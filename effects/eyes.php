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
<style>
	/* rotate the element animation starting positions */
	/*
	*:nth-of-type(30n){
		animation-delay: -30s !important;
	}
	*:nth-of-type(30n-29){
		animation-delay: -29s !important;
	}
	*:nth-of-type(30n-28){
		animation-delay: -28s !important;
	}
	*:nth-of-type(30n-27){
		animation-delay: -27s !important;
	}
	*:nth-of-type(30n-26){
		animation-delay: -26s !important;
	}
	*:nth-of-type(30n-25){
		animation-delay: -25s !important;
	}
	*:nth-of-type(30n-24){
		animation-delay: -24s !important;
	}
	*:nth-of-type(30n-23){
		animation-delay: -23s !important;
	}
	*:nth-of-type(30n-22){
		animation-delay: -22s !important;
	}
	*:nth-of-type(30n-21){
		animation-delay: -21s !important;
	}
	*:nth-of-type(30n-20){
		animation-delay: -20s !important;
	}
	*:nth-of-type(30n-19){
		animation-delay: -19s !important;
	}
	*:nth-of-type(30n-18){
		animation-delay: -18s !important;
	}
	*:nth-of-type(30n-17){
		animation-delay: -17s !important;
	}
	*:nth-of-type(30n-16){
		animation-delay: -16s !important;
	}
	*:nth-of-type(30n-15){
		animation-delay: -15s !important;
	}
	*:nth-of-type(30n-14){
		animation-delay: -14s !important;
	}
	*:nth-of-type(30n-13){
		animation-delay: -13s !important;
	}
	*:nth-of-type(30n-12){
		animation-delay: -12s !important;
	}
	*:nth-of-type(30n-11){
		animation-delay: -11s !important;
	}
	*:nth-of-type(30n-10){
		animation-delay: -10s !important;
	}
	*:nth-of-type(30n-9){
		animation-delay: -9s !important;
	}
	*:nth-of-type(30n-8){
		animation-delay: -8s !important;
	}
	*:nth-of-type(30n-7){
		animation-delay: -7s !important;
	}
	*:nth-of-type(30n-6){
		animation-delay: -6s !important;
	}
	*:nth-of-type(30n-5){
		animation-delay: -5s !important;
	}
	*:nth-of-type(30n-4){
		animation-delay: -4s !important;
	}
	*:nth-of-type(30n-3){
		animation-delay: -3s !important;
	}
	*:nth-of-type(30n-2){
		animation-delay: -2s !important;
	}
	*:nth-of-type(30n-1){
		animation-delay: -1s !important;
	}
	*/
</style>
<script>
// setup the particles, duplicates increase the probablity of particle being used
var particleValues = Array("👁️");
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/12);index++){
	new staticParticle(userChosenParticles=particleValues,userChosenColors=Array("white"),maxSpeed=4,minSpeed=2,maxSize=6,minSize=2,spinSpeed="blink",colorFlux=true,flipParticle=false,lockDirection=false,animateColor=true,parallelAnimations=10000);
}
</script>
