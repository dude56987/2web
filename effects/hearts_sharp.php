<?PHP
########################################################################
# 2web hearts effect
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
<style>
	.particle{
		font-family: font2webGlyph !important;
		font-variant-emoji: text !important;
	}
</style>
<script>
	//new fastFallingParticle(userChosenParticles=Array("💞︎","💖︎","💟︎","💝︎","💗︎","🩷","🧡","💛","💚","💙","🩵","💜","🤎","🤍","♥️"),userChosenColors=Array("white","red","lightgreen","orange","cyan"),maxSpeed=4,minSpeed=2,maxSize=5,minSize=2,spinSpeed="slow");
//
var userChosenColors=Array("white","red","lightgreen","orange","cyan","turquoise","yellow","pink","hotpink","blue");
//
var userChosenParticles=Array("❤︎","💞︎","💖︎","💝︎","💗︎","🧡","💛","💚","💙","💜","🖤︎");
//
for(var index=0;index<Math.floor(window.innerWidth/12);index++){
	new fastFallingParticle(userChosenParticles,userChosenColors,maxSpeed=4,minSpeed=2,maxSize=5,minSize=2,spinSpeed="slow");
}
</script>
