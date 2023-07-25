#! /usr/bin/python3
########################################################################
# ai2web_txt2txt is a CLI tool to use stable diffusion text generation
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
import torch
# stable diffusion image generating code
from diffusers import StableDiffusionPipeline

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
baseFileTitle = tempPromptText.replace(" ", "_")

# create hex for base file name
baseFileTitle = hashlib.md5((baseFileTitle).encode('utf-8')).hexdigest()

versionNumber = 1

# fix the version number
if versionNumber < 10:
	tempVersionNumber = "00"+str(versionNumber)
elif versionNumber < 100:
	tempVersionNumber = "0"+str(versionNumber)

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

	print("Creating image from prompt: "+fileTitle+".png")

	# if --set-model is called change the model
	if "--set-model" in sys.argv:
		modelPath = sys.argv[(sys.argv.index("--set-model")+1)]
	else:
		modelPath = "runwayml/stable-diffusion-v1-5"

	if "--offline" in sys.argv:
		use_only_local = True
	else:
		use_only_local = False

	# enable safety checker
	if "--sfw" in sys.argv:
		if "--gpu" in sys.argv:
			pipe = StableDiffusionPipeline.from_pretrained(modelPath, torch_dtype=torch.float16, cache_dir="/var/cache/2web/downloads_ai_image/", local_files_only=use_only_local)
		else:
			pipe = StableDiffusionPipeline.from_pretrained(modelPath, cache_dir="/var/cache/2web/downloads_ai_image/", local_files_only=use_only_local)
	else:
		# the safety checker is disabled by default
		if "--gpu" in sys.argv:
			pipe = StableDiffusionPipeline.from_pretrained(modelPath, torch_dtype=torch.float16, safety_checker=None, cache_dir="/var/cache/2web/downloads_ai_image/", local_files_only=use_only_local)
		else:
			pipe = StableDiffusionPipeline.from_pretrained(modelPath, safety_checker=None, cache_dir="/var/cache/2web/downloads_ai_image/", local_files_only=use_only_local)

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
	else:
		tempFilePath = (fileTitle+".png")

	image.save(tempFilePath)

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
