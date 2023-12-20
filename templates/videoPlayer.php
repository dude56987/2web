<html id='top' class='seriesBackground'>
<head>
<?PHP
if (file_exists("show.title")){
	# get the show title
	$showTitle=file_get_contents("show.title");
	# get the numeric title
	$numericTitlePath=$_SERVER["SCRIPT_FILENAME"].".numTitle";
	$numericTitleData=file_get_contents($numericTitlePath);
	echo "<title>$showTitle - $numTitle</title>";
}else{
	# get the movie title
	$movieTitle=file_get_contents("movie.title");
	echo "<title>$movieTitle</title>";
}
if (array_key_exists("HTTPS",$_SERVER)){
	$proto="https://";
}else{
	$proto="http://";
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
<?PHP
	include("/usr/share/2web/templates/header.php");
?>
<?PHP
	# get the title data
	$titlePath=$_SERVER["SCRIPT_FILENAME"].".title";
	$titleData=file_get_contents($titlePath);
	# check for direct link
	$directLinkPath=$_SERVER["SCRIPT_FILENAME"].".directLink";
	if (file_exists($directLinkPath)){
		$directLinkData=file_get_contents($directLinkPath);
	}
	# check for cache link
	$cacheLinkPath=$_SERVER["SCRIPT_FILENAME"].".cacheLink";
	if (file_exists($cacheLinkPath)){
		$cacheLinkData=file_get_contents($cacheLinkPath);
	}
	# get the video thumb path for the video player
	$posterPath=str_replace(".php","-thumb.png",$_SERVER["SCRIPT_FILENAME"]);
	logPrint("posterPath = ".$posterPath);
	if (file_exists($posterPath)){
		$posterPath=str_replace($_SERVER["DOCUMENT_ROOT"],"",$posterPath);
		logPrint("posterPath = ".$posterPath);
	}else{
		$posterPath="poster.png";
	}
?>
<div class='titleCard'>
<h1>
<?PHP
	if (file_exists("show.title")){
		# write the data
		echo "<a href='/shows/".$showTitle."/?search=".$seasonTitle."#Season ".$seasonTitle."'>".$showTitle."</a> $numericTitleData";
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
		$trailerData=file_get_contents($trailerPath);
		echo "<a class='button' href='$trailerData'>";
		echo "Trailer Link";
		echo "</a>";
	}
	# get the production studio
	$studioPath="studio.title";
	$studioData=file_get_contents($studioPath);
	if ( strlen($studioData) > 0){
		echo "<span class='button'>Studio : $studioData</span>";
	}
	# get the rating
	$ratingPath="grade.title";
	$ratingData=file_get_contents($ratingPath);
	if ( strlen($ratingData) > 0){
		echo "<span class='button'>Rating : $ratingData</span>";
	}
	?>
</div>
</div>
	<?PHP
		logPrint("directLinkPath = ".$directLinkPath."<br>");
		logPrint("directLinkData = ".$directLinkData."<br>");
		logPrint("cacheLinkPath = ".$cacheLinkPath."<br>");
		logPrint("cacheLinkData = ".$cacheLinkData."<br>");
		# get the cache link if it exists
		if (file_exists($cacheLinkPath)){
			$videoLink = $cacheLinkData;
			logPrint("videoLink cache link = ".$videoLink."<br>");
		}else if (file_exists($directLinkPath)){
			# load the direct link to the video into the player
			$videoLink = $directLinkData;
			logPrint("videoLink direct link = ".$videoLink."<br>");
		}
		# make the full path
		if (file_exists("show.title")){
			logPrint("showTitle = ".$showTitle."<br>");
			logPrint("seasonTitle = ".$seasonTitle."<br>");
			logPrint("video link format = /kodi/shows/\$showTitle/Season \$seasonTitle/\$directLinkData<br>");
			# load show episode
			$videoLink = "/kodi/shows/$showTitle/Season $seasonTitle/$directLinkData";
			logPrint("videoLinkFix = ".$videoLink."<br>");
		}else{
			# load movie
			logPrint("direct Link Data format = /kodi/movies/\$movieTitle/\$directLinkData<br>");
			$videoLink = "/kodi/movies/$movieTitle/$directLinkData";
			logPrint("videoLinkFix = ".$videoLink."<br>");
		}
		# if the video is a .strm file load the contents into the video link
		if (substr($videoLink,-5,5) == ".strm"){
			logPrint("loading data from the .strm file.<br>");
			$fullPathVideoLink=file_get_contents($_SERVER["DOCUMENT_ROOT"].$videoLink);
			# replace local links with the address used to access this page
			$fullPathVideoLink=str_replace((gethostname().".local"), $_SERVER["HTTP_HOST"], $fullPathVideoLink);
			# remove newlines added by file_get_contents(), .strm files should not have newlines
			$fullPathVideoLink=trim($fullPathVideoLink, "\r\n");
			# make sure the server is in https mode or redirect
			if (substr($fullPathVideoLink,0,8) == "https://"){
				# the link is https check the server
				if (! array_key_exists("HTTPS",$_SERVER)){
					# replace http with https if the server is using https
					# - external links from redirects should use https by default since these links are from the server to the internet
					$fullPathVideoLink=str_replace("http://","https://",$fullPathVideoLink);
					# build the upgrade button
					echo "<a class='button' href='".("https://".$_SERVER["HTTP_HOST"].".local/".$_SERVER["REQUEST_URI"])."'>Upgrade to HTTPS To Play</a>";
				}
				#redirect("https://".substr($_SERVER["REQUEST_URI"],7));
			}
			# set the header link
			$headerLink=$fullPathVideoLink;
		}else{
			# encode spaces in link
			$fullPathVideoLink=$proto.$_SERVER["HTTP_HOST"].str_replace(" ","%20",$videoLink);
			# send the header requests to the localhost server if they are to the local server
			$headerLink=str_replace($_SERVER["HTTP_HOST"],"localhost",$fullPathVideoLink);
		}
		#echo "<img class='globalPulse' src='/pulse.gif'>\n";
		#flush();
		#ob_flush();
		logPrint("fullPathVideoLinkFix = $fullPathVideoLink<br>");
		# get the headers to read the mimetype of the media for building the correct player on the webpage
		$videoMimeType=get_headers($headerLink, true);
		$headerData=$videoMimeType;
		# pull the content types
		logPrint("Checking if this is an array.<br>");
		if (is_array($videoMimeType)){
			logPrint("This is an array.<br>");
			logPrint("Checking mime type.<br>");
			if (array_key_exists("Content-Type", $videoMimeType)){
				logPrint("Key was found to exist.<br>");
				$videoMimeType=$videoMimeType["Content-Type"];
			}else{
				logPrint("Media Content Type could not be determined.<br>");
			}
		}else{
			logPrint("No Header was returned.<br>");
		}
		logPrint("Checking the mime type to build video player.<br>");
		# draw the player based on the video link mime type
		if (is_in_array("video/mp4", $videoMimeType)){
			logPrint("Discovered MP4 video file<br>");
			echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='video/mp4'>\n";
			echo "</video>\n";
		}else if (is_in_array("audio/mpeg", $videoMimeType)){
			echo "<audio id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
			echo "	<source src='$fullPathVideoLink' type='audio/mpeg'>\n";
			echo "</audio>\n";
		}else if (is_in_array("video/x-matroska", $videoMimeType)){
			logPrint("Loading MKV file into transcoder");
			# if the trancode is enabled run the transcode job
			if (isTranscodeEnabled()){
				#
				$transcodePath=$proto.$_SERVER["HTTP_HOST"].'/transcode.php?link='.str_replace(" ","%20",$videoLink);
				logPrint("Creating Transcode Path = ".$transcodePath);
				# launch the transcode job by sending a request for the header from the transcode path
				$videoMimeType=get_headers($transcodePath, true);
				#var_dump($videoMimeType);
				logPrint("Transcode Path = ".$_SERVER["DOCUMENT_ROOT"].$transcodePath);
				$videoMimeType=$videoMimeType["Content-Type"];
				# create the generated transcode path localy
				#$transcodePath=$videoMimeType["Location"];
				if (is_in_array("video/mp4", $videoMimeType)){
					# draw the mp4 player
					echo "<video id='video' class='nfoMediaPlayer' class='' poster='$posterPath' controls>\n";
					echo "	<source src='$transcodePath' type='video/mp4'>\n";
					echo "</video>\n";
				}else if (is_in_array("application/mpegurl", $videoMimeType)){
					# draw the hls stream player webpage player
					echo "<video id='video' class='livePlayer' poster='$posterPath' controls></video>\n";
					echo "<script>\n";
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
					echo "		hls.loadSource('$transcodePath');\n";
					echo "		hls.attachMedia(video);\n";
					echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
					echo "			video.muted = false;\n";
					echo "			video.play();\n";
					echo "		});\n";
					echo "	}\n";
					echo "	else if (video.canPlayType('application/vnd.apple.mpegurl')) {\n";
					echo "		video.src = '$transcodePath';\n";
					echo "		video.addEventListener('canplay',function() {\n";
					echo "			video.play();\n";
					echo "		});\n";
					echo "	}\n";
					# start playback on page load
					echo "hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
					echo "</script>\n";
				}
			}else{
				# this is a mkv file that can not be played with the web player
				echo "<div class='titleCard'>\n";
				echo "The server administrator has disabled video transcoding.Video Player Can Not currently Play MKV files. Use the direct links or external links below to download or access media with a external application.\n";
				echo "</div>\n";
			}
		}else if (is_in_array("application/mpegurl", $videoMimeType)){
			# hls stream
			logPrint("Loading HLS stream<br>");
			logPrint("hls stream = ".$fullPathVideoLink);
			# draw the hls stream player webpage player
			echo "<video id='video' class='livePlayer' poster='$posterPath' controls></video>\n";
			echo "<script>\n";
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
		}else{
			logPrint("Loading Unknown media resource<br>");
			logPrint("media = ".$fullPathVideoLink);
			# draw the hls stream player webpage player
			echo "<video id='video' class='livePlayer' poster='$posterPath' controls></video>\n";
			echo "<script>\n";
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
		}
	?>
<div class='descriptionCard'>
<h2>
<?PHP
	echo $titleData;
?>
</h2>

<?PHP
	# draw the direct links
	echo "<a class='button hardLink' href='".$directLinkData."'>\n";
	echo "🔗Direct Link\n";
	echo "</a>\n";

	# build the cache links
	if (file_exists($cacheLinkPath)){
		if (substr($fullPathVideoLink,0,8) == "https://"){
			echo "<a class='button hardLink' href='/ytdl-resolver.php?url=\"$cacheLinkData\"'>\n";
			echo "📥Cache Link\n";
			echo "</a>\n";
		}
	}
	# build the continue playing playlist links
	if (file_exists("show.title")){
		echo "<a class='button hardLink' href='".$proto.$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".$showTitle."'>";
		echo "🔁 Continue<sup>External</sup>";
		echo "</a>";
	}

	# build the play on kodi links
	if (file_exists("show.title")){
		echo "<a class='button hardLink' href='/kodi-player.php?url=".$proto.$_SERVER["HTTP_HOST"]."/kodi/shows/$showTitle/Season $seasonTitle/$directLinkData'>\n";
	}else{
		# this is a movie
		echo "<a class='button hardLink' href='/kodi-player.php?url=".$proto.$_SERVER["HTTP_HOST"]."/kodi/movies/$movieTitle/$directLinkData'>\n";
	}
	echo "🇰Play on KODI\n";
	echo "</a>\n";

	# build the vlc links
	if (file_exists("show.title")){
		echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"].str_replace(" ","%20","/shows/$showTitle/Season $seasonTitle/$directLinkData")."'>\n";
	}else{
		# this is a movie
		echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"].str_replace(" ","%20","/movies/$movieTitle/$directLinkData")."'>\n";
	}
	#echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"]."/$directLinkData'>\n";
	echo "▶️ Direct Play";
	echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
	echo "</a>\n";

	if (file_exists($cacheLinkPath)){
		if (substr($fullPathVideoLink,0,8) == "https://"){
			echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=\"$directLinkData\"'>\n";
			echo "📥Cache Link\n";
			echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>\n";
			echo "</a>\n";
		}
	}

	if (file_exists("show.title")){
		echo "<a class='button hardLink vlcButton' href='vlc://".$proto.$_SERVER["HTTP_HOST"]."/m3u-gen.php?playAt=".$numericTitleData."&showTitle=".$showTitle."'>";
		echo "🔁 Continue";
		echo "<sup><span id='vlcIcon'>▲</span>VLC</sup>";
		echo "</a>";
	}
?>
<?PHP
	$datePath=$_SERVER["SCRIPT_FILENAME"].".date";
	if (file_exists($datePath)){
		echo "<div class='aired'>";
		echo file_get_contents($datePath);
		echo "</div>";
	}else{
		logPrint("NO DATE FOUND AT $datePath<br>");
	}
	$plotPath=$_SERVER["SCRIPT_FILENAME"].".plot";
	if (file_exists($plotPath)){
		echo "<div class='plot'>";
		echo file_get_contents($plotPath);
		echo "</div>";
	}else{
		logPrint("NO PLOT FOUND AT $plotPath<br>");
	}
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
		array_push($externalSearchLinks, Array("https://www.youtube.com/results?search_query=","Youtube"));
		array_push($externalSearchLinks, Array("https://odysee.com/$/search?q=","Odysee"));
		array_push($externalSearchLinks, Array("https://rumble.com/search/video?q=","Rumble"));
		array_push($externalSearchLinks, Array("https://www.bitchute.com/search/?kind=video&query=","BitChute"));
		array_push($externalSearchLinks, Array("https://www.twitch.tv/search?term=","Twitch"));
		array_push($externalSearchLinks, Array("https://veoh.com/find/","VEOH"));
		# draw links for each of the search providers
		foreach($externalSearchLinks as $linkData){
			echo "<a class='button' target='_new' href='".$linkData[0].$titleData."'>🔎 ".$linkData[1]."</a>";
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

