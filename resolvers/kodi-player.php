<!--
########################################################################
# 2web kodi player to launch playback on kodi clients
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
<?PHP
################################################################################
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
################################################################################
include("/usr/share/2web/2webLib.php");
################################################################################
if (array_key_exists("url",$_GET)){
	# play
	$videoLink = $_GET['url'];
	# remove parenthesis from video link if they exist
	debug("Cleaning link ".$videoLink."<br>");
	while(strpos($videoLink,'"')){
		debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
		$videoLink = preg_replace('"','',$videoLink);
	}
	while(strpos($videoLink,"'")){
		debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
		$videoLink = preg_replace("'","",$videoLink);
	}
	# build the command to play on all the players
	$command = "kodi2web_player open '".$videoLink."'";
	#$command = "echo \"kodi2web_player play '".$videoLink."'\"";
	# set the command to run in the scheduler
	#$command = $command." | /usr/bin/at -M -q a now";
	# debug info
	# fork the process
	shell_exec($command);
	#echo "$command"."<br>\n";
	# go back to the page that sent the command
	#redirect($_SERVER["HTTP_REFERER"]);
	#echo $_SERVER["HTTP_REFERER"]."<br>\n";
	redirect("/kodi-player.php");
}else if (array_key_exists("input",$_GET)){
	$inputAction = (strtolower($_GET['input']));
	if($inputAction == "select"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "up"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "down"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "left"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "right"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "back"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "home"){
		shell_exec("kodi2web_player --".$inputAction);
	}else if($inputAction == "context"){
		shell_exec("kodi2web_player --".$inputAction);
	}else{
		# unknown input action
		echo "UNKNOWN ACTION SENT TO API";
	}
	# redirect back to the remote after the button is sent
	redirect("/kodi-player.php");
}else if (array_key_exists("volumeup",$_GET)){
	# volume up
	shell_exec("kodi2web_player --volumeup");
	# redirect back to the remote
	redirect("/kodi-player.php");
}else if (array_key_exists("volumedown",$_GET)){
	# volume down
	shell_exec("kodi2web_player --volumedown");
	# redirect back to the remote
	redirect("/kodi-player.php");
}else if (array_key_exists("mute",$_GET)){
	# toggle mute
	shell_exec("kodi2web_player --mute");
	# redirect back to the remote
	redirect("/kodi-player.php");
}else if (array_key_exists("play",$_GET) or array_key_exists("pause",$_GET)){
	# play/pause the video
	shell_exec("kodi2web_player --play");
	# redirect back to the remote
	redirect("/kodi-player.php");
}else{
	// no url was given at all draw the remote
	echo "<html>";
	echo "<head>";
	echo "<link rel='stylesheet' href='/style.css'>";
	echo "</head>";
	echo "<body>";
	echo "<div class='settingListCard'>";
	echo "<a href='/'>";
	echo "<h2>Close KODI Remote Control</h2>";
	echo "</a>";
	echo "<table class='kodiPlayerButtonGrid'>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?input=back'>🔙<div>BACK</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonUp kodiPlayerButton ' href='kodi-player.php?input=up'>⬆️<div>UP</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?input=home'>🏠<div>HOME</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonLeft kodiPlayerButton ' href='kodi-player.php?input=left'>⬅️<div>LEFT</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonSelect kodiPlayerButton ' href='kodi-player.php?input=select'>🔘<div>SELECT</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonRight kodiPlayerButton ' href='kodi-player.php?input=right'>➡️<div>RIGHT</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?play'>⏯️<div>Play/Pause</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonDown kodiPlayerButton ' href='kodi-player.php?input=down'>⬇️<div>DOWN</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonContext kodiPlayerButton ' href='kodi-player.php?input=context'>🔧<div>Context</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?volumedown'>🔉<div>- Volume</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonDown kodiPlayerButton ' href='kodi-player.php?mute'>🔇<div>Mute</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonContext kodiPlayerButton ' href='kodi-player.php?volumeup'>🔊<div>+ Volume</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	#echo "NO URL WAS GIVEN";
	echo "</div>";
	echo "</body>";
	echo "</html>";
}
?>
