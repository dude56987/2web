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
include("settingsHeader.php");
?>
<div class='inputCard'>
	<h2>Index</h2>
	<ul>
	<li><a href='#cacheQuality'>Cache Quality</a></li>
	<li><a href='#cacheUpgradeQuality'>Cache Upgrade Quality</a></li>
	<li><a href='#cacheResize'>HLS Size</a></li>
	<li><a href='#cacheFramerate'>HLS Framerate(FPS)</a></li>
	<li><a href='#cacheDelay'>Cache Time</a></li>
	<ul>
</div>
<!-- create the theme picker based on installed themes -->
<div id='cacheQuality' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Stream Quality</h2>
		<p>
			Change the quality of video cached videos.
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
<div id='cacheUpgradeQuality' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Upgrade Quality</h2>
		<p>
			Download a higher quality after the inital stream has been created.
		</p>
		<select name='cacheUpgradeQuality'>
			<?php
				// add the cache quality as a option
				if(file_exists("cacheUpgradeQuality.cfg")){
					$cacheUpgradeQuality = file_get_contents('cacheUpgradeQuality.cfg');
					echo "<option selected value='".$cacheQuality."'>$cacheUpgradeQuality</option>";
				}
			?>
			<option value='no_upgrade'>No Upgrade</option>
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
<div id='cacheResize' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>HLS Size</h2>
		<p>
			This is the size of the hls stream generated for first time playback. Quality of the downloaded file itself is set in 'Cache Quality' setting above.
		</p>
		<select name='cacheResize'>
			<?php
				// add the cache Mode as a option
				if(file_exists("cacheResize.cfg")){
					$cacheResize= file_get_contents('cacheResize.cfg');
					echo "<option selected value='".$cacheResize."'>$cacheResize</option>";
				}
			?>
			<option value=''>Copy Input</option>
			<option value='1920x1080'>1080p</option>
			<option value='1240x720'>720p</option>
			<option value='360x240'>240p</option>
			<option value='240x120'>120p</option>
			<option value='120x60'>60p</option>
		</select>
		<button class='button' type='submit'>Change Cache Mode</button>
	</form>
</div>


<!-- create the theme picker based on installed themes -->
<div id='cacheFramerate' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>HLS FrameRate</h2>
		<p>
			This is the HLS stream framerate.
		</p>
		<select name='cacheFramerate'>
			<?php
				// add the cache Mode as a option
				if(file_exists("cacheFramerate.cfg")){
					$cacheFramerate= file_get_contents('cacheFramerate.cfg');
					echo "<option selected value='$cacheFramerate'>$cacheFramerate FPS</option>";
				}
			?>
			<option value=''>Copy Input</option>
			<option value='8'>8 FPS</option>
			<option value='12'>12 FPS</option>
			<option value='24'>24 FPS</option>
			<option value='30'>30 FPS</option>
			<option value='60'>60 FPS</option>
			<option value='120'>120 FPS</option>
		</select>
		<button class='button' type='submit'>Change Cache Mode</button>
	</form>
</div>

<!-- create the theme picker based on installed themes -->
<div id='cacheDelay' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Time</h2>
		<p>
			Change the number of days that the cache will retain videos.
		</p>
		<select name='cacheDelay'>
		<?php
				// add the cache Mode as a option
				if(file_exists("cacheDelay.cfg")){
					$cacheDelay= file_get_contents('cacheDelay.cfg');
					echo "<option selected value='$cacheDelay'>$cacheDelay Days</option>";
				}
		?>
			<option value='1'>1 Days</option>
			<option value='3'>3 Days</option>
			<option value='7' selected>7 Days</option>
			<option value='14'>14 Days</option>
			<option value='30'>30 Days</option>
			<option value='90'>90 Days</option>
			<option value='120'>120 Days</option>
			<option value='365'>365 Days</option>
			<option value='forever'>forever</option>
		</select>
		<button class='button' type='submit'>Change Cache Time</button>
	</form>
</div>

<?PHP
include("ytdl-resolver.php");
?>

</body>
</html>
