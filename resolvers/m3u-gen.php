<!--
########################################################################
# 2web m3u generator to create and cache playlists on-demand
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
<?PHP
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
################################################################################
# force debugging
#$_GET['debug']='true';
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
################################################################################
function m3u_gen($section,$title){
	# Build a m3u playlist file for title in a section of 2web
	#
	# - section can be music,shows, movies
	# - title should be the media title under that section
	# - ?sort=random will shuffle the playlist
	# - ?playat=episodeName.mkv can be used to make a playlist starting at that
	#   specific episode with the generated playlist
	#
	# RETURN FILES
	$rootServerPath = $_SERVER['DOCUMENT_ROOT'];
	$rootPath = $_SERVER['DOCUMENT_ROOT']."/kodi";

	$showTitle = $title;
	$showTitle = str_replace('"','',$showTitle);


	if($section == 'shows'){
		$showPath = "$rootPath/shows/$showTitle/";
	}else if($section == 'artist'){
		# music artist name is lowercased
		$showPath = "$rootPath/music/".strtolower($showTitle)."/";
	}else if($section == 'music'){
		# music artist name is lowercased
		$showPath = "$rootPath/music/".strtolower($showTitle)."/";
	}else if($section == 'movies'){
		$showPath = "$rootPath/movies/";
	}else{
		$showPath = "$rootPath/$section/$showTitle/";
	}

	echo "Checking $showPath is a directory...<br>\n";

	echo "Show path is a directory...<br>\n";
	# create the cache if it does not exist
	if (! is_dir($rootServerPath."/m3u_cache/")){
		mkdir("$rootServerPath/m3u_cache/");
	}

	if (array_key_exists("sort",$_GET)){
		if ($_GET['sort'] == 'random'){
			// cache sum must be randomized for random option, duplicated randmizations will use the cached file
			// - currently 20 variations of the randomization pattern can be created
			$tempRand = rand(0,20);
			$cacheSum = md5("$tempRand".$showTitle.$_SERVER["HTTP_HOST"]);
		}else{
			$cacheSum = md5($showTitle.$_SERVER["HTTP_HOST"]);
		}
	}else{
		if (array_key_exists("playAt",$_GET)){
			# the sum should be unique for each playAt argument
			$cacheSum = md5($_GET['playAt'].$showTitle.$_SERVER["HTTP_HOST"]);
		}else{
			// cache sum
			$cacheSum = md5($showTitle.$_SERVER["HTTP_HOST"]);
		}
	}

	$cacheFile = $rootServerPath."/m3u_cache/".$cacheSum.".m3u";

	$totalFileList=Array();

	// check for existing redirect
	if (is_file($cacheFile)){
		# redirect to the built cache file if it exists
		redirect("/m3u_cache/$cacheSum.m3u");
	}else{
		# ignore user abort of connection
		ignore_user_abort(true);
		# set execution time limit to 15 minutes
		set_time_limit(900);

		// create the m3u file
		$data = fopen($cacheFile,'w');
		fwrite($data, "#EXTM3U\n");

		$foundFiles = recursiveScan($showPath);

		#var_dump($foundFiles);

		foreach ($foundFiles as $filePath){
			# cleanup the scan data by removing the site root path, from the file before adding it to the m3u
			$filePath = str_replace($_SERVER['DOCUMENT_ROOT'],"",$filePath);

			if (strpos($filePath,".avi") || strpos($filePath,".strm") || strpos($filePath,".mkv") || strpos($filePath,".mp4") || strpos($filePath,".m4v") || strpos($filePath,".mpg") || strpos($filePath,".mpeg") || strpos($filePath,".ogv") || strpos($filePath,".mp3") || strpos($filePath,".ogg")){
				#$tempDataEntry = "#EXTINF:-1,$seasonPath - $filePath - $showTitle \n";
				$tempDataEntry = "#EXTINF:-1,$filePath - $showTitle \n";
				#$tempDataEntry = $tempDataEntry."..$filePath\n";
				$tempDataEntry .= "http://".$_SERVER["HTTP_HOST"]."$filePath\n";
				array_push($totalFileList,$tempDataEntry);
			}
		}
	}

	if (array_key_exists("sort",$_GET)){
		if ($_GET['sort'] == 'random'){
			# randomize the list before writing it to the file
			shuffle($totalFileList);
		}
	}
	if (array_key_exists("playAt",$_GET)){
		$playAtFound=False;
		# write playlist lines only after playAt entry is found
		foreach ($totalFileList as $tempLineData){
			if (stripos($tempLineData,$_GET['playAt'])){
				$playAtFound=true;
			}
			if ($playAtFound){
				fwrite($data, $tempLineData);
			}
		}
	}else{
		# write all lines of the playlist
		foreach ($totalFileList as $tempLineData){
			fwrite($data, $tempLineData);
		}
	}
	// close the file
	fclose($data);
	// redirect to episode path
	redirect("/m3u_cache/$cacheSum.m3u");
}
################################################################################
if (array_key_exists("artist",$_GET)){
	echo "Building Artist...<br>\n";

	$rootServerPath = $_SERVER['DOCUMENT_ROOT'];
	$rootPath = $_SERVER['DOCUMENT_ROOT']."/kodi/";

	$showTitle = $_GET['artist'];
	$showTitle = str_replace('"','',$showTitle);

	$showPath = "$rootPath/music/$showTitle";

	#var_dump(recursiveScan($rootPath."music/".strtolower($showTitle)."/"));

	m3u_gen("music",$showTitle);
	exit();

}else if (array_key_exists("showTitle",$_GET)){
	echo "Building ShowTitle...<br>\n";

	$rootServerPath = $_SERVER['DOCUMENT_ROOT'];
	$rootPath = $_SERVER['DOCUMENT_ROOT']."/kodi";

	$showTitle = $_GET['showTitle'];
	//echo "showTitle pre replace=$showTitle<br>\n";
	$showTitle = str_replace('"','',$showTitle);
	//echo "showTitle=$showTitle<br>\n";

	$showPath = "$rootPath/shows/$showTitle";

	m3u_gen("shows",$showTitle);
	exit();
}else if (array_key_exists("movies",$_GET)){
	echo "Building Movies...<br>\n";
	m3u_gen("movies","all");
	exit();
}else{
	// no url was given at all
	echo "<html>";
	echo "<head>";
	echo "<link rel='stylesheet' href='style.css'>";
	echo "</head>";
	echo "<body>";
	echo "<div class='settingListCard'>";
	echo "<h2>Manual Video Cache Interface</h2>";
	echo "No url was specified to the resolver!<br>";
	echo "To Cache a video and play it from here you can use the below form.<br>";
	echo "<form method='get'>";
	echo "	<input class='button' width='60%' type='text' name='url'>";
	echo "	<input class='button' type='submit' value='Cache Url'>";
	echo "	<div>";
	echo "		<span>Enable Debug Output<span>";
	echo "		<input class='button' width='10%' type='checkbox' name='debug'>";
	echo "	</div>";
	echo "</form>";
	echo '</a>';
	echo "</div>";
	echo "<hr>";
	echo "<div class='settingListCard'>";
	echo "	<h2>WEB API EXAMPLES</h2>";
	echo "	<p>";
	echo "		Replace the url api key with your video web link to be cached by youtube-dl.";
	echo "		Debug=true will generate a webpage containing debug data and video output";
	echo "	</p>";
	echo "<ul>";
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/m3u-gen.php?showTitle="showTitle"';
	echo '	</li>';
	echo "</ul>";
	echo "</div>";
	echo "<div class='settingListCard'>";
	echo "<h2>Random Cached Playlists</h2>";
	$sourceFiles = explode("\n",shell_exec("ls -t1 m3u_gen/*.m3u"));
	// reverse the time sort
	# build the video index
	foreach($sourceFiles as $sourceFile){
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				echo "<a class='showPageEpisode' href='".$sourceFile."'>";
				echo "	<h3>".$sourceFile."</h3>";
				echo "</a>";
			}
		}
	}
	echo "</div>";
	//include("header.html");
	echo "</body>";
	echo "</html>";
}
?>
