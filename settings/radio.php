<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web live radio settings
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

ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");

?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
	<li><a href='#serverRadioLinkConfig'>Server Radio Link Config</a></li>
	<li><a href='#radioLinks'>Radio Links</a></li>
	<li><a href='#addRadioStation'>Add Radio Station</a></li>
	<ul>
</div>

<?php

echo "<div id='serverRadioLinkConfig' class='settingListCard'>\n";
echo "<h2>Server Radio Link Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/iptv/radioSources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='radioLinks' class='settingListCard'>";
echo "<h2>Radio Links</h2>\n";
$sourceFiles = scandir("/etc/2web/iptv/radioSources.d/");
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/iptv/radioSources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				$link=file_get_contents($sourceFile);

				# try to find a icon for the link
				$iconLink=md5("http://".$_SERVER["HTTP_HOST"]."/iptv-resolver.php?url=\"".$link."\"");

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				if (file_exists($iconLink)){
					# if the link is a redirected generated link get a diffrent icon
					echo "	<img class='settingsThumb' src='".$iconLink.".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeRadioLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='blockLink' value='".$link."'>BLOCK</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='moveToBottom' value='".$link."'>Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>

	<div id='addRadioLink' class='inputCard'>
		<form action='admin.php' method='post'>
			<h2>Add Radio Link</h2>
			<ul>
				<li>Add a link to a remote m3u/m3u8 playlist containing a list of channels</li>
			</ul>
			<input width='60%' type='text' name='addRadioLink' placeholder='http://example.com/playlist.m3u'>
			<button class='button' type='submit'>Add Link</button>
		</form>
	</div>
</div>

<?php
echo "<div id='radioLinks' class='settingListCard'>";
echo "<h2>Custom Radio Stations</h2>";
# read the custom links
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/iptv/radioSources.d/*.m3u"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".m3u")){
				# get the link, it should be the second line of the file
				$fileData = file_get_contents($sourceFile);
				$fileData = explode('\n',$fileData);
				$title = explode(',',$fileData[1])[1];
				$link = $fileData[2];
				echo "<div class='settingsEntry'>\n";
				echo "	<h2>".$title."</h2>";
				echo "	".$link."";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removeCustomLink' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='moveCustomToBottom' value='".$link."'>Move Down</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addRadioStation' class='inputCard'>
		<form action='admin.php' method='post'>
			<h2>Add Radio Station</h2>
			<ul>
				<li>Add the direct path to the remote audio stream
					<ul>
						<li><input width='60%' type='text' name='addCustomRadioLink' placeholder='http://example.com/player?stream=example'></li>
					</ul>
				</li>
				<li>Add the title of this channel
					<ul>
						<li><input width='60%' type='text' name='addCustomRadioTitle' placeholder='Channel Title'></li>
					</ul>
				</li>
				<li>Add the remote link path to the custom channel icon
					<ul>
						<li><input width='60%' type='text' name='addCustomRadioIcon' placeholder='http://example.com/Link.png'></li>
					</ul>
				</li>
			</ul>
			<button class='button' type='submit'>Add Channel</button>
		</form>
	</div>
</div>
<?PHP
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
