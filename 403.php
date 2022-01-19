<?PHP
function redirect($url,$debug=false){
	if ($debug){
		echo "<hr>";
		echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
		echo "<hr>";
		ob_flush();
		flush();
	}else{
		// temporary redirect
		header('Location: '.$url,true,302);
	}
}
if (! $_SERVER['HTTPS']){
	$tempURL=str_replace("http","https",$_SERVER["HTTP_REFERER"]);
	redirect($tempURL);
}
// log the failed login
// open file with append
// add the login time, user agent string, and ip address of failed login
//
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
			<li><a onclick='window.location.reload(true)'>Reload Page</a></li>
		</ul>
		<hr>
		<p>
			You failed to login or do not have authorization to access this webpage.
			<a onclick='window.location.reload(true)'>Try Again?</a>
		</p>
	</p>
	</div>
</body>
</html>
