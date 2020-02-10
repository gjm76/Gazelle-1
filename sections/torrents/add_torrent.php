<?php
authorize();

include(SERVER_ROOT.'/sections/collages/functions.php');

$Val = new VALIDATE;

function AddTorrent($CollageID, $GroupID)
{
    global $Cache, $LoggedUser, $DB;

/* ----------- Calculate sort ----------- */

    $DB->query("SELECT tg.TagList,
               t.Season
               FROM torrents_group as tg
               JOIN torrents AS t ON tg.ID=t.ID 
               WHERE tg.ID='$GroupID'");
    list($TagList, $Season) = $DB->next_record();
    $Sort = get_collage_sort($Season,$TagList);

/* ----------- Calculate sort old way -- */
    if(!$Sort) {
      $DB->query("SELECT MAX(Sort) FROM collages_torrents WHERE CollageID='$CollageID'");
      list($Sort) = $DB->next_record();
      $Sort+=20;
    }  
/* ------------------------------------- */

    //$DB->query("SELECT GroupID FROM collages_torrents WHERE CollageID='$CollageID' AND GroupID='$GroupID'");
    //if ($DB->record_count() == 0) {

    $DB->query("SELECT GroupID FROM collages_torrents WHERE CollageID='$CollageID'");
      $GroupIDs = $DB->collect('GroupID');
    if (!in_array($GroupID, $GroupIDs)) {
        $DB->query("INSERT IGNORE INTO collages_torrents
            (CollageID, GroupID, UserID, Sort, AddedOn)
            VALUES
            ('$CollageID', '$GroupID', '$LoggedUser[ID]', '$Sort', '".sqltime()."')");

        $DB->query("UPDATE collages SET NumTorrents=NumTorrents+1 WHERE ID='$CollageID'");

        $Cache->delete_value('collage_'.$CollageID);
        $GroupIDs[] = $GroupID; // Clear cache for the torrent we've just added as well
        foreach ($GroupIDs as $GID) {
            $Cache->delete_value('torrents_details_'.$GID);
            $Cache->delete_value('torrent_collages_'.$GID);
            $Cache->delete_value('torrent_collages_personal_'.$GID);
        }

        $DB->query("SELECT UserID FROM users_collage_subs WHERE CollageID=$CollageID");
        while (list($CacheUserID) = $DB->next_record()) {
            $Cache->delete_value('collage_subs_user_new_'.$CacheUserID);
        }
      }
}

$CollageName = $_POST['batchadd'];
// escape '
$CollageName = str_replace("'", "\'", $CollageName);
$CollageName = str_replace("`", "\'", $CollageName);

if (empty($CollageName)) { error("Collage Name is required!"); }

$DB->query("SELECT UserID, ID, CategoryID, Locked, NumTorrents, MaxGroups, MaxGroupsPerUser, Permissions FROM collages WHERE Name = '$CollageName'");
list($UserID, $CollageID, $CategoryID, $Locked, $NumTorrents, $MaxGroups, $MaxGroupsPerUser, $CPermissions) = $DB->next_record();

if (empty($CollageID)) { error("Collage '$CollageName' not found!"); }

//if ($CategoryID == 0 && $UserID!=$LoggedUser['ID'] && !check_perms('site_collages_delete')) { error(403); }

if (!check_perms('site_collages_manage')) {
    $CPermissions=(int) $CPermissions;
    if ($UserID == $LoggedUser['ID']) {
          $CanEdit = true;
    } elseif ($CPermissions>0) {
          $CanEdit = $LoggedUser['Class'] >= $CPermissions;
    } else {
          $CanEdit=false; // can be overridden by permissions
    }
    if (!$CanEdit) { error("No permission to edit!"); }
}

if ($Locked) { error("Locked!"); }

if ($MaxGroups>0 && $NumTorrents>=$MaxGroups) { error("Limit exceeded!"); }

if ($MaxGroupsPerUser>0) {
    $DB->query("SELECT COUNT(ID) FROM collages_torrents WHERE CollageID='$CollageID' AND UserID='$LoggedUser[ID]'");
    if ($DB->record_count()>=$MaxGroupsPerUser) {
        error("Limit exceeded!");
    }
}

if ($_REQUEST['action'] == 'add_torrent_batch') {

    $URLs = $_POST['delete_select'];
    
    $GroupIDs = array();
    $Err = '';

    foreach ($URLs as $URL) {
        $URL = trim($URL);
        
        if ($URL == '') { continue; }

        $DB->query("SELECT ID FROM torrents_group WHERE ID='$URL'");
        if (!$DB->record_count()) {
            $Err = "One of the entered URLs ($URL) does not correspond to a torrent on the site.";
            break;
        }
    }

    if ($Err) {
        error($Err);
        header('Location: collages.php?id='.$CollageID);
        die();
    }

    foreach ($URLs as $GroupID) {
        AddTorrent($CollageID, $GroupID);
    }

    write_log("Collage ".$CollageID." (".db_string($Name).") was edited by ".$LoggedUser['Username']." - added torrents ".implode(',', $URLs));
}

header('Location: collages.php?id='.$CollageID);
