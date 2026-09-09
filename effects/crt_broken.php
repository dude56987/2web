<?PHP
########################################################################
# 2web CRT broken effect
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
	@keyframes crt_flicker{
		0%{
			background: repeating-linear-gradient(90deg,transparent 1px,black 2px),
				repeating-linear-gradient(0deg,transparent 1px,black 2px);
		}
		20%{
			background: repeating-linear-gradient(90deg,black 1px,black 2px),
				repeating-linear-gradient(0deg,black 1px,black 2px);
		}
		40%{
			background: repeating-linear-gradient(90deg,transparent 1px,black 2px),
				repeating-linear-gradient(0deg,transparent 1px,black 2px);
		}
		60%{
			background: repeating-linear-gradient(90deg,black 1px,black 2px),
				repeating-linear-gradient(0deg,black 1px,black 2px);
		}
		80%{
			background: repeating-linear-gradient(90deg,transparent 1px,black 2px),
				repeating-linear-gradient(0deg,transparent 1px,black 2px);
		}
		100%{
			background: repeating-linear-gradient(90deg,black 1px,black 2px),
				repeating-linear-gradient(0deg,black 1px,black 2px);
		}
	}
	.screenOverlay{
		animation: crt_flicker 0.3s infinite;
		position: fixed;
		top: 0px;
		left: 0px;
		width: 100dvw;
		height: 100dvh;
		opacity: 0.35;
		pointer-events: none;
		z-index: 200;
		background: repeating-linear-gradient(90deg,transparent 1px,black 2px),
			repeating-linear-gradient(0deg,transparent 1px,black 2px);
	}
</style>
