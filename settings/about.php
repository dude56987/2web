<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web about settings
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
		<li><a href='#version'>Version Info</a></li>
		<li><a href='#firewall'>Firewall</a></li>
		<li><a href='#CLI_manuals'>CLI Manual Pages</a></li>
		<li><a href='#system_checks'>System Checks</a></li>
		<li><a href='#sslCert'>SSL Certificate</a></li>
		<li><a href='#license'>2web License</a></li>
	</ul>
</div>
<?PHP
function drawVersionRow($title,$filePath,$extra=""){
	# draw a versionTableRow
	if (file_exists($filePath)){
		echo "	<tr>";
		echo "		<td>\n";
		echo "			$title\n";
		echo "		</td>\n";
		echo "		<td>\n";
		echo "			".file_get_contents($filePath)."\n";
		echo "		</td>\n";
		echo "		<td>\n";
		echo "			".$extra."\n";
		echo "		</td>\n";
		echo "	</tr>\n";
	}
}
	if (file_exists("/usr/share/2web/version.cfg")){
		echo "<div id='version' class='inputCard'>\n";
		echo "<h2>2web Version Info</h2>\n";
		echo "<div>\n";
		echo "	Version Publish Date:\n";
		echo "	".file_get_contents("/usr/share/2web/versionDate.cfg");
		echo "</div>\n";
		echo "<div>\n";
		echo "	Build Date:\n";
		echo "	".file_get_contents("/usr/share/2web/buildDate.cfg");
		echo "</div>\n";
		echo "<h3>2web Server Version</h3>";
		echo "<table>\n";
		$tempLink="<a href='/settings/manuals.php#README'>📑</a>";
		drawVersionRow("Server Version","/usr/share/2web/version.cfg",$tempLink);
		echo "</table>\n";
		echo "<h3>Modules</h3>";
		echo "<table>\n";
		$tempLink="<a href='/settings/manuals.php#2web'>📑</a>";
		drawVersionRow("2web Version","/usr/share/2web/version_2web.cfg",$tempLink);
		$modules=listModules(true);
		sort($modules);
		foreach($modules as $module){
			$tempLink="<a href='/settings/manuals.php#$module'>📑</a>";
			drawVersionRow("$module Version","/usr/share/2web/version_$module.cfg",$tempLink);
		}
		echo "</table>";
		echo "<h3>Resolvers</h3>";
		echo "<table>";
		if (file_exists("/var/cache/2web/generated/yt-dlp/yt-dlp")){
			echo "	<tr>";
			echo "		<td>";
			echo "			yt-dlp Version: ";
			echo "		</td>";
			echo "		<td>";
			echo "			".shell_exec("/var/cache/2web/generated/yt-dlp/yt-dlp --version");
			echo "		</td>";
			echo "	</tr>";
		}
		if (file_exists("/var/cache/2web/generated/pip/gallery-dl/bin/gallery-dl")){
			$galleryVersionCommand='export PYTHONPATH="/var/cache/2web/generated/pip/gallery-dl/";';
			$galleryVersionCommand.="/var/cache/2web/generated/pip/gallery-dl/bin/gallery-dl --version";
			echo "	<tr>";
			echo "		<td>";
			echo "			gallery-dl Version: ";
			echo "		</td>";
			echo "		<td>";
			echo "			".shell_exec($galleryVersionCommand);
			echo "		</td>";
			echo "	</tr>";
		}
		if (file_exists("/var/cache/2web/generated/pip/streamlink/bin/streamlink")){
			$streamlinkVersionCommand='export PYTHONPATH="/var/cache/2web/generated/pip/streamlink/";';
			$streamlinkVersionCommand.="/var/cache/2web/generated/pip/streamlink/bin/streamlink --version";
			echo "	<tr>";
			echo "		<td>";
			echo "			streamlink Version: ";
			echo "		</td>";
			echo "		<td>";
			echo "			".shell_exec($streamlinkVersionCommand);
			echo "		</td>";
			echo "	</tr>";
		}
		echo "</table>";
		if(is_readable("/usr/share/2web/version_hls.cfg")){
			echo "<h3>Players</h3>";
			echo "<table>";
			echo "	<tr>";
			echo "		<td>";
			echo "			hls.js Version: ";
			echo "		</td>";
			echo "		<td>";
			echo "			".file_get_contents("/usr/share/2web/version_hls.cfg");
			echo "		</td>";
			echo "	</tr>";
			echo "</table>";
		}
		echo "</div>";
	}
?>
<div id='firewall' class='inputCard'>
<h2>Firewall</h2>
	<ul>
		<li>
			Unlock port 80 for the public interface
			<ul>
				<li>
					ufw allow port 80
				</li>
			</ul>
		</li>
		<li>
			Unlock port 443 to login to the admin interface
			<ul>
				<li>
					ufw allow port 443
				</li>
			</ul>
		</li>
		<li>
			Unlock port 5353 zeroconf/bonjour/avahi
			<ul>
				<li>
					ufw allow bonjour
				</li>
				<li>
					ufw allow port 5353
				</li>
			</ul>
		</li>
	</ul>
</div>
<div id='CLI_manuals' class='inputCard'>
	<h2>CLI Manual Pages</h2>
	<ul>
		<li><a href="/settings/manuals.php#README">README</a></li>
		<?PHP
		foreach($modules as $module){
			echo "<li><a href='/settings/manuals.php#$module'>$module</a></li>";
		}
		?>
	</ul>
</div>
<div id='system_checks' class='inputCard'>
	<h2>System Checks</h2>
	<ul>
		<?PHP
		if (file_exists("/usr/bin/unattended-upgrades")){
			echo "<li>Unattended Upgrades are <span class='enabledSetting'>INSTALLED</span></li>";
		}else{
			echo "<li>";
			echo "	Unattended Upgrades are <span class='disabledSetting'>NOT INSTALLED</span>";
			echo "	<ul>";
			echo "		<li>To install use 'sudo apt-get install unattended-upgrades'</li>";
			echo "	</ul>";
			echo "</li>";
		}
		if (file_exists("/usr/sbin/ufw")){
			echo "<li>UFW firewall is <span class='enabledSetting'>INSTALLED</span></li>";
		}else{
			echo "<li>";
			echo "	<li>UFW firewall is <span class='disabledSetting'>NOT INSTALLED</span></li>";
			echo "	<ul>";
			echo "		<li>To install use 'sudo apt-get install ufw'</li>";
			echo "	</ul>";
			echo "</li>";
		}
		if (file_exists("/etc/default/fail2ban")){
			echo "<li>Fail2ban is <span class='enabledSetting'>INSTALLED</span></li>";
		}else{
			echo "<li>";
			echo "	<li>Fail2ban is <span class='disabledSetting'>NOT INSTALLED</span></li>";
			echo "	<ul>";
			echo "		<li>To install use 'sudo apt-get install fail2ban'</li>";
			echo "	</ul>";
			echo "</li>";
		}
		?>
	</ul>
</div>

<details id='sslCert' class='titleCard'>
	<summary><h2>SSL Certificate</h2></summary>
	<p>
		You can copy and store the custom certificate in your management system from below.
	</p>
	<?PHP
	if (file_exists("/var/cache/2web/ssl-cert.crt")){
		echo "<pre>";
		echo file_get_contents("/var/cache/2web/ssl-cert.crt");
		echo "</pre>";
	}
	?>
</details>

<details id='license' class='titleCard'>
	<summary><h2>2web License</h2></summary>
	<?PHP
	if (file_exists("/usr/share/2web/LICENSE")){
		echo "<pre>";
		echo str_replace(">","&gt;", str_replace("<","&lt;",file_get_contents("/usr/share/2web/LICENSE")));
		echo "</pre>";
	}
	?>
</details>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
