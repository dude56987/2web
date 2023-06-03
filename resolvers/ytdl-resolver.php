<!--
########################################################################
# 2web resolver for caching and playback of video links using yt-dlp
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
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
################################################################################
function getQualityConfig($webDirectory){
	if (file_exists("/etc/2web/cache/cacheQuality.cfg")){
		debug("Loading the cacheQuality.cfg file...");
		# load the cache quality config
		# - this will be passed as a quality option to youtube-dl
		$cacheQualityConfig = file_get_contents("/etc/2web/cache/cacheQuality.cfg");
		return $cacheQualityConfig;
	}else{
		debug("No cacheQuality.cfg file could be found...");
		return "worst";
	}
}
################################################################################
function getCacheMode($webDirectory){
	if (file_exists("/etc/2web/cache/cacheMode.cfg")){
		debug("Loading the cacheMode.cfg file...");
		# load the cache quality config
		# - this will be passed as a quality option to youtube-dl
		$cacheModeConfig= file_get_contents("/etc/2web/cache/cacheMode.cfg");
		return $cacheModeConfig;
	}else{
		debug("No cacheMode.cfg file could be found...");
		return "default";
	}
}
################################################################################
function getUpgradeQualityConfig($webDirectory){
	if (file_exists("/etc/2web/cache/cacheUpgradeQuality.cfg")){
		debug("Loading the cacheUpgradeQuality.cfg file...");
		# load the cache upgrade quality config
		# - this will be passed as a upgrade quality option to youtube-dl
		$cacheUpgradeQualityConfig = file_get_contents("/etc/2web/cache/cacheUpgradeQuality.cfg");
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
	$command = "nice -n -5 ";
	// add the download to the cache with the processing queue
	if (file_exists("/usr/local/bin/yt-dlp")){
		debug("yt-dlp found<br>");
		# add the sponsorblock video bookmarks to the video file when using yt-dlp
		$command = $command."/usr/local/bin/yt-dlp --abort-on-error --sponsorblock-mark all ";
	}else if (file_exists("/usr/local/bin/youtube-dl")){
		debug("PIP version of youtube-dl found<br>");
		$command = $command."/usr/local/bin/youtube-dl";
	} else {
		$command = $command."youtube-dl";
	}
	$quality = getQualityConfig($webDirectory);
	$cacheMode = getCacheMode($webDirectory);
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
		if (file_exists("/etc/2web/cache/cacheUpgradeQuality.cfg")){
			$upgradeQuality = getUpgradeQualityConfig($webDirectory);
			# update the download command
			if (($upgradeQuality == "best") or ($upgradeQuality == "worst")){
				$dlCommand = $dlCommand." -f '".$upgradeQuality."'";
			}else{
				$dlCommand = $dlCommand." -S '".$upgradeQuality."'";
			}
			$dlCommand = $dlCommand." --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4' -c '".$videoLink."'";
		}else{
			# if no server settings are configured use the default
			# the dl command should simply convert the downloaded m3u file with the m3u file
			if ( (! file_exists("/etc/2web/cache/cacheResize.cfg")) AND (! file_exists("/etc/2web/cache/cacheFramerate.cfg")) ){
				# if no upgrade quality is set and no hls rescaling or frame dropping then convert the file to mp4 directly
				$dlCommand = "ffmpeg -i '$webDirectory/RESOLVER-CACHE/$sum/video.m3u' '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4'";
			}else{
				# if custom postprocessing is set then the download command should be the same as the input
				# - This is because postprocessing can only decrease the quality, this is to upgrade from those decreases
				if (($quality == "best") or ($quality == "worst")){
					$dlCommand = $dlCommand." -f '".$quality."'";
				}else{
					$dlCommand = $dlCommand." -S '".$quality."'";
				}
				$dlCommand = $dlCommand." --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/$sum.mp4' -c '".$videoLink."'";
			}
		}
		# by default use the option set in the web interface it it exists
		if (($quality == "best") or ($quality == "worst")){
			$command = $command." -f '".$quality."'";
		}else{
			$command = $command." -S '".$quality."'";
		}
	}
	if (file_exists("/etc/2web/cache/cacheFramerate.cfg")){
		$cacheFramerate = " -r ".file_get_contents("/etc/2web/cache/cacheFramerate.cfg");
	}else{
		//$cacheFramerate = " -r 30";
		$cacheFramerate = "";
	}
	if (file_exists("/etc/2web/cache/cacheResize.cfg")){
		$cacheResize = " -s ".file_get_contents("/etc/2web/cache/cacheResize.cfg");
	}else{
		//$cacheResize = " -s 1920x1080";
		$cacheResize = "";
	}
	if ( (array_key_exists("webplayer",$_GET)) AND ($_GET["webplayer"] == "true") ){
		$command = $command." -o - -c '".$videoLink."'";
		$command = 'echo "'.$command.'"';
	}else{
		$command = $command." -o - -c '".$videoLink."' | ffmpeg -i - $cacheFramerate $cacheResize -hls_playlist_type event -hls_list_size 0 -start_number 0 -master_pl_name ".$sum.".m3u -g 30 -hls_time 10 -f hls '$webDirectory/RESOLVER-CACHE/".$sum."/$sum-stream.m3u'";
		# after the download also transcode the
		# add the higher quality download to happen in the sceduled command after the stream has been transcoded
		$command = 'echo "'.$command.'"';
		$dlCommand = 'echo "'.$dlCommand.'"';
	}
	# - Place the higher quality download or the mp4 conversion in a batch queue
	#   that adds a new task once every 60 seconds if system load is below 1.5
	# - This will prevent active downloads from being overwhelmed
	# allow setting of batch processing of cached links
	if (array_key_exists("batch",$_GET)){
		if ($_GET["batch"] == "true") {
			$command = $command." | /usr/bin/at -M -q b now";
			$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
		}else{
			// add command to next available slot in the queue
			$command = $command." | /usr/bin/at -M -q a now";
			$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
		}
	}else{
		// add command to next available slot in the queue
		$command = $command." | /usr/bin/at -M -q a now";
		$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
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
	################################################################################
	# build the loading image if it does not exist yet
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/".$sum.".png")){
		# convert thumbnail into video bump
		$command = '/usr/bin/nohup /usr/bin/sem --keep-order --roundrobin --retries 0 --jobs 1 --id thumbQueue ';
		//$command = "";
		$command = $command."/usr/bin/convert '$webDirectory/RESOLVER-CACHE/baseBump.png' -background none -font 'OpenDyslexic-Bold' -fill white -stroke black -strokewidth 2 -style Bold -size 300x100 -gravity center caption:'Loading...' -composite '$webDirectory/RESOLVER-CACHE/".$sum.".png'";
		runShellCommand($command);
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
	# create the sum of the file
	#$sum = md5($videoLink);
	$sum = hash("sha512",$videoLink,false);
	debug("[DEBUG]: SUM is ".$sum."<br>");
	# libsyn will resolve properly with only the link, so resolve but do not cache
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
				echo '<p>ResolvedUrl = <a href="/'.$url.'">'.$url.'</a></p>';
				exit();
			}else{
				redirect("/".$url);
			}
		}
	}else{
		################################################################################
		$cacheMode = getCacheMode($webDirectory);
		################################################################################
		# By default use the cache for video playback, this works on everything
		################################################################################
		debug("Creating resolver cache<br>");
		// craft the url to the cache link
		$storagePath = "$webDirectory/RESOLVER-CACHE/$sum/";
		$url = "/RESOLVER-CACHE/$sum/$sum.mp4";
		debug("Checking path ".$url."<br>");
		################################################################################
		#check for the first x segments of the hls playback, ~30 seconds
		if (file_exists("$webDirectory$url")){
			# touch the file to update the mtime and delay cache removal
			touch($url);
			touch($storagePath);
			redirect($url);
		}else{
			# ignore user abort of connection
			ignore_user_abort(true);
			# set execution time limit to 15 minutes
			set_time_limit(900);

			debug("No file exists in the cache<br>");
			debug("cache is set<br>");
			// create the directory to hold the video files within the resolver cache
			if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum/")){
				mkdir("$webDirectory/RESOLVER-CACHE/$sum/");
			}

			# build a m3u playlist that plays the bump and then the video
			$playlist=fopen("$webDirectory/RESOLVER-CACHE/$sum/master.m3u8", "w");
			# add the file header
			fwrite($playlist,"#EXTM3U\n");
			fwrite($playlist,"#PLAYLIST:$sum\n");
			# figure out the absolute server path
			$serverPath='http://'.$_SERVER["HTTP_HOST"].'/';

			# write the first segment repeatedly in order to generate a buffer time for the player
			for ($index=1;$index <= 30;$index+=1){
				# write 20 lines of the bump
				fwrite($playlist,"#EXTINF:1,Loading... $index/30\n");
				fwrite($playlist,$serverPath."RESOLVER-CACHE/$sum/$sum-stream1.ts\n");
			}
			fwrite($playlist,"#EXTINF:1,\n");
			fwrite($playlist,$serverPath."RESOLVER-CACHE/$sum/$sum.m3u\n");
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
					redirect("RESOLVER-CACHE/$sum/$sum.mp4");
				}else if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/$sum.m3u")){
					# if the stream has x segments (segments start as 0)
					# - currently 10 seconds of video
					# - force loading of 3 segments before resolution
					if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/$sum-stream2.ts")){
						# redirect to the stream
						redirect("RESOLVER-CACHE/$sum/$sum.m3u");
					}
				}
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
	echo "<div class='settingListCard'>";
	echo "<h2>Manual Video Cache Interface</h2>";
	echo "No url was specified to the resolver!<br>";
	echo "To Cache a video and play it from here you can use the below form.<br>";
	echo "<form method='get'>";
	echo "	<input width='60%' type='text' name='url'>";
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
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'l/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo "</ul>";
	echo "</div>";
	echo "<div class='settingListCard'>";
	echo "<h2>Random Cached Videos</h2>";
	$sourceFiles = recursiveScan($_SERVER['DOCUMENT_ROOT']."/RESOLVER-CACHE/");
	# build the video index
	foreach($sourceFiles as $sourceFile){
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (stripos($sourceFile,".mp4")){
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
	echo "</div>";
	echo "</body>";
	echo "</html>";
}
?>
