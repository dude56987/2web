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
	else
		# this is in error only single and multi queues are available
		exit
	fi
	# generate a timestamp at the start of the filename
	timestamp=$(date "+%s")
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
	addToLog "INFO" "Queue Starting Job " "Command started processing '$queueFileData'."
	bash "$queueFile"
	exitStatus=$?
	queueFileData="$(cat "$queueFile")"
	if [ 0 -eq $exitStatus ];then
		# remove the job from the queue
		rm -v "$queueFile"
		addToLog "INFO" "Queue Job Success" "Command in queue '$queueFileData' has succeeded."
	else
		addToLog "ERROR" "Queue Job Failed" "Command in queue '$queueFileData' has failed."
	fi

}
########################################################################
function processJobQueue(){
	# process all the jobs found in the queue system

	# check for parallel processing and count the cpus
	totalCPUS=$(cpuCount)
	# load up files in queue to be processed
	find /var/cache/2web/queue/multi/ -name "*.cmd" | sort | while read -r queueFile;do
		processThreadedJob "$queueFile" &
		waitQueue 0.5 "$totalCPUS"
	done
	blockQueue 1
	find /var/cache/2web/queue/single/ -name "*.cmd" | sort | while read -r queueFile;do
		# for each job in the single queue
		processThreadedJob "$queueFile"
		waitQueue 0.5 "1"
	done
	blockQueue 1
}
########################################################################
function queueService(){
	# lock the process so multuple instances of the service do not run at the same time
	lockProc "queue2web"
	# create the multi and single queues
	createDir "/var/cache/2web/queue/multi/"
	createDir "/var/cache/2web/queue/single/"
	# setup the timeout that will reset the service
	# every X minutes check the queue
	timeOut=$(( (60 * 3) ))
	# launch the service that will run in the background to process new jobs added to the queue
	while true;do
		# run the queue before setting up the watch service
		processJobQueue
		# wait for queue to activate
		inotifywait --csv --timeout "$timeOut" -r -e "MODIFY" -e "CREATE" "/var/cache/2web/queue/" | while read event;do
			# if any files are added to the queue launch the process jobs function
			processJobQueue
		done
	done
	# remove active state file
	if test -f /var/cache/2web/web/queue2web.active;then
		rm /var/cache/2web/web/queue2web.active
	fi
}
########################################################################
# Process CLI options
########################################################################
if [ "$1" == "-s" ] || [ "$1" == "--service" ] || [ "$1" == "service" ] ;then
	# launch the service to process jobs as they are added to the server
	queueService
elif [ "$1" == "-a" ] || [ "$1" == "--add" ] || [ "$1" == "add" ] ;then
	# add a job to the queue
	addJob "$2" "$3"
elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
	enableMod "queue2web"
elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
	disableMod "queue2web"
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
