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
<div class='settingListCard'>

<?php
if (file_exists("/var/cache/2web/web/graphs/graphs.index")){
	// get a list of all the genetrated index links for the page
	$sourceFiles = file("/var/cache/2web/web/graphs/graphs.index", FILE_IGNORE_NEW_LINES);
	// reverse the time sort
	sort($sourceFiles);
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
	echo "<li>No Munin Graphs have been generated!</li>";
	echo "<li>Add libary paths in the <a href='/settings/graphs.php'>comics admin interface</a> to populate this page.</li>";
	echo "<li>Add download links in <a href='/settings/graphs.php'>comics admin interface</a></li>";
	echo "</ul>";
}
?>
</div>

<?php
	// add random comics above the footer
	drawPosterWidget("graphs", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
