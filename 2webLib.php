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
	function listAllIndex($indexPath){
		$foundData=false;
		$tempData="";
		#logPrint("loading index file ".$indexPath);
		# if the search index exists
		if ( file_exists( $indexPath ) ){
			#logPrint("index file '$indexPath' is a file reading");
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
						$tempData .= $packetData;
					}
					$foundData = true;
				}
			}
		}
		if ($foundData){
			return array(true,$tempData);
		}else{
			return array(false,$tempData);
		}
	}
}
################################################################################
?>
