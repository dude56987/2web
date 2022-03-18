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
	echo ">>>>>>>>>>>DEBUG STOPPER<<<<<<<<<<<"
	read -r
}
########################################################################
INFO(){
	width=$(tput cols)
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[INFO]: $1$buffer" | tail -n 1 | cut -b"1-$(( $width - 1 ))" )"
	# print the line
	printf "$output\r"
}
########################################################################
cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	if test -f /usr/bin/inline-detox;then
		echo "$1" | inline-detox --remove-trailing | sed "s/_/ /g" | tr -d '#'
	else
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
	fi
}
########################################################################
function debugCheck(){
	if test -f /etc/2web/nfo/debug.enabled;then
		# if debug mode is enabled show execution
		set -x
	else
		if ! test -d /etc/2web/nfo/;then
			# create dir if one does not exist
			mkdir -p /etc/2web/nfo/
		fi
		if ! test -f /etc/2web/nfo/debug.disabled;then
			# create debug flag file disabed, if it does not exist
			touch /etc/2web/nfo/debug.disabled
		fi
	fi
}
########################################################################
function validString(){
	stringToCheck="$1"
	if ! echo "$@" | grep -q "\-q";then
		INFO "Checking string '$stringToCheck'"
	fi
	# convert string letters to all uppercase and look for returned NULL string
	# jq returns these strings instead of failing outright
	if echo "${stringToCheck^^}" | grep "NULL";then
		# this means the function is a null string returned by jq
		if ! echo "$@" | grep -q "\-q";then
			INFO "[WARNING]:string is a NULL value"
		fi
		return 2
	elif [ 1 -ge "$(expr length "$stringToCheck")" ];then
		# this means the string is only one character
		if ! echo "$@" | grep -q "\-q";then
			INFO "[WARNING]:String length is less than one"
		fi
		return 1
	else
		# all checks have been passed the string is correct
		if ! echo "$@" | grep -q "\-q";then
			INFO "String passed all checks and is correct"
		fi
		return 0
	fi
}
########################################################################
ripXmlTag(){
	data=$1
	tag=$2

	# remove complex xml tags, they make parsing more difficult
	data=$(echo "$data" | grep -E --ignore-case --only-matching "<$tag>.*?</$tag>" | tr -d '\n' | tac | tail -n 1)

	# remove after slash tags, they break everything
	data="${data//<$tag \/>}"
	data="${data//<$tag\/>}"

	# pull data from between the tags
	data="$(echo "$data" | cut -d'>' -f2 )"
	data="$(echo "$data" | cut -d'<' -f1 )"
	# if multuple lines of tag info are given format them for html
	if validString "$tag" -q;then
		echo "$data"
		return 0
	else
		ALERT "[DEBUG]: Tag must be at least one character in length!"
		ALERT "[ERROR]: Program FAILURE has occured!"
		return 1
	fi
}
########################################################################
ripXmlTagMultiLine(){
	data=$1
	tag=$2
	# remove complex xml tags, they make parsing more difficult
	data=$(echo "$data" | grep -E --ignore-case --only-matching "<$tag>.*.?</$tag>")
	# remove after slash tags, they break everything
	data="${data//<$tag \/>}"
	data="${data//<$tag\/>}"
	# pull data from between the tags
	data="${data//<$tag>}"
	data="${data//<\/$tag>}"
	#data="$(echo "$data" | cut -d'>' -f2 )"
	#data="$(echo "$data" | cut -d'<' -f1 )"
	# if multuple lines of tag info are given format them for html
	if validString "$tag" -q;then
		echo "$data"
		return 0
	else
		ALERT "[DEBUG]: Tag must be at least one character in length!"
		ALERT "[ERROR]: Program FAILURE has occured!"
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
ALERT(){
	echo
	echo "$1";
	echo
}
########################################################################
checkMovieThumbPaths(){
	movieDir=$1
	thumbnail=$2
	thumbnailShort=$3
	logPagePath=$4
	thumbnailPath=$5
	thumbnailPathKodi=$6

	# check for movie thumb paths and output the correct found path
	possibleThumbPaths=""
	possibleThumbPaths="${possibleThumbPaths}$thumbnail\n"
	possibleThumbPaths="${possibleThumbPaths}$movieDir/poster\n"
	possibleThumbPaths="${possibleThumbPaths}$thumbnailShort\n"
	possibleThumbPaths="${possibleThumbPaths}$thumbnailShort-thumb\n"
	possibleThumbPaths=${possibleThumbPaths// /\ }
	# scan all the possible thumbnail paths
	#for thumbPathToCheck in $possibleThumbPaths;do
	#addToLog "DEBUG" "Checking possible file paths for thumbnail" "'$possibleThumbPaths'" "$logPagePath"
	echo -e "$possibleThumbPaths" | while read -r thumbPathToCheck;do
		#ALERT "reading possible thumb path '$thumbPathToCheck'"
		#addToLog "DEBUG" "Checking file path for thumbnail" "Checking file path '$thumbPathToCheck$thumbExtToCheck'" "$logPagePath"
		possibleThumbExts=".jpg .png"
		#echo "$possibleThumbExts" | shuf |  while read -r thumbExtToCheck;do
		for thumbExtToCheck in $possibleThumbExts;do
			#ALERT "Reading possible thumb extension '$thumbExtToCheck'"
			#ALERT "Checking file path '$thumbPathToCheck$thumbExtToCheck'"
			#addToLog "DEBUG" "Checking file path for thumbnail" "Checking file path '$thumbPathToCheck$thumbExtToCheck'" "$logPagePath"
			if test -f "$thumbPathToCheck$thumbExtToCheck";then
				addToLog "NEW" "Found thumbnail Path" "'$thumbPathToCheck$thumbExtToCheck'" "$logPagePath"
				thumbnailExt="$thumbExtToCheck"
				# link thumbnail into output directory
				linkFile "$thumbPathToCheck$thumbExtToCheck" "$thumbnailPath$thumbExtToCheck"
				linkFile "$thumbPathToCheck$thumbExtToCheck" "$thumbnailPathKodi$thumbExtToCheck"
				if ! test -f "$thumbnailPath-web.png";then
					convert -quiet "$thumbPathToCheck$thumbnailExtToCheck" -adaptive-resize "300x200" "$thumbnailPath-web.png"
					#if [ $thumbExtToCheck == ".png" ];then
					#	#convert -quiet "$thumbPathToCheck$thumbnailExtToCheck" "$thumbnailPath.jpg"
					#	convert -quiet "$thumbPathToCheck$thumbnailExtToCheck" -adaptive-resize "200x100" "$thumbnailPath-web.png"
					#else
					#	convert -quiet "$thumbPathToCheck$thumbnailExtToCheck" -adaptive-resize "200x100" "$thumbnailPath-web.png"
					#fi
				fi
				# ouput the return value
				echo "$thumbPathToCheck$thumbExtToCheck"
				# return success
				return 0
			fi
		done
	done
	# return error no output
	return 1
}





########################################################################
processMovie(){
	moviePath=$1
	webDirectory=$2
	# figure out the movie directory
	movieDir=$moviePath
	# find the movie nfo in the movie path
	moviePath=$(find "$moviePath"/*.nfo)
	# create log path
	logPagePath="$webDirectory/log.php"
	#INFO "Processing movie $moviePath"
	# if moviepath exists
	if test -f "$moviePath";then
		# create the path sum for reconizing the libary path
		pathSum=$(echo -n "$movieDir" | md5sum | cut -d' ' -f1)

		################################################################################
		# for each episode build a page for the episode
		nfoInfo=$(cat "$moviePath")
		# rip the movie title
		movieTitle=$(cleanXml "$nfoInfo" "title")
		#INFO "movie title = '$movieTitle'"
		movieYear=$(cleanXml "$nfoInfo" "year")
		#INFO "movie year = '$movieYear'"
		#moviePlot=$(ripXmlTag "$nfoInfo" "plot" | txt2html --extract -p 10)
		moviePlot=$(ripXmlTagMultiLine "$nfoInfo" "plot")
		#INFO "movie plot = '$moviePlot'"
		moviePlot=$(echo "$moviePlot" | txt2html --extract )
		#INFO "movie plot = '$moviePlot'"
		# create the episode page path
		# each episode file title must be made so that it can be read more easily by kodi
		movieWebPath="${movieTitle} ($movieYear)"
		#INFO "movie web path = '$movieWebPath'"
		INFO "Processing movie $movieTitle $movieYear at $movieDir"
		# if the movie is updating a check should be preformed to see if there are multuple  state_*.cfg files inside the movie web directory
		if [ "$( find "$webDirectory/movies/$movieWebPath/" -type f -name 'state_*.cfg' | wc -l )" -gt 1 ];then
			# there are more than one sources for this same movie in the libaries this will cause a forever update marking the movie as new on every update
			addToLog "ERROR" "Multiple Movie Sources" "Movie path '$movieDir' is a duplicate\n\nYou can remove on the server with the command\n\n\trm -rvi '$movieDir'\n\nRemove excess copies to stabilize the library data.\n\nBackups should not be placed in media library paths." "$logPagePath"
		fi
		################################################################################
		# check the state now that the movie web path has been determined
		################################################################################
		# check movie state as soon as posible processing
		if test -f "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg";then
			# a existing state was found
			currentSum=$(cat "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg")
			libarySum=$(getDirSum "$movieDir")
			# create the info
			updateInfo="$movieTitle\n\n$currentSum != $libarySum\n\n$(ls "$movieDir")\n\n$moviePath\n\n$1\n\n$movieDir"
			#unchangedInfo="$movieTitle\n\n$currentSum == $libarySum\n\n$(ls "$movieDir")\n\n$moviePath\n\n$1\n\n$movieDir"
			unchangedInfo="$movieTitle"
			# if the current state is the same as the state of the last update
			if [ "$libarySum" == "$currentSum" ];then
				# this means they are the same so no update needs run
				#INFO "State is unchanged for $movieTitle, no update is needed."
				#ALERT "[DEBUG]: $currentSum == $libarySum"
				addToLog "INFO" "Movie unchanged" "$unchangedInfo" "$logPagePath"
				return
			else
				#INFO "States are diffrent, updating $movieTitle..."
				#ALERT "[DEBUG]: $currentSum != $libarySum"
				addToLog "UPDATE" "Updating Movie" "$updateInfo" "$logPagePath"
			fi
		else
			#ALERT "No movie state exists for $movieTitle, updating..."
			addToLog "NEW" "Adding new movie " "Adding '$movieTitle' from '$movieDir'" "$logPagePath"
		fi
		################################################################################
		# After checking state build the movie page path, and build directories/links
		################################################################################
		moviePagePath="$webDirectory/movies/$movieWebPath/index.php"
		#INFO "movie page path = '$moviePagePath'"
		mkdir -p "$webDirectory/movies/$movieWebPath/"
		chown -R www-data:www-data "$webDirectory/movies/$movieWebPath/"
		mkdir -p "$webDirectory/kodi/movies/$movieWebPath/"
		chown -R www-data:www-data "$webDirectory/kodi/movies/$movieWebPath/"
		# link stylesheets
		linkFile "$webDirectory/style.css" "$webDirectory/movies/$movieWebPath/style.css"
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
			#INFO "[ERROR]: could not find video file"
			addToLog "ERROR" "No video file in directory" "$movieDir\n\nTo remove empty movie use the below command\n\nrm -rvi '$movieDir'" "$logPagePath"
			return
		fi
		# set the video type based on the found video path
		if echo "$videoPath" | grep -q --ignore-case ".mp3";then
			mediaType="audio"
			mimeType="audio/mp3"
		elif echo "$videoPath" | grep -q --ignore-case ".ogg";then
			mediaType="audio"
			mimeType="audio/ogg"
		elif echo "$videoPath" | grep -q --ignore-case ".ogv";then
			mediaType="video"
			mimeType="video/ogv"
		elif echo "$videoPath" | grep -q --ignore-case ".mp4";then
			mediaType="video"
			mimeType="video/mp4"
		elif echo "$videoPath" | grep -q --ignore-case ".m4v";then
			mediaType="video"
			mimeType="video/m4v"
		elif echo "$videoPath" | grep -q --ignore-case ".avi";then
			mediaType="video"
			mimeType="video/avi"
		elif echo "$videoPath" | grep -q --ignore-case ".mpeg";then
			mediaType="video"
			mimeType="video/mpeg"
		elif echo "$videoPath" | grep -q --ignore-case ".mpg";then
			mediaType="video"
			mimeType="video/mpg"
		elif echo "$videoPath" | grep -q --ignore-case ".mkv";then
			mediaType="video"
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		# link the movie nfo file
		#INFO "linking $moviePath to $webDirectory/movies/$movieWebPath/$movieWebPath.nfo"
		linkFile "$moviePath" "$webDirectory/movies/$movieWebPath/$movieWebPath.nfo"
		#INFO "linking $moviePath to $webDirectory/kodi/movies/$movieWebPath/$movieWebPath.nfo"
		linkFile "$moviePath" "$webDirectory/kodi/movies/$movieWebPath/$movieWebPath.nfo"
		# show gathered info
		#INFO "mediaType = $mediaType"
		#INFO "mimeType = $mimeType"
		#INFO "videoPath = $videoPath"
		movieVideoPath="${moviePath//.nfo/$sufix}"
		#INFO "movieVideoPath = $videoPath"

		# link the video from the libary to the generated website
		#INFO "linking '$movieVideoPath' to '$webDirectory/movies/$movieWebPath/$movieWebPath$sufix'"
		linkFile "$movieVideoPath" "$webDirectory/movies/$movieWebPath/$movieWebPath$sufix"

		#INFO "linking '$movieVideoPath' to '$webDirectory/kodi/movies/$movieWebPath/$movieWebPath$sufix'"
		linkFile "$movieVideoPath" "$webDirectory/kodi/movies/$movieWebPath/$movieWebPath$sufix"

		# remove .nfo extension and create thumbnail path template
		thumbnail="${moviePath//.nfo}-poster"
		# creating alternate thumbnail paths
		thumbnailShort="${moviePath//.nfo}"
		thumbnailPath="$webDirectory/movies/$movieWebPath/$movieWebPath-poster"
		thumbnailPathKodi="$webDirectory/kodi/movies/$movieWebPath/$movieWebPath-poster"
		#addToLog "DEBUG" "thumbnailPath" "$thumbnailPath" "$logPagePath"
		#addToLog "DEBUG" "thumbnailShort" "$thumbnailShort" "$logPagePath"
		#addToLog "DEBUG" "thumbnailShort2" "$thumbnailShort-thumb" "$logPagePath"
		#INFO "new thumbnail path = '$thumbnailPath'"
		# link all images to the kodi path
		if [ $(find "$movieDir" -type f -name '*.jpg' | wc -l) -gt 0 ] ;then
			INFO "Found media '$movieDir/*.jpg' !"
			# do not use linkFile function here or it will cause overwrites
			ln -s "$movieDir"/*.jpg "$webDirectory/kodi/movies/$movieWebPath/"
			#ln -s "$movieDir"/*.jpg "$webDirectory/movies/$movieWebPath/"
		elif [ $(find "$movieDir" -type f -name '*.png' | wc -l) -gt 0 ] ;then
			INFO "Found media '$movieDir/*.png' !"
			# do not use linkFile function here or it will cause overwrites
			ln -s "$movieDir"/*.png "$webDirectory/kodi/movies/$movieWebPath/"
			#ln -s "$movieDir"/*.png "$webDirectory/movies/$movieWebPath/"
		else
			ALERT "[ERROR]: No media files could be found!"
			addToLog "ERROR" "No media files could be found!" "Check in $movieDir for debug info" "$logPagePath"
		fi
		# copy over subtitles
		if [ $(find "$movieDir" -type f -name '*.srt' | wc -l) -gt 0 ] ;then
			linkFile "$movieDir"/*.srt "$webDirectory/kodi/movies/$movieWebPath/"
		elif [ $(find "$movieDir" -type f -name '*.sub' | wc -l) -gt 0 ] ;then
			linkFile "$movieDir"/*.sub "$webDirectory/kodi/movies/$movieWebPath/"
		elif [ $(find "$movieDir" -type f -name '*.idx' | wc -l) -gt 0 ] ;then
			linkFile "$movieDir"/*.idx "$webDirectory/kodi/movies/$movieWebPath/"
		fi
		# link the fanart
		if test -f "$movieDir/fanart.png";then
			#INFO "Found $movieDir/fanart.png"
			fanartPath="fanart.png"
			#INFO "Found fanart at '$movieDir/$fanartPath'"
			linkFile "$movieDir/$fanartPath" "$webDirectory/movies/$movieWebPath/$fanartPath"
			linkFile "$movieDir/$fanartPath" "$webDirectory/kodi/movies/$movieWebPath/$fanartPath"
		elif test -f "$movieDir/fanart.jpg";then
			fanartPath="fanart.jpg"
			#INFO "Found fanart at '$movieDir/$fanartPath'"
			linkFile "$movieDir/$fanartPath" "$webDirectory/movies/$movieWebPath/$fanartPath"
			linkFile "$movieDir/$fanartPath" "$webDirectory/kodi/movies/$movieWebPath/$fanartPath"
		else
			ALERT "[WARNING]: could not find fanart '$movieDir/fanart.[png/jpg]'"
		fi
		# find the fanart for the episode background
		if test -f "$movieDir/fanart.png";then
			tempStyle="html{ background-image: url(\"fanart.png\") }"
		elif test -f "$movieDir/fanart.jpg";then
			tempStyle="html{ background-image: url(\"fanart.jpg\") }"
		fi
		# start rendering the html
		{
			echo "<html id='top'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='/style.css' />"
			echo "<style>"
			echo "$tempStyle"
			echo "</style>"
			echo "<link rel='icon' type='image/png' href='/favicon.png'>"
			echo "</head>"
			echo "<body>"
			echo "<?PHP";
			echo "include('../../header.php')";
			echo "?>";
			echo "<div class='titleCard'>"
			echo "<h1>$movieTitle ($movieYear)</h1>"
			echo "</div>"
		} > "$moviePagePath"

		# check for the thumbnail and link it
		#checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi"

		# check for a local thumbnail
		if test -f "$thumbnailPath.jpg";then
			#INFO "Thumbnail already linked..."
			thumbnailExt=".jpg"
		elif test -f "$thumbnailPath.png";then
			#INFO "Thumbnail already linked..."
			thumbnailExt=".png"
		else
			#INFO "No thumbnail exists, looking for thumb file..."

			# no thumbnail has been linked or downloaded
			fullThumbPath=$(checkMovieThumbPaths "$movieDir" "$thumbnail" "$thumbnailShort" "$logPagePath" "$thumbnailPath" "$thumbnailPathKodi")
			#addToLog "DEBUG" "Full Thumbnail Path" "'$fullThumbPath'" "$logPagePath"

			if test -f "$fullThumbPath";then
				thumbnailExt=".$(echo "$fullThumbPath" | cut -d'.' -f2)"
				if ! test -f "$thumbnailPath-web.png";then
					# create the web thumbnail if it does not exist
					convert "$fullThumbPath" -adaptive-resize "300x200" "$thumbnailPath-web.png"
				fi
			fi
			#addToLog "DEBUG" "Full Thumbnail Path" "'$thumbnailPath$thumbnailExt'" "$logPagePath"

			if ! test -f "$thumbnailPath-web.png";then
				if echo "$nfoInfo" | grep -q "fanart";then
					# pull the double nested xml info for the movie thumb
					#INFO "[DEBUG]: ThumbnailLink phase 1 = $thumbnailLink"
					thumbnailLink=$(ripXmlTag "$nfoInfo" "fanart")
					#INFO "[DEBUG]: ThumbnailLink phase 2 = $thumbnailLink"
					thumbnailLink=$(ripXmlTag "$thumbnailLink" "thumb")
					#INFO "[DEBUG]: ThumbnailLink phase 3 = $thumbnailLink"
					if validString "$thumbnailLink" -q;then
						#INFO "Try to download movie thumbnail..."
						#INFO "Thumbnail found at '$thumbnailLink'"
						addToLog "WARNING" "Downloading Thumbnail" "Creating thumbnail from link '$thumbnailLink'" "$logPagePath"
						thumbnailExt=".png"
						# download the thumbnail
						ALERT "downloadThumbnail '$thumbnailLink' '$thumbnailPath' '$thumbnailExt'"
						downloadThumbnail "$thumbnailLink" "$thumbnailPath" "$thumbnailExt"
						#curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
						#if ! test -f "$thumbnailPath$thumbnailExt";then
						#	curl "$thumbnailLink" | convert - "$thumbnailPath$thumbnailExt"
						#fi
						# generate the web thumbnail
						#convert "$thumbnailPath$thumbnailExt" -adaptive-resize "200x100" "$thumbnailPath-web.png"
						convert "$fullThumbPath" -adaptive-resize "300x200" "$thumbnailPath-web.png"
						# link the downloaded thumbnail
						linkFile "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
					else
						ALERT "[DEBUG]: Thumbnail download link is invalid '$thumbnailLink'"
					fi
				fi
				#touch "$thumbnailPath$thumbnailExt"
				# check if the thumb download failed
				if test -f "$thumbnailPath$thumbnailExt";then
					tempFileSize=$(wc --bytes < "$thumbnailPath$thumbnailExt")
				else
					tempFileSize=0
				fi
				#ALERT "[DEBUG]: file size $tempFileSize"
				if [ "$tempFileSize" -le 0 ];then
					ALERT "[ERROR]: Failed to find thumbnail inside nfo file!"
					addToLog "WARNING" "Generating Thumbnail from video file" "$movieVideoPath" "$logPagePath"
					# try to generate a thumbnail from video file
					#INFO "Attempting to create thumbnail from video source..."
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
						#INFO "[DEBUG]: tempTotalFrames = $tempTotalFrames'"
						#INFO "[DEBUG]: ffmpeg -y -ss $tempTimeCode -i '$movieVideoPath' -vframes 1 '$thumbnailPath.png'"
						#ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 "$thumbnailPath.png"
						# store the image inside a variable
						image=$(ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 -f singlejpeg - | convert -quiet - "$thumbnailPath.jpg" )
						# resize the image before checking the filesize
						convert -quiet "$thumbnailPath.jpg" -adaptive-resize 400x200\! "$thumbnailPath.jpg"
						# get the size of the file, after it has been created
						tempFileSize=$(wc --bytes < "$thumbnailPath.jpg")
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
							linkFile "$thumbnailPath.jpg" "$thumbnailPathKodi.jpg"
						elif [ $tempFileSize -eq 0 ];then
							# break the loop, no thumbnail could be generated at all
							# - Blank white or black space takes up more than 0 bytes
							# - A webpage generated thumbnail will be created as a alternative
							rm "$thumbnailPath.jpg"
							tempFileSize=16000
						fi
					done
				fi
			fi
		fi
		if test -f "$thumbnailPath-web.png";then
			# convert the thumbnail into a web thumbnail
			convert -quiet "$thumbnailPath$thumbnailExt" -adaptive-resize "300x200" "$thumbnailPath-web.png"
		fi
		set +x
		#TODO: here is where .strm files need checked for Plugin: eg. youtube strm files
		if echo "$videoPath" | grep -q --ignore-case "plugin://";then
			# change the video path into a video id to make it embedable
			#yt_id=${videoPath//plugin:\/\/plugin.video.youtube\/play\/?video_id=}
			#yt_id=$(echo "$videoPath" | sed "s/^.*\?video_id=//g")
			#yt_id=${videoPath//^.*\?video_id\=/}
			yt_id=${videoPath//*video_id=}
			#INFO "yt-id = $yt_id"
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
		elif echo "$videoPath" | grep -q "http";then
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$movieWebPath-poster$thumbnailExt' controls preload>"
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
					echo "<a class='button hardLink vlcButton' href='vlc://$(hostname)/movies/$movieWebPath/$movieWebPath$sufix'>"
					echo "<span id='vlcIcon'>&#9650;</span> VLC"
					echo "</a>"
				fi
				echo "$moviePlot"
				echo "</div>"
			} >> "$moviePagePath"
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType id='nfoMediaPlayer' poster='$movieWebPath-poster$thumbnailExt' controls preload>"
				echo "<source src='$movieWebPath$sufix' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<div class='descriptionCard'>"
				# create a hard link
				echo "<a class='button hardLink' href='$movieWebPath$sufix'>"
				echo "Hard Link"
				echo "</a>"
				echo "<a class='button hardLink vlcButton' href='vlc://$(hostname)/movies/$movieWebPath/$movieWebPath$sufix'>"
				echo "<span id='vlcIcon'>&#9650;</span> VLC"
				echo "</a>"
				echo "$moviePlot"
				echo "</div>"
			} >> "$moviePagePath"
		fi
		{
			# add footer
			echo "<?PHP";
			echo "include('../../header.php')";
			echo "?>";
			echo "</body>"
			echo "</html>"
		} >> "$moviePagePath"
		################################################################################
		# add the movie to the movie index page
		################################################################################
		{
			echo "<a class='indexSeries' href='/movies/$movieWebPath'>"
			echo "	<img loading='lazy' src='/movies/$movieWebPath/$movieWebPath-poster-web.png'>"
			echo "	<div class='title'>"
			echo "		$movieTitle"
			echo "		<br>"
			echo "		($movieYear)"
			echo "	</div>"
			echo "</a>"
		} > "$webDirectory/movies/$movieWebPath/movies.index"
		# create the movie entry in new movies
		{
			echo "<a class='indexSeries' href='/movies/$movieWebPath'>"
			echo "	<img loading='lazy' src='/movies/$movieWebPath/$movieWebPath-poster-web.png'>"
			echo "	<div class='title'>"
			echo "		$movieTitle"
			echo "		<br>"
			echo "		($movieYear)"
			echo "	</div>"
			echo "</a>"
		} > "$webDirectory/new/movie_$movieWebPath.index"
	else
		ALERT "[WARNING]: The file '$moviePath' could not be found!"
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
	if test -f "$thumbnailPath.jpg";then
		thumbnailExt=".jpg"
	elif test -f "$thumbnailPath.png";then
		thumbnailExt=".png"
	else
		return 1
	fi
	# return the thumbnail extension
	echo "$thumbnailExt"
	return 0
}
########################################################################
downloadThumbnail(){
	thumbnailLink=$1
	thumbnailPath=$2
	thumbnailExt=$3
	# if the link has already been downloaded then dont download it
	if ! test -f "$thumbnailPath$thumbnailExt";then
		# if it dont exist download it
		curl --silent "$thumbnailLink" | convert -quiet - "$thumbnailPath$thumbnailExt"
	fi
}
########################################################################
checkForThumbnail(){
	#checkForThumbnail $episode
	thumbnail=$1
	thumbnailPath=$2
	thumbnailPathKodi=$3
	########################################################################
	#INFO "new thumbnail path = $thumbnailPath"
	# check for a local thumbnail
	if test -f "$thumbnailPath.jpg";then
		thumbnailExt=".jpg"
		#INFO "Thumbnail already linked..."
	elif test -f "$thumbnailPath.png";then
		thumbnailExt=".png"
		#INFO "Thumbnail already linked..."
	else
		# no thumbnail has been linked or downloaded
		if test -f "$thumbnail.png";then
			#INFO "found PNG thumbnail..."
			thumbnailExt=".png"
			# link thumbnail into output directory
			linkFile "$thumbnail.png" "$thumbnailPath.png"
			linkFile "$thumbnail.png" "$thumbnailPathKodi.png"
		elif test -f "$thumbnail.jpg";then
			#INFO "found JPG thumbnail..."
			thumbnailExt=".jpg"
			# link thumbnail into output directory
			linkFile "$thumbnail.jpg" "$thumbnailPath.jpg"
			linkFile "$thumbnail.jpg" "$thumbnailPathKodi.jpg"
		else
			if echo "$nfoInfo" | grep -q "thumb";then
				thumbnailLink=$(ripXmlTag "$nfoInfo" "thumb")
				#INFO "Try to download episode thumbnail..."
				#INFO "Thumbnail found at $thumbnailLink"
				addToLog "DOWNLOAD" "Downloading Thumbnail" "Creating thumbnail from link '$thumbnailLink'" "$logPagePath"
				thumbnailExt=".png"
				# download the thumbnail
				downloadThumbnail "$thumbnailLink" "$thumbnailPath" "$thumbnailExt"
				#curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
				#if ! test -f "$thumbnailPath$thumbnailExt";then
				#	curl "$thumbnailLink" | convert - "$thumbnailPath$thumbnailExt"
				#fi
				# link the downloaded thumbnail
				linkFile "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
			fi
			touch "$thumbnailPath$thumbnailExt"
			# check if the thumb download failed
			tempFileSize=$(wc --bytes < "$thumbnailPath$thumbnailExt")
			#INFO "[DEBUG]: file size $tempFileSize"
			if [ "$tempFileSize" -eq 0 ];then
				addToLog "DOWNLOAD" "Generating Thumbnail" "$videoPath" "$logPagePath"
				ALERT "[ERROR]: Failed to find thumbnail inside nfo file!"
				# try to generate a thumbnail from video file
				#INFO "Attempting to create thumbnail from video source..."
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
					#INFO "[DEBUG]: tempTotalFrames = $tempTotalFrames'"
					#ALERT "[DEBUG]: ffmpeg -y -ss $tempTimeCode -i '$episodeVideoPath' -vframes 1 '$thumbnailPath.png'"
					#ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 "$thumbnailPath.png"
					# store the image inside a variable
					image=$(ffmpeg -y -ss $tempTimeCode -i "$episodeVideoPath" -vframes 1 -f singlejpeg - | convert -quiet - "$thumbnailPath.png" )
					# resize the image before checking the filesize
					convert -quiet "$thumbnailPath.jpg" -adaptive-resize 400x200\! "$thumbnailPath.jpg"
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
						linkFile "$thumbnailPath.png" "$thumbnailPathKodi.png"
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
	# episode is the path to the episode nfo file
	episode="$1"
	episodeShowTitle="$2"
	showPagePath="$3"
	webDirectory="$4"
	# create log path
	#logPagePath="$webDirectory/log.php"
	logPagePath="$webDirectory/log.php"
	showLogPath="$webDirectory/shows/$episodeShowTitle/log.index"
	#INFO "checking if episode path exists $episode"
	# check the episode file path exists before anything is done
	if [ -f "$episode" ];then
		#INFO "Processing Episode $episode"
		# for each episode build a page for the episode
		nfoInfo=$(cat "$episode")
		# rip the episode title
		#INFO "Episode show title = '$episodeShowTitle'"
		episodeShowTitle=$(cleanText "$episodeShowTitle")
		#INFO "Episode show title after clean = '$episodeShowTitle'"
		episodeTitle=$(cleanXml "$nfoInfo" "title")
		#INFO "Episode title = '$episodeShowTitle'"
		#episodePlot=$(ripXmlTag "$nfoInfo" "plot" | txt2html --extract -p 10)
		#episodePlot=$(ripXmlTag "$nfoInfo" "plot" | recode ..php | txt2html --eight_bit_clean --extract -p 10 )
		#episodePlot=$(ripXmlTag "$nfoInfo" "plot" | recode ..php | txt2html -ec --eight_bit_clean --extract -p 10 )
		episodePlot=$(ripXmlTagMultiLine "$nfoInfo" "plot")
		#INFO "episode plot = '$episodePlot'"
		#episodePlot=$(echo "$episodePlot" | inline-detox -s "utf_8-only")
		#episodePlot=$(echo "$episodePlot" | sed "s/_/ /g")
		#INFO "episode plot = '$episodePlot'"
		episodePlot=$(echo "$episodePlot" | txt2html --extract )
		#INFO "episode plot = '$episodePlot'"
		episodeSeason=$(cleanXml "$nfoInfo" "season")
		#INFO "Episode season = '$episodeSeason'"
		episodeAired=$(ripXmlTag "$nfoInfo" "aired")
		#INFO "Episode air date = '$episodeAired'"
		if [ "$episodeSeason" -lt 10 ];then
			if ! echo "$episodeSeason"| grep "^0";then
				# add a zero to make it format correctly
				episodeSeason="0$episodeSeason"
			fi
		fi
		episodeSeasonPath="Season $episodeSeason"
		#INFO "Episode season path = '$episodeSeasonPath'"
		episodeNumber=$(cleanXml "$nfoInfo" "episode")
		if [ "$episodeNumber" -lt 10 ];then
			if ! echo "$episodeNumber"| grep -q "^0";then
				# add a zero to make it format correctly
				episodeNumber="0$episodeNumber"
			fi
		fi
		#INFO "Episode number = '$episodeNumber'"

		INFO "Updating $episodeShowTitle s${episodeSeason}e${episodeNumber} aired $episodeAired"

		# create the episode page path
		# each episode file title must be made so that it can be read more easily by kodi
		episodePath="${showTitle} - s${episodeSeason}e${episodeNumber} - $episodeTitle"
		episodePagePath="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.php"
		# check the episode has not already been processed
		if test -f "$episodePagePath";then
			# if this episode has already been processed by the system then skip processeing it with this function
			# - this also prevents caching below done for new cacheable videos
			return
		fi
		# with  the check out of the way write the episode data gathered for website php
		if test -d "$webDirectory/shows/$episodeShowTitle/data/";then
			mkdir "$webDirectory/shows/$episodeShowTitle/data/"
		fi
		if test -f "$webDirectory/shows/$episodeShowTitle/data/showTitle.index";then
			echo "$episodeShowTitle" > "$webDirectory/shows/$episodeShowTitle/data/showTitle.index"
		fi
		if test -f "$webDirectory/shows/$episodeShowTitle/data/$episodeSeason-$episodeNumber-title.index";then
			echo "$episodeTitle" > "$webDirectory/shows/$episodeShowTitle/data/$episodeSeason-$episodeNumber-title.index"
		fi
		if test -f "$webDirectory/shows/$episodeShowTitle/data/$episodeSeason-$episodeNumber-plot.index";then
			echo "$episodePlot" > "$webDirectory/shows/$episodeShowTitle/data/$episodeSeason-$episodeNumber-plot.index"
		fi
		#INFO "Episode page path = '$episodePagePath'"
		#INFO "Making season directory at '$webDirectory/$episodeShowTitle/$episodeSeasonPath/'"
		mkdir -p "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/"
		chown -R www-data:www-data "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/"
		mkdir -p "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"
		chown -R www-data:www-data "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"
		# link stylesheet
		#linkFile "$webDirectory/style.css" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/style.css"
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
			ALERT "[ERROR]: could not find video file"
			addToLog "ERROR" "No video file" "$episode" "$logPagePath"
			# exit the function  cancel building the episode
			return
		fi
		# set the video type based on the found video path
		if echo "$videoPath" | grep -q --ignore-case ".mp3";then
			mediaType="audio"
			mimeType="audio/mp3"
		elif echo "$videoPath" | grep -q --ignore-case ".ogg";then
			mediaType="audio"
			mimeType="audio/ogg"
		elif echo "$videoPath" | grep -q --ignore-case ".ogv";then
			mediaType="video"
			mimeType="video/ogv"
		elif echo "$videoPath" | grep -q --ignore-case ".mp4";then
			mediaType="video"
			mimeType="video/mp4"
		elif echo "$videoPath" | grep -q --ignore-case ".m4v";then
			mediaType="video"
			mimeType="video/m4v"
		elif echo "$videoPath" | grep -q --ignore-case ".mpeg";then
			mediaType="video"
			mimeType="video/mpeg"
		elif echo "$videoPath" | grep -q --ignore-case ".mpg";then
			mediaType="video"
			mimeType="video/mpg"
		elif echo "$videoPath" | grep -q --ignore-case ".avi";then
			mediaType="video"
			mimeType="video/avi"
		elif echo "$videoPath" | grep -q --ignore-case ".mkv";then
			mediaType="video"
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		# find the fanart for the episode background
		if test -f "$webDirectory/shows/$episodeShowTitle/fanart.png";then
			#tempStyle="html{ background-image: url('../fanart.png') }"
			tempStyle="--backgroundFanart: url(\"../fanart.png\");"
			#tempStyle="root:{ --backgroundFanart: url('../fanart.png');"
		elif test -f "$webDirectory/shows/$episodeShowTitle/fanart.jpg";then
			#tempStyle="root:{ --backgroundFanart: url('../fanart.jpg');"
			tempStyle="--backgroundFanart: url(\"../fanart.jpg\");"
			#tempStyle="html{ background-image: url('../fanart.jpg') }"
		fi
		if test -f "$webDirectory/shows/$episodeShowTitle/poster.png";then
			#tempStyle="$tempStyle --backgroundPoster: url('../poster.png')}"
			tempStyle="$tempStyle --backgroundPoster: url(\"../poster.png\")"
		elif test -f "$webDirectory/shows/$episodeShowTitle/poster.jpg";then
			#tempStyle="$tempStyle --backgroundPoster: url('../poster.jpg')}"
			tempStyle="$tempStyle --backgroundPoster: url(\"../poster.jpg\")"
		fi
		# start rendering the html
		{
			# the style variable must be set inline, not in head, this may be a bug in firefox
			#echo "<html id='top' class='seriesBackground' style='$tempStyle'>"
			echo "<html id='top' class='seriesBackground'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='/style.css' />"
			echo "<style>"
			#add the fanart
			#echo "$tempStyle"
			echo "</style>"
			echo "<link rel='icon' type='image/png' href='/favicon.png'>"
			echo "</head>"
			echo "<body>"
			echo "<?PHP";
			echo "include('../../../header.php')";
			echo "?>";
			echo "<div class='titleCard'>"
			echo "<h1><a href='/shows/$episodeShowTitle/#Season ${episodeSeason}'>$episodeShowTitle</a> ${episodeSeason}x${episodeNumber}</h1>"
			echo "</div>"
		} > "$episodePagePath"
		# link the episode nfo file
		#INFO "linking $episode to $webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		linkFile "$episode" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		linkFile "$episode" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		# show info gathered
		#INFO "mediaType = $mediaType"
		#INFO "mimeType = $mimeType"
		#INFO "videoPath = $videoPath"
		episodeVideoPath="${episode//.nfo/$sufix}"
		#INFO "episodeVideoPath = $videoPath"

		# check for plugin links and convert the .strm plugin links into ytdl-resolver.php links
		if echo "$sufix" | grep -q --ignore-case "strm";then
			tempPath="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"

			# change the video path into a video id to make it embedable
			yt_id=${videoPath//*video_id=}
			#INFO "yt-id = $yt_id"
			ytLink="https://youtube.com/watch?v=$yt_id"

			# generate a link to the local caching resolver
			# - cache new links in batch processing mode
			resolverUrl="http://$(hostname).local/ytdl-resolver.php?url=\"$ytLink\""

			# if the config option is set to cache new episodes
			if [ "$(cat /etc/2web/cacheNewEpisodes.cfg)" == "yes" ] ;then
				addToLog "DEBUG" "Checking episode for caching" "$showTitle - $episodePath" "$logPagePath"
				# split up airdate data to check if caching should be done
				airedYear=$(echo "$episodeAired" | cut -d'-' -f1)
				airedMonth=$(echo "$episodeAired" | cut -d'-' -f2)
				#ALERT "[DEBUG]: Checking if file was released in the last month"
				#ALERT "[DEBUG]: aired year $airedYear == current year $(date +"%Y")"
				#ALERT "[DEBUG]: aired month $airedMonth == current month $(date +"%m")"
				# if the airdate was this year
				if [ $((10#$airedYear)) -eq "$((10#$(date +"%Y")))" ];then
					addToLog "DEBUG" "Episode matches year" "$showTitle - $episodePath\n $((10#$airedYear)) ?= $((10#$(date +"%Y")))" "$logPagePath"
					# if the airdate was this month
					if [ $((10#$airedMonth)) -eq "$((10#$(date +"%m")))" ];then
						addToLog "DEBUG" "Episode matches month" "$showTitle - $episodePath\n $((10#$airedMonth)) ?= $((10#$(date +"%m")))" "$logPagePath"
						addToLog "DOWNLOAD" "Caching new episode" "$showTitle - $episodePath" "$logPagePath"
						# cache the video if it is from this month
						# - only newly created videos get this far into the process to be cached
						#ALERT "[DEBUG]:  Caching episode '$episodeTitle'"
						#tempSum=$(echo -n "$ytLink" | tr -d '"' | tr -d "'" | md5sum | cut -d' ' -f1)
						tempSum=$(echo -n "\"$ytLink\"" | md5sum | cut -d' ' -f1)
						mkdir "$webDirectory/RESOLVER-CACHE/$tempSum/"
						echo "Video link cached with nfo2web" > "$webDirectory/RESOLVER-CACHE/$tempSum/data_nfo.log"
						echo "Orignal Link = '$videoPath'" >> "$webDirectory/RESOLVER-CACHE/$tempSum/data_nfo.log"
						echo "Youtube Link = '$ytLink'" >> "$webDirectory/RESOLVER-CACHE/$tempSum/data_nfo.log"
						echo "MD5 Source = '$ytLink'" >> "$webDirectory/RESOLVER-CACHE/$tempSum/data_nfo.log"
						echo "MD5 Sum = '$tempSum'" >> "$webDirectory/RESOLVER-CACHE/$tempSum/data_nfo.log"
						chown -R www-data:www-data "$webDirectory/RESOLVER-CACHE/$tempSum/"
						#timeout 20 curl --silent "$resolverUrl&batch=true" > /dev/null
						#'/usr/bin/nohup /usr/bin/sem --retries 10 --jobs 3 --id downloadQueue ';
						#/usr/bin/sem --retries 10 --jobs 1 --id downloadQueue /usr/local/bin/youtube-dl --max-filesize '6g' --retries 'infinite' --no-mtime --fragment-retries 'infinite' --embed-subs --embed-thumbnail --recode-video mp4 --continue --write-info-json -f 'best' -o "$webDirectory/RESOLVER-CACHE/$tempSum/$tempSum.mp4" -c "$ytLink"
						#echo "/usr/bin/nohup /usr/bin/sem --retries 10 --jobs 1 --id downloadQueue /usr/local/bin/youtube-dl --max-filesize '6g' --retries 'infinite' --no-mtime --fragment-retries 'infinite' --embed-subs --embed-thumbnail --recode-video mp4 --continue --write-info-json -f 'best' -o '$webDirectory/RESOLVER-CACHE/$tempSum/$tempSum.mp4' -c '$ytLink'" | batch
						#echo "/usr/bin/sem --retries 10 --jobs 1 --id downloadQueue /usr/local/bin/youtube-dl --max-filesize '6g' --retries 'infinite' --no-mtime --fragment-retries 'infinite' --embed-subs --embed-thumbnail --recode-video mp4 --continue --write-info-json -f 'best' -o '$webDirectory/RESOLVER-CACHE/$tempSum/$tempSum.mp4' -c '$ytLink'" | batch
						echo "/usr/bin/sem --retries 10 --jobs 1 --id downloadQueue /usr/local/bin/youtube-dl --max-filesize '6g' --retries 'infinite' --no-mtime --fragment-retries 'infinite' --embed-subs --embed-thumbnail --recode-video mp4 --continue --write-info-json -f 'best' -o '$webDirectory/RESOLVER-CACHE/$tempSum/$tempSum.mp4' -c '$ytLink'" | at -q b -M 'now'
						#timeout 20 curl --silent "$resolverUrl" > /dev/null
						chown -R www-data:www-data "$webDirectory/RESOLVER-CACHE/$tempSum/"
					fi
				fi
			fi
			#if [ "$episodeAired" == "$(date +"%Y-%m-%d")" ];then
			#	echo "[INFO]: airdate $episodeAired == todays date $(date +'%Y-%m-%d') ]"
			#	# if the episode aired today cache the episode
			#	# - timeout will stop the download after 0.1 seconds
			#	timeout 0.1 curl "$resolverUrl" > /dev/null
			#fi
			#INFO "building resolver url for plugin link..."
			echo "$resolverUrl" > "$tempPath"
		else
			# link the video from the libary to the generated website
			#INFO "linking '$episodeVideoPath' to '$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix'"
			linkFile "$episodeVideoPath" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
			linkFile "$episodeVideoPath" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
		fi
		# remove .nfo extension and create thumbnail path
		thumbnail="${episode//.nfo}-thumb"
		#INFO "thumbnail template = $thumbnail"
		#INFO "thumbnail path 1 = $thumbnail.png"
		#INFO "thumbnail path 2 = $thumbnail.jpg"
		thumbnailPath="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		thumbnailPathKodi="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		# check for the thumbnail and link it
		checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi"
		# get the extension
		thumbnailExt=$(getThumbnailExt "$thumbnailPath")
		# convert the found episode thumbnail into a web thumb
		#INFO "building episode thumbnail: convert \"$thumbnailPath$thumbnailExt\" -resize \"200x100\" \"$thumbnailPath-web.png\""
		if ! test -f "$thumbnailPath-web.png";then
			convert -quiet "$thumbnailPath$thumbnailExt" -resize "300x200" "$thumbnailPath-web.png"
		fi
		#TODO: here is where .strm files need checked for Plugin: eg. youtube strm files
		if echo "$videoPath" | grep -q --ignore-case "plugin://";then
			# change the video path into a video id to make it embedable
			yt_id=${videoPath//*video_id=}
			#INFO "yt-id = $yt_id"
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
			#fullRedirect="http://$(hostname).local:444/ytdl-resolver.php?url=\"$ytLink\"&webplayer=true"
			cacheRedirect="http://$(hostname).local/ytdl-resolver.php?url=\"$ytLink\""
			fullRedirect="/ytdl-resolver.php?url=\"$ytLink\""
			{
				echo "<div class='descriptionCard'>"
				echo "<h2>$episodeTitle</h2>"
				# create a hard link
				echo "<a class='button hardLink' href='$ytLink'>"
				echo "	Hard Link"
				echo "</a>"

				echo "<a class='button hardLink' href='$cacheRedirect'>"
				echo "	Cache Link"
				echo "</a>"

				echo "<a class='button hardLink vlcButton' href='vlc://$cacheRedirect'>"
				echo "<span id='vlcIcon'>&#9650;</span> VLC"
				echo "</a>"

				echo "<div class='aired'>"
				echo "$episodeAired"
				echo "</div>"
				echo "$episodePlot"
				echo "</div>"
			} >> "$episodePagePath"
		#elif echo "$videoPath" | grep "http";then
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType id='nfoMediaPlayer' poster='$episodePath-thumb$thumbnailExt' controls preload>"
				# redirect mkv files to the transcoder to cache the video file for the webplayer
				if echo "$videoPath" | grep -qE ".mkv|.avi";then
					fullRedirect="/transcode.php?link=\"$videoPath\""
					#echo "<source src='$fullRedirect' type='video/mp4'>"
					# TODO: works but needs to be toggleable, above is correct code for the transcode.php script
					echo "<source src='$videoPath' type='$mimeType'>"
				else
					echo "<source src='$videoPath' type='$mimeType'>"
				fi
				#echo "<source src='$videoPath' type='$mimeType'>"
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
				echo "<a class='button hardLink vlcButton' href='vlc://$(hostname)/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix'>"
				echo "<span id='vlcIcon'>&#9650;</span> VLC"
				echo "</a>"

				echo "<div class='aired'>"
				echo "$episodeAired"
				echo "</div>"
				echo "$episodePlot"
				echo "</div>"
			} >> "$episodePagePath"
		#else
		#	{
		#		# build the html5 media player for local and remotly accessable media
		#		echo "<$mediaType id='nfoMediaPlayer' poster='$episodePath-thumb$thumbnailExt' controls preload>"
		#		echo "<source src='$episodePath$sufix' type='$mimeType'>"
		#		echo "</$mediaType>"
		#		echo "<div class='descriptionCard'>"
		#		echo "<h2>$episodeTitle</h2>"
		#		# create a hard link
		#		echo "<a class='button hardLink' href='$episodePath$sufix'>"
		#		echo "Hard Link"
		#		echo "</a>"
		#		echo "<div class='aired'>"
		#		echo "$episodeAired"
		#		echo "</div>"
		#		echo "$episodePlot"
		#		echo "</div>"
		#	} >> "$episodePagePath"
		fi
		{
			# add footer
			echo "<?PHP";
			echo "include('../../../header.php')";
			echo "?>";
			echo "</body>"
			echo "</html>"
		} >> "$episodePagePath"
		################################################################################
		# add the episode to the show page
		################################################################################
		tempEpisodeSeasonThumb="<a class='showPageEpisode' href='/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.php'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<img loading='lazy' src='/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb-web.png'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<h3 class='title'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<div class='showIndexNumbers'>${episodeSeason}x${episodeNumber}</div>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n		$episodeTitle"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	</h3>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n</a>"

		if [ "$episodeNumber" -eq 1 ];then
			echo -ne "$tempEpisodeSeasonThumb" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		else
			echo -ne "$tempEpisodeSeasonThumb" >> "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		fi

		echo -ne "$tempEpisodeSeasonThumb" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.index"
		# create the episode file in the new index
		{
			echo -ne "<a class='showPageEpisode' href='/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.php'>"
			echo -ne "\n	<h3 class='title'>"
			echo -ne "\n  $episodeShowTitle"
			echo -ne "\n	</h3>"
			echo -ne "\n	<img loading='lazy' src='/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb-web.png'>"
			echo -ne "\n	<h3 class='title'>"
			echo -ne "\n	<div class='showIndexNumbers'>${episodeSeason}x${episodeNumber}</div>"
			echo -ne "\n		$episodeTitle"
			echo -ne "\n	</h3>"
			echo -ne "\n</a>"
		} > "$webDirectory/new/episode_$episodePath.index"
	else
		ALERT "[WARNING]: The file '$episode' could not be found!"
	fi
}
################################################################################
processShow(){
	#processShow "$show" "$showMeta" "$showTitle" "$webDirectory"
	show=$1
	showMeta=$2
	showTitle=$3
	webDirectory=$4
	logPagePath="$webDirectory/log.php"
	showLogPath="$webDirectory/shows/$showTitle/log.index"
	# create the path sum for reconizing the libary path
	pathSum=$(echo -n "$show" | md5sum | cut -d' ' -f1)
	# create directory
	INFO "creating show directory at '$webDirectory/$showTitle/'"
	mkdir -p "$webDirectory/shows/$showTitle/"
	chown -R www-data:www-data "$webDirectory/shows/$showTitle"
	# link stylesheet
	linkFile "$webDirectory/style.css" "$webDirectory/shows/$showTitle/style.css"
	# check show state before processing
	if [ -f "$webDirectory/shows/$showTitle/state_$pathSum.cfg" ];then
		# a existing state was found
		currentSum=$(cat "$webDirectory/shows/$showTitle/state_$pathSum.cfg")
		libarySum=$(getDirSum "$show")
		updateInfo="$showTitle\n$currentSum != $libarySum\n$(ls "$show")\n$show"
		# if the current state is the same as the state of the last update
		if [ "$libarySum" == "$currentSum" ];then
			# this means they are the same so no update needs run
			#INFO "State is unchanged for $showTitle, no update is needed."
			#INFO "[DEBUG]: $currentSum == $libarySum"
			addToLog "INFO" "Show unchanged" "$showTitle" "$logPagePath"
			return
		else
			#INFO "States are diffrent, updating $showTitle..."
			#INFO "[DEBUG]: $currentSum != $libarySum"
			# clear the show log for the newly changed show state
			echo "" > "$showLogPath"
			addToLog "UPDATE" "Updating Show" "$updateInfo" "$logPagePath"
			# update the show directory modification date when the state has been changed
			touch "$webDirectory/shows/$showTitle/"
		fi
	else
		#INFO "No show state exists for $showTitle, updating..."
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
	linkFile "$show/tvshow.nfo" "$webDirectory/shows/$showTitle/tvshow.nfo"
	linkFile "$show/tvshow.nfo" "$webDirectory/kodi/shows/$showTitle/tvshow.nfo"
	# link all images to the kodi path
	if ls "$show" | grep -q "\.jpg" ;then
		linkFile "$show"/*.jpg "$webDirectory/kodi/shows/$showTitle/"
		linkFile "$show"/*.jpg "$webDirectory/shows/$showTitle/"
	fi
	if ls "$show" | grep -q "\.png" ;then
		linkFile "$show"/*.png "$webDirectory/kodi/shows/$showTitle/"
		linkFile "$show"/*.png "$webDirectory/shows/$showTitle/"
	fi
	# link the poster
	if [ -f "$show/poster.png" ];then
		posterPath="poster.png"
		#INFO "Found $show/$posterPath"
		linkFile "$show/$posterPath" "$webDirectory/shows/$showTitle/$posterPath"
		linkFile "$show/$posterPath" "$webDirectory/kodi/shows/$showTitle/$posterPath"
		# create the web thumbnails
		if ! test -f "$webDirectory/shows/$showTitle/poster-web.png";then
			convert -quiet "$show/$posterPath" -resize "300x200" "$webDirectory/shows/$showTitle/poster-web.png"
		fi
	elif [ -f "$show/poster.jpg" ];then
		posterPath="poster.jpg"
		#INFO "Found $show/$posterPath"
		linkFile "$show/$posterPath" "$webDirectory/shows/$showTitle/$posterPath"
		linkFile "$show/$posterPath" "$webDirectory/kodi/shows/$showTitle/$posterPath"
		# create the web thumbnails
		if ! test -f "$webDirectory/shows/$showTitle/poster-web.png";then
			convert -quiet "$show/$posterPath" -resize "300x200" "$webDirectory/shows/$showTitle/poster-web.png"
		fi
	else
		addToLog "WARNING" "Could not find poster.[png/jpg]" "$showTitle has no $show/poster.[png/jpg]" "$logPagePath"
	fi
	# link the fanart
	if test -f "$show/fanart.png";then
		#INFO "Found $show/fanart.png"
		fanartPath="fanart.png"
		#INFO "Found $show/$fanartPath"
		linkFile "$show/$fanartPath" "$webDirectory/shows/$showTitle/$fanartPath"
		linkFile "$show/$fanartPath" "$webDirectory/kodi/shows/$showTitle/$fanartPath"
	elif test -f "$show/fanart.jpg";then
		fanartPath="fanart.jpg"
		#INFO "Found $show/$fanartPath"
		linkFile "$show/$fanartPath" "$webDirectory/shows/$showTitle/$fanartPath"
		linkFile "$show/$fanartPath" "$webDirectory/kodi/shows/$showTitle/$fanartPath"
	else
		addToLog "WARNING" "Could not find fanart.[png/jpg]" "$showTitle has no $show/fanart.[png/jpg]" "$logPagePath"
	fi
	# building the webpage for the show
	showPagePath="$webDirectory/shows/$showTitle/index.php"
	#INFO "Creating directory at = '$webDirectory/shows/$showTitle/'"
	if ! test -d "$webDirectory/shows/$showTitle/";then
		mkdir -p "$webDirectory/shows/$showTitle/"
	fi
	#INFO "Creating showPagePath = $showPagePath"
	#touch "$showPagePath"
	################################################################################
	# begin building the html of the page
	################################################################################
	# generate the episodes based on .nfo files
	#find "$show/" -type 'd' -maxdepth 1 -mindepth 1 | while read -r season;do
	for season in "$show"/*;do
		#INFO "checking for season folder at '$season'"
		if test -d "$season";then
			#INFO "found season folder at '$season'"
			# generate the season name from the path
			seasonName=$(echo "$season" | rev | cut -d'/' -f1 | rev)
			seasonSum=$(echo -n "$season" | md5sum | cut -d' ' -f1)

			# get the season folder sum, youtube channels can have 2k + episodes a season
			if test -f "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg";then
				currentSeasonSum=$(cat "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg")
			else
				currentSeasonSum="0"
			fi
			libarySeasonSum=$(getDirSum "$season")
			#if echo "$libarySeasonSum" | grep -q "$currentSeasonSum";then
			#addToLog "INFO" "Season" "$libarySeasonSum ?= $currentSeasonSum" "$logPagePath"
			if [ "$libarySeasonSum" == "$currentSeasonSum" ];then
				# this season folder is unchanged ignore it
				#INFO "Season Unchanged $season"
				addToLog "INFO" "Season Unchanged" "$showTitle $seasonName\n$season" "$logPagePath"
			else
				# update the altered season files
				################################################################################
				addToLog "UPDATE" "Season is Updating" "$showTitle $seasonName\n$season" "$logPagePath"

				# if the folder is a directory that means a season has been found
				# read each episode in the series
				for episode in "$season"/*.nfo;do
					processEpisode "$episode" "$showTitle" "$showPagePath" "$webDirectory"
				done
				################################################################################
				headerPagePath="$webDirectory/header.php"

				# find the fanart for the episode background
				if test -f "$webDirectory/shows/$showTitle/fanart.png";then
					tempStyle="--backgroundFanart: url(\"fanart.png\");"
				elif test -f "$webDirectory/shows/$showTitle/fanart.jpg";then
					tempStyle="--backgroundFanart: url(\"fanart.jpg\");"
				fi
				if test -f "$webDirectory/shows/$showTitle/poster.png";then
					tempStyle="$tempStyle --backgroundPoster: url(\"poster.png\")"
				elif test -f "$webDirectory/shows/$showTitle/poster.jpg";then
					tempStyle="$tempStyle --backgroundPoster: url(\"poster.jpg\")"
				fi
				#tempStyle="html{ background-image: url(\"$fanartPath\") }"
				# build top of show webpage containing all of the shows meta info
				linkFile "/usr/share/2web/templates/seasons.php" "$showPagePath"

				# update the season sum file
				#getDirSum "$show" > "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg"

				# update the season sum file
				touch "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg"
				echo "$libarySeasonSum" > "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg"
			fi
		#else
		#	INFO "Season folder $season does not exist"
		fi
	done
	# add show page to the show index
	{
		echo "<a class='indexSeries' href='/shows/$showTitle/'>"
		echo "	<img loading='lazy' src='/shows/$showTitle/poster-web.png'>"
		#echo "  <marquee direction='up' scrolldelay='100'>"
		echo "	<div>"
		echo "		$showTitle"
		echo "	</div>"
		#echo "  </marquee>"
		echo "</a>"
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
########################################################################
getLibSum(){
	# find all state md5sums for shows and create a collective sum
	totalList=""
	while read -r line;do
		# read each line and load the file
		tempList=$(ls -R "$line")
		# add value to list
		totalList="$totalList$tempList"
	done < /etc/2web/nfo/libaries.cfg
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
################################################################################
linkFile(){
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
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

	INFO "Building home page..."
	# do not generate stats if website is in process of being updated
	# stats generation is IO intense, so it only needs ran ONCE at the end
	# if the stats.index cache is more than 1 day old update it
	if cacheCheck "$webDirectory/stats.index" "10";then
		# figure out the stats
		totalComics=$(find "$webDirectory"/comics/*/ -maxdepth 1 -mindepth 1 -name 'index.php' | wc -l)
		totalEpisodes=$(find "$webDirectory"/shows/*/*/ -name '*.nfo' | wc -l)
		totalShows=$(find "$webDirectory"/shows/*/ -name 'tvshow.nfo' | wc -l)
		totalMovies=$(find "$webDirectory"/movies/*/ -name '*.nfo' | wc -l)
		if test -f "$webDirectory/kodi/channels.m3u";then
			totalChannels=$(grep -c 'radio="false' "$webDirectory/kodi/channels.m3u" )
			totalRadio=$(grep -c 'radio="true' "$webDirectory/kodi/channels.m3u" )
		fi
		# count website size in total ignoring symlinks
		webSize=$(du -shP "$webDirectory" | cut -f1)
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/RESOLVER-CACHE/" | cut -f1)
		# count symlinks in kodi to get the total size of all media on all connected drives containing libs
		mediaSize=$(du -shL "$webDirectory/kodi/" | cut -f1)
		# count total freespace on all connected drives, ignore temp filesystems (snap packs)
		freeSpace=$(df -h -x "tmpfs" --total | grep "total" | tr -s ' ' | cut -d' ' -f4)
		#write a new stats index file
		{
			echo "<div class='date titleCard'>"
			echo "	<div>"
			echo "		Last updated on $(date)"
			echo "	</div>"
			echo "	<div>"
			if [ "$totalShows" -gt 0 ];then
				echo "		<span>"
				echo "			Episodes:$totalEpisodes"
				echo "		</span>"
				echo "		<span>"
				echo "			Shows:$totalShows"
				echo "		</span>"
			fi
			if [ "$totalMovies" -gt 0 ];then
				echo "		<span>"
				echo "			Movies:$totalMovies"
				echo "		</span>"
			fi
			if [ "$totalComics" -gt 0 ];then
				echo "		<span>"
				echo "			Comics:$totalComics"
				echo "		</span>"
			fi
			if test -f "$webDirectory/kodi/channels.m3u";then
				if [ "$totalChannels" -gt 0 ];then
					echo "		<span>"
					echo "			Channels:$totalChannels"
					echo "		</span>"
				fi
				if [ "$totalRadio" -gt 0 ];then
					echo "		<span>"
					echo "			Radio:$totalRadio"
					echo "		</span>"
				fi
			fi
			echo "		<span>"
			echo "			Web:$webSize "
			echo "		</span>"
			echo "		<span>"
			echo "			Cache:$cacheSize"
			echo "		</span>"
			echo "		<span>"
			echo "			Media:$mediaSize"
			echo "		</span>"
			echo "		<span>"
			echo "			Free:$freeSpace"
			echo "		</span>"
			echo "	</div>"
			echo "</div>"
		} > "$webDirectory/stats.index"
	fi
	# link homepage
	linkFile "/usr/share/2web/templates/home.php" "$webDirectory/index.php"
	# link lists
	linkFile "/usr/share/2web/templates/randomMovies.php" "$webDirectory/randomMovies.php"
	linkFile "/usr/share/2web/templates/randomShows.php" "$webDirectory/randomShows.php"
	linkFile "/usr/share/2web/templates/updatedShows.php" "$webDirectory/updatedShows.php"
	linkFile "/usr/share/2web/templates/updatedMovies.php" "$webDirectory/updatedMovies.php"
	linkFile "/usr/share/2web/templates/updatedEpisodes.php" "$webDirectory/updatedEpisodes.php"
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
	linkFile "/usr/share/2web/templates/shows.php" "$webDirectory/shows/index.php"
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
		find -L "comics/" -type f -name "thumb.png" >> "$webDirectory/fanart.cfg"
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
		find -L "comics/" -type f -name "thumb.png" >> "$webDirectory/poster.cfg"
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
	linkFile "/usr/share/2web/templates/movies.php" "$webDirectory/movies/index.php"
}
########################################################################
function cacheCheck(){

	filePath="$1"
	cacheDays="$2"

	fileName=$( echo "$filePath" | rev | cut -d'/' -f'1'| rev )
	fileDir=$( echo "$filePath" | rev | cut -d'/' -f'2-'| rev )

	# return true if cached needs updated
	if test -f "$filePath";then
		# check the file date
		fileFound=$(find "$fileDir" -type f -name "$fileName" -mtime "+$cacheDays" | wc -l)
		if [ "$fileFound" -gt 0 ] ;then
			# the file is more than "$cacheDays" days old, it needs updated
			#INFO "File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			#INFO "File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		#INFO "File does not exist, it must be created $1"
		return 0
	fi
}
########################################################################
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
function libaryPaths(){
	# add the download directory to the paths
	#echo "$(downloadDir)"
	# check for server libary config
	if [ ! -f /etc/2web/nfo/libaries.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			# add the default download directory
			echo "/var/cache/2web/libary/"
		} >> "/etc/2web/nfo/libaries.cfg"
	fi
	# write path to console
	cat "/etc/2web/nfo/libaries.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/nfo/libaries.d/" -mindepth 1 -maxdepth 1 -type f -name '*.cfg' | while read libaryConfigPath;do
		#grep -i "^#" "$libaryConfigPath"
		cat "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
########################################################################
function updateCerts(){
	genCert='no'
	# if the cert exists
	if test -f /var/cache/2web/ssl-cert.crt;then
		# if the certs are older than 364 days renew recreate a new valid key
		if [ $(find /var/cache/2web/ -mtime +364 -name '*.crt' | wc -l) -gt 0 ] ;then
			# the cert has expired
			echo "[INFO]: Updating cert..."
			# generate a new private key and public cert for the SSL certification
			genCert='yes'
		else
			echo "[INFO]: Cert still active..."
		fi
	else
		echo "[INFO]: Creating cert..."
		# if the cert does not exist
		genCert='yes'
	fi
	if [ $genCert == 'yes' ];then
		openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /var/cache/2web/ssl-private.key -out /var/cache/2web/ssl-cert.crt -config /etc/2web/certInfo.cnf
		# convert the ssl certificate into the der format
		# - der format can be copied to other systems at /usr/local/share/ca-certificates/
		# - linux only updates after update-ca-certificates
		openssl x509 -in /var/cache/2web/ssl-cert.crt -out /var/cache/2web/ssl-cert.der -outform DER
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
		# verbose removal of found files allows files to be visible as they are removed
		find "$(webRoot)/shows/" -type f -name 'state_*.cfg' -exec rm -v {} \;
		find "$(webRoot)/movies/" -type f -name 'state_*.cfg' -exec rm -v {} \;
		echo "[INFO]: Reseting web log for individual shows/movies..."
		find "$(webRoot)/movies/"  -mindepth 1 -type f -name 'log.index' -exec rm -v {} \;
		find "$(webRoot)/shows/"  -mindepth 2 -type f -name 'log.index' -exec rm -v {} \;
		# remove all individual episode files that lock rebuilding of the episode data
		echo "[INFO]: Reseting video player pages shows/movies..."
		find "$(webRoot)/movies/" -mindepth 1 -type f -name '*.php' -exec rm -v {} \;
		find "$(webRoot)/shows/" -mindepth 2  -type f -name '*.php' -exec rm -v {} \;
		# remove cached m3u files
		echo "[INFO]: Reseting m3u playlist cache..."
		find "$(webRoot)/m3u_cache/" -type f -name '*.m3u' -exec rm -v {} \;
		echo "[SUCCESS]: Web cache states reset, update to rebuild everything."
		echo "[SUCCESS]: Site will remain the same until updated."
		echo "[INFO]: Use 'nfo2web update' to generate a new website..."
	elif [ "$1" == "--certs" ] || [ "$1" == "certs" ] ;then
		echo "[INFO]: Checking for local SSL certs..."
		updateCerts
	elif [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		echo "[INFO]: Reseting web cache to blank..."
		rm -rv $(webRoot)/*
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
		# - index.php : contains links to health.php,recent.php,and each showTitle.php
		#   missing data. Links to each show can be found here.
		#  - health.php : Page contains a list of found issues with nfo libary
		#   + Use duplicate checker script to generate this page
		#  - recent.php : Contains links to all episodes added in the past 14 days
		#   + Use 'find /pathToLibary/ -type f -mtime -14' to find files less than 14
		#     days old
		#  - showTitle/index.php : Each show gets its own show page that contains links to
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
		if ! test -f /etc/2web/nfo/libaries.cfg;then
			mkdir -p /var/cache/2web/libary/
			echo "/var/cache/2web/libary" > /etc/2web/nfo/libaries.cfg
		fi
		#libaries=$(libaryPaths | tr -s "\n" | shuf )
		libaries=$(libaryPaths | tr -s "\n" | shuf )
		# the webdirectory is a cache where the generated website is stored
		webDirectory="$(webRoot)"
		ALERT "Building web directory at '$webDirectory'"
		# force overwrite symbolic link to web directory
		# - link must be used to also use premade apache settings
		ln -sfn "$webDirectory" "/var/cache/2web/web"
		# check if system is active
		if test -f "/tmp/nfo2web.active";then
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
		if ! test -d "$webDirectory/";then
			mkdir -p "$webDirectory"
			chown -R www-data:www-data "$webDirectory"
		fi
		if ! test -d "$webDirectory/new/";then
			mkdir -p "$webDirectory/new/"
			chown -R www-data:www-data "$webDirectory/new/"
		fi
		if ! test -d "$webDirectory/shows/";then
			mkdir -p "$webDirectory/shows/"
			chown -R www-data:www-data "$webDirectory/shows/"
		fi
		if ! test -d "$webDirectory/movies/";then
			mkdir -p "$webDirectory/movies/"
			chown -R www-data:www-data "$webDirectory/movies/"
		fi
		if ! test -d "$webDirectory/kodi/";then
			mkdir -p "$webDirectory/kodi/"
			chown -R www-data:www-data "$webDirectory/kodi/"
		fi
		if ! test -d "$webDirectory/settings/";then
			mkdir -p "$webDirectory/settings/"
			chown -R www-data:www-data "$webDirectory/settings/"
		fi
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
		# admin control file
		linkFile "/usr/share/2web/settings/admin.php" "$webDirectory/admin.php"
		# settings interface files
		linkFile "/usr/share/2web/settings/radio.php" "$webDirectory/radio.php"
		linkFile "/usr/share/2web/settings/tv.php" "$webDirectory/tv.php"
		linkFile "/usr/share/2web/settings/iptv_blocked.php" "$webDirectory/iptv_blocked.php"
		linkFile "/usr/share/2web/settings/nfo.php" "$webDirectory/nfo.php"
		linkFile "/usr/share/2web/settings/comics.php" "$webDirectory/comics.php"
		linkFile "/usr/share/2web/settings/comicsDL.php" "$webDirectory/comicsDL.php"
		linkFile "/usr/share/2web/settings/cache.php" "$webDirectory/cache.php"
		linkFile "/usr/share/2web/settings/system.php" "$webDirectory/system.php"
		linkFile "/usr/share/2web/settings/ytdl2nfo.php" "$webDirectory/ytdl2nfo.php"
		linkFile "/usr/share/2web/settings/settingsHeader.php" "$webDirectory/settingsHeader.php"
		linkFile "/usr/share/2web/settings/logout.php" "$webDirectory/logout.php"
		# help/info docs
		linkFile "/usr/share/2web/link.php" "$webDirectory/link.php"
		# caching resolvers
		linkFile "/usr/share/2web/ytdl-resolver.php" "$webDirectory/ytdl-resolver.php"
		linkFile "/usr/share/2web/m3u-gen.php" "$webDirectory/m3u-gen.php"
		# error documents
		linkFile "/usr/share/2web/404.php" "$webDirectory/404.php"
		linkFile "/usr/share/2web/403.php" "$webDirectory/403.php"
		linkFile "/usr/share/2web/401.php" "$webDirectory/401.php"
		# global javascript libary
		linkFile "/usr/share/2web/2web.js" "$webDirectory/2web.js"
		# link homepage
		linkFile "/usr/share/2web/templates/home.php" "$webDirectory/index.php"
		# link the movies and shows index
		linkFile "/usr/share/2web/templates/movies.php" "$webDirectory/movies/index.php"
		linkFile "/usr/share/2web/templates/shows.php" "$webDirectory/shows/index.php"
		# add the new index
		linkFile "/usr/share/2web/templates/new.php" "$webDirectory/new/index.php"
		# link lists these can be built and rebuilt during libary update
		linkFile "/usr/share/2web/templates/randomMovies.php" "$webDirectory/randomMovies.php"
		linkFile "/usr/share/2web/templates/randomShows.php" "$webDirectory/randomShows.php"
		linkFile "/usr/share/2web/templates/updatedShows.php" "$webDirectory/updatedShows.php"
		linkFile "/usr/share/2web/templates/updatedMovies.php" "$webDirectory/updatedMovies.php"
		linkFile "/usr/share/2web/templates/updatedEpisodes.php" "$webDirectory/updatedEpisodes.php"
		# copy over the favicon
		linkFile "/usr/share/2web/favicon_default.png" "$webDirectory/favicon.png"
		################################################################################
		# build the login users file
		counter=0
		if [ $(cat /etc/2web/users/*.cfg | wc -l ) -gt 0 ];then
			# if there are any users
			linkFile "/usr/share/2web/templates/_htaccess" "$webDirectory/.htaccess"
			cat /etc/2web/users/*.cfg > "/var/cache/2web/htpasswd.cfg"
		else
			# if there are no users set in the cfg remove the .htaccess file
			rm -v "$webDirectory/.htaccess"
		fi

		if ! test -d "$webDirectory/RESOLVER-CACHE/";then
			# build the cache directory if none exists
			mkdir -p "$webDirectory/RESOLVER-CACHE/"
			# set permissions
			chown www-data:www-data "$webDirectory/RESOLVER-CACHE/"
		fi
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

		# install the php streaming script
		#ln -s "/usr/share/2web/stream.php" "$webDirectory/stream.php"
		linkFile "/usr/share/2web/transcode.php" "$webDirectory/transcode.php"

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
			echo "default.css" > "/etc/2web/theme.cfg"
			chown www-data:www-data "/etc/2web/theme.cfg"
		fi
		# load the chosen theme
		theme=$(cat "/etc/2web/theme.cfg")
		# link the theme and overwrite if another theme is chosen
		ln -sf "/usr/share/2web/themes/$theme" "$webDirectory/style.css"
		# link stylesheet
		linkFile "$webDirectory/style.css" "$webDirectory/movies/style.css"
		linkFile "$webDirectory/style.css" "$webDirectory/shows/style.css"
		# create the log path
		logPagePath="$webDirectory/log.php"
		# create the homepage path
		#homePagePath="$webDirectory/index.php"
		headerPagePath="$webDirectory/header.php"
		showIndexPath="$webDirectory/shows/index.php"
		movieIndexPath="$webDirectory/movies/index.php"
		#touch "$showIndexPath"
		#touch "$movieIndexPath"
		touch "$logPagePath"
		#touch "$homePagePath"
		# check for the header
		linkFile "/usr/share/2web/templates/header.php" "$headerPagePath"
		# build log page
		{
			echo "<html id='top' class='randomFanart'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='/style.css' />"
			echo "<style>"
			echo "</style>"
			echo "<link rel='icon' type='image/png' href='/favicon.png'>"
			echo "<script src='/2web.js'></script>"
			echo "</head>"
			echo "<body>"
			echo "<?PHP";
			echo "include('header.php');";
			echo "include('settingsHeader.php');";
			echo "?>";
			echo "<div class='settingsListCard'>"
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
			echo "<div class='settingsTable'>"
			echo "<table>"
		} > "$logPagePath"
		addToLog "INFO" "Started Update" "$(date)" "$logPagePath"
		addToLog "INFO" "Libaries:" "$libaries" "$logPagePath"
		# read each libary from the libary config, single path per line
		ALERT "LIBARIES: $libaries";
		#for libary in $libaries;do
		echo "$libaries" | while read libary;do
			# check if the libary directory exists
			addToLog "INFO" "Checking library path" "$libary" "$logPagePath"
			#INFO "Check if directory exists at '$libary'"
			if test -d "$libary";then
				addToLog "UPDATE" "Starting library scan" "$libary" "$logPagePath"
				#INFO "library exists at '$libary'"
				# read each tvshow directory from the libary
				#for show in "$libary"/*;do
				find "$libary" -type 'd' -maxdepth 1 -mindepth 1 | shuf | while read -r show;do
					#INFO "show path = '$show'"
					################################################################################
					# process page metadata
					################################################################################
					# if the show directory contains a nfo file defining the show
					#INFO "searching for metadata at '$show/tvshow.nfo'"
					#if test -f "$show/tvshow.nfo";then
					if grep -q "<tvshow>" "$show"/*.nfo;then
						#INFO "found metadata at '$show/tvshow.nfo'"
						# load update the tvshow.nfo file and get the metadata required for
						showMeta=$(cat "$show/tvshow.nfo")
						showTitle=$(ripXmlTag "$showMeta" "title")
						#INFO "showTitle = '$showTitle'"
						showTitle=$(cleanText "$showTitle")
						#INFO "showTitle after cleanText() = '$showTitle'"
						if echo "$showMeta" | grep -q "<tvshow>";then
							# pipe the output to a black hole and cache
							episodeSearchResults=$(find "$show" -type f -maxdepth 2 -mindepth 2 -name '*.nfo' | wc -l)
							#episodeSearchResults=$(find "$show" -type f -name '*.nfo' | wc -l)
							#ls "$show"/*/*.nfo > /dev/null
							# make sure show has episodes
							if [ $episodeSearchResults -gt 0 ];then
								processShow "$show" "$showMeta" "$showTitle" "$webDirectory"
								# write log info from show to the log, this must be done here to keep ordering
								# of the log and to make log show even when the state of the show is unchanged
								#INFO "Adding logs from $webDirectory/shows/$showTitle/log.index to $logPagePath"
								#cat "$webDirectory/shows/$showTitle/log.index" >> "$webDirectory/log.php"

							else
								echo "[ERROR]: Show has no episodes!"
								addToLog "ERROR" "Show has no episodes" "No episodes found for '$showTitle' in '$show'\n\nTo remove this empty folder use below command.\n\nrm -rvi '$show'" "$logPagePath"
							fi
						else
							echo "[ERROR]: Show nfo file is invalid!"
							addToLog "ERROR" "Show NFO Invalid" "$show/tvshow.nfo" "$logPagePath"
						fi
					elif grep -q "<movie>" "$show"/*.nfo;then
						# this is a move directory not a show
						processMovie "$show" "$webDirectory"
					fi
				done
			fi
			# update random backgrounds
			scanForRandomBackgrounds "$webDirectory"
		done
		# add the end to the log, add the jump to top button and finish out the html
		addToLog "INFO" "FINISHED" "$(date)" "$logPagePath"
		{
			echo "</table>"
			echo "</div>"
			echo "</div>"
			# add footer
			echo "<?PHP";
			echo "include('header.php')";
			echo "?>";
			# create top jump button
			echo "<a href='#' id='topButton' class='button'>&uarr;</a>"
			echo "<hr class='topButtonSpace'>"
			echo "</body>"
			echo "</html>"
		} >> "$logPagePath"
		# create the final index pages, these should not have the progress indicator
		# build the final version of the homepage without the progress indicator
		buildHomePage "$webDirectory"
		# build the movie index
		buildMovieIndex "$webDirectory"
		# build the show index
		buildShowIndex "$webDirectory"
		# write the md5sum state of the libary for change checking
		#echo "$libarySum" > "$webDirectory/state.cfg"
		#getLibSum > "$webDirectory/state.cfg"
		# remove active state file
		rm -v /tmp/nfo2web.active
		# read the tvshow.nfo files for each show
		################################################################################
		# Create the show link on index.php
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
