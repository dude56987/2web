#! /bin/bash
################################################################################
# git2web generates websites from git repos
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
	# Load the download directory for git2web
	#
	# - loaded from /etc/2web/repos/download.cfg
	#
	# RETURN STDOUT

	# write path to console
	echo "/var/cache/2web/downloads/repos/"
}
################################################################################
function buildPhpDocstrings(){
	# Build the docstrings for PHP functions like this docstring you are reading.
	#
	# - docstrings in PHP are successive comments after the function declaration
	#
	# RETURN STDOUT
	fileName=$1
	outputFileName=$2
	# get all the function names
	#functionNames="$(cat "$fileName" | grep --ignore-case "^function" | cut -d' ' -f2 | cut -d'(' -f1)"
	# add function names with proceeding spaces
	#functionNames="$functionNames $(cat "$fileName" | grep --ignore-case "^.*function" | cut -d' ' -f2 | cut -d'(' -f1)"
	functionNames="$(cat "$fileName" | grep --ignore-case "^.*function " | cut -d' ' -f2 | cut -d'(' -f1)"
	#functionCount="$(cat "$fileName" | grep -P --ignore-case -c "^.*function\(.*?\)\{")"
	functionCount="$(cat "$fileName" | grep -P --ignore-case -c "^.*function ")"
	# only write the file if there is at least one function
	if [ $functionCount -gt 0 ];then
		{
			#
			cleanName=$(echo "$fileName" | sed "s/\.\///g" | sed "s/\.php//g")
			if echo "$cleanName" | grep "/";then
				cleanName=$(echo "$cleanName" | rev | cut -d'/' -f1 | rev)
			fi
			# for each of the found function names get the block of comments below it
			echo "NAME"
			echo "    $cleanName"
			echo
			echo "DESCRIPTION"
			# draw the file description header
			cat "$fileName" | while read -r line;do
				if echo "$line" | grep --ignore-case -q "<?PHP";then
					# ignore the starting line
					echo -n
				elif echo "$line" | grep -q "^.*#.*$";then
					# print the comment line
					echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
				elif echo "$line" | grep -q "^#.*$";then
					# print the comment line
					echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
				else
					# break on the first line not containing a comment
					break
				fi
			done
			# write the functions
			echo
			echo "FUNCTIONS"
			IFSBACKUP=$IFS
			IFS=$'\n'
			for functionName in $functionNames;do
				# for each function name read the function
				functionData=$(cat "$fileName" | sed -z "s/\n/;/g")
				functionData=$(echo "$functionData" | grep -P --only-matching "$functionName\(.*\)\{.*?\}")
				functionData=$(echo "$functionData" | sed "s/;/\n/g")
				# extract the function and parse each line until you hit a line that is not a comment
				echo "$functionData" | while read -r line;do
					#echo $line
					# read until you hit a non comment line
					if echo "$line" | grep -q "$functionName(){";then
						# this is the function declaration draw the header
						echo
						echo -e "    $functionName()"
					elif echo "$line" | grep -P -q "$functionName\(.*?\)\{";then
						# this is the function declaration draw the header
						echo
						echo "    $functionName()"
					elif echo "$line" | grep -q "^.*#.*$";then
						# print the comment line
						# add a extra tab and remove the # at the start of the comment line in the comment block
						echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
					elif echo "$line" | grep -q "^#.*$";then
						# print the comment line
						echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
					else
						# break on the first line not containing a comment
						break
					fi
				done
			done
			echo
			echo -e "FILE"
			echo -e "\t$fileName"
			# reset IFS
			IFS=$IFSBACKUP
		} > "$outputFileName"
	fi
}
################################################################################
function buildBashDocstrings(){
	# Build the docstrings for BASH functions like this docstring you are reading.
	#
	# - docstrings in BASH are successive comments after the function declaration
	#
	# RETURN STDOUT
	fileName=$1
	outputFileName=$2
	# get all the function names
	functionNames="$(cat "$fileName" | grep --ignore-case "^function" | cut -d' ' -f2 | cut -d'(' -f1)"
	functionCount="$(cat "$fileName" | grep -c --ignore-case "^function")"
	if [ $functionCount -gt 0 ];then
		{
			cleanName=$(echo "$fileName" | sed "s/\.\///g" | sed "s/\.sh//g")
			if echo "$cleanName" | grep "/";then
				cleanName=$(echo "$cleanName" | rev | cut -d'/' -f1 | rev)
			fi
			# for each of the found function names get the block of comments below it
			echo "NAME"
			echo "    $cleanName"
			echo
			echo "DESCRIPTION"
			# draw the file description header
			cat "$fileName" | while read -r line;do
				if echo "$line" | grep -q "#!";then
					# ignore the starting line
					echo -n
				elif echo "$line" | grep -q "^.*#.*$";then
					# print the comment line
					echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
				elif echo "$line" | grep -q "^#.*$";then
					# print the comment line
					echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
				else
					# break on the first line not containing a comment
					break
				fi
			done
			# write the functions
			echo
			echo "FUNCTIONS"
			IFSBACKUP=$IFS
			IFS=$'\n'
			for functionName in $functionNames;do
				# for each function name read the function
				functionData=$(cat "$fileName" | sed -z "s/\n/;/g")
				functionData=$(echo "$functionData" | grep -P --only-matching "$functionName\(\)\{.*?\}")
				functionData=$(echo "$functionData" | sed "s/;/\n/g")
				# extract the function and parse each line until you hit a line that is not a comment
				echo "$functionData" | while read -r line;do
					#echo $line
					# read until you hit a non comment line
					if echo "$line" | grep -q "$functionName(){";then
						# this is the function declaration draw the header
						echo
						echo "    $functionName()"
					elif echo "$line" | grep -q "^.*#.*$";then
						# print the comment line
						# add a extra tab and remove the # at the start of the comment line in the comment block
						echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
					elif echo "$line" | grep -q "^#.*$";then
						# print the comment line
						echo "$line" | sed "s/#/    /g" | sed "s/\t/    /g"
					else
						# break on the first line not containing a comment
						break
					fi
				done
				#echo "################################################################################"
				echo
			done
			echo
			echo -e "FILE"
			echo -e "\t$fileName"
			# reset IFS
			IFS=$IFSBACKUP
		} > "$outputFileName"
	fi
}
################################################################################
function buildDiffStats(){
	# Build a file change diff graph.
	#
	# The below example would graph diff data for the last 90 days
	# ex. buildDiffGraph "days" 90 "graph_diff_year" "$repoName"
	#
	# $1 = timeFrame : The time increment to use. years, days, months, weeks
	# $2 = timeLength : How many of the time increment to go back and graph
	# $3 = outputFileName : The name of the graph to be created
	# $4 = repoName : the name of the repo to read
	#
	# - The graph is scaled automatically to fit the tallest value.
	#
	# RETURN NULL, FILES
	repoName=$1

	# totals
	totalAddedLines=0
	totalRemovedLines=0
	totalModifiedLines=0
	totalLinesInProject=0

	# get all commit identifier sums
	commits=$(git log --oneline | cut -d' ' -f1)

	# first commit
	firstCommit=$(echo "$commits" | tail -1 )
	# last commit
	lastCommit=$(echo "$commits" | head -1 )

	# get the date of the first and last commit in seconds
	# - store dates in seconds and convert in webpage to x days ago
	projectStartDate="$(git log "$firstCommit" --no-patch --no-notes --pretty="%ct" | head -1)"
	lastProjectUpdate="$(git log "$lastCommit" --no-patch --no-notes --pretty="%ct" | head -1)"

	# get the total number of commits in the repo
	totalCommits=$(echo "$commits" | wc -l)

	# for each of the gathered commits generate stats
	for commitName in $commits;do
		tempAddedLines=$(git diff "$commitName" --stat | grep "insertions" | cut -d',' -f2 | cut -d' ' -f2)
		tempRemovedLines=$(git diff "$commitName" --stat | grep "deletions" | cut -d',' -f3 | cut -d' ' -f2)

		# add to the total added and removed lines
		totalAddedLines=$(( totalAddedLines + tempAddedLines ))
		totalRemovedLines=$(( totalRemovedLines + tempRemovedLines ))

		# do the total lines in the project
		totalLinesInProject=$((totalLinesInProjectLines - tempRemovedLines ))
		totalLinesInProject=$((totalLinesInProjectLines + tempAddedLines))

		# do the total changed lines
		totalModifiedLines=$((totalModifiedLines + tempRemovedLines ))
		totalModifiedLines=$((totalModifiedLines + tempAddedLines))
	done

	# figure out the total number of estimated work hours placed in the project
	# - assume that 1 day of work is per 500 lines of code
	estimatedWorkDays=$(bc <<< "$totalModifiedLines / 500")

	# store the generated stats
	echo "$totalAddedLines" > "$webDirectory/repos/$repoName/stat_added.cfg"
	echo "$totalRemovedLines" > "$webDirectory/repos/$repoName/stat_removed.cfg"
	echo "$totalModifiedLines" > "$webDirectory/repos/$repoName/stat_modified.cfg"
	echo "$totalLinesInProject" > "$webDirectory/repos/$repoName/stat_total.cfg"
	echo "$projectStartDate" > "$webDirectory/repos/$repoName/stat_start.cfg"
	echo "$lastProjectUpdate" > "$webDirectory/repos/$repoName/stat_end.cfg"
	echo "$totalCommits" > "$webDirectory/repos/$repoName/stat_commits.cfg"
	echo "$estimatedWorkDays" > "$webDirectory/repos/$repoName/stat_work.cfg"
}

################################################################################
function buildDiffGraph(){
	# Build a file change diff graph.
	#
	# The below example would graph diff data for the last 90 days
	# ex. buildDiffGraph "days" 90 "graph_diff_year" "$repoName"
	#
	# $1 = timeFrame : The time increment to use. years, days, months, weeks
	# $2 = timeLength : How many of the time increment to go back and graph
	# $3 = outputFileName : The name of the graph to be created
	# $4 = repoName : the name of the repo to read
	#
	# - The graph is scaled automatically to fit the tallest value.
	#
	# RETURN NULL, FILES
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
	graphScale=0
	largestValue=0
	# log the graph rendering update
	INFO "Generating '$outputFilename' graph for '$repoName'"
	addToLog "UPDATE" "Rendering Repo Graph" "Generating '$outputFilename' graph for '$repoName'"
	for index in $( seq $timeLength );do
		# get commits within a time frame
		commits=$(git log --oneline --before "$(( $index - 1 )) $timeFrame ago" --after "$index $timeFrame ago" | cut -d' ' -f1)
		printAddedLines=0
		printRemovedLines=0

		for commitName in $commits;do
			tempAddedLines=$(git diff "$commitName" --stat | grep "insertions" | cut -d',' -f2 | cut -d' ' -f2)
			tempRemovedLines=$(git diff "$commitName" --stat | grep "deletions" | cut -d',' -f3 | cut -d' ' -f2)
			#ALERT "$index | Checking the values for the largest value '$tempAddedLines'+/'$tempRemovedLines'- current=$largestValue"
			# add the commits diff values to the values used to generate the graph
			printAddedLines=$(( printAddedLines + tempAddedLines ))
			printRemovedLines=$(( printRemovedLines + tempRemovedLines ))
		done

		if [ $(( printRemovedLines )) -gt $largestValue ];then
			#ALERT "The removed lines '$printRemovedLines' are larger than the largest value '$largestValue'"
			largestValue=$(( printRemovedLines ))
		fi
		if [ $(( printAddedLines )) -gt $largestValue ];then
			#ALERT "The added lines '$printAddedLines' are larger than the largest value '$largestValue'"
			largestValue=$(( printAddedLines ))
		fi
	done
	#echo "largest value = $largestValue"
	# generate the graph scale for a specific pixel height graph
	graphHeight=600

	if [[ $largestValue -lt $graphHeight ]];then
		# if the greatest value is less than the set height then the scale should be one
		graphScale=1
	else
		#graphScale=$( bc -l <<< "$graphHeight / $largestValue" )
		graphScale=$( bc -l <<< "$largestValue / ($graphHeight - $textGap)" )
		graphScale=$( echo "$graphScale" | sed "s/^\./0./g" )
	fi

	for index in $( seq $timeLength );do
		# get commits within a time frame
		commits=$(git log --oneline --before "$(( $index - 1 )) $timeFrame ago" --after "$index $timeFrame ago" | cut -d' ' -f1)
		addedLines=0
		removedLines=0
		# read each of those commit diffs to build the added and removed lines numbers
		for commitName in $commits;do
			# get the numbers
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
		removedLines=$( bc -l <<< "$removedLines / $graphScale" )
		removedLines=$( echo "$removedLines" | sed "s/^\./0./g" )

		addedLines=$( bc -l <<< "$addedLines / $graphScale" )
		addedLines=$( echo "$addedLines" | sed "s/^\./0./g" )

		graphX=$(( ( $index * $barWidth ) - $barWidth ))
		# draw the base bar
		graphData="$graphData<rect x=\"$(( ( graphX + ( ( barWidth / 4 ) * 1 ) ) ))\" y=\"$textGap\" width=\"$(( barWidth / 4 ))\" height=\"$addedLines\" style=\"fill:green;stroke:gray;stroke-width:1\" />"
		graphData="$graphData<rect x=\"$(( ( graphX + ( ( barWidth / 4 ) * 3 ) ) ))\" y=\"$textGap\" width=\"$(( barWidth / 4 ))\" height=\"$removedLines\" style=\"fill:red;stroke:gray;stroke-width:1\" />"

		# draw text number of changed lines
		graphData="$graphData<text x=\"$(( graphX + barWidth ))\" y=\"$textGap\" font-size=\"$barWidth\" transform=\"rotate(-90,$(( graphX + barWidth )),$textGap)\" style=\"fill:black;stroke:white;\" >$(( orignalAddedLines + orignalRemovedLines ))</text>"
	done
	{
		echo "<svg preserveAspectRatio=\"xMidYMid meet\" viewBox=\"0 0 $graphWidth $graphHeight\" >"
		echo "$graphData"
		echo "</svg>"
	} > "$webDirectory/repos/$repoName/$outputFilename.svg"

	convert -flip -flop -trim -background none -quality 100 "$webDirectory/repos/$repoName/$outputFilename.svg" "$webDirectory/repos/$repoName/$outputFilename.png"
	convert -flip -flop -trim -background none -quality 100 "$webDirectory/repos/$repoName/$outputFilename.svg" -filter box -thumbnail 200x100 -unsharp 1x1 "$webDirectory/repos/$repoName/$outputFilename-thumb.png"

	# empty graph should be removed, when kept they look bad in the web interface and make pages without any info
	if echo "$emptyGraph" | grep -q "yes";then
		rm -v "$webDirectory/repos/$repoName/$outputFilename.svg"
		rm -v "$webDirectory/repos/$repoName/$outputFilename.png"
		rm -v "$webDirectory/repos/$repoName/$outputFilename-thumb.png"
	fi
	#
	addToLog "INFO" "Finished Rendering" "Finished generating '$outputFileName' graph for '$repoName'"
}
################################################################################
function buildCommitGraph(){
	# Build graph of the commits over time.
	#
	# The below example would graph commit hdata for the last 90 days
	# ex. buildCommitGraph "days" 90 "graph_diff_year" "$repoName"
	#
	# $1 = timeFrame : The time increment to use. years, days, months, weeks
	# $2 = timeLength : How many of the time increment to go back and graph
	# $3 = outputFileName : The name of the graph to be created
	# $4 = repoName : the name of the repo to read
	#
	# - The graph is scaled automatically to fit the tallest value.
	#
	# RETURN NULL, FILES
	timeFrame=$1
	timeLength=$2
	outputFilename=$3
	repoName=$4
	# - build a svg graph by building a single bar for each month for the past 30 months
	# - each commit on a day should make the bar 1px higher
	barWidth=15
	textGap=100
	#graphHeight=$(( 50 + $barWidth ))
	graphHeight=$(( $textGap + $barWidth ))
	graphData=""
	graphWidth=$(($timeLength * $barWidth ))
	emptyGraph="yes"
	graphScale=1
	largestValue=0
	commits=0

	# log the graph rendering update
	INFO "Generating '$outputFilename' graph for '$repoName'"
	addToLog "UPDATE" "Rendering Repo Graph" "Generating '$outputFilename' graph for '$repoName'"
	for index in $( seq $timeLength );do
		# get commits within a time frame
		commits=$(git log --oneline --before "$(( $index - 1 )) $timeFrame ago" --after "$index $timeFrame ago" | wc -l)
		commits=$(( $commits * $barWidth ))

		if [ $(( commits )) -gt $largestValue ];then
			largestValue=$(( commits ))
		fi
		if [ $(( commits )) -gt $largestValue ];then
			largestValue=$(( commits ))
		fi
	done
	# generate the graph scale for a specific pixel height graph
	graphHeight=600

	if [[ $largestValue -lt $graphHeight ]];then
		# if the greatest value is less than the set height then the scale should be one
		graphScale=1
	else
		graphScale=$( bc -l <<< "$largestValue / ($graphHeight - $textGap)" )
		graphScale=$( echo "$graphScale" | sed "s/^\./0./g" )
	fi

	commits=0

	for index in $( seq $timeLength );do
		# check the number of commits for each day
		commits=$(git log --oneline --before "$(( $index - 1 )) $timeFrame ago" --after "$index $timeFrame ago" | wc -l)
		orignalCommits=$(( $commits ))
		# scale up the commit graph
		commits=$(( $commits * $barWidth ))
		# scale the commit
		commits=$( bc -l <<< "$commits / $graphScale" )
		commits=$( echo "$commits" | sed "s/^\./0./g" )
		# check for a empty graph
		if [ $orignalCommits -gt 0 ];then
			# mark the graph as not empty if any commits are found in the graph
			emptyGraph="no"
		fi
		graphX=$(( ( $index * $barWidth ) - $barWidth ))
		# draw the base bar
		graphData="$graphData<rect x=\"$graphX\" y=\"$textGap\" width=\"$barWidth\" height=\"$commits\" style=\"fill:white;stroke:gray;stroke-width:1\" />"
		# draw text number of commits above bar
		graphData="$graphData<text x=\"$(( $graphX + $barWidth ))\" y=\"$textGap\" font-size=\"$barWidth\" transform=\"rotate(-90,$(( $graphX + $barWidth )),$textGap)\" style=\"fill:black;stroke:white;\" >$orignalCommits</text>"
	done
	{
		echo "<svg height=\"$graphHeight\" width=\"$graphWidth\" viewbox=\"0 0 $graphWidth $graphHeight\">"
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
	addToLog "INFO" "Finished Rendering" "Finished generating '$outputFileName' graph for '$repoName'"
}
################################################################################
function update(){
	# Pull updates from local and remote repos into git2web server.
	#
	# RETURN NULL, FILES
	addToLog "INFO" "STARTED Update" "$(date)"

	# disable the safe directory functionality
	# - this is for a security flaw that only exists on the 'windows' operating system
	git config --global --add safe.directory '*'

	INFO "Loading up sources..."
	repoSources=$(loadConfigs "/etc/2web/repos/sources.cfg" "/etc/2web/repos/sources.d/" "/etc/2web/config_default/git2web_sources.cfg" | tr -s '\n' | shuf)
	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	downloadDirectory="$(downloadDir)"
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make repos directory
	createDir "$webDirectory/repos/"
	# scan the sources
	ALERT "git Download Sources: $repoSources"
	echo "$repoSources" | while read repoSource;do
		# generate a sum for the source
		repoSum=$(echo "$repoSource" | sha512sum | cut -d' ' -f1)
		# create the repo directory, limit new repo pulls to once per day
		if cacheCheck "$webDirectory/sums/git2web_download_$repoSum.cfg" "1";then
			addToLog "DOWNLOAD" "Clone remote repo" "$repoSource"
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
				addToLog "DOWNLOAD" "Pull updates to repo" "$repoSource"
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
	#
	addToLog "INFO" "Update FINISHED" "$(date)"
}
################################################################################
function generateZip(){
	webDirectory=$1
	repoName=$2
	zipFilePath="$webDirectory/repos/$repoName/$repoName.zip"
	# generate a zip of the source
	cd "$webDirectory/repos/$repoName/source/"
	# compress into a zip file
	zip -rqT -9 "$zipFilePath" "."
	createDir "$webDirectory/kodi/repos/"
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
	# cleanup repo name for sorting compatiblity
	repoName="$(cleanText "$repoName")"
	repoName="$(alterArticles "$repoName")"
	# create the repo location in the web server
	createDir "$webDirectory/repos/$repoName/"
	# create the source storage location
	createDir "$webDirectory/repos/$repoName/source/"
	# disable safe directory checking on repo stored in web server
	git -C "$webDirectory/repos/$repoName/source/" config --add safe.directory '*'
	INFO "$repoName : Checking repo source sum"
	#cd "$repoSource"
	# test that this is a valid git repo by looking for .git directory
	if test -d "$repoSource/.git/";then
		ALERT "This is a valid repo '$repoSource'"
		addToLog "INFO" "Valid Repo" "'$repoSource' contains a valid .git repo"
	else
		ALERT "Invalid repo '$repoSource'"
		addToLog "ERROR" "Invalid Repo" "$repoSource"
		return
	fi
	if test -d "$webDirectory/repos/$repoName/source/.git/";then
		INFO "$repoName : Pull updates to git repo..."
		addToLog "DOWNLOAD" "Pull updates to repo" "$repoName"
		# pull updates to existing repo
		/usr/bin/git -C "$webDirectory/repos/$repoName/source/" pull || addToLog "ERROR" "Git Pull Failed" "$repoName"
	else
		INFO "$repoName : Creating a clone of the repo..."
		addToLog "DOWNLOAD" "Clone Inital repo" "$repoName"
		# add as a safe directory for service to be able to update in above git pull
		git config --global --add safe.directory "$webDirectory/repos/$repoName/source"
		# clone the repo into the web directory
		git clone "$repoSource" "$webDirectory/repos/$repoName/source/" || addToLog "ERROR" "Git Clone Failed" "$repoName"
	fi

	# get the old status sum
	if test -f "$webDirectory/repos/$repoName/status.cfg";then
		oldRepoStatusSum=$(cat "$webDirectory/repos/$repoName/status.cfg")
	else
		oldRepoStatusSum="NOSUM"
	fi

	# get the repo status sum based on the last commit to the local repo
	repoStatusSum=$(git -C "$webDirectory/repos/$repoName/source/" -P log --oneline -1 | md5sum | cut -d' ' -f1)

	INFO "$repoName : Checking git repo source data sum: $repoName"

	# configure how multithreading will be handled
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi

	# update the graph info at least once per day even if the repo is unchanged
	if cacheCheck "$webDirectory/repos/$repoName/graph_diff_year.png" "1";then
		# move into the source directory stored on the server in order to generate
		# graphs for the webserver stored version of the repo
		cd "$webDirectory/repos/$repoName/source/"
		# create the base directory if it does not yet exist
		addToLog "INFO" "Generating Graphs" "Creating all graphs for '$repoName'"
		#
		INFO "$repoName : Rendering Graphs"
		# build the pulse graph
		buildCommitGraph "days" 365 "graph" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		# build the rest of the graphs
		buildCommitGraph "days" 365 "graph_commit_365_day" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildDiffGraph "days" 365 "graph_diff_365_day" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildCommitGraph "days" 365 "graph_commit_365" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildDiffGraph "days" 365 "graph_diff_365" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildCommitGraph "hours" 24 "graph_commit_24_hour" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildDiffGraph "hours" 24 "graph_diff_24_hour" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildCommitGraph "hours" 72 "graph_commit_72_hour" "$repoName" &
		waitQueue 0.5 "$totalCPUS"
		buildDiffGraph "hours" 72 "graph_diff_72_hour" "$repoName" &
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
	fi

	# check if the data sum of the source has changed
	if [ "$repoStatusSum" == "$oldRepoStatusSum" ];then
		ALERT "The Repo $repoName has not changed and will not be updated..."
		addToLog "INFO" "No Repo Changes" "'$repoName' has no changes, nothing will be updated. "
	else
		addToLog "UPDATE" "New Repo Changes" "'$repoName' Commits have Changed, Updating all repo information and generated content."
		ALERT "The Repo $repoName has changed and will be updated..."
		# set ownership to root of the source code directory, in order to avoid git security errors in metadata generation
		chown -R root:www-data "$webDirectory/repos/$repoName/source/"

		# move into the cloned repo, so all work is done on the cloned repo
		cd "$webDirectory/repos/$repoName/source/"

		# create data directories inside the web directory
		createDir "$webDirectory/repos/$repoName/"
		createDir "$webDirectory/repos/$repoName/lint/"
		createDir "$webDirectory/repos/$repoName/doc/"
		createDir "$webDirectory/repos/$repoName/doc_count/"
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
		# build the stats for the repo
		buildDiffStats "$repoName" &
		waitQueue 0.5 "$totalCPUS"

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
			timeout 120 git show "$commitAddress" --stat | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/log/$commitAddress.index" &
			waitQueue 0.5 "$totalCPUS"
			timeout 120 git diff "$commitAddress"~ "$commitAddress" | recode ..HTML > "$webDirectory/repos/$repoName/diff/$commitAddress.index" &
			waitQueue 0.5 "$totalCPUS"
			timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%ct' > "$webDirectory/repos/$repoName/date/$commitAddress.index" &
			waitQueue 0.5 "$totalCPUS"
			timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%an' > "$webDirectory/repos/$repoName/author/$commitAddress.index" &
			waitQueue 0.5 "$totalCPUS"
			timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%ae' > "$webDirectory/repos/$repoName/email/$commitAddress.index" &
			waitQueue 0.5 "$totalCPUS"
			timeout 120 git show "$commitAddress" --no-patch --no-notes --pretty='%s' > "$webDirectory/repos/$repoName/msg/$commitAddress.index" &
			waitQueue 0.5 "$totalCPUS"
		done
		INFO "$repoName : Building README.md"
		# generate html from README.md if found in repo
		# - convert the readme links pointing to github local resources to source directory sources
		# - pandoc is how github renders markdown so use it if available with markdown as a backup
		if test -f "$repoSource/README.md";then
			if test -f /usr/bin/pandoc;then
				cat "$repoSource/README.md" | sed -E "s/\(\.\//(.\/source\//g" | pandoc -t html -o "$webDirectory/repos/$repoName/readme.index"
			elif test -f /usr/bin/markdown;then
				cat "$repoSource/README.md" | sed -E "s/\(\.\//(.\/source\//g" | markdown "$repoSource/README.md" > "$webDirectory/repos/$repoName/readme.index"
			fi
		fi

		INFO "$repoName : Building lint data for shellscripts"
		# run lint on all the existing files that support it
		#find "$repoSource" -type f -name "*.sh" | sort | while read sourceFilePath;do
		if test -f "/usr/bin/shellcheck";then
			find "." -type f -name "*.sh" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="%ct" "$sourceFilePath" > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
						shellcheck "$sourceFilePath" | txt2html --extract --escape_HTML_chars > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
						waitQueue 0.5 "$totalCPUS"
				fi
				# generate the documentation for the file
				buildBashDocstrings "$sourceFilePath" "$webDirectory/repos/$repoName/doc/$tempSourceSum.index" &
				waitQueue 0.5 "$totalCPUS"
				# count the number of functions
				#cat "$fileName" | grep --ignore-case -c "^function" > "$webDirectory/repos/$repoName/doc_count/$tempSourceSum.index" &
				#waitQueue 0.5 "$totalCPUS"
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
				git log -1 --pretty="%ct" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					weblint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
					waitQueue 0.5 "$totalCPUS"
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
		if test -f "/var/cache/2web/generated/pip/jslint/bin/jslint-cli";then
			find "." -type f -name "*.js" | sort | while read sourceFilePath;do
				tempSourceSum=$(popPath "$sourceFilePath")
				git log -1 --pretty="%ct" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					/var/cache/2web/generated/pip/jslint/bin/jslint-cli "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
					waitQueue 0.5 "$totalCPUS"
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
				git log -1 --pretty="%ct" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					pylint "$sourceFilePath" | txt2html --extract --escape_HTML_chars  > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index" &
					waitQueue 0.5 "$totalCPUS"
				fi
				# build the python docstrings
				pydoc3 "$sourceFilePath" > "$webDirectory/repos/$repoName/doc/$tempSourceSum.index" &
				waitQueue 0.5 "$totalCPUS"
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
				git log -1 --pretty="%ct" $sourceFilePath > "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index"
				if [ $( cat "$webDirectory/repos/$repoName/lint_time/$tempSourceSum.index" | wc -c ) -gt 6 ];then
					{
						# use php syntax checking and weblint for lint output of php files
						php --syntax-check "$sourceFilePath" | txt2html --extract --escape_HTML_chars
						if test -f "/usr/bin/weblint";then
								weblint "$sourceFilePath" | txt2html --extract --escape_HTML_chars &
								waitQueue 0.5 "$totalCPUS"
						fi
					} > "$webDirectory/repos/$repoName/lint/$tempSourceSum.index"
					# write php docstrings
					buildPhpDocstrings "$sourceFilePath" "$webDirectory/repos/$repoName/doc/$tempSourceSum.index" &
					waitQueue 0.5 "$totalCPUS"
				fi
			done
		fi
		INFO "$repoName : Building QR code"
		# build a qr code for the icon link
		qrencode -m 1 -l H -o "/var/cache/2web/web/repos/$repoName/thumb.png" "http://$(hostname).local/repos/$repoName/" &
		waitQueue 0.5 "$totalCPUS"
		# check if the video should be rendered
		renderVideo="yes"
		if yesNoCfgCheck "/etc/2web/repos/renderVideo.cfg";then
			renderVideo="yes"
		else
			renderVideo="no"
		fi
		if echo "$@" | grep -q -e "--no-video";then
			renderVideo="no"
		fi
		# render the video
		if echo "$renderVideo" | grep -q "yes";then
			INFO "$repoName : Rendering gource video..."
			/usr/bin/gource --output-custom-log "$webDirectory/repos/$repoName/GOURCE_LOG_RAW.gource" "$webDirectory/repos/$repoName/source/"
			# Add repo name to the combined repo log
			cp -v "$webDirectory/repos/$repoName/GOURCE_LOG_RAW.gource" "$webDirectory/repos/$repoName/GOURCE_LOG.gource"
			# filter the source file names to include the repo name for the combined video
			sed -i -r "s#(.+)\|#\1|/$repoName#" "$webDirectory/repos/$repoName/GOURCE_LOG.gource"
			# This parathensis is for a bug in vim while viewing the code, do not touch -> "
			# build history video in 720p from the generated log

			# render the gource log into images
			/usr/bin/xvfb-run /usr/bin/gource --key --max-files 0 -s 1 -c 4 -1280x720 -o "$webDirectory/repos/$repoName/GOURCE_IMAGES.ppm" "$webDirectory/repos/$repoName/GOURCE_LOG.gource"
			# render the gource images into a video file
			/usr/bin/ffmpeg -y -r 60 -f image2pipe -vcodec ppm -i "$webDirectory/repos/$repoName/GOURCE_IMAGES.ppm" -vcodec libx264 -preset medium -pix_fmt yuv420p -crf 1 -threads "$totalCPUS" -bf 0 "$webDirectory/repos/$repoName/repoHistory.mp4"
			# remove the ppm file that is un-needed after the format conversion
			# - this file is raw video and is way to large to keep
			rm -v "$webDirectory/repos/$repoName/GOURCE_IMAGES.ppm"
		else
			INFO "$repoName : Skip video rendering..."
			# remove any video generated when the setting was set to yes
			if test -f "$webDirectory/repos/$repoName/repoHistory.mp4";then
				# remove generated video
				rm -v "$webDirectory/repos/$repoName/repoHistory.mp4"
			fi
			if test -f "$webDirectory/repos/$repoName/repoHistory.png";then
				# remove generated thumbnail
				rm -v "$webDirectory/repos/$repoName/repoHistory.png"
			fi
		fi
		# block until video rendering is done, the video render will use all of the cpu cores anyway
		# NOTE: gource currently still refuses to render no headless servers so it only works if you run git2web on a desktop with graphics support
		# build thumbnail for repo from video or from graphs if video does not render
		if test -f "$webDirectory/repos/$repoName/repoHistory.mp4";then
			# build the thumbnail from the end of the video
			ffmpegthumbnailer -i "$webDirectory/repos/$repoName/repoHistory.mp4" -o "$webDirectory/repos/$repoName/repoHistory.png" -s 0 -t "100%"
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
		SQLaddToIndex "/repos/$repoName/repoHistory.png" "$webDirectory/backgrounds.db" "all_fanart"
		SQLaddToIndex "/repos/$repoName/repoHistory.png" "$webDirectory/backgrounds.db" "repos_fanart"

		#
		addToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/repos/repos.index"
		addToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/new/repos.index"
		addToIndex "$webDirectory/repos/$repoName/repos.index" "$webDirectory/new/all.index"
		# update update times
		date "+%s" > /var/cache/2web/web/new/all.cfg
		date "+%s" > /var/cache/2web/web/new/repos.cfg

		# update the repo status sum
		echo -n "$repoStatusSum" > "$webDirectory/repos/$repoName/status.cfg"
	fi
}
################################################################################
webUpdate(){
	# Update the webserver content for git2web, search for content in paths and generate webpages.
	#
	# - This make any content in paths accessable via the web interface.
	#
	# RETURN NULL, FILES
	addToLog "INFO" "STARTED Webgen" "$(date)"
	# read the download directory and convert repos into webpages
	# - There are 2 types of directory structures for repos in the download directory
	#   + gitWebsite/gitName/chapter/image.png
	#   + gitWebsite/gitName/image.png

	webDirectory=$(webRoot)

	downloadDirectory="$(loadConfigs "/etc/2web/repos/libaries.cfg" "/etc/2web/repos/libaries.d/" "/etc/2web/config_default/git2web_libraries.cfg" | tr -s '\n' | shuf)"

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


	# check if the video should be rendered
	renderCombinedVideo="yes"
	if yesNoCfgCheck "/etc/2web/repos/renderVideo.cfg";then
		renderCombinedVideo="yes"
	else
		renderCombinedVideo="no"
	fi
	if echo "$@" | grep -q -e "--no-video";then
		renderCombinedVideo="no"
	fi
	if echo "$renderCombinedVideo" | grep -q "yes";then
		INFO "Writing combined Gource video..."
		if checkFileDataSum "$webDirectory" "$webDirectory/repos/repos.index";then
			# combine the gource logs to make a combined repo video
			{
				cat "$webDirectory"/repos/*/GOURCE_LOG.gource | sort -n
			} > "$webDirectory/repos/combined_gource.gource"

			# render the gource log into images
			/usr/bin/xvfb-run /usr/bin/gource --key --max-files 0 -s 1 -c 4 -1280x720 -o "$webDirectory/repos/GOURCE_IMAGES.ppm" "$webDirectory/repos/combined_gource.gource"
			# render the gource images into a video file
			/usr/bin/ffmpeg -y -r 60 -f image2pipe -vcodec ppm -i "$webDirectory/repos/GOURCE_IMAGES.ppm" -vcodec libx264 -preset medium -pix_fmt yuv420p -crf 1 -threads "$totalCPUS" -bf 0 "$webDirectory/repos/allHistory.mp4"
			# remove the ppm file that is un-needed after the format conversion
			# - this file is raw video and is way to large to keep
			rm -v "$webDirectory/repos/GOURCE_IMAGES.ppm"
			setFileDataSum "$webDirectory" "$webDirectory/repos/repos.index"
		fi
	else
		INFO "Skip combined video rendering..."
		# remove existing old versions of video
		if test -f "$webDirectory/repos/allHistory.mp4";then
			rm -v "$webDirectory/repos/allHistory.mp4"
		fi
	fi

	if test -f "$webDirectory/repos/allHistory.mp4";then
		# build the thumbnail from the end of the video
		ffmpegthumbnailer -i "$webDirectory/repos/allHistory.mp4" -o "$webDirectory/repos/allHistory.png" -s 0 -t "100%"
	fi

	# finish building main index page a-z
	linkFile "/usr/share/2web/templates/repos.php" "$webDirectory/repos/index.php"

	# the random index simply uses the main index for repos
	linkFile "$webDirectory/repos/repos.index" "$webDirectory/random/repos.index"
	#
	addToLog "INFO" "Webgen FINISHED" "$(date)"
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
	sqlite3 $webDirectory/data.db "drop table _repos;"
	sqlite3 $webDirectory/data.db "drop table _repos_fanart;"
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
		# upgrade the pip packages if the module is enabled
		checkModStatus "git2web"
		upgrade-pip "git2web" "jslint"
	elif [ "$1" == "--force-upgrade" ];then
		# force upgrade or install of all the pip packages
		upgrade-pip
	elif [ "$1" == "--demo-push" ] || [ "$1" == "demo-push" ] ;then
		# create a list of file extensions
		fileExtensions=".txt .py .sh"
		# create new commits for existing repos in the demo data repos
		find "/var/cache/2web/generated/demo/repos/" -maxdepth 1 -mindepth 1 -type d | while read repoSource;do
			# for each of the found repos generate new randomized commits
			projectPath="$repoSource"
			# get the name of the repo
			randomTitle="$(basename "$repoSource")"
			# random start date between 10 and 300 days ago
			projectStartDate="$(date)"
			# create a random list of commiter names
			userNames="$(randomWord)\n"
			for index1 in $(seq -w $(( 1 + ( $RANDOM % 20 ) )) );do
				userNames="$userNames$(randomWord)\n"
			done
			ALERT "USER NAMES = '$userNames'"
			# generate a random number of commits for each repo
			for index1 in $(seq -w $(( 2 + ( $RANDOM % 5 ) )) );do
				# for each commit pick a random username
				userName="$( echo -e "$userNames" | tr -s '\n' | shuf | head -1 )"
				ALERT "CHOSEN USER NAME FOR COMMIT = '$userName'"
				# set the user name for the user commiting this commit
				git -C "$projectPath" config --add user.email "${userName}@demoData.local"
				git -C "$projectPath" config --add user.name "${userName} DemoData"
				# add a random number of files
				for index2 in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
					# choose a random file type
					extension=$( echo "$fileExtensions" | cut -d' ' -f$(( ( $RANDOM % $(echo "$fileExtensions" | wc --words) ) + 1 )) )
					randomFile="/var/cache/2web/generated/demo/repos/$randomTitle/$(randomWord)$extension"
					# create diffrent files based on extension
					if [ $extension == ".py" ];then
						commentPrefix="# "
					elif [ $extension == ".sh" ];then
						commentPrefix="# "
					elif [ $extension == ".js" ];then
						commentPrefix="// "
					else
						commentPrefix=""
					fi
					# create a random number of comments
					for index3 in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
						# add the comment prefix to the line
						echo -n "${commentPrefix}" >> "$randomFile"
						# set a random number of words in the comment line
						for index4 in $(seq -w $(( 1 + ( $RANDOM % 10 ) )) );do
							echo -n "$(randomWord) " >> "$randomFile"
						done
						# write the end of the line
						echo -ne "\n" >> "$randomFile"
					done
					# add the generated file to the git repo
					git -C "/var/cache/2web/generated/demo/repos/$randomTitle/" add "$randomFile"
				done
				# convert the date format to what git requires
				tempCommitTime="$(date)"
				# set the dates for the commit to be the same
				export GIT_COMMITTER_DATE="$tempCommitTime"
				export GIT_AUTHOR_DATE="$tempCommitTime"
				# commit the generated files
				# - create a randomized commit message
				commitMessage=""
				for index in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
					commitMessage="$commitMessage$(randomWord) "
				done
				# add the commit
				git -C "/var/cache/2web/generated/demo/repos/$randomTitle/" commit -m "$commitMessage"
				# unset values so the users shell is unaffected
				unset GIT_COMMITTER_DATE
				unset GIT_AUTHOR_DATE
			done
		done
	elif [ "$1" == "--demo-data" ] || [ "$1" == "demo-data" ] ;then
		# generate demo data git repos

		# check for parallel processing and count the cpus
		if echo "$@" | grep -q -e "--parallel";then
			totalCPUS=$(cpuCount)
		else
			totalCPUS=1
		fi
		# create a list of file extensions
		fileExtensions=".txt .py .sh"
		#########################################################################################
		# comic2web demo comics
		#########################################################################################
		createDir "/var/cache/2web/generated/demo/repos/"
		# build random git repos
		for index0 in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
			# generate the random git repo name
			randomTitle="$RANDOM $(randomWord) $(randomWord)"
			projectPath="/var/cache/2web/generated/demo/repos/$randomTitle/"
			# random start date between 10 and 300 days ago
			projectStartDate=$(( 1000 + ( $RANDOM % 300000 ) ))
			#
			commitTime=$(( $projectStartDate - ( $RANDOM % 5 ) ))
			# create the repo
			createDir "$projectPath"
			# initlize the repo
			git -C "$projectPath" init
			# disable the safe directory system
			git -C "$projectPath" config --add safe.directory '*'
			# create a random list of commiter names
			userNames="$(randomWord)\n"
			for index1 in $(seq -w $(( 2 + ( $RANDOM % 20 ) )) );do
				userNames="$userNames$(randomWord)\n"
			done
			ALERT "USER NAMES = '$userNames'"
			# generate a random number of commits for each repo
			for index1 in $(seq -w $(( 2 + ( $RANDOM % 150 ) )) );do
				# for each commit pick a random username
				userName="$( echo -e "$userNames" | tr -s '\n' | shuf | head -1 )"
				ALERT "CHOSEN USER NAME FOR COMMIT = '$userName'"
				# set the user name for the user commiting this commit
				git -C "$projectPath" config --add user.email "${userName}@demoData.local"
				git -C "$projectPath" config --add user.name "${userName} DemoData"
				# add a random number of files
				for index2 in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
					# choose a random file type
					extension=$( echo "$fileExtensions" | cut -d' ' -f$(( ( $RANDOM % $(echo "$fileExtensions" | wc --words) ) + 1 )) )
					randomFile="/var/cache/2web/generated/demo/repos/$randomTitle/$(randomWord)$extension"
					# create diffrent files based on extension
					if [ $extension == ".py" ];then
						commentPrefix="# "
					elif [ $extension == ".sh" ];then
						commentPrefix="# "
					elif [ $extension == ".js" ];then
						commentPrefix="// "
					else
						commentPrefix=""
					fi
					# create a random number of comments
					for index3 in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
						# add the comment prefix to the line
						echo -n "${commentPrefix}" >> "$randomFile"
						# set a random number of words in the comment line
						for index4 in $(seq -w $(( 1 + ( $RANDOM % 10 ) )) );do
							echo -n "$(randomWord) " >> "$randomFile"
						done
						# write the end of the line
						echo -ne "\n" >> "$randomFile"
					done
					# add the generated file to the git repo
					git -C "/var/cache/2web/generated/demo/repos/$randomTitle/" add "$randomFile"
				done
				# update the commit time
				# - time in minutes
				commitTime=$(( $commitTime - ( $RANDOM % 7200 ) ))
				#commitTime=$(( $commitTime - 1 ))
				# convert the date format to what git requires
				tempCommitTime="$(date --date="$commitTime hours ago")"
				# set the dates for the commit to be the same
				export GIT_COMMITTER_DATE="$tempCommitTime"
				export GIT_AUTHOR_DATE="$tempCommitTime"
				# commit the generated files
				# - create a randomized commit message
				commitMessage=""
				for index in $(seq -w $(( 1 + ( $RANDOM % 5 ) )) );do
					commitMessage="$commitMessage$(randomWord) "
				done
				# add the commit
				git -C "/var/cache/2web/generated/demo/repos/$randomTitle/" commit -m "$commitMessage"
				# unset values so the users shell is unaffected
				unset GIT_COMMITTER_DATE
				unset GIT_AUTHOR_DATE
			done
		done
		# get the size of the demo data generated
		du -sh /var/cache/2web/generated/demo/repos/
		#########################################################################################
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
