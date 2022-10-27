<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' href='/style.css' />
	<link rel='icon' type='image/png' href='/favicon.png'>
	<script src='/2web.js'></script>
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
<hr>
<!--  add the search box -->
<input id='searchBox' class='searchBox' type='text' onkeyup='filter("logEntry")' placeholder='Search...' >
<hr>
<div class='settingsListCard'>
	<div class='settingsTable'>
		<table>
		<?PHP
			$foundLogs = scandir($_SERVER['DOCUMENT_ROOT']."/log/");

			$foundLogs = array_diff($foundLogs,Array(".","..","index.php",".htaccess"));

			sort($foundLogs);

			# reverse entries so newest logs are on top, oldest on the bottom
			$foundLogs = array_reverse($foundLogs);

			# read each log file found
			foreach( $foundLogs as $logFilePath){
				echo file_get_contents($logFilePath);
			}
		?>
		</table>
	</div>
</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
