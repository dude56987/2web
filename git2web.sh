#! /bin/bash
################################################################################
# git2web generates websites from image filled directories
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
# enable debug log
#set -x
################################################################################
source /var/lib/2web/common
################################################################################
function generatedDir(){
	if [ ! -f /etc/2web/repos/generated.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/generated_repos/"
		} >> "/etc/2web/repos/generated.cfg"
		createDir "/var/cache/2web/generated_repos/"
	fi
	# write path to console
	cat "/etc/2web/repos/generated.cfg"
}
################################################################################
function downloadDir(){
	if [ ! -f /etc/2web/repos/download.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/download_repos/"
		} >> "/etc/2web/repos/download.cfg"
		createDir "/var/cache/2web/download_repos/"
	fi
	# write path to console
	cat "/etc/2web/repos/download.cfg"
}
################################################################################
function libaryPaths(){
	# add the download directory to the paths
	echo "$(downloadDir)"
	# check for server libary config
	if [ ! -f /etc/2web/repos/libaries.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/downloads_repos/"
		} >> "/etc/2web/repos/libaries.cfg"
	fi
	# write path to console
	cat "/etc/2web/repos/libaries.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/repos/libaries.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		cat "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
	# add the generated repos directories
	printf "$(generatedDir)/\n"
}
################################################################################
function update(){
	#DEBUG
	#set -x
	# this will launch a processing queue that downloads updates to repos
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/repos/sources.cfg;then
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/git2web_sources.cfg
		} > /etc/2web/repos/sources.cfg
	fi
	# load sources
	reposources=$(grep -v "^#" /etc/2web/repos/sources.cfg)
	reposources=$(echo -e "$reposources\n$(grep -v --no-filename "^#" /etc/2web/repos/sources.d/*.cfg)")

	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedDir)"
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make repos directory
	createDir "$webDirectory/repos/"
	# scan the sources
	ALERT "git Download Sources: $reposources"
	#for reposource in $reposources;do
	echo "$reposources" | while read reposource;do
		# generate a sum for the source
		reposum=$(echo "$reposource" | sha512sum | cut -d' ' -f1)
		# create the repo directory
		createDir "$webDirectory/repos/$reposum/"
		# do not process the git if it is still in the cache
		# - Cache removes files older than x days
		if ! test -f "$webDirectory/gitCache/download_$reposum.index";then
			# clone and update remote git repositories on the server
			ALERT "No clone repos yet..."
		fi
	done

	# cleanup the repos index
	if test -f "$webDirectory/repos/repos.index";then
		tempList=$(cat "$webDirectory/repos/repos.index" | sort -u )
		echo "$tempList" > "$webDirectory/repos/repos.index"
	fi
	# cleanup new git index
	if test -f "$webDirectory/new/repos.index";then
		# new repos but preform a fancy sort that does not change the order of the items
		#tempList=$(cat -n "$webDirectory/new/repos.index" | sort -uk2 | sort -nk1 | cut -f1 | tail -n 200 )
		tempList=$(cat "$webDirectory/new/repos.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/repos.index"
	fi
}
################################################################################
function processRepo(){
	repoSource=$1

	cd "$repoSource"
	repoSum=$(echo "$repoSource" | md5sum | cut -d' ' -f1)
	repoName=$(echo "$repoSource" | rev | cut -d'/' -f2 | rev)
	createDir "$webDirectory/repos/$repoSum/"
	createDir "$webDirectory/repos/$repoSum/lint/"
	createDir "$webDirectory/repos/$repoSum/diff/"
	createDir "$webDirectory/repos/$repoSum/log/"
	createDir "$webDirectory/repos/$repoSum/date/"
	createDir "$webDirectory/repos/$repoSum/author/"
	createDir "$webDirectory/repos/$repoSum/email/"
	createDir "$webDirectory/repos/$repoSum/msg/"
	echo "$repoName" > "$webDirectory/repos/$repoSum/title.index"
	# link the repo page
	linkFile "/usr/share/2web/templates/repo.php" "$webDirectory/repos/${repoSum}/index.php"
	# generate the website content
	#gitinspector --format=htmlembedded -f "**" -T true -H true -w false -m true --grading=true "$repoSource" |\
	gitinspector --format=text -f "**" -T true -H true -w false -m true --grading=true "$repoSource" |\
	grep --invert-match --ignore-case "<html" |\
	grep --invert-match --ignore-case "</html" |\
	grep --invert-match --ignore-case "<head" |\
	grep --invert-match --ignore-case "</head" |\
	grep --invert-match --ignore-case "<body" |\
	grep --invert-match --ignore-case "</body" |\
	grep --invert-match --ignore-case "<meta" |\
	grep --invert-match --ignore-case "<title" |\
	grep --invert-match --ignore-case "<?xml" \
	> "$webDirectory/repos/$repoSum/inspector.html"

	# get the latest commit time
	git show --no-patch --no-notes --pretty='%cd' > "$webDirectory/repos/$repoSum/origin.index"
	# get the origin
	git remote show origin > "$webDirectory/repos/$repoSum/origin.index"
	# build history video in 720p
	if echo "$@" | grep -q -e "--parallel";then
		gource --key --max-files 0 -s 1 -c 4 -1280x720 -o - |\
		ffmpeg -y -r 60 -f image2pipe -vcodec ppm -i - -vcodec libx264 -preset ultrafast -pix_fmt yuv420p -crf 1 -threads $totalCPUS -bf 0 \
		"$webDirectory/repos/$repoSum/repoHistory.mp4"
	else
		gource --key --max-files 0 -s 1 -c 4 -1280x720 -o - |\
		ffmpeg -y -r 60 -f image2pipe -vcodec ppm -i - -vcodec libx264 -preset ultrafast -pix_fmt yuv420p -crf 1 -bf 0 \
		"$webDirectory/repos/$repoSum/repoHistory.mp4"
	fi

	commitAddresses=$(git log --oneline | cut -d' ' -f1)

	echo "$commitAddresses" > "$webDirectory/repos/$repoSum/commits.index"

	#IFS=$'\n'
	#for commitAddress in $commitAddresses;do
	#git log --oneline | cut -d' ' -f1 | while read commitAddress;do
	echo "$commitAddresses" | while read commitAddress;do
		#commitAddress=$(echo "$commitAddress" | cut -d' ' -f1)
		git show "$commitAddress" --stat | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoSum/log/$commitAddress.index" &
		git diff "$commitAddress" | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoSum/diff/$commitAddress.index" &
		git show "$commitAddress" --no-patch --no-notes --pretty='%cd' > "$webDirectory/repos/$repoSum/date/$commitAddress.index" &
		git show "$commitAddress" --no-patch --no-notes --pretty='%an' > "$webDirectory/repos/$repoSum/author/$commitAddress.index" &
		git show "$commitAddress" --no-patch --no-notes --pretty='%ae' > "$webDirectory/repos/$repoSum/email/$commitAddress.index" &
		git show "$commitAddress" --no-patch --no-notes --pretty='%s' > "$webDirectory/repos/$repoSum/msg/$commitAddress.index" &
	done
	wait
	# generate html from README.md if found in repo
	if test -f "$repoSource/README.md";then
		if test -f /usr/bin/pandoc;then
			pandoc "$repoSource/README.md" -t html -o "$webDirectory/repos/$repoSum/readme.index"
		elif test -f /usr/bin/markdown;then
			markdown "$repoSource/README.md" > "$webDirectory/repos/$repoSum/readme.index"
		fi
	fi

	# - build a svg graph by building a single bar for each day going back 365 days,
	# - each commit on a day should make the bar 1px higher
	graphHeight=100
	graphData=""
	barWidth=3
	graphWidth=$((365 * $barWidth ))
	for index in {1..365};do
		# check the number of commits for each day
		commits=$(git log --oneline --before "$(( $index - 1 ))days ago" --after "$index days ago" | wc -l)
		commits=$(( $commits * ($barWidth * 2) ))
		if [ $commits -gt $graphHeight ];then
			graphHeight=$(( $commits * $barWidth ))
		fi
		graphX=$(( $index * $barWidth ))
		#graphData="$graphData<line x1=\"$graphX\" y1=\"0\" x2=\"$graphX\" y2=\"$commits\" style=\"stroke:rgb(255,255,255);stroke-width:1\" />"
		graphData="$graphData<rect x=\"$graphX\" y=\"0\" width=\"$barWidth\" height=\"$commits\" style=\"fill:white;stroke:gray;stroke-width:1\" />"
	done
	{
		echo "<svg height=\"$graphHeight\" width=\"$graphWidth\">"
		echo "$graphData"
		echo "</svg>"
	} > "$webDirectory/repos/$repoSum/graph.svg"


	# run lint on all the existing files that support it
	find "$repoSource" -type f -name "*.sh" | sort | while read sourceFilePath;do
		#tempSourceSum=$(popPath "$sourceFilePath" | md5sum | cut -d' ' -f1)
		tempSourceSum=$(popPath "$sourceFilePath")
		shellcheck "$sourceFilePath" > "$webDirectory/repos/$repoSum/lint/$tempSourceSum.index"
	done
	find "$repoSource" -type f -name "*.php" -o -name "*.html" | sort | while read sourceFilePath;do
		#tempSourceSum=$(popPath "$sourceFilePath" | md5sum | cut -d' ' -f1)
		tempSourceSum=$(popPath "$sourceFilePath")
		weblint "$sourceFilePath" > "$webDirectory/repos/$repoSum/lint/$tempSourceSum.index"
	done

	# build a qr code for the icon link
	qrencode -m 1 -l H -o "/var/cache/2web/web/repos/$repoSum/thumb.png" "http://$(hostname).local/repos/$repoSum/"

	#	build the index file for this entry if one does not exist
	if ! test -f "$gitNamePath/repo.index";then
		{
			echo "<a href='/repos/$repoSum/' class='indexSeries' >"
			echo "<img loading='lazy' src='/repos/$repoSum/thumb.png' />"
			echo "<div>$repoName</div>"
			echo "</a>"
		} > "$webDirectory/repos/$repoSum/repos.index"
	fi

	# add to the system indexes
	SQLaddToIndex "$webDirectory/repos/$repoSum/repos.index" "$webDirectory/data.db" "repos"
	addToIndex "$webDirectory/repos/$repoSum/repos.index" "$webDirectory/repos/repos.index"
}
################################################################################
webUpdate(){
	# read the download directory and convert repos into webpages
	# - There are 2 types of directory structures for repos in the download directory
	#   + gitWebsite/gitName/chapter/image.png
	#   + gitWebsite/gitName/image.png

	webDirectory=$(webRoot)
	downloadDirectory="$(libaryPaths | tr -s '\n' | shuf )"

	ALERT "$downloadDirectory"

	# create the kodi directory
	createDir "$webDirectory/kodi/repos/"

	# create the web directory
	createDir "$webDirectory/repos/"

	# link the homepage
	linkFile "/usr/share/2web/templates/repos.php" "$webDirectory/repos/index.php"

	# link the random poster script
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/repos/randomPoster.php"
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/repos/randomFanart.php"

	# link the kodi directory to the download directory
	#ln -s "$downloadDirectory" "$webDirectory/kodi/repos"

	# check for parallel processing and count the cpus
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(grep "processor" "/proc/cpuinfo" | wc -l)
		totalCPUS=$(( $totalCPUS / 2 ))
	fi
	totalrepos=0

	ALERT "Scanning libary config '$downloadDirectory'"
	echo "$downloadDirectory" | sort | while read repoSource;do
		startDebug
		echo "$repoSource"
		if test -d "$repoSource/.git/";then
			if echo "$@" | grep -q -e "--parallel";then
				processRepo "$repoSource" &
				waitQueue 0.5 "$totalCPUS"
			else
				processRepo "$repoSource"
			fi
			# increment the total repo counters
			totalRepos=$(( $totalRepos + 1 ))
		else
			INFO "No repo found!"
		fi
		stopDebug
	done
	# block for parallel threads here
	if echo "$@" | grep -q -e "--parallel";then
		blockQueue 1
	fi
	INFO "Writing total repos "
	echo "$totalrepos" > "$webDirectory/repos/totalrepos.cfg"

	# finish building main index page a-z
	linkFile "/usr/share/2web/templates/repos.php" "$webDirectory/repos/index.php"

	# the random index simply uses the main index for repos
	linkFile "$webDirectory/repos/repos.index" "$webDirectory/random/repos.index"
}
################################################################################
function resetCache(){
	# reset all generated/downloaded content
	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"
	# remove all the index files generated by the website
	find "$webDirectory/repos/" -name "*.index" -delete

	# remove web cache
	rm -rv "$webDirectory/repos/" || INFO "No git web directory at '$webDirectory/repos/'"

	#
	echo "You MUST remove downloaded repos manually they are stored at:"
	echo "$downloadDirectory"
}
################################################################################
function nuke(){
	webDirectory="$(webRoot)"
	downloadDirectory="$(downloadDir)"
	# delete intermediate conversion directories
	# remove new and random indexes
	rm -rv "$webDirectory/new/git_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/git_*.index'"
	rm -rv "$webDirectory/random/git_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/git_*.index'"
	# remove git directory and indexes
	rm -rv $webDirectory/repos/*
	rm -rv $webDirectory/new/repos.index
	rm -rv $webDirectory/random/repos.index
	rm -rv $webDirectory/sums/git2web_*.cfg || echo "No file sums found..."
	# remove sql data
	sqlite3 $webDirectory/data.db "drop table repos;"
	# remove widgets cached
	rm -v $webDirectory/web_cache/widget_random_repos.index
	rm -v $webDirectory/web_cache/widget_new_repos.index
}
################################################################################
main(){
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		lockProc "git2web"
		checkModStatus "git2web"
		webUpdate "$@"
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		lockProc "git2web"
		checkModStatus "git2web"
		update "$@"
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "git2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "git2web"
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/git2web.txt"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "git2web Version: "
		cat /usr/share/2web/version_git2web.cfg
	else
		lockProc "git2web"
		checkModStatus "git2web"
		update "$@"
		webUpdate "$@"
		#main --help $@
		# on default execution show the server links at the bottom of output
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/repos/"
		drawLine
		echo "http://$(hostname).local:80/settings/repos.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
