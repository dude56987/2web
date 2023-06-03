<!--
########################################################################
# 2web update log
# Copyright (C) 2023  Carl J Smith
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
	<script src='/2web.js'></script>
<?PHP
	if (array_key_exists("refresh",$_GET)){
		if ($_GET['refresh'] == 'true'){
			// using javascript, reload the webpage every 20 seconds, time is in milliseconds
			echo "<script>";
			echo "setTimeout(function() { window.location=window.location;},(1000*20));";
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
<div class='inputCard'>
	<h2>Filter Log Entries</h2>
	<input type='button' class='button' value='Info' onclick='toggleVisibleClass("INFO")'>
	<input type='button' class='button' value='Error' onclick='toggleVisibleClass("ERROR")'>
	<input type='button' class='button' value='Warning' onclick='toggleVisibleClass("WARNING")'>
	<input type='button' class='button' value='Update' onclick='toggleVisibleClass("UPDATE")'>
	<input type='button' class='button' value='New' onclick='toggleVisibleClass("NEW")'>
	<input type='button' class='button' value='Debug' onclick='toggleVisibleClass("DEBUG")'>
	<input type='button' class='button' value='Download' onclick='toggleVisibleClass("DOWNLOAD")'>
</div>
<div class='inputCard'>
	<h2>Automatic Refresh</h2>
	<hr>
	<?PHP
	if (array_key_exists("refresh",$_GET)){
		if ($_GET['refresh'] == 'true'){
			echo "<a class='activeButton' href='?refresh=false'>Disable</a>";
		}else{
			echo "<a class='button' href='?refresh=true#tableTop'>Enable</a>";
		}
	}else{
			echo "<a class='button' href='?refresh=true#tableTop'>Enable</a>";
	}
	?>
	<hr>
	<!--
	<span id='countdownTimer'>0</span>
	-->
</div>
<hr>
<!--  add the search box -->
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("logEntry")' placeholder='Search...' >
<hr>
<div class='settingsListCard'>
	<div class='settingsTable'>
		<hr id='tableTop'>
		<table>
		<tr>
			<th>Module</th>
			<th>Type</th>
			<th>Description</th>
			<th>Debug</th>
			<th>Date</th>
			<th>Time</th>
		</tr>
		<?PHP
		# load database
		$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/log/log.db");
		# set the timeout to 1 minute since most webbrowsers timeout loading before this
		$databaseObj->busyTimeout(60000);

		# run query to get 800 random
		$result = $databaseObj->query('select * from "log" order by logIdentifier DESC;');

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
			//echo "sourceFile = $sourceFile<br>\n";
			// read the index entry
			// write the index entry
			echo "$data";
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
