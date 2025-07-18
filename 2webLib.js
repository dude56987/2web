////////////////////////////////////////////////////////////////////////////////
// 2web javascript library
// Copyright (C) 2023  Carl J Smith
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////
function hideVisibleClass( visibleClass ){
	var elementArray = document.getElementsByClassName( visibleClass );
	for (var index=0; index < elementArray.length;index++){
		elementArray[index].hidden = true;
	}

	return true;
}
////////////////////////////////////////////////////////////////////////////////
function showVisibleClass( visibleClass ){
	var elementArray = document.getElementsByClassName( visibleClass );
	for (var index=0; index < elementArray.length;index++){
		elementArray[index].hidden = false;
	}

	return true;
}
////////////////////////////////////////////////////////////////////////////////
function toggleVisibleClass( visibleClass ){
	var elementArray = document.getElementsByClassName( visibleClass );
	for (var index=0; index < elementArray.length;index++){
		if (elementArray[index].hidden === false){
			// make hidden items visible
			hideVisibleClass( visibleClass );
		} else {
			// make visible items hidden
			showVisibleClass( visibleClass );
		}
	}
	return true
}
////////////////////////////////////////////////////////////////////////////////
function setHeaderState(){
	if (window.innerWidth < window.innerHeight || window.innerWidth < 800 ) {
		hideVisibleClass("headerButtons");
	}else{
		showVisibleClass("headerButtons");
	}
}
////////////////////////////////////////////////////////////////////////////////
function setHeaderStartState(){
	// run the state setup
	setHeaderState();
	// set a resize event for updating the header state on window resize or phone rotate
	window.addEventListener('resize', function(event){
		// set the header state based on the screen size
		// if the window is in portrat mode
		setHeaderState();
	});
}
//-----------------------------------------------------------------------------
function filterByClass(className,searchText){
	var filter = searchText;
	filter = filter.toLowerCase();

	// find all elements of htmlClass
	var elements = document.getElementsByClassName(className);


	var tempContent = "";
	// show all elements containing the search term
	for (elementIndex in elements) {

		tempContent = elements[elementIndex].textContent;
		tempContent.toLowerCase();


		// hide element if it contains the search term
		if ( tempContent.search(filter) != -1 ) {
			// show elements that match the search
			elements[elementIndex].style.display = "inline-block";
		} else {
			// hide elements that do not contain the filter phrase
			elements[elementIndex].style.display = "none";
		}
	}
}
//-----------------------------------------------------------------------------
function filter(className){
	// read searchbox text to create the filter
	var searchBoxElement = document.getElementById('searchBox');

	//var filter = searchBoxElement.innerHTML;
	var filter = searchBoxElement.value;
	filter = filter.toLowerCase();

	// find all elements of htmlClass
	var elements = document.getElementsByClassName(className);

	var tempContent = "";
	// show all elements containing the search term
	for (elementIndex in elements) {

		tempContent = elements[elementIndex].textContent;
		tempContent = tempContent.toLowerCase();

		// hide element if it contains the search term
		if ( tempContent.search(filter) != -1 ) {
			// show elements that match the search
			elements[elementIndex].style.display = "inline-block";
		} else {
			// hide elements that do not contain the filter phrase
			elements[elementIndex].style.display = "none";
		}
	}
}
////////////////////////////////////////////////////////////////////////////////
function setPlayButtonState(){
	var video = document.getElementById("video");
	var playButton = document.getElementById("playButton");
	var pauseButton = document.getElementById("pauseButton");
	if(video.paused){
		playButton.style.display = 'none';
		pauseButton.style.display = 'inline-block';
	}else{
		playButton.style.display = 'inline-block';
		pauseButton.style.display = 'none';
	}
}
////////////////////////////////////////////////////////////////////////////////
function pauseVideo(){
	// pauseVideo()
	//
	// Attempt to pause the video element with the ID 'video'
	//
	var video = document.getElementById("video");
	if(video.paused){
		console.log("Video is already paused.");
	}else{
		video.pause().catch(error => {
			console.log("Playback failed because user has not interacted with the page yet.");
		});
	}
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function playVideo(){
	// playVideo()
	//
	// Attempt to play the video element with the ID 'video'
	//
	var video = document.getElementById("video");
	if(video.paused){
		video.play().catch(error => {
			console.log("Playback failed because user has not interacted with the page yet.");
		});
	}else{
		console.log("Video is already playing.");
	}
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function playPause(){
	// playPause()
	//
	// Toggle play/pause state of the video element with the ID 'video'
	//
	var video = document.getElementById("video");
	//var playButton = document.getElementById("playButton");
	//var pauseButton = document.getElementById("pauseButton");
	if(video.paused){
		video.play().catch(error => {
			console.log("Playback failed because user has not interacted with the page yet.");
		});
		//playButton.style.display = 'none';
		//pauseButton.style.display = 'inline-block';
	}else{
		video.pause().catch(error => {
			console.log("Playback failed because user has not interacted with the page yet.");
		});
		//playButton.style.display = 'inline-block';
		//pauseButton.style.display = 'none';
	}
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function forcePlay(){
	var video = document.getElementById("video");
	var playButton = document.getElementById("playButton");
	var pauseButton = document.getElementById("pauseButton");
	video.play();
	playButton.style.display = 'none';
	pauseButton.style.display = 'inline-block';
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function seekForward(){
	// get the element pointer
	var element = document.getElementById("video");
	// increment the time forward in seconds
	element.currentTime += 15;
	// get the current time and the total duration to not exceed the end of the video
	if ( element.currentTime > element.duration ){
		// dont let volume go beyond the duration
		element.currentTime = element.duration;
	}
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function seekBackward(){
	// get the element pointer
	var element = document.getElementById("video");
	// increment the time backward in seconds
	element.currentTime -= 15;
	// get the current time and the total duration to not exceed the end of the video
	if ( element.currentTime < 0 ){
		// dont let the seek go below 0
		element.currentTime = 0;
	}
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function volumeUp(){
	var tempVolume = document.getElementById("video").volume;
	if ( (tempVolume + 0.05) > 1 ){
		// dont let volume go below zero
		tempVolume = 1;
		document.getElementById("video").volume = 1;
	} else {
		document.getElementById("video").volume += 0.10;
	}
	tempVolume = Math.floor(tempVolume * 100);
	if (tempVolume < 10){
		var volumeString = "&nbsp;&nbsp;"+String(tempVolume);
	}else if (tempVolume < 100){
		var volumeString = "&nbsp;"+String(tempVolume);
	} else {
		var volumeString = String(tempVolume);
	}
	//document.getElementById("currentVolume").innerHTML=volumeString;
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function volumeDown(){
	var tempVolume = document.getElementById("video").volume;
	if ( (tempVolume - 0.05) < 0 ){
		// dont let volume go above one
		tempVolume = 0;
		document.getElementById("video").volume = 0;
	} else {
		document.getElementById("video").volume -= 0.10;
	}
	tempVolume = Math.floor(tempVolume * 100);
	if (tempVolume < 10){
		var volumeString = "&nbsp;&nbsp;"+String(tempVolume);
	}else if (tempVolume < 100){
		var volumeString = "&nbsp;"+String(tempVolume);
	} else {
		var volumeString = String(tempVolume);
	}
	//document.getElementById("currentVolume").innerHTML=volumeString;
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function muteUnMute(){
	var video = document.getElementById("video");
	var muteButton = document.getElementById("muteButton");
	var unMuteButton = document.getElementById("unMuteButton");
	if(video.muted){
		video.muted = false;
		if(muteButton){
			muteButton.style.display = 'inline-block';
			unMuteButton.style.display = 'none';
		}
	}else{
		video.muted = true;
		if(muteButton){
			muteButton.style.display = 'none';
			unMuteButton.style.display = 'inline-block';
		}
	}
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function showControls(){
	var video = document.getElementById("video");
	var showControls = document.getElementById("showControls");
	var hideControls = document.getElementById("hideControls");
	// show controls for video
	showControls.style.display = 'none';
	hideControls.style.display = 'inline-block';
	video.setAttribute("controls","controls");
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function hideControls(){
	var video = document.getElementById("video");
	var showControls = document.getElementById("showControls");
	var hideControls = document.getElementById("hideControls");
	// show controls for video
	showControls.style.display = 'inline-block';
	hideControls.style.display = 'none';
	video.removeAttribute("controls");
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function reloadVideo(){
 document.getElementById("video").load();
}
////////////////////////////////////////////////////////////////////////////////
function stopVideo(){
	document.getElementById("video").stop();
}
////////////////////////////////////////////////////////////////////////////////
function toggleFullscreen(elementId="",enableScroll=false) {
	var chosenElement;
	var topButton;
	if (elementId == ""){
		// use the body if no element is set
		chosenElement=document.body;
	}else{
		// get the element by id
		chosenElement=document.getElementById(elementId);
	}
	if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement){
		if (chosenElement.requestFullscreen) {
			chosenElement.requestFullscreen();
		} else if (chosenElement.webkitRequestFullscreen) {
			chosenElement.webkitRequestFullscreen();
		} else if (chosenElement.msRequestFullscreen) {
			chosenElement.msRequestFullscreen();
		}
		// remove the top button
		if (document.getElementById("topButton")){
			topButton = document.getElementById("topButton");
			topButton.style.display="none";
		}
		// enable scrolling
		if (enableScroll){
			// renable the scrolling on the element
			chosenElement.style.overflowY="scroll";
		}
	}else{
		// Close fullscreen
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		}
		// add back the top button
		if (document.getElementById("topButton")){
			topButton = document.getElementById("topButton");
			topButton.style.display="block";
		}
	}

	return true;
}
////////////////////////////////////////////////////////////////////////////////
function showSpinner(){
	console.log("Spinner being shown")
	var spinnerElements=document.getElementsByClassName("globalSpinner");
	// loop though the found spinners
	for(let index = 0; index < spinnerElements.length; index++){
		// show the spinner
		spinnerElements[index].style.visibility= "visible";
	}
	// get the pulse elements
	var pulseElements=document.getElementsByClassName("globalPulse");
	for(let index = 0; index < pulseElements.length; index++){
		pulseElements[index].style.visibility = "visible";
	}
	return true;
}
////////////////////////////////////////////////////////////////////////////////
function hideSpinner(){
	console.log("Spinner being hidden")
	var spinnerElements=document.getElementsByClassName("globalSpinner");
	// loop though the found spinners
	for(let index = 0; index < spinnerElements.length; index++){
		// show the spinner
		spinnerElements[index].style.visibility= "hidden";
	}
	// get the pulse elements
	var pulseElements=document.getElementsByClassName("globalPulse");
	for(let index = 0; index < pulseElements.length; index++){
		pulseElements[index].style.visibility = "hidden";
	}
	return true;
}
////////////////////////////////////////////////////////////////////////////////
function openFullscreen() {
	// View in fullscreen

	// check the window orientation
	if (window.orientation != 90 && window.orientation != -90){
		// if the window is in portrat mode switch to landscape mode
		screen.orientation.lock('landscape')
	}
	// launch fullscreen
	var video = document.getElementById("videoPlayerContainer");
	if (video.requestFullscreen) {
		video.requestFullscreen();
	} else if (video.webkitRequestFullscreen) { /* Safari */
		video.webkitRequestFullscreen();
	} else if (video.msRequestFullscreen) { /* IE11 */
		video.msRequestFullscreen();
	}
	document.getElementById("fullscreenButton").style = "display:none;";
	document.getElementById("exitFullscreenButton").style = "display: inline-block;";
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function closeFullscreen() {
	// Close fullscreen
	if (document.exitFullscreen) {
		document.exitFullscreen();
	} else if (document.webkitExitFullscreen) { /* Safari */
		document.webkitExitFullscreen();
	} else if (document.msExitFullscreen) { /* IE11 */
		document.msExitFullscreen();
	}
	document.getElementById("fullscreenButton").style = "display: inline-block;";
	document.getElementById("exitFullscreenButton").style = "display: none;";
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function file_get_contents(fileUrl) {
	// non async read a file on the local server
	let xhttp = new XMLHttpRequest();
	xhttp.open("GET", fileUrl, false);
	xhttp.send();
	return xhttp.responseText;
}
////////////////////////////////////////////////////////////////////////////////
function delayedRefresh(timeout) {
	// reload the page after a timeout
	setTimeout(function() {
		// reload the page only if the search bar does not have focus
		if(document.activeElement.tagName == "INPUT"){
			// if the search box is focused delay the reload another cycle
			delayedRefresh(timeout);
		}else{
			// reload the current page
			location.reload();
		}
	},(1000*timeout));
}
////////////////////////////////////////////////////////////////////////////////
function delayedRedirect(timeout,url) {
	// redirect the page after a timeout
	setTimeout(function() {
		// redirect the page only if the search bar does not have focus
		if(document.activeElement.tagName == "INPUT"){
			// if the search box is focused delay the reload another cycle
			delayedRedirect(timeout,url);
		}else{
			// redirect the current page
			window.location = url;
		}
	},(1000*timeout));
}

////////////////////////////////////////////////////////////////////////////////
function copyToClipboard(copyText){
	// copy text given as $copyText to the system clipboard
	// - Text should be passed to this function encoded by encodeURIComponent() or
	//   by rawurlencode() in PHP
	var decodedText = decodeURIComponent(copyText);
	// write the text to the clipboard
	navigator.clipboard.writeText(decodedText);
}
////////////////////////////////////////////////////////////////////////////////
function CreateCopyButtons(){
	// grab all the pre tags
	var elementArray = document.body.getElementsByTagName("pre");
	for (var index=0; index < elementArray.length;index++){
		// copy the inner html of the object
		var clipboardData = elementArray[index].innerHTML;
		// remove code tags generated by markdown
		clipboardData = clipboardData.replaceAll("<code>", "");
		clipboardData = clipboardData.replaceAll("</code>", "");
		// escape the string data for passing it to the clipboard
		clipboardData = encodeURIComponent(clipboardData);
		// convert characters that are not done by javascripts encodeURIComponent
		clipboardData = clipboardData.replaceAll("'", "%27");
		// build the copy button
		elementArray[index].innerHTML = "<button class='copyButton' onclick='"+'copyToClipboard("' + clipboardData + '")' + ";return false;'></button>" + elementArray[index].innerHTML;
	}
}
////////////////////////////////////////////////////////////////////////////////
function notify(message,displayTime=500){
	console.log("notify="+message);
	// if a notification is being displayed, remove it
	if(document.getElementById("notification") != null){
		document.getElementById("notification").remove();
	}
	if(document.getElementById("notification") == null){
		console.log("notify passed="+message);
		// build the notification
		var notifyObj = document.createElement("div");
		notifyObj.setAttribute("id", "notification");
		//document.getElementById("pageContent").appendChild(notifyObj);
		document.body.appendChild(notifyObj);
		//
		document.getElementById("notification").style.opacity=0.9;
		document.getElementById("notification").innerHTML=message;
		// hide the message after .5 seconds
		notificationTimer = setTimeout(() =>{
			//
			if(document.getElementById("notification") != null){
				//
				document.getElementById("notification").style.opacity=0;
				// allow animation time to run then remove the object
				notificationTimeoutTimer = setTimeout(() =>{
					document.getElementById("notification").remove();
				}, displayTime);
			}
		}, 355);
	}
}
////////////////////////////////////////////////////////////////////////////////
function typeText(textInput,elementID="default",typingSpeed=50,index=0){
	// type out a text string like a oldschool videogame
	if (index < textInput.length){
		//
		if(elementID=="default"){
			// add text to the body if no element is selected
			document.body.innerHTML += textInput.charAt(index);
		}else{
			document.getElementById(elementID).innerHTML += textInput.charAt(index);
		}
		index+=1;
		setTimeout(typeText, typingSpeed, textInput, elementID, typingSpeed, index );
	}
}
/*
//-----------------------------------------------------------------------------
function startVideoUpdateLoop(){
	// get the seek bar element
	var videoPositionBar = document.getElementById("videoPositionBar");
	var video = document.getElementById("video");
	// create the event to update the seek bar value
	videoPositionBar.addEventListener("change", function, videoUpdateLoop(video,videoPositionBar);
}
//-----------------------------------------------------------------------------
function videoUpdateLoop(var video,var videoPositionBar){
	var time = (video.duration * (videoPositionBar.value/100));
	// set new position value
	video.currentTime = time;
}
//-----------------------------------------------------------------------------
*/
