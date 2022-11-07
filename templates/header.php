<!-- create top jump button -->
<a href='#' id='topButton' class='button'>&uarr;</a>
<?php
$startTime=microtime(True);
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
$cacheFile=$webDirectory."/web_cache/headerData.index";

include("/usr/share/2web/2webLib.php");

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
	$fileData .= formatText("<div id='header' class='header'>");

	$fileData .= formatText("<div class='menuButtonBox' onclick='toggleVisibleClass(\"headerButtons\")'>", 1);
	$fileData .= formatText("<hr class='menuButton'/>",2);
	$fileData .= formatText("<hr class='menuButton'/>",2);
	$fileData .= formatText("<hr class='menuButton'/>",2);
	$fileData .= formatText("</div>",1);

	//$fileData .= formatText('<div class="headerButtons" onload="hideVisibleClass(\'headerButtons\')">',1);


	$fileData .= formatText('<div class="headerButtons">',1);

	$fileData .= formatText("<a class='button' href='/'>",2);
	$fileData .= formatText("&#127968;",3);
	$fileData .= formatText("<span class='headerText'>",3);
	$fileData .= formatText("HOME",4);
	$fileData .= formatText("</span>",3);
	$fileData .= formatText("</a>",2);

	$fileData .= formatText("<a class='button' href='/new/'>",2);
	$fileData .= formatText("📃",3);
	$fileData .= formatText("<span class='headerText'>",3);
	$fileData .= formatText("PLAYLISTS",4);
	$fileData .= formatText("</span>",3);
	$fileData .= formatText("</a>",2);

	if (file_exists("$webDirectory/movies/movies.index")){
		$fileData .= formatText("<a class='button' href='/movies'>",2);
		$fileData .= formatText("🎥",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("MOVIES",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if (file_exists("$webDirectory/shows/shows.index")){
		$fileData .= formatText("<a class='button' href='/shows'>",2);
		$fileData .= formatText("📺",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("SHOWS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if (file_exists("$webDirectory/music/music.index")){
		$fileData .= formatText("<a class='button' href='/music'>",2);
		$fileData .= formatText("🎧",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("MUSIC",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if (file_exists("$webDirectory/comics/comics.index")){
		$fileData .= formatText("<a class='button' href='/comics'>",2);
		$fileData .= formatText("📚",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("COMICS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}

	if (file_exists("$webDirectory/totalChannels.index")){
		if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
			if (file_exists("$webDirectory/live/index.php")){
				$fileData .= formatText("<a class='button' href='/live'>",2);
				$fileData .= formatText("📡",3);
				$fileData .= formatText("<span class='headerText'>",3);
				$fileData .= formatText("LIVE",4);
				$fileData .= formatText("</span>",3);
				$fileData .= formatText("</a>",2);
			}
		}
	}
	// read the weather info for weather2web
	//
	if (file_exists("$webDirectory/weather/index.php")){
		if (file_exists("$webDirectory/totalWeatherStations.index")){
			if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
				$fileData .= formatText("<a class='button' href='/weather/'>",2);
				$fileData .= formatText("🌤️",3);
				$fileData .= formatText("<span class='headerText'>",3);
				$fileData .= formatText("WEATHER",4);
				$fileData .= formatText("</span>",3);
				$fileData .= formatText("</a>",2);
			}
		}
	}
	if (file_exists("$webDirectory/graphs/")){
		$fileData .= formatText("<a class='button' href='/graphs/'>",2);
		$fileData .= formatText("📊",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("GRAPHS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if (file_exists("$webDirectory/wiki/")){
		$fileData .= formatText("<a class='button' href='/wiki/'>",2);
		$fileData .= formatText("⛵",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("WIKI",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	# close the listcard block
	#echo "</div>";
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);
if (strpos($_SERVER['REQUEST_URI'], "settings/") || strpos($_SERVER['REQUEST_URI'], "log/") || strpos($_SERVER['REQUEST_URI'], "backup/")){
	formatEcho("<a class='button headerLoginButton' href='/settings/modules.php'>",2);
	formatEcho("🛠️",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("SETTINGS",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);

	formatEcho("<a class='button headerLoginButton' href='/logout.php'>",2);
	formatEcho("🔒",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("LOGOUT",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);

	formatEcho("<a class='button headerLoginButton' href='/help.php'>",2);
	formatEcho( "❔",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Help",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}else{
	formatEcho("<a class='button headerLoginButton' href='/settings/modules.php'>",2);
	formatEcho("🔒",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("LOGIN",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);

	formatEcho("<a class='button headerLoginButton' href='/help.php'>",2);
	formatEcho( "❔",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Help",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}
// draw the help button
//formatEcho("<a class='button' href='/help.php'>",2);
//formatEcho( "❔",3);
//formatEcho("<span class='headerText'>",3);
//formatEcho("Help",4);
//formatEcho("</span>",3);
//formatEcho("</a>",2);

//echo "<div class='loginLogoutBoxSpacer'>";

# close the header buttons class
#formatEcho("</div>",2);
# close the
formatEcho("</div>",1);
# close the header bracket
formatEcho("</div>",0);

formatEcho('<script>',1);
//$fileData .= formatText('hideVisibleClass(\'headerButtons\');',2);
formatEcho('setHeaderStartState();',2);
formatEcho('</script>',1);

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
	// draw the help button
	echo "	<hr>";
	formatEcho("<a class='button' href='/help.php'>",2);
	formatEcho( "❔",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Help",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
	echo "</div>";
}else{
	echo "<div class='loginLogoutBox'>";
	echo "<a class='button' href='/settings/modules.php'>";
	echo "🔓";
	echo "<span class='headerText'>";
	echo "LOGIN";
	echo "</span>";
	echo "</a>";
	echo "	<hr>";
	// draw the help button
	formatEcho("<a class='button' href='/help.php'>",2);
	formatEcho( "❔",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Help",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
	echo "</div>";
}
?>
<form class='searchBoxForm' action='/search.php' method='get'>
	<input id='searchBox' class='searchBox' type='text' name='q' placeholder='2web Search...' >
</form>
