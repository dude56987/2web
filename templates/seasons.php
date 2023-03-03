<!--
########################################################################
# 2web series seasons view
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
echo "<h1>";
echo "$showTitle";
echo "<img id='spinner' src='/spinner.gif' />";
echo "</h1>";
?>
<hr>
<div class='listCard'>

<?PHP
echo "<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"'>";
?>
	▶️ Play All<sup>(External)<sup>
</a>

<?PHP
echo "<a class='button vlcButton' href='vlc://".$_SERVER['SERVER_ADDR']."/m3u-gen.php?showTitle=\"$showTitle\"'>";
?>
	▶️ Play All<sup>(<span id='vlcIcon'>&#9650;</span>VLC)<sup>
</a>

<?PHP
echo "<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"&sort=random'>";
?>
	🔀 Play Random<sup>(External)<sup>
</a>

<?PHP
echo "<a class='button vlcButton' href='vlc://".$_SERVER['SERVER_ADDR']."/m3u-gen.php?showTitle=\"$showTitle\"&sort=random'>";
?>
	🔀 Play Random<sup>(<span id='vlcIcon'>&#9650;</span>VLC)<sup>
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
			if ($seasonName == "Season 0000"){
				echo "		📁 Specials";
			}else{
				echo "		📁 $seasonName";
			}
			echo "	</a>";
			flush();
			ob_flush();
		}
	}
}
?>
</div>
<?PHP
echo "<div class='titleCard seriesPlot'>";
echo "<h2 class=''>";
echo "Plot";
echo "</h2>";
if (file_exists($activeDir."/poster.png")){
	echo "<a href='poster.png'>";
	echo "<img class='right' src='poster-web.png'>";
	echo "</a>";
}
echo file_get_contents($activeDir."/plot.cfg");
echo "</div>";

echo "<div class='titleCard'>";
echo "<h2 class=''>";
echo "External Info";
echo "</h2>";
echo "<div class='listCard'>";
echo "<a class='button' target='_new' href='https://www.imdb.com/find?q=$showTitle'>🔎 IMDB</a>";
echo "<a class='button' target='_new' href='https://en.wikipedia.org/w/?search=$showTitle'>🔎 WIKIPEDIA</a>";
echo "<a class='button' target='_new' href='https://archive.org/details/movies?query=$showTitle'>🔎 ARCHIVE.ORG</a>";
echo "<a class='button' target='_new' href='https://www.youtube.com/results?search_query=$showTitle'>🔎 YOUTUBE</a>";
echo "<a class='button' target='_new' href='https://odysee.com/$/search?q=$showTitle'>🔎 ODYSEE</a>";
echo "<a class='button' target='_new' href='https://rumble.com/search/video?q=$showTitle'>🔎 RUMBLE</a>";
echo "<a class='button' target='_new' href='https://www.bitchute.com/search/?kind=video&query=$showTitle'>🔎 BITCHUTE</a>";
echo "<a class='button' target='_new' href='https://www.twitch.tv/search?term=$showTitle'>🔎 TWITCH</a>";
echo "<a class='button' target='_new' href='https://veoh.com/find/$showTitle'>🔎 VEOH</a>";
echo "</div>";
echo "</div>";
?>
<hr>
</div>
<!--
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("showPageEpisode")' placeholder='Search...' >
-->
<form class='searchBoxForm' action="#seriesSearchBox" method='get'>
	<input id='seriesSearchBox' class='searchBox' type='text' name='search' placeholder='Series Episode Search...' >
	<button id='searchButton' class='searchButton' type='submit'>🔎</button>
	<a class='searchButton' href='?#seriesSearchBox'>❌</a>
</form>
<hr>
<div class='episodeList'>
<?PHP
if (array_key_exists("search",$_GET)){
	$searchTerm=$_GET['search'];
}
$seasonDirs= explode("\n",shell_exec("find '$activeDir/' -type 'd' -name 'Season*' | sort"));
foreach($seasonDirs as $seasonDir){
	if (is_dir($seasonDir)){
		$seasonName = str_replace($activeDir.'/','',$seasonDir);
		echo "<div class='seasonContainer'>";
		echo "<div class='seasonHeader'>";
		if ($seasonName == "Season 0000"){
			echo "<h2 id='$seasonName'>Specials</h2>";
		}else{
			echo "<h2 id='$seasonName'>$seasonName</h2>";
		}
		echo "</div>";
		echo "<hr>";
		// set so script keeps running even if user cancels it
		ignore_user_abort(true);
		$episodeFiles = explode("\n",shell_exec("find '$seasonDir' -type 'f' -name 'episode_*.index' | sort"));

		if (array_key_exists("search",$_GET)){
			foreach($episodeFiles as $episodeFile){
				if (is_file($episodeFile)){
					$tempData=file_get_contents($episodeFile);
					# filter by search term
					if (stripos($tempData,$searchTerm)){
						# write the data
						echo $tempData;
						flush();
						ob_flush();
					}
				}
			}
		}else{
			foreach($episodeFiles as $episodeFile){
				if (is_file($episodeFile)){
					echo file_get_contents($episodeFile);
					flush();
					ob_flush();
				}
			}
		}
		echo "</div>";
	}
}
?>
</div>
<?PHP
	# add footer
	include($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
