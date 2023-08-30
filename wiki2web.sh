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
function update(){
	if ! test -f /usr/bin/zimdump;then
		# without zimdump wiki2web can not do anything so exit with error
		return 1
	fi

	webDirectory=$(webRoot)

	# create the kodi directory
	createDir "$webDirectory/kodi/wiki/"

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
	if ! test -f /etc/2web/wiki/sources.cfg;then
		# if no config exists create the default config
		{
			echo "################################################################################"
			echo "# Example Config"
			echo "################################################################################"
			echo "# - all .zim files found in the paths added here will be extracted and added to"
			echo "#   the server"
			echo "# - The .zim format project website"
			echo "#   - https://wiki.openzim.org/wiki/OpenZIM"
			echo "# - You can download .zim files from the kiwix project"
			echo "#   - https://library.kiwix.org/"
			echo "################################################################################"
			echo "/var/cache/2web/downloads/wiki/"
		} > /etc/2web/wiki/sources.cfg
	fi
	# load sources
	wikiLocations=$(grep -v "^#" /etc/2web/wiki/sources.cfg)
	wikiLocations=$(echo -e "$wikiLocations\n$(grep -v --no-filename "^#" /etc/2web/wiki/sources.d/*.cfg)")
	################################################################################
	webDirectory=$(webRoot)
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

		#
		find "$wikiLocation" -type f -name '*.zim' | sort | while read zimFilePath;do
			# if the md5sum of the wiki file has changed update it
			#if checkFileDataSum "$zimFilePath";then
			if test -f "$zimFilePath";then
				# link the zim file into the kodi directory
				linkFile "$zimFilePath" "$webDirectory/kodi/wiki/"
				# get the filename for the wiki name
				#wikiName=$(popPath $wikiLocation)
				# generate a md5sum for the location
				wikiSum=$(echo -n "$zimFilePath" | md5sum | cut -d' ' -f1)
				# create a directory for extracting the wiki into
				createDir "$webDirectory/wiki/$wikiSum/"
				createDir "$webDirectory/wiki/$wikiSum/M/"

				set -x
				# extract the zim file to html
				#zimdump dump --redirect --dir="$webDirectory/wiki/$wikiSum/" "$zimFilePath"
				zimdump dump --dir="$webDirectory/wiki/$wikiSum/" "$zimFilePath"

				mainPageName=$(zimdump info "$zimFilePath" | grep "main page:" | cut -d':' -f2 | tr -s ' ' | sed "s/^ //g")
				echo "$mainPageName" > "$webDirectory/wiki/$wikiSum/M/MainPage"

				# add to indexes
				addToIndex "$webDirectory/wiki/$wikiSum/wiki.index" "$webDirectory/wiki/wikis.index"
				SQLaddToIndex "$webDirectory/wiki/$wikiSum/wiki.index" "$webDirectory/data.db" "wiki"

				if ! test -f "$webDirectory/wiki/$wikiSum/thumb.png";then
					convert "$webDirectory/wiki/$wikiSum/-/favicon" -adaptive-resize 128x128 "$webDirectory/wiki/$wikiSum/thumb.png"
					#wkhtmltoimage --width 1920 "http://localhost/wiki/$wikiSum/index.php" "$webDirectory/wiki/$wikiSum/thumb.png"
				fi

				if ! test -f "$webDirectory/wiki/$wikiSum/wiki.index";then
					{
						echo "<a href='/wiki/$wikiSum/' class='inputCard' >"
						# write the wiki title
						echo "<h2>$(cat "$webDirectory/wiki/$wikiSum/M/Title")</h2>"
						if test -f "$webDirectory/wiki/$wikiSum/thumb.png";then
							echo "<img loading='lazy' src='/wiki/$wikiSum/thumb.png' />"
						fi
						echo "<p>$(cat "$webDirectory/wiki/$wikiSum/M/Description")</p>"
						echo "<p>$(cat "$webDirectory/wiki/$wikiSum/M/Date")</p>"
						echo "<p>$(cat "$webDirectory/wiki/$wikiSum/M/Counter"| sed "s/;/\n/g")</p>"
						#echo "<div>$wikiName</div>"
						echo "</a>"
					} > "$webDirectory/wiki/$wikiSum/wiki.index"
				fi

				linkFile "/usr/share/2web/templates/wiki.php" "$webDirectory/wiki/$wikiSum/index.php"

				# copy the wiki.php template into the extracted wiki directory
				#linkFile "/usr/share/2web/templates/wiki.php" "$webDirectory/wiki/$wikiSum/index.php"
			fi
		done
		#fi
	done
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
	rm -rv $(webRoot)/wiki/
	rm -rv $(webRoot)/kodi/wiki/
}
################################################################################
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "wiki2web"
		# lock the process
		lockProc "wiki2web"
		update
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
		# display the help
		main --help
		showServerLinks
		# show the server link at the bottom of the interface
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local/wiki/"
		drawLine
		echo "http://$(hostname).local/settings/wiki.php"
		drawLine
	fi

}
################################################################################
main "$@"
exit
