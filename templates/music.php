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
if (file_exists($_SERVER['DOCUMENT_ROOT']."/music/music.index")){
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT']."/music/music.index"));
	// reverse the time sort
	//$sourceFiles = array_reverse($sourceFiles);
	$sourceFiles = array_unique($sourceFiles);
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
}else{
	// no shows have been loaded yet
	echo "<ul>";
	echo "<li>No Music have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/music.php'>video on demand admin interface</a> to populate this page.</li>";
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
