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
	echo "<h2>Recently added ".ucfirst($filterType)."</h2>";
}else{
	$filterType="all";
	echo "<h2>Recently added Media</h2>";
}
?>
<div class='listCard'>
<a class='button' href='?filter=all'>📜 All</a>
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
</div>
</div>


<div class='settingListCard'>
<?php
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No $filterType items found!</li>";
$emptyMessage .= "</ul>";
displayIndexWithPages($filterType.".index",$emptyMessage,48,"reverse");
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
