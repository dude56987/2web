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

	# create a file based on the md5sum in a cache
	# fileName.php
	# fileName.php.directLink
	# fileName.php.title
	# fileName.php.date
	# fileName.php.plot
	# fileName.php.cacheLink

	# create the directory to store the video metadata
	$videoPathPrefix=$_SERVER["DOCUMENT_ROOT"]."/web_player/".$videoLinkSum."/";
	# make the local cache path for the video in the web player
	if (! file_exists($videoPathPrefix)){
		mkdir($videoPathPrefix);
	}
	# download the remote file to the web player cache in the background if it does not yet exist
	if (! file_exists($videoPathPrefix.$videoLinkSum.".finished")){
		# get the mime type for the remote file
		$videoMimeType=get_headers($videoLink, true);
		$videoMimeType=$videoMimeType["Content-Type"];
		# check and set the extension based on the remote file mime data
		if ("video/mp4" == $videoMimeType){
			$ext=".mp4";
		}else if ("audio/mpeg" == $videoMimeType){
			$ext=".mp3";
		}else if ("video/webm" == $videoMimeType){
			$ext=".webm";
		}else if ("video/ogg" == $videoMimeType){
			$ext=".ogv";
		}else if ("video/x-matroska" == $videoMimeType){
			$ext=".mkv";
		}else{
			addToLog("ERROR", "Unsupported file URL", "Unsupported file type '$videoMimeType' for file '$orignalVideoLink', If this is HTTPS using a self signed certificate this error will also occur but the video mime type data will be blank.");
			redirect("?uploadFailure=".$videoMimeType);
		}
		# generate qr codes if they do not yet exist
		$command = "wget -c -O '".$videoPathPrefix.$videoLinkSum.$ext."' '$videoLink';";
		$command .= "ffmpegthumbnailer -i '".$videoPathPrefix.$videoLinkSum.$ext."' -o '".$videoPathPrefix.$videoLinkSum."-thumb.png';";
		$command .= "touch '".$videoPathPrefix.$videoLinkSum.".finished';";
		$command .= "touch '".$videoPathPrefix.$videoLinkSum.$ext."';";
		#
		if(! file_exists($videoPathPrefix."command.cfg")){
			file_put_contents($videoPathPrefix."command.cfg",$command);
		}
		# launch command in queue
		$command = 'echo "'.$command.'" | at -M now';
		addToLog("UPDATE","Adding Data To Cache","Downloading remote url to cache for web player '".$command."'");
		# launch the command to download the remote file to the cache for playback on the 2web server
		shell_exec($command);
		# build the php page
		if(! file_exists($videoPathPrefix.$videoLinkSum.".php")){
			symlink("/usr/share/2web/templates/videoPlayer.php",$videoPathPrefix.$videoLinkSum.".php");
		}
		# build the direct link
		if(! file_exists($videoPathPrefix.$videoLinkSum.".php.directLink")){
			file_put_contents($videoPathPrefix.$videoLinkSum.".php.directLink", $orignalVideoLink);
		}
		# build the cache link
		if(! file_exists($videoPathPrefix.$videoLinkSum.".php.cacheLink")){
			file_put_contents($videoPathPrefix.$videoLinkSum.".php.cacheLink", "/web_player/".$videoLinkSum."/".$videoLinkSum.$ext);
		}
		# build the strm links
		if(! file_exists($videoPathPrefix.$videoLinkSum.".strm")){
			file_put_contents($videoPathPrefix.$videoLinkSum.".strm", "/web_player/".$videoLinkSum."/".$videoLinkSum.$ext);
		}
		if(! file_exists($videoPathPrefix.$videoLinkSum.".php.strmLink")){
			file_put_contents($videoPathPrefix.$videoLinkSum.".php.strmLink", "http://".$_SERVER["HTTP_HOST"]."/web_player/".$videoLinkSum."/".$videoLinkSum.".strm");
		}
	}
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum."/".$videoLinkSum.".php");
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

	if ($_SERVER["HTTPS"]){
		# https A
		$proto="https";
	}else{
		$proto="http";
	}
	# make the local redirect
	$videoLink = $proto."://".$_SERVER["HTTP_HOST"]."/ytdl-resolver.php?url=".'"'.$videoLink.'"';
	# create the directory to store the video metadata
	$videoPathPrefix="/var/cache/2web/web/web_player/".$videoLinkSum."/";
	if (! file_exists($videoPathPrefix)){
		mkdir($videoPathPrefix);
	}
	# build the php page
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php")){
		symlink("/usr/share/2web/templates/videoPlayer.php",$videoPathPrefix.$videoLinkSum.".php");
	}
	# build the direct link
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php.directLink")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".php.directLink", $orignalVideoLink);
	}
	# build the cache link
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php.cacheLink")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".php.cacheLink", $videoLink);
	}
	# build the strm links
	if(! file_exists($videoPathPrefix.$videoLinkSum.".strm")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".strm", $videoLink);
	}
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php.strmLink")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".php.strmLink", "http://".$_SERVER["HTTP_HOST"]."/web_player/".$videoLinkSum."/".$videoLinkSum.".strm");
	}
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum."/".$videoLinkSum.".php");
}else if(array_key_exists("uploadMediaFile",$_FILES)){
	# get the path to the tmp file
	$fileName=$_FILES['uploadMediaFile']["tmp_name"];
	# get the orignal file name
	$fileOgName=$_FILES['uploadMediaFile']["full_path"];
	# get the file mime type
	$fileMimeType=$_FILES['uploadMediaFile']["type"];
	# build the sum from the temp file data
	$fileSum=hash_file("sha512",$fileName);
	# set the video link sum as the file sum for the uploaded file
	$videoLinkSum=$fileSum;
	# create the directory to store the video metadata
	$videoPathPrefix=$_SERVER["DOCUMENT_ROOT"]."/web_player/".$videoLinkSum."/";
	#
	addToLog("DOWNLOAD", "User Uploaded File", "The user has uploaded a file '$fileOgName' to '$fileName' with mime type '$fileMimeType'");
	# check the mime data for the file type
	if($fileMimeType == "video/mp4"){
		$filePath=$videoPathPrefix.$fileSum.".mp4";
		$fileOgName=str_replace(".mp4","",$fileOgName);
	}else if ($fileMimeType == "audio/mpeg"){
		$filePath=$videoPathPrefix.$fileSum.".mp3";
		$fileOgName=str_replace(".mp3","",$fileOgName);
	}else if($fileMimeType == "video/webm"){
		$filePath=$videoPathPrefix.$fileSum.".webm";
		$fileOgName=str_replace(".webm","",$fileOgName);
	}else if($fileMimeType == "video/ogg"){
		$filePath=$videoPathPrefix.$fileSum.".ogg";
		$fileOgName=str_replace(".ogg","",$fileOgName);
	#}else if($fileMimeType == "video/x-msvideo"){
	#	$filePath=$videoPathPrefix.$fileSum.".avi";
	#	$fileOgName=str_replace(".avi","",$fileOgName);
	}else if($fileMimeType == "video/x-matroska"){
		$filePath=$videoPathPrefix.$fileSum.".mkv";
		$fileOgName=str_replace(".mkv","",$fileOgName);
	}else if($fileMimeType == "video/mkv"){
		$filePath=$videoPathPrefix.$fileSum.".mkv";
		$fileOgName=str_replace(".mkv","",$fileOgName);
	}else if($fileMimeType == "audio/mp3"){
		$filePath=$videoPathPrefix.$fileSum.".mp3";
		$fileOgName=str_replace(".mp3","",$fileOgName);
	}else{
		$filePath="";
		$linkFailed=true;
		addToLog("ERROR", "Unsupported file upload", "Unsupported file type '$fileMimeType' for file '$fileOgName'");
		# redirect the failed link
		redirect("?uploadFailure=".$fileMimeType);
	}
	if(! file_exists($filePath)){
		# copy the temp file to the path generated
		copy($fileName,$filePath);
	}
	if(! file_exists($videoPathPrefix.$fileSum."-thumb.png")){
		# build thumbnail with the scheduler in a seprate thread
		shell_exec("echo \"ffmpegthumbnailer -i '$filePath' -o '".$videoPathPrefix.$fileSum."-thumb.png"."'\" | at -M -q a now");
	}
	# remove the file from the temp directory
	unlink($fileName);
	# generate the video link for the direct file
	$videoLink = str_replace($_SERVER["DOCUMENT_ROOT"],"",$filePath);
	if ($_SERVER["HTTPS"]){
		# https A
		$proto="https";
	}else{
		$proto="http";
	}
	if (! file_exists($videoPathPrefix)){
		mkdir($videoPathPrefix);
	}
	# build the title for the video based on the file title uploaded
	if(! file_exists($videoPathPrefix.$fileSum.".php.title")){
		file_put_contents($videoPathPrefix.$fileSum.".php.title", $fileOgName);
	}
	# build the php page
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php")){
		symlink("/usr/share/2web/templates/videoPlayer.php",$videoPathPrefix.$videoLinkSum.".php");
	}
	# build the direct link
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php.directLink")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".php.directLink", $videoLink);
	}
	# build the strm links
	if(! file_exists($videoPathPrefix.$videoLinkSum.".strm")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".strm", $videoLink);
	}
	if(! file_exists($videoPathPrefix.$videoLinkSum.".php.strmLink")){
		file_put_contents($videoPathPrefix.$videoLinkSum.".php.strmLink", "http://".$_SERVER["HTTP_HOST"]."/web_player/".$videoLinkSum."/".$videoLinkSum.".strm");
	}
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum."/".$videoLinkSum.".php");
}
echo "<html class='randomFanart'>";
echo "<head>";
echo "<link rel='stylesheet' href='/style.css'>";
echo "<script src='/2webLib.js'></script>";
echo "</head>";
echo "<body>";
include("/usr/share/2web/templates/header.php");
# draw error banners
if (array_key_exists("uploadFailure",$_GET)){
	echo errorBanner("Error: Unsupported file type '".$_GET["uploadFailure"]."'\n");
	echo errorBanner("Supported filetypes are '.mp4','.mvk','.avi', and '.mp3'\n");
}else if (array_key_exists("failure",$_GET)){
	echo errorBanner("The given link '".$_GET['failure']."' is a invalid link and can not be parsed...",true);
}
echo "<div class='settingListCard'>";
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
# form to upload a file for playback
echo "	<form method='post' action='web-player.php' enctype='multipart/form-data'>";
echo "		<tr>\n";
echo "			<th id='directLink'>\n";
echo "				Upload File For Playback\n";
echo "			</th>\n";
echo "		</tr>\n";
echo "		<tr>\n";
echo "			<td>\n";
echo "				<table class='kodiControlEmbededTable'>\n";
echo "					<tr>\n";
echo "						<td>\n";
# build the share url interface for posting urls into the resolver to be passed to kodi
echo "							<input class='button' type='file' name='uploadMediaFile' accept='video/*'>";
echo "						</td>\n";
echo "						<td class='kodiControlEmbededTableButton'>\n";
echo "							<input class='button' type='submit' value='Upload File'>\n";
echo "						</td>\n";
echo "					</tr>\n";
echo "				</table>";
echo "			</td>\n";
echo "		</tr>\n";
echo "	</form>\n";
echo "</table>\n";
echo "</div>";
# send the input forms to the user before rendering the list of recent videos
flush();
ob_flush();
# draw the older cached videos
echo "<div class='titleCard'>\n";
echo "<h2>Recent Videos</h2>\n";
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
	#
	$cachePathTemplate="/RESOLVER-CACHE/".$directLinkSum."/video";
	#
	$thumbTemplate=$_SERVER["DOCUMENT_ROOT"].$cachePathTemplate;
	#echo "THUMB TEMPLATE = '$thumbTemplate'<br>\n";
	# get the cache data if it exists
	if(file_exists($thumbTemplate.".info.json")){
		$jsonData=file_get_contents($thumbTemplate.".info.json");
		$jsonData=json_decode($jsonData);
		$videoTitle=$jsonData->title;
	}else{
		if(file_exists($_SERVER["DOCUMENT_ROOT"]."/web_player/".$directLinkSum."/".$directLinkSum.".php.title")){
			$videoTitle=file_get_contents($_SERVER["DOCUMENT_ROOT"]."/web_player/".$directLinkSum."/".$directLinkSum.".php.title");
		}else{
			$videoTitle=str_replace(".php","",basename($sourceFile));
		}
	}
	echo "<a class='showPageEpisode' href='".("/web_player/".$directLinkSum."/".$directLinkSum.".php")."'>\n";
	#echo "<div>".mime_content_type($sourceFile)."</div>";

	if(file_exists($thumbTemplate.".png")){
		echo "<img loading='lazy' src='".$cachePathTemplate.".png"."' />\n";
	}else if(file_exists($_SERVER["DOCUMENT_ROOT"]."/web_player/".$directLinkSum."/".$directLinkSum."-thumb.png")){
		echo "<img loading='lazy' src='"."/web_player/".$directLinkSum."/".$directLinkSum."-thumb.png"."' />\n";
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
echo "</body>";
echo "</html>";
?>
