#! /bin/bash
########################################################################
# wiki2web adds .zim files to the webserver as wikis
# Copyright (C) 2022  Carl J Smith
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
source /var/lib/2web/common
################################################################################
# enable debug log
#set -x
################################################################################
function processZimWiki(){
	# process a zim wiki
	zimFilePath=$1
	webDirectory=$2
	kodiDirectory="$(kodiRoot)"
	# if the md5sum of the wiki file has changed update it
	#if checkFileDataSum "$zimFilePath";then
	if test -f "$zimFilePath";then
		# link the zim file into the kodi directory
		linkFile "$zimFilePath" "$kodiDirectory/wiki/"
		# get the filename for the wiki name
		#wikiName="$(basename "$wikiLocation" | cut -d "." -f1)"
		# generate a md5sum for the location
		wikiSum=$(basename "$zimFilePath" | md5sum | cut -d' ' -f1)
		# create a directory for extracting the wiki into
		createDir "$webDirectory/wiki/$wikiSum/"
		createDir "$webDirectory/wiki/$wikiSum/M/"

		# extract the zim file to html
		#zimdump dump --redirect --dir="$webDirectory/wiki/$wikiSum/" "$zimFilePath"
		#zimdump -s -D "$webDirectory/wiki/$wikiSum/" "$zimFilePath"
		#zimdump dump --dir="$webDirectory/wiki/$wikiSum/" -s "$zimFilePath"

		zimdump dump "$zimFilePath" --dir="$webDirectory/wiki/$wikiSum/"

		# add a .htaccess file to allow the sub web directory the wiki is loaded into to work as a sub website to the main apache site
		#{
		#	echo ""
		#	echo ""
		#	echo ""
		#	echo ""
		#} > "$webDirectory/wiki/$wikiSum/.htaccess"

		# get the main page path
		mainPageName=$(zimdump info "$zimFilePath" | grep "main page:" | cut -d':' -f2 | tr -s ' ' | sed "s/^ //g")
		#
		if ! test -f "$webDirectory/wiki/$wikiSum/wiki.index";then
			echo "$mainPageName" > "$webDirectory/wiki/$wikiSum/M/MainPage"
			#
			if test -f "$webDirectory/wiki/$wikiSum/M/Title";then
				# look for the title
				wikiTitle="$(cat "$webDirectory/wiki/$wikiSum/M/Title")"
			else
				# use the source file name if the title is not set
				wikiTitle="$(basename "$zimFilePath" | rev | cut -d'.' -f2- | rev)"
			fi
			# cleanup the wiki title
			wikiTitle="$(echo -n "$wikiTitle" | sed "s/\.com/ /g")"
			wikiTitle="$(echo -n "$wikiTitle" | sed "s/\.net/ /g")"
			wikiTitle="$(echo -n "$wikiTitle" | sed "s/\.org/ /g")"
			wikiTitle="$(echo -n "$wikiTitle" | sed "s/\./ /g")"
			wikiTitle="$(echo -n "$wikiTitle" | sed "s/_/ /g")"
			wikiTitle="$(echo -n "$wikiTitle" | tr -s " ")"
			# cleanup the wiki title
			wikiTitle="$(cleanText "$wikiTitle" )"
			#
			if ! test -f "$webDirectory/wiki/$wikiSum/thumb.png";then
				convert "$webDirectory/wiki/$wikiSum/-/favicon" -adaptive-resize 128x128 "$webDirectory/wiki/$wikiSum/thumb.png"
			fi
			#
			if ! test -f "$webDirectory/wiki/$wikiSum/thumb.png";then
				# create the fallback thumbnail
				demoImage "$webDirectory/wiki/$wikiSum/thumb.png" "$wikiTitle" "400" "400"
			fi
			#
			mainPage="$(cat "$webDirectory/wiki/$wikiSum/M/MainPage")"
			iconPath="/wiki/$wikiSum/thumb.png"
			# if the icon path does not exist in the extracted wiki
			if ! test -f "$webDirectory$iconPath";then
				# if the main page works
				if test -f "$webDirectory/wiki/$wikiSum/$mainPage";then
					screenshotWebpage "$webDirectory/wiki/$wikiSum/$mainPage" "$webDirectory/wiki/$wikiSum/thumb.png"
				fi
			fi

			description="$(cat "$webDirectory/wiki/$wikiSum/M/Description")"

			if test -f "$webDirectory/wiki/$wikiSum/M/Date";then
				wikiDate="$(cat "$webDirectory/wiki/$wikiSum/M/Date")"
			else
				# set the date based on the file creation time
				wikiDate=$(stat -c "%w" "$zimFilePath")
			fi
			counter="$(cat "$webDirectory/wiki/$wikiSum/M/Counter"| sed "s/;/\n/g")"
			creator="$(cat "$webDirectory/wiki/$wikiSum/M/Creator")"
			publisher="$(cat "$webDirectory/wiki/$wikiSum/M/Publisher")"
			language="$(cat "$webDirectory/wiki/$wikiSum/M/Language")"
			#
			{
				echo "<a href='/wiki/$wikiSum/' class='inputCard' >"
				# write the wiki title
				echo "	<h2>$wikiTitle</h2>"
				if test -f "${webDirectory}${iconPath}";then
					echo "	<img loading='lazy' class='wikiIcon' src='$iconPath' />"
				fi
				echo "	<p>$description</p>"
				echo "	<p>$wikiDate</p>"
				echo "	<p>$counter</p>"
				echo "</a>"
			} > "$webDirectory/wiki/$wikiSum/wiki.index"
		fi
		#
		linkFile "/usr/share/2web/templates/wiki.php" "$webDirectory/wiki/$wikiSum/index.php"
		# add to indexes
		addToIndex "$webDirectory/wiki/$wikiSum/wiki.index" "$webDirectory/wiki/wikis.index"
		SQLaddToIndex "$webDirectory/wiki/$wikiSum/wiki.index" "$webDirectory/data.db" "wiki"
	fi
}
################################################################################
function update(){
	if ! test -f /usr/bin/zimdump;then
		ALERT "No zimdump package was found the program can not continue!"
		# without zimdump wiki2web can not do anything so exit with error
		return 1
	fi

	webDirectory=$(webRoot)
	kodiDirectory="$(kodiRoot)"

	# create the kodi directory
	createDir "$kodiDirectory/wiki/"

	# create the web directory
	createDir "$webDirectory/wiki/"

	# link the homepage
	linkFile "/usr/share/2web/templates/wikis.php" "$webDirectory/wiki/index.php"

	# link the random poster script
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/wiki/randomPoster.php"
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/wiki/randomFanart.php"

	# this will launch a processing queue that looks for .zim files in a server path
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/wiki/libraries.cfg;then
		createDir "/etc/2web/wiki/libraries.d/"
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/wiki2web_libraries.cfg
		} > /etc/2web/wiki/libraries.cfg
	fi
	# load sources
	wikiLocations=$(grep -v "^#" /etc/2web/wiki/libraries.cfg)
	wikiLocations=$(echo -e "$wikiLocations\n$(grep -v --no-filename "^#" /etc/2web/wiki/libraries.d/*.cfg)")
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make comics directory
	createDir "$webDirectory/wiki/"
	# scan the sources
	ALERT "wiki Sources : $wikiLocations"
	totalwikiLocations=$(echo "$wikiLocations" | wc -l)
	#for comicSource in $comicSources;do
	wikiLocations=$(echo "$wikiLocations" | tr -s '\n')

	# check for local websites, that are previously extracted directories
	find "$wikiLocation" -maxdepth 1 -mindepth 1 -type d | sort | while read zimFilePath;do
		# scan for list of directories containing a local web directories
		break
	done

	echo "$wikiLocations" | while read wikiLocation;do
		# create the default download location
		createDir "$wikiLocation"
		ALERT "Scanning location '$wikiLocation'"
		#if checkDirSum "$wikiLocation";then

		# search for zim files in the location
		find "$wikiLocation" -type f -name '*.zip' | sort | while read zimFilePath;do
			# read website compressed as zip file

			# extract the zip file as a wiki

			# run a search and replace for all files inside the extracted zip file

			# replace the <body> tag to include php header

			# replace </body> tage to include php footer

			# replace href= with base wiki path for webserver

			# replace href="http with php http redirect with warning

			# replace .html" in href links with .php

			break
		done

		# check for parallel processing and count the cpus
		if echo "$@" | grep -q -e "--parallel";then
			totalCPUS=$(cpuCount)
		else
			totalCPUS=1
		fi
		# extract all found zim files
		find "$wikiLocation" -type f -name '*.zim' | sort | while read zimFilePath;do
			processZimWiki "$zimFilePath" "$webDirectory" &
			waitQueue 0.5 "$totalCPUS"
		done
	done
	# block for parallel threads here
	blockQueue 1
}
################################################################################
function resetCache(){
	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"
	# remove web cache
	rm -rv "$webDirectory/wiki/" || INFO "No comic web directory at '$webDirectory/wiki/'"
}
################################################################################
function nuke(){
	webDirectory=$(webRoot)
	kodiDirectory="$(kodiRoot)"
	#
	delete "$webDirectory/wiki/"
	delete "$kodiDirectory/wiki/"
}
################################################################################
# set the theme of the lines in CLI output
LINE_THEME="flowerRand"
#
INPUT_OPTIONS="$@"
PARALLEL_OPTION="$(loadOption "parallel" "$INPUT_OPTIONS")"
MUTE_OPTION="$(loadOption "mute" "$INPUT_OPTIONS")"
FAST_OPTION="$(loadOption "fast" "$INPUT_OPTIONS")"
################################################################################
if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
	checkModStatus "wiki2web"
	# lock the process
	lockProc "wiki2web"
	update
elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
	checkModStatus "wiki2web"
	drawLine
	echo "There are no modules to upgrade in wiki2web"
	drawLine
	showServerLinks
	drawSmallHeader "Module Links"
	drawLine
	echo "http://$(hostname).local/wiki/"
	echo "http://$(hostname).local/settings/wiki.php"
	drawLine
elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
	# lock the process
	lockProc "wiki2web"
	nuke
elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
	# lock the process
	lockProc "wiki2web"
	# remove the whole wiki directory
	rm -rv $(webRoot)/wiki/ || INFO "No wiki web directory at '$webDirectory/wiki/'"
elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
	cat "/usr/share/2web/help/wiki2web.txt"
elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
	enableMod "wiki2web"
elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
	disableMod "wiki2web"
else
	checkModStatus "wiki2web"
	# lock the process
	lockProc "wiki2web"
	# update sources
	update
	showServerLinks
	# show the server link at the bottom of the interface
	drawSmallHeader "Module Links"
	drawLine
	echo "http://$(hostname).local/wiki/"
	echo "http://$(hostname).local/settings/wiki.php"
	drawLine
fi
