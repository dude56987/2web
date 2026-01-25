<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web settings search
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
-->
<html class='randomFanart'>
<head>
<?PHP
	# get the active theme for use below in reseting previews
	echo "<link rel='stylesheet' type='text/css' href='/style.css'>";
	?>
	<script src='/2webLib.js'></script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
error_reporting(E_ALL);
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
################################################################################
function randomColor(){
	return ("#".dechex(rand(0,255)).dechex(rand(0,255)).dechex(rand(0,255)));
}
?>

<?PHP
# read in theme files in /usr/share/2web/
$sourceFiles=scandir("/var/cache/2web/web/settings/");
$sourceFiles=array_diff($sourceFiles,Array("..","."));
# check if a page number is set
$tempSourceFiles=Array();
# filter the files
foreach($sourceFiles as $sourceFile){
	# only include .php files
	if (strpos($sourceFile,".php") !== false){
		#
		$tempSourceFile=$sourceFile;
		#
		$tempSourceFile=str_replace("/var/cache/2web/web/settings/","",$tempSourceFile);
		$tempSourceFile=str_replace(".php","",$tempSourceFile);
		# remove settings files that are not openable
		if ( in_array($tempSourceFile, Array("themeExample","selectPath","search","index","admin","settingsHeader")) != true ){
			$tempSourceFiles=array_merge($tempSourceFiles, Array($sourceFile));
		}
	}
}
# build the autocomplete list if it does not exist
if (is_readable("/var/cache/2web/generated/settings_autocomplete.index") != true){
	$autoCompleteData="";
	$autoCompleteData.="<datalist id='settingSearchAutocompleteData'>\n";
	foreach($tempSourceFiles as $sourceFile){
		$sourceFile=str_replace(".php","",$sourceFile);
		$autoCompleteData.="<option value='$sourceFile'>\n";
	}
	$autoCompleteData.="</datalist>\n";
	file_put_contents("/var/cache/2web/generated/settings_autocomplete.index",$autoCompleteData);
}
#
$og_sourceFiles=$tempSourceFiles;
$sourceFiles=$tempSourceFiles;
?>
<?PHP
# if no settings are set show the random page first
if ( ! array_key_exists("search",$_GET) ){
	if ( ! array_key_exists("page",$_GET) ){
		$_GET["random"]=0;
	}
}
# check for the search filter
if (array_key_exists("search",$_GET)){
	# get the serch term
	$searchTerm=$_GET["search"];
	# filter the search results
	#
	$tempSourceFiles=Array();
	$matchFound=false;
	# filter the files by the search term
	foreach($sourceFiles as $sourceFile){
		$tempSourceFile=$sourceFile;
		# check if a file is readable
		if (is_readable("/var/cache/2web/web/settings/".$tempSourceFile)){
			$tempSourceFileData=file_get_contents("/var/cache/2web/web/settings/".$tempSourceFile);
			# remove comment lines
			$tempSourceFileData=preg_replace("/^#.*$/","",$tempSourceFileData);
			# remove all newlines for building the example
			$tempSourceFileData=str_replace("\n","",$tempSourceFileData);
			#
			$tempSourceFile=str_replace("/var/cache/2web/web/settings/","",$tempSourceFile);
			$tempSourceFile=str_replace(".php","",$tempSourceFile);
			#
			if ( stripos($tempSourceFileData, $searchTerm) !== false ){
				#
				$tempSourceFiles=array_merge($tempSourceFiles, Array(trim($sourceFile)));
				$matchFound=true;
			}else if ( stripos($sourceFile, $searchTerm) !== false ){
				#
				$tempSourceFiles=array_merge($tempSourceFiles, Array(trim($sourceFile)));
				$matchFound=true;
			}
		}
	}
	#
	$sourceFiles=$tempSourceFiles;
}else{
	$matchFound=true;
}
echo "<div class='titleCard'>\n";
if (array_key_exists("search",$_GET)){
	if($matchFound){
		echo "<h2>Matched Settings</h2>\n";
	}else{
		echo "<h2>No Matched Settings</h2>\n";
	}
}else{
	echo "<h2>Setting Pages</h2>\n";
}
#
if(! $matchFound){
	echo "<div class='warningBanner'>";
	echo "No matches have been found, All settings are listed.";
	echo "</div>";
	$sourceFiles=$og_sourceFiles;
	# randomize the results when a match is not found
	shuffle($sourceFiles);
}
#
foreach($sourceFiles as $sourceFile){
	#
	$tempTheme=str_replace("/var/cache/2web/web/settings/","",$sourceFile);
	$themeName=str_replace(".php","",$tempTheme);
	#
	$permissionsPassed=false;
	if($themeName == "users"){
		$tempIcon="👪";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "kodi"){
		$tempIcon="🇰";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("kodi2web");
	}else if($themeName == "ai"){
		$tempIcon="🧠";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("ai2web");
	}else if($themeName == "ai_audio"){
		$tempIcon="📢";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("ai2web");
	}else if($themeName == "ai_embeds"){
		$tempIcon="🪄";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("ai2web");
	}else if($themeName == "ai_prompt"){
		$tempIcon="👽";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("ai2web");
	}else if($themeName == "ai_subtitles"){
		$tempIcon="📹";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("ai2web");
	}else if($themeName == "ai_txt2img"){
		$tempIcon="🎨";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("ai2web");
	}else if($themeName == "about"){
		$tempIcon="❓";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "graphs"){
		$tempIcon="📊";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("graph2web");
	}else if($themeName == "rss"){
		$tempIcon="📶";
		$tempTitle="Video RSS Feeds";
		$permissionsPassed=checkModStatus("rss2nfo");
	}else if($themeName == "repos"){
		$tempIcon="💾";
		$tempTitle="Git Repos";
		$permissionsPassed=checkModStatus("git2web");
	}else if($themeName == "tv"){
		$tempIcon="📡";
		$tempTitle="Live Video";
		$permissionsPassed=checkModStatus("iptv2web");
	}else if($themeName == "radio"){
		$tempIcon="📻";
		$tempTitle="Live Audio";
		$permissionsPassed=checkModStatus("iptv2web");
	}else if($themeName == "iptv_blocked"){
		$tempIcon="🚫";
		$tempTitle="Blocked Live Channels";
		$permissionsPassed=checkModStatus("iptv2web");
	}else if($themeName == "wiki"){
		$tempIcon="⛵";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("wiki2web");
	}else if($themeName == "nfo"){
		$tempIcon="🎞️";
		$tempTitle="On-Demand";
		$permissionsPassed=checkModStatus("nfo2web");
	}else if($themeName == "clean"){
		$tempIcon="🧹";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "system"){
		$tempIcon="🎛️";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "manuals"){
		$tempIcon="📔";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "log"){
		$tempIcon="📋";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "fortune"){
		$tempIcon="🔮";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "modules"){
		$tempIcon="🧩";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "weather"){
		$tempIcon="🌤️";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("weather2web");
	}else if($themeName == "music"){
		$tempIcon="🎧";
		$permissionsPassed=checkModStatus("music2web");
		$tempTitle="$themeName";
	}else if($themeName == "comicsDL"){
		$tempIcon="📚↓";
		$tempTitle="Comic Downloads";
		$permissionsPassed=checkModStatus("comic2web");
	}else if($themeName == "comics"){
		$tempIcon="📚";
		$tempTitle="$themeName";
		$permissionsPassed=checkModStatus("comic2web");
	}else if($themeName == "themes"){
		$tempIcon="🎨";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "cache"){
		$tempIcon="📥";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "help"){
		$tempIcon="?";
		$tempTitle="$themeName";
		$permissionsPassed=true;
	}else if($themeName == "ytdl2nfo"){
		$tempIcon="🎞️↓";
		$tempTitle="On-Demand Downloads";
		$permissionsPassed=checkModStatus("ytdl2nfo");
	}else if($themeName == "apps"){
		$tempIcon="🖥️";
		$tempTitle="Applications";
		$permissionsPassed=checkModStatus("php2web");
	}else if($themeName == "portal"){
		$tempIcon="🚪";
		$tempTitle="Portal";
		$permissionsPassed=checkModStatus("portal2web");
	}else if($themeName == "portal_scanning"){
		$tempIcon="🌐";
		$tempTitle="Portal Scanning";
		$permissionsPassed=checkModStatus("portal2web");
	}else{
		#
		$tempIcon=strtoupper(substr($themeName,0,1));
		$tempTitle=$themeName;
		#
		$permissionsPassed=true;
	}
	# draw the link if the user has permissions
	if ($permissionsPassed){
		$tempTitle=str_replace("_"," ",$tempTitle);
		$tempTitle=ucwords($tempTitle);
		# draw the search result link
		echo "<a class='indexSeries' href='/settings/$tempTheme'>\n";
		echo "	<h2 class='moreEpisodesLinkIcon'>\n";
		echo "		$tempIcon\n";
		echo "	</h2>\n";
		echo "	$tempTitle\n";
		echo "</a>\n";
	}
}
if (array_key_exists("search",$_GET)){
	if($matchFound){
		echo "	<div class='listCard' >";
		echo "		<a class='button' href='/settings/search.php'>";
		echo "			Show All Settings";
		echo "		</a>";
		echo "	</div>";
	}
}
echo "</div>\n";
#
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
