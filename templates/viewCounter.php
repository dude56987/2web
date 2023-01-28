<?PHP
########################################################################
# 2web view counter stats page
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
?>
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
# add the base php libary
include("/usr/share/2web/2webLib.php");
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
################################################################################
?>
<div class='settingListCard'>
<?php
if (is_dir("/var/cache/2web/web/views/")){
	echo "<table>";
	echo "<ul>";
	// get a list of all the genetrated index links for the page
	$sourceFiles = scandir("/var/cache/2web/web/views/");
	// reverse the time sort
	$sourceFiles = array_unique($sourceFiles);
	# sort the list
	sort($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".cfg")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "<tr>";
					echo "	<td>";
					#$tempOutputData=str_replace("_","/",$sourceFile);
					$tempOutputData=str_replace("(#)","/",$sourceFile);
					# after the ? if its in the output data replace / with _
					$slicePoint=strpos("?",$tempOutputData);
					if ($slicePoint){
						$tempSplit=explode("?",$tempOutputData);
						$frontSide = array_slice($tempOutputData, 0, $slicePoint);
						$backSide = array_slice($tempOutputData, $slicePoint, count($tempOutputData));
						# fix formatting of the backside elements, $_GET data
						$tempOutputData = $frontSide.str_replace("(#)","/",$backside);
					}
					$tempOutputData=str_replace(".cfg","",$tempOutputData);
					echo "		<a href='".$tempOutputData."'>".$tempOutputData."</a>";
					echo "	</td>";
					echo "	<td>";
					echo "		$data";
					echo "	</td>";
					echo "</tr>";
					flush();
					ob_flush();
				}
			}
		}
	}
	echo "</ul>";
	echo "</table>";
}else{
	// no shows have been loaded yet
	echo "<ul>";
	echo "<li>No Movies have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/settings/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
	echo "<li>Add download links in <a href='/settings/ytdl2nfo.php'>video on demand admin interface</a></li>";
	echo "</ul>";
}

?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
