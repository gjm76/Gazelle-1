<?php
//******************************************************************************//
//--------------- Take unfill request ------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if (!is_number($RequestID)) {
    error(0);
}

$DB->query("SELECT
        r.UserID,
        r.FillerID,
        r.UploaderID,
        r.Title,
        u.Credits,
        f.Credits,
        r.TorrentID,
        r.GroupID
    FROM requests AS r
        LEFT JOIN users_main AS u ON u.ID=UploaderID
        LEFT JOIN users_main AS f ON f.ID=FillerID
    WHERE r.ID= ".$RequestID);
list($UserID, $FillerID, $UploaderID, $Title, $UploaderCredits, $FillerCredits, $TorrentID, $GroupID) = $DB->next_record();

if ((($LoggedUser['ID'] != $UserID && $LoggedUser['ID'] != $FillerID) && !check_perms('site_moderate_requests')) || $FillerID == 0) {
        error(403);
}

// Unfill
$DB->query("UPDATE requests SET
            TorrentID = 0,
            FillerID = 0,
            UploaderID = 0,
            TimeFilled = '0000-00-00 00:00:00',
            Visible = 1
            WHERE ID = ".$RequestID);

$FullName = $Title;

$Reason = $_POST['reason'];

$RequestVotes = get_votes_array($RequestID);
if (!empty($Reason)){
    $Reason = "\nReason: ".$Reason;
} else {
    $Reason = '';
}
if ( $FillerID == $UploaderID || $UploaderID == 0) {
    $DB->query("UPDATE users_main SET Credits = Credits - ".$RequestVotes['TotalBounty']." WHERE ID = ".$FillerID);

    write_user_log($FillerID, "Removed -". $RequestVotes['TotalBounty']. " Cubits because request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] was unfilled.".$Reason);

    update_bonus_log($FillerID, sqltime()." | -".$RequestVotes['TotalBounty']." credits | ".ucfirst("removed -". $RequestVotes['TotalBounty']. " because request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] was unfilled."));

    send_pm($FillerID, 0, db_string("A request you filled has been unfilled"), db_string("The request '[url=http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID."]".$FullName."[/url]' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url].".$Reason."\nThe bounty of ".$RequestVotes['TotalBounty']." Cubits has been removed from your Cubits stats."));
    $Cache->delete_value('user_stats_'.$FillerID);
} else {
    // Remove from filler
    $DB->query("UPDATE users_main SET Credits = Credits - ".($RequestVotes['TotalBounty']/2)." WHERE ID = ".$FillerID);

    write_user_log($FillerID, "Removed -". ($RequestVotes['TotalBounty']/2). " Cubits because request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] was unfilled.".$Reason);

    update_bonus_log($FillerID, sqltime()." | -".($RequestVotes['TotalBounty']/2)." credits | ".ucfirst("removed -". ($RequestVotes['TotalBounty']/2). " because request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] was unfilled."));

    send_pm($FillerID, 0, db_string("A request you filled has been unfilled"), db_string("The request '[url=http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID."]".$FullName."[/url]' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url].".$Reason."\nThe bounty of ".($RequestVotes['TotalBounty']/2)." Cubits has been removed from your Cubits stats."));

    // Remove from uploader
    $DB->query("UPDATE users_main SET Credits = Credits - ".($RequestVotes['TotalBounty']/2)." WHERE ID = ".$UploaderID);

    write_user_log($UploaderID, "Removed -". ($RequestVotes['TotalBounty']/2). " Cubits because request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] was unfilled.".$Reason);

    update_bonus_log($UploaderID, sqltime()." | -".($RequestVotes['TotalBounty']/2)." credits | ".ucfirst("removed -". ($RequestVotes['TotalBounty']/2). " because request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] was unfilled."));

    send_pm($UploaderID, 0, db_string("A request which was filled with one of your torrents has been unfilled"), db_string("The request '[url=http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID."]".$FullName."[/url]' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url].".$Reason."\nThe bounty of ".($RequestVotes['TotalBounty']/2)." Cubits has been removed from your Cubits stats."));

    $Cache->delete_value('user_stats_'.$FillerID);
    $Cache->delete_value('user_stats_'.$UploaderID);
}

send_pm($UserID, 0, db_string("A request you created has been unfilled"), db_string("The request '[url=http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID."]".$FullName."[/url]' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url].".$Reason));
write_log("Request $RequestID ($FullName), with a ".$RequestVotes['TotalBounty']." Cubits bounty, was un-filled by ".$LoggedUser['Username']." for the reason: ".$_POST['reason']);

$Cache->delete_value('request_'.$RequestID);
$Cache->delete_value('requests_torrent_'.$TorrentID);
if ($GroupID) {
    $Cache->delete_value('requests_group_'.$GroupID);
}

update_sphinx_requests($RequestID);

header('Location: requests.php?action=view&id='.$RequestID);
