<!--
########################################################################
# 2web music artist webpage
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
<html class='seriesBackground'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>
	<?PHP
		# get the show name
		$workingDirectory=getcwd();
		$workingDirectory=explode('/',$workingDirectory);
		$workingDirectory=array_pop($workingDirectory);
		echo ":root{";
		echo "--backgroundPoster: url('/music/".$workingDirectory."/poster.png');";
		echo "--backgroundFanart: url('/music/".$workingDirectory."/fanart.png');";
		echo"}";
	?>
	</style>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
# add the base php libary
include("/usr/share/2web/2webLib.php");
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");

?>
<div class='titleCard artistTitleCard'>
			<?php
			$artist=file("artist.cfg", FILE_IGNORE_NEW_LINES)[0];
			echo "<h1>".$artist."</h1>";
			?>
			<div class='albumPlayerInfo'>
			<div>
				<a href='poster.png'>
					<img class='albumPlayerThumb' src='poster.png' />
				</a>
				<?php
				if (file_exists("genre.cfg")){
					echo "<div>".file_get_contents("genre.cfg")."</div>";
				}
				?>
		</div>
	<?PHP
	echo "<div class='listCard'>";
	echo "	<a class='button' target='_new' href='https://musicbrainz.org/search?type=artist&query=$artist'>🔎 MUSIC BRAINZ</a>";
	echo "	<a class='button' target='_new' href='https://en.wikipedia.org/w/?search=$artist'>🔎 WIKIPEDIA</a>";
	echo "	<a class='button' target='_new' href='https://archive.org/details/movies?query=$artist'>🔎 ARCHIVE.ORG</a>";
	echo "	<a class='button' target='_new' href='https://www.youtube.com/results?search_query=$artist'>🔎 YOUTUBE</a>";
	echo "	<a class='button' target='_new' href='https://odysee.com/$/search?q=$artist'>🔎 ODYSEE</a>";
	echo "	<a class='button' target='_new' href='https://rumble.com/search/video?q=$artist'>🔎 RUMBLE</a>";
	echo "	<a class='button' target='_new' href='https://www.bitchute.com/search/?kind=video&query=$artist'>🔎 BITCHUTE</a>";
	echo "	<a class='button' target='_new' href='https://www.twitch.tv/search?term=$artist'>🔎 TWITCH</a>";
	echo "	<a class='button' target='_new' href='https://veoh.com/find/$artist'>🔎 VEOH</a>";
	echo "	<a class='button' target='_new' href='https://www.imdb.com/find?q=$artist'>🔎 IMDB</a>";
	echo "</div>";
	?>
	</div>
	<div class='listCard'>

		<?PHP
		echo "<a class='button' href='/m3u-gen.php?artist=\"$artist\"'>";
		?>
			▶️ Play All<sup>(External)<sup>
		</a>

		<?PHP
		echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist\"'>";
		?>
			<span id='vlcIcon'>&#9650;</span> VLC
			Play All<sup>(External)<sup>
		</a>

		<?PHP
		echo "<a class='button' href='/m3u-gen.php?artist=\"$artist\"&sort=random'>";
		?>
			▶️ Play Random<sup>(External)<sup>
		</a>

		<?PHP
		echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist\"&sort=random'>";
		?>
			<span id='vlcIcon'>&#9650;</span> VLC
			Play Random<sup>(External)<sup>
		</a>
		</div>
	</div>
</div>



</div>
<div class='settingListCard'>
<h2>Albums</h2>
<?php
if (file_exists("albums.index")){
	// get a list of all the genetrated index links for the page
	#$sourceFiles = explode("\n",file_get_contents("albums.index"));
	$sourceFiles = file("albums.index", FILE_IGNORE_NEW_LINES);
	// reverse the time sort
	$sourceFiles = array_unique($sourceFiles);
	// sort the files by name
	sort($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "$data";
					flush();
					ob_flush();
				}
			}
		}
	}
}else{
	echo "<ul>";
	echo "<li>No Music have been scanned into the libary!</li>";
	echo "<li>Add libary paths in the <a href='/music.php'>video on demand admin interface</a> to populate this page.</li>";
	echo "</ul>";
}

?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
