<html class='seriesBackground'>
<head>
<link rel='stylesheet' href='/style.css' />
<script src='/2web.js'></script>
<style>
<?PHP
	# get the show name
	$workingDirectory=getcwd();
	$workingDirectory=explode('/',$workingDirectory);
	$workingDirectory=array_pop($workingDirectory);
	echo ":root{";
	echo "--backgroundPoster: url('/shows/".$workingDirectory."/poster.png');";
	echo "--backgroundFanart: url('/shows/".$workingDirectory."/fanart.png');";
	echo"}";
?>
</style>
</head>
<body>
<a href='#' id='topButton' class='button'>&uarr;</a>
<?PHP
ini_set('display_errors', 1);
// this file is to be placed in the show directory
// - /shows/$showName/index.php
$activeDir=getcwd();
$showTitle=array_reverse(explode('/',$activeDir))[0];
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class='titleCard'>
<?PHP
echo "<h1>$showTitle</h1>";
?>
<hr>
<div class='listCard'>

<?PHP
echo "<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"'>";
?>
	▶️ Play All<sup>(External)<sup>
</a>

<?PHP
echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?showTitle=\"$showTitle\"'>";
?>
	<span id='vlcIcon'>&#9650;</span> VLC
	Play All<sup>(External)<sup>
</a>

<?PHP
echo "<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"&sort=random'>";
?>
	▶️ Play Random<sup>(External)<sup>
</a>

<?PHP
echo "<a class='button vlcButton' href='vlc://".$_SERVER['HTTP_HOST']."/m3u-gen.php?showTitle=\"$showTitle\"&sort=random'>";
?>
	<span id='vlcIcon'>&#9650;</span> VLC
	Play Random<sup>(External)<sup>
</a>
</div>

<div class='listCard'>
<?PHP
################################################################################
# after processing each season rebuild the show page index entirely
// get a list of all the genetrated index links for the page
//$sourceFiles = explode("\n",shell_exec("ls -1 $activeDir/*/season.index"));
//$seasonDirs= explode("\n",shell_exec("find '$activeDir/' -type 'd' -name 'season.index' | sort"));
$seasonDirs= explode("\n",shell_exec("find '$activeDir/' -type 'd' -name 'Season*' | sort"));
foreach($seasonDirs as $seasonDir){
	if (is_dir($seasonDir)){
		if (is_file($seasonDir."/season.index")){
			$seasonName = str_replace('/season.index','',$seasonDir);
			$seasonName = str_replace($activeDir.'/','',$seasonName);
			echo "	<a href='#$seasonName' class='button'>";
			echo "		📁 $seasonName";
			echo "	</a>";
			flush();
			ob_flush();
		}
	}
}
?>
</div>
<?PHP
echo "<div class='listCard'>";
echo "<a class='button' href='https://www.imdb.com/find?q=$showTitle'>🔎 IMDB</a>";
echo "<a class='button' href='https://en.wikipedia.org/w/?search=$showTitle'>🔎 WIKIPEDIA</a>";
echo "<a class='button' href='https://archive.org/details/movies?query=$showTitle'>🔎 ARCHIVE.ORG</a>";
echo "<a class='button' href='https://www.youtube.com/results?search_query=$showTitle'>🔎 YOUTUBE</a>";
echo "<a class='button' href='https://odysee.com/$/search?q=$showTitle'>🔎 ODYSEE</a>";
echo "<a class='button' href='https://rumble.com/search/video?q=$showTitle'>🔎 RUMBLE</a>";
echo "<a class='button' href='https://www.bitchute.com/search/?kind=video&query=$showTitle'>🔎 BITCHUTE</a>";
echo "<a class='button' href='https://www.twitch.tv/search?term=$showTitle'>🔎 TWITCH</a>";
echo "<a class='button' href='https://veoh.com/find/$showTitle'>🔎 VEOH</a>";
echo "</div>";
?>
<hr>
</div>
<input id='searchBox' class='searchBox' type='text'
 onkeyup='filter("showPageEpisode")' placeholder='Search...' >
<hr>
<div class='episodeList'>
<?PHP
$seasonDirs= explode("\n",shell_exec("find '$activeDir/' -type 'd' -name 'Season*' | sort"));
foreach($seasonDirs as $seasonDir){
	if (is_dir($seasonDir)){
		$seasonName = str_replace($activeDir.'/','',$seasonDir);
		echo "<div class='seasonContainer'>";
		echo "<div class='seasonHeader'>";
		echo "<h2 id='$seasonName'>$seasonName</h2>";
		echo "</div>";
		echo "<hr>";
		// set so script keeps running even if user cancels it
		ignore_user_abort(true);
		$episodeFiles = explode("\n",shell_exec("find '$seasonDir' -type 'f' -name 'episode_*.index' | sort"));
		foreach($episodeFiles as $episodeFile){
			if (is_file($episodeFile)){
				echo file_get_contents($episodeFile);
				flush();
				ob_flush();
			}
		}
		echo "</div>";
	}
}
?>
</div>
<?PHP
	# add footer
	include('../../header.php');
?>
<hr class='topButtonSpace'>
</body>
</html>
