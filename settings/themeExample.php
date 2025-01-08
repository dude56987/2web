<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# Example theme page for use inside a iframe
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
<?PHP
echo "<html class='randomFanart'>";
echo "<head>";
echo "<base target='_parent'>";
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
echo "</head>";

# draw the example theme code button
#echo "<div id='webTheme' class='inputCard' style=''>";
#echo "	<style>$tempThemeData</style>";
echo "<body>";
echo "<div class='titleCard'>";
echo "	<h1>$tempTheme</h1>";
echo "	<ul>";
echo "	</ul>";
echo "	<div class='listCard'>";
echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
echo "	<button class='button' type='submit' name='theme' value='$tempTheme'>Set Theme</button>\n";
echo "	</form>\n";
echo "	🌏 🌎 🌍 <a class='button' href='/settings/themes.php?theme=$tempTheme'>Test Theme</a> 🌏 🌎 🌍\n";
echo "<span class='helpQuestionMark'>?</span>\n";
echo "<span class='downloadIcon'>▼</span>\n";
echo "<p>";
echo "abcdefghijklmnopqrstuvwxyz ";
echo "ABCDEFGHIJKLMNOPQRSTUVWXYZ ";
echo "1234567890 ";
echo "!@#$%^&*()_+=-][{}';:?/.,| ";
echo "</p>";
echo "	</div>";
echo "</div>";
echo "</body>";
echo "</html>";
?>
