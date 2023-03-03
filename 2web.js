//-----------------------------------------------------------------------------
function toggleVisibleClass( visibleClass ){
  var elementArray = document.getElementsByClassName( visibleClass );
  if (elementArray[0].hidden == false){
    // make hidden items visible
    var newVisibility = true;
  } else {
    // make visible items hidden
    var newVisibility = false;
  }
  //console.log(elementArray);
  for (var index=0; index < elementArray.length;index++){
   //console.log(elementArray[index]);
   elementArray[index].hidden = newVisibility;
  }
  return true
}
//-----------------------------------------------------------------------------
function hideVisibleClass( visibleClass ){
	var elementArray = document.getElementsByClassName( visibleClass );
	// make hidden items visible
	var newVisibility = true;
  for (var index=0; index < elementArray.length;index++){
   elementArray[index].hidden = newVisibility;
  }
  return true
}
//-----------------------------------------------------------------------------
function setHeaderStartState(){
	if (window.innerWidth < window.innerHeight || window.innerWidth < 800 ) {
		console.log("Phone Mode")
		// this is the phone portrat mode
		toggleVisibleClass("headerButtons");
	}else{
		// this is the desktop mode
		console.log("Desktop Mode")
	}
}
//-----------------------------------------------------------------------------
function filterByClass(className,searchText){
  var filter = searchText;
  filter = filter.toLowerCase();
  console.log(filter);//debug

  // find all elements of htmlClass
  var elements = document.getElementsByClassName(className);

  console.log(elements);//debug
  console.log(elements.toString());//debug

  var tempContent = "";
  // show all elements containing the search term
  for (elementIndex in elements) {
    console.log(elements[elementIndex]);//debug

    tempContent = elements[elementIndex].textContent;
    tempContent.toLowerCase();

    console.log(tempContent);//debug

    // hide element if it contains the search term
    if ( tempContent.search(filter) != -1 ) {
      console.log("Found matching element");
      // show elements that match the search
      elements[elementIndex].style.display = "inline-block";
    } else {
      console.log("Found non-matching element");
      // hide elements that do not contain the filter phrase
      elements[elementIndex].style.display = "none";
    }
  }
}
//-----------------------------------------------------------------------------
function filter(className){
  // read searchbox text to create the filter
  var searchBoxElement = document.getElementById('searchBox');
  console.log(searchBoxElement);//debug
  console.log(searchBoxElement.value);//debug

  //var filter = searchBoxElement.innerHTML;
  var filter = searchBoxElement.value;
  filter = filter.toLowerCase();
  console.log(filter);//debug

  // find all elements of htmlClass
  var elements = document.getElementsByClassName(className);

  console.log(elements);//debug
  console.log(elements.toString());//debug

  var tempContent = "";
  // show all elements containing the search term
  for (elementIndex in elements) {
    console.log(elements[elementIndex]);//debug

    tempContent = elements[elementIndex].textContent;
    tempContent = tempContent.toLowerCase();

    console.log(tempContent);//debug

    // hide element if it contains the search term
    if ( tempContent.search(filter) != -1 ) {
      console.log("Found matching element");
      // show elements that match the search
      elements[elementIndex].style.display = "inline-block";
    } else {
      console.log("Found non-matching element");
      // hide elements that do not contain the filter phrase
      elements[elementIndex].style.display = "none";
    }
  }
}
////////////////////////////////////////////////////////////////////////////////
function playPause(){
  var video = document.getElementById("video");
  var playButton = document.getElementById("playButton");
  var pauseButton = document.getElementById("pauseButton");
  if(video.paused){
    video.play();
    playButton.style.display = 'none';
    pauseButton.style.display = 'inline-block';
  }else{
    video.pause();
    playButton.style.display = 'inline-block';
    pauseButton.style.display = 'none';
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
function volumeUp(){
  var tempVolume = document.getElementById("video").volume;
  if ( (tempVolume + 0.05) > 1 ){
    // dont let volume go below zero
    tempVolume = 1;
    document.getElementById("video").volume = 1;
  } else {
    document.getElementById("video").volume += 0.05;
  }
  tempVolume = Math.floor(tempVolume * 100);
  if (tempVolume < 10){
    var volumeString = "&nbsp;&nbsp;"+String(tempVolume);
  }else if (tempVolume < 100){
    var volumeString = "&nbsp;"+String(tempVolume);
  } else {
    var volumeString = String(tempVolume);
  }
  document.getElementById("currentVolume").innerHTML=volumeString;
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
    document.getElementById("video").volume -= 0.05;
  }
  tempVolume = Math.floor(tempVolume * 100);
  if (tempVolume < 10){
    var volumeString = "&nbsp;&nbsp;"+String(tempVolume);
  }else if (tempVolume < 100){
    var volumeString = "&nbsp;"+String(tempVolume);
  } else {
    var volumeString = String(tempVolume);
  }
  document.getElementById("currentVolume").innerHTML=volumeString;
	return false;
}
////////////////////////////////////////////////////////////////////////////////
function muteUnMute(){
  var video = document.getElementById("video");
  var muteButton = document.getElementById("muteButton");
  var unMuteButton = document.getElementById("unMuteButton");
  if(video.muted){
    video.muted = true;
    muteButton.style.display = 'none';
    unMuteButton.style.display = 'inline-block';
  }else{
    video.muted = false;
    muteButton.style.display = 'inline-block';
    unMuteButton.style.display = 'none';
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
/* View in fullscreen */
function openFullscreen() {
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
/* Close fullscreen */
function closeFullscreen() {
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
