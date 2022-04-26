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
########################################################################
rebootCheck(){
	echo "Checking if it is time to reboot the system..."
	# check the reboot time
	if test -f /etc/2web/rebootTime.cfg;then
		rebootTime=$(cat /etc/2web/rebootTime.cfg)
	else
		rebootTime="4"
		echo "$rebootTime" > /etc/2web/rebootTime.cfg
	fi
	currentTime=$(date "+%H")
	echo "Reboot Time ?= Current Time"
	echo "$rebootTime ?= $currentTime"
	if [ "disabled" == "$rebootTime" ];then
		return
	elif [ $currentTime -gt 24 ];then
		return
	elif [ $currentTime -lt 0 ];then
		return
	else
		# this is a usable reboot hour check if it is available
		if [ "$currentTime" -eq "$rebootTime" ];then
			echo -n "Rebooting"
			# 5 second delay
			sleep 1
			echo -n "."
			sleep 1
			echo -n "."
			sleep 1
			echo -n "."
			sleep 1
			echo -n "."
			sleep 1
			echo -n "."
			# reboot the system
			/usr/sbin/reboot
		fi
	fi
}
################################################################################
main(){
	if [ "$1" == "-a" ] || [ "$1" == "--all" ] || [ "$1" == "all" ];then
		# update main components
		update2web
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web
		/usr/bin/iptv2web
		# update nfo2web again to check for stats
		/usr/bin/nfo2web
		/usr/bin/comic2web
		/usr/bin/nfo2web
		rebootCheck
	elif [ "$1" == "-p" ] || [ "$1" == "--parallel" ] || [ "$1" == "parallel" ];then
		# parllelize the processes
		###########################
		# update main components
		# - all processes are locked so conflicts will not arise from launching this process multuple times
		update2web
		# update the on-demand downloads
		/usr/bin/ytdl2nfo &
		sleep 30
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web &
		sleep 10
		/usr/bin/iptv2web &
		/usr/bin/comic2web &
		while 1;do
			if test -f /tmp/comic2web.active;then
				sleep 1
			elif test -f /tmp/iptv2web.active;then
				sleep 1
			elif test -f /tmp/nfo2web.active;then
				sleep 1
			else
				# break the loop and run the reboot check
				break
			fi
		done
		# run the reboot check after all modules have finished running
		rebootCheck
	elif [ "$1" == "-I" ] || [ "$1" == "--iptv" ] || [ "$1" == "iptv" ];then
		/usr/bin/iptv2web
		rebootCheck
	elif [ "$1" == "-Y" ] || [ "$1" == "--ytdl" ] || [ "$1" == "ytdl" ];then
		/usr/bin/ytdl2nfo
		rebootCheck
	elif [ "$1" == "-N" ] || [ "$1" == "--nfo" ] || [ "$1" == "nfo" ];then
		/usr/bin/nfo2web
		rebootCheck
	elif [ "$1" == "-C" ] || [ "$1" == "--comic" ] || [ "$1" == "comic" ];then
		/usr/bin/comic2web
		rebootCheck
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ];then
		# update main components
		update2Web
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web update
		/usr/bin/iptv2web update
		/usr/bin/comic2web update
		rebootCheck
	elif [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ];then
		# update the website content
		/usr/bin/nfo2web webgen
		/usr/bin/iptv2web webgen
		/usr/bin/comic2web webgen
		rebootCheck
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
	elif [ "$1" == "-rc" ] || [ "$1" == "--reboot-check" ] || [ "$1" == "rebootcheck" ];then
		rebootCheck
	elif [ "$1" == "-cc" ] || [ "$1" == "--clean-cache" ] || [ "$1" == "cleancache" ] ;then
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
		cat /usr/share/2web/help/2web.txt
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "2web Version: #"
		cat /usr/share/2web/version.cfg
		echo -n "2web Version Publish Date: "
		cat /usr/share/2web/versionDate.cfg
	else
		# this is the default option to be ran without arguments
		main --help
	fi
}
################################################################################
main "$@"
exit
