<!--
########################################################################
# 2web graph settings
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
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include($_SERVER['DOCUMENT_ROOT']."/settings/settingsHeader.php");
include("/usr/share/2web/2webLib.php");
?>
<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#graph2webStatus'>Enable or Disable Graphs</a></li>
		<li><a href='#vnstat'>Vnstat</a></li>
		<li><a href='#munin'>Munin</a></li>
		<li><a href='#smokeping'>Smokeping</a></li>
	</ul>
</div>
<div id='vnstat' class='inputCard'>
	<h2>Vnstat</h2>
	<ul>
		<li>
			<?PHP
			if(file_exists("/usr/bin/vnstati")){
				echo "<span class='enabledSetting'>Vnstati Enabled</span>";
			}else{
				echo "<span class='disabledSetting'>Vnstati Disabled</span> ";
				echo "Install the vnstati package in order to include graphs.";
			}
			?>
		</li>
		<li>Graphs are added from vnstat via vnstati package
			<ul>
				<li>If vnstat and vnstati package is installed these graphs will be automatically generated.</li>
			</ul>
		</li>
	</ul>
</div>
<div id='smokeping' class='inputCard'>
	<h2>Smokeping</h2>
	<ul>
		<li>
			<?PHP
			if(file_exists("/usr/sbin/smokeping")){
				echo "<span class='enabledSetting'>Smokeping Enabled</span>";
			}else{
				echo "<span class='disabledSetting'>Smokeping Disabled</span> ";
				echo "Install the smokeping package in order to include graphs.";
			}
			?>
		</li>
		<li>Graphs are added from smokeping
			<ul>
				<li>Smokeping targets can be added in the smokeping config file</li>
				<li>/etc/smokeping/config.d/Targets</li>
			</ul>
		</li>
	</ul>
</div>
<div id='munin' class='titleCard'>
	<h2>Munin</h2>
	<ul>
		<li>
			Graphs are added from local munin instance
			<ul>
				<li>To add remove graphs, Add or remove them from munin</li>
				<li>Link plugins in /usr/share/munin/plugins/ to /etc/munin/plugins/ in order to enable plugins.</li>
			</ul>
		</li>
	</ul>
	<h2>Enable Munin Plugins</h2>
	<p>
		Replace the pluginName in the below command and run it on this server to enable the munin plugin.
	</p>
	<pre>ln -s /usr/share/munin/plugins/pluginName /etc/munin/plugins/</pre>
	<h2>
		Munin Plugins with Status
	</h2>
	<pre>
	<?PHP
	echo shell_exec("munin-node-configure");
	?>
	</pre>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
