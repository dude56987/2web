#! /bin/bash
########################################################################
# 2web function library
# Copyright (C) 2023  Carl J Smith
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
function createDir(){
	if ! test -d "$1";then
		mkdir -p "$1"
		# set ownership of directory and subdirectories as www-data
		chown -R www-data:www-data "$1"
	fi
	chown www-data:www-data "$1"
}
########################################################################
function webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/2web/web.cfg ];then
		webDirectory=$(cat /etc/2web/web.cfg)
	else
		chown -R www-data:www-data "/var/cache/2web/web_cache/"
		echo "/var/cache/2web/web_cache/" > /etc/2web/web.cfg
		webDirectory="/var/cache/2web/web_cache/"
	fi
	# check for a trailing slash appended to the path
	if [ "$(echo "$webDirectory" | rev | cut -b 1)" == "/" ];then
		# rip the last byte off the string and return the correct path, WITHOUT THE TRAILING SLASH
		webDirectory="$(echo "$webDirectory" | rev | cut -b 2- | rev )"
	fi
	echo "$webDirectory"
}
########################################################################
function downloadRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/2web/download.cfg ];then
		webDirectory=$(cat /etc/2web/download.cfg)
	else
		chown -R www-data:www-data "/var/cache/2web/downloads_cache/"
		echo "/var/cache/2web/downloads_cache/" > /etc/2web/download.cfg
		webDirectory="/var/cache/2web/downloads_cache/"
	fi
	# check for a trailing slash appended to the path
	if [ "$(echo "$webDirectory" | rev | cut -b 1)" == "/" ];then
		# rip the last byte off the string and return the correct path, WITHOUT THE TRAILING SLASH
		webDirectory="$(echo "$webDirectory" | rev | cut -b 2- | rev )"
	fi
	echo "$webDirectory"
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

	webDirectory=$1

	INFO "Building home page..."
	# link homepage
	linkFile "/usr/share/2web/templates/home.php" "$webDirectory/index.php"

	# check and update stats files
	# - do not generate stats if website is in process of being updated
	# - stats generation is IO intense, so it only needs ran ONCE at the end
	# - each element in the stats is ran on a diffrent schedule based on its intensity and propensity to lock up the system
	# - update the "last update on" data every run, this is simply to show the freshness of the content since updates are a batch process
	echo "$(date)" > "$webDirectory/lastUpdate.index"
	# set the timeout
	timeout=60000
	databasePath="$(webRoot)/data.db"
	if test -d "$webDirectory/comics/";then
		if cacheCheck "$webDirectory/totalComics.index" "1";then
			# get the count stats from SQL database
			totalComics=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_comics\";")
			echo "$totalComics" > "$webDirectory/totalComics.index"
		fi
	fi
	if test -d "$webDirectory/music";then
		if cacheCheck "$webDirectory/totalTracks.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_tracks\";")
			echo "$totalEpisodes" > "$webDirectory/totalTracks.index"
		fi
		if cacheCheck "$webDirectory/totalArtists.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_artists\";")
			echo "$totalEpisodes" > "$webDirectory/totalArtists.index"
		fi
		if cacheCheck "$webDirectory/totalAlbums.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_albums\";")
			echo "$totalEpisodes" > "$webDirectory/totalAlbums.index"
		fi
	fi
	if test -d "$webDirectory/shows/";then
		if cacheCheck "$webDirectory/totalEpisodes.index" "1";then
			totalEpisodes=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_episodes\";")
			echo "$totalEpisodes" > "$webDirectory/totalEpisodes.index"
		fi
		if cacheCheck "$webDirectory/totalShows.index" "1";then
			totalShows=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_shows\";")
			echo "$totalShows" > "$webDirectory/totalShows.index"
		fi
	fi
	if cacheCheck "$webDirectory/totalMovies.index" "1";then
		totalMovies=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_movies\";")
		echo "$totalMovies" > "$webDirectory/totalMovies.index"
	fi
	if cacheCheck "$webDirectory/totalWikis.index" "1";then
		totalWikis=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_wikis\";")
		echo "$totalWikis" > "$webDirectory/totalWikis.index"
	fi
	if cacheCheck "$webDirectory/totalRepos.index" "1";then
		totalRepos=$(sqlite3 -cmd ".timeout $timeout" "$databasePath" "select COUNT(*) from \"_repos\";")
		echo "$totalRepos" > "$webDirectory/totalRepos.index"
	fi
	if cacheCheck "$webDirectory/totalWeather.index" "1";then
		totalWeather=$(find "$webDirectory/weather/data/" -name "station_*.index" | wc -l)
		echo "$totalWeather" > "$webDirectory/totalWeather.index"
	fi
	#
	if cacheCheck "$webDirectory/totalWiki.index" "1";then
		totalWeather=$(cat "$webDirectory/wiki/wikis.index" | wc -l)
		echo "$totalWeather" > "$webDirectory/totalWiki.index"
	fi
	if test -f "$webDirectory/live/channels.m3u";then
		if cacheCheck "$webDirectory/totalChannels.index" "7";then
			totalChannels=$(grep -c 'radio="false' "$webDirectory/kodi/channels.m3u" )
			echo "$totalChannels" > "$webDirectory/totalChannels.index"
		fi
		if cacheCheck "$webDirectory/totalRadio.index" "7";then
			totalRadio=$(grep -c 'radio="true' "$webDirectory/kodi/channels.m3u" )
			echo "$totalRadio" > "$webDirectory/totalRadio.index"
		fi
	fi
	# Run filesystem size checks for stats
	if cacheCheck "$webDirectory/webSize.index" "1";then
		# count website size in total ignoring symlinks
		webSize=$(du -shP "$webDirectory" | cut -f1)
		echo "$webSize" > "$webDirectory/webSize.index"
	fi
	if cacheCheck "$webDirectory/webThumbSize.index" "1";then
		# count website thumbnail size in total ignoring symlinks
		webThumbSize=$(du -shP "$webDirectory/thumbnails/" | cut -f1)
		echo "$webThumbSize" > "$webDirectory/webThumbSize.index"
	fi
	if cacheCheck "$webDirectory/cacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/RESOLVER-CACHE/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/cacheSize.index"
	fi
	if cacheCheck "$webDirectory/repoGenSize.index" "1";then
		repoGenSize=$(du -shP "$webDirectory/repos/" | cut -f1)
		echo "$repoGenSize" > "$webDirectory/repoGenSize.index"
	fi
	if cacheCheck "$webDirectory/mediaSize.index" "1";then
		# count symlinks in kodi to get the total size of all media on all connected drives containing libs
		mediaSize=$(du -shL "$webDirectory/kodi/" | cut -f1)
		echo "$mediaSize" > "$webDirectory/mediaSize.index"
	fi
	if cacheCheck "$webDirectory/freeSpace.index" "1";then
		# count total freespace on all connected drives, ignore temp filesystems (snap packs)
		freeSpace=$(df -h -x "tmpfs" --total | grep "total" | tr -s ' ' | cut -d' ' -f4)
		echo "$freeSpace" > "$webDirectory/freeSpace.index"
	fi
	if cacheCheck "$webDirectory/aiSize.index" "6";then
		# count total size of AI models
		aiSize=$(du -shL "/var/cache/2web/downloads/ai/" | cut -f1)
		echo "$aiSize" > "$webDirectory/aiSize.index"
	fi
	if cacheCheck "$webDirectory/promptAi.index" "1";then
		# the number of prompt ais that are installed on the system
		promptAi=$(find "/var/cache/2web/downloads/ai/prompt/" -name "*.bin" | wc -l)
		echo "$promptAi" > "$webDirectory/promptAi.index"
	fi
	if cacheCheck "$webDirectory/imageAi.index" "1";then
		imageAi=$(find "/var/cache/2web/downloads/ai/txt2img/" -maxdepth 1 -type d -name "models--*" | wc -l)
		echo "$imageAi" > "$webDirectory/imageAi.index"
	fi
	if cacheCheck "$webDirectory/txtGenAi.index" "1";then
		txtGenAi=$(find "/var/cache/2web/downloads/ai/txt2txt/" -maxdepth 1 -type d -name "models--*" | wc -l)
		echo "$txtGenAi" > "$webDirectory/txtGenAi.index"
	fi
	if cacheCheck "$webDirectory/imageEditAi.index" "1";then
		imageEditAi=$(find "/var/cache/2web/downloads/ai/img2img/" -maxdepth 1 -type d -name "models--*" | wc -l)
		echo "$imageEditAi" > "$webDirectory/imageEditAi.index"
	fi
	if cacheCheck "$webDirectory/subAi.index" "1";then
		subAi=$(find "/var/cache/2web/downloads/ai/subtitles/" -type f -name "*.pt" | wc -l)
		echo "$subAi" > "$webDirectory/subAi.index"
	fi
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
function cacheCheckMin(){

	filePath="$1"
	cacheMinutes="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -cmin "+$cacheMinutes") ]];then
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
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
}
########################################################################
function updateCerts(){
	force=$1
	genCert='no'
	# if the cert exists
	# if the certs are older than 364 days renew recreate a new valid key
	if cacheCheck /var/cache/2web/ssl-cert.crt "365";then
		# the cert has expired
		echo "[INFO]: Updating cert..."
		# generate a new private key and public cert for the SSL certification
		genCert='yes'
	else
		echo "[INFO]: Cert still active..."
		return
	fi
	if $force;then
		# force cert generation
		genCert='yes'
	fi
	if [ $genCert == 'yes' ];then
		openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /var/cache/2web/ssl-private.key -out /var/cache/2web/ssl-cert.crt -config /etc/2web/certInfo.cnf
		# convert the ssl certificate into the der format
		# - der format can be copied to other systems at /usr/local/share/ca-certificates/
		# - linux only updates after update-ca-certificates
		openssl x509 -in /var/cache/2web/ssl-cert.crt -out /var/cache/2web/ssl-cert.der -outform DER
		# add the cert to the system cert directory
		ln -s /var/cache/2web/ssl-cert.crt /usr/share/ca-certificates/2web.crt
		# update system cert file
		update-ca-certificates --fresh
	fi
}
########################################################################
function alterArticles(){
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
	# - use system load and free cores to manage queue
	# wait for queue to free up for next command
	sleepTime=$1
	totalCPUS=$2
	if [ $totalCPUS -eq 1 ];then
		# if there is only one cpu use the fastqueue, one at a time, ignore system load
		waitFastQueue $sleepTime 1
		return 0
	fi
	while true;do
		# check if the cpu has any available cores that are unused in this process
		# jobs -r only shows running jobs
		if [ $(jobs -r | wc -l) -ge $totalCPUS ];then
			#INFO "Waiting for free CPU cores..."
			sleep $sleepTime
		else
			# if the load exceeds the number of cpus block the queue, this means the system is maxed out globally
			# - this will make all 2web modules parallel process without blocking each other
			# - this should make the apache server remain available even if all modules are running in parallel
			# convert load into interger and compare
			if [ $(cat /proc/loadavg | cut -d' ' -f1 | cut -d'.' -f1) -gt $(( totalCPUS )) ];then
				INFO "System is overloaded, Waiting for system resources..."
				sleep 1
			else
				break
			fi
		fi
	done
}
################################################################################
function waitSlowQueue(){
	# PARALLEL PROCESSING COMMAND
	# - use only the system load to manage queue
	# - This is dangerous and will heavily tax the system in a uneven way
	# wait for system load to get below the totalCpus
	sleepTime=$1
	totalCPUS=$2
	while true;do
		if [ $(cat /proc/loadavg | cut -d' ' -f1 | cut -d'.' -f1) -gt $(( totalCPUS )) ];then
			INFO "System is overloaded, Waiting for system resources..."
			sleep 1
		else
			break
		fi
	done
}
################################################################################
function waitFastQueue(){
	# PARALLEL PROCESSING COMMAND
	# - use only the free cpu cores to manage the queue
	# wait for queue to free up for next command
	sleepTime=$1
	totalCPUS=$2
	while true;do
		# check if the cpu has any available cores that are unused in this process
		# jobs -r only shows running jobs
		if [ $(jobs -r | wc -l) -ge $totalCPUS ];then
			#INFO "Waiting for free CPU cores..."
			sleep $sleepTime
		else
			# if the load exceeds the number of cpus block the queue, this means the system is maxed out globally
			# - this will make all 2web modules parallel process without blocking each other
			# - this should make the apache server remain available even if all modules are running in parallel
			# convert load into interger and compare
			break
		fi
	done
}
################################################################################
function blockQueue(){
	# PARALLEL PROCESSING COMMAND
	# wait for all jobs in queue to finish
	sleepTime=$1
	while true;do
		if [ $(jobs -r | wc -l) -gt 0 ];then
			sleep $sleepTime
		else
			break
		fi
	done
}
################################################################################
function ALERT(){
	echo "$1";
	echo
}
################################################################################
function startDebug(){
	echo
	echo "################################################################################"
	echo "#                              START DEBUG BLOCK                               #"
	echo "################################################################################"
	echo
	set -x
}
################################################################################
function stopDebug(){
	set +x
	echo
	echo "################################################################################"
	echo "#                               STOP DEBUG BLOCK                               #"
	echo "################################################################################"
	echo
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
function ERROR(){
	width=$(tput cols)
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[ERROR]: $1$buffer" | tail -n 1 | cut -b"1-$(( $width - 1 ))" )"
	# print the line
	echo "################################################################################"
	echo "$output"
	echo "################################################################################"
}
################################################################################
function lockProc(){
	procName=$1
	webDirectory=$(webRoot)
	ALERT "$webDirectory/${procName}.active"
	if test -f "$webDirectory/${procName}.active";then
		# system is already running exit
		echo "[INFO]: ${procName} is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '$webDirectory/${procName}.active'."
		exit
	else
		ALERT "Setting Active Flag $webDirectory/${procName}.active"
		# set the active flag
		touch "$webDirectory/${procName}.active"
		touch "$webDirectory/${procName}.activeGraph"
		ALERT "Setting Active Trap $webDirectory/${procName}.active"
		# create a trap to remove module lockfile
		trap "rm $webDirectory/${procName}.active" EXIT
	fi
}
################################################################################
function returnModStatus(){
	moduleName="$1"
	# foreground color codes
	redFG="\033[38;5;9m"
	greenFG="\033[38;5;10m"
	# reset all color code
	resetTerm="\033[0m"
	# the config exists check the config
	if test -f "/etc/2web/mod_status/${moduleName}.cfg";then
		if grep -q "enabled" "/etc/2web/mod_status/${moduleName}.cfg";then
			echo -e "MOD $moduleName IS ${greenFG}ENABLED${resetTerm}!"
			return 0
		else
			echo -e "MOD $moduleName IS ${redFG}DISABLED${resetTerm}!"
			return 1
		fi
	else
		echo -e "MOD $moduleName IS ${redFG}DISABLED${resetTerm}!"
		return 1
	fi
}
################################################################################
function checkModStatus(){
	# check the status of a mod
	# if the mod is enabled allow the module to keep running
	# if the mod is disabled run the nuke command within that module

	moduleName=$1

	# check the mod status
	if test -f "/etc/2web/mod_status/${moduleName}.cfg";then
		# the config exists check the config
		if grep -q "enabled" "/etc/2web/mod_status/${moduleName}.cfg";then
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
		echo -n "disabled" > "/etc/2web/mod_status/${moduleName}.cfg"
		chown www-data:www-data "/etc/2web/mod_status/${moduleName}.cfg"
		# exit the script since by default the module is disabled
		exit
	fi
}
################################################################################
function enableMod(){
	# enable a module name
	moduleName=$1
	ALERT "Enabling the module $moduleName"
	echo -n "enabled" > /etc/2web/mod_status/${moduleName}.cfg
}
################################################################################
function disableMod(){
	# disable a module name
	moduleName=$1
	ALERT "Disabling the module $moduleName"
	echo -n "disabled" > /etc/2web/mod_status/${moduleName}.cfg
}
################################################################################
function loadWithoutComments(){
	grep -Ehv "^#" "$1"
	return 0
}
################################################################################
function drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
}
################################################################################
function showServerLinks(){
	# show the server link at the bottom of the interface
	drawLine
	echo "To access the webserver go to the below link."
	drawLine
	echo "http://$(hostname).local:80/"
	drawLine
	echo "To access the administrative interface go to the below link."
	drawLine
	echo "http://$(hostname).local:80/settings/"
	drawLine
}
################################################################################
function addToIndex(){
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
	line=$1
	# check the libary sum against the existing one
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
setDirDataSum(){
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
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
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
setDirSum(){
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
downloadThumbnail(){
	thumbnailLink=$1
	thumbnailPath=$2
	thumbnailExt=$3
	sumName=$(echo -n "$thumbnailLink" | sha512sum | cut -d' ' -f1)
	# if the link has already been downloaded then dont download it
	webDirectory=$(webRoot)
	# if it dont exist download it
	if ! test -f "$webDirectory/thumbnails/$sumName$thumbnailExt";then
		# generated the sum for the thumbnail name
		timeout 120 curl -L --silent "$thumbnailLink" | convert -quiet - "$webDirectory/thumbnails/$sumName$thumbnailExt"
		# sleep for one second after each thumbnail download
		#sleep 1
	fi
	if ! test -f "$thumbnailPath$thumbnailExt";then
		linkFile "$webDirectory/thumbnails/$sumName$thumbnailExt" "$thumbnailPath$thumbnailExt"

		# save the thumbnail to a download path, and link to that downloaded thumbnail
		#curl --silent "$thumbnailLink" | convert -quiet - "$thumbnailPath$thumbnailExt"
	fi
}
########################################################################
popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg" gives you "test.jpg"
	echo "$1" | rev | cut -d'/' -f1 | rev
}
########################################################################
sqliteEscape(){
	printf -v var "%q" "$1"
	echo "$var"
}
########################################################################
function addToLog(){
	errorType=$1
	errorDescription=$2
	errorDetails=$3
	moduleName=$(echo "${0##*/}" | cut -d'.' -f1)

	# create identifier date to organize the data, this is really accurate
	logIdentifier="$(date "+%s.%N")"
	logDate="$(date "+%D")"
	logTime="$(date "+%R:%S")"

	#logDescription=$(echo -e "$errorDescription" | txt2html --extract | recode TXT..HTML )
	#logDescription=$(echo -e "$errorDescription" | txt2html --extract)
	#logDescription=$(echo -e "$errorDescription")
	#logDescription=$(echo -e "$errorDescription")
	#logDescription=$(sqliteEscape "$errorDescription")
	#logDescription=$(echo -e "$errorDescription" | php -R 'echo addslashes($argn);')
	#logDescription=$(echo -e "$errorDescription" | txt2html --extract --link_only)
	#logDescription=$(echo -e "$errorDescription" | tr --delete "[:punct:]" | tr --delete "[:digit:]" )
	logDescription=$(echo -e "$errorDescription" | sed "s/'/''/g" )
	#logDetails=$(echo -e "$errorDetails" | txt2html --extract | recode TXT..HTML )
	#logDetails=$(echo -e "$errorDetails" | txt2html --extract)
	#logDetails=$(echo -e "$errorDetails")
	#logDetails=$(echo -e "$errorDetails")
	#logDetails=$(sqliteEscape "$errorDetails")
	#logDetails=$(echo -e "$errorDetails" | php -R 'echo addslashes($argn);')
	#logDetails=$(echo -e "$errorDetails" | txt2html --extract --link_only)
	#logDetails=$(echo -e "$errorDetails" | tr --delete "[:punct:]" |  tr --delete "[:digit:]" )
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
	fi
	# if the data is already stored in the database
	#sqlite3 -cmd ".timeout $timeout" "$indexPath" "replace into $databaseTable values('$logIdentifier','$moduleName','$errorType','$logDescription','$logDetails','$logDate','$logTime');"
	sqlite3 -cmd ".timeout $timeout" "$indexPath" "replace into $databaseTable values('$logIdentifier','$moduleName','$errorType',quote('$logDescription'),quote('$logDetails'),'$logDate','$logTime');"
}
########################################################################
function yesNoCfgCheck(){
	configFilePath="$1"
	if test -f "$configFilePath";then
		# file exists check if it is a yes value
		if grep --quiet --ignore-case "yes" "$configFilePath";then
			return 0
		else
			return 1
		fi
	else
		return 1
	fi
}
########################################################################
