<?php
authorize();

//Set by system
if (!$_POST['groupid'] || !is_number($_POST['groupid'])) {
    error(404);
}
$GroupID = $_POST['groupid'];

//Usual perm checks
if (!check_perms('torrents_edit')) {
    $DB->query("SELECT UserID FROM torrents WHERE GroupID = ".$GroupID);
    if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
        error(403);
    }
}

if ((check_perms('torrents_freeleech') || check_perms('torrents_doubleseed')) && (isset($_POST['freeleech']) || isset($_POST['doubleseed']))) {
    if (check_perms('torrents_freeleech')) {
        $Free = (int) $_POST['freeleech'];
        $Free = $Free==1?1:0;
    } else {
        $Free = false;
    }

    if (check_perms('torrents_doubleseed')) {
        $Double = (int) $_POST['doubleseed'];
        $Double = $Double==1?1:0;
    } else {
        $Double = false;
    }

    freedouble_groups($GroupID, $Free, $Double);
}

header("Location: torrents.php?id=".$GroupID);
