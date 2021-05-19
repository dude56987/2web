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
include("header.html");
?>
<div class='titleCard'>
	<h1>Settings</h1>
	<a class='button' href='system.php'>SYSTEM</a>
	<a class='button' href='tv.php'>TV</a>
	<a class='button' href='radio.php'>RADIO</a>
	<a class='button' href='nfo.php'>NFO</a>
	<a class='button' href='comics.php'>COMICS</a>
	<a class='button' href='cache.php'>CACHE</a>
</div>
<?php

echo "<div class='settingListCard'>\n";
echo "<h2>Current Link Config</h2>\n";
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
<h2>Add Comic</h2>
<input width='60%' type='text' name='addComic' placeholder='Link'>
<input class='button' type='submit'>
</form>
</div>

</body>
</html>
