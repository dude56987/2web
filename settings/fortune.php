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
		<li><a href='#homepageFortuneStatus'>Homepage Fortune Status</a></li>
	</ul>
</div>
</div>

<div id='homepageFortuneStatus' class='inputCard'>
	<h2>Fortune</h2>
		<ul>
			<li>
				Enable or disable the fortune message on the homepage.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/fortuneStatus.cfg","Homepage Fortunes","homepageFortuneStatus");
		?>
</div>

<div id='help' class='inputCard'>
	<h2>Help</h2>
		<ul>
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
</div>

<div id='fortuneFiles' class='titleCard'>
	<h2>Fortune Lists</h2>
	<p>
		You can enable or disable fortune lists with the below buttons.
	</p>
	<?PHP
		$fortuneFiles = scandir("/etc/2web/fortune/");
		$fortuneFiles = array_diff($fortuneFiles,Array("..","."));
		foreach($fortuneFiles as $fortuneFile){
			$fortuneFile=str_replace(".cfg","",$fortuneFile);
			if(yesNoCfgCheck("/etc/2web/fortune/".$fortuneFile.".cfg")){
				echo "	<form class='singleButtonForm' action='admin.php' method='post'>";
				echo "		<input type='text' name='fortuneFileName' value='$fortuneFile' hidden>\n";
				echo "		<button title='$fortuneFile fortunes are enabled click to DISABLE it'	class='smallButton' type='submit' name='setFortuneFileStatus' value='no'>🟢 Disable <span class='singleStatValue'>$fortuneFile</span></button>";
				echo "	</form>";
			}else{
				echo "	<form class='singleButtonForm' action='admin.php' method='post'>";
				echo "		<input type='text' name='fortuneFileName' value='$fortuneFile' hidden>\n";
				echo "		<button title='$fortuneFile fortunes are disabled click to ENABLE it'	 class='smallButton' type='submit' name='setFortuneFileStatus' value='yes'>◯ Enable <span class='singleStatValue'>$fortuneFile</span></button>";
				echo "	</form>";
			}
		}
	?>
</div>

</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
