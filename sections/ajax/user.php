<?php
authorize(true);

$Text = new TEXT;

require(SERVER_ROOT.'/sections/requests/functions.php');

if (empty($_GET['id']) || !is_numeric($_GET['id'])) { error(0); }
$UserID = $_GET['id'];

if ($UserID == $LoggedUser['ID']) {
    $OwnProfile = true;
} else {
    $OwnProfile = false;
}

// Always view as a normal user.
$DB->query("SELECT
    m.Username,
    m.Email,
    m.LastAccess,
    m.IP,
    p.Level AS Class,
    m.Uploaded,
    m.Downloaded,
    m.RequiredRatio,
    m.Enabled,
    m.Paranoia,
    m.Invites,
    m.Title,
    m.torrent_pass,
    m.can_leech,
    i.JoinDate,
    i.Info,
    i.Avatar,
    i.Country,
    i.Donor,
    i.Warned,
    COUNT(posts.id) AS ForumPosts,
    i.Inviter,
    i.DisableInvites,
    inviter.username
    FROM users_main AS m
    JOIN users_info AS i ON i.UserID = m.ID
    LEFT JOIN permissions AS p ON p.ID=m.PermissionID
    LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
    LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
    WHERE m.ID = $UserID GROUP BY AuthorID");

//TODO: Handle this more gracefully.
if ($DB->record_count() == 0) { // If user doesn't exist
    die();
}

list($Username, $Email, $LastAccess, $IP, $Class, $Uploaded, $Downloaded, $RequiredRatio, $Enabled, $Paranoia, $Invites, $CustomTitle, $torrent_pass, $DisableLeech, $JoinDate, $Info, $Avatar, $Country, $Donor, $Warned, $ForumPosts, $InviterID, $DisableInvites, $InviterName, $RatioWatchEnds, $RatioWatchDownload) = $DB->next_record(MYSQLI_NUM, array(9,11));

$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
    $Paranoia = array();
}
$ParanoiaLevel = 0;
foreach ($Paranoia as $P) {
    $ParanoiaLevel++;
    if (strpos($P, '+')) {
        $ParanoiaLevel++;
    }
}

function check_paranoia_here($Setting)
{
    global $Paranoia, $Class, $UserID;

    return check_paranoia($Setting, $Paranoia, $Class, $UserID);
}

$Friend = false;
$DB->query("SELECT FriendID FROM friends WHERE UserID='$LoggedUser[ID]' AND FriendID='$UserID' AND Type='friends'");
if ($DB->record_count() != 0) {
    $Friend = true;
}

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
    $DB->query("SELECT COUNT(DISTINCT r.ID), SUM(rv.Bounty) FROM requests AS r LEFT JOIN requests_votes AS rv ON r.ID=rv.RequestID WHERE r.FillerID = ".$UserID);
    list($RequestsFilled, $TotalBounty) = $DB->next_record();
    $DB->query("SELECT COUNT(rv.RequestID), SUM(rv.Bounty) FROM requests_votes AS rv WHERE rv.UserID = ".$UserID);
    list($RequestsVoted, $TotalSpent) = $DB->next_record();

    $DB->query("SELECT COUNT(ID) FROM torrents WHERE UserID='$UserID'");
    list($Uploads) = $DB->next_record();
} else {
    $RequestsVoted = 0;
    $TotalSpent = 0;
}
if (check_paranoia_here('uploads+')) {
    $DB->query("SELECT COUNT(ID) FROM torrents WHERE UserID='$UserID'");
    list($Uploads) = $DB->next_record();
} else {
    $Uploads = null;
}

// Do the ranks.
$Rank = new USER_RANK;

if (check_paranoia_here('uploaded')) {
    $UploadedRank = $Rank->get_rank('uploaded', $Uploaded);
} else {
    $UploadedRank = null;
}
if (check_paranoia_here('downloaded')) {
    $DownloadedRank = $Rank->get_rank('downloaded', $Downloaded);
} else {
    $DownloadedRank = null;
}
if (check_paranoia_here('uploads+')) {
    $UploadsRank = $Rank->get_rank('uploads', $Uploads);
} else {
    $UploadsRank = null;
}
if (check_paranoia_here('requestsfilled_count')) {
    $RequestRank = $Rank->get_rank('requests', $RequestsFilled);
} else {
    $RequestRank = null;
}
$PostRank = $Rank->get_rank('posts', $ForumPosts);
if (check_paranoia_here('requestsvoted_bounty')) {
    $BountyRank = $Rank->get_rank('bounty', $TotalSpent);
} else {
    $BountyRank = null;
}

if ($Downloaded == 0) {
    $Ratio = 1;
} elseif ($Uploaded == 0) {
    $Ratio = 0.5;
} else {
    $Ratio = round($Uploaded/$Downloaded, 2);
}
if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty'))) {
    $OverallRank = floor($Rank->overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $Ratio));
} else {
    $OverallRank = null;
}

// Community section
if (check_paranoia_here(array('snatched', 'snatched+'))) {
$DB->query("SELECT COUNT(x.uid), COUNT(DISTINCT x.fid), x.upload FROM xbt_snatched AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID' AND x.upload = '0'");
list($Snatched, $UniqueSnatched) = $DB->next_record();
}

if (check_paranoia_here(array('torrentcomments', 'torrentcomments+'))) {
    $DB->query("SELECT COUNT(ID) FROM torrents_comments WHERE AuthorID='$UserID'");
    list($NumComments) = $DB->next_record();
}

if (check_paranoia_here(array('collages', 'collages+'))) {
    $DB->query("SELECT COUNT(ID) FROM collages WHERE Deleted='0' AND UserID='$UserID'");
    list($NumCollages) = $DB->next_record();
}

if (check_paranoia_here(array('collagecontribs', 'collagecontribs+'))) {
    $DB->query("SELECT COUNT(DISTINCT CollageID) FROM collages_torrents AS ct JOIN collages ON CollageID = ID WHERE Deleted='0' AND ct.UserID='$UserID'");
    list($NumCollageContribs) = $DB->next_record();
}

if (check_paranoia_here('seeding+')) {
    $DB->query("SELECT COUNT(x.uid) FROM xbt_files_users AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID' AND x.remaining=0");
    list($Seeding) = $DB->next_record();
}

if (check_paranoia_here('leeching+')) {
    $DB->query("SELECT COUNT(x.uid) FROM xbt_files_users AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID' AND x.remaining>0");
    list($Leeching) = $DB->next_record();
}

if (check_paranoia_here('invitedcount')) {
    $DB->query("SELECT COUNT(UserID) FROM users_info WHERE Inviter='$UserID'");
    list($Invited) = $DB->next_record();
}

if (!$OwnProfile) {
    $torrent_pass = "";
}

// Run through some paranoia stuff to decide what we can send out.
if (!check_paranoia_here('lastseen')) {
    $LastAccess = "";
}
if (!check_paranoia_here('uploaded')) {
    $Uploaded = null;
}
if (!check_paranoia_here('downloaded')) {
    $Downloaded = null;
}
if (isset($RequiredRatio) && !check_paranoia_here('requiredratio')) {
    $RequiredRatio = null;
}
if ($ParanoiaLevel == 0) {
    $ParanoiaLevelText = 'Off';
} elseif ($ParanoiaLevel == 1) {
    $ParanoiaLevelText = 'Very Low';
} elseif ($ParanoiaLevel <= 5) {
    $ParanoiaLevelText = 'Low';
} elseif ($ParanoiaLevel <= 20) {
    $ParanoiaLevelText = 'High';
} else {
    $ParanoiaLevelText = 'Very high';
}

header('Content-Type: text/plain; charset=utf-8');

print json_encode(array('status' => 'success',
                        'response' => array(
                            'username' => $Username,
                            'avatar' => $Avatar,
                            'isFriend' => $Friend,
                            'profileText' => $Text->full_format($Info),
                            'stats' => array(
                                'joinedDate' => $JoinDate,
                                'lastAccess' => $LastAccess,
                                'uploaded' =>  $Uploaded == null ? null : (int) $Uploaded,
                                'downloaded' => $Downloaded == null ? null: (int) $Downloaded,
                                'ratio' => $Ratio,
                                'requiredRatio' => $RequiredRatio == null ? null : (float) $RequiredRatio
                                ),
                            'ranks' => array(
                                'uploaded' => $UploadedRank,
                                'downloaded' => $DownloadedRank,
                                'uploads' => $UploadsRank,
                                'requests' => $RequestRank,
                                'bounty' => $BountyRank,
                                'posts' => $PostRank,
                                'overall' => $OverallRank == null ? 0 : $OverallRank
                                ),
                            'personal' => array(
                                'class' => $ClassLevels[$Class]['Name'],
                                'paranoia' => $ParanoiaLevel,
                                'paranoiaText' => $ParanoiaLevelText,
                                'donor' => $Donor == 1,
                                'warned' => ($Warned != '0000-00-00 00:00:00'),
                                'enabled' => ($Enabled == '1' || $Enabled == '0' || !$Enabled),
                                'passkey' => $torrent_pass
                            ),
                            'community' => array(
                                'posts' => (int) $ForumPosts,
                                'torrentComments' => (int) $NumComments,
                                'collagesStarted' => $NumCollages == null ? null : (int) $NumCollages,
                                'collagesContrib' => $NumCollageContribs == null ? null : (int) $NumCollageContribs,
                                'requestsFilled' =>  $RequestsFilled == null ? null : (int) $RequestsFilled,
                                'requestsVoted' => $RequestsVoted == null ? null : (int) $RequestsVoted,
                                'perfectFlacs' => $PerfectFLACs == null ? null : (int) $PerfectFlacs,
                                'uploaded' => $Uploads == null ? null : (int) $Uploads,
                                'groups' => $UniqueGroups == null ? null : (int) $UniqueGroups,
                                'seeding' =>  $Seeding == null ? null : (int) $Seeding,
                                'leeching' => $Leeching == null ? null : (int) $Leeching,
                                'snatched' => $Snatched == null ? null : (int) $Snatched,
                                'invited' => $Invited == null ? null : (int) $Invited
                                )
                        )
                    )
                ); // <- He's sad.
