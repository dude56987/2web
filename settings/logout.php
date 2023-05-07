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
# first run logout the user, then redirect them to the homepage
if (strpos($_SERVER['REQUEST_URI'], "settings/")){
	# redirect after logout and strip url of login infomation
	redirect("https://logout:logout@".$_SERVER["HTTP_HOST"]."/logout.php");
}else{
	redirect("http://".$_SERVER["HTTP_HOST"]."/");
}
# redirect to http version of page to logout, this can not be done on the settings menu
#if ($_SERVER['HTTPS']){
#	$tempURL=str_replace("https://","http://",$_SERVER["HTTP_REFERER"]);
#	redirect($tempURL);
#}
?>
