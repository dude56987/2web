<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("ai2web");
?>
<?PHP
########################################################################
# 2web AI image generation from text prompt interface
# Copyright (C) 2024	Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.	If not, see <http://www.gnu.org/licenses/>.
########################################################################
if (array_key_exists("imageInputPrompt",$_POST)){
	# prompt for image generation from text
	$fileSumString	= ($_POST['imageInputPrompt']);
	$fileSumString .= ($_POST['imageNegativeInputPrompt']);
	$fileSumString .= ($_POST['model']);
	# generate a sum
	$fileSum=md5($fileSumString);
	# create the directory to store the output in
	if (! is_dir("/var/cache/2web/web/ai/txt2img/")){
		mkdir("/var/cache/2web/web/ai/txt2img/");
	}
	if (! is_dir("/var/cache/2web/web/ai/txt2img/".$fileSum."/")){
		mkdir("/var/cache/2web/web/ai/txt2img/".$fileSum."/");
	}
	# always write start time
	file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/started.cfg",$_SERVER["REQUEST_TIME"]);
	# store the prompt
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/prompt.cfg")){
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/prompt.cfg",$_POST["imageInputPrompt"]);
	}
	# store the negative prompt
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/negativePrompt.cfg")){
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/negativePrompt.cfg",$_POST["imageNegativeInputPrompt"]);
	}
	# store the selected model
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/model.cfg")){
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/model.cfg",$_POST["model"]);
	}
	# set the thread hidden status
	if (array_key_exists("hidden",$_POST)){
		if ($_POST["hidden"] == "yes"){
			# if the post is set to hidden generate a hidden.cfg
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/hidden.cfg", "yes");
		}
	}
	# get the number of versions
	if (is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg")){
		# increment existing versions file
		$foundVersions = file_get_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg");
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2img/"),array(".","..",".locks")) as $directoryPath){
				if(is_dir("/var/cache/2web/downloads/ai/txt2img/".$directoryPath."/")){
					$foundVersions += 1;
				}
			}
		}else{
			$foundVersions = 1;
		}
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg", $foundVersions);
	}else{
		if ($_POST["model"] == "{ALL}"){
			$foundVersions = 0;
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2img/"),array(".","..",".locks")) as $directoryPath){
				if(is_dir("/var/cache/2web/downloads/ai/txt2img/".$directoryPath."/")){
					$foundVersions += 1;
				}
			}
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg",$foundVersions);
		}else{
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg","1");
		}
	}
	# create the command to be added to the queue
	$command = '';
	# disable all network access for the AI tool command
	# - Some AI tools have a telemetry problem even in offline mode
	$command .= '/usr/bin/unshare -n ';
	# build the model
	if (array_key_exists("model",$_POST)){
		# convert the model download name to the hugging face model name
		$modelName=str_replace("--","/",$_POST["model"]);
		$modelName=str_replace("models/","",$modelName);
		# attempt to disable telemetry
		$command .= "export DISABLE_TELEMETRY=YES;";
		$command .= "export HF_HUB_DISABLE_TELEMETRY=YES;";

		# set the model
		$command .= "/usr/bin/ai2web_txt2img --set-model '".$modelName."' ";
	}else{
		# by default use all available models
		$command .= '/usr/bin/ai2web_txt2img "{ALL}" ';
	}
	$command .= "--output-dir '/var/cache/2web/web/ai/txt2img/".$fileSum."/' ";
	# generate a version
	$command .= "--versions '1' ";
	# cleanup the negative prompt so it will work correctly
	$_POST["imageNegativeInputPrompt"] =	str_replace("'","",$_POST["imageNegativeInputPrompt"]);

	# load up the negative prompt file
	$command .= "--negative-prompt-file '/var/cache/2web/web/ai/txt2img/".$fileSum."/negativePrompt.cfg' ";
	# load up the written prompt file
	$command .= "--prompt-file '/var/cache/2web/web/ai/txt2img/".$fileSum."/prompt.cfg' ";
	# create the image view script link
	if (! is_link("/var/cache/2web/web/ai/txt2img/".$fileSum."/index.php")){
		symlink("/usr/share/2web/templates/ai_img.php" ,("/var/cache/2web/web/ai/txt2img/".$fileSum."/index.php"));
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/command.cfg")){
		if ($_POST["model"] == "{ALL}"){
			$combinedFileData = "";
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2img/"),array(".","..",".locks")) as $directoryPath){
				# only load valid directory models, ignore the temp files
				if (is_dir("/var/cache/2web/downloads/ai/txt2img/".$directoryPath)){
					# fix the path in the command
					$directoryPath=str_replace("--","/",$directoryPath);
					$directoryPath=str_replace("models/","",$directoryPath);
					# add the command to the command config
					$newCommand=str_replace("{ALL}","$directoryPath",$command);
					# add the filtered line
					$combinedFileData .= $newCommand.";\n";
				}
			}
			# write the combined commands used to generate all the prompts
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/command.cfg",$combinedFileData);
		}else{
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/command.cfg",$command);
		}
	}
	# if the model is set to all
	if ($_POST["model"] == "{ALL}"){
		foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2img/"),array(".","..",".locks")) as $directoryPath){
			# check that this is a directory
			if (is_dir("/var/cache/2web/downloads/ai/txt2img/".$directoryPath."/")){
				# cleanup the model name
				$directoryPath=str_replace("--","/",$directoryPath);
				$directoryPath=str_replace("models/","",$directoryPath);
				# replace all in the command
				$newCommand=str_replace("{ALL}","$directoryPath",$command);
				# add the command to the queue
				addToQueue("single",$newCommand);
			}
		}
	}else{
		# add the command to the queue
		addToQueue("single",$command);
	}
	# build redirect URL
	if(array_key_exists("HTTPS",$_SERVER)){
		if($_SERVER['HTTPS']){
			$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/txt2img/".$fileSum."/?autoRefresh");
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2img/".$fileSum."/?autoRefresh");
		}
	}else{
		$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2img/".$fileSum."/?autoRefresh");
	}
	if (array_key_exists("debug",$_POST)){
		if ($_POST["debug"] == "yes"){
			echo "<p>".$redirectUrl."</p>\n";
			echo "<a class='button' href='/ai/'>Back To Main Index</a>\n";
			echo "</div>\n";
		}else{
			sleep(1);
			redirect($redirectUrl);
		}
	}else{
		sleep(1);
		redirect($redirectUrl);
	}
}

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
$discoveredPromptData .= "<option value='{ALL}'>all</option>\n";
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/txt2img/"),array(".","..",".locks")) as $directoryPath){
	if(is_dir("/var/cache/2web/downloads/ai/txt2img/".$directoryPath."/")){
		$niceDirectoryPath=str_replace("--","/",$directoryPath);
		$niceDirectoryPath=str_replace("models/","",$niceDirectoryPath);
		$discoveredPromptData .= "<option value='$directoryPath'>$niceDirectoryPath</option>\n";
		$discoveredPrompt=True;
	}
}
# add the toolbox to the top of the page
include("/usr/share/2web/templates/ai_toolbox.php");

if ($discoveredImg2Img || $discoveredTxt2Img){
	echo "<div class='titleCard'>\n";
	echo "	<h1>What Can Image <sup class='simpleSup'>a</sup>I Do?</h1>\n";
	echo "	<div class='listCard'>\n";
	$helpTexts=Array();
	$helpTexts=array_merge($helpTexts,["Generate images from text descriptions"]);
	$helpTexts=array_merge($helpTexts,["Generate images from using comma seprated tags"]);
	$helpTexts=array_merge($helpTexts,["Add 'in x style' to emulate a artistic style"]);
	$helpTexts=array_merge($helpTexts,["Use more descriptive prompts for more detailed images"]);
	$helpTexts=array_merge($helpTexts,["Any tags or descriptions entered into the negative prompt will NOT be in the image"]);
	$helpTexts=array_merge($helpTexts,["Add 'Most Popular' or 'Highest Voted' as tags to improve image quality"]);
	$helpTexts=array_merge($helpTexts,["Different Models generate extremely different results"]);
	$helpTexts=array_merge($helpTexts,["Describe what you don't want using negative prompts"]);
	$helpTexts=array_merge($helpTexts,["You may have to generate a lot of images to find one you like"]);
	shuffle($helpTexts);
	foreach($helpTexts as $helpText ){
		echo "		<div class='inputCard textList'>\n";
		echo "			<p>$helpText</p>\n";
		echo "		</div>\n";
	}
	echo "	</div>\n";
	echo "</div>\n";
}

if ($discoveredTxt2Img){
	# build the prompt form
	echo "<div class='titleCard'>\n";
	echo "<h1>Generate Image From Text Prompt 🎨</h1>\n";

	echo "<div>\n";

	echo "<form method='post'>\n";

	echo "<span title='What Large Language Model would you like to use to generate anwsers to your prompt?'>";
	echo "<span class='groupedMenuItem'>\n";
	echo " LLM:\n";

	echo "<select name='model' class='dropBox'>\n";
	echo $discoveredPromptData;
	echo "</select>\n";

	echo "</span>\n";
	echo "</span>\n";

	echo "<span title='Hide the prompt output from the public indexes. Anyone can still access it with a direct link though.'>";
	echo "<span class='groupedMenuItem'>🥸 Hidden</span>:<input class='checkbox' type='checkbox' name='hidden' value='yes'></input></span>\n";
	echo "</span>";

	echo "<span title='Do not touch bugs! This is only for developers.'>";
	echo "<span class='groupedMenuItem'> 🐛:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>\n";
	echo "</span>";

	echo "</div>\n";
	echo "</span>";

	echo "<textarea class='imageInputPrompt' name='imageInputPrompt' placeholder='Image generation prompt, Tags...' maxlength='120'></textarea>";
	echo "<textarea class='imageNegativeInputPrompt' name='imageNegativeInputPrompt' placeholder='Negative Prompt, Tags...'  maxlength='120'></textarea>";

	echo "<button title='Submit the prompt to generate responses.' class='aiSubmit' type='submit'><span class='footerText'>Prompt</span> ↩️</button>";

	echo "</form>\n";
	echo "</div>\n";

	# draw the threads discovered
	$promptIndex=array_diff(scanDir("/var/cache/2web/web/ai/txt2img/"),array(".","..","index.php"));

	# generate an array where the keys are the file modification dates of the the directories listed
	$sortedPromptIndex=Array();
	# read each directory in the list
	foreach($promptIndex as $directoryPath){
		# get the file modification time
		$modificationDate=filemtime($directoryPath);
		# add the path to the array with the key as the file modification time
		$sortedPromptIndex[$modificationDate]=$directoryPath;
	}
	# sort the array by the key values
	ksort($sortedPromptIndex);
	# replace the original array with the sorted one
	$promptIndex=$sortedPromptIndex;

	# order newest prompts first
	$promptIndex=array_reverse($promptIndex);
	# if any previous prompts are found
	if (count($promptIndex) > 0){
		echo "<div class='titleCard'>\n";
		echo "<h1>Previous Prompts</h1>\n";
		# split into pages and grab only the first page
		$promptIndex=array_chunk($promptIndex,10);
		$totalPages=( count($promptIndex) - 1);
		# grab the page if the page number is set
		if (array_key_exists("page",$_GET)){
			# decrement page number since array 0 is page 1
			$promptIndex=$promptIndex[$_GET['page']];
		}else{
			$_GET["page"]=0;
			$promptIndex=$promptIndex[0];
		}
		foreach($promptIndex as $directoryPath){
			# if the hidden cfg file does not exist use this in the index
			if ( ! file_exists($directoryPath."/hidden.cfg")){
				echo "<a class='inputCard textList button' href='/ai/txt2img/$directoryPath'>";
				echo file_get_contents($directoryPath."/prompt.cfg");
				echo "<div>🖼️ Images: ";
				$finishedResponses=0;
				foreach(scandir($directoryPath."/") as $responseFileName){
					if(strpos($responseFileName,".png") !== false){
						$finishedResponses += 1;
					}
				}
				echo "$finishedResponses";
				echo "/";
				echo file_get_contents($directoryPath."/versions.cfg");
				echo "</div>";

				# check for failures
				if (file_exists($directoryPath."/failures.cfg")){
					echo "<hr>";
					echo "Failures: ";
					echo file_get_contents($directoryPath."/failures.cfg");
					echo "<hr>";
				}

				echo "</a>";
			}
		}
		echo "<div class='listCard'>";
		if (array_key_exists("page",$_GET)){
			# build the page buttons
			if($_GET["page"] > 0){
				# add previous page button
				echo "<a class='button' href='?page=".($_GET['page'] - 1)."'>Previous</a>";
			}
		}
		# add the pages index at the bottom of the page
		foreach(range(0,$totalPages) as $indexCounter){
			if ( $_GET["page"] == $indexCounter){
				echo "<a class='button activeButton' href='?page=$indexCounter'>".($indexCounter + 1)."</a>";
			}else{
				echo "<a class='button' href='?page=$indexCounter'>".($indexCounter + 1)."</a>";
			}
		}
		if (array_key_exists("page",$_GET)){
			if($_GET["page"] < $totalPages){
				# add next page button
				echo "<a class='button' href='?page=".($_GET['page'] + 1)."'>Next</a>";
			}
		}else{
			echo "<a class='button' href='?page=1'>Next</a>";
		}
		echo "</div>\n";

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
