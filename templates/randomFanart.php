<?php
	ini_set('display_errors', 1);
	$fileContent = file_get_contents("fanart.cfg");
	$backgrounds = explode("\n", $fileContent);
	shuffle($backgrounds);
	// redirect to location of random background
	//header("Cache-Control: no-store, no-cache");
	header('Content-type: image/png');
	header('Cache-Control: max-age=90');
	//header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
	header('Location: '.$backgrounds[0]);
?>
