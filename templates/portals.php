<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("portal2web");
?>
<!--
########################################################################
# 2web portal index
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
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
drawPosterWidget("portal");
################################################################################
?>
<div class='settingListCard'>
<h1>
	Portal
	<img class='globalPulse' src='/pulse.gif' />
</h1>
<?php
$portalLinks=scanDir(".");
sort($portalLinks);
$portalLinks=array_diff($portalLinks, Array("portal.index"));
# scan for links
foreach($portalLinks as $portalLink){
	if (stripos($portalLink, ".index")){
		# load each portal link
		echo file_get_contents($portalLink);
	}
}
?>
</div>
<?php
// add random music above the footer
drawPosterWidget("portal", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
