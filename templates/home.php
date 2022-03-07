<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
	<script src='/nfo2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("header.php");
?>
<!-- create top jump button -->
<a href='#' id='topButton' class='button'>&uarr;</a>

<?php
	if (file_exists("progress.index")){
		include("progress.index");
	}
	if (file_exists("stats.index")){
		include("stats.index");
	}
	if (file_exists("shows")){
		if (file_exists("updatedEpisodes.php")){
			include($_SERVER['DOCUMENT_ROOT']."/updatedEpisodes.php");
		}
		if (file_exists("updatedShows.php")){
			include($_SERVER['DOCUMENT_ROOT']."/updatedShows.php");
		}
	}
	if (file_exists("movies")){
		if (file_exists("updatedShows.php")){
			include($_SERVER['DOCUMENT_ROOT']."/updatedMovies.php");
		}
	}
	if (file_exists("movies")){
		if (file_exists("randomMovies.php")){
			include($_SERVER['DOCUMENT_ROOT']."/randomMovies.php");
		}
	}
	if (file_exists("shows")){
		if (file_exists("randomShows.php")){
			include($_SERVER['DOCUMENT_ROOT']."/randomShows.php");
		}
	}
	if (file_exists("comics")){
		if (file_exists("updatedComics.php")){
			include($_SERVER['DOCUMENT_ROOT']."/updatedComics.php");
		}
	}
	if (file_exists("comics")){
		if (file_exists("randomComics.php")){
			include($_SERVER['DOCUMENT_ROOT']."/randomComics.php");
		}
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
	// add the footer
	include("header.php");
?>

</body>
</html>
