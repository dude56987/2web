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
		<li><a href='#graph2webStatus'>Enable or Disable Graphs</a></li>
	</ul>
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

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
