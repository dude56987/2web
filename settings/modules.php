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
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include($_SERVER['DOCUMENT_ROOT']."/settings/settingsHeader.php");
include("/usr/share/2web/2webLib.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li>Enable/Disable Modules
			<ul>
				<li><a href='#live2webStatus'>Live</a></li>
				<li><a href='#nfo2webStatus'>Video On Demand</a></li>
				<li><a href='#comic2webStatus'>Comics</a></li>
				<li><a href='#weather2webStatus'>Weather</a></li>
				<li><a href='#music2webStatus'>Music</a></li>
				<li><a href='#graph2webStatus'>Graphs</a></li>
				<li><a href='#kodi2webStatus'>KODI</a></li>
			</ul>
		</li>
	</ul>
</div>

<div id='iptv2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Live Module Status</h2>
			<ul>
				<li>
					Enable or disable the live iptv channels on the website.
				</li>
			</ul>
			<select name='iptv2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/iptv2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>

<div id='nfo2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Video On Demand Module Status</h2>
			<ul>
				<li>
					Enable or disable nfo libaries on the website.
				</li>
			</ul>
			<select name='nfo2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/nfo2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>

<div id='comic2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Comics Module Status</h2>
			<ul>
				<li>
					Enable or disable comics section of the website.
				</li>
			</ul>
			<select name='comic2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/comic2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>
<div id='weather2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Weather Module Status</h2>
			<ul>
				<li>
					Enable or disable weather on the website.
				</li>
			</ul>
			<select name='weather2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/weather2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>

<div id='music2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Music Module Status</h2>
			<ul>
				<li>
					Enable or disable music on the website.
				</li>
			</ul>
			<select name='music2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/music2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>

<div id='graph2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Graphs Enabled</h2>
			<ul>
				<li>
					Enable or disable the graphs on the website.
				</li>
			</ul>
			<select name='graph2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/graph2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>

<div id='kodi2webStatus' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>KODI sync Status</h2>
			<ul>
				<li>
					Enable or disable sync of linked kodi instances.
				</li>
			</ul>
			<select name='kodi2webStatus'>
			<?PHP
				// check the status of the graph module
				if (detectEnabledStatus("/etc/2web/mod_status/kodi2web.cfg")){
					echo "<option value='enabled' selected>Enabled</option>";
					echo "<option value='disabled' >Disabled</option>";
				}else{
					echo "<option value='disabled' selected>Disabled</option>";
					echo "<option value='enabled' >Enabled</option>";
				}
				?>
			</select>
			<button class='button' type='submit'>Set Status</button>
	</form>
</div>



<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
