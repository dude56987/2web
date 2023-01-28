<?PHP
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
?>
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
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
	</ul>
</div>

<div id='graph2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Graphs Enabled</h2>
			<ul>
				<li>
					Enable or disable the graphs on the website.
				</li>
			</ul>
			<select name='graph2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("graph2web")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
