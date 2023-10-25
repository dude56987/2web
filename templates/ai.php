<?PHP
########################################################################
# 2web AI services index
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
		$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';
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
}else if (array_key_exists("prompt",$_POST)){
	# text prompting interface for gpt4all
	# prompt

	$fileSumString  = ($_POST['prompt']);
	$fileSumString .= ($_POST['model']);
	$fileSumString .= ($_POST['temperature']);

	$fileSum=md5($fileSumString);
	$fileSum=$_SERVER["REQUEST_TIME"].$fileSum;

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

	if ($_POST["hidden"] == "yes"){
		# if the post is set to hidden generate a hidden.cfg
		file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/hidden.cfg", "yes");
	}

	if (array_key_exists("model",$_POST)){
		$command .= '/usr/bin/ai2web_prompt --set-model "'.$_POST["model"].'" ';
	}else{
		# default model loaded by prompt is groovy
		$command .= '/usr/bin/ai2web_prompt ';
	}
	$command .= '--output-dir "/var/cache/2web/web/ai/prompt/'.$fileSum.'/" ';
	if (array_key_exists("versions",$_POST)){
		if ($_POST["versions"] != "NONE"){
			$command .= '--versions "'.$_POST["versions"].'" ';
		}
	}

	if (is_file("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg")){
		# increment existing versions file
		$foundVersions = file_get_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg");
		$foundVersions += $_POST["versions"];
		file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg", $foundVersions);
	}else{
		file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/versions.cfg",$_POST["versions"]);
	}

	if (array_key_exists("prompt",$_POST)){
		if ($_POST["prompt"] != "NONE"){
			$command .= '--one-prompt "'.$_POST["prompt"].'" ';
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
	if (! is_link("/var/cache/2web/web/ai/prompt/".$fileSum."/index.php")){
		symlink("/usr/share/2web/templates/ai_thread.php" ,("/var/cache/2web/web/ai/prompt/".$fileSum."/index.php"));
	}
	if (! is_file("/var/cache/2web/web/ai/prompt/".$fileSum."/command.cfg")){
			file_put_contents("/var/cache/2web/web/ai/prompt/".$fileSum."/command.cfg",$command);
	}
	# launch the command
	shell_exec($command);
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
	$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';
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
		# create the directories with proper permissions for web access
		if ($_POST["debug"] == "yes"){
			echo "<div class='titleCard'>\n";
		}

		if (! is_dir("/var/cache/2web/web/ai/txt2txt/")){
			mkdir("/var/cache/2web/web/ai/txt2txt/");
		}

		# build the input prompt sum
		$questionSum = md5($_POST['inputPrompt']);

		#
		if (! is_dir("/var/cache/2web/web/ai/txt2txt/".$questionSum."/")){
			mkdir("/var/cache/2web/web/ai/txt2txt/".$questionSum."/");
		}

		# update the started time
		file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/started.cfg",$_SERVER["REQUEST_TIME"]);

		# create the response viewing script link
		if (! is_link("/var/cache/2web/web/ai/txt2txt/".$questionSum."/index.php")){
			symlink("/usr/share/2web/templates/ai_thread.php" ,("/var/cache/2web/web/ai/txt2txt/".$questionSum."/index.php"));
		}
		if (! is_file("/var/cache/2web/web/ai/txt2txt/".$questionSum."/prompt.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/prompt.cfg",$_POST["inputPrompt"]);
		}
		if (! is_file("/var/cache/2web/web/ai/txt2txt/".$questionSum."/started.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/started.cfg",  );
		}

		# launch the process with a background scheduler
		$command = "echo '";
		# one at a time queue, but launch from atqueue right away, leave in foreground to view active processes from atq command
		$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';

		# check for loading custom LLM


		# default model loaded by prompt is groovy
		$command .= '/usr/bin/ai2web_txt2txt ';


		$command .= '--output-dir "/var/cache/2web/web/ai/txt2txt/'.$questionSum.'/" ';

		# set the versions value for this prompt
		if (is_file("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg")){
			# increment existing versions file
			$foundVersions = file_get_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg");
			$foundVersions += $_POST["versions"];
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg", $foundVersions);
		}else{
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg",$_POST["versions"]);
		}
		# set the versions in the command
		$command .= '--versions "'.$_POST["versions"].'" ';

		# convert temperature
		if($_POST["temperature"] > 9){
			$command .= '--temp "1.0" ';
		}else if($_POST["temperature"] <= 0){
			$command .= '--temp "0.1" ';
		}else{
			$command .= '--temp "0.'.$_POST["temperature"].'" ';
		}

		$command .= '--max-length "'.$_POST["maxOutput"].'" ';

		$_POST['inputPrompt'] = str_replace("\n","",$_POST['inputPrompt']);
		$_POST['inputPrompt'] = str_replace("'","`",$_POST['inputPrompt']);
		$_POST['inputPrompt'] = str_replace('"',"`",$_POST['inputPrompt']);
		$_POST['inputPrompt'] = escapeShellCmd($_POST['inputPrompt']);
		# build the unique user agent string and convert it to a md5
		# - This is so the web interface will display the recent prompts submited by that user
		# - This must be seprate in order to allow searching database based on indivual user access
		# - THIS IS NOT PRIVATE, its kinda private, but this is for home server use.
		$tempUserAgent = md5($_POST["inputPrompt"]);
		# generate the command setting the user agent, otherwise user agent will be set to unknown
		$command .=	'--prompt "'.$_POST['inputPrompt'].'" '."'";
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
				$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/txt2txt/".$tempUserAgent."/?autoRefresh");
			}else{
				$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2txt/".$tempUserAgent."/?autoRefresh");
			}
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2txt/".$tempUserAgent."/?autoRefresh");
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
}else if (array_key_exists("inputQuestionPrompt",$_POST)){
	if($_POST["inputQuestionPrompt"] == ""){
		echo "<div class='errorBanner'>\n";
		echo "<hr>\n";
		echo "Error in prompt: Blank prompts do nothing!\n";
		echo "<hr>\n";
		echo "</div>\n";
	}else{
		# create the directories with proper permissions for web access
		if ($_POST["debug"] == "yes"){
			echo "<div class='titleCard'>\n";
		}

		if (! is_dir("/var/cache/2web/web/ai/txt2txt/")){
			mkdir("/var/cache/2web/web/ai/txt2txt/");
		}

		# build the input prompt sum
		$questionSum = md5($_POST['inputQuestionPrompt']);

		#
		if (! is_dir("/var/cache/2web/web/ai/txt2txt/".$questionSum."/")){
			mkdir("/var/cache/2web/web/ai/txt2txt/".$questionSum."/");
		}

		# update the started time
		file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/started.cfg",$_SERVER["REQUEST_TIME"]);

		# create the response viewing script link
		if (! is_link("/var/cache/2web/web/ai/txt2txt/".$questionSum."/index.php")){
			symlink("/usr/share/2web/templates/ai_thread.php" ,("/var/cache/2web/web/ai/txt2txt/".$questionSum."/index.php"));
		}
		if (! is_file("/var/cache/2web/web/ai/txt2txt/".$questionSum."/prompt.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/prompt.cfg",$_POST["inputQuestionPrompt"]);
		}
		if (! is_file("/var/cache/2web/web/ai/txt2txt/".$questionSum."/started.cfg")){
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/started.cfg",  );
		}

		# launch the process with a background scheduler
		$command = "echo '";
		# one at a time queue, but launch from atqueue right away, leave in foreground to view active processes from atq command
		$command .= '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';

		# call the CLI tool for generating anwsers
		$command .= '/usr/bin/ai2web_q2a ';

		$command .= '--output-dir "/var/cache/2web/web/ai/txt2txt/'.$questionSum.'/" ';

		# set the versions value for this prompt
		if (is_file("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg")){
			# increment existing versions file
			$foundVersions = file_get_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg");
			$foundVersions += $_POST["versions"];
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg", $foundVersions);
		}else{
			file_put_contents("/var/cache/2web/web/ai/txt2txt/".$questionSum."/versions.cfg",$_POST["versions"]);
		}
		# set the versions in the command
		$command .= '--versions "'.$_POST["versions"].'" ';

		# convert temperature
		if($_POST["temperature"] > 9){
			$command .= '--temp "1.0" ';
		}else if($_POST["temperature"] <= 0){
			$command .= '--temp "0.1" ';
		}else{
			$command .= '--temp "0.'.$_POST["temperature"].'" ';
		}

		$command .= '--max-length "'.$_POST["maxOutput"].'" ';

		$_POST['inputQuestionPrompt'] = str_replace("\n","",$_POST['inputQuestionPrompt']);
		$_POST['inputQuestionPrompt'] = str_replace("'","`",$_POST['inputQuestionPrompt']);
		$_POST['inputQuestionPrompt'] = str_replace('"',"`",$_POST['inputQuestionPrompt']);
		$_POST['inputQuestionPrompt'] = escapeShellCmd($_POST['inputQuestionPrompt']);
		# build the unique user agent string and convert it to a md5
		# - This is so the web interface will display the recent prompts submited by that user
		# - This must be seprate in order to allow searching database based on indivual user access
		# - THIS IS NOT PRIVATE, its kinda private, but this is for home server use.
		$tempUserAgent = md5($_POST["inputQuestionPrompt"]);
		# generate the command setting the user agent, otherwise user agent will be set to unknown
		$command .=	'--prompt "'.$_POST['inputQuestionPrompt'].'" '."'";
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
				$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/txt2txt/".$tempUserAgent."/");
			}else{
				$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2txt/".$tempUserAgent."/");
			}
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/txt2txt/".$tempUserAgent."/");
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
					echo "<a class='inputCard textList' href='/ai/prompt/$directoryPath'>";
					echo file_get_contents("prompt/".$directoryPath."/prompt.cfg");
					echo "<div>Responses: ";
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
					echo "</a>";
				}
			}
			echo "</div>\n";
		}
	}

	if ($discoveredTxt2Txt){
		echo "<div class='titleCard'>\n";
		echo "<h1>Generate Text ✍️</h1>\n";

		echo "<div>\n";

		echo "<form method='post'>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " LLM:\n";
		echo "<select name='llm'>\n";
		echo $discoveredTxt2TxtData;
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo "Versions: <input class='' type='number' min='1' max='100' value='1' name='versions' placeholder='Number of versions to draw'>";
		echo "</span>\n";

		#echo "<span class='groupedMenuItem'>\n";
		#echo "Randomness : <input class='' type='number' min='1' max='10' value='7' name='temperature' placeholder='Randomness'>";
		#echo "</span>\n";

		#echo "<span class='groupedMenuItem'>\n";
		#echo "Max Output : <input class='' type='number' min='10' max='1000' value='100' name='maxOutput' placeholder='Max characters to output'>";
		#echo "</span>\n";

		echo "<span class='groupedMenuItem'> 🥸<span class='footerText'> Hidden</span>:<input class='checkbox' type='checkbox' name='hidden' value='yes'></input></span>\n";

		echo "<span class='groupedMenuItem'> 🐛<span class='footerText'> Debug</span>:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>\n";

		echo "</div>\n";

		echo "<hr>\n";

		echo "<textarea class='aiPrompt' name='inputPrompt' placeholder='Text generation prompt...'></textarea>";
		echo "<button class='aiSubmit' type='submit'><span class='footerText'>Generate</span> ↩️</button>";
		echo "</form>\n";
		echo "</div>\n";
	}

	if ($discoveredImg2Img || $discoveredTxt2Img){
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
	}

	if ($discoveredTxt2Img){
		# draw the image generator
		echo "<div class='titleCard'>\n";
		echo "<h1>Generate a image from text 🎨</h1>\n";
		echo "<form method='post' enctype='multipart/form-data'>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " Models:\n";
		echo "<select name='model'>\n";
		echo $discoveredTxt2imgData;
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " Base Negative Prompt:";
		echo "<select name='baseNegativePrompt'>\n";
		# load each of the ai models
		foreach(array_diff(scanDir("/etc/2web/ai/negative_prompts/"),array(".","..")) as $directoryPath){
			$directoryPath=str_replace(".cfg","",$directoryPath);
			echo "<option value='$directoryPath'>$directoryPath</option>\n";
		}
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

		echo "<span class='groupedMenuItem'> 🐛<span class='footerText'> Debug</span>:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>\n";

		echo "<hr>\n";

		echo "<textarea class='imageInputPrompt' name='imageInputPrompt' placeholder='Image generation prompt, Tags...' maxlength='120'></textarea>";
		echo "<textarea class='imageNegativeInputPrompt' name='imageNegativeInputPrompt' placeholder='Negative Prompt, Tags...' maxlength='120'></textarea>";
		echo "<button class='aiSubmit' type='submit'><span class='footerText'>Generate</span> ↩️</button>";
		echo "</form>";
		echo "</div>";
	}
	if ($discoveredImg2ImgData){
		# draw edit image prompt
		echo "<div class='titleCard'>";
		echo "<h1>Upload and Edit a Image 🖊️</h1>";

		echo "<form method='post' enctype='multipart/form-data'>";
		echo "<span class='groupedMenuItem'>\n";
		echo " Models:\n";
		echo "<select name='model'>\n";
		echo $discoveredImg2ImgData;
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo " Base Negative Prompt:";
		echo "<select name='baseNegativePrompt'>\n";
		# load each of the ai models
		$discoveredNegativePrompts=False;
		foreach(array_diff(scanDir("/etc/2web/ai/negative_prompts/"),array(".","..")) as $directoryPath){
			$directoryPath=str_replace(".cfg","",$directoryPath);
			echo "<option value='$directoryPath'>$directoryPath</option>\n";
			$discoveredNegativePrompts=True;
		}
		echo "</select>\n";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'>\n";
		echo "Versions: <input class='imageVersionsInput' type='number' min='1' max='10' value='3' name='imageGenVersions' placeholder='Number of versions to draw'>";
		echo "</span>\n";

		echo "<span class='groupedMenuItem'> 🐛<span class='footerText'> Debug</span>:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>\n";

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
		echo "<input class='' type='file' id='imageUploadForm' name='imageFileToEdit' onchange=\"$changeScript\" accept='image/*'>";
		echo "<img class='imageUploadPreview' id='imagePreview' src=''>";
		echo "<hr>";
		echo "<textarea class='imageInputPrompt' name='imageInputPrompt' placeholder='How to edit the image...' maxlength='120'></textarea>";
		echo "<textarea class='imageNegativeInputPrompt' name='imageNegativeInputPrompt' placeholder='Negative Prompt, Tags...' maxlength='120'></textarea>";
		echo "<button class='aiSubmit' type='submit'><span class='footerText'>Edit</span> ↩️</button>";
		echo "</form>";
		echo "</div>";
	}
}
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
