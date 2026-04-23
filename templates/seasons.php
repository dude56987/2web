<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("nfo2web");
?>
<!--
########################################################################
# 2web series seasons view
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
-->
<html class='seriesBackground'>
<head>
<link rel='stylesheet' href='/style.css' />
<script src='/2webLib.js'></script>
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
// this file is to be placed in the show directory
// - /shows/$showName/index.php
$activeDir=getcwd();
$showTitle=array_reverse(explode('/',$activeDir))[0];
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class='titleCard'>
<?PHP
echo "	<h1>$showTitle</h1>\n";
?>
	<hr>
	<div class='listCard'>
<?PHP
echo "	<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"' onclick='notify(\"🡇\");' download='$showTitle"."_all_episodes.m3u'>\n";
?>
			▶️ Play All<sup>External</sup>
		</a>
<?PHP
echo "	<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"&sort=random' onclick='notify(\"🡇\");' download='$showTitle"."_random_episodes.m3u'>\n";
?>
			🔀 Play Random<sup>External</sup>
		</a>
	</div>
	<div class='listCard'>
<?PHP
################################################################################
# after processing each season rebuild the show page index entirely
// get a list of all the genetrated index links for the page
$seasonDirs= explode("\n",shell_exec("find '$activeDir/' -type 'd' -name 'Season*' | sort"));
$newestSeason="";

echo "		<a href='?all#seasonsTop' class='button'>\n";
echo "			📁 All\n";
echo "		</a>\n";
foreach($seasonDirs as $seasonDir){
	if (is_dir($seasonDir)){
			$seasonName = str_replace($activeDir.'/','',$seasonDir);
			$newestSeason = $seasonName;
			echo "		<a href='?season=$seasonName#$seasonName' class='button'>\n";
			if ($seasonName == "Season 0000"){
				echo "			📁 Specials\n";
			}else{
				echo "			📁 $seasonName\n";
			}
			echo "		</a>\n";
			flush();
			ob_flush();
		#}
	}
}
?>
	</div>
<?PHP
echo "	<div class='titleCard seriesPlot'>\n";
echo "		<h2 class=''>Plot</h2>\n";
if (file_exists($activeDir."/poster.png")){
	echo "		<a href='poster.png'>\n";
	echo "			<img class='right' src='poster-web.png'>\n";
	echo "		</a>\n";
}
echo file_get_contents_tabbed($activeDir."/plot.cfg",1);
echo "	</div>\n";
# check for sources
if (requireGroup("admin",false)){
	if(file_exists("sources.cfg")){
		echo "	<div class='titleCard'>\n";
		echo "		<h2>Media Sources</h2>\n";
		echo "		<pre>".file_get_contents("sources.cfg")."</pre>\n";
		#
		$showScanPath=basename(dirname($_SERVER["SCRIPT_FILENAME"]));
		echo "		<h2>Admin Actions</h2>\n";
		echo "		<div class='listCard'>\n";
		echo "			<form action='/settings/admin.php' method='post'>\n";
		echo "				<input type='text' name='rescanShow' value='$showScanPath' hidden>\n";
		echo "				<button class='button' type='submit'>🗘 Force Media Rescan</button>\n";
		echo "			</form>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}
}
# draw the most recent episodes
# - get the latest season episodes
# - list them in reverse order
$newestEpisodes=array_reverse(file($newestSeason."/season.index"));
echo "<div class='titleCard'>\n";
echo "	<h2>New Episodes</h2>\n";
echo "	<div class='listCard'>\n";
foreach($newestEpisodes as $episodeFile){
	echo file_get_contents_tabbed(trim($episodeFile),1);
}
echo "	</div>\n";
echo "</div>\n";
# draw the search links
drawMoreSearchLinks($showTitle);
?>
<hr>
</div>
<form class='searchBoxForm' action="#seasonsTop" method='get'>
	<input id='seriesSearchBox' class='searchBox' type='text' name='search' placeholder='Series Episode Search...' >
	<button id='searchButton' class='searchButton' type='submit'>🔎</button>
	<a class='searchButton' href='?#seasonsTop'>❌</a>
<?PHP
	# use a random number to force a page refresh
	# - random does not really take a input value
	echo "<a class='searchButton' href='?random=".rand(0,10000)."#seasonsTop'>🎲</a>\n";
?>
</form>
<hr id='seasonsTop'>
<div class='settingListCard'>
<?PHP
if (array_key_exists("search",$_GET)){
	$searchTerm=$_GET['search'];
}
$searchResults=False;

if (array_key_exists("random",$_GET)){
	$episodeFiles=Array();
	foreach($seasonDirs as $seasonDir){
		if (is_readable("$seasonDir/season.index")){
			$tempFileData=file("$seasonDir/season.index",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$episodeFiles = array_merge($episodeFiles,$tempFileData);
		}
	}
	# get 40 random media items
	shuffle($episodeFiles);
	$episodeFiles=array_slice($episodeFiles,0,40);
	echo "<div class='seasonContainer'>\n";
	echo "<div class='seasonHeader'>\n";
	echo "<h2>Random Episodes</h2>\n";
	echo "</div>\n";
	foreach($episodeFiles as $episodeFile){
		$episodeFile=str_replace("\n","",$episodeFile);
		if (strpos($episodeFile,".index")){
			echo file_get_contents_tabbed($episodeFile);
			flush();
			ob_flush();
		}
	}
	echo "</div>\n";
}else{
	foreach($seasonDirs as $seasonDir){
		if (is_dir($seasonDir)){
			if (is_readable("$seasonDir/season.index")){
				$seasonName = str_replace($activeDir.'/','',$seasonDir);
				$firstRun=True;

				// set so script keeps running even if user cancels it
				ignore_user_abort(true);
				#$episodeFiles = scanDir($seasonDir);
				$episodeFiles = file("$seasonDir/season.index");
				# sort the episode names
				sort($episodeFiles);

				$seasonHeader="";
				$seasonHeader.="<div class='seasonContainer'>";
				$seasonHeader.="<div class='seasonHeader'>";
				if ($seasonName == "Season 0000"){
					$seasonHeader.="<h2 id='$seasonName'>Specials</h2>";
				}else{
					$seasonHeader.="<h2 id='$seasonName'>$seasonName</h2>";
				}
				$seasonHeader.="</div>";
				$seasonHeader.="<hr>";
				if (array_key_exists("season",$_GET)){
					if($seasonName == $_GET["season"]){
						foreach($episodeFiles as $episodeFile){
							#
							$episodeFile=str_replace("\n","",$episodeFile);
							if (strpos($episodeFile,".index")){
								if ($firstRun){
									echo $seasonHeader;
									$firstRun=False;
									$searchResults=True;
								}
								#echo file_get_contents($seasonDir."/".basename($episodeFile));
								echo file_get_contents_tabbed($episodeFile);
								flush();
								ob_flush();
							}
						}
					}
					if ($firstRun == False){
						echo "</div>";
					}
				}else if (array_key_exists("search",$_GET)){
					foreach($episodeFiles as $episodeFile){
						if (stripos($episodeFile,".index") !== false){
							$episodeFile=str_replace("\n","",$episodeFile);
							#$tempData=file_get_contents($seasonDir."/".$episodeFile);
							#$tempData=file_get_contents($episodeFile);
							# filter by search term
							if (stripos($episodeFile,$searchTerm) !== false){
								if ($firstRun){
									echo $seasonHeader;
									$firstRun=False;
									$searchResults=True;
								}
								# write the data
								#echo $tempData;
								echo file_get_contents_tabbed($episodeFile);

								flush();
								ob_flush();
							}
						}
					}
				}else{
					foreach($episodeFiles as $episodeFile){
						$episodeFile=str_replace("\n","",$episodeFile);
						if (strpos($episodeFile,".index")){
							if ($firstRun){
								echo $seasonHeader;
								$firstRun=False;
								$searchResults=True;
							}
							#echo file_get_contents($seasonDir."/".basename($episodeFile));
							echo file_get_contents_tabbed($episodeFile);
							flush();
							ob_flush();
						}
					}
				}
				if ($firstRun == False){
					echo "</div>";
				}
			}
		}
	}
}
# if random is not selected
if (! array_key_exists("random",$_GET)){
	# no search results were found
	if ($searchResults == False){
		# no search results found
		echo "<div class='titleCard'>";
		echo "<h2>No search results found for episode search.</h2>";
		echo "<a class='searchButton' href='?#seasonsTop'>Show All Episodes</a>";
		echo "</div>";
	}
}
?>
</div>
<?PHP
	clear();
	echo "<hr class='ruler'>\n";
	loadSearchIndexResults($showTitle,"shows",8,"Shows");
	loadSearchIndexResults($showTitle,"movies");
	drawPosterWidget("shows",true);

	echo "<hr class='ruler'>\n";
	drawMoreSearchLinks($showTitle);

	# add footer
	include($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
