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
	# addJob $queueName $userCommand
	# add a command to be executed by the queue
	queueType=$1
	userCommand=$2
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
	commandSum=$(echo "$userCommand" | md5sum | cut -d' ' -f1)
	# create the queue filename
	queueFileName="${timestamp}-${commandSum}.cmd"
	echo "$userCommand" > "/var/cache/2web/queue/${queueType}/${queueFileName}"
}
########################################################################
function processThreadedJob(){
	# run a threaded job in the queue and remove the job if the command is successfull
	queueFile=$1
	lockFilePath="/var/cache/2web/queue/active/$(basename "$queueFile" | cut -d'.' -f1).active"
	# create the lock file
	if ! test -f "$lockFilePath";then
		queueFileData="$(cat "$queueFile")"
		addToLog "INFO" "Queue Starting Job " "Command started processing '$queueFileData'."
		# lock the command execution from being doubled
		touch "$lockFilePath"
		# run the command
		bash "$queueFile"
		exitStatus=$?
		if [ 0 -eq $exitStatus ];then
			# remove the job from the queue
			rm -v "$queueFile"
			addToLog "INFO" "Queue Job Success" "Command in queue '$queueFileData' has succeeded."
		else
			addToLog "ERROR" "Queue Job Failed" "Command in queue '$queueFileData' has failed."
			# failed jobs should be moved into the failed job directory and ignored from then on
			cp -v "$queueFile" "/var/cache/2web/queue/failed/"
			# remove the orignal command file to prevent the queue from trying to run a failed job again
			rm -v "$queueFile"
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
	# process all the jobs found in the queue system
	# - multi queue job queue takes into account the single job queue as a job in the multi queue

	# check for parallel processing and count the cpus
	totalCPUS=$(cpuCount)

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
	# cleanup queue lock files
	rm -v "/var/cache/2web/queue/multi.active"
	rm -v "/var/cache/2web/queue/single.active"
	rm -v "/var/cache/2web/queue/idle.active"
	# remove all the active process locks
	rm -v /var/cache/2web/queue/active/*.active
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
# Process CLI options
########################################################################
if [ "$1" == "-s" ] || [ "$1" == "--service" ] || [ "$1" == "service" ] ;then
	# launch the service to process jobs as they are added to the server
	overviewQueueService
elif [ "$1" == "-s" ] || [ "$1" == "--status" ] || [ "$1" == "status" ] ;then
	#
	activeJobs=$(find /var/cache/2web/queue/active/ -type f -name '*.active')
	failedJobs=$(find /var/cache/2web/queue/failed/ -type f -name '*.cmd')
	idleJobs=$(find /var/cache/2web/queue/idle/ -type f -name '*.cmd')
	multiJobs=$(find /var/cache/2web/queue/multi/ -type f -name '*.cmd')
	singleJobs=$(find /var/cache/2web/queue/single/ -type f -name '*.cmd')
	#
	activeJobCount=$(find /var/cache/2web/queue/active/ -type f -name '*.active'  | wc -l)
	failedJobCount=$(find /var/cache/2web/queue/failed/ -type f -name '*.cmd'  | wc -l)
	idleJobCount=$(find /var/cache/2web/queue/idle/ -type f -name '*.cmd'  | wc -l)
	multiJobCount=$(find /var/cache/2web/queue/multi/ -type f -name '*.cmd'  | wc -l)
	singleJobCount=$(find /var/cache/2web/queue/single/ -type f -name '*.cmd' | wc -l)

	drawCellLine 5
	# draw the headers
	drawCell "Active Jobs" 5
	drawCell "Failed Jobs" 5
	drawCell "Idle Jobs" 5
	drawCell "Multi Jobs" 5
	drawCell "Single Jobs" 5
	# create a new row
	echo
	drawCellLine 5
	# draw the data
	drawCell "$activeJobCount" 5
	drawCell "$failedJobCount" 5
	drawCell "$idleJobCount" 5
	drawCell "$multiJobCount" 5
	drawCell "$singleJobCount" 5
	echo
	drawCellLine 5
	# draw each of the unfinished jobs
	if [ $singleJobCount -gt 0 ];then
		drawLine
		echo "Single Job Queue"
		drawLine
		echo "$singleJobs" | while read -r jobPath;do
			if test -f "$jobPath";then
				echo "Job From: $jobPath"
				cat "$jobPath"
				echo
				echo
			fi
		done
	fi
	if [ $multiJobCount -gt 0 ];then
		drawLine
		echo "Multi Job Queue"
		drawLine
		echo "$multiJobs" | while read -r jobPath;do
			if test -f "$jobPath";then
				echo "Job From: $jobPath"
				cat "$jobPath"
				echo
				echo
			fi
		done
	fi
	if [ $idleJobCount -gt 0 ];then
		drawLine
		echo "Idle Queue"
		drawLine
		echo "$idleJobs" | while read -r jobPath;do
			if test -f "$jobPath";then
				echo "Job From: $jobPath"
				cat "$jobPath"
				echo
				echo
			fi
		done
	fi
	if [ $failedJobCount -gt 0 ];then
		drawLine
		echo "Failed Jobs"
		drawLine
		echo "$failedJobs" | while read -r jobPath;do
			if test -f "$jobPath";then
				echo "Job From: $jobPath"
				cat "$jobPath"
				echo
				echo
			fi
		done
	fi
elif [ "$1" == "-a" ] || [ "$1" == "--add" ] || [ "$1" == "add" ] ;then
	# add a job to the queue
	addJob "$2" "$3"
elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
	enableMod "queue2web"
elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
	disableMod "queue2web"
	# kill all remaining queues running on the server
	killall queue2web
else
	echo "+---------------------------------------------------------------------------------+"
	echo "| queue2web queue processing system for 2web                                      |"
	echo "+---------------------------------------------------------------------------------+"
	echo "- Use --service to launch the queue processing service."
	echo "- Add multithreaded jobs with 'queue2web --add multi \"testCommand\"'"
	echo "- Add one at a time jobs with 'queue2web --add single \"testCommand\"'"
	echo "- Jobs can be added to the queues by adding files with the .cmd extension to queue "
	echo "  directories"
	echo "- To add multithreaded jobs add files to /var/cache/2web/queue/multi/"
	echo "- To run one at a time jobs add files to /var/cache/2web/queue/single/"
	echo "- Unfinished jobs will survive reboots of the server"
	echo "- The service will be started automatically by cron"
fi
