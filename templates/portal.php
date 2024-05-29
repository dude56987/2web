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
function replaceLink($search, $replace, $filePath){
	# run search and replace on inside contents of href tag
	$fileObject = file($filePath);
	$tempText="";
	foreach($fileObject as $line){
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
echo "	<img class='globalPulse' src='/pulse.gif'>";
?>
<div class='settingListCard'>
<?php
$portalLinks=scanDir(".");
sort($portalLinks);
$portalLinks=array_diff($portalLinks, Array("portal.index"));
# scan for links
$scriptDomain=str_ireplace(".php","",$_SERVER["SCRIPT_NAME"]);
$scriptDomain=str_ireplace("/portal/","",$scriptDomain);
$hostnameIp=gethostbyname($scriptDomain);
# load each portal link that is also in this domain
echo "<h1>";
echo "	$scriptDomain";
echo "</h1>";
# draw the resolve switch buttons
echo "<div class='listCard'>\n";
if (key_exists("ip",$_GET)){
	echo "<a class='button' href='?'>Auto</a>\n";
	echo "<a class='button' href='?domain'>Domain Links</a>\n";
	echo "<a class='button activeButton' href='?ip'>IP Links</a>\n";
}else if (key_exists("domain",$_GET)){
	echo "<a class='button' href='?'>Auto</a>\n";
	echo "<a class='button activeButton' href='?domain'>Domain Links</a>\n";
	echo "<a class='button' href='?ip'>IP Links</a>\n";
}else{
	echo "<a class='button activeButton' href='?'>Auto</a>\n";
	echo "<a class='button' href='?domain'>Domain Links</a>\n";
	echo "<a class='button' href='?ip'>IP Links</a>\n";
}
echo "</div>\n";
# draw each of the links
foreach($portalLinks as $portalLink){
	if (strpos($portalLink, ".index") !== false){
		if (strpos($portalLink, $scriptDomain) !== false){
			# load each portal link
			echo "<div class='listCard'>";
			if (key_exists("ip",$_GET)){
				# build the ip based link
				echo "	".replaceLink($scriptDomain, $hostnameIp, $portalLink);
			}else if (key_exists("domain",$_GET)){
				# build the regular link
				echo "	".file_get_contents($portalLink);
			}else{
				# automatic detection of ip address
				# - this will replace the link if the current URL is being accessed with a direct IP
				if(preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/",$_SERVER["HTTP_HOST"])){
					echo "	".replaceLink($scriptDomain, $hostnameIp, $portalLink);
				}else{
					echo "	".file_get_contents($portalLink);
				}
			}
			echo "	<div class='portalPreviewContainer'>";
			echo "		<a href='".str_replace(".index","-web.png",$portalLink)."'>";
			echo "			<h3>Preview</h3>";
			echo "			<img class='portalPreview' loading='lazy' src='".str_replace(".index","-web.png",$portalLink)."'>";
			echo "		</a>";
			echo "	</div>";
			echo "	<div class='portalPreviewContainer'>";
			echo "		<a href='".str_replace(".index","-qr.png",$portalLink)."'>";
			echo "			<h3>HD QR</h3>";
			echo "			<img class='portalPreview' loading='lazy' src='".str_replace(".index","-qr.png",$portalLink)."'>";
			echo "		</a>";
			echo "	</div>";
			#echo "	".str_replace($scriptDomain, $hostnameIp, file_get_contents($portalLink));
			echo "</div>";
		}
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
