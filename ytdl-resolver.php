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
		echo "[DEBUG]: ".$message;
		ob_flush();
		flush();
		return true;
	}else{
		return false;
	}
}
################################################################################
function cacheUrl($sum,$videoLink){
	################################################################################
	// if the cache flag has been set to true then download the file and play it from the cache
	debug("Build the command<br>");
	//$command = '/usr/bin/nohup /usr/bin/sem --retries 10 --jobs 3 --id downloadQueue ';
	//$command = '/usr/bin/sem --retries 10 --jobs 3 --id downloadQueue ';
	$command = 'echo "';
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
	# embed subtitles, continue file downloads, ignore timestamping the file(it messes with caching)
	//$command = $command." --continue --embed-subs --no-mtime --no-part ";
	$command = $command." --continue --write-info-json --all-subs";
	$command = $command."	--sub-format srt --embed-subs --no-mtime ";
	# TODO: add .srt subtitle dowloads
	if (array_key_exists("res",$_GET)){
		if($_GET["res"] == "HD"){
			$command = $command." -f best --recode-video mp4 ";
		} else if ($_GET["res"] == "SD") {
			$command = $command." -f worst --recode-video mp4 ";
		}
	} else {
		# default option in youtube-dl is SD
		$command = $command." -f worst --recode-video mp4 ";
	}
	# complete the command with the paths
	$command = $command."-o 'RESOLVER-CACHE/".$sum.".mp4' -c '".$videoLink."'";
	//$command = $command." | at -q b now";
	//$command = $command." && ln -sf '".$sum.".mp4' 'RESOLVER-CACHE/".$sum."-bump.mp4'\" ";
	$command = $command." && ln -sf 'BASEBUMP-skip.mp4' 'RESOLVER-CACHE/".$sum."-bump.mp4'\" ";
	# allow setting of batch processing of cached links
	if (array_key_exists("batch",$_GET)){
		if ($_GET["batch"] == "true") {
			$command = $command."| /usr/bin/at -q b now";
		}else{
			$command = $command."| /usr/bin/at now";
		}
	}else{
		$command = $command."| /usr/bin/at now";
	}
	# add end quote to sem command
	//$command = $command.'"';
	# launch the command to start downloading to the cache
	//debug("Launch the command '".$command."'<br>");
	# ignore aborting the connection this should run until it finishes
	//ignore_user_abort(true);
	# launch the parallel process
	//popen($command);
	# fork the process with "at" scheduler command
	runShellCommand($command);
	if ($_GET["batch"] == "true") {
		# exit connection after adding batch process to queue
		exit;
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
	if ( ! file_exists("RESOLVER-CACHE/baseBump.png")){
		# build the base bump image if it does not exist yet, this is the longest part of the process, so cache it
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --fg --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/convert -size 400x200 plasma:green-black \"RESOLVER-CACHE/baseBump.png\"";
		################################################################################
		runShellCommand($command);
	}
	################################################################################
	if ( ! file_exists("RESOLVER-CACHE/".$sum.".png")){
		# create the bump as a thumbnail of the downloading video
		$sourceUrl="RESOLVER-CACHE/".$sum.".mp4.part";
		# create thumbnail from downloading video
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		if (file_exists("RESOLVER-CACHE/".$sum.".mp4")){
			$command = $command."/usr/bin/ffmpeg -y -ss 1 -i 'RESOLVER-CACHE/".$sum.".mp4 -vframes 1 RESOLVER-CACHE/".$sum.".png";
		}else{
			$command = $command."/usr/bin/ffmpeg -y -ss 1 -i 'RESOLVER-CACHE/".$sum.".mp4.part -vframes 1 RESOLVER-CACHE/".$sum.".png";
		}
		//debug("[DEBUG]: running command '".$command."'<br>");
		shell_exec($command);
	}
	if ( ! file_exists("RESOLVER-CACHE/".$sum."-bump.png")){
		################################################################################
		# compose the webpage over the unique pattern  and overwrite the webpage.png
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/composite -dissolve 70 -gravity center 'RESOLVER-CACHE/".$sum.".png' 'RESOLVER-CACHE/baseBump.png' -alpha Set 'RESOLVER-CACHE/".$sum."-bump.png'";
		runShellCommand($command);
	}
	################################################################################
	# build the loading image if it does not exist yet
	if ( ! file_exists("RESOLVER-CACHE/".$sum.".png")){
		# convert thumbnail into video bump
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/convert 'RESOLVER-CACHE/baseBump.png' -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 2 -style Bold -size 300x100 -gravity center caption:'Loading...' -composite 'RESOLVER-CACHE/".$sum.".png'";
		runShellCommand($command);
	}
	if ( ! file_exists("RESOLVER-CACHE/".$sum."-bump.mp4")){
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/ffmpeg -loop 1 -i RESOLVER-CACHE/".$sum."-bump.png -r 1 -t 10 RESOLVER-CACHE/".$sum."-bump.mp4";
		runShellCommand($command);
	}
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
	}else{
		header('Location: '.$url);
		exit();
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
		# By default use the cache for video playback, this works on everything
		################################################################################
		debug("Creating resolver cache<br>");
		if ( ! file_exists("RESOLVER-CACHE/")){
			mkdir("RESOLVER-CACHE/");
		}
		// craft the url to the cache link
		$url = "RESOLVER-CACHE/".$sum.".mp4";
		debug("Checking path ".$url."<br>");
		################################################################################
		if (file_exists($url)){
			# touch the file to update the mtime and delay cache removal
			touch($url);
			redirect($url);
		} else {
			debug("No file exists in the cache<br>");
			debug("cache is set<br>");
			if( ! file_exists("RESOLVER-CACHE/".$sum.".mp4.part")){
				# build the m3u file before caching the video it is part of the buffering process
				if(! file_exists("RESOLVER-CACHE/".$sum.".m3u")){
					# link to the default bump to the bump that will be removed when download is finished
					symlink("BASEBUMP-bump.mp4",("RESOLVER-CACHE/".$sum."-bump.mp4"));
					# build a m3u playlist that plays the bump and then the video
					$playlist=fopen("RESOLVER-CACHE/".$sum.".m3u", "w");
					# write the fileData
					for ($index=0;$index < 30;$index+=1){
						# write 20 lines of the bump
						fwrite($playlist,"RESOLVER-CACHE/".$sum."-bump.mp4\n");
					}
					fwrite($playlist,"RESOLVER-CACHE/".$sum.".mp4\n");
					fclose($playlist);
				}
				# if no part file exists the caching command has not yet started add it to the queue
				cacheUrl($sum,$videoLink);
				//buildBump($sum);
				# sleep one second after forking the process
				sleep(2);
			}
			//}else{
			//	# resolve to the bump if the .part file exists
			//	header('Location: RESOLVER-CACHE/BASEBUMP-bump.mp4');
			//	exit();
			//}
			# wait for either the bump or the file to be downloaded and redirect
			while(true){
				sleep(2);
				if(file_exists("RESOLVER-CACHE/".$sum.".mp4")){
					//redirect('http://'.gethostname().'.local:444/RESOLVER-CACHE/'.$sum.'.mp4');
					redirect('RESOLVER-CACHE/'.$sum.'.mp4');
					//header('Location: http://'.gethostname().'.local:444/RESOLVER-CACHE/'.$sum.'.mp4');
					//exit();
				//}else if(file_exists("RESOLVER-CACHE/".$sum."-bump.mp4")){
				}else if(file_exists("RESOLVER-CACHE/BASEBUMP-bump.mp4")){
					//header('Location: http://'.gethostname().'.local:444/RESOLVER-CACHE/'.$sum.'-bump.mp4');
					//header('Location: http://'.gethostname().'.local:444/RESOLVER-CACHE/BASEBUMP-bump.mp4');
					//header('Location: RESOLVER-CACHE/BASEBUMP-bump.mp4');
					//exit();
					# redirect to the playlist
					redirect('RESOLVER-CACHE/'.$sum.'.m3u');
					//redirect('RESOLVER-CACHE/BASEBUMP-bump.mp4');
				}
			}
		}
	}
}else{
	// no url was given at all
	echo "No url was specified to the resolver!<br>";
	echo "Please give a valid URL to a video to be resolved.<br>";
	echo "<form method='get'>";
	echo "<input width='60%' type='text' name='url'>";
	echo "<input width='10%' type='checkbox' name='debug'>";
	echo "<input type='submit'>";
	echo "</form>";
	echo '</a>';
	echo "<hr>";
	echo "<h2>EXAMPLES</h2>";
	echo "<ul>";
	echo '	<li>';
	echo '		http://'.gethostname().'.local:444/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local:444/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local:444/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().':444/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().':444/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().':444/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo "</ul>";
}
?>
