<?php
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# check group permissions based on what the player is being used for
	requireGroup("php2web");

	################################################################################
	# Functions
	################################################################################
	function getListPath(){
		return "/etc/2web/applications/settings/list/todo.csv";
	}
	################################################################################
	function addToList($newListItem){
		$listPath=getListPath();
		$newFileText="";
		if (file_exists($listPath)){
			# add a new item to the list
			$fileData=file($listPath);
			$newItemExists=false;
			# read each of the existing entries
			foreach($fileData as $line){
				#
				$line=str_replace("\n","",$line);
				#
				$lineData=explode(",",$line);
				# check if the item already exists
				if ($lineData[0] == $newListItem){
					$newItemExists=true;
				}
				#
				$newFileText .= $line."\n";
			}
		}else{
			$newItemExists=false;
		}
		# if the new item does not exist in the file
		if(! $newItemExists){
			logPrint("Item not found in file, adding line");
			# add this to the file
			$newFileText .= $newListItem.",1\n";

			#echo "<pre>$newFileText</pre>";
			# write the new file
			file_put_contents($listPath,$newFileText);
			if(! file_exists("todo.csv")){
				symlink($listPath, "todo.csv");
			}
		}
	}
	################################################################################
	function removeFromList($listItem){
		$listPath=getListPath();
		if (file_exists($listPath)){
			# add a new item to the list
			$fileData=file($listPath);
			$itemExists=false;
			$newFileText="";
			# read each of the existing entries
			foreach($fileData as $line){
				$lineData=explode(",",$line);
				# check if the item already exists
				if ($lineData[0] == $listItem){
					$itemExists=true;
				}else{
					$newFileText.=$line;
				}
			}
			if($itemExists){
				# add this to the file
				#$newFileText.=$listItem.",1\n";
				# write the new file
				file_put_contents($listPath,$newFileText);
				if(! file_exists("todo.csv")){
					symlink($listPath, "todo.csv");
				}
			}
		}
	}
	################################################################################
	function addCount($listItem){
		$listPath=getListPath();
		if (file_exists($listPath)){
			# add a new item to the list
			$fileData=file($listPath);
			$itemExists=false;
			$newFileText="";
			# read each of the existing entries
			foreach($fileData as $line){
				$lineData=explode(",",$line);
				# check if the item already exists
				if ($lineData[0] == $listItem){
					#
					$newFileText.=$lineData[0].",".($lineData[1]+1)."\n";
					#
					$itemExists=true;
				}else{
					#
					$newFileText.=$line;
				}
			}
			if($itemExists){
				# write the new file
				file_put_contents($listPath,$newFileText);
				if(! file_exists("todo.csv")){
					symlink($listPath, "todo.csv");
				}
			}
		}
	}
	################################################################################
	function subtractCount($listItem){
		$listPath=getListPath();
		if (file_exists($listPath)){
			# add a new item to the list
			$fileData=file($listPath);
			$itemExists=false;
			$newFileText="";
			# read each of the existing entries
			foreach($fileData as $line){
				$lineData=explode(",",$line);
				# check if the item already exists
				if ($lineData[0] == $listItem){
					# prevent subtracting below zero
					$lineData[1] = $lineData[1] - 1;
					if($lineData[1] == -1){
						#
						$lineData[1] = 1;
					}
					#
					$newFileText .= $lineData[0].",".$lineData[1]."\n";
					#
					$itemExists = true;
				}else{
					#
					$newFileText .= $line;
				}
			}
			if($itemExists){
				# write the new file
				file_put_contents($listPath,$newFileText);
				if(! file_exists("todo.csv")){
					symlink($listPath, "todo.csv");
				}
			}
		}
	}
	################################################################################
	# check for api usage
	################################################################################
	if (array_key_exists("addItem",$_POST)){
		addToList($_POST["addItem"]);
		redirect("index.php?edit");
	}else if (array_key_exists("removeItem",$_POST)){
		removeFromList($_POST["removeItem"]);
		redirect("index.php?edit");
	}else if (array_key_exists("addX",$_POST)){
		addCount($_POST["addX"]);
		redirect("index.php?edit");
	}else if (array_key_exists("subtractX",$_POST)){
		subtractCount($_POST["subtractX"]);
		redirect("index.php?edit");
	}
?>
<!--
########################################################################
# 2web list example application
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
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<style>
		.itemText{
			width: 100%;
			border: solid var(--borderColor);
		}
		.itemCount{
			text-wrap: nowrap;
			border: solid var(--borderColor);
		}
		tr{
			background: var(--glassBackground);
		}
		td{
			border: none;
			font-size: 1rem;
		}
		table{
			border: none;
		}
	</style>
</head>
<body>
<?php
	ini_set('display_errors', 1);
	include('/var/cache/2web/web/header.php');
?>
<div class='titleCard'>
<h1>Todo List</h1>
<?PHP
$listPath=getListPath();
# draw the current list of items
if (file_exists($listPath)){
	$fileData=file($listPath);
}else{
	$fileData=Array();
}
#
echo "<table>";
foreach($fileData as $fileLine){
	# read each of the file lines and draw the data on to the screen
	$lineData=explode(",",$fileLine);

	echo "<tr>";

	echo "<td class='itemCount'>";
	echo "	<span class='xTimes'>X</span> ".$lineData[1]." \n";
	echo "</td>";

	# lineText,repeatCount
	echo "<td class='itemText'>";
	echo "	".$lineData[0]."\n";
	echo "</td>";

	if (array_key_exists("edit",$_GET)){
		echo "<td>";
		echo "	<form class='buttonForm' method='post'>\n";
		echo "		<button class='button' type='submit' name='addX' value='".$lineData[0]."'>➕ Add</button>\n";
		echo "	</form>\n";
		echo "</td>";

		echo "<td>";
		echo "	<form class='buttonForm' method='post'>\n";
		echo "		<button class='button' type='submit' name='subtractX' value='".$lineData[0]."'>➖ Subtract</button>\n";
		echo "	</form>\n";
		echo "</td>";

		echo "<td>";
		echo "	<form class='buttonForm' method='post'>\n";
		echo "		<button class='button' type='submit' name='removeItem' value='".$lineData[0]."'>❌ Remove</button>\n";
		echo "	</form>\n";
		echo "</td>";
	}
	echo "</tr>";
}
echo "</table>";

if (array_key_exists("edit",$_GET)){
	echo "<div class='titleCard'>";
	echo "<form class='buttonForm' method='post'>\n";
	echo "	<input class='newEntryInput' type='text' name='addItem' placeholder='milk'></input>\n";
	echo "	<button class='button' type='submit'>➕ New</button>\n";
	echo "</form>\n";
	echo "</div>";
}

echo "<div class='titleCard'>";
echo "	<div class='listCard'>";
if (array_key_exists("edit",$_GET)){
	echo "		<a class='button' href='?'>Read Only</a>";
}else{
	echo "		<a class='button' href='?edit'>Edit</a>";
}
if (file_exists("todo.csv")){
	echo "		<a class='button' href='todo.csv' target='_new' download>Download Spreadsheet</a>";
}
echo "	</div>";
echo "</div>";
?>
</div>
<?PHP
include('/var/cache/2web/web/footer.php');
?>
</body>
</html>
