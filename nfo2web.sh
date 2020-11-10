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
	elif [ 1 -ge $(expr length "$stringToCheck") ];then
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
		# build the movie page path
		moviePagePath="$webDirectory/movies/$movieWebPath/index.html"
		echo "[INFO]: movie page path = '$moviePagePath'"
		mkdir -p "$webDirectory/movies/$movieWebPath/"
		chown -R www-data:www-data "$webDirectory/movies/$movieWebPath/"
		mkdir -p "$webDirectory/kodi/movies/$movieWebPath/"
		chown -R www-data:www-data "$webDirectory/kodi/movies/$movieWebPath/"
		# link stylesheets
		ln -s "$webDirectory/style.css" "$webDirectory/movies/$movieWebPath/style.css"
		# create the path sum for reconizing the libary path
		pathSum=$(echo "$movieDir" | md5sum | cut -d' ' -f1)
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
				addToLog "INFO" "Movie unchanged" "$movieTitle" "$logPagePath"
				return
			else
				echo "[INFO]: States are diffrent, updating $movieTitle..."
				echo "[DEBUG]: $currentSum != $libarySum"
				addToLog "INFO" "Updating movie" "$movieTitle" "$logPagePath"
			fi
		else
			echo "[INFO]: No movie state exists for $movieTitle, updating..."
			addToLog "INFO" "Adding new movie " "$movieTitle" "$logPagePath"
		fi
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
						convert "$thumbnailPath.png" -resize 400x200\! "$thumbnailPath.png"
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
					convert "$thumbnailPath.jpg" -resize 400x200\! "$thumbnailPath.jpg"
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
			tempStyle="html{ background-image: url('../fanart.png') }"
		elif [ -f "$webDirectory/shows/$episodeShowTitle/fanart.jpg" ];then
			tempStyle="html{ background-image: url('../fanart.jpg') }"
		fi
		# start rendering the html
		{
			echo "<html id='top'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			echo "<style>"
			#add the fanart
			echo "$tempStyle"
			#add the stylesheet
			#cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\/..\/..\//g"
			echo "<div class='titleCard'>"
			echo "<h1>$episodeShowTitle</h1>"
			echo "<h2>$episodeTitle</h2>"
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
		# link the video from the libary to the generated website
		echo "[INFO]: linking '$episodeVideoPath' to '$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix'"
		ln -s "$episodeVideoPath" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
		ln -s "$episodeVideoPath" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"

		# remove .nfo extension and create thumbnail path
		thumbnail="${episode//.nfo}-thumb"
		echo "[INFO]: thumbnail template = $thumbnail"
		echo "[INFO]: thumbnail path 1 = $thumbnail.png"
		echo "[INFO]: thumbnail path 2 = $thumbnail.jpg"
		thumbnailPath="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		thumbnailPathKodi="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		# check for the thumbnail and link it
		checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi"
		thumbnailExt=getThumbnailExt "$thumbnailPath"
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
				echo "<div class='descriptionCard'>"
				# create a hard link
				echo "<a class='button hardLink' href='$ytLink'>"
				echo "	Hard Link"
				echo "</a>"
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
				# create a hard link
				echo "<a class='button hardLink' href='$episodePath$sufix'>"
				echo "Hard Link"
				echo "</a>"
				echo "$episodePlot"
				echo "</div>"
			} >> "$episodePagePath"
		fi
		{
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
	# create directory
	echo "[INFO]: creating show directory at '$webDirectory/$showTitle/'"
	mkdir -p "$webDirectory/shows/$showTitle/"
	chown -R www-data:www-data "$webDirectory/shows/$showTitle"
	# link stylesheet
	ln -s "$webDirectory/style.css" "$webDirectory/shows/$showTitle/style.css"
	# create the path sum for reconizing the libary path
	pathSum=$(echo "$show" | md5sum | cut -d' ' -f1)
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
			addToLog "INFO" "Updating show" "$showTitle" "$logPagePath"
		fi
	else
		echo "[INFO]: No show state exists for $showTitle, updating..."
		addToLog "INFO" "Creating new show" "$showTitle" "$logPagePath"
	fi
	if grep "<episodedetails>" "$show"/*.nfo;then
		# search all nfo files in the show directory if any are episodes
		addToLog "ERROR" "Episodes outside of season directories" "$showTitle has episode NFO files outside of a season folder" "$logPagePath"
	fi
	# check and remove duplicate thubnails for this show, failed thumbnails on the
	# same show generally fail in the same way
	fdupes --recurse --delete --immediate "$webDirectory/shows/$showTitle/"
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
			# build top of show webpage containing all of the shows meta info
			{
				tempStyle="html{ background-image: url(\"$fanartPath\") }"
				echo "<html id='top' style='$tempStyle'>"
				echo "<head>"
				echo "<style>"
				echo "$tempStyle"
				cat /usr/share/nfo2web/style.css
				echo "</style>"
				echo "</head>"
				echo "<body>"
				cat "$headerPagePath" | sed "s/href='/href='..\/..\//g"
				echo "<div class='titleCard'>"
				echo "<h1>$showTitle</h1>"
				echo "</div>"
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
				# create top jump button
				echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
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
		echo "<tr class='$errorType'>"
		echo "<td>"
		echo "$errorType"
		echo "</td>"
		echo "<td>"
		echo "$errorDescription"
		echo "</td>"
		echo "<td>"
		# convert the error details into html
		echo "$errorDetails" | txt2html --extract
		echo "</td>"
		echo "<td>"
		date "+%D"
		echo "</td>"
		echo "<td>"
		date "+%R:%S"
		echo "</td>"
		echo "</tr>"
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
	# buildUpdatedShows $webDirectory $numberOfShows $sourcePrefix
	################################################################################
	# Build a list of updated shows
	################################################################################
	webDirectory=$1
	numberOfShows=$2
	sourcePrefix=$3
	################################################################################
	updatedShows=$(ls -1tr "$webDirectory"/shows/*/shows.index| tail -n "$numberOfShows")
	echo "<div class='titleCard'>"
	echo "<h1>Updated Shows</h1>"
	echo "<hr>"
	echo "<div class='listCard'>"
	echo "$updatedShows" | while read -r line;do
		# fix index links to work for homepage
		cat "$line" | sed "s/src='/src='$sourcePrefix/g" | sed "s/href='/href='$sourcePrefix/g"
	done
	echo "</div>"
	echo "</div>"
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
	echo "<div class='titleCard'>"
	echo "<h1>Random Shows</h1>"
	echo "<hr>"
	echo "<div class='listCard'>"
	echo "$randomShows" | while read -r line;do
		# fix index links to work for homepage
		cat "$line" | sed "s/src='/src='$sourcePrefix/g" | sed "s/href='/href='$sourcePrefix/g"
	done
	echo "</div>"
	echo "</div>"
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
	updatedMovies=$(ls -1tr "$webDirectory"/movies/*/movies.index| tail -n "$numberOfMovies")
	echo "<div class='titleCard'>"
	echo "<h1>Updated Movies</h1>"
	echo "<hr>"
	echo "<div class='listCard'>"
	echo "$updatedMovies" | while read -r line;do
		# fix index links to work for homepage
		cat "$line" | sed "s/src='/src='$sourcePrefix/g" | sed "s/href='/href='$sourcePrefix/g"
	done
	echo "</div>"
	echo "</div>"
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
	echo "<div class='titleCard'>"
	echo "<h1>Random Movies</h1>"
	echo "<hr>"
	echo "<div class='listCard'>"
	echo "$randomMovies" | while read -r line;do
		# fix index links to work for homepage
		cat "$line" | sed "s/src='/src='$sourcePrefix/g" | sed "s/href='/href='$sourcePrefix/g"
	done
	echo "</div>"
	echo "</div>"
}
########################################################################
buildHomePage(){
	webDirectory=$1
	headerPagePath=$2
	echo "[INFO]: Building home page..."
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
			echo "Last updated on $(date)"
			echo "</div>"
	} > "$webDirectory/index.html"
	################################################################################
	buildUpdatedShows "$webDirectory" 15 "shows\/" >> "$webDirectory/index.html"
	buildUpdatedMovies "$webDirectory" 15 "movies\/" >> "$webDirectory/index.html"
	################################################################################
	buildRandomShows "$webDirectory" 15 "shows\/" >> "$webDirectory/index.html"
	buildRandomMovies "$webDirectory" 15 "movies\/" >> "$webDirectory/index.html"
	########################################################################
	{
		# create top jump button
		echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
		echo "</body>"
		echo "</html>"
	} >>  "$webDirectory/index.html"
	#STOP
}
########################################################################
getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(ls -R "$line")
	# convert lists into md5sum
	tempLibList="$(echo "$totalList" | md5sum | cut -d' ' -f1)"
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
		echo "########################################################################"
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		echo "[INFO]: Reseting web cache states..."
		rm -rv /var/cache/nfo2web/web/*/*/state_*.cfg
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
		else
			mkdir -p /var/cache/nfo2web/libary/
			echo "/var/cache/nfo2web/libary" > /etc/nfo2web/libaries.cfg
			libaries="/var/cache/nfo2web/libary"
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
			# create a trap to remove nfo2web
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
		# link the randomFanart.php script
		ln -s "/usr/share/nfo2web/randomFanart.php" "$webDirectory/randomFanart.php"
		ln -s "$webDirectory/randomFanart.php" "$webDirectory/shows/randomFanart.php"
		ln -s "$webDirectory/randomFanart.php" "$webDirectory/movies/randomFanart.php"
		# link randomPoster.php
		ln -s "/usr/share/nfo2web/randomPoster.php" "$webDirectory/randomPoster.php"
		ln -s "$webDirectory/randomPoster.php" "$webDirectory/shows/randomPoster.php"
		ln -s "$webDirectory/randomPoster.php" "$webDirectory/movies/randomPoster.php"
		# link stylesheets
		ln -s "/usr/share/nfo2web/style.css" "$webDirectory/style.css"
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
			echo "<a class='button' href='kodi'>"
			echo "KODI"
			echo "</a>"
			echo "<a class='button' href='movies'>"
			echo "MOVIES"
			echo "</a>"
			echo "<a class='button' href='shows'>"
			echo "SHOWS"
			echo "</a>"
			echo "<a class='button' href='log.html'>"
			echo "LOG"
			echo "</a>"
			echo "<a class='button' href='settings.php'>"
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
			echo "<input type='button' class='button' value='Warnings' onclick='toggleWarnings()'>"
			echo "<input type='button' class='button' value='Errors' onclick='toggleErrors()'>"
			echo "<input type='button' class='button' value='Info' onclick='toggleInfos()'>"
			# start the table
			echo "<table>"
		} > "$logPagePath"
		addToLog "INFO" "Started Update" "$(date)" "$logPagePath"
		buildHomePage "$webDirectory" "$headerPagePath" --in-progress
		IFS_BACKUP=$IFS
		IFS=$(echo -e "\n")
		# read each libary from the libary config, single path per line
		#for libary in $libaries;do
		echo "$libaries" | while read libary;do
			# check if the libary directory exists
			echo "Check if directory exists at $libary"
			if [ -e "$libary" ];then
				echo "[INFO]: libary exists at '$libary'"
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
								# update the show index
								{
									echo "<html id='top' class='randomFanart'>"
									echo "<head>"
									echo "<link rel='stylesheet' href='style.css' />"
									#echo "<style>"
									#cat /usr/share/nfo2web/style.css
									#echo "</style>"
									echo "</head>"
									echo "<body>"
									updateInProgress
									cat "$headerPagePath" | sed "s/href='/href='..\//g"
									# add the most recently updated series
									buildUpdatedShows "$webDirectory" 15 ""
									# load all existing shows into the index
									cat "$webDirectory"/shows/*/shows.index
									# create top jump button
									echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
									echo "</body>"
									echo "</html>"
								} > "$showIndexPath"
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
						# update the movie index webpage
						{
							echo "<html id='top' class='randomFanart'>"
							echo "<head>"
							echo "<link rel='stylesheet' href='style.css' />"
							#echo "<style>"
							#cat /usr/share/nfo2web/style.css
							#echo "</style>"
							echo "</head>"
							echo "<body>"
							updateInProgress
							cat "$headerPagePath" | sed "s/href='/href='..\//g"
							buildUpdatedMovies "$webDirectory" 15 ""
							# load the movie index parts
							cat "$webDirectory"/movies/*/movies.index
							# create top jump button
							echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
							echo "</body>"
							echo "</html>"
						} > "$movieIndexPath"
					fi
				done
				# rebuild the homepage after processing each existing libary item
				buildHomePage "$webDirectory" "$headerPagePath" --in-progress
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
			#########################################################################
			# create fanart list
			#########################################################################
			# move into the web directory so paths from below searches are relative
			cd $webDirectory
			find -L "shows/" -type f -name "fanart.png" > "$webDirectory/fanart.cfg"
			find -L "shows/" -type f -name "fanart.jpg" >> "$webDirectory/fanart.cfg"
			find -L "movies/" -type f -name "fanart.png" >> "$webDirectory/fanart.cfg"
			find -L "movies/" -type f -name "fanart.jpg" >> "$webDirectory/fanart.cfg"
			# create shows only fanart.cfg
			cd "$webDirectory/shows/"
			find -L "." -type f -name "fanart.png" > "$webDirectory/shows/fanart.cfg"
			find -L "." -type f -name "fanart.jpg" >> "$webDirectory/shows/fanart.cfg"
			# create movies only fanart.cfg
			cd "$webDirectory/movies/"
			find -L "." -type f -name "fanart.png" > "$webDirectory/movies/fanart.cfg"
			find -L "." -type f -name "fanart.jpg" >> "$webDirectory/movies/fanart.cfg"
			# move into the web directory so paths from below searches are relative
			cd $webDirectory
			find -L "shows/" -type f -name "poster.png" > "$webDirectory/poster.cfg"
			find -L "shows/" -type f -name "poster.jpg" >> "$webDirectory/poster.cfg"
			find -L "movies/" -type f -name "poster.png" >> "$webDirectory/poster.cfg"
			find -L "movies/" -type f -name "poster.jpg" >> "$webDirectory/poster.cfg"
			# create shows only poster.cfg
			cd "$webDirectory/shows/"
			find -L "." -type f -name "poster.png" > "$webDirectory/shows/poster.cfg"
			find -L "." -type f -name "poster.jpg" >> "$webDirectory/shows/poster.cfg"
			# create movies only poster.cfg
			cd "$webDirectory/movies/"
			find -L "." -type f -name "poster.png" > "$webDirectory/movies/poster.cfg"
			find -L "." -type f -name "poster.jpg" >> "$webDirectory/movies/poster.cfg"
		done
		{
			# add the end to the log, add the jump to top button and finish out the html
			addToLog "INFO" "FINISHED" "$(date)" "$logPagePath"
			echo "</table>"
			# create top jump button
			echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
			echo "</body>"
			echo "</html>"
		} >> "$logPagePath"
		# create the final index pages, these should not have the progress indicator
		{
			echo "<html id='top' class='randomFanart'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			#echo "<style>"
			#cat /usr/share/nfo2web/style.css
			#echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\//g"
			buildUpdatedMovies "$webDirectory" 15 ""
			# load the movie index parts
			cat "$webDirectory"/movies/*/movies.index
			# create top jump button
			echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
			echo "</body>"
			echo "</html>"
		} > "$movieIndexPath"
		{
			# write the show index page after everything has been generated
			echo "<html id='top' class='randomFanart'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css' />"
			#echo "<style>"
			#cat /usr/share/nfo2web/style.css
			#echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\//g"
			buildUpdatedShows "$webDirectory" 15  ""
			# load all existing shows into the index
			cat "$webDirectory"/shows/*/shows.index
			# create top jump button
			echo "<a href='#top' id='topButton' class='button'>&uarr;</a>"
			echo "</body>"
			echo "</html>"
		} > "$showIndexPath"
		# build the final version of the homepage without the progress indicator
		buildHomePage "$webDirectory" "$headerPagePath"
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
