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
################################################################################
if( ! function_exists("detectEnabledStatus")){
	function detectEnabledStatus($filePath){
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
#################################################################################
if( ! function_exists("formatText")){
	function formatText($text,$tabs=0,$newline="\n"){
		$returnValue="";
		# write a line with a number of tabs and a custom newline character to variable
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
		# write a line with a number of tabs and a custom newline character to page
		echo formatText($text,$tabs,$newline);
	}
}
################################################################################
if( ! function_exists("logPrint")){
	function logPrint($logMessage){
		echo "<script>";
		echo "console.log('".$logMessage."');";
		echo "</script>\n";
	}
}
################################################################################
if( ! function_exists("listAllIndex")){
	function listAllIndex($indexPath,$sortMethod="forward"){
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
		# add tabs to front of line to be appended to file
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
		$locationSum=md5($_SERVER["HTTP_HOST"]);
		$cacheFile=$_SERVER["DOCUMENT_ROOT"]."/web_cache/widget_services_$locationSum.index";
		if (file_exists($cacheFile)){
			# 3600 seconds = 1 hour = 60 * 60
			# 10 hours
			if ( ( time() - filemtime($cacheFile) ) > (10 * 3600)){
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
				appendCacheFile($cacheFile,"<!-- No Active Services Discovered -->");
				# return false to exit and avoid running the empty loops
				return false;
			}

			// draw services widget
			appendCacheFile($cacheFile,"<div class='titleCard'>");
			appendCacheFile($cacheFile,"<h2>Server Services</h2>");
			appendCacheFile($cacheFile,"<div class='listCard'>");

			foreach(availableServicesArray() as $serviceData){
				if (checkPort($serviceData[1])){
					$serviceLink="http://".$_SERVER['HTTP_HOST'].":".$serviceData[1];
					$qrSum=md5($serviceLink);
					if ( ! file_exists("/var/cache/2web/qrCodes/".$qrSum.".cfg") ){
						# set qr code to be generated
						file_put_contents("/var/cache/2web/qrCodes/".$qrSum.".cfg",$serviceLink);
					}
					#draw link
					appendCacheFile($cacheFile,"<a class='showPageEpisode' target='_BLANK' href='$serviceLink'>");
					if (file_exists("/var/cache/2web/web/thumbnails/$qrSum-qr.png")){
						appendCacheFile($cacheFile,"<img src='/thumbnails/$qrSum-qr.png' />");
					}
					appendCacheFile($cacheFile,"<div class='showIndexNumbers'>".$serviceData[0]."</div>");
					appendCacheFile($cacheFile,"$serviceData[2]");
					appendCacheFile($cacheFile,"</a>");
				}
			}
			foreach(availablePathServicesArray() as $serviceData){
				if (checkServerPath($serviceData[1])){
					$serviceLink="http://".$_SERVER['HTTP_HOST'].$serviceData[1];
					$qrSum=md5($serviceLink);
					if ( ! file_exists("/var/cache/2web/qrCodes/".$qrSum.".cfg") ){
						# set qr code to be generated
						file_put_contents("/var/cache/2web/qrCodes/".$qrSum.".cfg",$serviceLink);
					}
					appendCacheFile($cacheFile,"<a class='showPageEpisode' target='_BLANK' href='$serviceLink'>");
					if (file_exists("/var/cache/2web/web/thumbnails/$qrSum-qr.png")){
						appendCacheFile($cacheFile,"<img src='/thumbnails/$qrSum-qr.png' />");
					}
					appendCacheFile($cacheFile,"<div class='showIndexNumbers'>".$serviceData[0]."</div>");
					appendCacheFile($cacheFile,"$serviceData[2]");
					appendCacheFile($cacheFile,"</a>");
				}
			}
			appendCacheFile($cacheFile,"</div>");
			appendCacheFile($cacheFile,"</div>");
			ignore_user_abort(false);
		}else{
			# load the cached page
			$cacheFileHandle = fopen($cacheFile,"r");
			while( ! feof($cacheFileHandle)){
				# send a line of the cache file
				echo fgets($cacheFileHandle);
			}
		}
	}
}
###############################################################################
if( ! function_exists("checkServices")){
	function checkServices(){
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
			if (count(file("$filterName.index")) > 2){
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
if( ! function_exists("appendFile")){
	function appendFile($filePath,$data){
		$fileObject=fopen($filePath,"a");
		fwrite($fileObject,$data);
	}
}
################################################################################
if( ! function_exists("recursiveScan")){
	function recursiveScan($directoryPath){
		# scan the directory
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
if( ! function_exists("popPath")){
	function popPath($sourceFile){
		$fileName = explode("/",$sourceFile);
		$fileName = array_reverse($fileName);
		$fileName = $fileName[0];
		return $fileName;
	}
}
################################################################################
if( ! function_exists("debug")){
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
}
########################################################################
?>
