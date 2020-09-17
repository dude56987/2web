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
	data=$(echo "$data" | grep -v "/>")
	# check for valid string
	if validString "$tag" -q;then
		# ignore the case of the tag and rip the case used
		theTag=$(echo "$data" | grep --ignore-case --only-matching "<$tag>")
		theTag="${theTag//<}"
		theTag="${theTag//>}"
		# rip the tag from the data
		#echo "$data" | grep -i "<$theTag>" | sed "s/<$theTag>//g" | sed "s/<\/$theTag>//g"
		output=$(echo "$data" | grep "<$theTag>")
		output="${output//<$theTag>}"
		output="${output//<\/$theTag>}"
		# remove special characters that will gunk up the works
		#output=$(cleanText "$output")
		echo "$output"
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
		# check movie state as soon as posible processing
		if [ -f "$webDirectory/movies/$movieWebPath/state.cfg" ];then
			# a existing state was found
			currentSum=$(cat "$webDirectory/movies/$movieWebPath/state.cfg")
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
		elif [ -f "${moviePath//.nfo/.avi}" ];then
			videoPath="${moviePath//.nfo/.avi}"
			sufix=".avi"
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
		if echo "$videoPath" | grep ".mp3";then
			mediaType="audio"
			mimeType="audio/mp3"
		elif echo "$videoPath" | grep ".ogg";then
			mediaType="audio"
			mimeType="audio/ogg"
		elif echo "$videoPath" | grep ".ogv";then
			mediaType="video"
			mimeType="video/ogv"
		elif echo "$videoPath" | grep ".mp4";then
			mediaType="video"
			mimeType="video/mp4"
		elif echo "$videoPath" | grep ".avi";then
			mediaType="video"
			mimeType="video/avi"
		elif echo "$videoPath" | grep ".mpeg";then
			mediaType="video"
			mimeType="video/mpeg"
		elif echo "$videoPath" | grep ".mkv";then
			mediaType="video"
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		# start rendering the html
		{
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			#cat "$headerPagePath" | sed "s/href='/href='..\/..\//g"
			sed "s/href='/href='..\/..\//g" < "$headerPagePath"
			echo "<h1>$movieTitle</h1>"
		} > "$moviePagePath"
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
				ln -s "$thumbnailShort2.jpg" "$thumbnailPath.jpg"
				ln -s "$thumbnailShort2.jpg" "$thumbnailPathKodi.jpg"
			else
				if echo "$nfoInfo" | grep "fanart";then
					# pull the double nested xml info for the movie thumb
					thumbnailLink=$(ripXmlTag "$nfoInfo" "fanart")
					thumbnailLink=$(ripXmlTag "$thumbnailLink" "thumb")
					if validString "$thumbnailLink";then
						echo "[INFO]: Try to download found thumbnail..."
						echo "[INFO]: Thumbnail found at '$thumbnailLink'"
						thumbnailExt=".png"
						# download the thumbnail
						curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
						# link the downloaded thumbnail
						ln -s "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
					fi
				fi
				touch "$thumbnailPath.png"
				# check if the thumb download failed
				tempFileSize=$(wc --bytes < "$thumbnailPath.png")
				echo "[DEBUG]: file size $tempFileSize"
				if [ "$tempFileSize" -lt 15000 ];then
					echo "[ERROR]: Failed to find thumbnail inside nfo file!"
					# try to generate a thumbnail from video file
					echo "[INFO]: Attempting to create thumbnail from video source..."
					#tempFileSize=0
					tempTimeCode=1
					# - force the filesize to be large enough to be a complex descriptive thumbnail
					# - filesize of images is directly related to visual complexity
					while [ $tempFileSize -lt 15000 ];do
						# - place -ss in front of -i for speed boost in seeking to correct frame of source
						# - tempTimeCode is in seconds
						# - '-y' to force overwriting the empty file
						ffmpeg -y -ss $tempTimeCode -i "$movieVideoPath" -vframes 1 "$thumbnailPath.png"
						ln -s "$thumbnailPath.png" "$thumbnailPathKodi.png"
						# resize the image before checking the filesize
						convert "$thumbnailPath.png" -resize 400x200\! "$thumbnailPath.png"
						# get the size of the file, after it has been created
						tempFileSize=$(wc --bytes < "$thumbnailPath.png")
						# - increment the timecode to get from the video to find a thumbnail that is not
						#   a blank screen
						tempTimeCode=$(($tempTimeCode + 1))
						# if there is no file large enough after 60 attempts, the first 60 seconds of video
						if [ $tempTimeCode -gt 60 ];then
							# break the loop
							tempFileSize=16000
							rm "$thumbnailPath.png"
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
				echo "<hr>"
				# create a hard link
				echo "<ul>"
				echo "	<li>"
				echo "		<a href='$ytLink'>"
				echo "			$ytLink"
				echo "		</a>"
				echo "	</li>"
				echo "	<li>"
				# create link to .strm file
				echo "		<a href='$movieWebPath$sufix'>"
				echo "			$movieWebPath$sufix"
				echo "		</a>"
				echo "	</li>"
				echo "</ul>"
			} >> "$moviePagePath"
		elif echo "$videoPath" | grep "http";then
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$movieWebPath-poster$thumbnailExt' controls>"
				echo "<source src='$videoPath' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<hr>"
				# create a hard link
				if [ "$sufix" = ".strm" ];then
					echo "<a href='$videoPath'>"
					echo "$videoPath"
					echo "</a><br>"
				fi
				echo "<a href='$movieWebPath$sufix'>"
				echo "$movieWebPath$sufix"
				echo "</a>"
			} >> "$moviePagePath"
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$movieWebPath-poster$thumbnailExt' controls>"
				echo "<source src='$movieWebPath$sufix' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<hr>"
				# create a hard link
				echo "<a href='$movieWebPath$sufix'>"
				echo "$movieWebPath$sufix"
				echo "</a>"
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
			echo "<a class='showPageEpisode' href='$movieWebPath'>"
			echo "	<img loading='lazy' src='$movieWebPath/$movieWebPath-poster$thumbnailExt'>"
			echo "	<h3 class='title'>"
			echo "		$movieTitle"
			echo "	</h3>"
			echo "</a>"
		} > "$webDirectory/movies/$movieWebPath/movies.index"
	else
			echo "[WARNING]: The file '$moviePath' could not be found!"
	fi
	touch "$webDirectory/movies/$movieWebPath/state.cfg"
	getDirSum "$movieDir" > "$webDirectory/movies/$movieWebPath/state.cfg"
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
		elif [ -f "${episode//.nfo/.avi}" ];then
			videoPath="${episode//.nfo/.avi}"
			sufix=".avi"
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
		if echo "$videoPath" | grep ".mp3";then
			mediaType="audio"
			mimeType="audio/mp3"
		elif echo "$videoPath" | grep ".ogg";then
			mediaType="audio"
			mimeType="audio/ogg"
		elif echo "$videoPath" | grep ".ogv";then
			mediaType="video"
			mimeType="video/ogv"
		elif echo "$videoPath" | grep ".mp4";then
			mediaType="video"
			mimeType="video/mp4"
		elif echo "$videoPath" | grep ".mpeg";then
			mediaType="video"
			mimeType="video/mpeg"
		elif echo "$videoPath" | grep ".avi";then
			mediaType="video"
			mimeType="video/avi"
		elif echo "$videoPath" | grep ".mkv";then
			mediaType="video"
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		# start rendering the html
		{
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\/..\/..\//g"
			echo "<h1>$episodeShowTitle</h1>"
			echo "<h2>$episodeTitle</h2>"
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
					echo "[INFO]: Try to download found thumbnail..."
					echo "[INFO]: Thumbnail found at $thumbnailLink"
					thumbnailExt=".png"
					# download the thumbnail
					curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
				fi
				touch "$thumbnailPath.png"
				# check if the thumb download failed
				tempFileSize=$(wc --bytes < "$thumbnailPath.png")
				echo "[DEBUG]: file size $tempFileSize"
				if [ "$tempFileSize" -lt 15000 ];then
					echo "[ERROR]: Failed to find thumbnail inside nfo file!"
					# try to generate a thumbnail from video file
					echo "[INFO]: Attempting to create thumbnail from video source..."
					#tempFileSize=0
					tempTimeCode=1
					# - force the filesize to be large enough to be a complex descriptive thumbnail
					# - filesize of images is directly related to visual complexity
					while [ $tempFileSize -lt 15000 ];do
						# - place -ss in front of -i for speed boost in seeking to correct frame of source
						# - tempTimeCode is in seconds
						# - '-y' to force overwriting the empty file
						ffmpeg -y -ss $tempTimeCode -i "$episodeVideoPath" -vframes 1 "$thumbnailPath.png"
						ln -s "$thumbnailPath.png" "$thumbnailPathKodi.png"
						# resize the image before checking the filesize
						convert "$thumbnailPath.png" -resize 400x200\! "$thumbnailPath.png"
						# get the size of the file, after it has been created
						tempFileSize=$(wc --bytes < "$thumbnailPath.png")
						# - increment the timecode to get from the video to find a thumbnail that is not
						#   a blank screen
						tempTimeCode=$(($tempTimeCode + 1))
						# if there is no file large enough after 60 attempts, the first 60 seconds of video
						if [ $tempTimeCode -gt 60 ];then
							# break the loop
							tempFileSize=16000
							rm "$thumbnailPath.png"
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
				echo "<hr>"
				# create a hard link
				echo "<ul>"
				echo "	<li>"
				echo "		<a href='$ytLink'>"
				echo "			$ytLink"
				echo "		</a>"
				echo "	</li>"
				echo "	<li>"
				# create link to .strm file
				echo "		<a href='$episodePath$sufix'>"
				echo "			$episodePath$sufix"
				echo "		</a>"
				echo "	</li>"
				echo "</ul>"
			} >> "$episodePagePath"
			#echo "$videoPath" tr -d 'plugin://plugin.video.youtube/play/?video_id='
		elif echo "$videoPath" | grep "http";then
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$episodePath-thumb$thumbnailExt' controls>"
				echo "<source src='$videoPath' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<hr>"
				# create a hard link
				if [ "$sufix" = ".strm" ];then
					echo "<a href='$videoPath'>"
					echo "$videoPath"
					echo "</a><br>"
				fi
				echo "<a href='$episodePath$sufix'>"
				echo "$episodePath$sufix"
				echo "</a>"
			} >> "$episodePagePath"
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$episodePath-thumb$thumbnailExt' controls>"
				echo "<source src='$episodePath$sufix' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<hr>"
				# create a hard link
				echo "<a href='$episodePath$sufix'>"
				echo "$episodePath$sufix"
				echo "</a>"
			} >> "$episodePagePath"
		fi
		{
			echo "</body>"
			echo "</html>"
		} >> "$episodePagePath"
		################################################################################
		# add the episode to the show page
		################################################################################
		{
			#tempStyle="$episodeSeasonPath/$episodePath-thumb$thumbnailExt"
			#tempStyle="background-image: url(\"$tempStyle\")"
			#echo "<a class='showPageEpisode' style='$tempStyle' href='$episodeSeasonPath/$episodePath.html'>"
			echo "<a class='showPageEpisode' href='$episodeSeasonPath/$episodePath.html'>"
			#echo "	<div>"
			echo "	<img loading='lazy' src='$episodeSeasonPath/$episodePath-thumb$thumbnailExt'>"
			#echo "	</div>"
			echo "	<h3 class='title'>"
			echo "		$episodePath"
			echo "	</h3>"
			echo "</a>"
		} >> "$showPagePath"
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
	# blank the log for new log
	# check show state before processing
	if [ -f "$webDirectory/shows/$showTitle/state.cfg" ];then
		# a existing state was found
		currentSum=$(cat "$webDirectory/shows/$showTitle/state.cfg")
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
			# clear the log for the new log
			echo "" > "$showLogPath"
			addToLog "INFO" "Updating show" "$showTitle" "$logPagePath"
		fi
	else
		echo "[INFO]: No show state exists for $showTitle, updating..."
		addToLog "INFO" "Creating new show" "$showTitle" "$logPagePath"
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
	headerPagePath="$webDirectory/header.html"
	# build top of show webpage containing all of the shows meta info
	{
		tempStyle="html {background-image: url(\"$fanartPath\");background-size: 100%;}"
		echo "<html style='$tempStyle'>"
		echo "<head>"
		echo "<style>"
		echo "$tempStyle"
		cat /usr/share/nfo2web/style.css
		echo "</style>"
		echo "</head>"
		echo "<body>"
		cat "$headerPagePath" | sed "s/href='/href='..\/..\//g"
		echo "<h1>$showTitle</h1>"
		echo "<div class='episodeList'>"
	} > "$showPagePath"
	# generate the episodes based on .nfo files
	for season in "$show"/*;do
		echo "[INFO]: checking for season folder at '$season'"
		if [ -d "$season" ];then
			echo "[INFO]: found season folder at '$season'"
			# generate the season name from the path
			seasonName=$(echo "$season" | rev | cut -d'/' -f1 | rev)
			{
				echo "<div class='seasonHeader'>"
				echo "	<h2>"
				echo "		$seasonName"
				echo "	</h2>"
				echo "</div>"
				echo "<hr>"
				echo "<div class='seasonContainer'>"
			} >> "$showPagePath"
			# if the folder is a directory that means a season has been found
			# read each episode in the series
			for episode in "$season"/*.nfo;do
				processEpisode "$episode" "$showTitle" "$showPagePath" "$webDirectory"
			done
			{
				echo "</div>"
			} >> "$showPagePath"
		else
			echo "Season folder $season does not exist"
		fi
	done
	{
		echo "</div>"
		echo "</body>"
		echo "</html>"
	} >> "$showPagePath"
	# create show index information
	#showIndexPath="$webDirectory/shows/index.html"
	# add show page to the show index
	{
		echo "<a class='indexSeries' href='$showTitle/'>"
		echo "	<img loading='lazy' src='$showTitle/$posterPath'>"
		echo "	<div>"
		echo "		$showTitle"
		echo "	</div>"
		echo "</a>"
	#} >> "$showIndexPath"
	} > "$webDirectory/shows/$showTitle/shows.index"
	# update the libary sum
	touch "$webDirectory/shows/$showTitle/state.cfg"
	getDirSum "$show" > "$webDirectory/shows/$showTitle/state.cfg"
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
		echo "$errorDetails"
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
########################################################################
buildHomePage(){
	webDirectory=$1
	headerPagePath=$2
	echo "[INFO]: Building home page..."
	{
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath"
			echo "<div>"
			echo "Last updated on $(date)"
			echo "</div>"
	} > "$webDirectory/index.html"
	################################################################################
	# update random shows list
	randomShows=$(ls -1 "$webDirectory"/shows/*/shows.index| shuf -n 5)
	echo "[INFO]: Random Shows list = $randomShows"
	{
		echo "<hr>"
		echo "<h1>Random Shows</h1>"
		echo "<div>"
	} >>  "$webDirectory/index.html"
	echo "$randomShows" | while read -r line;do
		{
			# fix index links to work for homepage
			cat "$line" | sed "s/src='/src='shows\//g" | sed "s/href='/href='shows\//g"
		}	>>  "$webDirectory/index.html"
		echo "READING FILE $line = $(cat "$line")"
	done
	echo "</div>" >>  "$webDirectory/index.html"
	################################################################################
	updatedShows=$(ls -1tr "$webDirectory"/shows/*/shows.index| tail -n 5)
	echo "[INFO]: Updated Shows list = $updatedShows"
	{
		echo "<hr>"
		echo "<h1>Updated Shows</h1>"
		echo "<div>"
	} >>  "$webDirectory/index.html"
	echo "$updatedShows" | while read -r line;do
		{
			# fix index links to work for homepage
			cat "$line" | sed "s/src='/src='shows\//g" | sed "s/href='/href='shows\//g"
		}	>>  "$webDirectory/index.html"
		echo "READING FILE $line = $(cat "$line")"
	done
	echo "</div>" >>  "$webDirectory/index.html"
	################################################################################
	randomMovies=$(ls -1 "$webDirectory"/movies/*/movies.index| shuf -n 5)
	echo "[INFO]: Random Movies list = $randomMovies"
	{
		echo "<hr>"
		echo "<h1>Random Movies</h1>"
		echo "<div>"
	} >>  "$webDirectory/index.html"
	echo "$randomMovies" | while read -r line;do
		{
			# fix index links to work for homepage
			cat "$line" | sed "s/src='/src='movies\//g" | sed "s/href='/href='movies\//g"
		}	>>  "$webDirectory/index.html"
		echo "READING FILE $line = $(cat "$line")"
	done
	echo "</div>" >>  "$webDirectory/index.html"
	################################################################################
	updatedMovies=$(ls -1tr "$webDirectory"/movies/*/movies.index| tail -n 5)
	echo "[INFO]: Updated Movies list = $updatedMovies"
	{
		echo "<hr>"
		echo "<h1>Updated Movies</h1>"
		echo "<div>"
	} >>  "$webDirectory/index.html"
	echo "$updatedMovies" | while read -r line;do
		{
			# fix index links to work for homepage
			cat "$line" | sed "s/src='/src='movies\//g" | sed "s/href='/href='movies\//g"
		}	>>  "$webDirectory/index.html"
		echo "READING FILE $line = $(cat "$line")"
	done
	echo "</div>" >>  "$webDirectory/index.html"
	########################################################################
	{
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
		echo "########################################################################"
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		echo "[INFO]: Reseting web cache..."
		rm -rv /var/cache/nfo2web/web/*
		echo "[SUCCESS]: Web cache is now empty."
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
		# make sure the directories exist and have correct permissions
		mkdir -p "$webDirectory"
		chown -R www-data:www-data "$webDirectory"
		mkdir -p "$webDirectory/shows/"
		chown -R www-data:www-data "$webDirectory/shows/"
		mkdir -p "$webDirectory/movies/"
		chown -R www-data:www-data "$webDirectory/movies/"
		mkdir -p "$webDirectory/kodi/"
		chown -R www-data:www-data "$webDirectory/kodi/"
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
			echo "<div class='header'>"
			echo "<a class='headerButton' href='..'>"
			echo "HOME"
			echo "</a>"
			echo "<a class='headerButton' href='kodi'>"
			echo "KODI"
			echo "</a>"
			echo "<a class='headerButton' href='movies'>"
			echo "MOVIES"
			echo "</a>"
			echo "<a class='headerButton' href='shows'>"
			echo "SHOWS"
			echo "</a>"
			echo "<a class='headerButton' href='log.html'>"
			echo "LOG"
			echo "</a>"
			echo "<a class='headerButton' href='settings.php'>"
			echo "SETTINGS"
			echo "</a>"
			echo "</div>"
		} > "$headerPagePath"
		{
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath"
			echo "<table>"
		} > "$logPagePath"
		buildHomePage "$webDirectory" "$headerPagePath"
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
									echo "<html>"
									echo "<head>"
									echo "<style>"
									cat /usr/share/nfo2web/style.css
									echo "</style>"
									echo "</head>"
									echo "<body>"
									cat "$headerPagePath" | sed "s/href='/href='..\//g"
									# load all existing shows into the index
									cat "$webDirectory"/shows/*/shows.index
									echo "</body>"
									echo "</html>"
								} > "$showIndexPath"
							else
								echo "[ERROR]: Show has no episodes!"
								addToLog "ERROR" "Show has no episodes" "$show" "$logPagePath"
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
							echo "<html>"
							echo "<head>"
							echo "<style>"
							cat /usr/share/nfo2web/style.css
							echo "</style>"
							echo "</head>"
							echo "<body>"
							cat "$headerPagePath" | sed "s/href='/href='..\//g"
							# load the movie index parts
							cat "$webDirectory"/movies/*/movies.index
							echo "</body>"
							echo "</html>"
						} > "$movieIndexPath"
					fi
				# rebuild the homepage after processing each libary item
				buildHomePage "$webDirectory" "$headerPagePath"
				done
			fi
		done
		{
			# add the video file errors encountered during episode processing
			# these must be stored because of state checking
			#cat "$webDirectory"/shows/*/log.index
			addToLog "INFO" "FINISHED" "$(date)" "$logPagePath"
			echo "</table>"
			echo "</body>"
			echo "</html>"
		} >> "$logPagePath"
		{
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\//g"
			# load the movie index parts
			cat "$webDirectory"/movies/*/movies.index
			echo "</body>"
			echo "</html>"
		} > "$movieIndexPath"
		{
			# write the show index page after everything has been generated
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			cat "$headerPagePath" | sed "s/href='/href='..\//g"
			# load all existing shows into the index
			cat "$webDirectory"/shows/*/shows.index
			echo "</body>"
			echo "</html>"
		} > "$showIndexPath"
		# write the md5sum state of the libary for change checking
		#echo "$libarySum" > "$webDirectory/state.cfg"
		getLibSum > "$webDirectory/state.cfg"
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
