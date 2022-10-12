<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
</script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
# add the updated shows below the header
drawPosterWidget("shows");
################################################################################
?>
<div class='settingListCard'>
<?php
# store the index path
$indexFilePath="/var/cache/2web/web/shows/shows.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No Shows Have been scanned into the libary!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
$emptyMessage .= "<li>Add download links in <a href='/settings/ytdl2nfo.php'>video on demand admin interface</a></li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>
<?php
// add random shows above the footer
drawPosterWidget("shows", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
