<?PHP
########################################################################
# 2web forest effect
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
<script>
function drawAnts(){
	// draw the animals
	var animals=Array("🐜");
	// draw the small fish
	for(var index=0;index<Math.floor(window.innerHeight/16);index++){
		new flyingParticle(userChosenParticles=animals,userChosenColors=Array("brown"),maxSpeed=3,minSpeed=2,maxSize=2,minSize=1,spinSpeed="step",false);
	}
}
function drawRocks(){
	// make the dead trees
	var particleValues = Array("🪨");
	for(var index=0;index<Math.floor(window.innerWidth/128);index++){
		new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("green"),maxSpeed=4,minSpeed=2,maxSize=9,minSize=1,spinSpeed="none",colorFlux=false,flipParticle=false,lockDirection=false,animateColor=false);
	}
}
//
drawAnts();
drawRocks();
</script>
