<?PHP
########################################################################
# 2web AI prompt interface
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
ini_set('display_errors', 1);
ini_set('file_uploads', "On");
# add the base php libary
include("/usr/share/2web/2webLib.php");

if (array_key_exists("debug",$_POST)){
	echo "<div class='errorBanner'>\n";
	echo "<hr>\n";
	echo (var_dump($_POST));
	echo "<hr>\n";
	echo "</div>\n";
}

if (array_key_exists("prompt",$_POST)){
	# text prompting interface for gpt4all
	# prompt

	$fileSumString  = ($_POST['prompt']);
	$fileSumString .= ($_POST['model']);
	#$fileSumString .= ($_POST['temperature']);

	$fileSum=md5($fileSumString);
	#$fileSum=$_SERVER["REQUEST_TIME"].$fileSum;

	# launch the process with a background scheduler
	$command = "echo '";
	# one at a time queue, but launch from atq right away
	$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';
	# default load order of models if found on system
	# check for loading custom LLM
	if (! is_dir("/var/cache/2web/web/ai/prompt/")){
		mkdir("/var/cache/2web/web/ai/prompt/");
	}
	if (! is_dir("/var/cache/2web/web/ai/prompt/".$fileSum."/")){
		mkdir("/var/cache/2web/web/ai/prompt/".$fileSum."/");
	}

	if (! is_file("/var/cache/2web/web/ai/prompt/".$fileSum."/prompt.cfg")){
		file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/prompt.cfg",$_POST["prompt"]);
	}

	# always write start time
	file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/started.cfg",$_SERVER["REQUEST_TIME"]);
	if (! is_file("/var/cache/2web/web/ai/prompt/".$fileSum."/model.cfg")){
		file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/model.cfg",$_POST["model"]);
	}
	if (array_key_exists("model",$_POST)){
		# set the model
		$command .= '/usr/bin/ai2web_prompt --set-model "'.$_POST["model"].'" ';
	}else{
		# use the default model
		$command .= '/usr/bin/ai2web_prompt ';
	}
	$command .= '--output-dir "/var/cache/2web/web/ai/prompt/'.$fileSum.'/" ';
	if (array_key_exists("versions",$_POST)){
		if ($_POST["versions"] != "NONE"){
			#$command .= '--versions "'.$_POST["versions"].'" ';
			$command .= '--versions "1" ';
		}
	}

	if (array_key_exists("hidden",$_POST)){
		if ($_POST["hidden"] == "yes"){
			# if the post is set to hidden generate a hidden.cfg
			file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/hidden.cfg", "yes");
		}
	}

	if (is_file("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg")){
		# increment existing versions file
		$foundVersions = file_get_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg");
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
				$foundVersions += 1;
			}
		}else{
			$foundVersions += $_POST["versions"];
		}
		file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg", $foundVersions);
	}else{
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
				$foundVersions += 1;
			}
			file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg",$foundVersions);
		}else{
			file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg",$_POST["versions"]);
		}
	}

	# cleanup the prompt so it will work correctly
	$_POST["prompt"] =	str_replace("'","",$_POST["prompt"]);

	if (array_key_exists("prompt",$_POST)){
		if ($_POST["prompt"] != "NONE"){
			$command .= '--one-prompt "'.$_POST["prompt"].'" ';
		}
	}

	$command .= "' | at -M now";
	# create the image view script link
	if (! is_link("/var/cache/2web/web/ai/prompt/".$fileSum."/index.php")){
		symlink("/usr/share/2web/templates/ai_thread.php" ,("/var/cache/2web/web/ai/prompt/".$fileSum."/index.php"));
	}
	if (! is_file("/var/cache/2web/web/ai/prompt/".$fileSum."/command.cfg")){
		if ($_POST["model"] == "{ALL}"){
			$combinedFileData = "";
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
				$combinedFileData .= str_replace("{ALL}","$directoryPath",$command).";\n";
			}
			# write the combined commands used to generate all the prompts
			file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/command.cfg",$combinedFileData);
		}else{
			file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/command.cfg",$command);
		}
	}
	# launch a job on the queue for each version
	foreach(range(1,$_POST["versions"]) as $index){
		# if the model is set to all
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
				if ($_POST["debug"] == "yes"){
					echo "<div class='errorBanner'>\n";
					echo "<hr>\n";
					echo "DEBUG: SHELL EXECUTE: '$command'<br>\n";
					echo "<hr>\n";
					echo "</div>\n";
				}
				# for each model found launch a new command
				shell_exec(str_replace("{ALL}","\"$directoryPath\"",$command));
			}
		}else{
			if ($_POST["debug"] == "yes"){
				echo "<div class='errorBanner'>\n";
				echo "<hr>\n";
				echo "DEBUG: SHELL EXECUTE: '$command'<br>\n";
				echo "<hr>\n";
				echo "</div>\n";
			}
			# launch the command
			shell_exec($command);
		}
	}
	# delay 1 seconds to allow loading of database
	if(array_key_exists("HTTPS",$_SERVER)){
		if($_SERVER['HTTPS']){
			$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/prompt/".$fileSum."/?autoRefresh");
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/prompt/".$fileSum."/?autoRefresh");
		}
	}else{
		$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/prompt/".$fileSum."/?autoRefresh");
	}
	if ($_POST["debug"] == "yes"){
		echo "<p>".$redirectUrl."</p>\n";
		echo "<a class='button' href='/ai/'>Back To Main Index</a>\n";
		echo "</div>\n";
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
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
	$niceDirectoryPath=str_replace(".bin","",$directoryPath);
	$discoveredPromptData .= "<option value='$directoryPath'>$niceDirectoryPath</option>\n";
	$discoveredPrompt=True;
}
# add the toolbox to the top of the page
include("/usr/share/2web/templates/ai_toolbox.php");

if ($discoveredPrompt){
	echo "<div class='titleCard'>\n";
	echo "	<h1>What Can Text <sup class='simpleSup'>a</sup>I Do?</h1>\n";
	echo "	<div class='listCard'>\n";
	$helpTexts=Array();
	$helpTexts=array_merge($helpTexts,["Ask me anything."]);
	$helpTexts=array_merge($helpTexts,["Summarize the following text. X"]);
	$helpTexts=array_merge($helpTexts,["Write a essay several paragraphs long about X."]);
	$helpTexts=array_merge($helpTexts,["Describe X"]);
	$helpTexts=array_merge($helpTexts,["Give me the full recipe and steps to cook X."]);
	$helpTexts=array_merge($helpTexts,["Ask me to write a poem about X."]);
	$helpTexts=array_merge($helpTexts,["Create a top ten list of X."]);
	$helpTexts=array_merge($helpTexts,["What ideas exist in X that can be applied to Y?"]);
	$helpTexts=array_merge($helpTexts,["Write a song as X about Y."]);
	$helpTexts=array_merge($helpTexts,["Take the text after this and translate it into X."]);
	$helpTexts=array_merge($helpTexts,["If you tell me I'm a expert in a field before your question, I will respond as if I am."]);
	$helpTexts=array_merge($helpTexts,["If you tell me I am in a profession, I will respond as if I am."]);
	$helpTexts=array_merge($helpTexts,["Type 'Continue' to get me to complete unfinished responses."]);
	$helpTexts=array_merge($helpTexts,["I'm also a chatbot, just talk to me. I can pretend to be a person."]);
	$helpTexts=array_merge($helpTexts,["Randomness of 0 is deterministic and 10 is completely random."]);
	$helpTexts=array_merge($helpTexts,["I only know what has been loaded into me. Subjects considered taboo or controversial may have not been included."]);
	$helpTexts=array_merge($helpTexts,["Some prompts will trigger premade responses. This was done by the creators of the AI model being used."]);
	$helpTexts=array_merge($helpTexts,["I only exist between when you ask the question and I answer it."]);
	$helpTexts=array_merge($helpTexts,["Remember, I lie. Experts call it hallucinating, I call it lying."]);
	foreach($helpTexts as $helpText ){
		echo "		<div class='inputCard textList'>\n";
		echo "			<p>$helpText</p>\n";
		echo "		</div>\n";
	}
	echo "	</div>\n";
	echo "</div>\n";
}
if ($discoveredPrompt){
	echo "<div class='titleCard'>\n";
	echo "<h1>Prompt an AI 👽</h1>\n";

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

	echo "<span title='How many anwsers would you like the AI to generate to your prompt?'>";
	echo "<span class='groupedMenuItem'>\n";
	echo "Unique Versions: <input class='numberBox' type='number' min='1' max='1' value='1' name='versions' placeholder='Number of versions to draw'>";
	echo "</span>\n";
	echo "</span>\n";

	#echo "<span class='groupedMenuItem'>\n";
	#echo "Randomness : <input class='' type='number' min='1' max='10' value='7' name='temperature' placeholder='Randomness'>";
	#echo "</span>\n";

	#echo "<span class='groupedMenuItem'>\n";
	#echo "Max Output : <input class='' type='number' min='10' max='1000' value='100' name='maxOutput' placeholder='Max characters to output'>";
	#echo "</span>\n";
	echo "<span title='Hide the prompt output from the public indexes. Anyone can still access it with a direct link though.'>";
	echo "<span class='groupedMenuItem'>🥸 Hidden</span>:<input class='checkbox' type='checkbox' name='hidden' value='yes'></input></span>\n";
	echo "</span>";

	echo "<span title='Do not touch bugs! This is only for developers.'>";
	echo "<span class='groupedMenuItem'> 🐛<span class='footerText'> Debug</span>:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>\n";
	echo "</span>";

	echo "</div>\n";
	echo "</span>";

	echo "<textarea title='Input the prompt text here.' class='aiPrompt' name='prompt' placeholder='Text prompt...'></textarea>";
	echo "<button title='Submit the prompt to generate responses.' class='aiSubmit' type='submit'><span class='footerText'>Prompt</span> ↩️</button>";

	echo "</form>\n";
	echo "</div>\n";

	# draw the threads discovered
	$promptIndex=array_diff(scanDir("/var/cache/2web/web/ai/prompt/"),array(".","..","index.php"));
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
				echo "<a class='inputCard textList' href='/ai/prompt/$directoryPath'>";
				echo file_get_contents($directoryPath."/prompt.cfg");
				echo "<div>Responses: ";
				$finishedResponses=0;
				foreach(scandir($directoryPath."/") as $responseFileName){
					if(strpos($responseFileName,".txt") !== false){
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
