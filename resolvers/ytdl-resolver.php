<?php
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
requireGroup("resolver");
########################################################################
# 2web resolver for caching and playback of video links using yt-dlp
# Copyright (C) 2025  Carl J Smith
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

// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
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
	// create the directory to hold the video files within the resolver cache
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum/")){
		mkdir("$webDirectory/RESOLVER-CACHE/$sum/");
	}
	# link the player into the resolver cache
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum/index.php")){
		symlink("/usr/share/2web/templates/videoPlayer.php","$webDirectory/RESOLVER-CACHE/$sum/index.php");
	}
	# link the video bump
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum/bump.m3u")){
		# build a m3u playlist that plays the bump and then the video
		$playlist=fopen("$webDirectory/RESOLVER-CACHE/$sum/bump.m3u", "w");
		# add the file header
		fwrite($playlist,"#EXTM3U\n");
		fwrite($playlist,"#PLAYLIST:$sum\n");
		# figure out the absolute server path
		$serverPath='http://'.$_SERVER["HTTP_HOST"].'/';
		# write the first segment repeatedly in order to generate a buffer time for the player
		for ($index=1;$index <= 30;$index+=1){
			# write 20 lines of the bump
			fwrite($playlist,"#EXTINF:1,Loading... $index/30\n");
			fwrite($playlist,"bump.mp4\n");
		}
		fwrite($playlist,"#EXTINF:1,\n");
		fwrite($playlist,"video.mp4\n");
		fclose($playlist);
	}
	if ( ! file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.pls")){
		# build a m3u playlist that plays the bump and then the video
		$playlist=fopen("$webDirectory/RESOLVER-CACHE/$sum/video.pls", "w");
		# add the file header
		fwrite($playlist,"[playlist]\n");
		# figure out the absolute server path
		$serverPath='http://'.$_SERVER["HTTP_HOST"].'/RESOLVER-CACHE/'.$sum."/";
		# write the first segment repeatedly in order to generate a buffer time for the player
		for ($index=1;$index <= 30;$index+=1){
			# write 20 lines of the bump
			fwrite($playlist,"File".$index."=".$serverPath."bump.mp4\n");
			fwrite($playlist,"Title".$index."=Loading... $index/30\n");
		}
		fwrite($playlist,"File31=".$serverPath."video.mp4\n");
		fwrite($playlist,"Title31=$sum\n");
		fwrite($playlist,"NumberOfEntries=31\n");
		fwrite($playlist,"Version=2\n");
		fclose($playlist);
	}
	################################################################################
	// if the cache flag has been set to true then download the file and play it from the cache
	debug("Build the command<br>");
	$command = "set -x;";
	$command .= "nice -n -5 ";
	// add the download to the cache with the processing queue
	if (file_exists("/var/cache/2web/generated/yt-dlp/yt-dlp")){
		$command .= "/var/cache/2web/generated/yt-dlp/yt-dlp --abort-on-error --sponsorblock-mark all ";
	}else if (file_exists("/usr/local/bin/yt-dlp")){
		debug("yt-dlp found<br>");
		# add the sponsorblock video bookmarks to the video file when using yt-dlp
		$command .= "/usr/local/bin/yt-dlp --abort-on-error --sponsorblock-mark all ";
	}else if (file_exists("/usr/local/bin/youtube-dl")){
		debug("PIP version of youtube-dl found<br>");
		$command .= "/usr/local/bin/youtube-dl";
	} else {
		$command .= "youtube-dl";
	}
	$quality = getQualityConfig($webDirectory);
	$cacheMode = getCacheMode($webDirectory);
	debug("The web interface set quality is '".$quality."'");
	// abort if parts of the stream are missing
	$command .= " --abort-on-unavailable-fragments";
	// max download file size should be 6 gigs, this is a insane file size for a youtube video
	// if a way of detecting livestreams is found this is unnessary
	$command .= " --max-filesize '6g'";
	$command .= " --retries 'infinite'";
	$command .= " --no-mtime";
	$command .= " --fragment-retries 'infinite'";

	# embed subtitles, continue file downloads, ignore timestamping the file(it messes with caching)
	$command = $command." --continue";
	#
	#if (file_exists("/etc/2web/cache/cacheUpgradeQuality.cfg")){
	#	# only write the json info to the upgrade version
	#	$command = $command." --write-info-json";
	#}
	#
	$dlCommand = $command." --all-subs --sub-format srt --embed-subs";
	#
	$dlCommand = $dlCommand." --write-thumbnail --convert-thumbnails png";

	# if no quality is set in url use server settings
	if (file_exists("/etc/2web/cache/cacheUpgradeQuality.cfg")){
		$upgradeQuality = getUpgradeQualityConfig($webDirectory);

		# update the download command
		if (($upgradeQuality == "best") or ($upgradeQuality == "worst")){
			if ($upgradeQuality == "worst"){
				$upgradeQuality = " -f '".$upgradeQuality."'";
			}else{
				# no quality should be given for the best quality
				$upgradeQuality = "";
			}
		}else{
			$dlCommand = $dlCommand." -S '".$upgradeQuality."'";
		}
		# create the updgrade download command
		$dlCommand = $dlCommand." --write-info-json --recode-video mp4 -o '$webDirectory/RESOLVER-CACHE/$sum/video.mp4' -c '".$videoLink."'";
	}else{
		# download the json data into a file
		$dlCommand = "yt-dlp --no-download --dump-single-json '$videoLink' > '$webDirectory/RESOLVER-CACHE/$sum/video.info.json';\n";
		# if no upgrade is set then simply convert the hls stream into a mp4 and grab the json data last
		$dlCommand .= "ffmpeg -i '$webDirectory/RESOLVER-CACHE/$sum/video.m3u' -f mp4 '$webDirectory/RESOLVER-CACHE/$sum/video.mp4.part'";
		$dlCommand .= " && mv -v '$webDirectory/RESOLVER-CACHE/$sum/video.mp4.part' '$webDirectory/RESOLVER-CACHE/$sum/video.mp4';";
	}
	# use the correct format for the quality value chosen
	if (($quality == "best") or ($quality == "worst")){
		if ($quality == "worst"){
			$quality = " -f '".$quality."'";
		}else{
			# no quality should be given for the best quality
			$quality = "";
		}
	}else{
		$quality = " -S '".$quality."'";
	}
	# create the stream command that will be ran to get the chosen quality and convert it into a hls stream
	# force the most compatible version of the stream codecs
	$command .= $quality." -o - -c '".$videoLink."' | ffmpeg -i - -f mpegts -c:v libx264 -c:a aac -crf 0 - | ffmpeg -i - -c:v copy -c:a copy -crf 0 -hls_playlist_type event -hls_segment_type mpegts -hls_list_size 0 -start_number 0 -master_pl_name video.m3u -g 30 -hls_time 10 -f hls '$webDirectory/RESOLVER-CACHE/".$sum."/video-stream.m3u'";

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

	# add the upgrade command after writing the commands to the log
	$command .= ";".$dlCommand;
	# run curl after download to access the video link and activate the verification process
	#$command .= ";sleep 95;curl \"https://localhost/ytdl-resolver.php?url=$videoLink\" > /dev/null";
	# Add the command to the processing queue
	addToQueue("multi",$command);
}
################################################################################
function cacheResolve($sum,$webDirectory){
	# wait for either the bump or the file to be downloaded and redirect
	while(true){
		#
		$goToRedirect=false;
		#
		if(array_key_exists("activePlaybackSum",$_SESSION)){
			if( $_SESSION["activePlaybackSum"] == $sum ){
				if(array_key_exists("activePlaybackTime",$_SESSION)){
					# if the resovler has not been accessed for more than 90 seconds
					if( ( time() - $_SESSION["activePlaybackTime"] ) < 90 ){
						$_SESSION["activePlaybackTime"]=time();
						if(array_key_exists("activePlaybackMime",$_SESSION)){
							header($_SESSION["activePlaybackMime"]);
						}
						if(array_key_exists("activePlaybackUrl",$_SESSION)){
							redirect($_SESSION["activePlaybackUrl"]);
						}
					}
				}
			}
		}
		# if 90 seconds of the video has been downloaded then launch the video
		if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/verified.cfg")){
			if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mp3")){
				$mime="Content-type: audio/mpeg;";
				$path="/RESOLVER-CACHE/$sum/video.mp3";
				# redirect to discovered mp3
				$goToRedirect=true;
			}else if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mp4")){
				$mime="Content-type: video/mp4;";
				$path="/RESOLVER-CACHE/$sum/video.mp4";
				# file is fully downloaded and converted play instantly
				$goToRedirect=true;
			}else{
				$mime="Content-type: application/mpegurl;";
				$path="/RESOLVER-CACHE/$sum/video.m3u";
				# load the HLS stream as a last resort fallback
				# - nothing verified should get here so this is a fallback for crazy exceptions
				$goToRedirect=true;
			}
		}else if((file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mkv")) and (substr($_SERVER["HTTP_USER_AGENT"],0,4) == "Kodi") and ( ( time() - filemtime($webDirectory."/RESOLVER-CACHE/".$sum."/video.mkv") ) > 90) ){
			$mime="Content-type: video/x-matroska;";
			$path="/RESOLVER-CACHE/$sum/video.mkv";
			$goToRedirect=true;
		}else if((file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.webm")) and (substr($_SERVER["HTTP_USER_AGENT"],0,4) == "Kodi") and ( ( time() - filemtime($webDirectory."/RESOLVER-CACHE/".$sum."/video.webm") ) > 90) ){
			# only load webm files that are not being written
			# - only redirect to KODI clients
			# redirect to intermediary webm files
			$mime="Content-type: video/webm;";
			$path="/RESOLVER-CACHE/$sum/video.webm";
			$goToRedirect=true;
		}else if( file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.m3u") and file_exists("$webDirectory/RESOLVER-CACHE/$sum/video-stream0.ts") ){
			# if the stream has x segments (segments start as 0)
			# - currently 10 seconds of video
			# - force loading of 3 segments before resolution
			$mime="Content-type: application/mpegurl;";
			# redirect to the stream
			$path="/RESOLVER-CACHE/$sum/video.m3u";
			$goToRedirect=true;
		}
		if($goToRedirect){
			# Set the active playback values and then redirect
			# - sessions store the active playback variables to prevent broken
			#   playback because of the resolver upgrading the quality of the
			#   stream and changing the resolver value
			$_SESSION["activePlaybackSum"]=$sum;
			$_SESSION["activePlaybackMime"]=$mime;
			$_SESSION["activePlaybackUrl"]=$path;
			$_SESSION["activePlaybackTime"]=time();
			#
			header($mime);
			redirect($path);
		}
		# Sleep at end of the loop then try to find a redirect again
		sleep(1);
	}
}
################################################################################
if (array_key_exists("url",$_GET)){
	$videoLink = $_GET['url'];
	# check the url is not a path
	if (file_exists($_SERVER["DOCUMENT_ROOT"].$_GET["url"])){
		#addToLog("DEBUG","ytdl-resolver.php reading link",($_SERVER["DOCUMENT_ROOT"].$_GET["url"]));
		#addToLog("DEBUG","ytdl-resolver.php","Discovered direct link path, redirecting to path '".$_GET["url"]."'");
		# redirect to the direct path
		redirect($_GET["url"]);
	}
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
	#addToLog("DEBUG","resolver.php","hash input pre clean = '$videoLink'");
	# remove parenthesis from video link if they exist
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);

	# create the sum of the file
	$sum = hash("sha512",$videoLink,false);

	#addToLog("DEBUG","resolver.php","hash input = '$videoLink'");
	#addToLog("DEBUG","resolver.php","hash sum = '$sum'");

	debug("[DEBUG]: SUM is ".$sum."<br>");
	// check for the cache flag
	if (array_key_exists("link",$_GET)){
		debug("[DEBUG]: linking to video ".$videoLink."<br>");
		################################################################################
		# Get a direct link to the video bypassing the cache.
		# This works on most sites, except youtube.
		################################################################################
		$output = shell_exec('/usr/local/bin/youtube-dl --get-url '.$videoLink);
		if ($output == null){
			# the url was not able to resolve
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
		#check for the first x segments of the hls playback, ~30 seconds
		if (file_exists("$webDirectory$url")){
			# if the json data has not yet been verified to have downloaded the entire file
			$isVerified=verifyCacheFile($storagePath,$videoLink);
			if($isVerified == false){
				# delete the existing mp4 file so it will be recreated by the caching code
				unlink($storagePath."video.mp4");
				# this means the download has failed and must be re-cached to get the full video
				cacheUrl($sum,$videoLink);
			}
			# touch the file to update the mtime and delay cache removal
			touch($storagePath);
			cacheResolve($sum,$webDirectory);
		}else{
			# ignore user abort of connection
			ignore_user_abort(true);
			# allow parallel loading of pages for user
			session_write_close();
			# set execution time limit to 15 minutes
			set_time_limit(900);

			debug("No file exists in the cache<br>");
			debug("cache is set<br>");
			# cache the url if no log has been created, otherwise jump to the redirect
			if(! file_exists("$webDirectory/RESOLVER-CACHE/$sum/data.log")){
				cacheUrl($sum,$videoLink);
			}
			# wait for a cache link to become available and redirect
			cacheResolve($sum,$webDirectory);
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
		$tempSourceFile=$_SERVER['DOCUMENT_ROOT']."/RESOLVER-CACHE/".$sourceFile."/";
		#if (is_readable($tempSourceFile."/video.mp4")){
			$tempSourceFiles=array_merge($tempSourceFiles,Array($tempSourceFile));
		#}
	}
	# sort the diretories by date
	$sourceFiles = sortPathsByDate($tempSourceFiles);
	# draw the table containing cached videos and statuses of those videos
	echo "<table>";

	echo "<tr>";
	echo "<th>Last Watched</th>";
	echo "<th>Verified</th>";
	echo "<th>Thumbnail</th>";
	echo "<th>HLS</th>";
	echo "<th>MP4/MP3</th>";
	echo "<th>JSON</th>";
	echo "<th>Title</th>";
	echo "<th>Remove</th>";
	echo "<th>Web Player</th>";
	echo "<th>Link</th>";
	echo "</tr>";
	foreach($sourceFiles as $sourcePath){
		echo "<tr>";
		# remove file name left from date sort
		$sourceWebPath=str_replace($_SERVER["DOCUMENT_ROOT"],"",$sourcePath);
		# check the last watched time
		echo "<td>";
		echo timeElapsedToHuman(filemtime($sourcePath));
		echo "</td>";
		# check for verified video
		if (is_readable($sourcePath."verified.cfg")){
			echo "<td class='enabledSetting'>Verified</td>";
		}else{
			echo "<td class='disabledSetting'>Not Verified</td>";
		}
		# check for thumbnail
		if (is_readable($sourcePath."video.png")){
			echo "<td class='enabledSetting'>PNG Found</td>";
		}else{
			echo "<td class='disabledSetting'>No PNG</td>";
		}
		# check for HLS stream
		if (is_readable($sourcePath."video.m3u")){
			echo "<td class='enabledSetting'>M3U Found</td>";
		}else{
			echo "<td class='disabledSetting'>No M3U</td>";
		}
		# check for mp4 file
		if (is_readable($sourcePath."video.mp3")){
			echo "<td class='enabledSetting'>";
			echo "MP3 Found";
			echo "<span class='singleStat'>";
			echo "<span class='singleStatLabel'>";
			echo "Size";
			echo "</span>";
			echo "<span class='singleStatValue'>";
			echo bytesToHuman(filesize($sourcePath."video.mp3"));
			echo "</span>";
			echo "</span>";
			echo "</td>";
		}else if (is_readable($sourcePath."video.mp4")){
			echo "<td class='enabledSetting'>";
			echo "MP4 Found";
			echo "<span class='singleStat'>";
			echo "<span class='singleStatLabel'>";
			echo "Size";
			echo "</span>";
			echo "<span class='singleStatValue'>";
			echo bytesToHuman(filesize($sourcePath."video.mp4"));
			echo "</span>";
			echo "</span>";
			echo "</td>";
		}else{
			echo "<td class='disabledSetting'>No MP4 or MP3</td>";
		}

		$sourceHash=basename($sourcePath);

		# check for json data
		if (is_readable($sourcePath."video.info.json")){
			echo "<td class='enabledSetting'>";
			echo "<a class='button' href='/RESOLVER-CACHE/$sourceHash/video.info.json'>";
			echo "Json Found";
			echo "</a>";
			echo "</td>";
		}else{
			echo "<td class='disabledSetting'>No Json</td>";
		}
		# draw the entries for each directory
		# check for a json file to load the video title
		if (file_exists($sourcePath."video.info.json")){
			# load the json title
			$jsonData=file_get_contents($sourcePath."video.info.json");
			$jsonData=json_decode($jsonData);
			$videoTitle=$jsonData->title;
		}else{
			$videoTitle=$sourcePath;
		}
		echo "<td>$videoTitle</td>";

		echo "<td>\n";

		echo "	<form action='/settings/admin.php' class='buttonForm' method='post'>\n";
		echo "		<button class='button' type='submit' name='removeCachedVideo' value='$sourceHash'>Remove Cached Video</button>\n";
		echo "	</form>\n";
		echo "</td>\n";

		# use the web player if it is available
		if (is_readable("/var/cache/2web/web/web_player/".$sourceHash."/".$sourceHash.".php")){
			echo "<td class='enabledSetting'>";
			echo "	<a class='showPageEpisode' href='/web_player/".$sourceHash."/".$sourceHash.".php'>Web Player Link</a>\n";
			echo "</td>";
		}else{
			echo "<td class='disabledSetting'>";
			echo "No Web Player Link Was Found";
			echo "</td>";
		}

		echo "<td>";
		echo "	<a class='showPageEpisode' href='".$sourceWebPath."video.mp4"."'>\n";
		echo "		<img loading='lazy' src='".$sourceWebPath."video.png"."' />\n";
		echo "		<h3>".$videoTitle."</h3>\n";
		echo "	</a>\n";
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";

	echo "</div>\n";
	echo "</body>";
	echo "</html>";
}
?>
