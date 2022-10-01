<html id='top' class='randomFanart'>
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
if (array_key_exists("timespan",$_GET)){
	$timespan=($_GET['timespan']);
	echo "<h1>".ucfirst(file_get_contents("title.cfg"))." - ".ucfirst($timespan)."</h1>";
}else{
	echo "<h1>".ucfirst(file_get_contents("title.cfg"))."</h1>";
}
?>

<?php
if (array_key_exists("timespan",$_GET)){
	echo "<a class='graphLink' href='$graph-$timespan.png'>";
	echo "<img src='$graph-$timespan.png'>";
	echo "</a>";
}else{
	echo "<a class='graphLink' href='$graph-day.png'>";
	echo "<img src='$graph-day.png'>";
	echo "</a>";
}
?>
<div class='titleCard'>
	<h2></h2>
	<div class='listCard'>
		<h2></h2>
		<a class='button' href='?timespan=day'>Day</a>
		<a class='button' href='?timespan=week'>Week</a>
		<a class='button' href='?timespan=month'>Month</a>
		<a class='button' href='?timespan=year'>Year</a>
	</div>
</div>

</div>
<?php
	// add random comics above the footer
	drawPosterWidget("graphs", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
