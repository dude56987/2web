<?PHP
include("/usr/share/2web/2webLib.php");
requireLogin();
?>
<!--
########################################################################
# 2web comic settings
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

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addComicLibrary'>Add Comic Library Paths</a></li>
		<li><a href='#comicServerLibraryPaths'>Server Library Paths Config</a></li>
		<li><a href='#comicLibraryPaths'>Comic Library Paths</a></li>
	</ul>
</div>

<div id='index' class='inputCard'>
	<h2>Supported Library file types</h2>
	<ul>
		<li>.txt</li>
		<li>.zip</li>
		<li>.cbz</li>
		<li>.pdf</li>
		<li>local jpeg directories
			<ul>
				<li>one directory per comic</li>
				<li>directory name will be comic name</li>
				<li>You can place directories with image files inside the top level directory for chapters</li>
				<li>This is based on gallery-dl's download directory structure</li>
			</ul>
		</li>
	</ul>
</div>

<?php
echo "<div id='comicServerLibraryPaths' class='settingListCard'>\n";
echo "<h2>Comic Server Library Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/comics/libaries.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='comicLibraryPaths' class='settingListCard'>";
echo "<h2>Comic Library Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/comics/libaries.d/*.cfg"));
// reverse the time sort
sort($sourceFiles);
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
				echo "	<button class='button' type='submit' name='removeComicLibrary' value='".$link."'>Remove Library</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
				//echo "</div>";
			}
		}
	}
}
?>
	<div id='addComicLibrary' class='inputCard'>
	<form action='admin.php' method='post'>
		<h2>Add Comic Library Path</h2>
		<input width='60%' type='text' name='addComicLibrary' placeholder='/absolute/path/to/the/Library'>
		<button class='button' type='submit'>Add Path</button>
	</form>
	</div>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
