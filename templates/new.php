<?PHP
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
# this is part of the default group
requireGroup("2web");
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
# 2web new playlists
# Copyright (C) 2024  Carl J Smith
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
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
		if (array_key_exists("filter",$_GET)){
			$filterType=$_GET['filter'];
			echo "<title>Playlist: ".ucfirst($filterType)." New</title>";
		}else{
			echo "<title>Playlist: All New</title>";
		}
	?>
</head>
<body>
<?php
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<div class='titleCard'>
	<h2>
		Playlists
	</h2>
	<div class='listCard'>
			<a class='activeButton' href='/new/'>
			📜 NEW
		</a>
		<?PHP
		if (array_key_exists("filter",$_GET)){
			echo "<a class='button' href='/random/?filter=$filterType'>";
		}else{
			echo "<a class='button' href='/random/'>";
		}
		?>
			🔀 RANDOM
		</a>
		<?PHP
		if(checkModStatus("nfo2web")){
			echo "<a class='button' href='/tags/'>\n";
			echo "	🔖 Tags\n";
			echo "</a>\n";
		}
		?>
	</div>
</div>
<div class='titleCard'>
<?php
if (array_key_exists("filter",$_GET)){
	$filterType=$_GET['filter'];
	echo "<h2>Recently added ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Recently added Media</h2>";
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

drawPlaylistButton($filterType,"episodes","🎞️ Episodes");
drawPlaylistButton($filterType,"shows","📺 shows");
drawPlaylistButton($filterType,"movies","🎥 Movies");
drawPlaylistButton($filterType,"comics","📚 Comics");
drawPlaylistButton($filterType,"music","🎧 Music");
drawPlaylistButton($filterType,"channels","📡 Channels");
drawPlaylistButton($filterType,"albums","💿 Albums");
drawPlaylistButton($filterType,"artists","🎤 Artists");
drawPlaylistButton($filterType,"tracks","🎵 Tracks");
drawPlaylistButton($filterType,"repos","💾 Repos");
drawPlaylistButton($filterType,"portal","🔗 Links");
drawPlaylistButton($filterType,"graphs","📊 Graphs");
drawPlaylistButton($filterType,"applications","🖥️ Applications");

?>
</div>
</div>


<div class='settingListCard'>
<?php
flush();
ob_flush();
if ($hideFilter){
	echo "This filter is disabled because the content is restricted without login. Please use individual filters to access allowed playlists.";
}else{
	$emptyMessage = "<ul>";
	$emptyMessage .= "<li>No $filterType items found!</li>";
	$emptyMessage .= "</ul>";
	# draw the last updated time
	if (file_exists($filterType.".cfg")){
		echo "<span class='singleStat'>";
		echo "	<span class='singleStatLabel'>";
		echo "		Last Updated";
		echo "	</span>";
		echo "	<span class='singleStatValue'>";
		timeElapsedToHuman(file_get_contents($filterType.".cfg"));
		echo "	</span>";
		echo "</span>";
		echo "<hr>";
	}
	# loop though and display the playlist index
	displayIndexWithPages($filterType.".index",$emptyMessage,48,"reverse");
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
echo "<style>";
echo "	#spinner {";
echo "		display: none;";
echo "	}";
echo "</style>";
?>
</body>
</html>
