#! /bin/bash -x
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
ripXmlTag(){
	data=$1
	tag=$2
	# rip the tag from the data
	echo "$data" | grep "<$tag>" | sed "s/<$tag>//g" | sed "s/<\/$tag>//g"
}
ripXmlTag2(){
	data=$1
	tag=$2
	# cut out a tag and return the contents
	"$info" | cut -d"<$tag>" -f2 | cut -d"</$tag>" -f1
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
		echo "<html>" > $homePagePath
		echo "<head>" >> $homePagePath
		echo "</head>" >> $homePagePath
		echo "<body>" >> $homePagePath
		# read each libary from the libary config, single path per line
		for libary in $libaries;do
			# check if the libary directory exists
			echo "Check if directory exists at $libary"
			if [ -e $libary ];then
				# read each tvshow directory from the libary
				for show in $libary/*;do
					echo "show = '$show'"
					show=$(echo "$show" | sed "s/.*\///g")
					echo "show filtered = '$show'"
					# if the show directory exists
					##if [ -d "$libary/$show" ];then
					# load update the tvshow.nfo file and get the metadata required for
					# building the webpage for the show
					showPagePath="$webDirectory/$show/index.html"
					mkdir -p $(echo "$showPagePath" | grep -o ".*\/")
					touch $showPagePath
					showMeta=$(cat "$libary/$show/tvshow.nfo")
					showTitle=$(ripXmlTag "$showMeta" "title")
					posterPath="$libary/$show/poster.png"
					fanartPath="$libary/$show/fanart.png"
					# build top of show webpage containing all of the shows meta info
					echo "<head>" >> $showPagePath
					echo "</head>" >> $showPagePath
					echo "<body>" >> $showPagePath
					echo "<h1>$showTitle</h1>" >> $showPagePath
					echo "<ul>" >> $showPagePath
					echo "<li></li>" >> $showPagePath
					echo "<li></li>" >> $showPagePath
					echo "</ul>" >> $showPagePath
					# generate the episodes based on .nfo files
					nfoFiles="$libary/$show/"
					for season in $libary/$show/*;do
						# if the folder is a directory that means a season has been found
						echo "<div>" >> $showPagePath
						# read each episode in the series
						for episode in $season*.nfo;do
							# for each episode build a page for the episode
							nfoInfo=$(cat "$episode")
							# rip the episode title
							showTitle=$(ripXmlTag "$nfoInfo" "showTitle")
							episodeTitle=$(ripXmlTag "$nfoInfo" "title")
							season=$(ripXmlTag "$nfoInfo" "season")
							# create the episode page path
							#episodePagePath=$(echo "$episode" | sed "s/\.nfo$/.html/g")
							episodePagePath="$webDirectory/$showTitle/$season/$episodeTitle.html"
							mkdir -p "$webDirectory/$showTitle/$season/"
							# start rendering the html
							echo "<html>" > $episodePagePath
							echo "<body>" >> $episodePagePath
							echo "<h1>$showTitle</h1>" >> $episodePagePath
							echo "<h2>$episodeTitle</h2>" >> $episodePagePath
							# link the episode nfo file
							ln -s $episode "$webDirectory/$show/$season/$showTitle.nfo"
							echo "<video controls>" >> $episodePagePath
							# find the videofile refrenced by the nfo file
							episodeVideoPath=$(echo "$episodePagePath" | sed "s/\.html/.mkv/g")
							if [ -f "$episodeVideoPath" ];then
								videoPath=$libary/$show/$season/$episode.mkv
								echo "<source src='$episode.mkv' type='video/mkv'>" >> $episodePagePath
							fi
							episodeVideoPath=$(echo "$episodePagePath" | sed "s/\.html/.mp4/g")
							if [ -f "$episodeVideoPath" ];then
								videoPath=$libary/$show/$season/$episode.mp4
								echo "<source src='$episode.mp4' type='video/mp4'>" >> $episodePagePath
							fi
							episodeVideoPath=$(echo "$episodePagePath" | sed "s/\.html/.mp3/g")
							if [ -f "$episodeVideoPath" ];then
								videoPath=$libary/$show/$season/$episode.mp3
								echo "<source src='$episode.mp3' type='audio/mp3'>" >> $episodePagePath
							fi
							episodeVideoPath=$(echo "$episodePagePath" | sed "s/\.html/.ogv/g")
							if [ -f "$episodeVideoPath" ];then
								videoPath=$libary/$show/$season/$episode.ogv
								echo "<source src='$episode.ogv' type='video/ogv'>" >> $episodePagePath
							fi
							episodeVideoPath=$(echo "$episodePagePath" | sed "s/\.html/.ogg/g")
							if [ -f "$episodeVideoPath" ];then
								videoPath=$libary/$show/$season/$episode.ogg
								echo "<source src='$episode.ogg' type='audio/ogg'>" >> $episodePagePath
							fi
							episodeVideoPath=$(echo "$episodePagePath" | sed "s/\.html/.strm/g")
							if [ -f "$episodeVideoPath" ];then
								echo "<source src='$(cat "$episode")' type='video'>" >> $episodePagePath
							fi
							# link the video from the libary to the generated website
							ln -s "$videoPath" "$episodeVideoPath"
							echo "</video>" >> $episodePagePath
							if [ -f $libary/$show/$season/$episode-thumb.png ];then
								thumbnail="$libary/$show/$season/$episode-thumb.png"
								# link thumbnail into output directory
								ln -s "$thumbnail" "$webDirectory/$showTitle/$season/$episodeTitle-thumb.png"
							elif [ -f $libary/$show/$season/$episode-thumb.jpg ];then
								thumbnail="$libary/$show/$season/$episode-thumb.jpg"
								ln -s "$thumbnail" "$webDirectory/$showTitle/$season/$episodeTitle-thumb.jpg"
							else
								if echo $nfoInfo | grep "thumb";then
									# download the thumbnail
									curl "$(ripXmlTag "$nfoInfo" "thumb")" > $libary/$show/$season/$episode-thumb.png
								fi
							fi
							echo "</body>" >> $episodePagePath
							echo "</html>" >> $episodePagePath
						done
						echo "<a href=''></a>" >> $showPagePath
						echo "</div>" >> $showPagePath
					done
					echo "</body>" >> $showPagePath
					echo "</html>" >> $showPagePath
				done
			fi
		done
		echo "</body>" >> $homePagePath
		echo "</html>" >> $homePagePath
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
main $@
