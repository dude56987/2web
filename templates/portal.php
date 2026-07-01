<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("portal2web");
?>
<!--
########################################################################
# 2web portal index
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
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
		# find the domain name
		$scriptDomain=basename(dirname($_SERVER["SCRIPT_NAME"]));
		#$scriptDomain=str_ireplace(".php","",$_SERVER["SCRIPT_NAME"]);
		echo "<title>".ucfirst(gethostname())." - Portal - ".ucfirst($scriptDomain)."</title>\n";
	?>
</head>
<body>
<?php
################################################################################
function replaceLink($search, $replace, $filePath){
	# run search and replace on inside contents of href tag
	$fileObject = file($filePath);
	$tempText="";
	foreach($fileObject as $line){
		$line = str_replace("\n", "", $line);
		# replace lines that contain the href
		if (stripos($line, "href=") !== false){
			# if the line is a link replace the string
			$tempText .= str_replace($search, $replace, $line);
		}else{
			# add each line not containing a href link to the return output
			$tempText .= $line;
		}
	}
	return $tempText;
}
################################################################################
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
#drawPosterWidget("portal");
################################################################################
?>
<div class='settingListCard'>
<?php
#
$portalLinks=file("portal.index");
# remove the domain link itself
$portalLinks=array_diff($portalLinks, Array($scriptDomain.".index"));
# load each portal link that is also in this domain
echo "<h1>";
echo "	$scriptDomain";
echo "</h1>";
# set the is_ip variable to track if the web browser is accessing a ip address or a .local address
$is_ip=false;
# draw each of the links
foreach($portalLinks as $portalLink){
	$portalLink=str_replace("\n","",$portalLink);
	if (strpos($portalLink, ".index") !== false){
		# cleanup the path
		$portalLink=str_replace("/var/cache/2web/web/portal/","",$portalLink);
		# load each portal link
		echo "<div class='listCard'>";
		echo "	".file_get_contents(str_replace("\n","",$portalLink));
		# draw the preview box
		echo "	<div class='portalPreviewContainer'>";
		echo "		<a href='".str_replace(".index","-web.png",$portalLink)."'>";
		echo "			<h3>Preview</h3>";
		echo "			<img class='portalPreview' loading='lazy' src='".str_replace(".index","-thumb.png",$portalLink)."'>";
		echo "		</a>";
		echo "	</div>";
		# link the qr image with the domain link using .local domains
		echo "	<div class='portalPreviewContainer'>";
		echo "		<a href='".str_replace(".index","-qr.png",$portalLink)."'>";
		echo "			<h3>HD QR Domain Link</h3>";
		echo "			<img class='portalPreview' loading='lazy' src='".str_replace(".index","-qr.png",$portalLink)."'>";
		echo "		</a>";
		echo "	</div>";
		echo "</div>";
	}
}
?>
</div>
<?php
clear();
echo "<hr class='ruler'>\n";
# draw the widgets
loadSearchIndexResults($scriptDomain);
drawPosterWidget("portal", True);
echo "<hr class='ruler'>\n";
# draw more search links
drawMoreSearchLinks($scriptDomain);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
