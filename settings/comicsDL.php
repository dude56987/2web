<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web comic downloder settings
# Copyright (C) 2024  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
########################################################################
-->
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
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
<div class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addComicDownloadLink'>Add Comic Download Link</a></li>
		<li><a href='#serverDownloadLinkConfig'>Server Download Link Config</a></li>
		<li><a href='#currentLinks'>Current Links</a></li>
		<li><a href='#gallery-dl_links'>Gallery-dl Support</a></li>
		<li><a href='#dosage_links'>Dosage Support</a></li>
	</ul>
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

<div id='addComicDownloadLink' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Comic Download Link</h2>
<ul>
	<li>Add webpage links from <a href='#gallery-dl_links'>supported websites</a></li>
</ul>
<input width='60%' type='text' name='addComicDownloadLink' placeholder='http://link.com/test'>
<button class='button' type='submit'>Add Link</button>
</form>
</div>

</div>

<?PHP
echo "<div id='serverWebDownloadLinkConfig' class='settingListCard'>\n";
echo "<h2>Server WebComic Download Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/comics/webSources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='currentLinks' class='settingListCard'>";
echo "<h2>Current Webcomic links</h2>\n";
$sourceFiles = scandir("/etc/2web/comics/webSources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/comics/webSources.d/*.cfg"));
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
				echo "		<button class='button' type='submit' name='removeWebComicDownload' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>

<div id='addComicDownloadLink' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add WebComic By Name</h2>
<ul>
	<li>Add a webcomic by name path from <a href='#dosage_links'>supported comics</a>.</li>
</ul>
<input width='60%' type='text' name='addWebComicDownload' placeholder='xkcd'>
<button class='button' type='submit'>Add Link</button>
</form>
</div>

</div>

<div id='gallery-dl_links' class='settingListCard'>
<h2>Gallery-dl Supported Websites</h2>
<table>
	<tr>
		<th>Extractor</th>
		<th>SSL</th>
		<th>HTTP</th>
	</tr>
<?PHP
$extractors = explode("\n", shell_exec("gallery-dl --list-extractors | grep http | cut -d' ' -f3 | cut -d'/' -f3 | uniq"));
foreach($extractors as $extractor_name){
	if ($extractor_name != ''){
		echo "<tr>";
		echo "<td>";
		echo "$extractor_name";
		echo "</td>";
		echo "<td>";
		echo "<a href='https://$extractor_name'>https://$extractor_name</a>";
		echo "</td>";
		echo "<td>";
		echo "<a href='http://$extractor_name'>http://$extractor_name</a>";
		echo "</td>";
		echo "</tr>";
		flush();
		ob_flush();
	}
}
?>
</table>
</div>

<?PHP
$cacheFilePath="/var/cache/2web/web/web_cache/comic2web_dosageList.index";
if (file_exists($cacheFilePath)){
	echo "<div id='dosage_links' class='settingListCard'>";
	echo "<h2>Dosage Supported Webcomics</h2>";
	echo "<table>";
	echo "	<tr>";
	echo "		<th>Comic Name</th>";
	echo "		<th>Link</th>";
	echo "		<th>Language</th>";
	echo "	</tr>";
	# load the cached search results
	$cacheFileHandle = fopen($cacheFilePath,"r");
	while( ! feof($cacheFileHandle)){
		# send a line of the cache file
		echo fgets($cacheFileHandle);
	}
	echo "</table>";
	echo "</div>";
}
?>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
