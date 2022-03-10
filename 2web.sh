#! /bin/bash
########################################################################
STOP(){
	echo ">>>>>>>>>>>DEBUG STOPPER<<<<<<<<<<<" #DEBUG DELETE ME
	read -r #DEBUG DELETE ME
}
########################################################################
INFO(){
	width=$(tput cols)
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[INFO]: $1$buffer" | cut -b"1-$(( $width - 1 ))")"
	# print the line
	printf "$output\r"
}
################################################################################
debugCheck(){
	if [ -f /etc/nfo2web/debug.enabled ];then
		# if debug mode is enabled show execution
		set -x
	else
		if ! [ -d /etc/nfo2web/ ];then
			# create dir if one does not exist
			mkdir -p /etc/nfo2web/
		fi
		if ! [ -f /etc/nfo2web/debug.disabled ];then
			# create debug flag file disabed, if it does not exist
			touch /etc/nfo2web/debug.disabled
		fi
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
cacheCheck(){

	filePath="$1"
	cacheDays="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -mtime "+$cacheDays") ]];then
			# the file is more than "$2" days old, it needs updated
			INFO "[INFO]: File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			INFO "[INFO]: File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		INFO "[INFO]: File does not exist, it must be created $1"
		return 0
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
########################################################################
update2web(){
	echo "Updating 2web..."
	# build 2web common web interface this should be ran after each install to update main web components on which modules depend
}
################################################################################
main(){
	if [ "$1" == "-a" ] || [ "$1" == "--all" ] || [ "$1" == "all" ];then
		# update main components
		update2web
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web
		/usr/bin/iptv2web
		/usr/bin/comic2web
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ];then
		# update main components
		update2Web
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web update
		/usr/bin/iptv2web update
		/usr/bin/comic2web update
	elif [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ];then
		# update the website content
		/usr/bin/nfo2web webgen
		/usr/bin/iptv2web webgen
		/usr/bin/comic2web webgen
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ];then
		# upgrade packages related to operation of webserver
		/usr/bin/nfo2web upgrade
		/usr/bin/iptv2web upgrade
		/usr/bin/comic2web upgrade
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ];then
		# remove all genereated web content
		/usr/bin/nfo2web reset
		/usr/bin/comic2web reset
		/usr/bin/iptv2web reset
	elif [ "$1" == "-cc" ] || [ "$1" == "--cleancache" ] || [ "$1" == "cleancache" ] ;then
		# run the cleanup to remove cached files older than the cache time
		################################################################################
		if test -f "$(webRoot)/cacheDelay.cfg";then
			echo "Loading cache settings..."
			cacheDelay=$(cat "$(webRoot)/cacheDelay.cfg")
		else
			echo "Using default cache settings..."
			cacheDelay="14"
		fi
		echo "Checking cache for files older than ${cacheDelay} Days"
		# delete files older than x days
		echo "Checking for cache files in $(webRoot)/RESOLVER-CACHE/"
		if test -d "$(webRoot)/RESOLVER-CACHE/";then
			find "$(webRoot)/RESOLVER-CACHE/" -type f -mtime +"$cacheDelay" -exec rm -v {} \;
		fi
		echo "Checking for cache files in $(webRoot)/M3U-CACHE/"
		# delete the m3u cache
		if test -d "$(webRoot)/M3U-CACHE/";then
			find "$(webRoot)/M3U-CACHE/" -type f -mtime +"$cacheDelay" -name '*.index' -exec rm -v {} \;
		fi
		echo "Checking for cache files in $(webRoot)/new/"
		# delete the new episodes cache
		if test -d "$(webRoot)/new/";then
			find "$(webRoot)/new/" -type f -mtime +"$cacheDelay" -name '*.index' -exec rm -v {} \;
		fi
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ];then
		echo "########################################################################"
		echo "# 2web CLI for administration"
		echo "# Copyright (C) 2021  Carl J Smith"
		echo "#"
		echo "# This program is free software: you can redistribute it and/or modify"
		echo "# it under the terms of the GNU General Public License as published by"
		echo "# the Free Software Foundation, either version 3 of the License, or"
		echo "# (at your option) any later version."
		echo "#"
		echo "# This program is distributed in the hope that it will be useful,"
		echo "# but WITHOUT ANY WARRANTY; without even the implied warranty of"
		echo "# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the"
		echo "# GNU General Public License for more details."
		echo "#"
		echo "# You should have received a copy of the GNU General Public License"
		echo "# along with this program.  If not, see <http://www.gnu.org/licenses/>."
		echo "########################################################################"
		echo "HELP INFO"
		echo "This is the 2web administration and update program."
		echo "To return to this menu use '2web help'"
		echo "Other commands are listed below."
		echo ""
		echo "update"
		echo "  This will update the m3u file used to make the website."
		echo ""
		echo "cron"
		echo "  Run the cron check script."
		echo ""
		echo "reset"
		echo "  Reset the cache."
		echo ""
		echo "webgen"
		echo "	Build the website from the m3u generated."
		echo ""
		echo "libary"
		echo "	Download the latest version of the hls.js libary for use."
		echo "########################################################################"
		# this is also ran on import
	else
		# this is the default option to be ran without arguments
		main --help
	fi
}
################################################################################
main "$@"
exit
