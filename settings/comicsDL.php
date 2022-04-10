<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include("header.php");
include("settingsHeader.php");
?>
<div class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addComicDownloadLink'>Add Comic Download Link</a></li>
		<li><a href='#serverDownloadLinkConfig'>Server Download Link Config</a></li>
		<li><a href='#currentLinks'>Current Links</a></li>
	</ul>
</div>

<div id='addComicDownloadLink' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Comic Download Link</h2>
<input width='60%' type='text' name='addComicDownloadLink' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

<?PHP
echo "<div id='serverDownloadLinkConfig' class='settingListCard'>\n";
echo "<h2>Server Comic Download Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/comics/sources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='currentLinks' class='settingListCard'>";
echo "<h2>Current links</h2>\n";
$sourceFiles = scandir("/etc/2web/comics/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/comics/sources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='removeComicDownloadLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}



?>
</div>

<div id='currentLinks' class='settingListCard'>
<h2>Supported Websites</h2>
<?PHP
	echo shell_exec("gallery-dl --list-extractors | grep http | cut -d' ' -f3 | cut -d'/' -f3 | uniq");
?>
</div>

<?PHP
	include("header.php");
?>
</body>
</html>
