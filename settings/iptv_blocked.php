<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web iptv group block settings
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
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>
<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
	<li><a href='#blockGroup'>Manual Block Group</a></li>
	<li><a href='#unblockGroup'>Manual Unblock Group</a></li>
	<li><a href='#serverBlockedGroups'>Server Blocked Groups</a></li>
	<li><a href='#activeBlockedGroups'>Active/Blocked Groups</a></li>
	<ul>
</div>

<div id='blockGroup' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Block Group</h2>
<input width='60%' class='inputText' type='text' name='blockGroup' placeholder='GroupName...'>
<button class='button' type='submit'>🚫 Block</button>
</form>
</div>

<div id='unblockGroup' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Unblock Group</h2>
<input width='60%' class='inputText' type='text' name='unblockGroup' placeholder='GroupName...'>
<button class='button' type='submit'>❎ Unblock</button>
</form>
</div>

<?PHP
echo "<div id='serverBlockedGroups' class='titleCard'>";
echo "<h2>Server Blocked Groups</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/iptv/blockedGroups.cfg");
echo "</pre>\n";
echo "</div>";


$sourceFiles = scandir("/etc/2web/iptv/blockedGroups.d/");
$blockedGroups = array();
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	$sourceFile = "/etc/2web/iptv/blockedGroups.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				$link=file_get_contents($sourceFile);
				$blockedGroups = array_merge($blockedGroups,array($link));
			}
		}
	}
}
?>

<div id='ActiveBlockedGroups' class='settingListCard'>
<h1>Active/Blocked Groups</h1>
<?php
// find all the groups
if (file_exists("/var/cache/2web/web/live/groups/")){
	# create groups array
	$groups=array();


	# load database
	$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/live/groups.db");
	# set the timeout to 1 minute since most webbrowsers timeout loading before this
	$databaseObj->busyTimeout(60000);
	# get the list of tables in the sql database
	$result = $databaseObj->query("select name from sqlite_master where type='table';");
	$sourceFiles=Array();
	# check if the table exists in the sql database
	while($row = $result->fetchArray()){
		# add each row to the array
		#array_push($sourceFiles,str_replace("_","",$row['name']));
		array_push($sourceFiles,preg_replace("/^_/","",$row['name']));
		#array_push($sourceFiles,$row['name']);
	}

	# read the directory name and make a button to block it
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		$groups = array_merge($groups, array($sourceFileName));
		# if the group has been blocked
		if(in_array($sourceFile, $blockedGroups)){
			echo "<div class='disabledSetting settingsEntry'>\n";
		}else{
			echo "<div class='enabledSetting settingsEntry'>\n";
		}
		echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "		<h2>\n";
		echo "			$sourceFile";
		echo "		</h2>\n";
		echo "		<div class='buttonContainer'>\n";
		echo "			<a class='button' href='/live/?filter=$sourceFile#$sourceFile'>🔗 View</a>\n";
		# if the group has been blocked
		if(in_array($sourceFile, $blockedGroups)){
			echo "			<button class='button' type='submit' name='unblockGroup' value='".$sourceFile."'>❎ UNBLOCK</button>\n";
		}else{
			echo "			<button class='button' type='submit' name='blockGroup' value='".$sourceFile."'>🚫 BLOCK</button>\n";
		}
		echo "		</div>\n";
		echo "	</form>\n";
		echo "</div>\n";
	}

	$sourceFiles=array_diff($blockedGroups,$groups);
	foreach($sourceFiles as $groupName){
		# if the group has been blocked
		echo "<div class='disabledSetting settingsEntry'>\n";
		echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "		<h2>\n";
		echo "			$groupName";
		echo "		</h2>\n";
		echo "		<div class='buttonContainer'>\n";
		# if the group has been blocked
		echo "			<button class='button' type='submit' name='unblockGroup' value='".$groupName."'>❎ UNBLOCK</button>\n";
		echo "		</div>\n";
		echo "	</form>\n";
		echo "</div>\n";
	}
}
?>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
