#! /bin/bash
################################################################################
# enable debug log
#set -x
################################################################################
ALERT(){
	echo;
	echo "$1";
	echo;
}
################################################################################
drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
}
################################################################################
linkFile(){
	# link file if it is a link
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
}
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/2web/nfo/web.cfg ];then
		webDirectory=$(cat /etc/2web/nfo/web.cfg)
	else
		chown -R www-data:www-data "/var/cache/2web/cache/"
		echo "/var/cache/2web/cache/" > /etc/2web/nfo/web.cfg
		webDirectory="/var/cache/2web/cache/"
	fi
	# check for a trailing slash appended to the path
	if [ "$(echo "$webDirectory" | rev | cut -b 1)" == "/" ];then
		# rip the last byte off the string and return the correct path, WITHOUT THE TRAILING SLASH
		webDirectory="$(echo "$webDirectory" | rev | cut -b 2- | rev )"
	fi
	echo "$webDirectory"
}
################################################################################
function cacheCheck(){

	filePath="$1"
	cacheDays="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -mtime "+$cacheDays") ]];then
			# the file is more than "$2" days old, it needs updated
			INFO "File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			INFO "File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		INFO "File does not exist, it must be created $1"
		return 0
	fi
}
################################################################################
function cacheCheckMin(){

	filePath="$1"
	cacheMinutes="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -cmin "+$cacheMinutes") ]];then
			# the file is more than "$2" minutes old, it needs updated
			INFO "File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			INFO "File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		INFO "File does not exist, it must be created $1"
		return 0
	fi
}
################################################################################
getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
################################################################################
function INFO(){
	width=$(tput cols)
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[INFO]: $1$buffer" | cut -b"1-$(( $width - 1 ))")"
	# print the line
	printf "$output\r"
	#echo "$output"
	#printf "$output\n"
}
################################################################################
function ERROR(){
	output=$1
	printf "[ERROR]: $output\n"
}
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
function update(){
	# create the config directory if it does not exist
	createDir /etc/2web/kodi/
	createDir /etc/2web/kodi/location.d/
	# this will launch a processing queue that downloads updates to comics
	INFO "Loading up locations..."
	# check for defined sources
	if ! test -f /etc/2web/kodi/location.cfg;then
		# if no config exists create the default config
		{
			echo "################################################################################"
			echo "# Example Config"
			echo "################################################################################"
			echo "# - Any line starting with # will be a comment and is ignored"
			echo "# - List user:pass hostname and port for kodi remote access."
			echo "# - Each client must only be one line in this config"
			echo "# - Kodi default port is 8080"
			echo "# - Kodi will warn you but does not requre a password"
			echo "#  ex."
			echo "#    user:pass@localhost:8080"
			echo "################################################################################"
			echo "# user:pass@localhost:8080"
		} > /etc/2web/kodi/location.cfg
	fi

	# load sources
	kodiLocations=$(grep -v "^#" /etc/2web/kodi/location.cfg)
	kodiLocations=$(echo -e "$kodiLocations\n$(grep -v --no-filename "^#" /etc/2web/kodi/location.d/*.cfg)")
	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make comics directory
	createDir "$webDirectory/kodi/"
	createDir "$webDirectory/kodi/data/"
	# scan the sources
	ALERT "kodi Locations: $kodiLocations"
	#for comicSource in $comicSources;do
	kodiLocations=$(echo "$kodiLocations" | tr -s '\n')
	echo "$kodiLocations" | while read kodiLocation;do
		ALERT "Scanning location '$kodiLocation'"
		if [ "$( echo "$kodiLocation" | wc -c )" -gt 1 ];then
			# check if kodi is playing something
			activePlayers=$(curl --silent --data-binary '{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 1}' -H 'content-type: application/json;' http://$kodiLocation/jsonrpc | jq ".results" | grep "id" | wc -l)
			if [ $activePlayers -eq 0 ];then
				ALERT "Scan the kodi client at '$kodiLocation'"
				# if no players are active, scan the libary
				curl --silent --data-binary '{ "jsonrpc": "2.0", "method": "VideoLibrary.Scan", "id": "mybash"}' -H 'content-type: application/json;' http://$kodiLocation/jsonrpc
			fi
		else
			ALERT "THE CLIENT COULD NOT BE FOUND!"
		fi
	done
}
################################################################################
cleanText(){
	echo "$1" | tr -d '#`' | tr -d "'" | sed "s/_/ /g"
	return
	# remove punctuation from text, remove leading whitespace, and double spaces
	if [ -f /usr/bin/inline-detox ];then
		echo "$1" | inline-detox --remove-trailing | sed "s/-/ /g" | sed -e "s/^[ \t]*//g" | tr -s ' ' | sed "s/\ /_/g" | tr -d '#`' | tr -d "'" | sed "s/_/ /g"
	else
		# use sed to remove punctuation
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g" | sed "s/\ /_/g" | tr -d '#`'
	fi
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
createDir(){
	if ! test -d "$1";then
		mkdir -p "$1"
		# set ownership of directory and subdirectories as www-data
		chown -R www-data:www-data "$1"
	fi
	chown www-data:www-data "$1"
}
################################################################################
webUpdate(){
	webDirectory=$(webRoot)

	# create the kodi directory
	createDir "$webDirectory/kodi/kodi/"

	# create the web directory
	createDir "$webDirectory/kodi/"

	# link the homepage
	linkFile "/usr/share/2web/templates/kodi.php" "$webDirectory/kodi/index.php"

	# link the random poster script
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/kodi/randomPoster.php"
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/kodi/randomFanart.php"
}
################################################################################
lockProc(){
	# check if system is active
	if test -f "/tmp/kodi2web.active";then
		# system is already running exit
		echo "[INFO]: kodi2web is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/kodi2web.active'."
		exit
	else
		# set the active flag
		touch /tmp/kodi2web.active
		# create a trap to remove kodi2web lockfile
		trap "rm -v /tmp/kodi2web.active" EXIT
	fi
}
################################################################################
main(){
	if test -f "/etc/2web/mod_status/kodi2web.cfg";then
		# the config exists check the config
		if grep -q "enabled" "/etc/2web/mod_status/kodi2web.cfg";then
			# the module is enabled
			echo "Preparing to process..."
		else
			ALERT "MOD IS DISABLED!"
			ALERT "Edit /etc/2web/mod_status/kodi2web.cfg to contain only the text 'enabled' in order to enable the 2web module."
			# the module is not enabled
			# - remove the files and directory if they exist
			exit
		fi
	else
		createDir "/etc/2web/mod_status/"
		# the config does not exist at all create the default one
		# - the default status for graph2web should be disabled
		echo -n "disabled" > "/etc/2web/mod_status/kodi2web.cfg"
		chown www-data:www-data "/etc/2web/mod_status/kodi2web.cfg"
		# exit the script since by default the module is disabled
		exit
	fi
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		# lock the process
		lockProc
		update
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/kodi2web.txt"
	else
		# lock the process
		lockProc
		# gen prelem website
		webUpdate
		# update sources
		update
		# update webpages
		webUpdate
		# display the help
		main --help
		# show the server link at the bottom of the interface
		drawLine
		echo "http://$(hostname).local:80/"
		drawLine
		echo "http://$(hostname).local:80/kodi/"
		drawLine
	fi
}
################################################################################
main "$@"
exit
