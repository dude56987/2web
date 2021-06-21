<html>
<head>
	<link rel="stylesheet" href="style.css" />
	<title>404</title>
</head>
<body>
	<?PHP
		include("header.html");
	?>
	<h1>404</h1>
	<p>File could not be found!</p>
	<p>
	<?PHP
		echo $_SERVER['REQUEST_URI'];
	?>
	</p>
	<?PHP
		//include("randomMovies.index");
		//include("randomShows.index");
		//include("randomComics.index");
		//include("randomChannels.index");
		include("header.html");
	?>
</body>
</html>
