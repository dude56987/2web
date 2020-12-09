<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include("header.html");
################################################################################
echo "<div class='inputCard'>";
echo "<h2>Update</h2>\n";
echo "<form class='buttonForm' method='get'>\n";
echo "	<button class='button' type='submit' name='update' value='true'>UPDATE</button>\n";
echo "</form>\n";
echo "</div>";

################################################################################
# create the theme color picker
################################################################################
echo "<div class='inputCard'>";
echo "<form class='buttonForm' method='get'>\n";
echo "<ul>";
echo "<li>";
echo "	<input type='color' name='themeColor'>Background Color</input>\n";
echo "</li>";
echo "<li>";
echo "	<input type='color' name='textColor'>Text Color</input>\n";
echo "</li>";
echo "<li>";
echo "	<input type='color' name='borderColor'>Border Color</input>\n";
echo "</li>";
echo "<li>";
echo "	<input type='color' name='highlightColor'>Highlight Color</input>\n";
echo "</li>";
echo "</ul>";
echo "</form>\n";
echo "</div>";
################################################################################
# try to process the link to be added
if (array_key_exists("update",$_GET)){
	echo "Scheduling system update!<br>\n";
	touch("/var/www/iptv2web/update.cfg");
}else if (array_key_exists("addCustomRadioLink",$_GET)){
	# this will add a custom m3u file with a single entry
	$link=$_GET['addCustomRadioLink'];
	if (array_key_exists("addCustomRadioTitle",$_GET)){
		# add custom title
		$linkTitle=$_GET['addCustomRadioTitle'];
		if (array_key_exists("addCustomRadioIcon",$_GET)){
			# add the icon link
			$linkIcon=$_GET['addCustomRadioIcon'];
			################################################################################
			# all fields are filled out
			################################################################################
			# create sum of link
			$sumOfLink=md5($link);
			# read the link and create a custom config
			$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".m3u";
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
}else if (array_key_exists("addCustomLink",$_GET)){
	# this will add a custom m3u file with a single entry
	$link=$_GET['addCustomLink'];
	if (array_key_exists("addCustomTitle",$_GET)){
		# add custom title
		$linkTitle=$_GET['addCustomTitle'];
		if (array_key_exists("addCustomIcon",$_GET)){
			# add the icon link
			$linkIcon=$_GET['addCustomIcon'];
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

}else if (array_key_exists("addLink",$_GET)){
	$link=$_GET['addLink'];
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
}else if (array_key_exists("moveToBottom",$_GET)){
	$link=$_GET['moveToBottom'];
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
}else if (array_key_exists("moveCustomToBottom",$_GET)){
	$link=$_GET['moveCustomToBottom'];
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
}else if(array_key_exists("removeLink",$_GET)){
	$link=$_GET['removeLink'];
	echo "Running removeLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
}else if(array_key_exists("removeCustomLink",$_GET)){
	$link=$_GET['removeCustomLink'];
	echo "Running removeCustomLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/sources.d/".$sumOfLink.".m3u";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Removing link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}

}else if(array_key_exists("blockLink",$_GET)){
	$link=$_GET['blockLink'];
	echo "Running blockLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if ( ! file_exists($configPath)){
		echo "Blocking link ".$link."<br>\n";
		# create the blocked link file
		file_put_contents($configPath,$link);
	}
}else if(array_key_exists("unblockLink",$_GET)){
	$link=$_GET['unblockLink'];
	echo "Running unblockLink on link ".$link."<br>\n";
	$sumOfLink=md5($link);
	$configPath="/etc/iptv2web/blockedLinks.d/".$sumOfLink.".cfg";
	echo "Checking for Config file ".$configPath."<br>\n";
	if (file_exists($configPath)){
		echo "Unblocking link ".$link."<br>\n";
		# delete the custom config created for the link
		unlink($configPath);
	}
}
// no url was given at all
echo "<div class='settingListCard'>";
echo "<h2>Blocked links Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/iptv2web/blockedLinks.cfg");
echo "</pre>\n";
echo "</div>";


echo "<div class='settingListCard'>";
echo "<h2>Blocked links</h2>\n";
$sourceFiles = scandir("/etc/iptv2web/blockedLinks.d/");
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	$sourceFile = "/etc/iptv2web/blockedLinks.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				//echo "[DEBUG]: reading file ".$sourceFile."<br>";
				$link=file_get_contents($sourceFile);
				echo "<form class='removeLink' method='get'>\n";
				echo "<input class='button' type='text' name='unblockLink' value='".$link."' readonly>\n";
				echo "<input class='button' type='submit' value='UNBLOCK'>\n";
				echo "</form>\n";
				//echo "<pre>\n";
				//echo file_get_contents($sourceFile);
				//echo "</pre>\n";
			}
		}
	}
}
echo "</div>";

echo "<div class='inputCard'>\n";
echo "<form method='get'>\n";
echo "<h2>Block Link</h2>\n";
echo "<input width='60%' class='inputText' type='text' name='blockLink' placeholder='Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";

echo "<div class='inputCard'>\n";
echo "<form method='get'>\n";
echo "<h2>Unblock Link</h2>\n";
echo "<input width='60%' class='inputText' type='text' name='unblockLink' placeholder='Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";


echo "<div class='settingListCard'>\n";
echo "<h2>Current Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/iptv2web/sources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Current links</h2>\n";
$sourceFiles = scandir("/etc/iptv2web/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/iptv2web/sources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//$sourceFileName = array_reverse(explode("/",$sourceFile))[0];
	//$sourceFile = "/etc/iptv2web/sources.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);

				# try to find a icon for the link
				$iconLink=md5("http://".gethostname().".local:444/iptv-resolver.php?url=\"".$link."\"");

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				if (file_exists($iconLink)){
					# if the link is a redirected generated link get a diffrent icon
					echo "	<img class='settingsThumb' src='".$iconLink.".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form class='buttonForm' method='get'>\n";
				echo "	<button class='button' type='submit' name='removeLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form class='buttonForm' method='get'>\n";
				echo "		<button class='button' type='submit' name='blockLink' value='".$link."'>BLOCK</button>\n";
				echo "	</form>\n";
				echo "	<form class='buttonForm' method='get'>\n";
				echo "		<button class='button' type='submit' name='moveToBottom' value='".$link."'>Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
				//echo "</div>";
			}
		}
	}
}
//echo "</table>";
echo "</div>";

echo "<div class='inputCard'>";
echo "<form method='get'>\n";
echo "<h2>Add Link</h2>\n";
echo "<input width='60%' type='text' name='addLink' placeholder='Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";

echo "<div class='inputCard'>";
echo "<form method='get'>\n";
echo "<h2>Add Custom Link</h2>\n";
echo "<input width='60%' type='text' name='addCustomLink' placeholder='Link'>\n";
echo "<input width='60%' type='text' name='addCustomTitle' placeholder='Title'>\n";
echo "<input width='60%' type='text' name='addCustomIcon' placeholder='Icon Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";

echo "<div class='inputCard'>";
echo "<form method='get'>\n";
echo "<h2>Add Radio Station</h2>\n";
echo "<input width='60%' type='text' name='addCustomRadioLink' placeholder='Link'>\n";
echo "<input width='60%' type='text' name='addCustomRadioTitle' placeholder='Title'>\n";
echo "<input width='60%' type='text' name='addCustomRadioIcon' placeholder='Icon Link'>\n";
echo "<input class='button' type='submit'>\n";
echo "</form>\n";
echo "</div>";

echo "<div class='settingListCard'>";
echo "<h2>Custom Links</h2>\n";
# read the custom links
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/iptv2web/sources.d/*.m3u"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			//echo "[DEBUG]: It is a file...<br>\n";
			if (strpos($sourceFile,".m3u")){
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				# get the link, it should be the second line of the file
				$fileData = file_get_contents($sourceFile);
				# DEBUG DRAW THE ARRAY
				//print_r($link);
				$fileData = explode('\n',$fileData);
				//print_r($link);
				$title = explode(',',$fileData[1])[1];
				$link = $fileData[2];
				echo "<div class='settingsEntry'>\n";
				echo "	<h2>".$title."</h2>";
				echo "	".$link."";
				echo "<div class='buttonContainer'>\n";
				echo "	<form class='buttonForm' method='get'>\n";
				echo "	<button class='button' type='submit' name='removeCustomLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form class='buttonForm' method='get'>\n";
				echo "		<button class='button' type='submit' name='moveCustomToBottom' value='".$link."'>Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
echo "</div>";
//echo "<form method='get'>\n";
//echo "<h2>Remove Link</h2>\n";
//echo "<input width='60%' type='text' name='removeLink' placeholder='Link'>\n";
//echo "<input type='submit'>\n";
//echo "</form>\n";

?>
</body>
</html>
