<!--
########################################################################
# 2web exit to external website confirmation page
# Copyright (C) 2025  Carl J Smith
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
<?PHP
################################################################################
# force debugging
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
?>
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?PHP
include("/var/cache/2web/web/header.php");
################################################################################
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
################################################################################
if (array_key_exists("to",$_GET) or array_key_exists("toip",$_GET)){
	# build the link to the extrnal link
	if( array_key_exists("to",$_GET) ){
		$link=urldecode($_GET["to"]);
	}else if( array_key_exists("toip",$_GET) ){
		$link=urldecode($_GET["toip"]);
	}
	echo "<div class='titleCard'>\n";
	echo "	<h2>External Redirect</h2>\n";
	echo "	<ul>\n";
	echo "		<li>\n";
	echo "			This link will redirect to a external website.\n";
	echo "		</li>\n";
	echo "		<li>\n";
	echo "			Click the below link to proceed.\n";
	echo "		</li>\n";
	echo "	</ul>\n";
	# check if the user is accessing this server from a IP address
	if(preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/",$_SERVER["HTTP_HOST"])){
		if(stripos($link,".local") !== false){
			$is_ip_user=true;
		}else{
			$is_ip_user=false;
		}
	}else{
		$is_ip_user=false;
	}
	if( array_key_exists("to",$_GET) and ($is_ip_user == false) ){
		#
		echo "	<div class='listCard'>\n";
		echo "		<a class='button' target='_BLANK' rel='noreferer' href='".$link."'>🌐 ".$link."</a>\n";
		echo "	</div>\n";
		# draw the ip address based output
		echo "	<div class='listCard'>\n";
		echo "		<a class='button' href='/exit.php?toip=".$link."'>🔢 Resolve IP Address Directly</a>\n";
		echo "	</div>\n";
	}else if( array_key_exists("toip",$_GET) or ($is_ip_user == true) ){
		#echo "<h2>link</h2><pre>".var_export($link,true)."</pre>";
		# figure out the domain name
		$linkDomain=str_replace("https://","",$link);
		$linkDomain=str_replace("http://","",$linkDomain);
		#echo "<h2>linkDomain1</h2><pre>".var_export($linkDomain,true)."</pre>";
		$linkDomain=explode("/",$linkDomain);
		#echo "<h2>linkDomain2</h2><pre>".var_export($linkDomain,true)."</pre>";
		$linkDomain=$linkDomain[0];
		#echo "<h2>linkDomain3</h2><pre>".var_export($linkDomain,true)."</pre>";
		$linkDomain=explode(":",$linkDomain);
		#echo "<h2>linkDomain4</h2><pre>".var_export($linkDomain,true)."</pre>";
		$linkDomain=$linkDomain[0];
		#echo "<h2>linkDomain5</h2><pre>".var_export($linkDomain,true)."</pre>";
		# get the ip of the domain with DNS
		$domainIp=gethostbyname($linkDomain);
		#echo "<h2>domainIp</h2><pre>".var_export($domainIp,true)."</pre>";
		# replace the domain name with the ip address in the link
		$linkIp=str_replace($linkDomain,$domainIp,$link);
		# draw the ip address based output
		echo "	<div class='listCard'>\n";
		echo "		<a class='button' target='_BLANK' rel='noreferer' href='".$linkIp."'>🌐 ".$linkIp."</a>\n";
		echo "	</div>\n";
		# also draw the redirect button without the ip
		echo "	<div class='listCard'>\n";
		echo "		<a class='button' target='_BLANK' rel='noreferer' href='".$link."'>🌐 ".$link."</a>\n";
		echo "	</div>\n";
	}
	echo "	<ul>\n";
	echo "		<li>\n";
	echo "			Or you can return to the last page with the button below.\n";
	echo "		</li>\n";
	echo "	</ul>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' onclick='history.back()'>🔙 Go Back To Last Page</a>\n";
	echo "	</div>\n";
	echo "	<hr>";
	echo "</div>\n";
}else{
	echo "<div class='inputCard'>\n";
	echo "	<h2>No Redirect was given</h2>\n";
	echo "	<a class='button' onclick='history.back()'>🔙 Go Back To Last Page</a>\n";
	echo "	<a class='button' href='/'>🏠 Return To The Home Page</a>\n";
	echo "	<hr>";
	echo "</div>\n";
}
include("/var/cache/2web/web/footer.php");
?>
</body>
</html>
