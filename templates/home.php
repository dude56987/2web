<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("header.php");
	include("/usr/share/2web/2webLib.php");
?>

<?php
	if (file_exists("progress.index")){
		include("progress.index");
	}
	if (file_exists("stats.php")){
		include("stats.php");
	}
	if (file_exists("shows")){
		drawPosterWidget("episodes");
		drawPosterWidget("shows");
	}
	if (file_exists("movies")){
		drawPosterWidget("movies");
		# random movies
		drawPosterWidget("movies", True);
	}
	if (file_exists("shows")){
		# random
		drawPosterWidget("shows", True);
	}
	if (file_exists("comics")){
		drawPosterWidget("comics");
		drawPosterWidget("comics", True);
	}
	if (file_exists("music")){
		drawPosterWidget("albums");
		drawPosterWidget("artists");
		drawPosterWidget("music", True);
	}
	if (file_exists("live")){
		if (file_exists("updatedChannels.php")){
			include($_SERVER['DOCUMENT_ROOT']."/updatedChannels.php");
		}
	}
	if (file_exists("live")){
		if (file_exists("randomChannels.php")){
			include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
		}
	}
	if (file_exists("graphs")){
		drawPosterWidget("graphs", True);
	}
	// add the footer
	include("footer.php");
?>

</body>
</html>
