<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("portal2web");
?>
<!--
########################################################################
# 2web portal index
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
include("/var/cache/2web/web/header.php");
?>

<?php
drawPosterWidget("portal");
################################################################################
if (file_exists("portal.index")){
	$portalLinks=file("portal.index");
	$domainLinks=file("domain.index");
	# find the bookmarks
	$bookmarkLinks=Array();
	$bookmarkDomainLinks=Array();
	foreach($portalLinks as $portalLink){
		if (stripos($portalLink,"www.") !== false){
			$bookmarkLinks=array_merge($bookmarkLinks,Array($portalLink));
		}
	}
	$portalLinks=array_diff($portalLinks,$bookmarkLinks);
	foreach($domainLinks as $domainLink){
		if (stripos($domainLink,"www.") !== false){
			$bookmarkDomainLinks=array_merge($bookmarkDomainLinks,Array($domainLink));
		}
	}
	$domainLinks=array_diff($domainLinks,$bookmarkDomainLinks);

	echo "<div class='settingListCard'>";
	echo "	<h1>";
	echo "		Domains";
	echo "	</h1>";
	echo "	<div class='listCard'>";
	# scan for links
	foreach($domainLinks as $portalLink){
		echo file_get_contents(str_replace("\n","",$portalLink));
	}
	echo "	</div>";
	echo "</div>";

	echo "<div class='settingListCard'>";
	echo "<h1>";
	echo "	Local Portal";
	echo "</h1>";
	# scan for links
	foreach($portalLinks as $portalLink){
		# load each portal link
		echo file_get_contents(str_replace("\n","",$portalLink));
	}
	echo "</div>";

	echo "<div class='settingListCard'>";
	echo "	<h1>";
	echo "		Bookmark Domains";
	echo "	</h1>";
	echo "	<div class='listCard'>";
	# scan for links
	foreach($bookmarkDomainLinks as $bookmarkDomainLink){
		echo file_get_contents(str_replace("\n","",$bookmarkDomainLink));
	}
	echo "	</div>";
	echo "</div>";
	echo "<div class='settingListCard'>";
	echo "<h1>";
	echo "	External Bookmarks";
	echo "</h1>";
	# scan for links
	foreach($bookmarkLinks as $bookmarkLink){
		# load each portal link
		echo file_get_contents(str_replace("\n","",$bookmarkLink));
	}
	echo "</div>";
}else{
	echo "<div class='settingListCard'>";
	echo "<h1>";
	echo "	Portal";
	echo "</h1>";
	echo "<ul>";
	echo "<li>No Portal links have been scanned into the library!</li>";
	echo "<li>Add links in the <a href='/settings/portal.php'>portal admin interface</a> to populate this page.</li>";
	echo "</ul>";
	echo "</div>";
}
// add random music above the footer
drawPosterWidget("portal", True);
// add the footer
include("/var/cache/2web/web/footer.php");
?>
</body>
</html>
