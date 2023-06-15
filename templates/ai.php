<?PHP
########################################################################
# 2web conference gpt
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
# add the base php libary
include("/usr/share/2web/2webLib.php");

if (array_key_exists("inputPrompt",$_POST)){
	if (array_key_exists("debug",$_GET)){
		echo "<div class='titleCard'>";
	}
	# launch the process with a background scheduler
	$command = 'echo "';
	# one at a time queue, but launch from atq right away
	#$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --retries 10 --jobs 1 --id ai2web ';
	$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --bg --jobs 1 --id ai2web ';
	# default load order of models if found on system
	if (file_exists("/var/cache/2web/downloads_ai/ggml-gpt4all-l13b-snoozy.bin")){
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
	#$command .= '/usr/bin/ai2web_prompt ';
	if (array_key_exists("convoSum",$_POST)){
		$command .= '--input-token \"'.$_POST['convoSum'].'\" ';
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
	$command .=	'--user-agent '.$tempUserAgent.' ';
	$command .=	'--one-prompt \"'.$_POST['inputPrompt'].'\"" ';
	$command .= ' | at -M now';
	#$command .= ' | batch';
	# launch the command
	shell_exec($command);
	if (array_key_exists("debug",$_GET)){
		echo "SHELL EXECUTE '$command'<br>";
		echo "<a class='button' href='/ai/'>Back To Main Index</a>";
		# check if the file is cached as a conversation
		echo "</div>";
	}
	# delay 1 seconds to allow loading of database
	if(array_key_exists("HTTPS",$_SERVER)){
		if($_SERVER['HTTPS']){
			$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/");
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/");
		}
	}else{
		$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/");
	}

	sleep(1);
	redirect($redirectUrl);
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
						echo "<script>";
						echo "setTimeout(function() { window.location=window.location;},(1000*10));";
						echo "</script>";
						# lockout the set once
						$setOnce = False;
					}
				}

				$data = json_decode($row['convoToken']);
				// read the index entry
				// write the index entry
				echo "<div class='titleCard'>";
				#echo "<details>";
				#echo "<subject>";
				echo "<h1>Question ".$row['convoSum']."</h1> ";
				if (! array_key_exists("blockRefresh",$_GET)){
					echo "<img class='localPulse' src='/pulse.gif'>";
					echo "<a class='button' href='?blockRefresh&".$_SERVER['QUERY_STRING']."'>⏹️ Stop Auto Page Refresh</a>";
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
					echo "<div class='elapsedTime'>ElapsedTime $executionMinutes:$executionSeconds</div>";
				}else{
					echo "<a class='button' href='?loadConvo=".$_GET['loadConvo']."'>▶️  Auto Refresh Until Answered</a>";
				}
				#echo "</subject>";
				echo "<table>";
				echo "<tr>";
				echo "<th>Role</th>";
				echo "<th>Message</th>";
				echo "</tr>";
				foreach($data as $line){
					#echo "dump: ".var_dump($line)."<br>\n";
					#echo "role: ".$line->role."<br>\n";
					#echo "content: ".$line->content."<br>\n";
					# read each line of the conversation
					echo "<tr>";
					echo "<td>";
					echo ($line->role."\n");
					echo "</td>";
					echo "<td class='chatLine'>";
					#echo "<pre>";
					echo str_replace("\n","<br>",$line->content."\n");
					#echo "</pre>";
					echo "</td>";
					echo "</tr>";
				}
				echo "</table>";

				#echo var_dump($data);
				#echo "</details>";
				echo "</div>";
				flush();
				ob_flush();
			}else{
				# - if this is not an unanwsered question then load the anwser from the anwsersum of the question
				# - anwser links should be handled diffrently
				#echo ('select * from "anwsers" where convoSum = \''.$row['anwserSum'].'\';');
				$anwserResult = $databaseObj->query('select * from "anwsers" where convoSum = \''.$row['anwserSum'].'\';');
				while($anwserRow = $anwserResult->fetchArray()){
					echo "<div class='titleCard'>";
					echo "<h1>Anwser ".$anwserRow['convoSum']."</h1> ";
					$anwserData = json_decode($anwserRow['convoToken']);
					echo "<table>";
					echo "<tr>";
					echo "<th>Role</th>";
					echo "<th>Message</th>";
					echo "</tr>";
					foreach($anwserData as $anwserLine){
						# read each line of the conversation
						echo "<tr>";
						echo "<td>";
						echo ($anwserLine->role."\n");
						echo "</td>";
						echo "<td class='chatLine'>";
						if (array_key_exists("preformated",$_GET)){
							echo "<pre class='aiPreformatedResponse'>";
							echo $anwserLine->content;
							echo "</pre>";
						}else{
							while (strpos($anwserLine->content,"\n\n\n")){
								$anwserLine->content = str_replace("\n\n\n","\n\n",$anwserLine->content."\n");
							}
							echo str_replace("\n","<br>",$anwserLine->content."\n");
						}
						echo "</td>";
						echo "</tr>";
					}
					echo "</table>";

					echo "<form method='post'>";
					# store the json of the conversation as the input json
					echo "<input class='aiLog' name='convoSum' value='".$anwserRow['convoSum']."' type='text' readonly>";
					# add the prompt to the log
					echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
					echo "<input class='aiSubmit' type='submit' value='Prompt'>";
					echo "</form>";

					echo "</div>";
					flush();
					ob_flush();
				}
			}
		}
		if($foundQuestionsCount <= 0){
			# this is a anwser so load it from the anwsers
			$anwserResult = $databaseObj->query('select * from "anwsers" where convoSum = \''.$convoToken.'\';');

			while($anwserRow = $anwserResult->fetchArray()){
				echo "<div class='titleCard'>";
				echo "<h1>Anwser ".$anwserRow['convoSum']."</h1> ";
				$anwserData = json_decode($anwserRow['convoToken']);
				echo "<table>";
				echo "<tr>";
				echo "<th>Role</th>";
				echo "<th>Message</th>";
				echo "</tr>";
				foreach($anwserData as $anwserLine){
					# read each line of the conversation
					echo "<tr>";
					echo "<td>";
					echo ($anwserLine->role."\n");
					echo "</td>";
					echo "<td class='chatLine'>";
					if (array_key_exists("preformated",$_GET)){
						echo "<pre class='aiPreformatedResponse'>";
						echo $anwserLine->content;
						echo "</pre>";
					}else{
						# remove all double spaces
						while (strpos($anwserLine->content,"\n\n\n")){
							$anwserLine->content = str_replace("\n\n\n","\n\n",$anwserLine->content."\n");
						}
						# convert single spaces into html newlines
						echo str_replace("\n","<br>",$anwserLine->content."\n");
					}
					echo "</td>";
					echo "</tr>";
				}
				echo "</table>";

				echo "<form method='post'>";
				# store the json of the conversation as the input json
				echo "<input class='aiLog' name='convoSum' value='".$anwserRow['convoSum']."' type='text' readonly>";
				# add the prompt to the log
				echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
				echo "<input class='aiSubmit' type='submit' value='Prompt'>";
				echo "</form>";

				echo "</div>";
				flush();
				ob_flush();
			}
		}
	}else{
		echo "<div class='titleCard'>";
		echo "	<h1>What Can <sup>a</sup>I Do?</h1>";
		echo "	<div class='listCard'>";
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
			echo "		<div class='inputCard textList'>";
			echo "			<p>$helpText</p>";
			echo "		</div>";
		}
		echo "	</div>";
		echo "</div>";

		echo "<div class='titleCard'>";
		echo "<h1>Start New Conversation</h1>";
		echo "<form method='post'>";
		#echo "<input class='aiLog' name='inputLog' type='textarea'>";
		echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
		echo "<input class='aiSubmit' type='submit' value='Prompt'>";
		echo "</form>";
		echo "</div>";

		echo "<div class='settingListCard'>";

		echo "<h1>Previous Prompts</h1>";

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
					echo "<a href='?loadConvo=".$row['convoSum']."'>";
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
						echo "<a href='?loadConvo=".$anwserRow['convoSum']."'>";
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
}else{
	# draw the new conversation prompt
	echo "<div class='titleCard'>";
	echo "<h1>Start New Conversation</h1>";
	echo "<form method='post'>";
	#echo "<input class='aiLog' name='inputLog' type='textarea'>";
	echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
	echo "<input class='aiSubmit' type='submit' value='Prompt'>";
	echo "</form>";
	echo "</div>";

	# there is no database, DO not load
	echo "<div class='titleCard'>";
	echo "<h2>No Existing Conversations</h2>";
	echo "<p>";
	echo "No conversations have been started, Start a new conversation to see it here.";
	echo "</p>";
	echo "</div>";
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
