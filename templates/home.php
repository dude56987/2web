<!--
########################################################################
# 2web home page
# Copyright (C) 2023  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
########################################################################
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("header.php");
	include("/usr/share/2web/2webLib.php");
?>

<?php
	if (file_exists("progress.index")){
		include("progress.index");
	}

	if (file_exists("stats.php")){
		echo "<div class='date titleCard'>";
		echo "<h1>";
		echo ucfirst(shell_exec("hostname"));
		echo "</h1>";

		if ( file_exists("activityGraph.png")){
			echo "<div>";
			echo "<a href='/graphs/2web_activity/'>";
			echo "<img class='homeActivityGraph' src='activityGraph.png' />";
			echo "</a>";
			echo "</div>";
		}

		include("stats.php");

		echo "</div>";
	}
	if (file_exists("shows")){
		drawPosterWidget("episodes");
		drawPosterWidget("shows");
	}
	if (file_exists("movies")){
		drawPosterWidget("movies");
		# random movies
		drawPosterWidget("movies", True);
	}
	if (file_exists("shows")){
		# random
		drawPosterWidget("shows", True);
	}
	if (file_exists("comics")){
		drawPosterWidget("comics");
		drawPosterWidget("comics", True);
	}
	if (file_exists("music")){
		drawPosterWidget("albums",False,True);
		drawPosterWidget("artists",False,True);
		drawPosterWidget("music",True,True);
	}
	if (file_exists("live")){
		drawPosterWidget("channels",False,True);
		drawPosterWidget("channels",True,True);
		if (file_exists("randomChannels.php")){
			include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
		}
	}
	if (file_exists("graphs")){
		drawPosterWidget("graphs");
		drawPosterWidget("graphs", True);
	}
	if (file_exists("repos")){
		drawPosterWidget("repos");
		drawPosterWidget("repos", True);
	}
	drawServicesWidget();
	// add the footer
	include("footer.php");
?>

</body>
</html>
