<?PHP
########################################################################
# 2web website header
# Copyright (C) 2023  Carl J Smith
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
?>
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

	# open the file for writing
	$fileObj=fopen($cacheFile,'w') or die("Unable to write cache file!");
	$fileData = "";

	# check for section indexes to determine if buttons need drawn
	$graphsFound=False;
	$moviesFound=False;
	$showsFound=False;
	$musicFound=False;
	$comicsFound=False;
	$channelsFound=False;

	if (file_exists("$webDirectory/graphs/")){
		$graphsFound=True;
	}
	if (file_exists("$webDirectory/movies/movies.index")){
		$moviesFound=True;
	}
	if (file_exists("$webDirectory/shows/shows.index")){
		$showsFound=True;
	}
	if (file_exists("$webDirectory/music/music.index")){
		$musicFound=True;
	}
	if (file_exists("$webDirectory/comics/comics.index")){
		$comicsFound=True;
	}
	if (file_exists("$webDirectory/totalChannels.index")){
		if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
			if (file_exists("$webDirectory/live/index.php")){
				$channelsFound=True;
			}
		}
	}

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

	if ($moviesFound || $musicFound || $comicsFound || $showsFound || $graphsFound){
		$fileData .= formatText("<a class='button' href='/new/'>",2);
		$fileData .= formatText("📃",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("PLAYLISTS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}

	if ($moviesFound){
		$fileData .= formatText("<a class='button' href='/movies'>",2);
		$fileData .= formatText("🎥",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("MOVIES",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if ($showsFound){
		$fileData .= formatText("<a class='button' href='/shows'>",2);
		$fileData .= formatText("📺",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("SHOWS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if ($musicFound){
		$fileData .= formatText("<a class='button' href='/music'>",2);
		$fileData .= formatText("🎧",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("MUSIC",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if ($comicsFound){
		$fileData .= formatText("<a class='button' href='/comics'>",2);
		$fileData .= formatText("📚",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("COMICS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}

	if ($channelsFound){
		$fileData .= formatText("<a class='button' href='/live'>",2);
		$fileData .= formatText("📡",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("LIVE",4);
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
	if ($graphsFound){
		$fileData .= formatText("<a class='button' href='/graphs/'>",2);
		$fileData .= formatText("📊",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("GRAPHS",4);
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
	formatEcho( "❓",3);
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
	formatEcho( "❓",3);
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
	echo "		<a class='button' href='/settings/'>";
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
	echo "<a class='button' href='/settings/'>";
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
	<button id='searchButton' class='searchButton' type='submit'>🔎</button>
</form>
