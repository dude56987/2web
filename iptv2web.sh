#! /bin/bash
########################################################################
# Merge many iptv sources into a single source
# Copyright (C) 2020  Carl J Smith
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
#set -x
#export PS4='+(${BASH_SOURCE}:${LINENO}): ${FUNCNAME[0]:+${FUNCNAME[0]}(): }'
export PS4='${LINENO} +	|	'
# set tab size to 4 to make output more readable
tabs 4
# add main libary
source /var/lib/2web/common
################################################################################
function INFO(){
	width=$(tput cols)
	cleanedText=$(cleanText "$1")
	#cleanedText="$1"
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[INFO]: $cleanedText$buffer" | cut -b"1-$(( $width - 1 ))")"
	# print the line
	printf "$output\r"
	#printf "$output\n"
	# DEBUG sleep
	#sleep 0.2
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
cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	#echo "$1" | inline-detox --remove-trailing | sed "s/_/ /g"
	echo -n "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
}
################################################################################
function killFakeImage(){
	localIconPath=$1
	# if the file exists
	if test -f "$localIconPath";then
		# check it is not a empty file
		if file "$localIconPath" | grep -q "empty";then
			# remove the empty file
			rm -v "$localIconPath"
		elif file "$localIconPath" | grep -q "text";then
			# remove remote downloaded http redirect, 404, etc.
			rm -v "$localIconPath"
		fi
	fi
	return 0
}
################################################################################
function streamPass(){
	# pass streamlink arguments to correct streamlink path
	if test -f "/usr/local/bin/streamlink";then
		/usr/local/bin/streamlink "$@"
	elif test -f "/usr/bin/streamlink";then
		/usr/bin/streamlink "$@"
	else
		# could not find streamlink installed on the server
		ERROR "For the URL to resolve you must install streamlink on this server."
		ERROR "You may need to contact your local system administrator."
		ERROR "As a administrator use 'pip3 install streamlink' to install the latest version."
		exit
	fi
}
################################################################################
examineIconLink(){
	###################################################################
	iconLink=$1
	link=$2
	title=$3
	radio=$4
	###################################################################
	iconLength=$(echo -n "$iconLink" | wc -c)
	sum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
	INFO "Icon Sum=$sum"
	localIconPath="$(webRoot)/live/$sum.png"
	localIconThumbPath="$(webRoot)/live/$sum-thumb.png"
	localIconThumbMiniPath="$(webRoot)/live/$sum-thumb-mini.png"

	# if the file exists and is not older than 10 days
	if cacheCheck "$localIconPath" "20";then
		if [ "$iconLength" -gt 3 ];then
			# build a md5sum from the icon link
			#sum=$(echo "$iconLink" | md5sum | cut -d' ' -f1)
			if ! test -f "$localIconPath";then
				INFO "Downloading thumbnail '$iconLink'"
				#timeout 600 curl "$iconLink" | convert - -adaptive-resize 128x200\! "$localIconPath"
				# if the file does not exist in the cache download it
				timeout 120 curl --silent "$iconLink" > "$localIconPath"
				# resize the icon to standard size
				timeout 600 convert "$localIconPath" -adaptive-resize 200x200\! "$localIconPath"
			fi
		fi
		# remove image if fake was created
		killFakeImage "$localIconPath"
		# try to download the icon with youtube-dl
		if ! test -f "$localIconPath";then
			tempIconLink=$(youtube-dl -j "$link" | jq ".thumbnail")
			INFO "Downloading thumbnail '$tempIconLlink'"
			# if the file does not exist in the cache download it
			timeout 120 curl --silent "$tempIconLink" > "$localIconPath"
			# resize the icon to standard size
			timeout 600 convert "$localIconPath" -adaptive-resize 200x200\! "$localIconPath"
		fi
		# remove image if fake was created
		killFakeImage "$localIconPath"
		if ! test -f "$localIconPath";then
			# resolve the link using streamlink to create a thumbnail
			#resolvedLink=$(streamlink --stream-url "$link" best)
			# check if the link is a twitch link, they preload ads in the first 15 seconds
			# so take the thumbnail from after this 15 seconds
			if echo -n "$link" | grep -q "twitch.tv";then
				tempTimeout=20
			else
				tempTimeout=0
			fi
			# build a thumbnail from the video source
			#timeout 30 ffmpeg -y -i "$resolvedLink" -ss 1 -frames:v 1 "$localIconPath"
			# this must be contained in a single line or the delay causes it to be blocked
			#timeout 30 ffmpeg -y -i "$(streamlink --stream-url "$link" best)" -ss 1 -frames:v 1 "$localIconPath"
			if ! echo -n "$radio" | grep -q "true";then
				if streamPass --can-handle-url "$link";then
					INFO "Downloading thumbnail '$link'"
					# resolve with streamlink and then screenshot
					timeout 5 ffmpeg -hide_banner -loglevel quiet -y -i "$(streamPass --stream-url "$link" best)" -frames:v 1 "$localIconPath"
				else
					INFO "Downloading thumbnail '$link'"
					# raw stream screenshot
					timeout 5 ffmpeg -hide_banner -loglevel quiet -y -i "$link" -frames:v 1 "$localIconPath"
				fi
			fi
			# resize the icon to standard size
			timeout 600 convert "$localIconPath" -adaptive-resize 200x200\! "$localIconPath"
			# add text over retrieved thumbnail
			ALERT "timeout 600 convert '$localIconPath' -adaptive-resize 200x200\! -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 2 -size 200x200 -gravity center caption:'$title' -composite '$localIconPath'"
			timeout 600 convert "$localIconPath" -adaptive-resize 200x200\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 2 -size 200x200 -gravity center caption:"$title" -composite "$localIconPath"
		fi
		killFakeImage "$localIconPath"
		if ! test -f "$localIconPath";then
			# generate a image for the page since none exists
			swirlAmount=$(echo -n "$title" | wc -c)
			timeout 600 convert -size 200x200 +seed "$sum" plasma: -swirl "$swirlAmount" "$localIconPath"
			# add text over generated image
			timeout 600 convert "$localIconPath" -adaptive-resize 200x200\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 2 -size 200x200 -gravity center caption:"$title" -composite "$localIconPath"
			linkColor=$(echo -n "$link" | md5sum | cut --bytes='1-6')
			# convert to grayscale
			timeout 600 convert "$localIconPath" -colorSpace "gray" "$localIconPath"
			# colorize the image based on the link md5
			timeout 600 convert "$localIconPath" -colorSpace "gray" -fill "#$linkColor" -tint 100 "$localIconPath"
		fi
		# create the icon thumbnail for the web interface
		INFO "Creating image thumbnail..."
		timeout 600 convert "$localIconPath" -adaptive-resize 128x128\! "$localIconThumbPath"
		INFO "Creating image icon..."
		timeout 600 convert "$localIconPath" -adaptive-resize 32x32\! "$localIconThumbMiniPath"
	fi
}
################################################################################
function webGenCheck(){
	# read either from argument or filesystem
	if echo -n "$@" | grep -q "\-\-filecheck";then
		channelCount=$(( $(cat /var/cache/2web/web/kodi/channels.m3u | wc -l) / 2))
	else
		channelCount=$1
	fi

	# update website every 8 channel updates
	if [ $(( $channelCount % 8 )) -eq 0 ];then
		# re generate the webpage
		webGen --in-progress
	fi
}
################################################################################
function channelCheck(){
	webDirectory=$1

	channelsPath="$webDirectory/live/channels.index"
	channelsOutputPath="$webDirectory/live/channels.m3u"

	if [ $(cat "$channelsPath" | wc --bytes) -gt $(cat "$channelsOutputPath" | wc --bytes) ];then
		# if the temp file is larger copy it to the active webserver path
		cp "$channelsPath" "$channelsOutputPath"
	fi
}
################################################################################
function getIconLink(){
	lineCaught=$1
	# pipe the output of this function to get the iconLink, blank for no link
	if echo -n "$lineCaught" | grep -q 'tvg-logo="';then
		# store the icon if it is set
		tempIconLink=$(echo -n "$lineCaught" | grep --only-matching 'tvg-logo=".*"' | cut -d'"' -f2)
	elif echo -n "$lineCaught" | grep -q "tvg-logo='";then
		tempIconLink=$(echo -n "$lineCaught" | grep --only-matching "tvg-logo='.*'" | cut -d"'" -f2)
	else
		tempIconLink=""
	fi
	# return the link to be piped
	echo -n "$tempIconLink"
}
################################################################################
function getTVG(){
	lineCaught="$1"
	tvgInfo="$2"
	# pipe the output of this function to get the iconLink, blank for no link
	if echo -n "$lineCaught" | grep -q "$tvgInfo=\"";then
		# store the icon if it is set
		tempIconLink=$(echo "$lineCaught" | grep --only-matching "$tvgInfo=\".*\"" | cut -d'"' -f2)
	elif echo -n "$lineCaught" | grep -q "$tvgInfo='";then
		tempIconLink=$(echo "$lineCaught" | grep --only-matching "$tvgInfo='.*'" | cut -d"'" -f2)
	else
		tempIconLink=""
	fi
	# return the link to be piped
	echo -n "$tempIconLink"
}
################################################################################
updateInProgress(){
	echo -e "<div class='progressIndicator'>"
	echo -e "\t<span class='progressText'>Update In Progress...</span>"
	echo -e "\t<script>"
	echo -e "\t\t// reload the webpage every 1 minute, time is in milliseconds"
	echo -e "\t\tsetTimeout(function() { window.location=window.location;},((1000*60)*1));"
	echo -e "\t</script>"
	echo -e "</div>"
}
################################################################################
function process_M3U(){
	# open m3u files
	channels=$1
	webDirectory=$2
	radioFile=$3
	if [[ "$radioFile" == "" ]];then
		INFO "radio not set, turn radio to false"
		radioFile="false"
	fi
	################################################################################
	# convert m3u files by downloading icons and redirecting to downloaded local icons
	#channelNumber=1
	lineCaught=""

	# check for blocked channel config
	if ! test -f "/etc/2web/iptv/blockedGroups.cfg";then
		touch "/etc/2web/iptv/blockedGroups.cfg"
		# each line in this file is a string that if found will be blocked
		echo "news" > "/etc/2web/iptv/blockedGroups.cfg"
	fi

	# load up the blocked channel groups
	blockedGroups=$(cat /etc/2web/iptv/blockedGroups.cfg)
	blockedGroups=$(printf "$blockedGroups\n$(cat /etc/2web/iptv/blockedGroups.d/*.cfg)")

	echo -n "$channels" | while read line;do
		# if a info line was detected on the last line
		caughtLength=$(echo "$lineCaught" | wc -c)
		if [ "$caughtLength" -gt 1 ];then
			# pull the link on this line and store it
			title=$(echo -n "$lineCaught" | rev | cut -d',' -f1 | rev)
			title=$(cleanText "$title")
			#echo "Found Title = $title" >> "/var/log/iptv4everyone.log"
			link=$line
			INFO "Found Link = $link"
			radio="false"
			if [[ "$radioFile" == "true" ]];then
				INFO "Radio file is being scanned"
				# this is a radio file process all entries as radio entries
				radio="true"
			elif echo "$lineCaught" | grep -Eq "radio=[\",']true";then
				INFO "Radio line found mark radio tag true"
				# if the line is a radio entry
				radio="true"
			else
				INFO "No radio true tag found mark radio as false"
				radio="false"
			fi
			iconLink=$(getIconLink "$lineCaught")
			INFO "Icon Link = $iconLink"
			iconSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
			INFO "Icon MD5 = $iconSum"
			# check for group title
			groupTitle=$(getTVG "$lineCaught" "group-title")
			#IFS=$IFS_NORMAL
			#echo "$groupTitle" | while read -r group;do
			# during the building of the m3u file split out blocked items
			for group in $groupTitle;do
				# check each channel group to see if the group is in the blocked groups
				if echo -n "$blockedGroups" | grep -q --ignore-case "$group";then
					# this channel should be blocked from being added to the list
					INFO "The channel $title has been blocked, it contained blocked group $group"
					mkdir -p "$webDirectory/live/blocked/"
					{
						echo "<div class='blockedLink'>"
						echo "	<div class='blockedLinkTitle'>"
						echo "		$title"
						echo "	</div>"
						echo "	<div class='blockedLinkMeta'>"
						echo "		$lineCaught"
						echo "	</div>"
						echo "	<div class='blockedLinkLink'>"
						echo "		$link"
						echo "	</div>"
						echo "</div>"
					} > "$webDirectory/live/blocked/$iconSum.index"
					addChannel=false
				else
					# if the channel is not blocked, add the .index file for the group
					INFO "The channel $title was added in group $group"
					mkdir -p "$webDirectory/live/groups/$group/"
					if ! test -f "$webDirectory/live/groups/$group/$iconSum.index";then
						# link index files in group directories
						linkFile "$webDirectory/live/channel_$iconSum.index" "$webDirectory/live/groups/$group/$iconSum.index"
					fi
					addChannel=true
				fi
			done
			# if the channel was not blocked
			if $addChannel;then
				INFO "Building channel $title thumbnail..."
				# try to download or create the thumbnail
				examineIconLink "$iconLink" "$link" "$title" "$radio"

				INFO "Writing channel $title info to disk..."
				# Write the new version of the lines to the outputFile
				webIconPath="http://$(hostname).local:444/live/$iconSum.png"
				# write the raw channel file
				{
					echo "$lineCaught"
					echo "$link"
				} >> "$channelsRawPath"
				# write to the default channel file
				{
					echo -n "#EXTINF:-1 radio=\"$radio\" "
					echo -n "tvg-logo=\"$webIconPath\" "
					echo -n "tvg-name=\"$title\" "
					echo    "group-title=\"$groupTitle\",$title"
					echo "$link"
				} >> "$webDirectory/live/channels.index"
			fi
			################################################################################
			# increment the channel number
			#channelNumber=$(($channelNumber + 1))
			# invoke webgen to update webpage after adding new live link
			#webGenCheck --filecheck
		fi
		# if the line is a info line
		if echo -n "$line" | grep -q "#EXTINF";then
			INFO "Found info line '$line'"
			lineCaught="$line"
		else
			# reset the line caught variable
			lineCaught=""
		fi
	done
}
################################################################################
linkFile(){
	# link file if it is a link
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
}
################################################################################
function cacheCheck(){
	# return true if cached file does not exist or is over cacheDays old and needs updated

	filePath="$1"
	cacheDays="$2"

	if test -f "$filePath";then
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
function process_M3U_file(){
	# open m3u files
	channels=$(grep -v "#EXTM3U" "$1")
	webDirectory=$2
	process_M3U "$channels" "$webDirectory"
}
################################################################################
function processLink(){
	link=$1
	channelsPath=$2
	radioFile=$3
	# if radio is not set it will be false
	if [[ "$radioFile" == "" ]];then
		INFO "radio not set, turn radio to false"
		radioFile="false"
	fi
	################################################################################
	INFO "Processing Link '$link'"
	INFO "Channels Path '$channelsPath'"
	# check if link is a comment
	if echo -n "$link" | grep -Eq "^#";then
		# this link is a comment
		return 0
	elif test -f "$link";then
		# if the link is a local address
		#INFO "Link is a local address. Adding local file..."
		# add local files
		grep -v "#EXTM3U" "$link" >> "$channelsPath"
		return 0
	elif echo -n "$link" | grep -Eq "^http";then
		# if the link is a web address
		#INFO "Link is a web url..."
		# if the link is a link to a playlist download the playlist
		if echo -n "$link" | grep -Eq "\.m3u$|\.m3u8$|\.m3u8\?|\.m3u\?";then
			#INFO "Link is a m3u playlist..."

			# generate a md5 from the url for the cache
			linkSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)

			if cacheCheck "$webDirectory/live/cache/$linkSum.index" "10";then
				# create the cache directory if it does not exist
				mkdir -p "$webDirectory/live/cache/"
				# if no cached file exists
				# if downloaded file is older than 10 days update it
				timeout 120 curl --silent "$link" > "$webDirectory/live/cache/$linkSum.index"
			fi

			# if it is a playlist file add it to the list by download
			#downloadedM3U=$(curl "$link" | grep -v "#EXTM3U")
			downloadedM3U=$(cat "$webDirectory/live/cache/$linkSum.index")
			process_M3U "$downloadedM3U" "$webDirectory" "$radioFile"
		else
			INFO "Link is Unknown..."
			# if it is a known stream site use streamlink
			if streamPass --can-handle-url "$link";then
				INFO "Link can be processed by streamlink..."
				# determine the local hostname, use it to build the resolver path
				hostPath='http://'$(hostname)'.local:444/live/iptv-resolver.php?url="'$link'"'
				hostPathHD='http://'$(hostname)'.local:444/live/iptv-resolver.php?HD="true"&url="'$link'"'
				#hostPath='iptv-resolver.php?url="'$link'"'
				#hostPathHD='iptv-resolver.php?HD="true"&url="'$link'"'
				thumbnailLink="0"
				if which youtube-dl && which jq;then
					INFO "Attempting to get link metadata with youtube-dl ..."
					tempMeta=$(youtube-dl -j "$link")
					if echo -n "$link" | grep -q "youtube.com";then
						if echo -n "$tempMeta" | grep -q "fulltitle";then
							fileName=$(echo "$tempMeta" | jq ".fulltitle" | tr -d '"')
							INFO "link title = $fileName"
						fi
						if echo -n "$tempMeta" | grep -q "thumbnail";then
							thumbnailLink=$(echo "$tempMeta" | jq ".thumbnail" | tr -d '"')
							INFO "thumbnailLink = $thumbnailLink"
						fi
					else
						fileName=$(echo -n "$tempMeta" | jq ".display_id"| tr -d '"')
						INFO "link title  from display_id = $fileName"
					fi
				fi
				ERROR "[DEBUG]: checking filename length '$fileName'"
				tempFileName=$(echo -n "$fileName" | wc -c )
				tempFileName=$(($tempFileName))
				if [ 3 -gt $tempFileName ];then
					fileName=$(echo "$link" | rev | cut -d'/' -f1 | rev)
					ERROR "[DEBUG]: filename too short ripping end of url '$fileName'"
				fi
				ERROR "[DEBUG]: FileName = $fileName"
				examineIconLink "$thumbnailLink" "$hostPathHD" "$fileName HD" "$radio"
				examineIconLink "$thumbnailLink" "$hostPath" "$fileName" "$radio"
				sum=$(echo -n "$hostPath" | md5sum | cut -d' ' -f1)
				sumHD=$(echo -n "$hostPathHD" | md5sum | cut -d' ' -f1)
				ERROR "[DEBUG]: SUM = $sum"
				webIconPath="http://$(hostname).local:444/live/$sum.png"
				webIconPathHD="http://$(hostname).local:444/live/$sumHD.png"
				#webIconPath="$sum.png"
				# check if this link is a radio link
				if echo $lineCaught | grep -Eq "radio=[\",']true";then
					# if the line is a radio entry
					ERROR "Radio line found mark radio tag true"
					radio="true"
				elif [[ "$radioFile" == "true" ]];then
					ERROR "Radio file is being scanned"
					radio="true"
				else
					INFO "Found generated video entry set radio to false"
					radio="false"
				fi
				ERROR "[DEBUG]: WebIconPath = $webIconPath"
				{
					echo "#EXTINF:-1 radio=\"$radio\" tvg-logo=\"$webIconPath\" group-title=\"iptv2web\",$fileName HD"
					echo "$hostPathHD"
					echo "#EXTINF:-1 radio=\"$radio\" tvg-logo=\"$webIconPathHD\" group-title=\"iptv2web\",$fileName"
					echo "$hostPath"
				} >> "$channelsPath"
			else
				ERROR "Custom url creation failed for '$link'"
				return 1
			fi
		fi
	fi
	# invoke webgen to update webpage after adding new live links
	return 0
}
################################################################################
webUpdateCheck(){
	# if the update number is divisible by x
	if echo "$1 % 50" | bc ;then
		webGen
	fi
}
################################################################################
ALERT(){
	echo "$1";
}
################################################################################
fullUpdate(){
	################################################################################
	# scan sources config file and fetch each source
	################################################################################
	# enable debug
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f "/etc/2web/iptv/sources.cfg";then
		# if no config exists create the default config from the template
		{
			cat /etc/2web/config_default/live_sources.cfg
		} > /etc/2web/iptv/sources.cfg
	fi
	# load the link list
	linkList="$(loadWithoutComments /etc/2web/iptv/sources.cfg)"
	# build the radio sources cfg file if it does not exist
	if ! test -f "/etc/2web/iptv/radioSources.cfg";then
		# if no config exists create the default config from the template
		{
			cat /etc/2web/config_default/live_radioSources.cfg
		} > /etc/2web/iptv/radioSources.cfg
	fi
	# load the radio link list
	radioLinkList="$(loadWithoutComments /etc/2web/iptv/radioSources.cfg)"
	################################################################################

	webDirectory=$(webRoot)
	createDir "$webDirectory/live/"

	# link the live index
	linkFile  "/usr/share/2web/templates/live.php" "$webDirectory/live/index.php"

	# generate the placeholder website
	webGen

	channelsPath="$webDirectory/live/channels.index"
	channelsOutputPath="$webDirectory/live/channels.m3u"
	channelsRawPath="$webDirectory/live/channels_raw.index"
	channelsRawOutputPath="$webDirectory/live/channels_raw.m3u"

	# link the channel lists to the kodi directory
	linkFile "$channelsOutputPath" "$webDirectory/kodi/channels.m3u"
	linkFile "$channelsRawOutputPath" "$webDirectory/kodi/channels_raw.m3u"

	# for each link in the sources
	INFO "Processing sources..."
	INFO "Link List = $linkList"
	echo "#EXTM3U" > "$channelsPath"
	echo "#EXTM3U" > "$channelsRawPath"
	################################################################################
	# read video sources
	################################################################################
	if test -f "${webDirectory}live/totalSources.index";then
		totalSources=$(cat "${webDirectory}live/totalSources.index")
	else
		totalSources="?"
	fi
	################################################################################
	processedSources=0
	# add user created custom local configs first
	INFO "Adding m3u sources from /etc/2web/iptv/sources.d/"
	find "/etc/2web/iptv/sources.d/" -name '*.m3u' -type 'f' | while read configFile;do
		ALERT "Adding m3u source from $configFile"
		# add file to main m3u, exclude description line
		process_M3U_file "$configFile" "$webDirectory"
		processedSources=$(($processedSources + 1))
		INFO "processing source $processedSources/$totalSources"
		webUpdateCheck "$processedSources"
	done
	INFO "Adding m3u8 sources from /etc/2web/iptv/sources.d/"
	find "/etc/2web/iptv/sources.d/" -name '*.m3u8' -type 'f' | while read configFile;do
		ALERT "Adding m3u8 source from $configFile"
		# add file to main m3u8, exclude description line
		process_M3U_file "$configFile" "$webDirectory"
		processedSources=$(($processedSources + 1))
		INFO "processing source $processedSources/$totalSources"
		webUpdateCheck "$processedSources"
	done
	INFO "Adding /etc/2web/iptv/sources.cfg"
	# read main config m3u sources and merge them
	loadWithoutComments "/etc/2web/iptv/sources.cfg" | while read link;do
		ALERT "Adding web source from $configFile"
		processLink "$link" "$channelsPath"
		processedSources=$(($processedSources + 1))
		INFO "processing source $processedSources/$totalSources"
		webUpdateCheck "$processedSources"
	done
	# add external sources last
	INFO "Adding generated sources from /etc/2web/iptv/sources.d/*.cfg"
	# load the config file list
	find "/etc/2web/iptv/sources.d/" -name '*.cfg' -type 'f' | while read configFile;do
		ALERT "Adding web source from $configFile"
		# read each config file
		loadWithoutComments "$configFile" | while read link;do
			processLink "$link" "$channelsPath"
			processedSources=$(($processedSources + 1))
			INFO "processing source $processedSources/$totalSources"
			webUpdateCheck "$processedSources"
		done
	done
	################################################################################
	# process radio sources
	################################################################################
	# read main radio config
	echo -n "$radioLinkList" | while read link;do
		ALERT "Adding radio source from $link"
		# process radio link
		processLink "$link" "$channelsPath" "true"
		processedSources=$(($processedSources + 1))
		INFO "processing source $processedSources/$totalSources"
		webUpdateCheck "$processedSources"
	done
	# check for radio sources
	find /etc/2web/iptv/radioSources.d/ -name '*.m3u' -type 'f' | while read configFile;do
		loadWithoutComments "$configFile" | while read link;do
			ALERT "Adding radio source from $configFile"
			# process radio link
			processLink "$link" "$channelsPath" "true"
			processedSources=$(($processedSources + 1))
			INFO "processing source $processedSources/$totalSources"
			webUpdateCheck "$processedSources"
		done
	done
	# check for radio source files
	find /etc/2web/iptv/radioSources.d/ -name '*.cfg' -type 'f' | while read configFile;do
		loadWithoutComments "$configFile" | while read link;do
			ALERT "Adding radio source from $configFile"
			# process radio link
			processLink "$link" "$channelsPath" "true"
			processedSources=$(($processedSources + 1))
			INFO "processing source $processedSources/$totalSources"
			webUpdateCheck "$processedSources"
		done
	done
	echo "$processedSources" > "$webDirectory/live/totalSources.index"
	# after processing all configs copy the temp channels path over
	cp -v "$channelsPath" "$channelsOutputPath"
	cp -v "$channelsRawPath" "$channelsRawOutputPath"

	# generate the finished website
	webGen
	# fix any permission errors in the website
	chown -R www-data:www-data "/var/cache/2web/web/"
}
################################################################################
function buildPage(){
	title=$1
	link=$2
	poster=$3
	tabs=$4
	################################################################################
	localLinkSig="http://$(hostname).local:444/live/"
	################################################################################
	# check for .local domain indicating a local link
	if echo -n "$link" | grep -q --ignore-case ".local";then
		# cleanup the local string from the link as absolute paths will break resolution
		link=${link//$localLinkSig}
		# remove leading and trailing parathensis added from link
		link=${link//^\"}
		link=${link//\"$}
	fi
	if echo -n "$link" | grep -q --ignore-case "youtube.com";then
		# embed youtube livestream links into the webpage
		yt_id=${link//*watch?v=}
		yt_id=${yt_id//\"}
		ytLink="https://youtube.com/watch?v=$yt_id"
		# embed the youtube player
		echo "<iframe class='livePlayer'"
		# if title indicates it is a hd youtube channel embed, set hd to default
		if echo "$title" | grep -Eq " HD$";then
			echo " src='https://www.youtube-nocookie.com/embed/$yt_id?autoplay=1&hd=1'"
		else
			echo " src='https://www.youtube-nocookie.com/embed/$yt_id?autoplay=1'"
		fi
		echo " src='https://www.youtube-nocookie.com/embed/$yt_id?autoplay=1'"
		echo " frameborder='0'"
		echo " allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'"
		echo " allowfullscreen>"
		echo "</iframe>"
	else
		# build the page but dont write it, this function is intended to be
		# piped into a file
		echo -e "$tabs<script src='/2web.js'></script>"
		echo -e "$tabs<script src='/live/hls.js'></script>"
		echo -e "$tabs<video id='video' class='livePlayer' poster='$poster' autoplay muted></video>"
		echo -e "$tabs<script>"
		echo -e "$tabs	if(Hls.isSupported()) {"
		echo -e "$tabs		var video = document.getElementById('video');"
		echo -e "$tabs		var hls = new Hls({"
		echo -e "$tabs			debug: true"
		echo -e "$tabs		});"
		echo -e "$tabs		hls.loadSource('$link');"
		echo -e "$tabs		hls.attachMedia(video);"
		echo -e "$tabs		hls.on(Hls.Events.MEDIA_ATTACHED, function() {"
		echo -e "$tabs			video.muted = false;"
		echo -e "$tabs			video.play();"
		echo -e "$tabs		});"
		echo -e "$tabs	}"
		echo -e "$tabs	else if (video.canPlayType('application/vnd.apple.mpegurl')) {"
		echo -e "$tabs		video.src = '$link';"
		echo -e "$tabs		video.addEventListener('canplay',function() {"
		echo -e "$tabs			video.play();"
		echo -e "$tabs		});"
		echo -e "$tabs	}"
		# start playback on page load
		echo -e "$tabs hls.on(Hls.Events.MANIFEST_PARSED,playVideo);"
		echo -e "$tabs</script>"
	fi
}
################################################################################
function buildGroupPages(){
	webDirectory=$1
	################################################################################
	find "$webDirectory/live/groups/" -type 'd' | while read groupPath;do
		if ! [ "$groupPath" == "$webDirectory/live/groups/" ];then
			INFO "Building group pages for (groupPath = '$groupPath')"
			# link assets
			linkFile "$webDirectory/style.css" "$groupPath/style.css"
			linkFile "$webDirectory/randomFanart.php" "$groupPath/randomFanart.php"
			linkFile "$webDirectory/randomPoster.php" "$groupPath/randomPoster.php"
			linkFile "$webDirectory/fanart.cfg" "$groupPath/fanart.cfg"
			linkFile "$webDirectory/poster.cfg" "$groupPath/poster.cfg"
			# build the group index that lists all the groups
			{
				echo "<html id='top' class='liveBackground'>"
				echo "<head>"
				echo "<title></title>"
				echo "	<link rel='stylesheet' type='text/css' href='style.css'>"
				echo "	<script src='/2web.js'></script>"
				echo "</head>"
				echo "<body>"
				echo "<?PHP";
				echo "include('../../../header.php')";
				echo "?>";
				echo " <input id='searchBox' class='searchBox' type='text'"
				echo " onkeyup='filter(\"indexLink\")' placeholder='Search...' >"
				echo "<hr>"
			} > "$groupPath/index.index"
			# build the groups
			groupList=$(find "$groupPath" -maxdepth 1 -type 'd')
			find -L "$groupPath" -type 'f' | while read groupIndex;do
				# the .index file is found
				if echo "$groupIndex" | grep -Eq ".index";then
					# not index.index
					if ! echo "$groupIndex" | grep -Eq "index.index";then
						if ! echo "$groupIndex" | grep -Eq "index.php";then
							groupTitle=$(echo "$groupPath" | rev | cut -d'/' -f1 | rev)
							INFO "(groupTitle = $groupTitle )"
							INFO "Adding (groupindex = $groupIndex ) to (group = $groupPath )"
							{
								cat "$groupIndex"
							} >> "$groupPath/index.index"
						fi
					fi
				fi
			done
			{
				echo "<?PHP";
				echo "include('../../../header.php')";
				echo "?>";
				#echo "$headerData"
				echo "</body>"
				echo "</html>"
			} >> "$groupPath/index.index"
			cp -v "$groupPath/index.index" "$groupPath/index.php"
		fi
	done
	INFO "Finished building all group pages."
}
################################################################################
function buildRadioPage(){
	title=$1
	link=$2
	poster=$3
	tabs=$4
	################################################################################
	localLinkSig="http://$(hostname).local:444/live/"
	################################################################################
	# check for .local domain indicating a local link
	if echo "$link" | grep -q --ignore-case ".local";then
		# cleanup the local string from the link as absolute paths will break resolution
		link=${link//$localLinkSig}
		# remove leading and trailing parathensis added from link
		link=${link//^\"}
		link=${link//\"$}
	fi
	if echo "$link" | grep -q --ignore-case "youtube.com";then
		# embed youtube livestream links into the webpage
		yt_id=${link//*watch?v=}
		yt_id=${yt_id//\"}
		ytLink="https://youtube.com/watch?v=$yt_id"
		# embed the youtube player
		echo "<iframe class='livePlayer'"
		echo " src='https://www.youtube-nocookie.com/embed/$yt_id?autoplay=1'"
		echo " frameborder='0'"
		echo " allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'"
		echo " allowfullscreen>"
		echo "</iframe>"
	else
		# build the page but dont write it, this function is intended to be
		# piped into a file
		# make the background for the audio player the poster of the audio stream
		customStyle="background-image: url(\"$poster\");"
		echo -e "$tabs<audio id='video' class='livePlayer' style='$customStyle' poster='$poster' autoplay muted>"
		echo -e "$tabs<source src='$link' type='audio/mpeg'>"
		echo -e "$tabs</audio>"
	fi
}
################################################################################
append(){
	# add two variables together and output to stdout
	echo "$1$2"
}
################################################################################
popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg"
	echo "$1" | rev | cut -d'/' -f1 | rev
}
################################################################################
webGen(){
	webDirectory=$(webRoot)
	channelsPath="$webDirectory/live/channels.m3u"
	################################################################################
	if ! test -f "$webDirectory/live/hls.js";then
		# update the hls javascript libary, if no version has been downloaded
		main libary
	fi
	################################################################################
	createDir "$webDirectory/live/"
	# link the home php page
	linkFile  "/usr/share/2web/templates/live.php" "$webDirectory/live/index.php"
	# lists
	linkFile  "/usr/share/2web/templates/randomChannels.php" "$webDirectory/randomChannels.php"
	linkFile  "/usr/share/2web/templates/updatedChannels.php" "$webDirectory/updatedChannels.php"
	# copy over the stylesheet
	linkFile "$webDirectory/style.css" "$webDirectory/live/style.css"
	# copy over the resolver
	linkFile "/usr/share/2web/iptv/iptv-resolver.php" "$webDirectory/live/iptv-resolver.php"

	# build the background lists for random background
	cat "$webDirectory/fanart.cfg" | sed "s/^/..\//g" > "$webDirectory/live/fanart.cfg"
	cat "$webDirectory/poster.cfg" | sed "s/^/..\//g" > "$webDirectory/live/poster.cfg"

	linkFile "$webDirectory/randomFanart.php" "$webDirectory/live/randomFanart.php"
	linkFile "$webDirectory/randomPoster.php" "$webDirectory/live/randomPoster.php"

	################################################################################
	#build the header
	################################################################################
	channels=$(cat "$channelsPath" | grep -v "#EXTM3U")
	# split lines on line endings not spaces and line endings
	lineCaught=""

	# create temp files to be copied after update to output paths
	channelListPath="$webDirectory/live/channelList.index"

	# create output paths
	channelOutputPath="$webDirectory/live/channelList.php"
	indexOutputPath="$webDirectory/live/index.php"

	touch "$channelListPath"
	echo -n "" > "$channelListPath"
	################################################################################
	# build the channel list
	INFO "Building channel link list."
	################################################################################
	# build each channel page
	# - channel pages ignore --in-progress to prevent refresh during playback
	################################################################################
	echo "$channels" | while read line;do
		# if a info line was detected on the last line
		INFO "building channel page for line = $line"
		INFO "Line Caught = $line"
		# if a info line was detected on the last line
		caughtLength=$(echo "$lineCaught" | wc -c)
		if [ "$caughtLength" -gt 1 ];then
			#if cacheCheck "$webDirectory/live/channel_$channelNumber.index" "10";then
			#if ! test -f "$webDirectory/live/channel_$channelNumber.index";then
			if true;then
				# pull the link on this line and store it
				title=$(echo "$lineCaught" | cut -d',' -f2)
				link=$(echo -n "$line" | grep ".")
				iconSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
				iconLink="$iconSum.png"
				channelNumber=$(echo -n "$link" | md5sum | cut -d' ' -f1)
				# check for group title
				groupTitle=$(getTVG "$lineCaught" "group-title")
				INFO "Found Title = $title"
				INFO "Found Link = $link"
				INFO "Icon Link = $iconLink"
				INFO "Icon MD5 = $iconLink"
				################################################################################
				# build individual channel webpage
				################################################################################
				{
					# build the page
					echo "<html onload='forcePlay()'  id='top' class='liveBackground'>"
					echo "<head>"
					echo "	<link rel='stylesheet' type='text/css' href='style.css'>"
					echo " <title>$title</title>"
					echo "	<script src='/2web.js'></script>"
					echo "</head>"
					echo "<body>"
					# place the header
					echo "<?PHP";
					echo "include('../header.php')";
					echo "?>";
					echo "<div class='listCard'>";
					echo "<div id='videoPlayerContainer'>";
					if echo "$lineCaught" | grep -Eq "radio=[\",']true";then
						buildRadioPage "$title" "$link" "$iconLink" "\t\t\t"
					else
						buildPage "$title" "$link" "$iconLink" "\t\t\t"
					fi
					echo "<div class='videoPlayerControls'>"
					# play
					echo "<button id='playButton' class='button' style='display:none;' onclick='playPause()' alt='play'>&#9654;</button>"
					# pause
					echo "<button id='pauseButton' class='button' onclick='playPause()' alt='pause'>&#9208;</button>"
					#echo "<input type='range' onload='startVideoUpdateLoop()' id='videoPositionBar' value='0' />"
					#echo "<a class='button' onclick='stopVideo()'>Stop</a>"
					#echo "<a class='button' onclick='reloadVideo()'>Replay</a>"
					# volume controls
					echo "<span>"
					echo "<button class='button' onclick='volumeDown()'>&#128264;</button>"
					echo "<span id='currentVolume'>100</span>%"
					echo "<button class='button' onclick='volumeUp()'>&#128266;</button>"
					#echo "<span>"
					# mute
					#echo "<a id='muteButton' class='button' onclick='muteUnMute()'>Mute</a>"
					#echo "<a id='muteButton' class='button' onclick='muteUnMute()'>&#x1f507;</a>"
					# nmute
					#echo "<a id='unMuteButton' class='button' style='display:none;' onclick='muteUnMute()'>Unmute</a>"
					#echo "<a id='unMuteButton' class='button' style='display:none;' onclick='muteUnMute()'>&#x1f4e2;</a>"
					#echo "</span>"
					echo "</span>"
					echo "<span>";
					# show controls
					echo "<button id='showControls' onload='hideControls()' class='button' onclick='showControls()'>&#127899;</button>"
					# hide controls
					echo "<button id='hideControls' class='button' style='display:none;' onclick='hideControls()'>&#128317;</button>"
					echo "</span>"
					echo "<span>";
					# fullscreen
					echo "<button id='fullscreenButton' class='button' onclick='openFullscreen()'>&#11028;</button>"
					# exit fullscreen
					echo "<button id='exitFullscreenButton' class='button' style='display:none;' onclick='closeFullscreen()'>&#11029;</button>"
					echo "</span>"
					echo "</div>"

					echo "</div>"

					echo "	<div class='channelList'>"
					# create the line that will be replaced by the link list to all the channels
					echo "		<?PHP";
					echo "			include('channelList.php')";
					echo "		?>";
					echo "	</div>"

					echo "</div>"

					echo "<div>";

					echo "<br>"
					echo "<div class='descriptionCard'>"
					echo "	<a class='channelLink' href='channel_$channelNumber.php#$channelNumber'>"
					echo "		$title"
					echo "	</a>"
					echo "	<a class='button hardLink' href='$link'>"
					echo "		Hard Link"
					echo "	</a>"
					echo "	<a class='button hardLink vlcButton' href='vlc://$link'>"
					echo "		<span id='vlcIcon'>&#9650;</span> VLC"
					echo "	</a>"
					for group in $groupTitle;do
						echo "	<a class='button groupButton tag' href='groups/$group/'>$group</a>"
					done
					echo "</div>"
					# add footer
					echo "<?PHP";
					echo "include('../randomChannels.php');";
					echo "include('../header.php');";
					echo "?>";
					# add space for jump button when scrolled all the way down
					echo "<hr class='topButtonSpace'>"
					echo "</body>"
					echo "</html>"
				} > "$webDirectory/live/channel_$channelNumber.php"
				################################################################################
				# pull the link on this line and store it
				################################################################################
				iconThumbLink="$iconSum-thumb.png"
				iconThumbMiniLink="$iconSum-thumb-mini.png"
				iconLength=$(echo "$iconLink" | wc -c)
				################################################################################
				# build the .index files for the php index page to render correctly
				################################################################################
				if echo $lineCaught | grep -Eq "radio=[\",']true";then
					{
						# build icon to link to the channel
						echo -e "<a class='indexLink button radio' href='/live/channel_$channelNumber.php#$channelNumber'>"
						echo -e "\t<img loading='lazy' class='indexIcon' src='/live/$iconThumbLink'>"
						echo -e "\t<div class='indexTitle'>"
						echo -e "\t\t$title"
						echo -e "\t<div class='radioIcon'>"
						echo -e "\t&#128251;"
						echo -e "\t</div>"
						echo -e "\t</div>"
						echo -e "</a>"
						# store data in index files in order to allow stats to be created
					} > "$webDirectory/live/channel_$channelNumber.index"
				else
					{
						# build icon to link to the channel
						echo -e "<a class='indexLink button tv' href='/live/channel_$channelNumber.php#$channelNumber'>"
						echo -e "\t<img loading='lazy' class='indexIcon' src='/live/$iconThumbLink'>"
						echo -e "\t<div class='indexTitle'>"
						echo -e "\t\t$title"
						echo -e "\t<div class='radioIcon'>"
						echo -e "\t&#128250;"
						echo -e "\t</div>"
						echo -e "\t</div>"
						echo -e "</a>"
					} > "$webDirectory/live/channel_$channelNumber.index"
				fi
				################################################################################
				# add links to channel list, this is for the channel list used on individual pages
				################################################################################
				if echo $lineCaught | grep -Eq "radio=[\",']true";then
					# if the link is a radio station
					{
						#echo -e "<div id='$channelNumber'>"
						echo -e "\t<a id='$channelNumber' class='channelLink' href='/live/channel_$channelNumber.php#$channelNumber'>"
						echo -e "\t\t<img loading='lazy' class='channelIcon' src='/live/$iconThumbMiniLink'>"
						echo -e "\t\t$title"
						echo -e "\t<div class='radioIcon'>"
						echo -e "\t&#128251;"
						echo -e "\t</div>"
						echo -e "\t</a>"
						#echo -e "</div>"
					} >> "$channelListPath"
				else
					{
						#echo -e "<div id='$channelNumber'>"
						echo -e "\t<a id='$channelNumber' class='channelLink' href='/live/channel_$channelNumber.php#$channelNumber'>"
						echo -e "\t\t<img loading='lazy' class='channelIcon' src='/live/$iconThumbMiniLink'>"
						echo -e "\t\t$title"
						echo -e "\t<div class='radioIcon'>"
						echo -e "\t&#128250;"
						echo -e "\t</div>"
						echo -e "\t</a>"
						#echo -e "</div>"
					} >> "$channelListPath"
				fi
			fi
		fi
		# if the line is a info line
		if echo "$line" | grep -q "#EXTINF";then
			INFO "Found info line '$line'"
			lineCaught="$line"
		else
			# reset the line caught variable
			lineCaught=""
		fi
	done

	# build the group pages
	buildGroupPages "$webDirectory"

	# copy over the new versions of the generated webpages
	cp -v "$channelListPath" "$channelOutputPath"
}
################################################################################
resetCache(){
	webDirectory=$(webRoot)
	echo "The paths to be removed are"
	echo " - $webDirectory/live/*.index"
	echo " - $webDirectory/live/channel_*.html"
	echo "Starting reset..."
	find "$(webRoot)/live/" -type f -name 'channel_*.php' -exec rm -v {} \;
	find "$(webRoot)/live/" -type f -name '*.index' -exec rm -v {} \;
}
################################################################################
nuke(){
	webDirectory=$(webRoot)
	echo "The paths to be removed are"
	echo " - $webDirectory/live/*.html"
	echo " - $webDirectory/live/groups/*/"
	echo " - $webDirectory/live/*.php"
	echo " - $webDirectory/live/*.index"
	echo " - $webDirectory/live/*.png"
	echo " - $webDirectory/live/*.js"
	echo " - $webDirectory/live/cache/*.index"
	echo " - $webDirectory/live/*"
	echo "Starting delete..."
	rm -v "$webDirectory"/live/*.html
	rm -rv "$webDirectory"/groups/*/
	rm -v "$webDirectory"/live/*.php
	rm -v "$webDirectory"/live/*.index
	rm -v "$webDirectory"/live/*.png
	rm -v "$webDirectory"/live/*.js
	rm -v "$webDirectory"/live/cache/*.index
	rm -rv "$webDirectory"/live/
}
################################################################################
checkCron(){
	webDirectory=$(webRoot)
	############ check if this script is already running on the system
	if pgrep iptv2web;then
		# if the script is running already do not launch a duplicate process
		echo "[WARNING]: iptv4everyone_cron is already running..."
		echo "[WARNING]: Only one instance of iptv4everyone_cron should be run at a time..."
		exit
	fi
	#############################################################
	if test -f "$webDirectory/live/update.cfg";then
		echo "[START]: Update started on $(date)"
		echo "[WEBGEN]: Generate and update webpages"
		main webGen
		echo "[UPDATE]: Combine all iptv configs..."
		main update
		echo "[WEBGEN]: Generate and update webpages"
		main webGen
		echo "[CLEAN]: Remove the update config"
		rm -rv "$webDirectory/live/update.cfg"
		echo "[END]: Update ended at $(date)"
	fi
	########################################################################
}
################################################################################
main(){
	# check if the module is enabled
	if test -f "/etc/2web/mod_status/iptv2web.cfg";then
		# the config exists check the config
		if grep -q "enabled" "/etc/2web/mod_status/iptv2web.cfg";then
			# the module is enabled
			echo "Preparing to process iptv..."
		else
			ALERT "MOD IS DISABLED!"
			ALERT "Edit /etc/2web/mod_status/iptv2web.cfg to contain only the text 'enabled' in order to enable the 2web module."
			# the module is not enabled
			# - remove the files and directory if they exist
			nuke
			exit
		fi
	else
		createDir "/etc/2web/mod_status/"
		# the config does not exist at all create the default one
		# - the default status for iptv2web should be disabled
		echo -n "disabled" > "/etc/2web/mod_status/iptv2web.cfg"
		chown www-data:www-data "/etc/2web/mod_status/iptv2web.cfg"
		# exit the script since by default the module is disabled
		exit
	fi
	################################################################################
	# if --debug flag used activate bash debugging for script
	if echo "$@" | grep "debug";then
		set -x
	fi
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		webGen
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		################################################################################
		# check if system is active
		if test -f "/tmp/iptv2web.active";then
			# system is already running exit
			ERROR "iptv2web is already processing data in another process."
			ERROR "IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/iptv2web.active'."
			exit
		else
			# set the active flag
			touch /tmp/iptv2web.active
			# create a trap to remove nfo2web
			trap "rm -v /tmp/iptv2web.active" EXIT
		fi
		# run full update
		fullUpdate
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "-c" ] || [ "$1" == "--cron" ] || [ "$1" == "cron" ] ;then
		checkCron
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade streamlink and youtube-dl pip packages
		pip3 install --upgrade streamlink
		pip3 install --upgrade youtube-dl
	elif [ "$1" == "-l" ] || [ "$1" == "--libary" ] || [ "$1" == "libary" ] ;then
		ALERT "Checking if Cached HLS.js is older than 120 days..."
		if cacheCheck "$(webRoot)/live/hls.js" 120;then
			ALERT "Updating Cached HLS.js..."
			# download the latest version of the javascript video player libary
			#curl --silent https://hls-js.netlify.app/dist/hls.js > "$(webRoot)/live/hls.js"
			cat /usr/share/2web/iptv/hls.js > "$(webRoot)/live/hls.js"
			# check the hls.js downloaded correctly
			if [ $(wc -l "$(webRoot)/live/hls.js") -le 0 ];then
				# the downloaded file has 0 lines of text and the download has failed
				ALERT "The download of hls.js has failed..."
				# try to use the backup hls.js
				if test -f "$(webRoot)/live/hls.js.backup";then
					cp "$(webRoot)/live/hls.js.backup" "$(webRoot)/live/hls.js"
				else
					ALERT "NO BACKUP OF hls.js COULD BE FOUND, playback will be broken."
					ALERT "iptv2web can not build a functional website without a copy of hls.js"
					exit
				fi
			else
				INFO "hls.js has been updated correctly..."
				# save this downloaded file as a backup
				cp "$(webRoot)/live/hls.js" "$(webRoot)/live/hls.js.backup"
			fi
		fi
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/iptv2web.txt"
	else
		main --update
		main --webgen
		main --help
	fi
}
################################################################################
main "$@"
exit
