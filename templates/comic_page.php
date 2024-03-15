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
		if ( $lastPage < 1 ){
			# this means that this is the first page
			$lastPage = "index.php";
			$lastPageNumber = "Back";
		}else{
			$lastPage = prefixNumbers($lastPage).".php";
			$lastPageNumber = prefixNumbers($lastPageNumber);
		}
		if ( $nextPage > $totalPages ){
			$nextPage = "index.php";
			$nextPageNumber = "Back";
		}else{
			$nextPage = prefixNumbers($nextPage).".php";
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
		echo "}\n";
	?>
	</style>
	<script src='/2webLib.js'></script>
		<script>
		// setup keyboard controls
		function setupKeys() {
			document.body.addEventListener('keydown', function(event){
				const key = event.key;
				switch (key){
					case 'ArrowLeft':
					event.preventDefault();
					<?PHP
					echo "window.location.href='./$lastPage';";
					?>
					break;
					case 'ArrowRight':
					event.preventDefault();
					<?PHP
					echo "window.location.href='./$nextPage';";
					?>
					break;
					case 'ArrowUp':
					event.preventDefault();
					window.location.href='index.php';
					break;
					case 'Home':
					event.preventDefault();
					window.location.href='index.php';
					break;
					case 'PageDown':
					event.preventDefault();
					<?PHP
					echo "window.location.href='./$nextPage';";
					?>
					break;
					case 'PageUp':
					event.preventDefault();
					<?PHP
					echo "window.location.href='./$lastPage';";
					?>
					break;
				}
			});
		}
	</script>
</head>
<img class='globalPulse' src='/pulse.gif'>
<?PHP
	# send the loading bar code while the page loads the rest of the content
	flush();
	ob_flush();
	if(file_exists($page.".jpg")){
		$imageSizeData=getimagesize($page.".jpg");
	}else{
		$imageSizeData=getimagesize($page.".png");
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

	echo "<body onload='setupKeys();'>\n";
	echo "<div id='comicPane' class='$comicPaneType' style='background: ".'url("'.$page.'-thumb.png")'."'>\n";
	echo "<div id='comicThumbPane' class='$comicThumbType' style='background: ".'url("'.$page.'.jpg")'."'>\n";
	echo "	<a href='$lastPage' class='comicPageButton comicPageButtonLeft left'>\n";
	echo "		&#8617;\n";
	echo "		<br>\n";
	echo "		<span class='comicPageNumbers'>\n";
	echo "			".$lastPageNumber."\n";
	echo "		</span>\n";
	echo "	</a>\n";
	echo "	<a href='$nextPage' class='comicPageButton comicPageButtonRight right'>\n";
	echo "		&#8618;\n";
	echo "		<br>\n";
	echo "		<span class='comicPageNumbers'>\n";
	echo "			".$nextPageNumber."\n";
	echo "		</span>\n";
	echo "	</a>\n";
	echo "	<a class='comicIndexButton comicPageButton center' href='index.php#$page'>\n";
	echo "		&uarr;\n";
	echo "	</a>\n";
	echo "	<div id='comicPagePopup' class='comicPagePopup center'>\n";
	echo "		Page $page / ".prefixNumbers($totalPages)."\n";
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
	#
	echo "</div>\n";
	echo "</div>\n";
	echo "<span id='bottom'></span>\n";
?>
	<script>
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
	# hide the pulse after the page has loaded everything
	?>
	<style>
		.globalPulse{
			visibility: hidden;
		}
	</style>
</body>
</html>
