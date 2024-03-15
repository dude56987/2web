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
# get the directory name
$data=getcwd();
$data=explode('/',$data);
$currentDir=array_pop($data);

# look up one directory and search the directories there
# for each directory search for a comics.index or a chapterTitle.cfg in those subdirectories
$siblingDirs = scanDir("..");
sort($siblingDirs);
$siblingDirs = array_diff($siblingDirs,Array(".",".."));
#$tempDirs=$siblingDirs;
$tempDirs=Array();
foreach($siblingDirs as $dirPath){
	# if the directory path exists
	if(is_dir("../".$dirPath)){
		# add the directory to the array
		$tempDirs=array_merge($tempDirs,Array($dirPath));
	}
}
# sort the directories
sort($tempDirs);
#
$totalChapters=count($tempDirs);

$chapterIndex=false;
$previousDir="";

$firstDir="";
$lastCheckedDir="";
# determine the next and previous links
foreach($tempDirs as $dirPath){
	if ($firstDir == ""){
		# store the first directory
		$firstDir=$dirPath;
	}
	# set the next directory every run for first and last entries to pickup
	$nextDir=$dirPath;
	#
	if ($dirPath == $currentDir){
		# this is the current comic
		if ($lastCheckedDir == ""){
			$previousDir=$currentDir;
		}else{
			$previousDir=$lastCheckedDir;
		}
	}else if($previousDir != ""){
		# if the previousDirectory has been set and this is not the active directory
		# its the next directory
		# break the loop since both buttons have been set
		break;
	}
	# store the last checked directory
	$lastCheckedDir=$dirPath;
}
#
$tempLastDir=Array();
foreach($tempDirs as $dirPath){
	if(is_dir($dirPath)){
		$tempLastDir=array_merge($tempLastDir, Array($dirPath));
	}
}
# get the last dir
$lastDir=array_pop($tempLastDir);
#
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
	$comicTitle=$currentDir;
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
	#echo "<hr>";
	#var_dump($comicTitleData);
	#echo "<hr>";
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

#if($firstDir == $previousDir){
#	# do not draw a link
#	$previousDirButton="";
#}
#if($nextDir == $lastDir){
#	# do not draw the link
#	$nextDirButton="";
#}
#
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
		$pageGrid .= "<a id='$currentPath' href='$currentPath.php' class='indexSeries' >";
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
	echo "<a class='button indexSeries' href='0001.php'>";
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
