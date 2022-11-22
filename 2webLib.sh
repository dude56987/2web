#! /bin/bash
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
		chown -R www-data:www-data "/var/cache/2web/cache/"
		echo "/var/cache/2web/cache/" > /etc/2web/web.cfg
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
function checkFileDataSum(){
	# return true if the directory has been updated/changed
	# store sums in $webdirectory/$sums
	webDirectory=$1
	filePath=$2

	# check the sum of a directory and compare it to a previously stored sum
	createDir "$webDirectory/sums/"
	pathSum="$(echo "$filePath" | md5sum | cut -d' ' -f1 )"

	# check for a previous sum
	if test -f "$webDirectory/sums/file_$pathSum.cfg";then
		# load the old sum
		oldSum="$(cat "$webDirectory/sums/file_$pathSum.cfg")"

		# generate the new md5sum for the file
		newSum="$(cat "$filePath" | md5sum | cut -d' ' -f1 )"

		# compare the sum of the old path with the new one
		if [ "$oldSum" == "$newSum" ];then
			ALERT "Sum is UNCHANGED"
			# UNCHANGED
			# if the sums are the same no change detected, pass false
			# return false
			return 1
		else
			# CHANGED
			# the sums are diffrent, pass true
			# update the sum
			echo "$newSum" > "$webDirectory/sums/file_$pathSum.cfg"
			# return true
			return 0
		fi
	else
		# CHANGED, new file
		# no previous file was found, pass true
		# update the sum
		echo "$newSum" > "$webDirectory/sums/file_$pathSum.cfg"
		# return true
		return 0
	fi
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
	if test -d "$webDirectory/comics/";then
		if cacheCheck "$webDirectory/totalComics.index" "1";then
			# figure out the stats
			totalComics=$(cat "$webDirectory/comics/comics.index" | wc -l )
			# write the stats
			echo "$totalComics" > "$webDirectory/totalComics.index"
		fi
	fi
	if test -d "$webDirectory/shows/";then
		if cacheCheck "$webDirectory/totalEpisodes.index" "7";then
			totalEpisodes=$(find "$webDirectory"/shows/*/*/ -name '*.nfo' | wc -l)
			echo "$totalEpisodes" > "$webDirectory/totalEpisodes.index"
		fi
		if cacheCheck "$webDirectory/totalShows.index" "1";then
			totalShows=$(cat "$webDirectory/shows/shows.index" | wc -l )
			echo "$totalShows" > "$webDirectory/totalShows.index"
		fi
	fi
	if cacheCheck "$webDirectory/totalMovies.index" "1";then
		totalMovies=$(cat "$webDirectory/movies/movies.index" | wc -l )
		echo "$totalMovies" > "$webDirectory/totalMovies.index"
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
	if cacheCheck "$webDirectory/webSize.index" "7";then
		# count website size in total ignoring symlinks
		webSize=$(du -shP "$webDirectory" | cut -f1)
		echo "$webSize" > "$webDirectory/webSize.index"
	fi
	if cacheCheck "$webDirectory/cacheSize.index" "1";then
		# cache size for resolver-cache
		cacheSize=$(du -shP "$webDirectory/RESOLVER-CACHE/" | cut -f1)
		echo "$cacheSize" > "$webDirectory/cacheSize.index"
	fi
	if cacheCheck "$webDirectory/mediaSize.index" "7";then
		# count symlinks in kodi to get the total size of all media on all connected drives containing libs
		mediaSize=$(du -shL "$webDirectory/kodi/" | cut -f1)
		echo "$mediaSize" > "$webDirectory/mediaSize.index"
	fi
	if cacheCheck "$webDirectory/freeSpace.index" "7";then
		# count total freespace on all connected drives, ignore temp filesystems (snap packs)
		freeSpace=$(df -h -x "tmpfs" --total | grep "total" | tr -s ' ' | cut -d' ' -f4)
		echo "$freeSpace" > "$webDirectory/freeSpace.index"
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
function linkFile(){
	if ! test -L "$2";then
		ln -sf "$1" "$2"
		# DEBUG: log each linked file
		#echo "ln -sf '$1' '$2'" >> /var/cache/2web/web/linkedFiles.log
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
function checkModStatus(){
	configFile="$1"
	if test -f "/etc/2web/mod_status/$configFile";then
		# the config exists check the config
		if grep -q "enabled" "/etc/2web/mod_status/$configFile";then
			# the module is enabled
			echo "Preparing to process $configFile..."
		else
			# the module is not enabled
			# - remove the files and directory if they exist
			nuke
			exit
		fi
	else
		createDir "/etc/2web/mod_status/"
		# the config does not exist at all create the default one
		# - the default status for graph2web should be disabled
		echo "enabled" > "/etc/2web/mod_status/$configFile"
		chown www-data:www-data "/etc/2web/mod_status/$configFile"
		# exit the script since by default the module is disabled
		exit
	fi
}
################################################################################
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
function waitQueue(){
	# PARALLEL PROCESSING COMMAND
	# wait for queue to free up for next command
	sleepTime=$1
	totalCPUS=$2
	while true;do
		# jobs -r only shows running jobs
		if [ $(jobs -r | wc -l) -ge $totalCPUS ];then
			#ALERT "WAITING FOR QUEUE PLACE TO OPEN"
			sleep $sleepTime
		else
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
			# jobs -r only shows running jobs
			#ALERT "BLOCKING QUEUE '$( jobs -r | wc -l )' Remaining Jobs"
			#ALERT "$(jobs -r)"
			sleep $sleepTime
			#sleep 2
		else
			break
		fi
	done
}
################################################################################
ALERT(){
	echo
	echo "$1";
	echo
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
		ALERT "Setting Active Trap $webDirectory/${procName}.active"
		# create a trap to remove module lockfile
		trap "rm $webDirectory/${procName}.active" EXIT
	fi
}
################################################################################
checkModStatus(){
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
enableMod(){
	# enable a module name
	moduleName=$1
	ALERT "Enabling the module $moduleName"
	echo -n "enabled" > /etc/2web/mod_status/${moduleName}.cfg
}
################################################################################
disableMod(){
	# disable a module name
	moduleName=$1
	ALERT "Disabling the module $moduleName"
	echo -n "disabled" > /etc/2web/mod_status/${moduleName}.cfg
}
################################################################################
drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
}
################################################################################
showServerLinks(){
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
	INFO "Checking if the indexPath '$indexPath' exists"
	if test -f "$indexPath";then
		# the index file exists
		#ALERT "Looking for $indexItem in $indexPath"
		if grep -q "$indexItem" "$indexPath";then
			INFO "The Index '$indexPath' already contains '$indexItem'"
		else
			INFO "Adding '$indexItem' to '$indexPath'"
			# the item is not in the index
			echo "$indexItem" >> "$indexPath"
		fi
	else
		#ALERT "No index found, creating one..."
		INFO "Adding '$indexItem' to '$indexPath'"
		# create the index file
		touch "$indexPath"
		# set ownership of the newly created index
		chown www-data:www-data "$indexPath"
		# the index file does not exist
		echo "$indexItem" > "$indexPath"
	fi
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
