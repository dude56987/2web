<?PHP
########################################################################
# 2web 403 error page
# Copyright (C) 2023  Carl J Smith
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
// login must be on https
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
			<li><a onclick='window.location.reload(true)'>Retry Login</a></li>
			<li><a href='/'>Return to Homepage</a></li>
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
