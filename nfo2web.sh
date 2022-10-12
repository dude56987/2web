#! /bin/bash
########################################################################
# nfo2web generates websites from nfo filled directories
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
#set -x
#export PS4='+(${BASH_SOURCE}:${LINENO}): ${FUNCNAME[0]:+${FUNCNAME[0]}(): }'
export PS4='+ ${LINENO}	|	'
# set tab size to 4 to make output more readable
tabs 4
########################################################################
source /var/lib/2web/common
########################################################################
STOP(){
	echo ">>>>>>>>>>>DEBUG STOPPER<<<<<<<<<<<"
	read -r
}
########################################################################
drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
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
	# rip the tag data converted by converting lines to a token phrase to converted into html new lines
	data=$(echo "$data" | sed -z 's/\n/ ____NEWLINE____ /g' | grep -E --ignore-case --only-matching "<$tag>.*.?</$tag>" )
	# remove after slash tags, they break everything
	data="${data//<$tag \/>}"
	data="${data//<$tag\/>}"
	# pull data from between the tags
	data="${data//<$tag>}"
	data="${data//<\/$tag>}"
	# convert to html data after cleaning of tags is finished
	data=$(echo "$data" | txt2html --extract | sed 's/____NEWLINE____/ <br> /g')
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
	possibleThumbPaths="${possibleThumbPaths}$movieDir/$movieDir-poster\n"
	possibleThumbPaths="${possibleThumbPaths}$thumbnailShort\n"
	possibleThumbPaths="${possibleThumbPaths}$thumbnailShort-thumb\n"
	possibleThumbPaths=${possibleThumbPaths// /\ }
	# scan all the possible thumbnail paths
	#for thumbPathToCheck in $possibleThumbPaths;do
	#addToLog "DEBUG" "Checking possible file paths for thumbnail" "'$possibleThumbPaths'" "$logPagePath"
	echo -e "$possibleThumbPaths" | while read -r thumbPathToCheck;do
		#ALERT "reading possible thumb path '$thumbPathToCheck'"
		#addToLog "DEBUG" "Checking file path for thumbnail" "Checking file path '$thumbPathToCheck$thumbExtToCheck'" "$logPagePath"
		possibleThumbExts=".jpg .png .tbn"
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
				if ! test -f "$thumbnailPath.png";then
					convert -quiet "$thumbPathToCheck$thumbExtToCheck" "$thumbnailPath.png"
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
	logPagePath="$webDirectory/settings/log.php"
	#INFO "Processing movie $moviePath"
	# if moviepath exists
	if test -f "$moviePath";then
		# create the path sum for reconizing the libary path
		pathSum=$(echo -n "$movieDir" | md5sum | cut -d' ' -f1)
		#addToLog "DEBUG" "Path Sum Info" "Path Sum = '$pathSum' = sum data = movieDir = '$movieDir'" "$logPagePath"
		################################################################################
		# for each episode build a page for the episode
		nfoInfo=$(cat "$moviePath")
		# rip the movie title
		movieTitle=$(cleanXml "$nfoInfo" "title")
		movieTitle=$(alterArticles "$movieTitle" )
		#INFO "movie title = '$movieTitle'"
		movieYear=$(cleanXml "$nfoInfo" "year")
		#INFO "movie year = '$movieYear'"
		#moviePlot=$(ripXmlTag "$nfoInfo" "plot" | txt2html --extract -p 10)
		moviePlot=$(ripXmlTagMultiLine "$nfoInfo" "plot")
		#INFO "movie plot = '$moviePlot'"
		#moviePlot=$(echo "$moviePlot" | txt2html --extract )
		#INFO "movie plot = '$moviePlot'"
		# create the episode page path
		# each episode file title must be made so that it can be read more easily by kodi
		movieWebPath="${movieTitle} ($movieYear)"
		#INFO "movie web path = '$movieWebPath'"
		INFO "Processing movie $movieTitle $movieYear at $movieDir"
		# if the movie is updating a check should be preformed to see if there are multuple  state_*.cfg files inside the movie web directory
		#if [ "$( find "$webDirectory/movies/$movieWebPath/" -type f -name 'state_*.cfg' | wc -l )" -gt 1 ];then
		#	# there are more than one sources for this same movie in the libaries this will cause a forever update marking the movie as new on every update
		#	addToLog "ERROR" "Multiple Movie Sources" "Movie path '$movieDir' is a duplicate\n\nYou can remove on the server with the command\n\n\trm -rvi '$movieDir'\n\nRemove excess copies to stabilize the library data.\n\nBackups should not be placed in media library paths." "$logPagePath"
		#fi
		################################################################################
		# check the state now that the movie web path has been determined
		################################################################################
		# check movie state as soon as posible processing
		if test -f "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg";then
			if checkDirSum "$webDirectory" "$movieDir";then
					updateInfo="$movieTitle\n\n$currentSum != $libarySum\n\n$(ls "$movieDir")\n\n$moviePath\n\n$1\n\n$movieDir"
					addToLog "UPDATE" "Updating Movie" "$updateInfo" "$logPagePath"
			else
					unchangedInfo="$movieTitle"
					addToLog "INFO" "Movie unchanged" "$unchangedInfo" "$logPagePath"
					return
			fi
		else
			addToLog "NEW" "Adding new movie " "Adding '$movieTitle' from '$movieDir'" "$logPagePath"
		fi
		#if test -f "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg";then
		#	# a existing state was found
		#	currentSum=$(cat "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg")
		#	libarySum=$(getDirSum "$movieDir")
		#	# create the info
		#	updateInfo="$movieTitle\n\n$currentSum != $libarySum\n\n$(ls "$movieDir")\n\n$moviePath\n\n$1\n\n$movieDir"
		#	#unchangedInfo="$movieTitle\n\n$currentSum == $libarySum\n\n$(ls "$movieDir")\n\n$moviePath\n\n$1\n\n$movieDir"
		#	unchangedInfo="$movieTitle"
		#	# if the current state is the same as the state of the last update
		#	if [ "$libarySum" == "$currentSum" ];then
		#		# this means they are the same so no update needs run
		#		#INFO "State is unchanged for $movieTitle, no update is needed."
		#		#ALERT "[DEBUG]: $currentSum == $libarySum"
		#		addToLog "INFO" "Movie unchanged" "$unchangedInfo" "$logPagePath"
		#		return
		#	else
		#		#INFO "States are diffrent, updating $movieTitle..."
		#		#ALERT "[DEBUG]: $currentSum != $libarySum"
		#		addToLog "UPDATE" "Updating Movie" "$updateInfo" "$logPagePath"
		#	fi
		#else
		#	#ALERT "No movie state exists for $movieTitle, updating..."
		#	addToLog "NEW" "Adding new movie " "Adding '$movieTitle' from '$movieDir'" "$logPagePath"
		#fi
		################################################################################
		# After checking state build the movie page path, and build directories/links
		################################################################################
		moviePagePath="$webDirectory/movies/$movieWebPath/index.php"
		#INFO "movie page path = '$moviePagePath'"
		createDir "$webDirectory/movies/$movieWebPath/"
		createDir "$webDirectory/kodi/movies/$movieWebPath/"
		################################################################################
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
		thumbnailPath="$webDirectory/movies/$movieWebPath/poster"
		thumbnailPathKodi="$webDirectory/kodi/movies/$movieWebPath/poster"

		# check for the thumbnail and link it
		#checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi"

		# copy over subtitles
		if [ $(find "$movieDir" -type f -name '*.srt' | wc -l) -gt 0 ] ;then
			linkFile "$movieDir"/*.srt "$webDirectory/kodi/movies/$movieWebPath/"
		elif [ $(find "$movieDir" -type f -name '*.sub' | wc -l) -gt 0 ] ;then
			linkFile "$movieDir"/*.sub "$webDirectory/kodi/movies/$movieWebPath/"
		elif [ $(find "$movieDir" -type f -name '*.idx' | wc -l) -gt 0 ] ;then
			linkFile "$movieDir"/*.idx "$webDirectory/kodi/movies/$movieWebPath/"
		fi
		# link the fanart
		if test -f "$movieDir/$movieTitle-fanart.png";then
			linkFile "$movieDir/$movieTitle-fanart.png" "$webDirectory/movies/$movieWebPath/fanart.png"
		elif test -f "$movieDir/$movieTitle-fanart.jpg";then
			linkFile "$movieDir/$movieTitle-fanart.jpg" "$webDirectory/movies/$movieWebPath/fanart.jpg"
		elif test -f "$movieDir/fanart.png";then
			linkFile "$movieDir/fanart.png" "$webDirectory/movies/$movieWebPath/fanart.png"
		elif test -f "$movieDir/$movieWebPath-fanart.jpg";then
			linkFile "$movieDir/$movieWebPath-fanart.jpg" "$webDirectory/movies/$movieWebPath/fanart.jpg"
		elif test -f "$movieDir/$movieWebPath-fanart.png";then
			linkFile "$movieDir/$movieWebPath-fanart.png" "$webDirectory/movies/$movieWebPath/fanart.png"
		elif test -f "$movieDir/fanart.jpg";then
			linkFile "$movieDir/fanart.jpg" "$webDirectory/movies/$movieWebPath/fanart.jpg"
		else
			# generate a fanart if no fanart could be found
			ALERT "[WARNING]: could not find fanart '$movieDir/fanart.[png/jpg]'"
			ffmpeg -y -ss 1 -i "$videoPath" -vframes 1 -f singlejpeg - | convert -quiet - "$webDirectory/movies/$movieWebPath/fanart.png"
			convert -quiet "$webDirectory/movies/$movieWebPath/fanart.png" -blur 40x40 "$webDirectory/movies/$movieWebPath/fanart.png"
			# add the title to the poster
			convert -quiet "$webDirectory/movies/$movieWebPath/fanart.png" -adaptive-resize  1920x1080\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 1920x1080 -gravity center caption:"$movieTitle" -composite "$webDirectory/movies/$movieWebPath/fanart.png"
		fi
		if test -f "$webDirectory/movies/$movieWebPath/fanart.jpg";then
			convert -quiet "$webDirectory/movies/$movieWebPath/fanart.jpg" "$webDirectory/movies/$movieWebPath/fanart.png"
		fi

		# no thumbnail has been linked or downloaded search for one
		fullThumbPath=$(checkMovieThumbPaths "$movieDir" "$thumbnail" "$thumbnailShort" "$logPagePath" "$thumbnailPath" "$thumbnailPathKodi")

		# this is a simple check if the above search failed
		if test -f "$movieDir/$movieTitle-poster.png";then
			linkFile "$movieDir/$movieTitle-poster.png" "$webDirectory/movies/$movieWebPath/poster.png"
		elif test -f "$movieDir/$movieTitle-poster.jpg";then
			linkFile "$movieDir/$movieTitle-poster.jpg" "$webDirectory/movies/$movieWebPath/poster.jpg"
		elif test -f "$movieDir/poster.png";then
			linkFile "$movieDir/poster.png" "$webDirectory/movies/$movieWebPath/poster.png"
		elif test -f "$movieDir/$movieWebPath-poster.jpg";then
			linkFile "$movieDir/$movieWebPath-poster.jpg" "$webDirectory/movies/$movieWebPath/poster.jpg"
		elif test -f "$movieDir/$movieWebPath-poster.png";then
			linkFile "$movieDir/$movieWebPath-poster.png" "$webDirectory/movies/$movieWebPath/poster.png"
		elif test -f "$movieDir/poster.jpg";then
			linkFile "$movieDir/poster.jpg" "$webDirectory/movies/$movieWebPath/poster.jpg"
		else
			ALERT "[WARNING]: could not find poster '$movieDir/poster.[png/jpg]'"
			# generate a poster from a thumbnail if no poster could be found in previous check
			# generate a thumbnail at the first second
			ffmpeg -y -ss 1 -i "$videoPath" -vframes 1 -f singlejpeg - | convert -quiet - "$webDirectory/movies/$movieWebPath/poster.png"
			# blur the thumbnail
			convert -quiet "$webDirectory/movies/$movieWebPath/poster.png" -blur 40x40 "$webDirectory/movies/$movieWebPath/poster.png"
			# add the title to the poster
			convert -quiet "$webDirectory/movies/$movieWebPath/poster.png" -adaptive-resize 600x900\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 600x900 -gravity center caption:"$movieTitle" -composite "$webDirectory/movies/$movieWebPath/poster.png"
		fi
		if test -f "$webDirectory/movies/$movieWebPath/poster.jpg";then
			# link png for the web interface
			convert -quiet "$webDirectory/movies/$movieWebPath/poster.jpg" "$webDirectory/movies/$movieWebPath/poster.png"
		fi

		# link poster and fanart in the kodi section
		if ! test -f "$webDirectory/kodi/movies/$movieWebPath/poster.png";then
			linkFile "$webDirectory/movies/$movieWebPath/poster.png" "$webDirectory/kodi/movies/$movieWebPath/poster.png"
		fi
		if ! test -f "$webDirectory/kodi/movies/$movieWebPath/fanart.png";then
			linkFile "$webDirectory/movies/$movieWebPath/fanart.png" "$webDirectory/kodi/movies/$movieWebPath/fanart.png"
		fi
		# build temp style
		tempStyle=":root{"
		tempStyle="$tempStyle --backgroundPoster: url(\"/movies/$movieTitle ($movieYear)/poster.png\");"
		tempStyle="$tempStyle --backgroundFanart: url(\"/movies/$movieTitle ($movieYear)/fanart.png\");"
		tempStyle="$tempStyle}"
		# start rendering the html
		{
			echo "<html class='seriesBackground'>"
			echo "<head>"
			echo "<link rel='stylesheet' href='/style.css' />"
			echo "<title>$movieTitle ($movieYear)</title>"
			echo "<script src='/2web.js'></script>"
			echo "<style>"
			echo "$tempStyle"
			echo "</style>"
			echo "<link rel='icon' type='image/png' href='/favicon.png'>"
			echo "</head>"
			echo "<body>"
			echo "<?PHP";
			echo "include(\$_SERVER['DOCUMENT_ROOT'].'/header.php');";
			echo "?>";
			echo "<div class='titleCard'>"
			echo "<h1>$movieTitle ($movieYear)</h1>"
			# add outside search links
			echo "<div class='listCard'>"
			echo "<a class='button' target='_new' href='https://www.imdb.com/find?q=$movieTitle ($movieYear)'>🔎 IMDB</a>"
			echo "<a class='button' target='_new' href='https://en.wikipedia.org/w/?search=$movieTitle ($movieYear)'>🔎 WIKIPEDIA</a>"
			echo "<a class='button' target='_new' href='https://archive.org/details/movies?query=$movieTitle ($movieYear)'>🔎 ARCHIVE.ORG</a>"
			echo "<a class='button' target='_new' href='https://www.youtube.com/results?search_query=$movieTitle ($movieYear)'>🔎 YOUTUBE</a>"
			echo "<a class='button' target='_new' href='https://odysee.com/$/search?q=$movieTitle ($movieYear)'>🔎 ODYSEE</a>"
			echo "<a class='button' target='_new' href='https://rumble.com/search/video?q=$movieTitle ($movieYear)'>🔎 RUMBLE</a>"
			echo "<a class='button' target='_new' href='https://www.bitchute.com/search/?kind=video&query=$movieTitle ($movieYear)'>🔎 BITCHUTE</a>"
			echo "<a class='button' target='_new' href='https://www.twitch.tv/search?term=$movieTitle ($movieYear)'>🔎 TWITCH</a>"
			echo "<a class='button' target='_new' href='https://veoh.com/find/$movieTitle ($movieYear)'>🔎 VEOH</a>"
			echo "</div>"
			echo "</div>"
		} > "$moviePagePath"


		# generate a thumbnail from the xml data if it can be retreved
		if ! test -f "$thumbnailPath.png";then
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
					# generate the web thumbnail
					convert "$fullThumbPath" -adaptive-resize "300x200" "$thumbnailPath-web.png"
					# link the downloaded thumbnail
					linkFile "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
				else
					ALERT "[DEBUG]: Thumbnail download link is invalid '$thumbnailLink'"
				fi
			fi

			# check if the thumb download failed
			if test -f "$thumbnailPath.png";then
				tempFileSize=$(wc --bytes < "$thumbnailPath.png")
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
		# create the web thumbnail if it does not exist and a thumbnail path was found
		if ! test -f "$thumbnailPath-web.png";then
			# convert the thumbnail into a web thumbnail
			convert -quiet "$webDirectory/movies/$movieWebPath/poster.png" -adaptive-resize "300x200" "$webDirectory/movies/$movieWebPath/poster-web.png"
		fi
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
				echo "		🔗Direct Link"
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
					echo "🔗Direct Link"
					echo "</a>"
				else
					echo "<a class='button hardLink' href='$movieWebPath$sufix'>"
					echo "🔗Direct Link"
					echo "</a>"
					echo "<a class='button hardLink vlcButton' href='vlc://http://$(hostname).local/movies/$movieWebPath/$movieWebPath$sufix'>"
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
				echo "🔗Direct Link"
				echo "</a>"
				echo "<a class='button hardLink vlcButton' href='vlc://http://$(hostname).local/movies/$movieWebPath/$movieWebPath$sufix'>"
				echo "<span id='vlcIcon'>&#9650;</span> VLC"
				echo "</a>"
				echo "$moviePlot"
				echo "</div>"
			} >> "$moviePagePath"
		fi
		{
			# add footer
			echo "<?PHP";
			echo "include(\$_SERVER['DOCUMENT_ROOT'].'/footer.php');";
			echo "?>";
			echo "</body>"
			echo "</html>"
		} >> "$moviePagePath"
		################################################################################
		# add the movie to the movie index page
		################################################################################
		{
			echo "<a class='indexSeries' href='/movies/$movieWebPath'>"
			echo "	<img loading='lazy' src='/movies/$movieWebPath/poster-web.png'>"
			echo "	<div class='title'>"
			echo "		$movieTitle"
			echo "		<br>"
			echo "		($movieYear)"
			echo "	</div>"
			echo "</a>"
		} > "$webDirectory/movies/$movieWebPath/movies.index"

		# add the movie to the main movie index since it has been updated
		addToIndex "$webDirectory/movies/$movieWebPath/movies.index" "$webDirectory/movies/movies.index"
		# add the updated movie to the new movies index
		echo "$webDirectory/movies/$movieWebPath/movies.index" >> "$webDirectory/new/movies.index"
		echo "$webDirectory/movies/$movieWebPath/movies.index" >> "$webDirectory/new/all.index"
		# link movies to random movies
		linkFile "$webDirectory/movies/movies.index" "$webDirectory/random/movies.index"
		# add to random all
		echo "$webDirectory/movies/$movieWebPath/movies.index" >> "$webDirectory/random/all.index"

		# add the path to the list of paths for duplicate checking
		if ! grep "$movieDir" "$webDirectory/movies/$movieWebPath/sources.cfg";then
			# if the path is not in the file add it to the file
			echo "$movieDir" >> "$webDirectory/movies/$movieWebPath/sources.cfg"
		fi
	else
		ALERT "[WARNING]: The file '$moviePath' could not be found!"
	fi
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
			if ! test -f "$thumbnailPath.png";then
				convert -quiet "$thumbnail.jpg" "$thumbnailPath.png"
			fi
		else
			if echo "$nfoInfo" | grep -q "thumb";then
				thumbnailLink=$(ripXmlTag "$nfoInfo" "thumb")
				addToLog "DOWNLOAD" "Downloading Thumbnail" "Creating thumbnail from link '$thumbnailLink'" "$logPagePath"
				thumbnailExt=".png"
				# download the thumbnail
				downloadThumbnail "$thumbnailLink" "$thumbnailPath" "$thumbnailExt"

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
	logPagePath="$webDirectory/settings/log.php"
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
		episodeShowTitle=$(alterArticles "$episodeShowTitle")
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
		#episodePlot=$(echo "$episodePlot" | txt2html --extract )
		#INFO "episode plot = '$episodePlot'"
		episodeSeason=$(cleanXml "$nfoInfo" "season")
		#INFO "Episode season = '$episodeSeason'"
		episodeAired=$(ripXmlTag "$nfoInfo" "aired")
		#INFO "Episode air date = '$episodeAired'"
		episodeSeason=$(echo "$episodeSeason" | sed "s/^[0]\{,4\}//g")
		if [ "$episodeSeason" -lt 10 ];then
			# add a zero to make it format correctly
			episodeSeason="000$episodeSeason"
		elif [ "$episodeSeason" -lt 100 ];then
			# add a zero to make it format correctly
			episodeSeason="00$episodeSeason"
		elif [ "$episodeSeason" -lt 1000 ];then
			episodeSeason="0$episodeSeason"
		fi
		episodeSeasonPath="Season $episodeSeason"
		#INFO "Episode season path = '$episodeSeasonPath'"
		episodeNumber=$(cleanXml "$nfoInfo" "episode")
		episodeNumber=$(echo "$episodeNumber" | sed "s/^[0]\{,4\}//g")
		if [ "$episodeNumber" -lt 10 ];then
			# add a zero to make it format correctly
			episodeNumber="000$episodeNumber"
		elif [ "$episodeNumber" -lt 100 ];then
			# add a zero to make it format correctly
			episodeNumber="00$episodeNumber"
		elif [ "$episodeNumber" -lt 1000 ];then
			episodeNumber="0$episodeNumber"
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
		createDir "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/"
		createDir "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"
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
		# build the temp style theme
		tempStyle=":root{"
		tempStyle="$tempStyle --backgroundPoster: url(\"/shows/$episodeShowTitle/poster.png\");"
		tempStyle="$tempStyle --backgroundFanart: url(\"/shows/$episodeShowTitle/fanart.png\");"
		tempStyle="$tempStyle}"
		# start rendering the html
		{
			# the style variable must be set inline, not in head, this may be a bug in firefox
			#echo "<html id='top' class='seriesBackground' style='$tempStyle'>"
			echo "<html id='top' class='seriesBackground'>"
			echo "<head>"
			echo "<title>$episodeShowTitle - ${episodeSeason}x${episodeNumber}</title>"
			echo "<link rel='stylesheet' href='/style.css' />"
			echo "<script src='/2web.js'></script>"
			echo "<style>"
			#add the fanart
			echo "$tempStyle"
			echo "</style>"
			echo "<link rel='icon' type='image/png' href='/favicon.png'>"
			echo "</head>"
			echo "<body>"
			echo "<?PHP";
			echo "include(\$_SERVER['DOCUMENT_ROOT'].'/header.php');";
			echo "?>";
			echo "<div class='titleCard'>"
			echo "<h1><a href='/shows/$episodeShowTitle/#Season ${episodeSeason}'>$episodeShowTitle</a> ${episodeSeason}x${episodeNumber}</h1>"
			# add outside search links
			echo "<div class='listCard'>"
			echo "<a class='button' target='_new' href='https://www.imdb.com/find?q=$episodeShowTitle $episodeTitle'>🔎 IMDB</a>"
			echo "<a class='button' target='_new' href='https://en.wikipedia.org/w/?search=$episodeShowTitle $episodeTitle'>🔎 WIKIPEDIA</a>"
			echo "<a class='button' target='_new' href='https://archive.org/details/movies?query=$episodeShowTitle $episodeTitle'>🔎 ARCHIVE.ORG</a>"
			echo "<a class='button' target='_new' href='https://www.youtube.com/results?search_query=$episodeShowTitle $episodeTitle'>🔎 YOUTUBE</a>"
			echo "<a class='button' target='_new' href='https://odysee.com/$/search?q=$episodeShowTitle $episodeTitle'>🔎 ODYSEE</a>"
			echo "<a class='button' target='_new' href='https://rumble.com/search/video?q=$episodeShowTitle $episodeTitle'>🔎 RUMBLE</a>"
			echo "<a class='button' target='_new' href='https://www.bitchute.com/search/?kind=video&query=$episodeShowTitle $episodeTitle'>🔎 BITCHUTE</a>"
			echo "<a class='button' target='_new' href='https://www.twitch.tv/search?term=$episodeShowTitle $episodeTitle'>🔎 TWITCH</a>"
			echo "<a class='button' target='_new' href='https://veoh.com/find/$episodeShowTitle $episodeTitle'>🔎 VEOH</a>"
			echo "</div>"
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
				#addToLog "DEBUG" "Checking episode for caching" "$showTitle - $episodePath" "$logPagePath"
				# split up airdate data to check if caching should be done
				airedYear=$(echo "$episodeAired" | cut -d'-' -f1)
				airedMonth=$(echo "$episodeAired" | cut -d'-' -f2)
				#ALERT "[DEBUG]: Checking if file was released in the last month"
				#ALERT "[DEBUG]: aired year $airedYear == current year $(date +"%Y")"
				#ALERT "[DEBUG]: aired month $airedMonth == current month $(date +"%m")"
				# if the airdate was this year
				if [ $((10#$airedYear)) -eq "$((10#$(date +"%Y")))" ];then
					#addToLog "DEBUG" "Episode matches year" "$showTitle - $episodePath\n $((10#$airedYear)) ?= $((10#$(date +"%Y")))" "$logPagePath"
					# if the airdate was this month
					if [ $((10#$airedMonth)) -eq "$((10#$(date +"%m")))" ];then
						#addToLog "DEBUG" "Episode matches month" "$showTitle - $episodePath\n $((10#$airedMonth)) ?= $((10#$(date +"%m")))" "$logPagePath"
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
			vlcCacheRedirect="http://$(hostname).local/ytdl-resolver.php?url=\"$ytLink\""
			fullRedirect="$(hostname).local/ytdl-resolver.php?url=\"$ytLink\""
			{
				echo "<div class='descriptionCard'>"
				echo "<h2>$episodeTitle</h2>"
				# create a hard link
				echo "<a class='button hardLink' href='$ytLink'>"
				echo "	🔗Direct Link"
				echo "</a>"

				echo "<a class='button hardLink' href='$cacheRedirect'>"
				echo "	📥Cache Link"
				echo "</a>"

				echo "<a class='button hardLink vlcButton' href='vlc://$vlcCacheRedirect'>"
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
					# TODO: transcode works but needs to be toggleable, above is correct code for the transcode.php script
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
					cacheRedirect="http://$(hostname).local/ytdl-resolver.php?url=\"$videoPath\""
					vlcCacheRedirect="$(hostname).local/ytdl-resolver.php?url=\"$videoPath\""
					echo "<a class='button hardLink' href='$videoPath'>"
					echo "	🔗Direct Link"
					echo "</a>"
					# cache link
					echo "<a class='button hardLink' href='$cacheRedirect'>"
					echo "	📥Cache Link"
					echo "</a>"
					echo "<a class='button hardLink vlcButton' href='vlc://$vlcCacheRedirect'>"
					echo "<span id='vlcIcon'>&#9650;</span> VLC"
					echo "</a>"
				else
					echo "<a class='button hardLink' href='$episodePath$sufix'>"
					echo "	🔗Direct Link"
					echo "</a>"
					echo "<a class='button hardLink vlcButton' href='vlc://http://$(hostname).local/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix'>"
					echo "<span id='vlcIcon'>&#9650;</span> VLC"
					echo "</a>"
				fi

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
			echo "include(\$_SERVER['DOCUMENT_ROOT'].'/footer.php');";
			echo "?>";
			echo "</body>"
			echo "</html>"
		} >> "$episodePagePath"
		################################################################################
		# build the episode link to be included by index pages
		################################################################################
		tempEpisodeSeasonThumb="<a class='showPageEpisode' href='/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.php'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<h2 class='title'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n  	$episodeShowTitle"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	</h2>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<img loading='lazy' src='/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb-web.png'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<div class='title'>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	<div class='showIndexNumbers'>${episodeSeason}x${episodeNumber}</div>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n		$episodeTitle"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n	</div>"
		tempEpisodeSeasonThumb="$tempEpisodeSeasonThumb\n</a>"

		if [ "$episodeNumber" -eq 1 ];then
			echo -ne "$tempEpisodeSeasonThumb" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		else
			echo -ne "$tempEpisodeSeasonThumb" >> "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		fi

		echo -ne "$tempEpisodeSeasonThumb" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.index"

		#$episodeSum=$(echo "/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.php" | md5sum | cut -d' ' -f1)
		#echo -ne "$episodeSum" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.cfg"

		# add episodes to new indexes
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.index" >> "$webDirectory/new/episodes.index"
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.index" >> "$webDirectory/new/all.index"
		# random indexes
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.index" >> "$webDirectory/random/episodes.index"
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/episode_$episodePath.index" >> "$webDirectory/random/all.index"
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
	logPagePath="$webDirectory/settings/log.php"
	showLogPath="$webDirectory/shows/$showTitle/log.index"
	# create the path sum for reconizing the libary path
	pathSum=$(echo -n "$show" | md5sum | cut -d' ' -f1)
	# create directory
	INFO "creating show directory at '$webDirectory/$showTitle/'"
	createDir "$webDirectory/shows/$showTitle/"
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
	createDir "$webDirectory/kodi/shows/$showTitle/"
	# linking tvshow.nfo data
	linkFile "$show/tvshow.nfo" "$webDirectory/shows/$showTitle/tvshow.nfo"
	linkFile "$show/tvshow.nfo" "$webDirectory/kodi/shows/$showTitle/tvshow.nfo"
	if ! test -f "$webDirectory/shows/$showTitle/plot.cfg";then
		# rip the plot from the show metadata so it can be added to the webpage
		{
			showPlot=$(ripXmlTagMultiLine "$showMeta" "plot")
			echo "$showPlot"
		} > "$webDirectory/shows/$showTitle/plot.cfg"
	fi
	# link all images to the kodi path
	#if ls "$show" | grep -q "\.jpg" ;then
	#	linkFile "$show"/*.jpg "$webDirectory/kodi/shows/$showTitle/"
	#	linkFile "$show"/*.jpg "$webDirectory/shows/$showTitle/"
	#fi
	#if ls "$show" | grep -q "\.png" ;then
	#	linkFile "$show"/*.png "$webDirectory/kodi/shows/$showTitle/"
	#	linkFile "$show"/*.png "$webDirectory/shows/$showTitle/"
	#fi
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
	# link the poster
	if test -f "$show/poster.png";then
		linkFile "$show/poster.png" "$webDirectory/shows/$showTitle/poster.png"
		linkFile "$show/poster.png" "$webDirectory/kodi/shows/$showTitle/poster.png"
		# create the web thumbnails
		if ! test -f "$webDirectory/shows/$showTitle/poster-web.png";then
			convert -quiet "$show/$posterPath" -resize "300x200" "$webDirectory/shows/$showTitle/poster-web.png"
		fi
	elif test -f "$show/poster.jpg";then
		linkFile "$show/poster.jpg" "$webDirectory/shows/$showTitle/poster.jpg"
		linkFile "$show/poster.jpg" "$webDirectory/kodi/shows/$showTitle/poster.jpg"
		# link the jpg to a png for the web browser
		if ! test -f "$webDirectory/shows/$showTitle/poster.png";then
			convert -quiet "$webDirectory/shows/$showTitle/poster.jpg" "$webDirectory/shows/$showTitle/poster.png"
		fi
	fi
	# create the web thumbnails
	if ! test -f "$webDirectory/shows/$showTitle/poster-web.png";then
		convert -quiet "$webDirectory/shows/$showTitle/poster.png" -resize "300x200" "$webDirectory/shows/$showTitle/poster-web.png"
	fi
	# link the fanart
	if test -f "$show/fanart.png";then
		fanartPath="fanart.png"
		linkFile "$show/fanart.png" "$webDirectory/shows/$showTitle/fanart.png"
		linkFile "$show/fanart.png" "$webDirectory/kodi/shows/$showTitle/fanart.png"
	elif test -f "$show/fanart.jpg";then
		fanartPath="fanart.jpg"
		linkFile "$show/fanart.jpg" "$webDirectory/shows/$showTitle/fanart.jpg"
		linkFile "$show/fanart.jpg" "$webDirectory/kodi/shows/$showTitle/fanart.jpg"
		# convert the fanart to png for web
		if ! test -f "$webDirectory/shows/$showTitle/fanart.png";then
			convert -quiet "$webDirectory/shows/$showTitle/fanart.jpg" "$webDirectory/shows/$showTitle/fanart.png"
		fi
	fi
	if ! test -f "$webDirectory/shows/$showTitle/season-all-poster.png";then
		# TODO: search and find the newest season poster and make that the season all poster
		# copy the poster to be the season all poster
		linkFile "$webDirectory/shows/$showTitle/poster.png" "$webDirectory/shows/$showTitle/season-all-poster.png"
	fi
	################################################################################
	# process all individual episodes
	################################################################################
	# - Episodes must be processed after fanart is built because episodes search for
	#   series fanart
	# - generate the episodes based on .nfo files
	################################################################################
	# - find "$show/" -type 'd' -maxdepth 1 -mindepth 1 | while read -r season;do
	# - seasons in shows can not use find loop because it creates a subshell inside the
	#  loop which prevents chaninging of variable values used in the function
	################################################################################
	for season in "$show"/*;do
		if test -d "$season";then
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
				# build top of show webpage containing all of the shows meta info
				linkFile "/usr/share/2web/templates/seasons.php" "$showPagePath"

				# update the season sum file
				touch "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg"
				echo "$libarySeasonSum" > "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg"
			fi
		fi
	done

	if ! test -f "$webDirectory/shows/$showTitle/fanart.png";then
		# get the list of thumbnails
		#thumbnailList=$(find "$webDirectory/shows/$showTitle/" -name "*-web.png" | tail -n 24 | sed "s/^/'/g" | sed "s/$/'/g" | sed -z "s/\n/ /g" )
		# get the list of image files
		# create the failsafe background by combining all the thumbnails of episodes into a image
		montage "$webDirectory"/shows/"$showTitle"/*/*-web.png -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/shows/$showTitle/fanart.png"
		#montage $thumbnailList -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/shows/$showTitle/fanart.png"
		if test -f "$webDirectory/shows/$showTitle/fanart-0.png";then
			cp "$webDirectory/shows/$showTitle/fanart-0.png" "$webDirectory/shows/$showTitle/fanart.png"
			rm "$webDirectory/shows/$showTitle/fanart-"*.png
		fi
		convert "$webDirectory/shows/$showTitle/fanart.png" -trim -blur 40x40 "$webDirectory/shows/$showTitle/fanart.png"
		echo "Creating the fanart image from webpage..."
		convert "$webDirectory/shows/$showTitle/fanart.png" -adaptive-resize 1920x1080\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 1920x1080 -gravity center caption:"$showTitle" -composite "$webDirectory/shows/$showTitle/fanart.png"
		# error log
		addToLog "WARNING" "Could not find fanart.[png/jpg]" "$showTitle has no $show/fanart.[png/jpg], Generating one at <a href='/$show/fanart.png'>$show/fanart.png</a>" "$logPagePath"
	fi
	if ! test -f "$webDirectory/shows/$showTitle/poster.png";then
		#thumbnailList=$(find "$webDirectory/shows/$showTitle/" -name "*-web.png" | tail -n 8 | sed "s/^/'/g" | sed "s/$/'/g" | sed -z "s/\n/ /g" )
		montage "$webDirectory"/shows/"$showTitle"/*/*-web.png -background black -geometry 800x600\!+0+0 -tile 2x4 "$webDirectory/shows/$showTitle/poster.png"
		#montage $thumbnailList -background black -geometry 800x600\!+0+0 -tile 2x4 "$webDirectory/shows/$showTitle/poster.png"
		if test -f "$webDirectory/shows/$showTitle/poster-0.png";then
			# to many images exist use only first set
			cp "$webDirectory/shows/$showTitle/poster-0.png" "$webDirectory/shows/$showTitle/poster.png"
			# remove excess images
			rm "$webDirectory/shows/$showTitle/poster-"*.png
		fi
		convert "$webDirectory/shows/$showTitle/poster.png" -trim -blur 40x40 "$webDirectory/shows/$showTitle/poster.png"
		echo "Creating the poster image from webpage..."
		convert "$webDirectory/shows/$showTitle/poster.png" -adaptive-resize 600x900\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 600x900 -gravity center caption:"$showTitle" -composite "$webDirectory/shows/$showTitle/poster.png"
		convert -quiet "$webDirectory/shows/$showTitle/poster.png" -resize "300x200" "$webDirectory/shows/$showTitle/poster-web.png"
		linkFile "$webDirectory/shows/$showTitle/poster.png" "$webDirectory/kodi/shows/$showTitle/poster.png"
		# add log entry
		addToLog "WARNING" "Could not find poster.[png/jpg]" "$showTitle has no $show/poster.[png/jpg]" "$logPagePath"
	fi
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

	# add the show to the main show index since it has been updated
	addToIndex "$webDirectory/shows/$showTitle/shows.index" "$webDirectory/shows/shows.index"
	# add the updated show to the new shows index
	echo "$webDirectory/shows/$showTitle/shows.index" >> "$webDirectory/new/shows.index"
	echo "$webDirectory/shows/$showTitle/shows.index" >> "$webDirectory/new/all.index"
	# add the updated show to the random shows index
	linkFile "$webDirectory/shows/shows.index" "$webDirectory/random/shows.index"
	echo "$webDirectory/shows/$showTitle/shows.index" >> "$webDirectory/random/all.index"

	# add the path to the list of paths for duplicate checking
	# - this is for a sanity warning, shows spread across multuple devices
	#   can get extremely messy and episode files will be duplicated
	# - 2web will still scan these series and display them normally
	# - duplicates will show in web interface IF metadata is diffrent in any way
	if ! grep "$show" "$webDirectory/shows/$showTitle/sources.cfg";then
		# if the path is not in the file add it to the file
		echo "$show" >> "$webDirectory/shows/$showTitle/sources.cfg"
	fi

	# update the libary sum
	touch "$webDirectory/shows/$showTitle/state_$pathSum.cfg"
	getDirSum "$show" > "$webDirectory/shows/$showTitle/state_$pathSum.cfg"
}
########################################################################
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
	if test -f "$webDirectory/sums/nfo_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/nfo_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/nfo_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/nfo_$pathSum.cfg"
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
	# build the show fanart and poster indexes
	################################################################################
	if test -d "$webDirectory/shows/";then
		if cacheCheck "$webDirectory/shows/fanart.cfg" "$backgroundUpdateDelay";then
			# create shows only fanart.cfg
			cd "$webDirectory/shows/"
			find -L "." -type f -name "fanart.png" > "$webDirectory/shows/fanart.cfg"
			find -L "." -type f -name "fanart.jpg" >> "$webDirectory/shows/fanart.cfg"
		fi
		if cacheCheck "$webDirectory/shows/poster.cfg" "$backgroundUpdateDelay";then
			# create shows only poster.cfg
			cd "$webDirectory/shows/"
			find -L "." -type f -name "poster.png" > "$webDirectory/shows/poster.cfg"
			find -L "." -type f -name "poster.jpg" >> "$webDirectory/shows/poster.cfg"
		fi
	fi
	################################################################################
	# build the movie fanart and poster indexes
	################################################################################
	if test -d "$webDirectory/movies/";then
		if cacheCheck "$webDirectory/movies/fanart.cfg" "$backgroundUpdateDelay";then
			# create movies only fanart.cfg
			cd "$webDirectory/movies/"
			find -L "." -type f -name "fanart.png" > "$webDirectory/movies/fanart.cfg"
			find -L "." -type f -name "fanart.jpg" >> "$webDirectory/movies/fanart.cfg"
		fi
		if cacheCheck "$webDirectory/movies/poster.cfg" "$backgroundUpdateDelay";then
			# create movies only poster.cfg
			cd "$webDirectory/movies/"
			find -L "." -type f -name "poster.png" > "$webDirectory/movies/poster.cfg"
			find -L "." -type f -name "poster.jpg" >> "$webDirectory/movies/poster.cfg"
		fi
	fi
	################################################################################
	# build the main index by combining all the other indexes
	################################################################################
	if cacheCheck "$webDirectory/poster.cfg" "$backgroundUpdateDelay";then
		# move into the web directory so paths from below searches are relative
		cd $webDirectory
		if test -d "$webDirectory/shows/";then
			find -L "shows/" -type f -name "poster.png" > "$webDirectory/poster.cfg"
			find -L "shows/" -type f -name "poster.jpg" >> "$webDirectory/poster.cfg"
		fi
		if test -d "$webDirectory/movies/";then
			find -L "movies/" -type f -name "poster.png" >> "$webDirectory/poster.cfg"
			find -L "movies/" -type f -name "poster.jpg" >> "$webDirectory/poster.cfg"
		fi
		if test -d "$webDirectory/comics/";then
			find -L "comics/" -type f -name "thumb.png" >> "$webDirectory/poster.cfg"
		fi
	fi
	# if the fanart list is older than $backgroundUpdateDelay in days
	if cacheCheck "$webDirectory/fanart.cfg" "$backgroundUpdateDelay";then
		# move into the web directory so paths from below searches are relative
		cd $webDirectory
		if test -d "$webDirectory/shows/";then
			find -L "shows/" -type f -name "fanart.png" > "$webDirectory/fanart.cfg"
			find -L "shows/" -type f -name "fanart.jpg" >> "$webDirectory/fanart.cfg"
		fi
		if test -d "$webDirectory/movies/";then
			find -L "movies/" -type f -name "fanart.png" >> "$webDirectory/fanart.cfg"
			find -L "movies/" -type f -name "fanart.jpg" >> "$webDirectory/fanart.cfg"
		fi
		if test -d "$webDirectory/comics/";then
			find -L "comics/" -type f -name "thumb.png" >> "$webDirectory/fanart.cfg"
		fi
	fi
	################################################################################
	# remove empty lists
	################################################################################
	if [ "$( cat "$webDirectory/fanart.cfg" | wc -l )" -lt 2 ];then
		rm "$webDirectory/fanart.cfg"
	fi
	if [ "$( cat "$webDirectory/poster.cfg" | wc -l )" -lt 2 ];then
		rm "$webDirectory/poster.cfg"
	fi
}
########################################################################
function nuke(){
	echo "[INFO]: Reseting web cache to blank..."
	rm -rv $(webRoot)/movies/*
	rm -rv $(webRoot)/random/movies.index
	rm -rv $(webRoot)/new/movies.index
	rm -rv $(webRoot)/shows/*
	rm -rv $(webRoot)/random/shows.index
	rm -rv $(webRoot)/random/episodes.index
	rm -rv $(webRoot)/new/shows.index
	rm -rv $(webRoot)/new/episodes.index
	# remove widgets cached
	rm -v $(webRoot)/web_cache/widget_random_movies.index
	rm -v $(webRoot)/web_cache/widget_random_shows.index
	rm -v $(webRoot)/web_cache/widget_random_episodes.index
	rm -v $(webRoot)/web_cache/widget_new_movies.index
	rm -v $(webRoot)/web_cache/widget_new_shows.index
	rm -v $(webRoot)/web_cache/widget_new_episodes.index
	echo "[SUCCESS]: Web cache states reset, update to rebuild everything."
	echo "[SUCCESS]: Site will remain the same until updated."
	echo "[INFO]: Use 'nfo2web update' to generate a new website..."
}
########################################################################
function buildMovieIndex(){
	webDirectory=$1
	linkFile "/usr/share/2web/templates/movies.php" "$webDirectory/movies/index.php"
}
########################################################################
function libaryPaths(){
	# check for server libary config
	if ! test -f /etc/2web/nfo/libaries.cfg;then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			# add the default download directory
			echo "# Server Default Libaries config/"
			# add the download directory to the paths
			echo "/var/cache/2web/download/"
		} > "/etc/2web/nfo/libaries.cfg"
	fi
	# write path to console
	grep -v "^#" "/etc/2web/nfo/libaries.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/nfo/libaries.d/" -mindepth 1 -maxdepth 1 -type f -name '*.cfg' | while read libaryConfigPath;do
		grep -v "^#" "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
function cleanMediaSection(){
	mediaSectionLibaries=$(find "$1" -maxdepth 1 -mindepth 1 -type 'd' | sort)
	IFS=$'\n'
	# go into each show and movie directory in the website
	for mediaPath in $mediaSectionlibaries;do
		# look for broken symlinks in the directory
		if symlinks -r "$mediaPath" | grep -q "^dangling:";then
			# delete entire directory for show/movie if the show/movie contains broken links
			# a regular update will re add any shows/movies that were removed because of corrupt data
			echo "rm -rv $mediaPath" #DEBUG
			rm -rv "$mediaPath"
		else
			# diff lists are identical so no broken links exist
			echo "NO BROKEN LINKS IN $mediaPath" #DEBUG
		fi
	done
}
################################################################################
cleanMediaIndexFile(){
	webPath=$1
	webIndexName=$2
	# read and check individual links in .index files
	IFS=$'\n'
	generatedIndexData=$(find "$webPath" -maxdepth 2 -mindepth 2 -type 'f' -name "$webIndexName" | sort)
	currentIndexData=$(cat "$webPath$webIndexName" | sort)

	#diff <(echo "$generatedIndexData") <(echo "$currentIndexData")

	diffCount=$(diff <(echo "$generatedIndexData") <(echo "$currentIndexData") | wc -l )
	if [ $diffCount -eq 0 ];then
		# nothing needs done index data is exactly the same
		echo "Broken links could not be found in index data at $webPath$webIndexName..."
	else
		echo "Broken links found in $webPath$webIndexName"
		# overwrite the current data with the correct generated one
		echo "$generatedIndexData" > "$webPath$webIndexName"
	fi
}
################################################################################
function clean(){
	# find and delete directories for show/movie if the show/movie contains broken links
	cleanMediaSection "/var/cache/2web/web/movies/"
	cleanMediaSection "/var/cache/2web/web/shows/"
	# clean index files
	cleanMediaIndexFile "/var/cache/2web/web/shows/" "shows.index"
	cleanMediaIndexFile "/var/cache/2web/web/movies/" "movies.index"

	# remove the web cached data for widgets

	# updated(new) widgets
	rm -rv /var/cache/2web/web/web_cache/widget_updated_movies.index
	rm -rv /var/cache/2web/web/web_cache/widget_updated_shows.index
	rm -rv /var/cache/2web/web/web_cache/widget_updated_episodes.index
	# random widgets
	rm -rv /var/cache/2web/web/web_cache/widget_random_movies.index
	rm -rv /var/cache/2web/web/web_cache/widget_random_shows.index
	rm -rv /var/cache/2web/web/web_cache/widget_random_episodes.index
}
################################################################################
function update(){
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
	#libaries=$(libaryPaths | tr -s "\n" | shuf )
	libaries=$(libaryPaths | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )
	# the webdirectory is a cache where the generated website is stored
	webDirectory="$(webRoot)"

	INFO "Building web directory at '$webDirectory'"
	# force overwrite symbolic link to web directory
	# - link must be used to also use premade apache settings
	ln -sfn "$webDirectory" "/var/cache/2web/web"
	# create the log path
	logPagePath="$webDirectory/settings/log.php"
	# create the homepage path
	#homePagePath="$webDirectory/index.php"
	showIndexPath="$webDirectory/shows/index.php"
	movieIndexPath="$webDirectory/movies/index.php"
	# build the movie index
	buildMovieIndex "$webDirectory"
	# build the show index
	buildShowIndex "$webDirectory"
	#touch "$showIndexPath"
	#touch "$movieIndexPath"
	touch "$logPagePath"
	#touch "$homePagePath"
	# check for the header
	linkFile "/usr/share/2web/templates/header.php" "$webDirectory/header.php"
	linkFile "/usr/share/2web/templates/footer.php" "$webDirectory/footer.php"
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
		echo "include(\$_SERVER['DOCUMENT_ROOT'].'/header.php');";
		echo "include('settingsHeader.php');";
		echo "?>";
		# add the javascript sorter controls
		echo "<div class='inputCard'>"
		echo "<h2>Filter Log Entries</h2>"
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
		echo -n "<input type='button' class='button' value='Debug'"
		echo    " onclick='toggleVisibleClass(\"DEBUG\")'>"
		echo -n "<input type='button' class='button' value='Download'"
		echo    " onclick='toggleVisibleClass(\"DOWNLOAD\")'>"
		echo "</div>"
		echo "<hr>"
		echo "<!--  add the search box -->"
		echo "<input id='searchBox' class='searchBox' type='text' onkeyup='filter(\"logEntry\")' placeholder='Search...' >"
		echo "<hr>"
		echo "<div class='settingsListCard'>"
		# start the table
		echo "<div class='settingsTable'>"
		echo "<table>"
	} > "$logPagePath"
	addToLog "INFO" "Started Update" "$(date)" "$logPagePath"
	addToLog "INFO" "Libaries:" "$libaries" "$logPagePath"
	# figure out the total number of CPUS for parallel processing
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(grep "processor" "/proc/cpuinfo" | wc -l)
	fi
	# read each libary from the libary config, single path per line
	ALERT "LIBARIES: $libaries"
	#echo -e "LIBARIES: \n$libaries"
	#for libary in $libaries;do

	if cacheCheck "$webDirectory/cleanCheck.cfg" "7";then
		# clean the database of broken entries
		# - this should allow you to delete data from source drives and it automatically remove it from the website.
		clean
	fi

	IFS=$'\n'
	#echo "$libaries" | while read libary;do
	for libary in $libaries;do
		ALERT "libary = $libary"
		# check if the libary directory exists
		addToLog "INFO" "Checking library path" "$libary" "$logPagePath"
		ALERT "Check if directory exists at '$libary'"
		if test -d "$libary";then
			ALERT "library exists at '$libary'"
		else
			ALERT "library does not exist at '$libary'"
		fi
		if test -d "$libary";then
			addToLog "UPDATE" "Starting library scan" "$libary" "$logPagePath"
			echo "library exists at '$libary'"
			# read each tvshow directory from the libary
			#for show in "$libary"/*;do
			#addToLog "DEBUG" "Found show paths" "$(find "$libary" -type 'd' -maxdepth 1 -mindepth 1 | sed -z 's/\n/\n\n/g' )" "$logPagePath"

			foundLibaryPaths=$(find "$libary" -maxdepth 1 -mindepth 1 -type 'd' | shuf)

			#find "$libary" -type 'd' -maxdepth 1 -mindepth 1 | shuf | while read -r show;do

			for show in $foundLibaryPaths;do
				addToLog "DEBUG" "Found show path in libary" "$show" "$logPagePath"
				#ALERT "show path = '$show'"
				################################################################################
				# process page metadata
				################################################################################
				# if the show directory contains a nfo file defining the show
				#INFO "searching for metadata at '$show/tvshow.nfo'"
				if test -f "$show/tvshow.nfo";then
					#INFO "found metadata at '$show/tvshow.nfo'"
					# load update the tvshow.nfo file and get the metadata required for
					showMeta=$(cat "$show/tvshow.nfo")
					showTitle=$(ripXmlTag "$showMeta" "title")
					#INFO "showTitle = '$showTitle'"
					showTitle=$(cleanText "$showTitle")
					showTitle=$(alterArticles "$showTitle")
					#INFO "showTitle after cleanText() = '$showTitle'"
					if echo "$showMeta" | grep -q "<tvshow>";then
						# pipe the output to a black hole and cache
						episodeSearchResults=$(find "$show" -maxdepth 2 -mindepth 2 -type f -name '*.nfo' | wc -l)
						# make sure show has episodes
						if [ $episodeSearchResults -gt 0 ];then
							#ALERT "ADDING SHOW $show"
							if echo "$@" | grep -q -e "--parallel";then
								#ALERT "ADDING NEW PROCESS TO QUEUE $(jobs)"
								processShow "$show" "$showMeta" "$showTitle" "$webDirectory" &
								# pause execution while no cpus are open
								waitQueue 0.5 "$totalCPUS"
							else
								processShow "$show" "$showMeta" "$showTitle" "$webDirectory"
							fi
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
					#ALERT "ADDING MOVIE $show"
					if echo "$@" | grep -q -e "--parallel";then
						#ALERT "ADDING NEW PROCESS TO QUEUE"
						#ALERT "ADDING NEW PROCESS TO QUEUE $(jobs)"
						# this is a move directory not a show
						processMovie "$show" "$webDirectory" &
						# pause execution while no cpus are open
						waitQueue 0.5 "$totalCPUS"
					else
						# this is a move directory not a show
						processMovie "$show" "$webDirectory"
					fi
				fi
			done
		else
			ALERT "$show does not exist!"
		fi
		# update random backgrounds
		scanForRandomBackgrounds "$webDirectory"
	done
	# block for parallel threads here
	if echo "$@" | grep -q -e "--parallel";then
		blockQueue 1
	fi
	# add the end to the log, add the jump to top button and finish out the html
	addToLog "INFO" "FINISHED" "$(date)" "$logPagePath"
	{
		echo "</table>"
		echo "</div>"
		echo "</div>"
		# add footer
		echo "<?PHP";
		echo "include(\$_SERVER['DOCUMENT_ROOT'].'/footer.php');";
		echo "?>";
		echo "</body>"
		echo "</html>"
	} >> "$logPagePath"
	################################################################################
	# - sort and clean main indexes
	# - cleanup the new indexes by limiting the lists to 200 entries
	# - only run cleanup if the indexes exist as the indexes trigger header buttons
	################################################################################
	# fix permissions in the new and random indexes
	chown -R www-data:www-data "$webDirectory/new/"
	chown -R www-data:www-data "$webDirectory/random/"
	#########
	# SHOWS #
	#########
	if test -f "$webDirectory/shows/shows.index";then
		tempList=$(cat "$webDirectory/shows/shows.index" )
		echo "$tempList" | sort -u > "$webDirectory/shows/shows.index"
	fi
	if test -f "$webDirectory/new/shows.index";then
		# new list
		tempList=$(cat -n "$webDirectory/new/shows.index" | sort -uk2 | sort -nk1 | cut -f1- | tail -n 200 )
		echo "$tempList" > "$webDirectory/new/shows.index"
	fi
	if test -f "$webDirectory/new/episodes.index";then
		# new episodes
		tempList=$(cat -n "$webDirectory/new/episodes.index" | sort -uk2 | sort -nk1 | cut -f1- | tail -n 200 )
		echo "$tempList" > "$webDirectory/new/episodes.index"
	fi
	if test -f "$webDirectory/random/shows.index";then
		# new list
		tempList=$(cat "$webDirectory/random/shows.index" | uniq | tail -n 200 )
		echo "$tempList" > "$webDirectory/random/shows.index"
	fi
	if test -f "$webDirectory/random/episodes.index";then
		# new episodes
		tempList=$(cat "$webDirectory/random/episodes.index" | uniq | tail -n 200 )
		echo "$tempList" > "$webDirectory/random/episodes.index"
	fi
	##########
	# MOVIES #
	##########
	if test -f "$webDirectory/movies/movies.index";then
		tempList=$(cat "$webDirectory/movies/movies.index" )
		echo "$tempList" | sort -u > "$webDirectory/movies/movies.index"
	fi
	if test -f "$webDirectory/new/movies.index";then
		# new movies
		tempList=$(cat -n "$webDirectory/new/movies.index" | sort -uk2 | sort -nk1 | cut -f1- | tail -n 200 )
		echo "$tempList" > "$webDirectory/new/movies.index"
	fi
	if test -f "$webDirectory/random/movies.index";then
		# random movies
		tempList=$(cat "$webDirectory/random/movies.index" | uniq | tail -n 200 )
		echo "$tempList" > "$webDirectory/random/movies.index"
	fi
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
	if test -f /tmp/nfo2web.active;then
		rm /tmp/nfo2web.active
	fi
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
}
########################################################################
showHelp(){
	cat /usr/share/2web/help/nfo2web.txt
}
########################################################################
main(){
	debugCheck

	if [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		showHelp
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "nfo2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "nfo2web"
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		# verbose removal of found files allows files to be visible as they are removed
		# remove found .index files as they store web generated data
		echo "[INFO]: Reseting web cache states..."
		echo "[INFO]: Removing *.index files shows/movies/new..."
		find "$(webRoot)/shows/" -type f -name '*.index' -exec rm -v {} \;
		find "$(webRoot)/movies/" -type f -name '*.index' -exec rm -v {} \;
		find "$(webRoot)/new/" -type f -name '*.index' -exec rm -v {} \;
		echo "[INFO]: Reseting state files for shows/movies..."
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
	elif [ "$1" == "--CERTS" ] || [ "$1" == "CERTS" ] ;then
		# force update certs
		updateCerts 'yes'
	elif [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "--clean" ] || [ "$1" == "clean" ] ;then
		clean
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "nfo2web"
		lockProc "nfo2web"
		update "$@"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "nfo2web Version: "
		cat /usr/share/2web/version_nfo2web.cfg
	else
		checkModStatus "nfo2web"
		lockProc "nfo2web"
		update "$@"
		#main update "$@"
		# show the server link at the bottom of the interface
		showServerLinks
		# show the module links
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/movies/"
		drawLine
		echo "http://$(hostname).local:80/shows/"
		drawLine
		echo "http://$(hostname).local:80/settings/nfo.php"
		drawLine
	fi
}
main $@
