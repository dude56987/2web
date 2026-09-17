<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	requireGroup("2web");
?>
<!--
########################################################################
# 2web audio visualizer
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
	<?PHP
		# find the domain name
		echo "<title>".ucfirst(gethostname())." - Audio Visualizer</title>\n";
	?>
	<style>
		.particle{
			z-index: 1 !important;
			display: block !important;
		}
		html{
			background: transparent;
		}
	</style>
</head>
<body>
<?php
	#<body onclick="delayedRefresh(0);" class='randomFanart'>
	#echo "<body onclick='delayedRefresh(0)' $backgroundStyle>"."\n";
	################################################################################
	# - This is a visulizer page to be used with audio content
	# - this page should be embeded as a iframe in another page
	# - This is not currently connected to the beat or audio in any way. This draws
	#   random particle effects from the website background particle effects
	################################################################################
	# load the effect
	if (file_exists("/etc/2web/visualizer.cfg")){
		# load a effect as the visualizer chosen by a config file
		$visualizerName=trim(file_get_contents("/etc/2web/visualizer.cfg"));
	}else{
		# by default do a music notes effect as the visualization
		$visualizerName="notes_sharp_themed";
		# store the default config for next time
		file_put_contents("/etc/2web/visualizer.cfg",$visualizerName);
	}
	echo "<script>";
	echo "console.log('Visualizer loading $visualizerName')";
	echo "</script>";
	# include the chosen visulizer in the page
	include("/usr/share/2web/effects/$visualizerName.php");
	################################################################################
?>
</body>
</html>
