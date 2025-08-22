#! /bin/bash
########################################################################
# portal2web scans links for services and creates a index for 2web
# Copyright (C) 2024  Carl J Smith
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
########################################################################
function avahi2csv(){
	# use avahi to scan for domains with zeroconf services, this does not list
	# the services themselves though
	#
	# - This text should be piped into a while loop for processing or a file
	# - The scrape needs to have a timeout so it will not hang randomly (This may be a issue in avahi-browse or a bad network connection error)
	# - Timeout is 8 minutes in seconds.
	rawData=$(timeout 500 avahi-browse -alrt)
	# rip the hostnames
	hostArray=$(echo "$rawData" | grep "hostname = ")
	# only include remote hosts, this will remove localhost services
	hostArray=$(echo "$hostArray" | grep ".local")
	# cleanup the host array data
	# - remove duplicates
	# - randomize the ordering
	hostArray=$(echo "$hostArray" | sort -u)
	hostArray=$(echo "$hostArray" | sort --random-sort )
	# for each entry parse the data and combine it into links
	dataLength=$(echo "$hostArray" | wc -l)
	#
	#numbers=$(seq 1 "$dataLength")
	outputData=""
	#startDebug
	#IFSBACKUP=$IFS
	#IFS=!'\n'
	#for lineNumber in $numbers;do
	#for lineNumber in $( seq 1 "$dataLength" );do
	echo -n "$hostArray" | while read -r hostLine;do
		## pull the line number from the array
		#lineHostName=$(echo "$hostArray" | head -n "$lineNumber" | tail -n 1)
		## cleanup the data to show only the domain itself
		#lineHostName=$(echo "$lineHostName" | sed "s/hostname = \[//g"| sed "s/\]$//g" | tr -s ' ' | tr -d ' ' )
		lineHostName=$(echo "$hostLine" | sed "s/hostname = \[//g"| sed "s/\]$//g" | tr -s ' ' | tr -d ' ' )
		# print the line if it is not blank
		echo "$lineHostName"
	done
	#IFS=$IFSBACKUP
	#stopDebug
}
########################################################################
function avahi2csvOld(){
	# Generate CSV data from the avahi browse output of the local network
	#
	# - this text should be piped into a while loop for processing or a file
	rawData=$(avahi-browse -alrt)
	# rip the parallel arrays
	hostArray=$(echo "$rawData" | grep "hostname = ")
	ipArray=$(echo "$rawData" | grep "address = ")
	portArray=$(echo "$rawData" | grep "port = ")
	#
	dataLength=$(echo "$hostArray" | wc -l)
	#echo "Found $dataLength services with avahi..."
	# for each entry parse the data and combine it into links
	#IFS=!'\n'
	#IFSBACKUP=$IFS
	#IFS=!'\n'
	numbers=$(seq 1 "$dataLength")
	#for lineNumber in $numbers;do
	for lineNumber in $( seq 1 "$dataLength" );do
		#echo "line = $lineNumber"
		lineHostName=$(echo "$hostArray" | head -n "$lineNumber" | tail -n 1)
		lineIp=$(echo "$ipArray" | head -n "$lineNumber"| tail -n 1)
		linePort=$(echo "$portArray" | head -n "$lineNumber"| tail -n 1)
		#
		lineHostName=$(echo "$lineHostName" | sed "s/hostname = \[//g"| sed "s/\]$//g" | tr -s ' ' | tr -d ' ' )
		lineIp=$(echo "$lineIp" | sed "s/address = \[//g"| sed "s/\]$//g" | tr -s ' ' | tr -d ' ' )
		linePort=$(echo "$linePort" | sed "s/port = \[//g"| sed "s/\]$//g" | tr -s ' ' | tr -d ' ' )
		#
		if [ $linePort -eq 22 ];then
			proto="SSH"
		elif [ $linePort -eq 80 ];then
			proto="WEBSITE"
		elif [ $linePort -eq 8080 ];then
			proto="WEBSITE"
		else
			proto="UNKNOWN"
		fi
		#
		echo "$proto,$lineHostName:$linePort,$lineIp:$linePort"
		#echo "---- end entry ----"
	done
	#IFS=$IFSBACKUP
}

################################################################################
function scanLink(){
	# scanLink $scanLink $scanPorts $scanPaths $webDirectory
	#
	# Scan a link and generates html files if link resolves
	#
	# - Uses scan paths and scan ports configured in /etc/2web/portal/
	#
	scanLink="$1"
	scanPorts="$2"
	scanPaths="$3"
	webDirectory="$4"
	# skip blank links
	if [ "$scanLink" == "" ];then
		return
	fi
	# scan ports
	echo "$scanPorts" | shuf | while read -r scanPort;do
		name=$(echo "$scanPort" | cut -d',' -f1)
		port=$(echo "$scanPort" | cut -d',' -f2)
		description=$(echo "$scanPort" | cut -d',' -f3)
		# scan a given link for available ports and paths of known services
		INFO "Scanning $scanLink:$port"
		wget --tries=1 -q -S --timeout=15 -O /dev/null -o /dev/null "$scanLink:$port"
		if [ $? -eq 0 ];then
			#ALERT "Found link at $scanLink:$port, generating link..."
			INFO "Found link at $scanLink:$port, generating link..."
			# for working links generate index files
			generateLink "$scanLink:$port" "$name" "$description" "$webDirectory"
		else
			#ALERT "No link found for $scanLink:$port..."
			INFO "No link found for $scanLink:$port..."
		fi
		# reset port
		port=""
	done
	# scan paths
	echo "$scanPaths" | shuf | while read -r scanPath;do
		name=$(echo "$scanPath" | cut -d',' -f1)
		path=$(echo "$scanPath" | cut -d',' -f2)
		description=$(echo "$scanPath" | cut -d',' -f3)
		# scan a given link for available ports and paths of known services
		INFO "Scanning $scanLink$path"
		wget --tries=1 -q -S --timeout=15 -O /dev/null -o /dev/null "$scanLink$path"
		if [ $? -eq 0 ];then
			#ALERT "Found link at $scanLink$path, generating link..."
			INFO "Found link at $scanLink$path, generating link..."
			# for working links generate index files
			generateLink "$scanLink$path" "$name" "$description" "$webDirectory"
		else
			#ALERT "No link found for $scanLink$path..."
			INFO "No link found for $scanLink$path..."
		fi
	done
}
################################################################################
function generateLink(){
	# generate a link
	#
	link="$1"
	name="$2"
	description="$3"
	webDirectory="$4"
	# skip blank links
	if [ "$link" == "" ];then
		return
	fi
	# create sum
	linkSum=$(echo "$link" | md5sum | cut -d' ' -f1)
	# figure out the domain from the link
	domain=$(echo "$link" | tr -s "/" | cut -d'/' -f2 )
	if echo "$domain" | grep -q ":";then
		domain=$(echo "$domain" | cut -d':' -f1 )
	fi
	# create the domain directory
	createDir "$kodiDirectory/portal/${domain}/"
	createDir "$webDirectory/portal/${domain}/"

	#if echo "$domain" | grep -q ".local";then
	#	domain=$(echo "$domain" | sed "s/\.local//g" )
	#fi

	# generate qr codes for each link

	# check the link type
	if echo "$bookmarks" | grep -q "$link";then
		# this is a bookmark only update the link data once every 365 days
		linkCacheTime=365
		isBookmark="yes"
	else
		# update local links once every 24 hours
		linkCacheTime=1
		isBookmark="no"
	fi
	# update the link once every 14 days
	if cacheCheck "$webDirectory/portal/${domain}/$linkSum.index" "$linkCacheTime";then
		addToLog "DOWNLOAD" "Building portal link" "$link"
		# build the qr code image with a transparent background
		qrencode --background="00000000" -m 1 -l H -o "$webDirectory/portal/${domain}/$linkSum-qr.png" "$link"
		domainPrefix=$(echo -n "${link}" | cut -d'/' -f1-3 )
		addToLog "INFO" "Downloading Favicon" "Favicon path '${domainPrefix}/favicon.ico'"
		# try to get the favicon
		#faviconData="$(curl "${domainPrefix}/favicon.ico")"
		wget "${domainPrefix}/favicon.ico" -O "$webDirectory/portal/${domain}/${linkSum}-icon.ico"
		#if [ "$?" -ne 0 ];then
		#if test -s "$webDirectory/portal/${domain}/${linkSum}-icon.ico";then
		if [ "$( cat "$webDirectory/portal/${domain}/${linkSum}-icon.ico" | wc -c )" -gt 0 ];then
			# convert the icon into a usable image
			#convert -quiet  "$webDirectory/portal/${domain}/$linkSum-icon.ico" -resize "200x200" -transparent -flatten "$webDirectory/portal/${domain}/$linkSum-web.png"
			convert -quiet  "$webDirectory/portal/${domain}/$linkSum-icon.ico" -background transparent -resize "200x200" -flatten "$webDirectory/portal/${domain}/$linkSum-web.png"
		fi
		if [ "$( cat "$webDirectory/portal/${domain}/${linkSum}-web.png" | wc -c )" -gt 0 ];then
			ALERT "The favicon downloaded correctly"
		else
			# create a screenshot of the webpage link if the favicon fails to download
			screenshotWebpage "$link" "$webDirectory/portal/${domain}/${linkSum}-web.png"
		fi
		# create the failsafe demo image
		if ! test -f  "$webDirectory/portal/${domain}/$linkSum-web.png";then
			demoImage "${domain}${linkSum}" "$webDirectory/portal/${domain}/$linkSum-web.png" "1920x1080"
		fi
		# create the thumbnail
		convert -quiet  "$webDirectory/portal/${domain}/$linkSum-web.png" -resize "300x200" "$webDirectory/portal/${domain}/$linkSum-thumb.png"
		# if the image creation failed create a image using the hash image function
		# resize the qr code in order to use it in composite
		convert "$webDirectory/portal/${domain}/$linkSum-qr.png" -resize "1920x1080" "$webDirectory/portal/${domain}/$linkSum-qr.png"
		# save the combined file as the image to use in the web interface
		#composite -gravity "center" "$webDirectory/portal/${domain}/$linkSum-qr.png" "$webDirectory/portal/${domain}/$linkSum-web.png" "$webDirectory/portal/${domain}/$linkSum.png"
		#convert "$webDirectory/portal/${domain}/$linkSum-web.png" "$webDirectory/portal/${domain}/$linkSum.png"
		#
		convert -quiet  "$webDirectory/portal/${domain}/$linkSum-web.png" -resize "300x200" "$webDirectory/portal/${domain}/$linkSum.png"

		# build the .desktop file link for all linux/bsd systems
		{
			echo "[Desktop Entry]"
			echo "Encoding=UTF-8"
			echo "Name=$name"
			echo "Type=Link"
			echo "URL=$link"
			echo "Icon=text-html"
		} > "$kodiDirectory/portal/${domain}/${name}.desktop"
		chmod +x "$kodiDirectory/portal/${domain}/${name}.desktop"
		# build the windows url file link
		{
			echo "[InternetShortcut]"
			echo "URL=$link"
		} > "$kodiDirectory/portal/${domain}/${name}.url"

		# add the raw link to the raw link index
		touch "/var/cache/2web/web/portal/raw.index"
		addToIndex "${link}" "/var/cache/2web/web/portal/raw.index"

		chmod +x "$kodiDirectory/portal/${domain}/${name}.url"
		# link the portal info button to the portal page
		linkFile "/usr/share/2web/templates/portal.php" "$webDirectory/portal/$domain/index.php"

		addToIndex "$linkSum.index" "$webDirectory/portal/$domain/portal.index"
		addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/portal/portal.index"

		addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/new/all.index"
		addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/random/all.index"

		# add the filtered lists
		if [ "$isBookmark" == "yes" ];then
			addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/portal/bookmarks.index"
			addToIndex "$webDirectory/portal/${domain}.index" "$webDirectory/portal/domain_bookmarks.index"
		else
			addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/portal/local.index"
			addToIndex "$webDirectory/portal/${domain}.index" "$webDirectory/portal/domain_local.index"
		fi

		addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/new/portal.index"
		addToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/random/portal.index"

		# add to sql
		SQLaddToIndex "$webDirectory/portal/${domain}/$linkSum.index" "$webDirectory/data.db" "portal"

		# add this comic to the search index
		addToSearchIndex "$webDirectory/portal/${domain}/$linkSum.index" "${domain} ${name}" "/portal/$domain/"

		# create .index files for direct links
		{
			echo "	<a class='showPageEpisode' href='/exit.php?to=$link'>"
			echo "		<h2>$domain</h2>"
			# title newline code is &#13;
			echo "		<img title='${domain}&#13;&#13;${name}&#13;${description}' src='/portal/${domain}/$linkSum.png'>"
			echo "		<div class='showIndexNumbers'>$name</div>"
			echo "		$description"
			echo "	</a>"
		} > "$webDirectory/portal/${domain}/$linkSum.index"
		if ! test -f "$webDirectory/portal/${domain}.index";then
			# create .index files for domain
			{
				echo "	<a class='button' href='/portal/$domain/'>"
				echo "		$domain"
				echo "	</a>"
			} > "$webDirectory/portal/${domain}.index"
			# add the domain to the domain index
			addToIndex "$webDirectory/portal/${domain}.index" "$webDirectory/portal/domain.index"
		fi
	fi
}
################################################################################
function loadBookmarks(){
	# load a bookmarks.html file as a list of links
	bookmarkData="$(cat "$1" | sed "s/ /\n/g" | grep -i "href=" | sed 's/"/\n/g')"
	#
	httpData=$(echo "$bookmarkData" | grep -i "^http:")
	httpsData=$(echo "$bookmarkData" | grep -i "^https:")
	#
	echo "$httpData"
	echo "$httpsData"
}
################################################################################
function update(){
	addToLog "INFO" "STARTED Update" "$(date)"
	INFO "Loading Portal sources..."
	# load local portal links to LAN and local network resources
	portalSources=$(loadConfigs "/etc/2web/portal/sources.cfg" "/etc/2web/portal/sources.d/" "/etc/2web/config_default/portal2web_sources.cfg")
	INFO "Loading Bookmarks..."
	createDir "/etc/2web/portal/bookmarks.d/"
	# load remote links that do not change need updated more than once a year
	bookmarks=$(loadConfigs "/etc/2web/portal/bookmarks.cfg" "/etc/2web/portal/bookmarks.d/" "/etc/2web/config_default/portal2web_bookmarks.cfg")

	# remove empty lines and other problems in sources
	portalSources=$(echo -e "${portalSources}\n${bookmarks}" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g" | sed "s/\n\n//g")

	ALERT "$portalSources" "Sources"

	# this will launch a processing queue that downloads updates to portal
	INFO "Loading up scan sources..."
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

	ALERT "$portalSources" "Scan Sources"

	# load ports to scan on portal scan sources
	INFO "Loading up known ports..."
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
	INFO "Loading up known paths..."
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
	kodiDirectory="$(kodiRoot)"
	generatedDirectory="$(generatedRoot)"
	################################################################################
	#downloadDirectory="$(downloadDir)"
	################################################################################
	# make portals directory
	createDir "$webDirectory/portal/"
	createDir "$kodiDirectory/portal/"
	# setup the main index page
	linkFile "/usr/share/2web/templates/portals.php" "$webDirectory/portal/index.php"
	# copy over config page
	linkFile "/usr/share/2web/settings/portal.php" "$webDirectory/portal.php"
	# scan the sources
	ALERT "$portalSources" "Scanning Portal Sources"
	#
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
	#find "$webDirectory/portal/" -mtime 10 -type f | while read -r portalPath;do
	#	# remove .cfg .png and .index files
	#	if echo "$portalPath" | grep -q ".index";then
	#		#	remove discovered file
	#		#rm -v "$portalPath"
	#		echo "rm -v '$portalPath'"
	#	elif echo "$portalPath" | grep -q ".png";then
	#		#	remove discovered file
	#		#rm -v "$portalPath"
	#		echo "rm -v '$portalPath'"
	#	elif echo "$portalPath" | grep -q ".cfg";then
	#		#	remove discovered file
	#		#rm -v "$portalPath"
	#		echo "rm -v '$portalPath'"
	#	fi
	#done

	# if at least one source exists
	if echo "$portalSources" | grep -q "http";then
		# read the portal sources and generate links
		#echo "$portalSources" | shuf | while read -r portalSource;do
		IFSBACKUP=$IFS
		IFS=!'\n'
		#for portalSource in $(echo "$portalSources" | shuf);do
		echo "$portalSources" | sort -u | shuf | while read -r portalSource;do
			# split portal info up based on commas
			portalSourceName=$(echo "$portalSource" | cut -d';' -f1)
			portalSourceLink=$(echo "$portalSource" | cut -d';' -f2)
			portalSourceDesc=$(echo "$portalSource" | cut -d';' -f3)
			# add to tally
			processedSources=$(( $processedSources + 1 ))
			#ALERT "Processing '$portalSource'"
			# generate the source sum
			portalSourceSum=$(echo "$portalSource" | md5sum | cut -d' ' -f1)
			if cacheCheck "$webDirectory/portal/portal2web_$portalSourceSum.cfg" "1";then
				INFO "⚙️ [$processedSources/$totalSources]"
				# generate portal links
				generateLink "$portalSourceLink" "$portalSourceName" "$portalSourceDesc" "$webDirectory" &
				waitQueue 0.2 "$totalCPUS"
			fi
		done
		IFS=$IFSBACKUP
	fi
	# check that at least one source exists
	if echo "$portalScanSources" | grep -q "http";then
		# scan portal sources
		#echo "$portalScanSources" | shuf | while read -r portalSource;do
		IFSBACKUP=$IFS
		IFS=!'\n'
		#for portalSource in $(echo "$portalScanSources" | shuf);do
		#echo "$portalScanSources" | shuf | while read -r portalSource;do
		echo "$portalScanSources" | sort -u | shuf | while read -r portalSource;do
			#ALERT "Processing '$portalSource'"
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
		IFS=$IFSBACKUP
	fi
	# check if zeroconf should be scanned and added to the portal
	if yesNoCfgCheck "/etc/2web/portal/scanAvahi.cfg";then
		# look for bonjour/zeroconf/avahi connections
		# - sort and keep only unique entries
		#avahi2csv | sort -u | shuf | while read -r portalSource;do
		IFSBACKUP=$IFS
		IFS=!'\n'
		#for portalSource in $(avahi2csv | sort -u | shuf);do
		#avahi2csv | sort -u | tr -s '\n' | shuf | while read -r portalSource;do
		#avahi2csv | sort -u | shuf | while read -r portalSource;do
		avahi2csv | sort -u | shuf | while read -r portalSource;do
			#ALERT "Processing '$portalSource'"
			# add to tally
			processedSources=$(( $processedSources + 1 ))
			# split up the portal data
			linkDomainSource=$(echo "http://$portalSource")
			# generate the source sum
			portalSourceSum=$(echo "$linkDomainSource" | md5sum | cut -d' ' -f1)
			#
			if cacheCheck "$webDirectory/portal/portal2web_$portalSourceSum.cfg" "1";then
				INFO "⚙️ [$processedSources/$totalSources]"
				# generate portal links from zeroconf services
				scanLink "$linkDomainSource" "$scanPorts" "$scanPaths" "$webDirectory" &
				waitQueue 0.2 "$totalCPUS"
			fi
		done
		IFS=$IFSBACKUP
	fi
	# block for parallel threads here
	blockQueue 1

	if test -f "/var/cache/2web/web/new/portal.index";then
		tempList=$(cat "/var/cache/2web/web/new/portal.index" | uniq | tail -n 800 )
		echo "$tempList" > "/var/cache/2web/web/new/portal.index"
	fi
	if test -f "/var/cache/2web/web/random/portal.index";then
		tempList=$(cat "/var/cache/2web/web/random/portal.index" | uniq | tail -n 800 )
		echo "$tempList" > "/var/cache/2web/web/random/portal.index"
	fi
	if checkFileDataSum "/var/cache/2web/web/portal/raw.index";then
		# remove duplicates
		#rawData="$(cat "/var/cache/2web/web/portal/raw.index" | sort -u)"
		#echo "$rawData" > "/var/cache/2web/web/portal/raw.index"
		{
			# rebuild the html bookmark import file
			echo "<!DOCTYPE NETSCAPE-Bookmark-file-1>"
			echo "	<!--This is an automatically generated file."
			echo "	It will be read and overwritten.";
			echo "	Do Not Edit! -->"
			echo "	<Title>Bookmarks</Title>"
			echo "	<H1>Bookmarks</H1>"
			echo "	<DL><P>"
			IFSBACKUP=$IFS
			IFS=!'\n'
			for link in $(cat "/var/cache/2web/web/portal/raw.index" | sort -u | shuf );do
				linkDate="$(date "+%s")"
				echo "			<DT><A HREF=\"${link}\" ADD_DATE=\"${linkDate}\" LAST_VISIT=\"${linkDate}\" LAST_MODIFIED=\"${linkDate}\">${link}</A>"
			done
			IFS=$IFSBACKUP
			echo "	</DL><P>"
		} > "$kodiDirectory/portal/bookmarks.html"
		setFileDataSum "/var/cache/2web/web/portal/raw.index"
	fi

	addToLog "INFO" "Update FINISHED" "$(date)"
}
################################################################################
function resetCache(){
	yellowBackground
	blackText
		drawLine
		drawSmallHeader "There is no cache to remove from this module."
		drawLine
	resetColor
}
################################################################################
function nuke(){
	webDirectory=$(webRoot)
	kodiDirectory=$(kodiRoot)
	# remove the kodi and web portal files
	delete "$webDirectory/portal/"
	delete "$kodiDirectory/portal/"
	rm -v $webDirectory/sums/portal2web_*.cfg
	# remove random generated widget
	delete "$webDirectory/web_cache/widget_random_portal.index"
	# remove updated generated widget
	delete "$webDirectory/web_cache/widget_updated_portal.index"
	# new and random indexes
	delete "$webDirectory/new/portal.index"
	delete "$webDirectory/random/portal.index"
}
################################################################################
# set the theme of the lines in CLI output
LINE_THEME="computers"
#
INPUT_OPTIONS="$@"
PARALLEL_OPTION="$(loadOption "parallel" "$INPUT_OPTIONS")"
MUTE_OPTION="$(loadOption "mute" "$INPUT_OPTIONS")"
FAST_OPTION="$(loadOption "fast" "$INPUT_OPTIONS")"
#
if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
	checkModStatus "portal2web"
	lockProc "portal2web"
	update "$@"
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
	ALERT "$(cat /usr/share/2web/buildDate.cfg)\n" "Build Date"
	ALERT "$(cat /usr/share/2web/version_portal2web.cfg)\n" "portal2web Version"
elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
	cat "/usr/share/2web/help/portal2web.txt"
else
	checkModStatus "portal2web"
	lockProc "portal2web"
	#startSpinner
	update "$@"
	#stopSpinner
	showServerLinks
	drawSmallHeader "Module Links"
	drawLine
	echo "http://$(hostname).local:80/portal/"
	echo "http://$(hostname).local:80/settings/portal.php"
	drawLine
fi
################################################################################
