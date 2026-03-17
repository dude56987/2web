<!--
########################################################################
# 2web help kodi buttons
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
<?PHP
	if (detectEnabledStatus("kodi2web")){
		# check if the http share link is enabled
		if (yesNoCfgCheck("/etc/2web/kodi/enableHttpShare.cfg")){
			echo "<div class='titleCard linkInfo'>";
			echo "	<h2 id='link_index'>⛓️ Link Index</h2>";
			echo "	<p>";
			echo "		The below link will take you to the generated link index. This contains a organized hierarchy of links to all multimedia content on the server. This is used by 🇰 kodi to add content from the server to client machines.";
			echo "	</p>";
			echo "	<hr>";
			echo "	<a class='button' href='/kodi/'>";
			echo "		🇰 KODI";
			echo "	</a>";
			echo "	<hr>";
			echo "</div>";
		}
	}
?>
	<hr>
</div>
