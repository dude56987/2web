<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("ai2web");
?>
<?PHP
########################################################################
# 2web AI services index
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
ini_set('file_uploads', "On");
########################################################################
?>
<html class='randomFanart'>
<head>
	<script src='/2webLib.js'></script>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?php # Disable scaling of page elements ?>
	<meta meta name="viewport" content="width=device-width, initial-scale=0.4, maximum-scale=0.4, user-scalable=no" >
</head>
<body>
<?php
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
# load each of the ai prompt models
$discoveredPrompt=False;
$discoveredPromptData="";
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
	$niceDirectoryPath=str_replace(".bin","",$directoryPath);
	$discoveredPromptData .= "<option value='$directoryPath'>$niceDirectoryPath</option>\n";
	$discoveredPrompt=True;
}
# load each of the ai txt2txt models
$discoveredTxt2Txt=False;
$discoveredTxt2TxtData="";
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2txt/"),array(".","..")) as $directoryPath){
	$directoryPath=str_replace("--","/",$directoryPath);
	$directoryPath=str_replace("models/","",$directoryPath);
	$discoveredTxt2TxtData .= "<option value='$directoryPath'>$directoryPath</option>\n";
	$discoveredTxt2Txt=True;
}
# load each of the ai models
$discoveredImg2Img=False;
$discoveredImg2ImgData="";
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/img2img/"),array(".","..")) as $directoryPath){
	$directoryPath=str_replace("--","/",$directoryPath);
	$directoryPath=str_replace("models/","",$directoryPath);
	if (strpos($directoryPath,"/")){
		$discoveredImg2ImgData.="<option value='$directoryPath'>$directoryPath</option>\n";
		$discoveredImg2Img=True;
	}
}
# load each of the ai models
$discoveredTxt2Img=False;
$discoveredTxt2ImgData="";
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2img/"),array(".","..")) as $directoryPath){
	$directoryPath=str_replace("--","/",$directoryPath);
	$directoryPath=str_replace("models/","",$directoryPath);
	if (strpos($directoryPath,"/")){
		$discoveredTxt2ImgData.="<option value='$directoryPath'>$directoryPath</option>\n";
		$discoveredTxt2Img=True;
	}
}

echo "<div class='titleCard'>";
echo "<h2>AI Tools</h2>";
if ($discoveredPrompt){
	# ai prompting interface
	echo "<a class='showPageEpisode' href='/ai/prompt/'>";
	echo "	<h2>AI Prompting</h2>";
	echo "	<div class='aiIcon'>👽</div>";
	echo "</a>";
}
if ($discoveredTxt2Txt){
	#
	echo "<a class='showPageEpisode' href='/ai/txt2txt/'>";
	echo "	<h2>Text Generation</h2>";
	echo "	<div class='aiIcon'>✏️</div>";
	echo "</a>";
}
if ($discoveredTxt2Img){
	#
	echo "<a class='showPageEpisode' href='/ai/txt2img/'>";
	echo "	<h2>Image Generation</h2>";
	echo "	<div class='aiIcon'>🎨</div>";
	echo "</a>";
}
if ($discoveredImg2Img){
	#
	echo "<a class='showPageEpisode' href='/ai/img2img/'>";
	echo "	<h2>Image Editing</h2>";
	echo "	<div class='aiIcon'>🖌️</div>";
	echo "</a>";
}

echo "</div>";

if ($discoveredPrompt){
	# draw the threads discovered
	$promptIndex=array_diff(scanDir("/var/cache/2web/web/ai/prompt/"),array(".","..","index.php"));
	# shuffle the items in the index
	shuffle($promptIndex);
	#sort($promptIndex);
	# order newest prompts first
	$promptIndex=array_reverse($promptIndex);
	# split into pages and grab only the first page
	$promptIndex=array_chunk($promptIndex,6);
	if(count($promptIndex) > 0){
		$promptIndex=$promptIndex[0];
		# if there are any entries draw the random prompt questions widget
		echo "<div class='titleCard'>\n";
		echo "<h1>Random Prompts</h1>\n";
		foreach($promptIndex as $directoryPath){
			# if the hidden cfg file does not exist use this in the index
			if ( ! file_exists("prompt/".$directoryPath."/hidden.cfg")){
				echo "<a class='inputCard textList button' href='/ai/prompt/$directoryPath'>";
				echo file_get_contents("prompt/".$directoryPath."/prompt.cfg");
				echo "<div>🗨️ Responses: ";
				$finishedResponses=0;
				foreach(scandir("prompt/".$directoryPath."/") as $responseFileName){
					if(strpos($responseFileName,".txt") !== false){
						$finishedResponses += 1;
					}
				}
				echo "$finishedResponses";
				echo "/";
				echo file_get_contents("prompt/".$directoryPath."/versions.cfg");
				echo "</div>";
				# check for failures
				if (file_exists("prompt/".$directoryPath."/failures.cfg")){
					echo "<hr>";
					echo "⛔ Failures: ";
					echo file_get_contents("prompt/".$directoryPath."/failures.cfg");
					echo "<hr>";
				}
				echo "</a>";
			}
		}
		echo "</div>\n";
	}
}
if ($discoveredTxt2Img){
	# draw the threads discovered
	$promptIndex=array_diff(scanDir("/var/cache/2web/web/ai/txt2img/"),array(".","..","index.php"));
	# shuffle the items in the index
	shuffle($promptIndex);
	#sort($promptIndex);
	# order newest prompts first
	$promptIndex=array_reverse($promptIndex);
	# split into pages and grab only the first page
	$promptIndex=array_chunk($promptIndex,6);
	if(count($promptIndex) > 0){
		$promptIndex=$promptIndex[0];
		# if there are any entries draw the random prompt questions widget
		echo "<div class='titleCard'>\n";
		echo "<h1>Random Generated Images</h1>\n";
		foreach($promptIndex as $directoryPath){
			# if the hidden cfg file does not exist use this in the index
			if ( ! file_exists("txt2img/".$directoryPath."/hidden.cfg")){
				echo "<a class='inputCard textList button' href='/ai/txt2img/$directoryPath'>";
				echo file_get_contents("txt2img/".$directoryPath."/prompt.cfg");
				echo "<div>🖼️ Images: ";
				$finishedResponses=0;
				foreach(scandir("txt2img/".$directoryPath."/") as $responseFileName){
					if(strpos($responseFileName,".png") !== false){
						$finishedResponses += 1;
					}
				}
				echo "$finishedResponses";
				echo "/";
				echo file_get_contents("txt2img/".$directoryPath."/versions.cfg");
				echo "</div>";
				echo "</a>";
			}
		}
		echo "</div>\n";
	}
}
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
