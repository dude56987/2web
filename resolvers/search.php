<!--
########################################################################
# 2web search interface
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
-->
<?PHP
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with yt-dlp
################################################################################
# force debugging
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
################################################################################
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
################################################################################
function moreMusicLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2>🎧 External Music Search</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.newgrounds.com/search/conduct/audio?terms=$searchQuery'>🔎 Newgrounds</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://archive.org/details/audio?query=$searchQuery'>🔎 Internet Archive</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreMusicMetaLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2>🌐 External Music Metadata Search</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://musicbrainz.org/search?type=artist&query=$searchQuery'>🔎 Music Brainz</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreBookLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2>📚 External Book Search</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.gutenberg.org/ebooks/search/?query=$searchQuery'>🔎 Project Gutenberg</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://archive.org/details/texts?query=$searchQuery'>🔎 Internet Archive</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wikibooks.org/wiki/?search=$searchQuery'>🔎 Wiki Books</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://librivox.org/search?search_form=advanced&q=$searchQuery'>🔎 LibriVox</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wikisource.org/w/index.php?search=$searchQuery'>🔎 Wikisource</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.newgrounds.com/search/conduct/art?terms=$searchQuery'>🔎 Newgrounds</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreBookMetaLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2>🌐 External Book Metadata Search</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreSearchLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2>🔎 External Search</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.mojeek.com/search?q=$searchQuery'>🔎 Mojeek</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://search.brave.com/search?q=$searchQuery'>🔎 Brave</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.duckduckgo.com/?q=$searchQuery'>🔎 DuckDuckGo</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.startpage.com/sp/search?q=$searchQuery'>🔎 StartPage</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreVideoLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2>🎞️ External Video Search</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.newgrounds.com/search/conduct/movies?terms=$searchQuery'>🔎 Newgrounds</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://archive.org/details/movies?query=$searchQuery'>🔎 Internet Archive</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://odysee.com/$/search?q=$searchQuery'>🔎 Odysee</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://piped.video/results?search_query=$searchQuery'>🔎 Piped</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://rumble.com/search/video?q=$searchQuery'>🔎 Rumble</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.twitch.tv/search?term=$searchQuery'>🔎 Twitch</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.bitchute.com/search/?kind=video&query=$searchQuery'>🔎 Bitchute</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://cozy.tv/$searchQuery'>🔎 Cozy.TV</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://veoh.com/find/$searchQuery'>🔎 Veoh</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://dailymotion.com/search/$searchQuery/videos'>🔎 Dailymotion</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.youtube.com/results?search_query=$searchQuery'>🔎 Youtube</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreVideoMetaLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2 class=''>\n";
	echo "		🌐 External Video Metadata Search\n";
	echo "	</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.imdb.com/find?q=$searchQuery'>🔎 IMDB</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://thetvdb.com/search?query=$searchQuery'>🔎 TheTVDB</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.themoviedb.org/search?query=$searchQuery'>🔎 TMDB</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreDataLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2 class=''>\n";
	echo "		🌐 External Data Search\n";
	echo "	</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wikipedia.org/w/?search=$searchQuery'>🔎 Wikipedia</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.britannica.com/search?query=$searchQuery'>🔎 Britannica</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.fandom.com/?s=$searchQuery'>🔎 Fandom Wiki Search</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://everything2.com/title/$searchQuery'>🔎 Everything2 Search</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://library.kiwix.org/?q=$searchQuery'>🔎 ZIM file Search</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreToolLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2 class=''>\n";
	echo "		🛠️ Web Tools\n";
	echo "	</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://web.archive.org/web/$searchQuery'>🔎 Wayback Machine</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://downforeveryoneorjustme.com/$searchQuery'>🔎 Down for everyone or just Me</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreMapLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2 class=''>\n";
	echo "		🗺️ External Map Search\n";
	echo "	</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.openstreetmap.org/search?query=$searchQuery'>🔎 OpenStreetMap Search</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://openweathermap.org/find?q=$searchQuery'>🔎 OpenWeatherMap Search</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreDictLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2 class=''>\n";
	echo "		📕 External Dictionary\n";
	echo "	</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.urbandictionary.com/define.php?term=$searchQuery'>🔎 Urban Dictionary</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://en.wiktionary.org/wiki/$searchQuery'>🔎 Wiktionary</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.merriam-webster.com/dictionary/$searchQuery'>🔎 Merriam Webster Dictionary</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.britannica.com/dictionary/$searchQuery'>🔎 Britannica Dictionary</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.dictionary.com/browse/$searchQuery'>🔎 Random House Dictionary</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
}
################################################################################
function moreSynLinks($searchQuery){
	echo "<div class='titleCard'>\n";
	echo "	<h2 class=''>\n";
	echo "		📙 External Thesaurus Search\n";
	echo "	</h2>\n";
	echo "	<div class='listCard'>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.merriam-webster.com/thesaurus/$searchQuery'>🔎 Merriam Webster Thesaurus</a>\n";
	echo "		<a class='button' href='/exit.php?to=https://www.thesaurus.com/browse/$searchQuery'>🔎 Random House Thesaurus</a>\n";
	echo "	</div>\n";
	echo "</div>\n";
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
function checkForBangs($searchQuery){
	################################################################################
	# build the array of bang commands that can be checked for
	################################################################################
	# replace fullwidth version of ! if found
	$searchQuery=str_replace("！","!",$searchQuery);
	#
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
	# piped.video search
	$bangCommands->append(array("!piped","https://piped.video/results?search_query="));
	$bangCommands->append(array("!video","https://piped.video/results?search_query="));
	# youtube search
	$bangCommands->append(array("!youtube","https://piped.video/results?search_query="));
	$bangCommands->append(array("!yt","https://piped.video/results?search_query="));
	$bangCommands->append(array("!YOUTUBE","https://youtube.com/results?search_query="));
	$bangCommands->append(array("!YT","https://youtube.com/results?search_query="));
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
	# rumble
	$bangCommands->append(array("!rumble","https://rumble.com/search/video?q="));
	$bangCommands->append(array("!r","https://rumble.com/search/video?q="));
	# brave search
	$bangCommands->append(array("!search","https://search.brave.com/search?q="));
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
	# amazon
	$bangCommands->append(array("!AMAZON","https://www.amazon.com/s?k="));
	$bangCommands->append(array("!A","https://www.amazon.com/s?k="));
	# open street map
	$bangCommands->append(array("!osm","https://www.openstreetmap.org/search?query="));
	$bangCommands->append(array("!openstreetmap","https://www.openstreetmap.org/search?query="));
	# other bangs to call OSM
	$bangCommands->append(array("!map","https://www.openstreetmap.org/search?query="));
	$bangCommands->append(array("!locate","https://www.openstreetmap.org/search?query="));
	$bangCommands->append(array("!find","https://www.openstreetmap.org/search?query="));
	# open weather map
	$bangCommands->append(array("!owm","https://openweathermap.org/find?q="));
	$bangCommands->append(array("!openweathermap","https://openweathermap.org/find?q="));
	################################################################################
	# check for !bang help command in search query
	$bangHelp = "";
	if ( ($searchQuery == "!help") || strpos($searchQuery,"!help") || ($searchQuery == "!bang") || strpos($searchQuery,"!bang") ){
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
	if ($_SERVER["HTTPS"]){
		$bangPrefix="https://".$_SERVER["HTTP_HOST"]."/exit.php?to=";
	}else{
		$bangPrefix="http://".$_SERVER["HTTP_HOST"]."/exit.php?to=";
	}
	################################################################################
	# before anything else is done check for bang commands
	foreach($bangCommands as $bang){
		if (strpos($searchQuery,$bang[0])){
			$cleanSearch=str_replace($bang[0],"",$searchQuery);
			redirect($bangPrefix.$bang[1].$cleanSearch);
		}
	}
	return $bangHelp;
}
################################################################################
function drawHead($searchQuery=""){
	# start building the webpage
	echo "<html class='randomFanart'>\n";
	echo "<head>\n";
	echo " <title>2web Search$searchQuery</title>\n";
	echo " <script src='/2webLib.js'></script>\n";
	echo " <link rel='stylesheet' type='text/css' href='/style.css'>\n";
	echo " <link rel='icon' type='image/png' href='/favicon.png'>\n";
	echo " <link rel='search' type='application/opensearchdescription+xml' title='2web' href='/opensearch.xml'>\n";
	echo "</head>\n";
	echo "<body>\n";
}
################################################################################
function readSearchCacheFile($filePath,$searchQuery){
	$webDirectory="/var/cache/2web/web";
	#addToLog("DEBUG","searchQuery words split","Running readSearchCacheFile() on '".var_export($filePath,true)."' for '".$searchQuery."'");
	# if highlight is enabled
	if (array_key_exists("highlight",$_GET)){
		#
		$tempFileData=file($webDirectory."/search/".$filePath);
		#
		addToLog("DEBUG","searchQuery words split","searchQuery data'".var_export($searchQuery,true)."'");
		$searchQueryArray=explode(" ",$searchQuery);
		addToLog("DEBUG","searchQuery words split","searchQuery array '".var_export($searchQueryArray,true)."'");

		#addToLog("DEBUG","searchQuery words split","Loaded file data '".var_export($tempFileData,true)."'");
		foreach($tempFileData as $line){
			#addToLog("DEBUG","searchQuery words split","line data <pre>".var_export($line,true)."</pre>");
			$ogLine=$line;
			#
			$tempLineData=$line;
			#addToLog("DEBUG","searchQuery words split","searchQuery before explode ".var_export($searchQuery,true));
			#addToLog("DEBUG","searchQuery words split","searchQuery after explode ".var_export(explode(" ",$searchQuery),true));
			# highlight each word found in the search query
			foreach($searchQueryArray as $word){
				#addToLog("DEBUG","searchQuery words split","word being search for in the searchQuery '".var_export($word,true)."'");
				#addToLog("DEBUG","searchQuery words split","word being search for in the searchQuery '".var_export($word,true)."'");
				# only highlight lines with no pathing on them
				if (! (stripos($line,"='") !== false) ){
					#addToLog("DEBUG","searchQuery words split","Found Editable Line");
					# replace all varations of the word
					$tempLineData=str_ireplace($word,("<span class='enabledSetting'>".$word."</span>"),$tempLineData);

					if (stripos($line,$word) !== false){
						addToLog("DEBUG","searchQuery words split","Replacing word <pre>".var_export($word,true)."</pre> in line <pre>".var_export($ogLine,true)."</pre> with <pre>".var_export($tempLineData,true)."</pre>");
					}
					#$line=str_ireplace($word,("<span class='enabledSetting'>".$word."</span>"),$line);
					#addToLog("DEBUG","searchQuery words split","Replacing word <pre>".var_export($word,true)."</pre> in line <pre>".var_export($ogLine,true)."</pre> with <pre>".var_export($line,true)."</pre>");
				}
				#addToLog("DEBUG","searchQuery words split","line = '".var_export($line,true)."'");
			}
			#
			echo $tempLineData;
			#addToLog("DEBUG","searchQuery words split","Line Processing Complete");
			# send each processed line to the user
			clear();
		}
	}else{
		# if highlight is not enabled read the cached file without processing
		echo file_get_contents($webDirectory."/search/".$filePath);
		# send file output to user
		clear();
	}
}
################################################################################
function checkSpelling($searchQuery){
	# check the spelling and draw links to other sections

	# set the max execution time to 15 minutes
	# additional searches will display the results found by this running process
	set_time_limit(900);

	$pspell = pspell_new("en");
	$pspellData="";
	# if the query string contains a space
	if (strpos($searchQuery," ")){
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
			echo "	<h2>Did you mean?</h2>";
			echo "	<div class='listcard'>";
			echo "		<a class='button' href='/search.php?q=$correctedQuery'>$correctedQueryHTML</a>";
			echo "	</div>";
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
}
################################################################################
function webPlayerCheck($searchQuery){
	# check if this is a url and if it is then add a link that pushes it into the web player
	if(requireGroup("webPlayer", false)){
		# if the user has web player permissions check if a link can be created
		if(is_url($searchQuery)){
			echo "<div class='titleCard'>";
			echo "	<h2>Open Link In Web Player</h2>";
			echo "	<div class='listCard'>";
			echo "		<a class='button' href='/web-player.php?shareURL=\"".$searchQuery."\"'>🎞️ Load Link In Web Player</a>";
			echo "	</div>";
			echo "</div>";
		}
	}
}
################################################################################
if (array_key_exists("q",$_GET) && ($_GET['q'] != "")){
	cleanGetInput();
	# check for bangs prior to building any part of the webpage
	# - This must be done before anything is writen to the page for the redirect to work
	$searchQuery = $_GET["q"];
	# check for bang commands
	$bangHelp=checkForBangs($searchQuery);
	# convert the search to lowercase
	$searchQuery = strtolower($searchQuery);
	#
	$cleanQuery= cleanText($searchQuery);
	$cleanQuery= spaceCleanedText($cleanQuery);
	#
	drawHead(" - ".$searchQuery);
	# add the header document after building the document start
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
	# create md5sum for the query to store output
	$querySum = md5($searchQuery);

	echo "<div class='settingListCard'>\n";
	echo "<h1>";
	echo "Searching  for '$searchQuery'";
	echo "</h1>\n";
	# draw the bang help if it exists
	echo $bangHelp;

	$webDirectory=$_SERVER["DOCUMENT_ROOT"];

	logPrint($webDirectory."/search/".$querySum."_started.index");

	if(file_exists($webDirectory."/search/".$querySum."_started.index")){
		logPrint("the search has been started");
		$discoveredFiles=array_diff(scanDir($webDirectory."/search/"), Array($querySum."_started.index",$querySum."_finished.index",$querySum."_progress.index",$querySum."_processing.index",$querySum."_total.index"));
		# get the started time it is used by both branches of the below split
		$startedTime=trim(file_get_contents($webDirectory."/search/".$querySum."_started.index"),"\n\r");
		if(file_exists($webDirectory."/search/".$querySum."_finished.index")){
			logPrint("the search has finished");
			# get the finished time stamp
			$finishedTime=trim(file_get_contents($webDirectory."/search/".$querySum."_finished.index"),"\n\r");
			# figure out how long the search was processing for using start and end time stamps
			$processingTime=$finishedTime - $startedTime;
			# write when the search was completed in human readable format
			echo "<hr>\n";
			echo "<span class='singleStat'>\n";
			echo "	<span class='singleStatLabel'>\n";
			echo "		Search completed\n";
			echo "	</span>\n";
			echo "	<span class='singleStatValue'>\n";
			timeElapsedToHuman($finishedTime);
			echo "	</span>\n";
			echo "</span>\n";
			echo "<span class='singleStat'>\n";
			echo "	<span class='singleStatLabel'>\n";
			echo "		Search Processing Time\n";
			echo "	</span>\n";
			echo "	<span class='singleStatValue'>\n";
			timeToHuman($processingTime);
			echo "	</span>\n";
			echo "</span>\n";
			if (array_key_exists("highlight",$_GET)){
				# draw the button to disable highlighting
				echo "<a class='button right' href='?q=".$_GET["q"]."'>\n";
				echo "🌃 Disable Highlight\n";
				echo "</a>\n";
			}else{
				# draw the highlight button
				echo "<a class='button right' href='?highlight&q=".$_GET["q"]."'>\n";
				echo "💡 Highlight\n";
				echo "</a>\n";
			}
			echo "<hr>\n";
			checkSpelling($_GET["q"]);
			$noFoundCategories=true;
			# draw the jump buttons
			foreach($discoveredFiles as $filePath ){
				if (stripos($filePath,"$querySum") !== false){
					if ($noFoundCategories){
						$noFoundCategories=False;
						echo "<h2>Categories</h2>\n";
						echo "<div class='listCard'>\n";
					}
					$headerTitle=str_replace($querySum."_","",$filePath);
					$headerTitle=str_replace(".index","",$headerTitle);
					$jumpLink=$headerTitle;
					$headerTitle=str_replace("_"," ",$headerTitle);
					$headerTitle=ucwords($headerTitle);
					if (checkFilePathPermissions($filePath)){
						# draw the link
						echo "<a class='button' href='#$jumpLink'>$headerTitle</a>\n";
					}
				}
			}
			if ($noFoundCategories == false){
				echo "<a class='button' href='#external'>External Links</a>\n";
				echo "</div>\n";
			}
			# load the page as is with the auto refresh buttons
			foreach($discoveredFiles as $filePath ){
				if (stripos($filePath,"$querySum") !== false){
					$headerTitle=str_replace($querySum."_","",$filePath);
					$headerTitle=str_replace(".index","",$headerTitle);
					$jumpLink=$headerTitle;
					$headerTitle=str_replace("_"," ",$headerTitle);
					$headerTitle=ucwords($headerTitle);
					# check the permissions
					if (checkFilePathPermissions($filePath)){
						# draw the matching search content
						echo "<h2 id='$jumpLink'>$headerTitle</h2>\n";
						echo "<div>\n";
						echo "<hr>\n";
						readSearchCacheFile($filePath,$searchQuery);
						echo "<hr>\n";
						echo "</div>\n";
					}
				}
			}
		}else{
			logPrint("the search has not finished");
			checkSpelling($_GET["q"]);
			$autoRefreshValue="";
			# build the refresh
			if (array_key_exists("autoRefresh",$_GET)){
				echo "<div class='listCard'>";
				echo "<a class='button' href='?q=".$_GET["q"]."'>⏹️ Stop Refresh</a>\n";
				$autoRefreshValue="autoRefresh&";
			}else{
				echo "<div class='listCard'>";
				echo "<a class='button' href='?autoRefresh=true&q=".$_GET["q"]."'>▶️  Auto Refresh</a>\n";
				$autoRefreshValue="";
			}
			if (array_key_exists("highlight",$_GET)){
				# draw the button to disable highlighting
				echo "<a class='button right' href='?".$autoRefreshValue."q=".$_GET["q"]."'>\n";
				echo "🌃 Disable Highlight\n";
				echo "</a>\n";
			}else{
				# draw the highlight button
				echo "<a class='button right' href='?".$autoRefreshValue."highlight&q=".$_GET["q"]."'>\n";
				echo "💡 Highlight\n";
				echo "</a>\n";
			}
			echo "</div>";

			if(file_exists($webDirectory."/search/".$querySum."_processing.index")){
				$finishedVersions=str_replace("\n","",file_get_contents($webDirectory."/search/".$querySum."_progress.index"));
				$totalVersions=str_replace("\n","",file_get_contents($webDirectory."/search/".$querySum."_total.index"));
				#
				$executionTime = $_SERVER['REQUEST_TIME'] - str_replace("\n","",file_get_contents($webDirectory."/search/".$querySum."_processing.index")) ;
				$executionMinutes = floor($executionTime / 60);
				$executionSeconds = floor($executionTime - floor($executionMinutes * 60));
				# check for numbers less than 10
				if ($executionMinutes < 10){
					$executionMinutes = "0$executionMinutes" ;
				}
				if ($executionSeconds < 10){
					$executionSeconds = "0$executionSeconds" ;
				}
				if($finishedVersions > 0){
					$progress=floor(($finishedVersions/$totalVersions)*100);
				}else{
					$progress=0;
				}
				# draw the progress bar for the search
				echo "<div class='progressBar'>\n";
				echo "\t<img class='right' loading='lazy' src='/spinner.gif' />";
				if($finishedVersions > 0){
					echo "\t<div class='progressBarBar' style='width: ".$progress."%;'>\n";
					echo ($finishedVersions."/".$totalVersions." %".$progress);
				}else{
					echo ($finishedVersions."/".$totalVersions." %".$progress);
					echo "\t<div class='progressBarBar' style='width: ".$progress."%;'>\n";
				}
				echo "\t</div>\n";
				echo "</div>\n";

				# list the time elapsed so far
				echo "<div class='elapsedTime'>Searching for ";
				timeElapsedToHuman($startedTime,"");
				echo "</div>\n";
			}else{
				echo "<div class='elapsedTime'>Request has not yet started processing, Please wait for server to catch up...</div>\n";
			}
			$noFoundCategories=true;
			# draw the jump buttons
			foreach($discoveredFiles as $filePath ){
				if (stripos($filePath,"$querySum") !== false){
					if ($noFoundCategories){
						$noFoundCategories=False;
						echo "<h2>Categories</h2>\n";
						echo "<div class='listCard'>\n";
					}
					$headerTitle=str_replace($querySum."_","",$filePath);
					$headerTitle=str_replace(".index","",$headerTitle);
					$jumpLink=$headerTitle;
					$headerTitle=str_replace("_"," ",$headerTitle);
					$headerTitle=ucwords($headerTitle);
					# draw the link
					if (checkFilePathPermissions($filePath)){
						echo "<a class='button' href='#$jumpLink'>$headerTitle</a>\n";
					}
				}
			}
			if ($noFoundCategories == false){
				echo "<a class='button' href='#external'>External Links</a>\n";
				echo "</div>\n";
			}
			# load the page as is with the auto refresh buttons
			foreach($discoveredFiles as $filePath ){
				if (stripos($filePath,"$querySum") !== false){
					$headerTitle=str_replace($querySum."_","",$filePath);
					$headerTitle=str_replace(".index","",$headerTitle);
					$jumpLink=$headerTitle;
					$headerTitle=str_replace("_"," ",$headerTitle);
					$headerTitle=ucwords($headerTitle);
					# check the permissions
					if (checkFilePathPermissions($filePath)){
						# draw the matching search content
						echo "<h2 id='$jumpLink'>$headerTitle</h2>\n";
						echo "<div>";
						echo "<hr>\n";
						readSearchCacheFile($filePath,$searchQuery);
						echo "<hr>\n";
						echo "</div>";
					}
				}
			}
			# using javascript, reload the webpage every 60 seconds, time is in milliseconds
			if (array_key_exists("autoRefresh",$_GET)){
				echo "<script>\n";
				echo "delayedRefresh(10);\n";
				echo "</script>\n";
				echo "<noscript><meta http-equiv='refresh' content='10'></noscript>";
			}
		}
	}else{
		# build the refresh
		if (array_key_exists("autoRefresh",$_GET)){
			echo "<div class='listCard'>";
			echo "<a class='button' href='?q=".$_GET["q"]."'>⏹️ Stop Refresh</a>\n";
		}else{
			echo "<div class='listCard'>";
			echo "<a class='button' href='search.php?autoRefresh=true&q=".$_GET["q"]."'>▶️  Auto Refresh</a>\n";
		}
		if (array_key_exists("highlight",$_GET)){
			# draw the button to disable highlighting
			echo "<a class='button right' href='?q=".$_GET["q"]."'>\n";
			echo "🌃 Disable Highlight\n";
			echo "</a>\n";
		}else{
			# draw the highlight button
			echo "<a class='button right' href='?highlight&q=".$_GET["q"]."'>\n";
			echo "💡 Highlight\n";
			echo "</a>\n";
		}
		echo "</div>";

		# launch the process with a background scheduler
		$command = "";
		$command .= '/usr/bin/2web_search "'.str_replace(" ","_",$_GET["q"]).'" "'.$querySum.'" ';
		$command .= "";

		# launch the command
		addToQueue("multi",$command);

		# write the started file
		file_put_contents(($webDirectory."/search/".$querySum."_started.index"), $_SERVER["REQUEST_TIME"]);

		# redirect to the results page
		echo "<script>";
		echo "location.replace('?autoRefresh&q=".$_GET["q"]."')";
		echo "</script>";
		echo "<noscript><meta http-equiv='refresh' content='1;URL=search.php?autoRefresh=true&q=".$_GET["q"]."'></noscript>";
	}

	# check if web player links need created
	webPlayerCheck($_GET["q"]);
	# add ai search links if they exist
	if (file_exists($webDirectory."/ai/txt2img/index.php") || file_exists($webDirectory."/ai/prompt/index.php")){
		echo "<div class='titleCard'>";
		echo "<h2>AI Tools</h2>";
		echo "<div class='listCard'>";
		if (file_exists($webDirectory."/ai/txt2img/index.php")){
			echo "<form method='post' action='/ai/txt2img/index.php'>\n";
			echo "<input type='text' name='model' value='{ALL}' hidden>\n";
			echo "<input type='text' name='hidden' value='no' hidden>\n";
			echo "<input type='text' name='debug' value='no' hidden>\n";
			echo "<input type='text' name='imageInputPrompt' value='".$_GET["q"]."' hidden>";
			echo "<input type='text' name='imageNegativeInputPrompt' hidden>";
			echo "<button title='Submit the prompt to generate responses.' class='button' type='submit'>🎨 Generate Image From Query</button>";
			echo "</form>\n";
		}
		if (file_exists($webDirectory."/ai/prompt/index.php")){
			echo "<form method='post' action='/ai/prompt/index.php'>\n";
			echo "<input type='text' name='model' value='{ALL}' hidden>\n";
			echo "<input type='text' name='hidden' value='no' hidden>\n";
			echo "<input type='text' name='debug' value='no' hidden>\n";
			echo "<input type='text' name='prompt' value='".$_GET["q"]."' hidden>";
			echo "<button title='Submit the prompt to generate responses.' class='button' type='submit'>👽 Prompt AI Models with Query</button>";
			echo "</form>\n";
		}
		echo "</div>\n";
		echo "</div>\n";
	}

	echo "<h2 id='external'>External Search Links</h2>\n";

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

	echo "</div>\n";
}else{
	drawHead();
	# add the header document after building the document start
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
	# no search made, the search has been loaded, steal focus for the search bar
	echo "<script>\n";
	echo "	window.onload = function(){\n";
	echo "		document.getElementById('searchBox').focus();\n";
	echo "	}\n";
	echo "</script>\n";
	echo "<div class='searchSpacer settingListCard'>\n";
	echo "<h3>2web Database Stats</h3>\n";
	# build the search spacer and fill with meta content if any
	include("/usr/share/2web/templates/stats.php");
	echo "<h3>Search Help</h3>\n";
	echo "<p>To search the 2web server type a query into the search bar and hit enter! If no data is found in the server, external links to services are given using the same search term.</p>\n";
	echo "<p>You can use !bang commands to redirect the search bar to other services. For a list of available !bang commands type !help into the search or click <a href='/search.php?q=!help'>here</a>.</p>\n";

	echo "</div>\n";
	drawPosterWidget("portal", True);
}
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
