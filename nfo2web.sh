#! /bin/bash
########################################################################
# nfo2web generates websites from nfo filled directories
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
	#data=$(echo "$data" | txt2html --extract | sed 's/____NEWLINE____/ <br> /g')
	#data=$(echo "$data" | sed 's/____NEWLINE____/ <br> /g')
	data=$(echo "$data" | txt2html --linkonly | sed 's/____NEWLINE____/ <br> /g')
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
	echo -e "$possibleThumbPaths" | while read -r thumbPathToCheck;do
		possibleThumbExts=".jpg .png .tbn"
		for thumbExtToCheck in $possibleThumbExts;do
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
	# if moviepath exists
	if test -f "$moviePath";then
		# create the path sum for reconizing the libary path
		pathSum=$(echo -n "$movieDir" | sha512sum | cut -d' ' -f1)
		logPagePath="$webDirectory/log/$(date "+%s")_movie_${pathSum}.log"
		################################################################################
		# for each movie build a page for the movie
		nfoInfo=$(cat "$moviePath")
		# rip the movie title
		movieTitle=$(cleanXml "$nfoInfo" "title")
		movieTitle=$(alterArticles "$movieTitle" )
		movieYear=$(cleanXml "$nfoInfo" "year")
		moviePlot=$(ripXmlTagMultiLine "$nfoInfo" "plot")
		movieTrailer=$(ripXmlTag "$nfoInfo" "trailer")
		movieStudio=$(cleanXml "$nfoInfo" "studio")
		if [ $movieStudio == "" ];then
			movieStudio="unknown"
		fi
		movieGrade=$(cleanXml "$nfoInfo" "mpaa")
		if [ $movieGrade == "" ];then
			movieGrade="UNRATED"
		fi
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
		#if test -f "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg";then
		#	if checkDirSum "$webDirectory" "$movieDir";then
		#			updateInfo="$movieTitle\n\n$currentSum != $libarySum\n\n$(ls "$movieDir")\n\n$moviePath\n\n$1\n\n$movieDir"
		#			addToLog "UPDATE" "Updating Movie" "$updateInfo" "$logPagePath"
		#	else
			#		unchangedInfo="$movieTitle"
			#		addToLog "INFO" "Movie unchanged" "$unchangedInfo" "$logPagePath"
			#		return
			#fi
		#else
		#	addToLog "NEW" "Adding new movie " "Adding '$movieTitle' from '$movieDir'" "$logPagePath"
		#fi
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
				# check if nomedia files are enabled
				if ! yesNoCfgCheck "/etc/2web/kodi/nomediaFiles.cfg";then
					if test -f "$webDirectory/kodi/movies/$movieWebPath/.nomedia";then
						rm -v "$webDirectory/kodi/movies/$movieWebPath/.nomedia"
					fi
				else
					if cacheCheck "$webDirectory/movies/$movieWebPath/movies.index" 7;then
						# create the block to lockout updates from kodi clients after 1 weeks
						echo "No new media since: $(date)" > "$webDirectory/kodi/movies/$movieWebPath/.nomedia"
					fi
				fi
				# this means they are the same so no update needs run
				#addToLog "INFO" "Movie unchanged" "$unchangedInfo" "$logPagePath"
				return
			else
				# enable kodi client updates if the state has changed
				if test -f "$webDirectory/kodi/movies/$movieWebPath/.nomedia";then
					rm -v "$webDirectory/kodi/movies/$movieWebPath/.nomedia"
				fi
				addToLog "UPDATE" "Updating Movie" "$updateInfo" "$logPagePath"
			fi
		else
			addToLog "NEW" "Adding new movie " "Adding '$movieTitle' from '$movieDir'" "$logPagePath"
		fi
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
		# link the movie nfo file
		linkFile "$moviePath" "$webDirectory/movies/$movieWebPath/$movieWebPath.nfo"
		linkFile "$moviePath" "$webDirectory/kodi/movies/$movieWebPath/$movieWebPath.nfo"
		# show gathered info
		movieVideoPath="${moviePath//.nfo/$sufix}"

		# link the video from the libary to the generated website
		linkFile "$movieVideoPath" "$webDirectory/movies/$movieWebPath/$movieWebPath$sufix"

		linkFile "$movieVideoPath" "$webDirectory/kodi/movies/$movieWebPath/$movieWebPath$sufix"

		# remove .nfo extension and create thumbnail path template
		thumbnail="${moviePath//.nfo}-poster"
		# creating alternate thumbnail paths
		thumbnailShort="${moviePath//.nfo}"
		thumbnailPath="$webDirectory/movies/$movieWebPath/poster"
		thumbnailPathKodi="$webDirectory/kodi/movies/$movieWebPath/poster"

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
			convert -quiet "$webDirectory/movies/$movieWebPath/fanart.png" -blur 1x1 "$webDirectory/movies/$movieWebPath/fanart.png"
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
			convert -quiet "$webDirectory/movies/$movieWebPath/poster.png" -blur 1x1 "$webDirectory/movies/$movieWebPath/poster.png"
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

		thumbnailExt=".png"
		# generate a thumbnail from the xml data if it can be retreved
		if ! test -f "$thumbnailPath.png";then
			if echo "$nfoInfo" | grep -q "fanart";then
				# pull the double nested xml info for the movie thumb
				thumbnailLink=$(ripXmlTag "$nfoInfo" "fanart")
				thumbnailLink=$(ripXmlTag "$thumbnailLink" "thumb")
				if validString "$thumbnailLink" -q;then
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
			if [ "$tempFileSize" -le 0 ];then
				# generate a thumbnail from the video source
				generateThumbnailFromMedia "$videoPath" "$thumbnailPath" "$thumbnailPathKodi"
			fi
			# check if the thumb download failed
			if test -f "$thumbnailPath.png";then
				tempFileSize=$(wc --bytes < "$thumbnailPath.png")
			else
				tempFileSize=0
			fi
			# as a failsafe generate a image using the movie name and a color
			if [ "$tempFileSize" -le 0 ];then
				demoImage "$thumbnailPath.png" "$movieWebPath" "200" "500"
			fi
		fi
		thumbSum=$(echo -n "$thumbnailPath" | sha512sum | cut -d' ' -f1)
		# create the web thumbnail if it does not exist and a thumbnail path was found
		if ! test -f "$webDirectory/thumbnails/$thumbSum-web.png";then
			# convert the thumbnail into a web thumbnail
			convert -quiet "$webDirectory/movies/$movieWebPath/poster.png" -adaptive-resize "300x200" "$webDirectory/thumbnails/$thumbSum-web.png"
		fi
		if ! test -f "$webDirectory/movies/$movieWebPath/poster-web.png";then
			# link thumb to web directory
			linkFile "$webDirectory/thumbnails/$thumbSum-web.png" "$webDirectory/movies/$movieWebPath/poster-web.png"
		fi
		# store the movie title
		echo -n "$movieWebPath" > "$webDirectory/movies/$movieWebPath/movie.title"
		# store the movie plot
		echo -n "$moviePlot" > "$moviePagePath.plot"
		# store the direct link path
		echo -n "/kodi/movies/$movieWebPath/$movieWebPath$sufix" > "$moviePagePath.directLink"
		# build the cache link
		#echo -n "$movieWebPath$sufix" > "$moviePagePath.cacheLink"
		# store the title
		echo -n "$movieWebPath" > "$moviePagePath.title"
		# get the link for the trailer of the show
		echo -n "$movieTrailer" > "$webDirectory/movies/$movieWebPath/trailer.title"
		# get the studio name
		echo -n "$movieStudio" > "$webDirectory/movies/$movieWebPath/studio.title"
		# get the movie grade like G,PG,PG13,R,NC-17
		echo -n "$movieGrade" > "$webDirectory/movies/$movieWebPath/grade.title"
		# add tag playlists
		addPlaylist "$webDirectory/movies/$movieWebPath/movies.index" "year" "$movieYear" "movies"
		addPlaylist "$webDirectory/movies/$movieWebPath/movies.index" "studio" "$movieStudio" "movies"
		addPlaylist "$webDirectory/movies/$movieWebPath/movies.index" "grade" "$movieGrade" "movies"



		# link the video player
		linkFile "/usr/share/2web/templates/videoPlayer.php" "$moviePagePath"
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

		linkFile "$webDirectory/movies/$movieWebPath/movies.index" "$webDirectory/movies/$movieWebPath/$movieWebPath.index"

		# add the movie to the main movie index since it has been updated
		SQLaddToIndex "$webDirectory/movies/$movieWebPath/movies.index" "$webDirectory/data.db" "movies"
		SQLaddToIndex "$webDirectory/movies/$movieWebPath/movies.index" "$webDirectory/data.db" "all"

		# add to the poster and fanart indexes
		SQLaddToIndex "/movies/$movieWebPath/poster.png" "$webDirectory/backgrounds.db" "all_poster"
		SQLaddToIndex "/movies/$movieWebPath/fanart.png" "$webDirectory/backgrounds.db" "all_fanart"

		# add poster and fanart for this section
		SQLaddToIndex "/movies/$movieWebPath/poster.png" "$webDirectory/backgrounds.db" "movies_poster"
		SQLaddToIndex "/movies/$movieWebPath/fanart.png" "$webDirectory/backgrounds.db" "movies_fanart"

		# add the movie to the main movie index since it has been updated
		addToIndex "$webDirectory/movies/$movieWebPath/movies.index" "$webDirectory/movies/movies.index"

		# add the updated movie to the new movies index
		echo "$webDirectory/movies/$movieWebPath/movies.index" >> "$webDirectory/new/movies.index"
		echo "$webDirectory/movies/$movieWebPath/movies.index" >> "$webDirectory/new/all.index"
		# link movies to random movies
		linkFile "$webDirectory/movies/movies.index" "$webDirectory/random/movies.index"
		# add to random all
		echo "$webDirectory/movies/$movieWebPath/movies.index" >> "$webDirectory/random/all.index"

		# write the sum of the movie as a lock on the scan
		getDirSum "$movieDir" > "$webDirectory/movies/$movieWebPath/state_$pathSum.cfg"

		# add the path to the list of paths for duplicate checking
		if ! grep -c "$movieDir" "$webDirectory/movies/$movieWebPath/source_$pathSum.cfg";then
			# if the path is not in the file add it to the file
			echo "$movieDir" >> "$webDirectory/movies/$movieWebPath/source_$pathSum.cfg"
		fi
		# update update times for playlists
		date "+%s" > /var/cache/2web/web/new/all.cfg
		date "+%s" > /var/cache/2web/web/new/movies.cfg
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
checkForThumbnail(){
	thumbnail=$1
	thumbnailPath=$2
	thumbnailPathKodi=$3
	videoPath=$4
	nfoInfo=$5
	########################################################################
	tempFileSize=0
	#ALERT "Looking for thumbnail paths"
	# check for a local thumbnail
	if test -s "$thumbnailPath.jpg";then
		thumbnailExt=".jpg"
		#ALERT "Thumbnail already linked..."
	elif test -s "$thumbnailPath.jpeg";then
		thumbnailExt=".jpg"
		#ALERT "Thumbnail already linked..."
	elif test -L "$thumbnailPath.jpg";then
		thumbnailExt=".jpg"
		#ALERT "Thumbnail already linked..."
	elif test -L "$thumbnailPath.jpeg";then
		thumbnailExt=".jpg"
		#ALERT "Thumbnail already linked..."
	elif test -s "$thumbnailPath.png";then
		thumbnailExt=".png"
		#ALERT "Thumbnail already linked..."
	elif test -L "$thumbnailPath.png";then
		thumbnailExt=".png"
		#ALERT "Thumbnail already linked..."
	else
		# no thumbnail has been linked or downloaded already
		# check for thumbnails in the same directory as the media path
		if test -L "$thumbnail.png";then
			#ALERT "found PNG thumbnail..."
			thumbnailExt=".png"
			# link thumbnail into output directory
			linkFile "$thumbnail.png" "$thumbnailPath.png"
			linkFile "$thumbnail.png" "$thumbnailPathKodi.png"
		elif test -s "$thumbnail.png";then
			#ALERT "found PNG thumbnail..."
			thumbnailExt=".png"
			# link thumbnail into output directory
			linkFile "$thumbnail.png" "$thumbnailPath.png"
			linkFile "$thumbnail.png" "$thumbnailPathKodi.png"
		elif test -L "$thumbnail.jpg";then
			#ALERT "found JPG thumbnail..."
			thumbnailExt=".jpg"
			# link thumbnail into output directory
			linkFile "$thumbnail.jpg" "$thumbnailPath.jpg"
			linkFile "$thumbnail.jpg" "$thumbnailPathKodi.jpg"
			if ! test -f "$thumbnailPath.png";then
				convert -quiet "$thumbnail.jpg" "$thumbnailPath.png"
			fi
		elif test -s "$thumbnail.jpg";then
			#ALERT "found JPG thumbnail..."
			thumbnailExt=".jpg"
			# link thumbnail into output directory
			linkFile "$thumbnail.jpg" "$thumbnailPath.jpg"
			linkFile "$thumbnail.jpg" "$thumbnailPathKodi.jpg"
			if ! test -f "$thumbnailPath.png";then
				convert -quiet "$thumbnail.jpg" "$thumbnailPath.png"
			fi
		else
			# no existing thumbnail file was found or linked
			# look inside the nfo data for a thumbnail link
			# then download that thumbnail
			if echo "$nfoInfo" | grep -q "thumb";then
				thumbnailLink=$(ripXmlTag "$nfoInfo" "thumb")
				addToLog "DOWNLOAD" "Downloading Thumbnail" "Creating thumbnail from link '$thumbnailLink'" "$logPagePath"
				thumbnailExt=".png"
				# download the thumbnail
				downloadThumbnail "$thumbnailLink" "$thumbnailPath" "$thumbnailExt"
				# link the downloaded thumbnail to the kodi directory
				linkFile "$thumbnailPath$thumbnailExt" "$thumbnailPathKodi$thumbnailExt"
			else
				# no thumbnail could be discovered in any way
				# the extension must still be set for failsafe thumb generation methods below
				thumbnailExt=".png"
			fi
			#touch "$thumbnailPath$thumbnailExt"
			# check if the thumb download failed
		fi
		addToLog "DEBUG" "THUMBNAIL TESTING" "Thumbnail extension found to be '$thumbnailExt' for video path '$videoPath'"
		#INFO "[DEBUG]: file size $tempFileSize"
		if test -s $thumbnailPath$thumbnailExt;then
			INFO "Existing thumbnail file found '$thumbnailPath$thumbnailExt'!"
		elif test -L $thumbnailPath$thumbnailExt;then
			INFO "Existing thumbnail was linked already '$thumbnailPath$thumbnailExt'!"
		else
			# if the downloaded file is blank use mediainfo to determine if it is a video or audio link
			ALERT "[ERROR]: Failed to find thumbnail inside nfo file!"
			ALERT "thumbnail path = $thumbnailPath"
			# try to generate a thumbnail from video file
			#INFO "Attempting to create thumbnail from video source..."
			# load the json data from the media, this is cached for rescans
			mediaData="$(mediaJson "$videoPath")"
			# check if this is a video file using mediainfo
			if echo -n "$mediaData" | jq | grep --ignore-case "type" | grep --ignore-case -q "video";then
				addToLog "DOWNLOAD" "Generating Thumbnail" "Creating video thumbnail using media link: $videoPath"
				# generate a thumbnail from the video
				generateThumbnailFromMedia "$videoPath" "$thumbnailPath" "$thumbnailPathKodi"
				# verify the thumbnail was generated correctly by testing the file size
				if test -e "$thumbnailPath.png";then
					addToLog "DEBUG" "Generating Thumbnail" "Thumbnail path found at '$thumbnailPath.png' for video link '$videoPath'"
					tempFileSize=$(wc --bytes < "$thumbnailPath.png")
				else
					addToLog "DEBUG" "Generating Thumbnail" "Thumbnail path does not exist at '$thumbnailPath.png' for video link '$videoPath'"
					tempFileSize=0
				fi
				if [ "$tempFileSize" -le 15000 ];then
					addToLog "WARNING" "Failsafe Thumbnail" "No thumbnail could be created from the video. Creating a failsafe thumbnail at '$thumbnailPath.png' using only the video file name."
					# Generate a generic uniquely identifiable image using the demo image generator
					# - this is the last resort if no thumbnail could be generated any other way
					demoImage "$thumbnailPath.png" "$(basename "$thumbnailPath" | sed "s/-thumb$//g")" "800" "600"
				fi
			elif echo -n "$mediaData" | jq | grep --ignore-case "type" | grep --ignore-case -q "audio";then
				ALERT "This is a audio file, generate a audio waveform..."
				if yesNoCfgCheck "/etc/2web/nfo/generateAudioWaveforms.cfg";then
					generateWaveform "$videoPath" "$thumbnailPath" "$thumbnailPathKodi"
				else
					demoImage "$thumbnailPath.png" "$(basename "$thumbnailPath" | sed "s/-thumb$//g")" "800" "600"
				fi
				# check the file was created correctly
				if test -e "$thumbnailPath.png";then
					addToLog "DEBUG" "Generating Thumbnail" "Thumbnail path found at '$thumbnailPath.png' for video link '$videoPath'"
					tempFileSize=$(wc --bytes < "$thumbnailPath.png")
				else
					addToLog "DEBUG" "Generating Thumbnail" "Thumbnail path does not exist at '$thumbnailPath.png' for audio link '$videoPath'"
					tempFileSize=0
				fi
				# as a failsafe generate a image using the name and a hash based color
				if [ "$tempFileSize" -le 15000 ];then
					addToLog "WARNING" "Failsafe Thumbnail" "No thumbnail could be created from the audio. Creating a failsafe thumbnail at '$thumbnailPath.png' using only the audio file name."
					demoImage "$thumbnailPath.png" "$(basename "$thumbnailPath" | sed "s/-thumb$//g")" "800" "600"
				fi
			else
				ALERT "This media could not be determined to be any type of media try 'mediainfo \"$videoPath\"'"
				addToLog "ERROR" "Could not create Thumbnail" "This media could not be determined to be any type of media try 'mediainfo $videoPath'"
			fi
		fi
	fi
}
########################################################################
function drawKodiPlayerButton(){
	# drawKodiPlayerButton $link
	#
	# write php code to draw the kodi playback buttons for pages
	#
	# RETURN STDOUT
	link=$1
	# cleanup any quotations from the link because they will break the entire generated webpage
	link=$(echo "$link" | sed 's/\"/\\"/g')
	# new versions of kodi no longer can encode spaces in urls
	link=$(echo "$link" | sed 's/ /%20/g')
	echo "<?PHP"
	# if the kodi2web mod is enabled
	echo "if(detectEnabledStatus('kodi2web')){"
	# if the count of players is greater than zero
	echo "if(count(scanDir('/etc/2web/kodi/players.d/')) > 2){"
	# draw the play on kodi button using the resolver
	echo "echo \"<a class='button hardLink' href='/kodi-player.php?url=http://$(hostname).local$link'>\";"
	echo "echo \"	🇰Play on KODI\";"
	echo "echo \"</a>\";"
	echo "}"
	echo "}"
	echo "?>"
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
	# check the episode file path exists before anything is done
	if [ -f "$episode" ];then
		# for each episode build a page for the episode
		nfoInfo=$(cat "$episode")
		# rip the episode title
		episodeShowTitle=$(cleanText "$episodeShowTitle")
		episodeShowTitle=$(alterArticles "$episodeShowTitle")
		episodeTitle=$(cleanXml "$nfoInfo" "title")
		episodePlot=$(ripXmlTagMultiLine "$nfoInfo" "plot")
		episodeSeason=$(cleanXml "$nfoInfo" "season")
		episodeAired=$(ripXmlTag "$nfoInfo" "aired")
		episodeSeason=$(echo "$episodeSeason" | sed "s/^[0]\{,3\}//g")
		if ! test -f "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/show.title";then
			# get the tvshow.nfo data
			tvshowData=$(cat "$webDirectory/shows/$episodeShowTitle/tvshow.nfo")
			episodeStudio=$(cleanXml "$tvshowData" "studio")
			if [ $episodeStudio == "" ];then
				episodeStudio="unknown"
			fi
			# get the episode rating from the tvshow.nfo
			episodeGrade=$(cleanXml "$tvshowData" "mpaa")
			if [ $episodeGrade == "" ];then
				episodeGrade="UNRATED"
			fi
		fi
		if [ "$episodeSeason" -le 0 ];then
			episodeSeason="0000"
		elif [ "$episodeSeason" -lt 10 ];then
			# add a zero to make it format correctly
			episodeSeason="000$episodeSeason"
		elif [ "$episodeSeason" -lt 100 ];then
			# add a zero to make it format correctly
			episodeSeason="00$episodeSeason"
		elif [ "$episodeSeason" -lt 1000 ];then
			episodeSeason="0$episodeSeason"
		elif [ "$episodeSeason" -ge 1000 ];then
			episodeSeason="$episodeSeason"
		else
			episodeSeason="0000"
		fi
		episodeSeasonPath="Season $episodeSeason"
		episodeNumber=$(cleanXml "$nfoInfo" "episode")
		# remove leading zeros
		episodeNumber=$(echo "$episodeNumber" | sed "s/^[0]\{,3\}//g")
		if [ "$episodeNumber" -le 0 ];then
			episodeNumber="0000"
		elif [ "$episodeNumber" -lt 10 ];then
			# add a zero to make it format correctly
			episodeNumber="000$episodeNumber"
		elif [ "$episodeNumber" -lt 100 ];then
			# add a zero to make it format correctly
			episodeNumber="00$episodeNumber"
		elif [ "$episodeNumber" -lt 1000 ];then
			episodeNumber="0$episodeNumber"
		elif [ "$episodeNumber" -ge 1000 ];then
			episodeNumber="$episodeNumber"
		else
			episodeNumber="0000"
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

		# link the episode nfo file
		linkFile "$episode" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		linkFile "$episode" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.nfo"
		# show info gathered
		episodeVideoPath="${episode//.nfo/$sufix}"

		#TODO: here is where .strm files need checked for Plugin: eg. youtube strm files in plugin format
		if echo "$videoPath" | grep -q --ignore-case "plugin://plugin.video.youtube";then
			ALERT "$videoPath"
			# convert the plugin .strm link to a regular youtube link
			#plugin://plugin.video.youtube/play/?video_id=
			# change the video path into a video id to make it embedable
			videoPath=${videoPath//*video_id=}
			videoPath=${videoPath//*watch?v=}
			videoPath=${videoPath//*shorts\/}
			# create final plugin video path
			videoPath="https://youtube.com/watch?v=$videoPath"
		fi

		# remove .nfo extension and create thumbnail path
		thumbnail="${episode//.nfo}-thumb"
		thumbnailPath="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"
		thumbnailPathKodi="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath-thumb"

		# check for the thumbnail and link it
		checkForThumbnail "$thumbnail" "$thumbnailPath" "$thumbnailPathKodi" "$videoPath" "$nfoInfo"

		resolverUrl=""
		# check for plugin links and convert the .strm plugin links into ytdl-resolver.php links
		if echo "$sufix" | grep -q --ignore-case "strm";then
			createDir "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"
			tempPath="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"

			mediaData="$(mediainfo "$videoPath")"

			# change the video path into a video id to make it embedable
			# get the contents of the stream file as the link
			ytLink="$videoPath"
			# check the .strm link to see if it is a video link, a audio link or a link that must be ran though the resolver
			if echo "$mediaData" | grep -q --ignore-case "^video";then
				# direct link to video
				resolverUrl="$ytLink"
			elif echo "$mediaData" | grep -q --ignore-case "^audio";then
				# direct link to audio
				resolverUrl="$ytLink"
			else
				# redirect to the resolver
				resolverUrl="http://$(hostname).local/ytdl-resolver.php?url=\"$videoPath\""
				# build the cache link
				echo -n "$resolverUrl" > "$episodePagePath.cacheLink"
			fi
			# this is used for generating kodi playback links, kodi has a easier time playing .strm files
			strmUrl="http://$(hostname).local/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
			echo -n "$strmUrl" > "$episodePagePath.strmLink"

			# split up airdate data to check if caching should be done
			airedYear=$(echo "$episodeAired" | cut -d'-' -f1)
			airedMonth=$(echo "$episodeAired" | cut -d'-' -f2)
			# if the config option is set to cache new episodes
			# - cache new links in batch processing mode
			if [ "$(cat /etc/2web/cacheNewEpisodes.cfg)" == "yes" ] ;then
				yt_download_command=""
				#addToLog "DEBUG" "Checking episode for caching" "$showTitle - $episodePath" "$logPagePath"
				# if the airdate was this year
				if [ $((10#$airedYear)) -eq "$((10#$(date +"%Y")))" ];then
					# if the airdate was this month
					if [ $((10#$airedMonth)) -eq "$((10#$(date +"%m")))" ];then
						addToLog "DOWNLOAD" "Caching new episode" "$showTitle - $episodePath" "$logPagePath"
						# cache the video if it is from this month
						# - only newly created videos get this far into the process to be cached
						tempSum=$(echo -n "$ytLink" | sha512sum | cut -d' ' -f1)
						# create the directory to store the cached data
						mkdir "$webDirectory/RESOLVER-CACHE/$tempSum/"
						# create the command for caching
						temp_cache_command="/var/cache/2web/generated/yt-dlp/yt-dlp --max-filesize '6g' --retries 'infinite' --no-mtime --fragment-retries 'infinite' -f best --embed-subs --abort-on-error --abort-on-unavailable-fragments --embed-thumbnail --recode-video mp4 --continue --write-info-json -o '$webDirectory/RESOLVER-CACHE/$tempSum/video.mp4' -c '$ytLink'"
						# store processing info into a log file
						{
							echo "Video link cached with nfo2web because it was added and was orignally posted this same month"
							echo "Orignal Link = '$videoPath'"
							echo "Youtube Link = '$ytLink'"
							echo "SHA Source = '$ytLink'"
							echo "SHA Sum = '$tempSum'"
							echo "____COMMAND____"
							echo "$temp_cache_command"
						} > "$webDirectory/RESOLVER-CACHE/$tempSum/data_nfo.log"
						chown -R www-data:www-data "$webDirectory/RESOLVER-CACHE/$tempSum/"
						# launch the command in the queue scheduler
						queue2web --add idle "$temp_cache_command"
					fi
				fi
			fi
			# build the strm file
			echo "$resolverUrl" > "$tempPath"
			# build the direct link
			echo -n "$videoPath" > "$episodePagePath.directLink"
		else
			# link the video from the libary to the generated website
			linkFile "$episodeVideoPath" "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
			linkFile "$episodeVideoPath" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix"
			# build the direct link
			echo -n "/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath$sufix" > "$episodePagePath.directLink"
		fi

		# get the extension
		thumbnailExt=$(getThumbnailExt "$thumbnailPath")
		# convert the found episode thumbnail into a web thumb
		thumbSum=$(echo -n "$thumbnailPath" | sha512sum | cut -d' ' -f1)
		if ! test -f "$webDirectory/thumbnails/$thumbSum-web.png";then
			# store the thumbnail inside the thumbnails directory
			convert -quiet "$thumbnailPath$thumbnailExt" -resize "300x200" "$webDirectory/thumbnails/$thumbSum-web.png"
		fi
		if ! test -f "$thumbnailPath-web.png";then
			# link the thumbnail into the web directory
			linkFile "$webDirectory/thumbnails/$thumbSum-web.png" "$thumbnailPath-web.png"
		fi
		if echo "$videoPath" | grep -q --ignore-case "youtube.com";then
			# change the video path into a video id to make it embedable
			yt_id=${videoPath//*video_id=}
			yt_id=${yt_id//*watch?v=}
			yt_id=${yt_id//*shorts\/}
			#INFO "yt-id = $yt_id"
			ytLink="https://youtube.com/watch?v=$yt_id"
			ytLink="$videoPath"

			cacheRedirect="/ytdl-resolver.php?url=\"$ytLink\""
			vlcCacheRedirect="/ytdl-resolver.php?url=\\\"$ytLink\\\""
			echo "$episodeAired" > "$episodePagePath.date"
		else
			# store the episode aired date
			echo "$episodeAired" > "$episodePagePath.date"
			# build variables
		fi

		# build the values
		epNum="s${episodeSeason}e${episodeNumber}"
		# only write season data once
		if ! test -f "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/show.title";then
			# add the show name data
			echo -n "$episodeShowTitle" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/show.title"
			# add the show studio
			echo -n "$episodeStudio" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/studio.title"
			# get the mpaa rating
			echo -n "$episodeGrade" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/grade.title"
		fi
		# build the episode number
		echo -n "$epNum" > "$episodePagePath.numTitle"
		# build the season title
		echo -n "$episodeSeason" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.title"
		# build the title
		echo -n "$episodeTitle" > "$episodePagePath.title"
		# build the plot
		echo -n "$episodePlot" > "$episodePagePath.plot"

		# link the player
		linkFile "/usr/share/2web/templates/videoPlayer.php" "$episodePagePath"

		episodeSubSearch="$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath/"
		episodeSubSearchPath=$(echo "$episode" | rev | cut -d'/' -f2- | rev)

		subSavePath="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.srt"

		subSavePath="$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/"

		# check for existing subtitles
		existingSubs="no"
		if test -f "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.srt";then
			existingSubs="yes"
		elif test -f "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.sub";then
			existingSubs="yes"
		elif test -f "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.idx";then
			existingSubs="yes"
		fi
		# if existing subs is no then  skip this
		if [ "$existingSubs" == "no" ];then
			# copy over subtitles for episodes
			if test -f "$episodeSubSearchPath/$episodePath.srt";then
				linkFile "$episodeSubSearchPath/$episodePath.srt" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.srt"
			elif test -f "$episodeSubSearchPath/$episodePath.sub";then
				linkFile "$episodeSubSearchPath/$episodePath.sub" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.sub"
			elif test -f "$episodeSubSearchPath/$episodePath.idx";then
				linkFile "$episodeSubSearchPath/$episodePath.idx" "$webDirectory/kodi/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.idx"
			fi
		fi
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

		#if [ "$episodeNumber" -eq 1 ];then
		#	echo -ne "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		#else
		#	echo -ne "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" >> "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/season.index"
		#fi

		# mark the season as updated by creating a episode season path in the web directory as a season lock file
		# - this is used to generate a .nomedia file
		echo -ne "$(date)" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath.index"

		echo -ne "$tempEpisodeSeasonThumb" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index"

		#$episodeSum=$(echo "/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.php" | sha512sum | cut -d' ' -f1)
		#echo -ne "$episodeSum" > "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.cfg"

		# add episodes to new indexes
		SQLaddToIndex "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" "$webDirectory/data.db" "episodes"
		SQLaddToIndex "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" "$webDirectory/data.db" "all"

		date "+%s" > /var/cache/2web/web/new/episodes.cfg
		date "+%s" > /var/cache/2web/web/new/all.cfg

		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" >> "$webDirectory/new/episodes.index"
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" >> "$webDirectory/new/all.index"
		# random indexes
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" >> "$webDirectory/random/episodes.index"
		echo "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" >> "$webDirectory/random/all.index"

		addPlaylist "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" "year" "$airedYear" "episodes"
		addPlaylist "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" "studio" "$episodeStudio" "episodes"
		addPlaylist "$webDirectory/shows/$episodeShowTitle/$episodeSeasonPath/$episodePath.index" "grade" "$episodeGrade" "episodes"

	else
		ALERT "[WARNING]: The file '$episode' could not be found!"
	fi
}
################################################################################
processShow(){
	show=$1
	showMeta=$2
	showTitle=$3
	webDirectory=$4
	logPagePath="$webDirectory/settings/log.php"
	showLogPath="$webDirectory/shows/$showTitle/log.index"
	# create the path sum for reconizing the libary path
	pathSum=$(echo -n "$show" | sha512sum | cut -d' ' -f1)
	# generate the path sum
	showLogPath="$webDirectory/log/$(date "+%s")_show_${pathSum}.log"
	# create directory
	INFO "creating show directory at '$webDirectory/shows/$showTitle/'"
	createDir "$webDirectory/shows/$showTitle/"
	# link stylesheet
	linkFile "$webDirectory/style.css" "$webDirectory/shows/$showTitle/style.css"
	# check show state before processing
	if [ -f "$webDirectory/shows/$showTitle/state_$pathSum.cfg" ];then
		# a existing state was found
		currentSum=$(cat "$webDirectory/shows/$showTitle/state_$pathSum.cfg")
		libarySum=$(getDirSum "$show")
		updateInfo="$showTitle\n<br>$currentSum != $libarySum\n<br>$(ls "$show")\n<br>$show"
		# if the current state is the same as the state of the last update
		if [ "$libarySum" == "$currentSum" ];then
			# check if nomedia files are disabled
			if yesNoCfgCheck "/etc/2web/kodi/nomediaFiles.cfg";then
				# this means they are the same so no update needs run
				# if the show is unchanged check for the time it has been unchanged for more than 7 days
				if cacheCheck "$webDirectory/shows/$showTitle/shows.index" 7;then
					# create the block to lockout updates from kodi clients after 1 weeks
					# - this should reset when the software is updated and all content will be rescanned by clients
					if ! test -f  "$webDirectory/kodi/shows/$showTitle/.nomedia";then
						echo "No new media since $(date)" > "$webDirectory/kodi/shows/$showTitle/.nomedia"
					fi
				fi
			else
				# if nomedia files are disabled remove any existing nomedia files
				if test -f  "$webDirectory/kodi/shows/$showTitle/.nomedia";then
					rm -v "$webDirectory/kodi/shows/$showTitle/.nomedia"
				fi
			fi
			#INFO "State is unchanged for $showTitle, no update is needed."
			#INFO "[DEBUG]: $currentSum == $libarySum"
			#addToLog "INFO" "Show unchanged" "$showTitle" "$logPagePath"
			return
		else
			# enable kodi client updates
			if test -f "$webDirectory/kodi/shows/$showTitle/.nomedia";then
				rm -v "$webDirectory/kodi/shows/$showTitle/.nomedia"
			fi
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

	# remove existing fanart and posters
	# - this will refresh generated posters and fanart
	# - this code block only runs if the series has changed and the contents of the poster and fanart might have changed too
	rm -v "$webDirectory/shows/$showTitle/poster.png"
	rm -v "$webDirectory/shows/$showTitle/fanart.png"
	rm -v "$webDirectory/shows/$showTitle/poster-web.png"
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
			seasonSum=$(echo -n "$season" | sha512sum | cut -d' ' -f1)

			# get the season folder sum, youtube channels can have 9999 max episodes a season
			if test -f "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg";then
				currentSeasonSum=$(cat "$webDirectory/shows/$showTitle/$seasonName/state_${seasonSum}_season.cfg")
			else
				currentSeasonSum="0"
			fi

			#
			libarySeasonSum=$(getDirSum "$season")
			#if echo "$libarySeasonSum" | grep -q "$currentSeasonSum";then
			#addToLog "INFO" "Season" "$libarySeasonSum ?= $currentSeasonSum" "$logPagePath"
			if [ "$libarySeasonSum" == "$currentSeasonSum" ];then
				# this season folder is unchanged ignore it
				################################################################################
				# check if the season lock file has been updated more than 7 days ago
				# - seasons need to be checked indivually because some shows can have thousands of episodes a year
				# - This will only add .nomedia to seasons of active shows, shows with no new episodes will add
				#   a .nomedia file to the entire show after 7 days

				# check if nomedia files are disabled
				if ! yesNoCfgCheck "/etc/2web/kodi/nomediaFiles.cfg";then
					# if nomedia files are disabled remove any existing nomedia files
					if test -f "$webDirectory/kodi/shows/$showTitle/$seasonName/.nomedia";then
						rm -v "$webDirectory/kodi/shows/$showTitle/$seasonName/.nomedia"
					fi
				else
					# this means they are the same so no update needs run
					# if the show is unchanged check for the time it has been unchanged for more than 7 days
					if cacheCheck "$webDirectory/shows/$showTitle/$seasonName.index" 7;then
						# create the block to lockout updates from kodi clients after 1 weeks
						# - this should reset when the software is updated and all content will be rescanned by clients
						if ! test -f "$webDirectory/kodi/shows/$showTitle/$seasonName/.nomedia";then
							echo "No new media since $(date)" > "$webDirectory/kodi/shows/$showTitle/$seasonName/.nomedia"
						fi
					fi
				fi
				################################################################################
				#INFO "Season Unchanged $season"
				addToLog "INFO" "Season Unchanged" "$showTitle $seasonName\n$season" "$logPagePath"
			else
				# update the altered season files
				################################################################################
				# enable kodi client updates
				if test -f "$webDirectory/kodi/shows/$showTitle/$seasonName/.nomedia";then
					rm -v "$webDirectory/kodi/shows/$showTitle/$seasonName/.nomedia"
				fi
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
		#thumbnailList=$(find "$webDirectory/shows/$showTitle/" -name "*-web.png" | sort | tac | tail -n 24 | shuf |sed "s/ /\ /g"| sed -z "s/\n/ /g" )
		thumbnailList=$(find "$webDirectory/shows/$showTitle/" -name "*-web.png" -printf '%p\n' | sort | tail -n 24 | shuf | sed "s/\n/ /g" )
		montage $thumbnailList -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/shows/$showTitle/fanart.png"
		#montage "$webDirectory"/shows/"$showTitle"/*/*-web.png -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/shows/$showTitle/fanart.png"
		#montage $thumbnailList -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/shows/$showTitle/fanart.png"
		if test -f "$webDirectory/shows/$showTitle/fanart-0.png";then
			cp "$webDirectory/shows/$showTitle/fanart-0.png" "$webDirectory/shows/$showTitle/fanart.png"
			rm "$webDirectory/shows/$showTitle/fanart-"*.png
		fi
		convert "$webDirectory/shows/$showTitle/fanart.png" -trim -blur 1x1 "$webDirectory/shows/$showTitle/fanart.png"
		echo "Creating the fanart image from webpage..."
		convert "$webDirectory/shows/$showTitle/fanart.png" -adaptive-resize 1920x1080\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 1920x1080 -gravity center caption:"$showTitle" -composite "$webDirectory/shows/$showTitle/fanart.png"
		# error log
		addToLog "WARNING" "Could not find fanart.[png/jpg]" "$showTitle has no $show/fanart.[png/jpg], Generating one at <a href='/$show/fanart.png'>$show/fanart.png</a>" "$logPagePath"
	fi
	if ! test -f "$webDirectory/shows/$showTitle/poster.png";then
		#thumbnailList=$(find "$webDirectory/shows/$showTitle/" -name "*-web.png" | sort | tac | tail -n 8 | shuf |sed "s/ / /g"| sed -z "s/\n/ /g" )
		thumbnailList=$(find "$webDirectory/shows/$showTitle/" -name "*-web.png" -printf '%p\n' | sort | tail -n 8 | shuf | sed "s/\n/ /g" )
		montage $thumbnailList -background black -geometry 800x600\!+0+0 -tile 2x4 "$webDirectory/shows/$showTitle/poster.png"
		#montage "$webDirectory"/shows/"$showTitle"/*/*-web.png -background black -geometry 800x600\!+0+0 -tile 2x4 "$webDirectory/shows/$showTitle/poster.png"
		#montage $thumbnailList -background black -geometry 800x600\!+0+0 -tile 2x4 "$webDirectory/shows/$showTitle/poster.png"
		if test -f "$webDirectory/shows/$showTitle/poster-0.png";then
			# to many images exist use only first set
			cp "$webDirectory/shows/$showTitle/poster-0.png" "$webDirectory/shows/$showTitle/poster.png"
			# remove excess images
			rm "$webDirectory/shows/$showTitle/poster-"*.png
		fi
		convert "$webDirectory/shows/$showTitle/poster.png" -trim -blur 1x1 "$webDirectory/shows/$showTitle/poster.png"
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
		echo "	<div class='indexSeriesTitle'>"
		echo "		$showTitle"
		echo "	</div>"
		#echo "  </marquee>"
		echo "</a>"
	} > "$webDirectory/shows/$showTitle/shows.index"

	linkFile "$webDirectory/shows/$showTitle/shows.index" "$webDirectory/shows/$showTitle/tvshow.index"

	addPlaylist "$webDirectory/shows/$showTitle/shows.index" "studio" "$episodeStudio" "shows"
	addPlaylist "$webDirectory/shows/$showTitle/shows.index" "grade" "$episodeGrade" "shows"

	# add the show to the main show index since it has been updated
	SQLaddToIndex "$webDirectory/shows/$showTitle/shows.index" "$webDirectory/data.db" "shows"
	SQLaddToIndex "$webDirectory/shows/$showTitle/shows.index" "$webDirectory/data.db" "all"
	# update the last updated times
	date "+%s" > /var/cache/2web/web/new/all.cfg
	date "+%s" > /var/cache/2web/web/new/shows.cfg

	# add show poster and fanart information
	SQLaddToIndex "/shows/$showTitle/poster.png" "$webDirectory/backgrounds.db" "all_poster"
	SQLaddToIndex "/shows/$showTitle/fanart.png" "$webDirectory/backgrounds.db" "all_fanart"

	# create section specific poster and fanart indexes
	SQLaddToIndex "/shows/$showTitle/poster.png" "$webDirectory/backgrounds.db" "shows_poster"
	SQLaddToIndex "/shows/$showTitle/fanart.png" "$webDirectory/backgrounds.db" "shows_fanart"

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
	if ! grep -c "$show" "$webDirectory/shows/$showTitle/sources.cfg";then
		# if the path is not in the file add it to the file
		echo "$show" >> "$webDirectory/shows/$showTitle/sources.cfg"
	fi

	# update the libary sum
	touch "$webDirectory/shows/$showTitle/state_$pathSum.cfg"
	getDirSum "$show" > "$webDirectory/shows/$showTitle/state_$pathSum.cfg"

	date "+%s" > /var/cache/2web/web/new/all.cfg
	date "+%s" > /var/cache/2web/web/new/episodes.cfg
}
########################################################################
getLibSum(){
	# find all state sums for shows and create a collective sum
	totalList=""
	while read -r line;do
		# read each line and load the file
		tempList=$(ls -R "$line")
		# add value to list
		totalList="$totalList$tempList"
	done < /etc/2web/nfo/libaries.cfg
	# convert lists into sum
	tempLibList="$(echo -n "$totalList" | sha512sum | cut -d' ' -f1)"
	# write the sum to stdout
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
function buildShowIndex(){
	webDirectory="$1"
	linkFile "/usr/share/2web/templates/shows.php" "$webDirectory/shows/index.php"
}
########################################################################
getDirSumByTime(){
	line=$1
	# get the sum of the directory modification time
	totalList=$(stat --format="%Y" "$line")
	# convert lists into sum
	tempLibList="$(echo -n "$totalList" | sha512sum | cut -d' ' -f1)"
	# write the sum to stdout
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
	rm -rv $(webRoot)/kodi/movies/
	rm -rv $(webRoot)/random/movies.index
	rm -rv $(webRoot)/new/movies.index
	rm -rv $(webRoot)/shows/*
	rm -rv $(webRoot)/kodi/shows/
	rm -rv $(webRoot)/random/shows.index
	rm -rv $(webRoot)/random/episodes.index
	rm -rv $(webRoot)/new/shows.index
	rm -rv $(webRoot)/new/episodes.index
	rm -rv $(webRoot)/tags/*.index
	rm -rv $(webRoot)/sums/nfo2web_*.cfg || echo "No file sums found..."
	# remove sql data
	sqlite3 --cmd ".timeout 60000" $(webRoot)/data.db "drop table shows;"
	sqlite3 --cmd ".timeout 60000" $(webRoot)/data.db "drop table movies;"
	sqlite3 --cmd ".timeout 60000" $(webRoot)/data.db "drop table episodes;"
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
function cleanDatabase(){
	webDirectory=$(webRoot)
	# find and delete directories for show/movie if the show/movie contains broken links
	cleanMediaSection "$webDirectory/movies/"
	cleanMediaSection "$webDirectory/shows/"
	# clean index files
	cleanMediaIndexFile "$webDirectory/shows/" "shows.index"
	cleanMediaIndexFile "$webDirectory/movies/" "movies.index"

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
function nfo2web_watch_service(){
	# This launches the service that will update shows as filesystem changes are
	# detected. This is a more optimized way to manage updates to existing media
	# directories.
	#
	# - You must still run a full scan every so often if the service goes down
	# - This should update shows when they have changed
	addToLog "INFO" "Starting Watch Service" "Starting the 2web watcher service"
	ALERT "Starting the 2web watcher service..."
	# load the web root
	webDirectory=$(webRoot)
	# create the lock directory path
	createDir /tmp/2web/
	# Calc the timeout for hours in seconds
	# - this will force the service to reload and do a full scan after this period
	timeout=$(( ( (60 * 60) * 6 ) ))
	# forever loop for service
	while true;do
		# cleanup any leftover process locks that where left from broken execution
		rm -v /tmp/2web/active_scan_*.active
		# remove the conf changed trigger
		rm -v /tmp/2web/nfo2web_conf_changed.active
		ALERT "Finished removing process scan locks..."
		# run a regular update to detect new episodes
		# - this will also build stats and cleanup indexes as well
		update --parallel
		# watch for changes to the module config files
		# - if one is detected then this process will restart
		watch_conf_changes "$timeout" &
		# start the background process
		INFO "Loading library configs..."
		libaries=$(loadConfigs "/etc/2web/nfo/libaries.cfg" "/etc/2web/nfo/libaries.d/" "/etc/2web/config_default/nfo2web_libaries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )
		# load the disabled libraries that should not be scanned by the file watch service
		disabledLibaries=$(loadConfigs "/etc/2web/nfo/disabledLibaries.cfg" "/etc/2web/nfo/disabledLibaries.d/" "/etc/2web/config_default/nfo2web_disabledLibaries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )
		addToLog "INFO" "Watch Service Loading" "Scanning content in libaries <pre>$libaries</pre>Service will reset after a timeout of '$timeout' seconds."
		IFS=$'\n'
		for libary in $libaries;do
			if echo "$disabledLibaries" | grep "$libary";then
				ALERT "Library path is disabled '$libary'"
				addToLog "INFO" "Library Service Disabled" "Skipping scan for disabled path '$libary'"
			else
				ALERT "Loading service for libary = '$libary'"
				# check if the libary directory exists
				ALERT "Check if directory exists at '$libary'"
				if test -d "$libary";then
					ALERT "library exists at '$libary'"
					addToLog "INFO" "Starting Watch Service" "$libary" "$logPagePath"
					#
					echo "library exists at '$libary'"
					# read each tvshow directory from the libary
					# store these paths in a varaible to be checked when events are activated
					watch_library "$libary" "$webDirectory" "$timeout" &
				else
					ALERT "library does not exist at '$libary'"
					addToLog "ERROR" "Path Broken" "Path does not exist '$libary'" "$logPagePath"
				fi
			fi
		done
		# store the loop start time for activating the rescan
		watchProcessStartupTime="$(date "+%s")"
		# this process loops forever because it is a service
		while [ "true" == "true" ] ;do
			INFO "Running '$(jobs | wc -l)' watcher processes..."
			currentTime=$(date "+%s")
			# check if the watcher processes have been running for more than the timeout
			if [[ $(( currentTime - watchProcessStartupTime )) -gt $timeout ]];then
				# check for locked running processes
				activeProcesses=$(find /tmp/2web/ -type f -name "active_scan_*.active" | wc -l)
				if [ $activeProcesses -gt 0 ];then
					# if there are still processes running
					INFO "Service Reload Timeout exceeded, Waiting for '$activeProcesses' scans to finish before reloading service..."
					addToLog "INFO" "Service Reload Timeout Exceeded" "Waiting for '$activeProcesses' active scans to complete before reloading the service..."
				else
					addToLog "INFO" "Active scans finished." "All active scans have finished, triggering a reload of the service..."
					# no processes are running
					# close the service and it will be relaunched by cron
					# - this will reload a new version of the service if the software is updated
					exit
				fi
			fi
			# watch the event server for changes to /etc/2web/nfo/
			# - this means the configuration of the module has changed so a rescan must be triggered
			if test -f "/tmp/2web/nfo2web_conf_changed.active";then
				INFO "The configuration has changed. The service will be reloaded and a full scan will be triggered..."
				addToLog "Update" "Configuration Changed" "The configuration has changed. The service will be reloaded and a full scan will be triggered..."
				# launch a reload to load detected configuration changes
				break
			fi
			# sleep between job number updates
			sleep 60
		done
		# always kill all active background jobs
		# - stop all existing scan processes
		# - do not quote the string
		# - all processing tasks are power cycle safe so killing unfinished processes will not cause problems
		kill $(jobs -p)
		# check the status of the module
		# - This will stop the service if the module has been disabled
		checkModStatus "nfo2web"
	done
}
################################################################################
function watch_conf_changes(){
	timeout=$1
	#
	inotifywait --csv --timeout "$timeout" -r -e "MODIFY" -e "CREATE" -e "DELETE" "/etc/2web/nfo/" | while read event;do
		# mark the conf as changed
		touch /tmp/2web/nfo2web_conf_changed.active
	done
}
################################################################################
function watch_library(){
	# create a process that watches for filesystem events on a media directory
	libraryPath="$1"
	webDirectory="$2"
	timeout="$3"
	# this process will spawn one inotifywait process for a entire library
	# - when a change is detected in the library it scans the known paths for a match
	#   and updates that path information

	# find all library media paths
	# - this will be searched for events
	foundLibaryPaths=$(find "$libraryPath" -maxdepth 1 -mindepth 1 -type 'd' | shuf)
	# show the user all the paths that will be watched
	for showPath in $foundLibaryPaths;do
		ALERT "Adding media path to service watchlist '$showPath'"
	done
	# launch a event server to watch a library for changes and spawn update events
	while true;do
		inotifywait --csv --timeout "$timeout" -r -e "MODIFY" -e "CREATE" -e "DELETE" "$libraryPath" | while read event;do
			INFO "EVENT DETECTED : $event"
			# store the event time and watch for new events
			# backup IFS
			IFS_BACKUP=$IFS
			# split on newlines
			IFS=$'\n'
			# scan the event for matches with the library show paths
			for showPath in $foundLibaryPaths;do
				# if the event is in one of the found library paths
				if echo -n "$event" | grep -q "$showPath";then
					INFO "EVENT MATCHES: show '$showPath', waiting for event changes to finish..."
					# launch a thread in the background to wait for the changes to finish and then update the content
					wait_for_changes_to_finish "$showPath" "$event" "$webDirectory" &
				else
					# rescan the found library paths
					# - this should overwrite the above variable
					foundLibaryPaths=$(find "$libraryPath" -maxdepth 1 -mindepth 1 -type 'd' | shuf)
					# scan the updated show paths to try and match the event to the new list
					for subShowPath in $foundLibaryPaths;do
						# if the event matches
						if echo -n "$event" | grep -q "$subShowPath";then
							wait_for_changes_to_finish "$subShowPath" "$event" "$webDirectory" &
						else
							INFO "The change was detected in a place that could not be a new media item..."
							#addToLog "ERROR" "Unknown Event" "Could not determine what to do with event '$event', it is improperly formatted for the server. On the server use the command 'nfo2web --demo-data' in order to generate example data for nfo2web in '/var/cache/2web/generated/demo/nfo/'."
						fi
					done
				fi
			done
			# reset to backup IFS
			IFS=IFS_BACKUP
		done
	done
}
################################################################################
function wait_for_changes_to_finish(){
	# wait for a directory to stop being modified and then process the path
	showPath="$1"
	event="$2"
	webDirectory="$3"

	# must be process locked so multuple process path commands can not be ran at the same time
	waitChangesPathSum="$(echo -n "$showPath" | md5sum | cut -d' ' -f1)"
	#
	if test -f /tmp/2web/active_scan_$waitChangesPathSum.active;then
		INFO "Scan process already active, remove /tmp/2web/active_scan_$waitChangesPathSum.active force scanning if this is in error."
	else
		# log a new event being processed
		addToLog "Update" "Found Event" "Matched event '$event' to show '$showPath', Path will begin processing when no file changes have been detected for 10 minutes."
		# create the lock file
		date "+%s" > /tmp/2web/active_scan_$waitChangesPathSum.active

		# create a loop to run until no changes have been detected on the directory for at least 60 seconds
		changesComplete="false"
		while [ $changesComplete == "false" ];do
			changesComplete="true"
			# wait for changes to stop happening to the directory for more than 10 minutes
			inotifywait --timeout 600 -r -e "MODIFY" -e "CREATE" -e "DELETE" "$showPath" | while read event;do
				# if a change is detected in the 60 seconds then reset the loop and wait again after the 60 second timeout
				changesComplete="false"
			done
		done
		# get the event type
		eventType="$(echo "$event" | cut -d',' -f2)"
		# scan media for updates
		addToLog "Update" "Processing Path" "Matched event '$event' to show '$showPath', Processing Path for changes."
		# - this will be logged in the 2web log by the below function
		processPath "$showPath" "$eventType" "$webDirectory"
		# log finished
		addToLog "Update" "Processing Path" "Finished processing path, '$showPath'"
		# remove the lock file
		rm -v /tmp/2web/active_scan_$waitChangesPathSum.active
	fi
}
################################################################################
function processPath(){
	# Given a path it will determine if the path is a show or movie and update
	# the content
	show="$1"
	eventType="$2"
	webDirectory="$3"

	################################################################################
	# process page metadata
	################################################################################
	# if the show directory contains a nfo file defining the show
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
				# if this is a delete event the show must be rebuilt on the webserver
				if [ "$eventType" == "DELETE" ];then
					addToLog "UPDATE" "DELETE EVENT" "The show '$show' has had media removed. The web directory will be removed and rebuilt."
					# if a file has been removed from the directory all the content needs rescaned after the web content has been deleted
					ALERT "Removing '$webDirectory/shows/$showTitle/'"
					# remove existing web directory for rebuild of the show
					# - this will remove the removed content from the server
					rm -v "$webDirectory/shows/$showTitle/"
				fi
				#ALERT "ADDING SHOW $show"
				#ALERT "ADDING NEW PROCESS TO QUEUE $(jobs)"
				processShow "$show" "$showMeta" "$showTitle" "$webDirectory"
				# write log info from show to the log, this must be done here to keep ordering
				# of the log and to make log show even when the state of the show is unchanged
				#INFO "Adding logs from $webDirectory/shows/$showTitle/log.index to $logPagePath"
				#cat "$webDirectory/shows/$showTitle/log.index" >> "$webDirectory/log.php"
			else
				echo "[ERROR]: Show has no episodes!"
				addToLog "ERROR" "Show has no episodes" "No episodes found for '$showTitle' in '$show'\n\nTo remove this empty folder use below command.\n\nrm -rvi '$show'"
			fi
		else
			echo "[ERROR]: Show nfo file is invalid!"
			addToLog "ERROR" "Show NFO Invalid" "$show/tvshow.nfo"
		fi
	elif grep -q "<movie>" "$show"/*.nfo;then
		# if a file has been removed from the directory all the content needs rescaned from scratch
		# if this is a delete event the show must be rebuilt on the webserver
		if [ "$eventType" == "DELETE" ];then
			# find the movie nfo in the movie path
			moviePath=$(find "$show"/*.nfo)
			################################################################################
			# read the discovered nfo data
			nfoInfo=$(cat "$moviePath")
			# rip the movie title cleanup and rip the year of the movie
			movieTitle=$(cleanXml "$nfoInfo" "title")
			movieTitle=$(alterArticles "$movieTitle" )
			movieYear=$(cleanXml "$nfoInfo" "year")
			# Add the year to the movie title to get the web path
			movieWebPath="${movieTitle} ($movieYear)"
			# The existing web content must be be deleted and process movie will rebuild that content
			ALERT "rm -v '$webDirectory/movies/$movieWebPath/'"
		fi
		# this is a move directory not a show
		processMovie "$show" "$webDirectory"
	fi
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
	libaries=$(loadConfigs "/etc/2web/nfo/libaries.cfg" "/etc/2web/nfo/libaries.d/" "/etc/2web/config_default/nfo2web_libaries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )
	# load the disabled list
	disabledLibaries=$(loadConfigs "/etc/2web/nfo/disabledLibaries.cfg" "/etc/2web/nfo/disabledLibaries.d/" "/etc/2web/config_default/nfo2web_disabledLibaries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )
	# the webdirectory is a cache where the generated website is stored
	webDirectory="$(webRoot)"
	# create the log path
	logPagePath="$webDirectory/log/$(date "+%s").log"
	# create the homepage path
	showIndexPath="$webDirectory/shows/index.php"
	movieIndexPath="$webDirectory/movies/index.php"
	# build the movie index
	buildMovieIndex "$webDirectory"
	# build the show index
	buildShowIndex "$webDirectory"
	touch "$logPagePath"
	addToLog "INFO" "STARTED Update" "$(date)"
	# sleep one second to seprate the logs
	sleep 1
	# create new log after start so start log message is at start of log
	logPagePath="$webDirectory/log/$(date "+%s").log"
	# figure out the total number of CPUS for parallel processing
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi
	# read each libary from the libary config, single path per line
	ALERT "LIBARIES: $libaries"

	if cacheCheck "$webDirectory/cleanCheck.cfg" "7";then
		# clean the database of broken entries
		# - this should allow you to delete data from source drives and it automatically remove it from the website.
		cleanDatabase
	fi

	IFS=$'\n'
	for libary in $libaries;do
		if echo "$disabledLibaries" | grep "$libary";then
			ALERT "Path is disabled '$libary'"
			addToLog "INFO" "Library Scan Disabled" "Skipping scan for disabled path '$libary'"
		else
			ALERT "libary = $libary"
			# check if the libary directory exists
			logPagePath="$webDirectory/log/$(date "+%s").log"
			addToLog "INFO" "Checking library path" "$libary"
			ALERT "Check if directory exists at '$libary'"
			if test -d "$libary";then
				ALERT "library exists at '$libary'"
			else
				ALERT "library does not exist at '$libary'"
			fi
			if test -d "$libary";then
				addToLog "UPDATE" "Starting library scan" "$libary"
				echo "library exists at '$libary'"
				# read each tvshow directory from the libary
				foundLibaryPaths=$(find "$libary" -maxdepth 1 -mindepth 1 -type 'd' | shuf)

				for show in $foundLibaryPaths;do
					################################################################################
					# process page metadata
					################################################################################
					# if the show directory contains a nfo file defining the show
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
								#ALERT "ADDING NEW PROCESS TO QUEUE $(jobs)"
								processShow "$show" "$showMeta" "$showTitle" "$webDirectory" &
								# pause execution while no cpus are open
								waitQueue 0.5 "$totalCPUS"
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
						processMovie "$show" "$webDirectory" &
						# pause execution while no cpus are open
						waitQueue 0.5 "$totalCPUS"
					fi
				done
			else
				ALERT "$show does not exist!"
			fi
			# update random backgrounds
			#scanForRandomBackgrounds "$webDirectory"
		fi
	done
	# block for parallel threads here
	blockQueue 1

	# add the end to the log, add the jump to top button and finish out the html
	logPagePath="$webDirectory/log/$(date "+%s").log"
	addToLog "INFO" "FINISHED" "$(date)"
	# update video libaries on all kodi clients, if no video playback is detected
	/usr/bin/kodi2web video
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
		tempList=$(cat "$webDirectory/shows/shows.index" | sort -u )
		echo "$tempList" > "$webDirectory/shows/shows.index"
	fi
	if test -f "$webDirectory/new/shows.index";then
		# new list
		#tempList=$(cat -n "$webDirectory/new/shows.index" | sort -uk2 | sort -nk1 | cut -f1 | tail -n 200 )
		tempList=$(cat "$webDirectory/new/shows.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/shows.index"
	fi
	if test -f "$webDirectory/new/episodes.index";then
		# new episodes
		#tempList=$(cat -n "$webDirectory/new/episodes.index" | sort -uk2 | sort -nk1 | cut -f1 | tail -n 200 )
		tempList=$(cat "$webDirectory/new/episodes.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/episodes.index"
	fi

	linkFile "$webDirectory/shows/shows.index" "$webDirectory/random/shows.index"

	if test -f "$webDirectory/random/episodes.index";then
		# new episodes
		tempList=$(cat "$webDirectory/random/episodes.index" | sort -u | tail -n 800 )
		echo "$tempList" > "$webDirectory/random/episodes.index"
	fi
	##########
	# MOVIES #
	##########
	if test -f "$webDirectory/movies/movies.index";then
		tempList=$(cat "$webDirectory/movies/movies.index" | sort -u )
		echo "$tempList" > "$webDirectory/movies/movies.index"
	fi
	if test -f "$webDirectory/new/movies.index";then
		# new movies
		#tempList=$(cat -n "$webDirectory/new/movies.index" | sort -uk2 | sort -nk1 | cut -f1 | tail -n 200 )
		tempList=$(cat "$webDirectory/new/movies.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/movies.index"
	fi
	linkFile "$webDirectory/movies/movies.index" "$webDirectory/random/movies.index"
	#####################
	# ALL INDEX CLEANUP #
	#####################
	if test -f "$webDirectory/new/all.index";then
		# new movies
		#tempList=$(cat -n "$webDirectory/new/movies.index" | sort -uk2 | sort -nk1 | cut -f1 | tail -n 200 )
		tempList=$(cat "$webDirectory/new/all.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/all.index"
	fi
	#####################################
	# Cleanup Tag Indexes of duplicates #
	#####################################
	find "/var/cache/2web/web/tags/" -mindepth 1 -maxdepth 1 -type f -name '*.index' | while read tagFile;do
		# rebuild the file path to prevent unexpected paths from being removed
		tagFilePath="/var/cache/2web/web/tags/$(basename "$tagFile")"
		# sort the index and remove duplicates
		tagFileData="$(cat "$tagFilePath" | sort -u)"
		# overwrite the unsorted tag file
		echo "$tagFileData" > "$tagFilePath"
	done
	##############################################################################
	# create the final index pages, these should not have the progress indicator
	# build the final version of the homepage without the progress indicator
	buildHomePage "$webDirectory"
	# build the movie index
	buildMovieIndex "$webDirectory"
	# build the show index
	buildShowIndex "$webDirectory"
	# write the sum state of the libary for change checking
	#echo "$libarySum" > "$webDirectory/state.cfg"
	#getLibSum > "$webDirectory/state.cfg"
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
	elif [ "$1" == "--demo-data" ] || [ "$1" == "demo-data" ] ;then
		# generate demo data for 2web modules for use in screenshots, make it random as can be

		# check for parallel processing and count the cpus
		if echo "$@" | grep -q -e "--parallel";then
			totalCPUS=$(cpuCount)
		else
			totalCPUS=1
		fi
		#########################################################################################
		# nfo2web demo movies
		#########################################################################################
		createDir "/var/cache/2web/generated/demo/nfo/movies/"
		# create 5 random demo movies
		for index in $(seq $(( $RANDOM % 6 )) );do
			# write a fake movie nfo with random data
			randomTitle="$RANDOM $(randomWord) $(randomWord)"
			createDir "/var/cache/2web/generated/demo/nfo/movies/$randomTitle/"
			# generate the random plot string
			randomPlot="$(randomWord)"
			for index4 in $(seq -w $(( 10 + ( $RANDOM % 300 ) )) );do
				randomPlot="$randomPlot $(randomWord)"
			done
			{
				echo "<movie>";
				echo "	<title>$randomTitle</title>";
				echo "	<year>$RANDOM</year>";
				echo "	<plot>$randomPlot</plot>";
				echo "</movie>";
			} > "/var/cache/2web/generated/demo/nfo/movies/$randomTitle/$randomTitle.nfo"
			# generate a randomized artwork to use
			# - poster
			demoImage "/var/cache/2web/generated/demo/nfo/movies/$randomTitle/poster.png" "$randomTitle" "200" "500" &
			waitQueue 0.2 "$totalCPUS"
			# - fanart
			demoImage "/var/cache/2web/generated/demo/nfo/movies/$randomTitle/fanart.png" "$randomTitle" "800" "600" &
			waitQueue 0.2 "$totalCPUS"
			# generate the fake video file using the site spinner gif
			ffmpeg -i "/var/cache/2web/spinner.gif" "/var/cache/2web/generated/demo/nfo/movies/$randomTitle/$randomTitle.mp4" &
			waitQueue 0.2 "$totalCPUS"
		done
		#########################################################################################
		# nfo2web demo shows
		#########################################################################################
		createDir "/var/cache/2web/generated/demo/nfo/shows/"
		# create 5 random demo shows with 10 random demo episodes
		for index in $(seq $(( 1 + ( $RANDOM % 10 ) )) );do
			randomTitle="$RANDOM $(randomWord) $(randomWord)"
			createDir "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/"
			# generate the random plot string
			randomPlot="$(randomWord)"
			for index4 in $(seq -w $(( 10 + ( $RANDOM % 300 ) )) );do
				randomPlot="$randomPlot $(randomWord)"
			done
			# create the show nfo
			{
				echo "<tvshow>"
				echo "	<title>$randomTitle</title>"
				echo "	<year>$RANDOM</year>"
				echo "	<plot>$randomPlot</plot>"
				echo "</tvshow>"
			} > "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/tvshow.nfo"
			# create show poster
			demoImage "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/poster.png" "$randomTitle" "200" "500" &
			waitQueue 0.2 "$totalCPUS"
			# create show fanart
			demoImage "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/fanart.png" "$randomTitle" "800" "600" &
			waitQueue 0.2 "$totalCPUS"
			# create random episodes for show
			for index2 in $(seq $(( 2 + ( $RANDOM % 9 ) )) );do
				# random season
				createDir "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/Season $index2/"
				for index3 in $(seq $(( 5 + ( $RANDOM % 16 ) )) );do
					# create a random name for the episode
					randomEpisodeTitle="$(randomWord) $(randomWord)"
					randomPlot="$(randomWord)"
					for index4 in $(seq -w $(( 10 + ( $RANDOM % 300 ) )) );do
						randomPlot="$randomPlot $(randomWord)"
					done
					# create episode nfo
					{
						echo "<episodedetails>"
						echo "	<showtitle>$randomTitle</showtitle>"
						echo "	<title>$randomEpisodeTitle</title>"
						# set the episode number from the sequence number
						echo "	<season>$index2</season>"
						echo "	<episode>$index3</episode>"
						echo "	<plot>$randomPlot</plot>"
						echo "</episodedetails>"
					} > "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/Season $index2/$randomEpisodeTitle.nfo"
					# create episode thumbnail
					demoImage "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/Season $index2/$randomEpisodeTitle-thumb.png" "$randomEpisodeTitle" "800" "600" &
					waitQueue 0.2 "$totalCPUS"
					# create episode fake video file
					ffmpeg -i "/var/cache/2web/spinner.gif" "/var/cache/2web/generated/demo/nfo/shows/$randomTitle/Season $index2/$randomEpisodeTitle.mp4" &
					waitQueue 0.2 "$totalCPUS"
				done
			done
		done
		blockQueue 1
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
	elif [ "$1" == "--service" ] || [ "$1" == "service" ] ;then
		# only launch the service if the module is enabled
		checkModStatus "nfo2web"
		# lock the module execution
		lockProc "nfo2web"
		# launch the watch service
		nfo2web_watch_service
		# remove active state file
		if test -f /tmp/nfo2web.active;then
			rm /tmp/nfo2web.active
		fi
	elif [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "--clean" ] || [ "$1" == "clean" ] ;then
		clean
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade the pip packages if the module is enabled
		checkModStatus "nfo2web"
		# upgrade streamlink
		upgrade-pip "nfo2web" "streamlink"
		# install nightly version of yt-dlp
		upgrade-yt-dlp
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "nfo2web"
		lockProc "nfo2web"
		update "$@"
		# remove active state file
		if test -f /tmp/nfo2web.active;then
			rm /tmp/nfo2web.active
		fi
	elif [ "$1" == "-p" ] || [ "$1" == "--parallel" ] || [ "$1" == "parallel" ] ;then
		checkModStatus "nfo2web"
		lockProc "nfo2web"
		update "$@" --parallel
		# remove active state file
		if test -f /tmp/nfo2web.active;then
			rm /tmp/nfo2web.active
		fi
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "nfo2web Version: "
		cat /usr/share/2web/version_nfo2web.cfg
	else
		checkModStatus "nfo2web"
		lockProc "nfo2web"
		update "$@"
		# remove active state file
		if test -f /tmp/nfo2web.active;then
			rm /tmp/nfo2web.active
		fi
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
