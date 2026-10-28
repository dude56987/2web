<?PHP
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
# this is part of the default group
requireGroup("2web");
requireGroup("playlists");
# check for group permissions in filter type
if (array_key_exists("filter",$_GET)){
	$filterType=$_GET['filter'];
	if ($filterType == "graphs"){
		requireGroup("graph2web");
	}else if ($filterType == "comics"){
		requireGroup("comic2web");
	}else if ($filterType == "channels"){
		requireGroup("iptv2web");
	}else if ($filterType == "repos"){
		requireGroup("git2web");
	}else if ($filterType == "episodes"){
		requireGroup("nfo2web");
	}else if ($filterType == "movies"){
		requireGroup("nfo2web");
	}else if ($filterType == "videos"){
		requireGroup("video2web");
	}else if ($filterType == "videogroups"){
		requireGroup("video2web");
	}else if ($filterType == "shows"){
		requireGroup("nfo2web");
	}else if ($filterType == "music"){
		requireGroup("music2web");
	}else if ($filterType == "artists"){
		requireGroup("music2web");
	}else if ($filterType == "albums"){
		requireGroup("music2web");
	}else if ($filterType == "tracks"){
		requireGroup("music2web");
	}else if ($filterType == "portal"){
		requireGroup("portal2web");
	}else if ($filterType == "applications"){
		requireGroup("php2web");
	}
}
?>
<!--
########################################################################
# 2web alphabetical playlists
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
		if (array_key_exists("filter",$_GET)){
			$filterType=$_GET['filter'];
			echo "<title>Playlist: ".ucfirst($filterType)." Alphabetical</title>";
		}else{
			echo "<title>Playlist: All Alphabetical</title>";
		}
	?>
</head>
<body>
<?php
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
# draw the playlists header
# - requires $filterType to be set
include("/usr/share/2web/templates/playlists_header.php");
?>
<div class='titleCard'>
<?php
if (array_key_exists("filter",$_GET)){
	$filterType=$_GET['filter'];
	echo "<h2>Alphabetical ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Random Media Filters</h2>";
}
# if any content is restricted the all group will be locked
# the all group is default so a message will be shown below if all is locked
if ($filterType == "all"){
	$groups=listModules();
	# check each group permission
	foreach($groups as $group){
		$showOutput = requireGroup($group, false);
		# if any group requires permission lock out the 'all' playlist
		if ($showOutput == false){
			$hideFilter = true;
			# break the loop since only one locked item means the all list is unaccessable
			break;
		}else{
			$hideFilter = false;
		}
	}
}else{
	$hideFilter = false;
}
?>
<div class='listCard'>
<a class='button' href='?filter=all'>📜 All</a>
<?PHP
#
$checkPath="/var/cache/2web/web/new/";
#
drawPlaylistButton($filterType,"webcasts","🌐 Webcasts",$checkPath);
drawPlaylistButton($filterType,"broadcasts","🎬 Broadcasts",$checkPath);
drawPlaylistButton($filterType,"episodes","🎞️ Episodes",$checkPath);
drawPlaylistButton($filterType,"shows","📺 shows",$checkPath);
drawPlaylistButton($filterType,"movies","🎥 Movies",$checkPath);
drawPlaylistButton($filterType,"videos","📽️ Videos",$checkPath);
drawPlaylistButton($filterType,"videogroups","📼 Video Groups",$checkPath);
drawPlaylistButton($filterType,"comics","📚 Comics",$checkPath);
drawPlaylistButton($filterType,"music","🎧 Music",$checkPath);
drawPlaylistButton($filterType,"channels","📡 Channels",$checkPath);
drawPlaylistButton($filterType,"albums","💿 Albums",$checkPath);
drawPlaylistButton($filterType,"artists","🎤 Artists",$checkPath);
drawPlaylistButton($filterType,"tracks","🎵 Tracks",$checkPath);
drawPlaylistButton($filterType,"repos","💾 Repos",$checkPath);
drawPlaylistButton($filterType,"portal","🔗 links",$checkPath);
drawPlaylistButton($filterType,"graphs","📊 Graphs",$checkPath);
drawPlaylistButton($filterType,"applications","🖥️ Applications",$checkPath);
?>
</div>
</div>

<div class='settingListCard'>
<?php
flush();
ob_flush();
if ($hideFilter){
	echo "<h2>This filter is disabled because the content is restricted without login. Please use individual filters to access allowed playlists.</h2>\n";
	# draw new media widgets, only accessable filters will be shown
	drawPosterWidget("episodes",True);
	drawPosterWidget("shows",True);
	drawPosterWidget("movies",True);
	drawPosterWidget("videos",True);
	drawPosterWidget("comics",True);
	drawPosterWidget("albums",True,"icon");
	drawPosterWidget("artists",True,"icon");
	drawPosterWidget("channels",True,"icon");
	drawPosterWidget("repos",True);
	drawPosterWidget("portal",True);
	drawPosterWidget("graphs",True);
	drawPosterWidget("applications","icon");
}else{
	# load the selected index based on the filter index
	if(file_exists("/var/cache/2web/web/$filterType/$filterType.index")){
		# load the list directly
		displayIndexWithPages("/var/cache/2web/web/$filterType/$filterType.index");
	}else if(file_exists("/var/cache/2web/web/random/$filterType.index")){
		displayIndexWithPages("/var/cache/2web/web/random/$filterType.index");
	}else if(file_exists("/var/cache/2web/web/new/$filterType.index")){
		displayIndexWithPages("/var/cache/2web/web/new/$filterType.index");
	}
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
