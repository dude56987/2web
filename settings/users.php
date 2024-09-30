<?PHP
include("/usr/share/2web/2webLib.php");
requireAdmin();
?>
<!--
########################################################################
# 2web system settings
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
<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
error_reporting(E_ALL);
################################################################################
include($_SERVER['DOCUMENT_ROOT']."/header.php");
include("settingsHeader.php");
?>
<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#loginInactivityTimeout'>Login Inactivity Timeout</a></li>
		<li><a href='#manageUsers'>Manage Users</a></li>
		<li><a href='#addNewUser'>Add New Administrator</a></li>
		<li><a href='#addNewBasicUser'>Add New Basic User</a></li>
		<li><a href='#lockUnlockGroups'>Lock Unlock Groups</a></li>
	</ul>
</div>

<div id='loginInactivityTimeout' class='inputCard'>
<h2>Login Inactivity Timeout</h2>
<ul>
	<li>
		Set the time before a session is automatically ended from inactivity and the user must log in again.
	</li>
<?PHP
	$currentTimeoutSeconds=ini_get('session.gc_maxlifetime');

	$currentTimeoutHours=floor(floor($currentTimeoutSeconds / 60) / 60);
	# subtract new found number of seconds in hours
	$currentTimeoutSeconds-=floor(($currentTimeoutHours * 60) * 60);
	$currentTimeoutMinutes=$currentTimeoutSeconds / 60;
	$currentTimeoutSeconds-=($currentTimeoutMinutes * 60);
	echo "<li>";
	echo "The session will currently timeout after ".$currentTimeoutHours." hours, ".$currentTimeoutMinutes." minutes, and ".$currentTimeoutSeconds." seconds.";
	echo "</li>";
?>
</ul>
<form action='admin.php' method='post' class='buttonForm'>
	<?PHP
		echo "<h3>Set Session Timeout</h3>";
		$timeoutMinutes = file_get_contents("/etc/2web/loginTimeoutMinutes.cfg");
		$timeoutHours = file_get_contents("/etc/2web/loginTimeoutHours.cfg");
		echo "<table class='controlTable'>";
		echo "<tr>";
		echo "	<th>";
		echo "		Hours";
		echo "	</th>";
		echo "	<th>";
		echo "		Minutes";
		echo "	</th>";
		echo "</tr>";
		echo "<tr>";
		echo "	<td>";
		echo "		<input type='number' name='setSessionTimeoutHours' placeholder='Session Timeout Hours...' min='0' max='72' value='".$timeoutHours."' />";
		echo "	</td>";
		echo "	<td>";
		echo "		<input type='number' name='setSessionTimeoutMinutes' placeholder='Session Timeout Minutes...' min='0' max='59' value='".$timeoutMinutes."' />";
		echo "	</td>";
		echo "<tr>";
		echo "</table>";
	?>
	<button type='submit' class='button'>Change Session Timeout</button>
</form>
</div>

<div class='inputCard' id='userIndex'>
	<h2>User Index</h2>
		<ul>
		<?PHP
		# for each existing user create a jump link
		$foundUsers=scanDir("/etc/2web/users/");
		$foundUsers=array_diff($foundUsers,Array('..','.','.placeholder'));
		foreach( $foundUsers as $foundUser){
			$foundUser = str_replace(".cfg","",$foundUser);
			echo "<li>";
			echo "<a href='#user_$foundUser'>$foundUser</a>";
			echo "</li>";
		}
		?>
		</ul>
	</div>
</div>

<div class='settingListCard' id='manageUsers'>
<h2>Active Users</h2>
<?PHP
	# get a list of the groups
	$groups=scanDir("/etc/2web/groups/");
	$groups=array_diff($groups,Array('..','.','.placeholder'));
	# for each existing user build a card and place group enable/disable buttons for each group and a remove user button
	$foundUsers=scanDir("/etc/2web/users/");
	$foundUsers=array_diff($foundUsers,Array('..','.','.placeholder'));
	foreach( $foundUsers as $foundUser){
		# remove extension from filename
		$foundUser = str_replace(".cfg","",$foundUser);
		# build the user configuration panel
		echo "<div id='user_$foundUser' class='inputCard'>";
		echo "<h1>".$foundUser."</h1>";
		echo "<table class='controlTable'>";
		echo "<tr>";
		echo "	<th>";
		echo "		Group";
		echo "	</th>";
		echo "	<th>";
		echo "		Access Permissions";
		echo "	</th>";
		echo "</tr>";
		foreach( $groups as $group){
			echo "<tr>";
			echo "	<td>";
			echo "		$group";
			echo "	</td>";
			echo "	<td>";
			# build buttons to add or remove users from the groups that exist
			if (file_exists("/etc/2web/groups/".$group."/".$foundUser.".cfg")){
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<input type='text' name='removeUserFromGroup_userName' value='$foundUser' hidden>";
				echo "	<input type='text' name='removeUserFromGroup_groupName' value='$group' hidden>";
				echo "	<button class='button' type='submit' name='' value=''>🟢 Disable</button>\n";
				echo "	</form>\n";
			}else{
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<input type='text' name='addUserToGroup_userName' value='$foundUser' hidden>";
				echo "	<input type='text' name='addUserToGroup_groupName' value='$group' hidden>";
				echo "	<button class='button' type='submit' name='' value=''>◯  Enable</button>\n";
				echo "	</form>\n";
			}
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		# add the remove user button
		echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
		echo "	<input type='text' name='removeUser' value='$foundUser' hidden>";
		echo "	<button class='button' type='submit' name='' value=''>❌ Remove User</button>\n";
		echo "	</form>\n";

		# add the remove user button
		echo "</div>";
	}
?>
	<div id='addNewUser' class='inputCard'>
	<form action='admin.php' method='post'>
		<h2>Add New System Administrator</h2>
		<ul>
			<li>Add at least one administrator to lock the settings in this web interface.</li>
			<ul>
				<li>
					<input width='60%' type='text' name='newUserName' placeholder='NEW USERNAME' required>
				</li>
				<li>
					<input width='60%' type='password' name='newUserPass' placeholder='NEW USER PASSWORD' required>
				</li>
				<li>
					<input width='60%' type='password' name='newUserPassVerify' placeholder='VERIFY PASSWORD' required>
				</li>
			</ul>
		</ul>
		<button class='button' type='submit'>Add New Administrator</button>
	</form>
	</div>

	<div id='addNewBasicUser' class='inputCard'>
	<form action='admin.php' method='post'>
		<h2>Add New User</h2>
		<ul>
			<li>Add a basic user for logging in to use locked sections of the website</li>
			<li>If no groups are locked this type of user has no special privliges.</li>
			<ul>
				<li>
					<input width='60%' type='text' name='newBasicUserName' placeholder='NEW USERNAME' required>
				</li>
				<li>
					<input width='60%' type='password' name='newUserPass' placeholder='NEW USER PASSWORD' required>
				</li>
				<li>
					<input width='60%' type='password' name='newUserPassVerify' placeholder='VERIFY PASSWORD' required>
				</li>
			</ul>
		</ul>
		<button class='button' type='submit'>Add New User</button>
	</form>
	</div>
</div>


<div class='settingListCard' id='lockUnlockGroups'>
	<h2>Locked Groups</h2>
<?PHP
	# list the lock status of each group permisssions
	foreach( $groups as $group){
		echo "<div class='inputCard'>";
		echo "<h2 id='groupLock_".$group."'>".$group."</h2>";
		if ($group == "2web"){
			echo "<p>";
			echo "	This is the base group for the site. If this group is locked nothing is accessable without a login.";
			echo "</p>";
		}else if ($group == "admin"){
			echo "<p>";
			echo "	This is the group permission that allows access to these settings and permissions to access all groups.";
			echo "</p>";
			echo "<p>";
			echo "	This group can not be locked or unlocked. To allow access to settings without a username you must remove all users from the system.";
			echo "</p>";
		}
		# the admin group can not be locked or unlocked
		if ($group != "admin"){
			# build buttons to add or remove users from the groups that exist
			if (file_exists("/etc/2web/lockedGroups/".$group.".cfg")){
				echo "<p>";
				echo "	The group '$group' is currently only accessable by users who are logged in with permissions to access '$group'.";
				echo "</p>";
				echo "";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<input type='text' name='unlockGroup' value='$group' hidden>";
				echo "	<button class='button' type='submit' name='' value=''>🔓 Unlock</button>\n";
				echo "	</form>\n";
			}else{
				echo "<p>";
				echo "	The group '$group' currently has no permission requirements.";
				echo "</p>";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "	<input type='text' name='lockGroup' value='$group' hidden>";
				echo "	<button class='button' type='submit' name='' value=''>🔒 Lock</button>\n";
				echo "	</form>\n";
			}
		}
		echo "</div>";
	}
?>
</div>

</div>
<?PHP
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
