<!--
########################################################################
# 2web comic viewer scroll reading interface
# Copyright (C) 2023  Carl J Smith
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
		echo "html{";
		if (array_key_exists("chapter",$_GET)){
			echo "	background-image: url('/comics/$comic/$chapterNumber/thumb.png')";
		}else{
			echo "	background-image: url('/comics/$comic/thumb.png')";
		}
		echo "}";
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
<?PHP
	# draw header
	echo "<div class='titleCard'>\n";
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

	echo "<a class='button' href='index.php'>📑 Index View</a>\n";

	if (array_key_exists("real",$_GET)){
		# mark realsize to true
		$realSize=True;
		echo "<a class='button' href='?'>📜 Scroll View</a>\n";
	}else{
		$realSize=False;
		echo "<a class='button' href='?real'>🖼️ Real Size View</a>\n";
	}

	echo "<div>";
	echo "Pages: $totalPages";
	echo "</div>";
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
	echo "<div class='settingListCard'>";
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

		if($realSize){
			echo "<img id='$tempPageNumber' class='comicScrollViewImgReal' loading='lazy' src='$tempFileName' />";
		}else{
			echo "<img id='$tempPageNumber' style='background-image: url(\"$tempFileThumb\")' class='comicScrollViewImg' loading='lazy' src='$tempFileName' />";
		}

		echo "<div class='settingListCard'>";
		echo "<a class='button comicScrollIndexButton' href='index.php#$tempPageNumber'>📑 Index View</a>";
		echo "<span class='comicScrollPageCount'>📄 <span class='footerText'>Page:</span> $tempPageNumber/$totalPages</span>";
		echo "<a class='button comicScrollBookmarkButton' href='scroll.php#$tempPageNumber'>🔖 Bookmark Here</a>";
		echo "</div>";
	}
	if (array_key_exists("chapter",$_GET)){
		echo "<h2>End of Chapter $chapterNumber</h2>";
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
<?php
	// add random comics above the footer
	drawPosterWidget("comics", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
