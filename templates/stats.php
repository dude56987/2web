<?PHP
########################################################################
# 2web stats info
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
?>
<?PHP
# modules array
$moduleNames = Array("nfo2web","comic2web","iptv2web","graph2web","music2web","weather2web","ytdl2nfo","epg2web","ai2web","portal2web","wiki2web", "git2web","kodi2web");
# check for active processes
foreach($moduleNames as $moduleName){
	if ( file_exists("$moduleName.active")){
		echo "<span class='activeProcess'>";
		echo " ⚙️: $moduleName : ";
		timeElapsedToHuman(file_get_contents("$moduleName.active"),"");
		echo "</span>\n";
	}
}
# check last update time
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/new/all.cfg")){
	echo "	<div>";
	echo "<span class='singleStat'>";
	echo "		Last Updated : ";
	timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/new/all.cfg"));
	echo "</span>";
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index")){
		echo "<span class='singleStat'>";
		echo "		Last Update Check : ";
		timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index"));
		echo "</span>";
	}else{
		echo "<span class='singleStat'>";
		echo "		Last Update Check : Never";
		echo "</span>";
	}
	echo "	</div>";
}else{
	echo "	<div>";
	echo "<span class='singleStat'>";
	echo "		Last updated : Never";
	echo "</span>";
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index")){
		echo "<span class='singleStat'>";
		echo "		Last Update Check : ";
		timeElapsedToHuman(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/lastUpdate.index"));
		echo "</span>";
	}else{
		echo "<span class='singleStat'>";
		echo "		Last Update Check : Never";
		echo "</span>";
	}
	echo "	</div>";
}

# build the stats section
echo "	<div>";

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
getStat("webSize.index", "Total Web");
getStat("mediaSize.index", "Local Media");
getStat("freeSpace.index", "Free Space");

echo "	</div>";

echo "		<div>";
# draw session login time
if (isset($_SESSION["user"])){
	if (isset($_SESSION["loginTime"])){
		$loginTime=$_SESSION["loginTime"];
		# draw the login time
		echo "			<span class='singleStat'>";
		echo "				Login Time:";
		timeElapsedToHuman($loginTime);
		echo "			</span>";
	}
}

$activeJobs=count(array_diff(scanDir("/var/cache/2web/queue/active/"),Array(".","..")));
echo "			<span class='singleStat'>";
echo "				Active Jobs:".$activeJobs."\n";
echo "			</span>";

echo "		</div>";

# check the status of the fortunes for drawing large or small widgets
$fortuneEnabled = False;
if ( file_exists("/etc/2web/fortuneStatus.cfg")){
	$fortuneEnabled = True;
}
$weatherEnabled = False;
if ( file_exists("/etc/2web/weather/homepageLocation.cfg")){
	if ( file_exists($_SERVER['DOCUMENT_ROOT']."/weather.index")){
		$weatherEnabled = True;
	}
}
if (file_exists("fortune.index")){
	$todaysFortune = file_get_contents("fortune.index");
	if ( file_exists("/etc/2web/fortuneStatus.cfg")){
		echo "<a class='homeWeather' href='/fortune.php'>";
		if ($weatherEnabled){
			echo "<div class='inputCard'>";
		}else{
			echo "<div class='listCard'>";
		}
		echo "<h3>🔮 Fortune</h3>";
		echo "<div class='fortuneText'>";
		echo "$todaysFortune";
		echo "</div>";
		echo "</div>";
		echo "</a>";
	}
}

if (file_exists("weather.index")){
	$todaysWeather= file_get_contents("weather.index");
	if ( file_exists("/etc/2web/weather/homepageLocation.cfg")){
		if ( file_exists($_SERVER['DOCUMENT_ROOT']."/weather.index")){
			echo "<a class='homeFortune' href='/weather/?station=".str_replace("\n","",file_get_contents("/etc/2web/weather/homepageLocation.cfg"))."'>";
			if ($fortuneEnabled){
				echo "<div class='inputCard'>";
			}else{
				echo "<div class='listCard'>";
			}
			echo "$todaysWeather";
			echo "</div>";
			echo "</a>";
		}
	}
}
?>
