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
	redirect($_SERVER["HTTP_REFERER"]);
	#echo $_SERVER["HTTP_REFERER"]."<br>\n";
}else{
	// no url was given at all
	echo "<html>";
	echo "<head>";
	echo "<link rel='stylesheet' href='/style.css'>";
	echo "</head>";
	echo "<body>";
	echo "<div class='settingListCard'>";
	echo "<h2>Kodi Player Redirect</h2>";
	echo "NO URL WAS GIVEN";
	echo "</div>";
	echo "</body>";
	echo "</html>";
}
?>
