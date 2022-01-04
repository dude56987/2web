<html id='top' class='seriesBackground' style='$tempStyle'>
<head>
<link rel='stylesheet' href='style.css' />
<script src='/nfo2web.js'></script>
</head>
<body>
<a href='#' id='topButton' class='button'>&uarr;</a>
<?PHP
// this file is to be placed in the show directory
// - /shows/$showName/index.php
$activeDir=getcwd();
$showTitle=array_reverse(explode('/',$activeDir))[0];
include('../../header.php');
?>
<div class='titleCard'>
<?PHP
echo "<h1>$showTitle</h1>";
?>
<hr>
<div class='listCard'>
<?PHP
echo "<a class='button' href='/m3u-gen.php?showTitle=\"$showTitle\"'>";
?>
	Play All<sup>(External)<sup>
</a>

<?PHP
################################################################################
# after processing each season rebuild the show page index entirely
// get a list of all the genetrated index links for the page
//$sourceFiles = explode("\n",shell_exec("ls -1 $activeDir/*/season.index"));
$sourceFiles = explode("\n",shell_exec("find '$activeDir/' -name 'season.index' | sort"));
foreach($sourceFiles as $sourceFile){
	if (is_file($sourceFile)){
		$seasonName = str_replace('/season.index','',$sourceFile);
		$seasonName = str_replace($activeDir.'/','',$seasonName);
		//
		echo "	<a href='#$seasonName' class='button'>";
		echo "		$seasonName";
		echo "	</a>";
		flush();
		ob_flush();
	}
}
?>

</div>
</div>
<input id='searchBox' class='searchBox' type='text'
 onkeyup='filter("showPageEpisode")' placeholder='Search...' >
<div class='episodeList'>

<?PHP
foreach($sourceFiles as $sourceFile){
	if (is_file($sourceFile)){
		$seasonName = str_replace('/season.index','',$sourceFile);
		$seasonName = str_replace($activeDir.'/','',$seasonName);
		//
		echo "<div id='$seasonName' class='seasonContainer'>";
		echo "<div class='seasonHeader'>";
		echo "	<h2>";
		echo "		$seasonName";
		echo "	</h2>";
		echo "</div>";
		echo "<hr>";
		// read the index entry
		$data=file_get_contents($sourceFile);
		// write the index entry
		echo "$data";
		echo "</div>";
		flush();
		ob_flush();
	}
}
?>
</div>
<?PHP
	# add footer
	include('../../header.php');
?>
<hr class='topButtonSpace'>
</body>
</html>
