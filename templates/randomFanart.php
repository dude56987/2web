<?PHP
########################################################################
# 2web random fanart
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
?>
<?php
	ini_set('display_errors', 1);
	$fileContent = file_get_contents("fanart.cfg");
	$backgrounds = explode("\n", $fileContent);
	shuffle($backgrounds);
	// redirect to location of random background
	//header("Cache-Control: no-store, no-cache");
	header('Content-type: image/png');
	header('Cache-Control: max-age=90');
	//header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
	header('Location: '.$backgrounds[0]);
?>
