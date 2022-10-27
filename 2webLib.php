<?PHP
################################################################################
// contains common functions used by 2web php scripts
################################################################################
ini_set('display_errors', 1);
################################################################################
if( ! function_exists("drawPosterWidget")){
	function drawPosterWidget($filterType, $random=False){
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
					if ($random){
						// create a final link to the full new list
						fwrite($fileObj,"<a class='indexSeries' href='/random/index.php?filter=$filterType'>");
						fwrite($fileObj,"Full");
						fwrite($fileObj,"<br>");
						fwrite($fileObj,"List");
						fwrite($fileObj,"<br>");
						fwrite($fileObj,"🔀");
						fwrite($fileObj,"</a>");
						fwrite($fileObj,"</div>");
						fwrite($fileObj,"</div>");
					}else{
						// create a final link to the full new list
						fwrite($fileObj,"<a class='indexSeries' href='/new/index.php?filter=$filterType'>");
						fwrite($fileObj,"Full");
						fwrite($fileObj,"<br>");
						fwrite($fileObj,"List");
						fwrite($fileObj,"<br>");
						fwrite($fileObj,"📜");
						fwrite($fileObj,"</a>");
						fwrite($fileObj,"</div>");
						fwrite($fileObj,"</div>");
					}
				}
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
	function displayIndexWithPages($indexFilePath,$emptyMessage="",$maxItemsPerPage=48,$sortMethod="forward"){
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
			echo "<a class='button' href='?page=all$otherVars'>";
			echo "All";
			echo "</a>";
			foreach((range(1,$pageCount)) as $currentPageNumber){
				#echo "\n<br>".$currentPageNumber." == ".$_GET['page']."<br>\n";
				if (array_key_exists("page",$_GET)){
					if ($currentPageNumber == $_GET['page']){
						echo "<a class='activeButton' href='?page=$currentPageNumber$otherVars'>";
					}else{
						echo "<a class='button' href='?page=$currentPageNumber$otherVars'>";
					}
				}else{
					echo "<a class='button' href='?page=$currentPageNumber$otherVars'>";
				}
				echo "$currentPageNumber";
				echo "</a>";
			}
			echo "</div>";
			echo "</div>";
		}
	}
}
################################################################################
?>
