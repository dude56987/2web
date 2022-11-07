<html class='randomFanart'>
<head>
	<link href="/style.css" rel="stylesheet">
	<script src='/2web.js'></script>
</head>
<body>
<?PHP
include($_SERVER['DOCUMENT_ROOT']."/header.php");
###############################################################################
# enable error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);
###############################################################################
###############################################################################

// take port number and service name and generate a index then generate a series of links

$services = availableServicesArray();
?>

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<div class='titleCard linkInfo'>
	<h1>Server Services</h1>

	<div class="titleCard">
		<ul>
			<?PHP
			foreach($services as $serviceData){
				if (checkPort($serviceData[1])){
					echo "<li><a href='#$serviceData[0]'>$serviceData[0] ($serviceData[2])</a></li>";
				}
			}
			foreach($services as $serviceData){
				if (checkServerPath($serviceData[1])){
					echo "<li><a href='#$serviceData[0]'>$serviceData[0] ($serviceData[2])</a></li>";
				}
			}
			?>
		</ul>
	</div>

	<div class="titleCard">
		<p>
			Links to non-intergrated server services.
		</p>
	</div>
</div>

<table class="titleCard">
	<tr>
		<th>
			Service
		</th>
		<th>
			Port
		</th>
		<th>
			Zeroconf Link
		</th>
		<th>
			Hostname Link
		</th>
		<th>
			Localhost Link
		</th>
		<th>
			Ip Link
		</th>
		<th>
			Description
		</th>
	</tr>
<?PHP
checkServices();
?>
</table>
<hr>
<table class="titleCard">
	<tr>
		<th>
			Service
		</th>
		<th>
			Server Path
		</th>
		<th>
			Zeroconf Link
		</th>
		<th>
			Hostname Link
		</th>
		<th>
			Localhost Link
		</th>
		<th>
			Ip Link
		</th>
		<th>
			Description
		</th>
	</tr>
<?PHP
checkPathServices();
?>
</table>
<hr>
<?PHP
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
