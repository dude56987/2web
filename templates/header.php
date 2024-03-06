<?PHP
########################################################################
# 2web website header
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
?>
<!-- create top jump button -->
<img class='globalPulse' src='/pulse.gif'>
<a href='#' id='topButton' class='button'>&uarr;<div><div id='scrollProgress'></div></div></a>

<script>
	window.onscroll = function(){scrollCheck()};
	function scrollCheck(){
		var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
		var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
		var scrolled = (winScroll / height) * 100;
		document.getElementById("scrollProgress").style.width = (scrolled + "%");
	}
	scrollCheck();
</script>

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
	$reposFound=False;
	$portalsFound=False;
	$aiFound=False;

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
	if (file_exists("$webDirectory/live/index.php")){
		$channelsFound=True;
	}
	if (file_exists("$webDirectory/repos/repos.index")){
		$reposFound=True;
	}
	if (file_exists("$webDirectory/portal/")){
		$portalsFound=True;
	}
	if (file_exists("$webDirectory/ai/")){
		$aiFound=True;
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
	$fileData .= formatText(strtoupper(gethostname()),4);
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
	if ($reposFound){
		$fileData .= formatText("<a class='button' href='/repos/'>",2);
		$fileData .= formatText("💾",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("REPOS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if (file_exists("$webDirectory/ai/")){
		$fileData .= formatText("<a class='button' href='/ai/'>",2);
		$fileData .= formatText("🧠",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("AI",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if (file_exists("$webDirectory/portal/")){
		$fileData .= formatText("<a class='button' href='/portal/'>",2);
		$fileData .= formatText("🚪",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("PORTAL",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}
	if ($graphsFound){
		$fileData .= formatText("<a class='button' href='/graphs/'>",2);
		$fileData .= formatText("📊",3);
		$fileData .= formatText("<span class='headerText'>",3);
		$fileData .= formatText("GRAPHS",4);
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
	// check for the kodi remote
	if (detectEnabledStatus("kodi2web")){
		if (count(scanDir("/etc/2web/kodi/players.d/")) > 2){
			$fileData .= formatText("<a class='button' href='/kodi-player.php'>",2);
			$fileData .= formatText("🇰",3);
			$fileData .= formatText("<span class='headerText'>",3);
			$fileData .= formatText("KODI REMOTE",4);
			$fileData .= formatText("</span>",3);
			$fileData .= formatText("</a>",2);
		}
	}

	# write all data in buffer
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);

# try to load a session in the current window if one does not exist
if (session_status() != 2){
	session_start();
}
# check the user has logged in successfully
if (array_key_exists("user",$_SESSION)){
	if (array_key_exists("admin",$_SESSION)){
		if ($_SESSION["admin"]){
			# admin settings
			formatEcho("<a class='button headerLoginButton' href='/settings/modules.php'>",2);
			formatEcho("🛠️",3);
			formatEcho("<span class='headerText'>",3);
			formatEcho("SETTINGS",4);
			formatEcho("</span>",3);
			formatEcho("</a>",2);
		}
	}
	# logout
	formatEcho("<a class='button headerLoginButton' href='/logout.php'>",2);
	formatEcho("🔒",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("LOGOUT",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}else if ($_SERVER['SERVER_PORT'] != 443){
	formatEcho("<a class='button headerLoginButton' href='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>",2);
	formatEcho("🔑",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("ENCRYPT",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}else{
	# hide login button on the login page
	if ($_SERVER["PHP_SELF"] != "/login.php"){
		echo "<a class='button headerLoginButton' href='/login.php?redirect=https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>";
		echo "🔓";
		echo "<span class='headerText'>";
		echo " LOGIN";
		echo "</span>";
		echo "</a>";
	}
}

formatEcho("<a class='button headerLoginButton' href='/help.php'>",2);
formatEcho( "❓",3);
formatEcho("<span class='headerText'>",3);
formatEcho("Help",4);
formatEcho("</span>",3);
formatEcho("</a>",2);

# close the header buttons class
#formatEcho("</div>",2);
# close the
formatEcho("</div>",1);
# close the header bracket
formatEcho("</div>",0);

formatEcho('<script>',1);
formatEcho('setHeaderStartState();',2);
formatEcho('</script>',1);

# if the path is in the settings draw the logout button

echo "<div class='loginLogoutBox'>";
if (array_key_exists("user",$_SESSION)){
	if (array_key_exists("admin",$_SESSION)){
		if ($_SESSION["admin"]){
			echo "		<a class='button' href='/settings/'>";
			echo "			🛠️";
			echo "			<span class='headerText'>";
			echo "				SETTINGS";
			echo "			</span>";
			echo "		</a>";
			echo "	<hr>";
		}
	}
	echo "		<a class='button' href='/logout.php'>";
	echo "			🔒";
	echo "			<span class='headerText'>";
	echo "				LOGOUT";
	echo "			</span>";
	echo "		</a>";
	// draw the help button
}else if ($_SERVER['SERVER_PORT'] != 443){
	echo "<a class='button' href='https://".$_SERVER["HTTP_HOST"]."/'>";
	echo "🔑";
	echo "<span class='headerText'>";
	echo " ENCRYPT";
	echo "</span>";
	echo "</a>";
}else{
	# hide login button on the login page
	if ($_SERVER["PHP_SELF"] != "/login.php"){
		echo "<a class='button' href='/login.php?redirect=https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>";
		echo "🔓";
		echo "<span class='headerText'>";
		echo " LOGIN";
		echo "</span>";
		echo "</a>";
	}
}
echo "	<hr>";
// draw the help button
formatEcho("<a class='button' href='/help.php'>",2);
formatEcho( "❓",3);
formatEcho("<span class='headerText'>",3);
formatEcho("Help",4);
formatEcho("</span>",3);
formatEcho("</a>",2);
echo "</div>";

?>
<form class='searchBoxForm' action='/search.php' method='get'>
	<?PHP
if (array_key_exists("q",$_GET)){
		# place query into the search bar to allow editing of the query and resubmission
		echo "<input id='searchBox' class='searchBox' type='text' name='q' placeholder='2web Search...' value='".$_GET["q"]."' >";
	}else{
		echo "<input id='searchBox' class='searchBox' type='text' name='q' placeholder='2web Search...' >";
	}
	?>
	<button id='searchButton' class='searchButton' type='submit'>🔎</button>
</form>
