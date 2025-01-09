<?php
	include("/usr/share/2web/2webLib.php");
	########################################################################
	# check group permissions based on what the player is being used for
	requireGroup("php2web");
	#######################################################################
	# check for api usage
	#######################################################################
	#	- build calculation output with bc
	#######################################################################
	if (array_key_exists("addCalc",$_GET)){
		# filter codes
		$calcData=str_replace("plus","+",$_GET["addCalc"]);
		# add text to the current calculation
		$existingData=file_get_contents("calc.cfg");
		file_put_contents("calc.cfg",($existingData.$calcData));
		# redirect to the main page
		redirect("?");
	}
	if (array_key_exists("clear",$_GET)){
		# clear the existing calculation
		file_put_contents("calc.cfg","");
	}
	if (array_key_exists("calc",$_GET)){
		# load the active calculation
		$calculation=file_get_contents("calc.cfg");
		# remove any newlines
		$calculation=str_replace("\n","",$calculation);
		# run the calculation with bc
		$anwser=shell_exec('echo "'.$calculation.'" | /usr/bin/bc -l');
		$anwser=str_replace("\n","",$anwser);
		# DEBUG
		# DEBUG
		# DEBUG
		#$existingData=file_get_contents("history.cfg");
		#file_put_contents("history.cfg",($existingData.'echo "'.$calculation.'" |'." /usr/bin/bc -l\n"));
		# DEBUG
		# DEBUG
		# DEBUG
		# blank out the current calculation
		file_put_contents("calc.cfg","");
		#
		$existingData=file_get_contents("history.cfg");
		#file_put_contents("history.cfg",($existingData.$calculation."=".$anwser."\n"));
		file_put_contents("history.cfg",($existingData.$calculation."=".$anwser."\n"));
		# redirect to the calc page
		redirect("?");
	}
?>
<!--
########################################################################
# 2web Calculator
# Copyright (C) 2025  Carl J Smith
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
		#ticketFlowchart{
			width: 100%;
			display: inline-block;
			text-align: center;
		}
		area:hover{
			background-color: black;
		}
		.activeCalc{
			font-size: 2rem;
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
	<a href='?'><h1>Calc</h1></a>

	<div id='calcTop' class='inputCard'>
		<?PHP
		if(is_readable("calc.cfg")){
			echo "<pre class='activeCalc'>";
			echo file_get_contents("calc.cfg");
			echo "<a class='button right' href='?clear#calcTop'>X</a>";
			echo "</pre>";
		}
		?>
		<table>
			<tr>
				<td><a class='kodiPlayerButton' href='?addCalc=(#calcTop'>(</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=)#calcTop'>)</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=.#calcTop'>.</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=/#calcTop'>/</a></td>
			</tr>
			<tr>
				<td><a class='kodiPlayerButton' href='?addCalc=7#calcTop'>7</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=8#calcTop'>8</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=9#calcTop'>9</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=*#calcTop'>*</a></td>
			</tr>
			<tr>
				<td><a class='kodiPlayerButton' href='?addCalc=4#calcTop'>4</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=5#calcTop'>5</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=6#calcTop'>6</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=-#calcTop'>-</a></td>
			</tr>
			<tr>
				<td><a class='kodiPlayerButton' href='?addCalc=1#calcTop'>1</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=2#calcTop'>2</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=3#calcTop'>3</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=plus#calcTop'>+</a></td>
			</tr>
			<tr>
				<td><a class='kodiPlayerButton' href='?addCalc=0#calcTop'>0</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=.#calcTop'>.</a></td>
				<td><a class='kodiPlayerButton' href='?addCalc=^#calcTop'>^</a></td>
				<td><a class='kodiPlayerButton' href='?calc#calcTop'>=</a></td>
			</tr>
		</table>
	</div>
	<div class='inputCard'>
	<?PHP
		if(is_readable("history.cfg")){
			#
			$existingData=file("history.cfg");
			# reverse the list to show newest entries first
			$existingData=array_reverse($existingData);
			foreach($existingData as $previousData){
				echo "<pre>";
				echo "$previousData";
				echo "</pre>";
			}
		}else{
			echo "<pre>";
			echo "No Calculations preformed yet...";
			echo "</pre>";
		}
	?>
	</div>
</div>
<?PHP
	include('/var/cache/2web/web/footer.php');
?>
</body>
</html>
