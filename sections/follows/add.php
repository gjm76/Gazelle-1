<?php

// for show page only

authorize();

//if (!check_perms('site_torrents_notify')) { error(403); }

if ($_GET['showid'] && is_number($_GET['showid'])) {

    $sqltime = db_string( sqltime() );
    
    $DB->query("INSERT INTO follows_shows (UserID, ShowID, Time) VALUES ('".$LoggedUser[ID]."','".$_GET['showid']."','".$sqltime."')");

}

//$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
