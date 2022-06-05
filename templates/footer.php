<?php
$webDirectory=$_SERVER["DOCUMENT_ROOT"];


# get the name of the script this script is included in
$scriptName = str_replace("/","_",explode(".",$_SERVER['SCRIPT_NAME'])[0]);
#$scriptName = dirname(__FILE__).$scriptName;

# add the page counter
if (file_exists($webDirectory."/views/".$scriptName."_views.cfg")){
	$tempViews = (int) file_get_contents($webDirectory."/views/".$scriptName."_views.cfg");
	file_put_contents(($webDirectory."/views/".$scriptName."_views.cfg"),$tempViews + 1);
}else{
	file_put_contents(($webDirectory."/views/".$scriptName."_views.cfg"),"1");
}
# display the page view
echo "<div class='viewCounterBox'>";
echo "<span class='viewCounterHeader'>";
echo "Views:";
echo "</span>";
echo "<span class='viewCounter'>";
echo file_get_contents($webDirectory."/views/".$scriptName."_views.cfg");
echo "</span>";
echo "</div>";

# figure out the header data template
$cacheFile=$webDirectory."/footerData.index";
# if file is older than 2 hours
if (file_exists($cacheFile)){
	if (time()-filemtime($cacheFile) > 90){
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
	$fileObj=fopen($cacheFile,'w') or die("Unable to write cache file!");
	$fileData = "";

	# build the header
	$fileData .= "<a class='' href='/index.php'>";
	$fileData .= "🏠";
	$fileData .= "<span class='footerText'>";
	$fileData .= " HOME";
	$fileData .= "</span>";
	$fileData .= "</a> ";

	$fileData .= "<a class='' href='/new/'>";
	$fileData .= "📜";
	$fileData .= "<span class='footerText'>";
	$fileData .= " NEW";
	$fileData .= "</span>";
	$fileData .= "</a> ";

	$fileData .= "<a class='' href='/random/'>";
	$fileData .= "🔀";
	$fileData .= "<span class='footerText'>";
	$fileData .= " RANDOM";
	$fileData .= "</span>";
	$fileData .= "</a> ";

	if (file_exists("$webDirectory/movies/")){
		if (file_exists("$webDirectory/totalMovies.index")){
			if ((file_get_contents("$webDirectory/totalMovies.index")) > 0){
				$fileData .= "<a class='' href='/movies'>";
				$fileData .= "🎥";
				$fileData .= "<span class='footerText'>";
				$fileData .= " MOVIES";
				$fileData .= "</span>";
				$fileData .= "</a> ";
			}
		}
	}
	if (file_exists("$webDirectory/shows/")){
		if (file_exists("$webDirectory/totalShows.index")){
			if ((file_get_contents("$webDirectory/totalShows.index")) > 0){
				$fileData .= "<a class='' href='/shows'>";
				$fileData .= "📺";
				$fileData .= "<span class='footerText'>";
				$fileData .= " SHOWS";
				$fileData .= "</span>";
				$fileData .= "</a> ";
			}
		}
	}
	if (file_exists("$webDirectory/music/")){
		if (file_exists("$webDirectory/totalAlbums.index")){
			if ((file_get_contents("$webDirectory/totalAlbums.index")) > 0){
				$fileData .= "<a class='' href='/music'>";
				$fileData .= "🎧";
				$fileData .= "<span class='footerText'>";
				$fileData .= " MUSIC";
				$fileData .= "</span>";
				$fileData .= "</a> ";
			}
		}
	}
	if (file_exists("$webDirectory/comics/")){
		if (file_exists("$webDirectory/totalComics.index")){
			if ((file_get_contents("$webDirectory/totalComics.index")) > 0){
				$fileData .= "<a class='' href='/comics'>";
				$fileData .= "📚";
				$fileData .= "<span class='footerText'>";
				$fileData .= " COMICS";
				$fileData .= "</span>";
				$fileData .= "</a> ";
			}
		}
	}
	if (file_exists("$webDirectory/totalChannels.index")){
		if ((file_get_contents("$webDirectory/totalChannels.index")) > 0){
			$fileData .= "<a class='' href='/live'>";
			$fileData .= "📡";
			$fileData .= "<span class='footerText'>";
			$fileData .= " LIVE";
			$fileData .= "</span>";
			$fileData .= "</a> ";
		}
	}
	fwrite($fileObj,"$fileData");
	// close the file
	fclose($fileObj);
	ignore_user_abort(false);
}
// read the file that is cached
echo file_get_contents($cacheFile);
// read the weather info for weather2web
if (file_exists("$webDirectory/totalWeatherStations.index")){
	if ((file_get_contents("$webDirectory/totalWeatherStations.index")) > 0){
		echo "<a class='' href='/weather/'>";
		echo "🌤️";
		echo "<span class='footerText'>";
		echo " WEATHER";
		echo "</span>";
		echo "</a> ";
	}
}
// draw the help button
echo "<a class='' href='/help.php'>";
echo "❔ ";
echo "<span class='footerText'>";
echo "HELP";
echo "</span>";
echo "</a> ";

echo "</div>";

echo "<div class='topButtonSpace'>";
echo "<hr>";
echo "</div>";
?>
