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
function cacheCheckMin(){

	filePath="$1"
	cacheMinutes="$2"

	# return true if cached needs updated
	if [ -f "$filePath" ];then
		# the file exists
		if [[ $(find "$1" -cmin "+$cacheMinutes") ]];then
			# the file is more than "$2" minutes old, it needs updated
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
function update(){
	#DEBUG
	# this will launch a processing queue that downloads updates to comics
	INFO "Loading up sources..."
	# check for defined sources
	if ! test -f /etc/2web/weather/location.cfg;then
		# if no config exists create the default config
		{
			echo "##################################################"
			echo "# Example Config"
			echo "##################################################"
			echo "# - You can use the city and state name"
			echo "# - You can use the weather station name 'nyz072'"
			echo "#   is central park in New York"
			echo "# - You can use zip codes"
			echo "#  ex."
			echo "#    nyz072"
			echo "##################################################"
			echo "# city and state name"
			echo "New York, NY"
			echo "# weather station name"
			echo "nyz072"
			echo "# zip code"
			echo "90210"
		} > /etc/2web/weather/location.cfg
	fi
	# load sources
	weatherLocations=$(grep -v "^#" /etc/2web/weather/location.cfg)
	weatherLocations=$(echo -e "$weatherLocations\n$(grep -v --no-filename "^#" /etc/2web/weather/location.d/*.cfg)")
	################################################################################
	webDirectory=$(webRoot)
	################################################################################
	# make the download directory if is does not exist
	createDir "$downloadDirectory"
	# make comics directory
	createDir "$webDirectory/weather/"
	createDir "$webDirectory/weather/data/"
	# scan the sources
	ALERT "Weather Locations: $weatherLocations"
	totalWeatherLocations=$(echo "$weatherLocations" | wc -l)
	#for comicSource in $comicSources;do
	weatherLocations=$(echo "$weatherLocations" | tr -s '\n')
	echo "$weatherLocations" | while read weatherLocation;do
		ALERT "Scanning location '$weatherLocation'"
		if [ "$( echo "$weatherLocation" | wc -c )" -gt 1 ];then
			# generate a md5sum for the location
			locationSum=$(echo -n "$weatherLocation" | md5sum | cut -d' ' -f1)
			# check the forcast needs to be updated e.g. more than 30 minutes old
			if cacheCheckMin "$webDirectory/weather/data/forcast_$locationSum.cfg" 30;then
				# check for the index link for the station
				if cacheCheck "$webDirectory/weather/data/station_$locationSum.cfg" 40;then
					{
						# create the index entry
						echo -n "<a class='button' href='#$weatherLocation'>$weatherLocation</a>"
					} > "$webDirectory/weather/data/station_$locationSum.index"
				fi
				# fetch the weather info
				# look for the weather command
				# check for weather config
				#if ! test -f "$webDirectory/weather/data/location_$locationSum.cfg";then
				# update the weather
				weatherData="$(/usr/bin/weather -f "$weatherLocation" )"

				# pull the location name from the weather data
				locationName=$( echo "$weatherData" | grep --ignore-case "current conditions at" | sed "s/Current conditions at//g" )

				locationUpdateTime=$( echo "$weatherData" | grep --ignore-case "last updated" )

				todaysWeather=$(echo "$weatherData" | grep -v "\.\.\." )

				todaysForcast=$(echo "$weatherData" | tr -d '\n' | grep --only-matching "   \..*" | sed "s/   \./\n/g" | tr -s '\n')

				# update the weather config used for the website
				echo "$todaysWeather" > "$webDirectory/weather/data/current_$locationSum.cfg"
				#echo "$todaysForcast" > "$webDirectory/weather/data/forcast_$locationSum.cfg"

				todaysWeatherAsHtml=$(echo "$todaysWeather" | grep -v "\.\.\." | tr -s '.' | cut -d'.' -f1- | txt2html --extract )
				todaysForcastAsHtml=""

				# if no hompage weather location exists set the first added weather location as the homepage location since this data is gathered here anyway
				if ! test -f "/etc/2web/weather/homepageLocation.cfg";then
					echo "$weatherLocation" > "/etc/2web/weather/homepageLocation.cfg"
				fi
				# write the weather info for homepage
				if echo "$weatherLocation" | grep "$(cat '/etc/2web/weather/homepageLocation.cfg')";then
					tempWeatherData=$(\
						/usr/bin/weather "$weatherLocation" |\
						grep --invert-match "Searching via name" |\
						grep --invert-match "using result" |\
						grep --invert-match "Current conditions at" |\
						grep --invert-match "Last updated" |\
						txt2html --extract\
					)
					{
						echo "<h3>"
						echo "$weatherLocation"
						echo "</h3>"
						echo "<div class='weatherIcon weatherHomepageIcon right'>"
						getWeatherIcon "$tempWeatherData"
						echo "</div>"
						echo "$tempWeatherData"
					} > "$webDirectory/weather.index"
				fi
				# blank the forcast for rewriting
				{
					echo -n "<div id='$weatherLocation' class='titleCard weatherCard'>"
					echo -n "<h1 class='weatherLocationName'>$weatherLocation</h1>"
					echo -n "<div class='listCard'>"
					#echo "<div class='listCard'>"
				} > "$webDirectory/weather/data/forcast_$locationSum.index"
				# read each line of the forcast info
				echo "$todaysForcast" | while read forcast;do
					# pull forcast time
					timeOfForcast=$(echo "$forcast"	| cut -d'.' -f1 | sed "s/THROUGH EARLY //g" | sed "s/EARLY THIS //g" | sed "s/LATE THIS //g"  | sed "s/REST OF //g" | sed "s/THIS //g" )
					if [ $( echo -n "$timeOfForcast" | wc -c) -gt 0 ];then
						# pull forcast info for time
						tempForcast=$(echo "$forcast"	| cut -d'.' -f4-)
						tempForcast=$(echo "$tempForcast"	| tr -d '\t' | tr -s ' ')
						# check for emergency signals
						{
							echo "<div class='weatherForcast"
							#if echo "$tempForcast" | grep -q "Lows";then
							#	echo " COLD'>"
							#elif echo "$tempForcast" | grep -q "Highs";then
							#	echo " HOT'>"
							#fi
							tempTemp="?"
							goodWeather="false"
							echo "' style='background: linear-gradient(to bottom, "
							if echo "$tempForcast" | grep -q "lower 0s";then
								tempTemp="3"
								tempColor="rgba(1,1,255,0.5)"
							elif echo "$tempForcast" | grep -q "around 0\.";then
								tempTemp="0"
								tempColor="rgba(2,2,255,0.5)"
							elif echo "$tempForcast" | grep -q "mid 0s";then
								tempTemp="5"
								tempColor="rgba(3,3,255,0.5)"
							elif echo "$tempForcast" | grep -q "upper 0s";then
								tempTemp="8"
								tempColor="rgba(4,4,255,0.5)"
							elif echo "$tempForcast" | grep -q "lower 10s";then
								tempTemp="13"
								tempColor="rgba(5,5,255,0.5)"
							elif echo "$tempForcast" | grep -q "around 10\.";then
								tempTemp="10"
								tempColor="rgba(6,6,255,0.5)"
							elif echo "$tempForcast" | grep -q "mid 10s";then
								tempTemp="15"
								tempColor="rgba(7,7,255,0.5)"
							elif echo "$tempForcast" | grep -q "upper 10s";then
								tempTemp="18"
								tempColor="rgba(8,8,255,0.5)"
							elif echo "$tempForcast" | grep -q "lower 20s";then
								tempTemp="23"
								tempColor="rgba(10,10,255,0.5)"
							elif echo "$tempForcast" | grep -q "around 20\.";then
								tempTemp="20"
								tempColor="rgba(11,11,255,0.5)"
							elif echo "$tempForcast" | grep -q "mid 20s";then
								tempTemp="25"
								tempColor="rgba(12,12,255,0.5)"
							elif echo "$tempForcast" | grep -q "upper 20s";then
								tempTemp="28"
								tempColor="rgba(13,13,255,0.5)"
							elif echo "$tempForcast" | grep -q "lower 30s";then
								tempTemp="33"
								tempColor="rgba(14,14,255,0.5)"
							elif echo "$tempForcast" | grep -q "around 30\.";then
								tempTemp="30"
								tempColor="rgba(15,15,255,0.5)"
							elif echo "$tempForcast" | grep -q "mid 30s";then
								tempTemp="35"
								"rgba(16,16,255,0.5)"
							elif echo "$tempForcast" | grep -q "upper 30s";then
								tempTemp="38"
								tempColor="rgba(17,17,255,0.5)"
							elif echo "$tempForcast" | grep -q "lower 40s";then
								tempTemp="43"
								tempColor="rgba(18,18,255,0.5)"
							elif echo "$tempForcast" | grep -q "around 40\.";then
								tempTemp="40"
								tempColor="rgba(19,19,255,0.5)"
							elif echo "$tempForcast" | grep -q "mid 40s";then
								tempTemp="45"
								tempColor="rgba(20,20,255,0.5)"
							elif echo "$tempForcast" | grep -q "upper 40s";then
								tempTemp="48"
								tempColor="rgba(21,21,255,0.45)"
							elif echo "$tempForcast" | grep -q "lower 50s";then
								tempTemp="53"
								tempColor="rgba(22,22,255,0.40)"
							elif echo "$tempForcast" | grep -q "around 50\.";then
								tempTemp="50"
								tempColor="rgba(23,23,255,0.35)"
							elif echo "$tempForcast" | grep -q "mid 50s";then
								tempTemp="55"
								tempColor="rgba(24,24,255,0.30)"
							elif echo "$tempForcast" | grep -q "upper 50s";then
								tempTemp="58"
								tempColor="rgba(24,24,255,0.25)"
							elif echo "$tempForcast" | grep -q "lower 60s";then
								tempTemp="63"
								tempColor="rgba(25,25,255,0.20)"
							elif echo "$tempForcast" | grep -q "around 60\.";then
								tempTemp="60"
								tempColor="rgba(26,26,255,0.15)"
							elif echo "$tempForcast" | grep -q "mid 60s";then
								tempTemp="65"
								tempColor="rgba(27,27,255,0.1)"
							elif echo "$tempForcast" | grep -q "upper 60s";then
								goodWeather="yes"
								tempTemp="68"
								tempColor="var(--glassBackground)"
							elif echo "$tempForcast" | grep -q "lower 70s";then
								goodWeather="yes"
								tempTemp="73"
								tempColor="var(--glassBackground)"
							elif echo "$tempForcast" | grep -q "around 70\.";then
								goodWeather="yes"
								tempTemp="70"
								tempColor="var(--glassBackground)"
							elif echo "$tempForcast" | grep -q "mid 70s";then
								goodWeather="yes"
								tempTemp="75"
								tempColor="var(--glassBackground)"
							elif echo "$tempForcast" | grep -q "upper 70s";then
								tempTemp="78"
								tempColor="rgba(255,21,21,0.1)"
							elif echo "$tempForcast" | grep -q "lower 80s";then
								tempTemp="83"
								tempColor="rgba(255,20,20,0.15)"
							elif echo "$tempForcast" | grep -q "around 80\.";then
								tempTemp="80"
								tempColor="rgba(255,19,19,0.20)"
							elif echo "$tempForcast" | grep -q "mid 80s";then
								tempTemp="85"
								tempColor="rgba(255,18,18,0.25)"
							elif echo "$tempForcast" | grep -q "upper 80s";then
								tempTemp="88"
								tempColor="rgba(255,17,17,0.30)"
							elif echo "$tempForcast" | grep -q "lower 90s";then
								tempTemp="93"
								tempColor="rgba(255,16,16,0.35)"
							elif echo "$tempForcast" | grep -q "around 90\.";then
								tempTemp="90"
								tempColor="rgba(255,15,15,0.40)"
							elif echo "$tempForcast" | grep -q "mid 90s";then
								tempTemp="95"
								tempColor="rgba(255,14,14,0.45)"
							elif echo "$tempForcast" | grep -q "upper 90s";then
								tempTemp="98"
								tempColor="rgba(255,13,13,0.5)"
							elif echo "$tempForcast" | grep -q "lower 100s";then
								tempTemp="103"
								tempColor="rgba(255,12,12,0.5)"
							elif echo "$tempForcast" | grep -q "around 100\.";then
								tempTemp="100"
								tempColor="rgba(255,11,11,0.5)"
							elif echo "$tempForcast" | grep -q "mid 100s";then
								tempTemp="105"
								tempColor="rgba(255,10,10,0.5)"
							elif echo "$tempForcast" | grep -q "upper 100s";then
								tempTemp="108"
								tempColor="rgba(255,9,9,0.5)"
							elif echo "$tempForcast" | grep -q "lower 110s";then
								tempTemp="113"
								tempColor="rgba(255,8,8,0.5)"
							elif echo "$tempForcast" | grep -q "around 110\.";then
								tempTemp="110"
								tempColor="rgba(255,7,7,0.5)"
							elif echo "$tempForcast" | grep -q "mid 110s";then
								tempTemp="115"
								tempColor="rgba(255,6,6,0.5)"
							elif echo "$tempForcast" | grep -q "upper 110s";then
								tempTemp="118"
								tempColor="rgba(255,5,5,0.5)"
							elif echo "$tempForcast" | grep -q "lower 120s";then
								tempTemp="123"
								tempColor="rgba(255,4,4,0.5)"
							elif echo "$tempForcast" | grep -q "around 120\.";then
								tempTemp="120"
								tempColor="rgba(255,3,3,0.5)"
							elif echo "$tempForcast" | grep -q "mid 120s";then
								tempTemp="125"
								tempColor="rgba(255,2,2,0.5)"
							elif echo "$tempForcast" | grep -q "upper 120s";then
								tempTemp="128"
								tempColor="rgba(255,1,1,0.5)"
							else
								# the weather could not be identified
								tempColor="var(--glassBackground)"
							fi
							echo "$tempColor"
							# add farenheight symbol to the temp, or hide it if a time without a temp
							if echo "$timeOfForcast" | grep -q --ignore-case "MORNING";then
								tempTemp=""
							else
								tempTemp="$tempTemp℉"
							fi
							echo -n ",var(--glassBackground),var(--glassBackground))' >"
							if echo "$timeOfForcast" | grep -qE "(NIGHT|AFTERNOON|EVENING)";then
								# nighttime forcast
								timeOfForcast=$(echo "$timeOfForcast" | sed "s/ NIGHT//g")
								echo -n "	<h3>$timeOfForcast"
								echo -n "🌙";
							else
								# daytime forcast
								if echo "$timeOfForcast" | grep -q --ignore-case "MEMORIAL DAY";then
									echo -n "	<h3>MEMORIAL <br>DAY"
									echo -n "🪖";
								elif echo "$timeOfForcast" | grep -q --ignore-case "CHRISTMAS";then
									echo -n "	<h3>CHRISTMAS<br>DAY"
									echo -n "🎄";
								elif echo "$timeOfForcast" | grep -q --ignore-case "INDEPENDENCE";then
									echo -n "	<h3>INDEPENDENCE<br>DAY"
									echo -n "🎆";
								else
									echo -n "	<h3>$timeOfForcast"
									echo -n "🌞";
								fi
							fi
							echo -n "</h3>"
							# add temperature icon
							echo -n "	<div class='weatherIcon'>"
							getWeatherIcon "$tempForcast"
							echo -n "	</div>"
							echo -n "	<div class='weatherDescription'>"
							if echo "$goodWeather" | grep -q "yes";then
								echo -n "	<div class='tempIcon goodWeather' style='background-color: $tempColor'>"
							else
								echo -n "	<div class='tempIcon' style='background-color: $tempColor'>"
							fi
							echo -n "		$tempTemp"
							echo -n "	</div>"
							echo -n "		$tempForcast"
							echo -n "	</div>"
							echo -n "</div>"
						} >> "$webDirectory/weather/data/forcast_$locationSum.index"
					fi
				done
				# store the weather info for the current weather at the location
				{
					echo "<div class='weatherIcon right'>"
					getWeatherIcon "$todaysWeather"
					echo "</div>"
					echo "$todaysWeather"
				} > "$webDirectory/weather/data/current_$locationSum.index"
			fi
			{
				echo -n "</div>"
				echo -n "	<div class='listCard'>"
				echo -n "		<div class='weatherStationInfo'>"
				echo -n "		Found via $locationName weather station."
				echo -n "		<br>"
				echo -n "		$locationUpdateTime"
				echo -n "		</div>"
				# add outside search links
				echo -n "		<a class='button' target='_new' href='https://en.wikipedia.org/w/?search=$weatherLocation'>🔎 WIKIPEDIA</a>"
				echo -n "		<a class='button' target='_new' href='https://www.accuweather.com/en/search-locations?query=$weatherLocation'>🔎 AccuWeather</a>"
				echo -n "		<a class='button' target='_new' href='https://www.duckduckgo.com/?q=weather $weatherLocation'>🔎 DuckDuckGo</a>"
				echo -n "	</div>"
				echo -n "</div>"
			} >> "$webDirectory/weather/data/forcast_$locationSum.index"
		else
			ALERT "Weather has been updated recently and is stored in the cache."
			ALERT "Check back in 15-20 minutes when the weather station has new info..."
		fi
	done
	# update the stats file
	#if [ $( find /etc/2web/weather/location.d/ -name '*.cfg' | wc -l ) -gt 0 ];then
	if [ $( echo "$totalWeatherLocations" | wc -l ) -gt 0 ];then
		if cacheCheck "$webDirectory/totalWeatherStations.index" "14";then
			#totalWeatherStations=$(find "$webDirectory"/weather/data/ -name 'forcast_*.index' | wc -l)
			#echo "$totalWeatherStations" > "$webDirectory/totalWeatherStations.index"
			echo "$totalWeatherLocations" > "$webDirectory/totalWeatherStations.index"
		fi
	fi
}
################################################################################
function getWeatherIcon(){
	todaysWeather=$1
	#echo "<div> TODAYS WEATHER  $todaysWeather </div>"
	# check for icon  based on the weather data
	if echo "$todaysWeather" | grep -q --ignore-case "snow";then
		iconCode="🌨️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Snow</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "thunderstorm";then
		iconCode="<blink>⛈️</blink>"
		#iconCode="<div class='weatherIcon blink'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Thunderstorm</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "strong wind";then
		iconCode="🌬️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Strong Wind</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "tornado";then
		iconCode="🌪️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Tornado</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "fog";then
		iconCode="🌫️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Fog</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "lightning";then
		iconCode="🌩️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Lightning</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "showers";then
		iconCode="🌦️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Light Rain</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "rain";then
		iconCode="🌧️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Rain</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "sunny";then
		iconCode="☀️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Sunny</div>"
	elif echo "$todaysWeather" | grep -q --ignore-case "cloudy";then
		iconCode="☁️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>cloudy</div>"
	else
		# sunny weather code
		iconCode="☀️"
		#iconCode="<div class='weatherIcon'>$iconCode</div>"
		#iconCode="$iconCode<div class='forcastSub'>Sunny!</div>"
	fi
	echo "$iconCode"
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
webUpdate(){
	webDirectory=$(webRoot)

	# create the kodi directory
	createDir "$webDirectory/kodi/weather/"

	# create the web directory
	createDir "$webDirectory/weather/"

	# link the homepage
	linkFile "/usr/share/2web/templates/weather.php" "$webDirectory/weather/index.php"

	# link the random poster script
	linkFile "/usr/share/2web/templates/randomPoster.php" "$webDirectory/weather/randomPoster.php"
	linkFile "/usr/share/2web/templates/randomFanart.php" "$webDirectory/weather/randomFanart.php"
}
################################################################################
function resetCache(){
	webDirectory=$(webRoot)
	downloadDirectory="$(downloadDir)"
	# remove web cache
	rm -rv "$webDirectory/weather/" || INFO "No comic web directory at '$webDirectory/weather/'"
}
################################################################################
lockProc(){
	# check if system is active
	if test -f "/tmp/weather2web.active";then
		# system is already running exit
		echo "[INFO]: weather2web is already processing data in another process."
		echo "[INFO]: IF THIS IS IN ERROR REMOVE LOCK FILE AT '/tmp/weather2web.active'."
		exit
	else
		# set the active flag
		touch /tmp/weather2web.active
		# create a trap to remove nfo2web lockfile
		trap "rm -v /tmp/weather2web.active" EXIT
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
		rm -rv $(webRoot)/weather/.
		rm -rv $(webRoot)/weather/data/forcast_*.index
		rm -rv $(webRoot)/weather/data/current_*.cfg
	elif [ "$1" == "-r" ] || [ "$1" == "--reset" ] || [ "$1" == "reset" ] ;then
		# lock the process
		lockProc
		# remove the whole weather directory
		rm -rv $(webRoot)/weather/ || INFO "No weather web directory at '$webDirectory/weather/'"
	elif [ "$1" == "-h" ] || [ "$1" == "--help" ] || [ "$1" == "help" ] ;then
		cat "/usr/share/2web/help/weather2web.txt"
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
		# show the server link at the bottom of the interface
		drawLine
		echo "http://$(hostname).local:80/"
		drawLine
		echo "http://$(hostname).local:80/weather/"
		drawLine
		echo "http://$(hostname).local:80/weather.php"
		drawLine
	fi
}
################################################################################
main "$@"
exit
