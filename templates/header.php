<?PHP
		# build the header
		echo "<div id='header' class='header'>";
		echo "<hr class='menuButton'/>";
		echo "<hr class='menuButton'/>";
		echo "<hr class='menuButton'/>";
		echo "<a class='button' href='/index.php'>";
		echo "&#127968;HOME";
		echo "</a>";
		echo "<a class='button' href='/link.php'>";
		echo "&#128279;LINK";
		echo "</a>";
		$webDirectory=$_SERVER["DOCUMENT_ROOT"];
		if (file_exists("$webDirectory/movies/")){
			echo "<a class='button' href='/movies'>";
			echo "&#127916;MOVIES";
			echo "</a>";
		}
		if (file_exists("$webDirectory/shows/")){
			echo "<a class='button' href='/shows'>";
			echo "&#128250;SHOWS";
			echo "</a>";
		}
		if (file_exists("$webDirectory/music/")){
			echo "<a class='button' href='/music'>";
			echo "&#9834;MUSIC";
			echo "</a>";
		}
		if (file_exists("$webDirectory/comics/")){
			echo "<a class='button' href='/comics'>";
			echo "&#128214;COMICS";
			echo "</a>";
		}
		if (file_exists("$webDirectory/live/channels.m3u")){
			echo "<a class='button' href='/live'>";
			echo "&#128225;LIVE";
			echo "</a>";
		}
		echo "<a class='button' href='/system.php'>";
		echo "&#128421;SETTINGS";
		echo "</a>";
		echo "</div>";
?>
