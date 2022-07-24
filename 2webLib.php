<?PHP
// contains common functions used by 2web php scripts
function drawPosterWidget($filterType, $random=False){
	ini_set('display_errors', 1);
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
function detectEnabledStatus($filePath){
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
?>
