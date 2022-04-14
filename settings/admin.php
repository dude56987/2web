<html class='randomFanart'>
<head>
	<link rel='stylesheet' href='style.css'>
</head>
<body>

<?php
include('header.php');
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
	echo "Done!";
	clear();
	sleep(1);
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
		shell_exec("htpasswd -cb /etc/2web/users/".($userName).".cfg '".($userName."' '".$userPass."'") );
		//echo ( "Writing ".($userName.":".$passSum)." to ".("/etc/2web/users/".md5($userName).".cfg")."<br>" );
		//file_put_contents( ("/etc/2web/users/".md5($userName).".cfg"), ($userName.":$".$passSum) );

		# create a new htaccces file
		//file_put_contents("/var/cache/nfo2web/.htaccess","$userName:$passSum");
	}
	countdown(5);
	echo "<hr><a class='button' href='/system.php#addNewUser'>BACK</a><hr>";
}else if (array_key_exists("removeUser",$_POST)){
	$userName=$_POST['removeUser'];
	echo "Removing user $userName from authorization list<br>\n";
	unlink("/etc/2web/users/$userName");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#removeUser'>BACK</a><hr>";
}else if (array_key_exists("all_update",$_POST)){
	echo "Scheduling 2web update!<br>\n";
	shell_exec("echo '2web update' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("all_webgen",$_POST)){
	echo "Scheduling 2web update!<br>\n";
	shell_exec("echo '2web webgen' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("nfo_update",$_POST)){
	echo "Scheduling nfo update!<br>\n";
	shell_exec("echo 'nfo2web update' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("nfo_webgen",$_POST)){
	echo "Scheduling nfo2web update!<br>\n";
	shell_exec("echo 'nfo2web webgen' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("iptv_update",$_POST)){
	echo "Scheduling iptv2web update!<br>\n";
	shell_exec("echo 'iptv2web update' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("iptv_webgen",$_POST)){
	echo "Scheduling iptv2web update!<br>\n";
	shell_exec("echo 'iptv2web webgen' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("comic_update",$_POST)){
	echo "Scheduling comic2web update!<br>\n";
	shell_exec("echo 'comic2web update' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
	clear();
}else if (array_key_exists("comic_webgen",$_POST)){
	echo "Scheduling comic2web update!<br>\n";
	shell_exec("echo 'comic2web webgen' | /usr/bin/at -q b now");
	countdown(5);
	echo "<hr><a class='button' href='/system.php#update'>BACK</a><hr>";
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
			echo "Checking for Config file ".$configPath."<br>\n";
			# write the link to a file at the configPath if the path does not already exist
			$fileObject=fopen($configPath,'w');
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
			//file_put_contents($configPath,$content);
				fwrite($fileObject,$content);
				fwrite($fileObject,$link);
				fclose($fileObject);
			}else{
				echo "[ERROR]: Custom Radio link creation failed '".$link."'<br>\n";
			}
		}else{
			echo "[ERROR]: Custom Radio Icon not found<br>";
		}
	}else{
		echo "[ERROR]: Custom Radio Title not found<br>";
	}
	countdown(5);
	echo "<hr><a class='button' href='/radio.php'>BACK</a><hr>";
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
			echo "Checking for Config file ".$configPath."<br>\n";
			# write the link to a file at the configPath if the path does not already exist
			/*
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
				file_put_contents($configPath,$content);
			*/
			$fileObject=fopen($configPath,'w');
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
				fwrite($fileObject,$content);
				fwrite($fileObject,$link);
				fclose($fileObject);
			}else{
				echo "[ERROR]: Custom link creation failed '".$link."'<br>\n";
			}
		}else{
			echo "[ERROR]: Custom Icon not found<br>";
		}
	}else{
		echo "[ERROR]: Custom Title not found<br>";
	}
	countdown(5);
	echo "<hr><a class='button' href='/tv.php'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addRadioLink",$_POST)){
	$link=$_POST['addRadioLink'];
	echo "Running addRadioLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/iptv/radioSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding link ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/radio.php#addRadioLink'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheQuality",$_POST)){
	$cacheQuality=$_POST['cacheQuality'];
	# change the default cache quality
	echo "Changing cache quality to '".$cacheQuality."'<br>\n";
	# write the config file
	file_put_contents("cacheQuality.cfg",$cacheQuality);
	countdown(5);
	echo "<hr><a class='button' href='/cache.php#cacheQuality'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheUpgradeQuality",$_POST)){
	$cacheUpgradeQuality=$_POST['cacheUpgradeQuality'];
	# change the default cache quality
	echo "Changing cache upgrade quality to '".$cacheUpgradeQuality."'<br>\n";
	# write the config file
	if ($cacheUpgradeQuality == 'no_upgrade'){
		unlink("cacheUpgradeQuality.cfg");
	}else{
		file_put_contents("cacheUpgradeQuality.cfg",$cacheUpgradeQuality);
	}
	countdown(5);
	echo "<hr><a class='button' href='/cache.php#cacheUpgradeQuality'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheFramerate",$_POST)){
	$cacheFramerate=$_POST['cacheFramerate'];
	# change the default cache quality
	echo "Changing cache mode to '".$cacheFramerate."'<br>\n";
	# write the config file
	if ($cacheFramerate == ''){
		unlink("cacheFramerate.cfg");
	}else{
		file_put_contents("cacheFramerate.cfg",$cacheFramerate);
	}
	countdown(5);
	echo "<hr><a class='button' href='/cache.php#cacheFramerate'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheResize",$_POST)){
	$cacheResize=$_POST['cacheResize'];
	# change the default cache quality
	echo "Changing cache mode to '".$cacheResize."'<br>\n";
	# write the config file
	if ($cacheResize == ''){
		unlink("cacheResize.cfg");
	}else{
		file_put_contents("cacheResize.cfg",$cacheResize);
	}
	countdown(5);
	echo "<hr><a class='button' href='/cache.php#cacheResize'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheDelay",$_POST)){
	$cacheDelay =$_POST['cacheDelay'];
	# change the default cache quality
	echo "Changing cache delay to '".$cacheDelay."'<br>\n";
	# write the config file
	if ($cacheDelay == ''){
		unlink("cacheDelay.cfg");
	}else{
		file_put_contents("cacheDelay.cfg",$cacheDelay);
	}
	countdown(5);
	echo "<hr><a class='button' href='/cache.php#cacheDelay'>BACK</a><hr>";
	clear();
}else if (array_key_exists("cacheNewEpisodes",$_POST)){
	$cacheNewEpisodes=$_POST['cacheNewEpisodes'];
	# change the default cache quality
	echo "Changing cache new episodes option to '".$cacheNewEpisodes."'<br>\n";
	# write the config file
	if ($cacheNewEpisodes == ''){
		unlink("/etc/2web/cacheNewEpisodes.cfg");
	}else{
		file_put_contents("/etc/2web/cacheNewEpisodes.cfg",$cacheNewEpisodes);
	}
	countdown(5);
	echo "<hr><a class='button' href='/cache.php#cacheNewEpisodes'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addLink",$_POST)){
	$link=$_POST['addLink'];
	echo "Running addLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding link ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/tv.php#addLink'>BACK</a><hr>";
	clear();
}else if (array_key_exists("ytdl_add_source",$_POST)){
	$link=$_POST['ytdl_add_source'];
	echo "Running ytdl_add_source on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/ytdl/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding ytdl source ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/ytdl2nfo.php#websiteSources'>BACK</a><hr>";
	clear();
}else if(array_key_exists("ytdl_remove_source",$_POST)){
	$link=$_POST['ytdl_remove_source'];
	echo "Running ytdl_remove_source on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/ytdl/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing ytdl source ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/ytdl2nfo.php#websiteSources'>BACK</a><hr>";
	clear();
}else if (array_key_exists("ytdl_add_username_source",$_POST)){
	$link=$_POST['ytdl_add_username_source'];
	echo "Running ytdl_add_username_source on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/ytdl/usernameSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	#
	if ( ! file_exists($configPath)){
		echo "Adding ytdl username source ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/ytdl2nfo.php#usernameSources'>BACK</a><hr>";
	clear();
}else if(array_key_exists("ytdl_remove_username_source",$_POST)){
	$link=$_POST['ytdl_remove_username_source'];
	echo "Running ytdl_remove_username_source on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/ytdl/usernameSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing ytdl username source ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/ytdl2nfo.php#usernameSources'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addLibary",$_POST)){
	$link=$_POST['addLibary'];
	echo "Running addLibary on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/nfo/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding link ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/nfo.php#libaryPaths'>BACK</a><hr>";
	clear();

}else if (array_key_exists("addWeatherLocation",$_POST)){
	$link=$_POST['addWeatherLocation'];
	echo "Running addWeatherLocation on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# run the weather command as a search command and check that the location has a result
	$weatherTest=shell_exec("weather '".$_POST['addWeatherLocation']."'");
	countdown(5);
	# check if the location has failed
	if (strpos($weatherTest, "Your search is ambiguous")){
		echo "<div>";
		echo "ERROR: Your location was not specific enough.";
		echo "</div>";
		echo "<pre>";
		echo $weatherTest;
		echo "</pre>";
	}else if(strpos($weatherTest, "Current conditions")){
		# read the link and create a custom config
		$configPath="/etc/2web/weather/location.d/".$sumOfLink.".cfg";
		echo "Checking for Config file ".$configPath."<br>\n";
		# write the libary path to a file at the configPath if the path does not already exist
		if ( ! file_exists($configPath)){
			echo "Adding ".$link." to weather stations...<br>\n";
			# write the config file
			file_put_contents($configPath,$link);
		}
	}else{
		echo "<div>";
		echo "ERROR: Your search has no results.";
		echo "</div>";
		echo "<pre>";
		echo $weatherTest;
		echo "</pre>";
	}
	echo "<hr><a class='button' href='/weather.php#addWeatherLocation'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeWeatherLocation",$_POST)){
	$link=$_POST['removeWeatherLocation'];
	echo "Running removeWeatherLocation on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/weather/location.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing weather location ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/weather.php#currentLinks'>BACK</a><hr>";
	clear();

}else if (array_key_exists("addComicDownloadLink",$_POST)){
	$link=$_POST['addComicDownloadLink'];
	echo "Running addComicDownloadLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/comic/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding ".$link." to comic downloader...<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/comicsDL.php#addComicDownloadLink'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeComicDownloadLink",$_POST)){
	$link=$_POST['removeComicDownloadLink'];
	echo "Running removeComicDownloadLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/comics/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing comicDownloadLink ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/comicsDL.php#currentLinks'>BACK</a><hr>";
	clear();
}else if (array_key_exists("addComicLibary",$_POST)){
	$link=$_POST['addComicLibary'];
	echo "Running addComicLibary on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/2web/comics/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding libary ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/comics.php#comiclibaryPaths'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeComicLibary",$_POST)){
	$link=$_POST['removeComicLibary'];
	echo "Running removeComicLibary on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/comics/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing libary ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/comics.php#comiclibaryPaths'>BACK</a><hr>";
	clear();
}else if (array_key_exists("moveToBottom",$_POST)){
	$link=$_POST['moveToBottom'];
	echo "Running moveToBottom on link ".$link."<br>\n";
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
	countdown(5);
	echo "<hr><a class='button' href='/tv.php#currentLinks'>BACK</a><hr>";
	clear();
}else if (array_key_exists("moveCustomToBottom",$_POST)){
	$link=$_POST['moveCustomToBottom'];
	echo "Running moveCustomToBottom on link ".$link."<br>\n";
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
	countdown(5);
	echo "<hr><a class='button' href='/tv.php#currentLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeLink",$_POST)){
	$link=$_POST['removeLink'];
	echo "Running removeLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/tv.php#currentLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeLibary",$_POST)){
	$link=$_POST['removeLibary'];
	echo "Running removeLibary on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/nfo/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/nfo.php#libaryPaths'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeRadioLink",$_POST)){
	$link=$_POST['removeRadioLink'];
	echo "Running removeRadioLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/radioSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/radio.php#radioLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("removeCustomLink",$_POST)){
	$link=$_POST['removeCustomLink'];
	echo "Running removeCustomLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/sources.d/".$sumOfLink.".m3u";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/radio.php#radioLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("blockLink",$_POST)){
	$link=$_POST['blockLink'];
	echo "Running blockLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( ! file_exists($configPath)){
		echo "Blocking link ".$link."<br>\n";
		# create the blocked link file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/radio.php#radioLinks'>BACK</a><hr>";
	clear();
}else if(array_key_exists("unblockLink",$_POST)){
	$link=$_POST['unblockLink'];
	echo "Running unblockLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Unblocking link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/iptv_blocked.php#ActiveBlockedGroups'>BACK</a><hr>";
	clear();
}else if(array_key_exists("blockGroup",$_POST)){
	$link=$_POST['blockGroup'];
	echo "Running blockGroup on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedGroups.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( ! file_exists($configPath)){
		echo "Blocking link ".$link."<br>\n";
		# create the blocked link file
		file_put_contents($configPath,$link);
	}
	countdown(5);
	echo "<hr><a class='button' href='/iptv_blocked.php#ActiveBlockedGroups'>BACK</a><hr>";
	clear();
}else if(array_key_exists("unblockGroup",$_POST)){
	$link=$_POST['unblockGroup'];
	echo "Running unblockGroup on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/2web/iptv/blockedGroups.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Unblocking link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
	countdown(5);
	echo "<hr><a class='button' href='/iptv_blocked.php#ActiveBlockedGroups'>BACK</a><hr>";
	clear();
}else if(array_key_exists("theme",$_POST)){
	$theme=$_POST["theme"];
	echo "Changing theme to ".$theme."<br>\n";
	file_put_contents("/etc/2web/theme.cfg",$theme);
	countdown(5);
	echo "<hr><a class='button' href='/system.php#webTheme'>BACK</a><hr>";
	clear();
}else{
	countdown(5);
	echo "<h1>[ERROR]:UNKNOWN COMMAND SUBMITTED TO API</h1>";
	echo "<ul>";
	echo "<li>";
	print_r($_POST);
	echo "</li>";
	echo "<li>This incident will be logged.</li>";
	echo "<li>If you are lost <a href='/index.php'>here</a> is a link back to the homepage.</li>";
	echo "</ul>";
}
?>

</div>

<?php
include('header.php');
?>

</body>
</html>
