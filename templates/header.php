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
	$fileData .= "&#127968;";
	$fileData .= "<span class='headerText'>";
	$fileData .= "HOME";
	$fileData .= "</span>";
	$fileData .= "</a>";
	$fileData .= "<a class='button' href='/new/'>";
	$fileData .= "&#128220;";
	$fileData .= "<span class='headerText'>";
	$fileData .= "NEW";
	$fileData .= "</span>";
	$fileData .= "</a>";

	if (file_exists("$webDirectory/movies/")){
		if (file_exists("$webDirectory/totalMovies.index")){
			if ((file_get_contents("$webDirectory/totalMovies.index")) > 0){
				$fileData .= "<a class='button' href='/movies'>";
				#$fileData .= "&#127916;";
				$fileData .= "🎥";
				$fileData .= "<span class='headerText'>";
				$fileData .= "MOVIES";
				$fileData .= "</span>";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/shows/")){
		if (file_exists("$webDirectory/totalShows.index")){
			if ((file_get_contents("$webDirectory/totalShows.index")) > 0){
				$fileData .= "<a class='button' href='/shows'>";
				$fileData .= "📺";
				#$fileData .= "&#128250;";
				$fileData .= "<span class='headerText'>";
				$fileData .= "SHOWS";
				$fileData .= "</span>";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/music/")){
		if (file_exists("$webDirectory/totalAlbums.index")){
			if ((file_get_contents("$webDirectory/totalAlbums.index")) > 0){
				$fileData .= "<a class='button' href='/music'>";
				$fileData .= "🎧";
				$fileData .= "&#9834;";
				$fileData .= "<span class='headerText'>";
				$fileData .= "MUSIC";
				$fileData .= "</span>";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/comics/")){
		if (file_exists("$webDirectory/totalComics.index")){
			if ((file_get_contents("$webDirectory/totalComics.index")) > 0){
				$fileData .= "<a class='button' href='/comics'>";
				$fileData .= "📚";
				#$fileData .= "&#128218;";
				$fileData .= "<span class='headerText'>";
				$fileData .= "COMICS";
				$fileData .= "</span>";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/live/channels.m3u")){
		if (file_exists("$webDirectory/totalChannels.index")){
			if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
				$fileData .= "<a class='button' href='/live'>";
				$fileData .= "📡";
				#$fileData .= "&#128225;";
				$fileData .= "<span class='headerText'>";
				$fileData .= "LIVE";
				$fileData .= "</span>";
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
	echo "🛠️";
	echo "<span class='headerText'>";
	echo "SETTINGS";
	echo "</span>";
	echo "</a>";
	echo "<a class='button' href='/logout.php'>";
	echo "🔒";
	echo "<span class='headerText'>";
	echo "LOGOUT";
	echo "</span>";
	echo "</a>";
}else{
	echo "<a class='button' href='/system.php'>";
	echo "🔒";
	echo "<span class='headerText'>";
	echo "LOGIN";
	echo "</span>";
	echo "</a>";
}
// draw the help button
echo "<a class='button' href='/help.php'>";
echo "❔";
echo "Help";
echo "</a>";

echo "</div>";
?>
