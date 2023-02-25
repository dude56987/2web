<!--
########################################################################
# 2web random channels widget
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
<?php
# if no channel index exists exit
if (file_exists($_SERVER['DOCUMENT_ROOT']."/totalChannels.index")){
	$cacheFile=$_SERVER['DOCUMENT_ROOT']."/web_cache/randomChannels.index";
	if (file_exists($cacheFile)){
		if (time()-filemtime($cacheFile) > 2 * 3600){
			// update the cached file
			$writeFile=true;
		}else{
			// read from the already cached file
			$writeFile=false;
		}
	}else{
		# write the file if it does not exist
		$writeFile=true;
	}
	if ($writeFile){
		$fileObj=fopen($cacheFile,'w') or die("Unable to write cache file!");
		// get a list of all the genetrated index links for the page
		$sourceFiles = explode("\n",shell_exec("ls -t1 ".$_SERVER['DOCUMENT_ROOT']."/live/index/channel_*.index | shuf"));
		$counter=0;
		foreach($sourceFiles as $sourceFile){
			$sourceFileName = $sourceFile;
			if (file_exists($sourceFile)){
				if (is_file($sourceFile)){
					if (strpos($sourceFile,".index")){
						$counter += 1;
						if ($counter == 1){
							fwrite($fileObj,"<div class='titleCard'>");
							fwrite($fileObj,"<h1>Random Channels</h1>");
							fwrite($fileObj,"<div class='listCard'>");
						}
						// read the index entry
						$data=file_get_contents($sourceFile);
						// write the index entry
						fwrite($fileObj,"$data");
					}
				}
				if ($counter >= 40){
					break;
				}
			}
		}
		fwrite($fileObj,"</div>");
		fwrite($fileObj,"</div>");
		// close the file
		fclose($fileObj);
	}
	// read the file that is cached
	echo file_get_contents($cacheFile);
	// flush the buffer
	flush();
	ob_flush();
}
?>
