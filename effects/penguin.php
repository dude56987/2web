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
function drawPenguins(){
	// draw the animals
	var animals=Array("🐧");
	// draw the small fish
	for(var index=0;index<Math.floor(window.innerHeight/64);index++){
		new flyingParticle(userChosenParticles=animals,userChosenColors=Array("brown"),maxSpeed=2,minSpeed=1,maxSize=4,minSize=3,spinSpeed="step",false);
	}
}
function drawSnow(){
	// snow
	for(var index=0;index<Math.floor(window.innerWidth/64);index++){
		new fastFallingParticle(userChosenParticles=Array("❅","❆"),userChosenColors=Array("white"),maxSpeed=4,minSpeed=2,maxSize=4,minSize=1,spinSpeed="slow");
	}
}
function drawSnowmen(){
	// make the snowmen
	var particleValues = Array("⛄");
	for(var index=0;index<Math.floor(window.innerWidth/512);index++){
		new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("green"),maxSpeed=4,minSpeed=2,maxSize=7,minSize=7,spinSpeed="wiggle",colorFlux=false,flipParticle=false,lockDirection=false,animateColor=false);
	}
}
// draw out the layers
drawPenguins();
drawSnow();
drawPenguins();
//drawSnowmen();
drawPenguins();
drawSnow();
drawPenguins();
drawSnow();
</script>
