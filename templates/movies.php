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
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
# add the updated movies below the header
drawPosterWidget("movies");
################################################################################
?>
<div class='settingListCard'>
<?php
# store the index path
$indexFilePath="/var/cache/2web/web/movies/movies.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No Movies have been scanned into the libary!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
$emptyMessage .= "<li>Add download links in <a href='/settings/ytdl2nfo.php'>video on demand admin interface</a></li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>
<?php
// add random movies above the footer
drawPosterWidget("movies", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
