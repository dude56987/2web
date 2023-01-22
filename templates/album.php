<html id='top' class='seriesBackground'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
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
ini_set('display_errors', 1);
# add the base php libary
include("/usr/share/2web/2webLib.php");
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
		echo "<h1>".$albumTitle."</h1>";
	}
	?>
	<img class='albumPlayerThumb' src='album.png' />
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
			▶️ Play All<sup>(External)<sup>
		</a>

		<?PHP
		echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"'>";
		?>
			<span id='vlcIcon'>&#9650;</span> VLC
			Play All<sup>(External)<sup>
		</a>

		<?PHP
		echo "<a class='button' href='/m3u-gen.php?artist=\"$artist/$albumTitle\"&sort=random'>";
		?>
			▶️ Play Random<sup>(External)<sup>
		</a>

		<?PHP
		echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?artist=\"$artist/$albumTitle\"&sort=random'>";
		?>
			<span id='vlcIcon'>&#9650;</span> VLC
			Play Random<sup>(External)<sup>
		</a>
	</div>
</div>

<?php
# build the player for the current track
# - player should automatically loop all tracks and play from the currently chosen track
# ?play=track_number
if (array_key_exists("play",$_GET)){
	$track=($_GET['play']);
	if (file_exists("$track.mp3")){
		echo "<audio class='albumPlayer' controls loop autoplay>";
		echo "	<source src='$track.mp3' type='audio/mpeg'>";
		echo "</audio>";
	}
}
?>

<div class='settingListCard trackListing'>
<h2>Tracks</h2>
<?php
if (file_exists("tracks.index")){
	// get a list of all the genetrated index links for the page
	$sourceFiles = file("tracks.index", FILE_IGNORE_NEW_LINES);
	// reverse the time sort
	$sourceFiles = array_unique($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			// read the index entry
			$data=file_get_contents($sourceFile);
			// write the index entry
			echo "$data";
			flush();
			ob_flush();
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
