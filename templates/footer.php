<?PHP
########################################################################
# 2web website footer
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
<hr>
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

	if ($moviesFound){
		$fileData .= "<a class='footerButton' href='/movies'>";
		$fileData .= "🎥";
		$fileData .= "<span class='footerText'>";
		$fileData .= " MOVIES";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	if ($showsFound){
		$fileData .= "<a class='footerButton' href='/shows'>";
		$fileData .= "📺";
		$fileData .= "<span class='footerText'>";
		$fileData .= " SHOWS";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	if ($musicFound){
		$fileData .= "<a class='footerButton' href='/music'>";
		$fileData .= "🎧";
		$fileData .= "<span class='footerText'>";
		$fileData .= " MUSIC";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	if ($comicsFound){
		$fileData .= "<a class='footerButton' href='/comics'>";
		$fileData .= "📚";
		$fileData .= "<span class='footerText'>";
		$fileData .= " COMICS";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	if ($channelsFound){
		$fileData .= "<a class='footerButton' href='/live'>";
		$fileData .= "📡";
		$fileData .= "<span class='footerText'>";
		$fileData .= " LIVE";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	if (file_exists("$webDirectory/wiki/")){
		$fileData .= "<a class='footerButton' href='/wiki/'>";
		$fileData .= "⛵";
		$fileData .= "<span class='footerText'>";
		$fileData .= " WIKI";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	// read the weather info for weather2web
	if (file_exists("$webDirectory/weather/index.php")){
		if (file_exists("$webDirectory/totalWeatherStations.index")){
			if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
				$fileData .= "<a class='footerButton' href='/weather/'>";
				$fileData .= "🌤️";
				$fileData .= "<span class='footerText'>";
				$fileData .= " WEATHER";
				$fileData .= "</span>";
				$fileData .= "</a> ";
			}
		}
	}
	if ($graphsFound){
		$fileData .= "<a class='footerButton' href='/graphs/'>";
		$fileData .= "📊";
		$fileData .= "<span class='footerText'>";
		$fileData .= " GRAPHS";
		$fileData .= "</span>";
		$fileData .= "</a> ";
	}
	if ($reposFound){
		$fileData .= formatText("<a class='footerButton' href='/repos/'>",2);
		$fileData .= formatText("💾",3);
		$fileData .= formatText("<span class='footerText'>",3);
		$fileData .= formatText("REPOS",4);
		$fileData .= formatText("</span>",3);
		$fileData .= formatText("</a>",2);
	}

	#$fileData .= "<a class='' href='/kodi/'>";
	#$fileData .= "🇰";
	#$fileData .= "<span class='footerText'>";
	#$fileData .= " KODI";
	#$fileData .= "</span>";
	#$fileData .= "</a> ";

	// draw the help button
	$fileData .= "<a class='footerButton' href='/help.php'>";
	$fileData .= "❔";
	$fileData .= "<span class='footerText'>";
	$fileData .= " HELP";
	$fileData .= "</span>";
	$fileData .= "</a> ";

	$fileData .= "<a class='footerButton' href='/support.php'>";
	$fileData .= "🫀";
	$fileData .= "<span class='footerText'>";
	$fileData .= " SUPPORT";
	$fileData .= "</span>";
	$fileData .= "</a> ";

	$fileData .= "</div>";

	$fileData .= "<div class='topButtonSpace'>";
	$fileData .= "<hr>";
	$fileData .= "</div>";

	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);
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
