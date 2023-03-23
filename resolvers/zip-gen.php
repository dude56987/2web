<!--
########################################################################
# 2web zip generator to create and cache playlists on-demand
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
// NOTE: Do not write any text to the document, this will break the redirect
// redirect the given file to the resoved url found with youtube-dl
################################################################################
################################################################################
# force debugging
#$_GET['debug']='true';
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
include("/usr/share/2web/2webLib.php");
################################################################################
function runExternalProc($command){
	$client= new GearmanClient();
	$client->addServer();
	$client->addFunction();
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
function redirect($url){
	if (array_key_exists("debug",$_GET)){
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
################################################################################
function zip_gen($title){
	# - section can be music,shows
	# - title can be a artist
	$rootServerPath = $_SERVER['DOCUMENT_ROOT'];
	if (array_key_exists("comic",$_GET)){
		$rootPath = $_SERVER['DOCUMENT_ROOT']."/kodi";
	}else if (array_key_exists("repo",$_GET)){
		$rootPath = $_SERVER['DOCUMENT_ROOT'];
	}

	$title = str_replace('"','',$title);

	if (array_key_exists("comic",$_GET)){
		if (array_key_exists("chapter",$_GET)){
			$comicPath = "$rootPath/comics/$title/".$_GET['chapter']."/";
		}else{
			$comicPath = "$rootPath/comics_tank/$title/";
		}
	}else if (array_key_exists("repo",$_GET)){
		#$comicPath = file_get_contents("$rootPath/repos/$title/source.index");
		$comicPath = "$rootPath/repos/$title/source/";
	}

	debug("Checking $comicPath is a directory...<br>\n");

	# create the cache if it does not exist
	if (! is_dir($rootServerPath."/zip_cache/")){
		mkdir("$rootServerPath/zip_cache/");
	}

	$cacheSum = md5($title);

	# build zip file path
	if (array_key_exists("cbz",$_GET)){
		$tempExt="cbz";
	}else{
		$tempExt="zip";
	}
	if (array_key_exists("chapter",$_GET)){
		$chapter=" - Chapter - ".$_GET['chapter'];
	}else{
		$chapter="";
	}

	$cacheFilePath = "/zip_cache/".$title.$chapter.".".$tempExt;
	$fullCacheFilePath = $rootServerPath."/zip_cache/".$title.$chapter.".".$tempExt;

	$totalFileList=Array();

	// check for existing redirect
	if (is_file($fullCacheFilePath)){
		# redirect to the built cache file if it exists
		redirect($cacheFilePath);
	}else{
		# ignore user abort of connection
		ignore_user_abort(true);
		# set execution time limit to 15 minutes
		set_time_limit(900);

		debug("Creating zipfile at: $fullCacheFilePath<br>\n");
		// create the zip file
		$data = new ZipArchive();
		$data->open($fullCacheFilePath, ZipArchive::CREATE);
		// recursive scan comic path for image files
		$foundFiles = recursiveScan($comicPath);

		foreach ($foundFiles as $filePath){
			# cleanup the scan data by removing the site root path, from the file before adding it to the zip
			$filePath = str_replace($_SERVER['DOCUMENT_ROOT'],"",$filePath);

			debug("File exists adding file...<br>\n");
			$fileName=popPath($filePath);
			debug("Storing file in $title/$fileName<br>\n");

			if (array_key_exists("comic",$_GET)){
				if (strpos($filePath,".jpg") || strpos($filePath,".png") || strpos($filePath,".jpeg") || strpos($filePath,".webm") || strpos($filePath,".gif")){
					debug("full file path: ".$_SERVER['DOCUMENT_ROOT']."$filePath<br>\n");
					if (file_exists($_SERVER['DOCUMENT_ROOT'].$filePath)){
						# write each file to the zip archive on disk individually
						if (array_key_exists("chapter",$_GET)){
							$data->addFile($_SERVER['DOCUMENT_ROOT'].$filePath, $title.$chapter."/".$fileName);
						}else{
							$data->addFile($_SERVER['DOCUMENT_ROOT'].$filePath, $title."/".$fileName);
						}
					}else{
						debug("File does not exist...<br>\n");
						# write each file to the zip archive on disk individually
						$data->addFile($_SERVER['DOCUMENT_ROOT'].$filePath, $title."/".$fileName);
					}
				}
			}else if (array_key_exists("repo",$_GET)){
				#$internalFilePath=$filePath;
				$internalFilePath=str_replace("/repos/$title/source","",$filePath);
				#$internalFilePath=str_replace("/repos/$title","",$filePath);
				debug("file path: $filePath<br>\n");
				debug("full file path: ".$_SERVER['DOCUMENT_ROOT']."$filePath<br>\n");
				debug("internal file path: $internalFilePath<br>\n");
				$data->addFile($_SERVER['DOCUMENT_ROOT'].$filePath, $title.$internalFilePath);
			}
		}
		$data->close();
		#$data->close();
	}

	// close the file
	#fclose($data);
	// redirect to episode path
	redirect($cacheFilePath);
}
################################################################################
if (array_key_exists("comic",$_GET)){
	echo "Building Comic Zipfile...<br>\n";

	$comicTitle = $_GET['comic'];

	zip_gen($comicTitle);
	exit();
}else if (array_key_exists("repo",$_GET)){
	echo "Building Repo Zipfile...<br>\n";

	$repoTitle = $_GET['repo'];

	zip_gen($repoTitle);
	exit();
}else{
	// no url was given at all
	echo "<html>";
	echo "<head>";
	echo "<link rel='stylesheet' href='style.css'>";
	echo "<title>2web Zip Generator</title>";
	echo "</head>";
	echo "<body>";
	echo "<div class='settingListCard'>";
	echo "<h2>Manual Video Cache Interface</h2>";
	echo "No url was specified to the resolver!<br>";
	echo "To Cache a video and play it from here you can use the below form.<br>";
	echo "<form method='get'>";
	echo "	<input class='button' width='60%' type='text' name='url'>";
	echo "	<input class='button' type='submit' value='Cache Url'>";
	echo "	<div>";
	echo "		<span>Enable Debug Output</span>";
	echo "		<input class='button' width='10%' type='checkbox' name='debug'>";
	echo "	</div>";
	echo "</form>";
	echo "</div>";
	echo "<hr>";
	echo "<div class='settingListCard'>";
	echo "	<h2>WEB API EXAMPLES</h2>";
	echo "	<p>";
	echo "		Replace the url api key with your video web link to be cached by youtube-dl.";
	echo "		Debug=true will generate a webpage containing debug data and video output";
	echo "	</p>";
	echo "<ul>";
	echo '	<li>';
	echo '		http://'.$_SERVER["HTTP_HOST"].'/zip-gen.php?comic="comicName"';
	echo '	</li>';
	echo "</ul>";
	echo "</div>";
	echo "<div class='settingListCard'>";
	echo "<h2>Random Cached Playlists</h2>";
	$sourceFiles = explode("\n",shell_exec("ls -t1 zip_gen/*.zip"));
	// reverse the time sort
	# build the video index
	foreach($sourceFiles as $sourceFile){
		if (file_exists($sourceFile)){
			if (is_file($sourceFile)){
				echo "<a class='showPageEpisode' href='".$sourceFile."'>";
				echo "	<h3>".$sourceFile."</h3>";
				echo "</a>";
			}
		}
	}
	echo "</div>";
	//include("header.html");
	echo "</body>";
	echo "</html>";
}
?>
