<!--
########################################################################
# 2web public help document
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
	<link href="/style.css" rel="stylesheet">
	<script src='/2webLib.js'></script>
</head>
<body>
<?PHP
include("header.php");
include("/usr/share/2web/2webLib.php");
?>

<div class='titleCard linkInfo'>
<h1>Help</h1>

<div class="titleCard">
	<ul>
		<li><a href="#search">🔎 2web Search</a></li>
		<li><a href="#link_index">⛓️ Link Index</a></li>
		<li><a href="#direct_linking">🔗 Direct Linking</a></li>
		<li><a href="#kodi">🇰 Kodi</a></li>
		<ul>
			<li><a href="#kodi_TLDR">🏃 TLDR</a></li>
			<li><a href='#kodi_live'>📡 Live</a></li>
			<li><a href='#kodi_ondemand'>📺 On-Demand</a></li>
			<li><a href='#kodi_comics'>📚 Comics</a></li>
		</ul>
		<li><a href="#desktop">🖥️ Desktop</a></li>
		<ul>
			<li><a href="#desktop_web_interface">🌐 Web Interface</a></li>
			<li><a href="#desktop_direct_links">🔗 Direct links</a></li>
			<li><a href="#desktop_install_kodi">🇰 Install Kodi</a></li>
			<li><a href="#desktop_install_VLC"><span id='vlcIcon'>▲</span> Install VLC</a></li>
		</ul>
		<li><a href="#android">📱 Mobile/Tablet</a></li>
		<ul>
			<li><a href='#mobile_live'>📡 Live</a></li>
			<li><a href='#mobile_ondemand'>📺 On-Demand</a></li>
			<li><a href='#mobile_VLC'><span id='vlcIcon'>▲</span>VLC Links</a></li>
			<li><a href='#mobile_comic'>📚 Comic Viewer</a></li>
		</ul>
	</ul>
</div>

<p>
For when you need more than the web interface can handle. This help section defines how to set up syncing of on-demand ( movies/shows ) ,live ( channels/radio ), and comics ( comics/books ) to 🇰 kodi media centers. This also discusses how to use direct link buttons on media and alternative ( desktop/tablet/phone ) software for access.
</p>

</div>

<div class='titleCard linkInfo'>
	<h2 id="search">🔎 2web Search</h2>
	<p>
		At the top of each page is the 2web global search tool.
	</p>
	<form class='searchBoxForm' action='/search.php' method='get'>
		<input id='searchBox' class='searchBox' type='text' name='q' placeholder='2web Search...' >
		<button id='searchButton' class='searchButton' type='submit'>🔎</button>
	</form>
	<p>
		 This allows you to search all data stored on the 2web server including...
	</p>
	<ul>
		<li>Episodes</li>
		<li>Movies</li>
		<li>Shows</li>
		<li>Comics</li>
		<li>Wiki Articles</li>
		<li>Weather Stations</li>
		<li>Live IPTV Channels</li>
		<li>Live Radio Streams</li>
		<li>System Graphs</li>
		<li>Git Repos</li>
		<li>Music Artists</li>
		<li>Music Albums</li>
		<li>Local Dictionaries</li>
	</ul>
	<p>
		If you cant find something, external links to other search engines and web services are included at the bottom of the results.
	</p>
</div>

<div class='titleCard linkInfo'>
	<h2 id="link_index">⛓️ Link Index</h2>
	<p>
		The below link will take you to the generated link index. This contains a organized hierarchy of links to all multimedia content on the server. This is used by 🇰 kodi to add content from the server to client machines.
	</p>
	<hr>
	<a class='button' href='/kodi/'>
		🇰 KODI
	</a>
	<hr>
</div>

<div class='titleCard linkInfo'>
	<h2 id="direct_linking">🔗 Direct Linking</h2>
	<p>
	On all pages containing the web player there will be a
	</p>
	<span class='button'>🔗 Direct Link</span>
	<p>
	button. This links directly to the source content on the server. Clicking this link on most any platform will open a appropriate player. This is the simplest way to view any content that the webplayer can't handle in your browser.
	</p>
	<p>
	On android you can hold the button and "use open with external app" from the popup
	menu to play the link with your video player. <a href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>VLC</a> is a good one.
	</p>
	<p>
	Some content can still not be played with current web browsers by default, so if anything refuses to play this generally gets around it.
	</p>
</div>

<div class='titleCard linkInfo'>
	<h2 id="kodi">🇰 Kodi</h2>

	<div class="titleCard">
		<ul>
			<li><a href="#kodi_TLDR">🏃 TLDR</a></li>
			<li><a href="#kodi_live">📡 Live</a></li>
			<li><a href="#kodi_ondemand">📺 On-Demand Libary</a></li>
			<li><a href="#kodi_comics">📚 Comics Libary</a></li>
		</ul>
	</div>
	<h3 id="kodi_TLDR" >🏃 TLDR</h3>
	<p>
		<?PHP
		echo "The http://".gethostname().".local/kodi/ directory contains";
		echo " basic http indexes that can be used to link content into kodi.";
		?>
	</p>
	<hr>
	<a class='button' href='/kodi/'>
		🇰 KODI
	</a>
	<hr>
	<h3 id="kodi_live">📡 Live</h3>
	<p>
		To copy the live libary to be used on kodi you must have the iptv simple Client installed
	</p>

	<p>
		To install the client in kodi from the home menu go to
	</p>
	<div class='titleCard'>
		<h4>🔢 Step-By-Step<h4>
		<ol>
			<li>in kodi go to home</li>
			<li>settings</li>
			<li>addons</li>
			<li>install from repository</li>
			<li>all repositories</li>
			<li>PVR clients</li>
			<li>PVR IPTV Simple Client</li>
			<li>Install</li>
		</ol>
	</div>
	<p>
		NOTE: on UBUNTU 🐧 Linux you must install "kodi-pvr-iptvsimple" package with apt
	</p>

	<p>
	Once you have the client installed go to the settings and under the general tab
	change the "Location" to "Remote Path (Internet address)". Change the "M3U Play List URL" to
	</p>
	<div>
	<?PHP
		$channelLink="/kodi/channels.m3u";
		echo '<div>';
		echo '	<a class="button" href="'.$channelLink.'">Link</a>';
		echo '</div>';
		echo '<p>';
		echo '	<a href="'.$channelLink.'">'.$channelLink.'</a>';
		echo '</p>';
	?>
	</div>
	<h3 id="kodi_ondemand">📺 On-Demand</h3>
	To add the OnDemand content of this server to a kodi libary you would go to
	<div class='titleCard'>
		<h4>🔢 Step-By-Step<h4>
		<ol>
			<li>in kodi go to home</li>
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

	<h3 id="kodi_comics" >📚 Comics</h3>
	<p>
		Kodi can be linked to this servers comic collection by using the kodi pictures interface.
	</p>
	<div class='titleCard'>
		<h4>🔢 Step-By-Step<h4>
		<ol>
			<li>in kodi go to home</li>
			<li>settings</li>
			<li>media</li>
			<li>pictures</li>
			<li>Add pictures</li>
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
			<li>Enter the path that has been added above to the list</li>
			<li>Go to comics</li>
			<li>Select OK</li>
			<li>Select OK again</li>
			<li>Your done, you can now access comics on this server from kodi's pictures interface.</li>
		</ol>
	</div>
</div>
<div class='titleCard linkInfo'>
	<h2 id="desktop">🖥️ Desktop</h2>

	<div class="titleCard">
		<ul>
			<li><a href="#desktop_web_interface">🌐 Web Interface</a></li>
			<li><a href="#desktop_direct_links">🔗 Direct links</a></li>
			<li><a href="#desktop_install_kodi">🇰 Install Kodi</a></li>
			<li><a href="#desktop_install_VLC"><span id='vlcIcon'>▲</span> Install VLC</a></li>
			<li><a href="#desktop_comics">📚 Comic Book Viewer</a></li>
		</ul>
	</div>

	<h3 id="desktop_web_interface">🌐 Web Interface</h3>

	<p>
		Give it a try, if the web player gives you any trouble use the direct link button. This should serve most needs and should become more compatible as web standards improve. The web interface has filtering for ondemand content and channels. You can bookmark any part of the media collection in your browser.
	</p>
	<h3 id="desktop_direct_links">🔗 Direct Links</h3>
	<p>
		All media pages generated contain a direct link to the content they contain. This is a direct link to the file so you can save the file. You can paste the direct link in a video player that can stream links.
	</p>
	<h3 id="desktop_install_kodi">🇰 Install Kodi</h3>
	<p>
		You could install kodi on your desktop computer and link it to the media collection on this server.
	</p>
	<h3 id="desktop_install_VLC"><span id='vlcIcon'>▲</span> Install VLC</h3>
	<div>
		<a class='' href='https://www.videolan.org/vlc/'>
			Install VLC for your desktop computer.
		</a>
		<p>
			If you need a desktop player that will play any of the "Direct Link" buttons on the website.
		</p>
	</div>
	<h3 id="desktop_comics">📚 Comic Book Viewer</h3>
	<p>
		You can download any comic book from the website from the CBZ link on the comic book page.
	</p>
	<a class='button'>
		<span class='downloadIcon'>↓</span>
		Download CBZ
	</a>
	<p>
		<a href='https://sourceforge.net/projects/mcomix/'>Mcomix</a> is a comic book viewer for 🐧 Linux. Mcomix should be available in your package manager.
	</p>
	<p>
		If you run 🪟 windows, <a href='https://www.sumatrapdfreader.org/'>SumatraPDF</a> is a comic book viewer for 🪟 windows.
	</p>
</div>
<div class='titleCard linkInfo'>
	<h2 id="android">📱 Mobile/Tablet</h2>

	<div class="titleCard">
	<ul>
		<li><a href="#mobile_live">📡 Live</a></li>
		<li><a href="#mobile_ondemand">📺 On-Demand</a></li>
		<li><a href="#mobile_VLC"><span id='vlcIcon'>▲</span> VLC Links</a></li>
		<li><a href="#mobile_comic">📚 Comic Viewer</a></li>
	</ul>
	</div>

	<h3 id="mobile_live">📡 Live</h3>
	<p>
	There are lots of apps that would allow you to use iptv on android. Simplest is
	<a href='#mobile_VLC'>VLC</a>.
	Open the Link below to with
	<a href='#mobile_VLC'>VLC</a>.
	to view all live channels as a playlist.
	</p>
	<?PHP
	echo '<a class="button" href="/kodi/channels.m3u">channels.m3u</a>';
	$tempPath='http://'.gethostname().'.local/kodi/channels.m3u';
	echo '<p>The Direct link is <a href='.$tempPath.'>'.$tempPath.'</a></p>';
	?>
	<p>
	In order to bypass icon caching and disable link translation done by this server. You can use the below link.
	</p>
	<?PHP
		echo '<a class="button" href="http://'.gethostname().'.local/kodi/channels_raw.m3u">';
		echo 'channels_raw.m3u';
		echo '</a>';
	?>
	<p>
	If you Save the raw link from above to you android device you can watch any of the channels on the playlist by launching the playlist with
	<a href='#mobile_VLC'>VLC</a>.
	in android locally it will play the feeds directly from the internet to you phone. Even if this server is unreachable by your phone as long as you are connected to the internet you can play the channels_raw.m3u file.
	</p>

	<h3 id="mobile_ondemand">📺 On-Demand</h3>
	<p>
		Install Kodi
	</p>
	<h3 id="mobile_install_kodi">🇰 Android Install Kodi</h3>

	<ul>
		<li>
			<a class='' href='https://kodi.wiki/view/HOW-TO:Install_Kodi_for_Android'>
				Kodi WIKI: How to install kodi for android
			</a>
		</li>
		<li>
			<a class='' href='https://play.google.com/store/apps/details?id=org.xbmc.kodi'>
				Click here for the google play store link
			</a>
		</li>
	</ul>

	<p>
		You could install kodi on your android device and link it to the media collection on this server.
	</p>
	<h3 id="mobile_VLC"><span id='vlcIcon'>▲</span> VLC Links</h3>
	<p>
		If you have VLC installed on android you can click the VLC link found on every video media page.
		<br>
		<div class='button'>
			<span id='vlcIcon'>&#9650;</span> VLC
		</div>
		<br>
		You can install VLC from the play store on 🤖 android by clicking <a href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>here</a>.
		VLC is also available for 🍏 IOS <a href='https://itunes.apple.com/app/apple-store/id650377962?pt=454758&ct=vodownloadpage&mt=8'>here</a>.
		If you need VLC for another platform you can go to VLC's main website <a href='https://www.videolan.org/'>here</a>.
	</p>
	<h3 id="mobile_comic">📚 Comic Viewer</h3>
	<p>
		Each comic page	will have a button to download the CBZ file.
	</p>
	<a class='button'>
		<span class='downloadIcon'>↓</span>
		Download CBZ
	</a>
	<p>
	This will download the comic book as a CBZ<sup>(Comic Book Zip)</sup> file. You can read this CBZ file with any comic book viewer that supports CBZ comic book files. A simple open source viewer is <a href='https://en.wikipedia.org/wiki/MuPDF'>MuPDF</a>. You can install MuPDF for 🤖 android from the play store <a href='https://play.google.com/store/apps/details?id=com.artifex.mupdf.viewer.app&hl=en_US&gl=US'>here</a>. You can install MuPDF for 🍏 IOS from the app store <a href='https://itunes.apple.com/us/app/mupdf/id482941798?mt=8&uo=4'>here</a>.
	</p>
</div>

<?PHP
include("footer.php")
?>
</body>
</html>
