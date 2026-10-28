<?PHP
	if (array_key_exists("filter",$_GET)){
		$filterType=$_GET['filter'];
	}else{
		$filterType="all";
	}
?>
<div class='titleCard'>
	<h2>
		Playlists
	</h2>
	<div class='listCard'>
	<?PHP
		#
		$tempButtonStatus="button";
		if (stripos($_SERVER["PHP_SELF"],"/new/") !== false){
			$tempButtonStatus="activeButton";
		}
		if ($filterType=="all"){
			echo "<a class='$tempButtonStatus' href='/new/'>";
		}else{
			echo "<a class='$tempButtonStatus' href='/new/?filter=$filterType'>";
		}
		#
		echo "	📜 New\n";
		echo "</a>\n";
		$tempButtonStatus="button";
		if (stripos($_SERVER["PHP_SELF"],"/random/index.php") !== false){
			$tempButtonStatus="activeButton";
		}
		if ($filterType=="all"){
			echo "<a class='$tempButtonStatus' href='/random/'>";
		}else{
			echo "<a class='$tempButtonStatus' href='/random/?filter=$filterType'>";
		}
		echo "	🔀 Random\n";
		echo "</a>\n";
		$tempButtonStatus="button";
		if (stripos($_SERVER["PHP_SELF"],"/alpha.php") !== false){
			$tempButtonStatus="activeButton";
		}
		if ($filterType=="all"){
			echo "<a class='$tempButtonStatus' href='/alpha.php'>";
		}else{
			echo "<a class='$tempButtonStatus' href='/alpha.php?filter=$filterType'>";
		}
		echo "	🔤 Alphabetical\n";
		echo "</a>\n";
		if(checkModStatus("nfo2web")){
			$tempButtonStatus="button";
			if (stripos($_SERVER["PHP_SELF"],"/tags/") !== false){
				$tempButtonStatus="activeButton";
			}
			if ($filterType=="all"){
				echo "<a class='$tempButtonStatus' href='/tags/'>\n";
			}else{
				echo "<a class='$tempButtonStatus' href='/tags/?filter=$filterType'>\n";
			}
			echo "	🔖 Tags\n";
			echo "</a>\n";
		}
		# draw playlists based on view counts
		if(file_exists("/var/cache/2web/web/views.db")){
			$tempButtonStatus="button";
			if (stripos($_SERVER["PHP_SELF"],"/popular.php") !== false){
				$tempButtonStatus="activeButton";
			}
			if ($filterType=="all"){
				echo "<a class='$tempButtonStatus' href='/popular.php'>";
			}else{
				echo "<a class='$tempButtonStatus' href='/popular.php?filter=$filterType'>";
			}
			echo "	🗳️ Popular";
			echo "</a>";
			#
			$tempButtonStatus="button";
			if (stripos($_SERVER["PHP_SELF"],"/explore.php") !== false){
				$tempButtonStatus="activeButton";
			}
			if ($filterType=="all"){
				echo "<a class='$tempButtonStatus' href='/explore.php'>";
			}else{
				echo "<a class='$tempButtonStatus' href='/explore.php?filter=$filterType'>";
			}
			echo "	🧭 Explore";
			echo "</a>";
			#
			$tempButtonStatus="button";
			if (stripos($_SERVER["PHP_SELF"],"/recommend.php") !== false){
				$tempButtonStatus="activeButton";
			}
			if ($filterType=="all"){
				echo "<a class='$tempButtonStatus' href='/recommend.php'>";
			}else{
				echo "<a class='$tempButtonStatus' href='/recommend.php?filter=$filterType'>";
			}
			echo "	👍 Recommend";
			echo "</a>";
			#
			$tempButtonStatus="button";
			if (stripos($_SERVER["PHP_SELF"],"/discourage.php") !== false){
				$tempButtonStatus="activeButton";
			}
			if ($filterType=="all"){
				echo "<a class='$tempButtonStatus' href='/discourage.php'>";
			}else{
				echo "<a class='$tempButtonStatus' href='/discourage.php?filter=$filterType'>";
			}
			echo "	👎 Discourage";
			echo "</a>";
		}
		?>
	</div>
</div>
