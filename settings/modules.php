<?PHP
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web module settings
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
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include($_SERVER['DOCUMENT_ROOT']."/settings/settingsHeader.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li>Enable/Disable Modules
			<ul>
<?PHP
$modules=listModules(true);
sort($modules);
foreach($modules as $module){
	echo "				<li><a href='#".$module."'>$module</a></li>";
}
?>
			</ul>
		</li>
	</ul>
</div>

<div id='index' class='inputCard'>
	<h2>Managing Modules</h2>
	<ul>
		<li>Enabled modules will be updated automatically by the CRON scheduler</li>
		<li>Disabled modules will have cached data removed and no updates will be done</li>
		<li>When a module is disabled the web settings section will be hidden.</li>
	</ul>
</div>

<hr>
<?PHP
foreach($modules as $module){
	echo "<div id='".$module."' class='inputCard'>\n";
	echo "	<h2>$module Module Status</h2>\n";
	echo "	<ul>\n";
	echo "		<li>\n";
	echo "			Enable or disable the $module module.\n";
	echo "		</li>\n";
	# check the current module and show proper description
	if ($module == "nfo2web"){
		echo "		<li>\n";
		echo "			Will enable On Demand Video Processing.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Enable adding local nfo libaries to the website.\n";
		echo "		</li>\n";
	}elseif ($module == "ytdl2nfo"){
		echo "		<li>\n";
		echo "			Enable or disable downloading metadata from websites hosting video.\n";
		echo "		</li>\n";
	}elseif ($module == "iptv2web"){
		echo "		<li>\n";
		echo "			Will enable IPTV live channel and IPTV live radio Processing.\n";
		echo "		</li>\n";
	}elseif ($module == "comic2web"){
		echo "		<li>\n";
		echo "			Will enable comic and book Processing.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Enable or disable comics section of the website.\n";
		echo "		</li>\n";
	}elseif ($module == "weather2web"){
		echo "		<li>\n";
		echo "			Will enable Weather Station Processing.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Enable or disable weather on the website.\n";
		echo "		</li>\n";
	}elseif ($module == "music2web"){
		echo "		<li>\n";
		echo "			Will enable music Processing.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Enable or disable music on the website.\n";
		echo "		</li>\n";
	}elseif ($module == "graph2web"){
		echo "		<li>\n";
		echo "			Will enable graph Processing.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Enable or disable the graphs on the website.\n";
		echo "		</li>\n";
	}elseif ($module == "kodi2web"){
		echo "		<li>\n";
		echo "			Enable or disable sync of linked kodi instances.\n";
		echo "		</li>\n";
	}elseif ($module == "wiki2web"){
		echo "		<div class='warningBanner'>\n";
		echo "			 WARNING: This module is under development and still 🫨 UNSTABLE. Some services and functions of the module may not function completely or correctly.\n";
		echo "		</div>\n";
		# if zimdump does not exist zim files can not be extracted correctly
		if (! is_file("/usr/bin/zimdump")){
			echo "			<span class='disabledSetting'>wiki2web REQUIRES zimdump from zim-tools package to extract .zim files<span>\n";
			echo "		</li>\n";
			echo "		<li>\n";
			echo "			<span class='disabledSetting'>Install zim-tools or wiki2web does nothing<span>\n";
			echo "		</li>\n";
		}
		echo "		<li>\n";
		echo "			Enable or disable extraction of .zim files in wiki directory to the website.\n";
		echo "		</li>\n";
	}elseif ($module == "git2web"){
		echo "		<li>\n";
		echo "			Will enable <a href='https://wikipedia.org/wiki/Git'>git</a> repo processing.\n";
		echo "		</li>\n";
	}elseif ($module == "ai2web"){
		echo "		<div class='warningBanner'>\n";
		echo "			 WARNING: This module is under development and still 🫨 UNSTABLE. Some services and functions of the module may not function completely or correctly.\n";
		echo "		</div>\n";
		echo "		<li>\n";
		echo "			Will enable machine learning for recommending videos.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Will enable diffusion based image generation from text.\n";
		echo "		</li>\n";
		echo "		<li>\n";
		echo "			Will enable gpt4all web interface for prompting.\n";
		echo "		</li>\n";
	}
	# check the module status for drawing enabled or disabled onscreen
	if (checkModStatus($module)){
		echo "		<li>\n";
		echo "			Currently this module is <span class='enabledSetting'>Enabled</span>.\n";
		echo "		</li>\n";
	}else{
		echo "		<li>\n";
		echo "			Currently this module is <span class='disabledSetting'>Disabled<span>.\n";
		echo "		</li>\n";
	}
	echo "			</ul>\n";
	#
	if ($module == "nfo2web"){
		$settingsTempPath="/settings/nfo.php";
		$settingsTempIcon="📺";
	}else if ($module == "php2web"){
		$settingsTempPath="/settings/apps.php";
		$settingsTempIcon="🖥️";
	}else if ($module == "music2web"){
		$settingsTempPath="/settings/music.php";
		$settingsTempIcon="🎧";
	}else if ($module == "portal2web"){
		$settingsTempPath="/settings/portal.php";
		$settingsTempIcon="🚪";
	}else if ($module == "ytdl2nfo"){
		$settingsTempPath="/settings/ytdl2nfo.php";
		$settingsTempIcon="<span class='downloadIcon'>🡇</span>";
	}else if ($module == "rss2nfo"){
		$settingsTempPath="/settings/rss.php";
		$settingsTempIcon="📶";
	}else if ($module == "2web"){
		$settingsTempPath="/settings/system.php";
		$settingsTempIcon="🎛️";
	}else if ($module == "ai2web"){
		$settingsTempPath="/settings/ai.php";
		$settingsTempIcon="🧠";
	}else if ($module == "git2web"){
		$settingsTempPath="/settings/repos.php";
		$settingsTempIcon="💾";
	}else if ($module == "comic2web"){
		$settingsTempPath="/settings/comics.php";
		$settingsTempIcon="📚";
	}else if ($module == "kodi2web"){
		$settingsTempPath="/settings/kodi.php";
		$settingsTempIcon="🇰";
	}else if ($module == "weather2web"){
		$settingsTempPath="/settings/weather.php";
		$settingsTempIcon="🌤️";
	}else if ($module == "iptv2web"){
		$settingsTempPath="/settings/tv.php";
		$settingsTempIcon="📡";
	}else if ($module == "graph2web"){
		$settingsTempPath="/settings/graphs.php";
		$settingsTempIcon="📊";
	}else if ($module == "wiki2web"){
		$settingsTempPath="/settings/wiki.php";
		$settingsTempIcon="⛵";
	}else{
		echo "<div class='errorBanner'>Unknown Module Settings Page. Trying to guess?</div>";
		$settingsTempPath="/settings/$module.php";
		$settingsTempIcon="🎛️";
	}
	#
	echo "	<div class='listCard'>\n";
	// check the status of the module for the dropdown
	if (checkModStatus($module)){
		echo "		<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "		<button class='button' type='submit' name='".$module."Status' value='no'>🟢 Disable Module</button>\n";
		echo "		</form>\n";
	}else{
		echo "		<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "		<button class='button' type='submit' name='".$module."Status' value='yes'>◯ Enable Module</button>\n";
		echo "		</form>\n";
	}
	echo "<a class='button' href='$settingsTempPath'>$settingsTempIcon $module Settings</a>";

	echo "	</div>\n";
	echo "</div>\n";
}
?>
<hr>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
