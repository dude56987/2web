<?php
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# check group permissions based on what the player is being used for
	requireGroup("php2web");
	################################################################################
	# Create functions
	################################################################################
	function loadTicketData(){
		# load the csv file into an 2d array
		if(file_exists("/etc/2web/applications/settings/ticket/tickets.csv")){
			$ticketText=file("/etc/2web/applications/settings/ticket/tickets.csv");
		}else{
			$ticketText=Array();
		}
		$ticketData=Array();
		foreach($ticketText as $ticketLine){
			# remove newlines
			$ticketLine=str_replace("\n","",$ticketLine);
			# split the csv data into a array
			$ticketData=array_merge($ticketData,Array(explode(",",$ticketLine)));
		}
		return $ticketData;
	}
	################################################################################
	function loadTicketTitles($full=true,$key=false){
		# load all the titles in tickets.csv
		if(file_exists("/etc/2web/applications/settings/ticket/tickets.csv")){
			$ticketText=file("/etc/2web/applications/settings/ticket/tickets.csv");
		}else{
			$ticketText=Array();
		}
		$ticketData=Array();
		# load the root value
		if($key){
			$ticketData["root"]="root";
		}else if($full){
			$ticketData=array_merge($ticketData,Array(Array("root","root")));
		}else{
			$ticketData=array_merge($ticketData,Array("root"));
		}
		# load all the other titles
		foreach($ticketText as $ticketLine){
			# remove newlines
			$ticketLine=str_replace("\n","",$ticketLine);
			# convert to array
			$ticketLine=explode(",",$ticketLine);
			#addToLog("DEBUG","Ticket Titles","Ticket Line = ".var_export($ticketLine,true));
			if($key){
				$ticketData[$ticketLine[6]]=$ticketLine[0];
				#$ticketData=array_merge($ticketData,Array($tempLine));
			}else if($full){
				# split the title data into a 2d array linking hash values to titles
				$ticketData=array_merge($ticketData,Array(Array($ticketLine[6],$ticketLine[0])));
			}else{
				# only list the hash values
				$ticketData=array_merge($ticketData,Array($ticketLine[6]));
			}
			#
			#addToLog("DEBUG","Ticket Titles","Ticket Data = ".var_export($ticketData,true));
		}
		return $ticketData;
	}
	################################################################################
	function filterTicketHash($hashString){
		$ticketData=loadTicketData();
		$newTicketData=Array();
		# load each line
		foreach($ticketData as $ticketLine){
			#
			if($ticketLine[6] == $hashString){
				#
				$newTicketData=array_merge($newTicketData,Array($ticketLine));
			}
		}
		# return the filtered ticket data
		return array_unique($newTicketData);
	}
	################################################################################
	function childTickets($parentString,$ticketData=false){
		# get all the children tickets of the parent ticket
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		$newTicketData=Array();
		$parentTicketData=Array();
		$foundTickets=Array();
		# load each line
		foreach($ticketData as $ticketLine){
			#
			if($ticketLine[3] == $parentString){
				# only add tickets once
				if(! in_array($ticketLine[6],$foundTickets)){
					# add ticket data
					$newTicketData=array_merge($newTicketData,Array($ticketLine));
					# add found ticket to the found tickets to avoid duplicates
					$foundTickets=array_merge($foundTickets,Array($ticketLine[6]));
				}
			}else if($ticketLine[6] == $parentString){
				$parentTicketData=array_merge($parentTicketData,Array($ticketLine));
			}
		}
		if (count($newTicketData) == 0){
			# return only the parent ticket if no child tickets were found
			return $parentTicketData;
		}
		# return the filtered ticket data
		return $newTicketData;
	}
	################################################################################
	function subTickets($parentString,$ticketData=false){
		#addToLog("DEBUG","Subtickets","Adding subtickets for '".var_export($parentString,true)."'");
		# get all tickets below the parent ticket
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		$newTicketData=Array();
		$foundTickets=Array();
		$parentTicketData=Array();
		# load each line
		foreach($ticketData as $ticketLine){
			#
			if($ticketLine[3] == $parentString){
				if(count($ticketLine) == 7){
					#addToLog("DEBUG","SubTickets New Valid Ticket Line",var_export($ticketLine,true));
					if (! in_array($ticketLine[6],$foundTickets)){
						#addToLog("DEBUG","SubTickets","Scanning for subtickets of '$ticketLine[0]'");
						# get all sub tickets
						$newTicketData=array_merge($newTicketData,subTickets($ticketLine[6],$ticketData));
						#
						$foundTickets=array_merge($foundTickets,Array($ticketLine[6]));
						#addToLog("DEBUG","SubTickets Adding subtickets found to main ticket data",var_export($newTicketData,true));
					}
					# add this ticket line
					$newTicketData=array_merge($newTicketData,Array($ticketLine));
					#addToLog("DEBUG","SubTickets New Ticket Data after adding children ticktets",var_export($newTicketData,true));
				}
			}else if($ticketLine[6] == $parentString){
				#
				$parentTicketData=array_merge($parentTicketData,Array($ticketLine));
			}
		}
		if (count($newTicketData) == 0){
			# return only the parent ticket if no sub tickets were found
			return $parentTicketData;
		}
		#addToLog("DEBUG","SubTickets New Ticket Data after search",var_export($newTicketData,true));

		$foundTicketIds=Array();
		$allTicketData=Array();
		# filter all the tickets to remove duplicates
		foreach($newTicketData as $ticket){
			#addToLog("DEBUG","found subticket ids","foundTicketIds=".var_export($foundTicketIds,true));
			#addToLog("DEBUG","found subticket ids","ticket=".var_export($ticket,true));
			#
			if(! in_array($ticket[6],$foundTicketIds)){
				#addToLog("DEBUG","found subticket ids","ticket=".$ticket[6]." is NOT in ".var_export($foundTicketIds,true));
				#
				$foundTicketIds=array_merge($foundTicketIds,Array($ticket[6]));
				#
				$allTicketData=array_merge($allTicketData,Array($ticket));
			}else{
				#addToLog("DEBUG","found subticket ids","ticket=".$ticket[6]." is in ".var_export($foundTicketIds,true));
			}
		}
		#addToLog("DEBUG","SubTickets New Ticket Data after cleanup",var_export($allTicketData,true));

		#$newTicketData=array_unique($newTicketData);
		#addToLog("DEBUG","SubTickets of '$parentString' New Ticket Data after unique",var_export($newTicketData,true));

		# return the filtered ticket data
		return $allTicketData;
		#return $newTicketData;
	}
	################################################################################
	function alphaHash($hashString){
		#addToLog("DEBUG","Building hash","Building hash from '$hashString'");
		$hashValue=md5($hashString);
		$newValue="";
		foreach(str_split($hashValue) as $character){
			if($character == "1"){
				$newValue .= "a";
			}else if($character == "2"){
				$newValue .= "b";
			}else if($character == "3"){
				$newValue .= "c";
			}else if($character == "4"){
				$newValue .= "d";
			}else if($character == "5"){
				$newValue .= "e";
			}else if($character == "6"){
				$newValue .= "f";
			}else if($character == "7"){
				$newValue .= "g";
			}else if($character == "8"){
				$newValue .= "h";
			}else if($character == "9"){
				$newValue .= "i";
			}else if($character == "0"){
				$newValue .= "j";
			}else{
				$newValue .= $character;
			}
		}
		addToLog("DEBUG","Building hash","Hash from '$hashString' is '$newValue'");
		return $newValue;
	}
	################################################################################
	function buildGraphData($newTicketData,$graphHash,$circle=false,$graphParent="root"){
		# save a 2d array as a CSV file
		$flowChartText="digraph ticketFlowChartGraph {\n";
		$ticketCount=count($newTicketData);
		$ticketKey=loadTicketTitles(false,true);
		#
		ignore_user_abort();
		#
		if ($circle){
			# ticket count must be greater than three for a circle graph
			# - the overlap of nodes makes small circle graphs a mess
			if($ticketCount > 3){
				$flowChartText.="layout=twopi\n";
				$flowChartText.="root=\"$graphParent\"\n";
				# calc the graph spacing
				$graphSpacing = floor( 0.50 + ( 0.50 * $ticketCount ) );
				$flowChartText.="graph [pad=\"0\", nodesep=\"$graphSpacing\", ranksep=\"$graphSpacing\"];\n";
			}
		}else{
			$flowChartText.="graph [pad=\"0\"];\n";
		}
		#$flowChartText.="headclip=false\n";
		$flowChartHeader="";
		$flowChartNodes="";
		#addToLog("DEBUG","graph new ticket data",var_export($newTicketData,true));
		foreach($newTicketData as $ticketLine){
			#addToLog("DEBUG","graph Ticket hash",$ticketLine[0]);
			#addToLog("DEBUG","graph Parent Ticket hash",$ticketLine[3]);

			#addToLog("DEBUG","graph line",var_export($ticketLine,true));
			# only add lines containing all the input data

			# build the hash labels
			$ticketHash=alphaHash($ticketLine[6]);
			$parentHash=alphaHash($ticketLine[3]);

			# figure out the ticket color
			if($ticketLine[2] == "Trivial"){
				$ticketColor="cyan";
				$fontColor="black";
			}else if($ticketLine[2] == "Low"){
				$ticketColor="darkblue";
				$fontColor="white";
			}else if($ticketLine[2] == "Medium"){
				$ticketColor="black";
				$fontColor="white";
			}else if($ticketLine[2] == "High"){
				$ticketColor="darkorange";
				$fontColor="white";
			}else if($ticketLine[2] == "CRITICAL"){
				$ticketColor="darkred";
				$fontColor="white";
			}else{
				$ticketColor="white";
				$fontColor="black";
			}

			# figure out the ticket status
			if($ticketLine[4] == "Unfinished"){
				$ticketStatus="⚙️";
			}else if($ticketLine[4] == "Completed"){
				$ticketStatus="✅";
				$ticketColor="lightgreen";
			}else if($ticketLine[4] == "Closed"){
				$ticketStatus="⛔";
				$ticketColor="darkred";
			}else if($ticketLine[4] == "Will-Not-Fix"){
				$ticketStatus="⚠️";
				$ticketColor="darkred";
			}else{
				$ticketStatus="";
			}
			# build the labels
			#$flowChartHeader .= $parentHash." [label=\"".wordwrap($ticketLine[3],50,"\n")." $ticketStatus\"];\n";
			#$flowChartHeader .= $ticketHash." [label=\"".wordwrap($ticketLine[0],50,"\\n")." ".$ticketStatus."\\n\\n".wordwrap($ticketLine[1],50,"\\n")."\"];\n";
			$flowChartHeader .= $parentHash." [label=\"".wordwrap($ticketKey[$ticketLine[3]],50,"\n")." $ticketStatus\"];\n";
			$flowChartHeader .= $ticketHash." [label=\"".wordwrap($ticketLine[0],50,"\\n")." ".$ticketStatus."\"];\n";
			# add the url links
			$flowChartHeader .= $parentHash." [URL=\"?ticket=".$ticketKey[$ticketLine[3]]."\"];\n";
			$flowChartHeader .= $ticketHash." [URL=\"?ticket=".$ticketLine[6]."\"];\n";
			# set the shape
			$flowChartHeader .= $parentHash." [shape=\"box\"];\n";
			$flowChartHeader .= $ticketHash." [shape=\"box\"];\n";
			# set the color
			$flowChartHeader .= $ticketHash." [color=\"$ticketColor\"];\n";
			$flowChartHeader .= $ticketHash." [fontcolor=\"$fontColor\"];\n";
			$flowChartHeader .= $ticketHash." [style=\"filled\"];\n";

			$tempConnectorLine=$parentHash." -> ".$ticketHash.";\n";
			# add connections that do not yet exist in the graph
			if (! (stripos($flowChartNodes,$tempConnectorLine) !== false)){
				$flowChartNodes .= $tempConnectorLine;
			}
		}
		#
		$flowChartText .= $flowChartHeader;
		$flowChartText .= $flowChartNodes;
		#
		$flowChartText .= "}\n";
		#
		file_put_contents($graphHash.".gv",$flowChartText);
		$appPath="/var/cache/2web/web/applications/ticket/";
		#
		addToQueue("multi","dot -Tsvg ".$appPath.$graphHash.".gv -o ".$appPath.$graphHash.".svg");
		addToQueue("multi","dot -Tpng ".$appPath.$graphHash.".gv -o ".$appPath.$graphHash.".png");
	}
	################################################################################
	function saveTicketData($newTicketData){
		# save a 2d array as a CSV file
		$ticketText="";
		$flowChartText="digraph ticketFlowChartGraph {\n";
		$flowChartHeader="";
		$flowChartNodes="";
		$ticketTitles=loadTicketTitles(false);
		#echo "[DEBUG]: new ticket data for file save<br>\n";
		#echo "[DEBUG]: newTicketData = ".var_export($newTicketData,true)."<br>\n";
		foreach($newTicketData as $ticketLine){
			#echo "[DEBUG]: ticketLine = ".var_export($ticketLine,true)."<br>\n";
			#echo "[DEBUG]: ticketLine imploded = ".var_export(implode(",",$ticketLine),true)."<br>\n";
			# only add lines containing all the input data
			if (count($ticketLine) == 7){
				# verify the parent tickets exist
				#if(! in_array($ticketLine[6],$ticketTitles)){
				#	# reset orphined tickets to root parent
				#	$ticketLine[3]="root";
				#}
				# generate the csv text for the line
				$ticketText .= implode(",",$ticketLine)."\n";
			}
		}

		$flowChartText .= $flowChartHeader;
		$flowChartText .= $flowChartNodes;

		$flowChartText .= "}\n";
		#echo "[DEBUG]: ticketText = ".var_export($ticketText,true)."<br>\n";
		$ticketText=str_replace("\n\n","\n",$ticketText);
		# write the text to the file
		file_put_contents("/etc/2web/applications/settings/ticket/tickets.csv",$ticketText);
		#
		file_put_contents("tickets.gv",$flowChartText);
		$appPath="/var/cache/2web/web/applications/ticket/";
		# render the flowchart overview image in the 2web system queue
		addToQueue("multi","dot -Tpng ".$appPath."tickets.gv -o ".$appPath."tickets.png");
		addToQueue("multi","dot -Tsvg ".$appPath."tickets.gv -o ".$appPath."tickets.svg");
	}
	################################################################################
	function editTicket($ticketTitle,$ticketText,$ticketPriority,$parentTicket="root",$ticketStatus,$ticketHash){
		# editTicket($ticketTitle,$ticketText,$ticketPriority,$parentTicket="root",$ticketStatus)
		#
		# edit a existing ticket

		# generate added date
		$ticketAddDate=time();
		# verify input data
		if($ticketTitle == ""){
			$ticketTitle="BLANK";
		}
		#
		if($ticketText == ""){
			$ticketText="BLANK";
		}
		#echo "[DEBUG]:".var_export($newTicketLine,true)."<br>\n";
		$oldTicketData=loadTicketData();
		# add the new line to the existing data
		$newTicketData=Array();
		$duplicate=false;
		# check for duplicate ticket data
		foreach($oldTicketData as $ticket){
			if($ticket[6] == $ticketHash){
				# create the new line
				$newTicketLine=Array(Array($ticketTitle,$ticketText,$ticketPriority,$parentTicket,$ticketStatus,$ticketAddDate,$ticketHash));
				# replace old data with the new ticket data
				$newTicketData=array_merge($newTicketData,$newTicketLine);
			}else{
				# add existing data
				$newTicketData=array_merge($newTicketData,Array($ticket));
			}
		}
		#echo "[DEBUG]:".var_export($newTicketData ,true)."<br>\n";
		# save the new ticket file
		saveTicketData($newTicketData);
		# return the generated ticket hash
		if (isset($ticketHash)){
			return $ticketHash;
		}else{
			return false;
		}
	}
	################################################################################
	function deleteTicket($ticketTitle){
		# deleteTicket($ticketTitle)
		#
		# edit a existing ticket

		# verify input data
		if($ticketTitle == ""){
			# redirect to error code
			$errorText="<div class='errorBanner'>\n";
			$errorText.="The ticket title can not be blank.\n";
			$errorText.="</div>\n";
			redirect('?error='.$errorText);
		}
		$oldTicketData=loadTicketData();
		# add the new line to the existing data
		$newTicketData=Array();
		$duplicate=false;
		# check for duplicate ticket data
		foreach($oldTicketData as $ticket){
			# only add tickets not matching the ticket to be removed
			if($ticket[6] != $ticketTitle){
				# add existing data
				$newTicketData=array_merge($newTicketData,Array($ticket));
			}
		}
		# save the new ticket file
		saveTicketData($newTicketData);
	}
	################################################################################
	function newTicket($ticketTitle,$ticketText,$ticketPriority,$parentTicket="root"){
		# newTicket($ticketTitle,$ticketText,$ticketPriority,$parentTicket="root")
		#
		# create a new ticket in the ticket system

		# generate added date
		$ticketAddDate=time();
		$ticketStatus="Unfinished";
		# verify input data
		if($ticketTitle == ""){
			$ticketTitle == "BLANK";
		}
		#
		if($ticketText == ""){
			$ticketText == "BLANK";
		}
		# build a unique hash to be used to identify the ticket
		$ticketHash=alphaHash($ticketTitle.$ticketAddDate.$ticketText);
		# create the new line
		$newTicketLine=Array(Array($ticketTitle,$ticketText,$ticketPriority,$parentTicket,$ticketStatus,$ticketAddDate,$ticketHash));
		$oldTicketData=loadTicketData();
		# add the new line to the existing data
		$newTicketData=array_merge($oldTicketData,$newTicketLine);
		#echo "[DEBUG]: newTicketData = ".var_export($newTicketData,true)."<br>\n";
		$duplicate=false;
		# check for duplicate ticket data
		foreach($oldTicketData as $ticket){
			#echo "[DEBUG]: ticketTitle = ".var_export($ticketTitle,true)."<br>\n";
			#echo "[DEBUG]: ticket[0] = ".var_export($ticket[0],true)."<br>\n";
			if($ticket[6] == $ticketTitle){
				# this is a duplicate ticket
				$duplicate=true;
			}
		}
		# if this is not a duplicate
		if($duplicate){
			# redirect to error code
			$errorText="<div class='errorBanner'>\n";
			$errorText.="This ticket has a duplicate title, rename the ticket and re-submit.\n";
			$errorText.="</div>\n";
			redirect('?error='.$errorText);
		}else{
			# save the new ticket file
			saveTicketData($newTicketData);
		}
		return $ticketHash;
	}
	################################################################################
	function getTicket($ticketSearchTitle,$ticketData=false){
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		$parentTickets=Array();
		# read each ticket until a match is found
		foreach($ticketData as $ticket){
			if( $ticket[6] == $ticketSearchTitle ){
				return $ticket;
			}
		}
		return false;
	}
	################################################################################
	function showTicket($ticketSearchTitle,$ticketData=false){
		# draw webpage listing a single ticket
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		$ticketTitles=loadTicketTitles();
		#echo "[DEBUG]: ticket titles = ".var_export($ticketTitles ,true)."<br>\n";
		$ticket=getTicket($ticketSearchTitle);
		if ($ticket == false){
			return false;
		}
		$ticketTitle=$ticket[0];
		$ticketText=$ticket[1];
		$ticketPriority=$ticket[2];
		$ticketParent=$ticket[3];
		$ticketStatus=$ticket[4];
		$ticketTime=$ticket[5];
		$ticketHash=$ticket[6];
		# draw the tickets connected to the given parent
		echo "<div class='titleCard $ticketPriority $ticketStatus'>\n";
		echo "	<form method='post'>\n";
		echo "	<h2>\n";
		#echo "		<a href='?ticket=$ticketHash'>\n";
		echo "		<input class='editTicketTitle' type='text' name='editTicketTitle' value='$ticketTitle' />\n";
		#echo "		</a>\n";
		echo "	</h2>\n";
		# add the title as a hidden read only element to the form
		echo "	<input type='text' name='editTicketHash' value='$ticketHash' hidden />\n";
		echo "	<table>\n";
		echo "		<tr>\n";
		echo "			<th>Priority</th>\n";
		echo "			<th>Status</th>\n";
		echo "			<th>ParentTicket</th>\n";
		echo "		</tr>\n";
		echo "		<tr>\n";
		# load the priority values
		echo "			<td>\n";
		echo "			<select name='editTicketPriority'>\n";
		$ticketPriorities=Array("Trivial","Low","Medium","High","CRITICAL");
		foreach($ticketPriorities as $tempTicketPriority){
			if ( $ticketPriority == $tempTicketPriority){
				echo "			<option selected class='' value='".$tempTicketPriority."'>".$tempTicketPriority."</option>\n";
			}else{
				echo "			<option class='' value='".$tempTicketPriority."'>".$tempTicketPriority."</option>\n";
			}
		}
		echo "			</select>\n";
		echo "			</td>\n";

		# load the status values
		echo "			<td>\n";
		echo "			<select name='editTicketStatus'>\n";
		$ticketStatuses=Array("Unfinished","Completed","Will-Not-Fix","Closed");
		foreach($ticketStatuses as $tempTicketStatus){
			if ( $ticketStatus == $tempTicketStatus){
				echo "			<option selected class='' value='".$tempTicketStatus."'>".$tempTicketStatus."</option>\n";
			}else{
				echo "			<option class='' value='".$tempTicketStatus."'>".$tempTicketStatus."</option>\n";
			}
		}
		echo "			</select>\n";
		echo "			</td>\n";
		# load the parent values
		echo "			<td>\n";
		echo "				<select name='editTicketParent'>\n";
		echo "					<option class='' value='root'>root</option>\n";
		foreach($ticketTitles as $tempTicketTitle){
			if ( $tempTicketTitle[0] == $ticketParent ){
				echo "					<option selected class='' value='".$tempTicketTitle[0]."'>".$tempTicketTitle[1]."</option>\n";
			}else if ( $tempTicketTitle[0] == $ticketTitle){
				# skip drawing the current ticket as a available option for the parent
				# - This would throw the ticket off the graph
				echo "";
			}else{
				echo "					<option class='' value='".$tempTicketTitle[0]."'>".$tempTicketTitle[1]."</option>\n";
			}
		}
		echo "				</select>\n";
		echo "		</td>\n";

		echo "		</tr>\n";
		echo "	</table>\n";
		echo "	<textarea class='editTicketText' name='editTicketText'>$ticketText</textarea>\n";
		echo "	<div class='listCard'>";
		# Link to the parent ticket
		echo "		<a class='button' href='?deleteTicketTitle=$ticketHash'>Delete Ticket</a>\n";
		echo "		<a class='button' href='?ticket=$ticketParent'>Go to Parent Ticket</a>\n";
		# draw save button
		echo "		<button class='button' type='submit'>Save Changes</button>\n";
		echo "	</div>";
		echo "	</form>\n";
		echo "	<div>\n";
		echo "Modified: ";
		timeElapsedToHuman($ticketTime);
		echo "	</div>\n";
		echo "</div>\n";
	}
	################################################################################
	function drawGraphs($parent="root",$ticketData=false){
		$parentHash=alphaHash($parent.json_encode($ticketData));
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		# build the graph if no graph exists
		#if (array_key_exists("fullChart",$_GET)){
			if (! file_exists("sub_".$parentHash.".svg")){
				# load tickets and build graph based on parent
				buildGraphData(subTickets($parent,$ticketData),"sub_$parentHash");
				#buildAllGraphs();
				reloadPage(3);
			}
		#}else{
		#	if (! file_exists("child_".$parentHash.".svg")){
		#		buildGraphData(childTickets($parent),"child_$parentHash", true, $parent);
		#		#buildAllGraphs();
		#		# reload the page to load the graph data
		#		reloadPage(3);
		#	}
		#}
		#
		#addToLog("DEBUG","Ticket flowchart based on parent ",var_export($parent,true));
		#addToLog("DEBUG","Ticket flowchart path",var_export($parentHash,true));
		#addToLog("DEBUG","Ticket flowchart path","parent =".var_export($parent,true));
		#addToLog("DEBUG","Ticket flowchart file","$parentHash.svg");
		echo "<hr>\n";
		#if (array_key_exists("fullChart",$_GET)){
			if(file_exists("sub_".$parentHash.".svg")){
				echo "<div class='titleCard'>\n";
				echo "	<div class='listCard'>\n";
				unset($_GET["fullChart"]);
				#echo "		<a class='button' href='?".http_build_query($_GET)."'>📏 Local Chart</a>\n";
				echo "		<a class='button' target='_new' href='sub_".$parentHash.".svg' download>⬇️ Download Vector Chart</a>\n";
				echo "		<a class='button' href='sub_".$parentHash.".png' download>";
				echo "			<span class='downloadIcon'>▼</span>\n";
				echo "			Download Chart";
				echo "		</a>\n";
				echo "	</div>\n";
				echo "<div class='ticketFlowchart listCard'>\n";
				include("sub_".$parentHash.".svg");
				echo "</div>\n";
				echo "</div>\n";
			}
		#}else{
		#	if(file_exists("child_".$parentHash.".svg")){
		#		echo "<div class='titleCard'>\n";
		#		echo "	<div class='listCard'>\n";
		#		unset($_GET["fullChart"]);
		#		echo "		<a class='button' href='?fullChart&".http_build_query($_GET)."'>📐 Full Chart</a>\n";
		#		echo "		<a class='button' href='child_".$parentHash.".svg' download>⬇️ Download Vector Chart</a>\n";
		#		echo "		<a class='button' href='child_".$parentHash.".png' download>⬇️ Download Chart</a>\n";
		#		echo "	</div>\n";
		#		echo "<div class='ticketFlowchart listCard'>\n";
		#		include("child_".$parentHash.".svg");
		#		echo "</div>\n";
		#		echo "</div>\n";
		#	}
		#}
	}

	################################################################################
	function buildAllGraphs($parent="root"){
		# build all ticket graphs
		$ticketTitles=loadTicketTitles(false);
		#
		foreach($ticketTitles as $ticketTitle){
			buildGraphData(subTickets($ticketTitle),"sub_".alphaHash($ticketTitle));
			buildGraphData(childTickets($ticketTitle),"child_".alphaHash($ticketTitle),true,$ticketTitle);
		}
	}
	################################################################################
	function listParentTickets($ticketId="root"){
		# build all ticket graphs
		$ticketTitles=loadTicketTitles(false);
		# list the parent tickets up to root
	}
	################################################################################
	function filterTicketStatusCount($ticketStatus,$ticketData=false){
		# filter ticket data by the status of the tickets
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		$newTicketCount=0;
		# build a hash from the ticket data
		$countHash=md5(json_encode($ticketData).$ticketStatus);
		#
		if(file_exists("count_".$countHash.".cfg")){
			$fileData = str_replace("\n","",file_get_contents("count_".$countHash.".cfg"));
			#addToLog("DEBUG","countTickets count","Loading previous cached count '$fileData'");
			# return the cached file
			return $fileData;
		}else{
			foreach($ticketData as $ticket){
				if ($ticket[4] == $ticketStatus){
					$newTicketCount+=1;
				}
			}
			#addToLog("DEBUG","countTickets count","Writing found count = ".$newTicketCount);
			# write the found count to the cache file
			file_put_contents("count_".$countHash.".cfg", $newTicketCount);
			return $newTicketCount;
		}
	}
	################################################################################
	function filterTicketStatus($ticketStatus,$ticketData=false){
		# filter ticket data by the status of the tickets
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		$newTicketData=Array();
		foreach($ticketData as $ticket){
			if ($ticket[4] == $ticketStatus){
				$newTicketData=array_merge($newTicketData,Array($ticket));
			}
		}
		# return the new filtered ticket data
		return $newTicketData;
	}
	################################################################################
	function countTickets($ticketData=false){
		# count the tickets in ticketData
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		#addToLog("DEBUG","countTickets data",json_encode($ticketData));
		# build a hash from the ticket data
		$countHash=md5(json_encode($ticketData));
		#
		if(file_exists("count_".$countHash.".cfg")){
			$fileData = str_replace("\n","",file_get_contents("count_".$countHash.".cfg"));
			#addToLog("DEBUG","countTickets count","Loading previous cached count '$fileData'");
			# return the cached file
			return $fileData;
		}else{
			$foundCount=count($ticketData);
			#if($foundCount==0){
			#}
			#addToLog("DEBUG","countTickets count","Writing found count = ".$foundCount);
			# write the found count to the cache file
			file_put_contents("count_".$countHash.".cfg", $foundCount);
			return $foundCount;
		}
	}
	################################################################################
	function listTickets($parent="root",$ticketData=false){
		# draw webpage listing all tickets
		if(! $ticketData){
			# load default ticket data if none is given
			$ticketData=loadTicketData();
		}
		#
		$subTicketData=subTickets($parent,$ticketData);
		$subTicketCount=countTickets($subTicketData);
		# draw all the sub ticket information
		if ($subTicketCount > 1){
			# list the counts of subtickets based on status
			$completedTicketCount=filterTicketStatusCount("Completed",$subTicketData);
			$unfinishedTicketCount=filterTicketStatusCount("Unfinished",$subTicketData);
			$wontFixTicketCount=filterTicketStatusCount("Will-Not-Fix",$subTicketData);
			$closedTicketCount=filterTicketStatusCount("Closed",$subTicketData);
			echo "	<div>\n";
			echo "	<table>\n";
			echo "		<tr>\n";
			echo "			<th>\n";
			echo "				Completed ✅\n";
			echo "			</th>\n";
			echo "			<th>\n";
			echo "				Unfinished ⚙️\n";
			echo "			</th>\n";
			echo "			<th>\n";
			echo "				Will-Not-Fix ⚠️\n";
			echo "			</th>\n";
			echo "			<th>\n";
			echo "				Closed ⛔\n";
			echo "			</th>\n";
			echo "		</tr>\n";
			echo "		<tr>\n";
			echo "			<td>\n";
			echo "				".$completedTicketCount."\n";
			echo "			</td>\n";
			echo "			<td>\n";
			echo "				".$unfinishedTicketCount."\n";
			echo "			</td>\n";
			echo "			<td>\n";
			echo "				".$wontFixTicketCount."\n";
			echo "			</td>\n";
			echo "			<td>\n";
			echo "				".$closedTicketCount."\n";
			echo "			</td>\n";
			echo "		</tr>\n";
			echo "	</table>\n";
			echo "	</div>\n";
		}
		#
		$childTicketData=childTickets($parent,$ticketData);
		# get the hash for the graph
		$parentHash=alphaHash($parent);
		echo "<hr>\n";
		#
		foreach($childTicketData as $ticket){
			$ticketTitle=$ticket[0];
			$ticketText=$ticket[1];
			$ticketPriority=$ticket[2];
			$ticketStatus=$ticket[4];
			# draw the tickets connected to the given parent
			echo "<div class='inputCard $ticketPriority $ticketStatus'>";
			echo "	<h2>\n";
			echo "		<a href='?ticket=".$ticket[6]."' class=''>\n";
			echo "			".$ticketTitle."\n";
			echo "		</a>\n";
			echo "	</h2>\n";
			echo "<div>\n";
			echo "	".$ticketText."\n";
			echo "</div>\n";
			echo "	<div>\n";
			# list the number of subtickets
			$foundSubTickets=subTickets($ticket[6],$ticketData);
			$subTicketCount=countTickets($foundSubTickets);
			if ($subTicketCount > 1){
				echo "		<span class='left'>Sub Tickets: ";
				echo "			$subTicketCount";
				echo "		</span>\n";
			}
			echo "		<span class='right'>Status: ";
			echo "			".$ticketStatus." ";
			if ($ticketStatus == "Completed"){
				echo "✅";
			}else if ($ticketStatus == "Unfinished"){
				echo "⚙️";
			}else if ($ticketStatus == "Will-Not-Fix"){
				echo "⚠️";
			}else if ($ticketStatus == "Closed"){
				echo "⛔";
			}
			echo "\n";
			echo "		</span>\n";
			echo "	</div>\n";
			#echo "<div>".json_encode($foundSubTickets)."</div>";
			if ($subTicketCount > 1){
				# list the counts of subtickets based on status
				$completedTicketCount=filterTicketStatusCount("Completed",$foundSubTickets);
				$unfinishedTicketCount=filterTicketStatusCount("Unfinished",$foundSubTickets);
				$wontFixTicketCount=filterTicketStatusCount("Will-Not-Fix",$foundSubTickets);
				$closedTicketCount=filterTicketStatusCount("Closed",$foundSubTickets);
				#
				echo "	<div>\n";
				echo "	<table>\n";
				echo "		<tr>\n";
				if ($completedTicketCount > 0){
					echo "			<th>\n";
					echo "				Completed ✅\n";
					echo "			</th>\n";
				}
				if ($unfinishedTicketCount > 0){
					echo "			<th>\n";
					echo "				Unfinished ⚙️\n";
					echo "			</th>\n";
				}
				if ($wontFixTicketCount > 0){
					echo "			<th>\n";
					echo "				Will-Not-Fix ⚠️\n";
					echo "			</th>\n";
				}
				if ($closedTicketCount > 0){
					echo "			<th>\n";
					echo "				Closed ⛔\n";
					echo "			</th>\n";
				}
				echo "		</tr>\n";
				echo "		<tr>\n";
				if ($completedTicketCount > 0){
					echo "			<td>\n";
					echo "				".$completedTicketCount."\n";
					echo "			</td>\n";
				}
				if ($unfinishedTicketCount > 0){
					echo "			<td>\n";
					echo "				".$unfinishedTicketCount."\n";
					echo "			</td>\n";
				}
				if ($wontFixTicketCount > 0){
					echo "			<td>\n";
					echo "				".$wontFixTicketCount."\n";
					echo "			</td>\n";
				}
				if ($closedTicketCount > 0){
					echo "			<td>\n";
					echo "				".$closedTicketCount."\n";
					echo "			</td>\n";
				}
				echo "		</tr>\n";
				echo "	</table>\n";
				echo "	</div>\n";
			}

			echo "</div>\n";
		}
	}
	#######################################################################
	# check for api usage
	#######################################################################
	if (array_key_exists("ticketTitle",$_POST)){
		# create a new ticket in the ticket system
		$ticketTitle=$_POST["ticketTitle"];
		$ticketText=$_POST["ticketText"];
		$ticketPriority=$_POST["ticketPriority"];
		$ticketParent=$_POST["ticketParent"];
		# build the ticket in the csv file
		$ticketHash=newTicket($ticketTitle,$ticketText,$ticketPriority,$ticketParent);
		if($ticketHash){
			# load tickets and build graph based on parent
			buildGraphData(subTickets($ticketParent),"sub_".alphaHash($ticketParent));
			buildGraphData(childTickets($ticketParent),"child_".alphaHash($ticketParent),true,$ticketParent);
			# redirect to the newly added ticket
			redirect("?ticket=$ticketHash");
		}
	}
	if (array_key_exists("editTicketText",$_POST)){
		# create a new ticket in the ticket system
		$ticketTitle=$_POST["editTicketTitle"];
		$ticketText=$_POST["editTicketText"];
		$ticketPriority=$_POST["editTicketPriority"];
		$ticketParent=$_POST["editTicketParent"];
		$ticketStatus=$_POST["editTicketStatus"];
		$ticketHash=$_POST["editTicketHash"];
		# build the ticket in the csv file
		$ticketHash=editTicket($ticketTitle,$ticketText,$ticketPriority,$ticketParent,$ticketStatus,$ticketHash);
		if($ticketHash){
			# load tickets and build graph based on parent
			buildGraphData(subTickets($ticketParent),"sub_".alphaHash($ticketParent));
			buildGraphData(childTickets($ticketParent),"child_".alphaHash($ticketParent),true,$ticketParent);

			# redirect to the newly added ticket
			redirect("?ticket=$ticketHash");
		}
	}
	if (array_key_exists("deleteTicketTitle",$_GET)){
		# create a new ticket in the ticket system
		$ticketTitle=$_GET["deleteTicketTitle"];
		# remove the ticket from the csv file used as database
		deleteTicket($ticketTitle);
		# load tickets and build graph based on parent
		#buildGraphData(subTickets($ticketParent),"sub_".alphaHash($ticketParent));
		#buildGraphData(childTickets($ticketParent),"child_".alphaHash($ticketParent),true,$ticketParent);

		# redirect to the root
		redirect("?");
	}
?>
<!--
########################################################################
# 2web ticket system application
# Copyright (C) 2024  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
########################################################################
-->
<?php
	ini_set('display_errors', 1);
	################################################################################
?>
<html>
<head>
	<title>2web Ticket System</title>
	<link rel='stylesheet' href='/style.css'>
	<script src='/2webLib.js'></script>
	<style>
		.editTicketTitle{
			font-size: 3rem;
		}
		.ticketFlowchart{
			max-width: 100%;
			display: inline-block;
			text-align: center;
		}
		area:hover{
			background-color: black;
		}
		svg{
			max-height: 80dvh;
		}
		.editTicketText{
			width: 100%;
			min-height: 8rem;
		}
	</style>
</head>
<body>
<?PHP
	include('/var/cache/2web/web/header.php');
	if (array_key_exists("error",$_GET)){
		echo $_GET["error"];
	}
?>
<div class='settingListCard'>
	<a href='?'><h1>Tickets</h1></a>
	<div>
	</div>
<?PHP
		# if no flowchart exists yet
		#if(! file_exists("tickets.png")){
		#	# save the existing data to create a flowchart
		#	saveTicketData(loadTicketData());
		#	# reload the page after the flowchart is generated
		#	reloadPage();
		#}else{
			#echo "<div class='ticketFlowchart listCard'>";
			# load the ticket map
			#if(file_exists("tickets.map")){
			#	echo file_get_contents("tickets.map");
			#}
			# question mark at end causes browser not to cache the image
			#echo "	<img id='' usemap='#ticketFlowChartGraph' class='' loading='lazy' src='tickets.png?'>\n";
			#echo "</div>";
			#echo "<div class='ticketFlowchart listCard'>";
			#echo "	<img id='' class='' loading='lazy' src='tickets.svg?'>\n";
			#echo "</div>";
			#echo "<div class='ticketFlowchart listCard'>";
			#include("tickets.svg");
			#echo "</div>";
			#echo "<hr>";
		#}

		# load the ticket data
		$ticketTitles=loadTicketTitles();
		# load default ticket data if none is given
		$ticketData=loadTicketData();

		#echo "[DEBUG]: ticketTitles = ".var_export($ticketTitles,true)."<br>\n";
		# draw the tickets on the page
		if (array_key_exists("ticket",$_GET)){
			# list only tickets under the current ticket
			drawGraphs($_GET["ticket"],$ticketData);
			showTicket(urldecode($_GET["ticket"]),$ticketData);
			listTickets(urldecode($_GET["ticket"]),$ticketData);
		}else{
			drawGraphs("root",$ticketData);
			# list all the tickets in root
			listTickets("root",$ticketData);
			# link the settings file into the local directory for download button
			if (! file_exists("tickets.csv")){
				symlink("/etc/2web/applications/settings/ticket/tickets.csv", "tickets.csv");
			}
		}
		# create the add tickets form
		echo "<div class='inputCard'>";
		echo "	<h2>Add New</h2>";
		echo "	<form class='buttonForm' method='post'>\n";
		echo "		<input class='' type='text' name='ticketTitle' placeholder='Title' />\n";
		echo "		<textarea class='' type='text' name='ticketText' placeholder='Content of the ticket...' ></textarea>\n";
		echo "		<select name='ticketStatus'>\n";
		echo "			<option class='' value='Unfinished'>Unfinished</option>\n";
		echo "			<option class='' value='Completed'>Completed</option>\n";
		echo "			<option class='' value='Will-Not-Fix'>Will-Not-Fix</option>\n";
		echo "			<option class='' value='Closed'>Closed</option>\n";
		echo "		</select>\n";
		echo "		<select name='ticketPriority'>\n";
		echo "			<option class='' value='Trivial'>Trivial</option>\n";
		echo "			<option class='' value='Low'>Low</option>\n";
		echo "			<option class='' value='Medium'>Medium</option>\n";
		echo "			<option class='' value='High'>High</option>\n";
		echo "			<option class='' value='CRITICAL'>CRITICAL</option>\n";
		echo "		</select>\n";
		echo "		<select name='ticketParent'>\n";
		echo "			<option class='' value='root'>root</option>\n";
		foreach($ticketTitles as $ticketTitle){
			if (array_key_exists("ticket",$_GET)){
				if ($ticketTitle[0] == $_GET["ticket"]){
					echo "			<option selected class='' value='".$ticketTitle[0]."'>".$ticketTitle[1]."</option>\n";
				}else{
					echo "			<option class='' value='".$ticketTitle[0]."'>".$ticketTitle[1]."</option>\n";
				}
			}else{
				echo "			<option class='' value='".$ticketTitle[0]."'>".$ticketTitle[1]."</option>\n";
			}
		}
		echo "		</select>\n";

		echo "		<button class='button' type='submit'>Add New Ticket</button>\n";
		echo "	</form>\n";
		echo "</div>\n";
		echo "<div class='titleCard'>";
		echo "	<div class='listCard'>";
		echo "		<a class='button' href='tickets.csv' download>";
		echo "			<span class='downloadIcon'>▼</span>\n";
		echo "			Download Ticket Spreadsheet";
		echo "		</a>\n";
		echo "	</div>\n";
		echo "</div>\n";
	?>
</div>
<?PHP
	include('/var/cache/2web/web/footer.php');
?>
</body>
</html>
