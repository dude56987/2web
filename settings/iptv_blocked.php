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

echo "<div class='settingListCard'>";
echo "<h2>Server Blocked links Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/iptv2web/blockedLinks.cfg");
echo "</pre>\n";
echo "</div>";


echo "<div class='settingListCard'>";
echo "<h2>Blocked links</h2>\n";
$sourceFiles = scandir("/etc/iptv2web/blockedLinks.d/");
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	$sourceFile = "/etc/iptv2web/blockedLinks.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				$link=file_get_contents($sourceFile);
				echo "<form action='admin.php' class='removeLink' method='post'>\n";
				echo "<input class='button' type='text' name='unblockLink' value='".$link."' readonly>\n";
				echo "<input class='button' type='submit' value='UNBLOCK'>\n";
				echo "</form>\n";
			}
		}
	}
}
echo "</div>";
?>

<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Block Link</h2>
<input width='60%' class='inputText' type='text' name='blockLink' placeholder='Link'>
<input class='button' type='submit'>
</form>
</div>

<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Unblock Link</h2>
<input width='60%' class='inputText' type='text' name='unblockLink' placeholder='Link'>
<input class='button' type='submit'>
</form>
</div>


<?PHP
echo "<div class='settingListCard'>";
echo "<h2>Server Blocked Groups</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/iptv2web/blockedGroups.cfg");
echo "</pre>\n";
echo "</div>";


echo "<div class='settingListCard'>";
echo "<h2>Blocked Groups</h2>\n";
$sourceFiles = scandir("/etc/iptv2web/blockedGroups.d/");
//$blockedGroups= = array();
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	$sourceFile = "/etc/iptv2web/blockedGroups.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				$link=file_get_contents($sourceFile);
				//$blockedGroups = array_merge($blockedGroups,$link);
				echo "<div class='settingsEntry'>\n";
				echo "	<h3>\n";
				echo "		$link";
				echo "	</h3>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<div class='buttonContainer'>\n";
				echo "			<button class='button' type='submit' name='unblockGroup' value='".$link."'>UNBLOCK</button>\n";
				echo "		</div>\n";
				echo "	</form>\n";
				echo "</div>\n";
			}
		}
	}
}
echo "</div>";
?>


<div class='settingListCard'>
<h1>Available Groups</h1>
<?php
// find all the groups
$sourceFiles=scandir("/var/cache/nfo2web/web/live/groups/");
$sourceFiles=array_diff($sourceFiles,array('..','.'));
# read the directory name and make a button to block it
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	echo "<div class='settingsEntry'>\n";
	echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
	echo "		<h3>\n";
	echo "			$sourceFile";
	echo "		</h3>\n";
	echo "		<div class='buttonContainer'>\n";
	echo "			<button class='button' type='submit' name='blockGroup' value='".$sourceFile."'>BLOCK</button>\n";
	echo "		</div>\n";
	echo "	</form>\n";
	echo "</div>\n";
}
?>
</div>






<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Block Group</h2>
<input width='60%' class='inputText' type='text' name='blockGroup' placeholder='GroupName...'>
<input class='button' type='submit'>
</form>
</div>

<div class='inputCard'>
<form action='admin.php' method='post'>
<h2>Unblock Group</h2>
<input width='60%' class='inputText' type='text' name='unblockGroup' placeholder='GroupName...'>
<input class='button' type='submit'>
</form>
</div>



</body>
</html>
