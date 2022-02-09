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

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addComicLibary'>Add Comic Libary Paths</a></li>
		<li><a href='#comicServerLibaryPaths'>Server Libary Paths Config</a></li>
		<li><a href='#comicLibaryPaths'>Comic Libary Paths</a></li>
	</ul>
</div>

<div id='addComicLibary' class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Add Comic Libary Path</h2>
	<input width='60%' type='text' name='addComicLibary' placeholder='/absolute/path/to/the/libary'>
	<input class='button' type='submit'>
</form>
</div>

<?php
echo "<div id='comicServerLibaryPaths' class='settingListCard'>\n";
echo "<h2>Comic Server Libary Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/comic2web/libaries.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='comicLibaryPaths' class='settingListCard'>";
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
				echo "	<button class='button' type='submit' name='removeComicLibary' value='".$link."'>Remove Libary</button>\n";
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
<?PHP
	include("header.php");
?>
</body>
</html>
