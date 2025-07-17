<!--
########################################################################
# 2web 403 error page
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
<?PHP
	include("/usr/share/2web/2webLib.php");
	# build the redirect url after login
	$tempURL="https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
	$userAgent=$_SERVER["HTTP_USER_AGENT"];
	$remoteIP=$_SERVER["REMOTE_ADDR"];
	# log the event
	addToLog("ERROR","403 Unauthorized Access Detected!","A attempt was made to access the page '$tempURL' by remote ip '$remoteIP' using '$userAgent' as the user agent. This may or may not be a big deal depending on your security needs.");
	# start the session to access session variables
	startSession();
	# check for the username,
	if (array_key_exists("user",$_SESSION)){
		# if the user is logged in do not redirect to the login
		# - this will cause a infinite loop
		$redirect=false;
	}else{
		$redirect=true;
	}
	# if the user should be auto redirected to the login page
	if ($redirect){
		# redirect to the login page with the referer back to the https version of the failed page
		redirect("https://".$_SERVER["HTTP_HOST"]."/login.php?redirect=".$tempURL);
	}
	include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<html class='randomFanart'>
<head>
	<link rel="stylesheet" href="/style.css" />
	<title>ERROR 403</title>
</head>
<body>
	<div class='titleCard'>
		<h2>ERROR 403</h2>
		<p>Access Forbidden! Unauthorized Access Detected!</p>
		<ul>
			<?PHP
				if (array_key_exists("user",$_SESSION)){
					echo "<li>You are logged in as '".$_SESSION["user"]."', but do not have permission to access this page.If you wish to be given permission to access this resource, please contact the server administrator.</li>";
				}else{
					echo "<li><a href='"."https://".$_SERVER["HTTP_HOST"]."/login.php?redirect=".$tempURL."'>Retry Login</a></li>";
				}
			?>
			<li><a href='/'>Return to Homepage</a></li>
		</ul>
		<hr>
		<p>
			You can attempt to reload the page if this issue was resolved.
			<a onclick='window.location.reload(true)'>Reload Page?</a>
		</p>
	</p>
	</div>
	<?PHP
	include("footer.php");
	?>
</body>
</html>
