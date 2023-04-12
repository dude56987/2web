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
# write totals for header building
if (file_exists("totalMovies.index")){
	$totalMovies = file_get_contents("totalMovies.index");
}else{
	$totalMovies = 0;
}
if (file_exists("totalShows.index")){
	$totalShows = file_get_contents("totalShows.index");
}else{
	$totalShows = 0;
}
if (file_exists("totalEpisodes.index")){
	$totalEpisodes = file_get_contents("totalEpisodes.index");
}else{
	$totalEpisodes= 0;
}
if (file_exists("totalComics.index")){
	$totalComics = file_get_contents("totalComics.index");
}else{
	$totalComics = 0;
}
if (file_exists("totalChannels.index")){
	$totalChannels = file_get_contents("totalChannels.index");
}else{
	$totalChannels = 0;
}
if (file_exists("totalRadio.index")){
	$totalRadio = file_get_contents("totalRadio.index");
}else{
	$totalRadio = 0;
}
if (file_exists("fortune.index")){
	$todaysFortune = file_get_contents("fortune.index");
}else{
	$todaysFortune = 0;
}
if (file_exists("weather.index")){
	$todaysWeather= file_get_contents("weather.index");
}else{
	$todaysWeather= 0;
}
if (file_exists("totalWeatherStations.index")){
	$totalWeatherStations = file_get_contents("totalWeatherStations.index");
}else{
	$totalWeatherStations = 0;
}
if (file_exists("webSize.index")){
	$webSize = file_get_contents("webSize.index");
}else{
	$webSize = 0;
}
if (file_exists("mediaSize.index")){
	$mediaSize = file_get_contents("mediaSize.index");
}else{
	$mediaSize = 0;
}
if (file_exists("cacheSize.index")){
	$cacheSize = file_get_contents("cacheSize.index");
}else{
	$cacheSize = 0;
}
if (file_exists("freeSpace.index")){
	$freeSpace = file_get_contents("freeSpace.index");
}else{
	$freeSpace = 0;
}
if (file_exists("lastUpdate.index")){
	$lastUpdate = file_get_contents("lastUpdate.index");
}else{
	$lastUpdate = "Never";
}

echo "<div class='date titleCard'>";
echo "<h1>";
echo ucfirst(shell_exec("hostname"));
echo "<img id='spinner' src='/spinner.gif' />";
echo "</h1>";

if ( file_exists("activityGraph.png")){
	echo "<div>";
	echo "<a href='/graphs/2web_activity/'>";
	echo "<img class='homeActivityGraph' src='activityGraph.png' />";
	echo "</a>";
	echo "</div>";
}

if ( file_exists("nfo2web.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: nfo2web";
	echo "</span>";
}
if ( file_exists("comic2web.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: comic2web";
	echo "</span>";
}
if ( file_exists("iptv2web.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: iptv2web";
	echo "</span>";
}
if ( file_exists("graph2web.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: graph2web";
	echo "</span>";
}
if ( file_exists("music2web.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: music2web";
	echo "</span>";
}
if ( file_exists("weather2web.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: weather2web";
	echo "</span>";
}
if ( file_exists("ytdl2nfo.active")){
	echo "<span class='activeProcess'>";
	echo " ⚙️: ytdl2nfo";
	echo "</span>";
}

echo "	<div>";
echo "		Last updated on $lastUpdate";
echo "	</div>";

echo "	<div>";
if ( $totalShows > 0 ){
	echo "		<span>";
	echo "			Episodes:$totalEpisodes";
	echo "		</span>";
	echo "		<span>";
	echo "			Shows:$totalShows";
	echo "		</span>";
}
if ( $totalMovies > 0 ){
	echo "		<span>";
	echo "			Movies:$totalMovies";
	echo "		</span>";
}
if ( $totalComics > 0 ){
	echo "		<span>";
	echo "			Comics:$totalComics";
	echo "		</span>";
}
if ( $totalChannels > 0 ){
	echo "		<span>";
	echo "			Channels:$totalChannels";
	echo "		</span>";
}
if ( $totalRadio > 0 ){
	echo "		<span>";
	echo "			Radio:$totalRadio";
	echo "		</span>";
}
if ( $totalWeatherStations > 0 ){
	echo "		<span>";
	echo "			Weather Stations:$totalWeatherStations";
	echo "		</span>";
}
echo "		<span>";
echo "			Total Web:$webSize ";
echo "		</span>";
echo "		<span>";
echo "			Video Cache:$cacheSize";
echo "		</span>";
echo "		<span>";
echo "			Media:$mediaSize";
echo "		</span>";
echo "		<span>";
echo "			Free:$freeSpace";
echo "		</span>";
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
echo "</div>"
?>
