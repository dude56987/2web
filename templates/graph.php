<!--
########################################################################
# 2web individual graph page
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
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>
	<?PHP
		# get the show name
		$data=getcwd();
		$data=explode('/',$data);
		$graph=array_pop($data);
		echo ":root{";
		echo "--backgroundPoster: url('/graphs/$graph/graph.png');";
		echo "--backgroundFanart: url('/graphs/$graph/graph.png');";
		echo"}";
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
<div class='settingListCard'>
<?php
$title=file_get_contents("title.cfg");
#$title=str_replace("\n","",file_get_contents("title.cfg"));
#$title=str_replace("\r","",file_get_contents("title.cfg"));
#$title=str_replace(" ","_",file_get_contents("title.cfg"));

if (array_key_exists("timespan",$_GET)){
	$timespan=($_GET['timespan']);
	echo "<h1>".ucfirst($title)." - ".ucfirst($timespan)."</h1>";
}else{
	echo "<h1>".ucfirst($title)."</h1>";
}
?>

<?php
$timeScale=Array("hour","day","week","month","year","summary","top");
if (array_key_exists("timespan",$_GET)){
	echo "<a class='graphLink' href='$timespan.png'>";
	echo "<img src='$timespan.png'>";
	echo "</a>";
}else{
	echo "<a class='graphLink' href='day.png'>";
	echo "<img src='day.png'>";
	echo "</a>";
}
?>
<div class='titleCard'>
	<h2></h2>
	<div class='listCard'>
		<h2></h2>
		<?php
			foreach( $timeScale as $timeFrame ){
				# check if timeframe graph exists and build link
				# - all graphs are required to have a -day.png graph for the default graph
				#echo ($_SERVER['DOCUMENT_ROOT']."/graphs/".$title."/".$title."-".$timeFrame.".png\n");
				if(is_file($timeFrame.".png")){
					echo "<a class='button' href='?timespan=$timeFrame'>".ucfirst($timeFrame)."</a>";
				}
			}
		?>
	</div>
</div>
<?PHP
# print the current server time
echo "<div class='titleCard'>Server Time: ";
# munin date format for last update
echo date('l M d H:i:s Y');
echo "</div>";
?>
</div>
<?php
	// add random comics above the footer
	drawPosterWidget("graphs", True);
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
