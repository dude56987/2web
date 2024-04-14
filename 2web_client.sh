#! /bin/bash
########################################################################
# 2web client is a desktop application to read the client event server
# Copyright (C) 2024  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under  the terms of the GNU General Public License as published by
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
# load the common lib
source /var/lib/2web/common
########################################################################
# load the config file locations for background and event server paths
########################################################################
# check for default client event server path
if ! test -f "/etc/2web/client-event.cfg";then
	echo "http://$(hostname).local/client/?events" > "/etc/2web/client-event.cfg"
fi
# load the event server path from the config
eventServerPath="$(cat "/etc/2web/client-event.cfg")"
# check for background config
if ! test -f "/etc/2web/client-bg.cfg";then
	# read the custom client config for the background path
	echo "http://$(hostname).local/randomFanart.php" > "/etc/2web/client-bg.cfg"
fi
# set the background image path
eventServerBackgroundPath=$(cat "/etc/2web/client-bg.cfg")
################################################################################
function updateBackground(){
	# change the background on a loop
	eventServerBackgroundPath="$1"
	ALERT "Loading background changing loop process from source at '$eventServerBackgroundPath'..."
	# set the background update time as a global scope
	backgroundUpdateTime=$(date "+%s")
	# run the forever loop
	while true;do
		# update the random background if more than 60 seconds have passed
		currentTime=$(date "+%s")
		if [[ $((currentTime - backgroundUpdateTime)) -gt 60 ]];then
			# set a random background after a delay this will be activated by a heartbeat
			feh --bg-scale "$eventServerBackgroundPath"
			# update the background update time
			backgroundUpdateTime=$(date "+%s")
			# reset the screensaver timer
			# - this will prevent the screensaver or power saving from activating
			xset s reset
		fi
		# sleep 10 seconds between checks
		sleep 10
	done
}
################################################################################
function runEventServer(){
	# setup the connection to the event server and loop forever
	eventServerPath="$1"
	ALERT "Loading Main event server at '$eventServerPath'..."
	# run the client loop that will run forever
	while true;do
		# read events from the event server, this can fail if the connection fails
		curl -N --no-progress-meter "$eventServerPath" | while read -r event;do
			# read the events and run the aproprate commands on the client machine
			echo "$event"
			if echo "$event" | grep -q "play=";then
				# pull the url to be played back
				playUrl="$(echo "$event" | cut -d'=' -f2- )"
				echo "playing url = '$playUrl'"
				# close existing vlc instances
				killall vlc
				# send a space to wake the monitor if it is asleep
				xdotool key space
				# vlc can not play "LONG" urls so almost everything fails create a .strm file and play that
				echo "$HOME/clientPlayBuffer.strm"
				echo "$playUrl" > "$HOME/clientPlayBuffer.strm"
				# launch the player in the background
				vlc --fullscreen --play-and-exit "$HOME/clientPlayBuffer.strm" &
			elif echo "$event" | grep -q "playpause";then
				xdotool key space
			elif echo "$event" | grep -q "stop";then
				# stop video playback
				xdotool key s
				# kill all VLC instances
				killall vlc
			elif echo "$event" | grep -q "skipforward";then
				xdotool key Right
			elif echo "$event" | grep -q "skipbackward";then
				xdotool key Left
			elif echo "$event" | grep -q "volumeup";then
				xdotool key Up
			elif echo "$event" | grep -q "volumedown";then
				xdotool key Down
			elif echo "$event" | grep -q "mute";then
				xdotool key m
			elif echo "$event" | grep -q "configure";then
				killall vlc
				killall pavucontrol
				# launch vlc to configure the settings
				vlc &
				# launch the volume control interface
				pavucontrol &
			elif echo "$event" | grep -q "subs";then
				# enable/disable subtitles
				xdotool key shift+v
			elif echo "$event" | grep -q "switchoutput";then
				# go to the next available audio output
				xdotool key shift+a
			elif echo "$event" | grep -q "switchsub";then
				# switch to the next available subtitle
				xdotool key v
			elif echo "$event" | grep -q "switchaudio";then
				# switch to the next available audio track
				xdotool key b
			elif echo "$event" | grep -q "nexttrack";then
				xdotool key n
			elif echo "$event" | grep -q "previoustrack";then
				xdotool key p
			elif echo "$event" | grep -q "audiosource";then
				# change the audio source used by vlc
				xdotool key shift+a
			fi
		done
	done
}
################################################################################
# lauch the background updater in the background
updateBackground "$eventServerBackgroundPath" &
# run the event server this will keep running forever
runEventServer "$eventServerPath"
