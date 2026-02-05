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
?>
<style>
	@keyframes snowflake_left{
		0% {
			/*
			rotate: 0deg;
			*/
			transform: rotate(0deg) perspective(0);
		}
		100% {
			/*
			rotate: -360deg;
			*/
			transform: rotate(-360deg) perspective(0);
		}
	}
	@keyframes snowflake_right{
		0% {
			/*
			rotate: 0deg;
			*/
			transform: rotate(0deg) perspective(0);
		}
		100% {
			/*
			rotate: 360deg;
			*/
			transform: rotate(360deg) perspective(0);
		}
	}
	.snowflake_right{
		animation-name: snowflake_right;
		animation-duration: 20s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		color: white;
		user-select: none;
		font-family: font2webGlyph;
		emoji-variant-emoji: unicode;
		filter: grayscale(1);
		transform-origin: 50% 50%;
	}
	.snowflake_left{
		animation-name: snowflake_left;
		animation-duration: 20s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		color: white;
		user-select: none;
		font-family: font2webGlyph;
		emoji-variant-emoji: unicode;
		filter: grayscale(1);
		transform-origin: 50% 50%;
	}
</style>
<script>
	var globalSnowflakes=0;
	class snowflake{
		constructor(){
			//setTimeout( () => {
				this.speed=( Math.floor(Math.random() * 4) + 2 );
				this.size=( Math.floor(Math.random() * 7) + 4 );
				// wait a random time before building the  snowflake
				this.snowFlakeDiv = document.createElement("div");
				this.snowFlakeDiv.id="snowflake_"+globalSnowflakes;
				this.snowFlakeDiv.scale="0.5";
				this.globalID=this.snowFlakeDiv.id;
				//this.snowFlakeDiv.className="spinRight";
				if(1 == Math.floor(Math.random() * 2) ){
					this.snowFlakeDiv.className="snowflake_left";
				}else{
					this.snowFlakeDiv.className="snowflake_right";
				}
				// create a random snowflake
				this.snowFlakeDiv.innerHTML="⚙️";
				this.snowFlakeDiv.style.zIndex="-1";
				this.snowFlakeDiv.style.width=this.size+"rem";
				this.snowFlakeDiv.style.height=this.size+"rem";
				this.snowFlakeDiv.style.lineHeight=this.size+"rem";
				this.snowFlakeDiv.style.opacity = 1.0;
				//this.snowFlakeDiv.style.transform="rotate(0deg)";
				this.snowFlakeDiv.style.fontSize=this.size+"rem";
				this.snowFlakeDiv.style.textAlign="center";
				//this.snowFlakeDiv.style.opacity = "0."+(Math.floor(Math.random() * 9));
				this.snowFlakeDiv.style.transform = "blur("+Math.floor(Math.random * 10)+"px);";
				this.snowFlakeDiv.style.position="fixed";
				this.snowFlakeDiv.style.top = ( ( Math.random() * (window.innerHeight + 400) ) - 400 )+"px";
				this.snowFlakeDiv.style.left = ( Math.floor(Math.random() * window.innerWidth));
				// add the snowflake to the document
				document.body.appendChild(this.snowFlakeDiv);
				// get the snowflake div pointer after it has been added to the document
				this.snowFlakeDiv=document.getElementById(this.globalID);
				// increment the global snowflake number
				globalSnowflakes+=1;
				// setup the time check
				this.currentTime;
				this.lastTime = Date.now();
				// failures being random makes the particles disipate instead of vanish
				this.failures = 0;
				this.maxFailures = (Math.floor(Math.random() * 4)+1);
				this.removeTrigger=false;
				this.loopId = setInterval( () => {
					if (this.removeTrigger){
						if(this.snowFlakeDiv.style.opacity <= 0){
							// remove this object
							console.log("currentTime='"+this.currentTime+"'");
							console.log("this.lastTime='"+this.lastTime+"'");
							console.log("time diff ='"+(this.currentTime-this.lastTime)+"'");
							console.log("Removing particle '"+this.snowFlakeDiv.id+"'");
							console.log("globalsnowflakes = '"+globalSnowflakes+"'");
							this.snowFlakeDiv.remove();
							// stop the loop
							clearInterval(this.loopId);
							globalSnowflakes-=1;
						}else{
							// reduce the opacity
							this.snowFlakeDiv.style.opacity = (parseFloat(this.snowFlakeDiv.style.opacity) - 0.01);
						}
					}else{
						// get the current time
						this.currentTime = Date.now();
						// check the time diff and have a fault tollerance for animation delays
						if ( (this.currentTime - this.lastTime) > (33 * 2) ){
							this.failures+=1;
							console.log("failure = "+this.failures+" of "+this.maxFailures);
							if (this.failures >= this.maxFailures){
								this.removeTrigger=true;
							}
						}
					}
					this.lastTime=this.currentTime;
				//	//
				//	var tempFlake=document.getElementById(this.globalID);
				//	// set the recuring loop to move the snow flake
				//	//tempFlake.style.top = (parseInt(tempFlake.style.top) + (this.speed)) + "px";
				//	if ( (parseInt(tempFlake.style.top) > (window.innerHeight+100)) ){
				//		// randomize the size of the flake to create distance
				//		this.speed=( Math.floor(Math.random() * 4) + 2 );
				//		this.size=( Math.floor(Math.random() * 6) + 1 );
				//		tempFlake.style.width=this.size+"rem";
				//		tempFlake.style.height=this.size+"rem";
				//		// randomize the spin direction
				//		if(1 == Math.floor(Math.random() * 2) ){
				//			tempFlake.className="snowflake_left";
				//		}else{
				//			tempFlake.className="snowflake_right";
				//		}
				//		tempFlake.innerHTML="⚙️";
				//		// move the snow flake back above the top
				//		tempFlake.style.top = (-1 * ( (Math.random() * 400) + 100 ) )+"px";
				//		// set a random opacity
				//		//tempFlake.style.opacity = "0."+(Math.floor(Math.random() * 9));
				//		// set a randomized blur
				//		tempFlake.style.transform = "blur("+Math.floor(Math.random * 99)+"px);";
				//		// give the flake a random location
				//		tempFlake.style.left = ( Math.floor(Math.random() * window.innerWidth) );
				//	}
				}, 33);
		//}, ((Math.floor(Math.random() * 10)) * 1000) );
		}
	}
	//for(var index=0;index<Math.floor(window.innerWidth/2);index++){
	//for(var index=0;index<Math.floor(window.innerWidth/1);index++){
	for(var index=0;index<Math.floor(window.innerWidth/16);index++){
		new snowflake();
	}
</script>
