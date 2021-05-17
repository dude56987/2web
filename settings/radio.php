<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php

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
</div>

<?php
echo "<div class='settingListCard'>\n";
echo "<h2>Current Radio Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/iptv2web/radioSources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Custom Radio Links</h2>\n";

echo "</div>";


echo "<div class='settingListCard'>";
echo "<h2>Radio Links</h2>\n";
$sourceFiles = scandir("/etc/iptv2web/radioSources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/iptv2web/radioSources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//$sourceFileName = array_reverse(explode("/",$sourceFile))[0];
	//$sourceFile = "/etc/iptv2web/sources.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);

				# try to find a icon for the link
				$iconLink=md5("http://".gethostname().".local:444/iptv-resolver.php?url=\"".$link."\"");

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
				echo "	<button class='button' type='submit' name='removeRadioLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='blockLink' value='".$link."'>BLOCK</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='moveToBottom' value='".$link."'>Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
				//echo "</div>";
			}
		}
	}
}

# read the custom links
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/iptv2web/radioSources.d/*.m3u"));
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
			//echo "[DEBUG]: It is a file...<br>\n";
			if (strpos($sourceFile,".m3u")){
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				# get the link, it should be the second line of the file
				$fileData = file_get_contents($sourceFile);
				# DEBUG DRAW THE ARRAY
				//print_r($link);
				$fileData = explode('\n',$fileData);
				//print_r($link);
				$title = explode(',',$fileData[1])[1];
				$link = $fileData[2];
				echo "<div class='settingsEntry'>\n";
				echo "	<h2>".$title."</h2>";
				echo "	".$link."";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeCustomLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='moveCustomToBottom' value='".$link."'>Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}

//echo "</table>";
echo "</div>";

echo "<div class='inputCard'>";
echo "<form action='admin.php' method='post'>\n";
echo "<h2>Add Radio Link</h2>\n";
echo "<input width='60%' type='text' name='addRadioLink' placeholder='Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";

echo "<div class='inputCard'>";
echo "<form action='admin.php' method='post'>\n";
echo "<h2>Add Radio Station</h2>\n";
echo "<input width='60%' type='text' name='addCustomRadioLink' placeholder='Link'>\n";
echo "<input width='60%' type='text' name='addCustomRadioTitle' placeholder='Title'>\n";
echo "<input width='60%' type='text' name='addCustomRadioIcon' placeholder='Icon Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";

//echo "<form method='get'>\n";
//echo "<h2>Remove Link</h2>\n";
//echo "<input width='60%' type='text' name='removeLink' placeholder='Link'>\n";
//echo "<input type='submit'>\n";
//echo "</form>\n";

?>
</body>
</html>
