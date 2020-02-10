<?php
if (!check_perms('admin_manage_networks')) { error(403); }

enforce_login();

include(SERVER_ROOT . '/sections/tools/managers/functions.php');
include(SERVER_ROOT . '/sections/shows/functions.php');

$P=array();
$P=db_array($_POST); // Sanitize the form

switch ($_POST['submit']) {
    case 'Set':
        if (!is_number($_POST['tvmaze']) || $_POST['tvmaze'] == '') {
            $Message  = "Show `".$TVMazeTitle."` cannot be set.";
            error(0);
        }

        set_featured_show($P[tvmaze],$P[tvmazetitle],$P[poster],$P[rating],$P[synopsis],$P[unique_tag]);
        $F = get_featured_show();
        list($TVMazeID,$TVMazeTitle,$Synopsis,$TVMazeRating,$TVMazePoster,$SetTime,$Url) = $F;
        $Cache->cache_value('featured_show', $F, 3600);
        $Message = 'Show `'.$TVMazeTitle.'` has been set successfully!';
        break;

    case 'Remove':
        $DB->query("DELETE FROM featured_show");
        $Cache->delete_value('featured_show');
        $Message = 'Featured Show has been removed successfully!';
}

// Go back
header('Location: tools.php?action=featured_show');
