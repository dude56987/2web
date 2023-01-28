<?PHP
########################################################################
# 2web comic downloder settings
# Copyright (C) 2023  Carl J Smith
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
?>
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
<div class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addComicDownloadLink'>Add Comic Download Link</a></li>
		<li><a href='#serverDownloadLinkConfig'>Server Download Link Config</a></li>
		<li><a href='#currentLinks'>Current Links</a></li>
	</ul>
</div>

<div id='downloadPath' class='inputCard'>
<h2>Download Path</h2>
<ul>
	<li>
		This is where downloaded comics will be stored.
	</li>
	<li>
		Comics generated from other file formats will also be stored here.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/2web/comics/download.cfg");
?>
</div>

<div id='addComicDownloadLink' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Comic Download Link</h2>
<input width='60%' type='text' name='addComicDownloadLink' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
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
</div>

<div id='currentLinks' class='settingListCard'>
<h2>Supported Websites</h2>
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
	}
}
?>
</table>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
