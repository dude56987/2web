<?PHP
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("comic2web");
?>
<!--
########################################################################
# 2web comic overview webpage
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
# get this comic
$thisComic=dirname($_SERVER["SCRIPT_FILENAME"], 1);
$thisComic=basename($thisComic);
# check for chapter data to draw the chapter next and back buttons
if(file_exists("../totalChapters.cfg")){
	$thisChapterComic=dirname($_SERVER["SCRIPT_FILENAME"], 2);
	$thisChapterComicName=basename($thisChapterComic);
	# pull the chapter index data
	$comicIndex=scandir($thisChapterComic);
	# remove navigation directory paths
	$comicIndex=array_diff($comicIndex, Array(".",".."));
	# create the temp index to store verified entries in
	$tempIndex=Array();
	foreach($comicIndex as $comicPath){
		# check each path to be a directory
		if (is_dir($thisChapterComic."/".$comicPath."/")){
			# append the verified path to the temp index
			$tempIndex = array_merge($tempIndex, Array($thisChapterComic."/".$comicPath."/comics.index"));
		}
	}
	# set the value of the comic index to use only verified entries
	$comicIndex=$tempIndex;
}else{
	# get the comic index built by comic2web
	$comicIndex=file("/var/cache/2web/web/comics/comics.index");
}

$prevComic="";
$nextComic="";
$comicFound=false;
# read each of the comics to find the current comic
foreach($comicIndex as $comicName){
	#
	$comicName=dirname($comicName, 1);
	$comicName=basename($comicName);
	#
	if($comicName == $thisComic){
		# found this comic in the index
		# - disable listing the last comic
		$comicFound=true;
	}else{
		if ($comicFound){
			$nextComic=$comicName;
			# break the loop now that all info has been found
			break;
		}else{
			# if no comic has been found mark the current comic as the last comic
			$prevComic=$comicName;
		}
	}
}
# blank entries become this comic links
if($prevComic == ""){
	$previousDir=$thisComic;
}else{
	$previousDir=$prevComic;
}
if($nextComic == ""){
	$nextDir=$thisComic;
}else{
	$nextDir=$nextComic;
}
# get the random comic
$randomDir=basename(dirname($comicIndex[array_rand($comicIndex)],1));
# load chapter data from the file if this is a multi chapter comic
if (file_exists("totalChapters.cfg")){
	$totalChapters=file_get_contents("totalChapters.cfg");
}else if (file_exists("../totalChapters.cfg")){
	$totalChapters=file_get_contents("../totalChapters.cfg");
}

$chapterIndex=false;

$topLevel=false;

if(file_exists("../".$nextDir."/comics.index")){
	# this means this is a single chapter comic
	$topLevel=true;
}
if($topLevel){
	# load buttons for next and previous comics
	$nextDirButton="<a class='right button indexSeries' href='../".$nextDir."/'>";
	$nextDirButton.="<img loading='lazy' src='../".$nextDir."/thumb.png'>";
	$nextDirButton.="<div>";
	$nextDirButton.="Next";
	$nextDirButton.="</div>";
	$nextDirButton.="<div>";
	$nextDirButton.="Comic";
	$nextDirButton.="</div>";
	$nextDirButton.="</a>";

	$previousDirButton="<a class='left button indexSeries' href='../".$previousDir."/'>";
	$previousDirButton.="<img loading='lazy' src='../".$previousDir."/thumb.png'>";
	$previousDirButton.="<div>";
	$previousDirButton.="Previous";
	$previousDirButton.="</div>";
	$previousDirButton.="<div>";
	$previousDirButton.="Comic";
	$previousDirButton.="</div>";
	$previousDirButton.="</a>";

	# get the title of the comic
	$comicTitle=$thisComic;
	# get the title info
	$comicTitleData=getcwd();
	$comicTitleData=explode('/',$comicTitleData);
	# pop the directory name above the current directory
	$comicTitle=array_pop($comicTitleData);
}else{
	# build the chapter next and previous buttons
	$nextDirButton="<a class='right button indexSeries' href='../".$nextDir."/'>";
	$nextDirButton.="<img loading='lazy' src='../".$nextDir."/thumb.png'>";
	$nextDirButton.="<div>";
	$nextDirButton.="Next";
	$nextDirButton.="</div>";
	$nextDirButton.="<div>";
	$nextDirButton.="Chapter";
	$nextDirButton.="</div>";
	$nextDirButton.="</a>";
	# previous button
	$previousDirButton="<a class='left button indexSeries' href='../".$previousDir."/'>";
	$previousDirButton.="<img loading='lazy' src='../".$previousDir."/thumb.png'>";
	$previousDirButton.="<div>";
	$previousDirButton.="Last";
	$previousDirButton.="</div>";
	$previousDirButton.="<div>";
	$previousDirButton.="Chapter";
	$previousDirButton.="</div>";
	$previousDirButton.="</a>";

	# get the title info
	$comicTitleData=getcwd();
	$comicTitleData=explode('/',$comicTitleData);
	# pop the directory name above the current directory
	$chapterTitle=array_pop($comicTitleData);
	$comicTitle=array_pop($comicTitleData);
}

?>

<html>
<head>
<?PHP
if($topLevel){
	echo "<title>".$comicTitle."</title>";
}else{
	# total pages here would repsent the total chapters
	echo "<title>".$comicTitle." - Chapter ".$chapterTitle."/".$totalChapters."</title>";
}
?>
</title>
<style>
html{ background-image: url("thumb.png") }
</style>
<link rel='stylesheet' href='../../style.css'>
<script src='/2webLib.js'></script>
</head>
<body>
<?PHP
	include($_SERVER["DOCUMENT_ROOT"].'/header.php')
?>

<?PHP

$indexPaths = scanDir(".");
sort($indexPaths);
$indexPaths = array_diff($indexPaths,Array(".",".."));
#
$totalPages=0;
$pageGrid="";
foreach($indexPaths as $currentPath){
	# only load jpg images
	if (stripos($currentPath,".jpg") !== false){
		$currentPath=str_replace(".jpg","",$currentPath);
		# build the page links
		$pageGrid .= "<a target='_parent' id='$currentPath' href='$currentPath.php' class='indexSeries' >";
		$pageGrid .= "<img loading='lazy' src='$currentPath-thumb.png' />";
		$pageGrid .= "<div>$currentPath</div>";
		$pageGrid .= "</a>";
		$totalPages+=1;
	}else if(is_dir($currentPath)){
		# this is a list of chapters link to the chapters
		# build the chapter links
		$pageGrid .= "<a id='$currentPath' href='$currentPath/' class='indexSeries' >";
		$pageGrid .= "<img loading='lazy' src='$currentPath/thumb.png' />";
		if (file_exists($currentPath."/chapterTitle.cfg")){
			# load a specific chapter title
			$pageGrid .= "<div>".file_get_contents($currentPath."/chapterTitle.cfg")."</div>";
		}else{
			# load the generic chapter number
			$pageGrid .= "<div>Chapter $currentPath</div>";
		}
		$pageGrid .= "</a>";
		$totalPages+=1;
	}
}
?>
<script>
function setupKeys() {
	document.body.addEventListener('keydown', function(event){
		const key = event.key;
		switch (key){
			case 'ArrowUp':
			event.preventDefault();
			window.location.href='..';
			break;
			case 'ArrowLeft':
			event.preventDefault();
			<?PHP
			echo "window.location.href='../$previousDir';";
			?>
			break;
			case 'ArrowRight':
			event.preventDefault();
			<?PHP
			echo "window.location.href='../$nextDir';";
			?>
			break;
			case 'ArrowDown':
			event.preventDefault();
			<?PHP
			if(is_dir("0001/")){
				echo "window.location.href='0001/';";
			}else{
				echo "window.location.href='0001.php';";
			}
			?>
			break;
			case 'End':
			event.preventDefault();
			<?PHP
				echo "window.location.href='../".$randomDir."';";
			?>
			break;
		}
	});
}
// launch the function
setupKeys();
</script>

<div class='titleCard'>
<?PHP
# draw the buttons
# next button
echo "$nextDirButton";
# and last button
echo "$previousDirButton";
?>
<div>
	<?PHP
	echo "<a class='button comicTitleButton' href='..'>";
	echo "⬆️";
	echo "</a>";
	if($topLevel){
		echo "	<h2>".$comicTitle."</h2>";
	}else{
		echo "	<h2>".$comicTitle." - Chapter ".$chapterTitle."/".prefixNumbers($totalChapters)."</h2>";
		if (file_exists("chapterTitle.cfg")){
			echo "<h2>".file_get_contents("chapterTitle.cfg")."</h2>";
		}
	}
	echo "<hr>";
	if(is_dir("0001/")){
		echo "<a target='_parent' class='button indexSeries' href='0001/'>";
	}else{
		echo "<a target='_parent' class='button indexSeries' href='0001.php'>";
	}
	echo "<img loading='lazy' src='thumb.png' />";
	echo "</a>";
	echo "<hr>";
	?>
	<div class='listCard'>
		<?PHP
		if($topLevel){
			echo "<a class='button' href='/zip-gen.php?comic=$comicTitle'>";
		}else{
			echo "<a class='button' href='/zip-gen.php?comic=$comicTitle&chapter=$chapterTitle'>";
		}
		?>
			<span class='downloadIcon'>↓</span>
			Download ZIP
		</a>
		<?PHP
		if($topLevel){
			echo "<a class='button' href='/zip-gen.php?comic=$comicTitle&cbz'>";
		}else{
			echo "<a class='button' href='/zip-gen.php?comic=$comicTitle&chapter=$chapterTitle&cbz'>";
		}
		?>
			<span class='downloadIcon'>↓</span>
			Download CBZ
		</a>
	</div>
	<div class='listCard'>
		<?PHP
		if($topLevel){
			echo "<a class='button' href='/comics/$comicTitle/scroll.php'>";
		}else{
			echo "<a class='button' href='/comics/$comicTitle/scroll.php?chapter=$chapterTitle'>";
		}
		?>
			📜 Scroll View
		</a>

		<?PHP
		if($topLevel){
			echo "<a class='button' href='/comics/$comicTitle/scroll.php?&real'>";
		}else{
			echo "<a class='button' href='/comics/$comicTitle/scroll.php?chapter=$chapterTitle&real'>";
		}
		?>
			🖼️ Real Size View
		</a>
	</div>
<div class='chapterTitleBox'>
<?PHP
	if(file_exists("totalPages.cfg")){
		echo "Total Pages : ".file_get_contents("totalPages.cfg");
	}
	?>
</div>
</div>
</div>
<div class='settingListCard'>
<?PHP
echo $pageGrid;
?>
</div>
<?PHP
drawPosterWidget("comics", True);
?>
<hr>
<?PHP
	include($_SERVER["DOCUMENT_ROOT"].'/footer.php')
?>
</body>
</html>
