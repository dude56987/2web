<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web ytdl2nfo settings
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
include("settingsHeader.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#serverWebsiteSources'>Server Website Sources</a></li>
		<li><a href='#websiteSources'>Website Sources</a></li>
		<li><a href='#serverUsernameSources'>Server Username Sources</a></li>
		<li><a href='#addWebsiteSource'>Add Website Source</a></li>
		<li><a href='#addUsernameSource'>Add Username Source</a></li>
		<li><a href='#episodeProcessingLimit'>Episode Processing Limit</a></li>
		<li><a href='#downloadPath'>Download Path</a></li>
		<li><a href='#channelProcessingLimit'>Channel Processing Limit</a></li>
		<li><a href='#channelCacheUpdateDelay'>Channel Cache Update Delay</a></li>
		<li><a href='#videoFetchTimeLimit'>Video Fetch Time Limit</a></li>
		<li><a href='#sleepTime'>Sleep Time</a></li>
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
					<button class='button' type='submit' name='ytdl2nfo_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#ytdl2nfo'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='ytdl2nfo_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<?php
echo "<details id='serverWebsiteSources' class='titleCard'>\n";
echo "<summary><h2>Server Website Sources</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/ytdl/sources.cfg");
echo "</pre>\n";
echo "</details>";
?>
<?php
echo "<div id='ytdl_remove_source' class='settingListCard'>";
echo "<h2>Website Sources</h2>\n";
$sourceFiles = scandir("/etc/2web/ytdl/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/ytdl/sources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='ytdl_remove_source' value='".$link."'>❌ Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='ytdl_add_source' class='inputCard'>
	<h2>Add Website Source</h2>
	<form action='admin.php' method='post'>
	<ul>
		<li>
			Website sources will be added as shows based on video links found on source pages.
		</li>
		<li>
			Website sources will be grouped by website name.
		</li>
		<li>
			Website sources can be search pages.
		</li>
	</ul>
	<input width='60%' type='text' name='ytdl_add_source' placeholder='http://link.com/test'>
	<button class='button' type='submit'>➕ Add Source</button>
	</form>
	</div>
</div>
<?PHP
echo "<details id='serverUsernameSources' class='titleCard'>\n";
echo "<summary><h2>Server Username Sources</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/ytdl/usernameSources.cfg");
echo "</pre>\n";
echo "</details>";
?>

<?php
echo "<div id='ytdl_remove_username_source' class='settingListCard'>";
echo "<h2>Username Sources</h2>\n";
$sourceFiles = scandir("/etc/2web/ytdl/usernameSources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/ytdl/usernameSources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='ytdl_remove_username_source' value='".$link."'>❌ Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addUsernameSource' class='inputCard'>
	<h2>Add Username Source</h2>
	<form action='admin.php' method='post'>
	<ul>
		<li>
			For websites with usernames, will create a show with all of that usernames content.
		</li>
		<li>
			The same usernames on diffrent sites will link to the same generated show.
		</li>
	</ul>
	<input width='60%' type='text' name='ytdl_add_username_source' placeholder='http://link.com/test'>
	<button class='button' type='submit'>➕ Add User</button>
	</form>
	</div>
</div>
<div id='episodeProcessingLimit' class='inputCard'>
<h2>Episode Processing Limit</h2>
<ul>
	<li>
		The max number of episodes to process for a channel in a update.
	</li>
	<li>
		This is to throttle downloading metadata from the site.
	</li>
	<li>
		/etc/2web/ytdl/episodeProcessingLimit.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/ytdl/episodeProcessingLimit.cfg");
?>
</pre>
</div>

<div id='downloadPath' class='inputCard'>
<h2>Download Path</h2>
<ul>
	<li>
		This is where nfo libary will be created for shows.
	</li>
</ul>
<pre>
/var/cache/2web/downloads/nfo/
</pre>
</div>

<div id='channelProcessingLimit' class='inputCard'>
<h2>Channel Processing Limit</h2>
<ul>
	<li>
		How many channels can be scanned during an update.
	</li>
	<li>
		/etc/2web/ytdl/channelProcessingLimit.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/ytdl/channelProcessingLimit.cfg");
?>
</pre>
</div>

<div id='channelCacheUpdateDelay' class='inputCard'>
<h2>Channel Cache Update Delay</h2>
<ul>
	<li>
		How long in hours the channel will wait before updating again.
	</li>
	<li>
		/etc/2web/ytdl/channelCacheUpdateDelay.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/ytdl/channelCacheUpdateDelay.cfg");
?>
</pre>
</div>

<div id='videoFetchTimeLimit' class='inputCard'>
<h2>Video Fetch Time Limit</h2>
<ul>
	<li>
		The max time in seconds to wait before the network times out when downloading metadata.
	</li>
	<li>
		/etc/2web/ytdl/videoFetchTimeLimit.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/ytdl/videoFetchTimeLimit.cfg");
?>
</pre>
</div>

<div id='sleepTime' class='inputCard'>
<h2>Sleep Time</h2>
<ul>
	<li>
		The max sleep time in seconds to wait between metadata searches.
	</li>
	<li>
		The true sleep time is randomized between this number and zero.
	</li>
	<li>
		/etc/2web/ytdl/sleepTime.cfg
	</li>
</ul>
<pre>
<?PHP
	echo file_get_contents("/etc/2web/ytdl/sleepTime.cfg");
?>
</pre>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
