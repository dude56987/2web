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
ini_set('file_uploads', "On");
########################################################################
# add the base php libary
include("/usr/share/2web/2webLib.php");
########################################################################
function filesize_to_human($tempFileSize){
	# get the filesize
	if ($tempFileSize > pow(1024, 4)){
		return round($tempFileSize/pow(1024, 4), 2)." TB";
	}else if ($tempFileSize > pow(1024, 3)){
		return round($tempFileSize/pow(1024, 3), 2)." GB";
	}else if ($tempFileSize > pow(1024, 2)){
		return round($tempFileSize/pow(1024, 2), 2)." MB";
	}else if ($tempFileSize > 1024){
		return round(($tempFileSize/1024))." KB";
	}else{
		return $tempFileSize." Bytes";
	}
}
########################################################################
if (array_key_exists("generateMore",$_GET)){
	# increment the versions file
	$versions=file_get_contents("versions.cfg");
	if (stripos(file_get_contents("command.cfg"),"\n") !== false){
		foreach(array_diff(scanDir("/var/cache/2web/downloads/ai/prompt/"),array(".","..")) as $directoryPath){
			$versions+=1;
		}
	}else{
		$versions+=1;
	}
	file_put_contents("versions.cfg",$versions);
	# update the elapsed time since prompt
	file_put_contents("started.cfg",$_SERVER["REQUEST_TIME"]);
	# update the ordering by changing the modification time of the directory
	touch(".");
	# launch the command again to generate more versions of the output
	shell_exec(file_get_contents("command.cfg"));
	# redirect back to this page in refresh mode
	redirect("?autoRefresh");
}
?>
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?PHP
include($_SERVER['DOCUMENT_ROOT']."/header.php");

if (array_key_exists("debug",$_POST)){
	echo "<div class='errorBanner'>\n";
	echo "<hr>\n";
	echo (var_dump($_POST));
	echo "<hr>\n";
	echo "</div>\n";
}
# add the toolbox to the top of the page
include("/usr/share/2web/templates/ai_toolbox.php");
# build the anwser header
echo "<div class='titleCard'>\n";
echo "<a href='".$_SERVER["REQUEST_URI"]."'>";
# limit title size to 100
$promptData=file_get_contents("prompt.cfg");
$bigString="";
if (strlen($promptData) > 100){
	echo "<h1>".substr($promptData,0,100)."..."."</h1>";
}else{
	echo "<h1>".$promptData."</h1>";
}
echo "</a>";
echo "<div class=''>\n";
$noDiscoveredImages=True;
$discoveredImages=0;
$discoveredImageList=array_diff(scanDir("."),array(".",".."));
$discoveredImageList=array_reverse($discoveredImageList);
$discoveredFileSizes=Array();
$totalFileSize=0;

$fileSortPaths = array();

foreach( $discoveredImageList as $directoryPath){
	if (strpos($directoryPath,".png")){
		$fileSortPaths[$directoryPath]=filemtime($directoryPath);
		$noDiscoveredImages=False;
		$discoveredImages += 1;

		$tempFileSize=filesize($directoryPath);
		if ($tempFileSize > 0){
			$totalFileSize += $tempFileSize;
		}

		$discoveredFileSizes += Array($directoryPath => $tempFileSize);

	}
}
arsort($fileSortPaths);
$discoveredImageList=array_keys($fileSortPaths);

#$discoveredImageList=array_reverse($discoveredImageList);

$finishedVersions=0;
foreach(array_diff(scandir("."),Array(".","..")) as $foundFile ){
	# count only .png files found
	if (stripos($foundFile,".png") !== false){
		$finishedVersions += 1;
	}
}

$totalVersions=file_get_contents("versions.cfg");

#if (file_exists("finished.cfg")){
#	$finishedVersions=file_get_contents("finished.cfg");
#}else{
#	$finishedVersions=0;
#}

# if all versions have not been created
if ($finishedVersions < $totalVersions){
	if (array_key_exists("autoRefresh",$_GET)){
		echo "<img class='localPulse' src='/pulse.gif'>\n";
		echo "<div class='listCard'>";
		echo "<a class='button' href='?'>⏹️ Stop Refresh</a>\n";
	}else{
		echo "<div class='listCard'>";
		echo "<a class='button' href='?autoRefresh'>▶️  Auto Refresh</a>\n";
	}
	echo "	<a class='button' href='?generateMore'>⚙️ Generate More Responses</a>";
	echo "</div>";

	$executionTime = $_SERVER['REQUEST_TIME'] - (file_get_contents("started.cfg")) ;
	$executionMinutes = floor($executionTime / 60);
	$executionSeconds = floor($executionTime - floor($executionMinutes * 60));
	# check for numbers less than 10
	if ($executionMinutes < 10){
		$executionMinutes = "0$executionMinutes" ;
	}
	if ($executionSeconds < 10){
		$executionSeconds = "0$executionSeconds" ;
	}
	if($noDiscoveredImages){
		echo "No responses have finished rendering yet... ";
		echo "<hr>";
	}else{
		$progress=floor(($finishedVersions/$totalVersions)*100);
		echo "<div class='progressBar'>\n";
		echo "\t<div class='progressBarBar' style='width: ".$progress."%;'>\n";
		echo ($finishedVersions."/".$totalVersions." %".$progress);
		echo "\t</div>\n";
		echo "</div>\n";
	}
	# list the time elapsed so far
	echo "<div class='elapsedTime'>Elapsed Time since last prompt $executionMinutes:$executionSeconds</div>\n";
}else{
	# if discovered versions is greater than total versions from the file
	# overwrite the versions file with the greater number
	file_put_contents("versions.cfg","$finishedVersions");
}
if($discoveredImages > 0){
	echo "<table>";
	echo "	<tr>";
	echo "		<th>Discovered Files</th>";
	echo "		<th>Total Filesize</th>";
	echo "		<th>Prompt</th>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>$discoveredImages</td>";
	echo "		<td>".filesize_to_human($totalFileSize)."</td>";
	echo "		<td>".file_get_contents("prompt.cfg")."</td>";
	echo "	</tr>";
	echo "</table>";
}
$highestVotedAnwser="";
$highestVoteValue=0;
$allOtherAnwsers="";
$versionNumber=count($discoveredImageList);
foreach( $discoveredImageList as $directoryPath){
	if (strpos($directoryPath,".png")){
		$tempAnwserData="";
		$tempAnwserData .= "<div class='aiGenPreview' href='#$directoryPath' >\n";
		if($versionNumber < 10){
			$printVersionNumber = "00".$versionNumber;
		}else if($versionNumber < 100){
			$printVersionNumber = "0".$versionNumber;
		}else{
			$printVersionNumber = $versionNumber;
		}
		$tempAnwserData .= "<h1>Version ".$printVersionNumber."</h1>";
		# incremnt the version number
		$versionNumber -= 1;
		#$tempAnwserData .= "<h1>".$directoryPath."</h1>";
		$tempAnwserData .= "<div>";
		$tempAnwserData .= "<a href='$directoryPath'>";
		$tempAnwserData .= "<img class='aiGenPreviewImage' src='$directoryPath' >";
		$tempAnwserData .= "</a>";
		$tempAnwserData .= "</div>";
		# check for a model path
		$modelPath = str_replace(".png", ".model", $directoryPath);
		if (file_exists($modelPath)){
			$tempAnwserData .= "<hr>";
			$tempAnwserVotes = file_get_contents($modelPath);
			$tempAnwserData .= "Model:".$tempAnwserVotes;
		}else{
			# if no votes exist
			$tempAnwserVotes = 0;
		}
		$tempAnwserData .= "<hr>";
		# check for votes
		$votesPath = str_replace(".png", ".votes", $directoryPath);
		if (file_exists($votesPath)){
			$tempAnwserData .= "<hr>";
			$tempAnwserVotes = file_get_contents($votesPath);
			$tempAnwserData .= "Votes:".$tempAnwserVotes;
		}else{
			# if no votes exist
			$tempAnwserVotes = 0;
		}
		$tempAnwserData .= "<hr>";
		# load the discovered file size from the array
		$tempAnwserData .= filesize_to_human($discoveredFileSizes[$directoryPath]);
		$tempAnwserData .= "</div>\n";

		# compare this to the highest voted anwser
		if ($tempAnwserVotes > $highestVoteValue){
			$highestVoteValue = $tempAnwserVotes;
			# this is the new highest voted anwser set it
			$highestVotedAnwser = $tempAnwserData;
		}

		# append the anwser to all other anwser data
		$allOtherAnwsers .= $tempAnwserData;
	}
}
# print the highest voted anwser at the top of the list
echo $highestVotedAnwser;
# print out the discovered anwsers
echo $allOtherAnwsers;

echo "</div>\n";

$drawPrompt=False;
if ($finishedVersions < $totalVersions){
	if (array_key_exists("autoRefresh",$_GET)){
		// using javascript, reload the webpage every 60 seconds, time is in milliseconds
		echo "<script>";
		echo "delayedRefresh(10)";
		echo "</script>";
	}else{
		$drawPrompt=True;
	}
}else{
	$drawPrompt=True;
}

if ($drawPrompt){
	echo "<div class='titleCard'>";
	echo "<table>";
	echo "	<tr>";
	echo "		<th>Model</th>";
	echo "		<th>Prompt</th>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".file_get_contents("model.cfg")."</td>";
	echo "		<td>".file_get_contents("prompt.cfg")."</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<pre>";
	echo file_get_contents("command.cfg");
	echo "</pre>";
	echo "	<div class='listCard'>";
	echo "		<a class='button' href='?generateMore'>⚙️ Generate More Responses</a>";
	echo "	</div>";
	echo "</div>";
}

if (file_exists("failures.cfg")){
	echo "<div class='titleCard'>";
	echo "<h2>Failures</h2>";
	echo "<div>Failed Generation Attempts: ";
	echo file_get_contents("failures.cfg");
	echo "</div>";
	echo "<ul>";
	echo "	<li>Failures are the blank responses.</li>";
	echo "	<li>Failures indicate the model can not generate anwsers for this specific prompt.</li>";
	echo "	<li>You can change the prompt itself to try and get anwsers.</li>";
	echo "	<li>You can change the language model and try this same prompt.</li>";
	echo "	<li>You can brute force this and eventually you may get a result.</li>";
	echo "</ul>";
	echo "</div>";
}

echo "</div>\n";
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
