<?PHP
########################################################################
# 2web settings header
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
<div class='titleCard'>
	<h2>Settings</h2>
	<div class='listCard'>
		<a class='button' href='/settings/modules.php'>
			🧩
			<span class='headerText'>
				Modules
			</span>
		</a>
		<a class='button' href='/settings/system.php'>
			🎛️
			<span class='headerText'>
				General
			</span>
		</a>
		<a class='button' href='/settings/nfo.php'>
			🎞️
			<span class='headerText'>
				Video On Demand
			</span>
		</a>
		<a class='button' href='/settings/music.php'>
			🎧
			<span class='headerText'>
				Music
			</span>
		</a>
		<a class='button' href='/settings/comics.php'>
			📚
			<span class='headerText'>
				Comics
			</span>
		</a>
		<a class='button' href='/settings/tv.php'>
			📡
			<span class='headerText'>
				Live
			</span>
		</a>
		<a class='button' href='/settings/weather.php'>
			🌤️
			<span class='headerText'>
				Weather
			</span>
		</a>
		<a class='button' href='/settings/graphs.php'>
			📊
			<span class='headerText'>
				Graphs
			</span>
		</a>
		<a class='button' href='/settings/about.php'>
			❓
			<span class='headerText'>
				About
			</span>
		</a>
	</div>
</div>

<?PHP
$pageURL = $_SERVER['REQUEST_URI'];
if (($pageURL == "/settings/tv.php") || ($pageURL == "/settings/radio.php") || ($pageURL == "/settings/iptv_blocked.php")){
	$moduleName="iptv2web";
	echo "	<div class='inputCard'>\n";
	echo "		<h2>Live Settings</h2>\n";
	echo "		<ul>";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/tv.php'>📺TV</a>\n";
	echo "			</li>\n";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/radio.php'>📻Radio</a>\n";
	echo "			</li>\n";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/iptv_blocked.php'>🚫Blocked</a>\n";
	echo "			</li>\n";
	echo "		</ul>";
	echo "	</div>\n";
}else if (($pageURL == "/settings/nfo.php") || ($pageURL == "/settings/ytdl2nfo.php")){
	if ($pageURL == "/settings/nfo.php"){
		$moduleName="nfo2web";
	}else if ($pageURL == "/settings/ytdl2nfo.php"){
		$moduleName="ytdl2nfo";
	}
	echo "	<div class='inputCard'>\n";
	echo "		<h2>Video On Demand Settings</h2>\n";
	echo "		<ul>";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/nfo.php'>🎞️Libaries</a>\n";
	echo "			</li>\n";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/ytdl2nfo.php'>↓Downloads</a>\n";
	echo "			</li>\n";
	echo "		</ul>";
	echo "	</div>\n";
}else if (($pageURL == "/settings/comicsDL.php") || ($pageURL == "/settings/comics.php")){
	$moduleName="comic2web";
	echo "	<div class='inputCard'>\n";
	echo "		<h2>Comics Settings</h2>\n";
	echo "		<ul>";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/comics.php'>📚Libaries</a>\n";
	echo "			</li>\n";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/comicsDL.php'>↓Downloads</a>\n";
	echo "			</li>\n";
	echo "		</ul>";
	echo "	</div>\n";
}else if ($pageURL == "/settings/music.php"){
	$moduleName="music2web";
}else if ($pageURL == "/settings/graphs.php"){
	$moduleName="graph2web";
}else if (($pageURL == "/settings/system.php") || ($pageURL == "/settings/cache.php") || ($pageURL == "/settings/log.php")){
	$moduleName="none";
	echo "	<div class='inputCard'>\n";
	echo "		<h2>General Settings</h2>\n";
	echo "		<ul>";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/system.php'>🎛️System</a>\n";
	echo "			</li>\n";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/cache.php'>📥Cache</a>\n";
	echo "			</li>\n";
	echo "			<li>\n";
	echo "				<a class='' href='/log/'>📋Log</a>\n";
	echo "			</li>\n";
	echo "		</ul>";
	echo "	</div>\n";
}else if ($pageURL == "/settings/weather.php"){
	$moduleName="weather2web";
	echo "	<div class='inputCard'>\n";
	echo "		<h2>Weather Settings</h2>\n";
	echo "		<ul>";
	echo "			<li>\n";
	echo "				<a class='' href='/settings/weather.php'>🌤️Weather</a>\n";
	echo "			</li>\n";
	echo "		</ul>";
	echo "	</div>\n";
}else{
	$moduleName="none";
}
if ($moduleName != "none"){
	echo "<div id='".$moduleName."Status' class='inputCard'>";
	echo "	<form action='admin.php' class='buttonForm' method='post'>";
	echo "		<h2>".ucfirst($moduleName)." Status</h2>";
	echo "		<ul>";
	echo "			<li>";
	echo "				Enable or disable $moduleName on the 2web generated website.";
	echo "			</li>";
	# check the module status for drawing enabled or disabled onscreen
	if (detectEnabledStatus($moduleName)){
		echo "				<li>";
		echo "					Currently this module is <span class='enabledSetting'>Enabled</span>.";
		echo "				</li>";
	}else{
		echo "				<li>";
		echo "					Currently this module is <span class='disabledSetting'>Disabled<span>.";
		echo "				</li>";
	}
	echo "		</ul>";
	echo "		<select name='".$moduleName."Status'>";
	// check the status of the graph module
	if (detectEnabledStatus($moduleName)){
		echo "			<option value='enabled' selected>Enabled</option>";
		echo "			<option value='disabled' >Disabled</option>";
	}else{
		echo "			<option value='disabled' selected>Disabled</option>";
		echo "			<option value='enabled' >Enabled</option>";
	}
	echo "		</select>";
	echo "		<button class='button' type='submit'>Set Status</button>";
	echo "	</form>";
	echo "</div>";
}
?>
