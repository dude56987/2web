#! /bin/bash
################################################################################
# video2web generates websites from video filled directories
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
# enable debug log
#set -x
################################################################################
source /var/lib/2web/common
################################################################################
function downloadDir(){
	# write path to console
	echo "/var/cache/2web/downloads/videos"
}
################################################################################
function generatedDir(){
	# write path to console
	echo "/var/cache/2web/generated/videos"
}
################################################################################
function libaryPaths(){
	# load the configs
	configPaths=$(loadConfigs "/etc/2web/videos/libaries.cfg" "/etc/2web/videos/libaries.d/" "/etc/2web/config_default/video2web_libraries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | tr -s '/' | shuf  )
	# output the paths
	echo "$configPaths"
}
################################################################################
function update(){
	#rotateSpinner &
	#SPINNER_PID="$!"
	addToLog "INFO" "STARTED Update" "$(date)"
	################################################################################
	webDirectory=$(webRoot)
	kodiDirectory="$(kodiRoot)"
	################################################################################
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedRoot)"
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make videos directory
	createDir "$webDirectory/videos/"
	# create thumbnail directories
	createDir "/var/cache/2web/downloads/thumbnails/video2web/"
	createDir "/var/cache/2web/generated/thumbnails/video2web/"
	createDir "/var/cache/2web/web/thumbnails/video2web/"
	# check for parallel processing and count the cpus
	if [ "$PARALLEL_OPTION" == "yes" ];then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi
	# cleanup the videos index
	if test -f "$webDirectory/videos/videos.index";then
		tempList=$(cat "$webDirectory/videos/videos.index" | sort -ud )
		echo "$tempList" > "$webDirectory/videos/videos.index"
	fi
	# cleanup new video index
	if test -f "$webDirectory/new/videos.index";then
		# new videos but preform a fancy sort that does not change the order of the items
		tempList=$(cat "$webDirectory/new/videos.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/videos.index"
	fi
	addToLog "INFO" "FINISHED Update" "$(date)"
}
################################################################################
function addSingleVideo(){
	# addSingleVideo $videoPath
	#
	# - add a single video to the videos section using only the video path
	#
	videoPath="$1"
	INFO "Checking video file at '$videoPath'"
	#addToLog "INFO" "Adding video" "Checking if new video '$videoPath'"
	# get the year the video was created
	season="$(date -d "@$(stat --format '%W' "$videoPath" )" "+%Y")"
	# get the extension
	fileExt=$(basename "$videoPath" | rev | cut -d'.' -f1 | rev)
	videoGroup="$(basename "$(dirname "$videoPath")")"
	#ALERT "videoGroup phase 1 = $videoGroup"
	videoGroup="$(cleanText "$videoGroup")"
	#ALERT "videoGroup phase 2 = $videoGroup"
	videoGroup="$(alterArticles "$videoGroup")"
	#ALERT "videoGroup phase 3 = $videoGroup"
	# is a single collection
	# - use the directory name the video is stored in as the group title
	# - use the video file name as the video title
	#ALERT "videoName phase 1 = $videoPath"
	#ALERT "videoName phase 2 = $(basename "$videoPath")"
	tempVideoName="$(basename "$videoPath")"
	#ALERT "videoName phase 3 = $tempVideoName"
	tempVideoName="$( echo -n "$tempVideoName" | rev | cut -d'.' -f2- | rev)"
	#ALERT "videoName phase 4 = $tempVideoName"
	tempVideoName="$(cleanText "$tempVideoName")"
	#ALERT "videoName phase 5 = $tempVideoName"
	tempVideoName="$(alterArticles "$tempVideoName")"
	#ALERT "videoName phase 6 = $tempVideoName"
	#
	if ! test -f "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.php";then
		#
		addToLog "NEW" "Adding video" "Adding video '$videoPath' to group '$videoGroup'"
		ALERT "Adding NEW video '$tempVideoName' to group '$videoGroup'"
		# if the video is a gif or webp then convert it to mp4 and change the path
		# - gif and webp animations should be very small so conversions should be very small
		if [ "$fileExt" == "webp" ] || [ "$fileExt" == "gif" ];then
			# if no conversion has been done yet
			if ! test -f "/var/cache/2web/generated/video2web/converted/$videoGroup/$tempVideoName.mp4";then
				createDir "/var/cache/2web/generated/video2web/converted/$videoGroup/"
				# convert the media into a mp4 file
				ffmpeg -i "$videoPath" -f mp4 "/var/cache/2web/generated/video2web/converted/$videoGroup/$tempVideoName.mp4"
				# change the path to the generated video file
			fi
			videoPath="/var/cache/2web/generated/video2web/converted/$videoGroup/$tempVideoName.mp4";
			fileExt="mp4"
		fi
		#
		videoThumbnailPath="$webDirectory/videos/$videoGroup/Season $season/$tempVideoName"
		videoThumbnailPathKodi="$kodiDirectory/videos/$videoGroup/Season $season/$tempVideoName"
		# create kodi and web season directories
		createDir "$kodiDirectory/videos/$videoGroup/Season $season/"
		createDir "$webDirectory/videos/$videoGroup/Season $season/"
		# build the show data if it does not exist
		if ! test -f "$kodiDirectory/videos/$videoGroup/tvshow.nfo";then
			{
				echo "<tvshow>"
				echo "<title>$videoGroup</title>"
				echo "<studio>Internet</studio>"
				echo "<genre>Internet</genre>"
				echo "<plot>Source URL: $videoGroup</plot>"
				echo "<premiered>$(date +%F)</premiered>"
				echo "<director>$videoGroup</director>"
				echo "</tvshow>"
			} > "$kodiDirectory/videos/$videoGroup/tvshow.nfo"
		fi
		# build the show index file
		if ! test -f "$webDirectory/videos/$videoGroup/videos.index";then
			{
				echo "<a class='indexSeries' href='/videos/$videoGroup/'>"
				echo "	<img title='$videoGroup' loading='lazy' src='/videos/$videoGroup/poster.png'>"
				echo "	<div class='title'>"
				echo "		$videoGroup"
				echo "	</div>"
				echo "</a>"
			} > "$webDirectory/videos/$videoGroup/videos.index"
		fi
		# build the nfo data for the video file
		if ! test -f "$kodiDirectory/videos/$videoGroup/Season $season/$tempVideoName.nfo";then
			{
				echo "<episodedetails>"
				echo "	<showtitle>$videoGroup</showtitle>"
				echo "	<title>$tempVideoName</title>"
				# set the episode number from the sequence number
				echo "	<season>$season</season>"
				echo "	<episode>$tempVideoName</episode>"
				echo "	<plot>Video From '$videoGroup'</plot>"
				echo "</episodedetails>"
			} > "$kodiDirectory/videos/$videoGroup/Season $season/$tempVideoName.nfo"
			# add the video group to the search index
			addToSearchIndex "$webDirectory/videos/$videoGroup/videos.index" "$videoGroup" "/videos/$videoGroup/"
		fi
		# create the season title data if it does not exist
		if ! test -f "$webDirectory/videos/$videoGroup/Season $season/season.title";then
			echo "$season" > "$webDirectory/videos/$videoGroup/Season $season/season.title"
		fi
		# build the index file
		if ! test -f "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index";then
			{
				echo "<a class='showPageEpisode' href='/videos/$videoGroup/Season $season/$tempVideoName.index'>"
				echo "	<img title='$tempVideoName' loading='lazy' src='videos/$videoGroup/Season $season/$tempVideoName-web.png'>"
				echo "	<div class='title'>"
				echo "		$tempVideoName"
				echo "	</div>"
				echo "</a>"
			} > "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index"
		fi
		# build the direct link
		if ! test -f "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.php.directLink";then
			echo -n "/videos/$videoGroup/Season $season/$tempVideoName.$fileExt" > "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.php.directLink"
		fi
		# build the season data
		# - show.title
		# - studio.title
		# - grade.title
		if ! test -f "$webDirectory/videos/$videoGroup/Season $season/show.title";then
			echo -n "$videoGroup" > "$webDirectory/videos/$videoGroup/Season $season/show.title"
		fi
		if ! test -f "$webDirectory/videos/$videoGroup/Season $season/studio.title";then
			echo -n "Local" > "$webDirectory/videos/$videoGroup/Season $season/studio.title"
		fi
		if ! test -f "$webDirectory/videos/$videoGroup/Season $season/grade.title";then
			echo -n "UNRATED" > "$webDirectory/videos/$videoGroup/Season $season/grade.title"
		fi
		# build thumbnails from the local videos
		#if ! test -s "$thumbnailPath-thumb.png";then
		#	ffmpegthumbnailer -i "$videoPath" -s 400 -c png -o "$thumbnailPath-thumb.png"
		#fi
		#linkFile "$thumbnailPath-thumb.png" "$thumbnailPathKodi-thumb.png"
		# generate the thumbnail from the local media
		##thumbnailPath="$webDirectory/movies/$movieWebPath/poster"
		##thumbnailPathKodi="$kodiDirectory/movies/$movieWebPath/poster"
		#
		#thumbnailPath="$webDirectory/videos/$videoGroup/Season $season/$tempVideoName"
		#thumbnailPathKodi="$kodiDirectory/videos/$videoGroup/Season $season/$tempVideoName"
		#
		generateThumbnailFromMedia "$videoPath" "$videoThumbnailPath-thumb" "$videoThumbnailPathKodi-thumb"
		#ffmpegthumbnailer -i "$videoPath" -s 200 -c png -o "$thumbnailPath-web.png"
		#thumbSum="$(echo -n "$thumbnailPath-ThumbnailDataSalt" | sha512sum | cut -d' ' -f1)"
		#generateThumbnailFromMedia "$videoPath" "$videoThumbnailPath-web"
		if ! test -f "$videoThumbnailPath-web.png";then
			thumbSum=$(echo -n "$thumbnailPath" | sha512sum | cut -d' ' -f1)
			#
			#thumbnailPath="$webDirectory/videos/$videoGroup/Season $season/$tempVideoName"
			# create the web thumbnail
			#generateThumbnailFromMedia "$videoPath" "/var/cache/2web/generated/thumbnails/video2web/$thumbSum-gen" "$thumbnailPath-web"
			ffmpegthumbnailer -i "$videoPath" -s 200 -c png -o  "$videoThumbnailPath-web.png"
		fi
		#generateThumbnailFromMedia "$videoPath" "$thumbnailPath-thumb" "$thumbnailPathKodi-thumb"
		#generateThumbnailFromMedia "$videoPath" "$thumbnailPath-web" "$thumbnailPathKodi-web"
		#thumbSum=$(echo -n "$thumbnailPath" | sha512sum | cut -d' ' -f1)
		# create the web thumbnail if it does not exist and a thumbnail path was found
		#if ! test -f "/var/cache/2web/generated/thumbnails/video2web/$thumbSum-web.png";then
		#if ! test -f "$thumbnailPath-web.png";then
			# convert the thumbnail into a web thumbnail
			#convert -quiet "$thumbnailPath-thumb.png" -adaptive-resize "300x200" "/var/cache/2web/generated/thumbnails/video2web/$thumbSum-web.png"
		#	convert -quiet "$thumbnailPath-thumb.png" -adaptive-resize "300x200" "$thumbnailPath-web.png"
		#fi
		#if ! test -f "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName-web.png";then
		#	# link thumb to web directory
		#	linkFile "/var/cache/2web/generated/thumbnails/video2web/$thumbSum-web.png" "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName-web.png"
		#fi
		#
		# create video directory
		#createDir "$webDirectory/videos/$videoGroup/"
		# add the path to the list of paths for duplicate checking and rescans
		addSourcePath "$pagesDirectory" "$webDirectory/videos/$videoGroup/sources.cfg"
		# add the media file to the website
		linkFile "$videoPath" "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.$fileExt"
		# add the media to the kodi directory
		linkFile "$videoPath" "$kodiDirectory/videos/$videoGroup/Season $season/$tempVideoName.$fileExt"
		#
		videoWebPath="$webDirectory/videos/$videoGroup"
		addToIndex "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index" "$webDirectory/videos/$videoGroup/Season $season/season.index"
		addToIndex "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index" "$webDirectory/videos/$videoGroup/marathon.index"
		################################################################################
		# add video to the indexes
		################################################################################
		{
			echo "<a href='/videos/$videoGroup/Season $season/$tempVideoName.php' class='showPageEpisode' >"
			echo "	<h2 class='title'>$videoGroup</h2>"
			echo "	<img title='$tempVideoName' loading='lazy' src='/videos/$videoGroup/Season $season/$tempVideoName-web.png' />"
			echo "	<div class='title'>"
			echo "		<div class='showIndexNumbers'>${season}</div>"
			echo "		${tempVideoName}"
			echo "	</div>"
			echo "</a>"
		} > "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index"
		# build the preview thumbnails
		if ! test -f "${videoWebPath}Season $season/${tempVideoName}_preview_8.png";then
			ffmpegthumbnailer -t "6%" -i "$videoPath" -s 200 -c png -o  "${videoWebPath}/Season $season/${tempVideoName}_preview_1.png"
			ffmpegthumbnailer -t "12%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_2.png"
			ffmpegthumbnailer -t "25%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_3.png"
			ffmpegthumbnailer -t "37%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_4.png"
			ffmpegthumbnailer -t "50%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_5.png"
			ffmpegthumbnailer -t "62%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_6.png"
			ffmpegthumbnailer -t "75%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_7.png"
			ffmpegthumbnailer -t "87%" -i "$videoPath" -s 200 -c png -o "${videoWebPath}/Season $season/${tempVideoName}_preview_8.png"
		fi
		#
		addToIndex "$webDirectory/videos/$videoGroup/videos.index" "$webDirectory/videos/videos.index"
		#
		SQLaddToIndex "$webDirectory/videos/$tempVideoName/Season $season/$tempVideoName.index" "$webDirectory/data.db" "videos"
		SQLaddToIndex "$webDirectory/videos/$tempVideoName/Season $season/$tempVideoName.index" "$webDirectory/data.db" "all"
		#
		SQLaddToIndex "$webDirectory/videos/$tempVideoName/Season $season/$tempVideoName-thumb.png" "$webDirectory/backgrounds.db" "videos_poster"
		SQLaddToIndex "$webDirectory/videos/$tempVideoName/Season $season/$tempVideoName-thumb.png" "$webDirectory/backgrounds.db" "videos_fanart"
		SQLaddToIndex "$webDirectory/videos/$tempVideoName/Season $season/$tempVideoName-thumb.png" "$webDirectory/backgrounds.db" "poster_all"
		SQLaddToIndex "$webDirectory/videos/$tempVideoName/Season $season/$tempVideoName-thumb.png" "$webDirectory/backgrounds.db" "fanart_all"

		# add the updated show to the new videos index
		addToIndex "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index" "$webDirectory/new/videos.index"
		#
		addToIndex "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index" "$webDirectory/new/all.index"
		# random indexes
		linkFile "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index"  "$webDirectory/random/videos.index"
		ALERT "Adding video to search index '$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index'"
		# add this video to the search index
		addToSearchIndex "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.index" "${videoGroup} ${season} ${tempVideoName}" "/videos/$videoGroup/"

		# update last updated times
		date "+%s" > /var/cache/2web/web/new/all.cfg
		date "+%s" > /var/cache/2web/web/new/videos.cfg

		# add the group view using show seasons template
		linkFile "/usr/share/2web/templates/seasons.php" "$webDirectory/videos/$videoGroup/index.php"
		# move into the web directory so paths from below searches are relative
		cd "$webDirectory/videos/"
		# build the poster list from the thumbnails
		find -L "." -type f -name "thumb.png" > "$webDirectory/videos/poster.cfg"
		# copy over the web interface to play the video
		linkFile "/usr/share/2web/templates/videoPlayer.php" "$webDirectory/videos/$videoGroup/Season $season/$tempVideoName.php"
	fi
}
################################################################################
function scanVideos(){
	# - TODO: build a function that reads all image files in a directory, makes webpages for them
	#         index in directory links to first page, last page should link to .. index above
	pagesDirectory=$1
	#
	webDirectory="/var/cache/2web/web"
	################################################################################
	addToLog "UPDATE" "Scanning video" "Adding video from '$pagesDirectory'"
	# scan for all video files in the scan path
	videoFilesFound="$(find -L "$pagesDirectory" -type f -name "*.mkv" -o -name "*.gif" -o -name "*.webp" -o -name "*.avif" -o -name "*.avi" -o -name "*.webm" -o -name "*.mp4")"
	echo -n "$videoFilesFound" | sort | while read videoPath;do
		if [ "$videoPath" == "" ];then
			INFO "Skipping Blank Video File"
		else
			INFO "Found video file at '$videoPath'"
			addSingleVideo "$videoPath" &
			waitQueue 0.2 "$totalCPUS"
		fi
	done
	blockQueue 1
}
################################################################################
function processvideoPath(){
	# processvideoPath $videoNamePath $webDirectory
	#
	videoNamePath=$1
	webDirectory=$2

	if [ "$2" == "" ];then
		webDirectory="$(webRoot)"
	fi
	kodiDirectory="$(kodiRoot)"
	addToLog "UPDATE" "Adding videos from path" "$videoNamePath"

	# check the video is not a invalid path
	if echo "$videoNamePath" | grep -q "/\.Trash";then
		# do not process trash directories
		addToLog "WARNING" "Path was Trash" "The path '$videoNamePath' was detected as a Hidden trash directory. This will not be processed. You may want to place your media source in a lower level directory of the drive it is located on. For example '/media/driveName/' becomes '/media/driveName/myPathName/'."
		return
	fi

	INFO "link the videos to the kodi directory"
	# link this video to the kodi directory
	#createDir "$videoNamePath" "$webDirectory/kodi/videos/"

	INFO "Scanning video path '$videoNamePath'"
	# add one to the total videos
	totalvideos=$(( $totalvideos + 1 ))

	#check the dir sum for the video path
	if checkDirSum "$webDirectory" "$videoNamePath";then
		# scan the pages in the path
		scanVideos "$videoNamePath"
		# set the directory sum to prevent the path being rescanned unless files are altered
		setDirSum "$webDirectory" "$videoNamePath"
	fi
}
################################################################################
function webUpdate(){
	addToLog "INFO" "STARTED Web Update" "$(date)"
	# read the download directory and convert videos into webpages
	# - There are 2 types of directory structures for videos in the download directory
	#   + videoWebsite/videoName/chapter/image.png
	#   + videoWebsite/videoName/image.png

	webDirectory=$(webRoot)
	kodiDirectory="$(kodiRoot)"
	downloadDirectory="$(libaryPaths)"
	disabledLibaries="$(loadConfigs "/etc/2web/videos/disabledLibaries.cfg" "/etc/2web/videos/disabledLibaries.d/" "/etc/2web/config_default/video2web_disabledLibaries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )"

	ALERT "$downloadDirectory" "Download Directory"

	ALERT "$disabledLibaries" "Disabled video Libraries"

	# create the kodi directory
	createDir "$kodiDirectory/videos/"

	# create the web directory
	createDir "$webDirectory/videos/"

	# link the homepage
	linkFile "/usr/share/2web/templates/videos.php" "$webDirectory/videos/index.php"

	# link the random poster script
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/videos/randomPoster.php"
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/videos/randomFanart.php"

	# check for parallel processing and count the cpus
	if [ "$PARALLEL_OPTION" == "yes" ];then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi
	ALERT "$downloadDirectory" "Scanning Library Config"
	echo "$downloadDirectory" | sort | while read videoLibaryPath;do
		if echo "$disabledLibaries" | grep -q "$videoLibaryPath";then
			ALERT "Library path is disabled '$videoLibaryPath'"
			addToLog "INFO" "Library Scan Disabled" "Skipping scan for disabled path '$videoLibaryPath'"
		else
			ALERT "Scanning Libary Path... '$videoLibaryPath'"
			addToLog "INFO" "Scanning Library..." "$videoLibaryPath"
			# check the sum for this directory to see if the data has changed
			if checkDirSum "$webDirectory" "$videoLibaryPath";then
				addToLog "UPDATE" "Adding video" "$videoLibaryPath"

				INFO "scanning video path '$videoLibaryPath'"

				processvideoPath "$videoLibaryPath" "$webDirectory"

				setDirSum "$webDirectory" "$videoLibaryPath"
			else
				INFO "Already processed '$(basename "$videoLibaryPath")'"
			fi
		fi
	done
	foundGroups="$(find "/var/cache/2web/web/videos/" -type d -maxdepth 1 -mindepth 1 )"
	echo "$foundGroups" | sort | while read videoGroup;do
		#newestSeason="$(basename "$(find "$videoGroup" -type d -maxdepth 1 -mindepth 1 | sort | tail -1)")"
		videoGroup="$(basename "$videoGroup")"
		# generate the poster and fanart for the generated video groups
		# - limit updates to once per day
		#montage @"$webDirectory/videos/$videoGroup/$newestSeason/season.index" -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/videos/$videoGroup/fanart.png"

		#thumbnailList=$( find "$webDirectory/videos/$videoGroup/" -name "*-thumb.png" -printf "\"%h%f\" \n" | sort | tail -n 24 | shuf | sed "s/\\ /\\ /g" | sed "s/\n/ /g" )
		#thumbnailList=$( find "$webDirectory/videos/$videoGroup/" -name "*-thumb.png" -printf "\"%h/%f\" \n" | sort | tail -n 24 | shuf | sed "s/\n//g" )
		thumbnailList=$( find "$webDirectory/videos/$videoGroup/" -type f -name "*-thumb.png" | sort | tail -n 24 | shuf )
		thumbnailListCounter=1
		echo -n "$thumbnailList" | while read thumbnailListItem;do
			linkFile "$thumbnailListItem" "$webDirectory/videos/$videoGroup/fanart_temp_$thumbnailListCounter.png"
			# link the thumbnails to files without spaces
			thumbnailListCounter=$(( thumbnailListCounter + 1 ))
		done
		# draw fanart and poster based on the amout of videos in a group
		if [[ $thumbnailListCounter -ge 8 ]];then
			# draw the fanart
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-24].png -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/videos/$videoGroup/fanart.png"
			# draw the poster
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-8].png -background black -geometry 600x900\!+0+0 -tile 2x4 "$webDirectory/videos/$videoGroup/poster.png"
		elif [[ $thumbnailListCounter -eq 1 ]];then
			cp -v "$webDirectory/videos/$videoGroup/fanart_temp_0.png" "$webDirectory/videos/$videoGroup/fanart.png"
			cp -v "$webDirectory/videos/$videoGroup/fanart_temp_0.png" "$webDirectory/videos/$videoGroup/poster.png"
		elif [[ $thumbnailListCounter -eq 2 ]];then
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-2].png -background black -geometry 800x600\!+0+0 -tile 2x1 "$webDirectory/videos/$videoGroup/fanart.png"
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-2].png -background black -geometry 800x600\!+0+0 -tile 1x2 "$webDirectory/videos/$videoGroup/poster.png"
		elif [[ $thumbnailListCounter -eq 3 ]];then
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-3].png -background black -geometry 800x600\!+0+0 -tile 3x1 "$webDirectory/videos/$videoGroup/fanart.png"
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-3].png -background black -geometry 800x600\!+0+0 -tile 1x3 "$webDirectory/videos/$videoGroup/poster.png"
		else
			# less than 7 thumbnails means use less thumbnails for the images
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-4].png -background black -geometry 800x600\!+0+0 -tile 2x2 "$webDirectory/videos/$videoGroup/fanart.png"
			magick montage /var/cache/2web/web/videos/$videoGroup/fanart_temp_[1-4].png -background black -geometry 800x600\!+0+0 -tile 1x3 "$webDirectory/videos/$videoGroup/poster.png"
		fi
		#
		cp -v "$webDirectory/videos/$videoGroup/fanart-0.png" "$webDirectory/videos/$videoGroup/fanart.png"
		rm -v "$webDirectory/videos/$videoGroup/fanart-"*.png
		rm -v "$webDirectory/videos/$videoGroup"/fanart_temp_*.png
		#
		chown www-data:www-data "$webDirectory/videos/$videoGroup/fanart.png"
		chown www-data:www-data "$webDirectory/videos/$videoGroup/poster.png"
		#
		convert "$webDirectory/videos/$videoGroup/fanart.png" -trim -blur 1x1 "$webDirectory/videos/$videoGroup/fanart.png"
		#
		ALERT "Creating the fanart image from webpage..."
		convert "$webDirectory/videos/$videoGroup/fanart.png" -adaptive-resize 1920x1080\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 1920x1080 -gravity center caption:"$videoGroup" -composite "$webDirectory/videos/$videoGroup/fanart.png"
		#
		echo "Creating the poster image from webpage..."
		convert "$webDirectory/videos/$videoGroup/poster.png" -adaptive-resize 600x900\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 600x900 -gravity center caption:"$videoGroup" -composite "$webDirectory/videos/$videoGroup/poster.png"
		convert -quiet "$webDirectory/videos/$videoGroup/poster.png" -resize "300x200" "$webDirectory/videos/$videoGroup/poster-web.png"
		#
		linkFile "$webDirectory/videos/$videoGroup/poster.png" "$kodiDirectory/videos/$videoGroup/poster.png"
	done


	# the random index simply uses the main index for videos
	linkFile "$webDirectory/videos/videos.index" "$webDirectory/random/videos.index"
	addToLog "INFO" "FINISHED Web Update" "$(date)"
}
################################################################################
function resetCache(){
	# reset all generated/downloaded content
	# remove all the index files generated by the website
	find "/var/cache/2web/web/videos/" -name "*.index" -delete
	# remove web cache
	delete "/var/cache/2web/web/videos/"
	delete "/var/cache/2web/web/thumbnails/videos/"
}
################################################################################
function nuke(){
	webDirectory="$(webRoot)"
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedRoot)"
	kodiDirectory="$(kodiRoot)"
	# remove new and random indexes
	rm -v $webDirectory/new/video_*.index
	rm -v $webDirectory/random/video_*.index
	# kodi directories
	delete "$kodiDirectory/videos/"
	# remove video directory and indexes
	delete $webDirectory/videos/
	delete $webDirectory/new/videos.index
	delete $webDirectory/random/videos.index
	rm -v $webDirectory/sums/video2web_*.cfg || echo "No file sums found..."
	# remove sql data
	sqlite3 $webDirectory/data.db "drop table videos;"
	# remove widgets cached
	delete $webDirectory/web_cache/widget_random_videos.index
	delete $webDirectory/web_cache/widget_new_videos.index
	# remove thumbnails
	#delete "$webDirectory/thumbnails/video2web/"
	# remove search index data
	delete "/var/cache/2web/generated/searchIndexData/videos/"
	#
	drawLine
	drawHeader "NUKE Complete"
	drawLine
	ALERT "All file for the video2web module have been removed. video2web will rebuild all site info on the next automatic scan but can be done manually with 'video2web --parallel'. If you have videos that were converted from other formats you may also want to run 'video2web --reset' in order to remove any intermedary file formats created. Existing intermedary files will be added back on the next rescan even if the source files have been deleted." "WARNING"
	drawLine
	echo "You MUST remove downloaded videos, generated thumbnails, and converted video files with the 'video2web reset' command"
	drawLine
}
################################################################################
################################################################################
# set the theme of the lines in CLI output
LINE_THEME="lines"
#
INPUT_OPTIONS="$@"
PARALLEL_OPTION="$(loadOption "parallel" "$INPUT_OPTIONS")"
MUTE_OPTION="$(loadOption "mute" "$INPUT_OPTIONS")"
FAST_OPTION="$(loadOption "fast" "$INPUT_OPTIONS")"
#
if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
	lockProc "video2web"
	checkModStatus "video2web"
	webUpdate "$@"
elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
	lockProc "video2web"
	checkModStatus "video2web"
	update "$@"
elif [ "$1" == "--unlock" ] || [ "$1" == "unlock" ] ||  [ "$1" == "--stop" ] || [ "$1" == "stop" ];then
	# stop the running module
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	rm -v "/var/cache/2web/web/${moduleName}.active"
	killall "$moduleName"
elif [ "$1" == "--process" ] || [ "$1" == "process" ] ;then
	# process a single directory or rescan a single directory
	webDirectory=$(webRoot)
	kodiDirectory="$(kodiRoot)"
	# remove the sum blocking scanning for rescans
	rmDirSum "$webDirectory" "$2"
	# there is only one video this will prevent breaking the progress line
	totalvideos=1
	# scan the media
	if checkDirSum "$webDirectory" "$2";then
		processvideoPath "$2" "$webDirectory"
		setDirSum "$webDirectory" "$2"
	else
		INFO "Already processed '$(basename "$2")'"
	fi
elif [ "$1" == "--demo-data" ] || [ "$1" == "demo-data" ] ;then
	# generate demo data for use in screenshots, make it random as can be

	# check for parallel processing and count the cpus
	if [ "$PARALLEL_OPTION" == "yes" ];then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi
	#########################################################################################
	# video2web demo videos
	#########################################################################################
	createDir "/var/cache/2web/generated/demo/videos/"
	# build random videos
	for index in $(seq -w $(( 1 + ( $RANDOM % 10 ) )) );do
		# generate the random video name
		randomTitle="$RANDOM $(randomWord) $(randomWord)"
		#
		createDir "/var/cache/2web/generated/demo/videos/generated/$randomTitle/"
		# create a list of file extensions
		fileExtensions=".png .jpg .gif .webp .webm .mp4"
		# write the video pages
		for index2 in $(seq -w $(( 4 + ( $RANDOM % 25 ) )) );do
			# pick a random file extension for each demo image in the video
			extension=$( echo "$fileExtensions" | cut -d' ' -f$(( ( $RANDOM % $(echo "$fileExtensions" | wc --words) ) + 1 )) )
			if [ "$extension" == ".mp4" ] || [ "$extension" == ".webm" ] || [ "$extension" == ".gif" ];then
				# draw a animated image
				ffmpeg -i "/var/cache/2web/spinner.gif" -loop 10 -c:v libx264 -c:a aac "/var/cache/2web/generated/demo/videos/generated/$randomTitle/$index2${extension}" &
			else
				# draw a demo image
				demoImage "/var/cache/2web/generated/demo/videos/generated/$randomTitle/$index2${extension}" "${randomTitle} Page:$index2" "400" "700" &
			fi
			# wait for queue to be free
			waitQueue 0.2 "$totalCPUS"
		done
	done
	#
	blockQueue 1
	#########################################################################################
elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
	enableMod "video2web"
elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
	disableMod "video2web"
elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
	lockProc "video2web"
	nuke
elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
	lockProc "video2web"
	resetCache
elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
	# upgrade the pip packages if the module is enabled
	checkModStatus "video2web"
	lockProc "video2web"
	upgrade-pip "video2web" "gallery-dl dosage"
elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
	cat "/usr/share/2web/help/video2web.txt"
elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
	echo -n "Build Date: "
	cat /usr/share/2web/buildDate.cfg
	echo -n "video2web Version: "
	cat /usr/share/2web/version_video2web.cfg
else
	lockProc "video2web"
	checkModStatus "video2web"
	update "$@"
	webUpdate "$@"
	# on default execution show the server links at the bottom of output
	showServerLinks
	echo "Module Links"
	drawLine
	echo "http://$(hostname).local:80/videos/"
	drawLine
	echo "http://$(hostname).local:80/settings/videos.php"
	drawLine
fi
