<?php
authorize();

include(SERVER_ROOT . '/sections/torrents/functions.php');

if (!check_perms('torrents_delete')) error(403);

$TorrentIDs = $_POST['delete_select'];

// Check that when delete reason is a full-season trump, we're not actually deleting torrents that are full-season torrents!
if (strpos(strtolower($_POST['reason']), 'full season') !== false) {
    $IDList = implode(',',$TorrentIDs);
    $DB->query("SELECT ID FROM torrents  WHERE ID IN ($IDList) AND (Episode IS NULL or Episode = 0)");
    if ($DB->has_results()) {
        error($_POST['reason'] . ' cannot be used to delete these torrents as not all of them are individual episodes. Go back and try again.');
    }
}

show_header('Torrents deleted');
?>

<div class="thin">
   <br />

<?php
foreach ($TorrentIDs as $TorrentID) {

    if (!$TorrentID || !is_number($TorrentID)) continue;

    $DB->query("SELECT
        t.UserID,
        t.GroupID,
        t.Size,
        t.info_hash,
        tg.Name,
        t.Time,
        COUNT(x.uid)
        FROM torrents AS t
        LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
        LEFT JOIN xbt_snatched AS x ON x.fid=t.ID
        WHERE t.ID='$TorrentID'");
    list($UserID, $GroupID, $Size, $InfoHash, $Name, $Time, $Snatches) = $DB->next_record(MYSQLI_NUM, array(3));

    $InfoHash = unpack("H*", $InfoHash);
    delete_torrent($TorrentID, $GroupID);
    
    // notify active users
    notify_active_users($TorrentID, $Name, $Size, $_POST['reason'], $_POST['extra']);
    
    write_log('Torrent '.$TorrentID.' ('.$Name.') ('.get_size($Size).') ('.strtoupper($InfoHash[1]).') was deleted by '.$LoggedUser['Username'].' (Reason: ' .$_POST['reason'].') '.$_POST['extra']);
    write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "deleted $Name (".get_size($Size).", ".strtoupper($InfoHash[1]).") for reason: ".$_POST['reason']." ".$_POST['extra'], 0);
    echo 'Torrent '.$TorrentID.' ('.$Name.') ('.get_size($Size).') ('.strtoupper($InfoHash[1]).') was deleted by '.$LoggedUser['Username'].' (Reason: ' .$_POST['reason'].') '.$_POST['extra'];
    echo "<br>";
}
?>
    <h3>Torrents were successfully deleted.</h3>
</div>
<?php
show_footer();
