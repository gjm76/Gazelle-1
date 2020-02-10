<?php

enforce_login();
authorize();

$ShowID = $_GET['showid'];
if (!$ShowID || !is_number($ShowID)) error(404);

if (!check_perms('torrents_delete')){
    error(403);
}

$DB->query("DELETE FROM shows WHERE ID='$ShowID'");
$Cache->delete_value('show_'.$ShowID);
$Cache->delete_value('show_static_'.$ShowID);

header("Location: torrents.php?action=show&showid=".$ShowID."&did=1");

