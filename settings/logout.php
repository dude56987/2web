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

# first run logout the user, then redirect them to the homepage
if (strpos($_SERVER['REQUEST_URI'], "settings/")){
	# redirect after logout and strip url of login infomation
	redirect("https://logout:logout@".gethostname().".local/logout.php");
}else{
	redirect("http://".gethostname().".local/");
}

# redirect to http version of page to logout, this can not be done on the settings menu
#if ($_SERVER['HTTPS']){
#	$tempURL=str_replace("https://","http://",$_SERVER["HTTP_REFERER"]);
#	redirect($tempURL);
#}
?>
