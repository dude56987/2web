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
?>
<style>
	@keyframes snowflake_left{
		0% {
			transform: translateX(0rem);
		}
		100% {
			transform: translateX(30rem);
		}
	}
	@keyframes snowflake_right{
		0% {
			transform: translateX(0rem);
		}
		100% {
			transform: translateX(-30rem);
		}
	}
	@keyframes glow{
		0% {
			text-shadow: 0px 0px 2rem orange;
		}
		50% {
			text-shadow: 0px 0px 3rem orange;
		}
		100% {
			text-shadow: 0px 0px 2rem orange;
		}

	}
	.snowflake_right{
		/*
		animation: snowflake_right 50s 1, glow 10s;
		*/
		animation: snowflake_right 50s 1;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		color: white;
		user-select: none;
		font-family: monospace;
		emoji-variant-emoji: unicode;
		text-shadow: 0px 0px 2rem orange;
	}
	.snowflake_left{
		/*
		animation: snowflake_left 50s 1, glow 10s;
		*/
		animation: snowflake_left 50s 1;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
		color: white;
		user-select: none;
		font-family: monospace;
		emoji-variant-emoji: unicode;
		text-shadow: 0px 0px 2rem orange;
	}
</style>
<script>
	var globalSnowflakes=0;
	class snowflake{
		constructor(){
			this.maxSize=6;
			this.speed=( Math.floor(Math.random() * 2) + 1 );
			this.size=( Math.floor(Math.random() * this.maxSize) + 1 );
			// wait a random time before building the  snowflake
			this.snowFlakeDiv = document.createElement("div");
			this.snowFlakeDiv.id="snowflake_"+globalSnowflakes;
			this.globalID=this.snowFlakeDiv.id;
			if(1 == Math.floor(Math.random() * 2) ){
				this.snowFlakeDiv.className="snowflake_left";
			}else{
				this.snowFlakeDiv.className="snowflake_right";
			}
			// create a random snowflake
			this.snowFlakeDiv.innerHTML="🏮";
			// further away is smaller
			this.snowFlakeDiv.style.zIndex=((-(this.maxSize-this.size))-1);
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
				tempFlake.style.top = (parseInt(tempFlake.style.top) - this.speed) + "px";
				if ( ( parseInt(tempFlake.style.top) < -200 ) || ( parseInt(tempFlake.style.left) < -100 ) || ( parseInt(tempFlake.style.left) > ( window.innerWidth + 100 ) ) ){
					this.speed=( Math.floor(Math.random() * 2) + 1 );
					// randomize the size of the flake to create distance
					this.size=( Math.floor(Math.random() * this.maxSize) + 1 );
					// further away is smaller
					tempFlake.style.zIndex=((-(this.maxSize-this.size))-1);
					tempFlake.style.width=this.size+"rem";
					tempFlake.style.height=this.size+"rem";
					// randomize the spin direction
					if(1 == Math.floor(Math.random() * 2) ){
						tempFlake.className="snowflake_left";
					}else{
						tempFlake.className="snowflake_right";
					}
					tempFlake.innerHTML="🏮";
					// move the snow flake back below the bottom
					tempFlake.style.top = (window.innerHeight + ( (Math.random() * 400) + 100 ) )+"px";
					// set a random opacity
					//tempFlake.style.opacity = "0."+(Math.floor(Math.random() * 9));
					// set a randomized blur
					tempFlake.style.transform = "blur("+Math.floor(Math.random * 99)+"px);";
					// give the flake a random location
					tempFlake.style.left = ( Math.floor(Math.random() * window.innerWidth) );
					// reset the animations for the snowflake
					tempFlake.getAnimations().forEach((animation) => {
						animation.cancel();
						animation.play();
					});
				}
			}, 33);
		}
	}
	for(var index=0;index<Math.floor(window.innerWidth/12);index++){
		new snowflake();
	}
</script>



