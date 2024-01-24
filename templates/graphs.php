<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("graph2web");
?>
<!--
########################################################################
# 2web graph index
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
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
	// add random comics below the header
	drawPosterWidget("graphs");
?>
<div class='settingListCard'>
<h1>
	Graphs
	<img id='spinner' src='/spinner.gif' />
</h1>
<?php
# store the index path
$indexFilePath="/var/cache/2web/web/graphs/graphs.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No Munin Graphs have been generated!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/graphs.php'>comics admin interface</a> to populate this page.</li>";
$emptyMessage .= "<li>Add download links in <a href='/settings/graphs.php'>comics admin interface</a></li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>

<?php
	// add random comics above the footer
	drawPosterWidget("graphs", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
