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
<html style='background-image: url("thumb.png");background-size: cover;'>
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
</style>
<link rel='stylesheet' href='../../style.css'>
<script src='/2webLib.js'></script>
</head>
<body>
<?PHP
	include($_SERVER["DOCUMENT_ROOT"].'/header.php')
?>

<?PHP
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
	?>
</div>

<table class='controlTable'>
	<tr>
		<td>
			<?PHP
				if(is_dir("0001/")){
					echo "<a target='_parent' class='button indexSeries' href='0001/'>";
				}else{
					echo "<a target='_parent' class='button indexSeries' href='0001.php'>";
				}
				echo "<img loading='lazy' src='thumb.png' />";
				echo "</a>";
			?>
		</td>
	</tr>
	<tr>
		<td>
			<div class='listCard'>
				<?PHP
					if($topLevel){
						echo "<a class='button' href='/zip-gen.php?comic=$comicTitle'>";
					}else{
						echo "<a class='button' href='/zip-gen.php?comic=$comicTitle&chapter=$chapterTitle'>";
					}
				?>
					<span class='downloadIcon'>🡇</span>
					Download ZIP
				</a>
				<?PHP
					if($topLevel){
						echo "<a class='button' href='/zip-gen.php?comic=$comicTitle&cbz'>";
					}else{
						echo "<a class='button' href='/zip-gen.php?comic=$comicTitle&chapter=$chapterTitle&cbz'>";
					}
				?>
					<span class='downloadIcon'>🡇</span>
					Download CBZ
				</a>
			</div>
		</td>
	</tr>
	<tr>
		<td>
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
		</td>
	</tr>
</table>
<div class='chapterTitleBox'>
	<?PHP
	if(file_exists("totalPages.cfg")){
		echo "Total Pages : ".file_get_contents("totalPages.cfg");
	}
	?>
</div>
</div>
</div>
<?PHP
# check for sources
if (requireGroup("admin",false)){
	if(file_exists("sources.cfg")){
		echo "<div class='titleCard'>\n";
		echo "<h2>Media Sources</h2>\n";
		echo "<pre>\n";
		echo file_get_contents("sources.cfg");
		echo "</pre>\n";
		#
		$comicScanPath=basename(dirname($_SERVER["SCRIPT_FILENAME"]));
		echo "<h2>Admin Actions</h2>\n";
		echo "	<div class='listCard'>\n";
		echo "		<form action='/settings/admin.php' method='post'>";
		echo "			<input type='text' name='rescanComic' value='$comicTitle' hidden>";
		echo "			<button class='button' type='submit'>🗘 Force Media Rescan</button>";
		echo "		</form>";
		echo "	</div>\n";
		echo "</div>\n";
	}
}
?>
<div class='settingListCard'>
<?PHP
$indexPaths = scanDir(".");
sort($indexPaths);
$indexPaths = array_diff($indexPaths,Array(".",".."));
foreach($indexPaths as $currentPath){
	# only load jpg images
	if (stripos($currentPath,".jpg") !== false){
		$censorImage=false;
		#$pageMimeType=mime_content_type($_SERVER["DOCUMENT_ROOT"].dirname($_SERVER["PHP_SELF"])."/".$currentPath);
		if(is_readable($currentPath)){
			$pageMimeType=mime_content_type($currentPath);
			if($pageMimeType == "image/gif"){
				$animatedPage="<span class='radioIcon'>⏯️</span>";
			}else if($pageMimeType == "video/webm"){
				$animatedPage="<span class='radioIcon'>⏯️</span>";
			}else if($pageMimeType == "video/mp4"){
				$animatedPage="<span class='radioIcon'>⏯️</span>";
			}else{
				$animatedPage="";
			}
		}else{
			# the file is not accessable based on permissions
			$animatedPage="<span class='radioIcon'>⛔</span>";
			$censorImage=true;
		}
		$currentPath=str_replace(".jpg","",$currentPath);
		# build the page links
		echo "<a target='_parent' id='$currentPath' href='$currentPath.php' class='indexSeries' >\n";
		if($censorImage){
			echo "<img class='censorImage' loading='lazy' src='$currentPath-thumb.png' />\n";
		}else{
			echo "<img loading='lazy' src='$currentPath-thumb.png' />\n";
		}
		echo "<div>$currentPath";
		echo "$animatedPage";
		echo "</div>\n";
		echo "</a>\n";
	}else if(is_dir($currentPath)){
		# this is a list of chapters link to the chapters
		# build the chapter links
		echo "<a id='$currentPath' href='$currentPath/' class='indexSeries' >\n";
		echo "<img loading='lazy' src='$currentPath/thumb.png' />\n";
		if (file_exists($currentPath."/chapterTitle.cfg")){
			# load a specific chapter title
			echo "<div>".file_get_contents($currentPath."/chapterTitle.cfg")."</div>\n";
		}else{
			# load the generic chapter number
			echo "<div>Chapter $currentPath</div>\n";
		}
		echo "</a>\n";
	}
	ob_flush();
	flush();
}
?>
</div>
<?PHP
#
loadSearchIndexResults($comicTitle,"comics");
#
drawPosterWidget("comics", True);
?>
<hr>
<?PHP
	include($_SERVER["DOCUMENT_ROOT"].'/footer.php')
?>
</body>
</html>
