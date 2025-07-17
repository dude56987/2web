<?PHP
########################################################################
# 2web settings header
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
$pageURL = $_SERVER['REQUEST_URI'];
########################################################################
function drawModuleHeaderButton($moduleName,$buttonIcon,$buttonText,$buttonLink,$activeLinkArray=Array()){
	if (detectEnabledStatus("$moduleName")){
		if ($_SERVER['REQUEST_URI'] == "$buttonLink"){
			echo "<a class='activeButton' href='$buttonLink'>";
		}else{
			$foundLink=false;
			# compare the array
			foreach($activeLinkArray as $tempLink){
				if ($_SERVER['REQUEST_URI'] == "$tempLink"){
					$foundLink=true;
				}
			}
			if($foundLink){
				echo "<a class='activeButton' href='$buttonLink'>";
			}else{
				echo "<a class='button' href='$buttonLink'>";
			}
		}
		echo "	$buttonIcon";
		echo "	<span class='headerText'>";
		echo "		$buttonText";
		echo "	</span>";
		echo "</a>";
		return true;
	}
	return false;
}
########################################################################
function drawHeaderButton($buttonIcon,$buttonText,$buttonLink,$activeLinkArray=Array()){
	if ($_SERVER['REQUEST_URI'] == "$buttonLink"){
		echo "<a class='activeButton' href='$buttonLink'>";
	}else{
		$foundLink=false;
		# compare the array
		foreach($activeLinkArray as $tempLink){
			if ($_SERVER['REQUEST_URI'] == "$tempLink"){
				$foundLink=true;
			}
		}
		if($foundLink){
			echo "<a class='activeButton' href='$buttonLink'>";
		}else{
			echo "<a class='button' href='$buttonLink'>";
		}
	}
	echo "	$buttonIcon";
	echo "	<span class='headerText'>";
	echo "		$buttonText";
	echo "	</span>";
	echo "</a>";
}
########################################################################
?>
<div class='titleCard'>
	<h2>Settings</h2>
	<div class='listCard'>
		<?PHP
		# draw the module buttons if the module is enabled
drawHeaderButton("🎛️","System","/settings/system.php",Array("/settings/modules.php","/settings/users.php","/settings/themes.php","/settings/cache.php","/log/","/views/","/settings/about.php","/settings/fortune.php","/settings/clean.php","/settings/manuals.php"));
		$drawVideoOnDemandButton=drawModuleHeaderButton("nfo2web","🎞️","Video On Demand","/settings/nfo.php",Array("/settings/rss.php","/settings/ytdl.php"));
		if(! $drawVideoOnDemandButton ){
			# draw the header button even if only ytdl2nfo is active
			$drawVideoOnDemandButton=drawModuleHeaderButton("ytdl2nfo","🎞️","Video On Demand","/settings/nfo.php",Array("/settings/rss.php","/settings/ytdl.php"));
		}
		if(! $drawVideoOnDemandButton ){
			drawModuleHeaderButton("rss2nfo","🎞️","Video On Demand","/settings/nfo.php",Array("/settings/rss.php","/settings/ytdl.php"));
		}
		drawModuleHeaderButton("music2web","🎧","Music","/settings/music.php");
		drawModuleHeaderButton("comic2web","📚","Comics","/settings/comics.php",Array("/settings/comicsDL.php"));
		drawModuleHeaderButton("iptv2web","📡","Live","/settings/tv.php",Array("/settings/tv.php","/settings/radio.php","/settings/iptv_blocked.php"));
		drawModuleHeaderButton("wiki2web","⛵","Wiki","/settings/wiki.php");
		drawModuleHeaderButton("git2web","💾","Repos","/settings/repos.php");
		drawModuleHeaderButton("portal2web","🚪","Portal","/settings/portal.php",Array("/settings/portal.php","/settings/portal_scanning.php"));
		drawModuleHeaderButton("weather2web","🌤️","Weather","/settings/weather.php");
		drawModuleHeaderButton("ai2web","🧠","AI","/settings/ai.php");
		drawModuleHeaderButton("graph2web","📊","Graphs","/settings/graphs.php");
		drawModuleHeaderButton("kodi2web","🇰","Kodi","/settings/kodi.php");
		drawModuleHeaderButton("php2web","🖥️","Applications","/settings/apps.php");
		?>
	</div>
</div>

<?PHP
function drawModuleHeaderWarning($moduleName){
	if (! checkModStatus($moduleName)){
		echo "	<div class='errorBanner titleCard'>\n";
		echo "		<hr>\n";
		echo "		This module '$moduleName' is disabled. Enable it <a class='button' href='/settings/modules.php#$moduleName'>HERE</a>\n";
		echo "		<hr>\n";
		echo "	</div>\n";
	}
}
#
if (($pageURL == "/settings/tv.php") || ($pageURL == "/settings/radio.php") || ($pageURL == "/settings/iptv_blocked.php")){
	$moduleName="iptv2web";
	drawModuleHeaderWarning($moduleName);
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Live Settings</h2>\n";
	echo "		<div class='listCard'>";
	drawHeaderButton("📺","TV","/settings/tv.php");
	drawHeaderButton("📻","Radio","/settings/radio.php");
	drawHeaderButton("🚫","Blocked","/settings/iptv_blocked.php");
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
	drawModuleHeaderWarning($moduleName);
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Video On Demand Settings</h2>\n";
	echo "		<div class='listCard'>";
	drawHeaderButton("🎞️","Libaries","/settings/nfo.php");
	drawModuleHeaderButton("ytdl2nfo","↓","Downloads","/settings/ytdl2nfo.php");
	drawModuleHeaderButton("rss2nfo","📶","RSS","/settings/rss.php");
	echo "		</div>";
	echo "	</div>\n";
}else if (($pageURL == "/settings/rss.php")){
	$moduleName="rss2nfo";
	drawModuleHeaderWarning($moduleName);
}else if (($pageURL == "/settings/kodi.php")){
	$moduleName="kodi2web";
	drawModuleHeaderWarning($moduleName);
}else if (($pageURL == "/settings/ai.php") || ($pageURL == "/settings/ai_embeds.php") || ($pageURL == "/settings/ai_prompt.php") || ($pageURL == "/settings/ai_txt2img.php") || ($pageURL == "/settings/ai_subtitles.php") || ($pageURL == "/settings/ai_audio.php") ){
	$moduleName="ai2web";
	drawModuleHeaderWarning($moduleName);
	echo "	<div class='titleCard'>\n";
	echo "		<h2>AI Settings</h2>\n";
	echo "		<div class='warningBanner'>The AI tools are currently UNSTABLE and may contain missing/broken features.</div>";
	echo "		<div class='listCard'>\n";
	drawHeaderButton("🧠","Main","/settings/ai.php");
	drawHeaderButton("🪄","Intergrations","/settings/ai_embeds.php");
	drawHeaderButton("👽","Prompting","/settings/ai_prompt.php");
	drawHeaderButton("🎨","Image Gen","/settings/ai_txt2img.php");
	drawHeaderButton("📹","Subtitle Gen","/settings/ai_subtitles.php");
	drawHeaderButton("📢","Audio Gen","/settings/ai_audio.php");
	echo "		</div>";
	echo "	</div>\n";
}else if (($pageURL == "/settings/portal.php") || ($pageURL == "/settings/portal_scanning.php")){
	$moduleName="portal2web";
	drawModuleHeaderWarning($moduleName);
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Portal Settings</h2>\n";
	echo "		<div class='listCard'>";
	drawHeaderButton("⛓️","Sources","/settings/portal.php");
	drawHeaderButton("🌐","Scanning","/settings/portal_scanning.php");
	echo "		</div>";
	echo "	</div>";
}else if ($pageURL == "/settings/music.php"){
	$moduleName="music2web";
	drawModuleHeaderWarning($moduleName);
}else if ($pageURL == "/settings/graphs.php"){
	$moduleName="graph2web";
	drawModuleHeaderWarning($moduleName);
}else if (($pageURL == "/settings/comicsDL.php") || ($pageURL == "/settings/comics.php")){
	$moduleName="comic2web";
	drawModuleHeaderWarning($moduleName);
	echo "	<div class='titleCard'>\n";
	echo "		<h2>Comics Settings</h2>\n";
	echo "		<div class='listCard'>";
	drawHeaderButton("📚","Libaries","/settings/comics.php");
	drawHeaderButton("↓","Downloads","/settings/comicsDL.php");
	echo "		</div>";
	echo "	</div>\n";
}else if ($pageURL == "/settings/music.php"){
	$moduleName="music2web";
	drawModuleHeaderWarning($moduleName);
}else if ($pageURL == "/settings/graphs.php"){
	$moduleName="graph2web";
	drawModuleHeaderWarning($moduleName);
}else if ($pageURL == "/settings/repos.php"){
	$moduleName="git2web";
	drawModuleHeaderWarning($moduleName);
}else if ($pageURL == "/settings/weather.php"){
	$moduleName="weather2web";
	drawModuleHeaderWarning($moduleName);
}else if ($pageURL == "/settings/apps.php"){
	$moduleName="php2web";
	drawModuleHeaderWarning($moduleName);
}else if ($pageURL == "/settings/wiki.php"){
	$moduleName="wiki2web";
	drawModuleHeaderWarning($moduleName);
}else if (($pageURL == "/settings/") || ($pageURL == "/settings/modules.php") || ($pageURL == "/settings/system.php") || ($pageURL == "/settings/cache.php") || ($pageURL == "/settings/clean.php") || (stripos($pageURL, "/log/") != -1) || (stripos($pageURL, "/views/") != -1) || ($pageURL == "/settings/themes.php") || ($pageURL == "/settings/about.php") || ($pageURL == "/settings/manuals.php") || ($pageURL == "/settings/fortune.php")){
	$moduleName="system";
	echo "	<div class='titleCard'>\n";
	echo "		<h2>System Settings</h2>\n";
	echo "		<div class='listCard'>";
	drawHeaderButton("🎛️","General","/settings/system.php");
	drawHeaderButton("🧩","Modules","/settings/modules.php");
	drawHeaderButton("👪","Users & Groups","/settings/users.php");
	drawHeaderButton("🎨","Themes","/settings/themes.php");
	drawHeaderButton("🔮","Fortunes","/settings/fortune.php");
	drawHeaderButton("📥","Cache","/settings/cache.php");
	drawHeaderButton("🧹","Clean","/settings/clean.php");
	drawHeaderButton("📋","Log","/log/");
	drawHeaderButton("👁️","Views","/views/");
	drawHeaderButton("📔","Manuals","/settings/manuals.php");
	drawHeaderButton("❓","About","/settings/about.php");
	echo "		</div>";
	echo "	</div>";
}else{
	$moduleName="none";
}
?>
