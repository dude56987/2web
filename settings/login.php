<?PHP
# show errors
ini_set('display_errors', 1);
# include the standard lib
include("/usr/share/2web/2webLib.php");
# redirect to https if the page is loaded in http
if (! $_SERVER["HTTPS"]){
	redirect("https://".$_SERVER["HTTP_HOST"]."/login.php");
}
$errorMessages="";
# start a session
startSession();
# load the login page and allow the user to setup a login session
if (array_key_exists("userLogout",$_POST)){
	# logout of the current session
	session_destroy();
	$errorMessages .= errorBanner("You have been logged out!",true);
}else if (array_key_exists("userLogin",$_POST)){
	# check the user login and password
	$password=$_POST["password"];
	$username=$_POST["userLogin"];
	# the user exists on the system
	if (file_exists("/etc/2web/users/".$username.".cfg")){
		# check the stored password hash of the user password
		$stored_pw=file_get_contents("/etc/2web/users/".$username.".cfg");
		if (password_verify($password,$stored_pw)){
			# start a new session if the password is verifyed
			# - regenerate the session id and cookie for login session
			# - remove old session
			session_regenerate_id(true);
			# reset the varaibles stored in the session
			# - this will force permissions to be checked again for the now
			#   logged in session
			session_unset();
			# set the session variables to identify the user is logged in
			$_SESSION["user"]=$username;
			# if this user is a administrator
			if (file_exists("/etc/2web/groups/admin/".$username.".cfg")){
				$is_admin=true;
			}else{
				$is_admin=false;
			}
			# scan the groups to build the user privileges in the session
			$groups = scandir("/etc/2web/groups/");
			# remove the up and current paths
			$groups = array_diff($groups ,Array('..','.','.placeholder'));
			# for each found directory list
			foreach( $groups as $group ){
				if ( $is_admin ){
					# admin has all group privileges
					$_SESSION[$group] = true;
				}else{
					# other users need privlages checked for each group
					if (file_exists("/etc/2web/groups/".$group."/".$username.".cfg")){
						$_SESSION[$group] = true;
					}else{
						$_SESSION[$group] = false;
					}
				}
			}
			# always lock the admin group
			$_SESSION["admin_locked"] = true;
			# set the login timestamp
			$_SESSION["loginTime"]=time();
			# load user settings
			#
			# load the user selected remote if the user has selected on already
			if(file_exists("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemote.cfg")){
				addToLog("DEBUG","select-remote.php","Creating remote config");
				# load the user selected remote config
				$tempRemoteData=file_get_contents("/etc/2web/user_data/".$_SESSION["user"]."/selectedRemote.cfg");
				$tempRemoteData=str_replace("\n","",$tempRemoteData);
				# set the setting in the session
				$_SESSION["selectedRemote"]=$tempRemoteData;
			}
			# post the login into the system log
			addToLog("ADMIN", "LOGIN SUCCESSFUL", "User has logged in without issue. Username='".$username."'<br>\n".getIdentity());
			sleep(2);
		}else{
			$errorMessages .= errorBanner("LOGIN FAILED INCORRECT USERNAME/PASSWORD!", true);
			# log the failure to the system log
			addToLog("ADMIN", "FAILED LOGIN", "LOGIN FAILED INCORRECT PASSWORD! Username='".$username."'<br>\n".getIdentity());
			# sleep the login script to prevent overloading
			sleep(3);
		}
	}else{
		# the username does not exist at all
		$errorMessages .= errorBanner("LOGIN FAILED INCORRECT USERNAME/PASSWORD!", true);
		addToLog("ADMIN", "FAILED LOGIN", "NO USERNAME EXISTS! Username='".$username."'<br>\n".getIdentity());
		sleep(3);
	}
}
$loggedIn=false;
# check if the user is logged in by checking the session for a username
if (array_key_exists("user", $_SESSION)){
	$loggedIn=true;
}
# if the user is logged in
if (array_key_exists("noPermission", $_GET)){
	if (array_key_exists($_GET["noPermission"], $_SESSION)){
		# verify the current user session has no permissions for the noPermission group set in the GET request
		if (! $_SESSION[$_GET["noPermission"]]){
			$errorMessages .= errorBanner("You do not have permissions to access this content! Please login to a account with the correct permissions.", true);
			#$errorMessages .= errorBanner("The group '".$_GET["noPermission"]."' is not accessable by the current user '".$_SESSION["user"]."'", true);
		}
	}else{
		$errorMessages .= errorBanner("You do not have permissions to access this content! Please login to a account with the correct permissions.", true);
	}
}

# check if users exist in the user settings
$noLogins=false;
if (count(array_diff(scanDir("/etc/2web/users/"),array(".","..",".placeholder"))) == 0){
	# if there are no logins setup then create a user session as root
	$_SESSION["user"]="root";
	$_SESSION["admin"]=true;
	# set as logged in
	$loggedIn=true;
	$noLogins=true;

	# the login failed
	$errorMessages .= errorBanner("NO ADMIN USER HAS BEEN SET! ANYONE WHO CAN ACCESS THIS PAGE CAN ACT AS ADMINISTRATORS!",true);
	$errorMessages .= errorBanner("You need to setup at least one administrator login to lock the server from being modified by anyone with access to this page.",true);
	# sleep the login script to prevent overloading
	sleep(1);
}

if ($loggedIn){
	# if the redirect has been given and the user is logged in correctly
	if (array_key_exists("redirect",$_GET)){
		# redirect if the user has permission
		if (! array_key_exists("noPermission", $_GET)){
			# redirect the login back to the page it was sent from
			if ( ! ( stripos($_GET["redirect"], "/logout.php" ) !== false ) ){
				# if there are no logins the redirect should not happen
				if (! $noLogins){
					# only redirect if the redirect is not set to the logout page, this prevents a login/logout loop
					redirect($_GET["redirect"]);
				}
			}
		}
	}

	# you are logged in show logout button
	echo "<html class='randomFanart'>";
	echo "<head>";
	echo "	<link rel='stylesheet' type='text/css' href='/style.css'>";
	echo "	<script src='/2webLib.js'></script>";
	echo "</head>";
	echo "<body>";
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
	# no login is detected draw the login window
	echo "<div class='inputCard'>";
	echo "<h1>Logout of ".ucfirst(gethostname())."</h1>";
	echo "$errorMessages";
	echo "<hr>";
	if ($noLogins){
		echo "<div class='listCard'>";
		echo "	<a class='button' href='/settings/users.php#addNewUser'>🔒 Add Administrator Login</a>";
		echo "</div>";
		echo "<div class='listCard'>";
		echo "	<a class='button' href='/settings/modules.php'>🧩 Enable Modules</a>";
		echo "</div>";
		echo "<div class='listCard'>";
		echo "	<a class='button' href='/logout.php'>⤴️ Logout</a>";
		echo "</div>";
	}else{
		echo "<div>Logged in as ".$_SESSION["user"]."</div>";
		echo "<hr>";
		echo "<a class='button' href='/logout.php'>🔒 Logout</a>";
	}
	echo "<hr>";
	echo "</div>";
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
	echo "</body>";
	echo "</html>";
}else{
	# you are logged out
	echo "<html class='randomFanart'>";
	echo "<head>";
	echo "	<link rel='stylesheet' type='text/css' href='/style.css'>";
	echo "	<script src='/2webLib.js'></script>";
	echo "</head>";
	echo "<body>";
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
	# no login is detected draw the login window
	echo "<div class='inputCard'>";
	echo "<h1>Login To ".ucfirst(gethostname())."</h1>";
	echo "$errorMessages";
	echo "<form method='post'>";
	echo "<hr>";
	echo "<input class='loginName' type='text' autocorrect='off' autocapitalize='none' name='userLogin' placeholder='Username...' autofocus>";
	echo "<hr>";
	echo "<input class='loginPass' type='password' autocorrect='off' autocapitalize='none' name='password' placeholder='Password...'>";
	echo "<hr>";
	echo "<input class='button' type='submit' value='Login'>";
	echo "</form>";
	echo "</div>";
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
	echo "</body>";
	echo "</html>";
}
?>
