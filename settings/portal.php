<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web portals settings
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
		<li><a href='#addPortalscanSources'>Add portal scanSources Paths</a></li>
		<li><a href='#portalServerscanSourcesPaths'>Server scanSources Paths Config</a></li>
		<li><a href='#portalscanSourcesPaths'>portal scanSources Paths</a></li>
		<li><a href='#addPortalSource'>Add portal Source</a></li>
		<li><a href='#portalServerSourcePaths'>Server portal Sources</a></li>
	</ul>
</div>

<div id='scanAvahi' class='inputCard'>
	<h2>Scan using Avahi</h2>
		<ul>
			<li>
				Look for domains hosting Zeroconf/Bonjour services with Avahi.
			</li>
			<li>
				Scan domains found with avahi for services using <a href='#portalServerScanPaths'>Scan Paths</a> and <a href='#portalServerScanPort'>Scan Ports</a> Configurations.
			</li>
		</ul>
		<?PHP
		buildYesNoCfgButton("/etc/2web/portal/scanAvahi.cfg","Zeroconf/Avahi/Bonjour Domain Scanning","scanAvahi");
		?>
</div>

<?php
echo "<div id='portalServerscanSourcesPaths' class='settingListCard'>\n";
echo "<h2>portal Server scanSources Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/portal/scanSources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='portalscanSourcesPaths' class='settingListCard'>";
echo "<h2>Portal scanSources Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/portal/scanSources.d/*.cfg"));
sort($sourceFiles);
# write each config file as a editable entry
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removePortalScanSource' value='".$link."'>❌ Remove scanSources</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addPortalScanSource' class='inputCard'>
	<h2>Add portal scanSources Path</h2>
	<form action='admin.php' method='post'>
		<ul>
			<li>Use the base url of a server to scan for services</li>
		</ul>
		<input width='60%' type='text' name='addPortalScanSource' placeholder='/absolute/path/to/the/scanSources'>
		<button class='button' type='submit'>➕ Add Path</button>
	</form>
	</div>
</div>

<?php
echo "<div id='portalServerSourcePaths' class='settingListCard'>\n";
echo "<h2>portal Server Source Paths</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/portal/sources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='portalSourcePaths' class='settingListCard'>";
echo "<h2>portal Source Paths</h2>\n";
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/portal/sources.d/*.cfg"));
sort($sourceFiles);
# write each config file as a editable entry
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<button class='button' type='submit' name='removePortalSource' value='".$link."'>❌ Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addPortalScanSources' class='inputCard'>
	<h2>Add portal Source Path</h2>
	<form action='admin.php' method='post'>
		<ul>
			<li>You can manually add portal links with a comma seperated list. One entry per line.</li>
			<li>Title,URL,Description</li>
		</ul>
		<input width='60%' type='text' name='addPortalSource' placeholder=''>
		<button class='button' type='submit'>➕ Add Path</button>
	</form>
	</div>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
