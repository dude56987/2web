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
echo "<h2>Server Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/iptv2web/sources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='currentLinks' class='settingListCard'>";
echo "<h2>Current links</h2>\n";
$sourceFiles = scandir("/etc/iptv2web/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/iptv2web/sources.d/*.cfg"));
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
				echo "	<button class='button' type='submit' name='removeLink' value='".$link."'>Remove Link</button>\n";
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
?>
</div>

<div id='addLink' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Link</h2>
<input width='60%' type='text' name='addLink' placeholder='Link'>
<input class='button' type='submit'>
</form>
</div>

<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Custom Link</h2>
<input width='60%' type='text' name='addCustomLink' placeholder='Link'>
<input width='60%' type='text' name='addCustomTitle' placeholder='Title'>
<input width='60%' type='text' name='addCustomIcon' placeholder='Icon Link'>
<input class='button' type='submit'>
</form>
</div>

</body>
</html>
