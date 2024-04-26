<?php
	#ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# get the title data
	$titlePath=$_SERVER["SCRIPT_FILENAME"].".title";
	if (file_exists($titlePath)){
		$titleData=file_get_contents($titlePath);
		$useJson=false;
		$jsonPath="";
	}else{
		$titleData=str_replace(".php","",basename($_SERVER["SCRIPT_FILENAME"]));
		$jsonSum=$titleData;
		$useJson=true;
		# build the json path
		$jsonPath=$_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.info.json";
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
function transcodeVideo($link, $proto){
	# The function for transcoding a file for playback in the browser
	#
	# - This function does not check if transcoding is enabled use isTranscodeEnabled()
	#   to check before calling this function

	# if the trancode is enabled run the transcode job
	debug("Reading link for transcode : '".$link."'");
	# create the sum of the link
	$sum=md5($link);
	$webServerPath=$_SERVER["DOCUMENT_ROOT"];

	# set the default cache value
	$cacheFile=false;
	# set the storage path
	$storagePath=$webServerPath."/TRANSCODE-CACHE/".$sum."/";
	# if the json data has not yet been verified to have downloaded the entire file
	if (! file_exists($storagePath."verified.cfg")){
		# check the file downloaded correctly by comparing the json data file length with local file length
		if (file_exists($storagePath."video.mp4")){
			# if the file was last modified more than 60 seconds ago
			# - checks should wait for caching to complete in order to properly read file metadata
			if ( ( time() - filemtime($storagePath."video.mp4") ) > 60){
				if (file_exists($storagePath."video.info.json")){
					# The json downloaded from the remote and stored by the resolver
					$remoteJson = json_decode(shell_exec("mediainfo --output=JSON ".$storagePath."video.info.json"));
					$remoteJsonValue= (int)$remoteJson->media->track[0]->Duration;
					# reduce the value to add variance for rounding errors
					# - Depending on the json data and how it was generated the rounding of time may go up or down by one
					$remoteJsonValue-=1;
					# the json data from reading the current downloaded file
					$localJson = json_decode(shell_exec("mediainfo --output=JSON ".$storagePath."video.mp4"));
					$localJsonValue= (int)$localJson->media->track[0]->Duration;
					# compare the lenght in the remote json to the local file, including the variance above
					if ($localJsonValue >= $remoteJsonValue){
						addToLog("WARNING","Attempt to Verify Track Length","Track was verified in /TRANSCODE-CACHE/$sum/");
						debug("The video is completely downloaded and has been verified to have downloaded correctly...");
						# if the length is correct the file is verified to have downloaded completely
						touch($storagePath."verified.cfg");
					}else{
						addToLog("WARNING","Attempt to Verify Transcoded Track Length","Track was NOT verified because the length was incorrect<br>\nLOCAL='".$localJsonValue."' >= REMOTE='".$remoteJsonValue."'<br>\n");
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
	# make sure there is no existing stream available
	if ( ! file_exists($webServerPath."/TRANSCODE-CACHE/$sum/play.m3u")){
		$cacheFile=true;
	}
	if ($cacheFile){
		addToLog("WARNING", "Transcode Job was Started", "A transcode job was started for '$link'");
		if ( ! file_exists("$webServerPath/TRANSCODE-CACHE/")){
			mkdir("$webServerPath/TRANSCODE-CACHE/");
		}
		# cleanup html string encoding of spaces and pathnames
		#$link = str_replace("%20"," ",$link);
		#$link = str_replace("%21"," ",$link);
		#
		$link = str_replace("'","",$link);
		$link = str_replace('"',"",$link);
		# decode the link
		$link = urldecode($link);
		# build the command
		$fullLinkPath=$webServerPath.$link;
		# create a transcode directory to store the hls stream if it does not exist
		if ( ! file_exists("$webServerPath/TRANSCODE-CACHE/$sum/")){
			mkdir("$webServerPath/TRANSCODE-CACHE/$sum/");
		}
		# remove doubled slashes to fix paths
		$fullLinkPath=str_replace("//","/",$fullLinkPath);
		$command = 'echo "';
		$command .= "/usr/bin/ffmpeg -i '".$fullLinkPath."'";
		$command .= " -preset superfast";
		$command .= " -hls_list_size 0";
		$command .= " -start_number 0";
		$command .= " -master_pl_name 'play.m3u' -g 30 -hls_time 10 -f hls";
		$command .= " '".$webServerPath."/TRANSCODE-CACHE/".$sum."/stream.m3u'";
		# encode the stream into a mp4 file for compatibility with firefox
		$command .= "; /usr/bin/ffmpeg -i '".$webServerPath."/TRANSCODE-CACHE/".$sum."/stream.m3u' '".$webServerPath."/TRANSCODE-CACHE/".$sum."/play.mp4'";
		$command .= '" | /usr/bin/at -M -q a now';

		# save the transcode command to a file
		file_put_contents("$webServerPath/TRANSCODE-CACHE/$sum/command.cfg","$command");
		# launch the command to post job in the queue
		shell_exec($command);
		# sleep to allow the transcode job to startup
		# build and display a m3u file with a delay using the spinner gif
		sleep(10);
	}

	#
	$playbackUrl="";
	$playbackMime="";
	#
	$plabackAvailable=false;
	#
	if (file_exists($webServerPath."/TRANSCODE-CACHE/$sum/play.mp4")){
		$playbackUrl="/TRANSCODE-CACHE/$sum/play.mp4";
		$playbackMime="video/mp4";
		# redirect to the mp4 file for the highest level of browser compatibility
		if (filesize($_SERVER["DOCUMENT_ROOT"].$playbackUrl) > 10){
			$plabackAvailable=true;
			$playbackUrl=$proto.$_SERVER["HTTP_HOST"]."/TRANSCODE-CACHE/$sum/play.mp4";
		}
	}else if (file_exists($webServerPath."/TRANSCODE-CACHE/$sum/play.m3u")){
		$playbackUrl="/TRANSCODE-CACHE/$sum/play.m3u";
		$playbackMime="application/mpegurl";
		# redirect to the master playlist
		if (filesize($_SERVER["DOCUMENT_ROOT"].$playbackUrl) > 10){
			$plabackAvailable=true;
			$playbackUrl=$proto.$_SERVER["HTTP_HOST"]."/TRANSCODE-CACHE/$sum/play.m3u";
		}
	}
	if($plabackAvailable){
		return Array($playbackUrl, $playbackMime);
	}else{
		# the transcode job failed completely so add it to the system log
		addToLog("ERROR", "A transcode Job Failed", "The transcode job for '$link' has failed. The path '$webServerPath/TRANSCODE-CACHE/$sum/' does not contain any playable video content.");
		return false;
	}
}
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
	echo "<img class='localPulse' src='/pulse.gif'>";
	echo "<p>The video is still loading...</p>";
	echo "<p>The page will refresh when the player loads...</p>";
	echo "<noscript><meta http-equiv='refresh' content='$seconds'></noscript>";
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
	if(document.getElementById("video").hasFocus()){
		// check for key controls on the video player
		const key = event.key;
		switch (key){
			case " ":
			event.preventDefault();
			playPause();
			break;
			case "Spacebar":
			event.preventDefault();
			playPause();
			break;
			case "ArrowDown":
			event.preventDefault();
			volumeDown();
			break;
			case "ArrowUp":
			event.preventDefault();
			volumeUp();
			break;
			case "ArrowRight":
			event.preventDefault();
			seekForward();
			break;
			case "ArrowLeft":
			event.preventDefault();
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
	# check for direct link
	$directLinkPath=$_SERVER["SCRIPT_FILENAME"].".directLink";
	if (file_exists($directLinkPath)){
		$directLinkData=file_get_contents($directLinkPath);
		if ($_SERVER["HTTP_HOST"] != "localhost"){
			$directLinkData=str_replace((gethostname().".local"),$_SERVER["HTTP_HOST"],$directLinkData);
		}
		$directLinkData=trim($directLinkData, "\r\n");
		$directLinkExists=true;
	}else{
		$directLinkExists=false;
	}
	# check for cache link
	$cacheLinkPath=$_SERVER["SCRIPT_FILENAME"].".cacheLink";
	if (file_exists($cacheLinkPath)){
		$cacheLinkData=file_get_contents($cacheLinkPath);
		$cacheLinkData=str_replace((gethostname().".local"),$_SERVER["HTTP_HOST"],$cacheLinkData);
		$cacheLinkData=trim($cacheLinkData, "\r\n");
		$cacheLinkExists=true;
	}else{
		$cacheLinkExists=false;
	}
	# get the strmlink
	$strmLinkPath=$_SERVER["SCRIPT_FILENAME"].".strmLink";
	if (file_exists($strmLinkPath)){
		$strmLinkData=file_get_contents($strmLinkPath);
		$strmLinkData=trim($strmLinkData, "\r\n");
		$strmLinkExists=true;
	}else{
		$strmLinkExists=false;
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
		# get the uploder url
		if(property_exists($jsonData, "uploader_url")){
			$videoChannelUrl=$jsonData->uploader_url;
			# Build the html for the bottom of the page
			$tempVideoChannelUrl="<table>\n";
			$tempVideoChannelUrl.="<tr>\n";
			$tempVideoChannelUrl.="<th>Channel Source</th>\n";
			if (requireGroup("admin",false)){
				$tempVideoChannelUrl.="<th>Admin Actions</th>\n";
			}
			$tempVideoChannelUrl.="</tr>\n";
			$tempVideoChannelUrl.="<tr>\n";
			$tempVideoChannelUrl.="	<td>\n";
			$tempVideoChannelUrl.="		".$videoChannelUrl."\n";
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
			$tempVideoChannelUrl.="</table>\n";
			#
			$videoChannelUrl=$tempVideoChannelUrl;
		}
		# get the plot data
		#if(array_key_exists("description",$jsonData)){
		if(property_exists($jsonData, "description")){
			$plotData="<div class='plot'>\n";
			$plotData.=$jsonData->description;
			$plotData.="</div>\n";
		}else{
			# there is no description so blank it out
			$plotData="";
		}
		if(property_exists($jsonData, "age_limit")){
			$ratingText="<span class='button'>Required Viewing Age: ".$jsonData->age_limit."</span>";
		}else{
			# there is no description so it is unrated
			$ratingText="<span class='button'>Rating : UNRATED</span>";
		}
		# get the thumbnail
		$posterPath="/RESOLVER-CACHE/".$jsonSum."/video.png";
	}else{
		# get the video thumb path for the video player
		# - check for PNG and JPG versions
		$posterPath=str_replace(".php","-thumb.png",$_SERVER["SCRIPT_FILENAME"]);
		$posterPathJPG=str_replace(".php","-thumb.jpg",$_SERVER["SCRIPT_FILENAME"]);
		if (file_exists($posterPath)){
			$posterPath=$proto.$_SERVER["HTTP_HOST"].str_replace($_SERVER["DOCUMENT_ROOT"],"",$posterPath);
		}else if (file_exists($posterPathJPG)){
			$posterPath=$proto.$_SERVER["HTTP_HOST"].str_replace($_SERVER["DOCUMENT_ROOT"],"",$posterPathJPG);
		}else{
			$posterPath="poster.png";
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
				$ratingText="<span class='button'>Rating : $ratingData</span>";
			}else{
				$ratingText="<span class='button'>Rating : UNRATED</span>";
			}
		}else{
			$ratingText="<span class='button'>Rating : UNRATED</span>";
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
<img class='globalPulse' src='/pulse.gif'>
<div class='listCard'>
<?PHP
	if (! file_exists("show.title")){
		# get the trailer if it is a movie
		$trailerPath="trailer.title";
		if (file_exists($trailerPath)){
			$trailerData=file_get_contents($trailerPath);
			echo "<a class='button' rel='noreferer' target='_new' href='$trailerData'>";
			echo "🔗 Trailer";
			echo "</a>";
		}
	}
	# get the production studio
	$studioPath="studio.title";
	if (file_exists($studioPath)){
		$studioData=file_get_contents($studioPath);
		if ( strlen($studioData) > 0){
			echo "<span class='button'>Studio : $studioData</span>";
		}
	}
	echo $ratingText;

	?>
</div>
</div>
	<?PHP
		# get the cache link if it exists
		if ($cacheLinkExists){
			# check if the mp4 file is already in the cache
			$videoLink = $cacheLinkData;
		}else if ($directLinkExists){
			# load the direct link to the video into the player
			$videoLink = $directLinkData;
		}
		# make the full path
		if (file_exists("show.title")){
			if (! ($cacheLinkExists)){
				# load show episode
				$videoLink = $directLinkData;
			}
		}else{
			# load movie
			$videoLink = "$directLinkData";
		}
		flush();
		ob_flush();
		# get headers to use the local certificate
		$streamContextParams=[
			'ssl' => [
				'cafile' => "/var/cache/2web/ssl-cert.crt",
				'verify_peer' => true,
				'verify_peer_name' => true,
				'allow_self_signed' => true,
			],
		];
		$streamContext=stream_context_create($streamContextParams);
		# check if the file exists and check the metadata
		if ($cacheLinkExists){
			# check if the mp4 file is already in the cache
			if(file_exists($jsonPath)){
				# if the json data exists
				$mp4FilePath = $_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4";
				$strmFilePath = $_SERVER["DOCUMENT_ROOT"]."/RESOLVER-CACHE/".$jsonSum."/video.m3u";
				# if the mp4 exists load the file directly without reading the headers
				if(file_exists($mp4FilePath)){
					# build the video link directly to the mp4 file
					$videoLink = $proto.$_SERVER["HTTP_HOST"]."/RESOLVER-CACHE/".$jsonSum."/video.mp4" ;
					$videoMimeType=Array();
					$videoMimeType["Content-Type"]="video/mp4";
					$fullPathVideoLink=$videoLink;
				#}else if(file_exists($strmFilePath)){
				}else{
					# try the stream link
					$videoLink = $proto.$_SERVER["HTTP_HOST"]."/RESOLVER-CACHE/".$jsonSum."/video.m3u" ;
					$videoMimeType=Array();
					$videoMimeType["Content-Type"]="application/mpegurl";
					$fullPathVideoLink=$videoLink;
				}
			}else{
				# this is not a file but a external link so check the mime type of the video link
				#$videoMimeType=get_headers($cacheLinkData, true, $streamContext);
				# the mimetype should be gathered from the local resolver location
				$videoMimeType=get_headers($proto.gethostname().".local/ytdl-resolver.php?url=".$directLinkData, true, $streamContext);
				$fullPathVideoLink=$cacheLinkData;
			}
		}else{
			if ( (substr($directLinkData,0,8) == "https://") or (substr($directLinkData,0,7) == "http://") ){
				# this is a external link
				$videoMimeType=get_headers($directLinkData, true);
				$fullPathVideoLink=$directLinkData;
			}else{
				# this is a file path
				$videoMimeType=mime_content_type($_SERVER["DOCUMENT_ROOT"].$directLinkData);
				$fullPathVideoLink=$directLinkData;
			}
		}
		# generate https links for https connections
		if (array_key_exists("HTTPS",$_SERVER)){
			# the cache links that are linked to should be https anyway
			$fullPathVideoLink=str_replace("http://","https://",$fullPathVideoLink);
		}
		# pull the content type based on the mime data
		if (is_array($videoMimeType)){
			if (array_key_exists("Content-Type", $videoMimeType)){
				$videoMimeType=$videoMimeType["Content-Type"];
			}
		}
		# draw the player based on the video link mime type
		if (is_in_array("video/mp4", $videoMimeType)){
			echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='video/mp4'>\n";
			echo "</video>\n";
		}else if (is_in_array("audio/mpeg", $videoMimeType)){
			echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='audio/mpeg'>\n";
			echo "</audio>\n";
		}else if (is_in_array("video/webm", $videoMimeType)){
			echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='video/webm'>\n";
			echo "</audio>\n";
		}else if (is_in_array("video/ogg", $videoMimeType)){
			echo "<audio id='video' class='nfoMediaPlayer' class='' style='background-image: url(\"$posterPath\");' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='video/ogg'>\n";
			echo "</audio>\n";
		}else if (is_in_array("video/x-matroska", $videoMimeType)){
			# if the trancode is enabled run the transcode job
			if (isTranscodeEnabled()){
				# use the transcode function in order to transcode the video if transcoding is enabled
				$transcodePath=transcodeVideo(str_replace(" ","%20", $videoLink), $proto);
				if ($transcodePath == false){
					echo "<div class='titleCard'>\n";
					echo "The trancode job is currently running but is unfinished...\n";
					echo "</div>\n";
				}else{
					# create the generated transcode path localy
					#$transcodePath=$videoMimeType["Location"];
					if ("video/mp4" == $transcodePath[1]){
						# draw the mp4 player
						echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
						echo "	<source src='".$transcodePath[0]."' type='video/mp4'>\n";
						echo "</video>\n";
					}else if ("application/mpegurl" == $transcodePath[1]){
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
						echo "		hls.loadSource('".$transcodePath[0]."');\n";
						echo "		hls.attachMedia(video);\n";
						echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
						echo "			video.muted = false;\n";
						echo "			video.play();\n";
						echo "		});\n";
						echo "	}\n";
						echo "	else if (video.canPlayType('application/vnd.apple.mpegurl')) {\n";
						echo "		video.src = '".$transcodePath[0]."';\n";
						echo "		video.addEventListener('canplay',function() {\n";
						echo "			video.play();\n";
						echo "		});\n";
						echo "	}\n";
						# start playback on page load
						echo "hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
						echo "</script>\n";
						noscriptRefresh(10);
					}
				}
			}else{
				# this is a mkv file that can not be played with the web player
				echo "<div class='titleCard'>\n";
				echo "The server administrator has disabled video transcoding.Video Player Can Not currently Play MKV files. Use the direct links or external links below to download or access media with a external application.\n";
				echo "</div>\n";
			}
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
			echo "		hls.loadSource('$fullPathVideoLink');\n";
			echo "		hls.attachMedia(video);\n";
			echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
			#echo"	echo \"			video.muted = false;\";"
			echo "			video.play();\n";
			echo "		});\n";
			echo "	}\n";
			echo "	else if (video.canPlayType('application/vnd.apple.mpegurl')) {\n";
			echo "		video.src = '$fullPathVideoLink';\n";
			echo "		video.addEventListener('canplay',function() {\n";
			echo "			video.play();\n";
			echo "		});\n";
			echo "	}\n";
			# start playback on page load
			echo "hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
			echo "</script>\n";
			noscriptRefresh(10);
		}else{
			# draw the hls stream player webpage player
			echo "<script>\n";
			echo "document.write(\"<video id='video' class='livePlayer' poster='$posterPath' controls></video>\");\n";
			echo "	if(Hls.isSupported()) {\n";
			echo "		var video = document.getElementById('video');\n";
			echo "		var hls = new Hls({\n";
			echo "			startPosition: 0\n";
			echo "		});\n";
			echo "		hls.loadSource('$fullPathVideoLink');\n";
			echo "		hls.attachMedia(video);\n";
			echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
			#echo"	echo \"			video.muted = false;\";"
			echo "			video.play();\n";
			echo "		});\n";
			echo "	}\n";
			echo "	else if (video.canPlayType('application/vnd.apple.mpegurl')) {\n";
			echo "		video.src = '$fullPathVideoLink';\n";
			echo "		video.addEventListener('canplay',function() {\n";
			echo "			video.play();\n";
			echo "		});\n";
			echo "	}\n";
			# start playback on page load
			echo "hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
			echo "</script>\n";
			noscriptRefresh(10);
		}
	?>
<div class='descriptionCard'>
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

	# build the cache links
	if ($cacheLinkExists){
		# if the cache link is a external link that means it is a real cache link
		#if ( (substr($fullPathVideoLink,0,8) == "https://") or (substr($fullPathVideoLink,0,7) == "http://") ){
			echo "<div>\n";
			echo "<a class='button hardLink' href='$fullPathVideoLink'>\n";
			echo "📥Cache Link\n";
			echo "</a>\n";
			echo "</div>\n";
		#}
	}
	# build the continue playing playlist links
	if (file_exists("show.title")){
		echo "<div>\n";
		echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".str_replace(" ","%20",$showTitle)."'>\n";
		echo "🔁 Continue<sup>External</sup>\n";
		echo "</a>\n";
		echo "</div>\n";
	}
	# check if the play on kodi button is enabled in the settings
	if (yesNoCfgCheck("/etc/2web/kodi/playOnKodiButton.cfg")){
		# check if the user has permissisons to access these buttons
		if (requireGroup("kodi2web", false)){
			echo "<div>";
			# build the play on kodi links
			if (file_exists("show.title")){
				#echo "<a class='button hardLink' href='/kodi-player.php?url=".$proto.$_SERVER["HTTP_HOST"]."/kodi/shows/$showTitle/Season $seasonTitle/$directLinkData'>\n";
				# - using a self signed cert will cause this to fail unless you setup all kodi clients with the public cert so this is currently disabled by default
				if ($cacheLinkExists){
					echo "<a class='button hardLink' target='_new' href='/kodi-player.php?url=".str_replace(" ","%20","$strmLinkData")."'>\n";
				}else{
					#if ( (substr($fullPathVideoLink,0,8) == "https://") or (substr($fullPathVideoLink,0,7) == "http://") ){
					if ( stripos($fullPathVideoLink,(gethostname().".local")) !== false ){
						echo "<a class='button hardLink' target='_new' href='/kodi-player.php?url=".str_replace(" ","%20","$strmLinkData")."'>\n";
					}else{
						if($strmLinkExists){
							echo "<a class='button hardLink' target='_new' href='/kodi-player.php?url=".str_replace(" ","%20","$strmLinkData")."'>\n";
						}else{
							echo "<a class='button hardLink' target='_new' href='/kodi-player.php?url="."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20","$directLinkData")."'>\n";
						}
					}
				}
			}else{
				# this is a movie
				#echo "<a class='button hardLink' href='/kodi-player.php?url=".$proto.$_SERVER["HTTP_HOST"]."/kodi/movies/$movieTitle/$directLinkData'>\n";
				# - using a self signed cert will cause this to fail unless you setup all kodi clients with the public cert so this is currently disabled by default
				if ($cacheLinkExists){
					echo "<a class='button hardLink' target='_new' href='/kodi-player.php?shareURL=".str_replace(" ","%20","$directLinkData")."'>\n";
				}else{
					echo "<a class='button hardLink' target='_new' href='/kodi-player.php?url="."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20","$directLinkData")."'>\n";
				}
			}
			echo "🇰Play on KODI\n";
			echo "</a>\n";
			echo "</div>\n";
		}
	}

	if ($cacheLinkExists){
		#$clientLink="/ytdl-resolver.php?url=".$directLinkData;
		$clientLink=$cacheLinkData;
	}else{
		$clientLink=$directLinkData;
	}
	#$clientLink=$directLinkData;

	# if the client is enabled
	if (yesNoCfgCheck("/etc/2web/client.cfg")){
		# if the group permissions are available for the current user
		if (requireGroup("clientRemote", false)){
			# draw the button for playing videos across all the clients
			echo "<div>";
			# build the play on client link
			echo "<a class='button hardLink' target='_new' href='/client/?play=".$clientLink."'>\n";
			echo "🎟️ Play on Client\n";
			echo "</a>\n";
			echo "</div>\n";
		}
	}

	echo "<div>";
	# build the vlc links
	if (file_exists("show.title")){
		if ( (substr($fullPathVideoLink,0,8) == "https://") or (substr($fullPathVideoLink,0,7) == "http://") ){
			# if this is a external link
			echo "<a class='button hardLink vlcButton' href='vlc://".str_replace(" ","%20",$fullPathVideoLink)."'>\n";
		}else{
			#echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"].str_replace(" ","%20",$directLinkData)."'>\n";
			echo "<a class='button hardLink vlcButton' href='vlc://"."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20",$directLinkData)."'>\n";
		}
	}else{
		if ( (substr($fullPathVideoLink,0,8) == "https://") or (substr($fullPathVideoLink,0,7) == "http://") ){
			# if this is a external link
			echo "<a class='button hardLink vlcButton' href='vlc://".str_replace(" ","%20",$fullPathVideoLink)."'>\n";
		}else{
			# this is a movie
			#echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"].str_replace(" ","%20",$directLinkData)."'>\n";
			echo "<a class='button hardLink vlcButton' href='vlc://"."http://".$_SERVER["HTTP_HOST"].str_replace(" ","%20",$directLinkData)."'>\n";
		}
	}
	echo "▶️ Direct Play\n";
	echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
	echo "</a>\n";
	echo "</div>\n";

	#if ($cacheLinkExists){
	#	echo "<div>";
	#	echo "<a class='button hardLink vlcButton' href='vlc://$fullPathVideoLink'>\n";
	#	echo "📥Cache Link\n";
	#	echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
	#	echo "</a>\n";
	#	echo "</div>";
	#}

	if (file_exists("show.title")){
		echo "<div>\n";
		#echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".str_replace(" ","%20",$showTitle)."'>";
		echo "<a class='button hardLink vlcButton' href='vlc://"."http://".$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".str_replace(" ","%20",$showTitle)."'>\n";
		echo "🔁 Continue\n";
		echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
		echo "</a>\n";
		echo "</div>\n";
	}
	#if($useJson){
	#	# draw the json button link if it exists
	#	echo "<a class='button hardLink' href='".str_replace($_SERVER["DOCUMENT_ROOT"],"",$jsonPath)."'>\n";
	#	echo "📑 JSON\n";
	#	echo "</a>\n";
	#	echo "</div>\n";
	#}

	echo "</div>\n";
?>
<?PHP
	# write the plot data
	echo $plotData;
	# write the video channel url if the json is loaded
	echo $videoChannelUrl;
?>
</div>
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

