<!--
########################################################################
# 2web server detected services
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
	<link href="/style.css" rel="stylesheet">
	<script src='/2web.js'></script>
</head>
<body>
<?PHP
include($_SERVER['DOCUMENT_ROOT']."/header.php");
###############################################################################
# enable error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);
###############################################################################
###############################################################################

// take port number and service name and generate a index then generate a series of links

$services = availableServicesArray();
?>

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<div class='titleCard linkInfo'>
	<h1>Server Services</h1>

	<div class="titleCard">
		<ul>
			<?PHP
			foreach($services as $serviceData){
				if (checkPort($serviceData[1])){
					echo "<li><a href='#$serviceData[0]'>$serviceData[0] ($serviceData[2])</a></li>";
				}
			}
			foreach($services as $serviceData){
				if (checkServerPath($serviceData[1])){
					echo "<li><a href='#$serviceData[0]'>$serviceData[0] ($serviceData[2])</a></li>";
				}
			}
			?>
		</ul>
	</div>

	<div class="titleCard">
		<p>
			Links to non-intergrated server services.
		</p>
	</div>
</div>

<table class="titleCard">
	<tr>
		<th>
			Service
		</th>
		<th>
			Port
		</th>
		<th>
			Zeroconf Link
		</th>
		<th>
			Hostname Link
		</th>
		<th>
			Localhost Link
		</th>
		<th>
			Ip Link
		</th>
		<th>
			Description
		</th>
	</tr>
<?PHP
checkServices();
?>
</table>
<hr>
<table class="titleCard">
	<tr>
		<th>
			Service
		</th>
		<th>
			Server Path
		</th>
		<th>
			Zeroconf Link
		</th>
		<th>
			Hostname Link
		</th>
		<th>
			Localhost Link
		</th>
		<th>
			Ip Link
		</th>
		<th>
			Description
		</th>
	</tr>
<?PHP
checkPathServices();
?>
</table>
<hr>
<?PHP
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
