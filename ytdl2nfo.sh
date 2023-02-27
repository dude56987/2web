#! /bin/bash
########################################################################
# ytdl2nfo converts metadata from remote websites into nfo libaries
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
source "/var/lib/2web/common"
################################################################################
#set -x
################################################################################
ytdl2kodi_caption(){
	# ytdl2kodi_caption "pathToImage" "captionToUse"
	if ! test -f /etc/2web/ytdl/captionFont.cfg;then
		# create the defaut font
		echo "OpenDyslexic-Bold" > /etc/2web/ytdl/captionFont.cfg
	fi
	captionFont=$(cat /etc/2web/ytdl/captionFont.cfg)
	if echo "$(convert -list font)" | grep "$captionFont";then
		# the caption font chosen exists and can be used
		echo "$chosenFont"
	else
		echo "ERROR: The caption font '$captionFont' does not exist on the current system!" > /etc/2web/ytdl/captionFont.cfg
		echo "Choose from one of the following fonts by editing this file /etc/2web/ytdl/captionFont.cfg" >> /etc/2web/ytdl/captionFont.cfg
		echo "$(convert -list font)" >> /etc/2web/ytdl/captionFont.cfg
	fi
}
################################################################################
getDownloadPath(){
	# check for a user defined download path
	if test -f /etc/2web/ytdl/downloadPath.cfg;then
		# load the config file
		downloadPath=$(cat /etc/2web/ytdl/downloadPath.cfg)
	else
		# if no config exists create the default config
		downloadPath="/var/cache/2web/downloads_nfo/"
		# write the new config from the path variable
		echo "$downloadPath" > /etc/2web/ytdl/downloadPath.cfg
	fi
	echo "$downloadPath"
}
################################################################################
function addToLog(){
	errorType=$1
	errorDescription=$2
	errorDetails=$3
	logPagePath=$4
	{
		# add error to log
		echo -e "<tr class='logEntry $errorType'>"
		echo -e "<td>"
		echo -e "$errorType"
		echo -e "</td>"
		echo -e "<td>"
		echo -e "$errorDescription" | txt2html --extract
		echo -e "</td>"
		echo -e "<td>"
		# convert the error details into html
		echo -e "$errorDetails" | txt2html --extract
		echo -e "</td>"
		echo -e "<td>"
		date "+%D"
		echo -e "</td>"
		echo -e "<td>"
		date "+%R:%S"
		echo -e "</td>"
		echo -e "</tr>"
	} >> "$logPagePath"
}
################################################################################
ytdl2kodi_channel_extractor(){
	################################################################################
	# import and run the debug check
	################################################################################
	# NOTE: look at youtube-dl-selection for ripping info for nfo files
	# NOTE: also use youtube-dl for ripping the link to the video/or downloading
	channelLink=$(echo "$1")
	echo "################################################################################"
	echo "[INFO]: Running Channel Extractor on = '$channelLink'"
	echo "################################################################################"
	# rip the show title
	baseUrl=$(echo "$channelLink" | cut -d'/' -f3)
	#echo "[INFO]: baseUrl 1 cut = '$baseUrl'"
	baseUrl=$(echo "$baseUrl" | cut -d':' -f1)
	#echo "[INFO]: baseUrl 2 cut = '$baseUrl'"
	################################################################################
	echo "[INFO]: Checking for download configuration at '/etc/2web/ytdl/downloadPath.cfg'"
	downloadPath="$(getDownloadPath)"
	echo "[INFO]: DownloadPath set to = $downloadPath"
	################################################################################
	# remove domain prefix since since some sites vary the use of the prefix
	if [ $(echo "$baseUrl" | grep -oP "\." | wc -l) -gt 1 ];then
		echo "[INFO]: baseUrl needs restructuring '$baseUrl'"
		# create a baseurl without the prefix
		newUrl=$(echo "$baseUrl" | cut -d'.' -f2)
		#echo "[INFO]: newUrl 1 = $newUrl"
		newUrl=$(echo "$newUrl.")
		#echo "[INFO]: newUrl 2 = $newUrl"
		tempSufix=$(echo "$baseUrl" | cut -d'.' -f3)
		newUrl=$(echo "$newUrl$tempSufix")
		#echo "[INFO]: newUrl 3 = $newUrl"
		baseUrl="$newUrl"
	fi

	echo "[INFO]: baseUrl = '$baseUrl'"
	if [ "$baseUrl" == "" ];then
		# ignore blank lines
		return
	fi
	################################################################################
	# check for a timer on the channel link
	################################################################################
	if test -f /etc/2web/ytdl/channelCacheUpdateDelay.cfg;then
		# load the cache update delay
		channelCacheUpdateDelay=$(cat /etc/2web/ytdl/channelCacheUpdateDelay.cfg)
	else
		# set the cache update delay to six hours
		channelCacheUpdateDelay="6"
		touch /etc/2web/ytdl/channelCacheUpdateDelay.cfg
		echo "$channelCacheUpdateDelay" > /etc/2web/ytdl/channelCacheUpdateDelay.cfg
	fi
	# check the config database to see if the $channelLink has a entry
	# create a default channel cache
	touch /etc/2web/ytdl/channelUpdateCache.cfg
	#cat "/etc/2web/ytdl/channelUpdateCache.cfg"#DEBUG
	# if that entry exists then
	echo "[INFO]: searching for channelLink : '$channelLink'"
	if grep -q "$channelLink" "/etc/2web/ytdl/channelUpdateCache.cfg";then
		# get the line containing the channel link
		temp=$(grep "$channelLink" "/etc/2web/ytdl/channelUpdateCache.cfg")
		#  check the second field which will be a date
		resetTime=$(echo "$temp" | cut -d " " -f2)
		echo "[INFO]: { resetTime = $resetTime } > { now = ~$(date '+%s' ) }"
		# update the channel updated cache if the reset time is less than the current time
		if [ "$resetTime" -gt "$(date "+%s")" ];then
			# this means the reset time has not yet passed so exit out without downloading
			echo "[WARNING]: The reset time has not yet passed skipping..."
			echo "[INFO]: channelLink : '$channelLink'"
			return
		else
			echo "[INFO]: The reset time has passed, Processing link..."
		fi
	fi

	# create show directory
	#mkdir -p "$downloadPath$showTitle/"
	# download the newgrounds page
	echo "[INFO]: Downloading '$channelLink'"
	################################################################################
	# if the file is a playlist from youtube, rip links from the playlist
	# otherwise run the generic link ripper
	echo "[INFO]: Running playlist link extractor..."
	# run the youtube special link playlist extractor
	linkList=""
	# if the previous playlist has been downloaded
	channelSum=$(echo "$channelLink" | sha256sum | cut -d' ' -f1)
	# cache downloaded playlists for 24 hours
	# true if file is older than 1 day or does not exist
	#set -x
	if cacheCheck "$webDirectory/sums/ytdl_channel_$channelSum.cfg" "1";then
		echo "[INFO]: Updatng the cached playlist..."
		# try to rip as a playlist
		if test -f /usr/local/bin/yt-dlp;then
			tempLinkList=$(/usr/local/bin/yt-dlp --flat-playlist --abort-on-error -j "$channelLink")
			errorCode=$?
			echo "[INFO]: tempLinkList = $tempLinkList"
		else
			tempLinkList=$(yt-dlp --flat-playlist --abort-on-error -j "$channelLink")
			errorCode=$?
			echo "[INFO]: tempLinkList = $tempLinkList"
		fi
		# cache the playlist download
		echo "$tempLinkList" > "$webDirectory/sums/ytdl_channel_$channelSum.cfg"
	else
		echo "[INFO]: Loading the previously cached playlist..."
		touch "$webDirectory/sums/ytdl_channel_$channelSum.cfg"
		# if the playlist is already cached load the playlist, in reverse order
		tempLinkList=$(cat "$webDirectory/sums/ytdl_channel_$channelSum.cfg")
		# add the error code for reading the file so the cache will load, mark true
		errorCode=0
	fi
	# get uploader from the json data and set it as the show title
	showTitle=$(echo "$tempLinkList" | jq -r ".playlist_uploader" | head -1 )
	echo "[INFO]: Channel show title found = $showTitle"

	# list only the urls from the json data retrived
	tempLinkList=$(echo "$tempLinkList" | jq -r ".url")
	echo "[INFO]: tempLinkList after cleanup = $tempLinkList"
	################################################################################

	# the templinklist is the formatted list since the generic link extractor is disabled
	#linkList=$tempLinkList

	echo "[INFO]: error code = '$errorCode'"
	# check if the error code is true
	#if [[ $errorCode -eq 0 ]];then
	if [ $errorCode -eq 0 ];then
		addToLog "Found Links" "Adding links to Linklist" "$tempLinkList" "/var/cache/2web/ytdl2nfo.log"
		for videoId in $tempLinkList;do
			linkList=$(echo -en "$linkList\n$videoId")
		done
	else
		# set the show title for the generic link extractor based on the domain name
		showTitle=$(ytdl2kodi_rip_title "$channelLink")
		# if no custom extractor exists then run the generic link extractor
		echo "[INFO]: Running generic link extractor..."
		################################################################################
		# show errors in curl but not download progress
		webData=$(curl --silent --show-error "$channelLink")
		echo "[INFO]: Webpage data found word count = $(echo $webData | wc -w)"
		################################################################################
		# rip video links
		################################################################################
		# remove contents of head tag from page before parsing
		echo "[INFO]: Looking for links in the webpage..."
		# scan if the page is a rss, if so download enclosures as the linklist
		#if echo "$linkList" | grep -q "<rss ";then
		#	# this is a rss feed so extract the rss enclosures and they act as direct links
		#	# - the enclosure is a direct link to the video/audio file
		#	#linkList=$(echo "$linkList" | grep "enclosure" | sed "s/^.*url=\"//g" | sed "s/\".*$//g")
		#	# pull the link tag, it contains the episode link, this will be processed by yt-dlp
		#	set -x
		#	linkList=$(echo "$linkList" | grep "<link>")
		#	linkList=$(echo "$linkList" | sed "s/^.*<link>//g"| sed "s/<\/link>//g")
		#	linkList=$(echo "$linkList" | sed "s/<\!\[CDATA\[//g" | sed "s/\]\]>//g")
		#	set +x
		#else
			# use hxwls to list all links contained within the webpage
			# Some websites use javascript generated html that can not be parsed without removing backslashes
			linkList=$(echo "$webData" | sed 's/\\//g')
			# run a quick clean pass on the html
			linkList=$(echo "$linkList" | hxclean)
			# normalize the  html add ending tags
			linkList=$(echo "$linkList" | hxnormalize -e)
			# convert any special characters
			#linkList=$(echo "$linkList" | asc2xml )
			# list only the links found in the cleaned up html
			linkList=$(echo "$linkList" | hxwls )
			################################################################################
			# clean up links and remove http
			echo "[INFO]: Cleaning up the link prefixes..."
			# remove the http or https prefix
			linkList=$(echo "$linkList" | sed "s/http:/https:/g")
			linkList=$(echo "$linkList" | sed "s/https://g")
			linkList=$(echo "$linkList" | sed "s/\/\///g")
			# remove leading slashes and double slashes some links create
			echo "[INFO]: Cleaning up the leading slashes..."
			linkList=$(echo "$linkList" | sed "s/^\/\///g")
			linkList=$(echo "$linkList" | sed "s/^\///g")
			# remove links to non webpage content images/javascript/css
			echo "[INFO]: Removing links to non webpage resources..."
			linkList=$(echo "$linkList" | sed "s/^.*\.js$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.js\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.css$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.css\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.png$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.png\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.jpg$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.jpg\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.jpeg$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.jpeg\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.svg$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.svg\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.woff$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.woff\?.*$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.ttf$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\.ttf\?.*$//g")
			# remove links to common website resource pages
			linkList=$(echo "$linkList" | sed "s/^.*\/privacy$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\#privacy$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\/help$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\#help$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\/terms$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\#terms$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\/dmca$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\#dmca$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\/feedback$//g")
			linkList=$(echo "$linkList" | sed "s/^.*\#feedback$//g")
			# remove links to help.website.com type urls
			linkList=$(echo "$linkList" | sed "s/^https\:\/\/corp\..*.$//g")
			linkList=$(echo "$linkList" | sed "s/^https\:\/\/help\..*.$//g")
			linkList=$(echo "$linkList" | sed "s/^https\:\/\/feedback\..*.$//g")
		#fi
		################################################################################
		# the linklist links must be reformatted to a more standardized format for processing
		# this prevents strange third party website links (Ads) from being processed into episodes
		tempList=""
		for link in $linkList;do
			# if the base url is not in the link found try adding it to the start
			# without absolute paths the processing wont work
			if ! echo "$link" | grep -q "$baseUrl";then
				#echo "[INFO]: Phase 1 : $link"
				link="$(echo "$baseUrl/$link")"
				#echo "[INFO]: Phase 2 : $link"
				link="$(echo "$link" | sed "s/\/\/\//\//g")"
				#echo "[INFO]: Phase 3 : $link"
				link="$(echo "$link" | sed "s/\/\//\//g")"
				#echo "[INFO]: Phase 4 : $link"
			fi
			#link="https://$link"
			#link="$link"
			################################################################################
			# begin checking for problem file types
			################################################################################
			if echo "$link" | grep -q ".css";then
				# this is not a video file link its a zip file so ignore it
				echo "$selection" >> $previousDownloadsPath
				echo "Incorrect format CSS"
			elif echo "$link" | grep -q ".png";then
				# this is not a video file link its a zip file so ignore it
				echo "$selection" >> $previousDownloadsPath
				echo "Incorrect format PNG"
			elif echo "$link" | grep -q ".jpg";then
				# this is not a video file link its a zip file so ignore it
				echo "$selection" >> $previousDownloadsPath
				echo "Incorrect format JPG"
			elif echo "$link" | grep -q ".ico";then
				# this is not a video file link its a zip file so ignore it
				echo "$selection" >> $previousDownloadsPath
				echo "Incorrect format ICO"
			elif echo "$previousDownloads" | grep -q "$link";then
				# if download was found to already have been processed
				echo "'$link' was found in $previousDownloadsPath"
				echo "This download has already been processed..."
			else
				# if all tests have been passed add the link
				tempList=$(echo -e "$tempList\n$link")
			fi
		done
		# cleanup blank lines in the tmp list and rename it to linklist
		linkList=$(echo "$tempList" | tr -s '\n')
	fi
	################################################################################
	# sort out duplicate links
	echo "[INFO]: Removing non-unique entries..."
	# Join adjacent unique lines
	#linkList=$(echo "$linkList" | uniq)
	# reverse sort of list since most lists show newest at the top of the webpage
	echo "[INFO]: Reversing list because most pages list newest to oldest..."
	linkList=$(echo "$linkList" | tac)
	################################################################################
	# check for known individual sites video url patterns, this reduces the haystack
	if echo "$showTitle" | grep -q "newgrounds";then
		linkList=$(echo "$linkList" | grep "/portal/view/")
		# remove the leading newgrounds.com from links
		linkList=$(echo "$linkList" | sed "s/^.*\/portal/\/portal/g")
	fi
	#if echo "$showTitle" | grep -q "youtube";then
	#	linkList=$(echo "$linkList" | grep "watch?v=")
	#fi
	################################################################################
	# after cleaning the link should be compared to the previous downloads
	# and removed if it has already been processed
	################################################################################
	# create previous downloads list if it does not already exist
	mkdir -p /etc/2web/ytdl/previousDownloads/
	mkdir -p "/var/cache/2web/ytdl/processedSums/"
	tempSum=$(echo -n "$channelLink" | sha256sum | cut -d' ' -f1)
	# make list channel specific
	previousDownloadsPath="/etc/2web/ytdl/previousDownloads/$tempSum.cfg"
	touch "$previousDownloadsPath"
	previousDownloads=$(cat "$previousDownloadsPath")
	echo "[INFO]: Link List = $linkList"
	# get the number of links
	echo "[INFO]: Link List entries = $(echo \"$linkList\" | wc -l)"
	# create the channel metadata path
	mkdir -p "/etc/2web/ytdl/meta/"
	# write the channel metadata for total episodes
	totalEpisodes=$(echo "$linkList" | wc -l)
	channelId=$(echo "$channelLink" | sed "s/[[:punct:]]//g")
	################################################################################
	# load up the episode processing limit
	if test -f /etc/2web/ytdl/episodeProcessingLimit.cfg;then
		# load the config file
		episodeProcessingLimit=$(cat /etc/2web/ytdl/episodeProcessingLimit.cfg)
	else
		# if no config exists create the default config
		episodeProcessingLimit="40"
		# write the new config from the path variable
		echo "$episodeProcessingLimit" > /etc/2web/ytdl/episodeProcessingLimit.cfg
	fi
	################################################################################
	mkdir -p /tmp/ytdl2kodi/
	processedEpisodes=0
	# merge existing
	#$oldLinks=$(cat /etc/2web/ytdl/previousDownloads.cfg)
	#$linklist=$(echo -e "$linkList\n$oldLinks")
	# remove entries that exist in previousDownloads.cfg
	# this requires the episodes to be stored inside of the series individually
	#$linkList=$(echo "$linkList" | uniq -u)
	#echo $linkList
	for link in $linkList;do
		# episode processing limit should be checked before any work
		# is done in processing the episode
		if [ $processedEpisodes -ge $episodeProcessingLimit ];then
			echo "[INFO]: Exceeded Episode Processing Limit, skipping rendering episode..."
			return
		fi
		echo "[INFO]: Preprocessing '$link' ..."
		echo "[INFO]: Running metadata extractor on '$link' ..."
		if [ "$link" == "$channelLink" ];then
			# this means a link was found to the channel itself, this can cause problems
			echo "[INFO]: Found link to the channel on the channel page..."
		else
			# check links aginst existing stream files to pervent duplicating the work
			if echo "$@" | grep -q "\-\-username";then
				echo "[INFO]: Running username video extraction..."
				if ytdl2kodi_video_extractor "$link" "$channelLink" "$showTitle" --username;then
					processedEpisodes=$(($processedEpisodes + 1))
				fi
			else
				echo "[INFO]: Running video extraction...."
				if ytdl2kodi_video_extractor "$link" "$channelLink";then
					processedEpisodes=$(($processedEpisodes + 1))
				fi
			fi
		fi
	done
	################################################################################
	# set the timer in the cache after the channel has been extracted
	################################################################################
	temp="$channelLink $(($(date '+%s')+$(($channelCacheUpdateDelay * 60 * 60))))"
	# create the new link or write the link
	if  grep -q "$channelLink" "/etc/2web/ytdl/channelUpdateCache.cfg";then
		# update file to the next update time
		tempFile=""
		for line in cat "/etc/2web/ytdl/channelUpdateCache.cfg";do
			if echo "$line" | grep -q "$channelLink";then
				tempFile="$tempFile$temp\n"
			else
				tempFile="$tempFile$line\n"
			fi
		done
		echo "$tempFile" > "/etc/2web/ytdl/channelUpdateCache.cfg"
	else
		# add the channel to the channel update cache
		echo "$temp" >> /etc/2web/ytdl/channelUpdateCache.cfg
	fi
	# write the channel metadata for lastProcessed.cfg in seconds
	#lastProcessed=$(date "+%s")
}
################################################################################
ytdl2kodi_channel_meta_extractor(){
	################################################################################
	# META DATA EXTRACTOR
	################################################################################
	# NOTE: look at youtube-dl-selection for ripping info for nfo files
	# NOTE: also use youtube-dl for ripping the link to the video/or downloading
	################################################################################
	# import and run the debug check
	################################################################################
	echo "################################################################################"
	echo "# Now extracting metadata for channel from '$2' #"
	echo "################################################################################"
	################################################################################
	# create the show title based on the arguments
	showTitle="$1"
	echo "The showtitle = $1"
	# set the base url
	echo "The Url = $2"
	webpageLink="$2"
	echo "The Channel Url= $3"
	channelUrl="$3"
	baseUrl=$(echo "$webpageLink" | cut -d'/' -f3)
	domain=$(echo "$baseUrl" | cut -d'.' -f1)
	baseUrl="$(echo "https://$baseUrl")"
	echo "Created base url = $baseUrl"
	# generate the swirl amount
	swirlAmount=$(echo "$showTitle" | wc -c)
	################################################################################
	echo "Checking for download configuration at '/etc/2web/ytdl/downloadPath.cfg'"
	downloadPath="$(getDownloadPath)"
	echo "DownloadPath set to = $downloadPath"
	################################################################################
	# create show directory
	mkdir -p "$downloadPath/$showTitle/"
	# create the tvshow.nfo
	seriesFileName="$downloadPath/$showTitle/tvshow.nfo"
	if test -f "$seriesFileName";then
		echo "Series file already exists..."
		echo "Skipping creating series data..."
		return
	else
		# tvshow metadata does not exist so build it
		{
			#echo "<?xml version='1.0' encoding='UTF-8'?>"
			echo "<tvshow>"
			echo "<title>$showTitle</title>"
			echo "<studio>Internet</studio>"
			echo "<genre>Internet</genre>"
			echo "<plot>Source URL: $channelUrl</plot>"
			echo "<premiered>$(date +%F)</premiered>"
			echo "<director>$showTitle</director>"
			echo "</tvshow>"
		} > "$seriesFileName"
	fi
	echo "################################################################################"
	echo "# Metadata extraction finished #"
	echo "################################################################################"
	return
}
################################################################################
ytdl2kodi_depends_check(){
	# Install the most recent version of youtube-dl by using pip3, everything else is too slow to update
	# install youtube-dl from the latest repo
	# install the missing package
	pip3 install --upgrade yt-dlp
}
################################################################################
ytdl2kodi_reset_cache(){
	if test -f /etc/2web/ytdl/downloadPath.cfg;then
		downloadPath="$(cat /etc/2web/ytdl/downloadPath.cfg)"
		# empty the databases
		rm -rv "/etc/2web/ytdl/episodeDatabase/"
		mkdir -p "/etc/2web/ytdl/episodeDatabase/"
		touch "/etc/2web/ytdl/episodeDatabase/.placeholder"
		# remove all previous downloads
		rm -vr $(webRoot)/sums/ytdl_channel_*.cfg
		rm -vr "/etc/2web/ytdl/previousDownloads/" &
		rm -vr "/etc/2web/ytdl/processedSums/" &
		rm -vr "/etc/2web/ytdl/foundLinks/" &
		rm -v "/etc/2web/ytdl/channelUpdateCache.cfg" &
		rm -vr "/var/cache/2web/ytdl/" &
		# kill all processes
		pkill ytdl2kodi &
	else
		echo "No download path was set, can not clear cache."
	fi
}
################################################################################
ytdl2kodi_rip_title(){
	webpageLink=$1
	# extract the domain name from the link
	showTitle=$(echo "$webpageLink" | cut -d'/' -f3)
	showTitle=$(echo "$showTitle" | cut -d':' -f1)
	tempCount=$(echo "$showTitle" | grep -o "\." | wc -l)
	tempCount=$(expr "$tempCount")
	if [ $tempCount -gt 1  ];then
		showTitle=$(echo "$showTitle" | cut -d'.' -f2)
	else
		showTitle=$(echo "$showTitle" | cut -d'.' -f1)
	fi
	echo "$showTitle"
	return
}
################################################################################
ytdl2kodi_sleep(){
	################################################################################
	# checking sleepTime.cfg to see the max wait time between downloads
	echo "Loading up sleep config '/etc/2web/ytdl/sleepTime.cfg'"
	if test -f /etc/2web/ytdl/sleepTime.cfg;then
		# load the config file
		sleepTime=$(cat /etc/2web/ytdl/sleepTime.cfg)
	else
		# if no config exists create the default config
		sleepTime="30"
		# write the new config from the path variable
		echo "$sleepTime" > /etc/2web/ytdl/sleepTime.cfg
	fi
	################################################################################
	# sleep between 0 and 10 seconds between each link download
	if [ $sleepTime -gt 0 ];then
		tempTime="$(($RANDOM % $sleepTime))"
		echo "Waiting for '$tempTime' seconds..."
		sleep "$tempTime"
	else
		echo "Wait time disabled..."
		return
	fi
	################################################################################
	return
}
################################################################################
function sitePaths(){
	# check for server libary config
	if ! test -f /etc/2web/ytdl/sources.cfg;then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			cat /etc/2web/config_default/ytdl2nfo_websiteSources.cfg
		} >> "/etc/2web/ytdl/sources.cfg"
	fi
	# write path to console
	grep -v "^#"  "/etc/2web/ytdl/sources.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/ytdl/sources.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		grep -v "^#" "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
function userPaths(){
	# check for server libary config
	if ! test -f /etc/2web/ytdl/usernameSources.cfg;then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			cat /etc/2web/config_default/ytdl2nfo_usernameSources.cfg
		} >> "/etc/2web/ytdl/usernameSources.cfg"
	fi
	# write path to console
	grep -v "^#" "/etc/2web/ytdl/usernameSources.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/ytdl/usernameSources.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		grep -v "^#" "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
ytdl2kodi_update(){
	################################################################################
	# import and run the debug check
	# check dependencies to get the latest version of youtube-dl
	#ytdl2kodi_depends_check
	################################################################################
	# create a limit to set the number of channels that can be processed at once
	# running ytdl2kodi_update every hour with a limit of one means only one channel
	# be processed every hour it is run
	if test -f /etc/2web/ytdl/channelProcessingLimit.cfg;then
		# load the config file
		channelProcessingLimit=$(cat /etc/2web/ytdl/channelProcessingLimit.cfg)
	else
		# if no config exists create the default config
		channelProcessingLimit="1000"
		# write the new config from the path variable
		echo "1000" > /etc/2web/ytdl/channelProcessingLimit.cfg
	fi
	################################################################################
	# create the blank temporary database
	mkdir -p /tmp/ytdl2kodi/
	mkdir -p /etc/2web/ytdl/meta/
	################################################################################
	mkdir -p "$(getDownloadPath)"
	################################################################################
	currentlyProcessing=0
	################################################################################
	# for each link in the sources
	siteLinkList=$(sitePaths)
	echo "Processing sources..."
	echo "Site Link List = '$siteLinkList'"
	siteLinkList="$(echo "$siteLinkList" | tr -s '\n')"
	siteLinkList="$(echo "$siteLinkList" | sed "s/\n/ /g")"
	echo "Site Link List = '$siteLinkList'"
	#echo "$siteLinkList" | while read link;do
	for link in $siteLinkList;do
		echo "[INFO]:Checking site link '$link' ..."
		currentlyProcessing="$(($currentlyProcessing + 1))"
		if [ $currentlyProcessing -gt $(($channelProcessingLimit - 1)) ];then
			echo "[INFO]: Channel Processing Limit Reached!"
			break
		fi
		if [ "$link" == "" ];then
			echo "'$link' is blank..."
		else
			echo "Running channel metadata extractor on '$link'..."
			# check links aginst existing stream files to pervent duplicating the work
			ytdl2kodi_channel_extractor "$link"
		fi
	done
	################################################################################
	# reset currently processing for the
	currentlyProcessing=0
	################################################################################
	# for each link in the users sources
	userlinkList=$(userPaths)
	echo "Processing user sources..."
	echo "User Link List = '$userlinkList'"
	userlinkList="$(echo "$userlinkList" | tr -s '\n')"
	userlinkList="$(echo "$userlinkList" | sed "s/\n/ /g")"
	echo "User Link List post cleanup = '$userlinkList'"
	#echo "$userLinkList" | while read -r link;do
	for link in $userlinkList;do
		echo "[INFO]:Checking user link '$link' ..."
		currentlyProcessing="$(($currentlyProcessing + 1))"
		if [ $currentlyProcessing -gt $(($channelProcessingLimit - 1)) ];then
			echo "[INFO]: Channel Processing Limit Reached!"
			break
		fi
		if [ "$link" == "" ];then
			echo "'$link' is blank..."
		else
			echo "[INFO]:Running channel metadata extractor on '$link' ..."
			# check links aginst existing stream files to pervent duplicating the work
			ytdl2kodi_channel_extractor "$link" --username
		fi
	done
	################################################################################
	return
}
################################################################################
validString(){
	stringToCheck="$1"
	echo "[INFO]: Checking string '$stringToCheck'"
	# convert string letters to all uppercase and look for returned NULL string
	# jq returns these strings instead of failing outright
	if echo "${stringToCheck^^}" | grep "NULL";then
		# this means the function is a null string returned by jq
		echo "[WARNING]:string is a NULL value"
		return 2
	elif [ 2 -ge $(expr length "$stringToCheck") ];then
		# this means the string is only one character
		echo "[WARNING]:String length is less than one"
		return 1
	else
		# all checks have been passed the string is correct
		echo "[INFO]: String passed all checks and is correct"
		return 0
	fi
}
########################################################################
addProcessedSum(){
	selectionToAdd=$1
	channelSumToAdd=$2
	# generate the sum
	selectionToAdd=$(echo "$selectionToAdd" | md5sum | cut -d' ' -f1)
	# add the selection sum to the list of previously downloaded links, if they are not already present
	touch "/var/cache/2web/ytdl/processedSums/$channelSumToAdd.cfg"
	if ! grep -q "$selectionToAdd" "/var/cache/2web/ytdl/processedSums/$channelSumToAdd.cfg";then
		echo "$selectionToAdd" >> "/var/cache/2web/ytdl/processedSums/$channelSumToAdd.cfg"
		return 0
	else
		return 1
	fi
}
################################################################################
checkProcessedSum(){
	selectionToCheck=$1
	channelSumToCheck=$2
	# generate the sum
	selectionToCheck=$(echo "$selectionToCheck" | md5sum | cut -d' ' -f1)
	touch "/var/cache/2web/ytdl/processedSums/$channelSumToCheck.cfg"
	if grep -q "$selectionToCheck" "/var/cache/2web/ytdl/processedSums/$channelSumToCheck.cfg";then
		# return true if the selection is found
		return 0
	else
		return 1
	fi
}
################################################################################
ytdl2kodi_video_extractor(){
	################################################################################
	# VIDEO EXTRACTOR
	################################################################################
	# NOTE: also use youtube-dl for ripping the link to the video/or downloading
	################################################################################
	# import and run the debug check
	################################################################################
	selection=$1
	channelLink=$2
	################################################################################
	channelSum=$(echo "$channelLink" | sha256sum | cut -d' ' -f1)
	################################################################################
	# create previous downloads list if it does not already exist
	mkdir -p /etc/2web/ytdl/previousDownloads/
	mkdir -p /etc/2web/ytdl/processedSums/
	# make list channel specific
	previousDownloadsPath="/etc/2web/ytdl/previousDownloads/$channelSum.cfg"
	#selectionSum=$(echo -n "$selection" | sha256sum | cut -d' ' -f1)
	if checkProcessedSum "$selection" "$channelSum";then
		echo "The data for the selection '$selection' has already been processed."
		return 1
	fi
	#if test -f /etc/2web/ytdl/previousSums/$channelSum.cfg;then
	#	# if a set of linksums was found, check it for the link sum
	#	# if the sha256sum of the link has already been processed
	#	if grep -q "$selectionSum" "/etc/2web/ytdl/processedSums/$channelSum.cfg";then
	#		# exit the function because it has already been processed
	#		echo "The data for the selection '$selection' has already been processed."
	#		return
	#	fi
	#fi
	# check that this is a a link preceded by https://
	#if echo "$selection" | grep -q "https://";then
	#	echo "Correctly formatted link $selection"
	#else
	#	# exit the function because it is incorrectly formatted
	#	echo "The data for the selection '$selection' is incorrectly formated."
	#	return
	#fi
	################################################################################
	echo "################################################################################"
	echo "# Now extracting '$selection' #"
	echo "################################################################################"
	################################################################################
	mkdir -p /tmp/ytdl2kodi/
	################################################################################
	echo "Checking for download configuration at '/etc/2web/ytdl/downloadPath.cfg'"
	# check for a user defined download path
	downloadPath="$(getDownloadPath)"
	echo "DownloadPath set to = $downloadPath"
	# create the path if it does not exist, then move into it
	mkdir -p "$downloadPath"
	################################################################################
	echo "Checking for video fetch time limit '/etc/2web/ytdl/videoFetchTimeLimit.cfg'"
	if ! test -f "/etc/2web/ytdl/videoFetchTimeLimit.cfg";then
		# if no episodes have bee set create the variable
		echo "30" > "/etc/2web/ytdl/videoFetchTimeLimit.cfg"
	fi
	# create the episode numbering specific to the show
	timeLimitSeconds=$(cat "/etc/2web/ytdl/videoFetchTimeLimit.cfg")
	################################################################################
	echo "Extracting metadata from '$selection'..."
	# print youtube-dl command for debugging
	addToLog "INFO" "Extracting Metadata" "$selection" "/var/cache/2web/ytdl2nfo.log"
	# use the pip package if it is available
	if test -f /usr/local/bin/yt-dlp;then
		echo "timeout --preserve-status \"$timeLimitSeconds\" /usr/local/bin/yt-dlp -j --abort-on-error --no-playlist --playlist-end 1 \"$selection\""
		info=$(timeout --preserve-status "$timeLimitSeconds" /usr/local/bin/yt-dlp -j --abort-on-error --no-playlist --playlist-end 1 "$selection")
	else
		echo "timeout --preserve-status \"$timeLimitSeconds\" yt-dlp -j --abort-on-error --no-playlist --playlist-end 1 \"$selection\""
		info=$(timeout --preserve-status "$timeLimitSeconds" yt-dlp -j --abort-on-error --no-playlist --playlist-end 1 "$selection")
	fi
	infoCheck=$?
	if [ $infoCheck -eq 0 ];then
		echo "Return code of ytdl = $infoCheck"
		echo "Extraction Successfull!"
	elif [ $infoCheck -gt 123 ];then
		# exit code 124 or greater only comes from a timeout happening with the timeout command
		# this means that youtube-dl ran for more than $timeLimitSeconds and was stopped
		addProcessedSum "$selection" "$channelSum"
		echo "The info extractor timed out after $timeLimitSeconds seconds..."
		echo "Skipping..."
		echo
		return 1
	else
		echo "Return code of ytdl = $infoCheck"
		# if the info returns a failure code
		# add it to the previous downloads to stop rescanning repeated links
		addProcessedSum "$selection" "$channelSum"
		echo "The info extractor failed..."
		echo "Skipping..."
		echo
		return 1
	fi
	################################################################################
	formatCheck=$(echo "$info" | jq ".formats[0].url")
	formatCheck=$(echo "$formatCheck" | sed 's/\"//g')
	# if spaces somehow made it into the formating, cut the first field
	formatCheck=$(echo "$formatCheck" |  cut -d" " -f1)
	if ! validString "$formatCheck";then
		# this is not a video file link so ignore it
		addProcessedSum "$selection" "$channelSum"
		#check if the link contains a url of any kind as the link to play
		echo "The url to play this video can not be found."
		echo "Found URL = '$formatCheck'"
		echo "Skipping..."
		echo
		return 1
	fi
	if echo "$formatCheck" | grep ".zip";then
		# this is not a video file link its a zip file so ignore it
		addProcessedSum "$selection" "$channelSum"
		echo "This is a zip file not a video link"
		echo "Skipping..."
		echo
		return 1
	fi
	if echo "$formatCheck" | grep ".swf";then
		# this is not a video file link its a zip file so ignore it
		addProcessedSum "$selection" "$channelSum"
		echo "This is a swf file not a video link"
		echo "Skipping..."
		echo
		return 1
	fi
	################################################################################
	# Build the filename from the download path id and title
	echo 'Building filename...'
	# get the id and the title info
	id=$(echo "$info" | jq -r ".id?" | xargs -0 | cut -d$'\n' -f1 )
	title=$(echo "$info" | jq -r ".fulltitle?" | xargs -0 | cut -d$'\n' -f1 )
	titleGet=$?
	# write the webpage as the plot
	#plot=$(echo "$info" | jq -r ".webpage_url")
	plot=$(echo "$info" | jq -r ".description" | xargs -0)
	# figure out airdate
	airdate=$(echo "$info" | jq -r ".upload_date")
	# extract the date "20200101" year,month,day
	airdate_year=$(echo "$airdate" | cut -b 1-4 )
	airdate_month=$(echo "$airdate" | cut -b 5-6 )
	airdate_day=$(echo "$airdate" | cut -b 7-8 )
	# rebuild the airdate and format it
	airdate="$airdate_year/$airdate_month/$airdate_day"
	airdate=$(date -d "$airdate" "+%F" )
	if ! validString "$airdate";then
		# if the airdate can not be found generate one
		airdate=$(date "+%F")
	fi
	# figure out the episode season
	episodeSeason=$(date -d "$airdate" "+%Y")
	# get uploader
	uploader=$(echo "$info" | jq -r ".uploader" | xargs -0 | cut -d$'\n' -f1 )
	################################################################################
	# create the show title
	if echo "$@" | grep "\-\-username";then
		#if [ 5 -lt $(expr length "$uploader") ];then
		if validString "$uploader";then
			# create the username from the uploader, this is toggled by a switch
			showTitle="$uploader"
		else
			echo "No uploader name was found and use username as showname was selected."
			echo "Uploader = '$uploader'"
			# if this download is not listed in previousDownloads then add it
			addProcessedSum "$selection" "$channelSum"
			echo "Skipping video..."
			echo
			return 1
		fi
	else
		# create the showtitle from the base url this is default
		showTitle=$(ytdl2kodi_rip_title "$selection")
	fi
	echo "Show Title = $showTitle"
	if echo "$@" | grep "\-\-username";then
		# the show title should be the same as the playlist show title
		if [ "$showTitle" != "$3" ];then
			# this means this link is invalid
			echo "Episode processing stopped, This video link is a diffrent username."
			# if this download is not listed in previousDownloads then add it
			addProcessedSum "$selection" "$channelSum"
			echo "Skipping video..."
			echo
			return 1
		fi
	fi
	################################################################################
	# create season directory
	downloadPath="$downloadPath$showTitle/Season $episodeSeason/"
	echo "DownloadPath set to = $downloadPath"
	mkdir -p "$downloadPath"

	################################################################################
	# if titleget failed and plot get was successfull
	if ! validString "$title";then
		# the title is less than 4 characters
		if [ 5 -lt $(expr length "$plot") ];then
			# the plot was found to be greater than 5 characters
			# add the show id because this title schema can cause repeats
			################################################################################
			# list the first line of the plot as the title
			title=$(echo "$plot" | cut -d$'\n' -f1 )
		fi
	fi
	if [ $(expr length "$title") -lt 5 ];then
		#this means no viable title could be found or created from the plot
		echo "No title was found or created..."
		addProcessedSum "$selection" "$channelSum"
		echo
		return 1
	fi
	################################################################################
	# create the directory for the show data if it does not exist
	mkdir -p "/etc/2web/ytdl/episodeDatabase/"
	# the database tracks the episode number based on all previous episodes
	if ! test -f "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg";then
		# if no episodes exist create the variable
		echo "0" > "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg"
	fi
	#ALERT
	#set -x
	if test -f "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg";then
		# create the episode numbering specific to the show
		epNum=$(cat "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg")
		# increment episode number but dont save because it may fail
		epNum=$(( epNum + 1 ))
	else
		epNum="1"
	fi
	#set +x
	#ALERT
	#read
	# if the episode number is less than 10 add a 0 prefix for proper file sorting
	#if [ $epNum -lt 10 ];then
	#	# format extra zero
	#	epNum="0$epNum"
	#fi
	################################################################################
	echo "downloadPath = '$downloadPath' + title = '$title' + '-' + id = '$id'"
	# add season and episode numbering
	echo "The title is '$title'"
	#echo "Title length is $(expr length "$title"), is this greater than 5"
	if [ 5 -lt $(echo "$title" | wc -c) ];then
		# cleanup the filename
		tempTitle=$(echo "$title" | sed "s/[[:punct:]]//g")
		fileName="$showTitle - s${episodeSeason}e$epNum - $tempTitle"
	else
		fileName="$showTitle - s${episodeSeason}e$epNum"
	fi
	shortName="$fileName"
	#add the downloadpath
	fileName="$downloadPath$fileName"
	################################################################################
	# generate the plot
	if ! validString "$plot";then
		# no plot could be found, generate a generic one
		if ! validString "$uploader";then
			# if there is a uploader id
			plot=$(echo "Video from $selection Created by $uploader")
		else
			plot=$(echo "Video from $selection Created by $showTitle")
		fi
	fi
	################################################################################
	echo "File path set to $fileName"
	# check if the file already exists from previous runs
	#if test -f "$fileName.nfo";then
	#	echo "The data for $filename.nfo has already been processed."
	#	# this is the only failure mode that occurs after episode numbering
	#	# so decremnt the episode number to prevent gaps
	#	addProcessedSum "$selection" "$channelSum"
	#	echo "Skipping..."
	#	echo
	#	return
	#fi
	################################################################################
	# build stream file for video or download video file
	# save stream file sources
	touch "$fileName.strm"
	################################################################################
	# make the found links directory if it does not exist, and the channel specific list
	mkdir -p /etc/2web/ytdl/foundLinks/
	foundLinksPath=/etc/2web/ytdl/foundLinks/$channelSum.cfg
	################################################################################
	echo "[INFO]: Checking for found video links '$foundLinksPath'"
	################################################################################
	#set -x
	# check the length of the source url is long enough to be a link
	if [ 5 -gt "$(echo "$selection" | wc -c)" ];then
		# check if a custom resolver has been specified with a custom url
		#if test -f /etc/2web/ytdl/customResolverUrl.cfg;then
			# custom resolver should take presidence over system default resolvers
		#	resolverString=$(cat /etc/2web/ytdl/customResolverUrl.cfg)
			# - This should not affect the $selection variable since it is
			#   also used for thumbnail generation
			# - Wrap the selection in quotes for the custom resolver
		#	echo "$resolverString\"$selection\"" > "$fileName.strm"
		#else
		echo "[INFO]: There is no video link available!"
		echo "[INFO]: Skipping..."
		#fi
		return 1
	else
		touch "$foundLinksPath"
		echo "[INFO]: Writing source url to strm file..."
		echo "$selection" > "$fileName.strm"
		# mark link as found
		echo "$selection" >> "$foundLinksPath"
		# add the selection sum to the list of previously downloaded links, if they are not already present
		addProcessedSum "$selection" "$channelSum"
		echo
	fi
	#set +x
	################################################################################
	# create the file if it dont exist
	#touch $foundLinksPath
	# check the discoverd video url
	#if grep "$selection" "$foundLinksPath";then
	#	# if download was found to already have been processed
	#	echo "[INFO]: This download has already been processed..."
	#	echo "[INFO]: '$selection' matches another previously downloaded video in this series..."
	#	echo
	#	return
	#else
	#	# if the url has not been added, add it
	#	touch $foundLinksPath
	#	echo "[INFO]: Writing video URL to $foundLinksPath"
	#	echo "$selection"
	#	echo "$selection" >> "$foundLinksPath"
	#	addProcessedSum "$selection" "$channelSum"
	#fi
	################################################################################
	# get thumbnail data if it is available
	thumbnail=$(echo "$info" | jq -r ".thumbnail")
	################################################################################
	# if the thumbnail lists nothing or returned in error generate a thumbnail from the webpage link
	echo "[INFO]: Analyzing thumbnail '$thumbnail'"
	#echo "5 is greater than $(expr length "$thumbnail")"
	# if the thumbnail get fails
	# try to create a thumbnail from the discovered video url using ffmpeg
	#if ! validString "$thumbnail";then
	#	echo "[INFO]: Attempting to create thumbnail from video source..."
	#	touch "$fileName-thumb.png"
	#	tempFileSize=0
	#	tempTimeCode=1
	#	# - force the filesize to be large enough to be a complex descriptive thumbnail
	#	# - filesize of images is directly related to visual complexity
	#	while [ $tempFileSize -lt 15000 ];do
	#		# - place -ss in front of -i for speed boost in seeking to correct frame of source
	#		# - tempTimeCode is in seconds
	#		# - '-y' to force overwriting the empty file
	#		ffmpeg -y -ss $tempTimeCode -i "$selection" -vframes 1 "$fileName-thumb.png"
	#		# resize the image before checking the filesize
	#		convert "$fileName-thumb.png" -resize 400x200\! "$fileName-thumb.png"
	#		# get the size of the file, after it has been created
	#		tempFileSize=$(cat "$fileName-thumb.png" | wc --bytes)
	#		# - increment the timecode to get from the video to find a thumbnail that is not
	#		#   a blank screen
	#		tempTimeCode=$(($tempTimeCode + 1))
	#		# if there is no file large enough after 60 attempts, the first 60 seconds of video
	#		if [ $tempTimeCode -gt 60 ];then
	#			# break the loop
	#			tempFileSize=16000
	#			rm "$fileName-thumb.png"
	#		elif [ $tempFileSize -eq 0 ];then
	#			# break the loop, no thumbnail could be generated at all
	#			# - Blank white or black space takes up more than 0 bytes
	#			# - A webpage generated thumbnail will be created as a alternative
	#			rm "$fileName-thumb.png"
	#			tempFileSize=16000
	#		fi
	#	done
	#fi
	# if ffmpeg can not create a thumbnail, generate a thumbnail from the webpage
	#if ! validString "$thumbnail";then
	#	writeCaption=""
	#	# generate a image from the webpage for the thumbnail
	#	webpageUrl=$(echo "$info" | jq -r ".webpage_url" | sed "s/http:/https:/g")
	#	echo "[INFO]: Creating thumbnail from webpage '$webpageUrl'"
	#	# complex commands are made easier to debug when you can see the contents being fed in
	#	if ! test -f "$fileName-thumb.png";then
	#		echo "wkhtmltoimage --format png --enable-javascript --javascript-delay 1000 --width 1920 --disable-smart-width --height 1080 \"$webpageUrl\" \"$fileName-thumb.png\""
	#		wkhtmltoimage --format png --enable-javascript --javascript-delay 1000 --width 1920 --disable-smart-width --height 1080 "$webpageUrl" "$fileName-thumb.png"
	#		# if the file was created successfully, write title over webpage image
	#		writeCaption="yes"
	#	fi
	#	if ! test -f "$fileName-thumb.png";then
	#		echo "[INFO]: Webpage thumbnail could not be downloaded, generating plasma image"
	#		# if no webpage was downloaded
	#		convert -size 400x200 plasma: "$fileName-thumb.png"
	#		# write the title over the plasma
	#		writeCaption="yes"
	#	fi
	#	# resize the thumbnail
	#	convert "$fileName-thumb.png" -resize 400x200\! "$fileName-thumb.png"
	#	# write the caption if no real thumbnail could be found
	#	if echo "$writeCaption" | grep "yes";then
	#		# add a caption of the video title to the downloaded image of the webpage
	#		# the caption must be smaller than the image to prevent cutting off edges
	#		convert "$fileName-thumb.png" -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 2 -style Bold -size 300x100 -gravity center caption:"$title" -composite "$fileName-thumb.png"
	#	fi
	#else
	#	echo "[INFO]: Thumbnail was found with extractor!"
	#fi
	################################################################################
	# save nfo file
	touch "$fileName.nfo"
	{
		#echo "<?xml version='1.0' encoding='UTF-8'?>"
		echo "<episodedetails>"
		echo "<season>$episodeSeason</season>"
		echo "<episode>$epNum</episode>"
		# set the series title
		echo "<showtitle>$showTitle</showtitle>"
		# Set the title grabed previously to build the filename
		echo "<title>$title</title>"
		if [ 5 -lt $(expr length "$thumbnail") ];then
			echo "<thumb>$thumbnail</thumb>"
		fi
		# get the director information
		echo "<director>$uploader</director>"
		echo "<credits>$uploader</credits>"
		# get the runtime if it is available
		runtime=$(echo "$info" | jq -r ".duration")
		if [ "$runtime" -gt 0 ];then
			echo "<runtime>$runtime</runtime>"
		else
			# default runtime guess is 15 minutes
			echo "<runtime>15 min</runtime>"
		fi
		echo "<plot>$plot</plot>"
		echo "<aired>$airdate</aired>"
		# set the last processed time of the episode
		echo "<lastProcessed>$(date "+%s")</lastProcessed>"
		# end the nfo file
		echo "</episodedetails>"
	} > "$fileName.nfo"
	# display the created nfo file
	echo "--------------------------------------------------------------------------------"
	echo "$fileName.nfo"
	echo "--------------------------------------------------------------------------------"
	cat "$fileName.nfo"
	echo "--------------------------------------------------------------------------------"
	# add the current time of day to the airdate to make newly added videos sort properly
	tempTimeHours=$(date "+%H" | sed "s/^[0]*//g")
	tempTimeMinutes=$(date "+%M" | sed "s/^[0]*//g")
	# convert into seconds
	if echo $tempTimeHours | grep -q "[0123456789]";then
		tempTimeHours=$((10#$tempTimeHours * 60 * 60))
	else
		tempTimeHours=$((0 * 60 * 60))
	fi
	if echo $tempTimeMinutes | grep -q "[0123456789]";then
		tempTimeMinutes=$((10#$tempTimeMinutes * 60))
	else
		tempTimeMinutes=$((0 * 60))
	fi
	# add the current time to the airdate
	tempTime=$(date -d "$airdate" "+%s")
	tempTime=$(($tempTime + $tempTimeHours + $tempTimeMinutes))
	# set the file creation date for the nfo file
	#touch -a -m -d "$airdate" "$fileName.nfo"
	touch -d "@$tempTime" "$fileName.nfo"
	touch -d "@$tempTime" "$fileName.strm"
	if test -f "$fileName-thumb.png";then
		# if a thumbnail image exists change the date on it too
		touch -d "@$tempTime" "$fileName-thumb.png"
	fi
	################################################################################
	# wait random period after processing link unless debug flag is set
	ytdl2kodi_sleep
	################################################################################
	# now that everything has finished properly add it to the list so it will be
	# skipped on the next encounter to prevent work being duplicated
	#ALERT
	#set -x
	# if the selection does not exist inside of the previous downloads
	#if ! grep "$selection" "$previousDownloadsPath";then
	#	#	# cast the string to a number, removes extra 0 prefixes
	#	#epNum=$(echo "$epNum" | sed "s/^[0]*//g")
	#	#epNum=$(( $epNum + 1 ))
	#	# set the new episode number and save it
	#	echo "$epNum" > "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg"
	#	# if this download is not listed in previousDownloads then add it
	#	echo "$selection" >> "$previousDownloadsPath"
	#fi
	# add the download to previous downloads
	#echo "$selection" >> "$previousDownloadsPath"
	#set -x
	#ALERT
	# add the selection sum to the list of previously downloaded links, if they are not already present
	#if ! checkProcessedSum "$selection" "$channelSum";then
		# update the episode number
		echo "$epNum" > "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg"
		# mark the episode as processed
		addProcessedSum "$selection" "$channelSum"
	#fi
	#set +x
	#ALERT
	#read
	# add the selection sum to the list of previously downloaded links, if they are not already present
	#if ! grep -q "$selectionSum" "/etc/2web/ytdl/processedSums/$channelSum.cfg";then
	#	echo "$selectionSum" >> "/etc/2web/ytdl/processedSums/$channelSum.cfg"
	#fi
	################################################################################
	# Create the nfo file last since it is the switch this entire script checks for
	################################################################################
	#seriesFileName="$downloadPath/$showTitle/tvshow.nfo"
	#if ! test -f "$seriesFileName";then
	#	{
	#		echo "<?xml version='1.0' encoding='UTF-8'?>"
	#		echo "<tvshow>"
	#		echo "<title>$showTitle</title>"
	#		echo "<studio>Internet</studio>"
	#		echo "<genre>Internet</genre>"
	#		echo "<plot>Source URL: $channelLink</plot>"
	#		echo "<premiered>$(date +%F)</premiered>"
	#		echo "<director>$showTitle</director>"
	#		echo "</tvshow>"
	#	} > "$seriesFileName"
	#fi
	# The video extraction was successfull, run the channel creator
	if echo "$@" | grep -q "\-\-username";then
		ytdl2kodi_channel_meta_extractor "$showTitle" "$selection" "$channelLink" --username
	else
		ytdl2kodi_channel_meta_extractor "$showTitle" "$selection" "$channelLink"
	fi
	################################################################################
	echo "[INFO]:################################################################################"
	echo "[INFO]:# The extractor has finished #"
	echo "[INFO]:################################################################################"
	return 0
}
################################################################################
function nuke(){
	echo "########################################################################"
	echo "[INFO]: Reseting web cache to blank..."
	downloadPath=$(getDownloadPath)
	rm -rv "$downloadPath"
	# recreate the download path and placeholder
	#rm -rv "$downloadPath"/*
	touch "$downloadPath.placeholder"
	echo "[SUCCESS]: Web cache states reset, update to rebuild everything."
	echo "[SUCCESS]: Site will remain the same until updated."
	echo "[INFO]: Use 'ytdl2nfo update' to generate a new website..."
	echo "########################################################################"
}
################################################################################
main(){
	if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "ytdl2nfo"
		lockProc "ytdl2nfo"
		ytdl2kodi_update
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "ytdl2nfo"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "ytdl2nfo"
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		ytdl2kodi_depends_check
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		lockProc "ytdl2nfo"
		ytdl2kodi_reset_cache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		lockProc "ytdl2nfo"
		nuke
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "ytdl2nfo Version: "
		cat /usr/share/2web/version_ytdl2nfo.cfg
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/ytdl2nfo.txt"
	else
		checkModStatus "ytdl2nfo"
		lockProc "ytdl2nfo"
		ytdl2kodi_update
		drawLine
		echo "NFO Library generated at $(getDownloadPath)"
		drawLine
	fi
}
################################################################################
main "$@"
exit
