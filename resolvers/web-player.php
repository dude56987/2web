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
# Parse inputs
if (array_key_exists("url",$_GET)){
	# ignore close connection
	ignore_user_abort(true);
	# 2 hour limit
	set_time_limit(7200);
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
		$isHttps=false;
	}else if (stripos($videoLink,"https://") !== false){
		# link is https the link has passed
		$linkFailed=false;
		$isHttps=true;
	}
	if($linkFailed){
		# redirect failed links to the error page
		redirect("?failure=".$videoLink);
	}

	# remove parenthesis from video link if they exist
	$videoLink = str_replace('"','',$videoLink);
	$videoLink = str_replace("'","",$videoLink);

	# create the sum of the file
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
		$videoMimeTypeHeader=get_headers($videoLink, true);
		$videoMimeType=$videoMimeTypeHeader["Content-Type"];
		# check and set the extension based on the remote file mime data
		if (is_in_array("video/mp4",$videoMimeType)){
			$ext=".mp4";
		}else if (is_in_array("audio/mpeg",$videoMimeType)){
			$ext=".mp3";
		}else if (is_in_array("video/webm",$videoMimeType)){
			$ext=".webm";
		}else if (is_in_array("video/ogg",$videoMimeType)){
			$ext=".ogv";
		}else if (is_in_array("video/x-matroska",$videoMimeType)){
			$ext=".mkv";
		}else{
			addToLog("ERROR", "Unsupported file URL", "Unsupported file type '$videoMimeType' for file '$orignalVideoLink'\n<br>Video mime type header = ".var_export($videoMimeTypeHeader, true));
			if($isHttps){
				addToLog("ERROR", "Unsupported file URL", "videoLink='$orignalVideoLink',\n<br>If this is HTTPS using a self signed certificate the self signed cert must be configured to be accepted by this server.");
			}
			redirect("?uploadFailure=".$videoMimeType."&tryhttp=".$videoLink);
		}
		# create the command to build the thumbnail
		$command = "/var/cache/2web/generated/yt-dlp/yt-dlp -c -O '".$videoPathPrefix.$videoLinkSum.$ext."' '$videoLink';";
		$command .= "ffmpegthumbnailer -i '".$videoPathPrefix.$videoLinkSum.$ext."' -o '".$videoPathPrefix.$videoLinkSum."-thumb.png';";
		$command .= "touch '".$videoPathPrefix.$videoLinkSum.".finished';";
		$command .= "touch '".$videoPathPrefix.$videoLinkSum.$ext."';";
		#
		if(! file_exists($videoPathPrefix."command.cfg")){
			file_put_contents($videoPathPrefix."command.cfg",$command);
		}
		# launch command in queue
		addToLog("UPDATE","Adding Data To Cache","Downloading remote url to cache for web player '".$command."'");
		# launch the command to download the remote file to the cache for playback on the 2web server
		addToQueue("multi",$command);
		# build the php page
		if(! file_exists($videoPathPrefix.$videoLinkSum.".php")){
			symlink("/usr/share/2web/templates/videoPlayer.php",$videoPathPrefix.$videoLinkSum.".php");
		}
		# build the direct link
		if(! file_exists($videoPathPrefix.$videoLinkSum.".php.directLink")){
			file_put_contents($videoPathPrefix.$videoLinkSum.".php.directLink", $orignalVideoLink);
		}
	}
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum."/".$videoLinkSum.".php");
}else if(array_key_exists("shareURL",$_GET)){
	# ignore close connection
	ignore_user_abort(true);
	# 2 hour limit
	set_time_limit(7200);
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
	# redirect to the web page
	redirect("/web_player/".$videoLinkSum."/".$videoLinkSum.".php");
}else if(array_key_exists("uploadMediaFile",$_FILES)){
	# ignore close connection
	ignore_user_abort(true);
	# 2 hour limit
	set_time_limit(7200);
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
	# build the directory used to store all the data
	if (! file_exists($videoPathPrefix)){
		mkdir($videoPathPrefix);
	}
	# log the upload with the system
	addToLog("DOWNLOAD", "User Uploaded File", "The user has uploaded a file '$fileOgName' to '$fileName' with mime type '$fileMimeType'");
	# check the mime data for the file type
	if(is_in_array($fileMimeType, "video/mp4")){
		$filePath=$videoPathPrefix.$fileSum.".mp4";
		$fileOgName=str_replace(".mp4","",$fileOgName);
	}else if (is_in_array($fileMimeType, "audio/mpeg")){
		$filePath=$videoPathPrefix.$fileSum.".mp3";
		$fileOgName=str_replace(".mp3","",$fileOgName);
	}else if(is_in_array($fileMimeType, "video/webm")){
		$filePath=$videoPathPrefix.$fileSum.".webm";
		$fileOgName=str_replace(".webm","",$fileOgName);
	}else if(is_in_array($fileMimeType, "video/ogg")){
		$filePath=$videoPathPrefix.$fileSum.".ogg";
		$fileOgName=str_replace(".ogg","",$fileOgName);
	}else if(is_in_array($fileMimeType, "video/x-matroska")){
		$filePath=$videoPathPrefix.$fileSum.".mkv";
		$fileOgName=str_replace(".mkv","",$fileOgName);
	}else if(is_in_array($fileMimeType, "video/mkv")){
		$filePath=$videoPathPrefix.$fileSum.".mkv";
		$fileOgName=str_replace(".mkv","",$fileOgName);
	}else if(is_in_array($fileMimeType, "audio/mp3")){
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
		addToQueue("multi","ffmpegthumbnailer -i '$filePath' -o '".$videoPathPrefix.$fileSum."-thumb.png"."'");
	}
	# remove the file from the temp directory
	unlink($fileName);
	# generate the video link for the direct file
	$videoLink = str_replace($_SERVER["DOCUMENT_ROOT"],"",$filePath);
	if ($_SERVER["HTTPS"]){
		# https
		$proto="https";
	}else{
		$proto="http";
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
?>
<html class='randomFanart'>
<head>
<link rel='stylesheet' href='/style.css'>
<script src='/2webLib.js'></script>
<script>
// file upload progress function
function postFile() {
	//
  var formdata = new FormData();
	// get the first file in the upload form
  formdata.append('uploadMediaFile', document.getElementById('fileUploadInput').files[0]);
	// create a request
  var request = new XMLHttpRequest();
	// add a event trigger
	request.upload.addEventListener('progress', function (event) {
		// get the size for the file uploaded
		var fileSize = document.getElementById('fileUploadInput').files[0].size;
		// if the file has not completed upload draw the updated progress bar
		if (event.loaded <= fileSize){
			//
			var percent = Math.round(event.loaded / fileSize * 100);
			// set the progress bar width
			document.getElementById('progressBarBar').style.width = percent + '%';
			// the progress bar text
      document.getElementById('progressBarBar').innerHTML = String(percent) + '% ' + String(Math.floor(event.loaded / 1000000)) + "mb/" + String(Math.floor(fileSize / 1000000)) + "mb";
			// hide the upload input and show the progress bar
			document.getElementById('stopButton').style.display = "block";
			document.getElementById('uploadButton').style.display = "none";
			// swap file picker and progress bar elements
			document.getElementById('fileUploadInput').style.display = "none";
			document.getElementById('progressBar').style.display = "block";
    }
		// if the file upload has finished draw the full progress bar
		if(event.loaded == event.total){
			//
      document.getElementById('progressBarBar').style.width = '100%';
      document.getElementById('progressBarBar').innerHTML = '100% ' + String(event.loaded) + "/" + String(fileSize);
			// hide the progress bar and show the file input
			document.getElementById('stopButton').style.display = "none";
			document.getElementById('uploadButton').style.display = "block";
			// swap file picker and progress bar elements
			document.getElementById('fileUploadInput').style.display = "block";
			document.getElementById('progressBar').style.display = "none";
    }
	});
	// try and capture the redirect when it is loaded
	request.onload = () => {
		// redirect the current page to the new page
		window.location=request.responseURL;
	}
	//
	request.open('POST', 'web-player.php');
	//
	request.timeout = 45000;
	//
	request.send(formdata);
}
</script>
</head>
<body>
<?PHP
include("/usr/share/2web/templates/header.php");
# draw error banners
if (array_key_exists("uploadFailure",$_GET)){
	echo errorBanner("Error: Unsupported file type '".$_GET["uploadFailure"]."'\n");
	echo errorBanner("Supported filetypes are '.mp4','.mvk','.avi', '.webm', '.ogv', and '.mp3'\n");
	if (requireGroup("iptv2web")){
		echo errorBanner("Error: If this is a remote live stream, live streams are currently unsupported in the web player but can be added in live section of website by a admin.\n");
	}
}else if (array_key_exists("failure",$_GET)){
	echo errorBanner("The given link '".$_GET['failure']."' is a invalid link and can not be parsed...",true);
}
if (array_key_exists("tryhttp",$_GET)){
	# convert the https to http in the link
	$cleanHttp=preg_replace("/^https/", "http", $_GET["tryhttp"]);
	//$cleanHttp=preg_replace("/^https/", "http", urldecode($_GET["tryhttp"]));
	//$cleanHttp=str_replace("https", "http", urldecode($_GET["tryhttp"]));
	//$cleanHttp=urlencode($cleanHttp);
	# the link has failed you can try the link with http
	echo errorBanner("<a class='button' href='/web_player.php?url=".$cleanHttp."'>Try link as a http link</a>");
}
?>
<div class='settingListCard'>
<table class=''>
<!-- form to use resolver for a url -->
	<tr>
		<form method='get'>
			<th id='openLink'>
				Open Video Link With Resolver
			</th>
		</tr>
		<tr>
			<td>
				<!-- build the share url interface for posting urls into the resolver to be passed to kodi -->
				<table class='kodiControlEmbededTable'>
					<tr>
						<td>
							<input type='text' name='shareURL' placeholder='http://example.com/play.php?v=3d4D3ldK'>
						</td>
						<td class='kodiControlEmbededTableButton'>
							<input class='button' type='submit' value='📥 Share URL'>
						</td>
					</tr>
				</table>
			</td>
		</form>
	</tr>
	<!-- form to upload a file for playback -->
	<form method='post' action='/web-player.php' enctype='multipart/form-data'>
		<tr>
			<th id='directLink'>
				Upload Video File For Playback
			</th>
		</tr>
		<tr>
			<td>
				<!-- build the share url interface for posting urls into the resolver to be passed to kodi -->
				<table class='kodiControlEmbededTable'>
					<tr>
						<td>
							<noscript><div class="errorBanner">This upload form will not work without javascript enabled.</div></noscript>
							<input class='button' id="fileUploadInput" type='file' name='uploadMediaFile' accept='video/*,audio/*'>
							<div id='progressBar' class="progressBar" style="display: none;">
									<div id="progressBarBar" class="progressBarBar">Inactive</div>
							</div>
						</td>
						<td class='kodiControlEmbededTableButton' >
							<input id='uploadButton' class='button' onclick="postFile()" type='button' value='🎞️ Upload File'>
							<input id='stopButton' class='button' onclick="location.reload()" type='button' value='❌ Stop Upload' style="display: none;">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</form>
</table>
</div>
<?PHP
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
	}else if(file_exists($thumbTemplate.".mp4.info.json")){
		$jsonData=file_get_contents($thumbTemplate.".mp4.info.json");
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

	if(file_exists($thumbTemplate.".png")){
		echo "<img loading='lazy' src='".$cachePathTemplate.".png"."' />\n";
	}else if(file_exists($thumbTemplate.".mp4.png")){
		echo "<img loading='lazy' src='".$cachePathTemplate.".mp4.png"."' />\n";
	}else if(file_exists($_SERVER["DOCUMENT_ROOT"]."/web_player/".$directLinkSum."/".$directLinkSum."-thumb.png")){
		echo "<img loading='lazy' src='"."/web_player/".$directLinkSum."/".$directLinkSum."-thumb.png"."' />\n";
	}
	#
	$localCachePath=$_SERVER["DOCUMENT_ROOT"]."/web_player/".$directLinkSum."/".$directLinkSum;
	#
	if(file_exists($thumbTemplate.".mp4") or file_exists($thumbTemplate.".mp3") or file_exists($localCachePath.".mp4")){
		echo "	<div class='title'>".$videoTitle."<div class='radioIcon'>🟢</div></div>\n";
	}else{
		echo "	<div class='title'>".$videoTitle."<div class='radioIcon'>◯</div></div>\n";
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
