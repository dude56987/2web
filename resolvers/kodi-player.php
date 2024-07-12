<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("kodi2web");
?>
<!--
########################################################################
# 2web kodi player to launch playback on kodi clients
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
<?PHP
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
function cleanQuotes($videoLink){
	# clean quotes from the video link
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);
	return $videoLink;
}
################################################################################
function forkCommand($inputCommand){
	addToQueue("multi", $inputCommand);
}
################################################################################
function drawRemoteHeader(){
	echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' onclick='window.close();' href='/'>❌<div>CLOSE</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton' href='/kodi-player.php?share' title='Share Link'>⛓️<div>Share</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton' href='/kodi-player.php' title='Remote'>🎛️<div>Remote</div></a>\n";
	echo "					</td>\n";
	echo "				</tr>\n";
	echo "			</table>\n";
}
################################################################################
# Parse inputs
if (array_key_exists("url",$_GET)){
	# check if the link was passed with the web remote
	$videoLink = $_GET['url'];
	# clean up the quotes
	$videoLink = cleanQuotes($videoLink);
	#	generate the sum from the cleaned link
	$videoLinkSum = md5($videoLink);
	# build the command to play on all the players
	$command = "kodi2web_player open '".$videoLink."'";
	# fork the process
	forkCommand($command);
	# go back to the remote control page
	redirect("/kodi-player.php?ref=".$_SERVER["HTTP_REFERER"]);
}else if (array_key_exists("shareStreamURL",$_GET)){
	# check if the link was passed with the web remote
	$OGvideoLink = $_GET['shareStreamURL'];
	$videoLink = $OGvideoLink;
	# clean up the quotes
	$videoLink = cleanQuotes($videoLink);
	# add the local resolver to the video link
	$videoLink = "http://".gethostname().'.local/live/iptv-resolver.php?url="'.$videoLink.'"';
	# generate the hash
	$videoLinkSum=md5($videoLink);
	if (! file_exists("/var/cache/2web/web/kodi-player/".$videoLinkSum.".strm")){
		# get the title of the video
		$videoTitle=shell_exec("/var/cache/2web/generated/yt-dlp/yt-dlp --get-title '".$OGvideoLink."' ");
		file_put_contents("/var/cache/2web/web/kodi-player/".$videoLinkSum.".title", $videoTitle);
		# write the temp file
		file_put_contents("/var/cache/2web/web/kodi-player/".$videoLinkSum.".strm", $videoLink);
	}
	# build the link for the generated .strm file
	$videoLink = "http://".gethostname().".local/kodi-player/".$videoLinkSum.".strm";
	# build the command to play on all the players
	$command = "kodi2web_player open '".$videoLink."'";
	# fork the process
	forkCommand($command);
	# go back to the remote control page
	redirect("/kodi-player.php");
}else if (array_key_exists("shareURL",$_GET)){
	# check if the link was passed with the web remote
	$OGvideoLink = $_GET['shareURL'];
	$videoLink = $OGvideoLink;
	# clean up the quotes
	$videoLink = cleanQuotes($videoLink);
	# add the local resolver to the video link
	$videoLink = "http://".gethostname().'.local/ytdl-resolver.php?url="'.$videoLink.'"';
	# generate the hash
	$videoLinkSum=md5($videoLink);
	if (! file_exists("/var/cache/2web/web/kodi-player/".$videoLinkSum.".strm")){
		# get the title of the video
		$videoTitle=shell_exec("/var/cache/2web/generated/yt-dlp/yt-dlp --get-title '".$OGvideoLink."' ");
		file_put_contents("/var/cache/2web/web/kodi-player/".$videoLinkSum.".title", $videoTitle );
		# write the temp file
		file_put_contents("/var/cache/2web/web/kodi-player/".$videoLinkSum.".strm", $videoLink);
	}
	# build the link for the generated .strm file
	$videoLink = "http://".gethostname().".local/kodi-player/".$videoLinkSum.".strm";
	# build the command to play on all the players
	$command = "kodi2web_player open '".$videoLink."'";
	# fork the process
	forkCommand($command);
	# go back to the remote control page
	redirect("/kodi-player.php");
}else if (array_key_exists("share",$_GET)){
	echo "<html class='randomFanart'>";
	echo "<head>";
	echo "<link rel='stylesheet' href='/style.css'>";
	echo "</head>";
	echo "<body class='remoteCard'>";
	echo "<table class='kodiPlayerButtonGrid'>";
	echo "	<tr>\n";
	echo "		<td>\n";
	# link back to the launch location of the remote
	drawRemoteHeader();
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<th>\n";
	echo "			Load Previous Video\n";
	echo "		</th>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	echo "			<table class='kodiControlEmbededTable'>\n";
	echo "				<form class=''>\n";
	echo "					<td>\n";
	echo "						<select name='url'>\n";
	$linkPrefix="http://".gethostname().".local/kodi-player/";
	$linkRemovePrefix="http://".gethostname().".local/ytdl-resolver.php?url=";
	$previousLinkList=array_diff(scanDir("/var/cache/2web/web/kodi-player/"),array(".",".."));
	$sortedLinkList=array();
	# sort the link list by modification date
	foreach($previousLinkList as $directoryPath){
		# only include strm files
		if (stripos($directoryPath,".strm") !== false){
			$sortedLinkList[filemtime("kodi-player/".$directoryPath)]=$directoryPath;
		}
	}
	#$sortedLinkList=ksort($sortedLinkList);
	ksort($sortedLinkList);
	$sortedLinkList=array_reverse($sortedLinkList);
	# draw the previous links list
	foreach($sortedLinkList as $directoryPath){
		$tempTitleText=str_replace($linkRemovePrefix,"",file_get_contents("kodi-player/".$directoryPath));
		# use the sum generated for the filename
		$titleDataPath="/var/cache/2web/web/kodi-player/".str_replace(".strm",".title",$directoryPath);
		$linkDataPath="/var/cache/2web/web/kodi-player/".$directoryPath;
		# load the link itself from inside the .strm file
		$tempLinkData=file_get_contents($linkDataPath);
		if (file_exists($titleDataPath)){
			# load the video title
			$tempTitleText=file_get_contents($titleDataPath);
			echo "							<option value='".$tempLinkData."'>".$tempTitleText."</option>\n";
		}else{
			echo "							<option value='".$tempLinkData."'>".$tempTitleText."</option>\n";
		}
	}
	echo "						</select>\n";
	echo "					</td>\n";
	echo "					<td class='kodiControlEmbededTableButton'>\n";
	echo "						<input class='button' type='submit' value='Load Video'>";
	echo "					</td>\n";
	echo "				</form>\n";
	echo "			</table>";
	echo "		</td>\n";
	echo "	</tr>\n";
	# form to use resolver for a url
	echo "	<tr>\n";
	echo "		<form method='get'>\n";
	echo "			<th>\n";
	echo "				Send To KODI via Resolver\n";
	echo "			</th>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td>\n";
	echo "				<table class='kodiControlEmbededTable'>\n";
	echo "					<tr>\n";
	# build the share url interface for posting urls into the resolver to be passed to kodi
	echo "						<td>\n";
	echo "							<input type='text' name='shareURL' placeholder='http://example.com/play.php?v=3d4D3ldK'>\n";
	echo "						</td>\n";
	echo "						<td class='kodiControlEmbededTableButton'>\n";
	echo "							<input class='button' type='submit' value='Share URL'>\n";
	echo "						</td>\n";
	echo "					</tr>\n";
	echo "				</table>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	# form to use resolver for a stream url
	echo "	<form method='get'>\n";
	echo "		<tr>\n";
	echo "			<th>\n";
	echo "				Send Stream to KODI via Resolver\n";
	echo "			</th>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td>\n";
	echo "				<table class='kodiControlEmbededTable'>\n";
	echo "					<tr>\n";
	echo "						<td>\n";
	# build the share url interface for posting urls into the resolver to be passed to kodi
	echo "							<input type='text' name='shareStreamURL' placeholder='http://example.com/user/3d4D3ldK/'>\n";
	echo "						</td>\n";
	echo "						<td class='kodiControlEmbededTableButton'>\n";
	echo "							<input class='button' type='submit' value='Share Stream URL'>\n";
	echo "						</td>\n";
	echo "					</tr>\n";
	echo "				</table>";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</form>\n";
	# form to use a direct link to content to send to kodi
	echo "	<form method='get'>\n";
	echo "		<tr>\n";
	echo "			<th>\n";
	echo "				Send To KODI Direct\n";
	echo "			</th>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td>\n";
	echo "				<table class='kodiControlEmbededTable'>\n";
	echo "					<tr>\n";
	echo "						<td>\n";
	# build the share url interface for posting urls into the resolver to be passed to kodi
	echo "							<input type='text' name='url' placeholder='http://example.com/media.mkv'>\n";
	echo "						</td>\n";
	echo "						<td class='kodiControlEmbededTableButton'>\n";
	echo "							<input class='button' type='submit' value='Share Direct URL'>\n";
	echo "						</td>\n";
	echo "					</tr>\n";
	echo "				</table>";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</form>\n";
	echo "</table>\n";
	echo "</body>\n";
	echo "</html>\n";
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
	forkCommand($command);
	#echo "$command"."<br>\n";
	# go back to the page that sent the command
	#redirect($_SERVER["HTTP_REFERER"]);
	#echo $_SERVER["HTTP_REFERER"]."<br>\n";
	redirect("/kodi-player.php?ref=".$_SERVER["HTTP_REFERER"]);
}else if (array_key_exists("input",$_GET)){
	$inputAction = (strtolower($_GET['input']));
	if($inputAction == "select"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "up"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "down"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "left"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "right"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "back"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "home"){
		forkCommand("kodi2web_player --".$inputAction);
	}else if($inputAction == "context"){
		forkCommand("kodi2web_player --".$inputAction);
	}else{
		# unknown input action
		echo "UNKNOWN ACTION SENT TO API";
	}
	remoteRedirect();
}else if (array_key_exists("volumeup",$_GET)){
	# volume up
	forkCommand("kodi2web_player --volumeup");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("volumedown",$_GET)){
	# volume down
	forkCommand("kodi2web_player --volumedown");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("mute",$_GET)){
	# toggle mute
	forkCommand("kodi2web_player --mute");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("play",$_GET) or array_key_exists("pause",$_GET)){
	# play/pause the video
	forkCommand("kodi2web_player --play");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("stop",$_GET)){
	# play/pause the video
	forkCommand("kodi2web_player --stop");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("skipforward",$_GET)){
	# play/pause the video
	forkCommand("kodi2web_player --skip-forward");
	# redirect back to the remote
	remoteRedirect();
}else if (array_key_exists("skipbackward",$_GET)){
	# play/pause the video
	forkCommand("kodi2web_player --skip-backward");
	# redirect back to the remote
	remoteRedirect();
}else{
	# build the reference data to place in the buttons
	if (array_key_exists("ref",$_GET)){
		# store ref for links
		$refData="&ref=".$_GET["ref"];
	}else{
		#
		$refData="&ref=/";
		#
		$_GET["ref"]="/";
	}
	// no url was given at all draw the remote
	echo "<html class='randomFanart'>\n";
	echo "<head>\n";
	echo "<link rel='stylesheet' href='/style.css'>\n";
	echo "</head>\n";
	echo "<body class='remoteCard'>\n";
	echo "<table class='kodiPlayerButtonGrid kodiControlEmbededTableButtonGrid'>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	drawRemoteHeader();
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?input=home$refData'>🏠<div>HOME</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?stop$refData'>⏹️<div>STOP</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?play$refData'>⏯️<div>Play/Pause</div></a>\n";
	echo "					</td>\n";
	echo "				</tr>\n";
	echo "			</table>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?input=back$refData'>🔙<div>BACK</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonUp kodiPlayerButton ' href='kodi-player.php?input=up$refData'>⬆️<div>UP</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonContext kodiPlayerButton ' href='kodi-player.php?input=context$refData'>🔧<div>Context</div></a>\n";
	echo "					</td>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonLeft kodiPlayerButton ' href='kodi-player.php?input=left$refData'>⬅️<div>LEFT</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonSelect kodiPlayerButton ' href='kodi-player.php?input=select$refData'>🔘<div>SELECT</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonRight kodiPlayerButton ' href='kodi-player.php?input=right$refData'>➡️<div>RIGHT</div></a>\n";
	echo "					</td>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?skipbackward'>⏪<div>BACKWARD</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonDown kodiPlayerButton ' href='kodi-player.php?input=down$refData'>⬇️<div>DOWN</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='kodi-player.php?skipforward'>⏩<div>FORWARD</div></a>\n";
	echo "					</td>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td>\n";
	echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonBack kodiPlayerButton ' href='kodi-player.php?volumedown$refData'>🔉<div>- Volume</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonDown kodiPlayerButton ' href='kodi-player.php?mute$refData'>🔇<div>Mute</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonContext kodiPlayerButton ' href='kodi-player.php?volumeup$refData'>🔊<div>+ Volume</div></a>\n";
	echo "					</td>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	#echo "NO URL WAS GIVEN";
	#echo "</div>";
	echo "</body>\n";
	echo "</html>\n";
}
?>
