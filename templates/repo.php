<!--
########################################################################
# 2web git repo webpage
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
<?PHP
################################################################################
function buildCommitTable($onlyOne=False){
	if (file_exists("commits.index")){
		echo "	<table>\n";
		// get a list of all the genetrated index links for the page
		#$sourceFiles = explode("\n",file_get_contents("albums.index"));
		$sourceFiles = file("commits.index", FILE_IGNORE_NEW_LINES);
		// reverse the time sort
		#$sourceFiles = array_unique($sourceFiles);
		// sort the files by name
		#sort($sourceFiles);
		#echo var_dump($sourceFiles);
		echo "	<tr>\n";
		echo "		<th>Commit</th>\n";
		echo "		<th>Author</th>\n";
		echo "		<th class='gitEmailRow'>Email</th>\n";
		echo "		<th>Message</th>\n";
		echo "		<th>Log</th>\n";
		echo "		<th>Diff</th>\n";
		echo "		<th>Date</th>\n";
		echo "	</tr>\n";
		foreach($sourceFiles as $sourceFile){
			echo "	<tr>";
			// read the index entry
			echo "		<td><a href='?commit=$sourceFile'>$sourceFile</a></td>\n";
			echo "		<td>".file_get_contents("author/$sourceFile.index")."</td>\n";
			echo "		<td class='gitEmailRow'>".file_get_contents("email/$sourceFile.index")."</td>\n";
			echo "		<td>".file_get_contents("msg/$sourceFile.index")."</td>\n";
			echo "		<td><a href='?commit=$sourceFile#log'>LOG</a></td>\n";
			echo "		<td><a href='?commit=$sourceFile#diff'>DIFF</a></td>\n";
			echo "		<td>".file_get_contents("date/$sourceFile.index")."</td>\n";
			// write the index entry
			echo "	</tr>\n";
			flush();
			ob_flush();
			if ($onlyOne){
				break;
			}
		}
		echo "	</table>\n";
	}
}
################################################################################
function drawHeader(){
	echo "	<div class='titleCard'>\n";
	echo "		<h1>".file_get_contents("title.index")."</h1>";
	echo "		<div class='listCard'>\n";
	echo "			<a class='button' href='?all'>🗂️ Repository Overview</a>\n";
	echo "			<a class='button' href='?list'>💼 All Commits</a>\n";
	echo "			<a class='button' href='?inspector'>🕵️ Inspector Data</a>\n";
	echo "		</div>\n";
	echo "	</div>\n";
}
################################################################################
?>
<html class='randomFanart'>
<head>
	<script src='/2web.js'></script>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
# add the base php libary
include("/usr/share/2web/2webLib.php");
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");

?>
<?php
if (array_key_exists("inspector",$_GET)){
	drawHeader();
	echo "<div class='titleCard'>\n";
	echo "<h2>Inspector Data</h2>\n";
	#echo "<iframe src='inspector.html' scrolling='no' />\n";
	# draw the inspector report
	echo "<pre>";
	include("inspector.html");
	echo "</pre>";
	echo "</div>\n";
}else if (array_key_exists("list",$_GET)){
	drawHeader();
	echo "<div class='titleCard'>\n";
	echo "	<h2>Commits</h2>\n";
	# build the commit table
	buildCommitTable();
	echo "</div>\n";
}else if (array_key_exists("commit",$_GET)){
	$commitName=$_GET['commit'];
	drawHeader();
	echo "<div class='settingListCard'>\n";
	echo "	<h2>Commit: $commitName</h2>\n";
	# build the header table containing this commit
	echo "<table>\n";
	echo "	<tr>\n";
	echo "		<th>Commit</th>\n";
	echo "		<th>Author</th>\n";
	echo "		<th class='gitEmailRow'>Email</th>\n";
	echo "		<th>Message</th>\n";
	echo "		<th>Log</th>\n";
	echo "		<th>Diff</th>\n";
	echo "		<th>Date</th>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td><a href='?commit=$commitName'>$commitName</a></td>\n";
	echo "		<td>".file_get_contents("author/$commitName.index")."</td>\n";
	echo "		<td class='gitEmailRow'>".file_get_contents("email/$commitName.index")."</td>\n";
	echo "		<td>".file_get_contents("msg/$commitName.index")."</td>\n";
	echo "		<td><a href='?commit=$commitName#log'>LOG</a></td>\n";
	echo "		<td><a href='?commit=$commitName#diff'>DIFF</a></td>\n";
	echo "		<td>".file_get_contents("date/$commitName.index")."</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "	<div class='titleCard'>\n";
	echo "		<div class='listCard'>\n";
	echo "			<a class='button' href='?commit=$commitName#author'>📮 Commit Metadata</a>\n";
	echo "			<a class='button' href='?commit=$commitName#log'>🧾 Log Message</a>\n";
	echo "			<a class='button' href='?commit=$commitName#diff'>↔️ Diff</a>\n";
	echo "		</div>\n";
	echo "	</div>\n";
	echo "	<div class='titleCard' id='author'>\n";
	echo "		<h2>Commit Metadata</h2>\n";
	echo "		<ul>\n";
	echo "			<li>Author: ".file_get_contents("author/$commitName.index")."</li>\n";
	echo "			<li>Email: ".file_get_contents("email/$commitName.index")."</li>\n";
	echo "			<li>Commit Date: ".file_get_contents("date/$commitName.index")."</li>\n";
	echo "		</ul>\n";
	echo "	</div>\n";
	echo "	<div id='log' class='titleCard'>\n";
	echo "		<h2>Log</h2>\n";
	echo "		<pre>\n";
	echo 				file_get_contents("log/$commitName.index");
	echo "		</pre>\n";
	echo "	</div>\n";
	echo "	<div id='diff' class='titleCard'>\n";
	echo "		<h2>Diff</h2>\n";
	echo "		<pre>\n";
	echo 				file_get_contents("diff/$commitName.index");
	echo "		</pre>\n";
	echo "	</div>\n";
	echo "</div>\n";
}else{
	drawHeader();
	echo "<div class='titleCard'>\n";
	echo "	<h2>Repo</h2>\n";
	buildCommitTable(True);
	#echo "<img src='graph.svg' />";
	echo "<div>";
	include("graph.svg");
	echo "</div>";
	echo "	<video controls>\n";
	echo "	<source src='repoHistory.mp4' type='video/mp4'>\n";
	echo "	</video>\n";
	echo 		file_get_contents("readme.index");
	echo "</div>\n";
	/*
	echo "<div class='titleCard'>\n";
	echo "	<h2>Commits</h2>\n";
	# build the commit table
	buildCommitTable();
	echo "</div>\n";
	*/
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
