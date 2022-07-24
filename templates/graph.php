<html id='top' class='seriesBackground'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>
	<?PHP
		# get the show name
		$data=getcwd();
		$data=explode('/',$data);
		$graph=array_pop($data);
		echo ":root{";
		echo "--backgroundPoster: url('/graphs/$graph/graph.png');";
		echo "--backgroundFanart: url('/graphs/$graph/graph.png');";
		echo"}";
	?>
	</style>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
# add the base php libary
include("/usr/share/2web/2webLib.php");
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<div class='settingListCard'>
<?php
	echo "<h1>".ucfirst(file_get_contents("title.cfg"))."</h1>";
	echo "<a class='graphLink' href='$graph-day.png'>";
	echo "<img src='$graph-day.png'>";
	echo "</a>";
	echo "<a class='graphLink' href='$graph-week.png'>";
	echo "<img src='$graph-week.png'>";
	echo "</a>";
	echo "<a class='graphLink' href='$graph-month.png'>";
	echo "<img src='$graph-month.png'>";
	echo "</a>";
	echo "<a class='graphLink' href='$graph-year.png'>";
	echo "<img src='$graph-year.png'>";
	echo "</a>";
?>
</div>
<?php
	// add random comics above the footer
	drawPosterWidget("graphs", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
