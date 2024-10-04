<?PHP
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
# this is part of the default group
requireGroup("2web");
# get the group type
if (array_key_exists("group",$_GET)){
	$groupType=$_GET['group'];
}else{
	$groupType="all";
}
# tag of the playlist
if (array_key_exists("tag",$_GET)){
	$tagType=$_GET['tag'];
}else{
	$tagType="all";
}
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
}else{
	$filterType="all";
}
?>
<!--
########################################################################
# 2web tag playlists interface
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
			echo "<title>Playlist: ".ucfirst($tagType)." ".ucfirst($filterType)."</title>";
		}
	?>
</head>
<body>
<?php
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");

if (array_key_exists("tag",$_GET)){
	if ($_GET["tag"] == ""){
		$currentTagData="tag=all";
	}else{
		$currentTagData="tag=".$_GET["tag"];
	}
}else{
	$currentTagData="tag=all";
}
if (array_key_exists("filter",$_GET)){
	$currentFilterData="filter=".$_GET["filter"];
}else{
	$currentFilterData="filter=all";
}
if (array_key_exists("group",$_GET)){
	if ($_GET["group"] == ""){
		$currentGroupData="group=all";
	}else{
		$currentGroupData="group=".$_GET["group"];
	}
}else{
	$currentGroupData="group=all";
}

# get the playlists
$allPlaylists=scanDir("/var/cache/2web/web/tags/");
$allPlaylists=array_diff($allPlaylists,Array(".","..","index.php"));
$tempPlaylists=Array();
$foundTags=Array("all");
$foundFilters=Array("all");
$foundGroups=Array("all");
foreach($allPlaylists as $playlist){
	if (is_file($playlist)){
		$tempPlaylistName=str_replace(".index","",$playlist);
		# add to the temp playlists
		$tempPlaylistName=explode("_",$tempPlaylistName);
		$foundGroups=array_merge($foundGroups,Array($tempPlaylistName[0]));
		if ($currentGroupData == "group=all"){
			$foundTags=array_merge($foundTags,Array($tempPlaylistName[1]));
		}else{
			# only add tags found in the current group
			if ($currentGroupData == "group=$tempPlaylistName[0]"){
				$foundTags=array_merge($foundTags,Array($tempPlaylistName[1]));
			}
		}
		# only add filters found in the current group and current tag
		if ($currentGroupData == "group=$tempPlaylistName[0]"){
			if ($currentTagData == "tag=$tempPlaylistName[1]"){
				$foundFilters=array_merge($foundFilters,Array($tempPlaylistName[2]));
			}
		}
	}
}
$foundTags=array_unique($foundTags);
$foundFilters=array_unique($foundFilters);
$foundGroups=array_unique($foundGroups);
# if any content is restricted the all group will be locked
# the all group is default so a message will be shown below if all is locked
if ($filterType == "all"){
	$modules=listModules();
	# check each group permission
	foreach($modules as $module){
		$showOutput = requireGroup($module, false);
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
<div class='titleCard'>
	<h2>
		Playlists
	</h2>
	<div class='listCard'>
		<a class='button' href='/new/'>
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
		<a class='activeButton' href='/tags/'>
			🔖 Tags
		</a>
	</div>
</div>
<div class='titleCard'>
	<h3>
		Groups
	</h3>
	<div class='listCard'>
		<?PHP
			# draw the found tags and draw a button for each tag
			foreach($foundGroups as $group){
				if ($currentGroupData == "group=$group"){
					echo "<a class='activeButton' href='/tags/?group=$group&tag=all&filter=all'>\n";
				}else{
					echo "<a class='button' href='/tags/?group=$group&tag=all&filter=all'>\n";
				}
				echo "	".ucfirst($group)."\n";
				echo "</a>\n";
			}
		?>
	</div>

	<h3>
		Tags
	</h3>
	<div class='listCard'>
		<?PHP
			# draw the found tags and draw a button for each tag
			foreach($foundTags as $tag){
				if ($currentTagData == "tag=$tag"){
					echo "<a class='activeButton' href='/tags/?$currentGroupData&tag=$tag&filter=all'>\n";
				}else{
					echo "<a class='button' href='/tags/?$currentGroupData&tag=$tag&filter=all'>\n";
				}
				echo "	".ucfirst($tag)."\n";
				echo "</a>\n";
			}
		?>
	</div>

	<h3>
		Filters
	</h3>
	<div class='listCard'>
		<?PHP
			# draw the found tags and draw a button for each tag
			foreach($foundFilters as $filter){
				if ($currentFilterData == "filter=$filter"){
					echo "<a class='activeButton' href='/tags/?$currentGroupData&$currentTagData&filter=$filter'>\n";
				}else{
					echo "<a class='button' href='/tags/?$currentGroupData&$currentTagData&filter=$filter'>\n";
				}
				echo "	".ucfirst($filter)."\n";
				echo "</a>\n";
			}
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
	$emptyMessage .= "<li>No $groupType $tagType $filterType items found!</li>";
	$emptyMessage .= "</ul>";
	# draw the last updated time
	if (file_exists($groupType."_".$tagType."_".$filterType.".cfg")){
		echo "<div>Last Updated : ";
		timeElapsedToHuman(file_get_contents($groupType."_".$tagType."_".$filterType.".cfg"));
		echo "</div>";
	}
	# loop though and display the playlist index
	displayIndexWithPages($groupType."_".$tagType."_".$filterType.".index",$emptyMessage,48,"reverse");
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
