<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
</script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include("/usr/share/2web/2webLib.php");
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<?php
################################################################################
?>
<div class='settingListCard'>
<?php
if (file_exists("fortune.index")){
	$todaysFortune = file_get_contents("fortune.index");

	echo "<h3>Fortune</h3>";
	echo "<div class='fortuneText'>";
	echo "$todaysFortune";
	echo "</div>";
}else{
	echo "No fortune has been generated yet...";
}

?>
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
