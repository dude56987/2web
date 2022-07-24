<html class='randomFanart'>
<head>
	<link rel="stylesheet" href="/style.css" />
	<title>ERROR 401</title>
</head>
<body>
<?PHP
	// rate limit on response
	#foreach(range(0,2) as $index){
	#	sleep(1);
	#	echo ".";
	#	flush();
	#	ob_flush();
	#}
?>
	<div class='titleCard'>
		<h2>ERROR 401</h2>
		<p>Login Failed! Unauthorized Access Detected!</p>
		<ul>
			<li><a href='/settings/'>Retry Login</a></li>
			<li><a onclick='window.location.reload(true)'>Reload Login Page</a></li>
			<li><a href='/'>Return to Homepage</a></li>
		</ul>
		<hr>
		<p>
			You failed to login or do not have authorization to access this webpage.
			<a href='/settings/'>Retry Login</a>
		</p>
<?PHP
// log the failed login
// open file with append
// add the login time, user agent string, and ip address of failed login
//
?>
		</p>
	</div>
</body>
</html>
