<?PHP
########################################################################
# 2web website footer
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
<hr>
<script>
	// setup the keys only if they have not already been set in the interface
	if  (typeof setupKeys !== "function"){
		// check for any listCard elements in the document
		var elements = document.getElementsByClassName("listCard");
		if (elements.length > 0){
			// Make the listCard elements scrollable
			// Add a listener to pass the key event into a function
			function setupKeys(){
				document.body.addEventListener('keydown', function(event){
					const key = event.key;
					switch (key){
						case 'ArrowLeft':
							// search though the document for elements with listCard class
							var elements = document.getElementsByClassName("listCard");
							// for each element scroll the box to the left
							for (var element of elements){
								element.scrollLeft -= 50;
							}
							break;
						case 'ArrowRight':
							// search though the document for elements with listCard class
							var elements = document.getElementsByClassName("listCard");
							for (var element of elements){
								// for each element scroll the box to the right
								element.scrollLeft += 50;
							};
							break;
					}
				});
			}
		}
		setupKeys();
	}
</script>
<?php

$webDirectory=$_SERVER["DOCUMENT_ROOT"];

# get the name of the script this script is included in
#$scriptName = str_replace("/","(#)",explode(".",$_SERVER['SCRIPT_NAME'])[0]);
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptName = $scriptName."?";
$totalKeys = count(array_keys($_GET));
$keyCounter = 0;
foreach(array_keys($_GET) as $keyName){
	$keyCounter +=1;
	$tempKeyValue = $_GET[$keyName];
	$tempKeyValue = str_replace("/","(#)",$tempKeyValue);

	# do not add the and to the last element in the array
	if ($keyCounter >= $totalKeys){
		$scriptName = $scriptName.$keyName."=".$tempKeyValue;
	}else{
		$scriptName = $scriptName.$keyName."=".$tempKeyValue."&";
	}
}
# write page views to sql database
ignore_user_abort(true);
# if the view count database does not exist create it
if (! file_exists($webDirectory."/views.db")){
	createViewsDatabase();
}
# load the views database
$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");
# set the timeout to 1 minute since most webbrowsers timeout loading before this
$databaseObj->busyTimeout(90000);
# load the views database
# - scriptName includes php get API request data
$databaseSearchQuery='select * from "view_count" where url = \''.$scriptName.'\';';
$result = $databaseObj->query($databaseSearchQuery);
# search views database for this pages view count
$data = $result->fetchArray();
# close the database to process the data
$databaseObj->close();
unset($databaseObj);
# if the current url url is in the database
if ( $data != false){
	# increment the view counter
	$updatedViewCount = $data["views"] + 1;
}else{
	$updatedViewCount = 1;
}
$dbUpdateQuery  = 'REPLACE INTO "view_count" (url, views) ';
$dbUpdateQuery .= "VALUES ('".$scriptName."', '".$updatedViewCount."') ";
#$dbUpdateQuery .= "ON DUPLICATE KEY UPDATE";
#$dbUpdateQuery .= "views = '".$updatedViewCount."'";
$dbUpdateQuery .= ";";
# load the views database
$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");
# set the timeout to 1 minute since most webbrowsers timeout loading before this
$databaseObj->busyTimeout(90000);
# update the database
$databaseObj->query($dbUpdateQuery);
# clear up memory of database file
$databaseObj->close();
unset($databaseObj);

# display the page view
echo "<div class='viewCounterBox'>";
echo "<a class='viewCounterHeader' href='/views/'>";
echo "👁️";
echo "</a>";
echo "<span class='viewCounter'>";
echo $updatedViewCount;
echo "</span>";
echo "<span class='executionTimeHeader'>";
echo "⏱️";
echo "</span>";
echo "<span class='executionTime'>";
echo round((microtime(True) - $startTime), 4);
echo "</span>";
echo "</div>";
echo "<hr>";
# figure out the header data template
$cacheFile=$webDirectory."/web_cache/footerData.index";
# if file is older than 1 hours
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
# start writing the footer
echo "<div id='footer' class=''>";
if ($writeFile){
	ignore_user_abort(true);
	# if any of the variables exist then the checks do not need to be re-run
	if ( ! (isset($graphsFound) || isset($moviesFound) || isset($showsFound) || isset($musicFound) || isset($comicsFound) || isset($channelsFound))){
		# check for section indexes to determine if buttons need drawn
		$moviesFound=False;
		$showsFound=False;
		$musicFound=False;
		$comicsFound=False;
		$channelsFound=False;
		$graphsFound=False;
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
		if (file_exists("$webDirectory/repos/repos.index")){
			$reposFound=True;
		}
		if (file_exists("$webDirectory/totalChannels.index")){
			if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
				if (file_exists("$webDirectory/live/index.php")){
					$channelsFound=True;
				}
			}
		}
		if (file_exists("$webDirectory/portal/")){
			$portalsFound=True;
		}
		if (file_exists("$webDirectory/ai/")){
			$aiFound=True;
		}
	}

	$fileObj=fopen($cacheFile,'w') or die("Unable to write cache file!");
	$fileData = "";

	# add the spacer to prevent links being under the back to top button
	$fileData .= "<div class='bottomButtonSpacer'></div>";

	# build the header
	$fileData .= "<a class='footerButton' href='/index.php'>";
	$fileData .= "🏠";
	$fileData .= "<span class='footerText'>";
	#$fileData .= " HOME";
	$fileData .= formatText(strtoupper(gethostname()),1);
	$fileData .= "</span>";
	$fileData .= "</a> ";

	if ($moviesFound || $musicFound || $comicsFound || $showsFound || $graphsFound){
		$fileData .= "<a class='footerButton' href='/new/'>";
		$fileData .= "📃";
		$fileData .= "<span class='footerText'>";
		$fileData .= " PLAYLISTS";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);

if (detectEnabledStatus("nfo2web")){
	if (requireGroup("nfo2web",false)){
		echo "<a class='footerButton' href='/movies'>";
		echo "🎥";
		echo "<span class='footerText'>";
		echo " MOVIES";
		echo "</span>";
		echo "</a> ";
	}
}
if (detectEnabledStatus("nfo2web")){
	if (requireGroup("nfo2web",false)){
		echo "<a class='footerButton' href='/shows'>";
		echo "📺";
		echo "<span class='footerText'>";
		echo " SHOWS";
		echo "</span>";
		echo "</a> ";
	}
}
if (detectEnabledStatus("music2web")){
	if (requireGroup("music2web",false)){
		echo "<a class='footerButton' href='/music'>";
		echo "🎧";
		echo "<span class='footerText'>";
		echo " MUSIC";
		echo "</span>";
		echo "</a> ";
	}
}
if (detectEnabledStatus("comic2web")){
	if (requireGroup("comic2web",false)){
		echo "<a class='footerButton' href='/comics'>";
		echo "📚";
		echo "<span class='footerText'>";
		echo " COMICS";
		echo "</span>";
		echo "</a> ";
	}
}
if (detectEnabledStatus("iptv2web")){
	if (requireGroup("iptv2web",false)){
		echo "<a class='footerButton' href='/live'>";
		echo "📡";
		echo "<span class='footerText'>";
		echo " LIVE";
		echo "</span>";
		echo "</a> ";
	}
}
if (detectEnabledStatus("wiki2web")){
	if (requireGroup("wiki2web",false)){
		echo "<a class='footerButton' href='/wiki/'>";
		echo "⛵";
		echo "<span class='footerText'>";
		echo " WIKI";
		echo "</span>";
		echo "</a> ";
	}
}
if (detectEnabledStatus("weahter2web")){
	if (requireGroup("weather2web",false)){
	// read the weather info for weather2web
		if (file_exists("$webDirectory/weather/index.php")){
			if (file_exists("$webDirectory/totalWeatherStations.index")){
				if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
					echo "<a class='footerButton' href='/weather/'>";
					echo "🌤️";
					echo "<span class='footerText'>";
					echo " WEATHER";
					echo "</span>";
					echo "</a> ";
				}
			}
		}
	}
}
if (detectEnabledStatus("git2web")){
	if (requireGroup("git2web",false)){
		echo formatText("<a class='footerButton' href='/repos/'>",2);
		echo formatText("💾",3);
		echo formatText("<span class='footerText'>",3);
		echo formatText("REPOS",4);
		echo formatText("</span>",3);
		echo formatText("</a> ",2);
	}
}
if (detectEnabledStatus("ai2web")){
	if (requireGroup("ai2web",false)){
		echo formatText("<a class='footerButton' href='/ai/'>",2);
		echo formatText("🧠",3);
		echo formatText("<span class='footerText'>",3);
		echo formatText("AI",4);
		echo formatText("</span>",3);
		echo formatText("</a> ",2);
	}
}
if (detectEnabledStatus("portal2web")){
	if (requireGroup("portal2web",false)){
		echo formatText("<a class='footerButton' href='/portal/'>",2);
		echo formatText("🚪",3);
		echo formatText("<span class='footerText'>",3);
		echo formatText("PORTAL",4);
		echo formatText("</span>",3);
		echo formatText("</a> ",2);
	}
}
if (detectEnabledStatus("graph2web")){
	if (requireGroup("graph2web",false)){
		echo "<a class='footerButton' href='/graphs/'>";
		echo "📊";
		echo "<span class='footerText'>";
		echo " GRAPHS";
		echo "</span>";
		echo "</a> ";
	}
}
// read the weather info for weather2web
if (file_exists("$webDirectory/weather/index.php")){
	if (file_exists("$webDirectory/totalWeatherStations.index")){
		if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
			if (requireGroup("weather2web",false)){
				echo formatText("<a class='footerButton' href='/weather/'>",2);
				echo formatText("🌤️",3);
				echo formatText("<span class='footerText'>",3);
				echo formatText("WEATHER",4);
				echo formatText("</span>",3);
				echo formatText("</a>",2);
			}
		}
	}
}
if (requireGroup("webPlayer",false)){
	if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
		echo formatText("<a class='footerButton' href='/web-player.php'>",2);
		echo formatText("📥",3);
		echo formatText("<span class='footerText'>",3);
		echo formatText("WEB PLAYER",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (requireGroup("client",false)){
	if (yesNoCfgCheck("/etc/2web/client.cfg")){
		echo formatText("<a class='footerButton' href='/client/'>",2);
		echo formatText("🛰️",3);
		echo formatText("<span class='footerText'>",3);
		echo formatText("CLIENT",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
# draw the client remote control based on user permissions
if (yesNoCfgCheck("/etc/2web/client.cfg")){
	# check for permissions for using the remote to control the client page
	if (requireGroup("clientRemote",false)){
		echo formatText("<a class='footerButton' href='/client/?remote'>",2);
		echo formatText("🎛️",3);
		echo formatText("<span class='footerText'>",3);
		echo formatText("CLIENT REMOTE",4);
		echo formatText("</span>",3);
		echo formatText("</a>",2);
	}
}
if (detectEnabledStatus("kodi2web")){
	if (count(scanDir("/etc/2web/kodi/players.d/")) > 2){
		if (requireGroup("kodi2web",false)){
			echo formatText("<a class='footerButton' href='/kodi-player.php'>",2);
			echo formatText("🇰",3);
			echo formatText("<span class='footerText'>",3);
			echo formatText("KODI REMOTE",4);
			echo formatText("</span>",3);
			echo formatText("</a>",2);
		}
	}
}
// draw the help button
echo "<a class='footerButton' href='/help.php'>";
echo "❔";
echo "<span class='footerText'>";
echo " HELP";
echo "</span>";
echo "</a> ";

echo "<a class='footerButton' href='/support.php'>";
echo "🫀";
echo "<span class='footerText'>";
echo " SUPPORT";
echo "</span>";
echo "</a> ";

echo "</div>";

echo "<div class='topButtonSpace'>";
echo "<hr>";
echo "</div>";

// remove the spinners after the footer is loaded
echo "<style>";
echo "	#spinner {";
echo "		visibility: hidden;";
echo "	}";
echo "	.globalPulse{";
echo "		visibility: hidden;";
echo "	}";
echo "</style>";
?>
