<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web system theme selection
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
<?PHP
	# get the active theme for use below in reseting previews
	$activeThemeData=file_get_contents("/etc/2web/theme.cfg");

	if (array_key_exists("theme",$_GET)){
		echo "<style>";
		# read the theme source file and load it on this page
		$tempTheme=str_replace("/usr/share/2web/themes/","",$_GET["theme"]);
		$tempThemeData=file_get_contents("/usr/share/2web/themes/".$tempTheme);
		# write the theme data
		echo $tempThemeData;
		echo "</style>";
	}else{
		echo "<link rel='stylesheet' type='text/css' href='/style.css'>";
	}
	?>
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
		<li><a href='#webTheme'>Change Web Theme</a></li>
		<li><a href='#homepageFortuneStatus'>Homepage Fortune Status</a></li>
	</ul>
</div>

<div id='randomTheme' class='inputCard'>
	<h2>Randomize Theme</h2>
		<ul>
			<li>
				Change theme randomly every 30 minutes.
			</li>
			<li>
				This will override the set theme.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/randomTheme.cfg","Random Theme","randomTheme");
		?>
</div>

<div id='webTheme' class='titleCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Web Theme</h2>
			<ul>
				<li>
					Custom themes can be installed in /usr/share/2web/themes/
				</li>
				<li>
					Themes may not display until your browser cache has been refreshed.
				</li>
			</ul>
			<select name='theme'>
			<?PHP
			# build theme list
			$themePath="/etc/2web/theme.cfg";
			if (file_exists($themePath)){
				$activeTheme=file_get_contents($themePath);
				$activeTheme=str_replace("\n","",$activeTheme);
				# read in theme files in /usr/share/2web/
				$sourceFiles = explode("\n",shell_exec("ls -1 /usr/share/2web/themes/*.css"));
				foreach($sourceFiles as $sourceFile){
					if (strpos($sourceFile,".css")){
						$tempTheme=str_replace("/usr/share/2web/themes/","",$sourceFile);
						$themeName=str_replace(".css","",$tempTheme);
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
		<button class='button' type='submit'>Change Theme</button>
	</form>
</div>

<?php
if (array_key_exists("theme",$_GET)){
	echo "<div class='titleCard'>";
	echo "	<h1>".$_GET["theme"]."</h1>";
	echo "	<ul>";
	echo "		<li>You are currently testing the theme ".$_GET["theme"];
	echo "	</ul>";
	echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
	echo "		<button class='button' type='submit' name='theme' value='".$_GET["theme"]."'>Apply Theme</button>\n";
	echo "	</form>\n";
	echo "</div>";
}
?>

<?PHP
# read in theme files in /usr/share/2web/
$sourceFiles = explode("\n",shell_exec("ls -1 /usr/share/2web/themes/*.css"));
foreach($sourceFiles as $sourceFile){
	if (strpos($sourceFile,".css")){
		$tempTheme=str_replace("/usr/share/2web/themes/","",$sourceFile);
		$themeName=str_replace(".css","",$tempTheme);
		$tempThemeData=file_get_contents("/usr/share/2web/themes/".$tempTheme);
		# remove comment lines
		$tempThemeData=preg_replace("/^#.*$/","",$tempThemeData);
		# remove all newlines for building the example
		$tempThemeData=str_replace("\n","",$tempThemeData);
		# embed a iframe for the example page that uses the theme
		echo "<iframe class='inputCard' src='/settings/themeExample.php?theme=$tempTheme' style='height: 25rem;' seamless></iframe>";
	}
}
?>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
