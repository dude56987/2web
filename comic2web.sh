#! /bin/bash
################################################################################
# comic2web generates websites from image filled directories
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
# enable debug log
#set -x
################################################################################
source /var/lib/2web/common
################################################################################
function downloadDir(){
	# write path to console
	echo "/var/cache/2web/downloads/comics/"
}
################################################################################
function generatedDir(){
	# write path to console
	echo "/var/cache/2web/generated/comics/"
}
################################################################################
function libaryPaths(){
	# check for server libary config
	if [ ! -f /etc/2web/comics/libaries.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the default config
			cat /etc/2web/config_default/comic2web_libraries.cfg
		} > "/etc/2web/comics/libaries.cfg"
	fi
	# write path to console
	grep -v "^#" "/etc/2web/comics/libaries.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/comics/libaries.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		grep -v "^#" "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
function processPdfPageToImage(){
	pdfFilePath=$1
	pdfComicName=$2
	# the start page
	pageNumber=$3
	# pdf file path
	# pdf comic name
	# generate the page path
	pdfImageFilePath="${generatedDirectory}/comics/pdf2comic/$pdfComicName/$pdfComicName-$pageNumber.jpg"
	# render the page
	pdftoppm "$pdfFilePath" -jpeg -f "$pageNumber" -l "$pageNumber" -cropbox "${generatedDirectory}/comics/pdf2comic/$pdfComicName/$pdfComicName"
	# trim the whitespace
	convert -quiet "$pdfImageFilePath" -fuzz '10%' -trim "$pdfImageFilePath"
	# add a border to the edge of the image
	convert -quiet "$pdfImageFilePath" -matte -bordercolor white -border 15 "$pdfImageFilePath"
}
################################################################################
function extractCBZ(){
	# extractCBZ $generatedDirectory" "$cbzFilePath" "$cbzComicName"
	# extract a CBZ comic book zip file to the generated directory
	generatedDirectory=$1
	cbzFilePath=$2
	cbzComicName=$3
	#
	unzip "$cbzFilePath" -d "${generatedDirectory}/comics/cbz2comic/$cbzComicName/"
	chown -R www-data:www-data "${generatedDirectory}/comics/cbz2comic/$cbzComicName/"
}
################################################################################
function update(){
	addToLog "INFO" "STARTED Update" "$(date)"
	# this will launch a processing queue that downloads updates to comics
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/comics/sources.cfg;then
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/comic2web_sources.cfg
		} > /etc/2web/comics/sources.cfg
	fi
	# load sources
	comicSources=$(grep -v "^#" /etc/2web/comics/sources.cfg)
	comicSources=$(echo -e "$comicSources\n$(grep -v --no-filename "^#" /etc/2web/comics/sources.d/*.cfg)")

	# check for defined webcomic sources
	if ! test -f /etc/2web/comics/webSources.cfg;then
		# if no config exists create the default config
		{
			cat /etc/2web/config_default/comic2web_webSources.cfg
		} > /etc/2web/comics/webSources.cfg
	fi
	# load sources
	webComicSources=$(grep -v "^#" /etc/2web/comics/webSources.cfg)
	webComicSources=$(echo -e "$webComicSources\n$(grep -v --no-filename "^#" /etc/2web/comics/webSources.d/*.cfg)")


	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedRoot)"
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make comics directory
	createDir "$webDirectory/comics/"
	# create web and cache directories
	createDir "$webDirectory/comicCache/"
	# check for parallel processing and count the cpus
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi
	################################################################################
	# DOWNLOAD SECTION
	################################################################################
	if yesNoCfgCheck /etc/2web/comics/downloadNew.cfg "yes";then
		# remove mark files older than 40 days, this will cause the comic to be updated once every 40 days
		find "$webDirectory/comicCache/" -type f -name "download_*.index" -mtime +40 -delete
		# update webcomics every 7 days, download is limited to 50 pages every 7 days
		find "$webDirectory/comicCache/" -type f -name "webDownload_*.index" -mtime +7 -delete
		# clean the cache of old files
		# scan the sources
		ALERT "Comic Download Sources: $comicSources"
		#for comicSource in $comicSources;do
		echo "$comicSources" | while read comicSource;do
			# generate a sum for the source
			comicSum=$(echo "$comicSource" | sha512sum | cut -d' ' -f1)
			# do not process the comic if it is still in the cache
			# - Cache removes files older than x days
			if ! test -f "$webDirectory/comicCache/download_$comicSum.index";then
				addToLog "DOWNLOAD" "Downloading comic" "Downloading comic with gallery-dl from '$comicSource'"
				# if the comic is not cached it should be downloaded
				/usr/local/bin/gallery-dl --write-metadata --dest "$downloadDirectory" "$comicSource" && touch "$webDirectory/comicCache/download_$comicSum.index"
			fi
		done
		echo "$webComicSources" | while read comicSource;do
			# generate a sum for the source
			comicSum=$(echo "$comicSource" | sha512sum | cut -d' ' -f1)
			# do not process the comic if it is still in the cache
			# - Cache removes files older than x days
			if ! test -f "$webDirectory/comicCache/webDownload_$comicSum.index";then
				addToLog "DOWNLOAD" "Downloading comic" "Downloading comic with Dosage, comic titled '$comicSource'"
				# if the comic is not cached it should be downloaded
				if echo "$comicSource" | grep "/";then
					totalPages=$(find "${downloadDirectory}$comicSource"/ -name "*.jpg" | wc -l)
					# set the number of strips to download to be the current number + 50
					numStrips=$(( $totalPages + 50 ))

					/usr/local/bin/dosage --parallel "$totalCPUS" --adult --basepath "${downloadDirectory}/" --numstrips $numStrips --all "$comicSource" && touch "$webDirectory/comicCache/webDownload_$comicSum.index"
					# cleanup downloaded .txt files
					rm -v "${downloadDirectory}$comicSource"/*.txt
					find "${downloadDirectory}$comicSource/" -name "*.png" | while read imageToConvert;do
						newImagePath=$(echo "$imageToConvert"	| sed "s/\.png$/.jpg/g")
						# convert each png file to a jpg file
						convert -quiet "$imageToConvert" "$newImagePath"
					done
					find "${downloadDirectory}$comicSource/" -name "*.gif" | while read imageToConvert;do
						newImagePath=$(echo "$imageToConvert"	| sed "s/\.gif$/.jpg/g")
						# convert each png file to a jpg file
						convert -quiet "$imageToConvert" "$newImagePath"
					done
				else
					totalPages=$(find "${downloadDirectory}dosage/$comicSource"/ -name "*.jpg" | wc -l)
					# set the number of strips to download to be the current number + 50
					numStrips=$(( $totalPages + 50 ))

					/usr/local/bin/dosage --adult --basepath "${downloadDirectory}dosage/" --numstrips $numStrips --all "$comicSource" && touch "$webDirectory/comicCache/webDownload_$comicSum.index"
					# cleanup downloaded .txt files
					rm -v "${downloadDirectory}dosage/$comicSource"/*.txt
					find "${downloadDirectory}dosage/$comicSource/" -name "*.png" | while read imageToConvert;do
						newImagePath=$(echo "$imageToConvert"	| sed "s/\.png$/.jpg/g")
						# convert each png file to a jpg file
						convert -quiet "$imageToConvert" "$newImagePath"
					done
					find "${downloadDirectory}dosage/$comicSource/" -name "*.gif" | while read imageToConvert;do
						newImagePath=$(echo "$imageToConvert"	| sed "s/\.gif$/.jpg/g")
						# convert each png file to a jpg file
						convert -quiet "$imageToConvert" "$newImagePath"
					done
				fi
			fi
		done
	fi
	################################################################################
	# CONVERSION SECTION
	################################################################################
	# check for txt files and convert them into comics
	comicLibaries="$(libaryPaths | tr -s '\n' | shuf )"
	# first convert epub files to pdf files
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.txt' | sort | while read txtFilePath;do
			txtComicName=$(popPath "$txtFilePath" | sed "s/.txt//g")
			# only extract the cbz once
			if ! test -d "${generatedDirectory}/comics/txt2comic/$txtComicName/$txtComicName.pdf";then
				mkdir -p "${generatedDirectory}/comics/txt2comic/$txtComicName/"
				# extract the cbz file to the download directory
				INFO "Found txt '$txtComicName', converting to comic book..."
				addToLog "UPDATE" "Generating Comic" "Converting text documents to PDF format from '$txtFilePath'"
				# convert epub files into pdf files to be converted below
				cat "$txtFilePath" | txt2html --style_url "http://localhost/style.css" > "${generatedDirectory}/comics/txt2comic/$txtComicName/$txtComicName.html"
				chown -R www-data:www-data "${generatedDirectory}/comics/txt2comic/$txtComicName/"
			fi
		done
	done
	# first convert .ps postscript files to pdf files
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each .ps file found in the .ps libary locations
		find "$comicLibaryPath" -type f -name '*.ps' | sort | while read psFilePath;do
			psComicName=$(popPath "$psFilePath" | sed "s/.ps//g")
			# only extract the .ps once
			if ! test -d "${generatedDirectory}/comics/ps2comic/$psComicName/$psComicName.pdf";then
				mkdir -p "${generatedDirectory}/comics/ps2comic/$psComicName/"
				# extract the .ps file to the download directory
				INFO "Found ps '$psComicName', converting to comic book..."
				addToLog "UPDATE" "Generating Comic" "Converting postscript documents to PDF format from '$psComicName'"
				# convert postscript files into pdf files
				ps2pdf "$psFilePath" "${generatedDirectory}/comics/ps2comic/$psComicName/$psComicName.pdf"
				chown -R www-data:www-data "${generatedDirectory}/comics/ps2comic/$psComicName/"
			fi
		done
	done
	# convert markdown files to pdf files
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.md' | sort | while read markdownFilePath;do
			markdownComicName=$(popPath "$markdownFilePath" | sed "s/.md//g")
			# only extract the cbz once
			if ! test -d "${generatedDirectory}/comics/markdown2comic/$markdownComicName/$markdownComicName.pdf";then
				addToLog "UPDATE" "Generating Comic" "Converting markdown documents to PDF format from '$markdownComicName'"
				mkdir -p "${generatedDirectory}/comics/markdown2comic/$markdownComicName/"
				# extract the cbz file to the download directory
				INFO "Found markdown '$markdownComicName', converting to comic book..."
				# convert markdown into html
				{
					echo "<html>"
					echo "<head>"
					# use the currently active theme for the website
					echo "	<link rel='stylesheet' type='text/css' href='http://localhost/style.css'>"
					echo "	<script src='/2webLib.js'></script>"
					echo "	<link rel='icon' type='image/png' href='/favicon.png'>"
					echo "</head>"
					echo "<body>"
					cat "$markdownFilePath" | markdown
					echo "</body>"
					echo "</html>"

				} > "${generatedDirectory}/comics/markdown2comic/$markdownComicName/$markdownComicName.html"
				chown -R www-data:www-data "${generatedDirectory}/comics/markdown2comic/$markdownComicName/"
			fi
		done
	done

	# convert html files to pdf files
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.html' | sort | while read htmlFilePath;do
			htmlComicName=$(popPath "$htmlFilePath" | sed "s/.html//g")
			# only extract the cbz once
			if ! test -d "${generatedDirectory}/comics/html2comic/$htmlComicName/$htmlComicName.pdf";then
				addToLog "UPDATE" "Generating Comic" "Converting HTML documents to PDF format from '$htmlComicName'"
				mkdir -p "${generatedDirectory}/comics/html2comic/$htmlComicName/"
				# extract the cbz file to the download directory
				INFO "Found html'$htmlComicName', converting to comic book..."
				# convert epub files into pdf files to be converted below
				cat "$htmlFilePath" | wkhtmltopdf - "${generatedDirectory}/comics/html2comic/$htmlComicName/$htmlComicName.pdf"
				chown -R www-data:www-data "${generatedDirectory}/comics/html2comic/$htmlComicName/"
			fi
		done
	done

	if test -f /usr/bin/ebook-convert;then
		# first convert epub files to pdf files
		echo "$comicLibaries" | sort | while read comicLibaryPath;do
			# for each cbz file found in the cbz libary locations
			find "$comicLibaryPath" -type f -name '*.epub' | sort | while read epubFilePath;do
				epubComicName=$(popPath "$epubFilePath" | sed "s/.epub//g")
				# only extract the cbz once
				if ! test -d "${generatedDirectory}/comics/epub2comic/$epubComicName.pdf";then
					addToLog "UPDATE" "Generating Comic" "Converting EPUB documents to PDF format from '$epubComicName'"
					mkdir -p "${generatedDirectory}/comics/epub2comic/"
					# extract the cbz file to the download directory
					INFO "Found epub '$epubComicName', converting to comic book..."
					# allow ebook convert to run as root
					# convert epub files into pdf files to be converted below
					export QTWEBENGINE_CHROMIUM_FLAGS="--no-sandbox" && ebook-convert "$epubFilePath" "${generatedDirectory}/comics/epub2comic/$epubComicName/$epubComicName.pdf"
					chown -R www-data:www-data "${generatedDirectory}/comics/epub2comic/$epubComicName/"
				fi
			done
		done
	fi

	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each pdf file found in the pdf libary locations
		find "$comicLibaryPath" -type f -name '*.pdf' | sort | while read pdfFilePath;do
			pdfComicName=$(popPath "$pdfFilePath" | sed "s/.pdf//g")
			# only extract the pdf once
			if ! test -d "${generatedDirectory}/comics/pdf2comic/$pdfComicName/";then
				createDir "${generatedDirectory}/comics/pdf2comic/$pdfComicName/"
				# extract the pdf file to the download directory
				ALERT "Found pdf '$pdfComicName', converting to comic book..."
				addToLog "UPDATE" "Generating Comic" "Converting PDF document to PNG images from '$epubComicName'"
				# create the page counter
				pageCounter=1
				# get the page count from the pdf file using pdfInfo
				pageCount=$(pdfinfo "$pdfFilePath" | tr -d ' ' | grep "Pages:" | cut -d':' -f2)
				# launch a thread for each process
				for pageCounter in $(seq --equal-width -s " " 1 "$pageCount");do
					INFO "Rendering page $pageCounter/$pageCount from $pdfComicName"
					# render the page, use multithreading
					# - pdftoppm is not a multithreaded application so we render each page in a seprate instance to make it render all pages in parallel
					processPdfPageToImage "$pdfFilePath" "$pdfComicName" "$pageCounter" &
					# the fast queue is more appropriate to use here
					waitQueue 0.2 "$totalCPUS"
				done
			fi
		done
	done
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.cbz' | sort | while read cbzFilePath;do
			cbzComicName=$(popPath "$cbzFilePath" | sed "s/.cbz//g")
			# only extract the cbz once
			if ! test -d "${generatedDirectory}/comics/cbz2comic/$cbzComicName/";then
				addToLog "UPDATE" "Extracting Comic" "Converting CBZ file to image directory from '$cbzComicName'"
				createDir "${generatedDirectory}/comics/cbz2comic/$cbzComicName/"
				# extract the cbz file to the download directory
				INFO "Found cbz '$cbzComicName', converting to comic book..."
				# - load the cbz file with its filename as the comic name into the comic download directory
				extractCBZ "$generatedDirectory" "$cbzFilePath" "$cbzComicName" &
				waitQueue 0.2 "$totalCPUS"
			fi
		done
	done
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each zip file found in the zip libary locations
		find "$comicLibaryPath" -type f -name '*.zip' | sort | while read cbzFilePath;do
			cbzComicName=$(popPath "$cbzFilePath" | sed "s/.zip//g")
			# only extract the cbz once
			if ! test -d "${generatedDirectory}/comics/cbz2comic/$cbzComicName/";then
				addToLog "UPDATE" "Extracting Comic" "Converting ZIP file to image directory from '$cbzComicName'"
				mkdir -p "${generatedDirectory}/comics/cbz2comic/$cbzComicName/"
				# extract the zip file to the download directory
				INFO "Found zip '$cbzComicName', converting to comic book..."
				# - load the cbz file with its filename as the comic name into the comic download directory
				extractCBZ "$generatedDirectory" "$cbzFilePath" "$cbzComicName" &
				waitQueue 0.2 "$totalCPUS"
			fi
		done
	done
	# stop the queue outside of the loop to wait for rendering to finish
	blockQueue 1
	# scan the new comics into the index
	#rebuildComicIndex "$webDirectory"

	# cleanup the comics index
	if test -f "$webDirectory/comics/comics.index";then
		tempList=$(cat "$webDirectory/comics/comics.index" | sort -u )
		echo "$tempList" > "$webDirectory/comics/comics.index"
	fi
	# cleanup new comic index
	if test -f "$webDirectory/new/comics.index";then
		# new comics but preform a fancy sort that does not change the order of the items
		#tempList=$(cat -n "$webDirectory/new/comics.index" | sort -uk2 | sort -nk1 | cut -f1 | tail -n 200 )
		tempList=$(cat "$webDirectory/new/comics.index" | tail -n 800 )
		echo "$tempList" > "$webDirectory/new/comics.index"
	fi
	addToLog "INFO" "FINISHED Update" "$(date)"
}
################################################################################
convertImage(){
	fileName=$1

	# convert the image files
	if echo "$fileName" | grep -q ".webm$";then
		newName=$(echo "$fileName" | sed "s/\.webm/.jpg/g")
	elif echo "$fileName" | grep -q ".png$";then
		newName=$(echo "$fileName" | sed "s/\.png/.jpg/g")
	fi

	if ! test -f "$newName";then
		# convert the filename
		convert -quiet "$fileName" "$newName"
	fi

	return 0
}
################################################################################
cleanText(){
	# clean up the text for use in web urls and directory paths
	cleanedText="$1"
	# remove bangs as they break URLS
	cleanedText=$(echo "$cleanedText" | tr -d '#`')
	cleanedText=$(echo "$cleanedText" | tr -d "'" )
	cleanedText=$(echo "$cleanedText" | sed "s/_/ /g" )
	# convert question marks into wide question marks so they look
	# the same but wide question marks do not break URLS
	cleanedText=$(echo "$cleanedText" | sed "s/?/？/g" )
	# cleanup ampersands, they break URLs
	cleanedText=$(echo "$cleanedText" | sed "s/&/＆/g" )
	# cleanup @ symbols, they break URLs
	cleanedText=$(echo "$cleanedText" | sed "s/@/＠/g" )
	# remove percent signs they break print functions
	cleanedText=$(echo "$cleanedText" | sed "s/%/％/g" )
	# hyphens break grep searches
	cleanedText=$(echo "$cleanedText" | sed "s/-/－/g" )
	# squeeze double spaces into single spaces
	cleanedText=$(echo "$cleanedText" | tr -s ' ')
	# print the cleaned up text
	echo "$cleanedText"
}
################################################################################
getTitle(){
	# load the json string directly into the function
	tempJson=$1
	# check for various comic name values
	# - these are in order of availability
	comicName=$(echo "$tempJson" | jq -r ".comic")
	if echo "$comicName" | grep -q "null$";then
		# if it is not a comic check if it is a manga
		comicName=$(echo "$tempJson" | jq -r ".manga")
	fi
	if echo "$comicName" | grep -q "null$";then
		# if it is not a manga use the chapter title
		comicName=$(echo "$tempJson" | jq -r ".title")
	fi

	# clean up the text in the name
	cleanText "$comicName"
}
################################################################################
getPageNumber(){
	# load the json string directly into the function
	tempJson=$1
	# check for various page json values that can exist
	comicName=$(echo "$tempJson" | jq -r ".page")
	if echo "$data" | grep -q "null";then
		comicName=$(echo "$tempJson" | jq -r ".num")
	fi
	if echo "$data" | grep -q "null";then
		# get the filename but purge all non numeric string values
		comicName=$(echo "$tempJson" | jq -r ".filename" | tr -d "[:alpha:]")
	fi
	# clean up the text in the name
	echo "$comicName"
}
################################################################################
getChapter(){
	# load the json string directly into the function
	tempJson=$1
	# check for various page json values that can exist
	data=$(echo "$data" | jq -r ".chapter")
	if echo "$data" | grep -q "null";then
		data=$(echo "$tempJson" | jq -r ".issue")
	fi
	if echo "$data" | grep -q "null";then
		# get the filename but purge all non numeric string values
		data=$(echo "$tempJson" | jq -r ".filename" | tr -d "[:alpha:]")
	fi
	# clean up the text in the name
	echo "$data"
}
################################################################################
popPath(){
	# pop the path name from the end of a absolute path
	# e.g. popPath "/path/to/your/file/test.jpg"
	echo "$1" | rev | cut -d'/' -f1 | rev
}
################################################################################
pickPath(){
	# pop a element from the end of the path, $2 is how far back in the path is pulled
	echo "$1" | rev | cut -d'/' -f$2 | rev
}
################################################################################
prefixNumber(){
	#set -x
	pageNumber=$(( 10#$1 ))
	# set the page number prefix to make file sorting work
	# - this makes 1 occur before 10 by adding zeros ahead of the number
	# - this will work unless the comic has a chapter over 9999 pages
	if [ $pageNumber -lt 10 ];then
		pageNumber="000$pageNumber"
	elif [ $pageNumber -lt 100 ];then
		pageNumber="00$pageNumber"
	elif [ $pageNumber -lt 1000 ];then
		pageNumber="0$pageNumber"
	fi
	# output the number with a prefix on it
	echo $pageNumber
	#set +x
}
################################################################################
createDir(){
	if ! test -d "$1";then
		mkdir -p "$1"
		# set ownership of directory and subdirectories as www-data
		chown -R www-data:www-data "$1"
	fi
	# set ownership of directory and subdirectories as www-data
	chown www-data:www-data "$1"
}
################################################################################
scanPages(){
	# - TODO: build a function that reads all image files in a directory, makes webpages for them
	#         index in directory links to first page, last page should link to .. index above
	pagesDirectory=$1
	webDirectory=$2
	pageType=$3
	pageChapter=$4
	################################################################################
	# TODO: This is either a chapter or a single page comic build the index in the pages directory as
	#       the individual pages are rendered

	# set page number counter
	pageNumber=0

	# scan for all jpg and png images in the comic book directory
	find -L "$pagesDirectory" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" -o -name "*.png" -o -name "*.webp" | sort | while read imagePath;do
		pageNumber=$(( 10#$pageNumber + 1 ))
		################################################################################
		# set the page number prefix to make file sorting work
		# - this makes 1 occur before 10 by adding zeros ahead of the number
		# - this will work unless the comic has a chapter over 9999 pages
		################################################################################
		if echo "$pageType" | grep -q "chapter";then
			# is a chapter based comic
			tempComicName="$(pickPath "$imagePath" 3)"
			tempComicName="$(cleanText "$tempComicName")"
			tempComicName="$(alterArticles "$tempComicName")"
			#tempComicChapter="$(pickPath "$imagePath" 2)"
			tempComicChapter=$pageChapter
			#if cacheCheck "$webDirectory/comics/$tempComicName/index.php" 10;then
			#	# remove cached version of comic on server for rebuild if comic is older than 10 days
			#	rm -rv "$webDirectory/comics/$tempComicName/"
			#fi
			if cacheCheck "$webDirectory/comics/$tempComicName/$tempComicChapter/index.php" 10;then
				# create comic directory
				createDir "$webDirectory/comics/$tempComicName"
				createDir "$webDirectory/comics/$tempComicName/$tempComicChapter"
				# render if the page is older than 10 days
				# get the total chapters
				if ! test -f "$webDirectory/comics/$tempComicName/totalChapters.cfg";then
					# write the total number of chapters to a file in the web directory
					# - .. creates crazy filenames in output but all needed is # of lines
					totalChapters=$(find "$pagesDirectory/.." -mindepth 1 -maxdepth 1 -type d | wc -l)
					# write the total pages to a file in the directory
					echo "$totalChapters" > "$webDirectory/comics/$tempComicName/totalChapters.cfg"
				else
					totalChapters=$(cat "$webDirectory/comics/$tempComicName/totalChapters.cfg")
				fi
				# link the image file to the web directory
				#echo "[DEBUG]: Linking a comic chapter from $tempComicName"
				# if the total pages has not yet been stored
				if ! test -f "$webDirectory/comics/$tempComicName/$tempComicChapter/totalPages.cfg";then
					# find the total number of pages in the chapter
					totalPages=$(find -L "$pagesDirectory" -maxdepth 1 -mindepth 1 -name "*.jpg" -o -name "*.png" -o -name "*.webp" | wc -l)
					# write the total pages to a file in the directory
					echo "$totalPages" > "$webDirectory/comics/$tempComicName/$tempComicChapter/totalPages.cfg"
				else
					totalPages=$(cat "$webDirectory/comics/$tempComicName/$tempComicChapter/totalPages.cfg")
				fi
				INFO "Rendering $tempComicName chapter $tempComicChapter/$totalChapters page $pageNumber/$totalPages "
				# prefix the number for file sorting, output above makes more sense without leading zeros
				pageNumber=$(prefixNumber $pageNumber)
				# link image inside comic chapter directory
				linkFile "$imagePath" "$webDirectory/comics/$tempComicName/$tempComicChapter/$pageNumber.jpg"
				# render the web page
				renderPage "$imagePath" "$webDirectory" "$pageNumber" chapter "$pageChapter"
			else
				# exit scanning pages into the comic because the index was built within the last 10 days
				return
			fi
		else
			# is a single chapter comic
			tempComicName="$(pickPath "$imagePath" 2)"
			tempComicName="$(cleanText "$tempComicName")"
			tempComicName="$(alterArticles "$tempComicName")"

			if cacheCheck "$webDirectory/comics/$tempComicName/index.php" 10;then
				# remove cached version of comic on server for rebuild if it is older than 10 days
				#rm -rv "$webDirectory/comics/$tempComicName/"
				# create comic directory
				createDir "$webDirectory/comics/$tempComicName/"
				# link the image file to the web directory
				#echo "[INFO]: Linking single chapter comic $tempComicName"
				# if the total pages has not yet been stored
				if ! test -f "$webDirectory/comics/$tempComicName/totalPages.cfg";then
					# find the total number of pages in the chapter
					totalPages=$(find -L "$pagesDirectory" -maxdepth 1 -mindepth 1 -name "*.jpg" -o -name "*.png" -o -name "*.webp" | wc -l)
					# write the total pages to a file in the directory
					echo "$totalPages" > "$webDirectory/comics/$tempComicName/totalPages.cfg"
				else
					totalPages=$(cat "$webDirectory/comics/$tempComicName/totalPages.cfg")
				fi
				# update interface
				INFO "Rendering $tempComicName page $pageNumber/$totalPages"
				# prefix the number for file sorting, output above makes more sense without leading zeros
				pageNumber=$(prefixNumber $pageNumber)
				linkFile "$imagePath" "$webDirectory/comics/$tempComicName/$pageNumber.jpg"
				# render the page
				renderPage "$imagePath" "$webDirectory" $pageNumber single
			else
				# exit scanning pages into the comic because the index was built within the last 10 days
				return
			fi
		fi
	done
}
################################################################################
renderPage(){
	page=$1
	webDirectory=$2
	pageNumber=$3
	pageType=$4
	pageChapterName=$5
	imagePath=$1
	################################################################################
	if echo "$pageType" | grep -q --ignore-case "chapter";then
		isChapter=true
	else
		isChapter=false
	fi

	################################################################################
	pageName=$(popPath "$page")
	if [ $isChapter = true ];then
		pageComicName=$(pickPath "$page" 3 | tr -d "'")
		pageComicName=$(cleanText "$pageComicName")
		# cleanup (The, A ,An) at start of titles to make sorting work correctly
		pageComicName=$(alterArticles "$pageComicName")
		# remove : as it breaks web paths
		#pageComicName=$(echo "$pageComicName" | sed "s/:/~/g" )
		# multi chapter comic
		if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/chapterTitle.cfg";then
			pageChapterPathName=$(pickPath "$page" 2)
			echo "$pageChapterPathName" > "$webDirectory/comics/$pageComicName/$pageChapterName/chapterTitle.cfg"
		fi
		# link the image file into the web directory
		linkFile "$imagePath" "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.jpg"
		# create the thumbnail for the image, otherwise it will nuke the server reading the HQ image files on loading index pages
		if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber-thumb.png";then
			convert -quiet "$imagePath" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber-thumb.png"
		fi
		# get page width and height
		tempImageData=$(identify -verbose "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.jpg" | grep "Geometry" |  cut -d':' -f2 | sed "s/+0//g")
		#echo "[DEBUG]: image size = '$tempImageData'"
		# get the total pages
		totalPages=$(cat "$webDirectory/comics/$pageComicName/$pageChapterName/totalPages.cfg")
		totalChapters=$(cat "$webDirectory/comics/$pageComicName/totalChapters.cfg")
	else
		# single chapter comic
		pageComicName=$(pickPath "$page" 2)
		pageComicName=$(cleanText "$pageComicName")
		pageComicName=$(alterArticles "$pageComicName")
		# remove : as it breaks web paths
		#pageComicName=$(echo "$pageComicName" | sed "s/:/~/g" )

		# create the thumbnail for the image, otherwise it will nuke the server reading the HQ image files on loading index pages
		if ! test -f "$webDirectory/comics/$pageComicName/$pageNumber-thumb.png";then
			convert -quiet "$imagePath" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/$pageNumber-thumb.png"
		fi
		# link the image
		linkFile "$imagePath" "$webDirectory/comics/$pageComicName/$pageNumber.jpg"
		# get page width and height
		tempImageData=$(identify -verbose "$webDirectory/comics/$pageComicName/$pageNumber.jpg" | grep "Geometry" |  cut -d':' -f2 | sed "s/+0//g")
		# get the total pages
		totalPages=$(cat "$webDirectory/comics/$pageComicName/totalPages.cfg")
	fi
	# pull width and height from image file data
	width=$(echo "$tempImageData" | cut -d'x' -f1)
	height=$(echo "$tempImageData" | cut -d'x' -f2)

	#width=$(( 10#$width ))
	#height=$(( 10#$height ))

	# build stylesheet stuff
	tempStyle="background: url(\"$pageNumber.jpg\")"
	tempStyleThumb="background: url(\"$pageNumber-thumb.png\")"

	# link missing stylesheets for this chapter of the comic
	linkFile "$webDirectory/style.css" "$webDirectory/comics/style.css"

	if [ $isChapter = true ];then
		# figure out the back and forward pages
		imageArray=$(find -L "$webDirectory/comics/$pageComicName/$pageChapterName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort)
	else
		imageArray=$(find -L "$webDirectory/comics/$pageComicName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort)
	fi

	#echo "[DEBUG]: imageArray = '$imageArray'"

	# set the next and previous page numbers
	nextPage=$(( 10#$pageNumber + 1 ))
	previousPage=$(( 10#$pageNumber - 1 ))

	# format page numbers to sort correctly
	nextPage=$(prefixNumber $nextPage)
	previousPage=$(prefixNumber $previousPage)

	if [[ 10#$previousPage -le 0 ]];then
		if [ $isChapter = true ];then
			# if the previous page is 0 then link back to the index
			linkFile "index.php" "$webDirectory/comics/$pageComicName/$pageChapterName/0000.html"
		else
			linkFile "index.php" "$webDirectory/comics/$pageComicName/0000.html"
		fi

		if [ $isChapter = true ];then
			# create chapter specific thumbnails
			if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/thumb.png";then
				# create a thumb for the comic
				convert -quiet "$webDirectory/comics/$pageComicName/$pageChapterName/0001.jpg" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/$pageChapterName/thumb.png"
			fi
		fi
		# if no thumbnail exists then
		if ! test -f "$webDirectory/comics/$pageComicName/thumb.png";then
			if [ $isChapter = true ];then
				# create a thumb for the comic
				convert -quiet "$webDirectory/comics/$pageComicName/$pageChapterName/0001.jpg" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/thumb.png"
			else
				convert -quiet "$webDirectory/comics/$pageComicName/0001.jpg" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/thumb.png"
			fi
		fi
	fi
	if [ $isChapter = true ];then
		# link the image file into the web directory
		pagePath="$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.html"
		#
		linkFile "/usr/share/2web/templates/comic_page.php" "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.php"
	else
		# single chapter comic
		pagePath="$webDirectory/comics/$pageComicName/$pageNumber.html"
		#
		linkFile "/usr/share/2web/templates/comic_page.php" "$webDirectory/comics/$pageComicName/$pageNumber.php"
	fi
	# if no zip directory exists then create the zip directory
	createDir "$webDirectory/kodi/comics_tank/$pageComicName/"
	# write the downloadable .zip file
	# - zip requires the current working directory be changed
	if [ $isChapter = true ];then
		cd "$webDirectory/kodi/comics_tank/$pageComicName/"
		# link to kodi comic tanks directory
		linkFile "$imagePath" "$pageComicName-$pageChapterName-$pageNumber.jpg"
		# link to kodi directory comics directory
		createDir "$webDirectory/kodi/comics/$pageComicName/$pageChapterName/"
		linkFile "$imagePath" "$webDirectory/kodi/comics/$pageComicName/$pageChapterName/$pageNumber.jpg"
		if [  $((10#$nextPage)) -gt $totalPages ];then
			if [[  "10#$pageChapterName" -ge "10#$totalChapters" ]];then
				# if this is the last page create the zip file
				echo -n
				#zip -jrqT -9 "$webDirectory/comics/$pageComicName/$pageComicName.cbz" "."
			fi
		fi
	else
		cd "$webDirectory/kodi/comics_tank/$pageComicName/"
		# link to kodi comic tanks directory
		linkFile "$imagePath" "$pageComicName-$pageNumber.jpg"
		createDir "$webDirectory/kodi/comics/$pageComicName/"
		# link to kodi directory comics directory
		linkFile "$imagePath" "$webDirectory/kodi/comics/$pageComicName/$pageNumber.jpg"
		if [  $((10#$nextPage)) -gt $totalPages ];then
			# if this is the last page create the zip file
			echo -n
			#zip -jrqT -9 "$webDirectory/comics/$pageComicName/$pageComicName.cbz" "."
		fi
	fi
	# check next and previous pages to make sure they can be linked to
	# write the webpage for the individual image
	################################################################################
	# set the default index value
	buildPagesIndex=false

	if [ $isChapter = true ];then
		# if there is no next page in the chapter
		#if ! [ -f "$webDirectory/comics/$pageComicName/$pageChapterName/$nextPage.jpg" ];then
		if [  $((10#$nextPage)) -gt $totalPages ];then
			# if there is no next page in this chapter of the comic
			linkFile "index.php" "$webDirectory/comics/$pageComicName/$pageChapterName/$nextPage.html"
			# if this is the last chapter of a multi chapter comic
			if [[  "10#$pageChapterName" -ge "10#$totalChapters" ]];then
				buildPagesIndex=true
			fi
		fi
	else
		# if there is no next page in the comic
		#if ! [ -f "$webDirectory/comics/$pageComicName/$nextPage.jpg" ];then
		if [  $((10#$nextPage)) -gt $totalPages ];then
			# link to the index page
			linkFile "index.php" "$webDirectory/comics/$pageComicName/$nextPage.html"
			buildPagesIndex=true
		fi
	fi

	# if this is the last page build the index for the comic
	#if [ $nextPage -ge $totalPages ];then
	if [ $buildPagesIndex = true ];then
		{
			echo "<a href='/comics/$tempComicName/' class='indexSeries' >"
			echo "<img loading='lazy' src='/comics/$tempComicName/thumb.png' />"
			echo "<div>$tempComicName</div>"
			echo "</a>"
		} > "$webDirectory/comics/$tempComicName/comics.index"

		SQLaddToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/data.db" "comics"
		SQLaddToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/data.db" "all"

		SQLaddToIndex "/comics/$tempComicName/thumb.png" "$webDirectory/backgrounds.db" "comics_poster"
		SQLaddToIndex "/comics/$tempComicName/thumb.png" "$webDirectory/backgrounds.db" "comics_fanart"
		SQLaddToIndex "/comics/$tempComicName/thumb.png" "$webDirectory/backgrounds.db" "poster_all"
		SQLaddToIndex "/comics/$tempComicName/thumb.png" "$webDirectory/backgrounds.db" "fanart_all"

		# add new comic to log
		addToLog "NEW" "Adding Comic" "Adding comic '$tempComicName'"

		# add the comic to the main comic index since it has been updated
		addToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/comics/comics.index"
		# add the updated show to the new comics index
		addToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/new/comics.index"
		addToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/new/all.index"
		# random indexes
		linkFile "$webDirectory/comics/comics.index"  "$webDirectory/random/comics.index"

		# update last updated times
		date "+%s" > /var/cache/2web/web/new/all.cfg
		date "+%s" > /var/cache/2web/web/new/comics.cfg

		# start building the comic index since this is the last page
		# also link the scroll view page
		linkFile "/usr/share/2web/templates/comic_scroll.php" "$webDirectory/comics/$pageComicName/scroll.php"
		# link the comic overview page
		linkFile "/usr/share/2web/templates/comic_overview.php" "$webDirectory/comics/$pageComicName/index.php"

		#if echo "$pageType" | grep "chapter";then
		if [ $isChapter = true ];then
			INFO "Building index links to each chapter of the comic..."
			# build links to each chapter of the comic
			find "$webDirectory/comics/$pageComicName/" -mindepth 1 -maxdepth 1 -type d | sort | while read chapterPath;do
				# multi chapter comic
				tempChapterName="$(popPath "$chapterPath")"
				tempNextChapterName=$(( 10#$tempChapterName + 1 ))
				tempPreviousChapterName=$(( 10#$tempChapterName - 1 ))
				# reformat next and previous chapters
				tempNextChapterName=$(prefixNumber "$tempNextChapterName")
				tempPreviousChapterName=$(prefixNumber "$tempPreviousChapterName")
				# check for existince of next and previous chapters
				if [ $(( 10#$tempChapterName + 1 )) -gt $totalChapters ];then
					# no next chapter exists link to the index
					tempNextChapterName="index.php"
				else
					tempNextChapterName="$tempNextChapterName/"
				fi
				if [ $(( 10#$tempChapterName - 1 )) -le 0 ];then
					# no previous chapter exists link to the index
					tempPreviousChapterName="index.php"
				else
					tempPreviousChapterName="$tempPreviousChapterName/"
				fi
				# build the comic chapter link in the main comic index
				trueChapterTitle=$(cat "$webDirectory/comics/$pageComicName/$tempChapterName/chapterTitle.cfg")
				# build the index for the chapter displaying all the images
				linkFile "/usr/share/2web/templates/comic_overview.php" "$webDirectory/comics/$pageComicName/$tempChapterName/index.php"
			done
		fi
		# move into the web directory so paths from below searches are relative
		cd "$webDirectory/comics/"
		# build the poster list from the thumbnails
		find -L "." -type f -name "thumb.png" > "$webDirectory/comics/poster.cfg"
		# link fanart to poster list
		# NOTE: find a way to create a fanart list than duplicating the poster list
		linkFile "poster.cfg" "fanart.cfg"
	fi
}
################################################################################
rebuildComicIndex(){
	webDirectory=$1
	# search for new comics
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
		# create the comic index files here in order to allow dynamic index view during updates
		tempComicName="$(popPath "$comicNamePath")"
		tempComicName="$(cleanText "$tempComicName")"
		tempComicName="$(alterArticles "$tempComicName")"

		if ! test -f "$comicNamePath/comics.index";then
			{
				echo "<a href='/comics/$tempComicName/' class='indexSeries' >"
				echo "<img loading='lazy' src='/comics/$tempComicName/thumb.png' />"
				echo "<div>$tempComicName</div>"
				echo "</a>"
			} > "$comicNamePath/comics.index"
		fi

		SQLaddToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/data.db" "comics"
		SQLaddToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/data.db" "all"

		# add the comic to the main comic index since it has been updated
		addToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/comics/comics.index"
		# add the updated show to the new shows index
		addToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/new/comics.index"
		addToIndex "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/new/all.index"
		# random indexes
		linkFile "$webDirectory/comics/comics.index"  "$webDirectory/random/comics.index"
		# update last updated times
		date "+%s" > /var/cache/2web/web/new/all.cfg
		date "+%s" > /var/cache/2web/web/new/comics.cfg
	done
}
################################################################################
function processDosageExtractor(){
	extractorName=$1
	cacheFilePath=$2
	webDirectory=$3
	INFO "Building dosage list: $extractorName"
	addComic="True"
	if [ "$extractorName" == '' ];then
		addComic="False"
	elif echo "$extractorName" | grep -q "Available comic scrapers:";then
		addComic="False"
	elif echo "$extractorName" | grep -q "Comics tagged with";then
		addComic="False"
	elif echo "$extractorName" | grep -q "Non-english comics are";then
		addComic="False"
	elif echo "$extractorName" | grep -q "supported comics.";then
		addComic="False"
	fi
	extractorName=$(echo "$extractorName" | cut -d' ' -f1)
	if [ $addComic == "True" ];then
		# for each extractor get the website address
		moduleUrl=$(dosage --modulehelp "$extractorName" | grep 'URL:' | cut -d' ' -f3)
		moduleLang=$(dosage --modulehelp "$extractorName" | grep 'Language:' | cut -d' ' -f3)
		linkRow=$(
			echo "<tr>";\
			echo "<td>";\
			echo "$extractorName";\
			echo "</td>";\
			echo "<td>";\
			echo "<a target='_new' href='$moduleUrl'>$moduleUrl</a>";\
			echo "</td>";\
			echo "<td>";\
			echo "$moduleLang";\
			echo "</td>";\
			echo "</tr>";\
		)
		# write the link row
		echo "$linkRow" >> "$cacheFilePath"
	fi
}
################################################################################
function buildDosageList(){
	webDirectory=$1

	# read the supported comics list into a .index file
	cacheFilePath="/var/cache/2web/web/web_cache/comic2web_dosageList.index";
	lockFile="/var/cache/2web/web/web_cache/comic2web_dosageList_COMPLETE.index";
	if ! test -f "$lockFile";then
		extractors=$(dosage --singlelist)
		IFS=$'\n'
		for extractorName in $extractors;do
			# if ran in parallel run the extractor process in the background for this list
				processDosageExtractor "$extractorName" "$cacheFilePath" "$webDirectory"
		done
		# mark the dosage list complete
		touch "$lockFile"
	fi
}
################################################################################
webUpdate(){
	addToLog "INFO" "STARTED Web Update" "$(date)"
	# read the download directory and convert comics into webpages
	# - There are 2 types of directory structures for comics in the download directory
	#   + comicWebsite/comicName/chapter/image.png
	#   + comicWebsite/comicName/image.png

	webDirectory=$(webRoot)
	downloadDirectory="$(libaryPaths | tr -s '\n' | shuf )"
	#downloadDirectory="$(libaryPaths)"
	# add the generated directory cbz and pdf
	#downloadDirectory=$(printf "${downloadDirectory}\n")
	#downloadDirectory=$(printf "${downloadDirectory}$(generatedDir)/cbz2comic/\n")
	#downloadDirectory=$(printf "${downloadDirectory}$(generatedDir)/pdf2comic/\n")
	# clean duplicated newlines and shuffle the libary paths randomly
	#downloadDirectory=$(printf "$downloadDirectory" | tr -s '\n' | shuf )

	ALERT "$downloadDirectory"

	# create the kodi directory
	createDir "$webDirectory/kodi/comics/"

	# create the web directory
	createDir "$webDirectory/comics/"

	# link the homepage
	linkFile "/usr/share/2web/templates/comics.php" "$webDirectory/comics/index.php"

	# link the random poster script
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/comics/randomPoster.php"
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/comics/randomFanart.php"

	# link the kodi directory to the download directory
	#ln -s "$downloadDirectory" "$webDirectory/kodi/comics"

	# check for parallel processing and count the cpus
	if echo "$@" | grep -q -e "--parallel";then
		totalCPUS=$(cpuCount)
	else
		totalCPUS=1
	fi

	if echo "$@" | grep -q -e "--parallel";then
		buildDosageList "$webDirectory" &
	else
		buildDosageList "$webDirectory"
	fi

	totalComics=0

	ALERT "Scanning libary config '$downloadDirectory'"
	echo "$downloadDirectory" | sort | while read comicLibaryPath;do
		ALERT "Scanning Libary Path... '$comicLibaryPath'"
		# read each comicWebsite directory from the download directory
		find "$comicLibaryPath" -mindepth 1 -maxdepth 1 -type d | sort | while read comicWebsitePath;do
			INFO "scanning comic website path '$comicWebsitePath'"
			# build the website directory for the comic path
			#mkdir -p "$webDirectory/comics/$(popPath $comicWebsitePath)"
			# build the website tag index page
			find "$comicWebsitePath" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
				# check the sum for this directory to see if the data has changed
				if checkDirSum "$webDirectory" "$comicNamePath";then

					INFO "link the comics to the kodi directory"
					# link this comic to the kodi directory
					#createDir "$comicNamePath" "$webDirectory/kodi/comics/"

					INFO "scanning comic path '$comicNamePath'"
					# add one to the total comics
					totalComics=$(( $totalComics + 1 ))
					# build the comic index page
					if [ $(find -L "$comicNamePath" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | wc -l) -gt 0 ];then
						INFO "scanning single chapter comic '$comicNamePath'"
						# if this directory contains .jpg or .png files then this is a single chapter comic
						# - build the individual pages for the comic
						# pause execution while no cpus are open
						scanPages "$comicNamePath" "$webDirectory" single &
						waitQueue 0.5 "$totalCPUS"
					else
						# if this is not a single chapter comic then read the subdirectories containing
						#   each of the individual chapters
						INFO "scanning multi chapter comic '$comicNamePath'"
						# reset chapter number for count
						chapterNumber=0
						find "$comicNamePath" -mindepth 1 -maxdepth 1 -type d | sort | while read comicChapterPath;do
							chapterNumber=$(( 10#$chapterNumber + 1 ))
							# add zeros to the chapter as a prefix for correct ordering
							chapterNumber=$(prefixNumber $chapterNumber)
							# check if the chapter should be updated before running through all pages
							# for each chapter build the individual pages
							# pause execution while no cpus are open
							scanPages "$comicChapterPath" "$webDirectory" chapter $chapterNumber &
							waitQueue 0.5 "$totalCPUS"
						done
					fi
					setDirSum "$webDirectory" "$comicNamePath"
				else
					INFO "Already processed '$comicNamePath'"
				fi
			done
			# finish website tag index page
		done
	done
	# block for parallel threads here
	blockQueue 1
	INFO "Writing total Comics "
	echo "$totalComics" > "$webDirectory/comics/totalComics.cfg"
	INFO "Checking for comic index page..."
	# finish building main index page a-z
	linkFile "/usr/share/2web/templates/comics.php" "$webDirectory/comics/index.php"
	# build links to each comic in the index page
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
		# multi chapter comic
		tempComicName="$(popPath "$comicNamePath")"
		#	build the index file for this entry if one does not exist
		if ! test -f "$comicNamePath/comics.index";then
			{
				echo "<a href='/comics/$tempComicName/' class='indexSeries' >"
				echo "<img loading='lazy' src='/comics/$tempComicName/thumb.png' />"
				echo "<div>$tempComicName</div>"
				echo "</a>"
			} > "$comicNamePath/comics.index"
		fi
	done
	# the random index simply uses the main index for comics
	linkFile "$webDirectory/comics/comics.index" "$webDirectory/random/comics.index"
	addToLog "INFO" "FINISHED Web Update" "$(date)"
}
################################################################################
function resetCache(){
	# reset all generated/downloaded content
	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"
	# remove all the index files generated by the website
	find "$webDirectory/comics/" -name "*.index" -delete

	# remove web cache
	rm -rv "$webDirectory/comics/" || INFO "No comic web directory at '$webDirectory/comics/'"

	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | while read comicPath;do
		if [ ! "$comicPath" == "$webDirectory/comicCache/" ];then
			# comic
			echo "rm -rv '$comicPath'"
			rm -rv "$comicPath" || INFO "No path to remove at '$comicPath'"
		fi
	done

	rm -rv "$webDirectory/comicCache/download_"*.index || INFO "No path to remove at '$webDirectory/comicCache/download_*.index'"
	rm -rv "$downloadDirectory/pdf2comic/" || INFO "No path to remove at '$downloadDirectory/pdf2comic/'"
	rm -rv "$downloadDirectory/txt2comic/" || INFO "No path to remove at '$downloadDirectory/txt2comic/'"
	rm -rv "$downloadDirectory/epub2comic/" || INFO "No path to remove at '$downloadDirectory/epub2comic/'"
	rm -rv "$downloadDirectory/cbz2comic/" || INFO "No path to remove at '$downloadDirectory/cbz2comic/'"
	rm -rv "$downloadDirectory/markdown2comic/" || INFO "No path to remove at '$downloadDirectory/markdown2comic/'"
	rm -rv "$downloadDirectory/html2comic/" || INFO "No path to remove at '$downloadDirectory/html2comic/'"

	rm -rv "$webDirectory/kodi/comics/" || INFO "No path to remove at '$webDirectory/kodi/comics/'"
	rm -rv "$webDirectory/kodi/comics_tank/" || INFO "No path to remove at '$webDirectory/kodi/comics_tank/'"
	rm -rv "$webDirectory/new/comic_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/comic_*.index'"
	rm -rv "$webDirectory/random/comic_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/comic_*.index'"
	#
	echo "You MUST remove downloaded comics manually they are stored at:"
	echo "$downloadDirectory"
}
################################################################################
function nuke(){
	webDirectory="$(webRoot)"
	downloadDirectory="$(downloadDir)"
	generatedDirectory="$(generatedRoot)"
	# remove generated documents to prevent adding cached generated versions of removed files after nuke
	locations="cbz2comic pdf2comic txt2comic epub2comic markdown2comic html2comic epub2comic ps2comic"
	# delete intermediate conversion directories
	for location in $locations;do
		rm -rv "$generatedDirectory/comics/$location/" || INFO "No path to remove at '$generatedDirectory/comics/$location/'"
	done
	# remove new and random indexes
	rm -rv "$webDirectory/new/comic_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/comic_*.index'"
	rm -rv "$webDirectory/random/comic_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/comic_*.index'"
	# kodi directories
	rm -rv "$webDirectory/kodi/comics/" || INFO "No path to remove at '$webDirectory/kodi/comics/'"
	rm -rv "$webDirectory/kodi/comics_tank/" || INFO "No path to remove at '$webDirectory/kodi/comics_tank/'"
	# remove comic directory and indexes
	rm -rv $webDirectory/comics/*
	rm -rv $webDirectory/new/comics.index
	rm -rv $webDirectory/comicCache/
	rm -rv $webDirectory/random/comics.index
	rm -rv $webDirectory/sums/comic2web_*.cfg || echo "No file sums found..."
	# remove sql data
	sqlite3 $webDirectory/data.db "drop table comics;"
	# remove widgets cached
	rm -v $webDirectory/web_cache/widget_random_comics.index
	rm -v $webDirectory/web_cache/widget_new_comics.index
}
################################################################################
function upgrade-pip(){
	# upgrade gallery-dl pip packages
	pipInstallPath="/var/cache/2web/downloads/pip"
	# create the pip install paths
	createDir "$pipInstallPath/gallery-dl/"
	createDir "$pipInstallPath/dosage/"
	# upgrade streamlink and yt-dlp pip packages
	pip3 install --target "$pipInstallPath/gallery-dl/" --upgrade gallery-dl
	pip3 install --target "$pipInstallPath/dosage/" --upgrade dosage
}
################################################################################
main(){
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		lockProc "comic2web"
		checkModStatus "comic2web"
		webUpdate "$@"
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		lockProc "comic2web"
		checkModStatus "comic2web"
		update "$@"
	elif [ "$1" == "-e" ] || [ "$1" == "--enable" ] || [ "$1" == "enable" ] ;then
		enableMod "comic2web"
	elif [ "$1" == "-d" ] || [ "$1" == "--disable" ] || [ "$1" == "disable" ] ;then
		disableMod "comic2web"
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		nuke
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade the pip packages if the module is enabled
		checkModStatus "comic2web"
		upgrade-pip
	elif [ "$1" == "--force-upgrade" ];then
		# force upgrade or install of all the pip packages
		upgrade-pip
	elif [ "$1" == "-c" ] || [ "$1" == "--convert" ] || [ "$1" == "convert" ] ;then
		# comic2web --convert filePath
		convertImage "$3"
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/comic2web.txt"
	elif [ "$1" == "-v" ] || [ "$1" == "--version" ] || [ "$1" == "version" ];then
		echo -n "Build Date: "
		cat /usr/share/2web/buildDate.cfg
		echo -n "comic2web Version: "
		cat /usr/share/2web/version_comic2web.cfg
	else
		lockProc "comic2web"
		checkModStatus "comic2web"
		update "$@"
		webUpdate "$@"
		#main --help $@
		# on default execution show the server links at the bottom of output
		showServerLinks
		echo "Module Links"
		drawLine
		echo "http://$(hostname).local:80/comics/"
		drawLine
		echo "http://$(hostname).local:80/settings/comics.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
