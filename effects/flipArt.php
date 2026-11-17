<?PHP
########################################################################
# 2web flipArt effect
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
	@keyframes flipOverAndOver{
		0%{
			scale: 1 1;
		}
		50%{
			scale: 1 -1;
		}
		100%{
			scale: 1 1;
		}
	}
	@keyframes flipOverAndOverRev{
		0%{
			scale: 1 -1;
		}
		50%{
			scale: 1 1;
		}
		100%{
			scale: 1 -1;
		}
	}
	@keyframes flopOverAndOver{
		0%{
			scale: 1 1;
		}
		50%{
			scale: -1 1;
		}
		100%{
			scale: 1 1;
		}
	}
	@keyframes flopOverAndOverRev{
		0%{
			scale: -1 1;
		}
		50%{
			scale: 1 1;
		}
		100%{
			scale: -1 1;
		}
	}
	.particle_spin_right_flip{
		animation: flipOverAndOver 4s infinite linear !important;
		background: url("/randomFanart.php") !important;
		background-size: cover !important;
	}
	.particle_spin_right_flop{
		animation: flopOverAndOver 4s infinite linear !important;
		background: url("/randomFanart.php") !important;
		background-size: cover !important;
	}
	.particle_spin_left_flipRev{
		animation: flipOverAndOver 4s infinite linear !important;
		background: url("/randomFanart.php") !important;
		background-size: cover !important;
	}
	.particle_spin_left_flopRev{
		animation: flopOverAndOver 4s infinite linear !important;
		background: url("/randomFanart.php") !important;
		background-size: cover !important;
	}

</style>
<script>
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new fastFallingParticle(userChosenParticles=Array("&nbsp;"),userChosenColors=Array("white"),maxSpeed=3,minSpeed=2,maxSize=20,minSize=4,spinSpeed="flip");
}
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new flyingParticle(userChosenParticles=Array("&nbsp;"),userChosenColors=Array("white"),maxSpeed=3,minSpeed=2,maxSize=20,minSize=4,spinSpeed="flop");
}
for(var index=0;index<Math.floor(window.innerWidth/128);index++){
	new floatingParticle(userChosenParticles=Array("&nbsp;"),userChosenColors=Array("white"),maxSpeed=3,minSpeed=2,maxSize=20,minSize=4,spinSpeed="flip");
}


</script>
