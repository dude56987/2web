<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web update log
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
	<link rel='icon' type='image/png' href='/favicon.png'>
	<script src='/2webLib.js'></script>
<?PHP
	if (array_key_exists("refresh",$_GET)){
		if ($_GET['refresh'] == 'true'){
			// using javascript, reload the webpage every 5 seconds
			echo "<script>";
			echo "delayedRefresh(5)";
			echo "</script>";
		}
	}
	?>
</head>
<body>
<?PHP
	include($_SERVER['DOCUMENT_ROOT'].'/header.php');
	include($_SERVER['DOCUMENT_ROOT'].'/settings/settingsHeader.php');
	# add the javascript sorter controls
?>
<div class='titleCard'>
	<h2>Limit Shown Entries</h2>
	<div class='listCard'>
		<form method="get">
			<?PHP
			if (array_key_exists("limit", $_GET)){
				if (is_numeric($_GET["limit"])){
					echo "<input type='number' name='limit' max=5000 min=5 value='".$_GET["limit"]."' placeholder='X'>";
				}else{
					echo "<input type='number' name='limit' max=5000 min=5 value='50'>";
				}
			}else{
				echo "<input type='number' name='limit' max=5000 min=5 value='50'>";
			}
			?>
			<button class='button' type="submit">Change Limit</button>
		</form>
	</div>
	<div class='listCard'>
		<a class='button' href='?limit=500' >😕 Default Log Limit</a>
		<a class='button' href='?limit=all' >∞ Unlimited Log Entries</a>
		<?PHP
			# debug set the limit to max thirty during refresh
			if (array_key_exists("refresh",$_GET)){
				if ($_GET['refresh'] == 'true'){
					echo "<a class='activeButton' href='?refresh=false'>⏹️ Stop Refresh</a>";
				}else{
					echo "<a class='button' href='?refresh=true&limit=30#tableTop'>▶️ Auto Refresh</a>";
				}
			}else{
				echo "<a class='button' href='?refresh=true&limit=30#tableTop'>▶️ Auto Refresh</a>";
			}
		?>
	</div>
	<h2>Select Log Entries by Type</h2>
	<div class='listCard'>
		<a class='button' href='?search=admin'>Admin</a>
		<a class='button' href='?search=info'>Info</a>
		<a class='button' href='?search=error'>Error</a>
		<a class='button' href='?search=warning'>Warning</a>
		<a class='button' href='?search=update'>Update</a>
		<a class='button' href='?search=download'>Download</a>
		<a class='button' href='?search=debug'>Debug</a>
		<a class='button' href='?search=new'>New</a>
	</div>
</div>
<hr>
<!--  add the search box -->
<form class='searchBoxForm' method='get'>
	<input id='searchBox' class='searchBox' type='text' name='search' placeholder='Log Entry Search...' >
	<button id='searchButton' class='searchButton' type='submit'>🔎</button>
</form>
<hr>
<div class='settingsListCard'>
	<div class='settingsTable'>
		<hr id='tableTop'>
		<table>
		<tr>
			<th>Module</th>
			<th>Type</th>
			<th>Description</th>
			<th>Debug<br>
			<?PHP
			if (array_key_exists("refresh",$_GET)){
				if ($_GET['refresh'] == 'true'){
					echo "<img class='localPulse' src='/pulse.gif'>\n";
				}
			}
			?>
			</th>
			<th>Date</th>
			<th>Time</th>
		</tr>
		<?PHP
		# load database
		$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/log/log.db");
		# set the timeout to 1 minute since most webbrowsers timeout loading before this
		$databaseObj->busyTimeout(60000);
		if (array_key_exists("search", $_GET)){
			# set the search limit to all if it is a search
			$_GET["limit"] = "all";
		}
		# get the limit for how many items are displayed from the log
		if (array_key_exists("limit", $_GET)){
			if ($_GET["limit"] == "all"){
				$result = $databaseObj->query('select * from "log" order by logIdentifier DESC;');
			}else{
				if (is_numeric($_GET["limit"])){
					$result = $databaseObj->query('select * from "log" order by logIdentifier DESC limit '.$_GET["limit"].';');
				}else{
					# display error
					echo "<div class='errorBanner'>\n";
					echo "<hr>\n";
					echo "Invalid limit value: '".$_GET["limit"]."'<br>\n";
					echo "<hr>\n";
					echo "</div>\n";
				}
			}
		}else{
			# run query to get the 100 most recent log entries
			$result = $databaseObj->query('select * from "log" order by logIdentifier DESC limit 500;');
		}

		# fetch each row data individually and display results
		while($row = $result->fetchArray()){
			$data  = "<tr class='logEntry ".$row['type']."'>";
			$data .= "<td>";
			$data .= $row['module'];
			$data .= "</td>";
			$data .= "<td>";
			$data .= $row['type'];
			$data .= "</td>";
			$data .= "<td class='logDetails'>";
			$data .= $row['description'];
			$data .= "</td>";
			$data .= "<td class='logDetails'>";
			$data .= $row['details'];
			$data .= "</td>";
			$data .= "<td>";
			$data .= $row['date'];
			$data .= "</td>";
			$data .= "<td>";
			$data .= $row['time'];
			$data .= "</td>";
			$data .= "</tr>";
			# if a search has been set search loaded data for the search string
			if (array_key_exists("search",$_GET)){
				# remove tags and search for search terms in data row
				if (stripos(strip_tags($data), $_GET["search"]) !== false){
					# write matching found data
					echo "$data";
				}
			}else{
				# write all the index entries
				echo "$data";
			}
			flush();
			ob_flush();
		}
		#$foundLogs = scandir($_SERVER['DOCUMENT_ROOT']."/log/");

		#$foundLogs = array_diff($foundLogs,Array(".","..","index.php",".htaccess"));

		#sort($foundLogs);

		# read the array in reverse order
		#while (count($foundLogs) > 0){
		#	echo file_get_contents(array_pop($foundLogs));
#}
#

		# reverse entries so newest logs are on top, oldest on the bottom
		#$foundLogs = array_reverse($foundLogs);

		# read each log file found
		#foreach( $foundLogs as $logFilePath){
		#	echo file_get_contents($logFilePath);
		#}
		?>
		</table>
	</div>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
