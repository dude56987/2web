<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web ai settings
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
		<li><a href='#addaiLibary'>Add AI Libary Paths</a></li>
		<li><a href='#aiServerLibaryPaths'>Server Libary Paths Config</a></li>
		<li><a href='#aiLibaryPaths'>AI Libary Paths</a></li>
		<li><a href='#aiLyricsGenerate'>Generate Lyrics</a></li>
		<li><a href='#aiSubsGenerate'>Generate Subtitles</a></li>
		<li><a href='#aiCompareGenerate'>Generate Comparisons</a></li>
	</ul>
</div>

<div id='aiLyricsGenerate' class='inputCard'>
	<h2>Generate Lyrics</h2>
		<ul>
			<li>
				Generate lyrics for music2web tracks.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/ai/aiLyricsGenerate.cfg","Lyrics Generation","aiLyricsGenerate");
		?>
</div>

<div id='aiSubsGenerate' class='inputCard'>
	<h2>Generate Subtitles</h2>
		<ul>
			<li>
				Generate subtitles using AI for movies and shows added by nfo2web module.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/ai/aiSubsGenerate.cfg","Subs Generation","aiSubsGenerate");
		?>
</div>

<div id='aiCompareGenerate' class='inputCard'>
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
		<?php
		buildYesNoCfgButton("/etc/2web/ai/aiCompareGenerate.cfg","Comparison Generation","aiCompareGenerate");
		?>
</div>
<?php
echo "<div id='aiServerLibaryPaths' class='settingListCard'>\n";
echo "<h2>AI Prompt Models</h2>\n";
echo "<pre>\n";
echo (file_get_contents("/etc/2web/ai/promptModels.cfg"));
echo "</pre>\n";
echo "</div>";

echo "<div id='aiLibaryPaths' class='settingListCard'>";
echo "<h2>AI Prompt Models</h2>\n";
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
	<div id='addAiPromptModel' class='inputCard'>
	<form action='admin.php' method='post'>
		<h2>Add AI Prompt Models</h2>
		<input width='60%' type='text' name='addAiPromptModel' placeholder='example_gpt4all.bin'>
		<button class='button' type='submit'>Add Model</button>
	</form>
	</div>
</div>
<?PHP
echo "<div id='aiServerLibaryPaths' class='settingListCard'>\n";
echo "<h2>AI Text To Image Models</h2>\n";
echo "<pre>\n";
echo (file_get_contents("/etc/2web/ai/txt2imgModels.cfg"));
echo "</pre>\n";
echo "</div>";


echo "<div id='aiLibaryPaths' class='settingListCard'>";
echo "<h2>AI Text To Image Models</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/ai/txt2imgModels.d/*.cfg"));
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
				echo "	<button class='button' type='submit' name='remove_ai_txt2img_model' value='".$link."'>Remove Model</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
				//echo "</div>";
			}
		}
	}
}
?>
	<div id='add_ai_txt2img_model' class='inputCard'>
	<form action='admin.php' method='post'>
		<h2>Add AI Text To Image Models</h2>
		<input width='60%' type='text' name='add_ai_txt2img_model' placeholder='runwayml/stable-diffusion-v1-5'>
		<button class='button' type='submit'>Add Model</button>
	</form>
	</div>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
