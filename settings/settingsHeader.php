<div class='inputCard'>
	<h2>Settings</h2>
	<!--
	<h1>Settings</h1>
	<div class='titleCard settingsTab'>
	-->
	<div class='right'>
	<?PHP
		$pageURL = $_SERVER['REQUEST_URI'];
		if (($pageURL == "/tv.php") || ($pageURL == "/radio.php") || ($pageURL == "/iptv_blocked.php")){
			echo "	<div class='titleCard'>\n";
			echo "		<h2>Live</h2>\n";
			echo "		<div>\n";
			echo "			<a class='button' href='/tv.php#index'>📺TV</a>\n";
			echo "		</div>\n";
			echo "		<hr>";
			echo "		<div>\n";
			echo "			<a class='button' href='/radio.php#index'>📻Radio</a>\n";
			echo "		</div>\n";
			echo "		<hr>";
			echo "		<div>\n";
			echo "			<a class='button' href='/iptv_blocked.php#index'>🚫Blocked</a>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if (($pageURL == "/nfo.php") || ($pageURL == "/ytdl2nfo.php")){
			echo "	<div class='titleCard'>\n";
			echo "		<h2 style='wrap-text: break-word;'>Video On Demand</h2>\n";
			echo "		<div>\n";
			echo "			<a class='button' href='/nfo.php#index'>🎞️Libaries</a>\n";
			echo "		</div>\n";
			echo "		<hr>";
			echo "		<div>\n";
			echo "			<a class='button' href='/ytdl2nfo.php#index'>↓Downloads</a>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if (($pageURL == "/comicsDL.php") || ($pageURL == "/comics.php")){
			echo "	<div class='titleCard'>\n";
			echo "		<h2>Comics</h2>\n";
			echo "		<div>\n";
			echo "			<a class='button' href='/comics.php#index'>📚Libaries</a>\n";
			echo "		</div>\n";
			echo "		<hr>";
			echo "		<div>\n";
			echo "			<a class='button' href='/comicsDL.php#index'>↓Downloads</a>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if (($pageURL == "/system.php") || ($pageURL == "/cache.php") || ($pageURL == "/log.php")){
			echo "	<div class='titleCard'>\n";
			echo "		<h2>General</h2>\n";
			echo "		<div>\n";
			echo "			<a class='button' href='/system.php#index'>🎛️System</a>\n";
			echo "		</div>\n";
			echo "		<hr>";
			echo "		<div>\n";
			echo "			<a class='button' href='/cache.php#index'>📥Cache</a>\n";
			echo "		</div>\n";
			echo "		<hr>";
			echo "		<div>\n";
			echo "			<a class='button' href='/log.php#index'>📋Log</a>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
	?>
	</div>
	<div class=''>
		<ul>
			<?PHP
				echo "<li>General";
				echo "<ul>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/system.php#index'>🎛️ System</a>\n";
				echo "	</li>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/cache.php#index'>📥 Cache</a>\n";
				echo "	</li>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/log.php#index'>📋 Log</a>\n";
				echo "	</li>\n";
				echo "</ul>\n";
				echo "</li>";

				echo "<li>Live";
				echo "<ul>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/tv.php#index'>📺 TV</a>\n";
				echo "	</li>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/radio.php#index'>📻 Radio</a>\n";
				echo "	</li>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/iptv_blocked.php#index'>🚫 Blocked</a>\n";
				echo "	</li>\n";
				echo "</ul>\n";
				echo "</li>";

				echo "<li>Video On Demand";
				echo "	<ul>\n";
				echo "		<li>\n";
				echo "			<a class='' href='/nfo.php#index'>🎞️ Libaries</a>\n";
				echo "		</li>\n";
				echo "		<li>\n";
				echo "			<a class='' href='/ytdl2nfo.php#index'>↓ Downloads</a>\n";
				echo "		</li>\n";
				echo "	</ul>\n";
				echo "</li>";

				echo "<li>Comics";
				echo "<ul>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/comics.php#index'>📚 Libaries</a>\n";
				echo "	</li>\n";
				echo "	<li>\n";
				echo "		<a class='' href='/comicsDL.php#index'>↓ Downloads</a>\n";
				echo "	</li>\n";
				echo "</ul>\n";
				echo "</li>";
		?>
		</ul>
	</div>
</div>
