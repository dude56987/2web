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
	<h2>Playlists</h2>
	<div class='listCard'>
			<a class='activeButton' href='/new/'>
			📜 NEW
		</a>
		<a class='button' href='/random/'>
			🔀 RANDOM
		</a>
	</div>
</div>
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

drawPlaylistButton($filterType,"episodes","🎞️ Episodes");
drawPlaylistButton($filterType,"shows","📺 shows");
drawPlaylistButton($filterType,"movies","🎥 Movies");
drawPlaylistButton($filterType,"comics","📚 Comics");
drawPlaylistButton($filterType,"music","🎧 Music");
drawPlaylistButton($filterType,"albums","💿 Albums");
drawPlaylistButton($filterType,"artists","🎤 Artists");
drawPlaylistButton($filterType,"tracks","🎵 Tracks");
drawPlaylistButton($filterType,"graphs","📊 Graphs");

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
