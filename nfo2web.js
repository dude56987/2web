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
//-----------------------------------------------------------------------------
