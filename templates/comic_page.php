<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("comic2web");
?>
<!--
########################################################################
# The default 2web comic viewer
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
<?PHP
	# get the show name
	$data=getcwd();
	$data=explode('/',$data);
	$comic=array_pop($data);
	$page=str_replace(".php","",basename($_SERVER["PHP_SELF"]));

	function prefixNumbers($pageNumber){
		if($pageNumber < 10){
			return "000$pageNumber";
		}else if($pageNumber < 100){
			return "00$pageNumber";
		}else if ($pageNumber < 1000){
			return "0$pageNumber";
		}else{
			return "$pageNumber";
		}
	}
?>
<html id='top' class='comicPageBackground'>
<head>
	<meta meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" >
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
		# get the total pages from the stored location
		$totalPages=file_get_contents("totalPages.cfg");
		# convert total pages into a interger
		$totalPages=(int)$totalPages;
		# next and last page numbers
		# build the next page button info
		$nextPage = (int)$page;
		$nextPage = $nextPage + 1;
		$nextPageNumber = $nextPage;
		# build the last page button info
		$lastPage = (int)$page;
		$lastPage = $lastPage - 1;
		$lastPageNumber = $lastPage;
		#
		if (array_key_exists("fullscreen",$_GET)){
			$isFullscreen = true;
		}else{
			$isFullscreen = false;
		}
		#
		if ( $lastPage < 1 ){
			# this means that this is the first page
			$lastPageTarget = "_parent";
			$lastPage = "index.php";
			$lastPageNumber = "Back";
		}else{
			# all other pages
			if($isFullscreen){
				# if fullscreen is enabled add fullscreen to page links to remain in fullscreen
				$lastPageTarget = "comicFullscreen";
				$lastPage = prefixNumbers($lastPage).".php?fullscreen";
			}else{
				$lastPageTarget = "_parent";
				$lastPage = prefixNumbers($lastPage).".php";
			}
			$lastPageNumber = prefixNumbers($lastPageNumber);
		}
		if ( $nextPage > $totalPages ){
			# this is the last page
			$nextPageTarget = "_parent";
			$nextPage = "index.php";
			$nextPageNumber = "Back";
		}else{
			# this is all other pages
			if($isFullscreen){
				$nextPageTarget = "comicFullscreen";
			$nextPage = prefixNumbers($nextPage).".php?fullscreen";
			}else{
				$nextPageTarget = "_parent";
				$nextPage = prefixNumbers($nextPage).".php";
			}
			$nextPageNumber = prefixNumbers($nextPageNumber);
		}
		#
		echo "<title>$comic - Page $page / ".prefixNumbers($totalPages)."</title>\n";
	?>
	<style>
	<?PHP
		echo "html{\n";
		echo "	background-image: url('".$page."-thumb.png');\n";
		echo "	background-blend-mode: color-burn;\n";
		echo "	background-size: cover;\n";
		echo "}\n";
	?>
	</style>
	<script src='/2webLib.js'></script>
		<script>
		// build the javascript zoom box that is activated when the picture is left clicked
		function zoomInBox(idTag, zoom) {
			var img;
			var zoomBox;
			var w;
			var h;
			var comicPane;
			//
			// get the image
			img = document.getElementById(idTag);
			// get the distance of
			comicPane = document.getElementById("comicPane");
			// comicPane.offsetLeft
			// create a zoom box container
			zoomBox = document.createElement("div");
			zoomBox.setAttribute("id", "zoomBox");
			zoomBox.setAttribute("class", "zoomBox");
			// insert the container
			img.parentElement.insertBefore(zoomBox, img);
			// setup css inside the zoom box
			zoomBox.style.backgroundImage = img.style.backgroundImage;
			zoomBox.style.backgroundRepeat = "no-repeat";
			// set the background size
			zoomBox.style.backgroundSize = (img.offsetWidth * zoom) + "px " + (img.offsetHeight * zoom) + "px";
			/////////////////////////////////////////////////////////////////////////////////////////
			// Create functions for the events to call
			// - Events are setup below the functions
			/////////////////////////////////////////////////////////////////////////////////////////
			function readCursorPos(eventObject) {
				// get the mouse cursor pos
				var a;
				var cursorX = 0;
				var cursorY = 0;
				eventObject = eventObject || window.event;
				// get bounds of image
				a = img.getBoundingClientRect();
				// get cursor pos
				cursorX = eventObject.pageX - a.left;
				cursorY = eventObject.pageY - a.top;
				// account for scrolling
				cursorX = cursorX - window.pageXOffset;
				cursorY = cursorY - window.pageYOffset;
				// clamp the cursor position to the page
				// - this prevents continuous scroll
				if(cursorX > document.body.offsetWidth){
					cursorX = document.body.offsetWidth;
				}else if(cursorX < 0){
					cursorX = 0;
				}
				if(cursorY > document.body.offsetHeight){
					cursorY = document.body.offsetHeight;
				}else if(cursorY < 0){
					cursorY = 0;
				}
				// return the x,y pos
				return {x : cursorX, y : cursorY};
			}
			function moveZoomBox(eventObject) {
				// set the background size so that if the page is zoomed it will adjust
				zoomBox.style.backgroundSize = (img.offsetWidth * zoom) + "px " + (img.offsetHeight * zoom) + "px";
				// get the zoom box width and height
				w = zoomBox.offsetWidth / 2;
				h = zoomBox.offsetHeight / 2;
				// move the zoom box position
				var pos;
				var x;
				var y;
				// stop default events
				eventObject.preventDefault();
				// get the current position of the cursor
				pos = readCursorPos(eventObject);
				x = pos.x;
				y = pos.y;
				// set the zoom box location
				zoomBox.style.left = ( ( x - w ) ) + "px";
				zoomBox.style.top = ( ( y - h ) ) + "px";
				// enable the zoom box
				zoomBox.style.display = "inline";
				// set the inside of the zoom box
				zoomBox.style.backgroundPosition = "" + (((x * zoom) - w ) * -1) + "px " + (((y * zoom) - h ) * -1) + "px";
			}
			////////////////////////////////////////////////////////////////////////////////
			// create events for activating the zoom for mouse and touchscreens
			////////////////////////////////////////////////////////////////////////////////
			img.addEventListener("mousemove", moveZoomBox);
			zoomBox.addEventListener("mousemove", moveZoomBox);
		}
		// setup keyboard controls
		function setupKeys() {
			document.body.addEventListener('keydown', function(event){
				const key = event.key;
				console.log(key);
				switch (key){
					case 'ArrowLeft':
					event.preventDefault();
					<?PHP
					echo "window.open('./$lastPage','comicFullscreen');";
					?>
					break;
					case 'ArrowRight':
					event.preventDefault();
					<?PHP
					echo "window.open('./$nextPage','$nextPageTarget');";
					?>
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
					<?PHP
					echo "window.open('./$nextPage','$nextPageTarget');";
					?>
					break;
					case 'PageUp':
					event.preventDefault();
					<?PHP
					echo "window.open('./$lastPage','$lastPageTarget');";
					?>
					break;
					case 'Insert':
					event.preventDefault();
					<?PHP
					echo "window.open('fullscreen.php?page=$page','_parent');";
					?>
					break;
					case 'ArrowDown':
					event.preventDefault();
					<?PHP
					echo "window.open('fullscreen.php?page=$page','_parent');";
					?>
					break;
				}
			});
		}
	</script>
</head>
<img class='globalSpinner' src='/spinner.gif'>
<?PHP
	# send the loading bar code while the page loads the rest of the content
	flush();
	ob_flush();
	if(! is_readable($page.".jpg")){
		# if the media file is not readable load the blocked permission error
		echo "<body id='body' onload='setupKeys();'>\n";
		echo "<div id='comicFullscreenCheck' class=''>";
		echo "	<p>Permissions to access this page has been disabled by the server administrator.</p>";
		echo "	<div class='listCard'>";
		echo "		<a class='button' href='index.php'>Close Comic</a>";
		echo "	</div>";
		echo "	<div class='listCard'>";
		echo "		<a class='button' target='$lastPageTarget' href='$lastPage'>Last Page</a>";
		echo "		<a class='button' target='$nextPageTarget' href='$nextPage'>Next Page</a>";
		echo "	</div>";
		echo "</div>";
		echo "</body>";
		echo "</html>";
		exit();
	}
	if(mime_content_type($page.".jpg") == "image/jpeg"){
		$imageSizeData=getimagesize($page.".jpg");
		$videoFile=false;
	}else if(mime_content_type($page.".jpg") == "image/png"){
		$imageSizeData=getimagesize($page.".jpg");
		$videoFile=false;
	}else if(mime_content_type($page.".jpg") == "video/webm"){
		# use the thumbnail for dimensions in animated webm files
		$imageSizeData=getimagesize($page."-thumb.png");
		$videoFile=true;
		$videoMimeType="video/webm";
	}else if(mime_content_type($page.".jpg") == "video/mp4"){
		# use the thumbnail for dimensions in animated mp4 files
		$imageSizeData=getimagesize($page."-thumb.png");
		$videoFile=true;
		$videoMimeType="video/mp4";
	}else{
		# try to get the image file size data
		$imageSizeData=getimagesize($page.".jpg");
		$videoFile=false;
	}
	$imageWidth=$imageSizeData[0];
	$imageHeight=$imageSizeData[1];

	if ($imageWidth > $imageHeight){
		logPrint("Image width is larger than the height: landscape");
		$comicPaneType="comicWidePane";
		$comicThumbType="comicThumbWidePane";
	}else{
		logPrint("Image height is larger than the width: portrait");
		$comicPaneType="comicPane";
		$comicThumbType="comicThumbPane";
	}

	echo "<body id='body' onload='setupKeys();'>\n";
	if($videoFile){
		echo "<div id='comicPane' class='$comicPaneType zoomBoxContainer' >\n";
	}else{
		echo "<div id='comicPane' class='$comicPaneType zoomBoxContainer' style='background: ".'url("'.$page.'-thumb.png")'."'>\n";
	}
	echo "<div id='comicThumbPane' class='$comicThumbType' style='background: ".'url("'.$page.'.jpg")'."'>\n";
	if($videoFile){
		echo "<video class='comicVideo' loop autoplay controls>\n";
		echo "<source src='".$page.".jpg' type='$videoMimeType'>\n";
		echo "</video>\n";
	}
	echo "	<a id='leftButton' onclick='showSpinner();' target='$lastPageTarget' href='$lastPage' class='comicPageButton comicPageButtonLeft left'>\n";
	echo "		&#8617;\n";
	echo "		<br>\n";
	echo "		<span class='comicPageNumbers'>\n";
	echo "			".$lastPageNumber."\n";
	echo "		</span>\n";
	echo "	</a>\n";
	echo "	<a id='rightButton' onclick='showSpinner();' target='$nextPageTarget' href='$nextPage' class='comicPageButton comicPageButtonRight right'>\n";
	echo "		&#8618;\n";
	echo "		<br>\n";
	echo "		<span class='comicPageNumbers'>\n";
	echo "			".$nextPageNumber."\n";
	echo "		</span>\n";
	echo "	</a>\n";
	echo "	<a target='_parent' class='comicIndexButton comicPageButton center' href='index.php#$page'>\n";
	echo "		&uarr;\n";
	echo "	</a>\n";
	echo "	<div id='comicPagePopup' class='comicPagePopup center'>\n";
	echo "		Page $page / ".prefixNumbers($totalPages)."\n";
	# draw the fullscreen button
	echo "	<a target='_parent' class='comicFullscreenButton' href='fullscreen.php?page=".$page."'>";
	echo "		⛶";
	echo "	</a>\n";
	# convert to intergers for use with the scroll view API
	# and for calculating the reading progress
	$page=(int)$page;
	# draw the progress bar
	$progress=floor(($page/$totalPages)*100);
	# Draw the progress bar showing reading progress of the comic
	echo "<div class='progressBar'>\n";
	echo "\t<div class='progressBarBar' style='width: ".$progress."%;'>\n";
	echo ($progress."%");
	echo "\t</div>\n";
	echo "</div>\n";
	# check if this may be a scroll
	if ($imageHeight > ( $imageWidth * 4 )){
		# the image is to long to be read by the page view add a link to scroll view
		echo "<div class='listCard'>";
		echo "	<a class='button' href='scroll.php?real#$page'>";
		echo "		🖼️ Real Size View";
		echo "	</a>\n";
		echo "	<a class='button' href='scroll.php#$page'>";
		echo "		📜 Scroll View";
		echo "	</a>\n";
		echo "</div>";
	}
	#
	echo "</div>\n";
	echo "</div>\n";
	echo "<span id='bottom'></span>\n";
?>
	<script>
		// load the zoomBox
		zoomInBox("comicThumbPane",2);
		// value to tell if the zoomBox should be enabled by a mouse down event
		var zoomBoxEnabled = true;
		document.body.ontouchstart = function(){
			// disable the zoombox when a touchscreen is touched
			console.log("touch start");
			// remove the zoombox element
			document.getElementById("zoomBox").remove();
		}
		// activate the zoom if the mouse is clicked and no buttons are active
		document.body.onmousedown = function(){
			console.log("mouseDown");
			if(zoomBoxEnabled){
				console.log("zoom is enabled");
				// turn on the zoom
				//document.getElementById("zoomBox").style.display="inline";
				document.getElementById("zoomBox").style.visibility="visible";
				// hide the buttons while the zoom is active
				document.querySelectorAll(".comicPageButton, #comicPagePopup").forEach(button => {
					button.style.display="none";
				});
			}else{
				console.log("zoom is disabled");
			}
		}
		// create events for all the buttons to disable using the zoom when the buttons are hovered
		document.querySelectorAll(".comicPageButton, #comicPagePopup").forEach(button => {
			button.onmouseenter = function(){
				console.log("button mouseEnter");
				zoomBoxEnabled = false;
				document.getElementById("zoomBox").style.display="none";
			}
			button.onmouseleave = function(){
				console.log("button mouseLeave");
				zoomBoxEnabled = true;
				document.getElementById("zoomBox").style.display="inline";
			}
		});
		// reshow the buttons when the zoom is disabled
		document.body.onmouseup = function(){
			console.log("mouseUp");
			//document.getElementById("zoomBox").style.display="none";
			document.getElementById("zoomBox").style.visibility="hidden";
			// unhide the buttons
			document.querySelectorAll(".comicPageButton, #comicPagePopup").forEach(button => {
				button.style.display="inline";
			});
		}
		// show the progress popup with javascript when the page is loaded, then hide it again
		function reHidePopup(){
			console.log("Re hiding the popup")
			// hide the popup
			document.getElementById('comicPagePopup').style.cssText="";
		}
		console.log("Showing the popup")
		// show the popup by changing the inline style text
		document.getElementById('comicPagePopup').style.cssText="opacity: 1 !important;";
		// hide the popup after a delay
		setTimeout(reHidePopup, 1000);
	</script>
	<?PHP
	#
	if($videoFile){
		# remove the zoombox on videos
		echo '<script>';
		echo 'document.getElementById("zoomBox").remove();';
		echo '</script>';
	}
	?>
	<style>
		.globalPulse{
			visibility: hidden;
		}
		.globalSpinner{
			visibility: hidden;
		}
	</style>
	<script>
		addEventListener("pageshow", (event) => {
			hideSpinner();
		})
	</script>
</body>
</html>
