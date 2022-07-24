<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
# add the search box
?>
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("showPageEpisode")' placeholder='Search...' >
<hr>


<div class='titleCard'>
<?php
if (array_key_exists("filter",$_GET)){
	$filterType=$_GET['filter'];
	echo "<h2>Recently added ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Recently added Media</h2>";
}
?>
<div class='listCard'>
<?PHP
if (file_exists("$webDirectory/new/shows.index")){
	echo "<a class='button' href='?filter=shows'>📺 shows</a>";
}
if (file_exists("$webDirectory/new/episodes.index")){
	echo "<a class='button' href='?filter=episodes'>🎞️ Episodes</a>";
}
if (file_exists("$webDirectory/new/movies.index")){
	echo "<a class='button' href='?filter=movies'>🎥 Movies</a>";
}
if (file_exists("$webDirectory/new/comics.index")){
	echo "<a class='button' href='?filter=comics'>📚 Comics</a>";
}
if (file_exists("$webDirectory/new/music.index")){
	echo "<a class='button' href='?filter=music'>🎧 Music</a>";
}
if (file_exists("$webDirectory/new/albums.index")){
	echo "<a class='button' href='?filter=albums'>💿 Albums</a>";
}
if (file_exists("$webDirectory/new/artists.index")){
	echo "<a class='button' href='?filter=artists'>🎤 Artists</a>";
}
if (file_exists("$webDirectory/new/tracks.index")){
	echo "<a class='button' href='?filter=tracks'>🎵 Tracks</a>";
}
if (file_exists("$webDirectory/new/graphs.index")){
	echo "<a class='button' href='?filter=graphs'>📊 Graphs</a>";
}
?>
<a class='button' href='?filter=all'>📜 All</a>
</div>
</div>


<div class='settingListCard'>
<?php
flush();
ob_flush();

$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/new_$filterType.index";
# if the filter type can be found for this filter
if (file_exists($_SERVER['DOCUMENT_ROOT']."/new/$filterType.index")){
	# make a md5sum of the index file for the picked filter type
	$updateSum=md5(file_get_contents($_SERVER['DOCUMENT_ROOT']."/new/$filterType.index"));
	if (file_exists($cacheFile)){
		$timeDiff=time()-filemtime($cacheFile);
		if ($timeDiff > 300){
			// update the cached file
			echo "<!-- Cache Time limit exceeded -->\n";
			$writeFile=true;

			# write the sum as a comment on the page for debug purposes
			echo "<!-- $updateSum -->\n";
			flush();
			ob_flush();

			if (file_exists($_SERVER['DOCUMENT_ROOT']."/sums/new_$filterType.cfg")){
				# get the existing md5sum from the sums directory
				$existingSum=file_get_contents($_SERVER['DOCUMENT_ROOT']."/sums/new_$filterType.cfg");
				# compare the sums
				if ($updateSum != $existingSum){
					echo "<!-- $existingSum != $updateSum -->\n";
					# sums are diffrent write a new file
					$writeFile=true;
					# disable user abort of update, this will make the page update even if the user leaves the page
					# - the point of no return, this script must run now
					ignore_user_abort(true);
					# write the new sum to the file
					file_put_contents($_SERVER['DOCUMENT_ROOT']."/sums/new_$filterType.cfg", $updateSum);
				}else{
					echo "<!-- $existingSum == $updateSum -->\n";
					# update the modification time on the cachefile to delay the md5sum checking
					touch($cacheFile);
					# the sums match so the cache file does not need updated
					$writeFile=false;
				}
			}else{
				# the file does not even exist
				# - the point of no return, this script must run now
				ignore_user_abort(true);
				$writeFile=true;
				echo "<!-- No Sums to Compare, first run -->\n";
				# write the new sum to the file
				file_put_contents($_SERVER['DOCUMENT_ROOT']."/sums/new_$filterType.cfg", $updateSum);
			}
		}else{
			echo "<!-- Cache Time limit is still in countdown $timeDiff/300 -->\n";
			// read from the already cached file
			$writeFile=false;
		}
	}else{
		# the web cache file does not exist
		$writeFile=true;
		file_put_contents($_SERVER['DOCUMENT_ROOT']."/sums/new_$filterType.cfg", $updateSum);

	}
}else{
	# this means content could not be found for the given filter type
	echo "<div class='inputList'>";
	echo "<ul>";
	echo "<li>No Content could be found for the given filter type '$filterType'</li>";
	echo "<li>If you have administrative privileges you can add content in the settings.</li>";
	echo "</ul>";
	echo "</div>";
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
	echo "</body>";
	echo "</html>";
	exit();
}
# load the cached file or write a new cached fill
if ($writeFile){
	ignore_user_abort(true);
	$fileHandle = fopen($_SERVER['DOCUMENT_ROOT']."/web_cache/new_$filterType.index",'w');

	// get a list of all the genetrated index links for the page
	$sourceFiles = file("$filterType.index", FILE_IGNORE_NEW_LINES);

	// reverse the list to make the ordering correct
	$sourceFiles = array_reverse($sourceFiles);
	// limit the array to 200 items
	array_splice($sourceFiles, 200);
	foreach($sourceFiles as $sourceFile){
		if (file_exists($sourceFile)){
			// read the index entry
			$indexFileHandle = fopen($sourceFile,'r');
			while(!feof($indexFileHandle)){
				// read the index entry
				$data=fgets($indexFileHandle, 4096);
				// write the index entry into the page buffer
				fwrite($fileHandle, "$data");
				echo $data;
				# send data wrote to the page to the browser
				flush();
				ob_flush();
			}
			fclose($indexFileHandle);
		}
	}
	fclose($fileHandle);
	ignore_user_abort(false);
}else{
	# load the cached file
	$fileHandle = fopen($_SERVER['DOCUMENT_ROOT']."/web_cache/new_$filterType.index",'r');
	while(!feof($fileHandle)){
		# send a large frame packet of text from the prebuilt page
		# - this streams the file
		echo fgets($fileHandle,4096);
		# send data
		flush();
		ob_flush();
	}
	fclose($fileHandle);
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
