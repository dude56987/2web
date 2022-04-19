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

<div id='update' class='inputCard'>
	<h2>Update</h2>
	<form action='admin.php' class='buttonForm' method='post'>
		<div class=''>
			<button class='button' type='submit' name='all_update' value='true'>UPDATE ALL</button>
			<button class='button' type='submit' name='all_webgen' value='true'>WEBGEN ALL</button>
		</div>

		<div class=''>
			<button class='button' type='submit' name='nfo_update' value='true'>UPDATE NFO</button>
			<button class='button' type='submit' name='nfo_webgen' value='true'>NFO WEBGEN</button>
		</div>

		<div class=''>
			<button class='button' type='submit' name='iptv_update' value='true'>UPDATE IPTV</button>
			<button class='button' type='submit' name='iptv_webgen' value='true'>IPTV WEBGEN</button>
		</div>

		<div class=''>
			<button class='button' type='submit' name='comic_update' value='true'>UPDATE COMICS</button>
			<button class='button' type='submit' name='comic_webgen' value='true'>COMICS WEBGEN</button>
		</div>
	</form>
</div>

<div id='addNewUser' class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Add New System Administrator</h2>
	<ul>
		<li>New administrators will be added on next scheduled web update.( ~ 24 hours max )</li>
		<li>
			<input width='60%' type='text' name='newUserName' placeholder='NEW USERNAME' required>
		</li>
		<li>
			<input width='60%' type='password' name='newUserPass' placeholder='NEW USER PASSWORD' required>
		</li>
	</ul>
	<button class='button' type='submit'>Add User</button>
</form>
</div>
<!--
<div id='addComicLibary' class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Delete User</h2>
	<ul>
		<li>New usernames will be added on next scheduled web update.( ~ 24 hours max )</li>
	</ul>
	<input width='60%' type='text' name='newUserName' placeholder='NEW USERNAME'>
	<input width='60%' type='text' name='newUserPass' placeholder='NEW USER PASSWORD'>
	<input class='button' type='submit'>
</form>
</div>


<div id='addComicLibary' class='inputCard'>
<form action='admin.php' method='post'>
	<h2>Edit User Password</h2>
	<input width='60%' type='text' name='editUserName' placeholder='USERNAME'>
	<input width='60%' type='text' name='orignalUserPass' placeholder='CURRENT PASSWORD'>
	<input width='60%' type='text' name='editUserPass' placeholder='NEW PASSWORD'>
	<input width='60%' type='text' name='editUserPassVerify' placeholder='NEW PASSWORD'>
	<input class='button' type='submit'>
</form>
</div>
-->


<div id='removeUser' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Remove System Administrator</h2>
			<ul>
				<li>
					Remove existing user from accessing the website
				</li>
			</ul>
			<select name='removeUser'>
			<?PHP
			# build theme list
			$themePath="/etc/2web/theme.cfg";
			//echo "THEME PATH = ".$themePath."<br>";
			if (file_exists($themePath)){
				$activeTheme=file_get_contents($themePath);
				$activeTheme=str_replace("\n","",$activeTheme);
				//echo "ACTIVE THEME = ".$activeTheme."<br>";
				# read in theme files in /usr/share/2web/
				$sourceFiles = explode("\n",shell_exec("ls -1 /etc/2web/users/*.cfg"));
				//echo "Source Files = ".implode(",",$sourceFiles)."<br>\n";
				foreach($sourceFiles as $sourceFile){
					if (strpos($sourceFile,".cfg")){
						//echo "SOURCE FILE = ".$sourceFile."<br>\n";
						$tempTheme=str_replace("/etc/2web/users/","",$sourceFile);
						$themeName=str_replace(".cfg","",$tempTheme);
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
		<button class='button' type='submit'>Remove User</button>
	</form>
</div>

<!-- create the theme picker based on installed themes -->
<div id='webTheme' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Web Theme</h2>
			<ul>
				<li>
					Custom themes can be installed in /usr/share/2web/themes/
				</li>
				<li>
					Theme will change next time website updates.
				</li>
			</ul>
			<select name='theme'>
			<?PHP
			# build theme list
			$themePath="/etc/2web/theme.cfg";
			//echo "THEME PATH = ".$themePath."<br>";
			if (file_exists($themePath)){
				$activeTheme=file_get_contents($themePath);
				$activeTheme=str_replace("\n","",$activeTheme);
				//echo "ACTIVE THEME = ".$activeTheme."<br>";
				# read in theme files in /usr/share/2web/
				$sourceFiles = explode("\n",shell_exec("ls -1 /usr/share/2web/themes/*.css"));
				//echo "Source Files = ".implode(",",$sourceFiles)."<br>\n";
				foreach($sourceFiles as $sourceFile){
					if (strpos($sourceFile,".css")){
						//echo "SOURCE FILE = ".$sourceFile."<br>\n";
						$tempTheme=str_replace("/usr/share/2web/themes/","",$sourceFile);
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


<div id='firewall' class='inputCard'>
<h2>Firewall</h2>
	<ul>
		<li>
			Unlock port 80 for the public interface
		</li>
		<li>
			Unlock port 443 to login to the admin interface
		</li>
		<li>
			Unlock port 444 for compatibility mode
		</li>
	</ul>
</div>

<?PHP
	if (file_exists("/usr/share/2web/version.cfg")){
		echo "<div id='version' class='inputCard'>";
		echo "<h2>2web Version Info</h2>";
		echo "	<div>";
		echo "		Version: #".file_get_contents("/usr/share/2web/version.cfg");
		echo "	</div>";
		if (file_exists("/usr/share/2web/versionDate.cfg")){
			echo "	<div>";
			echo "		Version Publish Date: ".file_get_contents("/usr/share/2web/versionDate.cfg");
			echo "	</div>";
		}
		echo "</div>";
	}
?>
</div>
<?PHP
	include("header.php");
?>
</body>
</html>
