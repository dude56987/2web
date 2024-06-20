<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web administrative API
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
-->
<html class='randomFanart'>
<head>
	<link rel='stylesheet' href='/style.css'>
	<script src='/2webLib.js'></script>
</head>
<body>

<?php
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>

<div class='settingListCard'>
<h1>Running adminstrative action, This will be logged!</h1>
<img class='globalPulse' src='/pulse.gif'>
<?php
# enable error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);
////////////////////////////////////////////////////////////////////////////////
function clear(){
	# Flush output to the users webpage.
	#
	# RETURN OUTPUT
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
function yesNoCfgSet($configPath, $newConfigSetting){
	# Set a yes/no configuration file to a set value, The value must be 'yes' or it will be set to no.
	#
	# RETURN FILES
	// create a sum for the value for updating the session
	$configPathSum=md5($configPath);
	#
	outputLog("Setting $configPath status to $newConfigSetting");
	if (strtolower($newConfigSetting) == "yes"){
		# store the updated value in the local session
		sessionSetValue($configPathSum,true);
		# store the update value on disk
		file_put_contents($configPath , "yes");
		outputLog("$configPath saved as 'yes'");
	}else{
		# store the updated value in the local session
		sessionSetValue($configPathSum,false);
		# set the file to disabled if anything other than yes is set
		file_put_contents($configPath, "no");
		outputLog("$configPath saved as 'no'");
	}
	return true;
}
////////////////////////////////////////////////////////////////////////////////
function outputLog($stringData,$class="outputLog"){
	# Write output and do three dots with randomized delays to simulate processing
	#
	# RETURN OUTPUT

	# write $stringData to the log then to the webpage
	addToLog("ADMIN","Admin Action by user '".$_SESSION["user"]."'","$stringData");
	echo "<div class='$class'>";
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
	echo "</div>";
}
////////////////////////////////////////////////////////////////////////////////
function setModStatus($modName,$modStatus){
	# Enable/Disable a module
	#
	# RETURN FILES
	outputLog("Setting $modName status to ".$modStatus);
	# read the link and create a custom config
	$configPath="/etc/2web/mod_status/".$modName.".cfg";
	#
	yesNoCfgSet($configPath, $modStatus);
}
////////////////////////////////////////////////////////////////////////////////
function usablePath($path){
	# check if the file path exists
	# check the path info
	$path=explode("/",$path);
	$pathCollector="/";
	foreach($path as $pathPart){
		# if the path is not empty
		if($pathPart != ""){
			# check the pathpart
			$pathCollector .= $pathPart."/";
			if(is_executable($pathCollector)){
				outputLog("$pathCollector is executable path, this is good", "goodLog");
			}else{
				outputLog("$pathCollector is NOT a executable path, this is bad", "badLog");
				outputLog("$pathCollector may not even exist on the server, this is bad", "badLog");
				outputLog("The path given has incorrect permissions", "badLog");
				$tempOutputData="Use ";
				$tempOutputData.="<pre>";
				$tempOutputData.="mkdir -p \"$pathCollector\"\n";
				$tempOutputData.="chmod +X \"$pathCollector\"\n";
				$tempOutputData.="</pre>";
			 	$tempOutputData.="on the server to create the directory and fix the permissions. Then try to add the path again.";
				outputLog($tempOutputData, "badLog");
				$errorMessage="Use <pre>chmod +X $pathCollector</pre> on the server to fix the permissions. Then try to add the path again.";
				addToLog("ERROR","Incorrect Permissions", $errorMessage);
				return false;
			}
		}
	}
	return true;
}
////////////////////////////////////////////////////////////////////////////////
function addCustomPathConfig($keyName, $baseConfigPath, $settingsWebpage){
	# check if the path is usable
	if (usablePath($_POST[$keyName])){
		# if the path is usable add it
		addCustomConfig($keyName, $baseConfigPath, $settingsWebpage);
	}else{
		# if the check failed check if the force set config was set
		if (array_key_exists("forceSetConfig",$_POST)){
			if ($_POST["forceSetConfig"] == "yes"){
				outputLog("Force setting the configuration");
				# if the bypass has been set create the config anyway
				addCustomConfig($keyName, $baseConfigPath, $settingsWebpage);
			}else{
				outputLog("ForceSetConfig was not enabled");
				# draw the back button
				echo "<div class='listCard'>";
				echo "<a class='button' href='$settingsWebpage'>Back</a>";
				echo "</div>";
			}
		}else{
			# draw the override button to ignore the path errors
			echo "<div class='titleCard'>";
			echo "	<form action='admin.php' method='post'>";
			echo "		<h2>Override Path Checking</h2>";
			echo "		<ul>";
			echo "			<li>This will force adding the path to the system.</li>";
			echo "			<li>This will cause the server to upgrade some random requests to HTTPS from HTTP.</li>";
			echo "			<li>This will most likely break the VLC player buttons on mobile devices for anything added from this path.</li>";
			echo "			<li>It is recomended that your administrator fix permissions for this directory before you add this path.</li>";
			echo "		</ul>";
			echo "		<input width='60%' type='text' name='".$keyName."' value='".$_POST[$keyName]."' hidden />";
			echo "		<input width='60%' type='text' name='forceSetConfig' value='yes' hidden />";
			echo "		<button class='button' type='submit'>🪠 Force Adding the Path</button>";
			echo "	</form>";
			echo "</div>";

			# draw the back button
			echo "<div class='listCard'>";
			echo "<a class='button' href='$settingsWebpage'>Back</a>";
			echo "</div>";
		}
	}
}
////////////////////////////////////////////////////////////////////////////////
function addCustomConfig($keyName, $baseConfigPath, $settingsWebpage){
	# Add a custom config file for a list in a 2web module
	# - $link is the link to be added to this location
	# - $settingsWebpage is a webpage name from the settings directory in the webserver
	#  - Example: "radio.php"
	$data=$_POST[$keyName];
	outputLog("Running ".$keyName." on ".$data, "goodLog");
	$sumOfLink=md5($data);
	# Create the config containing directory
	if (! is_dir($baseConfigPath)){
		# create directory for config if it does not exist
		mkdir($baseConfigPath);
	}
	# read the link and create a custom config
	$configPath=$baseConfigPath.$sumOfLink.".cfg";
	outputLog("Checking for Config file ".$configPath, "goodLog");
	# write the data to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		outputLog("Adding ".$data, "goodLog");
		# write the config file
		file_put_contents($configPath,$data);
	}else{
		outputLog("File already exists at ".$configPath, "badLog");
	}
	echo "<div class='listCard'>";
	echo "<hr><a class='button' href='/settings/".$settingsWebpage."#".$keyName."'>BACK</a><hr>";
	echo "</div>";
	clear();
}
////////////////////////////////////////////////////////////////////////////////
function removeCustomConfig($keyName, $baseConfigPath, $settingsWebpage){
	# Remove custom config file
	#
	# RETURN FILES
	$data=$_POST[$keyName];
	outputLog("Running ".$keyName." on ".$data, "goodLog");
	$sumOfLink=md5($data);
	# read the link and create a custom config
	$configPath=$baseConfigPath.$sumOfLink.".cfg";
	outputLog("Checking for Config file ".$configPath, "goodLog");
	# write the data to a file at the configPath if the path does not already exist
	if ( file_exists($configPath)){
		outputLog("Removing ".$data." from ".$configPath, "goodLog");
		# delete the custom config created for the link
		unlink($configPath);
	}else{
		outputLog("Can not remove non existing file from ".$configPath, "badLog");
	}
	echo "<hr><a class='button' href='/settings/".$settingsWebpage."#".$keyName."'>BACK</a><hr>";
	clear();
}
////////////////////////////////////////////////////////////////////////////////
# clean up the post input before processing
cleanPostInput();
////////////////////////////////////////////////////////////////////////////////
// Start processing data
////////////////////////////////////////////////////////////////////////////////
if (array_key_exists("newUserName",$_POST)){
	# make all chacters lowercase for password
	$userName=strtolower($_POST['newUserName']);
	outputLog("Creating new user '$userName'");
	if (array_key_exists("newUserPass",$_POST)){
		# Verify the password is the same in both fields
		if (array_key_exists("newUserPassVerify",$_POST)){
			if($_POST["newUserPass"] == $_POST["newUserPassVerify"]){
				# the passwords are the same, build the user account
				if ( ! file_exists("/etc/2web/users/")){
					# create the users directory if it does not exist
					mkdir("/etc/2web/users/");
				}
				if ( ! file_exists("/etc/2web/groups/")){
					mkdir("/etc/2web/groups/");
				}
				if ( ! file_exists("/etc/2web/groups/admin/")){
					mkdir("/etc/2web/groups/admin/");
				}
				# check if the username already exists
				if (file_exists("/etc/2web/users/".$userName.".cfg")){
					# the username has already exists
					outputLog("The user '".$userName."' already exists!", "badLog");
					outputLog("Processing failed!", "badLog");
				}else{
					# build the password hash
					$passSum=password_hash($_POST["newUserPass"],PASSWORD_DEFAULT);
					# save the password
					file_put_contents("/etc/2web/users/".$userName.".cfg",$passSum);
					# add admin to the admin group
					touch("/etc/2web/groups/admin/".$userName.".cfg");
					outputLog("The user '".$userName."' has been created!", "goodLog");
				}
			}else{
				# the passwords are diffrent, fail out
				outputLog("The passwords given are diffrent, You must verify the password to create a new account!", "badLog");
				outputLog("Processing failed!", "badLog");
			}
		}else{
			# no verification was given for the password entered
			outputLog("You did not verify the password given, Please verify the password to create a account!", "badLog");
			outputLog("Processing failed!", "badLog");
		}
	}else{
		outputLog("No password was given for the new user!", "badLog");
		outputLog("Processing failed!", "badLog");
	}
	echo ("<hr><a class='button' href='/settings/users.php'>BACK</a><hr>");
}else if (array_key_exists("removeUser",$_POST)){
	$userName=$_POST['removeUser'];
	outputLog("Removing user $userName from authorization list");
	unlink("/etc/2web/users/".$userName.".cfg");
	echo ("<hr><a class='button' href='/settings/users.php'>BACK</a><hr>");
}else if (array_key_exists("addUserToGroup_userName",$_POST)){
	$userName=$_POST['addUserToGroup_userName'];
	$groupName=$_POST['addUserToGroup_groupName'];
	# if the user name exists
	if (file_exists("/etc/2web/users/".$userName.".cfg")){
		# if the user is already in the group
		if (file_exists("/etc/2web/groups/".$groupName."/".$userName.".cfg")){
			# the user is already in the group
			outputLog("The user '".$userName."' is already in this group '".$groupName."'","badLog");
		}else{
			# the user does not exist in the group yet
			# add the user to the group by creating the file
			outputLog("Adding the user '".$userName."' to the group '".$groupName."'","goodLog");
			touch("/etc/2web/groups/".$groupName."/".$userName.".cfg");
		}
	}else{
		outputLog("The user '".$userName."' does not yet exist so it can not be added to the group '".$groupName."'","badLog");
	}
	echo ("<hr><a class='button' href='/settings/users.php'>BACK</a><hr>");
}else if (array_key_exists("removeUserFromGroup_userName",$_POST)){
	$userName=$_POST['removeUserFromGroup_userName'];
	$groupName=$_POST['removeUserFromGroup_groupName'];
	# if the user name exists
	if (file_exists("/etc/2web/users/".$userName.".cfg")){
		# if the user is already in the group
		if (file_exists("/etc/2web/groups/".$groupName."/".$userName.".cfg")){
			# the user is already in the group
			# remove the user from the group
			outputLog("Removing the user '".$userName."' from the group '".$groupName."'","goodLog");
			unlink("/etc/2web/groups/".$groupName."/".$userName.".cfg");
		}else{
			# the user does not exist in the group yet
			outputLog("The user '".$userName."' does not yet exist in this group. So it can not be added to the group '".$groupName."'","badLog");
		}
	}
	echo ("<hr><a class='button' href='/settings/users.php'>BACK</a><hr>");
}else if (array_key_exists("newBasicUserName",$_POST)){
	# make all chacters lowercase for password
	$userName=strtolower($_POST['newBasicUserName']);
	outputLog("Creating new user '$userName'");
	if (array_key_exists("newUserPass",$_POST)){
		# Verify the password is the same in both fields
		if (array_key_exists("newUserPassVerify",$_POST)){
			if($_POST["newUserPass"] == $_POST["newUserPassVerify"]){
				# the passwords are the same, build the user account
				if ( ! file_exists("/etc/2web/users/")){
					# create the users directory if it does not exist
					mkdir("/etc/2web/users/");
				}
				if ( ! file_exists("/etc/2web/groups/")){
					mkdir("/etc/2web/groups/");
				}
				if ( ! file_exists("/etc/2web/groups/admin/")){
					mkdir("/etc/2web/groups/admin/");
				}
				# check if the username already exists
				if (file_exists("/etc/2web/users/".$userName.".cfg")){
					# the username has already exists
					outputLog("The user '".$userName."' already exists!", "badLog");
					outputLog("Processing failed!", "badLog");
				}else{
					# build the password hash
					$passSum=password_hash($_POST["newUserPass"],PASSWORD_DEFAULT);
					# save the password
					file_put_contents("/etc/2web/users/".$userName.".cfg",$passSum);
					# add base users to the base 2web group, e.g. homepage, playlists, help
					touch("/etc/2web/groups/2web/".$userName.".cfg");
					# output log for user feedback
					outputLog("The user '".$userName."' has been created!", "goodLog");
				}
			}else{
				# the passwords are diffrent, fail out
				outputLog("The passwords given are diffrent, You must verify the password to create a new account!", "badLog");
				outputLog("Processing failed!", "badLog");
			}
		}else{
			# no verification was given for the password entered
			outputLog("You did not verify the password given, Please verify the password to create a account!", "badLog");
			outputLog("Processing failed!", "badLog");
		}
	}else{
		outputLog("No password was given for the new user!", "badLog");
		outputLog("Processing failed!", "badLog");
	}
	echo ("<hr><a class='button' href='/settings/users.php'>BACK</a><hr>");
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
}else if (array_key_exists("setSessionTimeoutMinutes",$_POST)){
	$timeoutMinutes=$_POST['setSessionTimeoutMinutes'];
	$timeoutHours=$_POST['setSessionTimeoutHours'];
	outputLog("Setting Session Timeout minutes to $timeoutMinutes");
	file_put_contents("/etc/2web/loginTimeoutMinutes.cfg",$timeoutMinutes);
	outputLog("Setting Session Timeout hours to $timeoutHours");
	file_put_contents("/etc/2web/loginTimeoutHours.cfg",$timeoutHours);
	outputLog("Set to timeout after ".$timeoutHours." hours and ".$timeoutMinutes." minutes", "goodLog");
	echo "<hr><a class='button' href='/settings/users.php#loginInactivityTimeout'>BACK</a><hr>";
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
			$content="#EXTM3U\n".'#EXTINF:-1 tvg-logo="'.$linkIcon.'",'.$linkTitle."\n".$link;
			outputLog("Checking for Config file ".$configPath);
			# write the link to a file at the configPath if the path does not already exist
			/*
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
				file_put_contents($configPath,$content);
			*/
			if ( ! file_exists($configPath)){
				$fileObject=fopen($configPath,'w');
				outputLog("Adding link ".$link);
				# write the config file
				fwrite($fileObject,$content);
				fclose($fileObject);
			}else{
				outputLog("[ERROR]: Custom link creation failed '".$link."' '$configPath' already exists!");
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
}else if (array_key_exists("randomTheme",$_POST)){
	outputLog("Setting randomize theme status to ".$_POST['randomTheme']);
	yesNoCfgSet("/etc/2web/randomTheme.cfg", $_POST['randomTheme']);
	echo "<hr><a class='button' href='/settings/themes.php#randomTheme'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addKodiLocation",$_POST)){
	# add kodi location
	addCustomConfig("addKodiLocation","/etc/2web/kodi/location.d/","kodi.php");
}else if (array_key_exists("removeKodiLocation",$_POST)){
	# remove kodi location
	removeCustomConfig("removeKodiLocation","/etc/2web/kodi/location.d/","kodi.php");
}else if (array_key_exists("addKodiPlayer",$_POST)){
	# set the player for building play on kodi links
	addCustomConfig("addKodiPlayer","/etc/2web/kodi/players.d/","kodi.php");
}else if (array_key_exists("removeKodiPlayer",$_POST)){
	# set the player that play on kodi links will send playback commands to
	removeCustomConfig("removeKodiPlayer","/etc/2web/kodi/players.d/","kodi.php");
}else if (array_key_exists("addPortalScanSource",$_POST)){
	# add portal scan source
	addCustomConfig("addPortalScanSource","/etc/2web/portal/scanSources.d/","portal.php");
}else if (array_key_exists("removePortalScanSource",$_POST)){
	# remove portal scan source
	removeCustomConfig("removePortalScanSource","/etc/2web/portal/scanSources.d/","portal.php");
}else if (array_key_exists("addPortalSource",$_POST)){
	# add portal source
	addCustomConfig("addPortalSource","/etc/2web/portal/sources.d/","portal.php");
}else if (array_key_exists("removePortalSource",$_POST)){
	# remove portal source
	removeCustomConfig("removePortalSource","/etc/2web/portal/sources.d/","portal.php");
}else if (array_key_exists("addPortalScanPort",$_POST)){
	# add portal scan port
	addCustomConfig("addPortalScanPort","/etc/2web/portal/scanPorts.d/","portal.php");
}else if (array_key_exists("removePortalScanPort",$_POST)){
	# remove portal scan port
	removeCustomConfig("removePortalScanPort","/etc/2web/portal/scanPorts.d/","portal.php");
}else if (array_key_exists("addPortalScanPath",$_POST)){
	# add portal scan path
	addCustomConfig("addPortalScanPath","/etc/2web/portal/scanPaths.d/","portal.php");
}else if (array_key_exists("removePortalScanPath",$_POST)){
	# remove portal scan path
	removeCustomConfig("removePortalScanPath","/etc/2web/portal/scanPaths.d/","portal.php");
}else if (array_key_exists("scanAvahi",$_POST)){
	outputLog("Changing setting to scan for services using avahi on the local network to '".$_POST['scanAvahi']."'");
	yesNoCfgSet("/etc/2web/portal/scanAvahi.cfg", $_POST['scanAvahi']);
	echo "<hr><a class='button' href='/settings/portal.php#scanAvahi'>BACK</a><hr>";
	clear();
}else if (array_key_exists("aiSubsGenerate",$_POST)){
	outputLog("Setting AI lyrics generation status to ".$_POST['aiSubsGenerate']);
	# generate subtitles for nfo2web movies/shows
	yesNoCfgSet("/etc/2web/ai/aiSubsGenerate.cfg", $_POST['aiSubsGenerate']);
	echo "<hr><a class='button' href='/settings/ai.php#aiSubsGenerate'>BACK</a><hr>";
	clear();
}else if (array_key_exists("aiLyricsGenerate",$_POST)){
	outputLog("Setting AI lyrics generation status to ".$_POST['aiLyricsGenerate']);
	# generate lyrics for music2web tracks
	yesNoCfgSet("/etc/2web/ai/aiLyricsGenerate.cfg", $_POST['aiLyricsGenerate']);
	echo "<hr><a class='button' href='/settings/ai.php#aiLyricsGenerate'>BACK</a><hr>";
	clear();
}else if (array_key_exists("aiCompareGenerate",$_POST)){
	outputLog("Setting AI comparison generation status to ".$_POST['aiCompareGenerate']);
	# run the ai comparison generators
	yesNoCfgSet("/etc/2web/ai/aiCompareGenerate.cfg", $_POST['aiCompareGenerate']);
	echo "<hr><a class='button' href='/settings/ai.php#aiCompareGenerate'>BACK</a><hr>";
	clear();
}else if (array_key_exists("nomediaFiles",$_POST)){
	outputLog("Setting Status for .nomedia files ".$_POST['nomediaFiles']);
	yesNoCfgSet("/etc/2web/kodi/nomediaFiles.cfg", $_POST['nomediaFiles']);
	echo "<hr><a class='button' href='/settings/kodi.php#nomediaFiles'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addAiPromptModel",$_POST)){
	addCustomConfig("addAiPromptModel","/etc/2web/ai/promptModels.d/","ai.php");
}else if (array_key_exists("removeAiPromptModel",$_POST)){
	removeCustomConfig("removeAiPromptModel","/etc/2web/ai/promptModels.d/","ai.php");
}else if (array_key_exists("add_ai_txt2img_model",$_POST)){
	addCustomConfig("add_ai_txt2img_model","/etc/2web/ai/txt2imgModels.d/","ai.php");
}else if (array_key_exists("remove_ai_txt2img_model",$_POST)){
	removeCustomConfig("remove_ai_txt2img_model","/etc/2web/ai/txt2imgModels.d/","ai.php");
}else if (array_key_exists("generateVisualisationsForWeb",$_POST)){
	outputLog("Setting music2web visual generation status to ".$_POST['generateVisualisationsForWeb']);
	# run the ai comparison generators
	yesNoCfgSet("/etc/2web/music/generateVisualisationsForWeb.cfg", $_POST['generateVisualisationsForWeb']);
	echo "<hr><a class='button' href='/settings/music.php#generateVisualisationsForWeb'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addLink",$_POST)){
	addCustomConfig("addLink","/etc/2web/iptv/sources.d/","tv.php");
}else if (array_key_exists("ytdl_add_source",$_POST)){
	addCustomConfig("ytdl_add_source","/etc/2web/ytdl/sources.d/","ytdl2nfo.php");
}else if(array_key_exists("ytdl_remove_source",$_POST)){
	removeCustomConfig("ytdl_remove_source","/etc/2web/ytdl/sources.d/","ytdl2nfo.php");
}else if (array_key_exists("ytdl_add_username_source",$_POST)){
	addCustomConfig("ytdl_add_username_source","/etc/2web/ytdl/usernameSources.d/","ytdl2nfo.php");
}else if(array_key_exists("ytdl_remove_username_source",$_POST)){
	removeCustomConfig("ytdl_remove_username_source","/etc/2web/ytdl/usernameSources.d/","ytdl2nfo.php");
}else if (array_key_exists("addLibary",$_POST)){
	addCustomPathConfig("addLibary","/etc/2web/nfo/libaries.d/","nfo.php");
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
}else if (array_key_exists("playOnKodiButton",$_POST)){
	$link=$_POST['playOnKodiButton'];
	yesNoCfgSet("/etc/2web/kodi/playOnKodiButton.cfg", $_POST['playOnKodiButton']);
	echo "<hr><a class='button' href='/settings/kodi.php#playOnKodiButton'>BACK</a><hr>";
	clear();
}else if (array_key_exists("homepageFortuneStatus",$_POST)){
	$link=$_POST['homepageFortuneStatus'];
	yesNoCfgSet("/etc/2web/fortuneStatus.cfg", $_POST['homepageFortuneStatus']);
	echo "<hr><a class='button' href='/settings/system.php#homepageFortuneStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("webPlayerStatus",$_POST)){
	$link=$_POST['webPlayerStatus'];
	yesNoCfgSet("/etc/2web/webPlayer.cfg", $_POST['webPlayerStatus']);
	echo "<hr><a class='button' href='/settings/system.php#webpagePlayerStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("webClientStatus",$_POST)){
	$link=$_POST['webClientStatus'];
	yesNoCfgSet("/etc/2web/client.cfg", $_POST['webClientStatus']);
	echo "<hr><a class='button' href='/settings/system.php#webClientStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("lockGroup",$_POST)){
	$group=$_POST["lockGroup"];
	if (file_exists("/etc/2web/lockedGroups/".$group.".cfg")){
		outputLog("The group '".$group."' is already locked, Nothing is to be done.","badLog");
	}else{
		outputLog("Locking access to the group '".$group."'","goodLog");
		touch("/etc/2web/lockedGroups/".$group.".cfg");
	}
	echo "<hr><a class='button' href='/settings/users.php#groupLock_".$group."'>BACK</a><hr>";
	clear();
}else if (array_key_exists("unlockGroup",$_POST)){
	$group=$_POST["unlockGroup"];
	if (file_exists("/etc/2web/lockedGroups/".$group.".cfg")){
		outputLog("Unlocking access to the group '".$group."'","goodLog");
		unlink("/etc/2web/lockedGroups/".$group.".cfg");
	}else{
		outputLog("The group '".$group."' is already unlocked, Nothing is to be done.","badLog");
	}
	echo "<hr><a class='button' href='/settings/users.php#groupLock_".$group."'>BACK</a><hr>";
	clear();
}else if (array_key_exists("rss2nfoStatus",$_POST)){
	$status=$_POST['rss2nfoStatus'];
	setModStatus("rss2nfo",$status);
	echo "<hr><a class='button' href='/settings/modules.php#rss2nfoStatus'>BACK</a><hr>";
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
	# also enable epg2web if iptv2web is enabled
	setModStatus("epg2web",$status);
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
}else if (array_key_exists("ai2webStatus",$_POST)){
	$status=$_POST['ai2webStatus'];
	setModStatus("ai2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#ai2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("portal2webStatus",$_POST)){
	$status=$_POST['portal2webStatus'];
	setModStatus("portal2web",$status);
	echo "<hr><a class='button' href='/settings/modules.php#portal2webStatus'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addComicDownloadLink",$_POST)){
	addCustomConfig("addComicDownloadLink","/etc/2web/comics/sources.d/","comicsDL.php");
}else if (array_key_exists("addWebComicDownload",$_POST)){
	addCustomConfig("addWebComicDownload","/etc/2web/comics/webSources.d/","comicsDL.php");
}else if(array_key_exists("removeWebComicDownload",$_POST)){
	removeCustomConfig("removeWebComicDownload","/etc/2web/comics/webSources.d/","comicsDL.php");
}else if(array_key_exists("removeComicDownloadLink",$_POST)){
	removeCustomConfig("removeComicDownloadLink","/etc/2web/comics/sources.d/","comicsDL.php");
}else if (array_key_exists("addComicLibrary",$_POST)){
	addCustomPathConfig("addComicLibrary","/etc/2web/comics/libaries.d/","comics.php");
}else if(array_key_exists("removeComicLibrary",$_POST)){
	removeCustomConfig("removeComicLibrary","/etc/2web/comics/libaries.d/","comics.php");
}else if (array_key_exists("addWikiPath",$_POST)){
	addCustomPathConfig("addWikiPath","/etc/2web/wiki/libraries.d/","wiki.php");
}else if(array_key_exists("removeWikiPath",$_POST)){
	removeCustomConfig("removeWikiPath","/etc/2web/wiki/libraries.d/","wiki.php");
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
	removeCustomConfig("removeLink","/etc/2web/iptv/sources.d/","tv.php");
}else if(array_key_exists("removeLibary",$_POST)){
	removeCustomConfig("removeLibary","/etc/2web/nfo/libaries.d/","nfo.php");
}else if(array_key_exists("removeRadioLink",$_POST)){
	removeCustomConfig("removeRadioLink","/etc/2web/iptv/radioSources.d/","radio.php");
}else if(array_key_exists("removeCustomLink",$_POST)){
	removeCustomConfig("removeCustomLink","/etc/2web/iptv/sources.d/","radio.php");
}else if(array_key_exists("blockLink",$_POST)){
	addCustomConfig("blockLink","/etc/2web/iptv/blockedLinks.d/","iptv_blocked.php");
}else if(array_key_exists("unblockLink",$_POST)){
	removeCustomConfig("unblockLink","/etc/2web/iptv/blockedLinks.d/","iptv_blocked.php");
}else if(array_key_exists("blockGroup",$_POST)){
	addCustomConfig("blockGroup","/etc/2web/iptv/blockedGroups.d/","iptv_blocked.php");
}else if(array_key_exists("unblockGroup",$_POST)){
	removeCustomConfig("unblockGroup","/etc/2web/iptv/blockedGroups.d/","iptv_blocked.php");
}else if(array_key_exists("theme",$_POST)){
	$theme=$_POST["theme"];
	outputLog("Changing theme to ".$theme);
	# write the new theme to the config file
	file_put_contents("/etc/2web/theme.cfg",$theme);
	# remove existing symlinks
	if (file_exists("/var/cache/2web/web/style.css")){
		unlink("/var/cache/2web/web/style.css");
	}
	# recreate the symlink to update the website
	symlink(("/usr/share/2web/themes/".$theme),"/var/cache/2web/web/style.css");
	# draw the back button
	echo "<hr><a class='button' href='/settings/themes.php#webTheme'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addMusicLibary",$_POST)){
	addCustomPathConfig("addMusicLibary","/etc/2web/music/libaries.d/","music.php");
}else if(array_key_exists("removeMusicLibary",$_POST)){
	removeCustomConfig("removeMusicLibary","/etc/2web/music/libaries.d/","music.php");
}else if (array_key_exists("addRssSource",$_POST)){
	addCustomConfig("addRssSource","/etc/2web/rss/sources.d/","rss.php");
}else if(array_key_exists("removeRssSource",$_POST)){
	removeCustomConfig("removeRssSource","/etc/2web/rss/sources.d/","rss.php");
}else if (array_key_exists("addRepoLibrary",$_POST)){
	addCustomPathConfig("addRepoLibrary","/etc/2web/repos/libaries.d/","repos.php");
}else if(array_key_exists("removeRepoLibrary",$_POST)){
	removeCustomConfig("removeRepoLibrary","/etc/2web/repos/libaries.d/","repos.php");
}else if (array_key_exists("addRepoSource",$_POST)){
	addCustomConfig("addRepoSource","/etc/2web/repos/sources.d/","repos.php");
}else if(array_key_exists("removeRepoSource",$_POST)){
	removeCustomConfig("removeRepoSource","/etc/2web/repos/sources.d/","repos.php");
}else if (array_key_exists("repoRenderVideo",$_POST)){
	outputLog("Render gource videos for repos ".$_POST['repoRenderVideo']);
	yesNoCfgSet("/etc/2web/repos/renderVideo.cfg", $_POST['repoRenderVideo']);
	echo "<hr><a class='button' href='/settings/repos.php#repoRenderVideo'>BACK</a><hr>";
	clear();
}else if (array_key_exists("colorName",$_POST)){
	# load the template name
	$newColorName=$_POST["colorName"];
	# load each of the elements
	$solidBackground = str_replace("\\", "", $_POST["solidBackground"]);
	$glassBackground = str_replace("\\#", "", $_POST["glassBackground"]);
	$borderColor = str_replace("\\", "", $_POST["borderColor"]);
	$textColor = str_replace("\\", "", $_POST["textColor"]);
	$shadowColor = str_replace("\\", "", $_POST["shadowColor"]);
	$highlightText = str_replace("\\", "", $_POST["highlightText"]);
	$highlightBackground = str_replace("\\", "", $_POST["highlightBackground"]);
	$highlightBorder = str_replace("\\", "", $_POST["highlightBorder"]);
	# fill in values to the template
	$newColorStyle = "";
	$newColorStyle .= ":root{\n";
	$newColorStyle .= "	--solidBackground: ".$solidBackground.";\n";
	$colorCode = hexdec(substr($glassBackground,0,2)).", ";
	$colorCode .= hexdec(substr($glassBackground,2,2)).", ";
	$colorCode .= hexdec(substr($glassBackground,4,2)).", ";
	$newColorStyle .= "	--glassBackground: rgba(".$colorCode."0.90);\n";
	$newColorStyle .= "	--borderColor: ".$borderColor.";\n";
	$newColorStyle .= "	--textColor: ".$textColor.";\n";
	$newColorStyle .= "	--shadowColor: ".$shadowColor.";\n";
	$newColorStyle .= "	--highlightText: ".$highlightText.";\n";
	$newColorStyle .= "	--highlightBackground: ".$highlightBackground.";\n";
	$newColorStyle .= "	--highlightBorder: ".$highlightBorder.";\n";
	$newColorStyle .= "	--staticBackground: radial-gradient(var(--solidBackground), var(--glassBackground));\n";
	$newColorStyle .= "}\n";
	#
	$themePath="/usr/share/2web/theme-templates/color-".$newColorName.".css";
	#
	outputLog("Writing the new generated theme to '".$themePath."'");
	# save the new template
	file_put_contents($themePath, $newColorStyle);
	# new theme will be generated on next run of '2web' command
	echo "<hr><a class='button' href='/settings/themes.php#createColor'>BACK</a><hr>";
}else{
	addToLog("ERROR","UNKONWN ADMIN COMMAND",var_export($_POST, true));
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
<hr>
</div>

<?php
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
