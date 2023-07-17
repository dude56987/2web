#! /usr/bin/python3
########################################################################
# ai2web_image is a CLI tool to use stable diffusion language models
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
	print("\tChoose a model from huggingface to use. It must be a image to image model")
	print("\t- To find a list of available models go to https://huggingface.co/models?library=diffusers&sort=downloads")
	# exit after showing help
	exit()
################################################################################
def loadModel():
	if "--text-model" in sys.argv:
		activeModel = sys.argv[(sys.argv.index("--text-model")+1)]
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
		defaultModels.append("ggml-gpt4all-l13b-snoozy.bin")
		defaultModels.append("ggml-gpt4all-j-v1.3-groovy.bin")
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
import torch
import requests
import PIL
# stable diffusion image generating code
from diffusers import StableDiffusionInstructPix2PixPipeline,EulerAncestralDiscreteScheduler
################################################################################
if "--input-file" in sys.argv:
	url = sys.argv[(sys.argv.index("--input-file")+1)]
else:
	print("[ERROR]: No input file detected. Use --input-file to add a input file from the local system or a http or https address.")
	exit()
################################################################################
if "--list-negative-prompts" in sys.argv:
	h1("Looking for default prompts in: /etc/2web/ai/negative_prompts/")
	# set the default negative prompt created by stable diffusion
	discoveredPrompts = os.scandir("/etc/2web/ai/negative_prompts/")
	for negativePromptBase in discoveredPrompts:
		h1(str(negativePromptBase))
		print(file_get_contents(os.path.join("/etc/2web/ai/negative_prompts/",negativePromptBase)))
	exit()
################################################################################
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
################################################################################
if "--prompt" in sys.argv:
	# get everything after one prompt and make that the prompt
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--prompt "
	# use all text after --prompt as the input prompt
	promptText = sys.argv[(sys.argv.index("--prompt")+1)]
else:
	print("[ERROR]:You must give a prompt with --prompt")
	exit()
################################################################################
# custom negative prompts
if "--negative-prompt" in sys.argv:
	# get everything after one prompt and make that the prompt
	tempQuestion = " ".join(sys.argv)
	argumentSearch = "--negative-prompt "
	# get value of negative prompt, use quotes for multi line
	negativePromptText = sys.argv[(sys.argv.index("--negative-prompt")+1)]
elif "--negative-prompt-plus" in sys.argv:
	# negative prompt that adds to the default negative prompts
	negativePromptText += "," + sys.argv[(sys.argv.index("--negative-prompt-plus")+1)]
################################################################################
# loop and make x versions of the image prompt
if "--versions" in sys.argv:
	versions = sys.argv[(sys.argv.index("--versions")+1)]
	versions = int(versions)

if "--height" in sys.argv:
	imageHeight = sys.argv[(sys.argv.index("--height")+1)]
else:
	imageHeight = 512

if "--width" in sys.argv:
	imageWidth = sys.argv[(sys.argv.index("--width")+1)]
else:
	imageWidth = 512

# if the prompt is greater than 120 characters have the gpt4all ai rewrite it
if len(promptText) > 120:
	print("[ERROR]: This prompt is to long, Rewrite the prompt with less than 120 characters.")
	exit()

if "--output-dir" in sys.argv:
	outputDir = sys.argv[(sys.argv.index("--output-dir")+1)]

while versions > 0:
	# figure out the version number
	fileTitle = promptText.replace(" ", "_")

	if len(fileTitle) > 100:
		# if the file title is to long replace it with a hash sum of the input prompt
		# - use the url and the prompt message to generate the sum for the path of the image being edited
		fileTitle = hashlib.md5((url).encode('utf-8')).hexdigest()

	versionNumber = 1

	if "--output-dir" in sys.argv:
		# figure out the name and version
		while os.path.exists(outputDir+fileTitle+".png"):
			fileTitle = promptText.replace(" ", "_")
			fileTitle = os.path.join(outputDir,(fileTitle+"_v"+str(versionNumber)))
			versionNumber += 1
	else:
		# figure out the name and version
		while os.path.exists(fileTitle+".png"):
			fileTitle = promptText.replace(" ", "_")
			fileTitle = fileTitle+"_v"+str(versionNumber)
			versionNumber += 1

	print("Creating image from prompt: "+fileTitle+".png")

	# if --set-model is called change the model
	if "--set-model" in sys.argv:
		modelPath = sys.argv[(sys.argv.index("--set-model")+1)]
	else:
		modelPath = "timbrooks/instruct-pix2pix"

	if "--offline" in sys.argv:
		use_only_local = True
	else:
		use_only_local = False

	# enable safety checker
	if "--sfw" in sys.argv:
		#pipe = StableDiffusionPipeline.from_pretrained(modelPath, revision="fp16", torch_dtype=torch.float16)
		#pipe = StableDiffusionPipeline.from_pretrained(modelPath, torch_dtype=torch.float16)
		pipe = StableDiffusionInstructPix2PixPipeline.from_pretrained(modelPath, torch_dtype=torch.float16, cache_dir="/var/cache/2web/downloads/ai/img2img/", local_files_only=use_only_local)
	else:
		# the safety checker is disabled by default
		#pipe = StableDiffusionPipeline.from_pretrained(modelPath, revision="fp16", torch_dtype=torch.float16, safety_checker=None)
		#pipe = StableDiffusionPipeline.from_pretrained(modelPath, torch_dtype=torch.float16, safety_checker=None)
		pipe = StableDiffusionInstructPix2PixPipeline.from_pretrained(modelPath, torch_dtype=torch.float16, safety_checker=None, cache_dir="/var/cache/2web/downloads/ai/img2img/", local_files_only=use_only_local)

	if "--debug-pipe-components" in sys.argv:
		print("Pipe components: "+str(pipe.components))

	# use cuda cores if possible
	if "--gpu" in sys.argv:
		pipe.to("cuda")
	elif "--cpu" in sys.argv:
		pass
	else:
		pipe.to("cuda")

	# setup the pipe sceduler for image editing
	pipe.scheduler = EulerAncestralDiscreteScheduler.from_config(pipe.scheduler.config)

	# load the image to modify
	if "https://" in url:
		imageData = requests.get(url, stream=True).raw
	elif "http://" in url:
		imageData = requests.get(url, stream=True).raw
	else:
		imageData = url

	# open the image
	imageData = PIL.Image.open(imageData)
	imageData = PIL.ImageOps.exif_transpose(imageData)
	imageData = imageData.convert("RGB")

	# generate image from prompt and get the generated image from the generated images array
	#image = pipe(prompt=promptText, height=int(imageHeight), width=int(imageWidth), negative_prompt=negativePromptText).images[0]
	imageData = pipe(image=imageData,prompt=promptText, negative_prompt=negativePromptText, num_inference_steps=10, image_guidance_scale=1).images[0]

	# check if the output directory has been set
	if "--output-dir" in sys.argv:
		# save the created image to the specified output directory
		imageData.save(os.path.join(outputDir, (fileTitle+".png")))
	else:
		imageData.save(fileTitle+".png")

	# mark another version completed
	versions -= 1

