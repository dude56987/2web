#! /bin/bash
########################################################################
# NFO2WEB generates websites from nfo filled directories
# Copyright (C) 2016  Carl J Smith
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
########################################################################
STOP(){
	echo ">>>>>>>>>>>DEBUG STOPPER<<<<<<<<<<<" #DEBUG DELETE ME
	read -r #DEBUG DELETE ME
}
########################################################################
cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	if [ -f /usr/bin/inline-detox ];then
		echo "$1" | inline-detox --remove-trailing | sed "s/_/ /g" | tr -d '#'
	else
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
	fi
}
########################################################################
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
########################################################################
function validString(){
	stringToCheck="$1"
	if ! echo "$@" | grep -q "\-q";then
		echo "[INFO]: Checking string '$stringToCheck'"
	fi
	# convert string letters to all uppercase and look for returned NULL string
	# jq returns these strings instead of failing outright
	if echo "${stringToCheck^^}" | grep "NULL";then
		# this means the function is a null string returned by jq
		if ! echo "$@" | grep -q "\-q";then
			echo "[WARNING]:string is a NULL value"
		fi
		return 2
	elif [ 1 -ge "$(expr length "$stringToCheck")" ];then
		# this means the string is only one character
		if ! echo "$@" | grep -q "\-q";then
			echo "[WARNING]:String length is less than one"
		fi
		return 1
	else
		# all checks have been passed the string is correct
		if ! echo "$@" | grep -q "\-q";then
			echo "[INFO]: String passed all checks and is correct"
		fi
		return 0
	fi
}
########################################################################
ripXmlTag(){
	data=$1
	tag=$2
	# remove complex xml tags, they make parsing more difficult
	#data=$(echo "$data" | grep -v "/>")
	data=$(echo "$data" | grep -Ez --ignore-case --only-matching "<$tag>.*</$tag>")
	# remove after slash tags, they break everything
	data="${data//<$tag \/>}"
	#data=$(grep -Ez --ignore-case --only-matching "<$tag>.*</$tag>" < "$data")
	#data=$("$data" | grep -Ez --ignore-case --only-matching "<$tag>.*</$tag>")
	# convert null line endings
	# remove all new lines so grep can read multi line entries
	#data=$(echo "$data" | tr -d '\n')
	#data=$(echo "$data" | sed -z "s/\n/<br>/g")
	data="${data//<$tag>}"
	data="${data//<\/$tag>}"
	# if multuple lines of tag info are given format them for html
	#if [ "$(echo "$data" | wc -l)" -gt 1 ];then
	#	lineEnding="<br>"
	#else
	#	lineEnding=""
	#fi
	if validString "$tag" -q;then
		# loop though info
		echo -e "$data" | while read -r line;do
			# read lines until you reach the end tag line
			#echo "$line$lineEnding"
			echo "$line"
		done
		return 0
	else
		echo "[DEBUG]: Tag must be at least one character in length!"
		echo "[ERROR]: Program FAILURE has occured!"
		return 1
	fi
}
########################################################################
cleanXml(){
	data=$1
	tag=$2
	cleanText "$(ripXmlTag "$data" "$tag")"
}
########################################################################
processMovie(){
	moviePath=$1
	webDirectory=$2
	# figure out the movie directory
	#movieDir=$(echo "$moviePath" | rev | cut -d'/' -f'2-' | rev )
	movieDir=$moviePath
	# find the movie nfo in the movie path
	moviePath=$(find "$moviePath"/*.nfo)
	# create log path
	logPagePath="$webDirectory/log.html"
	echo "################################################################################"
	echo "Processing movie $moviePath"
	echo "################################################################################"
	# if moviepath exists
	if [ -f "$moviePath" ];then
		# create the path sum for reconizing the libary path
		pathSum=$(echo "$movieDir" | md5sum | cut -d' ' -f1)

		################################################################################
		# for each episode build a page for the episode
		nfoInfo=$(cat "$moviePath")
		# rip the movie title
		movieTitle=$(cleanXml "$nfoInfo" "title")
		echo "[INFO]: movie title = '$movieTitle'"
		movieYear=$(cleanXml "$nfoInfo" "year")
		echo "[INFO]: movie year = '$movieYear'"
		#moviePlot=$(ripXmlTag "$nfoInfo" "plot" | txt2html --extract -p 10)
		moviePlot=$(ripXmlTag "$nfoInfo" "plot")
		echo "[INFO]: movie plot = '$moviePlot'"
		moviePlot=$(echo "$moviePlot" | inline-detox -s "utf_8-only" )
		moviePlot=$(echo "$moviePlot" | sed "s/_/ /g" )
		#moviePlot=$(echo "$moviePlot" | recode ..HTML)
		#echo "[INFO]: movie plot = '$moviePlot'"
		#moviePlot=$(echo "$moviePlot" | markdown )
		#echo "[INFO]: movie plot = '$moviePlot'"
		moviePlot=$(echo "$moviePlot" | txt2html --extract )
		echo "[INFO]: movie plot = '$moviePlot'"
		#moviePlot=$(echo "$moviePlot"| txt2html --link-only --extract -p 10 )
		#echo "[INFO]: movie plot = '$moviePlot'"
		# create the episode page path
		# each episode file title must be made so that it can be read more easily by kodi
		movieWebPath="${movieTitle} ($movieYear)"
		echo "[INFO]: movie web path = '$movieWebPath'"
		################################################################################
		# check the state now that the movie web path has been determined
		################################################################################
		# check movie state as soon as posible processing
		if [ -f "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg" ];then
			# a existing state was found
			currentSum=$(cat "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg")
			libarySum=$(getDirSum "$movieDir")
			# if the current state is the same as the state of the last update
			if [ "$libarySum" == "$currentSum" ];then
				# this means they are the same so no update needs run
				echo "[INFO]: State is unchanged for $movieTitle, no update is needed."
				echo "[DEBUG]: $currentSum == $libarySum"
				addToLog "INFO" "Movie unchanged" "$movieTitle, $currentSum" "$logPagePath"
				return
			else
				echo "[INFO]: States are diffrent, updating $movieTitle..."
				echo "[DEBUG]: $currentSum != $libarySum"
				updateInfo="$movieTitle\n$currentSum != $libarySum\n$(ls "$movieDir")"
				addToLog "UPDATE" "Updating Movie" "$updateInfo" "$logPagePath"
			fi
		else
			echo "[INFO]: No movie state exists for $movieTitle, updating..."
			addToLog "NEW" "Adding new movie " "$movieTitle" "$logPagePath"
		fi
		################################################################################
		# After checking state build the movie page path, and build directories/links
		################################################################################
		moviePagePath="$webDirectory/movies/$movieWebPath/index.html"
		echo "[INFO]: movie page path = '$moviePagePath'"
		mkdir -p "$webDirectory/movies/$movieWebPath/"
		chown -R www-data:www-data "$webDirectory/movies/$movieWebPath/"
		mkdir -p "$webDirectory/kodi/movies/$movieWebPath/"
		chown -R www-data:www-data "$webDirectory/kodi/movies/$movieWebPath/"
		# link stylesheets
		ln -s "$webDirectory/style.css" "$webDirectory/movies/$movieWebPath/style.css"
		################################################################################
		################################################################################
		# using the path of the found nfo file, check for a file extension
		#  that is not nfo
		#foundNumber="${moviePath//.nfo/}"
		#foundNumber=$(ls -1 "$foundNumber".*.nfo | wc -l)
		# if the filename has more than one specific nfo file
		#if [[ "$foundNumber" -gt 1 ]];then
			# excess files were found, all that should exist is a thumb, video, and nfo file
		#	echo "[INFO]: Excess files found..."
		#	addToLog "WARNING" "Excess files" "Found $foundNumber files in $movieDir" "$logPagePath"
		#elif [[ "$foundNumber" -lt 1 ]];then
		#	echo "[INFO]: No data files found..."
			# no files found this is a unpassable error
		#	addToLog "INFO" "No data files found ???" "Found $foundNumber NFO files in $movieDir" "$logPagePath"
			# exit function
		#	return
		#fi
		################################################################################
		# find the videofile refrenced by the nfo file
		if [ -f "${moviePath//.nfo/.mkv}" ];then
			videoPath="${moviePath//.nfo/.mkv}"
			sufix=".mkv"
		elif [ -f "${moviePath//.nfo/.iso}" ];then
			videoPath="${moviePath//.nfo/.iso}"
			sufix=".iso"
		elif [ -f "${moviePath//.nfo/.mp4}" ];then
			videoPath="${moviePath//.nfo/.mp4}"
			sufix=".mp4"
		elif [ -f "${moviePath//.nfo/.mp3}" ];then
			videoPath="${moviePath//.nfo/.mp3}"
			sufix=".mp3"
		elif [ -f "${moviePath//.nfo/.ogv}" ];then
			videoPath="${moviePath//.nfo/.ogv}"
			sufix=".ogv"
		elif [ -f "${moviePath//.nfo/.ogg}" ];then
			videoPath="${moviePath//.nfo/.ogg}"
			sufix=".ogg"
		elif [ -f "${moviePath//.nfo/.mpeg}" ];then
			videoPath="${moviePath//.nfo/.mpeg}"
			sufix=".mpeg"
		elif [ -f "${moviePath//.nfo/.mpg}" ];then
			videoPath="${moviePath//.nfo/.mpg}"
			sufix=".mpg"
		elif [ -f "${moviePath//.nfo/.avi}" ];then
			videoPath="${moviePath//.nfo/.avi}"
			sufix=".avi"
		elif [ -f "${moviePath//.nfo/.m4v}" ];then
			videoPath="${moviePath//.nfo/.m4v}"
			sufix=".m4v"
		elif [ -f "${moviePath//.nfo/.strm}" ];then
			videoPath="${moviePath//.nfo/.strm}"
			videoPath=$(cat "$videoPath")
			sufix=".strm"
		else
			echo "[ERROR]: could not find video file"
			addToLog "ERROR" "No video file in directory" "$movieDir" "$logPagePath"
			return
		fi
		# set the video type based on the found video path
		if echo "$videoPath" | grep --ignore-case ".mp3";then
			mediaType="audio"
			mimeType="audio/mp3"
		elif echo "$videoPath" | grep --ignore-case ".ogg";then
			mediaType="audio"
			mimeType="audio/ogg"
		elif echo "$videoPath" | grep --ignore-case ".ogv";then
			mediaType="video"
			mimeType="video/ogv"
		elif echo "$videoPath" | grep --ignore-case ".mp4";then
			mediaType="video"
			mimeType="video/mp4"
		elif echo "$videoPath" | grep --ignore-case ".m4v";then
			mediaType="video"
			mimeType="video/m4v"
		elif echo "$videoPath" | grep --ignore-case ".avi";then
			mediaType="video"
			mimeType="video/avi"
		elif echo "$videoPath" | grep --ignore-case ".mpeg";then
			mediaType="video"
			mimeType="video/mpeg"
		elif echo "$videoPath" | grep --ignore-case ".mpg";then
			mediaType="video"
			mimeType="video/mpg"
		elif echo "$videoPath" | grep --ignore-case ".mkv";then
			mediaType="video"
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		# link the movie nfo file
		echo "[INFO]: linking $moviePath to $webDirectory/movies/$movieWebPath/$movieWebPath.nfo"
		ln -s "$moviePath" "$webDirectory/movies/$movieWebPath/$movieWebPath.nfo"
		echo "[INFO]: linking $moviePath to $webDirectory/kodi/movies/$movieWebPath/$movieWebPath.nfo"
		ln -s "$moviePath" "$webDirectory/kodi/movies/$movieWebPath/$movieWebPath.nfo"
		# show gathered info
		echo "[INFO]: mediaType = $mediaType"
		echo "[INFO]: mimeType = $mimeType"
		echo "[INFO]: videoPath = $videoPath"
		movieVideoPath="${moviePath//.nfo/$sufix}"
		echo "[INFO]: movieVideoPath = $videoPath"

		# link the video from the libary to the generated website
		echo "[INFO]: linking '$movieVideoPath' to '$webDirectory/movies/$movieWebPath/$movieWebPath$sufix'"
		ln -s "$movieVideoPath" "$webDirectory/movies/$movieWebPath/$movieWebPath$sufix"

		echo "[INFO]: linking '$movieVideoPath' to '$webDirectory/kodi/movies/$movieWebPath/$movieWebPath$sufix'"
		ln -s "$movieVideoPath" "$webDirectory/kodi/movies/$movieWebPath/$movieWebPath$sufix"

		# remove .nfo extension and create thumbnail path
		thumbnail="${moviePath//.nfo}-poster"
		echo "[INFO]: thumbnail template = $thumbnail"
		echo "[INFO]: thumbnail path 1 = $thumbnail.png"
		echo "[INFO]: thumbnail path 2 = $thumbnail.jpg"
		# creating alternate thumbnail paths
		echo "[INFO]: thumbnail path 3 = '$movieDir/poster.jpg'"
		echo "[INFO]: thumbnail path 4 = '$movieDir/poster.png'"
		#
		thumbnailShort="${moviePath//.nfo}"
		echo "[INFO]: thumbnail path 5 = '$thumbnailShort.png'"
		echo "[INFO]: thumbnail path 6 = '$thumbnailShort.jpg'"
		thumbnailShort2="${moviePath//.nfo}-thumb"
		echo "[INFO]: thumbnail path 7 = '$thumbnailShort2.png'"
		echo "[INFO]: thumbnail path 8 = '$thumbnailShort2.jpg'"
		thumbnailPath="$webDirectory/movies/$movieWebPath/$movieWebPath-poster"
		thumbnailPathKodi="$webDirectory/kodi/movies/$movieWebPath/$movieWebPath-poster"
		echo "[INFO]: new thumbnail path = '$thumbnailPath'"
		# link all images to the kodi path
		if ls "$movieDir" | grep "\.jpg" ;then
			echo "[INFO]: Found media '$movieDir/*.jpg' !"
			ln -s "$movieDir"/*.jpg "$webDirectory/kodi/movies/$movieWebPath/"
			ln -s "$movieDir"/*.jpg "$webDirectory/movies/$movieWebPath/"
		elif ls "$movieDir" | grep "\.png" ;then
			echo "[INFO]: Found media '$movieDir/*.png' !"
			ln -s "$movieDir"/*.png "$webDirectory/kodi/movies/$movieWebPath/"
			ln -s "$movieDir"/*.png "$webDirectory/movies/$movieWebPath/"
		else
			echo "[ERROR]: No media files could be found!"
			addToLog "ERROR" "No media files could be found!" "$movieDir" "$logPagePath"
		fi
		# copy over subtitles
		if ls "$movieDir" | grep "\.srt" ;then
			ln -s "$movieDir"/*.srt "$webDirectory/kodi/movies/$movieWebPath/"
		elif ls "$movieDir" | grep "\.sub" ;then
			ln -s "$movieDir"/*.sub "$webDirectory/kodi/movies/$movieWebPath/"
		elif ls "$movieDir" | grep "\.idx" ;then
			ln -s "$movieDir"/*.idx "$webDirectory/kodi/movies/$movieWebPath/"
		fi
		# link the fanart
		if [ -f "$movieDir/fanart.png" ];then
			echo "[INFO]: Found $movieDir/fanart.png"
			fanartPath="fanart.png"
			echo "[INFO]: Found fanart at '$movieDir/$fanartPath'"
			ln -s "$movieDir/$fanartPath" "$webDirectory/movies/$movieWebPath/$fanartPath"
			ln -s "$movieDir/$fanartPath" "$webDirectory/kodi/movies/$movieWebPath/$fanartPath"
		elif [ -f "$show/fanart.jpg" ];then
			fanartPath="fanart.jpg"
			echo "[INFO]: Found fanart at '$movieDir/$fanartPath'"
			ln -s "$movieDir/$fanartPath" "$webDirectory/movies/$movieWebPath/$fanartPath"
			ln -s "$movieDir/$fanartPath" "$webDirectory/kodi/movies/$movieWebPath/$fanartPath"
		else
			echo "[WARNING]: could not find fanart '$movieDir/fanart.[png/jpg]'"
		fi
		# find the fanart for the episode background
		if [ -f "$movieDir/fanart.png" ];then
			tempStyle="html{ background-image: url(\"fanart.png\") }"
		elif [ -f "$movieDir/fanart.jpg" ];then
			tempStyle="html{ background-image: url(\"fanart.jpg\") }"
		fi
		# start rendering the html
		{
			echo "<html id='top'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			echo "<style>"
			echo "$tempStyle"
			#cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			#cat "$headerPagePath" | sed "s/href='/href='..\/..\//g"
			sed "s/href='/href='..\/..\//g" < "$headerPagePath"
			echo "<div class='titleCard'>"
			echo "<h1>$movieTitle</h1>"
			echo "</div>"
		} > "$moviePagePath"

		# check for the thumbnail and link it
		#checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi"
		thumbnailExt=getThumbnailExt "$thumbnailPath"

		# check for a local thumbnail
		if [ -f "$thumbnailPath.jpg" ];then
			echo "[INFO]: Thumbnail already linked..."
			thumbnailExt=".jpg"
		elif [ -f "$thumbnailPath.png" ];then
			echo "[INFO]: Thumbnail already linked..."
			thumbnailExt=".png"
		else
			echo "[INFO]: No thumbnail exists, looking for thumb file..."
			# no thumbnail has been linked or downloaded
			if [ -f "$thumbnail.png" ];then
				echo "[INFO]: found PNG thumbnail '$thumbnail.png'..."
				thumbnailExt=".png"
				# link thumbnail into output directory
				ln -s "$thumbnail.png" "$thumbnailPath.png"
				ln -s "$thumbnail.png" "$thumbnailPathKodi.png"
			elif [ -f "$thumbnail.jpg" ];then
				echo "[INFO]: found JPG thumbnail '$thumbnail.jpg'..."
				thumbnailExt=".jpg"
				# link thumbnail into output directory
				ln -s "$thumbnail.jpg" "$thumbnailPath.jpg"
				ln -s "$thumbnail.jpg" "$thumbnailPathKodi.jpg"
			elif [ -f "$movieDir/poster.jpg" ];then
				echo "[INFO]: found JPG thumbnail '$movieDir/poster.jpg'..."
				thumbnailExt=".jpg"
				# link thumbnail into output directory
				ln -s "$movieDir/poster.jpg" "$thumbnailPath.jpg"
				ln -s "$movieDir/poster.jpg" "$thumbnailPathKodi.jpg"
			elif [ -f "$movieDir/poster.png" ];then
				echo "[INFO]: found PNG thumbnail '$movieDir/poster.png'..."
				thumbnailExt=".png"
				# link thumbnail into output directory
				ln -s "$movieDir/poster.png" "$thumbnailPath.png"
				ln -s "$movieDir/poster.png" "$thumbnailPathKodi.png"
			elif [ -f "$thumbnailShort.png" ];then
				echo "[INFO]: found PNG thumbnail '$thumbnailShort.png'..."
				thumbnailExt=".png"
				# link thumbnail into output directory
				ln -s "$thumbnailShort.png" "$thumbnailPath.png"
				ln -s "$thumbnailShort.png" "$thumbnailPathKodi.png"
			elif [ -f "$thumbnailShort.jpg" ];then
				echo "[INFO]: found JPG thumbnail '$thumbnailShort.jpg'..."
				thumbnailExt=".jpg"
				# link thumbnail into output directory
				ln -s "$thumbnailShort.jpg" "$thumbnailPath.jpg"
				ln -s "$thumbnailShort.jpg" "$thumbnailPathKodi.jpg"
			elif [ -f "$thumbnailShort2.png" ];then
				echo "[INFO]: found PNG thumbnail '$thumbnailShort2.png'..."
				thumbnailExt=".png"
				# link thumbnail into output directory
				ln -s "$thumbnailShort2.png" "$thumbnailPath.png"
				ln -s "$thumbnailShort2.png" "$thumbnailPathKodi.png"
			elif [ -f "$thumbnailShort2.jpg" ];then
				echo "[INFO]: found JPG thumbnail '$thumbnailShort2.jpg'..."
				thumbnailExt=".jpg"
				# link thumbnail into output directory
				ln -s "$thumbnailShort2$thumbnailExt" "$thumbnailPath$thumbnailExt"
				ln -s "$thumbnailShort2$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
			else
				if echo "$nfoInfo" | grep "fanart";then
					# pull the double nested xml info for the movie thumb
					echo "[DEBUG]: ThumbnailLink phase 1 = $thumbnailLink"
					thumbnailLink=$(ripXmlTag "$nfoInfo" "fanart")
					echo "[DEBUG]: ThumbnailLink phase 2 = $thumbnailLink"
					thumbnailLink=$(ripXmlTag "$thumbnailLink" "thumb")
					echo "[DEBUG]: ThumbnailLink phase 3 = $thumbnailLink"
					if validString "$thumbnailLink";then
						echo "[INFO]: Try to download movie thumbnail..."
						echo "[INFO]: Thumbnail found at '$thumbnailLink'"
						addToLog "WARNING" "Downloading Thumbnail" "Creating thumbnail from link '$thumbnailLink'" "$showLogPath"
						thumbnailExt=".png"
						# download the thumbnail
						#curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
						curl "$thumbnailLink" | convert - "$thumbnailPath$thumbnailExt"
						# link the downloaded thumbnail
						ln -s "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
					else
						echo "[DEBUG]: Thumbnail link is invalid '$thumbnailLink'"
					fi
				fi
				touch "$thumbnailPath$thumbnailExt"
				# check if the thumb download failed
				tempFileSize=$(wc --bytes < "$thumbnailPath$thumbnailExt")
				echo "[DEBUG]: file size $tempFileSize"
				if [ "$tempFileSize" -eq 0 ];then
					addToLog "WARNING" "Generating Thumbnail" "$thumbnailLink" "$logPagePath"
					echo "[ERROR]: Failed to find thumbnail inside nfo file!"
					# try to generate a thumbnail from video file
					echo "[INFO]: Attempting to create thumbnail from video source..."
					#tempFileSize=0
					tempTotalFrames=$(mediainfo --Output="Video;%FrameCount%" "$movieVideoPath")
					tempFrameRate=$(mediainfo --Output="Video;%FrameRate%" "$movieVideoPath")
					if echo "$tempFrameRate" | grep -q ".";then
						# remove any found decimal places in the frame rate
						tempFrameRate=$(echo "$tempFrameRate" | cut -d'.' -f1)
					fi
					tempTimeCode=$(( $tempTotalFrames / 16 ))
					tempTimeCode=$(($tempTimeCode / $tempFrameRate))
					# - force the filesize to be large enough to be a complex descriptive thumbnail
					# - filesize of images is directly related to visual complexity
					largestFileSize=15000
					largestImage=""
					image=""
					while [ $tempFileSize -lt $largestFileSize ];do
						# - place -ss in front of -i for speed boost in seeking to correct frame of source
						# - tempTimeCode is in seconds
						# - '-y' to force overwriting the empty file
						echo "[DEBUG]: tempTotalFrames = $tempTotalFrames'"
						echo "[DEBUG]: ffmpeg -y -ss $tempTimeCode -i '$movieVideoPath' -vframes 1 '$thumbnailPath.png'"
						#ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 "$thumbnailPath.png"
						# store the image inside a variable
						image=$(ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 -f singlejpeg - | convert - "$thumbnailPath.png" )
						# resize the image before checking the filesize
						convert "$thumbnailPath.png" -adaptive-resize 400x200\! "$thumbnailPath.png"
						# get the size of the file, after it has been created
						tempFileSize=$(wc --bytes < "$thumbnailPath.png")
						# - increment the timecode to get from the video to find a thumbnail that is not
						#   a blank screen
						# - This will create 50 screenshots 500/10 and use the screenshot with the largest
						#   file size
						tempTimeCode=$(($tempTimeCode + 10))
						# after checking x seconds for a thumbnail of the thumbs created use the one with
						# the largest file size
						if [ $tempTimeCode -gt 500 ];then
							# break the loop by breaking the comparison
							tempFileSize=$largestFileSize
							# link the thumbnail created to the kodi path
							ln -s "$thumbnailPath.png" "$thumbnailPathKodi.png"
						elif [ $tempFileSize -eq 0 ];then
							# break the loop, no thumbnail could be generated at all
							# - Blank white or black space takes up more than 0 bytes
							# - A webpage generated thumbnail will be created as a alternative
							rm "$thumbnailPath.png"
							tempFileSize=16000
						fi
					done
				fi
			fi
		fi
		#TODO: here is where .strm files need checked for Plugin: eg. youtube strm files
		if echo "$videoPath" | grep --ignore-case "plugin://";then
			# change the video path into a video id to make it embedable
			#yt_id=${videoPath//plugin:\/\/plugin.video.youtube\/play\/?video_id=}
			#yt_id=$(echo "$videoPath" | sed "s/^.*\?video_id=//g")
			#yt_id=${videoPath//^.*\?video_id\=/}
			yt_id=${videoPath//*video_id=}
			echo "[INFO]: yt-id = $yt_id"
			ytLink="https://youtube.com/watch?v=$yt_id"
			{
				# embed the youtube player
				echo "<iframe width='560' height='315'"
				echo "src='https://www.youtube-nocookie.com/embed/$yt_id'"
				echo "frameborder='0'"
				echo "allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'"
				echo "allowfullscreen>"
				echo "</iframe>"
				echo "<div class='descriptionCard'>"
				# create a hard link
				echo "	<a class='button hardLink' href='$ytLink'>"
				echo "		Hard Link"
				echo "	</a>"
				echo "	$moviePlot"
				echo "</div>"
			} >> "$moviePagePath"
		elif echo "$videoPath" | grep "http";then
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$movieWebPath-poster$thumbnailExt' controls>"
				echo "<source src='$videoPath' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<div class='descriptionCard'>"
				# create a hard link
				if [ "$sufix" = ".strm" ];then
					echo "<a class='button hardLink' href='$videoPath'>"
					echo "Hard Link"
					echo "</a><br>"
				else
					echo "<a class='button hardLink' href='$movieWebPath$sufix'>"
					echo "Hard Link"
					echo "</a>"
				fi
				echo "$moviePlot"
				echo "</div>"
			} >> "$moviePagePath"
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType id='nfoMediaPlayer' poster='$movieWebPath-poster$thumbnailExt' controls>"
				echo "<source src='$movieWebPath$sufix' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<div class='descriptionCard'>"
				# create a hard link
				echo "<a class='button hardLink' href='$movieWebPath$sufix'>"
				echo "Hard Link"
				echo "</a>"
				echo "$moviePlot"
				echo "</div>"
			} >> "$moviePagePath"
		fi
		{
			# add footer
			sed "s/href='/href='..\/..\//g" < "$headerPagePath"
			echo "</body>"
			echo "</html>"
		} >> "$moviePagePath"
		################################################################################
		# add the movie to the movie index page
		################################################################################
		{
			echo "<a class='indexSeries' href='$movieWebPath'>"
			echo "	<img loading='lazy' src='$movieWebPath/$movieWebPath-poster$thumbnailExt'>"
			echo "	<div class='title'>"
			echo "		$movieTitle"
			echo "	</div>"
			echo "</a>"
		} > "$webDirectory/movies/$movieWebPath/movies.index"
	else
		echo "[WARNING]: The file '$moviePath' could not be found!"
	fi
	# update the path sum after successfull
	touch "$webDirectory/movies/$movieWebPath/"
	touch "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg"
	getDirSum "$movieDir" > "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg"
}
########################################################################
getThumbnailExt(){
	thumbnailPath=$1
	########################################################################
	if [ -f "$thumbnailPath.jpg" ];then
		thumbnailExt=".jpg"
	elif [ -f "$thumbnailPath.png" ];then
		thumbnailExt=".png"
	else
		return 1
	fi
	# return the thumbnail extension
	echo "$thumbnailExt"
	return 0
}
########################################################################
checkForThumbnail(){
	#checkForThumbnail $episode
	thumbnail=$1
	thumbnailPath=$2
	thumbnailPathKodi=$3
	########################################################################
	echo "[INFO]: new thumbnail path = $thumbnailPath"
	# check for a local thumbnail
	if [ -f "$thumbnailPath.jpg" ];then
		thumbnailExt=".jpg"
		echo "[INFO]: Thumbnail already linked..."
	elif [ -f "$thumbnailPath.png" ];then
		thumbnailExt=".png"
		echo "[INFO]: Thumbnail already linked..."
	else
		# no thumbnail has been linked or downloaded
		if [ -f "$thumbnail.png" ];then
			echo "[INFO]: found PNG thumbnail..."
			thumbnailExt=".png"
			# link thumbnail into output directory
			ln -s "$thumbnail.png" "$thumbnailPath.png"
			ln -s "$thumbnail.png" "$thumbnailPathKodi.png"
		elif [ -f "$thumbnail.jpg" ];then
			echo "[INFO]: found JPG thumbnail..."
			thumbnailExt=".jpg"
			# link thumbnail into output directory
			ln -s "$thumbnail.jpg" "$thumbnailPath.jpg"
			ln -s "$thumbnail.jpg" "$thumbnailPathKodi.jpg"
		else
			if echo "$nfoInfo" | grep "thumb";then
				thumbnailLink=$(ripXmlTag "$nfoInfo" "thumb")
				echo "[INFO]: Try to download episode thumbnail..."
				echo "[INFO]: Thumbnail found at $thumbnailLink"
				addToLog "WARNING" "Downloading Thumbnail" "Creating thumbnail from link '$thumbnailLink'" "$showLogPath"
				thumbnailExt=".png"
				# download the thumbnail
				#curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
				curl "$thumbnailLink" | convert - "$thumbnailPath$thumbnailExt"
				# link the downloaded thumbnail
				ln -s "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
			fi
			touch "$thumbnailPath$thumbnailExt"
			# check if the thumb download failed
			tempFileSize=$(wc --bytes < "$thumbnailPath$thumbnailExt")
			echo "[DEBUG]: file size $tempFileSize"
			if [ "$tempFileSize" -eq 0 ];then
				addToLog "WARNING" "Generating Thumbnail" "$videoPath" "$showLogPath"
				echo "[ERROR]: Failed to find thumbnail inside nfo file!"
				# try to generate a thumbnail from video file
				echo "[INFO]: Attempting to create thumbnail from video source..."
				#tempFileSize=0
				tempTotalFrames=$(mediainfo --Output="Video;%FrameCount%" "$videoPath")
				tempFrameRate=$(mediainfo --Output="Video;%FrameRate%" "$videoPath")
				if echo "$tempFrameRate" | grep -q ".";then
					# remove any found decimal places in the frame rate
					tempFrameRate=$(echo "$tempFrameRate" | cut -d'.' -f1)
				fi
				tempTimeCode=$(( $tempTotalFrames / 5 ))
				tempTimeCode=$(($tempTimeCode / $tempFrameRate))
				# - force the filesize to be large enough to be a complex descriptive thumbnail
				# - filesize of images is directly related to visual complexity
				largestFileSize=15000
				largestImage=""
				image=""
				while [ $tempFileSize -lt $largestFileSize ];do
					# - place -ss in front of -i for speed boost in seeking to correct frame of source
					# - tempTimeCode is in seconds
					# - '-y' to force overwriting the empty file
					echo "[DEBUG]: tempTotalFrames = $tempTotalFrames'"
					echo "[DEBUG]: ffmpeg -y -ss $tempTimeCode -i '$episodeVideoPath' -vframes 1 '$thumbnailPath.png'"
					#ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 "$thumbnailPath.png"
					# store the image inside a variable
					image=$(ffmpeg -y -ss $tempTimeCode -i "$episodeVideoPath" -vframes 1 -f singlejpeg - | convert - "$thumbnailPath.png" )
					# resize the image before checking the filesize
					convert "$thumbnailPath.jpg" -adaptive-resize 400x200\! "$thumbnailPath.jpg"
					tempFileSize=$(echo "$image" | wc --bytes)
					# get the size of the file, after it has been created
					tempFileSize=$(wc --bytes < "$thumbnailPath.png")
					# - increment the timecode to get from the video to find a thumbnail that is not
					#   a blank screen
					# - This will create 50 screenshots 500/10 and use the screenshot with the largest
					#   file size
					tempTimeCode=$(($tempTimeCode + 10))
					# after checking x seconds for a thumbnail of the thumbs created use the one with
					# the largest file size
					if [ $tempTimeCode -gt 500 ];then
						# break the loop by breaking the comparison
						tempFileSize=$largestFileSize
						# write the thubmnail data
						# link the thumbnail created to the kodi path
						ln -s "$thumbnailPath.png" "$thumbnailPathKodi.png"
					elif [ $tempFileSize -eq 0 ];then
						# break the loop, no thumbnail could be generated at all
						# - Blank white or black space takes up more than 0 bytes
						# - A webpage generated thumbnail will be created as a alternative
						rm "$thumbnailPath.png"
						tempFileSize=16000
					fi
				done
			fi
		fi
	fi
}
########################################################################
processEpisode(){
	episode="$1"
	episodeShowTitle="$2"
	showPagePath="$3"
	webDirectory="$4"
	# create log path
	#logPagePath="$webDirectory/log.html"
	logPagePath="$webDirectory/log.html"
	showLogPath="$webDirectory/shows/$episodeShowTitle/log.index"
	echo "[INFO]: checking if episode path exists $episode"
	# check the episode file path exists before anything is done
	if [ -f "$episode" ];then
		echo "################################################################################"
		echo "Processing Episode $episode"
		echo "################################################################################"
		# for each episode build a page for the episode
		nfoInfo=$(cat "$episode")
		# rip the episode title
		echo "[INFO]: Episode show title = '$episodeShowTitle'"
		episodeShowTitle=$(cleanText "$episodeShowTitle")
		echo "[INFO]: Episode show title after clean = '$episodeShowTitle'"
		episodeTitle=$(cleanXml "$nfoInfo" "title")
		echo "[INFO]: Episode title = '$episodeShowTitle'"
		#episodePlot=$(ripXmlTag "$nfoInfo" "plot" | txt2html --extract -p 10)
		#episodePlot=$(ripXmlTag "$nfoInfo" "plot" | recode ..html | txt2html --eight_bit_clean --extract -p 10 )
		#episodePlot=$(ripXmlTag "$nfoInfo" "plot" | recode ..html | txt2html -ec --eight_bit_clean --extract -p 10 )
		episodePlot=$(ripXmlTag "$nfoInfo" "plot")
		echo "[INFO]: episode plot = '$episodePlot'"
		episodePlot=$(echo "$episodePlot" | inline-detox -s "utf_8-only")
		episodePlot=$(echo "$episodePlot" | sed "s/_/ /g")
		#episodePlot=$(echo "$episodePlot" | recode ..HTML )
		echo "[INFO]: episode plot = '$episodePlot'"
		#episodePlot=$(echo "$episodePlot" | markdown )
		#echo "[INFO]: episode plot = '$episodePlot'"
		episodePlot=$(echo "$episodePlot" | txt2html --extract )
		echo "[INFO]: episode plot = '$episodePlot'"
		#episodePlot=$(echo "$episodePlot"| txt2html --link-only --extract -p 10 )
		episodeSeason=$(cleanXml "$nfoInfo" "season")
		echo "[INFO]: Episode season = '$episodeSeason'"
		episodeAired=$(ripXmlTag "$nfoInfo" "aired")
		echo "[INFO]: Episode air date = '$episodeAired'"
		if [ "$episodeSeason" -lt 10 ];then
			if ! echo "$episodeSeason"| grep "^0";then
				# add a zero to make it format correctly
				episodeSeason="0$episodeSeason"
			fi
		fi
		episodeSeasonPath="Season $episodeSeason"
		echo "[INFO]: Episode season path = '$episodeSeasonPath'"
		episodeNumber=$(cleanXml "$nfoInfo" "episode")
		if [ "$episodeNumber" -lt 10 ];then
			if ! echo "$episodeNumber"| grep "^0";then
				# add a zero to make it format correctly
				episodeNumber="0$episodeNumber"
			fi
		fi
		echo "[INFO]: Episode number = '$episodeNumber'"
		# create the episode page path
		# each episode file title must be made so that it can be read more easily by kodi
		episodePath="${showTitle} - s${episodeSeason}e${episodeNumber} - $episodeTitle"
		episodePagePath="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.html"
		# check the episode has not already been processed
		if [ -f "$episodePagePath" ];then
			# if this episode has already been processed by the system then skip processeing it with this function
			# - this also prevents caching below done for new cacheable videos
			return
		fi
		echo "[INFO]: Episode page path = '$episodePagePath'"
		echo "[INFO]: Making season directory at '$webDirectory/$episodeShowTitle/$episodeSeasonPath/'"
		mkdir -p "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/"
		chown -R www-data:www-data "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/"
		mkdir -p "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"
		chown -R www-data:www-data "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"
		# link stylesheet
		ln -s "$webDirectory/style.css" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/style.css"
		# find the videofile refrenced by the nfo file
		if [ -f "${episode//.nfo/.mkv}" ];then
			videoPath="${episode//.nfo/.mkv}"
			sufix=".mkv"
		elif [ -f "${episode//.nfo/.mp4}" ];then
			videoPath="${episode//.nfo/.mp4}"
			sufix=".mp4"
		elif [ -f "${episode//.nfo/.mp3}" ];then
			videoPath="${episode//.nfo/.mp3}"
			sufix=".mp3"
		elif [ -f "${episode//.nfo/.ogv}" ];then
			videoPath="${episode//.nfo/.ogv}"
			sufix=".ogv"
		elif [ -f "${episode//.nfo/.ogg}" ];then
			videoPath="${episode//.nfo/.ogg}"
			sufix=".ogg"
		elif [ -f "${episode//.nfo/.mpeg}" ];then
			videoPath="${episode//.nfo/.mpeg}"
			sufix=".mpeg"
		elif [ -f "${episode//.nfo/.mpg}" ];then
			videoPath="${episode//.nfo/.mpg}"
			sufix=".mpg"
		elif [ -f "${episode//.nfo/.avi}" ];then
			videoPath="${episode//.nfo/.avi}"
			sufix=".avi"
		elif [ -f "${episode//.nfo/.m4v}" ];then
			videoPath="${episode//.nfo/.m4v}"
			sufix=".m4v"
		elif [ -f "${episode//.nfo/.strm}" ];then
			videoPath="${episode//.nfo/.strm}"
			videoPath=$(cat "$videoPath")
			sufix=".strm"
		else
			# no video file could be found this should be logged
			echo "[ERROR]: could not find video file"
			addToLog "ERROR" "No video file" "$episode" "$showLogPath"
			# exit the function  cancel building the episode
			return
		fi
		# set the video type based on the found video path
		if echo "$videoPath" | grep --ignore-case ".mp3";then
			mediaType="audio"
			mimeType="audio/mp3"
		elif echo "$videoPath" | grep --ignore-case ".ogg";then
			mediaType="audio"
			mimeType="audio/ogg"
		elif echo "$videoPath" | grep --ignore-case ".ogv";then
			mediaType="video"
			mimeType="video/ogv"
		elif echo "$videoPath" | grep --ignore-case ".mp4";then
			mediaType="video"
			mimeType="video/mp4"
		elif echo "$videoPath" | grep --ignore-case ".m4v";then
			mediaType="video"
			mimeType="video/m4v"
		elif echo "$videoPath" | grep --ignore-case ".mpeg";then
			mediaType="video"
			mimeType="video/mpeg"
		elif echo "$videoPath" | grep --ignore-case ".mpg";then
			mediaType="video"
			mimeType="video/mpg"
		elif echo "$videoPath" | grep --ignore-case ".avi";then
			mediaType="video"
			mimeType="video/avi"
		elif echo "$videoPath" | grep --ignore-case ".mkv";then
			mediaType="video"
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		# find the fanart for the episode background
		if [ -f "$webDirectory/shows/$episodeShowTitle/fanart.png" ];then
			#tempStyle="html{ background-image: url('../fanart.png') }"
			tempStyle="--backgroundFanart: url(\"../fanart.png\");"
			#tempStyle="root:{ --backgroundFanart: url('../fanart.png');"
		elif [ -f "$webDirectory/shows/$episodeShowTitle/fanart.jpg" ];then
			#tempStyle="root:{ --backgroundFanart: url('../fanart.jpg');"
			tempStyle="--backgroundFanart: url(\"../fanart.jpg\");"
			#tempStyle="html{ background-image: url('../fanart.jpg') }"
		fi
		if [ -f "$webDirectory/shows/$episodeShowTitle/poster.png" ];then
			#tempStyle="$tempStyle --backgroundPoster: url('../poster.png')}"
			tempStyle="$tempStyle --backgroundPoster: url(\"../poster.png\")"
		elif [ -f "$webDirectory/shows/$episodeShowTitle/poster.jpg" ];then
			#tempStyle="$tempStyle --backgroundPoster: url('../poster.jpg')}"
			tempStyle="$tempStyle --backgroundPoster: url(\"../poster.jpg\")"
		fi
		# start rendering the html
		{
			# the style variable must be set inline, not in head, this may be a bug in firefox
			echo "<html id='top' class='seriesBackground' style='$tempStyle'>"
			#echo "<html id='top' class='seriesBackground' >"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			echo "<style>"
			#add the fanart
			#echo "$tempStyle"
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\/..\/..\//g"
			echo "<div class='titleCard'>"
			echo "<h1>$episodeShowTitle ${episodeSeason}x${episodeNumber}</h1>"
			echo "</div>"
		} > "$episodePagePath"
		# link the episode nfo file
		echo "[INFO]: linking $episode to $webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		ln -s "$episode" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		ln -s "$episode" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		# show info gathered
		echo "[INFO]: mediaType = $mediaType"
		echo "[INFO]: mimeType = $mimeType"
		echo "[INFO]: videoPath = $videoPath"
		episodeVideoPath="${episode//.nfo/$sufix}"
		echo "[INFO]: episodeVideoPath = $videoPath"

		# check for plugin links and convert the .strm plugin links into ytdl-resolver.php links
		if echo "$sufix" | grep --ignore-case "strm";then
			tempPath="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"

			# change the video path into a video id to make it embedable
			yt_id=${videoPath//*video_id=}
			echo "[INFO]: yt-id = $yt_id"
			ytLink="https://youtube.com/watch?v=$yt_id"

			# generate a link to the local caching resolver
			# - cache new links in batch processing mode
			resolverUrl="http://$(hostname).local:444/ytdl-resolver.php?&url=\"$ytLink\""
			# split up airdate data to check if caching should be done
			airedYear=$(echo "$episodeAired" | cut -d'-' -f1)
			airedMonth=$(echo "$episodeAired" | cut -d'-' -f2)
			echo "[DEBUG]:  Checking if file was released in the last month"
			echo "[DEBUG]:  aired year $airedYear == current year $(date +"%Y")"
			echo "[DEBUG]:  aired month $airedMonth == current month $(date +"%m")"
			# if the airdate was this year
			if [ $airedYear -eq "$(date +"%Y")" ];then
				# if the airdate was this month
				if [ $airedMonth -eq "$(date +"%m")" ];then
					# cache the video if it is from this month
					# - only newly created videos get this far into the process to be cached
					echo "[DEBUG]:  Caching file..."
					curl "$resolverUrl&batch=true" > /dev/null
				fi
			fi
			#if [ "$episodeAired" == "$(date +"%Y-%m-%d")" ];then
			#	echo "[INFO]: airdate $episodeAired == todays date $(date +'%Y-%m-%d') ]"
			#	# if the episode aired today cache the episode
			#	# - timeout will stop the download after 0.1 seconds
			#	timeout 0.1 curl "$resolverUrl" > /dev/null
			#fi
			echo "[INFO]: building resolver url for plugin link..."
			echo "$resolverUrl" > "$tempPath"
		else
			# link the video from the libary to the generated website
			echo "[INFO]: linking '$episodeVideoPath' to '$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix'"
			ln -s "$episodeVideoPath" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
			ln -s "$episodeVideoPath" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
		fi
		# remove .nfo extension and create thumbnail path
		thumbnail="${episode//.nfo}-thumb"
		echo "[INFO]: thumbnail template = $thumbnail"
		echo "[INFO]: thumbnail path 1 = $thumbnail.png"
		echo "[INFO]: thumbnail path 2 = $thumbnail.jpg"
		thumbnailPath="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		thumbnailPathKodi="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		# check for the thumbnail and link it
		checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi"
		thumbnailExt=$(getThumbnailExt "$thumbnailPath")
		#TODO: here is where .strm files need checked for Plugin: eg. youtube strm files
		if echo "$videoPath" | grep --ignore-case "plugin://";then
			# change the video path into a video id to make it embedable
			yt_id=${videoPath//*video_id=}
			echo "[INFO]: yt-id = $yt_id"
			ytLink="https://youtube.com/watch?v=$yt_id"
			{
				# embed the youtube player
				echo "<iframe id='nfoMediaPlayer' width='560' height='315'"
				echo "src='https://www.youtube-nocookie.com/embed/$yt_id'"
				echo "frameborder='0'"
				echo "allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'"
				echo "allowfullscreen>"
				echo "</iframe>"
			} >> "$episodePagePath"
			#fullRedirect="http://$(hostname).local:444/ytdl-resolver.php?url=\"$ytLink\""
			#{
			#	echo "<video id='nfoMediaPlayer' poster='$episodePath-thumb$thumbnailExt' controls>"
			#	echo "<source src='$fullRedirect' type='video/mp4'>"
			#	echo "</video>"
			#} >> "$episodePagePath"
			{
				echo "<div class='descriptionCard'>"
				echo "<h2>$episodeTitle</h2>"
				# create a hard link
				echo "<a class='button hardLink' href='$ytLink'>"
				echo "	Hard Link"
				echo "</a>"
				echo "<div class='aired'>"
				echo "$episodeAired"
				echo "</div>"
				echo "$episodePlot"
				echo "</div>"
			} >> "$episodePagePath"
			#echo "$videoPath" tr -d 'plugin://plugin.video.youtube/play/?video_id='
		elif echo "$videoPath" | grep "http";then
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType id='nfoMediaPlayer' poster='$episodePath-thumb$thumbnailExt' controls>"
				echo "<source src='$videoPath' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<div class='descriptionCard'>"
				echo "<h2>$episodeTitle</h2>"
				# create a hard link
				if [ "$sufix" = ".strm" ];then
					echo "<a class='button hardLink' href='$videoPath'>"
					echo "Hard Link"
					echo "</a><br>"
				else
					echo "<a class='button hardLink' href='$episodePath$sufix'>"
					echo "Hard Link"
					echo "</a>"
				fi
				echo "<div class='aired'>"
				echo "$episodeAired"
				echo "</div>"
				echo "$episodePlot"
				echo "</div>"
			} >> "$episodePagePath"
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType id='nfoMediaPlayer' poster='$episodePath-thumb$thumbnailExt' controls>"
				echo "<source src='$episodePath$sufix' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<div class='descriptionCard'>"
				echo "<h2>$episodeTitle</h2>"
				# create a hard link
				echo "<a class='button hardLink' href='$episodePath$sufix'>"
				echo "Hard Link"
				echo "</a>"
				echo "<div class='aired'>"
				echo "$episodeAired"
				echo "</div>"
				echo "$episodePlot"
				echo "</div>"
			} >> "$episodePagePath"
		fi
		{
			# add footer
			cat "$headerPagePath" | sed "s/href='/href='..\/..\/..\//g"
			echo "</body>"
			echo "</html>"
		} >> "$episodePagePath"
		################################################################################
		# add the episode to the show page
		################################################################################
		if [ $episodeNumber -eq 1 ];then
			{
				echo "<a class='showPageEpisode' href='$episodeSeasonPath/$episodePath.html'>"
				echo "	<img loading='lazy' src='$episodeSeasonPath/$episodePath-thumb$thumbnailExt'>"
				#echo "  <marquee direction='up' scrolldelay='100'>"
				echo "	<h3 class='title'>"
				echo "		<div class='showIndexNumbers'>${episodeSeason}x${episodeNumber}</div>"
				echo "		$episodeTitle"
				echo "	</h3>"
				#echo "  </marquee>"
				echo "</a>"
			} > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		else
			{
				echo "<a class='showPageEpisode' href='$episodeSeasonPath/$episodePath.html'>"
				echo "	<img loading='lazy' src='$episodeSeasonPath/$episodePath-thumb$thumbnailExt'>"
				#echo "  <marquee direction='up' scrolldelay='100'>"
				echo "	<h3 class='title'>"
				echo "		<div class='showIndexNumbers'>${episodeSeason}x${episodeNumber}</div>"
				echo "		$episodeTitle"
				echo "	</h3>"
				#echo "  </marquee>"
				echo "</a>"
			} >> "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		fi
	else
		echo "[WARNING]: The file '$episode' could not be found!"
	fi
}
################################################################################
processShow(){
	#processShow "$show" "$showMeta" "$showTitle" "$webDirectory"
	show=$1
	showMeta=$2
	showTitle=$3
	webDirectory=$4
	logPagePath="$webDirectory/log.html"
	showLogPath="$webDirectory/shows/$showTitle/log.index"
	# create the path sum for reconizing the libary path
	pathSum=$(echo -n "$show" | md5sum | cut -d' ' -f1)
	# create directory
	echo "[INFO]: creating show directory at '$webDirectory/$showTitle/'"
	mkdir -p "$webDirectory/shows/$showTitle/"
	chown -R www-data:www-data "$webDirectory/shows/$showTitle"
	# link stylesheet
	ln -s "$webDirectory/style.css" "$webDirectory/shows/$showTitle/style.css"
	# check show state before processing
	if [ -f "$webDirectory/shows/$showTitle/state_$pathSum.cfg" ];then
		# a existing state was found
		currentSum=$(cat "$webDirectory/shows/$showTitle/state_$pathSum.cfg")
		libarySum=$(getDirSum "$show")
		# if the current state is the same as the state of the last update
		if [ "$libarySum" == "$currentSum" ];then
			# this means they are the same so no update needs run
			echo "[INFO]: State is unchanged for $showTitle, no update is needed."
			echo "[DEBUG]: $currentSum == $libarySum"
			addToLog "INFO" "Show unchanged" "$showTitle" "$logPagePath"
			return
		else
			echo "[INFO]: States are diffrent, updating $showTitle..."
			echo "[DEBUG]: $currentSum != $libarySum"
			# clear the show log for the newly changed show state
			echo "" > "$showLogPath"
			updateInfo="$showTitle\n$currentSum != $libarySum"
			addToLog "UPDATE" "Updating Show" "$updateInfo" "$logPagePath"
			# update the show directory modification date when the state has been changed
			touch "$webDirectory/shows/$showTitle/"
		fi
	else
		echo "[INFO]: No show state exists for $showTitle, updating..."
		addToLog "NEW" "Creating new show" "$showTitle" "$logPagePath"
		# update the show directory modification date when the state has been changed
		touch "$webDirectory/shows/$showTitle/"
	fi
	if grep "<episodedetails>" "$show"/*.nfo;then
		# search all nfo files in the show directory if any are episodes
		addToLog "ERROR" "Episodes outside of season directories" "$showTitle has episode NFO files outside of a season folder" "$logPagePath"
	fi
	# check and remove duplicate thubnails for this show, failed thumbnails on the
	# same show generally fail in the same way
	#fdupes --recurse --delete --immediate "$webDirectory/shows/$showTitle/"
	# create the kodi directory for the show
	mkdir -p "$webDirectory/kodi/shows/$showTitle/"
	chown -R www-data:www-data "$webDirectory/kodi/shows/$showTitle"
	# linking tvshow.nfo data
	ln -s "$show/tvshow.nfo" "$webDirectory/shows/$showTitle/tvshow.nfo"
	ln -s "$show/tvshow.nfo" "$webDirectory/kodi/shows/$showTitle/tvshow.nfo"
	# link all images to the kodi path
	if ls "$show" | grep "\.jpg" ;then
		ln -s "$show"/*.jpg "$webDirectory/kodi/shows/$showTitle/"
		ln -s "$show"/*.jpg "$webDirectory/shows/$showTitle/"
	fi
	if ls "$show" | grep "\.png" ;then
		ln -s "$show"/*.png "$webDirectory/kodi/shows/$showTitle/"
		ln -s "$show"/*.png "$webDirectory/shows/$showTitle/"
	fi
	# link the poster
	if [ -f "$show/poster.png" ];then
		posterPath="poster.png"
		echo "[INFO]: Found $show/$posterPath"
		ln -s "$show/$posterPath" "$webDirectory/shows/$showTitle/$posterPath"
		ln -s "$show/$posterPath" "$webDirectory/kodi/shows/$showTitle/$posterPath"
	elif [ -f "$show/poster.jpg" ];then
		posterPath="poster.jpg"
		echo "[INFO]: Found $show/$posterPath"
		ln -s "$show/$posterPath" "$webDirectory/shows/$showTitle/$posterPath"
		ln -s "$show/$posterPath" "$webDirectory/kodi/shows/$showTitle/$posterPath"
	else
		echo "[WARNING]: could not find $show/poster.[png/jpg]"
	fi
	# link the fanart
	if [ -f "$show/fanart.png" ];then
		echo "[INFO]: Found $show/fanart.png"
		fanartPath="fanart.png"
		echo "[INFO]: Found $show/$fanartPath"
		ln -s "$show/$fanartPath" "$webDirectory/shows/$showTitle/$fanartPath"
		ln -s "$show/$fanartPath" "$webDirectory/kodi/shows/$showTitle/$fanartPath"
	elif [ -f "$show/fanart.jpg" ];then
		fanartPath="fanart.jpg"
		echo "[INFO]: Found $show/$fanartPath"
		ln -s "$show/$fanartPath" "$webDirectory/shows/$showTitle/$fanartPath"
		ln -s "$show/$fanartPath" "$webDirectory/kodi/shows/$showTitle/$fanartPath"
	else
		echo "[WARNING]: could not find $show/fanart.[png/jpg]"
	fi
	# building the webpage for the show
	showPagePath="$webDirectory/shows/$showTitle/index.html"
	echo "[INFO]: Creating directory at = '$webDirectory/shows/$showTitle/'"
	mkdir -p "$webDirectory/shows/$showTitle/"
	echo "[INFO]: Creating showPagePath = $showPagePath"
	touch "$showPagePath"
	################################################################################
	# begin building the html of the page
	################################################################################
	# generate the episodes based on .nfo files
	for season in "$show"/*;do
		echo "[INFO]: checking for season folder at '$season'"
		if [ -d "$season" ];then
			echo "[INFO]: found season folder at '$season'"
			# generate the season name from the path
			seasonName=$(echo "$season" | rev | cut -d'/' -f1 | rev)
			# if the folder is a directory that means a season has been found
			# read each episode in the series
			for episode in "$season"/*.nfo;do
				processEpisode "$episode" "$showTitle" "$showPagePath" "$webDirectory"
			done
			################################################################################
			headerPagePath="$webDirectory/header.html"

			# find the fanart for the episode background
			if [ -f "$webDirectory/shows/$showTitle/fanart.png" ];then
				tempStyle="--backgroundFanart: url(\"fanart.png\");"
			elif [ -f "$webDirectory/shows/$showTitle/fanart.jpg" ];then
				tempStyle="--backgroundFanart: url(\"fanart.jpg\");"
			fi
			if [ -f "$webDirectory/shows/$showTitle/poster.png" ];then
				tempStyle="$tempStyle --backgroundPoster: url(\"poster.png\")"
			elif [ -f "$webDirectory/shows/$showTitle/poster.jpg" ];then
				tempStyle="$tempStyle --backgroundPoster: url(\"poster.jpg\")"
			fi
			#tempStyle="html{ background-image: url(\"$fanartPath\") }"
			# build top of show webpage containing all of the shows meta info
			{
				#echo "<html id='top' style='$tempStyle'>"
				echo "<html id='top' class='seriesBackground' style='$tempStyle'>"
				echo "<head>"
				echo "<link rel='stylesheet' href='style.css' />"
				#echo "<style>"
				#echo "$tempStyle"
				#cat /usr/share/nfo2web/style.css
				#echo "</style>"
				echo "<script>"
				cat /usr/share/nfo2web/nfo2web.js
				echo "</script>"
				echo "</head>"
				echo "<body>"
				cat "$headerPagePath" | sed "s/href='/href='..\/..\//g"
				echo "<div class='titleCard'>"
				echo "<h1>$showTitle</h1>"
				echo "</div>"
				# add the search box
				echo " <input id='searchBox' class='searchBox' type='text'"
				echo " onkeyup='filter(\"showPageEpisode\")' placeholder='Search...' >"
				# add the most recently updated series
				echo "<div class='episodeList'>"
			} > "$showPagePath"
			################################################################################
			# after processing each season rebuild the show page index entirely
			for generatedSeason in "$webDirectory/shows/$showTitle"/*;do
				if [ -f "$generatedSeason/season.index" ];then
					tempSeasonName=$(echo "$generatedSeason" | rev | cut -d'/' -f1 | rev)
					{
						echo "<div class='seasonContainer'>"
						echo "<div class='seasonHeader'>"
						echo "	<h2>"
						echo "		$tempSeasonName"
						echo "	</h2>"
						echo "</div>"
						echo "<hr>"
						# read all season indexes
						cat "$generatedSeason/season.index"
						echo "</div>"
					} >> "$showPagePath"
				fi
			done
			{
				echo "</div>"
				# add footer
				cat "$headerPagePath" | sed "s/href='/href='..\/..\//g"
				# create top jump button
				echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
				echo "<hr class='topButtonSpace'>"
				echo "</body>"
				echo "</html>"
			} >> "$showPagePath"
		else
			echo "Season folder $season does not exist"
		fi
	done
	# create show index information
	#showIndexPath="$webDirectory/shows/index.html"
	# add show page to the show index
	{
		echo "<a class='indexSeries' href='$showTitle/'>"
		echo "	<img loading='lazy' src='$showTitle/$posterPath'>"
		#echo "  <marquee direction='up' scrolldelay='100'>"
		echo "	<div>"
		echo "		$showTitle"
		echo "	</div>"
		#echo "  </marquee>"
		echo "</a>"
	#} >> "$showIndexPath"
	} > "$webDirectory/shows/$showTitle/shows.index"
	# update the libary sum
	touch "$webDirectory/shows/$showTitle/state_$pathSum.cfg"
	getDirSum "$show" > "$webDirectory/shows/$showTitle/state_$pathSum.cfg"
}
########################################################################
addToLog(){
	errorType=$1
	errorDescription=$2
	errorDetails=$3
	logPagePath=$4
	{
		# add error to log
		echo -e "<tr class='$errorType'>"
		echo -e "<td>"
		echo -e "$errorType"
		echo -e "</td>"
		echo -e "<td>"
		echo -e "$errorDescription"
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
########################################################################
getLibSum(){
	# find all state md5sums for shows and create a collective sum
	totalList=""
	while read -r line;do
		# read each line and load the file
		#tempList=$(ls -lR $line)
		tempList=$(ls -R "$line")
		#tempList=$(cat "$line"/*/*.cfg)
		# add value to list
		totalList="$totalList$tempList"
	done < /etc/nfo2web/libaries.cfg
	# convert lists into md5sum
	tempLibList="$(echo "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
################################################################################
buildUpdatedShows(){
	# buildUpdatedShows $webDirectory $numberOfShows
	################################################################################
	# Build a list of updated shows
	################################################################################
	webDirectory=$1
	numberOfShows=$2
	################################################################################
	updatedShows=$(ls -1t "$webDirectory"/shows/*/shows.index | tac | tail -n "$numberOfShows" | tac)
	if [ $(echo -n "$updatedShows" | wc -l) -gt 0 ];then
		echo "<div class='titleCard'>"
		echo "<h1>Updated Shows</h1>"
		echo "<div class='listCard'>"
		echo "$updatedShows" | while read -r line;do
			# fix index links to work for homepage
			cat "$line"
		done
		echo "</div>"
		echo "</div>"
	fi
}
################################################################################
buildRandomShows(){
	# buildRandomShows $webDirectory $numberOfShows $sourcePrefix
	################################################################################
	# Build a list of updated shows
	################################################################################
	webDirectory=$1
	numberOfShows=$2
	sourcePrefix=$3
	################################################################################
	randomShows=$(ls -1 "$webDirectory"/shows/*/shows.index| shuf -n "$numberOfShows")
	if [ $(echo -n "$randomShows" | wc -l) -gt 0 ];then
		echo "<div class='titleCard'>"
		echo "<h1>Random Shows</h1>"
		echo "<div class='listCard'>"
		echo "$randomShows" | while read -r line;do
			# fix index links to work for homepage
			cat "$line"
		done
		echo "</div>"
		echo "</div>"
	fi
}
################################################################################
buildRandomChannels(){
	################################################################################
	# Build a list of randomly generated channels
	################################################################################
	webDirectory=$1
	numberOfShows=$2
	sourcePrefix=$3
	################################################################################
	randomChannels=$(ls -1 "$webDirectory"/live/channel_*.index | shuf -n "$numberOfShows")
	if [ $(echo -n "$randomChannels" | wc -l) -gt 0 ];then
		echo "<div class='titleCard'>"
		echo "<h1>Random Channels</h1>"
		echo "<div class='listCard'>"
		echo "$randomChannels" | while read -r line;do
			# fix index links to work for homepage
			cat "$line"
		done
		echo "</div>"
		echo "</div>"
	fi
}
########################################################################
buildUpdatedMovies(){
	# buildUpdatedMovies $webDirectory $numberOfMovies $sourcePrefix
	################################################################################
	# Build a list of updated movies
	################################################################################
	webDirectory=$1
	numberOfMovies=$2
	sourcePrefix=$3
	################################################################################
	updatedMovies=$(ls -1t "$webDirectory"/movies/*/movies.index | tac | tail -n "$numberOfMovies" | tac )
	if [ $(echo -n "$updatedMovies" | wc -l) -gt 0 ];then
		echo "<div class='titleCard'>"
		echo "<h1>Updated Movies</h1>"
		echo "<div class='listCard'>"
		echo "$updatedMovies" | while read -r line;do
			# fix index links to work for homepage
			cat "$line"
		done
		echo "</div>"
		echo "</div>"
	fi
}
########################################################################
buildRandomMovies(){
	# buildRandomMovies $webDirectory $numberOfMovies $sourcePrefix
	################################################################################
	# Build a list of updated movies
	################################################################################
	webDirectory=$1
	numberOfMovies=$2
	sourcePrefix=$3
	################################################################################
	randomMovies=$(ls -1 "$webDirectory"/movies/*/movies.index| shuf -n "$numberOfMovies")
	if [ $(echo -n "$randomMovies" | wc -l) -gt 0 ];then
		echo "<div class='titleCard'>"
		echo "<h1>Random Movies</h1>"
		echo "<div class='listCard'>"
		echo "$randomMovies" | while read -r line;do
			# fix index links to work for homepage
			cat "$line"
		done
		echo "</div>"
		echo "</div>"
	fi
}
################################################################################
buildRandomComics(){
	# buildRandomMovies $webDirectory $numberOfMovies $sourcePrefix
	################################################################################
	# Build a list of updated movies
	################################################################################
	webDirectory=$1
	numberOfMovies=$2
	sourcePrefix=$3
	################################################################################
	randomMovies=$(ls -1 "$webDirectory"/comics/*/index.index | shuf -n "$numberOfMovies")
	if [ $(echo -n "$randomMovies" | wc -l) -gt 0 ];then
		echo "<div class='titleCard'>"
		echo "<h1>Random Comics</h1>"
		echo "<div class='listCard'>"
		echo "$randomMovies" | while read -r line;do
			# fix index links to work for homepage
			cat "$line"
		done
		echo "</div>"
		echo "</div>"
	fi
}
########################################################################
buildHomePage(){

	webDirectory=$1
	headerPagePath=$2

	echo "[INFO]: Building home page..."
	# do not generate stats if website is in process of being updated
	# stats generation is IO intense, so it only needs ran ONCE at the end
	# if the stats.index cache is more than 1 day old update it
	if cacheCheck "$webDirectory/stats.index" "10";then
		# figure out the stats
		totalEpisodes=$(find "$webDirectory"/shows/*/*/ -name "*.nfo" | wc -l)
		totalShows=$(find "$webDirectory"/shows/*/ -name "tvshow.nfo" | wc -l)
		totalMovies=$(find "$webDirectory"/movies/*/ -name "*.nfo" | wc -l)
		if [ -f "$webDirectory/kodi/channels.m3u" ];then
			totalChannels=$(grep -c 'radio="false' "$webDirectory/kodi/channels.m3u" )
			totalRadio=$(grep -c 'radio="true' "$webDirectory/kodi/channels.m3u" )
		fi
		webSize=$(du -sh "$webDirectory" | cut -f1)
		mediaSize=$(du -shL "$webDirectory/kodi/" | cut -f1)
		freeSpace=$(df -h -x "tmpfs" --total | grep "total" | tr -s ' ' | cut -d' ' -f4)
		#write a new stats index file
		{
			if [ "$totalShows" -gt 0 ];then
				echo "<span>"
				echo "	Episodes:$totalEpisodes"
				echo "</span>"
				echo "<span>"
				echo "	Shows:$totalShows"
				echo "</span>"
			fi
			if [ "$totalMovies" -gt 0 ];then
				echo "<span>"
				echo "	Movies:$totalMovies"
				echo "</span>"
			fi
			if [ -f "$webDirectory/kodi/channels.m3u" ];then
				echo "<span>"
				echo "	Channels:$totalChannels"
				echo "</span>"
				echo "<span>"
				echo "	Radio:$totalRadio"
				echo "</span>"
			fi
			echo "<span>"
			echo "	Web:$webSize "
			echo "</span>"
			echo "<span>"
			echo "	Media:$mediaSize"
			echo "</span>"
			echo "<span>"
			echo "	Free:$freeSpace"
			echo "</span>"
		} > "$webDirectory/stats.index"
	fi
	# build homepage code
	tempStyle="html{ background-image: url(\"background.png\") }"
	{
			echo "<html id='top' class='randomFanart'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			echo "<style>"
			#echo "$tempStyle"
			#cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			if echo "$@" | grep -Eq "\-\-in\-progress";then
				updateInProgress
			fi
			cat "$headerPagePath"
			echo "<div class='date titleCard'>"
			echo "	<div>"
			echo "		Last updated on $(date)"
			echo "	</div>"
			echo "	<div>"

			# load from cache previously generated stats to display
			cat "$webDirectory/stats.index"
			echo "	</div>"
			echo "</div>"
	} > "$webDirectory/index.html"
	################################################################################
	# if time is older than one day for .index files
	if cacheCheck "$webDirectory/updatedShows.index" "1";then
		buildUpdatedShows "$webDirectory" 50 > "$webDirectory/updatedShows.index"
	fi
	if cacheCheck "$webDirectory/updatedMovies.index" "1";then
		buildUpdatedMovies "$webDirectory" 50 > "$webDirectory/updatedMovies.index"
	fi
	if cacheCheck "$webDirectory/randomShows.index" "10";then
		buildRandomShows "$webDirectory" 50 > "$webDirectory/randomShows.index"
	fi
	if cacheCheck "$webDirectory/randomMovies.index" "10";then
		buildRandomMovies "$webDirectory" 50 > "$webDirectory/randomMovies.index"
	fi
	if cacheCheck "$webDirectory/randomChannels.index" "10";then
		buildRandomChannels "$webDirectory" 50 > "$webDirectory/randomChannels.index"
	fi
	if cacheCheck "$webDirectory/randomComics.index" "10";then
		buildRandomComics "$webDirectory" 50 > "$webDirectory/randomComics.index"
	fi

	{
		sourcePrefix="shows\/"
		cat "$webDirectory/updatedShows.index" | \
			sed "s/src='/src='$sourcePrefix/g" | \
			sed "s/href='/href='$sourcePrefix/g"
		sourcePrefix="movies\/"
		cat "$webDirectory/updatedMovies.index" | \
			sed "s/src='/src='$sourcePrefix/g" | \
			sed "s/href='/href='$sourcePrefix/g"
		sourcePrefix="shows\/"
		cat "$webDirectory/randomShows.index" | \
			sed "s/src='/src='$sourcePrefix/g" | \
			sed "s/href='/href='$sourcePrefix/g"
		sourcePrefix="movies\/"
		cat "$webDirectory/randomMovies.index" | \
			sed "s/src='/src='$sourcePrefix/g" | \
			sed "s/href='/href='$sourcePrefix/g"
		sourcePrefix="comics\/"
		cat "$webDirectory/randomComics.index" | \
			sed "s/src='/src='$sourcePrefix/g" | \
			sed "s/href='/href='$sourcePrefix/g"
		sourcePrefix="live\/"
		cat "$webDirectory/randomChannels.index" | \
			sed "s/src='/src='$sourcePrefix/g" | \
			sed "s/href='/href='$sourcePrefix/g"
	} >> "$webDirectory/index.html"
	################################################################################
	{
		# add footer
		cat "$headerPagePath"
		# create top jump button
		echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
		echo "<hr class='topButtonSpace'>"
		echo "</body>"
		echo "</html>"
	} >>  "$webDirectory/index.html"
}
########################################################################
getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
########################################################################
function buildShowIndex(){
	webDirectory="$1"
	headerPagePath="$2"
	showIndexPath="$webDirectory/shows/index.html"
	# update the show index
	{
		echo "<html id='top' class='randomFanart'>"
		echo "<head>"
		echo "<link rel='stylesheet' href='style.css' />"
		#echo "<style>"
		#cat /usr/share/nfo2web/style.css
		#echo "</style>"
		echo "<script>"
		cat /usr/share/nfo2web/nfo2web.js
		echo "</script>"
		echo "</head>"
		echo "<body>"
		#updateInProgress
		cat "$headerPagePath" | sed "s/href='/href='..\//g"
		# add the search box
		echo " <input id='searchBox' class='searchBox' type='text'"
		echo " onkeyup='filter(\"indexSeries\")' placeholder='Search...' >"

		# add the most recently updated series
		#cat "$webDirectory/updatedShows.index"
		sourcePrefix="shows\/"
		cat "$webDirectory/updatedShows.index"
			#sed "s/src='/src='$sourcePrefix/g" | \
			#sed "s/href='/href='$sourcePrefix/g"

		#buildUpdatedShows "$webDirectory" 25 ""

		# load all existing shows into the index
		cat "$webDirectory"/shows/*/shows.index

		# add the random list to the footer
		#buildRandomShows "$webDirectory" 25 ""
		#cat "$webDirectory/randomShows.index"
		cat "$webDirectory/randomShows.index"
			#sed "s/src='/src='$sourcePrefix/g" | \
			#sed "s/href='/href='$sourcePrefix/g"

		# add footer
		cat "$headerPagePath" | sed "s/href='/href='..\//g"
		# create top jump button
		echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
		echo "<hr class='topButtonSpace'>"
		echo "</body>"
		echo "</html>"
	} > "$showIndexPath"
}
########################################################################
getDirSumByTime(){
	line=$1
	# get the sum of the directory modification time
	totalList=$(stat --format="%Y" "$line")
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
########################################################################
updateInProgress(){
	echo -e "<div class='progressIndicator'>"
	echo -e "\t<span class='progressText'>Update In Progress...</span>"
	echo -e "\t<script>"
	echo -e "\t\t// reload the webpage every 1 minute, time is in milliseconds"
	echo -e "\t\tsetTimeout(function() { window.location=window.location;},((1000*60)*1));"
	echo -e "\t</script>"
	echo -e "</div>"
}
#########################################################################
scanForRandomBackgrounds(){
	webDirectory="$1"
	backgroundUpdateDelay="7"
	#########################################################################
	# create fanart list
	#########################################################################
	# if the fanart list is older than $backgroundUpdateDelay in days
	if cacheCheck "$webDirectory/fanart.cfg" "$backgroundUpdateDelay";then
		# move into the web directory so paths from below searches are relative
		cd $webDirectory
		find -L "shows/" -type f -name "fanart.png" > "$webDirectory/fanart.cfg"
		find -L "shows/" -type f -name "fanart.jpg" >> "$webDirectory/fanart.cfg"
		find -L "movies/" -type f -name "fanart.png" >> "$webDirectory/fanart.cfg"
		find -L "movies/" -type f -name "fanart.jpg" >> "$webDirectory/fanart.cfg"
	fi
	if cacheCheck "$webDirectory/shows/fanart.cfg" "$backgroundUpdateDelay";then
		# create shows only fanart.cfg
		cd "$webDirectory/shows/"
		find -L "." -type f -name "fanart.png" > "$webDirectory/shows/fanart.cfg"
		find -L "." -type f -name "fanart.jpg" >> "$webDirectory/shows/fanart.cfg"
	fi
	if cacheCheck "$webDirectory/movies/fanart.cfg" "$backgroundUpdateDelay";then
		# create movies only fanart.cfg
		cd "$webDirectory/movies/"
		find -L "." -type f -name "fanart.png" > "$webDirectory/movies/fanart.cfg"
		find -L "." -type f -name "fanart.jpg" >> "$webDirectory/movies/fanart.cfg"
	fi
	if cacheCheck "$webDirectory/poster.cfg" "$backgroundUpdateDelay";then
		# move into the web directory so paths from below searches are relative
		cd $webDirectory
		find -L "shows/" -type f -name "poster.png" > "$webDirectory/poster.cfg"
		find -L "shows/" -type f -name "poster.jpg" >> "$webDirectory/poster.cfg"
		find -L "movies/" -type f -name "poster.png" >> "$webDirectory/poster.cfg"
		find -L "movies/" -type f -name "poster.jpg" >> "$webDirectory/poster.cfg"
	fi
	if cacheCheck "$webDirectory/shows/poster.cfg" "$backgroundUpdateDelay";then
		# create shows only poster.cfg
		cd "$webDirectory/shows/"
		find -L "." -type f -name "poster.png" > "$webDirectory/shows/poster.cfg"
		find -L "." -type f -name "poster.jpg" >> "$webDirectory/shows/poster.cfg"
	fi
	if cacheCheck "$webDirectory/movies/poster.cfg" "$backgroundUpdateDelay";then
		# create movies only poster.cfg
		cd "$webDirectory/movies/"
		find -L "." -type f -name "poster.png" > "$webDirectory/movies/poster.cfg"
		find -L "." -type f -name "poster.jpg" >> "$webDirectory/movies/poster.cfg"
	fi
}
########################################################################
function buildMovieIndex(){

	webDirectory=$1
	headerPagePath=$2

	# update the movie index webpage
	{
		echo "<html id='top' class='randomFanart'>"
		echo "<head>"
		echo "<link rel='stylesheet' href='style.css' />"
		#echo "<style>"
		#cat /usr/share/nfo2web/style.css
		#echo "</style>"
		echo "<script>"
		cat /usr/share/nfo2web/nfo2web.js
		echo "</script>"
		echo "</head>"
		echo "<body>"
		#updateInProgress
		cat "$headerPagePath" | sed "s/href='/href='..\//g"
		# add the search box
		echo " <input id='searchBox' class='searchBox' type='text'"
		echo " onkeyup='filter(\"indexSeries\")' placeholder='Search...' >"

		#buildUpdatedMovies "$webDirectory" 25 ""
		#cat "$webDirectory/updatedMovies.index"
		sourcePrefix="movies\/"
		cat "$webDirectory/updatedMovies.index"
			#sed "s/src='/src='$sourcePrefix/g" | \
			#sed "s/href='/href='$sourcePrefix/g"

		# load the movie index parts
		cat "$webDirectory"/movies/*/movies.index

		# add the random list to the footer
		#buildRandomMovies "$webDirectory" 25 ""
		#cat "$webDirectory/randomMovies.index"
		cat "$webDirectory/randomMovies.index"
			#sed "s/src='/src='$sourcePrefix/g" | \
			#sed "s/href='/href='$sourcePrefix/g"

		# add footer
		cat "$headerPagePath" | sed "s/href='/href='..\//g"
		# create top jump button
		echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
		echo "<hr class='topButtonSpace'>"
		echo "</body>"
		echo "</html>"
	} > "$movieIndexPath"
}
########################################################################
function cacheCheck(){

	filePath="$1"
	cacheDays="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -mtime "+$cacheDays") ]];then
			# the file is more than "$2" days old, it needs updated
			echo "[INFO]: File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			echo "[INFO]: File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		echo "[INFO]: File does not exist, it must be created $1"
		return 0
	fi
}
########################################################################
main(){
	debugCheck
	if [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		echo "########################################################################"
		echo "# nfo2web CLI for administration"
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
		echo "This is the nfo2web administration and update program."
		echo "To return to this menu use 'nfo2web help'"
		echo "Other commands are listed below."
		echo ""
		echo "update"
		echo "  This will update the webpages and refresh the database."
		echo "reset"
		echo "  This will reset the state of the cache so everything will be updated."
		echo "nuke"
		echo "  This will delete the cached website."
		echo "########################################################################"
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		echo "[INFO]: Reseting web cache states..."
		rm -rv /var/cache/nfo2web/web/*/*/state_*.cfg
		echo "[INFO]: Reseting web log for individual shows/movies..."
		rm -rv /var/cache/nfo2web/web/*/*/log.index
		echo "[SUCCESS]: Web cache states reset, update to rebuild everything."
		echo "[SUCCESS]: Site will remain the same until updated."
		echo "[INFO]: Use 'nfo2web update' to generate a new website..."
	elif [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		echo "[INFO]: Reseting web cache to blank..."
		rm -rv /var/cache/nfo2web/web/*
		echo "[SUCCESS]: Web cache states reset, update to rebuild everything."
		echo "[SUCCESS]: Site will remain the same until updated."
		echo "[INFO]: Use 'nfo2web update' to generate a new website..."
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		################################################################################
		# Create website containing info and links to one or more .nfo directories
		# containing shows
		################################################################################
		# GENERATED SITE EXAMPLE
		################################################################################
		# - index.html : contains links to health.html,recent.html,and each showTitle.html
		#   missing data. Links to each show can be found here.
		#  - health.html : Page contains a list of found issues with nfo libary
		#   + Use duplicate checker script to generate this page
		#  - recent.html : Contains links to all episodes added in the past 14 days
		#   + Use 'find /pathToLibary/ -type f -mtime -14' to find files less than 14
		#     days old
		#  - showTitle/index.html : Each show gets its own show page that contains links to
		#    all available episodes. Sorted Into seasons.
		#   + seasons are on the show page, not seprate
		#_NOTES_________________________________________________________________________
		# - Should allow multuple directories to be set as libaries
		# - Should create symlinks to pictures and video files
		# - Downloaded video files should be symlinked into web directory
		# - Downloaded files should be linked with html5 video player
		# - Should convert .strm file links to html5 video player pages
		# - Include download button on episode webpage
		# - Should convert youtube links from kodi to embeded player links
		# - Should create its own web directory
		################################################################################
		# load the libary directory
		if [ -f /etc/nfo2web/libaries.cfg ];then
			libaries=$(cat /etc/nfo2web/libaries.cfg)
			libaries=$(echo -e "$libaries\n$(cat /etc/nfo2web/libaries.d/*.cfg)")
		else
			mkdir -p /var/cache/nfo2web/libary/
			echo "/var/cache/nfo2web/libary" > /etc/nfo2web/libaries.cfg
			libaries="/var/cache/nfo2web/libary"
			libaries=$(echo -e "$libaries\n$(cat /etc/nfo2web/libaries.d/*.cfg)")
		fi
		# the webdirectory is a cache where the generated website is stored
		if [ -f /etc/nfo2web/web.cfg ];then
			webDirectory=$(cat /etc/nfo2web/web.cfg)
		else
			mkdir -p /var/cache/nfo2web/web/
			chown -R www-data:www-data "/var/cache/nfo2web/web/"
			echo "/var/cache/nfo2web/web" > /etc/nfo2web/web.cfg
			webDirectory="/var/cache/nfo2web/web"
		fi
		# check if system is active
		if [ -f "/tmp/nfo2web.active" ];then
			# system is already running exit
			echo "[INFO]: nfo2web is already processing data in another process."
			echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/nfo2web.active'."
			exit
		else
			# set the active flag
			touch /tmp/nfo2web.active
			# create a trap to remove nfo2web lockfile
			trap "rm -v /tmp/nfo2web.active" EXIT
		fi
		# make sure the directories exist and have correct permissions, also link stylesheets
		mkdir -p "$webDirectory"
		chown -R www-data:www-data "$webDirectory"
		mkdir -p "$webDirectory/shows/"
		chown -R www-data:www-data "$webDirectory/shows/"
		mkdir -p "$webDirectory/movies/"
		chown -R www-data:www-data "$webDirectory/movies/"
		mkdir -p "$webDirectory/kodi/"
		chown -R www-data:www-data "$webDirectory/kodi/"
		# link the settings scripts
		ln -s "/usr/share/mms/settings/admin.php" "$webDirectory/admin.php"
		ln -s "/usr/share/mms/settings/radio.php" "$webDirectory/radio.php"
		ln -s "/usr/share/mms/settings/tv.php" "$webDirectory/tv.php"
		ln -s "/usr/share/mms/settings/nfo.php" "$webDirectory/nfo.php"
		ln -s "/usr/share/mms/settings/comics.php" "$webDirectory/comics.php"
		ln -s "/usr/share/mms/settings/cache.php" "$webDirectory/cache.php"
		ln -s "/usr/share/mms/settings/system.php" "$webDirectory/system.php"
		ln -s "/usr/share/mms/link.php" "$webDirectory/link.php"
		ln -s "/usr/share/mms/ytdl-resolver.php" "$webDirectory/ytdl-resolver.php"
		################################################################################
		if ! [ -d "$webDirectory/RESOLVER-CACHE/" ];then
			# build the cache directory if none exists
			mkdir -p "$webDirectory/RESOLVER-CACHE/"
			# set permissions
			chown www-data:www-data "$webDirectory/RESOLVER-CACHE/"
		fi
		# build bumps from youtube videos
		# - this should load a /etc/mms/bumps.cfg file
		if ! [ -f "/etc/mms/bumps.cfg" ];then
			{
				echo "# this is a comment"
				echo "# - To add more bumps to randomly choose from"
				echo "#   add links to videos in this file"
				echo "# gold particles"
				echo "https://www.youtube.com/watch?v=aNVviTECNM0"
				echo "# spiral of color bubbles"
				echo "https://www.youtube.com/watch?v=vyMUhgMeJ8A"
				echo "# spiral of color ridges"
				echo "https://www.youtube.com/watch?v=97jRHEj0HZw"
				echo "# blue lavalamp river "
				echo "https://www.youtube.com/watch?v=XR-e5I0QkcY"
				echo "# fall down the hypno hole"
				echo "https://www.youtube.com/watch?v=oTXoUgjpHFs"
				echo "# dark ambient swirl"
				echo "https://www.youtube.com/watch?v=lwbjQUY_xd0"
			} >> "/etc/mms/bumps.cfg"
		fi
		bumpConfig=$(grep -v "^#" /etc/mms/bumps.cfg)
		# - each non # entry in the file should be a web link to a loop video that
		#   can have the first 30 seconds cut off for the bump
		# - the resolver will pick random bumps from the $webdirectory/bumps/ directory
		# - users can add bump and skip files directly by naming them anything
		#   with -bump.mp4 as the last part of the filename "*-bump.mp4"
		# - bump and skip files generated from remote web links will be listed
		#   by the md5sum of the web link e.g. ":LIU435435LDKJD4389DLKJDFJ-bump.mp4"
		if ! [ -d "$webDirectory/bumps/" ];then
			mkdir -p "$webDirectory/bumps/"
		fi
		echo "$bumpConfig" | while read bumpLink;do
			# create sum for link
			bumpLinkSum=$(echo "$bumpLink" | md5sum | cut -d' ' -f1)
			# read each link in the link config and check if it has been downloaded
			if ! [ -f "$webDirectory/bumps/$bumpLinkSum-bump.mp4" ];then
				# download the file as the base for the bump
				/usr/local/bin/youtube-dl "$bumpLink" --format "worst" --recode-video mp4 -o "$webDirectory/bumps/$bumpLinkSum-BASE.mp4"
				# the bump has not been created from the base, cut the video with ffmpeg
				ffmpeg -i "$webDirectory/bumps/$bumpLinkSum-BASE.mp4" -to 30 -codec copy "$webDirectory/bumps/$bumpLinkSum-bump.mp4"
				# set permissions in case user set file has been used
				chown www-data:www-data "$webDirectory/bumps/$bumpLinkSum-bump.mp4"
				ffmpeg -i "$webDirectory/bumps/$bumpLinkSum-BASE.mp4" -to 1 -codec copy "$webDirectory/bumps/$bumpLinkSum-skip.mp4"
				chown www-data:www-data "$webDirectory/bumps/$bumpLinkSum-skip.mp4"
				# remove the base bump since it is no longer nessassary
				rm -v "$webDirectory/bumps/$bumpLinkSum-BASE.mp4"
			fi
		done
		# generate the bump for the resolver cache if a file can not be downloaded
		#if ! [ -f "$webDirectory/RESOLVER-CACHE/BASEBUMP-bump.mp4" ];then
			# build the base bump image if it does not exist yet, this is the longest part of the process, so cache it
			#convert -size 800x600 plasma:cyan-white "$webDirectory/RESOLVER-CACHE/baseBump.png"
			# build frames of animation
			#convert "$webDirectory/RESOLVER-CACHE/baseBump.png" -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500 -gravity center caption:'Loading\n[=   ]' -composite "$webDirectory/RESOLVER-CACHE/BASEBUMP_01.png"
			#convert "$webDirectory/RESOLVER-CACHE/baseBump.png" -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500 -gravity center caption:'Loading\n[ =  ]' -composite "$webDirectory/RESOLVER-CACHE/BASEBUMP_02.png"
			#convert "$webDirectory/RESOLVER-CACHE/baseBump.png" -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500 -gravity center caption:'Loading\n[  = ]' -composite "$webDirectory/RESOLVER-CACHE/BASEBUMP_03.png"
			#convert "$webDirectory/RESOLVER-CACHE/baseBump.png" -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500 -gravity center caption:'Loading\n[   =]' -composite "$webDirectory/RESOLVER-CACHE/BASEBUMP_04.png"
			# use links for last frames of the loop
			#ln -s  "BASEBUMP_03.png" "$webDirectory/RESOLVER-CACHE/BASEBUMP_05.png"
			#ln -s  "BASEBUMP_02.png" "$webDirectory/RESOLVER-CACHE/BASEBUMP_06.png"
		#	#-composite -draw 'circle 100,100 200,800'\
		#	# frame 1
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF1F"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_01.png"
		#	# frame 2
		#	#-gravity center caption:"▀"\
		#	#-composite -draw 'circle 100,100 400,800'\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF2E"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_02.png"
		#	# frame 3
		#	#-gravity center caption:"▝"\
		#	#-composite -draw 'circle 100,100 600,800'\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF3D"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_03.png"
		#	# frame 4
		#	#-gravity center caption:"▐"\
		#	#-composite -draw 'circle 100,100 800,800'\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF4C"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_04.png"
		#	# frame 5
		#	#-gravity center caption:"▗"\
		#	#-composite -draw 'circle 100,100 1000,800'\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF4C"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_05.png"
		#	# frame 6
		#	#-gravity center caption:"▃"\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF3D"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_06.png"
		#	# frame 7
		#	#-gravity center caption:"▖"\
		#	#-composite -draw 'circle 100,100 1400,800'\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF2E"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_07.png"
		#	# frame 8
		#	#-gravity center caption:"▌"\
		#	#-composite -draw 'circle 100,100 1600,800'\
		#	convert -size 1920x1080 plasma:white-cyan\
		#		-size 1920x1080 radial-gradient:"#00FF1F"-"rgba(0,0,0,0)" -composite\
		#		-background none -font 'DejaVu-Sans-Mono' -fill white\
		#		-stroke black -strokewidth 2 -style Bold -size 1820x980\
		#		-gravity center caption:"Loading..." -composite\
		#		"$webDirectory/RESOLVER-CACHE/BASEBUMP_07.png"
		#	# combine animation together
		#	#ffmpeg -y -loop 1 -f image2 -i "$webDirectory/RESOLVER-CACHE/BASEBUMP_%02d.png" -r 28 -t 30 -vcodec theora -b:v 128k "$webDirectory/RESOLVER-CACHE/BASEBUMP-bump.ogv"
		#	ffmpeg -y -loop 1 -f image2 -i "$webDirectory/RESOLVER-CACHE/BASEBUMP_%02d.png" -r 28 -t 30 -c:v libx264 -preset slow -profile:v high -crf 18 -coder 1 -pix_fmt yuv420p -movflags +faststart -g 30 -bf 2 -c:a aac -b:a 384k -profile:a aac_low "$webDirectory/RESOLVER-CACHE/BASEBUMP-bump.mp4"
		#	#ffmpeg -i input -c:v libx264 -preset slow -profile:v high -crf 18 -coder 1 -pix_fmt yuv420p -movflags +faststart -g 30 -bf 2 -c:a aac -b:a 384k -profile:a aac_low output
		#	chown -R www-data:www-data "$webDirectory/RESOLVER-CACHE/"
		#else
		#	# update the modified time so the bump video generated will not be cleaned with the cache
		#	touch "$webDirectory/RESOLVER-CACHE/BASEBUMP-bump.png"
		#	# set permissions in case user set file has been used
		#	chown www-data:www-data "$webDirectory/RESOLVER-CACHE/BASEBUMP-bump.mp4"
		#fi
		#if ! [ -f "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip.mp4" ];then
		#	#convert -size 800x600 plasma:green-lightgreen "$webDirectory/RESOLVER-CACHE/baseSkip.png"
		#	# frame 1
		#	#-gravity center caption:"▘"\
		#	convert -size 800x600 radial-gradient:"#00FF1F"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_01.png"
		#	# frame 2
		#	#-gravity center caption:"▀"\
		#	convert -size 800x600 radial-gradient:"#00FF2E"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_02.png"
		#	# frame 3
		#	#-gravity center caption:"▝"\
		#	convert -size 800x600 radial-gradient:"#00FF3D"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_03.png"
		#	# frame 4
		#	#-gravity center caption:"▐"\
		#	convert -size 800x600 radial-gradient:"#00FF4C"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_04.png"
		#	# frame 5
		#	#-gravity center caption:"▗"\
		#	convert -size 800x600 radial-gradient:"#00FF4C"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_05.png"
		#	# frame 6
		#	#-gravity center caption:"▃"\
		#	convert -size 800x600 radial-gradient:"#00FF3D"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_06.png"
		#	# frame 7
		#	#-gravity center caption:"▖"\
		#	convert -size 800x600 radial-gradient:"#00FF2E"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_07.png"
		#	# frame 8
		#	#-gravity center caption:"▌"\
		#	convert -size 800x600 radial-gradient:"#00FF1F"-"#000000" -background none\
		#		-font 'DejaVu-Sans-Mono' -fill white -stroke black -strokewidth 8 -style Bold -size 700x500\
		#		-gravity center caption:""\
		#		-composite "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_08.png"
		#	# combine
		#	ffmpeg -y -loop 1 -i "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip_%02d.png" -r 8 -t 1 "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip.mp4"
		#	# set permissions on all generated files
		#	chown -R www-data:www-data "$webDirectory/RESOLVER-CACHE/"
		#else
		#	# update the modified time so the bump video generated will not be cleaned with the cache
		#	touch "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip.png"
		#	# set permissions in case user set file has been used
		#	chown www-data:www-data "$webDirectory/RESOLVER-CACHE/BASEBUMP-skip.mp4"
		#fi


		# check the scheduler and make sure www-data is allowed to use the at command for php resolver
		if [ -f "/etc/at.deny" ];then
			# the file exists check for the www-data line
			if grep -q "www-data" "/etc/at.deny";then
				# remove www-data from the deny file for scheduler
				data=$(grep --invert-match "www-data" "/etc/at.deny")
				echo "$data" > "/etc/at.deny"
			fi
		fi
		# install the php streaming script
		ln -s "/usr/share/mms/stream.php" "$webDirectory/stream.php"
		# link the randomFanart.php script
		ln -s "/usr/share/nfo2web/randomFanart.php" "$webDirectory/randomFanart.php"
		ln -s "$webDirectory/randomFanart.php" "$webDirectory/shows/randomFanart.php"
		ln -s "$webDirectory/randomFanart.php" "$webDirectory/movies/randomFanart.php"
		# link randomPoster.php
		ln -s "/usr/share/nfo2web/randomPoster.php" "$webDirectory/randomPoster.php"
		ln -s "$webDirectory/randomPoster.php" "$webDirectory/shows/randomPoster.php"
		ln -s "$webDirectory/randomPoster.php" "$webDirectory/movies/randomPoster.php"
		# link the stylesheet based on the chosen theme
		if ! [ -f /etc/mms/theme.cfg ];then
			echo "default.css" > "/etc/mms/theme.cfg"
			chown www-data:www-data "/etc/mms/theme.cfg"
		fi
		# load the chosen theme
		theme=$(cat "/etc/mms/theme.cfg")
		# link the theme and overwrite if another theme is chosen
		ln -sf "/usr/share/mms/themes/$theme" "$webDirectory/style.css"
		# link stylesheet
		ln -s "$webDirectory/style.css" "$webDirectory/movies/style.css"
		ln -s "$webDirectory/style.css" "$webDirectory/shows/style.css"
		# compare libaries to see if updates are needed
		#if [ -f "$webDirectory/state.cfg" ];then
		#	# a existing state was found
		#	currentSum=$(cat "$webDirectory/state.cfg")
		#	libarySum=$(getLibSum)
		#	# if the current state is the same as the state of the last update
		#	if [ "$libarySum" == "$currentSum" ];then
		#		# this means they are the same so no update needs run
		#		echo "[INFO]: State is unchanged, no update is needed."
		#		echo "[DEBUG]: $currentSum == $libarySum"
		#		exit
		#	else
		#		echo "[INFO]: States are diffrent, updating..."
		#		echo "[DEBUG]: $currentSum != $libarySum"
		#	fi
		#else
		#	echo "[INFO]: No state exists, updating..."
		#fi
		# create the log path
		logPagePath="$webDirectory/log.html"
		# create the homepage path
		homePagePath="$webDirectory/index.html"
		headerPagePath="$webDirectory/header.html"
		showIndexPath="$webDirectory/shows/index.html"
		movieIndexPath="$webDirectory/movies/index.html"
		touch "$showIndexPath"
		touch "$movieIndexPath"
		touch "$headerPagePath"
		touch "$logPagePath"
		touch "$homePagePath"
		{
			# build the header
			echo "<div id='header' class='header'>"
			echo "<a class='button' href='..'>"
			echo "HOME"
			echo "</a>"
			echo "<a class='button' href='link.php'>"
			echo "LINK"
			echo "</a>"
			#if grep -q "Movies" "$webDirectory/stats.index";then
				echo "<a class='button' href='movies'>"
				echo "MOVIES"
				echo "</a>"
			#fi
			#if grep -q "Shows" "$webDirectory/stats.index";then
				echo "<a class='button' href='shows'>"
				echo "SHOWS"
				echo "</a>"
			#fi
			#if [ -f "$webDirectory/kodi/channels.m3u" ];then
				echo "<a class='button' href='live'>"
				echo "Live"
				echo "</a>"
			#fi
			#if [ -d "$webDirectory/comics/" ];then
				echo "<a class='button' href='comics'>"
				echo "COMICS"
				echo "</a>"
			#fi
			echo "<a class='button' href='log.html'>"
			echo "LOG"
			echo "</a>"
			echo "<a class='button' href='system.php'>"
			echo "SETTINGS"
			echo "</a>"
			echo "</div>"
		} > "$headerPagePath"
		# build log page
		{
			echo "<html id='top' class='randomFanart'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			echo "<style>"
			# cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "<script>"
			cat /usr/share/nfo2web/nfo2web.js
			echo "</script>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath"
			# add the javascript sorter
			echo -n "<input type='button' class='button' value='Info'"
			echo    " onclick='toggleVisibleClass(\"INFO\")'>"
			echo -n "<input type='button' class='button' value='Error'"
			echo    " onclick='toggleVisibleClass(\"ERROR\")'>"
			echo -n "<input type='button' class='button' value='Warning'"
			echo    " onclick='toggleVisibleClass(\"WARNING\")'>"
			echo -n "<input type='button' class='button' value='Update'"
			echo    " onclick='toggleVisibleClass(\"UPDATE\")'>"
			echo -n "<input type='button' class='button' value='New'"
			echo    " onclick='toggleVisibleClass(\"NEW\")'>"
			# start the table
			echo "<table>"
		} > "$logPagePath"
		addToLog "INFO" "Started Update" "$(date)" "$logPagePath"
		#buildHomePage "$webDirectory" "$headerPagePath" --in-progress
		IFS_BACKUP=$IFS
		IFS=$(echo -e "\n")
		# read each libary from the libary config, single path per line
		#for libary in $libaries;do
		echo "$libaries" | while read libary;do
			# check if the libary directory exists
			addToLog "INFO" "Checking library path" "$libary" "$logPagePath"
			echo "Check if directory exists at $libary"
			if [ -e "$libary" ];then
				addToLog "INFO" "Starting library scan" "$libary" "$logPagePath"
				echo "[INFO]: library exists at '$libary'"
				# read each tvshow directory from the libary
				for show in "$libary"/*;do
					echo "[INFO]: show path = '$show'"
					################################################################################
					# process page metadata
					################################################################################
					# if the show directory contains a nfo file defining the show
					echo "[INFO]: searching for metadata at '$show/tvshow.nfo'"
					if [ -f "$show/tvshow.nfo" ];then
						echo "[INFO]: found metadata at '$show/tvshow.nfo'"
						# load update the tvshow.nfo file and get the metadata required for
						showMeta=$(cat "$show/tvshow.nfo")
						showTitle=$(ripXmlTag "$showMeta" "title")
						echo "[INFO]: showTitle = '$showTitle'"
						showTitle=$(cleanText "$showTitle")
						echo "[INFO]: showTitle after cleanText() = '$showTitle'"
						if echo "$showMeta" | grep "<tvshow>";then
							# make sure show has episodes
							if ls "$show"/*/*.nfo;then
								processShow "$show" "$showMeta" "$showTitle" "$webDirectory"
								# write log info from show to the log, this must be done here to keep ordering
								# of the log and to make log show even when the state of the show is unchanged
								echo "[INFO]: Adding logs from $webDirectory/shows/$showTitle/log.index to $logPagePath"
								cat "$webDirectory/shows/$showTitle/log.index" >> "$webDirectory/log.html"

							else
								echo "[ERROR]: Show has no episodes!"
								addToLog "WARNING" "Show has no episodes" "$show" "$logPagePath"
							fi
						else
							echo "[ERROR]: Show nfo file is invalid!"
							addToLog "ERROR" "Show NFO Invalid" "$show/tvshow.nfo" "$logPagePath"
						fi
					elif grep "<movie>" "$show"/*.nfo;then
						# this is a move directory not a show
						processMovie "$show" "$webDirectory"
						#buildMovieIndex
					fi
				done
				# rebuild the homepage after processing each existing libary item
				#buildHomePage "$webDirectory" "$headerPagePath" --in-progress
			fi
			#images=""
			#if find "$webDirectory/movies/" -name "poster.png";then
			#	echo "[INFO]: Found movie posters in PNG format"
			#	images="$images $(find "$webDirectory/movies/" -name "poster.png" -printf '"%p" ')"
			#	echo "[DEBUG]: images 1 = $images"
			#fi
			#if find "$webDirectory/shows/" -name "poster.png";then
			#	echo "[INFO]: Found show posters in PNG format"
			#	images="$images $(find "$webDirectory/shows/" -name "poster.png" -printf '"%p" ')"
			#	echo "[DEBUG]: images 2 = $images"
			#fi
			#if find "$webDirectory/shows/" -name "poster.jpg";then
			#	echo "[INFO]: Found show posters in JPG format"
			#	images="$images $(find "$webDirectory/shows/" -name "poster.jpg" -printf '"%p" ')"
			#	echo "[DEBUG]: images 3 = $images"
			#fi
			#if find "$webDirectory/movies/" -name "poster.jpg";then
			#	echo "[INFO]: Found movie posters in JPG format"
			#	images="$images $(find "$webDirectory/movies/" -name "poster.jpg" -printf '"%p" ')"
			#	echo "[DEBUG]: images 4 = $images"
			#fi
			# shuffle the list of images
			#images=$(echo "$images" | shuf)
			#tempFiles=""
			#for imageFile in $images;do
			#	tempFiles="$tempFiles -texture '$imageFile'"
			#done
			#echo "[DEBUG]: images 5 = $images"
			#tempFiles=$(echo "$images" | sed "s/\"\ /\" -texture /g")
			# build poster list
			#images=$(find "$webDirectory/" -name "poster.[jpg|png]" -printf "%p " | shuf)
			#echo "[DEBUG]: images 5 = $images"
			# create the homepage background after processing each libary location
			#montage -geometry -15-15 -alpha on -blur 1.5 -background none -tile 5x4 +polaroid \
			#montage -geometry +0+0 -alpha on -blur 100.5 -background none \
			#	"$webDirectory"/shows/*/poster.png \
			#	"$webDirectory"/shows/*/poster.jpg \
			#	"$webDirectory"/movies/*/poster.png \
			#	"$webDirectory"/movies/*/poster.jpg \
			#	"$webDirectory/background.png"
			# ------------------------------------------------------------------ #
			#	"$webDirectory"/shows/*/*/*.png \
			#	"$webDirectory"/shows/*/*/*.jpg \
			#	$(find "$webDirectory/movies/" -name "poster.jpg" -printf '"%p" ') \
			#	$(find "$webDirectory/shows/" -name "poster.jpg" -printf '"%p" ') \
			#	"$webDirectory/background.png"
			#echo "[DEBUG]: montage -geometry -15-15 -alpha on -blur 1.5 -background none -tile 5x4 +polaroid $tempFiles '$webDirectory/background.png'"
			#montage -geometry -15-15 -alpha on -blur 1.5 -background none -tile 5x4 +polaroid $tempFiles "$webDirectory/background.png"
			scanForRandomBackgrounds "$webDirectory"
		done
		# add the end to the log, add the jump to top button and finish out the html
		addToLog "INFO" "FINISHED" "$(date)" "$logPagePath"
		{
			echo "</table>"
			# add footer
			cat "$headerPagePath"
			# create top jump button
			echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
			echo "<hr class='topButtonSpace'>"
			echo "</body>"
			echo "</html>"
		} >> "$logPagePath"
		# create the final index pages, these should not have the progress indicator
		# build the final version of the homepage without the progress indicator
		buildHomePage "$webDirectory" "$headerPagePath"
		# build the movie index
		buildMovieIndex "$webDirectory" "$headerPagePath"
		# build the show index
		buildShowIndex "$webDirectory" "$headerPagePath"
		# write the md5sum state of the libary for change checking
		#echo "$libarySum" > "$webDirectory/state.cfg"
		#getLibSum > "$webDirectory/state.cfg"
		# remove active state file
		rm -v /tmp/nfo2web.active
		# read the tvshow.nfo files for each show
		################################################################################
		# Create the show link on index.html
		# - read poster.png as show button
		################################################################################
		# Create the series page
		# - While generating the series page, you must generate the episode page in its
		#   entirity, then proceed with generating the next episode link in the seasons
		#   page
		################################################################################
		# - Create show metadata at the top of the page
		# - set fanart.png as a static unscrolling background on the show page
		# load all nfo files in show into a list
		# find /pathToLibary/ -type f
		# read show title and remove tags with sed
		# feed nfo files into generator to extract seasons and episode .nfo data
		################################################################################
	else
		# if no arguments are given run the update then help commands.
		main update
		main help
	fi
}
main "$@"
