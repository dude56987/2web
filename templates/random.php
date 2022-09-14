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
?>

<div class='titleCard'>
<?php
if (array_key_exists("filter",$_GET)){
	$filterType=$_GET['filter'];
	echo "<h2>Random ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Random Media</h2>";
}
?>
<div class='listCard'>
<?PHP
if (file_exists("$webDirectory/random/shows.index")){
	if (count(file("$webDirectory/random/shows.index")) > 2){
		echo "<a class='button' href='?filter=shows'>📺 shows</a>";
	}
}
if (file_exists("$webDirectory/random/episodes.index")){
	if (count(file("$webDirectory/random/episodes.index")) > 2){
		echo "<a class='button' href='?filter=episodes'>🎞️ Episodes</a>";
	}
}
if (file_exists("$webDirectory/random/movies.index")){
	if (count(file("$webDirectory/random/movies.index")) > 2){
		echo "<a class='button' href='?filter=movies'>🎥 Movies</a>";
	}
}
if (file_exists("$webDirectory/random/comics.index")){
	if (count(file("$webDirectory/random/comics.index")) > 2){
		echo "<a class='button' href='?filter=comics'>📚 Comics</a>";
	}
}
if (file_exists("$webDirectory/random/music.index")){
	if (count(file("$webDirectory/random/music.index")) > 2){
		echo "<a class='button' href='?filter=music'>🎧 Music</a>";
	}
}
if (file_exists("$webDirectory/random/albums.index")){
	if (count(file("$webDirectory/random/albums.index")) > 2){
		echo "<a class='button' href='?filter=albums'>💿 Albums</a>";
	}
}
if (file_exists("$webDirectory/random/artists.index")){
	if (count(file("$webDirectory/random/artists.index")) > 2){
		echo "<a class='button' href='?filter=artists'>🎤 Artists</a>";
	}
}
if (file_exists("$webDirectory/random/tracks.index")){
	if (count(file("$webDirectory/random/tracks.index")) > 2){
		echo "<a class='button' href='?filter=tracks'>🎵 Tracks</a>";
	}
}
if (file_exists("$webDirectory/random/graphs.index")){
	if (count(file("$webDirectory/random/graphs.index")) > 2){
		echo "<a class='button' href='?filter=graphs'>📊 Graphs</a>";
	}
}
?>
<a class='button' href='?filter=all'>📜 All</a>
</div>
</div>


<div class='settingListCard'>
<?php
flush();
ob_flush();

$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/random_$filterType.index";
if (file_exists($cacheFile)){
	if (time()-filemtime($cacheFile) > 30){
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
	$fileHandle = fopen($cacheFile,'w');
	// get a list of all the genetrated index links for the page
	$sourceFiles = file($_SERVER['DOCUMENT_ROOT']."/random/".$filterType.".index", FILE_IGNORE_NEW_LINES);

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
	echo file_get_contents($cacheFile);
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
