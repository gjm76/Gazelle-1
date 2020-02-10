<?php

authorize();

//if (!check_perms('site_torrents_notify')) { error(403); }

if ($_GET['showid'] && is_number($_GET['showid'])) {
	
    $DB->query("DELETE FROM follows_shows WHERE ShowID='".db_string($_GET['showid'])."' AND UserID='$LoggedUser[ID]'");
    
}

//$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
