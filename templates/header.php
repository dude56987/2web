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
# if the user is logged in
if (isset($_SESSION["user"])){
	# upgrade any connections to https if they are http
	if(! $_SERVER["HTTPS"]){
		redirect("https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
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
	# show holiday icons
	if( date("m") == 3 ){
		$fileData .= formatText("🍀",3);
	}else if( date("m") == 6 ){
		$fileData .= formatText("<span class='rainbow'>🪲</span>",3);
	}else if( date("m") == 7 ){
		$fileData .= formatText("🇺🇸",3);
	}else if( date("m") == 10 ){
		$fileData .= formatText("🎃",3);
	}else if( date("m") == 11 ){
		$fileData .= formatText("🦃",3);
	}else if( date("m") == 12 ){
		$fileData .= formatText("🎄",3);
	}else{
		$fileData .= formatText("🏠",3);
	}
	$fileData .= formatText("<span class='headerText'>",3);
	$fileData .= formatText(ucfirst(gethostname()),4);
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
formatEcho("<a class='button' href='/new/'>",2);
formatEcho("📃",3);
formatEcho("<span class='headerText'>",3);
formatEcho("Playlists",4);
formatEcho("</span>",3);
formatEcho("</a>",2);
#
if (requireGroup("webPlayer",false)){
	if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
		formatEcho("<a class='button' href='/web-player.php'>",2);
		formatEcho("📥",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Web Player",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
// read the weather info for weather2web
if (requireGroup("weather2web",false)){
	formatEcho("<a class='button' href='/weather/'>",2);
	formatEcho("🌤️",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Weather",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}
if (detectEnabledStatus("nfo2web")){
	if (requireGroup("nfo2web",false)){
		formatEcho("<a class='button' href='/movies'>",2);
		formatEcho("🎥",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Movies",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("nfo2web")){
	if (requireGroup("nfo2web",false)){
		formatEcho("<a class='button' href='/shows'>",2);
		formatEcho("📺",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Shows",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("music2web")){
	if (requireGroup("music2web",false)){
		formatEcho("<a class='button' href='/music'>",2);
		formatEcho("🎧",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Music",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("comic2web")){
	if (requireGroup("comic2web",false)){
		formatEcho("<a class='button' href='/comics'>",2);
		formatEcho("📚",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Comics",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("iptv2web")){
	if (requireGroup("iptv2web",false)){
		formatEcho("<a class='button' href='/live'>",2);
		formatEcho("📡",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Live",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("wiki2web")){
	if (requireGroup("wiki2web",false)){
		formatEcho("<a class='button' href='/wiki/'>",2);
		formatEcho("⛵",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Wiki",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("ai2web")){
	if (requireGroup("ai2web",false)){
		formatEcho("<a class='button' href='/ai/'>",2);
		formatEcho("🧠",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("AI",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("git2web")){
	if (requireGroup("git2web",false)){
		formatEcho("<a class='button' href='/repos/'>",2);
		formatEcho("💾",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Repos",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("graph2web")){
	if (requireGroup("graph2web",false)){
		formatEcho("<a class='button' href='/graphs/'>",2);
		formatEcho("📊",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Graphs",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
if (detectEnabledStatus("portal2web")){
	if (requireGroup("portal2web",false)){
		formatEcho("<a class='button' href='/portal/'>",2);
		formatEcho("🚪",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Portal",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
#
if (detectEnabledStatus("php2web")){
	if (requireGroup("php2web",false)){
		formatEcho("<a class='button' href='/applications/'>",2);
		formatEcho("🖥️",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Applications",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
# client and remote
if (requireGroup("client",false)){
	if (yesNoCfgCheck("/etc/2web/client.cfg")){
		formatEcho("<a class='button' href='/client/'>",2);
		formatEcho("🛰️",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Client",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}
$drawRemote=false;
# draw the client remote control based on user permissions
if (yesNoCfgCheck("/etc/2web/client.cfg")){
	# check for permissions for using the remote to control the client page
	if (requireGroup("clientRemote",false)){
		$drawRemote=true;
	}
}
if (detectEnabledStatus("kodi2web")){
	# check if the http share link is enabled
	if (yesNoCfgCheck("/etc/2web/kodi/enableHttpShare.cfg")){
		if (yesNoCfgCheck("/etc/2web/kodi/enableHttpShareLink.cfg")){
			#
			formatEcho("<a class='button' href='/kodi/'>");
			formatEcho("🇰");
			formatEcho("<span class='headerText'>");
			formatEcho("Kodi");
			formatEcho("</span>");
			formatEcho("</a>");
		}
	}
	#
	if (count(scanDir("/etc/2web/kodi/players.d/")) > 2){
		if (requireGroup("kodi2web",false)){
			$drawRemote=true;
		}
	}
}
if ($drawRemote){
	formatEcho("<a class='button' href='/remote.php'>");
	formatEcho("🎛️");
	formatEcho("<span class='headerText'>");
	formatEcho("Remote");
	formatEcho("</span>");
	formatEcho("</a>");
}
# try to load a session in the current window if one does not exist
startSession();
# check the user has logged in successfully
if (isset($_SESSION["user"])){
	if (requireGroup("admin",false)){
		# admin settings
		formatEcho("<a class='button headerLoginButton' href='/settings/modules.php'>",2);
		formatEcho("🛠️",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho("Settings",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}

	# logout
	formatEcho("<a class='button headerLoginButton' href='/logout.php'>",2);
	formatEcho("🔒",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Logout",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}else if ($_SERVER['SERVER_PORT'] != 443){
	formatEcho("<a class='button headerLoginButton' href='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>",2);
	formatEcho("🔑",3);
	formatEcho("<span class='headerText'>",3);
	formatEcho("Encrypt",4);
	formatEcho("</span>",3);
	formatEcho("</a>",2);
}else{
	# hide login button on the login page
	if ($_SERVER["PHP_SELF"] != "/login.php"){
		formatEcho("<a class='button headerLoginButton' href='/login.php?redirect=https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>",2);
		formatEcho("🔓",3);
		formatEcho("<span class='headerText'>",3);
		formatEcho(" Login",4);
		formatEcho("</span>",3);
		formatEcho("</a>",2);
	}
}

formatEcho("<a class='button headerLoginButton' href='/help.php'>",2);
formatEcho("<span class='helpQuestionMark'>",3);
formatEcho("?",3);
formatEcho("</span>",3);
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

formatEcho("<div class='loginLogoutBox'>");
if (isset($_SESSION["user"])){
	if (requireGroup("admin",false)){
		formatEcho("<a class='button' href='/settings/'>");
		formatEcho("🛠️");
		formatEcho("<span class='headerText'>");
		formatEcho("Settings");
		formatEcho("</span>");
		formatEcho("</a>");
		formatEcho("<hr>");
	}
	formatEcho("<a class='button' href='/logout.php'>");
	formatEcho("🔒");
	formatEcho("<span class='headerText'>");
	formatEcho("Logout");
	formatEcho("</span>");
	formatEcho("</a>");
}else if ($_SERVER['SERVER_PORT'] != 443){
	formatEcho("<a class='button' href='https://".$_SERVER["HTTP_HOST"]."/'>");
	formatEcho("🔑");
	formatEcho("<span class='headerText'>");
	formatEcho("Encrypt");
	formatEcho("</span>");
	formatEcho("</a>");
}else{
	# hide login button on the login page
	if ($_SERVER["PHP_SELF"] != "/login.php"){
		formatEcho("<a class='button' href='/login.php?redirect=https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>");
		formatEcho("🔓");
		formatEcho("<span class='headerText'>\n");
		formatEcho("Login");
		formatEcho("</span>");
		formatEcho("</a>");
	}
}
echo "	<hr>";
// draw the help button
formatEcho("<a class='button' href='/help.php'>",2);
formatEcho("<span class='helpQuestionMark'>");
formatEcho("?");
formatEcho("</span>");
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
# do not leave a space between the search box and the button
	?><button id='searchButton' class='searchButton' type='submit'>🔎</button>
</form>
<?PHP
if (file_exists($_SERVER['DOCUMENT_ROOT']."/rebootAlert.cfg")){
	formatEcho("<div class='errorBanner'>\n");
	formatEcho("<h1>");
	formatEcho("<img class='localSpinner left' src='/spinner.gif'>\n");
	formatEcho("<img class='localSpinner right' src='/spinner.gif'>\n");
	formatEcho("Server Reboot Impending\n");
	formatEcho("</h1>");
	formatEcho("The server is preparing to reboot. Services may become momentarily unavailable.\n");
	formatEcho("</div>\n");
}
# release the lock on the session for this script to allow pages to load in parallel
session_write_close();
# send the header information before the rest of the page
flush();
ob_flush();
?>
