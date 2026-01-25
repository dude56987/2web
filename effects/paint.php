<?PHP
########################################################################
# 2web paint effect
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
	/*
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
	*/
	.droplet{
		opacity: 0.5;
	}
</style>
<script>
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
	function randomPaint(){
		var paintColors=Array("red","green","blue","yellow");
		var pickedColor=Math.floor(Math.random() * 4);
		return paintColors[pickedColor];
	}
	var globalSnowflakes=0;
	class snowflake{
		constructor(){
		//setTimeout( () => {
				this.speed=( Math.floor(Math.random() * 20) + 15 );
				this.size=( Math.floor(Math.random() * 3) );
				// wait a random time before building the  snowflake
				this.snowFlakeDiv = document.createElement("div");
				this.snowFlakeDiv.id="snowflake_"+globalSnowflakes;
				this.globalID=this.snowFlakeDiv.id;
				this.snowFlakeDiv.className="droplet";
				// create a random snowflake
				this.snowFlakeDiv.innerHTML="🌢";
				this.snowFlakeDiv.style.color=randomPaint();
				this.snowFlakeDiv.style.zIndex="-1";
				this.snowFlakeDiv.style.width=this.size+"rem";
				this.snowFlakeDiv.style.height=this.size+"rem";
				//this.snowFlakeDiv.style.transform="rotate(0deg)";
				this.snowFlakeDiv.style.fontSize=this.size+"rem";
				this.snowFlakeDiv.style.textAlign="center";
				this.snowFlakeDiv.style.transform = "blur("+Math.floor(Math.random * 3)+"px);";
				this.snowFlakeDiv.style.position="fixed";
				this.snowFlakeDiv.style.top = ( ( Math.random() * (window.innerHeight + 400) ) - 400 )+"px";
				this.snowFlakeDiv.style.left = ( Math.floor(Math.random() * window.innerWidth) );
				// add the snowflake to the document
				document.body.appendChild(this.snowFlakeDiv);
				// increment the snowflake number
				globalSnowflakes+=1;
				setInterval( () => {
					//
					var tempFlake=document.getElementById(this.globalID);
					// set the recuring loop to move the snow flake
					tempFlake.style.top = (parseInt(tempFlake.style.top) + this.speed) + "px";
					if ( (parseInt(tempFlake.style.top) > window.innerHeight) || (parseInt(tempFlake.style.left) > window.innerWidth) ){
						this.speed=( Math.floor(Math.random() * 20) + 15 );
						// randomize the size of the flake to create distance
						this.size=( Math.floor(Math.random() * 3) );
						tempFlake.style.color=randomPaint();
						tempFlake.style.width=this.size+"rem";
						tempFlake.style.height=this.size+"rem";
						tempFlake.className="droplet";
						tempFlake.innerHTML="🌢"
						// move the snow flake back above the top
						tempFlake.style.top = (-1 * ( (Math.random() * 400) + 100 ) )+"px";
						// set a randomized blur
						tempFlake.style.transform = "blur("+Math.floor(Math.random * 99)+"px);";
						// give the flake a random location
						tempFlake.style.left = ( Math.floor(Math.random() * window.innerWidth) );
					}
				}, 33);
			//}, ((Math.floor(Math.random() * 10)) * 1000) );
		}
	}
	for(var index=0;index<Math.floor(window.innerWidth/6);index++){
		new snowflake();
	}
</script>
