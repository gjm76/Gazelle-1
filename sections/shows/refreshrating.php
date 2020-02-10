<?php 
authorize();

$ShowID = $_GET['showid'];
$Rating = $_GET['rating'];

if (!is_number($ShowID) || strlen($Rating) > 3 || $Rating > 10) { // fail safe
    error(0);
}

$DB->query("SELECT Updated FROM shows WHERE ID=$ShowID");
list($Updated) = $DB->next_record();

if(strtotime($Updated) < strtotime('today - 1 day')) {
   $DB->query("SELECT ID FROM shows WHERE ID=$ShowID");

   if ($DB->record_count()) {

      $DB->query("UPDATE shows SET
             Rating='$Rating',
             Updated='" . sqltime() . "'
             WHERE ID='$ShowID'");
      $Cache->delete_value('show_static_'.$ShowID);             
   }
}   
