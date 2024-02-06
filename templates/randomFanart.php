<?PHP
########################################################################
# 2web random fanart
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
?>
<?php
	ini_set('display_errors', 1);
	# check the section filter
	if (array_key_exists("filter",$_GET) && ($_GET['filter'] != "")){
		$filterType=$_GET['filter']."_fanart";
	}else{
		$filterType="all_fanart";
	}
	$filterType="_$filterType";

	# load database
	$databaseObj = new SQLite3($_SERVER['DOCUMENT_ROOT']."/backgrounds.db");
	# set the timeout to 1 minute since most webbrowsers timeout loading before this
	$databaseObj->busyTimeout(60000);

	# run query to get 800 random
	$result = $databaseObj->query('select * from "'.$filterType.'" order by random() limit 1;');

	# fetch the row data
	$fileContent=($result->fetchArray())['title'];

	# close the database to process the data
	$databaseObj->close();
	unset($databaseObj);

	// redirect to location of random background
	header('Content-type: image/png');
	header('Cache-Control: max-age=90');
	header('Location: '.$fileContent);
?>
