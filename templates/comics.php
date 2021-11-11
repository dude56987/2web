<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
<script>
	<?php
		include("../nfo2web.js");
	?>
</script>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("../header.php");
?>

<!--  add the search box -->
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexSeries")' placeholder='Search...' >

<?php
	include("../updatedComics.php");
?>

<hr>

<!-- create top jump button -->
<a href='#top' id='topButton' class='button'>&uarr;</a>

<div class='settingListCard'>

<?php
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",shell_exec("ls -t1 /var/cache/nfo2web/web/comics/*/comic.index"));
	// reverse the time sort
	$sourceFiles = array_reverse($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "$data";
				}
			}
		}
	}
?>

</div>

<?php
	// add random comics above the footer
	include("../randomComics.index");
	// add the footer
	include("../header.php");
	echo "<hr class='topButtonSpace'>"
?>

</body>
</html>
