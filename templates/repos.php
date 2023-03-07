<!--
########################################################################
# 2web git repo index
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
# add the updated repos below the header
drawPosterWidget("repos");
################################################################################
?>
<div class='settingListCard'>
<h1>
	repos
	<img id='spinner' src='/spinner.gif' />
</h1>
<?php
flush();
ob_flush();
# store the index path
$indexFilePath="/var/cache/2web/web/repos/repos.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No repos have been scanned into the libary!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/repos.php'>video on demand admin interface</a> to populate this page.</li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>

<?php
// add random repos above the footer
drawPosterWidget("repos", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
