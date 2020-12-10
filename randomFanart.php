<?php
	ini_set('display_errors', 1);
	// check if

	$fileContent = file_get_contents("fanart.cfg");
	//echo(str($fileContents));
	$backgrounds = explode("\n", $fileContent);
	//echo(str($backgrounds));
	shuffle($backgrounds);
	//echo(str($backgrounds));
	//echo(str($backgrounds[0]));
	// redirect to location of random background
	//header("Cache-Control: no-store, no-cache");
	header('Content-type: image/png');
	header('Cache-Control: max-age=90');
	//header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
	header('Location: '.$backgrounds[0]);
?>
