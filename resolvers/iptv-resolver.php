<?php

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
if (file_exists("/usr/local/bin/streamlink")){
	$streamlinkPath="/usr/local/bin/streamlink";
}
else if (file_exists("/usr/bin/streamlink")){
	$streamlinkPath="/usr/bin/streamlink";
}else{
	# could not find streamlink installed on the server
	echo "[ERROR]: For the URL to resolve you must install streamlink on this server."."<br>";
	echo "[ERROR]: You may need to contact your local system administrator."."<br>";
	echo "[INFO]: As a administrator use 'pip3 install streamlink' to install the latest version."."<br>";
	exit();
}
################################################################################
debug('<hr>'.$streamlinkPath.' --stream-url '.$_GET['url']."<br>\n");
#if (array_key_exists("debug",$_GET)){
#	echo '<hr>';
#	echo $streamlinkPath.' --stream-url '.$_GET['url']."<br>\n";
#}
################################################################################
function writeLog($md5sum, $contents){
	file_put_contents(("RESOLVER-CACHE/".$md5sum.".log"),$contents, FILE_APPEND);
}
################################################################################
function cleanLink($link){
	$videoLink = $link;
	debug("[DEBUG]: URL is ".$videoLink."<br>");
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
	debug($streamlinkPath.' --stream-url "'.$videoLink.'"'."<br>");
	#if (array_key_exists("debug",$_GET)){
	#	echo "[DEBUG]: MD5SUM is ".$sum."<br>";
	#	echo $streamlinkPath.' --stream-url "'.$videoLink.'"'."<br>";
	#}
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
	$output = shell_exec($streamlinkPath.' --stream-url "'.$videoLink.'" '.$quality);
	debug("Output = ".$output."<br>");
	#if (array_key_exists("debug",$_GET)){
	#	echo "Output = ".$output."<br>";
	#}
	#if ($output == null || strpos($output,"error:")){
	if (strpos($output,"error:")){
		debug("Checking for 'error:' in ".$output);
		// the url was not able to resolve
		//echo "The URL '".$_GET['url']."' was unable to resolve...";
		//echo "The URL was unable to resolve...<br>";
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
	echo "No url was specified to the resolver!<br>";
	echo "Please give a valid URL to a video to be resolved.<br>";
	echo "<form method='get'>";
	echo "<input width='60%' type='text' name='url'>";
	echo "<input type='submit'>";
	echo "</form>";
	echo '<a href=\'http://'.gethostname().'.local:121/iptv-resolver.php?url="NONSENSE"\'>';
	echo 'Play Failed Video';
	echo '</a>';
	echo "php.ini output_buffering=".ini_get("output_buffering");
	echo "<hr>";
	echo "<h2>EXAMPLES</h2>";
	echo "<ul>";
	echo '	<li>';
	echo '		http://'.gethostname().'.local:121/iptv-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local:121/iptv-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local:121/iptv-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo "</ul>";
}
?>
