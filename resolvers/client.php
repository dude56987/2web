<?PHP
########################################################################
# 2web client player
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
# setup api option to access the event server
# - this changes the header so it must be before any other code
include("/usr/share/2web/2webLib.php");
################################################################################
# functions
################################################################################
function loadCache(){
	# load the shared memory cache
	$cache = new Memcached();
	$cache->addServer("localhost", 11211);
	# return the created connection
	return $cache;
}
################################################################################
function loadCacheData($varName, $cacheObject){
	# load data from the cache by variable name
	$cacheData = $cacheObject->get($varName);

	return $cacheData;
}
################################################################################
function setCacheData($varName, $newValue, $cacheObject){
	# set cache data in the shared memory cache
	$cacheObject->set($varName,$newValue);
	return true;
}
################################################################################
function loadButtons(){
	# load the button file paths
	$filePaths = Array(
		"buttonpressed",
		"playpause",
		"skipforward",
		"skipbackward",
		"stop",
		"volumeup",
		"volumedown",
		"mute",
		"configure",
		"subs",
		"switchsub",
		"switchaudio",
		"switchoutput",
		"nexttrack",
		"previoustrack",
		"blank",
		"nightmode",
		"daymode",
		"duskmode"
	);
	return $filePaths;
}
################################################################################
function buildButtonData(){
	$cache=loadCache();
	# generate the default configs if they are not there
	foreach(loadButtons() as $filePath){
		if(! file_exists($filePath.".json")){
			setCacheData($filePath.".json", "", $cache);
		}
	}
}
################################################################################
function nukeButtonData(){
	# generate the default configs if they are not there
	foreach(loadButtons() as $filePath){
		if(file_exists($filePath.".json")){
			unlink($filePath.".json");
		}
	}
}
################################################################################
function drawRemoteHeader(){
	echo "			<table class='kodiControlEmbededTableButtonGridHeader'>\n";
	echo "				<tr>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' onclick='window.close();' href='/'>❌<div>CLOSE</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?setSleep'>😴<div>Sleep Timer</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton' href='?share' title='Share Link'>⛓️<div>Share</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?configure'>🎚️<div>Configure</div></a>\n";
	echo "					</td>\n";
	echo "					<td>\n";
	echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='/client/?remote'>🎛️<div>Remote</div></a>\n";
	echo "					</td>\n";
	echo "				</tr>\n";
	echo "			</table>\n";
}
################################################################################
if (array_key_exists("events",$_GET)){
	# set the header data for the event stream
	date_default_timezone_set("America/New_York");
	header("Cache-Control: no-store");
	header("Content-Type: text/event-stream");
	# build the array for the button names for the controler
	$buttonNames = loadButtons();

	#
	$cache=loadCache();

	# generate the default configs if they are not there
	foreach($buttonNames as $filePath){
		if(! file_exists($filePath.".json")){
			setCacheData($filePath.".json", "", $cache);
		}
	}
	#while (true) {
	#	#
	#	echo "event: ping\n";
	#	echo 'data: {"time": "'.time().'"}';
	#	echo "\n\n";

	#	# break the loop if the connection is broken
	#	if (connection_aborted()){
	#		break;
	#	}
	#	sleep(1);
	#}

	#
	$lastStatus = [];
	$heartBeatCounter=0;

	# set the current states from the stored states
	if(! file_exists("status.json")){
		$lastStatus["media"] = "";
		setCacheData("status.json","", $cache);
	}else{
		$lastStatus["media"] = loadCacheData("status.json", $cache);
	}
	if(! file_exists("buttonpressed.json")){
		$lastStatus["buttonPressed"] = "";
		setCacheData("buttonpressed.json","", $cache);
	}else{
		$lastStatus["buttonPressed"] = loadCacheData("buttonpressed.json", $cache);
	}
	# build the sleep file if it does not yet exist
	if(! file_exists("sleepcheck.json")){
		setCacheData("sleepcheck.json", "", $cache);
		# set the status of sleep
		$lastStatus["sleep"] = "";
	}else{
		# get the current sleep status
		$lastStatus["sleep"] = loadCacheData("sleepcheck.json", $cache);
	}
	# setup the last button status on first load of the button events
	foreach($buttonNames as $buttonName){
		$lastStatus[$buttonName]=loadCacheData($buttonName.".json", $cache);
		#addToLog("DEBUG","button file status '".$buttonName."'",$lastStatus[$buttonName]);
	}
	function clear(){
		# send all information to the connection
		ob_flush();
		flush();
	}
	# this is the event stream and will loop forever
	while (true) {
		# send the heartbeat event to keep the stream open
		if ($heartBeatCounter >= 10){
			echo "#BaBump";
			echo "\n\n";
			clear();
			$heartBeatCounter=0;
		}else{
			$heartBeatCounter+=1;
		}
		# check the sleep timer status
		$currentSleepStatus = loadCacheData("sleepcheck.json", $cache);
		# compare the current sleep status with the last found sleep status
		if ($currentSleepStatus != $lastStatus["sleep"]){
			$sleepTime=loadCacheData("sleep.json", $cache);
			# if the sleep timer is greater than 0 send a sleep timer event
			echo "data: sleep=".$sleepTime;
			echo "\n\n";
			# send the generated event
			clear();
			# update the sleep timer status so the event checking will be marked as done
			$lastStatus["sleep"] = $currentSleepStatus;
		}

		# check the status
		$currentStatus = loadCacheData("status.json", $cache);
		if ($currentStatus != $lastStatus["media"]){
			# get the media path data
			$mediaPath = loadCacheData("media.json", $cache);
			# check the media path for absolute paths
			if (! (substr($mediaPath,0,4) == "http")){
				# make the path absolute if it is relative
				$mediaPath="http://".$_SERVER["HTTP_HOST"].$mediaPath;
			}
			# send the event
			# - double new lines seprate events
			echo 'data: play='.$mediaPath;
			echo "\n\n";
			# send the generated event
			clear();
			# update the last status
			$lastStatus["media"] = $currentStatus;
		}
		# check if a button was pressed
		$currentButtonStatus = loadCacheData("buttonpressed.json", $cache);
		if ($currentButtonStatus != $lastStatus["buttonPressed"]){
			# check each button status
			foreach($buttonNames as $buttonName){
				$activeButtonStatus=loadCacheData($buttonName.".json", $cache);
				if ($activeButtonStatus != $lastStatus[$buttonName]){
					# build the event
					echo 'data: '.$buttonName;
					echo "\n\n";
					# send the generated event
					clear();
					# update the button status
					$lastStatus[$buttonName] = $activeButtonStatus;
				}
			}
			# update the last status
			$lastStatus["buttonPressed"] = $currentButtonStatus;
		}
		# break the loop if the connection is broken
		if (connection_aborted()){
			break;
		}
		sleep(1);
	}
}
?>
<!--
########################################################################
# 2web client player
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
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
################################################################################
# require the web player group
requireGroup("client");
################################################################################
# check for get data used to set the playback link
if (array_key_exists("play",$_GET)){
	if (requireGroup("clientRemote",false)){
		$cache=loadCache();
		# store the url as the playback url
		setCacheData("media.json",urldecode($_GET["play"]), $cache);
		# store the time of the playback start
		$playbackTime=filemtime("media.cfg");
		setCacheData("time.json",$playbackTime, $cache);
		# generate the sum
		$urlSum=md5($_GET["play"].time());
		# store the hash sum
		setCacheData("status.json",$urlSum, $cache);
		# redirect to the client remote page after starting playback
		redirect("/client/?remote");
	}else{
		addToLog("ERROR","Play on Client Failed","The user '".$_SESSION["user"]."' with the user agent string ".$_SERVER["HTTP_USER_AGENT"]." has attempted to play a video on the client without permissions.");
		# a attempt to play something without permissions was made
		echo "<h1 class='errorBanner'>You have attempted to play a video on the client without permissions. Please login to a user with correct permissions to play videos on the client page.</h1>";
	}
}else if (array_key_exists("resolveStream",$_GET)){
	if (requireGroup("clientRemote",false)){
		$cache=loadCache();
		# store the url as the playback url
		setCacheData("media.json",urldecode("http://".$_SERVER["HTTP_HOST"]."/iptv-resolver.php?url='".$_GET["resolveStream"]."'"), $cache);
		# store the time of the playback start
		$playbackTime=filemtime("media.cfg");
		setCacheData("time.json",$playbackTime, $cache);
		# generate the sum
		$urlSum=md5($_GET["resolveStream"].time());
		# store the hash sum
		setCacheData("status.json",$urlSum, $cache);
		# redirect to the client remote page after starting playback
		redirect("/client/?remote");
	}else{
		addToLog("ERROR","Play on Client Failed","The user '".$_SESSION["user"]."' with the user agent string ".$_SERVER["HTTP_USER_AGENT"]." has attempted to play a video stream on the client without permissions.");
		# a attempt to play something without permissions was made
		echo "<h1 class='errorBanner'>You have attempted to play a video on the client without permissions. Please login to a user with correct permissions to play videos on the client page.</h1>";
	}
}else if (array_key_exists("resolveUrl",$_GET)){
	if (requireGroup("clientRemote",false)){
		$cache=loadCache();
		# store the url as the playback url
		setCacheData("media.json",urldecode("http://".$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url='".$_GET["resolveUrl"]."'"), $cache);
		# store the time of the playback start
		$playbackTime=filemtime("media.cfg");
		setCacheData("time.json",$playbackTime, $cache);
		# generate the sum
		$urlSum=md5($_GET["resolveUrl"].time());
		# store the hash sum
		setCacheData("status.json",$urlSum, $cache);
		# redirect to the client remote page after starting playback
		redirect("/client/?remote");
	}else{
		addToLog("ERROR","Play on Client Failed","The user '".$_SESSION["user"]."' with the user agent string ".$_SERVER["HTTP_USER_AGENT"]." has attempted to play a video stream on the client without permissions.");
		# a attempt to play something without permissions was made
		echo "<h1 class='errorBanner'>You have attempted to play a video on the client without permissions. Please login to a user with correct permissions to play videos on the client page.</h1>";
	}
}else if (array_key_exists("remote",$_GET)){
	if (requireGroup("clientRemote")){
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
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=skipbackward'>⏪<div>BACKWARD</div></a>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonBack kodiPlayerButton ' href='?remoteKey=playpause'>⏯️<div>Play/Pause</div></a>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=skipforward'>⏩<div>FORWARD</div></a>\n";
		echo "				</td>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=nexttrack'>⏮️<div>Previous Track</div></a>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=stop'>⏹️<div>STOP</div></a>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=previoustrack'>⏭️<div>Next Track</div></a>\n";
		echo "				</td>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonBack kodiPlayerButton ' href='?remoteKey=volumedown'>🔉<div>- Volume</div></a>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonDown kodiPlayerButton ' href='?remoteKey=mute'>🔇<div>Mute</div></a>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<a class='kodiPlayerButtonContext kodiPlayerButton ' href='?remoteKey=volumeup'>🔊<div>+ Volume</div></a>\n";
		echo "				</td>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</body>\n";
		echo "</html>\n";
		exit();
	}
}else if (array_key_exists("configure",$_GET)){
	if (requireGroup("clientRemote")){
		// no url was given at all draw the remote
		echo "<html class='randomFanart'>\n";
		echo "<head>\n";
		echo "<link rel='stylesheet' href='/style.css'>\n";
		echo "</head>\n";
		echo "<body class='remoteCard'>\n";
		echo "<table class='kodiPlayerButtonGrid kodiControlEmbededTableButtonGrid'>\n";
		# link back to the launch location of the remote
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
		echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=nightmode'>🌃<div>Night Mode</div></a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=duskmode'>🌇<div>Dusk Mode</div></a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=daymode'>🏙️<div>Day Mode</div></a>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
		echo "				<tr>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonBack kodiPlayerButton ' href='?remoteKey=switchoutput'>📢<div>Switch Output</div></a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonBack kodiPlayerButton ' href='?remoteKey=switchsub'>✍️<div>Switch Sub</div></a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=switchaudio'>🗣️<div>Switch Audio</div></a>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<table class='kodiControlEmbededTableButtonGrid'>\n";
		echo "				<tr>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=configure'>🔧<div>Configure Client</div></a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='kodiPlayerButtonHome kodiPlayerButton ' href='?remoteKey=blank'>🖥️<div>Blank Screen</div></a>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</body>\n";
		echo "</html>\n";
		exit();
	}
}else if (array_key_exists("sleep",$_GET)){
	if (requireGroup("clientRemote")){
		$cache=loadCache();
		# update the sleep file to activate the sleep timer on all clients
		# - this will turn off or blank out the display
		# - this will be deactivated by any keypresses or activation of playback
		setCacheData("sleep.json",$_GET["sleep"], $cache);
		# set the sleep check variable that activates a new sleep timer
		setCacheData("sleepcheck.json",$_SERVER["REQUEST_TIME"], $cache);
		redirect("?remote");
	}
}else if (array_key_exists("setSleep",$_GET)){
	if (requireGroup("clientRemote")){
		echo "<html class='randomFanart'>\n";
		echo "<head>\n";
		echo "<link rel='stylesheet' href='/style.css'>\n";
		echo "</head>\n";
		echo "<body class='remoteCard'>\n";
		echo "<table class='kodiPlayerButtonGrid'>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		drawRemoteHeader();
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<th>\n";
		echo "			Sleep Presets\n";
		echo "		</th>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td class='listCard'>\n";
		echo "			<table class='kodiControlEmbededTable'>\n";
		echo "				<tr>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=5'>5 Minutes</a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=10'>10 Minutes</a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=15'>15 Minutes</a>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "				<tr>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=30'>30 Minutes</a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=60'>60 Minutes</a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=90'>90 Minutes</a>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "				<tr>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=120'>2 Hours</a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=240'>4 Hours</a>\n";
		echo "					</td>\n";
		echo "					<td>\n";
		echo "						<a class='button' href='?sleep=480'>8 Hours</a>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<th>\n";
		echo "			Set custom sleep timer\n";
		echo "		</th>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<table class='kodiControlEmbededTable'>\n";
		echo "			<tr>\n";
		echo "				<form method='get'>\n";
		echo "				<td>\n";
		echo "					<input type='number' name='sleep' placeholder='90' value='90' min='1' max='1000'>\n";
		echo "				</td>\n";
		echo "				<td class='kodiControlEmbededTableButton'>\n";
		echo "					<input class='button' type='submit' value='Set Sleep Timer'>\n";
		echo "				</td>\n";
		echo "				</form>\n";
		echo "			</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</body>\n";
		echo "</html>\n";
		exit();
	}
}else if (array_key_exists("share",$_GET)){
	if (requireGroup("clientRemote")){
		echo "<html class='randomFanart'>";
		echo "<head>";
		echo "<link rel='stylesheet' href='/style.css'>";
		echo "</head>";
		echo "<body class='remoteCard'>";
		echo "<table class='kodiPlayerButtonGrid'>";
		echo "	<tr>\n";
		echo "		<td>\n";
		drawRemoteHeader();
		echo "		</td>\n";
		echo "	</tr>\n";
		# form to use resolver for a url
		echo "	<tr>\n";
		echo "		<form method='get'>\n";
		echo "			<th>\n";
		echo "				Send Stream To Client via Resolver\n";
		echo "			</th>\n";
		echo "		</tr>\n";
		echo "		<tr>\n";
		echo "			<td>\n";
		echo "				<table class='kodiControlEmbededTable'>\n";
		echo "					<tr>\n";
		# build the share url interface for posting urls into the resolver to be passed to kodi
		echo "						<td>\n";
		echo "							<input type='text' name='resolveStream' placeholder='http://example.com/play.php?v=3d4D3ldK'>\n";
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
		echo "				Send URL to Client via Resolver\n";
		echo "			</th>\n";
		echo "		</tr>\n";
		echo "		<tr>\n";
		echo "			<td>\n";
		echo "				<table class='kodiControlEmbededTable'>\n";
		echo "					<tr>\n";
		echo "						<td>\n";
		# build the share url interface for posting urls into the resolver to be passed to kodi
		echo "							<input type='text' name='resolveUrl' placeholder='http://example.com/user/3d4D3ldK/'>\n";
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
		echo "				Send Direct URL\n";
		echo "			</th>\n";
		echo "		</tr>\n";
		echo "		<tr>\n";
		echo "			<td>\n";
		echo "				<table class='kodiControlEmbededTable'>\n";
		echo "					<tr>\n";
		echo "						<td>\n";
		# build the share url interface for posting urls into the resolver to be passed to kodi
		echo "							<input type='text' name='play' placeholder='http://example.com/media.mkv'>\n";
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
		exit();
	}
}else if (array_key_exists("remoteKey",$_GET)){
	$cache=loadCache();
	#addToLog("DEBUG","remoteKey"," pressed key");
	$pressedKey=$_GET["remoteKey"];
	#addToLog("DEBUG","remoteKey",$pressedKey);
	setCacheData("buttonpressed.json", time(), $cache);
	# set found key before the search
	$validKey=false;
	# mark the button pressed file that will trigger all button presses to be checked
	foreach(loadButtons() as $buttonName){
		if ($pressedKey == $buttonName){
			$validKey=true;
		}
	}
	# check if the key was a valid API entry
	if($validKey){
		if($pressedKey == "stop"){
			# remove the currently playing media and clear the command queue
			# - this is only done on the stop event
			setCacheData("media.json", "", $cache);
		}
		# mark the key as pressed
		setCacheData($pressedKey.".json", time(), $cache);
	}else{
		# output API error message
		addToLog("ERROR","CLIENT API ERROR","The remote key was sent '$pressedKey' this key press is unsupported.");
	}
	redirect("/client/?remote");
}else if (array_key_exists("stopPlayer",$_GET)){
	# called when the player has reached the end of the video
	#file_put_contents("status.json", "");
	#file_put_contents("media.json", "");
	# redirect back to main player page
	# - also refreshes the page after playback of video has finished
	redirect("/client/");
}
# build the button data for each button that does not exist
buildButtonData();
# generate the sum used by the qr codes for host specific qr codes
# - where you access this from gives you a diffrent qr code for ip based mdns based and hostname based pages to generate qr codes for those pages
$hostSum=md5($_SERVER["HTTP_HOST"]);
# generate qr codes if they do not yet exist
if (! file_exists("qr_".$hostSum.".png")){
	$command = "qrencode -o '".$_SERVER["DOCUMENT_ROOT"]."/client/qr_".$hostSum.".png' --background='00000000' -m 1 -l h 'http://".$_SERVER["HTTP_HOST"]."/'";
	# launch command in queue
	addToLog("UPDATE","Launching New QR code generator","Full QR gen command '".$command."'");
	# launch the command
	addToQueue("multi",$command);
}
if (! file_exists("qr_ip_".$hostSum.".png")){
	# get the ip based host name
	$domainIP=gethostbyname($_SERVER["HTTP_HOST"]);
	$command = "qrencode -o '".$_SERVER["DOCUMENT_ROOT"]."/client/qr_ip_".$hostSum.".png' --background='00000000' -m 1 -l h 'http://".$domainIP."/'";
	# launch command in queue
	addToLog("UPDATE","Launching New QR code generator","Full QR gen command '".$command."'");
	# launch the command
	addToQueue("multi",$command);
}
?>
<html class='randomFanart'>
<head>
<title>2web Client Player</title>
<link rel='stylesheet' href='/style.css'>
<script src='/2webLib.js'></script>
<script>
	function pageKeys() {
		document.body.addEventListener('keydown', function(event){
			const key = event.key;
			switch (key){
				case " ":
				event.preventDefault();
				playPause();
				break;
				case "Spacebar":
				event.preventDefault();
				playPause();
				break;
				case "ArrowDown":
				event.preventDefault();
				volumeDown();
				break;
				case "ArrowUp":
				event.preventDefault();
				volumeUp();
				break;
				case "ArrowRight":
				event.preventDefault();
				seekForward();
				break;
				case "ArrowLeft":
				event.preventDefault();
				seekBackward();
				break;
			}
		});
	}
	// use the event server API
	<?PHP
	#	# use the event server config if set to setup the remote client
	#	if (file_exists("/etc/2web/client-event.cfg")){
	#		# load the event server config and remove all newlines
	#		$eventServerPath=file_get_contents("/etc/2web/client-event.cfg");
	#		$eventServerPath=str_replace("\n","",$eventServerPath);
	#		echo "const eventData = new EventSource(\"".$eventServerPath."\");\n";
	#	}else{
	#		# load up the local event server
	#		echo "const eventData = new EventSource(\"?events\");\n";
	#	}
	?>
	const eventData = new EventSource("?events");
	console.log(eventData);

	// setup the default window data
	var defaultWindow = "";
	//
	defaultWindow += "<a class='clientBackButton button' href='/'>↖️</a>";
	<?PHP
	if (requireGroup("clientRemote", false)){
		# only show the remote button if the user has remote control permissions
		echo "defaultWindow += \"<a class='clientRemoteButton button' href='/client/?remote'>🎛️</a>\";\n";
	}
	?>
	//defaultWindow += "<a class='clientRemoteButton button' href='/client/?remote'>🎛️</a>";
	defaultWindow += "<img class='clientQrCode' loading='lazy' src='";
	<?PHP
		# load the qr code based on the host sum
		echo "defaultWindow += 'qr_".$hostSum.".png';\n";
	?>
	defaultWindow += "' />\n";
	defaultWindow += "<img class='clientQrCodeIp' loading='lazy' src='";
	<?PHP
		# load the qr code based on the host sum
		echo "defaultWindow += 'qr_ip_".$hostSum.".png';\n";
	?>
	defaultWindow += "' />\n";
	//
	var defaultWindowResetTimer="";
	//
	function resetPlayer(defaultWindow){
		// function to reset the player to the default window
		document.getElementById("pageContent").innerHTML = defaultWindow;
		// redirect the page and stop playback
		//window.location = "?stopPlayer";
		console.log("Setup function to reload page automatically after delay...");
		// set the timer to reload the default page after a delay
		defaultWindowResetTimer = setTimeout(() =>{
			console.log("Reloading page...");
			location.reload();
		}, 60000);
	}
	//function logEvent(event){
	//	console.log(event);
	//}
	//eventData.onmessage = logEvent(event);
	// generate code to run  in the event loop
	eventData.onmessage = (event) => {
		// run code on event server events
		// add the event text to the inner html of the main window
		//document.getElementById("pageContent").innerHTML += event.data+"<br>\n";
		//console.log(event.data);
		console.log(event);
		console.log(event.data);
		if(event.data.substr(0,5) == "resetPlayer"){
			//document.getElementById("pageContent").innerHTML = defaultWindow;
			resetPlayer(defaultWindow);
		}else if(event.data.substr(0,5) == "play="){
			console.log("Got command to run 'play video'");
			// get the video path
			let mediaPath = event.data.substr(5);
			if (mediaPath == ""){
				console.log("reset media player because mediaPath="+mediaPath);
				resetPlayer(defaultWindow);
			}else{
				// clear the timer that keeps the page from losing focus by reloading
				clearTimeout(defaultWindowResetTimer);
				console.log("mediaPath="+mediaPath);
				// build the video player data
				let tempData="";
				tempData += "<video id='video' class='clientVideoPlayer' controls autoplay>";
				tempData += "<source src='"+mediaPath+"'>";
				tempData += "</video>";
				//console.log("tempData="+tempData);
				// insert the video player created above into the page
				document.getElementById("pageContent").innerHTML = tempData;
				// when the end of playback is reached reload the default window
				document.getElementById('video').addEventListener('ended',() => {
					//the function to be executed at the end of playback
					resetPlayer(defaultWindow);
				},false);
			}
		}else if(event.data == "playpause"){
			console.log("Got command to run 'playpause'");
			playPause();
		}else if(event.data == "skipforwards"){
			console.log("Got command to run 'skipforward'");
			seekForward();
		}else if(event.data == "skipbackwards"){
			console.log("Got command to run 'skipbackward'");
			seekBackward();
		}else if(event.data == "volumeup"){
			console.log("Got command to run 'volumedown'");
			volumeUp();
		}else if(event.data == "volumedown"){
			console.log("Got command to run 'volumeup'");
			volumeDown();
		}else if(event.data == "mute"){
			console.log("Got command to run 'mute'");
			mute();
		}else if(event.data == "stop"){
			console.log("Got command to run 'stop'");
			//location.reload();
			resetPlayer(defaultWindow);
		}
	};
	eventData.onerror = (errorData) => {
		// log any errors in the event server
		// - this should show when the network drops connection to the event server
		console.error(errorData);
	};
</script>
</head>
<body onload='pageKeys();resetPlayer(defaultWindow);'>
<?PHP
#include("/usr/share/2web/templates/header.php");
?>
<div id='pageContent' onload='resetPlayer(defaultWindow)'>
<noscript>
<h1 class='errorBanner'>This page can not work without javascript enabled.</h1>
</noscript>
<?PHP
	# load the qr code based on the host sum
	echo "<img class='clientQrCodeIp' loading='lazy' src='qr_ip_".$hostSum.".png' />\n";
	echo "<img class='clientQrCode' loading='lazy' src='qr_".$hostSum.".png' />\n";
?>
</div>
<?PHP
#include("/usr/share/2web/templates/footer.php");
echo "</body>";
echo "</html>";
?>
