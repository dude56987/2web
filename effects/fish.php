<?PHP
########################################################################
# 2web fish effect
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

// setup the particles, duplicates increase the probablity of particle being used
var fishValues=Array("🐠","🐠","🐠","🐠","🐠","🐠","🐠","🐠","🐠","🐠","🪼","🐙","🐡","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟","🐟");
var tinyFishValues=Array("𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","𜲒","𜲔","🦐","🦐","🦀");
// draw the small fish
for(var index=0;index<Math.floor(window.innerHeight/64);index++){
	new flyingParticle(userChosenParticles=tinyFishValues,userChosenColors=Array("blue","yellow","red","white"),maxSpeed=5,minSpeed=2,maxSize=2,minSize=1,spinSpeed="none",false);
}
// Bubbles layer 1
//for(var index=0;index<Math.floor(window.innerHeight/32);index++){
//	new floatingParticle(userChosenParticles=Array("🫧"),userChosenColors=Array("blue","yellow","red","white"),maxSpeed=3,minSpeed=1,maxSize=2,minSize=1,spinSpeed="slow",false);
//}
// draw the big fish
for(var index=0;index<Math.floor(window.innerHeight/12);index++){
	new flyingParticle(userChosenParticles=fishValues,userChosenColors=Array("white"),maxSpeed=7,minSpeed=4,maxSize=6,minSize=1,spinSpeed="none",true);
}
// Bubbles layer 2
for(var index=0;index<Math.floor(window.innerHeight/32);index++){
	new floatingParticle(userChosenParticles=Array("🫧"),userChosenColors=Array("blue","yellow","red","white"),maxSpeed=4,minSpeed=2,maxSize=5,minSize=1,spinSpeed="slow",false);
}
</script>
