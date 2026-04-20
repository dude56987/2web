<?PHP
########################################################################
# 2web dodgeball effect
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
// 🌀
// setup the particles, duplicates increase the probablity of particle being used
var particleValues = Array("꩜");
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/16);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("cyan"),maxSpeed=30,minSpeed=20,maxSize=8,minSize=3,spinSpeed="slow",colorFlux=true,flipParticle=false,lockDirection=true);
}
</script>
