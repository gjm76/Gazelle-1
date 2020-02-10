<?php

enforce_login();
authorize();

$PersonID = $_GET['personid'];
if (!$PersonID || !is_number($PersonID)) error(404);

if (!check_perms('torrents_delete')){
    error(403);
}

$DB->query("DELETE FROM persons WHERE ID='$PersonID'");
$Cache->delete_value('person_'.$PersonID);

header("Location: torrents.php?action=person&personid=".$PersonID."&did=1");

