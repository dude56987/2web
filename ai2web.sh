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
			echo "/var/cache/2web/generated/ai/"
		} >> "/etc/2web/ai/generated.cfg"
		createDir "/var/cache/2web/generated/ai/"
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
			echo "/var/cache/2web/downloads/ai/"
		} >> "/etc/2web/ai/download.cfg"
		createDir "/var/cache/2web/downloads/ai/"
	fi
	# write path to console
	cat "/etc/2web/ai/download.cfg"
}
################################################################################
function loadLyricsModel(){
	if [ ! -f /etc/2web/ai/lyricsLanguageModel.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "base"
		} >> "/etc/2web/ai/lyricsLanguageModel.cfg"
	fi
	# write path to console
	cat "/etc/2web/ai/lyricsLanguageModel.cfg"
}
################################################################################
function loadSubsModel(){
	if [ ! -f /etc/2web/ai/subtitlesLanguageModel.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "base"
		} >> "/etc/2web/ai/subtitlesLanguageModel.cfg"
	fi
	# write path to console
	cat "/etc/2web/ai/subtitlesLanguageModel.cfg"
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
			echo "/var/cache/2web/downloads/ai/prompt/"
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
	INFO "Loading up prompt models..."
	# check for defined sources
	if ! test -f /etc/2web/ai/promptModels.cfg;then
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/ai2web_promptModels.cfg
		} > /etc/2web/ai/promptModels.cfg
	fi
	# load sources
	aiPromptModels=$(grep -v "^#" /etc/2web/ai/promptModels.cfg)
	aiPromptModels=$(echo -e "$aiPromptModels\n$(grep -v --no-filename "^#" /etc/2web/ai/promptModels.d/*.cfg)")

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
	################################################################################
	# create the default download directories
	################################################################################
	# create web cache directories for model generated responses
	createDir "/var/cache/2web/web/ai/txt2txt/"
	createDir "/var/cache/2web/web/ai/img2img/"
	createDir "/var/cache/2web/web/ai/txt2img/"
	createDir "/var/cache/2web/web/ai/prompt/"
	# create cache directories for AI models
	createDir "/var/cache/2web/downloads/ai/prompt/"
	createDir "/var/cache/2web/downloads/ai/txt2txt/"
	createDir "/var/cache/2web/downloads/ai/txt2img/"
	createDir "/var/cache/2web/downloads/ai/img2img/"
	createDir "/var/cache/2web/downloads/ai/img2txt/"
	createDir "/var/cache/2web/downloads/ai/zoom/"
	createDir "/var/cache/2web/downloads/ai/voice2txt/"
	createDir "/var/cache/2web/downloads/ai/txt2voice/"
	# used for whisper to convert voice to text locally
	createDir "/var/cache/2web/downloads/ai/subtitles/"
	# scan the sources
	ALERT "AI Download Model Sources: $aiPromptModels"
	echo "$aiPromptModels" | while read aiSource;do
		# generate a sum for the source
		aiName=$(echo "$aiSource" | rev | cut -d'/' -f1 | rev)
		# create the ai directory
		createDir "$webDirectory/ai/$aiName/"
		# link the individual index page for this ai model in the web interface
		linkFile "/usr/share/2web/templates/ai.php" "$webDirectory/ai/$aiName/index.php"
		# do not process the ai if it is still in the cache
		#if ! test -f "/var/cache/2web/downloads/ai/prompt/$aiName";then
		ALERT "checking for existance of lock file that blocks further download  '$webDirectory/sums/ai2web_model_prompt_$aiName.cfg'"
		if ! test -f "$webDirectory/sums/ai2web_model_prompt_$aiName.cfg";then
			ALERT "No block file found downloading with wget..."
			# download the ai model from remote location
			wget --continue "https://gpt4all.io/models/$aiSource" -O "/var/cache/2web/downloads/ai/prompt/$aiName"
			# set correct ownership of files
			chown www-data:www-data "/var/cache/2web/downloads/ai/prompt/$aiName"
			if [ $? -eq 0 ];then
				# the download finished successfully
				touch "$webDirectory/sums/ai2web_model_prompt_$aiName.cfg"
			fi
			#curl -C - "https://gpt4all.io/models/$aiSource" > "/var/cache/2web/downloads/ai/prompt/$aiName"
		fi
		#fi
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
########################################################################
function generateLyrics(){
	# generate lyrics using local AI tool whisper

	# used to generate subtitles for video files that do not already have subtitles
	videoFile=$1
	aiModel=$2

	# split up input path
	fileName=$(echo "$videoFile" | rev | cut -d '/' -f1 | rev)
	# remove file extension
	fileName=$(echo "$fileName" | sed "s/\.mp3//g")

	filePath=$(echo "$videoFile" | rev | cut -d '/' -f2- | rev)

	inputSum=$(echo "$filePath" | md5sum | cut -d' ' -f1 )

	# check if the video file exists
	if ! test -f "$videoFile";then
		return 0
	fi
	# check if the file has already been created
	if test -f "$filePath/$fileName-lyrics.txt";then
		return 0
	elif echo "$videoFile" | grep ".strm$";then
		# check if the file is a .strm that can not generate subtitles
		return 0
	fi

	# generated subtitles cache directory
	createDir "/var/cache/2web/ai/lyrics/"
	createDir "/var/cache/2web/ai/lyrics/$inputSum/"

	# input the videofile to generate srt file
	# - save only .srt subtitle files
	# - use the max threads
	# - set task to translate so non-english movies are translated into english
	# - english movies will be transcribed with with translate task as well
	# - chosen model will be downloaded on first use
	# - models are: tiny, base, small, medium, large, large-v2
	# - threads is set to one because the processing jobs that call this function run in parallel
	#/usr/bin/sem --retries 10 --jobs 1 --fg --id "AI" whisper --model small \
	#whisper --model small \
	#whisper --model tiny \
	#whisper --model base \
	whisper --model "$aiModel" \
		--task translate \
		--model_dir "/var/cache/2web/downloads/ai/subtitles/" \
		--output_format "txt" \
		--output_dir "/var/cache/2web/ai/lyrics/$inputSum/" \
		--threads "$(cpuCount)" \
		"$videoFile"
	# link the generated subtitle file into the same directory as the file
	# - This can then be linked into the kodi directory for library video files
	# - This can generate .srt subs for the video_cache/ and translate_cache/
	linkFile "/var/cache/2web/ai/lyrics/$inputSum/$fileName.txt" "$filePath/$fileName-lyrics.txt"
}
########################################################################
function generateSubtitles(){
	# generate subtitles using local AI tool whisper

	# used to generate subtitles for video files that do not already have subtitles
	videoFile=$1
	aiModel=$2

	# split up input path
	fileName=$(echo "$videoFile" | rev | cut -d '/' -f1 | rev)
	filePath=$(echo "$videoFile" | rev | cut -d '/' -f2- | rev)

	# check if the video file exists
	if ! test -f "$videoFile";then
		return 0
	fi
	# check if the file has already been created
	if test -f "$filePath/$fileName.ai.srt";then
		return 0
	elif echo "$videoFile" | grep ".strm$";then
		# check if the file is a .strm that can not generate subtitles
		return 0
	fi
	startDebug

	# generated subtitles cache directory
	createDir "/var/cache/2web/ai/subs/"

	# input the videofile to generate srt file
	# - save only .srt subtitle files
	# - use the max threads
	# - set task to translate so non-english movies are translated into english
	# - english movies will be transcribed with with translate task as well
	# - chosen model will be downloaded on first use
	# - models are: tiny, base, small, medium, large, large-v2
	# - threads is set to one because the processing jobs that call this function run in parallel
	#/usr/bin/sem --retries 10 --jobs 1 --fg --id "AI" whisper --model small \
	#whisper --model small \
	#whisper --model tiny \
	#whisper --model base \
	whisper --model "$aiModel" \
		--task translate \
		--model_dir "/var/cache/2web/downloads/ai/subtitles/" \
		--output_format "srt" \
		--output_dir "/var/cache/2web/ai/subs/" \
		--threads "$(cpuCount)" \
		"$videoFile"
	# link the generated subtitle file into the same directory as the file
	# - This can then be linked into the kodi directory for library video files
	# - This can generate .srt subs for the video_cache/ and translate_cache/
	linkFile "/var/cache/2web/ai/subs/$fileName.srt" "$filePath/$fileName.ai.srt"
	stopDebug
}
################################################################################
webUpdate(){
	# read the download directory and convert ai into webpages
	# - There are 2 types of directory structures for ai in the download directory
	#   + aiWebsite/aiName/chapter/image.png
	#   + aiWebsite/aiName/image.png

	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"

	ALERT "$downloadDirectory"

	# create the kodi directory
	createDir "$webDirectory/kodi/ai/"

	# create the web directory
	createDir "$webDirectory/ai/"

	# link the homepage
	linkFile "/usr/share/2web/templates/ai.php" "$webDirectory/ai/index.php"
	# link the prompting interface
	linkFile "/usr/share/2web/templates/ai_prompt.php" "$webDirectory/ai/prompt/index.php"

	startDebug
	################################################################################
	# generate lyrics for mp3 tracks in music2web
	################################################################################
	# check if music module is enabled
	if returnModStatus "music2web";then
		if yesNoCfgCheck "/etc/2web/ai/aiLyricsGenerate.cfg";then
			# load up the lyrics model chosen
			lyricsModel="$(loadLyricsModel)"
			# search web directories for music to generate lyrics for web interface
			foundVideoFiles=$(find "$webDirectory/music/" \
				| grep ".mp3$" \
				| shuf )
			echo "$foundVideoFiles" | while read videoFilePath;do
				#
				generateLyrics "$videoFilePath" "$lyricsModel"
			done
		fi
	fi

	################################################################################
	#	check for files that can be transcribed by whisper
	################################################################################
	if returnModStatus "nfo2web";then
		if yesNoCfgCheck "/etc/2web/ai/aiSubsGenerate.cfg";then
			subsModel="$(loadSubsModel)"
			foundVideoFiles=$(find "$webDirectory/kodi/shows/" \
				| grep ".mkv$\|.mp4$\|.avi$\|.ogv$" \
				| shuf )
			echo "$foundVideoFiles" | while read videoFilePath;do
				#
				generateSubtitles "$videoFilePath" "$subsModel"
			done
			# look for movies that can be transcribed by whisper
			foundVideoFiles=$(find "$webDirectory/kodi/movies/" \
				| grep ".mkv$\|.mp4$\|.avi$\|.ogv$" \
				| shuf )
			echo "$foundVideoFiles" | while read videoFilePath;do
				#
				generateSubtitles "$videoFilePath" "$subsModel"
			done
		fi
	fi
	stopDebug

	################################################################################
	# build the comparisons in the database for machine learning comparison match database
	# - This will build comparisons for related videos style comparisons
	################################################################################
	if returnModStatus "nfo2web";then
		if yesNoCfgCheck "/etc/2web/ai/aiCompareGenerate.cfg";then
			compareGroup "/var/cache/2web/ml.db" "movies"
			compareGroup "/var/cache/2web/ml.db" "shows"
			compareGroup "/var/cache/2web/ml.db" "episodes"
		fi
	fi
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
		# install gpt4all for base text prompt generation
		# - version 1.0.8 is still working on debain but 1.0.9 is broken
		pip3 install --break-system-packages --upgrade "gpt4all==1.0.8"
		# install whisper speech recognition
		#pip3 install --break-system-packages --upgrade openai-whisper
		# install stable diffusion diffusers library
		#pip3 install --break-system-packages --upgrade diffusers
		# install the huggingface transformers library
		#pip3 install --break-system-packages --upgrade transformers
		#pip3 install --break-system-packages --upgrade torch
		# tensor libaries
		#pip3 install --break-system-packages --upgrade safetensors
		#pip3 install --break-system-packages --upgrade xformers
		#pip3 install --break-system-packages --upgrade tensorflow
		# accelerate allows using cpu and gpu
		#pip3 install --break-system-packages --upgrade accelerate
		# 8bit support for accelerate to run larger models on smaller computers
		#pip3 install --break-system-packages --upgrade bitsandbytes
		#pip3 install --break-system-packages --upgrade tensorrt
		# speech functions are provided by speechbrain, tts, stt
		#pip3 install --break-system-packages --upgrade speechbrain
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
