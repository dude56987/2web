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
		<li><a href='#aiLyricsGenerate'>Generate Lyrics</a></li>
		<li><a href='#aiSubsGenerate'>Generate Subtitles</a></li>
	</ul>
</div>

<div id='aiLyricsGenerate' class='inputCard'>
	<h2>Generate Lyrics</h2>
		<ul>
			<li>
				Generate lyrics for music2web tracks.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/ai/aiLyricsGenerate.cfg","Lyrics Generation","aiLyricsGenerate");
		?>
</div>

<div id='aiSubsGenerate' class='inputCard'>
	<h2>Generate Subtitles</h2>
		<ul>
			<li>
				Generate subtitles using AI for movies and shows added by nfo2web module.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/ai/aiSubsGenerate.cfg","Subs Generation","aiSubsGenerate");
		?>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
