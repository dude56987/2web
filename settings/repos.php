<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web repos settings
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
		<li><a href='#addReposLibrary'>Add Repo Library Paths</a></li>
		<li><a href='#reposServerLibraryPaths'>Server Library Paths Config</a></li>
		<li><a href='#reposLibraryPaths'>Repo Library Paths</a></li>
		<li><a href='#addRepoSource'>Add Repo Source</a></li>
		<li><a href='#reposServerSourcePaths'>Server Repo Sources</a></li>
		<li><a href='#reposSourcePaths'>Repo Source Paths</a></li>
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
					<button class='button' type='submit' name='git2web_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#git2web'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='git2web_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<div id='repoRenderVideo' class='inputCard'>
	<h2>Render Videos</h2>
		<ul>
			<li>
				Render video files using gource for each repo.
			</li>
			<li>
				Render a combined video showing work done on all repos.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/repos/renderVideo.cfg","Video Rendering","repoRenderVideo");
		?>
</div>
<?php
echo "<details id='repoServerLibraryPaths' class='titleCard'>\n";
echo "<summary><h2>Repo Server Library Paths</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/repos/libaries.cfg");
echo "</pre>\n";
echo "</details>";

echo "<div id='reposLibraryPaths' class='settingListCard'>";
echo "<h2>Repos Library Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/repos/libaries.d/*.cfg"));
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
				echo "	<button class='button' type='submit' name='removeRepoLibrary' value='".$link."'>❌ Remove Library</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addRepoLibrary' class='inputCard'>
		<h2>Add Repo Library Path</h2>
		<ul>
			<li>Path is scanned recursively to look for GIT repositories</li>
		</ul>
		<form action='selectPath.php' method='post'>
			<input type='text' name='valueName' value='addRepoLibrary' hidden>
			<input type='text' name='startPath' placeholder='/absolute/path/to/the/library/'>
			<button class='button' type='submit'>📁 Select Path</button>
		</form>
	</div>
</div>
<?php
echo "<details id='repoServerSourcePaths' class='titleCard'>\n";
echo "<summary><h2>Repo Server Source Paths</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/repos/sources.cfg");
echo "</pre>\n";
echo "</details>";

echo "<div id='repoSourcePaths' class='settingListCard'>";
echo "<h2>Repo Source Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/repos/sources.d/*.cfg"));
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
				echo "	<button class='button' type='submit' name='removeRepoSource' value='".$link."'>❌ Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addRepoLibrary' class='inputCard'>
	<h2>Add Repo Source Path</h2>
	<form action='admin.php' method='post'>
		<input width='60%' type='text' name='addRepoSource' placeholder='/absolute/path/to/the/library'>
		<button class='button' type='submit'>➕ Add Path</button>
	</form>
	</div>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
