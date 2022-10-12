<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
	drawPosterWidget("comics");
?>

<hr>

<div class='settingListCard'>

<?php


# store the index path
$indexFilePath="/var/cache/2web/web/comics/comics.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No Comics Have been scanned into the libary!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/comics.php'>comics admin interface</a> to populate this page.</li>";
$emptyMessage .= "<li>Add download links in <a href='/settings/comicsDL.php'>comics admin interface</a></li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>

<?php
	// add random comics above the footer
	drawPosterWidget("comics", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
