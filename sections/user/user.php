<?php
$Text = new TEXT;

include(SERVER_ROOT.'/sections/requests/functions.php');
include(SERVER_ROOT.'/sections/user/functions.php');

if (empty($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) { error(0); }
$UserID = $_REQUEST['id'];

$OwnProfile = $UserID == $LoggedUser['ID'];

if (check_perms('users_mod')) { // Person viewing is a staff member
    $DB->query("SELECT
        m.Username,
        m.Email,
        m.TwoFactor,
        m.LastAccess,
        m.IP,
        m.ipcc,
        p.Level AS Class,
        m.Uploaded,
        m.Downloaded,
        m.RequiredRatio,
        m.Title,
        m.torrent_pass,
        m.PermissionID AS ClassID,
        m.GroupPermissionID,
        m.Enabled,
        m.Paranoia,
        m.Invites,
        m.can_leech,
        m.Visible,
        i.JoinDate,
        i.Info,
        i.Avatar,
        i.Country,
        i.AdminComment,
        i.Donor,
        i.Warned,
        i.SupportFor,
        i.RestrictedForums,
        i.PermittedForums,
        i.Inviter,
        inviter.Username,
        COUNT(posts.id) AS ForumPosts,
        i.RatioWatchEnds,
        i.RatioWatchDownload,
        i.SuppressConnPrompt,
        i.DisableAvatar,
        i.DisableInvites,
        i.DisablePosting,
        i.DisableForums,
        i.DisableTagging,
        i.DisableUpload,
        i.DisablePM,
        i.DisableIRC,
        i.DisableRequests,
        i.DisableSignature,
        i.DisableTorrentSig,
        i.HideCountryChanges,
        m.FLTokens,
        m.personal_freeleech,
        SHA1(i.AdminComment),
        m.Credits,
        i.BonusLog,
        p.MaxAvatarWidth,
        p.MaxAvatarHeight,
        i.SeedHistory,
        m.SeedHours,
        m.SeedHoursDaily,
        m.CreditsDaily,
        m.Flag,
        i.BanReason,
        m.SeedSize,
        m.HnR,
        i.HnRWatchEnds,
        i.HnRWatchTimes
        FROM users_main AS m
        JOIN users_info AS i ON i.UserID = m.ID
        LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
        LEFT JOIN permissions AS p ON p.ID=m.PermissionID
        LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
        WHERE m.ID = '".$UserID."' GROUP BY AuthorID");

    if ($DB->record_count() == 0) { // If user doesn't exist
        header("Location: log.php?search=User+".$UserID);
    }

    list($Username,$Email,$TwoFactor,$LastAccess,$IP, $ipcc, $Class, $Uploaded, $Downloaded, $RequiredRatio, $CustomTitle, $torrent_pass, $ClassID,
              $GroupPermID, $Enabled, $Paranoia, $Invites, $DisableLeech, $Visible, $JoinDate, $Info, $Avatar, $Country,
              $AdminComment, $Donor, $Warned, $SupportFor, $RestrictedForums, $PermittedForums, $InviterID, $InviterName, $ForumPosts,
              $RatioWatchEnds, $RatioWatchDownload, $SuppressConnPrompt, $DisableAvatar, $DisableInvites, $DisablePosting, $DisableForums, $DisableTagging,
              $DisableUpload, $DisablePM, $DisableIRC, $DisableRequests, $DisableSig, $DisableTorrentSig, $DisableCountry, $FLTokens, $PersonalFreeLeech, $CommentHash,
              $BonusCredits,$BonusLog,$MaxAvatarWidth, $MaxAvatarHeight, $SeedHistory, $SeedHoursTotal, $SeedHoursDaily, $CreditsDaily, $flag, $BanReason, 
              $SeedSize, $HnR, $HnRWatchEnds, $HnRWatchTimes) = $DB->next_record(MYSQLI_NUM, array(14));

} else { // Person viewing is a normal user
    $DB->query("SELECT
        m.Username,
        m.Email,
        m.LastAccess,
        m.IP,
        m.ipcc,
        p.Level AS Class,
        m.Uploaded,
        m.Downloaded,
        m.RequiredRatio,
        m.PermissionID AS ClassID,
        m.GroupPermissionID,
        m.Enabled,
        m.Paranoia,
        m.Invites,
        m.Title,
        m.torrent_pass,
        m.can_leech,
        i.JoinDate,
        i.Info,
        i.Avatar,
        m.FLTokens,
        i.Country,
        i.Donor,
        i.Warned,
        COUNT(posts.id) AS ForumPosts,
        i.Inviter,
        i.DisableInvites,
        inviter.username,
        m.Credits,
        i.BonusLog,
        p.MaxAvatarWidth,
        p.MaxAvatarHeight,
        i.RatioWatchEnds,
        i.RatioWatchDownload,
        m.Flag,
        i.BanReason,
        m.SeedSize,
        m.HnR,
        i.HnRWatchEnds
        FROM users_main AS m
        JOIN users_info AS i ON i.UserID = m.ID
        LEFT JOIN permissions AS p ON p.ID=m.PermissionID
        LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
        LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
        WHERE m.ID = $UserID GROUP BY AuthorID");

    if ($DB->record_count() == 0) { // If user doesn't exist
        header("Location: log.php?search=User+".$UserID);
    }

    list($Username, $Email, $LastAccess, $IP, $ipcc, $Class, $Uploaded, $Downloaded, $RequiredRatio, $ClassID, $GroupPermID,
              $Enabled, $Paranoia, $Invites, $CustomTitle, $torrent_pass, $DisableLeech, $JoinDate, $Info, $Avatar, $FLTokens,
              $Country, $Donor, $Warned, $ForumPosts, $InviterID, $DisableInvites, $InviterName,$BonusCredits,$BonusLog,
              $MaxAvatarWidth,$MaxAvatarHeight, $RatioWatchEnds, $RatioWatchDownload, $flag, $BanReason, $SeedSize, $HnR, $HnRWatchEnds) = $DB->next_record(MYSQLI_NUM, array(12));
}

// Image proxy CTs
$DisplayCustomTitle = $CustomTitle;
if (check_perms('site_proxy_images') && !empty($CustomTitle)) {
    $DisplayCustomTitle = preg_replace_callback('~src=("?)(http.+?)(["\s>])~', function ($Matches) {
                                                                        return 'src='.$Matches[1].'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&amp;i='.urlencode($Matches[2]).$Matches[3];
                                                                    }, $CustomTitle);
}

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

$JoinedDate = time_diff($JoinDate);
$LastAccess = time_diff($LastAccess);

function check_paranoia_here($Setting)
{
    global $Paranoia, $Class, $UserID;

    return check_paranoia($Setting, $Paranoia, $Class, $UserID);
}

$Badges=($Donor) ? '<a href="donate.php"><img src="'.STATIC_SERVER.'common/symbols/donor.svg" alt="Donor" /></a>' : '';

$Badges.=($Warned!='0000-00-00 00:00:00') ? '<img src="'.STATIC_SERVER.'common/symbols/warned.svg" alt="Warned" />' : '';
$Badges.=($Enabled == '1' || $Enabled == '0' || !$Enabled) ? '': '<img src="'.STATIC_SERVER.'common/symbols/disabled.svg" alt="Banned" />';

show_header($Username,'overlib,jquery.cookie,user,bbcode,requests,watchlist');

?>
<div class="thin">
    <h2><?=format_username($UserID, $Username, false, $Warned, $Enabled, $ClassID, $CustomTitle, true, $GroupPermID)?></h2>
    <div class="linkbox">
<?php   if (!$OwnProfile) { ?>
        [ <a href="inbox.php?action=compose&amp;to=<?=$UserID?>" title="Send a Private Message to <?=$Username?>">Send PM</a>
<?php
        if (check_perms('users_mod')) {  ?>
        | <a href="staffpm.php?action=compose&amp;toid=<?=$UserID?>" title="Start a Staff Conversation with <?=$Username?>">Staff Message</a>
<?php       }
        $DB->query("SELECT Type FROM friends WHERE UserID='$LoggedUser[ID]' AND FriendID='$UserID'");
        if($DB->record_count() > 0) list($FType)=$DB->next_record();

        if (!$FType || $FType != 'friends') { ?>
            | <a href="friends.php?action=add&amp;friendid=<?=$UserID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Add to friends</a>
<?php       } elseif ($FType == 'friends') { ?>
            | <a href="friends.php?action=Defriend&amp;friendid=<?=$UserID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Remove friend</a>
<?php       }
        if (!$FType || $FType != 'blocked') { ?>
            | <a href="friends.php?action=add&amp;friendid=<?=$UserID?>&amp;type=blocked&amp;auth=<?=$LoggedUser['AuthKey']?>">Block User</a>
<?php       } elseif ($FType == 'blocked') { ?>
            | <a href="friends.php?action=Unblock&amp;friendid=<?=$UserID?>&amp;type=blocked&amp;auth=<?=$LoggedUser['AuthKey']?>">Remove block</a>
<?php       } ?>
        | <a href="reports.php?action=report&amp;type=user&amp;id=<?=$UserID?>">Report User</a> ]
<?php
    } else {
        // OwnProfile ?>
    [ <a href="userhistory.php?action=useremail&userid=<?=$UserID?>">Email History</a>
    | <a href="userhistory.php?action=userip&userid=<?=$UserID?>">IP History</a> ]
<?php  }

    if (check_perms('users_edit_profiles', $Class)) {
        $links2 .= '[ <a href="user.php?action=edit&amp;userid='.$UserID.'">Settings</a> ';
    }
    if (check_perms('users_view_invites', $Class)) {
        $links2 .= '| <a href="user.php?action=invite&amp;userid='.$UserID.'">Invites</a> ';
    }
    if (check_perms('admin_manage_permissions', $Class)) {
        $links2 .= '| <a href="user.php?action=permissions&amp;userid='.$UserID.'">Permissions</a> ';
    }
    if (check_perms('users_logout', $Class) && check_perms('users_view_ips', $Class)) {
        $links2 .= '| <a href="user.php?action=sessions&amp;userid='.$UserID.'">Sessions</a> ';
    }
    if (check_perms('admin_reports')) {
        $links2 .= '| <a href="reportsv2.php?view=reporter&amp;id='.$UserID.'">Reports</a> ';
    }
    if (check_perms('users_mod')) {
        $links2 .= '| <a href="userhistory.php?action=token_history&amp;userid='.$UserID.'">Slots</a> ';
    }
    if (check_perms('admin_manage_ipbans')) {
        $links2 .= '| <a href="tools.php?action=ip_ban&userid='.$UserID.'&uip='.display_str($IP).'" title="Ban this users current IP ('.display_str($IP).')">IP Ban</a> ';
    }

    if ($links2) echo '<br/>'.$links2;

if (check_perms('users_manage_cheats', $Class)) {
    $DB->query("SELECT UserID FROM users_watch_list WHERE UserID='$UserID'"); ?>
    <span id="wl">
<?php   if ($DB->record_count() > 0) {?>
       | <a onclick="watchlist_remove('<?=$UserID?>');return false;" href="#" title="Remove this user from the speed records user watchlist">Remove from watchlist</a>
<?php   } else {?>
       | <a onclick="watchlist_add('<?=$UserID?>');return false;" href="#" title="Add this user to the speed records user watchlist">Add to watchlist</a>
<?php   } ?>
       | <a href="/tools.php?action=speed_records&viewspeed=0&userid=<?=$UserID;if($Enabled!='1')echo"&viewbanned=1";?>" title="View speed records for this user">View speed records</a>
       | <a href="/user.php?action=next_class&userid=<?=$UserID;?>" title="View Next Class for this user">View Next Class</a> ]
    </span>
<?php
} ?>
    </div>

    <div class="sidebar">
<?php 	if (empty($HeavyInfo['DisableAvatars'])) {
        if (check_perms('site_proxy_images') && !empty($Avatar)) {
            $Avatar = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&avatar='.$UserID.'&i='.urlencode($Avatar);
        }
?>
        <div class="head colhead_dark">Avatar</div>
                <div class="box">
            <div align="center">
            <?php  if ($Avatar) { ?>
                    <img src="<?=$Avatar?>" class="avatar" style="<?=get_avatar_css($MaxAvatarWidth, $MaxAvatarHeight)?>" alt="<?=$Username?>'s avatar" />
            <?php  } else { ?>
                    <img src="<?=STATIC_SERVER?>common/avatars/default.svg" class="avatar" style="<?=get_avatar_css()?>" alt="Default avatar" />
            <?php  } ?>
                  </div>
            </div>
<?php  } ?>

<?php 	if ($flag && $flag != '??') {
        $flag = '<img src="static/common/flags/64/'.$flag.'.png" alt="'.$flag.'" title="'.$flag.'" />';
?>
        <div class="head colhead_dark">Flag</div>
        <div class="box center">
              <?=$flag?>
        </div>
<?php  } ?>

        <div class="head colhead_dark">Stats</div>
        <div class="box">
            <ul class="stats nobullet">
                <li>Joined: <?=$JoinedDate?></li>
<?php  if (check_force_anon($UserID) && check_paranoia_here('lastseen')) { ?>
                <li>Last Seen: <?=$LastAccess?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('uploaded')) { ?>
                <li>Uploaded: <?=get_size($Uploaded)?></li>
<?php  } ?>
<?php  if ($OwnProfile || check_perms('users_mod') || $ClassLevels[$Class]['Level'] < LEVEL_STAFF) { ?>
                <li>HnRs:  <?php if($HnR){echo "<span style='color:red'>";}?><?=$HnR?> <?php if($HnR){echo "</span>";}?>
<?php  } ?>
<?php  if ($OwnProfile || check_perms('users_mod')) { ?>                
                 [<a href="torrents.php?type=hitandrun&amp;userid=<?=$UserID?>">View</a>]</li>
<?php  } ?>                
<?php  if (check_force_anon($UserID) && check_paranoia_here('downloaded') && check_perms('site_downloaded')) { ?>
                <li title="Was on HnR Watch: <?=$HnRWatchTimes?> times">HnR Watch: <?=$HnRWatchTimes?> times</li>
                <li>Downloaded: <?=get_size($Downloaded)?></li>
<?php  } ?>
<?php  if (check_paranoia_here('ratio') && check_perms('site_ratio')) { ?>
                <li>Ratio: <?=ratio($Uploaded, $Downloaded)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('requiredratio') && isset($RequiredRatio) && check_perms('site_ratio')) { ?>
                <li>Required ratio: <?=number_format((double) $RequiredRatio, 2)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('seedsize')) { ?>
                <li>Seeding Size: <?=get_size($SeedSize)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('seedsize')) { 
          list($Seeding)=array_values(user_peers($UserID));?>
                <li>Cubits per hour: <?=ShowCubitsRate($Seeding,$SeedSize)?></li>
<?php  } ?>                
<?php  if (check_force_anon($UserID) && ($OwnProfile || check_paranoia_here(false))) { //if ($OwnProfile || check_perms('users_mod')) { ?>
                <li><a href="userhistory.php?action=token_history&amp;userid=<?=$UserID?>">Slots</a>: <?=number_format($FLTokens)?></li>
<?php  } ?>
            </ul>
        </div>
<?php

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
    $DB->query("SELECT COUNT(DISTINCT r.ID), SUM(rv.Bounty) FROM requests AS r LEFT JOIN requests_votes AS rv ON r.ID=rv.RequestID WHERE r.FillerID = ".$UserID);
    list($RequestsFilled, $TotalBounty) = $DB->next_record();
} else {
    $RequestsFilled = $TotalBounty = 0;
}

if (check_paranoia_here('requestsvoted_count') || check_paranoia_here('requestsvoted_bounty')) {
    $DB->query("SELECT COUNT(rv.RequestID), SUM(rv.Bounty) FROM requests_votes AS rv WHERE rv.UserID = ".$UserID);
    list($RequestsVoted, $TotalSpent) = $DB->next_record();
} else {
    $RequestsVoted = $TotalSpent = 0;
}

if (check_paranoia_here('uploads+')) {
    $DB->query("SELECT COUNT(ID) FROM torrents WHERE UserID='$UserID'");
    list($Uploads) = $DB->next_record();
} else {
    $Uploads = 0;
}

$Rank = new USER_RANK;

$UploadedRank = $Rank->get_rank('uploaded', $Uploaded);
$DownloadedRank = $Rank->get_rank('downloaded', $Downloaded);
$UploadsRank = $Rank->get_rank('uploads', $Uploads);
$RequestRank = $Rank->get_rank('requests', $RequestsFilled);
$PostRank = $Rank->get_rank('posts', $ForumPosts);
$BountyRank = $Rank->get_rank('bounty', $TotalSpent);

if ($Downloaded == 0) {
    $Ratio = 1;
} elseif ($Uploaded == 0) {
    $Ratio = 0.5;
} else {
    $Ratio = round($Uploaded/$Downloaded, 2);
}
$OverallRank = $Rank->overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $Ratio);

?>
        <div class="head colhead_dark">Percentile Rankings (hover for values)</div>
        <div class="box">
            <ul class="stats nobullet">
<?php  if (check_paranoia_here('uploaded')) { ?>
                <li title="<?=get_size($Uploaded)?>" data-tooltip-position="left">Data uploaded: <?=number_format($UploadedRank)?></li>
<?php  } ?>
<?php  if (check_paranoia_here('downloaded') && check_perms('site_ratio')) { ?>
                <li title="<?=get_size($Downloaded)?>" data-tooltip-position="left">Data downloaded: <?=number_format($DownloadedRank)?></li>
<?php  } ?>
<?php  if (check_paranoia_here('uploads+')) { ?>
                <li title="<?=$Uploads?>" data-tooltip-position="left">Torrents uploaded: <?=number_format($UploadsRank)?></li>
<?php  } ?>
<?php  if (check_paranoia_here('requestsfilled_count')) { ?>
                <li title="<?=$RequestsFilled?>" data-tooltip-position="left">Requests filled: <?=number_format($RequestRank)?></li>
<?php  } ?>
<?php  if (check_paranoia_here('requestsvoted_bounty')) { ?>
                <li title="<?=intval($TotalSpent)?>" data-tooltip-position="left">Bounty spent: <?=number_format($BountyRank)?></li>
<?php  } ?>
                <li title="<?=$ForumPosts?>" data-tooltip-position="left">Posts made: <?=number_format($PostRank)?></li>
<?php  if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty'))) { ?>
                <li><strong>Overall rank: <?=number_format($OverallRank)?></strong></li>
<?php  } ?>
            </ul>
        </div>
<?php
    if (check_perms('users_mod', $Class) || check_perms('users_view_ips',$Class) || check_perms('users_view_keys',$Class)) {
        $DB->query("SELECT COUNT(*) FROM users_history_passwords WHERE UserID='$UserID'");
        list($PasswordChanges) = $DB->next_record();
        if (check_perms('users_view_keys',$Class)) {
            $DB->query("SELECT COUNT(*) FROM users_history_passkeys WHERE UserID='$UserID'");
            list($PasskeyChanges) = $DB->next_record();
        }
        if (check_perms('users_view_ips',$Class)) {
            $DB->query("SELECT COUNT(DISTINCT IP) FROM users_history_ips WHERE UserID='$UserID'");
            list($IPChanges) = $DB->next_record();
            $DB->query("SELECT COUNT(DISTINCT IP) FROM xbt_snatched WHERE uid='$UserID' AND IP != ''");
            list($TrackerIPs) = $DB->next_record();
        }
        if (check_perms('users_view_email',$Class)) {
            $DB->query("SELECT COUNT(*) FROM users_history_emails WHERE UserID='$UserID'");
            list($EmailChanges) = $DB->next_record();
        }
?>
    <div class="head colhead_dark">History</div>
    <div class="box">
        <ul class="stats nobullet">
<?php 	if (check_perms('users_view_email',$Class)) { ?>
<li>Emails: <?=number_format($EmailChanges)?> [<a href="userhistory.php?action=email2&amp;userid=<?=$UserID?>">View</a>]&nbsp;[<a href="userhistory.php?action=email&amp;userid=<?=$UserID?>">Legacy view</a>]</li>
<?php
    }
    if (check_perms('users_view_ips',$Class)) {
?>
    <li>IPs: <?=number_format($IPChanges)?> [<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>">View</a>]&nbsp;[<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>&amp;usersonly=1">View Users</a>]</li>
<?php 		if (check_perms('users_view_ips',$Class) && check_perms('users_mod',$Class)) {
?>
    <li>Tracker IPs: <?=number_format($TrackerIPs)?> [<a href="userhistory.php?action=tracker_ips&amp;userid=<?=$UserID?>">View</a>]</li>
<?php 		} ?>
<?php
    }
    if (check_perms('users_view_keys',$Class)) {
?>
            <li>Passkeys: <?=number_format($PasskeyChanges)?> [<a href="userhistory.php?action=passkeys&amp;userid=<?=$UserID?>">View</a>]</li>
<?php
    }
    if (check_perms('users_mod', $Class)) {
?>
            <li>Passwords: <?=number_format($PasswordChanges)?> [<a href="userhistory.php?action=passwords&amp;userid=<?=$UserID?>">View</a>]</li>
            <li>Stats: N/A [<a href="userhistory.php?action=stats&amp;userid=<?=$UserID?>">View</a>]</li>
<?php

    }
?>
        </ul>
    </div>
<?php 	} ?>

        <div class="head colhead_dark">Personal</div>
        <div class="box">
            <ul class="stats nobullet">

<?php       if (check_perms('users_view_language', $Class) || $OwnProfile) {

                $Userlangs = $Cache->get_value('user_langs_' .$UserID);
                if ($Userlangs===false) {
                    $DB->query("SELECT ul.LangID, l.code, l.flag_cc AS cc, l.language
                              FROM users_languages AS ul
                              JOIN languages AS l ON l.ID=ul.LangID
                             WHERE UserID=$UserID");
                    $Userlangs = $DB->to_array('LangID', MYSQL_ASSOC);
                    $Cache->cache_value('user_langs_'.$UserID, $Userlangs);
                }
                //$DB->query("SELECT ul.cc, country  FROM users_languages AS ul LEFT JOIN countries AS c ON c.cc=ul.cc WHERE UserID=$UserID");
                if ($Userlangs) {
?>
                <li>Languages:
<?php
                    foreach ($Userlangs as $langresult) {
?>
                        <img style="vertical-align: bottom" title="<?=$langresult['language']?>" alt="[<?=$langresult['code']?>]" src="http://<?=SITE_URL?>/static/common/flags/iso16/<?=$langresult['cc']?>.png" />
<?php
                    }
?>
                </li>
<?php
                }
        }
?>
                <li>Class: <?=$ClassLevels[$Class]['Name']?></li>
<?php
// An easy way for people to measure the paranoia of a user, for e.g. contest eligibility
if ($ParanoiaLevel == 0) {
    $ParanoiaLevelText = 'Off';
} elseif ($ParanoiaLevel == 1) {
    $ParanoiaLevelText = 'Very Low';
} elseif ($ParanoiaLevel <= 5) {
    $ParanoiaLevelText = 'Low';
} elseif ($ParanoiaLevel <= 10) {
    $ParanoiaLevelText = 'Medium';
} elseif ($ParanoiaLevel <= 20) {
    $ParanoiaLevelText = 'High';
} else {
    $ParanoiaLevelText = 'Very high';
}

    if (check_force_anon($UserID)) { ?>
                <li>Paranoia level: <span title="<?=$ParanoiaLevel?>"><?=$ParanoiaLevelText?></span></li>
<?php   }

    if (check_perms('users_view_email',$Class) || $OwnProfile) { ?>
                <li>Email: <a href="mailto:<?=display_str($Email)?>"><?=display_str($Email)?></a>
<?php 		if (check_perms('users_view_email',$Class)) { ?>
                    [<a href="user.php?action=search&amp;email_history=on&amp;email=<?=display_str($Email)?>" title="Search">S</a>]
<?php 		} ?>
                </li>
<?php 	}

if (check_perms('users_view_ips',$Class)) {
?>
                <li>IP: <?=display_ip($IP, $ipcc)?></li>
                <li>Host: <?=get_host($IP)?></li>
<?php
}

if (check_perms('users_view_keys',$Class) || $OwnProfile) {
?>
                <li>Passkey: <?=display_str($torrent_pass)?></li>
<?php  }
if (check_perms('users_view_invites')) {
    if (!$InviterID) {
        $Invited="<i>Nobody</i>";
    } else {
        $Invited='<a href="user.php?id='.$InviterID.'">'.$InviterName.'</a>';
    }

?>
                <li>Invited By: <?=$Invited?></li>
                <li>Invites: <?php
                $DB->query("SELECT count(InviterID) FROM invites WHERE InviterID = '$UserID'");
                list($Pending) = $DB->next_record();
                if ($DisableInvites) {
                    echo 'X';
                } else {
                    echo number_format($Invites);
                }
                echo " (".$Pending.")"
                ?></li>
<?php
}

if (check_perms('users_mod') || $OwnProfile) {
    ?>
        <li>Clients: <?php
        $DB->query("SELECT useragent, ip, LEFT(peer_id, 8) AS clientid
                      FROM xbt_files_users WHERE uid ='".$UserID."'
                  GROUP BY useragent, ip");
        while (list($Client, $ClientIP, $ClientID) = $DB->next_record()) {
            $Clients .= "<br/>&nbsp; &bull; <span title=\"$ClientID on $ClientIP\">$Client</span>";
        }
        echo $Clients;
        ?></li>

        <li>Connectable: <br/><?php
        // connectable status(es)
    $DB->query("
        SELECT ucs.Status, ucs.IP, xbt.port, Max(ucs.Time)
          FROM users_connectable_status AS ucs
     LEFT JOIN xbt_files_users AS xbt ON xbt.uid=ucs.UserID AND xbt.ip=ucs.IP AND xbt.Active='1'
         WHERE UserID = '$UserID'
      GROUP BY ucs.IP
      ORDER BY Max(ucs.Time) DESC LIMIT 100");

        $elemid = 0;
        while (list($Status, $IP, $Port, $TimeChecked) = $DB->next_record()) {
            if ($Status == 'yes') {
                $color = 'green';
                $show = 'Yes';
            } elseif ($Status == 'no') {
                $color = 'red';
                $show = 'No';
            } else {
                $color = 'grey';
                $show = '?';
            }
            ?>
                <span id="statuscont<?=$elemid?>" title="status last checked at <?=time_diff($TimeChecked,2,false,false,0)?>">
                    <span id="status<?=$elemid?>" class="<?=$color?>"><?=$show?></span> &nbsp; <?=$IP?> &nbsp;&nbsp;
                <?php    if ($Status!='unset') {  ?>
                <a id="unset<?=$elemid?>" style="cursor: pointer;" onclick="unset_conn_status('status<?=$elemid?>', 'unset<?=$elemid?>', '<?=$UserID?>','<?=$IP?>')">[U]</a>
                <?php    }   ?>
                 <a style="cursor: pointer;" onclick="delete_conn_record('statuscont<?=$elemid?>','<?=$UserID?>','<?=$IP?>')">[X]</a>
                    <?php  if ($Port) { ?>
                 [<a href="user.php?action=connchecker&checkuser=<?=$UserID?>&checkip=<?=$IP?>&checkport=<?=$Port?>">check</a>]
                    <?php  } ?>
                </span><br/>
            <?php
            $elemid++;
        }
        ?></li>
<?php
}
?>
            </ul>
        </div>
<?php
// These stats used to be all together in one UNION'd query
// But we broke them up because they had a habit of locking each other to death.
// They all run really quickly anyways.
$DB->query("SELECT COUNT(x.uid), COUNT(DISTINCT x.fid), x.upload FROM xbt_snatched AS x WHERE x.uid='$UserID' AND x.upload= '0'");
list($Snatched, $UniqueSnatched) = $DB->next_record();

$DB->query("SELECT COUNT(ID) FROM torrents_comments WHERE AuthorID='$UserID'");
list($NumComments) = $DB->next_record();

$DB->query("SELECT COUNT(ID) FROM collages WHERE Deleted='0' AND UserID='$UserID'");
list($NumCollages) = $DB->next_record();

$DB->query("SELECT COUNT(DISTINCT CollageID) FROM collages_torrents AS ct JOIN collages ON CollageID = ID WHERE Deleted='0' AND ct.UserID='$UserID'");
list($NumCollageContribs) = $DB->next_record();

$DB->query("SELECT COUNT(DISTINCT GroupID) FROM torrents WHERE UserID = '$UserID'");
list($UniqueGroups) = $DB->next_record();

?>
        <div id="community" class="head colhead_dark">Community</div>
        <div class="box">
            <ul class="stats nobullet">

<?php
    /*
     * Lets just skip the tag stats for the moment and see
     * (added a switch in case we want to check it)
     */
    if (check_force_anon($UserID) && check_paranoia_here('tags+')) {

        $UserTagCount = $Cache->get_value('user_tag_count_'.$UserID);

        if (is_array($UserTagCount)) {

            list($NumOwnTags, $NumOthersTags, $NumVotesOwn, $NumVotesOthers) = $UserTagCount;

        } else {

            $DB->query("SELECT COUNT(tt.TagID) FROM torrents_tags AS tt
                          JOIN torrents AS t ON t.GroupID=tt.GroupID
                         WHERE tt.UserID = '$UserID'
                           AND t.UserID = '$UserID'");
            list($NumOwnTags) = $DB->next_record(MYSQL_NUM);

            $DB->query("SELECT COUNT(tt.TagID) FROM torrents_tags AS tt
                          JOIN torrents AS t ON t.GroupID=tt.GroupID 
                         WHERE tt.UserID = '$UserID'
                           AND t.UserID != '$UserID'");
            list($NumOthersTags) = $DB->next_record(MYSQL_NUM);

            $DB->query("SELECT COUNT(ttv.TagID) FROM torrents_tags_votes AS ttv
                          JOIN torrents AS t ON t.GroupID=ttv.GroupID 
                         WHERE ttv.UserID = '$UserID'
                           AND t.UserID = '$UserID'");
            list($NumVotesOwn) = $DB->next_record(MYSQL_NUM);

            $DB->query("SELECT COUNT(ttv.TagID) FROM torrents_tags_votes AS ttv
                         WHERE ttv.UserID = '$UserID'");
            list($NumVotesTotal) = $DB->next_record(MYSQL_NUM);
            # != is expensive on large DB tables
            $NumVotesOthers = $NumVotesTotal - $NumVotesOwn;

            $UserTagCount = array($NumOwnTags, $NumOthersTags, $NumVotesOwn, $NumVotesOthers);
            $Cache->cache_value('user_tag_count_'.$UserID , $UserTagCount, 3600 );
        }

    }

   if (check_force_anon($UserID) && check_paranoia_here('tags')) { ?>
                <li>Tags added: <span title="Tags on other uploaders torrents added"><?=$NumOthersTags?></span>
                                <span title="Tags on own torrents added (<?=($NumOthersTags+$NumOwnTags)?> total)">(+<?=$NumOwnTags?>) </span>
                                [<a href="userhistory.php?action=tag_history&amp;type=added&amp;userid=<?=$UserID?>" title="View all tags added by <?=$Username?>">View</a>]
                </li>
<?php   } elseif (check_paranoia_here('tags+')) { ?>
                <li>Tags added: <span title="Tags on other uploaders torrents added"><?=$NumOthersTags?></span>
                                <span title="Tags on own torrents added (<?=($NumOthersTags+$NumOwnTags)?> total)">(+<?=$NumOwnTags?>) </span>
                </li>
<?php   }

?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('torrentcomments')) { ?>
                <li>Forum Posts: <?=number_format($ForumPosts)?> [<a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>" title="View all forum posts by <?=$Username?>">View</a>]</li>
                <li>Torrent Comments: <?=number_format($NumComments)?> [<a href="userhistory.php?action=comments&amp;userid=<?=$UserID?>" title="View all torrent comments by <?=$Username?>">View</a>]</li>
<?php  } elseif (check_paranoia_here('torrentcomments+')) { ?>
                <li>Forum Posts: <?=number_format($ForumPosts)?></li>
                <li>Torrent Comments: <?=number_format($NumComments)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('collages')) { ?>
                <li>Collages started: <?=number_format($NumCollages)?> [<a href="collages.php?userid=<?=$UserID?>" title="View all collages started by <?=$Username?>">View</a>]</li>
<?php  } elseif (check_paranoia_here('collages+')) { ?>
                <li>Collages started: <?=number_format($NumCollages)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('collagecontribs')) { ?>
                <li>Collages contributed to: <?=number_format($NumCollageContribs)?> [<a href="collages.php?userid=<?=$UserID?>&amp;contrib=1" title="View all collages added to by <?=$Username?>">View</a>]</li>
<?php  } elseif (check_paranoia_here('collagecontribs+')) { ?>
                <li>Collages contributed to: <?=number_format($NumCollageContribs)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('requestsfilled_list')) { ?>
                <li>Requests filled: <?=number_format($RequestsFilled)?> for <?=intval($TotalBounty)?>  Cubits [<a href="requests.php?type=filled&amp;userid=<?=$UserID?>" title="View all requests filled by <?=$Username?>">View</a>]</li>
<?php  } elseif (check_paranoia_here(array('requestsfilled_count', 'requestsfilled_bounty'))) { ?>
                <li>Requests filled: <?=number_format($RequestsFilled)?> for <?=intval($TotalBounty)?> Cubits</li>
<?php  } elseif (check_paranoia_here('requestsfilled_count')) { ?>
                <li>Requests filled: <?=number_format($RequestsFilled)?></li>
<?php  } elseif (check_paranoia_here('requestsfilled_bounty')) { ?>
                <li>Requests filled: <?=intval($TotalBounty)?> Cubits collected</li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('requestsvoted_list')) { ?>
                <li>Requests voted: <?=number_format($RequestsVoted)?> for <?=intval($TotalSpent)?> [<a href="requests.php?type=voted&amp;userid=<?=$UserID?>" title="View all requests added to by <?=$Username?>">View</a>]</li>
<?php  } elseif (check_paranoia_here(array('requestsvoted_count', 'requestsvoted_bounty'))) { ?>
                <li>Requests voted: <?=number_format($RequestsVoted)?> for <?=intval($TotalSpent)?> Cubits</li>
<?php  } elseif (check_paranoia_here('requestsvoted_count')) { ?>
                <li>Requests voted: <?=number_format($RequestsVoted)?></li>
<?php  } elseif (check_paranoia_here('requestsvoted_bounty')) { ?>
                <li>Requests voted: <?=intval($TotalSpent)?> Cubits spent</li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('uploads')) { ?>
                <li>Uploaded: <?=number_format($Uploads)?> [<a href="torrents.php?type=uploaded&amp;userid=<?=$UserID?>" title="View all uploads by <?=$Username?>">View</a>]
            <?php   if ($OwnProfile || check_perms('zip_downloader')) { ?>
                    [<a href="torrents.php?action=redownload&amp;type=uploads&amp;userid=<?=$UserID?>" title="Download all uploaded torrents in a zip" onclick="return confirm('If you no longer have the content, your stats WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]
            <?php   } ?>
                </li>
<?php  } elseif (check_paranoia_here('uploads+')) { ?>
                <li>Uploaded: <?=number_format($Uploads)?></li>
<?php  } ?>
<?php

if (check_paranoia_here('seeding+') || check_paranoia_here('leeching+')) {
    list($Seeding, $Leeching)=array_values(user_peers($UserID));
}
?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('seeding')) { ?>
                <li>Seeding: <?=number_format($Seeding)?> <?=($Snatched && ($OwnProfile || check_paranoia_here(false)))?'(' . 100*min(1,round($Seeding/$UniqueSnatched,2)).'%) ':''?>[<a href="torrents.php?type=seeding&amp;userid=<?=$UserID?>" title="View seeding torrents">View</a>]<?php  if ($OwnProfile || check_perms('zip_downloader')) { ?> [<a href="torrents.php?action=redownload&amp;type=seeding&amp;userid=<?=$UserID?>"  title="Download all seeding torrents in a zip"onclick="return confirm('If you no longer have the content, your stats WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]<?php  } ?></li>
<?php  } elseif (check_paranoia_here('seeding+')) { ?>
                <li>Seeding: <?=number_format($Seeding)?></li>
<?php  } ?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('leeching')) { ?>
                <li>Leeching: <?=number_format($Leeching)?> [<a href="torrents.php?type=leeching&amp;userid=<?=$UserID?>" title="View leeching torrents">View</a>]<?=($DisableLeech == 0 && check_perms('users_view_ips')) ? "<strong> (Disabled)</strong>" : ""?></li>
<?php  } elseif (check_paranoia_here('leeching+')) { ?>
                <li>Leeching: <?=number_format($Leeching)?></li>
<?php  }
?>
<?php  if (check_force_anon($UserID) && check_paranoia_here('snatched')) { ?>
                <li>Snatched: <span title="total snatched"><?=number_format($Snatched)?></span>
                              <span title="total unique snatched">(<?=number_format($UniqueSnatched)?>)</span>
                [<a href="torrents.php?type=snatched&amp;userid=<?=$UserID?>" title="View snatched torrents">View</a>]
                <?php  if ($OwnProfile || check_perms('zip_downloader')) { ?>
                    [<a href="torrents.php?action=redownload&amp;type=snatches&amp;userid=<?=$UserID?>" title="Download all snatched torrents in a zip" onclick="return confirm('If you no longer have the content, your stats WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]
                <?php  } ?>
                </li>
<?php  } elseif (check_paranoia_here('snatched+')) { ?>
                <li>Snatched: <span title="total snatched"><?=number_format($Snatched)?></span>
                              <span title="total unique snatched">(<?=number_format($UniqueSnatched)?>)</span></li>
<?php 	//} ?>
<?php  }



if (check_paranoia_here('grabbed+')) {

    $DB->query("SELECT COUNT(ud.UserID), COUNT(DISTINCT ud.TorrentID) FROM users_downloads AS ud WHERE ud.UserID='$UserID'");
    list($NumDownloads, $UniqueDownloads) = $DB->next_record();
?>
        <li>Grabbed: <span title="total grabbed"><?=number_format($NumDownloads)?></span>
                     <span title="total unique grabbed">(<?=number_format($UniqueDownloads)?>) </span>

<?php       if (check_force_anon($UserID) && check_paranoia_here('grabbed')) { ?>
            [<a href="torrents.php?type=downloaded&amp;userid=<?=$UserID?>" title="View grabbed torrents">View</a>]
                <?php  if ($OwnProfile || check_perms('zip_downloader')) { ?>
                    [<a href="torrents.php?action=redownload&amp;type=grabbed&amp;userid=<?=$UserID?>" title="Download all grabbed torrents in a zip" onclick="return confirm('If you no longer have the content, your stats WILL be affected, be sure to check the size of all torrents before redownloading.');">Download</a>]
                <?php  } ?>
<?php       } ?>
        </li>
<?php
}

if (check_paranoia_here('rated+')) {

    $DB->query("SELECT COUNT(ShowID) FROM shows_ratings WHERE UserID='$UserID'");
    list($Rated) = $DB->next_record();
?>
        <li>Rated: <span title="total rated"><?=number_format($Rated)?></span>

<?php       if (check_force_anon($UserID) && check_paranoia_here('rated')) { ?>
            [<a href="user.php?action=ratings&userid=<?=$UserID?>" title="View Rated Shows">View</a>]
<?php       } ?>
        </li>
<?php
}

if (check_paranoia_here('follow+')) {

    $DB->query("SELECT COUNT(ShowID) FROM follows_shows WHERE UserID='$UserID'");
    list($Follow) = $DB->next_record();
?>
        <li>Following: <span title="total followed shows"><?=number_format($Follow)?></span>

<?php       if (check_force_anon($UserID) && check_paranoia_here('follow')) { ?>
            [<a href="follows.php?userid=<?=$UserID?>" title="View My Shows">View</a>]
<?php       } ?>
        </li>
<?php
}

if ($OwnProfile || check_perms('users_view_donor')) {

        $DB->query("SELECT COUNT(ID) FROM bitcoin_donations WHERE state='unused' AND userID='$UserID'");
        list($NumDonationsIssued) = $DB->next_record();
        $DB->query("SELECT COUNT(ID), Sum(amount_euro) FROM bitcoin_donations WHERE state!='unused' AND userID='$UserID'");
        list($NumDonations, $SumDonations) = $DB->next_record();
?>
        <li>Donated: <strong>&euro;<?=number_format($SumDonations, 2) ?></strong>
            &nbsp; <span title="number of donations made"><?=number_format($NumDonations)?></span>
            <span title="donation addresses unused">(<?=number_format($NumDonationsIssued)?>)</span>
             [<a href="donate.php?action=my_donations&amp;userid=<?=$UserID?>" title="View donations">View</a>]</li>
<?php
}

if (check_force_anon($UserID) && check_paranoia_here('invitedcount')) {
    $DB->query("SELECT COUNT(UserID) FROM users_info WHERE Inviter='$UserID'");
    list($Invited) = $DB->next_record();
?>
                <li>Invited: <?=number_format($Invited)?></li>
<?php
}
?>
            </ul>
        </div>
    </div>
    <div class="main_column">
<?php
        $CookieItems=array();
        $CookieItems[] = 'profile';

    if(check_force_anon($UserID) &&  $RatioWatchEnds!='0000-00-00 00:00:00'
        && ($Downloaded*$RequiredRatio)>$Uploaded ) {
?>
        <div class="head">Ratio watch</div>
        <div class="box pad">
<?php
            if ($DisableLeech == 1) {
?>
                This user is currently on ratio watch, and must upload <?=get_size(($Downloaded*$RequiredRatio)-$Uploaded)?> in the next <?=time_diff($RatioWatchEnds,2,true,false,0)?>, or their leeching privileges will be revoked. Amount downloaded while on ratio watch: <?=get_size($Downloaded-$RatioWatchDownload)?>
<?php           } else {    ?>
                This user is currently on ratio watch, their downloading privileges are disabled until they meet their required ratio. Upload required: <?=get_size(($Downloaded*$RequiredRatio)-$Uploaded)?>
<?php           }       ?>
        </div>
<?php   } 

    if($HnRWatchEnds!='0000-00-00 00:00:00' && $HnR >= $SiteOptions['HnRThreshold'] ) {
?>
        <div class="head">HnR watch</div>
        <div class="box pad">
<?php
            if ($DisableLeech == 1) {
?>
                This user is currently on HnR watch, and must fix <?=$HnR?> HnRs within the next <?=time_diff($HnRWatchEnds,2,true,false,0)?>, or their leeching privileges will be revoked.
<?php           } else {    ?>
                This user is currently on HnR watch, their downloading privileges are disabled until they fix their HnRs. Number of HnRs required to fix: <?=$HnR?>.
<?php           }       ?>
        </div>
<?php   } ?>

            <div class="head">
                <span style="float:left;">Profile<?php  if ($CustomTitle) { echo " - ".display_str(html_entity_decode($DisplayCustomTitle)); } ?></span>
                <span style="float:right;"><?=!empty($Badges)?"$Badges&nbsp;&nbsp;":''?>
                    <a id="profilebutton" href="#" onclick="return Toggle_view('profile');">(Hide)</a></span>&nbsp;
            </div>
            <div class="box">
                <div id="profilediv">
                    <div class="pad">
<?php               if (!$Info) { ?>
                This profile is currently empty.
<?php               } else {
                        echo $Text->full_format($Info, get_permissions_advtags($UserID));
                }   ?>
                    </div>
<?php
            $UserBadges = get_user_badges($UserID);
            if ($UserBadges) {  ?>
                    <div id="userbadges" class="badgesrow badges"><h3>Badges</h3>
<?php                           print_badges_array($UserBadges, false);  ?>
                    </div>
<?php           }   
            $UserStamps = Stamps::getUserStamps($UserID);
            if (count($UserStamps)) {  ?>
                <div id="userstamps">
                    <?php if ($OwnProfile) { ?>
                        <a style="float:right" href="bonus.php?action=manage_stamps">Reorder / Hide</a>
                    <?php } ?>
                    <h3>Stamp Collection</h3>
                    <?php foreach($UserStamps as $Stamp) echo Stamps::getStampImg($Stamp); ?>
                </div>
<?php            }
?>
                </div>
            </div>
<?php

if (check_perms('admin_login_watch',$Class)) {
    // get any failed login attempts
    $DB->query("SELECT
                   l.ID,
                   l.IP,
                   l.LastAttempt,
                   l.Attempts,
                   l.BannedUntil,
                   l.Bans
              FROM login_attempts AS l
             WHERE l.Attempts>0
               AND l.UserID = '$UserID'
          ORDER BY LastAttempt DESC ");

    if ($DB->record_count()>0) {

        $CookieItems[] = 'loginwatch';

?>
        <div class="head">
            <span style="float:left;">Login Watch</span>
            <span style="float:right;"><a id="loginwatchbutton" href="#" onclick="return Toggle_view('loginwatch');">(Hide)</a></span>&nbsp;
        </div>

        <div class="box">
            <table width="100%" id="loginwatchdiv" class="shadow">
                <tr class="colhead">
                    <td>IP</td>
                    <td>Attempts</td>
                    <td>Last Attempt</td>
                    <td>Bans</td>
                    <td>Remaining</td>
                    <td style="width:160px"></td>
                </tr>
<?php
            $Row = 'b';
            while (list($loginID, $loginIP, $LastAttempt, $Attempts, $BannedUntil, $Bans) = $DB->next_record()) {
                $Row = ($Row === 'a' ? 'b' : 'a');

?>
                <tr class="row<?=$Row?>">
                    <td>
                        <?=display_ip($loginIP)?>
                    </td>
                    <td>
                        <?=$Attempts?>
                    </td>
                    <td>
                        <?=time_diff($LastAttempt)?>
                    </td>
                    <td>
                        <?=$Bans?>
                    </td>
                    <td>
                        <?=time_diff($BannedUntil)?>
                    </td>
                    <td>
                        <form action="user.php?id=<?=$UserID?>" method="post" style="display:inline-block">
                            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                            <input type="hidden" name="loginid" value="<?=$loginID?>" />
                            <input type="hidden" name="action" value="reset_login_watch" />
                            <input type="hidden" name="id" value="<?=$UserID?>" />
                            <input type="submit" name="submit" title="remove any bans (and reset attempts) from login watch" value="Unban" />
                        </form>
<?php       if (check_perms('admin_manage_ipbans')) { ?>
                        <form action="tools.php" method="post" style="display:inline-block">
                            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                            <input type="hidden" name="id" value="<?=$loginID?>" />
                            <input type="hidden" name="action" value="ip_ban" />
                            <input type="hidden" name="start" value="<?=$loginIP?>" />
                            <input type="hidden" name="end" value="<?=$loginIP?>" />
                            <input type="hidden" name="notes" value="Banned per <?=$Bans?> bans on login watch." />
                            <input type="submit" name="submit" title="IP Ban this ip address (use carefully!)" value="IP Ban" />
                        </form>
<?php       } ?>
                    </td>
                </tr>
<?php
    }
?>
            </table>
        </div>

<?php
    }
}

if (check_perms('users_view_bonuslog',$Class) || $OwnProfile) {
        $CookieItems[] = 'bonus';
        $RealBonus = 0;
        // Calculate what BonusPoints should be from the log
        if ($BonusLog) {
            $BonusArr = explode(PHP_EOL, $BonusLog);
            function addCredit($Total, $BonusRow) {
                if (preg_match('/^[^\|]+\|\s((\+|\-)\d+\.?\d*)\s/', $BonusRow, $Match)) {
                    $Total += $Match[1];
                }
                return $Total;
            };
            $RealBonus = array_reduce($BonusArr, "addCredit");
        }
        // Load Slot Machine Data
        $UserResults = $Cache->get_value('sm_sum_history_'.$UserID);
        if ($UserResults === false) {
            $DB->query("SELECT Count(ID), SUM(Spins), SUM(Won),SUM(Bet*Spins),(SUM(Won)/SUM(Bet*Spins))
                      FROM sm_results WHERE UserID = $UserID");
            $UserResults = $DB->next_record();
            $Cache->cache_value('sm_sum_history_'.$UserID, $UserResults, 86400);
        }
        if (is_array($UserResults) && $UserResults[0] > 0) {
            list($Num, $NumSpins, $TotalWon, $TotalBet, $TotalReturn) = $UserResults;
            $RealBonus += $TotalWon - $TotalBet;
        }
?>
        <div class="head">
            <span style="float:left;">Bonus Cubits</span>
            <span style="float:right;"><a id="bonusbutton" href="#" onclick="return Toggle_view('bonus');">(Hide)</a></span>&nbsp;
        </div>
        <div class="box">
            <div class="pad" id="bonusdiv">
                <div class="center">
                    <h2>Cubits: <?=(!$BonusCredits ? '0.00' : number_format($BonusCredits,2))?></h2>
            <?php if ($RealBonus > ($BonusCredits+10)) { ?>
                <h3>Cylons have scattered your cubits in deep space!</h3>
                <div>Galactic records indicate your balance should really be <strong><?=number_format($RealBonus,2)?></strong> cubits.<br/>
                    <form action="user.php?id=<?=$UserID?>" method="post" style="display:inline-block">
                        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                        <input type="hidden" name="loginid" value="<?=$loginID?>" />
                        <input type="hidden" name="action" value="restore" />
                        <input type="hidden" name="userid" value="<?=$UserID?>" />
                        <input type="submit" name="submit" value="Recover Missing Cubits NOW!" style="margin-top:5px;"/>
                    </form>
                </div>                
            <?php } ?>
                </div>
                <span style="float:right;"><a href="#" onclick="$('#bonuslogdiv').toggle(); this.innerHTML=(this.innerHTML=='(Show Log)'?'(Hide Log)':'(Show Log)'); return false;">(Show Log)</a></span>&nbsp;

                <div class="hidden" id="bonuslogdiv" style="padding-top: 10px;">
                    <div id="bonuslog" class="box pad scrollbox">
                        <?=(!$BonusLog ? 'no bonus history' :$Text->full_format($BonusLog))?>
                    </div>
<?php
                    if (is_array($UserResults) && $UserResults[0] > 0) {
                        list($Num, $NumSpins, $TotalWon, $TotalBet, $TotalReturn) = $UserResults;
?>
                        <div class="box pad" title="<?="spins: $NumSpins ($Num) | -$TotalBet | +$TotalWon | return: $TotalReturn"?>">
                            <strong>Slot Machine:</strong> <?= ($TotalWon-$TotalBet)?> Cubits
                        </div>
<?php
                    }
?>
                    <div class="box pad">Total cubits from log: <?=number_format($RealBonus,2)?></div>
                </div>
           </div>
        </div>

<?php 
// Disabling the option to send Cubits.
// Cubits should be earned by seeding only.

/*<?php
}

if ($Enabled == '1' && !$OwnProfile) {
        $CookieItems[] = 'donate';
    include(SERVER_ROOT.'/sections/bonus/functions.php');
    $ShopItems = get_shop_items_other();

 ?>
        <div class="head">
            <span style="float:left;">Donate to user</span>
            <span style="float:right;"><a id="donatebutton" href="#" onclick="return Toggle_view('donate');">(Hide)</a></span>&nbsp;
        </div>
        <div class="box">
            <table width="100%" id="donatediv" class="shadow">
<?php

    foreach ($ShopItems as $BonusItem) {
            list($ItemID, $Title, $Description, $Action, $Value, $Cost) = $BonusItem;
            $CanBuy = is_float((float) $LoggedUser['TotalCredits']) ? $LoggedUser['TotalCredits'] >= $Cost: false;
            //echo $Title;
            if ($Action=='givegb') $Title = str_replace ('other', $Username, $Title);
            else $Title .= " to $Username";
            $Row = ($Row == 'a') ? 'b' : 'a';
?>
                <tr class="row<?=$Row?>">
                    <td title="<?=display_str($Description)?>"><strong><?=display_str($Title) ?></strong></td>
                    <td style="text-align: left;">(cost <?=number_format($Cost) ?>c)</td>
                    <td style="text-align: right;">
                    <form method="post" action="bonus.php" style="display:inline-block">
                        <input type="hidden" name="action" value="buy" />
                        <input type="hidden" name="othername" value="<?=$Username?>" />
                        <input type="hidden" name="userid" value="<?=$LoggedUser['ID']?>" />
                        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                        <input type="hidden" name="itemid" value="<?=$ItemID?>" />
                        <input type="hidden" name="retu" value="<?=$UserID?>" />
                        <input class="shopbutton<?=($CanBuy ? ' itembuy' : ' itemnotbuy')?>" name="submit" value="<?=($CanBuy?'Buy':'x')?>" type="submit"<?=($CanBuy ? '' : ' disabled="disabled"')?> />
                    </form>
                    </td>
                </tr>
<?php
    }
?>
            </table>
        </div>
<?php
}*/ ?>

<?php
}

if (check_force_anon($UserID) && $Snatched > 0 && check_paranoia_here('snatched')) {
    $CookieItems[] = 'snatches';
    $RecentSnatches = $Cache->get_value('recent_snatches_'.$UserID);
    if (!is_array($RecentSnatches)) {
        $DB->query("SELECT
        g.ID,
        g.Name,
        g.Image
        FROM xbt_snatched AS s
        INNER JOIN torrents AS t ON t.ID = s.fid
        INNER JOIN torrents_group AS g ON t.GroupID = g.ID
        WHERE s.uid = '$UserID'
        AND g.Image <> ''
        AND s.upload = '0'
        GROUP BY g.ID
        ORDER BY s.tstamp DESC
        LIMIT 5");
        $RecentSnatches = $DB->to_array();
        $Cache->cache_value('recent_snatches_'.$UserID, $RecentSnatches, 3600); // cache for 1 hour
    }
?>
            <div class="head">
                <span style="float:left;">Recent Snatches</span>
                <span style="float:right;"><a id="snatchesbutton" href="#" onclick="return Toggle_view('snatches');">(Hide)</a></span>&nbsp;
            </div>
    <table class="recent" cellpadding="0" cellspacing="0" border="0">
        <tr id="snatchesdiv">
<?php
        foreach ($RecentSnatches as $RS) { ?>
            <td>
                <a href="torrents.php?id=<?=$RS['ID']?>" title="<?=display_str($RS['Name'])?>"><img src="<?=$RS['Image']?>" alt="<?=display_str($RS['Name'])?>" width="107" /></a>
            </td>
<?php 		} ?>
        </tr>
    </table>
<?php
}

if (!isset($Uploads)) { $Uploads = 0; }
if (check_force_anon($UserID) && $Uploads > 0 && check_paranoia_here('uploads')) {
    $RecentUploads = $Cache->get_value('recent_uploads_'.$UserID);
    if (!is_array($RecentUploads)) {
        $DB->query("SELECT
        g.ID,
        g.Name,
        g.Image
        FROM torrents_group AS g
        INNER JOIN torrents AS t ON t.GroupID = g.ID
        WHERE t.UserID = '$UserID'
        AND t.Anonymous = '0'
        GROUP BY g.ID
        ORDER BY t.Time DESC
        LIMIT 5");
        $RecentUploads = $DB->to_array();
        $Cache->cache_value('recent_uploads_'.$UserID, $RecentUploads, 3600); // cache for 1 hour
    }
      if (count($RecentUploads)>0) {
        $CookieItems[] = 'recentuploads';
?>
    <div class="head">
        <span style="float:left;">Recent Uploads</span>
        <span style="float:right;"><a id="recentuploadsbutton" href="#" onclick="return Toggle_view('recentuploads');">(Hide)</a></span>&nbsp;
    </div>
    <div class="box">
    <table id="recentuploadsdiv" class="recent shadow" cellpadding="0" cellspacing="0" border="0">
        <tr>
<?php               foreach ($RecentUploads as $RU) { ?>
                    <td width="20%">
                        <div>
                <a href="torrents.php?id=<?=$RU['ID']?>" title="<?=$RU['Name']?>">
<?php                   if ($RU['Image']) {
?>                          <img src="<?=$RU['Image']?>" alt="<?=$RU['Name']?>" style="max-width: 120px"/>
<?php                   } else { ?>
                            <?=$RU['Name']?>
<?php                   } ?>
                        </a>
                        </div>
                    </td>
<?php               } ?>
        </tr>
    </table>
    </div>
<?php
      }
}

$DB->query("SELECT ID, Name FROM collages WHERE UserID='$UserID' AND CategoryID='0' AND Deleted='0' ORDER BY Featured DESC, Name ASC");
$Collages = $DB->to_array();
$FirstCol = true;
foreach ($Collages as $CollageInfo) {
    list($CollageID, $CName) = $CollageInfo;
    $DB->query("SELECT ct.GroupID,
        tg.Image,
        tg.NewCategoryID
        FROM collages_torrents AS ct
        JOIN torrents_group AS tg ON tg.ID=ct.GroupID
        WHERE ct.CollageID='$CollageID'
        ORDER BY ct.Sort LIMIT 5");
    $Collage = $DB->to_array();
?>
    <div class="head">
        <span style="float:left;"><?=display_str($CName)?> - <a href="collages.php?id=<?=$CollageID?>">see full</a></span>
        <span style="float:right;"><a href="#" onclick="$('#collage<?=$CollageID?>').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(View)':'(Hide)'); return false;"><?=$FirstCol?'(Hide)':'(View)'?></a></span>&nbsp;
    </div>
    <table class="recent" cellpadding="0" cellspacing="0" border="0">
        <tr id="collage<?=$CollageID?>" <?=$FirstCol?'':'class="hidden"'?>>
<?php 	foreach ($Collage as $C) {
            $Group = get_groups(array($C['GroupID']));
            $Group = array_pop($Group['matches']);
            list($GroupID, $GroupName, $TagList, $Torrents) = array_values($Group);

            $Name = $GroupName;
?>
            <td>
                <a href="torrents.php?id=<?=$GroupID?>" title="<?=$Name?>"><img src="<?=$C['Image']?>" alt="<?=$Name?>" width="107" /></a>
            </td>
<?php 	} ?>
        </tr>
    </table>
<?php
    $FirstCol = false;
}

// Linked accounts
if (check_perms('users_mod', $Class)) {
        $CookieItems[] = 'linked';
        $CookieItems[] = 'iplinked';
        $CookieItems[] = 'elinked';
    include(SERVER_ROOT.'/sections/user/linkedfunctions.php');
    user_dupes_table($UserID, $Username);
}

if ((check_perms('users_view_invites')) && $Invited > 0) {
        $CookieItems[] = 'invite';
    $Tree = new INVITE_TREE($UserID, array('visible'=>false));
?>
            <div class="head">
                <span style="float:left;">Invite Tree</span>
                <span style="float:right;"><a id="invitebutton" href="#" onclick="return Toggle_view('invite');">(Hide)</a></span>&nbsp;
        </div>
        <div class="box">
                <div id="invitediv" class="">
                <?php  $Tree->make_tree(); ?>
                </div>
        </div>
<?php
}

// Requests
if (check_force_anon($UserID) && check_paranoia_here('requestsvoted_list')) {
        $CookieItems[] = 'requests';
    $DB->query("SELECT
            r.ID,
            r.CategoryID,
            r.Title,
            r.TimeAdded,
            COUNT(rv.UserID) AS Votes,
            SUM(rv.Bounty) AS Bounty
        FROM requests AS r
            LEFT JOIN users_main AS u ON u.ID=UserID
            LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
        WHERE r.UserID = ".$UserID."
            AND r.TorrentID = 0
        GROUP BY r.ID
        ORDER BY Votes DESC");
    $NumRequests =  $DB->record_count() ;
    if ($NumRequests > 0) {
        $Requests = $DB->to_array();
?>
            <div class="head">
                    <span style="float:left;"><?=$NumRequests?> Request<?=(($NumRequests == 1)?'':'s')?></span>
                    <span style="float:right;"><a id="requestsbutton" href="#" onclick="return Toggle_view('requests');">(Hide)</a></span>&nbsp;
            </div>
            <div class="box">
            <div id="requestsdiv" class="">
                <table cellpadding="6" cellspacing="1" border="0" class="shadow" width="100%">
                    <tr class="colhead">
                        <td style="width:48%;"><strong>Request Name</strong></td>
                        <td><strong>Votes</strong></td>
                        <td><strong>Bounty</strong></td>
                        <td><strong>Added</strong></td>
                    </tr>
<?php
        foreach ($Requests as $Request) {
            list($RequestID, $CategoryID, $Title, $TimeAdded, $Votes, $Bounty) = $Request;

            $Request = get_requests(array($RequestID));
            $Request = $Request['matches'][$RequestID];
            if (empty($Request)) {
                continue;
            }

            list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Image, $Description,
            $FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;

                        $FullName ="<a href='requests.php?action=view&amp;id=".$RequestID."'>".$Title."</a>";

            $Row = (empty($Row) || $Row == 'a') ? 'b' : 'a';
?>
                    <tr class="row<?=$Row?>">
                        <td>
                            <?=$FullName?>
<?php               if ($LoggedUser['HideTagsInLists'] !== 1) { ?>
                            <div class="tags">
<?php
                    $Tags = $Request['Tags'];
                    $TagList = array();
                    foreach ($Tags as $TagID => $TagName) {
                        $TagList[] = "<a href='requests.php?tags=".$TagName."'>".display_str($TagName)."</a>";
                    }
                    $TagList = implode(' ', $TagList);

                    echo $TagList;
?>
                            </div>
<?php               } ?>
                        </td>
                        <td>
                            <span id="vote_count_<?=$RequestID?>"><?=$Votes?></span>
<?php 		  	if (check_perms('site_vote')) { ?>
                            <input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                            &nbsp;&nbsp; <a href="javascript:VotePromptMB(<?=$RequestID?>)"><strong>(+)</strong></a>
<?php 			} ?>
                        </td>
                        <td>
                            <span id="bounty_<?=$RequestID?>"><?=intval($Bounty)?></span>
                        </td>
                        <td>
                            <?=time_diff($TimeAdded)?>
                        </td>
                    </tr>
<?php 		} ?>
                </table>
            </div>
        </div>
<?php
    }
}

include_once(SERVER_ROOT.'/sections/staff/functions.php');
$FLS = get_fls();
$IsFLS = false;
foreach ($FLS as $F) {
    if ($LoggedUser['ID'] == $F['ID']) {
        $IsFLS = true;
        break;
    }
}
if (check_perms('users_mod') || $IsFLS) {
    $UserLevel = $LoggedUser['Class'];
    $DB->query("SELECT
                    SQL_CALC_FOUND_ROWS
                    ID,
                    Subject,
                    Status,
                    Level,
                    AssignedToUser,
                    Date,
                    ResolverID
                FROM staff_pm_conversations
                WHERE UserID = $UserID AND (Level <= $UserLevel OR AssignedToUser='".$LoggedUser['ID']."')
                ORDER BY Date DESC");
    $NumStaffPMs = $DB->record_count();
    if ($NumStaffPMs) {
        $CookieItems[] = 'staffpms';
        $StaffPMs = $DB->to_array();
?>
                <div class="head">
                        <span style="float:left;"><?=$NumStaffPMs?> Staff PM<?=(($NumStaffPMs == 1)?'':'s')?></span>
                        <span style="float:right;"><a id="staffpmsbutton" href="#" onclick="return Toggle_view('staffpms');">(Hide)</a></span>&nbsp;
                </div>
                <div class="box">
                    <table width="100%" class="shadow" id="staffpmsdiv">
                <tr class="colhead">
                    <td>Subject</td>
                    <td>Date</td>
                    <td>Assigned To</td>
                    <td>Resolved By</td>
                </tr>
<?php 		foreach ($StaffPMs as $StaffPM) {
            list($ID, $Subject, $Status, $Level, $AssignedTo, $Date, $ResolverID) = $StaffPM;
            // Get assigned
            if ($AssignedToUser == '') {
                // Assigned to class
                $Assigned = ($Level == 0) ? "First Line Support" : $ClassLevels[$Level]['Name'];
                // No + on Sysops
                if ($Assigned != 'Sysop') { $Assigned .= "+"; }

            } else {
                // Assigned to user
                $UserInfo = user_info($AssignedToUser);
                $Assigned = format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);
            }

            if ($ResolverID) {
                $UserInfo = user_info($ResolverID);
                $Resolver = format_username($ResolverID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);
            } else {
                $Resolver = "(unresolved)";
            }

            $Row = ($Row == 'a') ? 'b' : 'a';
?>
                <tr class="row<?=$Row?>">
                    <td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
                    <td><?=time_diff($Date, 2, true)?></td>
                    <td><?=$Assigned?></td>
                    <td><?=$Resolver?></td>
                </tr>
<?php 		} ?>
            </table>
        </div>
<?php 	}
}

if (check_perms('admin_reports') || $IsFLS) {
    $DB->query("SELECT
                    SQL_CALC_FOUND_ROWS
                    r.ID,
                    r.ReporterID,
                    r.TorrentID,
                    tg.Name,
                    r.Type,
                    r.UserComment,
                    r.Status,
                    r.ReportedTime,
                    r.LastChangeTime,
                    r.ModComment,
                    r.ResolverID
                FROM reportsv2 as r
           LEFT JOIN torrents_group as tg ON tg.ID=r.TorrentID
               WHERE ReporterID = $UserID
            ORDER BY ReportedTime DESC");
    $NumReports = $DB->record_count();
    if ($NumReports) {
        $CookieItems[] = 'reports';
        $Reports = $DB->to_array();
?>
                <div class="head">
                        <span style="float:left;"><?=$NumReports?> Report<?=(($NumReports == 1)?'':'s')?></span>
                        <span style="float:right;"><a id="reportsbutton" href="#" onclick="return Toggle_view('reports');">(Hide)</a></span>&nbsp;
                </div>
                <div class="box">
                    <table width="100%" class="shadow" id="reportsdiv">
                <tr class="colhead">
                    <td title="Report ID">ID</td>
                    <td width="80px">Torrent</td>
                    <td>Type</td>
                    <td>User Comment</td>
                    <td width="80px">Date</td>
                    <td>Resolved By</td>
                    <td width="100px">Mod Comment</td>
                </tr>
<?php 		foreach ($Reports as $Report) {
            list($ID, $ReporterID, $TorrentID, $Name, $Type, $UserComment, $Status,
                    $ReportedTime, $LastChangeTime, $ModComment, $ResolverID) = $Report;

            if ($ResolverID) {
                $UserInfo = user_info($ResolverID);
                $Resolver = format_username($ResolverID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);
            } else {
                $Resolver = "(unresolved)";
            }

            if ($Name) {
                $Torrent = '<a href="torrents.php?id='.$TorrentID.'">'.cut_string( display_str($Name), 30, 1).'</a>';
            } else {
                $Torrent = '<a href="log.php?search=Torrent+'.$TorrentID.'">'.display_str($TorrentID).' (deleted)</a>';
            }

            $Row = ($Row == 'a') ? 'b' : 'a';
?>
                <tr class="row<?=$Row?>">
                    <td><a href="reportsv2.php?view=report&id=<?=$ID?>">#<?=display_str($ID)?></a></td>
                    <td><?=$Torrent?></td>
                    <td><?=$Type?></td>
                    <td><?=$Text->full_format(cut_string($UserComment,120))?></td>
                    <td><?=time_diff($ReportedTime, 2, true)?></td>
                    <td><?=$Resolver?></td>
                    <td><?=$Text->full_format(cut_string($ModComment,120))?></td>
                </tr>
<?php 		} ?>
            </table>
        </div>
<?php 	}
}

if (check_perms('users_mod', $Class)) {
        $CookieItems[] = 'notes';
        $CookieItems[] = 'history';
        $CookieItems[] = 'info';
        $CookieItems[] = 'privilege';
        $CookieItems[] = 'submit'; ?>
        <form id="form" action="user.php" method="post">
        <input type="hidden" name="action" value="moderate" />
        <input type="hidden" name="userid" value="<?=$UserID?>" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />

            <div class="head">
                <span style="float:left;">Staff Notes</span>
                <span style="float:right;"><a id="notesbutton" href="#" onclick="return Toggle_view('notes');">(Hide)</a></span>&nbsp;
            </div>
        <div class="box" >
                  <div class="pad" id="notesdiv" style="padding-bottom: 20px;">
                <input type="hidden" name="comment_hash" value="<?=$CommentHash?>">
                <div id="admincommentlinks" class="AdminComment box pad scrollbox"><?=$Text->full_format($AdminComment)?></div>
                <textarea id="admincomment" onkeyup="resize('admincomment');" class="AdminComment hidden" name="AdminComment" cols="65" rows="26" style="width:98%;"><?=display_str($AdminComment)?></textarea>
<?php
        if (check_perms('users_admin_notes', $Class)) { ?>
                        <span style="float:right;">
                            <a href="#" name="admincommentbutton" onclick="ChangeTo('text'); return false;">(Edit Notes)</a>
                        </span>
<?php       } ?>
            </div>
        </div>

                <script type="text/javascript">
                    resize('admincomment');
                </script>
            <div class="head">
                <span style="float:left;">Tracker History</span>
                <span style="float:right;"><a id="historybutton" href="#" onclick="return Toggle_view('history');">(Hide)</a></span>&nbsp;
            </div>
        <div class="box">
            <div class="pad" id="historydiv">
                      This is a record of up/down from the tracker, plus cubits awarded
<?php

                echo '<div class="box pad seedhistory scrollbox">';
                echo " Total &nbsp;&nbsp; | ". hoursdays($SeedHoursTotal) .'<br/>';
                echo " Today &nbsp;&nbsp; | ". hoursdays($SeedHoursDaily) . " | $CreditsDaily credits";
                echo '</div>';
?>
                <div class="box pad seedhistory scrollbox"><?=$Text->full_format($SeedHistory)?></div>

            </div>
        </div>

            <div class="head">
                <span style="float:left;">User Moderation</span>
                <span style="float:right;"><a id="infobutton" href="#" onclick="Toggle_view('info');return false;">(Hide)</a></span>&nbsp;
            </div>
        <div class="box">
                <table id="infodiv" class="shadow">
<?php 	if (check_perms('users_edit_usernames', $Class)) {  ?>
            <tr>
                <td class="label">Username:</td>
                <td><input type="text" size="40" name="Username" maxlength="20"  pattern="[A-Za-z0-9_\-\.]{1,20}" value="<?=display_str($Username)?>" /></td>
            </tr>
<?php
    }
    if (check_perms('users_edit_titles')) {
?>
            <tr>
                <td class="label">CustomTitle:<br/>(max 32)</td>
                <td><input class="long" type="text" name="Title" maxlength="32" value="<?=display_str($CustomTitle)?>" /></td>
            </tr>
<?php
    }

    if (check_perms('users_promote_below', $Class) || check_perms('users_promote_to', $Class-1)) {
?>
            <tr>
                <td class="label">Class:</td>
                <td>
                    <select name="Class">
<?php
        foreach ($ClassLevels as $CurClass) {
            if ($CurClass['IsUserClass']!='1') continue;
            if (check_perms('users_promote_below', $Class) && $CurClass['ID']>=$LoggedUser['Class']) { break; }
            if ($CurClass['ID']>$LoggedUser['Class']) { break; }
            if ($Class===$CurClass['Level']) { $Selected='selected="selected"'; } else { $Selected=""; }
?>
                        <option value="<?=$CurClass['ID']?>" <?=$Selected?>><?=$CurClass['Name'].' ('.$CurClass['Level'].')'?></option>
<?php 		} ?>
                    </select>
                </td>
            </tr>
<?php
    }

      if (check_perms('admin_manage_permissions', $Class) || check_perms('user_group_permissions', $Class) ) {

            $GroupPerms = $Cache->get_value('group_permissions');
            if (!$GroupPerms) {
                $DB->query("SELECT ID, Name FROM permissions WHERE IsUserClass='0' ORDER BY ID");
                $GroupPerms = $DB->to_array('ID');
                $Cache->cache_value('group_permissions', $GroupPerms, 0);
            }
?>
            <tr>
                <td class="label">Group Permissions:</td>
                <td>
                    <select name="GroupPermission">
                        <option value="0" <?php if($GroupPermID===0)echo'selected="selected"';?>> -none- &nbsp;</option>
<?php
        foreach ($GroupPerms as $GPerm) {
            if ($GroupPermID===$GPerm['ID']) { $Selected='selected="selected"'; } else { $Selected=""; }
?>
                        <option value="<?=$GPerm['ID']?>" <?=$Selected?>><?=$GPerm['Name']?>&nbsp;</option>
<?php 		} ?>
                    </select>
                </td>
            </tr>
<?php
      }

    if (check_perms('users_give_donor')) {
?>
            <tr>
                <td class="label">Donor:</td>
                <td><input type="checkbox" name="Donor" <?php  if ($Donor == 1) { ?>checked="checked" <?php  } ?> /></td>
            </tr>
<?php
    }
    if (check_perms('users_make_invisible')) {
?>
            <tr>
                <td class="label">Visible:</td>
                <td><input type="checkbox" name="Visible" <?php  if ($Visible == 1) { ?>checked="checked" <?php  } ?> /></td>
            </tr>
<?php
    }

    if ((check_perms('users_edit_ratio',$Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_ratio') && $UserID == $LoggedUser['ID'])) {
?>
            <tr>
                <td class="label">Adjust Upload:</td>
                <td>
                    <input type="hidden" name="OldUploaded" value="<?=$Uploaded?>" />
                              <input type="text" size="10" name="adjustupvalue" id="adjustupvalue" value="" onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" title="Use '-' to remove from Upload" /> &nbsp;&nbsp;
                              <input name="adjustup" value="mb" type="radio"  onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" /> MB&nbsp;&nbsp;
                              <input name="adjustup" value="gb" type="radio" onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" checked="checked" /> GB&nbsp;&nbsp;
                              <input name="adjustup" value="tb" type="radio" onchange="CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>)" /> TB

                    <span style="margin-left:40px;" title="Current Upload"><?=get_size($Uploaded, 2)?></span>
                              <span style="margin-left: 10px;" id="adjustupresult" name="adjustupresult" title="Preview of Total Upload after adjustment"></span>
                </td>
            </tr>
            <tr>
                <td class="label">Adjust Download:</td>
                <td>
                    <input type="hidden" name="OldDownloaded" value="<?=$Downloaded?>" />

                              <input type="text" size="10" name="adjustdownvalue" id="adjustdownvalue" value="" onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" title="Use '-' to remove from Download" /> &nbsp;&nbsp;
                              <input name="adjustdown" value="mb" type="radio"  onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" /> MB&nbsp;&nbsp;
                              <input name="adjustdown" value="gb" type="radio" onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" checked="checked" /> GB&nbsp;&nbsp;
                              <input name="adjustdown" value="tb" type="radio" onchange="CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>)" /> TB

                              <span style="margin-left: 40px;" title="Current Download"><?=get_size($Downloaded, 2)?></span>
                      <span style="margin-left: 10px;" id="adjustdownresult" name="adjustdownresult" title="Preview of Total Download after adjustment"></span>
                </td>
            </tr>
                    <script type="text/javascript">
                        CalculateAdjustUpload('adjustdown', document.forms['form'].elements['adjustdown'],<?=$Downloaded?>);
                        CalculateAdjustUpload('adjustup', document.forms['form'].elements['adjustup'],<?=$Uploaded?>);
                    </script>
            <tr>
                <td class="label">Merge Stats <strong>From:</strong></td>
                <td>
                    <input class="long" type="text" name="MergeStatsFrom" />
                </td>
            </tr>
<?php
    }

    if ((check_perms('users_edit_tokens',$Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_tokens') && $UserID == $LoggedUser['ID'])) {
?>
            <tr>
                <td class="label">Slots:</td>
                <td>
                    <input type="text" size="10" name="FLTokens" value="<?=$FLTokens?>" />
                </td>
            </tr>
<?php
    }

    if ((check_perms('users_edit_credits',$Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_credits') && $UserID == $LoggedUser['ID'])) {
?>
            <tr>
                <td class="label">Bonus Cubits</td>
                <td>
                    <input type="text" size="10" name="BonusCredits" value="<?=$BonusCredits?>" />
                </td>
            </tr>
<?php
    }

    if (check_perms('users_edit_invites')) {
?>
            <tr>
                <td class="label">Invites:</td>
                <td><input type="text" size="10" name="Invites" value="<?=$Invites?>" /></td>
            </tr>
<?php       }

    if (check_perms('users_set_suppressconncheck')) {
?>
            <tr>
                <td class="label">Suppress ConnCheck prompt:</td>
                <td><input type="checkbox" name="ConnCheck" <?php  if ($SuppressConnPrompt == 1) { ?>checked="checked" <?php  } ?> />
                    &nbsp;if checked then this user will never see a prompt to check their connectable status in the header bar
                </td>
            </tr>
<?php
    }

        if ((check_perms('users_edit_pfl',$Class) && $UserID != $LoggedUser['ID'])
        || (check_perms('users_edit_own_pfl') && $UserID == $LoggedUser['ID'])) {
?>
                        <tr>
                                <td class="label">Personal Freeleech</td>
                                <td>
                                    <select name="PersonalFreeLeech">
                                        <option value="0" selected="<?=$PersonalFreeLeech < sqltime()?'seleced':''?>">None</option>
                                        <option value="24">24 hours</option>
                                        <option value="48">48 hours</option>
                                        <option value="168">1 week</option>
                                        <option value="730">1 month</option>
                                        <option value="2190">3 months</option>
                                        <option value="4380">6 months</option>
                                        <option value="8760">1 year</option>
                                        <option value="87648">10 years</option>
                                    <?php  if ($PersonalFreeLeech > sqltime()) { ?>
                                        <option value="1" selected="selected"><?=time_diff($PersonalFreeLeech, 2, false,false,0)?> (current)</option>
                                    <?php  } ?>
                                    </select>
                                </td>
<?php
    }

        if (check_perms('admin_manage_fls') || (check_perms('users_mod') && $OwnProfile)) {
?>
            <tr>
                <td class="label">First Line Support:</td>
                <td><input class="long" type="text" name="SupportFor" value="<?=display_str($SupportFor)?>" /></td>
            </tr>
<?php
    }

    if (check_perms('users_edit_reset_keys')) {
?>
            <tr>
                <td class="label">Reset:</td>
                <td>
                    <!--input type="checkbox" name="ResetRatioWatch" id="ResetRatioWatch" /> <label for="ResetRatioWatch">Ratio Watch</label> |-->
                    <input type="checkbox" name="ResetHnRWatch" id="ResetHnRWatch" /> <label for="ResetHnRWatch">HnR Watch</label> |
                    <input type="checkbox" name="ResetPasskey" id="ResetPasskey" /> <label for="ResetPasskey">Passkey</label> |
                    <input type="checkbox" name="ResetAuthkey" id="ResetAuthkey" /> <label for="ResetAuthkey">Authkey</label> |
                    <input type="checkbox" name="ResetIPHistory" id="ResetIPHistory" /> <label for="ResetIPHistory">IP History</label> |
                    <input type="checkbox" name="ResetEmailHistory" id="ResetEmailHistory" /> <label for="ResetEmailHistory">Email History</label>
                    <br />
                    <input type="checkbox" name="ResetSnatchList" id="ResetSnatchList" /> <label for="ResetSnatchList">Snatch List</label> |
                    <input type="checkbox" name="ResetDownloadList" id="ResetDownloadList" /> <label for="ResetDownloadList">Download List</label>
                    <input type="checkbox" name="ResetHnRs" id="ResetHnRs" /> <label for="ResetHnRs">HnRs</label>
                </td>
            </tr>
<?php
    }

    if (check_perms('users_edit_password')) {
?>
            <tr>
                <td class="label">New Password:</td>
                <td>
                    <input class="long" type="text" id="change_password" name="ChangePassword" />
                </td>
            </tr>
            <tr>
                <td class="label">(repeat) New Password:</td>
                <td>
                    <input class="long" type="text" id="change_password2" name="ChangePassword2" />
                </td>
            </tr>
            <tr>
                <td class="label">2FA Status:</td>
                <td>
                    <?php if (empty($TwoFactor)) { echo 'Not enrolled'; } else { ?>
                    <input class="long" type="text" id="2fa" name="2fa" placeholder="Enrolled. Enter REMOVE here to disable."/>
                    <?php } ?>
                </td>
            </tr>            
<?php 	}

    if (check_perms('users_edit_email')) {
?>
            <tr>
                <td class="label">New E-Mail: </td>
                <td><strong>note:</strong> users can change their own email - using this could be allowing someone to steal the account!
                    <input class="long" type="text" id="change_email" name="ChangeEmail" />
                </td>
            </tr>
<?php 	} ?>
                </table>
        </div>

<?php
    if ((check_perms('users_edit_badges', $Class) && $UserID != $LoggedUser['ID'])
              || (check_perms('users_edit_own_badges') && $UserID == $LoggedUser['ID'])) {

        $CookieItems[] = 'badgesadmin';  ?>

        <div class="head">
            <span style="float:left;">User Badges</span>
            <span style="float:right;"><a id="badgesadminbutton" href="#" onclick="return Toggle_view('badgesadmin');">(Hide)</a></span>&nbsp;
        </div>
        <div class="box">
            <div class="pad" id="badgesadmindiv">
<?php
                $UserBadgesIDs = array(); // used in a mo to determine what badges user has for admin
                if ($UserBadges) {
?>
                      <div class="pad"><h3>Current user badges (select to remove)</h3>
<?php
                            foreach ($UserBadges as $UBadge) {
                                list($ID, $BadgeID, $Tooltip, $Name, $Image, $Auto, $Type ) = $UBadge;
                                $UserBadgesIDs[] = $BadgeID;
?>
                            <div class="badge">
                                <?='<img src="'.STATIC_SERVER.'common/badges/'.$Image.'" title="The '.$Name.'. '.$Tooltip.'" alt="'.$Name.'" />'?>
                                <br />
                                <input type="checkbox" name="delbadge[]" value="<?=$ID?>" />
                                        <label for="delbadge[]"> <?=$Name.'<br/>';
                                                if($Type=='Unique') echo " *(unique)";
                                                elseif ($Auto) echo " (automatically awarded)";
                                                else echo " ($Type)";  ?></label>
                            </div>
<?php
                            }
?>
                      </div><hr />
<?php
                      }
?>
                      <div class="pad addbadges"><h3>Add user badges (select to add)</h3>
                          <p>Black Market and single type items can be owned once by each user, multiple type items many times, and unique items only by one user at once</p>
                          <table class="noborder">
<?php
                        $DB->query("SELECT
                                    b.ID As Bid,
                                    b.Badge,
                                    b.Rank,
                                    b.Type,
                                    b.Title,
                                    b.Description,
                                    b.Image,
                                    IF(b.Type != 'Unique', TRUE,
                                                        (SELECT COUNT(*) FROM users_badges
                                                            WHERE users_badges.BadgeID=b.ID)=0) AS Available,
                                (SELECT Max(b2.Rank)
                                        FROM users_badges AS ub2
                                   LEFT JOIN badges AS b2 ON b2.ID=ub2.BadgeID
                                       WHERE b2.Badge = b.Badge
                                         AND ub2.UserID = $UserID) As MaxRank

                               FROM badges AS b
                               LEFT JOIN badges_auto AS ba ON b.ID=ba.BadgeID
                               WHERE ba.ID IS NULL
                               ORDER BY b.Sort");

                        $AvailableBadges = $DB->to_array();

                    foreach ($AvailableBadges as $ABadge) { // = $DB->next_record()
                        list($BadgeID, $Badge, $Rank, $Type, $Name, $Tooltip, $Image, $Available, $MaxRank) = $ABadge;

                        if (!in_array($Type, array('Single','Shop','Donor')) || !$MaxRank || $MaxRank < $Rank ) {
                        ?>
                        <tr>
                            <td width="60px">
                            <div class="badge">
<?php
                                echo '<img src="'.STATIC_SERVER.'common/badges/'.$Image.'" title="The '.$Name.'. '.$Tooltip.'" alt="'.$Name.'" />';

                                if (!$Available ||
                                     ($Type != 'Multiple' && in_array($BadgeID, $UserBadgesIDs) )  )
                                            $Disabled =' disabled="disabled" title="award is unavailable"';
                                else $Disabled='';
?>
                            </div>
                            </td>
                            <td>
                                <input  type="checkbox" name="addbadge[]" value="<?=$BadgeID?>"<?=$Disabled?> />
                                        <label for="addbadge[]"> <?=$Name;
                                                if($Type=='Unique') echo " *(unique)";
                                                else echo " ($Type)";?></label>
                                <br />
                                <input class="long" type="text" id="addbadge<?=$BadgeID?>" name="addbadge<?=$BadgeID?>"<?=$Disabled?> value="<?=$Tooltip?>" />
                            </td>
                        </tr>
<?php                       }
                    }
?>                      </table>
                      </div>
            </div>
        </div>
<?php 	}

    if (check_perms('users_warn')) {
        $CookieItems[] = 'warn';  ?>
        <div class="head">
                        <span style="float:left;">Warn User</span>
                        <span style="float:right;"><a id="warnbutton" href="#" onclick="return Toggle_view('warn');">(Hide)</a></span>&nbsp;
            </div>
            <div class="box">
                <table id="warndiv" class="shadow">
            <tr>
                <td class="label">Warned:</td>
                <td>
                    <input type="checkbox" name="Warned" <?php  if ($Warned != '0000-00-00 00:00:00') { ?>checked="checked"<?php  } ?> />
                </td>
            </tr>
<?php 		if ($Warned=='0000-00-00 00:00:00') { // user is not warned ?>
            <tr>
                <td class="label">Expiration:</td>
                <td>
                    <select name="WarnLength">
                        <option value="">---</option>
                        <option value="1"> 1 Week</option>
                        <option value="2"> 2 Weeks</option>
                        <option value="4"> 4 Weeks</option>
                        <option value="8"> 8 Weeks</option>
                    </select>
                </td>
            </tr>
<?php 		} else { // user is warned ?>
            <tr>
                <td class="label">Extension:</td>
                <td>
                    <select name="ExtendWarning">
                        <option>---</option>
                        <option value="1"> 1 Week</option>
                        <option value="2"> 2 Weeks</option>
                        <option value="4"> 4 Weeks</option>
                        <option value="8"> 8 Weeks</option>
                    </select>
                </td>
            </tr>
<?php 		} ?>
            <tr>
                <td class="label">Reason:</td>
                <td>
                    <input class="long" type="text" name="WarnReason" />
                </td>
            </tr>
<?php 	} ?>
                </table>
            </div>

            <div class="head">
                        <span style="float:left;">User Privileges</span>
                        <span style="float:right;"><a id="privilegebutton" href="#" onclick="return Toggle_view('privilege');">(Hide)</a></span>&nbsp;
            </div>
            <div class="box">
                <table id="privilegediv" class="shadow">
<?php 	if (check_perms('users_disable_posts') || check_perms('users_disable_any')) {
        $DB->query("SELECT DISTINCT Email, IP FROM users_history_emails WHERE UserID = ".$UserID." ORDER BY Time ASC");
        $Emails = $DB->to_array();
?>
            <tr>
                <td class="label">Disable:</td>
                <td>
                    <input type="checkbox" title="Disable users ability to post in threads and all comments (torrent, collage, requests)" name="DisablePosting" id="DisablePosting" <?php  if ($DisablePosting==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable users ability to post in threads and all comments (torrent, collage, requests)" for="DisablePosting">Posting</label>
<?php 		if (check_perms('users_disable_any')) { ?>  |
                    <input type="checkbox" title="Disable user avatar" name="DisableAvatar" id="DisableAvatar" <?php  if ($DisableAvatar==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user avatar" for="DisableAvatar">Avatar</label> |
                    <input type="checkbox" title="Disable user invites" name="DisableInvites" id="DisableInvites" <?php  if ($DisableInvites==1) { ?>checked="checked"<?php  } ?> /> <label  title="Disable user invites" for="DisableInvites">Invites</label> |

                    <input type="checkbox" title="Disable user from being able to access the forums (no read permission)" name="DisableForums" id="DisableForums" <?php  if ($DisableForums==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user from being able to access the forums (no read permission)" for="DisableForums">Forums</label> |
                    <input type="checkbox" title="Disable user from being able to add tags" name="DisableTagging" id="DisableTagging" <?php  if ($DisableTagging==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user from being able to add tags" for="DisableTagging">Tagging</label> |
                    <input type="checkbox" title="Disable user from being able to access the requests section" name="DisableRequests" id="DisableRequests" <?php  if ($DisableRequests==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user from being able to access the requests section" for="DisableRequests">Requests</label>
                    <br />
                    <input type="checkbox" title="Disable user ability to upload torrents" name="DisableUpload" id="DisableUpload" <?php  if ($DisableUpload==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user ability to upload torrents" for="DisableUpload">Upload</label> |
                    <input type="checkbox" title="Disable user ability to leech on the tracker" name="DisableLeech" id="DisableLeech" <?php  if ($DisableLeech==0) { ?>checked="checked"<?php  } ?> /> <label title="Disable user ability to leech on the tracker" for="DisableLeech">Leech</label> |
                    <input type="checkbox" title="Disable user ability to send private messages" name="DisablePM" id="DisablePM" <?php  if ($DisablePM==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user ability to send private messages" for="DisablePM">PM</label> |

                    <input type="checkbox" title="Disable user ability to change their signature" name="DisableSignature" id="DisableSignature" <?php  if ($DisableSig==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user ability to change their signature" for="DisableSignature">Signature</label> |
                    <input type="checkbox" title="Disable user ability to change their torrent signature" name="DisableTorrentSig" id="DisableTorrentSig" <?php  if ($DisableTorrentSig==1) { ?>checked="checked"<?php  } ?> /> <label title="Disable user ability to change their torrent signature" for="DisableTorrentSig">Torrent Signature</label>

                </td>
            </tr>
            <tr>
                <td class="label">Hacked:</td>
                <td>
                    <input type="checkbox" name="SendHackedMail" id="SendHackedMail" /> <label for="SendHackedMail">Send hacked account email</label> to
                    <select name="HackedEmail">
<?php
            foreach ($Emails as $Email) {
                list($Address, $IP) = $Email;
?>
                        <option value="<?=display_str($Address)?>"><?=display_str($Address)?> - <?=display_str($IP)?></option>
<?php 			} ?>
                    </select> (disables the account)
                </td>
            </tr>

<?php 		}
    }

    if ($Enabled == '0' && check_perms('users_mod')) {
        if (!is_array($Emails)) {
            $DB->query("SELECT DISTINCT Email, IP FROM users_history_emails WHERE UserID = ".$UserID." ORDER BY Time ASC");
            $Emails = $DB->to_array();
        }
?>
            <tr>
                <td class="label">Confirm Account:</td>
                <td>
                    <input type="checkbox" name="SendConfirmMail" id="SendConfirmMail" /> <label for="SendConfirmMail">Resend confirmation email</label> to
                    <select name="ConfirmEmail">
<?php
            foreach ($Emails as $Email) {
                list($Address, $IP) = $Email;
?>
                        <option value="<?=display_str($Address)?>"><?=display_str($Address)?> - <?=display_str($IP)?></option>
<?php 			} ?>
                    </select>
                </td>
            </tr>
<?php
    }

    if (check_perms('users_disable_any')) {
            $Reasons = array(0=>'Unknown',1=>'Manual',2=>'Ratio',3=>'Inactive',4=>'Cheating' );
?>
            <tr>
                <td class="label">Account:</td>
                <td>
                    <select name="UserStatus">
                        <option value="0" <?php  if ($Enabled=='0') { ?>selected="selected"<?php  } ?>>Unconfirmed</option>
                        <option value="1" <?php  if ($Enabled=='1') { ?>selected="selected"<?php  } ?>>Enabled</option>
                        <option value="2" <?php  if ($Enabled=='2') { ?>selected="selected"<?php  } ?>>Disabled</option>
<?php 		if (check_perms('users_delete_users')) { ?>
                        <optgroup label="-- WARNING --"></optgroup>
                        <option value="delete">Delete Account</option>
<?php 		} ?>
                    </select>
                    &nbsp;&nbsp;
                    <label for="ban_reason" title="When disabling a user this will be recorded as the ban reason">Ban Reason (when disabling) </label>&nbsp;
                    <select name="ban_reason" title="When disabling a user this will be recorded as the ban reason">
<?php                       foreach ($Reasons as $Key=>$Reason) {   ?>
                            <option value="<?=$Key?>" <?=($Key==$BanReason?' selected="selected"':'');?>>&nbsp;<?=$Reason;?> &nbsp;</option>
<?php                       } ?>
                    </select>

                </td>
            </tr>
            <tr>
                <td class="label">User Reason:</td>
                <td>
                    <input class="long" type="text" name="UserReason" />
                </td>
            </tr>
            <tr>
                <td class="label">Restricted Forum ID's (comma-delimited):</td>
                <td>
                            <input class="long" type="text" name="RestrictedForums" value="<?=display_str($RestrictedForums)?>" />
                </td>
            </tr>
            <tr>
                <td class="label">Extra Forum ID's (comma-delimited):</td>
                <td>
                            <input class="long" type="text" name="PermittedForums" value="<?=display_str($PermittedForums)?>" />
                </td>
            </tr>

<?php 	} ?>
                </table>
            </div>

<?php 	if (check_perms('users_logout')) {
        $CookieItems[] = 'session';  ?>
            <div class="head">
                        <span style="float:left;">Session</span>
                        <span style="float:right;"><a id="sessionbutton" href="#" onclick="return Toggle_view('session');">(Hide)</a></span>&nbsp;
            </div>
            <div class="box">
                <table id="sessiondiv" class="shadow">
            <tr>
                <td class="label">Reset session:</td>
                <td><input type="checkbox" name="ResetSession" id="ResetSession" /></td>
            </tr>
            <tr>
                <td class="label">Log out:</td>
                <td><input type="checkbox" name="LogOut" id="LogOut" /></td>
            </tr>

                </table>
            </div>
<?php 	} ?>
            <div class="head">
                        <span style="float:left;">Submit</span>
                        <span style="float:right;"><a id="submitbutton" href="#" onclick="return Toggle_view('submit');">(Hide)</a></span>&nbsp;
            </div>
            <div class="box">
                <table id="submitdiv" class="shadow">
            <tr>
                <td class="label">Reason:</td>
                <td>
                   <textarea rows="8" class="long" name="Reason" id="Reason" onkeyup="resize('Reason');"></textarea>
                </td>
            </tr>

            <tr>
                <td align="right" colspan="2">
                    <input type="submit" value="Save Changes" />
                </td>
            </tr>
                </table>
            </div>
        </form>
<?php  } // end moderation panel

?>
                <script type="text/javascript">
                    var cookieitems= new Array( '<?= implode("','", $CookieItems) ?>' );
                </script>

      <a id="torrents"></a>
<?php
      if ($LoggedUser['HideUserTorrents']==0 && check_paranoia_here('uploads') && check_force_anon($UserID)) {
            $INLINE=true;
            $_GET['userid'] = $UserID;
            $_GET['type'] = 'uploaded';
            include(SERVER_ROOT.'/sections/torrents/user.php');
      }
?>
    </div>
    <div class="clear"></div>
</div>
<?php
show_footer();
