#! /usr/bin/python3
########################################################################
# 2webLib.py is a common python3 library for 2web utilities
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
def hr():
	"""
	Draws a horisontal line of bangs(#) across the terminal. with a width of 80 characters.
	"""
	width=80
	print("_"*width)
################################################################################
def h1(bannerText):
	"""
	This function will draw the given bannerText variable with a line above and below it.
	"""
	width=80
	edge = int(( width - len("  "+bannerText+"  ") ) / 2)
	print("#"*width)
	print("#"+(" "*edge)+" "+bannerText+" "+(" "*edge)+"#")
	print("#"*width)
################################################################################
def file_get_contents(filePath):
	"""
	A copy of file_get_contents from PHP. This function takes a file path and returns the contents of the file as a full string.
	"""
	fileObj = open(filePath, "r")
	tempFileData = ""
	for line in fileObj:
		tempFileData += line
	fileObj.close()
	return tempFileData

