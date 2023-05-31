#! /bin/bash
################################################################################
# ai2web adds machine learning to other 2web modules, and gpt4all web interface
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
# enable debug log
#set -x
################################################################################
source /var/lib/2web/common
################################################################################
function generatedDir(){
	if [ ! -f /etc/2web/ai/generated.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/generated_ai/"
		} >> "/etc/2web/ai/generated.cfg"
		createDir "/var/cache/2web/generated_ai/"
	fi
	# write path to console
	cat "/etc/2web/ai/generated.cfg"
}
################################################################################
function downloadDir(){
	if [ ! -f /etc/2web/ai/download.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/download_ai/"
		} >> "/etc/2web/ai/download.cfg"
		createDir "/var/cache/2web/download_ai/"
	fi
	# write path to console
	cat "/etc/2web/ai/download.cfg"
}
################################################################################
function libaryPaths(){
	# add the download directory to the paths
	echo "$(downloadDir)"
	# check for server libary config
	if [ ! -f /etc/2web/ai/libaries.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/downloads_ai/"
		} >> "/etc/2web/ai/libaries.cfg"
	fi
	# write path to console
	cat "/etc/2web/ai/libaries.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/ai/libaries.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		cat "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
	# add the generated ai directories
	printf "$(generatedDir)/\n"
}
################################################################################
function disabledModels(){
	# check for server libary config
	if [ ! -f /etc/2web/ai/disabled_models.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "# list a language model name in this file to disable it from running in the web interface."
		} >> "/etc/2web/ai/disabled_models.cfg"
	fi
	# write path to console
	cat "/etc/2web/ai/disabled_models.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/ai/disabled_models.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		cat "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
function disabledPersonas(){
	# check for server libary config
	if [ ! -f /etc/2web/ai/disabled_personas.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "# list a language persona name in this file to disable it being generated in the web interface."
		} >> "/etc/2web/ai/disabled_personas.cfg"
	fi
	# write path to console
	cat "/etc/2web/ai/disabled_personas.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/ai/disabled_personas.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		cat "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
function update(){
	#DEBUG
	#set -x
	# this will launch a processing queue that downloads updates to ai
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/ai/sources.cfg;then
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/ai2web_sources.cfg
		} > /etc/2web/ai/sources.cfg
	fi
	# load sources
	aiSources=$(grep -v "^#" /etc/2web/ai/sources.cfg)
	aiSources=$(echo -e "$aiSources\n$(grep -v --no-filename "^#" /etc/2web/ai/sources.d/*.cfg)")

	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedDir)"
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make ai directory
	createDir "$webDirectory/ai/"
	# scan the sources
	ALERT "AI Download Sources: $aiSources"
	echo "$aiSources" | while read aiSource;do
		# generate a sum for the source
		#aiSum=$(echo "$aiSource" | sha512sum | cut -d' ' -f1)
		aiName=$(echo "$aiSource" | rev | cut -d'/' -f1 | rev)
		# create the ai directory
		createDir "$webDirectory/ai/$aiName/"
		# link the individual index page for this ai model in the web interface
		linkFile "/usr/share/2web/templates/ai.php" "$webDirectory/ai/$aiName/index.php"
		# do not process the ai if it is still in the cache
		if ! test -f "/var/cache/2web/downloads_ai/$aiName";then
			# download the ai model from remote location
			curl "$aiSource" > "/var/cache/2web/downloads_ai/$aiName"
		fi
	done
}
################################################################################
function getWeight(){
	# this is the heavy lifter of the program this builds the comparison values and stores them in a sqlite database
	file_1="$1"
	file_2="$2"
	databasePath="$3"
	totalCompress="$(cat "$file_1" "$file_2" | gzip -c | wc -c)"
	orignalCompress="$(cat "$file_1" | gzip -c | wc -c)"
	#weight="$(echo "$totalCompress - $orignalCompress" | bc )"
	weight=$((totalCompress - orignalCompress))
	if [[ "$weight" == "" ]];then
		weight=0
	fi
	addSqlComparison "$databasePath" "$file_1" "$file_2" "$weight"
}
################################################################################
function addSqlComparison(){
	# Database Ex
	#
	# * Compare_Index
	#  - md5sum of file
	#  - file path
	# * Compare_Group_Charts
	#  - md5sum of base file
	#  - md5sum of compared file
	#  - weight given to comparison

	databasePath="$1"
	baseFile="$2"
	compareFile="$3"
	totalWeight="$4"

	weightsTable="Weights"
	pathIndex="Paths"

	# set the default timeout to wait for writing to the database
	# - time in miliseconds
	# - 1 minute default
	timeout=60000

	# build sums for each of the files
	baseSum=$(echo -n "$baseFile" | md5sum | cut -d' ' -f1)
	compareSum=$(echo -n "$compareFile" | md5sum | cut -d' ' -f1)

	# build the comparison identifier by combining and sorting file sums then generate a sum from those sums
	comparsionId=$(echo -e "$baseSum\n$compareSum" | sort)
	comparisonId=$(echo "$comparisonId" | md5sum | cut -d' ' -f1)

	#example: /var/cache/2web/new.sql
	#INFO "Checking if the databasePath '$databasePath' exists"
	# if the database file exists read it
	if test -f "$databasePath";then
		# create tables that do not exist in the database
		if ! sqlite3 -cmd ".timeout $timeout"  "$databasePath" "select name from sqlite_master where type='table';" | grep -q "$baseSum";then
			sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$baseSum\" (compareSum text primary key,weight int);"
		fi
		if ! sqlite3 -cmd ".timeout $timeout"  "$databasePath" "select name from sqlite_master where type='table';" | grep -q "$compareSum";then
			sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$compareSum\" (compareSum text primary key,weight int);"
		fi
		if ! sqlite3 -cmd ".timeout $timeout"  "$databasePath" "select name from sqlite_master where type='table';" | grep -q "$pathIndex";then
			sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$pathIndex\" (sum text primary key,filePath text);"
		fi
		#if ! sqlite3 -cmd ".timeout $timeout"  "$indexPath" "select name from sqlite_master where type='table';" | grep -q "$compareSum";then
		#	sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$compareSum\" (compareSum text primary key,weight int);"
		#fi
		# insert the data into the existing database
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$baseSum\" values('$compareSum', '$totalWeight');"
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$compareSum\" values('$baseSum', '$totalWeight');"
		#
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$pathIndex\" values('$baseSum', '$baseFile');"
		#
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$pathIndex\" values('$compareSum', '$compareFile');"
	else
		# first set the new database into wal mode for better handling of concurrency in the database
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "PRAGMA journal_mode=WAL;"
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "PRAGMA wal_autocheckpoint=20;"
		# create the sql database tables
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$baseSum\" (compareSum text primary key,weight int);"
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$compareSum\" (compareSum text primary key,weight int);"
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "create table \"$pathIndex\" (sum text primary key,filePath text);"
		# add the item to the sql database
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$baseSum\" values('$compareSum', '$totalWeight');"
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$compareSum\" values('$baseSum', '$totalWeight');"
		# add the file into the file index
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$pathIndex\" values('$baseSum', '$baseFile');"
		#
		sqlite3 -cmd ".timeout $timeout" "$databasePath" "replace into \"$pathIndex\" values('$compareSum', '$compareFile');"

		# set ownership of the newly created index
		chown www-data:www-data "$databasePath"
	fi
}
################################################################################
convertTime(){
	estimatedProcessingTime=$1

	# calc the time
	miliseconds=$(echo "$estimatedProcessingTime" | cut -d'.' -f2)
	# remove miliseconds from the time calc
	estimatedProcessingTime=$(echo "$estimatedProcessingTime" | cut -d'.' -f1 )

	days=$(echo "(($estimatedProcessingTime / 60) / 60) / 24 " | bc)
	estimatedProcessingTime=$(echo "$estimatedProcessingTime - ((($days * 24) * 60) * 60)" | bc)

	hours=$(echo "($estimatedProcessingTime / 60) / 60 " | bc)
	estimatedProcessingTime=$(echo "$estimatedProcessingTime - (($hours * 60) * 60)" | bc)

	minutes=$(echo "$estimatedProcessingTime / 60" | bc)
	estimatedProcessingTime=$(echo "$estimatedProcessingTime - ($minutes * 60)" | bc)
	if [ $minutes -lt 10 ];then
		minutes="0$minutes"
	fi

	seconds=$estimatedProcessingTime
	if [ $seconds -lt 10 ];then
		seconds="0$seconds"
	fi
	estimatedProcessingTime="$days days $hours:$minutes:$seconds"
	echo "$estimatedProcessingTime"
}
################################################################################
function compareGroup(){
	# for each file in the group files compare it to every other file
	# prevent running duplicate comparisons with md5sums

	# take a list of files and create a comparison ranking for each of the files compared to each of the other files
	# this will give you a comparison ranking for each file in the data set, the smallest number generated will be correct
	# you can then pull this data from the sqlite database generated with the rankings to see what files compare in the dataset
	# all new data must be compared with all other data in the group,

	databaseName=$1
	#databaseName="test.db"
	groupFolderPath=$2

	groupSearchName=$3

	if test -d "$groupFolderPath";then
		groupFiles=$(find "$groupFolderPath" -type "f,l" | grep "$groupSearchName" | shuf )
	elif [ $groupFolderPath == "episodes" ];then
		# episodes comparisons
		groupFiles=$(find "/var/cache/2web/web/shows/" -mindepth 3 -type "f,l" -name "*.nfo" | shuf )
	elif [ $groupFolderPath == "movies" ];then
		# movies comparisons
		groupFiles=$(find "/var/cache/2web/web/movies/" -mindepth 1 -type "f,l" -name "*.nfo" | shuf )
	elif [ $groupFolderPath == "shows" ];then
		# shows comparisons
		groupFiles=$(find "/var/cache/2web/web/shows/" -maxdepth 2 -type "f,l" -name "*.nfo" | shuf )
	elif [ $groupFolderPath == "repos" ];then
		# repo comparisons, of index files
		groupFiles=$(cat /var/cache/2web/web/repos.index | shuf )
	elif [ $groupFolderPath == "graphs" ];then
		# graph comparisons, of index files
		groupFiles=$(cat /var/cache/2web/web/graphs.index | shuf )
	else
		# read directory given as second argument for index files to compare
		groupFiles=$(find "$2" -mindepth 1 -type "f,l" -name "*.index" | shuf )
	fi

	IFSBACKUP=$IFS
	IFS=$'\n'
	#databaseName="/var/cache/2web/ml.db"
	totalCpus=$(cpuCount)
	totalCpus=$((totalCpus * 2))
	#totalCpus=1
	#ALERT "group files = $groupFiles"
	blocksComplete=0
	for filePath in $groupFiles;do
		if [ "$executionTime" == "0" ];then
			# build the test case and time the execution for estimate
			startTime=$(date "+%s.%N")
			getWeight "$filePath" "$filePath" "$databaseName"
			stopTime=$(date "+%s.%N")
			executionTime=$(echo "$stopTime - $startTime" | bc)
		fi

		estimatedProcessingTime=$(echo "($executionTime * (($groupLength * $groupLength) - ($counter * $groupLength) ) )" | bc)

		estimatedProcessingTime=$(convertTime $estimatedProcessingTime)

		counter=0
		executionTime="0"
		executionSeconds="0"
		#estimatedProcessingTime="? days ??:??:??"

		groupLength="$(echo "$groupFiles" | wc -l)"
		queueLength="$(( $groupLength * $groupLength ))"

		# calculate the base sum value
		baseSum=$(echo "$filePath" | md5sum | cut -d' ' -f1)
		# if the file has less entries in its database than the length of a block, it needs calculated, otherwise skip
		if [ $(sqlite3 -cmd ".timeout 60000"  "$databaseName" "select * from \"$baseSum\";" | wc -l) -lt $groupLength ];then
			# build block pulls in all the variables present in the current shell
			#buildBlock
			buildBlock &
			waitFastQueue 1 "$totalCpus"
		else
			INFO
			ALERT "All sums have been calculated for $baseSum, skipping..."
			counter=$(( counter + groupLength ))
		fi
		blocksComplete=$(( blocksComplete + 1 ))
		#INFO "Comparing $baseSum to $baseSum [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime"
		tempString="Block Processing [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime"
		#echo -ne "$(echo "$tempString" | sed "s/./ /g")\r"
		#echo
		INFO
		ALERT "Block Processing [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime"
	done
	blockQueue 1
	IFS=$IFSBACKUP
}
################################################################################
function buildBlock(){
	for compareFilePath in $groupFiles;do
		compareSum=$( echo "$compareFilePath" | md5sum | cut -d' ' -f1 )
		# check if the comparison exists already
		#if ! sqlite3 -cmd ".timeout 60000"  "$databaseName" "select compareSum from \"$baseSum\";" | grep -q "$compareSum" ;then

		# build the comparison id
		comparsionId=$(echo -e "$baseSum\n$compareSum" | sort)
		comparisonId=$(echo "$comparisonId" | md5sum | cut -d' ' -f1)

		# check for the comparison id in the base sum comparisons
		#if ! sqlite3 -cmd ".timeout 60000"  "$databaseName" "select * from \"$weightsTable\";" | grep -q "$comparisonId" ;then
		if ! sqlite3 -cmd ".timeout 60000"  "$databaseName" "select compareSum from \"$baseSum\";" | grep -q "$compareSum" ;then
			#ALERT "executionSeconds = $executionSeconds"
			#ALERT "executionTime = $executionTime"
			#if [ "$executionTime" == "0" ];then
			#	# build the test case and time the execution for estimate
			#	startTime=$(date "+%s.%N")
			#	getWeight "$filePath" "$compareFilePath" "$databaseName"
			#	stopTime=$(date "+%s.%N")
			#	executionTime=$(echo "$stopTime - $startTime" | bc)
			#	#ALERT "executionTime = $executionTime"
			#fi

			#estimatedProcessingTime=$(echo "($executionTime * (($groupLength * $groupLength) - $counter) )" | bc)

			#estimatedProcessingTime=$(convertTime $estimatedProcessingTime)

			#progress="$(echo "scale=4;( $counter / $queueLength ) * 100" | bc )"
			# get the weight
			#INFO "Comparing $baseSum to $compareSum checking file [$counter/$groupLength] in block: [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime"
			#INFO "Comparing $baseSum to $compareSum checking file [$counter/$groupLength] ETA: $estimatedProcessingTime"
			INFO "Checking file [$counter/$groupLength] ETA: $estimatedProcessingTime"
			#INFO "Comparing blocks [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime, building blocks..."
			#INFO "Comparing $baseSum to $compareSum"
			#getWeight "$filePath" "$compareFilePath" "$databaseName"
			getWeight "$filePath" "$compareFilePath" "$databaseName"
			counter=$(( counter + 1 ))
			#waitSlowQueue 0 "$totalCpus"
		else
			#INFO "Comparing blocks [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime, rescanning for missing blocks..."
			#INFO "Comparing $baseSum to $compareSum [$counter/$queueLength] in $groupLength sized blocks ETA: $estimatedProcessingTime, Skipping existing comparison..."
			#INFO "Comparing $baseSum to $compareSum checking file [$counter/$groupLength] in block: [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime, Skipping existing comparison..."
			#INFO "Comparing $baseSum to $compareSum checking file [$counter/$groupLength], Skipping existing comparison..."
			INFO "Checking file [$counter/$groupLength], Skipping existing comparison..."
			#INFO "Comparing $baseSum to $compareSum [$blocksComplete/$groupLength] ETA: $estimatedProcessingTime, Skipping existing comparison..."
			#INFO "Comparing $baseSum to $compareSum"
			counter=$(( counter + 1 ))
		fi
	done
}
################################################################################
webUpdate(){
	# read the download directory and convert ai into webpages
	# - There are 2 types of directory structures for ai in the download directory
	#   + aiWebsite/aiName/chapter/image.png
	#   + aiWebsite/aiName/image.png

	webDirectory=$(webRoot)
	downloadDirectory="$(libaryPaths | tr -s '\n' | shuf )"

	ALERT "$downloadDirectory"

	# create the kodi directory
	createDir "$webDirectory/kodi/ai/"

	# create the web directory
	createDir "$webDirectory/ai/"

	# link the homepage
	linkFile "/usr/share/2web/templates/ai.php" "$webDirectory/ai/index.php"

	################################################################################
	# build the comparisons in the database for machine learning comparison match database
	# - This will build comparisons for related videos style comparisons
	################################################################################
	compareGroup "/var/cache/2web/ml.db" "movies"
	compareGroup "/var/cache/2web/ml.db" "shows"
	compareGroup "/var/cache/2web/ml.db" "episodes"
	#compareGroup "/var/cache/2web/ml.db" "graphs"
	#compareGroup "/var/cache/2web/ml.db" "repos"
	#compareGroup "/var/cache/2web/ml.db" "artists"
	#compareGroup "/var/cache/2web/ml.db" "tracks"
}
################################################################################
function resetCache(){
	# reset all generated/downloaded content
	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"
	# remove all the index files generated by the website
	find "$webDirectory/ai/" -name "*.index" -delete

	# remove web cache
	rm -rv "$webDirectory/ai/" || INFO "No ai web directory at '$webDirectory/ai/'"

	#
	echo "You MUST remove downloaded ai manually they are stored at:"
	echo "$downloadDirectory"
}
################################################################################
function nuke(){
	webDirectory="$(webRoot)"
	downloadDirectory="$(downloadDir)"
	# delete intermediate conversion directories
	# remove new and random indexes
	rm -rv "$webDirectory/new/ai_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/ai_*.index'"
	rm -rv "$webDirectory/random/ai_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/ai_*.index'"
	# remove ai directory and indexes
	rm -rv $webDirectory/ai/
	# remove the cached conversations
	rm -rv $webDirectory/ai/convos.db
	rm -rv $webDirectory/sums/ai2web_*.cfg || echo "No file sums found..."
	# remove sql data
	sqlite3 $webDirectory/data.db "drop table ai;"
	# remove widgets cached
	rm -v $webDirectory/web_cache/widget_random_ai.index
	rm -v $webDirectory/web_cache/widget_new_ai.index
}
################################################################################
main(){
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		lockProc "ai2web"
		checkModStatus "ai2web"
		webUpdate "$@"
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		lockProc "ai2web"
		checkModStatus "ai2web"
		update "$@"
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		checkModStatus "ai2web"
		# upgrade the jslint package
		pip3 install --upgrade gpt4all
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "ai2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "ai2web"
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/ai2web.txt"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "ai2web Version: "
		cat /usr/share/2web/version_ai2web.cfg
	else
		lockProc "ai2web"
		checkModStatus "ai2web"
		update "$@"
		webUpdate "$@"
		#main --help $@
		# on default execution show the server links at the bottom of output
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/ai/"
		drawLine
		echo "http://$(hostname).local:80/settings/ai.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
