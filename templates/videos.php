<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("video2web");
?>
<!--
########################################################################
# 2web video index webpage
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
-->
<html id='top' class='videosFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
		echo "<title>".ucfirst(gethostname())." - Videos</title>\n";
	?>
</head>
<body>

<?php
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
	drawPosterWidget("videos");
?>

<hr>

<div class='settingListCard'>
<h1>
	videos
</h1>
<?php
flush();
ob_flush();
# store the index path
$indexFilePath="/var/cache/2web/web/videos/videos.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No videos Have been scanned into the library!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/videos.php'>videos admin interface</a> to populate this page.</li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>

<?php
	// add random videos above the footer
	drawPosterWidget("videos", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
