#! /bin/bash
########################################################################
# ytdl2nfo converts metadata from remote websites into nfo libaries
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
		downloadPath="/var/cache/2web/download/"
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
	cat "/etc/2web/ytdl/channelUpdateCache.cfg"#DEBUG
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
	################################################################################
	showTitle=$(ytdl2kodi_rip_title "$channelLink")
	# create show directory
	#mkdir -p "$downloadPath$showTitle/"
	# download the newgrounds page
	echo "[INFO]: Downloading '$channelLink'"
	################################################################################
	# if the file is a playlist from youtube, rip links from the playlist
	# otherwise run the generic link ripper
	if echo "$channelLink" | grep -q "youtube.com";then
		echo "[INFO]: Running youtube.com link extractor..."
		# run the youtube special link playlist extractor
		linkList=""
		# try to rip as a playlist
		if test -f /usr/local/bin/yt-dlp;then
			tempLinkList=$(/usr/local/bin/yt-dlp --flat-playlist --abort-on-error -j "$channelLink")
		elif test -f /usr/local/bin/youtube-dl;then
			tempLinkList=$(/usr/local/bin/youtube-dl --flat-playlist -j "$channelLink")
		elif test -f /snap/bin/youtube-dl;then
			tempLinkList=$(/snap/bin/youtube-dl --flat-playlist -j "$channelLink")
		elif test -f /usr/bin/youtube-dl;then
			tempLinkList=$(/usr/bin/youtube-dl --flat-playlist -j "$channelLink")
		else
			tempLinkList=$(youtube-dl --flat-playlist -j "$channelLink")
		fi
		tempLinkList=$(echo "$tempLinkList" | jq -r ".url" )
		addToLog "Found Links" "Adding links to Linklist" "$tempLinkList" "/var/cache/2web/ytdl2nfo.log"
		for videoId in $tempLinkList;do
			linkList=$(echo -en "$linkList\n$videoId")
		done
	else
		echo "[INFO]: Running generic link extractor..."
		# if no custom extractor exists then run the generic link extractor
		################################################################################
		# show errors in curl but not download progress
		webData=$(curl --silent --show-error "$channelLink")
		echo "[INFO]: Webpage data found word count = $(echo $webData | wc -w)"
		################################################################################
		# rip video links
		################################################################################
		# remove contents of head tag from page before parsing
		echo "[INFO]: Looking for links in the webpage..."
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
	fi
	################################################################################
	# sort out duplicate links
	echo "[INFO]: Removing non-unique entries..."
	# Join adjacent unique lines
	linkList=$(echo "$linkList" | uniq)
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
	tempSum=$(echo -n "$channelLink" | sha256sum | cut -d' ' -f1)
	# make list channel specific
	previousDownloadsPath="/etc/2web/ytdl/previousDownloads/$tempSum.cfg"
	touch "$previousDownloadsPath"
	previousDownloads=$(cat "$previousDownloadsPath")
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
				ytdl2kodi_video_extractor "$link" "$channelLink" --username
			else
				echo "[INFO]: Running video extraction...."
				ytdl2kodi_video_extractor "$link" "$channelLink"
			fi
			processedEpisodes=$(($processedEpisodes + 1))
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
	lastProcessed=$(date "+%s")
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
	mkdir -p "$downloadPath$showTitle/"
	# create the tvshow.nfo
	fileName="$downloadPath$showTitle/tvshow.nfo"
	if test -f "$fileName";then
		echo "Series file already exists..."
		echo "Skipping creating series data..."
		return
	else
		################################################################################
		# webpage
		# generate a image from the webpage
		#echo "Downloading webpage to create image file for series"
		#if echo "$@" | grep -q "\-\-username";then
		#	tempWebUrl="$channelUrl"
		#	echo "--username found passing url '$tempWebUrl'"
		#else
		#	tempWebUrl="$baseUrl"
		#	echo "--username was NOT found passing url '$tempWebUrl'"
		#fi
		#echo "Downloading webpage for fanart '$tempWebUrl'"
		#set -x
		#wkhtmltoimage --format png --enable-javascript --javascript-delay 30000 --width 1920 --disable-smart-width --height 1080 "$tempWebUrl" "$downloadPath$showTitle/webpage.png"
		#set +x
		#if [ -f "$downloadPath$showTitle/webpage.png" ];then
		#	# blur the webpage image slightly
		#	echo "convert -blur 0x3 \"$downloadPath$showTitle/webpage.png\" \"$downloadPath$showTitle/webpage.png\""
		#	convert -blur 0x3 "$downloadPath$showTitle/webpage.png" "$downloadPath$showTitle/webpage.png"
		#else
		#	if [ $(ls "$downloadPath$showTitle/Season*/*.png" | grep -c -F .png) -ge 10 ];then
		#		# create a collage using thumbnail files
		#		montage "$downloadPath$showTitle/*.png" -background black -geometry 600x900\!+0+0 "$downloadPath$showTitle/webpage.png"
		#		# if the above runs and fails then noise will be generated below
		#	else
		#		#dont create the metadata as no base image can be created yet
		#		return
		#	fi
		#fi
		################################################################################
		# if the thumbnail download fails create the poster from the favicon
		#echo "Downloading the websites favicon from '$baseUrl/favicon.ico'"
		#curl "$baseUrl/favicon.ico" > "$downloadPath$showTitle/icon.ico"
		#if [ -f "$downloadPath$showTitle/icon.ico" ];then
		#	# scale up the favicon so it looks less awful than the fast scaling
		#	echo "Scaling the icon up and composing text over it..."
		#	################################################################################
		#	echo "convert \"$downloadPath$showTitle/icon.ico\" -adaptive-resize 256x256 \"$downloadPath$showTitle/icon.png\""
		#	convert "$downloadPath$showTitle/icon.ico" -adaptive-resize 256x256\! "$downloadPath$showTitle/icon.png"
		#	#rm -v "$downloadPath$showTitle/icon.ico"
		#fi
		#if ! [ -f "$downloadPath$showTitle/webpage.png" ];then
		#	echo "Creating failsafe noise background..."
		#	# creating failsafe blank image with random noise
		#	echo "convert -size 1920x1080 +seed \"$showTitle\" plasma: -swirl \"$swirlAmount\" \"$downloadPath$showTitle/webpage.png\""
		#	convert -size 1920x1080 +seed "$showTitle" plasma: -swirl "$swirlAmount" "$downloadPath$showTitle/webpage.png"
		#fi
		#if ! [ -f "$downloadPath$showTitle/webpage.png" ];then
		#	echo "No fanart could be created or downloaded metadata failed..."
		#	echo
		#	return
		#fi
		################################################################################
		# compose seed based noise over username based images
		################################################################################
		#if echo "$@" | grep -q "\-\-username";then
		#	echo "Add plasma over top of the webpage based on the '$showTitle'..."
		#	echo "convert -size 1920x1080 plasma: +seed \"$showTitle\" -swirl \"$swirlAmount\" \"$downloadPath$showTitle/uniquePattern.png\""
		#	convert -size 1920x1080 plasma: +seed "$showTitle" -swirl "$swirlAmount" "$downloadPath$showTitle/uniquePattern.png"
		#	# compose pattern over top of the pulled webpage
		#	echo "composite -dissolve 70 -gravity center \"$downloadPath$showTitle/webpage.png\" \"$downloadPath$showTitle/uniquePattern.png\" -alpha Set \"$downloadPath$showTitle/webpage.png\""
		#	composite -dissolve 70 -gravity center "$downloadPath$showTitle/webpage.png" "$downloadPath$showTitle/uniquePattern.png" -alpha Set "$downloadPath$showTitle/webpage.png"
		#fi
		#if [ -f "$downloadPath$showTitle/icon.png" ];then
		#	# compose the favicon over the background image
		#	echo "composite \"$downloadPath$showTitle/icon.png\" -gravity SouthWest \"$downloadPath$showTitle/webpage.png\" -alpha Set \"$downloadPath$showTitle/webpage.png\""
		#	composite "$downloadPath$showTitle/icon.png" -gravity SouthWest "$downloadPath$showTitle/webpage.png" -alpha Set "$downloadPath$showTitle/webpage.png"
		#fi
		################################################################################
		# create the poster with text overlay
		################################################################################
		#echo "Creating the fanart image from webpage..."
		#convert "$downloadPath$showTitle/webpage.png" -adaptive-resize 1920x1080\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -style Bold -size 1920x1080 -gravity center caption:"$showTitle" -composite "$downloadPath$showTitle/fanart.png"
		################################################################################
		#echo "Creating the poster image from webpage..."
		#convert "$downloadPath$showTitle/webpage.png" -adaptive-resize 600x900\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -style Bold -size 600x900 -gravity center caption:"$showTitle" -composite "$downloadPath$showTitle/poster.png"
		#cp "$downloadPath$showTitle/poster.png" "$downloadPath$showTitle/season-all-poster.png"
		################################################################################
		# create the banner
		################################################################################
		#echo "Creating the banner image from webpage..."
		#convert "$downloadPath$showTitle/webpage.png" -adaptive-resize 1000x300\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -style Bold -size 1000x300 -gravity center caption:"$showTitle" -composite "$downloadPath$showTitle/banner.png"
		#cp "$downloadPath$showTitle/banner.png" "$downloadPath$showTitle/season-all-banner.png"
		################################################################################
		# remove the webpage.png temp image file
		#rm -v "$downloadPath$showTitle/webpage.png"
		################################################################################
		# Create the nfo file last since it is the switch this entire script checks for
		################################################################################
		{
			echo "<?xml version='1.0' encoding='UTF-8'?>"
			echo "<tvshow>"
			echo "<title>$showTitle</title>"
			echo "<studio>Internet</studio>"
			echo "<genre>Internet</genre>"
			echo "<plot>Source URL: $channelUrl</plot>"
			echo "<premiered>$(date +%F)</premiered>"
			echo "<director>$showTitle</director>"
			echo "</tvshow>"
		} > "$fileName"
		# remove extra image files
		#if [ -f "$downloadPath$showTitle/webpage.png" ];then
		#	# remove the webpage.png temp image file
		#	rm -v "$downloadPath$showTitle/webpage.png"
		#fi
		#if [ -f "$downloadPath$showTitle/uniquePattern.png" ];then
		#	# remove the uniquePattern.png temp image file
		#	rm -v "$downloadPath$showTitle/uniquePattern.png"
		#fi
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
	pip3 install --upgrade youtube-dl
	pip3 install --upgrade yt-dlp
}
################################################################################
ytdl2kodi_reset_cache(){
	echo -n "Would you like to reset the entire video cache and all databases?[y/n]:"
	read doIt
	if test -f /etc/2web/ytdl/downloadPath.cfg;then
		if echo "$doIt" | grep -q "y" ;then
			downloadPath="$(cat /etc/2web/ytdl/downloadPath.cfg)"
			echo "The paths to be removed are"
			echo "$downloadPath"
			echo "/etc/2web/ytdl/episodeDatabase/"
			echo "/etc/2web/ytdl/previousDownloads/*.cfg"
			echo "/etc/2web/ytdl/foundLinks/*.cfg"
			echo "/etc/2web/ytdl/processedSums/*.cfg"
			echo "/etc/2web/ytdl/channelUpdateCache.cfg"
			echo -n "Would you still like to remove all files and reset the cache?[y/n]:"
			read doIt
			if echo "$doIt" | grep -q "y" ;then
				# empty the databases
				rm -rv "/etc/2web/ytdl/episodeDatabase/"
				mkdir -p "/etc/2web/ytdl/episodeDatabase/"
				touch "/etc/2web/ytdl/episodeDatabase/.placeholder"
				# remove all previous downloads
				rm -vr "/etc/2web/ytdl/previousDownloads/" &
				rm -vr "/etc/2web/ytdl/processedSums/" &
				rm -vr "/etc/2web/ytdl/foundLinks/" &
				rm -v "/etc/2web/ytdl/channelUpdateCache.cfg" &
				# remove lock file
				rm -v /tmp/ytdl2kodi_LOCKFILE &
				# kill all processes
				pkill ytdl2kodi &
			fi
		fi
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
			echo "# Server site paths"
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
			echo "# server user paths"
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
	# check if system is active
	if test -f "/tmp/ytdl2nfo.active";then
		# system is already running exit
		echo "[INFO]: ytdl2nfo is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/ytdl2nfo.active'."
		exit
	else
		# set the active flag
		touch /tmp/ytdl2nfo.active
		# create a trap to remove nfo2web lockfile
		trap "rm -v /tmp/ytdl2nfo.active" EXIT
	fi
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
checkDirSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	directory=$2
	# check the sum of a directory and compare it to a previously stored sum
	if ! test -d "$webDirectory/sums/";then
		mkdir -p "$webDirectory/sums/"
	fi
	pathSum="$(echo "$directory" | md5sum | cut -d' ' -f1 )"
	newSum="$(getDirSum "$2")"
	# check for a previous sum
	if test -f "$webDirectory/sums/ytdl_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/ytdl_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/ytdl_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/ytdl_$pathSum.cfg"
		return 0
	fi
}
########################################################################
checkDirDataSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	directory=$2
	# check the sum of a directory and compare it to a previously stored sum
	createDir "$webDirectory/sums/"
	pathSum="$(echo "$directory" | md5sum | cut -d' ' -f1 )"
	newSum="$(getDirDataSum "$2")"
	# check for a previous sum
	if test -f "$webDirectory/sums/$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/$pathSum.cfg"
		return 0
	fi
}
########################################################################
checkFileDataSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	filePath=$2
	# check the sum of a directory and compare it to a previously stored sum
	createDir "$webDirectory/sums/"
	pathSum="$(echo "$filePath" | md5sum | cut -d' ' -f1 )"
	newSum="$( cat "$filePath" | md5sum | cut -d' ' -f1 )"
	# check for a previous sum
	if test -f "$webDirectory/sums/file_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/file_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/file_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/file_$pathSum.cfg"
		return 0
	fi
}
########################################################################
getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# add the version to the sum to update old versions
	# - Disk caching on linux should make this repetative file read
	#   not destroy the hard drive
	totalList="$totalList$(cat /usr/share/2web/versionDate.cfg)"
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
########################################################################
getDirDataSum(){
	line=$1
	# check the libary sum against the existing one
	#totalList=$(find "$line" | sort)
	# read the data from each file
	totalList="$( find "$line" -type f -exec /usr/bin/cat {} \; )"
	# add the version to the sum to update old versions
	# - Disk caching on linux should make this repetative file read
	#   not destroy the hard drive
	totalList="$totalList$(cat /usr/share/2web/versionDate.cfg)"
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
################################################################################
addProcessedSum(){
	selectionSum=$1
	channelSum=$2
	# add the selection sum to the list of previously downloaded links, if they are not already present
	if ! grep -q "$selectionSum" "/etc/2web/ytdl/processedSums/$channelSum.cfg";then
		echo "$selectionSum" >> "/etc/2web/ytdl/processedSums/$channelSum.cfg"
	fi
}
################################################################################
ytdl2kodi_video_extractor(){
	################################################################################
	# VIDEO EXTRACTOR
	################################################################################
	# NOTE: look at youtube-dl-selection for ripping info for nfo files
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
	selectionSum=$(echo -n "$selection" | md5sum | cut -d' ' -f1)
	if test -f /etc/2web/ytdl/previousSums/$channelSum.cfg;then
		# if a set of linksums was found, check it for the link sum
		# if the md5sum of the link has already been processed
		if grep -q "$selectionSum" "/etc/2web/ytdl/processedSums/$channelSum.cfg";then
			# exit the function because it has already been processed
			echo "The data for the selection '$selection' has already been processed."
			return
		fi
	fi
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
		echo "timeout --preserve-status \"$timeLimitSeconds\" /usr/local/bin/yt-dlp -j --abort-on-error --no-playlist \"$selection\""
		#info=$(timeout --preserve-status "$timeLimitSeconds" /usr/local/bin/yt-dlp -j --no-playlist "$selection")
		info=$(timeout --preserve-status "$timeLimitSeconds" /usr/local/bin/yt-dlp -j --no-playlist --playlist-end 1 "$selection")
	elif test -f /usr/local/bin/youtube-dl;then
		echo "timeout --preserve-status \"$timeLimitSeconds\" /usr/local/bin/youtube-dl -j --no-playlist \"$selection\""
		#info=$(timeout --preserve-status "$timeLimitSeconds" /usr/local/bin/youtube-dl -j --no-playlist "$selection")
		info=$(timeout --preserve-status "$timeLimitSeconds" /usr/local/bin/youtube-dl -j --no-playlist --playlist-end 1 "$selection")
	elif test -f /snap/bin/youtube-dl;then
		# snap package is second priority
		echo "timeout --preserve-status \"$timeLimitSeconds\" /snap/bin/youtube-dl -j --no-playlist \"$selection\""
		#info=$(timeout --preserve-status "$timeLimitSeconds" /snap/bin/youtube-dl -j --no-playlist "$selection")
		info=$(timeout --preserve-status "$timeLimitSeconds" /snap/bin/youtube-dl -j --no-playlist --playlist-end 1 "$selection")
	elif test -f /usr/bin/youtube-dl;then
		echo "timeout --preserve-status \"$timeLimitSeconds\" /usr/bin/youtube-dl -j --no-playlist \"$selection\""
		#info=$(timeout --preserve-status "$timeLimitSeconds" /usr/bin/youtube-dl -j --no-playlist "$selection")
		info=$(timeout --preserve-status "$timeLimitSeconds" /usr/bin/youtube-dl -j --no-playlist --playlist-end 1 "$selection")
	else
		# failsave uses whatever is stored in $PATH
		echo "timeout --preserve-status \"$timeLimitSeconds\" youtube-dl -j --no-playlist \"$selection\""
		#info=$(timeout --preserve-status "$timeLimitSeconds" youtube-dl -j --no-playlist "$selection")
		info=$(timeout --preserve-status "$timeLimitSeconds" youtube-dl -j --no-playlist --playlist-end 1 "$selection")
	fi
	infoCheck=$?
	if [ $infoCheck -eq 0 ];then
		echo "Return code of ytdl = $infoCheck"
		echo "Extraction Successfull!"
	elif [ $infoCheck -gt 123 ];then
		# exit code 124 or greater only comes from a timeout happening with the timeout command
		# this means that youtube-dl ran for more than $timeLimitSeconds and was stopped
		touch $previousDownloadsPath
		echo "$selection" >> $previousDownloadsPath
		addProcessedSum "$selection" "$channelSum"
		echo "The info extractor timed out after $timeLimitSeconds seconds..."
		echo "Skipping..."
		echo
		return
	else
		echo "Return code of ytdl = $infoCheck"
		# if the info returns a failure code
		# add it to the previous downloads to stop rescanning repeated links
		touch $previousDownloadsPath
		echo "$selection" >> "$previousDownloadsPath"
		addProcessedSum "$selection" "$channelSum"
		echo "The info extractor failed..."
		echo "Skipping..."
		echo
		return
	fi
	################################################################################
	formatCheck=$(echo "$info" | jq ".formats[0].url")
	formatCheck=$(echo "$formatCheck" | sed 's/\"//g')
	# if spaces somehow made it into the formating, cut the first field
	formatCheck=$(echo "$formatCheck" |  cut -d" " -f1)
	if ! validString "$formatCheck";then
		# this is not a video file link so ignore it
		touch $previousDownloadsPath
		echo "$selection" >> $previousDownloadsPath
		addProcessedSum "$selection" "$channelSum"
		#check if the link contains a url of any kind as the link to play
		echo "The url to play this video can not be found."
		echo "Found URL = '$formatCheck'"
		echo "Skipping..."
		echo
		return
	fi
	if echo "$formatCheck" | grep ".zip";then
		# this is not a video file link its a zip file so ignore it
		touch $previousDownloadsPath
		echo "$selection" >> $previousDownloadsPath
		addProcessedSum "$selection" "$channelSum"
		echo "This is a zip file not a video link"
		echo "Skipping..."
		echo
		return
	fi
	if echo "$formatCheck" | grep ".swf";then
		# this is not a video file link its a zip file so ignore it
		touch $previousDownloadsPath
		echo "$selection" >> $previousDownloadsPath
		addProcessedSum "$selection" "$channelSum"
		echo "This is a swf file not a video link"
		echo "Skipping..."
		echo
		return
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
	uploader=$(echo "$info" | jq -r ".uploader" | xargs -0 | cut -d$'\n' -f1 )
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
	################################################################################
	# create the show title
	if echo "$@" | grep "\-\-username";then
		#if [ 5 -lt $(expr length "$uploader") ];then
		if validString "$uploader";then
			# create the username from the uploader, this is toggled by a switch
			showTitle="$uploader"
		else
			echo "No uploader name was found and use username as showname was selected."
			echo "Uploader = $uploader"
			# if this download is not listed in previousDownloads then add it
			touch $previousDownloadsPath
			echo "$selection" >> $previousDownloadsPath
			addProcessedSum "$selection" "$channelSum"
			echo "Skipping video..."
			echo
			return
		fi
	else
		# create the showtitle from the base url this is default
		showTitle=$(ytdl2kodi_rip_title "$selection")
	fi
	echo "Show Title = $showTitle"
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
		return
	fi
	################################################################################
	# create the directory for the show data if it does not exist
	mkdir -p "/etc/2web/ytdl/episodeDatabase/"
	# the database tracks the episode number based on all previous episodes
	if ! test -f "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg";then
		# if no episodes exist create the variable
		echo "0" > "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg"
	fi
	# create the episode numbering specific to the show
	epNum=$(cat "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg")
	# increment episode number but dont save because it may fail
	epNum=$(($epNum + 1))
	# if the episode number is less than 10 add a 0 prefix for proper file sorting
	if [ $epNum -lt 10 ];then
		# format extra zero
		epNum="0$epNum"
	fi
	################################################################################
	echo "downloadPath = '$downloadPath' + title = '$title' + '-' + id = '$id'"
	# add season and episode numbering
	echo "The title is '$title'"
	#echo "Title length is $(expr length "$title"), is this greater than 5"
	if [ 5 -lt $(expr length "$title") ];then
		# cleanup the filename
		tempTitle=$(echo "$title" | sed "s/[[:punct:]]//g")
		fileName="$showTitle - s$(echo $episodeSeason)e$epNum - $tempTitle"
	else
		fileName="$showTitle - s$(echo $episodeSeason)e$epNum"
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
	if test -f "$fileName.nfo";then
		echo "The data for $filename.nfo has already been processed."
		# this is the only failure mode that occurs after episode numbering
		# so decremnt the episode number to prevent gaps
		addProcessedSum "$selection" "$channelSum"
		echo "Skipping..."
		echo
		return
	fi
	################################################################################
	# build stream file for video or download video file
	# save stream file sources
	touch "$fileName.strm"
	if echo "$selection" | grep "youtube\.com";then
		# for youtube episodes use the built in kodi streaming plugin for .strm files otherwise the
		# gathered video links expire after a short time
		#tempVar=$(echo "$selection" | sed "s/https\:\/\/youtube\.com//g")
		#tempVar=$(echo "$tempVar" | sed "s/\/watch?v=//g")
		#sourceUrl=$(echo "plugin://plugin.video.youtube/play/?video_id=$tempVar")
		# DEBUG
		sourceUrl=$selection
	else
		# for generic extraction simply get the first format url, this is lowest quality to highest
		# NOTE: the number of formats is unknown per video but each is assured to have one
		#sourceUrl=$(echo "$info" | jq ".formats[0].url" | sed 's/\"//g')
		# try to get a usable format, lowest quality is checked first
		#if ! validString "$sourceUrl";then
		#	# if previous format failed
		#	sourceUrl=$(echo "$info" | jq ".formats[1].url" | sed 's/\"//g')
		#fi
		#if ! validString "$sourceUrl";then
		#	sourceUrl=$(echo "$info" | jq ".formats[2].url" | sed 's/\"//g')
		#fi
		#if ! validString "$sourceUrl";then
		#	sourceUrl=$(echo "$info" | jq ".formats[3].url" | sed 's/\"//g')
		#fi
		## the default url is checked last because sometimes the json does not list
		## a default url, it should be prioritized if it exists
		#if ! validString "$sourceUrl";then
		#	sourceUrl=$(echo "$info" | jq ".url" | sed 's/\"//g')
		#fi
		#
		# use the webpage as the source url
		sourceUrl=$selection
	fi
	################################################################################
	# make the found links directory if it does not exist, and the channel specific list
	mkdir -p /etc/2web/ytdl/foundLinks/
	foundLinksPath=/etc/2web/ytdl/foundLinks/$channelSum.cfg
	################################################################################
	echo "[INFO]: Checking for found video links '$foundLinksPath'"
	################################################################################
	# check the length of the source url is long enough to be a link
	if [ 5 -lt $(expr length "$sourceUrl") ];then
		# check if a custom resolver has been specified with a custom url
		if test -f /etc/2web/ytdl/customResolverUrl.cfg;then
			# custom resolver should take presidence over system default resolvers
			resolverString=$(cat /etc/2web/ytdl/customResolverUrl.cfg)
			# - This should not affect the $sourceUrl variable since it is
			#   also used for thumbnail generation
			# - Wrap the selection in quotes for the custom resolver
			echo "$resolverString\"$selection\"" > "$fileName.strm"
		else
			echo "[INFO]: Writing source url to strm file..."
			echo "$sourceUrl" > "$fileName.strm"
		fi
	else
		touch $foundLinksPath
		echo "$sourceUrl" >> $foundLinksPath
		# add the selection sum to the list of previously downloaded links, if they are not already present
		addProcessedSum "$selection" "$channelSum"
		echo "[INFO]: There is no video link available!"
		echo "[INFO]: Skipping..."
		echo
		return
	fi
	################################################################################
	# create the file if it dont exist
	touch $foundLinksPath
	# check the discoverd video url
	if grep "$sourceUrl" $foundLinksPath;then
		# if download was found to already have been processed
		echo "[INFO]: This download has already been processed..."
		echo "[INFO]: '$sourceUrl' matches another previously downloaded video in this series..."
		echo
		return
	else
		# if the url has not been added, add it
		touch $foundLinksPath
		echo "[INFO]: Writing video URL to $foundLinksPath"
		echo "$sourceUrl"
		echo "$sourceUrl" >> $foundLinksPath
		addProcessedSum "$selection" "$channelSum"
	fi
	################################################################################
	# get thumbnail data if it is available
	thumbnail=$(echo "$info" | jq -r ".thumbnail")
	################################################################################
	# if the thumbnail lists nothing or returned in error generate a thumbnail from the webpage link
	echo "[INFO]: Analyzing thumbnail '$thumbnail'"
	#echo "5 is greater than $(expr length "$thumbnail")"
	# if the thumbnail get fails
	# try to create a thumbnail from the discovered video url using ffmpeg
	if ! validString "$thumbnail";then
		echo "[INFO]: Attempting to create thumbnail from video source..."
		touch "$fileName-thumb.png"
		tempFileSize=0
		tempTimeCode=1
		# - force the filesize to be large enough to be a complex descriptive thumbnail
		# - filesize of images is directly related to visual complexity
		while [ $tempFileSize -lt 15000 ];do
			# - place -ss in front of -i for speed boost in seeking to correct frame of source
			# - tempTimeCode is in seconds
			# - '-y' to force overwriting the empty file
			ffmpeg -y -ss $tempTimeCode -i "$sourceUrl" -vframes 1 "$fileName-thumb.png"
			# resize the image before checking the filesize
			convert "$fileName-thumb.png" -resize 400x200\! "$fileName-thumb.png"
			# get the size of the file, after it has been created
			tempFileSize=$(cat "$fileName-thumb.png" | wc --bytes)
			# - increment the timecode to get from the video to find a thumbnail that is not
			#   a blank screen
			tempTimeCode=$(($tempTimeCode + 1))
			# if there is no file large enough after 60 attempts, the first 60 seconds of video
			if [ $tempTimeCode -gt 60 ];then
				# break the loop
				tempFileSize=16000
				rm "$fileName-thumb.png"
			elif [ $tempFileSize -eq 0 ];then
				# break the loop, no thumbnail could be generated at all
				# - Blank white or black space takes up more than 0 bytes
				# - A webpage generated thumbnail will be created as a alternative
				rm "$fileName-thumb.png"
				tempFileSize=16000
			fi
		done
	fi
	# if ffmpeg can not create a thumbnail, generate a thumbnail from the webpage
	if ! validString "$thumbnail";then
		writeCaption=""
		# generate a image from the webpage for the thumbnail
		webpageUrl=$(echo "$info" | jq -r ".webpage_url" | sed "s/http:/https:/g")
		echo "[INFO]: Creating thumbnail from webpage '$webpageUrl'"
		# complex commands are made easier to debug when you can see the contents being fed in
		if ! test -f "$fileName-thumb.png";then
			echo "wkhtmltoimage --format png --enable-javascript --javascript-delay 1000 --width 1920 --disable-smart-width --height 1080 \"$webpageUrl\" \"$fileName-thumb.png\""
			wkhtmltoimage --format png --enable-javascript --javascript-delay 1000 --width 1920 --disable-smart-width --height 1080 "$webpageUrl" "$fileName-thumb.png"
			# if the file was created successfully, write title over webpage image
			writeCaption="yes"
		fi
		if ! test -f "$fileName-thumb.png";then
			echo "[INFO]: Webpage thumbnail could not be downloaded, generating plasma image"
			# if no webpage was downloaded
			convert -size 400x200 plasma: "$fileName-thumb.png"
			# write the title over the plasma
			writeCaption="yes"
		fi
		# resize the thumbnail
		convert "$fileName-thumb.png" -resize 400x200\! "$fileName-thumb.png"
		# write the caption if no real thumbnail could be found
		if echo "$writeCaption" | grep "yes";then
			# add a caption of the video title to the downloaded image of the webpage
			# the caption must be smaller than the image to prevent cutting off edges
			convert "$fileName-thumb.png" -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 2 -style Bold -size 300x100 -gravity center caption:"$title" -composite "$fileName-thumb.png"
		fi
	else
		echo "[INFO]: Thumbnail was found with extractor!"
	fi
	################################################################################
	# save nfo file
	touch "$fileName.nfo"
	echo "<?xml version='1.0' encoding='UTF-8'?>" > "$fileName.nfo"
	echo "<episodedetails>" >> "$fileName.nfo"
	echo "<season>$episodeSeason</season>" >> "$fileName.nfo"
	echo "<episode>$epNum</episode>" >> "$fileName.nfo"
	# set the series title
	echo "<showtitle>$showTitle</showtitle>" >> "$fileName.nfo"
	# Set the title grabed previously to build the filename
	echo "<title>$title</title>" >> "$fileName.nfo"
	if [ 5 -lt $(expr length "$thumbnail") ];then
		echo "<thumb>$thumbnail</thumb>" >> "$fileName.nfo"
	fi
	# get the director information
	echo "<director>$uploader</director>" >> "$fileName.nfo"
	echo "<credits>$uploader</credits>" >> "$fileName.nfo"
	# get the runtime if it is available
	runtime=$(echo "$info" | jq -r ".duration")
	if [ "$runtime" -gt 0 ];then
		echo "<runtime>$runtime</runtime>" >> "$fileName.nfo"
	else
		# default runtime guess is 15 minutes
		echo "<runtime>15 min</runtime>" >> "$fileName.nfo"
	fi
	echo "<plot>$plot</plot>" >> "$fileName.nfo"
	echo "<aired>$airdate</aired>" >> "$fileName.nfo"
	# set the last processed time of the episode
	echo "<lastProcessed>$(date "+%s")</lastProcessed>" >> "$fileName.nfo"
	# end the nfo file
	echo "</episodedetails>" >> "$fileName.nfo"
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
	tempTimeHours=$((10#$tempTimeHours * 60 * 60))
	tempTimeMinutes=$((10#$tempTimeMinutes * 60))
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
	if ! grep "$selection" $previousDownloadsPath;then
		# cast the string to a number, removes extra 0 prefixes
		epNum=$(echo "$epNum" | sed "s/^[0]*//g")
		# set the new episode number and save it
		echo "$epNum" > "/etc/2web/ytdl/episodeDatabase/$showTitle-$episodeSeason.cfg"
		# if this download is not listed in previousDownloads then add it
		touch $previousDownloadsPath
		echo "$selection" >> $previousDownloadsPath
	fi

	# add the selection sum to the list of previously downloaded links, if they are not already present
	addProcessedSum "$selection" "$channelSum"
	# add the selection sum to the list of previously downloaded links, if they are not already present
	#if ! grep -q "$selectionSum" "/etc/2web/ytdl/processedSums/$channelSum.cfg";then
	#	echo "$selectionSum" >> "/etc/2web/ytdl/processedSums/$channelSum.cfg"
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
	return
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
		lockProc "ytdl2nfo"
		checkModStatus "ytdl2nfo"
		ytdl2kodi_update
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "ytdl2nfo"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "ytdl2nfo"
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		ytdl2kodi_depends_check
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		ytdl2kodi_reset_cache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "ytdl2nfo Version: "
		cat /usr/share/2web/version_ytdl2nfo.cfg
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/ytdl2nfo.txt"
	else
		lockProc "ytdl2nfo"
		checkModStatus "ytdl2nfo"
		ytdl2kodi_update
		main --help
	fi
}
################################################################################
main "$@"
exit
