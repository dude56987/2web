<!--
########################################################################
# 2web help spinners
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
	<h2 id="widgets">Widgets</h2>
	<p>
		Widgets contain media elements and scroll left and right. When scrolled all the way to the right a "More" button will link to the rest of the playlist in the widget. Widgets contain either media from a playlist or media from search query results. On some pages widgets be scroled left and right with the arrow keys.
	</p>
	<p>
		The Below is a example widget with fake media items in it.
	</p>
	<div class='titleCard widget'>
		<h1>Updated Media</h1>
		<div class='listCard'>
			<?PHP
			foreach(range(1,20) as $rangeIndex){
				echo "		<a class='indexSeries' href=''>\n";
				echo "			<img title='Example Title $rangeIndex' loading='lazy' src='/poster.png'>\n";
				echo "			<div class='indexSeriesTitle'>\n";
				echo "				Example Title $rangeIndex\n";
				echo "			</div>\n";
				echo "		</a>\n";
			}
			?>
			<a class='indexSeries' href='#widgets'>
				<h2 class='moreEpisodesLinkIcon'>📜</h2>
				Full List
			</a>
		</div>
	</div>
	<hr>
</div>
