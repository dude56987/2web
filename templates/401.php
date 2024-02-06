<!--
########################################################################
# 2web 401 error webpage
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
