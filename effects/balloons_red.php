<?PHP
########################################################################
# 2web lantern effect
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
// particles layer 1
for(var index=0;index<Math.floor(window.innerHeight/12);index++){
	new floatingParticle(userChosenParticles=Array("🎈"),userChosenColors=Array("white"),maxSpeed=3,minSpeed=1,maxSize=6,minSize=1,spinSpeed="sway",false);
}
</script>
