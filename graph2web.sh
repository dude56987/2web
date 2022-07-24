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
	set -x
	# this will launch a processing queue that downloads updates to graph
	webDirectory=$(webRoot)

	if test -f "/etc/2web/mod_status/graph2web.cfg";then
		# the config exists check the config
		if grep -q "enabled" "/etc/2web/mod_status/graph2web.cfg";then
			# the module is enabled
			echo "Preparing to process graphs..."
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
		echo "enabled" > "/etc/2web/mod_status/graph2web.cfg"
		chown www-data:www-data "/etc/2web/mod_status/graph2web.cfg"
		# exit the script since by default the module is disabled
		exit
	fi

	createDir "$webDirectory/graphs/"

	# copy over the php files for the graph
	linkFile "/usr/share/2web/templates/graphs.php" "$webDirectory/graphs/index.php"

	# find each of the graphs provided by munin
	searchPath="/var/cache/munin/www/localdomain/localhost.localdomain"
	find "$searchPath/" -maxdepth 1 -mindepth 1 -type f -name "*-day.png" | while read graphPath;do
		# get the filename and path
		fileName=$(echo "$graphPath" | rev | cut -d'/' -f1 | rev | cut -d'.' -f1 | sed "s/-day//g")

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
	done
	if test -f "$webDirectory/new/graphs.index";then
		chown www-data:www-data "$webDirectory/new/graphs.index"
		# limit the new list to 200 entries
		tempList=$(cat "$webDirectory/new/graphs.index" | uniq | tail -n 200 )
		echo "$tempList" > "$webDirectory/new/graphs.index"
	fi
	if test -f "$webDirectory/random/graphs.index";then
		# set permission on indexes
		chown www-data:www-data "$webDirectory/random/graphs.index"
		tempList=$(cat "$webDirectory/random/graphs.index" | uniq )
		echo "$tempList" > "$webDirectory/random/graphs.index"
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
function lockCheck(){
	if test -f "/tmp/graph2web.active";then
		# system is already running exit
		echo "[INFO]: graph2web is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/graph2web.active'."
		exit
	else
		# set the active flag
		touch /tmp/graph2web.active
		# create a trap to remove graph2web lockfile
		trap "rm /tmp/graph2web.active" EXIT
	fi
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
		# remove the kodi and web graph files
		nuke
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# enable all available plugins to munin
		munin-node-configure --suggest | sh
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/graph2web.txt"
	else
		main --update
		main --webgen
		main --help
	fi
}
################################################################################
main "$@"
exit