<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/nfo2web.js'></script>
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
		$filterCommand = "ls -t1 /var/cache/nfo2web/web/new/movie_*.index";
	}else if ($filterType == 'episodes'){
		echo "<h2>Recently added Episodes</h2>";
		$filterCommand = "ls -t1 /var/cache/nfo2web/web/new/episode_*.index";
	}else{
		echo "<h2>Recently added Media</h2>";
		$filterCommand = "ls -t1 /var/cache/nfo2web/web/new/*.index";
	}
}else{
	echo "<h2>Recently added Media</h2>";
	$filterCommand = "ls -t1 /var/cache/nfo2web/web/new/*.index";
}
?>

<a class='button' href='?filter=episodes'>Episodes</a>
<a class='button' href='?filter=movies'>Movies</a>
<a class='button' href='?filter=all'>All</a>
</div>


<div class='settingListCard'>
<?php
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
				flush();
				ob_flush();
			}
		}
	}
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
