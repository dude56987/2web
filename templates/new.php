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
	//print_r($_GET);
	$filterType=$_GET['filter'];
	//echo "<p>filter = $filterType</p>";
	if ($filterType == 'movies'){
		echo "<h2>Recently added Movies</h2>";
	}else if ($filterType == 'episodes'){
		echo "<h2>Recently added Episodes</h2>";
	}else if ($filterType == 'comics'){
		echo "<h2>Recently added Comics</h2>";
	}else if ($filterType == 'shows'){
		echo "<h2>Recently added Shows</h2>";
	}else{
		echo "<h2>Recently added $filterType</h2>";
	}
}else{
	$filterType="all";
	echo "<h2>Recently added Media</h2>";
}
?>
<div class='listCard'>
<?PHP
if (file_exists("$webDirectory/new/shows.index")){
	echo "<a class='button' href='?filter=shows'>📺 shows</a>";
	echo "<a class='button' href='?filter=episodes'>🎞️ Episodes</a>";
}

if (file_exists("$webDirectory/new/movies.index")){
	echo "<a class='button' href='?filter=movies'>🎥 Movies</a>";
}

if (file_exists("$webDirectory/new/comics.index")){
	echo "<a class='button' href='?filter=comics'>📚 Comics</a>";
}

?>
<a class='button' href='?filter=all'>📜 All</a>
</div>
</div>


<div class='settingListCard'>
<?php
flush();
ob_flush();

$cacheFile="new_$filterType.index";
if (file_exists($cacheFile)){
	if (time()-filemtime($cacheFile) > 300){
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
# load the cached file or write a new cached fill
if ($writeFile){
	ignore_user_abort(true);
	$fileHandle = fopen("new_$filterType.index",'w');
	// get a list of all the genetrated index links for the page
	//$sourceFiles = explode("\n",file_get_contents("$filterType.index"));
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
	//fclose($sourceFiles);
	fclose($fileHandle);
	ignore_user_abort(false);
}else{
	//echo file_get_contents("new_$filterType.index");
	# load the cached file
	$fileHandle = fopen("new_$filterType.index",'r');
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
