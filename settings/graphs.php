<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web graph settings
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
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include($_SERVER['DOCUMENT_ROOT']."/settings/settingsHeader.php");
?>
<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#graph2webStatus'>Enable or Disable Graphs</a></li>
		<li><a href='#vnstat'>Vnstat</a></li>
		<li><a href='#muninEnabled'>Munin Enabled</a></li>
		<li><a href='#muninDisabled'>Munin Disabled</a></li>
	</ul>
</div>

<div id='moduleStatus' class='inputCard'>
	<h2>Module Actions</h2>
	<table class='controlTable'>
		<tr>
			<td>
				Build or Refresh all generated web components.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='graph2web_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#graph2web'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='graph2web_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<?PHP
	# check for available plugins
	$plugins=scanDir("/usr/share/munin/plugins/");
	$plugins=array_diff($plugins,Array('..','.','.placeholder'));
	$disabledPlugins=Array();
	$enabledPlugins=Array();
	# sort the plugins
	foreach( $plugins as $plugin){
		if (file_exists("/etc/munin/plugins/$plugin")){
			# add to enabled
			$enabledPlugins=array_merge($enabledPlugins,Array($plugin));
		}else{
			# add to disabled
			$disabledPlugins=array_merge($disabledPlugins,Array($plugin));
		}
	}
	#
	sort($enabledPlugins);
	sort($disabledPlugins);
	sort($plugins);
?>

<div class='inputCard' id='muninEnabled'>
	<h2>Enabled Munin Plugins</h2>
<?PHP
	# list available plugins and thier status
	foreach( $enabledPlugins as $plugin){
		#echo "<div class='inputCard'>";
		#echo "<h2 id='pluginStatus_$plugin'>$plugin</h2>";
		# build the buttons to control the status of the plugin
		echo "	<form action='admin.php' class='singleButtonForm' method='post'>\n";
		echo "	<input type='text' name='disableGraphPlugin' value='$plugin' hidden>";
		echo "	<button class='smallButton' type='submit' name='' value=''>🟢 Disable <span class='singleStatValue'>$plugin</span></button>\n";
		echo "	</form>\n";
		#echo "</div>";
	}
?>
</div>

<div class='inputCard' id='muninDisabled'>
	<h2>Disabled Munin Plugins</h2>
<?PHP
	# list available plugins and thier status
	foreach( $disabledPlugins as $plugin){
		#echo "<div class='inputCard'>";
		#echo "<h2 id='pluginStatus_$plugin'>$plugin</h2>";
		# build the buttons to control the status of the plugin
		echo "	<form action='admin.php' class='singleButtonForm' method='post'>\n";
		echo "	<input type='text' name='enableGraphPlugin' value='$plugin' hidden>";
		echo "	<button class='smallButton' type='submit' name='' value=''>◯ Enable <span class='singleStatValue'>$plugin</span></button>\n";
		echo "	</form>\n";
		#echo "</div>";
	}
?>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
