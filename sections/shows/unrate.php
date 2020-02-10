<?php 
authorize();

$UserID = $LoggedUser['ID'];
$ShowID = $_GET['showid'];

if (!is_number($ShowID)) {
    error(0);
}

$DB->query("SELECT ShowID FROM shows_ratings WHERE UserID=$UserID AND ShowID=$ShowID");

if ($DB->record_count()) {
   $DB->query("DELETE FROM shows_ratings WHERE UserID=$UserID AND ShowID=$ShowID");
}
