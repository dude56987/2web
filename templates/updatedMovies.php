<?php
$cacheFile="updatedMovies.index";
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
	//$sourceFiles = explode("\n",shell_exec("ls -rt1 /var/cache/nfo2web/web/movies/*/movies.index"));
	$sourceFiles = explode("\n",shell_exec("find '/var/cache/2web/web/new/' -name 'movie_*.index' -printf '%T+ %p\n' | sort | cut -d' ' -f2-"));
	// reverse the time sort
	$sourceFiles = array_reverse($sourceFiles);
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
						fwrite($fileObj,"<h1>Updated Movies</h1>");
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
		// create a final link to the full new list
		fwrite($fileObj,"<a class='indexSeries' href='/new/index.php?filter=movies'>");
		fwrite($fileObj,"Full");
		fwrite($fileObj,"<br>");
		fwrite($fileObj,"List");
		fwrite($fileObj,"<br>");
		fwrite($fileObj,"&#128220;");
		fwrite($fileObj,"</a>");
		fwrite($fileObj,"</div>");
		fwrite($fileObj,"</div>");
	}
	// close the file
	fclose($fileObj);
}
// read the file that is cached
echo file_get_contents($cacheFile);
// flush the buffer
flush();
ob_flush();
?>

