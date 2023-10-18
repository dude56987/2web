<!--
########################################################################
# 2web logout script
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
<?PHP
include("/usr/share/2web/2webLib.php");
# destroy the existing logged in session
session_start();
session_destroy();
# you are logged out
echo "<html class='randomFanart'>";
echo "<head>";
echo "	<link rel='stylesheet' type='text/css' href='/style.css'>";
echo "	<script src='/2webLib.js'></script>";
echo "</head>";
echo "<body>";
include($_SERVER['DOCUMENT_ROOT']."/header.php");
# no login is detected draw the login window
if (array_key_exists("HTTP_REFERER",$_SERVER)){
	$tempURL=$_SERVER["HTTP_REFERER"];
}
echo "<div class='inputCard'>";
echo "<h1>Logged out of ".gethostname()."</h1>";
echo "You have been logged out!";
if (array_key_exists("HTTP_REFERER",$_SERVER)){
	echo "<div class='listCard'>";
	# only return to the last page if the last page exists
	echo "	<a class='button' href='$tempURL'>Return to Last Page</a>";
	echo "</div>";
}
echo "<div class='listCard'>";
$homeURL="https://".$_SERVER["HTTP_HOST"]."/";
echo "	<a class='button' href='$homeURL'>Return to Homepage</a>";
echo "</div>";
echo "<div class='listCard'>";
$loginURL="https://".$_SERVER["HTTP_HOST"]."/login.php";
echo "	<a class='button' href='$loginURL'>Log Back In</a>";
echo "</div>";

echo "</div>";
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
echo "</body>";
echo "</html>";
#redirect("https://".$_SERVER["HTTP_HOST"]."/login.php");
?>
