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
echo "<h2>Server Website Sources</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/ytdl2kodi/sources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Website Sources</h2>\n";
$sourceFiles = scandir("/etc/ytdl2kodi/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/ytdl2kodi/sources.d/*.cfg"));
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
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='ytdl_remove_source' value='".$link."'>Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
echo "</div>\n";

echo "<div class='settingListCard'>\n";
echo "<h2>Server Username Sources</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/ytdl2kodi/usernameSources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Username Sources</h2>\n";
$sourceFiles = scandir("/etc/ytdl2kodi/usernameSources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/ytdl2kodi/usernameSources.d/*.cfg"));
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
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='ytdl_remove_username_source' value='".$link."'>Remove Source</button>\n";
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
<h2>Add Website Source</h2>
<ul>
	<li>
	Website sources will be added as shows based on video links found on source pages.
	</li>
	<li>
	Website sources will be grouped by website name.
	</li>
	<li>
	Website sources can be search pages.
	</li>
</ul>
<input width='60%' type='text' name='ytdl_add_source' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Username Source</h2>
<ul>
	<li>
		For websites with usernames, will create a show with all of that usernames content.
	</li>
	<li>
		The same usernames on diffrent sites will link to the same generated show.
	</li>
</ul>
<input width='60%' type='text' name='ytdl_add_username_source' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

</body>
</html>
