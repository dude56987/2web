<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web ondemand settings
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
	<li><a href='#serverLibaryPaths'>Server Library Paths</a></li>
	<li><a href='#libaryPaths'>Library Paths</a></li>
	<li><a href='#addLibaryPath'>Add Library Path</a></li>
	<ul>
</div>

<div id='nfo_generateAudioWaveform' class='inputCard'>
	<h2>Generate Waveform Thumbnails</h2>
		<ul>
			<li>
				Generate waveform thumbnails for audio files. This includes remote audio files stored as '.strm' files.
			</li>
			<li>
				To generate a waveform the audio track must be downloaded to local memory. If you have a lot of remote audio links like a podcast, this means every episode must be downloaded to be converted into a waveform. Depeneding on how fast your network connection is you may not want to use this option.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/nfo/generateAudioWaveforms.cfg","Generating Audio Waveforms","nfo_generateAudioWaveforms");
		?>
</div>

<?php
echo "<div id='serverLibaryPaths' class='titleCard'>\n";
echo "<h2>Server Libary Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/nfo/libaries.cfg");
echo "</pre>\n";
echo "</div>";
?>
<?php
echo "<div id='serverDisabledLibaryPaths' class='titleCard'>\n";
echo "<h2>Server Disabled Libary Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/nfo/disabledLibaries.cfg");
echo "</pre>\n";
echo "</div>";
?>

<?php
echo "<div id='libaryPaths' class='settingListCard'>";
echo "<h2>Libary Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -1 /etc/2web/nfo/libaries.d/*.cfg"));
# sort sources alphabetically
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
				if (file_exists("/etc/2web/nfo/disabledLibaries.d/".md5($link).".cfg")){
					echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
					echo "	<button class='button' type='submit' name='enableLibrary' value='".$link."'>◯ Enable Updates</button>\n";
					echo "	</form>\n";
				}else{
					echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
					echo "	<button class='button' type='submit' name='disableLibrary' value='".$link."'>🟢 Disable Updates</button>\n";
					echo "	</form>\n";
				}
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeLibary' value='".$link."'>❌ Remove Library</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
				//echo "</div>";
			}
		}
	}
}
?>
	<div id='addLibaryPath' class='inputCard'>
		<form action='admin.php' method='post'>
			<h2>Add Library Path</h2>
			<input width='60%' type='text' name='addLibary' placeholder='/absolute/path/to/the/libary'>
			<button class='button' type='submit'>➕ Add Path</button>
		</form>
	</div>
</div>
<?php
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
