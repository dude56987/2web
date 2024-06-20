<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("ai2web");
?>
<?PHP
########################################################################
# 2web AI subtitles interface
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

if (array_key_exists("subtitles",$_POST)){
	# text subtitlesing interface for gpt4all
	# subtitles

	$fileSumString  = ($_POST['subtitles']);
	$fileSumString .= ($_POST['model']);
	#$fileSumString .= ($_POST['temperature']);

	$fileSum=md5($fileSumString);
	#$fileSum=$_SERVER["REQUEST_TIME"].$fileSum;

	# launch the process with a background scheduler
	//$command = "echo '";
	# one at a time queue, but launch from atq right away
	$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --jobs 1 --id ai2web ';
	# default load order of models if found on system
	# check for loading custom LLM
	if (! is_dir("/var/cache/2web/web/ai/subtitles/")){
		mkdir("/var/cache/2web/web/ai/subtitles/");
	}
	if (! is_dir("/var/cache/2web/web/ai/subtitles/".$fileSum."/")){
		mkdir("/var/cache/2web/web/ai/subtitles/".$fileSum."/");
	}

	if (! is_file("/var/cache/2web/web/ai/subtitles/".$fileSum."/subtitles.cfg")){
		file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/subtitles.cfg",$_POST["subtitles"]);
	}

	# always write start time
	file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/started.cfg",$_SERVER["REQUEST_TIME"]);
	if (! is_file("/var/cache/2web/web/ai/subtitles/".$fileSum."/model.cfg")){
		file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/model.cfg",$_POST["model"]);
	}
	if (array_key_exists("model",$_POST)){
		# set the model
		$command .= '/usr/bin/ai2web --media2text  "'.$_POST["model"].'" ';
	}else{
		# use the default model
		$command .= '/usr/bin/ai2web --media2text ';
	}
	$command .= ' "/var/cache/2web/web/ai/subtitles/'.$fileSum.'/" ';

	if (array_key_exists("hidden",$_POST)){
		if ($_POST["hidden"] == "yes"){
			# if the post is set to hidden generate a hidden.cfg
			file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/hidden.cfg", "yes");
		}
	}
	if (is_file("/var/cache/2web/web/ai/subtitles/".$fileSum."/versions.cfg")){
		# increment existing versions file
		$foundVersions = file_get_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/versions.cfg");
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/subtitles/"),array(".","..")) as $directoryPath){
				$foundVersions += 1;
			}
		}else{
			$foundVersions += 1;
		}
		file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/versions.cfg", $foundVersions);
	}else{
		if ($_POST["model"] == "{ALL}"){
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/subtitles/"),array(".","..")) as $directoryPath){
				$foundVersions += 1;
			}
			file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/versions.cfg",$foundVersions);
		}else{
			file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/versions.cfg","1");
		}
	}

	# cleanup the subtitles so it will work correctly
	$_POST["subtitles"] =	str_replace("'","",$_POST["subtitles"]);
	# write the subtitles file
	$command .= '--subtitles-file "/var/cache/2web/web/ai/subtitles/'.$fileSum.'/subtitles.cfg" ';
	# end the command by passing it to the "at" queue
	//$command .= "' | at -M now";
	# create the image view script link
	if (! is_link("/var/cache/2web/web/ai/subtitles/".$fileSum."/index.php")){
		symlink("/usr/share/2web/templates/ai_thread.php" ,("/var/cache/2web/web/ai/subtitles/".$fileSum."/index.php"));
	}
	if (! is_file("/var/cache/2web/web/ai/subtitles/".$fileSum."/command.cfg")){
		if ($_POST["model"] == "{ALL}"){
			$combinedFileData = "";
			foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/subtitles/"),array(".","..")) as $directoryPath){
				$combinedFileData .= str_replace("{ALL}","$directoryPath",$command).";\n";
			}
			# write the combined commands used to generate all the subtitless
			file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/command.cfg",$combinedFileData);
		}else{
			file_put_contents("/var/cache/2web/web/ai/subtitles/".$fileSum."/command.cfg",$command);
		}
	}
	# launch a job on the queue for each version
	# if the model is set to all
	if ($_POST["model"] == "{ALL}"){
		foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/subtitles/"),array(".","..")) as $directoryPath){
			if ($_POST["debug"] == "yes"){
				echo "<div class='errorBanner'>\n";
				echo "<hr>\n";
				echo "DEBUG: SHELL EXECUTE: '$command'<br>\n";
				echo "<hr>\n";
				echo "</div>\n";
			}
			# for each model found launch a new command
			addToQueue("multi", str_replace("{ALL}","\"$directoryPath\"",$command));
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
		addToQueue("multi", $command);
	}
	# delay 1 seconds to allow loading of database
	if(array_key_exists("HTTPS",$_SERVER)){
		if($_SERVER['HTTPS']){
			$redirectUrl = ("https://".$_SERVER['HTTP_HOST']."/ai/subtitles/".$fileSum."/?autoRefresh");
		}else{
			$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/subtitles/".$fileSum."/?autoRefresh");
		}
	}else{
		$redirectUrl = ("http://".$_SERVER['HTTP_HOST']."/ai/subtitles/".$fileSum."/?autoRefresh");
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
	<script>
	// file upload progress function
	function postFile() {
		//
		var formdata = new FormData();
		// get the first file in the upload form
		formdata.append('uploadMediaFile', document.getElementById('fileUploadInput').files[0]);
		// create a request
		var request = new XMLHttpRequest();
		// add a event trigger
		request.upload.addEventListener('progress', function (event) {
			// get the size for the file uploaded
			var fileSize = document.getElementById('fileUploadInput').files[0].size;
			// if the file has not completed upload draw the updated progress bar
			if (event.loaded <= fileSize){
				//
				var percent = Math.round(event.loaded / fileSize * 100);
				// set the progress bar width
				document.getElementById('progressBarBar').style.width = percent + '%';
				// the progress bar text
				document.getElementById('progressBarBar').innerHTML = String(percent) + '% ' + String(Math.floor(event.loaded / 1000000)) + "mb/" + String(Math.floor(fileSize / 1000000)) + "mb";
				// hide the upload input and show the progress bar
				document.getElementById('stopButton').style.display = "block";
				document.getElementById('uploadButton').style.display = "none";
				// swap file picker and progress bar elements
				document.getElementById('fileUploadInput').style.display = "none";
				document.getElementById('progressBar').style.display = "block";
			}
			// if the file upload has finished draw the full progress bar
			if(event.loaded == event.total){
				//
				document.getElementById('progressBarBar').style.width = '100%';
				document.getElementById('progressBarBar').innerHTML = '100% ' + String(event.loaded) + "/" + String(fileSize);
				// hide the progress bar and show the file input
				document.getElementById('stopButton').style.display = "none";
				document.getElementById('uploadButton').style.display = "block";
				// swap file picker and progress bar elements
				document.getElementById('fileUploadInput').style.display = "block";
				document.getElementById('progressBar').style.display = "none";
			}
		});
		// try and capture the redirect when it is loaded
		request.onload = () => {
			// redirect the current page to the new page
			window.location=request.responseURL;
		}
		//
		request.open('POST', '/ai/subtitles/index.php');
		//
		request.timeout = 45000;
		//
		request.send(formdata);
	}
	</script>
</head>
<body>
<?php
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");

# load each of the ai subtitles models
$discoveredsubtitles=False;
$discoveredsubtitlesData="";
$discoveredsubtitlesData .= "<option value='{ALL}'>all</option>\n";
foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/subtitles/"),array(".","..")) as $directoryPath){
	$niceDirectoryPath=str_replace(".bin","",$directoryPath);
	$discoveredsubtitlesData .= "<option value='$directoryPath'>$niceDirectoryPath</option>\n";
	$discoveredsubtitles=True;
}
# add the toolbox to the top of the page
include("/usr/share/2web/templates/ai_toolbox.php");

if ($discoveredsubtitles){
	echo "<div class='titleCard'>\n";
	echo "	<h1>What Can Text <sup class='simpleSup'>a</sup>I Do?</h1>\n";
	echo "	<div class='listCard'>\n";
	$helpTexts=Array();
	$helpTexts=array_merge($helpTexts,["Upload a file and generate a subtitles file for that file."]);
	shuffle($helpTexts);
	foreach($helpTexts as $helpText ){
		echo "		<div class='inputCard textList'>\n";
		echo "			<p>$helpText</p>\n";
		echo "		</div>\n";
	}
	echo "	</div>\n";
	echo "</div>\n";
}
?>
	<div class='titleCard'>
	<h1>Create Subtitles For A Media File</h1>
	<!-- form to upload a file for playback -->
	<form method='post' enctype='multipart/form-data'>
		<tr>
			<th id='directLink'>
				Upload File to Generate Subtitles
			</th>
		</tr>
		<tr>
			<td>
				<table class='kodiControlEmbededTable'>
					<tr>
						<td>
							<noscript><div class="errorBanner">This upload form will not work without javascript enabled.</div></noscript>
							<input class='button' id="fileUploadInput" type='file' name='uploadMediaFile' accept='video/*'>
							<div id='progressBar' class="progressBar" style="display: none;">
									<div id="progressBarBar" class="progressBarBar">Inactive</div>
							</div>
						</td>
						<td class='kodiControlEmbededTableButton' >
							<input id='uploadButton' class='button' onclick="postFile()" type='button' value='🠉 Upload File'>
							<input id='stopButton' class='button' onclick="location.reload()" type='button' value='❌ Stop Upload' style="display: none;">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	<div>
		<span title='What Large Language Model would you like to use to generate anwsers to your subtitles?'>
		<span class='groupedMenuItem'>
		LLM:
		<select name='model' class='dropBox'>
		<?php
		echo $discoveredsubtitlesData;
		?>
		</select>

		</span>
		</span>
	</div>
	<span title='Hide the subtitles output from the public indexes. Anyone can still access it with a direct link though.'>
	<span class='groupedMenuItem'>🥸 Hidden</span>:<input class='checkbox' type='checkbox' name='hidden' value='yes'></input></span>
	</span>

	<span title='Do not touch bugs! This is only for developers.'>
	<span class='groupedMenuItem'> 🐛<span class='footerText'> Debug</span>:<input class='checkbox' type='checkbox' name='debug' value='yes'></input></span>
	</span>
	</form>

<?php

	# draw the threads discovered
	$subtitlesIndex=array_diff(scanDir("/var/cache/2web/web/ai/subtitles/"),array(".","..","index.php"));
	#sort($subtitlesIndex);

	# generate an array where the keys are the file modification dates of the the directories listed
	$sortedsubtitlesIndex=Array();
	# read each directory in the list
	foreach($subtitlesIndex as $directoryPath){
		# get the file modification time
		$modificationDate=filemtime($directoryPath);
		# add the path to the array with the key as the file modification time
		$sortedsubtitlesIndex[$modificationDate]=$directoryPath;
	}
	# sort the array by the key values
	ksort($sortedsubtitlesIndex);
	# replace the original array with the sorted one
	$subtitlesIndex=$sortedsubtitlesIndex;

	# order newest subtitless first
	$subtitlesIndex=array_reverse($subtitlesIndex);
	# if any previous subtitless are found
	if (count($subtitlesIndex) > 0){
		echo "<div class='titleCard'>\n";
		echo "<h1>Previous subtitless</h1>\n";
		# split into pages and grab only the first page
		$subtitlesIndex=array_chunk($subtitlesIndex,10);
		$totalPages=( count($subtitlesIndex) - 1);
		# grab the page if the page number is set
		if (array_key_exists("page",$_GET)){
			# decrement page number since array 0 is page 1
			$subtitlesIndex=$subtitlesIndex[$_GET['page']];
		}else{
			$_GET["page"]=0;
			$subtitlesIndex=$subtitlesIndex[0];
		}
		foreach($subtitlesIndex as $directoryPath){
			# if the hidden cfg file does not exist use this in the index
			if ( ! file_exists($directoryPath."/hidden.cfg")){
				echo "<a class='inputCard textList button' href='/ai/subtitles/$directoryPath'>";
				echo file_get_contents($directoryPath."/subtitles.cfg");
				echo "<div>🗨️ Responses: ";
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
					echo "⛔ Failures: ";
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
