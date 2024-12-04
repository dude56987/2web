#! /bin/bash
########################################################################
# 2web is the CLI interface for managing the 2web server
# Copyright (C) 2024  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under  the terms of the GNU General Public License as published by
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
################################################################################
function debugCheck(){
	if [ -f /etc/2web/debug.enabled ];then
		# if debug mode is enabled show execution
		set -x
	else
		if ! [ -d /etc/2web/ ];then
			# create dir if one does not exist
			mkdir -p /etc/2web/
		fi
		if ! [ -f /etc/2web/debug.disabled ];then
			# create debug flag file disabed, if it does not exist
			touch /etc/2web/debug.disabled
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
########################################################################
function enableApacheServer(){
	rm -v "/etc/apache2/conf-enabled/0000-default.conf"
	rm -v "/etc/apache2/conf-enabled/000-default.conf"
	rm -v "/etc/apache2/conf-enabled/00-default.conf"
	rm -v "/etc/apache2/conf-enabled/0-default.conf"
	# disable default site by removing symlink
	rm -v "/etc/apache2/sites-enabled/0000-default.conf"
	rm -v "/etc/apache2/sites-enabled/000-default.conf"
	rm -v "/etc/apache2/sites-enabled/00-default.conf"
	rm -v "/etc/apache2/sites-enabled/0-default.conf"
	# copy over the config files
	linkFile "/etc/apache2/sites-available/0000-2web-website.conf" "/etc/apache2/sites-enabled/0000-2web-website.conf"
	linkFile "/etc/apache2/sites-available/0000-2web-website-SSL.conf" "/etc/apache2/sites-enabled/0000-2web-website-SSL.conf"
	# restart apache to apply changes
	apache2ctl restart
}
########################################################################
function disableApacheServer(){
	# remove 2web apache configs
	rm -v "/etc/apache2/conf-enabled/0000-2web-ports.conf"
	rm -v "/etc/apache2/sites-enabled/0000-2web-website.conf"
	rm -v "/etc/apache2/sites-enabled/0000-2web-website-SSL.conf"
	rm -v "/etc/apache2/sites-enabled/0000-2web-website-compat.conf"
	# renenable the default apache config found
	if test -f "/etc/apache2/sites-available/0000-default.conf";then
		ln -s "/etc/apache2/sites-available/0000-default.conf" "/etc/apache2/sites-enabled/0000-default.conf"
	elif test -f "/etc/apache2/sites-available/000-default.conf";then
		ln -s "/etc/apache2/sites-available/000-default.conf" "/etc/apache2/sites-enabled/000-default.conf"
	elif test -f "/etc/apache2/sites-available/00-default.conf";then
		ln -s "/etc/apache2/sites-available/00-default.conf" "/etc/apache2/sites-enabled/00-default.conf"
	elif test -f "/etc/apache2/sites-available/0-default.conf";then
		ln -s "/etc/apache2/sites-available/0-default.conf" "/etc/apache2/sites-enabled/0-default.conf"
	fi
	# restart apache to apply changes
	apache2ctl restart
}
########################################################################
function enableCronJob(){
	linkFile "/usr/share/2web/cron" "/etc/cron.d/2web"
}
########################################################################
function disableCronJob(){
	rm -v "/etc/cron.d/2web"
}
########################################################################
function checkActiveStatusForGraph(){
	graphName="$1"
	webDirectory="$2"
	if test -f "$webDirectory/${graphName}.activeGraph";then
		rm "$webDirectory/${graphName}.activeGraph"
		echo "1"
	elif test -f "$webDirectory/${graphName}.active";then
		echo "1"
	else
		echo "0"
	fi
}
########################################################################
function recordActivityGraph(){
	webDirectory=$(webRoot)
	# use loadmodules for unified module list
	moduleNames=$(loadModules)
	# record a round robin CSV database of active services in 30 minute intervals
	if cacheCheckMin "/var/cache/2web/activityGraphData.index" 30;then
		# reset the line string to be generated
		lineData=""
		for module in $moduleNames;do
			module="$(echo "$module" | cut -d'=' -f1)"
			# store the current activity status in the graph
			lineData="$lineData$(checkActiveStatusForGraph "$module" "$webDirectory"),"
		done
		# write the line but remove endline comma
		echo "$lineData" | sed "s/,$//g" >> "/var/cache/2web/activityGraphData.index"
		# limit log to last 36 entries, this is because this log is updated every 30 minutes
		# - You can not > pipe a file directly with tail, so it is stored in memory fist
		# - 48 should be 24 hours worth of stats
		tempDatabase=$(tail -n 48 "/var/cache/2web/activityGraphData.index")
		# write the trimmed database
		echo "$tempDatabase" > "/var/cache/2web/activityGraphData.index"
	fi
}
########################################################################
function buildFakeActivityGraph(){
	totalModules=$(loadModules | wc -l)
	{
		for index in $(seq 48);do
			for index in $(seq $totalModules);do
				# randomize anwser
				if [[ $(( $RANDOM % 2 )) -eq 0 ]];then
					# build each line
					echo -n "1,"
				else
					echo -n "0,"
				fi
			done
			# randomize anwser
			if [[ $(( $RANDOM % 2 )) -eq 0 ]];then
				echo "1"
			else
				echo "0"
			fi
		done
	} > "/var/cache/2web/activityGraphData.index"
}
########################################################################
function buildFullFakeActivityGraph(){
	totalModules=$(loadModules | wc -l)
	{
		for index in $(seq 48);do
			line=""
			for index in $(seq $totalModules);do
				# build each line
				line="${line}1,"
			done
			# remove trailing comma in fake graph lines
			echo "$line" | sed "s/,$//g"
		done
	} > "/var/cache/2web/activityGraphData.index"
}
########################################################################
function loadModules(){
	# List the modules as lines with thier color and number data
	#
	# RETURN STDOUT

	# setup the modules and thier colors in the graph
	# - this is used by the loops that draw the graph elements in SVG
	moduleNames=$'2web=black=1\n'
	moduleNames=$moduleNames$'queue2web=white=2\n'
	moduleNames=$moduleNames$'kodi2web=aqua=3\n'
	moduleNames=$moduleNames$'nfo2web=red=4\n'
	moduleNames=$moduleNames$'ytdl2nfo=teal=5\n'
	moduleNames=$moduleNames$'rss2nfo=slateblue=6\n'
	moduleNames=$moduleNames$'music2web=yellow=7\n'
	moduleNames=$moduleNames$'wiki2web=green=8\n'
	moduleNames=$moduleNames$'comic2web=orange=9\n'
	moduleNames=$moduleNames$'ai2web=lime=10\n'
	moduleNames=$moduleNames$'git2web=purple=11\n'
	moduleNames=$moduleNames$'portal2web=seagreen=12\n'
	moduleNames=$moduleNames$'graph2web=chocolate=13\n'
	moduleNames=$moduleNames$'iptv2web=greenyellow=14\n'
	moduleNames=$moduleNames$'epg2web=olive=15\n'
	moduleNames=$moduleNames$'weather2web=hotpink=16\n'
	moduleNames=$moduleNames$'php2web=blue=17\n'

	# Display the data
	echo -n "$moduleNames"
}
########################################################################
function buildActivityGraph(){
	# build a 24 hour graph with each block repsenting 30 minutes
	graphData=$( cat "/var/cache/2web/activityGraphData.index" )
	index=0
	barWidth=20
	# generated the paths
	createDir "/var/cache/2web/generated/graphs/"
	generatedSvgPath="/var/cache/2web/generated/graphs/2web_activity_day.svg"
	generatedPngPath="/var/cache/2web/generated/graphs/2web_activity-day.png"
	webPath="/var/cache/2web/web/activityGraph.png"
	graphHeightCounter=0
	graphHeaderData=""

	# set ifs to newlines for next loop
	IFSBACKUP=$IFS
	IFS=$'\n'

	# setup the modules and thier colors in the graph
	# - this is used by the loops that draw the graph elements in SVG
	moduleNames="$(loadModules)"

	ALERT "MODULE NAMES ='$moduleNames'"

	# storage varable for active modules
	enabledModules=""
	# figure out enabled modules and build header text
	for module in $moduleNames;do
		module="$(echo "$module" | cut -d'=' -f1)"
		# figure out enabled modules and build header text
		if returnModStatus "$module";then
			#nfo2webHeight=$graphHeightCounter
			graphHeightCounter=$(( graphHeightCounter + 1 ))
			graphHeaderData="$graphHeaderData<text x=\"$(( 0 ))\" y=\"$(( barWidth * ( graphHeightCounter ) ))\" font-size=\"$barWidth\" style=\"fill:black;stroke:white;\" >$module</text>\n"
			# add the module to the enabled modules variable
			enabledModules="$enabledModules$module "
		fi
	done
	# remove spaces at the end of lines
	enabledModules=$(echo "$enabledModules" | sed "s/ $//g")

	ALERT "ENABLED MODULES ='$enabledModules'"

	# add to the height for the time codes
	graphHeightCounter=$(( graphHeightCounter + 3 ))

	textGap=$(( barWidth * 8 ))
	graphHeight=$(( (barWidth * graphHeightCounter) + (barWidth / 4) ))
	graphWidth=$((textGap + (barWidth * 48) ))

	{
		# - add the graph header data after the header
		# - while building the header it figures out the height of the graph based on enabled modules so it must be
		#   added after the text headers are added as guidelines for the graph
		echo -e "<svg preserveAspectRatio=\"xMidYMid meet\" viewBox=\"0 0 $graphWidth $graphHeight\" >\n$graphHeaderData"

		for line in $graphData;do
			index=$(( index + 1 ))

			# for every 30 min write the activity to a graph
			graphX=$(( ( $index * $barWidth ) ))
			# reset height counter
			graphHeightCounter=0

			# for each active module generate graph data
			for module in $moduleNames;do
				moduleColor="$(echo "$module" | cut -d'=' -f2)"
				moduleNumber="$(echo "$module" | cut -d'=' -f3)"
				module="$(echo "$module" | cut -d'=' -f1)"

				if echo "$enabledModules" | grep -q "$module";then
					moduleStatus=$(echo "$line" | cut -d',' -f${moduleNumber} )
					if [[ 1 -eq $moduleStatus ]];then
						echo "<rect x=\"$(( textGap + graphX - barWidth ))\" y=\"$(( (barWidth * graphHeightCounter ) ))\" width=\"$(( barWidth ))\" height=\"$barWidth\" style=\"fill:$moduleColor;stroke:white;stroke-width:1\" />"
					else
						echo "<rect x=\"$(( textGap + graphX - barWidth ))\" y=\"$(( (barWidth * graphHeightCounter ) ))\" width=\"$(( barWidth ))\" height=\"$barWidth\" style=\"fill:none;stroke:white;stroke-width:1\" />"
					fi
					graphHeightCounter=$((graphHeightCounter + 1))
				fi
			done

		done
		# write the times on the bottom of the graph
		timeCounterHours="$(date "+%H")"
		timeCounterMinutes="$(date "+%M")"
		if [[ $timeCounterMinutes -gt 30 ]];then
			timeCounter="$timeCounterHours.5"
		else
			timeCounter="$timeCounterHours.0"
		fi
		index="0"
		for line in $graphData;do
			index=$(( index + 1 ))
			graphX=$(( ( $index * $barWidth ) ))
			#
			x=$(( textGap + graphX - barWidth ))
			y=$(( (barWidth * graphHeightCounter ) ))
			#
			timeHour=$(echo "$timeCounter" | sed "s/\.0//g")
			timeHour=$(echo "$timeHour" | sed "s/\.5//g")

			# if the timecounter is greater than 24
			timeCounter=$(bc <<< "$timeCounter % 24")
			#timeCounter="$(bc <<< "$timeCounter % 12")"

			# add am/pm and adjust time hour code
			#if [[ $timeHour -eq 0 ]];then
			#	timeText="$timeCounter PM"
			#elif [[ $timeCounter -eq 0 ]];then
			#	timeText="00.0 PM"
			#elif [[ $timeHour -gt 12 ]];then
			#	timeText="$timeCounter PM"
			#elif [[ $timeHour -lt 0 ]];then
			#	timeText="$timeCounter PM"
			#else
			#	timeText="$timeCounter AM"
			#fi

			# set the minutes
			if echo "$timeCounter" | grep -q "\.0";then
				timeText=$(echo "$timeCounter" | sed "s/\.0/:00/g")
			elif echo "$timeCounter" | grep -q "\.5";then
				timeText=$(echo "$timeCounter" | sed "s/\.5/:30/g")
			fi

			# for numbers less than ten add the zero
			if [[ $( echo "$timeText" | cut -d':' -f1 ) -eq 0 ]];then
				timeText="24:00"
			elif [[ $( echo "$timeText" | cut -d':' -f1 ) -lt 10 ]];then
				timeText="0$timeText"
			fi

			# write the time code
			echo "<text x=\"$x\" y=\"$y\" font-size=\"$barWidth\" style=\"fill:black;stroke:white;stroke-width:1\" transform=\"rotate(90,$x,$y)\" >$timeText</text>\n"
			# decrement the time counter to make it go back an hour
			timeCounter=$(bc <<< "$timeCounter + 0.5")
		done

		echo "</svg>"
	} > "$generatedSvgPath"

	# render graph as image file
	convert -background none -quality 100 -font "OpenDyslexic-Bold" "$generatedSvgPath" "$generatedPngPath"
	# build the web path
	linkFile "$generatedPngPath" "$webPath"
	IFS=$IFSBACKUP
}
########################################################################
function update2web(){
	lockProc "2web"
	echo "Updating 2web..."
	# build 2web common web interface this should be ran after each install to update main web components on which modules depend
	webDirectory="$(realWebRoot)"
	downloadDirectory="$(downloadRoot)"
	generatedDirectory="$(generatedRoot)"

	createDir "$webDirectory"
	createDir "$downloadDirectory"
	createDir "$generatedDirectory"
	createDir "/etc/2web/mod_status/"
	createDir "/var/cache/2web/sessions/"

	INFO "Building web directory at '$webDirectory'"
	# force overwrite symbolic link to web directory
	# - link must be used to also use premade apache settings
	ln -sfn "$webDirectory" "/var/cache/2web/web"
	# link the user setable download directory cache
	ln -sfn "$downloadDirectory" "/var/cache/2web/downloads"
	# link the user setable generated directory cache
	ln -sfn "$generatedDirectory" "/var/cache/2web/generated"

	if returnModStatus "graph2web";then
		# this function runs once every 30 minutes, and record activity graph is locked to once every 30 minutes
		recordActivityGraph
		# build the updated activity graph
		buildActivityGraph
	fi

	# update the search plugin once per x days, this should only change if the hostname changes
	if cacheCheck "$webDirectory/opensearch.xml" 10;then
		# build the search plugin for the local server instance
		# - only works with mdns .local based resolution
		{
			echo "<?xml version='1.0' encoding='UTF-8'?>"
			echo "<OpenSearchDescription xmlns='http://a9.com/-/spec/opensearch/1.1/'>"
			echo "	<ShortName>$(hostname) 2web Search</ShortName>"
			echo "	<Description>Use 2web on $(hostname) to search the Internet.</Description>"
			echo "	<Image width='64' height='64' type='image/x-icon'>https://$(hostname).local/favicon.ico</Image>"
			echo "	<Url type='text/html' template='https://$(hostname).local/search.php?q={searchTerms}' />"
			echo "	<InputEncoding>UTF-8</InputEncoding>"
			echo "</OpenSearchDescription>"
		} > "$webDirectory/opensearch.xml"
	fi

	updateThemes="no"
	# if the build date of the software has changed then update the generated css themes for the site
	# - only one needs to be true so stop checks if one is true
	if checkFileDataSum "$webDirectory" "/usr/share/2web/buildDate.cfg";then
		# if the build date of the software has changed, update the themes
		updateThemes="yes"
	else
		if checkFileDataSum "$webDirectory" "/var/cache/2web/themeGen.cfg";then
			# if the theme gen file contains a diffrent timestamp, update the themes
			updateThemes="yes"
		else
			if checkDirSum "$webDirectory" "/usr/share/2web/theme-templates/";then
				# if any new files are added, old files removed, or file names are changed in the templates, update the themes
				updateThemes="yes"
			fi
		fi
	fi
	if [ "$updateThemes" == "yes" ];then
		themeColors=$(find "/usr/share/2web/theme-templates/" -type f -name 'color-*.css')
		themeColors=$(echo "$themeColors" | sed -z "s/\n/ /g")
		themeFonts=$(find "/usr/share/2web/theme-templates/" -type f -name 'font-*.css')
		themeFonts=$(echo "$themeFonts" | sed -z "s/\n/ /g")
		themeMods=$(find "/usr/share/2web/theme-templates/" -type f -name 'mod-*.css')
		themeMods=$(echo "$themeMods" | sed -z "s/\n/ /g")
		themeBases=$(find "/usr/share/2web/theme-templates/" -type f -name 'base-*.css')
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
						tempThemeName="${tempPathBase}-${tempPathColor}-${tempPathFont}-${tempPathMod}"
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
		# update the checksum files for all the checks
		setFileDataSum "$webDirectory" "/var/cache/2web/themeGen.cfg"
		setFileDataSum "$webDirectory" "/usr/share/2web/buildDate.cfg"
		setDirSum "$webDirectory" "/usr/share/2web/theme-templates/"
	fi
	# make sure the directories exist and have correct permissions, also link stylesheets
	createDir "$webDirectory"
	createDir "$webDirectory/new/"
	createDir "$webDirectory/random/"
	createDir "$webDirectory/tags/"
	createDir "$webDirectory/web_cache/"
	createDir "$webDirectory/shows/"
	createDir "$webDirectory/movies/"
	createDir "$webDirectory/kodi/"
	createDir "$webDirectory/settings/"
	createDir "$webDirectory/sums/"
	createDir "$webDirectory/views/"
	#createDir "$webDirectory/backups/"
	createDir "$webDirectory/log/"
	createDir "$webDirectory/search/"
	# storage location for all the thumbnails
	createDir "$webDirectory/thumbnails/"

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

	# add the log file
	linkFile "/usr/share/2web/settings/log.php" "$webDirectory/log/index.php"

	# Link the header and footer of the website
	linkFile "/usr/share/2web/templates/header.php" "$webDirectory/header.php"
	linkFile "/usr/share/2web/templates/footer.php" "$webDirectory/footer.php"
	# copy the indexHeader template
	linkFile "/usr/share/2web/templates/indexHeader.html" "$webDirectory/kodi/indexHeader.html"
	linkFile "/usr/share/2web/templates/indexHeader.html" "$webDirectory/indexHeader.html"
	# settings interface files
	linkFile "/usr/share/2web/settings/selectPath.php" "$webDirectory/settings/selectPath.php"
	linkFile "/usr/share/2web/settings/modules.php" "$webDirectory/settings/index.php"
	linkFile "/usr/share/2web/settings/modules.php" "$webDirectory/settings/modules.php"
	linkFile "/usr/share/2web/settings/users.php" "$webDirectory/settings/users.php"
	linkFile "/usr/share/2web/settings/about.php" "$webDirectory/settings/about.php"
	linkFile "/usr/share/2web/settings/radio.php" "$webDirectory/settings/radio.php"
	linkFile "/usr/share/2web/settings/tv.php" "$webDirectory/settings/tv.php"
	linkFile "/usr/share/2web/settings/ai.php" "$webDirectory/settings/ai.php"
	linkFile "/usr/share/2web/settings/ai_embeds.php" "$webDirectory/settings/ai_embeds.php"
	linkFile "/usr/share/2web/settings/ai_subtitles.php" "$webDirectory/settings/ai_subtitles.php"
	linkFile "/usr/share/2web/settings/ai_prompt.php" "$webDirectory/settings/ai_prompt.php"
	linkFile "/usr/share/2web/settings/ai_txt2img.php" "$webDirectory/settings/ai_txt2img.php"
	linkFile "/usr/share/2web/settings/ai_audio.php" "$webDirectory/settings/ai_audio.php"
	linkFile "/usr/share/2web/settings/iptv_blocked.php" "$webDirectory/settings/iptv_blocked.php"
	linkFile "/usr/share/2web/settings/nfo.php" "$webDirectory/settings/nfo.php"
	linkFile "/usr/share/2web/settings/comics.php" "$webDirectory/settings/comics.php"
	linkFile "/usr/share/2web/settings/graphs.php" "$webDirectory/settings/graphs.php"
	linkFile "/usr/share/2web/settings/comicsDL.php" "$webDirectory/settings/comicsDL.php"
	linkFile "/usr/share/2web/settings/cache.php" "$webDirectory/settings/cache.php"
	linkFile "/usr/share/2web/settings/system.php" "$webDirectory/settings/system.php"
	linkFile "/usr/share/2web/settings/themes.php" "$webDirectory/settings/themes.php"
	linkFile "/usr/share/2web/settings/themeExample.php" "$webDirectory/settings/themeExample.php"
	linkFile "/usr/share/2web/settings/weather.php" "$webDirectory/settings/weather.php"
	linkFile "/usr/share/2web/settings/ytdl2nfo.php" "$webDirectory/settings/ytdl2nfo.php"
	linkFile "/usr/share/2web/settings/rss.php" "$webDirectory/settings/rss.php"
	linkFile "/usr/share/2web/settings/music.php" "$webDirectory/settings/music.php"
	linkFile "/usr/share/2web/settings/repos.php" "$webDirectory/settings/repos.php"
	linkFile "/usr/share/2web/settings/portal.php" "$webDirectory/settings/portal.php"
	linkFile "/usr/share/2web/settings/portal_scanning.php" "$webDirectory/settings/portal_scanning.php"
	linkFile "/usr/share/2web/settings/wiki.php" "$webDirectory/settings/wiki.php"
	linkFile "/usr/share/2web/settings/kodi.php" "$webDirectory/settings/kodi.php"
	linkFile "/usr/share/2web/settings/apps.php" "$webDirectory/settings/apps.php"
	linkFile "/usr/share/2web/settings/settingsHeader.php" "$webDirectory/settings/settingsHeader.php"
	linkFile "/usr/share/2web/settings/logout.php" "$webDirectory/logout.php"
	linkFile "/usr/share/2web/settings/login.php" "$webDirectory/login.php"
	# add the manuals page
	linkFile "/usr/share/2web/templates/manuals.php" "$webDirectory/settings/manuals.php"
	# help/info docs
	linkFile "/usr/share/2web/templates/help.php" "$webDirectory/help.php"
	linkFile "/usr/share/2web/templates/support.php" "$webDirectory/support.php"
	linkFile "/usr/share/2web/templates/viewCounter.php" "$webDirectory/views/index.php"
	# caching resolvers
	linkFile "/usr/share/2web/resolvers/search.php" "$webDirectory/search.php"
	linkFile "/usr/share/2web/resolvers/ytdl-resolver.php" "$webDirectory/ytdl-resolver.php"
	linkFile "/usr/share/2web/resolvers/iptv-resolver.php" "$webDirectory/iptv-resolver.php"
	linkFile "/usr/share/2web/resolvers/m3u-gen.php" "$webDirectory/m3u-gen.php"
	linkFile "/usr/share/2web/resolvers/zip-gen.php" "$webDirectory/zip-gen.php"
	linkFile "/usr/share/2web/resolvers/kodi-player.php" "$webDirectory/kodi-player.php"
	linkFile "/usr/share/2web/resolvers/select-remote.php" "$webDirectory/remote.php"
	# error documents
	linkFile "/usr/share/2web/templates/404.php" "$webDirectory/404.php"
	linkFile "/usr/share/2web/templates/403.php" "$webDirectory/403.php"
	linkFile "/usr/share/2web/templates/401.php" "$webDirectory/401.php"
	# global javascript libary
	linkFile "/usr/share/2web/2webLib.js" "$webDirectory/2webLib.js"
	linkFile "/usr/share/2web/hls.js" "$webDirectory/hls.js"
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
	# create the playlists page
	linkFile "/usr/share/2web/templates/tagPlaylists.php" "$webDirectory/tags/index.php"
	# add the random index
	linkFile "/usr/share/2web/templates/random.php" "$webDirectory/random/index.php"
	# add robots.txt, to attempt to prevent any crawling of the site
	# - the site is for use on a internal network not online but still should not be crawled
	# - Can be edited by the user
	linkFile "/etc/2web/config_default/robots.txt" "$webDirectory/robots.txt"

	################################################################################
	# link lists these can be built and rebuilt during libary update
	################################################################################
	# copy over the favicon
	linkFile "/usr/share/2web/favicon_default.png" "$webDirectory/favicon.png"
	# only build a new .ico file if the source favicon.png has changed in contents
	rebuildFavIcon="no"
	if ! test -f "$webDirectory/favicon.ico";then
		rebuildFavIcon="yes"
	elif checkFileDataSum "$webDirectory" "$webDirectory/favicon.png";then
		rebuildFavIcon="yes"
		setFileDataSum "$webDirectory" "$webDirectory/favicon.png"
	else
		ALERT "A favicon already exists..."
	fi
	ALERT "Build Favicon: $rebuildFavIcon"
	if [ $rebuildFavIcon == "yes" ];then
		ALERT "Building a new favicon.ico for the website..."
		# build the favicon ico file using imagemagick for web compatibility
		convert "/usr/share/2web/favicon_default.png" \
			\( -clone 0 -resize 16x16 \) \
			\( -clone 0 -resize 32x32 \) \
			\( -clone 0 -resize 48x48 \) \
			\( -clone 0 -resize 64x64 \) \
			\( -clone 0 -resize 128x128 \) \
			-delete 0 -channel Alpha "$webDirectory/favicon.ico"
	fi
	# build the pulse graphic
	if ! test -f /var/cache/2web/pulse.gif;then
		buildPulseGif
	fi
	# build the spinner GIF
	if ! test -f /var/cache/2web/spinner.gif;then
		buildSpinnerGif
	fi
	# build the plasma failstate backgrounds
	if ! test -f /var/cache/2web/plasmaFanart.gif;then
		timeout 600 convert -size 800x600 plasma: -colorspace Gray "/var/cache/2web/web/plasmaFanart.png"
	fi
	if ! test -f /var/cache/2web/plasmaPoster.gif;then
		timeout 600 convert -size 200x500 plasma: -colorspace Gray "/var/cache/2web/web/plasmaPoster.png"
	fi

	# build the bump video
	if test -f /var/cache/2web/spinner.gif;then
		if ! test -f "/var/cache/2web/spinner_bg.png";then
			# make background used to generate video bump
			convert -scale "320x240" xc:black "/var/cache/2web/spinner_bg.png"
		fi
		filter="[0][1]scale2ref[bg][gif];[bg]setsar=1[bg];[bg][gif]overlay=shortest=1"
		#filter="${filter};drawtext:text='Try again\, Video is Caching...':fontcolor=white:fontsize=24;"
		if ! test -f "/var/cache/2web/spinner.mp4";then
			ffmpeg -f lavfi -i color=000000 -i "/var/cache/2web/spinner.gif" -filter_complex "$filter" -c:v libvpx-vp9 -loop 100 "/var/cache/2web/spinner.mp4"
			chown www-data:www-data "/var/cache/2web/spinner.mp4"
		fi
		if test -f "/var/cache/2web/spinner.mp4";then
			if ! test -f "/var/cache/2web/spinner_long.mp4";then
				ffmpeg -stream_loop 60 -i "/var/cache/2web/spinner.mp4" "/var/cache/2web/spinner_long.mp4"
				chown www-data:www-data "/var/cache/2web/spinner_long.mp4"
			fi
		fi
	fi
	# link default animations
	if ! test -f $webDirectory/spinner.gif;then
		linkFile "/var/cache/2web/spinner.gif" "$webDirectory/spinner.gif"
	fi
	if ! test -f $webDirectory/pulse.gif;then
		linkFile "/var/cache/2web/pulse.gif" "$webDirectory/pulse.gif"
	fi
	# create directory to store links that will generate qr codes
	createDir /var/cache/2web/qrCodes/
	# build the web widgets for these services
	# build web widgets for each http_host
	find "/var/cache/2web/qrCodes/" -mindepth 1 -maxdepth 1 -type d | while read qrDir;do
		hostSum=$(echo -n "$qrDir" | rev | cut -d'/' -f1 | rev)
		# per host check if the services file is older than 1 day
		if cacheCheck "$webDirectory/web_cache/widget_services_${hostSum}.index" "1";then
			{
				echo "<div class='titleCard'>"
				echo "<h2>Server Services</h2>"
				echo "<div class='listCard'>"
				find "/var/cache/2web/qrCodes/$hostSum/" -mindepth 1 -maxdepth 1 -type f -name '*-lnk.cfg' | while read qrConfig;do
					linkData=$(cat "$qrConfig")
					linkInfo=$(echo "$qrConfig" | sed "s/-lnk.cfg/-srv.cfg/g")
					linkInfo=$(cat "$linkInfo")
					linkServiceName=$(echo "$linkInfo" | cut -d',' -f1)
					linkServiceDesc=$(echo "$linkInfo" | cut -d',' -f2)
					#linkSum=$(echo "$qrConfig" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1)
					linkSum=$(echo -n "$linkData" | md5sum | cut -d' ' -f1)
					# build the thumbnail image as a qr code
					qrencode -m 1 -l H -o "/var/cache/2web/web/thumbnails/$linkSum.png" "$linkData"
					#draw link
					echo "<a class='showPageEpisode' target='_BLANK' href='$linkData'>"
					echo "<img src='/thumbnails/$linkSum.png' />"
					echo "<div class='showIndexNumbers'>$linkServiceName</div>"
					echo "$linkServiceDesc"
					echo "</a>"
				done
				echo "</div>"
				echo "</div>"
			} > "$webDirectory/web_cache/widget_services_${hostSum}.index"
		fi
	done
	################################################################################
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
	if yesNoCfgCheck "/etc/2web/fortuneStatus.cfg";then
		# only build a new fortune once every 24 hours
		if cacheCheck "$webDirectory/fortune.index" "1";then
			# write the fortune for this processing run...
			if test -f /usr/games/fortune;then
				# replace tabs with spaces for ascii art
				todaysFortune=$(/usr/games/fortune -a | sed "s/\t/ /g")
				echo "$todaysFortune" > "$webDirectory/fortune.index"
			fi
		fi
	else
		# the config is disabled check for any cached fortunes
		if test -f "$webDirectory/fortune.index";then
			# remove the fortune index file
			rm -v "$webDirectory/fortune.index"
		fi
	fi

	# check for the web player config
	if yesNoCfgCheck "/etc/2web/webPlayer.cfg";then
		# create the group
		# - This group must be removed manually from the /etc/2web/groups/ section
		# - This allows locking the web player to users logged in with permissions
		createDir "/etc/2web/groups/webPlayer/"
		# enable the web player
		linkFile "/usr/share/2web/resolvers/web-player.php" "$webDirectory/web-player.php"
		createDir "$webDirectory/web_player/"
	else
		# remove web player if it is disabled
		if test -L "$webDirectory/web-player.php";then
			rm -v "$webDirectory/web-player.php"
			# also remove the web player directory
			rm -vr "$webDirectory/web_player/"
		fi
	fi

	# check if the web client is enabled for use with signage type interfaces
	if yesNoCfgCheck "/etc/2web/client.cfg";then
		# create the group for client permissions
		createDir "/etc/2web/groups/client/"
		createDir "/etc/2web/groups/clientRemote/"
		# create the client directory
		createDir "$webDirectory/client/"
		# enable the web client page
		linkFile "/usr/share/2web/resolvers/client.php" "$webDirectory/client/index.php"
	else
		# remove the web client completely
		if test -d "$webDirectory/client/";then
			rm -vr "$webDirectory/client/"
		fi
	fi

	# install the php streaming script
	#ln -s "/usr/share/2web/stream.php" "$webDirectory/stream.php"
	linkFile "/usr/share/2web/resolvers/transcode.php" "$webDirectory/transcode.php"

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
	# check if the user has set randomTheme.cfg to yes to change to a random theme every 30 minutes
	if yesNoCfgCheck "/etc/2web/randomTheme.cfg";then
		# use a random theme from the themes directory
		theme=$(find /usr/share/2web/themes/ -name '*.css' | shuf | head -1 | rev | cut -d'/' -f1 | rev)
		ALERT "Randomly picked theme is '$theme'"
		# set the theme
		ln -sf "/usr/share/2web/themes/$theme" "$webDirectory/style.css"
	else
		# load the chosen theme
		theme=$(cat "/etc/2web/theme.cfg")
		# link the theme and overwrite if another theme is chosen
		ln -sf "/usr/share/2web/themes/$theme" "$webDirectory/style.css"
	fi
	# create font directory
	createDir "$webDirectory/fonts/"
	# link the fonts, only two are enabled by default, these fonts are for accessibility
	linkFile "/usr/share/fonts/truetype/hermit/Hermit-medium.otf" "$webDirectory/fonts/"
	linkFile "/usr/share/fonts/opentype/opendyslexic/OpenDyslexic-Regular.otf" "$webDirectory/fonts/"

	# cleanup tail of log database only once a day
	if cacheCheck "$webDirectory/log/cleanup.index" "1";then
		cleanupLog
		# update the time on the file to lock it out for another 24 hours
		touch "$webDirectory/log/cleanup.index"
	fi
	# cleanup old logged in sessions
	ALERT "Checking for cache files in /var/cache/2web/sessions/"
	# figure out the session time
	if test -f "/etc/2web/loginTimeoutMinutes.cfg";then
		timeoutMinutes=$(cat "/etc/2web/loginTimeoutMinutes.cfg")
	else
		# default to 0 minutes
		timeoutMinutes="0"
	fi
	if test -f "/etc/2web/loginTimeoutHours.cfg";then
		timeoutHours=$(cat "/etc/2web/loginTimeoutHours.cfg")
	else
		# default to 72 hours
		timeoutHours="72"
	fi
	# convert the timeout to minutes
	timeoutHours=$(( timeoutHours * 60 ))
	# combine the timeouts
	totalTimeout=$(( timeoutHours + timeoutMinutes ))
	# remove any sessions older than the timeout
	find "/var/cache/2web/sessions/" -type f -mmin +"$totalTimeout" -exec rm -v {} \;

	# build the homepage stats and link the homepage
	buildHomePage "$webDirectory"
	# check if the system needs rebooted
	rebootCheck
}
########################################################################
backupSettings(){
	# create a compressed backup of the server settings
	createDir "/var/cache/2web/backups/"
	tempTime=$1
	zip -9 -r "/var/cache/2web/backups/settings_$tempTime.zip" "/etc/2web/"
}
########################################################################
backupMetadata(){
	tempTime=$1
	# backup thumbnail cache
	# backup show and movie metadata from content
	# TVshows
	# - shows/*
	#  + tvshow.nfo
	#  + poster.png
	#  + fanart.png
	# Movies
	# - movies/*
	#  + movie.nfo
	#  + poster.png
	#  + fanart.png
	# - music/*
	#  + artist.nfo
	#  + folder.jpg
	#  + fanart.jpg
	#  + album/
	#   * album.nfo
	#   * cover.jpg
	# Comics
	# - comics/*
	#  + Get comic title from filenames and store comic titles as subfolders
	IFSBACKUP=$IFS
	IFS=$'\n'
	# use find command to search and get paths to all relevent metadata from the /kodi/ directory
	########################################################################
	# need relative directory for kodi backup to create proper pathnames
	cd "/var/cache/2web/web/kodi/"
	# search for shows
	files=$(find "shows" -maxdepth 2 -name '*.nfo' -o -name '*.png' -o -name '*.jpg' )
	for filePath in $files;do
		# compress all found files into a backup
		zip -9 --grow "/var/cache/2web/backups/content_$tempTime.zip" "$filePath"
	done
	########################################################################
	# search for movies
	files=$(find "movies" -maxdepth 2 -name '*.nfo' -o -name '*.png' -o -name '*.jpg' )
	for filePath in $files;do
		# remove leading path
		zip -9 --grow "/var/cache/2web/backups/content_$tempTime.zip" "$filePath"
	done
	########################################################################
	# music
	files=$(find "music" -name '*.nfo' -o -name '*.png' -o -name '*.jpg' )
	for filePath in $files;do
		zip -9 --grow "/var/cache/2web/backups/content_$tempTime.zip" "$filePath"
	done
	########################################################################
	# search for channels raw list, this can be re imported into a new 2web instance
	channelsPathRaw="/var/cache/2web/web/kodi/channels_raw.m3u"
	if test -f $channelsPathRaw;then
		zip -9 -j --grow "/var/cache/2web/backups/content_$tempTime.zip" "$channelsPathRaw"
	fi
	IFS=$IFSBACKUP
}
########################################################################
restoreSettings(){
	# unzip the stored settings file given
	settingsFile=$1
	#createDir "$(webRoot)/backups/$(date)/"
	# the file must exist
	if test -f "$settingsFile";then
		set -x
		ALERT "Use the below command to restore a backup of the settings on the server."
		unzip -x "$settingsFile" -d '/etc/2web/'
		set +x
	else
		# failed to find file to restore from
		echo "No file could be found to restore from at the path $settingsFile"
	fi
}
########################################################################
rebootCheck(){
	# stop the reboot if it is disabled
	if ! yesNoCfgCheck /etc/2web/autoReboot.cfg;then
		ALERT "Auto Reboot Disabled in CLI"
		# clear any previous uncleared reboot alerts
		if test -f "/var/cache/2web/web/rebootAlert.cfg";then
			rm -v "/var/cache/2web/web/rebootAlert.cfg"
		fi
		#
		return
	fi
	# check if reboot check is disabled
	if echo "$@" | grep -q -e "--no-reboot";then
		ALERT "Reboot Disabled in CLI"
		return
	fi
	ALERT "Auto Reboot enabled, checking current time..."
	# get the total cpus
	totalCPUS=$(cpuCount)
	#
	lastRebootTime="$(cat /var/cache/2web/web/lastReboot.cfg)"
	echo "Checking if it is time to reboot the system..."
	# check the reboot time
	if test -f /etc/2web/autoRebootTime.cfg;then
		rebootTime=$(cat /etc/2web/autoRebootTime.cfg)
	else
		rebootTime="4"
		echo "$rebootTime" > /etc/2web/autoRebootTime.cfg
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
			ALERT "Current Hour is set hour for reboot to occur..."
			# the last reboot must have happened more than a hour ago
			if [ $(date "+%s") -gt $(( $lastRebootTime + ( 60 * 60 ) )) ];then
				ALERT "Last Reboot was more than an hour ago..."
				ALERT "Reboot is now scheduled to happen when system becomes idle..."
				# put up the reboot alert on the website
				touch /var/cache/2web/web/rebootAlert.cfg
				# ten percent of the idle load
				idleLoad=$(( totalCPUS / 5 ))
				# the idle load should never be below 1
				if [ $idleLoad -le 0 ];then
					idleLoad=1
				fi
				# wait for the reboot until the job queue empties and the server becomes idle
				while [ $( echo "$(cat /proc/loadavg | cut -d' ' -f1) > $idleLoad" | bc ) -eq 1 ] && [ $(find "/var/cache/2web/queue/active/" -name "*.active" | wc -l) -gt 0 ];do
					INFO "Waiting for system to become idle in order to reboot. LOAD:($(cat /proc/loadavg | cut -d' ' -f1) > $idleLoad) ACTIVE JOBS:($(find "/var/cache/2web/queue/active/" -name "*.active" | wc -l))"
					sleep 30
				done
				# start the reboot process by showing a delay before rebooting the system
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
				# store the time of the last reboot
				date "+%s" > /var/cache/2web/web/lastReboot.cfg
				# remove the alert before reboot
				rm -v /var/cache/2web/web/rebootAlert.cfg
				# log reboot in the system log
				addToLog "INFO" "REBOOT" "A system reboot was triggered."
				# reboot the system using the available program
				if test -f /usr/bin/systemctl;then
					/usr/bin/systemctl reboot
				elif test -f /usr/sbin/reboot;then
					/usr/sbin/reboot
				elif test -f /usr/sbin/shutdown;then
					/usr/sbin/shutdown -r 'now'
				fi
			else
				ALERT "It has been less than a hour since the last reboot. The system will not reboot more than once per hour."
			fi
		else
			ALERT "The Time check failed this is not a hour on which a reboot is scheduled."
		fi
	fi
}
################################################################################
function verifyDatabasePaths(){
	databasePath=$1
	# timeout of sql database in miliseconds
	timeout=60000
	# check live groups database
	tables=$(sqlite3 --cmd ".timeout $timeout" "$databasePath" "select name from sqlite_master where type='table';")
	#INFO "tables='$tables'\n"
	IFSBACKUP=$IFS
	IFS=$'\n'
	totalTableCount=$(echo "$tables" | wc -w)
	tableCount=1
	for tableName in $tables;do
		ALERT "Searching $databasePath table:$tableName"
		rows=$(sqlite3 --cmd ".timeout $timeout" "$databasePath" "select * from \"$tableName\";")
		totalRowCount=$(echo -n "$rows" | wc -l)
		rowCount=1
		# read though each table for the title column
		for path in $rows;do
			rowCount=$(( rowCount + 1 ))
			# check the path stored in the table exists on the disk
			if test -f "${path}";then
				# directly test the path
				validPath="yes"
			elif test -f "/var/cache/2web/web${path}";then
				# add the base web directory to the path
				validPath="yes"
			else
				# no valid path could be found on the local disk
				validPath="no"
			fi
			# if the path could  not be validated
			if [ "$validPath" == "no" ];then
				# draw the invalid path to the CLI and log the invalid path and removal into the 2web system log
				ALERT "Scanning $tableCount/$totalTableCount tables. Reading $tableName row $rowCount/$totalRowCount ."
				ALERT "Discovered invalid path in $databasePath table:$tableName:$path"
				addToLog "ERROR" "Invalid SQL Entry" "Discovered invalid path in $databasePath table:$tableName:$path"
				# the path does not exist so it needs removed from the database
				sqlite3 --cmd ".timeout $timeout" "$databasePath" "delete from \"$tableName\" where title='$path';"
			fi
		done
		# increment the table count
		tableCount=$(( tableCount + 1 ))
		# draw the progress for the current database
		INFO "Scanning $tableCount/$totalTableCount tables. Reading $tableName row $rowCount/$totalRowCount ."
	done
	# cleanup and rebuild the database
	sqlite3 -cmd ".timeout $timeout" "$indexPath" "vacuum;"
	# reset IFS
	IFS=$IFSBACKUP
}
################################################################################
function waitForIdleServer(){
	webDirectory=$1
	loopCounter=0
	while true;do
		# make the loop spinner spin
		loopCounter=$(( $loopCounter + 1 ))
		# modulus the counter to make it loop
		loopCounter=$(( $loopCounter % 5 ))
		outputTail=""
		for ((i=1; i<=loopCounter; i++));do
			outputTail="$outputTail."
		done
		#tempLoopSpinner=$(echo "$tempRotate" | cut -c$(( $loopCounter + 1 )) )
		#infoPrefix="Waiting for server to become Idle. $tempLoopSpinner Active Service:"
		infoPrefix="Waiting for server to become Idle. Active Service:"
		if test -f "$webDirectory/comic2web.active";then
			INFO "$infoPrefix comic2web$outputTail"
		elif test -f "$webDirectory/iptv2web.active";then
			INFO "$infoPrefix iptv2web$outputTail"
		elif test -f "$webDirectory/nfo2web.active";then
			INFO "$infoPrefix nfo2web$outputTail"
		elif test -f "$webDirectory/weather2web.active";then
			INFO "$infoPrefix weather2web$outputTail"
		elif test -f "$webDirectory/music2web.active";then
			INFO "$infoPrefix music2web$outputTail"
		elif test -f "$webDirectory/graph2web.active";then
			INFO "$infoPrefix graph2web$outputTail"
		elif test -f "$webDirectory/ytdl2nfo.active";then
			INFO "$infoPrefix ytdl2nfo$outputTail"
		elif test -f "$webDirectory/wiki2web.active";then
			INFO "$infoPrefix wiki2web$outputTail"
		else
			INFO "Web server is now idle..."
			# this means all processes have completed
			# break the loop and run the reboot check
			break
		fi
		sleep 0.5
	done
}
################################################################################
function buildSpinnerGif(){
	mkdir -p /tmp/2web/spinner/
	backgroundColor="transparent"
	foregroundColor="white"
	outputPathPrefix="/tmp/2web/spinner/frame"
	newSize="32x32"
	# draw all the frames of the gif
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 0,0' -scale $newSize "${outputPathPrefix}_08.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 0,1' -scale $newSize "${outputPathPrefix}_07.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 0,2' -scale $newSize "${outputPathPrefix}_06.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 1,2' -scale $newSize "${outputPathPrefix}_05.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 2,2' -scale $newSize "${outputPathPrefix}_04.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 2,1' -scale $newSize "${outputPathPrefix}_03.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 2,0' -scale $newSize "${outputPathPrefix}_02.gif"
	convert -size 3x3 xc:$backgroundColor -fill $foregroundColor -draw 'point 1,0' -scale $newSize "${outputPathPrefix}_01.gif"

	# convert frames into transparent gif
	convert -transparent "#000000" -delay 16 -dispose Background \
		-page +0+0 "${outputPathPrefix}_01.gif" \
		-page +0+0 "${outputPathPrefix}_02.gif" \
		-page +0+0 "${outputPathPrefix}_03.gif" \
		-page +0+0 "${outputPathPrefix}_04.gif" \
		-page +0+0 "${outputPathPrefix}_05.gif" \
		-page +0+0 "${outputPathPrefix}_06.gif" \
		-page +0+0 "${outputPathPrefix}_07.gif" \
		-page +0+0 "${outputPathPrefix}_08.gif" \
		-loop 0 "/var/cache/2web/spinner.gif"
}
################################################################################
function buildPulseGif(){
	webDirectory=$1
	mkdir -p /tmp/2web/pulse/
	backgroundColor="transparent"
	foregroundColor="white"
	outputPathPrefix="/tmp/2web/pulse/frame"
	newSize="100x10"

	# pulse to right side
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 0,0' -scale $newSize "${outputPathPrefix}_01.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 1,0' -scale $newSize "${outputPathPrefix}_02.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 2,0' -scale $newSize "${outputPathPrefix}_03.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 3,0' -scale $newSize "${outputPathPrefix}_04.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 4,0' -scale $newSize "${outputPathPrefix}_05.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 5,0' -scale $newSize "${outputPathPrefix}_06.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 6,0' -scale $newSize "${outputPathPrefix}_07.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 7,0' -scale $newSize "${outputPathPrefix}_08.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 8,0' -scale $newSize "${outputPathPrefix}_09.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 9,0' -scale $newSize "${outputPathPrefix}_10.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 10,0' -scale $newSize "${outputPathPrefix}_11.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 11,0' -scale $newSize "${outputPathPrefix}_12.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 12,0' -scale $newSize "${outputPathPrefix}_13.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 13,0' -scale $newSize "${outputPathPrefix}_14.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 14,0' -scale $newSize "${outputPathPrefix}_15.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 15,0' -scale $newSize "${outputPathPrefix}_16.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 16,0' -scale $newSize "${outputPathPrefix}_17.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 17,0' -scale $newSize "${outputPathPrefix}_18.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 18,0' -scale $newSize "${outputPathPrefix}_19.gif"
	convert -size 20x1 xc:$backgroundColor -fill $foregroundColor -draw 'point 19,0' -scale $newSize "${outputPathPrefix}_20.gif"
	# pulse back to start

	# convert frames into transparent gif
	convert -transparent "#000000" -delay 16 -dispose Background \
		-page +0+0 "${outputPathPrefix}_01.gif" \
		-page +0+0 "${outputPathPrefix}_02.gif" \
		-page +0+0 "${outputPathPrefix}_03.gif" \
		-page +0+0 "${outputPathPrefix}_04.gif" \
		-page +0+0 "${outputPathPrefix}_05.gif" \
		-page +0+0 "${outputPathPrefix}_06.gif" \
		-page +0+0 "${outputPathPrefix}_07.gif" \
		-page +0+0 "${outputPathPrefix}_08.gif" \
		-page +0+0 "${outputPathPrefix}_09.gif" \
		-page +0+0 "${outputPathPrefix}_10.gif" \
		-page +0+0 "${outputPathPrefix}_11.gif" \
		-page +0+0 "${outputPathPrefix}_12.gif" \
		-page +0+0 "${outputPathPrefix}_13.gif" \
		-page +0+0 "${outputPathPrefix}_14.gif" \
		-page +0+0 "${outputPathPrefix}_15.gif" \
		-page +0+0 "${outputPathPrefix}_16.gif" \
		-page +0+0 "${outputPathPrefix}_17.gif" \
		-page +0+0 "${outputPathPrefix}_18.gif" \
		-page +0+0 "${outputPathPrefix}_19.gif" \
		-page +0+0 "${outputPathPrefix}_20.gif" \
		-page +0+0 "${outputPathPrefix}_19.gif" \
		-page +0+0 "${outputPathPrefix}_18.gif" \
		-page +0+0 "${outputPathPrefix}_17.gif" \
		-page +0+0 "${outputPathPrefix}_16.gif" \
		-page +0+0 "${outputPathPrefix}_15.gif" \
		-page +0+0 "${outputPathPrefix}_14.gif" \
		-page +0+0 "${outputPathPrefix}_13.gif" \
		-page +0+0 "${outputPathPrefix}_12.gif" \
		-page +0+0 "${outputPathPrefix}_11.gif" \
		-page +0+0 "${outputPathPrefix}_10.gif" \
		-page +0+0 "${outputPathPrefix}_09.gif" \
		-page +0+0 "${outputPathPrefix}_08.gif" \
		-page +0+0 "${outputPathPrefix}_07.gif" \
		-page +0+0 "${outputPathPrefix}_06.gif" \
		-page +0+0 "${outputPathPrefix}_05.gif" \
		-page +0+0 "${outputPathPrefix}_04.gif" \
		-page +0+0 "${outputPathPrefix}_03.gif" \
		-page +0+0 "${outputPathPrefix}_02.gif" \
		-page +0+0 "${outputPathPrefix}_01.gif" \
		-loop 0 "/var/cache/2web/pulse.gif"
}
################################################################################
function screenshot(){
	webPath=$1
	localPath=$2
	mkdir -p "/var/cache/2web/generated/comics/2web/2web Screenshots/"
	# make desktop screenshot
	wkhtmltoimage --cookie-jar "/var/cache/2web/generated/comics/2web/2web Screenshots/cookies.cfg" \
		--format jpg --enable-javascript --javascript-delay 2000 --width 1920 --disable-smart-width --height 1080 \
		"$webPath" "/var/cache/2web/generated/comics/2web/2web Screenshots/desktop_$localPath"
	# make phone screenshot
	wkhtmltoimage --cookie-jar "/var/cache/2web/generated/comics/2web/2web Screenshots/cookies.cfg" \
	  --format jpg --enable-javascript --javascript-delay 2000 --width 800 --disable-smart-width --height 2000 \
		"$webPath" "/var/cache/2web/generated/comics/2web/2web Screenshots/phone_$localPath"
}
################################################################################
function clientSetupMessage(){
	# display a message after setup of one of the clients
	if [[ $(find "/etc/2web/users/" -type f | wc -l) -gt 0 ]];then
		# if theere are no users
		echo "###############################################################################"
		echo "# You should log into the webserver and set a administrator username and      #"
		echo "# password to lock control of this device down.                               #"
		echo "###############################################################################"
		echo "https://$(hostname).local/settings/users.php#addNewUser"
		echo "###############################################################################"
		echo "_______________________________________________________________________________"
	fi
	echo "###############################################################################"
	echo "# Base access to the server for control is at the below link."
	echo "###############################################################################"
	echo "http://$(hostname).local/"
	echo "###############################################################################"
	if which pavucontrol;then
		echo "# You may have to setup the sound output with 'pavucontrol'."
		echo "###############################################################################"
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
		/usr/bin/wiki2web
		/usr/bin/git2web
		rebootCheck
	elif [ "$1" == "-s" ] || [ "$1" == "--status" ] || [ "$1" == "status" ];then
		# read all the modules from loadModules
		moduleNames=$(loadModules)
		counter=0
		drawCellLine 3
		startCellRow
		drawCell "Module" 3
		drawCell "Status" 3
		drawCell "Active" 3
		endCellRow
		drawCellLine 3
		# figure out enabled modules and build header text
		for module in $moduleNames;do
			# draw the headers
			#drawLine
			module="$(echo "$module" | cut -d'=' -f1)"
			startCellRow
			# draw the module  name
			drawCell "$module" 3
			# figure out enabled modules and build header text
			#echo -n "🧩"
			if returnModStatus "$module";then
				#echo -n "$module is ✅ Enabled, and is "
				#drawCell "$module is ✅ Enabled, and is " 3
				#drawCell "✅ Enabled" 3
				highlightCell "Enabled" 3
			else
				#echo -n "$module is ❎ Disabled, and is "
				#drawCell "$module is ❎ Disabled, and is " 3
				#drawCell "❎ Disabled" 3
				drawCell "Disabled" 3
			fi
			# check if the module is running
			if test -f "/var/cache/2web/web/$module.active";then
				#echo "⚙️ Currently Running."
				highlightCell "Currently Running." 3
			else
				#echo "🛑 Currently Inactive."
				#drawCell "🛑 Currently Inactive." 3
				drawCell "Currently Inactive." 3
			fi
			counter=$(( counter + 1 ))
			if [ $counter -gt 5 ];then
				counter=0
			fi
			endCellRow
			drawCellLine 3
		done
	elif [ "$1" == "-V" ] || [ "$1" == "--verify" ] || [ "$1" == "verify" ];then
		webDirectory=$(webRoot)
		# wait for all background services to stop
		if echo "$@" | grep -q -e "--force";then
			ALERT "[WARNING]:Skipping wait for idle server..."
			ALERT "[WARNING]:Forcing database verify while running active updates..."
			ALERT "[WARNING]:This can corrupt the database until the next update..."
			ALERT "[WARNING]:Use --verify without --force to avoid this..."
		else
			waitForIdleServer "$webDirectory"
		fi
		# parallel and regular processing is available for --verify
		if echo "$@" | grep -q -e "--parallel";then
			totalCPUS=$(grep "processor" "/proc/cpuinfo" | wc -l)
			# verify the 2web content indexes
			verifyDatabasePaths "$webDirectory/data.db" &
			waitQueue 0.5 "$totalCPUS"
			# verify the background images
			verifyDatabasePaths "$webDirectory/backgrounds.db" &
			waitQueue 0.5 "$totalCPUS"
			# verify the groups generated by the live channels
			verifyDatabasePaths "$webDirectory/live/groups.db" &
			blockQueue 1
		else
			verifyDatabasePaths "$webDirectory/data.db"
			verifyDatabasePaths "$webDirectory/backgrounds.db"
			verifyDatabasePaths "$webDirectory/live/groups.db"
		fi
		echo "Finished Verifying database."
	elif [ "$1" == "-L" ] || [ "$1" == "--unlock" ] || [ "$1" == "unlock" ];then
		webDirectory=$(webRoot)
		# read all the modules from loadModules
		# - reverse list so main 2web process is killed last
		moduleNames=$(loadModules | tac)
		# figure out enabled modules and build header text
		for module in $moduleNames;do
			module="$(echo "$module" | cut -d'=' -f1)"
			# clean all temp lock files
			rm -v $webDirectory/$module.active
			# kill active running modules
			killall $module
		done
	elif [ "$1" == "-p" ] || [ "$1" == "--parallel" ] || [ "$1" == "parallel" ];then
		ALERT "================================================================================"
		ALERT "PARALLEL MODE"
		ALERT "================================================================================"
		totalCPUS=$(grep "processor" "/proc/cpuinfo" | wc -l)
		webDirectory=$(webRoot)
		# parllelize the update processes
		###########################
		# update main components
		# - all processes are locked so conflicts will not arise from launching this process multuple times
		update2web
		# update the on-demand downloads
		ALERT "Launching ytdl2nfo..."
		/usr/bin/ytdl2nfo &
		waitQueue 1 "$totalCPUS"
		# update weather
		ALERT "Launching weather2web..."
		/usr/bin/weather2web &
		waitQueue 1 "$totalCPUS"
		# update the metadata and build webpages for all generators
		###########################################################################
		ALERT "Launching nfo2web..."
		/usr/bin/nfo2web --parallel &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching graph2web..."
		/usr/bin/graph2web &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching iptv2web..."
		/usr/bin/iptv2web &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching comic2web..."
		/usr/bin/comic2web --parallel &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching music2web..."
		/usr/bin/music2web --parallel &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching wiki2web..."
		/usr/bin/wiki2web --parallel &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching git2web..."
		/usr/bin/git2web --parallel &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching rss2nfo..."
		/usr/bin/rss2nfo --parallel &
		waitQueue 1 "$totalCPUS"
		###########################################################################
		ALERT "Launching ai2web..."
		/usr/bin/ai2web --parallel &
		blockQueue 1
		# wait for all background services to stop
		waitForIdleServer "$webDirectory"
		ALERT "Finished Parallel Processing..."
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
	elif [ "$1" == "-G" ] || [ "$1" == "--git" ] || [ "$1" == "git" ];then
		update2web
		/usr/bin/git2web
		rebootCheck
	elif [ "$1" == "-w" ] || [ "$1" == "--wiki" ] || [ "$1" == "wiki" ];then
		update2web
		/usr/bin/wiki2web
		rebootCheck
	elif [ "$1" == "-A" ] || [ "$1" == "--ai" ] || [ "$1" == "ai" ];then
		update2web
		/usr/bin/ai2web
		rebootCheck
	elif [ "$1" == "-P" ] || [ "$1" == "--portal" ] || [ "$1" == "portal" ];then
		update2web
		/usr/bin/portal2web
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
		/usr/bin/wiki2web
		/usr/bin/graph2web
		/usr/bin/portal2web
		/usr/bin/ai2web
		rebootCheck
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ];then
		# - upgrade streamlink and yt-dlp and gallery-dl pip packages
		#  * All fast moving software is included here for upgrade in a single command
		#  * yt-dlp is used for stream translation and metadata conversion
		#  * streamlink is used for translation of livestreams
		#  * gallery-dl is used for comic2web
		#  * jslint is used by git2web for javascript linting
		# - upgrade all the pip packages used
		# - This command is called in 2web.cron
		# - each module has its own upgrade method for its individual packages so that
		#   only enabled modules will upgrade
		ytdl2nfo --upgrade
		iptv2web --upgrade
		nfo2web --upgrade
		comic2web --upgrade
		git2web --upgrade
		ai2web --upgrade
		rss2nfo --upgrade
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ];then
		# remove all genereated web content
		/usr/bin/nfo2web nuke
		/usr/bin/comic2web nuke
		/usr/bin/iptv2web nuke
		/usr/bin/weather2web nuke
		/usr/bin/music2web nuke
		/usr/bin/wiki2web nuke
		/usr/bin/weather2web nuke
		/usr/bin/graph2web nuke
		/usr/bin/git2web nuke
		/usr/bin/portal2web nuke
		/usr/bin/ai2web nuke
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ];then
		# remove all website content and disable the website
		rm -rv /var/cache/2web/web/*
		disableApacheServer
		disableCronJob
	elif [ "$1" == "--cleanup-log" ] || [ "$1" == "cleanup-log" ];then
		# force the log cleanup
		cleanupLog
	elif [ "$1" == "-F" ] || [ "$1" == "--fake-graph" ] || [ "$1" == "fake-graph" ];then
		if returnModStatus "graph2web";then
			buildFakeActivityGraph
			buildActivityGraph
		else
			echo "[ERROR]: graph2web is disabled so no fake graph was generated."
		fi
	elif [ "$1" == "-FF" ] || [ "$1" == "--full-fake-graph" ] || [ "$1" == "full-fake-graph" ];then
		if returnModStatus "graph2web";then
			buildFullFakeActivityGraph
			buildActivityGraph
		else
			echo "[ERROR]: graph2web is disabled so no fake graph was generated."
		fi
	elif [ "$1" == "-F" ] || [ "$1" == "--fake-log" ] || [ "$1" == "fake-log" ];then
		# does the black hole still exist
		while : ;do
			addToLog "DEBUG" "Fake log test" "This is for checking log functionality, Random Number $RANDOM$RANDOM$RANDOM"
			INFO "Writing to log once per second, Use [ CTRL + C ] to exit..."
			sleep 1
		done
	elif [ "$1" == "-rc" ] || [ "$1" == "--reboot-check" ] || [ "$1" == "rebootcheck" ];then
		rebootCheck
	elif [ "$1" == "-b" ] || [ "$1" == "--backup" ] || [ "$1" == "backup" ] ;then
		backupTime=$(date)
		backupSettings "$backupTime"
		backupMetadata "$backupTime"
		drawLine
		echo "The backup can be found in the backup location"
		echo "/var/cache/2web/backups/"
		drawLine
		echo "This specific backup is stored at"
		echo "/var/cache/2web/backups/settings_$backupTime.zip"
		echo "/var/cache/2web/backups/content_$backupTime.zip"
		drawLine
	elif [ "$1" == "-re" ] || [ "$1" == "--restore" ] || [ "$1" == "restore" ] ;then
		restoreSettings "$2"
	elif [ "$1" == "--demo-data" ] || [ "$1" == "demo-data" ] ;then
		# generate demo data for 2web modules for use in screenshots, make it random as can be
		#########################################################################################
		# nfo2web demo data for movies and shows
		#########################################################################################
		nfo2web --demo-data
		#########################################################################################
		# comic2web demo comics
		#########################################################################################
		comic2web --demo-data
		#########################################################################################
		# git2web demo repos
		#########################################################################################
		git2web --demo-data
	elif [ "$1" == "-S" ] || [ "$1" == "--screenshots" ] || [ "$1" == "screenshots" ] ;then
		totalCPUs=$(cpuCount)
		# remove existing cookie file
		# - cookie file allows screenshots of admin section when no admin is yet configured
		if test -f "/var/cache/2web/generated/comics/2web/2web Screenshots/cookies.cfg";then
			rm -v "/var/cache/2web/generated/comics/2web/2web Screenshots/cookies.cfg"
		fi
		################################################################################
		screenshot  "http://localhost/" "01_home.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot  "http://localhost/help.php" "02_help.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# playlists new
		screenshot "http://localhost/new/" "02_playlist_new.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# playlists random
		screenshot "http://localhost/random/" "02_playlist_random.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# nfo2web movies index
		screenshot "http://localhost/movies/" "03_index_movies.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# nfo2web shows index
		screenshot "http://localhost/shows/" "03_index_shows.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# iptv index
		screenshot "http://localhost/live/" "03_index_live.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# comic index
		screenshot "http://localhost/comics/" "03_index_comics.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# weather index
		screenshot "http://localhost/weather/" "03_index_weather.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# portal index
		screenshot "http://localhost/portal/" "03_index_portal.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# repo index
		screenshot "http://localhost/repos/" "03_index_repos.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# graph index
		screenshot "http://localhost/graphs/" "03_index_graphs.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# wiki index
		screenshot "http://localhost/wiki/" "03_index_wiki.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# music2web index
		# - need example of a music artist and a album, and a track playing
		screenshot "http://localhost/music/" "05_index_music.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# ai index
		screenshot "http://localhost/ai/" "03_index_ai.jpg" &
		waitQueue 0.5 "$totalCPUs"
		################################################################################
		# search
		################################################################################
		screenshot "http://localhost/search.php?q=" "06_index_search.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/search.php?q=!help" "06_search_bang_help.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/search.php?q=the" "06_search_the.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/search.php?q=a" "06_search_a.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/search.php?q=cpu" "06_search_cpu.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/search.php?q=2web" "06_search_2web.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/search.php?q=awesome" "06_search_awesome.jpg" &
		waitQueue 0.5 "$totalCPUs"
		################################################################################
		# git2web index
		################################################################################
		screenshot "http://localhost/repos/" "07_index_repos.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# test repo view
		screenshot "http://localhost/repos/2web/" "07_repos_2web_overview.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# all commits
		screenshot "http://localhost/repos/2web/?list" "08_repos_2web_commits.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# lint lists
		screenshot "http://localhost/repos/2web/?listLint" "08_repos_2web_lint.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# inspector
		screenshot "http://localhost/repos/2web/?inspector" "08_repos_2web_inspector.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# graph view diff
		screenshot "http://localhost/repos/2web/?graph=diff_month" "08_repos_2web_graph_diff.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# graph view commit
		screenshot "http://localhost/repos/2web/?graph=commit_month" "08_repos_2web_graph_commit.jpg" &
		waitQueue 0.5 "$totalCPUs"
		# documentation
		screenshot "http://localhost/repos/2web/?listDoc" "08_repos_2web_docs.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/repos/2web/?doc=2webLib.sh.index" "08_repos_2web_doc_example.jpg" &
		waitQueue 0.5 "$totalCPUs"
		################################################################################
		# ai2web index
		################################################################################
		screenshot "http://localhost/ai/" "08_ai_2web_index.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/ai/prompt/" "08_ai_2web_prompt.jpg" &
		waitQueue 0.5 "$totalCPUs"
		################################################################################
		# portal2web index
		################################################################################
		screenshot "http://localhost/portal/" "08_portal_2web_portal.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "http://localhost/portal/localhost.php" "08_portal_2web_portal_localhost.jpg" &
		waitQueue 0.5 "$totalCPUs"
		################################################################################
		# graph2web index
		################################################################################
		screenshot "http://localhost/graphs/cpu/" "08_graph_2web_cpu.jpg" &
		waitQueue 0.5 "$totalCPUs"
		################################################################################
		# settings screenshots, 2web must be in passwordless mode, e.g. no admin users
		# settings required https
		################################################################################
		ALERT "Screenshots of Admin locations only work if no admin is set."
		screenshot "https://localhost/views/" "10_settings_views.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/log/" "10_settings_log.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/modules.php" "10_settings_modules.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/system.php" "10_settings_system.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/nfo.php" "10_settings_nfo2web.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/ytdl2nfo.php" "10_settings_ytdl2web.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/music.php" "10_settings_music.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/comics.php" "10_settings_comics.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/comicsDL.php" "10_settings_comics_downloads.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/tv.php" "10_settings_iptv_tv.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/radio.php" "10_settings_iptv_radio.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/iptv_blocked.php" "10_settings_iptv_blocked.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/weather.php" "10_settings_weather.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/ai.php" "10_settings_ai.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/portal.php" "10_settings_portal.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/repos.php" "10_settings_repos.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/graphs.php" "10_settings_graphs.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/settings/about.php" "10_settings_about.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/logout.php" "10_settings_logout.jpg" &
		waitQueue 0.5 "$totalCPUs"
		screenshot "https://localhost/login.php" "10_settings_login.jpg" &
		waitQueue 0.5 "$totalCPUs"
		blockQueue 1
		# remove the cookies used in the screenshots
		rm -v "/var/cache/2web/generated/comics/2web/2web Screenshots/cookies.cfg"
		# change ownership
		chown -R www-data:www-data "/var/cache/2web/downloads/comics/generated/2web Screenshots/"
		ALERT "Finished building the 2web screenshots"
		ALERT "Screenshots are stored in /var/cache/2web/downloads/comics/generated/2web Screenshots/"
		ALERT "Run 'comic2web' in order to add the screenshots to the comic section of 2web"
	elif [ "$1" == "-cc" ] || [ "$1" == "--clean-cache" ] || [ "$1" == "cleancache" ] ;then
		################################################################################
		# Run the cleanup to remove cached files older than the cache time
		# - ALL caches use the same timer
		# - the timer is set in days
		################################################################################
		webDirectory=$(webRoot);
		# load the cache settings
		if test -f "/etc/2web/cache/cacheDelay.cfg";then
			echo "Loading cache settings..."
			cacheDelay=$(cat "/etc/2web/cache/cacheDelay.cfg")
		else
			echo "Using default cache settings..."
			cacheDelay="14"
		fi
		# if the cache is not set to forever then cleanup the cache directories that exist
		if ! echo "$cacheDelay" | grep -q "forever";then
			# cleanup all the 2web cache directories
			echo "Checking cache for files older than ${cacheDelay} Days"
			# cleanup the resolver cache
			ALERT "Checking for cache files in $webDirectory/RESOLVER-CACHE/"
			if test -d "$webDirectory/RESOLVER-CACHE/";then
				find "$webDirectory/RESOLVER-CACHE/" -type d -mtime +"$cacheDelay" -exec rm -rv {} \;
			fi
			# cleanup files in the transcode cache
			ALERT "Checking for cache files in $webDirectory/TRANSCODE-CACHE/"
			if test -d "$webDirectory/TRANSCODE-CACHE/";then
				find "$webDirectory/TRANSCODE-CACHE/" -type f -mtime +"$cacheDelay" -name '*.webm' -exec rm -v {} \;
			fi
			# cleanup the m3u playlist cache
			ALERT "Checking for cache files in $webDirectory/M3U-CACHE/"
			if test -d "$webDirectory/M3U-CACHE/";then
				find "$webDirectory/M3U-CACHE/" -type f -mtime +"$cacheDelay" -name '*.m3u' -exec rm -v {} \;
			fi
			# cleanup the search results cache
			ALERT "Checking for cache files in $webDirectory/search/"
			if test -d "$webDirectory/search/";then
				find "$webDirectory/search/" -type f -mtime +"$cacheDelay" -name '*.index' -exec rm -v {} \;
			fi
			# cleanup the generated zip file cache
			ALERT "Checking for cache files in $webDirectory/zip_cache/"
			if test -d "$webDirectory/zip_cache/";then
				find "$webDirectory/zip_cache/" -type f -mtime +"$cacheDelay" -name '*.zip' -o -name '*.cbz' -exec rm -v {} \;
			fi
			# cleanup the web player cache
			ALERT "Checking for cache files in $webDirectory/web_player/"
			if test -d "$webDirectory/web_player/";then
				find "$webDirectory/web_player/" -type d -mtime +"$cacheDelay" -exec rm -rv {} \;
			fi
			# cleanup kodi player
			ALERT "Checking for cache files in $webDirectory/kodi-player/"
			if test -d "$webDirectory/kodi-player/";then
				find "$webDirectory/kodi-player/" -type f -mtime +"$cacheDelay" -exec rm -v {} \;
			fi
			# cleanup queue logs
			ALERT "Checking for log files in /var/cache/2web/queue/log/"
			if test -d "/var/cache/2web/queue/log/";then
				find "/var/cache/2web/queue/log/" -type f -mtime +"$cacheDelay" -exec rm -v {} \;
			fi
		fi
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ];then
		cat /usr/share/2web/help/2web.txt
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		#drawLine
		#echo "2web Server Version"
		#drawLine
		drawCellLine 2
		startCellRow
		drawCell "Server Info" 2
		drawCell "Data" 2
		endCellRow
		drawCellLine 2
		startCellRow
		drawCell "Server Verison" 2
		drawCell "$(cat /usr/share/2web/version.cfg)" 2
		endCellRow
		startCellRow
		drawCell "Publish Date" 2
		drawCell "$(cat /usr/share/2web/versionDate.cfg)" 2
		endCellRow
		startCellRow
		drawCell "Build Date" 2
		drawCell "$(cat /usr/share/2web/buildDate.cfg)" 2
		endCellRow
		# read all the modules from loadModules
		moduleNames=$(loadModules)
		#drawCellLine 1
		drawCellLine 2
		startCellRow
		drawCell "Module Name" 2
		drawCell "Module Version" 2
		endCellRow
		drawCellLine 2
		#drawLine
		#drawCellLine 2
		# figure out enabled modules
		for module in $moduleNames;do
			module="$(echo "$module" | cut -d'=' -f1)"
			if test -f "/usr/share/2web/version_$module.cfg";then
				# draw the headers
				startCellRow
				drawCell "$module" 2
				drawCell "$(cat "/usr/share/2web/version_$module.cfg")" 2
				endCellRow
				#drawCellLine 2
				# write the module version
				#echo -n "$module : "
				#cat /usr/share/2web/version_$module.cfg
			fi
		done
		drawCellLine 2
		startCellRow
		drawCell "Resolver Name" 2
		drawCell "Resolver Version" 2
		endCellRow
		drawCellLine 2
		# draw resolver versions
		startCellRow
		export PYTHONPATH="/var/cache/2web/generated/pip/gallery-dl/"
		versionData=$(/var/cache/2web/generated/pip/gallery-dl/bin/gallery-dl --version)
		drawCell "gallery-dl" 2
		drawCell "$versionData" 2
		endCellRow
		startCellRow
		export PYTHONPATH="/var/cache/2web/generated/pip/streamlink/"
		versionData=$(/var/cache/2web/generated/pip/streamlink/bin/streamlink --version)
		drawCell "streamlink" 2
		drawCell "$versionData" 2
		endCellRow
		startCellRow
		versionData=$(/var/cache/2web/generated/yt-dlp/yt-dlp --version)
		drawCell "yt-dlp" 2
		drawCell "$versionData" 2
		endCellRow
		drawCellLine 2
	elif [ "$1" == "--desktop-client" ] || [ "$1" == "desktop-client" ];then
		################################################################################
		# use a bash script to load the event server and launch client commands
		# - will setup a custom kiosk user with a openbox session
		# - kiosk will login automatically when the computer is powered on
		################################################################################
		# check for default client event server path
		if ! test -f "/etc/2web/client-event.cfg";then
			# create the event server config
			echo "http://$(hostname).local/client/?events" > "/etc/2web/client-event.cfg"
		fi
		# check for background config
		if ! test -f "/etc/2web/client-bg.cfg";then
			# read the custom client config for the background path
			echo "http://$(hostname).local/randomFanart.php" > "/etc/2web/client-bg.cfg"
		fi
		################################################################################
		# install the software to make this into a fast client
		################################################################################
		# install x server and the init system
		apt-get install -y xserver-xorg
		apt-get install -y xinit
		# install boot system
		apt-get install -y lightdm
		# install the notification daemon
		apt-get install -y xfce4-notifyd
		if ! which openbox-session;then
			# install the desktop
			apt-get install -y openbox
		fi
		if ! which notify-send;then
			# install the notification system
			apt-get install -y libnotify-bin
		fi
		if ! which vlc;then
			# install the video player
			apt-get install -y vlc
		fi
		if ! which feh;then
			# install the image viewer to set the background on openbox
			apt-get install -y feh
		fi
		if ! which redshift;then
			# install redshift for night mode support
			apt-get install -y redshift
		fi
		if ! which adduser;then
			# install adduser to create the kiosk user
			apt-get install -y adduser
		fi
		if ! which xdotool;then
			# install the sound control application
			apt-get install -y xdotool
		fi
		if ! which pavucontrol;then
			# install the sound control application
			apt-get install -y pavucontrol
		fi
		if ! which pulseaudio;then
			# install the sound system
			apt-get install -y pulseaudio
		fi
		if ! which unclutter;then
			# install unclutter to hide the mouse cursor
			apt-get install -y unclutter
			apt-get install -y unclutter-startup
		fi
		if ! test -d "/etc/lightdm/lightdm.conf.d/";then
			# create the autologin lightdm config path
			mkdir -p "/etc/lightdm/lightdm.conf.d/"
		fi
		if ! test -f "/etc/lightdm/lightdm.conf.d/12-autologin-kiosk.conf";then
			# create the autologin lightdm config file
			{
				echo "[SeatDefaults]"
				echo "autologin-user=kiosk"
				echo "autologin-user-timeout=0"
				#echo "user-session=Xsession"
			} > /etc/lightdm/lightdm.conf.d/20-autologin-kiosk.conf
		fi
		# create a user without any special privliges to run the kiosk on
		adduser kiosk --home "/home/kiosk/" --comment "2web Kiosk,Unlisted,Unlisted,Unlisted" --disabled-password
		# debian version of above command with old style comment line, this does nothing if the above line worked
		adduser kiosk --home "/home/kiosk/" --gecos "2web Kiosk,Unlisted,Unlisted,Unlisted" --disabled-password
		# Create custom Xsession file
		{
			echo "#!/bin/bash"
			# launch the desktop enviorment
			echo "exec openbox-session &"
			# hide the mouse cursor
			echo "unclutter &"
			# start the sound server
			echo "pulseaudio --start"
			# run the client and respond to connections from the server
			echo "2web_client"
		} > /home/kiosk/.xsession
		clientSetupMessage
	elif [ "$1" == "--browser-client" ] || [ "$1" == "browser-client" ];then
		################################################################################
		# install and setup a client kiosk that will run in the display of the server
		################################################################################
		# install software to setup kiosk
		if ! which openbox-session;then
			apt-get install -y openbox
		fi
		if ! which adduser;then
			apt-get install -y adduser
		fi
		apt-get install -y xserver-xorg
		apt-get install -y xinit
		# install boot system
		apt-get install -y lightdm
		# install the stable version of firefox
		if ! which firefox;then
			apt-get install -y firefox-esr
		fi
		if ! which unclutter;then
			apt-get install -y unclutter
			apt-get install -y unclutter-startup
		fi
		if ! test -d "/etc/lightdm/lightdm.conf.d/";then
			# create the autologin lightdm config directory
			mkdir -p "/etc/lightdm/lightdm.conf.d/"
		fi
		if ! test -f "/etc/lightdm/lightdm.conf.d/12-autologin-kiosk.conf";then
			# create the autologin lightdm config file
			{
				echo "[SeatDefaults]"
				echo "autologin-user=kiosk"
				echo "autologin-user-timeout=0"
				#echo "user-session=Xsession"
			} > /etc/lightdm/lightdm.conf.d/20-autologin-kiosk.conf
		fi
		# create a user without any special privliges to run the kiosk on
		adduser kiosk --home "/home/kiosk/" --comment "2web Kiosk,Unlisted,Unlisted,Unlisted" --disabled-password
		# debian version of above command with old style comment line, this does nothing if the above line worked
		adduser kiosk --home "/home/kiosk/" --gecos "2web Kiosk,Unlisted,Unlisted,Unlisted" --disabled-password

		# create the firefox profile directory for the kiosk
		mkdir -p "/home/kiosk/browser/"
		# run a fake video input to let firefox load up and generate the user profile, give the pc 10 seconds to load it
		xvfb-run timeout 10s firefox --profile "/home/kiosk/browser/"

		# create the custom firefox settings with user.js file in firefox directory
		{
			echo 'user_pref("media.autoplay.default",0);'
			echo 'user_pref("media.autoplay.allow-muted",false);'
			echo 'user_pref("media.autoplay.blocking_policy",0);'
			echo 'user_pref("media.block-autoplay-until-in-foreground",true);'
			# enable option to always use graphics compositing
			echo 'user_pref("layers.acceleration.force-enabled",true);'
		} > "/home/kiosk/browser/user.js"
		# Create custom Xsession file
		{
			echo "#!/bin/bash"
			# launch the desktop enviorment
			echo "exec openbox-session &"
			# hide the mouse cursor
			echo "unclutter &"
			# create a loop that will keep the web browser active if it crashes
			echo "while true;do"
			# launch the browser
			echo "	firefox --kiosk --profile '/home/kiosk/browser/' --private-window 'http://localhost/client/'"
			echo "done"
		} > /home/kiosk/.xsession
		# enable the web client in the webserver
		yesNoCfgSet "/etc/2web/kodi/client.cfg" "yes"
		# enable the web player
		yesNoCfgSet "/etc/2web/kodi/webPlayer.cfg" "yes"
		clientSetupMessage
	elif [ "$1" == "--kodi-client" ] || [ "$1" == "kodi-client" ];then
		# setup a kiosk that auto logs in and launches kodi fullscreen on the server system. This will also
		# automatically setup kodi to use content from the server as its sources.

		apt-get install -y xserver-xorg
		apt-get install -y xinit
		# install boot system
		apt-get install -y lightdm

		if ! which adduser;then
			apt-get install -y adduser
		fi
		if ! which unclutter;then
			# install unclutter to hide the mouse cursor
			apt-get install -y unclutter
			apt-get install -y unclutter-startup
		fi
		if ! which kodi;then
			# install kodi if it is not on the system
			apt-get install -y kodi
		fi
		if ! test -d "/etc/lightdm/lightdm.conf.d/";then
			# create the lightdm custom config directory if it does not exist
			mkdir -p "/etc/lightdm/lightdm.conf.d/"
		fi
		if ! test -f "/etc/lightdm/lightdm.conf.d/20-autologin-kiosk.conf";then
			# create the autologin lightdm config file
			{
				echo "[SeatDefaults]"
				echo "autologin-user=kiosk"
				echo "autologin-user-timeout=0"
				# set the user session to kodi
				echo "user-session=kodi"
			} > /etc/lightdm/lightdm.conf.d/20-autologin-kiosk.conf
		fi
		# create a user without any special privliges to run the kiosk on
		adduser kiosk --home "/home/kiosk/" --comment "2web Kiosk,Unlisted,Unlisted,Unlisted" --disabled-password
		# debian version of above command with old style comment line, this does nothing if the above line worked
		adduser kiosk --home "/home/kiosk/" --gecos "2web Kiosk,Unlisted,Unlisted,Unlisted" --disabled-password
		# create the directory to store the kodi settings
		if ! test -d "/home/kiosk/.kodi/userdata/";then
			mkdir -p /home/kiosk/.kodi/userdata/
		fi
		{
			# build the kodi sources config file to point to this server
			echo "<sources>"
			echo "	<programs>"
			echo "		<default pathversion=\"1\"></default>"
			echo "	</programs>"
			echo "	<video>"
			echo "		<default pathversion=\"1\"></default>"
			echo "		<source>"
			echo "			<name>movies</name>"
			echo "			<path pathversion=\"1\">http://localhost:80/kodi/movies/</path>"
			echo "			<allowsharing>true</allowsharing>"
			echo "		</source>"
			echo "		<source>"
			echo "			<name>shows</name>"
			echo "			<path pathversion=\"1\">http://localhost:80/kodi/shows/</path>"
			echo "			<allowsharing>true</allowsharing>"
			echo "		</source>"
			echo "	</video>"
			echo "	<music>"
			echo "     <default pathversion=\"1\"></default>"
			echo "     <source>"
			echo "         <name>music</name>"
			echo "         <path pathversion=\"1\">http://localhost:80/kodi/music/</path>"
			echo "         <allowsharing>true</allowsharing>"
			echo "     </source>"
			echo "	</music>"
			echo "	<pictures>"
			echo "		<default pathversion=\"1\"></default>"
			echo "     <source>"
			echo "         <name>comics</name>"
			echo "         <path pathversion=\"1\">http://localhost:80/kodi/comics/</path>"
			echo "         <allowsharing>true</allowsharing>"
			echo "     </source>"
			echo "     <source>"
			echo "         <name>comics_tank</name>"
			echo "         <path pathversion=\"1\">http://localhost:80/kodi/comics_tank/</path>"
			echo "         <allowsharing>true</allowsharing>"
			echo "     </source>"
			echo "	</pictures>"
			echo "	<files>"
			echo "		<default pathversion=\"1\"></default>"
			echo "	</files>"
			echo "	<games>"
			echo "		<default pathversion=\"1\"></default>"
			echo "	</games>"
			echo "</sources>"
		} > "/home/kiosk/.kodi/userdata/sources.xml"
		# create a random password
		remotePass="$RANDOM$RANDOM$RANDOM"
		# create kodi custom advanced settings.xml
		{
			echo "<advancedsettings>"
			echo "	<services>"
			echo "		<esallinterfaces>true</esallinterfaces>"
			echo "		<webserver>true</webserver>"
			echo "		<zeroconf>true</zeroconf>"
			echo "	</services>"
			echo "</advancedsettings>"
		} > "/home/kiosk/.kodi/userdata/advancedsettings.xml"
		#	gui settings
		{
			echo "<settings version=\"2\">"
			echo "	<setting id=\"services.webserver\">true</setting>"
			echo "	<setting id=\"services.webserverport\" default=\"true\">8080</setting>"
			echo "	<setting id=\"services.webserverauthentication\" default=\"true\">true</setting>"
			echo "	<setting id=\"services.webserverusername\" default=\"true\">kodi</setting>"
			echo "	<setting id=\"services.webserverpassword\">$remotePass</setting>"
			echo "	<setting id=\"services.webserverssl\" default=\"true\">false</setting>"
			echo "</settings>"
		} > "/home/kiosk/.kodi/userdata/guisettings.xml"

		# fix created file permissions to be owned by the user
		chown -R kiosk:kiosk /home/kiosk/
		# enable kodi2web
		kodi2web enable
		# enable "play on kodi" button
		yesNoCfgSet "/etc/2web/kodi/playOnKodiButton.cfg" "yes"
		# add local kodi instance as remote on the server
		playerLink="kodi:$remotePass@localhost:8080"
		# create the sum for the config so the web interface can remove it
		playerFileSum=$(echo "$playerLink" | md5sum | cut -d' ' -f1)
		# add this player to the players
		{
			echo "$playerLink"
		} > "/etc/2web/kodi/players.d/$playerFileSum.cfg"
		# write the help messages
		echo "################################################################################"
		echo "# If you have ran this command multuple times you may want to remove old       #"
		echo "# players in the settings with different random passowrds.                     #"
		echo "################################################################################"
		echo "https://settings/kodi.php#kodiPlayerPaths"
		echo "################################################################################"
		clientSetupMessage
	elif [ "$1" == "--rebuild-themes" ] || [ "$1" == "rebuild-themes" ];then
		# reset the theme gen timer
		date "+%s" > "/var/cache/2web/themeGen.cfg"
		# run a update to rebuild the CSS files
		update2web
	elif [ "$1" == "--disable-client" ] || [ "$1" == "disable-client" ];then
		# disable auto launcher for the client
		rm -v /etc/lightdm/lightdm.conf.d/20-autologin-kiosk.conf
		# remove the kiosk user from the system
		deluser --remote-all-files kiosk
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "2web"
	elif [ "$1" == "-R" ] || [ "$1" == "--rescan" ] || [ "$1" == "rescan" ] ;then
		# set the flag to force the re processing of ALL media found on ALL modules.
		date "+%s" > /etc/2web/forceRescan.cfg
		ALERT "A RESCAN of content has been scheduled during the next update of each module."
	else
		# update main components
		# - this builds the base site without anything enabled
		update2web
		# this is the default option to be ran without arguments
		#main --help
		showServerLinks
	fi
}
################################################################################
main "$@"
exit
