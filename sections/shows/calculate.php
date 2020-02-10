<?php 
authorize();

require_once(SERVER_ROOT.'/sections/torrents/functions.php');

$ShowID = $_GET['showid'];

if (!is_number($ShowID)) {
    error(0);
}

$AverageRating = get_average_rating($ShowID);
$Votes = get_votes($ShowID);

echo $AverageRating."|".$Votes;
