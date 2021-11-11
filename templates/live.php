<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
<script>
	<?php
		include("/usr/share/nfo2web/nfo2web.js");
	?>
</script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
include("../header.php");
# add the search box
?>
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("indexLink")' placeholder='Search...' >

<?php // create top jump button ?>
<a href='#top' id='topButton' class='button'>&uarr;</a>

<?php
# add the updated movies below the header
#include("../randomChannels.index");
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/updatedChannels.php");
?>

<div class='titleCard'>
	<h1>Groups</h1>
<?php
// find all the groups
$sourceFiles=scandir("/var/cache/nfo2web/web/live/groups/");
$sourceFiles=array_diff($sourceFiles,array('..','.'));
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	# read the directory name
	echo "	<a class='button tag' href='/live/groups/$sourceFile/'>";
	echo "		$sourceFile";
	echo "	</a>";
}
?>
	<hr>
	<div class="filterButtonBox">
		<input type="button" class="button liveFilter" value="📺 TV" onclick="filterByClass('indexLink','📺')">
		<input type="button" class="button liveFilter" value="∞ All" onclick="filterByClass('indexLink','')">
		<input type="button" class="button liveFilter" value="📻 Radio" onclick="filterByClass('indexLink','📻')">
	</div>
</div>
<hr>

<div class='settingListCard'>
<?php
// get a list of all the genetrated index links for the page
$sourceFiles = explode("\n",file_get_contents("channels.m3u"));
// reverse the time sort
//$sourceFiles = array_reverse($sourceFiles);
foreach($sourceFiles as $sourceFile){
	if ( ! strpos($sourceFile,"#EXTINF")){
		$sourceFileName = md5($sourceFile);
		if (file_exists("channel_".$sourceFileName.".index")){
			if (is_file("channel_".$sourceFileName.".index")){
				// read the index entry
				$data=file_get_contents("channel_".$sourceFileName.".index");
				// write the index entry
				echo "$data";
			}
		}
	}
}
?>
</div>


<?php
// add random movies above the footer
include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
// add the footer
include("../header.php");
echo "<hr class='topButtonSpace'>"
?>
</body>
</html>
