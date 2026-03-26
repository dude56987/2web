<!--
########################################################################
# 2web help introduction
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
	<h2 id="">Intergration Introduction</h2>
	<p>
	The web interface is the most supported means of accessing the media collection. However you can connect KODI to the servers collection by using the KODI share at
	<?PHP
		echo "<pre>http://".$_SERVER["HTTP_HOST"]."/kodi/</pre>\n";
	?>
	</p>
	<p>
	This is a organized set of HTTP directories containing the media collection for simple intergration with KODI. Metadata is stored on the server so clients only need acces to THIS server. If you want less than full media library intergration you can browse the HTTP share with any media browser that supports HTTP. Direct links on webpages can also be copy pasted into your media player of choice. The last option for intergration is you have a download button on most media pages that will allow you to download the media locally in order to use it with your perfered software on your system. Each of these methods are explained in more detail in thier own sections of the help.
	</p>
	<hr>
</div>
