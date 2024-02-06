#! /usr/bin/python3
########################################################################
# ai2web_q2a is a CLI tool to use stable diffusion text generation
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
# import libaries
import sys, os, json, hashlib, sqlite3, time
sys.path.append("/usr/share/2web/")
from python2webLib import h1, hr, file_get_contents
################################################################################
if "--help" in sys.argv:
	print("--help")
	print("\tDisplay this message")
	print("--one-prompt")
	print("\tThis must be called last. Everything after this will be used as the prompt for image generation.")
	print("--height")
	print("\theight of the generated image")
	print("--width")
	print("\twidth of the generated image")
	print("--output-dir")
	print("\tThe directory where generated files will be saved.")
	print("--debug-pipe-components")
	print("\tOutput generatd pipe details as json data")
	print("--sfw")
	print("\tThis will only generate images that the set model has determined are not NSFW")
	print("--offline")
	print("\tThis will disable downloading models from https://www.huggingface.co If used with --set-model the model must have previously been downloaded or a large error will occur.")
	print("--set-model")
	print("\tChoose a model from huggingface to use. Example: 'runwayml/stable-diffusion-v1-5'")
	print("\t- To find a list of available models go to https://huggingface.co/models?library=diffusers&sort=downloads")
	# exit after showing help
	exit()
################################################################################
def addToLog(tempFilePath, fileData):
	print(fileData)
	fileObj = open(tempFilePath, "a")
	fileObj.write(fileData)
	fileObj.close()
################################################################################
import torch
# stable diffusion image generating code
from diffusers import StableDiffusionPipeline
from transformers import AutoTokenizer, AutoModelForCausalLM, AutoModelForSeq2SeqLM, AutoModelForQuestionAnwsering


if "--prompt" in sys.argv:
	# get everything after one prompt and make that the prompt
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--prompt "
	# use all text after --prompt as the input prompt
	promptText = " ".join(sys.argv[(sys.argv.index("--prompt")+1):])
else:
	print("[ERROR]:You must give a prompt with --prompt")
	exit()

# loop and make x versions of the image prompt
if "--versions" in sys.argv:
	versions = sys.argv[(sys.argv.index("--versions")+1)]
	versions = int(versions)
else:
	versions = 1

# if the prompt is greater than 500 characters it can not be processed
if len(promptText) > 500:
	print("[ERROR]: This prompt is to long, Rewrite the prompt with less than 500 characters.")
	exit()

if "--output-dir" in sys.argv:
	outputDir = sys.argv[(sys.argv.index("--output-dir")+1)]

# replace spaces with underscores
tempPromptText = promptText
#baseFileTitle = tempPromptText.replace(" ", "_")

# create hex for base file name
baseFileTitle = hashlib.md5((promptText).encode('utf-8')).hexdigest()

versionNumber = 1

# fix the version number
if versionNumber < 10:
	tempVersionNumber = "00"+str(versionNumber)
elif versionNumber < 100:
	tempVersionNumber = "0"+str(versionNumber)

if "--output-dir" in sys.argv:
	fileTitle = os.path.join(outputDir, (baseFileTitle + "_v" + str(tempVersionNumber)))
	# create the output dir if it does not exist
	# - This will only happen from the CLI, the web interface creates its own directories to
	#   avoid file permission issues
	if not os.path.exists(outputDir):
		os.mkdir(outputDir)
else:
	fileTitle = baseFileTitle + "_v" + str(tempVersionNumber)

# if --set-model is called change the model
if "--set-model" in sys.argv:
	modelPath = sys.argv[(sys.argv.index("--set-model")+1)]
else:
	modelPath = "distilgpt2"

if "--offline" in sys.argv:
	use_only_local = True
else:
	use_only_local = False

# if --set-model is called change the model
if "--max-length" in sys.argv:
	maxLength = int(sys.argv[(sys.argv.index("--max-length")+1)])
else:
	maxLength = 100

# if --set-model is called change the model
if "--temp" in sys.argv:
	outputTemp = float(sys.argv[(sys.argv.index("--temp")+1)])
else:
	outputTemp = 0.7

# add  the log data #DEBUG
tempFilePath = (fileTitle+".log")

addToLog(tempFilePath,"Started log for prompt: "+promptText)

failures = 0
tempVersionNumber=1
while versions > 0:
	addToLog((fileTitle+".log"),("Versions Left: "+str(versions)))
	if "--output-dir" in sys.argv:
		# figure out the name and version
		while os.path.exists(fileTitle+".txt"):
			if versionNumber < 10:
				tempVersionNumber = "00"+str(versionNumber)
			elif versionNumber < 100:
				tempVersionNumber = "0"+str(versionNumber)
			fileTitle = os.path.join(outputDir, (baseFileTitle+"_v"+str(tempVersionNumber)))
			versionNumber += 1
			#print("File Title with outputDir: "+fileTitle)
	else:
		while os.path.exists(fileTitle+".txt"):
			if versionNumber < 10:
				tempVersionNumber = "00"+str(versionNumber)
			elif versionNumber < 100:
				tempVersionNumber = "0"+str(versionNumber)
			fileTitle = baseFileTitle+"_v"+str(tempVersionNumber)
			versionNumber += 1
			#print("File Title: "+fileTitle)

	addToLog((fileTitle+".log"), ("Creating response from prompt as: "+fileTitle+".txt\n"))

	# enable safety checker
	if "--gpu" in sys.argv:
		try:
			model = AutoModelForQuestionAnwsering.from_pretrained(modelPath, torch_dtype="auto", cache_dir="/var/cache/2web/downloads/ai/txt2txt/", local_files_only=use_only_local, low_cpu_mem_usage=True)
		except:
			model = AutoModelForQuestionAnwsering.from_pretrained(modelPath, torch_dtype="auto", cache_dir="/var/cache/2web/downloads/ai/txt2txt/", local_files_only=use_only_local, low_cpu_mem_usage=True, from_tf=True)
	else:
		try:
			model = AutoModelForQuestionAnwsering.from_pretrained(modelPath, cache_dir="/var/cache/2web/downloads/ai/txt2txt/", local_files_only=use_only_local, low_cpu_mem_usage=True)
		except:
			model = AutoModelForQuestionAnwsering.from_pretrained(modelPath, cache_dir="/var/cache/2web/downloads/ai/txt2txt/", local_files_only=use_only_local, low_cpu_mem_usage=True, from_tf=True)

	if "--debug-pipe-components" in sys.argv:
		print("Pipe components: "+str(pipe.components))

	addToLog((fileTitle+".log"), ("Building tokenizer...\n"))

	tokenizer = AutoTokenizer.from_pretrained(modelPath)

	addToLog((fileTitle+".log"), ("Checking for CUDA cores...\n"))

	# allow the forced use of GPU or CPU for processing
	if "--gpu" in sys.argv:
		model.to("cuda")
	elif "--cpu" in sys.argv:
		model.to("cpu")
	else:
		# by default check if cuda cores are avaiable and if so use them, otherwise use the cpu
		if torch.cuda.is_available():
			model.to("cuda")
		else:
			model.to("cpu")
	# generate image from prompt and get the generated image from the generated images array
	inputs = tokenizer(promptText, return_tensors="pt")

	input_ids = inputs.input_ids

	#gen_tokens = model.generate(input_ids, do_sample=True, temperature=0.9, max_length=100)
	gen_tokens = model.generate(input_ids, temperature=outputTemp, max_length=maxLength, do_sample=True)

	gen_text = tokenizer.batch_decode(gen_tokens)[0]

	print(gen_text)

	addToLog((fileTitle+".log"), ("Writing cached response \n"))
	# check if the output directory has been set
	tempFilePath = (fileTitle+".txt")

	fileObj = open(tempFilePath, "w")
	fileObj.write(gen_text)
	fileObj.close()

	# check if the version was created successfully
	if os.path.exists(tempFilePath):
		# mark another version completed
		versions -= 1
	else:
		# this is a failure to save the file
		print("ERROR: Could not save version correctly", tempFilePath)
		failures += 1
		if failures > 10:
			print("ERROR: Too many failures")
			exit()
