#! /bin/bash
########################################################################
# 2web_search scans links for services and creates a index for 2web
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
source /var/lib/2web/common
################################################################################
function update(){
	addToLog "INFO" "STARTED Update" "$(date)"

	webDirectory=$(webRoot)

	if test -f "/etc/2web/cache/cacheDelay.cfg";then
		echo "Loading cache settings..."
		cacheDelay=$(cat "/etc/2web/cache/cacheDelay.cfg")
	else
		echo "Using default cache settings..."
		cacheDelay="14"
	fi

	# cleanup old searches

	ALERT "Checking for cache files in $webDirectory/search/"
	if test -d "$webDirectory/search/";then
		find "$webDirectory/search/" -type f -mtime +"$cacheDelay" -name '*.index' -exec rm -v {} \;
	fi


	# build searches based on names of content in database and substrings of those terms

	addToLog "INFO" "Update FINISHED" "$(date)"
}
################################################################################
function searchNew(){
	# search through each of the new database
	webDirectory=$1
	searchQuery=$2
	searchSum=$3

	outputPath="$webDirectory/search/${searchSum}_new_all.index"
	searchIndex "$webDirectory" "$webDirectory/new/all.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_movies.index"
	searchIndex "$webDirectory" "$webDirectory/new/movies.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_episodes.index"
	searchIndex "$webDirectory" "$webDirectory/new/episodes.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_comics.index"
	searchIndex "$webDirectory" "$webDirectory/new/comics.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_music.index"
	searchIndex "$webDirectory" "$webDirectory/new/music.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_graphs.index"
	searchIndex "$webDirectory" "$webDirectory/new/graphs.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_graphs.index"
	searchIndex "$webDirectory" "$webDirectory/new/graphs.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_tracks.index"
	searchIndex "$webDirectory" "$webDirectory/new/tracks.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_artists.index"
	searchIndex "$webDirectory" "$webDirectory/new/artists.index" "$searchQuery" "$outputPath"
	outputPath="$webDirectory/search/${searchSum}_new_channels.index"
	searchIndex "$webDirectory" "$webDirectory/new/channels.index" "$searchQuery""$outputPath"
}
################################################################################
function searchMovies(){
	# search though the movies database

	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_movies.index"
	searchIndex "$webDirectory" "$webDirectory/movies/movies.index" "$searchQuery"
}
################################################################################
function searchShows(){
	# search though each of the shows

	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_shows.index"
	searchIndex "$webDirectory" "$webDirectory/shows/shows.index" "$searchQuery" "$outputPath"
}
################################################################################
function searchEpisodes(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_old_episodes.index"
	indexPath="/var/cache/2web/web/data.db"
	outputPath="$webDirectory/search/${searchSum}_old_episodes.index"
	INFO "Scanning $indexPath for $searchQuery"
	# if the output path does not yet exist then build it
	if ! test -f "$outputPath";then
		# if the index path exists
		if test -f "$indexPath";then
			# get all the index files
			indexData="$(sqlite3 -cmd ".timeout 60000" "$indexPath" "select * from \"_episodes\";")"
			indexDataLength=$(echo "$indexData" | wc -l)
			indexDataCounter=0
			# search though each of the index files
			echo "$indexData" | while read episode;do
				INFO "Scanning $indexPath for $searchQuery [$indexDataCounter/$indexDataLength]"
				#
				found="false"
				# scan each index entry for the search term in the filename
				if echo "$episode" | rev | cut -d'/' -f1 | rev | grep -q --ignore-case "$searchQuery";then
					# write the data
					cat "$episode" >> "$outputPath"
					found="true"
				fi
				# only search the index file if the filename does not match
				if echo "$found" | grep -q "false";then
					# scan the contents of each .index file
					# - cache episode read
					episodeData="$(cat "$episode")"
					# search the contents of the index file
					if echo "$episodeData" | grep -q --ignore-case "$searchQuery";then
						# show the found data
						echo "$episodeData" >> "$outputPath"
					fi
				fi
				# increment the counter
				indexDataCounter=$(( indexDataCounter + 1 ))
			done
		fi
	fi
}
################################################################################
function searchGraphs(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_graphs.index"
	searchIndex "$webDirectory" "$webDirectory/graphs/graphs.index" "$searchQuery" "$outputPath"
}
################################################################################
function searchPortal(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_portal.index"
	searchIndex "$webDirectory" "$webDirectory/portal/portal.index" "$searchQuery" "$outputPath"
}
################################################################################
function searchMusic(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_music.index"
	searchIndex "$webDirectory" "$webDirectory/music/music.index" "$searchQuery"  "$outputPath"
}
################################################################################
function searchRepos(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_repos.index"
	searchIndex "$webDirectory" "$webDirectory/repos/repos.index" "$searchQuery" "$outputPath"
}
################################################################################
function searchChannels(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_live_channels.index"
	#indexPath="/var/cache/2web/web/live/index/"
	indexPath="/var/cache/2web/web/data.db"
	INFO "Scanning $indexPath for $searchQuery"
	# if the output path does not yet exist then build it
	if ! test -f "$outputPath";then
		# if the index path exists
		if test -f "$indexPath";then
			# get all the index files
			#indexData="$(find "$indexPath" -type f)"
			indexData="$(sqlite3 -cmd ".timeout 60000" "$indexPath" "select * from \"_channels\";")"
			indexDataLength=$(echo "$indexData" | wc -l)
			indexDataCounter=0
			# search though each of the index files
			echo "$indexData" | while read episode;do
				INFO "Scanning $indexPath for $searchQuery [$indexDataCounter/$indexDataLength]"
				#
				found="false"
				# scan each index entry for the search term in the filename
				if echo "$episode" | rev | cut -d'/' -f1 | rev | grep -q --ignore-case "$searchQuery";then
					# write the data
					cat "$episode" >> "$outputPath"
					found="true"
				fi
				# only search the index file if the filename does not match
				if echo "$found" | grep -q "false";then
					# scan the contents of each .index file
					# - cache episode read
					episodeData="$(cat "$episode")"
					# search the contents of the index file
					if echo "$episodeData" | grep -q --ignore-case "$searchQuery";then
						# show the found data
						echo "$episodeData" >> "$outputPath"
					fi
				fi
				# increment the counter
				indexDataCounter=$(( indexDataCounter + 1 ))
			done
		fi
	fi
}
################################################################################
function searchWeather(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_weather_stations.index"
	# check for any stations
	foundStations=$(find "/var/cache/2web/web/weather/data/" -type f -name "station_*.index")
	# if there are any stations
	foundData="no"
	# draw the stations in a listcard for correct scaling
	# read all the weather station names
	echo "$foundStations" | while read stationFilePath;do
		fileData="$(cat "$stationFilePath")"
		# search the contents of the index file
		if echo "$fileData" | grep -q --ignore-case "$searchQuery";then
			if echo "$foundData" | grep -q "no";then
				foundData="yes"
				echo "<div class='listCard'>" > "$outputPath"
			fi
			# show the found data
			echo "$fileData" >> "$outputPath"
		fi
	done
	# close the
	if echo "$foundData" | grep -q "yes";then
		echo "</div>" >> "$outputPath"
	fi
}
################################################################################
function searchDict(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	# search the dict server
	outputPath="$webDirectory/search/${searchSum}_definitions.index"
	#	# build the dict data
	definitionData=$(dict "$searchQuery" | tr -s '\n')
	firstDef="yes"
	firstLine="yes"
	# only build if there are found definitions
	if [[ $(echo "$definitionData" | wc -l) -gt 3 ]];then
		{
			echo "<div class='settingListCard'>"
			echo "<div class='listCard'>"
		} > "$outputPath"
		#
		definitionCounter=0
		#
		echo "$definitionData" | while read line;do
			# ignore the first line since it only lists the number of found definitions
			#if echo "$line" | grep -q --ignore-case "^.*defintions found$";then
			if echo "$firstLine" | grep -q --ignore-case "yes";then
				firstLine="no"
			elif echo "$line" | grep -q --ignore-case "^From.*:$";then
				# increment the counter
				definitionCounter=$(( definitionCounter + 1 ))
				# check if this is the first def or if a footer is needed
				if echo "$firstDef" | grep -q "yes";then
					firstDef="no"
				else
					{
					echo "</pre>"
					echo "</div>"
					} >> "$outputPath"
				fi

				# this is a line starting a new definition
				{
					echo "<div class='searchDef'>"
					echo "<h3>Definition $definitionCounter</h3>"
					echo "<pre class=''>"
					echo "$line"
				} >> "$outputPath"
			else
				# draw the line of text from inside the definition
				{
					echo "$line"
				} >> "$outputPath"
			fi
		done
		# close the last def found
		{
			echo "</pre>"
			echo "</div>"
			echo "</div>"
			echo "</div>"
		} >> "$outputPath"
	fi
}
################################################################################
function searchIndex(){
	webDirectory=$1
	indexPath=$2
	searchQuery=$3
	outputPath=$4
	INFO "Scanning $indexPath for $searchQuery"
	# if the output path does not yet exist then build it
	if ! test -f "$outputPath";then
		# if the index path exists
		if test -f "$indexPath";then
			indexData="$(cat "$indexPath" | uniq )"
			indexDataLength=$(echo "$indexData" | wc -l)
			indexDataCounter=0
			# search though each of the index files
			echo "$indexData" | while read episode;do
				INFO "Scanning $indexPath for $searchQuery [$indexDataCounter/$indexDataLength]"
				#
				found="false"
				# scan each index entry for the search term in the filename
				if echo "$episode" | rev | cut -d'/' -f1 | rev | grep -q --ignore-case "$searchQuery";then
					ALERT "Found Match in filename $episode"
					# write the data
					cat "$episode" >> "$outputPath"
					found="true"
				fi
				# only search the index file if the filename does not match
				if echo "$found" | grep -q "false";then
					# scan the contents of each .index file
					# - cache episode read
					episodeData="$(cat "$episode")"
					# search the contents of the index file
					if echo "$episodeData" | grep -q --ignore-case "$searchQuery";then
						ALERT "Found Match in file data $episode"
						# show the found data
						echo "$episodeData" >> "$outputPath"
					fi
				fi
				# increment the counter
				indexDataCounter=$(( indexDataCounter + 1 ))
			done
		fi
	fi
}
################################################################################
incrementProgressFile(){
	webDirectory="$1"
	searchSum="$2"
	# increment the progress file by 1
	echo "$(( $(cat "$webDirectory/search/${searchSum}_progress.index") + 1 ))"  > "$webDirectory/search/${searchSum}_progress.index"
}
################################################################################
function search(){
	# lauch a search of all database infomation
	searchQuery=$1
	searchQuery=$(echo "$searchQuery" | sed "s/_/ /g")
	addToLog "INFO" "Starting Search" "$searchQuery"
	webDirectory=$(webRoot)

	searchSum="$2"

	# check for parallel processing and count the cpus
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi

	# launch each of the search types in a parallel queue so sections can be processed in parallel
	date "+%s" > "$webDirectory/search/${searchSum}_processing.index"

	echo "0" > "$webDirectory/search/${searchSum}_progress.index"
	echo "10" > "$webDirectory/search/${searchSum}_total.index"

	# search the dictionary server
	searchDict "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the shows
	searchShows "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the portal
	searchPortal "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the enabled graphs
	searchGraphs "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the music
	searchMusic "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the repos
	searchRepos "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the new playlists
	searchNew "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the weather stations
	searchWeather "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the channels and radio channels
	searchChannels "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search all episodes
	searchEpisodes "$webDirectory" "$searchQuery" "$searchSum" &
	incrementProgressFile "$webDirectory" "$searchSum"
	waitQueue 0.2 "$totalCPUS"
	# search the wiki pages

	# block the queue
	blockQueue 1

	# mark the search as complete this will stop the page refresh

	date "+%s" > "$webDirectory/search/${searchSum}_finished.index"

}
################################################################################
# launch the search
search "$1" "$2"
exit
