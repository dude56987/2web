<!--
########################################################################
# 2web public help desktop integration
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
<div class='titleCard linkInfo'>
	<h2 id="android">📱 Mobile/Tablet</h2>

	<div class="titleCard">
	<ul>
		<li><a href="#mobile_find_servers">Find Local 2web Servers</a></li>
		<li><a href="#mobile_live">📡 Live</a></li>
		<li><a href="#mobile_ondemand">📺 On-Demand</a></li>
		<li><a href="#mobile_VLC"><span id='vlcIcon'>▲</span> VLC Links</a></li>
		<li><a href="#mobile_comic">📚 Comic Viewer</a></li>
	</ul>
	</div>

	<h3 id="mobile_live">Find Local 2web Servers</h3>
	<p>
	2web will broadcast on the lan as a zeroconf/bonjour service. You can use any zeroconf/bonjour browser in order to access 2web servers on the network.
	</p>
	<p>
	On 🤖 android there is a open source service browser that will scan and list all 2web servers on the LAN.
	</p>
	<a class='smallButton' target='_new' href='https://play.google.com/store/apps/details?id=de.wellenvogel.bonjourbrowser'>
		🔗 Click here for the google play store link
	</a>
	<hr>

	<h3 id="mobile_live">📡 Live</h3>
	<p>
	There are lots of apps that would allow you to use iptv on android. Simplest is
	<a href='#mobile_VLC'>VLC</a>.
	Open the Link below to with
	<a href='#mobile_VLC'>VLC</a>.
	to view all live channels as a playlist.
	</p>
	<?PHP
	echo '<a class="button" href="/live/channels.m3u">🔗 channels.m3u</a>';
	$tempPath='http://'.$_SERVER["HTTP_HOST"].'.local/live/channels.m3u';
	echo '<p>The Direct link is <a href='.$tempPath.'>🔗 '.$tempPath.'</a></p>';
	?>
	<p>
	In order to bypass icon caching and disable link translation done by this server. You can use the below link.
	</p>
	<?PHP
		echo '<a class="button" target="_new" href="http://'.$_SERVER["HTTP_HOST"].'.local/kodi/channels_raw.m3u">';
		echo '🔗 channels_raw.m3u';
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
			<a class='' target="_new" href='https://kodi.wiki/view/HOW-TO:Install_Kodi_for_Android'>
				🔗 Kodi WIKI: How to install kodi for android
			</a>
		</li>
		<li>
			<a class='' target="_new" href='https://play.google.com/store/apps/details?id=org.xbmc.kodi'>
				🔗 Click here for the google play store link
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
		You can install VLC from the play store on 🤖 android by clicking <a target="_new"  href='https://play.google.com/store/apps/details?id=org.videolan.vlc'>here</a>.
		VLC is also available for 🍏 IOS <a target="_new" href='https://itunes.apple.com/app/apple-store/id650377962?pt=454758&ct=vodownloadpage&mt=8'>here</a>.
		If you need VLC for another platform you can go to VLC's main website <a target="_new" href='https://www.videolan.org/'>here</a>.
	</p>
	<h3 id="mobile_comic">📚 Comic Viewer</h3>
	<p>
		Each comic page	will have a button to download the CBZ file.
	</p>
	<a class='button'>
		<span class='downloadIcon'>▼</span>
		Download CBZ
	</a>
	<p>
	This will download the comic book as a CBZ<sup>Comic Book Zip</sup> file. You can read this CBZ file with any comic book viewer that supports CBZ comic book files. A simple open source viewer is <a target="_new" href='https://en.wikipedia.org/wiki/MuPDF'>MuPDF</a>. You can install MuPDF for 🤖 android from the play store <a target="_new" href='https://play.google.com/store/apps/details?id=com.artifex.mupdf.viewer.app&hl=en_US&gl=US'>here</a>. You can install MuPDF for 🍏 IOS from the app store <a target="_new" href='https://itunes.apple.com/us/app/mupdf/id482941798?mt=8&uo=4'>here</a>.
	</p>
</div>
