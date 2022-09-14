<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
	<li><a href='#blockGroup'>Manual Block Group</a></li>
	<li><a href='#unblockGroup'>Manual Unblock Group</a></li>
	<li><a href='#serverBlockedGroups'>Server Blocked Groups</a></li>
	<li><a href='#activeBlockedGroups'>Active/Blocked Groups</a></li>
	<ul>
</div>

<div id='blockGroup' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Block Group</h2>
<input width='60%' class='inputText' type='text' name='blockGroup' placeholder='GroupName...'>
<input class='button' type='submit'>
</form>
</div>

<div id='unblockGroup' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Unblock Group</h2>
<input width='60%' class='inputText' type='text' name='unblockGroup' placeholder='GroupName...'>
<input class='button' type='submit'>
</form>
</div>

<?PHP
echo "<div id='serverBlockedGroups' class='inputCard'>";
echo "<h2>Server Blocked Groups</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/iptv/blockedGroups.cfg");
echo "</pre>\n";
echo "</div>";


$sourceFiles = scandir("/etc/2web/iptv/blockedGroups.d/");
$blockedGroups = array();
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	$sourceFile = "/etc/2web/iptv/blockedGroups.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				$link=file_get_contents($sourceFile);
				$blockedGroups = array_merge($blockedGroups,array($link));
			}
		}
	}
}
?>

<div id='ActiveBlockedGroups' class='settingListCard'>
<h1>Active/Blocked Groups</h1>
<?php
// find all the groups
if (file_exists("/var/cache/2web/web/live/groups/")){
	$sourceFiles=scandir("/var/cache/2web/web/live/groups/");
	$sourceFiles=array_diff($sourceFiles,array('..','.'));
	$groups=array();
	# read the directory name and make a button to block it
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		$groups = array_merge($groups, array($sourceFileName));
		# if the group has been blocked
		if(in_array($sourceFile, $blockedGroups)){
			echo "<div class='disabledSetting settingsEntry'>\n";
		}else{
			echo "<div class='enabledSetting settingsEntry'>\n";
		}
		echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "		<h2>\n";
		echo "			$sourceFile";
		echo "		</h2>\n";
		echo "		<div class='buttonContainer'>\n";
		# if the group has been blocked
		if(in_array($sourceFile, $blockedGroups)){
			echo "			<button class='button' type='submit' name='unblockGroup' value='".$sourceFile."'>UNBLOCK</button>\n";
		}else{
			echo "			<button class='button' type='submit' name='blockGroup' value='".$sourceFile."'>BLOCK</button>\n";
		}
		echo "		</div>\n";
		echo "	</form>\n";
		echo "</div>\n";
	}

	$sourceFiles=array_diff($blockedGroups,$groups);
	foreach($sourceFiles as $groupName){
		# if the group has been blocked
		echo "<div class='disabledSetting settingsEntry'>\n";
		echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "		<h2>\n";
		echo "			$groupName";
		echo "		</h2>\n";
		echo "		<div class='buttonContainer'>\n";
		# if the group has been blocked
		echo "			<button class='button' type='submit' name='unblockGroup' value='".$groupName."'>UNBLOCK</button>\n";
		echo "		</div>\n";
		echo "	</form>\n";
		echo "</div>\n";
	}
}
?>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
