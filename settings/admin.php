<html class='randomFanart'>
<head>
	<link rel='stylesheet' href='style.css'>
</head>
<body>

<?php
include('header.html');
?>

<div class='settingListCard'>

<div>

<?php
# enable error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);

# try to process the link to be added
if (array_key_exists("update",$_POST)){
	echo "Scheduling system update!<br>\n";
	touch("/var/cache/nfo2web/web/update/update.cfg");
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
			$configPath="/etc/iptv2web/radioSources.d/".$sumOfLink.".m3u";
			# create the custom link content
			$content='#EXTM3U\n'.'#EXTINF:-1 radio="true" tvg-logo="'.$linkIcon.'",'.$linkTitle.'\n'.$link;
			echo "Checking for Config file ".$configPath."<br>\n";
			# write the link to a file at the configPath if the path does not already exist
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
				file_put_contents($configPath,$content);
			}else{
				echo "[ERROR]: Custom Radio link creation failed '".$link."'<br>\n";
			}
		}else{
			echo "[ERROR]: Custom Radio Icon not found<br>";
		}
	}else{
		echo "[ERROR]: Custom Radio Title not found<br>";
	}
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
			$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".m3u";
			# create the custom link content
			$content='#EXTM3U\n'.'#EXTINF:-1 tvg-logo="'.$linkIcon.'",'.$linkTitle.'\n'.$link;
			echo "Checking for Config file ".$configPath."<br>\n";
			# write the link to a file at the configPath if the path does not already exist
			if ( ! file_exists($configPath)){
				echo "Adding link ".$link."<br>\n";
				# write the config file
				file_put_contents($configPath,$content);
			}else{
				echo "[ERROR]: Custom link creation failed '".$link."'<br>\n";
			}
		}else{
			echo "[ERROR]: Custom Icon not found<br>";
		}
	}else{
		echo "[ERROR]: Custom Title not found<br>";
	}

}else if (array_key_exists("addRadioLink",$_POST)){
	$link=$_POST['addRadioLink'];
	echo "Running addRadioLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/iptv2web/radioSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding link ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
}else if (array_key_exists("cacheQuality",$_POST)){
	$cacheQuality=$_POST['cacheQuality'];
	# change the default cache quality
	echo "Changing cache quality to '".$cacheQuality."'<br>\n";
	# write the config file
	file_put_contents("cacheQuality.cfg",$cacheQuality);
}else if (array_key_exists("addLink",$_POST)){
	$link=$_POST['addLink'];
	echo "Running addLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding link ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
}else if (array_key_exists("addLibary",$_POST)){
	$link=$_POST['addLibary'];
	echo "Running addLibary on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/nfo2web/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the libary path to a file at the configPath if the path does not already exist
	if ( ! file_exists($configPath)){
		echo "Adding link ".$link."<br>\n";
		# write the config file
		file_put_contents($configPath,$link);
	}
}else if (array_key_exists("moveToBottom",$_POST)){
	$link=$_POST['moveToBottom'];
	echo "Running moveToBottom on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if (file_exists($configPath)){
		echo "Moving to bottom of list ".$link."<br>\n";
		# write the config file
		touch($configPath);
	}
}else if (array_key_exists("moveCustomToBottom",$_POST)){
	$link=$_POST['moveCustomToBottom'];
	echo "Running moveCustomToBottom on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	# read the link and create a custom config
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".m3u";
	echo "Checking for Config file ".$configPath."<br>\n";
	# write the link to a file at the configPath if the path does not already exist
	if (file_exists($configPath)){
		echo "Moving to bottom of list ".$link."<br>\n";
		# write the config file
		touch($configPath);
	}
}else if(array_key_exists("removeLink",$_POST)){
	$link=$_POST['removeLink'];
	echo "Running removeLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
}else if(array_key_exists("removeLibary",$_POST)){
	$link=$_POST['removeLibary'];
	echo "Running removeLibary on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/nfo2web/libaries.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
}else if(array_key_exists("removeRadioLink",$_POST)){
	$link=$_POST['removeRadioLink'];
	echo "Running removeRadioLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/radioSources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
}else if(array_key_exists("removeCustomLink",$_POST)){
	$link=$_POST['removeCustomLink'];
	echo "Running removeCustomLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".m3u";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}

}else if(array_key_exists("blockLink",$_POST)){
	$link=$_POST['blockLink'];
	echo "Running blockLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( ! file_exists($configPath)){
		echo "Blocking link ".$link."<br>\n";
		# create the blocked link file
		file_put_contents($configPath,$link);
	}
}else if(array_key_exists("unblockLink",$_POST)){
	$link=$_POST['unblockLink'];
	echo "Running unblockLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Unblocking link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
}else if(array_key_exists("theme",$_POST)){
	$theme=$_POST["theme"];
	echo "Changing theme to ".$theme."<br>\n";
	file_put_contents("/etc/mms/theme.cfg",$theme);
}
?>

</div>

<br>
<br>
<br>

<a class='button' href='index.html'>Return to Media</a>
<a class='button' href='system.php'>System Settings</a>
<a class='button' href='tv.php'>TV Settings</a>
<a class='button' href='radio.php'>RADIO Settings</a>

</div>

<?php
include('header.html');
?>

</body>
</html>
