<?PHP
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
# check if the cache is over full
//$cleanCommand = '/usr/bin/nohup /usr/bin/sem --retries 10 --no-notice --ungroup --jobs 3 --id downloadQueue ';
//$cleanCommand = 'find RESOLVER-CACHE/ -type f -mtime +7 -delete';
//$cleanOutput = shell_exec($cleanCommand);
################################################################################
# force debugging
#$_GET['debug']='true';
################################################################################
if (array_key_exists("debug",$_GET)){
	echo "[DEBUG]: cleanCommand = ".$cleanCommand."<br>";
	echo "[DEBUG]: cleanOutput = ".$cleanOutput."<br>";
	echo '<hr>';
	echo '/usr/local/bin/youtube-dl -j '.$_GET['url']."<br>\n";
}
################################################################################
if (array_key_exists("url",$_GET)){
	$videoLink = $_GET['url'];
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: URL is ".$videoLink."<br>";
	}
	# remove parenthesis from video link if they exist
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
	}
	while(strpos($videoLink,'"')){
		if (array_key_exists("debug",$_GET)){
			echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
		}
		$videoLink = preg_replace('"','',$videoLink);
	}
	while(strpos($videoLink,"'")){
		if (array_key_exists("debug",$_GET)){
			echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
		}
		$videoLink = preg_replace("'","",$videoLink);
	}
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
	}
	$videoLink = '"'.$videoLink.'"';
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: Cleaning link ".$videoLink."<br>";
	}
	# create the md5sum of the file
	$sum = md5($videoLink);
	if (array_key_exists("debug",$_GET)){
		echo "[DEBUG]: MD5SUM is ".$sum."<br>";
	}
	# newgrounds will resolve properly with only the link, so resolve but do not cache
	if (strpos($videoLink,"newgrounds.com")){
		# if the link value is already set do NOT override the setting
		if (!array_key_exists("link",$_GET)){
			# set the link value to true
			$_GET['link']=true;
		}
	}
	// check for the cache flag
	if (array_key_exists("link",$_GET)){
		if (array_key_exists("debug",$_GET)){
			echo "[DEBUG]: linking to video ".$videoLink."<br>";
		}
		################################################################################
		# build a unique port to the stream, limit the streams to a numberA
		################################################################################
		# run the http server streaming server
		$command = '/usr/bin/nohup /usr/bin/sem --retries 1 --jobs 1 --id streamQueue ';
		$command = $command.'/usr/bin/timeout 40000 ';
		$command = $command.'/usr/local/bin/streamlink --player-external-http --player-external-http-port 4444 '.$videoLink.' worst';
		if (array_key_exists("debug",$_GET)){
			echo "[DEBUG]: launching streaming server with command ".$command."<br>";
		}
		# launch the stream server
		$output = shell_exec($command);
		# redirect to streamlink stream server restream
		header('Location: http://'.hostname().'.local:4444');
		exit();
		################################################################################
		# Get a direct link to the video bypassing the cache.
		# This works on most sites, except youtube.
		################################################################################
		$output = shell_exec('/usr/local/bin/youtube-dl --get-url '.$videoLink);
		if ($output == null){
			// the url was not able to resolve
			//echo "The URL '".$_GET['url']."' was unable to resolve...";
			echo "The URL was unable to resolve...<br>";
		}else{
			// output is the resolved url
			$url = $output;
			if (array_key_exists("debug",$_GET)){
				echo "<hr>";
				echo "<div>".$output."</div>";
				echo "<hr>";
				echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
				exit();
			}else{
				header('Location: '.$url);
				exit();
			}
		}
	}else{
		################################################################################
		# By default use the cache for video playback, this works on everything
		################################################################################
		echo "[DEBUG]: Creating resolver cache<br>";
		if ( ! file_exists("RESOLVER-CACHE/")){
			mkdir("RESOLVER-CACHE/");
		}
		// craft the url to the cache link
		$url = "RESOLVER-CACHE/".$sum.".mp4";
		echo "[DEBUG]: Checking path ".$url."<br>";
		################################################################################
		if (file_exists($url)){
			if(file_exists("RESOLVER-CACHE/".$sum.".part")){
				# this means the file is still downloading wait
				sleep(1);
				header('Location: http://'.gethostname().'.local:444/ytdl-resolver.php?url='.$videoLink);
				exit();
			}
			# add the webserver address to the local url
			# use mdns .local name resolution
			$url = "http://".gethostname().".local:444/".$url;
			echo "[DEBUG]: previous file download exists<br>";
			if (array_key_exists("debug",$_GET)){
				echo "<hr>";
				echo '<p>ResolvedUrl = <a href="'.$url.'">'.$url.'</a></p>';
				echo '<div>';
				echo '<video>';
				echo '<source src="'.$url.'">';
				echo '</video>';
				echo '</div>';
				exit();
			}else{
				header('Location: '.$url);
				exit();
			}
		################################################################################
		} else {
			echo "[DEBUG]: No file exists in the cache<br>";
			echo "[DEBUG]: cache is set<br>";
			// if the cache flag has been set to true then download the file and play it from the cache
			// build the command
			$command = '/usr/bin/nohup /usr/bin/sem --retries 10 --jobs 2 --id downloadQueue ';
			// add the download to the cache with the processing queue
			if (file_exists("/usr/local/bin/youtube-dl")){
				echo "[DEBUG]: PIP version of youtube-dl found<br>";
				$command = $command." '/usr/local/bin/youtube-dl";
			} else {
				$command = $command." 'youtube-dl";
			}
			if (array_key_exists("res",$_GET)){
				if($_GET["res"] == "HD"){
					$command = $command." -f best --recode-video mp4 ";
				} else if ($_GET["res"] == "SD") {
					$command = $command." -f worst --recode-video mp4 ";
				}
			} else {
				# default option in youtube-dl is SD
				$command = $command." -f worst --recode-video mp4 ";
			}
			# complete the command with the paths
			$command = $command.'-o "RESOLVER-CACHE/'.$sum.'.mp4" -c '.$videoLink."'";
			# launch the command to start downloading to the cache
			shell_exec($command);
			#####################################################
			# delay resolution untill file is finished downloading
			$tempSum=md5();
			$syncSeconds=0;
			while($syncSeconds < 200){
				sleep(1);
				# if the file has not changed in the last second
				if(md5("RESOLVER-CACHE/".$sum.".mp4") == $tempSum){
					$syncSeconds += 1;
				}else{
					$syncSeconds = 0;
				}
			}
			#####################################################
			header('Location: http://'.gethostname().'.local:444/RESOLVER-CACHE/'.$sum.'.mp4');
			exit();
			#####################################################
			# use ffmpeg to stream playback of the downloading file from the cache
			# THIS IS ONLY NESSASSARY because kodi will not play video from apache
			# correctly when the file is not complete downloading but will play correctly
			# when streamed though ffmpeg
			#####################################################
			# build a command to stream the downloading video over udp with ffmpeg
			$command = '/usr/bin/nohup /usr/bin/sem --retries 1 --jobs 1 --id streamQueue ';
			$command = $command.'/usr/bin/ffmpeg -i RESOLVER-CACHE/'.$sum.'.mp4';
			$command = $command.' -tune zerolatency -r 30 -preset ultrafast';
			$command = $command.' -vcodec hevc -f hevc "udp://'.hostname().'.local:444/stream"';
			shell_exec($command);
			# redirect to the generated stream
			header('Location: udp://'.gethostname().'.local:444/stream');
			exit();
			#####################################################
			header('Location: http://'.gethostname().'.local:444/ytdl-resolver.php?link=true&url='.$videoLink);
			exit();
			#####################################################
			// sleep 10 seconds after starting download
			//sleep(5);
			// wait for a file to be available to stream then redirect to it
			# read as writing method
			#####################################################
			$filelength=0;
			$activeChunk=0;
			header('Content-Type: video/mp4');
			$matchedTimes=0;
			$failWait=0;
			$tempFileSize=filesize('RESOLVER-CACHE/'.$sum.'.mp4');
			#####################################################
			# load the fileData
			#####################################################
			$handle=fopen(('RESOLVER-CACHE/'.$sum.'.mp4'),'rb');
			$fileData=array();
			while (!feof($handle)){
				# append the fileData array with the next chunk
				$fileData[]=fread($handle,64);
			}
			fclose($handle);
			#####################################################
			# start the read loop
			#####################################################
			while(true){
				#####################################################
				# read the active chunk
				#####################################################
				echo $fileData[($activeChunk-1)];
				# flush the buffer after writing
				ob_flush();
				flush();
				#####################################################
				# move the read head one move ahead
				#####################################################
				$activeChunk += 1;
				$tempFileSize=filesize('RESOLVER-CACHE/'.$sum.'.mp4');
				# if the read head is past the length of the file data
				if ($tempFileSize > $fileSize){
					#####################################################
					# update the file data since the read head has nothing left
					#####################################################
					# read the file and append the output if the file changes
					$handle=fopen(('RESOLVER-CACHE/'.$sum.'.mp4'),'rb');
					$fileData=array();
					while (!feof($handle)){
						# append the fileData array with the next chunk
						$fileData[]=fread($handle,64);
					}
					fclose($handle);
					#####################################################
					# check the number of chunks read from the file
					#####################################################
					$tempFileSize=filesize('RESOLVER-CACHE/'.$sum.'.mp4');

					if ($failWait > 100){
						#####################################################
						# if the file parity does not change for 100 seconds exit
						#####################################################
						exit();
					}else{
						#####################################################
						# sleep a second and increment failwait
						#####################################################
						sleep(1);
						$failWait += 1;
						# reset the chunk
						#$activeChunk -= 1;
					}
				}else{
					$failWait=0;
				}
			}
		}
	}
}else{
	// no url was given at all
	echo "No url was specified to the resolver!<br>";
	echo "Please give a valid URL to a video to be resolved.<br>";
	echo "<form method='get'>";
	echo "<input width='60%' type='text' name='url'>";
	echo "<input width='10%' type='checkbox' name='debug'>";
	echo "<input type='submit'>";
	echo "</form>";
	echo '</a>';
	echo "<hr>";
	echo "<h2>EXAMPLES</h2>";
	echo "<ul>";
	echo '	<li>';
	echo '		http://'.gethostname().'.local:444/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local:444/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().'.local:444/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().':444/ytdl-resolver.php?url="http://videoUrl/videoid/"';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().':444/ytdl-resolver.php?url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo '	<li>';
	echo '		http://'.gethostname().':444/ytdl-resolver.php?link=true&url="http://videoUrl/videoid/"&debug=true';
	echo '	</li>';
	echo "</ul>";
}
?>
