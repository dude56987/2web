<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("comic2web");
?>
<!--
########################################################################
# The default 2web comic viewer fullscreen mode
# Copyright (C) 2024  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
########################################################################
-->
<html id='top' class='comicPageBackground'>
<head>
	<meta meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" >
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	</style>
	<script>
		function comicFullscreenEnable(){
			toggleFullscreen();
			// remove the check dialog
			document.getElementById("comicFullscreenCheck").remove();
		}
		function loadKeys(){
			// setup hotkey to enable fullscreen
			document.body.addEventListener('keydown', function(event){
				const key = event.key;
				console.log(key);
				switch (key){
					case 'Insert':
					event.preventDefault();
					//comicFullscreenEnable();
					document.getElementById("enable").click();
					console.log("Fullscreen Key Detected");
					break;
					case 'ArrowLeft':
					event.preventDefault();
					document.getElementById("comicFullscreen").contentWindow.document.getElementById("leftButton").click();
					break;
					case 'ArrowRight':
					event.preventDefault();
					document.getElementById("comicFullscreen").contentWindow.document.getElementById("rightButton").click();
					break;
					case 'ArrowUp':
					event.preventDefault();
					window.location.href='index.php';
					window.open('index.php','_parent');
					break;
					case 'Home':
					event.preventDefault();
					window.open('index.php','_parent');
					break;
					case 'PageDown':
					event.preventDefault();
					document.getElementById("rightButton").click();
					document.getElementById("comicFullscreen").contentWindow.document.getElementById("rightButton").click();
					break;
					case 'PageUp':
					event.preventDefault();
					document.getElementById("comicFullscreen").contentWindow.document.getElementById("leftButton").click();
					break;
				}
			});
		}
	</script>
</head>
<body id='body'>
	<div id='comicFullscreenCheck' class=''>
		<p>Would you like to enable fullscreen mode?</p>
		<div class='listCard'>
			<a onclick='comicFullscreenEnable()' class='button' id='enable'>🟢 Enable</a>
			<?PHP
			if (array_key_exists("page",$_GET)){
				echo "<a target='_parent' href='".$_GET["page"].".php' class='button' id='disable'>🔴 Disable</a>";
			}else{
				echo "<a target='_parent' href='index.php' class='button' id='disable'>🔴 Disable</a>";
			}
			?>
		</div>
	</div>
	<?PHP
	# check for ?page=
	if (array_key_exists("page",$_GET)){
		echo "<iframe onload='loadKeys()' name='comicFullscreen' id='comicFullscreen' src='".$_GET["page"].".php?fullscreen'>";
	}else{
		echo "<iframe onload='loadKeys()' name='comicFullscreen' id='comicFullscreen' src='0001.php?fullscreen'>";
	}
	?>
	<style>
		.globalPulse{
			visibility: hidden;
		}
	</style>
</body>
</html>
