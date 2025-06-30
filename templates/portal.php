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
# scan for links
$scriptDomain=basename(dirname($_SERVER["SCRIPT_NAME"]));
#$scriptDomain=str_ireplace(".php","",$_SERVER["SCRIPT_NAME"]);
#$scriptDomain=str_ireplace("/portal/","",$scriptDomain);
$hostnameIp=gethostbyname($scriptDomain);
# remove the domain link itself
$portalLinks=array_diff($portalLinks, Array($scriptDomain.".index"));
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
# set the is_ip variable to track if the web browser is accessing a ip address or a .local address
$is_ip=false;
# draw each of the links
foreach($portalLinks as $portalLink){
	$portalLink=str_replace("\n","",$portalLink);
	if (strpos($portalLink, ".index") !== false){
		#if (strpos($portalLink, $scriptDomain) !== false){
			# cleanup the path
			$portalLink=str_replace("/var/cache/2web/web/portal/","",$portalLink);
			# load each portal link
			echo "<div class='listCard'>";
			if (key_exists("ip",$_GET)){
				# build the ip based link
				echo "	".replaceLink($scriptDomain, $hostnameIp, $portalLink);
				$is_ip=true;
			}else if (key_exists("domain",$_GET)){
				# build the regular link
				echo "	".file_get_contents(str_replace("\n","",$portalLink));
			}else{
				# automatic detection of ip address
				# - this will replace the link if the current URL is being accessed with a direct IP
				if(preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/",$_SERVER["HTTP_HOST"])){
					echo "	".replaceLink($scriptDomain, $hostnameIp, $portalLink);
					$is_ip=true;
				}else{
					echo "	".file_get_contents(str_replace("\n","",$portalLink));
				}
			}
			# draw the preview box
			echo "	<div class='portalPreviewContainer'>";
			echo "		<a href='".str_replace(".index","-web.png",$portalLink)."'>";
			echo "			<h3>Preview</h3>";
			echo "			<img class='portalPreview' loading='lazy' src='".str_replace(".index","-thumb.png",$portalLink)."'>";
			echo "		</a>";
			echo "	</div>";
			# draw the qr code based on the access method
			if($is_ip){
				# build the paths
				$qrLink=str_replace(".index","-qr-ip.png",$portalLink);
				# join the path to create the full path
				$qrPath=$_SERVER["DOCUMENT_ROOT"]."/portal/$scriptDomain/".$qrLink;
				# check for the ip qr code image
				if (! file_exists($qrPath)){
					# make a qrcode image using the job queue for this ip
					$command="/usr/bin/qrencode --background='00000000' -m 1 -l H -o '$qrPath' '$qrLink'";
					# add the command to build the image
					addToQueue("multi",$command);
					# sleep one second to allow image to be generated before page is loaded
					sleep(1);
				}
				# link the qr image using the resolved ip address
				echo "	<div class='portalPreviewContainer'>";
				echo "		<a href='".$qrLink."'>";
				echo "			<h3>HD QR IP Link</h3>";
				echo "			<img class='portalPreview' loading='lazy' src='$qrLink'>";
				echo "		</a>";
				echo "	</div>";
				echo "</div>";
			}else{
				# link the qr image with the domain link using .local domains
				echo "	<div class='portalPreviewContainer'>";
				echo "		<a href='".str_replace(".index","-qr.png",$portalLink)."'>";
				echo "			<h3>HD QR Domain Link</h3>";
				echo "			<img class='portalPreview' loading='lazy' src='".str_replace(".index","-qr.png",$portalLink)."'>";
				echo "		</a>";
				echo "	</div>";
				echo "</div>";
			}
		#}
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
