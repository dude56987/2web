<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web removal tracker
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
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' href='/style.css' />
	<link rel='icon' type='image/png' href='/favicon.png'>
	<script src='/2webLib.js'></script>
</head>
<body>
<?PHP
	include($_SERVER['DOCUMENT_ROOT'].'/header.php');
	include($_SERVER['DOCUMENT_ROOT'].'/settings/settingsHeader.php');
?>
<div class='settingListCard'>
	<h1>Media Data Marked for removal</h1>
	<p>
		Media paths below have been marked for removal on the server. Media is not automatically removed. A server administrator with access to the filesystem must remove the source path manually. You can then force a media rescan to remove it from the web interface. You must then remove the media from this removal list.
	</p>
<?PHP
	$removalScript="#! /bin/bash\n";
	$removalScript.="################################################################################\n";
	$removalScript.="# - You need to review this script before running it on the server to prevent unwanted deletions.\n";
	$removalScript.="# - This script will remove sources of all content marked for removal on this page.\n";
	$removalScript.="# - You must run this script on the server as a administrator in order to remove the sources.\n";
	$removalScript.="# - Each item must also be removed from the removal list.\n";
	$removalScript.="################################################################################\n\n";
	$scriptGenerated=false;
	if(is_dir("/var/cache/2web/generated/removal/")){
		#
		$sourceData=scandir("/var/cache/2web/generated/removal/");
		#echo "<pre>".var_export($sourceData,true)."</pre>\n";
		$sourceData=array_diff($sourceData,Array(".",".."));
		#echo "<pre>".var_export($sourceData,true)."</pre>\n";
		#
		foreach($sourceData as $mediaName){
			#
			$mediaSourcePath="/var/cache/2web/generated/removal/".$mediaName."/removal.cfg";
			$tempSources=file("$mediaSourcePath",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			#
			$tempPath=trim(file_get_contents("/var/cache/2web/generated/removal/".$mediaName."/path.cfg"));
			#
			echo "<a href='/$tempPath/$mediaName/'><h2>$mediaName</h2></a>\n";
			echo "<table>\n";
			foreach($tempSources as $sourcePath){
				$sourcePath=trim($sourcePath);
				echo "	<tr>\n";
				echo "		<td>\n";
				echo "			$sourcePath\n";
				echo "		</td>\n";
				$removalScript.="################################################################################\n";
				$removalScript.="# $mediaName\n";
				$removalScript.="################################################################################\n";
				# check the source path
				if(file_exists($sourcePath)){
					echo "		<td class='disabledSetting'>\n";
					echo "			Source Path Exists\n";
					echo "		</td>\n";
					#
					$removalScript.="# remove source directory of $mediaName\n";
					$removalScript.="rm -rv '$sourcePath/'\n";
					$scriptGenerated=true;
				}else{
					echo "		<td class='enabledSetting'>\n";
					echo "			Source Path Removed\n";
					echo "		</td>\n";
				}
				#
				if(file_exists("/var/cache/2web/web/$tempPath/$mediaName/")){
					echo "		<td class='disabledSetting'>\n";
					echo "			Web Path Exists\n";
					echo "		</td>\n";
					$removalScript.="# remove web directory of $mediaName\n";
					$removalScript.="rm -rv '/var/cache/2web/web/$tempPath/$mediaName/'\n";
					$scriptGenerated=true;
				}else{
					echo "		<td class='enabledSetting'>\n";
					echo "			Web Path Removed\n";
					echo "		</td>\n";
				}
				if(file_exists("/var/cache/2web/kodi/$tempPath/$mediaName/")){
					$removalScript.="# remove KODI directory of $mediaName\n";
					$removalScript.="rm -rv '/var/cache/2web/web/kodi/$tempPath/$mediaName/'\n";
					$scriptGenerated=true;
				}
				if(file_exists("/var/cache/2web/kodi_cache/$tempPath/$mediaName/")){
					$removalScript.="# remove KODI directory of $mediaName\n";
					$removalScript.="rm -rv '/var/cache/2web/kodi_cache/$tempPath/$mediaName/'\n";
					$scriptGenerated=true;
				}
				# remove any search index data
				if(file_exists("/var/cache/2web/generated/searchIndexData/$tempPath/$mediaName/")){
					$removalScript.="# remove search index data of $mediaName\n";
					$removalScript.="rm -rv \"/var/cache/2web/generated/searchIndexData/$tempPath/$mediaName/\"\n";
				}
				$removalScript.="#==============================================================================#\n\n";
				# draw the button to rescan the media in order to remove it from the web path
				echo "		<td>\n";
				# filter based on path in order to create the correct rescan button
				if ($tempPath == "comics"){
					echo "			<form action='/settings/admin.php' method='post'>";
					echo "				<input type='text' name='rescanComic' value='$mediaName' hidden>";
					echo "				<button class='button' type='submit'>🗘 Force Media Rescan</button>";
					echo "			</form>";
					#}else if ($tempPath == "shows"){
				}else{
					echo "No Web Interface Data Can Be Removed for this media item and the module may require a NUKE and RESCAN.\n";
				}
				echo "		</td>\n";
				echo "		<td>\n";
				echo "			<form action='/settings/admin.php' method='post'>";
				echo "				<input type='text' name='markMediaAsRemoved' value='$mediaName' hidden>";
				echo "				<button class='button' type='submit'>Remove From This List</button>";
				echo "			</form>";
				echo "		</td>\n";
				echo "	</tr>\n";
			}
			echo "</table>\n";
		}
	}else{
		echo "<h2>No Media Has Been Marked For Removal</h2>";
	}
	?>
</div>
<?PHP
	#
	if($scriptGenerated){
		echo "<div class='titleCard'>\n";
		echo "	<h2>Removal Script</h2>\n";
		echo "	<pre>$removalScript</pre>\n";
		echo "</div>\n";
	}
	include($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
