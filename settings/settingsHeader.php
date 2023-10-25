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
$pageURL = $_SERVER['REQUEST_URI'];
########################################################################
function drawSettingsHeaderButton($moduleName,$buttonIcon,$buttonText,$buttonLink){
	if (detectEnabledStatus("$moduleName")){
		if ($_SERVER['REQUEST_URI'] == "$buttonLink"){
			echo "<a class='activeButton' href='$buttonLink'>";
		}else{
			echo "<a class='button' href='$buttonLink'>";
		}
		echo "	$buttonIcon";
		echo "	<span class='headerText'>";
		echo "		$buttonText";
		echo "	</span>";
		echo "</a>";
	}
}
########################################################################
?>
<div class='titleCard'>
	<h2>Settings</h2>
	<div class='listCard'>
		<?PHP
		if ($pageURL == "/settings/modules.php"){
			echo "<a class='activeButton' href='/settings/modules.php'>";
		}else{
			echo "<a class='button' href='/settings/modules.php'>";
		}
		?>
			🧩
			<span class='headerText'>
				Modules
			</span>
		</a>
		<?PHP
		if ($pageURL == "/settings/system.php"){
			echo "<a class='activeButton' href='/settings/system.php'>";
		}else{
			echo "<a class='button' href='/settings/system.php'>";
		}
		?>
			🎛️
			<span class='headerText'>
				System
			</span>
		</a>
		<?PHP
		# draw the module buttons if the module is enabled
		drawSettingsHeaderButton("nfo2web","🎞️","Video On Demand","/settings/nfo.php");
		drawSettingsHeaderButton("music2web","🎧","Music","/settings/music.php");
		drawSettingsHeaderButton("comic2web","📚","Comics","/settings/comics.php");
		drawSettingsHeaderButton("iptv2web","📡","Live","/settings/tv.php");
		drawSettingsHeaderButton("wiki2web","⛵","Wiki","/settings/wiki.php");
		drawSettingsHeaderButton("git2web","💾","Repos","/settings/repos.php");
		drawSettingsHeaderButton("portal2web","🚪","Portal","/settings/portal.php");
		drawSettingsHeaderButton("weather2web","🌤️","Weather","/settings/weather.php");
		drawSettingsHeaderButton("ai2web","🧠","AI","/settings/ai.php");
		drawSettingsHeaderButton("graph2web","📊","Graphs","/settings/graphs.php");
		?>
		<?PHP
		if ($pageURL == "/log/index.php"){
			echo "<a class='activeButton' href='/log/'>";
		}else{
			echo "<a class='button' href='/log/'>";
		}
		?>
			📋
			<span class='headerText'>
				Log
			</span>
		</a>
		<?PHP
		if ($pageURL == "/settings/about.php"){
			echo "<a class='activeButton' href='/settings/about.php'>";
		}else{
			echo "<a class='button' href='/settings/about.php'>";
		}
		?>
			❓
			<span class='headerText'>
				About
			</span>
		</a>
	</div>
</div>

<?PHP
if (($pageURL == "/settings/tv.php") || ($pageURL == "/settings/radio.php") || ($pageURL == "/settings/iptv_blocked.php")){
	$moduleName="iptv2web";
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Live Settings</h2>\n";
	echo "		<div class='listCard'>";
	echo "			<a class='button' href='/settings/tv.php'>📺TV</a>\n";
	echo "			<a class='button' href='/settings/radio.php'>📻Radio</a>\n";
	echo "			<a class='button' href='/settings/iptv_blocked.php'>🚫Blocked</a>\n";
	echo "		</div>";
	echo "	</div>\n";
}else if (($pageURL == "/settings/nfo.php") || ($pageURL == "/settings/ytdl2nfo.php") || ($pageURL == "/settings/rss.php")){
	if ($pageURL == "/settings/nfo.php"){
		$moduleName="nfo2web";
	}else if ($pageURL == "/settings/ytdl2nfo.php"){
		$moduleName="ytdl2nfo";
	}else if ($pageURL == "/settings/rss.php"){
		$moduleName="rss2nfo";
	}
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Video On Demand Settings</h2>\n";
	echo "		<div class='listCard'>";
	echo "			<a class='button' href='/settings/nfo.php'>🎞️Libaries</a>\n";
	if (detectEnabledStatus("ytdl2nfo")){
		echo "			<a class='button' href='/settings/ytdl2nfo.php'>↓Downloads</a>\n";
	}
	if (detectEnabledStatus("rss2nfo")){
		echo "			<a class='button' href='/settings/rss.php'>📶 RSS</a>\n";
	}
	echo "		</div>";
	echo "	</div>\n";
}else if (($pageURL == "/settings/rss.php")){
	$moduleName="rss2nfo";
}else if ($pageURL == "/settings/music.php"){
	$moduleName="music2web";
}else if ($pageURL == "/settings/graphs.php"){
	$moduleName="graph2web";
}else if (($pageURL == "/settings/comicsDL.php") || ($pageURL == "/settings/comics.php")){
	$moduleName="comic2web";
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Comics Settings</h2>\n";
	echo "		<div class='listCard'>";
	echo "			<a class='button' href='/settings/comics.php'>📚Libaries</a>\n";
	echo "			<a class='button' href='/settings/comicsDL.php'>↓Downloads</a>\n";
	echo "		</div>";
	echo "	</div>\n";
}else if ($pageURL == "/settings/music.php"){
	$moduleName="music2web";
}else if ($pageURL == "/settings/graphs.php"){
	$moduleName="graph2web";
}else if ($pageURL == "/settings/modules.php"){
	$moduleName="none";
}else if (($pageURL == "/settings/system.php") || ($pageURL == "/settings/cache.php") || (stripos($pageURL, "/log/") != -1) || (stripos($pageURL, "/views/") != -1) || ($pageURL == "/settings/themes.php")){
	$moduleName="none";
	echo "	<div class='titleCard'>\n";
	echo "		<h2>System Settings</h2>\n";
	echo "		<div class='listCard'>";
	echo "			<a class='button' href='/settings/system.php'>🎛️ General</a>\n";
	echo "			<a class='button' href='/settings/themes.php'>🎨 Themes</a>\n";
	echo "			<a class='button' href='/settings/cache.php'>📥 Cache</a>\n";
	echo "			<a class='button' href='/log/'>📋 Log</a>\n";
	echo "			<a class='button' href='/views/'>👁️ Views</a>\n";
	echo "		</div>";
	echo "	</div>";
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
}else if ($pageURL == "/settings/repos.php"){
	$moduleName="git2web";
}else{
	$moduleName="none";
}
?>
