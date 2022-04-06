<?PHP
# write totals for header building
if (file_exists("totalMovies.index")){
	$totalMovies = file_get_contents("totalMovies.index");
}else{
	$totalMovies = 0;
}
if (file_exists("totalShows.index")){
	$totalShows = file_get_contents("totalShows.index");
}else{
	$totalShows = 0;
}
if (file_exists("totalEpisodes.index")){
	$totalEpisodes = file_get_contents("totalEpisodes.index");
}else{
	$totalEpisodes= 0;
}
if (file_exists("totalComics.index")){
	$totalComics = file_get_contents("totalComics.index");
}else{
	$totalComics = 0;
}
if (file_exists("totalChannels.index")){
	$totalChannels = file_get_contents("totalChannels.index");
}else{
	$totalChannels = 0;
}
if (file_exists("totalRadio.index")){
	$totalRadio = file_get_contents("totalRadio.index");
}else{
	$totalRadio = 0;
}
if (file_exists("fortune.index")){
	$todaysFortune = file_get_contents("fortune.index");
}else{
	$todaysFortune = 0;
}
if (file_exists("weather.index")){
	$todaysWeather= file_get_contents("weather.index");
}else{
	$todaysWeather= 0;
}
if (file_exists("webSize.index")){
	$webSize = file_get_contents("webSize.index");
}else{
	$webSize = 0;
}
if (file_exists("mediaSize.index")){
	$mediaSize = file_get_contents("mediaSize.index");
}else{
	$mediaSize = 0;
}
if (file_exists("cacheSize.index")){
	$cacheSize = file_get_contents("cacheSize.index");
}else{
	$cacheSize = 0;
}
if (file_exists("freeSpace.index")){
	$freeSpace = file_get_contents("freeSpace.index");
}else{
	$freeSpace = 0;
}
if (file_exists("lastUpdate.index")){
	$lastUpdate = file_get_contents("lastUpdate.index");
}else{
	$lastUpdate = "Never";
}

echo "<div class='date titleCard'>";
echo "	<div>";
echo "		Last updated on $lastUpdate";
echo "	</div>";
echo "	<div>";
if ( $totalShows > 0 ){
	echo "		<span>";
	echo "			Episodes:$totalEpisodes";
	echo "		</span>";
	echo "		<span>";
	echo "			Shows:$totalShows";
	echo "		</span>";
}
if ( $totalMovies > 0 ){
	echo "		<span>";
	echo "			Movies:$totalMovies";
	echo "		</span>";
}
if ( $totalComics > 0 ){
	echo "		<span>";
	echo "			Comics:$totalComics";
	echo "		</span>";
}
if ( $totalChannels > 0 ){
	echo "		<span>";
	echo "			Channels:$totalChannels";
	echo "		</span>";
}
if ( $totalRadio > 0 ){
	echo "		<span>";
	echo "			Radio:$totalRadio";
	echo "		</span>";
}
echo "		<span>";
echo "			Web:$webSize ";
echo "		</span>";
echo "		<span>";
echo "			Cache:$cacheSize";
echo "		</span>";
echo "		<span>";
echo "			Media:$mediaSize";
echo "		</span>";
echo "		<span>";
echo "			Free:$freeSpace";
echo "		</span>";
echo "	</div>";
if ( file_exists("fortune.index")){
	echo "	<div class='titleCard'>";
	echo "$todaysFortune";
	echo "	</div>";
}
if ( file_exists("weather.index")){
	echo "	<div class='titleCard'>";
	echo "$todaysWeather";
	echo "	</div>";
}
echo "</div>"
?>
