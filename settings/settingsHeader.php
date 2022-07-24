<div class='titleCard'>
	<h2>Settings</h2>
	<div class='listCard'>
		<a class='button' href='/settings/system.php'>
			🎛️
			<span class='headerText'>
				General
			</span>
		</a>
		<a class='button' href='/settings/tv.php'>
			📡
			<span class='headerText'>
				Live
			</span>
		</a>
		<a class='button' href='/settings/nfo.php'>
			🎞️
			<span class='headerText'>
				Video On Demand
			</span>
		</a>
		<a class='button' href='/settings/comics.php'>
			📚
			<span class='headerText'>
				Comics
			</span>
		</a>
		<a class='button' href='/settings/weather.php'>
			🌤️
			<span class='headerText'>
				Weather
			</span>
		</a>
		<a class='button' href='/settings/music.php'>
			🎧
			<span class='headerText'>
				Music
			</span>
		</a>
		<a class='button' href='/settings/graphs.php'>
			📊
			<span class='headerText'>
				Graphs
			</span>
		</a>
	</div>
</div>

<?PHP
	$pageURL = $_SERVER['REQUEST_URI'];
	if (($pageURL == "/settings/tv.php") || ($pageURL == "/settings/radio.php") || ($pageURL == "/settings/iptv_blocked.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Live Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/tv.php'>📺TV</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/radio.php'>📻Radio</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/iptv_blocked.php'>🚫Blocked</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}else if (($pageURL == "/settings/nfo.php") || ($pageURL == "/settings/ytdl2nfo.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Video On Demand Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/nfo.php'>🎞️Libaries</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/ytdl2nfo.php'>↓Downloads</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}else if (($pageURL == "/settings/comicsDL.php") || ($pageURL == "/settings/comics.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Comics Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/comics.php'>📚Libaries</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/comicsDL.php'>↓Downloads</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}else if (($pageURL == "/settings/system.php") || ($pageURL == "/settings/cache.php") || ($pageURL == "/settings/log.php")){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>General Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/system.php'>🎛️System</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/cache.php'>📥Cache</a>\n";
		echo "			</li>\n";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/log.php'>📋Log</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}else if ($pageURL == "/settings/weather.php"){
		echo "	<div class='inputCard'>\n";
		echo "		<h2>Weather Settings</h2>\n";
		echo "		<ul>";
		echo "			<li>\n";
		echo "				<a class='' href='/settings/weather.php'>🌤️Weather</a>\n";
		echo "			</li>\n";
		echo "		</ul>";
		echo "	</div>\n";
	}
?>
