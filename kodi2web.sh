#! /bin/bash
########################################################################
# kodi2web allows synchronizing server updates with external KODI clients
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
source /var/lib/2web/common
########################################################################
# enable debug log
#set -x
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
	scanAudio="false"
	scanVideo="false"
	if echo "$@" | grep -q -e "audio";then
		scanAudio="true"
	elif echo "$@" | grep -q -e "video";then
		scanVideo="true"
	else
		scanAudio="true"
		scanVideo="true"
	fi
	#for comicSource in $comicSources;do
	kodiLocations=$(echo "$kodiLocations" | tr -s '\n')
	echo "Preparing to update locations\n$kodiLocations\n"
	echo "$kodiLocations" | while read kodiLocation;do
		ALERT "Scanning location '$kodiLocation'"
		if [ "$( echo "$kodiLocation" | wc -c )" -gt 1 ];then
			# check if kodi is playing something
			activePlayers=$(curl --silent --data-binary '{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 1}' -H 'content-type: application/json;' http://$kodiLocation/jsonrpc | jq ".results" | grep "id" | wc -l)
			if [ $activePlayers -eq 0 ];then
				ALERT "Scan the kodi client at '$kodiLocation'"
				# if no players are active, scan the libary
				# check if the music or video
				if [ $scanVideo == "true" ];then
					curl --silent --data-binary '{ "jsonrpc": "2.0", "method": "VideoLibrary.Scan", "id": "mybash"}' -H 'content-type: application/json;' http://$kodiLocation/jsonrpc
				fi
				if [ $scanAudio == "true" ];then
					curl --silent --data-binary '{ "jsonrpc": "2.0", "method": "AudioLibrary.Scan", "id": "mybash"}' -H 'content-type: application/json;' http://$kodiLocation/jsonrpc
				fi
			fi
		else
			ALERT "THE CLIENT COULD NOT BE FOUND!"
		fi
	done
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
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		# check if the mod is enabled
		checkModStatus "kodi2web"
		# lock the process
		lockProc "kodi2web"
		update "$@"
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "kodi2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "kodi2web"
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/kodi2web.txt"
	else
		# check if the mod is enabled
		checkModStatus "kodi2web"
		# lock the process
		lockProc "kodi2web"
		# gen prelem website
		webUpdate
		# update sources
		update "$@"
		# update webpages
		webUpdate
		# display the help
		main --help
		# show the server link at the bottom of the interface
		showServerLinks
		# add the link to this specific module content, this module only updates kodi clients when the library changes
		drawLine
		echo "http://$(hostname).local:80/kodi/"
		drawLine
	fi
}
################################################################################
main "$@"
exit
