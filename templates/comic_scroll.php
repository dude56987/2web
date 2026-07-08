<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("comic2web");
?>
<!--
########################################################################
# 2web comic viewer scroll reading interface
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
-->
<html id='top' class=''>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>
	<?PHP
		# load up a single chapter
		if (array_key_exists("chapter",$_GET)){
			$chapterNumber=$_GET['chapter'];
		}

		# get the show name
		$data=getcwd();
		$data=explode('/',$data);
		$comic=array_pop($data);
		#echo ":root{";
		#echo "--backgroundPoster: url('/comics/$comic/thumb.png');";
		#echo "--backgroundFanart: url('/comics/$comic/thumb.png');";
		#echo "--backgroundPoster: url('thumb.png');";
		#echo "--backgroundFanart: url('thumb.png');";
		#echo"}";
		#$totalPages=file_get_contents("totalPages.cfg");
		#
		echo "html{\n";
		if (array_key_exists("chapter",$_GET)){
			echo "	background-image: url('/comics/$comic/$chapterNumber/thumb.png');\n";
		}else{
			echo "	background-image: url('/comics/$comic/thumb.png');\n";
		}
		echo "	background-size: cover;\n";
		echo "}\n";
	?>
	</style>
	<?PHP
		echo "<title>".basename(dirname($_SERVER["PHP_SELF"]))."</title>\n";
	?>
</head>
<body>
<?php
################################################################################
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<a id='comicScrollPauseButton' class='comicScrollPauseButton button' onclick='ToggleAutoscroll();'>⏯️</a>
<?PHP
	# draw header
	echo "<div id='comicScrollReader'>\n";
	echo "<div id='readerTitle' class='titleCard'>\n";
	echo "<h1>$comic</h1>\n";


	#echo "comic='$comic'<br>\n";
	#echo "comicPath='".$_SERVER['DOCUMENT_ROOT']."/comics/".$comic."/'<br>\n";
	#var_dump(recursiveScan($_SERVER['DOCUMENT_ROOT']."/comics/".$comic."/"));

	# get a list of all directories and list them as chapters in scroll view
	$discoveredDirs=scanDir($_SERVER['DOCUMENT_ROOT']."/comics/".$comic."/");
	$discoveredDirs=array_diff($discoveredDirs,Array('..','.'));

	if (array_key_exists("chapter",$_GET)){
		$discoveredFiles=recursiveScan($_SERVER['DOCUMENT_ROOT']."/comics/".$comic."/".$chapterNumber."/");
	}else{
		$discoveredFiles=recursiveScan($_SERVER['DOCUMENT_ROOT']."/comics/".$comic."/");
	}
	$tempFileList=Array();
	foreach($discoveredFiles as $fileName){
		if (stripos($fileName,".jpg")){
			$tempFileList=array_merge($tempFileList,Array($fileName));
		}
	}
	$discoveredFiles=$tempFileList;

	$tempDirList=Array();
	foreach($discoveredDirs as $dirName){
		if (is_dir($dirName)){
			$tempDirList=array_merge($tempDirList,Array($dirName));
		}
	}
	$discoveredDirs=$tempDirList;

	# come up with chapter and page counts from cleaned lists
	$totalPages=count($discoveredFiles);
	$totalChapters=count($discoveredDirs);
	echo "<hr>\n";
	echo "<div class='listCard'>\n";
	echo "<a class='button' href='index.php'>📑 Overview</a>\n";
	echo "<a class='button' href='0001.php'>📖 Reader View</a>\n";

	if (array_key_exists("real",$_GET)){
		# mark realsize to true
		$realSize=True;
		echo "<a class='button' href='?'>📜 Scroll View</a>\n";
	}else{
		$realSize=False;
		echo "<a class='button' href='?real'>🖼️ Real Size View</a>\n";
	}

	echo "<a class='button' onclick='toggleFullscreen(\"comicScrollReader\",true);'>⛶ Toggle Fullscreen</a>\n";

	echo "<a class='button' onclick='autoScrollComic(3);notify(\"⏬\");'>⏬ Default Auto Scroll</a>\n";
	echo "<a class='button' onclick='autoScrollComic(2);notify(\"🔽\");'>🔽Slow Auto Scroll</a>\n";
	echo "<a class='button' onclick='autoScrollComic(1);notify(\"🐢\");'>🐢Slower Auto Scroll</a>\n";
	#
	echo "<a class='button' onclick='toggleLightSwitch();notify(\"🌕/☀️\");'>🌕/☀️ LightSwitch</a>\n";

	echo "</div>";
	echo "<div>";
	drawStat("Total Pages",$totalPages);
	echo "</div>";
	echo "<hr>\n";
	if ($totalChapters > 0){
		echo "<div>";
		echo "Chapters: $totalChapters";
		echo "</div>";

		echo "	<div class='listCard'>\n";
		echo "		<a id='all' class='button' href='?#all'>All</a>\n";
		foreach($discoveredDirs as $fileName){
			echo "		<a id='$fileName' class='button' href='?chapter=$fileName#$fileName'>\n";
			echo "			Chapter $fileName\n";
			echo "		</a>\n";
		}
		echo "	</div>\n";
	}

	echo "</div>\n";
	echo "<div id='bookPages' class='settingListCard'>";
	$tempPageNumber=0;

	if (array_key_exists("chapter",$_GET)){
		echo "<h2>Chapter $chapterNumber</h2>";
	}
	# check each file for .jpg extension then write to page as scroll page
	foreach($discoveredFiles as $fileName){
		# remove document root from path
		$tempFileName=str_replace($_SERVER["DOCUMENT_ROOT"],"",$fileName);
		$tempFileThumb=str_replace(".jpg","-thumb.png",$tempFileName);

		$tempPageNumber+=1;

		#$tempPageNumber=explode('/',$tempFileName);
		#$tempPageNumber=array_pop($tempPageNumber);
		#$tempPageNumber=str_replace(".jpg","",$tempPageNumber);
		$tempPageNumberString=str_pad($tempPageNumber,4,"0",STR_PAD_LEFT);
		if($realSize){
			echo "<img id='$tempPageNumberString' class='comicScrollViewImgReal' loading='lazy' src='$tempFileName' />";
		}else{
			echo "<img id='$tempPageNumberString' style='background-image: url(\"$tempFileThumb\");' class='comicScrollViewImg' loading='lazy' src='$tempFileName' />";
		}
		echo "	<hr class='ruler'>";
		echo "<details>\n";
		echo "<summary>\n";
		echo "	<h2><span class='comicScrollPageCount'>📄 <span class='footerText'>Page:</span> $tempPageNumberString/$totalPages</span></h2>\n";
		echo "</summary>\n";
		echo "<div class='listCard'>\n";
		echo "<a class='button comicScrollIndexButton' href='index.php'>📑 Overview</a>\n";
		# switch to other views for this comic page
		echo "<a class='button comicScrollIndexButton' href='$tempPageNumberString.php'>📖 Reader View</a>\n";
		if (array_key_exists("real",$_GET)){
			echo "<a class='button comicScrollIndexButton' href='scroll.php#$tempPageNumberString'>📜 Scroll View</a>\n";
		}else{
			echo "<a class='button comicScrollIndexButton' href='scroll.php?real#$tempPageNumberString'>🖼️ Real Size View</a>\n";
		}
		echo "<a class='button comicScrollBookmarkButton' href='scroll.php#$tempPageNumberString'>🔖 Bookmark This Page</a>\n";
		#echo "<a class='button' href='#$tempPageNumberString' onclick='toggleFullscreen(\"comicScrollReader\",true);'>⛶ Toggle Fullscreen</a>\n";
		echo "<a class='button comicScrollBookmarkButton' href='#readerTitle'>↑ Go To Top</a>\n";
		echo "</details>\n";
		echo "	<hr class='ruler'>";
	}
	if (array_key_exists("chapter",$_GET)){
		echo "<h2>End of Chapter $chapterNumber</h2>\n";
		echo "<div class='listCard'>\n";
		echo "	<a class='button' href='#readerTitle'>↑ Go To Top</a>\n";
		echo "</div>\n";
	}
	if ($totalChapters > 0){
		echo "	<div class='listCard'>\n";
		echo "		<a id='all' class='button' href='?#all'>All</a>\n";
		foreach($discoveredDirs as $fileName){
			echo "		<a class='button' href='?chapter=$fileName'>\n";
			echo "			Chapter $fileName\n";
			echo "		</a>\n";
		}
		echo "	</div>\n";
	}

?>
</div>
	<div class='settingListCard'>
		🔚
	</div>
</div>
<script>
	// locks to prevent autoscroll
	var autoScroll=false;
	var cursorHidden=false;
	function ToggleAutoscroll(){
		notify("⏯️");
		// update the scroll position when play or pause is pressed
		scrollPosition=window.scrollY;
		// flip that bit back and forth
		if(autoScroll==false){
			autoScroll=true;
			window.topButton.style.display='none';
		}else{
			autoScroll=false;
			window.topButton.style.display='block';
		}
		return true;
	}
	var globalLight=false;
	function toggleLightSwitch(){
		// invert the color of the pages

		// search though the document for elements with listCard class
		var elements;
		var newFilter;
		// flip the light back and forth
		if(globalLight){
			newFilter="";
			globalLight=false;
		}else{
			newFilter="invert(1)";
			globalLight=true;
		}
		//
		elements = document.getElementsByClassName("comicScrollViewImgReal");
		for (var element of elements){
			element.style.filter=newFilter;
		}
		//
		elements = document.getElementsByClassName("comicScrollViewImg");
		for (var element of elements){
			element.style.filter=newFilter;
		}
		//
		return true;
	}
	//
	var scrollPosition = 0;
	//  - scroll the page down when the user is not moving the mouse or touching the screen
	//  after a delay of 2 seconds
	function showControls(){
		// enable the cursor
		document.body.style.cursor="default";
		window.clearTimeout(controlHideTimeout);
		console.log("Mouse moved Unhide the mouse/controls");
		cursorHidden=false;
		// hide the cursor and video controls after 2 seconds of inactivity
		controlHideTimeout = setTimeout(() =>{
			console.log("Hide the mouse/controls when inactive");
			cursorHidden=true;
			// hide the cursor
			document.body.style.cursor="none";
		}, 2000);
	};
	function autoScrollComic(scrollSpeed=3){
		window.comicScrollPauseButton.style.display="block";
		// set scroll to true
		autoScroll=true;
		// hide the back to top button
		window.topButton.style.display='none';
		//cursorHidden=true;
		setInterval(function() {
			if (autoScroll){
		//		if (cursorHidden){
					// scroll to the position on the page
					scroll(0,scrollPosition);
					scrollPosition+=scrollSpeed;
					if (scrollPosition > document.body.scrollHeight){
						scrollPosition=0;
						delayedRefresh(5);
					}
		//		}else{
		//			console.log("Cursor is NOT Hidden");
				}
		//	}else{
		//		console.log("Auto Scroll Disabled");
		//	}
		},33);
	}
	// add event for mouse move or screen touch
	window.addEventListener("mousemove", showControls);
	window.addEventListener("touchstart", showControls);
	// hide the cursor after page load
	document.body.onload = function(){
		showControls();
	}
</script>
<?php
	// add random comics above the footer
	drawPosterWidget("comics", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
