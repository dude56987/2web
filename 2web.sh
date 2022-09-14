#! /bin/bash
########################################################################
# 2web is the CLI interface for managing the 2web server
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
########################################################################
function STOP(){
	echo ">>>>>>>>>>>DEBUG STOPPER<<<<<<<<<<<" #DEBUG DELETE ME
	read -r #DEBUG DELETE ME
}
########################################################################
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
}
################################################################################
function drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
}
################################################################################
function debugCheck(){
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
function getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# add the version to the sum to update old versions
	totalList="$totalList$(cat /usr/share/2web/version.cfg)"
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
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
########################################################################
function enableApacheServer(){
	# enable the apache config
	linkFile "/etc/apache2/conf-available/0-2web-ports.conf" "/etc/apache2/conf-enabled/0-2web-ports.conf"
	linkFile "/etc/apache2/sites-available/0-2web-website.conf" "/etc/apache2/sites-enabled/0-2web-website.conf"
	linkFile "/etc/apache2/sites-available/0-2web-website-SSL.conf" "/etc/apache2/sites-enabled/0-2web-website-SSL.conf"
	linkFile "/etc/apache2/sites-available/0-2web-website-compat.conf" "/etc/apache2/sites-enabled/0-2web-website-compat.conf"
}
########################################################################
function disableApacheServer(){
	rm -v "/etc/apache2/conf-enabled/0-2web-ports.conf"
	rm -v "/etc/apache2/sites-enabled/0-2web-website.conf"
	rm -v "/etc/apache2/sites-enabled/0-2web-website-SSL.conf"
	rm -v "/etc/apache2/sites-enabled/0-2web-website-compat.conf"
}
########################################################################
function enableCronJob(){
	cp -v "/usr/share/2web/cron" "/etc/cron.d/2web"
}
########################################################################
function disableCronJob(){
	rm -v "/etc/cron.d/2web"
}
########################################################################
function update2web(){
	echo "Updating 2web..."
	# build 2web common web interface this should be ran after each install to update main web components on which modules depend
	webDirectory="$(webRoot)"
	# if the build date of the software has changed then update the generated css themes for the site
	if checkFileDataSum "$webDirectory" "/usr/share/2web/buildDate.cfg";then
		themeColors=$(find "/usr/share/2web/theme-templates/" -type f -name 'color-*.css')
		#themeColors=$(echo "$themeColors" | sed -z "s/$/\"/g" | sed -z "s/^/'/g" | sed -z "s/\n/ /g")
		themeColors=$(echo "$themeColors" | sed -z "s/\n/ /g")
		themeFonts=$(find "/usr/share/2web/theme-templates/" -type f -name 'font-*.css')
		#themeFonts=$(echo "$themeFonts" | sed -z "s/$/\"/g" | sed -z "s/^/'/g" | sed -z "s/\n/ /g")
		themeFonts=$(echo "$themeFonts" | sed -z "s/\n/ /g")
		themeMods=$(find "/usr/share/2web/theme-templates/" -type f -name 'mod-*.css')
		#themeMods=$(echo "$themeMods" | sed -z "s/$/\"/g" | sed -z "s/^/'/g" | sed -z "s/\n/ /g")
		themeMods=$(echo "$themeMods" | sed -z "s/\n/ /g")
		themeBases=$(find "/usr/share/2web/theme-templates/" -type f -name 'base-*.css')
		#themeBases=$(echo "$themeBases" | sed -z "s/$/\"/g" | sed -z "s/^/'/g" | sed -z "s/\n/ /g")
		themeBases=$(echo "$themeBases" | sed -z "s/\n/ /g")
		# build the custom stylesheets if they need to be built
		for themeColor in $themeColors;do
			tempPathColor=$(echo "$themeColor" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1 | sed "s/color-//g" )
			for themeFont in $themeFonts;do
				tempPathFont=$(echo "$themeFont" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1  | sed "s/font-//g" )
				for themeMod in $themeMods;do
					tempPathMod=$(echo "$themeMod" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1  | sed "s/mod-//g" )
					for themeBase in $themeBases;do
						tempPathBase=$(echo "$themeBase" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1  | sed "s/base-//g" )
						#tempThemeName="${tempPathColor}-${tempPathFont}-${tempPathMod}-${tempPathBase}"
						tempThemeName="${tempPathBase}-${tempPathColor}-${tempPathFont}-${tempPathMod}"
						#ALERT "Building theme at /usr/share/2web/themes/$tempThemeName.css"
						#addToLog "DEBUG" "Building theme at /usr/share/2web/themes/$tempThemeName.css" "$logPagePath"
						# build the theme
						{
							if test -f "$themeColor";then
								cat "$themeColor"
							fi
							if test -f "$themeFont";then
								cat "$themeFont"
							fi
							if test -f "$themeMod";then
								cat "$themeMod"
							fi
							if test -f "$themeBase";then
								cat "$themeBase"
							fi
						} > "/usr/share/2web/themes/$tempThemeName.css"
					done
				done
			done
		done
		# update the timer
		touch /var/cache/2web/web/themeGen.cfg
	fi
	# make sure the directories exist and have correct permissions, also link stylesheets
	createDir "$webDirectory"
	createDir "$webDirectory/new/"
	createDir "$webDirectory/web_cache/"
	createDir "$webDirectory/random/"
	createDir "$webDirectory/shows/"
	createDir "$webDirectory/movies/"
	createDir "$webDirectory/kodi/"
	createDir "$webDirectory/settings/"
	createDir "$webDirectory/sums/"
	createDir "$webDirectory/views/"
	createDir "$webDirectory/backups/"

	# create config files if they do not exist
	if ! test -f /etc/2web/cacheNewEpisodes.cfg;then
		# by default disable caching of new episodes
		echo "no" > /etc/2web/cacheNewEpisodes.cfg
		chown www-data:www-data /etc/2web/cacheNewEpisodes.cfg
	fi

	################################################################################
	# Link website scripts into website directory to build a functional site
	# - The php web interface
	#  - These scripts limit libary checking for interface updates to once per 2 hours
	#  - Adding users to enable password protection of site
	#  - Is only available from the https version of the website
	# - The php resolver scripts
	#  - These scripts allow for kodi to play .strm files though youtube-dl
	#  - Generate m3u files to allow android phones to share the media to any video player
	################################################################################

	enableApacheServer

	enableCronJob

	# admin control file
	linkFile "/usr/share/2web/settings/admin.php" "$webDirectory/settings/admin.php"
	# settings interface files
	linkFile "/usr/share/2web/settings/modules.php" "$webDirectory/settings/index.php"

	linkFile "/usr/share/2web/settings/modules.php" "$webDirectory/settings/modules.php"
	linkFile "/usr/share/2web/settings/serverServices.php" "$webDirectory/settings/serverServices.php"
	linkFile "/usr/share/2web/settings/radio.php" "$webDirectory/settings/radio.php"
	linkFile "/usr/share/2web/settings/tv.php" "$webDirectory/settings/tv.php"
	linkFile "/usr/share/2web/settings/iptv_blocked.php" "$webDirectory/settings/iptv_blocked.php"
	linkFile "/usr/share/2web/settings/nfo.php" "$webDirectory/settings/nfo.php"
	linkFile "/usr/share/2web/settings/comics.php" "$webDirectory/settings/comics.php"
	linkFile "/usr/share/2web/settings/graphs.php" "$webDirectory/settings/graphs.php"
	linkFile "/usr/share/2web/settings/comicsDL.php" "$webDirectory/settings/comicsDL.php"
	linkFile "/usr/share/2web/settings/cache.php" "$webDirectory/settings/cache.php"
	linkFile "/usr/share/2web/settings/system.php" "$webDirectory/settings/system.php"
	linkFile "/usr/share/2web/settings/weather.php" "$webDirectory/settings/weather.php"
	linkFile "/usr/share/2web/settings/ytdl2nfo.php" "$webDirectory/settings/ytdl2nfo.php"
	linkFile "/usr/share/2web/settings/music.php" "$webDirectory/settings/music.php"
	linkFile "/usr/share/2web/settings/settingsHeader.php" "$webDirectory/settings/settingsHeader.php"
	linkFile "/usr/share/2web/settings/logout.php" "$webDirectory/logout.php"
	# add the manuals page
	linkFile "/usr/share/2web/templates/manuals.php" "$webDirectory/settings/manuals.php"
	# help/info docs
	linkFile "/usr/share/2web/templates/help.php" "$webDirectory/help.php"
	# caching resolvers
	linkFile "/usr/share/2web/ytdl-resolver.php" "$webDirectory/ytdl-resolver.php"
	linkFile "/usr/share/2web/m3u-gen.php" "$webDirectory/m3u-gen.php"
	# error documents
	linkFile "/usr/share/2web/templates/404.php" "$webDirectory/404.php"
	linkFile "/usr/share/2web/templates/403.php" "$webDirectory/403.php"
	linkFile "/usr/share/2web/templates/401.php" "$webDirectory/401.php"
	# global javascript libary
	linkFile "/usr/share/2web/2web.js" "$webDirectory/2web.js"
	# link homepage
	linkFile "/usr/share/2web/templates/home.php" "$webDirectory/index.php"
	# link stats script
	linkFile "/usr/share/2web/templates/stats.php" "$webDirectory/stats.php"
	# link the fortune script
	linkFile "/usr/share/2web/templates/fortune.php" "$webDirectory/fortune.php"
	# link the movies and shows index
	linkFile "/usr/share/2web/templates/movies.php" "$webDirectory/movies/index.php"
	linkFile "/usr/share/2web/templates/shows.php" "$webDirectory/shows/index.php"
	# add the new index
	linkFile "/usr/share/2web/templates/new.php" "$webDirectory/new/index.php"
	# add the random index
	linkFile "/usr/share/2web/templates/random.php" "$webDirectory/random/index.php"
	# link lists these can be built and rebuilt during libary update
	# copy over the favicon
	linkFile "/usr/share/2web/favicon_default.png" "$webDirectory/favicon.png"
	################################################################################
	# build the login users file
	if [ $( find "/etc/2web/users/" -type f -name "*.cfg" | wc -l ) -gt 0 ];then
		# if there are any users
		#linkFile "/usr/share/2web/templates/_htaccess" "$webDirectory/.htaccess"
		linkFile "/usr/share/2web/templates/_htaccess" "$webDirectory/settings/.htaccess"
		linkFile "/usr/share/2web/templates/_htaccess" "$webDirectory/backups/.htaccess"
		# copy server users to administrator list
		cat /etc/2web/users/*.cfg > "/var/cache/2web/htpasswd.cfg"
	else
		# if there are no users set in the cfg remove the .htaccess file
		if test -f "$webDirectory/.htaccess";then
			rm "$webDirectory/.htaccess"
		fi
		if test -f "$webDirectory/settings/.htaccess";then
			rm "$webDirectory/settings/.htaccess"
		fi
		if test -f "$webDirectory/backups/.htaccess";then
			rm "$webDirectory/backups/.htaccess"
		fi
	fi
	createDir "$webDirectory/RESOLVER-CACHE/"

	# update the certificates
	updateCerts

	# check the scheduler and make sure www-data is allowed to use the at command for php resolver
	if test -f "/etc/at.deny";then
		# the file exists check for the www-data line
		if grep -q "www-data" "/etc/at.deny";then
			# remove www-data from the deny file for scheduler
			data=$(grep --invert-match "www-data" "/etc/at.deny")
			echo "$data" > "/etc/at.deny"
		fi
	fi
	# build the fortune if the config is set
	if cacheCheck "$webDirectory/fortune.index" "1";then
		# write the fortune for this processing run...
		if test -f /usr/games/fortune;then
			#todaysFortune=$(/usr/games/fortune -a | txt2html --extract)
			# replace tabs with spaces
			todaysFortune=$(/usr/games/fortune -a | sed "s/\t/ /g")
			echo "$todaysFortune" > "$webDirectory/fortune.index"
		fi
	fi

	# install the php streaming script
	#ln -s "/usr/share/2web/stream.php" "$webDirectory/stream.php"
	#linkFile "/usr/share/2web/transcode.php" "$webDirectory/transcode.php"

	# link the randomFanart.php script
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/randomFanart.php"
	linkFile "$webDirectory/randomFanart.php" "$webDirectory/shows/randomFanart.php"
	linkFile "$webDirectory/randomFanart.php" "$webDirectory/movies/randomFanart.php"

	# link randomPoster.php
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/randomPoster.php"
	linkFile "$webDirectory/randomPoster.php" "$webDirectory/shows/randomPoster.php"
	linkFile "$webDirectory/randomPoster.php" "$webDirectory/movies/randomPoster.php"

	# link the stylesheet based on the chosen theme
	if ! test -f /etc/2web/theme.cfg;then
		# the default theme is gray
		echo "Simple-Gray-OpenDyslexic-round.css" > "/etc/2web/theme.cfg"
		chown www-data:www-data "/etc/2web/theme.cfg"
	fi
	# load the chosen theme
	theme=$(cat "/etc/2web/theme.cfg")
	# link the theme and overwrite if another theme is chosen
	ln -sf "/usr/share/2web/themes/$theme" "$webDirectory/style.css"
	# build the homepage stats and link the homepage
	buildHomePage "$webDirectory"
}
########################################################################
backupSettings(){
	# create a compressed backup of the server settings
	createDir "$(webRoot)/backups/"
	set -x
	zip -9 -r "$(webRoot)/backups/$(date).zip" "/etc/2web/"
	set +x
}
########################################################################
restoreSettings(){
	# unzip the stored settings file given
	settingsFile=$1
	createDir "$(webRoot)/backups/$(date)/"
	set -x
	ALERT "Use the below command to restore a backup of the settings on the server."
	echo "unzip -x '$settingsFile' -d '/etc/2web/'"
	set +x
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
		/usr/bin/weather2web
		/usr/bin/graph2web
		/usr/bin/comic2web
		/usr/bin/nfo2web
		/usr/bin/music2web
		/usr/bin/iptv2web
		rebootCheck
	elif [ "$1" == "-p" ] || [ "$1" == "--parallel" ] || [ "$1" == "parallel" ];then
		ALERT "================================================================================"
		ALERT "PARALLEL MODE"
		ALERT "================================================================================"
		totalCPUS=$(grep "processor" "/proc/cpuinfo" | wc -l)
		# parllelize the update processes
		###########################
		# update main components
		# - all processes are locked so conflicts will not arise from launching this process multuple times
		update2web
		# update the on-demand downloads
		/usr/bin/ytdl2nfo &
		waitQueue 0.5 "$totalCPUS"
		# update weather
		/usr/bin/weather2web &
		waitQueue 0.5 "$totalCPUS"
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web --parallel &
		waitQueue 0.5 "$totalCPUS"
		/usr/bin/graph2web &
		waitQueue 0.5 "$totalCPUS"
		/usr/bin/iptv2web &
		waitQueue 0.5 "$totalCPUS"
		/usr/bin/comic2web --parallel &
		waitQueue 0.5 "$totalCPUS"
		/usr/bin/music2web --parallel &
		waitQueue 0.5 "$totalCPUS"
		blockQueue 1
		while 1;do
			sleep 1
			if test -f /tmp/comic2web.active;then
				sleep 1
			elif test -f /tmp/iptv2web.active;then
				sleep 1
			elif test -f /tmp/nfo2web.active;then
				sleep 1
			elif test -f /tmp/weather2web.active;then
				sleep 1
			elif test -f /tmp/music2web.active;then
				sleep 1
			elif test -f /tmp/graph2web.active;then
				sleep 1
			elif test -f /tmp/ytdl2nfo.active;then
				sleep 1
			else
				# this means all processes have completed
				# break the loop and run the reboot check
				break
			fi
		done
		# run the reboot check after all modules have finished running
		rebootCheck
	elif [ "$1" == "-I" ] || [ "$1" == "--iptv" ] || [ "$1" == "iptv" ];then
		update2web
		/usr/bin/iptv2web
		rebootCheck
	elif [ "$1" == "-Y" ] || [ "$1" == "--ytdl" ] || [ "$1" == "ytdl" ];then
		update2web
		/usr/bin/ytdl2nfo
		rebootCheck
	elif [ "$1" == "-N" ] || [ "$1" == "--nfo" ] || [ "$1" == "nfo" ];then
		update2web
		/usr/bin/nfo2web
		rebootCheck
	elif [ "$1" == "-c" ] || [ "$1" == "--comic" ] || [ "$1" == "comic" ];then
		update2web
		/usr/bin/comic2web
		rebootCheck
	elif [ "$1" == "-w" ] || [ "$1" == "--weather" ] || [ "$1" == "weather" ];then
		update2web
		/usr/bin/weather2web
		rebootCheck
	elif [ "$1" == "-m" ] || [ "$1" == "--music" ] || [ "$1" == "music" ];then
		update2web
		/usr/bin/music2web
		rebootCheck
	elif [ "$1" == "-g" ] || [ "$1" == "--graph" ] || [ "$1" == "graph" ];then
		update2web
		/usr/bin/graph2web
		rebootCheck
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ];then
		# update main components
		update2web
		# update the metadata and build webpages for all generators
		/usr/bin/nfo2web update
		/usr/bin/iptv2web update
		/usr/bin/comic2web update
		/usr/bin/weather2web
		/usr/bin/music2web
		rebootCheck
	elif [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ];then
		update2web
		# update the website content
		/usr/bin/nfo2web webgen
		/usr/bin/iptv2web webgen
		/usr/bin/comic2web webgen
		# weather2web only generates website data
		/usr/bin/weather2web
		/usr/bin/music2web webgen
		rebootCheck
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ];then
		# upgrade packages related to operation of webserver
		/usr/bin/nfo2web upgrade
		/usr/bin/iptv2web upgrade
		/usr/bin/comic2web upgrade
		/usr/bin/music2web upgrade
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ];then
		# remove all genereated web content
		/usr/bin/nfo2web reset
		/usr/bin/comic2web reset
		/usr/bin/iptv2web reset
		/usr/bin/weather2web reset
		/usr/bin/music2web reset
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ];then
		# remove all website content and disable the website
		rm -rv /var/cache/2web/web/*
		disableApacheServer
		disableCronJob
	elif [ "$1" == "-rc" ] || [ "$1" == "--reboot-check" ] || [ "$1" == "rebootcheck" ];then
		rebootCheck
	elif [ "$1" == "-b" ] || [ "$1" == "--backup" ] || [ "$1" == "backup" ] ;then
		backupSettings
	elif [ "$1" == "-r" ] || [ "$1" == "--restore" ] || [ "$1" == "restore" ] ;then
		restoreSettings "$2"
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
			find "$(webRoot)/RESOLVER-CACHE/" -type d -mtime +"$cacheDelay" -exec rm -rv {} \;
		fi
		echo "Checking for cache files in $(webRoot)/M3U-CACHE/"
		# delete the m3u cache
		if test -d "$(webRoot)/M3U-CACHE/";then
			find "$(webRoot)/M3U-CACHE/" -type f -mtime +"$cacheDelay" -name '*.index' -exec rm -v {} \;
		fi
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ];then
		cat /usr/share/2web/help/2web.txt
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "2web Version: #"
		cat /usr/share/2web/version.cfg
		echo -n "2web Version Publish Date: "
		cat /usr/share/2web/versionDate.cfg
	else
		# update main components
		# - this builds the base site without anything enabled
		update2web
		# this is the default option to be ran without arguments
		#main --help
	fi
	# show the server link at the bottom of the interface
	drawLine
	echo "http://$(hostname).local:80/"
	drawLine
	echo "http://$(hostname).local:80/new/"
	drawLine
	echo "http://$(hostname).local:80/system.php"
	drawLine
}
################################################################################
main "$@"
exit
