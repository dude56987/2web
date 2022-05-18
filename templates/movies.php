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
# add the search box
?>
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexSeries")' placeholder='Search...' >

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<?php
# add the updated movies below the header
include($_SERVER['DOCUMENT_ROOT']."/updatedMovies.php");
################################################################################
?>
<div class='settingListCard'>
<?php
// get a list of all the genetrated index links for the page
//$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/2web/web/movies/*/movies.index"));
$sourceFiles = explode("\n",file_get_contents("/var/cache/2web/web/movies/movies.index"));
// reverse the time sort
//$sourceFiles = array_reverse($sourceFiles);
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
?>
</div>
<?php
// add random movies above the footer
include($_SERVER['DOCUMENT_ROOT']."/randomMovies.php");
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
echo "<hr class='topButtonSpace'>"
?>
</body>
</html>
