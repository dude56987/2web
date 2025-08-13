<?PHP
########################################################################
# 2web controller selection interface
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
# force debugging
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
startSession();
################################################################################
# generate any mime data to be sent in the string
if (array_key_exists("mime",$_GET)){
	$mimeData="?mime=".$_GET["mime"]."&";
}else{
	$mimeData="?";
}
# if the user has NOT clicked to reselect the remote
if (! array_key_exists("select",$_GET)){
	# load the user remote data
	if (array_key_exists("selectedRemote",$_GET)){
		# load the selected remote
		$selectedRemote=$_GET["selectedRemote"];
		# store the value in the session
		$_SESSION["selectedRemote"]=$selectedRemote;
		# set the remote title
		$_SESSION["remoteTitle"]=explode("@",$_SESSION["selectedRemote"])[1];
		if (array_key_exists("user",$_SESSION)){
			# create the user directory if it does not exist
			if(! file_exists("/etc/2web/user_data/".$_SESSION["user"]."/")){
				mkdir("/etc/2web/user_data/".$_SESSION["user"]."/");
			}
			addToLog("ADMIN","select-remote.php","user '".$_SESSION["user"]."' set remote to '$selectedRemote'");
			file_put_contents("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemote.cfg",$_SESSION["selectedRemote"]);
			file_put_contents("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemoteTitle.cfg",$_SESSION["remoteTitle"]);
		}else{
			addToLog("ADMIN","select-remote.php","user at '".$_SERVER["REMOTE_ADDR"]."' set remote to '$selectedRemote'");
		}
	}else{
		# if the user is logged in
		if (array_key_exists("user",$_SESSION)){
			# load the user selected remote
			if(file_exists("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemote.cfg")){
				addToLog("ADMIN","select-remote.php","Creating remote config");
				# load the user selected remote config
				$tempRemoteData=file_get_contents("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemote.cfg");
				$tempRemoteData=str_replace("\n","",$tempRemoteData);
				#
				$_SESSION["selectedRemote"]=$tempRemoteData;
				$selectedRemote=$tempRemoteData;
			}else{
				# The user has logged in but not selected remote
				# redirect to the selection menu
				if (array_key_exists("play",$_GET)){
					redirect("/remote.php".$mimeData."select&play=".$_GET["play"]);
				}else{
					redirect("/remote.php?select");
				}
			}
			if(file_exists("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemoteTitle.cfg")){
				addToLog("ADMIN","select-remote.php","Creating remote title config");
				# load the user selected remote config
				$tempRemoteData=file_get_contents("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemoteTitle.cfg");
				$tempRemoteData=str_replace("\n","",$tempRemoteData);
				#
				$_SESSION["remoteTitle"]=$tempRemoteData;
			}else{
				# redirect to the selection menu
				if (array_key_exists("play",$_GET)){
					redirect("/remote.php".$mimeData."select&play=".$_GET["play"]);
				}else{
					redirect("/remote.php?select");
				}
			}
		}else{
			addToLog("ERROR","select-remote.php","Must Be logged in to load remote configuration");
		}
	}
	if (array_key_exists("selectedRemote",$_SESSION)){
		# redirect to the correct location
		if( $_SESSION["selectedRemote"] == "CLIENT" ){
			# if the remote is the server client
			if (array_key_exists("play",$_GET)){
				# redirect to playback url if given
				redirect("/client/".$mimeData."play=".($_GET["play"]));
			}else{
				redirect("/client/?remote");
			}
		}else{
			# if the selected remote is kodi
			if (array_key_exists("play",$_GET)){
				# convert https to http requests for kodi
				if (substr($_GET["play"],0,7) == "https://"){
					$_GET["play"] = "http://".substr($_GET["play"],7);
				}
				# redirect to playback url if given
				redirect("/kodi-player.php?url=".urldecode($_GET["play"]));
			}else{
				redirect("/kodi-player.php");
			}
		}
	}
}
################################################################################
?>
<html class='randomFanart'>
<head>
<title>2web Client Player</title>
<link rel='stylesheet' href='/style.css'>
<script src='/2webLib.js'></script>
</head>
<body>
<?PHP
	include("/var/cache/2web/web/header.php");
?>
<div id='kodiPlayers' class='settingListCard'>
<?PHP
	# load all the kodi players
	echo "<h1>Choose Your Remote</h1>\n";
	echo "<p>Choose the client your remote will connect to automatically. This will be saved while you are logged in.</p>\n";
	echo "<h2>KODI Players </h2>\n";
	$sourceFiles = recursiveScan("/etc/2web/kodi/players.d/");
	sort($sourceFiles);
	echo "<div class='listCard'>\n";
	# write each config file as a editable entry
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".cfg") !== False){
					$playerLink=file_get_contents($sourceFile);
					$playerLink=str_replace("\n","",$playerLink);
					$playerHash=basename($sourceFile);
					$playerHash=str_replace(".cfg","",$sourceFile);
					# get the link name
					$playerLinkName=explode("@",$playerLink)[1];
					echo "		<a class='button' href='?selectedRemote=$playerLink'>🎛️ ".$playerLinkName." KODI Remote</a>\n";
				}
			}
		}
	}
	echo "</div>\n";
	# draw the client player button to use the client broadcast from this server
	if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
		if (requireGroup("clientRemote",false)){
			echo "<h2>Client Remote</h2>\n";
			echo "<p>\n";
			echo "	This will broadcast the remote commands to all clients connected to the";
			echo "	<a href='/client/'>client</a>";
			echo "	page on this server this server.\n";
			echo "</p>\n";
			echo "<div class='listCard'>\n";
			echo "	<a class='button' href='".$mimeData."selectedRemote=CLIENT'>🎛️ ".gethostname()." Client Remote</a>";
			echo "</div>\n";
		}
	}
?>
</div>
<?PHP
	include("/var/cache/2web/web/footer.php");
?>
</body>
</html>
