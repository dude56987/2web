#! /bin/bash
########################################################################
# music2web scans music files with tags into the 2web webserver
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
# enable debug log
#set -x
source /var/lib/2web/common
################################################################################
function touchFile(){
	if ! test -f "$1";then
		touch "$1"
		# set ownership of directory and subdirectories as www-data
		chown www-data:www-data "$1"
	fi
}
################################################################################
function albumCheckDirSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	directory=$2
	# check the sum of a directory and compare it to a previously stored sum
	if ! test -d "$webDirectory/sums/";then
		mkdir -p "$webDirectory/sums/"
	fi
	pathSum="$(find "$directory" -type f -name "album.png" | sort | md5sum | cut -d' ' -f1 )"
	newSum="$(getDirSum "$2")"
	# check for a previous sum
	if test -f "$webDirectory/sums/music2web_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/music2web_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/music2web_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/music2web_$pathSum.cfg"
		return 0
	fi
}
########################################################################
function processTrack(){
	musicPath="$1"
	totalProgressString="$2"
	webDirectory=$(webRoot)
	if ! test -f "$musicPath";then
		ALERT "FILE PATH DOES NOT EXIST '$musicPath'"
		# the file path given does not exist so exit the loop
		return
	fi
	# check the md5sum of the music file
	if checkFileDataSum "$webDirectory" "$musicPath";then
		# get the web root
		webDirectory=$(webRoot)

		# build the blank data in case of bash wierdness with clearing variables
		artist=""
		album=""
		title=""
		disc=""
		track=""
		date=""
		length=""
		genre=""

		# try mediainfo for getting info on tracks
		musicData=$(mediainfo "$musicPath")

		# build the cleanup pipes
		artist=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "performer :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )
		album=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "album :" | head -n 1 | cut -d':' -f2  | tr --delete "'" | xargs )
		title=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "track name :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )
		disc=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "part/position :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )
		# if the disk value is not found mark the disk as disk number 1
		if echo "$disc" | grep -qE "[[:alpha:]]";then
			disc=""
		fi
		if ! echo "$disc" | grep -qE "[[:digit:]]";then
			disc=""
		fi
		track=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "track name/position :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )
		date=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "recorded date :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )
		length=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "duration :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )
		genre=$(echo "$musicData" | tr -s ' ' | grep --ignore-case "genre :" | head -n 1 | cut -d':' -f2 | tr --delete "'" | xargs )

		#if [ $( echo "$artist" | wc -c ) -le 0 ];then
		#	# get the music metadata
		#	musicData=$(ffprobe "$musicPath" |& cat)

		#	# build the cleanup pipes
		#	artist=$(echo "$musicData" | tr -s ' ' | grep "artist" | tac | tail -n 1 | cut -d':' -f2  | cut -c2- )
		#	album=$(echo "$musicData" | tr -s ' ' | grep "album" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
		#	title=$(echo "$musicData" | tr -s ' ' | grep "title" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
		#	disc=$(echo "$musicData" | tr -s ' ' | grep "disc" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- | cut -d'/' -f1 )
		#	track=$(echo "$musicData" | tr -s ' ' | grep "track" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 | cut -d'/' -f1 )
		#	date=$(echo "$musicData" | tr -s ' ' | grep "date" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 )
		#	length=$(echo "$musicData" | tr -s ' ' | grep "Duration" | cut -d'.' -f1 | cut -c2- | cut -d' ' -f2 )
		#	genre=$(echo "$musicData" | tr -s ' ' | grep "genre" | tac | tail -n 1 | cut -d':' -f3  | cut -c2- )
		#fi

		## check for metadata extraction failure and try other eyeD3
		#metadataBackup=0
		#if echo "$musicData" | grep -q "misdetection possible";then
		#	metadataBackup=1
		#elif [ $( echo "$artist" | wc -c ) -le 0 ];then
		#	metadataBackup=1
		#fi
		#if [ $metadataBackup -eq 1 ];then
		#	echo "Falling back to eyeD3 tag extraction..."
		#	# metadata fallback
		#	musicData=$(eyeD3 "$musicPath")
		#	artist=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^artist" | cut -d':' -f2  | cut -c2-)
		#	album=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^album" | cut -d':' -f2 | cut -c2- )
		#	disc=""
		#	date=""
		#	title=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^title" | cut -d':' -f2 | cut -c2-)
		#	track=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^track" | cut -d':' -f2  | cut -d' ' -f2 )
		#	length=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^Time" | cut -d' ' -f2 )
		#	genre=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "genre:" | cut -d':' -f3  | cut -c2-)
		#fi

		workingFile=0
		if echo "$musicData" | grep -q "misdetection possible";then
			workingFile=1
		elif [ "$artist" == "" ];then
			workingFile=1
		elif [ "$album" == "" ];then
			workingFile=1
		elif [ $( echo "$artist" | wc -c ) -le 0 ];then
			workingFile=1
		elif [ $( echo "$album" | wc -c ) -le 0 ];then
			workingFile=1
		fi

		if [ $workingFile -eq 0 ];then
			# cleanup common numbering schemes used for labeling discs
			album=$(echo "$album" | sed -E "s/[ ]{0,1}[(]{0,1}disc[ ]{0,1}[0-9]{0,3}[)]{0,1}[ ]{0,1}//Ig")
			album=$(echo "$album" | sed -E "s/[ ]{0,1}[(]{0,1}Disc[ ]{0,1}[0-9]{0,3}[)]{0,1}[ ]{0,1}//Ig")
			album=$(echo "$album" | sed -E "s/[ ]{0,1}[(]{0,1}disk[ ]{0,1}[0-9]{0,3}[)]{0,1}[ ]{0,1}//Ig")
			album=$(echo "$album" | sed -E "s/[ ]{0,1}[(]{0,1}Disk[ ]{0,1}[0-9]{0,3}[)]{0,1}[ ]{0,1}//Ig")
			album=$(echo "$album" | sed -E "s/[ ]{0,1}[(]{0,1}cd[ ]{0,1}[0-9]{0,3}[)]{0,1}[ ]{0,1}//Ig")
			album=$(echo "$album" | sed -E "s/[ ]{0,1}[(]{0,1}CD[ ]{0,1}[0-9]{0,3}[)]{0,1}[ ]{0,1}//Ig")

			# add the disc number to the album title to make multi disc albums work
			if [ "$disc" != "" ];then
				album="$album Disc $disc"
			fi
			#if [ "$date" != "" ];then
			#	album="$album ($date)"
			#fi

			artistOG="$artist"
			albumOG="$album"

			# paths must be cleaned up for compatiblity
			#artist=$(echo -n "$artist" | tr '[:upper:]' '[:lower:]' | sed "s/'/[\`,\',\"]/g" | tr --delete '[]`' )
			#album=$(echo -n "$album" | tr '[:upper:]' '[:lower:]' | sed "s/'/[\`,\',\"]/g"  | tr --delete '[]`' )
			artist=$(echo -n "$artist" | tr '[:upper:]' '[:lower:]' | tr --delete '`' )
			album=$(echo -n "$album" | tr '[:upper:]' '[:lower:]' | tr --delete '`' )

			# add the disk number preceding the track, this should fix strange formatting issues with multi disk albums
			#if [ "$disc" != "" ];then
			#	track="$disc$track"
			#	#track=$(echo "$track" | sed "s/^0*//")
			#fi
			# if the track is less than 10 add a preceding zero for sorting
			if [ "$track" -lt 10 ];then
				track="00$track"
			elif [ "$track" -lt 100 ];then
				track="0$track"
			fi

			# list the track info
			processingInfo="🎤:$artistOG | 💿:$albumOG | 🔢:$track | 🗓️:$date | ⚙️:$totalProgressString | "
			INFO "$processingInfo"
			#echo "################################################################################"
			#echo "================================================================================"
			#echo "Processing file: $musicPath"
			#echo "================================================================================"
			#echo "Processing data: $musicData"
			#echo "================================================================================"
			#echo "---------+---------------------------------------------------------------------+"
			#echo "ArtistOG | $artistOG"
			#echo "Artist   | $artist"
			#echo "Album    | $album"
			#echo "AlbumOG  | $albumOG"
			#echo "Disc     | $disc"
			#echo "Date     | $date"
			#echo "Title    | $title"
			#echo "Track    | $track"
			#echo "Length   | $length"
			#echo "Genre    | $genre"
			#echo "---------+---------------------------------------------------------------------+"

			# create the directories
			createDir "$webDirectory/music/$artist/$album/"
			createDir "$webDirectory/kodi/music/$artist/$album/"
			# link the file in the web path
			if echo "$musicPath" | grep -q ".mp3$";then
				INFO "${processingInfo}Linking mp3 to web directory..."
				linkFile "$musicPath" "$webDirectory/music/$artist/$album/${track}.mp3"
			else
				# convert  all found audio files to mp3 for compatibilty with the html5 player
				INFO "${processingInfo}Converting file to MP3..."
				ffmpeg -loglevel quiet -y -i "$musicPath" "$webDirectory/music/$artist/$album/${track}.mp3"
			fi
			linkFile "$webDirectory/music/$artist/$album/${track}.mp3" "$webDirectory/kodi/music/$artist/$album/${track}.mp3"

			################################################################################
			# get the music path and look for a thumbnail
			################################################################################
			if ! test -f "$webDirectory/kodi/music/$artist/$album/album.png";then
				lookingPath=$(echo "$musicPath" | rev | cut -d'/' -f2- | rev )

				if test -f "$lookingPath/album.png";then
					linkFile "$lookingPath/album.png" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/album.jpg";then
					convert -quiet "$lookingPath/album.jpg" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/folder.jpg";then
					convert -quiet "$lookingPath/folder.jpg" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/folder.png";then
					linkFile "$lookingPath/folder.png" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/diskart.jpg";then
					convert -quiet "$lookingPath/diskart.jpg" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/diskart.png";then
					linkFile "$lookingPath/diskart.png" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/discart.jpg";then
					convert -quiet "$lookingPath/discart.jpg" "$webDirectory/music/$artist/$album/album.png"
				elif test -f "$lookingPath/discart.png";then
					linkFile "$lookingPath/discart.png" "$webDirectory/music/$artist/$album/album.png"
				fi

				# try to find a album cover inside the mp3 file tags
				if ! test -f "$webDirectory/music/$artist/$album/album.png";then
					INFO "${processingInfo}Extracting album cover from file..."
					ffmpeg -loglevel quiet -y -i "$musicPath" -an -vcodec copy "$webDirectory/music/$artist/$album/album.png"
					#ffmpeg -loglevel quiet -y -ss 1 -i "$musicPath" -vframes 1 -f singlejpeg - | convert -quiet - "$webDirectory/music/$artist/$album/album.png"
				fi

				# generate a thumbnail for the album
				if ! test -f "$webDirectory/music/$artist/$album/album.png";then
					INFO "${processingInfo}Building Album Cover from file..."
					convert -quiet -size 800x800 plasma: "$webDirectory/music/$artist/$album/album.png"
					convert -quiet "$webDirectory/music/$artist/$album/album.png" -adaptive-resize 800x800\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 800x800 -gravity center caption:"$albumOG" -composite "$webDirectory/music/$artist/$album/album.png"
				fi

				# create a artist thumbnail from combining all the album covers
				if albumCheckDirSum "$webDirectory" "$webDirectory/music/$artist/";then
					albumCount=$( find "$webDirectory/music/$artist/" -type f -name "album.png" | wc -l )
					if [ $albumCount -ge 36 ];then
						tileType="9x4"
					elif [ $albumCount -ge 32 ];then
						tileType="8x4"
					elif [ $albumCount -ge 28 ];then
						tileType="7x4"
					elif [ $albumCount -ge 24 ];then
						tileType="6x4"
					elif [ $albumCount -ge 18 ];then
						tileType="6x3"
					elif [ $albumCount -ge 12 ];then
						tileType="6x2"
					elif [ $albumCount -ge 8 ];then
						tileType="4x2"
					elif [ $albumCount -ge 6 ];then
						tileType="3x2"
					elif [ $albumCount -ge 4 ];then
						tileType="4x1"
					elif [ $albumCount -eq 3 ];then
						tileType="3x1"
					elif [ $albumCount -eq 2 ];then
						tileType="2x1"
					elif [ $albumCount -eq 1 ];then
						tileType="1x1"
					fi

					INFO "${processingInfo}Building fanart..."
					montage "$webDirectory/music/$artist/"*/album.png -background black -geometry 800x600\!+0+0 -tile $tileType "$webDirectory/music/$artist/fanart.png"
					if test -f "$webDirectory/music/$artist/fanart-0.png";then
						# to many images exist use only first set
						cp "$webDirectory/music/$artist/fanart-0.png" "$webDirectory/music/$artist/fanart.png"
						# remove excess images
						rm "$webDirectory/music/$artist/fanart-"*.png
					fi
					if test -f "$webDirectory/music/$artist/fanart.png";then
						# trim and blur the fanart
						convert -quiet "$webDirectory/music/$artist/fanart.png" -trim -blur 5x5 "$webDirectory/music/$artist/fanart.png"
						# copy the artist fanart to the kodi folder
						convert -quiet "$webDirectory/music/$artist/fanart.png" "$webDirectory/kodi/music/$artist/fanart.jpg"
						linkFile "$webDirectory/music/$artist/fanart.png" "$webDirectory/kodi/music/$artist/landscape.jpg"
					fi
					################################################################################
					# build a new poster if the discovred albums have changed
					################################################################################
					INFO "${processingInfo}Building poster..."
					# figure out the number of albums and build a grid from 2 up to 5 wide
					# - sizes of 1 or lower are trimmed from the edges
					# - tiles should be able to fill up half of the square at least
					#albumCount=$( find "$webDirectory/music/$artist/" -type f -name "album.png" | wc -l )
					if [ $albumCount -ge 81 ];then
						tileType="9x9"
					elif [ $albumCount -ge 64 ];then
						tileType="8x8"
					elif [ $albumCount -ge 49 ];then
						tileType="7x7"
					elif [ $albumCount -ge 36 ];then
						tileType="6x6"
					elif [ $albumCount -ge 25 ];then
						tileType="5x5"
					elif [ $albumCount -ge 12 ];then
						tileType="4x4"
					elif [ $albumCount -ge 9 ];then
						tileType="3x3"
					elif [ $albumCount -ge 8 ];then
						tileType="4x2"
					elif [ $albumCount -ge 6 ];then
						tileType="3x2"
					elif [ $albumCount -ge 4 ];then
						tileType="2x2"
					elif [ $albumCount -eq 3 ];then
						tileType="3x1"
					elif [ $albumCount -eq 2 ];then
						tileType="2x1"
					elif [ $albumCount -eq 1 ];then
						tileType="1x1"
					fi

					# build the montage
					montage "$webDirectory/music/$artist/"*/album.png -background black -geometry 800x800\!+0+0 -tile $tileType "$webDirectory/music/$artist/poster.png"
					if test -f "$webDirectory/music/$artist/poster-0.png";then
						# to many images exist use only first set
						cp "$webDirectory/music/$artist/poster-0.png" "$webDirectory/music/$artist/poster.png"
						# remove excess images
						rm "$webDirectory/music/$artist/poster-"*.png
					fi
					if test -f "$webDirectory/music/$artist/poster.png";then
						convert -quiet "$webDirectory/music/$artist/poster.png" -trim "$webDirectory/music/$artist/poster.png"
						# resize the poster to resonable porportions
						convert -quiet "$webDirectory/music/$artist/poster.png" -adaptive-resize 800x800\! "$webDirectory/music/$artist/poster.png"
						# create the smaller thumbnail
						convert -quiet "$webDirectory/music/$artist/poster.png" -adaptive-resize 128x128\! "$webDirectory/music/$artist/poster-web.png"
						# create the kodi jpg artist poster thumb
						convert -quiet "$webDirectory/music/$artist/poster.png" "$webDirectory/kodi/music/$artist/folder.jpg"
					fi
					if test -f "$webDirectory/music/$artist/folder.png";then
						linkFile "$webDirectory/kodi/music/$artist/folder.jpg" "$webDirectory/kodi/music/$artist/clearart.jpg"
						linkFile "$webDirectory/kodi/music/$artist/folder.jpg" "$webDirectory/kodi/music/$artist/clearlogo.jpg"
					fi
				fi
				if ! test -f "$webDirectory/kodi/music/$artist/$album/cover.jpg";then
					convert -quiet "$webDirectory/music/$artist/$album/album.png" "$webDirectory/kodi/music/$artist/$album/cover.jpg"
				fi
				# build the web thumbnail
				if test -f "$webDirectory/music/$artist/$album/album.png";then
					convert -quiet "$webDirectory/music/$artist/$album/album.png" -adaptive-resize 128x128\! "$webDirectory/music/$artist/$album/album-web.png"
				fi
			fi
			################################################################################
			# create the nfo data
			################################################################################
			# artist
			if ! test -f "$webDirectory/kodi/music/$artist/artist.nfo";then
				{
					echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>'
					echo "<artist>"
					echo "	<name>$artistOG</name>"
					echo "</artist>"
				} > "$webDirectory/kodi/music/$artist/artist.nfo"
				# Create the artist index link
				{
					echo "<a class='indexLink button' href='/music/$artist'>"
					echo "	<img class='indexIcon' loading='lazy' src='/music/$artist/poster-web.png'>"
					echo "	<div class='indexTitle'>"
					echo "		$artistOG"
					echo "	</div>"
					echo "</a>"
				} > "$webDirectory/music/$artist/artist.index"

				# build the sql database entries
				SQLaddToIndex "$webDirectory/music/$artist/artist.index" "$webDirectory/data.db" "all"
				SQLaddToIndex "$webDirectory/music/$artist/artist.index" "$webDirectory/data.db" "music"
				SQLaddToIndex "$webDirectory/music/$artist/artist.index" "$webDirectory/data.db" "artists"

				#
				SQLaddToIndex "/music/$artist/poster.png" "$webDirectory/data.db" "all_poster"
				SQLaddToIndex "/music/$artist/poster.png" "$webDirectory/data.db" "music_poster"
				SQLaddToIndex "/music/$artist/poster.png" "$webDirectory/data.db" "artist_poster"

				SQLaddToIndex "/music/$artist/poster.png" "$webDirectory/backgrounds.db" "all_fanart"
				SQLaddToIndex "/music/$artist/poster.png" "$webDirectory/backgrounds.db" "music_fanart"
				SQLaddToIndex "/music/$artist/poster.png" "$webDirectory/backgrounds.db" "artist_fanart"

				# add artist to the main music index
				touchFile "$webDirectory/music/music.index"
				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/music/music.index"

				# add music to random indexes
				touchFile "$webDirectory/random/artists.index"
				touchFile "$webDirectory/random/music.index"
				touchFile "$webDirectory/random/all.index"
				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/random/artists.index"
				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/random/music.index"
				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/random/all.index"

				touchFile "$webDirectory/new/artists.index"
				touchFile "$webDirectory/new/music.index"
				touchFile "$webDirectory/new/all.index"

				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/artists.index"
				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/music.index"
				echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/all.index"

				# update last updated times
				date "+%s" > /var/cache/2web/web/new/all.cfg
				date "+%s" > /var/cache/2web/web/new/music.cfg
				date "+%s" > /var/cache/2web/web/new/artists.cfg
			fi
			# album data
			if ! test -f "$webDirectory/kodi/music/$artist/$album/album.nfo";then
				{
					echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>'
					echo "<album>"
					echo "	<title>$albumOG</title>"
					echo "	<genre>$genre</genre>"
					echo "	<artist>$artistOG</artist>"
					echo "</album>"
				} > "$webDirectory/kodi/music/$artist/$album/album.nfo"
				# add the album to the artist index
				{
					echo "<a class='indexLink button' href='/music/$artist/$album'>"
					echo "	<img class='indexIcon' loading='lazy' src='/music/$artist/$album/album-web.png'>"
					echo "	<div class='indexTitle'>"
					echo "		$albumOG"
					if [ "$date" != "" ];then
						echo "	<span>($date)</span>"
					fi
					echo "	</div>"
					echo "</a>"
				} > "$webDirectory/music/$artist/$album/album.index"

				SQLaddToIndex "$webDirectory/music/$artist/$album/album.index" "$webDirectory/data.db" "albums"
				SQLaddToIndex "$webDirectory/music/$artist/$album/album.index" "$webDirectory/data.db" "music"
				SQLaddToIndex "$webDirectory/music/$artist/$album/album.index" "$webDirectory/data.db" "all"

				SQLaddToIndex "/music/$artist/$album/album.png" "$webDirectory/data.db" "all_poster"
				SQLaddToIndex "/music/$artist/$album/album.png" "$webDirectory/data.db" "music_poster"
				SQLaddToIndex "/music/$artist/$album/album.png" "$webDirectory/data.db" "albums_poster"

				SQLaddToIndex "/music/$artist/$album/album.png" "$webDirectory/backgrounds.db" "all_fanart"
				SQLaddToIndex "/music/$artist/$album/album.png" "$webDirectory/backgrounds.db" "music_fanart"
				SQLaddToIndex "/music/$artist/$album/album.png" "$webDirectory/backgrounds.db" "albums_fanart"

				# add album to the artist index
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/music/$artist/albums.index"

				# add to new indexes
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/new/albums.index"
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/new/music.index"
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/new/all.index"

				# add to random indexes
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/random/albums.index"
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/random/music.index"
				echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/random/all.index"

				# update content found times
				date "+%s" > /var/cache/2web/web/new/all.cfg
				date "+%s" > /var/cache/2web/web/new/music.cfg
				date "+%s" > /var/cache/2web/web/new/albums.cfg
			fi
			# build the track thumbnail
			# create a waveform with ffmpeg for the track
			generateWaveform "$webDirectory/music/$artist/$album/$track.mp3" "$webDirectory/music/$artist/$album/$track" "$webDirectory/kodi/music/$artist/$album/$track"
			# generate the thumbnail
			convert -quiet "$webDirectory/music/$artist/$album/$track.png" -adaptive-resize 200x50\! "$webDirectory/music/$artist/$album/web-$track.png"
			# track data
			if ! test -f "$webDirectory/kodi/music/$artist/$album/$track.index";then
				# create the track link
				{
					echo "<a class='showPageEpisode track' href='/music/$artist/$album/?play=$track'>"
					echo "	<h2>$artist <hr> $album</h2>";
					echo "	<img class='indexIcon' loading='lazy' src='/music/$artist/$album/web-$track.png'>"
					echo "	<div class='indexTitle'>"
					echo "		$track $title"
					echo "	</div>"
					echo "</a>"
				} > "$webDirectory/music/$artist/$album/$track.index"
			fi

			################################################################################
			# link the webpage templates
			################################################################################
			# add the album webpage
			linkFile "/usr/share/2web/templates/album.php" "$webDirectory/music/$artist/$album/index.php"
			# add the artist webpage
			linkFile "/usr/share/2web/templates/artist.php" "$webDirectory/music/$artist/index.php"

			################################################################################
			# link .cfg files for webpages
			################################################################################
			echo "$artistOG" > "$webDirectory/music/$artist/artist.cfg"
			if [ "$genre" != "" ];then
				echo "$genre" > "$webDirectory/music/$artist/genre.cfg"
			fi

			echo "$albumOG" > "$webDirectory/music/$artist/$album/album.cfg"
			linkFile "$webDirectory/music/$artist/artist.cfg" "$webDirectory/music/$artist/$album/artist.cfg"
			if [ "$genre" != "" ];then
				linkFile "$webDirectory/music/$artist/genre.cfg" "$webDirectory/music/$artist/$album/genre.cfg"
			fi

			echo "$title" > "$webDirectory/music/$artist/$album/${track}_title.cfg"
			echo "$length" > "$webDirectory/music/$artist/$album/${track}_length.cfg"
			if [ "$genre" != "" ];then
				linkFile "$webDirectory/music/$artist/genre.cfg" "$webDirectory/music/$artist/$album/${track}_genre.cfg"
			fi

			SQLaddToIndex "$webDirectory/music/$artist/$album/${track}.index" "$webDirectory/data.db" "tracks"
			SQLaddToIndex "$webDirectory/music/$artist/$album/${track}.index" "$webDirectory/data.db" "all"
			# update content found times
			date "+%s" > /var/cache/2web/web/new/all.cfg
			date "+%s" > /var/cache/2web/web/new/tracks.cfg

			# add track to album track index
			echo "$webDirectory/music/$artist/$album/${track}.index" >> "$webDirectory/music/$artist/$album/tracks.index"
			# add tracks to the new tracks index
			echo "$webDirectory/music/$artist/$album/${track}.index" >> "$webDirectory/new/tracks.index"
			# random tracks index
			echo "$webDirectory/music/$artist/$album/${track}.index" >> "$webDirectory/random/tracks.index"
			# add to all
			echo "$webDirectory/music/$artist/$album/${track}.index" >> "$webDirectory/random/all.index"
			echo "$webDirectory/music/$artist/$album/${track}.index" >> "$webDirectory/new/all.index"

			# cleanup the track list for the album
			if test -f "$webDirectory/music/$artist/$album/tracks.index";then
				tempList=$(cat "$webDirectory/music/$artist/$album/tracks.index" )
				echo "$tempList" | sort -u > "$webDirectory/music/$artist/$album/tracks.index"
			fi
		fi
		setFileDataSum "$webDirectory" "$musicPath"
	else
		INFO "⚙️:$totalProgressString"
	fi
}
################################################################################
function buildVisual(){
	# Function for building visuals for mp3 files
	# - used for multithreading visual generation
	mp3FilePath=$1
	mp3FileName=$2
	webDirectory=$3
	# generate the visualization, with white waveforms at 12 fps ( the lowest framerate human eyes can handle )
	# if the ffmpeg process was successfull mark the render as complete with setFileDataSum
	# NOTE: webm codec is not multithreaded so only one CPU will be used, This might be something that the FFMPEG project needs to fix
	#< /dev/null ffmpeg -loglevel "quiet" -y -threads "$totalCPUS" -i "$mp3FilePath" -filter_complex "showwaves=mode=line:colors=white:r=12" -shortest "$mp3FileName.webm"
	# NOTE: can only use one thread for webm so set to one for multithreading purposes
	< /dev/null ffmpeg -loglevel "quiet" -y -threads "1" -i "$mp3FilePath" -filter_complex "showwaves=mode=line:colors=white:r=12" -shortest "$mp3FileName.webm"
	if [ $? -eq 0 ];then
		setFileDataSum "$webDirectory" "$mp3FilePath"
	fi
	ALERT "Found track '$mp3FileName', Finished Generating visualization..."
}
################################################################################
function update(){
	# this will launch a processing queue that downloads updates to music
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/music/libaries.cfg;then
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/music2web_libaries.cfg
		} > /etc/2web/music/libaries.cfg
	fi
	# load sources
	musicSources=$(grep -v "^#" /etc/2web/music/libaries.cfg)
	musicSources=$(echo -en "$musicSources\n$(grep --invert-match --no-filename "^#" /etc/2web/music/libaries.d/*.cfg)")
	musicSources=$(echo "$musicSources" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")

	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	#downloadDirectory="$(downloadDir)"
	################################################################################
	# make musics directory
	createDir "$webDirectory/music/"
	# setup the main index page
	linkFile "/usr/share/2web/templates/music.php" "$webDirectory/music/index.php"
	# copy over config page
	linkFile "/usr/share/2web/settings/music.php" "$webDirectory/music.php"
	# make the download directory if is does not exist
	#createDir "$downloadDirectory"
	# scan the sources
	ALERT "Scanning Music Sources: $musicSources"

	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi

	totalTracks=0
	processedTracks=0
	totalTrackList=""
	IFSBACKUP=$IFS
	IFS=$'\n'
	# tally up the total tracks
	#for musicSource in $musicSources;do
	#	ALERT "MUSIC SOURCE: $musicSource"
	#	if checkDirSum "$webDirectory" "$musicSource";then
	#		tempFoundTracks=$(find "$musicSource" -type f | grep -E ".mp3$|.wma$|.flac$|.ogg$" | wc -l)
	#		tempFoundTrackCount=$(echo "$tempFoundTracks" | wc -l )
	#		totalTrackList="${totalTrackList}${tempFoundTracks}\n"
	#		totalTracks=$(( $totalTracks + $tempFoundTrackCount ))
	#	fi
	#done
	#musicFiles=$(echo "$totalTrackList" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")
	#IFS=$IFSBACKUP
	#echo "_____________"
	#echo "= $totalTracks"

	#ALERT "Total Track List = '$totalTrackList'"
	#ALERT "CLI OPTIONS $@"
	#echo "$musicSources" | sort | while read -r musicSource;do
	totalSources=0
	for musicSource in $musicSources;do
		totalSources=$(( $totalSources + 1 ))
	done
	processedSources=0
	# musicSources need to be shuffled since some music files crash the processing
	echo "$musicSources" | shuf | while read -r musicSource;do
		processedSources=$(( $processedSources + 1 ))
		INFO "Processing '$musicSource'"
		if checkDirSum "$webDirectory" "$musicSource";then
			#ALERT "MUSIC SOURCE: $musicSource"
			# scan inside the music source directories for mp3 files
			#musicFiles=$totalTrackList
			musicFiles=$(find "$musicSource" -type f | grep -E ".mp3$|.wma$|.flac$|.ogg$")
			musicFiles=$(echo "$musicFiles" | tr -s ' ' | tr -s '\n' | sed "s/\t//g" | sed "s/^ //g")
			#
			tempFoundTrackCount=$(echo "$musicFiles" | wc -l )
			totalTracks=$(( $totalTracks + $tempFoundTrackCount ))
			#
			#ALERT "MUSIC FILES : $musicFiles"
			IFS=$'\n'
			#echo "$musicFiles\n$musicFiles" | uniq | shuf | while read -r musicPath;do
			for musicPath in $musicFiles;do
				#ALERT "MUSIC FILE PATH IN LOOP : $musicPath"
				# block for parallel threads here if there are more threads than cpus
				# block adding thread if there are more threads than cpus
				#ALERT "Processing Track $musicPath"
				processTrack "$musicPath" "$processedTracks/$totalTracks [$processedSources/$totalSources]" "$@" &
				processedTracks=$(( $processedTracks + 1 ))
				waitFastQueue 0.2 "$totalCPUS"
			done
			setDirSum "$webDirectory" "$musicSource"
		fi
		IFS=$IFSBACKUP
	done

	# check config file but default to "no"
	if yesNoCfgCheck "/etc/2web/music/generateVisualisationsForWeb.cfg" "no";then
		foundFiles=$(find "/var/cache/2web/web/music/" -name '*.mp3')
		totalVisuals=$(echo "$foundFiles" | wc -l)
		visualCounter=0
		# generate visualizations for music tracks after the base mp3 processing and webpage generation has been completed, this is the longest processing task
		echo "$foundFiles" | shuf | while read mp3FilePath;do
			visualCounter=$((visualCounter + 1))
			INFO "[$visualCounter/$totalVisuals] Visuals Generated..."
			mp3FileName=$(echo "$mp3FilePath" | sed "s/.mp3//g")
			# extract the cbz file to the download directory
			INFO "[$visualCounter/$totalVisuals] Found track '$mp3FileName', Checking visualization..."
			if checkFileDataSum "$webDirectory" "$mp3FilePath";then
				INFO "[$visualCounter/$totalVisuals] Found track '$mp3FileName', Generating visualization..."
				buildVisual "$mp3FilePath" "$mp3FileName" "$webDirectory" &
				waitFastQueue 0.2 "$totalCPUS"
			fi
			blockQueue 1
		done
	fi

	# block for parallel threads here
	blockQueue 1
	# cleanup the music index
	if test -f "$webDirectory/music/music.index";then
		tempList=$(cat "$webDirectory/music/music.index" | sort -u )
		echo "$tempList" > "$webDirectory/music/music.index"
	fi
	if test -f "$webDirectory/music/artists.index";then
		tempList=$(cat "$webDirectory/music/artists.index" | sort -u )
		echo "$tempList" > "$webDirectory/music/artists.index"
	fi
	if test -f "$webDirectory/music/tracks.index";then
		tempList=$(cat "$webDirectory/music/tracks.index" | sort -u )
		echo "$tempList" > "$webDirectory/music/tracks.index"
	fi

	# cleanup new music index
	if test -f "$webDirectory/new/music.index";then
		tempList=$(cat "$webDirectory/new/music.index" | uniq | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/music.index"
	fi
	if test -f "$webDirectory/new/artists.index";then
		tempList=$(cat "$webDirectory/new/artists.index" | uniq | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/artists.index"
	fi
	if test -f "$webDirectory/new/tracks.index";then
		tempList=$(cat "$webDirectory/new/tracks.index" | uniq | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/tracks.index"
	fi

	# cleanup random music index
	if test -f "$webDirectory/random/music.index";then
		tempList=$(cat "$webDirectory/random/music.index" | sort -u )
		echo "$tempList" > "$webDirectory/new/music.index"
	fi
	if test -f "$webDirectory/random/artists.index";then
		tempList=$(cat "$webDirectory/random/artists.index" | sort -u )
		echo "$tempList" > "$webDirectory/new/artists.index"
	fi
	if test -f "$webDirectory/random/tracks.index";then
		tempList=$(cat "$webDirectory/random/tracks.index" | sort -u )
		echo "$tempList" > "$webDirectory/new/tracks.index"
	fi
	# update kodi clients
	if test -f /usr/bin/kodi2web;then
		# update video libaries on all kodi clients, if no video playback is detected
		/usr/bin/kodi2web audio
	fi
}
################################################################################
function cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	if [ -f /usr/bin/inline-detox ];then
		echo "$1" | inline-detox --remove-trailing | sed "s/_/ /g" | tr -d '#'
	else
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
	fi
}
################################################################################
function getJson(){
	# load the json string value
	jsonData=$1
	valueToGrab=$2
	# check for various page json values that can exist
	data=$(echo "$jsonData" | jq -r ".$valueToGrab")
	if echo "$data" | grep -q "null";then
		return 1
	fi
	# clean up the text in the name
	echo "$data"
	return 0
}
################################################################################
function popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg"
	echo "$1" | rev | cut -d'/' -f1 | rev
}
################################################################################
function pickPath(){
	# pop a element from the end of the path, $2 is how far back in the path is pulled
	echo "$1" | rev | cut -d'/' -f$2 | rev
}
################################################################################
function webUpdate(){
	webDirectory=$(webRoot)
	#downloadDirectory="$(downloadDir)"

	# run the regular update
	update $@
}
################################################################################
function resetCache(){
	webDirectory=$(webRoot)
	# remove web cache
	rm -rv "$webDirectory/music/"
	exit
	find "$webDirectory/music/" -mindepth 1 -maxdepth 1 -type d | while read -r musicPath;do
		if [ ! "$musicPath" == "$webDirectory/musicCache/" ];then
			# music
			echo "rm -rv '$musicPath'"
		fi
	done
}
################################################################################
function INFO(){
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
################################################################################
function nuke(){
	webDirectory=$(webRoot)
	# remove the kodi and web music files
	rm -rv "$webDirectory/music/" || echo "No files found in music web directory..."
	rm -rv "$webDirectory/kodi/music/" || echo "No files found in kodi directory..."
	rm -rv $webDirectory/sums/music2web_*.cfg || echo "No file sums found..."
	#
	rm -rv $webDirectory/web_cache/widget_random_music.index || echo "No file sums found..."
	rm -rv $webDirectory/web_cache/widget_random_artists.index || echo "No file sums found..."
	rm -rv $webDirectory/web_cache/widget_random_albums.index || echo "No file sums found..."
	#
	rm -rv $webDirectory/web_cache/widget_updated_music.index || echo "No file sums found..."
	rm -rv $webDirectory/web_cache/widget_updated_artists.index || echo "No file sums found..."
	rm -rv $webDirectory/web_cache/widget_updated_albums.index || echo "No file sums found..."
	# create the database path
	databasePath="$webDirectory/data.db"
	# remove sql data
	SQLremoveTable "$databasePath" "_music"
	SQLremoveTable "$databasePath" "_albums"
	SQLremoveTable "$databasePath" "_artists"
	# new indexes
	rm -rv "$webDirectory/new/music.index" || echo "No music index..."
	rm -rv "$webDirectory/new/albums.index" || echo "No album index..."
	rm -rv "$webDirectory/new/artists.index" || echo "No artist index..."
	rm -rv "$webDirectory/new/tracks.index" || echo "No track index..."
	# random indexes
	rm -rv "$webDirectory/random/music.index" || echo "No music index..."
	rm -rv "$webDirectory/random/albums.index" || echo "No album index..."
	rm -rv "$webDirectory/random/artists.index" || echo "No artist index..."
	rm -rv "$webDirectory/random/tracks.index" || echo "No track index..."
}
################################################################################
function main(){
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		checkModStatus "music2web"
		lockProc "music2web"
		webUpdate $@
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "music2web"
		lockProc "music2web"
		update $@
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		lockProc "music2web"
		resetCache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		lockProc "music2web"
		nuke
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "music2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "music2web"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "music2web Version: "
		cat /usr/share/2web/version_music2web.cfg
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/music2web.txt"
	else
		checkModStatus "music2web"
		lockProc "music2web"
		update $@
		#webUpdate $@
		#main --help $@
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/music/"
		drawLine
		echo "http://$(hostname).local:80/settings/music.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
