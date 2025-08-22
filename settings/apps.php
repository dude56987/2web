<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web app settings
# Copyright (C) 2025  Carl J Smith
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
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addAppLibrary'>Add Application Library Paths</a></li>
		<li><a href='#appServerLibraryPaths'>Server Application Library Paths Config</a></li>
		<li><a href='#appLibraryPaths'>Application Library Paths</a></li>
	</ul>
</div>

<div id='moduleStatus' class='inputCard'>
	<h2>Module Actions</h2>
	<table class='controlTable'>
		<tr>
			<td>
				Build or Refresh all generated web components.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='php2web_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#php2web'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='php2web_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<div id='index' class='titleCard'>
	<h2>Supported Library file types</h2>
	<ul>
		<li>Zip files placed in application Library paths will be loaded when php2web is updated.</li>
		<li>Example applications can be found on the server at /usr/share/2web/example_apps/</li>
		<li>The zip file must contain a .html .php or .htm file at the top level of the zip file. The file should be named 'main' or 'index' otherwise it may not be found.</li>
		<li>PHP applications with the line 'include("/var/cache/2web/web/header.php")' and 'include("/var/cache/2web/web/footer.php")' will be fully intergrated into the website. All other applications will be placed inside a adjustable frame with a fullscreen button.</li>
	</ul>
</div>

<?php
echo "<details id='appServerLibraryPaths' class='titleCard'>\n";
echo "<summary><h2>Application Server Library Paths</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/applications/libaries.cfg");
echo "</pre>\n";
echo "</details>";

echo "<div id='appLibraryPaths' class='settingListCard'>";
echo "<h2>Application Library Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/applications/libaries.d/*.cfg"));
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
				echo "	<button class='button' type='submit' name='removeAppLibrary' value='".$link."'>❌ Remove App Library</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addAppLibrary' class='inputCard'>
		<h2>Add Application Library Path</h2>
		<form action='selectPath.php' method='post'>
			<input type='text' name='valueName' value='addAppLibrary' hidden>
			<input type='text' name='startPath' placeholder='/absolute/path/to/the/library/'>
			<button class='button' type='submit'>📁 Select Path</button>
		</form>
	</div>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
