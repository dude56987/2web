<!--
########################################################################
# 2web fortune webpage
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
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
</script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<?php
################################################################################
?>
<div class='settingListCard'>
<?php
if (file_exists("fortune.index")){
	$todaysFortune = file_get_contents("fortune.index");

	echo "<h3>🔮 Fortune</h3>";
	echo "<div class='fortuneText'>";
	echo "$todaysFortune";
	echo "</div>";
	if (requireGroup("admin",false)){
		echo "		<form action='/settings/admin.php' method='post'>\n";
		echo "			<input width='60%' type='text' name='reloadFortune' value='yes' hidden>\n";
		echo "			<button class='button' type='submit'>👐 New Server Fortune</button>\n";
		echo "		</form>\n";
	}
}else{
	echo "No fortune has been generated yet...<br>";
	echo "Reloading page automatically...";
	reloadPage(10);
}
?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
