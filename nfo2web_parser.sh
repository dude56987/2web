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
# Generate a webpage for a .nfo file. The webpage will be generated in
#  the same directory as the .nfo file specified even if specified with
#  a absolute path. The program will search the same directory for any
#  media files with the same name as the .nfo file
########################################################################
# usage: nfo2web_parser /full/absolute/path/to/file.nfo
########################################################################
ripXmlTag(){
	data=$1
	tag=$2
	# rip the tag from the data
	echo "$data" | grep "<$tag>" | sed "s/<$tag>//g" | sed "s/<\/$tag>//g"
}
########################################################################
nfoFilePath=$1
echo "[DEBUG]: nfoFilePath = $nfoFilePath"
########################################################################
# get the absolute path of the file
nfoFilePath="$(readlink -f "$nfoFilePath")"
echo "[DEBUG]: absolute nfoFilePath = $nfoFilePath"
########################################################################
# for each episode build a page for the episode
nfoInfo=$(cat "$nfoFilePath")
########################################################################
# extract and generate all metadata from the nfo file before rendering
# the webpage
########################################################################
# rip the episode title
showTitle=$(ripXmlTag "$nfoInfo" "showtitle")
echo "[DEBUG]: showTitle = $showTitle"
episodeTitle=$(ripXmlTag "$nfoInfo" "title")
echo "[DEBUG]: episodeTitle = $episodeTitle"
season=$(ripXmlTag "$nfoInfo" "season")
echo "[DEBUG]: season= $season"
########################################################################
# strip the filename from the directory containing the nfo file
# THIS MEANS THAT THE WEBPAGE WILL BE CREATED IN THE SAME DIRECTORY AS
# THE NFO FILE, LINKS ARE RELATIVE
########################################################################
relative_filename=$(echo "$nfoFilePath" | sed "s/\/.*\///g")
echo "[DEBUG]: relative_filename = $relative_filename"
########################################################################
exit #DEBUG EXIT
########################################################################
nfoDirectory=$(echo "$nfoFilePath" | sed "s/$relative_filename//g")
echo "[DEBUG]: nfoDirectory = $nfoDirectory"
# remove path to get filename
# create the episode page path
#episodePagePath=$(echo "$episode" | sed "s/\.nfo$/.html/g")
episodePagePath="$nfoDirectory$showTitle/$season/$episodeTitle.html"
echo "[DEBUG]: episodePagePath = $episodePagePath"
videoFiles=""
# spaces are split
for path in "$nfoDirectory$showTitle/$season/$episodeTitle.*";do
	echo "[DEBUG]: path = $path"
	# copy video file paths into an array
	videoFiles=$(echo -e "$videoFiles$path\n")
done
echo "[DEBUG]: videoFiles = $episodePagePath"
echo "[DEBUG]: filtering out metadata files and webpage..."
videoFiles=$(echo "$videoFiles" | grep -v ".nfo")
videoFiles=$(echo "$videoFiles" | grep -v ".html")
echo "[DEBUG]: videoFiles = $episodePagePath"
########################################################################
exit #DEBUG EXIT
########################################################################
########################################################################
# create directories from metadata
########################################################################
# create nfoDirectory
# create the nfoDirectory for this show and this season
mkdir -p "$nfoDirectory/$showTitle/$season/"
########################################################################
# build the webpage now that everything exists
########################################################################
# start rendering the html
echo "<html>" > $episodePagePath
echo "<body>" >> $episodePagePath
echo "<h1>$showTitle</h1>" >> $episodePagePath
echo "<h2>$episodeTitle</h2>" >> $episodePagePath
# link the episode nfo file
ln -s $episode "$nfoDirectory/$show/$season/$showTitle.nfo"
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
	ln -s "$thumbnail" "$nfoDirectory/$showTitle/$season/$episodeTitle-thumb.png"
elif [ -f $libary/$show/$season/$episode-thumb.jpg ];then
	thumbnail="$libary/$show/$season/$episode-thumb.jpg"
	ln -s "$thumbnail" "$nfoDirectory/$showTitle/$season/$episodeTitle-thumb.jpg"
else
	if echo $nfoInfo | grep "thumb";then
		# download the thumbnail
		curl "$(ripXmlTag "$nfoInfo" "thumb")" > $libary/$show/$season/$episode-thumb.png
	fi
fi
echo "</body>" >> $episodePagePath
echo "</html>" >> $episodePagePath
