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
function buildCommitTable($entriesToRead=-1){
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
		echo "		<th>Email</th>\n";
		echo "		<th>Message</th>\n";
		echo "		<th>Log</th>\n";
		echo "		<th>Diff</th>\n";
		echo "		<th>Date</th>\n";
		echo "	</tr>\n";
		$totalFilesScanned=0;
		foreach($sourceFiles as $sourceFile){
			if (($totalFilesScanned % 2) == 1){
				echo "	<tr class='evenTableRow'>\n";
			}else{
				echo "	<tr>\n";
			}
			// read the index entry
			echo "		<td><a href='?commit=$sourceFile'>$sourceFile</a></td>\n";
			echo "		<td><a href='?commit=$sourceFile#author'>📮 <span class='tableShrink'>".file_get_contents("author/$sourceFile.index")."</span></a></td>\n";
			$tempEmail=file_get_contents("email/$sourceFile.index");
			echo "		<td><a href='mailto:".$tempEmail."'>📧 <span class='tableShrink'>".$tempEmail."</span></a></td>\n";
			echo "		<td>".file_get_contents("msg/$sourceFile.index")."</td>\n";
			echo "		<td><a href='?commit=$sourceFile#log'>🧾 <span class='tableShrink'>LOG</span></a></td>\n";
			echo "		<td><a href='?commit=$sourceFile#diff'>↔️ <span class='tableShrink'>DIFF</span></a></td>\n";
			echo "		<td><span class='tableShrink'>📅 </span>".file_get_contents("date/$sourceFile.index")."</td>\n";
			// write the index entry
			echo "	</tr>\n";
			flush();
			ob_flush();
			$totalFilesScanned += 1;
			# loop is -1 by default to allow infinite looping
			if ($entriesToRead == 0){
				break;
			}else{
				$entriesToRead -= 1;
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
	echo "			<a class='button' href='?graph'>📈 Graphs</a>\n";
	echo "			<a class='button' href='?inspector'>🕵️ Inspector Data</a>\n";
	echo "			<a class='button' href='?listLint'>🧹 Lint Data</a>\n";
	#$fileName = popPath(file_get_contents("source.index"));
	#$fileName = str_replace(".index","",$fileName);
	#echo "			<a class='button' href='/zip-gen?repo=".$fileName."'>\n";
	echo "			<a class='button' href='/zip-gen.php?repo=".file_get_contents("title.index")."'>\n";
	echo "				<span class='downloadIcon'>↓</span>\n";
	echo "				Download Source\n";
	echo "			</a>\n";
	echo "		</div>\n";
	echo "	</div>\n";
}
################################################################################
function drawLint(){
	echo "<div class='titleCard'>\n";
	echo "	<h2>Lint</h2>\n";
	echo "	<table>\n";
	echo "	<tr>\n";
	echo "		<th>File</th>\n";
	echo "		<th>Report Length</th>\n";
	echo "		<th>File Type</th>\n";
	echo "		<th>Date</th>\n";
	echo "	</tr>\n";
	#foreach(scanDir("lint/") as $sourceFile){
	$totalFilesScanned=0;
	$totalReportLines=0;
	foreach(recursiveScan("lint/") as $sourceFile){
		if (($totalFilesScanned % 2) == 1){
			echo "	<tr class='evenTableRow'>\n";
		}else{
			echo "	<tr>\n";
		}
		$fileName = popPath($sourceFile);
		$fileTime = str_replace(".index","",$fileName);
		$fileTime = "lint_time/".$fileTime.".index";
		$fileTitle = str_replace(".index","",$fileName);
		$fileExt = explode(".",$fileName)[1];
		$tempLineCount=count(file($sourceFile));
		# get the number of lines in the lint file
		// read the index entry
		echo "	<td><a href='?lint=$fileName'>$fileTitle</a></td>\n";
		echo "	<td>".$tempLineCount."</td>";
		if ($fileExt == "sh"){
			echo "	<td>ShellScript</td>";
		}else if ($fileExt == "js"){
			echo "	<td>Javascript</td>";
		}else if ($fileExt == "php"){
			echo "	<td>PHP</td>";
		}else if ($fileExt == "html"){
			echo "	<td>HTML</td>";
		}else{
			echo "	<td>Unknown</td>";
		}
		echo "	<td>".file_get_contents($fileTime)."</td>\n";
		echo "	</tr>\n";
		$totalReportLines += $tempLineCount;
		$totalFilesScanned += 1;
	}
	echo "</table>\n";
	echo "</div>\n";
	echo "<div class='titleCard'>\n";
	echo "	<h2>Totals</h2>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th>Total Files Scanned</th>\n";
	echo "			<th>Total Report Warnings</th>\n";
	echo "			<th>Warning Ratio</th>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td>".$totalFilesScanned."</td>";
	echo "			<td>".$totalReportLines."</td>";
	echo "			<td>".($totalReportLines / $totalFilesScanned)."%</td>";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</div>\n";
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
}else if (array_key_exists("graph",$_GET)){
	drawHeader();
	$graphName=$_GET['graph'];
	#$graphTitles=Array("day","week","month","year","365_day","diff_365_day","diff_day","diff_week","diff_month","diff_year");
	$graphTitles=Array("commit_day","commit_week","commit_month","commit_year","commit_365_day","diff_day","diff_week","diff_month","diff_year");
	if (in_array($graphName,$graphTitles)){
		$graphName=$_GET['graph'];
	}else{
		$graphName="commit_week";
	}
	echo "<div class='settingListCard'>";
	echo "<div class='listCard'>";
	foreach($graphTitles as $graphTitle){
		echo "	<a href='?graph=$graphTitle' class='showPageEpisode'>";
		#include("graph_$graphTitle.svg");
		echo "		<img class='' src='graph_$graphTitle-thumb.png' />";
		echo "		<div class='indexTitle'>";
		echo ucwords(str_replace("_"," ",$graphTitle));
		echo "		</div>";
		echo "	</a>";
	}
	echo "</div>";
	echo "</div>";
	if (in_array($graphName,$graphTitles)){
		echo "<div class='titleCard'>\n";
		echo "	<h2>".ucwords(str_replace("_"," ",$graphName))."</h2>\n";
		echo "	<a href='graph_$graphName.png' class=''>";
		echo "		<div class='gitCommitListMonthGraph'>";
		#include("graph_$graphName.svg");
		echo "		</div>\n";
		echo "		<img class='gitCommitListMonthGraph' src='graph_$graphName.png' />";
		echo "	</a>";
		echo "</div>\n";
	}
}else if (array_key_exists("list",$_GET)){
	drawHeader();

	echo "<div class='titleCard'>\n";
	echo "	<h2>Commits By Month</h2>\n";
	echo "	<a href='?graph=commit_month' class=''>";
	echo "		<img class='gitCommitListMonthGraph' src='graph_commit_month.png' />";
	echo "	</a>";
	echo "</div>\n";

	echo "<div class='titleCard'>\n";
	echo "	<h2>Commits</h2>\n";
	echo "	<hr>";
	# build the commit table
	buildCommitTable();
	echo "</div>\n";
}else if (array_key_exists("listLint",$_GET)){
	drawHeader();
	drawLint();
}else if (array_key_exists("lint",$_GET)){
	$lintFileName=$_GET['lint'];
	$cleanLintFileName=str_replace(".index","",$lintFileName);
	drawHeader();
	echo "<div class='titleCard'>\n";
	echo "	<h2>Lint Output for '$cleanLintFileName'</h2>\n";
	echo "	<pre>";
	echo file_get_contents("lint/".$lintFileName);
	echo "	</pre>";
	echo "</div>\n";
	drawLint();
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
	echo "		<th>Email</th>\n";
	echo "		<th>Message</th>\n";
	echo "		<th>Log</th>\n";
	echo "		<th>Diff</th>\n";
	echo "		<th>Date</th>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td><a href='?commit=$commitName'>$commitName</a></td>\n";
	echo "		<td><a href='?commit=$commitName#author'>📮 <span class='tableShrink'>".file_get_contents("author/$commitName.index")."</span></a></td>\n";
	$tempEmail=file_get_contents("email/$commitName.index");
	echo "		<td><a href='mailto:".$tempEmail."'>📧 <span class='tableShrink'>".$tempEmail."</span></a></td>\n";
	echo "		<td>".file_get_contents("msg/$commitName.index")."</td>\n";
	echo "		<td><a href='?commit=$commitName#log'>🧾 <span class='tableShrink'>LOG</span></a></td>\n";
	echo "		<td><a href='?commit=$commitName#diff'>↔️ <span class='tableShrink'>DIFF</span></a></td>\n";
	echo "		<td>📅 ".file_get_contents("date/$commitName.index")."</td>\n";
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
	$tempEmail=file_get_contents("email/$commitName.index");
	echo "			<li>Email: <a href='mailto:".$tempEmail."'>".$tempEmail."</a></li>\n";
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
	# draw first commit
	buildCommitTable(0);
	echo "<div>";
	echo "<a href='?graph=commit_365_day'><img class='gitRepoGraph' src='graph_commit_365.png' /></a>";
	#include("graph.svg");
	echo "</div>";
	if (file_exists("repoHistory.webm")){
		echo "	<video controls poster='repoHistory.png'>\n";
		echo "		<source src='repoHistory.webm' type='video/webm'>\n";
		echo "	</video>\n";
	}
	#echo "	<a href='graph_month.png' class='indexSeries right'>";
	#echo "		<img class='gitRepoGraphMonth' src='graph_month.png' />";
	#echo "		<div>";
	#echo "			Commits Monthly";
	#echo "		</div>";
	#echo "	</a>";
	if (file_exists("readme.index")){
		echo 		file_get_contents("readme.index");
	}
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
