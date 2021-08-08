<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
################################################################################
include("header.php");
?>

<div class='titleCard'>
	<h1>Settings</h1>
	<a class='button' href='system.php'>SYSTEM</a>
	<a class='button' href='tv.php'>TV</a>
	<a class='button' href='radio.php'>RADIO</a>
	<a class='button' href='nfo.php'>NFO</a>
	<a class='button' href='comics.php'>COMICS</a>
	<a class='button' href='cache.php'>CACHE</a>
	<a class='button' href='log.php'>LOG</a>
</div>

<!-- create the theme picker based on installed themes -->
<div class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Quality</h2>
		<p>
			Change the quality of video for future cached videos.
		</p>
		<select name='cacheQuality'>
			<?php
				// add the cache quality as a option
				if(file_exists("cacheQuality.cfg")){
					$cacheQuality = file_get_contents('cacheQuality.cfg');
					echo "<option selected value='".$cacheQuality."'>$cacheQuality</option>";
				}
			?>
			<option value='worst'>worst</option>
			<option value='best' >best</option>
			<option value='360p,240p' >360p,240p</option>
			<option value='720p,360p,240p' >720p,360p,240p</option>
			<option value='1080p,720p,360p,240p' >1080p,720p,360p,240p</option>
			<option value='240p,360p,720p,1080p' >240p,360p,720p,1080p</option>
			<option value='4000p,1080p,720p,360p,240p' >4K</option>
			<option value='8000p,4000p,1080p,720p,360p,240p' >8K</option>
			<option value='12000p,8000p,4000p,1080p,720p,360p,240p' >12K</option>
		</select>
		<button class='button' type='submit'>Change Quality</button>
	</form>
</div>

<!-- create the theme picker based on installed themes -->
<div class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Time</h2>
		<p>
			Change the number of days that the cache will retain videos.
		</p>
		<select name='cacheDelay'>
			<option value='1'>1</option>
			<option value='3'>3</option>
			<option value='7' selected>7</option>
			<option value='14'>14</option>
			<option value='30'>30</option>
			<option value='90'>90</option>
			<option value='120'>120</option>
			<option value='365'>365</option>
			<option value='forever'>forever</option>
		</select>
		<button class='button' type='submit'>Change Cache Time</button>
	</form>
</div>

<!-- create the theme picker based on installed themes -->
<div class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Mode</h2>
		<p>
			Change the download mode. Default mode re-streams the video file as a cached HLS stream. Compatibility mode is to allow lower CPU resources on a server to not disable caching videos.
		</p>
		<select name='cacheMode'>
			<?php
				// add the cache Mode as a option
				if(file_exists("cacheMode.cfg")){
					$cacheMode= file_get_contents('cacheMode.cfg');
					echo "<option selected value='".$cacheMode."'>$cacheMode</option>";
				}
			?>
			<option value='default'>default</option>
			<option value='compat'>compatibility</option>
		</select>
		<button class='button' type='submit'>Change Cache Mode</button>
	</form>
</div>

<?PHP
include("ytdl-resolver.php");
?>

</body>
</html>
