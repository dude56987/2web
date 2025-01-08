<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web system settings
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
ini_set('display_errors', 1);
error_reporting(E_ALL);
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>
<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#webPlayerStatus'>Web Player Status</a></li>
		<li><a href='#webClientStatus'>Web Client Status</a></li>
		<li><a href='#homepageFortuneStatus'>Homepage Fortune Status</a></li>
		<li><a href='#additionalDictionaryResults'>Additional Dictionary Results</a></li>
		<li><a href='#websiteCachePath'>2web Cache Path</a></li>
		<li><a href='#websiteDownloadPath'>2web Download Path</a></li>
		<li><a href='#websiteGeneratedPath'>2web Generated Path</a></li>
	</ul>
</div>
</div>

<div id='steamLockoutStatus' class='inputCard'>
	<h2>🎮 Steam Lockout</h2>
		<ul>
			<li>
				Enable or disable the Steam lockout.
			</li>
			<li>
				When enabled the steam lockout will disable 2web background processing of libraries.
			</li>
			<li>
				Modules will not update, download, or scan for any content while steam games are running on the same PC.
			</li>
			<li>
				Web, kodi, and client transcode jobs will still recieve normal priority when lockout is enabled.
			</li>
			<li>
				Helpfull if your 2web server is also used as a game desktop.
			</li>
			<li>
				If you do not have Steam installed you can ignore this option.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/steamLockout.cfg","Steam Lockout","steamLockoutStatus");
		?>
</div>

<div id='webPlayerStatus' class='inputCard'>
	<h2>Web Player</h2>
		<ul>
			<li>
				Enable or disable the <a href='/web-player.php'>Web Player Page</a>.
			</li>
			<li>
				Play videos from the cache by submiting links.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/webPlayer.cfg","Web Player","webPlayerStatus");
		?>
</div>

<div id='webClientStatus' class='inputCard'>
	<h2>Web Client</h2>
		<ul>
			<li>
				Enable or disable the <a href='/client/'>Web Client Page</a>.
			</li>
			<li>
				A syncronized page that can be loaded on client machines and controlled by the server web interface.
			</li>
			<li>
				This module creates a webpage that can be remote controlled by the web interface. To send links to kodi, enable and use the <a href='/settings/modules.php#kodi2webStatus'>kodi2web</a> module.
			</li>
			<li>
				You can enable the player and lock the remote control to make the player page public but the controls for the player private. To lock or unlock it use the <a href='/settings/users.php#groupLock_clientRemote'>Users & Groups</a> and lock or unlock the "clientRemote" group.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/client.cfg","Web Client","webClientStatus");
		?>
</div>

<div id='homepageFortuneStatus' class='inputCard'>
	<h2>Fortune</h2>
		<ul>
			<li>
				Enable or disable the fortune message on the homepage.
			</li>
			<li>
				You can use the package manager to add or remove fortunes. The following packages contain the fortune databases.
				<ul>
					<li>fortunes-off<sup>Offensive To the Senses</sup></li>
					<li>fortunes-mario<sup>Video Game Quotes</sup></li>
					<li>fortunes-spam<sup>BBS Spam</sup></li>
					<li>fortunes-bofh-excuses<sup>Admin Excuses</sup></li>
					<li>fortunes-ubuntu-server<sup>Ubuntu Server Tips</sup></li>
					<li>fortunes-debian-hints<sup>Debian Server Tips</sup></li>
					<li>fortunes-min<sup>Basic Default Fortunes</sup></li>
				</ul>
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/fortuneStatus.cfg","Homepage Fortunes","homepageFortuneStatus");
		?>
</div>

<div id='autoReboot' class='inputCard'>
	<h2>Automatic Reboot</h2>
		<ul>
			<li>
				Schedule a automatic reboot each day.
			</li>
			<li>
				Reboot will wait until the server becomes idle.
			</li>
			<li>
			The hour the reboot will be set to happen is set in the <a href="#autoRebootTime">automatic reboot time</a> setting.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/autoReboot.cfg","Automatic Reboot","autoReboot");
		?>
</div>

<div id='autoRebootTime' class='inputCard'>
	<h2>Automatic Reboot Time</h2>
	<form action='admin.php' method='post' class='buttonForm'>
		<ul>
			<li>
				The hour that the reboot should be scheduled for.
			</li>
			<li>
				Selected hour is based on 24 hour clock.
			</li>
			<li>
				This will only reboot if <a href="#autoReboot">automatic reboot</a> is enabled.
			</li>
		</ul>
		<?PHP
			if (file_exists("/etc/2web/autoRebootTime.cfg")){
				# get the current reboot time and show it
				$automaticRebootTime=file_get_contents("/etc/2web/autoRebootTime.cfg");
			}else{
				$automaticRebootTime=4;
			}
			echo "<input type='number' name='autoRebootTime' placeholder='Reboot Hour...' min='0' max='23' value='".$automaticRebootTime."' />";
		?>
		<button type='submit' class='button'>⏰ Change Reboot Time</button>
	</form>
</div>

<div id='additionalDictionaryResults' class='inputCard'>
	<h2>Additional dictionary results</h2>
	<p>
		You can install the below packages in order to expand the local dictionary results in search.
	</p>
	<ul>
		<li>
			dict-freedict-eng-lat
		</li>
		<li>
			dict-gcide
		</li>
		<li>
			dict-devil
		</li>
		<li>
			dict-jargon
		</li>
		<li>
			dict-vera
		</li>
		<li>
			dict-wn
		</li>
		<li>
			dict-foldoc
		</li>
		<li>
			dict-elements
		</li>
	</ul>
</div>

<div id='websiteCachePath' class='inputCard'>
<h2>2web Website Cache Path</h2>
<ul>
	<li>
		The location on the server the web root will be stored.
	</li>
	<li>
		This location will have lots of read/write activity.
	</li>
	<li>
		Only a server administrator can change this by editing /etc/2web/web.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/web.cfg");
?>
</pre>
</div>

<div id='websiteDownloadPath' class='inputCard'>
<h2>2web Download Path</h2>
<ul>
	<li>
		The location on the server the downloads from modules will be stored.
	</li>
	<li>
		This location will have lots of write once read repeatedly disk activity.
	</li>
	<li>
		Only a server administrator can change this by editing /etc/2web/download.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/download.cfg");
?>
</pre>
</div>

<div id='websiteGeneratedPath' class='inputCard'>
<h2>2web Generated Path</h2>
<ul>
	<li>
		Only a server administrator can change this by editing /etc/2web/generated.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/generated.cfg");
?>
</pre>
</div>

</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
