<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("ai2web");
?>
<?PHP
########################################################################
# 2web AI speaker interface
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
if (array_key_exists("debug",$_POST)){
	echo "<div class='errorBanner'>\n";
	echo "<hr>\n";
	echo (var_dump($_POST));
	echo "<hr>\n";
	echo "</div>\n";
}
if (array_key_exists("inputPrompt",$_POST)){
	# prompt for speak generation from text

	$fileSumString  = ($_POST['inputPrompt']);
	$fileSumString .= ($_POST['model']);
	#$fileSumString .= ($_POST['temperature']);

	$fileSum=md5($fileSumString);
	#$fileSum=$_SERVER["REQUEST_TIME"].$fileSum;

	# launch the process with a background scheduler
	$command = 'echo "';
	# one at a time queue, but launch from atq right away
	$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';
	# default load order of models if found on system
	# check for loading custom LLM
	if (! is_dir("/var/cache/2web/web/ai/speak/")){
		mkdir("/var/cache/2web/web/ai/speak/");
	}
	if (! is_dir("/var/cache/2web/web/ai/speak/".$fileSum."/")){
		mkdir("/var/cache/2web/web/ai/speak/".$fileSum."/");
	}

	if (! is_file("/var/cache/2web/web/ai/speak/".$fileSum."/prompt.cfg")){
		file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/prompt.cfg",$_POST["speakInputPrompt"]);
	}

	# always write start time
	file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/started.cfg",$_SERVER["REQUEST_TIME"]);
	if (! is_file("/var/cache/2web/web/ai/speak/".$fileSum."/model.cfg")){
		file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/model.cfg",$_POST["model"]);
	}
	if (array_key_exists("model",$_POST)){
		# convert the model download name to the hugging face model name
		#$modelName=str_replace("models--","/",$_POST["model"]);
		$modelName=str_replace("--","/",$_POST["model"]);
		$modelName=str_replace("models/","",$modelName);
		# set the model
		$command .= "/usr/bin/ai2web_speak --set-model '".$modelName."' ";
	}else{
		# use the default model
		$command .= '/usr/bin/ai2web_speak ';
	}
	$command .= "--output-dir '/var/cache/2web/web/ai/speak/".$fileSum."/' ";
	# generate a version
	$command .= "--versions '1' ";

	if (array_key_exists("hidden",$_POST)){
		if ($_POST["hidden"] == "yes"){
			# if the post is set to hidden generate a hidden.cfg
			file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/hidden.cfg", "yes");
		}
	}

	if (is_file("/var/cache/2web/web/ai/speak/".$fileSum."/versions.cfg")){
		# increment existing versions file
		$foundVersions = file_get_contents("/var/cache/2web/web/ai/speak/".$fileSum."/versions.cfg");
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/speak/"),array(".","..")) as $directoryPath){
				if(is_dir("/var/cache/2web/downloads/ai/speak/".$directoryPath."/")){
					$foundVersions += 1;
				}
			}
		}else{
			$foundVersions = 1;
		}
		file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/versions.cfg", $foundVersions);
	}else{
		if ($_POST["model"] == "{ALL}"){
			$foundVersions = 0;
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/speak/"),array(".","..")) as $directoryPath){
				if(is_dir("/var/cache/2web/downloads/ai/speak/".$directoryPath."/")){
					$foundVersions += 1;
				}
			}
			file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/versions.cfg",$foundVersions);
		}else{
			file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/versions.cfg","1");
		}
	}

	# load up the written prompt file
	$command .= "--prompt-file '/var/cache/2web/web/ai/speak/".$fileSum."/prompt.cfg' ";

	$command .= '" | at -M now';
	# create the speak view script link
	if (! is_link("/var/cache/2web/web/ai/speak/".$fileSum."/index.php")){
		symlink("/usr/share/2web/templates/ai_img.php" ,("/var/cache/2web/web/ai/speak/".$fileSum."/index.php"));
	}
	if (! is_file("/var/cache/2web/web/ai/speak/".$fileSum."/command.cfg")){
		if ($_POST["model"] == "{ALL}"){
			$combinedFileData = "";
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/speak/"),array(".","..")) as $directoryPath){
				# only load valid directory models, ignore the temp files
				if (is_dir("/var/cache/2web/downloads/ai/speak/".$directoryPath)){
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
			file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/command.cfg",$combinedFileData);
		}else{
			file_put_contents("/var/cache/2web/web/ai/speak/".$fileSum."/command.cfg",$command);
		}
	}
	# if the model is set to all
	if ($_POST["model"] == "{ALL}"){
		foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/speak/"),array(".","..")) as $directoryPath){
			# check that this is a directory
			if (is_dir("/var/cache/2web/downloads/ai/speak/".$directoryPath."/")){
				# cleanup the model name
				$directoryPath=str_replace("--","/",$directoryPath);
				$directoryPath=str_replace("models/","",$directoryPath);
				# replace all in the command
				$newCommand=str_replace("{ALL}","$directoryPath",$command);
				# print debug info
				if (array_key_exists("debug",$_POST)){
					if ($_POST["debug"] == "yes"){
						echo "<div class='errorBanner'>\n";
						echo "<hr>\n";
						echo "DEBUG: SHELL EXECUTE: '$newCommand'<br>\n";
						echo "<hr>\n";
						echo "</div>\n";
					}
				}
				# for each model found launch a new command
				shell_exec($newCommand);
			}
		}
	}else{
		if (array_key_exists("debug",$_POST)){
			if ($_POST["debug"] == "yes"){
				echo "<div class='errorBanner'>\n";
				echo "<hr>\n";
				echo "DEBUG: SHELL EXECUTE: '$command'<br>\n";
				echo "<hr>\n";
				echo "</div>\n";
			}
		}
		# launch the command
		shell_exec($command);
	}
	# delay 1 seconds to allow loading of database
	if(array_key_exists("HTTPS",$_SERVER)){
		if($_SERVER['HTTPS']){
			$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/speak/".$fileSum."/?autoRefresh");
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/speak/".$fileSum."/?autoRefresh");
		}
	}else{
		$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/speak/".$fileSum."/?autoRefresh");
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
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/speak/"),array(".","..")) as $directoryPath){
	if(is_dir("/var/cache/2web/downloads/ai/speak/".$directoryPath."/")){
		$niceDirectoryPath=str_replace("--","/",$directoryPath);
		$niceDirectoryPath=str_replace("models/","",$niceDirectoryPath);
		$discoveredPromptData .= "<option value='$directoryPath'>$niceDirectoryPath</option>\n";
		$discoveredPrompt=True;
	}
}

# add the toolbox to the top of the page
include("/usr/share/2web/templates/ai_toolbox.php");

echo "<div class='titleCard'>\n";
echo "	<h1>What Can speak <sup class='simpleSup'>a</sup>I Do?</h1>\n";
echo "	<div class='listCard'>\n";
$helpTexts=Array();
$helpTexts=array_merge($helpTexts,["Generate voice from text input."]);
# shuffle the help data
shuffle($helpText);
foreach($helpTexts as $helpText ){
	echo "		<div class='inputCard textList'>\n";
	echo "			<p>$helpText</p>\n";
	echo "		</div>\n";
}
echo "	</div>\n";
echo "</div>\n";

# build the prompt form
echo "<div class='titleCard'>\n";
echo "<h1>Generate speak From Text Prompt 🎨</h1>\n";

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

echo "<textarea class='inputPrompt' name='inputPrompt' placeholder='Voice generation prompt, Tags...' maxlength='120'></textarea>";

echo "<button title='Submit the prompt to generate responses.' class='aiSubmit' type='submit'><span class='footerText'>Prompt</span> ↩️</button>";

echo "</form>\n";
echo "</div>\n";

# draw the threads discovered
$promptIndex=array_diff(scanDir("/var/cache/2web/web/ai/speak/"),array(".","..","index.php"));
#sort($promptIndex);

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
			echo "<a class='inputCard textList' href='/ai/speak/$directoryPath'>";
			echo file_get_contents($directoryPath."/prompt.cfg");
			echo "<div>🖼️ speaks: ";
			$finishedResponses=0;
			foreach(scandir($directoryPath."/") as $responseFileName){
				if(strpos($responseFileName,".wav") !== false){
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
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
