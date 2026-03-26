<!--
########################################################################
# 2web public help kodi integration
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
		<h4>🔢 Step-By-Step</h4>
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
		echo '	<a class="button" href="'.$channelLink.'">🔗 Link</a>';
		echo '</div>';
		echo '<p>';
		echo '	<a href="'.$channelLink.'">🔗 '.$channelLink.'</a>';
		echo '</p>';
	?>
	</div>
	<h3 id="kodi_ondemand">📺 On-Demand</h3>
	To add the OnDemand content of this server to a kodi libary you would go to
	<div class='titleCard'>
		<h4>🔢 Step-By-Step</h4>
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
		<h4>🔢 Step-By-Step</h4>
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

