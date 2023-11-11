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
	# Open a video link
	#
	# $1 = kodiLocation :
	# $2 = link :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	link=$2
	# remove spaces for json parsing
	link=$(echo "$link" | sed "s/ /%20/g")
	playerId=$(getPlayerId "$kodiLocation")
	request="{\"jsonrpc\": \"2.0\", \"method\": \"Player.Open\", \"params\": {\"item\": {\"file\": \"$link\"}}, \"playerid\": $playerId }"
	# print the request before sending it
	echo $request | jq
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary  "$request" "http://$kodiLocation/jsonrpc" | jq
	#
}
################################################################################
function next(){
	# Go to next item in playlist
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary "{\"jsonrpc\": \"2.0\", \"method\": \"Player.GoTo\", \"params\":{\"playerid\": 0, \"next\"}, \"id\": 1}" "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function previous(){
	# Go to previous item in playlist
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary "{\"jsonrpc\": \"2.0\", \"method\": \"Player.GoTo\", \"params\":{\"playerid\": 0, \"previous\"}, \"id\": 0}" "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputLeft(){
	# Input the left move button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Left"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputRight(){
	# Input the right move button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Right"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputDown(){
	# Input the down move button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Down"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputUp(){
	# Input the down move button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Up"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputSelect(){
	# Input the select button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Select"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputHome(){
	# Input the home button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Home"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputContext(){
	# Input the go back button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.ContextMenu"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function volumeUp(){
	# Move the volume up one increment
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{ "jsonrpc": "2.0", "method": "Application.SetVolume", "params": { "volume": "increment" }, "id": 1 }' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function volumeDown(){
	# Move the volume down one increment
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{ "jsonrpc": "2.0", "method": "Application.SetVolume", "params": { "volume": "decrement" }, "id": 1 }' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function volumeMute(){
	# Toggle the mute
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{ "jsonrpc": "2.0", "method": "Application.SetMute", "params": { "mute": "toggle" }, "id": 1 }' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function inputGoBack(){
	# Input the go back button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Input.Back"}' "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function sendText(){
	# Input the select button
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	messageText=$2
	startDebug
	# run json
	curl --silent -H "content-type: application/json;" --data-binary "{\"jsonrpc\": \"2.0\", \"method\": \"Input.SendText\", \"params\": {\"$messageText\"}" "http://$kodiLocation/jsonrpc"
	stopDebug
}
################################################################################
function playPause(){
	# Play or Pause the current video
	#
	# $1 = kodiLocation :
	# $2 = link :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug

	# combine the request  string into a single string
	request='{"jsonrpc": "2.0", "method": "Player.PlayPause", "params":{"playerid": '
	request="$request$(getPlayerId "$kodiLocation")"
	tempString=' }, "id": 1}"'
	request="$request$tempString"
	# print request for debug
	echo "$request" | jq
	# cleaup the request
	request=$(echo "$request" | jq)
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary "$request" "http://$kodiLocation/jsonrpc" | jq

	stopDebug
}
################################################################################
function skipForward(){
	# Skip ahead one step in the video
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug

	# combine the request  string into a single string
	request='{"jsonrpc": "2.0", "method": "Player.Seek", "params":{"playerid": '
	request="$request$(getPlayerId "$kodiLocation")"
	#tempString=', "step": "smallforward" }, "id": 1}"'
	#tempString=', "value": { "seconds": 60 } }, "id": 1}"'
	tempString=', "value": { "step": "smallforward" } }, "id": 1}"'
	request="$request$tempString"
	# print request for debug
	echo "$request" | jq
	# cleaup the request
	request=$(echo "$request" | jq)
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary "$request" "http://$kodiLocation/jsonrpc" | jq

	stopDebug
}
################################################################################
function skipBackward(){
	# Skip back one step in the video
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug

	# combine the request  string into a single string
	request='{"jsonrpc": "2.0", "method": "Player.Seek", "params":{"playerid": '
	request="$request$(getPlayerId "$kodiLocation")"
	#tempString=', "step": "smallbackward" }, "id": 1}"'
	#tempString=', "value": { "increment": -60 } }, "id": 1}"'
	tempString=', "value": { "step": "smallbackward" } }, "id": 1}"'
	request="$request$tempString"
	# print request for debug
	echo "$request" | jq
	# cleaup the request
	request=$(echo "$request" | jq)
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary "$request" "http://$kodiLocation/jsonrpc" | jq

	stopDebug
}
################################################################################
function stopPlayer(){
	# Play or Pause the current video
	#
	# $1 = kodiLocation :
	# $2 = link :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	startDebug

	# combine the request  string into a single string
	request='{"jsonrpc": "2.0", "method": "Player.Stop", "params":{"playerid": '
	request="$request$(getPlayerId "$kodiLocation")"
	tempString=' }, "id": 1}"'
	request="$request$tempString"
	# print request for debug
	echo "$request" | jq
	# cleaup the request
	request=$(echo "$request" | jq)
	# run playback for link on kodi client
	curl --silent -H "content-type: application/json;" --data-binary "$request" "http://$kodiLocation/jsonrpc" | jq

	stopDebug
}
################################################################################
function getPlayerId(){
	# Get the kodi player id number
	#
	# $1 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	kodiLocation=$1
	# run json to get the player id
	playerId=$(curl --silent -H "content-type: application/json;" --data-binary '{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 99}' "http://$kodiLocation/jsonrpc" | jq ".result" | jq ".[]" | jq ".playerid")
	# if the playerid is a number
	if echo "$playerId" | grep -q "[0-9]";then
		# return the active player id
		echo "$playerId"
	else
		# if no player can be found set the player as 0 for a new player
		echo "0"
	fi
}
################################################################################
function launchJsonRequest(){
	# launch a json request and print the output
	#
	# $1 = request :
	# $2 = kodiLocation :
	#
	# RETURN REMOTE_ACTION
	request=$1
	# cleanup the request by escaping the parathensis
	request=$(echo "$request" | sed "s/\"/\"/g")
	kodiLocation=$2
	# run the curl request
	curl --silent -H "content-type: application/json;" --data-binary "$request" "http://$kodiLocation/jsonrpc"
}
################################################################################
function loadPlayers(){
	# load all the players configured in kodi2web
	#
	# RETURN REMOTE_ACTION
	loadConfigs "/etc/2web/kodi/players.cfg" "/etc/2web/kodi/players.d/" "/etc/2web/config_default/kodi2web_players.cfg" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | tr -s "\n" | shuf
}
################################################################################
function main(){
	# Read and interpert all arguments
	#
	# RETURN BOOL
	if [ "$1" == "-o" ] || [ "$1" == "--open" ] || [ "$1" == "open" ] ;then
		# play link on all configured kodi clients
		loadPlayers | while read -r player;do
			openLink "$player" "$2"
		done
	elif [ "$1" == "-p" ] || [ "$1" == "--play" ] || [ "$1" == "play" ] ;then
		loadPlayers | while read -r player;do
			playPause "$player" &
		done
	elif [ "$1" == "-P" ] || [ "$1" == "--pause" ] || [ "$1" == "pause" ] ;then
		loadPlayers | while read -r player;do
			playPause "$player" &
		done
	elif [ "$1" == "-st" ] || [ "$1" == "--stop" ] || [ "$1" == "stop" ] ;then
		loadPlayers | while read -r player;do
			stopPlayer "$player" &
		done
	elif [ "$1" == "-n" ] || [ "$1" == "--next" ] || [ "$1" == "next" ] ;then
		loadPlayers | while read -r player;do
			next "$player" &
		done
	elif [ "$1" == "-pr" ] || [ "$1" == "--previous" ] || [ "$1" == "previous" ] ;then
		loadPlayers | while read -r player;do
			previous "$player" &
		done
	elif [ "$1" == "-l" ] || [ "$1" == "--left" ] || [ "$1" == "left" ] ;then
		loadPlayers | while read -r player;do
			inputLeft "$player" &
		done
	elif [ "$1" == "-r" ] || [ "$1" == "--right" ] || [ "$1" == "right" ] ;then
		loadPlayers | while read -r player;do
			inputRight "$player" &
		done
	elif [ "$1" == "-u" ] || [ "$1" == "--up" ] || [ "$1" == "up" ] ;then
		loadPlayers | while read -r player;do
			inputUp "$player" &
		done
	elif [ "$1" == "-d" ] || [ "$1" == "--down" ] || [ "$1" == "down" ] ;then
		loadPlayers | while read -r player;do
			inputDown "$player" &
		done
	elif [ "$1" == "-s" ] || [ "$1" == "--select" ] || [ "$1" == "select" ] ;then
		loadPlayers | while read -r player;do
			inputSelect "$player" &
		done
	elif [ "$1" == "-h" ] || [ "$1" == "--home" ] || [ "$1" == "home" ] ;then
		loadPlayers | while read -r player;do
			inputHome "$player" &
		done
	elif [ "$1" == "-b" ] || [ "$1" == "--back" ] || [ "$1" == "back" ] ;then
		loadPlayers | while read -r player;do
			inputGoBack "$player" &
		done
	elif [ "$1" == "-c" ] || [ "$1" == "--context" ] || [ "$1" == "context" ] ;then
		loadPlayers | while read -r player;do
			inputContext "$player" &
		done
	elif [ "$1" == "-S" ] || [ "$1" == "--send" ] || [ "$1" == "send" ] ;then
		loadPlayers | while read -r player;do
			sendText "$player" "$2" &
		done
	elif [ "$1" == "-V" ] || [ "$1" == "--volumeup" ] || [ "$1" == "volumeup" ] ;then
		loadPlayers | while read -r player;do
			volumeUp "$player" &
		done
	elif [ "$1" == "-v" ] || [ "$1" == "--volumedown" ] || [ "$1" == "volumedown" ] ;then
		loadPlayers | while read -r player;do
			volumeDown "$player" &
		done
	elif [ "$1" == "-m" ] || [ "$1" == "--mute" ] || [ "$1" == "mute" ] ;then
		loadPlayers | while read -r player;do
			volumeMute "$player" &
		done
	elif [ "$1" == "-sf" ] || [ "$1" == "--skip-forward" ] || [ "$1" == "skip-forward" ] ;then
		loadPlayers | while read -r player;do
			skipForward "$player" &
		done
	elif [ "$1" == "-sb" ] || [ "$1" == "--skip-backward" ] || [ "$1" == "skip-backward" ] ;then
		loadPlayers | while read -r player;do
			skipBackward "$player" &
		done
	elif [ "$1" == "-I" ] || [ "$1" == "--id" ] || [ "$1" == "id" ] ;then
		loadPlayers | while read -r player;do
			getPlayerId "$player" &
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
