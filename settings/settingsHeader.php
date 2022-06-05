<div class='titleCard'>
	<h2>Settings</h2>
	<div class='listCard'>
		<a class='button' href='/system.php'>
			🎛️
			<span class='headerText'>
				General
			</span>
		</a>
		<a class='button' href='/tv.php'>
			📡
			<span class='headerText'>
				Live
			</span>
		</a>
		<a class='button' href='/nfo.php'>
			🎞️
			<span class='headerText'>
				Video On Demand
			</span>
		</a>
		<a class='button' href='/comics.php'>
			📚
			<span class='headerText'>
				Comics
			</span>
		</a>
		<a class='button' href='/weather.php'>
			🌤️
			<span class='headerText'>
				Weather
			</span>
		</a>
	</div>
</div>

<?PHP
	$pageURL = $_SERVER['REQUEST_URI'];
	if (($pageURL == "/tv.php") || ($pageURL == "/radio.php") || ($pageURL == "/iptv_blocked.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Live Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/tv.php'>📺TV</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/radio.php'>📻Radio</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/iptv_blocked.php'>🚫Blocked</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}
	if (($pageURL == "/nfo.php") || ($pageURL == "/ytdl2nfo.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Video On Demand Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/nfo.php'>🎞️Libaries</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/ytdl2nfo.php'>↓Downloads</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}
	if (($pageURL == "/comicsDL.php") || ($pageURL == "/comics.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Comics Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/comics.php'>📚Libaries</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/comicsDL.php'>↓Downloads</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}
	if (($pageURL == "/system.php") || ($pageURL == "/cache.php") || ($pageURL == "/log.php") || ($pageURL == "/weather.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>General Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/system.php'>🎛️System</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/cache.php'>📥Cache</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/weather.php'>🌤️Weather</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/log.php'>📋Log</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}
?>
