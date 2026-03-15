<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web comic settings
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
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

<div id='moduleStatus' class='inputCard'>
	<h2>Module Actions</h2>
	<table class='controlTable'>
		<tr>
			<td>
				Build or Refresh all generated web components.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='comic2web_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#comic2web'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='comic2web_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<div id='index' class='titleCard'>
	<h2>Supported Library file types</h2>
	<ul>
		<li>.html (Hypertext Markup Language)</li>
		<li>.ps (Postscript)</li>
		<li>.md (Markdown)</li>
		<li>.txt (Text)</li>
		<li>.zip (Compressed File)</li>
		<li>.cbz (Comic Book Zip)</li>
		<li>.pdf (Portable Document Format)</li>
		<li>.epub (Electronic Publication)</li>
		<li>The above file formats will be scanned for in the library paths and any subdirectorys in those paths.</li>
		<li>local mixed jpeg/png/mp4/gif directories
			<ul>
				<li>given library paths must contain a tag path preceding the comics</li>
				<li>/comicLibraryPath/tagName/comicTitle/</li>
				<li>/comicLibraryPath/tagName/comicTitle/chapterTitle/</li>
				<li>one directory per comic</li>
				<li>directory name will be comic name</li>
				<li>You can place directories with image files inside the top level directory for chapters</li>
				<li>This is based on gallery-dl's download directory structure</li>
			</ul>
		</li>
	</ul>
	<h2>Example Comic Directory Structures</h2>
	<div class='inputCard'>
		<details>
			<summary><h3>Mixed Format Multi Chapter Example</h3></summary>
			<ul>
				<li>comicTag
					<ul>
						<li>ExampleComic_01
							<ul>
								<li>ExampleChapterTitle_01
									<ul>
										<li>Page_01.png</li>
										<li>Page_02.jpg</li>
										<li>Page_03.jpg</li>
										<li>Page_04.mp4</li>
										<li>Page_05.gif</li>
									</ul>
								</li>
								<li>ExampleChapterTitle_02
									<ul>
										<li>Page_01.png</li>
										<li>Page_02.jpg</li>
										<li>Page_03.jpg</li>
										<li>Page_04.mp4</li>
										<li>Page_05.gif</li>
									</ul>
								</li>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
		</details>
	</div>
	<div class='inputCard'>
		<details>
			<summary><h3>Mixed Format Example Without Chapters</h3></summary>
			<ul>
				<li>comicTag
					<ul>
						<li>ExampleComic_01
							<ul>
								<li>Page_01.png</li>
								<li>Page_02.jpg</li>
								<li>Page_03.jpg</li>
								<li>Page_04.mp4</li>
								<li>Page_05.gif</li>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
		</details>
	</div>
	<div class='inputCard'>
		<details>
			<summary><h3>Single Format Example Without Chapters</h3></summary>
			<ul>
				<li>comicTag
					<ul>
						<li>ExampleComic_01
							<ul>
								<li>Page_01.png</li>
								<li>Page_02.png</li>
								<li>Page_03.png</li>
								<li>Page_04.png</li>
								<li>Page_05.png</li>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
		</details>
	</div>
	<div class='inputCard'>
		<details>
			<summary><h3>Single Format Example Without Chapters</h3></summary>
			<ul>
				<li>comicTag
					<ul>
						<li>ExampleComic_01
							<ul>
								<li>Page_01.jpg</li>
								<li>Page_02.jpg</li>
								<li>Page_03.jpg</li>
								<li>Page_04.jpg</li>
								<li>Page_05.jpg</li>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
		</details>
	</div>

</div>

<?php
echo "<details id='comicServerLibraryPaths' class='titleCard'>\n";
echo "<summary><h2>Comic Server Library Paths</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/comics/libaries.cfg");
echo "</pre>\n";
echo "</details>";
#
echo "<details id='serverDisabledLibraryPaths' class='titleCard'>\n";
echo "<summary><h2>Server Disabled Comic Library Paths</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/comics/disabledLibaries.cfg");
echo "</pre>\n";
echo "</details>";
#
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
				$link=trim(file_get_contents($sourceFile));
				$tempDiskSize=getDiskSize($link);
				# check the disk is accessable
				if (! is_readable($link)){
					echo "<details>";
					echo "<summary class='errorBanner'>🖐︎ ERROR: Path is not accessable ! 🖐︎</summary>";
					echo "<ul>";
					echo "<li>The disk could be unplugged?</li>";
					echo "<li>The disk is not mounted?</li>";
					echo "<li>The disk filesystem could be corrupted?</li>";
					echo "<li>The disk could be dead/broken?</li>";
					echo "</ul>";
					echo "</details>";
				}else{
					echo "<span class='diskSize'>".$tempDiskSize."</span>\n";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				if (file_exists("/etc/2web/comics/disabledLibaries.d/".md5($link).".cfg")){
					echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
					echo "	<button class='button' type='submit' name='enableComicLibrary' value='".$link."'>◯ Enable Updates</button>\n";
					echo "	</form>\n";
				}else{
					echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
					echo "	<button class='button' type='submit' name='disableComicLibrary' value='".$link."'>🟢 Disable Updates</button>\n";
					echo "	</form>\n";
				}
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeComicLibrary' value='".$link."'>❌ Remove Library</button>\n";
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
		<h2>Add Library Path</h2>
		<form action='selectPath.php' method='post'>
			<input type='text' name='valueName' value='addComicLibrary' hidden>
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
