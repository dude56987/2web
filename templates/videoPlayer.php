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
	}else{
		requireGroup("nfo2web");
	}
?>
<!--
########################################################################
# 2web video player for nfo2web
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
########################################################################
function cachedMimeType($videoLink){
	# return the path to a found cache link once it is available

	# set the default web directory
	$webDirectory="/var/cache/2web/web";
	# cleanup the video link
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);
	# get the sum
	$sum = hash("sha512",$videoLink,false);
	#
	addToLog("DEBUG","videoPlayer.php","Creating sum '$sum' from link '$videoLink'\n");
	$maxSleep=10;
	$sleepCounter=20;
	# wait for either the bump or the file to be downloaded and redirect
	while(true){
		if(file_exists("$webDirectory/RESOLVER-CACHE/$sum/verified.cfg")){
			if((file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mp3"))){
				return ("audio/mpeg");
			}else if((file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.mp4"))){
				return ("video/mp4");
			}else{
				return ("application/mpegurl");
			}
		}else if((file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.webm")) and (substr($_SERVER["HTTP_USER_AGENT"],0,4) == "Kodi") and ( ( time() - filemtime($webDirectory."/RESOLVER-CACHE/".$sum."/video.webm") ) > 90) ){
			return ("video/webm");
		}else if( file_exists("$webDirectory/RESOLVER-CACHE/$sum/video.m3u") and file_exists("$webDirectory/RESOLVER-CACHE/$sum/video-stream0.ts") ){
			return ("application/mpegurl");
		}
		$maxSleep+=1;
		if ($maxSleep > $sleepCounter){
			$sleepCounter+=1;
		}else{
			# if no media can be resolved return loading as the metadata type
			return ("loading");
		}
		sleep(1);
	}
}
########################################################################
function getCacheLink($videoLink,$mimeType){
	# return the path to a found cache link once it is available

	# set the default web directory
	$webDirectory="/var/cache/2web/web";
	# cleanup the video link
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);
	# get the sum
	$sum = hash("sha512",$videoLink,false);
	#
	if ("video/mp4" == $mimeType){
		return ("/RESOLVER-CACHE/$sum/video.mp4");
	}else if ("video/webm" == $mimeType){
		return ("/RESOLVER-CACHE/$sum/video.webm");
	}else if ("audio/mpeg" == $mimeType){
		return ("/RESOLVER-CACHE/$sum/video.mp3");
	}else if ("application/mpegurl" == $mimeType){
		return ("/RESOLVER-CACHE/$sum/video.m3u");
	}else if ("video/ogg" == $mimeType){
		return ("/RESOLVER-CACHE/$sum/video.ogg");
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
#
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
<?PHP
#################################################################################
function isTranscodeEnabled(){
	$doTranscode = False;
	# check if the transcode is enabled
	if (file_exists("/etc/2web/transcodeForWebpages.cfg")){
		$selected=file_get_contents("/etc/2web/transcodeForWebpages.cfg");
		if ($selected == "yes"){
			$doTranscode = True;
		}
	}
	return $doTranscode;
}
#################################################################################
function noscriptRefresh($seconds=10){
	echo "<noscript>";
	echo "<div class='titleCard'>";
	echo "<p>The video is still loading...</p>";
	echo "<p>The page will refresh when the player loads...</p>";
	echo "<meta http-equiv='refresh' content='$seconds'>";
	echo "</div>";
	echo "</noscript>";
}
#################################################################################
?>
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
	if(document.getElementById("video") == document.activeElement){
		// check for key controls on the video player
		const key = event.key;
		switch (key){
			case "Insert":
			event.preventDefault();
			event.stopImmediatePropagation();
			toggleFullscreen("video");
			break;
			case "ArrowDown":
			event.preventDefault();
			event.stopImmediatePropagation();
			volumeDown();
			break;
			case "ArrowUp":
			event.preventDefault();
			event.stopImmediatePropagation();
			volumeUp();
			break;
			case "ArrowRight":
			event.preventDefault();
			event.stopImmediatePropagation();
			seekForward();
			break;
			case "ArrowLeft":
			event.preventDefault();
			event.stopImmediatePropagation();
			seekBackward();
			break;
		}
	}
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
		$sum = hash("sha512",$directLinkData,false);
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
	# this should always be set even if it is empty
	$videoChannelUrl="";
	# load data from the json file
	if(file_exists($jsonPath)){
		$jsonData=json_decode(file_get_contents($jsonPath));
		# get the title
		if(property_exists($jsonData, "title")){
			$titleData=$jsonData->title;
		}
		#
		if(property_exists($jsonData, "age_limit")){
			$ratingText="<span class='button'>Required Viewing Age: ".$jsonData->age_limit."</span>";
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

				if (file_exists("show.title")){
					# write the data
					$archiveTitle="$showTitle $numericTitleData";
				}else if($useJson){
					# use the title data if this is a video in the cache
					$archiveTitle=$titleData;
				}else{
					# write the movie data
					$archiveTitle=$movieTitle;
				}
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
			$posterPath="/spinner.png";
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
		echo "<a href='/shows/".$showTitle."/?search=".$seasonTitle."#Season ".$seasonTitle."'>".$showTitle."</a> $numericTitleData";
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
			# check if the web player is enabled
			if (yesNoCfgCheck("/etc/2web/webPlayer.cfg")){
				echo "<a class='button' rel='noreferer' target='_new' href='/web-player.php?shareURL=$trailerData'>";
				echo "🔗 Trailer";
				echo "</a>";
			}else{
				echo "<a class='button' rel='noreferer' target='_new' href='$trailerData'>";
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

	?>
</div>
</div>
<?PHP
		# flush output so far to the page
		flush();
		ob_flush();

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
				$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].'/transcode.php?path="'.urlencode($directLinkData).'"';
				# get mime data if the resolver has it cached
				#$videoMimeType=cachedMimeType(urlencode($directLinkData));
				$videoMimeType=cachedMimeType($directLinkData);
			}else{
				# build the full path on the server with the current protocol
				$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"]."/".$directLinkData;
				# if no stream link exists then generate the mime type info from the file
				$videoMimeType=mime_content_type(str_replace("//","/", ($_SERVER["DOCUMENT_ROOT"].$directLinkData) ));
			}
		}
		# pull the content type based on the mime data
		if (is_array($videoMimeType)){
			if (array_key_exists("Content-Type", $videoMimeType)){
				$videoMimeType=$videoMimeType["Content-Type"];
			}
		}
		# get the link to the cache location on the server
		$tempVideoLink=getCacheLink($directLinkData, $videoMimeType);
		# check if the video is still loading in the cache
		if (is_in_array("loading", $videoMimeType)){
			# reload the page if no mime type could be found for playback
			# - some videos will not generate a hls stream but will generate another playable
			#   stream eventually so the page will reload until it finds a playable one
			echo "<div class='titleCard'>";
			echo "Video is loading, page will automatically refresh...";
			echo "</div>";
			echo "<script>\n";
			echo "	notify(\"Loading Video...\",10000);\n";
			echo "</script>\n";
			echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='video/mp4'>\n";
			echo "</video>\n";
			# reload the page after a 10 second delay
			reloadPage(10);
		}else{
			# draw the player based on the video link mime type
			if (is_in_array("video/mp4", $videoMimeType)){
				if (array_key_exists("loop",$_GET)){
					echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' autoplay loop controls>\n";
				}else{
					echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
				}
				echo "	<source src='$fullPathVideoLink' type='video/mp4'>\n";
				echo "</video>\n";
			}else if (is_in_array("audio/mpeg", $videoMimeType)){
				if (array_key_exists("loop",$_GET)){
					echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' autoplay loop controls>\n";
				}else{
					echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls>\n";
				}
				echo "	<source src='$fullPathVideoLink' type='audio/mpeg'>\n";
				echo "</audio>\n";
			}else if (is_in_array("video/webm", $videoMimeType)){
				if (array_key_exists("loop",$_GET)){
					echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' autoplay loop controls>\n";
				}else{
					echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls>\n";
				}
				echo "	<source src='$fullPathVideoLink' type='video/webm'>\n";
				echo "</audio>\n";
			}else if (is_in_array("video/ogg", $videoMimeType)){
				if (array_key_exists("loop",$_GET)){
					echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' autoplay loop controls>\n";
				}else{
					echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls>\n";
				}
				echo "	<source src='$fullPathVideoLink' type='video/ogg'>\n";
				echo "</audio>\n";
			}else if (is_in_array("application/mpegurl", $videoMimeType)){
				# hls stream
				# draw the hls stream player webpage player
				echo "<script>\n";
				echo "document.write(\"<video id='video' class='livePlayer' poster='$posterPath' controls></video>\");\n";
				echo "	if(Hls.isSupported()) {\n";
				echo "		var video = document.getElementById('video');\n";
				echo "		var hls = new Hls({\n";
				echo "			startPosition: 0,\n";
				echo "			enableWebVTT: true,\n";
				echo "			enableWorker: true,\n";
				echo "			enableSoftwareAES: true,\n";
				echo "			autoStartLoad: true,\n";
				echo "			debug: true\n";
				echo "		});\n";
				echo "		hls.loadSource('$tempVideoLink');\n";
				echo "		hls.attachMedia(video);\n";
				echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
				#echo"	echo \"			video.muted = false;\";"
				echo "			video.play();\n";
				echo "		});\n";
				echo "	}\n";
				echo "	else if (video.canPlayType('application/vnd.apple.mpegurl')) {\n";
				echo "		video.src = '$tempVideoLink';\n";
				echo "		video.addEventListener('canplay',function() {\n";
				echo "			video.play();\n";
				echo "		});\n";
				echo "	}\n";
				# start playback on page load
				echo "hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
				echo "</script>\n";
				noscriptRefresh(10);
			}else{
				# This is a unsupported media file that can not be played with the web player
				# - This is the message displayed when a local media type is given but transcoding is disabled
				echo "<div class='titleCard'>\n";
				echo "	<div class='videoPosterContainer'>";
				echo "		<img class='videoPoster' src='$posterPath' />";
				echo "	</div>";
				echo "	<div class='titleCard'>\n";
				echo "		The server administrator has disabled video transcoding.<br> Video player can not currently play this file type '$videoMimeType'.<br> Use the direct links or external links below to download or access media with a external application.\n";
				echo "	</div>\n";
				echo "</div>\n";
			}
		}
	?>
<div id='pageContent' class='descriptionCard'>
<h2>
<?PHP
	echo $titleData;
?>
</h2>
<?PHP
	$datePath=$_SERVER["SCRIPT_FILENAME"].".date";
	if (file_exists($datePath)){
		echo "<div class='aired'>\n";
		echo file_get_contents($datePath)."\n";
		echo "</div>\n";
	}

	echo "<div class='hardLink'>\n";
	# draw the direct links
	echo "<div>\n";
	echo "<a class='button hardLink' rel='noreferer' href='".$directLinkData."'>\n";
	echo "🔗Direct Link\n";
	echo "</a>\n";
	echo "</div>\n";
	#
	echo "<div>\n";
	echo "<a class='button hardLink' rel='noreferer' href='".$directLinkData."' download>\n";
	echo "<span class='downloadIcon'>🡇</span> Direct Download\n";
	echo "</a>\n";
	echo "</div>\n";

	# build the cache links
	if ( $httpLink ){
		# if the cache link is a external link that means it is a real cache link
		echo "<div>\n";
		echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".$directLinkData."'>\n";
		echo "📥Cache Link\n";
		echo "</a>\n";
		echo "</div>\n";
		#
		echo "<div>\n";
		echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".$directLinkData."' download>\n";
		echo "<span class='downloadIcon'>🡇</span> Cache Download Link\n";
		echo "</a>\n";
		echo "</div>\n";
	}else{
		# only draw a cache link for local links if the transcoder is enabled
		if (isTranscodeEnabled()){
			# this is a local link so show the transcode cache link unless transcoding is disabled
			echo "<div>\n";
			echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/transcode.php?path=".$directLinkData."'>\n";
			echo "📥Cache Link\n";
			echo "</a>\n";
			echo "</div>\n";
			#
			echo "<div>\n";
			echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/transcode.php?path=".$directLinkData."' download>\n";
			echo "<span class='downloadIcon'>🡇</span> Cache Download Link\n";
			echo "</a>\n";
			echo "</div>\n";
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
	addToLog("DEBUG","videoPlayer.php","userClientName = '$userClientName'");
	if ($userClientName == "client"){
		addToLog("DEBUG","videoPlayer.php","loading client'");
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
		echo "<a onclick='pauseVideo();' class='button hardLink' target='_new' href='/remote.php?mime=$playbackType&play=".urlencode($directLinkData)."'>\n";
		echo "🎛️ Select Your Remote\n";
		echo "</a>\n";
		echo "</div>\n";
	}
	echo "<div>";
	# build the vlc links
	if (file_exists("show.title")){
		if ( $httpLink ){
			# if this is a external link
			echo "<a onclick='pauseVideo();' class='button hardLink vlcButton' href='vlc://".str_replace(" ","%20",$fullPathVideoLink)."'>\n";
		}else{
			echo "<a onclick='pauseVideo();' class='button hardLink vlcButton' href='vlc://"."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20",$directLinkData)."'>\n";
		}
	}else{
		if ( $httpLink ){
			# if this is a external link
			echo "<a onclick='pauseVideo();' class='button hardLink vlcButton' href='vlc://".str_replace(" ","%20",$fullPathVideoLink)."'>\n";
		}else{
			# this is a movie
			echo "<a onclick='pauseVideo();' class='button hardLink vlcButton' href='vlc://"."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20",$directLinkData)."'>\n";
		}
	}
	echo "▶️ Direct Play\n";
	echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
	echo "</a>\n";
	echo "</div>\n";

	if (file_exists("show.title")){
		echo "<div>\n";
		echo "<a onclick='pauseVideo();' class='button hardLink vlcButton' href='vlc://"."http://".$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".str_replace(" ","%20",$showTitle)."'>\n";
		echo "🔁 Continue\n";
		echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
		echo "</a>\n";
		echo "</div>\n";
	}
	# add code to turn looping the video on or off
	if (array_key_exists("loop",$_GET)){
		echo "		<a class='hardLink button' href='?'>";
		echo "			① Play Once";
		echo "		</a>";
	}else{
		echo "		<a class='hardLink button' href='?loop'>";
		echo "			∞ Loop Playback";
		echo "		</a>";
	}
	echo "</div>\n";
?>
<?PHP
	# write the plot data
	echo $plotData;
	# write the video channel url if the json is loaded
	echo $videoChannelUrl;
?>
</div>
<?PHP
# write the admin data if it exists
echo $adminData;

#

loadSearchIndexResults($titleData,"shows");
loadSearchIndexResults($titleData,"movies");

?>

<div class='titleCard'>
	<h1>External Links</h1>
	<div class='listCard'>
	<?PHP
		# load up the external search providers
		$externalSearchLinks=Array();
		array_push($externalSearchLinks, Array("/search.php?q=","2web"));
		array_push($externalSearchLinks, Array("https://www.imdb.com/find?q=","IMDB"));
		array_push($externalSearchLinks, Array("https://en.wikipedia.org/w/?search=","Wikipedia"));
		array_push($externalSearchLinks, Array("https://archive.org/details/movies?query=","Archive.org"));
		array_push($externalSearchLinks, Array("https://piped.video/results?search_query=","Piped"));
		array_push($externalSearchLinks, Array("https://odysee.com/$/search?q=","Odysee"));
		array_push($externalSearchLinks, Array("https://rumble.com/search/video?q=","Rumble"));
		array_push($externalSearchLinks, Array("https://www.bitchute.com/search/?kind=video&query=","BitChute"));
		array_push($externalSearchLinks, Array("https://www.twitch.tv/search?term=","Twitch"));
		array_push($externalSearchLinks, Array("https://veoh.com/find/","VEOH"));
		array_push($externalSearchLinks, Array("https://www.youtube.com/results?search_query=","Youtube"));
		# draw links for each of the search providers
		foreach($externalSearchLinks as $linkData){
			echo "<a class='button' rel='noreferer' target='_new' href='".$linkData[0].$titleData."'>🔎 ".$linkData[1]."</a>\n";
		}
	?>
	</div>
</div>

<hr>
<?PHP
	include("/usr/share/2web/templates/footer.php");
?>
</body>
</html>

