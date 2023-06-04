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
	$command .= '/usr/bin/ai2web_prompt --set-model ggml-gpt4all-l13b-snoozy.bin ';
	#$command .= '/usr/bin/ai2web_prompt ';
	if (array_key_exists("convoSum",$_POST)){
		$command .= '--input-token \"'.$_POST['convoSum'].'\" ';
	}
	$_POST['inputPrompt'] = str_replace("\n","",$_POST['inputPrompt']);
	$_POST['inputPrompt'] = str_replace("'","`",$_POST['inputPrompt']);
	$_POST['inputPrompt'] = str_replace('"',"`",$_POST['inputPrompt']);
	$_POST['inputPrompt'] = escapeShellCmd($_POST['inputPrompt']);
	$command .=	'--one-prompt \"'.$_POST['inputPrompt'].'\""';
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
	if($_SERVER['HTTPS']){
		$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/");
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
	<meta meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" >
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
					echo " <img src='/spinner.gif'> <a class='button' href='?blockRefresh".$_SERVER['QUERY_STRING']."'>⏹️ Stop Auto Page Refresh</a>";
				}else{
					echo "<a class='button' href='?'>▶️  Auto Refresh Until Answered</a>";
				}
				echo "</h1>";
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
						echo str_replace("\n","<br>",$anwserLine->content."\n");
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
					echo str_replace("\n","<br>",$anwserLine->content."\n");
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

		#$result = $databaseObj->query('select * from "anwsers" where convoSum = \''.$convoToken.'\';');

		## fetch each row data individually and display results
		#while($row = $result->fetchArray()){
		#	#echo var_dump($row)."<br>\n";
		#	$data = json_decode($row['convoToken']);
		#	// read the index entry
		#	// write the index entry
		#	echo "<div class='titleCard'>";
		#	#echo "<details>";
		#	#echo "<subject>";
		#	echo "<h1>Anwser ".$row['convoSum']." ✅</h1>";
		#	#echo "</subject>";
		#	echo "<table>";
		#	echo "<tr>";
		#	echo "<th>Role</th>";
		#	echo "<th>Message</th>";
		#	echo "</tr>";
		#	foreach($data as $line){
		#		#echo "dump: ".var_dump($line)."<br>\n";
		#		#echo "role: ".$line->role."<br>\n";
		#		#echo "content: ".$line->content."<br>\n";
		#		# read each line of the conversation
		#		echo "<tr>";
		#		echo "<td>";
		#		echo ($line->role."\n");
		#		echo "</td>";
		#		echo "<td class='chatLine'>";
		#		#echo "<pre>";
		#		echo str_replace("\n","<br>",$line->content."\n");
		#		#echo "</pre>";
		#		echo "</td>";
		#		echo "</tr>";
		#	}
		#	echo "</table>";

		#	echo "<form method='post'>";
		#	# store the json of the conversation as the input json
		#	echo "<input class='aiLog' name='convoSum' value='".$row['convoSum']."' type='text' readonly>";
		#	# add the prompt to the log
		#	echo "<textarea class='aiPrompt' name='inputPrompt'></textarea>";
		#	echo "<input class='aiSubmit' type='submit' value='Prompt'>";
		#	echo "</form>";

		#	#echo var_dump($data);
		#	#echo "</details>";
		#	echo "</div>";
		#	flush();
		#	ob_flush();
		#}
	}else{
		echo "<div class='titleCard'>";
		echo "	<h1>What Can <sup>a</sup>I Do?</h1>";
		echo "	<div class='listCard'>";
		$helpTexts=Array();
		$helpTexts=array_merge($helpTexts,["Ask me to summarize a text"]);
		$helpTexts=array_merge($helpTexts,["Write a essay several paragraphs long about x."]);
		$helpTexts=array_merge($helpTexts,["Describe X"]);
		$helpTexts=array_merge($helpTexts,["Give me the full recipe and steps to cook x."]);
		$helpTexts=array_merge($helpTexts,["Ask me to write a poem about x."]);
		$helpTexts=array_merge($helpTexts,["Create a top ten list of x."]);
		$helpTexts=array_merge($helpTexts,["What ideas exist in x that can be applied to y?"]);
		$helpTexts=array_merge($helpTexts,["Write a song as x about y."]);
		$helpTexts=array_merge($helpTexts,["Take the text after this and translate it into x."]);
		$helpTexts=array_merge($helpTexts,["If you tell me I'm a expert in a field before your question, I will respond as if I am a expert."]);
		$helpTexts=array_merge($helpTexts,["If you tell me I am in a profession, I will respond as if I am."]);
		$helpTexts=array_merge($helpTexts,["Type 'Continue' to get me to complete unfinished responses."]);
		$helpTexts=array_merge($helpTexts,["I only exist between when you ask the question and I answer it."]);
		$helpTexts=array_merge($helpTexts,["I'm also a chatbot, just talk to me. I can pretend to be a person."]);
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
			echo " <img src='/spinner.gif'> <a class='button' href='?'>⏹️ Disable Auto Refresh</a>";
			echo "<hr>";
		}else{
			echo "<hr>";
			echo "<a class='button' href='?autoRefresh&".$_SERVER['QUERY_STRING']."'>▶️  Auto Refresh</a>";
			echo "<hr>";
		}
		echo "</div>";

		$setOnce=True;
		$result = $databaseObj->query('select * from "questions" order by renderTime DESC limit 100;');
		# fetch each row data individually and display results
		while($row = $result->fetchArray()){
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

				$data = json_decode($row['convoToken']);
				// read the index entry
				// write the index entry
				echo "<div class='inputCard'>";
				#echo "<details>";
				#echo "<subject>";
				echo "<h2>";
				echo "<a href='?loadConvo=".$row['convoSum']."'>";
				echo "Question ".$row['convoSum']." ";
				echo "</a>";
				#if (! array_key_exists("blockRefresh",$_GET)){
				#	echo " <img src='/spinner.gif'> <a class='button' href='?blockRefresh'>⏹️</a>";
				#}else{
				#	echo "<a class='button' href='?'>▶️ </a>";
				#}
				echo "</h2>";
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
					# break loop for homepage preview
					break;
				}
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
			}
		}
		$result = $databaseObj->query('select * from "anwsers" order by renderTime DESC limit 100;');

		#echo var_dump($result);//DEBUG
		#echo var_dump($result->fetchArray());//DEBUG

		# fetch each row data individually and display results
		while($row = $result->fetchArray()){
			#echo var_dump($row)."<br>\n";
			$data = json_decode($row['convoToken']);
			// read the index entry
			// write the index entry
			echo "<div class='inputCard'>";
			#echo "<details>";
			#echo "<subject>";
			echo "<h2>";
			echo "<a href='?loadConvo=".$row['convoSum']."'>";
			echo "Anwsered ".$row['convoSum']." ✅";
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
			foreach($data as $line){
				$dataCounter+=1;
				#echo "dump: ".var_dump($line)."<br>\n";
				#echo "role: ".$line->role."<br>\n";
				#echo "content: ".$line->content."<br>\n";
				# read each line of the conversation
				echo "<tr>";
				echo "<td>";
				echo ($line->role."\n");
				echo "</td>";
				echo "<td class='chatLine'>";
				# get the last character of the line
				$tempLineContent = substr($line->content,-1);
				#echo "templine content = ".var_dump($tempLineContent)."<br>\n";
				# if the end of the response is not puncuated
				if ( $dataCounter == $lengthOfData){
					if ( $line->role == "assistant" ){
						if ( ! ( ($tempLineContent == ".") || ($tempLineContent == "!") || ($tempLineContent == "?") ) ){
							# add a continue button
							echo "<form class='aiContButton' method='post'>";
							# store the json of the conversation as the input json
							echo "<input class='hidden' name='convoSum' value='".$row['convoSum']."' type='text' readonly>";
							# add the prompt to the log
							echo "<textarea class='hidden' name='inputPrompt' readonly>Continue</textarea>";
							echo "<input class='button' type='submit' value='Continue'>";
							echo "</form>";

						}
					}
				}
				#echo "<pre>";
				echo str_replace("\n","<br>",$line->content."\n");
				#echo "</pre>";
				echo "</td>";
				echo "</tr>";
				# break loop for homepage preview
				break;
			}
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
		echo "</div>";
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
