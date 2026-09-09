<?PHP
########################################################################
# 2web base particle system
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
?>
<style>
	@keyframes particle_thrash_left{
		0% {
			rotate: 1.8deg;
		}
		25% {
			rotate: 0deg;
		}
		50% {
			rotate: -1.8deg;
		}
		75% {
			rotate: 0deg;
		}
		100% {
			rotate: 1.8deg;
		}
	}
	@keyframes particle_thrash_right{
		0% {
			rotate: -1.8deg;
		}
		25% {
			rotate: 0deg;
		}
		50% {
			rotate: 1.8deg;
		}
		75% {
			rotate: 0deg;
		}
		100% {
			rotate: -1.8deg;
		}
	}
	@keyframes particle_wiggle_left{
		0% {
			rotate: 0.8deg;
		}
		25% {
			rotate: 0deg;
		}
		50% {
			rotate: -0.8deg;
		}
		75% {
			rotate: 0deg;
		}
		100% {
			rotate: 0.8deg;
		}
	}
	@keyframes particle_wiggle_right{
		0% {
			rotate: -0.8deg;
		}
		25% {
			rotate: 0deg;
		}
		50% {
			rotate: 0.8deg;
		}
		75% {
			rotate: 0deg;
		}
		100% {
			rotate: -0.8deg;
		}
	}
	@keyframes particle_spin_left{
		0% {
			rotate: 0deg;
		}
		100% {
			rotate: -360deg;
		}
	}
	@keyframes particle_spin_right{
		0% {
			rotate: 0deg;
		}
		100% {
			rotate: 360deg;
		}
	}
	@keyframes particle_sway_left{
		0% {
			rotate: 0deg;
			transform: translateX(0rem);
		}
		50% {
			rotate: -1deg;
			transform: translateX(-9rem);
		}
		100% {
			rotate: 0deg;
			transform: translateX(0rem);
		}
	}
	@keyframes particle_sway_right{
		0% {
			rotate: 0deg;
			transform: translateX(0rem);
		}
		50% {
			rotate: 1deg;
			transform: translateX(9rem);
		}
		100% {
			rotate: 0deg;
			transform: translateX(0rem);
		}
	}
	@keyframes particle_blink{
		0% {
			scale: 1 1;
		}
		2% {
			scale: 1 0;
		}
		4% {
			scale: 1 1;
		}
		100% {
			scale: 1 1;
		}
	}
	@keyframes particle_flux{
		0% {
			filter: hue-rotate(0deg);
			-webkit-filter:hue-rotate(0deg);
		}
		25% {
			filter: hue-rotate(90deg);
			-webkit-filter:hue-rotate(90deg);
		}
		50% {
			filter: hue-rotate(180deg);
			-webkit-filter:hue-rotate(180deg);
		}
		75% {
			filter: hue-rotate(270deg);
			-webkit-filter:hue-rotate(270deg);
		}
		100% {
			filter: hue-rotate(360deg);
			-webkit-filter:hue-rotate(360deg);
		}
	}
	@keyframes particle_shimmer{
		0% {
			filter: brightness(1);
		}
		50% {
			filter: brightness(1.2);
		}
		100% {
			filter: brightness(1);
		}
	}
	.particle_spin_left_shimmer{
		animation-name: particle_shimmer;
		animation-duration: 8s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: ease-in-out;
		user-select: none;
	}
	.particle_spin_right_shimmer{
		scale: -1 1;
		animation-name: particle_shimmer;
		animation-duration: 8s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: ease-in-out;
		user-select: none;
	}
	.particle_spin_right_blink{
		scale: -1 1;
		animation-name: particle_blink;
		animation-duration: 8s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
	}
	.particle_spin_left_blink{
		scale: -1 1;
		animation-name: particle_blink;
		animation-duration: 8s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
	}
	.particle_spin_right_flux{
		scale: -1 1;
		animation-name: particle_flux;
		animation-duration: 8s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
	}
	.particle_spin_left_flux{
		animation-name: particle_flux;
		animation-duration: 8s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
	}
	.particle_spin_right_sway{
		scale: -1 1;
		animation-name: particle_sway_right;
		animation-duration: 15s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: ease-in-out;
		user-select: none;
	}
	.particle_spin_left_sway{
		scale: 1 1;
		animation-name: particle_sway_left;
		animation-duration: 15s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: ease-in-out;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_right_fast{
		scale: -1 1;
		animation-name: particle_spin_right;
		animation-duration: 2s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_left_fast{
		animation-name: particle_spin_left;
		animation-duration: 2s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_right_superfast{
		scale: -1 1;
		animation-name: particle_spin_right;
		animation-duration: 0.5s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_left_superfast{
		animation-name: particle_spin_left;
		animation-duration: 0.5s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_right_normal{
		scale: -1 1;
		animation-name: particle_spin_right;
		animation-duration: 5s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_left_normal{
		animation-name: particle_spin_left;
		animation-duration: 5s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_right_slow{
		scale: -1 1;
		animation-name: particle_spin_right;
		animation-duration: 10s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_left_slow{
		animation-name: particle_spin_left;
		animation-duration: 10s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_right_wiggle{
		scale: -1 1;
		animation-name: particle_wiggle_right;
		animation-duration: 4s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_left_wiggle{
		animation-name: particle_wiggle_left;
		animation-duration: 4s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_right_thrash{
		scale: -1 1;
		animation-name: particle_thrash_right;
		animation-duration: 2s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	.particle_spin_left_thrash{
		animation-name: particle_thrash_left;
		animation-duration: 2s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		user-select: none;
		font-family: font2webGlyph;
	}
	/* Add variablity to animation start position */
	.particle:nth-of-type(30n){
		animation-delay: -30s !important;
	}
	.particle:nth-of-type(30n-29){
		animation-delay: -29s !important;
	}
	.particle:nth-of-type(30n-28){
		animation-delay: -28s !important;
	}
	.particle:nth-of-type(30n-27){
		animation-delay: -27s !important;
	}
	.particle:nth-of-type(30n-26){
		animation-delay: -26s !important;
	}
	.particle:nth-of-type(30n-25){
		animation-delay: -25s !important;
	}
	.particle:nth-of-type(30n-24){
		animation-delay: -24s !important;
	}
	.particle:nth-of-type(30n-23){
		animation-delay: -23s !important;
	}
	.particle:nth-of-type(30n-22){
		animation-delay: -22s !important;
	}
	.particle:nth-of-type(30n-21){
		animation-delay: -21s !important;
	}
	.particle:nth-of-type(30n-20){
		animation-delay: -20s !important;
	}
	.particle:nth-of-type(30n-19){
		animation-delay: -19s !important;
	}
	.particle:nth-of-type(30n-18){
		animation-delay: -18s !important;
	}
	.particle:nth-of-type(30n-17){
		animation-delay: -17s !important;
	}
	.particle:nth-of-type(30n-16){
		animation-delay: -16s !important;
	}
	.particle:nth-of-type(30n-15){
		animation-delay: -15s !important;
	}
	.particle:nth-of-type(30n-14){
		animation-delay: -14s !important;
	}
	.particle:nth-of-type(30n-13){
		animation-delay: -13s !important;
	}
	.particle:nth-of-type(30n-12){
		animation-delay: -12s !important;
	}
	.particle:nth-of-type(30n-11){
		animation-delay: -11s !important;
	}
	.particle:nth-of-type(30n-10){
		animation-delay: -10s !important;
	}
	.particle:nth-of-type(30n-9){
		animation-delay: -9s !important;
	}
	.particle:nth-of-type(30n-8){
		animation-delay: -8s !important;
	}
	.particle:nth-of-type(30n-7){
		animation-delay: -7s !important;
	}
	.particle:nth-of-type(30n-6){
		animation-delay: -6s !important;
	}
	.particle:nth-of-type(30n-5){
		animation-delay: -5s !important;
	}
	.particle:nth-of-type(30n-4){
		animation-delay: -4s !important;
	}
	.particle:nth-of-type(30n-3){
		animation-delay: -3s !important;
	}
	.particle:nth-of-type(30n-2){
		animation-delay: -2s !important;
	}
	.particle:nth-of-type(30n-1){
		animation-delay: -1s !important;
	}
	.particle{
		z-index: -1;
	}
</style>
<script>
var globalParticleCount=0;
var removalActive="";
function particleStatus(){
	// Check the status of the particle system
	console.log("The Particle system is running with '"+globalParticleCount+"' active particles.")
}
// random color
function randomColor(){
	var outputColor="";
	// include the conversion array
	var conversionTable=Array("1","2","3","4","5","6","7","8","9","A","B","C","D","E","F");
	var tempColorCode=0;
	for(index=0;index<6;index++){
		// 0 -> 15 (base 16)
		tempColorCode=Math.floor(Math.random() * 15);
		// convert the color code into HEX values
		tempColorCode=conversionTable[tempColorCode];
		// add the new value to the hex output
		outputColor = outputColor + tempColorCode;
	}
	// add the hash mark to make a valid html color
	return ("#" + outputColor);
}
function randomSimpleColor(paintColors=Array("red","yellow","green","blue")){
	// red orange yellow green blue indigo violet
	// - red,yellow,green,blue looks better than all the colors of the rainbow
	var pickedColor=Math.floor(Math.random() * paintColors.length);
	return paintColors[pickedColor];
}
function randomParticle(particles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾")){
	// pick a random element from an array of strings for use as a particle in the particles system
	var pickedParticle=Math.floor(Math.random() * particles.length);
	return particles[pickedParticle];
}
function randomWeight(particles=Array("bold","bolder","normal","lighter")){
	// randomize the weight to generate variety in shapes
	var pickedParticle=Math.floor(Math.random() * particles.length);
	return particles[pickedParticle];
}
function getRandomDirectionModifier(){
	// create a random direction modifier
	var tempDirectionMod;
	tempDirectionMod=Math.floor(Math.random() * 3);
	if(1 == tempDirectionMod ){
		return 1;
	}else if(tempDirectionMod == 2 ){
		return -1;
	}else{
		return 0;
	}
}
////////////////////////////////////////////////////////////////////////////////
// start the particle constructors and classes
////////////////////////////////////////////////////////////////////////////////
class floatingParticle{
	constructor(userChosenParticles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾"),userChosenColors=Array("red","green","blue","yellow"),maxSpeed=9,minSpeed=7,maxSize=3,minSize=1,spinSpeed="fast",colorFlux=false,distanceLimit=-1){
		this.distanceLimit=distanceLimit;
		this.colorFlux=colorFlux;
		this.spinSpeed=spinSpeed;
		this.chosenParticles=userChosenParticles;
		this.maxSize=maxSize;
		this.minSize=minSize;
		this.maxSpeed=maxSpeed;
		this.minSpeed=minSpeed;
		this.speed=( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed );
		this.size=( Math.floor(Math.random() * this.maxSize) + this.minSize );
		this.particleDiv = document.createElement("div");
		this.particleDiv.id="particle_"+globalParticleCount;
		this.globalID=this.particleDiv.id;
		// randomize the spin direction
		if(1 == Math.floor(Math.random() * 2) ){
			this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
		}else{
			this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
		}
		// create a random particle
		this.particleDiv.innerHTML=randomParticle(userChosenParticles);
		//this.particleDiv.style.zIndex="-1";
		//this.particleDiv.style.zIndex=((-(this.maxSize-this.size))-1);
		if(this.colorFlux){
			this.particleDiv.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
		}
		this.particleDiv.style.color=self.randomSimpleColor(userChosenColors);
		this.particleDiv.style.width=this.size+"rem";
		this.particleDiv.style.height=this.size+"rem";
		//this.particleDiv.style.transform="rotate(0deg)";
		this.particleDiv.style.fontSize=this.size+"rem";
		this.particleDiv.style.lineHeight=this.size+"rem";
		this.particleDiv.style.textAlign="center";
		//this.particleDiv.style.opacity = "0."+(Math.floor(Math.random() * 9));
		//this.particleDiv.style.transform = "blur("+Math.floor(Math.random * 10)+"px);";
		this.particleDiv.style.position="fixed";
		//
		this.resetParticle=false;
		//
		if( this.distanceLimit > -1 ){
			this.currentDistance=(Math.random() * this.distanceLimit);
			this.particleDiv.style.top = ( ( window.innerHeight + 100 + ( Math.random() * 100 ) ) + "px" );
		}else{
			this.particleDiv.style.top = ( ( Math.random() * window.innerHeight + 100 + ( Math.random() * 100 ) ) )+"px";
		}
		this.particleDiv.style.left = ( Math.floor(Math.random() * window.innerWidth));
		// add the particle to the document
		document.body.appendChild(this.particleDiv);
		// increment the particle number
		globalParticleCount+=1;
		setInterval( () => {
			//
			var tempParticle=document.getElementById(this.globalID);
			// set the recuring loop to move the particle
			tempParticle.style.top = (parseInt(tempParticle.style.top) - (this.speed)) + "px";
			// check limits
			if( this.distanceLimit > -1 ){
				this.currentDistance+=1;
				//
				if ( ( this.currentDistance > this.distanceLimit ) ){
					this.resetParticle=true;
				}
			}
			if ( (parseInt(tempParticle.style.top) < (-100)) ){
				this.resetParticle=true;
			}
			if( this.resetParticle == true ){
				this.currentDistance=0;
				this.resetParticle=false;
				//
				this.resetParticle = false;
				// randomize the size of the particle to create distance
				this.speed=( Math.floor(Math.random() * this.maxSpeed ) + this.minSpeed );
				this.size=( Math.floor(Math.random() * this.maxSize ) + this.minSize );
				tempParticle.style.color=randomSimpleColor(userChosenColors);
				tempParticle.style.width=this.size+"rem";
				//tempParticle.style.zIndex="-1";
				//tempParticle.style.zIndex=((-(this.maxSize-this.size))-1);
				if(this.colorFlux){
					tempParticle.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
				}
				tempParticle.style.height=this.size+"rem";
				tempParticle.style.lineHeight=this.size+"rem";
				// randomize the spin direction
				if(1 == Math.floor(Math.random() * 2) ){
					tempParticle.className="particle particle_spin_left_"+this.spinSpeed;
				}else{
					tempParticle.className="particle particle_spin_right_"+this.spinSpeed;
				}
				// create a random particle
				tempParticle.innerHTML=randomParticle(userChosenParticles);
				// move the particle back below the bottom
				tempParticle.style.top = ( ( window.innerHeight) + 50 + (Math.random() * 100) )+"px";
				// give the particle a random location
				tempParticle.style.left = ( Math.floor(Math.random() * window.innerWidth) );
			}
		// 30 fps (Movie Framerate) is 33ms delay
		}, 33);
	}
}
////////////////////////////////////////////////////////////////////////////////
// Falling Particle
////////////////////////////////////////////////////////////////////////////////
class fastFallingParticle{
	constructor(userChosenParticles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾"),userChosenColors=Array("red","green","blue","yellow"),maxSpeed=9,minSpeed=7,maxSize=3,minSize=1,spinSpeed="fast",colorFlux=false){
		this.colorFlux=colorFlux;
		this.spinSpeed=spinSpeed;
		this.chosenParticles=userChosenParticles;
		this.maxSize=maxSize;
		this.minSize=minSize;
		this.maxSpeed=maxSpeed;
		this.minSpeed=minSpeed;
		this.speed=( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed );
		this.size=( Math.floor(Math.random() * this.maxSize) + this.minSize );
		this.particleDiv = document.createElement("div");
		this.particleDiv.id="particle_"+globalParticleCount;
		this.globalID=this.particleDiv.id;
		this.particleDiv.style.fontWeight=randomWeight();
		// randomize the spin direction
		if(1 == Math.floor(Math.random() * 2) ){
			this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
		}else{
			this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
		}
		// create a random particle
		this.particleDiv.innerHTML=randomParticle(userChosenParticles);
		//this.particleDiv.style.zIndex="-1";
		//this.particleDiv.style.zIndex=((-(this.maxSize-this.size))-1);
		if(this.colorFlux){
			this.particleDiv.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
		}
		this.particleDiv.style.color=self.randomSimpleColor(userChosenColors);
		this.particleDiv.style.width=this.size+"rem";
		this.particleDiv.style.height=this.size+"rem";
		//this.particleDiv.style.transform="rotate(0deg)";
		this.particleDiv.style.fontSize=this.size+"rem";
		this.particleDiv.style.lineHeight=this.size+"rem";
		this.particleDiv.style.textAlign="center";
		//this.particleDiv.style.opacity = "0."+(Math.floor(Math.random() * 9));
		//this.particleDiv.style.transform = "blur("+Math.floor(Math.random * 10)+"px);";
		this.particleDiv.style.position="fixed";
		this.particleDiv.style.top = ( ( Math.random() * (window.innerHeight + 400) ) - 400 )+"px";
		this.particleDiv.style.left = ( Math.floor(Math.random() * window.innerWidth));
		// add the particle to the document
		document.body.appendChild(this.particleDiv);
		// increment the particle number
		globalParticleCount+=1;
		setInterval( () => {
			//
			var tempParticle=document.getElementById(this.globalID);
			// set the recuring loop to move the particle
			tempParticle.style.top = (parseInt(tempParticle.style.top) + (this.speed)) + "px";
			if ( (parseInt(tempParticle.style.top) > (window.innerHeight+100)) ){
				// randomize the size of the particle to create distance
				this.speed=( Math.floor(Math.random() * this.maxSpeed ) + this.minSpeed );
				this.size=( Math.floor(Math.random() * this.maxSize ) + this.minSize );
				tempParticle.style.color=randomSimpleColor(userChosenColors);
				tempParticle.style.width=this.size+"rem";
				//tempParticle.style.zIndex="-1";
				tempParticle.style.fontWeight=randomWeight();
				//tempParticle.style.zIndex=((-(this.maxSize-this.size))-1);
				if(this.colorFlux){
					tempParticle.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
				}
				tempParticle.style.height=this.size+"rem";
				tempParticle.style.lineHeight=this.size+"rem";
				// randomize the spin direction
				if(1 == Math.floor(Math.random() * 2) ){
					tempParticle.className="particle particle_spin_left_"+this.spinSpeed;
				}else{
					tempParticle.className="particle particle_spin_right_"+this.spinSpeed;
				}
				// create a random particle
				tempParticle.innerHTML=randomParticle(userChosenParticles);
				// move the particle back above the top
				tempParticle.style.top = (-1 * ( (Math.random() * 400) + 100 ) )+"px";
				// give the particle a random location
				tempParticle.style.left = ( Math.floor(Math.random() * window.innerWidth) );
			}
		// 30 fps (Movie Framerate) is 33ms delay
		}, 33);
	}
}

// start the particle constructors and classes
class flyingParticle{
	// a particle that flys across the screen from left to right or right to left
	constructor(userChosenParticles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾"),userChosenColors=Array("red","green","blue","yellow"),maxSpeed=9,minSpeed=7,maxSize=3,minSize=1,spinSpeed="none",colorFlux=false,flipParticle=false,lockDirection=false){
		this.colorFlux=colorFlux;
		this.spinSpeed=spinSpeed;
		this.chosenParticles=userChosenParticles;
		// randomize the left to right or right to left direction
		if(1 == Math.floor(Math.random() * 2) ){
			this.flyDirection="left";
		}else{
			this.flyDirection="right";
		}
		// set the particle size limits
		this.maxSize=maxSize;
		this.minSize=minSize;
		// set the particle speed limits
		this.maxSpeed=maxSpeed;
		this.minSpeed=minSpeed;
		// set the speed and size based on limits
		this.speed=( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed );
		this.size=( Math.floor(Math.random() * this.maxSize) + this.minSize );
		// create the HTML element for the particle
		this.particleDiv = document.createElement("div");
		this.particleDiv.id="particle_"+globalParticleCount;
		this.globalID=this.particleDiv.id;
		// randomize the spin direction
		if( lockDirection ){
			if( this.flyDirection == "right" ){
				this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
			}else{
				this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
			}
		}else{
			if(1 == Math.floor(Math.random() * 2) ){
				this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
			}else{
				this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
			}
		}
		// create a random particle
		this.particleDiv.innerHTML=randomParticle(userChosenParticles);
		//this.particleDiv.style.zIndex="-1";
		//this.particleDiv.style.zIndex=((-(this.maxSize-this.size))-1);
		if(this.colorFlux){
			this.particleDiv.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
		}
		// set the position
		if(this.flyDirection=="left"){
			this.particleDiv.style.left = (Math.floor(window.innerWidth * Math.random()))+"px";
			if(flipParticle == true){
				this.particleDiv.style.scale="-1 1";
			}else{
				this.particleDiv.style.scale="1 1";
			}
		}else{
			this.particleDiv.style.left = (Math.floor(Math.random() * window.innerWidth))+"px";
			if(flipParticle == true){
				this.particleDiv.style.scale="1 1";
			}else{
				this.particleDiv.style.scale="-1 1";
			}
		}
		//
		this.particleDiv.style.color=self.randomSimpleColor(userChosenColors);
		this.particleDiv.style.width=this.size+"rem";
		this.particleDiv.style.height=this.size+"rem";
		this.particleDiv.style.fontSize=this.size+"rem";
		this.particleDiv.style.lineHeight=this.size+"rem";
		this.particleDiv.style.textAlign="center";
		//this.particleDiv.style.opacity = "0."+(Math.floor(Math.random() * 9));
		//this.particleDiv.style.transform = "blur("+Math.floor(Math.random * 10)+"px);";
		this.particleDiv.style.position="fixed";
		// randomize the starting position
		this.particleDiv.style.top = ( Math.floor(Math.random() * window.innerHeight));
		// add the particle to the document
		document.body.appendChild(this.particleDiv);
		// increment the particle number
		globalParticleCount+=1;
		setInterval( () => {
			// get the particle based on the global id
			var tempParticle=document.getElementById(this.globalID);
			// set the recuring loop to move the particle
			if(this.flyDirection=="left"){
				//console.log("Moving particle LEFT");
				tempParticle.style.left = (parseInt(tempParticle.style.left) - (this.speed)) + "px";
			}else{
				//console.log("Moving particle RIGHT");
				tempParticle.style.left = (parseInt(tempParticle.style.left) + (this.speed)) + "px";
			}
			if ( (parseInt(tempParticle.style.left) > (window.innerWidth + 100)) || (parseInt(tempParticle.style.left) < -100 ) ){
				// randomize the size of the particle to create distance
				this.speed=( Math.floor(Math.random() * this.maxSpeed ) + this.minSpeed );
				this.size=( Math.floor(Math.random() * this.maxSize ) + this.minSize );
				tempParticle.style.color=randomSimpleColor(userChosenColors);
				tempParticle.style.width=this.size+"rem";
				//tempParticle.style.zIndex="-1";
				//
				tempParticle.style.height=this.size+"rem";
				tempParticle.style.lineHeight=this.size+"rem";
				// randomize the left to right or right to left direction
				if(1 == Math.floor(Math.random() * 2) ){
					this.flyDirection="left";
				}else{
					this.flyDirection="right";
				}
				if(this.colorFlux){
					tempParticle.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
				}
				// randomize the spin direction
				if( lockDirection ){
					if( this.flyDirection == "right" ){
						tempParticle.className="particle particle_spin_right_"+this.spinSpeed;
					}else{
						tempParticle.className="particle particle_spin_left_"+this.spinSpeed;
					}
				}else{
					if(1 == Math.floor(Math.random() * 2) ){
						tempParticle.className="particle particle_spin_left_"+this.spinSpeed;
					}else{
						tempParticle.className="particle particle_spin_right_"+this.spinSpeed;
					}
				}
				// set a random starting position
				tempParticle.style.top = ( Math.floor(Math.random() * window.innerHeight));
				// create a random particle
				tempParticle.innerHTML=randomParticle(userChosenParticles);
				// give the particle a random offscreen location based on its movement direction
				if(this.flyDirection=="left"){
					//console.log("flyDirection set to LEFT");
					tempParticle.style.left = ( Math.floor( Math.random() * 100) + window.innerWidth + 100 );
					if(flipParticle == true){
						this.particleDiv.style.scale="-1 1";
					}else{
						this.particleDiv.style.scale="1 1";
					}
				}else{
					//console.log("flyDirection set to RIGHT");
					tempParticle.style.left = ( -1 * ( Math.floor( Math.random() * 100) + 100 ) );
					if(flipParticle == true){
						this.particleDiv.style.scale="1 1";
					}else{
						this.particleDiv.style.scale="-1 1";
					}
				}
			}
		// 30 fps (Movie Framerate) is 33ms delay
		}, 33);
	}
}
// start the particle constructors and classes
class staticParticle{
	// a particle that flys across the screen from left to right or right to left
	constructor(userChosenParticles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾"),userChosenColors=Array("red","green","blue","yellow"),maxSpeed=9,minSpeed=7,maxSize=3,minSize=1,spinSpeed="none",colorFlux=false,flipParticle=false,lockDirection=false){
		this.colorFlux=colorFlux;
		this.spinSpeed=spinSpeed;
		this.chosenParticles=userChosenParticles;
		// randomize the left to right or right to left direction
		if(1 == Math.floor(Math.random() * 2) ){
			this.flyDirection="left";
		}else{
			this.flyDirection="right";
		}
		// set the particle size limits
		this.maxSize=maxSize;
		this.minSize=minSize;
		// set the particle speed limits
		this.maxSpeed=maxSpeed;
		this.minSpeed=minSpeed;
		// set the speed and size based on limits
		this.speed=( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed );
		this.size=( Math.floor(Math.random() * this.maxSize) + this.minSize );
		// create the HTML element for the particle
		this.particleDiv = document.createElement("div");
		this.particleDiv.id="particle_"+globalParticleCount;
		this.globalID=this.particleDiv.id;
		// randomize the spin direction
		if( lockDirection ){
			if( this.flyDirection == "right" ){
				this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
			}else{
				this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
			}
		}else{
			if(1 == Math.floor(Math.random() * 2) ){
				this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
			}else{
				this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
			}
		}
		// create a random particle
		this.particleDiv.innerHTML=randomParticle(userChosenParticles);
		//this.particleDiv.style.zIndex="-1";
		// randomize the animation loop starting position when creating the particle
		//this.particleDiv.style.animationDelay=( -1 * ( Math.floor(Math.random() * parallelAnimations) ) );
		// randomize the weight
		this.particleDiv.style.fontWeight=randomWeight();
		//this.particleDiv.style.zIndex=((-(this.maxSize-this.size))-1);
		if(this.colorFlux){
			this.particleDiv.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
		}
		// set the position
		if(this.flyDirection=="left"){
			this.particleDiv.style.left = (Math.floor(window.innerWidth * Math.random()))+"px";
			if(flipParticle == true){
				this.particleDiv.style.scale="-1 1";
			}else{
				this.particleDiv.style.scale="1 1";
			}
		}else{
			this.particleDiv.style.left = (Math.floor(Math.random() * window.innerWidth))+"px";
			if(flipParticle == true){
				this.particleDiv.style.scale="1 1";
			}else{
				this.particleDiv.style.scale="-1 1";
			}
		}
		//
		this.particleDiv.style.color=self.randomSimpleColor(userChosenColors);
		this.particleDiv.style.width=this.size+"rem";
		this.particleDiv.style.height=this.size+"rem";
		this.particleDiv.style.fontSize=this.size+"rem";
		this.particleDiv.style.lineHeight=this.size+"rem";
		this.particleDiv.style.textAlign="center";
		//this.particleDiv.style.opacity = "0."+(Math.floor(Math.random() * 9));
		//this.particleDiv.style.transform = "blur("+Math.floor(Math.random * 10)+"px);";
		this.particleDiv.style.position="fixed";
		// randomize the starting position
		this.particleDiv.style.top = ( Math.floor(Math.random() * window.innerHeight));
		// add the particle to the document
		document.body.appendChild(this.particleDiv);
		// increment the particle number
		globalParticleCount+=1;
		this.maxFailures = 1;
		this.removeTrigger=false;
		this.failures = 0;
		this.lastTime = Date.now();
		this.currentTime = Date.now();
	}
}
// start the particle constructors and classes
class lightParticle{
	// a particle that flys across the screen from left to right or right to left
	constructor(userChosenParticles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾"),userChosenColors=Array("red","green","blue","yellow"),maxSpeed=9,minSpeed=7,maxSize=3,minSize=1,spinSpeed="none",colorFlux=false,flipParticle=false,lockDirection=false){
		this.maxSize=maxSize;
		this.colorFlux=colorFlux;
		this.spinSpeed=spinSpeed;
		this.chosenParticles=userChosenParticles;
		// randomize the left to right or right to left direction
		if(1 == Math.floor(Math.random() * 2) ){
			this.flyDirection="left";
		}else{
			this.flyDirection="right";
		}
		// setup the direction modifier
		//
		// set a random direction modifier
		this.directionModifierX=getRandomDirectionModifier();
		this.directionModifierY=getRandomDirectionModifier();
		// prevent static objects
		while((this.directionModifierX==0) && (this.directionModifierY==0)){
			this.directionModifierX=getRandomDirectionModifier();
			this.directionModifierY=getRandomDirectionModifier();
		}
		// set the particle speeds
		this.maxSpeed=maxSpeed;
		this.minSpeed=minSpeed;
		//
		this.xSpeed=(( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed ) * this.directionModifierX);
		this.ySpeed=(( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed ) * this.directionModifierY);
		// set the speed and size based on limits
		this.speed=( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed );
		this.size=0;
		// create the HTML element for the particle
		this.particleDiv = document.createElement("div");
		this.particleDiv.id="particle_"+globalParticleCount;
		this.globalID=this.particleDiv.id;
		// randomize the spin direction
		if( lockDirection ){
			if( this.flyDirection == "right" ){
				this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
			}else{
				this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
			}
		}else{
			if(1 == Math.floor(Math.random() * 2) ){
				this.particleDiv.className="particle particle_spin_left_"+this.spinSpeed;
			}else{
				this.particleDiv.className="particle particle_spin_right_"+this.spinSpeed;
			}
		}
		// create a random particle
		this.particleDiv.innerHTML=randomParticle(userChosenParticles);
		//
		//this.particleDiv.style.zIndex="-1";
		if(this.colorFlux){
			this.particleDiv.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
		}
		// set the position
		if(this.flyDirection=="left"){
			this.particleDiv.style.left = (Math.floor(window.innerWidth * Math.random()))+"px";
			if(flipParticle == true){
				this.particleDiv.style.scale="-1 1";
			}else{
				this.particleDiv.style.scale="1 1";
			}
		}else{
			this.particleDiv.style.left = (Math.floor(Math.random() * window.innerWidth))+"px";
			if(flipParticle == true){
				this.particleDiv.style.scale="1 1";
			}else{
				this.particleDiv.style.scale="-1 1";
			}
		}
		//
		this.particleDiv.style.color=self.randomSimpleColor(userChosenColors);
		// start the particle in the center of the screen
		//
		this.particleDiv.style.top=(window.innerHeight/2)+"px";
		this.particleDiv.style.left=(window.innerWidth/2)+"px";
		//
		this.particleDiv.style.fontSize="0px";
		//this.particleDiv.style.lineHeight=this.size+"px";
		this.particleDiv.style.textAlign="center";
		//this.particleDiv.style.rotate= ( Math.floor(Math.random() * 360) );
		//this.particleDiv.style.transform = "translate(0px,0px)";
		this.particleDiv.style.position="fixed";
		// add the particle to the document
		document.body.appendChild(this.particleDiv);
		// increment the particle number
		globalParticleCount+=1;
		setInterval( () => {
			// get the particle based on the global id
			var tempParticle=document.getElementById(this.globalID);
			// move and scale the particle based on the size value
			this.size = (this.size + this.speed );
			// scale the size of the particle
			if(this.size>this.maxSize){
				this.size=this.maxSize;
			}
			//
			tempParticle.style.width=this.size+"px";
			tempParticle.style.height=this.size+"px";
			//tempParticle.style.lineHeight=this.size+"rem";
			tempParticle.style.fontSize= (parseInt(this.size)+"px");
			// set the recuring loop to move the particle
			tempParticle.style.left=((parseInt(tempParticle.style.left)+this.xSpeed));
			tempParticle.style.top=((parseInt(tempParticle.style.top)+this.ySpeed));
			// set the reset particle conditions
			if ( (parseInt(tempParticle.style.left) > (window.innerWidth+200)) ||
			(parseInt(tempParticle.style.left) < (-200)) ||
			(parseInt(tempParticle.style.top) > (window.innerHeight+200)) ||
			(parseInt(tempParticle.style.top) < (-200)) ){
				// reset the size of the particle
				tempParticle.style.color=randomSimpleColor(userChosenColors);
				//tempParticle.style.zIndex="-1";
				// reset the position to the center
				this.particleDiv.style.top=(window.innerHeight/2)+"px";
				this.particleDiv.style.left=(window.innerWidth/2)+"px";
				// reset the font size
				tempParticle.style.fontSize="0px";
				this.size=0;
				if(this.colorFlux){
					tempParticle.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
				}
				// set a random direction modifier
				this.directionModifierX=getRandomDirectionModifier();
				this.directionModifierY=getRandomDirectionModifier();
				// prevent static objects
				while((this.directionModifierX==0) && (this.directionModifierY==0)){
					this.directionModifierX=getRandomDirectionModifier();
					this.directionModifierY=getRandomDirectionModifier();
				}
				// set the direction movements speed
				this.xSpeed=(( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed ) * this.directionModifierX);
				this.ySpeed=(( Math.floor(Math.random() * this.maxSpeed) + this.minSpeed ) * this.directionModifierY);
				// randomize the spin direction
				if( lockDirection ){
					if( this.flyDirection == "right" ){
						tempParticle.className="particle particle_spin_right_"+this.spinSpeed;
					}else{
						tempParticle.className="particle particle_spin_left_"+this.spinSpeed;
					}
				}else{
					if(1 == Math.floor(Math.random() * 2) ){
						tempParticle.className="particle particle_spin_left_"+this.spinSpeed;
					}else{
						tempParticle.className="particle particle_spin_right_"+this.spinSpeed;
					}
				}
				// create a random particle
				tempParticle.innerHTML=randomParticle(userChosenParticles);
			}
		// 30 fps (Movie Framerate) is 33ms delay
		}, 33);
	}
}
console.log("Particle System Loaded...");
// create the default amount of particles
//for(var index=0;index<Math.floor(window.innerWidth/12);index++){
	//new fastFallingParticle(userChosenParticles=Array("▰","🞧","🞮","🞴","🞺","🞸","🞾"),userChosenColors=Array("red","green","blue","yellow"),maxSpeed=9,minSpeed=7,maxSize=3,minSize=1,spinSpeed="fast");
//}
</script>
