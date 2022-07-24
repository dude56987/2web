#! /bin/bash
################################################################################
# enable debug log
#set -x
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/2web/web.cfg ];then
		webDirectory=$(cat /etc/2web/web.cfg)
	else
		chown -R www-data:www-data "/var/cache/2web/web/"
		echo "/var/cache/2web/web" > /etc/2web/web.cfg
		webDirectory="/var/cache/2web/web"
	fi
	echo "$webDirectory"
}
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
linkFile(){
	if ! test -L "$2";then
		ln -sf "$1" "$2"
		# DEBUG: log each linked file
		#echo "ln -sf '$1' '$2'" >> /var/cache/2web/web/linkedFiles.log
	fi
}
################################################################################
createDir(){
	if ! test -d "$1";then
		mkdir -p "$1"
		# set ownership of directory and subdirectories as www-data
		chown -R www-data:www-data "$1"
	fi
	chown www-data:www-data "$1"
}
################################################################################
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
################################################################################
albumCheckDirSum(){
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
	if test -f "$webDirectory/sums/music_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/music_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/music_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/music_$pathSum.cfg"
		return 0
	fi
}
################################################################################
checkFileDataSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	filePath=$2
	# check the sum of a directory and compare it to a previously stored sum
	if ! test -d "$webDirectory/sums/";then
		mkdir -p "$webDirectory/sums/"
	fi
	pathSum="$(echo "$filePath" | md5sum | cut -d' ' -f1 )"
	newSum="$( cat "$filePath" | md5sum | cut -d' ' -f1 )"
	# check for a previous sum
	if test -f "$webDirectory/sums/file_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/file_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/file_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/file_$pathSum.cfg"
		return 0
	fi
}
########################################################################
ALERT(){
	echo
	echo "$1";
	echo
}
################################################################################
function update(){
	# this will launch a processing queue that downloads updates to music
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/music/libaries.cfg;then
		# if no config exists create the default config
		{
		echo "##################################################"
		echo "# Example Config"
		echo "##################################################"
		echo "# - Directories on the server to be deep scanned "
		echo "#   for .mp3 files"
		echo "#  ex."
		echo "#    /var/cache/2web/music/"
		echo "##################################################"
		} > /etc/2web/music/libaries.cfg
	fi
	# load sources
	musicSources=$(grep -v "^#" /etc/2web/music/libaries.cfg)
	musicSources=$(echo -en "$musicSources\n$(grep --invert-match --no-filename "^#" /etc/2web/music/libaries.d/*.cfg)")
	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	#downloadDirectory="$(downloadDir)"
	################################################################################
	# setup the main index page
	linkFile "/usr/share/2web/templates/music.php" "$webDirectory/music/index.php"
	# copy updated movies widget
	linkFile "/usr/share/2web/templates/updatedMusic.php" "$webDirectory/updatedMusic.php"
	# copy over config page
	linkFile "/usr/share/2web/settings/music.php" "$webDirectory/music.php"
	# make the download directory if is does not exist
	#createDir "$downloadDirectory"
	# make musics directory
	createDir "$webDirectory/music/"
	# scan the sources
	ALERT "Scanning Music Sources: $musicSources"
	echo "$musicSources" | shuf | while read musicSource;do
		# scan inside the music source directories for mp3 files
		#find "$musicSource" -type f -name "*.*" | grep -qE "*.(mp3|wma|ogg|flac)" | shuf | while read musicPath;do
		#find "$musicSource" -type f | grep -qE "*\.(mp3|wma|ogg|flac)" | shuf | while read musicPath;do
		#find "$musicSource" -type f -name "*.mp3" | while read musicPath;do
		#ALERT "Scanning Music Source: $musicSource"
		find "$musicSource" -type f | grep -E ".mp3$|.wma$|.flac$" | shuf | while read musicPath;do
			webDirectory=$(webRoot)
			# check the md5sum of the music file
			if checkFileDataSum "$webDirectory" "$musicPath";then
				# get the web root
				webDirectory=$(webRoot)

				INFO "Attempting ffprobe metadata extraction..."
				# build the blank data in case of bash wierdness with clearing variables
				artist=""
				album=""
				title=""
				disc=""
				track=""
				date=""
				length=""
				genre=""

				# get the music metadata
				musicData=$(ffprobe "$musicPath" |& cat)
				# build the cleanup pipes
				set -x
				artist=$(echo "$musicData" | tr -s ' ' | grep "artist" | tac | tail -n 1 | cut -d':' -f2  | cut -c2- )
				album=$(echo "$musicData" | tr -s ' ' | grep "album" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
				title=$(echo "$musicData" | tr -s ' ' | grep "title" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
				disc=$(echo "$musicData" | tr -s ' ' | grep "disc" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- | cut -d'/' -f1 )
				#disc=$(echo "$musicData" | tr -s ' ' | grep "disc" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
				#if echo "$disc" | grep "/";then
				#	disc=$(echo "$musicData" | cut -d'/' -f1 )
				#fi
				track=$(echo "$musicData" | tr -s ' ' | grep "track" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 | cut -d'/' -f1 )
				#track=$(echo "$musicData" | tr -s ' ' | grep "track" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 )
				#if echo "$track" | grep "/";then
				#	track=$(echo "$musicData" | cut -d'/' -f1 )
				#fi
				date=$(echo "$musicData" | tr -s ' ' | grep "date" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 )
				length=$(echo "$musicData" | tr -s ' ' | grep "Duration" | cut -d'.' -f1 | cut -c2- | cut -d' ' -f2 )
				genre=$(echo "$musicData" | tr -s ' ' | grep "genre" | tac | tail -n 1 | cut -d':' -f3  | cut -c2- )
				set +x

				# check for metadata extraction failure and try other eyeD3
				metadataBackup=0
				if echo "$musicData" | grep -q "misdetection possible";then
					metadataBackup=1
				elif [ $( echo "$artist" | wc -c ) -le 0 ];then
					metadataBackup=1
				fi
				if [ $metadataBackup -eq 1 ];then
					echo "Falling back to eyeD3 tag extraction..."
					# metadata fallback
					musicData=$(eyeD3 "$musicPath")
					artist=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^artist" | cut -d':' -f2  | cut -c2-)
					album=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^album" | cut -d':' -f2 | cut -c2- )
					disc=""
					date=""
					title=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^title" | cut -d':' -f2 | cut -c2-)
					track=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^track" | cut -d':' -f2  | cut -d' ' -f2 )
					length=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^Time" | cut -d' ' -f2 )
					genre=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "genre:" | cut -d':' -f3  | cut -c2-)
				fi

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
					# add the disc number to the album title to make multi disc albums work
					if [ "$disc" != "" ];then
						album="$album $disc"
					fi

					artistOG=$artist
					albumOG=$album

					# paths must be cleaned up for compatiblity
					artist=$(echo -n "$artist" | tr '[:upper:]' '[:lower:]' | sed "s/'/\`/g" )
					album=$(echo -n "$album" | tr '[:upper:]' '[:lower:]' | sed "s/'/\`/g" )

					# if the track is less than 10 add a preceding zero for sorting
					if [ "$track" -lt 10 ];then
						track="0$track"
					fi

					# list the track info
					processingInfo="🎤:$artistOG | 💿:$albumOG | 🔢:$track | 🗓️:$date | "
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
							ffmpeg -loglevel quiet -i "$musicPath" -an -vcodec copy "$webDirectory/music/$artist/$album/album.png"
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
							# trim and blur the fanart
							convert -quiet "$webDirectory/music/$artist/fanart.png" -trim -blur 5x5 "$webDirectory/music/$artist/fanart.png"
							# copy the artist fanart to the kodi folder
							convert -quiet "$webDirectory/music/$artist/fanart.png" "$webDirectory/kodi/music/$artist/fanart.jpg"
							linkFile "$webDirectory/music/$artist/fanart.png" "$webDirectory/kodi/music/$artist/landscape.jpg"

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
							convert -quiet "$webDirectory/music/$artist/poster.png" -trim "$webDirectory/music/$artist/poster.png"
							# resize the poster to resonable porportions
							convert -quiet "$webDirectory/music/$artist/poster.png" -adaptive-resize 800x800\! "$webDirectory/music/$artist/poster.png"
							# create the smaller thumbnail
							convert -quiet "$webDirectory/music/$artist/poster.png" -adaptive-resize 128x128\! "$webDirectory/music/$artist/poster-web.png"
							# create the kodi jpg artist poster thumb
							convert -quiet "$webDirectory/music/$artist/poster.png" "$webDirectory/kodi/music/$artist/folder.jpg"
							linkFile "$webDirectory/kodi/music/$artist/folder.jpg" "$webDirectory/kodi/music/$artist/clearart.jpg"
							linkFile "$webDirectory/kodi/music/$artist/folder.jpg" "$webDirectory/kodi/music/$artist/clearlogo.jpg"
						fi
						if ! test -f "$webDirectory/kodi/music/$artist/$album/cover.jpg";then
							convert -quiet "$webDirectory/music/$artist/$album/album.png" "$webDirectory/kodi/music/$artist/$album/cover.jpg"
						fi
						# build the web thumbnail
						convert -quiet "$webDirectory/music/$artist/$album/album.png" -adaptive-resize 128x128\! "$webDirectory/music/$artist/$album/album-web.png"
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
							echo "<a class='indexSeries' href='/music/$artist'>"
							echo "	<img class='albumArt' loading='lazy' src='/music/$artist/poster-web.png'>"
							echo "	<div class='title'>"
							echo "		$artistOG"
							echo "	</div>"
							echo "</a>"
						} > "$webDirectory/music/$artist/artist.index"
						# add artist to the main music index
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/music/music.index"

						# add music to random indexes
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/random/music.index"
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/random/all.index"

						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/artists.index"
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/music.index"
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/all.index"
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
							echo "<a class='indexSeries' href='/music/$artist/$album'>"
							#echo "	<h2>$date</h2>"
							echo "	<img class='albumArt' loading='lazy' src='/music/$artist/$album/album-web.png'>"
							echo "	<div class='title'>"
							echo "		$albumOG"
							echo "	</div>"
							echo "</a>"
						} > "$webDirectory/music/$artist/$album/album.index"
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
					fi
					# track data
					if ! test -f "$webDirectory/kodi/music/$artist/$album/$track.index";then
						# create the track link
						{
							echo "<a class='showPageEpisode' href='/music/$artist/$album/?play=$track'>"
							echo "	<div class='title'>"
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
			fi
		done
	done
	# cleanup the music index
	if test -f "$webDirectory/music/music.index";then
		tempList=$(cat "$webDirectory/music/music.index" )
		echo "$tempList" | sort -u > "$webDirectory/music/music.index"
	fi
	# cleanup new music index
	if test -f "$webDirectory/new/music.index";then
		# new music
		tempList=$(cat "$webDirectory/new/music.index" | uniq | tail -n 200 )
		echo "$tempList" > "$webDirectory/new/music.index"
	fi
}
################################################################################
cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	if [ -f /usr/bin/inline-detox ];then
		echo "$1" | inline-detox --remove-trailing | sed "s/_/ /g" | tr -d '#'
	else
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
	fi
}
################################################################################
getJson(){
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
popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg"
	echo "$1" | rev | cut -d'/' -f1 | rev
}
################################################################################
pickPath(){
	# pop a element from the end of the path, $2 is how far back in the path is pulled
	echo "$1" | rev | cut -d'/' -f$2 | rev
}
################################################################################
webUpdate(){
	webDirectory=$(webRoot)
	#downloadDirectory="$(downloadDir)"

	# run the regular update
	update
}
################################################################################
function resetCache(){
	webDirectory=$(webRoot)
	# remove web cache
	rm -rv "$webDirectory/music/"
	exit
	find "$webDirectory/music/" -mindepth 1 -maxdepth 1 -type d | while read musicPath;do
		if [ ! "$musicPath" == "$webDirectory/musicCache/" ];then
			# music
			echo "rm -rv '$musicPath'"
		fi
	done
}
################################################################################
function lockCheck(){
	if test -f "/tmp/music2web.active";then
		# system is already running exit
		echo "[INFO]: music2web is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/music2web.active'."
		exit
	else
		# set the active flag
		touch /tmp/music2web.active
		# create a trap to remove music2web lockfile
		trap "rm /tmp/music2web.active" EXIT
	fi
}
################################################################################
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
################################################################################
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
################################################################################
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		lockCheck
		webUpdate
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		lockCheck
		update
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		lockCheck
		resetCache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		lockCheck
		# remove the kodi and web music files
		rm -rv $(webRoot)/music/* || echo "No files found in music web directory..."
		rm -rv $(webRoot)/sums/* || echo "No file sums found in kodi directory..."
		rm -rv $(webRoot)/kodi/music/* || echo "No files found in kodi directory..."
		rm -rv $(webRoot)/new/music.index || echo "No music index..."
		rm -rv $(webRoot)/new/album.index || echo "No album index..."
		rm -rv $(webRoot)/new/artist.index || echo "No artist index..."
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade gallery-dl pip packages
		pip3 install --upgrade gallery-dl
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/music2web.txt"
	else
		main --update
		main --webgen
		main --help
	fi
}
################################################################################
main "$@"
exit