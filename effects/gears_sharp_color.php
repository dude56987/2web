<?PHP
########################################################################
# 2web gears effect
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
var particleValues = Array("⛭");
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/12);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("red","green","blue","yellow"),maxSpeed=4,minSpeed=2,maxSize=7,minSize=4,spinSpeed="slow",colorFlux=false,flipParticle=false,lockDirection=false);
}
</script>
