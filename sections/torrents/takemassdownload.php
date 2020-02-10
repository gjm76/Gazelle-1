<?php
authorize();

if (!check_perms('zip_downloader')) error(403);

$TorrentIDs = $_POST['delete_select'];
show_header('Torrents Mass Download');

$SQL = "SELECT
t.GroupID,
t.ID,
t.Size,
t.FileName
FROM torrents AS t
INNER JOIN torrents_group AS tg ON tg.ID=t.GroupID
WHERE t.GroupID IN (".implode(',', $TorrentIDs).")
ORDER BY t.GroupID ASC";

$DB->query($SQL);
$Downloads = $DB->to_array('1',MYSQLI_NUM,false);
$TotalSize = 0;

if (count($Downloads)) {
    $DB->query("SELECT TorrentID, file FROM torrents_files WHERE TorrentID IN (".implode(',', $TorrentIDs).")");
    $Torrents = $DB->to_array('TorrentID',MYSQLI_ASSOC,false);
}

$Zip = new ZIP(file_string('Torrents'));
$Zip->unlimit(); // lets see if this solves the download problems with super large zips

foreach ($Downloads as $Download) {
    list($GroupID, $TorrentID, $Size, $File_Name) = $Download;
    $TotalSize += $Size;
    $Contents = unserialize(base64_decode($Torrents[$TorrentID]['file']));
    $Tor = new TORRENT($Contents, true);
    if($LoggedUser['SSLTracker']) {
        $Tor->set_announce_url(SSL_ANNOUNCE_URL.'/'.$LoggedUser['torrent_pass'].'/announce');
    } else {
        $Tor->set_announce_url(ANNOUNCE_URL.'/'.$LoggedUser['torrent_pass'].'/announce');
    }
    $Tor->set_comment('http://'. SITE_URL."/torrents.php?id=$GroupID");

    unset($Tor->Val['announce-list']);

    // We need this section for long file names :/
    $FileName='';
    $FileName = file_string($File_Name).".".$GroupID;
    $FileName = cut_string($FileName, 192, true, false);

    $Zip->add_file($Tor->enc(), $FileName.'.torrent');
}

$Skipped = count($Skips);
$Downloaded =count($Downloads);
$Time = number_format(((microtime(true)-$ScriptStartTime)*1000),5).' ms';
$Used = get_size(memory_get_usage(true));
$Date = date('M d Y, H:i');
$Zip->add_file('Collector Download Summary - '.SITE_NAME."\r\n\r\nUser:\t\t$LoggedUser[Username]\r\nPasskey:\t$LoggedUser[torrent_pass]\r\n\r\nTime:\t\t$Time\r\nUsed:\t\t$Used\r\nDate:\t\t$Date\r\n\r\nTorrents Downloaded:\t\t$Downloaded\r\n\r\nTotal Size of Torrents (Ratio Hit): ".get_size($TotalSize)."\r\n", 'Summary.txt');
$Settings = array(implode(':',$_REQUEST['list']),$_REQUEST['preference']);
$Zip->close_stream();
