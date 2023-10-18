<?PHP
# show errors
ini_set('display_errors', 1);
# include the standard lib
include("/usr/share/2web/2webLib.php");
$errorMessages="";
# start a session
session_start();
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
			# set the session variables
			$_SESSION["user"]=$username;
			$_SESSION["admin"]=true;
			addToLog("ADMIN", "LOGIN SUCCESSFUL", "User has logged in without issue. Username='".$username."'<br>\n".getIdentity());
			sleep(2);
		}else{
			$errorMessages .= errorBanner("LOGIN FAILED INCORRECT PASSWORD!", true);
			addToLog("ADMIN", "FAILED LOGIN", "LOGIN FAILED INCORRECT PASSWORD! Username='".$username."'<br>\n".getIdentity());
			# sleep the login script to prevent overloading
			sleep(3);
		}
	}else{
		# the username does not exist at all
		$errorMessages .= errorBanner("The username '".$username."' does not exist.", true);
		addToLog("ADMIN", "FAILED LOGIN", "NO USERNAME EXISTS! Username='".$username."'<br>\n".getIdentity());
		sleep(3);
	}
}
$loggedIn=false;
if (array_key_exists("admin",$_SESSION)){
	if ($_SESSION["admin"]){
		# the user is logged in with administrative privileges
		$loggedIn=true;
	}
}
# if the user is logged in
if ($loggedIn){
	# if the redirect has been given
	if (array_key_exists("redirect",$_GET)){
		# redirect the login back to the page it was sent from
		redirect($_GET["redirect"]);
	}
}

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
	echo "<h1>Logout of ".gethostname()."</h1>";
	echo "$errorMessages";
	echo "<hr>";
	if ($noLogins){
		echo "<div class='listCard'>";
		echo "	<a class='button' href='/settings/system.php'>Add Administrator Login</a>";
		echo "</div>";
		echo "<div class='listCard'>";
		echo "	<a class='button' href='/logout.php'>Logout</a>";
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
	echo "<h1>Login To ".gethostname()."</h1>";
	echo "$errorMessages";
	echo "<form method='post'>";
	echo "<hr>";
	echo "<input class='loginName' type='text' autocorrect='off' autocapitalize='none' name='userLogin' placeholder='username...'>";
	echo "<hr>";
	echo "<input class='loginPass' type='password' autocorrect='off' autocapitalize='none' name='password' placeholder='password...'>";
	echo "<hr>";
	echo "<input class='button' type='submit' value='login'>";
	echo "</form>";
	echo "</div>";
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
	echo "</body>";
	echo "</html>";
}
?>
