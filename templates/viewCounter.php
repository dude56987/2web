<?PHP
# add the base php libary
include("/usr/share/2web/2webLib.php");
# verify the login before loading the page
requireAdmin();
?>
<!--
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
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include($_SERVER['DOCUMENT_ROOT']."/settings/settingsHeader.php");
################################################################################
if (is_dir("/var/cache/2web/web/views/")){
	createViewsDatabase();
	if (is_file("/var/cache/2web/web/views.db")){
		$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/views.index";
		if (file_exists($cacheFile)){
			# set the time the cached results are kept, in seconds
			if (time()-filemtime($cacheFile) > 10){
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
		if ($writeFile){
			ignore_user_abort(true);
			# load database
			$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");
			# set the timeout to 1 minute since most webbrowsers timeout loading before this
			$databaseObj->busyTimeout(60000);

			# run query view counts
			$result = $databaseObj->query('select * from "view_count" order by views DESC limit 100 ;');

			# open the cache file for writing
			$fileHandle = fopen($cacheFile,'w');

			# build the views database
			$data ="<div class='titleCard'><h2>Url Views</h2><table>";
			$data.="<tr><th>URL</th><th>VIEWS</th></tr>";
			// write the index entry
			echo "$data";
			fwrite($fileHandle, "$data");
			flush();
			ob_flush();

			# fetch each row data individually and display results
			while($row = $result->fetchArray()){
				// read the index entry
				$data="<tr><td class='viewsPathCell'>".$row["url"]."</td><td>".$row["views"]."</td></tr>";
				// write the index entry
				echo "$data";
				fwrite($fileHandle, "$data");
				flush();
				ob_flush();
			}

			$data = "</table></div>\n";
			$data.= "<div class='titleCard'>\n";
			$data.= "<h2>Failed Urls</h2>\n";
			// write the index entry
			echo "$data";
			fwrite($fileHandle, "$data");
			flush();
			ob_flush();

			# run query view counts
			$result = $databaseObj->query('select * from "error_count" order by views DESC limit 100 ;');

			# build the 404 database
			$data ="<table>";
			$data.="<tr><th>404 URL</th><th>VIEWS</th></tr>";
			// write the index entry
			echo "$data";
			fwrite($fileHandle, "$data");
			flush();
			ob_flush();

			# fetch each row data individually and display results
			while($row = $result->fetchArray()){
				// read the index entry
				$data="<tr><td class='viewsPathCell'>".$row["url"]."</td><td>".$row["views"]."</td></tr>";
				// write the index entry
				echo "$data";
				fwrite($fileHandle, "$data");
				flush();
				ob_flush();
			}
			$data="</table></div>";
			// write the index entry
			echo "$data";
			fwrite($fileHandle, "$data");
			flush();
			ob_flush();

			fclose($fileHandle);
			ignore_user_abort(false);
		}else{
			# load the cached file
			echo file_get_contents($cacheFile);
		}
	}
}else{
	// no shows have been loaded yet
	echo "<ul>";
	echo "<li>No Movies have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/settings/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
	echo "<li>Add download links in <a href='/settings/ytdl2nfo.php'>video on demand admin interface</a></li>";
	echo "</ul>";
}

?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
