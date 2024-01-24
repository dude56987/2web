<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("nfo2web");
?>
<!--
########################################################################
# 2web movie index
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
<html id='top' class='moviesFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
# add the updated movies below the header
drawPosterWidget("movies");
################################################################################
?>
<div class='settingListCard'>
<h1>
	Movies
	<img id='spinner' src='/spinner.gif' />
</h1>
<?php
flush();
ob_flush();
# store the index path
$indexFilePath="/var/cache/2web/web/movies/movies.index";
# store the empty message
$emptyMessage = "<ul>";
$emptyMessage .= "<li>No Movies have been scanned into the libary!</li>";
$emptyMessage .= "<li>Add libary paths in the <a href='/settings/nfo.php'>video on demand admin interface</a> to populate this page.</li>";
$emptyMessage .= "<li>Add download links in <a href='/settings/ytdl2nfo.php'>video on demand admin interface</a></li>";
$emptyMessage .= "</ul>";

displayIndexWithPages($indexFilePath,$emptyMessage);

?>
</div>

<div class='titleCard'>
	<h1>Playlists</h1>
	<div class='listCard'>
		<a class="button" href="?page=all">∞ All<sup>web</sup></a>
		<a class="button" href="/new/?filter=movies">📜 New<sup>web</sup></a>
		<a class="button" href="/random/?filter=movies">🔀 Random<sup>web</sup></a>
		<a class="button" href="/m3u-gen.php?movies=all">▶️ Play All<sup>External</sup></a>
		<a class="button" href="/m3u-gen.php?movies=all&sort=random">🔀 Play Random<sup>External</sup></a>
		<?PHP
		# play all vlc link
		$tempLink="vlc://".$_SERVER["SERVER_ADDR"]."/m3u-gen.php?movies=all";
		$tempLink=str_replace(" ","%20",$tempLink);
		echo "<a class='button vlcButton' href='$tempLink'>▶️ Play All<sup><span id='vlcIcon'>&#9650;</span> VLC</sup></a>";
		# random vlc link
		$tempLink="vlc://".$_SERVER["SERVER_ADDR"]."/m3u-gen.php?movies=all&sort=random";
		$tempLink=str_replace(" ","%20",$tempLink);
		echo "<a class='button vlcButton' href='$tempLink'>🔀 Play Random<sup><span id='vlcIcon'>&#9650;</span> VLC</sup></a>";
		?>
	</div>
</div>

<?php
// add random movies above the footer
drawPosterWidget("movies", True);
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
