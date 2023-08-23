<!--
########################################################################
# 2web project support page
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
		You can support the 2web project directly with monero or though these subscription services.
	</p>
	<ul>
		<li>
			<a href="https://www.subscribestar.com/">
				SubscribeStar
			</a>
		</li>
		<li>
			<a href="https://www.patreon.com/">
				Patreon
			</a>
		</li>
		<li>
			<a href="https://ko-fi.com/">
				Ko-Fi
			</a>
		</li>
		<li>
			<a href="https://liberapay.com/">
				Librepay
			</a>
		</li>
		<li>
			<a href="https://buymeacoffee.com/">
				BuyMeACoffee
			</a>
		</li>
		<li>
			<a href="https://backed.by/">
				Backed.By
			</a>
		</li>
		<li>
				Monero Direct Donation:
		</li>
	</ul>
</div>
<?php
	// add the footer
	include("footer.php");
?>
</body>
</html>
