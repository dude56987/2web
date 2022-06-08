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
	musicSources=$(echo -e "$musicSources\n$(grep -v "^#" /etc/2web/music/libaries.d/*.cfg)")
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
	echo "$musicSources" | shuf | while read musicSource;do
		# scan inside the music source directories for mp3 files
		#find "$musicSource" -type f -name "*.*" | grep -qE "*.(mp3|wma|ogg|flac)" | shuf | while read musicPath;do
		#find "$musicSource" -type f | grep -qE "*\.(mp3|wma|ogg|flac)" | shuf | while read musicPath;do
		find "$musicSource" -type f -name "*.mp3" | shuf | while read musicPath;do
			webDirectory=$(webRoot)
			# check the md5sum of the music file
			if checkFileDataSum "$webDirectory" "$musicPath";then
				# get the web root
				webDirectory=$(webRoot)
				#if test -f /usr/bin/eyeD3_DEBUG;then
				## get the music metadata
				#musicData=$(eyeD3 "$musicPath")
				#artist=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^artist" | cut -d':' -f2  | cut -c2-)
				#album=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^album" | cut -d':' -f2 | cut -c2- )
				#title=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^title" | cut -d':' -f2 | cut -c2-)
				#track=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^track" | cut -d':' -f2  | cut -d' ' -f2 )
				#length=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "^Time" | cut -d' ' -f2 )
				#genre=$(echo "$musicData" | sed "s/^ / /g" | sed "s/\n/ /g" | sed "s/\t/ /g" | tr -s ' ' | grep "genre:" | cut -d':' -f3  | cut -c2-)

				echo "Attempting ffprobe metadata extraction..."
				# get the music metadata
				musicData=$(ffprobe "$musicPath" |& cat)
				artist=$(echo "$musicData" | tr -s ' ' | grep "artist" | tac | tail -n 1 | cut -d':' -f2  | cut -c2- )
				album=$(echo "$musicData" | tr -s ' ' | grep "album" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
				title=$(echo "$musicData" | tr -s ' ' | grep "title" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- )
				disc=$(echo "$musicData" | tr -s ' ' | grep "disc" | tac | tail -n 1 | cut -d':' -f2 | cut -c2- | cut -d'/' -f1 )
				track=$(echo "$musicData" | tr -s ' ' | grep "track" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 | cut -d'/' -f1 )
				date=$(echo "$musicData" | tr -s ' ' | grep "date" | tac | tail -n 1 | cut -d':' -f2  | cut -d' ' -f2 )
				length=$(echo "$musicData" | tr -s ' ' | grep "Duration" | cut -d'.' -f1 | cut -c2- | cut -d' ' -f2 )
				genre=$(echo "$musicData" | tr -s ' ' | grep "genre" | tac | tail -n 1 | cut -d':' -f3  | cut -c2- )
				metadataBackup=0
				# check for metadata extraction failure and try other eyeD3
				if echo "$musicData" | grep "misdetection possible";then
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
				if echo "$musicData" | grep "misdetection possible";then
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

					# path artist
					artist=$(echo -n "$artist" | tr '[:upper:]' '[:lower:]' )
					album=$(echo -n "$album" | tr '[:upper:]' '[:lower:]' )

					# if the track is less than 10 add a preceding zero for sorting
					if [ "$track" -lt 10 ];then
						track="0$track"
					fi

					# list the track info
					echo "################################################################################"
					echo "================================================================================"
					echo "Processing file: $musicPath"
					echo "================================================================================"
					echo "Processing data: $musicData"
					echo "================================================================================"
					echo "---------+---------------------------------------------------------------------+"
					echo "ArtistOG | $artistOG"
					echo "Artist   | $artist"
					echo "Album    | $album"
					echo "AlbumOG  | $albumOG"
					echo "Disc     | $disc"
					echo "Date     | $date"
					echo "Title    | $title"
					echo "Track    | $track"
					echo "Length   | $length"
					echo "Genre    | $genre"
					echo "---------+---------------------------------------------------------------------+"

					# create the directories
					createDir "$webDirectory/music/$artist/$album/"
					createDir "$webDirectory/kodi/music/$artist/$album/"
					# link the file in the web path
					linkFile "$musicPath" "$webDirectory/music/$artist/$album/${track}.mp3"
					linkFile "$musicPath" "$webDirectory/kodi/music/$artist/$album/${track}.mp3"

					################################################################################
					# get the music path and look for a thumbnail
					################################################################################
					set -x
					if ! test -f "$webDirectory/kodi/music/$artist/$album/album.png";then
						lookingPath=$(echo "$musicPath" | rev | cut -d'/' -f2- | rev )
						if test -f "$lookingPath/album.png";then
							linkFile "$lookingPath/album.png" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/album.jpg";then
							convert "$lookingPath/album.jpg" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/folder.jpg";then
							convert "$lookingPath/folder.jpg" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/folder.png";then
							linkFile "$lookingPath/folder.png" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/diskart.jpg";then
							convert "$lookingPath/diskart.jpg" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/diskart.png";then
							linkFile "$lookingPath/diskart.png" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/discart.jpg";then
							convert "$lookingPath/discart.jpg" "$webDirectory/music/$artist/$album/album.png"
						elif test -f "$lookingPath/discart.png";then
							linkFile "$lookingPath/discart.png" "$webDirectory/music/$artist/$album/album.png"
						fi

						if ! test -f "$webDirectory/music/$artist/$album/album.png";then
							convert -size 600x900 plasma: "$webDirectory/music/$artist/$album/album.png"
							convert "$webDirectory/music/$artist/$album/album.png" -adaptive-resize 600x900\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 600x900 -gravity center caption:"$albumOG" -composite "$webDirectory/music/$artist/$album/album.png"
						fi
						# create a artist thumbnail from combining all the album coversA
						if cacheCheck "$webDirectory/music/$artist/fanart.png" "10";then
							montage "$webDirectory/music/$artist/"*/album.png -background black -geometry 800x600\!+0+0 -tile 6x4 "$webDirectory/music/$artist/fanart.png"
							if test -f "$webDirectory/music/$artist/fanart-0.png";then
								# to many images exist use only first set
								cp "$webDirectory/music/$artist/fanart-0.png" "$webDirectory/music/$artist/fanart.png"
								# remove excess images
								rm "$webDirectory/music/$artist/poster-"*.png
							fi
							convert "$webDirectory/music/$artist/fanart.png" -trim -blur 40x40 "$webDirectory/music/$artist/fanart.png"
						fi
						if cacheCheck "$webDirectory/music/$artist/poster.png" "10";then
							montage "$webDirectory/music/$artist/"*/album.png -background black -geometry 800x600\!+0+0 -tile 2x6 "$webDirectory/music/$artist/poster.png"
							if test -f "$webDirectory/music/$artist/poster-0.png";then
								# to many images exist use only first set
								cp "$webDirectory/music/$artist/poster-0.png" "$webDirectory/music/$artist/poster.png"
								# remove excess images
								rm "$webDirectory/music/$artist/poster-"*.png
							fi
							convert "$webDirectory/music/$artist/poster.png" -trim -blur 40x40 "$webDirectory/music/$artist/poster.png"
							convert "$webDirectory/music/$artist/poster.png" -adaptive-resize 600x900\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 5 -size 600x900 -gravity center caption:"$artistOG" -composite "$webDirectory/music/$artist/poster.png"
						fi
						linkFile "$webDirectory/music/$artist/$album/album.png" "$webDirectory/kodi/music/$artist/$album/album.png"
					fi
					set +x
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
							echo "	<img loading='lazy' src='/music/$artist/poster.png'>"
							echo "	<div class='title'>"
							echo "		$artistOG"
							echo "	</div>"
							echo "</a>"
						} > "$webDirectory/music/$artist/artist.index"
						# add artist to the main music index
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/music/music.index"
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/artists.index"
						echo "$webDirectory/music/$artist/artist.index" >> "$webDirectory/new/music.index"
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
							echo "	<img loading='lazy' src='/music/$artist/$album/album.png'>"
							echo "	<div class='title'>"
							echo "		$albumOG"
							echo "	</div>"
							echo "</a>"
						} > "$webDirectory/music/$artist/$album/album.index"
						# add album to the artist index
						echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/music/$artist/albums.index"
						echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/new/albums.index"
						echo "$webDirectory/music/$artist/$album/album.index" >> "$webDirectory/new/music.index"
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
					echo "$artist" > "$webDirectory/music/$artist/artist.cfg"
					if [ "$genre" != "" ];then
						echo "$genre" > "$webDirectory/music/$artist/genre.cfg"
					fi

					echo "$album" > "$webDirectory/music/$artist/$album/album.cfg"
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
	if test -f "/tmp/musicweb.active";then
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
