<?php
if (php_sapi_name() != "cli") error(403);

include(SERVER_ROOT . '/sections/upload/functions.php');

$DB->query("SELECT SQL_CALC_FOUND_ROWS ID, ShowTitle FROM torrents_banners WHERE TVMazeID IS NULL");

$Banners = $DB->to_array();
$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();

foreach($Banners as $Index => $Banner) {
	$TVMazeInfo=get_tvmaze_show_info($Banner['ShowTitle']);
        if(is_numeric($TVMazeInfo['ID']))
            $DB->query("Update torrents_banners SET TVMazeID=$TVMazeInfo[ID] WHERE ID=$Banner[ID]");
        usleep(10000);
}
