<?php
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# check group permissions based on what the player is being used for
	requireGroup("php2web");
?>
<!--
########################################################################
# 2web application player for php2web
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
<html id='top' class='randomFanart'>
<head>
<link rel='stylesheet' href='/style.css' />
<script src='/2webLib.js'></script>
<script src='/hls.js'></script>
<link rel='icon' type='image/png' href='/favicon.png'>
<?PHP
	$appName=basename(dirname($_SERVER["SCRIPT_NAME"]));
?>
<script>
	function loadApp(){
		var pickedElement=document.getElementById("applicationWindow");
		// show the spinner in the contents of the current element
		var newAppFrame = document.createElement("iframe");
		newAppFrame.setAttribute("id", "applicationFrame");
		newAppFrame.setAttribute("class", "applicationFrame");
		newAppFrame.setAttribute("onload", "refitApp()");
		<?PHP
		echo "var appName='$appName';";
		?>
		newAppFrame.setAttribute("src", "/applications/"+appName+"/");
		// insert the spinner element inside the element
		pickedElement.appendChild(newAppFrame);
		// after loading the app resize the iframe to fit the interal content without clipping
		var appFrame = document.getElementById("applicationFrame");
		// get the internal height of the frame content
		var internalHeight = appFrame.contentWindow.document.body.scrollHeight;
		// set the frame to be the same height as the loaded interal content
		refitApp();
	}
	function refitApp(){
		// after loading the app resize the iframe to fit the interal content without clipping
		var appFrame = document.getElementById("applicationFrame");
		// get the internal height of the frame content
		var internalHeight = appFrame.contentWindow.document.body.scrollHeight;
		if (internalHeight < 100){
			appFrame.style.height = "40dvh";
		}else{
			//var internalHeight = appFrame.scrollHeight;
			// set the frame to be the same height as the loaded interal content
			// add 10 percent of the height to the bottom because of rounding errors in browsers
			appFrame.style.height = (internalHeight+(internalHeight*0.10))+"px";
		}
	}
	function setSize(width,height){
		var appFrame = document.getElementById("applicationFrame");
		appFrame.style.width = width+"dvw";
		appFrame.style.height = height+"dvh";
	}
	function setBackground(newColor){
		// change the background css value to a new color
		var appFrame = document.getElementById("applicationFrame");
		appFrame.style.background = newColor;
	}
</script>
</head>
<body onload='loadApp()'>
<?PHP
	include("/usr/share/2web/templates/header.php");
	if (file_exists("main.php")){
		include("main.php");

		include("/usr/share/2web/templates/footer.php");
		echo "</body>";
		echo "</html>";
		exit();
	}
?>
<div>
<div class='titleCard'>
	<h1>
	<?PHP
		echo "$appName";
	?>
	</h1>
	<div class='listCard'>
		<a class='button' onclick='toggleFullscreen("applicationFrame")'>📐 Fullscreen</a>
		<a class='button' onclick='setSize(90,90)'>Theatre View</a>
		<a class='button' onclick='setSize(60,50)'>Default View</a>
		<a class='button' onclick='refitApp()'>AutoFit</a>
		<a class='button' onclick='setBackground("white")'>White Background</a>
		<a class='button' onclick='setBackground("black")'>Black Background</a>
	</div>
</div>


<div id='applicationWindow'>
</div>

<?PHP

	$aboutData="";

	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/screenshot.png")){
		$aboutData .= "<div class='listCard'>";
		$screenshotPaths=Array();
		$screenshotPaths=array_merge($screenshotPaths,Array("screenshot.png"));
		$screenshotPaths=array_merge($screenshotPaths,Array("screenshot1.png"));
		$screenshotPaths=array_merge($screenshotPaths,Array("screenshot2.png"));
		$screenshotPaths=array_merge($screenshotPaths,Array("screenshot3.png"));
		$screenshotPaths=array_merge($screenshotPaths,Array("screenshot4.png"));
		$screenshotPaths=array_merge($screenshotPaths,Array("screenshot5.png"));
		foreach($screenshotPaths as $screenshotPath){
			if (file_exists($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/".$screenshotPath)){
				$aboutData .= "<a href='$screenshotPath'>";
				$aboutData .= "	<img class='appScreenshot' src='$screenshotPath'>";
				$aboutData .= "</a>";
			}
		}
		$aboutData .= "</div>";
	}
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/about.cfg")){
		$aboutData .= "<hr id='about'>";
		$aboutData .= file_get_contents($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/about.cfg");
	}
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/help.cfg")){
		$aboutData .= "<h2 id='help'>Help</h2>";
		$aboutData .= file_get_contents($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/help.cfg");
	}
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/license.cfg")){
		$aboutData .= "<h2 id='license'>License</h2>";
		$aboutData .= file_get_contents($_SERVER["DOCUMENT_ROOT"]."/applications/".$appName."/license.cfg");
	}
	if($aboutData != ""){
		echo "<div class='titleCard'>";
		echo "<h2 id='description'>Description</h2>";
		echo $aboutData;
		echo "</div>";
	}
	?>
<div class='titleCard'>
	<h1>External Links</h1>
	<div class='listCard'>
	<?PHP
		# load up the external search providers
		$externalSearchLinks=Array();
		array_push($externalSearchLinks, Array("/search.php?q=","2web"));
		array_push($externalSearchLinks, Array("https://www.imdb.com/find?q=","IMDB"));
		array_push($externalSearchLinks, Array("https://en.wikipedia.org/w/?search=","Wikipedia"));
		array_push($externalSearchLinks, Array("https://archive.org/details/movies?query=","Archive.org"));
		array_push($externalSearchLinks, Array("https://piped.video/results?search_query=","Piped"));
		array_push($externalSearchLinks, Array("https://odysee.com/$/search?q=","Odysee"));
		array_push($externalSearchLinks, Array("https://rumble.com/search/video?q=","Rumble"));
		array_push($externalSearchLinks, Array("https://www.bitchute.com/search/?kind=video&query=","BitChute"));
		array_push($externalSearchLinks, Array("https://www.twitch.tv/search?term=","Twitch"));
		array_push($externalSearchLinks, Array("https://veoh.com/find/","VEOH"));
		array_push($externalSearchLinks, Array("https://www.youtube.com/results?search_query=","Youtube"));
		# draw links for each of the search providers
		foreach($externalSearchLinks as $linkData){
			echo "<a class='button' rel='noreferer' target='_new' href='".$linkData[0].$appName."'>🔎 ".$linkData[1]."</a>\n";
		}
	?>
	</div>
</div>

</div>

<hr>
<?PHP
	include("/usr/share/2web/templates/footer.php");
?>
</body>
</html>

