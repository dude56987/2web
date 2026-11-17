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
<style>
	.particle{
		width: 20rem !important;
		height: 1rem !important;
		font-weight: bold;
	}
</style>
<script>
<?PHP
	# get a list of random words and add them to the array
	echo "var particleValues=Array(";
	foreach(range(0,10) as $index){
		echo "\"".randomWord()."\",";
	}
	echo ");";
?>
// setup the particles, duplicates increase the probablity of particle being used
//var particleValues = Array("words");
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/32);index++){
	new flyingParticle(userChosenParticles=particleValues,userChosenColors=Array("white"),maxSpeed=20,minSpeed=10,maxSize=4,minSize=2,spinSpeed="slow",false);
}
// create the default amount of particles
for(var index=0;index<Math.floor(window.innerWidth/32);index++){
	new flyingParticle(userChosenParticles=particleValues,userChosenColors=Array("white"),maxSpeed=10,minSpeed=7,maxSize=4,minSize=2,spinSpeed="slow",false);
}

</script>
