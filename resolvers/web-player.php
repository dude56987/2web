<!--
########################################################################
# 2web player to launch playback on a webpage
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
<?PHP
################################################################################
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
################################################################################
# require the web player group
requireGroup("webPlayer");
################################################################################
function is_in_array($needle,$haystack){
	# search for a needle in a string or array haystack
	if (is_array($haystack)){
		return in_array($needle, $haystack);
	}else if(is_string($haystack)){
		if (stripos($haystack,$needle) !== false){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}
# Parse inputs
if (array_key_exists("url",$_GET)){
	# check if the link was passed with the web remote
	$videoLink = $_GET['url'];

	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);

	# build the orignal video link from the cleaned video link
	$orignalVideoLink=$videoLink;
	# set the linkfailed state to true
	$linkFailed=true;
	# check the link for failures
	if (stripos($videoLink,"http://") !== false){
		# link is http the link has passed
		$linkFailed=false;
	}else if (stripos($videoLink,"https://") !== false){
		# link is https the link has passed
		$linkFailed=false;
	}
	if($linkFailed){
		# redirect failed links to the error page
		redirect("?failure=".$videoLink);
	}

	#
	$videoLinkSum=hash("sha512",$videoLink,false);

	# set the proto based on the current protocol
	if ($_SERVER["HTTPS"]){
		$proto="https";
	}else{
		$proto="http";
	}
	# build the cache link
	#$videoLink = $proto."://".$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".'"'.$videoLink.'"';

	# create a file based on the md5sum in a cache
	# fileName.php
	# fileName.php.directLink
	# fileName.php.title
	# fileName.php.date
	# fileName.php.plot
	# fileName.php.cacheLink
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".php")){
		symlink("/usr/share/2web/templates/videoPlayer.php","/var/cache/2web/web/web_player/".$videoLinkSum.".php");
	}
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".directLink")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".directLink", $orignalVideoLink);
	}
	# build the strm links
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".strm")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".strm", $orignalVideoLink);
	}
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".php.strmLink")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".php.strmLink", "http://".$_SERVER["HTTP_HOST"]."/web_player/".$videoLinkSum.".strm");
	}
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum.".php");
}else if(array_key_exists("shareURL",$_GET)){
	# check if the link was passed with the web remote
	$videoLink = $_GET['shareURL'];
	# set the linkfailed state to true
	$linkFailed=true;
	# check the link for failures
	if (stripos($videoLink,"http://") !== false){
		# link is http the link has passed
		$linkFailed=false;
	}else if (stripos($videoLink,"https://") !== false){
		# link is https the link has passed
		$linkFailed=false;
	}
	if($linkFailed){
		# redirect failed links to the error page
		redirect("?failure=".$videoLink);
	}

	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);

	$orignalVideoLink=$videoLink;

	$videoLinkSum=hash("sha512",$videoLink,false);

	#$videoLinkSum=hash("sha512",$videoLink,false);
	#$hashSum2=hash("sha512",'"'.$videoLink.'"',false);
	# hashSums log
	#file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".log", "HASH=".$videoLinkSum."\nHASH2=".$hashSum2);

	if ($_SERVER["HTTPS"]){
		# https A
		$proto="https";
	}else{
		$proto="http";
	}
	# make the local redirect
	#$videoLink = $proto."://".$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".'"'.$videoLink.'"';
	$videoLink = $proto."://".$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".'"'.$videoLink.'"';

	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".php")){
		symlink("/usr/share/2web/templates/videoPlayer.php","/var/cache/2web/web/web_player/".$videoLinkSum.".php");
	}
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".php.directLink")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".php.directLink", $orignalVideoLink);
	}
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".php.cacheLink")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".php.cacheLink", $videoLink);
	}
	# build the strm links
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".strm")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".strm", $videoLink);
	}
	if(! file_exists("/var/cache/2web/web/web_player/".$videoLinkSum.".php.strmLink")){
		file_put_contents("/var/cache/2web/web/web_player/".$videoLinkSum.".php.strmLink", "http://".$_SERVER["HTTP_HOST"]."/web_player/".$videoLinkSum.".strm");
	}
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum.".php");
}
echo "<html class='randomFanart'>";
echo "<head>";
echo "<link rel='stylesheet' href='/style.css'>";
echo "<script src='/2webLib.js'></script>";
echo "</head>";
echo "<body class='settingListCard'>";
include("/usr/share/2web/templates/header.php");
#
if (array_key_exists("failure",$_GET)){
	echo errorBanner("The given link '".$_GET['failure']."' is a invalid link and can not be parsed...",true);
}

echo "<table class=''>";
# form to use resolver for a url
echo "	<tr>\n";
echo "		<form method='get'>\n";
echo "			<th id='openLink'>\n";
echo "				Open Link With Resolver\n";
echo "			</th>\n";
echo "		</tr>\n";
echo "		<tr>\n";
echo "			<td>\n";
echo "				<table class='kodiControlEmbededTable'>\n";
echo "					<tr>\n";
# build the share url interface for posting urls into the resolver to be passed to kodi
echo "						<td>\n";
echo "							<input type='text' name='shareURL' placeholder='http://example.com/play.php?v=3d4D3ldK'>\n";
echo "						</td>\n";
echo "						<td class='kodiControlEmbededTableButton'>\n";
echo "							<input class='button' type='submit' value='Share URL'>\n";
echo "						</td>\n";
echo "					</tr>\n";
echo "				</table>\n";
echo "			</td>\n";
echo "		</form>\n";
echo "	</tr>\n";
# form to use a direct link to content to send to kodi
echo "	<form method='get'>\n";
echo "		<tr>\n";
echo "			<th id='directLink'>\n";
echo "				Open Direct Media Link\n";
echo "			</th>\n";
echo "		</tr>\n";
echo "		<tr>\n";
echo "			<td>\n";
echo "				<table class='kodiControlEmbededTable'>\n";
echo "					<tr>\n";
echo "						<td>\n";
# build the share url interface for posting urls into the resolver to be passed to kodi
echo "							<input type='text' name='url' placeholder='http://example.com/media.mkv'>\n";
echo "						</td>\n";
echo "						<td class='kodiControlEmbededTableButton'>\n";
echo "							<input class='button' type='submit' value='Share Direct URL'>\n";
echo "						</td>\n";
echo "					</tr>\n";
echo "				</table>";
echo "			</td>\n";
echo "		</tr>\n";
echo "	</form>\n";
echo "</table>\n";
# draw the older cached videos
echo "<div class='titleCard'>\n";
echo "<h2>Recently Viewed Videos</h2>\n";
$sourceFiles = recursiveScan($_SERVER['DOCUMENT_ROOT']."/web_player/");
# sort the files by mtime
$sortedLinkList=array();
# sort the link list by modification date
foreach($sourceFiles as $sourceFile){
	# only include php files
	if (mime_content_type($sourceFile) == "text/x-php"){
		# make sure not to load the link files used by the webpage
		$sortedLinkList[lstat($sourceFile)["mtime"]]=$sourceFile;
	}
}
ksort($sortedLinkList);
$sourceFiles=array_reverse($sortedLinkList);
$processedTitles=0;
# build the video index
foreach($sourceFiles as $sourceFile){
	# build the links
	#$directLink=file_get_contents(str_replace(".php",".directLink",$sourceFile));
	#$cacheLink=file_get_contents(str_replace(".php",".cacheLink",$sourceFile));

	# link sums
	#$directLinkSum=hash("sha512",$directLink,false);
	# get the link sum
	$directLinkSum=str_replace(".php","",basename($sourceFile));

	$cachePathTemplate="/RESOLVER-CACHE/".$directLinkSum."/video";
	$thumbTemplate=$_SERVER["DOCUMENT_ROOT"].$cachePathTemplate;
	#echo "THUMB TEMPLATE = '$thumbTemplate'<br>\n";
	# get the cache data if it exists
	if(file_exists($thumbTemplate.".info.json")){
		$jsonData=file_get_contents($thumbTemplate.".info.json");
		$jsonData=json_decode($jsonData);
		$videoTitle=$jsonData->title;
	}else{
		$videoTitle=str_replace(".php","",basename($sourceFile));
	}

	echo "<a class='showPageEpisode' href='".("/web_player/".$directLinkSum.".php")."'>\n";
	#echo "<div>".mime_content_type($sourceFile)."</div>";

	if(file_exists($thumbTemplate.".png")){
		echo "<img loading='lazy' src='".$cachePathTemplate.".png"."' />\n";
	}
	if(file_exists($thumbTemplate.".mp4")){
		echo "	<h3>".$videoTitle."<div class='radioIcon'>🟢</div></h3>\n";
	}else{
		echo "	<h3>".$videoTitle."<div class='radioIcon'>◯</div></h3>\n";
	}
	echo "</a>\n";
	# increment the processed titles
	$processedTitles+=1;
	if ($processedTitles >= 100){
		# break processing after the last 100 links
		break;
	}
}
echo "</div>";


include("/usr/share/2web/templates/footer.php");
echo "</body>\n";
echo "</html>\n";
?>
