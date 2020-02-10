<?php
define('FOOTER_FILE', SERVER_ROOT.'/design/privatefooter.php');
include_once(SERVER_ROOT . '/common/toolbox.php');
$HTTPS = ($_SERVER['SERVER_PORT'] == 443) ? 'ssl_' : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?=display_str($PageTitle)?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="same-origin">
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
    <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?> Torrentz" href="opensearch.php?type=torrents" />
    <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?> Torrent Tags" href="opensearch.php?type=tags" />
    <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?> Requests" href="opensearch.php?type=requests" />
    <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?> Forums" href="opensearch.php?type=forums" />
    <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?> Log" href="opensearch.php?type=log" />
    <link rel="search" type="application/opensearchdescription+xml" title="<?=SITE_NAME?> Users" href="opensearch.php?type=users" />
    <link rel="alternate" type="application/rss+xml" href="feeds.php?feed=feed_news&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> - News" />
    <link rel="alternate" type="application/rss+xml" href="feeds.php?feed=feed_blog&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> - Blog" />
    <link rel="alternate" type="application/rss+xml" href="feeds.php?feed=torrents_notify_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> - P.T.N." />
<?php  if (isset($LoggedUser['Notify'])) {
    foreach ($LoggedUser['Notify'] as $Filter) {
        list($FilterID, $FilterName) = $Filter;
?>
    <link rel="alternate" type="application/rss+xml" href="feeds.php?feed=torrents_notify_<?=$FilterID?>_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode($FilterName)?>" title="<?=SITE_NAME?> - <?=display_str($FilterName)?>" />
<?php  	}
}?>
    <link rel="alternate" type="application/rss+xml" href="feeds.php?feed=torrents_all&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> - All Torrents" />
    <link href="<?=STATIC_SERVER?>styles/common/normalize.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/common/normalize.css')?>" rel="stylesheet" type="text/css" />
    <link href="<?=STATIC_SERVER?>styles/common/structure.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/common/structure.css')?>" rel="stylesheet" type="text/css" />
    <link href="<?=STATIC_SERVER?>styles/common/tooltipster.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/common/tooltipster.css')?>" rel="stylesheet" type="text/css" />
    <link href="<?=STATIC_SERVER?>styles/common/global.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/common/global.css')?>" rel="stylesheet" type="text/css" />
<?php  if ($Mobile) { ?>
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0, user-scalable=no;"/>
<?php  } else { ?>
    <?php  if (empty($LoggedUser['StyleURL'])) { ?>
    <link href="<?=STATIC_SERVER?>styles/themes/<?=$LoggedUser['StyleName']?>/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/themes/'.$LoggedUser['StyleName'].'/style.css')?>" title="<?=$LoggedUser['StyleName']?>" rel="stylesheet" type="text/css" media="screen" />
    <?php  } else { ?>
    <link href="<?=$LoggedUser['StyleURL']?>" title="External CSS" rel="stylesheet" type="text/css" media="screen" />
    <?php  } ?>
<?php  } ?>

    <script src="<?=STATIC_SERVER?>functions/jquery.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/jquery.js')?>" type="text/javascript"></script>
    <?php if ($LoggedUser['UseTooltipster'] == 0 || $LoggedUser['UseTooltipster'] === null) { ?>
    <script src="<?=STATIC_SERVER?>functions/jquery.tooltipster.min.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/jquery.tooltipster.min.js')?>" type="text/javascript"></script>
    <?php } ?>
    <script src="<?=STATIC_SERVER?>functions/jquery-ui.min.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/jquery-ui.min.js')?>" type="text/javascript"></script>    
    <script src="<?=STATIC_SERVER?>functions/sizzle.js" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/shows.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/shows.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/autocomplete.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/autocomplete.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/jquery.autocomplete.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/jquery.autocomplete.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/script_start.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/script_start.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/class_ajax.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_ajax.js')?>" type="text/javascript"></script>

    <script type="text/javascript">//<![CDATA[
        var authkey = "<?=$LoggedUser['AuthKey']?>";
        var userid = <?=$LoggedUser['ID']?>;
    //]]></script>
    <script src="<?=STATIC_SERVER?>functions/global.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/global.js')?>" type="text/javascript"></script>
    <script type="text/x-mathjax-config">
        MathJax.Hub.Config({
            showMathMenu: false,
            tex2jax: {inlineMath: [['[tex]','[/tex]']]}
        });
    </script>
    <script type="text/javascript" async src="<?=STATIC_SERVER?>functions/MathJax/MathJax.js?config=TeX-AMS_CHTML"></script>
<?php
$Styles=explode(',',$CSSIncludes);
foreach ($Styles as $Style) {
    if (empty($Style)) { continue; }
?>
    <link href="<?=STATIC_SERVER?>styles/sections/<?=$Style?>.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/sections/'.$Style.'.css')?>" rel="stylesheet" type="text/css" />
<?php
}

$Scripts=explode(',',$JSIncludes);

foreach ($Scripts as $Script) {
    if (empty($Script)) { continue; }
?>
    <script src="<?=STATIC_SERVER?>functions/<?=$Script?>.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/'.$Script.'.js')?>" type="text/javascript"></script>
<?php
    if ($Script == 'jquery') { ?>
        <script type="text/javascript">
            $.noConflict();
        </script>
<?php  	} elseif ($Script == 'charts') { ?>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
<?php     }
}
if ($Mobile) { ?>
    <script src="<?=STATIC_SERVER?>styles/mobile/style.js" type="text/javascript"></script>
<?php
}

?>
</head>

<body id="<?=$Document == 'collages' ? 'collage' : $Document?>" <?= ((!$Mobile && $LoggedUser['Rippy'] == 'On') ? 'onload="say()"' : '') ?>>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<div id="wrapper">

<h1 class="hidden"><?=SITE_NAME?></h1>

<div id="header">
    <div id="header_top">
    <div id="logo"><a href="index.php"></a></div>
    <div id="stats_block">
		  <?php
// if there is an active donation drive show the donation bar
$ActiveDrive = $Cache->get_value('active_drive');
if ($ActiveDrive===false) {
    $DB->query("SELECT ID, name, start_time, target_euros, threadid
                      FROM donation_drives WHERE state='active' ORDER BY start_time DESC LIMIT 1");
    if ($DB->record_count()>0) {
            $ActiveDrive = $DB->next_record();
    } else {
            $ActiveDrive = array('false');
    }
    $Cache->cache_value('active_drive' , $ActiveDrive, 0);
}

if (isset($ActiveDrive['ID']) ) {
    list($ID, $name, $start_time, $target_euros, $threadid) = $ActiveDrive;
    $DB->query("SELECT SUM(amount_euro), Count(ID) FROM bitcoin_donations WHERE state!='unused' AND received > '$start_time'");
    list($raised_euros, $count)=$DB->next_record();
    $percentdone = (int) ($raised_euros * 100 / $target_euros);
    if ($percentdone>100) $percentdone=100;
    ?>
<div id="active_drive">
    <div class="donorbar">
        <a href="donate.php" title="Click to donate" data-tooltip-position="left">
            <span class="donortext"><?php if($percentdone>94)echo "Donations: $percentdone%"; if($percentdone<=94)echo "Donations: $percentdone%";?></span>
            <span class="donorpercent" style="width: <?=$percentdone?>%"></span>
        </a>
    </div>
</div>
    <?php
}
?>
<?php /*************/?>
    </div>
<?php
$NewSubscriptions = $Cache->get_value('subscriptions_user_new_'.$LoggedUser['ID']);
if ($NewSubscriptions === FALSE) {
    if ($LoggedUser['CustomForums']) {
        unset($LoggedUser['CustomForums']['']);
        $RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
        $PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
    }
    $DB->query("SELECT COUNT(s.TopicID)
                FROM users_subscriptions AS s
                        JOIN forums_last_read_topics AS l ON s.UserID = l.UserID AND s.TopicID = l.TopicID
                        JOIN forums_topics AS t ON l.TopicID = t.ID
                        JOIN forums AS f ON t.ForumID = f.ID
                WHERE (f.MinClassRead <= ".$LoggedUser['Class']." OR f.ID IN ('$PermittedForums'))
                        AND l.PostID < t.LastPostID
                        AND s.UserID = ".$LoggedUser['ID'].
                (!empty($RestrictedForums) ? "
                        AND f.ID NOT IN ('".$RestrictedForums."')" : ""));
    list($NewSubscriptions) = $DB->next_record();
    $Cache->cache_value('subscriptions_user_new_'.$LoggedUser['ID'], $NewSubscriptions, 0);
}

// Moved alert bar handling to before we draw minor stats to allow showing alert status in links too

//Start handling alert bars
$Infos = array(); // an info alert bar (nicer color)
$Alerts = array(); // warning bar (red!)
$ModBar = array();

// News
$MyNews = $LoggedUser['LastReadNews']+0;
$CurrentNews = $Cache->get_value('news_latest_id');
if ($CurrentNews === false) {
    $DB->query("SELECT ID FROM news ORDER BY Time DESC LIMIT 1");
    if ($DB->record_count() == 1) {
        list($CurrentNews) = $DB->next_record();
    } else {
        $CurrentNews = -1;
    }
    $Cache->cache_value('news_latest_id', $CurrentNews, 0);
}

if ($MyNews < $CurrentNews) {
    $Alerts[] = '<a href="index.php">New Announcement!</a>';
}

//Staff PMs for users
$NewStaffPMs = $Cache->get_value('staff_pm_new_'.$LoggedUser['ID']);
if ($NewStaffPMs === false) {
    $DB->query("SELECT COUNT(ID) FROM staff_pm_conversations WHERE UserID='".$LoggedUser['ID']."' AND Unread = '1'");
    list($NewStaffPMs) = $DB->next_record();
    $Cache->cache_value('staff_pm_new_'.$LoggedUser['ID'], $NewStaffPMs, 0);
}

if ($NewStaffPMs > 0) {
    $Alerts[] = '<a href="staffpm.php?action=user_inbox">'.$NewStaffPMs.(($NewStaffPMs > 1) ? ' New Staff Messages' : ' New Staff Message').'</a>';
}

//Inbox
$NewMessages = $Cache->get_value('inbox_new_'.$LoggedUser['ID']);
if ($NewMessages === false) {
    $DB->query("SELECT COUNT(UnRead) FROM pm_conversations_users WHERE UserID='".$LoggedUser['ID']."' AND UnRead = '1' AND InInbox = '1'");
    list($NewMessages) = $DB->next_record();
    $Cache->cache_value('inbox_new_'.$LoggedUser['ID'], $NewMessages, 0);
}

if ($NewMessages > 0) {
    $Alerts[] = '<a href="inbox.php">You currently have '.$NewMessages.(($NewMessages > 1) ? ' new messages' : ' new message').'</a>';
}

if ($LoggedUser['RatioWatch']) {
    if ($LoggedUser['CanLeech'] == 1) {
        $Alerts[] = '<a href="articles.php?topic=ratio">'.'Ratio Watch'.'</a>: '.'You have '.time_diff($LoggedUser['RatioWatchEnds'],3,true,false,0).' to get your ratio over your required ratio or your leeching abilities will be disabled.';
    } else {
        $Alerts[] = '<a href="articles.php?topic=ratio">'.'Ratio Watch'.'</a>: '.'Your downloading privileges are disabled until you meet your required ratio.';
    }
}

if (check_perms('site_torrents_notify')) {
    $NewNotifications = $Cache->get_value('notifications_new_'.$LoggedUser['ID']);
    if ($NewNotifications === false) {
        $DB->query("SELECT COUNT(UserID) FROM users_notify_torrents WHERE UserID='$LoggedUser[ID]' AND UnRead='1'");
        list($NewNotifications) = $DB->next_record();
        $Cache->cache_value('notifications_new_'.$LoggedUser['ID'], $NewNotifications, 0);
    }
    if ($NewNotifications > 0) {
        $Alerts[] = '<a href="torrents.php?action=notify">'.$NewNotifications.(($NewNotifications > 1) ? ' new torrent notifications' : ' new torrent notification').'</a>';
    }
}

// Forum Subscription
$SubsForumLimit = 2; // 0 to put a link, $n>0 to display up to $n threads
if($LoggedUser['CustomForums']) {
        unset($LoggedUser['CustomForums']['']);
        $RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
        $PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
}

$sql = 'SELECT
        SQL_CALC_FOUND_ROWS
        MAX(p.ID) AS ID
        FROM forums_posts AS p
        LEFT JOIN forums_topics AS t ON t.ID = p.TopicID
        JOIN users_subscriptions AS s ON s.TopicID = t.ID
        LEFT JOIN forums AS f ON f.ID = t.ForumID
        LEFT JOIN forums_last_read_topics AS l ON p.TopicID = l.TopicID AND l.UserID = s.UserID
        WHERE s.UserID = '.$LoggedUser['ID'].'
        AND IF(l.PostID IS NULL OR (t.IsLocked = \'1\' && t.IsSticky = \'0\'), t.LastPostID, l.PostID) < t.LastPostID
        AND p.ID <= IFNULL(l.PostID,t.LastPostID)
        AND ((f.MinClassRead <= '.$LoggedUser['Class'];
if(!empty($RestrictedForums)) {
        $sql.=' AND f.ID NOT IN (\''.$RestrictedForums.'\')';
}
$sql .= ')';
if(!empty($PermittedForums)) {
        $sql.=' OR f.ID IN (\''.$PermittedForums.'\')';
}
$sql .= ')';
$sql .= '
        GROUP BY t.ID
        ORDER BY t.LastPostID DESC
        LIMIT '.($SubsForumLimit + 1);
$PostIDs = $DB->query($sql);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();


if ($NumResults > $SubsForumLimit) {
    $Alerts[] = '<a href="userhistory.php?action=subscriptions">New posts in subscribed threads</a>';
}
else if ($NumResults > 0) {
        $DB->set_query_id($PostIDs);
        $PostIDs = $DB->collect('ID');
        $sql = 'SELECT
                f.ID AS ForumID,
                f.Name AS ForumName,
                p.TopicID,
                t.Title,
                p.Body,
                t.LastPostID,
                t.IsLocked,
                t.IsSticky,
                p.ID,
                p.AuthorID,
                um.Username,
                ui.Avatar,
            um.PermissionID
                FROM forums_posts AS p
                LEFT JOIN forums_topics AS t ON t.ID = p.TopicID
                LEFT JOIN forums AS f ON f.ID = t.ForumID
                LEFT JOIN users_main AS um ON um.ID = p.AuthorID
                LEFT JOIN users_info AS ui ON ui.UserID = um.ID
                LEFT JOIN users_main AS ed ON ed.ID = um.ID
                WHERE p.ID IN ('.implode(',',$PostIDs).')
                ORDER BY f.Name ASC, t.LastPostID DESC';
    $DB->query($sql);
    $Posts = $DB->to_array(false,MYSQLI_ASSOC);
    foreach($Posts as $Post){
        list($ForumID, $ForumName, $TopicID, $ThreadTitle, $Body, $LastPostID, $Locked, $Sticky, $PostID, $AuthorID, $AuthorName, $AuthorAvatar, $PermissionID) = array_values($Post);
        $Alerts[] = 'Updated: '.
                    '<a href="forums.php?action=viewthread&amp;threadid='.$TopicID.($PostID?'&amp;postid='.$PostID.'#post'.$PostID:'').'"'.
                    ' title="'.display_str($ThreadTitle).'">'.
                    cut_string($ThreadTitle, 40).'</a>';
    }
}

// Collage subscriptions
if (check_perms('site_collages_subscribe')) {
    $NewCollages = $Cache->get_value('collage_subs_user_new_'.$LoggedUser['ID']);
    if ($NewCollages === FALSE) {
            $DB->query("SELECT COUNT(DISTINCT s.CollageID)
                    FROM users_collage_subs as s
                    JOIN collages as c ON s.CollageID = c.ID
                    JOIN collages_torrents as ct on ct.CollageID = c.ID
                    WHERE s.UserID = ".$LoggedUser['ID']." AND ct.AddedOn > s.LastVisit AND c.Deleted = '0'");
            list($NewCollages) = $DB->next_record();
            $Cache->cache_value('collage_subs_user_new_'.$LoggedUser['ID'], $NewCollages, 0);
    }
    if ($NewCollages > 0) {
        $Alerts[] = '<a href="userhistory.php?action=subscribed_collages">You have '.$NewCollages.(($NewCollages > 1) ? ' new collage updates' : ' new collage update').'</a>';
    }
}

if (check_perms('users_mod')) {
    $ModBar[] = '<a href="tools.php">Toolbox</a>';
}
//changed check so that FLS as well as staff can see PM's (always restricted by userclass anyway so its just a nicety for FLS)
if ($LoggedUser['SupportFor'] !="" || $LoggedUser['DisplayStaff'] == 1) {
    $DB->query("SELECT COUNT(ID) FROM staff_pm_conversations
                 WHERE (AssignedToUser={$LoggedUser['ID']} OR Level <={$LoggedUser['Class']})
                   AND Status IN ('Unanswered', 'User Resolved')");
    list($NumUnansweredStaffPMs) = $DB->next_record();
    $DB->query("SELECT COUNT(ID) FROM staff_pm_conversations
                 WHERE (AssignedToUser={$LoggedUser['ID']} OR Level <={$LoggedUser['Class']})
                   AND Status = 'Open'");
    list($NumOpenStaffPMs) = $DB->next_record();
    $NumOpenStaffPMs += $NumUnansweredStaffPMs;
    //}
    if ($NumUnansweredStaffPMs > 0 || $NumOpenStaffPMs >0) $ModBar[] =
        '<a href="staffpm.php?view=unanswered">('.$NumUnansweredStaffPMs.')</a><a href="staffpm.php?view=open">('.$NumOpenStaffPMs.') Staff PMs</a>';
}

if (check_perms('admin_reports')) {
    $NumTorrentReports = $Cache->get_value('num_torrent_reportsv2');
    if ($NumTorrentReports === false) {
        $DB->query("SELECT COUNT(ID) FROM reportsv2 WHERE Status='New'");
        list($NumTorrentReports) = $DB->next_record();
        $Cache->cache_value('num_torrent_reportsv2', $NumTorrentReports, 0);
    }

    $ModBar[] = '<a href="reportsv2.php">'.$NumTorrentReports.(($NumTorrentReports == 1) ? ' Report' : ' Reports').'</a>';
}

if (check_perms('users_mod')) {
    $NumDeleteRequests = $Cache->get_value('num_deletion_requests');
    if ($NumDeleteRequests === false) {
      $DB->query("SELECT COUNT(*) FROM deletion_requests");
      list($NumDeleteRequests) = $DB->next_record();
      $Cache->cache_value('num_deletion_requests', $NumDeleteRequests);
    }
    if ($NumDeleteRequests > 0) {
      $ModBar[] = '<a href="tools.php?action=expunge_requests">' . $NumDeleteRequests . " Expunge request".($NumDeleteRequests > 1 ? 's' : '')."</a>";
    }
  }

if (check_perms('admin_reports')) {
    $NumOtherReports = $Cache->get_value('num_other_reports');
    if ($NumOtherReports === false) {
        $DB->query("SELECT COUNT(ID) FROM reports WHERE Status='New'");
        list($NumOtherReports) = $DB->next_record();
        $Cache->cache_value('num_other_reports', $NumOtherReports, 0);
    }

    $ModBar[] = '<a href="reports.php">'.$NumOtherReports.(($NumTorrentReports == 1) ? ' Other Report' : ' Other Reports').'</a>';

} elseif (check_perms('project_team')) {
    $NumUpdateReports = $Cache->get_value('num_update_reports');
    if ($NumUpdateReports === false) {
        $DB->query("SELECT COUNT(ID) FROM reports WHERE Status='New' AND Type = 'request_update'");
        list($NumUpdateReports) = $DB->next_record();
        $Cache->cache_value('num_update_reports', $NumUpdateReports, 0);
    }

    if ($NumUpdateReports > 0) {
        $ModBar[] = '<a href="reports.php">'.'Request update reports'.'</a>';
    }
} elseif (check_perms('site_moderate_forums')) {
    $NumForumReports = $Cache->get_value('num_forum_reports');
    if ($NumForumReports === false) {
        $DB->query("SELECT COUNT(ID) FROM reports WHERE Status='New' AND Type IN('collages_comment', 'Post', 'requests_comment', 'thread', 'torrents_comment')");
        list($NumForumReports) = $DB->next_record();
        $Cache->cache_value('num_forum_reports', $NumForumReports, 0);
    }

    if ($NumForumReports > 0) {
        $ModBar[] = '<a href="reports.php">'.'Forum reports'.'</a>';
    }
}
      ?>


<?php

// draw the alert bars (arrays set already^^)
if (!empty($Alerts) || !empty($ModBar)  || !empty($Infos) ) {
?>
    <div id="alerts">
    <?php
         foreach ($Alerts as $Alert) { ?>
        <div class="alertbar"><?=$Alert?></div>
    <?php  }
        /*if (!empty($ModBar)) { ?>
        <div id="modbar" class="alertbar blend"> <?=implode(' | ',$ModBar); ?></div>
    <?php  }*/
        if (!empty($Infos)) {
            foreach ($Infos as $Infobar) { ?>
            <div class="alertbar bluebar"><?=$Infobar?></div>
    <?php       }
        } ?>
    </div>
<?php
}
//Done handling alertbars

if (!$Mobile && $LoggedUser['Rippy'] != 'Off') {
    switch ($LoggedUser['Rippy']) {
        case 'PM' :
            $Says = $Cache->get_value('rippy_message_'.$LoggedUser['ID']);
            if ($Says === false) {
                $Says = $Cache->get_value('global_rippy_message');
            }
            $Show = ($Says !== false);
            $Cache->delete_value('rippy_message_'.$LoggedUser['ID']);
            break;
        case 'On' :
            $Show = true;
            $Says = '';
            break;
    }

    if ($Show) {
?>
    <div class="rippywrap">
        <div id="bubble" style="display: <?=($Says ? 'block' : 'none')?>">
            <span class="rbt"></span>
            <span id="rippy-says" class="rbm"><?=$Says?></span>
            <span class="rbb"></span>
        </div>
        <div class="rippy" onclick="rippyclick();"></div>
    </div>
<?php
    }
}
?>

    <div id="searchbars">
        <ul>
            <li id="searchbar_torrents">
                <span class="hidden">Torrents: </span>
                <form action="/torrents.php" method="get">
                    <div class="searchcontainer">
<?php  if (isset($LoggedUser['SearchType']) && $LoggedUser['SearchType']) { // Advanced search searchtext=anal&action=advanced ?>
                    <input type="hidden" name="action" value="advanced" />
<?php  } ?>
                    <input
                        id="searchbox_torrents"
                        autocomplete="off"
                        data-gazelle-autocomplete="true"
                        class="searchbox"
                        accesskey="t"
                        spellcheck="false"
                        placeholder="Torrents"
                        type="text" name="searchtext" title="Torrents - enter text and press Enter to search"
                    />
                </form>
            </li>
            <li id="searchbar_shows">
                <span class="hidden">Shows: </span>
                <form action="/torrents.php" method="get">
                    <div class="searchcontainer">
                    <input type="hidden" name="action" id="shows_action" value="" />
                    <input
                        id="searchbox_shows"
                        autocomplete="off"
                        data-gazelle-autocomplete="true"
                        class="searchbox"
                        accesskey="s"
                        spellcheck="false"
                        placeholder="Shows"
                        type="text" name=""  title="Shows - enter text or TVMazeID and press Enter to search"
                    />
                </form>
            </li> 
            <li id="searchbar_people">
                <span class="hidden">People: </span>
                <form action="/people.php" method="get">
                    <div class="searchcontainer">
                    <input
                        id="searchbox_people"
                        autocomplete="off"
                        data-gazelle-autocomplete="true"
                        class="searchbox"
                        accesskey="p"
                        spellcheck="false"
                        placeholder="People"
                        type="text" name="search" title="People - enter text and press Enter to search"
                    />
                </form>
            </li>                        
            <li id="searchbar_requests">
                <span class="hidden">Requests: </span>
                <form action="/requests.php" method="get">
                    <div class="searchcontainer">
                    <input
                        id="searchbox_requests"
                        class="searchbox"
                        accesskey="r"
                        spellcheck="false"
                        placeholder="Requests"
                        type="text" name="search" title="Requests - enter text and press Enter to search"
                    />
                </form>
            </li>
            <li id="searchbar_forums">
                <span class="hidden">Forums: </span>
                <form action="/forums.php" method="get">
                    <div class="searchcontainer">
                    <input value="search" type="hidden" name="action" />
                    <input
                        id="searchbox_forums"
                        class="searchbox"
                        accesskey="f"
                        placeholder="Forums"
                        type="text" name="search" title="Forums - enter text and press Enter to search"
                    />
                </form>
            </li>
            <li id="searchbar_help">
                <span class="hidden">Wiki: </span>
                <form action="/articles.php" method="get">
                    <div class="searchcontainer">
                    <input
                        id="searchbox_help"
                        class="searchbox"
                        accesskey="w"
                        placeholder="Wiki / Rules"
                        type="text" name="searchtext" title="Wiki &amp; Rules Articles - enter text and press Enter to search"
                    />
                </form>
            </li>
            <li id="searchbar_users">
                <span class="hidden">Users: </span>
                <form action="/user.php" method="get">
                    <div class="searchcontainer">
                    <input type="hidden" name="action" value="search" />
                    <input
                        id="searchbox_users"
                        class="searchbox"
                        accesskey="u"
                        placeholder="Users"
                        type="text" name="search" size="17" title="Users - enter text and press Enter to search"
                    />
                </form>
            </li>
        </ul>
    </div>
    </div>
<?php
    list($Seeding, $Leeching)= array_values(user_peers($LoggedUser['ID']));
    function get_peer_span($Spanid, $Num)
    {
        if($Num>0) return '<span id="'.$Spanid.'">'.number_format($Num).'</span>';
        else return '0';
    }
?>
    <div id="header_bottom">
        <div id="major_stats_left">
            <ul id="userinfo_major">
                <li><a id="nav_seeding"   class="user_peers" href="torrents.php?type=seeding&amp;userid=<?=$LoggedUser['ID']?>" title="View seeding torrents">Seeding: <?=get_peer_span('nav_seeding_r',$Seeding)?></a></li>
                <li><a id="nav_leeching"  class="user_peers" href="torrents.php?type=leeching&amp;userid=<?=$LoggedUser['ID']?>" title="View leeching torrents">Leeching: <?=get_peer_span('nav_leeching_r',$Leeching)?></a></li>
                <li><a id="nav_upload"    class="stat" href="torrents.php?type=seeding&amp;userid=<?=$LoggedUser['ID']?>" title="Amount of data you have uploaded">Up: <?=get_size($LoggedUser['BytesUploaded'])?></a></li>
                <li><a id="nav_hnr"       class="stat" href="torrents.php?type=hitandrun&amp;userid=<?=$LoggedUser['ID']?>" title="Hit & Run torrents">HnRs: <?=get_peer_span('nav_leeching_r',$LoggedUser['HnR'])?></a></li>
                <?php if (check_perms('site_downloaded')) { ?>                 
                   <li><a id="nav_download"  class="stat" href="torrents.php?type=seeding&amp;userid=<?=$LoggedUser['ID']?>" title="Amount of data you have downloaded">Down: <?=get_size($LoggedUser['BytesDownloaded'])?></a></li>
                <?php } ?> 
                <li><a id="nav_credits"   class="stat" href="bonus.php">Cubits: <?=number_format((int) $LoggedUser['TotalCredits'])?></a></li>
                <li><a id="nav_invites"   class="stat" href="user.php?action=invite">Invites: <?=check_perms('site_send_unlimited_invites')? "&infin;" : number_format((int) $LoggedUser['Invites'])?></a></li>
            </ul>
        </div>
<?php
if ($SiteOptions['SitewideFreeleechMode'] == "timed") {

    $TimeNow = date('M d Y, H:i', strtotime($SiteOptions['SitewideFreeleechTime']) - (int) $LoggedUser['TimeOffset']);
    $PFL = '<span class="time" title="Sitewide Freeleech for '. time_diff($SiteOptions['SitewideFreeleechTime'],2,false,false,0).' (until '.$TimeNow.')">Sitewide Freeleech for '.time_diff($SiteOptions['SitewideFreeleechTime'],2,false,false,0).'</span>';

} else {

    $TimeStampNow = time();
    $PFLTimeStamp = strtotime($LoggedUser['personal_freeleech']);

    if ($PFLTimeStamp >= $TimeStampNow) {

        if (($PFLTimeStamp - $TimeStampNow) < (28*24*3600)) { // more than 28 days freeleech and the time is only specififed in the tooltip //
            $TimeAgo = time_diff($LoggedUser['personal_freeleech'],2,false,false,0);
            $PFL = "PFL for $TimeAgo";
        } else {
            $PFL = "Personal Freeleech";
        }
        $TimeNow = date('M d Y, H:i', $PFLTimeStamp - (int) $LoggedUser['TimeOffset']);
        $PFL = '<span class="time" title="Personal Freeleech until '.$TimeNow.'">'.$PFL.'</span>';
    }

}

if ( !empty($PFL)) { ?>
            <div class="nicebar"><?=$PFL?></div>
<?php   }

if ($SiteOptions['SitewideDoubleseedMode'] == "timed") {

    $TimeNow = date('M d Y, H:i', strtotime($SiteOptions['SitewideDoubleseedTime']) - (int) $LoggedUser['TimeOffset']);
    $PDS = '<span class="time" title="Sitewide Doubleseed for '. time_diff($SiteOptions['SitewideDoubleseedTime'],2,false,false,0).' (until '.$TimeNow.')">Sitewide Doubleseed for '.time_diff($SiteOptions['SitewideDoubleseedTime'],2,false,false,0).'</span>';

} else {

    $TimeStampNow = time();
    $PDSTimeStamp = strtotime($LoggedUser['personal_doubleseed']);

    if ($PDSTimeStamp >= $TimeStampNow) {

        if (($PDSTimeStamp - $TimeStampNow) < (28*24*3600)) { // more than 28 days doubleseed and the time is only specififed in the tooltip //
            $TimeAgo = time_diff($LoggedUser['personal_doubleseed'],2,false,false,0);
            $PDS = "PDS for $TimeAgo";
        } else {
            $PDS = "Personal Doubleseed";
        }
        $TimeNow = date('M d Y, H:i', $PDSTimeStamp - (int) $LoggedUser['TimeOffset']);
        $PDS = '<span class="time" title="Personal Doubleseed until '.$TimeNow.'">'.$PDS.'</span>';
    }

}

if ( !empty($PDS)) { ?>
            <div class="nicebar"><?=$PDS?></div>
<?php   }  ?>

            <div id="major_stats">
                <ul id="userinfo_tools">
<?php

if (check_perms('users_mod') || $LoggedUser['SupportFor'] !="" || $LoggedUser['DisplayStaff'] == 1 ) {
?>
                    <li id="nav_tools"><a href="tools.php">Tools</a>
                        <ul>
<?php               foreach($Toolbox as $Tool) {
                        list($ToolName, $ToolAction, $ToolPermission) = $Tool;
                        if (check_perms($ToolPermission)) { ?>
                             <li><a href="tools.php?action=<?=$ToolAction?>"><?=$ToolName?></a></li>
<?php                   } 
                    }
                    if (check_perms('users_groups')) { ?>
                            <li><a href="groups.php">User Groups</a></li>
<?php               }  ?>
                        </ul>
                    </li>
<?php  } ?>
                    <li id="nav_donate" class="brackets"><a href="donate.php">Donate</a></li>
                    <li id="nav_useredit" class="brackets"><a href="user.php?action=edit&amp;userid=<?=$LoggedUser['ID']?>" title="Edit User Settings">Edit</a></li>
                    <li id="nav_logout" class="brackets"><a href="logout.php?auth=<?=$LoggedUser['AuthKey']?>">Logout</a></li>
                </ul>
            </div>
    </div>
</div>

<div id="candyfloss">
  <h4 class="hidden">Site Menu</h4>
<nav id="mainnav">
    <ul>
        <li class="left-round"><a href="/">Home</a></li>
        <li class="dropdown-more"><a href="shows.php" class="hovertouch">TV Info</a>       
				<ul>
                <li><a href="shows.php">Shows</a></li>
                <li><a href="follows.php">Follows</a></li>
                <li><a href="people.php">People</a></li>
                <li><a href="tvschedule.php">Schedule</a></li>
            </ul>
        </li>
        <li class="dropdown-more"><a href="torrents.php" class="hovertouch">Torrents</a>
            <ul>
                <li><a href="torrents.php">Browse</a></li>
                <li><a href="bookmarks.php">Bookmarks</a></li>
                <li><a href="upload.php">Upload</a></li>
                <li><a href="collages.php">Collages</a></li>
                <li><a href="top10.php">Top 10</a></li>
                <li><a href="tags.php">Tags</a></li>
            </ul>
        </li>
        <li><a href="requests.php">Requests</a></li>
        <li class="dropdown-more"><a href="forums.php" class="hovertouch">Community</a>
            <ul>
                <li><a href="forums.php">Forums</a></li>
                <li><a href="friends.php">Friends</a></li>
                <li><a href="chat.php">Chat</a></li>
                <li><a href="https://images.nebulance.io" target="_new">Imagehost</a></li>
            </ul>
        </li>
        <li class="dropdown-more"><a href="articles.php?topic=rules" class="hovertouch">Wiki &amp; Support</a>
            <ul>
                <li><a href="articles.php?topic=rules">Rules</a></li>
                <li><a href="articles.php?topic=tutorials">Wiki</a></li>
                <li><a href="staff.php">Staff</a></li>
                <!--li><a href="logout.php?auth=<?=$LoggedUser['AuthKey']?>">Reboot Server</a></li-->
            </ul>
        </li>
        <li><a href="bonus.php">Black Market</a></li>
        <li class= "right-round" class="<?=($NewMessages||$NumUnansweredStaffPMs||$NewStaffPMs||$NewNotifications||$NewSubscriptions)? 'highlight' : 'bold'?> dropdown-more"><a href="user.php?id=<?=$LoggedUser['ID']?>" class="hovertouch username"><?=$LoggedUser['Username']?></a>
          <ul>
                                <li id="nav_profile" class="normal hidden"><a href="user.php?id=<?=$LoggedUser['ID']?>" class="username">Profile</a></li>
                                <li id="nav_myrequests" class="normal"><a onmousedown="Stats('requests');" href="user.php?action=edit&amp;userid=<?=$LoggedUser['ID']?>" title="Edit User Settings">Settings</a></li>
                                <li id="nav_inbox" class="<?=$NewMessages ? 'highlight' : 'normal'?>"><a onmousedown="Stats('inbox');" href="inbox.php">Inbox<?=$NewMessages ? "($NewMessages)" : ''?></a></li>
    <?php  if ($LoggedUser['SupportFor'] !="" || $LoggedUser['DisplayStaff'] == 1) {  ?>
                      <li id="nav_staffinbox" class="<?=($NumUnansweredStaffPMs)? 'highlight' : 'normal'?>">
                          <a onmousedown="Stats('staffinbox');" href="staffpm.php?action=staff_inbox&amp;view=open">Staff Inbox <?="($NumUnansweredStaffPMs) ($NumOpenStaffPMs)"?></a>
                      </li>
    <?php  } ?>
                                <li id="nav_staffmessages" class="<?=$NewStaffPMs ? 'highlight' : 'normal'?>"><a onmousedown="Stats('staffpm');" href="staffpm.php?action=user_inbox">Message Staff<?=$NewStaffPMs ? "($NewStaffPMs)" : ''?></a></li>

                                <li id="nav_uploaded" class="normal"><a onmousedown="Stats('uploads');" href="torrents.php?type=uploaded&amp;userid=<?=$LoggedUser['ID']?>">Uploads</a></li>
<?php  if (check_perms('site_submit_requests')) { ?>
                                <li id="nav_myrequests" class="normal"><a onmousedown="Stats('requests');" href="requests.php?type=created">My Requests</a></li>
<?php  } ?>
                                <li id="nav_bookmarks" class="normal"><a onmousedown="Stats('bookmarks');" href="bookmarks.php?type=torrents">Bookmarks</a></li>
                                <li id="nav_favorites" class="normal"><a onmousedown="Stats('favorites');" href="follows.php">Follows</a></li>
<?php  if (check_perms('site_torrents_notify')) { ?>
                                <li id="nav_notifications" class="<?=$NewNotifications ? 'highlight' : 'normal'?>"><a onmousedown="Stats('notifications');" href="torrents.php?action=notify">Notifications<?=$NewNotifications ? "($NewNotifications)" : ''?></a></li>
<?php  } ?>
                                <li id="nav_subscriptions" class="<?=$NewSubscriptions ? 'highlight' : 'normal'?>"><a onmousedown="Stats('subscriptions');" href="userhistory.php?action=subscriptions"<?=($NewSubscriptions ? ' class="new-subscriptions"' : '')?>>Subscriptions<?=$NewSubscriptions ? "($NewSubscriptions)" : ''?></a></li>
                                <li id="nav_posthistory" class="normal"><a href="userhistory.php?action=posts&amp;group=0&amp;showunread=0">Post History</a></li>
                                <li id="nav_comments" class="normal"><a onmousedown="Stats('comments');" href="userhistory.php?action=comments">Comments</a></li>
                                <li id="nav_invites" class="normal"><a onmousedown="Stats('invites');" href="user.php?action=invite">Invites</a></li>
                                <li id="nav_friends" class="normal"><a onmousedown="Stats('friends');" href="friends.php">Friends</a></li>

                                <li id="nav_mydonations" class="normal"><a href="donate.php?action=my_donations">My Donations</a></li>

                                <li id="nav_bonus" class="normal" title="Spend your Cubits in the Black Market"><a href="bonus.php">Black Market</a></li>
                                <li id="nav_nextclass" class="normal" title="Check stats and purchase Next Class promotion"><a href="user.php?action=next_class">Next Class</a></li>
<?php           if ( check_perms('site_give_specialgift') ) {  ?>
                                <li id="nav_gift" class="normal" title="Give a gift of Cubits to a colonian in need"><a href="bonus.php?action=gift">Special Gift</a></li>
<?php           } ?>
                                <li id="nav_sandbox" class="normal"><a href="sandbox.php">Sandbox</a></li>

<?php           if ( check_perms('site_play_slots') ) {  ?>
                                <li id="nav_slots" class="normal"><a href="bonus.php?action=slot">Slot Machine</a></li>
<?php           } ?>
          </ul>
      </li>
    </ul>
</nav>
</div>
<script type="text/javascript">
jQuery( document ).ready(function( $ ) {
    window.addEventListener('touchstart', function onFirstTouch() {
        $('.hovertouch').removeAttr('href');
        $('#nav_profile').removeClass('hidden');
        window.removeEventListener('touchstart', onFirstTouch, false);
    });
});
</script>
<?php
if (!empty($ModBar)) { ?>
        <div id="modbar" class="blendy"> <?=implode(' | ',$ModBar); ?></div>
    <?php  } ?>
<div id="content">
