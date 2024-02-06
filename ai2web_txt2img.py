#! /usr/bin/python3
########################################################################
# ai2web_image is a CLI tool to use stable diffusion language models
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
import gc
sys.path.append("/usr/share/2web/")
from python2webLib import h1, hr, file_get_contents
################################################################################
if "--help" in sys.argv:
	print("--help")
	print("\tDisplay this message")
	print("--prompt")
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
import torch
# stable diffusion image generating code
#from diffusers import StableDiffusionPipeline
from diffusers import DiffusionPipeline

# check for download argument and if given only download the model and exit
if "--download-model" in sys.argv:
	modelPath = sys.argv[(sys.argv.index("--download-model")+1)]
	# load the model, download if missing
	DiffusionPipeline.from_pretrained(modelPath, cache_dir="/var/cache/2web/downloads/ai/txt2img/")
	gc.collect()
	exit()

if "--list-negative-prompts" in sys.argv:
	h1("Looking for default prompts in: /etc/2web/ai/negative_prompts/")
	# set the default negative prompt created by stable diffusion
	discoveredPrompts = os.scandir("/etc/2web/ai/negative_prompts/")
	for negativePromptBase in discoveredPrompts:
		h1(str(negativePromptBase))
		print(file_get_contents(os.path.join("/etc/2web/ai/negative_prompts/",negativePromptBase)))
	exit()

# if --set-model is called change the model
if "--set-model" in sys.argv:
	modelPath = sys.argv[(sys.argv.index("--set-model")+1)]
else:
	modelPath = "runwayml/stable-diffusion-v1-5"

if "--sfw" in sys.argv:
	# enable safety checker
	safetyCheck=True
else:
	# the safety checker is disabled by default
	safetyCheck=None

if "--base-negative-prompt" in sys.argv:
	baseNegativePrompt = sys.argv[(sys.argv.index("--base-negative-prompt")+1)]
	baseNegativePromptPath = os.path.join("/etc/2web/ai/negative_prompts/",baseNegativePrompt)

	if os.path.exists(baseNegativePromptPath):
		# the negative prompt exists load it
		negativePromptText = file_get_contents(baseNegativePromptPath)
	elif os.path.exists(baseNegativePromptPath+".cfg"):
		# the negative prompt exists but no extension was included
		negativePromptText = file_get_contents(baseNegativePromptPath+".cfg")
	else:
		print("ERROR: The chosen --base-negative-prompt could not be loaded.")
		exit()
else:
	negativePromptText = file_get_contents("/etc/2web/ai/negative_prompts/default.cfg")

if "--prompt" in sys.argv:
	# get everything after one prompt and make that the prompt
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--prompt "
	# use all text after --prompt as the input prompt
	#promptText = " ".join(sys.argv[(sys.argv.index("--prompt")+1):])
	promptText = " ".join(sys.argv[(sys.argv.index("--prompt")+1):])
elif "--prompt-file" in sys.argv:
	# read the prompt text from a file
	promptFile = sys.argv[(sys.argv.index("--prompt-file")+1)]
	promptText = file_get_contents(promptFile)
else:
	print("[ERROR]:You must give a prompt with --prompt")
	exit()

# custom negative prompts
if "--negative-prompt" in sys.argv:
	# get everything after one prompt and make that the prompt
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--negative-prompt "
	# get value of negative prompt, use quotes for multi line
	negativePromptText = sys.argv[(sys.argv.index("--negative-prompt")+1)]
elif "--negative-prompt-file" in sys.argv:
	# read the prompt text from a file
	negativePromptFile = sys.argv[(sys.argv.index("--negative-prompt-file")+1)]
	negativePromptText = file_get_contents(negativePromptFile)
elif "--negative-prompt-plus" in sys.argv:
	# negative prompt that adds to the default negative prompts
	negativePromptText += "," + sys.argv[(sys.argv.index("--negative-prompt-plus")+1)]

# loop and make x versions of the image prompt
if "--versions" in sys.argv:
	versions = sys.argv[(sys.argv.index("--versions")+1)]
	versions = int(versions)
else:
	versions = 1

if "--height" in sys.argv:
	imageHeight = sys.argv[(sys.argv.index("--height")+1)]
else:
	imageHeight = 512

if "--width" in sys.argv:
	imageWidth = sys.argv[(sys.argv.index("--width")+1)]
else:
	imageWidth = 512

# if the prompt is greater than 120 characters exit with error
if len(promptText) > 120:
	print("[ERROR]: This prompt is to long, Rewrite the prompt with less than 120 characters.")
	exit()

if "--output-dir" in sys.argv:
	outputDir = sys.argv[(sys.argv.index("--output-dir")+1)]
	# write the log file
	#fileObject=open(os.path.join(outputDir, "data.log"),"w")
	#fileObject.write(str(sys.argv)+"\n")
	#fileObject.close()

# replace spaces with underscores
tempPromptText = promptText
baseFileTitle = tempPromptText.replace(" ", "_")

if len(baseFileTitle) > 100:
	# if the file title is to long replace it with a hash sum of the input prompt
	baseFileTitle = hashlib.md5((baseFileTitle).encode('utf-8')).hexdigest()

versionNumber = 1
tempVersionNumber = "001"

if "--output-dir" in sys.argv:
	fileTitle = os.path.join(outputDir, (baseFileTitle + "_v" + str(tempVersionNumber)))
else:
	fileTitle = baseFileTitle + "_v" + str(tempVersionNumber)

failures = 0
tempVersionNumber=1
while versions > 0:
	print("Versions Left: ", versions)
	if "--output-dir" in sys.argv:
		print(fileTitle)
		# figure out the name and version
		while os.path.exists(fileTitle+".png"):
			if versionNumber < 10:
				tempVersionNumber = "00"+str(versionNumber)
			elif versionNumber < 100:
				tempVersionNumber = "0"+str(versionNumber)
			fileTitle = os.path.join(outputDir, (baseFileTitle+"_v"+str(tempVersionNumber)))
			versionNumber += 1
			#print("File Title with outputDir: "+fileTitle)
	else:
		print(fileTitle)
		while os.path.exists(fileTitle+".png"):
			if versionNumber < 10:
				tempVersionNumber = "00"+str(versionNumber)
			elif versionNumber < 100:
				tempVersionNumber = "0"+str(versionNumber)
			fileTitle = baseFileTitle+"_v"+str(tempVersionNumber)
			versionNumber += 1
			#print("File Title: "+fileTitle)

	deviceToUse = "cpu"

	if "--gpu" in sys.argv:
		deviceToUse = "gpu"
	elif "--cpu" in sys.argv:
		deviceToUse = "cpu"
	else:
		if torch.cuda.is_available():
			deviceToUse = "gpu"
		else:
			deviceToUse = "cpu"

	# local_files_only should always be true to prevent unwanted internet connections
	if deviceToUse == "cpu":
		pipe = DiffusionPipeline.from_pretrained(modelPath, safety_checker=safetyCheck, cache_dir="/var/cache/2web/downloads/ai/txt2img/", local_files_only=True)
	elif deviceToUse == "gpu":
		pipe = DiffusionPipeline.from_pretrained(modelPath, safety_checker=safetyCheck, torch_dtype=torch.float16, cache_dir="/var/cache/2web/downloads/ai/txt2img/", local_files_only=True)

	if "--debug-pipe-components" in sys.argv:
		print("Pipe components: "+str(pipe.components))

	# allow the forced use of GPU or CPU for processing
	if "--gpu" in sys.argv:
		pipe.to("cuda")
	elif "--cpu" in sys.argv:
		pipe.to("cpu")
	else:
		# by default check if cuda cores are avaiable and if so use them, otherwise use the cpu
		if torch.cuda.is_available():
			pipe.to("cuda")
		else:
			pipe.to("cpu")
	# generate image from prompt and get the generated image from the generated images array
	image = pipe(prompt=promptText, height=int(imageHeight), width=int(imageWidth), negative_prompt=negativePromptText).images[0]

	# check if the output directory has been set
	if "--output-dir" in sys.argv:
		# save the created image to the specified output directory
		tempFilePath = (os.path.join(outputDir, (fileTitle+".png")))
		tempFileModelPath = (os.path.join(outputDir, (fileTitle+".model")))
	else:
		tempFilePath = (fileTitle+".png")
		tempFileModelPath = (fileTitle+".model")

	# save the image
	image.save(tempFilePath)
	# save the model used to generate this image
	tempModelFileObject = open(tempFileModelPath, "w")
	tempModelFileObject.write(modelPath)
	tempModelFileObject.close()


	# check if the version was created successfully
	if os.path.exists(tempFilePath):
		# mark another version completed
		versions -= 1
		# reset failures so they must be consecutive to fail out
		failures = 0
	else:
		# this is a failure to save the file
		print("ERROR: Could not save version correctly", tempFilePath)
		failures += 1
		if failures > 10:
			print("ERROR: Too many failures")
			gc.collect()
			exit()
# collect garbage before exit
gc.collect()
