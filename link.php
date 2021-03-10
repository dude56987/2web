<html class='randomFanart'>
<head>
	<link href="style.css" rel="stylesheet">
</head>
<body>
<?PHP
include("header.html")
?>
<div class='titleCard linkInfo'>
<h1>Linking</h1>

<div class="inputCard">
	<ul>
		<li><a href="#hard_linking">Hard Linking</a></li>
		<li><a href="#android">Android</a></li>
		<ul>
			<li><a href="#android_live">Android Live</a></li>
			<li><a href="#android_ondemand">Android On-Demand</a></li>
		</ul>
		<li><a href="#kodi">Kodi</a></li>
		<ul>
			<li><a href="#kodi_live">Live Channels</a></li>
			<li><a href="#kodi_ondemand">On-Demand Libary</a></li>
		</ul>
		<li><a href="#desktop">Desktop</a></li>
		<ul>
			<li><a href="#desktop_web_interface">Web Interface</a></li>
			<li><a href="#desktop_hard_links">Hard links</a></li>
			<li><a href="#desktop_install_kodi">Install Kodi</a></li>
		</ul>
	</ul>
</div>

<p>
For when you need more that the web player can handle. This help section defines how to set up syncing of on-demand movies/shows and live channels/radio to kodi media centers. This also discusses how to use hard link buttons on media.
</p>

</div>

<div class='titleCard linkInfo'>
	<h2 id="hard_linking">Hard Linking</h2>
	<p>
	On all pages containing the web player there will be a
	</p>
	<span class='button'>Hard Link</span>
	<p>
	button. This links
  directly to the content. This is the simplest most compatible way to view any content.
	</p>
	<p>
	On android you can hold the button and "use open with external app" from the popup
	menu to play the link with <a href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>VLC</a>.
	</p>
	<p>
	Some content can still not be played with web player technology so if anything refuses
  to play this generally gets around it.
	</p>
</div>
<div class='titleCard linkInfo'>
	<h2 id="android">Android</h2>

	<div class="inputCard">
	<ul>
		<li><a href="#android_live">Android Live</a></li>
		<li><a href="#android_ondemand">Android On-Demand</a></li>
	</ul>
	</div>

	<h3 id="android_live">Android Live</h3>
	<p>
	There are lots of apps that would allow you to use iptv on android. Simplest is
	<a href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>VLC</a>.
	Open the Link below to with
	<a href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>VLC</a>.
	to view all live channels as a playlist.
	</p>
	<?PHP
	echo '<a class="button" href="/kodi/channels.m3u">Link</a>';

	echo '<p>The hard link is http://'.gethostname().'.local:444/kodi/channels.m3u</p>';
	?>
	<p>
	In order to bypass icon caching and disable link translation done by this server. You can use the below link.
	</p>
	<?PHP
	echo'<a class="button" href="http://'.gethostname().'.local:444/kodi/channels_raw.m3u">RAW Link</a>';
?>
	<p>
	If you Save the raw link from above to you android device you can watch any of the channels on the playlist by launching the playlist with
	<a href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>VLC</a>.
	in android locally it will play the feeds directly from the network to you phone.
	</p>

	<h3 id="android_ondemand">Android On-Demand</h3>
	<p>
		Install Kodi
	</p>
	<h3 id="android_install_kodi">Android Install Kodi</h3>

	<a class='button' href='https://kodi.wiki/view/HOW-TO:Install_Kodi_for_Android'>
		Kodi WIKI: How to install kodi for android
	</a>
	<a class='button' href='https://play.google.com/store/apps/details?id=org.xbmc.kodi'>
		Click here for the google play store link
	</a>

	<p>
		You could install kodi on your android device and link it to the media collection on this server.
	</p>

</div>
<div class='titleCard linkInfo'>
	<h2 id="kodi">Kodi</h2>

	<div class="inputCard">
		<ul>
			<li><a href="#kodi_live">Live Channels</a></li>
			<li><a href="#kodi_ondemand">On-Demand Libary</a></li>
		</ul>
	</div>

	<h3 id="kodi_live">Kodi Live</h3>
	<p>
		To copy the live libary to be used on kodi you must have the iptv simple Client installed
	</p>

	<p>
		To install the client in kodi from the home menu go to
	</p>
	<ol class='inputCard'>
	<li>home</li>
	<li>settings</li>
	<li>addons</li>
	<li>install from repository</li>
	<li>all repositories</li>
	<li>PVR clients</li>
	<li>PVR IPTV Simple Client</li>
	<li>Install</li>
	</ol>
	<p>
		NOTE: on UBUNTU linux you must install "kodi-pvr-iptvsimple" package with apt
	</p>

	<p>
	Once you have the client installed go to the settings and under the general tab
	change the "Location" to "Remote Path (Internet address)". Change the "M3U Play List URL" to
	</p>
	<div>
	<?PHP
		$channelLink="/kodi/channels.m3u";
		echo '<div>';
		echo '<a class="button" href="'.$channelLink.'">Link</a>';
		echo '</div>';
		echo '<p>';
		echo '<a href="'.$channelLink.'">'.$channelLink.'</a>';
		echo '</p>';
	?>
	</div>
	<h3 id="kodi_ondemand">Kodi On-Demand</h3>
		To add the OnDemand content of this server to a kodi libary you would go to

		<ol class='inputCard'>
			<li>home</li>
			<li>settings</li>
			<li>media</li>
			<li>videos</li>
			<li>Add videos</li>
			<li>Browse</li>
			<li>Add network location</li>
			<li>Change "Protocol" to "Web server directory"</li>
			<li>Change "Server address" to "
			<?PHP
				$channelLink="http://".gethostname().".local";
				echo '<a href="'.$channelLink.'">'.$channelLink.'</a>';
			?>
			"</li>
			<li>Change "Remote Path" to "kodi"</li>
			<li>Change "port" to "444"</li>
			<li>Enter the Path That has been added</li>
			<li>Go to movies</li>
			<li>On the "Set content" screen</li>
			<li>Change "This directory contains" to "movies"</li>
			<li>Change "Choose information provider" to "Local information only"</li>
			<li>Set "Movies are in seprate folders that match the movie title" to "True"</li>
			<li>Set "Scan recursively" to "False"</li>
			<li>Repeat the process of adding the shows repository as well but set "scan recursively" to "True"</li>
		</ol>
</div>
<div class='titleCard linkInfo'>
	<h2 id="desktop">Desktop</h2>

	<div class="inputCard">
		<ul>
			<li><a href="#desktop_web_interface">Web Interface</a></li>
			<li><a href="#desktop_hard_links">Hard links</a></li>
			<li><a href="#desktop_install_kodi">Install Kodi</a></li>
			<li><a href="#desktop_install_VLC">Install Kodi</a></li>
		</ul>
	</div>

	<h3 id="desktop_web_interface">Web Interface</h3>

	<p>
		Give it a try, if the web player gives you any trouble use the hard link button. This should serve most needs and should become more compatible as web standards improve. The web interface has filtering for ondemand content and channels. You can bookmark any part of the media collection in your browser.
	</p>
	<h3 id="desktop_hard_links">Hard Links</h3>
	<p>
		All media pages generated contain a hard link to the content they contain. This is a direct link to the file so you can save the file. You can paste the hard link in a video player that can stream links.
	</p>
	<h3 id="desktop_install_kodi">Install Kodi</h3>
	<p>
		You could install kodi on your desktop computer and link it to the media collection on this server.
	</p>
	<h3 id="desktop_install_VLC">Install VLC</h3>
	<div>
		<a class='button' href='https://www.videolan.org/vlc/'>
			Install VLC for your desktop computer.
		</a>
		<p>
			If you need a desktop player that will play any of the "Hard Link" buttons on the website. You can install VLC for almost any computing device (windows/mac/Linux/android/ios/BSD/chromeos).
		</p>
	</div>
</div>
<?PHP
include("header.html")
?>
</body>
</html>
