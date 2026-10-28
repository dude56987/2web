<?php
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# check group permissions based on what the player is being used for
	requireGroup("php2web");

	################################################################################
	# Functions
	################################################################################


	################################################################################
	# check for api usage
	################################################################################
	$pasteData="";
	if (array_key_exists("paste",$_POST)){
		$pasteData=cleanText($_POST["paste"]);
		# generate a sum from the paste text
		$pasteSum=md5($pasteData);
		# store the pasted text in a file
		# - store pastes in the web_cache so that cleanup will occur after cache time
		file_put_contents("/var/cache/2web/web/web_cache/pasteData_$pasteSum.md",$pasteData);
		# redirect to the stored paste link
		redirect("index.php?load=$pasteSum");
	}else if (array_key_exists("public_paste",$_POST)){
		$pasteData=cleanText($_POST["public_paste"]);
		# generate a sum from the paste text
		$pasteSum=md5($pasteData);
		# store the pasted text in a file
		# - store pastes in the web_cache so that cleanup will occur after cache time
		file_put_contents("/var/cache/2web/web/web_cache/pasteData_$pasteSum.md",$pasteData);
		# write the public paste link list
		appendFile("public.index","$pasteSum\n");
		# redirect to the stored paste link
		redirect("?load=$pasteSum");
	}else if (array_key_exists("load",$_GET)){
		# load a previously pasted file using the sum
		$pastePath="/var/cache/2web/web/web_cache/pasteData_".$_GET["load"].".md";
		$pasteData=trim(file_get_contents($pastePath));
		$pasteData=unCleanText($pasteData);
	}
?>
<!--
########################################################################
# 2web paste example application
# Copyright (C) 2026  Carl J Smith
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
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>

	</style>
</head>
<body>
<?php
	ini_set('display_errors', 1);
	include('/var/cache/2web/web/header.php');
?>
<div class='titleCard'>
<h1>Server Clipboard</h1>
<?PHP
	if( $pasteData == "" ){
		# no paste data was loaded
	}else{
		# load the pasted data
		echo "<pre>\n";
		echo "$pasteData";
		echo "</pre>\n";
		# draw the link to this paste
		echo "<h3>Link to this paste</h3>\n";
		echo "<pre>\n";
		if($_SERVER["HTTPS"]){
			$proto="https://";
		}else{
			$proto="http://";
		}
		echo $proto.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."?load=".$_GET["load"];
		echo "</pre>\n";
	}
?>
</div>
<div class='titleCard'>
	<h2>🕶️ New Private Paste</h2>
	<form method='post'>
		<p>Only those you share the link with and the server operators can see this paste text.</p>
		<?PHP
		?>
		<textarea name='paste' placeholder='Paste text here...'></textarea>
		<button class='button' type='submit'>Paste</button>
	</form>
</div>

<div class='titleCard'>
	<h2>👓 New Public Paste</h2>
	<form method='post'>
		<p>Anyone who can view this page can see this paste text in recent pastes below.</p>
		<?PHP
		?>
		<textarea name='public_paste' placeholder='Paste text here to share publicly...'></textarea>
		<button class='button' type='submit'>Paste</button>
	</form>
</div>
<?PHP
	if(file_exists("public.index")){
		echo "<div class='titleCard widget'>";
		echo "<h2>Recent Public Pastes</h2>";
		echo "<div class='listCard'>";
		# load the recent pastes
		$publicLinks=file("public.index");
		# reverse the list to make the newest pastes first and the oldest last
		$publicLinks=array_reverse($publicLinks);
		$newPublicLinks="";
		$removalTriggered=false;
		foreach($publicLinks as $publicLink){
			$publicLink=trim($publicLink);
			# for each sum in the public index check the sum exists
			if(file_exists("/var/cache/2web/web/web_cache/pasteData_".$publicLink.".md")){
				$pasteData=file_get_contents("/var/cache/2web/web/web_cache/pasteData_".$publicLink.".md");
				# add the link to the new links list
				$newPublicLinks.=$publicLink;
				# draw a icon linking to the paste sum
				echo "<a class='button' href='?load=$publicLink'>".substr($pasteData,0,80)."</a>";
			}else{
				# removal has been triggered a new file will be created from the working links
				$removalTriggered=true;
			}
		}
		if($removalTriggered){
			# write the new public link list without the broken links
			file_put_contents("public.index",$newPublicLinks);
		}
		echo "</div>";
		echo "</div>";
	}
?>
<?PHP
# draw the footer
include('/var/cache/2web/web/footer.php');
?>
</body>
</html>
