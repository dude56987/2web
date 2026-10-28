



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
# auto redirects based on source page
$comingFrom=$_SERVER["HTTP_REFERER"];
$newPath="";
addToLog("DEBUG","comingFrom","$comingFrom");
if(stripos($comingFrom,"/comics/") !== false){
	$newPath="?filter=comics";
}else if(stripos($comingFrom,"/shows/") !== false){
	$newPath=("?filter=episodes");
}else if(stripos($comingFrom,"/movies/") !== false){
	$newPath=("?filter=movies");
}else if(stripos($comingFrom,"/videos/") !== false){
	$newPath=("?filter=videos");
}else if(stripos($comingFrom,"/music/") !== false){
	$newPath=("?filter=music");
}else if(stripos($comingFrom,"/graphs/") !== false){
	$newPath=("?filter=graphs");
}else if(stripos($comingFrom,"/portal/") !== false){
	$newPath=("?filter=portal");
}else if(stripos($comingFrom,"/applications/") !== false){
	$newPath=("?filter=applications");
}else if(stripos($comingFrom,"/wiki/") !== false){
	$newPath=("?filter=wiki");
}else if(stripos($comingFrom,"/live/") !== false){
	$newPath=("?filter=channels");
}else if(stripos($comingFrom,"/web_player") !== false){
	$newPath=("?filter=webcasts");
}else if(stripos($comingFrom,"/web-player.php") !== false){
	$newPath=("?filter=webcasts");
}else if(stripos($comingFrom,"/repos/") !== false){
	$newPath=("?filter=repos");
}
# if newpath has been set
if($newPath != ""){
	# check the page has not already redirected
	if((stripos($_SERVER["REQUEST_URI"],"$newPath") !== false)==false){
		# redirect to the filter based on the page the user is coming from
		redirect($newPath,true);
	}
}

?>
<!--
########################################################################
# 2web popular playlist
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
			echo "<title>Playlist: Explore ".ucfirst($filterType)."</title>";
		}else{
			echo "<title>Playlist: Explore All</title>";
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
	echo "<h2>Explore ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Media Filters</h2>";
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

drawViewsFilterButton($filterType,"webcasts","🌐 Webcasts");
drawViewsFilterButton($filterType,"broadcasts","🎬 Broadcasts");
drawViewsFilterButton($filterType,"episodes","🎞️ Episodes");
drawViewsFilterButton($filterType,"shows","📺 Shows");
drawViewsFilterButton($filterType,"movies","🎥 Movies");
drawViewsFilterButton($filterType,"videos","📽️ Videos");
drawViewsFilterButton($filterType,"videogroups","📼 Video Groups");
drawViewsFilterButton($filterType,"comics","📚 Comics");
drawViewsFilterButton($filterType,"music","🎧 Music");
drawViewsFilterButton($filterType,"channels","📡 Channels");
drawViewsFilterButton($filterType,"albums","💿 Albums");
drawViewsFilterButton($filterType,"artists","🎤 Artists");
drawViewsFilterButton($filterType,"tracks","🎵 Tracks");
drawViewsFilterButton($filterType,"repos","💾 Repos");
drawViewsFilterButton($filterType,"portal","🔗 Links");
drawViewsFilterButton($filterType,"graphs","📊 Graphs");
drawViewsFilterButton($filterType,"applications","🖥️ Applications");

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
	drawPosterWidget("webcasts");
	drawPosterWidget("broadcasts");
	drawPosterWidget("episodes");
	drawPosterWidget("videos");
	drawPosterWidget("shows");
	drawPosterWidget("videoGroups");
	drawPosterWidget("movies");
	drawPosterWidget("comics");
	drawPosterWidget("albums",False,True);
	drawPosterWidget("artists",False,True);
	drawPosterWidget("channels",False,True);
	drawPosterWidget("channels",True,True);
	drawPosterWidget("repos");
	drawPosterWidget("portal");
	drawPosterWidget("graphs");
	drawPosterWidget("applications");
}else{
	$emptyMessage = "<ul>\n";
	$emptyMessage .= "<li>No $filterType items found!</li>\n";
	$emptyMessage .= "</ul>\n";
	# draw the last updated time
	if (file_exists("/var/cache/2web/web/views.db")){
		# last update time
		$tempTime=timeElapsedToHuman(filemtime("/var/cache/2web/web/views.db"));
		echo "<span class='singleStat' title='Last Updated - $tempTime'>\n";
		echo "	<span class='singleStatLabel'>\n";
		echo "		Last Updated\n";
		echo "	</span>\n";
		echo "	<span class='singleStatValue'>\n";
		echo $tempTime."\n";
		echo "	</span>\n";
		echo "</span>\n";
		echo "<hr>\n";
	}
	# draw the most popular playlist and the filters associated
	#
	# - By default draw the most popular list
	# - Show items by the most popular
	# - Show items by the least popular items
	# - Still allows filtering by content
	# - $inverse will show the least viewed content
	# - Content of the same exact popularity will be randomized within that list
	#
	# - The filter type determines which playlist filter the widget pulls items from
	# - The random variable sets if this is a widget containing random entries
	# - The linktype determines the formatting used
	# - The content rendered by this widget will be cached long term. So the playlist
	#   section of the site itself will be ahead of any widgets rendered by this
	#   function. However this function will update widget data on access. So if you
	#   load up a page with the widget and the site has not cached it recently, the
	#   same data as the playlists page will be loaded. The cache prevents high usage
	#   of the site from hammering the server databases.
	# require the views database to exist
	if (file_exists($_SERVER['DOCUMENT_ROOT']."/views.db")){
		# Draw the poster widget as HTML
		$dataSourcePath=$_SERVER['DOCUMENT_ROOT']."/views.db";
		if (file_exists($dataSourcePath)){
			$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/widget_unpopular_playlist_$filterType.index";
			if (file_exists($cacheFile)){
				if (time()-filemtime($cacheFile) > 2 * 3600){
					// update the cached file
					$writeFile=true;
				}else{
					// read from the already cached file
					$writeFile=false;
				}
			}else{
				# write the file if it does not exist
				$writeFile=true;
			}
			if ($writeFile){
				# load the views database
				$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");
				# set the timeout to 1 minute since most webbrowsers timeout loading before this
				$databaseObj->busyTimeout(60000);
				# get the list of views to a path in the sql database
				# sort least seen content first
				$result = $databaseObj->query("select * from watch_count order by views ASC;");

				$dataResults=Array();
				$foundIndexFiles=Array();
				# check if the table exists in the sql database
				while($row = $result->fetchArray()){
					# add each row to the array
					array_push($dataResults,$row['path']);
					# draw the icon for the page if it can be found
					# - the views are not listed here only the ranking
					# - views are listed for admin
					$tempUrl=$row["path"];
					# if a index file exists for this item on the most popular list
					# draw the index file in the list
					#
					$tempIndexPath=($tempUrl);
					if (file_exists($tempIndexPath)){
						if ($filterType=="all"){
							# store all the index files unfiltered
							$foundIndexFiles=array_merge($foundIndexFiles,Array($tempIndexPath));
						}else{
							# check the filter is correct
							if (stripos($tempUrl,"/$filterType/") !== false){
								# store the url
								$foundIndexFiles=array_merge($foundIndexFiles,Array($tempIndexPath));
							}
						}
					}
					#
				}
				# add the cached source files to the page
				$sourceFiles=file_put_contents($cacheFile,join("\n",$foundIndexFiles));
				#
				#
				#
				# get data from views database, sort and cache output as a file in web_cache
				#
				#
				#
				# read the views database
				# get 800 items based on view count of pages
				# take the items and sort them into a 2d array of the values based on duplicate view counts
				# randomize the list items inside the view count group
				# list the items sorted by the view count
				# filter the items in order to remove icons that do not exist
				# if inverse is set reverse the sort in order to show the least viewed media items
				# cache the result in the 2web cache
				#
				# open the cache index file
			}
			# display the cached file
			displayIndexWithPages($cacheFile,$emptyMessage,48);
		}else{
			# the views.db file does not exist
			echo "View tracking database has been disabled...";
		}
	}
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
echo "<style>\n";
echo "	#spinner {\n";
echo "		display: none;\n";
echo "	}\n";
echo "</style>\n";
?>
</body>
</html>
