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
function remoteRedirect(){
	# redirect back to the remote after the button is sent
	#
	# - Store the referer url that opened the remote control
	#
	# RETURN REDIRECT
	if (array_key_exists("ref",$_GET)){
		redirect("/kodi-player.php?ref=".$_GET["ref"]);
	}else{
		redirect("/kodi-player.php");
	}
}
################################################################################
# Parse inputs
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
	redirect("/kodi-player.php?ref=".$_SERVER["HTTP_REFERER"]);
}else if (array_key_exists("playlist",$_GET)){
	# play
	$videoLink = $_GET['playlist'];
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
	$command = "kodi2web_player openplaylist '".$videoLink."'";
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
	redirect("/kodi-player.php?ref=".$_SERVER["HTTP_REFERER"]);
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
	remoteRedirect();
}else if (array_key_exists("volumeup",$_GET)){
	# volume up
	shell_exec("kodi2web_player --volumeup");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("volumedown",$_GET)){
	# volume down
	shell_exec("kodi2web_player --volumedown");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("mute",$_GET)){
	# toggle mute
	shell_exec("kodi2web_player --mute");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("play",$_GET) or array_key_exists("pause",$_GET)){
	# play/pause the video
	shell_exec("kodi2web_player --play");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("stop",$_GET)){
	# play/pause the video
	shell_exec("kodi2web_player --stop");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("skipforward",$_GET)){
	# play/pause the video
	shell_exec("kodi2web_player --skip-forward");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("skipbackward",$_GET)){
	# play/pause the video
	shell_exec("kodi2web_player --skip-backward");
	# redirect back to the remote
	remoteRedirect();
}else{
	# build the reference data to place in the buttons
	if (array_key_exists("ref",$_GET)){
		# store ref for links
		$refData="&ref=".$_GET["ref"];
	}else{
		if (array_key_exists("HTTP_REFERER",$_SERVER)){
			# store new ref for links
			$refData="&ref=".$_SERVER["HTTP_REFERER"];
			# store the referer in the get data for back button
			$_GET["ref"]=$_SERVER["HTTP_REFERER"];
		}else{
			#
			$refData="&ref=/";
			#
			$_GET["ref"]="/";
		}
	}
	// no url was given at all draw the remote
	echo "<html class='randomFanart'>";
	echo "<head>";
	echo "<link rel='stylesheet' href='/style.css'>";
	echo "</head>";
	echo "<body class='settingListCard'>";
	echo "<table class='kodiPlayerButtonGrid'>";
	echo "	<tr>";
	echo "		<td>";
	# link back to the launch location of the remote
	echo "			<a class='kodiPlayerButtonHome kodiPlayerButton ' href='".$_GET["ref"]."'>❌<div>CLOSE</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?skipbackward'>⏪<div>BACKWARD</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?skipforward'>⏩<div>FORWARD</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?input=back$refData'>🔙<div>BACK</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonUp kodiPlayerButton ' href='kodi-player.php?input=up$refData'>⬆️<div>UP</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonContext kodiPlayerButton ' href='kodi-player.php?input=context$refData'>🔧<div>Context</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonLeft kodiPlayerButton ' href='kodi-player.php?input=left$refData'>⬅️<div>LEFT</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonSelect kodiPlayerButton ' href='kodi-player.php?input=select$refData'>🔘<div>SELECT</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonRight kodiPlayerButton ' href='kodi-player.php?input=right$refData'>➡️<div>RIGHT</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?stop$refData'>⏹️<div>STOP</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonDown kodiPlayerButton ' href='kodi-player.php?input=down$refData'>⬇️<div>DOWN</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?play$refData'>⏯️<div>Play/Pause</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?volumedown$refData'>🔉<div>- Volume</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonDown kodiPlayerButton ' href='kodi-player.php?mute$refData'>🔇<div>Mute</div></a>";
	echo "		</td>";
	echo "		<td>";
	echo "			<a class='kodiPlayerButtonContext kodiPlayerButton ' href='kodi-player.php?volumeup$refData'>🔊<div>+ Volume</div></a>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	#echo "NO URL WAS GIVEN";
	#echo "</div>";
	echo "</body>";
	echo "</html>";
}
?>
