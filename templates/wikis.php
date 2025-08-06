<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("wiki2web");
?>
<!--
########################################################################
# 2web wiki index
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
	<title>2web Wikis</title>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
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
# add the updated movies below the header
drawPosterWidget("wikis");
################################################################################
?>
<div class='settingListCard'>
<h1>Local Wikis</h1>
<?php
if (file_exists("/var/cache/2web/web/wiki/wikis.index")){
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",file_get_contents("/var/cache/2web/web/wiki/wikis.index"));
	// reverse the time sort
	$sourceFiles = array_unique($sourceFiles);
	# sort the list
	sort($sourceFiles);
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
}else{
	// no shows have been loaded yet
	echo "<ul>";
	echo "<li>No Wikis have been scanned into the libary!</li>";
	echo "</ul>";
}

?>
</div>
<?php
// add random movies above the footer
drawPosterWidget("wikis", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
