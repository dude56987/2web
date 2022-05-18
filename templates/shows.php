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
include($_SERVER['DOCUMENT_ROOT']."/header.php");
# add the search box
?>
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexSeries")' placeholder='Search...' >

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<?php
# add the updated shows below the header
include($_SERVER['DOCUMENT_ROOT']."/updatedShows.php");
################################################################################
?>
<div class='settingListCard'>
<?php
// get a list of all the genetrated index links for the page
//$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/2web/web/shows/*/shows.index"));
if (file_exists("/var/cache/2web/web/shows/shows.index")){
	$sourceFiles = explode("\n",file_get_contents("/var/cache/2web/web/shows/shows.index"));
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
	echo "<li>No Shows Have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
	echo "<li>Add download paths in <a href='/nfo.php'>video on demand admin interface</a></li>";
	echo "<li></li>";
	echo "</ul>";
}
?>
</div>
<?php
// add random shows above the footer
include($_SERVER['DOCUMENT_ROOT']."/randomShows.php");
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
echo "<hr class='topButtonSpace'>"
?>
</body>
</html>
