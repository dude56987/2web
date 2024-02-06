<!--
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
-->
<?PHP
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");

////////////////////////////////////////////////////////////////////////////////
$webServerPath = $_SERVER['DOCUMENT_ROOT'];
////////////////////////////////////////////////////////////////////////////////
$doTranscode = False;
# check if the transcode is enabled
if (file_exists("/etc/2web/transcodeForWebpages.cfg")){
	$selected=file_get_contents("/etc/2web/transcodeForWebpages.cfg");
	if ($selected == "yes"){
		$doTranscode = True;
	}
}
////////////////////////////////////////////////////////////////////////////////
if (array_key_exists("link",$_GET)){
	# pull the link from
	$link = $_GET['link'];
	# if the trancode is enabled run the transcode job
	if ($doTranscode){
		debug("Reading link for transcode : '".$link."'");
		# create the sum of the link
		$sum=md5($link);
		# make sure there is no existing stream available
		if ( ! file_exists($webServerPath."/TRANSCODE-CACHE/$sum/play.m3u")){
			if ( ! file_exists("$webServerPath/TRANSCODE-CACHE/")){
				mkdir("$webServerPath/TRANSCODE-CACHE/");
			}
			# cleanup html string encoding of spaces and pathnames
			$link = str_replace("%20"," ",$link);
			$link = str_replace("%21"," ",$link);
			$link = str_replace("'","",$link);
			$link = str_replace('"',"",$link);
			# build the command
			$fullLinkPath=$webServerPath.$link;
			# create a transcode directory to store the hls stream if it does not exist
			if ( ! file_exists("$webDirectory/TRANSCODE-CACHE/$sum/")){
				mkdir("$webServerPath/TRANSCODE-CACHE/$sum/");
			}
			# remove doubled slashes to fix paths
			$fullLinkPath=str_replace("//","/",$fullLinkPath);
			$command = 'echo "';
			$command .= "/usr/bin/ffmpeg -i '".$fullLinkPath."'";
			#$command .= " -hls_segment_type fmp4";
			#$command .= " -c:v libx264 -b:v 5000k";
			$command .= " -preset superfast";
			#$command .= " -hls_playlist_type event";
			$command .= " -hls_list_size 0";
			$command .= " -start_number 0";
			$command .= " -master_pl_name 'play.m3u' -g 30 -hls_time 10 -f hls";
			$command .= " '".$webServerPath."/TRANSCODE-CACHE/".$sum."/stream.m3u'";
			# encode the stream into a mp4 file for compatibility with firefox
			$command .= "; /usr/bin/ffmpeg -i '".$webServerPath."/TRANSCODE-CACHE/".$sum."/stream.m3u' '".$webServerPath."/TRANSCODE-CACHE/".$sum."/play.mp4'";
			$command .= '" | /usr/bin/at -M -q a now';

			#$command = 'echo "';
			#$command .= "/usr/bin/ffmpeg -i '".$fullLinkPath."'";
			#$command .= " '".$webServerPath."/TRANSCODE-CACHE/".$sum."/".$sum.".mp4'";
			#$command .= " touch '".$webServerPath."/TRANSCODE-CACHE/".$sum."/".$sum.".mp4.finished'";
			#$command .= '" | /usr/bin/at -M -q a now';

			# save the transcode command to a file
			file_put_contents("$webServerPath/TRANSCODE-CACHE/$sum/command.cfg","$command");
			# launch the command to post job in the queue
			shell_exec($command);
			# sleep to allow the transcode job to startup
			# build and display a m3u file with a delay using the spinner gif
			sleep(20);
		}
		if (file_exists($webServerPath."/TRANSCODE-CACHE/$sum/play.mp4")){
			# redirect to the mp4 file for the highest level of browser compatibility
			redirect("/TRANSCODE-CACHE/$sum/play.mp4");
		}else{
			# redirect to the master playlist
			redirect("/TRANSCODE-CACHE/$sum/play.m3u");
		}
	}else{
		# transcoding is disabled redirect to the transcode page
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
//////////////////////////////////////////////////////////////////////////////////
//return $output;
?>
