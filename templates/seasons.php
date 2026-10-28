<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("nfo2web");
	########################################################################
	# this file is to be placed in the show directory
	# - /shows/$showName/index.php
	$activeDir=getcwd();
	$showTitle=array_reverse(explode('/',$activeDir))[0];
	#
	if (array_key_exists("autoplay",$_GET)){
		$seasons=scandir($activeDir);
		#echo "<pre>".var_export($seasons,true)."</pre>"."\n";
		#
		$seasons=array_diff($seasons,Array(".",".."));
		#echo "<pre>".var_export($seasons,true)."</pre>"."\n";
		#
		sort($seasons);
		#echo "<pre>".var_export($seasons,true)."</pre>"."\n";
		#
		if (is_readable($seasons[0]."/season.index")){
			$episodes=file( ($seasons[0]."/season.index") , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			sort($episodes);
			#echo "<pre>".var_export($episodes,true)."</pre>"."\n";
			$episodePath=str_replace("/var/cache/2web/web","",$episodes[0]);
			#echo "<pre>".var_export($episodePath,true)."</pre>"."\n";
			$episodePath=str_replace(".index",".php",$episodePath);
			#echo "<pre>".var_export($episodePath,true)."</pre>"."\n";
			if(isset($_GET["loop"])){
				$loopOption="&loop";
			}else{
				$loopOption="";
			}
			$episodePath=$episodePath."?autoplay&play$loopOption";
			#echo "<pre>".var_export($episodePath,true)."</pre>"."\n";
			redirect($episodePath);
		}
	}
	if (array_key_exists("playrandom",$_GET)){
		$seasons=scandir($activeDir);
		#
		$seasons=array_diff($seasons,Array(".",".."));
		#
		sort($seasons);
		#
		if (is_readable($seasons[0]."/season.index")){
			$episodes=file( ($seasons[0]."/season.index") , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			shuffle($episodes);
			$episodePath=str_replace("/var/cache/2web/web","",$episodes[0]);
			$episodePath=str_replace(".index",".php",$episodePath);
			if(isset($_GET["loop"])){
				$loopOption="&loop";
			}else{
				$loopOption="";
			}
			$episodePath=$episodePath."?playrandom&play$loopOption";
			redirect($episodePath);
		}
	}
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
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class='titleCard'>
<?PHP
echo "	<h1>$showTitle</h1>\n";
?>
	<hr>
	<div class='listCard'>
<?PHP
#
echo "<a class='button' href='?autoplay' onclick='notify(\"▶️\")'>▶️ Play All</a>\n";
echo "<a class='button' href='?playrandom' onclick='notify(\"🔀\")'>🔀 Play Random</a>\n";
#
echo "<a class='button' href='?autoplay&loop' onclick='notify(\"▶️\")'>▶️ Loop All</a>\n";
echo "<a class='button' href='?playrandom&loop' onclick='notify(\"🔀\")'>🔀 Loop Random</a>\n";
#
echo "<a class='button' href='?random=".rand(0,10000)."#seasonsTop' onclick='notify(\"🎲\",\"60000\",\"spinRight\")'>🎲 Random Episodes</a>\n";
#
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
if (is_readable($activeDir."/plot.cfg")){
	echo "	<div class='titleCard seriesPlot'>\n";
	echo "		<h2 class=''>Plot</h2>\n";
	if (file_exists($activeDir."/poster.png")){
		echo "		<a href='poster.png'>\n";
		echo "			<img loading='lazy' class='right' src='poster-web.png'>\n";
		echo "		</a>\n";
	}
	echo file_get_contents_tabbed($activeDir."/plot.cfg",1);
	#echo "Local Collection of videos sorted by Year.";
	echo "	</div>\n";
}else{
	if (file_exists($activeDir."/poster.png")){
		echo "		<a href='poster.png'>\n";
		echo "			<img loading='lazy' class='' src='poster-web.png'>\n";
		echo "		</a>\n";
	}
}
if (requireGroup("admin",false)){
	# display the admin tools if sources are available
	if(file_exists("sources.cfg")){
		echo "	<div class='titleCard'>\n";
		echo "		<h2>Media Sources</h2>\n";
		echo "		<pre>".file_get_contents("sources.cfg")."</pre>\n";
		#
		$showScanPath=basename(dirname($_SERVER["SCRIPT_FILENAME"]));
		# check for the videos section
		if (stripos($_SERVER["SCRIPT_FILENAME"],"/videos/") !== false){
			$mediaRescanType="rescanVideos";
		}else{
			$mediaRescanType="rescanShow";
		}
		echo "		<h2>Admin Actions</h2>\n";
		echo "		<div class='listCard'>\n";
		# remove existing data and rescan this show
		echo "			<form action='/settings/admin.php' method='post'>\n";
		echo "				<input type='text' name='$mediaRescanType' value='$showScanPath' hidden>\n";
		echo "				<button class='button' type='submit'>🗘 Force Media Rescan</button>\n";
		echo "			</form>\n";
		if (is_readable($activeDir."/plot.cfg")){
			echo "			<form action='/settings/admin.php' method='post'>\n";
			echo "				<input type='text' name='showTitle' value='$showScanPath' hidden>\n";
			# enable or disable a single show
			if ( yesNoCfgCheck("/var/cache/2web/web/shows/$showScanPath/forceCache.cfg","no") ){
				echo "				<input type='text' name='forceCacheNewEpisodesForShow' value='no' hidden>\n";
				echo "				<button class='button' type='submit'>🟢 Disable Auto Cache of New Episodes</button>\n";
			}else{
				echo "				<input type='text' name='forceCacheNewEpisodesForShow' value='yes' hidden>\n";
				echo "				<button class='button' type='submit'>◯ Enable Auto Cache of New Episodes</button>\n";
			}
			echo "			</form>\n";
		}
		echo "		</div>\n";
		echo "	</div>\n";
	}
}
# draw the most recent episodes
# - get the latest season episodes
# - list them in reverse order
if(is_readable("marathon.index")){
	#
	$newEpisodesCacheSum=md5($showTitle);
	#
	$newEpisodeCachePath="/var/cache/2web/web/web_cache/new_episodes_$newEpisodesCacheSum.cfg";
	# rebuild the new episodes list once every day
	if(cacheCheck($newEpisodeCachePath,1)){
		# cache the new file
		$newestEpisodes=array_reverse(file("marathon.index",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
		$newEpisodeIndexData="";
		$totalNewEpisodes=0;
		# get the new episode index files
		foreach($newestEpisodes as $episodeFile){
			# make sure the path is readable
			if(is_readable($episodeFile)){
				# check if the episode is less than 30 days old in the web interface
				if (time()-filemtime($episodeFile) < ((60*60)*24)*30){
					$newEpisodeIndexData.=file_get_contents_tabbed(trim($episodeFile),1);
					$totalNewEpisodes+=1;
				}
			}
			# limit output to 40 episodes
			if($totalNewEpisodes>=40){
				break;
			}
		}
		# add the more link to the new episodes data
		# - use ?season to load only that single season this prevents this button from being non-working
		#   until the entire page has completely loaded.
		$newEpisodeIndexData.="<a class='indexSeries' href='?season=$newestSeason#$newestSeason'>\n";
		$newEpisodeIndexData.="	<h2 class='moreEpisodesLinkIcon'>+</h2>\n";
		$newEpisodeIndexData.="	More\n";
		$newEpisodeIndexData.="</a>\n";
		# check if any episodes were found and list them
		if($totalNewEpisodes>0){
			$newEpisodeCacheData="<div class='titleCard'>\n";
			$newEpisodeCacheData.="	<h2 title='New Episodes ordered by Newest to Oldest'>New Episodes</sup></h2>\n";
			$newEpisodeCacheData.="	<div class='listCard'>\n";
			$newEpisodeCacheData.="		$newEpisodeIndexData";
			$newEpisodeCacheData.="	</div>\n";
			$newEpisodeCacheData.="</div>\n";
		}else{
			# cache has no data but create file anyway to prevent excess rescans
			$newEpisodeCacheData="";
		}
		file_put_contents($newEpisodeCachePath,$newEpisodeCacheData);
		echo "$newEpisodeCacheData";
	}else{
		# load the cached data
		echo file_get_contents($newEpisodeCachePath);
	}
}
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
	echo "<a class='searchButton' href='?random=".rand(0,10000)."#seasonsTop' onclick='notify(\"🎲\",\"60000\",\"spinRight\")'>🎲</a>\n";
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
	# show random spinner
	echo "<script>"."\n";
	echo "	notify(\"🎲\",\"2000\",\"spinRight\")"."\n";
	echo "</script>"."\n";
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
				$episodeFiles = file("$seasonDir/season.index",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
