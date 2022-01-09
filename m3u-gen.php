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
if (array_key_exists("showTitle",$_GET)){
	$rootServerPath = $_SERVER['DOCUMENT_ROOT'];
	$rootPath = $_SERVER['DOCUMENT_ROOT']."/kodi/";

	$showTitle = $_GET['showTitle'];
	//echo "showTitle pre replace=$showTitle<br>\n";
	$showTitle = str_replace('"','',$showTitle);
	//echo "showTitle=$showTitle<br>\n";

	$showPath = "$rootPath/shows/$showTitle";

	//echo "showTitle=$showTitle<br>\n";
	//echo "showPath=$showPath<br>\n";

	//echo "Checking '$showPath'<br>\n";
	if (is_dir($showPath)){
		# create the cache if it does not exist
		if (! is_dir($rootServerPath."/m3u_cache/")){
			mkdir("$rootServerPath/m3u_cache/");
		}
		// cache sum
		$cacheSum = md5($showTitle);
		$cacheFile = $rootServerPath."/m3u_cache/".$cacheSum.".m3u";
		// check for existing redirect
		if (is_file($cacheFile)){
			# redirect to the built cache file if it exists
			redirect("/m3u_cache/$cacheSum.m3u");
		}else{
			// create the m3u file
			$data = fopen($cacheFile,'w');
			fwrite($data, "#EXTM3U\n");
			//echo "#EXTM3U<br>\n";
			$seasonPaths = scandir($showPath);
			//print_r($seasonPaths);
			$seasonPaths = array_diff($seasonPaths,array('..','.'));
			foreach ($seasonPaths as &$seasonPath){
				//echo "Checking season path '$seasonPath'<br>\n";//DEBUG
				$fullSeasonPath="$showPath/$seasonPath";
				//echo "Checking full season path '$fullSeasonPath'<br>\n";//DEBUG
				// find directories that are valid season directories
				if (strpos(strtolower($fullSeasonPath),"season")){
					if (is_dir($fullSeasonPath)){
							$episodePaths=scandir($fullSeasonPath);
							//print_r($episodePaths);
							$episodePaths=array_diff($episodePaths,array('..','.'));
							foreach ($episodePaths as &$episodePath){
								$fullEpisodePath="$showPath/$episodePath";
								//echo "Checking episode path '$episodePath'<br>\n";//DEBUG
								//echo "Checking episode path '$fullEpisodePath'<br>\n";//DEBUG
								// if a non genrated file it is a media file
								//if (file_exists($episodePath)){
									if (strpos($episodePath,".avi") || strpos($episodePath,".strm") || strpos($episodePath,".mkv") || strpos($episodePath,".mp4") || strpos($episodePath,".m4v") || strpos($episodePath,".mpg") || strpos($episodePath,".mpeg") || strpos($episodePath,".ogv") || strpos($episodePath,".mp3") || strpos($episodePath,".ogg")){
										//echo "episode is correct type of file...<br>\n";//DEBUG
										//fwrite($data, "http://".$_SERVER['SERVER_ADDR'].":444/kodi/$showTitle/$seasonPath/$episodePath\n");
										//fwrite($data, "http://".gethostname().".local:444/kodi/shows/$showTitle/$seasonPath/$episodePath\n");
										fwrite($data, "#EXTINF:-1,$seasonPath - $episodePath - $showTitle \n");
										fwrite($data, "../kodi/shows/$showTitle/$seasonPath/$episodePath\n");
										//echo ("http://".$_SERVER['SERVER_ADDR'].":444/kodi/$showTitle/$seasonPath/$episodePath\n");
										//echo "../kodi/$showTitle/$seasonPath/$episodePath<br>\n";
									}
								//}
							}
						}
					}
				}
			// close the file
			fclose($data);
			// redirect to episode path
			redirect("/m3u_cache/$cacheSum.m3u");
		}
	}
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
