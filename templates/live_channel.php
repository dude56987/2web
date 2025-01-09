<?PHP
	ini_set('display_errors', 1);
	include('/usr/share/2web/2webLib.php');
	requireGroup('iptv2web');
	# set the vary header
	header('Vary: Origin');
	# set the headers to allow playback of streams from diffrent origins
	header('Access-Control-Allow-Headers: "*";');
	#header("Access-Control-Allow-Origin: * always");
	header('Access-Control-Allow-Origin: "*" always;');

	#header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
	#header("Access-Control-Allow-Origin: *");
	#header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
	#header("Access-Control-Allow-Methods: GET");
	#header("Access-Control-Allow-Headers: *");
	#header("Access-Control-Allow-Headers: Accept, Content-Type, Content-Length, Accept-Encoding");
?>
<!--
########################################################################
# iptv2web live channel template
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
	# build the prefix
	$channelDataPrefix=$_SERVER["SCRIPT_FILENAME"];
	# after loading the header load the channel metadata
	$channelTitle=str_replace("\n","",file_get_contents($channelDataPrefix.".title"));
	#$channelLink=urlencode(str_replace("\n","",file_get_contents($channelDataPrefix.".strm")));
	if (file_exists($channelDataPrefix.".og.strm")){
		# this file means the channel is a redirect to the local resolver
		$channelLink=str_replace("\n","",file_get_contents($channelDataPrefix.".og.strm"));
		# add the host based on the current host address
		#$channelLink="http://".$_SERVER["HTTP_HOST"].$channelLink;
	}else{
		$channelLink=str_replace("\n","",file_get_contents($channelDataPrefix.".strm"));
	}
	#
	$channelLink=urlencode($channelLink);
	$decodedChannelLink=urldecode($channelLink);
	#
	$channelGroups=str_replace("\n","",file_get_contents($channelDataPrefix.".groups"));
	$channelType=str_replace("\n","",file_get_contents($channelDataPrefix.".type"));
	$channelNumber=str_replace("\n","",file_get_contents($channelDataPrefix.".number"));

	//var_dump($channelLink);
	//var_dump(get_headers(urldecode($channelLink)));
?>
<html onload='forcePlay()'  id='top' class='liveBackground'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<?PHP
	echo "<title>$channelTitle</title>";
	?>
	<script src='/2webLib.js'></script>
	<script>
	document.body.addEventListener('keydown', function(event){
		// only allow hotkeys if the video player has focus
		if(document.getElementById("video") == document.activeElement){
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
</head>
<body>
<?PHP
	include('/var/cache/2web/web/header.php');
?>
<div class='titleCard'>
	<h1>
		<?PHP
			echo "$channelTitle";
		?>
		<img id='spinner' src='/spinner.gif' />
	</h1>
<div class='listCard'>
	<?PHP
	echo "<a class='button' href='$decodedChannelLink'>"
	?>
		🔗 Direct Link
	</a>
	<?PHP
	echo "<a class='button' target='_new' href='/client/?play=$channelLink'>";
	?>
		🎟️ Play on Client
	</a>
	<?PHP
	echo "<a class='button' target='_new' href='/kodi-player.php?url=$channelLink'>";
	?>
		🇰Play on KODI
	</a>
	<?PHP
	echo "<a class='button vlcButton' href='vlc://".str_replace(" ","%20",urldecode($decodedChannelLink))."'>";
	?>
		<span id='vlcIcon'>&#9650;</span> VLC
	</a>
</div>
</div>
<?PHP
	if ((stripos($decodedChannelLink,"youtube.com") !== false) and (stripos($decodedChannelLink,"watch?v=") !== false)){
		echo "<div class='errorBanner'>Using Embeded Player! This means the source website can track your viewing of this stream and run javascript on this page. The embeded player is only used on websites that do not support <a href='https://en.wikipedia.org/wiki/HTTP_Live_Streaming'>HLS</a>.</div>";
	}
?>
<div class='listCard'>
<div id='videoPlayerContainer'>
<?PHP
	$poster="/live/thumbs/$channelNumber-thumb.png";
	# load the correct type of player
	if ($channelType == "radio"){
		# piped into a file
		# make the background for the audio player the poster of the audio stream
		$customStyle="background-image: url(\"$poster\");";
		echo "<audio id='video' class='livePlayer' style='$customStyle' poster='$poster' controls='controls' autoplay muted>";
		echo "<source src='$decodedChannelLink' type='audio/mpeg'>";
		echo "</audio>";
	}else if ($channelType == "video"){
		# check if this can be embeded
		if ((stripos($decodedChannelLink,"youtube.com") !== false) and (stripos($decodedChannelLink,"watch?v=") !== false)){
			# cut out the video id
			$embed_id=preg_replace("/.*watch\?v=/","",$decodedChannelLink);
			# remove trailing parenthesis
			$embed_id=preg_replace("/\"$/","",$embed_id);
			# embed the video player from the source website
			echo "<iframe class='livePlayer'";
			echo " src='https://www.youtube-nocookie.com/embed/$embed_id'";
			echo " frameborder='0'";
			echo " allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'";
			echo " allowfullscreen>";
			echo "</iframe>";
		}else{
			# build the page but dont write it, this function is intended to be
			# piped into a file
			echo "<script src='/2webLib.js'></script>\n";
			echo "<script src='/hls.js'></script>\n";
			echo "<video id='video' class='livePlayer' poster='$poster' autoplay muted></video>\n";
			echo "<script>\n";
			echo "	if(Hls.isSupported()) {\n";
			echo "		var video = document.getElementById('video');\n";
			echo "		var hls = new Hls({\n";
			echo "			debug: true\n";
			echo "		});\n";
			echo "		hls.loadSource('$decodedChannelLink');\n";
			echo "		hls.attachMedia(video);\n";
			echo "		hls.on(Hls.Events.MEDIA_ATTACHED, function() {\n";
			echo "			video.muted = false;\n";
			echo "			video.play();\n";
			echo "		});\n";
			echo "	}\n";
			echo "	else if (video.canPlayType('application/vnd.apple.mpegurl')) {\n";
			echo "		video.src = '$decodedChannelLink';\n";
			echo "		video.addEventListener('canplay',function() {\n";
			echo "			video.play();\n";
			echo "		});\n";
			echo "	}\n";
			# start playback on page load
			echo "hls.on(Hls.Events.MANIFEST_PARSED,playVideo);\n";
			echo "</script>\n";
		}
	}
?>
	<div class='videoPlayerControls'>
		<div class='left'>
			<span>
				<button id='playButton' class='button' style='display:none;' onclick='playPause()' alt='play'>&#9654;</button>
				<button id='pauseButton' onload='setPlayButtonState()' class='button' onclick='playPause()' alt='pause'>&#9208;</button>
			</span>
		</div>
		<div class='right'>
			<span>
				<button class='button' onclick='volumeDown()'>🔉</button>
				<span id='currentVolume'>100</span>%
					<button class='button' onclick='volumeUp()'>🔊</button>
				</span>
				<span>
					<button id='showControls' onload='hideControls()' class='button' onclick='showControls()'>🔼</button>
					<button id='hideControls' class='button' style='display:none;' onclick='hideControls()'>🔽</button>
				</span>
			<span>
				<button id='fullscreenButton' class='button' onclick='openFullscreen()'>⛶</button>
				<button id='exitFullscreenButton' class='button' style='display:none;' onclick='closeFullscreen()'>⛶</button>
			</span>
		</div>
	</div>
</div>
	<div class='channelList'>
		<?PHP
			include('/var/cache/2web/web/live/channelList.php')
		?>
	</div>
</div>
<div>
<br>
<div class='descriptionCard'>

	<?PHP
		echo "<a class='channelLink' href='/live/channels/channel_".$channelNumber.".php#".$channelNumber."'>";
		echo "$channelTitle";
		?>
	</a>
	<?PHP
	echo "<a class='button hardLink' href='$decodedChannelLink'>"
	?>
		🔗 Direct Link
	</a>
	<?PHP
	echo "<a class='button hardLink' target='_new' href='/client/?play=$channelLink'>";
	?>
		🎟️ Play on Client
	</a>
	<?PHP
	echo "<a class='button hardLink' target='_new' href='/kodi-player.php?url=$channelLink'>";
	?>
		🇰Play on KODI
	</a>
	<?PHP
	echo "<a class='button hardLink vlcButton' href='vlc://".str_replace(" ","%20",urldecode($decodedChannelLink))."'>";
	?>
		<span id='vlcIcon'>&#9650;</span> VLC
	</a>
	<div class='listCard'>
		<?PHP
		echo "$channelGroups";
		?>
	</div>
	<div class='titleCard'>
	<h2>Link</h2>
				<?PHP
				echo urldecode($channelLink);
				?>
	</div>
</div>
<?PHP
	include('/var/cache/2web/web/randomChannels.php');
	include('/var/cache/2web/web/footer.php');
?>
<hr class='topButtonSpace'>
</body>
</html>
