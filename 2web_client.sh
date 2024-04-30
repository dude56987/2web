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
		fi
		# sleep 20 seconds between checks
		sleep 20
	done
}
################################################################################
function stopOtherTasks(){
	# stop all the other tasks running
	killall vlc
	killall pavucontrol
	killall xfce4-notifyd-config
}
################################################################################
function runEventServer(){
	# setup the connection to the event server and loop forever
	eventServerPath="$1"
	ALERT "Loading Main event server at '$eventServerPath'..."
	# Setup sleep timer variables
	sleepStartTime=0
	sleepSetTime=0
	sleepTimerOn=false
	awake=true
	# run the client loop that will run forever
	while true;do
		# read events from the event server, this can fail if the connection fails
		curl -N --no-progress-meter "$eventServerPath" | while read -r event;do
			# read the events and run the aproprate commands on the client machine
			# - the heartbeat will be recieved roughly every 10 seconds regardless of actual events
			#   to keep the server connection alive
			echo "$event"
			if echo "$event" | grep -q "play=";then
				# pull the url to be played back
				playUrl="$(echo "$event" | cut -d'=' -f2- )"
				echo "playing url = '$playUrl'"
				# send a space key to wake the client screen if it is asleep
				xdotool key space
				# close existing vlc instances
				stopOtherTasks
				# vlc can not play "LONG" urls so almost everything fails create a .strm file and play that
				echo "$HOME/clientPlayBuffer.strm"
				echo "$playUrl" > "$HOME/clientPlayBuffer.strm"
				# launch the player in the background
				vlc --fullscreen --play-and-exit "$HOME/clientPlayBuffer.strm" &
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "playpause";then
				xdotool key space
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "stop";then
				# stop video playback
				xdotool key s
				# kill all VLC instances
				stopOtherTasks
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "skipforward";then
				xdotool key Right
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "skipbackward";then
				xdotool key Left
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "volumeup";then
				xdotool key Up
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "volumedown";then
				xdotool key Down
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "mute";then
				xdotool key m
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "configure";then
				stopOtherTasks
				# launch vlc to configure the settings
				vlc &
				# launch the volume control interface
				pavucontrol &
				# launch the notification theme configuration interface
				xfce4-notifyd-config &
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "subs";then
				# enable/disable subtitles
				xdotool key shift+v
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "switchoutput";then
				# go to the next available audio output
				xdotool key shift+a
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "switchsub";then
				# switch to the next available subtitle
				xdotool key v
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "switchaudio";then
				# switch to the next available audio track
				xdotool key b
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "nexttrack";then
				xdotool key n
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "previoustrack";then
				xdotool key p
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "switchoutput";then
				# change the audio source used by vlc
				xdotool key shift+a
				# set the sleep status
				awake=true
			elif echo "$event" | grep -q "nightmode";then
				# reset the color
				redshift -x
				# set the color wavelength low enough for night mode
				# - 1000k is the fully red light
				redshift -O 1000
			elif echo "$event" | grep -q "duskmode";then
				# reset the color
				redshift -x
				# set the color wavelength halfway to night mode
				# - 1000k is the fully red light
				redshift -O 2500
			elif echo "$event" | grep -q "daymode";then
				# reset the color temp
				redshift -x
			elif echo "$event" | grep -q "blank";then
				# turn off all active tools
				stopOtherTasks
				# turn off the screen
				xset dpms force off
				# set the sleep status
				awake=false
			elif echo "$event" | grep -q "sleep=";then
				# set a timer to turn off the display after a delay
				sleepTimerOn=true
				# sleep time in minutes
				sleepStartTime="$(date "+%s")"
				sleepSetTime="$(echo "$event" | cut -d'=' -f2- )"
				# notify the user the sleep timer is set
				notify-send "Sleep timer set for '$sleepSetTime' minutes"
				# convert sleep time into seconds for use with timers
				sleepSetTime="$(( sleepSetTime * 60 ))"
			fi
			if $sleepTimerOn;then
				# if the sleep timer is on check the current time is not past the sleep timer poweroff time
				if [ $(( $( date "+%s" ) - sleepStartTime )) -gt $sleepSetTime ];then
					# stop all tools and turn the display off
					stopOtherTasks
					# turn off the screen
					xset dpms force off
					# reset sleep timer variables
					sleepStartTime=0
					sleepSetTime=0
					sleepTimerOn=false
					# set the sleep status
					awake=false
				fi
			fi
			# if the screen is supposted to be awake
			if $awake;then
				# reset the screensaver timer
				# - this will prevent the screensaver or power saving from activating
				# - this will also turn off the active screensaver
				xset s reset
			fi
		done
	done
}
################################################################################
# lauch the background updater in the background
updateBackground "$eventServerBackgroundPath" &
# run the event server this will keep running forever
runEventServer "$eventServerPath"
