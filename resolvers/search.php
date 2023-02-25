<!--
########################################################################
# 2web search interface
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
-->
<?PHP
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with yt-dlp
################################################################################
# force debugging
#$_GET['debug']='true';
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
################################################################################
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
################################################################################
function debug($message){
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: ".$message."<br>";
		ob_flush();
		flush();
		return true;
	}else{
		return false;
	}
}
################################################################################
function runShellCommand($command){
	if (array_key_exists("debug",$_GET)){
		//echo 'Running command %echo "'.$command.'" | at now<br>';
		echo 'Running command %'.$command.'<br>';
	}
	################################################################################
	//exec($command);
	//$output=shell_exec('echo "'.$command.'" | at now >> RESOLVER-CACHE/resolver.log');
	$output=shell_exec($command);
	debug("OUTPUT=".$output."<br>");
}
################################################################################
function searchIndex($indexPath,$searchQuery,$cacheFilePath){
	$foundData=false;
	$tempData="";
	# if the search index exists
	if ( file_exists( $indexPath ) ){
		$fileHandle = fopen( $indexPath , "r" );
		while( ! feof( $fileHandle ) ){
			# read a line of the file
			$fileData = fgets( $fileHandle );
			#echo "The file path is '$fileData'<br>\n";
			#remove newlines from extracted file paths in index
			$fileData = str_replace( "\n" , "" , $fileData);
			if ( stripos( $fileData , $searchQuery ) ){
				if ( file_exists( $fileData ) ){
					# read the file in peices
					$linkTextHandle = fopen( $fileData , "r" );
					while( ! feof( $linkTextHandle ) ){
						$tempFileData = fgets( $linkTextHandle);
						# read each line of the file
						$tempData .= $tempFileData;
						echo $tempFileData;
						ob_flush();
						flush();
					}
					$foundData = true;
				}
			}
		}
	}
	if ($foundData){
		appendFile($cacheFilePath,$tempData);
		return array(true,$tempData);
	}else{
		return array(false,$tempData);
	}
}
################################################################################
function scan_dir($directory){
	if (is_dir($directory)){
		$tempData = scandir($directory);
		$tempData = array_diff($tempData,Array(".","..","index.php","randomFanart.php"));
		return $tempData;
	}else{
		return false;
	}
}
################################################################################
function searchWeather($cacheFilePath){
	$weatherData=scanDir("/var/cache/2web/web/weather/data/");
	$foundData=False;
	$output="";
	# search weather data and forecasts
	foreach($weatherData as $weatherPath){
		$fullWeatherPath="/var/cache/2web/web/weather/data/".$weatherPath;
		#echo "$fullWeatherPath<br>";
		# read the weather data from the file
		if (stripos($fullWeatherPath,".index")){
			if (file_exists($fullWeatherPath)){
				if (is_file($fullWeatherPath)){
					#echo "File is file...<br>";
					$weatherFileData=file_get_contents($fullWeatherPath);
					#echo $weatherFileData."<br>";
					if (stripos($weatherFileData,$_GET['q'])){
						if (stripos($fullWeatherPath,"current_")){
							# check current weather conditions
							$tempOutput="<div class='titleCard'>";
							$tempOutput.=$weatherFileData;
							$tempOutput.="</div>"."\n";
							$output.=$tempOutput;
							echo $tempOutput;
							flush();
							ob_flush();
							$foundData=True;
							#appendFile($cacheFilePath,$tempOutput);
						}else if(stripos($fullWeatherPath,"forcast_")){
							# check forcast
							$tempOutput=$weatherFileData;
							$output.=$tempOutput."\n";
							echo $tempOutput;
							flush();
							ob_flush();
							$foundData=True;
							#appendFile($cacheFilePath,$tempOutput);
						}
					}
				}
			}
		}
	}
	if ($foundData){
		return array(true,$output);
	}else{
		return array(false,$output);
	}
}
################################################################################
function searchAllWiki($wikiPath,$cacheFilePath){
	# search all wiki content
	$output = "";
	$foundData = false;
	$wikiPaths = scan_dir($_SERVER['DOCUMENT_ROOT']."/wiki/");
	if ($wikiPaths){
		foreach($wikiPaths as $wikiPath){
			# read each wiki and search for content
			$wikiSearchResults = searchWiki($_SERVER['DOCUMENT_ROOT']."/wiki/".$wikiPath,$cacheFilePath);
			if ($wikiSearchResults[0]){
				# if the wiki found search results add them to the output
				$output .= $wikiSearchResults[1];
				$foundData = true;
			}
		}
	}
	if ($foundData){
		return array(true,$output);
	}else{
		return array(false,$output);
	}
}
################################################################################
function searchChannels($cacheFilePath){
	$output = "";
	$foundData = false;
	# search though the article files for the search term
	$foundFiles = scan_dir($_SERVER['DOCUMENT_ROOT']."/live/index/");
	#
	if ($foundFiles){
		foreach($foundFiles as $foundFile){
			# open the .index file and search inside it
			$serverPath=$_SERVER['DOCUMENT_ROOT']."/live/index/";
			#
			$foundFileData=file_get_contents($serverPath.$foundFile);
			#
			if (stripos($foundFileData,$_GET['q'])){
				$foundData = true;
				# check each filename for the search term
				$tempOutput = "";
				$tempOutput .= $foundFileData;

				$output .= $tempOutput;

				appendFile($cacheFilePath,$tempOutput);

				echo $tempOutput;
				flush();
				ob_flush();
			}
		}
	}
	if ($foundData){
		return array(true,$output);
	}else{
		return array(false,$output);
	}
}
################################################################################
function searchWiki($wikiPath,$cacheFilePath){
	$output = "";
	$foundData = false;
	# search though the article files for the search term
	$foundFiles = scan_dir($wikiPath."/A/");
	#
	$wikiName=explode("/",$wikiPath);
	$wikiName=array_pop($wikiName);
	if ($foundFiles){
		foreach($foundFiles as $foundFile){
			if (stripos($foundFile,$_GET['q'])){
				# check each filename for the search term
				$tempOutput = "";

				$tempOutput .= "<div class='inputCard button'>";
				$tempOutput .= "<h2>";
				$tempOutput .= "<a href='/wiki/$wikiName/?article=".$foundFile."'>".$foundFile."</a>";
				$tempOutput .= "</h2>";
				$tempOutput .= "</div>\n";

				$output .= $tempOutput;

				appendFile($cacheFilePath,$tempOutput);

				echo $tempOutput;
				flush();
				ob_flush();
			}else if(is_file($wikiPath."/A/".$foundFile)){
				# read each file and search line by line
				$articleHandle = fopen($wikiPath."/A/".$foundFile,'r');
				while(! feof($articleHandle)){
					$lineData = fgets($articleHandle,128);
					#$lineData = file_get_contents($wikiPath."/A/".$foundFile);
					# remove html tags
					$lineData = strip_tags($lineData);
					# make string all lowercase
					$lineData = strtolower($lineData);
					# highlight found search terms
					$lineData = str_replace($_GET['q'],("<span class='highlightText'>".$_GET['q']."</span>"),$lineData);
					#$lineData = str_replace(strtoupper($_GET['q']),("<span class='highlightText'>".strtoupper($_GET['q'])."</span>"),$lineData);
					if(stripos($lineData,$_GET['q'])){

						$foundPosition=stripos($lineData,$_GET['q']);

						$startCut=$foundPosition - 10;
						if ($startCut < 0){
							$startCut=0;
						}

						$endCut = $foundPosition + 10;
						if ($endCut > strlen($lineData)){
							$endCut=strlen($lineData);
						}

						$foundStringPreview = substr($lineData,$startCut,$endCut);

						$foundData = true;
						$tempOutput = "";
						# check each files contents for the search term
						#$tempOutput .= "<div class='titleCard button'>";
						$tempOutput .= "<div class='inputCard button'>";
						$tempOutput .= "<h2>";
						$tempOutput .= "<a href='/wiki/$wikiName/?article=".$foundFile."'>".$foundFile."</a>";
						$tempOutput .= "</h2>";

						$tempOutput .= "<div class='foundSearchContentPreview'>";
						$tempOutput .= str_replace("\n","",$lineData);
						#$tempOutput .= $foundStringPreview;
						$tempOutput .= "</div>";

						$tempOutput .= "<div class='wikiPublisher'>";
						$tempOutput .= "Publisher : ";
						$tempOutput .= str_replace("\n","",file_get_contents($_SERVER['DOCUMENT_ROOT']."/wiki/$wikiName/M/Title"));
						$tempOutput .= "</div>";

						$tempOutput .= "</div>\n";

						$output .= $tempOutput;

						appendFile($cacheFilePath,$tempOutput);

						echo $tempOutput;
						flush();
						ob_flush();
						break;
					}
				}
			}
		}
	}
	if ($foundData){
		return array(true,$output);
	}else{
		return array(false,$output);
	}
}
################################################################################
function printDateTime(){
	$date = new DateTimeImmutable();
	echo $date->format("y-m-d H:i:s");
}
################################################################################
?>
<html class='randomFanart'>
<head>
	<title>2web Search</title>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?PHP
include("/usr/share/2web/2webLib.php");
include($_SERVER['DOCUMENT_ROOT']."/header.php");

################################################################################
if (array_key_exists("q",$_GET)){
	$searchQuery = $_GET["q"];
	# create md5sum for the query to store output
	$querySum = md5($searchQuery);

	echo "<div class='settingListCard'>\n";
	echo "<h1>";
	echo "Searching  for '$searchQuery'";
	echo "<img id='spinner' src='/spinner.gif' />";
	echo "</h1>\n";

	# draw the top of the search results to prevent long searches from timing out
	flush();
	ob_flush();

	$foundResults=false;

	$indexPaths=Array();
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/movies/movies.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/shows/shows.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/music/music.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/random/albums.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/comics/comics.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/graphs/graphs.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/new/episodes.index"));

	$searchCacheFilePath="search/".$querySum.".index";

	# search weather stations first
	# - weather results can not be cached because they update every 15 minutes
	$weatherResults=searchWeather($searchCacheFilePath);

	if (file_exists($searchCacheFilePath)){
		# load the cached search results
		$searchCacheFileHandle = fopen($searchCacheFilePath,"r");
		while( ! feof($searchCacheFileHandle)){
			# send a line of the cache file
			echo fgets($searchCacheFileHandle);
		}
	}else{
		# if the file does not exist cache the search results
		# ignore user aborts after the cacheing has begun
		ignore_user_abort(true);

		$startSearchTime=microtime(True);
		# tell apache to not compress search results so streaming search results will work
		#apache_setenv("no-gzip", "1");

		# write the cache file as a lock file
		appendFile($searchCacheFilePath,"<!-- Search Started -->\n");
		# set the max execution time to 15 minutes
		# additional searches will display the results found by this running process
		set_time_limit(900);

		foreach( $indexPaths as $indexPath ){
			$indexInfo=searchIndex($indexPath,$searchQuery,$searchCacheFilePath);
			if ( $indexInfo[0] ){
				echo "<hr>";
				appendFile($searchCacheFilePath,"<hr>");
				$foundResults = true;
			}
		}
		# search all the live channel names
		$channelResults=searchChannels($searchCacheFilePath);
		if ($channelResults[0]){
			echo "<hr>";
			appendFile($searchCacheFilePath,"<hr>");
		}

		# search all the wikis
		$wikiSearchResults = searchAllWiki($_GET['q'],$searchCacheFilePath);
		if ($wikiSearchResults[0]){
			echo "<hr>";
			appendFile($searchCacheFilePath,"<hr>");
		}

		# calc the total search time
		$totalSearchTime= round((microtime(True) - $startSearchTime), 4);
		if ( $foundResults || ($wikiSearchResults[0] == true) || ($channelResults[0] == true) || ($weatherResults[0] == true) ){
			$tempEndString="<h1>Search Complete in $totalSearchTime seconds</h1>";
			echo $tempEndString;
			appendFile($searchCacheFilePath,$tempEndString);
		}else{
			$tempEndString="<h1>No Search Results for '$searchQuery' after search time of $totalSearchTime seconds</h1>";
			echo $tempEndString;
			appendFile($searchCacheFilePath,$tempEndString);
		}
	}

	formatEcho("<div class='titleCard'>",1);
	formatEcho("<h2>External Search</h2>",2);
	formatEcho("<div class='listCard'>",2);
	formatEcho("<a class='button' target='_new' href='https://www.mojeek.com/search?q=$searchQuery'>Mojeek 🔍</a>",3);
	formatEcho("<a class='button' target='_new' href='https://search.brave.com/search?q=$searchQuery'>Brave 🔍</a>",3);
	formatEcho("<a class='button' target='_new' href='https://www.peekier.com/#!$searchQuery'>Peekier 🔍</a>",3);
	formatEcho("<a class='button' target='_new' href='https://www.duckduckgo.com/?q=$searchQuery'>DuckDuckGo 🔍</a>",3);
	formatEcho("</div>",2);
	formatEcho("</div>",1);

	echo "</div>";

	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
}
?>
</body>
</html>
