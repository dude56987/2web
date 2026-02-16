<?php
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# check group permissions based on what the player is being used for
	requireGroup("php2web");
	#######################################################################
	ini_set('display_errors', 1);
?>
<!--
########################################################################
# 2web effect tester webapp
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
<html id='pageRoot' class='randomFanart'>
<head>
	<title>2web Ticket System</title>
	<?PHP
	if(isset($_GET["theme"])){
		if($_GET["theme"]=="none"){
			echo "<link rel='stylesheet' href='/style.css'>";
		}else{
			echo "<style>\n";
			include("/usr/share/2web/themes/".$_GET["theme"]);
			echo "</style>\n";
		}
	}else{
		echo "<link rel='stylesheet' href='/style.css'>";
	}
	?>
	<script src='/2webLib.js'></script>
	<style>
		#pageRoot{
			background-size: cover !important;
			background-repeat: no-repeat !important;
		}
		.sidePaneButton{
			width: 15dvw;
		}
		.sidePaneRight{
			width: 20dvw;
			height: 100dvh;
			background: var(--glassBackground);
			border-right: solid 0.2rem;
			position: fixed;
			top: 0px;
			left: 0px;
			overflow-y: scroll;
		}
		.sidePaneLeft{
			width: 20dvw;
			height: 100dvh;
			background: var(--glassBackground);
			border-left: solid 0.2rem;
			position: fixed;
			top: 0px;
			right: 0px;
			overflow-y: scroll;
		}
	</style>
</head>
<body id='pageRoot' class=''>
	<?PHP
		$foundThemes=scanDir("/usr/share/2web/themes/");
		$foundThemes=array_diff($foundThemes, Array(".",".."));
		$foundThemes=array_diff($foundThemes, Array(".placeholder"));
		$foundThemes=array_merge($foundThemes, Array("none"));

		$foundEffects=scanDir("/usr/share/2web/effects/");
		$foundEffects=array_diff($foundEffects, Array(".",".."));
		$foundEffects=array_merge($foundEffects, Array("none"));

		if (array_key_exists("effect",$_GET)){
			# load the chosen effect name
			$effectName=$_GET["effect"];
			if(file_exists("/usr/share/2web/effects/$effectName.php")){
				include("/usr/share/2web/effects/$effectName.php");
			}else{
				echo "<div class='errorBanner'>EFFECT COULD NOT BE FOUND</div>";
			}
		}else{
			# load the enabled effect
			if(file_exists("/var/cache/2web/web/effect.php")){
				include("/var/cache/2web/web/effect.php");
			}else{
				echo "<div class='errorBanner'>NO EFFECT IS ENABLED</div>";
			}
		}
		if(isset($_GET["theme"])){
			$currentTheme="&theme=".$_GET["theme"];
		}else{
			$currentTheme="";
		}
		if(isset($_GET["effect"])){
			$currentEffect="&effect=".$_GET["effect"];
		}else{
			$currentEffect="";
		}

		echo "<div class='sidePaneLeft'>";
		foreach($foundEffects as $effectName){
			$effectName=str_replace(".php","",$effectName);
			echo "	<div class='listCard'>";
			echo "	<a class='button sidePaneButton' href='?effect=$effectName$currentTheme'>";
			echo "		$effectName";
			echo "	</a>";
			echo "	</div>";
		}
		echo "</div>";
		#
		echo "<div class='sidePaneRight'>";
		foreach($foundThemes as $themeName){
			if (stripos($themeName,"-OpenDyslexic-round") !== false){
				echo "	<div class='listCard'>";
				echo "	<a class='button sidePaneButton' href='?theme=$themeName$currentEffect'>";
				$themeName=str_replace(".css","",$themeName);
				$themeName=str_replace("-OpenDyslexic-round","",$themeName);
				$themeName=str_replace("Simple-","",$themeName);
				echo "		$themeName";
				echo "	</a>";
				echo "	</div>";
			}
		}
		echo "</div>";
	?>
</body>
</html>
