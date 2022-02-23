<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
<script>
	<?php
		include("/usr/share/nfo2web/nfo2web.js");
	?>
</script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include("../header.php");
# add the search box
?>
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexSeries")' placeholder='Search...' >

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<?php
# add the updated movies below the header
include("../updatedMovies.php");
################################################################################
?>
<div class='settingListCard'>
<?php
// get a list of all the genetrated index links for the page
$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/nfo2web/web/movies/*/movies.index"));
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
include("../randomMovies.php");
// add the footer
include("../header.php");
echo "<hr class='topButtonSpace'>"
?>
</body>
</html>
