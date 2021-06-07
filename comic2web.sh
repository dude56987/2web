#! /bin/bash
################################################################################
# enable debug log
#set -x
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/nfo2web/web.cfg ];then
		webDirectory=$(cat /etc/nfo2web/web.cfg)
	else
		#mkdir -p /var/cache/nfo2web/web/
		chown -R www-data:www-data "/var/cache/nfo2web/web/"
		echo "/var/cache/nfo2web/web" > /etc/nfo2web/web.cfg
		webDirectory="/var/cache/nfo2web/web"
	fi
	#mkdir -p "$webDirectory"
	echo "$webDirectory"
}
################################################################################
function loadWithoutComments(){
	grep -Ev "^#" "$1"
	return 0
}
################################################################################
function downloadDir(){
	if [ ! -f /etc/comic2web/download.cfg ];then
		# if no config exists create the default config
		{
			# write the new config from the path variable
			echo "/var/cache/comic2web/"
		} >> "/etc/comic2web/download.cfg"
	fi
	# write path to console
	cat "/etc/comic2web/download.cfg"
}
################################################################################
function update(){
	#DEBUG
	#set -x
	# this will launch a processing queue that downloads updates to comics
	echo "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/comic2web/sources.cfg;then
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
	comicSources=$(echo -e "$comicSources\n$(grep -v "^#" /etc/comic2web/sources.d/*.cfg)")
	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	downloadDirectory="$(downloadDir)"
	################################################################################
	# make the download directory if is does not exist
	mkdir -p "$downloadDirectory"
	# make comics directory
	mkdir -p "$webDirectory/comics/"
	# create web and cache directories
	mkdir -p "$webDirectory/comicCache/"
	# remove mark files older than 40 days, this will cause the comic to be updated
	find "$webDirectory/comicCache/" -type f -mtime +40 -delete
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
		if ! test -f "$webDirectory/comicCache/$comicSum.index";then
			# if the comic is not cached it should be downloaded
			# - gallery-dl with json output will download into the $downloadDirectory/
			# - niceload --net will sleep a process when the network is overloaded
			#sem --bg --retries 2 --no-notice --ungroup --jobs 1 --id downloadQueue "echo 'Processing...';sleep 15;gallery-dl --write-metadata --dest '$downloadDirectory' '$comicSource'"
			/usr/local/bin/gallery-dl --write-metadata --dest "$downloadDirectory" "$comicSource"
			# after download mark the download to have been successfully cached
			touch "$webDirectory/comicCache/$comicSum.index"
		fi
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
prefixNumber(){
	pageNumber=$1
	# set the page number prefix to make file sorting work
	# - this makes 1 occur before 10 by adding zeros ahead of the number
	# - this will work unless the comic has a chapter over 9999 pages
	if [ $pageNumber -lt 10 ];then
		pageNumber="000$pageNumber"
	elif [ $pageNumber -lt 100 ];then
		pageNumber="00$pageNumber"
	elif [ $previousPage -lt 1000 ];then
		pageNumber="0$pageNumber"
	fi
	# output the number with a prefix on it
	echo $pageNumber
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


	find -L "$pagesDirectory" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
		pageNumber=$(( 10#$pageNumber + 1 ))
		################################################################################
		# set the page number prefix to make file sorting work
		# - this makes 1 occur before 10 by adding zeros ahead of the number
		# - this will work unless the comic has a chapter over 9999 pages
		pageNumber=$(prefixNumber $pageNumber)
		#if [ $pageNumber -lt 10 ];then
		#	pageNumber="000$pageNumber"
		#elif [ $pageNumber -lt 100 ];then
		#	pageNumber="00$pageNumber"
		#elif [ $previousPage -lt 1000 ];then
		#	pageNumber="0$pageNumber"
		#fi
		################################################################################
		if echo "$pageType" | grep -q "chapter";then
			# is a chapter based comic
			tempComicName="$(pickPath "$imagePath" 3)"
			#tempComicChapter="$(pickPath "$imagePath" 2)"
			tempComicChapter=$pageChapter
			mkdir -p "$webDirectory/comics/$tempComicName/$tempComicChapter"
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
			echo -ne "[INFO]: Rendering $tempComicName chapter $tempComicChapter/$totalChapters page $pageNumber/$totalPages \r"
			# link image inside comic chapter directory
			if ! test -f "$webDirectory/comics/$tempComicName/$tempComicChapter/$pageNumber.jpg";then
				ln -s "$imagePath" "$webDirectory/comics/$tempComicName/$tempComicChapter/$pageNumber.jpg"
			fi
			# render the web page
			renderPage "$imagePath" "$webDirectory" $pageNumber chapter $pageChapter
		else
			# is a single chapter comic
			tempComicName="$(pickPath "$imagePath" 2)"
			mkdir -p "$webDirectory/comics/$tempComicName/"
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
			echo -ne "[INFO]: Rendering $tempComicName page $pageNumber/$totalPages \r"
			if ! test -f "$webDirectory/comics/$tempComicName/$pageNumber.jpg";then
				ln -s "$imagePath" "$webDirectory/comics/$tempComicName/$pageNumber.jpg"
			fi
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
		# multi chapter comic
		#pageChapterName=$(pickPath "$page" 2)
		pageComicName=$(pickPath "$page" 3)
		# link the image file into the web directory
		if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.jpg";then
			ln -s "$imagePath" "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber.jpg"
		fi
		# create the thumbnail for the image, otherwise it will nuke the server reading the HQ image files on loading index pages
		if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber-thumb.png";then
			convert "$imagePath" -resize 150x200 "$webDirectory/comics/$pageComicName/$pageChapterName/$pageNumber-thumb.png"
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
		# create the thumbnail for the image, otherwise it will nuke the server reading the HQ image files on loading index pages
		if ! test -f "$webDirectory/comics/$pageComicName/$pageNumber-thumb.png";then
			convert "$imagePath" -resize 150x200 "$webDirectory/comics/$pageComicName/$pageNumber-thumb.png"
		fi
		# link the image
		if ! test -f "$webDirectory/comics/$pageComicName/$pageNumber.jpg";then
			ln -s "$imagePath" "$webDirectory/comics/$pageComicName/$pageNumber.jpg"
		fi
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
	if [ ! -f "$webDirectory/comics/style.css" ];then
		ln -s "$webDirectory/style.css" "$webDirectory/comics/style.css"
	fi

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
			if ! test -f"$webDirectory/comics/$pageComicName/$pageChapterName/0000.html";then
				ln -s "index.html" "$webDirectory/comics/$pageComicName/$pageChapterName/0000.html"
			fi
			#ln -s "1.html" "$webDirectory/comics/$pageComicName/$pageChapterName/index.html"
			#previousPage="index"
		else
			if ! test -f "$webDirectory/comics/$pageComicName/0000.html";then
				ln -s "index.html" "$webDirectory/comics/$pageComicName/0000.html"
			fi
			#ln -s "1.html" "$webDirectory/comics/$pageComicName/index.html"
		fi

		if [ $isChapter = true ];then
			# create chapter specific thumbnails
			if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/thumb.png";then
				# create a thumb for the comic
				convert "$webDirectory/comics/$pageComicName/$pageChapterName/0001.jpg" -resize 150x200 "$webDirectory/comics/$pageComicName/$pageChapterName/thumb.png"
			fi
		fi
		# if no thumbnail exists then
		if ! test -f "$webDirectory/comics/$pageComicName/thumb.png";then
			if [ $isChapter = true ];then
				# create a thumb for the comic
				convert "$webDirectory/comics/$pageComicName/$pageChapterName/0001.jpg" -resize 150x200 "$webDirectory/comics/$pageComicName/thumb.png"
			else
				convert "$webDirectory/comics/$pageComicName/0001.jpg" -resize 600x800 "$webDirectory/comics/$pageComicName/thumb.png"
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
		echo "<title>$pageComicName - Chapter $((10#$pageChapter))/$totalChapters - Page $((10#$pageNumber))/$totalPages</title>" >> "$pagePath"
		echo "<link rel='stylesheet' href='../../style.css'>" >> "$pagePath"
	else
		echo "<title>$pageComicName - Page $((10#$pageNumber))/$totalPages</title>" >> "$pagePath"
		echo "<link rel='stylesheet' href='../style.css'>" >> "$pagePath"
	fi
	{
		echo "<script>"
		cat /usr/share/nfo2web/nfo2web.js
		# add a listener to pass the key event into a function
		echo "function setupKeys() {"
		echo "	document.body.addEventListener('keydown', function(event){"
		echo "		const key = event.key;"
		echo "		switch (key){"
		echo "			case 'ArrowLeft':"
		echo "				window.location.href='$previousPage.html';"
		echo "				break;"
		echo "			case 'ArrowRight':"
		echo "				window.location.href='$nextPage.html';"
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
			if ! test -f "$webDirectory/comics/$pageComicName/$pageChapterName/$nextPage.html";then
				ln -s "index.html" "$webDirectory/comics/$pageComicName/$pageChapterName/$nextPage.html"
			fi
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
			if ! test -f "$webDirectory/comics/$pageComicName/$nextPage.html";then
				ln -s "index.html" "$webDirectory/comics/$pageComicName/$nextPage.html"
			fi
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
			echo "</head>"
			echo "<body>"
			cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
			# add the search box
			echo " <input id='searchBox' class='searchBox' type='text'"
			echo " onkeyup='filter(\"indexSeries\")' placeholder='Search...' >"
			echo "<hr>"
			echo "<div class='titleCard'>"
			echo "<h1>$pageComicName</h1>"
			echo "</div>"
		} > "$webDirectory/comics/$pageComicName/index.html"
		#if echo "$pageType" | grep "chapter";then
		if [ $isChapter = true ];then
			echo "[INFO]: Building index links to each chapter of the comic..."
			# build links to each chapter of the comic
			find "$webDirectory/comics/$pageComicName/" -mindepth 1 -maxdepth 1 -type d | sort | while read chapterPath;do
				# multi chapter comic
				tempChapterName="$(popPath "$chapterPath")"
				# build the comic chapter link in the main comic index
				{
					echo "<a href='./$tempChapterName/' class='indexSeries' >"
					echo "<img loading='lazy' src='./$tempChapterName/thumb.png' />"
					echo "<div>Chapter $tempChapterName</div>"
					echo "</a>"
				} >> "$webDirectory/comics/$pageComicName/index.html"
				# build the index for the chapter displaying all the images
				{
					echo "<html>"
					echo "<head>"
					echo "<link rel='stylesheet' href='../../style.css'>"
					echo "</head>"
					echo "<body>"
					cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\/..\//g"
					echo "<div class='titleCard'>"
					echo "<h1>$pageComicName</h1>"
					echo "<h2>Chapter $((10#$tempChapterName))/$totalChapters</h2>"
					echo "</div>"
				} > "$webDirectory/comics/$pageComicName/$tempChapterName/index.html"

				# build the individual image index for this chapter
				find -L "$webDirectory/comics/$pageComicName/$tempChapterName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
					# single chapter of a multi chapter comic
					tempImageName="$(popPath "$imagePath" | sed "s/\.jpg//g")"

					{
						echo "<a href='./$tempImageName.html' class='indexSeries' >"
						echo "<img loading='lazy' src='./$tempImageName-thumb.png' />"
						echo "<div>$tempImageName</div>"
						echo "</a>"
					} >> "$webDirectory/comics/$pageComicName/$tempChapterName/index.html"
				done
				# finish the chapter index
				{
					cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\/..\//g"
					echo "</body>"
					echo "</html>"
				} >> "$webDirectory/comics/$pageComicName/$tempChapterName/index.html"
			done
		else
			# if it is not a chapter and is only a page link
			find -L "$webDirectory/comics/$pageComicName/" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | sort | while read imagePath;do
				# single chapter comic image
				tempName="$(popPath "$imagePath" | sed "s/\.jpg//g")"
				{
					echo "<a href='./$tempName.html' class='indexSeries' >"
					echo "<img loading='lazy' src='./$tempName-thumb.png' />"
					echo "<div>$tempName</div>"
					echo "</a>"
				} >> "$webDirectory/comics/$pageComicName/index.html"
			done
		fi
		{
			cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
			echo "</body>"
			echo "</html>"
		} >> "$webDirectory/comics/$pageComicName/index.html"
	fi
}
################################################################################
webUpdate(){
	# read the download directory and convert comics into webpages
	# - There are 2 types of directory structures for comics in the download directory
	#   + comicWebsite/comicName/chapter/image.png
	#   + comicWebsite/comicName/image.png

	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"

	# start building main index page a-z comics

	# read each comicWebsite directory from the download directory
	find "$downloadDirectory" -mindepth 1 -maxdepth 1 -type d | sort | while read comicWebsitePath;do
		echo "[INFO]: scanning comic website path '$comicWebsitePath'"
		# build the website directory for the comic path
		#mkdir -p "$webDirectory/comics/$(popPath $comicWebsitePath)"
		# build the website tag index page
		find "$comicWebsitePath" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
			echo "[INFO]: scanning comic path '$comicNamePath'"
			# build the comic index page
			if [ $(find -L "$comicNamePath" -mindepth 1 -maxdepth 1 -type f -name "*.jpg" | wc -l) -gt 0 ];then
				echo "[INFO]: scanning single chapter comic '$comicNamePath'"
				# if this directory contains .jpg or .png files then this is a single chapter comic
				# - build the individual pages for the comic
				scanPages "$comicNamePath" "$webDirectory" single
			else
				# if this is not a single chapter comic then read the subdirectories containing
				#   each of the individual chapters
				echo "[INFO]: scanning multi chapter comic '$comicNamePath'"
				# reset chapter number for count
				chapterNumber=0
				find "$comicNamePath" -mindepth 1 -maxdepth 1 -type d | sort | while read comicChapterPath;do

					chapterNumber=$(( 10#$chapterNumber + 1 ))
					# add zeros to the chapter as a prefix for correct ordering
					chapterNumber=$(prefixNumber $chapterNumber)
					#if [ $chapterNumber -lt 10 ];then
					#	chapterNumber="000$chapterNumber"
					#elif [ $chapterNumber -lt 100 ];then
					#	chapterNumber="00$chapterNumber"
					#elif [ $chapterNumber -lt 1000 ];then
					#	chapterNumber="0$chapterNumber"
					#fi
					# for each chapter build the individual pages
					scanPages "$comicChapterPath" "$webDirectory" chapter $chapterNumber
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
		echo "<script>"
		cat /usr/share/nfo2web/nfo2web.js
		echo "</script>"
		echo "</head>"
		echo "<body>"
		cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
		# add the search box
		echo " <input id='searchBox' class='searchBox' type='text'"
		echo " onkeyup='filter(\"indexSeries\")' placeholder='Search...' >"
		echo "<hr>"
	} > "$webDirectory/comics/index.html"

	# build links to each comic in the index page
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | sort | while read comicNamePath;do
		# multi chapter comic
		tempComicName="$(popPath "$comicNamePath")"
		{
			echo "<a href='./$tempComicName/' class='indexSeries' >"
			echo "<img loading='lazy' src='./$tempComicName/thumb.png' />"
			echo "<div>$tempComicName</div>"
			echo "</a>"
		} >> "$webDirectory/comics/index.html"
		#	build the index file for this entry if one does not exist
		if ! test -f "$webDirectory/comics/$tempComicName/index.index";then
			{
				echo "<a href='$tempComicName/' class='indexSeries' >"
				echo "<img loading='lazy' src='$tempComicName/thumb.png' />"
				echo "<div>$tempComicName</div>"
				echo "</a>"
			} > "$webDirectory/comics/$tempComicName/index.index"
		fi
	done
	{
		# write the random comics index file if is exists to the bottom of the main comic index
		if test -f "$webDirectory/randomComics.index";then
			#cat "$webDirectory/randomComics.index" | sed "s/href='/href='..\/.\//g"
			cat "$webDirectory/randomComics.index"
		fi
		cat "$webDirectory/header.html" | sed "s/href='/href='..\/..\//g"
		echo "</body>"
		echo "</html>"
	} >> "$webDirectory/comics/index.html"
}
################################################################################
function resetCache(){
	webDirectory=$(webRoot)
	# remove web cache
	rm -rv "$webDirectory/comics/"
	exit
	find "$webDirectory/comics/" -mindepth 1 -maxdepth 1 -type d | while read comicPath;do
		if [ ! "$comicPath" == "$webDirectory/comicCache/" ];then
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
