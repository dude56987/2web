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
# redirect to http version of page to logout, this can not be done on the settings menu
if ($_SERVER['HTTPS']){
	$tempURL=str_replace("https://","http://",$_SERVER["HTTP_REFERER"]);
	redirect($tempURL);
}
?>
