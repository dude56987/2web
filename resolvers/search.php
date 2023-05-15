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
include("/usr/share/2web/2webLib.php");
################################################################################
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
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
function moreMusicLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2>🎧 External Music Search</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.newgrounds.com/search/conduct/audio?terms=$searchQuery'>🔎 Newgrounds</a>";
	echo "		<a class='button' rel='noreferer' href='https://archive.org/details/audio?query=$searchQuery'>🔎 Internet Archive</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreMusicMetaLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2>🌐 External Music Metadata Search</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>";
	echo "		<a class='button' rel='noreferer' href='https://musicbrainz.org/search?type=artist&query=$searchQuery'>🔎 Music Brainz</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreBookLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2>📚 External Book Search</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.gutenberg.org/ebooks/search/?query=$searchQuery'>🔎 Project Gutenberg</a>";
	echo "		<a class='button' rel='noreferer' href='https://en.wikibooks.org/wiki/?search=$searchQuery'>🔎 Wiki Books</a>";
	echo "		<a class='button' rel='noreferer' href='https://librivox.org/search?search_form=advanced&q=$searchQuery'>🔎 LibriVox</a>";
	echo "		<a class='button' rel='noreferer' href='https://en.wikisource.org/w/index.php?search=$searchQuery'>🔎 Wikisource</a>";
	echo "		<a class='button' rel='noreferer' href='https://archive.org/details/texts?query=$searchQuery'>🔎 Internet Archive</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreBookMetaLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2>🌐 External Book Metadata Search</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreSearchLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2>🔎 External Search</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.mojeek.com/search?q=$searchQuery'>🔎 Mojeek</a>";
	echo "		<a class='button' rel='noreferer' href='https://search.brave.com/search?q=$searchQuery'>🔎 Brave</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.duckduckgo.com/?q=$searchQuery'>🔎 DuckDuckGo</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.startpage.com/sp/search?q=$searchQuery'>🔎 StartPage</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreVideoLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2>🎞️ External Video Search</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.newgrounds.com/search/conduct/movies?terms=$searchQuery'>🔎 Newgrounds</a>";
	echo "		<a class='button' rel='noreferer' href='https://archive.org/details/movies?query=$searchQuery'>🔎 Internet Archive</a>";
	echo "		<a class='button' rel='noreferer' href='https://odysee.com/$/search?q=$searchQuery'>🔎 Odysee</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.youtube.com/results?search_query=$searchQuery'>🔎 Youtube</a>";
	echo "		<a class='button' rel='noreferer' href='https://rumble.com/search/video?q=$searchQuery'>🔎 Rumble</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.bitchute.com/search/?kind=video&query=$searchQuery'>🔎 Bitchute</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.twitch.tv/search?term=$searchQuery'>🔎 Twitch</a>";
	echo "		<a class='button' rel='noreferer' href='https://veoh.com/find/$searchQuery'>🔎 Veoh</a>";
	echo "		<a class='button' rel='noreferer' href='https://dailymotion.com/search/$searchQuery/videos'>🔎 Dailymotion</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreVideoMetaLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2 class=''>";
	echo "		🌐 External Video Metadata Search";
	echo "	</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.imdb.com/find?q=$searchQuery'>🔎 IMDB</a>";
	echo "		<a class='button' rel='noreferer' href='https://thetvdb.com/search?query=$searchQuery'>🔎 TheTVDB</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.themoviedb.org/search?query=$searchQuery'>🔎 TMDB</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreDataLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2 class=''>";
	echo "		🌐 External Data Search";
	echo "	</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.britannica.com/search?query=$searchQuery'>🔎 Britannica</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.fandom.com/?s=$searchQuery'>🔎 Fandom Wiki Search</a>";
	echo "		<a class='button' rel='noreferer' href='https://everything2.com/title/$searchQuery'>🔎 Everything2 Search</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreToolLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2 class=''>";
	echo "		🛠️ Web Tools";
	echo "	</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://web.archive.org/web/$searchQuery'>🔎 Wayback Machine</a>";
	echo "		<a class='button' rel='noreferer' href='https://downforeveryoneorjustme.com/$searchQuery'>🔎 Down for everyone or just Me</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreMapLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2 class=''>";
	echo "		🗺️ External Map Search";
	echo "	</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.openstreetmap.org/search?query=$searchQuery'>🔎 OpenStreetMap Search</a>";
	echo "		<a class='button' rel='noreferer' href='https://openweathermap.org/find?q=$searchQuery'>🔎 OpenWeatherMap Search</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreDictLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2 class=''>";
	echo "		📕 External Dictionary";
	echo "	</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.urbandictionary.com/define.php?term=$searchQuery'>🔎 Urban Dictionary</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.merriam-webster.com/dictionary/$searchQuery'>🔎 Merriam Webster Dictionary</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.britannica.com/dictionary/$searchQuery'>🔎 Britannica Dictionary</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.dictionary.com/browse/$searchQuery'>🔎 Random House Dictionary</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function moreSynLinks($searchQuery){
	echo "<div class='titleCard'>";
	echo "	<h2 class=''>";
	echo "		📙 External Thesaurus Search";
	echo "	</h2>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' rel='noreferer' href='https://www.merriam-webster.com/thesaurus/$searchQuery'>🔎 Merriam Webster Thesaurus</a>";
	echo "		<a class='button' rel='noreferer' href='https://www.thesaurus.com/browse/$searchQuery'>🔎 Random House Thesaurus</a>";
	echo "	</div>";
	echo "</div>";
}
################################################################################
function searchIndex($indexPath,$searchQuery,$cacheFilePath){
	$foundData=false;
	$tempData="";
	$resultLimit=100;
	$resultCounter=0;
	# if the search index exists
	if ( file_exists( $indexPath ) ){
		$fileHandle = fopen( $indexPath , "r" );
		while( ! feof( $fileHandle ) ){
			# read a line of the file
			$fileData = fgets( $fileHandle );
			#echo "The file path is '$fileData'<br>\n";
			#remove newlines from extracted file paths in index
			$fileData = str_replace( "\n" , "" , $fileData);
			if ( file_exists( $fileData ) ){
				# read the file
				$tempFileData = file_get_contents($fileData);
				$searchableData = strip_tags($tempFileData);
				if ( stripos( $searchableData, $searchQuery ) ){
					if ($foundData == false){
						$headerData = popPath($indexPath);
						$headerData = str_replace(".index","",$headerData);
						$headerData = ucfirst($headerData);
						$headerData = "<h2 id='$headerData'>".$headerData."</h2>";

						$tempData .= $headerData;
						echo $headerData;

						$foundData = true;
					}
					# read each line of the file
					$tempData .= $tempFileData;
					echo $tempFileData;
					ob_flush();
					flush();
					if($resultCounter >= $resultLimit){
						# break the loop there are to many results
						break;
					}else{
						$resultCounter+=1;
					}
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
	# kill weather search if no data directory can be found
	if (is_dir("/var/cache/2web/web/weather/data/")){
		echo " ";
	}else{
		return array(false,"");
	}

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
					# remove all html from the weather data file
					$weatherFileSearchData=strip_tags($weatherFileData);
					#echo $weatherFileData."<br>";
					if (stripos($weatherFileSearchData,$_GET['q'])){
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
				$tempOutput = "";

				if ($foundData == false){
					$headerData="<h2 id='channels'>Channels</h2>";
					$tempOutput .= $headerData;
					$foundData = true;
				}

				# check each filename for the search term
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
	$articleCount=0;
	if ($foundFiles){
		foreach($foundFiles as $foundFile){
			if ($articleCount > 100){
				break;
			}else if (stripos($foundFile,$_GET['q'])){
				$articleCount += 1;
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
					# get a single line of the file
					$lineData = fgets($articleHandle);
					# remove html tags
					$lineData = strip_tags($lineData);
					#$lineData = file_get_contents($wikiPath."/A/".$foundFile);
					# remove html tags
					$lineData = strip_tags($lineData);
					# make string all lowercase
					$lineData = strtolower($lineData);
					# highlight found search terms
					$lineData = str_replace($_GET['q'],("<span class='highlightText'>".$_GET['q']."</span>"),$lineData);
					#$lineData = str_replace(strtoupper($_GET['q']),("<span class='highlightText'>".strtoupper($_GET['q'])."</span>"),$lineData);
					if(stripos($lineData,$_GET['q'])){

						$articleCount += 1;

						$foundPosition=stripos($lineData,$_GET['q']);

						$startCut=$foundPosition - 10;
						if ($startCut < 0){
							$startCut=0;
						}

						$endCut = $foundPosition + 10;
						if ($endCut > strlen($lineData)){
							$endCut=strlen($lineData);
						}

						$tempOutput = "";

						$foundStringPreview = substr($lineData,$startCut,$endCut);
						if ($foundData == false){
							$foundData = true;
							# write once header
							$tempOutput .= "<h2>Local Wiki Articles</h2>";
						}

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
function checkForBangs($searchQuery){
	################################################################################
	# build the array of bang commands that can be checked for
	################################################################################
	$bangCommands=new ArrayObject();
	# dict and thesaurus
	$bangCommands->append(array("!define","https://www.dictionary.com/browse/"));
	$bangCommands->append(array("!about","https://wikipedia.org/w/?search="));
	$bangCommands->append(array("!describe","https://wikipedia.org/w/?search="));
	$bangCommands->append(array("!synonyms","https://www.thesaurus.com/browse/"));
	$bangCommands->append(array("!synonym","https://www.thesaurus.com/browse/"));
	$bangCommands->append(array("!syn","https://www.thesaurus.com/browse/"));
	$bangCommands->append(array("!similar","https://www.thesaurus.com/browse/"));
	$bangCommands->append(array("!alike","https://www.thesaurus.com/browse/"));
	# redirect bing to duckduckgo
	$bangCommands->append(array("!bing","https://duckduckgo.com/?q="));
	# redirect google to startpage
	$bangCommands->append(array("!google","https://www.startpage.com/sp/search?q="));
	$bangCommands->append(array("!g","https://www.startpage.com/sp/search?q="));
	# duckduckgo search
	$bangCommands->append(array("!duckduckgo","https://duckduckgo.com/?q="));
	$bangCommands->append(array("!duck","https://duckduckgo.com/?q="));
	$bangCommands->append(array("!ddg","https://duckduckgo.com/?q="));
	# startpage search
	$bangCommands->append(array("!startpage","https://www.startpage.com/sp/search?q="));
	$bangCommands->append(array("!start","https://www.startpage.com/sp/search?q="));
	$bangCommands->append(array("!s","https://www.startpage.com/sp/search?q="));
	# youtube search
	$bangCommands->append(array("!youtube","https://youtube.com/results?search_query="));
	$bangCommands->append(array("!yt","https://youtube.com/results?search_query="));
	# bitchute video search
	$bangCommands->append(array("!bitchute","https://www.bitchute.com/search/?kind=video&query="));
	$bangCommands->append(array("!bit","https://www.bitchute.com/search/?kind=video&query="));
	# peertube video search
	$bangCommands->append(array("!peertube","https://sepiasearch.org/search?search="));
	$bangCommands->append(array("!pt","https://sepiasearch.org/search?search="));
	# d tube video search
	$bangCommands->append(array("!dtube","https://d.tube/#!/s/"));
	$bangCommands->append(array("!dt","https://d.tube/#!/s/"));
	# odysee video search
	$bangCommands->append(array("!odysee","https://odysee.com/$/search?q="));
	$bangCommands->append(array("!od","https://odysee.com/$/search?q="));
	# brave search
	$bangCommands->append(array("!brave","https://search.brave.com/search?q="));
	$bangCommands->append(array("!b","https://search.brave.com/search?q="));
	# mojeek search
	$bangCommands->append(array("!mojeek","https://www.mojeek.com/search?q="));
	$bangCommands->append(array("!m","https://www.mojeek.com/search?q="));
	# wikipedia
	$bangCommands->append(array("!wikipedia","https://wikipedia.org/w/?search="));
	$bangCommands->append(array("!wiki","https://wikipedia.org/w/?search="));
	$bangCommands->append(array("!w","https://wikipedia.org/w/?search="));
	# urban dict
	$bangCommands->append(array("!urban","https://www.urbandictionary.com/define.php?term="));
	$bangCommands->append(array("!u","https://www.urbandictionary.com/define.php?term="));
	# britiannica wiki
	$bangCommands->append(array("!britannica","https://www.britannica.com/search?query="));
	$bangCommands->append(array("!brit","https://www.britannica.com/search?query="));
	# camelcamelcamel
	$bangCommands->append(array("!camelcamelcamel","https://camelcamelcamel.com/search?sq="));
	$bangCommands->append(array("!camel","https://camelcamelcamel.com/search?sq="));
	$bangCommands->append(array("!ccc","https://camelcamelcamel.com/search?sq="));
	$bangCommands->append(array("!c","https://camelcamelcamel.com/search?sq="));
	$bangCommands->append(array("!amazon","https://camelcamelcamel.com/search?sq="));
	$bangCommands->append(array("!a","https://camelcamelcamel.com/search?sq="));
	################################################################################
	# check for !bang help command in search query
	$bangHelp = "";
	if ( strpos($searchQuery,"!help") || ($searchQuery == "!help") ){
		# print out all the bang commands and the links they generate
		$bangHelp .= "<h1>Bang Command List</h1>";
		$bangHelp .= "<table>";
		$bangHelp .= "<tr><th>Bang</th><th>Link</th></tr>";
		foreach($bangCommands as $bang){
			$bangHelp .= "<tr><td>$bang[0]</td><td>$bang[1]</td></tr>";
		}
		$bangHelp .= "</table>";
	}
	################################################################################
	# before anything else is done check for bang commands
	foreach($bangCommands as $bang){
		if (strpos($searchQuery,$bang[0])){
			$cleanSearch=str_replace($bang[0],"",$searchQuery);
			redirect($bang[1].$cleanSearch);
		}
	}
	return $bangHelp;
}
# check for bangs prior to building any part of the webpage
# - This must be done before anything is writen to the page for the redirect to work
if (array_key_exists("q",$_GET)){
	$searchQuery = $_GET["q"];
	# check for bang commands
	$bangHelp=checkForBangs($searchQuery);
}
################################################################################
# start building the webpage
?>
<html class='randomFanart'>
<head>
	<title>2web Search</title>
	<script src='/2web.js'></script>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?PHP
include("/usr/share/2web/2webLib.php");
include($_SERVER['DOCUMENT_ROOT']."/header.php");

################################################################################
if (array_key_exists("q",$_GET) && ($_GET['q'] != "")){
	# create md5sum for the query to store output
	$querySum = md5($searchQuery);

	echo "<div class='settingListCard'>\n";
	echo "<h1>";
	echo "Searching  for '$searchQuery'";
	echo "<img id='spinner' src='/spinner.gif' />";
	echo "</h1>\n";
	if (stripos($searchQuery,"http://") || stripos($searchQuery,"https://")){
		echo "<div class='titleCard'>";
		echo "	<h2>Direct Link</h2>";
		echo "		<a class='button' href='$searchQuery'>$searchQuery</a>";
		echo "</div>";
	}else if (stripos($searchQuery,"www.") || stripos($searchQuery,".com") || stripos($searchQuery,".net") || stripos($searchQuery,".org")){
		# if this is a direct link make a link directly to the link
		echo "<div class='titleCard'>";
		echo "	<h2>Direct Link</h2>";
		echo "		<a class='button' href='https://$searchQuery'>https://$searchQuery</a>";
		echo "</div>";
	}
	$definitionData = shell_exec("dict '".$searchQuery."'");
	if ( $definitionData ){
		echo "<div class='settingListCard'>\n";
		echo "<h2>";
		echo "Definition";
		echo "</h2>";
		echo "<pre>\n";
		echo "$definitionData";
		echo "</pre>\n";
		echo "</div>";
	}

	$pspell = pspell_new("en");
	# if the query string contains a space
	if (strpos($_GET['q']," ")){
		# explode the string into an array split based on the spaces
		$searchTerms=explode( " " , $searchQuery );
		$correctedQuery="";
		$correctedQueryHTML="";
		$foundErrors=False;
		# for each word seprated by a space create a search link
		foreach($searchTerms as $searchTerm){
			# check the spelling of each search term and include spelling sugestions
			if (! pspell_check($pspell, $searchTerm)){
				$spellingSuggestions =  pspell_suggest($pspell, $searchTerm);
				foreach($spellingSuggestions as $word){
					# create a search for corrected spelling of each word
					#echo "		<a class='button' href='/search.php?q=$word'>$word</a>";
					# add the word to the corrected query
					$foundErrors=True;
					$correctedQuery .= $word." ";
					$correctedQueryHTML .= "<span class='highlightText'>".$word."</span> ";
					break;
				}
			}else{
				$correctedQuery .= $searchTerm." ";
				$correctedQueryHTML .= $searchTerm." ";
				#echo "		<a class='button' href='/search.php?q=$searchTerm'>$searchTerm</a>";
			}
		}
		if ($foundErrors){
			echo "<div class='titleCard'>";
			//echo "	<h2>Expand Search</h2>";
			echo "	<h2>Did you mean?</h2>";
			echo "	<a class='button' href='/search.php?q=$correctedQuery'>$correctedQueryHTML</a>";
			echo "</div>";
		}
	}else{
		if (! pspell_check($pspell, $_GET['q'])){
			echo "<div class='titleCard'>";
			echo "<h2>";
			echo "Did you mean?";
			echo "</h2>";
			echo "<div class='listCard'>";
			$spellingSuggestions =  pspell_suggest($pspell, $_GET['q']);
			foreach($spellingSuggestions as $word){
				echo "		<a class='button' href='/search.php?q=$word'>$word</a>";
			}
			echo "</div>";
			echo "</div>";
		}
	}
	echo "$bangHelp\n";
	# write blank space to bypass buffering and start loading of the search results
	# if this is not done page will hang on a difficult search
	for($index=0; $index<5000; $index++){
		echo " ";
	}

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
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/new/repos.index"));

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

		echo "<hr>";
		# sql episode search
		# load database
		$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/data.db");
		# set the timeout to 1 minute since most webbrowsers timeout loading before this
		$databaseObj->busyTimeout(60000);

		# run query to get a list of all episodes
		$result = $databaseObj->query('select * from "_episodes";');

		$resultLimit=100;
		$resultCounter=0;
		$episodeSearchResultsFound=False;
		# fetch each row data individually and display results
		while($row = $result->fetchArray()){
			$sourceFile = $row['title'];
			# search each episode file
			if (file_exists($sourceFile)){
				if (is_file($sourceFile)){
					if (stripos($sourceFile,".index")){
						// read the index entry
						$data=file_get_contents($sourceFile);
						if (stripos($data,$searchQuery)){
							if ($episodeSearchResultsFound == False){
								$headerData="<h2 id='old_episodes'>Old Episodes</h2>";
								appendFile($searchCacheFilePath, $headerData);
								echo $headerData;
								$episodeSearchResultsFound=True;
							}
							if($resultCounter >= $resultLimit){
								# break the loop there are to many results
								break;
							}else{
								$resultCounter+=1;
								// write the index entry
								appendFile($searchCacheFilePath, $data);
								echo $data;
								flush();
								ob_flush();
							}
						}
					}
				}
			}
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
	moreSearchLinks($searchQuery);
	moreDataLinks($searchQuery);

	moreMapLinks($searchQuery);
	moreToolLinks($searchQuery);

	moreDictLinks($searchQuery);
	moreSynLinks($searchQuery);

	moreVideoLinks($searchQuery);
	moreVideoMetaLinks($searchQuery);

	moreMusicLinks($searchQuery);
	moreMusicMetaLinks($searchQuery);

	moreBookLinks($searchQuery);
	moreBookMetaLinks($searchQuery);

	echo "</div>";

}else{
	# no search made, the search has been loaded, steal focus for the search bar
	echo "<script>";
	echo "	window.onload = function(){";
	echo "		document.getElementById('searchBox').focus();";
	echo "	}";
	echo "</script>";
	echo "<div class='searchSpacer settingListCard'>";
	echo "<h3>2web Database Stats</h3>";
	# build the search spacer and fill with meta content if any
	include("/usr/share/2web/templates/stats.php");
	#if (file_exists("fortune.index")){
	#	$todaysFortune = file_get_contents("fortune.index");
	#}else{
	#	$todaysFortune = "Fortune Disabled";
	#}
	#if ( file_exists("/etc/2web/fortuneStatus.cfg")){
	#	echo "<a class='homeWeather' href='/fortune.php'>";
	#	echo "<div class='listCard'>";
	#	echo "<h3>🔮 Fortune</h3>";
	#	echo "<div class='fortuneText'>";
	#	echo "$todaysFortune";
	#	echo "</div>";
	#	echo "</div>";
	#	echo "</a>";
	#}
	echo "<h3>Search Help</h3>";
	echo "<p>To search the 2web server type a query into the search bar and hit enter! If no data is found in the server, external links to services are given using the same search term.</p>";
	echo "<p>You can use !bang commands to redirect the search bar to other services. For a list of available !bang commands type !help into the search or click <a href='/search.php?q=!help'>here</a>.</p>";

	echo "</div>";
}
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
