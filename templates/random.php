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
		echo "<h2>Random Movies</h2>";
	}else if ($filterType == 'episodes'){
		echo "<h2>Random Episodes</h2>";
	}else if ($filterType == 'comics'){
		echo "<h2>Random Comics</h2>";
	}else if ($filterType == 'shows'){
		echo "<h2>Random Shows</h2>";
	}else{
		echo "<h2>Random $filterType</h2>";
	}
}else{
	$filterType="all";
	echo "<h2>Random Media</h2>";
}
?>
<div class='listCard'>
<?PHP
if (file_exists("$webDirectory/shows/shows.index")){
	echo "<a class='button' href='?filter=shows'>📺 shows</a>";
	echo "<a class='button' href='?filter=episodes'>🎞️ Episodes</a>";
}

if (file_exists("$webDirectory/movies/movies.index")){
	echo "<a class='button' href='?filter=movies'>🎥 Movies</a>";
}

if (file_exists("$webDirectory/comics/comics.index")){
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

$cacheFile="random_$filterType.index";
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
	$fileHandle = fopen($_SERVER['DOCUMENT_ROOT']."/random/".$filterType.".index",'w');
	// get a list of all the genetrated index links for the page
	if ( "$filterType" == "all" ){
		$sourceFiles = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT']."/new/".$filterType.".index"));
	}else if ( "$filterType" == "episodes" ){
		$sourceFiles = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT']."/new/".$filterType.".index"));
	}else{
		$sourceFiles = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT']."/".$filterType."/".$filterType.".index"));
	}

	# remove list duplicates
	$sourceFiles = array_unique($sourceFiles);

	# randomize the playlist items
	shuffle($sourceFiles);

	// limit the array to 200 items
	array_splice($sourceFiles, 200);

	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "$data";
					fwrite($fileHandle, "$data");
					flush();
					ob_flush();
				}
			}
		}
	}
	fclose($fileHandle);
	ignore_user_abort(false);
}else{
	# load the cached file
	echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/random_$filterType.index");
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>