#! /usr/bin/python3
########################################################################
# ai2web_prompt is a CLI tool to use GPT4All language models
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
try:
	import gpt4all
except:
	print("ERROR: GPT4All is not installed ai2web_prompt needs missing dependency!")
	print("You can install it with 'ai2web --upgrade'")
	print("ai2web_prompt will now close...")
	exit()
########################################################################
# import libaries
import sys, os, json, hashlib, sqlite3, time
import datetime
# add custom lib path
sys.path.append("/usr/share/2web/")
from python2webLib import h1, hr, file_get_contents, file_put_contents
################################################################################
def ai2web_CLI_help():
	print("--help")
	print("\tDisplay this message")
	print("--rebuild-config")
	print("\tBuild a new default config from the GPT4All website")
	print("--list-json")
	print("\tFetch online json list of all GPT4All models")
	print("--list-installed")
	print("\tLists installed AI models")
	print("--list")
	print("\tList all installed AI models formatted for the CLI")
	print("--prompt")
	print("\tForce the prompt to open in interactive mode")
	print("--cache")
	print("\t")
	print("--one-prompt")
	print("\tThis will load everything after it as a prompt to give to the AI and return the anwser. This is for bash scripting with ai2web_prompt.")
	print("--json-output")
	print("\tCan only be use with --one-prompt before --one-prompt is called. Will output the conversation token as json.")
	print("--download")
	print("\tDownload one of the AI models shown in --list\n")
	h1("When creating prompts REMEMBER")
	print(" - Language models have a tendency to take things extremely literally")
	print(" - Descriptive words help in refining prompts")
	print(" - Punctuation in sentences helps the model understand meaning.")
	print(" - Mispelled words confuse the model")
	print(" - Incorrect grammer confuses the model")
	exit()
################################################################################
def ai2web_prompt_help():
	h1("When creating prompts REMEMBER")
	print(" - Language models have a tendency to take things extremely literally")
	print(" - Descriptive words help in refining prompts")
	print(" - Punctuation in sentences helps the model understand meaning.")
	print(" - Mispelled words confuse the model")
	print(" - Incorrect grammer confuses the model")
	h1("Prompt Commands")
	print(" - /exit into the prompt to close the program")
	print(" - /help display this help message")
	hr()
################################################################################
def hr():
	width=80
	print("_"*width)
################################################################################
def h1(bannerText):
	width=80
	edge = int(( width - len("  "+bannerText+"  ") ) / 2)
	print("#"*width)
	print("#"+(" "*edge)+" "+bannerText+" "+(" "*edge)+"#")
	print("#"*width)
################################################################################
def loadModel():
	if "--set-model" in sys.argv:
		activeModel = sys.argv[(sys.argv.index("--set-model")+1)]
		# if the model exists load it, if not check for download flat
		if os.path.exists("/var/cache/2web/downloads/ai/prompt/"+activeModel):
			# load the set model
			gptj = gpt4all.GPT4All(activeModel, "/var/cache/2web/downloads/ai/prompt/", allow_download=False)
			return gptj
		else:
			if "--download" in sys.argv:
				gptj = gpt4all.GPT4All(activeModel, "/var/cache/2web/downloads/ai/prompt/", allow_download=True)
				return gptj
			else:
				# download is disabled, do not download remote models automatically
				print("ERROR: Failed to load language model!")
				print("Could not load any lanuage models ai2web_prompt will now close.")
				print("Download default language model with 'ai2web_prompt --download.'")
				exit()
	else:
		# load list of default models to use
		defaultModels = list()
		defaultModels.append("orca-mini-3b.ggmlv3.q4_0.bin")
		defaultModels.append("ggml-gpt4all-j-v1.3-groovy.bin")
		defaultModels.append("ggml-gpt4all-l13b-snoozy.bin")
		defaultModels.append("ggml-mpt-7b-chat.bin")

		for defaultModel in defaultModels:
			if os.path.exists("/var/cache/2web/downloads/ai/prompt/"+defaultModel):
				# load up the found model and break the loop
				gptj = gpt4all.GPT4All(defaultModel, "/var/cache/2web/downloads/ai/prompt/", allow_download=False)
				# return the loaded model
				return gptj
			else:
				if "--download" in sys.argv:
					# load the model and attempt to download it from the default server
					gptj = gpt4all.GPT4All(defaultModel, "/var/cache/2web/downloads/ai/prompt/", allow_download=True)
					return gptj
				else:
					# download is disabled, do not download remote models automatically
					print("ERROR: Failed to load language model!")
					print("Could not load any lanuage models ai2web_prompt will now close.")
					print("Download default language model with 'ai2web_prompt --download.'")
					exit()
################################################################################
if "--rebuild-config" in sys.argv:
	prompt = False
	gptj = loadModel()
	# get json format of available models
	# get the list of dicts describing moddels
	onlineModels = gptj.list_models()
	fileObj = open("/etc/2web/ai/sources.cfg", "w")
	for onlineModel in onlineModels:
		fileObj.write("#"*80+"\n")
		fileObj.write("# Uncomment the below to download the language model"+"\n")
		fileObj.write("#"+ onlineModel["filename"]+"\n")
		fileObj.write("#\t Description: "+ onlineModel["description"]+"\n")
		fileObj.write("#\t  Model Size: "+ "MB "+str(int(onlineModel["filesize"]) / 1000000)+"\n")
		if "isDefault" in onlineModel.keys():
			fileObj.write("# Recommended 'Default' Model For Use by GPT4All\n")
	fileObj.close()
	exit()
################################################################################
# run prompt by default
prompt = True

if "--help" in sys.argv:
	prompt = False
	ai2web_CLI_help()

# snoozy is supposted to be better than groovy
if "--list-json" in sys.argv:
	prompt = False
	gptj = loadModel()
	# list the models in json format
	print(str(gptj.list_models()))
	exit()

# read the current active models
aiModels = os.scandir("/var/cache/2web/downloads/ai/prompt/")
installedAi = list()
for aiModel in aiModels:
	installedAi.append(aiModel.name)

if "--list-installed" in sys.argv:
	prompt = False
	for aiModel in installedAi:
		print("Installed AI Model: "+aiModel)
	exit()

if "--list-models" in sys.argv:
	prompt = False
	gptj = loadModel()
	# get json format of available models
	# get the list of dicts describing moddels
	onlineModels = gptj.list_models()

	for onlineModel in onlineModels:
		print("="*80)
		if onlineModel["filename"] in installedAi:
			print("This AI Model Is installed on this system :D")
			print("*"*80)
		print("\t", onlineModel["md5sum"])
		print("\t", onlineModel["filename"])
		print("\t", "MB "+str(int(onlineModel["filesize"]) / 1000000))
		print("\t", onlineModel["description"])
		if "isDefault" in onlineModel.keys():
			print("Recommended 'Default' Model For Use by GPT4All")
		print()
	exit()

if "--prompt" in sys.argv:
	prompt = True

if "--output-dir" in sys.argv:
	outputDir = sys.argv[(sys.argv.index("--output-dir")+1)]

if "--one-prompt" in sys.argv:
	# get everything after one prompt and make that the question
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--one-prompt "
	# use all text after --one-prompt as the input question
	question = tempQuestion[tempQuestion.find(argumentSearch)+len(argumentSearch):]
elif "--prompt-file" in sys.argv:
	# get the prompt data from a file
	promptFilePath = sys.argv[(sys.argv.index("--prompt-file")+1)]
	question = file_get_contents(promptFilePath)

samePromptBuffer = list()

# add the prompt to the the buffer to build the conversation token
gptj = loadModel()

# read the input temp given by the user
if "--deterministic" in sys.argv:
	# deterministic flag blocks temp and random
	temperature = 0.0
elif "--random" in sys.argv:
	# --random blocks --temp but is disabled by --deterministic
	temperature = 1.0
elif "--temp" in sys.argv:
	# set the temp manually must be in form of 1.0
	temperature = float(sys.argv[(sys.argv.index("--temp")+1)])
else:
	# set the temperature to almost deterministic
	temperature = 0.1

if "--versions" in sys.argv:
	versions = int(sys.argv[(sys.argv.index("--versions")+1)])
else:
	versions = 1

versionNumber = 1
tempVersionNumber = "001"
# replace spaces with underscores
tempPromptText = question

failures = 0
max_failures = versions * 100

if "--output-dir" in sys.argv:
	# rewrite the started time for each new created prompt
	file_put_contents(os.path.join(outputDir, "started.cfg"), datetime.datetime.now().strftime("%s"))
else:
	file_put_contents("started.cfg", datetime.datetime.now().strftime("%s"))

if "--set-model" in sys.argv:
	activeModel = sys.argv[(sys.argv.index("--set-model")+1)]

# loop the prompt until ctrl-c is hit
while versions > 0:
	# generate a new chat session for each version of the output
	gptj.chat_session()
	# list versions left in CLI
	print("Versions Left: ", versions)
	print("Failures : ", failures)
	print("Current Temp: ", temperature)
	# generate the anwser
	anwser = gptj.generate(prompt=question, max_tokens=7000, repeat_penalty=1.18, temp=temperature )

	anwserSum = hashlib.md5((anwser).encode('utf-8')).hexdigest()

	if "--output-dir" in sys.argv:
		fileTitle = os.path.join(outputDir, (anwserSum))
		print(fileTitle)
	else:
		fileTitle = anwserSum
		print(fileTitle)

	noOutputWarning  = "WARNING: No anwser could be returned by the language model to your input.\n"
	noOutputWarning += "Please change your input in order to get a response. Some ways to fix this.\n"
	noOutputWarning += " - Add puncuation to the query\n"
	noOutputWarning += " - Add more descriptive words to query\n"
	noOutputWarning += " - Fix mispelled words\n"
	noOutputWarning += " - Fix incorrect grammer\n\n"

	if type(anwser) == type(""):
		# print the anwser queue
		print(anwser)
	else:
		print(noOutputWarning)

	# check if the anwser has already been generated
	if os.path.exists(fileTitle+".txt"):
		# if the anwser has been generated generate a votes file and increment it by one
		if os.path.exists(fileTitle+".votes"):
			# load the existing votes file and convert it to a int
			currentVotes = file_get_contents(fileTitle+".votes")
			if currentVotes == "":
				currentVotes = 1
			else:
				# convert value in file to interger
				currentVotes = int(currentVotes)
			# incrent votes
			currentVotes += 1
			# write new tally of votes
			file_put_contents((fileTitle+".votes"), str(currentVotes))
		else:
			# create a new votes file with one vote
			file_put_contents((fileTitle+".votes"), "1")
		# for every failure increase the temperature to generate more random anwsers
		temperature += 0.1
		# a vote counts as a failure to generate a new version
		failures += 1
	else:
		# if the anwser is greater than zero characters long
		# - If the response is a non response ignore it
		if (len(anwser) > 0):
			# remove one completed unique version
			versions -= 1
			#
			print("Writing file to ", fileTitle+".txt")
			# save the output as a cache file since one does not exist
			fileObject = open((fileTitle+".txt"), "w")
			# write the anwser
			fileObject.write(anwser)
			# write the llm used to anwser the question
			fileObject.write("\n\nLLM: "+activeModel+"\n")
			# close the file
			fileObject.close()
		else:
			# for every failure increase the temperature to generate more random anwsers
			temperature += 0.1
			failures += 1

	# max out the temp value at 1
	if temperature >= 1:
		temperature = 1.0

	# if failures exceeds max_failures exit out the program
	if failures > max_failures:
		print("ERROR: FAILED OUT OF PROCESSING, more than ", max_failures, " failures to anwser prompt!")
		break
# increment the finished versions
if "--output-dir" in sys.argv:
	finishedPath = os.path.join(outputDir, "finished.cfg")
else:
	finishedPath = "finished.cfg"
# set the finished number
finished = 1
# look for existing file
if os.path.exists(finishedPath):
	fileData = file_get_contents(finishedPath)
	try:
		fileData = int(fileData)
		# combine the new finished to the old ones
		finished += fileData
	except:
		# somehow everything failed
		print("finished = ", finished)
# write the finished count to a file
file_put_contents(finishedPath, str(finished))

# store failures of the prompt
if failures > 0:
	fileData = ""
	#
	if "--output-dir" in sys.argv:
		failurePath = os.path.join(outputDir, "failures.cfg")
	else:
		failurePath = "failures.cfg"
	# look for existing file
	if os.path.exists(failurePath):
		fileData = file_get_contents(failurePath)
		try:
			fileData = int(fileData)
			# combine the new failures to the old ones
			failures += fileData
		except:
			# somehow everything failed
			print("Failures = ", failures)
	# write the failures to a file
	file_put_contents(failurePath, str(failures))

exit()
