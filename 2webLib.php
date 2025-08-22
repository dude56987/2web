<?PHP
########################################################################
# 2web function library
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
################################################################################
ini_set('display_errors', 1);
################################################################################
if( ! function_exists("drawPosterWidget")){
	function drawPosterWidget($filterType, $random=False, $linkType="poster"){
		# Draw a widget containing items from the playlists section of 2web
		#
		# - The filter type determines which playlist filter the widget pulls items from
		# - The random variable sets if this is a widget containing random entries
		# - The linktype determines the formatting used
		# - The content rendered by this widget will be cached long term. So the playlist
		#   section of the site itself will be ahead of any widgets rendered by this
		#   function. However this function will update widget data on access. So if you
		#   load up a page with the widget and the site has not cached it recently, the
		#   same data as the playlists page will be loaded. The cache prevents high usage
		#   of the site from hammering the server databases.

		# check for group permissions in filter type
		if ($filterType == "all"){
			$groups=listModules();
			# check user has all permissions for groups
			foreach($groups as $groupName){
				if(! requireGroup($groupName, false)){
					$showOutput = false;
					break;
				}else{
					# mark output to be shown
					$showOutput = true;
				}
			}
		}else if ($filterType == "graphs"){
			$showOutput = requireGroup("graph2web", false);
		}else if ($filterType == "comics"){
			$showOutput = requireGroup("comic2web", false);
		}else if ($filterType == "channels"){
			$showOutput = requireGroup("iptv2web", false);
		}else if ($filterType == "repos"){
			$showOutput = requireGroup("git2web", false);
		}else if ($filterType == "episodes"){
			$showOutput = requireGroup("nfo2web", false);
		}else if ($filterType == "movies"){
			$showOutput = requireGroup("nfo2web", false);
		}else if ($filterType == "shows"){
			$showOutput = requireGroup("nfo2web", false);
		}else if ($filterType == "music"){
			$showOutput = requireGroup("music2web", false);
		}else if ($filterType == "artists"){
			$showOutput = requireGroup("music2web", false);
		}else if ($filterType == "albums"){
			$showOutput = requireGroup("music2web", false);
		}else if ($filterType == "tracks"){
			$showOutput = requireGroup("music2web", false);
		}else if ($filterType == "portal"){
			$showOutput = requireGroup("portal2web", false);
		}else if ($filterType == "channels"){
			$showOutput = requireGroup("iptv2web", false);
		}else if ($filterType == "applications"){
			$showOutput = requireGroup("php2web", false);
		}else{
			$showOutput = true;
		}
		if ($showOutput == false){
			# hide the output if group permissions are not available for this widget
			return false;
		}
		# Draw the poster widget as HTML
		if ($random){
			$dataSourcePath=$_SERVER['DOCUMENT_ROOT']."/$filterType/$filterType.index";
		}else{
			$dataSourcePath=$_SERVER['DOCUMENT_ROOT']."/new/$filterType.index";
		}
		if (file_exists($dataSourcePath)){
			if ($random){
				$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/widget_random_$filterType.index";
			}else{
				$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/widget_updated_$filterType.index";
			}
			if (file_exists($cacheFile)){
				if (time()-filemtime($cacheFile) > 2 * 3600){
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
				$fileObj=fopen($cacheFile,'w') or die("Unable to write cache file!");
				// set so script keeps running even if user cancels it
				ignore_user_abort(true);
				// get a list of all the genetrated index links for the page
				if ($random){
					# this means search for random entries
					$sourceFiles = file($_SERVER['DOCUMENT_ROOT']."/$filterType/$filterType.index", FILE_IGNORE_NEW_LINES);
					# randomize the array
					shuffle($sourceFiles);
				}else{
					// this means search for newest entries
					$sourceFiles = file($_SERVER['DOCUMENT_ROOT']."/new/$filterType.index", FILE_IGNORE_NEW_LINES);
					# sort the array newest to oldest
					$sourceFiles = array_reverse($sourceFiles);
				}
				$counter=0;
				$drawBottom=0;
				foreach($sourceFiles as $sourceFile){
					$sourceFileName = $sourceFile;
					if (file_exists($sourceFile)){
						if (is_file($sourceFile)){
							if (strpos($sourceFile,".index")){
								$counter += 1;
								if ($counter == 1){
									fwrite($fileObj,"<div class='titleCard'>");
									if ($random){
										fwrite($fileObj,"<h1>Random ".ucfirst($filterType)."</h1>");
									}else{
										fwrite($fileObj,"<h1>Updated ".ucfirst($filterType)."</h1>");
									}
									fwrite($fileObj,"<div class='listCard'>");
									$drawBottom = 1;
								}
								// read the index entry
								$data=file_get_contents($sourceFile);
								// write the index entry
								fwrite($fileObj,"$data");
							}
						}
						# limit results
						if ($counter >= 16){
							// break the loop
							break;
						}
					}
				}
				if ($drawBottom == 1){
					if ($filterType == "music"){
						$linkType="icon";
					}else if ($filterType == "music"){
						$linkType="icon";
					}else if ($filterType == "albums"){
						$linkType="icon";
					}else if ($filterType == "artists"){
						$linkType="icon";
					}else if ($filterType == "episodes"){
						$linkType="episode";
					}else if ($filterType == "portal"){
						$linkType="episode";
					}else{
						$linkType="poster";
					}
					if ($linkType == "icon"){
						if ($random){
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/random/index.php?filter=$filterType'>");
							fwrite($fileObj,"<h2 class='moreEpisodesLinkIcon'>");
							fwrite($fileObj,"🔀");
							fwrite($fileObj,"</h2>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List");
							fwrite($fileObj,"</a>");
						}else{
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/new/index.php?filter=$filterType'>");
							fwrite($fileObj,"<h2 class='moreEpisodesLinkIcon'>");
							fwrite($fileObj,"📜");
							fwrite($fileObj,"</h2>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List");
							fwrite($fileObj,"</a>");
						}
					}else if ($linkType == "episode"){
						# show page episode
						if ($random){
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/random/index.php?filter=$filterType'>");
							fwrite($fileObj,"<h2 class='moreEpisodesLinkIcon'>");
							fwrite($fileObj,"🔀");
							fwrite($fileObj,"</h2>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List");
							fwrite($fileObj,"</a>");
						}else{
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/new/index.php?filter=$filterType'>");
							fwrite($fileObj,"<h2 class='moreEpisodesLinkIcon'>");
							fwrite($fileObj,"📜");
							fwrite($fileObj,"</h2>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List");
							fwrite($fileObj,"</a>");
						}
					}else{
						if ($random){
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/random/index.php?filter=$filterType'>");
							fwrite($fileObj,"<h2 class='moreEpisodesLinkIcon'>");
							fwrite($fileObj,"🔀");
							fwrite($fileObj,"</h2>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List");
							fwrite($fileObj,"</a>");
						}else{
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/new/index.php?filter=$filterType'>");
							fwrite($fileObj,"<h2 class='moreEpisodesLinkIcon'>");
							fwrite($fileObj,"📜");
							fwrite($fileObj,"</h2>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List");
							fwrite($fileObj,"</a>");
						}
					}
				}
				fwrite($fileObj,"</div>");
				fwrite($fileObj,"</div>");
				// close the file
				fclose($fileObj);
			}
			// read the file that is cached
			echo file_get_contents($cacheFile);
			// flush the buffer
			flush();
			ob_flush();
		}
	}
}
################################################################################
if( ! function_exists("detectEnabledStatus")){
	function detectEnabledStatus($moduleName){
		# Return true if given module is enabled
		# Used for testing module enabled or disabled status
		#
		# return true if a $filePath exists and contains the text "enabled"
		$filePath = "/etc/2web/mod_status/".$moduleName.".cfg";
		return yesNoCfgCheck($filePath);
	}
}
################################################################################
if( ! function_exists("listModules")){
	function listModules($allModules=false){
		# by default only the modules used by content filters will be shown
		$modules=Array("2web","nfo2web","comic2web","music2web","iptv2web","graph2web","wiki2web","git2web","portal2web","php2web");
		if ($allModules){
			# all background loading modules
			$modules=array_merge($modules,Array("kodi2web","ytdl2nfo","rss2nfo","weather2web","ai2web"));
		}
		return $modules;
	}
}
################################################################################
if( ! function_exists("checkModStatus")){
	function checkModStatus($moduleName){
		# Return true if given module is enabled
		# Used for testing module enabled or disabled status
		#
		# return true if a $filePath exists and contains the text "enabled"
		$filePath = "/etc/2web/mod_status/".$moduleName.".cfg";
		return yesNoCfgCheck($filePath);
	}
}
################################################################################
if( ! function_exists("is_url")){
	function is_url($urlText){
		# check if a string is a valid url link

		# validate the url
		if (filter_var($urlText, FILTER_VALIDATE_URL) !== false){
			# check for http or https protocol
			if (substr($urlText,0,7) == "http://"){
				return true;
			}else if (substr($urlText,0,8) == "https://"){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}
################################################################################
if( ! function_exists("formatText")){
	function formatText($text,$tabs=0,$newline="\n"){
		# write a line with a number of tabs and a custom newline character to variable
		$returnValue="";
		if ($tabs > 0){
			foreach(range(1,$tabs) as $index){
				$returnValue .= "\t";
			}
		}
		$returnValue .= $text;
		$returnValue .= $newline;

		return $returnValue;
	}
}
################################################################################
if( ! function_exists("formatEcho")){
	function formatEcho($text,$tabs=0,$newline="\n"){
		# Write a line with a number of tabs and a custom newline character to page
		echo formatText($text,$tabs,$newline);
	}
}
################################################################################
if( ! function_exists("logPrint")){
	function logPrint($logMessage){
		# Print data into the console log of a page with javascript
		echo "<script>";
		echo "console.log('".$logMessage."');";
		echo "</script>\n";
	}
}
################################################################################
if( ! function_exists("is_in_array")){
	function is_in_array($needle,$haystack){
		# search for a needle in a string or array haystack
		if (is_array($haystack)){
			return in_array($needle, $haystack);
		}else{
			if (stripos($haystack,$needle) !== false){
				return true;
			}else{
				return false;
			}
		}
	}
}
################################################################################
if( ! function_exists("addToQueue")){
	function addToQueue($type,$command){
		# addToQueue($type,$command)
		#
		# Add a item to queue2web
		#
		# - Command should be a bash command to run
		# - Set type to 'multi' for multithreaded queue processing
		# - Set the type to 'single' for linear processing
		# - Both queues will be processed, just diffrently
		if (! in_array($type, Array("single","multi","idle"))){
			# if the type is a unknown type that has no existing queue
			addToLog("DEBUG","Unknown Type","Unknown type used in addToQueue(), Use 'multi', 'single', or 'idle' as the types.");
			$queueAvailable=$false;
			return false;
		}
		# build the path for the job file
		$jobPath=( "/var/cache/2web/queue/$type/".time().str_replace(".","",microtime())."-".md5($command));
		# place command in the idle queue
		file_put_contents($jobPath.".cmd", $command );
		# return the path to the job file
		return $jobPath;
	}
}
################################################################################
if( ! function_exists("listAllIndex")){
	function listAllIndex($indexPath,$sortMethod="forward"){
		# List all text files stored in a .index file
		$foundData=false;
		$tempData="";
		#logPrint("loading index file ".$indexPath);
		# if the search index exists
		if ( file_exists( $indexPath ) ){
			#logPrint("index file '$indexPath' is a file reading");
			if ($sortMethod == "forward"){
				$fileHandle = fopen( $indexPath , "r" );
				while( ! feof( $fileHandle ) ){
					#logPrint("reading line of index file");
					# read a line of the file
					$fileData = fgets( $fileHandle );
					#logPrint("line uncleaned = ",$fileData);
					#remove newlines from extracted file paths in index
					$fileData = str_replace( "\n" , "" , $fileData);
					#logPrint("line newline stripped = ",$fileData);
					if ( file_exists( $fileData ) ){
						#logPrint("Opening data file ".$fileData." of index file...");
						# read the file in peices
						$linkTextHandle = fopen( $fileData , "r" );
						while( ! feof( $linkTextHandle ) ){
							$packetData = fgets( $linkTextHandle , 4096 );
							#logPrint("reading a single line of the file...");
							# read each packet of the file
							# write the packet data to the page as it is found and send read data immediately
							echo $packetData;
							flush();
							ob_flush();
						}
						$foundData = true;
					}
				}
			}else if ($sortMethod == "reverse"){
				# read the file data and reverse it
				$fileData = array_reverse( file( $indexPath ) , FILE_IGNORE_NEW_LINES);
				# print each file data
				foreach( $fileData as $line ){
					# remove newlines
					$line=str_replace("\n","",$line);
					# increment the item counter for the page
					#echo $line;
					# read the file in peices
					#$foundData = file_get_contents($line);
					#echo $foundData;
					$foundData = readFileInPackets($line);
					if ($foundData){
						$foundData = true;
					}
				}
			}else if ($sortMethod == "random"){
				# load the entire file
				$fileData =  file( $indexPath , FILE_IGNORE_NEW_LINES);
				# shuffle the lines
				shuffle( $fileData );
				# print the data
				foreach( $fileData as $line ){
					$pageItemCounter += 1;
					# remove newlines
					$line=str_replace("\n","",$line);
					# increment the item counter for the page
					# read the file in peices
					#$foundData = file_get_contents($line);
					#echo $foundData;
					$foundData = readFileInPackets($line);
					if ($foundData){
						$foundData = true;
					}
				}
			}
		}
		# blank the data
		if ($foundData){
			return true;
		}else{
			return false;
		}
	}
}
################################################################################
if( ! function_exists("listIndexPage")){
	function listIndexPage($indexPath,$pageNumber=1,$maxPageItems=50,$sortMethod='forward'){
		# List a single page of a .index list
		$foundData=false;
		$tempData="";
		#logPrint("loading index file ".$indexPath);
		# if the search index exists
		if ( file_exists( $indexPath ) ){
			$pageCounter=1;
			$pageItemCounter=0;
			if ($sortMethod == "forward"){
				# forward read method read data the most memory efficent way
				$fileHandle = fopen( $indexPath , "r" );
				while( ! feof( $fileHandle ) ){
					$pageItemCounter += 1;
					# increment the item counter for the page
					if ($pageItemCounter > $maxPageItems){
						$pageCounter += 1;
						$pageItemCounter = 0;
					}
					# reset the page item counter
					#logPrint("reading line of index file");
					# read a line of the file
					$fileData = fgets( $fileHandle );
					#logPrint("line uncleaned = ",$fileData);
					#remove newlines from extracted file paths in index
					$fileData = str_replace( "\n" , "" , $fileData);
					#logPrint("line newline stripped = ",$fileData);
					if ( $pageCounter == $pageNumber ){
						# read the file in peices
						$foundData = readFileInPackets($fileData);
					}
				}
			}else if ($sortMethod == "reverse"){
				# read the file data and reverse it
				$fileData = array_reverse( file( $indexPath ) , FILE_IGNORE_NEW_LINES);
				# print each file data
				foreach( $fileData as $line ){
					# remove newlines
					$line=str_replace("\n","",$line);
					# increment the item counter for the page
					$pageItemCounter += 1;
					if ($pageItemCounter > $maxPageItems){
						$pageCounter += 1;
						$pageItemCounter = 0;
					}
					if ( $pageCounter == $pageNumber ){
						#echo $line;
						# read the file in peices
						#$foundData = file_get_contents($line);
						#echo $foundData;
						$foundData = readFileInPackets($line);
					}
				}
			}else if ($sortMethod == "random"){
				# load the entire file
				$fileData =  file( $indexPath , FILE_IGNORE_NEW_LINES);
				# shuffle the lines
				shuffle( $fileData );
				# print the data
				foreach( $fileData as $line ){
					$pageItemCounter += 1;
					# remove newlines
					$line=str_replace("\n","",$line);
					# increment the item counter for the page
					if ($pageItemCounter > $maxPageItems){
						$pageCounter += 1;
						$pageItemCounter = 0;
					}

					if ( $pageCounter == $pageNumber ){
						# read the file in peices
						#$foundData = file_get_contents($line);
						#echo $foundData;
						$foundData = readFileInPackets($line);
					}
				}
			}
			#logPrint("index file '$indexPath' is a file reading");
		}
		# blank the data
		if ($foundData){
			return true;
		}else{
			return false;
		}
	}
}
################################################################################
if( ! function_exists("readFileInPackets")){
	function readFileInPackets($fileData){
		# Read a file in large frame packets and send them to the page as each is read from the disk
		$foundData=False;
		#echo "'".$fileData."'<br>\n";
		#if ( file_exists( $fileData ) ){
		if ( is_file( $fileData ) ){
			# print the page item
			#logPrint("Opening data file ".$fileData." of index file...");
			# read the file in peices
			$linkTextHandle = fopen( $fileData , "r" );
			while( ! feof( $linkTextHandle ) ){
				$packetData = fgets( $linkTextHandle , 4096 );
				#logPrint("reading a single line of the file...");
				# read each packet of the file
				# write the packet data to the page as it is found and send read data immediately
				echo $packetData;
				flush();
				ob_flush();
				$foundData=True;
			}
		}
		if ( $foundData ){
			return True;
		}else{
			return False;
		}
	}
}
################################################################################
if( ! function_exists("loadAlphabet")){
	function loadAlphabet(){
		# load the alphabet as an array
		return str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
	}
}
################################################################################
if( ! function_exists("is_alphaNum")){
	function is_alphaNum($string){
		# returns true if all characters in a string are in the alphabet
		$stringData=str_split($string);
		#
		$letterList=loadAlphabet();
		$letterList=array_merge($alphaList,str_split("0123456789"));
		#
		$allAlpha=true;
		#
		foreach($stringData as $letter){
			# verify the letters are letters in the english alphabet
			if (! in_array($letter,$letterList)){
				# break the loop and return false on first incorrect value
				return false;
			}
		}
		return true;
	}
}
################################################################################
if( ! function_exists("is_alpha")){
	function is_alpha($string){
		# returns true if all characters in a string are in the alphabet
		$stringData=str_split($string);
		#
		$alphaList=loadAlphabet();
		#
		$allAlpha=true;
		#
		foreach($stringData as $letter){
			# verify the letters are letters in the english alphabet
			if (! in_array($letter,$alphaList)){
				# break the loop and return false on first incorrect value
				return false;
			}
		}
		return true;
	}
}
################################################################################
if( ! function_exists("findFirstLetter")){
	function findFirstLetter($string){
		#
		$string=strtoupper($string);
		#
		$stringData=str_split($string);
		#
		$letterList=loadAlphabet();
		$letterList=array_merge($letterList,str_split("0123456789"));
		#
		foreach($stringData as $letter){
			# return the first character
			#return $letter;
			# verify the letter is a letter in the english alphabet
			if (in_array($letter,$letterList)){
				return $letter;
			}
		}
		return false;
	}
}
################################################################################
if( ! function_exists("displayIndexWithPages")){
	function displayIndexWithPages($indexFilePath,$emptyMessage="This list is empty. Please come back latter. :D",$maxItemsPerPage=45,$sortMethod="forward"){
		#
		if (! file_exists($indexFilePath)){
			echo "$emptyMessage";
			return false;
		}
		# Higher level display a display index with the pages buttons below the page
		# - sort can be forward, reverse, and random
		# - Default maxItemsPerPage is equilivent to the number of even rows on the default css setting
		if (array_key_exists("page",$_GET)){
			if (strtolower($_GET['page']) == "all"){
				if( ! listAllIndex($indexFilePath,$sortMethod)){
					// no shows have been loaded yet
					echo $emptyMessage;
				}
			}else{
				# check if the page given is a letter
				# jump to the first page containing that letter
				if (is_alpha($_GET['page'])){
					# find the page where entries first begin with letter
					$indexFileData=file($indexFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					# clamp the data and scan per page to find the page the letter starts on
					$chunkedIndex=array_chunk($indexFileData,$maxItemsPerPage);
					$breakSearch=false;
					$foundPageNumber=1;
					# search for the chunk where the letter first occurs in the sorted list
					foreach($chunkedIndex as $singleChunk){
						foreach($singleChunk as $individualEntry){
							#logPrint("---------------------------");
							#logPrint($_GET['page']."=GET[page]");
							#logPrint(cleanText(($individualEntry))."=individualEntry" );
							#logPrint(cleanText(dirname($individualEntry))."=dirname(individualEntry)");
							#logPrint(cleanText(basename(dirname($individualEntry)))."=basename(dirname(individualEntry)");
							#logPrint(findFirstLetter(basename(dirname($individualEntry)))."=findFirstLetter(basename(dirname(individualEntry)");
							# break the loop when the letter is first found
							if ( findFirstLetter(basename(dirname($individualEntry))) == $_GET['page'] ){
								# found it set the page and list the index
								$breakSearch=true;
							}
							if ($breakSearch){
								break;
							}
						}
						if ($breakSearch){
							break;
						}
						$foundPageNumber+=1;
					}
					$_GET["page"]=$foundPageNumber;
				}
				# if the page is a number load that page on the index
				listIndexPage($indexFilePath,$_GET['page'],$maxItemsPerPage,$sortMethod);
			}
		}else{
			// no shows have been loaded yet
			$_GET['page'] = 1;
			listIndexPage($indexFilePath,1,$maxItemsPerPage,$sortMethod);
		}
		# build the page buttons
		# get the index size by reading the index file and counting

		$fileItemCount=count(file($indexFilePath));

		$pageCount = $fileItemCount / $maxItemsPerPage;

		if ( ( $pageCount - floor($pageCount) ) > 0){
			$pageCount += 1;
		}

		$pageCount = floor( $pageCount );

		# build the variables from get and attach the pages on the end
		$otherVars="";
		foreach ( array_keys($_GET) as $varName ){
			# ignore page variables
			if ( $varName != "page" ){
				# read the variable name and value
				$otherVars .= "&".$varName."=".$_GET[$varName];
			}
		}

		if ( $pageCount > 1 ){
			#echo "<div class='titleCard'>\n";
			echo "<div class='titleCard'>\n";
			echo "<div class='listCard'>\n";
			echo "<table class='controlTable'>\n";
			echo "	<tr>\n";

			if (is_numeric($_GET["page"]) and ( $_GET["page"] > 1 )){
				# build the left button for pages
				echo "		<td>\n";
				echo "			<a class='button' href='?page=".($_GET["page"] - 1).$otherVars."'>";
				echo "◁ Back";
				echo "</a>\n";
				echo "		</td>\n";
			}
			#
			if (array_key_exists("page",$_GET)){
				#
				if ($_GET["page"] == 1){
					# first page
					$pageMargin=8;
				}else if ($_GET["page"] == $pageCount){
					# last page
					$pageMargin=8;
				}else{
					#
					$pageMargin=4;
					#
					if($_GET["page"] < $pageMargin ){
						$pageMargin=$pageMargin + $_GET["page"];
					}else if($_GET["page"] > ( $pageCount - $pageMargin ) ){
						$pageMargin=( $pageMargin + ($pageCount - $_GET["page"] ) ) - 1;
					}else{
						$pageMargin=4;
					}
				}
			}else{
				$pageMargin=8;
			}
			# clamp the margin
			if ($pageMargin > 8){
				$pageMargin=8;
			}
			#
			$pageDrawCounter=0;
			foreach((range(1,$pageCount)) as $currentPageNumber){
				$activePage = False;
				$drawPage = False;
				if (array_key_exists("page",$_GET)){
					# only draw pages within 8 pages of the current page
					if ($currentPageNumber == 1){
						# print the first page number always
						$drawPage = True;
						$pageDrawCounter += 1;
					}else if ($currentPageNumber == $pageCount){
						# print the last page always
						$drawPage = True;
						$pageDrawCounter += 1;
						if ($currentPageNumber == $_GET['page']){
							$activePage = True;
						}
					}else if (($currentPageNumber < ($_GET['page'] + $pageMargin)) && ($currentPageNumber > ($_GET['page'] - $pageMargin))){
						$drawPage = True;
						$pageDrawCounter += 1;
						if ($currentPageNumber == $_GET['page']){
							$activePage = True;
						}
					}else{
						if ($currentPageNumber == 2){
							echo "<td>\n";
							echo "⋯\n";
							echo "</td>\n";
						}else if($currentPageNumber == ($pageCount - 1)){
							echo "<td>\n";
							echo "⋯\n";
							echo "</td>\n";
						}
					}
				}
				# check if the page number should be drawn
				if ($drawPage){
					echo "<td>\n";
					# draw the page
					if ($activePage){
						# if the page is active set the class
						echo "<a class='activeButton' href='?page=$currentPageNumber$otherVars'>";
						echo "$currentPageNumber";
						echo "</a>\n";
					}else{
						echo "<a class='button' href='?page=$currentPageNumber$otherVars'>";
						echo "$currentPageNumber";
						echo "</a>\n";
					}
					echo "</td>\n";
				}
			}

			if (is_numeric($_GET["page"]) and ( $_GET["page"] < $pageCount )){
				echo "		<td>\n";
				# build the next button for pages
				echo "			<a class='button' href='?page=".($_GET["page"] + 1)."$otherVars'>";
				echo "Next ▷";
				echo "</a>\n";
				echo "		</td>\n";
			}

			echo "	</tr>\n";
			echo "</table>\n";

			echo "</div>\n";
			echo "</div>";
			# only draw the letter jumping buttons if there are more than 8 pages
			if ( $pageCount >= 8 ){
				echo "<div class='titleCard'>\n";
				echo "<div class='listCard'>";
				# draw the secondary jump bar
				echo "<table class='controlTable'>\n";
				echo "	<tr>\n";
				# Draw The All Page
				echo "		<td>\n";
				echo "			<a class='button' href='?page=all$otherVars'>∞</a>\n";
				echo "		</td>\n";
				# create an array for the links
				$alphaList=loadAlphabet();
				# build all the links
				foreach($alphaList as $alphaLink){
					echo "		<td>\n";
					echo "			<a class='button' href='?page=$alphaLink$otherVars'>$alphaLink</a>\n";
					echo "		</td>\n";
				}
				echo "	</tr>\n";
				echo "</table>\n";
				echo "</div>\n";
				echo "</div>";
			}
		}
	}
}
################################################################################
if( ! function_exists("checkPort")){
	function checkPort($port){
		# Check if a port is open on the local server
		$connection = @fsockopen($_SERVER['HTTP_HOST'], $port, $errorNum, $errorStr, 30);
		if (is_resource($connection)){
			fclose($connection);
			return true;
		}else{
			# no connection could be made
			return false;
		}
	}
}
################################################################################
if( ! function_exists("checkServerPath")){
	function checkServerPath($subDir){
		# Check if a server path exists on the local server

		# build the url
		$url = "http://".$_SERVER['HTTP_HOST'].$subDir;
		// initilize curl the the url
		$http = curl_init($url);
		# set the return transfer option to prevent output of url data
		curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
		// execute curl
		$result = curl_exec($http);
		# get the http status code
		$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
		#echo "\n<br>http status<br>\n";
		#echo "\n<br>".$http_status."<br>\n";
		#echo "\n<br>http status<br>\n";
		#echo "\n<br>result<br>\n";
		#echo "\n<br>".$result."<br>\n";
		#echo "\n<br>result<br>\n";
		# read the error code and return true if http return code was "OK"
		if ($http_status == "200"){
			return true;
		}else{
			return false;
		}
	}
}
###############################################################################
if( ! function_exists("availableServicesArray")){
	function availableServicesArray(){
		# Return an array of all the detectable services, ports and descriptions
		// take port number and service name and generate a index then generate a series of links
		$services = Array();
		array_push($services,Array('2WEB', 80, 'This Server'));
		array_push($services,Array('CUPS', 631, 'Print Server'));
		array_push($services,Array('TRANSMISSION', 9091, 'Bittorrent Client'));
		array_push($services,Array('DELUGE', 8112, 'Bittorrent Client'));
		array_push($services,Array('QBITTORRENT', 1342, 'Bittorrent Client'));
		array_push($services,Array('MEDUSA', 1340, 'Metadata Tool'));
		array_push($services,Array('SONARR', 8989, 'Metadata Tool'));
		array_push($services,Array('RADARR', 7878, 'Metadata Tool'));
		array_push($services,Array('BAZARR', 6767, 'Subtitle Downloader'));
		array_push($services,Array('LIDARR', 8686, 'Metadata Tool'));
		array_push($services,Array('JACKETT', 9117, 'Metadata Tool'));
		array_push($services,Array('CUBERITE', 1339, 'Minecraft Server'));
		array_push($services,Array('MINEOS', 8443, 'Minecraft Server'));
		array_push($services,Array('NETDATA', 19999, 'Realtime Stats'));
		array_push($services,Array('WEBMIN', 10000, 'Web Administration'));
		array_push($services,Array('DIETPI-DASHBOARD', 5252, 'Realtime Stats'));
		array_push($services,Array('AdGuard Home', 8083, 'DNS AdBlock'));
		array_push($services,Array('RPI-Monitor', 8888, 'Realtime Stats'));
		array_push($services,Array('GOGS', 3000, 'Self Hosted Git Service'));
		array_push($services,Array('PaperMC', 25565, 'Minecraft Server'));
		array_push($services,Array('NZBGet', 6789, 'Metadata Tool'));
		array_push($services,Array('HTPC Manager', 8085, ''));
		array_push($services,Array('I2P', 7657, 'P2P Internet'));
		array_push($services,Array('YACY', 8090, 'P2P Search Engine'));
		array_push($services,Array('FOLDING@HOME', 7396, 'P2P Protein Folding'));
		array_push($services,Array('IPFS', 5003, 'P2P File Transfer'));
		array_push($services,Array('Ur Backup', 55414, 'Backup Server'));
		array_push($services,Array('GITEA', 3000, 'Git Server'));
		array_push($services,Array('SYNCTHING', 3000, 'File Sync Server'));
		array_push($services,Array('Vault Warden', 8001, 'Unoffical Bitwarden Pass Manager'));
		array_push($services,Array('Ubooquity', 2039, 'Ebook Mediaserver'));
		array_push($services,Array('Komga', 2037, 'Comic/Manga Mediaserver'));
		array_push($services,Array('Spotify Connect Web', 4000, 'Spotify Web Player'));
		array_push($services,Array('Jellyfin', 8097, 'Mediaserver'));
		array_push($services,Array('NaviDrome', 4533, 'Music Player'));
		array_push($services,Array('Snapcast', 1780, 'Home Speaker Sync Server'));
		array_push($services,Array('Koel', 8003, 'Music Player'));
		array_push($services,Array('Tautulli', 8181, 'Plex Monitoring'));
		array_push($services,Array('Plex', 32400, 'Mediaserver'));
		array_push($services,Array('Emby', 8096, 'Mediaserver'));
		array_push($services,Array('ReadyMedia', 8200, 'DLNA/UPnP'));
		array_push($services,Array('Logitech Media Server', 9000, 'Media Server'));
		array_push($services,Array('Mopidy', 6680, 'Music Player'));
		array_push($services,Array('myMPD', 1333, 'Music Player'));
		array_push($services,Array('ympd', 1337, 'Music Player'));
		//array_push($services,Array('Unbound', 53, 'DNS Server'));

		return $services;
	}
}
###############################################################################
if( ! function_exists("availablePathServicesArray")){
	function availablePathServicesArray(){
		# Return the services that can be detected by server paths
		// take port number and service name and generate a index then generate a series of links
		$pathServices = Array();
		array_push($pathServices,Array('SMOKEPING', '/smokeping/', 'Graph Ping Times'));
		array_push($pathServices,Array('RPi Cam', '/rpicam/', 'Webcam'));
		array_push($pathServices,Array('O!MPD', '/ompd/', 'Music Player'));
		array_push($pathServices,Array('airsonic', '/airsonic/', 'Mediaserver'));
		array_push($pathServices,Array('Ampache', '/ampache/', 'Mediaserver/Player'));
		array_push($pathServices,Array('Wordpress', '/wordpress/', 'Blog'));
		array_push($pathServices,Array('SFPG', '/gallery/', 'Single File PHP Gallery'));
		array_push($pathServices,Array('BAIKAL', '/baikal/html', 'CalDAV + CardDAV'));
		array_push($pathServices,Array('PHPBB', '/phpbb/', 'Forum'));
		array_push($pathServices,Array('FreshRSS', '/freshrss/', 'RSS Reader'));
		array_push($pathServices,Array('Linux Dash', '/linuxdash/app/', 'System Monitor'));
		array_push($pathServices,Array('PhpSysInfo', '/phpsysinfo/', 'System Information'));
		# pi hole uses a generic /admin/ path which may be used by other stuff
		array_push($pathServices,Array('Pi-hole', '/admin/', 'System Information'));

		return $pathServices;
	}
}
###############################################################################
if( ! function_exists("serverPathServicesCount")){
	function serverPathServicesCount(){
		# The count of discovered path services on the system.
		$totalServiceCount=0;
		foreach(availablePathServicesArray() as $serviceData){
			if (is_file($serviceData[1])){
				$totalServiceCount += 1;
			}
		}
		return $totalServiceCount;
	}
}
###############################################################################
if( ! function_exists("serverServicesCount")){
	function serverServicesCount(){
		# The count of discovered port services on the system.
		$totalServiceCount=0;
		foreach(availableServicesArray() as $serviceData){
			if (checkPort($serviceData[1])){
				$totalServiceCount += 1;
			}
		}
		foreach(availablePathServicesArray() as $serviceData){
			if (checkServerPath($serviceData[1])){
				$totalServiceCount += 1;
			}
		}
		return $totalServiceCount;
	}
}
###############################################################################
if( ! function_exists("appendCacheFile")){
	function appendCacheFile($cacheFilePath,$cacheData="",$tabs=0){
		# Add to the end of a cache file.
		// add tabs to front of line to be appended to file
		for($index=0;$index<$tabs;$index++){
			$cacheData="\t".$cacheData;
		}
		appendFile($cacheFilePath,$cacheData."\n");
		echo $cacheData;
	}
}
###############################################################################
if( ! function_exists("drawServicesWidget")){
	function drawServicesWidget(){
		# Draw the available services widget in HTML
		$locationSum=md5($_SERVER["HTTP_HOST"]);
		$cacheFile=$_SERVER["DOCUMENT_ROOT"]."/web_cache/widget_services_$locationSum.index";
		if (file_exists($cacheFile)){
			# 3600 seconds = 1 hour = 60 * 60
			# 24 hours
			if ( ( time() - filemtime($cacheFile) ) > (24 * 3600)){
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
			# ignore user aborts after the cacheing has begun
			ignore_user_abort(true);

			# set the max execution time to 15 minutes
			# additional searches will display the results found by this running process
			set_time_limit(900);

			if (serverServicesCount() <= 1){
				# write the cache file to avoid running the code again for cache delay
				appendCacheFile($cacheFile,"<!-- No Server Services Found -->");
				# return false to exit and avoid running the empty loops
				return false;
			}
			if (! is_dir("/var/cache/2web/qrCodes/$locationSum/")){
				mkdir("/var/cache/2web/qrCodes/$locationSum/");
			}
			// draw services widget
			foreach(availableServicesArray() as $serviceData){
				if (checkPort($serviceData[1])){
					$serviceLink="http://".$_SERVER['HTTP_HOST'].":".$serviceData[1];
					$qrSum=md5($serviceLink);
					if ( ! file_exists("/var/cache/2web/qrCodes/".$locationSum."/".$qrSum.".cfg") ){
						# set qr code to be generated
						file_put_contents("/var/cache/2web/qrCodes/".$locationSum."/".$qrSum."-lnk.cfg",$serviceLink);
						file_put_contents("/var/cache/2web/qrCodes/".$locationSum."/".$qrSum."-srv.cfg",$serviceData[0].",".$serviceData[2]);
					}
				}
			}
			foreach(availablePathServicesArray() as $serviceData){
				if (checkServerPath($serviceData[1])){
					$serviceLink="http://".$_SERVER['HTTP_HOST'].$serviceData[1];
					$qrSum=md5($serviceLink);
					if ( ! file_exists("/var/cache/2web/qrCodes/".$locationSum."/".$qrSum.".cfg") ){
						# set qr code to be generated
						file_put_contents("/var/cache/2web/qrCodes/".$locationSum."/".$qrSum."-lnk.cfg",$serviceLink);
						file_put_contents("/var/cache/2web/qrCodes/".$locationSum."/".$qrSum."-srv.cfg",$serviceData[0].",".$serviceData[2]);
					}
				}
			}
			ignore_user_abort(false);
		}
		# load the cached page from previous cache time or current cache
		$cacheFileHandle = fopen($cacheFile,"r");
		while( ! feof($cacheFileHandle)){
			# send a line of the cache file
			echo fgets($cacheFileHandle);
		}
	}
}
###############################################################################
if( ! function_exists("checkServices")){
	function checkServices(){
		# Check available port services and write HTML
		$services=availableServicesArray();
		################################################################################
		# build the table entries for active ports
		################################################################################
		foreach($services as $serviceData){
			if (checkPort($serviceData[1])){
				echo "	<tr class='titleCard'>";
				echo "		<td>$serviceData[0]</td>";
				echo "		<td>$serviceData[1]</td>";
				echo "		<td>";
				echo "			<a id='$serviceData[0]' href='http://".gethostname().".local:$serviceData[1]'>http://".gethostname().".local:$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>";
				echo "			<a id='$serviceData[0]' href='http://".gethostname().":$serviceData[1]'>http://".gethostname().":$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>";
				echo "			<a id='$serviceData[0]' href='http://localhost:$serviceData[1]'>http://localhost:$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>";
				echo "			<a id='$serviceData[0]' href='http://".$_SERVER['HTTP_HOST'].":$serviceData[1]'>http://".$_SERVER['HTTP_HOST'].":$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>$serviceData[2]</td>";
				echo "	</tr>";
			}
		}
	}
}
################################################################################
if( ! function_exists("checkPathServices")){
	function checkPathServices(){
		# Check available path services and write HTML
		$services=availablePathServicesArray();
		################################################################################
		# build the table entries for active ports
		################################################################################
		foreach($services as $serviceData){
			if (checkServerPath($serviceData[1])){
				echo "	<tr id='$serviceData[0]' class='titleCard'>";
				echo "		<td>$serviceData[0]</td>";
				echo "		<td>$serviceData[1]</td>";
				echo "		<td>";
				echo "			<a href='http://".gethostname().".local$serviceData[1]'>http://".gethostname().".local$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>";
				echo "			<a href='http://".gethostname()."$serviceData[1]'>http://".gethostname()."$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>";
				echo "			<a href='http://localhost$serviceData[1]'>http://localhost$serviceData[1]</a>";
				echo "		</td>";
				echo "		<td>";
				echo "			<a href='http://".$_SERVER['HTTP_HOST'].$serviceData[1]."'>http://".$_SERVER['HTTP_HOST'].$serviceData[1]."</a>";
				echo "		</td>";
				echo "		<td>$serviceData[2]</td>";
				echo "	</tr>";
			}
		}
	}
}
################################################################################
if( ! function_exists("drawPlaylistButton")){
	function drawPlaylistButton($activeFilter,$filterName,$buttonText){
		# check if the playlist index exists
		if (file_exists($filterName.".index")){
			if (checkFilePathPermissions("_".$filterName.".index")){
				# check the file has more than 2 entries
				if (count(file("$filterName.index")) > 0){
					if ($activeFilter == $filterName){
						#echo "<a id='activeButton' class='activeButton' href='?filter=$filterName'>$buttonText</a>\n";
						echo "<a class='activeButton' href='?filter=$filterName'>$buttonText</a>\n";
					}else{
						echo "<a class='button' href='?filter=$filterName#activeButton'>$buttonText</a>\n";
					}
				}
			}
		}
	}
}
################################################################################
if( ! function_exists("SQLdrawPlaylistButton")){
	function SQLdrawPlaylistButton($activeFilter,$filterName,$buttonText){
		# Draw a button based on the SQL database information for playlist filters
		if (file_exists($_SERVER['DOCUMENT_ROOT']."/data.db")){
			# load database
			$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/data.db");
			# set the timeout to 1 minute since most webbrowsers timeout loading before this
			$databaseObj->busyTimeout(60000);
			# get the list of tables in the sql database
			$result = $databaseObj->query("select name from sqlite_master where type='table';");
			$dataResults=Array();
			# check if the table exists in the sql database
			while($row = $result->fetchArray()){
				# add each row to the array
				array_push($dataResults,$row['name']);
			}
			if (checkFilePathPermissions("_".$filterName.".index")){
				if (in_array("_".$filterName, $dataResults)){
					# if the button is the active filter change the css
					if ($activeFilter == $filterName){
						#echo "<a id='activeButton' class='activeButton' href='?filter=$filterName'>$buttonText</a>\n";
						echo "<a class='activeButton' href='?filter=$filterName'>$buttonText</a>\n";
					}else{
						echo "<a class='button' href='?filter=$filterName#activeButton'>$buttonText</a>\n";
					}
				}
			}
		}
	}
}
################################################################################
if( ! function_exists("createViewsDatabase")){
	function createViewsDatabase($timeout=60000){
		# Creates the view count database if it does not already exist
		// if no database file exists create one
		if (! file_exists($_SERVER['DOCUMENT_ROOT']."/views.db")){
			# load database
			$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");

			# set the timeout to 1 minute since most webbrowsers timeout loading before this
			$databaseObj->busyTimeout(60000);

			# get the list of tables in the sql database
			$databaseObj->query("PRAGMA journal_mode=WAL;");
			$databaseObj->query("PRAGMA wal_autocheckpoint=20;");
			$databaseObj->query("create table view_count(url text primary key,views int);");
			$databaseObj->query("create table error_count(url text primary key,views int);");
		}
	}
}
################################################################################
if( ! function_exists("appendFile")){
	function appendFile($filePath,$data){
		# Append content to a file
		$fileObject=fopen($filePath,"a");
		fwrite($fileObject,$data);
	}
}
################################################################################
if( ! function_exists("recursiveScan")){
	function recursiveScan($directoryPath){
		# recursiveScan($directoryPath)
		#
		# Recursively scan a directory and all subdirectories and return an array of the full paths to all discovered files.
		#
		# - $directoryPath
		#   - This is required or the function will fail
		#   - Use a relative or absolute system path
		# - $maxDepth
		#   - A interger that determines how deep into the directories the scan should go
		# - $currentDepth
		#   - Do not use this, this is used by the function internally
		// scan the directory
		$foundPaths = scandir($directoryPath);
		# remove the up and current paths
		$foundPaths = array_diff($foundPaths,Array('..','.'));
		$finalFoundLinks = Array();
		# for each found directory list
		foreach( $foundPaths as $foundPath){
			# create the full directory path
			$fullDirPath = $directoryPath.$foundPath."/";

			# try to fix the full found path
			if (is_dir($fullDirPath)){
				$fullFoundPath = $fullDirPath;
			}else{
				$fullFoundPath = $directoryPath.$foundPath;
			}

			# Check if recursion is needed to find search directory
			if (is_dir($fullFoundPath)){
				# add the results of the recursive scan to the output
				$finalFoundLinks = array_merge($finalFoundLinks, recursiveScan($fullFoundPath));
			}else{
				# if a file add the file to the return value
				$finalFoundLinks = array_merge($finalFoundLinks, Array($fullFoundPath));
			}
		}
		return $finalFoundLinks;
	}
}
################################################################################
if( ! function_exists("sortPathsByDate")){
	function sortPathsByDate($finalFoundLinks,$subPath=""){
		# sortPathsByDate($arrayOfPaths,$subPath)
		#
		# The array of paths will be scanned and sorted by the modification time of
		# the paths
		#
		# The subPath is not required
		#
		# The subPath will be added to the paths and that combined path will be used
		# to get the modification time used for sorting the orignal list of paths

		# sort the files by mtime
		$sortedList=Array();
		# sort the link list by modification date
		foreach($finalFoundLinks as $sourceFile){
			# get the timestamp for the file modification date
			$tempTimeStamp=lstat($sourceFile.$subPath)["mtime"];
			# check if the timestamp exists in the sorted array
			if(! array_key_exists($tempTimeStamp,$sortedList)){
				# the time stamp array does not yet exist, create it
				$sortedList[$tempTimeStamp]=Array();
			}
			# append the new entry to the array storing files in that timestamp
			$sortedList[$tempTimeStamp]=array_merge($sortedList[$tempTimeStamp],Array($sourceFile));
		}
		ksort($sortedList);
		# clear the final found links for the sort process to remerge them
		$finalFoundLinks=Array();
		# after the ksort sorts the arrays of files by time, remerge the arrays in order
		# - this must be done in order to keep the order correct for files created in the same secondS
		foreach($sortedList as $fileList){
			$finalFoundLinks=array_merge($finalFoundLinks,$fileList);
		}
		# reverse the link list to order newest to oldest by default
		$finalFoundLinks=array_reverse($finalFoundLinks);

		return $finalFoundLinks;
	}
}
################################################################################
if( ! function_exists("filterPathsByMime")){
	function filterPathsByMime($finalFoundLinks, $filterMimeType){
		#filterPathsByMime($finalFoundLinks, $filterMimeType)
		#
		# filter an array of paths by a mime type
		#
		# - $filterMimeType
		#   - Can take any mime type from unix system mime types
		#   - Examples
		#     - video/mp4
		#     - text/x-php
		#   - Uses the mime_content_type() function in PHP to determine type

		# filter output by mimetype if a mimetype is given in the function call
		# sort the files by mtime
		$filteredList=array();
		foreach($finalFoundLinks as $filePath){
			# check the file mime type to see if it should be in the list
			if (mime_content_type($filePath) == $filterMimeType){
				# merge into the filtered list files with the correct mime type
				$filteredList=array_merge($filteredList,Array($filePath));
			}
		}
		return $filteredList;
	}
}
################################################################################
if( ! function_exists("popPath")){
	function popPath($sourceFile){
		return basename($sourceFile);
	}
}
################################################################################
if( ! function_exists("debug")){
	function debug($message){
		# debug($message)
		#
		# Write debug info if debug key is in the GET data
		if (array_key_exists("debug",$_GET)){
			echo "[DEBUG]: ".$message."<br>";
			ob_flush();
			flush();
			return true;
		}else{
			return false;
		}
	}
}
########################################################################
if( ! function_exists("redirect")){
	function redirect($url){
		# redirect($url)
		#
		# Send the user to a temporary redirect at a given URL
		// temporary redirect
		header('Location: '.$url,true,302);
		exit();
		die();
	}
}
########################################################################
if( ! function_exists("noscriptRefresh")){
	function noscriptRefresh($seconds=10){
		# reload a page only if javascript is disabled
		echo "<noscript>\n";
		# add the noscript page refresh
		echo "	<meta http-equiv='refresh' content='$seconds'>\n";
		# draw the notitication spinner
		echo "	<div id='notification'>";
		echo "		<div id='spinRight'>";
		echo "			🗘";
		echo "		</div>";
		echo "	</div>";
		#echo "	<style>\n";
		# also display the global spinners
		#echo "		.globalPulse,.globalSpinner{\n";
		#echo "			visibility: visible !important;\n";
		#echo "		}\n";
		#echo "	</style>\n";
		echo "</noscript>\n";
	}
}
########################################################################
if( ! function_exists("reloadPage")){
	function reloadPage($delaySeconds=10){
		# reloadPage($delaySeconds=10){
		#
		# Reload a webpage after a delay with javascript or meta refresh if scripts are disabled
		echo "<script>\n";
		# pause any video playback
		echo "	pauseVideo();\n";
		# start the delayed page reload
		echo "	delayedRefresh($delaySeconds);\n";
		# show the spinner to indicate activity to the user
		echo "	showSpinner();\n";
		# 🗘 ⁝ ⸫ 🟃 🟂 ⚙️ 🟕 🟗
		echo "	notify(\"🗘\",".(1000 * $delaySeconds).",\"spinRight\");\n";
		echo "</script>\n";
		noscriptRefresh($delaySeconds);
	}
}
########################################################################
if( ! function_exists("cleanPostInput")){
	function cleanPostInput(){
		# Cleanup post data values to remove shell commands injected, This is a security measure
		// clean the input of the global post values
		foreach( array_keys($_POST) as $postKey ){
			# escape all post keys given to admin script to prevent breaking out of shell commands when used
			#$_POST[$postKey] = escapeshellcmd($_POST[$postKey]);
			$_POST[$postKey] = str_replace(";","；",$_POST[$postKey]);
			$_POST[$postKey] = str_replace("|","｜",$_POST[$postKey]);
			$_POST[$postKey] = str_replace("\n"," ",$_POST[$postKey]);
			#$_POST[$postKey] = cleanText($_POST[$postKey]);
			# replace double quotes with single quotes
			#$_POST[$postKey] = str_replace('"',"'",$_POST[$postKey]);
		}
	}
}
########################################################################
if( ! function_exists("cleanGetInput")){
	function cleanGetInput(){
		# Cleanup get data values to remove shell commands injected, This is a security measure
		// clean the input of the global post values
		foreach( array_keys($_GET) as $getKey ){
			# escape all post keys given to admin script to prevent breaking out of shell commands when used
			#$_GET[$getKey] = escapeshellcmd($_GET[$getKey]);
			$_GET[$getKey] = str_replace(";","；",$_GET[$getKey]);
			$_GET[$getKey] = str_replace("|","｜",$_GET[$getKey]);
			$_GET[$getKey] = str_replace("\n"," ",$_GET[$getKey]);
			#$_GET[$getKey] = cleanText($_GET[$getKey]);
			# replace double quotes with single quotes
			#$_GET[$getKey] = str_replace('"',"'",$_GET[$getKey]);
		}
	}
}
########################################################################
if( ! function_exists("errorBanner")){
	function errorBanner($message,$returnText=false){
		# Draw the html error banner to show on the page to the user a error message.
		$outputText = "<div class='errorBanner'>\n";
		$outputText .= "<hr>\n";
		$outputText .= $message."<br>\n";
		$outputText .= "<hr>\n";
		$outputText .= "</div>\n";
		if ($returnText){
			return $outputText;
		}else{
			echo $outputText;
		}
	}
}

########################################################################
if( ! function_exists("startSession")){
	function startSession(){
		# load a new session with the 2web custom session management
		# - this will avoid the debian/php complex session management
		if (! isset($_SESSION)){
			# load the minutes and convert into seconds
			if (file_exists("/etc/2web/loginTimeoutMinutes.cfg")){
				$timeOutMinutes = file_get_contents("/etc/2web/loginTimeoutMinutes.cfg");
				$timeOutMinutes = (int)$timeOutMinutes;
				$timeOutMinutes = ( ($timeOutMinutes * 60));
			}else{
				file_put_contents("/etc/2web/loginTimeoutMinutes.cfg","30");
				$timeOutMinutes = 0;
			}
			if (file_exists("/etc/2web/loginTimeoutHours.cfg")){
				# load the hours and convert into seconds
				$timeOutHours = file_get_contents("/etc/2web/loginTimeoutHours.cfg");
				$timeOutHours = (int)$timeOutHours;
				$timeOutHours = (60 * ($timeOutHours * 60));
			}else{
				file_put_contents("/etc/2web/loginTimeoutHours.cfg","1");
				$timeOutHours = 0;
			}
			#
			$totalTimeout=( $timeOutHours + $timeOutMinutes );

			# set the session timeout and then start the session
			#ini_set('session.gc_maxlifetime', $totalTimeout );

			# Setup session variables
			# - set the session timeout and then start the session
			$sessionVariables=[
				"save_path" => "/var/cache/2web/sessions",
				"cookie_lifetime" => $totalTimeout,
				"gc_maxlifetime" => $totalTimeout
			];
			#
			#session_set_cookie_params($totalTimeout,"/");
			# the session does not yet exist so start the session
			# - this is only for users that are not logged in
			session_start($sessionVariables);
		}
		# check if the session needs to be regenerated and a new cookie sent
		if (isset($_SESSION["lastActionTime"])){
			# regenerate the session id every 10 minutes (600 seconds)
			if( ( $_SERVER["REQUEST_TIME"] - $_SESSION["lastActionTime"] ) > 600){
				$sessionMessage=" Server will regenerate the hash used to identify the user. A new cookie will be sent to the user over the established session to update the session with a new ID.";
				if (isset($_SESSION["user"])){
					addToLog("ADMIN","Session Update","Regenerate Session Cookie for user '".$_SESSION["user"]."'.".$sessionMessage);
				}else{
					addToLog("ADMIN","Session Update","Regenerate Session Cookie for non logged in user agent.'".$_SERVER["HTTP_USER_AGENT"]."'".$sessionMessage);
				}
				# Regenerate the session id and cookie for login session
				# - delete the old session
				session_regenerate_id(true);
				# update the session update timeout
				$_SESSION["lastActionTime"]=$_SERVER["REQUEST_TIME"];
			}
		}else{
			addToLog("DEBUG","Session Timeout","No Last Action Time Set");
			# update the session update timeout
			$_SESSION["lastActionTime"]=$_SERVER["REQUEST_TIME"];
		}
	}
}
########################################################################
if( ! function_exists("sessionSetValue")){
	function sessionSetValue($indexKey,$storedValue){
		# store a value in the session data and set a timeout for the value
		#
		# - This function can not store a value of null because the sessionGetValue()
		#   return value will return null if no stored value could be retrieved
		#
		# - Default timeout is high because it will mostly effect users who are not
		#   modifying these values
		#
		# - Timeout defaults to 600 seconds (10 Minutes)

		# the session does not yet exist so start the session
		# - this is only for users that are not logged in
		startSession();
		#addToLog("DEBUG","Session Set Value",$indexKey."_value = ".var_export($storedValue,true));
		# set the timestamp
		$_SESSION[$indexKey."_timeStamp"]=time();
		# store the value itself
		$_SESSION[$indexKey."_value"]=$storedValue;
	}
}
########################################################################
if( ! function_exists("sessionGetValue")){
	function sessionGetValue($indexKey){
		# load a value stored in the session data
		#
		# - This will return null if the value could not be loaded
		#

		# store a value in the session
		startSession();
		if (array_key_exists($indexKey."_value",$_SESSION)){
			# the value is stored check the value timeout
			if (array_key_exists($indexKey."_timeStamp",$_SESSION)){
				# load up the timestamp
				$timeStamp=$_SESSION[$indexKey."_timeStamp"];
				# load up the timeout
				$timeOut=600;
				# the timestamp exists check the value of the timestamp
				if ((time() - $timeStamp) < $timeOut){
					# the cached data is not to old so load the data

					# return the stored value
					return $_SESSION[$indexKey."_value"];
				}
			}
		}
		# the data could not be got, so fail
		return null;
	}
}
########################################################################
if( ! function_exists("sessionRemoveValue")){
	function sessionRemoveValue($indexKey){
		# remove a value stored in the session data

		# remove a value stored in the session
		startSession();
		# set the timestamp to 0 to force re checking the data
		$_SESSION[$indexKey."_timeStamp"] = 0;

		#if (isset($_SESSION[$indexKey."_value"])){
		#	unset($_SESSION[$indexKey."_value"]);
		#}
		#if (isset($_SESSION[$indexKey."_timeStamp"])){
		#	$_SESSION[$indexKey."_timeStamp"] = 0;
		#	#unset($_SESSION[$indexKey."_timeStamp"]);
		#}
		return true;
	}
}
########################################################################
if( ! function_exists("yesNoCfgCheck")){
	function yesNoCfgCheck($configPath){
		# This function checks the value of a configuration file and returns true if the file is set to yes.
		# If no config file exists a new one will be created.
		#
		# RETURN BOOL
		# check if the config is cached in ram

		$configPathSum = $configPath;

		#check session data
		$storedValue=sessionGetValue($configPathSum);
		# if a value is returned
		if($storedValue !== null){
			#addToLog("DEBUG","SessionValue","Loading stored session value for '".$configPathSum."' as ".var_export($storedValue,true));
			# load the stored value found
			# - exit the function here so the value will not be loaded from disk
			return $storedValue;
		}
		#addToLog("DEBUG","SessionValue","Could not find stored value, loading file'".$configPath."'");
		# check if the config file exists
		if (file_exists($configPath)){
			$selected=file_get_contents($configPath);
			$selected=strtolower($selected);
			#addToLog("DEBUG","SessionValue","File contents found '".$selected."'");
			if ($selected == "yes"){
				# cache the calculated value
				#addToLog("DEBUG","SessionValue","set value '".$configPathSum."' to 'true'");
				sessionSetValue($configPathSum,true);
				#addToLog("DEBUG","SessionValue","Verify value '".$configPathSum."' is now set to '".sessionGetValue($configPathSum)."'");
				# the config file is set to yes
				return true;
			}else{
				# cache the calculated value
				#addToLog("DEBUG","SessionValue","set value '".$configPathSum."' to 'true'");
				sessionSetValue($configPathSum,false);
				#addToLog("DEBUG","SessionValue","Verify value '".$configPathSum."' is now set to '".sessionGetValue($configPathSum)."'");
				# the config is set to anything other than yes it is false
				return false;
			}
		}else{
			# cache the calculated value
			#addToLog("DEBUG","SessionValue","set value '".$configPathSum."' to 'true'");
			sessionSetValue($configPathSum,false);
			#addToLog("DEBUG","SessionValue","Verify value '".$configPathSum."' is now set to '".sessionGetValue($configPathSum)."'");
			# no file exists return false and create default no config
			file_put_contents($configPath , "no");
			return false;
		}
	}
}
########################################################################
if( ! function_exists("checkFilePathPermissions")){
	function checkFilePathPermissions($filePath){
		# check for permissions to read the file
		$drawResult=false;
		# check for permissions for specific results
		if (stripos($filePath,"_all") !== false){
			# check for all module permissions
			$modules=listModules();
			# check user has all permissions for groups
			foreach($modules as $module){
				if(! requireGroup($module, false)){
					$drawResult=false;
					break;
				}else{
					# mark output to be shown
					$drawResult=true;
				}
			}
		}else if (stripos($filePath,"_wiki") !== false){
			# check group permissions
			if (requireGroup("wiki2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_comics") !== false){
			if (requireGroup("comic2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_episodes") !== false){
			if (requireGroup("nfo2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_shows") !== false){
			if (requireGroup("nfo2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_movies") !== false){
			if (requireGroup("nfo2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_channels") !== false){
			if (requireGroup("iptv2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_graphs") !== false){
			if (requireGroup("graph2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_music") !== false){
			if (requireGroup("music2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_tracks") !== false){
			if (requireGroup("music2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_artists") !== false){
			if (requireGroup("music2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_albums") !== false){
			if (requireGroup("music2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_portal") !== false){
			if (requireGroup("portal2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_weather") !== false){
			if (requireGroup("weather2web",false)){
				$drawResult=true;
			}
		}else if (stripos($filePath,"_applications") !== false){
			if (requireGroup("php2web",false)){
				$drawResult=true;
			}
		}else{
			$drawResult=true;
		}
		return $drawResult;
	}
}
########################################################################
if( ! function_exists("requireGroup")){
	function requireGroup($group, $redirect=true){
		# requireGroup($group, $redirect=true)
		#
		# Check the logged in user has permissions for the group given or if the group is unlocked
		#
		# - Redirect user to the login page automatically if they do not have permission.
		#
		// try to load a session in the current window
		startSession();
		# check if the current user has admin privileges
		if (array_key_exists("admin",$_SESSION)){
			# admin privileges override all other group permissions
			if ($_SESSION["admin"]){
				# update the session timestamp
				$_SESSION["login_time_stamp"] = time();
				# if the user is logged in and has admin permissions, eject them from the group auth process
				return true;
			}
		}
		# check if the group itself is locked
		if (array_key_exists($group."_locked",$_SESSION)){
			# the array key is set
			if (! $_SESSION[$group."_locked"]){
				# eject from the lock check and load the page without login
				$_SESSION["login_time_stamp"] = time();
				return true;
			}
		}else{
			if ($group == "admin"){
				# always lock the admin group
				$_SESSION[$group."_locked"]=true;
			}else{
				# the session has not yet been checked
				# check if the group being checked requires a login
				if (file_exists("/etc/2web/lockedGroups/".$group.".cfg")){
					# if the group is unlocked let anyone enter and store the status in the current session
					$_SESSION[$group."_locked"]=true;
				}else{
					# the group is not locked so set the session value
					$_SESSION[$group."_locked"]=false;
					# eject from the lock check and load the page without login
					return true;
				}
			}
		}
		# check the user has logged in successfully
		if (array_key_exists($group,$_SESSION)){
			if ($_SESSION[$group]){
				# if the user is logged in and has permissions to access the group, eject them from the group auth process
				return true;
			}else{
				if ($redirect){
					# if the user is not logged in redirect to the login page
					redirect("https://".$_SERVER["HTTP_HOST"]."/login.php?failedLogin=true&noPermission=".$group."&redirect=https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
				}else{
					return false;
				}
			}
		}else{
			if ($redirect){
				# if the user is not logged in redirect to the login page
				redirect("https://".$_SERVER["HTTP_HOST"]."/login.php?failedLogin=true&redirect=https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
			}else{
				return false;
			}
		}
	}
}
########################################################################
if( ! function_exists("findWordGroups")){
	function findWordGroups($searchQuery){
		# create an array from a string of the word groups
		$foundWords=explode(" ",$searchQuery);
		$outputData=Array();
		for($index=0;$index<count($foundWords);$index++){
			if ($index+1 < count($foundWords)){
				$outputData=array_merge( $outputData,Array($foundWords[$index]." ".$foundWords[$index+1]) );
			}
		}
		return $outputData;
	}
}
########################################################################
if( ! function_exists("loadSearchIndexResults")){
	function loadSearchIndexResults($searchQuery,$filter="all"){
		# loadSearchIndexResults($searchQuery,$filter="all")
		#
		# Output the results of a search query to the search index
		#
		# - The filter allows only results from a specific subsection of the 2web website
		#   - comics
		#   - movies
		#   - shows
		#

		# get the orignal query
		$ogQuery=$searchQuery;
		$searchQuery=cleanText($searchQuery);
		$searchQuery=spaceCleanedText($searchQuery);
		# split the query into word groups
		$wordGroups=findWordGroups($searchQuery);
		# explode the search query into an array
		$searchQuery=explode(" ",$searchQuery);
		# add the word groups
		$searchQuery=array_merge($searchQuery,$wordGroups);
		#
		$allIndex="";
		#
		foreach ($searchQuery as $word){
			$cleanWord=strtolower("$word");
			if (is_readable("/var/cache/2web/generated/searchIndex/$cleanWord.index")){
				# check if the filter is enabled
				if($filter=="all"){
					# load the index for each word
					$allIndex .= file_get_contents("/var/cache/2web/generated/searchIndex/$cleanWord.index");
				}else{
					$tempIndex="";
					# load the word file
					$tempFileIndex = file("/var/cache/2web/generated/searchIndex/$cleanWord.index");
					# filter file by the secondary web path
					# - comics
					# - movies
					# - shows
					foreach ($tempFileIndex as $tempIndexEntry){
						if(stripos($tempIndexEntry,"/var/cache/2web/web/$filter/") !== false){
							$tempIndex .= $tempIndexEntry;
						}
					}
					# add the filtered temp index to the all index
					$allIndex .= $tempIndex;
				}
			}
		}
		# split the index into a array
		$allIndex=explode("\n",$allIndex);
		# sort the all index
		sort($allIndex);
		# count the unique items
		$countValues=array_count_values($allIndex);
		arsort($countValues);
		# limit output to 40 results
		$countValues=array_slice($countValues,0,40);
		#	output the index
		$outputFound=false;
		$outputText="";
		foreach (array_keys($countValues) as $word){
			if(is_readable($word)){
				$outputFound=true;
				$outputText .= file_get_contents($word)."\n";
			}
		}
		# only draw the widget if there is output
		if($outputFound){
			echo "<div class='titleCard'>\n";
			if($filter=="all"){
				echo "<h1>Related Media</h1>\n";
			}else{
				echo "<h1>Related ".ucfirst($filter)."</h1>\n";
			}
			echo "<div class='listCard'>\n";
			echo $outputText;
			# create the search link
			echo "<a class='indexSeries' href='/search.php?q=$ogQuery'>";
			echo "	<h2 class='moreEpisodesLinkIcon'>";
			echo "		🔍";
			echo "	</h2>";
			echo "	Full ";
			echo "	List";
			echo "</a>";
			#
			echo "</div>\n";
			echo "</div>\n";
		}
	}
}
########################################################################
if( ! function_exists("requireAdmin")){
	function requireAdmin(){
		# check permissions for admin
		requireGroup("admin");
	}
}
########################################################################
if( ! function_exists("getIdentity")){
	function getIdentity(){
		# Get the user agent and the ip address of the currently connected user
		#
		# RETURN STRING
		return $_SERVER["HTTP_USER_AGENT"]."; ".$_SERVER["REMOTE_ADDR"].";";
	}
}
########################################################################
if( ! function_exists("getStat")){
	function getStat($totalPath, $label, $showEmpty=false){
		# get a value from a file and print a stat on a webpage
		#
		# RETURN OUTPUT
		if (file_exists($totalPath)){
			if (filesize($totalPath) > 0){
				$total = file_get_contents($totalPath);
			}else{
				$total=0;
			}
		}else{
			$total=0;
		}
		# format the value if it is only numbers
		if (is_numeric($total)){
			# add commas
			$total=number_format($total);
		}
		# if the value is not a number
		if (is_string($total)){
			# and it is blank
			# - remove newlines before checking if blank
			if (str_replace("\n","",$total) == ""){
				# set the value to zero
				$total=0;
			}
		}
		#
		$drawValue=false;
		# only draw stats that are greater than zero
		if ($total > 0){
			$drawValue=true;
		}else{
			# do not draw empty values by default, but allow a overide with $showEmpty
			if ($showEmpty){
				$drawValue=true;
			}
		}
		if ( $total == 0 ){
			#
			$total="∅";
		}
		if ($drawValue){
			echo "		<span class='singleStat'>\n";
			echo "			<span class='singleStatLabel'>$label</span>\n";
			echo "			<span class='singleStatValue'>$total</span>\n";
			echo "		</span>\n";
		}
	}
}
########################################################################
if( ! function_exists("buildYesNoCfgButton")){
	function buildYesNoCfgButton($configPath,$buttonText,$buttonName){
		# Check if a yes/no config file is enabled and draw a button to set it to the opposite value
		#
		# RETURN FILES
		$enabledIcon="🟢";
		$disabledIcon="◯";
		#
		if (file_exists($configPath)){
			$selected=file_get_contents($configPath);
			if ($selected == "yes"){
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='$buttonName' value='no'>$enabledIcon Disable $buttonText</button>\n";
				echo "	</form>\n";
			}else{
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='$buttonName' value='yes'>$disabledIcon Enable $buttonText</button>\n";
				echo "	</form>\n";
			}
		}else{
			echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
			echo "	<button class='button' type='submit' name='$buttonName' value='yes'>$disabledIcon Enable $buttonText</button>\n";
			echo "	</form>\n";
		}
	}
}
########################################################################
if( ! function_exists("timeToHuman")){
	function timeToHuman($timestamp){
		# remove newlines in timestamp
		$timestamp=str_replace("\n","",$timestamp);

		$yearInSeconds=(((60 * 60) * 24) * 365);
		$dayInSeconds=((60 * 60) * 24);
		$hourInSeconds=(60 * 60);
		$minuteInSeconds=(60);

		$yearsPassed=0;
		$daysPassed=0;
		$hoursPassed=0;
		$minutesPassed=0;

		if ($timestamp > $yearInSeconds ){
			$yearsPassed=floor( $timestamp / $yearInSeconds );
			$timestamp -= $yearsPassed * $yearInSeconds;
			if ($yearsPassed == 1){
				echo "$yearsPassed year ";
			}else if ($yearsPassed > 1){
				echo "$yearsPassed years ";
			}
		}

		if ($timestamp > $dayInSeconds ){
			$daysPassed=floor( $timestamp / $dayInSeconds );
			$timestamp -= $daysPassed * $dayInSeconds;
			if ($daysPassed == 1){
				echo "$daysPassed day ";
			}else if ($daysPassed > 1){
				echo "$daysPassed days ";
			}
			if ($yearsPassed > 0){
				return true;
			}
		}

		if ($timestamp > $hourInSeconds ){
			$hoursPassed=floor( $timestamp / $hourInSeconds );
			$timestamp -= $hoursPassed * $hourInSeconds;
			if ($hoursPassed == 1){
				echo "$hoursPassed hour ";
			}else if ($hoursPassed > 1){
				echo "$hoursPassed hours ";
			}
			if ($daysPassed > 0){
				return true;
			}
		}

		if ($timestamp > $minuteInSeconds ){
			$minutesPassed=floor( $timestamp / $minuteInSeconds );
			$timestamp -= $minutesPassed * $minuteInSeconds;
			if ($minutesPassed == 1){
				echo "$minutesPassed minute ";
			}else if ($minutesPassed > 1){
				echo "$minutesPassed minutes ";
			}
			if ($hoursPassed > 0){
				return true;
			}
		}
		# write out the remaining seconds
		if ($timestamp == 1){
			echo "$timestamp second ";
		}else{
			echo "$timestamp seconds ";
		}
	}
}
########################################################################
if( ! function_exists("timeElapsedToHuman")){
	function timeElapsedToHuman($timestamp,$postText=" ago"){
		# remove newlines in timestamp
		# - second argument is post time text default=" ago"
		$timestamp=str_replace("\n","",$timestamp);

		$currentTime=time();
		$elapsedTime=( $currentTime - $timestamp );

		# convert the elapsed time to human
		timeToHuman($elapsedTime);
		echo $postText;
	}
}
########################################################################
if( ! function_exists("getDateStat")){
	function getDateStat($totalPath, $label){
		# get a date from a file stored as seconds since the unix epoch and print a stat on a webpage
		#
		# RETURN OUTPUT
		if (file_exists($totalPath)){
			$total = file_get_contents($totalPath);
		}else{
			$total= 0;
		}
		# only draw stats that are greater than zero
		if ($total > 0){
			echo "<span class='singleStat'>";
			echo "<span class='singleStatLabel'>$label</span>";
			echo "<span class='singleStatValue'>";
			timeElapsedToHuman($total);
			echo "</span>";
			echo "</span>\n";
		}
	}
}
########################################################################
if( ! function_exists("addToLog")){
	function addToLog($errorType, $errorDescription, $errorDetails){
		# Add a log entry
		#
		# RETURN FILES

		# set the module name to admin
		$moduleName="WEB";
		# create identifier date to organize the data, this is really accurate
		# - use microtime to generate log entries at the time the function is executed
		# - do not use request_time_float as this will make all log entries added by a php script to be under the same log entry
		$logIdentifier=(string)microtime(true);
		$logDate=date("d\/m\/y");
		$logTime=date("h:i:s");
		#
		$logDescription=str_replace("'", "''", $errorDescription);
		#
		#echo "error details = $errorDetails <br>\n";

		$logDetails=str_replace("'", "''", "$errorDetails");

		# load database
		$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/log/log.db");
		# set the timeout to 1 minute since most webbrowsers timeout loading before this
		$databaseObj->busyTimeout(60000);
		# get the list of tables in the sql database
		$result = $databaseObj->query("select name from sqlite_master where type='table';");
		# check if the database has been created yet
		if ( ! file_exists($_SERVER['DOCUMENT_ROOT']."/log/log.db")){
			# setup the base function of the database
			$databaseObj->query("PRAGMA journal_mode=WAL;");
			$databaseObj->query("PRAGMA wal_autocheckpoint=20;");
			# create the database table structure
			$databaseObj->query("create table log(logIdentifier text primary key,module,type,description,details,date,time);");
		}
		# add the log entry
		$databaseObj->query("replace into log values('$logIdentifier','$moduleName','$errorType','$logDescription','$logDetails','$logDate','$logTime');");

		#echo ("replace into log values('$logIdentifier','$moduleName','$errorType','$logDescription','$logDetails','$logDate','$logTime');<br>\n");

		# clear up memory of database file
		$databaseObj->close();
		unset($databaseObj);
	}
}
################################################################################
if( ! function_exists("uncleanText")){
	function uncleanText($inputText){
		# clean up the text for use in web urls and directory paths
		# - uses fullwidth versions of caracters that interfere with URLs
		$uncleanedText="$inputText";
		# add grave accents
		$uncleanedText=str_replace("｀","`",$uncleanedText);
		# add hash marks
		$uncleanedText=str_replace("＃","#",$uncleanedText);
		# add single quotes
		$uncleanedText=str_replace("＇","'",$uncleanedText);
		################################################################################
		# convert symbols that cause issues to full width versions of those characters
		################################################################################
		# convert question marks into wide question marks so they look
		# the same but wide question marks do not break URLS
		$uncleanedText=str_replace("？","?",$uncleanedText);
		# cleanup ampersands, they break URLs
		$uncleanedText=str_replace("＆","&",$uncleanedText);
		# cleanup @ symbols, they break URLs
		$uncleanedText=str_replace("＠","@",$uncleanedText);
		# remove percent signs they break print functions
		$uncleanedText=str_replace("％","%",$uncleanedText);
		# hyphens break grep searches
		$uncleanedText=str_replace("－","-",$uncleanedText);
		# exclamation marks can also cause problems
		$uncleanedText=str_replace("！","!",$uncleanedText);
		# plus signs are used in URLs so they must be changed
		$uncleanedText=str_replace("＋","+",$uncleanedText);
		# remove forward slashes, they will break all paths
		$uncleanedText=str_replace("／","/",$uncleanedText);
		$uncleanedText=str_replace("＼","\\",$uncleanedText);
		# remove pipes
		$uncleanedText=str_replace("｜","|",$uncleanedText);
		# remove semicolons
		$uncleanedText=str_replace("；",";",$uncleanedText);
		# remove dollar signs
		$uncleanedText=str_replace("＄","$",$uncleanedText);
		# greater/less than
		$uncleanedText=str_replace("＜","<",$uncleanedText);
		$uncleanedText=str_replace("＞",">",$uncleanedText);
		# brackets
		$uncleanedText=str_replace("［","[",$uncleanedText);
		$uncleanedText=str_replace("］","]",$uncleanedText);
		# curly brackets
		$uncleanedText=str_replace("｛","{",$uncleanedText);
		$uncleanedText=str_replace("｝","}",$uncleanedText);
		#
		$uncleanedText=str_replace("＊","*",$uncleanedText);
		# print the cleaned up text
		return "$uncleanedText";
	}
}
########################################################################
if( ! function_exists("spaceCleanedText")){
	function spaceCleanedText($cleanedText){
		# clean up the text for use in web urls and directory paths
		# - uses fullwidth versions of caracters that interfere with URLs
		$characters=Array("．",",","｀","＃","＇","？","＆","＠","％","－","！","＋","／","＼","｜","；","：","＄","＂","＇","｛","｝","(",")","［","］","＊","＜","＞");
		$spacedText="";
		foreach($characters as $specialCharacter){
			$cleanedText=str_replace("$specialCharacter"," $specialCharacter ","$cleanedText");
		}
		# squeeze double spaces into single spaces
		$cleanedText=str_replace("  "," ","$cleanedText");
		# print the cleaned up text
		return "$cleanedText";
	}
}
########################################################################
if( ! function_exists("cleanText")){
	function cleanText($inputText){
		# clean up the text for use in web urls and directory paths
		# - uses fullwidth versions of caracters that interfere with URLs
		$cleanedText="$inputText";
		# convert html entities into the characters
		#cleanedText=$(echo -n "$cleanedText" | recode html )
		$cleanedText=str_replace("&amp;","＆",$cleanedText);
		$cleanedText=str_replace("&quot;","＇",$cleanedText);
		$cleanedText=str_replace("&apos;","＇",$cleanedText);
		$cleanedText=str_replace("&lt;","<",$cleanedText);
		$cleanedText=str_replace("&gt;",">",$cleanedText);
		# remove grave accents
		$cleanedText=str_replace("`","｀",$cleanedText);
		# remove hash marks as they break URLS
		$cleanedText=str_replace("#","＃",$cleanedText);
		# remove single quotes
		$cleanedText=str_replace("'","＇",$cleanedText);
		# convert underscores into spaces
		$cleanedText=str_replace("_"," ",$cleanedText);
		################################################################################
		# convert symbols that cause issues to full width versions of those characters
		################################################################################
		# convert question marks into wide question marks so they look
		# the same but wide question marks do not break URLS
		$cleanedText=str_replace("?","？",$cleanedText);
		# cleanup ampersands, they break URLs
		$cleanedText=str_replace("&","＆",$cleanedText);
		# cleanup @ symbols, they break URLs
		$cleanedText=str_replace("@","＠",$cleanedText);
		# remove percent signs they break print functions
		$cleanedText=str_replace("%","％",$cleanedText);
		# hyphens break grep searches
		$cleanedText=str_replace("-","－",$cleanedText);
		# exclamation marks can also cause problems
		$cleanedText=str_replace("!","！",$cleanedText);
		# plus signs are used in URLs so they must be changed
		$cleanedText=str_replace("+","＋",$cleanedText);
		# remove forward slashes, they will break all paths
		$cleanedText=str_replace("/","／",$cleanedText);
		$cleanedText=str_replace("\\","＼",$cleanedText);
		# remove pipes
		$cleanedText=str_replace("|","｜",$cleanedText);
		# remove semicolons
		$cleanedText=str_replace(";","；",$cleanedText);
		#
		$cleanedText=str_replace(":","：",$cleanedText);
		# remove dollar signs
		$cleanedText=str_replace("$","＄",$cleanedText);
		#
		$cleanedText=str_replace("*","＊",$cleanedText);
		# brackets
		$cleanedText=str_replace("[","［",$cleanedText);
		$cleanedText=str_replace("]","］",$cleanedText);
		# curly brackets
		$cleanedText=str_replace("{","｛",$cleanedText);
		$cleanedText=str_replace("}","｝",$cleanedText);
		# greater/less than
		$cleanedText=str_replace("<","＜",$cleanedText);
		$cleanedText=str_replace(">","＞",$cleanedText);
		# squeeze double spaces into single spaces
		$cleanedText=str_replace("  "," ",$cleanedText);
		# print the cleaned up text
		return "$cleanedText";
	}
}
################################################################################
if( ! function_exists("linkFile")){
	function linkFile($targetPath,$symlinkPath){
		# create a symlink if it does not exist
		if (is_file($targetPath)){
			if (is_link($symlinkPath)){
				# the symlink path already exists
				return false;
			}else if (is_file($symlinkPath)){
				# the symlink path already exists and is a file
				return false;
			}else{
				# everything is good create the symlink
				symlink($targetPath,$symlinkPath);
				return true;
			}
		}else{
			# the target is not a file
			return false;
		}
	}
}
################################################################################
if( ! function_exists("verifyCacheFile")){
	function verifyCacheFile($storagePath,$videoLink){
		# verifyCacheFile($storagePath,$videoLink)
		#
		# - Return true if the file is verified
		# - Return false if the file could not be verified
		$isVerified=true;
		if (! file_exists($storagePath."verified.cfg")){
			# check the file downloaded correctly by comparing the json data file length with local file length
			if (file_exists($storagePath."video.mp3") or file_exists($storagePath."video.mp4")){
				if(file_exists($storagePath."video.mp3")){
					$foundExt=".mp3";
				}else if (file_exists($storagePath."video.mp4")){
					$foundExt=".mp4";
				}
				# if the file was last modified more than 90 seconds ago
				# - checks should wait for caching to complete in order to properly read file metadata
				if ( ( time() - filemtime($storagePath."video$foundExt") ) > 90 ){
					#
					if ( (file_exists($storagePath."video.info.json")) || (file_exists($storagePath."video.mp3.info.json")) || (file_exists($storagePath."video.mp4.info.json")) ){
						#
						if (file_exists($storagePath."video.info.json")){
							$jsonInfoPath=$storagePath."video.info.json";
						}else if(file_exists($storagePath."video.mp3.info.json")){
							# fix the path
							linkFile(($storagePath."video.mp3.info.json"), ($storagePath."video.info.json"));
							$jsonInfoPath=$storagePath."video.info.json";
						}else if(file_exists($storagePath."video.mp4.info.json")){
							# fix the path
							linkFile(($storagePath."video.mp4.info.json"), ($storagePath."video.info.json"));
							$jsonInfoPath=$storagePath."video.info.json";
						}

						# fix wierd thumbnail paths
						if(file_exists($storagePath."video.mp3.png")){
							linkFile(($storagePath."video.mp3.png"), ($storagePath."video.png"));
						}else if(file_exists($storagePath."video.mp4.png")){
							linkFile(($storagePath."video.mp4.png"), ($storagePath."video.png"));
						}

						# The json downloaded from the remote and stored by the resolver
						$remoteJson = json_decode(file_get_contents($jsonInfoPath));

						# check the remote json contains a duration value
						# - not all websites support the duration metadata value
						if(property_exists($remoteJson, "duration")){
							#
							$remoteJsonValue=(int)$remoteJson->duration;
							# reduce the value to add variance for rounding errors
							# - Depending on the json data and how it was generated the rounding of time may go up or down by one
							$remoteJsonValue-=5;
							# the json data from reading the current downloaded file
							$localJson = json_decode(shell_exec("mediainfo --output=JSON ".$storagePath."video$foundExt"));
							$localJsonValue= (int)$localJson->media->track[0]->Duration;
							# compare the lenght in the remote json to the local file, including the variance above
							if ($localJsonValue >= $remoteJsonValue){
								addToLog("DOWNLOAD","Attempt to Verify Track Length","Track was verified for link '$videoLink'");
								debug("The video is completely downloaded and has been verified to have downloaded correctly...");
								# if the length is correct the file is verified to have downloaded completely, mark the file as verified
								touch($storagePath."verified.cfg");
							}else{
								addToLog("DOWNLOAD","Attempt to Verify Track Length","Track was NOT verified because the length was incorrect<br>\nLink = '$videoLink'<br>\nLOCAL='".$localJsonValue."' >= REMOTE='".$remoteJsonValue."'<br>\n");
								debug("The video was corrupt and could not be verified...");
								# re cache the corrupted file
								$isVerified=false;
							}
						}else{
							# the duration can not be used to verify the value so a attempt will be made to generate a thumbnail
							addToLog("DOWNLOAD","Track Verification Issue","Track has no track duration metadata. Running Mime type check...");
							$tempMimeType=mime_content_type($storagePath."video$foundExt");
							if (($tempMimeType == "audio/mpeg") and ($foundExt == ".mp3")){
								addToLog("DEBUG","Attempt to Verify Track","filesize='".filesize($storagePath."video".$foundExt)."'");
								if (filesize($storagePath."video$foundExt") == 0){
									addToLog("ERROR","Attempt to Verify Track","File was empty '$storagePath"."video$foundExt' for '$videoLink'");
									# remove the blank file
									unlink($storagePath."video$foundExt");
								}else{
									if ( ( time() - filemtime($storagePath."video$fileExt" ) ) > 90 ){
										addToLog("DOWNLOAD","Attempt to Verify Track","Track was verified for link '$videoLink'");
										debug("The audio is completely downloaded and has been verified to have downloaded correctly...");
										# mark the file as verified
										touch($storagePath."verified.cfg");
									}else{
										# verification is in progress
										addToLog("DOWNLOAD","Attempt to Verify Track","Track was NOT verified because the video is still being written to disk.<br>\nLink = '$videoLink'<br>\n");
									}
								}
							}else if (($tempMimeType == "video/mp4") and ($foundExt == ".mp4")){
								addToLog("DEBUG","Attempt to Verify Track","filesize='".filesize($storagePath."video".$foundExt)."'");
								if (filesize($storagePath."video$foundExt") == 0){
									addToLog("ERROR","Attempt to Verify Track","File was empty '$storagePath"."video$foundExt' for '$videoLink'");
									# remove the blank file
									unlink($storagePath."video$foundExt");
								}else{
									# only verify the file if is has not been modified for more than 90 seconds
									if ( ( time() - filemtime($storagePath."video$fileExt" ) ) > 90 ){
										addToLog("DOWNLOAD","Attempt to Verify Track","Track was verified for link '$videoLink'");
										debug("The video is completely downloaded and has been verified to have downloaded correctly...");
										# mark the file as verified
										touch($storagePath."verified.cfg");
									}else{
										# verification is in progress
										addToLog("DOWNLOAD","Attempt to Verify Track","Track was NOT verified because the video is still being written to disk.<br>\nLink = '$videoLink'<br>\n");
									}
								}
							}else{
								addToLog("DOWNLOAD","Attempt to Verify Track","Track was NOT verified because the mime type was incorrect<br>\nLink = '$videoLink'<br>\n LOCAL='$tempMimeType' != 'video/mp4' <br>\n");
								addToLog("DOWNLOAD","Attempt to Verify Track","Track was NOT verified because the mime type was incorrect<br>\nLink = '$videoLink'<br>\n LOCAL='$tempMimeType' != 'audio/mpeg' <br>\n");
								debug("The video was corrupt and could not be verified...");
								# re cache the corrupted file
								$isVerified=false;
							}
						}
					}else{
						addToLog("DOWNLOAD","Attempt to Verify Track Length","Track was NOT verified because no .info.json data could be found to verify length with.\n<br>The link given was '$videoLink'");
						# the mp4 was found but the json was not
						$isVerified=false;
					}
				}
			}
		}
		if($isVerified){
			sleep(1);
		}
		return $isVerified;
	}
}
################################################################################
if( ! function_exists("bytesToHuman")){
	function bytesToHuman($bytes){
		# bytesToHuman($bytes)
		#
		# Take a interger value of bytes and convert it to human readable string.
		#

		if ( $bytes <= 999 ){
			# bytes
			return ( ( $bytes ) . "B");
		}else if ( $bytes <= 999999 ){
			# kilobytes
			return ( number_format( ( $bytes / 1000 ) , 0) . "K");
		}else if ( $bytes <= 999999999){
			# megabytes
			return ( number_format( ( $bytes / 1000000 ) , 1) . "M");
		}else if ( $bytes <= 999999999999){
			# gigabytes
			return ( number_format( ( $bytes / 1000000000 ) , 1) . "G");
		}else if ( $bytes <= 999999999999999){
			# terabytes
			return ( number_format( ( $bytes / 1000000000000 ) , 1) . "T");
		}else if ( $bytes <= 999999999999999999999){
			# petabytes
			return ( number_format( ( $bytes / 1000000000000000 ) , 1) . "P");
		}else{
			# if the filesize is to large show it in petabytes
			# petabytes
			return ( number_format( ( $bytes / 1000000000000000 ) , 1) . "P");
		}
	}
}
################################################################################
if( ! function_exists("clear")){
	function clear(){
		# send all text output to the browser
		flush();
		ob_flush();
	}
}
################################################################################
if( ! function_exists("cacheCheck")){
	function cacheCheck($filePath,$cacheDays=1){
		# Check if a file is more than x days old and needs updated.
		#
		# $1 = filePath : Path to the cached file to check for.
		# $2 = cacheDays : The max age in days of the file
		#
		# - Will return true if the files does not exist or the file is older than $cacheDays
		# - This should activate code that changes the file
		#
		# RETURN BOOL
		return cacheCheckMin($filePath,( $cacheDays * ( 60 * 24 ) ) );
	}
}
################################################################################
if( ! function_exists("cacheCheckMin")){
	function cacheCheckMin($filePath,$cacheMinutes=60){
		# Check if a file is more than x minutes old and needs updated.
		#
		# $1 = filePath : Path to the cached file to check for.
		# $2 = cacheMinutes : The max age in minutes of the file
		#
		# - Will return true if the files does not exist or the file is older than $cacheMinutes
		# - This should activate code that changes the file
		#
		# RETURN BOOL
		if (file_exists($filePath)){
			if (time()-filemtime($filePath) > ( $cacheMinutes * ( 60 ) ) ){
				# update the cached file
				return true;
			}else{
				# read from the already cached file
				return false;
			}
		}else{
			# write the file if it does not exist
			return true;
		}
	}
}
################################################################################
if( ! function_exists("isTranscodeEnabled")){
	function isTranscodeEnabled(){
		$doTranscode = False;
		# check if the transcode is enabled
		if (file_exists("/etc/2web/transcodeForWebpages.cfg")){
			$selected=file_get_contents("/etc/2web/transcodeForWebpages.cfg");
			if ($selected == "yes"){
				$doTranscode = True;
			}
		}
		return $doTranscode;
	}
}
#################################################################################
?>
