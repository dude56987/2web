#! /bin/bash
########################################################################
# kodi2web playback control application
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
function openLink(){
	# Play a video link
	#
	# $1 = kodiLocation :
	# $2 = link :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	link=$2
	startDebug
	# remove spaces for json parsing
	link=$(echo "$link" | sed "s/ /%20/g")
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary "{\"jsonrpc\": \"2.0\", \"method\": \"Player.Open\", \"params\": {\"item\": {\"file\": \"$link\"}}, \"playerid\": 0}" "http://$kodiLocation/jsonrpc"
	stopDebug
	#
}
################################################################################
function main(){
	################################################################################
	if [ "$1" == "-o" ] || [ "$1" == "--open" ] || [ "$1" == "open" ] ;then
		# play link on all configured kodi clients
		players=$(loadConfigs "/etc/2web/kodi/players.cfg" "/etc/2web/kodi/players.d/" "/etc/2web/config_default/kodi2web_players.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )
		echo "$players" | while read -r player;do
			openLink "$player" "$2"
		done
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		echo "Kodi Player Help"
		echo "--open"
		echo "	Play a link on all configured kodi2web players."
	fi
}
################################################################################
main "$@"
exit
