#! /bin/bash
################################################################################
# php2web allows loading html5 apps into 2web
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
# enable debug log
#set -x
################################################################################
source /var/lib/2web/common
################################################################################
function downloadDir(){
	# write path to console
	echo "/var/cache/2web/downloads/applications/"
}
################################################################################
function generatedDir(){
	# write path to console
	echo "/var/cache/2web/generated/applications/"
}
################################################################################
function updateApplication(){
	# update a application file

	# load arguments
	appSum="$1"
	foundAppFile="$2"

	# get the sum of the downloaded cache file
	newFileSum=$(md5sum "$foundAppFile" | cut -d' ' -f1 )

	addToLog "DEBUG" "Checking app sums" "Checking Sum at '/var/cache/2web/generated/appSums/$appSum.cfg'"
	# check if the file is already installed
	if test -f "/var/cache/2web/generated/appSums/$appSum.cfg";then
		# if the file is installed get the file sum
		oldFileSum="$(cat "/var/cache/2web/generated/appSums/$appSum.cfg")"
		addToLog "DEBUG" "Checking app sums" "Sum '$appSum' was found, loading sum '$oldFileSum'"
	else
		oldFileSum="0"
		addToLog "DEBUG" "Checking app sums" "Sum '$appSum' was not found, using defaultSum '$oldFileSum'"
	fi

	addToLog "DEBUG" "Checking app sums" "Comparing old sum '$oldFileSum' to new sum '$newFileSum'."

	# check the new file is diffrent than the old file by comparing the sums
	if [ "$newFileSum" == "$oldFileSum" ];then
		ALERT "No changes to application..."
		addToLog "DEBUG" "Checking app sums" "App '$appSum' has no changes."
	else
		################################################################################
		# update the application
		################################################################################
		addToLog "UPDATE" "Updating application" "Updating application at '$foundAppFile'"
		# remove existing application version
		if test -f "/var/cache/2web/web/applications/$appTitle/";then
			rm -rv "/var/cache/2web/web/applications/$appTitle/"
		fi

		# read the metadata found inside the zip file

		# read file list from zip file and scan for metadata files
		zipFileList=$(unzip -l "$foundAppFile")

		# check for the index file
		# - at least one of these must exist in order to load the application in the web page
		if echo "$zipFileList" | grep -q "index.php";then
			INFO "This is a valid application..."
		elif echo "$zipFileList" | grep -q "index.html";then
			INFO "This is a valid application..."
		elif echo "$zipFileList" | grep -q "index.htm";then
			INFO "This is a valid application..."
		elif echo "$zipFileList" | grep -q "main.php";then
			INFO "This is a valid application..."
		else
			####################################
			# This is a catastrophic error
			####################################
			ALERT "This is a broken application..."
			# if the index file can not be found this is not a application that can be loaded
			addToLog "ERROR" "Broken App" "The found application file '$foundAppFile' is broken because it contains no 'index.php', 'index.html', or 'index.htm' file to load."
			# update the sum so the update will not try to install again and fail again
			echo "$newFileSum" > "/var/cache/2web/generated/appSums/$appSum.cfg"
			# exit the function to stop the processing
			return
		fi

		####################################
		# load the metadata in the zip file
		####################################
		# look for the title
		if echo "$zipFileList" | grep -q "title.cfg";then
			appTitle=$(unzip "$foundAppFile" -p "title.cfg")
		else
			# show a warning
			addToLog "WARNING" "Partial App" "The found application file '$foundAppFile' is broken because it contains no application title. Acceptable paths are 'title.cfg'. One will be generated..."
			appTitle=$(basename "$foundAppFile" | cut -d'.' -f1)
		fi
		# remove any previously existing application version
		if test -f "/var/cache/2web/web/applications/$appTitle/";then
			rm -rv "/var/cache/2web/web/applications/$appTitle/"
		fi
		# create the app directory
		createDir "/var/cache/2web/web/applications/$appTitle/"
		createDir "/etc/2web/applications/settings/$appTitle/"

		# extract the application
		unzip "$foundAppFile" -d "/var/cache/2web/web/applications/$appTitle/"

		# only add the player if the app is not a 2web full intergration app
		# - includes 2web header and footer in the app
		if ! grep -q "/var/cache/2web/web/header.php" "$webDirectory/applications/$appTitle/index.php";then
			linkFile "/usr/share/2web/templates/appPlayer.php" "$webDirectory/applications/$appTitle/2webAppPlayer.php"
		fi

		################################################################################
		# look for the icon
		################################################################################
		if test -f "/var/cache/2web/web/applications/$appTitle/index.icon.png";then
			convert -quiet "/var/cache/2web/web/applications/$appTitle/index.icon.png" "/var/cache/2web/web/applications/$appTitle/icon.png"
		fi
		# search for a favicon to convert into a icon image file
		if test -f "/var/cache/2web/web/applications/$appTitle/favicon.ico";then
			convert -quiet "/var/cache/2web/web/applications/$appTitle/favicon.ico" "/var/cache/2web/web/applications/$appTitle/icon.png"
		fi
		# search for icon image variants
		if test -f "/var/cache/2web/web/applications/$appTitle/web-icon.png";then
			convert -quiet "/var/cache/2web/web/applications/$appTitle/web-icon.png" "/var/cache/2web/web/applications/$appTitle/icon.png"
		fi
		# if no icon has been found, scan for one
		if ! test -f "/var/cache/2web/web/applications/$appTitle/icon.png";then
			# scan the directory for a favicon in case it is in a subdirectory
			find "/var/cache/2web/web/applications/$appTitle/" -type f -name 'favicon.ico' | while read -r webIconPath;do
				convert -quiet "$webIconPath" "/var/cache/2web/web/applications/$appTitle/icon.png"
				break
			done
		fi
		# if no icon exists after the extraction process, then generate one
		if ! test -f "/var/cache/2web/web/applications/$appTitle/icon.png";then
			# show a warning
			addToLog "WARNING" "Partial App" "The found application file '$foundAppFile' is broken because it contains no icon or logo file to load. Acceptable paths are '$iconList'. One will be generated..."
			# generate a icon for the application
			demoImage "/var/cache/2web/web/applications/$appTitle/icon.png" "$appTitle" "400" "400"
		fi
		################################################################################
		# look for the version information
		################################################################################
		if ! test -f "/var/cache/2web/web/applications/$appTitle/version.cfg";then
			# generate version number as a hash sum based on the .zip file
			versionNumber=$(md5sum "$foundAppFile" | cut -d' ' -f1 )
			# store the generated version hash
			echo "$versionNumber" > "/var/cache/2web/web/applications/$appTitle/version.cfg"
		fi
		################################################################################
		# look for the about text
		################################################################################
		#
		if test -f "/var/cache/2web/web/applications/$appTitle/README.md";then
			markdown "/var/cache/2web/web/applications/$appTitle/README.md" > "/var/cache/2web/web/applications/$appTitle/about.cfg"
		fi
		################################################################################
		# look for the help text
		################################################################################
		if test -f "/var/cache/2web/web/applications/$appTitle/help.md";then
			markdown "/var/cache/2web/web/applications/$appTitle/help.md" > "/var/cache/2web/web/applications/$appTitle/help.cfg"
		fi
		################################################################################
		# look for the license
		################################################################################
		if test -f "/var/cache/2web/web/applications/$appTitle/LICENSE.md";then
			markdown "/var/cache/2web/web/applications/$appTitle/LICENSE.md" > "/var/cache/2web/web/applications/$appTitle/license.cfg"
		fi
		################################################################################
		# generate the thumbnail for the application index
		################################################################################
		{
			if ! grep -q "/var/cache/2web/web/header.php" "$webDirectory/applications/$appTitle/index.php";then
				echo "<a href='/applications/$appTitle/2webAppPlayer.php' class='indexSeries' >"
			else
				echo "<a href='/applications/$appTitle/' class='indexSeries' >"
			fi
			echo "<img loading='lazy' src='/applications/$appTitle/icon.png' />"
			echo "<div>$appTitle</div>"
			echo "</a>"
		} > "$webDirectory/applications/$appTitle/applications.index"

		# add the application to the app index
		SQLaddToIndex "$webDirectory/applications/$appTitle/applications.index" "$webDirectory/data.db" "applications"
		SQLaddToIndex "$webDirectory/applications/$appTitle/applications.index" "$webDirectory/data.db" "all"

		#
		SQLaddToIndex "/applications/$appTitle/icon.png" "$webDirectory/backgrounds.db" "applications_poster"
		SQLaddToIndex "/applications/$appTitle/icon.png" "$webDirectory/backgrounds.db" "applications_fanart"
		SQLaddToIndex "/applications/$appTitle/icon.png" "$webDirectory/backgrounds.db" "poster_all"
		SQLaddToIndex "/applications/$appTitle/icon.png" "$webDirectory/backgrounds.db" "fanart_all"

		# add new php to log
		addToLog "UPDATE" "Updating Application" "Adding application '$appTitle'"

		# add the application to the main application index since it has been updated
		addToIndex "$webDirectory/applications/$appTitle/applications.index" "$webDirectory/applications/applications.index"

		# add the updated show to the new application index
		addToIndex "$webDirectory/applications/$appTitle/applications.index" "$webDirectory/new/applications.index"
		addToIndex "$webDirectory/applications/$appTitle/applications.index" "$webDirectory/new/all.index"

		# random indexes
		linkFile "$webDirectory/applications/applications.index"  "$webDirectory/random/applications.index"

		# update last updated times
		date "+%s" > /var/cache/2web/web/new/all.cfg
		date "+%s" > /var/cache/2web/web/new/applications.cfg

		# update the sum so the update will not be installed more than once
		echo "$newFileSum" > "/var/cache/2web/generated/appSums/$appSum.cfg"
	fi
}
################################################################################
update(){
	addToLog "INFO" "STARTED Web Update" "$(date)"
	# read the download directory and convert phps into webpages
	# - There are 2 types of directory structures for phps in the download directory
	#   + phpWebsite/phpName/chapter/image.png
	#   + phpWebsite/phpName/image.png

	webDirectory=$(webRoot)
	appDirectory=$(loadConfigs "/etc/2web/applications/libaries.cfg" "/etc/2web/applications/libaries.d/" "/etc/2web/config_default/php2web_libaries.cfg" | tr -s "\n" | tr -d "\t" | tr -d "\r" | sed "s/^[[:blank:]]*//g" | shuf )

	ALERT "$appDirectory"

	# NOTE: php2web does not have a kodi directory because kodi can not support them

	# create the web directory
	createDir "$webDirectory/applications/"
	# create the settings directory
	createDir "/etc/2web/applications/settings/"
	# create the sum directory
	createDir "/var/cache/2web/generated/appSums/"

	# link the homepage
	linkFile "/usr/share/2web/templates/apps.php" "$webDirectory/applications/index.php"

	# check for parallel processing and count the cpus
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi

	ALERT "Scanning libary config '$appDirectory'"
	echo "$appDirectory" | sort | while read phpLibaryPath;do
		ALERT "Scanning Application Libary Path... '$phpLibaryPath'"
		addToLog "INFO" "Scanning Application Library..." "$phpLibaryPath"
		# read the applications found in the library path
		find "$phpLibaryPath" -mindepth 1 -type f -name '*.zip' | sort | while read foundAppFile;do
			INFO "Found application '$foundAppFile'"
			# create a sum of the app file found based on the path
			appSum=$(echo "$foundAppFile" | md5sum | cut -d' ' -f1 )
			# update the application if it has changed
			updateApplication "$appSum" "$foundAppFile" &
			#
			waitQueue 0.2 "$totalCPUS"
		done
	done
	# block for parallel threads here
	blockQueue 1
	addToLog "INFO" "FINISHED Web Update" "$(date)"
}
################################################################################
function resetCache(){
	# reset all generated/downloaded content
	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"
	# remove all the index files generated by the website
	find "$webDirectory/phps/" -name "*.index" -delete
	# remove web cache
	rm -rv "$webDirectory/phps/" || INFO "No php web directory at '$webDirectory/phps/'"
	rm -rv "$webDirectory/thumbnails/phps/" || INFO "No php web directory at '$webDirectory/thumbnails/phps/'"
	#
	rm -rv "/var/cache/2web/generated/phpCache/" || INFO "No path to remove at '/var/cache/2web/generated/phpCache/'"
}
################################################################################
function nuke(){
	webDirectory="$(webRoot)"
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedRoot)"
	# remove new and random indexes
	rm -rv "$webDirectory/new/applications.index" || INFO "No path to remove at '$webDirectory/new/applications.index'"
	rm -rv "$webDirectory/random/applications.index" || INFO "No path to remove at '$webDirectory/new/applications.index'"
	# remove php directory and indexes
	rm -rv "$webDirectory/applications/"
	# remove application sums
	rm -rv "/var/cache/2web/generated/appSums/"
	# remove sql data
	sqlite3 $webDirectory/data.db "drop table apps;"
	# remove widgets cached
	rm -v $webDirectory/web_cache/widget_random_applications.index
	rm -v $webDirectory/web_cache/widget_new_applications.index
	drawLine
	ALERT "NUKE completed. php2web section of the website has been removed. To regenerate data in this section run 'php2web'"
	drawLine
}
################################################################################
################################################################################
main(){
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		lockProc "php2web"
		checkModStatus "php2web"
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		lockProc "php2web"
		checkModStatus "php2web"
		update "$@"
	elif [ "$1" == "--demo-data" ] || [ "$1" == "demo-data" ] ;then
		echo "No demo data can be generated for php2web."
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "php2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "php2web"
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		echo "No packages for php2web."
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/php2web.txt"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "php2web Version: "
		cat /usr/share/2web/version_php2web.cfg
	else
		lockProc "php2web"
		checkModStatus "php2web"
		update "$@"
		# on default execution show the server links at the bottom of output
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/applications/"
		drawLine
		echo "http://$(hostname).local:80/settings/apps.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
