<?php

enforce_login();
authorize();

$UserID = $_POST['userid'];
if (!$UserID || !is_number($UserID)) error(404);

if (!check_perms('torrents_delete')){
    error(403);
}

$Cache->delete_value('shows');

header("Location: shows.php?&did=1");

