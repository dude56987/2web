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
	echo "<div id='".$module."' class='inputCard'>";
	echo "		<h2>$module Module Status</h2>";
	echo "			<ul>";
	echo "				<li>";
	echo "					Enable or disable the $module module.";
	echo "				</li>";
	# check the current module and show proper description
	if ($module == "nfo2web"){
		echo "				<li>";
		echo "					Will enable On Demand Video Processing.";
		echo "				</li>";
		echo "				<li>";
		echo "						Enable adding local nfo libaries to the website.";
		echo "				</li>";
	}elseif ($module == "ytdl2nfo"){
		echo "				<li>";
		echo "						Enable or disable downloading metadata from websites hosting video.";
		echo "				</li>";
	}elseif ($module == "iptv2web"){
		echo "				<li>";
		echo "					Will enable IPTV live channel and IPTV live radio Processing.";
		echo "				</li>";
	}elseif ($module == "comic2web"){
		echo "				<li>";
		echo "					Will enable comic and book Processing.";
		echo "				</li>";
		echo "				<li>";
		echo "					Enable or disable comics section of the website.";
		echo "				</li>";
	}elseif ($module == "weather2web"){
		echo "				<li>";
		echo "					Will enable Weather Station Processing.";
		echo "				</li>";
		echo "				<li>";
		echo "					Enable or disable weather on the website.";
		echo "				</li>";
	}elseif ($module == "music2web"){
		echo "				<li>";
		echo "					Will enable music Processing.";
		echo "				</li>";
		echo "				<li>";
		echo "					Enable or disable music on the website.";
		echo "				</li>";
	}elseif ($module == "graph2web"){
		echo "				<li>";
		echo "					Will enable graph Processing.";
		echo "				</li>";
		echo "				<li>";
		echo "					Enable or disable the graphs on the website.";
		echo "				</li>";
	}elseif ($module == "kodi2web"){
		echo "				<li>";
		echo "					Enable or disable sync of linked kodi instances.";
		echo "				</li>";
	}elseif ($module == "wiki2web"){
		echo "				<div class='errorBanner'>";
		echo "					 WARNING: This module is under development and still 🫨 UNSTABLE. Some services and functions of the module may not function completely or correctly.";
		echo "				</div>";
		# if zimdump does not exist zim files can not be extracted correctly
		if (! is_file("/usr/bin/zimdump")){
			echo "					<span class='disabledSetting'>wiki2web REQUIRES zimdump from zim-tools package to extract .zim files<span>";
			echo "				</li>";
			echo "				<li>";
			echo "					<span class='disabledSetting'>Install zim-tools or wiki2web does nothing<span>";
			echo "				</li>";
		}
		echo "				<li>";
		echo "					Enable or disable extraction of .zim files in wiki directory to the website.";
		echo "				</li>";
	}elseif ($module == "git2web"){
		echo "				<li>";
		echo "					Will enable <a href='https://wikipedia.org/wiki/Git'>git</a> repo processing.";
		echo "				</li>";
	}elseif ($module == "ai2web"){
		echo "				<div class='errorBanner'>";
		echo "					 WARNING: This module is under development and still 🫨 UNSTABLE. Some services and functions of the module may not function completely or correctly.";
		echo "				</div>";
		echo "				<li>";
		echo "					Will enable machine learning for recommending videos.";
		echo "				</li>";
		echo "				<li>";
		echo "					Will enable diffusion based image generation from text.";
		echo "				</li>";
		echo "				<li>";
		echo "					Will enable gpt4all web interface for prompting.";
		echo "				</li>";
	}
	# check the module status for drawing enabled or disabled onscreen
	if (checkModStatus($module)){
		echo "				<li>";
		echo "					Currently this module is <span class='enabledSetting'>Enabled</span>.";
		echo "				</li>";
	}else{
		echo "				<li>";
		echo "					Currently this module is <span class='disabledSetting'>Disabled<span>.";
		echo "				</li>";
	}
	echo "			</ul>";
	echo "	<div class='listCard'>";
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
	// add force update button
	echo "		<form action='admin.php' class='buttonForm' method='post'>\n";
	echo "			<button class='button' type='submit' name='".$module."_update' value='yes'>⚙️ Force Update</button>\n";
	echo "		</form>\n";
	echo "	</div>";
	echo "</div>";
}
?>
<hr>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
