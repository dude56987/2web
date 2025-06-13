<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web weather settings
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
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
include("settingsHeader.php");
?>
<div class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addWeatherLocation'>Add Weather Location</a></li>
		<li><a href='#setHomepageWeatherLocation'>Set Homepage Weather Location</a></li>
		<li><a href='#serverLocations'>Server Configured Locations</a></li>
		<li><a href='#currentLocations'>Current Configured Locations</a></li>
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
					<button class='button' type='submit' name='weather2web_update' value='yes'>🗘 Force Update</button>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				Remove the generated module content. To disable the module go to the
				<a href='/settings/modules.php#weather2web'>modules</a>
				page.
			</td>
			<td>
				<form action='admin.php' class='buttonForm' method='post'>
					<button class='button' type='submit' name='weather2web_nuke' value='yes'>☢️ Nuke</button>
				</form>
			</td>
		</tr>
	</table>
</div>

<!-- create the theme picker based on installed themes -->
<div id='setHomepageWeatherLocation' class='inputCard'>
	<h2>Homepage Weather Location</h2>
	<form action='admin.php' class='buttonForm' method='post'>
			<ul>
				<li>
					Is the location for which the current weather will be displayed on the homepage.
				</li>
				<li>
					This will show current conditions on the homepage for the chosen location
				</li>
			</ul>
			<select name='setHomepageWeatherLocation'>
			<?PHP
			# build the location list
			$themePath="/etc/2web/weather/homepageLocation.cfg";
			$activeTheme=file_get_contents($themePath);
			$activeTheme=str_replace("\n","",$activeTheme);
			# read location list
			$sourceFiles = explode("\n",shell_exec("ls -1 /etc/2web/weather/location.d/*.cfg"));
			foreach($sourceFiles as $sourceFile){
				if (strpos($sourceFile,".cfg")){
					//echo "SOURCE FILE = ".$sourceFile."<br>\n";
					$tempTheme=str_replace("/etc/2web/weather/location.d/","",$sourceFile);
					$themeName=str_replace(".cfg","",$tempTheme);
					$themeName=file_get_contents($sourceFile);
					$tempTheme=$themeName;
					if ("disabled" == $activeTheme){
						echo "<option value='disabled' selected>Disabled (Default)</option>";
					}else if ($tempTheme == $activeTheme){
						# mark the selected location
						echo "<option value='".$tempTheme."' selected>".$themeName."</option>\n";
					}else{
						# add all other locations
						echo "<option value='".$tempTheme."' >".$themeName."</option>\n";
					}
				}
			}
			if (file_exists($themePath)){
				echo "<option value='disabled'>Disabled (Default)</option>";
			}else{
				echo "<option value='disabled' selected>Disabled (Default)</option>";
			}
			?>
			<!--
			<option value='red.css' >Red</option>
			<option value='green.css' >Green</option>
			<option value='blue.css' >Blue</option>
			-->
		</select>
		<button class='button' type='submit'>📍 Set Location</button>
	</form>
</div>

<?PHP
echo "<details id='serverLocations' class='titleCard'>\n";
echo "<summary><h2>Server Weather Location Config</h2></summary>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/weather/location.cfg");
echo "</pre>\n";
echo "</details>";

echo "<div id='currentLocations' class='settingListCard'>";
echo "<h2>Current locations</h2>\n";
$sourceFiles = scandir("/etc/2web/weather/location.d/");
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/weather/location.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	if (file_exists($sourceFile)){
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='removeWeatherLocation' value='".$link."'>❌ Remove</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
	<div id='addWeatherLocation' class='inputCard'>
	<h2>Add Weather Location</h2>
	<form action='admin.php' method='post'>
		<ul>
			<li>
				Search for a location to show weather forcasts.
			</li>
			<li>
				If a city returns no results reduce your query size for more results.
				<ul>
					<li>
						'springfield, NY' vs 'spring'
					</li>
				</ul>
			</li>
		</ul>
		<input width='60%' type='text' name='addWeatherLocation' placeholder='New York City, NY'>
		<button class='button' type='submit'>➕ Search For Location</button>
	</form>
	</div>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
