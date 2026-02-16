<?PHP
########################################################################
# 2web fish effect
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
	@keyframes snowflake_left{
		0% {
			rotate: 0deg;
		}
		100% {
			rotate: -360deg;
		}
	}
	@keyframes snowflake_right{
		0% {
			rotate: 0deg;
		}
		100% {
			rotate: 360deg;
		}
	}
	@keyframes grow{
		0% {
			scale: 1;
		}
		100% {
			scale: 2;
		}
	}
	@keyframes rise{
		0% {
			top: 110%;
		}
		100% {
			top: -10%;
		}
	}
	.snowflake_right{
		animation: snowflake_right 10s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		color: white;
		user-select: none;
		font-family: monospace;
		emoji-variant-emoji: unicode;
	}
	.snowflake_left{
		animation: snowflake_left 10s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		color: white;
		user-select: none;
		font-family: monospace;
		emoji-variant-emoji: unicode;
	}
	.fish_right{
		scale: -1 1;
	}
	.fish_left{
	}
</style>
<script>
	var globalSnowflakes=0;
	class snowflake{
		constructor(){
			this.speed=( Math.floor(Math.random() * 4) + 2 );
			this.size=( Math.floor(Math.random() * 5) );
			// wait a random time before building the  snowflake
			this.snowFlakeDiv = document.createElement("div");
			this.snowFlakeDiv.id="snowflake_"+globalSnowflakes;
			this.globalID=this.snowFlakeDiv.id;
			//this.snowFlakeDiv.className="spinRight";
			if(1 == Math.floor(Math.random() * 2) ){
				this.snowFlakeDiv.className="snowflake_left";
			}else{
				this.snowFlakeDiv.className="snowflake_right";
			}
			// create a random snowflake
			this.snowFlakeDiv.innerHTML="🫧";
			this.snowFlakeDiv.style.zIndex="-1";
			this.snowFlakeDiv.style.width=this.size+"rem";
			this.snowFlakeDiv.style.height=this.size+"rem";
			this.snowFlakeDiv.style.fontSize=this.size+"rem";
			this.snowFlakeDiv.style.textAlign="center";
			this.snowFlakeDiv.style.transform = "blur("+Math.floor(Math.random * 10)+"px);";
			this.snowFlakeDiv.style.position="fixed";
			this.snowFlakeDiv.style.top = ( ( Math.random() * (window.innerHeight + 400) ) )+"px";
			this.snowFlakeDiv.style.left = ( Math.floor(Math.random() * window.innerWidth));
			// add the snowflake to the document
			document.body.appendChild(this.snowFlakeDiv);
			// increment the snowflake number
			globalSnowflakes+=1;
			setInterval( () => {
				//
				var tempFlake=document.getElementById(this.globalID);
				// set the recuring loop to move the snow flake
				tempFlake.style.top = (parseInt(tempFlake.style.top) - (this.speed) ) + "px";
				tempFlake.style.scale = (parseInt(tempFlake.style.scale) + 1) + "px";
				if ( ( parseInt(tempFlake.style.top) < -100 ) ){
					this.speed=( Math.floor(Math.random() * 4) + 2 );
					// randomize the size of the flake to create distance
					this.size=( Math.floor(Math.random() * 5) );
					tempFlake.style.width=this.size+"rem";
					tempFlake.style.height=this.size+"rem";
					// randomize the spin direction
					if(1 == Math.floor(Math.random() * 2) ){
						tempFlake.className="snowflake_left";
					}else{
						tempFlake.className="snowflake_right";
					}
					tempFlake.innerHTML="🫧";
					// move the snow flake back below the bottom
					tempFlake.style.top = (window.innerHeight + ( (Math.random() * 400) + 100 ) )+"px";
					// set a random opacity
					//tempFlake.style.opacity = "0."+(Math.floor(Math.random() * 9));
					// set a randomized blur
					tempFlake.style.transform = "blur("+Math.floor(Math.random * 99)+"px);";
					// give the flake a random location
					tempFlake.style.left = ( Math.floor(Math.random() * window.innerWidth) );
					// reset the animations for the snowflake
					//tempFlake.getAnimations().forEach((animation) => {
					//	animation.cancel();
					//	animation.play();
					//});
				}
			}, 33);
		}
	}
	for(var index=0;index<Math.floor(window.innerWidth/12);index++){
		new snowflake();
	}
	// create a random fish
	function pickAfish(){
		// return a random fish string
		var chosenFish;
		chosenFish=Math.floor(Math.random() * 50);
		if( 1 == chosenFish ){
			return "🐠";
		}else if( 2 == chosenFish ){
			return "🐠";
		}else if( 3 == chosenFish ){
			return "🐠";
		}else if( 4 == chosenFish ){
			return "🐠";
		}else if( 5 == chosenFish ){
			return "🐠";
		}else if( 6 == chosenFish ){
			return "🐠";
		}else if( 7 == chosenFish ){
			return "🐠";
		}else if( 8 == chosenFish ){
			return "🦐";
		}else if( 9 == chosenFish ){
			return "🦐";
		}else if( 10 == chosenFish ){
			return "🦀";
		}else if( 11 == chosenFish ){
			return "🪼";
		}else if( 12 == chosenFish ){
			return "🐙";
		}else if( 13 == chosenFish ){
			return "🐡";
		}else{
			return "🐟";
		}
	}
	// fish
	var globalfishs=0;
	class fish{
		constructor(){
			this.speed=( Math.floor(Math.random() * 7) + 4 );
			this.size=( Math.floor(Math.random() * 6) );
			// wait a random time before building the  fish
			this.fishDiv = document.createElement("div");
			this.fishDiv.id="fish_"+globalfishs;
			this.globalID=this.fishDiv.id;
			this.chosenFishDirection=Math.floor(Math.random() * 2);
			if( 0 == this.chosenFishDirection ){
				this.fishDiv.className="fish_left";
			}else if( 1 == this.chosenFishDirection ){
				this.fishDiv.className="fish_right";
			}
			// create a random fish
			this.fishDiv.innerHTML=pickAfish();
			// set the default values
			this.fishDiv.style.zIndex="-1";
			this.fishDiv.style.width=this.size+"rem";
			this.fishDiv.style.height=this.size+"rem";
			//this.fishDiv.style.transform="rotate(0deg)";
			this.fishDiv.style.fontSize=this.size+"rem";
			this.fishDiv.style.textAlign="center";
			this.fishDiv.style.transform = "blur("+Math.floor(Math.random * 3)+"px);";
			this.fishDiv.style.position="fixed";
			this.fishDiv.style.left = ( ( Math.random() * (window.innerWidth + 400) ) - 400 )+"px";
			this.fishDiv.style.top = ( Math.floor(Math.random() * window.innerHeight) );
			// randomize the base fish color
			//if(this.fishDiv.innerHTML == "🐟"){
			//	this.fishDiv.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
			//}else{
			//	this.fishDiv.style.filter="";
			//}
			// add the fish to the document
			document.body.appendChild(this.fishDiv);
			// increment the fish number
			globalfishs+=1;
			setInterval( () => {
				//
				var tempFish=document.getElementById(this.globalID);
				// set the recuring loop to move the snow flake
				if ( tempFish.className == "fish_left" ){
					tempFish.style.left = (parseInt(tempFish.style.left) - (this.speed)) + "px";
				}else if ( tempFish.className == "fish_right" ){
					tempFish.style.left = (parseInt(tempFish.style.left) + (this.speed)) + "px";
				}else{
					console.log("Could not find fish move direction");
				}
				//console.log("fish top",tempFish.style.top);
				//console.log("fish left",tempFish.style.left);
				var resetFish=false;
				// the reset is dependent on the direction the fish is moving
				if ( (tempFish.className == "fish_left") && (parseInt(tempFish.style.left) < -100) ){
					resetFish=true;
				}else if ( (tempFish.className == "fish_right") && (parseInt(tempFish.style.left) > (window.innerWidth +100) ) ){
					resetFish=true;
				}
				if ( resetFish ){
					this.speed=( Math.floor(Math.random() * 7) + 4 );
					// randomize the size of the flake to create distance
					this.size=( Math.floor(Math.random() * 3) );
					tempFish.style.width=this.size+"rem";
					tempFish.style.height=this.size+"rem";
					// class
					this.chosenFishDirection=Math.floor(Math.random() * 2);
					if( 0 == this.chosenFishDirection ){
						tempFish.className="fish_left";
					}else if( 1 == this.chosenFishDirection ){
						tempFish.className="fish_right";
					}else{
						console.log("Broken fish direction");
					}
					// create a random fish
					tempFish.innerHTML=pickAfish();
					// randomize the base fish color
					//if(tempFlake.innerHTML == "🐟"){
					//	tempFlake.style.filter="hue-rotate("+(Math.floor(Math.random() * 360))+"deg)";
					//}else{
					//	tempFlake.style.filter="";
					//}
					if ( tempFish.className == "fish_left" ){
						// fish move left, move them past the right side of the screen to start
						tempFish.style.left = (window.innerWidth + ( (Math.random() * 400) + 100 ) )+"px";
					}else if ( tempFish.className == "fish_right" ){
						// fish move right, move them past the left side of the screen
						tempFish.style.left = (-1 * ( (Math.random() * 400) + 100 ) )+"px";
					}else{
						console.log("Broken fish class name");
					}
					// set a randomized blur
					tempFish.style.transform = "blur("+Math.floor(Math.random * 99)+"px);";
					// give the fish a random location
					tempFish.style.top = ( Math.floor(Math.random() * window.innerHeight ) );
				}
			}, 33);
		}
	}
	for(var index=0;index<Math.floor(window.innerWidth/6);index++){
		new fish();
	}

</script>
