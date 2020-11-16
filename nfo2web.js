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
