<?php

authorize();

if (!check_perms('site_torrents_notify')) { error(403); }

if ($_GET['id'] && is_number($_GET['id'])) {
    $DB->query("DELETE FROM users_notify_filters WHERE ID='".db_string($_GET['id'])."' AND UserID='$LoggedUser[ID]'");
}

$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
