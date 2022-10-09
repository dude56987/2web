<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?php
################################################################################
ini_set('display_errors', 1);
# add the base php libary
include("/usr/share/2web/2webLib.php");
# add header
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>

<?php
echo "<div class='settingListCard' >";

if (array_key_exists("article",$_GET)){
	# load the article
	$article=($_GET['article']);

	# search for the article file based on the article name
	foreach($articleIndex as $foundFile){
		if ( strpos($foundFile,$article) ){
			$lineData="".$foundFile;
		}else if ( strpos( str_replace("/","_",$foundFile) , str_replace("/","_",$article) ) ){
			$lineData=str_replace("/","_",$foundFile);
		}
	}

	# article


	# read the page name and convert the hyperlinks to load the file here with article directive ?article=
	if (is_file($article)){
		# load the active article
		$articleHandle = fopen("A/".$article,'r');
		# build the article index since it will be read over and over in the next 2 loops
		$articleIndex=scandir("A/");
		while(! feof($articleHandle)){
			# read the article line by line and send large packets
			$lineData = fgets($articleHandle);
			$lineData = str_replace('../','',$lineData);
			if (strpos($lineData,"http://") || strpos($lineData,"https://")){
				$lineData = str_replace('href="','href="?redirect=',$lineData);
			}else if (strpos($lineData,"<link") || strpos($lineData,"<script")){
				# dont add these lines just add a blank line
				echo "\n";
			}else{
				# search for existing articles in links and
				foreach($articleIndex as $foundFile){
					if ( strpos($foundFile,$article) ){
						$lineData="".$foundFile;
					}else if ( strpos( str_replace("/","_",$foundFile) , str_replace("/","_",$article) ) ){
						$lineData=str_replace("/","_",$foundFile);
					}
				}
				if ( strpos($lineData,'href="?article=A/') ){
					$lineData = str_replace('href="?article=A/','href="?article=',$lineData);
				}else{
					$lineData = str_replace('href="','href="?article=',$lineData);
				}
			}

			# remove all custom stylesheets
			#$lineData = str_replace('style=".*$"','',$lineData);
			# remove links and scripts
			#$lineData = str_replace('<link=".*$>','',$lineData);


			#$lineData = str_replace('href="../../','href="index.php?article=',$lineData);
			#$lineData = str_replace('href="../','href="index.php?article=',$lineData);
			#$lineData = str_replace('href="','href="index.php?article=',$lineData);
			#$lineData = str_replace('src="../../../','src="?article=',$lineData);
			#$lineData = str_replace('img src="../../','src="?article=',$lineData);
			#$lineData = str_replace('img src="../','src="?article=',$lineData);
			#$lineData = str_replace('img src="','src="?article=',$lineData);
			# remove all custom css styles inside the html
			#if (strpos($lineData,"style=")){
			#	$lineData = preg_replace('style=\".*$\"','',$lineData);
			#}
			#$lineData = str_replace('<body>','',$lineData);
			#$lineData = str_replace('</body>','',$lineData);
			#$lineData = str_replace('<head>','',$lineData);
			#$lineData = str_replace('</head>','',$lineData);
			#echo "\n";
			//if (preg_match('..\/..\/A\/*"',$lineData)){
				//echo str_replace('..\/..\/A\/*"','',$lineData);
			//}else{
				//echo $lineData;
			//}

			# write the processed line data
			echo $lineData;
		}
	}else{
		# the article file does not exist
		echo "<h2>Failed to find Article<h2>";
		echo "Article '".$_GET['article']."' does not exist";
		$foundFiles=scandir("A/");
		#
		echo "<h2>All Found Articles</h2>";
		echo "<ul>";
		foreach($foundFiles as $foundFile){
			echo "<li>".$foundFile."</li>\n";
		}
		echo "</ul>";
	}
}else if (array_key_exists("redirect",$_GET)){
	echo "<h2>External Redirect<h2>";
	echo "<ul>";
	echo "<li>";
	echo "This link will redirect to a external website.";
	echo "</li>";
	echo "<li>";
	echo "Click the below link to proceed.";
	echo "</li>";
	echo "</ul>";
	# build the link to the extrnal link
	echo "<a class='button' href=".$_GET['redirect'].">".$_GET['redirect']."</a>";
}else{
	#redirect("?article=A/Main_Page/Sandbox");
	header('Location: ?article=Main_Page/Sandbox',true);
}

echo "</div>";

?>
<!--
<iframe src='A/Main_Page/Sandbox' />
-->
</div>
<?php
// add the footer
include($_SERVER['DOCUMENT_ROOT']."/footer.php");
?>
</body>
</html>
