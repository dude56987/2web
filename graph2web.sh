#! /bin/bash
########################################################################
# graph2web creates a web interface to munin graphs on 2web server
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
################################################################################
# enable debug log
#set -x
source /var/lib/2web/common
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
	# this will launch a processing queue that downloads updates to graph
	webDirectory=$(webRoot)
	checkModStatus "graph2web"

	createDir "$webDirectory/graphs/"

	# copy over the php files for the graph
	linkFile "/usr/share/2web/templates/graphs.php" "$webDirectory/graphs/index.php"

	# find each of the graphs provided by munin
	searchPath="/var/cache/munin/www/localdomain/localhost.localdomain"
	find "$searchPath/" -maxdepth 1 -mindepth 1 -type f -name "*-day.png" | while read graphPath;do
		# get the filename and path
		fileName=$(echo "$graphPath" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1 | sed "s/-day//g")

		#if cacheCheck "$webDirectory/graphs/$fileName/index.php" 7;then
		if test -f "$webDirectory/graphs/$fileName/index.php";then
			# if the graph has been added to the server already skip this
			INFO "Already processed $fileName graph..."
		else
			# the graph does not exist on the webserver but is enabled in munin so activate it

			createDir "$webDirectory/graphs/$fileName/"
			# read each graph path and build a directory for each set of graphs
			linkFile "$searchPath/$fileName-day.png" "$webDirectory/graphs/$fileName/$fileName-day.png"
			linkFile "$searchPath/$fileName-week.png" "$webDirectory/graphs/$fileName/$fileName-week.png"
			linkFile "$searchPath/$fileName-month.png" "$webDirectory/graphs/$fileName/$fileName-month.png"
			linkFile "$searchPath/$fileName-year.png" "$webDirectory/graphs/$fileName/$fileName-year.png"

			# copy over the metadata for the template to use
			titleClean=$(echo "$fileName" | sed "s/_/ /g" )
			echo "$titleClean" > "$webDirectory/graphs/$fileName/title.cfg"

			# build the index entry for the graph on the main index
			{
				echo "<a class='showPageEpisode' href='/graphs/$fileName/'>"
				echo "	<h2 class='title'>"
				echo "  	$titleClean"
				echo "	</h2>"
				echo "	<img loading='lazy' src='/graphs/$fileName/$fileName-day.png'>"
				echo "</a>"
			} > "$webDirectory/graphs/$fileName/graphs.index"

			SQLaddToIndex "$webDirectory/graphs/$fileName/graphs.index" "$webDirectory/data.db" "graphs"
			SQLaddToIndex "$webDirectory/graphs/$fileName/graphs.index" "$webDirectory/data.db" "all"

			# copy over the php template for the graphs
			linkFile "/usr/share/2web/templates/graph.php" "$webDirectory/graphs/$fileName/index.php"

			# add graphs to graphs.index
			echo "$webDirectory/graphs/$fileName/graphs.index" >> "$webDirectory/graphs/graphs.index"

			# add to new indexes
			echo "$webDirectory/graphs/$fileName/graphs.index" >> "$webDirectory/new/graphs.index"
			echo "$webDirectory/graphs/$fileName/graphs.index" >> "$webDirectory/new/all.index"

			# add to new indexes
			echo "$webDirectory/graphs/$fileName/graphs.index" >> "$webDirectory/random/graphs.index"
			echo "$webDirectory/graphs/$fileName/graphs.index" >> "$webDirectory/random/all.index"
		fi
	done
	if test -f "$webDirectory/new/graphs.index";then
		chown www-data:www-data "$webDirectory/new/graphs.index"
		# limit the new list to 200 entries
		tempList=$(cat "$webDirectory/new/graphs.index" | uniq | tail -n 800 )
		if [ $(diff <(echo "$tempList") <(cat "$webDirectory/new/graphs.index") | wc -l) -eq 0 ];then
			# the list has not changed do not write to disk
			echo "No new graph updates..."
		else
			echo "$tempList" > "$webDirectory/new/graphs.index"
		fi
	fi
	if test -f "$webDirectory/random/graphs.index";then
		# random pulls from all graphs so use the main graph index
		linkFile "$webDirectory/graphs/graphs.index" "$webDirectory/random/graphs.index"
		# set permission on indexes
		chown www-data:www-data "$webDirectory/random/graphs.index"
	fi
	# cleanup the graph index and sort it
	if test -f "$webDirectory/graphs/graphs.index";then
		tempList=$(cat "$webDirectory/graphs/graphs.index" | sort -u )
		echo "$tempList" > "$webDirectory/graphs/graphs.index"
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
	# remove web cached graph data
	rm -rv "$webDirectory"/graphs/*/
	rm -v "$webDirectory"/graphs/graphs.index
	exit
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
function nuke(){
	# remove the kodi and web graph files
	rm -rv $(webRoot)/graphs/ || echo "No files found in graph web directory..."
	rm -rv $(webRoot)/graphs/* || echo "No files found in graph web directory..."
	rm -rv $(webRoot)/new/graphs.index || echo "No graph index..."
	rm -rv $(webRoot)/random/graphs.index || echo "No graph index..."
	rm -v $(webRoot)/web_cache/widget_random_graphs.index
	rm -v $(webRoot)/web_cache/widget_new_graphs.index
}
################################################################################
main(){
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		checkModStatus "graph2web"
		lockProc "graph2web"
		webUpdate
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "graph2web"
		lockProc "graph2web"
		update
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		lockProc "graph2web"
		resetCache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		lockProc "graph2web"
		# remove the kodi and web graph files
		nuke
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# enable all available plugins to munin
		munin-node-configure --suggest | sh
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/graph2web.txt"
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "graph2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "graph2web"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "graph2web Version: "
		cat /usr/share/2web/version_graph2web.cfg
	else
		checkModStatus "graph2web"
		lockProc "graph2web"
		update $@
		webUpdate $@
		main --help
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/graphs/"
		drawLine
		echo "http://$(hostname).local:80/settings/graphs.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
