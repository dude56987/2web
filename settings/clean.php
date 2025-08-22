<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web maintenance tools interface
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
		<li><a href='#cleanResolverCache'>Clean Resolver Cache</a></li>
		<li><a href='#cleanTranscodeCache'>Clean Transcode Cache</a></li>
		<li><a href='#cleanPlaylistCache'>Clean Playlist Cache</a></li>
		<li><a href='#cleanSearchCache'>Clean Search Cache</a></li>
		<li><a href='#cleanZipCache'>Clean Zip Cache</a></li>
		<li><a href='#cleanWebCache'>Clean Web Cache</a></li>
		<li><a href='#cleanLogs'>Clean Logs</a></li>
		<li><a href='#cleanViewCounts'>Clean View Counts</a></li>
		<li><a href='#cleanKodiPlayer'>Clean Kodi Player History</a></li>
	</ul>
</div>

<div id='cleanResolverCache' class='inputCard'>
	<h2>Clean Resolver Cache</h2>
		<?PHP getStat("/var/cache/2web/web/cacheSize.index","Resolver Cache Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/RESOLVER-CACHE/'
			</li>
			<li>
				Change how the resolver does this automatically by changing the <a href='/settings/cache.php'>All Cache Settings</a>.
			</li>
			<li>
				Remove all media cached by the resolver. Removes video cache and metadata.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanResolverCache' value='yes'>🧹 Clean Cached Resolver Data</button>
		</form>
</div>

<div id='cleanTranscodeCache' class='inputCard'>
	<h2>Clean Transcode Cache</h2>
		<?PHP getStat("/var/cache/2web/web/transcodeCacheSize.index","Transcode Cache Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/TRANSCODE-CACHE/'
			</li>
			<li>
				Enable or disable transcoding in the <a href='/settings/cache.php'>All Cache Settings</a>.
			</li>
			<li>
				Remove all media cached by the transcode system.
			</li>
			<li>
				Removes cached transcoded video files.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanTranscodeCache' value='yes'>🧹 Clean Cached Transcode Data</button>
		</form>
</div>

<div id='cleanPlaylistCache' class='inputCard'>
	<h2>Clean Playlist Cache</h2>
		<?PHP getStat("/var/cache/2web/web/m3uCacheSize.index","Playlist Cache Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/m3u_cache/'
			</li>
			<li>
				Remove all existing generated playlists.
			</li>
			<li>
				Removes cached .m3u playlist files.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanPlaylistCache' value='yes'>🧹 Clean Cached Playlist Data</button>
		</form>
</div>

<div id='cleanSearchCache' class='inputCard'>
	<h2>Clean Search Cache</h2>
		<?PHP getStat("/var/cache/2web/web/searchCacheSize.index","Search Cache Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/search/'
			</li>
			<li>
				Remove all generated search results cached on the server.
			</li>
			<li>
				All search queries will have to rebuild the results.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanSearchCache' value='yes'>🧹 Clean Cached Search Data</button>
		</form>
</div>

<div id='cleanZipCache' class='inputCard'>
	<h2>Clean Zip Cache</h2>
		<?PHP getStat("/var/cache/2web/web/zipCacheSize.index","Zip Cache Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/zip_cache/'
			</li>
			<li>
				Remove all generated compressed files.
			</li>
			<li>
				Zip files will have to be regenerated when a coresponding link is clicked.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanZipCache' value='yes'>🧹 Clean Cached Search Data</button>
		</form>
</div>

<div id='cleanWebCache' class='inputCard'>
	<h2>Clean Web Cache</h2>
		<?PHP getStat("/var/cache/2web/web/webCacheSize.index","Web Cache Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/web_cache/'
			</li>
			<li>
				Remove all cached generated webpage content.
			</li>
			<li>
				Widgets will have to rebuild and cache the content. Some pages will need to rebuild themselves on first visit.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanWebCache' value='yes'>🧹 Clean Cached Web Data</button>
		</form>
</div>

<div id='cleanLogs' class='inputCard'>
	<h2>Clean Logs</h2>
		<?PHP
			# read the size of the view database
			if (file_exists("/var/cache/2web/web/log/log.db")){
				echo "<span class='singleStat'>\n";
				echo "	<span class='singleStatLabel'>";
				echo "Log Database Size";
				echo "</span>\n";
				echo "	<span class='singleStatValue'>";
				echo bytesToHuman(filesize("/var/cache/2web/web/log/log.db"));
				echo "</span>\n";
				echo "</span>\n";
			}
		?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/log/log.db'
			</li>
			<li>
				Remove all existing logs.
			</li>
			<li>
				Log database will be deleted from the disk.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanLogCache' value='yes'>🧹 Clean Log Data</button>
		</form>
</div>

<div id='cleanViewCounts' class='inputCard'>
	<h2>Clean View Counts</h2>
		<?PHP
			# read the size of the view database
			if (file_exists("/var/cache/2web/web/views.db")){
				echo "<span class='singleStat'>\n";
				echo "	<span class='singleStatLabel'>";
				echo "View Database Size";
				echo "</span>\n";
				echo "	<span class='singleStatValue'>";
				echo bytesToHuman(filesize("/var/cache/2web/web/views.db"));
				echo "</span>\n";
				echo "</span>\n";
			}
		?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/views.db'
			</li>
			<li>
				View counter data for all pages.
			</li>
			<li>
				Every page will have the view count reset to zero.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanViewCounts' value='yes'>🧹 Clean View Count Data</button>
		</form>
</div>

<div id='cleanWebPlayer' class='inputCard'>
	<h2>Clean Web Player Entries</h2>
		<?PHP getStat("/var/cache/2web/web/webPlayer.index","Web Player Database Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/web_player/'
			</li>
			<li>
				Remove all entries added to the <a href='/web-player.php'>web player</a>.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanWebPlayer' value='yes'>🧹 Clean Web Player Media</button>
		</form>
</div>

<div id='cleanKodiPlayer' class='inputCard'>
	<h2>Clean KODI Player Entries</h2>
		<?PHP getStat("/var/cache/2web/web/kodiPlayerSize.index","KODI Player History Size",true); ?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/kodi-player/'
			</li>
			<li>
				Remove all entries added to the <a href='/kodi-player.php'>KODI Remote</a>.
			</li>
		</ul>
		<form action='admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanKodiPlayer' value='yes'>🧹 Clean KODI Player History</button>
		</form>
</div>


<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
