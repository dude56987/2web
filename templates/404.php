<?PHP
include("/usr/share/2web/2webLib.php");
requireGroup("2web");
$unknownUrl=$_SERVER['REQUEST_URI'];
if (stripos($unknownUrl,".png") !== false){
	# this is a broken image link
	if (stripos($unknownUrl,"poster.png") !== false){
		redirect("/plasmaPoster.png");
	}else if (stripos($unknownUrl,"fanart.png") !== false){
		redirect("/plasmaFanart.png");
	}else{
		redirect("/404.png");
	}
}
?>
<!--
########################################################################
# 2web 404 error page
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
	<link rel="stylesheet" href="/style.css" />
	<title>404</title>
	<script src='/2webLib.js'></script>
</head>
<body>
	<?PHP
		include($_SERVER['DOCUMENT_ROOT']."/header.php");
	?>
	<div class='titleCard'>
		<h2>404</h2>
		<p>
			The path could not be resolved on our server.
		</p>
		<pre><?PHP echo $_SERVER['REQUEST_URI']; ?></pre>
		<div class='listCard'>
			<a class='button' onclick='window.location.reload(true);'>🔃 Reload Link</a>
			<a class='button' onclick='history.back();'>🔙 Return To Last Page</a>
			<a class='button' href='/'>🏠 Return to Homepage</a>
		</div>
		<?PHP
			$unknownUrl=$_SERVER['REQUEST_URI'];
			// break the url into sub urls tracing back to the last viable url since identifiable url paths are used
			$urlArray=explode('/',$unknownUrl);
			echo "<h2>Go To</h2>\n";
			echo "<div class='listCard'>\n";
			$pretext='/';
			# remove blank string items from the array
			$urlArray=array_diff($urlArray,Array(''));
			// build the clickable path
			foreach($urlArray as $url){
				$pretext=$pretext.$url.'/';
				echo "<a href='$pretext' class='button'>";
				echo "🔗 $pretext";
				echo "</a>\n";
			}
			echo "</div>\n";
		?>
		<h2>Search For</h2>
		<div class='listCard'>
		<?PHP
			# draw the search links
			foreach($urlArray as $url){
				echo "	<a href='/search.php?q=$url' class='button'>";
				echo "		🔎 $url";
				echo "	</a>\n";
			}
		?>
		</div>
		<?PHP
			# write the 404 request to 404.db

			# write page views to sql database
			ignore_user_abort(true);
			# if the view count database does not exist create it
			if (! file_exists($_SERVER['DOCUMENT_ROOT']."/views.db")){
				createViewsDatabase();
			}
			# load the views database add 404 section
			$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");
			# set the timeout to 1 minute since most webbrowsers timeout loading before this
			$databaseObj->busyTimeout(60000);
			# load the views database
			# - scriptName includes php get API request data
			$databaseSearchQuery='select * from "error_count" where url = \''.$_SERVER['REQUEST_URI'].'\';';
			$result = $databaseObj->query($databaseSearchQuery);
			# search views database for this pages view count
			$data = $result->fetchArray();
			# if the current url url is in the database
			if ( $data != false){
				# increment the view counter
				$updatedViewCount = $data["views"] + 1;
			}else{
				$updatedViewCount = 1;
			}
			$dbUpdateQuery  = 'REPLACE INTO "error_count" (url, views) ';
			$dbUpdateQuery .= "VALUES ('".$_SERVER['REQUEST_URI']."', '".$updatedViewCount."') ";
			$dbUpdateQuery .= ";";
			# update the database
			$databaseObj->query($dbUpdateQuery);
		?>
	</div>
	<?php
		// add the footer
		include($_SERVER['DOCUMENT_ROOT']."/footer.php");
	?>
</body>
</html>
