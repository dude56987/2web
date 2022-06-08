<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/nfo2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<!--  add the search box -->
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexSeries")' placeholder='Search...' >

<?php
	drawPosterWidget("comics");
?>

<hr>

<div class='settingListCard'>

<?php
if (file_exists("/var/cache/2web/web/comics/comics.index")){
	// get a list of all the genetrated index links for the page
	//$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/2web/web/comics/*/comic.index | sort"));
	$sourceFiles = explode("\n",file_get_contents("/var/cache/2web/web/comics/comics.index"));
	// reverse the time sort
	$sourceFiles = array_reverse($sourceFiles);
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
	echo "<ul>";
	echo "<li>No Comics Have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/comics.php'>comics admin interface</a> to populate this page.</li>";
	echo "<li>Add download links in <a href='/comicsDL.php'>comics admin interface</a></li>";
	echo "</ul>";
}
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
