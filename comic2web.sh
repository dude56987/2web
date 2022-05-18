#! /bin/bash
################################################################################
# enable debug log
#set -x
################################################################################
ALERT(){
	echo;
	echo "$1";
	echo;
}
################################################################################
drawLine(){
	width=$(tput cols)
	buffer="=========================================================================================================================================="
	output="$(echo -n "$buffer" | cut -b"1-$(( $width - 1 ))")"
	printf "$output\n"
}
################################################################################
linkFile(){
	# link file if it is a link
	if ! test -L "$2";then
		ln -sf "$1" "$2"
	fi
}
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/2web/nfo/web.cfg ];then
		webDirectory=$(cat /etc/2web/nfo/web.cfg)
	else
		chown -R www-data:www-data "/var/cache/2web/cache/"
		echo "/var/cache/2web/cache/" > /etc/2web/nfo/web.cfg
		webDirectory="/var/cache/2web/cache/"
	fi
	# check for a trailing slash appended to the path
	if [ "$(echo "$webDirectory" | rev | cut -b 1)" == "/" ];then
		# rip the last byte off the string and return the correct path, WITHOUT THE TRAILING SLASH
		webDirectory="$(echo "$webDirectory" | rev | cut -b 2- | rev )"
	fi
	echo "$webDirectory"
}
################################################################################
function cacheCheck(){

	filePath="$1"
	cacheDays="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -mtime "+$cacheDays") ]];then
			# the file is more than "$2" days old, it needs updated
			INFO "File is to old, update the file $1"
			return 0
		else
			# the file exists and is not old enough in cache to be updated
			INFO "File in cache, do not update $1"
			return 1
		fi
	else
		# the file does not exist, it needs created
		INFO "File does not exist, it must be created $1"
		return 0
	fi
}
################################################################################
getDirSum(){
	line=$1
	# check the libary sum against the existing one
	totalList=$(find "$line" | sort)
	# convert lists into md5sum
	tempLibList="$(echo -n "$totalList" | md5sum | cut -d' ' -f1)"
	# write the md5sum to stdout
	echo "$tempLibList"
}
################################################################################
function INFO(){
	width=$(tput cols)
	# cut the line to make it fit on one line using ncurses tput command
	buffer="                                                                                "
	# - add the buffer to the end of the line and cut to terminal width
	#   - this will overwrite any previous text wrote to the line
	#   - cut one off the width in order to make space for the \r
	output="$(echo -n "[INFO]: $1$buffer" | cut -b"1-$(( $width - 1 ))")"
	# print the line
	printf "$output\r"
	#echo "$output"
	#printf "$output\n"
}
################################################################################
function ERROR(){
	output=$1
	printf "[ERROR]: $output\n"
}
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
function downloadDir(){
	if [ ! -f /etc/2web/comics/download.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/comic2web/"
		} >> "/etc/2web/comics/download.cfg"
	fi
	# write path to console
	cat "/etc/2web/comics/download.cfg"
}
################################################################################
function libaryPaths(){
	# add the download directory to the paths
	echo "$(downloadDir)"
	# check for server libary config
	if [ ! -f /etc/2web/comics/libaries.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/2web/comics/"
		} >> "/etc/2web/comics/libaries.cfg"
	fi
	# write path to console
	cat "/etc/2web/comics/libaries.cfg"
	# create a space just in case none exists
	printf "\n"
	# read the additional configs
	find "/etc/2web/comics/libaries.d/" -mindepth 1 -maxdepth 1 -type f -name "*.cfg" | shuf | while read libaryConfigPath;do
		cat "$libaryConfigPath"
		# create a space just in case none exists
		printf "\n"
	done
}
################################################################################
function update(){
	#DEBUG
	#set -x
	# this will launch a processing queue that downloads updates to comics
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/comics/sources.cfg;then
		# if no config exists create the default config
		{
		echo "##################################################"
		echo "# Example Config"
		echo "##################################################"
		echo "# - You can use sources from remote http servers"
		echo "# - A generic extractor is used so many comic/manga"
		echo "#   websites should work if you try them."
		echo "#"
		echo "#  ex."
		echo "#    "https://xkcd.com/
		echo "##################################################"
		# write the new config from the path variable
		echo "https://xkcd.com/"
		} > /etc/2web/comics/sources.cfg
	fi
	# load sources
	comicSources=$(grep -v "^#" /etc/2web/comics/sources.cfg)
	comicSources=$(echo -e "$comicSources\n$(grep -v --no-filename "^#" /etc/2web/comics/sources.d/*.cfg)")
	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	downloadDirectory="$(downloadDir)"
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make comics directory
	createDir "$webDirectory/comics/"
	# create web and cache directories
	createDir "$webDirectory/comicCache/"
	# remove mark files older than 40 days, this will cause the comic to be updated
	find "$webDirectory/comicCache/" -type f -name "download_*.index" -mtime +40 -delete
	# clean the cache of old files
	# scan the sources
	ALERT "Comic Download Sources: $comicSources"
	#for comicSource in $comicSources;do
	echo "$comicSources" | while read comicSource;do
		# generate a md5sum for the source
		comicSum=$(echo "$comicSource" | md5sum | cut -d' ' -f1)
		# do not process the comic if it is still in the cache
		# - Cache removes files older than x days
		if ! test -f "$webDirectory/comicCache/download_$comicSum.index";then
			# if the comic is not cached it should be downloaded
			# - gallery-dl with json output will download into the $downloadDirectory/
			# - niceload --net will sleep a process when the network is overloaded
			#sem --bg --retries 2 --no-notice --ungroup --jobs 1 --id downloadQueue "echo 'Processing...';sleep 15;gallery-dl --write-metadata --dest '$downloadDirectory' '$comicSource'"
			#--exec 'convert {} {}.png && rm {}'
			#/usr/local/bin/gallery-dl --write-metadata --exec 'convert {} {}.jpg && rm {}' --dest "$downloadDirectory" "$comicSource"
			#/usr/local/bin/gallery-dl --write-metadata --exec 'convert {} {}.jpg' --dest "$downloadDirectory" "$comicSource" && touch "$webDirectory/comicCache/download_$comicSum.index"
			/usr/local/bin/gallery-dl --write-metadata --dest "$downloadDirectory" "$comicSource" && touch "$webDirectory/comicCache/download_$comicSum.index"
			#/usr/local/bin/gallery-dl --write-metadata --exec '/usr/bin/comic2web convert {}' --dest "$downloadDirectory" "$comicSource"
			# download unfinished chapters
			#/usr/local/bin/gallery-dl --chapter-range "$downloadChapters" --write-metadata --dest "$downloadDirectory" "$comicSource"
			# after download mark the download to have been successfully cached
			#touch "$webDirectory/comicCache/download_$comicSum.index"
		fi
	done
	# check for pdf files in comic libaries
	comicLibaries="$(libaryPaths | tr -s '\n' | shuf )"
	# first convert epub files to pdf files
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.txt' | sort | while read txtFilePath;do
			txtComicName=$(popPath "$txtFilePath" | sed "s/.txt//g")
			# only extract the cbz once
			if ! test -d "${downloadDirectory}txt2comic/$txtComicName/$txtComicName.pdf";then
				mkdir -p "${downloadDirectory}txt2comic/$txtComicName/"
				# extract the cbz file to the download directory
				INFO "Found txt '$txtComicName', converting to comic book..."
				# convert epub files into pdf files to be converted below
				cat "$txtFilePath" | txt2html --style_url "http://localhost/style.css" | wkhtmltopdf - "${downloadDirectory}txt2comic/$txtComicName/$txtComicName.pdf"
				chown -R www-data:www-data "${downloadDirectory}txt2comic/$txtComicName/"
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
				if ! test -d "${downloadDirectory}epub2comic/$epubComicName.pdf";then
					mkdir -p "${downloadDirectory}epub2comic/"
					# extract the cbz file to the download directory
					INFO "Found epub '$epubComicName', converting to comic book..."
					# allow ebook convert to run as root
					# convert epub files into pdf files to be converted below
					export QTWEBENGINE_CHROMIUM_FLAGS="--no-sandbox" && ebook-convert "$epubFilePath" "${downloadDirectory}epub2comic/$epubComicName/$epubComicName.pdf"
					chown -R www-data:www-data "${downloadDirectory}epub2comic/$epubComicName/"
				fi
			done
		done
	fi
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each pdf file found in the pdf libary locations
		find "$comicLibaryPath" -type f -name '*.pdf' | sort | while read pdfFilePath;do
			pdfComicName=$(popPath "$pdfFilePath" | sed "s/.pdf//g")
			# only extract the pdf once
			if ! test -d "${downloadDirectory}pdf2comic/$pdfComicName/";then
				mkdir -p "${downloadDirectory}pdf2comic/$pdfComicName/"
				# extract the pdf file to the download directory
				INFO "Found pdf '$pdfComicName', converting to comic book..."
				# - load the pdf file with its filename as the comic name into the comic download directory
				pdftoppm "$pdfFilePath" -jpeg -cropbox "${downloadDirectory}pdf2comic/$pdfComicName/$pdfComicName"
				# change ownership to server
				chown -R www-data:www-data "${downloadDirectory}pdf2comic/$pdfComicName/"
				# get comic book pages
				data=$(find "${downloadDirectory}pdf2comic/$pdfComicName/" -type f -name '*.jpg')
				dataLength=$(echo "$data" | wc -l)
				counter=0
				# trim all whitespace from image files
				#find "$data" -type f -name '*.jpg' | sort | while read pdfImageFilePath;do
				find "${downloadDirectory}pdf2comic/$pdfComicName/" -type f -name '*.jpg' | sort | while read pdfImageFilePath;do
					INFO "Trimming whitespace from $pdfComicName page $counter/$dataLength"
					# trim the whitespace
					convert "$pdfImageFilePath" -fuzz '10%' -trim "$pdfImageFilePath"
					# add a border to the edge of the image
					convert "$pdfImageFilePath" -matte -bordercolor white -border 15 "$pdfImageFilePath"
					counter=$(( $counter + 1 ))
				done
			fi
		done
	done
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.cbz' | sort | while read cbzFilePath;do
			cbzComicName=$(popPath "$cbzFilePath" | sed "s/.cbz//g")
			# only extract the cbz once
			if ! test -d "$downloadDirectory/cbz2comic/$cbzComicName/";then
				mkdir -p "$downloadDirectory/cbz2comic/$cbzComicName/"
				# extract the cbz file to the download directory
				INFO "Found cbz '$cbzComicName', converting to comic book..."
				# - load the cbz file with its filename as the comic name into the comic download directory
				unzip "$cbzFilePath" -d "$downloadDirectory/cbz2comic/$cbzComicName/"
				chown -R www-data:www-data "${downloadDirectory}cbz2comic/$cbzComicName/"
			fi
		done
	done
	echo "$comicLibaries" | sort | while read comicLibaryPath;do
		# for each cbz file found in the cbz libary locations
		find "$comicLibaryPath" -type f -name '*.zip' | sort | while read cbzFilePath;do
			cbzComicName=$(popPath "$cbzFilePath" | sed "s/.zip//g")
			# only extract the cbz once
			if ! test -d "$downloadDirectory/cbz2comic/$cbzComicName/";then
				mkdir -p "$downloadDirectory/cbz2comic/$cbzComicName/"
				# extract the cbz file to the download directory
				INFO "Found cbz '$cbzComicName', converting to comic book..."
				# - load the cbz file with its filename as the comic name into the comic download directory
				unzip "$cbzFilePath" -d "$downloadDirectory/cbz2comic/$cbzComicName/"
				chown -R www-data:www-data "${downloadDirectory}cbz2comic/$cbzComicName/"
			fi
		done
	done
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
		convert "$fileName" "$newName"
	fi

	return 0
}
################################################################################
cleanText(){
	echo "$1" | tr -d '#`' | tr -d "'" | sed "s/_/ /g"
	return
	# remove punctuation from text, remove leading whitespace, and double spaces
	if [ -f /usr/bin/inline-detox ];then
		echo "$1" | inline-detox --remove-trailing | sed "s/-/ /g" | sed -e "s/^[ \t]*//g" | tr -s ' ' | sed "s/\ /_/g" | tr -d '#`' | tr -d "'" | sed "s/_/ /g"
	else
		# use sed to remove punctuation
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g" | sed "s/\ /_/g" | tr -d '#`'
	fi
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
	find -L "$pagesDirectory" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
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
			#tempComicChapter="$(pickPath "$imagePath" 2)"
			tempComicChapter=$pageChapter
			if cacheCheck "$webDirectory/comics/$tempComicName/$tempComicChapter/index.php" 10;then
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
					totalPages=$(find -L "$pagesDirectory" -maxdepth 1 -mindepth 1 -name "*.jpg" | wc -l)
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
			if cacheCheck "$webDirectory/comics/$tempComicName/index.php" 10;then
				createDir "$webDirectory/comics/$tempComicName/"
				# link the image file to the web directory
				#echo "[INFO]: Linking single chapter comic $tempComicName"
				# if the total pages has not yet been stored
				if ! test -f "$webDirectory/comics/$tempComicName/totalPages.cfg";then
					# find the total number of pages in the chapter
					totalPages=$(find -L "$pagesDirectory" -maxdepth 1 -mindepth 1 -name "*.jpg" | wc -l)
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
			#set -x
			convert "$imagePath" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber-thumb.png"
			#set +x
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
		# remove : as it breaks web paths
		#pageComicName=$(echo "$pageComicName" | sed "s/:/~/g" )

		# create the thumbnail for the image, otherwise it will nuke the server reading the HQ image files on loading index pages
		if ! test -f "$webDirectory/comics/$pageComicName/$pageNumber-thumb.png";then
			#set -x
			convert "$imagePath" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/$pageNumber-thumb.png"
			#set +x
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
				convert "$webDirectory/comics/$pageComicName/$pageChapterName/0001.jpg" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/$pageChapterName/thumb.png"
			fi
		fi
		# if no thumbnail exists then
		if ! test -f "$webDirectory/comics/$pageComicName/thumb.png";then
			if [ $isChapter = true ];then
				# create a thumb for the comic
				convert "$webDirectory/comics/$pageComicName/$pageChapterName/0001.jpg" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/thumb.png"
			else
				convert "$webDirectory/comics/$pageComicName/0001.jpg" -filter triangle -resize 150x200 "$webDirectory/comics/$pageComicName/thumb.png"
			fi
		fi
	fi
	if [ $isChapter = true ];then
		# link the image file into the web directory
		pagePath="$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.html"
	else
		# single chapter comic
		pagePath="$webDirectory/comics/$pageComicName/$pageNumber.html"
	fi
	# if no zip directory exists then create the zip directory
	createDir "$webDirectory/kodi/comics_tank/$pageComicName/"
	# write the downloadable .zip file
	# zip requires the current working directory be changed
	if [ $isChapter = true ];then
		cd "$webDirectory/kodi/comics_tank/$pageComicName/"
		linkFile "$imagePath" "$pageComicName-$pageChapterName-$pageNumber.jpg"
		#zip -jquT -9 "../$pageComicName.cbz" "$pageComicName-$pageChapterName-$pageNumber.jpg"
		if [  $((10#$nextPage)) -gt $totalPages ];then
			if [[  "10#$pageChapterName" -ge "10#$totalChapters" ]];then
				#set -x
				# if this is the last page create the zip file
				zip -jrqT -9 "$webDirectory/comics/$pageComicName/$pageComicName.cbz" "."
				#set +x
			fi
		fi
		#zip -9 --symlinks "$webDirectory/comics/$pageComicName/$pageComicName.zip" "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.jpg"
	else
		cd "$webDirectory/kodi/comics_tank/$pageComicName/"
		linkFile "$imagePath" "$pageComicName-$pageNumber.jpg"
		#zip -jquT -9 "../$pageComicName.cbz" "$pageComicName-$pageNumber.jpg"
		if [  $((10#$nextPage)) -gt $totalPages ];then
			# if this is the last page create the zip file
			#set -x
			zip -jrqT -9 "$webDirectory/comics/$pageComicName/$pageComicName.cbz" "."
			#set +x
		fi
		#zip -9 --symlinks "$webDirectory/comics/$pageComicName/$pageComicName.zip" "$webDirectory/comics/$pageComicName/$pageNumber.jpg"
	fi
	# check next and previous pages to make sure they can be linked to
	# write the webpage for the individual image
	{
		echo "<html class='comicPageBackground'>"
		echo "<head>"
	} > "$pagePath"
	if [ $isChapter = true ];then
		echo "<title>$pageComicName - Chapter $((10#$pageChapter))/$totalChapters - Page $((10#$pageNumber))/$totalPages</title>" >> "$pagePath"
		echo "<link rel='stylesheet' href='../../style.css'>" >> "$pagePath"
	else
		echo "<title>$pageComicName - Page $((10#$pageNumber))/$totalPages</title>" >> "$pagePath"
		echo "<link rel='stylesheet' href='../style.css'>" >> "$pagePath"
	fi
	{
		echo "<script>"
		cat /usr/share/2web/2web.js
		# add a listener to pass the key event into a function
		echo "function setupKeys() {"
		echo "	document.body.addEventListener('keydown', function(event){"
		echo "		const key = event.key;"
		echo "		switch (key){"
		echo "			case 'ArrowLeft':"
		echo "				window.location.href='./$previousPage.html';"
		echo "				break;"
		echo "			case 'ArrowRight':"
		echo "				window.location.href='./$nextPage.html';"
		echo "				break;"
		echo "			case 'ArrowUp':"
		echo "				window.location.href='index.php';"
		echo "				break;"
		echo "			"
		echo "		}"
		echo "	});"
		echo "}"
		# fullscreen function
		echo "function toggleFullScreen() {"
		echo "	if (!document.fullscreenElement) {"
		echo "			document.documentElement.requestFullscreen();"
		echo "	} else {"
		echo "		if (document.exitFullscreen) {"
		echo "			document.exitFullscreen();"
		echo "		}"
		echo "	}"
		echo "}"
		echo "</script>"
		echo "</head>"
		echo "<body onload='setupKeys();'>"
	} >> "$pagePath"
	if [ $width -gt $height ];then
		#echo "[DEBUG]: landscape image found WxH $width > $height"
		{
			echo "<div id='comicPane' class='comicWidePane' style='$tempStyleThumb'>"
			echo "<div id='comicThumbPane' class='comicThumbWidePane' style='$tempStyle'>"
		} >> "$pagePath"
	else
		#echo "[DEBUG]: portrait image found  WxH $width < $height"
		{
			echo "<div id='comicPane' class='comicPane' style='$tempStyleThumb'>"
			echo "<div id='comicThumbPane' class='comicThumbPane' style='$tempStyle'>"
		} >> "$pagePath"
	fi
	{
		if [ $((10#$previousPage)) -eq 0 ];then
			echo "	<a href='index.php' class='comicPageButton left'>"
			echo "		&#8617;"
			echo "	<br>"
			echo "		Back"
			echo "	</a>"
		else
			echo "	<a href='$previousPage.html' class='comicPageButton left'>"
			echo "		&#8617;"
			echo "		<br>"
			echo "		<span class='comicPageNumbers'>"
			echo "			$((10#$pageNumber))/$totalPages"
			echo "		</span>"
			echo "	</a>"
		fi
		if [  $((10#$nextPage)) -gt $totalPages ];then
			echo "	<a href='index.php' class='comicPageButton right'>"
			echo "		&#8618;"
			echo "		<br>"
			echo "		Back"
			echo "	</a>"
		else
			echo "	<a href='$nextPage.html' class='comicPageButton right'>"
			echo "		&#8618;"
			echo "		<br>"
			echo "		<span class='comicPageNumbers'>"
			echo "			$((10#$pageNumber))/$totalPages"
			echo "		</span>"
			echo "	</a>"
		fi
		#echo "	<a class='comicHomeButton comicPageButton center' href='../../..'>"
		#echo "		HOME"
		#echo "	</a>"
		echo "	<a class='comicIndexButton comicPageButton center' href='index.php#$pageNumber'>"
		echo "		&uarr;"
		echo "	</a>"

		if [ $isChapter = true ];then
			echo "	<div class='comicPagePopup center' href='index.php'>"
			echo "		Chapter $((10#$pageChapter))/$totalChapters <hr> Page $((10#$pageNumber))/$totalPages"
			echo "	</div>"
		else
			echo "	<div class='comicPagePopup center' href='index.php'>"
			echo "		Page $((10#$pageNumber))/$totalPages"
			echo "	</div>"
		fi

		#echo "<div class='comicFooter'>"
		#echo "	$pageNumber"
		#echo "</div>"
		echo "</div>"
		echo "</div>"
		echo "<span id='bottom'></span>"
		echo "</body>"
		echo "</html>"
	} >> "$pagePath"
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
		# start building the comic index since this is the last page
		{
			echo "<html>"
			echo "<head>"
			echo "<link rel='stylesheet' href='../style.css'>"
			echo "<style>"
			echo "html{ background-image: url(\"thumb.png\") }"
			echo "</style>"
			echo "<script>"
			cat /usr/share/2web/2web.js
			# add a listener to pass the key event into a function
			echo "function setupKeys() {"
			echo "	document.body.addEventListener('keydown', function(event){"
			echo "		const key = event.key;"
			echo "		switch (key){"
			echo "			case 'ArrowRight':"
			# if it is a chapter
			if [ $isChapter = true ];then
				echo "				window.location.href='0001/';"
			else
				echo "				window.location.href='0001.html';"
			fi
			echo "				break;"
			echo "			case 'ArrowUp':"
			echo "				window.location.href='..';"
			echo "				break;"
			echo "			case 'ArrowDown':"
			if [ $isChapter = true ];then
				echo "				window.location.href='0001/';"
			else
				echo "				window.location.href='0001.html';"
			fi
			echo "				break;"
			echo "			"
			echo "		}"
			echo "	});"
			echo "}"
			echo "</script>"
			echo "</head>"
			echo "<body onload='setupKeys();'>"
			#create top jump button
			echo "<a href='#' id='topButton' class='button'>&uarr;</a>";
			echo "<?PHP";
			echo "include('../../header.php')";
			echo "?>";
			#cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
			# add the search box
			echo " <input id='searchBox' class='searchBox' type='text'"
			echo " onkeyup='filter(\"indexSeries\")' placeholder='Search...' >"
			echo "<hr>"
			echo "<div class='titleCard'>"
			echo "<h1>$pageComicName</h1>"
			echo "<a class='button' href='$pageComicName.cbz'>"
			echo "<span class='downloadIcon'>⮋</span>"
			echo "Download"
			# get the file size and list it in the download button
			echo $(	du -sh "$webDirectory/comics/$pageComicName/$pageComicName.cbz" | cut -f1 );
			echo "</a>"
			# get the total comic book pages, pages are jpg files, thumbnails are png files
			totalComicBookPages=0
			if [ $isChapter = true ];then
				#find "$webDirectory/comics/$pageComicName/" -type f -name 'totalPages.cfg' | while read comicPageTotalEntry;do
				for comicPageTotalEntry in "$webDirectory/comics/$pageComicName/"*/totalPages.cfg;do
					tempTotalComicPages=$(cat "$comicPageTotalEntry" )
					if [ $tempTotalComicPages -gt 0 ];then
						totalComicBookPages=$(( "$totalComicBookPages" + "$tempTotalComicPages" ))
					fi
				done
			else
				tempTotalComicPages=$(cat "$webDirectory/comics/$pageComicName/totalPages.cfg" )
				if [ $tempTotalComicPages -gt 0 ];then
					totalComicBookPages=$(( "$totalComicBookPages" + "$tempTotalComicPages" ))
				fi
			fi
			echo "<span>Total Pages: $totalComicBookPages </span>"
			echo "</div>"
			echo "<div class='settingListCard'>"
		} > "$webDirectory/comics/$pageComicName/index.php"
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
				{
					echo "<a href='./$tempChapterName/' class='indexSeries' >"
					echo "<img loading='lazy' src='./$tempChapterName/thumb.png' />"
					if echo "$trueChapterTitle" | grep -q "[[:alpha:]]";then
						# this is a text title
						echo "<div>"
						echo "$trueChapterTitle"
					else
						# this is a number only
						echo "<div>Chapter "
						echo "$tempChapterName"
					fi
					echo "</div>"
					echo "</a>"
				} >> "$webDirectory/comics/$pageComicName/index.php"
				# build the index for the chapter displaying all the images
				{
					echo "<html>"
					echo "<head>"
					if [ $isChapter = true ];then
						echo "<title>$pageComicName - Chapter $((10#$pageChapter))/$totalChapters</title>"
					else
						echo "<title>$pageComicName Oneshot</title>"
					fi
					echo "</title>"
					echo "<style>"
					echo "html{ background-image: url(\"thumb.png\") }"
					echo "</style>"
					echo "<link rel='stylesheet' href='../../style.css'>"
					echo "<script>"
					cat /usr/share/2web/2web.js
					# add a listener to pass the key event into a function
					echo "function setupKeys() {"
					echo "	document.body.addEventListener('keydown', function(event){"
					echo "		const key = event.key;"
					echo "		switch (key){"
					echo "			case 'ArrowUp':"
					echo "				window.location.href='..';"
					echo "				break;"
					echo "			case 'ArrowLeft':"
					echo "				window.location.href='../$tempPreviousChapterName';"
					echo "				break;"
					echo "			case 'ArrowRight':"
					echo "				window.location.href='../$tempNextChapterName';"
					echo "				break;"
					echo "			case 'ArrowDown':"
					echo "				window.location.href='0001.html';"
					echo "				break;"
					echo "			"
					echo "		}"
					echo "	});"
					echo "}"
					echo "</script>"
					echo "</head>"
					echo "<body onload='setupKeys();'>"
					#create top jump button
					echo "<a href='#' id='topButton' class='button'>&uarr;</a>";
					echo "<?PHP";
					echo "include('../../../header.php')";
					echo "?>";
					#cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\/..\//g"
					echo "<div class='titleCard'>"
					echo "<a class='left button' href='../$tempPreviousChapterName'>Back</a>"
					echo "<a class='right button' href='../$tempNextChapterName'>Next</a>"
					echo "<div>"
					echo "	<a class='button comicTitleButton' href='..'>$pageComicName</a>"
					echo "	<h2>Chapter $(( 10#$tempChapterName ))/$totalChapters</h2>"
					echo "<div class='chapterTitleBox'>"
					if echo "$trueChapterTitle" | grep -q "[[:alpha:]]";then
						echo "$trueChapterTitle"
					fi
					#cat "$webDirectory/comics/$pageComicName/$tempChapterName/chapterTitle.cfg"
					echo "</div>"
					echo "</div>"
					echo "</div>"
					echo "<div class='settingListCard'>"
				} > "$webDirectory/comics/$pageComicName/$tempChapterName/index.php"

				# build the individual image index for this chapter
				find -L "$webDirectory/comics/$pageComicName/$tempChapterName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
					# single chapter of a multi chapter comic
					tempImageName="$(popPath "$imagePath" | sed "s/\.jpg//g")"

					{
						echo "<a id='$tempImageName' href='./$tempImageName.html' class='indexSeries' >"
						echo "<img loading='lazy' src='./$tempImageName-thumb.png' />"
						echo "<div>$tempImageName</div>"
						echo "</a>"
					} >> "$webDirectory/comics/$pageComicName/$tempChapterName/index.php"
				done
				# finish the chapter index
				{
					echo "</div>"
					echo "<?PHP";
					echo "include('../../../header.php')";
					echo "?>";
					#cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\/..\//g"
					echo "</body>"
					echo "</html>"
				} >> "$webDirectory/comics/$pageComicName/$tempChapterName/index.php"
			done
		else
			# if it is not a chapter and is only a page link
			find -L "$webDirectory/comics/$pageComicName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
				# single chapter comic image
				tempName="$(popPath "$imagePath" | sed "s/\.jpg//g")"
				{
					echo "<a id='$tempName' href='./$tempName.html' class='indexSeries' >"
					echo "<img loading='lazy' src='./$tempName-thumb.png' />"
					echo "<div>$tempName</div>"
					echo "</a>"
				} >> "$webDirectory/comics/$pageComicName/index.php"
			done
		fi
		{
			echo "</div>"
			#cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
			echo "<?PHP";
			echo "include('../../header.php')";
			echo "?>";
			echo "</body>"
			echo "</html>"
		} >> "$webDirectory/comics/$pageComicName/index.php"
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
webUpdate(){
	# read the download directory and convert comics into webpages
	# - There are 2 types of directory structures for comics in the download directory
	#   + comicWebsite/comicName/chapter/image.png
	#   + comicWebsite/comicName/image.png

	webDirectory=$(webRoot)
	#downloadDirectory="$(downloadDir)"
	downloadDirectory="$(libaryPaths | tr -s '\n' | shuf )"

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
				# check the md5sum for this directory to see if the data has changed

				INFO "link the comics to the kodi directory"
				# link this comic to the kodi directory
				if test -d "$webDirectory/kodi/comics/";then
					ln -s "$comicNamePath" "$webDirectory/kodi/comics/"
				fi
				INFO "scanning comic path '$comicNamePath'"
				# add one to the total comics
				totalComics=$(( $totalComics + 1 ))
				# build the comic index page
				if [ $(find -L "$comicNamePath" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | wc -l) -gt 0 ];then
					INFO "scanning single chapter comic '$comicNamePath'"
					# if this directory contains .jpg or .png files then this is a single chapter comic
					# - build the individual pages for the comic
					scanPages "$comicNamePath" "$webDirectory" single
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
						scanPages "$comicChapterPath" "$webDirectory" chapter $chapterNumber
					done
				fi
				# search for new comics
				find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
					# create the comic index files here in order to allow dynamic index view during updates
					tempComicName="$(popPath "$comicNamePath")"
					if ! test -f "$comicNamePath/comic.index";then
						{
							echo "<a href='/comics/$tempComicName/' class='indexSeries' >"
							echo "<img loading='lazy' src='/comics/$tempComicName/thumb.png' />"
							echo "<div>$tempComicName</div>"
							echo "</a>"
						} > "$comicNamePath/comics.index"
					fi

					# add the comic to the main comic index since it has been updated
					echo "$webDirectory/comics/$tempComicName/comics.index" >> "$webDirectory/comics/comics.index"

					# link the new comic data to the new directory
					linkFile "$webDirectory/comics/$tempComicName/comics.index" "$webDirectory/new/comic_$tempComicName.index"
					# add the updated show to the new shows index
					echo "$webDirectory/new/comic_$tempComicName.index" >> "$webDirectory/new/comics.index"
				done
			done
			# finish website tag index page
		done
	done
	INFO "Writing total Comics "
	echo "$totalComics" > "$webDirectory/comics/totalComics.cfg"
	INFO "Checking for comic index page..."
	# finish building main index page a-z
	linkFile "/usr/share/2web/templates/comics.php" "$webDirectory/comics/index.php"
	linkFile "/usr/share/2web/templates/randomComics.php" "$webDirectory/randomComics.php"
	linkFile "/usr/share/2web/templates/updatedComics.php" "$webDirectory/updatedComics.php"
	# build links to each comic in the index page
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
		# multi chapter comic
		tempComicName="$(popPath "$comicNamePath")"
		#	build the index file for this entry if one does not exist
		if ! test -f "$comicNamePath/comic.index";then
			{
				echo "<a href='/comics/$tempComicName/' class='indexSeries' >"
				echo "<img loading='lazy' src='/comics/$tempComicName/thumb.png' />"
				echo "<div>$tempComicName</div>"
				echo "</a>"
			} > "$comicNamePath/comic.index"
		fi
		if ! test -f "$webDirectory/new/comic_$tempComicName.index";then
			{
				echo "<a href='/comics/$tempComicName/' class='indexSeries' >"
				echo "<img loading='lazy' src='/comics/$tempComicName/thumb.png' />"
				echo "<div>$tempComicName</div>"
				echo "</a>"
			} > "$webDirectory/new/comic_$tempComicName.index"
		fi
	done
}
################################################################################
function resetCache(){
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
	rm -rv "$webDirectory/kodi/comics/" || INFO "No path to remove at '$webDirectory/kodi/comics/'"
	rm -rv "$webDirectory/new/comic_*.index" || INFO "No path to remove at '$webDirectory/kodi/new/comic_*.index'"
}
################################################################################
lockProc(){
	# check if system is active
	if test -f "/tmp/comic2web.active";then
		# system is already running exit
		echo "[INFO]: comic2web is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/comic2web.active'."
		exit
	else
		# set the active flag
		touch /tmp/comic2web.active
		# create a trap to remove nfo2web lockfile
		trap "rm -v /tmp/comic2web.active" EXIT
	fi
}
################################################################################
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		# lock the process
		lockProc
		webUpdate
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		# lock the process
		lockProc
		update
	elif [ "$1" == "-n" ] || [ "$1" == "--nuke" ] || [ "$1" == "nuke" ] ;then
		# lock the process
		lockProc
		rm -rv $(webRoot)/comics/*
		rm -rv $(webRoot)/new/comic_*.index
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		# lock the process
		lockProc
		resetCache
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade gallery-dl pip packages
		pip3 install --upgrade gallery-dl
	elif [ "$1" == "-c" ] || [ "$1" == "--convert" ] || [ "$1" == "convert" ] ;then
		# comic2web --convert filePath
		convertImage "$3"
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/comic2web.txt"
	else
		# lock the process
		lockProc
		# gen prelem website
		webUpdate
		# update sources
		update
		# update webpages
		webUpdate
		# display the help
		main --help
		# on default execution show the server links at the bottom of output
		drawLine
		echo "http://$(hostname).local:80/"
		drawLine
		echo "http://$(hostname).local:80/comics/"
		drawLine
	fi
}
################################################################################
main "$@"
exit
