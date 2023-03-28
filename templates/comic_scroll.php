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
	<script src='/2web.js'></script>
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
	echo "<a class='button' href='index.php'>📑 Back to Index</a>\n";

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

	$totalPages=count($discoveredFiles);
	echo "Pages: $totalPages";
	echo "	<div class='listCard'>\n";
	echo "		<a id='all' class='button' href='?#all'>All</a>\n";
	foreach($discoveredDirs as $fileName){
		if (is_dir($fileName)){
			echo "		<a id='$fileName' class='button' href='?chapter=$fileName#$fileName'>\n";
			echo "			Chapter $fileName\n";
			echo "		</a>\n";
		}
	}
	echo "	</div>\n";

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

		$tempPageNumber+=1;

		#$tempPageNumber=explode('/',$tempFileName);
		#$tempPageNumber=array_pop($tempPageNumber);
		#$tempPageNumber=str_replace(".jpg","",$tempPageNumber);

		echo "<img id='$tempPageNumber' class='comicScrollViewImg' loading='lazy' src='$tempFileName' />";

		echo "<div class='settingListCard'>";
		#echo "<a class='button' href='index.php'>Back to Index</a>";
		echo "<span>📄 Page: $tempPageNumber/$totalPages</span>";
		echo "<a class='button left' href='index.php#$tempPageNumber'>📑 View In Page Index</a>";
		echo "<a class='button right' href='scroll.php#$tempPageNumber'>🔖 Bookmark Here</a>";
		echo "</div>";
	}


	if (array_key_exists("chapter",$_GET)){
		echo "	<div class='listCard'>\n";
		echo "		<a id='all' class='button' href='?#all'>All</a>\n";
		foreach($discoveredDirs as $fileName){
			if (is_dir($fileName)){
				echo "		<a class='button' href='?chapter=$fileName'>\n";
				echo "			Chapter $fileName\n";
				echo "		</a>\n";
			}
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
