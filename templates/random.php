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
# 2web random playlists
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
			echo "<title>Playlist: ".ucfirst($filterType)." Random</title>";
		}else{
			echo "<title>Playlist: All Random</title>";
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
		<?PHP
		if (array_key_exists("filter",$_GET)){
			echo "<a class='button' href='/new/?filter=$filterType'>";
		}else{
			echo "<a class='button' href='/new/'>";
		}
		?>
			📜 New
		</a>
		<a class='activeButton' href='/random/'>
			🔀 Random
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
	echo "<h2>Random ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Random Media</h2>";
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

SQLdrawPlaylistButton($filterType,"episodes","🎞️ Episodes");
SQLdrawPlaylistButton($filterType,"shows","📺 shows");
SQLdrawPlaylistButton($filterType,"movies","🎥 Movies");
SQLdrawPlaylistButton($filterType,"comics","📚 Comics");
SQLdrawPlaylistButton($filterType,"music","🎧 Music");
SQLdrawPlaylistButton($filterType,"channels","📡 Channels");
SQLdrawPlaylistButton($filterType,"albums","💿 Albums");
SQLdrawPlaylistButton($filterType,"artists","🎤 Artists");
SQLdrawPlaylistButton($filterType,"tracks","🎵 Tracks");
SQLdrawPlaylistButton($filterType,"repos","💾 Repos");
SQLdrawPlaylistButton($filterType,"portal","🔗 links");
SQLdrawPlaylistButton($filterType,"graphs","📊 Graphs");
SQLdrawPlaylistButton($filterType,"applications","🖥️ Applications");

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
	$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/random_$filterType.index";
	if (file_exists($cacheFile)){
		# set the time the cached results are kept, in seconds
		if (time()-filemtime($cacheFile) > 30){
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
	# load the cached file or write a new cached fill
	if ($writeFile){
		ignore_user_abort(true);

		# load database
		$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/data.db");
		# set the timeout to 1 minute since most webbrowsers timeout loading before this
		$databaseObj->busyTimeout(60000);

		# run query to get 800 random
		$result = $databaseObj->query('select * from "_'.$filterType.'" order by random() limit 100;');

		# open the cache file for writing
		$fileHandle = fopen($cacheFile,'w');

		# set the delay counter for CSS animations
		$animationDelayCounter=1;
		# fetch each row data individually and display results
		while($row = $result->fetchArray()){
			$sourceFile = $row['title'];
			if (file_exists($sourceFile)){
				if (is_file($sourceFile)){
					if (strpos($sourceFile,".index")){
						//echo "sourceFile = $sourceFile<br>\n";
						#$tempData="<span class='fallIn' style='animation-delay: ".$animationDelayCounter."s !important'>";
						// read the index entry
						$tempFileData=file_get_contents($sourceFile);
						# add the animation class
						$tempData=str_replace("class='indexSeries","class='fallIn indexSeries",$tempFileData);
						#
						$tempData=str_replace("class='showPageEpisode","class='fallIn showPageEpisode ",$tempData);
						# add the custom animation delay to the element
						$tempData=str_replace("class='fallIn","style='opacity: 0;animation-delay: ".$animationDelayCounter."s' class='fallIn",$tempData);
						$tempData.="</span>";
						// write the index entry
						fwrite($fileHandle, "$tempFileData");
						# flush output to the user
						echo "$tempData";
						#
						flush();
						ob_flush();
						#
						#sleep(0.08);
						# increment the counter
						$animationDelayCounter += 0.08;
						# clamp the animation delay
						if ($animationDelayCounter > 5){
							$animationDelayCounter = 5;
						}
					}
				}
			}
		}
		fclose($fileHandle);
		ignore_user_abort(false);
	}else{
		# load the cached file
		echo file_get_contents($cacheFile);
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
