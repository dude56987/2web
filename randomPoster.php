<?php
	ini_set('display_errors', 1);
	$fileContent = file_get_contents("poster.cfg");
	//echo(str($fileContents));
	$backgrounds = explode("\n", $fileContent);
	//echo(str($backgrounds));
	shuffle($backgrounds);
	//echo(str($backgrounds));
	//echo(str($backgrounds[0]));
	// redirect to location of random background
	header("Cache-Control: no-store, no-cache");
	header('Content-type: image/png');
	header('Location: '.$backgrounds[0]);
?>
