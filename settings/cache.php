<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web cache settings
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
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
################################################################################
include($_SERVER['DOCUMENT_ROOT'].'/header.php');
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
				if(file_exists("/etc/2web/cache/cacheQuality.cfg")){
					$cacheQuality = file_get_contents('/etc/2web/cache/cacheQuality.cfg');
					echo "<option selected value='".$cacheQuality."'>$cacheQuality</option>";
				}
			?>
			<option value='best' >best</option>
			<option value='res:12000' >12K</option>
			<option value='res:8000' >8K</option>
			<option value='res:4000' >4K</option>
			<option value='res:1080' >1080p</option>
			<option value='res:720' >720p</option>
			<option value='res:360' >360p</option>
			<option value='res:240' >240p</option>
			<option value='worst'>worst</option>
		</select>
		<button class='button' type='submit'>Change Quality</button>
	</form>
</div>

<!-- create the theme picker based on installed themes -->
<div id='cacheUpgradeQuality' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Upgrade Quality</h2>
		<p>
		<ul>
			<li>
				Download a higher quality after the inital stream has been created.
			</li>
			<li>
				Any chosen upgrade quality other than "No Upgrade" will also add chapters to most videos.
			</li>
		</ul>
		</p>
		<select name='cacheUpgradeQuality'>
			<?php
				// add the cache quality as a option
				if(file_exists("/etc/2web/cache/cacheUpgradeQuality.cfg")){
					$cacheUpgradeQuality = file_get_contents('/etc/2web/cache/cacheUpgradeQuality.cfg');
					echo "<option selected value='".$cacheQuality."'>$cacheUpgradeQuality</option>";
				}
			?>
			<option value='no_upgrade'>No Upgrade</option>
			<option value='best' >best</option>
			<option value='res:12000' >12K</option>
			<option value='res:8000' >8K</option>
			<option value='res:4000' >4K</option>
			<option value='res:1080' >1080p</option>
			<option value='res:720' >720p</option>
			<option value='res:360' >360p</option>
			<option value='res:240' >240p</option>
			<option value='worst'>worst</option>
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
				if(file_exists("/etc/2web/cache/cacheResize.cfg")){
					$cacheResize= file_get_contents('/etc/2web/cache/cacheResize.cfg');
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
		<button class='button' type='submit'>Change Cache Quality</button>
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
				if(file_exists("/etc/2web/cache/cacheFramerate.cfg")){
					$cacheFramerate= file_get_contents('/etc/2web/cache/cacheFramerate.cfg');
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
		<button class='button' type='submit'>Change Cache Framerate</button>
	</form>
</div>

<div id='cacheDelay' class='inputCard'>
	<form action='admin.php' class='buttonForm' method='post'>
		<h2>Cache Time</h2>
		<ul>
			<li>
				Change the number of days that the system caches will retain videos.
			</li>
			<li>
				If set to forever no cleanup will occur. This can take up a EXTREME amount of disk space!
			</li>
			<li>
				The forever option should only be used for archive purposes.
			</li>
		</ul>
		<select name='cacheDelay'>
			<?php
				// add the cache Mode as a option
				if(file_exists("/etc/2web/cache/cacheDelay.cfg")){
					$cacheDelay= file_get_contents('/etc/2web/cache/cacheDelay.cfg');
					echo "<option selected value='$cacheDelay'>$cacheDelay Days</option>";
				}
			?>
			<option value='1'>1 Days</option>
			<option value='3'>3 Days</option>
			<option value='7'>7 Days</option>
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

<div id='cacheNewEpisodes' class='inputCard'>
	<h2>Cache New Episodes</h2>
		<ul>
			<li>
				Automatically cache episodes that first aired this month.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/cacheNewEpisodes.cfg","Caching New Episodes","cacheNewEpisodes");
		?>
</div>

<div id='transcodeForWebpages' class='inputCard'>
	<h2>Transcode</h2>
		<ul>
			<li>
				Automatically transcode videos into a format that can be played though the webplayer.
			</li>
		</ul>
		<?php
		buildYesNoCfgButton("/etc/2web/transcodeForWebpages.cfg","Webpage Transcoding","transcodeForWebpages");
		?>
</div>

<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/ytdl-resolver.php");
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
