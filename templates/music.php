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
# add the base php libary
include("/usr/share/2web/2webLib.php");
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
drawPosterWidget("music");
################################################################################
?>
<div class='settingListCard'>
<?php
$indexData = listAllIndex("/var/cache/2web/web/music/music.index");
if ($indexData[0]){
	# print the data stored in the index
	echo $indexData[1];
}else{
	// no shows have been loaded yet
	echo "<ul>";
	echo "<li>No Music have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/settings/music.php'>music admin interface</a> to populate this page.</li>";
	echo "</ul>";
}
?>
</div>
<?php
// add random music above the footer
drawPosterWidget("music", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
