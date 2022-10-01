<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>


<hr>

<!--
<div class='settingListCard'>
-->
<div class='titleCard'>
<h1>Stations</h1>
<div class='listCard'>
<?php
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/2web/web/weather/data/station_*.index | sort"));
	// reverse the time sort
	$sourceFiles = array_reverse($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "$data";
					flush();
					ob_flush();
				}
			}
		}
	}
?>
</div>
</div>

<?php
	// get a list of all the genetrated index links for the page
	$sourceFiles = explode("\n",shell_exec("ls -1 /var/cache/2web/web/weather/data/forcast_*.index | sort"));
	// reverse the time sort
	$sourceFiles = array_reverse($sourceFiles);
	foreach($sourceFiles as $sourceFile){
		$sourceFileName = $sourceFile;
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				if (strpos($sourceFile,".index")){
					// read the index entry
					$data=file_get_contents($sourceFile);
					// write the index entry
					echo "<div class='settingsList'>";
					echo "$data";
					// write the current condititons at the bottom of the extended forecast
					echo "<div class='titleCard'>";
					echo file_get_contents(str_replace("forcast_","current_",$sourceFile));
					echo "</div>";
					echo "</div>";
					flush();
					ob_flush();
				}
			}
		}
	}
?>

<!--
</div>
-->

<?php
	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>

</body>
</html>
