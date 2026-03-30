<?PHP
########################################################################
# 2web rain effect
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
#tempFlake.style.transform = "blur("+Math.floor(Math.random * 99)+"px);";
?>
<script>
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/6);index++){
	new fastFallingParticle(userChosenParticles=Array("💧"),userChosenColors=Array("blue"),maxSpeed=20,minSpeed=15,maxSize=3,minSize=1,spinSpeed="none");
}
</script>
