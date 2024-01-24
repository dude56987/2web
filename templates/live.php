<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("iptv2web");
?>
<!--
########################################################################
# 2web public help document
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
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
# add the updated movies below the header
#include("../randomChannels.index");
################################################################################
drawPosterWidget("channels",False);

// find all the groups
?>
<div class='titleCard'>
<h1>
	Groups
	<img class='globalPulse' src='/pulse.gif' />
</h1>
<div class='listCard'>
	<a id='all' class='button tag' href='/live/#all'>
		All
	</a>
<?php
# load database
$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/live/groups.db");
# set the timeout to 1 minute since most webbrowsers timeout loading before this
$databaseObj->busyTimeout(60000);
# run query to get the table names
$result = $databaseObj->query("select name from sqlite_master where type='table' order by name ASC;");
# fetch each row data individually and display results
while($row = $result->fetchArray()){
	#$cleanName=str_replace("_","",$row['name']);
	$cleanName=preg_replace("/^_/","",$row['name']);
	$displayName=str_replace("_"," ",$cleanName);
	$displayName=ucwords($displayName);
	# read the directory name
	if (array_key_exists("filter",$_GET)){
		$filterType=$_GET['filter'];
		if ($filterType == $cleanName){
			echo "	<a id='".$cleanName."' class='button tag activeButton' href='?filter=".$cleanName."#".$cleanName."'>\n";
		}else{
			echo "	<a id='".$cleanName."' class='button tag' href='?filter=".$cleanName."#".$cleanName."'>\n";
		}
	}else{
		echo "	<a id='".$cleanName."' class='button tag' href='?filter=".$cleanName."#".$cleanName."'>\n";
	}
	echo "		".$displayName."\n";
	echo "	</a>\n";
}
echo "</div>\n";
?>
	<hr>
	<div class="filterButtonBox">
		<input type="button" class="button liveFilter" value="📺 TV" onclick="filterByClass('indexLink','📺')">
		<input type="button" class="button liveFilter" value="∞ All" onclick="filterByClass('indexLink','')">
		<input type="button" class="button liveFilter" value="📻 Radio" onclick="filterByClass('indexLink','📻')">
	</div>
</div>
<hr>

<div class='settingListCard'>
<?php
if (array_key_exists("filter",$_GET)){
	$filterType=$_GET['filter'];
	$displayName=str_replace("_"," ",$filterType);
	$displayName=ucwords($displayName);
	# draw the header to identify the filter applied
	echo "<h2>";
	echo "$displayName";
	echo "<img class='globalPulse' src='/pulse.gif' />";
	echo "</h2>";

	$result = $databaseObj->query('select * from "_'.$filterType.'";');

	# fetch each row data individually and display results
	while($row = $result->fetchArray()){
		$sourceFile = $row['title'];
		//echo $sourceFile;
		if (file_exists($sourceFile)){
			// read the index entry
			$data=file_get_contents($sourceFile);
			// write the index entry
			echo "$data";
			flush();
			ob_flush();
		}
	}
}else if(file_exists("channels.m3u")){
	# draw the header to identify the filter applied
	echo "<h2>";
	echo "All Channels";
	echo "<img class='globalPulse' src='/pulse.gif' />";
	echo "</h2>";

	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT']."/live/channels.m3u"));
	// reverse the time sort
	//$sourceFiles = array_reverse($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		if ( ! strpos($sourceFile,"#EXTINF")){
			$sourceFileName = md5($sourceFile);
			if (file_exists($_SERVER['DOCUMENT_ROOT']."/live/index/channel_".$sourceFileName.".index")){
				if (is_file($_SERVER['DOCUMENT_ROOT']."/live/index/channel_".$sourceFileName.".index")){
					// read the index entry
					$data=file_get_contents($_SERVER['DOCUMENT_ROOT']."/live/index/channel_".$sourceFileName.".index");
					// write the index entry
					echo "$data";
					flush();
					ob_flush();
				}
			}
		}
	}
}else{
	# draw the header to identify the filter applied
	echo "<h2>";
	echo "All Found Channels";
	echo "<img class='globalPulse' src='/pulse.gif' />";
	echo "</h2>";
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/2web/web/live/index/channel_*.index | sort"));
	// reverse the time sort
	$sourceFiles = array_reverse($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "$data";
					flush();
					ob_flush();
				}
			}
		}
	}
}
?>
</div>


<?php
// add random movies above the footer
include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
