<!-- create top jump button -->
<a href='#' id='topButton' class='button'>&uarr;</a>
<?php
$startTime=microtime(True);
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
$cacheFile=$webDirectory."/headerData.index";
# if file is older than 2 hours
if (file_exists($cacheFile)){
	if (time()-filemtime($cacheFile) > 60){
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
	$fileData .= "<hr class='menuButton'/>";

	$fileData .= "<a class='button' href='/'>";
	$fileData .= "&#127968;";
	$fileData .= "<span class='headerText'>";
	$fileData .= "HOME";
	$fileData .= "</span>";
	$fileData .= "</a>";

	$fileData .= "<a class='button' href='/new/'>";
	$fileData .= "📜";
	$fileData .= "<span class='headerText'>";
	$fileData .= "NEW";
	$fileData .= "</span>";
	$fileData .= "</a>";

	$fileData .= "<a class='button' href='/random/'>";
	$fileData .= "🔀";
	$fileData .= "<span class='headerText'>";
	$fileData .= "RANDOM";
	$fileData .= "</span>";
	$fileData .= "</a>";

	if (file_exists("$webDirectory/movies/movies.index")){
		$fileData .= "<a class='button' href='/movies'>";
		$fileData .= "🎥";
		$fileData .= "<span class='headerText'>";
		$fileData .= "MOVIES";
		$fileData .= "</span>";
		$fileData .= "</a>";
	}
	if (file_exists("$webDirectory/shows/shows.index")){
		$fileData .= "<a class='button' href='/shows'>";
		$fileData .= "📺";
		$fileData .= "<span class='headerText'>";
		$fileData .= "SHOWS";
		$fileData .= "</span>";
		$fileData .= "</a>";
	}
	if (file_exists("$webDirectory/music/music.index")){
		$fileData .= "<a class='button' href='/music'>";
		$fileData .= "🎧";
		$fileData .= "<span class='headerText'>";
		$fileData .= "MUSIC";
		$fileData .= "</span>";
		$fileData .= "</a>";
	}
	if (file_exists("$webDirectory/comics/comics.index")){
		$fileData .= "<a class='button' href='/comics'>";
		$fileData .= "📚";
		$fileData .= "<span class='headerText'>";
		$fileData .= "COMICS";
		$fileData .= "</span>";
		$fileData .= "</a>";
	}

	if (file_exists("$webDirectory/totalChannels.index")){
		if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
			if (file_exists("$webDirectory/live/index.php")){
				$fileData .= "<a class='button' href='/live'>";
				$fileData .= "📡";
				$fileData .= "<span class='headerText'>";
				$fileData .= "LIVE";
				$fileData .= "</span>";
				$fileData .= "</a>";
			}
		}
	}
	// read the weather info for weather2web
	//
	if (file_exists("$webDirectory/weather/index.php")){
		if (file_exists("$webDirectory/totalWeatherStations.index")){
			if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
				$fileData .= "<a class='button' href='/weather/'>";
				$fileData .= "🌤️";
				$fileData .= "<span class='headerText'>";
				$fileData .= "WEATHER";
				$fileData .= "</span>";
				$fileData .= "</a>";
			}
		}
	}
	if (file_exists("$webDirectory/graphs/")){
		$fileData .= "<a class='button' href='/graphs/'>";
		$fileData .= "📊";
		$fileData .= "<span class='headerText'>";
		$fileData .= "GRAPHS";
		$fileData .= "</span>";
		$fileData .= "</a>";
	}
	# close the listcard block
	echo "</div>";
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);
if (strpos($_SERVER['REQUEST_URI'], "settings/")){
	echo "<a class='button headerLoginButton' href='/settings/modules.php'>";
	echo "🛠️";
	echo "<span class='headerText'>";
	echo "SETTINGS";
	echo "</span>";
	echo "</a>";
	echo "<a class='button headerLoginButton' href='/logout.php'>";
	echo "🔒";
	echo "<span class='headerText'>";
	echo "LOGOUT";
	echo "</span>";
	echo "</a>";
}else{
	echo "<a class='button headerLoginButton' href='/settings/modules.php'>";
	echo "🔒";
	echo "<span class='headerText'>";
	echo "LOGIN";
	echo "</span>";
	echo "</a>";
}
// draw the help button
echo "<a class='button' href='/help.php'>";
echo "❔";
echo "<span class='headerText'>";
echo "Help";
echo "</span>";
echo "</a>";

//echo "<div class='loginLogoutBoxSpacer'>";
echo "</div>";
# close the header bracket
echo "</div>";

# if the path is in the settings draw the logout button
if (strpos($_SERVER['REQUEST_URI'], "settings/")){
	echo "<div class='loginLogoutBox'>";
	echo "		<a class='button' href='/settings/modules.php'>";
	echo "			🛠️";
	echo "			<span class='headerText'>";
	echo "				SETTINGS";
	echo "			</span>";
	echo "		</a>";
	echo "	<hr>";
	echo "		<a class='button' href='/logout.php'>";
	echo "			🔒";
	echo "			<span class='headerText'>";
	echo "				LOGOUT";
	echo "			</span>";
	echo "		</a>";
	echo "</div>";
}else{
	echo "<div class='loginLogoutBox'>";
	echo "<a class='button' href='/settings/modules.php'>";
	echo "🔓";
	echo "<span class='headerText'>";
	echo "LOGIN";
	echo "</span>";
	echo "</a>";
	echo "</div>";
}
?>

