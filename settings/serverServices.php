<html class='randomFanart'>
<head>
	<link href="/style.css" rel="stylesheet">
	<script src='/2web.js'></script>
</head>
<body>
<?PHP
include("header.php");
###############################################################################
# enable error reporting
ini_set("display_errors", 1);
error_reporting(E_ALL);
###############################################################################
function checkPort($port){
	$connection = @fsockopen("localhost", $port, $errorNum, $errorStr, 30);
	if (is_resource($connection)){
		fclose($connection);
		return true;
	}else{
		# no connection could be made
		return false;
	}
}
###############################################################################

// take port number and service name and generate a index then generate a series of links

$services = Array();
array_push($services,Array('2WEB', 80, 'This Server'));
array_push($services,Array('CUPS', 631, 'Print Server'));
array_push($services,Array('TRANSMISSION', 9091, 'Bittorrent Client'));
array_push($services,Array('DELUGE', 8112, 'Bittorrent Client'));
array_push($services,Array('QBITTORRENT', 1342, 'Bittorrent Client'));
array_push($services,Array('MEDUSA', 1340, 'Metadata Tool'));
array_push($services,Array('SONARR', 8989, 'Metadata Tool'));
array_push($services,Array('RADARR', 7878, 'Metadata Tool'));
array_push($services,Array('BAZARR', 6767, 'Subtitle Downloader'));
array_push($services,Array('LIDARR', 8686, 'Metadata Tool'));
array_push($services,Array('JACKETT', 9117, 'Metadata Tool'));
array_push($services,Array('CUBERITE', 1339, 'Minecraft Server'));
array_push($services,Array('MINEOS', 8443, 'Minecraft Server'));
array_push($services,Array('NETDATA', 19999, 'Realtime Stats'));
array_push($services,Array('WEBMIN', 10000, 'Web Administration'));
array_push($services,Array('DIETPI-DASHBOARD', 5252, 'Realtime Stats'));
array_push($services,Array('AdGuard Home', 8083, 'DNS AdBlock'));
array_push($services,Array('RPI-Monitor', 8888, 'Realtime Stats'));
array_push($services,Array('GOGS', 3000, 'Self Hosted Git Service'));
array_push($services,Array('PaperMC', 25565, 'Minecraft Server'));
array_push($services,Array('NZBGet', 6789, 'Metadata Tool'));
array_push($services,Array('HTPC Manager', 8085, ''));
array_push($services,Array('I2P', 7657, 'P2P Internet'));
array_push($services,Array('YACY', 8090, 'P2P Search Engine'));
array_push($services,Array('FOLDING@HOME', 7396, 'P2P Protein Folding'));
array_push($services,Array('IPFS', 5003, 'P2P File Transfer'));
array_push($services,Array('Ur Backup', 55414, 'Backup Server'));
array_push($services,Array('GITEA', 3000, 'Git Server'));
array_push($services,Array('SYNCTHING', 3000, 'File Sync Server'));
array_push($services,Array('Vault Warden', 8001, 'Unoffical Bitwarden Pass Manager'));
//array_push($services,Array('Unbound', 53, 'DNS Server'));
?>

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<input id='searchBox' class='searchBox' type='text' onkeyup='filter("titleCard")' placeholder='Search...' >

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
			Description
		</th>
	</tr>
<?PHP
foreach($services as $serviceData){
	if (checkPort($serviceData[1])){
		echo "	<tr class='titleCard'>";
		echo "		<td>$serviceData[0]</td>";
		echo "		<td>$serviceData[1]</td>";
		echo "		<td>";
		echo "			<a id='$serviceData[0]' href='http://".gethostname().".local:$serviceData[1]'>http://".gethostname().".local:$serviceData[1]</a>";
		echo "		</td>";
		echo "		<td>";
		echo "			<a id='$serviceData[0]' href='http://".gethostname().":$serviceData[1]'>http://".gethostname().":$serviceData[1]</a>";
		echo "		</td>";
		echo "		<td>";
		echo "			<a id='$serviceData[0]' href='http://localhost:$serviceData[1]'>http://localhost:$serviceData[1]</a>";
		echo "		</td>";
		echo "		<td>$serviceData[2]</td>";
		echo "	</tr>";
	}
}
?>
</table>

<?PHP
include("footer.php")
?>
</body>
</html>
