<?PHP
########################################################################
# 2web stats info
# Copyright (C) 2025  Carl J Smith
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
?>
<?PHP
# modules array
$moduleNames = Array("nfo2web","comic2web","iptv2web","graph2web","music2web","weather2web","ytdl2nfo","epg2web","ai2web","portal2web","wiki2web", "git2web","kodi2web");

$isAdmin=requireGroup("admin",false);
if($isAdmin){
	# check for active processes
	foreach($moduleNames as $moduleName){
		if ( file_exists("$moduleName.active")){
			echo "<span class='singleStat'>\n";
			echo "	<span class='singleStatLabel'>\n";
			echo "		<img src='/spinner.gif' />\n";
			echo "		$moduleName ";
			echo "	</span>\n";
			echo "	<span class='singleStatValue'>";
			timeElapsedToHuman(file_get_contents("$moduleName.active"),"");
			echo "	</span>\n";
			echo "</span>\n";
		}
	}
}
if($isAdmin){
	# build the stats section
	echo "<div>\n";

	if (detectEnabledStatus("nfo2web")){
		getStat("totalEpisodes.index", "Episodes");
		getStat("totalShows.index", "Shows");
		getStat("totalMovies.index", "Movies");
		getStat("ytdlShows.index", "YTDL Shows");
	}
	if (detectEnabledStatus("comic2web")){
		getStat("totalComics.index", "Books/Comics");
	}
	if (detectEnabledStatus("git2web")){
		getStat("totalRepos.index", "Repos");
	}
	if (detectEnabledStatus("php2web")){
		getStat("totalApps.index", "Applications");
	}
	if (detectEnabledStatus("iptv2web")){
		getStat("totalChannels.index", "TV Channels");
		getStat("totalRadio.index", "Radio Channels");
	}
	if (detectEnabledStatus("weather2web")){
		getStat("totalWeather.index", "Weather Stations");
	}
	if (detectEnabledStatus("wiki2web")){
		getStat("totalWiki.index", "Wikis");
	}
	if (detectEnabledStatus("music2web")){
		getStat("totalArtists.index", "Artists");
		getStat("totalAlbums.index", "Albums");
		getStat("totalTracks.index", "Tracks");
	}
	if (detectEnabledStatus("ai2web")){
		getStat("promptAi.index", "Prompt AI");
		getStat("txtGenAi.index", "Text Gen AI");
		getStat("subAi.index", "Subtitle AI");
		getStat("imageEditAi.index", "Image Edit AI");
		getStat("imageAi.index", "Image Gen AI");
		getStat("localAi.index", "Total AI");
		getStat("aiSize.index", "Total AI Size");
	}
	if (detectEnabledStatus("git2web")){
		getStat("repoGenSize.index", "Repo Cache");
	}
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

	if (is_readable("drives.index")){
		echo " ";
		echo file_get_contents("drives.index");
	}
	echo "</div>\n";
}

# check last update time
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/new/all.cfg")){
	echo "<div>\n";
	echo "<span class='singleStat'>\n";
	echo "		<span class='singleStatLabel'>Last Updated</span>\n";
	echo "		<span class='singleStatValue'>";
	timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/new/all.cfg"));
	echo "		</span>\n";
	echo "</span>\n";
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index")){
		echo "<span class='singleStat'>\n";
		echo "		<span class='singleStatLabel'>Last Update Check</span>\n";
		echo "		<span class='singleStatValue'>";
		timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index"));
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
		timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index"));
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
		# draw the login time
		echo "<span class='singleStat'>\n";
		echo "	<span class='singleStatLabel'>Login Time</span>\n";
		echo "	<span class='singleStatValue'>";
		timeElapsedToHuman($loginTime);
		echo "</span>\n";
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
		echo "<span class='singleStat'>\n";
		echo "	<span class='singleStatLabel'>\n";
		echo "		<img src='/spinner.gif' />\n";
		echo "		Running Jobs";
		echo "	</span>\n";
		echo "	<span class='singleStatValue'>".$activeJobs."/".$totalJobs."</span>\n";
		echo "</span>\n";
	}
	#
}
echo "</div>\n";

# check the status of the fortunes for drawing large or small widgets
$fortuneEnabled = False;
if ( file_exists("/etc/2web/fortuneStatus.cfg")){
	$fortuneEnabled = True;
}
$weatherEnabled = False;
if (requireGroup("weather2web",false)){
	if ( file_exists("/etc/2web/weather/homepageLocation.cfg")){
		$weatherEnabled = True;
	}
}
if ($fortuneEnabled){
	echo "<a class='homeWeather inputCard' href='/fortune.php'>\n";
	echo "<h3>🔮 Fortune</h3>\n";
	echo "<div class='fortuneText'>\n";
	# load the fortune data
	if (file_exists("/var/cache/2web/web/fortune.index")){
		$todaysFortune = file_get_contents("fortune.index");
		echo "$todaysFortune";
	}else{
		echo "No Fortune has been loaded yet, please wait for the server to catch up.\n";
	}
	echo "</div>\n";
	echo "</a>\n";
}
if ($weatherEnabled){
	echo "<a class='homeFortune inputCard' href='/weather/?station=".str_replace("\n","",file_get_contents("/etc/2web/weather/homepageLocation.cfg"))."'>\n";
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
