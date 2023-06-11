<!--
########################################################################
# 2web random playlists
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
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<div class='titleCard'>
	<h2>
		Playlists
		<img id='spinner' src='/spinner.gif' />
	</h2>
	<div class='listCard'>
		<?PHP
		if (array_key_exists("filter",$_GET)){
			$filterType=$_GET['filter'];
			echo "<a class='button' href='/new/?filter=$filterType'>";
		}else{
			echo "<a class='button' href='/new/'>";
		}
		?>
			📜 NEW
		</a>
		<a class='activeButton' href='/random/'>
			🔀 RANDOM
		</a>
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
SQLdrawPlaylistButton($filterType,"graphs","📊 Graphs");
SQLdrawPlaylistButton($filterType,"repos","💾 Repos");

?>
</div>
</div>


<div class='settingListCard'>
<img class='globalPulse' src='/pulse.gif'>
<?php
flush();
ob_flush();

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

	# fetch each row data individually and display results
	while($row = $result->fetchArray()){
		$sourceFile = $row['title'];
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					//echo "sourceFile = $sourceFile<br>\n";
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "$data";
					fwrite($fileHandle, "$data");
					flush();
					ob_flush();
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
