<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("music2web");
?>
<!--
########################################################################
# 2web music album webpage
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
<html class='seriesBackground'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>
	<?PHP
		# get the show name
		$data=getcwd();
		$data=explode('/',$data);
		$album=array_pop($data);
		$artist=array_pop($data);
		#$artist= file("artist.cfg", FILE_IGNORE_NEW_LINES)[0];
		echo ":root{";
		echo "--backgroundPoster: url('/music/$artist/$album/album.png');";
		echo "--backgroundFanart: url('/music/$artist/$album/album.png');";
		//echo "--backgroundFanart: url('/music/$artist/fanart.png');";
		echo"}";
	?>
	</style>
</head>
<body>
<?php
################################################################################
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
if (file_exists("artist.cfg")){
	$artist = file_get_contents("artist.cfg");
}
?>
<div class='titleCard'>
	<?php
	$albumTitle=file_get_contents("album.cfg");
	if (file_exists("album.cfg")){
		echo "<h1 id='title'>".$albumTitle."</h1>";
	}
	?>
	<a href='album.png'>
		<img class='albumPlayerThumb' src='album.png' />
	</a>
	<div class='albumPlayerInfo'>
			<?php
			if (array_key_exists("play",$_GET)){
				$track=($_GET['play']);
			}
			if (file_exists("artist.cfg")){
				echo "<div>Artist: ";
				echo "<a href='..'>";
				echo $artist;
				echo "</a>";
				echo "</div>";
			}
			if (file_exists("album.cfg")){
				echo "<div>Album: ";
				echo file_get_contents("album.cfg");
				echo "</div>";
			}
			if (array_key_exists("play",$_GET)){
				if (file_exists("$track.mp3")){
					echo "<div>Duration: ";
					echo file_get_contents($track."_length.cfg");
					echo "</div>";
					echo "<div>Track: ";
					echo "$track";
					echo "</div>";
				}
			}
			if (file_exists("genre.cfg")){
				echo "<div>Genre: ";
				echo file_get_contents("genre.cfg");
				echo "</div>";
			}
			if (array_key_exists("play",$_GET)){
				if (file_exists("$track.png")){
					echo "<img class='trackWaveform' loading='lazy' src='$track.png'>";
				}
			}
			?>
	</div>
	<div class='listCard'>
		<?PHP
		echo "<a class='button' href='/m3u-gen.php?artist=\"$artist/$albumTitle\"'>";
		?>
			▶️ Play All<sup>External</sup>
		</a>
		<?PHP
		echo "<a class='button' href='/m3u-gen.php?artist=\"$artist/$albumTitle\"&sort=random'>";
		?>
			🔀 Play Random<sup>External</sup>
		</a>
		<?PHP
		echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"'>";
		?>
			▶️ Play All
			<sup><span id='vlcIcon'>&#9650;</span> VLC</sup>
		</a>
		<?PHP
		echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"&sort=random'>";
		?>
			🔀 Play Random
			<sup><span id='vlcIcon'>&#9650;</span> VLC</sup>
		</a>
		<?PHP
		# check if the play on kodi button is enabled in the settings
		if (yesNoCfgCheck("/etc/2web/kodi/playOnKodiButton.cfg")){
			# check if the user has permissisons to access these buttons
			if (requireGroup("kodi2web", false)){
				# Draw the kodi button to play all
				echo "<a class='button' target='_new' href='/kodi-player.php?url=http://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"'>\n";
				echo "▶️ Play All <sup>🇰 KODI</sup>\n";
				echo "</a>\n";
				# Draw the kodi button to play all randomly
				echo "<a class='button' target='_new' href='/kodi-player.php?url=http://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"&sort=random'>\n";
				echo "🔀 Play Random <sup>🇰 KODI</sup>\n";
				echo "</a>\n";
			}
		}
		# if the client is enabled
		if (yesNoCfgCheck("/etc/2web/client.cfg")){
			# if the group permissions are available for the current user
			if (requireGroup("clientRemote", false)){
				#
				echo "<a class='button' target='_new' href='/client/?play=http://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"'>\n";
				echo "▶️ Play All <sup>🎟️ Client</sup>\n";
				echo "</a>\n";
				#
				echo "<a class='button' target='_new' href='/client/?play=http://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"&sort=random'>\n";
				echo "🔀 Play Random <sup>🎟️ Client</sup>\n";
				echo "</a>\n";
			}
		}
		?>
	</div>
</div>

<?php
function buildPlayer($track,$player){
	if ($player == "video"){
		#echo "<video id='nfoMediaPlayer' controls loop autoplay poster='$track.png' data-setup='{ \"inactivityTimeout\": 0 }'>";
		if (array_key_exists("loop",$_GET)){
			echo "<video id='mediaPlayer' class='nfoMediaPlayer' controls loop autoplay data-setup='{ \"inactivityTimeout\": 0 }'>\n";
		}else{
			echo "<video id='mediaPlayer' class='nfoMediaPlayer' controls autoplay data-setup='{ \"inactivityTimeout\": 0 }'>\n";
		}
		echo "	<source src='$track.webm' type='video/webm'>\n";
		echo "</video>\n";
		$extension=".webm";
	}else if ($player == "audio"){
		if (array_key_exists("loop",$_GET)){
			echo "<audio id='mediaPlayer' class='albumPlayer' controls loop autoplay>\n";
		}else{
			echo "<audio id='mediaPlayer' class='albumPlayer' controls autoplay>\n";
		}
		echo "	<source src='$track.mp3' type='audio/mpeg'>\n";
		echo "</audio>\n";
		$extension=".mp3";
	}
	# if not looping a single file
	if (! array_key_exists("loop",$_GET)){
		# loop the album
		if (file_exists(str_pad(($track + 1), 3, "0", STR_PAD_LEFT).$extension)){
			# play the next track at the end of this one
			echo "<script>\n";
			echo "document.getElementById('mediaPlayer').addEventListener('ended',playNext,false);\n";
			echo "function playNext(){\n";
			echo "	window.location='?play=".str_pad(($track + 1), 3, "0", STR_PAD_LEFT)."&player=$player#title';\n";
			echo "}\n";
			echo "</script>\n";
		}else{
			# go back to the first track
			echo "<script>\n";
			echo "document.getElementById('mediaPlayer').addEventListener('ended',playNext,false);\n";
			echo "function playNext(){\n";
			echo "	window.location='?play=001&player=$player#title';\n";
			echo "}\n";
			echo "</script>\n";
		}
		# build the previous button
		if (file_exists(str_pad(($track - 1), 3, "0", STR_PAD_LEFT).$extension)){
			# play the next track at the end of this one
			echo "<script>\n";
			echo "document.getElementById('mediaPlayer').addEventListener('ended',playNext,false);\n";
			echo "function playPrevious(){\n";
			echo "	window.location='?play=".str_pad(($track - 1), 3, "0", STR_PAD_LEFT)."&player=$player#title';\n";
			echo "}\n";
			echo "</script>\n";
		}else{
			# go back to the first track
			echo "<script>\n";
			echo "document.getElementById('mediaPlayer').addEventListener('ended',playNext,false);\n";
			echo "function playPrevious(){\n";
			echo "	window.location='?play=001&player=$player#title';\n";
			echo "}\n";
			echo "</script>\n";
		}

	}
	echo "<div class='titleCard'>";
	echo "	<div class='listCard'>";

	echo "		<a class='button' onclick='playPrevious()'>";
	echo "			⏮️ Previous Track";
	echo "		</a>";

	echo "		<a class='button' href='$track.mp3'>";
	echo "			🔗Direct Link";
	echo "		</a>";
	if ($player == "video"){
		echo "		<a class='button' href='?play=$track&player=audio'>";
		echo "			🎶 Audio Player";
		echo "		</a>";
	}else if ($player == "audio"){
		if (file_exists("$track.webm")){
			echo "		<a class='button' href='?play=$track&player=video'>";
			echo "			🕺💃 Visualisation Player";
			echo "		</a>";
		}
	}
	if (array_key_exists("loop",$_GET)){
		echo "<noscript>";
		echo "Loop album does not work without javascript enabled on the browser. Use the external player links to play the album without javascript.";
		echo "</noscript>";
		echo "		<a class='button' href='?play=$track&player=$player'>";
		echo "			➿ Loop Album";
		echo "		</a>";
	}else{
		echo "		<a class='button' href='?play=$track&player=$player&loop'>";
		echo "			➰ Loop Track";
		echo "		</a>";
	}
	echo "		<a class='button' onclick='playNext()'>";
	echo "			⏭️ Next Track";
	echo "		</a>";

	echo "	</div>";
	echo "</div>";
	if (file_exists($track."-lyrics.txt")){
		echo "<div class='titleCard'>";
		echo "<h1>Lyrics</h1>";
		echo "<pre>";
		echo file_get_contents($track."-lyrics.txt");
		echo "</pre>";
		echo "</div>";
	}
}
# build the player for the current track
# - player should automatically loop all tracks and play from the currently chosen track
# ?play=track_number
if (array_key_exists("play",$_GET)){
	$track=($_GET['play']);
	if (array_key_exists("player",$_GET)){
		$player=($_GET['player']);
		if ($player == "audio"){
			buildPlayer($track,"audio");
		}else if ($player == "video"){
			buildPlayer($track,"video");
		}
	}else{
		# default load the video before the audio player
		if (file_exists("$track.mp3")){
			if (file_exists("$track.webm")){
				buildPlayer($track,"video");
			}else{
				buildPlayer($track,"audio");
			}
		}
	}
}
?>

<div class='settingListCard trackListing'>
<h2>Tracks</h2>
<?php
#if (file_exists("tracks.index")){
// get a list of all the genetrated index links for the page
//$sourceFiles = file("tracks.index", FILE_IGNORE_NEW_LINES);
//$sourceFiles = file("tracks.index", FILE_IGNORE_NEW_LINES);
$sourceFiles = scanDir(".");
$noFileFound = True;
// reverse the time sort
$sourceFiles = array_unique($sourceFiles);
# sort the list
sort($sourceFiles);

foreach($sourceFiles as $sourceFile){
	if (stripos($sourceFile, ".mp3")){
		// read the index entry
		$data=file_get_contents(str_replace(".mp3",".index",$sourceFile));
		// write the index entry
		echo "$data";
		flush();
		ob_flush();
		$noFileFound = False;
	}
}
if ($noFileFound){
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
