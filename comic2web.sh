#! /bin/bash
################################################################################
# enable debug log
set -x
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/nfo2web/web.cfg ];then
		webDirectory=$(cat /etc/nfo2web/web.cfg)
	else
		mkdir -p /var/cache/nfo2web/web/
		chown -R www-data:www-data "/var/cache/nfo2web/web/"
		echo "/var/cache/nfo2web/web" > /etc/nfo2web/web.cfg
		webDirectory="/var/cache/nfo2web/web"
	fi
	mkdir -p "$webDirectory"
	echo "$webDirectory"
}
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
function update(){
	# this will launch a processing queue that downloads updates to comics
	echo "Loading up sources..."
	# check for defined sources
	if ! [ -f /etc/comic2web/sources.cfg ];then
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
		} > /etc/comic2web/sources.cfg
	fi
	# load sources
	comicSources=$(grep -v "^#" /etc/comic2web/sources.cfg)
	comicSources="$comicSources\n$(grep -v "^#" /etc/comic2web/sources.d/*.cfg)"
	################################################################################
	webDirectory=$(webRoot)
	downloadDirectory="/var/cache/comic2web/"
	mkdir -p "$downloadDirectory"
	# create web and cache directories
	mkdir -p "$webDirectory/comics/"
	#mkdir -p "$webDirectory/kodi/comics"
	# link the kodi directory to the download directory
	ln -s "$downloadDirectory" "$webDirectory/kodi/comics"
	# clean the cache of old files
	# scan the sources
	#for comicSource in $comicSources;do
	echo "$comicSources" | while read comicSource;do
		# generate a md5sum for the source
		comicSum=$(echo "$comicSource" | md5sum | cut -d' ' -f1)
		# do not process the comic if it is still in the cache
		# - Cache removes files older than x days
		#if ! [ -f "$webDirectory/comics/cache/$comicSum.index" ];then
		# if the comic is not cached it should be downloaded
		# - gallery-dl with json output will download into the $downloadDirectory/
		#sem --bg --retries 2 --no-notice --ungroup --jobs 1 --id downloadQueue "echo 'Processing...';sleep 15;gallery-dl --write-metadata --dest '$downloadDirectory' '$comicSource'"
		/usr/local/bin/gallery-dl --write-metadata --dest "$downloadDirectory" "$comicSource"
		touch "$webDirectory/comics/cache/$comicSum.index"
		#fi
	done
}
################################################################################
cleanText(){
	# remove punctuation from text, remove leading whitespace, and double spaces
	if [ -f /usr/bin/inline-detox ];then
		echo "$1" | inline-detox --remove-trailing | sed "s/_/ /g" | tr -d '#'
	else
		echo "$1" | sed "s/[[:punct:]]//g" | sed -e "s/^[ \t]*//g" | sed "s/\ \ / /g"
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
scanPages(){
	# - TODO: build a function that reads all image files in a directory, makes webpages for them
	#         index in directory links to first page, last page should link to .. index above
	pagesDirectory=$1
	webDirectory=$2
	pageType=$3
	#tempPath=$(echo "$pagesDirectory" | sed "s/\//_/g" )
	# set page number counter
	pageNumber=0
	find -L "$pagesDirectory" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
		pageNumber=$(( $pageNumber + 1 ))
		if echo "$pageType" | grep "chapter";then
			# is a chapter based comic
			tempComicName="$(pickPath "$imagePath" 3)"
			tempComicChapter="$(pickPath "$imagePath" 2)"
			mkdir -p "$webDirectory/comics/$tempComicName/"
			mkdir -p "$webDirectory/comics/$tempComicName/$tempComicChapter"
			# link the image file to the web directory
			echo "[DEBUG]: Linking a comic chapter from $tempComicName"
			echo "[DEBUG]: Linking chapter $tempComicChapter"
			# link image inside comic chapter directory
			ln -s "$imagePath" "$webDirectory/comics/$tempComicName/$tempComicChapter/$pageNumber.jpg"
			# render the web page
			renderPage "$imagePath" "$webDirectory" $pageNumber chapter
		else
			# is a single chapter comic
			tempComicName="$(pickPath "$imagePath" 2)"
			mkdir -p "$webDirectory/comics/$tempComicName/"
			# link the image file to the web directory
			echo "[DEBUG]: Linking single chapter comic $tempComicName"
			ln -s "$imagePath" "$webDirectory/comics/$tempComicName/$pageNumber.jpg"
			# render the page
			renderPage "$imagePath" "$webDirectory" $pageNumber single
		fi
	done

}
################################################################################
renderPage(){
	page=$1
	webDirectory=$2
	pageNumber=$3
	pageType=$4
	################################################################################
	if echo "$pageType" | grep --ignore-case "chapter";then
		isChapter=true
	else
		isChapter=false
	fi
	################################################################################
	pageName=$(popPath "$page")
	if [ $isChapter = true ];then
		# multi chapter comic
		pageChapterName=$(pickPath "$page" 2)
		pageComicName=$(pickPath "$page" 3)
		# link the image file into the web directory
		ln -s "$imagePath" "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.jpg"
		# get page width and height
		tempImageData=$(file -L "$webDirectory/comics/$pageComicName/$pageNumber.jpg" | grep -E --only-matching "[0123456789]{3,}x[0123456789]{3,}")
	else
		# single chapter comic
		pageComicName=$(pickPath "$page" 2)
		ln -s "$imagePath" "$webDirectory/comics/$pageComicName/$pageNumber.jpg"
		# get page width and height
		tempImageData=$(file -L "$webDirectory/comics/$pageComicName/$pageNumber.jpg" | grep -E --only-matching "[0123456789]{3,}x[0123456789]{3,}")
	fi
	# pull width and height from image file data
	width=$(echo "$tempImageData" | cut -d'x' -f1)
	height=$(echo "$tempImageData" | cut -d'x' -f2)

	# build stylesheet stuff
	#tempStyle="background: url(\"$pageName\")"
	tempStyle="background: url(\"$pageNumber.jpg\")"

	# link missing stylesheets for this chapter of the comic
	if ! [ -f "$webDirectory/comics/$comicName/$comicChapter/style.css" ];then
		ln -s "$webDirectory/style.css" "$webDirectory/comics/$comicName/$comicChapter/style.css"
	fi

	if [ $isChapter = true ];then
		# figure out the back and forward pages
		imageArray=$(find -L "$webDirectory/comics/$pageComicName/$pageChapterName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort)
	else
		imageArray=$(find -L "$webDirectory/comics/$pageComicName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort)
	fi

	previousPage=""
	nextPage=""
	tempPage=""

	echo "[DEBUG]: imageArray = '$imageArray'"

	# set the next and previous page numbers
	nextPage="$((pageNumber + 1))"
	previousPage="$((pageNumber - 1))"

	if [ $previousPage -le 0 ];then
		if [ $isChapter = true ];then
			# if the previous page is 0 then link back to the index
			ln -s "1.html" "$webDirectory/comics/$pageComicName/$pageChapterName/0.html"
			ln -s "1.html" "$webDirectory/comics/$pageComicName/$pageChapterName/index.html"
			#previousPage="index"
		else
			ln -s "index.html" "$webDirectory/comics/$pageComicName/0.html"
			ln -s "1.html" "$webDirectory/comics/$pageComicName/index.html"
		fi
		# if no thumbnail exists then
		if ! [ -f "$webDirectory/comics/$pageComicName/thumb.png" ];then
			if [ $isChapter = true ];then
				# create a thumb for the comic
				convert "$webDirectory/comics/$pageComicName/$pageChapterName/1.jpg" -size 600x800 "$webDirectory/comics/$pageComicName/thumb.png"
			else
				convert "$webDirectory/comics/$pageComicName/1.jpg" -size 600x800 "$webDirectory/comics/$pageComicName/thumb.png"
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

	# check next and previous pages to make sure they can be linked to
	# write the webpage for the individual image
	{
		echo "<html>"
		echo "<head>"
	} > "$pagePath"
	if [ $isChapter = true ];then
		echo "<link rel='stylesheet' href='../../style.css'>" >> "$pagePath"
	else
		echo "<link rel='stylesheet' href='../style.css'>" >> "$pagePath"
	fi
	{
		echo "</head>"
		echo "<body>"
		if [ $width -gt $height ];then
			echo "<div class='comicWidePane' style='$tempStyle'>"
		else
			echo "<div class='comicPane' style='$tempStyle'>"
		fi
		echo "	<a href='$previousPage.html' class='comicPageButton left'>&#8617;<br>$previousPage</a>"
		echo "	<a href='$nextPage.html' class='comicPageButton right'>&#8618;<br>$nextPage</a>"
		echo "	<a class='comicHomeButton comicPageButton center' href='../../..'>"
		echo "		HOME"
		echo "	</a>"
		if [ $isChapter = true ];then
			echo "	<a class='comicIndexButton comicPageButton center' href='../'>"
		else
			echo "	<a class='comicIndexButton comicPageButton center' href='../'>"
		fi
		echo "		BACK"
		echo "	</a>"
		#echo "<div class='comicFooter'>"
		#echo "	$pageNumber"
		#echo "</div>"
		echo "</div>"
		echo "</body>"
		echo "</html>"
	} >> "$pagePath"
}
################################################################################
webUpdate(){
	# read the download directory and convert comics into webpages
	# - There are 2 types of directory structures for comics in the download directory
	#   + comicWebsite/comicName/chapter/image.png
	#   + comicWebsite/comicName/image.png

	webDirectory=$(webRoot)
	downloadDirectory="/var/cache/comic2web/"

	# start building main index page a-z comics

	# read each comicWebsite directory from the download directory
	find "$downloadDirectory" -mindepth 1 -maxdepth 1 -type d | while read comicWebsitePath;do
		echo "[DEBUG]: scanning comic website path '$comicWebsitePath'"
		# build the website directory for the comic path
		mkdir -p "$webDirectory/$(popPath $comicWebsitePath)"
		# build the website tag index page
		find "$comicWebsitePath" -mindepth 1 -maxdepth 1 -type d | while read comicNamePath;do
			echo "[DEBUG]: scanning comic path '$comicNamePath'"
			# build the comic index page
			if [ $(find -L "$comicNamePath" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | wc -l) -gt 0 ];then
				echo "[DEBUG]: scanning single chapter comic '$comicNamePath'"
				# if this directory contains .jpg or .png files then this is a single chapter comic
				# - build the individual pages for the comic
				# - TODO: build a function that reads all image files in a directory, makes webpages for them
				#         index in directory links to first page, last page should link to .. index above
				scanPages "$comicNamePath" "$webDirectory" single
			else
				# if this is not a single chapter comic then read the subdirectories containing
				#   each of the individual chapters
				echo "[DEBUG]: scanning multi chapter comic '$comicNamePath'"
				find "$comicNamePath" -mindepth 1 -maxdepth 1 -type d | while read comicChapterPath;do
					# for each chapter build the individual pages
					scanPages "$comicChapterPath" "$webDirectory" chapter
				done
			fi
		done
		# finish website tag index page
	done
	# finish building main index page a-z
	# start building the index page
	{
		echo "<html>"
		echo "<head>"
		echo "<link rel='stylesheet' href='style.css'>"
		echo "</head>"
		echo "<body>"
		cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
	} > "$webDirectory/comics/index.html"

	# build links to each comic in the index page
	find -L "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
		# multi chapter comic
		tempComicName="$(popPath "$comicNamePath")"
		{
			echo "<a href='$tempComicName/' class='indexSeries' >"
			echo "<img loading='lazy' src='$tempComicName/thumb.png' />"
			echo "<div>$tempComicName</div>"
			echo "</a>"
		} >> "$webDirectory/comics/index.html"
	done
	{
		cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
		echo "</body>"
		echo "</html>"
	} >> "$webDirectory/comics/index.html"
}
################################################################################
webUpdateOld(){
	# webUpdate should scan the download directory and the libary.cfg directories
	webDirectory=$(webRoot)
	downloadDirectory="/var/cache/comic2web/"
	jsonDataFiles=$(find "$downloadDirectory" -name "*.json")
	#for image in $images;do
	echo "$jsonDataFiles" | while read jsonPath;do
		# check the imagePath
		imagePath=$(echo "$jsonPath" | sed "s/\.json//g")
		# read the json data from the image
		tempJson=$(cat "$jsonPath")
		comicName=$(getTitle "$comicName")

		#comicName=$(echo "$tempJson" | jq -r ".title")
		#comicName=$(cleanText "$comicName")

		# get the md5sum for storing comic metadata
		comicTitleSum=$(echo "$comicName" | md5sum | cut -d' ' -f1)
		#comicChapter=$(echo "$tempJson" | jq -r ".chapter")
		comicChapter=$(getChapter "$tempJson")
		# if no chapter data exists set the chapter to be chapter 1
		# - This is most likely a single chapter comic or art gallery
		#echo "$tempJson" | jq ".chapter"
		#if ! $!;then
		if echo "$comicChapter" | grep "null";then
			comicChapter=1
		fi
		# create directories for the files
		mkdir -p "$webDirectory/comics/$comicName/$comicChapter/"
		# pull the page number from the json
		pageNumber=$(getPageNumber "$tempJson")
		# get the file extension
		ext=$(echo "$tempJson" | jq -r ".extension")
		# check if this is the first page of the chapter
		if [ $comicChapter -eq 1 ];then
			if [ $pageNumber -eq 1 ];then
				# link the cover page to the coverThumb.png
				convert "$webDirectory/comics/$comicName/$comicChapter/1.$ext" "$webDirectory/comics/$comicName/$comicChapter/coverThumb.png"
				# copy over json to chapterData.json for this page
				ln -s "$jsonPath" "$webDirectory/comics/$comicName/$comicChapter/chapterData.json"
			fi
		fi

		# get the page height and width
		pageHeight=$(echo "$tempJson" | jq -r ".height")
		pageWidth=$(echo "$tempJson" | jq -r ".width")

		# store or get the release date
		if [ -f "$webDirectory/comics/$comicName/$comicChapter/releaseDate.cfg" ];then
			# check the release date for the comic
			releaseDate=$(cat "$webDirectory/comics/$comicName/$comicChapter/releaseDate.cfg")
		else
			releaseDate=$(echo "$tempJson" | jq -r ".date")
			echo "$releaseDate" > "$webDirectory/comics/$comicName/$comicChapter/releaseDate.cfg"
		fi
		# get the next and previous pages
		comicBackPage=$(($pageNumber - 1))
		comicNextPage=$(($pageNumber + 1))
		# build stylesheet stuff
		tempStyle="background: url(\"$pageNumber.$ext\")"
		# link the image file into the web directory
		ln -s "$imagePath" "$webDirectory/comics/$comicName/$comicChapter/$pageNumber.$ext"
		# link missing stylesheets for this chapter of the comic
		if ! [ -f "$webDirectory/comics/$comicName/$comicChapter/style.css" ];then
			ln -s "$webDirectory/style.css" "$webDirectory/comics/$comicName/$comicChapter/style.css"
		fi
		# check next and previous pages to make sure they can be linked to
		# write the webpage for the individual image
		{
			echo "<html>"
			echo "<head>"
			echo "<link rel='stylesheet' href='style.css'>"
			echo "</head>"
			echo "<body>"
			if [ $width -gt $height ];then
				echo "<div class='comicWidePane' style='$tempStyle'>"
			else
				echo "<div class='comicPane' style='$tempStyle'>"
			fi
			echo "	<a href='$comicBackPage.html' class='comicPageButton left'>&#8617;<br>$comicBackPage</a>"
			echo "	<a href='$comicNextPage.html' class='comicPageButton right'>&#8618;<br>$comicNextPage</a>"
			echo "	<a class='comicHomeButton comicPageButton center' href='../../..'>"
			echo "		HOME"
			echo "	</a>"
			echo "	<a class='comicIndexButton comicPageButton center' href='index.html'>"
			echo "		INDEX"
			#echo "		<img src='coverThumb.png' />"
			echo "	</a>"
			#echo "	<div class='comicFooter'>"
			#echo "			$pageNumber"
			#echo "	</div>"
			echo "</div>"
			echo "</body>"
			echo "</html>"
		} > "$webDirectory/comics/$comicName/$comicChapter/$pageNumber.html"
		# write the metadata for the comic book chapter
		# write the metadata for the comic book index page
		# copy index page links to the tag folders
		# update homepage data if it is old for recently added

	done
	################################################################################
	# TODO: check for local comic caches
	localComics=$(cat /etc/comic2web/libaries.cfg)
	# local comics can contain a larger variety of formats so
	#  processing of local libaries must be done diffrently
	################################################################################

	################################################################################
	# - TODO: loop though all chapters and link after the last page and before
	#   the first page to the comic book index, next or previous chapter
	# find the page count
	# for each comic start building indexes
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | while read comicPath;do
		# build the chapter index webpage
		firstReleaseDate=$(cat "$comicPath/1/releaseDate.cfg")
		# load up the generated chapter data
		chapterData=$(cat "$comicPath/1/chapterData.json")
		comicName=$(echo "$chapterData" | jq -r ".title")
		comicName=$(cleanText "$comicName")
		# build the main comic index, this includes links to all chapters
		{
			echo "<html>"
			echo "<head>"
			echo "<link rel='stylesheet' href='../../style.css'>"
			echo "</head>"
			echo "<body>"
			cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
			echo "<div class='titleCard'>"
			echo "	<img class='comicThumb' src='1/coverThumb.png' />"
			echo "	<h1>"
			echo "		<a class='button' href='1/'>"
			echo "			$comicName"
			echo "		</a>"
			echo "	<h1>"
			echo "	<h2>Release Date: $firstReleaseDate</h2>"
			echo "</div>"
			echo "<div class='seasonContainer'>"
		} > "$comicPath/index.html"
		find "$comicPath" -mindepth 1 -maxdepth 1 -type d  | while read chapterPath;do
			# build the chapter index
			if [ -f "$chapterPath/chapterData.json" ];then
				# load up the generated chapter data
				chapterData=$(cat "$chapterPath/chapterData.json")
				comicName=$(echo "$chapterData" | jq -r ".title")
				comicName=$(cleanText "$comicName")
				#comicChapter=$(echo "$chapterData" | jq -r ".chapter")
				comicChapter=$(getChapter "$chapterData")
				if echo "$comicChapter" | grep "null";then
					comicChapter=1
				fi
				# build each chapter index
				totalPages=$(find -L "$chapterPath" -name "*.jpg" | wc -l)
				# build link in the comic index to this chapter index
				{
					echo "<a class='showPageEpisode' href='$comicChapter/'>"
					echo "<img src='$comicChapter/coverThumb.png' />"
					echo "<h3>Chapter: $comicChapter</h3>"
					echo "</a>"
				} >> "$comicPath/index.html"
				releaseDate=$(cat "$chapterPath/releaseDate.cfg")
				{
					echo "<html>"
					echo "<head>"
					echo "<link rel='stylesheet' href='../../../style.css'>"
					echo "</head>"
					echo "<body>"
					cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\/..\//g"
					echo "<div class='titleCard'>"
					echo "	<img class='comicThumb' src='coverThumb.png' />"
					echo "	<h1>"
					echo "		<a class='button' href='..'>"
					echo "			$comicName"
					echo "		</a>"
					echo "	<h1>"
					echo "	<h2>Release Date: $releaseDate</h2>"
					echo "	<h2>Chapter: $comicChapter</h2>"
					echo "</div>"
					echo "<div class='seasonContainer'>"
				} > "$chapterPath/index.html"
				# loop though pages
				pageNumber=1
				while true;do
					if [ -f "$chapterPath/$pageNumber.jpg" ];then
						{
							echo "<a class='showPageEpisode' href='$pageNumber.html'>"
							echo "	<img src='$pageNumber.jpg' />"
							echo "	<h3>$pageNumber/$totalPages</h3>"
							echo "</a>"
						} >> "$chapterPath/index.html"
					else
						# if the new page number does not exist this is the last page
						# - link the 0.html and $pageNumber.html to index.html
						ln -s "$chapterPath/index.html" "$chapterPath/0.html"
						ln -s "$chapterPath/index.html" "$chapterPath/$pageNumber.html"
						# break the loop
						break
					fi
					pageNumber=$(( pageNumber + 1 ))
				done
				{
					echo "</div>"
					echo "</body>"
					echo "</html>"
				} >> "$chapterPath/index.html"
			fi
		done
		# build the main comic index listing all the chapters
		{
			echo "</div>"
			echo "</body>"
			echo "</html>"
		} >> "$comicPath/index.html"
	done
	# - TODO: loop though again and build the comic book indexes listing all chapters
	################################################################################
	# start building the main index page for all comics
	{
		echo "<html>"
		echo "<head>"
		echo "<link rel='stylesheet' href='../../style.css'>"
		echo "</head>"
		echo "<body>"
		cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
	} > "$webDirectory/comics/index.html"
	# add each individual comic to the index
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | while read comicPath;do
		# pop the directory path name
		popPath=$(echo "$comicPath" | rev | cut -d'/' -f1 | rev)
		# add each comic link using the cover thumb
		{
			echo "<a class='indexSeries' href='$popPath/'>"
			echo "	<img src='$popPath/1/coverThumb.png' />"
			echo "	<h3>$popPath</h3>"
			echo "</a>"
		} >> "$webDirectory/comics/index.html"
	done
	{
		echo "</div>"
		echo "</body>"
		echo "</html>"
	} >> "$webDirectory/comics/index.html"
}
################################################################################
function resetCache(){
	# remove web cache
	rm -rv "$webDirectory/comics/"
	exit
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | while read comicPath;do
		if ! [ "$comicPath" == "$webDirectory/comics/cache/" ];then
			# comic
			echo "rm -rv '$comicPath'"
		fi
	done
}
################################################################################
main(){
	################################################################################
	webRoot
	################################################################################
	if [ "$1" == "-w" ] || [ "$1" == "--webgen" ] || [ "$1" == "webgen" ] ;then
		webUpdate
	elif [ "$1" == "-u" ] || [ "$1" == "--update" ] || [ "$1" == "update" ] ;then
		update
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		resetCache
	elif [ "$1" == "-U" ] || [ "$1" == "--upgrade" ] || [ "$1" == "upgrade" ] ;then
		# upgrade gallery-dl pip packages
		pip3 install --upgrade gallery-dl
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		echo "########################################################################"
		echo "# comic2web CLI for administration"
		echo "# Copyright (C) 2020  Carl J Smith"
		echo "#"
		echo "# This program is free software: you can redistribute it and/or modify"
		echo "# it under the terms of the GNU General Public License as published by"
		echo "# the Free Software Foundation, either version 3 of the License, or"
		echo "# (at your option) any later version."
		echo "#"
		echo "# This program is distributed in the hope that it will be useful,"
		echo "# but WITHOUT ANY WARRANTY; without even the implied warranty of"
		echo "# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the"
		echo "# GNU General Public License for more details."
		echo "#"
		echo "# You should have received a copy of the GNU General Public License"
		echo "# along with this program.  If not, see <http://www.gnu.org/licenses/>."
		echo "########################################################################"
		echo "HELP INFO"
		echo "This is the iptv4everyone administration and update program."
		echo "To return to this menu use 'iptv4everyone help'"
		echo "Other commands are listed below."
		echo ""
		echo "update"
		echo "  This will update the m3u file used to make the website."
		echo ""
		echo "cron"
		echo "  Run the cron check script."
		echo ""
		echo "reset"
		echo "  Reset the cache."
		echo ""
		echo "webgen"
		echo "	Build the website from the m3u generated."
		echo ""
		echo "libary"
		echo "	Download the latest version of the hls.js libary for use."
		echo "########################################################################"
	else
		main --update
		main --webgen
		main --help
	fi
}
################################################################################
main "$@"
exit
