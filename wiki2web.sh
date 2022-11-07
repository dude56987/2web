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
ALERT(){
	echo;
	echo "$1";
	echo;
}
################################################################################
drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
}
################################################################################
linkFile(){
	# link file if it is a link
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
}
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/2web/nfo/web.cfg ];then
		webDirectory=$(cat /etc/2web/nfo/web.cfg)
	else
		chown -R www-data:www-data "/var/cache/2web/cache/"
		echo "/var/cache/2web/cache/" > /etc/2web/nfo/web.cfg
		webDirectory="/var/cache/2web/cache/"
	fi
	# check for a trailing slash appended to the path
	if [ "$(echo "$webDirectory" | rev | cut -b 1)" == "/" ];then
		# rip the last byte off the string and return the correct path, WITHOUT THE TRAILING SLASH
		webDirectory="$(echo "$webDirectory" | rev | cut -b 2- | rev )"
	fi
	echo "$webDirectory"
}
################################################################################
function cacheCheck(){

	filePath="$1"
	cacheDays="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -mtime "+$cacheDays") ]];then
			# the file is more than "$2" days old, it needs updated
			INFO "File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			INFO "File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		INFO "File does not exist, it must be created $1"
		return 0
	fi
}
################################################################################
function cacheCheckMin(){

	filePath="$1"
	cacheMinutes="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -cmin "+$cacheMinutes") ]];then
			# the file is more than "$2" minutes old, it needs updated
			INFO "File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			INFO "File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		INFO "File does not exist, it must be created $1"
		return 0
	fi
}
################################################################################
getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
################################################################################
function INFO(){
	width=$(tput cols)
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[INFO]: $1$buffer" | cut -b"1-$(( $width - 1 ))")"
	# print the line
	printf "$output\r"
	#echo "$output"
	#printf "$output\n"
}
################################################################################
function ERROR(){
	output=$1
	printf "[ERROR]: $output\n"
}
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
function addToIndex(){
	indexItem="$1"
	indexPath="$2"
	if test -f "$indexPath";then
		# the index file exists
		if grep -q "$indexItem" "$indexPath";then
			ALERT "The Index '$indexPath' already contains '$indexItem'"
		else
			ALERT "Adding '$indexItem' to '$indexPath'"
			# the item is not in the index
			echo "$indexItem" >> "$indexPath"
		fi
	else
		ALERT "No index found, creating one..."
		ALERT "Adding '$indexItem' to '$indexPath'"
		# create the index file
		touch "$indexPath"
		# set ownership of the newly created index
		chown www-data:www-data "$indexPath"
		# the index file does not exist
		echo "$indexItem" > "$indexPath"
	fi
}
################################################################################
popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg"
	echo "$1" | rev | cut -d'/' -f1 | rev
}
################################################################################
function update(){
	#DEBUG
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
			echo "################################################################################"
			echo "/var/cache/2web/downloads_wiki/"
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
	echo "$wikiLocations" | while read wikiLocation;do
		# create the default download location
		createDir "$wikiLocation"
		ALERT "Scanning location '$wikiLocation'"
		# search for zim files in the location
		find "$wikiLocation" -type f -name '*.zim' | sort | while read zimFilePath;do
			# get the filename for the wiki name
			wikiName=$(popPath $wikiLocation)
			# generate a md5sum for the location
			wikiSum=$(echo -n "$zimFilePath" | md5sum | cut -d' ' -f1)
			# create a directory for extracting the wiki into
			createDir "$webDirectory/wiki/$wikiSum/"

			set -x
			# extract the zim file to html
			zimdump dump --dir="$webDirectory/wiki/$wikiSum/" "$zimFilePath"

			mainPageName=$(zimdump info "$zimFilePath" | grep "main page:" | cut -d':' -f2 | tr -s ' ' | sed "s/^ //g")
			echo "$mainPageName" > "$webDirectory/wiki/$wikiSum/M/MainPage"

			set +x

			addToIndex "$webDirectory/wiki/$wikiSum/wiki.index" "$webDirectory/wiki/wikis.index"

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
		done
	done
}
################################################################################
webUpdate(){
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
	rm -rv $(webRoot)/wiki/*
	rm -rv $(webRoot)/wiki/data/forcast_*.index
	rm -rv $(webRoot)/wiki/data/current_*.cfg
	rm -rv $(webRoot)/wiki.index
}
################################################################################
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		checkModStatus "wiki2web"
		# lock the process
		lockProc "wiki2web"
		webUpdate
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
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
		# gen prelem website
		webUpdate
		# update sources
		update
		# update webpages
		webUpdate
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
