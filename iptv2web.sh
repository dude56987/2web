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
export PS4='+ ${LINENO}	|	'
# set tab size to 4 to make output more readable
tabs 4
################################################################################
################################################################################
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
function killFakeImage(){
	localIconPath=$1
	# if the file exists
	if [ -f "$localIconPath" ];then
		# check it is not a empty file
		if file "$localIconPath" | grep "empty";then
			# remote the empty file
			rm -v "$localIconPath"
		elif file "$localIconPath" | grep "text";then
			# remove remote downloaded http redirect, 404, etc.
			rm -v "$localIconPath"
		fi
	fi
	return 0
}
################################################################################
function streamPass(){
	# pass streamlink arguments to correct streamlink path
	if [ -f "/usr/local/bin/streamlink" ];then
		/usr/local/bin/streamlink "$@"
	elif [ -f "/usr/bin/streamlink" ];then
		/usr/bin/streamlink "$@"
	else
		# could not find streamlink installed on the server
		echo "[ERROR]: For the URL to resolve you must install streamlink on this server."
		echo "[ERROR]: You may need to contact your local system administrator."
		echo "[INFO]: As a administrator use 'pip3 install streamlink' to install the latest version."
		exit
	fi
}
################################################################################
examineIconLink(){
	###################################################################
	iconLink=$1
	link=$2
	title=$3
	###################################################################
	iconLength=$(echo "$iconLink" | wc -c)
	sum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
	echo "Icon Sum=$sum"
	localIconPath="$(webRoot)/live/$sum.png"
	if [ "$iconLength" -gt 3 ];then
		# build a md5sum from the icon link
		#sum=$(echo "$iconLink" | md5sum | cut -d' ' -f1)
		if ! [ -f "$localIconPath" ];then
			# if the file does not exist in the cache download it
			curl "$iconLink" > "$localIconPath"
			# resize the icon to standard size
			convert "$localIconPath" -resize 400x400\! "$localIconPath"
		fi
	fi
	# remove image if fake was created
	killFakeImage "$localIconPath"
	# try to download the icon with youtube-dl
	if ! [ -f "$localIconPath" ];then
		tempIconLink=$(youtube-dl -j "$link" | jq ".thumbnail")
		# if the file does not exist in the cache download it
		curl "$tempIconLink" > "$localIconPath"
		# resize the icon to standard size
		convert "$localIconPath" -resize 400x400\! "$localIconPath"
	fi
	# remove image if fake was created
	killFakeImage "$localIconPath"
	if ! [ -f "$localIconPath" ];then
		# resolve the link using streamlink to create a thumbnail
		#resolvedLink=$(streamlink --stream-url "$link" best)
		# check if the link is a twitch link, they preload ads in the first 15 seconds
		# so take the thumbnail from after this 15 seconds
		if echo "$link" | grep "twitch.tv";then
			tempTimeout=20
		else
			tempTimeout=0
		fi
		# build a thumbnail from the video source
		#timeout 30 ffmpeg -y -i "$resolvedLink" -ss 1 -frames:v 1 "$localIconPath"
		# this must be contained in a single line or the delay causes it to be blocked
		#timeout 30 ffmpeg -y -i "$(streamlink --stream-url "$link" best)" -ss 1 -frames:v 1 "$localIconPath"
		timeout 30 ffmpeg -y -i "$(streamPass --stream-url $link best)" -ss "$tempTimeout" -frames:v 1 "$localIconPath"
		# resize the icon to standard size
		convert "$localIconPath" -resize 400x400\! "$localIconPath"
		# add text over retrieved thumbnail
		convert "$localIconPath" -adaptive-resize 400x400\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -style Bold -size 400x400 -gravity center caption:"$title" -composite "$localIconPath"
	fi
	killFakeImage "$localIconPath"
	if ! [ -f "$localIconPath" ];then
		# generate a image for the page since none exists
		swirlAmount=$(echo "$title" | wc -c)
		convert -size 400x400 +seed "$title" plasma: -swirl "$swirlAmount" "$localIconPath"
		# add text over generated image
		convert "$localIconPath" -adaptive-resize 400x400\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -style Bold -size 400x400 -gravity center caption:"$title" -composite "$localIconPath"
	fi
}
################################################################################
function getIconLink(){
	lineCaught=$1
	# pipe the output of this function to get the iconLink, blank for no link
	if echo "$lineCaught" | grep -q 'tvg-logo="';then
		# store the icon if it is set
		tempIconLink=$(echo "$lineCaught" | grep --only-matching 'tvg-logo=".*"' | cut -d'"' -f2)
	elif echo "$lineCaught" | grep -q "tvg-logo='";then
		tempIconLink=$(echo "$lineCaught" | grep --only-matching "tvg-logo='.*'" | cut -d"'" -f2)
	else
		tempIconLink=""
	fi
	# return the link to be piped
	echo "$tempIconLink"
}
################################################################################
function process_M3U(){
	# open m3u files
	channels=$1
	outputFile=$2
	################################################################################
	# convert m3u files by downloading icons and redirecting to downloaded local icons
	channelNumber=1
	lineCaught=""
	IFS_BACKUP=$IFS
	IFS=$'\n'
	for line in $channels;do
		# if a info line was detected on the last line
		caughtLength=$(echo "$lineCaught" | wc -c)
		if [ "$caughtLength" -gt 1 ];then
			# pull the link on this line and store it
			title=$(echo "$lineCaught" | rev | cut -d',' -f1 | rev)
			echo "Found Title = $title" >> "/var/log/iptv4everyone.log"
			link=$line
			echo "Found Link = $link"
			# check if this link is a radio link
			radio="false"
			if echo $lineCaught | grep -E "radio=[\",']true";then
				# if the line is a radio entry
				radio="true"
			fi
			iconLink=$(getIconLink "$lineCaught")
			echo "Icon Link = $iconLink"
			iconSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
			echo "Icon MD5 = $iconLink"
			# try to download or create the thumbnail
			examineIconLink "$iconLink" "$link" "$title"
			# Write the new version of the lines to the outputFile
			#hostPath='http://'$(hostname)'.local:444/iptv-resolver.php?url="'$link'"'
			webIconPath="http://$(hostname).local:444/live/$iconSum.png"
			{
				echo "#EXTINF:-1 radio=\"$radio\" tvg-logo=\"$webIconPath\",$title"
				echo "$link"
			} >> "$outputFile"
			################################################################################
			# increment the channel number
			channelNumber=$(($channelNumber + 1))
			# invoke webgen to update webpage after adding new live link
			webgen
		fi
		# if the line is a info line
		if echo "$line" | grep -q "#EXTINF";then
			echo "Found info line '$line'"
			lineCaught="$line"
		else
			# reset the line caught variable
			lineCaught=""
		fi
	done
}
################################################################################
function process_M3U_file(){
	# open m3u files
	channels=$(grep -v "#EXTM3U" "$1")
	outputFile=$2
	process_M3U "$channels" "$outputFile"
}

################################################################################
function processLink(){
	link=$1
	channelsPath=$2
	################################################################################
	echo "Processing Link '$link'"
	echo "Channels Path '$channelsPath'"
	# check if link is a comment
	if echo "$link" | grep -E "^#";then
		# this link is a comment
		return 0
	fi
	# if the link is a local address
	if [ -f "$link" ];then
		echo "[INFO]: Link is a local address. Adding local file..."
		# add local files
		grep -v "#EXTM3U" "$link" >> "$channelsPath"
		return 0
	fi
	# if the link is a web address
	if echo "$link" | grep -E "^http";then
		echo "[INFO]: Link is a web url..."
		# if the link is a link to a playlist download the playlist
		if echo "$link" | grep -E "\.m3u$|\.m3u8$|\.m3u8\?|\.m3u\?";then
			echo "[INFO]: Link is a m3u playlist..."
			# if it is a playlist file add it to the list by download
			downloadedM3U=$(curl "$link" | grep -v "#EXTM3U")
			process_M3U "$downloadedM3U" "$channelsPath"
		else
			echo "[INFO]: Link is Unknown..."
			# if it is a known stream site use streamlink
			if streamPass --can-handle-url "$link";then
				echo "[INFO]: Link can be processed by streamlink..."
				# determine the local hostname, use it to build the resolver path
				hostPath='http://'$(hostname)'.local:444/live/iptv-resolver.php?url="'$link'"'
				hostPathHD='http://'$(hostname)'.local:444/live/iptv-resolver.php?HD="true"&url="'$link'"'
				#hostPath='iptv-resolver.php?url="'$link'"'
				#hostPathHD='iptv-resolver.php?HD="true"&url="'$link'"'
				thumbnailLink="0"
				if which youtube-dl && which jq;then
					echo "[INFO]: Attempting to get link metadata with youtube-dl ..."
					tempMeta=$(youtube-dl -j "$link")
					if echo "$link" | grep "youtube.com";then
						if echo "$tempMeta" | grep "fulltitle";then
							fileName=$(echo "$tempMeta" | jq ".fulltitle" | tr -d '"')
							echo "[INFO]: link title = $fileName"
						fi
						if echo "$tempMeta" | grep "thumbnail";then
							thumbnailLink=$(echo "$tempMeta" | jq ".thumbnail" | tr -d '"')
							echo "[INFO]: thumbnailLink = $thumbnailLink"
						fi
					else
						fileName=$(echo "$tempMeta" | jq ".display_id"| tr -d '"')
						echo "[INFO]: link title  from display_id = $fileName"
					fi
				fi
				echo "[DEBUG]: checking filename length '$fileName'"
				tempFileName=$(echo "$fileName" | wc -c )
				tempFileName=$(($tempFileName))
				if [ 3 -gt $tempFileName ];then
					#fileName=$(echo "$link" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1)
					fileName=$(echo "$link" | rev | cut -d'/' -f1 | rev)
					echo "[DEBUG]: filename too short ripping end of url '$fileName'"
				fi
				echo "[DEBUG]: FileName = $fileName"
				examineIconLink "$thumbnailLink" "$hostPathHD" "$fileName HD"
				examineIconLink "$thumbnailLink" "$hostPath" "$fileName"
				sum=$(echo -n "$hostPath" | md5sum | cut -d' ' -f1)
				sumHD=$(echo -n "$hostPathHD" | md5sum | cut -d' ' -f1)
				echo "[DEBUG]: SUM = $sum"
				webIconPath="http://$(hostname).local:444/live/$sum.png"
				webIconPathHD="http://$(hostname).local:444/live/$sumHD.png"
				#webIconPath="$sum.png"
				# check if this link is a radio link
				radio="false"
				# if the line is a radio entry
				if echo $lineCaught | grep -E "radio=[\",']true";then
					# if the line is a radio entry
					radio="true"
				fi
				echo "[DEBUG]: WebIconPath = $webIconPath"
				{
					echo "#EXTINF:-1 radio=\"$radio\" tvg-logo=\"$webIconPath\",$fileName HD"
					echo "$hostPathHD"
					echo "#EXTINF:-1 radio=\"$radio\" tvg-logo=\"$webIconPathHD\",$fileName"
					echo "$hostPath"
				} >> "$channelsPath"
			else
				echo "[ERROR]: Custom url creation failed for '$link'"
				return 1
			fi
		fi
	fi
	# invoke webgen to update webpage after adding new live links
	webgen
	return 0
}
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/nfo2web/web.cfg ];then
		webDirectory=$(cat /etc/nfo2web/web.cfg)
	else
		mkdir -p /var/cache/nfo2web/web/
		chown -R www-data:www-data "/var/cache/nfo2web/web/"
		echo "/var/cache/nfo2web/web" > /etc/nfo2web/web.cfg
		webDirectory="/var/cache/nfo2web/web"
	fi
	mkdir -p "$webDirectory"
	echo "$webDirectory"
}
################################################################################
fullUpdate(){
	################################################################################
	# scan sources config file and fetch each source
	################################################################################
	# enable debug
	echo "Loading up sources..."
	# check for defined sources
	if [ -f /etc/iptv2web/sources.cfg ];then
		# load the config file
		linkList="$(loadWithoutComments /etc/iptv2web/sources.cfg)"
	else
		# if no config exists create the default config
		{
		echo "##################################################"
		echo "#Example Config"
		echo "##################################################"
		echo "# - You can use local filesystem .m3u/m3u8 sources"
		echo "#  ex."
		echo "# - You can use sources from remote http servers"
		echo "#  ex."
		echo "#    https://iptv-org.github.io/iptv/index.m3u"
		echo "# - You can also use streaming sites"
		echo "#  ex."
		echo "#    https://twitch.tv/username"
		echo "#  ex."
		echo "#    https://www.youtube.com/watch?v=F109TZt3nRc"
		echo "##################################################"
		# write the new config from the path variable
		echo "https://iptv-org.github.io/iptv/index.m3u"
		} > /etc/iptv2web/sources.cfg
	fi
	################################################################################

	webDirectory=$(webRoot)
	channelsPath="$webDirectory/live/channels.m3u"
	# link the channels to the kodi directory
	ln -s "$channelsPath" "$webDirectory/kodi/channels.m3u"
	# for each link in the sources
	echo "Processing sources..."
	echo "Link List = $linkList"
	echo "#EXTM3U" > $channelsPath
	# add user created custom local configs first
	ls -t1 /etc/iptv2web/sources.d/*.m3u
	if [ $? -eq 0 ];then
		for configFile in /etc/iptv2web/sources.d/*.m3u;do
			# add file to main m3u, exclude description line
			process_M3U_file "$configFile" "$channelsPath"
			#cat "$configFile" | grep -v "#EXTM3U" >> $channelsPath
		done
	fi
	ls -t1 /etc/iptv2web/sources.d/*.m3u8
	if [ $? -eq 0 ];then
		for configFile in /etc/iptv2web/sources.d/*.m3u8;do
			# add file to main m3u8, exclude description line
			process_M3U_file "$configFile" "$channelsPath"
			#cat "$configFile" | grep -v "#EXTM3U" >> $channelsPath
		done
	fi
	# read main config m3u sources and merge them
	for link in $linkList;do
		processLink "$link" "$channelsPath"
	done
	# add external sources last
	ls -t1 /etc/iptv2web/sources.d/*.cfg
	if [ $? -eq 0 ];then
		for configFile in $(ls -t1 /etc/iptv2web/sources.d/*.cfg | tac);do
			for link in $(loadWithoutComments "$configFile");do
				processLink "$link" "$channelsPath"
			done
		done
	fi
}
################################################################################
################################################################################
################################################################################

################################################################################
#set -x #debug
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
		#echo ">"
		echo "</iframe>"
	else
		# build the page but dont write it, this function is intended to be
		# piped into a file
		echo -e "$tabs<script src='hls.js'></script>"
		echo -e "$tabs<video id='video' class='livePlayer' poster='$poster' controls autoplay></video>"
		echo -e "$tabs<script>"
		echo -e "$tabs	if(Hls.isSupported()) {"
		echo -e "$tabs		var video = document.getElementById('video');"
		echo -e "$tabs		var hls = new Hls({"
		echo -e "$tabs			debug: true"
		echo -e "$tabs		});"
		echo -e "$tabs		hls.loadSource('$link');"
		echo -e "$tabs		hls.attachMedia(video);"
		echo -e "$tabs		hls.on(Hls.Events.MEDIA_ATTACHED, function() {"
		echo -e "$tabs			video.muted = true;"
		echo -e "$tabs			video.play();"
		echo -e "$tabs		});"
		echo -e "$tabs	}"
		echo -e "$tabs	else if (video.canPlayType('application/vnd.apple.mpegurl')) {"
		echo -e "$tabs		video.src = '$link';"
		echo -e "$tabs		video.addEventListener('canplay',function() {"
		echo -e "$tabs			video.play();"
		echo -e "$tabs		});"
		echo -e "$tabs	}"
		echo -e "$tabs</script>"
	fi
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
		#echo ">"
		echo "</iframe>"
	else
		# build the page but dont write it, this function is intended to be
		# piped into a file
		#echo -e "$tabs<div class='livePlayer'>"
		# make the background for the audio player the poster of the audio stream
		customStyle="background-image: url(\"$poster\");"
		echo -e "$tabs<audio class='livePlayer' style='$customStyle' poster='$poster' controls autoplay>"
		echo -e "$tabs<source src='$link' type='audio/mpeg'>"
		echo -e "$tabs</audio>"
		#echo -e "$tabs</div>"
	fi
}
################################################################################
################################################################################
webGen(){
	webDirectory=$(webRoot)
	channelsPath="$webDirectory/live/channels.m3u"
	################################################################################
	if ! [ -f "$webDirectory/live/hls.js" ];then
		# update the hls javascript libary, if no version has been downloaded
		main libary
	fi
	################################################################################
	# copy over the stylesheet
	ln -s "$webDirectory/style.css" "$webDirectory/live/style.css"
	# copy over the resolver
	ln -s "/usr/share/nfo2web/iptv-resolver.php" "$webDirectory/live/iptv-resolver.php"
	################################################################################
	#build the header
	################################################################################
	channels=$(cat "$channelsPath" | grep -v "#EXTM3U")
	# split lines on line endings not spaces and line endings
	lineCaught=""
	IFS_BACKUP=$IFS
	IFS=$'\n'
	echo "" > "$webDirectory/live/channelList.html"
	################################################################################
	# build the channel list
	################################################################################
	channelNumber=1
	for line in $channels;do
		echo "[INFO]: building channel list for line = $line"
		# if a info line was detected on the last line
		if [ 1 -lt $(echo "$lineCaught" | wc -c) ];then
			# pull the link on this line and store it
			title=$(echo "$lineCaught" | cut -d',' -f2)
			link=$line
			#iconLink=$(getIconLink "$lineCaught")
			iconSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
			iconLink="$iconSum.png"
			echo "[INFO]: Found Title = $title"
			echo "[INFO]: Found Link = $link"
			echo "[INFO]: Icon Link = $iconLink"
			echo "[INFO]: Icon MD5 = $iconSum"
			#examineIconLink "$iconLink" "$link" "$title"
			################################################################################
			# add links to channel list
			if echo $lineCaught | grep -Eq "radio=[\",']true";then
				# if the link is a radio station
				{
					echo -e "<div id='$channelNumber'>"
					echo -e "\t<a class='channelLink' href='channel_$channelNumber.html#$channelNumber'>"
					echo -e "\t\t<img loading='lazy' class='channelIcon' src='$iconLink'>"
					#echo -e "\t\t$channelNumber $title"
					echo -e "\t\t$title"
					echo -e "\t<div class='radioIcon'>"
					#echo -e "\t&#9835;"
					echo -e "\t&#128251;"
					echo -e "\t</div>"
					echo -e "\t</a>"
					echo -e "</div>"
				} >> "$webDirectory/live/channelList.html"
			else
				{
					echo -e "<div id='$channelNumber'>"
					echo -e "\t<a class='channelLink' href='channel_$channelNumber.html#$channelNumber'>"
					echo -e "\t\t<img loading='lazy' class='channelIcon' src='$iconLink'>"
					#echo -e "\t\t$channelNumber $title"
					echo -e "\t\t$title"
					echo -e "\t<div class='radioIcon'>"
					echo -e "\t&#128250;"
					echo -e "\t</div>"
					echo -e "\t</a>"
					echo -e "</div>"
				} >> "$webDirectory/live/channelList.html"
			fi
			channelNumber=$(($channelNumber + 1))
		fi
		# if the line is a info line
		if echo "$line" | grep "#EXTINF";then
			echo "[INFO]: Found info line '$line'"
			lineCaught=$line
		else
			# reset the line caught variable
			lineCaught=""
		fi
	done
	################################################################################
	# build each channel page
	################################################################################
	channelNumber=1
	for line in $channels;do
		echo "[INFO]: building channel page for line = $line"
		# if a info line was detected on the last line
		caughtLength=$(echo "$lineCaught" | wc -c)
		if [ "$caughtLength" -gt 1 ];then
			# pull the link on this line and store it
			title=$(echo "$lineCaught" | cut -d',' -f2)
			link=$line
			iconSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
			#iconLink=$(getIconLink "$lineCaught")
			iconLink="$iconSum.png"
			echo "[INFO]: Found Title = $title"
			echo "[INFO]: Found Link = $link"
			echo "[INFO]: Icon Link = $iconLink"
			echo "[INFO]: Icon MD5 = $iconLink"
			{
				# build the page
				echo "<html id='top' class='liveBackground'>"
				echo "<head>"
				echo "	<link rel='stylesheet' type='text/css' href='style.css'>"
				echo "</head>"
				echo "<body>"
				# place the header
				cat "$webDirectory/header.html" | sed "s/href='/href='..\//g"
				echo "<a href='channels.m3u' id='channelsDownloadLink'"
				echo " class='button'>channels.m3u</a>"
				echo "<div>"
				if echo $lineCaught | grep -Eq "radio=[\",']true";then
					buildRadioPage "$title" "$link" "$iconLink" "\t\t\t"
				else
					buildPage "$title" "$link" "$iconLink" "\t\t\t"
				fi
				echo "	<div class='channelList'>"
				# create the line that will be replaced by the link list to all the channels
				for channelLine in $(cat "$webDirectory/live/channelList.html");do
					echo -e "\t\t$channelLine"
				done
				echo "	</div>"
				echo "</div>"
				echo "<br>"
				echo "<div class='descriptionCard'>"
				echo "	<a class='channelLink' href='channel_$channelNumber.html#$channelNumber'>"
				echo "		$title"
				echo "	</a>"
				echo "	<div>"
				echo "		Hard Link : <a href='$link'>$link</a>"
				echo "	</div>"
				echo "</div>"
				# create top jump button
				echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
				echo "</body>"
				echo "</html>"
			} > "$webDirectory/live/channel_$channelNumber.html"
			################################################################################
			# increment the channel number
			channelNumber=$(($channelNumber + 1))
		fi
		# if the line is a info line
		if echo "$line" | grep "#EXTINF";then
			echo "[INFO]: Found info line '$line'"
			lineCaught="$line"
		else
			# reset the line caught variable
			lineCaught=""
		fi
	done
	################################################################################
	# build the index page
	################################################################################
	{
		echo -e "<html id='top' class='liveBackground'>"
		echo -e "<head>"
		echo -e "\t<link rel='stylesheet' type='text/css' href='style.css'>"
		echo "<script>"
		cat /usr/share/nfo2web/nfo2web.js
		echo "</script>"
		echo -e "</head>"
		echo -e "<body>"
		# place the header
		sed "s/href='/href='..\//g" < "$webDirectory/header.html"
		echo "<a href='channels.m3u' id='channelsDownloadLink'"
		echo " class='button'>channels.m3u</a>"

		echo -n "<input type='button' class='button' value='&#128250;'"
		echo    " onclick='filterByClass(\"indexLink\",\"&#128250;\")'>"

		echo " <input id='searchBox' type='text'"
		echo " onkeyup='filter(\"indexLink\")' placeholder='Search...' >"

		echo -n "<input type='button' class='button' value='&#9746;'"
		echo    " onclick='filterByClass(\"indexLink\",\"\")'>"

		echo -n "<input type='button' class='button' value='&#128251;'"
		echo    " onclick='filterByClass(\"indexLink\",\"&#128251;\")'>"

		echo -e "<div class='indexBody'>"
	} > "$webDirectory/live/index.html"
	channelNumber=1
	for line in $channels;do
		echo "[INFO]: building channel index entry for line = $line"
		# if a info line was detected on the last line
		caughtLength=$(echo "$lineCaught" | wc -c)
		if [ "$caughtLength" -gt 1 ];then
			# pull the link on this line and store it
			title=$(echo "$lineCaught" | cut -d',' -f2)
			link=$line
			iconSum=$(echo -n "$link" | md5sum | cut -d' ' -f1)
			#iconLink=$(getIconLink "$lineCaught")
			iconLink="$iconSum.png"
			iconLength=$(echo "$iconLink" | wc -c)
			echo "[INFO]: Found Title = $title"
			echo "[INFO]: Found Link = $link"
			echo "[INFO]: Icon Link = $iconLink"
			echo "[INFO]: Icon MD5 = $iconSum"
			if echo $lineCaught | grep -Eq "radio=[\",']true";then
				{
					# build icon to link to the channel
					echo -e "<a class='indexLink button radio' href='channel_$channelNumber.html#$channelNumber'>"
					echo -e "\t<img loading='lazy' class='indexIcon' src='$iconLink'>"
					echo -e "\t<div class='indexTitle'>"
					echo -e "\t\t$title"
					echo -e "\t<div class='radioIcon'>"
					echo -e "\t&#128251;"
					echo -e "\t</div>"
					echo -e "\t</div>"
					echo -e "</a>"
				} >> "$webDirectory/live/index.html"
			else
				{
					# build icon to link to the channel
					echo -e "<a class='indexLink button tv' href='channel_$channelNumber.html#$channelNumber'>"
					echo -e "\t<img loading='lazy' class='indexIcon' src='$iconLink'>"
					echo -e "\t<div class='indexTitle'>"
					echo -e "\t\t$title"
					echo -e "\t<div class='radioIcon'>"
					#echo -e "\t&#128250;"
					echo -e "\t&#128250;"
					echo -e "\t</div>"
					echo -e "\t</div>"
					echo -e "</a>"
				} >> "$webDirectory/live/index.html"
			fi
			################################################################################
			# increment the channel number
			channelNumber=$(($channelNumber + 1))
		fi
		# if the line is a info line
		if echo "$line" | grep "#EXTINF";then
			echo "[INFO]: Found info line '$line'"
			lineCaught="$line"
		else
			# reset the line caught variable
			lineCaught=""
		fi
	done
	{
		echo -e "</div>"
		# add the footer
		cat "$webDirectory/header.html" | sed "s/href='/href='..\//g"
		echo -e "</body>"
		# create top jump button
		echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
		echo -e "</html>"
	} >> "$webDirectory/live/index.html"
	IFS=$IFS_BACKUP
}
################################################################################
################################################################################
################################################################################
resetCache(){
	webDirectory=$(webRoot)
	echo "The paths to be removed are"
	echo " - $webDirectory/live/*.html"
	echo " - $webDirectory/live/*.png"
	rm -v "$webDirectory"/live/*.html
	rm -v "$webDirectory"/live/*.png
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
	if [ -f "$webDirectory/live/update.cfg" ];then
		echo "[START]: Update started on $(date)"
		echo "[UPDATE]: Combine all iptv configs..."
		main update
		echo "[WEBGEN]: Generate and update webpages"
		main webgen
		echo "[CLEAN]: Remove the update config"
		rm -rv "$webDirectory/live/update.cfg"
		echo "[END]: Update ended at $(date)"
	fi
	########################################################################
}
################################################################################
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		webGen
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		fullUpdate
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "-c" ] || [ "$1" == "--cron" ] || [ "$1" == "cron" ] ;then
		checkCron
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade streamlink and youtube-dl pip packages
		pip3 install --upgrade streamlink
		pip3 install --upgrade youtube-dl
	elif [ "$1" == "-l" ] || [ "$1" == "--libary" ] || [ "$1" == "libary" ] ;then
		# download the latest version of the javascript video player libary
		curl https://hls-js.netlify.app/dist/hls.js > "$(webRoot)/live/hls.js"
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		echo "########################################################################"
		echo "# iptv4everyone CLI for administration"
		echo "# Copyright (C) 2020  Carl J Smith"
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
		echo "This is the iptv4everyone administration and update program."
		echo "To return to this menu use 'iptv4everyone help'"
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
	else
		main --update
		main --webgen
		main --help
	fi
}
################################################################################
main "$@"
exit
