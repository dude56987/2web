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
include("/usr/share/2web/2webLib.php");
# the 2web group to lock login to the website completely
if(! requireGroup("2web", false)){
	# check for access to the 2web group
	if (! ($_SERVER["PHP_SELF"] == "/login.php")){
		# redirect to the login if this page is not the login page
		redirect("/login.php");
	}
}
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


	# write all data in buffer
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);

#
echo formatText("<a class='button' href='/new/'>",2);
echo formatText("📃",3);
echo formatText("<span class='headerText'>",3);
echo formatText("PLAYLISTS",4);
echo formatText("</span>",3);
echo formatText("</a>",2);
#
if (detectEnabledStatus("nfo2web")){
	if (requireGroup("nfo2web",false)){
		echo formatText("<a class='button' href='/movies'>",2);
		echo formatText("🎥",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("MOVIES",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("nfo2web")){
	if (requireGroup("nfo2web",false)){
		echo formatText("<a class='button' href='/shows'>",2);
		echo formatText("📺",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("SHOWS",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("music2web")){
	if (requireGroup("music2web",false)){
		echo formatText("<a class='button' href='/music'>",2);
		echo formatText("🎧",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("MUSIC",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("comic2web")){
	if (requireGroup("comic2web",false)){
		echo formatText("<a class='button' href='/comics'>",2);
		echo formatText("📚",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("COMICS",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("iptv2web")){
	if (requireGroup("iptv2web",false)){
		echo formatText("<a class='button' href='/live'>",2);
		echo formatText("📡",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("LIVE",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("wiki2web")){
	if (requireGroup("wiki2web",false)){
		echo formatText("<a class='button' href='/wiki/'>",2);
		echo formatText("⛵",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("WIKI",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("git2web")){
	if (requireGroup("git2web",false)){
		echo formatText("<a class='button' href='/repos/'>",2);
		echo formatText("💾",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("REPOS",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("ai2web")){
	if (requireGroup("ai2web",false)){
		echo formatText("<a class='button' href='/ai/'>",2);
		echo formatText("🧠",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("AI",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("portal2web")){
	if (requireGroup("portal2web",false)){
		echo formatText("<a class='button' href='/portal/'>",2);
		echo formatText("🚪",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("PORTAL",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("graph2web")){
	if (requireGroup("graph2web",false)){
		formatText("<a class='button' href='/graphs/'>",2);
		formatText("📊",3);
		formatText("<span class='headerText'>",3);
		formatText("GRAPHS",4);
		formatText("</span>",3);
		formatText("</a>",2);
	}
}
// read the weather info for weather2web
if (file_exists("$webDirectory/weather/index.php")){
	if (file_exists("$webDirectory/totalWeatherStations.index")){
		if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
			if (requireGroup("weather2web",false)){
				echo formatText("<a class='button' href='/weather/'>",2);
				echo formatText("🌤️",3);
				echo formatText("<span class='headerText'>",3);
				echo formatText("WEATHER",4);
				echo formatText("</span>",3);
				echo formatText("</a>",2);
			}
		}
	}
}

// check for the kodi remote
if (detectEnabledStatus("kodi2web")){
	if (count(scanDir("/etc/2web/kodi/players.d/")) > 2){
		if (requireGroup("kodi2web",false)){
			echo formatText("<a class='button' href='/kodi-player.php'>",2);
			echo formatText("🇰",3);
			echo formatText("<span class='headerText'>",3);
			echo formatText("KODI REMOTE",4);
			echo formatText("</span>",3);
			echo formatText("</a>",2);
		}
	}
}
if (requireGroup("webPlayer",false)){
	if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
		echo formatText("<a class='button' href='/web-player.php'>",2);
		echo formatText("📥",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("WEB PLAYER",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (requireGroup("client",false)){
	if (yesNoCfgCheck("/etc/2web/client.cfg")){
		echo formatText("<a class='button' href='/client/'>",2);
		echo formatText("🛰️",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("CLIENT",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}

# draw the client remote control based on user permissions
if (yesNoCfgCheck("/etc/2web/client.cfg")){
	# check for permissions for using the remote to control the client page
	if (requireGroup("clientRemote",false)){
		echo formatText("<a class='button' href='/client/?remote'>",2);
		echo formatText("🎛️",3);
		echo formatText("<span class='headerText'>",3);
		echo formatText("CLIENT REMOTE",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}

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
<?PHP
# send the header information before the rest of the page
flush();
ob_flush();
?>
