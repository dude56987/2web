<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include("header.php");
include("settingsHeader.php");
?>
<div class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#addWeatherLocation'>Add Weather Location</a></li>
	</ul>
</div>

<div id='addWeatherLocation' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Weather Location</h2>
<input width='60%' type='text' name='addWeatherLocation' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

<!-- create the theme picker based on installed themes -->
<div id='setHomepageWeatherLocation' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Homepage Weather Location</h2>
			<ul>
				<li>
					Is the location for which the current weather will be displayed on the homepage.
				</li>
				<li>
					This will show current conditions on the homepage for the chosen location
				</li>
			</ul>
			<select name='setHomepageWeatherLocation'>
			<?PHP
			# build theme list
			$themePath="/etc/2web/weather/homepageLocation.cfg";
			//echo "THEME PATH = ".$themePath."<br>";
			$activeTheme=file_get_contents($themePath);
			$activeTheme=str_replace("\n","",$activeTheme);
			//echo "ACTIVE THEME = ".$activeTheme."<br>";
			# read in theme files in /usr/share/2web/
			$sourceFiles = explode("\n",shell_exec("ls -1 /etc/2web/weather/location.d/*.cfg"));
			//echo "Source Files = ".implode(",",$sourceFiles)."<br>\n";
			foreach($sourceFiles as $sourceFile){
				if (strpos($sourceFile,".cfg")){
					//echo "SOURCE FILE = ".$sourceFile."<br>\n";
					$tempTheme=str_replace("/etc/2web/weather/location.d/","",$sourceFile);
					$themeName=str_replace(".cfg","",$tempTheme);
					$themeName=file_get_contents($sourceFile);
					$tempTheme=$themeName;
					//echo "TEMP THEME = ".$tempTheme."<br>\n";
					echo "TEMP THEME : '".$tempTheme."' == ACTIVE THEME : '".$activeTheme."'<br>\n";
					if ("disabled" == $activeTheme){
						echo "<option value='disabled' selected>Disabled (Default)</option>";
					}else if ($tempTheme == $activeTheme){
						# mark the active theme as selected
						echo "<option value='".$tempTheme."' selected>".$themeName."</option>\n";
					}else{
						# add other theme options found
						echo "<option value='".$tempTheme."' >".$themeName."</option>\n";
					}
				}
			}
			if (file_exists($themePath)){
				echo "<option value='disabled'>Disabled (Default)</option>";
			}else{
				echo "<option value='disabled' selected>Disabled (Default)</option>";
			}
			?>
			<!--
			<option value='red.css' >Red</option>
			<option value='green.css' >Green</option>
			<option value='blue.css' >Blue</option>
			-->
		</select>
		<button class='button' type='submit'>Set Location</button>
	</form>
</div>

<?PHP
echo "<div id='serverDownloadLinkConfig' class='settingListCard'>\n";
echo "<h2>Server Weather Location Config</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/2web/weather/location.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='currentLinks' class='settingListCard'>";
echo "<h2>Current locations</h2>\n";
$sourceFiles = scandir("/etc/2web/weather/location.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/2web/weather/location.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='removeWeatherLocation' value='".$link."'>Remove Link</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}



?>
</div>

<?PHP
	include("header.php");
?>
</body>
</html>
