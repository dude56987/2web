#! /bin/bash
########################################################################
# 2web_search scans links for services and creates a index for 2web
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
function searchPortal(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}_portal.index"
	searchIndex "$webDirectory" "$webDirectory/portal/portal.index" "$searchQuery" "$outputPath"
}
################################################################################
function searchSQL(){
	# searchSQL $webDirectory $searchQuery $searchSum $outputPath $tableName
	#
	# Function to search the sql index for a search query in a specific table of the database
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	#outputPath="$webDirectory/search/${searchSum}_live_channels.index"
	outputPath="$4"
	# table name may need to be prefixed with a underscore
	tableName="$5"
	#indexPath="/var/cache/2web/web/live/index/"
	indexPath="/var/cache/2web/web/data.db"
	INFO "Scanning $indexPath for $searchQuery"
	# if the output path does not yet exist then build it
	if ! test -f "$outputPath";then
		# if the index path exists
		if test -f "$indexPath";then
			# get all the index files
			#indexData="$(find "$indexPath" -type f)"
			indexData="$(sqlite3 -cmd ".timeout 60000" "$indexPath" "select * from \"$tableName\";")"
			indexDataLength=$(echo "$indexData" | wc -l)
			indexDataCounter=0
			foundDataCounter=0
			# search though each of the index files
			echo "$indexData" | while read episode;do
				INFO "Scanning $indexPath for $searchQuery [$indexDataCounter/$indexDataLength]"
				#
				found="false"
				# scan each index entry for the search term in the filename
				if basename "$episode" | grep -q --ignore-case "$searchQuery";then
					# write the data
					cat "$episode" >> "$outputPath"
					foundDataCounter=$(( $foundDataCounter + 1 ))
					# break the loop if more than 20 episodes are found
					if [[ $foundDataCounter -ge 20 ]];then
						break
					fi
					#
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
	incrementProgressFile "$webDirectory" "$searchSum"
}

################################################################################
function searchWeather(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	outputPath="$webDirectory/search/${searchSum}__weather_stations.index"
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
			#if echo "$foundData" | grep -q "no";then
			#	foundData="yes"
			#	echo "<div class='listCard'>" > "$outputPath"
			#fi
			# show the found data
			echo "$fileData" >> "$outputPath"
		fi
	done
	# close the
	#if echo "$foundData" | grep -q "yes";then
	#	echo "</div>" >> "$outputPath"
	#fi
	incrementProgressFile "$webDirectory" "$searchSum"
}
################################################################################
function searchDict(){
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	# search the dict server
	outputPath="$webDirectory/search/${searchSum}__definitions.index"
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
	incrementProgressFile "$webDirectory" "$searchSum"
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
			foundDataCounter=0
			# search though each of the index files
			echo "$indexData" | while read episode;do
				INFO "Scanning $indexPath for $searchQuery [$indexDataCounter/$indexDataLength]"
				#
				found="false"
				# scan each index entry for the search term in the filename
				if basename "$episode" | grep -q --ignore-case "$searchQuery";then
					ALERT "Found Match in filename $episode"
					# write the data
					cat "$episode" >> "$outputPath"
					# increment the found data counter
					foundDataCounter=$(( $foundDataCounter + 1 ))
					# break the loop if more than 20 episodes are found
					if [[ $foundDataCounter -ge 20 ]];then
						break
					fi
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
	incrementProgressFile "$webDirectory" "$searchSum"
}
#
function singleWikiSearch(){
	#singleWikiSearch "$webDirectory" "$searchSum" "$baseWikiName" "$wikiArticlePath" "$wikiPath" "$searchQuery"
	webDirectory=$1
	searchSum=$2
	baseWikiName=$3
	wikiPath=$4
	searchQuery=$5
	# try to get the wiki title
	if test -f $wikiPath/M/Title;then
		# Get the title directly from the file
		wikiTitle=$(cat "$wikiPath/M/Title")
	else
		# Use the path base name as the name
		wikiTitle="$baseWikiName"
	fi
	maxWikiArticles=20
	foundWikiArticles=0
	# search all articles for the search query
	find "$wikiPath/A/" -type f | while read -r wikiArticlePath;do
		# break loop if there are more than the max number of articles found
		# - user should refine the search terms for more results
		# - if wikis are scanned completely it takes a extreme amount of time
		# check the article is a file
		if test -f "$wikiArticlePath";then
			# for each article in the wiki search the text of the wiki page
			searchResult=$(cat "$wikiArticlePath" | sed -e 's/<[^>]*>//g' | fold --width=80 -s | grep -B 2 -A 2 -m 1 --ignore-case "$searchQuery" | tr "A-Z" "a-z" | sed "s/${searchQuery}/<span class='highlightText'>${searchQuery}<\/span>/g")
			#searchResult=$(cat "$wikiArticlePath" | sed -e 's/<[^>]*>//g' | grep -B 2 -A 2 -m 1 --ignore-case "$searchQuery" | tr "A-Z" "a-z" | sed "s/${searchQuery}/<span class='highlightText'>${searchQuery}<\/span>/g")
			#searchResult=$(w3m -T "text/html" "$wikiArticlePath" | grep -B 2 -A 2 -m 1 --ignore-case "$searchQuery" | sed -e 's/<[^>]*>//g' | tr "A-Z" "a-z" | sed "s/${searchQuery}/<span class='highlightText'>${searchQuery}<\/span>/g")
			#searchResult=$(w3m "$wikiArticlePath" | grep -m 4 --ignore-case "$searchQuery" | sed "s/${searchQuery}/<span class='selected'>${searchQuery}<\/span>/g")
			#searchResult=$(w3m "$wikiArticlePath" | grep -m 4 --ignore-case "$searchQuery" | sed -e 's/<[^>]*>//g')
			#searchResult=$(w3m "$wikiArticlePath" | grep --ignore-case "$searchQuery")
			#searchResult=$(grep --ignore-case "$searchQuery" "$wikiArticlePath" )
		else
			# no search result should be found for anything that is not a file
			searchResult=""
		fi
		if [ $(echo "$searchResult" | wc -c) -gt 7 ];then
			# a article with the search query was found
			baseArticleName=$(basename "$wikiArticlePath")
			{
			echo "<a href='/wiki/$baseWikiName/?article=$baseArticleName'>"
			echo "<div class='titleCard'>"
			echo "<h2>$baseArticleName</h2>"
			echo "<pre>"
			echo "$searchResult"
			echo "</pre>"
			echo "</div>"
			echo "</a>"
			} >> "$webDirectory/search/${searchSum}_wiki:_$wikiTitle.index"
			# check if the max number of articles was found in this wiki
			if [ $foundWikiArticles -ge $maxWikiArticles ];then
				# break the loop if there are to many results
				break
			else
				# increment the loop if a article is found
				foundWikiArticles=$(( $foundWikiArticles + 1 ))
			fi
		fi
	done
	incrementProgressFile "$webDirectory" "$searchSum"
}
################################################################################
function wikiSearch(){
	#wikiSearch "$webDirectory" "$searchQuery" "$searchSum" "$totalCPUS"
	webDirectory=$1
	searchQuery=$2
	searchSum=$3
	totalCPUS=$4
	#
	wikis=$(find "/var/cache/2web/web/wiki/" -maxdepth 1 -mindepth 1 -type d)
	# search each of the wikis
	IFS=$'\n'
	# search each wiki in parallel for matching articles
	for wiki in $wikis;do
		baseWikiName=$(basename "$wiki")
		singleWikiSearch "$webDirectory" "$searchSum" "$baseWikiName" "$wiki" "$searchQuery" &
		waitQueue 0.1 "$totalCPUS"
	done
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
	addToLog "INFO" "Starting Search" "<a href='/search.php?q=$searchQuery'>$searchQuery</a>"
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
	# total number of threads to be launched below here +1 so 100% is complete
	# add the wikis to the total
	if test -d "$webDirectory/wiki/";then
		totalWikis=$(find "$webDirectory/wiki/" -maxdepth 1 -mindepth 1 -type d | wc -l)
	else
		totalWikis=0
	fi
	echo "$(( 24 + $totalWikis ))" > "$webDirectory/search/${searchSum}_total.index"

	# search the dictionary server
	searchDict "$webDirectory" "$searchQuery" "$searchSum" &
	waitQueue 0.2 "$totalCPUS"
	# search the weather stations
	searchWeather "$webDirectory" "$searchQuery" "$searchSum" &
	waitQueue 0.2 "$totalCPUS"
	# search the portal
	searchIndex "$webDirectory" "$webDirectory/portal/portal.index" "$searchQuery" "$webDirectory/search/${searchSum}_portal_links.index" &
	waitQueue 0.2 "$totalCPUS"
	# search the shows
	searchIndex "$webDirectory" "$webDirectory/shows/shows.index" "$searchQuery" "$webDirectory/search/${searchSum}_all_shows.index" &
	waitQueue 0.2 "$totalCPUS"
	# search the movies
	searchIndex "$webDirectory" "$webDirectory/movies/movies.index" "$searchQuery" "$webDirectory/search/${searchSum}_all_movies.index" &
	waitQueue 0.2 "$totalCPUS"
	# search the comics
	searchIndex "$webDirectory" "$webDirectory/comics/comics.index" "$searchQuery" "$webDirectory/search/${searchSum}_all_comics.index" &
	waitQueue 0.2 "$totalCPUS"
	# search the graphs
	searchIndex "$webDirectory" "$webDirectory/graphs/graphs.index" "$searchQuery" "$webDirectory/search/${searchSum}_graphs.index" &
	waitQueue 0.2 "$totalCPUS"
	# search the repos
	searchIndex "$webDirectory" "$webDirectory/repos/repos.index" "$searchQuery" "$webDirectory/search/${searchSum}_repos.index" &
	waitQueue 0.2 "$totalCPUS"
	# search the music
	searchIndex "$webDirectory" "$webDirectory/music/music.index" "$searchQuery"  "$webDirectory/search/${searchSum}_all_music.index" &
	waitQueue 0.2 "$totalCPUS"
	# music artists
	searchSQL "$webDirectory" "$searchQuery" "$searchSum" "$webDirectory/search/${searchSum}_old_music_artists.index" "_artists" &
	waitQueue 0.2 "$totalCPUS"
	# music albums
	searchSQL "$webDirectory" "$searchQuery" "$searchSum" "$webDirectory/search/${searchSum}_old_music_albums.index" "_albums" &
	waitQueue 0.2 "$totalCPUS"
	# music tracks
	searchSQL "$webDirectory" "$searchQuery" "$searchSum" "$webDirectory/search/${searchSum}_old_music_tracks.index" "_tracks" &
	waitQueue 0.2 "$totalCPUS"
	# search the channels and radio channels
	searchSQL "$webDirectory" "$searchQuery" "$searchSum" "$webDirectory/search/${searchSum}_live_channels.index" "_channels" &
	waitQueue 0.2 "$totalCPUS"
	# search the new playlists all in parallel
	# - skip the combined playlist since this contains all the playlists and will split them into thier search results sections
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/movies.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_movies.index" &
	waitQueue 0.2 "$totalCPUS"
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/episodes.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_episodes.index" &
	waitQueue 0.2 "$totalCPUS"
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/comics.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_comics.index" &
	waitQueue 0.2 "$totalCPUS"
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/music.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_music.index" &
	waitQueue 0.2 "$totalCPUS"
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/tracks.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_tracks.index" &
	waitQueue 0.2 "$totalCPUS"
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/artists.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_artists.index" &
	waitQueue 0.2 "$totalCPUS"
	################################################################################
	searchIndex "$webDirectory" "$webDirectory/new/channels.index" "$searchQuery" "$webDirectory/search/${searchSum}_new_channels.index" &
	waitQueue 0.2 "$totalCPUS"
	# search all episodes this will be a huge database
	searchSQL "$webDirectory" "$searchQuery" "$searchSum" "$webDirectory/search/${searchSum}_old_episodes.index" "_episodes" &
	waitQueue 0.2 "$totalCPUS"
	# search the wiki pages
	wikiSearch "$webDirectory" "$searchQuery" "$searchSum" "$totalCPUS"


	# block the queue
	blockQueue 1

	# mark the search as complete this will stop the page refresh
	date "+%s" > "$webDirectory/search/${searchSum}_finished.index"

	addToLog "INFO" "Finished Search" "<a href='/search.php?q=$searchQuery'>$searchQuery</a>"

}
################################################################################
# launch the search
search "$1" "$2"
exit
