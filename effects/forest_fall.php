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
// make the dead trees
var particleValues = Array("🪾");
for(var index=0;index<Math.floor(window.innerWidth/64);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("green"),maxSpeed=4,minSpeed=2,maxSize=6,minSize=6,spinSpeed="wiggle",colorFlux=false,flipParticle=false,lockDirection=false,animateColor=false);
}
// create the falling leaves
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new fastFallingParticle(userChosenParticles=Array("🍁"),userChosenColors=Array("white"),maxSpeed=4,minSpeed=2,maxSize=1,minSize=1,spinSpeed="slow");
}
// make the dead trees
var particleValues = Array("🪾");
for(var index=0;index<Math.floor(window.innerWidth/64);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("green"),maxSpeed=4,minSpeed=2,maxSize=7,minSize=7,spinSpeed="wiggle",colorFlux=false,flipParticle=false,lockDirection=false,animateColor=false);
}
// create the falling leaves
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new fastFallingParticle(userChosenParticles=Array("🍁"),userChosenColors=Array("white"),maxSpeed=4,minSpeed=2,maxSize=1,minSize=1,spinSpeed="slow");
}
// make the dead trees
var particleValues = Array("🪾");
for(var index=0;index<Math.floor(window.innerWidth/64);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("green"),maxSpeed=4,minSpeed=2,maxSize=9,minSize=9,spinSpeed="wiggle",colorFlux=false,flipParticle=false,lockDirection=false,animateColor=false);
}
// create the falling leaves
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new fastFallingParticle(userChosenParticles=Array("🍁"),userChosenColors=Array("white"),maxSpeed=4,minSpeed=2,maxSize=3,minSize=3,spinSpeed="slow");
}
// make the dead trees
var particleValues = Array("🪾");
for(var index=0;index<Math.floor(window.innerWidth/64);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("green"),maxSpeed=4,minSpeed=2,maxSize=10,minSize=10,spinSpeed="wiggle",colorFlux=false,flipParticle=false,lockDirection=false,animateColor=false);
}
// create the falling leaves
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new fastFallingParticle(userChosenParticles=Array("🍁"),userChosenColors=Array("white"),maxSpeed=4,minSpeed=2,maxSize=4,minSize=3,spinSpeed="slow");
}
</script>
