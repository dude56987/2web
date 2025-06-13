<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web live tv settings
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
include("/var/cache/2web/web/header.php");
include("settingsHeader.php");
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
?>

<div class='inputCard'>
	<h2>Index</h2>
	<ul>
	<li><a href='#serverLinkConfig'>Server Link Config</a></li>
	<li><a href='#currentLinks'>Current Links</a></li>
	<li><a href='#addLink'>Add Link</a></li>
	<li><a href='#addCustomLink'>Add Custom Link</a></li>
	<ul>
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
					<button class='button' type='submit' name='iptv2web_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#iptv2web'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='iptv2web_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<?php
echo "<details id='serverLinkConfig' class='titleCard'>\n";
echo "<summary><h2>Server Link Config</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/iptv/sources.cfg");
echo "</pre>\n";
echo "</details>";

echo "<div id='currentLinks' class='settingListCard'>";
echo "<h2>Current links</h2>\n";
$sourceFiles = scandir("/etc/2web/iptv/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/iptv/sources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//$sourceFileName = array_reverse(explode("/",$sourceFile))[0];
	//$sourceFile = "/etc/2web/iptv/sources.d/".$sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos(strtolower($sourceFile),".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='removeLink' value='".$link."'>❌ Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='moveToBottom' value='".$link."'>⬇️ Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				$tempImgSum=md5($link);
				$tempResolverImgSum=md5("http://".gethostname().".local/live/iptv-resolver.php?url=\"".$link."\"");
				if(file_exists($webDirectory."/live/thumbs/".$tempImgSum."-thumb.png")){
					# build image link for direct links
					echo "<a href='/live/channels/channel_".$tempImgSum.".php#'>";
					echo "	<img src='/live/thumbs/".$tempImgSum."-thumb.png' />\n";
					echo "</a>";
				}else if(file_exists($webDirectory."/live/thumbs/".$tempResolverImgSum."-thumb.png")){
					# build image link for stream site links
					echo "<a href='/live/channels/channel_".$tempResolverImgSum.".php#$tempResolverImgSum'>";
					echo "	<img src='/live/thumbs/".$tempResolverImgSum."-thumb.png' />\n";
					echo "</a>";
				}
				echo "</div>\n";
				//echo "</div>";
			}else if (strpos(strtolower($sourceFile),".m3u")){
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeLink' value='".$link."'>❌ Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='moveToBottom' value='".$link."'>⬇️ Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}

		}
	}
}
?>

	<div id='addLink' class='inputCard'>
	<h2>Add Link</h2>
	<form action='admin.php' method='post'>
	<ul>
		<li>Add a link directly to a livestream webpage</li>
		<li>Add a link to a remote m3u/m3u8 playlist containing a list of channels</li>
	</ul>
	<input width='60%' type='text' name='addLink' placeholder='http://example.com/playlist.m3u'>
	<button class='button' type='submit'>➕ Add Link</button>
	</form>
	</div>

	<div id='addCustomLink' class='inputCard'>
	<form action='admin.php' method='post'>
	<h2>Add Custom Link</h2>
	<ul>
		<li>Add the direct path to the remote video stream
			<ul>
				<li><input width='60%' type='text' name='addCustomLink' placeholder='http://example.com/player?stream=example'></li>
			</ul>
		</li>
		<li>Add the title of this channel
			<ul>
				<li><input width='60%' type='text' name='addCustomTitle' placeholder='Channel Title'></li>
			</ul>
		</li>
		<li>Add the remote link path to the custom channel icon
			<ul>
				<li><input width='60%' type='text' name='addCustomIcon' placeholder='http://example.com/Link.png'></li>
			</ul>
		</li>
	</ul>
	<button class='button' type='submit'>➕ Add Channel</button>
	</form>
	</div>

</div>
<?PHP
	include("/var/cache/2web/web/footer.php");
?>
</body>
</html>
