<?php
authorize();

if (!check_perms('torrents_freeleech')) error(403);

$TorrentIDs = $_POST['delete_select'];
show_header('Torrents UnFreeleech');
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
          tg.Name,
          t.Time,
          t.FreeTorrent,
          t.DoubleTorrent
          FROM torrents_group AS tg
          JOIN torrents AS t ON t.GroupID = tg.ID
          WHERE tg.ID='$TorrentID'");
    if ($DB->record_count() == 0) { error(404); }
    list($UserID, $GroupID, $Size, $Name, $Time, $Freeleech, $Doubleseed) = $DB->next_record();
    
    if($Freeleech) {
     freedouble_groups($TorrentID, $Freeleech = 0, $Doubleseed);
     echo 'Torrent '.$TorrentID.' ('.$Name.') ('.number_format($Size/(1024*1024), 2).' MB) was marked as UnFreeleech.<br />';
    } 
}
?>
    <h3>Torrents were successfully UnFreeleeched.</h3>
</div>
<?php
show_footer();
