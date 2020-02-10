<?php
authorize();

if (!check_perms('torrents_delete') || !$UserID) error(403);

$TorrentIDs = $_POST['hnr_select'];
$UserID = $_POST['userid'];
show_header('HnRs cleared');
?>

<div class="thin">
   <br />
<?php
foreach ($TorrentIDs as $TorrentID) {

    if (!$TorrentID || !is_number($TorrentID)) continue;

    $DB->query("UPDATE xbt_snatched SET seedtime = '" . ($SiteOptions['HnRSeason'] + 1) . "' WHERE uid='$UserID' AND fid='$TorrentID' AND seedtime < '" . $SiteOptions['HnRSeason'] . "'");
    $DB->query("UPDATE users_info SET AdminComment = CONCAT('".sqltime()." - HnR for http://".SSL_SITE_URL."/torrents.php?id=".$TorrentID." was cleared by ".$LoggedUser['Username']."\n', AdminComment) WHERE UserID = $UserID");    
    $Cache->delete_value('users_torrents_snatched_' . $UserID);
    $Cache->delete_value('user_info_heavy_'.$UserID);
    $Cache->delete_value('user_stats_'.$UserID);    
    echo 'Hnr for <a href="/torrents.php?id='.$TorrentID.'">'.$TorrentID.'</a> was cleared.<br>';
}
?>
    <h3>HnRs were successfully cleared.</h3>
</div>
<?php
show_footer();
