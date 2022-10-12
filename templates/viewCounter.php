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
?>

<?php
################################################################################
?>
<div class='settingListCard'>
<?php
if (is_dir("/var/cache/2web/web/views/")){
	echo "<table>";
	echo "<ul>";
	// get a list of all the genetrated index links for the page
	$sourceFiles = scandir("/var/cache/2web/web/views/");
	// reverse the time sort
	$sourceFiles = array_unique($sourceFiles);
	# sort the list
	sort($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".cfg")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "<tr>";
					echo "	<td>";
					$tempOutputData=str_replace("_","/",$sourceFile);
					$tempOutputData=str_replace("/views.cfg",".php",$tempOutputData);
					echo "		<a href='".$tempOutputData."'>".$tempOutputData."</a>";
					echo "	</td>";
					echo "	<td>";
					echo "		$data";
					echo "	</td>";
					echo "</tr>";
					flush();
					ob_flush();
				}
			}
		}
	}
	echo "</ul>";
	echo "</table>";
}else{
	// no shows have been loaded yet
	echo "<ul>";
	echo "<li>No Movies have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/settings/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
	echo "<li>Add download links in <a href='/settings/ytdl2nfo.php'>video on demand admin interface</a></li>";
	echo "</ul>";
}

?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
