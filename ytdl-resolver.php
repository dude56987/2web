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
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
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
function getQualityConfig(){
	if (file_exists("cacheQuality.cfg")){
		debug("Loading the cacheQuality.cfg file...");
		# load the cache quality config
		# - this will be passed as a quality option to youtube-dl
		$cacheQualityConfig = file_get_contents("cacheQuality.cfg");
		return $cacheQualityConfig;
	}else{
		debug("No cacheQuality.cfg file could be found...");
		return "worst";
	}
}
################################################################################
function getCacheMode(){
	if (file_exists("cacheMode.cfg")){
		debug("Loading the cacheMode.cfg file...");
		# load the cache quality config
		# - this will be passed as a quality option to youtube-dl
		$cacheModeConfig= file_get_contents("cacheMode.cfg");
		return $cacheModeConfig;
	}else{
		debug("No cacheMode.cfg file could be found...");
		return "default";
	}
}
################################################################################
function getUpgradeQualityConfig(){
	if (file_exists("cacheUpgradeQuality.cfg")){
		debug("Loading the cacheUpgradeQuality.cfg file...");
		# load the cache upgrade quality config
		# - this will be passed as a upgrade quality option to youtube-dl
		$cacheUpgradeQualityConfig = file_get_contents("cacheUpgradeQuality.cfg");
		return $cacheUpgradeQualityConfig;
	}else{
		debug("No cacheUpgradeQuality.cfg file could be found...");
		return "worst";
	}
}
################################################################################
function cacheUrl($sum,$videoLink){
	$webDirectory=$_SERVER["DOCUMENT_ROOT"];
	################################################################################
	// if the cache flag has been set to true then download the file and play it from the cache
	debug("Build the command<br>");
	$command = "";
	//$command = '/usr/bin/nohup /usr/bin/sem --retries 10 --jobs 3 --id downloadQueue ';
	//$command = '/usr/bin/sem --retries 10 --jobs 3 --id downloadQueue ';
	//$command = '/usr/bin/sem --retries 10 --jobs 1 --id downloadQueue ';
	//$command = 'echo "/usr/bin/niceload --net ';
	//$command = 'echo "/usr/bin/niceload ';
	//$command = 'echo "';
	//$command = 'echo "';
	// add the download to the cache with the processing queue
	if (file_exists("/usr/local/bin/youtube-dl")){
		debug("PIP version of youtube-dl found<br>");
		$command = $command."/usr/local/bin/youtube-dl";
		//$command = $command." '/usr/local/bin/youtube-dl";
		//$command = "/usr/local/bin/youtube-dl";
	} else {
		$command = $command."youtube-dl";
		//$command = $command." 'youtube-dl";
		//$command = "youtube-dl";
	}
	$quality = getQualityConfig();
	$cacheMode = getCacheMode();
	debug("The web interface set quality is '".$quality."'");
	// max download file size should be 6 gigs, this is a insane file size for a youtube video
	// if a way of detecting livestreams is found this is unnessary
	$command = $command." --max-filesize '6g'";
	$command = $command." --retries 'infinite'";
	$command = $command." --no-mtime";
	$command = $command." --fragment-retries 'infinite'";

	# embed subtitles, continue file downloads, ignore timestamping the file(it messes with caching)
	$command = $command." --continue --write-info-json";
	if ( (array_key_exists("webplayer",$_GET)) AND ($_GET["webplayer"] == "true") ){
		$dlCommand = $command." --all-subs --sub-format srt --embed-subs --no-part";
		$dlCommand = $dlCommand." --write-thumbnail";
	}else{
		$dlCommand = $command." --all-subs --sub-format srt --embed-subs";
		$dlCommand = $dlCommand." --write-thumbnail";
	}
	# check for manually set quality
	if (array_key_exists("res",$_GET)){
		if($_GET["res"] == "HD"){
			$command = $command." -f best";
		} else if ($_GET["res"] == "SD") {
			$command = $command." -f worst";
		}
	} else {
		# if no quality is set in url use server settings
		if (file_exists("cacheUpgradeQuality.cfg")){
			$upgradeQuality = getUpgradeQualityConfig();
			# update the download command
			$dlCommand = $dlCommand." -f '".$upgradeQuality."'";
			$dlCommand = $dlCommand." --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4' -c '".$videoLink."'";
		}else{
			# if no server settings are configured use the default
			# the dl command should simply convert the downloaded m3u file with the m3u file
			if ( (! file_exists("cacheResize.cfg")) AND (! file_exists("cacheFramerate.cfg")) ){
				# if no upgrade quality is set and no hls rescaling or frame dropping then convert the file to mp4 directly
				$dlCommand = "ffmpeg -i '$webDirectory/RESOLVER-CACHE/$sum/video.m3u' '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4'";
			}else{
				# if custom postprocessing is set then the download command should be the same as the input
				# - This is because postprocessing can only decrease the quality, this is to upgrade from those decreases
				$dlCommand = $dlCommand." -f '".$quality."'";
				$dlCommand = $dlCommand." --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4' -c '".$videoLink."'";
			}
		}
		# by default use the option set in the web interface it it exists
		$command = $command." -f '".$quality."'";
	}
	if (file_exists("cacheFramerate.cfg")){
		$cacheFramerate = " -r ".file_get_contents("cacheFramerate.cfg");
	}else{
		//$cacheFramerate = " -r 30";
		$cacheFramerate = "";
	}
	if (file_exists("cacheResize.cfg")){
		$cacheResize = " -s ".file_get_contents("cacheResize.cfg");
	}else{
		//$cacheResize = " -s 1920x1080";
		$cacheResize = "";
	}
	if ( (array_key_exists("webplayer",$_GET)) AND ($_GET["webplayer"] == "true") ){
		$command = $command." -o - -c '".$videoLink."'";
		$command = 'echo "'.$command.'"';
	}else{
		//$command = $command." -o - -c '".$videoLink."' | ffmpeg -i - $cacheFramerate $cacheResize -hls_playlist_type event -start_number 0 -master_pl_name ".$sum.".m3u -hls_time 10 -hls_list_size 0 -f hls 'RESOLVER-CACHE/".$sum."-stream.m3u'";
		$command = $command." -o - -c '".$videoLink."' | ffmpeg -i - $cacheFramerate $cacheResize -hls_playlist_type event -hls_list_size 0 -start_number 0 -master_pl_name ".$sum.".m3u -g 30 -hls_time 10 -f hls '$webDirectory/RESOLVER-CACHE/".$sum."/$sum-stream.m3u'";
		# after the download also transcode the
		# add the higher quality download to happen in the sceduled command after the stream has been transcoded
		//$command = 'echo "'.$command." && ".$dlCommand.'"';
		//
		$command = 'echo "'.$command.'"';
		$dlCommand = 'echo "'.$dlCommand.'"';
	}
	# - Place the higher quality download or the mp4 conversion in a batch queue
	#   that adds a new task once every 60 seconds if system load is below 1.5
	# - This will prevent active downloads from being overwhelmed
	//$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
	//$dlCommand = "/usr/bin/tsp ".$dlCommand;
	# allow setting of batch processing of cached links
	if (array_key_exists("batch",$_GET)){
		if ($_GET["batch"] == "true") {
			$command = $command." | /usr/bin/at -M -q b now";
			$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
			// add command to the end of the queue
			//$command = "/usr/bin/tsp ".$command;
		}else{
			// add command to next available slot in the queue
			$command = $command." | /usr/bin/at -M -q a now";
			$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
			//$command = "/usr/bin/tsp -u ".$command;
		}
	}else{
		// add command to next available slot in the queue
		$command = $command." | /usr/bin/at -M -q a now";
		$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
		//$command = "/usr/bin/tsp -u ".$command;
		//$command = $command." | /usr/bin/at -q b now";
	}
	# write to the log the download start time
	$logFile=fopen("$webDirectory/RESOLVER-CACHE/$sum/data.log", "w");
	$tempTime = strtotime('now');
	fwrite($logFile,date('d/m/y H:i:s',$tempTime)."\n");
	fwrite($logFile,"$webDirectory/RESOLVER-CACHE/".$sum."/bump.mp4\n");
	fwrite($logFile,"COMMAND:\n");
	fwrite($logFile,$command."\n");
	fwrite($logFile,"DL-COMMAND:\n");
	fwrite($logFile,$dlCommand."\n");
	fwrite($logFile,"MD5 Source:\n");
	fwrite($logFile,$videoLink."\n");
	fclose($logFile);
	# fork the process with "at" scheduler command
	runShellCommand($command);
	runShellCommand($dlCommand);
	if (array_key_exists("batch",$_GET)){
		if ($_GET["batch"] == "true") {
			# exit connection after adding batch process to queue
			exit;
		}
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
function buildBump($sum){
	$sum="BASEBUMP";
	################################################################################
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/baseBump.png")){
		# build the base bump image if it does not exist yet, this is the longest part of the process, so cache it
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/convert -size 400x200 plasma:green-black \"$webDirectory/RESOLVER-CACHE/baseBump.png\"";
		################################################################################
		runShellCommand($command);
	}
	################################################################################
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum.png")){
		# create the bump as a thumbnail of the downloading video
		$sourceUrl="$webDirectory/RESOLVER-CACHE/$sum.mp4.part";
		# create thumbnail from downloading video
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		if (file_exists("$webDirectory/RESOLVER-CACHE/$sum.mp4")){
			$command = $command."/usr/bin/ffmpeg -y -ss 1 -i '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4 -vframes 1 $webDirectory/RESOLVER-CACHE/$sum.png";
		}else{
			$command = $command."/usr/bin/ffmpeg -y -ss 1 -i '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4.part -vframes 1 $webDirectory/RESOLVER-CACHE/$sum.png";
		}
		debug("Running command '".$command."'<br>");
		shell_exec($command);
	}
	#if ( ! file_exists("RESOLVER-CACHE/".$sum."-bump.png")){
	#	################################################################################
	#	# compose the webpage over the unique pattern  and overwrite the webpage.png
	#	$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
	#	//$command = "";
	#	$command = $command."/usr/bin/composite -dissolve 70 -gravity center 'RESOLVER-CACHE/".$sum.".png' 'RESOLVER-CACHE/baseBump.png' -alpha Set 'RESOLVER-CACHE/".$sum."-bump.png'";
	#	runShellCommand($command);
	#}
	################################################################################
	# build the loading image if it does not exist yet
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/".$sum.".png")){
		# convert thumbnail into video bump
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/convert '$webDirectory/RESOLVER-CACHE/baseBump.png' -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 2 -style Bold -size 300x100 -gravity center caption:'Loading...' -composite '$webDirectory/RESOLVER-CACHE/".$sum.".png'";
		runShellCommand($command);
	}
	/*
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/".$sum."-bump.mp4")){
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/ffmpeg -loop 1 -i $webDirectory/RESOLVER-CACHE/".$sum."-bump.png -r 1 -t 10 $webDirectory/RESOLVER-CACHE/".$sum."-bump.mp4";
		runShellCommand($command);
	}
 */
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
if (array_key_exists("url",$_GET)){
	$videoLink = $_GET['url'];
	debug("URL is ".$videoLink."<br>");
	# remove parenthesis from video link if they exist
	debug("Cleaning link ".$videoLink."<br>");
	while(strpos($videoLink,'"')){
		debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
		$videoLink = preg_replace('"','',$videoLink);
	}
	while(strpos($videoLink,"'")){
		debug("[DEBUG]: Cleaning link ".$videoLink."<br>");
		$videoLink = preg_replace("'","",$videoLink);
	}
	debug("Cleaning link ".$videoLink."<br>");
	//$videoLink = '"'.$videoLink.'"';
	debug("Cleaning link ".$videoLink."<br>");
	# create the md5sum of the file
	$sum = md5($videoLink);
	debug("[DEBUG]: MD5SUM is ".$sum."<br>");
	# newgrounds will resolve properly with only the link, so resolve but do not cache
	if (strpos($videoLink,"newgrounds.com")){
		# if the link value is already set do NOT override the setting
		if (!array_key_exists("link",$_GET)){
			# set the link value to true
			$_GET['link']=true;
		}
	}
	if (strpos($videoLink,"libsyn.com")){
		if (!array_key_exists("link",$_GET)){
			$_GET['link']=true;
		}
	}
	// check for the cache flag
	if (array_key_exists("link",$_GET)){
		debug("[DEBUG]: linking to video ".$videoLink."<br>");
		################################################################################
		# Get a direct link to the video bypassing the cache.
		# This works on most sites, except youtube.
		################################################################################
		$output = shell_exec('/usr/local/bin/youtube-dl --get-url '.$videoLink);
		if ($output == null){
			// the url was not able to resolve
			//echo "The URL '".$_GET['url']."' was unable to resolve...";
			debug("The URL was unable to resolve...<br>");
		}else{
			// output is the resolved url
			$url = $output;
			if (array_key_exists("debug",$_GET)){
				echo "<hr>";
				echo "<div>".$output."</div>";
				echo "<hr>";
				echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
				exit();
			}else{
				redirect($url);
			}
		}
	}else{
		################################################################################
		$cacheMode = getCacheMode();
		################################################################################
		# By default use the cache for video playback, this works on everything
		################################################################################
		debug("Creating resolver cache<br>");
		#if ( ! file_exists("$webDirectory/RESOLVER-CACHE/")){
		#	mkdir("$webDirectory/RESOLVER-CACHE/");
		#}
		// craft the url to the cache link
		$url = "/RESOLVER-CACHE/$sum/$sum.mp4";
		debug("Checking path ".$url."<br>");
		################################################################################
		#check for the first x segments of the hls playback, ~30 seconds
		if (file_exists("$webDirectory$url")){
			# touch the file to update the mtime and delay cache removal
			touch($url);
			redirect($url);
		}else{
			debug("No file exists in the cache<br>");
			debug("cache is set<br>");
			// create the directory to hold the video files within the resolver cache
			if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum/")){
				mkdir("$webDirectory/RESOLVER-CACHE/$sum/");
			}

			# build a m3u playlist that plays the bump and then the video
			$playlist=fopen("$webDirectory/RESOLVER-CACHE/$sum/master.m3u8", "w");
			# add the file header
			//header("Content-Type: application/mpegurl");
			//header("Content-Disposition: attachment; filename=" . ($sum."_master.m3u"));
			//print "#EXTM3U\n";
			fwrite($playlist,"#EXTM3U\n");
			fwrite($playlist,"#PLAYLIST:$sum\n");
			# write the fileData
			# if running in compatibility mode build the symlinks
			#if( ! file_exists("$webDirectory/RESOLVER-CACHE/".$sum.".ts")){
			# build the m3u file before caching the video it is part of the buffering process
			# build a list of available bumps
			# iterate though the bumps directory and pick a video file randomly
			//$tempFiles = glob("bumps/*-bump.mp4");
			//$bumpFile= $tempFiles[array_rand($tempFiles)];
			//$skipFile= str_replace("-bump.mp4","-skip.mp4",$bumpFile);
			//debug("BumpFile : ".$bumpFile);
			//debug("SkipFile : ".$skipFile);
			# figure out the absolute server path
			$serverPath='http://'.gethostname().'.local/';

			# link the bump file
			//if( ! file_exists("$webDirectory/RESOLVER-CACHE/".$sum."/bump.mp4")){
				//symlink("../$bumpFile",("$webDirectory/RESOLVER-CACHE/".$sum."/bump.mp4"));
			//}
			# link the skip file
			//if( ! file_exists("$webDirectory/RESOLVER-CACHE/".$sum."/skip.mp4")){
				//symlink("../$skipFile",("$webDirectory/RESOLVER-CACHE/".$sum."/skip.mp4"));
			//}
			# write the bumps to the buffer file to allow managed delay for download and playback
			for ($index=1;$index <= 30;$index+=1){
				# write 20 lines of the bump
				fwrite($playlist,"#EXTINF:1,Loading... $index/30\n");
				//fwrite($playlist,$sum."-bump.mp4\n");
				//fwrite($playlist,"$webDirectory/RESOLVER-CACHE/".$sum."-bump.mp4\n");
				//fwrite($playlist,$serverPath."RESOLVER-CACHE/".$sum."/bump.mp4\n");
				fwrite($playlist,$serverPath."RESOLVER-CACHE/$sum/$sum-stream1.ts\n");
				//print "#EXTINF:,Loading... $index/30\n";
				//print "RESOLVER-CACHE/".$sum."-bump.mp4\n";
			}
			//fwrite($playlist,"#EXTINF:-1,\n");
			//fwrite($playlist,"RESOLVER-CACHE/".$sum.".mp4\n");
			fwrite($playlist,"#EXTINF:1,\n");
			fwrite($playlist,$serverPath."RESOLVER-CACHE/$sum/$sum.m3u\n");
			//print "#EXTINF:,\n";
			//print "RESOLVER-CACHE/".$sum.".mp4\n";
			fclose($playlist);
			# cache the url if no log has been created, otherwise jump to the redirect
			if(! file_exists("$webDirectory/RESOLVER-CACHE/$sum/data.log")){
				cacheUrl($sum,$videoLink);
			}
			# the playlist is wrote by the php and cached on the server this code
			# should be able to exit here as it should have wrote the file to the
			# server and sent the created file from RAM the the client, the
			# download has also already been forked
			#
			# wait for either the bump or the file to be downloaded and redirect
			while(true){
				# if 60 seconds of the video has been downloaded then launch the video
				if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4")){
					// file is fully downloaded and converted play instantly
					//header("Content-Type: video/mpeg");
					//header("Content-Disposition: attachment; filename=".$sum.".mp4");
					redirect("RESOLVER-CACHE/$sum/$sum.mp4");
				}else if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/$sum.m3u")){
					# if the stream has x segments (segments start as 0)
					# - currently 10 seconds of video
					# - force loading of 3 segments before resolution
					if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/$sum-stream2.ts")){
						# redirect to the stream
						//header("Content-Type: application/mpegurl");
						//header("Content-Disposition: attachment; filename=".$sum.".m3u");
						redirect("RESOLVER-CACHE/$sum/$sum.m3u");
					}
				}
				//}else if(file_exists("$webDirectory/RESOLVER-CACHE/".$sum."/master.m3u8")){
				//	//header("Content-Type: application/mpegurl");
				//	//header("Content-Disposition: attachment; filename=".$sum.".m3u");
				//	if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/$sum-stream1.ts")){
				//		redirect('RESOLVER-CACHE/'.$sum.'/master.m3u8');
				//	}
				//}
				# if all else fails wait then restart the loop
				sleep(1);
			}
		}
	}
}else{
	// no url was given at all
	echo "<html>";
	echo "<head>";
	echo "<link rel='stylesheet' href='style.css'>";
	echo "</head>";
	echo "<body>";
	//include("header.html");
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
	echo '		http://'.gethostname().'.local/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo "</ul>";
	echo "</div>";
	echo "<div class='settingListCard'>";
	echo "<h2>Random Cached Videos</h2>";
	$sourceFiles = explode("\n",shell_exec("ls -t1 RESOLVER-CACHE/*/*.mp4"));
	// reverse the time sort
	//$sourceFiles = array_reverse($sourceFiles);
	# build the video index
	foreach($sourceFiles as $sourceFile){
		#echo "	<div>File Exists $sourceFile</div>";
		if (file_exists($sourceFile)){
			#echo "	<div>Is File $sourceFile</div>";
			if (is_file($sourceFile)){
				if ( ! strpos($sourceFile,"-bump")){
					if ( ! strpos($sourceFile,"-skip")){
						echo "<a class='showPageEpisode' href='".$sourceFile."'>";
						if (file_exists(str_replace(".mp4",".jpg",$sourceFile))){
							echo "<img loading='lazy' src='".str_replace(".mp4",".jpg",$sourceFile)."' />";
						}else if (file_exists(str_replace(".mp4",".webp",$sourceFile))){
							echo "<img loading='lazy' src='".str_replace(".mp4",".webp",$sourceFile)."' />";
						}else if (file_exists(str_replace(".mp4",".png",$sourceFile))){
							echo "<img loading='lazy' src='".str_replace(".mp4",".png",$sourceFile)."' />";
						}
						echo "	<h3>".$sourceFile."</h3>";
						echo "</a>";
					}
				}
			}
		}
	}
	echo "</div>";
	//include("header.html");
	echo "</body>";
	echo "</html>";
}
?>
