<!--
########################################################################
# 2web public help document
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
<html class='randomFanart'>
<head>
	<link href="/style.css" rel="stylesheet">
	<script src='/2webLib.js'></script>
	<?PHP
		echo "<title>".ucfirst(gethostname())." - Help</title>\n";
	?>
</head>
<body>
<?PHP
include("header.php");
include("/usr/share/2web/2webLib.php");
?>
<!--
<div class='titleCard linkInfo'>
<h1>Help</h1>
-->
<?PHP
################################################################################
# 2web dynamic help document
################################################################################
# - load a list of documents from the help directory
# - create a link at the top of the document in a index containing that documents title
# - after all documents have been parsed and the titles are added to the index
# - include each document in the current document inside a div titlecard with a id linking it to the genrated index at the top of the document
# - filenames should be in the form of "help document_title.permissions.php"
#  - ex. groupTitle,test_title,nfo2web.php
#  - ex. groupTitle,test_title,comic2web.php
#  - ex. groupTitle,test_title,portal2web.php
# - Files will be rendered in order based on thier group
# - Each group will be placed inside a .titlecard div
# - Each group will also have a index link at the top of the page
# - Files will be ordered based on thier titles and can be explicity ordered by
#   adding numbers to files
#  - ex. 2.1 test_title,nfo2web.php
#  - ex. 2.2 test_title,nfo2web.php
#  - ex. 2.3 test_title,nfo2web.php
#  - ex. 3.1 test_title,nfo2web.php
#  - ex. 1.1 test_title,nfo2web.php
# - Files in the web_help/ directory will be scanned and sorted alphabetically
# - then based on the sorting each group will be individually rendered on the
#   page with indexes for each section of the help document
# - Include a search function for searching the help documents
#  - Use data list of existing help documents
#  - Run fulltext search of each help document
#  - ??? Build llm for each help document rendered to the CLI with php for
#    search purposes
################################################################################
# Improvemnts of this method
################################################################################
# - this should allow adding and removing of help document content
# - this should also make help available based on what modules are enabled
#   and what content is on the server as well as the per user permissions
#   set on the server
# - users without access to comics do not need comic based help documentation
# - Search would make the help document much more accessable
# - Help search could be expanded to include search for settings in admin help
#   sections
################################################################################
# - documents will follow group permisssions if you place the permission group name at the
#   end of the filename after a comma test,permissionGroup.php
# - anything without explicit permissions is world readable
# - You can number files explicitly to order them
################################################################################
# gather all the default help files
# - by default all files from /usr/share/2web/web_help/ should be symlinked into
#   /etc/2web/web_help/
# - web help can be added to by the server admin by adding documents into
#   /etc/2web/web_help/
################################################################################
if (! file_exists("/usr/share/2web/web_help/")){
	mkdir("/usr/share/2web/web_help/");
}
if (! file_exists("/etc/2web/web_help/")){
	mkdir("/etc/2web/web_help/");
}
# gather all the help files added by the user and enabled on the system
$helpFiles = recursiveScan("/etc/2web/web_help/");
$serverHelpFiles = recursiveScan("/usr/share/2web/web_help/");
# merge the help lists
$helpFiles=array_merge($helpFiles,$serverHelpFiles);
# create a sum by joining all the found files into a string
$helpSum=md5(join(";",$helpFiles));
#
# sort the help files by path name
sort($helpFiles);
$helpData="";
$helpIndex="";
$complexIndex=Array();
# build the help file index at the top of the page
foreach($helpFiles as $helpFile){
	$tempHelpGroupName=basename(dirname($helpFile));
	if ($tempHelpGroupName == "web_help"){
		$tempHelpGroupName="";
	}else{
		$tempHelpGroupName=str_replace("_"," ",$tempHelpGroupName);
		$tempHelpGroupName=ucwords($tempHelpGroupName);
		$tempHelpGroupName=ltrim($tempHelpGroupName,"0123456789");
		$tempHelpGroupName=trim($tempHelpGroupName);
		$tempHelpGroupName=$tempHelpGroupName." - ";
	}
	# get the permissions name
	$tempHelpIndexEntry=str_replace(".php","",$helpFile);
	$tempHelpIndexName=basename($tempHelpIndexEntry);
	$tempHelpIndexName=str_replace("_"," ",$tempHelpIndexName);
	$tempHelpIndexName=ucwords($tempHelpIndexName);
	$tempHelpIndexName=ltrim($tempHelpIndexName,"0123456789");
	$tempHelpIndexName=ltrim($tempHelpIndexName);
	# if permissions are set with a comma
	if(stripos($tempHelpIndexName,",") !== false){
		# split the data into an array
		$splitHelpData=explode(",",$tempHelpIndexName);
		# remove the permissions from the title
		$tempHelpIndexName=$splitHelpData[0];
		# get the permissions
		$tempHelpPermissions=$splitHelpData[1];
		# check for permissions
		if(requireGroup("$tempHelpPermissions",false)){
			# read the help file path and use the base name as the index name
			$helpIndex.="<li><a href='#$tempHelpGroupName$tempHelpIndexName'>$tempHelpGroupName$tempHelpIndexName</a></li>";
		}
	}else{
		# no permissions
		# read the help file path and use the base name as the index name
		$helpIndex.="<li><a href='#$tempHelpGroupName$tempHelpIndexName'>$tempHelpGroupName$tempHelpIndexName</a></li>";
	}
}
echo "<div class='titleCard'>\n";
echo "	<h1>Help Index</h1>\n";
echo "	<ul>\n";
# draw the index before the data
echo $helpIndex;
echo "	</ul>\n";
echo "</div>\n";
# read each help file
foreach($helpFiles as $helpFile){
	$tempHelpGroupName=basename(dirname($helpFile));
	if ($tempHelpGroupName == "web_help"){
		$tempHelpGroupName="";
	}else{
		$tempHelpGroupName=str_replace("_"," ",$tempHelpGroupName);
		$tempHelpGroupName=ucwords($tempHelpGroupName);
		$tempHelpGroupName=ltrim($tempHelpGroupName,"0123456789");
		$tempHelpGroupName=trim($tempHelpGroupName);
		$tempHelpGroupName=$tempHelpGroupName." - ";
	}
	$tempHelpIndexEntry=str_replace(".php","",$helpFile);
	$tempHelpIndexName=basename($tempHelpIndexEntry);
	$tempHelpIndexName=str_replace("_"," ",$tempHelpIndexName);
	$tempHelpIndexName=ucwords($tempHelpIndexName);
	$tempHelpIndexName=ltrim($tempHelpIndexName,"0123456789");
	$tempHelpIndexName=ltrim($tempHelpIndexName);
	# if permissions are set with a comma
	if(stripos($tempHelpIndexName,",") !== false){
		$splitHelpData=explode(",",$tempHelpIndexName);
		# remove the permissions from the title
		$tempHelpIndexName=$splitHelpData[0];
		# get the permissions
		$tempHelpPermissions=$splitHelpData[1];
		# check for permissions
		if(requireGroup("$tempHelpPermissions",false)){
			# read the help file path and use the base name as the index name
			if(is_readable($helpFile)){
				# draw the bookmark element
				echo "<hr id='$tempHelpGroupName$tempHelpIndexName' class='hiddenBookmark'>";
				# include the help document
				include($helpFile);
			}else{
				echo "<div class='errorBanner'>Broken help file at '$helpFile'</div>";
			}
		}
	}else{
		# skip permission checking
		if(is_readable($helpFile)){
			# draw the bookmark element
			echo "<hr id='$tempHelpGroupName$tempHelpIndexName' class='hiddenBookmark'>";
			# include the help document
			include($helpFile);
		}else{
			echo "<div class='errorBanner'>Broken help file at '$helpFile'</div>";
		}
	}
}
include("footer.php")
?>
</body>
</html>
