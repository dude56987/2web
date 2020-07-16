#! /bin/bash
########################################################################
# hackbox-system-monitor CLI for administration
# Copyright (C) 2016  Carl J Smith
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
set -x
#export PS4='+(${BASH_SOURCE}:${LINENO}): ${FUNCNAME[0]:+${FUNCNAME[0]}(): }'
export PS4='+ ${LINENO}	|	'
# set tab size to 4 to make output more readable
tabs 4
########################################################################
ripXmlTag(){
	data=$1
	tag=$2
	# ignore the case of the tag and rip the case used
	theTag=$(echo "$data" | grep --ignore-case --only-matching "<$tag>")
	theTag="${theTag//<}"
	theTag="${theTag//>}"
	# rip the tag from the data
	#echo "$data" | grep -i "<$theTag>" | sed "s/<$theTag>//g" | sed "s/<\/$theTag>//g"
	output=$(echo "$data" | grep "<$theTag>")
	output="${output//<$theTag>}"
	output="${output//<\/$theTag>}"
	# remove special characters that will gunk up the works
	echo "$output"
	return 0
}
########################################################################
cleanText(){
	# remove punctuation from text
	echo "$1" | sed "s/[[:punct:]]//g"
}
########################################################################
main(){
	if [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		echo "########################################################################"
		echo "# nfo2web CLI for administration"
		echo "# Copyright (C) 2020  Carl J Smith"
		echo "#"
		echo "# This program is free software: you can redistribute it and/or modify"
		echo "# it under the terms of the GNU General Public License as published by"
		echo "# the Free Software Foundation, either version 3 of the License, or"
		echo "# (at your option) any later version."
		echo "#"
		echo "# This program is distributed in the hope that it will be useful,"
		echo "# but WITHOUT ANY WARRANTY; without even the implied warranty of"
		echo "# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the"
		echo "# GNU General Public License for more details."
		echo "#"
		echo "# You should have received a copy of the GNU General Public License"
		echo "# along with this program.  If not, see <http://www.gnu.org/licenses/>."
		echo "########################################################################"
		echo "HELP INFO"
		echo "This is the nfo2web administration and update program."
		echo "To return to this menu use 'nfo2web help'"
		echo "Other commands are listed below."
		echo ""
		echo "update"
		echo "  This will update the webpages and refresh the database."
		echo "########################################################################"
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		################################################################################
		# Create website containing info and links to one or more .nfo directories
		# containing shows
		################################################################################
		# GENERATED SITE EXAMPLE
		################################################################################
		# - index.html : contains links to health.html,recent.html,and each showTitle.html
		#   missing data. Links to each show can be found here.
		#  - health.html : Page contains a list of found issues with nfo libary
		#   + Use duplicate checker script to generate this page
		#  - recent.html : Contains links to all episodes added in the past 14 days
		#   + Use 'find /pathToLibary/ -type f -mtime -14' to find files less than 14
		#     days old
		#  - showTitle/index.html : Each show gets its own show page that contains links to
		#    all available episodes. Sorted Into seasons.
		#   + seasons are on the show page, not seprate
		#_NOTES_________________________________________________________________________
		# - Should allow multuple directories to be set as libaries
		# - Should create symlinks to pictures and video files
		# - Downloaded video files should be symlinked into web directory
		# - Downloaded files should be linked with html5 video player
		# - Should convert .strm file links to html5 video player pages
		# - Include download button on episode webpage
		# - Should convert youtube links from kodi to embeded player links
		# - Should create its own web directory
		################################################################################
		# load the libary directory
		if [ -f /etc/nfo2web/libaries.cfg ];then
			libaries=$(cat /etc/nfo2web/libaries.cfg)
		else
			mkdir -p /var/cache/nfo2web/libary/
			echo "/var/cache/nfo2web/libary" > /etc/nfo2web/libaries.cfg
			libaries="/var/cache/nfo2web/libary"
		fi
		# the webdirectory is a cache where the generated website is stored
		if [ -f /etc/nfo2web/web.cfg ];then
			webDirectory=$(cat /etc/nfo2web/web.cfg)
		else
			mkdir -p /var/cache/nfo2web/web/
			echo "/var/cache/nfo2web/web" > /etc/nfo2web/web.cfg
			webDirectory="/var/cache/nfo2web/web"
		fi
		mkdir -p $webDirectory
		# create the homepage path
		homePagePath="$webDirectory/index.html"
		touch $homePagePath
		{
			echo "<html>"
			echo "<head>"
			echo "</head>"
			echo "<body>"
		} > $homePagePath
		IFS_BACKUP=$IFS
		IFS=$(echo -e "\n")
		# read each libary from the libary config, single path per line
		for libary in $libaries;do
			# check if the libary directory exists
			echo "Check if directory exists at $libary"
			if [ -e $libary ];then
				# read each tvshow directory from the libary
				for show in $libary/*;do
					echo "show = '$show'"
					#show=$(echo "$show" | sed "s/.*\///g")
					#echo "show filtered = '$show'"
					# if the show directory exists
					##if [ -d "$libary/$show" ];then
					# load update the tvshow.nfo file and get the metadata required for
					showMeta=$(cat "$show/tvshow.nfo")
					showTitle=$(ripXmlTag "$showMeta" "title")
					posterPath="$show/poster.png"
					fanartPath="$show/fanart.png"
					# building the webpage for the show
					showPagePath="$webDirectory/$showTitle/index.html"
					mkdir -p $(echo "$showPagePath" | grep -o ".*\/")
					touch $showPagePath
					# build top of show webpage containing all of the shows meta info
					echo "<head>" >> $showPagePath
					echo "</head>" >> $showPagePath
					echo "<body>" >> $showPagePath
					echo "<h1>$showTitle</h1>" >> $showPagePath
					echo "<ul>" >> $showPagePath
					# generate the episodes based on .nfo files
					for season in $show/*;do
						# if the folder is a directory that means a season has been found
						echo "<div>" >> $showPagePath
						# read each episode in the series
						for episode in $season/*.nfo;do
							if [ -f "$episode" ];then
								echo "################################################################################"
								echo "Processing Episode $episode"
								echo "################################################################################"
								# for each episode build a page for the episode
								nfoInfo=$(cat "$episode")
								# rip the episode title
								showTitle=$(ripXmlTag "$nfoInfo" "showTitle")
								episodeTitle=$(ripXmlTag "$nfoInfo" "title")
								episodeSeason=$(ripXmlTag "$nfoInfo" "season")
								# create the episode page path
								#episodePagePath=$(echo "$episode" | sed "s/\.nfo$/.html/g")
								episodePagePath="$webDirectory/$showTitle/$episodeSeason/$episodeTitle.html"
								mkdir -p "$webDirectory/$showTitle/$episodeSeason/"
								# start rendering the html
								{
									echo "<html>"
									echo "<body>"
									echo "<h1>$showTitle</h1>"
									echo "<h2>$episodeTitle</h2>"
								} > "$episodePagePath"
								# link the episode nfo file
								ln -s "$episode" "$webDirectory/$showTitle/$episodeSeason/$episodeTitle.nfo"
								# find the videofile refrenced by the nfo file
								if [ -f "${episode//.nfo/.mkv}" ];then
									videoPath="${episode//.nfo/.mkv}"
									sufix=".mkv"
								elif [ -f "${episode//.nfo/.mp4}" ];then
									videoPath="${episode//.nfo/.mp4}"
									sufix=".mp4"
								elif [ -f "${episode//.nfo/.mp3}" ];then
									videoPath="${episode//.nfo/.mp3}"
									sufix=".mp3"
								elif [ -f "${episode//.nfo/.ogv}" ];then
									videoPath="${episode//.nfo/.ogv}"
									sufix=".ogv"
								elif [ -f "${episode//.nfo/.ogg}" ];then
									videoPath="${episode//.nfo/.ogg}"
									sufix=".ogg"
								elif [ -f "${episode//.nfo/.strm}" ];then
									videoPath="${episode//.nfo/.strm}"
									videoPath=$(cat "$videoPath")
									sufix=".strm"
								fi
								# set the video type based on the found video path
								if echo "$videoPath" | grep ".mp3";then
									mediaType="audio"
									mimeType="audio/mp3"
								elif echo "$videoPath" | grep ".ogg";then
									mediaType="audio"
									mimeType="audio/ogg"
								elif echo "$videoPath" | grep ".ogv";then
									mediaType="video"
									mimeType="video/ogv"
								elif echo "$videoPath" | grep ".mp4";then
									mediaType="video"
									mimeType="video/mp4"
								elif echo "$videoPath" | grep ".mkv";then
									mediaType="video"
									mimeType="video/mkv"
								else
									# if no correct video type was found use video only tag
									# this is a failover for .strm files
									mediaType="video"
									mimeType="video"
								fi
								echo "videoPath = $videoPath"
								episodeVideoPath="${episode//.nfo/$sufix}"
								echo "episodeVideoPath = $videoPath"
								# link the video from the libary to the generated website
								ln -s "$episodeVideoPath" "$webDirectory/$showTitle/$episodeSeason/$episodeTitle$sufix"
								thumbnail="$episode-thumb.jpg"
								thumbnailPath="$webDirectory/$showTitle/$episodeSeason/$episodeTitle-thumb.png"
								# check for a local thumbnail
								if [ -f $thumbnail ];then
									# link thumbnail into output directory
									ln -s "$thumbnail" "$thumbnailPath"
								else
									if echo $nfoInfo | grep "thumb";then
										# download the thumbnail
										curl "$(ripXmlTag "$nfoInfo" "thumb")" > "$thumbnailPath"
									fi
								fi
								#TODO: here is where strm files need checked for Plugin: eg. youtube strm files
								if echo "$videoPath" | grep --ignore-case "plugin://";then
									# change the video path into a video id to make it embedable
									#yt_id=${videoPath//plugin:\/\/plugin.video.youtube\/play\/?video_id=}
									yt_id=$(echo "$videoPath" | tr -d "plugin://plugin.video.youtube/play/?video_id=")
									{
										# embed the youtube player
										echo "<iframe width='560' height='315'"
										echo "src='https://www.youtube-nocookie.com/embed/$yt_id'"
										echo "frameborder='0'"
										echo "allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'"
										echo "allowfullscreen>"
										echo "</iframe>"
									} >> "$episodePagePath"
									#echo "$videoPath" tr -d 'plugin://plugin.video.youtube/play/?video_id='
								else
									{
										# build the html5 media player for local and remotly accessable media
										echo "<$mediaType poster='$episodeTitle-thumb.png' controls>"
										echo "<source src='$videoPath' type='$mimeType'>"
										echo "</$mediaType>"
									} >> "$episodePagePath"
								fi
								# create a hard link
								{
									echo "<a href='$episodeTitle$sufix'>"
									echo "$episodeTitle$sufix"
									echo "</a>"
									echo "</body>"
									echo "</html>"
								} >> "$episodePagePath"
								################################################################################
								# add the episode to the show page
								################################################################################
								echo "<li>" >> "$showPagePath"
								echo "<a href='$episodeSeason/$episodeTitle.html'>" >> "$showPagePath"
								echo "$episodeTitle" >> "$showPagePath"
								echo "</a>" >> "$showPagePath"
								echo "</li>" >> "$showPagePath"
							else
								echo "[WARNING]: The file '$episode' could not be found!"
							fi
						done
						echo "</ul>" >> $showPagePath
						echo "</div>" >> "$showPagePath"
					done
					echo "</body>" >> "$showPagePath"
					echo "</html>" >> "$showPagePath"
					# add show page to the home page index
					echo "<a href='$showTitle/'>" >> "$homePagePath"
					echo "$showTitle" >> "$homePagePath"
					echo "</a>" >> "$homePagePath"
				done
			fi
		done
		echo "</body>" >> "$homePagePath"
		echo "</html>" >> "$homePagePath"
		# read the tvshow.nfo files for each show
			################################################################################
			# Create the show link on index.html
			# - read poster.png as show button
			################################################################################
			# Create the series page
			# - While generating the series page, you must generate the episode page in its
			#   entirity, then proceed with generating the next episode link in the seasons
			#   page
			################################################################################
			# - Create show metadata at the top of the page
			# - set fanart.png as a static unscrolling background on the show page
			# load all nfo files in show into a list
				# find /pathToLibary/ -type f
				# read show title and remove tags with sed
			# feed nfo files into generator to extract seasons and episode .nfo data
		################################################################################
	else
		# if no arguments are given run the update then help commands.
		main update
		main help
	fi
}
main "$@"
