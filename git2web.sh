#! /bin/bash
################################################################################
# git2web generates websites from git repos
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
			echo "/var/cache/2web/generated/repos/"
		} >> "/etc/2web/repos/generated.cfg"
		createDir "/var/cache/2web/generated/repos/"
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
			echo "/var/cache/2web/downloads/repos/"
		} >> "/etc/2web/repos/download.cfg"
		createDir "/var/cache/2web/downloads/repos/"
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
			echo "/var/cache/2web/downloads/repos/"
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
function buildDiffGraph(){
	timeFrame=$1
	timeLength=$2
	outputFilename=$3
	repoName=$4
	# - build a svg graph by building a single bar for each month for the past 30 months
	# - each commit on a day should make the bar 1px higher
	textGap=100
	barWidth=20
	#graphHeight=$(( 50 + $barWidth ))
	graphHeight=$(( $textGap + $barWidth ))
	graphData=""
	graphWidth=$(($timeLength * $barWidth ))
	emptyGraph="yes"
	for index in $( seq $timeLength );do
		# get commits within a time frame
		commits=$(git log --oneline --before "$(( $index - 1 )) $timeFrame ago" --after "$index $timeFrame ago" | cut -d' ' -f1)
		addedLines=0
		removedLines=0
		# read each of those commit diffs to build the added and removed lines numbers
		for commitName in $commits;do
			# get the numbers
			#tempAddedLines=$(git diff -U0 "$commitName" | grep "^+" | wc -l)
			#tempRemovedLines=$(git diff -U0 "$commitName" | grep "^-" | wc -l)

			tempAddedLines=$(git diff "$commitName" --stat | grep "insertions" | cut -d',' -f2 | cut -d' ' -f2)
			tempRemovedLines=$(git diff "$commitName" --stat | grep "deletions" | cut -d',' -f3 | cut -d' ' -f2)

			# add the commits diff values to the values used to generate the graph
			addedLines=$(( addedLines + tempAddedLines ))
			removedLines=$(( removedLines + tempRemovedLines ))
		done

		# set modifier for removed and added lines
		modifier=10
		orignalRemovedLines=$(( removedLines ))
		orignalAddedLines=$(( addedLines ))

		totalLines=$(( addedLines + removedLines))

		if [ $totalLines -gt 0 ];then
			# mark the graph as not empty if any commits are found in the graph
			emptyGraph="no"
		fi

		# adjust the height based on the modifier
		removedLines=$(( removedLines / modifier ))
		addedLines=$(( addedLines / modifier ))

		#if [ $(( ( ( removedLines + addedLines ) ) + barWidth + textGap )) -gt $graphHeight ];then
		#	graphHeight=$(( ( ( removedLines + addedLines ) ) + barWidth + textGap ))
		#fi

		#if [ $(( ( ( removedLines ) * barWidth ) + barWidth + textGap )) -gt $graphHeight ];then
		#	graphHeight=$(( ( ( removedLines ) * barWidth ) + barWidth + textGap ))
		#fi
		#if [ $(( ( ( addedLines ) * barWidth ) + barWidth + textGap )) -gt $graphHeight ];then
		#	graphHeight=$(( ( ( addedLines ) * barWidth ) + barWidth + textGap ))
		#fi

		if [ $(( ( ( removedLines ) ) + textGap )) -gt $graphHeight ];then
			graphHeight=$(( ( ( removedLines ) ) + textGap ))
		fi
		if [ $(( ( ( addedLines ) ) + textGap )) -gt $graphHeight ];then
			graphHeight=$(( ( ( addedLines ) ) + textGap ))
		fi

		#ALERT "Removed Lines: $removedLines"
		#ALERT "Added Lines: $addedLines"
		graphX=$(( ( $index * $barWidth ) - $barWidth ))
		# draw the base bar
		graphData="$graphData<rect x=\"$(( ( graphX + ( ( barWidth / 4 ) * 1 ) ) ))\" y=\"$textGap\" width=\"$(( barWidth / 4 ))\" height=\"$(( addedLines ))\" style=\"fill:green;stroke:gray;stroke-width:1\" />"
		#graphData="$graphData<rect x=\"$(( graphX + ( ( barWidth / 4 ) * 1 ) ))\" y=\"$textGap\" width=\"$(( barWidth / 4 ))\" height=\"$(( removedLines + addedLines ))\" style=\"fill:gray;stroke:gray;stroke-width:1\" />"
		graphData="$graphData<rect x=\"$(( ( graphX + ( ( barWidth / 4 ) * 3 ) ) ))\" y=\"$textGap\" width=\"$(( barWidth / 4 ))\" height=\"$(( removedLines ))\" style=\"fill:red;stroke:gray;stroke-width:1\" />"

		# draw text number of changed lines
		#graphData="$graphData<text x=\"$(( graphX + barWidth ))\" y=\"$textGap\" font-size=\"$barWidth\" transform=\"rotate(-90,$(( graphX + barWidth )),$textGap)\" style=\"fill:black;stroke:white;\" >$(( addedLines + removedLines ))</text>"
		graphData="$graphData<text x=\"$(( graphX + barWidth ))\" y=\"$textGap\" font-size=\"$barWidth\" transform=\"rotate(-90,$(( graphX + barWidth )),$textGap)\" style=\"fill:black;stroke:white;\" >$(( orignalAddedLines + orignalRemovedLines ))</text>"
	done
	{
		echo "<svg preserveAspectRatio=\"xMidYMid meet\" viewBox=\"0 0 $graphWidth $graphHeight\" >"
		#echo "<svg height=\"$graphHeight\" width=\"$graphWidth\">"
		#echo "<svg height=\"auto\" width=\"auto\">"
		#echo "<defs>"
		#echo '<filter id="dropShadow" x="0" y="0" width="200%" height="200%">'
		#echo '<feOffset result="offOut" in="SourceAlpha" dx="0" dy="0" />'
		#echo '<feGaussianBlur result="blurOut" in="offOut" stdDeviation="10" />'
		#echo '<feBlend in="SourceGraphic" in2="blurOut" mode="normal" />'
		#echo "</defs>"
		echo "$graphData"
		echo "</svg>"
	} > "$webDirectory/repos/$repoName/$outputFilename.svg"

	convert -flip -flop -trim -background none -quality 100 "$webDirectory/repos/$repoName/$outputFilename.svg" "$webDirectory/repos/$repoName/$outputFilename.png"
	#convert -flip -flop -trim -background none -quality 100 "$webDirectory/repos/$repoName/$outputFilename.svg" -filter box -thumbnail 100x50 -unsharp 1x1 "$webDirectory/repos/$repoName/$outputFilename-thumb.png"
	convert -flip -flop -trim -background none -quality 100 "$webDirectory/repos/$repoName/$outputFilename.svg" -filter box -thumbnail 200x100 -unsharp 1x1 "$webDirectory/repos/$repoName/$outputFilename-thumb.png"

	# empty graph should be removed, when kept they look bad in the web interface and make pages without any info
	if echo "$emptyGraph" | grep -q "yes";then
		rm -v "$webDirectory/repos/$repoName/$outputFilename.svg"
		rm -v "$webDirectory/repos/$repoName/$outputFilename.png"
		rm -v "$webDirectory/repos/$repoName/$outputFilename-thumb.png"
	fi
}
################################################################################
function buildCommitGraph(){
	timeFrame=$1
	timeLength=$2
	outputFilename=$3
	repoName=$4
	# - build a svg graph by building a single bar for each month for the past 30 months
	# - each commit on a day should make the bar 1px higher
	barWidth=15
	#graphHeight=$(( 50 + $barWidth ))
	graphHeight=$(( 50 + $barWidth ))
	graphData=""
	graphWidth=$(($timeLength * $barWidth ))
	emptyGraph="yes"
	for index in $( seq $timeLength );do
		# check the number of commits for each day
		commits=$(git log --oneline --before "$(( $index - 1 )) $timeFrame ago" --after "$index $timeFrame ago" | wc -l)
		commits=$(( $commits ))
		if [ $commits -gt 0 ];then
			# mark the graph as not empty if any commits are found in the graph
			emptyGraph="no"
		fi
		if [ $(( ( $commits * $barWidth ) + $barWidth + 50 )) -gt $graphHeight ];then
			graphHeight=$(( ( $commits * $barWidth ) + $barWidth + 50 ))
		fi
		graphX=$(( ( $index * $barWidth ) - $barWidth ))
		# draw the base bar
		#graphData="$graphData<rect x=\"$graphX\" y=\"50\" width=\"$barWidth\" height=\"$(( $commits * $barWidth ))\" style=\"fill:white;stroke:gray;stroke-width:1\" filter=\"url(#dropShadow)\"/>"
		graphData="$graphData<rect x=\"$graphX\" y=\"50\" width=\"$barWidth\" height=\"$(( $commits * $barWidth ))\" style=\"fill:white;stroke:gray;stroke-width:1\" />"
		# draw text number of commits above bar
		#graphData="$graphData<text x=\"$(( $graphX + $barWidth ))\" y=\"50\" font-size=\"$barWidth\" transform=\"rotate(-90,$(( $graphX + $barWidth )),50)\" style=\"fill:black;stroke:white;\" filter=\"url(#dropShadow)\">$commits</text>"
		graphData="$graphData<text x=\"$(( $graphX + $barWidth ))\" y=\"50\" font-size=\"$barWidth\" transform=\"rotate(-90,$(( $graphX + $barWidth )),50)\" style=\"fill:black;stroke:white;\" >$commits</text>"
	done
	{
		echo "<svg height=\"$graphHeight\" width=\"$graphWidth\" viewbox=\"0 0 $graphWidth $graphHeight\">"
		#echo "<svg height=\"$graphHeight\" width=\"$graphWidth\">"
		#echo "<defs>"
		#echo '<filter id="dropShadow" x="0" y="0" width="200%" height="200%">'
		#echo '<feOffset result="offOut" in="SourceAlpha" dx="0" dy="0" />'
		#echo '<feGaussianBlur result="blurOut" in="offOut" stdDeviation="10" />'
		#echo '<feBlend in="SourceGraphic" in2="blurOut" mode="normal" />'
		#echo "</defs>"
		echo "$graphData"
		echo "</svg>"
	} > "$webDirectory/repos/$repoName/$outputFilename.svg"

	convert -flip -flop -trim -background none "$webDirectory/repos/$repoName/$outputFilename.svg" "$webDirectory/repos/$repoName/$outputFilename.png"
	convert -flip -flop -trim -background none "$webDirectory/repos/$repoName/$outputFilename.svg" -thumbnail 200x100 -unsharp 1x1 "$webDirectory/repos/$repoName/$outputFilename-thumb.png"

	# empty graph should be removed, when kept they look bad in the web interface and make pages without any info
	if echo "$emptyGraph" | grep -q "yes";then
		rm -v "$webDirectory/repos/$repoName/$outputFilename.svg"
		rm -v "$webDirectory/repos/$repoName/$outputFilename.png"
		rm -v "$webDirectory/repos/$repoName/$outputFilename-thumb.png"
	fi
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
	echo "$reposources" | while read repoSource;do
		# generate a sum for the source
		repoSum=$(echo "$repoSource" | sha512sum | cut -d' ' -f1)
		# create the repo directory, limit new repo pulls to once per day
		if cacheCheck "$webDirectory/sums/git2web_download_$repoSum.cfg" "1";then
			# clone and update remote git repositories on the server
			git -C "$downloadDirectory/" clone "$repoSource"
			# update a existing repo
			touch "$webDirectory/sums/git2web_download_$repoSum.cfg"
		fi
	done
	# update any existing repos by pulling local paths to update repo
	find "$webDirectory/repos/" -maxdepth 1 -mindepth 1 -type d | while read repoSource;do
		repoSum=$(echo "$repoSource" | sha512sum | cut -d' ' -f1)
		# if this is a git repository, update once per day
		if cacheCheck "$webDirectory/sums/git2web_pull_$repoSum.cfg" "1";then
			if test -d "$repoSource/source/.git/";then
				ALERT "Found Repo at $repoSource"
				# launch as www-data so git will not throw security errors
				su www-data git -C "$repoSource/source/" pull
			fi
			touch "$webDirectory/sums/git2web_pull_$repoSum.cfg"
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
		tempList=$(cat "$webDirectory/new/repos.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/repos.index"
	fi
}
################################################################################
function generateZip(){
	webDirectory=$1
	repoName=$2
	zipFilePath="$webDirectory/repos/$repoName/source.zip"
	# generate a zip of the source
	cd "$webDirectory/repos/$repoName/source/"
	# compress into a zip file
	zip -rqT -9 "$zipFilePath" "."
	# link the zip file created into the web directory
	linkFile "$zipFilePath" "$webDirectory/kodi/repos/$repoName.zip"
}
################################################################################
function processRepo(){
	repoSource=$1
	webDirectory=$2
	# create sum and name from repo source
	repoSum=$(echo "$repoSource" | md5sum | cut -d' ' -f1)
	#
	repoName=$(echo "$repoSource" | rev | cut -d'/' -f1 | rev)
	#
	if [ "$repoName" == "" ];then
		repoName=$(echo "$repoSource" | rev | cut -d'/' -f2 | rev)
	fi
	INFO "$repoName : Checking repo source sum"
	cd "$repoSource"
	if git -P log --oneline -1;then
		ALERT "This is a valid repo '$repoSource'"
	else
		ALERT "Invalid repo '$repoSource'"
		return
	fi

	if test -d "$webDirectory/repos/$repoName/source/.git/";then
		INFO "$repoName : Pull updates to git repo..."
		# pull updates to existing repo
		git -C "$webDirectory/repos/$repoName/source/" pull
	else
		INFO "$repoName : Creating a clone of the repo..."
		# add as a safe directory for service to be able to update in above git pull
		git config --global --add safe.directory "$webDirectory/repos/$repoName/source/"
		# clone the repo into the web directory
		git clone "$repoSource" "$webDirectory/repos/$repoName/source/"
	fi

	# get the old status sum
	if test -f "$webDirectory/repos/$repoName/status.cfg";then
		oldRepoStatusSum=$(cat "$webDirectory/repos/$repoName/status.cfg")
	else
		oldRepoStatusSum="NOSUM"
	fi

	# get the repo status sum based on the last commit to the local repo
	repoStatusSum=$(git -P log --oneline -1 | md5sum | cut -d' ' -f1)
	INFO "$repoName : Checking git repo source data sum: $repoName"

	# check if the data sum of the source has changed
	if [ "$repoStatusSum" == "$oldRepoStatusSum" ];then
		ALERT "The Repo $repoName has not changed and will not be updated..."
	else
		ALERT "The Repo $repoName has changed and will be updated..."
		# set ownership to root of the source code directory, in order to avoid git security errors in metadata generation
		chown -R root:www-data "$webDirectory/repos/$repoName/source/"

		# move into the cloned repo, so all work is done on the cloned repo
		cd "$webDirectory/repos/$repoName/source/"

		if echo "$@" | grep -q -e "--parallel";then
			totalCPUS=$(cpuCount)
		else
			totalCPUS=1
		fi

		# create data directories inside the web directory
		createDir "$webDirectory/repos/$repoName/"
		createDir "$webDirectory/repos/$repoName/lint/"
		createDir "$webDirectory/repos/$repoName/lint_time/"
		createDir "$webDirectory/repos/$repoName/diff/"
		createDir "$webDirectory/repos/$repoName/log/"
		createDir "$webDirectory/repos/$repoName/date/"
		createDir "$webDirectory/repos/$repoName/author/"
		createDir "$webDirectory/repos/$repoName/email/"
		createDir "$webDirectory/repos/$repoName/msg/"
		# link to /kodi/
		linkFile "$webDirectory/repos/$repoName/source/" "$webDirectory/kodi/$repoName/"
		echo "$repoSource" > "$webDirectory/repos/$repoName/source.index"
		echo "$repoName" > "$webDirectory/repos/$repoName/title.index"
		# link the repo page
		linkFile "/usr/share/2web/templates/repo.php" "$webDirectory/repos/${repoName}/index.php"
		# generate the website content
		if echo "$@" | grep -q -e "--no-inspector";then
			INFO "$repoName : Skipping inspector data processing "
		else
			INFO "$repoName : Building inspector data"
			#gitinspector --format=htmlembedded -f "**" -T true -H true -w false -m true --grading=true "$repoSource" |\
			# launch gitinspector with a timeout of 2 hours,
			# - some repos require stronger hardware to process the blame tree
			timeout 7200 gitinspector --format=text -f "**" -T true -H true -w false -m true --grading=true "$repoSource" |\
			grep --invert-match --ignore-case "<html" |\
			grep --invert-match --ignore-case "</html" |\
			grep --invert-match --ignore-case "<head" |\
			grep --invert-match --ignore-case "</head" |\
			grep --invert-match --ignore-case "<body" |\
			grep --invert-match --ignore-case "</body" |\
			grep --invert-match --ignore-case "<meta" |\
			grep --invert-match --ignore-case "<title" |\
			grep --invert-match --ignore-case "<?xml" \
			> "$webDirectory/repos/$repoName/inspector.html" &
			waitQueue 0.5 "$totalCPUS"
		fi

		INFO "$repoName : Generating zip file"
		generateZip "$webDirectory" "$repoName" &
		waitQueue 0.5 "$totalCPUS"

		INFO "$repoName : Get latest commit time"
		# get the latest commit time
		git show --no-patch --no-notes --pretty='%cd' > "$webDirectory/repos/$repoName/origin.index" &
		waitQueue 0.5 "$totalCPUS"
		INFO "$repoName : Get the origin"
		# get the origin
		git remote show origin > "$webDirectory/repos/$repoName/origin.index" &
		waitQueue 0.5 "$totalCPUS"

		INFO "$repoName : Get the list of all commits"
		commitAddresses=$(git log --oneline | cut -d' ' -f1)

		echo "$commitAddresses" > "$webDirectory/repos/$repoName/commits.index"

		# count commits for output
		totalCommits=$(echo "$commitAddresses" | wc -l)
		commitCount=0

		echo "$commitAddresses" | while read commitAddress;do
			commitCount=$(( commitCount + 1 ))
			INFO "$repoName : Building commit page $commitCount/$totalCommits for $commitAddress "
			#commitAddress=$(echo "$commitAddress" | cut -d' ' -f1)
			if echo "$@" | grep -q -e "--parallel";then
				timeout 120 git show "$commitAddress" --stat | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/log/$commitAddress.index" &
				waitQueue 0.5 "$totalCPUS"
				timeout 120 git diff "$commitAddress" | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/diff/$commitAddress.index" &
				waitQueue 0.5 "$totalCPUS"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%cd' > "$webDirectory/repos/$repoName/date/$commitAddress.index" &
				waitQueue 0.5 "$totalCPUS"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%an' > "$webDirectory/repos/$repoName/author/$commitAddress.index" &
				waitQueue 0.5 "$totalCPUS"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%ae' > "$webDirectory/repos/$repoName/email/$commitAddress.index" &
				waitQueue 0.5 "$totalCPUS"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%s' > "$webDirectory/repos/$repoName/msg/$commitAddress.index" &
				waitQueue 0.5 "$totalCPUS"
			else
				timeout 120 git show "$commitAddress" --stat | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/log/$commitAddress.index"
				timeout 120 git diff "$commitAddress" | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/diff/$commitAddress.index"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%cd' > "$webDirectory/repos/$repoName/date/$commitAddress.index"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%an' > "$webDirectory/repos/$repoName/author/$commitAddress.index"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%ae' > "$webDirectory/repos/$repoName/email/$commitAddress.index"
				timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%s' > "$webDirectory/repos/$repoName/msg/$commitAddress.index"
			fi
		done
		INFO "$repoName : Building README.md"
		# generate html from README.md if found in repo
		if test -f "$repoSource/README.md";then
			if test -f /usr/bin/pandoc;then
				pandoc "$repoSource/README.md" -t html -o "$webDirectory/repos/$repoName/readme.index"
			elif test -f /usr/bin/markdown;then
				markdown "$repoSource/README.md" > "$webDirectory/repos/$repoName/readme.index"
			fi
		fi
		INFO "$repoName : Rendering Graphs"
		if echo "$@" | grep -q -e "--parallel";then
			buildCommitGraph "days" 365 "graph" "$repoName" &
			waitQueue 0.5 "$totalCPUS"

			buildCommitGraph "days" 365 "graph_commit_365_day" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildDiffGraph "days" 365 "graph_diff_365_day" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildCommitGraph "days" 365 "graph_commit_365" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildDiffGraph "days" 365 "graph_diff_365" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildCommitGraph "days" 90 "graph_commit_day" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildDiffGraph "days" 90 "graph_diff_day" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildCommitGraph "months" 90 "graph_commit_month" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildDiffGraph "months" 90 "graph_diff_month" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildCommitGraph "weeks" 90 "graph_commit_week" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildDiffGraph "weeks" 90 "graph_diff_week" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildCommitGraph "years" 90 "graph_commit_year" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
			buildDiffGraph "years" 90 "graph_diff_year" "$repoName" &
			waitQueue 0.5 "$totalCPUS"
		else
			buildCommitGraph "days" 365 "graph" "$repoName"
			buildCommitGraph "days" 365 "graph_commit_365_day" "$repoName"
			buildDiffGraph "days" 365 "graph_diff_365_day" "$repoName"
			buildCommitGraph "days" 365 "graph_commit_365" "$repoName"
			buildDiffGraph "days" 365 "graph_diff_365" "$repoName"
			buildCommitGraph "days" 90 "graph_commit_day" "$repoName"
			buildDiffGraph "days" 90 "graph_diff_day" "$repoName"
			buildCommitGraph "months" 90 "graph_commit_month" "$repoName"
			buildDiffGraph "months" 90 "graph_diff_month" "$repoName"
			buildCommitGraph "weeks" 90 "graph_commit_week" "$repoName"
			buildDiffGraph "weeks" 90 "graph_diff_week" "$repoName"
			buildCommitGraph "years" 90 "graph_commit_year" "$repoName"
			buildDiffGraph "years" 90 "graph_diff_year" "$repoName"
		fi

		INFO "$repoName : Building lint data for shellscripts"
		# run lint on all the existing files that support it
		#find "$repoSource" -type f -name "*.sh" | sort | while read sourceFilePath;do
		if test -f "/usr/bin/shellcheck";then
			find "." -type f -name "*.sh" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="format:%ci" "$sourceFilePath" > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					if echo "$@" | grep -q -e "--parallel";then
						shellcheck "$sourceFilePath" | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
						waitQueue 0.5 "$totalCPUS"
					else
						shellcheck "$sourceFilePath" | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
					fi
				fi
			done
		else
			{
				echo "################################################################################"
				echo "You need to install shellcheck to get lint output for this filetype."
				echo "################################################################################"
				echo "You can run"
				echo ""
				echo "	apt-get install shellcheck"
				echo ""
				echo "to install the package. "
				echo "################################################################################"
			} > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
		fi

		INFO "$repoName : Building lint data for hypertext"
		#find "$repoSource" -type f -name "*.php" -o -name "*.html" | sort | while read sourceFilePath;do
		if test -f "/usr/bin/weblint";then
			find "." -type f -name "*.html" -o -name "*.htm" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="format:%ci" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					if echo "$@" | grep -q -e "--parallel";then
						weblint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
						waitQueue 0.5 "$totalCPUS"
					else
						weblint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
					fi
				fi
			done
		else
			{
				echo "################################################################################"
				echo "You need to install weblint to get lint output for this filetype."
				echo "################################################################################"
				echo "You can run"
				echo ""
				echo "	apt-get install weblint"
				echo ""
				echo "to install the package. "
				echo "################################################################################"
			} > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
		fi

		#if test -f "/usr/bin/cpplint";then
		#	find "." -type f -name "*.php" -o -name "*.html" | sort | while read sourceFilePath;do
		#		tempSourceSum=$(popPath "$sourceFilePath")
		#		git log -1 --pretty="format:%ci" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
		#		if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
		#			cpplint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
		#		fi
		#	done
		#fi

		INFO "$repoName : Building lint data for Javascript"
		# check for javascript files to run lint on
		if test -f "/usr/local/bin/jslint";then
			find "." -type f -name "*.js" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="format:%ci" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					#eslint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
					if echo "$@" | grep -q -e "--parallel";then
						/usr/local/bin/jslint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
						waitQueue 0.5 "$totalCPUS"
					else
						/usr/local/bin/jslint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
					fi
				fi
			done
		else
			{
				echo "################################################################################"
				echo "You need to install jslint to get lint output for this filetype."
				echo "################################################################################"
				echo "You can run"
				echo ""
				echo "	git2web upgrade"
				echo ""
				echo "or"
				echo ""
				echo "	pip3 install jslint"
				echo ""
				echo "to install the package. "
				echo "################################################################################"
			} > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
		fi

		INFO "$repoName : Building lint data for Python"
		if test -f "/usr/bin/pylint";then
			find "." -type f -name "*.py" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="format:%ci" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					if echo "$@" | grep -q -e "--parallel";then
						pylint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
						waitQueue 0.5 "$totalCPUS"
					else
						pylint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
					fi
				fi
			done
		else
			{
				echo "################################################################################"
				echo "You need to install pylint to get lint output for this filetype."
				echo "################################################################################"
				echo "You can run"
				echo ""
				echo "	apt-get install pylint"
				echo ""
				echo "to install the package. "
				echo "################################################################################"
			} > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
		fi

		INFO "$repoName : Building lint data for PHP"
		if test -f "/usr/bin/php";then
			find "." -type f -name "*.php" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="format:%ci" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					{
						php --syntax-check "$sourceFilePath" | txt2html --extract --escape_HTML_chars
						if test -f "/usr/bin/weblint";then
							if echo "$@" | grep -q -e "--parallel";then
								weblint "$sourceFilePath" | txt2html --extract --escape_HTML_chars &
								waitQueue 0.5 "$totalCPUS"
							else
								weblint "$sourceFilePath" | txt2html --extract --escape_HTML_chars
							fi
						fi
					} > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
				fi
			done
		fi
		INFO "$repoName : Building QR code"
		# build a qr code for the icon link
		qrencode -m 1 -l H -o "/var/cache/2web/web/repos/$repoName/thumb.png" "http://$(hostname).local/repos/$repoName/" &
		waitQueue 0.5 "$totalCPUS"

		if echo "$@" | grep -q -e "--no-video";then
			INFO "$repoName : Skip video rendering..."
		else
			INFO "$repoName : Rendering gource video..."
			gource --output-custom-log "$webDirectory/repos/$repoName/GOURCE_LOG.gource" "$webDirectory/repos/$repoName/source/"

			# Add repo name to the repo log
			sed -i -r "s#(.+)\|#\1|/$repoName#" "$webDirectory/repos/$repoName/GOURCE_LOG.gource"

			# build history video in 720p from the generated log
			#xvfb-run gource --key --max-files 0 -s 1 -c 4 -1280x720 -o - |\
			xvfb-run gource --key --max-files 0 -s 1 -c 4 -1280x720 -o - "$webDirectory/repos/$repoName/GOURCE_LOG.gource" |\
			ffmpeg -y -r 60 -f image2pipe -i - -threads $totalCPUS -bf 0 "$webDirectory/repos/$repoName/repoHistory.webm"
		fi
		# block until video rendering is done, the video render will use all of the cpu cores anyway
		# NOTE: gource currently still refuses to render no headless servers so it only works if you run git2web on a desktop with graphics support
		# build thumbnail for repo from video or from graphs if video does not render
		if test -f "$webDirectory/repos/$repoName/repoHistory.webm";then
			# build the thumbnail from the end of the video
			ffmpegthumbnailer -i "$webDirectory/repos/$repoName/repoHistory.webm" -o "$webDirectory/repos/$repoName/repoHistory.png" -s 0 -t "100%"
		else
			# if no video was rendered use generated graphs for thumbnail
			linkFile "$webDirectory/repos/$repoName/graph_commit_month.png" "$webDirectory/repos/$repoName/repoHistory.png"
		fi
		# build a thumbnail of the video thumbnail
		convert -trim -background none "$webDirectory/repos/$repoName/repoHistory.png" -thumbnail 200x100 -unsharp 1x1 "$webDirectory/repos/$repoName/repoHistory-thumb.png"

		INFO "$repoName : Waiting for all threads to finish"
		blockQueue 1
		INFO "$repoName : Adding to indexes"
		#	build the index file for this entry if one does not exist
		{
			echo "<a href='/repos/$repoName/' class='showPageEpisode' >"
			echo "<img loading='lazy' src='/repos/$repoName/repoHistory.png' />"
			echo "<div>$repoName</div>"
			echo "</a>"
		} > "$webDirectory/repos/$repoName/repos.index"

		# add to the system indexes
		SQLaddToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/data.db" "repos"
		SQLaddToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/data.db" "all"

		#
		SQLaddToIndex "$webDirectory/repos/$repoName/repoHistory.png" "$webDirectory/data.db" "all_fanart"
		SQLaddToIndex "$webDirectory/repos/$repoName/repoHistory.png" "$webDirectory/data.db" "repos_fanart"

		#
		addToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/repos/repos.index"
		addToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/new/repos.index"
		addToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/new/all.index"

		# update the repo status sum
		echo -n "$repoStatusSum" > "$webDirectory/repos/$repoName/status.cfg"
	fi
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

	totalrepos=0

	ALERT "Scanning libary config '$downloadDirectory'"
	IFSBACKUP=$IFS
	IFS=$'\n'
	# scan for subdirectories containing git repos
	for sourcePath in $downloadDirectory;do
		repoPaths=$(find "$sourcePath" -type d | shuf)
		#INFO "Discovered Repo Paths '$repoPaths', searching for .git/"
		#find "$sourcePath" -type d | sort | while read repoSource;do
		for repoSource in $repoPaths;do
			#INFO "Repo source Found '$repoSource', searching for .git/"
			if test -d "$repoSource/.git/";then
				INFO "Repo found processing $repoSource"
				processRepo "$repoSource" "$webDirectory" $@
				# increment the total repo counters
				totalRepos=$(( $totalRepos + 1 ))
			else
				INFO "No repo found!"
			fi
		done
	done
	IFS=$IFSBACKUP

	echo "$totalrepos" > "$webDirectory/repos/totalrepos.cfg"

	if echo "$@" | grep -q -e "--no-video";then
		INFO "Skip combined video rendering..."
	else
		INFO "Writing combined Gource video..."
		if checkFileDataSum "$webDirectory" "$webDirectory/repos/repos.index";then
			# combine the gource logs to make a combined repo video
			{
				#cat "$webDirectory"/repos/*/GOURCE_LOG.gource | sed -r "s#(.+)\|#\1|/Repos#" | sort -n
				cat "$webDirectory"/repos/*/GOURCE_LOG.gource | sort -n
			} > "$webDirectory/repos/combined_gource.gource"
			# render the combined video
			xvfb-run gource --key --max-files 0 -s 1 -c 4 -1280x720 -o - "$webDirectory/repos/combined_gource.gource" | \
			ffmpeg -y -r 60 -f image2pipe -i - -threads $totalCPUS -bf 0 "$webDirectory/repos/allHistory.webm"

			setFileDataSum "$webDirectory" "$webDirectory/repos/repos.index"
		fi
	fi

	if test -f "$webDirectory/repos/allHistory.webm";then
		# build the thumbnail from the end of the video
		ffmpegthumbnailer -i "$webDirectory/repos/allHistory.webm" -o "$webDirectory/repos/allHistory.png" -s 0 -t "100%"
	fi

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
	rm -rv $webDirectory/repos/
	rm -rv $webDirectory/kodi/repos/
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
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		checkModStatus "git2web"
		# upgrade the jslint package
		pip3 install --upgrade jslint
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
