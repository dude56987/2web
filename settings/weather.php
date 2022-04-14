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
		<li><a href='#addWeatherLocation'>Add Weather Location</a></li>
	</ul>
</div>

<div id='addWeatherLocation' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Weather Location</h2>
<input width='60%' type='text' name='addWeatherLocation' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

<?PHP
echo "<div id='serverDownloadLinkConfig' class='settingListCard'>\n";
echo "<h2>Server Weather Location Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/weather/location.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='currentLinks' class='settingListCard'>";
echo "<h2>Current locations</h2>\n";
$sourceFiles = scandir("/etc/2web/weather/location.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/weather/location.d/*.cfg"));
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
				echo "		<button class='button' type='submit' name='removeWeatherLocation' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}



?>
</div>

<?PHP
	include("header.php");
?>
</body>
</html>
