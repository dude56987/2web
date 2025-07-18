#! /bin/bash
########################################################################
# 2web unified queue system
# Copyright (C) 2024  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under  the terms of the GNU General Public License as published by
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
source /var/lib/2web/common
########################################################################
# queue2web functions
########################################################################
function addJob(){
	# addJob $queueName $userCommand $uniqueFlag
	#
	# Add a command to be executed by the queue
	#
	# - Commands are ran as the 'root' user, BEWARE
	# - The unique flag is not required but the default value is 'no'
	# - If the uniqueFlag is set to 'yes' then that command can only be
	#   allowed one running instance of its execution by the queue.
	#   Additional attempts to send this command to the queue will be
	#   discarded by the queue system.
	# - The selected queue will cause the job to be processed diffrently
	#
	# Queues
	# ======
	#
	# - The single queue will only allow one job at a time to run. The AI
	#   module uses this because AI jobs can not run in parallel.
	# - The multi queue will use 1 less than the number of cores in the
	#   system. This will act as a single queue on a single core system.
	# - The idle queue will only start jobs when the system load is below 1.
	#   This queue still operates like the multi queue though and can run
	#   jobs in parallel.
	queueType=$1
	userCommand=$2
	unique=$3
	if [ $queueType == "single" ];then
		echo "You have added a job to the single queue"
	elif [ $queueType == "multi" ];then
		echo "You have added a job to the multi queue"
	elif [ $queueType == "idle" ];then
		echo "You have added a job to the idle queue"
	else
		# this is in error only single and multi queues are available
		exit
	fi
	# generate a timestamp at the start of the filename
	timestamp=$(date "+%s %N")
	# generate a md5sum of the command itself for the name of the queue job file
	commandSum=$(echo -n "$userCommand" | md5sum | cut -d' ' -f1)
	# create the queue filename
	queueFileName="${timestamp}-${commandSum}.cmd"
	# check for unique setting
	if [ "$unique" == "yes" ];then
		# add the job to the unique queue
		touch "/var/cache/2web/queue/unique/${commandSum}.cfg"
	fi
	echo "$userCommand" > "/var/cache/2web/queue/${queueType}/${queueFileName}"
}
########################################################################
function captureOutput(){
	fileName="$1"
	tee "$fileName"
}
########################################################################
function processThreadedJob(){
	# run a threaded job in the queue and remove the job if the command is successfull
	queueFile=$1
	lockFilePath="/var/cache/2web/queue/active/$(basename "$queueFile" | cut -d'.' -f1).active"
	logFilePath="/var/cache/2web/queue/log/$(basename "$queueFile" | cut -d'.' -f1).log"
	# create the lock file
	if ! test -f "$lockFilePath";then
		# lock the command execution from being doubled by the queue system
		touch "$lockFilePath"
		# get the data from the queue file
		queueFileData="$(cat "$queueFile")"
		# Check if the unique id lock has been set on this job
		# - unique jobs can not be executed at the same time
		# - unique jobs cause other jobs to exit if the command given is the same
		# - this is used to enable unique jobs that only are added once like search queries, and resolver jobs
		jobID=$(echo -n "$queueFileData" | md5sum | cat -d' ' -f1)
		if test -f "/var/cache/2web/queue/unique/$jobID.cfg";then
			if test -f "/var/cache/2web/queue/unique/$jobID.active";then
				# remove locks to the queue system for this process since it is running already

				# remove the lock file
				rm -v "$lockFilePath"
				# remove the command from the queue
				rm -v "$queueFile"
				# close this function
				return
			else
				# lock the job as unique on first run
				touch "/var/cache/2web/queue/unique/$jobID.active"
			fi
		fi
		addToLog "INFO" "Queue Starting Job " "Command started processing '$queueFileData'."

		if [ "$useLogs" == "yes" ];then
			bash "$queueFile" | captureOutput "$logFilePath"
			exitStatus=$?
		else
			bash "$queueFile"
			exitStatus=$?
		fi
		# get the log data
		if [ "$useLogs" == "yes" ];then
			jobOutput="$(cleanText "$(cat "$logFilePath")")"
		else
			jobOutput="Job Output Log Disabled"
		fi
		if [ 0 -eq $exitStatus ];then
			# remove the job from the queue
			rm -v "$queueFile"
			addToLog "INFO" "Queue Job Log" "Command '$queueFileData' has succeeded.<br><h2>Job Output</h2><pre>$jobOutput</pre>"
			addToLog "INFO" "Queue Job Success" "Command in queue '$queueFileData' has succeeded."
		else
			addToLog "ERROR" "Queue Job Failed" "Command in queue '$queueFileData' has failed.<br><h2>Job Output</h2><pre>$jobOutput</pre>"
			# failed jobs should be moved into the failed job directory and ignored from then on
			cp -v "$queueFile" "/var/cache/2web/queue/failed/"
			# remove the orignal command file to prevent the queue from trying to run a failed job again
			rm -v "$queueFile"
		fi
		# remove unique config files if they were created
		if test -f "/var/cache/2web/queue/unique/$jobID.cfg";then
			rm -v "/var/cache/2web/queue/unique/$jobID.cfg"
		fi
		if test -f "/var/cache/2web/queue/unique/$jobID.active";then
			rm -v "/var/cache/2web/queue/unique/$jobID.active"
		fi
		# remove the lock file
		rm -v "$lockFilePath"
	fi
}
########################################################################
function processSingleJobQueue(){
	# process all the jobs found in the queue system
	find /var/cache/2web/queue/single/ -name "*.cmd" | sort | while read -r queueFile;do
		# for each job in the single queue
		processThreadedJob "$queueFile"
		#waitQueue 0.5 "1"
	done
	#blockQueue 1
}
########################################################################
function processIdleJobQueue(){
	# process all the jobs found in the idle queue system
	# - only process the next item when the sytem load is less than 10%
	totalCPUS=$(cpuCount)
	# get the idle load from the max load, a percentage of the max load
	idleLoad=$(( totalCPUS / 5 ))
	# the idle load should never be below 1
	if [ $idleLoad -le 0 ];then
		idleLoad=1
	fi
	# watch the queue
	find /var/cache/2web/queue/idle/ -name "*.cmd" | sort | while read -r queueFile;do
		# wait for the idle queue to get to less than half the system load
		while [ $( echo "$(cat /proc/loadavg | cut -d' ' -f1) > $idleLoad" | bc ) -eq 1 ] && [ $(find "/var/cache/2web/queue/active/" -name "*.active" | wc -l) -gt 0 ];do
			INFO "Waiting for system to become idle..."
			sleep 10
		done
		# for each job in the single queue
		processThreadedJob "$queueFile" &
		sleep 1
	done
}
########################################################################
function processMultiJobQueue(){
	# processMultiJobQueue
	#
	# Process all the jobs found in the queue system.
	#
	# - Multi queue job queue takes into account the single job queue as a job in the multi queue
	# - The size of the multi queue is determined by the interger value in /etc/2web/multiQueueSize.cfg
	#

	# load the setting for the number of multi jobs to run in parallel
	if test -f "/etc/2web/multiQueueSize.cfg";then
		# load the config setting
		multiQueueSize="$( cat '/etc/2web/multiQueueSize.cfg' | tr -d '\n' )"
	else
		# save the default config
		# - This will process 3 jobs in parallel
		{
			echo -n "3"
		} > "/etc/2web/multiQueueSize.cfg"
		multiQueueSize="3"
	fi

	# check for parallel processing and count the cpus
	#totalCPUS=$(cpuCount)
	totalCPUS="$multiQueueSize"

	# while there are still jobs in the queue keep reloading them into the queue
	while [ $(find "/var/cache/2web/queue/multi/" -name "*.cmd" | wc -l ) -gt 0 ];do
		# load up files in queue to be processed
		find /var/cache/2web/queue/multi/ -name "*.cmd" | sort | while read -r queueFile;do
			# wait for the job queue to free up by checking the active jobs
			while [ $(find "/var/cache/2web/queue/active/" -name "*.active" | wc -l) -ge $totalCPUS ];do
				echo "The queue is full wait for queue to free up..."
				sleep 10
			done
			processThreadedJob "$queueFile" &
			sleep 1
			#waitQueue 0.5 "$totalCPUS"
		done
		# sleep a few seconds between queue checks
		sleep 10
	done

	#blockQueue 1
}
########################################################################
function overviewQueueService(){
	# lock the process so multuple instances of the service do not run at the same time
	lockProc "queue2web"
	# create the process lock file directory
	createDir "/var/cache/2web/queue/active/"
	# create directory to store failed jobs
	createDir "/var/cache/2web/queue/failed/"
	# create the unique status for queue items
	createDir "/var/cache/2web/queue/unique/"
	# log the output of each command ran
	createDir "/var/cache/2web/queue/log/"
	# cleanup queue lock files
	rm -v "/var/cache/2web/queue/multi.active"
	rm -v "/var/cache/2web/queue/single.active"
	rm -v "/var/cache/2web/queue/idle.active"
	# remove all the active process locks
	rm -v /var/cache/2web/queue/active/*.active
	# check if the logs are enabled
	if yesNoCfgCheck "/etc/2web/useQueueLogs.cfg" "no";then
		# set the global value for logs used passed to all functions
		useLogs="yes"
	else
		useLogs="no"
	fi
	# launch the multi and the single queues in parallel
	while true;do
		if ! test -f "/var/cache/2web/queue/multi.active";then
			multiQueueService &
			touch "/var/cache/2web/queue/multi.active"
		fi
		if ! test -f "/var/cache/2web/queue/single.active";then
			singleQueueService &
			touch "/var/cache/2web/queue/single.active"
		fi
		if ! test -f "/var/cache/2web/queue/idle.active";then
			idleQueueService &
			touch "/var/cache/2web/queue/idle.active"
		fi
		# print the queue processing info
		singleQueueSize=$(find "/var/cache/2web/queue/single/" -name "*.cmd" | wc -l)
		multiQueueSize=$(find "/var/cache/2web/queue/multi/" -name "*.cmd" | wc -l)
		idleQueueSize=$(find "/var/cache/2web/queue/idle/" -name "*.cmd" | wc -l)
		failedQueueSize=$(find "/var/cache/2web/queue/failed/" -name "*.cmd" | wc -l)
		# draw the Queue sizes every 30 seconds
		INFO "Single Queue:$singleQueueSize Multi Queue:$multiQueueSize Idle Queue:$idleQueueSize Failed Jobs:$failedQueueSize"
		# sleep the overview process
		sleep 30
	done
	# remove active state file
	if test -f /var/cache/2web/web/queue2web.active;then
		rm /var/cache/2web/web/queue2web.active
	fi
}
########################################################################
function liveView(){
	clear
	activeTab="active"
	tabs="active failed idle multi single log"
	#
	lastTab="log"
	nextTabButton="active"
	previousTabButton=""
	#
	while true;do
		for tabName in $tabs;do
			if [ "$tabName" == "$activeTab" ];then
				previousTabButton="$lastTab"
			fi
			if [ "$lastTab" == "$activeTab" ];then
				nextTabButton="$tabName"
				break
			fi
			lastTab="$tabName"
		done
		# count job types
		activeJobFiles=$(find /var/cache/2web/queue/active/ -type f -name '*.active' | sort)
		activeJobCount=$(echo -n "$activeJobFiles" | wc -l)
		failedJobFiles=$(find /var/cache/2web/queue/failed/ -type f -name '*.cmd' | sort)
		failedJobCount=$(echo -n "$failedJobFiles" | wc -l)
		idleJobFiles=$(find /var/cache/2web/queue/idle/ -type f -name '*.cmd' | sort)
		idleJobCount=$(echo -n "$idleJobFiles" | wc -l)
		multiJobFiles=$(find /var/cache/2web/queue/multi/ -type f -name '*.cmd' | sort)
		multiJobCount=$(echo -n "$multiJobFiles" | wc -l)
		singleJobFiles=$(find /var/cache/2web/queue/single/ -type f -name '*.cmd' | sort)
		singleJobCount=$(echo -n "$singleJobFiles" | wc -l)
		logJobFiles=$(find /var/cache/2web/queue/log/ -type f -name '*.log' | sort)
		logJobCount=$(echo -n "$logJobFiles" | wc -l)
		# use curses clear command
		tput clear
		# the x,y cordnates are reversed in tput
		# y, x
		tput cup 0 0
		drawCellLine 6
		#tput cup 1 0
		startCellRow
		# draw the headers
		if [ "$activeTab" == "active" ];then
			highlightCell "Active Jobs" 6
		else
			drawCell "Active Jobs" 6
		fi
		if [ "$activeTab" == "failed" ];then
			highlightCell "Failed Jobs" 6
		else
			drawCell "Failed Jobs" 6
		fi
		if [ "$activeTab" == "idle" ];then
			highlightCell "Idle Jobs" 6
		else
			drawCell "Idle Jobs" 6
		fi
		if [ "$activeTab" == "multi" ];then
			highlightCell "Multi Jobs" 6
		else
			drawCell "Multi Jobs" 6
		fi
		if [ "$activeTab" == "single" ];then
			highlightCell "Single Jobs" 6
		else
			drawCell "Single Jobs" 6
		fi
		if [ "$activeTab" == "log" ];then
			highlightCell "Logs" 6
		else
			drawCell "Logs" 6
		fi
		endCellRow
		# create a new row
		#tput cup 2 0
		drawCellLine 6
		#tput cup 3 0
		startCellRow
		# draw the data
		drawCell "$activeJobCount" 6
		drawCell "$failedJobCount" 6
		drawCell "$idleJobCount" 6
		drawCell "$multiJobCount" 6
		drawCell "$singleJobCount" 6
		drawCell "$logJobCount" 6
		#tput cup 4 0
		endCellRow
		drawCellLine 6
		outputBuffer=""
		# draw the active tab file data for the most recent file
		if [ "$activeTab" == "failed" ];then
			echo "$failedJobFiles" | head -10 | while read -r filePath;do
				if test -f "$filePath";then
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$filePath"
					outputBuffer="$outputBuffer$(tr -d '\0' < "$filePath")"
				fi
			done
		elif [ "$activeTab" == "multi" ];then
			echo "$multiJobFiles" | head -10 | while read -r filePath;do
				if test -f "$filePath";then
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$filePath"
					outputBuffer="$outputBuffer$(tr -d '\0' < "$filePath")"
				fi
			done
		elif [ "$activeTab" == "log" ];then
			echo "$logJobFiles" | head -10 | while read -r filePath;do
				echo "$filePath"
				if test -f "$filePath";then
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$filePath"
					outputBuffer="$outputBuffer$(tr -d '\0' < "$filePath")"
				fi
			done
		elif [ "$activeTab" == "idle" ];then
			echo "$idleJobFiles" | head -10 | while read -r filePath;do
				if test -f "$filePath";then
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$filePath"
					outputBuffer="$outputBuffer$(tr -d '\0' < "$filePath")"
				fi
			done
		elif [ "$activeTab" == "active" ];then
			echo "$idleJobFiles" | head -10 | while read -r filePath;do
				if test -f "$filePath";then
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$filePath"
					outputBuffer="$outputBuffer$(tr -d '\0' < "$filePath")"
				fi
			done
		elif [ "$activeTab" == "single" ];then
			echo "$idleJobFiles" | head -10 | while read -r filePath;do
				if test -f "$filePath";then
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$filePath"
					outputBuffer="$outputBuffer$(drawLine)"
					outputBuffer="$outputBuffer$(tr -d '\0' < "$filePath")"
				fi
			done
		fi
		echo -ne "$outputBuffer" | head -10
		echo
		echo "Refreshed $(date) ,Use [q] key to exit..."
		#echo "Active Tab = $activeTab"
		#echo "Next Button = $nextTabButton"
		#echo "Previous Button = $previousTabButton"
		# sleep and read input for keybindings
		# - the interface will refresh if enter is pressed
		read -t 5 -n 1 inputKey
		#read -t 5 -rsn 1 inputKey
		if [ "$inputKey" == "" ];then
			echo
		elif [ "$inputKey" == "q" ];then
			exit
		elif [ "$inputKey" == "k" ];then
			# move right though the tabs
			#echo "RIGHT Pressed"
			activeTab="$nextTabButton"
		elif [ "$inputKey" == "j" ];then
			# move left though the tabs
			#echo "LEFT Pressed"
			activeTab="$previousTabButton"
		else
			# show the help if any random key is pressed
			echo
			echo "Unknown key = '$inputKey'"
			echo "Try 'j' to move left or 'k' to move right."
			echo
			sleep 5
		fi
	done
}

########################################################################
function multiQueueService(){
	# create the multi and single queues
	createDir "/var/cache/2web/queue/multi/"
	# setup the timeout that will reset the service
	# every X minutes check the queue
	timeOut=$(( (60 * 5) ))
	# launch the service that will run in the background to process new jobs added to the queue
	while true;do
		# run the queue before setting up the watch service
		processMultiJobQueue
		# wait for queue to activate
		inotifywait --csv --timeout "$timeOut" -r -e "MODIFY" -e "CREATE" "/var/cache/2web/queue/multi/" | while read event;do
			# while there are still jobs in the queue
			while [ $(find "/var/cache/2web/queue/multi/" -name "*.cmd" | wc -l ) -gt 0 ];do
				# if any files are added to the queue launch the process jobs function
				processMultiJobQueue
			done
		done
	done
}
########################################################################
function singleQueueService(){
	# create the multi and single queues
	createDir "/var/cache/2web/queue/single/"
	# setup the timeout that will reset the service
	# every X minutes check the queue
	timeOut=$(( (60 * 5) ))
	# launch the service that will run in the background to process new jobs added to the queue
	while true;do
		# run the queue before setting up the watch service
		processSingleJobQueue
		# wait for queue to activate
		inotifywait --csv --timeout "$timeOut" -r -e "MODIFY" -e "CREATE" "/var/cache/2web/queue/single/" | while read event;do
			# while there are still jobs in the queue
			while [ $(find "/var/cache/2web/queue/single/" -name "*.cmd" | wc -l ) -gt 0 ];do
				# if any files are added to the queue launch the process jobs function
				processSingleJobQueue
			done
		done
	done
}
########################################################################
function idleQueueService(){
	# The service that will run the idle queue this is a single queue that only allows the
	# next job to run when the system load is below 1
	createDir "/var/cache/2web/queue/idle/"
	# setup the timeout that will reset the service
	# every X minutes check the queue
	timeOut=$(( (60 * 5) ))
	# launch the service that will run in the background to process new jobs added to the queue
	while true;do
		# run the queue before setting up the watch service
		processIdleJobQueue
		# wait for queue to activate
		inotifywait --csv --timeout "$timeOut" -r -e "MODIFY" -e "CREATE" "/var/cache/2web/queue/idle/" | while read event;do
			# while there are still jobs in the queue
			while [ $(find "/var/cache/2web/queue/idle/" -name "*.cmd" | wc -l ) -gt 0 ];do
				# if any files are added to the queue launch the process jobs function
				processIdleJobQueue
			done
		done
	done
}
########################################################################
# set the CLI theme
LINE_THEME="flowersRand"
#
INPUT_OPTIONS="$@"
PARALLEL_OPTION="$(loadOption "parallel" "$INPUT_OPTIONS")"
MUTE_OPTION="$(loadOption "mute" "$INPUT_OPTIONS")"
########################################################################
# Process CLI options
########################################################################
if [ "$1" == "-s" ] || [ "$1" == "--service" ] || [ "$1" == "service" ] ;then
	# launch the service to process jobs as they are added to the server
	overviewQueueService
elif [ "$1" == "-l" ] || [ "$1" == "--live" ] || [ "$1" == "live" ] ;then
	liveView
elif [ "$1" == "-r" ] || [ "$1" == "--retry" ] || [ "$1" == "retry" ] ;then
	# move all failed jobs back into the multi job queue
	mv -v /var/cache/2web/queue/failed/*.cmd "/var/cache/2web/queue/multi/"
elif [ "$1" == "-c" ] || [ "$1" == "--clean" ] || [ "$1" == "clean" ] ;then
	# cleanup failed jobs
	delete "/var/cache/2web/queue/failed/"
	createDir "/var/cache/2web/queue/failed/"
	# clean up log files of command output
	delete "/var/cache/2web/queue/log/"
	createDir "/var/cache/2web/queue/log/"
elif [ "$1" == "--view-active" ] ;then
	allJobCommands=""
	# show the active job commands running in the queue
	activeJobFiles=$(find /var/cache/2web/queue/active/ -type f -name '*.active' | sort)
	#
	idleJobFiles=$(find /var/cache/2web/queue/idle/ -type f -name '*.cmd' | sort)
	multiJobFiles=$(find /var/cache/2web/queue/multi/ -type f -name '*.cmd' | sort)
	singleJobFiles=$(find /var/cache/2web/queue/single/ -type f -name '*.cmd' | sort)
	IFSBACKUP=$IFS
	IFS=!'\n'
	for jobCommandFile in $idleJobFiles;do
		if echo "$activeJobFiles" | grep -q "$(basename "$jobCommandFile")" ;then
			drawLine
			cat "$jobCommandFile"
		fi
	done
	for jobCommandFile in $multiJobFiles;do
		if echo "$activeJobFiles" | grep -q "$(basename "$jobCommandFile")" ;then
			drawLine
			cat "$jobCommandFile"
		fi
	done
	for jobCommandFile in $singleJobFiles;do
		if echo "$activeJobFiles" | grep -q "$(basename "$jobCommandFile")" ;then
			drawLine
			cat "$jobCommandFile"
		fi
	done
	IFS=$IFSBACKUP
elif [ "$1" == "-l" ] || [ "$1" == "--log" ] || [ "$1" == "log" ] ;then
	logJobFiles=$(find /var/cache/2web/queue/log/ -type f -name '*.log' | sort)
	logJobCount=$(echo -n "$logJobFiles" | wc -l)
	drawLine
	drawHeader "Job Queue Log"
	drawLine
	echo "$logJobFiles" | while read -r jobPath;do
		# display each of the jobs
		drawSmallHeader "$(basename "$jobPath")"
		cat "$jobPath"
		drawLine
	done
elif [ "$1" == "-j" ] || [ "$1" == "--jobs" ] || [ "$1" == "jobs" ] ;then
	activeJobFiles=$(find /var/cache/2web/queue/active/ -type f -name '*.active' | sort)
	activeJobCount=$(echo -n "$activeJobFiles" | wc -l)
	idleJobFiles=$(find /var/cache/2web/queue/idle/ -type f -name '*.cmd' | sort)
	idleJobCount=$(echo -n "$idleJobFiles" | wc -l)
	multiJobFiles=$(find /var/cache/2web/queue/multi/ -type f -name '*.cmd' | sort)
	multiJobCount=$(echo -n "$multiJobFiles" | wc -l)
	singleJobFiles=$(find /var/cache/2web/queue/single/ -type f -name '*.cmd' | sort)
	singleJobCount=$(echo -n "$singleJobFiles" | wc -l)
	# calc the total jobs
	totalJobs=$(( singleJobCount + multiJobCount + idleJobCount ))
	drawCellLine 2
	# draw the headers
	startCellRow
	drawCell "Total Jobs" 2
	drawCell "Active Jobs" 2
	endCellRow
	# create a new row
	drawCellLine 2
	# draw the data
	startCellRow
	drawCell "$totalJobs" 2
	drawCell "$activeJobCount" 2
	endCellRow
	drawCellLine 2
	drawHeader "Multi Queue Jobs"
	echo "$multiJobFiles" | while read -r jobPath;do
		# display each of the jobs
		drawSmallHeader "$(basename "$jobPath")"
		cat "$jobPath"
		drawLine
	done
	drawHeader "Single Queue Jobs"
	echo "$singleJobFiles" | while read -r jobPath;do
		# display each of the jobs
		drawSmallHeader "$(basename "$jobPath")"
		cat "$jobPath"
		drawLine
	done
	drawHeader "Idle Queue Jobs"
	echo "$idleJobFiles" | while read -r jobPath;do
		# display each of the jobs
		drawSmallHeader "$(basename "$jobPath")"
		cat "$jobPath"
		drawLine
	done
elif [ "$1" == "-s" ] || [ "$1" == "--status" ] || [ "$1" == "status" ] ;then
	activeJobFiles=$(find /var/cache/2web/queue/active/ -type f -name '*.active' | sort)
	activeJobCount=$(echo -n "$activeJobFiles" | wc -l)
	failedJobFiles=$(find /var/cache/2web/queue/failed/ -type f -name '*.cmd' | sort)
	failedJobCount=$(echo -n "$failedJobFiles" | wc -l)
	idleJobFiles=$(find /var/cache/2web/queue/idle/ -type f -name '*.cmd' | sort)
	idleJobCount=$(echo -n "$idleJobFiles" | wc -l)
	multiJobFiles=$(find /var/cache/2web/queue/multi/ -type f -name '*.cmd' | sort)
	multiJobCount=$(echo -n "$multiJobFiles" | wc -l)
	singleJobFiles=$(find /var/cache/2web/queue/single/ -type f -name '*.cmd' | sort)
	singleJobCount=$(echo -n "$singleJobFiles" | wc -l)
	logJobFiles=$(find /var/cache/2web/queue/log/ -type f -name '*.log' | sort)
	logJobCount=$(echo -n "$logJobFiles" | wc -l)

	drawCellLine 6
	# draw the headers
	startCellRow
	drawCell "Active Jobs" 6
	drawCell "Failed Jobs" 6
	drawCell "Idle Jobs" 6
	drawCell "Multi Jobs" 6
	drawCell "Single Jobs" 6
	drawCell "Logs" 6
	endCellRow
	# create a new row
	drawCellLine 6
	# draw the data
	startCellRow
	drawCell "$activeJobCount" 6
	drawCell "$failedJobCount" 6
	drawCell "$idleJobCount" 6
	drawCell "$multiJobCount" 6
	drawCell "$singleJobCount" 6
	drawCell "$logJobCount" 6
	endCellRow
	drawCellLine 6
elif [ "$1" == "-a" ] || [ "$1" == "--add" ] || [ "$1" == "add" ];then
	# add a job to the queue
	addJob "$2" "$3"
elif [ "$1" == "-u" ] || [ "$1" == "--unique" ] || [ "$1" == "unique" ];then
	# add a unique job to the queue
	addJob "$2" "$3" "yes"
elif [ "$1" == "--stop" ] || [ "$1" == "stop" ] || [ "$1" == "--cancel" ] || [ "$1" == "cancel" ];then
	# empty the queues of all jobs
	delete "/var/cache/2web/queue/multi/"
	createDir "/var/cache/2web/queue/multi/"
	delete "/var/cache/2web/queue/unique/"
	createDir "/var/cache/2web/queue/unique/"
	delete "/var/cache/2web/queue/single/"
	createDir "/var/cache/2web/queue/single/"
	delete "/var/cache/2web/queue/idle/"
	createDir "/var/cache/2web/queue/idle/"
	delete "/var/cache/2web/queue/active/"
	createDir "/var/cache/2web/queue/active/"
	#
	drawLine
	echo "All Jobs have been canceled in the queue."
	drawLine
	echo "Active jobs running currently in the queue may still remain running even after the queue service has been stopped."
	drawLine
	echo "This and all existing queue service processes will now be killed."
	drawLine
	# kill running processes
	killall queue2web &
elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ];then
	enableMod "queue2web"
elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ];then
	disableMod "queue2web"
	# kill all remaining queues running on the server
	killall queue2web
else
	drawLine
	drawSmallHeader "queue2web queue processing system for 2web"
	drawLine
	echo "- Use --service to launch the queue processing service."
	echo "- Add multithreaded jobs with 'queue2web --add multi \"testCommand\"'"
	echo "- Add one at a time jobs with 'queue2web --add single \"testCommand\"'"
	echo "- Jobs can be added to the queues by adding files with the .cmd extension to queue "
	echo "  directories"
	echo "- To add multithreaded jobs add files to /var/cache/2web/queue/multi/"
	echo "- To run one at a time jobs add files to /var/cache/2web/queue/single/"
	echo "- Unfinished jobs will survive reboots of the server"
	echo "- The service will be started automatically by cron"
	drawLine
	echo "--cancel"
	echo "   Stop all queued jobs"
	echo "--jobs"
	echo "   Will list all queued jobs"
	echo "--retry"
	echo "   Will move all failed jobs into the multi queue to be attempted again"
	echo "--log"
	echo "   Show all logs from finished jobs"
	echo "--clean"
	echo "   Cleanup the failed jobs and the job logs in the queue system"
	drawLine
fi
