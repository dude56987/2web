<?PHP
########################################################################
# 2web transcoder for converting and caching local video formats
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

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
################################################################################
function cacheResolve($sum,$webDirectory){
	$debugPath="/TRANSCODE-CACHE/$sum/";
	# wait for either the bump or the file to be downloaded and redirect
	while(true){
		# if 60 seconds of the video has been downloaded then launch the video
		if(file_exists("$webDirectory/TRANSCODE-CACHE/$sum/video.mp3")){
			header("Content-type: audio/mpeg;");
			# redirect to discovered mp3
			redirect("/TRANSCODE-CACHE/$sum/video.mp3");
		}else if(file_exists("$webDirectory/TRANSCODE-CACHE/$sum/video.mp4")){
			header("Content-type: video/mp4;");
			// file is fully downloaded and converted play instantly
			redirect("/TRANSCODE-CACHE/$sum/video.mp4");
		}else if(file_exists("$webDirectory/TRANSCODE-CACHE/$sum/video.m3u")){
			# if the stream has x segments (segments start as 0)
			# - currently 10 seconds of video
			# - force loading of 3 segments before resolution
			if(file_exists("$webDirectory/TRANSCODE-CACHE/$sum/video-stream2.ts")){
				header("Content-type: application/mpegurl;");
				# redirect to the stream
				redirect("/TRANSCODE-CACHE/$sum/video.m3u");
			}
		}
		# Sleep at end of the loop then try to find a redirect again
		sleep(1);
	}
}
################################################################################
////////////////////////////////////////////////////////////////////////////////
$webServerPath = $_SERVER['DOCUMENT_ROOT'];
////////////////////////////////////////////////////////////////////////////////
# check if the transcode is enabled
if (yesNoCfgCheck("/etc/2web/transcodeForWebpages.cfg")){
	$doTranscode = True;
}else{
	$doTranscode = False;
}
////////////////////////////////////////////////////////////////////////////////
if (array_key_exists("path",$_GET)){
	# pull the link from
	$link = $_GET['path'];
	# decode link into text
	$link = urldecode($link);
	# allow parallel loading of pages for user
	session_write_close();
	# if the trancode is enabled run the transcode job
	if ($doTranscode){
		addToLog("INFO","Transcode","Checking for trancode for '$link'\n");
		# create the sum of the link
		$sum=md5($link);
		# check if the session has been locked
		if ( ! file_exists("$webServerPath/TRANSCODE-CACHE/$sum/started.cfg")){
			# create the transcode lock file
			file_put_contents("$webServerPath/TRANSCODE-CACHE/$sum/started.cfg",time());
			# make sure there is no existing stream available
			addToLog("UPDATE","Transcode Started","Transcoding link '$link'\n");
			# ignore user abort of connection
			ignore_user_abort(true);
			# set execution time limit to 15 minutes
			set_time_limit(900);
			# create path if it does not exist
			if ( ! file_exists("$webServerPath/TRANSCODE-CACHE/")){
				mkdir("$webServerPath/TRANSCODE-CACHE/");
			}
			# cleanup html string encoding of spaces and pathnames
			$link = str_replace("'","",$link);
			$link = str_replace('"',"",$link);
			# build the command
			$fullLinkPath=$webServerPath.$link;
			# create a transcode directory to store the hls stream if it does not exist
			if ( ! file_exists("$webServerPath/TRANSCODE-CACHE/$sum/")){
				mkdir("$webServerPath/TRANSCODE-CACHE/$sum/");
			}
			# build the transcode command
			$command = "/usr/bin/ffmpeg -i '".$fullLinkPath."' -f mpegts - ";
			$command .= " | /usr/bin/ffmpeg -i - ";
			$command .= " -hls_playlist_type event -hls_list_size 0 -start_number 0 -master_pl_name video.m3u -g 30 -hls_time 10 -f hls '$webServerPath/TRANSCODE-CACHE/".$sum."/video-stream.m3u'";
			# encode the stream into a mp4 file for compatibility with firefox
			# - use a .part file until the mp4 is complete because partial mp4 files will not play
			$command .= "; /usr/bin/ffmpeg -i '".$webServerPath."/TRANSCODE-CACHE/".$sum."/video.m3u' -f mp4 '".$webServerPath."/TRANSCODE-CACHE/".$sum."/video.mp4.part'";
			# copy the complete file
		 	$command .= " && cp '".$webServerPath."/TRANSCODE-CACHE/".$sum."/video.mp4.part' -f mp4 '".$webServerPath."/TRANSCODE-CACHE/".$sum."/video.mp4'";
			# save the transcode command to a file
			file_put_contents("$webServerPath/TRANSCODE-CACHE/$sum/command.cfg","$command");
			# add the job to the 2web queue system
			addToQueue("multi",$command);
		}
		# resolve file created in the cache
		cacheResolve($sum, $webServerPath);
	}else{
		# transcoding is disabled redirect to the transcode page itself
		redirect("/transcode.php");
	}
}else{
	if ($doTranscode){
		# no link was given to transcode, draw the interface
		echo "<div class='settingListCard'>";
		echo "<h1>Transcode links to webm in local cache</h1>";
		echo "<form method='get'>";
		echo "	<input class='button' width='60%' type='text' name='link' placeholder='/shows/showTitle/season 01/showTitle - s1e1 - episodeTitle.webm'>";
		echo "	<input class='button' type='submit' value='Transcode link'>";
		echo "</form>";
		echo "</div>";
	}else{
		# if transcoding is disabled show a link to the settings
		echo "<div class='settingListCard'>";
		echo "<h1>Transcoding is Disabled</h1>";
		echo "<p>A System Administrator can enable transcoding in the server settings.</p>";
		echo "<a class='button' href='/settings/cache.php#transcodeForWebpages'>";
		echo "Transcode Settings";
		echo "</a>";
		echo "</div>";
	}
}
?>
