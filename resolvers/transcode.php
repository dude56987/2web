<!--
########################################################################
# 2web transcoder for converting and caching local video formats
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
<?PHP
ini_set('display_errors', 'On');
////////////////////////////////////////////////////////////////////////////////
function redirect($url,$debug=false){
	if ($debug){
		echo "<hr>";
		echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
		echo "<hr>";
		ob_flush();
		flush();
		exit();
		die();
	}else{
		// temporary redirect
		header('Location: '.$url,true,302);
		exit();
		die();
	}
}
////////////////////////////////////////////////////////////////////////////////
$webServerPath = $_SERVER['DOCUMENT_ROOT'];
////////////////////////////////////////////////////////////////////////////////
if (array_key_exists("link",$_GET)){
	# pull the link from
	$link = $_GET['link'];
	# create the sum of the link
	$sum=md5($link);
	if ( ! file_exists("RESOLVER-CACHE/$sum.mp4")){
		# cleanup html string encoding of spaces in pathnames
		$link = str_replace("%20"," ",$link);
		$link = str_replace("%21"," ",$link);
		# build the command
		//$command = "echo \" ffmpeg -i '".$webServerPath.$link."' -hls_playlist_type event -start_number 0 -master_pl_name ".$sum.".m3u -hls_time 20 -f hls 'RESOLVER-CACHE/".$sum."_stream.m3u'\" | at 'now'";
		$command = "echo \"ffmpeg -i '".$link."' '".$webServerPath."/RESOLVER-CACHE/$sum.mp4'\" | at 'now'";
		# launch the command to post job in the queue
		//echo "[COMMAND]: $command";
		shell_exec($command);
		sleep(20);
	}
	redirect('RESOLVER-CACHE/'.$sum.'.mp4');
}else{
	# no link was given to transcode, draw the interface
	echo "<div class='settingListCard'>";
	echo "<h1>Trancode links to mp4 in local cache</h1>";
	echo "<form method='get'>";
	echo "	<input class='button' width='60%' type='text' name='link' placeholder='/shows/showTitle/season 01/showTitle - s1e1 - episodeTitle.mp4'>";
	echo "	<input class='button' type='submit' value='Transcode link'>";
	echo "</form>";
	echo "</div>";
}
	//////////////////////////////////////////////////////////////////////////////////
	//return $output;
?>
