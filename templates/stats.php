<?PHP
########################################################################
# 2web stats info
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
?>
<?PHP
function getStat($totalPath, $label){
	if (file_exists($totalPath)){
		$total = file_get_contents($totalPath);
	}else{
		$total= 0;
	}
	echo "		<span class='singleStat'>";
	echo "			$label:$total";
	echo "		</span>";

}
# modules array
$moduleNames = Array("nfo2web","comic2web","iptv2web","graph2web","music2web","weather2web","ai2web","ytdl2nfo","epg2web");
# check for active processes
foreach($moduleNames as $moduleName){
	if ( file_exists("$moduleName.active")){
		echo "<span class='activeProcess'>";
		echo " ⚙️: $moduleName";
		echo "</span>";
	}
}
# check last update time
if (file_exists("lastUpdate.index")){
	$lastUpdate = file_get_contents("lastUpdate.index");
}else{
	$lastUpdate = "Never";
}
echo "	<div>";
echo "		Last updated on $lastUpdate";
echo "	</div>";

# build the stats section
echo "	<div>";

if (detectEnabledStatus("nfo2web")){
	getStat("totalEpisodes.index", "Episodes");
	getStat("totalShows.index", "Shows");
	getStat("totalMovies.index", "Movies");
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
getStat("webSize.index", "Total Web");
getStat("cacheSize.index", "Video Cache");
getStat("mediaSize.index", "Media");
getStat("free.index", "Free");

echo "	</div>";
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
			echo "<a class='homeFortune' href='/weather/#".str_replace("\n","",file_get_contents("/etc/2web/weather/homepageLocation.cfg"))."'>";
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
