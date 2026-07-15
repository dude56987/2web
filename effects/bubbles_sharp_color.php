<?PHP
########################################################################
# 2web bubbles effect
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
// Bubbles sharp spinning lopsided
for(var index=0;index<Math.floor(window.innerHeight/12);index++){
	new floatingParticle(userChosenParticles=Array("❍"),userChosenColors=Array("var(--solidBackground)"),maxSpeed=15,minSpeed=8,maxSize=2,minSize=1,spinSpeed="slow",true);
}
// Bubbles sharp round swaying
for(var index=0;index<Math.floor(window.innerHeight/12);index++){
	new floatingParticle(userChosenParticles=Array("◯","○"),userChosenColors=Array("var(--solidBackground)"),maxSpeed=15,minSpeed=8,maxSize=3,minSize=1,spinSpeed="sway",true);
}
</script>
