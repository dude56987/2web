<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web wikis settings
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

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addwikiPath'>Add wiki Path</a></li>
		<li><a href='#wikiServerPaths'>Wiki Server Paths</a></li>
		<li><a href='#wikiPaths'>Wiki Paths</a></li>
	</ul>
</div>
<?php
echo "<div id='wikiServerPaths' class='settingListCard'>\n";
echo "<h2>wiki Server scanSources Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/wiki/libraries.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='wikiPaths' class='settingListCard'>";
echo "<h2>Wiki Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/wiki/libraries.d/*.cfg"));
sort($sourceFiles);
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
				echo "	<button class='button' type='submit' name='removeWikiPath' value='".$link."'>❌ Remove Path</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addWikiPath' class='inputCard'>
	<form action='admin.php' method='post'>
		<h2>Add Wiki Path</h2>
		<ul>
			<li>Add the path of a directory containing .zim files</li>
		</ul>
		<input width='60%' type='text' name='addWikiPath' placeholder='/absolute/path/to/the/directory/'>
		<button class='button' type='submit'>➕ Add Path</button>
	</form>
	</div>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
