<html class='randomFanart'>
<head>
	<link href="/style.css" rel="stylesheet">
	<script src='/2web.js'></script>
</head>
<body>
<?PHP
include("header.php")
?>

<?php // create top jump button ?>
<a href='#' id='topButton' class='button'>&uarr;</a>

<input id='searchBox' class='searchBox' type='text' onkeyup='filter("titleCard")' placeholder='Search...' >

<div class='titleCard linkInfo'>
	<h1>Manuals</h1>

	<div class="titleCard">
		<ul>
			<li><a href="#README">README</a></li>
			<li><a href="#2web">2web</a></li>
			<li><a href="#nfo2web">nfo2web</a></li>
			<li><a href="#comic2web">comic2web</a></li>
			<li><a href="#iptv2web">iptv2web</a></li>
			<li><a href="#ytdl2nfo">ytdl2nfo</a></li>
			<li><a href="#weather2web">weather2web</a></li>
		</ul>
	</div>

	<div class="titleCard">
		<p>
			This page contains web versions of all manual pages for each of the 2web commands.
		</p>
	</div>
</div>

<div id='README' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/README.html")
	?>
</div>
<div id='2web' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/2web.html")
	?>
</div>
<div id='nfo2web' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/nfo2web.html")
	?>
</div>
<div id='comic2web' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/comic2web.html")
	?>
</div>
<div id='iptv2web' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/iptv2web.html")
	?>
</div>
<div id='ytdl2nfo' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/ytdl2nfo.html")
	?>
</div>
<div id='weather2web' class='titleCard'>
	<?PHP
	include("/usr/share/2web/help/weather2web.html")
	?>
</div>
<?PHP
include("footer.php")
?>
</body>
</html>
