<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web path selction utility
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
ini_set('display_errors', 1);
error_reporting(E_ALL);
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
# check the value name has been given
if(array_key_exists("valueName",$_POST)){
	$valueName=$_POST["valueName"];
	# if the start path is not set set it to root
	if(array_key_exists("startPath",$_POST)){
		$startPath=$_POST["startPath"];
	}else{
		$startPath="/";
	}
	# blank start path should become the default
	if($startPath == ""){
		$startPath="/";
	}
	# make sure the start path is a directory and is readable
	if(! is_dir($startPath)){
		$startPath="/";
	}
	if(! is_readable($startPath)){
		$startPath="/";
	}

	echo "<div class='titleCard'>\n";
	echo "<h1>Select Path</h1>";
	#
	echo "<form action='/settings/admin.php' method='post'>\n";
	echo "	<input type='text' name='$valueName' value='$startPath'>\n";
	#echo "	<button class='smallButton' type='submit'>➕ Use Path '".$startPath."'</button>\n";
	echo "	<button class='smallButton' type='submit'>➕ Use Path</button>\n";
	echo "</form>\n";
	echo "<h2>Choose Path</h2>";

	#if(dirname($startPath) == "/"){
	#	# draw the parent path link
	#	echo "<form action='/settings/selectPath.php' method='post'>\n";
	#	echo "	<input type='text' name='valueName' value='$valueName' hidden>\n";
	#	echo "	<button class='smallButton' type='submit'>📂 .. 0_0</button>\n";
	#	echo "</form>\n";
	#}else{
	#if(dirname($startPath) != "/"){
	if($startPath != "/"){
		# draw the parent path link
		echo "<form action='/settings/selectPath.php' method='post'>\n";
		echo "	<input type='text' name='valueName' value='$valueName' hidden>\n";
		if(dirname($startPath) != "/"){
		#	echo "	<input type='text' name='startPath' value='/' hidden>\n";
		#}else{
			echo "	<input type='text' name='startPath' value='".dirname($startPath)."/' hidden>\n";
		}
		echo "	<button class='smallButton' type='submit'>📂 ..</button>\n";
		echo "</form>\n";
	}

	$printChildData=false;
	# draw the path and the subpaths found in that path
	if(is_dir($startPath)){
		#$childPathData="<h2>Child Paths</h2>";
		$childPathData="<table>";
		#$childPathData="";
		# scan the start path for the sub directories
		$foundPaths=array_diff(scandir($startPath),Array("..","."));
		# draw table rows
		$tableRowEven=false;
		foreach($foundPaths as $foundPath){
			$foundPath=$foundPath."/";
			if (is_readable($startPath.$foundPath)){
				if (is_dir($startPath.$foundPath)){
					# do not display hidden folders
					if (substr($foundPath,0,1) != "."){
						$printChildData=true;
						#if ($tableRowEven){
						#	$childPathData.="<tr class='evenTableRow'>";
						#}else{
							$childPathData.="<tr>";
						#}
						$childPathData.="<td>";
						# draw the links
						$childPathData.="<form class='selectPathOpenForm' action='/settings/selectPath.php' method='post'>\n";
						$childPathData.="	<input type='text' name='valueName' value='$valueName' hidden>\n";
						$childPathData.="	<input type='text' name='startPath' value='$startPath$foundPath' hidden>\n";
						$childPathData.="	<button class='smallButton' type='submit'>📂 ".$startPath.$foundPath."</button>\n";
						$childPathData.="</form>\n";
						$childPathData.="</td>";
						$childPathData.="</tr>";
						#if ($tableRowEven){
						#	$tableRowEven=false;
						#}else{
						#	$tableRowEven=true;
						#}
					}
				}
			}
		}
		$childPathData.="</table>";
	}
	if($printChildData){
		echo $childPathData;
	}
	echo "</div>\n";
}else{
	# no value has been given to be set
	$userAgent=$_SERVER["HTTP_USER_AGENT"];
	$remoteIP=$_SERVER["REMOTE_ADDR"];
	# build the remote user data
	$errorMessage="No valueName has been set to give a path to in selectPath.php<br>";
	$errorMessage.="User Agent -> '$userAgent'<br>";
	$errorMessage.="Remote IP -> '$remoteIP'<br>";
	# log the user info for the failed API request
	addToLog("ERROR","No value given to select path API",$errorMessage);
}
#
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
