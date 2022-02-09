<html class='randomFanart'>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'>
</head>
<body>
<?php
################################################################################
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
ini_set('display_errors', 1);
include("header.php");
include("settingsHeader.php");
?>

<div id='index' class='inputCard'>
	<h2>Index</h2>
	<ul>
		<li><a href='#serverWebsiteSources'>Server Website Sources</a></li>
		<li><a href='#websiteSources'>Website Sources</a></li>
		<li><a href='#serverUsernameSources'>Server Username Sources</a></li>
		<li><a href='#addWebsiteSource'>Add Website Source</a></li>
		<li><a href='#addUsernameSource'>Add Username Source</a></li>
		<li><a href='#episodeProcessingLimit'>Episode Processing Limit</a></li>
		<li><a href='#downloadPath'>Download Path</a></li>
		<li><a href='#channelProcessingLimit'>Channel Processing Limit</a></li>
		<li><a href='#channelCacheUpdateDelay'>Channel Cache Update Delay</a></li>
		<li><a href='#videoFetchTimeLimit'>Video Fetch Time Limit</a></li>
		<li><a href='#sleepTime'>Sleep Time</a></li>
	</ul>
</div>

<?php
echo "<div id='serverWebsiteSources' class='inputCard'>\n";
echo "<h2>Server Website Sources</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/ytdl2kodi/sources.cfg");
/*
$data = fopen("/etc/ytdl2kodi/sources.cfg",'r');
if ($data){
	while (($line = fgets($data)) !== false){
		if(strpos("#",$line) == 0){
			echo "<div class='codeCommentLine'>".$line."</div>";
		}else{
			echo "<div class='codeLine'>".$line."</div>";
		}
	}
	fclose($data);
}
*/
echo "</pre>\n";
echo "</div>";

echo "<div id='websiteSources' class='settingListCard'>";
echo "<h2>Website Sources</h2>\n";
$sourceFiles = scandir("/etc/ytdl2kodi/sources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/ytdl2kodi/sources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='ytdl_remove_source' value='".$link."'>Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
echo "</div>\n";

echo "<div id='serverUsernameSources' class='settingListCard'>\n";
echo "<h2>Server Username Sources</h2>\n";
echo "<pre>\n";
echo file_get_contents("/etc/ytdl2kodi/usernameSources.cfg");
echo "</pre>\n";
echo "</div>";

echo "<div id='usernameSources' class='settingListCard'>";
echo "<h2>Username Sources</h2>\n";
$sourceFiles = scandir("/etc/ytdl2kodi/usernameSources.d/");
//print_r($sourceFiles);
$sourceFiles = explode("\n",shell_exec("ls -t1 /etc/ytdl2kodi/usernameSources.d/*.cfg"));
// reverse the time sort
$sourceFiles = array_reverse($sourceFiles);
//print_r($sourceFiles);
//echo "<table class='settingsTable'>";
foreach($sourceFiles as $sourceFile){
	$sourceFileName = $sourceFile;
	//echo "[DEBUG]: found file ".$sourceFile."<br>\n";
	if (file_exists($sourceFile)){
		//echo "[DEBUG]: file exists ".$sourceFile."<br>\n";
		if (is_file($sourceFile)){
			if (strpos($sourceFile,".cfg")){
				echo "<div class='settingsEntry'>";
				//echo "<hr>\n";
				//echo "[DEBUG]: reading file ".$sourceFile."<br>\n";
				$link=file_get_contents($sourceFile);

				if (file_exists(md5($link).".png")){
					# if the link is direct
					echo "	<img class='settingsThumb' src='".md5($link).".png'>";
				}
				echo "	<h2>".$link."</h2>";
				echo "<div class='buttonContainer'>\n";
				echo "	<form action='admin.php' class='buttonForm' method='post'>\n";
				echo "		<button class='button' type='submit' name='ytdl_remove_username_source' value='".$link."'>Remove Source</button>\n";
				echo "	</form>\n";
				echo "</div>\n";
				echo "</div>\n";
			}
		}
	}
}
?>
</div>

<div id='addWebsiteSource' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Website Source</h2>
<ul>
	<li>
	Website sources will be added as shows based on video links found on source pages.
	</li>
	<li>
	Website sources will be grouped by website name.
	</li>
	<li>
	Website sources can be search pages.
	</li>
</ul>
<input width='60%' type='text' name='ytdl_add_source' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

<div id='addUsernameSource' class='inputCard'>
<form action='admin.php' method='post'>
<h2>Add Username Source</h2>
<ul>
	<li>
		For websites with usernames, will create a show with all of that usernames content.
	</li>
	<li>
		The same usernames on diffrent sites will link to the same generated show.
	</li>
</ul>
<input width='60%' type='text' name='ytdl_add_username_source' placeholder='http://link.com/test'>
<input class='button' type='submit'>
</form>
</div>

<div id='episodeProcessingLimit' class='inputCard'>
<h2>Episode Processing Limit</h2>
<ul>
	<li>
		The max number of episodes to process for a channel in a update.
	</li>
	<li>
		This is to throttle downloading metadata from the site.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/ytdl2kodi/episodeProcessingLimit.cfg");
?>
</div>

<div id='downloadPath' class='inputCard'>
<h2>Download Path</h2>
<ul>
	<li>
		This is where nfo libary will be created for shows.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/ytdl2kodi/downloadPath.cfg");
?>
</div>

<div id='channelProcessingLimit' class='inputCard'>
<h2>Channel Processing Limit</h2>
<ul>
	<li>
		How many channels can be scanned during an update.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/ytdl2kodi/channelProcessingLimit.cfg");
?>
</div>

<div id='channelCacheUpdateDelay' class='inputCard'>
<h2>Channel Cache Update Delay</h2>
<ul>
	<li>
		How long in hours the channel will wait before updating again.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/ytdl2kodi/channelCacheUpdateDelay.cfg");
?>
</div>

<div id='videoFetchTimeLimit' class='inputCard'>
<h2>Video Fetch Time Limit</h2>
<ul>
	<li>
		The max time in seconds to wait before the network times out when downloading metadata.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/ytdl2kodi/videoFetchTimeLimit.cfg");
?>
</div>

<div id='sleepTime' class='inputCard'>
<h2>Sleep Time</h2>
<ul>
	<li>
		The max sleep time in seconds to wait between metadata searches.
	</li>
	<li>
		The true sleep time is randomized between this number and zero.
	</li>
</ul>
<?PHP
	echo file_get_contents("/etc/ytdl2kodi/sleepTime.cfg");
?>
</div>

</body>
</html>