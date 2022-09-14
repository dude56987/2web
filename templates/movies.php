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
# add the search box
?>
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexSeries")' placeholder='Search...' >

<?php
# add the updated movies below the header
drawPosterWidget("movies");
################################################################################
?>
<div class='settingListCard'>
<?php
if (file_exists("/var/cache/2web/web/movies/movies.index")){
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",file_get_contents("/var/cache/2web/web/movies/movies.index"));
	// reverse the time sort
	$sourceFiles = array_unique($sourceFiles);
	# sort the list
	sort($sourceFiles);
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
	echo "<li>No Movies have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
	echo "<li>Add download links in <a href='/ytdl2nfo.php'>video on demand admin interface</a></li>";
	echo "</ul>";
}

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
