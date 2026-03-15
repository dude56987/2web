<?PHP
########################################################################
# 2web stats info
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
?>
<?PHP
# modules array
$moduleNames = Array("nfo2web","comic2web","iptv2web","graph2web","music2web","weather2web","ytdl2nfo","epg2web","ai2web","portal2web","wiki2web", "git2web","kodi2web");

$isAdmin=requireGroup("admin",false);
if($isAdmin){
	# check for active processes
	foreach($moduleNames as $moduleName){
		if ( file_exists("$moduleName.active")){
			$moduleRunTime=timeElapsedToHuman(file_get_contents("$moduleName.active"),"");
			echo "<span class='singleStat' title='$moduleName has been running for $moduleRunTime'>\n";
			echo "	<span class='singleStatLabel'>\n";
			echo "		<img src='/spinner.gif' />\n";
			echo "		$moduleName ";
			echo "	</span>\n";
			echo "	<span class='singleStatValue'>";
			echo "		$moduleRunTime";
			echo "	</span>\n";
			echo "</span>\n";
		}
	}
}
# build the stats section
echo "<div>\n";

if (detectEnabledStatus("nfo2web")){
	if(requireGroup("nfo2web",false)){
		getStat("totalEpisodes.index", "Episodes");
		getStat("totalShows.index", "Shows");
		getStat("totalMovies.index", "Movies");
		getStat("ytdlShows.index", "YTDL Shows");
	}
}
if (detectEnabledStatus("comic2web")){
	if(requireGroup("comic2web",false)){
		getStat("totalComics.index", "Books/Comics");
	}
}
if (detectEnabledStatus("git2web")){
	if(requireGroup("git2web",false)){
		getStat("totalRepos.index", "Repos");
	}
}
if (detectEnabledStatus("php2web")){
	if(requireGroup("php2web",false)){
		getStat("totalApps.index", "Applications");
	}
}
if (detectEnabledStatus("iptv2web")){
	if(requireGroup("iptv2web",false)){
		getStat("totalChannels.index", "TV Channels");
		getStat("totalRadio.index", "Radio Channels");
	}
}
if (detectEnabledStatus("weather2web")){
	if(requireGroup("weather2web",false)){
		getStat("totalWeather.index", "Weather Stations");
	}
}
if (detectEnabledStatus("wiki2web")){
	if(requireGroup("wiki2web",false)){
		getStat("totalWiki.index", "Wikis");
	}
}
if (detectEnabledStatus("music2web")){
	if(requireGroup("music2web",false)){
		getStat("totalArtists.index", "Artists");
		getStat("totalAlbums.index", "Albums");
		getStat("totalTracks.index", "Tracks");
	}
}
if (detectEnabledStatus("ai2web")){
	if(requireGroup("ai2web",false)){
		getStat("promptAi.index", "Prompt AI");
		getStat("txtGenAi.index", "Text Gen AI");
		getStat("subAi.index", "Subtitle AI");
		getStat("imageEditAi.index", "Image Edit AI");
		getStat("imageAi.index", "Image Gen AI");
		getStat("localAi.index", "Total AI");
		getStat("aiSize.index", "Total AI Size");
	}
}
if (detectEnabledStatus("git2web")){
	if(requireGroup("git2web",false)){
		getStat("repoGenSize.index", "Repo Cache");
	}
}
# only show size data to server admins
if($isAdmin){
	getStat("webThumbSize.index", "Thumbnail Cache");
	getStat("cacheSize.index", "Video Cache");
	getStat("webCacheSize.index", "Page Cache");
	getStat("transcodeCacheSize.index", "Transcode Cache");
	getStat("zipCacheSize.index", "Zip Cache");
	getStat("m3uCacheSize.index", "Playlist Cache");
	getStat("searchCacheSize.index", "Search Cache");
	getStat("webSize.index", "Total Web");
	getStat("mediaSize.index", "Local Media");
	getStat("freeSpace.index", "Total Free Space");
	# show the drive space data
	if (is_readable("drives.index")){
		echo " ";
		echo file_get_contents("drives.index");
	}
}
echo "</div>\n";

# check last update time
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/new/all.cfg")){
	$lastUpdateString=timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/new/all.cfg"));
	echo "<div>\n";
	echo "<span class='singleStat' title='Last Updated server $lastUpdateString'>\n";
	echo "		<span class='singleStatLabel'>Last Updated</span>\n";
	echo "		<span class='singleStatValue'>\n";
	echo "			$lastUpdateString\n";
	echo "		</span>\n";
	echo "</span>\n";
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index")){
		$lastUpdateString=timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index"));
		echo "<span class='singleStat' title='Last check for new or updated media was $lastUpdateString'>\n";
		echo "		<span class='singleStatLabel'>Last Update Check</span>\n";
		echo "		<span class='singleStatValue'>\n";
		echo "			$lastUpdateString\n";
		echo "		</span>\n";
		echo "</span>\n";
	}else{
		echo "<span class='singleStat'>\n";
		echo "		<span class='singleStatLabel'>Last Update Check</span>\n";
		echo "		<span class='singleStatValue'>Never</span>\n";
		echo "</span>\n";
	}
	echo "</div>\n";
}else{
	echo "<div>\n";
	echo "<span class='singleStat'>\n";
	echo "		<span class='singleStatLabel'>Last Updated</span>\n";
	echo "		<span class='singleStatValue'>Never</span>\n";
	echo "</span>\n";
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index")){
		echo "<span class='singleStat'>";
		echo "		<span class='singleStatLabel'>Last Update Check</span>\n";
		echo "		<span class='singleStatValue'>";
		echo timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index"));
		echo "</span>\n";
		echo "</span>\n";
	}else{
		echo "<span class='singleStat'>\n";
		echo "		<span class='singleStatLabel'>Last Update Check</span>\n";
		echo "		<span class='singleStatValue'>Never</span>\n";
		echo "</span>\n";
	}
	echo "</div>\n";
}

echo "<div>\n";
# draw session login time
if (isset($_SESSION["user"])){
	if (isset($_SESSION["loginTime"])){
		$loginTime=$_SESSION["loginTime"];
		$loginTimeString=timeElapsedToHuman($loginTime);
		# draw the login time
		echo "<span class='singleStat' title='User ".$_SESSION["user"]." logged in $loginTimeString'>\n";
		echo "	<span class='singleStatLabel'>Login Time</span>\n";
		echo "	<span class='singleStatValue'>\n";
		echo "		$loginTimeString\n";
		echo "	</span>\n";
		echo "</span>\n";
	}
}
if($isAdmin){
	#
	$totalJobs=count(array_diff(scanDir("/var/cache/2web/queue/multi/"),Array(".","..")));
	$totalJobs+=count(array_diff(scanDir("/var/cache/2web/queue/single/"),Array(".","..")));
	$totalJobs+=count(array_diff(scanDir("/var/cache/2web/queue/idle/"),Array(".","..")));
	#
	$activeJobs=count(array_diff(scanDir("/var/cache/2web/queue/active/"),Array(".","..")));
	#
	if ($activeJobs > 0){
		echo "<span class='singleStat' title='There are $activeJobs running jobs and $totalJobs in the queue.'>\n";
		echo "	<span class='singleStatLabel'>\n";
		echo "		<img src='/spinner.gif' />\n";
		echo "		Running Jobs";
		echo "	</span>\n";
		echo "	<span class='singleStatValue'>".$activeJobs."/".$totalJobs."</span>\n";
		echo "</span>\n";
	}
	# if the queue is not running in on the server show a warning
	if ( ! file_exists("/var/cache/2web/web/queue2web.active") ){
		#
		echo "<details class='warningBanner'>\n";
		echo "<summary>🖐︎ The Queue is currently unavailable! 🖐︎</summary>\n";
		echo "If this message is shown for  more than 15 minutes a error has occured and the queue can not be restarted. A manual unlock may be required by running <pre>2web unlock</pre> as an administrator on the server.\n";
		echo "</details>\n";
	}
	#
}
echo "</div>\n";

# check the status of the fortunes for drawing large or small widgets
if (yesNoCfgCheck("/etc/2web/fortuneStatus.cfg")){
	$fortuneEnabled = true;
}else{
	$fortuneEnabled = false;
}
$weatherEnabled = False;
if (requireGroup("weather2web",false)){
	if ( file_exists("/etc/2web/weather/homepageLocation.cfg")){
		if(requireGroup("weather2web",false)){
			$weatherEnabled = true;
		}else{
			$weatherEnabled = false;
		}
	}
}
if ($fortuneEnabled){
	if ($weatherEnabled){
		$fortuneClass="inputCard";
	}else{
		$fortuneClass="homeFortune";
		echo "<h3>🔮 Fortune</h3>\n";
	}
	echo "<a class='$fortuneClass' href='/fortune.php'>\n";
	if ($weatherEnabled){
		echo "<h3>🔮 Fortune</h3>\n";
	}
	# load the fortune data
	if (file_exists("/var/cache/2web/web/fortune.index")){
		$todaysFortune = file_get_contents("fortune.index");
		echo "<pre class=''>\n";
		echo "$todaysFortune";
		echo "</pre>\n";
	}else{
		echo "No Fortune has been loaded yet, please wait for the server to catch up.\n";
	}
	echo "</a>\n";
}
if ($weatherEnabled){
	$stationName=(trim(file_get_contents("/etc/2web/weather/homepageLocation.cfg")));
	if ($fortuneEnabled){
		$weatherClass="inputCard";
	}else{
		$weatherClass="homeFortune";
		echo "<h3>🌡️ $stationName</h3>\n";
	}
	echo "<a class='$weatherClass' href='/weather/?station=".cleanText($stationName)."'>\n";
	if ($fortuneEnabled){
		echo "<h3>🌡️ $stationName</h3>\n";
	}
	# load the weather data
	if (file_exists("/var/cache/2web/web/weather.index")){
		$todaysWeather= file_get_contents("weather.index");
		echo "$todaysWeather";
	}else{
		echo "No weather data has been loaded yet, please wait for the server to catch up.\n";
	}
	echo "</a>\n";
}
?>
