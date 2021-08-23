<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
################################################################################
include("header.php");
include("settingsHeader.php");
?>

<div class='inputCard'>
	<h2>Update</h2>
	<form action='admin.php' class='buttonForm' method='post'>
		<button class='button' type='submit' name='update' value='true'>UPDATE</button>
	</form>
</div>
<!-- create the theme picker based on installed themes -->
<div class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Web Theme</h2>
			<p>
				Theme will change next time website updates.
			</p>
			<select name='theme'>
			<?PHP
			# build theme list
			$themePath="/etc/mms/theme.cfg";
			//echo "THEME PATH = ".$themePath."<br>";
			if (file_exists($themePath)){
				$activeTheme=file_get_contents($themePath);
				$activeTheme=str_replace("\n","",$activeTheme);
				//echo "ACTIVE THEME = ".$activeTheme."<br>";
				# read in theme files in /usr/share/mms/
				$sourceFiles = explode("\n",shell_exec("ls -1 /usr/share/mms/themes/*.css"));
				//echo "Source Files = ".implode(",",$sourceFiles)."<br>\n";
				foreach($sourceFiles as $sourceFile){
					if (strpos($sourceFile,".css")){
						//echo "SOURCE FILE = ".$sourceFile."<br>\n";
						$tempTheme=str_replace("/usr/share/mms/themes/","",$sourceFile);
						$themeName=str_replace(".css","",$tempTheme);
						//echo "TEMP THEME = ".$tempTheme."<br>\n";
						echo "TEMP THEME : '".$tempTheme."' == ACTIVE THEME : '".$activeTheme."'<br>\n";
						if ($tempTheme == $activeTheme){
							# mark the active theme as selected
							echo "<option value='".$tempTheme."' selected>".$themeName."</option>\n";
						}else{
							# add other theme options found
							echo "<option value='".$tempTheme."' >".$themeName."</option>\n";
						}
					}
				}
			}
			?>
			<!--
			<option value='default.css' selected>Default</option>
			<option value='red.css' >Red</option>
			<option value='green.css' >Green</option>
			<option value='blue.css' >Blue</option>
			-->
		</select>
		<button class='button' type='submit'>Change Theme</button>
	</form>
</div>

</body>
</html>
