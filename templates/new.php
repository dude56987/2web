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
<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>


<div class='titleCard'>
<?php
if (array_key_exists("filter",$_GET)){
	//print_r($_GET);
	$filterType=$_GET['filter'];
	//echo "<p>filter = $filterType</p>";
	if ($filterType == 'movies'){
		echo "<h2>Recently added Movies</h2>";
		//$filterCommand = 'ls -t1 /var/cache/2web/web/new/movie_*.index';
		$filterCommand = "find '/var/cache/2web/web/new/' -name 'movie_*.index' -printf '%T+ %p\n'";
	}else if ($filterType == 'episodes'){
		echo "<h2>Recently added Episodes</h2>";
		//$filterCommand = 'ls -t1 /var/cache/2web/web/new/episode_*.index';
		$filterCommand = "find '/var/cache/2web/web/new/' -name 'episode_*.index' -printf '%T+ %p\n'";
	}else if ($filterType == 'comics'){
		echo "<h2>Recently added Comics</h2>";
		$filterCommand = "find '/var/cache/2web/web/new/' -name 'comic_*.index' -printf '%T+ %p\n'";
	}else{
		echo "<h2>Recently added Media</h2>";
		//$filterCommand = 'ls -t1 /var/cache/2web/web/new/*.index';
		$filterCommand = "find '/var/cache/2web/web/new/' -name '*.index' -printf '%T+ %p\n'";
	}
}else{
	$filterType="all";
	echo "<h2>Recently added Media</h2>";
	//$filterCommand = 'ls -t1 /var/cache/2web/web/new/*.index';
	$filterCommand = "find '/var/cache/2web/web/new/' -name '*.index' -printf '%T+ %p\n'";
}
# sort list based on time
$filterCommand = $filterCommand." | sort | cut -d' ' -f2-";
# limit list to 200 entries
$filterCommand = $filterCommand." | tail -n 500 | tac";
//$filterCommand = $filterCommand." | tac | tail -n 200 | tac";
//echo "<br>$filterCommand<br>";

if (file_exists("$webDirectory/shows/")){
	if (file_exists("$webDirectory/totalShows.index")){
		if ((file_get_contents("$webDirectory/totalShows.index")) > 0){
			echo "<a class='button' href='?filter=episodes'>Episodes</a>";
		}
	}
}

if (file_exists("$webDirectory/movies/")){
	if (file_exists("$webDirectory/totalMovies.index")){
		if ((file_get_contents("$webDirectory/totalMovies.index")) > 0){
			echo "<a class='button' href='?filter=movies'>Movies</a>";
		}
	}
}

if (file_exists("$webDirectory/comics/")){
	if (file_exists("$webDirectory/totalComics.index")){
		if ((file_get_contents("$webDirectory/totalComics.index")) > 0){
			echo "<a class='button' href='?filter=comics'>Comics</a>";
		}
	}
}

?>
<a class='button' href='?filter=all'>All</a>
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
	$sourceFiles = explode("\n",shell_exec($filterCommand));
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
	echo file_get_contents("new_$filterType.index");
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<hr class='topButtonSpace'>
</body>
</html>
