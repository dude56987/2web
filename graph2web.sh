#! /bin/bash
########################################################################
# graph2web creates a web interface to munin graphs on 2web server
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
################################################################################
# enable debug log
#set -x
source /var/lib/2web/common
################################################################################
function buildVnstatDevice(){
	device=$1

	# generate vnstat graphs for graph section from the vnstat sqlite database
	vnstati -i "$device" -hg -o "/var/cache/2web/generated/graphs/vnstat_${device}-day.png"
	if ! test -f "/var/cache/2web/generated/graphs/vnstat_${device}-day.png";then
		# this is for compatiblity with older versions of vnstat, -hg is only in new versions
		vnstati -i "$device" -h -o "/var/cache/2web/generated/graphs/vnstat_${device}-day.png"
	fi
	vnstati -i "$device" -d 7 -o "/var/cache/2web/generated/graphs/vnstat_${device}-week.png"
	vnstati -i "$device" -m -o "/var/cache/2web/generated/graphs/vnstat_${device}-month.png"
	vnstati -i "$device" -y -o "/var/cache/2web/generated/graphs/vnstat_${device}-year.png"
	# generate additional summary graphs for vnstat
	vnstati -i "$device" -s -o "/var/cache/2web/generated/graphs/vnstat_${device}-summary.png"
	# generate top activity months of all time
	vnstati -i "$device" -t -o "/var/cache/2web/generated/graphs/vnstat_${device}-top.png"

}
################################################################################
function vnstatGen(){
	# generate vnstat graphs for old and new versions of vnstat

	# create a path to store generated graphs for each device
	mkdir -p /var/cache/2web/generated/graphs/
	# check for the sqlite database from new versions of vnstat
	if test -f /var/lib/vnstat/vnstat.db;then
		# read vnstat database for interface entries
		sqlite3 /var/lib/vnstat/vnstat.db "select name from interface" | while read device;do
			buildVnstatDevice "$device"
		done
	else
		# read older versions of vnstat database
		for device in /var/lib/vnstat/*;do
			# get the device name based on the file name of the databases
			device=$(echo "$device" | rev | cut -d'/' -f1 | rev)
			# vnstat lists the devices in a directory
			buildVnstatDevice "$device"
		done
	fi
}
################################################################################
function update(){
	# this will launch a processing queue that downloads updates to graph
	webDirectory=$(webRoot)
	checkModStatus "graph2web"

	createDir "$webDirectory/graphs/"
	createDir "$webDirectory/kodi/graphs/"
	# set ownership of munin plugin directory to be able to edit them in the web interface
	chown -R www-data:www-data /etc/munin/plugins/

	# copy over the php files for the graph
	linkFile "/usr/share/2web/templates/graphs.php" "$webDirectory/graphs/index.php"

	searchPath="/var/cache/2web/generated/graphs"
	# build vnstat graphs
	if test -f /usr/bin/vnstati;then
		vnstatGen
	fi
	# find all custom generated graphs
	find "$searchPath/" -maxdepth 1 -mindepth 1 -name "*-day.png" | while read graphPath;do
		# get the filename and path
		fileName=$(echo "$graphPath" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1 | sed "s/-day//g")
		if test -f "$webDirectory/graphs/$fileName/index.php";then
			# if the graph has been added to the server already skip this
			INFO "Already processed $fileName graph..."
		else
			# copy over the metadata for the template to use
			titleClean=$(echo "$fileName" | sed "s/_/ /g")

			# the graph does not exist on the webserver but is enabled in munin so activate it
			createDir "$webDirectory/graphs/$fileName/"
			createDir "$webDirectory/kodi/graphs/$fileName/"

			# add the title config file for the php page
			echo "$titleClean" > "$webDirectory/graphs/$fileName/title.cfg"

			# read each graph path and build a directory for each set of graphs
			linkFile "$searchPath/$fileName-top.png" "$webDirectory/graphs/$fileName/top.png"
			linkFile "$searchPath/$fileName-summary.png" "$webDirectory/graphs/$fileName/summary.png"
			linkFile "$searchPath/$fileName-hour.png" "$webDirectory/graphs/$fileName/hour.png"
			linkFile "$searchPath/$fileName-day.png" "$webDirectory/graphs/$fileName/day.png"
			linkFile "$searchPath/$fileName-week.png" "$webDirectory/graphs/$fileName/week.png"
			linkFile "$searchPath/$fileName-month.png" "$webDirectory/graphs/$fileName/month.png"
			linkFile "$searchPath/$fileName-year.png" "$webDirectory/graphs/$fileName/year.png"

			# creates kodi directory links
			linkFile "$searchPath/$fileName-top.png" "$webDirectory/kodi/graphs/$fileName/top.png"
			linkFile "$searchPath/$fileName-summary.png" "$webDirectory/kodi/graphs/$fileName/summary.png"
			linkFile "$searchPath/$fileName-hour.png" "$webDirectory/kodi/graphs/$fileName/hour.png"
			linkFile "$searchPath/$fileName-day.png" "$webDirectory/kodi/graphs/$fileName/day.png"
			linkFile "$searchPath/$fileName-week.png" "$webDirectory/kodi/graphs/$fileName/week.png"
			linkFile "$searchPath/$fileName-month.png" "$webDirectory/kodi/graphs/$fileName/month.png"
			linkFile "$searchPath/$fileName-year.png" "$webDirectory/kodi/graphs/$fileName/year.png"

			# build the index entry for the graph on the main index
			{
				echo "<a class='showPageEpisode' href='/graphs/$fileName/'>"
				echo "	<h2 class='title'>"
				echo "  	$titleClean"
				echo "	</h2>"
				echo "	<img class='graphLinkThumbnail' loading='lazy' src='/graphs/$fileName/day.png'>"
				echo "</a>"
			} > "$webDirectory/graphs/$fileName/graphs.index"

			SQLaddToIndex "$webDirectory/graphs/$fileName/graphs.index" "$webDirectory/data.db" "graphs"
			SQLaddToIndex "$webDirectory/graphs/$fileName/graphs.index" "$webDirectory/data.db" "all"
			# update last updated times
			date "+%s" > /var/cache/2web/web/new/all.cfg
			date "+%s" > /var/cache/2web/web/new/graphs.cfg

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
	###################################################################################################
	# find each of the graphs provided by munin
	searchPath="/var/cache/munin/www/localdomain/localhost.localdomain"
	# get the list of enabled graphs
	enabledGraphs=$(find "/etc/munin/plugins/" -type f)
	find "$searchPath/" -maxdepth 1 -mindepth 1 -type f -name "*-day.png" | while read graphPath;do
		# get the filename and path
		fileName=$(echo "$graphPath" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1 | sed "s/-day//g")
		#
		if test -f "$webDirectory/graphs/$fileName/index.php";then
			# check if the graph is enabled
			if ! test -f "/etc/munin/plugins/$fileName";then
				# the plugin is disabled remove it from the web interface
				rm -rv "$webDirectory/graphs/$fileName/"
				rm -rv "$webDirectory/kodi/graphs/$fileName/"
			fi
			# if the graph has been added to the server already skip this
			INFO "Already processed $fileName graph..."
		else
			# the graph does not exist on the webserver
			if test -f "/etc/munin/plugins/$fileName";then
				# if the graph is enabled in munin

				createDir "$webDirectory/graphs/$fileName/"
				createDir "$webDirectory/kodi/graphs/$fileName/"
				# read each graph path and build a directory for each set of graphs
				linkFile "$searchPath/$fileName-day.png" "$webDirectory/graphs/$fileName/day.png"
				linkFile "$searchPath/$fileName-week.png" "$webDirectory/graphs/$fileName/week.png"
				linkFile "$searchPath/$fileName-month.png" "$webDirectory/graphs/$fileName/month.png"
				linkFile "$searchPath/$fileName-year.png" "$webDirectory/graphs/$fileName/year.png"

				# add kodi directory links
				linkFile "$searchPath/$fileName-day.png" "$webDirectory/kodi/graphs/$fileName/day.png"
				linkFile "$searchPath/$fileName-week.png" "$webDirectory/kodi/graphs/$fileName/week.png"
				linkFile "$searchPath/$fileName-month.png" "$webDirectory/kodi/graphs/$fileName/month.png"
				linkFile "$searchPath/$fileName-year.png" "$webDirectory/kodi/graphs/$fileName/year.png"

				# copy over the metadata for the template to use
				titleClean=$(echo "$fileName" | sed "s/_/ /g" )
				echo "$titleClean" > "$webDirectory/graphs/$fileName/title.cfg"

				# build the index entry for the graph on the main index
				{
					echo "<a class='showPageEpisode' href='/graphs/$fileName/'>"
					echo "	<h2 class='title'>"
					echo "  	$titleClean"
					echo "	</h2>"
					echo "	<img class='graphLinkThumbnail' loading='lazy' src='/graphs/$fileName/day.png'>"
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
	webDirectory=$(webRoot)
	# remove the kodi and web graph files
	rm -rv "$webDirectory/graphs/" || echo "No files found in graph web directory..."
	rm -rv "$webDirectory/kodi/graphs/" || echo "No files found in graph web directory..."
	rm -rv "$webDirectory/new/graphs.index" || echo "No graph index..."
	rm -rv "$webDirectory/random/graphs.index" || echo "No graph index..."
	rm -v  "$webDirectory/web_cache/widget_random_graphs.index"
	rm -v  "$webDirectory/web_cache/widget_new_graphs.index"
	rm -v  "$webDirectory/activityGraph.png"
	# remove graphs generated by graph2web
	rm -rv "/var/cache/2web/generated/graphs/"
}
################################################################################
main(){
	# set the theme of the lines in CLI output
	LINE_THEME="lines"
	#
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
