#! /bin/bash
########################################################################
# rss2nfo converts rss feeds from remote websites into nfo libaries
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
source "/var/lib/2web/common"
################################################################################
#set -x
################################################################################
function processEpisode(){
	# Generate episode nfo structure from the json object and download thumbnails if they are found
	#
	#processEpisode "$rssObject" "$processedEpisodes" "$totalEpisodes" "$finishedSources" "$totalSources" &
	#
	# RETURN FILES
	rssObject=$1
	processedEpisodes=$2
	totalEpisodes=$3
	finishedSources=$4
	totalSources=$5
	#
	showTitle=$(echo "$rssObject" | jq -r ".playlist_title")
	if ! test -f "/var/cache/2web/generated/rss/$showTitle/tvshow.nfo";then
		INFO "Shows:[$finishedSources/$totalSources] Episodes:[$finishedEpisodes/$totalEpisodes] - Creating show $showTitle"
		# create the show directory
		createDir "/var/cache/2web/generated/rss/$showTitle/"
		showSource=$(echo "$rssObject" | jq -r ".playlist_id")
		# generate a tvshow.nfo file from the base json data
		{
			#echo "<?xml version='1.0' encoding='UTF-8'?>"
			echo "<tvshow>"
			echo "<title>$showTitle</title>"
			echo "<studio>Internet</studio>"
			echo "<genre>Internet</genre>"
			echo "<plot>Source URL: $showSource</plot>"
			echo "<premiered>$(date +%F)</premiered>"
			echo "<director>$showTitle</director>"
			echo "</tvshow>"
		} > "/var/cache/2web/generated/rss/$showTitle/tvshow.nfo"
	fi
	# get the air date
	airDate=$(echo "$rssObject" | jq -r ".timestamp")
	# get airdate info from airDate timestamp
	airDateYear=$(date --date="@$airDate" "+%Y")
	airDateMonth=$(date --date="@$airDate" "+%m")
	airDateDay=$(date --date="@$airDate" "+%d")
	airDate="$airDateDay/$airDateMonth/$airDateYear"
	# get the episode title
	episodeTitle=$(echo "$rssObject" | jq -r ".title")
	# get the episode number
	episodeNumber=$finishedEpisodes
	# get the episode number from the playlist index
	#episodeNumber=$(echo "$rssObject" | jq -r ".playlist_index")

	# build the episode if the episode files do not exist
	if test -f "/var/cache/2web/generated/rss/$showTitle/$airDateYear/s${airDateYear}e$episodeNumber - $episodeTitle.strm";then
		INFO "Shows:[$finishedSources/$totalSources] Episodes:[$finishedEpisodes/$totalEpisodes] - $showTitle : Episode Already Processed $episodeTitle"
	else
		INFO "Shows:[$finishedSources/$totalSources] Episodes:[$finishedEpisodes/$totalEpisodes] - $showTitle : Adding Episode $episodeTitle"

		plot=$(echo "$rssObject" | jq -r ".description")
		runtime=$(echo "$rssObject" | jq -r ".duration")
		# get thumbnail data if it is available
		thumbnail=$(echo "$rssObject" | jq -r ".thumbnail")
		# create the season directory
		createDir "/var/cache/2web/generated/rss/$showTitle/$airDateYear/"
		# check the thumbnail is a real link
		#if echo "$thumbnail" | grep -q --ignore-case "http" | grep -q --ignore-case "://";then
		if [ $(echo "$thumbnail" | wc --bytes) -gt 6 ];then
			downloadThumbnail "$thumbnail" "/var/cache/2web/generated/rss/$showTitle/$airDateYear/s${airDateYear}e$episodeNumber - $episodeTitle-thumb" ".jpg"
		fi
		{
			#echo "<?xml version='1.0' encoding='UTF-8'?>"
			echo "<episodedetails>"
			echo "<season>$airDateYear</season>"
			echo "<episode>$episodeNumber</episode>"
			# set the series title
			echo "<showtitle>$showTitle</showtitle>"
			# Set the title grabed previously to build the filename
			echo "<title>$episodeTitle</title>"
			# get the director information
			echo "<director>$showTitle</director>"
			echo "<credits>$showTitle</credits>"
			# get the runtime if it is available
			echo "<fileinfo>"
			echo "<streamdetails>"
			echo "<video>"
			# make sure the runtime is a interger
			if [[ "$runtime" =~ ^[0-9]+$ ]];then
				if [ "$runtime" -gt 0 ];then
					echo "<durationinseconds>$runtime</durationinseconds>"
				else
					# default runtime guess is 15 minutes
					echo "<durationinseconds>600</durationinseconds>"
				fi
			else
				# default runtime guess is 15 minutes
				echo "<durationinseconds>600</durationinseconds>"
			fi
			echo "</video>"
			echo "</streamdetails>"
			echo "</fileinfo>"
			echo "<plot>$plot</plot>"
			echo "<aired>$airdate</aired>"
			# set the last processed time of the episode
			echo "<lastProcessed>$(date "+%s")</lastProcessed>"
			# end the nfo file
			echo "</episodedetails>"
		} > "/var/cache/2web/generated/rss/$showTitle/$airDateYear/s${airDateYear}e$episodeNumber - $episodeTitle.nfo"
		# get the playback url
		playbackUrl=$(echo "$rssObject" | jq -r ".url")
		# generate a .strm file from the media found in the rss
		echo "$playbackUrl" > "/var/cache/2web/generated/rss/$showTitle/$airDateYear/s${airDateYear}e$episodeNumber - $episodeTitle.strm"
	fi
}
################################################################################
rss2nfo_update(){
	# Update all RSS feeds
	#
	# RETURN FILES

	# this will launch a processing queue that downloads updates to rsss
	INFO "Loading up sources..."
	# check for defined sources
	rssSources=$(loadConfigs "/etc/2web/rss/sources.cfg" "/etc/2web/rss/sources.d/" "/etc/2web/config_default/rss2nfo_sources.cfg")

	################################################################################
	# create show and cache directories
	createDir "/var/cache/2web/downloads/rss2nfo/"
	createDir "/var/cache/2web/downloads/rss2nfo/cache/"
	# generated directory
	createDir "/var/cache/2web/generated/rss/"

	# check for parallel processing and count the cpus
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi
	totalSources=$(echo "$rssSources" | wc -l)
	finishedSources=1
	# read each of the rss sources
	# - shuffle the order of sources for processing
	echo "$rssSources" | shuf | while read -r rssSource;do
		# generate a sum for the source
		rssSum=$(echo "$rssSource" | sha512sum | cut -d' ' -f1)
		# check for existing cached rss json data limited to once per day
		if cacheCheck "/var/cache/2web/downloads/rss2nfo/cache/$rssSum.cfg" "1";then
			# use yt-dlp to download the rss and convert it into json
			INFO "Shows:[$finishedSources/$totalSources] - Downloading rss and converting to json from $rssSource"
			rssAsJson=$(timeout 120 /usr/local/bin/yt-dlp --flat-playlist --abort-on-error -j "$rssSource")
			# only write valid rss data to the cache
			if [ 5 -lt $(echo "$rssAsJson" | wc -c) ];then
				# cache the rss that has been convered to json
				echo "$rssAsJson" > "/var/cache/2web/downloads/rss2nfo/cache/$rssSum.cfg"
			else
				# log failed downloads
				addToLog "ERROR" "Failed Download" "The download of RSS from '$rssSource' has failed!"
				# load the cached json data
				rssAsJson="$(cat "/var/cache/2web/downloads/rss2nfo/cache/$rssSum.cfg")"
			fi
		else
			# load the cached json data
			rssAsJson="$(cat "/var/cache/2web/downloads/rss2nfo/cache/$rssSum.cfg")"
		fi
		# read each item in the json array
		totalEpisodes=$(echo "$rssAsJson" | wc -l)
		finishedEpisodes=1
		# for each item read the json data and put it into a nfo file
		# - reverse line sorting order to be oldest to newest
		echo "$rssAsJson" | jq -c | tac | while read -r rssObject;do
			# process rss episode
			processEpisode "$rssObject" "$processedEpisodes" "$totalEpisodes" "$finishedSources" "$totalSources" &
			waitQueue 0.5 "$totalCPUS"
			finishedEpisodes=$(( $finishedEpisodes + 1 ))
		done
		finishedSources=$(( $finishedSources + 1 ))
	done
	blockQueue 1
}
################################################################################
function nuke(){
	echo "########################################################################"
	echo "[INFO]: NUKE is disabled for rss2nfo..."
	echo "[INFO]: This is so you can disable the module but keep metadata."
	echo "[INFO]: Use 'rss2nfo reset' to remove all downloaded metadata."
	echo "########################################################################"
	ALERT "Remove NFO data generated from the downloaded RSS file"
	rm -rv /var/cache/2web/generated/rss/
	ALERT "The RSS is still stored as json in /var/cache/2web/downloads/rss/"
}
################################################################################
main(){
	if [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		checkModStatus "rss2nfo"
		lockProc "rss2nfo"
		rss2nfo_update $@
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "rss2nfo"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "rss2nfo"
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		checkModStatus "rss2nfo"
		pip3 install --break-system-packages --upgrade yt-dlp
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		lockProc "rss2nfo"
		ytdl2kodi_reset_cache
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		lockProc "rss2nfo"
		nuke
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "rss2nfo Version: "
		cat /usr/share/2web/version_rss2nfo.cfg
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/rss2nfo.txt"
	else
		checkModStatus "rss2nfo"
		lockProc "rss2nfo"
		rss2nfo_update $@
		drawLine
		echo "NFO Library generated at /var/cache/2web/generated/rss/"
		drawLine
	fi
}
################################################################################
main "$@"
exit
