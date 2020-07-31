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
cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
}
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
	output=$(cleanText "$output")
	echo "$output"
	return 0
}
########################################################################
processEpisode(){
	episode="$1"
	showTitle="$2"
	showPagePath="$3"
	webDirectory="$4"
	echo "[INFO]: checking if episode path exists $episode"
	# check the episode file path exists before anything is done
	if [ -f "$episode" ];then
		echo "################################################################################"
		echo "Processing Episode $episode"
		echo "################################################################################"
		# for each episode build a page for the episode
		nfoInfo=$(cat "$episode")
		# rip the episode title
		episodeShowTitle=$showTitle
		#episodeShowTitle=$(ripXmlTag "$nfoInfo" "showTitle")
		echo "[INFO]: Episode show title = '$episodeShowTitle'"
		episodeShowTitle=$(cleanText "$episodeShowTitle")
		echo "[INFO]: Episode show title after clean = '$episodeShowTitle'"
		episodeTitle=$(ripXmlTag "$nfoInfo" "title")
		echo "[INFO]: Episode title = '$episodeShowTitle'"
		episodeTitle=$(cleanText "$episodeTitle")
		echo "[INFO]: Episode title after clean = '$episodeShowTitle'"
		episodeSeason=$(ripXmlTag "$nfoInfo" "season")
		echo "[INFO]: Episode season = '$episodeSeason'"
		# create the episode page path
		episodePagePath="$webDirectory/$episodeShowTitle/$episodeSeason/$episodeTitle.html"
		echo "[INFO]: Episode page path = '$episodePagePath'"
		echo "[INFO]: Making season directory at '$webDirectory/$episodeShowTitle/$episodeSeason/'"
		mkdir -p "$webDirectory/$episodeShowTitle/$episodeSeason/"
		# start rendering the html
		{
			echo "<html>"
			echo "<head>"
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
			echo "<h1>$episodeShowTitle</h1>"
			echo "<h2>$episodeTitle</h2>"
		} > "$episodePagePath"
		# link the episode nfo file
		echo "[INFO]: linking $episode to $webDirectory/$episodeShowTitle/$episodeSeason/$episodeTitle.nfo"
		ln -s "$episode" "$webDirectory/$episodeShowTitle/$episodeSeason/$episodeTitle.nfo"
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
			mimeType="video/x-matroska"
		else
			# if no correct video type was found use video only tag
			# this is a failover for .strm files
			mediaType="video"
			mimeType="video"
		fi
		echo "[INFO]: mediaType = $mediaType"
		echo "[INFO]: mimeType = $mimeType"
		echo "[INFO]: videoPath = $videoPath"
		episodeVideoPath="${episode//.nfo/$sufix}"
		echo "[INFO]: episodeVideoPath = $videoPath"
		# link the video from the libary to the generated website
		echo "[INFO]: linking '$episodeVideoPath' to '$webDirectory/$episodeShowTitle/$episodeSeason/$episodeTitle$sufix'"
		ln -s "$episodeVideoPath" "$webDirectory/$episodeShowTitle/$episodeSeason/$episodeTitle$sufix"
		# remove .nfo extension and create thumbnail path
		thumbnail="${episode//.nfo}-thumb"
		echo "[INFO]: thumbnail template = $thumbnail"
		echo "[INFO]: thumbnail path 1 = $thumbnail.png"
		echo "[INFO]: thumbnail path 2 = $thumbnail.jpg"
		thumbnailPath="$webDirectory/$episodeShowTitle/$episodeSeason/$episodeTitle-thumb"
		echo "[INFO]: new thumbnail path = $thumbnailPath"
		# check for a local thumbnail
		if [ -f "$thumbnailPath.jpg" ];then
			echo "[INFO]: Thumbnail already linked..."
		elif [ -f "$thumbnailPath.jpg" ];then
			echo "[INFO]: Thumbnail already linked..."
		else
			# no thumbnail has been linked or downloaded
			if [ -f "$thumbnail.png" ];then
				echo "[INFO]: found PNG thumbnail..."
				thumbnailExt=".png"
				# link thumbnail into output directory
				ln -s "$thumbnail.png" "$thumbnailPath.png"
			elif [ -f "$thumbnail.jpg" ];then
				echo "[INFO]: found JPG thumbnail..."
				thumbnailExt=".jpg"
				# link thumbnail into output directory
				ln -s "$thumbnail.jpg" "$thumbnailPath.jpg"
			else
				if echo "$nfoInfo" | grep "thumb";then
					thumbnailLink=$(ripXmlTag "$nfoInfo" "thumb")
					echo "[INFO]: Try to download found thumbnail..."
					echo "[INFO]: Thumbnail found at $thumbnailLink"
					thumbnailExt=".png"
					# download the thumbnail
					curl "$thumbnailLink" > "$thumbnailPath$thumbnailExt"
				fi
			fi
		fi
		#TODO: here is where strm files need checked for Plugin: eg. youtube strm files
		if echo "$videoPath" | grep --ignore-case "plugin://";then
			# change the video path into a video id to make it embedable
			#yt_id=${videoPath//plugin:\/\/plugin.video.youtube\/play\/?video_id=}
			#yt_id=$(echo "$videoPath" | sed "s/^.*\?video_id=//g")
			#yt_id=${videoPath//^.*\?video_id\=/}
			yt_id=${videoPath//*video_id=}
			echo "[INFO]: yt-id = $yt_id"
			ytLink="https://youtube.com/watch?v=$yt_id"
			{
				# embed the youtube player
				echo "<iframe width='560' height='315'"
				echo "src='https://www.youtube-nocookie.com/embed/$yt_id'"
				echo "frameborder='0'"
				echo "allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'"
				echo "allowfullscreen>"
				echo "</iframe>"
				echo "<hr>"
				# create a hard link
				echo "<ul>"
				echo "	<li>"
				echo "		<a href='$ytLink'>"
				echo "			$ytLink"
				echo "		</a>"
				echo "	</li>"
				echo "	<li>"
				# create link to .strm file
				echo "		<a href='$episodeTitle$sufix'>"
				echo "			$episodeTitle$sufix"
				echo "		</a>"
				echo "	</li>"
				echo "</ul>"
			} >> "$episodePagePath"
			#echo "$videoPath" tr -d 'plugin://plugin.video.youtube/play/?video_id='
		elif echo "$videoPath" | grep "http";then
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$episodeTitle-thumb$thumbnailExt' controls>"
				echo "<source src='$videoPath' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<hr>"
				# create a hard link
				echo "<a href='$episodeTitle$sufix'>"
				echo "$episodeTitle$sufix"
				echo "</a>"
			} >> "$episodePagePath"
		else
			{
				# build the html5 media player for local and remotly accessable media
				echo "<$mediaType poster='$episodeTitle-thumb$thumbnailExt' controls>"
				echo "<source src='$episodeTitle$sufix' type='$mimeType'>"
				echo "</$mediaType>"
				echo "<hr>"
				# create a hard link
				echo "<a href='$episodeTitle$sufix'>"
				echo "$episodeTitle$sufix"
				echo "</a>"
			} >> "$episodePagePath"
		fi
		{
			echo "</body>"
			echo "</html>"
		} >> "$episodePagePath"
		################################################################################
		# add the episode to the show page
		################################################################################
		{
			#tempStyle="$episodeSeason/$episodeTitle-thumb$thumbnailExt"
			#tempStyle="background-image: url(\"$tempStyle\")"
			#echo "<a class='showPageEpisode' style='$tempStyle' href='$episodeSeason/$episodeTitle.html'>"
			echo "<a class='showPageEpisode' href='$episodeSeason/$episodeTitle.html'>"
			#echo "	<div>"
			echo "	<img src='$episodeSeason/$episodeTitle-thumb$thumbnailExt'>"
			#echo "	</div>"
			echo "	<h3 class='title'>"
			echo "		$episodeTitle"
			echo "	</h3>"
			echo "</a>"
		} >> "$showPagePath"
	else
		echo "[WARNING]: The file '$episode' could not be found!"
	fi
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
			echo "<style>"
			cat /usr/share/nfo2web/style.css
			echo "</style>"
			echo "</head>"
			echo "<body>"
		} > $homePagePath
		IFS_BACKUP=$IFS
		IFS=$(echo -e "\n")
		# read each libary from the libary config, single path per line
		for libary in $libaries;do
			# check if the libary directory exists
			echo "Check if directory exists at $libary"
			if [ -e "$libary" ];then
				echo "[INFO]: libary exists at '$libary'"
				# read each tvshow directory from the libary
				for show in $libary/*;do
					echo "[INFO]: show path = '$show'"
					################################################################################
					# process page metadata
					################################################################################
					# if the show directory contains a nfo file defining the show
					echo "[INFO]: searching for metadata at $show/tvshow.nfo"
					if [ -f "$show/tvshow.nfo" ];then
						echo "[INFO]: found metadata at $show/tvshow.nfo"
						# load update the tvshow.nfo file and get the metadata required for
						showMeta=$(cat "$show/tvshow.nfo")
						showTitle=$(ripXmlTag "$showMeta" "title")
						echo "[INFO]: showTitle = $showTitle"
						showTitle=$(cleanText "$showTitle")
						echo "[INFO]: showTitle after cleanText() = $showTitle"
						# create directory
						echo "[INFO]: creating show directory at '$webDirectory/$showTitle/'"
						mkdir -p "$webDirectory/$showTitle/"
						# link the poster
						if [ -f "$show/poster.png" ];then
							posterPath="poster.png"
							echo "[INFO]: Found $show/$posterPath"
							ln -s "$show/$posterPath" "$webDirectory/$showTitle/$posterPath"
						elif [ -f "$show/poster.jpg" ];then
							posterPath="poster.jpg"
							echo "[INFO]: Found $show/$posterPath"
							ln -s "$show/$posterPath" "$webDirectory/$showTitle/$posterPath"
						else
							echo "[WARNING]: could not find $show/poster.[png/jpg]"
						fi
						# link the fanart
						if [ -f "$show/fanart.png" ];then
							echo "[INFO]: Found $show/fanart.png"
							fanartPath="fanart.png"
							echo "[INFO]: Found $show/$fanartPath"
							ln -s "$show/$fanartPath" "$webDirectory/$showTitle/$fanartPath"
						elif [ -f "$show/fanart.jpg" ];then
							fanartPath="fanart.jpg"
							echo "[INFO]: Found $show/$fanartPath"
							ln -s "$show/$fanartPath" "$webDirectory/$showTitle/$fanartPath"
						else
							echo "[WARNING]: could not find $show/fanart.[png/jpg]"
						fi
						# building the webpage for the show
						showPagePath="$webDirectory/$showTitle/index.html"
						echo "[INFO]: Creating directory at = '$webDirectory/$showTitle/'"
						mkdir -p "$webDirectory/$showTitle/"
						echo "[INFO]: Creating showPagePath = $showPagePath"
						touch "$showPagePath"
						################################################################################
						# begin building the html of the page
						################################################################################
						# build top of show webpage containing all of the shows meta info
						{
							tempStyle="html {background-image: url(\"$fanartPath\");background-size: 100%;}"
							echo "<html style='$tempStyle'>"
							echo "<head>"
							echo "<style>"
							echo "$tempStyle"
							cat /usr/share/nfo2web/style.css
							echo "</style>"
							echo "</head>"
							echo "<body>"
							echo "<h1>$showTitle</h1>"
							echo "<div class='episodeList'>"
						} >> "$showPagePath"
						# generate the episodes based on .nfo files
						for season in "$show"/*;do
							echo "[INFO]: checking for season folder at '$season'"
							if [ -d "$season" ];then
								echo "[INFO]: found season folder at '$season'"
								# generate the season name from the path
								seasonName=$(echo "$season" | rev | cut -d'/' -f1 | rev)
								{
									echo "<div class='seasonHeader'>"
									echo "	<h2>"
									echo "		$seasonName"
									echo "	</h2>"
									echo "</div>"
									echo "<hr>"
									echo "<div class='seasonContainer'>"
								} >> "$showPagePath"
								# if the folder is a directory that means a season has been found
								# read each episode in the series
								for episode in "$season"/*.nfo;do
									processEpisode "$episode" "$showTitle" "$showPagePath" "$webDirectory"
								done
								{
									echo "</div>"
								} >> "$showPagePath"
							else
								echo "Season folder $season does not exist"
							fi
						done
						{
							echo "</div>"
							echo "</body>"
							echo "</html>"
						} >> "$showPagePath"
						# add show page to the home page index
						{
							echo "<a class='indexSeries' href='$showTitle/'>"
							echo "	<img src='$showTitle/$posterPath'>"
							echo "	<div>"
							echo "		$showTitle"
							echo "	</div>"
							echo "</a>"
						} >> "$homePagePath"
					fi
				done
			fi
		done
		{
			echo "</body>"
			echo "</html>"
		} >> "$homePagePath"
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
