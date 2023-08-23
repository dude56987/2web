<!--
########################################################################
# 2web wiki viewer
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
-->
<html id='top' class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2webLib.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
	<?PHP
	// set wiki title from metadata
	if (is_file("M/Title")){
		$wikiTitle = file_get_contents("M/Title");
		echo "<title>".$wikiTitle."</title>";
	}
	?>
	<style>
		html{
			font-size: 1rem;
		}
	</style>
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
<div class='titleCard'>
	<h1>
		<?php
			echo $wikiTitle;
		?>
		<img id='spinner' src='/spinner.gif' />
	</h1>
	<a class='button' href='?home'>⛵ Wiki Homepage</a>
	<a class='button' href='?random'>🎲 Random Article</a>
	<a class='button' href='?index'>📋 Article Index</a>
	<hr>
	<form class='searchBoxForm' method='get'>
		<input id='searchBox' class='searchBox' type='text' name='search' placeholder='Wiki Search...' >
	</form>
</div>
<hr>
<?php
echo "<div class='settingListCard' >";

if (array_key_exists("search",$_GET)){
	echo "<h1>";
	echo "Searching ".$wikiTitle." For ".$_GET['search'];
	echo "</h1>";
	# search though the article files for the search term
	$foundFiles=scandir("A/");
	foreach($foundFiles as $foundFile){
		if (stripos($foundFile,$_GET['search'])){
			# check each filename for the search term
			echo "<div class='titleCard'>";
			echo "<a href='?article=".$foundFile."'>".$foundFile."</a>\n";
			echo "</div>";
			flush();
			ob_flush();
		}else if(is_file("A/".$foundFile)){
			# read each file and search line by line
			$articleHandle = fopen("A/".$foundFile,'r');
			while(! feof($articleHandle)){
				$lineData = fgets($articleHandle, 512);
				# remove meta lines that contain redirects
				$lineData = strip_tags($lineData);
				#$lineData = str_replace("<meta","",$lineData);
				#$lineData = str_replace("<span","",$lineData);
				#$lineData = str_replace("<div","",$lineData);
				#$lineData = str_replace("</","",$lineData);
				# highlight found search terms
				$lineData = str_replace($_GET['search'],("<span class='highlightText'>".$_GET['search']."</span>"),$lineData);
				if(stripos($lineData,$_GET['search'])){
					# check each files contents for the search term
					echo "<div class='titleCard button'>";
					echo "<h2>";
					echo "<a href='?article=".$foundFile."'>".$foundFile."</a>\n";
					echo "</h2>";
					echo "<div class='foundSearchContentPreview'>";
					echo $lineData;
					echo "</div>";
					echo "</div>";
					flush();
					ob_flush();
					break;
				}
			}
		}
	}
}else if (array_key_exists("home",$_GET)){
	# try and load the home page from the /M/MainPage file
	if(file_exists("M/MainPage")){
		# find main page in metadata
		$mainPagePath=file_get_contents("M/MainPage");
		# remove newlines
		$mainPagePath=str_replace("\n","",$mainPagePath);
		if(is_file($mainPagePath)){
			# redirect to the main page
			header('Location: ?article='.$mainPagePath,true);
		}else if(is_file($mainPagePath."/Sandbox")){
			# redirect to the main page
			header('Location: ?article='.$mainPagePath."/Sandbox",true);
		}
	}else if(is_file("A/Main_Page/Sandbox")){
		header('Location: ?article=Main_Page/Sandbox',true);
	}else if (is_file("A/Main_Page")){
		header('Location: ?article=Main_Page',true);
	}else{
		# go to the index if a home page could not be found for the wiki
		header('Location: ?index',true);
	}
}else if (array_key_exists("index",$_GET)){
		$foundFiles=scandir("A/");
		echo "<h2>All Articles Index<h2>";
		echo "<ul>";
		foreach($foundFiles as $foundFile){
			echo "<li><a href='?article=".$foundFile."'>?article=".$foundFile."</li>\n";
		}
		echo "</ul>";
}else if (array_key_exists("random",$_GET)){
	$foundFiles=scandir("A/");
	# shuffle files to pick a random one from the top of the deck
	shuffle($foundFiles);
	echo "<h2>Random Found Article</h2>";
	echo "<ul>";
	echo "<li><a href='?article=".$foundFiles[0]."'>?article=".$foundFiles[0]."</li>\n";
	echo '<meta http-equiv="refresh" content="0;url=?article='.$foundFiles[0].'" />';
	echo "</ul>";
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
}else if (array_key_exists("article",$_GET)){
	# load the article
	$article=($_GET['article']);


	# build the article index since it will be read over and over in the next 2 loops
	$articleIndex=scandir("A/");

	# search for the article file based on the article name
	#foreach($articleIndex as $foundFile){
	#	if ( strpos($foundFile,$article) ){
	#		$lineData="".$foundFile;
	#	}else if ( strpos( str_replace("/","_",$foundFile) , str_replace("/","_",$article) ) ){
	#		$lineData=str_replace("/","_",$foundFile);
	#	}
	#}

	#echo "<div>".var_dump(stripos($article,"A/"))."</div>";

	# add the A/ to the front of the article if it does not exist
	if( ($article[0]=="A") && ($article[1] == "/") ){
		echo "<!-- Article prefix already exists --!>";
	}else{
		$article = "A/".$article;
	}

	#$article = $article;

	# article
	#echo "<div>".$article."</div>";
	#echo "<div>".file_get_contents($article)."</div>";

	# read the page name and convert the hyperlinks to load the file here with article directive ?article=
	#if (is_file("A/".$article)){
	if (is_file($article)){
		# load the active article
		#$articleHandle = fopen("A/".$article,'r');
		#$= file_get_contents($article);
		$articleHandle = fopen($article,'r');
		$cleanFileData = "";
		while(! feof($articleHandle)){
			#foreach ($fullFileContents as $lineData ){
			# read the article line by line and send large packets
			$lineData = fgets($articleHandle);

			# convert summaries from description tags
			#$lineData = str_replace("<summary>","<h3>",$lineData);
			#$lineData = str_replace("</summary>","</h3>",$lineData);
			#$lineData = preg_replace("/<details.*[0,]>/","<p>",$lineData);
			#$lineData = preg_replace("/<\/details.*[0,]>/","</p>",$lineData);

			#$lineData = preg_replace('/<script>.*<\/script>/',"",$lineData);

			#$lineData = str_replace('style="*"','',$lineData);
			$lineData = str_replace('../','',$lineData);
			# remove all style tags from line
			$lineData = preg_replace('/style=".*"/',"",$lineData);
			# replace redirect urls
			$lineData = str_replace('url=','url=?article=',$lineData);
			#$lineData = preg_replace('/<link.*>/',"",$lineData);
			# check for metadata redirect
			#if (strpos($lineData,'http-equiv="refresh"')){
			#	#echo "\nLineData=".$lineData."<br>\n";
			#	# redirect to the correct page
			#	$lineData = preg_replace("/^.*?contents=\"0;url=/",'',$lineData);
			#	#echo "\nLineData=".$lineData."<br>\n";
			#	$lineData = preg_replace("/\" \/\>.*?\n/",'',$lineData);
			#	#echo "\nLineData=".$lineData."<br>\n";
			#
			#	# redirect to the correct article
			#	#redirect($lineData);
			if (strpos($lineData,"http://") || strpos($lineData,"https://")){
				$lineData = str_replace('href="','href="?redirect=',$lineData);
			}else if (strpos($lineData,"<link") || strpos($lineData,"<script")){
				# dont add these lines just add a blank line
				echo "\n";
			}else{
				# search for existing articles in links and
				#foreach($articleIndex as $foundFile){
				#	if ( strpos($foundFile,$article) ){
				#		$lineData="".$foundFile;
				#	}else if ( strpos( str_replace("/","_",$foundFile) , str_replace("/","_",$article) ) ){
				#		$lineData=str_replace("/","_",$foundFile);
				#	}
				#}

				$lineData = str_replace('../','',$lineData);
				$lineData = str_replace('href="A/','href="',$lineData);
				if ( strpos($lineData,'href="?article=') ){
					$lineData = str_replace('href="?article=','href="?article=',$lineData);
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
			#echo $lineData;
			$cleanFileData .= $lineData;
		}
		# remove script and style
		$cleanFileData = preg_replace('/<script.*<\/script>/',"",$cleanFileData);
		$cleanFileData = preg_replace('/<style.*<\/style>/',"",$cleanFileData);

		# remove all unknown tags
		#$cleanFileData = strip_tags($cleanFileData,"<meta><script><span><p><table><td><th><tr><div><hr><img><video><source><a><ul><li><ol><pre><code><details><summary>");
		$cleanFileData = strip_tags($cleanFileData,"<meta><script><span><p><div><hr><img><video><source><a><ul><li><ol><pre><code><details><summary>");
		#$cleanFileData = strip_tags($cleanFileData,"<meta><span><p><div><hr><img><video><source><a><ul><li><ol><pre><code><details><summary>");
		# write the clean file data of the article
		echo $cleanFileData;
	}else{
		# the article file does not exist
		echo "<h2>Failed to find Article<h2>";
		echo "Article '".$_GET['article']."' does not exist";
		$foundFiles=scandir("A/");
		#
		echo "<h2>All Found Articles</h2>";
		echo "<ul>";
		foreach($foundFiles as $foundFile){
			echo "<li><a href='?article=".$foundFile."'>?article=".$foundFile."</li>\n";
		}
		echo "</ul>";
	}
}else{
	#redirect("?article=A/Main_Page/Sandbox");
	header('Location: ?home',true);
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
