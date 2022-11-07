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
		<?PHP
			$foundLogs = scandir($_SERVER['DOCUMENT_ROOT']."/log/");

			$foundLogs = array_diff($foundLogs,Array(".","..","index.php",".htaccess"));

			sort($foundLogs);

			# read the array in reverse order
			while (count($foundLogs) > 0){
				echo file_get_contents(array_pop($foundLogs));
			}

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
