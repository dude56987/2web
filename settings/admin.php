<!--
########################################################################
# 2web administrative API
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
-->
<html class='randomFanart'>
<head>
	<link rel='stylesheet' href='/style.css'>
	<script src='/2web.js'></script>
</head>
<body>

<?php
include("/usr/share/2web/2webLib.php");
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
//include('settingsHeader.php');
?>

<div class='settingListCard'>
<?php
# enable error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);
////////////////////////////////////////////////////////////////////////////////
function clear(){
	flush();
	ob_flush();
}
////////////////////////////////////////////////////////////////////////////////
function countdown($countdownTime){
	$index=0;
	$waitTime=rand(1,$countdownTime);
	while($index < $waitTime){
		$index += 1;
		echo "$index..";
		clear();
		sleep(1);
	}
	echo "Done!<br>";
	clear();
	sleep(1);
}
////////////////////////////////////////////////////////////////////////////////
function outputLog($stringData){
	echo "$stringData";
	$index=0;
	$waitTime=3;
	while($index < $waitTime){
		$index += 1;
		echo ".";
		clear();
		if ( ($index % 2) == 0 ){
			sleep(1);
		}
	}
	# 50/50 shot
	if ( rand(0,1) == 0 ){
		sleep(1);
		clear();
	}
	echo "<br>\n";
}
////////////////////////////////////////////////////////////////////////////////
function setModStatus($modName,$modStatus){
	outputLog("Setting $modName status to ".$modStatus);
	# read the link and create a custom config
	$configPath="/etc/2web/mod_status/".$modName.".cfg";
	outputLog("Checking for Config file ".$configPath);
	if ( $modStatus == "disabled"){
		outputLog("Disabled $modName");
		# this means to remove the link
		if (file_get_contents($configPath) == "enabled"){
			//unlink($configPath);
			file_put_contents($configPath,$modStatus);
		}else{
			outputLog("$modName status is already set to ".$modStatus);
		}
	}else{
		# write the libary path to a file at the configPath if the path does not already exist
		outputLog("Enabled $modName");
		# enable the graph section
		if ((file_exists($configPath)) and (file_get_contents($configPath) != "enabled") ){
			# write the config file
			file_put_contents($configPath,$modStatus);
		}else{
			outputLog("$modName status is already set to ".$modStatus);
		}
	}
}
////////////////////////////////////////////////////////////////////////////////
function checkUsernamePass($userName, $password){
	$passSum = file_get_contents("/etc/2web/users/".md5($userName));
	if ( $passSum == md5($password) ){
		return true;
	}else{
		return false;
	}
}
# clean up the post input before processing
cleanPostInput();
////////////////////////////////////////////////////////////////////////////////
// Start processing data
////////////////////////////////////////////////////////////////////////////////
if (array_key_exists("newUserName",$_POST)){
	# make all chacters lowercase for password
	$userName=strtolower($_POST['newUserName']);
	echo "Creating new user '$userName'";
	if (array_key_exists("newUserPass",$_POST)){
		if ( ! file_exists("/etc/2web/users/")){
			mkdir("/etc/2web/users/");
		}
		$userPass=strtolower($_POST['newUserPass']);
		# build the password
		$passSum=md5($userPass);
		if (array_key_exists("authType",$_POST)){
			$authType=$_POST['authType'];
		}else{
			# by default if a authType is not configured use default method
			$authType='bcrypt';
		}
		if ($authType == "md5"){
			shell_exec("htpasswd -cb /etc/2web/users/".($userName).".cfg '".($userName."' '".$userPass."'") );
		}else if ($authType == "bcrypt"){
			# default bcrypt encryption for password, -C is 5 by default
			shell_exec("htpasswd -cb -B /etc/2web/users/".($userName).".cfg '".($userName."' '".$userPass."'") );
		}else if ($authType == "bcrypt_weakest"){
			shell_exec("htpasswd -cb -B -C 4 /etc/2web/users/".($userName).".cfg '".($userName."' '".$userPass."'") );
		}else if ($authType == "bcrypt_strongest"){
			# strongest encryption but takes a long time to verify each page served
			shell_exec("htpasswd -cb -B -C 17 /etc/2web/users/".($userName).".cfg '".($userName."' '".$userPass."'") );
		}
		//echo ( "Writing ".($userName.":".$passSum)." to ".("/etc/2web/users/".md5($userName).".cfg")."<br>" );
		//file_put_contents( ("/etc/2web/users/".md5($userName).".cfg"), ($userName.":$".$passSum."\n") );

		# create a new htaccces file
		//file_put_contents("/var/cache/nfo2web/.htaccess","$userName:$passSum");
	}
	outputLog("<hr><a class='button' href='/settings/system.php#addNewUser'>BACK</a><hr>");
}else if (array_key_exists("removeUser",$_POST)){
	$userName=$_POST['removeUser'];
	outputLog("Removing user $userName from authorization list");
	unlink("/etc/2web/users/$userName");
	echo "<hr><a class='button' href='/settings/system.php#removeUser'>BACK</a><hr>";
}else if (array_key_exists("all_update",$_POST)){
	outputLog("Scheduling 2web update!");
	shell_exec("echo '2web all' | /usr/bin/at -q b now");
	echo "<hr><a class='button' href='/settings/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("nfo_update",$_POST)){
	outputLog("Scheduling nfo update!");
	shell_exec("echo 'nfo2web' | /usr/bin/at -q b now");
	echo "<hr><a class='button' href='/settings/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("iptv_update",$_POST)){
	outputLog("Scheduling iptv2web update!");
	shell_exec("echo 'iptv2web' | /usr/bin/at -q b now");
	echo "<hr><a class='button' href='/settings/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("comic_update",$_POST)){
	outputLog("Scheduling comic2web update!");
	shell_exec("echo 'comic2web' | /usr/bin/at -q b now");
	echo "<hr><a class='button' href='/settings/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("weather_update",$_POST)){
	outputLog("Scheduling weather2web update!");
	shell_exec("echo 'weather2web' | /usr/bin/at -q b now");
	echo "<hr><a class='button' href='/settings/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addCustomRadioLink",$_POST)){
	# this will add a custom m3u file with a single entry
	$link=$_POST['addCustomRadioLink'];
	if (array_key_exists("addCustomRadioTitle",$_POST)){
		# add custom title
		$linkTitle=$_POST['addCustomRadioTitle'];
		if (array_key_exists("addCustomRadioIcon",$_POST)){
			# add the icon link
			$linkIcon=$_POST['addCustomRadioIcon'];
			################################################################################
			# all fields are filled out
			################################################################################
			# create sum of link
			$sumOfLink=md5($link);
			# read the link and create a custom config
			$configPath="/etc/2web/iptv/radioSources.d/".$sumOfLink.".m3u";
			# create the custom link content
			#$content='#EXTM3U\n'.'#EXTINF:-1 radio="true" tvg-logo="'.$linkIcon.'",'.$linkTitle.'\n'.$link;
			$content='#EXTM3U\n'.'#EXTINF:-1 radio="true" tvg-logo="'.$linkIcon.'",'.$linkTitle;
			outputLog("Checking for Config file ".$configPath);
			# write the link to a file at the configPath if the path does not already exist
			$fileObject=fopen($configPath,'w');
			if ( ! file_exists($configPath)){
				outputLog("Adding link ".$link);
				# write the config file
				//file_put_contents($configPath,$content);
				fwrite($fileObject,$content);
				fwrite($fileObject,$link);
				fclose($fileObject);
			}else{
				outputLog("[ERROR]: Custom Radio link creation failed '".$link."'");
			}
		}else{
			outputLog("[ERROR]: Custom Radio Icon not found");
		}
	}else{
		outputLog("[ERROR]: Custom Radio Title not found");
	}
	echo "<hr><a class='button' href='/settings/radio.php'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addCustomLink",$_POST)){
	# this will add a custom m3u file with a single entry
	$link=$_POST['addCustomLink'];
	if (array_key_exists("addCustomTitle",$_POST)){
		# add custom title
		$linkTitle=$_POST['addCustomTitle'];
		if (array_key_exists("addCustomIcon",$_POST)){
			# add the icon link
			$linkIcon=$_POST['addCustomIcon'];
			################################################################################
			# all fields are filled out
			################################################################################
			# create sum of link
			$sumOfLink=md5($link);
			# read the link and create a custom config
			$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".m3u";
			# create the custom link content
			$content='#EXTM3U\n'.'#EXTINF:-1 tvg-logo="'.$linkIcon.'",'.$linkTitle.'\n'.$link;
			outputLog("Checking for Config file ".$configPath);
			# write the link to a file at the configPath if the path does not already exist
			/*
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
				file_put_contents($configPath,$content);
			*/
			$fileObject=fopen($configPath,'w');
			if ( ! file_exists($configPath)){
				outputLog("Adding link ".$link);
				# write the config file
				fwrite($fileObject,$content);
				fwrite($fileObject,$link);
				fclose($fileObject);
			}else{
				outputLog("[ERROR]: Custom link creation failed '".$link."'");
			}
		}else{
			outputLog("[ERROR]: Custom Icon not found");
		}
	}else{
		outputLog("[ERROR]: Custom Title not found");
	}
	echo "<hr><a class='button' href='/settings/tv.php'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addRadioLink",$_POST)){
	$link=$_POST['addRadioLink'];
	outputLog("Running addRadioLink on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/iptv/radioSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding link ".$link);
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/radio.php#addRadioLink'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheQuality",$_POST)){
	$cacheQuality=$_POST['cacheQuality'];
	# change the default cache quality
	outputLog("Changing cache quality to '".$cacheQuality."'");
	# write the config file
	file_put_contents("/etc/2web/cache/cacheQuality.cfg",$cacheQuality);
	echo "<hr><a class='button' href='/settings/cache.php#cacheQuality'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheUpgradeQuality",$_POST)){
	$cacheUpgradeQuality=$_POST['cacheUpgradeQuality'];
	# change the default cache quality
	outputLog("Changing cache upgrade quality to '".$cacheUpgradeQuality."'");
	# write the config file
	if ($cacheUpgradeQuality == 'no_upgrade'){
		unlink("/etc/2web/cache/cacheUpgradeQuality.cfg");
	}else{
		file_put_contents("/etc/2web/cache/cacheUpgradeQuality.cfg",$cacheUpgradeQuality);
	}
	echo "<hr><a class='button' href='/settings/cache.php#cacheUpgradeQuality'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheFramerate",$_POST)){
	$cacheFramerate=$_POST['cacheFramerate'];
	# change the default cache quality
	outputLog("Changing cache mode to '".$cacheFramerate."'");
	# write the config file
	if ($cacheFramerate == ''){
		unlink("/etc/2web/cache/cacheFramerate.cfg");
	}else{
		file_put_contents("/etc/2web/cache/cacheFramerate.cfg",$cacheFramerate);
	}
	echo "<hr><a class='button' href='/settings/cache.php#cacheFramerate'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheResize",$_POST)){
	$cacheResize=$_POST['cacheResize'];
	# change the default cache quality
	outputLog("Changing cache mode to '".$cacheResize."'");
	# write the config file
	if ($cacheResize == ''){
		unlink("/etc/2web/cache/cacheResize.cfg");
	}else{
		file_put_contents("/etc/2web/cache/cacheResize.cfg",$cacheResize);
	}
	echo "<hr><a class='button' href='/settings/cache.php#cacheResize'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheDelay",$_POST)){
	$cacheDelay =$_POST['cacheDelay'];
	# change the default cache quality
	outputLog("Changing cache delay to '".$cacheDelay."'");
	# write the config file
	if ($cacheDelay == ''){
		unlink("/etc/2web/cache/cacheDelay.cfg");
	}else{
		file_put_contents("/etc/2web/cache/cacheDelay.cfg",$cacheDelay);
	}
	echo "<hr><a class='button' href='/settings/cache.php#cacheDelay'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheNewEpisodes",$_POST)){
	$cacheNewEpisodes=$_POST['cacheNewEpisodes'];
	# change the default cache quality
	outputLog("Changing cache new episodes option to '".$cacheNewEpisodes."'");
	# write the config file
	if ($cacheNewEpisodes == ''){
		unlink("/etc/2web/cacheNewEpisodes.cfg");
	}else{
		file_put_contents("/etc/2web/cacheNewEpisodes.cfg",$cacheNewEpisodes);
	}
	echo "<hr><a class='button' href='/settings/cache.php#cacheNewEpisodes'>BACK</a><hr>";
	clear();
}else if (array_key_exists("transcodeForWebpages",$_POST)){
	$cacheNewEpisodes=$_POST['transcodeForWebpages'];
	# change the default cache quality
	outputLog("Changing transcode for webpages option to '".$cacheNewEpisodes."'");
	# write the config file
	if ($cacheNewEpisodes == ''){
		unlink("/etc/2web/transcodeForWebpages.cfg");
	}else{
		file_put_contents("/etc/2web/transcodeForWebpages.cfg",$cacheNewEpisodes);
	}
	echo "<hr><a class='button' href='/settings/cache.php#cacheNewEpisodes'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addLink",$_POST)){
	$link=$_POST['addLink'];
	outputLog("Running addLink on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding link ".$link);
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/tv.php#addLink'>BACK</a><hr>";
	clear();
}else if (array_key_exists("ytdl_add_source",$_POST)){
	$link=$_POST['ytdl_add_source'];
	outputLog("Running ytdl_add_source on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/ytdl/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding ytdl source ".$link);
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/ytdl2nfo.php#websiteSources'>BACK</a><hr>";
	clear();
}else if(array_key_exists("ytdl_remove_source",$_POST)){
	$link=$_POST['ytdl_remove_source'];
	outputLog("Running ytdl_remove_source on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/ytdl/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		outputLog("Removing ytdl source ".$link);
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/ytdl2nfo.php#websiteSources'>BACK</a><hr>";
	clear();
}else if (array_key_exists("ytdl_add_username_source",$_POST)){
	$link=$_POST['ytdl_add_username_source'];
	outputLog("Running ytdl_add_username_source on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/ytdl/usernameSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding ytdl username source ".$link);
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/ytdl2nfo.php#usernameSources'>BACK</a><hr>";
	clear();
}else if(array_key_exists("ytdl_remove_username_source",$_POST)){
	$link=$_POST['ytdl_remove_username_source'];
	outputLog("Running ytdl_remove_username_source on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/ytdl/usernameSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		outputLog("Removing ytdl username source ".$link);
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/ytdl2nfo.php#usernameSources'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addLibary",$_POST)){
	$link=$_POST['addLibary'];
	outputLog("Running addLibary on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/nfo/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding link ".$link);
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/nfo.php#libaryPaths'>BACK</a><hr>";
	clear();

}else if (array_key_exists("addWeatherLocation",$_POST)){
	$link=$_POST['addWeatherLocation'];
	outputLog("Running addWeatherLocation on link ".$link);
	$sumOfLink=md5($link);
	# run the weather command as a search command and check that the location has a result
	$weatherTest=shell_exec("weather '".$_POST['addWeatherLocation']."'");
	# check if the location has failed
	if (strpos($weatherTest, "Your search is ambiguous")){
		$weatherTest=shell_exec("weather --info '".$_POST['addWeatherLocation']."'");
		echo "<div>";
		echo "ERROR: Your location was not specific enough.";
		echo "</div>";
		echo "<pre class='settingListCard'>";
		echo ("weather --info '".$_POST['addWeatherLocation']."'\n");
		echo $weatherTest;
		echo "</pre>";
	}else if(strpos($weatherTest, "Current conditions")){
		# read the link and create a custom config
		$configPath="/etc/2web/weather/location.d/".$sumOfLink.".cfg";
		outputLog("Checking for Config file ".$configPath);
		# write the libary path to a file at the configPath if the path does not already exist
		if ( ! file_exists($configPath)){
			echo "Adding ".$link." to weather stations...<br>\n";
			# write the config file
			file_put_contents($configPath,$link);
		}
	}else{
		$weatherTest=shell_exec("weather --info '".$_POST['addWeatherLocation']."'\n");
		echo "<div>";
		echo "ERROR: Your search has no results.";
		echo "</div>";
		echo "<pre class='settingListCard'>";
		echo ("weather --info '".$_POST['addWeatherLocation']."'\n");
		echo $weatherTest;
		echo "</pre>";
	}
	echo "<hr><a class='button' href='/settings/weather.php#addWeatherLocation'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeWeatherLocation",$_POST)){
	$link=$_POST['removeWeatherLocation'];
	outputLog("Running removeWeatherLocation on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/weather/location.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		outputLog("Removing weather location ".$link);
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/weather.php#currentLinks'>BACK</a><hr>";
	clear();
}else if (array_key_exists("setHomepageWeatherLocation",$_POST)){
	$link=$_POST['setHomepageWeatherLocation'];
	outputLog("Running setHomepageWeatherLocation on location ".$link);
	# read the link and create a custom config
	$configPath="/etc/2web/weather/homepageLocation.cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( $link == "disabled"){
		echo "Disabled Homepage Weather Location<br>\n";
		# this means to remove the link
		if (file_exists($configPath)){
			unlink($configPath);
		}
	}else{
		# write the libary path to a file at the configPath if the path does not already exist
		if ( ! file_exists($configPath)){
			outputLog("Setting homepage weather location to ".$link);
			# write the config file
			file_put_contents($configPath,$link);
		}
	}
	echo "<hr><a class='button' href='/settings/weather.php#setWeatherHomepageLocation'>BACK</a><hr>";
	clear();
}else if (array_key_exists("homepageFortuneStatus",$_POST)){
	$link=$_POST['homepageFortuneStatus'];
	echo "Running homepageFortuneStatus on location ".$link."<br>\n";
	# read the link and create a custom config
	$configPath="/etc/2web/fortuneStatus.cfg";
	outputLog("Checking for Config file ".$configPath);
	if ( $link == "disabled"){
		outputLog("Disabled Homepage fortune");
		# this means to remove the link
		unlink($configPath);
	}else{
		# write the libary path to a file at the configPath if the path does not already exist
		if ( ! file_exists($configPath)){
			outputLog("Setting homepage fortune to ".$link);
			# write the config file
			file_put_contents($configPath,$link);
		}
	}
	echo "<hr><a class='button' href='/settings/system.php#homepageFortuneStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("wiki2webStatus",$_POST)){
	$status=$_POST['wiki2webStatus'];
	setModStatus("wiki2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#wiki2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("graph2webStatus",$_POST)){
	$status=$_POST['graph2webStatus'];
	setModStatus("graph2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#graph2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("nfo2webStatus",$_POST)){
	$status=$_POST['nfo2webStatus'];
	setModStatus("nfo2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#nfo2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("comic2webStatus",$_POST)){
	$status=$_POST['comic2webStatus'];
	setModStatus("comic2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#comic2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("music2webStatus",$_POST)){
	$status=$_POST['music2webStatus'];
	setModStatus("music2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#music2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("iptv2webStatus",$_POST)){
	$status=$_POST['iptv2webStatus'];
	setModStatus("iptv2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#iptv2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("weather2webStatus",$_POST)){
	$status=$_POST['weather2webStatus'];
	setModStatus("weather2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#weather2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("kodi2webStatus",$_POST)){
	$status=$_POST['kodi2webStatus'];
	setModStatus("kodi2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#kodi2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("ytdl2nfoStatus",$_POST)){
	$status=$_POST['ytdl2nfoStatus'];
	setModStatus("ytdl2nfo",$status);
	echo "<hr><a class='button' href='/settings/modules.php#ytdl2nfoStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("git2webStatus",$_POST)){
	$status=$_POST['git2webStatus'];
	setModStatus("git2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#git2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addComicDownloadLink",$_POST)){
	$link=$_POST['addComicDownloadLink'];
	outputLog("Running addComicDownloadLink on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/comics/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding ".$link." to comic downloader...<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/comicsDL.php#addComicDownloadLink'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addWebComicDownload",$_POST)){
	$link=$_POST['addWebComicDownload'];
	outputLog("Running addWebComicDownload on comic ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/comics/webSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding ".$link." to comic downloader...<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/comicsDL.php#addWebComicDownload'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeWebComicDownload",$_POST)){
	$link=$_POST['removeWebComicDownload'];
	outputLog("Running removeWebComicDownload on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/comics/webSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		outputLog("Removing webComicDownloadLink ".$link);
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/comicsDL.php#removeWebComicDownload'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeComicDownloadLink",$_POST)){
	$link=$_POST['removeComicDownloadLink'];
	outputLog("Running removeComicDownloadLink on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/comics/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		outputLog("Removing comicDownloadLink ".$link);
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/comicsDL.php#currentLinks'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addComicLibrary",$_POST)){
	$link=$_POST['addComicLibrary'];
	outputLog("Running addComicLibrary on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/comics/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding libary ".$link);
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/comics.php#comiclibraryPaths'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeComicLibrary",$_POST)){
	$link=$_POST['removeComicLibrary'];
	outputLog("Running removeComicLibrary on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/comics/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing libary ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/comics.php#comiclibraryPaths'>BACK</a><hr>";
	clear();
}else if (array_key_exists("moveToBottom",$_POST)){
	$link=$_POST['moveToBottom'];
	outputLog("Running moveToBottom on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if (file_exists($configPath)){
		echo "Moving to bottom of list ".$link."<br>\n";
		# write the config file
		touch($configPath);
	}
	echo "<hr><a class='button' href='/settings/tv.php#currentLinks'>BACK</a><hr>";
	clear();
}else if (array_key_exists("moveCustomToBottom",$_POST)){
	$link=$_POST['moveCustomToBottom'];
	outputLog("Running moveCustomToBottom on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".m3u";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if (file_exists($configPath)){
		echo "Moving to bottom of list ".$link."<br>\n";
		# write the config file
		touch($configPath);
	}
	echo "<hr><a class='button' href='/settings/tv.php#currentLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeLink",$_POST)){
	$link=$_POST['removeLink'];
	outputLog("Running removeLink on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/tv.php#currentLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeLibary",$_POST)){
	$link=$_POST['removeLibary'];
	outputLog("Running removeLibary on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/nfo/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/nfo.php#libaryPaths'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeRadioLink",$_POST)){
	$link=$_POST['removeRadioLink'];
	outputLog("Running removeRadioLink on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/radioSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/radio.php#radioLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeCustomLink",$_POST)){
	$link=$_POST['removeCustomLink'];
	outputLog("Running removeCustomLink on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".m3u";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/radio.php#radioLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("blockLink",$_POST)){
	$link=$_POST['blockLink'];
	outputLog("Running blockLink on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( ! file_exists($configPath)){
		echo "Blocking link ".$link."<br>\n";
		# create the blocked link file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/radio.php#radioLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("unblockLink",$_POST)){
	$link=$_POST['unblockLink'];
	outputLog("Running unblockLink on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Unblocking link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/iptv_blocked.php#ActiveBlockedGroups'>BACK</a><hr>";
	clear();
}else if(array_key_exists("blockGroup",$_POST)){
	$link=$_POST['blockGroup'];
	outputLog("Running blockGroup on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedGroups.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( ! file_exists($configPath)){
		echo "Blocking link ".$link."<br>\n";
		# create the blocked link file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/iptv_blocked.php#ActiveBlockedGroups'>BACK</a><hr>";
	clear();
}else if(array_key_exists("unblockGroup",$_POST)){
	$link=$_POST['unblockGroup'];
	outputLog("Running unblockGroup on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedGroups.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Unblocking link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/iptv_blocked.php#ActiveBlockedGroups'>BACK</a><hr>";
	clear();
}else if(array_key_exists("theme",$_POST)){
	$theme=$_POST["theme"];
	outputLog("Changing theme to ".$theme);
	file_put_contents("/etc/2web/theme.cfg",$theme);
	echo "<hr><a class='button' href='/settings/system.php#webTheme'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addMusicLibary",$_POST)){
	$link=$_POST['addMusicLibary'];
	outputLog("Running addMusicLibary on link ".$link);
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/music/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding libary ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	echo "<hr><a class='button' href='/settings/music.php#musicLibaryPaths'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeMusicLibary",$_POST)){
	$link=$_POST['removeMusicLibary'];
	outputLog("Running removeMusicLibary on link ".$link);
	$sumOfLink=md5($link);
	$configPath="/etc/2web/music/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing libary ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	echo "<hr><a class='button' href='/settings/music.php#musicLibaryPaths'>BACK</a><hr>";
	clear();
}else{
	countdown(5);
	echo "<h1>[ERROR]:UNKNOWN COMMAND SUBMITTED TO API</h1>";
	echo "<ul>";
	echo "<li>";
	print_r($_POST);
	echo "</li>";
	echo "<li>This incident will be logged.</li>";
	echo "<li>If you are lost <a href='/settings/index.php'>here</a> is a link back to the homepage.</li>";
	echo "</ul>";
}
?>

</div>

<?php
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
