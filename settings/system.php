<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include("header.html");
?>

<h2>Settings</h2>
<hr>
<div class='header'>
	<a class='button' href='system.php'>SYSTEM</a>
	<a class='button' href='tv.php'>TV</a>
	<a class='button' href='radio.php'>RADIO</a>
</div>

<div class='inputCard'>
	<h2>Update</h2>
	<form action='admin.php' class='buttonForm' method='post'>
		<button class='button' type='submit' name='update' value='true'>UPDATE</button>
	</form>
</div>
<!-- create the theme picker based on installed themes -->
<div class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Web Theme</h2>
		<select name='theme'>
			<option value='Default' >Default</option>
		</select>
	</form>
</div>

</body>
</html>
