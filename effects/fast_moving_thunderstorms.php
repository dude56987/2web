<?PHP
########################################################################
# 2web crayon effect
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
// create the clouds
for(var index=0;index<Math.floor(window.innerHeight/128);index++){
	new flyingParticle(userChosenParticles=Array("☁️"),userChosenColors=Array("white"),maxSpeed=10,minSpeed=8,maxSize=20,minSize=10,spinSpeed="none",false);
}
// create the rain
for(var index=0;index<Math.floor(window.innerWidth/12);index++){
	new fastFallingParticle(userChosenParticles=Array("💧"),userChosenColors=Array("white"),maxSpeed=20,minSpeed=15,maxSize=3,minSize=1,spinSpeed="none");
}
// create the clouds
for(var index=0;index<Math.floor(window.innerHeight/128);index++){
	new flyingParticle(userChosenParticles=Array("☁️"),userChosenColors=Array("white"),maxSpeed=10,minSpeed=8,maxSize=20,minSize=10,spinSpeed="none",false);
}
// drop the lightining
for(var index=0;index<Math.floor(window.innerWidth/512);index++){
	new fastFallingParticle(userChosenParticles=Array("⚡"),userChosenColors=Array("white"),maxSpeed=100,minSpeed=50,maxSize=8,minSize=3,spinSpeed="none");
}
// create the clouds
for(var index=0;index<Math.floor(window.innerHeight/128);index++){
	new flyingParticle(userChosenParticles=Array("☁️"),userChosenColors=Array("white"),maxSpeed=10,minSpeed=8,maxSize=20,minSize=10,spinSpeed="none",false);
}
</script>
