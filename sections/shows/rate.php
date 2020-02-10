<?php 
authorize();

$UserID = $LoggedUser['ID'];
$ShowID = $_GET['showid'];
$Rating = $_GET['rating'];

if (!is_number($ShowID) || !is_number($Rating)) {
    error(0);
}

$DB->query("SELECT ShowID FROM shows_ratings WHERE UserID=$UserID AND ShowID=$ShowID");

if ($DB->record_count() == 0) {

   $DB->query("INSERT INTO shows_ratings
            (UserID, ShowID, Rating, Time) VALUES
            ('$UserID', '$ShowID', '$Rating', '" . sqltime() . "')");
}else {

   $DB->query("UPDATE shows_ratings SET
             Rating='$Rating',
             Time='" . sqltime() . "'
             WHERE UserID='$UserID'
             AND ShowID='$ShowID'");
}
