<?PHP
# add the base php libary
include("/usr/share/2web/2webLib.php");
# verify the login before loading the page
requireAdmin();
?>
<!--
########################################################################
# 2web view counter stats page
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
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
# add the header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include($_SERVER['DOCUMENT_ROOT']."/settings/settingsHeader.php");
################################################################################
?>
<div id='cleanViewCounts' class='titleCard'>
	<h1>Clean View Counts</h1>
		<?PHP
			# read the size of the view database
			if (file_exists("/var/cache/2web/web/views.db")){
				echo "<span class='singleStat'>\n";
				echo "	<span class='singleStatLabel'>";
				echo "View Database Size";
				echo "</span>\n";
				echo "	<span class='singleStatValue'>";
				echo "	".bytesToHuman(filesize("/var/cache/2web/web/views.db"));
				echo "</span>\n";
				echo "</span>\n";
			}
		?>
		<ul>
			<li>
				Stored on server at '/var/cache/2web/web/views.db'
			</li>
			<li>
				View counter data for all pages.
			</li>
			<li>
				Every page will have the view count reset to zero.
			</li>
			<li>
				Remove all 404 reports and counter data.
			</li>
			<li>
				If you disable all tracking you may want to use this to wipe the existing database.
			</li>
		</ul>
		<form action='/settings/admin.php' class='buttonForm' method='post'>
			<button class='button' type='submit' name='cleanViewCounts' value='yes'>🧹 Clean View Count Data</button>
		</form>
</div>

<div id='enableViewTracking' class='inputCard'>
	<h1>View Tracking</h1>
	<ul>
		<li>This will enable the view counter for pages.</li>
		<li>The view counter will be visible in the bottom left of every page.</li>
		<li>The page load time will also be displayed next to the view count.</li>
	</ul>
	<?php
	buildYesNoCfgButton("/etc/2web/enableViewTracking.cfg","View Tracking","enableViewTracking");
	?>
</div>

<div id='enable404Tracking' class='inputCard'>
	<h1>404 Tracking</h1>
	<ul>
		<li>This will enable the view counter for 404 pages.</li>
		<li>This will enable detailed reports for server admins when a 404 event occurs.</li>
	</ul>
	<?php
	buildYesNoCfgButton("/etc/2web/enable404Tracking.cfg","404 Tracking","enable404Tracking");
	?>
</div>
<?PHP
################################################################################
#
$data = "";
#
$reportData="";
# if either of the tracking options is enabled
if (yesNoCfgCheck("/etc/2web/enableViewTracking.cfg","no") or yesNoCfgCheck("/etc/2web/enable404Tracking.cfg","no")){
	# check if the data needs cached
	$cacheFile="/var/cache/2web/web/web_cache/views.index";
	if (file_exists($cacheFile)){
		# set the time the cached results are kept, in seconds
		if (time()-filemtime($cacheFile) > 10){
			// update the cached file
			$writeFile=true;
		}else{
			// read from the already cached file
			$writeFile=false;
		}
	}else{
		# write the file if it does not exist
		$writeFile=true;
	}
	# check for view tracking
	if (yesNoCfgCheck("/etc/2web/enableViewTracking.cfg","no")){
		createViewsDatabase();
		# open the cache file for writing
		if ($writeFile){
		}
		#
		if (is_file("/var/cache/2web/web/views.db")){
			if ($writeFile){
				ignore_user_abort(true);
				# load database
				$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/views.db");
				# set the timeout to 1 minute since most webbrowsers timeout loading before this
				$databaseObj->busyTimeout(60000);


				# run query view counts
				$result = $databaseObj->query('select * from "view_count" order by views DESC limit 100 ;');

				# build the views database
				$data.="<div class='titleCard'><h1>Url Views</h1><table>"."\n";
				$data.="<tr><th>URL</th><th>VIEWS</th></tr>"."\n";
				// write the index entry
				#echo "$data";

				# fetch each row data individually and display results
				while($row = $result->fetchArray()){
					// read the index entry
					$data.="<tr><td class='viewsPathCell'>".uncleanText($row["url"])."</td><td>".$row["views"]."</td></tr>"."\n";
				}
				$data.= "</table></div>\n";
			}
		}
	}
	if (yesNoCfgCheck("/etc/2web/enable404Tracking.cfg","no")){
		if ($writeFile){
			$data.= "<div class='titleCard'>\n";
			$data.= "<h1>Failed Urls</h1>\n";

			# run query view counts
			#$result = $databaseObj->query('select * from "error_count" order by views DESC limit 100 ;');
			################################################################################
			# build the 404 data
			################################################################################
			# write the index entry
			$indexData=file("/var/cache/2web/generated/404/reports.index", FILE_IGNORE_NEW_LINES);
			$indexData=array_reverse($indexData);
			$countData=Array();
			$reportData.="<div class='titleCard'>"."\n";
			$reportData.="<h1>Reports</h1>"."\n";
			# fetch each row data individually and display results
			foreach($indexData as $indexEntry){
				#
				$entryUrlSum=basename(dirname($indexEntry));
				$entryUrlSum=str_replace("","",$entryUrlSum);;
				#
				$entryUrl=file_get_contents("/var/cache/2web/generated/404/keys/".$entryUrlSum.".cfg");
				#
				#$data.="<tr>"."\n";
				#$data.="<td>"."\n";
				#$data.="	$entryUrl"."\n";
				#$data.="</td>"."\n";
				#$data.="</tr>"."\n";
				#
				$reportData.="<details>"."\n";
				$reportData.="	<summary>"."\n";
				$reportData.="		<h2>"."\n";
				$reportData.="			Report for '".$entryUrl."' From '".timeElapsedToHuman(filemtime($indexEntry))."'\n";
				$reportData.="		</h2>"."\n";
				$reportData.="	</summary>"."\n";
				$reportData.="		".file_get_contents($indexEntry)."\n";
				$reportData.="</details>"."\n";
				if(isset($countData[$entryUrl])){
					$countData[$entryUrl] += 1;
				}else{
					$countData[$entryUrl] = 1;
				}
			}
			$reportData.="</div>"."\n";
			#
			$data.="<table>"."\n";
			$data.="<tr><th>404 URL</th><th>VIEWS</th></tr>"."\n";
			foreach(array_keys($countData) as $countDataKey){
				$data.="<tr>"."\n";
				$data.="	<td>"."\n";
				$data.="		".$countDataKey."\n";
				$data.="	</td>"."\n";
				$data.="	<td>"."\n";
				$data.="		".$countData[$countDataKey]."\n";
				$data.="	</td>"."\n";
				$data.="</tr>"."\n";
			}
			#
			$data.="</table>"."\n";
			$data.="</div>"."\n";
		}
	}
	if ($writeFile){
		$fileHandle = fopen($cacheFile,'w');
		#
		fwrite($fileHandle, $data);
		# draw the reports
		fwrite($fileHandle, $reportData);
		fclose($fileHandle);
		#
		ignore_user_abort(false);
		#
		echo file_get_contents($cacheFile);
	}else{
		# load the cached file
		echo file_get_contents($cacheFile);
	}
}else{
	echo "<div class='titleCard'>"."\n";
	echo "	<h1>"."\n";
	echo "		All Tracking Disabled"."\n";
	echo "	</h1>"."\n";
	// no shows have been loaded yet
	echo "	<ul>"."\n";
	echo "		<li>All Tracking Has Been Disabled.</li>"."\n";
	echo "		<li>No view counts or 404 reports will be generated on this server.</li>"."\n";
	echo "	</ul>"."\n";
	echo "</div>"."\n";
}
?>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
