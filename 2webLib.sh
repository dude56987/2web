#! /bin/bash
########################################################################
# 2web function library
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
function resetColor(){
	# display termail code to reset all color codes
	echo -ne "\033[0m"
}
################################################################################
function randomColorText(){
	# Set the terminal output text to a random color. Excludes black and white.
	tempValue=$(( $RANDOM % 7 ))
	#
	if [ $tempValue -eq 0 ];then
		blueText
	elif [ $tempValue -eq 1 ];then
		greenText
	elif [ $tempValue -eq 2 ];then
		yellowText
	elif [ $tempValue -eq 3 ];then
		redText
	elif [ $tempValue -eq 4 ];then
		blueText
	elif [ $tempValue -eq 5 ];then
		purpleText
	elif [ $tempValue -eq 6 ];then
		cyanText
	fi
}
################################################################################
function randomColorBackground(){
	# Set the terminal output background to a random color. Excludes black and white.
	tempValue=$(( $RANDOM % 7 ))
	#
	if [ $tempValue -eq 0 ];then
		blueBackground
	elif [ $tempValue -eq 1 ];then
		greenBackground
	elif [ $tempValue -eq 2 ];then
		yellowBackground
	elif [ $tempValue -eq 3 ];then
		redBackground
	elif [ $tempValue -eq 4 ];then
		blueBackground
	elif [ $tempValue -eq 5 ];then
		purpleBackground
	elif [ $tempValue -eq 6 ];then
		cyanBackground
	fi
}
################################################################################
function blueText(){
	# make text blue
	echo -ne "\033[34m"
}
################################################################################
function greenText(){
	# make text green
	echo -ne "\033[32m"
}
################################################################################
function yellowText(){
	# make text yellow
	echo -ne "\033[33m"
}
################################################################################
function boldText(){
	# make bold text
	echo -ne "\033[1m"
}
################################################################################
function boldText(){
	# make bold text
	echo -ne "\033[5m"
}
################################################################################
function redText(){
	# make text red
	echo -ne "\033[31m"
}
################################################################################
function blackText(){
	# make text black
	echo -ne "\033[30m"
}
################################################################################
function whiteBackground(){
	# make text background white
	echo -ne "\033[47m"
}
################################################################################
function blackBackground(){
	# make text background black
	echo -ne "\033[40m"
}
################################################################################
function yellowBackground(){
	# make text background yellow
	echo -ne "\033[43m"
}
################################################################################
function redBackground(){
	# make text background red
	echo -ne "\033[41m"
}
################################################################################
function greenBackground(){
	# make text background green
	echo -ne "\033[42m"
}
################################################################################
function blueBackground(){
	# make text background blue
	echo -ne "\033[44m"
}
################################################################################
function magentaBackground(){
	# make text background magenta
	echo -ne "\033[45m"
}
################################################################################
function purpleBackground(){
	magentaBackground
}
################################################################################
function magentaText(){
	# make text magenta
	echo -ne "\033[35m"
}
################################################################################
function purpleText(){
	magentaText
}
################################################################################
function cyanBackground(){
	# make text background cyan
	echo -ne "\033[46m"
}
################################################################################
function cyanText(){
	# make text cyan
	echo -ne "\033[36m"
}
################################################################################
function whiteText(){
	echo -ne "\033[37m"
}
########################################################################
function timeToHuman(){
	# remove newlines in timestamp
	timestamp="$(echo -n "$1" | sed "s/\n//g" )"

	yearInSeconds=$(( ( (60 * 60) * 24 ) * 365 ))
	dayInSeconds=$(( ( (60 * 60) * 24 ) ))
	hourInSeconds=$(( 60 * 60 ))
	minuteInSeconds=60

	yearsPassed=0
	daysPassed=0
	hoursPassed=0
	minutesPassed=0

	if [ $timestamp -gt $yearInSeconds ];then
		yearsPassed=$(( $timestamp / $yearInSeconds ))
		timestamp=$(( $timestamp - ( $yearsPassed * $yearInSeconds ) ))
		if [ $yearsPassed == 1 ];then
			echo -n "$yearsPassed year "
		elif ($yearsPassed > 1);then
			echo -n "$yearsPassed years "
		fi
	fi

	if [ $timestamp -gt $dayInSeconds ];then
		daysPassed=$(( $timestamp / $dayInSeconds ))
		timestamp=$(( $timestamp - ( $daysPassed * $dayInSeconds ) ))
		if [ $daysPassed == 1 ];then
			echo -n "$daysPassed day "
		elif [ $daysPassed -gt 1 ];then
			echo -n "$daysPassed days "
		fi
		if [ $yearsPassed -gt 0 ];then
			return
		fi
	fi

	if [ $timestamp -gt $hourInSeconds ];then
		hoursPassed=$(( $timestamp / $hourInSeconds ))
		timestamp=$(( $timestamp - ( $hoursPassed * $hourInSeconds ) ))
		if [ $hoursPassed == 1 ];then
			echo -n "$hoursPassed hour "
		elif [ $hoursPassed -gt 1 ];then
			echo -n "$hoursPassed hours "
		fi
		if [ $daysPassed -gt 0 ];then
			return
		fi
	fi

	if [ $timestamp -gt $minuteInSeconds ];then
		minutesPassed=$(( $timestamp / $minuteInSeconds ))
		timestamp=$(( $timestamp - ( $minutesPassed * $minuteInSeconds ) ))
		if [ $minutesPassed == 1 ];then
			echo -n "$minutesPassed minute "
		elif [ $minutesPassed -gt 1 ];then
			echo -n "$minutesPassed minutes "
		fi
		if [ $hoursPassed -gt 0 ];then
			return
		fi
	fi
	# write out the remaining seconds
	if [ $timestamp == 1 ];then
		echo -n "$timestamp second "
	else
		echo -n "$timestamp seconds "
	fi
}
################################################################################
function spaceCleanedText(){
	# clean up the text for use in web urls and directory paths
	# - uses fullwidth versions of caracters that interfere with URLs
	cleanedText="$1"
	characters="． , ｀ ＃ ＇ ？ ＆ ＠ ％ － ！ ＋ ／ ＼ ｜ ； ： ＄ ＂ ＇ ＊"
	spacedText=""
	for specialCharacter in $characters;do
		cleanedText=$(echo -n "$cleanedText" | sed "s/${specialCharacter}/ ${specialCharacter} /g" )
	done
	#
	cleanedText=$(echo -n "$cleanedText" | sed 's/{/ { /g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/}/ } /g' )
	#
	cleanedText=$(echo -n "$cleanedText" | sed 's/(/ ( /g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/)/ ) /g' )
	#
	cleanedText=$(echo -n "$cleanedText" | sed 's/\[/ [ /g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/]/ ] /g' )
	# space out periods
	cleanedText=$(echo -n "$cleanedText" | sed 's/\．/ ． /g' )

	# squeeze double spaces into single spaces
	cleanedText=$(echo -n "$cleanedText" | tr -s ' ')
	# print the cleaned up text
	echo -n "$cleanedText"
}
########################################################################
function cleanText(){
	# clean up the text for use in web urls and directory paths
	# - uses fullwidth versions of caracters that interfere with URLs
	cleanedText="$1"
	# convert html entities into the characters
	#cleanedText=$(echo -n "$cleanedText" | recode html )
	cleanedText=$(echo -n "$cleanedText" | sed 's/&amp;/＆/g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/&quot;/＇/g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/&apos;/＇/g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/&lt;/</g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/&gt;/>/g' )
	# replace html spaces
	cleanedText=$(echo -n "$cleanedText" | sed 's/%20/ /g' )
	# remove grave accents
	cleanedText=$(echo -n "$cleanedText" | sed 's/`/｀/g' )
	# remove hash marks as they break URLS
	cleanedText=$(echo -n "$cleanedText" | sed "s/#/＃/g" )
	# remove single quotes
	cleanedText=$(echo -n "$cleanedText" | sed "s/'/＇/g" )
	# remove double quotes
	cleanedText=$(echo -n "$cleanedText" | sed 's/"/＂/g' )
	# convert underscores into spaces
	cleanedText=$(echo -n "$cleanedText" | sed "s/_/ /g" )
	################################################################################
	# convert symbols that cause issues to full width versions of those characters
	################################################################################
	# convert question marks into wide question marks so they look
	# the same but wide question marks do not break URLS
	cleanedText=$(echo -n "$cleanedText" | sed "s/?/？/g" )
	# cleanup ampersands, they break URLs
	cleanedText=$(echo -n "$cleanedText" | sed "s/&/＆/g" )
	# cleanup @ symbols, they break URLs
	cleanedText=$(echo -n "$cleanedText" | sed "s/@/＠/g" )
	# remove percent signs they break print functions
	cleanedText=$(echo -n "$cleanedText" | sed "s/%/％/g" )
	# hyphens break grep searches
	cleanedText=$(echo -n "$cleanedText" | sed "s/-/－/g" )
	# exclamation marks can also cause problems
	cleanedText=$(echo -n "$cleanedText" | sed "s/!/！/g" )
	# plus signs are used in URLs so they must be changed
	cleanedText=$(echo -n "$cleanedText" | sed "s/+/＋/g" )
	# remove forward slashes, they will break all paths
	cleanedText=$(echo -n "$cleanedText" | sed 's/\//／/g' )
	cleanedText=$(echo -n "$cleanedText" | sed 's/\\/＼/g' )
	# remove pipes
	cleanedText=$(echo -n "$cleanedText" | sed "s/|/｜/g" )
	# remove semicolons
	cleanedText=$(echo -n "$cleanedText" | sed "s/;/；/g" )
	#
	cleanedText=$(echo -n "$cleanedText" | sed "s/:/：/g" )
	# remove dollar signs
	cleanedText=$(echo -n "$cleanedText" | sed 's/\$/＄/g' )
	# replace periods
	cleanedText=$(echo -n "$cleanedText" | sed "s/\./．/g" )
	# repace astrisks
	cleanedText=$(echo -n "$cleanedText" | sed "s/\*/＊/g" )
	# brackets
	cleanedText="${cleanedText/\[/［}"
	cleanedText="${cleanedText/\]/］}"
	#cleanedText=$(echo -n "$cleanedText" | sed "s/\[/［/g" )
	#cleanedText=$(echo -n "$cleanedText" | sed "s/\]/］/g" )
	# curly brackets
	cleanedText="${cleanedText/\{/｛}"
	cleanedText="${cleanedText/\}/｝}"
	#cleanedText=$(echo -n "$cleanedText" | sed "s/\{//g" )
	#cleanedText=$(echo -n "$cleanedText" | sed "s/\}//g" )
	# replace any broken replacement characters
	cleanedText=$(echo -n "$cleanedText" | sed "s/�/ /g" )
	# squeeze double spaces into single spaces
	#cleanedText=$(echo -n "$cleanedText" | sed "s/  / /g" )
	cleanedText=$(echo -n "$cleanedText" | tr -s ' ')
	# print the cleaned up text
	echo -n "$cleanedText"
}
########################################################################
function delete(){
	# Remove a directory and all files in the directory showing progress
	#
	# Should replace any rm -rv
	#
	# $1 = directoryPath : The directory to create with webserver permissions
	#
	# - Create a directory with permissions for the web user www-data
	# - Create path recursively if necessary
	#
	# RETURN FILES
	if test -d "$1";then
		ALERT "Preparing to remove files in '$1'"
		# get files and symlinks
		tempDirFiles=$(find "$1" -type l -o -type f)
		# sort the dirs and reverse them so they are removed longest path to shortest
		tempDirDirs=$(find "$1" -type d | sort -u | tac )
		#
		tempDirFileCount=$(echo -n "$tempDirFiles" | wc -l)
		tempDirDirCount=$(echo -n "$tempDirDirs" | wc -l)
		#
		theTotal=$(( $tempDirFileCount + $tempDirDirCount ))
		#
		tempTotalProgress=0
		#
		progressCount=0
		#
		totalCPUS=$(cpuCount)
		ALERT "Preparing to remove '$theTotal' files in '$1'"
		# check if fast mode is enabled
		if ! [ $theTotal -gt 10000 ];then
			# read all subdirectory files and remove them
			echo "$tempDirFiles" | while read -r tempDirFilePath;do
				if test -e "$tempDirFilePath";then
					# fremove files
					rm "$tempDirFilePath" &
					progressPercent="$(echo "$( bc -l <<< "( $progressCount / $theTotal ) * 100" )" | cut -d'.' -f1 )"
					INFO "[$progressCount/$theTotal] ${progressPercent}% Removing file '$tempDirFilePath'"
					waitFastQueue 0.001 "$totalCPUS"
					progressCount=$(( $progressCount + 1 ))
				elif test -h "$tempDirFilePath";then
					# remove symlinks
					rm "$tempDirFilePath" &
					progressPercent="$(echo "$( bc -l <<< "( $progressCount / $theTotal ) * 100" )" | cut -d'.' -f1 )"
					INFO "[$progressCount/$theTotal] ${progressPercent}% Removing file '$tempDirFilePath'"
					waitFastQueue 0.001 "$totalCPUS"
					progressCount=$(( $progressCount + 1 ))
				else
					ERROR "File could not be removed at '$tempDirFilePath'"
				fi
			done
			blockQueue 1
		fi
		#ALERT "DOING THE DIRECTORIES"
		progressCount=$tempDirFileCount
		# remove any skeleton directory structure
		echo "$tempDirDirs" | while read -r tempDirDirPath;do
			# fix the trailing / in the directory path
			tempDirDirPath="$( echo "$tempDirDirPath/" | tr -s '/' )"
			if test -d "$tempDirDirPath";then
				# check if fast mode is enabled
				if [ $theTotal -gt 10000 ];then
					rm -r "$tempDirDirPath" &
				else
					rmdir "$tempDirDirPath" &
				fi
				# calculate the percent and remove decimals
				progressPercent="$(echo "$( bc -l <<< "( $progressCount / $theTotal ) * 100" )" | cut -d'.' -f1 )"
				INFO "[$progressCount/$theTotal] ${progressPercent}% Removing directory '$tempDirDirPath'"
				waitFastQueue 0.001 "$totalCPUS"
				progressCount=$(( $progressCount + 1 ))
			else
				ERROR "Path could not be removed at $( echo -e "${tempDirDirPath}\n" )$( tree "$tempDirDirPath" )"
			fi
		done
		blockQueue 1
		# return codes
		if test -d "$1";then
			ERROR "Removal of directory '$1' Failed!"
			return 1
		else
			ALERT "Removal of directory '$1' complete !"
			return 0
		fi
	elif test -f "$1";then
		# this is a single file path, remove the file
		rm -v "$1"
		if ! test -f "$1";then
			ALERT "Removal of file '$1' complete !"
			return 0
		else
			ERROR "Removal of file '$1' Failed!"
			return 1
		fi
	elif test -s "$1";then
		# this is a symlink remove it
		rm -v "$1"
		if ! test -s "$1";then
			ALERT "Removal of symlink '$1' complete !"
			return 0
		else
			ERROR "Removal of symlink '$1' Failed!"
			return 1
		fi
		return "$?"
	else
		ALERT "Skipping path '$1' it is already removed!"
		return 0
	fi
}
########################################################################
function createDir(){
	# Create a path for use in the webserver
	#
	# $1 = directoryPath : The directory to create with webserver permissions
	#
	# - Create a directory with permissions for the web user www-data
	# - Create path recursively if necessary
	#
	# RETURN FILES
	if ! test -d "$1";then
		mkdir -p "$1"
		# set ownership of directory and subdirectories as www-data
		chown -R www-data:www-data "$1"
	fi
	chown www-data:www-data "$1"
}
########################################################################
function readPathConfig(){
	# Read a single path stored in a text file
	#
	# $1 = pathToConfig : The file where the path configuration file is stored
	# $2 = defaultPath : The default location for this path config file to set if no path is set
	#
	# RETURN STDOUT
	pathToConfig=$1
	defaultPath=$2
	# check for the config path
	if test -f "$pathToConfig";then
		foundPath=$(cat "$pathToConfig")
		buildDefault="no"
	else
		buildDefault="yes"
	fi
	# if a blank file is found
	if [ "${#foundPath}" -eq 0 ];then
		# this is catastrophic so overwrite it with the default path
		buildDefault="yes"
	fi
	# if the default does not exist create it and set the config
	if echo "$buildDefault" | grep -q "yes";then
		mkdir -p "$defaultPath"
		chown -R www-data:www-data "$defaultPath"
		echo "$defaultPath" > "$pathToConfig"
		foundPath="$defaultPath"
	fi
	# check for a trailing slash appended to the path
	if [ "$(echo "$foundPath" | rev | cut -b 1)" == "/" ];then
		# rip the last byte off the string and return the correct path, WITHOUT THE TRAILING SLASH
		foundPath="$(echo "$foundPath" | rev | cut -b 2- | rev )"
	fi
	# output the found path
	echo "$foundPath"
}
########################################################################
function realWebRoot(){
	# read config for the full path used in the symlink to the web root
	#
	# RETURN STDOUT
	readPathConfig "/etc/2web/web.cfg" "/var/cache/2web/web_cache/"
}
########################################################################
function webRoot(){
	# read config for webserver root directory
	#
	# - This is a dummy function that returns the symlink to the web root
	# - For the full path the symlink is set to use realWebRoot()
	#
	# RETURN STDOUT
	echo "/var/cache/2web/web"
}
########################################################################
function generatedRoot(){
	# Read config for the directory where generated content is stored
	#
	# RETURN STDOUT
	readPathConfig "/etc/2web/generated.cfg" "/var/cache/2web/generated_cache/"
}
########################################################################
function downloadRoot(){
	# Read config for the location where all downloaded content is stored
	#
	# - This does not include the thumbnails downloaded
	#
	# RETURN STDOUT
	readPathConfig "/etc/2web/download.cfg" "/var/cache/2web/downloads_cache/"
}
########################################################################
function kodiRoot(){
	# Read config for the kodi root path, this is where symlinks to all media
	# will be stored.
	#
	# RETURN STDOUT
	readPathConfig "/etc/2web/kodi.cfg" "/var/cache/2web/generated/kodi_cache/"
}
########################################################################
function checkFileDataSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	filePath=$2
	# module name
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)

	# check the sum of a file and compare it to a previously stored sum
	createDir "$webDirectory/sums/"
	pathSum="$(echo "$filePath" | sha512sum | cut -d' ' -f1 )"

	# generate the new sum for the file
	newSum="$(cat "$filePath" | sha512sum | cut -d' ' -f1 )"

	# check for a previous sum
	if test -f "$webDirectory/sums/${moduleName}_$pathSum.cfg";then
		# load the old sum
		oldSum="$(cat "$webDirectory/sums/${moduleName}_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			#ALERT "Sum is UNCHANGED"
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			# return false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			#echo "$newSum" > "$webDirectory/sums/${moduleName}_$pathSum.cfg"
			# return true
			return 0
		fi
	else
		# CHANGED, new file
		# no previous file was found, pass true
		# update the sum
		#echo "$newSum" > "$webDirectory/sums/${moduleName}_$pathSum.cfg"
		# return true
		return 0
	fi
}
########################################################################
function setFileDataSum(){
	# Set the data sum of a file in the sums directory, for use with checkFileDataSum()
	# This allows you to check the sum then run your code to update whatever needs changed.
  # Then this function locks in those changes.
	#
	# $1 = webDirectory : The base root of the webserver returned by webRoot()
	# $2 = filePath : The path to the file that was checked.
	#
	# RETURN NULL, FILES
	webDirectory=$1
	filePath=$2
	# module name
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	# build sums
	pathSum="$(echo "$filePath" | sha512sum | cut -d' ' -f1 )"
	newSum="$(cat "$filePath" | sha512sum | cut -d' ' -f1 )"

	echo "$newSum" > "$webDirectory/sums/${moduleName}_$pathSum.cfg"
}
########################################################################
function buildHomePage(){
	# Update all the statistics for the 2web homepage
	#
	# $1 = webDirectory : the base path of the web directory returned by webRoot()
	#
	# RETURN NULL, FILES
	webDirectory=$1

	INFO "Building home page..."
	# link homepage
	linkFile "/usr/share/2web/templates/home.php" "$webDirectory/index.php"

	# check and update stats files
	# - do not generate stats if website is in process of being updated
	# - stats generation is IO intense, so it only needs ran ONCE at the end
	# - each element in the stats is ran on a diffrent schedule based on its intensity and propensity to lock up the system
	# - update the "last update on" data every run, this is simply to show the freshness of the content since updates are a batch process
	# set the timeout
	timeout=60000
	databasePath="$(webRoot)/data.db"
	INFO "Building stats for total comic count"
	if test -d "$webDirectory/comics/";then
		if cacheCheck "$webDirectory/totalComics.index" "1";then
			# get the count stats from SQL database
			totalComics=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_comics\";")
			echo "$totalComics" > "$webDirectory/totalComics.index"
		fi
	fi
	INFO "Building stats for total music count"
	if test -d "$webDirectory/music";then
		INFO "Building stats for total track count"
		if cacheCheck "$webDirectory/totalTracks.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_tracks\";")
			echo "$totalEpisodes" > "$webDirectory/totalTracks.index"
		fi
		INFO "Building stats for total artist count"
		if cacheCheck "$webDirectory/totalArtists.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_artists\";")
			echo "$totalEpisodes" > "$webDirectory/totalArtists.index"
		fi
		INFO "Building stats for total album count"
		if cacheCheck "$webDirectory/totalAlbums.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_albums\";")
			echo "$totalEpisodes" > "$webDirectory/totalAlbums.index"
		fi
	fi
	INFO "Building stats for show metadata"
	if test -d "$webDirectory/shows/";then
		INFO "Building stats for total episode count"
		if cacheCheck "$webDirectory/totalEpisodes.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_episodes\";")
			echo "$totalEpisodes" > "$webDirectory/totalEpisodes.index"
		fi
		INFO "Building stats for total show count"
		if cacheCheck "$webDirectory/totalShows.index" "1";then
			totalShows=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_shows\";")
			echo "$totalShows" > "$webDirectory/totalShows.index"
		fi
	fi
	INFO "Building stats for total movie count"
	if cacheCheck "$webDirectory/totalMovies.index" "1";then
		totalMovies=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_movies\";")
		echo "$totalMovies" > "$webDirectory/totalMovies.index"
	fi
	INFO "Building stats for total wiki count"
	if cacheCheck "$webDirectory/totalWikis.index" "1";then
		totalWikis=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_wikis\";")
		echo "$totalWikis" > "$webDirectory/totalWikis.index"
	fi
	INFO "Building stats for total repo count"
	if cacheCheck "$webDirectory/totalRepos.index" "1";then
		totalRepos=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_repos\";")
		echo "$totalRepos" > "$webDirectory/totalRepos.index"
	fi
	INFO "Building stats for total application count"
	if cacheCheck "$webDirectory/totalApps.index" "1";then
		totalApps=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_applications\";")
		echo "$totalApps" > "$webDirectory/totalApps.index"
	fi
	INFO "Building stats for total weather station count"
	if cacheCheck "$webDirectory/totalWeather.index" "1";then
		totalWeather=$(find "$webDirectory/weather/data/" -name "station_*.index" | wc -l)
		echo "$totalWeather" > "$webDirectory/totalWeather.index"
	fi
	#
	INFO "Building stats for total wiki count"
	if cacheCheck "$webDirectory/totalWiki.index" "1";then
		totalWeather=$(cat "$webDirectory/wiki/wikis.index" | wc -l)
		echo "$totalWeather" > "$webDirectory/totalWiki.index"
	fi
	INFO "Building stats for channel metadata"
	if test -f "$webDirectory/live/channels.m3u";then
		INFO "Building stats for total channel count"
		if cacheCheck "$webDirectory/totalChannels.index" "7";then
			totalChannels=$(grep -c 'radio="false' "$webDirectory/kodi/channels.m3u" )
			echo "$totalChannels" > "$webDirectory/totalChannels.index"
		fi
		INFO "Building stats for total radio station count"
		if cacheCheck "$webDirectory/totalRadio.index" "7";then
			totalRadio=$(grep -c 'radio="true' "$webDirectory/kodi/channels.m3u" )
			echo "$totalRadio" > "$webDirectory/totalRadio.index"
		fi
	fi
	INFO "Building stats for generated website size"
	# Run filesystem size checks for stats
	if cacheCheck "$webDirectory/webSize.index" "1";then
		# count website size in total ignoring symlinks
		webSize=$(du -shP "$webDirectory" | cut -f1)
		echo "$webSize" > "$webDirectory/webSize.index"
	fi
	INFO "Building stat for generated website thumbnail size"
	if cacheCheck "$webDirectory/webThumbSize.index" "1";then
		# count website thumbnail size in total ignoring symlinks
		webThumbSize=$(du -shP "$webDirectory/thumbnails/" | cut -f1)
		echo "$webThumbSize" > "$webDirectory/webThumbSize.index"
	fi
	INFO "Building stat for generated website resolver cache size"
	if cacheCheck "$webDirectory/cacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/RESOLVER-CACHE/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/cacheSize.index"
	fi
	INFO "Building stat for generated website transcode cache size"
	if cacheCheck "$webDirectory/transcodeCacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/TRANSCODE-CACHE/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/transcodeCacheSize.index"
	fi
	INFO "Building stat for generated website m3u cache size"
	if cacheCheck "$webDirectory/m3uCacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/m3u_cache/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/m3uCacheSize.index"
	fi
	INFO "Building stat for generated website search cache size"
	if cacheCheck "$webDirectory/searchCacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/search/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/searchCacheSize.index"
	fi
	INFO "Building stat for generated website zip cache size"
	if cacheCheck "$webDirectory/zipCacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/zip/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/zipCacheSize.index"
	fi
	INFO "Building stat for generated website page cache size"
	if cacheCheck "$webDirectory/webCacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/web_cache/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/webCacheSize.index"
	fi
	INFO "Building stat for generated repo cache size"
	if cacheCheck "$webDirectory/repoGenSize.index" "1";then
		repoGenSize=$(du -shP "$webDirectory/repos/" | cut -f1)
		echo "$repoGenSize" > "$webDirectory/repoGenSize.index"
	fi
	INFO "Building stat for size of all media files hosted on the server"
	if cacheCheck "$webDirectory/mediaSize.index" "1";then
		# count symlinks in kodi to get the total size of all media on all connected drives containing libs
		mediaSize=$(du -shL "$webDirectory/kodi/" | cut -f1)
		echo "$mediaSize" > "$webDirectory/mediaSize.index"
	fi
	INFO "Building stat for the free space left on the system"
	if cacheCheck "$webDirectory/freeSpace.index" "1";then
		# count total freespace on all connected drives, ignore temp filesystems (snap packs)
		freeSpace=$(df -h -x "tmpfs" --total | grep "total" | tr -s ' ' | cut -d' ' -f4)
		echo "$freeSpace" > "$webDirectory/freeSpace.index"
	fi
	INFO "Building stat for the free space left each drive on the system"
	if cacheCheck "$webDirectory/drives.index" "1";then
		# count total freespace on all connected drives, ignore temp filesystems (snap packs)
		#driveList=$(df -h -x "tmpfs" --total | grep "^/dev/sd" | tr -s ' ' )
		driveList=$(df -h -x "tmpfs" --total | grep "^/dev/" | tr -s ' ' )
		#driveList=$(df -h -x "tmpfs" --total | tr -s ' ' )
		#
		echo -n "" > "$webDirectory/drives.index"
		IFSBACKUP=$IFS
		IFS=$'\n'
		for driveData in $driveList;do
			#
			tempFreeSpace=$(echo -n "$driveData" | cut -d' ' -f4 )
			#
			tempFreeSpaceLabel=$(echo -n "$driveData" | cut -d' ' -f6 )
			#
			if [ $tempFreeSpaceLabel == "/" ];then
				tempFreeSpaceLabel="ROOT"
			else
				tempFreeSpaceLabel=$(basename "$driveData" )
			fi
			{
				echo "<span class='singleStat'>"
				echo "	<span class='singleStatLabel'>"
				echo "		$tempFreeSpaceLabel Free Space"
				echo "	</span>"
				echo "	<span class='singleStatValue'>"
				echo "		$tempFreeSpace"
				echo "	</span>"
				echo "</span>"
			} >> "$webDirectory/drives.index"
		done
		IFS=$IFSBACKUP
	fi

	INFO "Building stat for the disk space used by downloaded AI models"
	if cacheCheck "$webDirectory/aiSize.index" "6";then
		# count total size of AI models
		aiSize=$(du -shL "/var/cache/2web/downloads/ai/" | cut -f1)
		echo "$aiSize" > "$webDirectory/aiSize.index"
	fi
	INFO "Building stat for the disk space used by downloaded AI prompt models"
	if cacheCheck "$webDirectory/promptAi.index" "1";then
		# the number of prompt ais that are installed on the system
		promptAi=$(find "/var/cache/2web/downloads/ai/prompt/" -name "*.bin" | wc -l)
		echo "$promptAi" > "$webDirectory/promptAi.index"
	fi
	INFO "Building stat for the disk space used by downloaded AI image models"
	if cacheCheck "$webDirectory/imageAi.index" "1";then
		imageAi=$(find "/var/cache/2web/downloads/ai/txt2img/" -maxdepth 1 -type d -name "models--*" | wc -l)
		echo "$imageAi" > "$webDirectory/imageAi.index"
	fi
	INFO "Building stat for the disk space used by downloaded AI text models"
	if cacheCheck "$webDirectory/txtGenAi.index" "1";then
		txtGenAi=$(find "/var/cache/2web/downloads/ai/txt2txt/" -maxdepth 1 -type d -name "models--*" | wc -l)
		echo "$txtGenAi" > "$webDirectory/txtGenAi.index"
	fi
	INFO "Building stat for the disk space used by downloaded AI image editing models"
	if cacheCheck "$webDirectory/imageEditAi.index" "1";then
		imageEditAi=$(find "/var/cache/2web/downloads/ai/img2img/" -maxdepth 1 -type d -name "models--*" | wc -l)
		echo "$imageEditAi" > "$webDirectory/imageEditAi.index"
	fi
	INFO "Building stat for the disk space used by downloaded AI subtitling models"
	if cacheCheck "$webDirectory/subAi.index" "1";then
		subAi=$(find "/var/cache/2web/downloads/ai/subtitles/" -type f -name "*.pt" | wc -l)
		echo "$subAi" > "$webDirectory/subAi.index"
	fi
	INFO "Building stat for the disk space used by downloaded show metadata"
	if cacheCheck "$webDirectory/ytdlShows.index" "1";then
		ytdlShows=$(find "/var/cache/2web/downloads/nfo/" -maxdepth 2 -type f -name "tvshow.nfo" | wc -l)
		echo "$ytdlShows" > "$webDirectory/ytdlShows.index"
	fi
	INFO "Building stat for the disk space used by all AI models"
	if cacheCheck "$webDirectory/localAi.index" "1";then
		# the total number of AIs
		localAi=0
		localAi=$(( $localAi + $(cat "$webDirectory/subAi.index") ))
		localAi=$(( $localAi + $(cat "$webDirectory/imageAi.index") ))
		localAi=$(( $localAi + $(cat "$webDirectory/imageEditAi.index") ))
		localAi=$(( $localAi + $(cat "$webDirectory/promptAi.index") ))
		echo "$localAi" > "$webDirectory/localAi.index"
	fi
}
########################################################################
function cacheCheck(){
	# Check if a file is more than x days old and needs updated.
	#
	# $1 = filePath : Path to the cached file to check for.
	# $2 = cacheDays : The max age in days of the file
	#
	# - Will return true if the files does not exist or the file is older than $cacheDays
	# - This should activate code that changes the file
	#
	# RETURN BOOL

	filePath="$1"
	cacheDays="$2"

	# return true if cached needs updated
	if test -f "$filePath";then
		# check the file date
		fileMtime=$(stat -c "%Y" "$filePath")

		#addToLog "DEBUG" "CACHE CHECK" "filePath = '$filePath'"
		#addToLog "DEBUG" "CACHE CHECK" "fileMtime = '$fileMtime'"
		#addToLog "DEBUG" "CACHE CHECK" "$(timeToHuman "$(($(date "+%s") - $fileMtime))") -gt $(timeToHuman "$(( ( (60 * 60) * 24 ) * $cacheDays ))")"
		if [ $(($(date "+%s") - $fileMtime)) -gt $(( ( (60 * 60) * 24 ) * $cacheDays )) ];then
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
function cacheCheckMin(){
	# Check if a file is more than x minutes old and needs updated.
	#
	# $1 = filePath : Path to the cached file to check for.
	# $2 = cacheMinutes : The max age in minutes of the file
	#
	# - Will return true if the files does not exist or the file is older than $cacheMinutes
	# - This should activate code that changes the file
	#
	# RETURN BOOL

	filePath="$1"
	cacheMinutes="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		fileMtime=$(stat -c "%Y" "$filePath")
		if [ $(($(date "+%s") - $fileMtime)) -gt $(( 60 * $cacheMinutes )) ];then
			# the file is more than "$2" minutes old, it needs updated
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
function linkFile(){
	# Create a link if it does not yet exist
	#
	# $1 = target
	# $2 = symlinkPath
	#
	# RETURN FILES
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
}
########################################################################
function updateCerts(){
	# Update the SSL certificates if they are more than 365 days old
	#
	# $1 = force : Force generating new certificates, ignore age check
	#
	# RETURN NULL, FILES
	force=$1
	genCert='no'
	if ! test -f "/var/cache/2web/ssl-cert.crt";then
		# the cert does not yet exist create a default one
		INFO "Updating SSL cert..."
		# generate a new private key and public cert for the SSL certification
		genCert='yes'
	else
		INFO "SSL cert Already exists at '/var/cache/2web/ssl-cert.crt'..."
		return
	fi
	if $force;then
		# force default cert generation, will overwite orignal
		genCert='yes'
	fi
	if [ $genCert == 'yes' ];then
		addToLog "UPDATE" "Updating custom SSL certificate"
		# build the custom cert and private key
		openssl req -x509 -newkey rsa:4096 -keyout /var/cache/2web/ssl-private.key -out /var/cache/2web/ssl-cert.crt -sha256 -days 3650 -nodes -subj "/C=XX/ST=Networked/L=Internetville/O=2web/OU=$(hostname)@2web/CN=$(hostname).local"
		# add the cert to the system cert directory
		ln -s /var/cache/2web/ssl-cert.crt /usr/share/ca-certificates/2web.crt
		# update system cert file
		update-ca-certificates --fresh
	fi
}
########################################################################
function alterArticles(){
	# Modify a text string so that articles are at the end of the title
	#
	# $1 = title : The title to alter the articles of
	#
	# - Articles are
	#  - A
	#  - An
	#  - The
	# - Leading Intergers are also treated as articles
	#
	# ex)
	#    "The Big Man" becomes "Big Man, The"
	# ex)
	#    "A Big Adventure" becomes "Big Adventure, A"
	#
	# RETURN STDOUT
	pageComicName=$1
	# alter the title to make articles(a, an, the) sort correctly
	if echo "$pageComicName" | grep --ignore-case -q "^the ";then
		pageComicName=$(echo "$pageComicName" | sed "s/^[T,t][H,h][E,e] //g")
		pageComicName="$pageComicName, The"
	fi
	if echo "$pageComicName" | grep --ignore-case -q "^a ";then
		pageComicName=$(echo "$pageComicName" | sed "s/^[A,a] //g")
		pageComicName="$pageComicName, A"
	fi
	if echo "$pageComicName" | grep --ignore-case -q "^an ";then
		pageComicName=$(echo "$pageComicName" | sed "s/^[A,a][N,n] //g")
		pageComicName="$pageComicName, An"
	fi
	# check for id #s
	if echo "$pageComicName" | grep --ignore-case -q "^[0-9]\{4,\} ";then
		comicIdNumber=$(echo "$pageComicName" | cut -d' ' -f1)
		pageComicName=$(echo "$pageComicName" | sed "s/^[0-9]\{4,\} //g")
		pageComicName="$pageComicName, $comicIdNumber"
	fi
	echo "$pageComicName"
}
################################################################################
function cpuCount(){
	# PARALLEL PROCESSING COMMAND
	# List the total number of cores the system has available for multithreading
	#
	# RETURN STDOUT

	#
	totalCPUS=$(grep "processor" "/proc/cpuinfo" | wc -l)
	# remove one cpu from the total cpu count, this will prevent service interuptions
	totalCPUS=$(( $totalCPUS - 1 ))
	if [ $totalCPUS -lt 2 ];then
		totalCPUS=1
	fi
	# output the cpu count
	echo $totalCPUS
}
################################################################################
function waitQueue(){
	# PARALLEL PROCESSING COMMAND
	# Wait for queue to free up for next command
	#
	# $1 = sleepTime : The amount of seconds to wait between queue size checks
	# $2 = totalCPUS : The number of cpus the system has from cpuCount()
	#
	# - Uses system load and free cores to manage queue
	#
	# RETURN NULL
	sleepTime=$1
	totalCPUS=$2
	#
	if [ $totalCPUS == "" ];then
		# failsafe to one CPU if a total is not given
		totalCPUS=1
	fi
	#
	if [ $totalCPUS -eq 1 ];then
		# if there is only one cpu use the fastqueue, one at a time, ignore system load
		waitFastQueue $sleepTime 1
		return 0
	fi
	firstRun="yes"
	while true;do
		# check if the cpu has any available cores that are unused in this process
		# jobs -r only shows running jobs
		if [ $(jobs -r | wc -l) -ge $totalCPUS ];then
			#INFO "Waiting for free CPU cores..."
			#ALERT "WAITING IN QUEUE"
			if [ $firstRun == "yes" ];then
				firstRun="no"
			else
				sleepSpinner
			fi
			#sleep $sleepTime
		else
			# if the load exceeds the number of cpus block the queue, this means the system is maxed out globally
			# - this will make all 2web modules parallel process without blocking each other
			# - this should make the apache server remain available even if all modules are running in parallel
			# convert load into interger and compare
			if [ $(cat /proc/loadavg | cut -d' ' -f1 | cut -d'.' -f1) -gt $(( totalCPUS )) ];then
				INFO "System is overloaded, Waiting for system resources..."
				#ALERT "WAITING IN QUEUE for reduced system load"
				#sleep 1
				if [ $firstRun == "yes" ];then
					# skip drawing the spinner on the first loop for fast finishing queue items
					firstRun="no"
				else
					sleepSpinner
				fi
			else
				break
			fi
		fi

	done
}
################################################################################
function waitSlowQueue(){
	# PARALLEL PROCESSING COMMAND
	# Wait for system load to get below the totalCpus
	#
	# $1 = sleepTime : The amount of seconds to wait between queue size checks
	# $2 = totalCPUS : The number of cpus the system has from cpuCount()
	#
	# - Use only the system load to manage queue
	# - This is dangerous and will heavily tax the system in a uneven way
	#
	# RETURN NULL
	sleepTime=$1
	totalCPUS=$2
	firstRun="yes"
	while true;do
		if [ $(cat /proc/loadavg | cut -d' ' -f1 | cut -d'.' -f1) -gt $(( totalCPUS )) ];then
			INFO "System is overloaded, Waiting for system resources..."
			if [ $firstRun == "yes" ];then
				firstRun="no"
			else
				sleepSpinner
			fi
			#sleep 1
		else
			break
		fi
	done
}
################################################################################
function waitFastQueue(){
	# PARALLEL PROCESSING COMMAND
	# Wait for queue to free up for next command
	#
	# $1 = sleepTime : The amount of seconds to wait between queue size checks
	# $2 = totalCPUS : The number of cpus the system has from cpuCount()
	#
	# - Use only the free cpu cores to manage the queue
	#
	# RETURN NULL
	sleepTime=$1
	totalCPUS=$2
	firstRun="yes"
	while true;do
		# check if the cpu has any available cores that are unused in this process
		# jobs -r only shows running jobs
		if [ $(jobs -r | wc -l) -ge $totalCPUS ];then
			#INFO "Waiting for free CPU cores..."
			#ALERT "Waiting Fast Queue"
			#sleep $sleepTime
			if [ "$firstRun" == "yes" ];then
				firstRun="no"
			else
				sleepSpinner
			fi
		else
			# if the load exceeds the number of cpus block the queue, this means the
			# system is maxed out globally
			# - this will make all 2web modules parallel process without blocking each
			#   other
			# - this should make the apache server remain available even if all
			#   modules are running in parallel
			# convert load into interger and compare
			break
		fi
	done
}
################################################################################
function queueIsActive(){
	# PARALLEL PROCESSING COMMAND
	# This is to be used in the head of a while loop to run a queue while the jobs
	# are actively processing.
	#
	# - Return true if the queue is still processing jobs
	#
	# RETURN NULL
	if [ $(jobs -r | wc -l) -gt 0 ];then
		# the queue is still active
		return 0
	else
		# the queue is empty
		return 1
	fi
}
################################################################################
function blockQueue(){
	# PARALLEL PROCESSING COMMAND
	# Wait for all jobs in queue to finish
	#
	# - Place this after waitQueue() commands to make all jobs finish before
	#   processing can continue
	#
	# RETURN NULL
	sleepTime=$1
	# set the number of processes to unlock the queue at
	unlockAt=0
	#
	firstRun="yes"
	while true;do
		if [ $(jobs -r | wc -l) -gt $unlockAt ];then
			if [ $firstRun == "yes" ];then
				firstRun="no"
			else
				sleepSpinner
			fi
		else
			break
		fi
	done
}
################################################################################
function ALERT(){
	# Write output and move down to the next line. Keeps text on screen when using INFO()
	#
	# RETURN STDOUT
	#

	#colorCodeLength="${#colorCode}"

	#colorCode="\033[33m"
	#resetCode="\033[0m"

	resetCode="$(resetColor)"
	colorCode="$(yellowText)"

	#
	width=$(tput cols)
	height=$(tput lines)
	buffer=" "
	# get the header text
	headerText="$2"
	# set the default if empty
	if [ "$headerText" == "" ];then
		headerText="ALERT"
	fi
	#
	headerTextWidth=$( echo -n "$headerText" | wc -c )

	# make the buffer the width of the terminal
	for index in $(seq $width);do
		buffer="$buffer "
	done
	#
	if [ $( echo -ne "$1" | wc -l ) -gt 0 ];then
		#
		yellowText
			drawSmallHeader "$headerText"
		resetColor
		whiteBackground
		blackText
			echo -e "$1" | tr -s '\n'
		resetColor
		yellowText
			drawLine
		resetColor
	else
		# get the length of the color codes
		resetCodeLength="${#resetCode}"
		colorCodeLength="${#colorCode}"
		#resetCodeLength="$(echo -ne "${resetCode} | wc -c)"
		#colorCodeLength="$(echo -ne "${colorCode} | wc -c)"
		# - cut the line to make it fit on one line using ncurses tput command
		# - add the buffer to the end of the line and cut to terminal width
		#   - this will overwrite any previous text wrote to the line
		#   - cut one off the width in order to make space for the \r
		# - The ((width-1)+7+11)  equation refers to the characters in the color codes used and the one creates room for the next opcode
		#output="$(echo -ne "[${yellowCode}ALERT${resetCode}]: $1$buffer" | tail -n 1 | cut -b"1-$(( ( $width -  1 ) + 7 + 11 ))" )"
		#output="$(echo -n "[${colorCode}$headerText${resetCode}]: $1$buffer" | tail -n 1 | cut -b"1-$(( ( $width ) + 7 + 11 ))" )"
		#output="$(echo -n "[${colorCode}$headerText${resetCode}]: $1$buffer" | tail -n 1 | cut -b"1-$(( ( $width ) + $colorCodeLength + $resetCodeLength ))" )"
		fullTermWidth="$(( ( ( $width ) + $colorCodeLength + $resetCodeLength ) ))"
		#fullTermWidth="$(( ( ( $width ) + 7 + 11 ) ))"
		output="$(echo -n "[${colorCode}$headerText${resetCode}]: $1$buffer" | tail -n 1 | cut -b"1-${fullTermWidth}" )"
		# move to the start of the bottom line
		tput cup "$height" "0"
		echo -e "$output"
	fi
	# reset the cursor position
	tput cup "$height" "0"
}
################################################################################
function startDebug(){
	# Draw a debug header in the output and enable debug mode in BASH
	#
	# - All commands executed will be displayed after this command is ran
	#
	# RETURN NULL
	drawLine
	drawHeader "START DEBUG BLOCK"
	drawLine
	set -x
}
################################################################################
function stopDebug(){
	# Stop debugging process started with startDebug(). This will reset BASH to its default
	# non-debug state where commands are executed without printing to STDOUT.
	#
	# RETURN NULL
	set +x
	drawLine
	drawHeader "STOP DEBUG BLOCK"
	drawLine
}
########################################################################
function drawCellLine(){
	# draw a line above or below a cell
	colums=$1

	totalWidth=$(( $(tput cols) - 0 ))
	width=$(( ( $totalWidth / $colums ) - 1 ))

	buffer=""
	# make the buffer
	for index in $(seq $width);do
		buffer="${buffer}━"
		#buffer="${buffer}█"
	done
	# cut the output to cell size
	#output="$(echo -ne "$buffer" | cut -b1-$width)"
	#output="$(printf "$buffer" | cut -b1-$width)"
	#
	#echo -ne " ╋ "
	printf "╋"
	#printf "█"
	#
	for index in $(seq $colums);do
		#echo -ne "${output} ╋ "
		printf "${buffer}╋"
		#printf "${buffer}█"
	done
	echo
	return 0
}
########################################################################
function drawCell(){
	# drawCell $text $colums
	#
	# Draw a single cell of a table
	#
	# - The number of colums determines the width of the cells
	text="$1"
	colums=$2
	# divide the total width by the number of collums in this table
	totalWidth=$(( $(tput cols) - 0 ))
	width=$(( ( $totalWidth / $colums ) - 1 ))
	#
	buffer=""
	# make the buffer the width of the terminal
	for index in $(seq $width);do
		buffer="$buffer "
	done
	# cut the output to cell size
	output="$(echo -n "$text$buffer" | cut -b1-$width)"
	#echo -ne "$output | "
	echo -ne "$output┃"
	#echo -n "$output | "
	#echo -ne "$output█"
	return 0
}
################################################################################
function startCellRow(){
	# start a new cell row
	#echo -n " | "
	echo -n "┃"
	#echo -n "█"
}
################################################################################
function endCellRow(){
	# end a existing cell row
	echo
}
################################################################################
function highlightCell(){
	# drawCell $text $colums
	#
	# Draw a single cell of a table
	#
	# - The number of colums determines the width of the cells
	text="$1"
	colums=$2
	# divide the total width by the number of collums in this table
	totalWidth=$(( $(tput cols) - 0 ))
	width=$(( ( $totalWidth / $colums ) - 1 ))
	#
	buffer=""
	# make the buffer the width of the terminal
	for index in $(seq $width);do
		buffer="$buffer "
	done
	# cut the output to cell size
	output="$(echo -n "$text$buffer" | cut -b1-$width)"
	echo -en "$highlightColor"
	whiteBackground
	blackText
	echo -n "$output"
	resetColor
	echo -n "┃"
	return 0
}
################################################################################
function INFO(){
	# Output text on a single line and overwite that text with the next INFO() output.
	#
	# - This will prevent the terminal from scrolling down when outputing text.
	# - Line length will be cut to the size of the active terminal
	#
	# RETURN STDOUT

	#
	height=$(tput lines)
	width=$(tput cols)
	#
	buffer=""
	# make the buffer the width of the terminal
	for index in $(seq $width);do
		buffer="$buffer "
	done
	# store the color codes for coloring
	#resetCode="$(resetColor)"
	#blueCode="$(blueText)"
	blueCode="\033[34m"
	resetCode="\033[0m"
	# - cut the line to make it fit on one line using ncurses tput command
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	# - The ((width-3)+7+11)  equation refers to the characters in the color codes used and the three creates room for the next opcode and the spinner
	output="$(echo -n "[${blueCode}INFO${resetCode}]: $1$buffer" | tail -n 1 | cut -b"1-$(( ( $width - 3 ) + 7 + 11 ))" )"
	# place the cursor at the start of the bottom line
	tput cup "$height" "0"
	# write the output text without a newline
	# - add 2 blank spaces to the end of the line for the spinner
	#echo -ne "$output   "
	#echo -ne "$output  \r"
	#echo -ne "$output\r"
	echo -ne "$output\r"
}
################################################################################
function ERROR(){
	# Print text as error output. This will have a ERROR header.
	#
	# RETURN STDOUT
	width=$(tput cols)
	output="$1"
	echo
	# draw the error header
	redText
		drawLine
		drawHeader "!!! ERROR !!!"
		drawLine
	resetColor
	# draw the error output text
	yellowBackground
	blackText
		echo -e "$output"
	resetColor
	redText
		drawLine
	resetColor
}
################################################################################
function upgrade-pip(){
	# list the package names in a space seprated list
	moduleName="$1"
	if [ "$moduleName" == "" ];then
		moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	fi
	packageNames="$2"
	# download package source code into the download path
	pipDownloadPath="/var/cache/2web/downloads/pip"
	# update downloaded packages
	# - downloads are not throttled but installs are in loop below
	for packageName in $packageNames;do
		# create the pip install and download cache
		createDir "$pipDownloadPath/$packageName/"
		# if the mod is enabled install the package
		pip3 download "$packageName" --destination-directory "$pipDownloadPath/$packageName/"
	done
	# build and install packages into generated path
	pipInstallPath="/var/cache/2web/generated/pip"
	# install packages if the mod is enabled
	if returnModStatus "$moduleName";then
		# build and install the packages
		for packageName in $packageNames;do
			# only allow upgrades every 7 days
			if cacheCheck "/var/cache/2web/generated/pip/${moduleName}_${packageName}_timestamp.cfg" "7";then
				# create the install path for this package
				createDir "$pipInstallPath/$packageName/"
				# if the mod is disabled only download the package into the cache
				# - This makes packages available to install even if no network connection is available
				pip3 install "$packageName" --no-index --find-links "$pipDownloadPath/$packageName/" --target "$pipInstallPath/$packageName/" --upgrade --upgrade-strategy=only-if-needed
				# update the timestamp of the package upgrade
				touch "/var/cache/2web/generated/pip/${moduleName}_${packageName}_timestamp.cfg"
			fi
		done
	fi
}
################################################################################
function upgrade-single-pip(){
	# list the package names in a space seprated list
	moduleName="$1"
	if [ "$moduleName" == "" ];then
		moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	fi
	packageName="$2"
	installPath="$3"
	# download package source code into the download path
	pipDownloadPath="/var/cache/2web/downloads/pip"
	#
	# create the pip install and download cache
	createDir "$pipDownloadPath/$installPath/"
	# if the mod is enabled install the package
	pip3 download "$packageName" --destination-directory "$pipDownloadPath/$installPath/"
	# build and install packages into generated path
	pipInstallPath="/var/cache/2web/generated/pip"
	# install packages if the mod is enabled
	if returnModStatus "$moduleName";then
		# limit upgrades to once every 7 days
		if cacheCheck "/var/cache/2web/generated/pip/${moduleName}_${packageName}_timestamp.cfg" "7";then
			# create the install path for this package
			createDir "$pipInstallPath/$installPath/"
			# if the mod is disabled only download the package into the cache
			# - This makes packages available to install even if no network connection is available
			pip3 install "$packageName" --no-index --find-links "$pipDownloadPath/$installPath/" --target "$pipInstallPath/$installPath/" --upgrade --upgrade-strategy "only-if-needed"
		fi
	fi
}
################################################################################
function upgrade-yt-dlp(){
	# upgrade the yt-dlp binary to the latest version
	#
	# - https://github.com/yt-dlp/yt-dlp-nightly-builds/releases/latest/download/yt-dlp
	# - https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp

	# the stable version of yt-dlp
	stableLink="https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp"
	# the nightly builds of yt-dlp
	unstableLink="https://github.com/yt-dlp/yt-dlp-nightly-builds/releases/latest/download/yt-dlp"

	# create the directories to store the download
	createDir "/var/cache/2web/generated/yt-dlp/"
	createDir "/var/cache/2web/downloads/yt-dlp/"
	# download yt-dlp directly
	# - only download the file if the modified time is newer
	# - fetch the stable or unstable version based on cfg file
	if yesNoCfgCheck "/etc/2web/download-yt-dlp-stable-version.cfg" "yes";then
		wget -N "$stableLink" -P "/var/cache/2web/downloads/yt-dlp/"
	else
		wget -N "$unstableLink" -P "/var/cache/2web/downloads/yt-dlp/"
	fi
	# get the sum of the downloaded cache file
	newFileSum=$(md5sum "/var/cache/2web/downloads/yt-dlp/yt-dlp" | cut -d' ' -f1 )
	# check if the file is already installed
	if test -f "/var/cache/2web/generated/yt-dlp/yt-dlp";then
		# if the file is installed get the file sum
		oldFileSum=$(md5sum "/var/cache/2web/generated/yt-dlp/yt-dlp" | cut -d' ' -f1 )
	else
		oldFileSum=0
	fi
	# check the new file is diffrent than the old file by comparing the sums
	if [ $newFileSum == $oldFileSum ];then
		ALERT "No upgrade could not be found..."
	else
		# upgrade the package with the new one
		addToLog "UPDATE" "Upgrading Package" "Upgrading the yt-dlp package."
		# copy over the new file
		cp -v "/var/cache/2web/downloads/yt-dlp/yt-dlp" "/var/cache/2web/generated/yt-dlp/yt-dlp"
		# set the permissions
		chmod +x "/var/cache/2web/generated/yt-dlp/yt-dlp"
	fi
}
################################################################################
function lockProc(){
	# Lock a module to a single process. This should prevent any module from running
	# more than one process at a time.
	#
	# - Creates a trap to remove lock when the parent process dies
	# - Creates a lockfile in the web directory path while the process is active.
	# - Creates a activeGraph file to mark the activity graph
	#
	# RETURN FILES

	# get the module name for use as the proc name
	procName=$(echo "${0##*/}" | cut -d'.' -f1)
	# get the set web root
	webDirectory=$(webRoot)
	# store the color codes for coloring
	resetCode="\033[0m"
	#
	INFO "$webDirectory/${procName}.active"
	if test -f "$webDirectory/${procName}.active";then
		# system is already running exit
		drawLine
		ALERT "${procName} is already processing data in another process."
		ALERT "IF THIS IS IN ERROR REMOVE LOCK FILE AT '$webDirectory/${procName}.active'."
		drawLine
		exit
	else
		# disable lockout for main 2web module to prevent the mangling of graphs
		if [ "$procName" != "2web" ];then
			# if the steam lockout is enabled, default is yes
			if yesNoCfgCheck "/etc/2web/steamLockout.cfg" "yes";then
				# check for active steam games by searching for the in-game overlay interface
				if pgrep "gameoverlayui" > /dev/null;then
					# if the gameoverlayui is active then a game is currently running
					# - skip over this launch as if the module is already running
					drawLine
					ALERT "A Steam game is currently running."
					ALERT "${procName} will not run while a steam game is running."
					ALERT "Change you setting in /etc/2web/steamLockout.cfg"
					drawLine
					# exit without launch
					exit
				fi
			fi
		fi
		INFO "Setting Active Flag $webDirectory/${procName}.active"
		# set the active flag
		echo "$(date "+%s")" > "$webDirectory/${procName}.active"
		# the activegraph file will be removed when the graph is updated
		# - this detects when  a module runs but is finished before the graph is updated
		touch "$webDirectory/${procName}.activeGraph"
		INFO "Setting Active Trap $webDirectory/${procName}.active"
		# create a trap to remove module lockfile
		trap "exitModuleTrap ${procName}" EXIT
		# set the last updated time
		date "+%s" > "$webDirectory/lastUpdate.index"
		# create the ram disk directory if it does not exist
		createDir "/var/cache/2web/ram/${procName}/"
		# create ramdisk for the module to use
		mount -t tmpfs -o size=1M tmpfs /var/cache/2web/ram/${procName}/ || ERROR "MOUNTING RAMDISK FAILED"
	fi
	greenText
		drawLine
		drawSmallHeader "$procName"
		drawLine
	resetColor
}
################################################################################
function exitModuleTrap(){
	procName="$1"
	# lock control-c
	trap "ALERT 'Please wait for module to close...'" INT
	# store the color codes for coloring
	yellowText
		drawLine
		drawSmallHeader "Closing $procName Please Wait..."
		drawLine
	resetColor
	#ALERT "Please wait for module to close..."
	INFO "Reseting Cursor settings..."
	# return the cursor to normal operation
	tput cnorm
	# stop the spinner before removing the ramdisk since it uses the ramdisk
	#stopSpinner
	INFO "Removing '$procName' Ramdisk..."
	# remove the ramdisk
	if test -d "/var/cache/2web/ram/${procName}/";then
		# umount is used to unmount the ramdisk
		umount "/var/cache/2web/ram/${procName}/"
		# remove the ramdisk directory contents
		rm -r  "/var/cache/2web/ram/${procName}/"
	fi
	INFO "Unlocking '$procName'..."
	# remove the process lock
	rm /var/cache/2web/web/${procName}.active
	#
	INFO "'$procName' Has successfully exited."
	redText
		drawLine
		drawSmallHeader "$procName"
		drawLine
	resetColor
	# unlock control-c
	trap INT
}
################################################################################
function returnModStatus(){
	# Check if a module is enabled
	#
	# $1 = moduleName : The name of the module to check the enable/disabled status of.
	#
	# - Return true if the module name is enabled
	#
	# ex)
	#   returnModStatus "nfo2web"
	# 	Will return true if nfo2web is enabled.
	#
	# RETURN BOOL
	moduleName="$1"
	if [ "$moduleName" == "" ];then
		moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	fi
	# foreground color codes
	redFG="\033[38;5;9m"
	greenFG="\033[38;5;10m"
	# reset all color code
	resetTerm="\033[0m"
	# the config exists check the config
	if test -f "/etc/2web/mod_status/${moduleName}.cfg";then
		if grep -q "yes" "/etc/2web/mod_status/${moduleName}.cfg";then
			#echo -e "MOD $moduleName IS ${greenFG}ENABLED${resetTerm}!"
			return 0
		else
			#echo -e "MOD $moduleName IS ${redFG}DISABLED${resetTerm}!"
			return 1
		fi
	else
		#echo -e "MOD $moduleName IS ${redFG}DISABLED${resetTerm}!"
		return 1
	fi
}
################################################################################
function checkModStatus(){
	# For use at startup of a module, If mod is disabled the local module nuke() is
	# launched to clear generated data from previous module runs.
	#
	# $1 = moduleName : name of the module to check.
	#
	# - If the mod is enabled allow the module to keep running
	# - If the mod is disabled run the nuke command within that module
	#
	# RETURN NULL

	moduleName=$1
	#
	if [ "$moduleName" == "" ];then
		moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	fi

	# check the mod status
	if test -f "/etc/2web/mod_status/${moduleName}.cfg";then
		# the config exists check the config
		if grep -q "yes" "/etc/2web/mod_status/${moduleName}.cfg";then
			# the module is enabled
			echo "Preparing to process ${moduleName}..."
		else
			ALERT "MOD IS DISABLED!"
			ALERT "Edit /etc/2web/mod_status/${moduleName}.cfg to contain only the text 'enabled' in order to enable the 2web module."
			# the module is not enabled
			# - remove the files and directory if they exist
			nuke
			exit
		fi
	else
		createDir "/etc/2web/mod_status/"
		# the config does not exist at all create the default one
		# - the default status for module should be disabled
		echo -n "no" > "/etc/2web/mod_status/${moduleName}.cfg"
		chown www-data:www-data "/etc/2web/mod_status/${moduleName}.cfg"
		# exit the script since by default the module is disabled
		exit
	fi
}
################################################################################
function enableMod(){
	# Enable a module name
	#
	# $1 = moduleName : The name of the module to enable
	#
	# RETURN FILES
	moduleName=$1
	#
	if [ "$moduleName" == "" ];then
		moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	fi

	ALERT "Enabling the module $moduleName"
	# create a group for the module when it is enabled
	createDir "/etc/2web/groups/${moduleName}/"
	# enable the module
	echo -n "yes" > /etc/2web/mod_status/${moduleName}.cfg
	# fix ownership so the web interface can change settings
	chown www-data:www-data /etc/2web/mod_status/${moduleName}.cfg
}
################################################################################
function disableMod(){
	# Disable a module name
	#
	# $1 = moduleName : The name of the module to disable
	#
	# RETURN FILES
	moduleName=$1
	if [ "$moduleName" == "" ];then
		moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	fi
	ALERT "Disabling the module $moduleName"
	echo -n "no" > /etc/2web/mod_status/${moduleName}.cfg
	# fix ownership so the web interface can change settings
	chown www-data:www-data /etc/2web/mod_status/${moduleName}.cfg
}
################################################################################
function loadWithoutComments(){
	# Load a file without comment lines starting in #
	#
	#	- $1 = fileName : The path of the file to load without comments
	#
	# RETURN STDOUT
	grep -Ehv "^#" "$1"
	return 0
}
################################################################################
function drawHeader(){
	# draw a header with figlet
	termWidth=$(tput cols)
	#
	themeTitle=$( basename "$themeName" | cut -d'.' -f1 )
	#
	figlet -w "$termWidth" -c -f "smblock" "$1"
	#figlet -w "$termWidth" -c -f "smblock" "$1" | lolcat -a
	#figlet -w "$termWidth" -c -f "pagga" "$1"
	#figlet -w "$termWidth" -c -f "future" "$1"
	#figlet -w "$termWidth" -c -f "smbraille" "$1"
	#figlet -w "$termWidth" -c -f "emboss" "$1"
}
################################################################################
function drawRandomizedPattern(){
	# store the pattern
	pattern="$1"
	# get the length of the pattern
	patternLength="${#pattern}"
	# get the value to pull from the pattern
	tempValue=$(( $RANDOM % ( $patternLength ) ))
	# pull the character from the pattern
	echo -n "${pattern:$tempValue:1}"
}
################################################################################
function drawPattern(){
	# store the pattern
	pattern="$1"
	#ALERT "$2" "input index"
	# get the length of the pattern
	patternLength="${#pattern}"
	#ALERT "$patternLength" "patternLength"
	# get the value to pull from the pattern
	tempValue=$(( $2 % ( $patternLength ) ))
	#ALERT "$tempValue" "tempValue"
	# pull the character from the pattern
	echo -n "${pattern:$tempValue:1}"
}
################################################################################
function drawAltPattern(){
	# drawGeoSymbols $line_x_position
	#
	# Draw 2 or more geometric symbols in a pattern
	#
	# --default
	#	  🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗
	# --quilt
	#   🙪 🙨 🙪 🙨 🙪 🙨
	# --flowers
	#   ⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇
	# --bowtie
	#   ⬖⬗⬖⬗⬖⬗⬖⬗⬖⬗⬖⬗
	# --book
	#   🕮 🕮 🕮 🕮 🕮 🕮
	# --castle
	#   ⛫🜁🜁⛫🜁🜁⛫🜁🜁⛫🜁🜁
	# --flowerRand
	#   ❁❀✾✿❁✾✿❀✾✿❀✾
	# --brick
	#   𝍁𝌙𝌌𝌎𝌽𝍔𝍖𝍏𝌺𝌸𝌌𝌺
	#
	if [ $1 -ge 0 ];then
		tempValue=$1
	else
		tempValue=$RANDOM
	fi
	if echo "$@" | grep -q -e "--quilt";then
		# 🙪 🙨 🙪 🙨 🙪 🙨 🙪
		echo -n "$(drawPattern "🙪 🙨 " "$tempValue")"
	elif echo "$@" | grep -q -e "--flowerRand2";then
		echo -n "$(drawRandomizedPattern "✿✾❀❁𑽇⚘")"
	elif echo "$@" | grep -q -e "--flowerRand";then
		echo -n "$(drawRandomizedPattern "✿✾❀❁")"
	elif echo "$@" | grep -q -e "--flowers";then
		# ⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇
		echo -n "$(drawPattern "⚘𑽇" "$tempValue")"
	elif echo "$@" | grep -q -e "--bowtie";then
		# ⬖⬗⬖⬗⬖⬗⬖⬗⬖⬗⬖⬗
		echo -n "$(drawPattern "⬗⬖" "$tempValue")"
	elif echo "$@" | grep -q -e "--book";then
		# 🕮 🕮 🕮 🕮 🕮 🕮
		echo -n "$(drawPattern "🕮 " "$tempValue")"
	elif echo "$@" | grep -q -e "--castle";then
		# ⛫🜁🜁⛫🜁🜁⛫🜁🜁⛫🜁🜁⛫🜁🜁⛫🜁🜁⛫🜁🜁
		echo -n "$(drawPattern "⛫🜁🜁" "$tempValue")"
	elif echo "$@" | grep -q -e "--papersRand";then
		# 🗉🗏🗟🗉🗏🗟🗉🗏🗟🗉🗏🗟🗉🗏🗏🗟🗉🗏🗟🗉🗟
		echo -n "$(drawRandomizedPattern "🗉🗏🗟")"
	elif echo "$@" | grep -q -e "--papersRand2";then
		# 🗉🗌🗆🗏🗟🗉🗌🗆🗏🗟🗉🗌🗆🗏🗟🗉🗌🗆🗏🗟🗉
		echo -n "$(drawRandomizedPattern "🗉🗌🗆🗏🗟")"
	elif echo "$@" | grep -q -e "--brick";then
		# 𝍁𝌙𝌌𝌎𝌽𝍔𝍖𝍏𝌺𝌸𝌌𝌺𝌸𝌎𝌎𝌽𝍔𝍖𝍏𝌎𝌽𝍔𝍖𝍏
		tempValue=$(( $RANDOM % 81 ))
		pattern=""
		# store all the tetragrams
		pattern="${pattern}𝍁𝌙𝍑𝌌𝌉𝌣𝌎𝌆𝌡𝌽𝌦𝍏𝍎𝍒𝌸𝌕"
		pattern="${pattern}𝌞𝌋𝍈𝌢𝌏𝍇𝍔𝌼𝍉𝌐𝍃𝌠𝌜𝍂𝌰𝌟"
		pattern="${pattern}𝌳𝌺𝍊𝍐𝌶𝍖𝌘𝌇𝌫𝌨𝌿𝌯𝌲𝌾𝍍𝌖"
		pattern="${pattern}𝌒𝍆𝌝𝌊𝌧𝍕𝌭𝌥𝍀𝌹𝌈𝍓𝌍𝌤𝌴𝌓"
		pattern="${pattern}𝌪𝌔𝌚𝌬𝌛𝌮𝌵𝍋𝍅𝍌𝍌𝌱𝌩"
		pattern="${pattern}𝌻𝌷𝌗𝍄𝌑"
		echo -n "$(drawRandomizedPattern "$pattern")"
	elif echo "$@" | grep -q -e "--floppy";then
		# 🖫 🖪 🖬 🖫 🖪 🖬 🖫 🖪 🖬 🖫 🖪 🖬
		echo -n "$(drawPattern "🖫☐🖪☐🖬☐" "$tempValue")"
	elif echo "$@" | grep -q -e "--computers";then
		# 🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧
		echo -n "🖧"
	elif echo "$@" | grep -q -e "--computer";then
		# 🖳┉🖳┉🖳┉🖳┉🖳┉🖳┉🖳┉🖳┉
		echo -n "$(drawPattern "🖳┉" "$tempValue")"
	elif echo "$@" | grep -q -e "--term";then
		# 🗔 🖳 🗔 🖳 🗔 🖳 🗔 🖳
		echo -n "$(drawPattern "🗔 🖳 " "$tempValue")"
	elif echo "$@" | grep -q -e "--altdice";then
		# - altdice      ⚁☐⚂☐⚄☐⚄☐⚁☐⚁☐⚄☐⚄☐
		tempValue=$(( $tempValue % 2 ))
		if [ $tempValue -eq 0 ];then
			echo -n "☐"
		else
			echo -n "$(rollDie)"
		fi
	elif echo "$@" | grep -q -e "--grass";then
		# - grass        🗤 🗤 🗤 🗤 🗤 🗤 🗤 🗤
		echo -n "$(drawPattern "🗤 " "$tempValue")"
	elif echo "$@" | grep -q -e "--vines";then
		# - vines        🗥 🗥 🗥 🗥 🗥 🗥 🗥 🗥
		echo -n "$(drawPattern "🗥 " "$tempValue")"
	elif echo "$@" | grep -q -e "--crossAlt";then
		echo -n "$(drawPattern "🕇🕆" "$tempValue")"
	else
		# 🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗
		echo -n "$(drawPattern "🟕🟗" "$tempValue")"
	fi
}
################################################################################
lineThemeDemo(){
	drawLineTheme
}
################################################################################
randomLineTheme(){
	# Load a random line theme from the list
	themes="dice flower flower2 flower3 flower4 flower5 flower6 flowers flowerRand flowerRand2 wood quilt suit card solid double stitch diamond smallDotted dotted boxes note hollowBlock bowtie block bottomBlock fadeDown fadeUp book castle floppy computer computers term lines sine grass vines altdice weather sid chem papersRand papersRand2 cross cross2 crossAlt graph"
	#
	echo -ne "$themes" | sed "s/ /\n/g" | shuf | head -1
}
################################################################################
loadLineTheme(){
	# loadLineTheme "$index" "$themeName"
	#
	# Draw a single character in a line using the given line theme
	#
	# Load a line theme, the following themes are available
	#
	# - dice         ⚁⚂⚄⚄⚁⚁⚄⚄⚂⚁⚁⚁⚂⚂⚄⚁
	# - flowers      ⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇
	# - flower       ❀❀❀❀❀❀❀❀❀❀❀❀❀❀❀❀
	# - flower2      ✾✾✾✾✾✾✾✾✾✾✾✾✾✾✾✾
	# - flower3      ✿✿✿✿✿✿✿✿✿✿✿✿✿✿✿✿
	# - flower4      ❁❁❁❁❁❁❁❁❁❁❁❁❁❁❁❁
	# - flowerRand   ❁❀✾✿❁✾✿❀✾✿❀✾✿❁✾❀
	# - wood         🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗🟕🟗
	# - quilt        🙪 🙨 🙪 🙨 🙪 🙨 🙪 🙪
	# - suit         ♤♢♧♡♤♢♧♡♤♢♧♡♤♢♧♡
	# - card
	# - solid        ━━━━━━━━━━━━━━━━
	# - double       ════════════════
	# - stitch       ╳╳╳╳╳╳╳╳╳╳╳╳╳╳╳╳
	# - diamond      🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮🮮
	# - smallDotted  ┉┉┉┉┉┉┉┉┉┉┉┉┉┉┉┉
	# - dotted       ┅┅┅┅┅┅┅┅┅┅┅┅┅┅┅┅
	# - boxes        ☐☐☐☐☐☐☐☐☐☐☐☐☐☐☐☐
	# - note
	# - hollowBlock  🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀🮀
	# - bowtie       ⬖⬗⬖⬗⬖⬗⬖⬗⬖⬖⬗⬖⬗⬖⬗⬖
	# - block        ████████████████
	# - bottomBlock  ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
	# - fadeDown     🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒🮒
	# - fadeUp       🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑🮑
	# - book         🕮 🕮 🕮 🕮 🕮 🕮 🕮 🕮
	# - castle       ⛫═⛫═⛫═⛫═⛫═⛫═⛫═⛫═
	# - floppy       🖫☐🖬☐🖪☐🖫☐🖬☐🖪☐🖫☐🖬☐
	# - computer     🖳┉🖳┉🖳┉🖳┉🖳┉🖳┉🖳┉🖳┉
	# - computers    🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧🖧
	# - term         🗔 🖳 🗔 🖳 🗔 🖳 🗔 🖳
	# - lines        𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛𝄛
	# - sine         ∿∿∿∿∿∿∿∿∿∿∿∿∿∿∿∿
	# - grass        🗤 🗤 🗤 🗤 🗤 🗤 🗤 🗤
	# - vines        🗥 🗥 🗥 🗥 🗥 🗥 🗥 🗥
	# - altdice      ⚁☐⚂☐⚄☐⚄☐⚁☐⚁☐⚄☐⚄☐
	# - weather
	# - sid          𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗𑗗
	# - chem
	#
	index="$1"
	lineTheme="$2"
	if [ "$LINE_THEME" == "" ];then
		lineTheme="fadeUp"
	else
		lineTheme="$LINE_THEME"
	fi
	# default theme
	if [ "$lineTheme" == "" ];then
		lineTheme="flower"
	fi

	if [ "$lineTheme" == "dice" ];then
		# draw random dice for each line
		echo -n "$(rollDie)"
	elif [ "$lineTheme" == "wood" ];then
		echo -n "$(drawAltPattern "$index" --wood)"
	elif [ "$lineTheme" == "quilt" ];then
		echo -n "$(drawAltPattern "$index" --quilt)"
	elif [ "$lineTheme" == "bowtie" ];then
		echo -n "$(drawAltPattern "$index" --bowtie)"
	elif [ "$lineTheme" == "book" ];then
		echo -n "$(drawAltPattern "$index" --book)"
	elif [ "$lineTheme" == "castle" ];then
		echo -n "$(drawAltPattern "$index" --castle)"
	elif [ "$lineTheme" == "brick" ];then
		echo -n "$(drawAltPattern "$index" --brick)"
	elif [ "$lineTheme" == "suit" ];then
		# draw a card suite
		echo -n "$(drawCardSuite "$index")"
	elif [ "$lineTheme" == "card" ];then
		# draw a random card from a standard deck of cards
		echo -n "$(drawCard "$index")"
	elif [ "$lineTheme" == "solid" ];then
		echo -n "━"
	elif [ "$lineTheme" == "double" ];then
		echo -n "═"
	elif [ "$lineTheme" == "stitch" ];then
		echo -n "╳"
	elif [ "$lineTheme" == "diamond" ];then
		echo -n "🮮"
	elif [ "$lineTheme" == "small_dotted" ];then
		echo -n "┉"
	elif [ "$lineTheme" == "dotted" ];then
		echo -n "┅"
	elif [ "$lineTheme" == "block" ];then
		echo -n "█"
	elif [ "$lineTheme" == "bottomBlock" ];then
		echo -n "▄"
	elif [ "$lineTheme" == "hollowBlock" ];then
		echo -n "🮀"
	elif [ "$lineTheme" == "boxes" ];then
		echo -n "☐"
	elif [ "$lineTheme" == "fadeUp" ];then
		echo -n "🮑"
	elif [ "$lineTheme" == "fadeDown" ];then
		echo -n "🮒"
	elif [ "$lineTheme" == "lines" ];then
		echo -n "𝄛"
	elif [ "$lineTheme" == "note" ];then
		#
		tempValue=$(( $index % 4 ))
		#
		if [ $tempValue -eq 0 ];then
			echo -n "🎜"
		elif [ $tempValue -eq 1 ];then
			echo -n "♬"
		elif [ $tempValue -eq 2 ];then
			echo -n "♫"
		elif [ $tempValue -eq 3 ];then
			echo -n "♬"
		fi
		#𝄚
		#echo -n "♪"
		#echo -n "♩"
		#echo -n "♮"
		#echo -n "𝅘𝅥𝅱"
		#echo -n "𝆕"
		#echo -n "𝅘𝅥𝅮"
		#echo -n "♭"
		#echo -n "𝅝"
	elif [ "$lineTheme" == "floppy" ];then
		# - floppy       🖫 🖬 🖪 🖫 🖬 🖪 🖫 🖬
		echo -n "$(drawAltPattern "$index" --floppy)"
	elif [ "$lineTheme" == "computers" ];then
		# - computers    🖳 🖧 🖳 🖧 🖳 🖧 🖳 🖧
		echo -n "$(drawAltPattern "$index" --computers)"
	elif [ "$lineTheme" == "computer" ];then
		# - computer     🖳 🖳 🖳 🖳 🖳 🖳 🖳 🖳
		echo -n "$(drawAltPattern "$index" --computer)"
	elif [ "$lineTheme" == "term" ];then
		# - term         🗔 🖳 🗔 🖳 🗔 🖳 🗔 🖳
		echo -n "$(drawAltPattern "$index" --term)"
	elif [ "$lineTheme" == "sine" ];then
		# - sine         ∿∿∿∿∿∿∿∿∿∿∿∿∿∿∿∿
		echo -n "∿"
	elif [ "$lineTheme" == "grass" ];then
		# - grass        🗤 🗤 🗤 🗤 🗤 🗤 🗤 🗤
		echo -n "🗤"
	elif [ "$lineTheme" == "vines" ];then
		# - vines        🗥 🗥 🗥 🗥 🗥 🗥 🗥 🗥
		echo -n "🗥"
	elif [ "$lineTheme" == "altdice" ];then
		# - altdice      ⚁☐⚂☐⚄☐⚄☐⚁☐⚁☐⚄☐⚄☐
		echo -n "$(drawAltPattern "$index" --altdice)"
	elif [ "$lineTheme" == "weather" ];then
		# - cloud
		echo -n "$(drawRandomizedPattern "🌣🗲🌢🌢🌢🌢🌢🌢")"
	elif [ "$lineTheme" == "cross" ];then
		echo -n "🕇"
	elif [ "$lineTheme" == "cross2" ];then
		echo -n "🕆"
	elif [ "$lineTheme" == "crossAlt" ];then
		#echo -n "$(drawPattern "🕇🕆" "$tempValue")"
		#echo -n "$(drawRandomizedPattern "🕇🕆" "$tempValue")"
		echo -n "$(drawAltPattern "$index" --crossAlt)"
	elif [ "$lineTheme" == "sid" ];then
		# - siddham mark
		echo -n "𑗗"
	elif [ "$lineTheme" == "chem" ];then
		# - random chemestry symbols
		echo -n "$(randomChem)"
	elif [ "$lineTheme" == "flower" ];then
		# - flower       ❀❀❀❀❀❀❀❀❀❀❀❀❀❀❀❀
		echo -n "❀"
	elif [ "$lineTheme" == "flower2" ];then
		# - flower2      ✾✾✾✾✾✾✾✾✾✾✾✾✾✾✾✾
		echo -n "✾"
	elif [ "$lineTheme" == "flower3" ];then
		# - flower3      ✿✿✿✿✿✿✿✿✿✿✿✿✿✿✿✿
		echo -n "✿"
	elif [ "$lineTheme" == "flower4" ];then
		# - flower4      ❁❁❁❁❁❁❁❁❁❁❁❁❁❁❁❁
		echo -n "❁"
	elif [ "$lineTheme" == "flower5" ];then
		# - flower5      ⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘⚘
		echo -n "⚘"
	elif [ "$lineTheme" == "flower6" ];then
		# - flower6      𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇𑽇
		echo -n "𑽇"
	elif [ "$lineTheme" == "flowers" ];then
		# - flowers      ⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇⚘𑽇
		echo -n "$(drawAltPattern "$index" --flowers)"
	elif [ "$lineTheme" == "flowerRand" ];then
		# - flowerRand   ⚘❁❀⚘⚘✾✿⚘❁✾✿❀⚘✾✿⚘❀
		echo -n "$(drawRandomizedPattern "⚘𑽇❁❀✾✿" "$tempValue")"
	elif [ "$lineTheme" == "flowerRand2" ];then
		# - flowerRand2   ❁❀✾✿❁✾✿❀✾✿❀✾✿❁✾❀
		echo -n "$(drawRandomizedPattern "❁❀✾✿" "$tempValue")"
	elif [ "$lineTheme" == "papersRand" ];then
		echo -n "$(drawRandomizedPattern "🗉🗏🗟🖻🖺🗈🗎")"
	elif [ "$lineTheme" == "papersRand2" ];then
		echo -n "$(drawRandomizedPattern "🗉🗌🗆🗏🗟🖻🖺🗈🗎")"
	elif [ "$lineTheme" == "graph" ];then
		echo -n "🗠"
	else
		# draw the default theme if no theme could be matched
		# - solid
		echo -n "━"
	fi
}
################################################################################
function drawLine(){
	# Draw a line across the terminal using curses to determine the length
	width=$(tput cols)
	buffer=""
	# make the buffer the width of the terminal
	#█🮮◙⭗🟕🟗═
	for index in $(seq 0 $(( $width - 1 )) );do
		# draw random dice for each line
		buffer="$buffer$(loadLineTheme "$index" )"
	done
	# draw the buffer
	echo "$buffer";
}
################################################################################
function drawCardSuite(){
	# drawCardSuite $numericSuiteValue --color
	#
	# Draw a card suite icon
	#
	# --color ♠️ ♦️ ♣️ ♥️ ♠️ ♦️ ♣️ ♥️ ♠️ ♦️ ♣️ ♥️ ♠️ ♦️ ♣️ ♥️ ♠️ ♦️ ♣️ ♥️
	#
	# --default ♤♢♧♡♤♢♧♡♤♢♧♡♤♢♧♡♤♢♧♡♤♢♧♡♤♢♧♡♤♢♧♡
	#
	# If no color option is given the default is used
	#
	# - '--color' will draw icons as colored emojis
	# - defaults to random
	# - takes a input and draws a suite
	#

	tempValue="$1"

	# check for color flag
	if echo "$@" | grep -q -e "--color";then
		color="yes"
	else
		color="no"
	fi
	# if color mode is enabled
	if [ "$color" == "yes" ];then
		tempValue=$(( $tempValue % 8 ))
		#
		if [ $tempValue -eq 0 ];then
			echo -n "♠️"
		elif [ $tempValue -eq 1 ];then
			echo -n " "
		elif [ $tempValue -eq 2 ];then
			echo -n "♦️"
		elif [ $tempValue -eq 3 ];then
			echo -n " "
		elif [ $tempValue -eq 4 ];then
			echo -n "♣️"
		elif [ $tempValue -eq 5 ];then
			echo -n " "
		elif [ $tempValue -eq 6 ];then
			echo -n "♥️"
		elif [ $tempValue -eq 7 ];then
			echo -n " "
		fi
	else
		tempValue=$(( $tempValue % 4 ))
		if [ $tempValue -eq 0 ];then
			echo -n "♤"
		elif [ $tempValue -eq 1 ];then
			echo -n "♢"
		elif [ $tempValue -eq 2 ];then
			echo -n "♧"
		elif [ $tempValue -eq 3 ];then
			echo -n "♡"
		fi
	fi
}
################################################################################
function drawCard(){
	# drawCard
	#
	# draw a random card and print the ascii character code for that card
	#
	tempValue=$(( $RANDOM % 13 ))
	if [ $tempValue -eq 0 ];then
		echo -n "🂡"
	elif [ $tempValue -eq 1 ];then
		echo -n "🂢"
	elif [ $tempValue -eq 2 ];then
		echo -n "🂣"
	elif [ $tempValue -eq 3 ];then
		echo -n "🂤"
	elif [ $tempValue -eq 4 ];then
		echo -n "🂥"
	elif [ $tempValue -eq 5 ];then
		echo -n "🂦"
	elif [ $tempValue -eq 6 ];then
		echo -n "🂧"
	elif [ $tempValue -eq 7 ];then
		echo -n "🂨"
	elif [ $tempValue -eq 8 ];then
		echo -n "🂩"
	elif [ $tempValue -eq 9 ];then
		echo -n "🂪"
	elif [ $tempValue -eq 10 ];then
		echo -n "🂫"
	elif [ $tempValue -eq 11 ];then
		echo -n "🂭"
	elif [ $tempValue -eq 12 ];then
		echo -n "🂮"
	fi
}
################################################################################
function rollDie(){
	#
	tempValue=$(( $RANDOM % 6 ))
	#
	if [ $tempValue -eq 0 ];then
		echo -n "⚀"
	elif [ $tempValue -eq 1 ];then
		echo -n "⚁"
	elif [ $tempValue -eq 2 ];then
		echo -n "⚂"
	elif [ $tempValue -eq 3 ];then
		echo -n "⚃"
	elif [ $tempValue -eq 4 ];then
		echo -n "⚄"
	elif [ $tempValue -eq 5 ];then
		echo -n "⚅"
	fi
}
################################################################################
function randomChem(){
	#
	tempValue=$(( $RANDOM % 117 ))
	#
	if [ $tempValue -eq 0 ];then
		echo -n "🜁"
	elif [ $tempValue -eq 1 ];then
		echo -n "🝪"
	elif [ $tempValue -eq 2 ];then
		echo -n "🜶"
	elif [ $tempValue -eq 3 ];then
		echo -n "🜷"
	elif [ $tempValue -eq 4 ];then
		echo -n "🝅"
	elif [ $tempValue -eq 5 ];then
		echo -n "🝛"
	elif [ $tempValue -eq 6 ];then
		echo -n "🜫"
	elif [ $tempValue -eq 7 ];then
		echo -n "🜆"
	elif [ $tempValue -eq 8 ];then
		echo -n "🜇"
	elif [ $tempValue -eq 9 ];then
		echo -n "🜈"
	elif [ $tempValue -eq 10 ];then
		echo -n "🜉"
	elif [ $tempValue -eq 11 ];then
		echo -n "🜅"
	elif [ $tempValue -eq 12 ];then
		echo -n "🜺"
	elif [ $tempValue -eq 13 ];then
		echo -n "🝗"
	elif [ $tempValue -eq 14 ];then
		echo -n "🜽"
	elif [ $tempValue -eq 15 ];then
		echo -n "🝫"
	elif [ $tempValue -eq 16 ];then
		echo -n "🝬"
	elif [ $tempValue -eq 17 ];then
		echo -n "🜾"
	elif [ $tempValue -eq 18 ];then
		echo -n "🜏"
	elif [ $tempValue -eq 19 ];then
		echo -n "🝂"
	elif [ $tempValue -eq 20 ];then
		echo -n "🝃"
	elif [ $tempValue -eq 21 ];then
		echo -n "🝄"
	elif [ $tempValue -eq 22 ];then
		echo -n "🝙"
	elif [ $tempValue -eq 23 ];then
		echo -n "🝐"
	elif [ $tempValue -eq 24 ];then
		echo -n "🝌"
	elif [ $tempValue -eq 25 ];then
		echo -n "🝎"
	elif [ $tempValue -eq 26 ];then
		echo -n "🜓"
	elif [ $tempValue -eq 27 ];then
		echo -n "🜥"
	elif [ $tempValue -eq 28 ];then
		echo -n "🜠"
	elif [ $tempValue -eq 29 ];then
		echo -n "🜣"
	elif [ $tempValue -eq 30 ];then
		echo -n "🜤"
	elif [ $tempValue -eq 31 ];then
		echo -n "🜞"
	elif [ $tempValue -eq 32 ];then
		echo -n "🝥"
	elif [ $tempValue -eq 33 ];then
		echo -n "🝦"
	elif [ $tempValue -eq 34 ];then
		echo -n "🝧"
	elif [ $tempValue -eq 35 ];then
		echo -n "🝨"
	elif [ $tempValue -eq 36 ];then
		echo -n "🝩"
	elif [ $tempValue -eq 37 ];then
		echo -n "🝰"
	elif [ $tempValue -eq 38 ];then
		echo -n "🝡"
	elif [ $tempValue -eq 39 ];then
		echo -n "🝢"
	elif [ $tempValue -eq 40 ];then
		echo -n "🝠"
	elif [ $tempValue -eq 42 ];then
		echo -n "🜃"
	elif [ $tempValue -eq 43 ];then
		echo -n "🜂"
	elif [ $tempValue -eq 44 ];then
		echo -n "🜚"
	elif [ $tempValue -eq 45 ];then
		echo -n "🝉"
	elif [ $tempValue -eq 46 ];then
		echo -n "🝲"
	elif [ $tempValue -eq 47 ];then
		echo -n "🝳"
	elif [ $tempValue -eq 48 ];then
		echo -n "🝖"
	elif [ $tempValue -eq 49 ];then
		echo -n "🝮"
	elif [ $tempValue -eq 50 ];then
		echo -n "🜜"
	elif [ $tempValue -eq 51 ];then
		echo -n "🜝"
	elif [ $tempValue -eq 52 ];then
		echo -n "🜡"
	elif [ $tempValue -eq 53 ];then
		echo -n "🜪"
	elif [ $tempValue -eq 54 ];then
		echo -n "🜡"
	elif [ $tempValue -eq 55 ];then
		echo -n "🜪"
	elif [ $tempValue -eq 56 ];then
		echo -n "🝓"
	elif [ $tempValue -eq 57 ];then
		echo -n "🜸"
	elif [ $tempValue -eq 58 ];then
		echo -n "🜐"
	elif [ $tempValue -eq 59 ];then
		echo -n "🝱"
	elif [ $tempValue -eq 60 ];then
		echo -n "🝯"
	elif [ $tempValue -eq 61 ];then
		echo -n "🜕"
	elif [ $tempValue -eq 62 ];then
		echo -n "🝆"
	elif [ $tempValue -eq 63 ];then
		echo -n "🜎"
	elif [ $tempValue -eq 64 ];then
		echo -n "🝘"
	elif [ $tempValue -eq 65 ];then
		echo -n "🝋"
	elif [ $tempValue -eq 66 ];then
		echo -n "🝚"
	elif [ $tempValue -eq 67 ];then
		echo -n "🝟"
	elif [ $tempValue -eq 68 ];then
		echo -n "🝣"
	elif [ $tempValue -eq 69 ];then
		echo -n "🝤"
	elif [ $tempValue -eq 70 ];then
		echo -n "🝁"
	elif [ $tempValue -eq 71 ];then
		echo -n "🜀"
	elif [ $tempValue -eq 72 ];then
		echo -n "🜻"
	elif [ $tempValue -eq 73 ];then
		echo -n "🜼"
	elif [ $tempValue -eq 74 ];then
		echo -n "🜲"
	elif [ $tempValue -eq 75 ];then
		echo -n "🜰"
	elif [ $tempValue -eq 76 ];then
		echo -n "🜱"
	elif [ $tempValue -eq 77 ];then
		echo -n "🜟"
	elif [ $tempValue -eq 78 ];then
		echo -n "🜳"
	elif [ $tempValue -eq 79 ];then
		echo -n "🜴"
	elif [ $tempValue -eq 80 ];then
		echo -n "🜵"
	elif [ $tempValue -eq 81 ];then
		echo -n "🝭"
	elif [ $tempValue -eq 82 ];then
		echo -n "🜘"
	elif [ $tempValue -eq 83 ];then
		echo -n "🜙"
	elif [ $tempValue -eq 84 ];then
		echo -n "🜹"
	elif [ $tempValue -eq 85 ];then
		echo -n "🜔"
	elif [ $tempValue -eq 86 ];then
		echo -n "🜭"
	elif [ $tempValue -eq 87 ];then
		echo -n "🜦"
	elif [ $tempValue -eq 88 ];then
		echo -n "🝏"
	elif [ $tempValue -eq 89 ];then
		echo -n "🜛"
	elif [ $tempValue -eq 90 ];then
		echo -n "🝔"
	elif [ $tempValue -eq 91 ];then
		echo -n "🝇"
	elif [ $tempValue -eq 92 ];then
		echo -n "🝒"
	elif [ $tempValue -eq 93 ];then
		echo -n "🝜"
	elif [ $tempValue -eq 94 ];then
		echo -n "🝝"
	elif [ $tempValue -eq 95 ];then
		echo -n "🜬"
	elif [ $tempValue -eq 96 ];then
		echo -n "🜢"
	elif [ $tempValue -eq 97 ];then
		echo -n "🜮"
	elif [ $tempValue -eq 98 ];then
		echo -n "🜧"
	elif [ $tempValue -eq 99 ];then
		echo -n "🝞"
	elif [ $tempValue -eq 100 ];then
		echo -n "🜍"
	elif [ $tempValue -eq 101 ];then
		echo -n "🜿"
	elif [ $tempValue -eq 102 ];then
		echo -n "🝀"
	elif [ $tempValue -eq 103 ];then
		echo -n "🜩"
	elif [ $tempValue -eq 104 ];then
		echo -n "🝈"
	elif [ $tempValue -eq 105 ];then
		echo -n "🝑"
	elif [ $tempValue -eq 106 ];then
		echo -n "🝍"
	elif [ $tempValue -eq 107 ];then
		echo -n "🝕"
	elif [ $tempValue -eq 108 ];then
		echo -n "🜨"
	elif [ $tempValue -eq 109 ];then
		echo -n "🜊"
	elif [ $tempValue -eq 110 ];then
		echo -n "🜯"
	elif [ $tempValue -eq 111 ];then
		echo -n "🜋"
	elif [ $tempValue -eq 112 ];then
		echo -n "🜌"
	elif [ $tempValue -eq 113 ];then
		echo -n "🜖"
	elif [ $tempValue -eq 114 ];then
		echo -n "🜗"
	elif [ $tempValue -eq 115 ];then
		echo -n "🜄"
	elif [ $tempValue -eq 116 ];then
		echo -n "🝊"
	fi
}

################################################################################
function drawSmallHeader(){
	# Draw a line across the terminal using curses to determine the length
	width=$(tput cols)
	buffer=""
	# make the buffer the width of the terminal
	#█🮮◙⭗🟕🟗═
	headerText="$1"
	# count the bytes
	headerTextLength=${#headerText}
	# get the size of the first half of the line
	bufferSize=$(( ( $width - ( $headerTextLength + 2 ) ) / 2 ))
	for index in $(seq 0 $(( $bufferSize - 1 )) );do
		#
		buffer="$buffer$(loadLineTheme "$index" )"
	done
	# draw the text in the middle of the line
	buffer="$buffer $1 "
	# Figure out the remaining width of the terminal
	# - This must be done for odd numbered widths
	currentBufferSize=${#buffer}
	bufferSize=$(( $width - $currentBufferSize ))
	# fill out the remaining width of the terminal
	for index in $(seq 0 $(( $bufferSize - 1 )) );do
		#
		buffer="$buffer$(loadLineTheme "$index" )"
	done
	# draw the output to the terminal
	echo "$buffer";
}
################################################################################
function showServerLinks(){
	# In the CLI show the server links to the homepage and the settings
	#
	# - For people who click links in the terminal
	drawLine
	drawHeader "Server Links"
	drawLine
	drawSmallHeader "To access the webserver go to the below link."
	drawLine
	echo "http://$(hostname).local:80/"
	drawLine
	drawSmallHeader "To access the administrative interface go to the below link."
	drawLine
	echo "http://$(hostname).local:80/settings/"
	drawLine
}
################################################################################
function addSourcePath(){
	# addSourcePath $sourcePath $sourceFilePath
	#
	# - add a sourcePath to the sourceFilePath
	# - prevent duplicates
	sourcePath="$1"
	sourceFilePath="$2"
	# add the path to the list of paths for duplicate checking and rescans
	if test -f "$sourceFilePath";then
		# check if the directory path exist
		if ! grep -Fq "$sourcePath" "$sourceFilePath";then
			# if the path is not in the file add it to the file
			echo "$sourcePath" >> "$sourceFilePath"
		fi
	else
		# create the file
		echo "$sourcePath" > "$sourceFilePath"
	fi
}
################################################################################
function addToIndex(){
	# addToIndex $indexItem $indexPath
	#
	# Add a item to a index file, prevent duplicate entries
	#
	indexItem="$1"
	indexPath="$2"
	#INFO "Checking if the indexPath '$indexPath' exists"
	if test -f "$indexPath";then
		# the index file exists
		#ALERT "Looking for $indexItem in $indexPath"
		if grep -q "$indexItem" "$indexPath";then
			echo -n
			#INFO "The Index '$indexPath' already contains '$indexItem'"
		else
			#ALERT "Adding '$indexItem' to '$indexPath'"
			# the item is not in the index
			echo "$indexItem" >> "$indexPath"
		fi
	else
		#ALERT "No index found, creating one..."
		#ALERT "Adding '$indexItem' to '$indexPath'"
		# create the index file
		touch "$indexPath"
		# set ownership of the newly created index
		chown www-data:www-data "$indexPath"
		# the index file does not exist
		echo "$indexItem" > "$indexPath"
	fi
}
################################################################################
function SQLaddToIndex(){
	indexItem="$1"
	indexPath="$2"
	databaseTable="_$3"
	# set the default timeout to wait for writing to the database
	# - time in miliseconds
	# - 1 minute default
	timeout=60000
	#example: /var/cache/2web/new.sql
	#INFO "Checking if the indexPath '$indexPath' exists"
	# if the database file exists read it
	if test -f "$indexPath";then
		# check if the table exists in the database
		if ! sqlite3 -cmd ".timeout $timeout"  "$indexPath" "select name from sqlite_master where type='table';" | grep -q "$databaseTable";then
			# create the database if it does not exist
			# first set the new database into wal mode for better handling of concurrency in the database
			sqlite3 -cmd ".timeout $timeout" "$indexPath" "PRAGMA journal_mode=WAL;"
			sqlite3 -cmd ".timeout $timeout" "$indexPath" "PRAGMA wal_autocheckpoint=20;"
			# create the table
			sqlite3 -cmd ".timeout $timeout" "$indexPath" "create table $databaseTable(title text primary key);"
		fi
		#ALERT "Looking for $indexItem in $indexPath"
		# if the data is already stored in the database
		if [ $(sqlite3 -cmd ".timeout $timeout" "$indexPath" "select '$indexItem' from '$databaseTable' where title = '$indexItem';" | wc -l) -gt 0 ];then
			echo -n
			#INFO "The Index '$indexPath' already contains '$indexItem'"
		else
			#INFO "Adding '$indexItem' to '$indexPath'"
			sqlite3 -cmd ".timeout $timeout" "$indexPath" "insert into $databaseTable values('$indexItem');"
		fi
	else
		#ALERT "No index found, creating one..."
		#INFO "Adding '$indexItem' to '$indexPath'"
		# create the sql database
		sqlite3 -cmd ".timeout $timeout" "$indexPath" "create table $databaseTable(title text primary key);"
		# add the item to the sql database
		sqlite3 -cmd ".timeout $timeout" "$indexPath" "insert into $databaseTable values('$indexItem');"
		# set ownership of the newly created index
		chown www-data:www-data "$indexPath"
	fi
}
################################################################################
function SQLremoveTable(){
	databasePath=$1
	tableName=$2
	if test -f "$databasePath";then
		# check if the table exists in the database
		timeout=60000
		if ! sqlite3 -cmd ".timeout $timeout"  "$databasePath" "select name from sqlite_master where type='table';" | grep -q "$tableName";then
			# remove the table that has been found
			sqlite3 -cmd ".timeout $timeout"  "$databasePath" "drop table $tableName;"
		fi
	fi
}
################################################################################
function SQLtableLength(){
	databasePath=$1
	tableName=$2
	totalCount=0
	if test -f "$databasePath";then
		# check if the table exists in the database
		timeout=60000
		if ! sqlite3 -cmd ".timeout $timeout"  "$databasePath" "select name from sqlite_master where type='table';" | grep -q "$tableName";then
			# count all items in the table
			totalCount=$(sqlite3 -cmd ".timeout $timeout" "from COUNT(*) in $tableName;")
		fi
	fi
	echo "$totalCount"
}
################################################################################
function checkDirDataSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	directory=$2
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	# check the sum of a directory and compare it to a previously stored sum
	createDir "$webDirectory/sums/"
	pathSum="$(echo "$directory" | sha512sum | cut -d' ' -f1 )"
	newSum="$(getDirDataSum "$2")"
	# check for a previous sum
	if test -f "$webDirectory/sums/${moduleName}_data_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/${moduleName}_data_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			#echo "$newSum" > "$webDirectory/sums/$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		#echo "$newSum" > "$webDirectory/sums/$pathSum.cfg"
		return 0
	fi
}
########################################################################
function getDirDataSum(){
	# check the libary sum against the existing one
	line=$1
	#totalList=$(find "$line" | sort)
	# read the data from each file
	#totalList="$( find "$line" -type f -exec /usr/bin/cat {} \; )"
	totalFileList="$( find "$line" -type f )"
	IFSBACKUP=$IFS
	IFS=$'\n'
	for filePath in $totalFileList;do
		totalList="$( cat "$filePath" )"
	done
	IFS=$IFSBACKUP
	# add the version to the sum to update old versions
	# - Disk caching on linux should make this repetative file read
	#   not destroy the hard drive
	totalList="$totalList$(cat /usr/share/2web/versionDate.cfg)"
	# convert lists into sum
	tempLibList="$(echo -n "$totalList" | sha512sum | cut -d' ' -f1)"
	# write the sum to stdout
	echo "$tempLibList"
}
########################################################################
function setDirDataSum(){
	# for use with checkdir sum, to update a sum as finished
	webDirectory=$1
	directory=$2

	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)

	pathSum="$(echo "$directory" | sha512sum | cut -d' ' -f1 )"
	newSum="$(getDirDataSum "$directory")"

	# write the new sum to the file
	echo "$newSum" > "$webDirectory/sums/${moduleName}_data_$pathSum.cfg"

}
################################################################################
function getDirSum(){
	# getDirSum $dirPath
	#
	# Create a sum of a directory by recursively listing all the files beneath it
	# and converting them into a hash sum
	#
	# - Altering the file at /etc/2web/forceRescan.cfg will force all sums to change
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# force rescan flag will be checked, when force rescan is set the
	# date in seconds is wrote to the force rescan file. this will change the
	# checksums for all directories across all modules
	# - You can also write anything to this file by hand to force a rescan
	if test -f /etc/2web/forceRescan.cfg;then
		totalList="$totalList$(cat /etc/2web/forceRescan.cfg)"
	fi
	# convert lists into a sum
	tempLibList="$(echo -n "$totalList" | sha512sum | cut -d' ' -f1)"
	# write the sum to stdout
	echo "$tempLibList"
}
########################################################################
function checkDirSum(){
	# return true if the directory has been updated/changed
	# - use setDirSum to mark as finished, meaning this should be in a if statement
	#   and the end of the if statement you sould put a setDirSum with the same arguments
	# store sums in $webdirectory/$sums
	webDirectory=$1
	directory=$2
	# generate the module name
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	# check the sum of a directory and compare it to a previously stored sum
	if ! test -d "$webDirectory/sums/";then
		mkdir -p "$webDirectory/sums/"
	fi
	pathSum="$(echo "$directory" | sha512sum | cut -d' ' -f1 )"
	newSum="$(getDirSum "$directory")"
	# check for a previous sum
	if test -f "$webDirectory/sums/${moduleName}_$pathSum.cfg";then
		oldSum="$(cat "$webDirectory/sums/${moduleName}_$pathSum.cfg")"
		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			#echo "$newSum" > "$webDirectory/sums/nfo_$pathSum.cfg"
			return 0
		fi
	else
		# CHANGED
		# no previous file was found, pass true
		# update the sum
		# the sum should be updated with setDirSum
		#echo "$newSum" > "$webDirectory/sums/nfo_$pathSum.cfg"
		return 0
	fi
}
########################################################################
function setDirSum(){
	# for use with checkdir sum, to update a sum as finished
	webDirectory=$1
	directory=$2

	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)

	pathSum="$(echo "$directory" | sha512sum | cut -d' ' -f1 )"
	newSum="$(getDirSum "$directory")"

	# write the new sum to the file
	echo "$newSum" > "$webDirectory/sums/${moduleName}_$pathSum.cfg"

}
########################################################################
function rmDirSum(){
	# rmDirSum $webDirectory $directory
	#
	#

	# for use with checkdir sum, to remove a existing sum
	webDirectory="$1"
	directory="$2"

	# get the data for the file path
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	pathSum="$(echo "$directory" | sha512sum | cut -d' ' -f1 )"

	# only remove existing hash files
	if test -f "$webDirectory/sums/${moduleName}_$pathSum.cfg";then
		# remove the existing sum file
		rm -v "$webDirectory/sums/${moduleName}_$pathSum.cfg"
		# pipe the return value of rm to the output return value
		return $?
	else
		# return true if the file does not exist in the hash directory
		return 0
	fi
}
########################################################################
function generateWaveform(){
	# Generate a waveform from a audio source
	#
	# - ffmpeg requires downloading the entire file for creating the thumbnail
	# create a thumbnail for the mp3 links inside streams
	#
	# RETURN FILES
	videoPath=$1
	thumbnailPath=$2
	thumbnailPathKodi=$3
	# get the module name
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	thumbSum=$(echo "$videoPath" | md5sum | cut -d' ' -f1)
	# check if the links exist already, and skip if the links are found
	# generate the waveform thumbnail for audio files
	if ! test -f "/var/cache/2web/downloads/thumbnails/$moduleName/$thumbSum-wave.png";then
		createDir /var/cache/2web/downloads/thumbnails/$moduleName/
		# create the log entry to mark a new download
		addToLog "DOWNLOAD" "Generating Thumbnail" "Creating audio waveform using media link: $videoPath"
		ALERT "No waveform file exists, creating one..."
		ffmpeg -loglevel quiet -y -i "$videoPath" -filter_complex "showwavespic=colors=white" -frames:v 1 "/var/cache/2web/downloads/thumbnails/$moduleName/$thumbSum-wave.png"
	fi
	if ! test -s "$thumbnailPath.png";then
		ALERT "Linking generated waveform thumbnail..."
		# and the web thumbnail link
		linkFile "/var/cache/2web/downloads/thumbnails/$moduleName/$thumbSum-wave.png" "$thumbnailPath.png"
	fi
	if ! test -s "$thumbnailPathKodi.png";then
		ALERT "Linking generated waveform kodi thumbnail..."
		# add kodi thumbnail link link
		linkFile "/var/cache/2web/downloads/thumbnails/$moduleName/$thumbSum-wave.png" "$thumbnailPathKodi.png"
	fi
}
########################################################################
function downloadThumbnail(){
	# downloadThumbnail $thumbnailLink $thumbnailPath $thumbnailExt
	#
	# Download the thumbnail and store it in the cache, link the downloaded thumb in the main website
	#
	# - Downloaded thumbnails are stored in $(webRoot)/downloads/$moduleName/
	#
	# RETURN FILES
	thumbnailLink=$1
	thumbnailPath=$2
	thumbnailExt=$3
	sumName=$(echo -n "$thumbnailLink" | sha512sum | cut -d' ' -f1)
	# check for hashes used by sites for tracking, some sites use these for uncool purposes
	if echo -n "$thumbnailLink" | grep -q "#";then
		# remove the hash in the url
		thumbnailLink=$(echo -n "$thumbnailLink" | cut -d'#' -f1)
	fi
	# get the module name
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	# if it dont exist download it
	if ! test -f "/var/cache/2web/downloads/thumbnails/${moduleName}/$sumName$thumbnailExt";then
		# create the download directory if it does not exist
		createDir "/var/cache/2web/downloads/thumbnails/${moduleName}/"
		# generated the sum for the thumbnail name
		timeout 120 curl -L --silent "$thumbnailLink" | convert -quiet - "/var/cache/2web/downloads/thumbnails/${moduleName}/$sumName$thumbnailExt"
		# sleep for one second after each thumbnail download
		#sleep 1
	fi
	if ! test -f "$thumbnailPath$thumbnailExt";then
		linkFile "/var/cache/2web/downloads/thumbnails/${moduleName}/$sumName$thumbnailExt" "$thumbnailPath$thumbnailExt"

		# save the thumbnail to a download path, and link to that downloaded thumbnail
		#curl --silent "$thumbnailLink" | convert -quiet - "$thumbnailPath$thumbnailExt"
	fi
}
########################################################################
function generateThumbnailFromMedia(){
	# generateThumbnailFromMedia $videoPath $thumbnailPath $thumbnailPathKodi
	#
	# Take a source video and generate thumbnails from the first 30% of the video making a thumbnail every 5%.
	#
	# - The largest thumbnail generated will be used as the thumbnail.
	# - Any thumbnail larger than 15000 bytes will end thumbnail generation.
	videoPath=$1
	thumbnailPath=$2
	thumbnailKodi=$3
	# if the downloaded file is blank use mediainfo to determine if it is a video or audio link
	addToLog "DOWNLOAD" "Generating Thumbnail" "Creating video thumbnail using media link: $videoPath"
	# get the name for the active module
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	# get the thumbsum to identify the name of the thumbnail in the generated thumbnails
	thumbSum=$(echo -n "$thumbnailPath" | sha512sum | cut -d' ' -f1)
	# check link type
	if [ "$(echo "$videoPath" | cut -f1-8)" == "https://" ];then
		# check for hashes used by sites for tracking, some sites use these for uncool purposes
		# - This should only be done for URLs
		if echo -n "$videoPath" | grep -q "#";then
			# remove the hash in the url
			videoPath=$(echo -n "$videoPath" | cut -d'#' -f1)
		fi
		# generate the thumbnail directory if it does not exist
		createDir /var/cache/2web/downloads/thumbnails/$moduleName/
		thumbnailCachePath="/var/cache/2web/downloads/thumbnails/${moduleName}/$thumbSum-gen.png"
	elif [ "$(echo "$videoPath" | cut -f1-7)" == "http://" ];then
		# check for hashes used by sites for tracking, some sites use these for uncool purposes
		# - This should only be done for URLs
		if echo -n "$videoPath" | grep -q "#";then
			# remove the hash in the url
			videoPath=$(echo -n "$videoPath" | cut -d'#' -f1)
		fi
		# generate the thumbnail directory if it does not exist
		createDir /var/cache/2web/downloads/thumbnails/$moduleName/
		thumbnailCachePath="/var/cache/2web/downloads/thumbnails/${moduleName}/$thumbSum-gen.png"
	else
		# generate the thumbnail directory if it does not exist
		createDir "/var/cache/2web/downloads/generated/$moduleName/"
		# The path to store the generated thumbnail
		thumbnailCachePath="/var/cache/2web/generated/thumbnails/${moduleName}/$thumbSum-gen.png"
	fi
	# create a temp file to store generated thumbnails
	tempThumbnailCachePath="/var/cache/2web/downloads/thumbnails/${moduleName}/$thumbSum-temp.png"

	# try to generate a thumbnail from video file
	# - filesize of images is directly related to visual complexity

	# stop processing the thumbnail if it is already cached
	if ! test -e "$thumbnailCachePath";then
		# This is the minimum file size for a thumb, anything larger than this will be accepted
		largestFileSize=15000
		#
		largestImage=""
		largestImageSize=0
		tempTimeCode=1
		tempFileSize=0
		#
		while [ $tempFileSize -lt $largestFileSize ];do
			#
			addToLog "DEBUG" "Thumbnail Gen" "Building a thumbnail at '$tempThumbnailCachePath'"
			# use the ffmpeg thumbnailer to build a thumbnail
			ffmpegthumbnailer -t "${tempTimeCode}%" -i "$videoPath" -s 400 -c png -o "$tempThumbnailCachePath"
			# get the size of the file, after it has been created
			if test -e "$tempThumbnailCachePath";then
				tempFileSize=$(wc -c < "$tempThumbnailCachePath" )
			else
				tempFileSize=0
			fi
			# check if this image is larger than the other generated thumbnails
			if [ $tempFileSize -gt $largestImageSize ];then
				addToLog "DEBUG" "Thumbnail Gen" "Larger thumbnail discovered <br>( '$tempFileSize' < 15000 )"
				# copy over the temp thumbnail into the cached thumbnail path
				cp -v "$tempThumbnailCachePath" "$thumbnailCachePath"
				# remove the temp thumbnail
				rm -v "$tempThumbnailCachePath"
			else
				addToLog "DEBUG" "Thumbnail Gen" "Thumbnail is not larger than the minimum size <br>( '$tempFileSize' < 15000 )"
			fi
			# - increment the timecode to get from the video to find a thumbnail that is not
			#   a blank screen
			# - This will create 50 screenshots 500/10 and use the screenshot with the largest
			#   file size
			tempTimeCode=$(( tempTimeCode + 5 ))
			# after checking x seconds for a thumbnail of the thumbs created use the one with
			# the largest file size
			if [ $tempTimeCode -gt 30 ];then
				addToLog "DEBUG" "Thumbnail Gen" "Exceeded length no more thumbnails will attempt to be made"
				# break the loop by breaking the comparison
				tempFileSize=$largestFileSize
				# write the thubmnail data
			fi
		done
	fi
	# link the cached thumbnail to the web path
	addToLog "DEBUG" "Thumbnail Gen" "Generation completed, saving thumbnail '$thumbnailPath.png'"
	linkFile "$thumbnailCachePath" "$thumbnailPath.png"
	# link the thumbnail created to the kodi path
	addToLog "DEBUG" "Thumbnail Gen" "Linking thumbnail to kodi directory '$thumbnailPathKodi.png'"
	linkFile "$thumbnailPath.png" "$thumbnailPathKodi.png"
	#
	#if ! test -f "/var/cache/2web/web/thumbnails/$thumbSum-web.png";then
	#	# create the web page link thumbnail, this is smaller than the kodi thumbnail
	#	convert -quiet "$thumbnailPath.png" -resize "300x200" "/var/cache/2web/web/thumbnails/$thumbSum-web.png"
	#fi
}
########################################################################
function mediaJson(){
	# mediaJson $mediaFilePath
	#
	# Load a media file path and print the json meta data for that media file
	mediaFilePath=$1
	sum=$(echo -n "$mediaFilePath" | sha512sum | cut -d' ' -f1)
	# create the cache directory
	createDir "/var/cache/2web/downloads/mediaInfo/"
	#
	if test -f "/var/cache/2web/downloads/mediaInfo/$sum.json";then
		# read cached mediainfo
		mediaData=$(cat "/var/cache/2web/downloads/mediaInfo/$sum.json")
	else
		# load the mediainfo as json data
		mediaData=$(mediainfo --output=JSON "$mediaFilePath")
		# store the media info
		echo -n "$mediaData" > "/var/cache/2web/downloads/mediaInfo/$sum.json"
	fi
	#
	addToLog "DEBUG" "Media Json" "mediaData = '$mediaData'"
	# store the media info
	echo -n "$mediaData"
}
########################################################################
function popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg" gives you "test.jpg"
	basename "$1"
}
################################################################################
function pickPath(){
	# pop a element from the end of the path, $2 is how far back in the path is pulled
	echo "$1" | rev | cut -d'/' -f$2 | rev
}
########################################################################
function sqliteEscape(){
	printf -v var "%q" "$1"
	echo "$var"
}
########################################################################
function addToLog(){
	# addToLog $logType $description $details
	#
	# Add a entry to the 2web server log
	#
	# - log types can be DEBUG,INFO,WARNING,UPDATE,NEW,ERROR
	# - Unknown log types will not be colored in the server view but will
	#   not cause errors
	# - Description should be short, max 4 words
	# - Details can be as long as you want and should contain any info
	#   that is relevent to the log entry

	# add a entry to the 2web log system
	errorType=$1
	errorDescription=$2
	errorDetails=$3

	#
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)

	# create identifier date to organize the data, this is really accurate
	logIdentifier="$(date "+%s.%N")"
	logDate="$(date "+%D")"
	logTime="$(date "+%R:%S")"

	#
	logDescription=$(echo -e "$errorDescription" | sed "s/'/''/g" )
	#
	logDetails=$(echo -e "$errorDetails" | sed "s/'/''/g" )

	# set the log database path
	indexPath="/var/cache/2web/web/log/log.db"
	databaseTable="log"
	timeout=60000

	# check if the table exists in the database
	if ! sqlite3 -cmd ".timeout $timeout"  "$indexPath" "select name from sqlite_master where type='table';" | grep -q "$databaseTable";then
		# create the database if it does not exist
		# first set the new database into wal mode for better handling of concurrency in the database
		sqlite3 -cmd ".timeout $timeout" "$indexPath" "PRAGMA journal_mode=WAL;"
		sqlite3 -cmd ".timeout $timeout" "$indexPath" "PRAGMA wal_autocheckpoint=20;"
		# create the table
		sqlite3 -cmd ".timeout $timeout" "$indexPath" "create table $databaseTable(logIdentifier text primary key,module,type,description,details,date,time);"
		chown www-data:www-data "$indexPath"
	fi
	# if the data is already stored in the database
	sqlite3 -cmd ".timeout $timeout" "$indexPath" "replace into $databaseTable values('$logIdentifier','$moduleName','$errorType','$logDescription','$logDetails','$logDate','$logTime');"
}
########################################################################
function cleanupLog(){
	# cleanupLog
	#
	# - Max out the log at 5,000 entries so the database does not grow forever
	# - Log is cleaned once every 24 hours
	#
	# - Has no options
	indexPath="/var/cache/2web/web/log/log.db"
	timeout=60000

	# load the config
	if test -f "/etc/2web/maxLogSize.cfg";then
		maxLogEntries="$(cat "/etc/2web/maxLogSize.cfg")"
	else
		# create the default config
		maxLogEntries=5000
		echo "5000" > "/etc/2web/maxLogSize.cfg"
		# set the permissions so the config file can be changed in the web interface
		chown www-data:www-data "/etc/2web/maxLogSize.cfg"
	fi

	# remove old entries in the database but keep last 5,000 entries
	sqlite3 -cmd ".timeout $timeout" "$indexPath" "delete from log where logIdentifier not in ( select logIdentifier from log order by date desc limit $maxLogEntries );"
	# rebuild and shrink the database file
	sqlite3 -cmd ".timeout $timeout" "$indexPath" "vacuum;"
}
########################################################################
function yesNoCfgCheck(){
	# yesNoCfgCheck $configFilePath
	#
	# - Return 0 (true) if config file is set to 'yes'
	# - Return 1 (false) if config is anything other than no or does not exist
	configFilePath="$1"
	# set the default value if none has been set
	if [ "$2" == "yes" ];then
		defaultValue="yes"
	else
		defaultValue="no"
	fi
	if test -f "$configFilePath";then
		# file exists check if it is a yes value
		if grep --quiet --ignore-case "yes" "$configFilePath";then
			return 0
		else
			return 1
		fi
	else
		# write the default config value
		echo -n "$defaultValue" > "$configFilePath"
		# fix permissions so the config can be edited by the web interface
		chown www-data:www-data "$configFilePath"
		# return the no state
		return 1
	fi
}
########################################################################
function yesNoCfgSet(){
	# yesNoCfgSet $configFilePath $value
	#
	# Set value for a yes/no config file
	#
	# - Anything other than yes will be set to no
	configFilePath="$1"
	configValue="$2"
	if echo "$configValue" | grep --quiet --ignore-case "yes";then
		echo "yes" > "$configFilePath"
	else
		echo "no" > "$configFilePath"
	fi
}
########################################################################
function loadConfigs(){
	# load a config from a path, if no config is found copy the default config path to the config
	#
	# $1 = configPath : The path to the config file to load
	# $2 = configDirectory : A path on the system where .cfg files exist that will be combined with the config file
	# $3 = defaultConfigPath : The path to the default config file
	#
	# RETURN OUTPUT
	configPath=$1
	configDirectory=$2
	defaultConfigPath=$3
	# create the default config path for the web page if it does not yet exist
	# - createDir() makes a directory with www-data as the owner for web settings
	createDir "$configDirectory"
	# check for server libary config
	if ! test -f "$configPath";then
		# if no config exists create the default config
		{
			cat "$defaultConfigPath"
		} > "$configPath"
	fi
	# write path to console
	grep -v "^#" "$configPath"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "$configDirectory" -mindepth 1 -maxdepth 1 -type f -name '*.cfg' | while read libraryConfigPath;do
		# load up config without comments
		grep -v "^#" "$libraryConfigPath" | tr -s "\n"
		# create a space just in case none exists
		printf "\n"
	done
}
########################################################################
function demoImage(){
	# demoImage $outputFilePath $title $width $height
	#
	# Generate a generic image with a caption, the background will be generated
	# using the caption as a seed so each image will be visually unique enough
	# for you to reconize them

	# the output path for the image
	localIconPath="$1"
	# the title for captioning the image
	title="$2"
	# image width
	imageWidth="$3"
	# image height
	imageHeight="$4"
	#########################################################################################
	# generate a identifiable hash image from the title
	hashImage "$title" "$localIconPath" "${imageWidth}x${imageHeight}"
	# add text over generated image
	timeout 600 convert "$localIconPath" -adaptive-resize  ${imageWidth}x${imageHeight}\! -background none -font "OpenDyslexic-Bold" -fill white -stroke black -strokewidth 2 -size ${imageWidth}x${imageHeight} -gravity center caption:"$title" -composite "$localIconPath"
}
#########################################################################################
function addPlaylist(){
	#	addPlaylist "/path/to/index/file.index" "groupName" "tagName" "filterName"
	#
	# Add a playlist to the web playlist interface
	#
	# - This will generate all the variations of the playlist filters
	indexFile="$1"
	groupName="$2"
	groupName="$(cleanText "$groupName")"
	tagName="$3"
	tagName="$(cleanText "$tagName")"
	filterName="$4"
	# if the tag is blank mark it as unknown
	if [ "$tagName" == "" ];then
		tagName="unknown"
	fi
	# add the direct filtered list
	addToIndex "$indexFile" "$webDirectory/tags/${groupName}_${tagName}_${filterName}.index"
	# add the group tag and filter type playlist variations to include the all playlist
	addToIndex "$indexFile" "$webDirectory/tags/${groupName}_${tagName}_all.index"
	addToIndex "$indexFile" "$webDirectory/tags/${groupName}_all_all.index"
	addToIndex "$indexFile" "$webDirectory/tags/all_all_all.index"
	addToIndex "$indexFile" "$webDirectory/tags/all_all_${filterName}.index"
	addToIndex "$indexFile" "$webDirectory/tags/all_${tagName}_${filterName}.index"
	# add the middle variation
	addToIndex "$indexFile" "$webDirectory/tags/all_${tagName}_all.index"
}
########################################################################
function compileSearchIndex(){
	# compile together all the search data

	# would like to add that file to a language model
	drawLine
	drawSmallHeader "Looking for New Search Index Data"
	drawLine
	#
	dataDirectories=""
	#
	newData="NO"
	if checkFileDataSum "/var/cache/2web/web" "/var/cache/2web/generated/searchIndexSum.cfg";then
		newData="YES"
		ALERT "The Search Index Sum has changed!\nIt is required that the search data be compiled again in order to use the search index without errors."
	fi
	# if the data has changed rebuild the search index
	if [ "$newData" == "YES" ];then
		# check for search index data for each module section
		if test -d "/var/cache/2web/generated/searchIndexData/shows/";then
			dataDirectories="${dataDirectories}\n$( find "/var/cache/2web/generated/searchIndexData/shows/" -mindepth 1 -maxdepth 1 -type d )"
			ALERT "Found Shows For Search Index"
		fi
		if test -d "/var/cache/2web/generated/searchIndexData/movies/";then
			dataDirectories="${dataDirectories}\n$( find "/var/cache/2web/generated/searchIndexData/movies/" -mindepth 1 -maxdepth 1 -type d )"
			ALERT "Found Movies For Search Index"
		fi
		if test -d "/var/cache/2web/generated/searchIndexData/comics/";then
			dataDirectories="${dataDirectories}\n$( find "/var/cache/2web/generated/searchIndexData/comics/" -mindepth 1 -maxdepth 1 -type d )"
			ALERT "Found Comics For Search Index"
		fi
		if test -d "/var/cache/2web/generated/searchIndexData/music/";then
			dataDirectories="${dataDirectories}\n$( find "/var/cache/2web/generated/searchIndexData/music/" -mindepth 1 -maxdepth 1 -type d )"
			ALERT "Found Music For Search Index"
		fi
		ALERT "$dataDirectories" "Discovered Search Data Paths"
		# remove any existing search index
		delete "/var/cache/2web/generated/searchIndex/"
		# recreate the search index
		createDir "/var/cache/2web/generated/searchIndex/"
		#
		drawLine
		drawSmallHeader "Compile All Search Indexes"
		drawLine
		# count the total
		totalDataDirectories=$( echo -n "$dataDirectories" | wc -l )
		dataDirectoryCount=0
		# read each data directory
		echo -n "$dataDirectories" | while read -r dataDirectory;do
			ALERT "Reading Data from '$dataDirectory'"
			baseDirectoryName=$( basename "$dataDirectory" )
			dataDirectoryCount=$(( $dataDirectoryCount + 1 ))
			addThisFile="NO"
			#
			dataDirectoryFiles=$(find "$dataDirectory/" -type f)
			totalDataDirectoryFiles=$(echo -n "$dataDirectoryFiles" | wc -l )
			dataDirectoryFileCount=0
			# move files in existing data directory into the new model
			echo -n "$dataDirectoryFiles" | while read -r dataFileName;do
				dataDirectoryFileCount=$(( $dataDirectoryFileCount + 1 ))
				baseDataFileName="$(basename "$dataFileName")"
				INFO "Adding file $dataDirectoryCount/$totalDataDirectories and token $dataDirectoryFileCount/$totalDataDirectoryFiles in '$baseDirectoryName' token title is '$baseDataFileName'"
				# add the values from this file to the compiling model
				cat "$dataFileName" >> "/var/cache/2web/generated/searchIndex/$baseDataFileName"
			done
		done
		drawLine
		drawSmallHeader "Search Index Compile Complete"
		drawLine
		#
		setFileDataSum "/var/cache/2web/web" "/var/cache/2web/web/new/shows.cfg"
		setFileDataSum "/var/cache/2web/web" "/var/cache/2web/web/new/movies.cfg"
		setFileDataSum "/var/cache/2web/web" "/var/cache/2web/web/new/music.cfg"
		setFileDataSum "/var/cache/2web/web" "/var/cache/2web/web/new/comics.cfg"

		# update the sum
		date "+%s" > "/var/cache/2web/generated/searchIndexSum.cfg"

		# set the new data sum to prevent this from running again
		setFileDataSum "/var/cache/2web/web" "/var/cache/2web/generated/searchIndexSum.cfg"
	fi
}
########################################################################
function addToSearchIndex(){
	#	addToSearchIndex "$indexFilePath" "$searchData" "$outputPath"
	#
	# Add a index file to the search index. The search data will be tokenized
	# and used. The output path is a prefix used inside the search index data
	# directory.
	#
	# - This will add items to
	#   - /var/cache/2web/generated/searchIndexData/
	#     + This is where the outputPath will be based.
	#     + data in this path will be used to recompile the model after data
	#       is removed.
	#   - /var/cache/2web/generated/searchIndex/
	#     + This is the database used by the search engine
	#     + This is also used for related content widgets
	#
	local indexFile="$1"
	# the data to be searched for indexing
	local searchData="$2"
	outputPath="$3"
	# set default value for blank value
	if [ "$outputPath" == "" ];then
		outputPath="/unknown/unknown/"
	fi
	ALERT "Reading search index path '$outputPath'"
	#
	outputPath="/var/cache/2web/generated/searchIndexData${outputPath}"
	#
	ALERT "Adding to search index at '$outputPath'"
	createDir "$outputPath"
	#
	searchData="$(echo "$searchData" | sed "s/\n/ /g")"
	searchData="$(echo "$searchData" | sed "s/\r/ /g")"
	# remove possessiveness of nouns it complicates the search index
	searchData="$(echo "$searchData" | sed "s/'s/ /g")"
	# convert all words to lowercase for search index simplicity
	searchData="$(echo "$searchData" | tr "[:upper:]" "[:lower:]")"
	#
	searchData="$(cleanText "$searchData")"
	#
	searchData="$(spaceCleanedText "$searchData")"
	# scan the search data for words to build into the index
	IFSBACKUP=$IFS
	IFS=" "
	#
	for word in $searchData;do
		#
		local cleanWord="$word"
		# do not add groups of ONLY numbers into the search index
		if echo "$cleanWord" | grep -q "[[:digit:]]";then
			if echo "$cleanWord" | grep -q "[[:alpha:]]";then
				# contains digits and characters add it to the word index
				addToIndex "$indexFile" "${outputPath}${cleanWord}.index"
				addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${cleanWord}.index"
			elif echo "$cleanWord" | grep -q "[[:punct:]]";then
				# contains punctuation and digits so add it to the word index
				addToIndex "$indexFile" "${outputPath}${cleanWord}.index"
				addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${cleanWord}.index"
			else
				# if the word is only digits include it in the word index only if it is below 1000
				if [ "$cleanWord" -lt 1000 ];then
					# for each word build a index
					addToIndex "$indexFile" "${outputPath}${cleanWord}.index"
					addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${cleanWord}.index"
				fi
			fi
		else
			# add a normal word
			addToIndex "$indexFile" "${outputPath}${cleanWord}.index"
			addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${cleanWord}.index"
		fi
	done
	# create the relational LLM, also used for search results
	for word1 in $searchData;do
		for word2 in $searchData;do
			# if the word pair is in the data add it to the index
			if echo "$searchData" | grep -q "${word1} ${word2}";then
				#
				if echo "${word1} ${word2}" | grep -q "[[:digit:]]";then
					if echo "${word1} ${word2}" | grep -q "[[:alpha:]]";then
						addToIndex "$indexFile" "${outputPath}${word1} ${word2}.index"
						addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${word1} ${word2}.index"
					elif echo "${word1} ${word2}" | grep -q "[[:punct:]]";then
						addToIndex "$indexFile" "${outputPath}${word1} ${word2}.index"
						addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${word1} ${word2}.index"
					else
						if [ "${word1}" -lt 1000 ];then
							addToIndex "$indexFile" "${outputPath}${word1} ${word2}.index"
							addToIndex "$indexFile" "/var/cache/2web/generated/searchIndex/${word1} ${word2}.index"
						fi
					fi
				fi
				#
				#addToIndex "$indexFile" "${outputPath}${word1} ${word2}.index"
			fi
		done
	done
	#
	IFS=$IFSBACKUP
}
########################################################################
function removeSectionFromSearchIndex(){
	# removeSectionFromSearchIndex $sectionName
	#
	# remove a section of entries from the search index
	#
	# - movies
	# - shows
	# - music
	# - comics
	# - live
	# - portal
	# - weather
	# - applications
	# - repos
	#
	sectionName="$1"
	# setup for multi threading otherwise this will take forever
	totalCPUS=$(cpuCount)
	# remove the entries from the search index
	searchIndexFiles=$(find "/var/cache/2web/generated/searchIndex/" -type f )
	#
	searchIndexLength=$(echo -n "$searchIndexFiles" | wc -l)
	searchIndexCounter=0
	echo "$searchIndexFiles" | while read -r filePath;do
		cleanFileName="$(basename "$filePath")"
		#
		removeFromSearchIndexFile "$filePath" "^/var/cache/2web/web/$sectionName/" &
		waitQueue 0.2 "$totalCPUS"
		#
		searchIndexCounter=$(( $searchIndexCounter + 1 ))
		INFO "Cleaning Search Index ${searchIndexCounter}/${searchIndexLength} '${cleanFileName}'"
	done
	#
	blockQueue 1
}
########################################################################
function verifySearchIndex(){
	# Verify all entries in the search index
	#
	# setup for multi threading otherwise this will take forever
	totalCPUS=$(cpuCount)

	# load each word index and sort out the paths that do not exist

	# remove the entries from the search index
	searchIndexFiles=$(find "/var/cache/2web/generated/searchIndex/" -type f )
	# load the all index from random because it contains everything
	allMediaIndex=$( cat "/var/cache/2web/web/random/all.index" )
	#
	searchIndexLength=$(echo -n "$searchIndexFiles" | wc -l)
	#
	mediaIndexLength=$(echo -n "$allMediaIndex" | wc -l)
	#
	totalEntries=$(( $mediaIndexLength * $searchIndexLength ))
	#
	searchIndexCounter=0
	#
	tempIndexFiles=""
	# search for entries in the search index itself
	echo "$searchIndexFiles" | while read -r indexFilePath;do
		if test -f "$indexFilePath";then
			INFO "[${searchIndexCounter}/${searchIndexLength}] Adding index file for scanning '$indexFilePath'"
			#
			tempIndexData="$(cat "$indexFilePath")"
			#
			#tempIndexFiles="$(echo "${tempIndexFiles}\n$tempIndexData" | sort -u )"
			tempIndexFiles="$(echo "${tempIndexFiles}\n$tempIndexData" )"
		else
			ERROR "The search index file '$indexFilePath' could not be opened!"
		fi
		searchIndexCounter=$(( $searchIndexCounter + 1 ))
	done
	# do a final sort
	allMediaIndex="$(echo "${tempIndexFiles}\n$allMediaIndex" | sort -u )"
	#allMediaIndex="$(echo "$allMediaIndex" | sort -u )"
	searchIndexCounter=0
	# read and check media paths are correct
	echo "$allMediaIndex" | while read -r mediaFilePath;do
		cleanMediaFilePath="$(basename "$mediaFilePath")"
		#
		INFO "Verifying Search Index ${searchIndexCounter}/${totalEntries} for '$cleanMediaFilePath'"
		# if the media path does not exist
		if ! test -f "$mediaFilePath";then
			# remove the media from all word indexes in the search index
			echo "$searchIndexFiles" | while read -r indexFilePath;do
				#
				cleanFileName="$(basename "$mediaFilePath")"
				INFO "Verifying Search Index ${searchIndexCounter}/${totalEntries} for '$cleanMediaFilePath' in '${cleanFileName}'"
				#
				removeFromSearchIndexFile "$indexFilePath" "^$mediaFilePath" &
				waitQueue 0.2 "$totalCPUS"
				#
				searchIndexCounter=$(( $searchIndexCounter + 1 ))
			done
		else
			searchIndexCounter=$(( $searchIndexCounter + $searchIndexLength ))
		fi
	done
	blockQueue 1
}
########################################################################
function removeFromSearchIndexFile(){
	# removeFromSearchIndexFile $removalSearchQuery
	#
	# Remove all lines from a index file containing the $removalSearchQuery
	#
	filePath="$1"
	removalSearchQuery="$2"
	# cleanup the file and store the result
	tempCleanFile="$( cat "$filePath" | grep --invert-match "$removalSearchQuery" )"
	# write the result
	echo "$tempCleanFile" > "$filePath"
}
########################################################################
function loadSearchIndexResults(){
	# loadSearchIndexResults $searchQuery
	#
	# Output the results of a search query to the search index
	#
	local searchQuery="$1"
	addToLog "DEBUG" "Fuzzy Search" "<p>Search Query</p><pre>${searchQuery}</pre>"
	# cleanup the query
	searchQuery="$(echo -n "$searchQuery" | sed "s/'s//g")"
	searchQuery="$(echo -n "$searchQuery" | sed "s/＇s//g")"
	searchQuery="$(cleanText "$searchQuery")"
	addToLog "DEBUG" "Fuzzy Search" "<p>Search Query Post Cleanup</p><pre>${searchQuery}</pre>"
	# add spaces around special characters to make the search index not care about "query" vs "query?"
	searchQuery="$(spaceCleanedText "$searchQuery")"
	addToLog "DEBUG" "Fuzzy Search" "<p>Search Query Post Spacing</p><pre>${searchQuery}</pre>"
	#
	allIndex=""
	IFSBACKUP=$IFS
	IFS=" "
	for word in $searchQuery;do
		cleanWord=$(echo -n "$word" | tr "[:upper:]" "[:lower:]")
		if test -f "/var/cache/2web/generated/searchIndex/${cleanWord}.index";then
			# load the index for each word
			allIndex="${allIndex}\n$(cat "/var/cache/2web/generated/searchIndex/${cleanWord}.index")"
		fi
		# load the word association index results
		for secondWord in $searchQuery;do
			secondCleanWord=$(echo -n "$secondWord" | tr "[:upper:]" "[:lower:]")
			if echo -n "$searchQuery" | grep -q "${cleanWord} ${secondCleanWord}";then
				if test -f "/var/cache/2web/generated/searchIndex/${cleanWord} ${secondCleanWord}.index";then
					allIndex="${allIndex}\n$(cat "/var/cache/2web/generated/searchIndex/${cleanWord} ${secondCleanWord}.index")"
				fi
			fi
		done
	done
	IFS=$IFSBACKUP
	addToLog "DEBUG" "Fuzzy Search" "<p>Index Pre Sort</p><pre>${allIndex}</pre>"
	# sort the all index
	allIndex="$(echo -e "$allIndex" | sort )"
	# count the unique items
	allIndex="$(echo "$allIndex" | uniq -c )"
	# sort in reverse smallest numbers are listed at the top of the sort
	allIndex="$(echo "$allIndex" | sort -nr )"
	# remove leading whitespace
	allIndex="$(echo "$allIndex" | sed "s/^[ \t]*//g" )"
	#
	addToLog "DEBUG" "Fuzzy Search" "<p>Index Post Sort</p><pre>${allIndex}</pre>"
	#
	allIndex="$(echo "$allIndex" | cut -d' ' -f2- )"
	#	output the index
	echo "$allIndex"
}
########################################################################
function randomWord(){
	# randomWord
	#
	# Generate a random word from the dict server
	#
	# - Has no options
	# - Uses words from dictonarys installed for 'dict' command
	shuf -n 1 /usr/share/dict/words
}
########################################################################
function prefixZeros(){
	# prefixZeros $number
	#
	# Prefix a number with zeros to maintain a width of 4 characters
	#
	# - Will keep numbers 4 characters wide by prefixing small numbers
	#   with leading zeros
	# - Numbers that are to large will be returned unchanged, without any
	#   leading zeros

	# convert to a number
	unformatedNumber="$(( $1 ))"
	if test -z $unformatedNumber;then
		addToLog "ERROR" "Incorrect Input" "A blank string was given to prefixZeros() number is defaulting to zero"
		# this is a broken number
		formatedNumber="$(( 0 ))"
	fi
	# figure out the prefix amount based on the value
	if [ "$(( 10#$unformatedNumber ))" -le 0 ];then
		formatedNumber="0000"
	elif [ "$(( 10#$unformatedNumber ))" -lt 10 ];then
		# add a zero to make it format correctly
		formatedNumber="000$unformatedNumber"
	elif [ "$(( 10#$unformatedNumber ))" -lt 100 ];then
		# add a zero to make it format correctly
		formatedNumber="00$unformatedNumber"
	elif [  "$(( 10#$unformatedNumber ))" -lt 1000 ];then
		formatedNumber="0$unformatedNumber"
	elif [ "$(( 10#$unformatedNumber ))" -ge 1000 ];then
		formatedNumber="$unformatedNumber"
	else
		formatedNumber="0000"
	fi
	# return the number formatted as a string
	echo -n "$formatedNumber"
}
########################################################################
function hashImage(){
	# hashImage $inputText $hashSize $outputFilePath $newSize
	#
	# Generate a unique visual repsentation of a input text. Like a hash but for images not text.
	if [ "$1" == "" ];then
		ERROR "No output input text given for hashImage()"
		return
	else
		inputText="$1"
	fi
	# generate a md5 hash of the input text
	baseHash=$(echo -n "$inputText" | md5sum | cut -d' ' -f1)
	if [ "$2" == "" ];then
		ERROR "No output file given for hashImage()"
		return
	else
		outputFilePath="$2"
	fi

	if [ "$3" == "" ];then
		# default resize size
		newSize="16x16"
	else
		newSize="$3"
	fi

	if [ "$4" == "" ];then
		# default hash size
		hashSize=5
	else
		hashSize=$4
	fi

	# create a transparent background
	backgroundColor="transparent"

	# create foreground color from base hash
	# - split the hash in three
	foregroundColor="#$(echo -n "$baseHash" | cut -c1-6 )"

	# get the swirl amount from the base hash
	swirlAmount=$(( ( 0x${baseHash} ) % 60 ))

	# get the link color from the base hash
	linkColor="#$(echo -n "$baseHash" | cut -c7-12 )"
	# generate a plasma background under the image
	timeout 600 convert -size $((${hashSize} * 4))x$((${hashSize} * 4)) -seed "$(( 0x${baseHash} ))" plasma: -swirl "$swirlAmount" "$outputFilePath"
	# grayscale
	convert "$outputFilePath" -colorspace gray "$outputFilePath"
	# colorize the pattern
	convert "$outputFilePath" -fill "$linkColor" -colorize 40 "$outputFilePath"

	# build the icon by drawing pixels on a grid and resizing the image
	for (( index=0; index<${hashSize}; index++ ));do
		for (( index2=0; index2<${hashSize}; index2++ ));do
			# generate the hash from the value
			tempHash=$(echo -n "$baseHash$index$index2" | md5sum | cut -d' ' -f1)
			if [ $(( ( 0x${tempHash} ) % 2 )) -eq 1 ];then
				# read the character in the hash and modulus that number by 1 to generate a 0 or 1
				convert "$outputFilePath" -background transparent -size ${hashSize}x${hashSize} xc:$backgroundColor -fill $foregroundColor -draw "point ${index},${index2}" -filter point -resize $newSize -layers flatten "$outputFilePath"
			fi
		done
	done

	# mirror the image around the center
	convert "$outputFilePath" -background transparent -extent 200%x100% -flop +clone -composite -background transparent -extent 100%x200% -flip +clone -composite "$outputFilePath"
}
########################################################################
function sleepSpinner(){
	# sleepSpinner $animationName $animationTime
	#
	# This will create the spinner using the selected animation in a time
	#
	# Animation name can be any of the preset themes
	# - spin_white_square
	# - spin_white_circle
	# - spin_block
	# - spin_sextant
	# - spin_triangles
	# - spin_checkers
	# - spin_circle_edges
	# - spin_box_split_fill
	# - spin_box_cross_fill
	# - fast_circle_cross
	# - other_left_right_parallel_opposite_arrows
	# - other_left_right_opposite_arrows
	# - other_left_right_opposite_arrows_wall
	# - other_up_down_opposite_arrows
	# - fill_empty_blocks
	# - fill_empty_diamond
	# - greater_than
	#
	#	The animation delay is measured in seconds. Decimals can be used. ex) 0.1, 1, 2.4, 0.02
	#
	#	The default animation delay is 0.1 seconds.
	#
	sleepTime="$1"
	spinnerName="$1"
	#animationName="$2"
	#delayTime="$3"
	# set the default animation
	if [ "$animationName" == "" ];then
		animationName="spin_split_fill"
	fi

	if [ "$delayTime" == "" ];then
		delayTime="0.1"
	fi

	if [ "$animationName" == "spin_white_square" ];then
		# spinner animations
		animation="◳◲◱◰"
	elif [ "$animationName" == "spin_white_circle" ];then
		animation="◷◶◵◴"
	elif [ "$animationName" == "spin_block" ];then
		animation="▝▗▖▘"
	elif [ "$animationName" == "spin_sextant" ];then
		animation="🬁🬇🬞🬏🬃🬀"
	elif [ "$animationName" == "spin_triangles" ];then
		animation="▴▸▾◂"
	elif [ "$animationName" == "spin_checkers" ];then
		animation="⛀⛁⛃⛂"
	elif [ "$animationName" == "spin_circle_edges" ];then
		animation="◝◞◟◜"
	elif [ "$animationName" == "spin_split_fill" ];then
		animation="⬔◨◪⬓⬕◧◩⬒"
	elif [ "$animationName" == "spin_box_split_fill" ];then
		animation="◨⬓◧⬒"
	elif [ "$animationName" == "spin_box_cross_fill" ];then
		animation="⬔◪⬕◩"
	elif [ "$animationName" == "fast_circle_cross" ];then
		# fast spin animations
		animation="⊗⊕"
	elif [ "$animationName" == "other_left_right_parallel_opposite_arrows" ];then
		# other loop animations
		animation="⇆⇇⇄⇉"
	elif [ "$animationName" == "other_left_right_opposite_arrows" ];then
		animation="⇆⇄"
	elif [ "$animationName" == "other_left_right_opposite_arrows_wall" ];then
		animation="↹↹"
	elif [ "$animationName" == "other_up_down_opposite_arrows" ];then
		animation="⇅⇵"
	elif [ "$animationName" == "fill_empty_blocks" ];then
		animation="▝▐▟█▙▌▘ "
	elif [ "$animationName" == "fill_empty_diamond" ];then
		animation="🮡🮥🮪🮮🮫🮤🮠 "
	elif [ "$animationName" == "greater_than" ];then
		animation=">≫⋙"
	fi

	# store the color codes for coloring
	resetCode="\033[0m"
	#resetCode=$(resetColor)
	# green
	colorCode="\033[0;32m"
	#colorCode=$(greenText)
	#
	spinnerPositionX=$(tput cols)
	spinnerPositionY=$(tput lines)
	#
	animationLength=${#animation}
	#echo -ne "\b"
	# animate the spinner
	# loop though the animation once and exit
	for (( index=0;index<$animationLength;index++ ));do
		# find the bottom right corner of the terminal
		spinnerPositionX="$(tput cols)"
		spinnerPositionY="$(tput lines)"
		# check the global flag used to draw to the terminal without interacting with the spinner
		#if [ "$SHOW_SPINNER" == "yes" ];then
		#if test -f "/var/cache/2web/ram/$spinnerName/showSpinner.cfg";then
			# move the cursor to the bottom right position of the terminal
			tput cup "$spinnerPositionY" "$spinnerPositionX"
			# draw the spinner at the current animation step
			echo -ne "${colorCode}"
			echo -ne "\b${animation:$index:1}"
			echo -ne "${resetCode}"
			# move the cursor back to the start of the line
			tput cup "$spinnerPositionY" "0"
		#fi
		# use a consistant sleep time to make the animation play smoothly
		sleep $delayTime
	done
}
########################################################################
function rotateSpinner(){
	# rotateSpinner $animationName $animationDelayTime
	#
	# It is recommended to use the startSpinner() function to launch the spinner.
	#
	# This will create the spinner using the selected animation
	#
	# Animation name can be any of the preset themes
	# - spin_white_square
	# - spin_white_circle
	# - spin_block
	# - spin_sextant
	# - spin_triangles
	# - spin_checkers
	# - spin_circle_edges
	# - spin_box_split_fill
	# - spin_box_cross_fill
	# - fast_circle_cross
	# - other_left_right_parallel_opposite_arrows
	# - other_left_right_opposite_arrows
	# - other_left_right_opposite_arrows_wall
	# - other_up_down_opposite_arrows
	# - fill_empty_blocks
	# - fill_empty_diamond
	# - greater_than
	#
	#	The animation delay is measured in seconds. Decimals can be used. ex) 0.1, 1, 2.4, 0.02
	#
	#	The default animation delay is 0.1 seconds.
	#
	spinnerName="$1"
	animationName="$2"
	delayTime="$3"
	# set the default animation
	if [ "$animationName" == "" ];then
		animationName="spin_split_fill"
	fi
	if [ "$delayTime" == "" ];then
		delayTime="0.1"
	fi
	if [ "$animationName" == "spin_white_square" ];then
		# spinner animations
		animation="◳◲◱◰"
	elif [ "$animationName" == "spin_white_circle" ];then
		animation="◷◶◵◴"
	elif [ "$animationName" == "spin_block" ];then
		animation="▝▗▖▘"
	elif [ "$animationName" == "spin_sextant" ];then
		animation="🬁🬇🬞🬏🬃🬀"
	elif [ "$animationName" == "spin_triangles" ];then
		animation="▴▸▾◂"
	elif [ "$animationName" == "spin_checkers" ];then
		animation="⛀⛁⛃⛂"
	elif [ "$animationName" == "spin_circle_edges" ];then
		animation="◝◞◟◜"
	elif [ "$animationName" == "spin_split_fill" ];then
		animation="⬔◨◪⬓⬕◧◩⬒"
	elif [ "$animationName" == "spin_box_split_fill" ];then
		animation="◨⬓◧⬒"
	elif [ "$animationName" == "spin_box_cross_fill" ];then
		animation="⬔◪⬕◩"
	elif [ "$animationName" == "fast_circle_cross" ];then
		# fast spin animations
		animation="⊗⊕"
	elif [ "$animationName" == "other_left_right_parallel_opposite_arrows" ];then
		# other loop animations
		animation="⇆⇇⇄⇉"
	elif [ "$animationName" == "other_left_right_opposite_arrows" ];then
		animation="⇆⇄"
	elif [ "$animationName" == "other_left_right_opposite_arrows_wall" ];then
		animation="↹↹"
	elif [ "$animationName" == "other_up_down_opposite_arrows" ];then
		animation="⇅⇵"
	elif [ "$animationName" == "fill_empty_blocks" ];then
		animation="▝▐▟█▙▌▘ "
	elif [ "$animationName" == "fill_empty_diamond" ];then
		animation="🮡🮥🮪🮮🮫🮤🮠 "
	elif [ "$animationName" == "greater_than" ];then
		animation=">≫⋙"
	else
		# draw the default spinner
		animation="🬁🬇🬞🬏🬃🬀"
	fi
	#
	spinnerPositionX=$(tput cols)
	spinnerPositionY=$(tput lines)
	#
	animationLength=${#animation}
	echo -ne "\b"
	# animate the spinner
	while [ "yes" == "yes" ];do
		# check the position after each animation loop
		spinnerPositionX="$(tput cols)"
		spinnerPositionY="$(tput lines)"
		# loop though the animation
		for (( index=0;index<$animationLength;index++ ));do
			# check the global flag used to draw to the terminal without interacting with the spinner
			#if [ "$SHOW_SPINNER" == "yes" ];then
			#if test -f "/var/cache/2web/ram/$spinnerName/showSpinner.cfg";then
				# move the cursor to the bottom right position of the terminal
				tput cup "$spinnerPositionY" "$spinnerPositionX"
				# draw the spinner at the current animation step
				#echo -ne "${animation:$index:1}\b"
				# delete the existing character and draw the spinner
				#echo -ne "\b\b\b ${animation:$index:1} "
				echo -ne "\b${animation:$index:1}"
				# move the cursor back to the start of the line
				tput cup "$spinnerPositionY" "0"
			#fi
			# use a consistant sleep time to make the animation play smoothly
			sleep $delayTime
		done
		#spinnerPositionX="$(tput cols)"
		#spinnerPositionY="$(tput lines)"
		# cleanup the right column
		#for (( index=0;index<$spinnerPositionY;index++ ));do
		#	# position the cursor
		#	tput cup "$index" "$spinnerPositionX"
		#	# blank out the character
		#	echo -ne " "
		#done
	done
}
########################################################################
function startSpinner(){
	# startSpinner
	#
	# Launches rotateSpinner in a background process you can give a animation name and animation delay to this function and it will be passed into rotateSpinner()
	#
	# Stores the process id of the spinner background process so stopSpinner() can stop the background process later in a script.
	#

	# disable cursor blinking while spinner is active
	tput civis
	# start the animated spinner
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)
	# - pass the spinner theme and animation delay time
	rotateSpinner "$moduleName" "$2" "$3" &
	# store the PID of the spinner
	SPINNER_PID=$!
	#
	echo -n "$SPINNER_PID"
	return $SPINNER_PID
}
########################################################################
function stopSpinner(){
	# stopSpinner $SPINNER_PID
	#
	# pass the spinner PID from the return value of startSpinner()
	#
	SPINNER_PID="$1"
	# stop a already running spinner animation
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)

	# enable cursor blinking on stop
	tput cnorm

	# stop the animated spinner
	kill "$SPINNER_PID" || INFO "No Spinner could be stopped..."
}
########################################################################
function loadOption(){
	# loadOption "$optionName" "$input"
	#
	# Load a option from the input usually done at the top level of a script
	#
	# - Normally you would use $@ as the input
	# - This will detect --optionName and can be used to store the true or
	#   false value
	#
	option="$1"
	input="$2"
	# load a option
	if echo "$input" | grep -q -e "--${option}";then
		# load the option
		echo "yes"
	else
		# load the default of no
		echo "no"
	fi
}
########################################################################
