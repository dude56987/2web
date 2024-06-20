<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web ai settings
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
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#aiCompareGenerate'>Generate Comparisons</a></li>
	</ul>
</div>

<div id='aiCompareGenerate' class='inputCard'>
	<h2>Generate Comparisons</h2>
		<ul>
			<li>
				Generate comparisons for related videos section of webpages.
			</li>
			<li>
				This is EXTREMELY <span title='Central Processing Unit'>CPU</span><sup>Central Processing Unit</sup> expensive. It is generating a <span title='Large Language Model'>LLM</span><sup>Large Language Model</sup> for local content from scratch. This process may take weeks to complete but can be interupted and picked up after a unexpected system reboot.
			</li>
			<li>
				<span class='disabledSetting'>Webpages do NOT support this yet.</span>
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/ai/aiCompareGenerate.cfg","Comparison Generation","aiCompareGenerate");
		?>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
