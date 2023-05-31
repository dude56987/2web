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
import sys, os, json, hashlib, sqlite3
################################################################################
def ai2web_CLI_help():
	print("--help")
	print("\tDisplay this message")
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
	print("--list-personas")
	print("\tList all available personas installed.")
	print("--load-persona")
	print("\tLoad up a persona file for this conversation")
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
def getActiveModel():
	if "--set-model" in sys.argv:
		# return the set model
		return sys.argv[(sys.argv.index("--set-model")+1)]
	else:
		# load list of default models to use
		defaultModels = list()
		defaultModels.append("ggml-gpt4all-j-v1.3-groovy.bin")
		defaultModels.append("ggml-gpt4all-l13b-snoozy.bin")
		defaultModels.append("ggml-mpt-7b-chat.bin")

		for defaultModel in defaultModels:
			if os.path.exists("/var/cache/2web/downloads_ai/"+defaultModel):
				return defaultModel
	# if no model could be loaded return False
	return False
################################################################################
def loadModel():
	if "--set-model" in sys.argv:
		activeModel = sys.argv[(sys.argv.index("--set-model")+1)]
		# if the model exists load it, if not check for download flat
		if os.path.exists("/var/cache/2web/downloads_ai/"+activeModel):
			# load the set model
			gptj = gpt4all.GPT4All(activeModel, "/var/cache/2web/downloads_ai/", allow_download=False)
			return gptj
		else:
			if "--download" in sys.argv:
				gptj = gpt4all.GPT4All(activeModel, "/var/cache/2web/downloads_ai/", allow_download=True)
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
		defaultModels.append("ggml-gpt4all-j-v1.3-groovy.bin")
		defaultModels.append("ggml-gpt4all-l13b-snoozy.bin")
		defaultModels.append("ggml-mpt-7b-chat.bin")

		for defaultModel in defaultModels:
			if os.path.exists("/var/cache/2web/downloads_ai/"+defaultModel):
				# load up the found model and break the loop
				gptj = gpt4all.GPT4All(defaultModel, "/var/cache/2web/downloads_ai/", allow_download=False)
				# return the loaded model
				return gptj
			else:
				if "--download" in sys.argv:
					# load the model and attempt to download it from the default server
					gptj = gpt4all.GPT4All(defaultModel, "/var/cache/2web/downloads_ai/", allow_download=True)
					return gptj
				else:
					# download is disabled, do not download remote models automatically
					print("ERROR: Failed to load language model!")
					print("Could not load any lanuage models ai2web_prompt will now close.")
					print("Download default language model with 'ai2web_prompt --download.'")
					exit()
################################################################################
def printConvo(convoToken):
	"""
	Print the contents of a convo token
	"""
	for line in convoToken:
		# read each part of the conversation
		print(line['role']+"\n"+("_"*80)+"\n"+line['content'])
################################################################################
def loadDatabase(databasePath):
	"""
	Load a sql database run the statement and return a list of all discovered values
	"""
	# if the database path does not exist
	if os.path.exists(databasePath):
		newDatabase = False
	else:
		newDatabase = True

	# open database with 120 seconds of wait time in queue before fail
	dbConnection = sqlite3.connect(databasePath, 120)
	dbCursor = dbConnection.cursor()

	if newDatabase:
		# build the new file
		dbCursor.execute("PRAGMA journal_mode=WAL;")
		dbCursor.execute("PRAGMA wal_autocheckpoint=0;")
		# build the base tables
		# - ROWID column is incremented in database to identify newest and oldest elements
		# build the anwsers table
		dbCursor.execute("create table \"anwsers\" (convoSum text primary key,convoToken text);")
		# build the question table
		dbCursor.execute("create table \"questions\" (convoSum text primary key,convoToken text,anwserSum text);")
		dbConnection.commit()

	return [dbConnection, dbCursor]
################################################################################
def databaseExecute(sqlQuery,databasePath="/var/cache/2web/web/ai/convos.db"):
	"""
	Load a sql database run the statement and return a list of all discovered values
	"""
	databaseObject = loadDatabase(databasePath)
	dbConnection = databaseObject[0]
	dbCursor = databaseObject[1]
	#print("sql query : "+str(sqlQuery))
	# run sql statement
	foundTokens = dbCursor.execute(sqlQuery)
	#print(foundTokens)
	#print("foundTokens : "+str(foundTokens))
	#foundTokens = foundTokens.fetchall()
	#print("foundTokens fetched : "+str(foundTokens))
	convoData = list()
	# get all return values of sql statement
	for row in foundTokens.fetchall():
		# if the entry returned correct this means the convo
		convoData.append(row)
	# return false if no values were returned
	#if len(convoData) == 0:
	#	return False
	#print("foundTokens fetched : "+str(foundTokens))
	# commit changes to the database
	dbConnection.commit()
	#print("ConvoData : "+str(convoData))
	#close database connection
	dbConnection.close()
	# return convo data from database
	return convoData
	#return foundTokens
################################################################################
def readConvo(convoToken,depth=1):
	"""
	Read a convo token and return the generated anwser token. Cache all convos in a sqlite3 database to allow web interfaces to acccess it.

	"""
	if depth >= 10:
		return False
	# database model is used for hash sum generation, to make anwsers model specific
	databaseModel = getActiveModel()
	# generate the sum using both values
	convoSum = hashlib.md5((databaseModel+str(convoToken)).encode('utf-8')).hexdigest()
	# build the anwsers table

	## if the database path does not exist
	#if os.path.exists(databasePath):
	#	newDatabase = False
	#else:
	#	newDatabase = True

	# load up the database file, with timeout of 120 seconds to allow concurrency
	#dbConnection = sqlite3.connect(databasePath, 120)
	# build the cursor object for interacting with the database
	#dbCursor = dbConnection.cursor()
	#if newDatabase:
	#	# build the new file
	#	dbCursor.execute("PRAGMA journal_mode=WAL;")
	#	dbCursor.execute("PRAGMA wal_autocheckpoint=1;")
	#	# build the base tables
	#	# - ROWID column is incremented in database to identify newest and oldest elements
	#	# build the anwsers table
	#	dbCursor.execute("create table \"anwsers\" (convoSum text primary key,convoToken text);")
	#	# build the question table
	#	dbCursor.execute("create table \"questions\" (convoSum text primary key,convoToken text,anwserSum text);")
	## search for existing convo data
	convoData = False
	# take the input token and check the existing database for that token value
	#foundTokens = dbCursor.execute("select * from questions where convoSum = '"+convoSum+"';")

	foundTokens = databaseExecute("select * from questions where convoSum = '"+convoSum+"';")
	#print(foundTokens)
	# if convo data was found load it
	if len(foundTokens) > 0:
		#convoData (covoSum, covoToken, anwserSum)
		#print(convoData)
		#print(convoData[0])
		#print(convoData[1])
		#print(convoData[2])
		#printConvo(convoData['convoToken'])
		#print("select * from anwsers where convoSum = '"+convoData[2]+"';")
		anwserData = databaseExecute("select * from anwsers where convoSum = '"+foundTokens[0][2]+"';")
		#print("Anwser data : "+str(anwserData))
		tempData = list()
		for row in anwserData:
			tempData = row
			break
		# if the anwserdata has no length then no anwser could be formed
		if len(anwserData) > 1:
			return False
		else:
			anwserData = tempData
		#print("Anwser data after clean : "+str(anwserData))

		#print("Anwser data after 2 clean : "+str(anwserData))

		#dbConnection.close()
		#print(anwserData)
		#print(type(anwserData))
		#print(anwserData)
		anwserData = json.loads(anwserData[1])
		#print(anwserData)
		# should load an array
		return anwserData
		#for row in anwserData.fetchall():
		#print(convoData['convoToken'])
		#print(convoData['anwserSum'])
	else:
		convoJson = json.dumps(convoToken)
		databaseExecute("replace into questions(convoSum, convoToken, anwserSum) values('"+convoSum+"','"+convoJson+"','UNANWSERED');")
		# load up the prompt and get the anwser token
		gptj = loadModel()
		anwserToken = gptj.chat_completion(convoToken, True, True, False)
		#print("Anwser Token before clean : "+str(anwserToken))#debug
		# read the message dict from the anwser token
		anwserToken = anwserToken["choices"]
		#print("Anwser Token after clean choices : "+str(anwserToken))#debug
		#anwserToken = anwserToken["message"]
		#print("Anwser Token after clean message : "+str(anwserToken))#debug
		tempToken = list()
		# read all anwsers into array
		for anwserDict in anwserToken:
			#print("Anwser dict message : "+str(anwserDict["message"]))#debug
			# build the anwser token, remove single quotes that break sql statements
			tempToken.append({"role":anwserDict["message"]['role'],"content":(anwserDict["message"]['content'].replace("'",'`'))})

			# check the anwserToken and read the last character of the string. If it is not a puncuation automatically ask to continue
			tempAnwserText = anwserDict["message"]['content'].replace("'",'`')
			tempAnwserText = tempAnwserText[len(tempAnwserText)-1]
			if tempAnwserText not in [".","!","?"]:
				# if the anwser does not contain punctuation then automatically ask AI to continue
				readConvo(convoToken + tempToken + [{"role":"user","content":"Continue"}],(depth+1))
			# this should only run once
			break
		#print("Temp Token : "+str(tempToken))#debug
		# replace anwser token with temp token
		anwserToken = tempToken
		#print("Anwser Token after clean replace : "+str(anwserToken))#debug
		# add the orignal message to the anwser token for storage
		anwserToken = convoToken + anwserToken
		#print("Anwser Token after adding convo token : "+str(anwserToken))#debug
		# build a sum for the anwser token
		anwserSum = hashlib.md5((databaseModel+str(anwserToken)).encode('utf-8')).hexdigest()
		#print("Anwser sum : "+str(anwserSum))#debug
		# the anwsertoken has been generated now, build the anwser token in the database and read it
		anwserJson = json.dumps(anwserToken)
		#print("Anwser json : "+str(anwserJson))#debug
		#print("replace into anwsers(convoSum, convoToken) values('"+anwserSum+"','"+anwserJson+"');")
		databaseExecute("replace into anwsers(convoSum, convoToken) values('"+anwserSum+"','"+anwserJson+"');")
		#print("replace into questions(convoSum, convoToken, anwserSum) values('"+convoSum+"','"+convoJson+"','"+anwserSum+"');")
		# add this convo token to the datbase
		databaseExecute("replace into questions(convoSum, convoToken, anwserSum) values('"+convoSum+"','"+convoJson+"','"+anwserSum+"');")
		# commit changes to database
		#dbConnection.commit()
		# close the database
		#dbConnection.close()
		# print contents of the stored anwser token
		#printConvo(anwserToken)
		return anwserToken

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

# read the current active models
aiModels = os.scandir("/var/cache/2web/downloads_ai/")
installedAi = list()
for aiModel in aiModels:
	installedAi.append(aiModel.name)
	#print("Installed AI Model: "+aiModel.name)

if "--list-installed" in sys.argv:
	prompt = False
	for aiModel in installedAi:
		print("Installed AI Model: "+aiModel)

if "--list" in sys.argv:
	prompt = False
	gptj = loadModel()
	# get json format of available models
	# get the list of dicts describing moddels
	onlineModels = gptj.list_models()
	for onlineModel in onlineModels:
		print("="*80)
		if onlineModel["filename"] in installedAi:
			print("*"*80)
			print("This AI Model Is installed on this system :D")
			print("*"*80)
		print(onlineModel["md5sum"])
		print(onlineModel["filename"])
		print("MB "+str(int(onlineModel["filesize"]) / 1000000))
		print(onlineModel["description"])
		if "isDefault" in onlineModel.keys():
			print("Recommended Model For Use")
		print()

if "--prompt" in sys.argv:
	prompt = True

if "--download" in sys.argv:
	prompt = False
	gptj = loadModel()
	gptj.retrieve_model("ggml-gpt4all-j-v1.3-groovy.bin", "/var/cache/2web/downloads_ai/", True)

if "--one-prompt" in sys.argv:
	# get everything after one prompt and make that the question
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--one-prompt "
	# use all text after --one-prompt as the input question
	question = tempQuestion[tempQuestion.find(argumentSearch)+len(argumentSearch):]

if "--list-personas" in sys.argv:
	prompt=False
	aiModels = os.scandir("/etc/2web/ai/personas/")
	#print(aiModels)
	print("="*80)
	print("Discovered AI Personas")
	print("="*80)
	installedAi = list()
	for aiModel in aiModels:
		# print the list of installed ai personas
		print("Core ID:'"+aiModel.name.replace(".cfg","")+"'")
		#installedAi.append(aiModel.name)
	# print the list of installed ai personas
	#print(installedAi)
	print("="*80)
	print("Use 'ai2web_prompt --load-persona \"personaName\"' to use a specific persona for prompts.")
	print("="*80)

samePromptBuffer=list()
# preload a persona from the prompt
if "--load-persona" in sys.argv:
	# get item after the --load-persona as the name of the persona to use
	persona = sys.argv[(sys.argv.index("--load-persona")+1)]
	personaText = str()
	if os.path.exists("/etc/2web/ai/personas/"+persona+".cfg"):
		# preload the prompt with the persona file
		fileObject = open("/etc/2web/ai/personas/"+persona+".cfg","r")
		for line in fileObject:
			#personaText += fileObject.read()
			personaText += line
		#print("Persona = "+str(personaText))
		samePromptBuffer.append({"role": "user", "content": personaText.replace("'","`")})
else:
	persona = str()

if "--input-json" in sys.argv:
	# load input json string, this is for loading previous conversations saved with --output-json
	inputJson = json.loads(sys.argv[(sys.argv.index("--input-json")+1)])
	# add input json to the same prompt buffer after persona is loaded
	for tempJsonLine in inputJson:
		samePromptBuffer.append(tempJsonLine)

if "--input-token" in sys.argv:
	# take the input of a convoSum, search for that convosum linking to that convoToken, load that convo json into the samePromptBuffer
	inputToken = (sys.argv[(sys.argv.index("--input-token")+1)])
	#print("select * from \"anwsers\" where convoSum = '"+inputToken+"';")
	inputData = databaseExecute("select * from \"anwsers\" where convoSum = '"+inputToken+"';")
	#print("inputData="+str(inputData))
	for tempLine in inputData:
		#print("tempLine="+str(tempLine))
		#print("tempLine 1 ="+str(tempLine[1]))
		samePromptBuffer += json.loads(tempLine[1])

# if the prompt is set to active then load the model
if prompt:
	if "--cache" in sys.argv:
		# build the sum from a temp prompt, this is to make --one-prompt load instantly when conversation token is cached
		tempPromptBuffer = samePromptBuffer
		tempPromptBuffer.append({"role": "user", "content": question})
		tempSum = hashlib.md5(str(tempPromptBuffer).encode('utf-8')).hexdigest()
		# no model should be loaded if the file is already cached
		if os.path.exists("/var/cache/2web/web/search/ai_"+tempSum+".index"):
			# this file already exists in the cache do not load the lang model
			pass
		else:
			# if this is not cached load the model before the prompt
			#gptj = loadModel()
			pass
	else:
		#gptj = loadModel()
		pass

## load and execute the persona before executing the question
#if "--load-persona" in sys.argv:
#	#anwser = gptj.chat_completion(samePromptBuffer, True, True, False)
#	anwser = readConvo(samePromptBuffer)
#	# run the persona and add it to the message buffer
#	for anwserDict in anwser:
#		samePromptBuffer.append(anwserDict)
#		if "--one-prompt" in sys.argv:
#			print(anwserDict['content'])
#		else:
#			print("🗩H:'"+anwserDict['content']+"'")

if "--one-prompt" not in sys.argv:
	h1("Starting Prompt")
	print("- Use /help for more prompt commands.")
	print("- Use /exit to close the program.")
	print("- Use /new to start a new conversation.")
	hr()
# loop the prompt until ctrl-c is hit
while prompt:

	if "--one-prompt" not in sys.argv:
		question = input("🗨️ -->")

	if question[0] == "/":
		if question == "/exit":
			print("Closing GPT4All Model...")
			exit()
		elif question == "/help":
			ai2web_prompt_help()
		elif question == "/new":
			# reset the prompt buffer for a new conversation
			samePromptBuffer = list()
		else:
			print("WARNING: Could not interpert prompt command / If you are attempting to prompt the language model your prompt must not start with a forward slash.")
	else:
		# if this is not a prompt command it needs to be processed by model
		if "--one-prompt" in sys.argv:
			pass
		else:
			# print the symbol to repsenent a active processing job on the prompt
			print("💬")

		# add the question to the same prompt buffer, e.g. this conversation
		samePromptBuffer.append({"role": "user", "content": question})
		anwserNotCached = True
		if "--cache" in sys.argv:
			if "--set-model" in sys.argv:
				activeModel = sys.argv[(sys.argv.index("--set-model")+1)]
			else:
				activeModel = "ggml-gpt4all-j-v1.3-groovy.bin"
			# - Cache must be ran inside of prompt loop after the question has been appended to the prompt buffer
			# get the sum of the query
			tempSum = hashlib.md5((str(samePromptBuffer)+activeModel).encode('utf-8')).hexdigest()
			if os.path.exists("/var/cache/2web/web/ai/"+tempSum+".index"):
				anwserNotCached = False
				anwser = str()
				fileObject = open("/var/cache/2web/web/ai/"+tempSum+".index", "r")
				for line in fileObject:
					anwser += fileObject.read()
				fileObject.close()
		if anwserNotCached:
			# if the anwser was not cached then load the given anwser
			# add the prompt to the the buffer to build the conversation token
			if "--raw" in sys.argv:
				# --raw generates replys based on the input only not the totality of the conversation, like goldfish mode
				# generate raw input output
				raw_anwser = gptj.generate(question)
				# rebuild the buffer because --raw generates a new conversation for every prompt
				samePromptBuffer = [{"role": "user", "content": question}]
				# format the anwser provided by raw output command for use in conversation token
				anwser = samePromptBuffer + [{'role': 'assistant', 'content': raw_anwser}]
			else:
				# get the anwser to the question, these flags are listed below in order
				# - include prompt header
				# - include prompt footer
				# - show terminal output text
				anwser = readConvo(samePromptBuffer)
				#anwser = gptj.chat_completion(samePromptBuffer, True, True, False)

		noOutputWarning  = "WARNING: No anwser could be returned by the language model to your input.\n"
		noOutputWarning += "Please change your input in order to get a response. Some ways to fix this.\n"
		noOutputWarning += " - Add puncuation to the query\n"
		noOutputWarning += " - Add more descriptive words to query\n"
		noOutputWarning += " - Fix mispelled words\n"
		noOutputWarning += " - Fix incorrect grammer\n\n"
		if "--one-prompt" in sys.argv:
			if type(anwser) == type(""):
				print(noOutputWarning)
			else:
				# print the anwser queue as json output
				for anwserDict in anwser:
					samePromptBuffer.append(anwserDict)
				outputDict = samePromptBuffer[len(samePromptBuffer)-1]
				print(outputDict['content'])
			# after printing the output exit the program
			exit()
		else:
			if type(anwser) == type(""):
				print(noOutputWarning)
			else:
				# add each of the anwsers, one prompt has no conversation permanence
				for anwserDict in anwser:
					samePromptBuffer.append(anwserDict)
				outputDict = samePromptBuffer[len(samePromptBuffer)-1]
				print("🗩H:'"+outputDict['content']+"'")

		if "--cache" in sys.argv:
			if os.path.exists("/var/cache/2web/web/ai/"+tempSum+".index"):
				# do nothing the cache file exists
				pass
			else:
				# save the output as a cache file since one does not exist
				fileObject = open("/var/cache/2web/web/ai/"+tempSum+".index", "w")
				fileObject.write(json.dumps(samePromptBuffer))
				fileObject.close()
exit()

