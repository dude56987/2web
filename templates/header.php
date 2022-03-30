<?php
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
$cacheFile=$webDirectory."/headerData.index";
# if file is older than 2 hours
if (file_exists($cacheFile)){
	if (time()-filemtime($cacheFile) > 300){
		// update the cached file
		$writeFile=true;
	}else{
		// read from the already cached file
		$writeFile=false;
	}
}else{
	# write the file if it does not exist
	$writeFile=true;
}
if ($writeFile){
	ignore_user_abort(true);
	$fileObj=fopen($cacheFile,'w') or die("Unable to write cache file!");
	$fileData = "";

	# build the header
	$fileData .= "<div id='header' class='header'>";

	$fileData .= "<hr class='menuButton'/>";
	$fileData .= "<hr class='menuButton'/>";
	$fileData .= "<hr class='menuButton'/>";
	$fileData .= "<a class='button' href='/index.php'>";
	$fileData .= "&#127968;HOME";
	$fileData .= "</a>";
	$fileData .= "<a class='button' href='/new/'>";
	$fileData .= "&#128220;";
	$fileData .= "New";
	$fileData .= "</a>";
	$fileData .= "<a class='button' href='/link.php'>";
	$fileData .= "&#128279;LINK";
	$fileData .= "</a>";

	if (file_exists("$webDirectory/movies/")){
		if (file_exists("$webDirectory/totalMovies.index")){
			if ((file_get_contents("$webDirectory/totalMovies.index")) > 0){
				$fileData .= "<a class='button' href='/movies'>";
				$fileData .= "&#127916;MOVIES";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/shows/")){
		if (file_exists("$webDirectory/totalShows.index")){
			if ((file_get_contents("$webDirectory/totalShows.index")) > 0){
				$fileData .= "<a class='button' href='/shows'>";
				$fileData .= "&#128250;SHOWS";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/music/")){
		if (file_exists("$webDirectory/totalAlbums.index")){
			if ((file_get_contents("$webDirectory/totalAlbums.index")) > 0){
				$fileData .= "<a class='button' href='/music'>";
				$fileData .= "&#9834;MUSIC";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/comics/")){
		if (file_exists("$webDirectory/totalComics.index")){
			if ((file_get_contents("$webDirectory/totalComics.index")) > 0){
				$fileData .= "<a class='button' href='/comics'>";
				$fileData .= "&#128214;COMICS";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/live/channels.m3u")){
		if (file_exists("$webDirectory/totalChannels.index")){
			if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
				$fileData .= "<a class='button' href='/live'>";
				$fileData .= "&#128225;LIVE";
				$fileData .= "</a>";
			}
		}
	}
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);
if (isset($_SERVER['HTTPS'])){
	echo "<a class='button' href='/system.php'>";
	echo "&#128421;SETTINGS";
	echo "</a>";
	echo "<a class='button' href='/logout.php'>";
	echo "&#128274;LOGOUT";
	echo "</a>";
}else{
	echo "<a class='button' href='/system.php'>";
	echo "&#128274;LOGIN";
	echo "</a>";
}
echo "</div>";

?>
