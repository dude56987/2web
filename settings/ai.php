
<!--
########################################################################
# 2web ai settings
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
		<li><a href='#addaiLibary'>Add AI Libary Paths</a></li>
		<li><a href='#aiServerLibaryPaths'>Server Libary Paths Config</a></li>
		<li><a href='#aiLibaryPaths'>AI Libary Paths</a></li>
		<li><a href='#aiLyricsGenerate'>Generate Lyrics</a></li>
		<li><a href='#aiSubsGenerate'>Generate Subtitles</a></li>
		<li><a href='#aiCompareGenerate'>Generate Comparisons</a></li>
	</ul>
</div>

<div id='aiLyricsGenerate' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Generate Lyrics</h2>
			<ul>
				<li>
					Generate lyrics for music2web tracks.
				</li>
			</ul>
		<select name='aiLyricsGenerate'>
			<?php
			if (file_exists("/etc/2web/ai/aiLyricsGenerate.cfg")){
				$selected=file_get_contents("/etc/2web/ai/aiLyricsGenerate.cfg");
				if ($selected == "yes"){
					echo "<option value='yes' selected>Yes</option>";
					echo "<option value='no'>No</option>";
				}else{
					echo "<option value='no' selected>No</option>";
					echo "<option value='yes'>Yes</option>";
				}
			}else{
				echo "<option value='no' selected>No</option>";
				echo "<option value='yes'>Yes</option>";
			}
			?>
		</select>
		<button class='button' type='submit'>Change Setting</button>
	</form>
</div>

<div id='aiSubsGenerate' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Generate Subtitles</h2>
			<ul>
				<li>
					Generate subtitles using AI for movies and shows added by nfo2web module.
				</li>
			</ul>
		<select name='aiSubsGenerate'>
			<?php
			if (file_exists("/etc/2web/ai/aiSubsGenerate.cfg")){
				$selected=file_get_contents("/etc/2web/ai/aiSubsGenerate.cfg");
				if ($selected == "yes"){
					echo "<option value='yes' selected>Yes</option>";
					echo "<option value='no'>No</option>";
				}else{
					echo "<option value='no' selected>No</option>";
					echo "<option value='yes'>Yes</option>";
				}
			}else{
				echo "<option value='no' selected>No</option>";
				echo "<option value='yes'>Yes</option>";
			}
			?>
		</select>
		<button class='button' type='submit'>Change Setting</button>
	</form>
</div>

<div id='aiCompareGenerate' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Generate Comparisons</h2>
			<ul>
				<li>
					Generate comparisons for related videos section of webpages.
				</li>
				<li>
					This is EXTREMELY <span title='Central Processing Unit'>CPU</span><sup>Central Processing Unit</sup> expensive. It is generating a <span title='Large Language Model'>LLM</span><sup>Large Language Model</sup> for local content from scratch. This process may take weeks to complete but can be interupted and picked up after a unexpected system reboot.
				</li>
				<li>
					<span class='disabledSetting'>Webpages do NOT support this yet.</span>
				</li>
			</ul>
		<select name='aiCompareGenerate'>
			<?php
			if (file_exists("/etc/2web/ai/aiCompareGenerate.cfg")){
				$selected=file_get_contents("/etc/2web/ai/aiCompareGenerate.cfg");
				if ($selected == "yes"){
					echo "<option value='yes' selected>Yes</option>";
					echo "<option value='no'>No</option>";
				}else{
					echo "<option value='no' selected>No</option>";
					echo "<option value='yes'>Yes</option>";
				}
			}else{
				echo "<option value='no' selected>No</option>";
				echo "<option value='yes'>Yes</option>";
			}
			?>
		</select>
		<button class='button' type='submit'>Change Setting</button>
	</form>
</div>

<div id='addAiPromptModel' class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Add AI Libary Path</h2>
	<input width='60%' type='text' name='addAiPromptModel' placeholder='example_gpt4all.bin'>
	<button class='button' type='submit'>Add Path</button>
</form>
</div>

<?php
echo "<div id='aiServerLibaryPaths' class='settingListCard'>\n";
echo "<h2>AI Server Libary Paths</h2>\n";
echo "<pre>\n";
echo (file_get_contents("/etc/2web/ai/promptModels.cfg"));
echo "</pre>\n";
echo "</div>";

echo "<div id='aiLibaryPaths' class='settingListCard'>";
echo "<h2>AI Libary Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/ai/promptModels.d/*.cfg"));
sort($sourceFiles);
# write each config file as a editable entry
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				$link=(file_get_contents($sourceFile));
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeAiPromptModel' value='".$link."'>Remove Model</button>\n";
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
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
