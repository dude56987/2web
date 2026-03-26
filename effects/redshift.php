<?PHP
########################################################################
# 2web redshift effect
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
?>
<style>
	.screenOverlay{
		position: fixed;
		top: 0px;
		left: 0px;
		width: 100dvw;
		height: 100dvh;
		opacity: 0.35;
		background-color: red;
		pointer-events: none;
		z-index: 100;
	}
	img{
		filter: grayscale(1);
	}
	html{
		background-blend-mode: luminosity;
	}
</style>
<script>
// Use the screenOverlay object to create a redshift effect on the screen
// - adjust the redshift effect based on the local system time
// - move the opacity of the redshift overlay to 0 from 9 am to 6 pm
// - slowly move redshift up to max of 0.35 from 6 pm to 9 am
// - use a loop in javascript to update the screen opacity once every 120 seconds
</script>
