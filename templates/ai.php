<?PHP
########################################################################
# 2web image viewer
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

if (array_key_exists("imageFileToEdit",$_FILES)){
	# img2img
	# this is a edit image upload
	#echo ($_POST['imageInputPrompt']);
	#echo ($_POST['imageNegativeInputPrompt']);
	#echo ($_POST['imageGenVersions']);
	#echo ($_POST['baseNegativePrompt']);
	#echo ($_POST['model']);
	# get the uploaded file object
	$fileName=$_FILES['imageFileToEdit']["tmp_name"];
	$fileOgName=$_FILES['imageFileToEdit']["full_path"];
	$fileSum=md5_file($fileName);

	if(strpos($fileOgName,".png")){
		$filePath="/var/cache/2web/web/ai/img2img/".$fileSum.".png";
	}else if(strpos($fileOgName,".jpg")){
		$filePath="/var/cache/2web/web/ai/img2img/".$fileSum.".jpg";
	}else{
		$filePath="";
		echo "<div class='errorBanner'>\n";
		echo "<hr>\n";
		echo "Error: only PNG and JPG files are supported for upload and editing!\n";
		echo "<hr>\n";
		echo "</div>\n";
	}
	if(is_file($filePath)){
		# save the file to the cache location
		move_uploaded_file($fileName,$filePath);

		# launch the process with a background scheduler
		$command = 'echo "';
		# one at a time queue, but launch from atq right away
		$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --bg --jobs 1 --id ai2web ';
		# default load order of models if found on system
		# check for loading custom LLM
		if (! is_dir("/var/cache/2web/web/ai/img2img/")){
			mkdir("/var/cache/2web/web/ai/img2img/");
		}
		if (! is_dir("/var/cache/2web/web/ai/img2img/".$fileSum."/")){
			mkdir("/var/cache/2web/web/ai/img2img/".$fileSum."/");
		}
		if (! is_file("/var/cache/2web/web/ai/img2img/".$fileSum."/prompt.cfg")){
			file_put_contents("/var/cache/2web/web/ai/img2img/".$fileSum."/prompt.cfg",$_POST["imageInputPrompt"]);
		}
		if (! is_file("/var/cache/2web/web/ai/img2img/".$fileSum."/negativePrompt.cfg")){
			file_put_contents("/var/cache/2web/web/ai/img2img/".$fileSum."/negativePrompt.cfg",$_POST["imageNegativeInputPrompt"]);
		}
		# always write start time
		file_put_contents("/var/cache/2web/web/ai/img2img/".$fileSum."/started.cfg",$_SERVER["REQUEST_TIME"]);
		if (! is_file("/var/cache/2web/web/ai/img2img/".$fileSum."/model.cfg")){
			file_put_contents("/var/cache/2web/web/ai/img2img/".$fileSum."/model.cfg",$_POST["model"]);
		}
		if (! is_file("/var/cache/2web/web/ai/img2img/".$fileSum."/baseNegativePrompt.cfg")){
			file_put_contents("/var/cache/2web/web/ai/img2img/".$fileSum."/baseNegativePrompt.cfg",$_POST["baseNegativePrompt"]);
		}
		if (array_key_exists("model",$_POST)){
			$command .= '/usr/bin/ai2web_img2img --set-model "'.$_POST["model"].'" ';
		}else{
			$command .= '/usr/bin/ai2web_img2img ';
		}
		$command .= '--output-dir "/var/cache/2web/web/ai/img2img/'.$fileSum.'/" ';
		if (array_key_exists("baseNegativePrompt",$_POST)){
			if ($_POST["baseNegativePrompt"] != "NONE"){
				$command .= '--base-negative-prompt "'.$_POST["baseNegativePrompt"].'" ';
			}
		}
		if (array_key_exists("imageGenVersions",$_POST)){
			if ($_POST["imageGenVersions"] != "NONE"){
				$command .= '--versions "'.$_POST["imageGenVersions"].'" ';
			}
		}
		if (array_key_exists("imageNegativeInputPrompt",$_POST)){
			if ($_POST["imageNegativeInputPrompt"] != "NONE"){
				if ($_POST["imageNegativeInputPrompt"] != ""){
					$command .= '--negative-prompt-plus "'.$_POST["imageNegativeInputPrompt"].'" ';
				}
			}
		}
		if (array_key_exists("imageFileToEdit",$_POST)){
			if ($_POST["imageFileToEdit"] != "NONE"){
				$command .= '--image-file "'.$filePath.'" ';
			}
		}
		if (array_key_exists("imageInputPrompt",$_POST)){
			if ($_POST["imageInputPrompt"] != "NONE"){
				$command .= '--prompt "'.$_POST["imageInputPrompt"].'" ';
			}
		}

		$command .= "' | at -M now";

		if ($_POST["debug"] == "yes"){
			echo "<div class='errorBanner'>\n";
			echo "<hr>\n";
			echo "DEBUG: SHELL EXECUTE: '$command'<br>\n";
			echo "<hr>\n";
			echo "</div>\n";
		}
		# create the image view script link
		if (! is_link("/var/cache/2web/web/ai/img2img/".$fileSum."/index.php")){
			symlink("/usr/share/2web/templates/ai_img.php" ,("/var/cache/2web/web/ai/img2img/".$fileSum."/index.php"));
		}
		# launch the command
		shell_exec($command);
		# delay 1 seconds to allow loading of database
		if(array_key_exists("HTTPS",$_SERVER)){
			if($_SERVER['HTTPS']){
				$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/img2img/".$fileSum."/?autoRefresh");
			}else{
				$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/img2img/".$fileSum."/?autoRefresh");
			}
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/img2img/".$fileSum."/?autoRefresh");
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
}else if (array_key_exists("imageInputPrompt",$_POST)){
	# text to image generation
	# txt2img

	$fileSumString  = ($_POST['imageInputPrompt']);
	$fileSumString .= ($_POST['imageNegativeInputPrompt']);
	$fileSumString .= ($_POST['baseNegativePrompt']);
	$fileSumString .= ($_POST['model']);
	$fileSumString .= ($_POST['imageWidth']);
	$fileSumString .= ($_POST['imageHeight']);

	$fileSum=md5($fileSumString);

	# launch the process with a background scheduler
	$command = "echo '";
	# one at a time queue, but launch from atq right away
	$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --bg --jobs 1 --id ai2web ';
	# default load order of models if found on system
	# check for loading custom LLM
	if (! is_dir("/var/cache/2web/web/ai/txt2img/")){
		mkdir("/var/cache/2web/web/ai/txt2img/");
	}
	if (! is_dir("/var/cache/2web/web/ai/txt2img/".$fileSum."/")){
		mkdir("/var/cache/2web/web/ai/txt2img/".$fileSum."/");
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/prompt.cfg")){
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/prompt.cfg",$_POST["imageInputPrompt"]);
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/negativePrompt.cfg")){
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/negativePrompt.cfg",$_POST["imageNegativeInputPrompt"]);
	}
	# always write start time
	file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/started.cfg",$_SERVER["REQUEST_TIME"]);
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/model.cfg")){
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/model.cfg",$_POST["model"]);
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/baseNegativePrompt.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/baseNegativePrompt.cfg",$_POST["baseNegativePrompt"]);
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/width.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/width.cfg",$_POST["imageWidth"]);
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/height.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/height.cfg",$_POST["imageHeight"]);
	}
	if (array_key_exists("model",$_POST)){
		$command .= '/usr/bin/ai2web_txt2img --set-model "'.$_POST["model"].'" ';
	}else{
		# default model loaded by prompt is groovy
		$command .= '/usr/bin/ai2web_txt2img ';
	}
	$command .= '--output-dir "/var/cache/2web/web/ai/txt2img/'.$fileSum.'/" ';
	if (array_key_exists("baseNegativePrompt",$_POST)){
		if ($_POST["baseNegativePrompt"] != "NONE"){
			$command .= '--base-negative-prompt "'.$_POST["baseNegativePrompt"].'" ';
		}
	}
	if (array_key_exists("imageGenVersions",$_POST)){
		if ($_POST["imageGenVersions"] != "NONE"){
			$command .= '--versions "'.$_POST["imageGenVersions"].'" ';
		}
	}

	if (is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg")){
		# increment existing versions file
		$foundVersions = file_get_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg");
		$foundVersions += $_POST["imageGenVersions"];
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg", $foundVersions);
	}else{
		file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/versions.cfg",$_POST["imageGenVersions"]);
	}

	if (array_key_exists("imageWidth",$_POST)){
		if ($_POST["imageWidth"] != "NONE"){
			if ($_POST["imageWidth"] != ""){
				$command .= '--width "'.$_POST["imageWidth"].'" ';
			}
		}
	}
	if (array_key_exists("imageHeight",$_POST)){
		if ($_POST["imageHeight"] != "NONE"){
			if ($_POST["imageHeight"] != ""){
				$command .= '--height "'.$_POST["imageHeight"].'" ';
			}
		}
	}
	if (array_key_exists("imageNegativeInputPrompt",$_POST)){
		if ($_POST["imageNegativeInputPrompt"] != "NONE"){
			if ($_POST["imageNegativeInputPrompt"] != ""){
				$command .= '--negative-prompt-plus "'.$_POST["imageNegativeInputPrompt"].'" ';
			}
		}
	}
	if (array_key_exists("imageInputPrompt",$_POST)){
		if ($_POST["imageInputPrompt"] != "NONE"){
			$command .= '--prompt "'.$_POST["imageInputPrompt"].'" ';
		}
	}

	$command .= "' | at -M now";

	if ($_POST["debug"] == "yes"){
		echo "<div class='errorBanner'>\n";
		echo "<hr>\n";
		echo "DEBUG: SHELL EXECUTE: '$command'<br>\n";
		echo "<hr>\n";
		echo "</div>\n";
	}
	# create the image view script link
	if (! is_link("/var/cache/2web/web/ai/txt2img/".$fileSum."/index.php")){
		symlink("/usr/share/2web/templates/ai_img.php" ,("/var/cache/2web/web/ai/txt2img/".$fileSum."/index.php"));
	}
	if (! is_file("/var/cache/2web/web/ai/txt2img/".$fileSum."/command.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2img/".$fileSum."/command.cfg",$command);
	}
	# launch the command
	shell_exec($command);
	# delay 1 seconds to allow loading of database
	if(array_key_exists("HTTPS",$_SERVER)){
		if($_SERVER['HTTPS']){
			$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/txt2img/".$fileSum."/?autoRefresh");
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2img/".$fileSum."/?autoRefresh");
		}
	}else{
		$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2img/".$fileSum."/?autoRefresh");
	}
	if ($_POST["debug"] == "yes"){
		echo "<p>".$redirectUrl."</p>\n";
		echo "<a class='button' href='/ai/'>Back To Main Index</a>\n";
		echo "</div>\n";
	}else{
		sleep(1);
		redirect($redirectUrl);
	}
}else if (array_key_exists("inputPrompt",$_POST)){
	if($_POST["inputPrompt"] == ""){
		echo "<div class='errorBanner'>\n";
		echo "<hr>\n";
		echo "Error in prompt: Blank prompts do nothing!\n";
		echo "<hr>\n";
		echo "</div>\n";
	}else{
		if ($_POST["debug"] == "yes"){
			echo "<div class='titleCard'>\n";
		}
		# launch the process with a background scheduler
		$command = 'echo "';
		# one at a time queue, but launch from atq right away
		#$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --retries 10 --jobs 1 --id ai2web ';
		$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --bg --jobs 1 --id ai2web ';
		# default load order of models if found on system
		# check for loading custom LLM
		if (array_key_exists("llm",$_POST)){
			$command .= '/usr/bin/ai2web_prompt --set-model '.$_POST["llm"].' ';
		}else if (file_exists("/var/cache/2web/downloads_ai/ggml-gpt4all-l13b-snoozy.bin")){
			$command .= '/usr/bin/ai2web_prompt --set-model ggml-gpt4all-l13b-snoozy.bin ';
		}else if (file_exists("/var/cache/2web/downloads_ai/ggml-gpt4all-j-v1.2-jazzy.bin")){
			$command .= '/usr/bin/ai2web_prompt --set-model ggml-gpt4all-j-v1.2-jazzy.bin ';
		}else if (file_exists("/var/cache/2web/downloads_ai/ggml-gpt4all-j-v1.3-groovy.bin")){
			$command .= '/usr/bin/ai2web_prompt --set-model ggml-gpt4all-j-v1.3-groovy.bin ';
		}else if (file_exists("/var/cache/2web/downloads_ai/ggml-mpt-7b-chat.bin")){
			$command .= '/usr/bin/ai2web_prompt --set-model ggml-mpt-7b-chat.bin ';
		}else if (file_exists("/var/cache/2web/downloads_ai/ggml-nous-gpt4-vicuna-13b.bin")){
			$command .= '/usr/bin/ai2web_prompt --set-model ggml-nous-gpt4-vicuna-13b.bin ';
		}else{
			# default model loaded by prompt is groovy
			$command .= '/usr/bin/ai2web_prompt ';
		}

		# check for custom personas
		if (array_key_exists("persona",$_POST)){
			if ($_POST["persona"] != "NONE"){
				$command .= '--load-persona '.$_POST["persona"].' ';
			}
		}
		# build the convoSum to be as unique as posible
		$_POST["threadSum"] = md5($_SERVER['REQUEST_TIME_FLOAT'].$_SERVER["HTTP_USER_AGENT"].gethostname());
		#$command .= '/usr/bin/ai2web_prompt ';
		if (array_key_exists("threadSum",$_POST)){
			$command .= '--thread-sum \"'.$_POST['threadSum'].'\" ';
		}

		$_POST['inputPrompt'] = str_replace("\n","",$_POST['inputPrompt']);
		$_POST['inputPrompt'] = str_replace("'","`",$_POST['inputPrompt']);
		$_POST['inputPrompt'] = str_replace('"',"`",$_POST['inputPrompt']);
		$_POST['inputPrompt'] = escapeShellCmd($_POST['inputPrompt']);
		# build the unique user agent string and convert it to a md5
		# - This is so the web interface will display the recent prompts submited by that user
		# - This must be seprate in order to allow searching database based on indivual user access
		# - THIS IS NOT PRIVATE, its kinda private, but this is for home server use.
		$tempUserAgent = $_SERVER["HTTP_USER_AGENT"];
		if (array_key_exists("REMOTE_USER",$_SERVER)){
			$tempUserAgent .= $_SERVER["REMOTE_USER"];
		}
		$tempUserAgent = md5($tempUserAgent);
		# use the threadSum for the userSum
		$tempUserAgent = $_POST["threadSum"];
		# if in a thread
		if (array_key_exists("thread",$_POST)){
			$tempUserAgent = $_POST["thread"];
		}
		# generate the command setting the user agent, otherwise user agent will be set to unknown
		$command .=	'--user-agent '.$tempUserAgent.' ';
		$command .=	'--one-prompt \"'.$_POST['inputPrompt'].'\"" ';
		$command .= ' | at -M now';
		#$command .= ' | batch';
		# launch the command
		shell_exec($command);
		if ($_POST["debug"] == "yes"){
			echo "SHELL EXECUTE '$command'<br>\n";
			# check if the file is cached as a conversation
		}
		# delay 1 seconds to allow loading of database
		if(array_key_exists("HTTPS",$_SERVER)){
			if($_SERVER['HTTPS']){
				$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/?thread=".$tempUserAgent);
			}else{
				$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/?thread=".$tempUserAgent);
			}
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/?thread=".$tempUserAgent);
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
}
?>
<html class='randomFanart'>
<head>
	<script src='/2web.js'></script>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?php # Disable scaling of page elements ?>
	<meta meta name="viewport" content="width=device-width, initial-scale=0.4, maximum-scale=0.4, user-scalable=no" >
</head>
<body>
<?php
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");

$databasePath = "/var/cache/2web/web/ai/convos.db";
if (file_exists($databasePath)){
	# load database
	$databaseObj = new SQLite3($databasePath);
	# set the timeout to 1 minute since most webbrowsers timeout loading before this
	$databaseObj->busyTimeout(60000);
	################################################################################
	if (array_key_exists("loadConvo",$_GET)){
		#echo "Loading convo<br>";
		# load the convo by using the convoSum
		$convoToken = $_GET["loadConvo"];

		$setOnce=True;

		#echo ('select * from "questions" where convoSum = \''.$convoToken.'\';<br>');

		$result = $databaseObj->query('select * from "questions" where convoSum = \''.$convoToken.'\';');

		#echo (var_dump($result));

		$foundQuestionsCount= 0;

		# fetch each row data individually and display results
		while($row = $result->fetchArray()){
			$foundQuestionsCount += 1;
			#echo (var_dump($row));
			if ($row['anwserSum'] == "UNANWSERED"){
				# check for set once
				if ($setOnce){
					# refresh if block refresh is not set
					if (! array_key_exists("blockRefresh",$_GET)){
						// using javascript, reload the webpage every 60 seconds, time is in milliseconds
						echo "<script>\n";
						echo "setTimeout(function() { window.location=window.location;},(1000*10));\n";
						echo "</script>\n";
						# lockout the set once
						$setOnce = False;
					}
				}

				$data = json_decode($row['convoToken']);
				// read the index entry
				// write the index entry
				echo "<div class='titleCard'>\n";
				#echo "<details>";
				#echo "<subject>";
				echo "<h1>Question ".$row['convoSum']."</h1>\n ";
				if (! array_key_exists("blockRefresh",$_GET)){
					echo "<img class='localPulse' src='/pulse.gif'>\n";
					echo "<a class='button' href='?blockRefresh&".$_SERVER['QUERY_STRING']."'>⏹️ Stop Auto Page Refresh</a>\n";
					$executionTime = $_SERVER['REQUEST_TIME_FLOAT'] - ($row['renderTime']) ;
					$executionMinutes = floor($executionTime / 60);
					$executionSeconds = floor($executionTime - floor($executionMinutes * 60));
					# check for numbers less than 10
					if ($executionMinutes < 10){
						$executionMinutes = "0$executionMinutes" ;
					}
					if ($executionSeconds < 10){
						$executionSeconds = "0$executionSeconds" ;
					}
					# list the time elapsed so far
					echo "<div class='elapsedTime'>Elapsed Time $executionMinutes:$executionSeconds</div>\n";
				}else{
					if (array_key_exists("thread",$_GET)){
						echo "<a class='button' href='?loadConvo=".$_GET['loadConvo']."&thread=".$_GET['thread']."'>▶️  Auto Refresh Until Answered</a>\n";
					}else{
						echo "<a class='button' href='?loadConvo=".$_GET['loadConvo']."'>▶️  Auto Refresh Until Answered</a>\n";
					}
				}
				#echo "</subject>";
				echo "<table>\n";
				echo "<tr>\n";
				echo "<th>Role</th>\n";
				echo "<th>Message</th>\n";
				echo "</tr>\n";
				foreach($data as $line){
					#echo "dump: ".var_dump($line)."<br>\n";
					#echo "role: ".$line->role."<br>\n";
					#echo "content: ".$line->content."<br>\n";
					# read each line of the conversation
					echo "<tr>\n";
					echo "<td>\n";
					echo ($line->role."\n");
					echo "</td>\n";
					echo "<td class='chatLine'>\n";
					#echo "<pre>";
					echo str_replace("\n","<br>",$line->content."\n");
					#echo "</pre>";
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";

				#echo var_dump($data);
				#echo "</details>";
				echo "</div>\n";
				flush();
				ob_flush();
			}else{
				# - if this is not an unanwsered question then load the anwser from the anwsersum of the question
				# - anwser links should be handled diffrently
				#echo ('select * from "anwsers" where convoSum = \''.$row['anwserSum'].'\';');
				$anwserResult = $databaseObj->query('select * from "anwsers" where convoSum = \''.$row['anwserSum'].'\';');
				while($anwserRow = $anwserResult->fetchArray()){
					echo "<div class='titleCard'>\n";
					echo "<h1>Anwser ".$anwserRow['convoSum']."</h1>\n ";
					$anwserData = json_decode($anwserRow['convoToken']);
					echo "<table>\n";
					echo "<tr>\n";
					echo "<th>Role</th>\n";
					echo "<th>Message</th>\n";
					echo "</tr>\n";
					foreach($anwserData as $anwserLine){
						# read each line of the conversation
						echo "<tr>\n";
						echo "<td>\n";
						echo ($anwserLine->role."\n");
						echo "</td>\n";
						echo "<td class='chatLine'>\n";
						if (array_key_exists("preformated",$_GET)){
							echo "<pre class='aiPreformatedResponse'>\n";
							echo $anwserLine->content;
							echo "</pre>\n";
						}else{
							while (strpos($anwserLine->content,"\n\n\n")){
								$anwserLine->content = str_replace("\n\n\n","\n\n",$anwserLine->content."\n");
							}
							echo str_replace("\n","<br>",$anwserLine->content."\n");
						}
						echo "</td>\n";
						echo "</tr>\n";
					}
					echo "</table>\n";

					echo "<form method='post'>\n";
					# store the json of the conversation as the input json
					if (array_key_exists("thread",$_GET)){
						echo "<input class='aiLog' name='thread' value='".$_GET['thread']."' type='text' readonly>\n";
					}
					echo "<input class='aiLog' name='convoSum' value='".$anwserRow['convoSum']."' type='text' readonly>\n";
					# add the prompt to the log
					echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>\n";
					echo "<input class='aiSubmit' type='submit' value='Prompt'>\n";
					echo "</form>\n";

					echo "</div>\n";
					flush();
					ob_flush();
				}
			}
		}
		if($foundQuestionsCount <= 0){
			# this is a anwser so load it from the anwsers
			$anwserResult = $databaseObj->query('select * from "anwsers" where convoSum = \''.$convoToken.'\';');

			while($anwserRow = $anwserResult->fetchArray()){
				echo "<div class='titleCard'>\n";
				echo "<h1>Anwser ".$anwserRow['convoSum']."</h1>\n ";
				$anwserData = json_decode($anwserRow['convoToken']);
				echo "<table>\n";
				echo "<tr>\n";
				echo "<th>Role</th>\n";
				echo "<th>Message</th>\n";
				echo "</tr>\n";
				foreach($anwserData as $anwserLine){
					# read each line of the conversation
					echo "<tr>\n";
					echo "<td>\n";
					echo ($anwserLine->role."\n");
					echo "</td>\n";
					echo "<td class='chatLine'>\n";
					if (array_key_exists("preformated",$_GET)){
						echo "<pre class='aiPreformatedResponse'>";
						echo $anwserLine->content;
						echo "</pre>\n";
					}else{
						# remove all double spaces
						while (strpos($anwserLine->content,"\n\n\n")){
							$anwserLine->content = str_replace("\n\n\n","\n\n",$anwserLine->content."\n");
						}
						# convert single spaces into html newlines
						echo str_replace("\n","<br>",$anwserLine->content."\n");
					}
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";

				echo "<form method='post'>\n";
				if (array_key_exists("thread",$_GET)){
					echo "<input class='aiLog' name='thread' value='".$_GET['thread']."' type='text' readonly>\n";
				}
				# store the json of the conversation as the input json
				echo "<input class='aiLog' name='convoSum' value='".$anwserRow['convoSum']."' type='text' readonly>\n";
				# add the prompt to the log
				echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>\n";
				echo "<input class='aiSubmit' type='submit' value='Prompt'>\n";
				echo "</form>\n";

				echo "</div>\n";
				flush();
				ob_flush();
			}
		}
	}else{
		echo "<div class='titleCard'>\n";
		echo "	<h1>What Can Text <sup>a</sup>I Do?</h1>\n";
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

		echo "<div class='titleCard'>\n";
		echo "<h1>Start New Conversation</h1>\n";

		echo "<div>\n";

		echo "<form method='post'>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " LLM:\n";
		echo "<select name='llm'>\n";
		# load each of the ai models
		$discoveredTxt2Txt=False;
		foreach(array_diff(scanDir("/var/cache/2web/downloads_ai/"),array(".","..")) as $directoryPath){
			echo "<option value='$directoryPath'>$directoryPath</option>\n";
			$discoveredTxt2Txt=True;
		}
		echo "</select>\n";
		echo "</span>\n";


		echo "<span class='groupedMenuItem'>\n";
		echo " PERSONA:\n";
		echo "<select name='persona'>\n";
		echo "<option value='NONE' selected>NONE</option>\n";
		# load each of the ai models
		foreach(array_diff(scanDir("/etc/2web/ai/personas/"),array(".","..")) as $directoryPath){
			$directoryPath=str_replace(".cfg","",$directoryPath);
			echo "<option value='$directoryPath'>$directoryPath</option>\n";
		}
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>Debug:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>\n";

		echo "</div>\n";

		#
		if($discoveredTxt2Txt){
			echo "<hr>\n";
		}else{
			#
			echo "<div class='errorBanner'>\n";
			echo "<hr>\n";
			echo "Error: No language models discovered in '/var/cache/2web/downloads_ai/', Install models to make them available in the web interface.\n";
			echo "<hr>\n";
			echo "</div>\n";
		}

		echo "<textarea class='aiPrompt' name='inputPrompt' placeholder='Text generation prompt...'></textarea>";
		echo "<button class='aiSubmit' type='submit'><span class='footerText'>Prompt</span> ↩️</button>";
		echo "</form>\n";
		echo "</div>\n";

		echo "<div class='titleCard'>\n";
		echo "	<h1>What Can Image <sup>a</sup>I Do?</h1>\n";
		echo "	<div class='listCard'>\n";
		$helpTexts=Array();
		$helpTexts=array_merge($helpTexts,["Generate images from text descriptions"]);
		$helpTexts=array_merge($helpTexts,["Generate images from text tags"]);
		$helpTexts=array_merge($helpTexts,["Different Models generate extremely different results"]);
		$helpTexts=array_merge($helpTexts,["Edit existing images"]);
		$helpTexts=array_merge($helpTexts,["Cartoonize images"]);
		$helpTexts=array_merge($helpTexts,["Convert to anime"]);
		$helpTexts=array_merge($helpTexts,["Change objects in images"]);
		$helpTexts=array_merge($helpTexts,["Insert a rough sketch and fill in the details"]);
		$helpTexts=array_merge($helpTexts,["Remove or add details into the image"]);
		$helpTexts=array_merge($helpTexts,["Enhance zoom the size of the image"]);
		$helpTexts=array_merge($helpTexts,["Describe what you don't want using negative prompts"]);
		foreach($helpTexts as $helpText ){
			echo "		<div class='inputCard textList'>\n";
			echo "			<p>$helpText</p>\n";
			echo "		</div>\n";
		}
		echo "	</div>\n";
		echo "</div>\n";

		# draw the image generator
		echo "<div class='titleCard'>\n";
		echo "<h1>Generate a image from text</h1>\n";
		echo "<form method='post' enctype='multipart/form-data'>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " Models:\n";
		echo "<select name='model'>\n";
		# load each of the ai models
		foreach(array_diff(scanDir("/var/cache/2web/downloads_ai_image/"),array(".","..")) as $directoryPath){
			$directoryPath=str_replace("--","/",$directoryPath);
			$directoryPath=str_replace("models/","",$directoryPath);
			if (strpos($directoryPath,"/")){
				echo "<option value='$directoryPath'>$directoryPath</option>\n";
			}
		}
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " Base Negative Prompt:";
		echo "<select name='baseNegativePrompt'>\n";
		# load each of the ai models
		foreach(array_diff(scanDir("/etc/2web/ai/negative_prompts/"),array(".","..")) as $directoryPath){
			#echo var_dump($directoryPath);
			#echo ($directoryPath);
			#echo ($directoryPath)."\n";
			$directoryPath=str_replace(".cfg","",$directoryPath);
			#echo ($directoryPath)."\n";
			echo "<option value='$directoryPath'>$directoryPath</option>\n";
		}
		echo "<option value='none'>None</option>\n";
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo "Versions: <input class='imageVersionsInput' type='number' min='1' max='10' value='1' name='imageGenVersions' placeholder='Number of versions to draw'>";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo "Width : <input class='imageWidth' type='number' min='360' max='1920' value='360' name='imageWidth' placeholder='Image Width in pixels'>";
		echo "</span>\n";
		echo "<span class='groupedMenuItem'>\n";
		echo "Height : <input class='imageHeight' type='number' min='240' max='1080' value='240' name='imageHeight' placeholder='Image Height in pixels'>";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>Debug:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>";

		echo "<hr>\n";

		echo "<textarea class='imageInputPrompt' name='imageInputPrompt' placeholder='Image generation prompt, Tags...'></textarea>";
		echo "<textarea class='imageNegativeInputPrompt' name='imageNegativeInputPrompt' placeholder='Negative Prompt, Tags...'></textarea>";
		echo "<input class='aiSubmit' type='submit' value='Prompt'>";
		echo "</form>";
		echo "</div>";

		# draw edit image prompt
		echo "<div class='titleCard'>";
		echo "<h1>Upload and Edit a Image</h1>";

		echo "<form method='post' enctype='multipart/form-data'>";
		echo "<span class='groupedMenuItem'>\n";
		echo " Models:\n";
		echo "<select name='model'>\n";
		# load each of the ai models
		$discoveredImg2Img=False;
		foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/img2img/"),array(".","..")) as $directoryPath){
			$directoryPath=str_replace("--","/",$directoryPath);
			$directoryPath=str_replace("models/","",$directoryPath);
			if (strpos($directoryPath,"/")){
				echo "<option value='$directoryPath'>$directoryPath</option>\n";
				$discoveredImg2Img=True;
			}
		}
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " Base Negative Prompt:";
		echo "<select name='baseNegativePrompt'>\n";
		# load each of the ai models
		#echo var_dump(scanDir("/etc/2web/ai/negative_prompts/"));
		$discoveredNegativePrompts=False;
		foreach(array_diff(scanDir("/etc/2web/ai/negative_prompts/"),array(".","..")) as $directoryPath){
			#echo var_dump($directoryPath);
			#echo ($directoryPath);
			#echo ($directoryPath)."\n";
			$directoryPath=str_replace(".cfg","",$directoryPath);
			#echo ($directoryPath)."\n";
			echo "<option value='$directoryPath'>$directoryPath</option>\n";
			$discoveredNegativePrompts=True;
		}
		echo "<option value='none'>None</option>\n";
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo "Versions: <input class='imageVersionsInput' type='number' min='1' max='10' value='3' name='imageGenVersions' placeholder='Number of versions to draw'>";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>Debug:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>";

		if ($discoveredImg2Img){
			echo "<hr>\n";
		}else{
			#
			echo "<div class='errorBanner'>\n";
			echo "<hr>\n";
			echo "Error: No editing language models discovered, Install models to make them available in the web interface.\n";
			echo "<hr>\n";
			echo "</div>\n";
		}

		$changeScript="document.getElementById('imagePreview').src = window.URL.createObjectURL(this.files[0])";
		echo "<input class='' type='file' id='imageUploadForm' name='imageFileToEdit' onchange=\"$changeScript\">";
		echo "<img class='imageUploadPreview' id='imagePreview' src=''>";
		echo "<hr>";
		echo "<textarea class='imageInputPrompt' name='imageInputPrompt' placeholder='How to edit the image...'></textarea>";
		echo "<textarea class='imageNegativeInputPrompt' name='imageNegativeInputPrompt' placeholder='Negative Prompt, Tags...'></textarea>";
		echo "<input class='aiSubmit' type='submit' value='Prompt'>";
		echo "</form>";
		echo "</div>";

		echo "<div class='settingListCard'>";

		if (array_key_exists("thread",$_GET)){
			echo "<h1>Thread ".$_GET["thread"]."</h1>";

			echo "<div>";
			if (array_key_exists("autoRefresh",$_GET)){
				echo "<hr>";
				echo " <img class='globalPulse' src='/pulse.gif'> <a class='button' href='?'>⏹️ Disable Auto Refresh</a>";
				echo "<hr>";
			}else{
				echo "<hr>";
				echo "<a class='button' href='?autoRefresh&".$_SERVER['QUERY_STRING']."'>▶️  Auto Refresh</a>";
				echo "<hr>";
			}
			echo "</div>";

			$setOnce=True;

			$tempUserAgent = $_SERVER["HTTP_USER_AGENT"];
			if (array_key_exists("REMOTE_USER",$_SERVER)){
				$tempUserAgent .= $_SERVER["REMOTE_USER"];
			}
			$tempUserAgent = md5($tempUserAgent);
			# use the thread as the user agent to link thread after prompting
			$tempUserAgent = $_GET["thread"];


			#echo "<div class='titleCard'>";
			#echo "<hr>debug 1 = '".$_SERVER["HTTP_USER_AGENT"].$_SERVER["REMOTE_ADDR"]."' = ".$tempUserAgent."<hr>\n";
			#echo "</div>\n";

			$userConvos = $databaseObj->query('select convoSum from "users" where userAgent = \''.$tempUserAgent.'\' order by ROWID DESC;');

			#echo "<div class='titleCard'>";
			#echo "User Convos = ";
			#echo var_dump($userConvos);
			#echo "</div>\n";

			while($userInfo = $userConvos->fetchArray()){
				#echo "<div class='titleCard'>\n";
				#echo "user convos, convo = ";
				#echo var_dump($userInfo);
				#echo "\n";
				#echo "</div>\n";

				#echo "<div class='titleCard'>\n";
				#echo "dump 1 convoSum = ";
				#echo var_dump($userInfo['convoSum']);
				#echo "\n";
				#echo "</div>\n";

				#echo "<div class='titleCard'>\n";
				#echo "dump 1 convoSum = ";
				#echo $userInfo['convoSum'];
				#echo "\n";
				#echo "</div>\n";

				#$anwserSum =
				#$anwserResult = $databaseObj->query('select * from "anwsers" where convoSum = \''.$row['anwserSum'].'\';');

				#echo "<div class='titleCard'>\n";
				#echo ('select * from "anwsers" where convoSum = \''.$userInfo['convoSum'].'\' order by renderTime DESC limit 100;'."\n");
				#echo "</div>\n";

				#echo "<div class='titleCard'>\n";
				#echo ('select * from "questions" where convoSum = \''.$userInfo['convoSum'].'\' order by renderTime DESC limit 100;');
				#echo "</div>\n";

				$result = $databaseObj->query('select * from "questions" where convoSum = \''.$userInfo['convoSum'].'\' order by renderTime DESC limit 100;');

				#$result = $databaseObj->query('select * from "questions" order by renderTime DESC limit 100;');

				# fetch each row data individually and display results
				while($row = $result->fetchArray()){

					#echo "<div class='titleCard'>\n";
					#echo "dump 2 row = ";
					#echo var_dump($row);
					#echo "\n";
					#echo "</div>\n";

					#echo "<div class='titleCard'>\n";
					#echo "dump 2 row anwserSum = ";
					#echo var_dump($row["anwserSum"]);
					#echo "\n";
					#echo "</div>\n";

					#echo "<div class='titleCard'>\n";
					#echo "dump 2 row convoToken = ";
					#echo var_dump($row["convoToken"]);
					#echo "\n";
					#echo "</div>\n";


					# if the question is unanwsered load the question
					if ($row['anwserSum'] == "UNANWSERED"){
						if ($setOnce){
							# refresh if block refresh is not set
							if (array_key_exists("autoRefresh",$_GET)){
								// using javascript, reload the webpage every 60 seconds, time is in milliseconds
								echo "<script>";
								echo "setTimeout(function() { window.location=window.location;},(1000*10));";
								echo "</script>";
								# lockout the set once
								$setOnce = False;
							}
						}

						#echo "<div class='titleCard'>\n";
						#echo "dump 2 part 2 row convoToken = ";
						#echo var_dump($row["convoToken"]);
						#echo "\n";
						#echo "</div>\n";


						$data = json_decode($row['convoToken']);
						// read the index entry
						// write the index entry
						echo "<div class='inputCard'>";
						#echo "<details>";
						#echo "<subject>";
						echo "<h2>";
						if (array_key_exists("thread",$_GET)){
							echo "<a href='?loadConvo=".$row['convoSum']."&thread=".$_GET['thread']."'>";
						}else{
							echo "<a href='?loadConvo=".$row['convoSum']."'>";
						}
						#echo "Question ".$row['convoSum']." ";
						echo "Question";
						echo "</a>";
						echo "</h2>";
						#echo "</subject>";
						echo "<table>";
						echo "<tr>";
						echo "<th>Role</th>";
						echo "<th>Message</th>";
						echo "</tr>";
						$messageData="";
						foreach($data as $line){
							if($line->role == "user"){
								# read each line of the conversation
								$messageData = "<tr>";
								$messageData .= "<td>";
								$messageData .= ($line->role."\n");
								$messageData .= "</td>";
								$messageData .= "<td class='chatLine'>";
								#messageData .= "<pre>";
								$messageData .= str_replace("\n","<br>",$line->content."\n");
								$messageData .= "</pre>";
								$messageData .= "</td>";
								$messageData .= "</tr>";
							}
						}
						echo $messageData;
						echo "</table>";

						echo "<div class=''>";
						echo "Total Responses:";
						echo floor(count($data)/2);
						echo "</div>";

						#echo var_dump($data);
						#echo "</details>";
						echo "</div>";
						flush();
						ob_flush();
					}else{
						# render out any anwsered questions found for this user agent string

						#echo "<div class='titleCard'>\n";
						#echo "userInfo = ";
						#echo var_dump($userInfo);
						#echo "\n";
						#echo "</div>\n";

						# select anwsers discovered in anwsersums
						$questionResult = $databaseObj->query('select * from "anwsers" where convoSum = \''.$row['anwserSum'].'\' order by renderTime DESC limit 100;');

						#echo "<div class='titleCard'>\n";
						#echo "result = ";
						#echo var_dump($questionResult);
						#echo "\n";
						#echo "</div>\n";

						# fetch each row data individually and display results
						while($anwserRow = $questionResult->fetchArray()){
							#echo var_dump($row)."<br>\n";
							$data = json_decode($anwserRow['convoToken']);
							// read the index entry
							// write the index entry
							echo "<div class='inputCard'>";
							#echo "<details>";
							#echo "<subject>";
							echo "<h2>";
							if (array_key_exists("thread",$_GET)){
								echo "<a href='?loadConvo=".$anwserRow['convoSum']."&thread=".$_GET["thread"]."'>";
							}else{
								echo "<a href='?loadConvo=".$anwserRow['convoSum']."'>";
							}
							#echo "Anwser ".$anwserRow['convoSum']."";
							echo "Anwser";
							echo "</a>";
							echo "</h2>";
							#echo "</subject>";
							echo "<table>";
							echo "<tr>";
							echo "<th>Role</th>";
							echo "<th>Message</th>";
							echo "</tr>";
							$lengthOfData=count($data);
							$dataCounter=0;
							$tempConvoData = "";
							foreach($data as $line){
								$dataCounter+=1;
								if($line->role == "user"){
									#echo "dump: ".var_dump($line)."<br>\n";
									#echo "role: ".$line->role."<br>\n";
									#echo "content: ".$line->content."<br>\n";
									# read each line of the conversation
									$tempConvoData = "<tr>";
									$tempConvoData .= "<td>";
									$tempConvoData .= ($line->role."\n");
									$tempConvoData .= "</td>";
									$tempConvoData .= "<td class='chatLine'>";
									# get the last character of the line
									$tempLineContent = substr($line->content,-1);
									#echo "templine content = ".var_dump($tempLineContent)."<br>\n";
									# if the end of the response is not puncuated
									if ( $dataCounter == $lengthOfData){
										if ( $line->role == "assistant" ){
											if ( ! ( ($tempLineContent == ".") || ($tempLineContent == "!") || ($tempLineContent == "?") ) ){
												# add a continue button
												$tempConvoData .= "<form class='aiContButton' method='post'>";
												# store the json of the conversation as the input json
												$tempConvoData .= "<input class='hidden' name='convoSum' value='".$anwserRow['convoSum']."' type='text' readonly>";
												# add the prompt to the log
												$tempConvoData .= "<textarea class='hidden' name='inputPrompt' readonly>Continue</textarea>";
												$tempConvoData .= "<input class='button' type='submit' value='Continue'>";
												$tempConvoData .= "</form>";

											}
										}
									}
									#echo "<pre>";
									$tempConvoData .= str_replace("\n","<br>",$line->content."\n");
									#echo "</pre>";
									$tempConvoData .= "</td>";
									$tempConvoData .= "</tr>";
								}
							}
							echo $tempConvoData;
							echo "</table>";

							echo "<div class=''>";
							echo "Total Responses:";
							echo floor(count($data)/2);
							echo "</div>";
							#echo "<form method='post'>";
							## store the json of the conversation as the input json
							#echo "<input class='aiLog' name='convoSum' value='".$row['convoSum']."' type='text' readonly>";
							## add the prompt to the log
							#echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
							#echo "<input class='aiSubmit' type='submit' value='Prompt'>";
							#echo "</form>";

							#echo var_dump($data);
							#echo "</details>";
							echo "</div>";
							flush();
							ob_flush();
						}
						#echo "</div>";
					}
				}
			}
		}
	}
}else{
	# draw the new conversation prompt
	echo "<div class='titleCard'>";
	echo "<h1>Start New Thread</h1>";
	echo "<form method='post'>";
	echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
	echo "<input class='aiSubmit' type='submit' value='Prompt'>";
	echo "</form>";
	echo "</div>";

	# draw edit image prompt
	echo "<div class='titleCard'>";
	echo "<h1>Upload and Edit a Image</h1>";
	echo "<form method='post' enctype='multipart/form-data'>";
	echo "<input class='aiPrompt' type='file' name='fileToUpload'>";
	echo "<input class='aiSubmit' type='submit' value='Prompt'>";
	echo "</form>";
	echo "</div>";

	# there is no database, DO not load
	#echo "<div class='titleCard'>";
	#echo "<h2>No Existing Conversations</h2>";
	#echo "<p>";
	#echo "No conversations have been started, Start a new conversation to see it here.";
	#echo "</p>";
	#echo "</div>";
}
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
