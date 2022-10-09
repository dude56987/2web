<?PHP
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
################################################################################
# force debugging
#$_GET['debug']='true';
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
################################################################################
$webDirectory=$_SERVER["DOCUMENT_ROOT"];
################################################################################
function debug($message){
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: ".$message."<br>";
		ob_flush();
		flush();
		return true;
	}else{
		return false;
	}
}
################################################################################
function runShellCommand($command){
	if (array_key_exists("debug",$_GET)){
		//echo 'Running command %echo "'.$command.'" | at now<br>';
		echo 'Running command %'.$command.'<br>';
	}
	################################################################################
	//exec($command);
	//$output=shell_exec('echo "'.$command.'" | at now >> RESOLVER-CACHE/resolver.log');
	$output=shell_exec($command);
	debug("OUTPUT=".$output."<br>");
}
################################################################################
function searchIndex($indexPath,$searchQuery){
	$foundData=false;
	$tempData="";
	# if the search index exists
	if ( file_exists( $indexPath ) ){
		$fileHandle = fopen( $indexPath , "r" );
		while( ! feof( $fileHandle ) ){
			# read a line of the file
			$fileData = fgets( $fileHandle );
			#echo "The file path is '$fileData'<br>\n";
			#remove newlines from extracted file paths in index
			$fileData = str_replace( "\n" , "" , $fileData);
			if ( strpos( $fileData , $searchQuery ) ){
				if ( file_exists( $fileData ) ){
					# read the file in peices
					$linkTextHandle = fopen( $fileData , "r" );
					while( ! feof( $linkTextHandle ) ){
						# read each packet of the file
						$tempData .= fgets( $linkTextHandle , 4096 );
					}
					$foundData = true;
				}
			}
		}
	}
	if ($foundData){
		return array(true,$tempData);
	}else{
		return array(false,$tempData);
	}
}
################################################################################
?>
<html class='randomFanart'>
<head>
	<title>2web Search</title>
	<link rel='stylesheet' type='text/css' href='/style.css'>
	<script src='/2web.js'></script>
	<link rel='icon' type='image/png' href='/favicon.png'>
</head>
<body>
<?PHP
include("/usr/share/2web/2webLib.php");
include($_SERVER['DOCUMENT_ROOT']."/header.php");
?>
<!--
<div class='titleCard'>
	<form action='/search.php' method='get'>
		<h2>2web Search</h2>
		<input class='searchBox' width='60%' type='text' name='q' placeholder='SEARCH...' required>
		<button class='button' type='submit'>🔍</button>
	</form>
</div>
-->
<?PHP
################################################################################
if (array_key_exists("q",$_GET)){
	$searchQuery = $_GET["q"];
	# create md5sum for the query to store output
	$querySum = md5($searchQuery);

	echo "<div class='settingListCard'>";

	$foundResults=false;

	$indexPaths=Array();
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/movies/movies.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/shows/shows.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/music/music.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/random/albums.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/comics/comics.index"));
	$indexPaths=array_merge($indexPaths, Array("/var/cache/2web/web/graphs/graphs.index"));

	$totalOutput="";

	foreach( $indexPaths as $indexPath ){
		$indexInfo=searchIndex($indexPath,$searchQuery);
		if ( $indexInfo[0] ){
			$totalOutput .= $indexInfo[1];
			$foundResults = true;
			flush();
			ob_flush();
		}
	}

	if ($foundResults){
		echo "<h1>Search Results for '$searchQuery'</h1>";
		echo $totalOutput;
	}else{
		echo "<h1>No Search Results for '$searchQuery'</h1>";
		// show the homepage
		if (file_exists("shows")){
			drawPosterWidget("episodes");
			drawPosterWidget("shows");
		}
		if (file_exists("movies")){
			drawPosterWidget("movies");
			# random movies
			drawPosterWidget("movies", True);
		}
		if (file_exists("shows")){
			# random
			drawPosterWidget("shows", True);
		}
		if (file_exists("comics")){
			drawPosterWidget("comics");
			drawPosterWidget("comics", True);
		}
		if (file_exists("music")){
			drawPosterWidget("albums");
			drawPosterWidget("artists");
			drawPosterWidget("music", True);
		}
		if (file_exists("live")){
			if (file_exists("updatedChannels.php")){
				include($_SERVER['DOCUMENT_ROOT']."/updatedChannels.php");
			}
		}
		if (file_exists("live")){
			if (file_exists("randomChannels.php")){
				include($_SERVER['DOCUMENT_ROOT']."/randomChannels.php");
			}
		}
		if (file_exists("graphs")){
			drawPosterWidget("graphs", True);
		}
	}
	formatEcho("<div class='titleCard'>",1);
	formatEcho("<h2>External Search</h2>",2);
	formatEcho("<div class='listCard'>",2);
	formatEcho("<a class='button' target='_new' href='https://www.mojeek.com/search?q=$searchQuery'>Mojeek 🔍</a>",3);
	formatEcho("<a class='button' target='_new' href='https://search.brave.com/search?q=$searchQuery'>Brave 🔍</a>",3);
	formatEcho("<a class='button' target='_new' href='https://www.peekier.com/#!$searchQuery'>Peekier 🔍</a>",3);
	formatEcho("<a class='button' target='_new' href='https://www.duckduckgo.com/?q=$searchQuery'>DuckDuckGo 🔍</a>",3);
	formatEcho("</div>",2);
	formatEcho("</div>",1);

	echo "</div>";

	// add the footer
	include($_SERVER['DOCUMENT_ROOT']."/footer.php");
}
?>
</body>
</html>
