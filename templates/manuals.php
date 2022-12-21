<html class='randomFanart'>
<head>
	<link href="/style.css" rel="stylesheet">
	<script src='/2web.js'></script>
</head>
<body>
<?PHP
include("header.php")
?>

<div class='titleCard linkInfo'>
	<h1>Manuals</h1>

	<div class="titleCard">
		<ul>
			<li><a href="#README">README</a></li>
			<?PHP
				$readmeList=Array("2web","nfo2web","comic2web","iptv2web","ytdl2nfo","weather2web","graph2web");
				foreach($readmeList as $readmeTitle){
					echo "<li><a href='#".$readmeTitle."'>".$readmeTitle."</a></li>";
				}
			?>
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
<?PHP
	foreach($readmeList as $readmeTitle){
		echo "<div id='".$readmeTitle."' class='titleCard'>";
		include("/usr/share/2web/help/".$readmeTitle.".html");
		echo "</div>";
	}
?>
</div>
<?PHP
include("footer.php")
?>
</body>
</html>
