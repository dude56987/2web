<?PHP
	########################################################################
	# 2web ai toolbox
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
	# load each of the ai prompt models
	$discoveredPrompt=False;
	$discoveredPromptToolboxData="";
	foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
		$niceDirectoryPath=str_replace(".bin","",$directoryPath);
		$discoveredPromptToolboxData .= "<option value='$directoryPath'>$niceDirectoryPath</option>\n";
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
	# load each of the ai models
	$discoveredMedia2Text=False;
	$discoveredMedia2TextData="";
	foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/subtitles/"),array(".","..")) as $directoryPath){
		if (strpos($directoryPath,"/")){
			$discoveredMedia2TextData.="<option value='$directoryPath'>$directoryPath</option>\n";
			$discoveredMedia2Text=True;
		}
	}

	echo "<div class='titleCard'>";
	echo "<details>";
	echo "<summary><h2>AI Tools</h2></summary>";
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
	if ($discoveredMedia2Text){
		# speech to text
		echo "<a class='showPageEpisode' href='/ai/media2text/'>";
		echo "	<h2>Dictaphone</h2>";
		echo "	<div class='aiIcon'>📠</div>";
		echo "</a>";
	}
	if ($discoveredMedia2Text){
		# speech to subtitle file
		echo "<a class='showPageEpisode' href='/ai/media2subs/'>";
		echo "	<h2>Dictaphone</h2>";
		echo "	<div class='aiIcon'>📹</div>";
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
	echo "</details>";
	echo "</div>";
?>
