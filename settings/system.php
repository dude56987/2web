<?PHP
include("/usr/share/2web/2webLib.php");
requireLogin();
?>
<!--
########################################################################
# 2web system settings
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
		<li><a href='#addNewUser'>Add New Administrator</a></li>
		<li><a href='#removeUser'>Remove Administrator</a></li>
		<li><a href='#homepageFortuneStatus'>Homepage Fortune Status</a></li>
	</ul>
</div>

<div id='addNewUser' class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Add New System Administrator</h2>
	<ul>
		<li>Add at least one administrator to lock the settings in this web interface.</li>
		<ul>
			<li>
				<input width='60%' type='text' name='newUserName' placeholder='NEW USERNAME' required>
			</li>
			<li>
				<input width='60%' type='password' name='newUserPass' placeholder='NEW USER PASSWORD' required>
			</li>
			<li>
				<input width='60%' type='password' name='newUserPassVerify' placeholder='VERIFY PASSWORD' required>
			</li>
		</ul>
	</ul>
	<button class='button' type='submit'>Add New Administrator</button>
</form>
</div>

<div id='removeUser' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Remove System Administrator</h2>
			<ul>
				<li>
					Remove existing administrator from accessing the website
				</li>
				<li>
					If at least one administrator exists all web interface settings will be locked, including this page.
				</li>
			</ul>
			<select name='removeUser'>
			<?PHP
			# build theme list
			$themePath="/etc/2web/theme.cfg";
			if (file_exists($themePath)){
				$activeTheme=file_get_contents($themePath);
				$activeTheme=str_replace("\n","",$activeTheme);
				# read in theme files in /usr/share/2web/
				$sourceFiles = explode("\n",shell_exec("ls -1 /etc/2web/users/*.cfg"));
				foreach($sourceFiles as $sourceFile){
					if (strpos($sourceFile,".cfg")){
						$tempTheme=str_replace("/etc/2web/users/","",$sourceFile);
						$themeName=str_replace(".cfg","",$tempTheme);
						if ($tempTheme == $activeTheme){
							# mark the active theme as selected
							echo "<option value='".$tempTheme."' selected>".$themeName."</option>\n";
						}else{
							# add other theme options found
							echo "<option value='".$tempTheme."' >".$themeName."</option>\n";
						}
					}
				}
			}
			?>
		</select>
		<button class='button' type='submit'>Remove Administrator</button>
	</form>
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
			// if the fortuneStatus.cfg file exists that means the fortune is enabled
			if (file_exists("/etc/2web/fortuneStatus.cfg")){
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='homepageFortuneStatus' value='disabled'>🟢 Disable Fortune</button>\n";
				echo "	</form>\n";
			}else{
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='homepageFortuneStatus' value='enabled'>◯ Enable Fortune</button>\n";
				echo "	</form>\n";
			}
		?>
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

<div id='channelCacheUpdateDelay' class='inputCard'>
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
<?PHP
	echo file_get_contents("/etc/2web/web.cfg");
?>
</div>

<div id='channelCacheUpdateDelay' class='inputCard'>
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
<?PHP
	echo file_get_contents("/etc/2web/download.cfg");
?>
</div>

<div id='channelCacheUpdateDelay' class='inputCard'>
<h2>2web Generated Path</h2>
<ul>
	<li>
		Only a server administrator can change this by editing /etc/2web/generated.cfg
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/2web/generated.cfg");
?>
</div>

</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
