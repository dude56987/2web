#! /bin/bash
########################################################################
# ytdl2nfo converts metadata from remote websites into nfo libaries
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
	# Print the download path
	#
	# - This function needs removed because 2web now has a global download path
	#   that can be accessed with downloadRoot()
	#
	# RETURN OUTPUT
	echo "/var/cache/2web/downloads/nfo/"
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
	echo "[INFO]: Downloading '$channelLink'"
	################################################################################
	echo "[INFO]: Running playlist link extractor..."
	linkList=""
	channelSum=$(echo "$channelLink" | sha256sum | cut -d' ' -f1)
	echo "[INFO]: Updating the playlist..."
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
	# get uploader from the json data and set it as the show title
	#showTitle=$(echo "$tempLinkList" | jq -r ".playlist_uploader" | head -1 )

	## get uploader from the json data and set it as the show title
	#if echo "$@" | grep "\-\-username";then
	#	showTitle=$(echo "$tempLinkList" | jq -r ".playlist_uploader" | head -1 )
	#	if ! validString "$showTitle";then
	#		showTitle=$(echo "$tempLinkList" | jq -r ".channel" | head -1)
	#	fi
	#	# final failsafe username
	#	#if ! validString "$showTitle";then
	#	#	showTitle=$(ytdl2kodi_rip_title "$selection")
	#	#fi
	#else
	#	showTitle=$(ytdl2kodi_rip_title "$selection")
	#fi
	#echo "[INFO]: Channel show title found = $showTitle"

	#tempLinkList=$(echo "$tempLinkList" | jq -r ".url")
	linkList=""
	echo "[INFO]: Checking error code = '$errorCode'"
	# check if the error code is true
	#if [[ $errorCode -eq 0 ]];then
	if [ $errorCode -eq 0 ];then
		# mark the playlist download as cached
		#echo "$tempLinkList" > "$webDirectory/sums/ytdl_channel_$channelSum.cfg"
		# list only the urls from the json data retrived
		linkList=$(echo "$tempLinkList" | jq -r ".url")
		echo "[INFO]: linkList after cleanup = $tempLinkList"
	else
		# write in the database that this channel link has failed
		logPagePath="$webDirectory/log/$(date "+%s").log"
		addToLog "ERROR" "ytdl2nfo" "ytdl2nfo could not download playlist from link '$channelLink'" "$logPagePath"
		# exit and do not mark as processed since no playlist/linklist could be retrieved
		# this should also fail out if the network connection is down
		return 1
	fi

	# list only the urls from the json data retrived
	#tempLinkList=$(echo "$tempLinkList" | jq -r ".url")
	#echo "[INFO]: tempLinkList after cleanup = $tempLinkList"

	# reverse sort of list since most lists show newest at the top of the webpage
	echo "[INFO]: Reversing list because most pages list newest to oldest..."
	linkList=$(echo "$linkList" | tac)

	# set the show title based on the data from the latest video posted on the channel playlist
	if echo "$@" | grep "\-\-username";then
		showTitle=""

		#if ! validString "$showTitle";then
		#	echo "Reading '.playlist_title' from playlist entry"
		#	showTitle=$(echo "$tempLinkList" | jq -r ".playlist_title" | head -1 )
		#	echo "Show title set to '$showTitle' from .playlist_title"
		#fi
		if ! validString "$showTitle";then
			echo "Reading '.playlist_uploader' from playlist entry"
			showTitle=$(echo "$tempLinkList" | jq -r ".playlist_uploader" | head -1 )
			echo "Show title set to '$showTitle' from .playlist_uploader"
		fi
		if ! validString "$showTitle";then
			echo "Show title is invalid string '$showTitle'"
			echo "Reading '.channel' name from first playlist entry"
			# failsafe extract channel from json data in tempLinkList
			showTitle=$(echo "$tempLinkList" | head -1 | jq -r ".channel" | head -1)
		fi
		if ! validString "$showTitle";then
			showTitle=$(echo "$tempLinkList" | head -1 | jq -r ".uploader" | head -1)
		fi
		if ! validString "$showTitle";then
			# get the second video from the playlist and compare the uploader name to the first
			# if the first is the same as the second, that means the playlist title is correct
			# if the first and second video have diffrent uploaders go to the next failure mode
			echo "Show title is invalid string '$showTitle'"
			echo "Downloading json data from latest video to set the show title from '.channel' value"
			# get the last url in the link list and set the user name based on .channel in that link
			tempLinkUrl="$(echo "$tempLinkList" | jq -r ".url" | head -1)"
			tempLinkUrl2="$(echo "$tempLinkList" | jq -r ".url" | head -2)"
			echo "tempLinkUrl= $tempLinkUrl"
			tempJsonInfo=$(yt-dlp -j "$tempLinkUrl")
			tempJsonInfo2=$(yt-dlp -j "$tempLinkUrl2")

			tempJsonData=$(echo "$tempJsonInfo" | jq -r ".channel")
			tempJsonData2=$(echo "$tempJsonInfo2" | jq -r ".channel")

			echo "[INFO]: [ $tempJsonData == $tempJsonData2 ]"
			if [ "$tempJsonData" == "$tempJsonData2" ];then
				echo "[INFO]: comparison correct"
				showTitle=$tempJsonData
			fi
			if ! validString "$showTitle";then
				tempJsonData=$(echo "$tempJsonInfo" | jq -r ".uploader")
				tempJsonData2=$(echo "$tempJsonInfo2" | jq -r ".uploader")
				echo "[INFO]: [ $tempJsonData == $tempJsonData2 ]"
				if [ "$tempJsonData" == "$tempJsonData2" ];then
					echo "[INFO]: comparison correct"
					showTitle=$tempJsonData
				fi
			fi
		fi
		if ! validString "$showTitle";then
			echo "Show title is invalid string '$showTitle'"
			showTitle=$(echo "$tempLinkList" | jq -r ".playlist_title" | head -1 )
			echo "Show title set to '$showTitle' from .playlist_title"
		fi
		if ! validString "$showTitle";then
			echo "Show title is invalid string '$showTitle'"
			showTitle=$(echo "$tempLinkList" | jq -r ".playlist" | head -1 )
			echo "Show title set to '$showTitle' from .playlist"
		fi
		# get the playlist and try to mangle the domain or path into a title, this will only sometimes work correctly
		#if ! validString "$showTitle";then
		#	echo "Show title is invalid string '$showTitle'"
		#	# if a hash is used in here somehow it will be basicly unreadable
		#	echo "Getting .Playlist_id and attempting cleanup of the playlist..."
		#	# remove punctuation from the playlist title if it is the entire url
		#	showTitle=$(echo "$tempLinkList" | jq -r ".playlist_id" | head -1 | sed "s/\// /g" | tr -s ' ' | sed "s/[[:punct:]]//g")
		#	# cleanup url crap
		#	showTitle=$(echo "$showTitle" | sed "s/https\:\/\///g")
		#	showTitle=$(echo "$showTitle" | sed "s/http\:\/\///g")
		#fi
		# final failsafe username generator is the domain name
		if ! validString "$showTitle";then
			echo "Show title is invalid string '$showTitle'"
			echo "Using link domain name as last effort to generate a show title"
			showTitle=$(ytdl2kodi_rip_title "$channelLink")
		fi
	else
		echo "Using link domain name generate a show title for all videos from this domain"
		showTitle=$(ytdl2kodi_rip_title "$channelLink")
	fi
	echo "Show title set to '$showTitle'"
	################################################################################
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
			return 2
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
ytdl2kodi_reset_cache(){
	if test -f /etc/2web/ytdl/downloadPath.cfg;then
		downloadPath="$(cat /etc/2web/ytdl/downloadPath.cfg)"
		# remove the download data stored
		rm -rv "$downloadPath"/*
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
ytdl2kodi_update(){
	################################################################################
	# import and run the debug check
	# check dependencies to get the latest version of youtube-dl
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
	mkdir -p /etc/2web/ytdl/meta/
	################################################################################
	mkdir -p "$(getDownloadPath)"
	################################################################################
	currentlyProcessing=0
	################################################################################
	# for each link in the sources
	siteLinkList=$(loadConfigs "/etc/2web/ytdl/sources.cfg" "/etc/2web/ytdl/sources.d/" "/etc/2web/config_default/ytdl2nfo_websiteSources.cfg" | shuf)
	echo "Processing sources..."
	echo "Site Link List = '$siteLinkList'"
	siteLinkList="$(echo "$siteLinkList" | tr -s '\n')"
	siteLinkList="$(echo "$siteLinkList" | sed "s/\n/ /g")"
	echo "Site Link List = '$siteLinkList'"
	#echo "$siteLinkList" | while read link;do
	for link in $siteLinkList;do
		#echo "[INFO]:Checking site link '$link' ..."
		currentlyProcessing="$(($currentlyProcessing + 1))"
		if [ $currentlyProcessing -gt $(($channelProcessingLimit - 1)) ];then
			echo "[INFO]: Channel Processing Limit Reached!"
			break
		fi
		if [ "$link" == "" ];then
			echo "'$link' is blank..."
		else
			#echo "Running channel metadata extractor on '$link'..."
			# check links aginst existing stream files to pervent duplicating the work
			ytdl2kodi_channel_extractor "$link"
		fi
	done
	################################################################################
	# reset currently processing for the
	currentlyProcessing=0
	################################################################################
	# for each link in the users sources
	userlinkList=$(loadConfigs "/etc/2web/ytdl/usernameSources.cfg" "/etc/2web/ytdl/usernameSources.d/" "/etc/2web/config_default/ytdl2nfo_usernameSources.cfg" | shuf)
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
	if echo "$stringToCheck" | grep -q --ignore-case "^NULL";then
		# this means the function is a null string returned by jq
		echo "[WARNING]:string is a NULL value"
		return 2
	elif [ 2 -ge "$(echo "$stringToCheck" | wc -c)" ];then
		# this means the string is only one character
		echo "[WARNING]:String length is less than three"
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
	selection="$1"
	channelLink="$2"
	showTitle="$3"
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
	echo "################################################################################"
	echo "# Now extracting '$selection' #"
	echo "################################################################################"
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
	# use the pip package if it is available, otherwise use default system paths
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
		# if the extractor failed then try to extract info with ffprobe
		probeData=$(ffprobe "$selection" |& cat)
		if ! validString "$probeData";then
			# get the file title in metadata
			echo "$probeData" | grep "^title"| tr -s ' ' | cut -d':' -f2
		fi
		if ! validString "$probeData";then
			echo "Return code of ytdl = $infoCheck"
			# if the info returns a failure code
			# add it to the previous downloads to stop rescanning repeated links
			addProcessedSum "$selection" "$channelSum"
			echo "The info extractor failed..."
			echo "Skipping..."
			echo
			return 1
		fi
	fi
	################################################################################
	# this checks for the webpage url which is used for playback
	formatCheck=$(echo "$info" | jq -r ".webpage_url")

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

	# try to simply use the .title in the json
	if ! validString "$title";then
		title=$(echo "$info" | jq -r ".title?" | xargs -0 | cut -d$'\n' -f1 )
	fi

	# if the extractor failed then try to extract info with ffprobe
	if ! validString "$title";then
		probeData=$(ffprobe "$selection" |& cat)
		# get the file title in metadata
		title=$(echo "$probeData" | grep "^title"| tr -s ' ' | cut -d':' -f2 | head -1)
	fi

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
	uploader=$(echo "$info" | jq -r ".channel")
	# if channel is not available use uploader
	if ! validString "$uploader";then
		uploader=$(echo "$info" | jq -r ".uploader")
	fi
	################################################################################
	# create the show title
	#if echo "$@" | grep "\-\-username";then
	#	#if [ 5 -lt $(expr length "$uploader") ];then
	#	if validString "$uploader";then
	#		# create the username from the uploader, this is toggled by a switch
	#		showTitle="$uploader"
	#	else
	#		echo "No uploader name was found and use username as showname was selected."
	#		echo "Uploader = '$uploader'"
	#		# if this download is not listed in previousDownloads then add it
	#		addProcessedSum "$selection" "$channelSum"
	#		echo "Skipping video..."
	#		echo
	#		return 1
	#	fi
	#if validString "$3";then
	#	# get uploader from  channel extractor
	#	echo "[INFO]: uploader='$3' was a valid string"
	#	echo "[INFO]: uploader wil now become showTitle..."
	#	# create the username from the uploader, this is toggled by a switch
	#	showTitle="$3"
	#else
	#	echo "[INFO]: uploader='$uploader' was not a valid string"
	#	echo "[INFO]: ripping title from url..."
	#	# create the showtitle from the base url this is default
	#	showTitle=$(ytdl2kodi_rip_title "$selection")
	#	echo "[INFO]: Discovered title is '$showTitle'..."
	#fi
	echo "Show Title = $showTitle"
	#if echo "$@" | grep "\-\-username";then
	#	# the show title should be the same as the playlist show title
	#	if [ "$showTitle" != "$3" ];then
	#		# this means this link is invalid
	#		echo "Episode processing stopped, This video link is a diffrent username."
	#		# if this download is not listed in previousDownloads then add it
	#		addProcessedSum "$selection" "$channelSum"
	#		echo "Skipping video..."
	#		echo
	#		return 1
	#	fi
	#fi
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
	################################################################################
	# get thumbnail data if it is available
	thumbnail=$(echo "$info" | jq -r ".thumbnail")
	################################################################################
	# if the thumbnail lists nothing or returned in error generate a thumbnail from the webpage link
	echo "[INFO]: Analyzing thumbnail '$thumbnail'"
	# if the thumbnail link is valid
	if validString "$thumbnail";then
		# download the thumbnail via the cache from the found link data
		downloadThumbnail "$thumbnail" "${fileName}-thumb" ".png"
		echo "[WARNING]: Thumbnail link broken '$thumbnail'"
	fi
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
		runtime="$(echo "$info" | jq -r ".duration")"
		videoHeight="$(echo "$info" | jq -r ".height")"
		videoWidth="$(echo "$info" | jq -r ".width")"
		echo "<fileinfo>"
		echo "<streamdetails>"
		echo "<video>"
		# make sure the runtime is a interger
		if [[ "$runtime" =~ ^[0-9]+$ ]];then
			if [ "$runtime" -gt 0 ];then
				echo "<durationinseconds>$runtime</durationinseconds>"
			else
				# default runtime guess is 15 minutes
				echo "<durationinseconds>600</durationinseconds>"
			fi
		else
			# default runtime guess is 15 minutes
			echo "<durationinseconds>600</durationinseconds>"
		fi
		if [[ "$videoWidth" =~ ^[0-9]+$ ]];then
			if [ "$videoWidth" -gt 0 ];then
				echo "<width>$videoWidth</width>"
			fi
		fi
		if [[ "$videoHeight" =~ ^[0-9]+$ ]];then
			if [ "$videoHeight" -gt 0 ];then
				echo "<height>$videoHeight</height>"
			fi
		fi
		echo "</video>"
		echo "</streamdetails>"
		echo "</fileinfo>"
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
	echo "[INFO]: NUKE is disabled for ytdl2nfo..."
	echo "[INFO]: This is so you can disable the module but keep metadata."
	echo "[INFO]: Use 'ytdl2nfo reset' to remove all downloaded metadata."
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
		checkModStatus "ytdl2nfo"
		pip3 install --break-system-packages --upgrade yt-dlp
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
