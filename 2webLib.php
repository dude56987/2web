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
		# check for group permissions in filter type
		if ($filterType == "graphs"){
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
						if ($counter >= 40){
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
							fwrite($fileObj,"<a class='button indexIconLink' href='/random/index.php?filter=$filterType'>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List ");
							fwrite($fileObj,"🔀");
							fwrite($fileObj,"</a>");
						}else{
							// create a final link to the full new list
							fwrite($fileObj,"<a class='button indexIconLink' href='/new/index.php?filter=$filterType'>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"List ");
							fwrite($fileObj,"📜");
							fwrite($fileObj,"</a>");
						}
					}else if ($linkType == "episode"){
						# show page episode
						if ($random){
							// create a final link to the full new list
							fwrite($fileObj,"<a class='showPageEpisode moreEpisodesLink' href='/random/index.php?filter=$filterType'>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"List ");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"🔀");
							fwrite($fileObj,"</a>");
						}else{
							// create a final link to the full new list
							fwrite($fileObj,"<a class='showPageEpisode moreEpisodesLink' href='/new/index.php?filter=$filterType'>");
							fwrite($fileObj,"Full ");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"List ");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"📜");
							fwrite($fileObj,"</a>");
						}
					}else{
						if ($random){
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/random/index.php?filter=$filterType'>");
							fwrite($fileObj,"Full");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"List");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"🔀");
							fwrite($fileObj,"</a>");
						}else{
							// create a final link to the full new list
							fwrite($fileObj,"<a class='indexSeries' href='/new/index.php?filter=$filterType'>");
							fwrite($fileObj,"Full");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"List");
							fwrite($fileObj,"<br>");
							fwrite($fileObj,"📜");
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
if( ! function_exists("detectEnabledStatus")){
	function detectEnabledStatus($filePath){
		# Return true if given module is enabled
		# Used for testing module enabled or disabled status
		# return true if a $filePath exists and contains the text "enabled"
		$filePath = "/etc/2web/mod_status/".$filePath.".cfg";
		if (file_exists($filePath)){
			if (file_get_contents($filePath) == "enabled"){
				return True;
			}else{
				return False;
			}
		}else{
			// no config exists so mark it as disabled
			return False;
		}
	}
}
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
if( ! function_exists("formatEcho")){
	function formatEcho($text,$tabs=0,$newline="\n"){
		# Write a line with a number of tabs and a custom newline character to page
		echo formatText($text,$tabs,$newline);
	}
}
if( ! function_exists("logPrint")){
	function logPrint($logMessage){
		# Print data into the console log of a page with javascript
		echo "<script>";
		echo "console.log('".$logMessage."');";
		echo "</script>\n";
	}
}
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
if( ! function_exists("displayIndexWithPages")){
	function displayIndexWithPages($indexFilePath,$emptyMessage="",$maxItemsPerPage=45,$sortMethod="forward"){
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
					//echo $emptyMessage;
				}
			}else{
				listIndexPage($indexFilePath,$_GET['page'],$maxItemsPerPage,$sortMethod);
			}
		}else{
			// no shows have been loaded yet
			$_GET['page'] = 1;
			if( ! listIndexPage($indexFilePath,1,$maxItemsPerPage,$sortMethod)){
				//echo $emptyMessage;
			}
		}
		# build the page buttons
		# get the index size by reading the index file and counting
		$fileCountHandle=fopen($indexFilePath,'r');
		$fileItemCount=0;
		while(! feof($fileCountHandle)){
			$fileItemCount += 1;
			fgets($fileCountHandle);
		}

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
			echo "<div class='titleCard'>";

			echo "<div class='listCard'>";
			if (is_numeric($_GET["page"]) and ( $_GET["page"] > 1 )){
				# build the left button for pages
				echo "<a class='button' href='?page=".($_GET["page"] - 1).$otherVars."'>";
				echo "⇦ Back";
				echo "</a>";
			}

			# check status for the special all page
			echo "<a class='button' href='?page=all$otherVars'>";
			echo "∞";
			echo "</a>";
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
					}else if (($currentPageNumber < ($_GET['page'] + 5)) && ($currentPageNumber > ($_GET['page'] - 5))){
						$drawPage = True;
						$pageDrawCounter += 1;
						if ($currentPageNumber == $_GET['page']){
							$activePage = True;
						}
					}else{
						if ($currentPageNumber == 2){
							echo "...";
						}else if($currentPageNumber == ($pageCount - 1)){
							echo "...";
						}
					}
				}
				# check if the page number should be drawn
				if ($drawPage){
					# draw the page
					if ($activePage){
						# if the page is active set the class
						echo "<a class='activeButton' href='?page=$currentPageNumber$otherVars'>";
						echo "$currentPageNumber";
						echo "</a>";
					}else{
						echo "<a class='button' href='?page=$currentPageNumber$otherVars'>";
						echo "$currentPageNumber";
						echo "</a>";
					}
				}
			}

			if (is_numeric($_GET["page"]) and ( $_GET["page"] < $pageCount )){
				# build the next button for pages
				echo "<a class='button' href='?page=".($_GET["page"] + 1)."$otherVars'>";
				echo "Next ⇨";
				echo "</a>";
			}

			echo "</div>";
			echo "</div>";
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
		# recursiveScan($directoryPath,$sort="none",$filterMimeType="none",$maxDepth)
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
if( ! function_exists("sortPathsByDate")){
	function sortPathsByDate($finalFoundLinks){
		# sort the files by mtime
		$sortedList=Array();
		# sort the link list by modification date
		foreach($finalFoundLinks as $sourceFile){
			# get the timestamp for the file modification date
			$tempTimeStamp=lstat($sourceFile)["mtime"];
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
		# Pop the path from a absolute path
		$fileName = explode("/",$sourceFile);
		$fileName = array_reverse($fileName);
		$fileName = $fileName[0];
		return $fileName;
	}
}
################################################################################
if( ! function_exists("debug")){
	function debug($message){
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
		# Send the user to a temporary redirect at a given URL
		// temporary redirect
		header('Location: '.$url,true,302);
		exit();
		die();
	}
}
########################################################################
if( ! function_exists("cleanPostInput")){
	function cleanPostInput(){
		# Cleanup post data values to remove shell commands injected, This is a security measure
		// clean the input of the global post values
		foreach( array_keys($_POST) as $postKey ){
			# escape all post keys given to admin script to prevent breaking out of shell commands when used
			$_POST[$postKey] = escapeshellcmd($_POST[$postKey]);
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
			$_GET[$getKey] = escapeshellcmd($_GET[$getKey]);
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
if( ! function_exists("yesNoCfgCheck")){
	function yesNoCfgCheck($configPath){
		# This function checks the value of a configuration file and returns true if the file is set to yes.
		# If no config file exists a new one will be created.
		#
		# RETURN BOOL

		# check if the config file exists
		if (file_exists($configPath)){
			$selected=file_get_contents($configPath);
			$selected=strtolower($selected);
			if ($selected == "yes"){
				# the config file is set to yes
				return true;
			}else{
				# the config is set to anything other than yes it is false
				return false;
			}
		}else{
			# no file exists return false and create default no config
			file_put_contents($configPath , "no");
			return false;
		}
	}
}
########################################################################
if( ! function_exists("requireGroup")){
	function requireGroup($group, $redirect=true){
		# check the logged in user has permissions for the group given or if the group is unlocked
		// try to load a session in the current window
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
			# set the session timeout and then start the session
			ini_set('session.gc_maxlifetime', ( $timeOutHours + $timeOutMinutes ) );
			session_start();
		}
		# check if the group itself is locked
		if (array_key_exists($group."_locked",$_SESSION)){
			# the array key is set
			if (! $_SESSION[$group."_locked"]){
				# eject from the lock check and load the page without login
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
	function getStat($totalPath, $label){
		# get a value from a file and print a stat on a webpage
		#
		# RETURN OUTPUT
		if (file_exists($totalPath)){
			$total = file_get_contents($totalPath);
		}else{
			$total= 0;
		}
		if (is_numeric($total)){
			# add commas
			$total=number_format($total);
		}
		# only draw stats that are greater than zero
		if ($total > 0){
			echo "		<span class='singleStat'>";
			echo "			$label:$total";
			echo "		</span>";
		}
	}
}
########################################################################
if( ! function_exists("buildYesNoCfgButton")){
	function buildYesNoCfgButton($configPath,$buttonText,$buttonName){
		# Check if a yes/no config file is enabled and draw a button to set it to the opposite value
		#
		# RETURN FILES
		if (file_exists($configPath)){
			$selected=file_get_contents($configPath);
			if ($selected == "yes"){
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='$buttonName' value='no'>🟢 Disable $buttonText</button>\n";
				echo "	</form>\n";
			}else{
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='$buttonName' value='yes'>◯ Enable $buttonText</button>\n";
				echo "	</form>\n";
			}
		}else{
			echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
			echo "	<button class='button' type='submit' name='$buttonName' value='yes'>◯ Enable $buttonText</button>\n";
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
			echo "$label:";
			timeElapsedToHuman($total);
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
		$logIdentifier=$_SERVER["REQUEST_TIME_FLOAT"];
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
########################################################################
?>
