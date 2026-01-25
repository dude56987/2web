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
</style>
<script>
	var globalSnowflakes=0;
	class snowflake{
		constructor(){
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
				tempFlake.style.top = (parseInt(tempFlake.style.top) - 1) + "px";
				tempFlake.style.scale = (parseInt(tempFlake.style.scale) + 1) + "px";
				if ( ( parseInt(tempFlake.style.top) < -100 ) ){
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
			}, 10);
		}
	}
	for(var index=0;index<Math.floor(window.innerWidth/12);index++){
		new snowflake();
	}
</script>



