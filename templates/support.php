<!--
########################################################################
# 2web project support page
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
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("header.php");
	include("/usr/share/2web/2webLib.php");
?>
<div class="titleCard">
	<h2>Support the 2web Project</h2>
	<p>
		You can support the 2web project by using it in your homelab and sharing it with others.
	</p>
	<h2>Source code available on Github</h2>
	<ul>
		<li>
			<a href="https://github.com/dude56987/2web" target="_blank">
				https://github.com/dude56987/2web
			</a>
		</li>
	</ul>
</div>
<?php
	// add the footer
	include("footer.php");
?>
</body>
</html>
