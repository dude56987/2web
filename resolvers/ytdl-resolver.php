<!--
########################################################################
# 2web resolver for caching and playback of video links using yt-dlp
# Copyright (C) 2024  Carl J Smith
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
		$dlCommand = $dlCommand." --write-thumbnail --convert-thumbnails png";
	}else{
		$dlCommand = $command." --all-subs --sub-format srt --embed-subs";
		$dlCommand = $dlCommand." --write-thumbnail --convert-thumbnails png";
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
			$dlCommand = $dlCommand." --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/video.mp4' -c '".$videoLink."'";
		}else{
			# if no server settings are configured use the default
			# the dl command should simply convert the downloaded m3u file with the m3u file
			if ( (! file_exists("/etc/2web/cache/cacheResize.cfg")) AND (! file_exists("/etc/2web/cache/cacheFramerate.cfg")) ){
				# if no upgrade quality is set and no hls rescaling or frame dropping then convert the file to mp4 directly
				$dlCommand = "ffmpeg -i '$webDirectory/RESOLVER-CACHE/$sum/video.m3u' '$webDirectory/RESOLVER-CACHE/$sum/video.mp4'";
			}else{
				# if custom postprocessing is set then the download command should be the same as the input
				# - This is because postprocessing can only decrease the quality, this is to upgrade from those decreases
				if (($quality == "best") or ($quality == "worst")){
					$dlCommand = $dlCommand." -f '".$quality."'";
				}else{
					$dlCommand = $dlCommand." -S '".$quality."'";
				}
				$dlCommand = $dlCommand." --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/video.mp4' -c '".$videoLink."'";
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
		$command = $command." -o - -c '".$videoLink."' | ffmpeg -i - $cacheFramerate $cacheResize -hls_playlist_type event -hls_list_size 0 -start_number 0 -master_pl_name video.m3u -g 30 -hls_time 10 -f hls '$webDirectory/RESOLVER-CACHE/".$sum."/video-stream.m3u'";
		# after the download also transcode the
		# add the higher quality download to happen in the sceduled command after the stream has been transcoded
		$command = 'echo "'.$command.";".$dlCommand.'"';
		#$dlCommand = 'echo "'.$dlCommand.'"';
	}
	# - Place the higher quality download or the mp4 conversion in a batch queue
	#   that adds a new task once every 60 seconds if system load is below 1.5
	# - This will prevent active downloads from being overwhelmed
	# allow setting of batch processing of cached links
	if (array_key_exists("batch",$_GET)){
		if ($_GET["batch"] == "true") {
			$command = $command." | /usr/bin/at -M -q b now";
			#$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
		}else{
			// add command to next available slot in the queue
			$command = $command." | /usr/bin/at -M -q a now";
			#$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
		}
	}else{
		// add command to next available slot in the queue
		$command = $command." | /usr/bin/at -M -q a now";
		#$dlCommand = $dlCommand." | /usr/bin/at -M -q Z now";
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
	#runShellCommand($dlCommand);
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
if (array_key_exists("url",$_GET)){
	$videoLink = $_GET['url'];
	debug("URL is ".$videoLink."<br>");
	# make sure the url is a webpage
	$videoMimeType=get_headers($videoLink, true);
	$headerData=$videoMimeType;
	# pull the content types
	logPrint("Checking if this is an array.<br>");
	if (is_array($videoMimeType)){
		logPrint("This is an array.<br>");
		logPrint("Checking mime type.<br>");
		if (array_key_exists("Content-Type", $videoMimeType)){
			logPrint("Key was found to exist.<br>");
			$videoMimeType=$videoMimeType["Content-Type"];
			logPrint("videoMimeType = $videoMimeType");
		}else{
			logPrint("Media Content Type could not be determined.<br>");
		}
	}else{
		logPrint("No Header was returned.<br>");
	}
	# if the url is a link to media directly redirect to that media
	#if (! is_in_array("text/html", $videoMimeType)){
	if (is_in_array("audio/mpeg", $videoMimeType)){
		redirect($videoLink);
	}else if (is_in_array("video/mp4", $videoMimeType)){
		redirect($videoLink);
	}
	# start translating the url
	# remove parenthesis from video link if they exist
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);

	# create the sum of the file
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
		$url = "/RESOLVER-CACHE/$sum/video.mp4";
		debug("Checking path ".$url."<br>");
		################################################################################
		$cacheFile=false;
		#check for the first x segments of the hls playback, ~30 seconds
		if (file_exists("$webDirectory$url")){
			# if the json data has not yet been verified to have downloaded the entire file
			if (! file_exists($storagePath."verified.cfg")){
				# check the file downloaded correctly by comparing the json data file length with local file length
				if (file_exists($storagePath."video.mp4")){
					# if the file was last modified more than 60 seconds ago
					# - checks should wait for caching to complete in order to properly read file metadata
					if ( ( time() - filemtime($storagePath."video.mp4") ) > 60){
						if (file_exists($storagePath."video.info.json")){
							# The json downloaded from the remote and stored by the resolver
							$remoteJson = json_decode(file_get_contents($storagePath."video.info.json"));
							$remoteJsonValue=(int)$remoteJson->duration;
							# reduce the value to add variance for rounding errors
							# - Depending on the json data and how it was generated the rounding of time may go up or down by one
							$remoteJsonValue-=1;
							# the json data from reading the current downloaded file
							$localJson = json_decode(shell_exec("mediainfo --output=JSON ".$storagePath."video.mp4"));
							$localJsonValue= (int)$localJson->media->track[0]->Duration;
							# compare the lenght in the remote json to the local file, including the variance above
							if ($localJsonValue >= $remoteJsonValue){
								addToLog("DOWNLOAD","Attempt to Verify Track Length","Track was verified");
								debug("The video is completely downloaded and has been verified to have downloaded correctly...");
								# if the length is correct the file is verified to have downloaded completely
								touch($storagePath."verified.cfg");
							}else{
								addToLog("DOWNLOAD","Attempt to Verify Track Length","Track was NOT verified because the length was incorrect<br>\nLOCAL='".$localJsonValue."' >= REMOTE='".$remoteJsonValue."'<br>\n");
								debug("The video was corrupt and could not be verified...");
								$cacheFile=true;
							}
						}else{
							addToLog("DOWNLOAD","Attempt to Verify Track Length","Track was NOT verified because no .info.json data could be found to verify length with");
							# the mp4 was found but the json was not
							$cacheFile=true;
						}
					}
				}
			}
			if($cacheFile == true){
				# delete the existing mp4 file so it will be recreated by the caching code
				unlink($storagePath."video.mp4");
				# this means the download has failed and must be re-cached to get the full video
				cacheUrl($sum,$videoLink);
				# wait for either the bump or the file to be downloaded and redirect
				while(true){
					# if 60 seconds of the video has been downloaded then launch the video
					if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mp4")){
						// file is fully downloaded and converted play instantly
						redirect("RESOLVER-CACHE/$sum/video.mp4");
					}else if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.m3u")){
						# if the stream has x segments (segments start as 0)
						# - currently 10 seconds of video
						# - force loading of 3 segments before resolution
						if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video-stream2.ts")){
							# redirect to the stream
							redirect("RESOLVER-CACHE/$sum/video.m3u");
						}
						# if all else fails wait then restart the loop
						sleep(1);
					}
				}
			}
			# touch the file to update the mtime and delay cache removal
			#touch($url);
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
				fwrite($playlist,$serverPath."RESOLVER-CACHE/$sum/video-stream1.ts\n");
			}
			fwrite($playlist,"#EXTINF:1,\n");
			fwrite($playlist,$serverPath."RESOLVER-CACHE/$sum/video.m3u\n");
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
				if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mp4")){
					// file is fully downloaded and converted play instantly
					redirect("RESOLVER-CACHE/$sum/$sum.mp4");
				}else if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.m3u")){
					# if the stream has x segments (segments start as 0)
					# - currently 10 seconds of video
					# - force loading of 3 segments before resolution
					if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video-stream2.ts")){
						# redirect to the stream
						redirect("RESOLVER-CACHE/$sum/video.m3u");
					}
				}
				# if all else fails wait then restart the loop
				sleep(1);
			}
		}
	}
}else{
	# require admin privilages to access the manual resolver interface
	requireAdmin();
	// no url was given at all
	echo "<html>\n";
	echo "<head>\n";
	echo "<link rel='stylesheet' href='style.css'>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "<div class='settingListCard'>\n";
	echo "<h2>Manual Video Cache Interface</h2>\n";
	echo "No url was specified to the resolver!<br>\n";
	echo "To Cache a video and play it from here you can use the below form.<br>\n";
	echo "<form method='get'>\n";
	echo "	<input width='60%' type='text' name='url'>\n";
	echo "	<input class='button' type='submit' value='Cache Url'>\n";
	echo "	<div>\n";
	echo "		<span>Enable Debug Output<span>\n";
	echo "		<input class='button' width='10%' type='checkbox' name='debug'>\n";
	echo "	</div>\n";
	echo "</form>\n";
	echo "</a>\n";
	echo "</div>\n";
	echo "<hr>\n";
	echo "<div class='settingListCard'>"."\n";
	echo "	<h2>WEB API EXAMPLES</h2>"."\n";
	echo "	<p>"."\n";
	echo "		Replace the url api key with your video web link to be cached by youtube-dl."."\n";
	echo "		Debug=true will generate a webpage containing debug data and video output"."\n";
	echo "	</p>"."\n";
	echo "<ul>"."\n";
	echo '	<li>'."\n";
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="http://videoUrl/videoid/"'."\n";
	echo '	</li>'."\n";
	echo '	<li>'."\n";
	echo '		http://'.$_SERVER["HTTP_HOST"].'l/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true'."\n";
	echo '	</li>'."\n";
	echo '	<li>'."\n";
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true'."\n";
	echo '	</li>'."\n";
	echo '	<li>'."\n";
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="http://videoUrl/videoid/"'."\n";
	echo '	</li>'."\n";
	echo '	<li>'."\n";
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true'."\n";
	echo '	</li>'."\n";
	echo '	<li>'."\n";
	echo '		http://'.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true'."\n";
	echo "	</li>\n";
	echo "</ul>\n";
	echo "</div>\n";


	echo "<div class='settingListCard'>\n";
	echo "<h2>All Cached Videos</h2>\n";
	$sourceFiles = scanDir($_SERVER['DOCUMENT_ROOT']."/RESOLVER-CACHE/");
	# remove navigation directory paths
	$sourceFiles = array_diff($sourceFiles,Array(".",".."));
	$tempSourceFiles=Array();
	foreach($sourceFiles as $sourceFile){
		# add the video.mp4 to the path, this is the file the dates will be sorted by
		$tempSourceFile=$_SERVER['DOCUMENT_ROOT']."/RESOLVER-CACHE/".$sourceFile."/video.mp4";
		# add the path if it contains a cached video
		if (file_exists($tempSourceFile)){
			$tempSourceFiles=array_merge($tempSourceFiles,Array($tempSourceFile));
			#$tempSourceFiles=array_merge($tempSourceFiles,Array($_SERVER['DOCUMENT_ROOT']."/RESOLVER-CACHE/".$sourceFile."/video.mp4"));
		}
	}
	# sort the diretories by date
	$sourceFiles = sortPathsByDate($tempSourceFiles);

	foreach($sourceFiles as $sourceFile){
		# remove file name left from date sort
		$sourcePath=str_replace("video.mp4","",$sourceFile);
		$sourceWebPath=str_replace($_SERVER["DOCUMENT_ROOT"],"",$sourcePath);
		# draw the entries for each directory
		# check for a json file to load the video title
		if (file_exists($sourcePath."video.info.json")){
			# load the json title
			$jsonData=file_get_contents($sourcePath."video.info.json");
			$jsonData=json_decode($jsonData);
			$videoTitle=$jsonData->title;
		}else{
			$videoTitle=$sourceFile;
		}
		echo "<a class='showPageEpisode' href='".$sourceWebPath."video.mp4"."'>\n";
		echo "<img loading='lazy' src='".$sourceWebPath."video.png"."' />\n";
		echo "	<h3>".$videoTitle."</h3>\n";
		echo "</a>\n";

	}
	echo "</div>\n";
	echo "</body>";
	echo "</html>";
}
?>
