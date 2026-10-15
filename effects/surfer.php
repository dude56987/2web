<?PHP
########################################################################
# 2web surfer effect
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
# include the base particle system
include("/usr/share/2web/effects/particleBase.php");
?>
<style>
	.particle{
		text-shadow: cornflowerblue 0 0 6rem,cornflowerblue 0 0 6rem,cornflowerblue 0 0 6rem,cornflowerblue 0 0 6rem,cornflowerblue 0 0 6rem,cornflowerblue 0 0 6rem;
		animation-duration: 4s !important;
	}
</style>
<script>
// particles layer 1
for(var index=0;index<Math.floor(window.innerHeight/64);index++){
	new fastFallingParticle(userChosenParticles=Array("🏄","🏄‍♂️","🏄‍♀️"),userChosenColors=Array("white"),maxSpeed=5,minSpeed=2,maxSize=7,minSize=4,spinSpeed="sway",fluxColor=false);
}
</script>
