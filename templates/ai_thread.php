<?PHP
########################################################################
# 2web conference gpt
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
ini_set('display_errors', 1);
ini_set('file_uploads', "On");
########################################################################
function filesize_to_human($tempFileSize){
	# get the filesize
	if ($tempFileSize > pow(1024, 4)){
		return round($tempFileSize/pow(1024, 4), 2)." TB";
	}else if ($tempFileSize > pow(1024, 3)){
		return round($tempFileSize/pow(1024, 3), 2)." GB";
	}else if ($tempFileSize > pow(1024, 2)){
		return round($tempFileSize/pow(1024, 2), 2)." MB";
	}else if ($tempFileSize > 1024){
		return round(($tempFileSize/1024))." KB";
	}else{
		return $tempFileSize." Bytes";
	}
}
########################################################################
?>
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?PHP
# add the base php libary
include("/usr/share/2web/2webLib.php");
include($_SERVER['DOCUMENT_ROOT']."/header.php");

if (array_key_exists("debug",$_POST)){
	echo "<div class='errorBanner'>\n";
	echo "<hr>\n";
	echo (var_dump($_POST));
	echo "<hr>\n";
	echo "</div>\n";
}
echo "<div class='titleCard'>\n";
echo "<a href='".$_SERVER["REQUEST_URI"]."'>";
echo "<h1>".file_get_contents("prompt.cfg")."</h1>";
echo "</a>";
echo "<div class=''>\n";
$noDiscoveredImages=True;
$discoveredImages=0;
$discoveredImageList=array_diff(scanDir("."),array(".",".."));
$discoveredImageList=array_reverse($discoveredImageList);
$discoveredFileSizes=Array();
$totalFileSize=0;

foreach( $discoveredImageList as $directoryPath){
	if (strpos($directoryPath,".txt")){
		$noDiscoveredImages=False;
		$discoveredImages += 1;

		$tempFileSize=filesize($directoryPath);
		if ($tempFileSize > 0){
			$totalFileSize += $tempFileSize;
		}

		$discoveredFileSizes += Array($directoryPath => $tempFileSize);

	}
}
$totalVersions=file_get_contents("versions.cfg");
# if all versions have not been created
if ($discoveredImages < $totalVersions){
	if (array_key_exists("autoRefresh",$_GET)){
		echo "<img class='localPulse' src='/pulse.gif'>\n";
		echo "<a class='button' href='?'>⏹️ Stop Refresh</a>\n";
	}else{
		echo "<a class='button' href='?autoRefresh'>▶️  Auto Refresh</a>\n";
	}
	echo "<hr>";

	$executionTime = $_SERVER['REQUEST_TIME'] - (file_get_contents("started.cfg")) ;
	$executionMinutes = floor($executionTime / 60);
	$executionSeconds = floor($executionTime - floor($executionMinutes * 60));
	# check for numbers less than 10
	if ($executionMinutes < 10){
		$executionMinutes = "0$executionMinutes" ;
	}
	if ($executionSeconds < 10){
		$executionSeconds = "0$executionSeconds" ;
	}
	if($noDiscoveredImages){
		echo "No responses have finished rendering yet... ";
		echo "<hr>";
	}else{
		$progress=floor(($discoveredImages/$totalVersions)*100);
		echo "<div class='progressBar'>\n";
		echo "\t<div class='progressBarBar' style='width: ".$progress."%;'>\n";
		echo ($discoveredImages."/".$totalVersions." %".$progress);
		echo "\t</div>\n";
		echo "</div>\n";
	}
	# list the time elapsed so far
	echo "<div class='elapsedTime'>Elapsed Time since last prompt $executionMinutes:$executionSeconds</div>\n";
}
if($discoveredImages > 0){
	echo "<table>";
	echo "	<tr>";
	echo "		<th>Discovered Files</th>";
	echo "		<th>Total Filesize</th>";
	echo "		<th>Prompt</th>";
	echo "		<th>Negative Prompt</th>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>$discoveredImages</td>";
	echo "		<td>".filesize_to_human($totalFileSize)."</td>";
	echo "		<td>".file_get_contents("prompt.cfg")."</td>";
	echo "	</tr>";
	echo "</table>";
}
foreach( $discoveredImageList as $directoryPath){
	if (strpos($directoryPath,".txt")){
		//echo "<a class='aiGenComment' href='#$directoryPath' >\n";
		echo "<div class='titleCard' href='#$directoryPath' >\n";
		echo "<h1>".$directoryPath."</h1>";
		echo "<div>";
		echo str_replace("\n","<br>\n",file_get_contents($directoryPath));
		echo "</div>";
		echo "<hr>";
		# load the discovered file size from the array
		echo filesize_to_human($discoveredFileSizes[$directoryPath]);
		echo "</div>\n";
		//echo "</a>\n";
	}
}
echo "</div>\n";

$drawPrompt=False;
if ($discoveredImages < $totalVersions){
	if (array_key_exists("autoRefresh",$_GET)){
		// using javascript, reload the webpage every 60 seconds, time is in milliseconds
		echo "<script>";
		echo "setTimeout(function() { window.location=window.location;},(1000*10));";
		echo "</script>";
	}else{
		$drawPrompt=True;
	}
}else{
	$drawPrompt=True;
}

if ($drawPrompt){
	# draw the image generator
	echo "<div class='titleCard'>\n";
	echo "<h1>Generate More Versions</h1>\n";
	echo "<form method='post' enctype='multipart/form-data' action='/ai/index.php'>\n";

	echo "<span class='groupedMenuItem'>\n";
	echo " Models:\n";
	echo "<select name='model'>\n";
	# load each of the ai models
	echo "<option value='".file_get_contents("model.cfg")."'>".file_get_contents("model.cfg")."</option>\n";
	echo "</select>\n";
	echo "</span>\n";

	echo "<span class='groupedMenuItem'>\n";
	echo "Versions: <input class='imageVersionsInput' type='number' min='1' max='10' value='1' name='imageGenVersions' placeholder='Number of versions to draw'>";
	echo "</span>\n";

	echo "<span class='groupedMenuItem'>Debug:<input class='checkbox' type='checkbox' name='debug' value='yes' ></input></span>";

	echo "<hr>\n";

	echo "<textarea class='aiPrompt' name='imageInputPrompt' placeholder='Image generation prompt, Tags...' >".file_get_contents("prompt.cfg")."</textarea>";
	echo "<input class='aiSubmit' type='submit' formtarget='_blank' value='Prompt'>";
	echo "</form>";
	echo "</div>";
}
echo "</div>\n";
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
