<!--
########################################################################
# 2web about settings
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
		<li><a href='#firewall'>Firewall</a></li>
		<li><a href='#homepageFortuneStatus'>Homepage Fortune Status</a></li>
		<li><a href='#CLI_manuals'>CLI Manual Pages</a></li>
		<li><a href='#services'>Services</a></li>
		<li><a href='#sslCert'>SSL Certificate</a></li>
	</ul>
</div>
<?PHP
	if (file_exists("/usr/share/2web/version.cfg")){
		echo "<div id='version' class='inputCard'>";
		echo "<h2>2web Version Info</h2>";
		echo "	<div>";
		echo "		Version: ".file_get_contents("/usr/share/2web/version.cfg");
		echo "		<a href='/settings/manuals.php#README'>📑</a>";
		echo "	</div>";
		if (file_exists("/usr/share/2web/versionDate.cfg")){
			echo "	<div>";
			echo "		Version Publish Date: ".file_get_contents("/usr/share/2web/versionDate.cfg");
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/buildDate.cfg")){
			echo "	<div>";
			echo "		Build Date: ".file_get_contents("/usr/share/2web/buildDate.cfg");
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_2web.cfg")){
			echo "	<div>";
			echo "		2web Version: ".file_get_contents("/usr/share/2web/version_2web.cfg");
			echo "		<a href='/settings/manuals.php#2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_nfo2web.cfg")){
			echo "	<div>";
			echo "		nfo2web Version: ".file_get_contents("/usr/share/2web/version_nfo2web.cfg");
			echo "		<a href='/settings/manuals.php#nfo2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_comic2web.cfg")){
			echo "	<div>";
			echo "		comic2web Version: ".file_get_contents("/usr/share/2web/version_comic2web.cfg");
			echo "		<a href='/settings/manuals.php#comic2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_iptv2web.cfg")){
			echo "	<div>";
			echo "		iptv2web Version: ".file_get_contents("/usr/share/2web/version_iptv2web.cfg");
			echo "		<a href='/settings/manuals.php#iptv2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_music2web.cfg")){
			echo "	<div>";
			echo "		music2web Version: ".file_get_contents("/usr/share/2web/version_music2web.cfg");
			echo "		<a href='/settings/manuals.php#music2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_graph2web.cfg")){
			echo "	<div>";
			echo "		graph2web Version: ".file_get_contents("/usr/share/2web/version_graph2web.cfg");
			echo "		<a href='/settings/manuals.php#graph2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_weather2web.cfg")){
			echo "	<div>";
			echo "		weather2web Version: ".file_get_contents("/usr/share/2web/version_weather2web.cfg");
			echo "		<a href='/settings/manuals.php#weather2web'>📑</a>";
			echo "	</div>";
		}
		if (file_exists("/usr/share/2web/version_wiki2web.cfg")){
			echo "	<div>";
			echo "		wiki2web Version: ".file_get_contents("/usr/share/2web/version_wiki2web.cfg");
			echo "		<a href='/settings/manuals.php#wiki2web'>📑</a>";
			echo "	</div>";
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
	<h2>CLI<sup>Command Line Interface</sup> Manual Pages</h2>
	<ul>
		<li><a href="/settings/manuals.php#README">README</a></li>
		<li><a href="/settings/manuals.php#2web">2web</a></li>
		<li><a href="/settings/manuals.php#nfo2web">nfo2web</a></li>
		<li><a href="/settings/manuals.php#comic2web">comic2web</a></li>
		<li><a href="/settings/manuals.php#iptv2web">iptv2web</a></li>
		<li><a href="/settings/manuals.php#ytdl2nfo">ytdl2nfo</a></li>
		<li><a href="/settings/manuals.php#weather2web">weather2web</a></li>
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
<div id='sslCert' class='titleCard'>
	<h1>SSL Certificate</h1>
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
</div>

<div id='sslCert' class='titleCard'>
	<h1>2web License</h1>
	<?PHP
	if (file_exists("/usr/share/2web/LICENSE")){
		echo "<pre>";
		echo str_replace(">","&gt;", str_replace("<","&lt;",file_get_contents("/usr/share/2web/LICENSE")));
		echo "</pre>";
	}
	?>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
