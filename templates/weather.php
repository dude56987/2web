<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("weather2web");
?>
<!--
########################################################################
# 2web weather index
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
</head>
<body>

<?php
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<hr>
<?php
	$updateStarted=false;
	$stationPath="/var/cache/2web/web/weather/data/station/";
	$forcastPath="/var/cache/2web/web/weather/data/forcast/";
	$currentPath="/var/cache/2web/web/weather/data/current/";
	// get a list of all the genetrated index links for the page
	if (is_dir($stationPath)){
		$stationFiles = scandir($stationPath);
		$stationFiles = array_diff($stationFiles,Array("..","."));
		sort($stationFiles);
	}
	$refreshPath="/var/cache/2web/web/weather/refresh.cfg";
	$refreshFinishedPath="/var/cache/2web/web/weather/refreshComplete.cfg";
	# if index entry is over one hour old update the weather information
	if ( file_exists($refreshPath) ){
		if (! file_exists($refreshFinishedPath)){
			$updateStarted=true;
		}else if ( ( time() - filemtime($refreshPath) ) > ( ( (60 * 60) ) ) ){
			# weather2web will
			# - only run one instance at a time
			#	- update only once every hour even if activated by this
			$updateStarted=true;
			file_put_contents($refreshPath,time());
			if (file_exists($refreshFinishedPath)){
				unlink($refreshFinishedPath);
			}
		}
	}else{
		$updateStarted=true;
		file_put_contents($refreshPath,time());
		if (file_exists($refreshFinishedPath)){
			unlink($refreshFinishedPath);
		}
	}
	# if the finished file is more than 90 seconds old
	#if ( ( time() - filemtime($refreshFinishedPath) ) > ( ( (30) ) ) ){
	#	$updateStarted=true;
	#}
	# render the page if the info is fresh
	if($updateStarted == false){
		echo "<div class='titleCard'>";
		echo "<h1>Stations</h1>";
		echo "<div class='listCard'>";
		echo "<a class='button' href='?'>🪟 Overview</a>";
		# read each station file
		foreach($stationFiles as $stationFile){
			# read the index entry
			echo file_get_contents($stationPath.$stationFile);
			flush();
			ob_flush();
		}
		echo "<a class='button' href='?all'>🌎 View All Stations</a>";
		echo "</div>";
		echo "</div>";
		if (array_key_exists("station",$_GET)){
			# draw the weather info for a single station
			$stationFile=$_GET["station"].".index";
			echo "<div class='titleCard'>";
			echo		file_get_contents( ($currentPath.$stationFile) );
			echo "</div>";
			// write the current condititons at the bottom of the extended forecast
			echo	file_get_contents($forcastPath.$stationFile);
			flush();
			ob_flush();
		}else if (array_key_exists("all",$_GET)){
			# draw weather info for all stations
			foreach($stationFiles as $stationFile){
				echo "<div class='titleCard'>";
				// write the index entry
				echo file_get_contents( ($currentPath.$stationFile) );
				echo "</div>";
				// write the current condititons at the bottom of the extended forecast
				echo file_get_contents($forcastPath.$stationFile);
				flush();
				ob_flush();
			}
		}else{
			echo "<div class='titleCard'>";
			# draw the default view showing all station current info as links to individual stations
			foreach($stationFiles as $stationFile){
				$stationName=str_replace(".index","",$stationFile);
				echo "<a href='?station=$stationName'>\n";
				echo "<div class='inputCard'>\n";
				echo "<h2>\n";
				echo "$stationName\n";
				echo "</h2>\n";
				echo file_get_contents($currentPath.$stationFile)."\n";
				echo "</div>\n";
				echo "</a>\n";
				flush();
				ob_flush();
			}
			echo "</div>";
		}
	}else{
		# if weather2web is not running queue a update job
		if (! file_exists("/var/cache/2web/web/weather2web.active")){
			addToQueue("multi","weather2web");
			addToLog("DEBUG","weather check", "weather updated");
		}
		echo "<div class='inputCard'>";
		echo "<h1>Updating Weather Information</h1>";
		echo "Weather information is out of date. Updating weather information from remote server...";
		echo "</div>";
		# reload the page after 10 seconds
		reloadPage(10);
	}
?>
<?php
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
