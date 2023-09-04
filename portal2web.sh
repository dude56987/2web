#! /bin/bash
########################################################################
# portal2web scans links for services and creates a index for 2web
# Copyright (C) 2023  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
########################################################################
# enable debug log
#set -x
source /var/lib/2web/common
################################################################################
function scanLink(){
	link="$1"
	scanPorts="$2"
	scanPaths="$3"
	webDirectory="$4"
	# scan ports
	echo "$scanPorts" | shuf | while read -r scanPort;do
		name=$(echo "$scanPort" | cut -d',' -f1)
		port=$(echo "$scanPort" | cut -d',' -f2)
		description=$(echo "$scanPort" | cut -d',' -f3)
		# scan a given link for available ports and paths of known services
		ALERT "Scanning $link:$port"
		wget --tries=1 -q -S --timeout=15 -O /dev/null -o /dev/null "$link:$port"
		if [ $? -eq 0 ];then
			ALERT "Found link at $link:$port, generating link..."
			# for working links generate index files
			generateLink "$link:$port" "$name" "$description" "$webDirectory"
		else
			ALERT "No link found for $link:$port..."
		fi
	done
	# scan paths
	echo "$scanPaths" | shuf | while read -r scanPath;do
		name=$(echo "$scanPath" | cut -d',' -f1)
		path=$(echo "$scanPath" | cut -d',' -f2)
		description=$(echo "$scanPath" | cut -d',' -f3)
		# scan a given link for available ports and paths of known services
		ALERT "Scanning $link$path"
		wget --tries=1 -q -S --timeout=15 -O /dev/null -o /dev/null "$link$path"
		if [ $? -eq 0 ];then
			ALERT "Found link at $link$path, generating link..."
			# for working links generate index files
			generateLink "$link$path" "$name" "$description" "$webDirectory"
		else
			ALERT "No link found for $link$path..."
		fi
	done
}
################################################################################
function generateLink(){
	link="$1"
	name="$2"
	description="$3"
	webDirectory="$4"
	# create sum
	linkSum=$(echo "$link" | md5sum | cut -d' ' -f1)
	# figure out the domain from the link
	domain=$(echo "$link" | tr -s "/" | cut -d'/' -f2 )
	if echo "$domain" | grep -q ":";then
		domain=$(echo "$domain" | cut -d':' -f1 )
	fi
	if echo "$domain" | grep -q ".local";then
		domain=$(echo "$domain" | sed "s/\.local//g" )
	fi
	# generate qr codes for each link
	startDebug
	# update the link once every 14 days
	if cacheCheck "$webDirectory/portal/${domain}_$linkSum-web.png" "14";then
		# build the qr code image with a transparent background
		qrencode --background="00000000" -m 1 -l H -o "$webDirectory/portal/${domain}_$linkSum-qr.png" "$link"
		# create a screenshot of the webpage link
		wkhtmltoimage --width 1920 --height 1080 --javascript-delay 10000 "$link" "$webDirectory/portal/${domain}_$linkSum-web.png"
		# resize the qr code in order to use it in composite
		convert "$webDirectory/portal/${domain}_$linkSum-qr.png" -resize "1920x1080" "$webDirectory/portal/${domain}_$linkSum-qr.png"
		# save the combined file as the image to use in the web interface
		composite -gravity "center" "$webDirectory/portal/${domain}_$linkSum-qr.png" "$webDirectory/portal/${domain}_$linkSum-web.png" "$webDirectory/portal/${domain}_$linkSum.png"
		stopDebug
		# create .index files for direct links
		{
			echo "<div class='showPageEpisode'>"
			echo "	<a href='/portal/$domain.php'>"
			echo "		<h2>$domain</h2>"
			echo "	</a>"
			echo "	<a target='_BLANK' href='$link'>"
			echo "		<img src='/portal/${domain}_$linkSum.png'>"
			echo "		<div class='showIndexNumbers'>$name</div>"
			echo "			$description"
			echo "	</a>"
			#echo "	<a href='${domain}_$linkSum.php'>ℹ️</a>"
			echo "</div>"
		} > "$webDirectory/portal/${domain}_$linkSum.index"

		# link the portal info button to the portal page
		linkFile "/usr/share/2web/templates/portal.php" "$webDirectory/portal/$domain.php"

		addToIndex "$webDirectory/portal/${domain}_$linkSum.index" "$webDirectory/portal/portal.index"

		addToIndex "$webDirectory/portal/${domain}_$linkSum.index" "$webDirectory/new/all.index"
		addToIndex "$webDirectory/portal/${domain}_$linkSum.index" "$webDirectory/random/all.index"

		addToIndex "$webDirectory/portal/${domain}_$linkSum.index" "$webDirectory/new/portal.index"
		addToIndex "$webDirectory/portal/${domain}_$linkSum.index" "$webDirectory/random/portal.index"

		# add to sql
		SQLaddToIndex "$webDirectory/portal/${domain}_$linkSum.index" "$webDirectory/data.db" "portal"
	fi
}
################################################################################
function update(){
	# this will launch a processing queue that downloads updates to portal
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/portal/sources.cfg;then
		createDir "/etc/2web/portal/sources.d/"
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/portal2web_sources.cfg
		} > /etc/2web/portal/sources.cfg
	fi
	# load sources
	portalSources=$(grep -v "^#" /etc/2web/portal/sources.cfg)
	portalSources=$(echo -en "$portalSources\n$(grep --invert-match --no-filename "^#" /etc/2web/portal/sources.d/*.cfg)")
	portalSources=$(echo "$portalSources" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")

	# this will launch a processing queue that downloads updates to portal
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/portal/scanSources.cfg;then
		createDir "/etc/2web/portal/scanSources.d/"
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/portal2web_scanSources.cfg
			# add the localhost to the scan
			echo "http://$(hostname).local"
		} > /etc/2web/portal/scanSources.cfg
	fi
	# load sources
	portalScanSources=$(grep -v "^#" /etc/2web/portal/scanSources.cfg)
	portalScanSources=$(echo -en "$portalScanSources\n$(grep --invert-match --no-filename "^#" /etc/2web/portal/scanSources.d/*.cfg)")
	portalScanSources=$(echo "$portalScanSources" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")

	# load ports to scan on portal scan sources
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/portal/scanPorts.cfg;then
		createDir "/etc/2web/portal/scanPorts.d/"
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/portal2web_scanPorts.cfg
		} > /etc/2web/portal/scanPorts.cfg
	fi
	# load sources
	scanPorts=$(grep -v "^#" /etc/2web/portal/scanPorts.cfg)
	scanPorts=$(echo -en "$scanPorts\n$(grep --invert-match --no-filename "^#" /etc/2web/portal/scanPorts.d/*.cfg)")
	scanPorts=$(echo "$scanPorts" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")

	# load up path scan sources
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/portal/scanPaths.cfg;then
		createDir "/etc/2web/portal/scanPaths.d/"
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/portal2web_scanPaths.cfg
		} > /etc/2web/portal/scanPaths.cfg
	fi
	# load sources
	scanPaths=$(grep -v "^#" /etc/2web/portal/scanPaths.cfg)
	scanPaths=$(echo -en "$scanPaths\n$(grep --invert-match --no-filename "^#" /etc/2web/portal/scanPaths.d/*.cfg)")
	scanPaths=$(echo "$scanPaths" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")

	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	#downloadDirectory="$(downloadDir)"
	################################################################################
	# make portals directory
	createDir "$webDirectory/portal/"
	# setup the main index page
	linkFile "/usr/share/2web/templates/portals.php" "$webDirectory/portal/index.php"
	# copy over config page
	linkFile "/usr/share/2web/settings/portal.php" "$webDirectory/portal.php"
	# scan the sources
	ALERT "Scanning portal Sources: $portalSources"

	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi

	totalTracks=0
	processedTracks=0
	totalTrackList=""

	totalSources=0
	for portalSource in $portalSources;do
		totalSources=$(( $totalSources + 1 ))
	done
	for portalSource in $portalScanSources;do
		totalSources=$(( $totalSources + 1 ))
	done

	processedSources=0

	# remove existing portal links older than 10 days before generating new ones
	find "$webDirectory/portal/" -mtime 10 -type f | while read -r portalPath;do
		# remove .cfg .png and .index files
		if echo "$portalPath" | grep -q ".index";then
			#	remove discovered file
			#rm -v "$portalPath"
			echo "rm -v '$portalPath'"
		elif echo "$portalPath" | grep -q ".png";then
			#	remove discovered file
			#rm -v "$portalPath"
			echo "rm -v '$portalPath'"
		elif echo "$portalPath" | grep -q ".cfg";then
			#	remove discovered file
			#rm -v "$portalPath"
			echo "rm -v '$portalPath'"
		fi
	done

	# read the portal sources and generate links
	echo "$portalSources" | shuf | while read -r portalSource;do
		# split portal info up based on commas
		portalSourceName=$(echo "$portalSource" | cut -d',' -f1)
		portalSourceLink=$(echo "$portalSource" | cut -d',' -f2)
		portalSourceDesc=$(echo "$portalSource" | cut -d',' -f3)
		# add to tally
		processedSources=$(( $processedSources + 1 ))
		ALERT "Processing '$portalSource'"
		# generate the source sum
		portalSourceSum=$(echo "$portalSource" | md5sum | cut -d' ' -f1)
		if cacheCheck "$webDirectory/portal/portal2web_$portalSourceSum.cfg" "1";then
			INFO "⚙️ [$processedSources/$totalSources]"
			# generate portal links
			generateLink "$portalSourceLink" "$portalSourceName" "$portalSourceDesc" "$webDirectory" &
			waitQueue 0.2 "$totalCPUS"
		fi
	done

	# scan portal sources
	echo "$portalScanSources" | shuf | while read -r portalSource;do
		ALERT "Processing '$portalSource'"
		# add to tally
		processedSources=$(( $processedSources + 1 ))
		# generate the source sum
		portalSourceSum=$(echo "$portalSource" | md5sum | cut -d' ' -f1)
		if cacheCheck "$webDirectory/portal/portal2web_$portalSourceSum.cfg" "1";then
			INFO "⚙️ [$processedSources/$totalSources]"
			# generate portal links
			scanLink "$portalSource" "$scanPorts" "$scanPaths" "$webDirectory" &
			waitQueue 0.2 "$totalCPUS"
		fi
	done

	# block for parallel threads here
	blockQueue 1

	if test -f "$webDirectory/new/portal.index";then
		tempList=$(cat "$webDirectory/new/portal.index" | uniq | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/portal.index"
	fi
	if test -f "$webDirectory/random/portal.index";then
		tempList=$(cat "$webDirectory/random/portal.index" | uniq | tail -n 800 )
		echo "$tempList" > "$webDirectory/random/portal.index"
	fi
}
################################################################################
function resetCache(){
	webDirectory=$(webRoot)
	echo "There is no cache to remove from this module."
}
################################################################################
function nuke(){
	webDirectory=$(webRoot)
	# remove the kodi and web portal files
	rm -rv "$webDirectory/portal/" || echo "No files found in portal web directory..."
	rm -rv "$webDirectory/kodi/portal/" || echo "No files found in kodi directory..."
	rm -rv $webDirectory/sums/portal2web_*.cfg || echo "No file sums found..."
	# remove random generated widget
	rm -rv $webDirectory/web_cache/widget_random_portal.index || echo "No file sums found..."
	# remove updated generated widget
	rm -rv $webDirectory/web_cache/widget_updated_portal.index || echo "No file sums found..."
	# new indexes
	rm -rv "$webDirectory/new/portal.index" || echo "No portal index..."
	# random indexes
	rm -rv "$webDirectory/random/portal.index" || echo "No portal index..."
}
################################################################################
function main(){
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		checkModStatus "portal2web"
		lockProc "portal2web"
		webUpdate $@
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "portal2web"
		lockProc "portal2web"
		update $@
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		lockProc "portal2web"
		resetCache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		lockProc "portal2web"
		nuke
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "portal2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "portal2web"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "portal2web Version: "
		cat /usr/share/2web/version_portal2web.cfg
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/portal2web.txt"
	else
		checkModStatus "portal2web"
		lockProc "portal2web"
		update $@
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/portal/"
		drawLine
		echo "http://$(hostname).local:80/settings/portal.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
