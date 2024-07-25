<!--
########################################################################
# 2web iptv link resolver using streamlink
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
<?php
include("/usr/share/2web/2webLib.php");
################################################################################
function debugTrue(){
	if (array_key_exists("debug",$_GET)){
		return true;
	}else{
		return false;
	}
}
function debug($output){
	# check for debug flag
	if (debugTrue()){
		echo "Output = ".$output."<br>";
	}
}
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
if (file_exists("/var/cache/2web/generated/pip/streamlink/bin/streamlink")){
	# look for the 2web pip path
	$streamlinkPath="/var/cache/2web/generated/pip/streamlink/bin/streamlink";
}else{
	# could not find streamlink installed on the server
	echo "[ERROR]: For the URL to resolve you must install streamlink on this server."."<br>";
	echo "[ERROR]: You may need to contact your local system administrator."."<br>";
	echo "[INFO]: As a administrator use 'pip3 install streamlink' to install the latest version."."<br>";
	exit();
}
################################################################################
function writeLog($md5sum, $contents){
	file_put_contents(("RESOLVER-CACHE/".$md5sum.".log"),$contents, FILE_APPEND);
}
################################################################################
function cleanLink($link){
	$videoLink = $link;
	debug("[DEBUG]: URL is ".$videoLink."<br>");
	# decode link first as it was probably encoded last and may include things that need cleaned up
	$videoLink = urldecode($videoLink);
	debug("[DEBUG]: decoding link ".$videoLink."<br>");
	#if (array_key_exists("debug",$_GET)){
	#	echo "[DEBUG]: URL is ".$videoLink."<br>";
	#}
	# remove parenthesis from video link if they exist
	debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
	#if (array_key_exists("debug",$_GET)){
	#	echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
	#}
	// remove parathenesis (")
	while(strpos($videoLink,'"')){
		debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
		#if (array_key_exists("debug",$_GET)){
		#	echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
		#}
		$videoLink = str_replace('"','',$videoLink);
	}
	// remove quotes (')
	while(strpos($videoLink,"'")){
		debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
		#if (array_key_exists("debug",$_GET)){
		#	echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
		#}
		$videoLink = str_replace("'","",$videoLink);
	}
	debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
	#if (array_key_exists("debug",$_GET)){
	#	echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
	#}
	# adding quotes around video link
	//$videoLink = '"'.$videoLink.'"';
	debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
	#if (array_key_exists("debug",$_GET)){
	#	echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
	#}
	return $videoLink;
}
################################################################################
if (array_key_exists("url",$_GET)){
	$videoLink=cleanLink($_GET['url']);
	# create the md5sum of the file
	$sum = md5($videoLink);
	debug("[DEBUG]: MD5SUM is ".$sum."<br>");
	################################################################################
	# get the quality, defaults to worst, this is for making low bandwidth and HD
	# versions of channels
	################################################################################
	if (array_key_exists("HD",$_GET)){
		$quality = "best";
	}else if (array_key_exists("hd",$_GET)){
		$quality = "best";
	}else{
		$quality = "worst";
	}
	################################################################################
	# Get a direct link to the video bypassing the cache.
	# This works on most sites, except youtube.
	################################################################################
	# use the pip package path
	$pathInfo='export PYTHONPATH="/var/cache/2web/generated/pip/streamlink/";';
	#
	$command=$pathInfo.$streamlinkPath.' --stream-url "'.$videoLink.'" '.$quality;
	debug($command);
	#
	$output = shell_exec($command);
	debug("Output = ".$output."<br>");
	if (strpos($output,"error:")){
		debug("Checking for 'error:' in ".$output);
		// the url was not able to resolve
		if (debugTrue()){
			echo 'Location: FAILED.webm';
		}else{
			header('Location: FAILED.webm');
		}
	}else{
		// output is the resolved url
		$url = $output;
		if (debugTrue()){
			echo "<hr>";
			echo "<div>".$output."</div>";
			echo "<hr>";
			echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
			exit();
		}else{
			header('Location: '.$url);
			exit();
		}
	}
}else{
	// no url was given at all
	echo "<html>\n";
	echo "<head>\n";
	echo "<link rel='stylesheet' href='style.css'>\n";
	echo "</head>\n";
	echo "<body>\n";
	// no url was given at all
	echo "No url was specified to the resolver!<br>";
	echo "Please give a valid URL to a video to be resolved.<br>";
	echo "<form method='get'>";
	echo "<input width='60%' type='text' name='url'>";
	echo "	<div>\n";
	echo "		<span>Enable Debug Output<span>\n";
	echo "		<input class='button' width='10%' type='checkbox' name='debug'>\n";
	echo "	</div>\n";
	echo "<input type='submit'>";
	echo "</form>";
	echo '<a href=\'http://'.$_SERVER["HTTP_HOST"].'/iptv-resolver.php?url="NONSENSE"\'>';
	echo 'Play Failed Video';
	echo '</a>';
	echo "php.ini output_buffering=".ini_get("output_buffering");
	echo "<hr>";
	echo "<h2>EXAMPLES</h2>";
	echo "<ul>";
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/iptv-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/iptv-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/iptv-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo "</ul>";
	echo "</body>";
	echo "</html>";
}
?>
