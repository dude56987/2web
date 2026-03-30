<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web queue settings and controls
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
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>

<?php
# if the queue is not running in on the server show a warning
if ( ! file_exists("/var/cache/2web/web/queue2web.active") ){
	#
	echo "<details class='warningBanner'>\n";
	echo "<summary>🖐︎ The Queue is currently unavailable! 🖐︎</summary>\n";
	echo "If this message is shown for  more than 15 minutes a error has occured and the queue can not be restarted. A manual unlock may be required by running <pre>2web unlock</pre> as an administrator on the server.\n";
	echo "</details>\n";
}

echo "<div id='portalscanSourcesPaths' class='titleCard'>\n";
echo "<h2>Queue Data</h2>\n";
#
$activeQueueFiles = recursiveScan("/var/cache/2web/queue/active/");
#
$multiQueueFiles = recursiveScan("/var/cache/2web/queue/multi/");
$singleQueueFiles = recursiveScan("/var/cache/2web/queue/single/");
$idleQueueFiles = recursiveScan("/var/cache/2web/queue/idle/");
$failedQueueFiles = recursiveScan("/var/cache/2web/queue/failed/");
# count it up

$activeQueueCount=count($activeQueueFiles);
$multiQueueCount=count($multiQueueFiles);
$singleQueueCount=count($singleQueueFiles);
$idleQueueCount=count($idleQueueFiles);
$failedQueueCount=count($failedQueueFiles);
$totalQueueCount=($multiQueueCount+$singleQueueCount+$idleQueueCount);
#
#
echo "<table>\n";
echo "	<tr>\n";
echo "		<th>Active Queue Size</th>\n";
echo "		<th>Multi Queue Size</th>\n";
echo "		<th>Single Queue Size</th>\n";
echo "		<th>Idle Queue Size</th>\n";
echo "		<th>Failed Queue Size</th>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td>".$activeQueueCount."/".trim(file_get_contents("/etc/2web/multiQueueSize.cfg"))."</td>\n";
echo "		<td>".$multiQueueCount."</td>\n";
echo "		<td>".$singleQueueCount."</td>\n";
echo "		<td>".$idleQueueCount."</td>\n";
echo "		<td>".$failedQueueCount."</td>\n";
echo "	</tr>\n";
echo "</table>\n";
echo "</div>\n";
if($totalQueueCount > 0){
	echo "<div id='' class='titleCard'>\n";
	echo "<table>\n";
	echo "	<tr>\n";
	echo "		<th>Queue Type</th>\n";
	echo "		<th>State</th>\n";
	echo "		<th>Command</th>\n";
	#echo "		<th>Log</th>\n";
	echo "	</tr>\n";
	# draw each of the jobs in the queue
	foreach($multiQueueFiles as $multiQueueFilePath){
		echo "	<tr>\n";
		echo "		<td>Multi</td>\n";
		# get the log data
		$tempStatePath="/var/cache/2web/queue/active/".basename($multiQueueFilePath);
		echo "<div class='warningBanner'>tempStatePath=$tempStatePath</div>\n";
		if(is_readable($tempStatePath)){
			echo "		<td class='enabledSetting'>Running</td>\n";
		}else{
			echo "		<td class='disabledSetting'>Waiting...</td>\n";
		}
		#
		echo "		<td class='whiteBoard'>".trim(file_get_contents($multiQueueFilePath))."</td>\n";
		# get the log data
		#$tempLogPath="/var/cache/2web/queue/log/".basename($multiQueueFilePath);
		#if (is_readable($tempLogPath)){
		#	echo "		<td class='whiteBoard'>".trim(file_get_contents($tempLogPath))."</td>\n";
		#}else{
		#	echo "		<td class='whiteBoard'>No Log Data Yet</td>\n";
		#}
		echo "	</tr>\n";
	}
	foreach($singleQueueFiles as $multiQueueFilePath){
		echo "	<tr>\n";
		echo "		<td>Single</td>\n";
		# get the log data
		$tempStatePath="/var/cache/2web/queue/active/".str_replace(".cmd",".active",basename($multiQueueFilePath));
		if(is_readable($tempStatePath)){
			echo "		<td class='enabledSetting'>Running</td>\n";
		}else{
			echo "		<td class='disabledSetting'>Waiting...</td>\n";
		}
		#
		echo "		<td class='whiteBoard'>".trim(file_get_contents($multiQueueFilePath))."</td>\n";
		# get the log data
		#$tempLogPath="/var/cache/2web/queue/log/".basename($multiQueueFilePath);
		#if (is_readable($tempLogPath)){
		#	echo "		<td class='whiteBoard'>".trim(file_get_contents($tempLogPath))."</td>\n";
		#}else{
		#	echo "		<td class='whiteBoard'>No Log Data Yet</td>\n";
		#}
		echo "	</tr>\n";
	}
	foreach($idleQueueFiles as $multiQueueFilePath){
		echo "	<tr>\n";
		echo "		<td>Idle</td>\n";
		echo "		<td class='whiteBoard'>".trim(file_get_contents($multiQueueFilePath))."</td>\n";
		# get the log data
		#$tempLogPath="/var/cache/2web/queue/active/".str_replace(".cmd",".active",basename($multiQueueFilePath));
		#if (is_readable($tempLogPath)){
		#	echo "		<td class='whiteBoard'>".trim(file_get_contents($tempLogPath))."</td>\n";
		#}else{
		#	echo "		<td class='whiteBoard'>No Log Data Yet</td>\n";
		#}
		echo "	</tr>\n";
	}
	echo "</table>\n";
	echo "</div>\n";
}
echo "<div id='' class='titleCard'>\n";
echo "<h1>Failed Job Log</h1>\n";
foreach($failedQueueFiles as $failedQueueFilePath){
	echo "	<h2>".basename($failedQueueFilePath)."</h2>\n";
	# get the log data if it exists for the failed job
	if(is_readable("/var/cache/2web/queue/log/".str_replace(".cmd",".log",basename($failedQueueFilePath)))){
		echo "	<pre>".file_get_contents(("/var/cache/2web/queue/log/".str_replace(".cmd",".log",basename($failedQueueFilePath))))."</pre>\n";
	}else{
		echo "	<pre>".$failedQueueFilePath."</pre>\n";
	}
}
echo "</div>\n";

echo "</div>\n";
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
