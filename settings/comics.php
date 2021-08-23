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

echo "<div class='settingListCard'>\n";
echo "<h2>Server Comic Download Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/comic2web/sources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Current links</h2>\n";
$sourceFiles = scandir("/etc/comic2web/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/comic2web/sources.d/*.cfg"));
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

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				if (file_exists($iconLink)){
					# if the link is a redirected generated link get a diffrent icon
					echo "	<img class='settingsThumb' src='".$iconLink.".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='removeComic' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
</div>

<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Comic Download Link</h2>
<input width='60%' type='text' name='addComic' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>


<?php

echo "<div class='settingListCard'>\n";
echo "<h2>Comic Server Libary Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/comic2web/libaries.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Comic Libary Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/comic2web/libaries.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
# write each config file as a editable entry
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeLibary' value='".$link."'>Remove Libary</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
				//echo "</div>";
			}
		}
	}
}
?>
</div>

<div class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Add Comic Libary Path</h2>
	<input width='60%' type='text' name='addComicLibary' placeholder='/absolute/path/to/the/libary'>
	<input class='button' type='submit'>
</form>
</div>

</body>
</html>
