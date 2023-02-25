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
################################################################################
function debug($message){
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: ".$message."<br>";
		ob_flush();
		flush();
		return true;
	}else{
		return false;
	}
}
################################################################################
function runExternalProc($command){
	$client= new GearmanClient();
	$client->addServer();
	$client->addFunction();
}
################################################################################
function runShellCommand($command){
	if (array_key_exists("debug",$_GET)){
		//echo 'Running command %echo "'.$command.'" | at now<br>';
		echo 'Running command %'.$command.'<br>';
	}
	################################################################################
	//exec($command);
	//$output=shell_exec('echo "'.$command.'" | at now >> RESOLVER-CACHE/resolver.log');
	$output=shell_exec($command);
	debug("OUTPUT=".$output."<br>");
}
################################################################################
function redirect($url){
	if (array_key_exists("debug",$_GET)){
		echo "<hr>";
		echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
		echo '<div>';
		echo '<video controls>';
		echo '<source src="'.$url.'" type="video/mp4">';
		echo '</video>';
		echo '</div>';
		echo "<hr>";
		ob_flush();
		flush();
		exit();
		die();
	}else{
		// temporary redirect
		header('Location: '.$url,true,302);
		exit();
		die();
	}
}
################################################################################
function recursiveScan($directoryPath){
	# scan the directory
	$foundPaths = scandir($directoryPath);
	# remove the up and current paths
	$foundPaths = array_diff($foundPaths,Array('..','.'));
	$finalFoundLinks = Array();
	# for each found directory list
	foreach( $foundPaths as $foundPath){
		# create the full directory path
		$fullDirPath = $directoryPath.$foundPath."/";

		# try to fix the full found path
		if (is_dir($fullDirPath)){
			$fullFoundPath = $fullDirPath;
		}else{
			$fullFoundPath = $directoryPath.$foundPath;
		}

		# Check if recursion is needed to find search directory
		if (is_dir($fullFoundPath)){
			# add the results of the recursive scan to the output
			$finalFoundLinks = array_merge($finalFoundLinks, recursiveScan($fullFoundPath));
		}else{
			# if a file add the file to the return value
			$finalFoundLinks = array_merge($finalFoundLinks, Array($fullFoundPath));
		}
	}
	return $finalFoundLinks;
}
################################################################################
function m3u_gen($section,$title){
	# - section can be music,shows
	# - title can be a artist
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
	}else{
		$showPath = "$rootPath/$section/$showTitle/";
	}

	echo "Checking $showPath is a directory...<br>\n";

	//var_dump(recursiveScan($showPath));

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
			$cacheSum = md5("$tempRand".$showTitle);
		}else{
			$cacheSum = md5($showTitle);
		}
	}else{
		if (array_key_exists("playAt",$_GET)){
			# the sum should be unique for each playAt argument
			$cacheSum = md5($_GET['playAt'].$showTitle);
		}else{
			// cache sum
			$cacheSum = md5($showTitle);
		}
	}

	$cacheFile = $rootServerPath."/m3u_cache/".$cacheSum.".m3u";

	$totalFileList=Array();

	// check for existing redirect
	if (is_file($cacheFile)){
		# redirect to the built cache file if it exists
		redirect("/m3u_cache/$cacheSum.m3u");
	}else{
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
				$tempDataEntry = $tempDataEntry."..$filePath\n";
				array_push($totalFileList,$tempDataEntry);
			}
		}
	}

	#var_dump($totalFileList);
	# check for playAt and concat array
	#if (array_key_exists("playAt",$_GET)){
	#	# if the playAt is set start the playlist at this discovered file cut the file paths array at that filename
	#	$searchResults=array_search($_GET["playAt"], array_keys($totalFileList));
	#	var_dump($searchResults);
	#	if($searchResults != false){
	#		$totalFileList = array_slice($totalFileList,$searchResults);
	#	}
	#}

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
	echo '		http://'.gethostname().'.local:444/m3u-gen.php?showTitle="showTitle"';
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
