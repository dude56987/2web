<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
<script>
	<?php
		include("nfo2web.js");
	?>
</script>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("header.php");
?>
<!-- create top jump button -->
<a href='#top' id='topButton' class='button'>&uarr;</a>

<?php
	if (file_exists("progress.index")){
		include("progress.index");
	}
	if (file_exists("stats.index")){
		include("stats.index");
	}
	if (file_exists("updatedShows.index")){
		$tempPrefix="shows/";
		$tempString=(str_replace("href='","href='$tempPrefix",str_replace("src='","src='$tempPrefix",file_get_contents("updatedShows.index"))));
		echo "$tempString";
	}
	if (file_exists("updatedMovies.index")){
		$tempPrefix="movies/";
		$tempString=(str_replace("href='","href='$tempPrefix",str_replace("src='","src='$tempPrefix",file_get_contents("updatedMovies.index"))));
		echo "$tempString";
	}
	if (file_exists("randomShows.index")){
		$tempPrefix="shows/";
		$tempString=(str_replace("href='","href='$tempPrefix",str_replace("src='","src='$tempPrefix",file_get_contents("randomShows.index"))));
		echo "$tempString";
	}
	if (file_exists("randomMovies.index")){
		$tempPrefix="movies/";
		$tempString=(str_replace("href='","href='$tempPrefix",str_replace("src='","src='$tempPrefix",file_get_contents("randomMovies.index"))));
		echo "$tempString";
	}
	include($_SERVER['DOCUMENT_ROOT']."/updatedComics.php");
	include($_SERVER['DOCUMENT_ROOT']."/randomComics.php");
	include($_SERVER['DOCUMENT_ROOT']."/updatedChannels.php");
	include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
	// add the footer
	include("header.php");
?>

</body>
</html>
