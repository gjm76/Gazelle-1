<?php
enforce_login();
authorize();

include(SERVER_ROOT . '/sections/torrents/functions.php');

show_header('Torrents scraped');

//check user has permission to scrape
$CanEdit = check_perms('torrents_scrape');

if (!$CanEdit) {
    $DB->query("SELECT UserID, Time FROM torrents WHERE GroupID='$GroupID'");
    list($AuthorID, $AddedTime) = $DB->next_record();
    if ($LoggedUser['ID'] == $AuthorID) {
        if (check_perms ('torrents_scrape')) {
            $CanEdit = true;
        } else {
            error(403);
        }
    }
}

//check user has permission to edit
if (!$CanEdit) { error(403); }
?>

<div class="thin">

<?php
if ($_REQUEST['action'] == 'scrape_torrents') {

    $GroupIDs = $_POST['delete_select'];
    
    if(!$TVMaze) $TVMaze = $_POST['tvmazeid']; // get TVMaze ID, parser rules override it  
    
    foreach ($GroupIDs as $GroupID) {
      scrape($GroupID, $TVMaze, $Season, $Episode);
      echo "Torrent " . '<a href="torrents.php?id=' . $GroupID . '">' . $GroupID . '</a>' . " scraped successfully.<br />";

      $Cache->delete_value('show_'.$TVMaze);

    }
}
?>

   <br />
   <h3>Torrents were successfully scraped with TVMaze ID: <?=$TVMaze?>.</h3>
</div>

<?php
show_footer();
