<!--
########################################################################
# 2web help comic interface
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
	<h2 id="">📚 Comics</h2>
	<p>
		The web interface can be viewed full page on your device or in scrolling view. If you want you can download the comic to your local device in CBZ or ZIP format.
	</p>
	<h3 id="comic_downloads">Comic Downloads</h3>
	<p>
		You can download any comic book from the website from the CBZ link on the comic book page.
	</p>
	<a class='button'>
		<span class='downloadIcon'>▼</span>
		Download CBZ
	</a>
	<hr>

	<h3 id="web_viewer">Web Viewer</h3>
	<p>
		You can open a comic in the default view by clicking the play button in the comic overview page.
	</p>
	<span class='loadComicButton' style='background-image: url("/poster.png")'>▷</span>
	<p>
		The default view works for most comics and books but there is a alternative auto-sized scroll view. There is also a real size scroll view were browser controls can zoom the image size. All of this is available on the comic overview page under the large play button(▷).
	</p>
	<h3 id="web_viewer">Default Viewer</h3>
	<p>
		When viewing the comic in the default fullscreen view each edge of the screen has controls hidden until clicked or touched.
	</p>
	<h4>Top</h4>
	<p>
		This is the exit button and will return to the comic overview. This will also exit fullscreen if the comic is fullscreen.
	</p>

	<h4>Left</h4>
	<p>
		Go back one page. On first page go back to the overview.
	</p>

	<h4>Right</h4>
	<p>
		Go forward one page. On last page go back to the overview.
	</p>

	<h4>Bottom</h4>
	<p>
		This contains a bundle of controls and gauges. The bottom will flash and fade out on every page flip to allow you to see your progress reading the document.
		<ul>
			<li>
				The page number out of the total number of pages.
			</li>
			<li>
				The percentage you have progressed reading the book.
			</li>
			<li>
				The fullscreen button. You will have to verify fullscreen after clicking this button for browser security purposes.
			</li>
			<li>
				The autoplay button that flips to the next page every 60 seconds. This can be stopped by clicking any button in the interface or by clicking this button again.
			</li>
		</ul>
	</p>

	<hr>
</div>
