<?php
	#ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	########################################################################
	startSession();
	########################################################################
	# get the title data
	$titlePath=$_SERVER["SCRIPT_FILENAME"].".title";
	# get the sum from the filename
	$jsonSum=str_replace(".php","",basename($_SERVER["SCRIPT_FILENAME"]));
	# check for resolver json data
	$jsonPath=$_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.info.json";
	$jsonPathMP4=$_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4.info.json";
	if (file_exists($titlePath)){
		$titleData=file_get_contents($titlePath);
		$useJson=false;
		$jsonPath="";
	}else if (file_exists($jsonPath)){
		$titleData=$jsonSum;
		$useJson=true;
		# build the json path
		$jsonPath=$jsonPath;
	}else if (file_exists($jsonPathMP4)){
		$titleData=$jsonSum;
		$useJson=true;
		# build the json path
		$jsonPath=$jsonPathMP4;
	}else{
		addToLog("ERROR","videoPlayer.php","Could Not Find A Title");
		$titleData=$jsonSum;
		$useJson=false;
	}
	# check group permissions based on what the player is being used for
	if($useJson){
		requireGroup("webPlayer");
		# load the json data
		$jsonData=json_decode(file_get_contents($jsonPath));
		if (isset($jsonData)){
			if ($jsonData != ""){
				# get the title
				if(property_exists($jsonData, "title")){
					$titleData=$jsonData->title;
				}
			}
		}
	}else{
		requireGroup("nfo2web");
	}
?>
<!--
########################################################################
# 2web video player for nfo2web
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
<?PHP
########################################################################
function cachedMimeType($videoLink,$cacheLocation="RESOLVER-CACHE"){
	# return the path to a found cache link once it is available

	# set the default web directory
	$webDirectory="/var/cache/2web/web";
	# cleanup the video link
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);
	# get the sum
	$sum = createSum($videoLink);
	#
	$maxSleep=2;
	$sleepCounter=0;
	# wait for either the bump or the file to be downloaded and redirect
	while(true){
		if(file_exists("$webDirectory/$cacheLocation/$sum/verified.cfg")){
			if((file_exists("$webDirectory/$cacheLocation/$sum/video.mp3"))){
				return ("audio/mpeg");
			}else if((file_exists("$webDirectory/$cacheLocation/$sum/video.mp4"))){
				return ("video/mp4");
			}else{
				return ("application/mpegurl");
			}
		}else if((file_exists("$webDirectory/$cacheLocation/$sum/video.webm")) and (substr($_SERVER["HTTP_USER_AGENT"],0,4) == "Kodi") and ( ( time() - filemtime($webDirectory."/$cacheLocation/".$sum."/video.webm") ) > 90) ){
			return ("video/webm");
		}else if( file_exists("$webDirectory/$cacheLocation/$sum/video.m3u") and file_exists("$webDirectory/RESOLVER-CACHE/$sum/video-stream0.ts") ){
			return ("application/mpegurl");
		}
		$sleepCounter+=1;
		if ($sleepCounter > $maxSleep ){
			# if no media can be resolved return loading as the metadata type
			return ("loading");
		}
		sleep(1);
	}
}
########################################################################
function getCacheSum($videoLink){
	# cleanup the video link
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);
	# get the sum
	$sum = createSum($videoLink);
	return $sum;
}
########################################################################
function getCacheLink($videoLink,$mimeType,$cacheType="RESOLVER-CACHE"){
	# return the path to a found cache link once it is available

	# set the default web directory
	$webDirectory="/var/cache/2web/web";
	# cleanup the video link
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);
	# get the sum
	$sum = createSum($videoLink);
	#
	if ("video/mp4" == $mimeType){
		return ("/$cacheType/$sum/video.mp4");
	}else if ("video/webm" == $mimeType){
		return ("/$cacheType/$sum/video.webm");
	}else if ("audio/mpeg" == $mimeType){
		return ("/$cacheType/$sum/video.mp3");
	}else if ("application/mpegurl" == $mimeType){
		return ("/$cacheType/$sum/video.m3u");
	}else if ("video/ogg" == $mimeType){
		return ("/$cacheType/$sum/video.ogg");
	}else if ("video/mp4" == $mimeType){
		# all cached locations have failed to find this video
		addToLog("ERROR","videoPlayer.php","No mime type '$mimeType' exists for getCacheLink()");
		return ("$videoLink");
	}
}
########################################################################
function fetchHeader($URL, $streamContextParams=false){
	if ($streamContextParams == false){
		# setup the defaul stream context params
		# - timeout is 30 seconds
		# - accept the webserver cert by default if this url is on the local server
		$streamContextParams=[
			'ssl' => [
				'cafile' => "/var/cache/2web/ssl-cert.crt",
				'verify_peer' => true,
				'verify_peer_name' => true,
				'allow_self_signed' => true,
			],
			'http' => [
				'timeout' => 90,
				'method' => 'HEAD',
				'user_agent' => 'PHP',
			],
		];
	}
	$streamContext=stream_context_create($streamContextParams);
	#
	$headerOutputData=get_headers($URL, true, $streamContext);
	#
	return $headerOutputData;
}
################################################################################
function createSum($inputString){
	# create the sum of the file
	if (array_key_exists("HTTPS",$_SERVER)){
		if ($_SERVER["HTTPS"]){
			$proto="https://";
		}else{
			$proto="http://";
		}
	}else{
		$proto="http://";
	}
	# check if the path is a local path
	if(file_exists($inputString)){
		#
		$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url='.($proto.$_SERVER["HTTP_HOST"].$inputString);
		#
		$sum = hash("sha512",$fullPathVideoLink,false);
	}else{
		#
		$sum = hash("sha512",$inputString,false);
	}
	return $sum;
}
################################################################################
if($useJson){
	# use random fanart if this is loading from the json
	echo "<html id='top' class='randomFanart'>";
}else{
	echo "<html id='top' class='seriesBackground'>";
}
if (file_exists("show.title")){
	# get the show title
	$showTitle=file_get_contents("show.title");
	# get the numeric title
	$numericTitlePath=$_SERVER["SCRIPT_FILENAME"].".numTitle";
	$numericTitleData=file_get_contents($numericTitlePath);
	echo "<title>$showTitle - $numericTitleData</title>";
}else	if(file_exists("movie.title")){
	$movieTitle=file_get_contents("movie.title");
	echo "<title>$movieTitle</title>";
}else if(file_exists($_SERVER["SCRIPT_FILENAME"].".title")){
	# set the title from the title file
	$movieTitle=file_get_contents($_SERVER["SCRIPT_FILENAME"].".title");
	echo "<title>".$movieTitle."</title>";
}else{
	# set the title from the script name
	$movieTitle=str_replace(".php","",basename($_SERVER["PHP_SELF"]));
}
# update the hostname
if ( $_SERVER["HTTP_HOST"] == "localhost" ){
	$_SERVER["HTTP_HOST"] == (gethostname().".local");
}
if (array_key_exists("HTTPS",$_SERVER)){
	if ($_SERVER["HTTPS"]){
		$proto="https://";
	}else{
		$proto="http://";
	}
}else{
	$proto="http://";
}
?>
<head>
<link rel='stylesheet' href='/style.css' />
<script src='/2webLib.js'></script>
<script src='/hls.js'></script>
<style>
<?PHP
	# look for fanart and poster
	echo ":root{\n";
	if (file_exists("show.title")){
		$seasonTitle=file_get_contents("season.title");
		# set the show poster and fanart as the background
		echo "--backgroundPoster: url(\"/shows/$showTitle/poster.png\");";
		echo "--backgroundFanart: url(\"/shows/$showTitle/fanart.png\");";
	}else{
		#
		echo "--backgroundPoster: url(\"/movies/$movieTitle/poster.png\");";
		echo "--backgroundFanart: url(\"/movies/$movieTitle/fanart.png\");";
	}
	echo "}";
	?>
</style>
<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<script>
document.body.addEventListener('keydown', function(event){
	// only allow hotkeys if the video player has focus
	//if(document.getElementById("video") == document.activeElement){
		// check for key controls on the video player
		const key = event.key;
		switch (key){
			case "Insert":
			event.preventDefault();
			event.stopImmediatePropagation();
			toggleFullscreen("video");
			// hide the video controls after keypresses
			window.video.controls=false;
			break;
			case "ArrowDown":
			event.preventDefault();
			event.stopImmediatePropagation();
			volumeDown();
			notify("Vol -");
			window.video.controls=false;
			break;
			case "ArrowUp":
			event.preventDefault();
			event.stopImmediatePropagation();
			volumeUp();
			notify("Vol +");
			window.video.controls=false;
			break;
			case "ArrowRight":
			event.preventDefault();
			event.stopImmediatePropagation();
			seekForward();
			notify("Seek ++");
			window.video.controls=false;
			break;
			case "ArrowLeft":
			event.preventDefault();
			event.stopImmediatePropagation();
			seekBackward();
			notify("Seek --");
			window.video.controls=false;
			break;
			case " ":
			event.preventDefault();
			event.stopImmediatePropagation();
			playPause();
			notify("⏯️");
			window.video.controls=false;
			break;
		}
	//}
});
</script>
<?PHP
	include("/usr/share/2web/templates/header.php");
?>
<?PHP
	# check for sources
	if (requireGroup("admin",false)){
		if(file_exists("sources.cfg")){
			$adminData="<div class='titleCard'>\n";
			$adminData.="<h2>Media Sources</h2>\n";
			$adminData.="<pre>\n";
			$adminData.=file_get_contents("sources.cfg");
			$adminData.="</pre>\n";
			$adminData.="</div>\n";
		}else{
			$adminData="";
		}
	}else{
		$adminData="";
	}
	# check for direct link
	$directLinkPath=$_SERVER["SCRIPT_FILENAME"].".directLink";
	if (file_exists($directLinkPath)){
		$directLinkData=file_get_contents($directLinkPath);
		#
		$directLinkData=trim($directLinkData, "\r\n");
		#
		$directLinkExists=true;
		# remove parenthesis from video link if they exist
		$directLinkData=str_replace('"','',$directLinkData);
		$directLinkData=str_replace("'","",$directLinkData);
		# remove hash data if found
		if ( strpos($directLinkData,"#") !== false) {
			# split the string based on the hash
			$tempData=explode("#",$directLinkData);
			$directLinkData=$tempData[0];
		}
		# create the sum of the file
		$sum=createSum($directLinkData);
	}else{
		$directLinkExists=false;
	}
	# check if the direct link is a link to a external source rather than the local server
	if ( substr($directLinkData,0,1) == "/" ){
		$httpLink=false;
	}else if ( substr($directLinkData,0,7) == "http://" ){
		$httpLink=true;
	}else if ( substr($directLinkData,0,8) == "https://" ){
		$httpLink=true;
	}else{
		$httpLink=true;
	}
	# get the archive title
	if (file_exists("show.title")){
		# write the data
		$archiveTitle="$showTitle - $numericTitleData - $titleData";
	}else if($useJson){
		# use the title data if this is a video in the cache
		$archiveTitle=$titleData;
	}else{
		# write the movie data
		$archiveTitle=$movieTitle;
	}
	# this should always be set even if it is empty
	$videoChannelUrl="";
	# load data from the json file
	if(file_exists($jsonPath)){
		if( ( $jsonData == null ) or ( $jsonData == "null" ) ){
			# remove and re-download the json data
			# remove the json data
			unlink($jsonPath);
			# add a job to the queue to get the title
			$command = "/var/cache/2web/generated/yt-dlp/yt-dlp --no-download --dump-single-json ";
			$command .= "\"$directLinkData\"";
			$command .= " > \"$jsonPath\"";
			#
			addToQueue("multi",$command);
			# this will require a reload
			reloadPage(10);
			exit();
		}
		# load the json and build the page
		if(property_exists($jsonData, "age_limit")){
			$ratingText="<span class='button'>Recommended Minimum Viewing Age: ".$jsonData->age_limit."</span>";
		}else{
			# there is no description so it is unrated
			$ratingText="<span class='button'>Rating : UNRATED</span>";
		}
		if(file_exists($_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.png")){
			# get the thumbnail
			$posterPath="/RESOLVER-CACHE/".$jsonSum."/video.png";
		}else if(file_exists($_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4.png")){
			# get the thumbnail
			$posterPath="/RESOLVER-CACHE/".$jsonSum."/video.mp4.png";
		}else{
			# check if the video file has been found
			if (file_exists($_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4")){
				if ( ( time() - filemtime($_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4") ) > 90){
					# create a thumbnail  from the downloaded video file if the video file has finished downloading
					addToQueue("multi","/usr/bin/ffmpegthumbnailer -i '".$_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4' -s 400 -c png -o '".$_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.png'");
					# set the current thumbnail to be the poster.png
					$posterPath="/RESOLVER-CACHE/".$jsonSum."/video.png";
				}else{
					# set the current thumbnail to be the poster.png
					$posterPath="/plasmaPoster.png";
				}
			}else{
				# set the current thumbnail to be the poster.png
				$posterPath="/plasmaPoster.png";
			}
		}
		# get the plot data
		if(property_exists($jsonData, "description")){
			$plotData="<div class='plot'>\n";
			$plotData.=str_replace("\n","<br>",$jsonData->description);
			$plotData.="</div>\n";
		}else{
			# there is no description so blank it out
			$plotData="";
		}
		# get the uploder url
		$tempVideoChannelUrl="<table>\n";
		if(property_exists($jsonData, "uploader_url")){
			$videoChannelUrl=$jsonData->uploader_url;
			# Build the html for the bottom of the page
			$tempVideoChannelUrl.="<tr>\n";
			$tempVideoChannelUrl.="<th>Channel Source</th>\n";
			if (requireGroup("admin",false)){
				$tempVideoChannelUrl.="<th>Admin Actions</th>\n";
			}
			$tempVideoChannelUrl.="</tr>\n";
			$tempVideoChannelUrl.="<tr>\n";
			$tempVideoChannelUrl.="	<td>\n";
			$tempVideoChannelUrl.="		<a href='".$videoChannelUrl."'>".$videoChannelUrl."</a>\n";
			$tempVideoChannelUrl.="	</td>\n";
			if (requireGroup("admin",false)){
				# if the user has admin permissions load the button that will add the channel
				$tempVideoChannelUrl.="	<td>\n";
				$tempVideoChannelUrl.="		<form action='/settings/admin.php' method='post'>\n";
				$tempVideoChannelUrl.="			<input width='60%' type='text' name='ytdl_add_username_source' value='".$videoChannelUrl."' hidden>\n";
				$tempVideoChannelUrl.="			<button class='button' type='submit'>➕ Add Channel</button>\n";
				$tempVideoChannelUrl.="		</form>\n";
				$tempVideoChannelUrl.="	</td>\n";
			}
			$tempVideoChannelUrl.="</tr>\n";
		}
		# Build the html for the bottom of the page
		$tempVideoChannelUrl.="<tr>\n";
		$tempVideoChannelUrl.="<th>Video URL</th>\n";
		if (requireGroup("admin",false)){
			$tempVideoChannelUrl.="<th>Admin Actions</th>\n";
		}
		$tempVideoChannelUrl.="</tr>\n";
		if (file_exists($directLinkPath)){
			$tempVideoChannelUrl.="<tr>\n";
			$tempVideoChannelUrl.="	<td>\n";
			$tempVideoChannelUrl.="		<a href='".$directLinkData."'>".$directLinkData."</a>\n";
			$tempVideoChannelUrl.="	</td>\n";
			if (requireGroup("admin",false)){
				# if the user has admin permissions load the button that will add the channel
				$tempVideoChannelUrl.="	<td>\n";
				$tempVideoChannelUrl.="		<form action='/settings/admin.php' method='post'>\n";
				$tempVideoChannelUrl.="		<input width='60%' type='text' name='archiveVideoUrl' value='".$directLinkData."' hidden>\n";
				#
				$tempVideoChannelUrl.="		<input width='60%' type='text' name='archiveVideoUrl_title' value='".$archiveTitle."' hidden>\n";
				#$tempVideoChannelUrl.="		<input width='60%' type='text' name='archiveVideoUrl_plot' value='".$plotData."' hidden>\n";
				$tempVideoChannelUrl.="		<input width='60%' type='text' name='archiveVideoUrl_posterPath' value='".$posterPath."' hidden>\n";
				#
				$tempVideoChannelUrl.="			<button class='button' type='submit'>➕ Archive Video</button>\n";
				$tempVideoChannelUrl.="		</form>\n";
				$tempVideoChannelUrl.="	</td>\n";
			}

			$tempVideoChannelUrl.="</tr>\n";

			# build the remove button
			$tempVideoChannelUrl.="<tr>\n";
			$tempVideoChannelUrl.="<th>Cache Sum</th>\n";
			if (requireGroup("admin",false)){
				$tempVideoChannelUrl.="<th>Admin Actions</th>\n";
			}
			$tempVideoChannelUrl.="</tr>\n";
			if (file_exists($directLinkPath)){
				$tempVideoChannelUrl.="<tr>\n";
				$tempVideoChannelUrl.="	<td>\n";
				$tempVideoChannelUrl.="		<a href='".$jsonSum."'>".$jsonSum."</a>\n";
				$tempVideoChannelUrl.="	</td>\n";
				if (requireGroup("admin",false)){
					if (is_readable("/var/cache/2web/web/RESOLVER-CACHE/$jsonSum/")){
						# if the user has admin permissions load the button that will add the channel
						$tempVideoChannelUrl.="	<td>\n";
						$tempVideoChannelUrl.="		<form action='/settings/admin.php' method='post'>\n";
						$tempVideoChannelUrl.="			<input width='60%' type='text' name='removeCachedVideo' value='".$jsonSum."' hidden>\n";
						$tempVideoChannelUrl.="			<button class='button' type='submit'>🗑️ Remove Cached Video</button>\n";
						$tempVideoChannelUrl.="		</form>\n";
						$tempVideoChannelUrl.="	</td>\n";
					}
				}
				$tempVideoChannelUrl.="</tr>\n";
			}
		}
		$tempVideoChannelUrl.="</table>\n";
		#
		$videoChannelUrl=$tempVideoChannelUrl;
	}else{
		# get the video thumb path for the video player
		# - check for PNG and JPG versions
		$posterPath=str_replace(".php","-thumb.png",$_SERVER["SCRIPT_FILENAME"]);
		$posterPathJPG=str_replace(".php","-thumb.jpg",$_SERVER["SCRIPT_FILENAME"]);
		$moviePosterPath="poster.png";
		$moviePosterPathJPG="poster.jpg";
		if (file_exists($posterPath)){
			$posterPath=$proto.$_SERVER["HTTP_HOST"].str_replace($_SERVER["DOCUMENT_ROOT"],"",$posterPath);
		}else if (file_exists($posterPathJPG)){
			$posterPath=$proto.$_SERVER["HTTP_HOST"].str_replace($_SERVER["DOCUMENT_ROOT"],"",$posterPathJPG);
		}else if (file_exists($moviePosterPath)){
			$posterPath=$moviePosterPath;
		}else if (file_exists($moviePosterPathJPG)){
			$posterPath=$moviePosterPathJPG;
		}else{
			# show the spinner
			$posterPath="/loading.png";
		}
		$plotPath=$_SERVER["SCRIPT_FILENAME"].".plot";
		$plotData="";
		if (file_exists($plotPath)){
			$plotData.="<div class='plot'>\n";
			$plotData.=file_get_contents($plotPath);
			$plotData.="</div>\n";
		}
		# get the rating
		$ratingPath="grade.title";
		if (file_exists($ratingPath)){
			$ratingData=file_get_contents($ratingPath);
			if ( strlen($ratingData) > 0){
				$ratingText="<a class='button' href='/tags/?group=grade&tag=$ratingData'>Rating : $ratingData</a>";
			}else{
				$ratingText="<a class='button' href='/tags/?group=grade&tag=unknown'>Rating : UNRATED</a>";
			}
		}else{
			$ratingText="<a class='button' href='/tags/?group=grade&tag=unknown'>Rating : UNRATED</a>";
		}
	}

?>
<div class='titleCard'>
<h1>
<?PHP
	if(file_exists($jsonPath)){
		if(property_exists($jsonData, "age_limit")){
			if(($jsonData->age_limit) == 18){
				echo "🔞 ";
			}
		}
	}
	if (file_exists("show.title")){
		# write the data
		echo "<a href='/shows/".$showTitle."/?season=Season ".$seasonTitle."#Season ".$seasonTitle."'>".$showTitle."</a> $numericTitleData";
	}else if($useJson){
		# use the title data if this is a video in the cache
		echo $titleData;
	}else{
		# write the movie data
		echo "<a href='/movies/".$movieTitle."/'>".$movieTitle."</a>";
	}
?>
</h1>
<div class='listCard'>
<?PHP
	if (! file_exists("show.title")){
		# get the trailer if it is a movie
		$trailerPath="trailer.title";
		if (file_exists($trailerPath)){
			$trailerData=file_get_contents($trailerPath);
			if(stripos($trailerData,"plugin") !== false){
				# convert known kodi plugin links to trailers into real URLs
				$realTrailerUrl="https://www.youtube.com/watch?v=";
				$trailerData=str_replace("plugin://plugin.video.youtube/play/?video_id=",$realTrailerUrl,$trailerData);

			}
			# check if the web player is enabled
			if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
				echo "<a class='button' rel='noreferer' target='_new' href='/web-player.php?shareURL=$trailerData'>";
				echo "🔗 Trailer";
				echo "</a>";
			}else{
				echo "<a class='button' href='/exit.php?to=$trailerData'>";
				echo "🔗 Trailer";
				echo "</a>";
			}
		}
	}
	# get the production studio
	$studioPath="studio.title";
	if (file_exists($studioPath)){
		$studioData=file_get_contents($studioPath);
		if ( strlen($studioData) > 0){
			echo "<a class='button' href='/tags/?group=studio&tag=$studioData'>Studio : $studioData</a>";
		}
	}
	# draw the rating button
	echo $ratingText;
	# draw cache state for cached videos
	# - check if the cache was had issue and was delayed or broken
	$cacheDelayed=false;
	if ( $httpLink ){
		$pathPrefix=$_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".getCacheSum($directLinkData)."/";
		#
		if(file_exists($pathPrefix."verified.cfg")){
			echo "	<div class='button' title='Video is completely cached and ready for playback.'>Cache State <div class='radioIcon'>🟢</div></div>\n";
		}else if(file_exists($pathPrefix."video.mp4")){
			echo "	<div class='button' title='Final video is available but unverified.'>Cache State <div class='radioIcon'>🟡</div></div>\n";
		}else if(file_exists($pathPrefix."video.m3u")){
			#echo "	<div class='button' title='HLS stream is available for playback.'>Cache State <div class='radioIcon'>🟠</div></div>\n";
			echo "	<div class='button'>Cache State <img class='smallSpinner' src='/spinner.gif'></div>\n";
		}else{
			# check if the failed cache is older than 10 minutes
			if( is_readable($pathPrefix) and (time()-filemtime($pathPrefix) > 600) ){
				echo "	<div class='button' title='This video may not be caching properly.'>Cache State <div class='radioIcon'>⚠️</div></div>\n";
				$tempMessage="A video could not be cached after 10 minutes. This means the queue is running slow or that the media can not be cached.";
				addToLog("ERROR","Broken Video Link",$tempMessage);
				$cacheDelayed=true;
			}else if( is_readable($pathPrefix) and (time()-filemtime($pathPrefix) > 60) ){
				echo "	<div class='button' title='Cache is waiting for queue to open up...'>Cache State <div class='radioIcon'>⏳</div></div>\n";
			}else{
				echo "	<div class='button' title='Video is not being cached yet, Hit the play button to cache and play the video.'>Cache State <div class='radioIcon'>🔴</div></div>\n";
			}
		}
	}
	if (array_key_exists("play",$_GET)){
		# draw the fullscreen button
		echo "<button class='button' onclick='toggleFullscreen(\"video\");playVideo();'>⛶ Fullscreen</button>\n";
	}
	?>
</div>
</div>
<?PHP
		# if the cache has been delayed show a warning
		if($cacheDelayed){
			$startCacheTime=timeElapsedToHuman(filemtime($pathPrefix));
			echo "<br>\n";
			echo "<div class='warningBanner'>The media may not be caching correctly...<br>Caching was started $startCacheTime</div>\n";
			echo "<br>\n";
		}

		# flush output so far to the page
		clear();

		# check if the file exists and check the metadata
		if ($httpLink){
			# check the mimetype for the link
			$videoMimeType=cachedMimeType($directLinkData);
			#
			$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url="'.$directLinkData.'"';
			# attempt to verify the cached file
			verifyCacheFile("/var/cache/2web/web/RESOLVER-CACHE/$sum/",$directLinkData);
			#
			if( file_exists("/var/cache/2web/web/RESOLVER-CACHE/$sum/verified.cfg") ){
				# check if the cache file was just verified
				if( ( time() - filemtime("/var/cache/2web/web/RESOLVER-CACHE/$sum/verified.cfg") ) < 5){
					# if the file was verified less than 5 seconds ago reload the page to load the high res version
					reloadPage(5);
				}
			}
		}else{
			# if the trancode is enabled run the transcode job
			# - the transcoder will transcode all local videos for playback on webpages.
			if (isTranscodeEnabled()){
				# send the link to the resolver to transcode it using the same method as the resolver
				#$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/transcode.php?path="'.urlencode($directLinkData).'"';
				#$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?path='.urlencode($directLinkData);
				$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?path='.urlencode($directLinkData);
				#$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/ytdl-resolver.php?url='.($proto.$_SERVER["HTTP_HOST"].$directLinkData);
				# get mime data if the resolver has it cached
				#$videoMimeType=cachedMimeType(urlencode($directLinkData));
				$videoMimeType=cachedMimeType($directLinkData);
				# get the transcode path link
			}else{
				# build the full path on the server with the current protocol
				$fullPathVideoLink=str_replace("//","/", ($proto.$_SERVER["HTTP_HOST"]."/".$directLinkData) );
				$fullPathVideoLink=str_replace("http:/","http://", ($fullPathVideoLink) );
				$fullPathVideoLink=str_replace("https:/","https://", ($fullPathVideoLink) );
				# if no stream link exists then generate the mime type info from the file
				$tempVideoMimePath=str_replace("//","/", ($_SERVER["DOCUMENT_ROOT"].$directLinkData) );
				if ( is_readable($tempVideoMimePath) ){
					$videoMimeType=mime_content_type($tempVideoMimePath);
				}else{
					$videoMimeType="Missing File";
					errorBanner("The video is missing from the server!");
				}
			}
		}
		# pull the content type based on the mime data
		if (is_array($videoMimeType)){
			if (array_key_exists("Content-Type", $videoMimeType)){
				$videoMimeType=$videoMimeType["Content-Type"];
			}
		}
		# get the link to the cache location on the server
		if ($httpLink){
			$tempVideoLink=getCacheLink($directLinkData, $videoMimeType);
		}else{
			if (isTranscodeEnabled()){
				$tempVideoLink=getCacheLink(urlencode($directLinkData), $videoMimeType,"TRANSCODE-CACHE");
			}else{
				#$tempVideoLink=getCacheLink($directLinkData, $videoMimeType);
				$tempVideoLink=$directLinkData;
			}
		}
		#
		if (array_key_exists("play",$_GET)){
			# check if the video is still loading in the cache
			if (is_in_array("loading", $videoMimeType)){
				# reload the page if no mime type could be found for playback
				# - some videos will not generate a hls stream but will generate another playable
				#   stream eventually so the page will reload until it finds a playable one
				#echo "<div class='titleCard'>";
				#echo "Video is loading, page will automatically refresh...";
				#echo "</div>";
				echo "<video id='video' class='nfoMediaPlayer' poster='$posterPath' controls preload='auto' >\n";
				echo "	<source src='$fullPathVideoLink' type='video/mp4'>\n";
				echo "</video>\n";
				# reload the page after a 10 second delay
				reloadPage(10);
			}else{
				# draw the player based on the video link mime type
				if (is_in_array("video/mp4", $videoMimeType)){
					if (array_key_exists("loop",$_GET)){
						echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' autoplay loop controls preload='auto' >\n";
					}else{
						echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' autoplay controls preload='auto' >\n";
					}
					echo "	<source src='$fullPathVideoLink' type='video/mp4'>\n";
					echo "</video>\n";
				}else if (is_in_array("audio/mpeg", $videoMimeType)){
					if (array_key_exists("loop",$_GET)){
						echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' autoplay loop controls preload='auto' >\n";
					}else{
						echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls preload='auto' >\n";
					}
					echo "	<source src='$fullPathVideoLink' type='audio/mpeg'>\n";
					echo "</audio>\n";
				}else if (is_in_array("video/webm", $videoMimeType)){
					if (array_key_exists("loop",$_GET)){
						echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' autoplay loop controls  preload='auto' >\n";
					}else{
						echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls  preload='auto' >\n";
					}
					echo "	<source src='$fullPathVideoLink' type='video/webm'>\n";
					echo "</audio>\n";
				}else if (is_in_array("video/ogg", $videoMimeType)){
					if (array_key_exists("loop",$_GET)){
						echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' autoplay loop controls  preload='auto' >\n";
					}else{
						echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls  preload='auto' >\n";
					}
					echo "	<source src='$fullPathVideoLink' type='video/ogg'>\n";
					echo "</audio>\n";
				}else if (is_in_array("application/mpegurl", $videoMimeType)){
					# hls stream
					echo "<div id='mediaPlayerContainer'>\n";
					echo "</div>\n";
					# draw the hls stream player webpage player
					echo "<script>\n";
					# remove existing video and replace it with a hls stream
					#echo "	document.write(\"<video id='video' class='livePlayer' poster='$posterPath' controls></video>\");\n";
					echo "	var activeBuffering=false;\n";
					echo "	var videoObj = document.createElement(\"video\");\n";
					echo "	videoObj.setAttribute(\"id\", \"video\");\n";
					echo "	videoObj.setAttribute(\"class\", \"livePlayer\");\n";
					echo "	videoObj.setAttribute(\"poster\", \"$posterPath\");\n";
					echo "	videoObj.setAttribute(\"controls\", \"true\");\n";
					echo "	videoObj.setAttribute(\"autoplay\", \"true\");\n";
					echo "	window.mediaPlayerContainer.appendChild(videoObj);\n";
					# set the global variables
					echo "	var currentPlaybackTime;\n";
					echo "	var videoIsPaused = false;\n";
					echo "	var bufferSleepTime;\n";
					# check if the hls player is supported
					echo "	if(Hls.isSupported()) {\n";
					echo "		var hls = new Hls({\n";
					echo "			startPosition: 0,\n";
					echo "			enableWebVTT: true,\n";
					echo "			enableWorker: true,\n";
					echo "			enableSoftwareAES: true,\n";
					echo "			autoStartLoad: true,\n";
					echo "			maxBufferLength: 300,\n";
					#echo "			maxBufferLength: 0,\n";
					#echo "			maxBufferLength: 1000,\n";
					#echo "			backBufferLength: 1000,\n";
					#echo "			lowLatencyMode: false\n";
					echo "			debug: false,\n";
					#echo "			debug: true\n";
					echo "		});\n";
					echo "		hls.loadSource('$tempVideoLink');\n";
					echo "		hls.attachMedia(video);\n";
					echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
					##echo"	echo \"			document.video.muted = false;\";"
					#echo "			if(window.video.paused){\n";
					#echo "				pauseVideo();\n";
					#echo "			}else{\n";
					#echo "				playVideo();\n";
					#echo "			}\n";
					echo "			playVideo();\n";
					echo "		});\n";
					echo "	}else if (window.video.canPlayType('application/vnd.apple.mpegurl')) {\n";
					echo "		window.video.src = '$tempVideoLink';\n";
					echo "		window.video.addEventListener('canplay',function() {\n";
					echo "			window.video.play();\n";
					echo "		});\n";
					echo "	}\n";
					# forcefully reload the video	for when hls.recoverMediaError() does not work
					echo "	function forceReloadVideo(){\n";
					# only allow one reload video function to run at a single time
					echo "		if(activeBuffering == true){\n;";
					echo "			console.log('Could not force reload the video a active buffer was found.');\n";
					echo "			return true;\n;";
					echo "		}else{\n;";
					echo "			activeBuffering=true;\n";
					echo "			console.log('No Active buffer, forcefully reloading the video.');\n";
					echo "		}\n;";
					echo "		pauseVideo();\n";
					echo "		window.video.controls=false;\n";
					echo "		window.video.poster=\"/loading.png\";\n";
					echo "		currentPlaybackTime=window.video.currentTime;\n";
					# get the currently buffered time
					echo "		bufferedTime=window.video.duration;\n";
					echo "		console.log(bufferedTime);\n";
					echo "		hls.loadSource('$tempVideoLink');\n";
					echo "		hls.attachMedia(window.video);\n";
					echo "		window.video.currentTime=currentPlaybackTime;\n";
					echo "		window.video.poster=\"$posterPath\";\n";
					echo "		window.video.controls=true;\n";
					echo "		playVideo();\n";
					# reset the buffer sleep time
					echo "		bufferSleepTime=0;\n";
					echo "		activeBuffering=false;\n";
					echo "	}\n";
					# the reload video function
					echo "	function reloadVideo(sleepTime=-1){\n";
					# only allow one reload video function to run at a single time
					echo "		if(activeBuffering == true){\n;";
					echo "			console.log('Could not reload the video a active buffer was found.');\n";
					echo "			return true;\n;";
					echo "		}else{\n;";
					echo "			activeBuffering=true;\n";
					echo "			console.log('No Active buffer, reloading the video.');\n";
					echo "		}\n;";
					# overwrite the global value if one is given directly
					echo "		if (sleepTime>=0){\n;";
					echo "			bufferSleepTime=sleepTime;\n;";
					echo "		}\n;";
					echo "		window.video.controls=false;\n";
					# add the loading spinner
					echo "		var loadingObj = document.createElement(\"img\");\n";
					echo "		loadingObj.setAttribute(\"id\", \"loadingSpinner\");\n";
					echo "		loadingObj.setAttribute(\"src\", \"/spinner.gif\");\n";
					##echo "		var loadingObj = document.createElement(\"div\");\n";
					##echo "		loadingObj.setAttribute(\"id\", \"loadingSpinner\");\n";
					##echo "		loadingObj.setAttribute(\"class\", \"spinRight\");\n";
					##echo "		loadingObj.innerHTML=\"🗘\";\n";
					##echo "		window.mediaPlayerContainer.appendChild(loadingObj);\n";
					# insert the loading element before the video player so it acts as a overlay
					echo "		window.mediaPlayerContainer.insertBefore(loadingObj,window.video);\n";
					# log
					echo "		console.log(bufferSleepTime);\n";
					# the sleep time for the buffering of a video is a exponental curve of wait time
					# - the user should experience none of this because the video should keep playing
					#   until the buffer time has been activated
					##echo "		bufferSleepTime=(bufferSleepTime * 2)\n";
					##echo "		console.log(bufferSleepTime);\n";
					# delay the reload by 10 seconds
					#echo "		currentPlaybackTime=0;\n";
					echo "		videoIsPaused = window.video.paused;\n";
					echo "		window.video.poster=\"/loading.png\";\n";
					#
					#echo "		currentPlaybackTime=window.video.currentTime;\n";
					echo "		pauseVideo();\n";
					## reload the video to reshow the poster image
					##
					##echo "		hls.loadSource('$tempVideoLink');\n";
					##echo "		hls.attachMedia(video);\n";
					##
					#echo "		window.video.poster=\"/spinner.gif\";\n";
					# pause the video to keep it from playing during buffer
					#echo "		pauseVideo();\n";
					##echo "		if(videoIsPaused){\n";
					##echo "			pauseVideo();\n";
					##echo "		}else{\n";
					##echo "			playVideo();\n";
					##echo "		}\n";
					##echo "		notify(\"🗘\",".(1000 * 1).",\"spinRight\");\n";
					## reset the playback time to zero in order to display the poster
					##echo "		currentPlaybackTime=0;\n";
					## set the poster to the loading spinner
					## get the current playback time
					echo "		setTimeout(function() {\n";
					##echo "			if( video.currentTime > ( currentPlaybackTime + sleepTime ) ){\n";
					##echo "				document.video.currentTime == ( currentPlaybackTime + sleepTime );\n";
					##echo "			}\n";
					#echo "			hls.loadSource('$tempVideoLink');\n";
					#echo "			hls.attachMedia(window.video);\n";
					#echo "			hls.recoverMediaError();\n";
					echo "			hls.startLoad();\n";
					# set the poster back to the poster
					echo "			window.video.poster=\"$posterPath\";\n";
					# seek back to the same playback time and resume the video
					#echo "			window.video.currentTime=currentPlaybackTime;\n";
					# remove the spinner
					echo "			if(videoIsPaused){\n";
					echo "				pauseVideo();\n";
					echo "			}else{\n";
					echo "				playVideo();\n";
					echo "			}\n";
					# remove all loading spinners generated by buffer events
					#echo "			while(window.loadingSpinner == null){;\n";
					echo "				window.loadingSpinner.remove();\n";
					#echo "			}\n";
					echo "			window.video.controls=true;\n";
					echo "			activeBuffering=false;\n";
					#echo "		document.getElementById(\"notification\").remove();\n";
					#echo "		},(1000*bufferSleepTime));\n";
					echo "		},(1000*bufferSleepTime));\n";
					echo "		console.log(bufferSleepTime);\n";
					echo "	}\n";
					# get the current time
					#echo "	function getCurrentTime(){\n";
					#echo "		var resetTime;\n";
					#echo "		resetTime=new Date();\n";
					#echo "		resetTime=resetTime.getSeconds();\n";
					#echo "		return resetTime;\n";
					#echo "	}\n";
					#echo "	var resetTime = getCurrentTime();\n";
					# create the global failure count
					echo "	var failed_video_playback_count=0;\n";
					# use sleep time to increse the buffering time given on a error
					echo "	bufferSleepTime=1;\n";
					# add error catching code and reload the page if the HLS stream stops working
					echo "	hls.on(Hls.Events.ERROR, function (event, data){\n";
					# log the error in the browser console for debugging
					echo "		console.log(event)\n";
					echo "		console.log(data)\n";
					# prevent the video from reloading more than once every 30 seconds
					#echo "		if( ( getCurrentTime() - resetTime ) > 30 ){\n";
					#echo "			resetTime=new Date();\n";
					#echo "			resetTime=resetTime.getSeconds();\n";
					# reload the video
					##echo "		if(activeBuffering == false){\n;";
					##echo "			console.log('No Active buffer, reloading the video.');\n";
					##echo "			activeBuffering=true;\n;";
					#echo "			console.log(event)\n";
					#echo "			console.log(data)\n";
					#echo "			reloadVideo();\n";
					# look for buffer errors that are recoverable from
					##echo "			if(event.error.details==\"bufferStalledError\"){\n";
					##echo "					console.log(event)\n";
					##echo "					reloadVideo(1);\n";
					##echo "			}else if(event.error.details==\"bufferAppendError\"){\n";
					##echo "					console.log(event)\n";
					##echo "					reloadVideo(1);\n";
					##echo "			}else{\n";
					## only reload on fatal unknown errors
					###echo "				if(data.fatal){\n";
					##echo "					reloadVideo(3);\n";
					##echo "					bufferSleepTime+=1;\n";
					###echo "				}\n";
					##echo "			}\n";
					##echo "		}\n";
					##echo "		}\n";
					# reload the page if the playback fails 100 times
					# limit buffer sleeping time to 15 seconds
					##echo "		console.log(event.error.name);\n";
					##echo "		if(event.error.name == \"QuotaExceededError\"){\n";
					##echo "			console.log(\"Browser does not want to load a larger buffer.\");\n";
					##echo "		}else{\n";#
					##echo "			if(bufferSleepTime < 5){\n";
					##echo "				bufferSleepTime+=1;\n";
					##echo "				console.log(event);\n";
					##echo "				console.log(data);\n";
					##echo "				forceReloadVideo();\n";
					###echo "				reloadVideo();\n";
					##echo "			}else{\n";#
					### force reload of the video completely
					##echo "				forceReloadVideo();\n";
					##echo "			}\n";
					##echo "		}\n";
					#echo "		forceReloadVideo();\n";
					echo "		reloadVideo(0);\n";
					echo "		failed_video_playback_count+=1;\n";
					echo "		console.log('failed_video_playback_count:'+failed_video_playback_count);\n";
					#echo "		}\n";
					echo "	});\n";
					# start playback on page load
					echo "	hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
					#echo "	}\n";
					echo "</script>\n";
					# draw the fake video player
					echo "<noscript>\n";
					echo "	<div class='videoPosterContainer'>\n";
					echo "		<img class='videoPoster' src='$posterPath' />\n";
					echo "	</div>\n";
					echo "</noscript>\n";
					# refresh if javascript is disabled
					noscriptRefresh(10);
				}else{
					# This is a unsupported media file that can not be played with the web player
					# - This is the message displayed when a local media type is given but transcoding is disabled
					echo "<div class='titleCard'>\n";
					echo "	<div class='videoPosterContainer'>";
					echo "		<img class='videoPoster' src='$posterPath' />";
					echo "	</div>";
					echo "	<div class='titleCard'>\n";
					echo "		The server administrator has disabled video transcoding.<br>\n";
					echo "		Video player can not currently play this file type '$videoMimeType'.<br>\n";
					echo "		Use the direct links or external links below to download or access media with a external application.<br>\n";
					echo "		The help page contains more infomation about direct links.<br>\n";
					echo "		<hr>\n";
					echo "		<a class='button' href='/help.php#direct_linking'><span class='helpQuestionMark'>?</span> Direct Link Help</a>\n";
					echo "		<hr>\n";
					echo "	</div>\n";
					echo "</div>\n";
				}
			}

		}else{
			#echo "<a class='loadVideoButton' href='?play'><img src='$posterPath'><div>⯈</div></a>\n";
			#echo "<a class='loadVideoButton' href='?play' style='background: url(\"$posterPath\")'>⯈</a>\n";
			echo "<a class='loadVideoButton' href='?play' style='background: url(\"$posterPath\")'>▷</a>\n";
		}
	clear();
	?>
<div id='pageContent' class='settingListCard descriptionCard'>
<h2>
<?PHP
	echo $titleData;
?>
</h2>
<?PHP
	# draw the Copy Link Button
	$copyLinkText="";
	$copyLinkText .= "<pre>\n";
	if ( $httpLink ){
		$copyLinkText .= "$directLinkData\n";
	}else{
		$copyLinkText .= "$fullPathVideoLink\n";
	}
	# if the direct link is not a http external link add a direct download button for downloading from this server
	#$copyLinkText .= "<button class='copyButton hardLink' onclick='copyToClipboard(\"$directLinkData\");'>\n";
	#$copyLinkText .= "</button>\n";
	$copyLinkText .= "</pre>\n";
	# draw the copy link
	echo "$copyLinkText";
	# write out the aired date if it exists
	$datePath=$_SERVER["SCRIPT_FILENAME"].".date";
	$metaLeft="";
	$metaExists=false;
	if (file_exists($datePath)){
		$metaLeft .= "<tr>";
		$metaLeft .= "	<td>Aired</td>\n";
		$metaLeft .= "	<td>".file_get_contents($datePath)."</td>\n";
		$metaLeft .= "</tr>\n";
		$metaExists=true;
	}
	# write out the video duration if it exists
	$durationPath=$_SERVER["SCRIPT_FILENAME"].".duration";
	if (file_exists($durationPath)){
		$metaLeft .= "<tr>";
		$metaLeft .= "	<td>Duration</td>\n";
		$metaLeft .= "	<td>".timeToHuman(file_get_contents($durationPath))."</td>\n";
		$metaLeft .= "</tr>\n";
		$metaExists=true;
	}
	if($metaExists){
		echo "<table class='aired'>\n";
		echo "$metaLeft";
		echo "</table>\n";
	}
	echo "<div class='hardLink'>\n";
	# draw the direct links
	echo "<div>\n";
	# throw external links into the exit gate
	if ( $httpLink ){
		echo "<a class='button hardLink' rel='noreferer' href='/exit.php?to=".$directLinkData."'>\n";
	}else{
		echo "<a class='button hardLink' rel='noreferer' href='".$directLinkData."'>\n";
	}
	echo "🔗Direct Link\n";
	echo "</a>\n";
	echo "</div>\n";
	$downloadLinkText="";
	# if the direct link is not a http external link add a direct download button for downloading from this server
	if ( in_array(substr($tempVideoLink,-4),Array(".mp4",".mkv",".mp3",".mov",".avi",".avi")) or in_array(substr($tempVideoLink,-3),Array(".ts")) or in_array(substr($tempVideoLink,-5),Array(".webm")) ){
		# look if the downloadable file is available
		if (is_readable("/var/cache/2web/web".$tempVideoLink)){
			# get the downloadable file size
			$downloadFileSize=bytesToHuman(filesize("/var/cache/2web/web".$tempVideoLink));
		}else{
			# the size could not be determined because no file is cached
			$downloadFileSize="?";
		}
		$downloadLinkText .= "<div>\n";
		#$downloadLinkText .= "<a class='button hardLink' rel='noreferer' href='".$tempVideoLink."' download='$archiveTitle'>\n";
		$downloadLinkText .= "<a class='button hardLink' onclick='notify(\"🡇\");' href='".$tempVideoLink."' download='$archiveTitle'>\n";
		$downloadLinkText .= "<span class='downloadIcon'>🡇</span>Download $downloadFileSize\n";
		$downloadLinkText .= "</a>\n";
		$downloadLinkText .= "</div>\n";
	}
	# build the cache links
	if ( $httpLink ){
		# if the cache link is a external link that means it is a real cache link
		echo "<div>\n";
		echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".$directLinkData."'>\n";
		echo "📥Cache Link\n";
		echo "</a>\n";
		echo "</div>\n";
		#
		#$downloadLinkText .= "<div>\n";
		#$downloadLinkText .= "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".$directLinkData."' download='$archiveTitle'>\n";
		#$downloadLinkText .= "<span class='downloadIcon'>🡇</span> Cache Download Link\n";
		#$downloadLinkText .= "</a>\n";
		#$downloadLinkText .= "</div>\n";
	}else{
		# only draw a cache link for local links if the transcoder is enabled
		if (isTranscodeEnabled()){
			# this is a local link so show the transcode cache link unless transcoding is disabled
			echo "<div>\n";
			#echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/transcode.php?path=".$directLinkData."'>\n";
			#echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".($proto.$_SERVER["HTTP_HOST"].$directLinkData)."'>\n";
			echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?path=".urlencode($directLinkData)."'>\n";
			echo "📥Cache Link\n";
			echo "</a>\n";
			echo "</div>\n";
			#
			#$downloadLinkText .= "<div>\n";
			#$downloadLinkText .= "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/transcode.php?path=".$directLinkData."' download='$archiveTitle'>\n";
			#$downloadLinkText .= "<span class='downloadIcon'>🡇</span> Cache Download Link\n";
			#$downloadLinkText .= "</a>\n";
			#$downloadLinkText .= "</div>\n";
		}
	}
	# build the continue playing playlist links
	if (file_exists("show.title")){
		echo "<div>\n";
		echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".str_replace(" ","%20",$showTitle)."'>\n";
		echo "🔁 Continue<sup>External</sup>\n";
		echo "</a>\n";
		echo "</div>\n";
	}
	# draw the download links to the page
	echo "$downloadLinkText";
	$userClientName="";
	if (array_key_exists("selectedRemote",$_SESSION)){
		if ($_SESSION["selectedRemote"] == "CLIENT"){
			$userClientName="client";
		}else{
			$userClientName="kodi";
		}
	}else{
		$userClientName="select";
	}
	if ($userClientName == "client"){
		# if the client is enabled
		if (yesNoCfgCheck("/etc/2web/client.cfg")){
			# if the group permissions are available for the current user
			if (requireGroup("clientRemote", false)){
				if ($videoMimeType == "application/mpegurl"){
					$playbackType="stream";
				}else if ($videoMimeType == "video/mp4"){
					$playbackType="play";
				}else if ($videoMimeType == "video/webm"){
					$playbackType="play";
				}else if ($videoMimeType == "audio/mpeg"){
					$playbackType="audio";
				}else{
					$playbackType="unsupported=$videoMimeType&";
				}
				$mimeData="?mime=".$playbackType."&";
				# draw the button for playing videos across all the clients
				echo "<div>";
				# build the play on client link
				$tempPathPrefix=$proto.$_SERVER["HTTP_HOST"];
				#echo "<a class='button hardLink' target='_new' href='/client/?play=".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".$directLinkData."'>\n";
				if( (stripos( $directLinkData , "http://" ) !== false) or (stripos( $directLinkData , "https://" ) !== false) ){
					echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/client/$mimeData$playbackType=".urlencode("$tempPathPrefix/ytdl-resolver.php?url=".$directLinkData)."'>\n";
				}else{
					#echo "<a class='button hardLink' target='_new' href='/client/?play=/ytdl-resolver.php?url=http://".$directLinkData."'>\n";
					#echo "<a class='button hardLink' target='_new' href='/client/$mimeData$playbackType=".urlencode("$tempPathPrefix/ytdl-resolver.php?url=".$proto.$_SERVER["HTTP_HOST"]."/".$directLinkData)."'>\n";
					echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/client/$mimeData$playbackType=".urlencode("$tempPathPrefix/ytdl-resolver.php?url=".$directLinkData)."'>\n";
				}
				echo "🎟️ Play on Client\n";
				echo "</a>\n";
				echo "</div>\n";
			}
		}
	}else if ( $userClientName == "kodi" ){
		# check if the play on kodi button is enabled in the settings
		if (yesNoCfgCheck("/etc/2web/kodi/playOnKodiButton.cfg")){
			# check if the user has permissisons to access these buttons
			if (requireGroup("kodi2web", false)){
				echo "<div>";
				# build the play on kodi links
				if (file_exists("show.title")){
					# if this is a .strm link it should always be passed though the resolver
					# - The resolver will cache direct links to media files
					# - The resolver will resolve webpages that contain media links
					# - using a self signed cert will cause this to fail unless you setup all
					#   kodi clients with the public cert so this is currently disabled by default
					if($httpLink){
						echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/kodi-player.php?shareURL=".str_replace(" ","%20","$directLinkData")."'>\n";
					}else{
						echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/kodi-player.php?url="."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20","$directLinkData")."'>\n";
					}
				}else{
					# this is a movie
					# - using a self signed cert will cause this to fail unless you setup all
					#   kodi clients with the public cert so this is currently disabled by default
					if($httpLink){
						echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/kodi-player.php?shareURL=".str_replace(" ","%20","$directLinkData")."'>\n";
					}else{
						echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/kodi-player.php?url="."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20","$directLinkData")."'>\n";
					}
				}
				echo "🇰Play on KODI\n";
				echo "</a>\n";
				echo "</div>\n";
			}
		}
	}else if ( $userClientName == "select" ){
		if ($videoMimeType == "application/mpegurl"){
			$playbackType="stream";
		}else if ($videoMimeType == "video/mp4"){
			$playbackType="play";
		}else if ($videoMimeType == "video/webm"){
			$playbackType="play";
		}else if ($videoMimeType == "audio/mpeg"){
			$playbackType="audio";
		}else{
			$playbackType="unsupported=$videoMimeType&";
		}
		echo "<div>\n";
		echo "<a onclick='pauseVideo();delayedRefresh(1);' class='button hardLink' target='_new' href='/remote.php?mime=$playbackType&play=".urlencode($directLinkData)."'>\n";
		echo "🎛️ Select Your Remote\n";
		echo "</a>\n";
		echo "</div>\n";
	}
	echo "<div>";
	# add code to turn looping the video on or off
	if (array_key_exists("loop",$_GET)){
		echo "		<a class='hardLink button' href='?play'>\n";
		echo "			① Play Once\n";
		echo "		</a>\n";
	}else{
		echo "		<a class='hardLink button' href='?play&loop'>\n";
		echo "			∞ Loop Playback\n";
		echo "		</a>\n";
	}
	if( $httpLink ){
		# if the web player is enabled
		if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
			# if the local path is not to the web player
			if(! (stripos($_SERVER["SCRIPT_FILENAME"],"/web_player/") !== false) ){
				if(requireGroup("webPlayer", false)){
					# if the user has web player permissions check if a link can be created
					if(is_url($directLinkData)){
						echo "	<div>\n";
						echo "		<a class='hardLink button' href='/web-player.php?shareURL=$directLinkData'>\n";
						echo "			📥 Add To Web Player\n";
						echo "		</a>\n";
						echo "	</div>\n";
					}
				}
			}
		}
	}
	echo "</div>\n";
	echo "</div>\n";
?>
<?PHP
	# write the plot data
	echo $plotData;
	# write the video channel url if the json is loaded
	echo $videoChannelUrl;
	# send data to the client
	clear();
?>
</div>
<?PHP
	# draw the video preview thumbnails if they are found
	$previewExists=false;
	$previewOutput="";
	$previewOutput.="<details class='titleCard'>\n";
	$previewOutput.= "<summary>\n";
	$previewOutput.= "<h2>Video Previews</h2>\n";
	$previewOutput.= "</summary>\n";
	$previewOutput.= "<div class='listCard'>\n";
	foreach(Array(1,2,3,4,5,6,7,8) as $previewNumber){
		$tempPath=str_replace(".php","",$_SERVER["SCRIPT_FILENAME"]);
		$tempPath=basename($tempPath);
		$tempPath=$tempPath."_preview_".$previewNumber.".png";
		if(is_readable($tempPath)){
			$previewExists=true;
			$previewOutput.="<img class='videoPreview' src='$tempPath'>\n";
		}
	}
	$previewOutput.="</details>\n";
	if($previewExists){
		echo $previewOutput;
	}
?>
<?PHP
# write the admin data if it exists
echo $adminData;

# build the auto hide controls timer in javascript
?>
<script>
	var controlHideTimeout;
	//window.video.addEventListener("mousemove", function() {
	window.addEventListener("mousemove", function() {
		// show the moved cursor and video controls
		document.body.style.cursor="default";
		window.video.controls=true;
		window.clearTimeout(controlHideTimeout);
		console.log("Mouse moved Unhide the mouse/controls");
		// hide the cursor and video controls after 2 seconds of inactivity
		controlHideTimeout = setTimeout(() =>{
			console.log("Hide the mouse/controls when inactive");
			window.video.controls=false;
			document.body.style.cursor="none";
		}, 2000);
	});
	// end of playback function
	function playbackEnd(){
		closeFullscreen();
		// show the notification
		notify("🔚");
		// disable autoplay
		document.getElementById('video').autoplay=false;
		// reload the video element
		document.getElementById('video').load();
		console.log("End of playback reached!");
	}
<?PHP
# only activate playback end event if video looping is disabled
if(! isset($_GET["loop"])){
	# end of playback event
	echo "document.getElementById('video').addEventListener('ended',playbackEnd,false);";
}
?>
</script>
<?PHP
# send current data and draw the widgets
clear();
loadSearchIndexResults($titleData,"shows",9,"Episodes");
loadSearchIndexResults($titleData,"shows",8,"Shows");
loadSearchIndexResults($titleData,"movies");
#
drawMoreSearchLinks($titleData);
?>
<hr>
<?PHP
	clear();
	include("/usr/share/2web/templates/footer.php");
?>
</body>
</html>

