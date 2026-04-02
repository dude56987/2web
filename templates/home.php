<!--
########################################################################
# 2web home page
# Copyright (C) 2026  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
########################################################################
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
		echo "<title>".ucfirst(gethostname())."</title>\n";
	?>
</head>
<body>

<?php
	ini_set('display_errors', 1);
	include("/usr/share/2web/2webLib.php");
	include("/var/cache/2web/web/header.php");
?>

<?php
	if (file_exists("progress.index")){
		include("progress.index");
	}

	echo "<div class='titleCard'>\n";
	echo "<h1>";
	echo ucfirst(gethostname());
	echo "</h1>\n";
	# include stats for admins
	if(requireGroup("graph2web",false)){
		if ( file_exists("activityGraph.png") ){
			echo "<div>\n";
			echo "	<a href='/graphs/2web_activity/'>\n";
			echo "		<img class='homeActivityGraph' src='activityGraph.png' />\n";
			echo "	</a>\n";
			echo "</div>\n";
		}else{
			echo "<img class='homeActivityGraph' src='/logo.png' />\n";
		}
	}else{
		echo "<img class='homeActivityGraph' src='/logo.png' />\n";
	}
	include("stats.php");
	echo "</div>";
	# draw the combined widget
	drawPosterWidget("all");
	# draw the random combined widget
	drawPosterWidget("all", True);
	# new episodes shows and movies
	drawPosterWidget("episodes");
	drawPosterWidget("shows");
	drawPosterWidget("movies");
	# random movies and shows
	drawPosterWidget("movies", True);
	drawPosterWidget("shows", True);

	drawPosterWidget("comics");
	drawPosterWidget("comics", True);

	drawPosterWidget("albums",False,True);
	drawPosterWidget("artists",False,True);
	drawPosterWidget("music",True,True);

	if (file_exists("live")){
		drawPosterWidget("channels",False,True);
		drawPosterWidget("channels",True,True);
		if (file_exists("randomChannels.php")){
			include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
		}
	}
	drawPosterWidget("repos");
	drawPosterWidget("repos", True);

	drawPosterWidget("portal");
	drawPosterWidget("portal", True);

	drawPosterWidget("graphs");
	drawPosterWidget("graphs", True);

	drawPosterWidget("applications");
	drawPosterWidget("applications", True);

	# draw widgets with random words
	$randomWords=(randomWord()." ".randomWord()." ".randomWord());
	#echo "<h1>$randomWords</h1>";
	clear();
	loadSearchIndexResults($randomWords,"shows",9,"Episodes to '$randomWords'");
	loadSearchIndexResults($randomWords,"shows",8,"Shows to '$randomWords'");
	loadSearchIndexResults($randomWords,"movies",-1,"Movies to '$randomWords'");
	loadSearchIndexResults($randomWords,"comics",-1,"Comics to '$randomWords'");
	loadSearchIndexResults($randomWords,"music",-1,"Music to '$randomWords'");
	loadSearchIndexResults($randomWords,"portal",-1,"Links to '$randomWords'");
	loadSearchIndexResults($randomWords,"repos",-1,"Repos to '$randomWords'");

	# add the footer
	include("/var/cache/2web/web/footer.php");
?>

</body>
</html>
