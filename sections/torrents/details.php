<?php
define(MAX_PERS_COLLAGES, 3); // How many personal collages should be shown by default

include(SERVER_ROOT.'/sections/tools/managers/mfd_functions.php');
include(SERVER_ROOT.'/sections/requests/functions.php');
include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/sections/torrents/functions.php');

$Text = new TEXT;

if(!$GroupID) $GroupID=ceil($_GET['id']);

$TorrentCache = get_group_info($GroupID, true);

$TorrentDetails = $TorrentCache[0];
$TorrentList = $TorrentCache[1];
$TorrentTags = $TorrentCache[2];

list($Body, $Mediainfo, $Screens, $Trailer, $Synopsis, $EpisodeGuide, $Image, $GroupID, $GroupName, $GroupCategoryID, $GroupTime,  , $PosterURL, $TVMAZE, $TMDb) = array_shift($TorrentDetails);

$Synopsis = str_replace('\\','',$Synopsis); // remove extra '\'

if(substr($Synopsis,0,5) == "&#39;") // cover old way
 $Synopsis = substr($Synopsis,5,-5); // remove extra ' at start and end of the string

$EpisodeGuide = str_replace('\\','',$EpisodeGuide); // remove extra '\'

list($TorrentID, $FileCount, $Size, $Seeders, $Leechers, $Snatched, $FreeTorrent, $DoubleTorrent, $TorrentTime,
        $FileList, $FilePath, $UserID, $Username, $LastActive,
        $BadTags, $BadFolders, $BadFiles, $LastReseedRequest, $LogInDB, $HasFile, $IsAnon, $Season, $Episode, $AirDate) = $TorrentList[0];

$DisplayName=$GroupName;
$AltName=$GroupName; // Goes in the alt text of the image
$Title=$GroupName; // goes in <title>

$SearchTitle = explode(" - " , $GroupName); //extract title for search
$EpisodeTitle = $SearchTitle[2]; // get the episode title
$SearchTitle = $SearchTitle[0]; // get the title

if($TMDb && !$EpisodeTitle) $EpisodeTitle = $SearchTitle; // a tv movie

if(!$Synopsis) {
	if($EpisodeTitle && $Season && $Episode) $Synopsis = "<i>We don't have a summary for $EpisodeTitle yet.</i>";
	elseif(!$EpisodeTitle && $Season && $Episode) $Synopsis = "<i>We don't have a summary yet.</i>";
	elseif($Season && !$Episode) $Synopsis = "[i]We don't have a summary yet.[/i]";
	else $Synopsis = "<i>We don't have a summary yet.</i>";
}	

$tagsort = isset($_GET['tsort'])?$_GET['tsort']:'uses';
if(!in_array($tagsort, array('uses','score','az','added'))) $tagsort = 'uses';

$Tags = array();
if ($TorrentTags != '') {
    foreach ($TorrentTags as $TagKey => $TagDetails) {
        list($TagName, $TagID, $TagUserID, $TagUsername, $TagUses, $TagPositiveVotes, $TagNegativeVotes,
                $TagVoteUserIDs, $TagVoteUsernames, $TagVoteWays) = $TagDetails;

        $Tags[$TagKey]['name'] = $TagName;
        $Tags[$TagKey]['score'] = ($TagPositiveVotes - $TagNegativeVotes);
        $Tags[$TagKey]['id']= $TagID;
        $Tags[$TagKey]['userid']= $TagUserID;
        $Tags[$TagKey]['username']= anon_username_ifmatch($TagUsername, $Username, $IsAnon) ;
        $Tags[$TagKey]['uses']= $TagUses;

        $TagVoteUsernames = explode('|',$TagVoteUsernames);
        $TagVoteWays = explode('|',$TagVoteWays);
        $VoteMsgs=array();
        $VoteMsgs[]= "$TagName (" . str_plural('use' , $TagUses).')';
        $VoteMsgs[]= "added by ".anon_username_ifmatch($TagUsername, $Username, $IsAnon);
        foreach ($TagVoteUsernames as $TagVoteKey => $TagVoteUsername) {
            if (!$TagVoteUsername) continue;
            $VoteMsgs[] = $TagVoteWays[$TagVoteKey] . " (". anon_username_ifmatch($TagVoteUsername, $Username, $IsAnon).")";
        }
        $Tags[$TagKey]['votes'] = implode("\n", $VoteMsgs);
    }

    uasort($Tags, "sort_{$tagsort}_desc");
}

//advance tagsort for link
if($tagsort=='score') $tagsort2='az';
else if($tagsort=='az') $tagsort2='uses';
else $tagsort2='score';

$LoggedUserID = $LoggedUser['ID'];
$TokenTorrents = $Cache->get_value('users_tokens_'.$LoggedUserID);
if (empty($TokenTorrents)) {
    $DB->query("SELECT TorrentID, FreeLeech, DoubleSeed FROM users_slots WHERE UserID=$LoggedUserID");
    $TokenTorrents = $DB->to_array('TorrentID');
    $Cache->cache_value('users_tokens_'.$LoggedUserID, $TokenTorrents);
}

$Review = get_last_review($GroupID);

// Start output
show_header($Title,'comments,status,torrent,bbcode,details,watchlist,jquery.cookie,browse,overlib','details');  // ,tag_autocomplete,autocomplete

$IsUploader =  $UserID == $LoggedUser['ID'];
$CanEdit = (check_perms('torrents_edit') ||  $IsUploader );

$Reported = false;
unset($ReportedTimes);
$Reports = $Cache->get_value('reports_torrent_'.$TorrentID);
if ($Reports === false) {
        $DB->query("SELECT r.ID,
                r.ReporterID,
                r.Type,
                r.UserComment,
                r.ReportedTime
            FROM reportsv2 AS r
            WHERE TorrentID = $TorrentID
                AND Type != 'edited'
                AND Status != 'Resolved'");
        $Reports = $DB->to_array();
        $Cache->cache_value('reports_torrent_'.$TorrentID, $Reports, 0);
}

if (count($Reports) > 0) {
    $Title = "This torrent has ".count($Reports)." active ".(count($Reports) > 1 ?'reports' : 'report');
    $DisplayName .= ' <span style="color: #FF3030; padding: 2px 4px 2px 4px;" title="'.$Title.'">Reported</span>';
}

$IsBookmarked = has_bookmarked('torrent', $GroupID);

$sqltime = sqltime();

$Icons = '';
if ($DoubleTorrent == '1') {
    $SeedTooltip = "Unlimited Doubleseed";
} elseif (!empty($TokenTorrents[$TorrentID]) && $TokenTorrents[$TorrentID]['DoubleSeed'] > $sqltime) {
    $SeedTooltip = "Personal Doubleseed Slot for ".time_diff($TokenTorrents[$TorrentID]['DoubleSeed'], 2, false,false,0);
}

if ($FreeTorrent == '1') {
    $FreeTooltip = "Unlimited Freeleech";
} elseif (!empty($TokenTorrents[$TorrentID]) && $TokenTorrents[$TorrentID]['FreeLeech'] > $sqltime) {
    $FreeTooltip = "Personal Freeleech Slot for ".time_diff($TokenTorrents[$TorrentID]['FreeLeech'], 2, false,false,0);
} elseif ($LoggedUser['personal_freeleech'] > $sqltime) {
    $FreeTooltip = "Personal Freeleech for ".time_diff($LoggedUser['personal_freeleech'], 2, false,false,0);
} elseif ($SiteOptions['SitewideFreeleechMode'] == "timed") {
    $FreeTooltip = "Sitewide Freeleech for ".time_diff($SiteOptions['SitewideFreeleechTime'], 2,false,false,0);
}

if ($SeedTooltip)
    $Icons .= '<span class="icon icon_doubleseed" title="'.$SeedTooltip.'"></span> ';

if ($IsBookmarked)
    $Icons .= '<img src="static/styles/themes/'. $LoggedUser['StyleName'].'/images/bookmarked.svg" alt="bookmarked" title="You have this torrent bookmarked" />&nbsp;';
$Icons .= '&nbsp;';

// For now we feed this function some 'false' information to prevent certain icons from occuring that are already present elsewhere on the page
$ExtraIcons = torrent_icons(array('FreeTorrent'=>false, 'DoubleTorrent'=>false), $TorrentID, $Review, false);

?>
<div class="details thin">
    <p/><h2><span class="arrow" style="float:left"><a href="torrents.php?id=<?=$GroupID?>&action=prev"><img src="static/styles/themes/<?=$LoggedUser['StyleName']?>/images/arrow_left.png" alt="prev" title="goto previous torrent" /></a></span><?="$Icons$DisplayName"?><span class="arrow" style="float:right"><a href="torrents.php?id=<?=$GroupID?>&action=next"><img src="static/styles/themes/<?=$LoggedUser['StyleName']?>/images/arrow_right.png" alt="next" title="goto next torrent" /></a></span></h2><br/>

<?php
if (check_perms('torrents_review')) {
    if (!isset($_GET['checked'])) update_staff_checking("viewing \"".cut_string($GroupName, 32)."\"  #$GroupID", true);

?>
    <div id="staff_status" class="status_box">
        <span class="status_loading">loading staff checking status...</span>
    </div>
    <br class="clear"/>
    <script type="text/javascript">
        setTimeout("Update_status();", 500);
    </script>
<?php
}

    if ($Review['Status'] == 'Warned' || $Review['Status'] == 'Pending') {
?>
    <div id="warning_status" class="box vertical_space">
        <div class="redbar warning">
                <strong>Status:&nbsp;Warned&nbsp; (<?=$Review['StatusDescription']?>)</strong>
            </div>
            <div class="pad"><strong>This torrent has been marked for deletion and will be automatically deleted unless the uploader fixes it. </strong><span style="float:right;"><?=time_diff($Review['KillTime'])?></span></div>
<?php       if ($UserID == $LoggedUser['ID']) { // if the uploader is looking at the warning message
            if ($Review['Status'] == 'Warned') { ?>
                <div id="user_message" class="center">If you have fixed this upload make sure you have told the staff: <a class="button greenButton" onclick="Send_Okay_Message(<?=$GroupID?>,<?=($Review['ConvID']?$Review['ConvID']:0)?>);" title="send staff a message">By clicking here</a></div>
<?php           } else {  ?>
                <div id="user_message" class="center"><div class="messagebar"><a href="staffpm.php?action=viewconv&id=<?=$Review['ConvID']?>">You sent a message to staff <?=time_diff($Review['Time'])?></a></div></div>
<?php           }
        }
?>
    </div>
<?php
    }
      $AlertClass = ' hidden';
    if (isset($_GET['did']) && is_number($_GET['did'])) {
          if ($_GET['did'] == 1) {
              $ResultMessage ='Successfully edited description';
              $AlertClass = '';
          } elseif ($_GET['did'] == 2) {
              $ResultMessage ='Successfully renamed title';
              $AlertClass = '';
          } elseif ($_GET['did'] == 3) {
              $ResultMessage = 'Added '. display_str($_GET['addedtag']);
              if (isset($_GET['synonym'])) $ResultMessage .= ' as a synonym of '. display_str($_GET['synonym']);
              $AlertClass = '';
          } elseif ($_GET['did'] == 4) {
              $ResultMessage = display_str($_GET['addedtag']). ' is already added.';
              $AlertClass = ' alert';
          } elseif ($_GET['did'] == 5) {
              $ResultMessage = display_str($_GET['synonym']). ' is a synonym for '. display_str($_GET['addedtag']). ' which is already added.';
              $AlertClass = ' alert';
          } elseif ($_GET['did'] == 6) {
              $ResultMessage = 'Successfully scraped TVMaze';
              $AlertClass = '';
          } elseif ($_GET['did'] == 7) {
              $ResultMessage = 'Successfully scraped TMDb';
              $AlertClass = '';
          }                    
      }
?>
    <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>

    <div class="linkbox" >
           [
<?php   if ($CanEdit) { ?>           
<?php       if (check_perms('torrents_edit') || check_perms('site_upload_anon') && (time_ago($TorrentTime) < TORRENT_EDIT_TIME && $Review['Status'] != 'Okay' || $Review['Status'] != 'Okay' && $Review['Status'] || check_perms('site_edit_override_timelock'))) { ?>
               <a href="torrents.php?action=editanon&amp;groupid=<?=$GroupID?>" title="Set if uploader info is visible for other users">Anon</a>
           |     
<?php       } ?>
<?php   } ?>
<?php   if (check_perms('site_debug')) { ?>
             <a href="torrents.php?action=output_enc&torrentid=<?=$TorrentID ?>" title="View bencode data">Bencode</a>
           |  
<?php   } ?>           
<?php   if ($IsBookmarked) { ?>
             <a href="#" class="__bookmark-torrent" data-torrentid="<?=$GroupID?>" data-bookmarked>Remove bookmark</a>
<?php   } else { ?>
             <a href="#" class="__bookmark-torrent" data-torrentid="<?=$GroupID?>">Bookmark</a>
<?php   } ?>
<?php   if (check_perms('torrents_delete')) {  // for staff only ?>             
           | <a href="<?=get_search($SearchTitle)?>" title="Search for <?=$SearchTitle?>">Browse</a>
<?php   } ?>
<?php   if (check_perms('site_debug')) { ?>
           | <a href="torrents.php?action=output&torrentid=<?=$TorrentID ?>" title="View torrent data">Data</a>
<?php   } ?>
<?php   if (($LoggedUser['FLTokens'] > 0) && $HasFile  && (empty($TokenTorrents[$TorrentID]) || $TokenTorrents[$TorrentID]['DoubleSeed'] < $sqltime) && ($DoubleTorrent == '0')) { ?>
           | <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=2" title="This will use 1 slot" onClick="return confirm('Are you sure you want to use a doubleseed slot here?');">Doubleseed</a>
<?php       } ?>
<?php   if (check_perms('torrents_download_override') || !$Review['Status'] || $Review['Status'] == 'Okay'  ) { ?>
           | <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">Download</a>
<?php   } ?>
<?php   if (check_perms('torrents_delete') ) {  // testing first ?>
           | <a href="torrents.php?action=dupe_check&amp;id=<?=$GroupID ?>" title="Check for exact matches in filesize">Dupe</a>
<?php   } ?>
<?php   // $on hides freeleech   
        if ($on && !($SiteOptions['SitewideFreeleechMode'] == "timed") && ($LoggedUser['FLTokens'] > 0) && $HasFile  && (empty($TokenTorrents[$TorrentID]) || $TokenTorrents[$TorrentID]['FreeLeech'] < $sqltime) && ($FreeTorrent == '0')  && ($LoggedUser['personal_freeleech'] < $sqltime) && ($LoggedUser['CanLeech'] == '1')) { ?>
           | <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=1" title="This will use 1 slot" onClick="return confirm('Are you sure you want to use a freeleech slot here?');">Freeleech</a>
<?php   } ?>
<?php   if ($CanEdit) {
            if (check_perms('torrents_edit') || time_ago($TorrentTime) < TORRENT_EDIT_TIME && $Review['Status'] != 'Okay' || $Review['Status'] != 'Okay' && $Review['Status'] || check_perms('site_edit_override_timelock')) {  ?>
               | <a href="torrents.php?action=editgroup&amp;groupid=<?=$GroupID?>">Edit</a>
<?php       }
        } ?>
           | <a href="torrents.php?action=grouplog&amp;groupid=<?=$GroupID?>">Log</a>
<?php   if (check_perms('torrents_delete') && $UserID == $LoggedUser['ID'] || $UserID == $LoggedUser['ID'] && time_ago($TorrentTime) < TORRENT_EDIT_TIME && $Review['Status'] != 'Okay' || $UserID == $LoggedUser['ID'] && $Review['Status'] != 'Okay' && $Review['Status'] || check_perms('site_edit_override_timelock')) { ?>
           | <a href="torrents.php?action=delete&amp;torrentid=<?=$TorrentID ?>" title="Remove">Remove</a>
<?php   } ?>
           | <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">Report</a>
<?php   if (check_perms('users_manage_cheats')) {
            $DB->query("SELECT TorrentID FROM torrents_watch_list WHERE TorrentID='$TorrentID'"); ?>
           <span id="wl">
<?php      if ($DB->record_count() > 0) { ?>
           | <a onclick="twatchlist_remove('<?=$GroupID?>','<?=$TorrentID?>');return false;" href="#" title="Remove this torrent from the speed records torrent watchlist">Remove from watchlist</a>
<?php      } else { ?>
           | <a onclick="twatchlist_add('<?=$GroupID?>','<?=$TorrentID?>');return false;" href="#" title="Add this torrent to the speed records torrent watchlist">Watchlist</a>
<?php      } ?>
           </span>
<?php   } ?>
           ]
    </div>
    <div  class="linkbox">
        <span style="float: right;"><a id="slide_button"  class="button toggle infoButton" onclick="Details_Toggle();return false;" title="Toggle display">Hide Info</a></span>

<?php      if (check_perms('torrents_review')) { ?>
           <span style="float: right;"><a id="slide_tools_button"  class="button toggle redButton" onclick="Tools_Toggle();return false;" title="Toggle staff tools">Staff Tools</a></span>
<?php      } ?>
           <br style="clear:both" />
    </div>
    <br/>
<?php

// For staff draw the tools section
if (check_perms('torrents_review')) {
        // get review history
        if ($Review['ID'] && is_number($Review['ID'])) { // if reviewID == null then no history
            $DB->query("SELECT r.Status, r.Time, r.ConvID,
                               IF(r.ReasonID = 0, r.Reason, rs.Description),
                               r.UserID, um.Username
                      FROM torrents_reviews AS r
                      LEFT JOIN users_main AS um ON um.ID=r.UserID
                      LEFT JOIN review_reasons AS rs ON rs.ID=r.ReasonID
                      WHERE r.GroupID = $GroupID AND r.ID != $Review[ID] ORDER BY Time");
            $NumReviews = $DB->record_count();
        } else $NumReviews = 0;
?>
    <table id="staff_tools" class="pad">
        <form id="form_reviews" action="" method="post">
                <tr class="head">
                    <td colspan="3">
                        <span style="float:left;"><strong>Review Tools</strong></span>
                   <?php  if ($NumReviews>0) { ?>
                        <span style="float:right;"><a href="#" onclick="$('.history').toggle(); this.innerHTML=(this.innerHTML=='(Hide <?=$NumReviews?> Review Logs)'?'(View <?=$NumReviews?> Review Logs)':'(Hide <?=$NumReviews?> Review Logs)'); return false;">(View <?=$NumReviews?> Review Logs)</a></span>&nbsp;
                   <?php  } ?>
                    </td>
                </tr>
<?php
    if ($NumReviews>0) { // if there is review history show it
        while (list($Stat, $StatTime, $StatConvID, $StatDescription, $StatUserID, $StatUsername) = $DB->next_record()) { ?>
                <tr class="history hidden">
                    <td width="200px"><strong>Status:</strong>&nbsp;&nbsp;<?=$Stat?"$Stat&nbsp;".get_status_icon($Stat):'Not set'?></td>
                    <td><?=$StatDescription?'<strong>Reason:</strong>&nbsp;&nbsp;'.$StatDescription:''?>
<?php
                         if ($StatConvID>0) {
                             echo '<span style="float:right;">'.($Stat=='Pending'?'(user sent fixed message) &nbsp;&nbsp;':'').'<a href="staffpm.php?action=viewconv&id='.$StatConvID.'">'.($Stat=='Pending'?'Message sent to staff':"reply sent to $Username").'</a></span>';
                         } elseif ($Stat == 'Warned') {
                             echo '<span style="float:right;">(pm sent to '.$Username.')</span>';
                         }
?>
                    </td>
                    <td width="25%"><?=$Stat?'<strong>By:</strong>&nbsp;&nbsp;'.format_username($StatUserID, $StatUsername).'&nbsp;'.time_diff($StatTime):'';?></td>
                </tr>
<?php
        }
    } // end show history
?>
                <tr>
                    <td width="200px"><strong>Current Status:</strong>&nbsp;&nbsp;<?=$Review['Status']?"$Review[Status]&nbsp;".get_status_icon($Review['Status']):'Not set'?></td>
                    <td><?=$Review['StatusDescription']?'<strong>Reason:</strong>&nbsp;&nbsp;'.$Review['StatusDescription']:''?>
<?php
                         if ($Review['ConvID']>0) {
                             echo '<span style="float:right;">'.($Review['Status']=='Pending'?'(user sent fixed message) &nbsp;&nbsp;':'').'<a href="staffpm.php?action=viewconv&id='.$Review['ConvID'].'">'.($Review['Status']=='Pending'?'Message sent to staff':"reply sent to $Username").'</a></span>';
                         } elseif ($Review['Status'] == 'Warned') {
                             echo '<span style="float:right;">(pm sent to '.$Username.')</span>';
                         }
?>
                    </td>
                    <td width="25%"><?=$Review['Status']?'<strong>By:</strong>&nbsp;&nbsp;'.format_username($Review['UserID'], $Review['Username']).'&nbsp;'.time_diff($Review['Time']):'';?></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right">
                        <input type="hidden" name="action" value="set_review_status" />
                        <input type="hidden" id="groupid" name="groupid" value="<?=$GroupID?>" />
                        <input type="hidden" id="authkey" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                        <input type="hidden" id="convid" name="convid" value="<?=$Review['ConvID']?>" />
                        <strong id="warn_insert" class="important_text" style="margin-right:20px;"></strong>
<?php               if ( !$Review['Status'] || $Review['Status'] == 'Okay' || check_perms('torrents_review_override') ) { // onsubmit="return Validate_Form_Reviews('<?=$Status ')"   ?>
                        <select id="reasonid" name="reasonid"  onchange="Select_Reason(<?=($Review['Status'] == 'Warned' || $Review['Status'] == 'Pending' || $Review['Status'] == 'Okay')?'true':'false';?>);" >
                            <option value="-1" selected="selected">none&nbsp;&nbsp;</option>
<?php
                    $DB->query("SELECT ID, Name FROM review_reasons ORDER BY Sort");
                    while (list($ReasonID, $ReasonName) = $DB->next_record()) { 
                        if ($Episode || strpos(strtolower($ReasonName), 'full season') == false) {?>

                            <option value="<?=$ReasonID?>"><?=$ReasonName?>&nbsp;&nbsp;</option>
<?php                   }
                    }    ?>
                            <option value="0">Other&nbsp;&nbsp;</option>
                        </select>
                        <input id="mark_delete_button" type="submit" name="submit" value="Mark for Deletion" disabled="disabled" title="Mark this torrent for Deletion" />

<?php               } else {   ?>

<?php               }          ?>
                    </td>
                    <td>
<?php               if ($Review['Status'] == 'Pending') {  // || $Review['Status'] == 'Warned' ?>
                        <input type="submit" name="submit" value="Accept Fix" title="Accept the fix this uploader has made" />
                        <input type="submit" name="submit" value="Reject Fix" title="Reject the fix this uploader has made" />
<?php               } else {  ?>

                        <input type="submit" name="submit" value="Mark as Okay" <?=($Review['Status']=='Okay'||($Review['Status'] == 'Warned' && !check_perms('torrents_review_override')))?'disabled="disabled" ':''?>title="Mark this torrent as Okay" />
<?php                   if ($Review['Status'] == 'Warned' && check_perms('torrents_review_override') ) {  ?>
                        <br/><strong class="important_text" style="margin-left:10px;">override warned status?</strong>
<?php                   }       ?>
<?php               }       ?>
                    </td>
                </tr>
                <tr id="review_message" class="hidden">
                    <td colspan="2">
                        <div>
                            <span class="quote_label">
                                <strong>preview of PM that will automatically be sent to <?=format_username($UserID, $Username)?></strong>
                            </span>
                            <blockquote class="bbcode">
                                <span id="message_insert"></span>
                                <textarea id="reason_other" name="reason" class="hidden medium" style="vertical-align: middle;" rows="1" title="The reason entered here is also displayed in the warning notice, ie. keep it short and sweet"></textarea>
                                <br/><br/>add to message:
                                <textarea id="msg_extra" name="msg_extra" class="medium" style="vertical-align: middle;" rows="1" title="Whatever you enter here is added to the message sent to the user"></textarea>
<?php
                                echo $Text->full_format(get_warning_message(false, true), true);
?>
                            </blockquote>
                        </div>
                    </td>
                    <td></td>
                </tr>
        </form>
    </table>
    <script type="text/javascript">
         addDOMLoadEvent(Load_Tools_Cookie);
    </script>
<?php
} // end draw staff tools
?>

 <div id="details_top">
    <div class="sidebar" style="float: right;">
            <a id="tags"></a>
            <div class="head">
                <strong>Tags</strong>
                <span style="float:right;margin-left:5px;"><a href="#" id="tagtoggle" onclick="TagBox_Toggle(); return false;">(Hide)</a></span>
            </div>
            <div id="tag_container" class="box box_tags">
                <div class="tag_header">
                    <div>
                        <input type="hidden" id="sort_groupid" value="<?=$GroupID?>" />
                        <span id="sort_az" class="button_sort"><a onclick="Resort_Tags(<?="$GroupID, 'az'"?>);" title="change sort order of tags to total az">az</a></span>
						<span id="sort_uses" class="button_sort sort_select"><a onclick="Resort_Tags(<?="$GroupID, 'uses'"?>);" title="change sort order of tags to total uses">uses</a></span>
                        <?php /*<span id="sort_score" class="button_sort"><a onclick="Resort_Tags(<?="$GroupID, 'score'"?>);" title="change sort order of tags to total score">score</a></span>*/ ?>
                    </div>
                    <br />
                </div>
                <div id="torrent_tags" class="tag_inner">
<?php

if (count($Tags) == 0) {
?>
            Please add a tag for this torrent!
<?php
}
?>
                </div>
<?php       if (empty($LoggedUser['DisableTagging']) && (check_perms('site_add_tag') || $IsUploader)) { ?>
            <div class="tag_add">
    <div id="messagebar" class="messagebar hidden"></div>
                <form id="form_addtag" action="" method="post" onsubmit="return false;">
                    <input type="hidden" name="action" value="add_tag" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input type="hidden" name="groupid" value="<?=$GroupID?>" />
                    <input type="hidden" name="tagsort" value="<?=$tagsort?>" />
                    <input type="text" id="tagname" name="tagname" size="15" onkeydown="if (event.keyCode == 13) { Add_Tag(); return false; }" />
                    <input type="button" value="+" onclick="Add_Tag(); return false;" />
                </form>
            </div>
<?php       }       ?>
            </div>
    </div>

    <div class="middle_column">
<?php  if ($Image!="") { ?>
          <div class="head">
            <strong>Banner</strong>
            <span style="float:right;"><a href="#" id="covertoggle" onclick="Cover_Toggle(); return false;">(Hide)</a></span>
          </div>
          <div id="coverimage" class="box box_albumart">
<?php     if ($Image!="") {
              if (check_perms('site_proxy_images')) {
                 $Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
              } ?>
<?php       if($TVMAZE) { ?>                          
           <a href="/torrents.php?action=show&showid=<?=$TVMAZE?>">
<?php       } else { ?>
           <a href="<?=get_search($SearchTitle)?>">
<?php       } ?> 
            <img class="details_banner" src="<?=$Image?>" title="Go to <?=$SearchTitle?>" />
           </a>
<?php     }
       } else { ?>
          <div class="head">
            <strong>Banner</strong>
            <span style="float:right;"><a href="#" id="covertoggle" onclick="Cover_Toggle(); return false;">(Hide)</a></span>
          </div>
          <div id="coverimage" class="box box_albumart">        
<?php       if($TVMAZE) { ?>                          
           <a href="/torrents.php?action=show&showid=<?=$TVMAZE?>">
<?php       } else { ?>
           <a href="<?=get_search($SearchTitle)?>">
<?php       } ?> 
            <img class="details_banner" src="static/common/noartwork/noimage.png" title="Go to <?=$SearchTitle?>" />
           </a>
<?php   } ?>
          </div>
        <div class="head">Torrent Info</div>
        <table class="torrent_table">
            <tr class="colhead">
                <td width="80%">Name</td>
                <td>Size</td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/snatched.svg" alt="↺" title="Snatches" /></td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/seeders.svg" alt="∧" title="Seeders" /></td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/leechers.svg" alt="∨" title="Leechers" /></td>
            </tr>
<?php

function filelist($Str)
{
    return "</td><td>".get_size($Str[1])."</td></tr>";
}

$EditionID = 0;

        // The report array has been moved up above the display name so "reported" could be added to the title.
        if (count($Reports) > 0) {
        $Reported = true;
        include(SERVER_ROOT.'/sections/reportsv2/array.php');
        $ReportInfo = '<table class="reported"><tr class="smallhead"><td>This torrent has '.count($Reports)." active ".(count($Reports) > 1 ?'reports' : 'report').":</td></tr>";

        foreach ($Reports as $Report) {
            list($ReportID, $ReporterID, $ReportType, $ReportReason, $ReportedTime) = $Report;

            $Reporter = user_info($ReporterID);
            $ReporterName = $Reporter['Username'];

            if (array_key_exists($ReportType, $Types)) {
                $ReportType = $Types[$ReportType];
            } else {
                //There was a type but it wasn't an option!
                $ReportType = $Types['other'];
            }
            $ReportInfo .= "<tr><td>".(check_perms('admin_reports') ? "<a href='user.php?id=$ReporterID'>$ReporterName</a> <a href='reportsv2.php?view=report&amp;id=$ReportID'>reported it</a> " : "Someone reported it ").time_diff($ReportedTime,2,true,true)." for the reason '".$ReportType['title']."':";
            $ReportInfo .= "<blockquote>".$Text->full_format($ReportReason)."</blockquote></td></tr>";
        }
        $ReportInfo .= "</table>";
    }


// count filetypes
$num = preg_match_all('/\.([^\.]*)\{\{\{/ism', $FileList, $Extensions);

$TempFileTypes = array('video'=>0,'image'=>0,'compressed'=>0, 'other'=>0);
foreach ($Extensions[1] as $ext) { // filetypes arrays defined in config
    $ext = strtolower($ext);
    if (in_array($ext, $Video_FileTypes))
        $TempFileTypes['video']+=1;
    elseif (in_array($ext, $Image_FileTypes))
        $TempFileTypes['image']+=1;
    elseif (in_array($ext, $Sub_FileTypes))
        $TempFileTypes['subtitle']+=1;
    else
        $TempFileTypes['other']+=1;
}
$FileTypes=array();
foreach ($TempFileTypes as $image_ext=>$count) {
    if ($count>0) $FileTypes[] = "$count <img src=\"static/common/symbols/$image_ext.svg\" alt=\"$image_ext\" title=\"$image_ext files\" /> ";
}
  $FileTypes = "<span class=\"grey\" style=\"float:left;\">" . implode(' ', $FileTypes)."</span>";
    $FileList = str_replace('|||','<tr><td>',display_str($FileList));
    $FileList = preg_replace_callback('/\{\{\{([^\{]*)\}\}\}/i','filelist',$FileList);
    $FileList = '<table style="overflow-x:auto;"><tr class="smallhead"><td colspan="2">'.(empty($FilePath) ? '/' : '/'.$FilePath.'/' ).'</td></tr><tr class="rowa"><td><strong><div style="float: left; display: block;">File Name'.(check_perms('users_mod') ? ' [<a href="torrents.php?action=regen_filelist&amp;torrentid='.$TorrentID.'">Regenerate</a>]' : '').'</div></strong></td><td><strong>Size</strong></td></tr><tr><td>'.$FileList."</td></tr></table>";

    $TorrentUploader = $Username; // Save this for "Uploaded by:" below

    // similar to torrent_info()
    $ExtraInfo = $GroupName;
  $AddExtra = ' / ';

    if ($DoubleTorrent == '1') { $ExtraInfo.=$AddExtra.'<strong>Double Seed!</strong>'; $AddExtra=' / '; }
    if (!empty($TokenTorrents[$TorrentID]) && $TokenTorrents[$TorrentID]['Type'] == 'seed') { $ExtraInfo.=$AddExtra.'<strong>Personal Doubleseed!</strong>'; $AddExtra=' / '; }
    if ($Reported) { $ExtraInfo.=$AddExtra.'<strong>Reported</strong>'; $AddExtra=' / '; }
    if (!empty($BadTags)) { $ExtraInfo.=$AddExtra.'<strong>Bad Tags</strong>'; $AddExtra=' / '; }
    if (!empty($BadFolders)) { $ExtraInfo.=$AddExtra.'<strong>Bad Folders</strong>'; $AddExtra=' / '; }
    if (!empty($BadFiles)) { $ExtraInfo.=$AddExtra.'<strong>Bad File Names</strong>'; $AddExtra=' / '; }

?>

            <tr class="groupid_<?=$GroupID?> edition_<?=$EditionID?> group_torrent" style="font-weight: normal;" id="torrent<?=$TorrentID?>">
                <td rowspan="2" style="border-bottom:none;border-left:none;">
                    <strong><?=$ExtraInfo; ?></strong>
                </td>
                <td class="nobr"><?=get_size($Size)?></td>
                <td><?=number_format($Snatched)?></td>
                <td><?=number_format($Seeders)?></td>
                <td><?=number_format($Leechers)?></td>
            </tr>
            <tr>

                <td class="filesvgs filetypes" colspan="4"><?=$FileTypes;//$Text->full_format($FileTypes)?></td>
            </tr>
            <tr>
                <td colspan="6" class="left">
                    <em>&nbsp;&nbsp;Uploaded by   <?=torrent_username($UserID, $TorrentUploader, $IsAnon)?> <?=time_diff($TorrentTime);?> </em>
                </td>
            </tr>
<?php


$FilledRequests = get_group_requests_filled($GroupID);
if (count($FilledRequests) > 0) {
        $row = $row =='a'?'b':'a';
?>
        <tr class="row<?=$row?>">
            <td colspan="6" >
                <em>filled request<?=count($FilledRequests)>1?'s':''?></em>
            </td>
        </tr>
<?php
    foreach ($FilledRequests as $Request) {
        $RequestVotes = get_votes_array($Request['ID']);
        $row = $row =='a'?'b':'a';
?>
            <tr class="requestrows row<?=$row?>">
                <td colspan="2" >
                    <a href="requests.php?action=view&id=<?=$Request['ID']?>"><?=$Request['Title']?></a>
                </td>
                <td colspan="4" >
                    <span style="float:right"><em>for <?=$RequestVotes['TotalBounty']?> Cubits</em></span>
                </td>
            </tr>
<?php 	}
}
?>
            <tr class="groupid_<?=$GroupID?> edition_<?=$EditionID?> torrentdetails pad" id="torrent_<?=$TorrentID; ?>">
                <td colspan="6" style="border-top:none;">

<?php
    if ($Seeders < 5 && $LastActive != '0000-00-00 00:00:00') { ?>

<?php
                        $lasttimesinceactive =  time() - strtotime($LastActive);
                        $ReseedStr='';
                        if ($Seeders == 0 || $lasttimesinceactive >= 86400) { // 24 hrs
                                $ReseedStr = "<strong>Last active: ".time_diff($LastActive)."</strong>";
                        } elseif ($lasttimesinceactive >= 3600 * 3) {
                                $ReseedStr = "Last active: ".time_diff($LastActive);
                        }
                        if (time()-strtotime($LastReseedRequest)< 259200 ) {  //= 3 days  | 432000= 5 days
                                $ReseedStr .= " <em>re-seed was requested (".time_diff($LastReseedRequest).")</em> ";
                        } elseif ( ($Snatched > 2 || $Snatched > $Seeders) && $lasttimesinceactive >= 86400 ) {  //
                                $ReseedStr .= ' <a href="torrents.php?action=reseed&amp;torrentid='.$TorrentID.'&amp;groupid='.$GroupID.'" title="request a reseed from the '.$Snatched.' users who have snatched this torrent"> [Request re-seed] </a> ';
                        }
                        if($ReseedStr) echo '<blockquote  style="text-align: center;">'.$ReseedStr.'</blockquote>';
?>

<?php  } ?>

<?php  if (check_perms('site_moderate_requests')) { ?>
                    <div class="linkbox">
                        <a href="torrents.php?action=masspm&amp;id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>">[Mass PM Snatchers]</a>
                    </div>
<?php  } ?>
                    <div class="linkbox">
<?php  if (check_perms('site_view_torrent_peerlist')) { ?>
                        <a href="#" onclick="show_peers('<?=$TorrentID?>', 0);return false;">(View Peerlist)</a>
<?php  } ?>
<?php  if (check_perms('site_view_torrent_snatchlist')) { ?>
                        <a href="#" onclick="show_downloads('<?=$TorrentID?>', 0);return false;">(View Downloadlist)</a>
                        <a href="#" onclick="show_snatches('<?=$TorrentID?>', 0);return false;">(View Snatchlist)</a>
<?php  } ?>
                        <a href="#" onclick="show_files('<?=$TorrentID?>');return false;">(View Filelist)</a>
<?php  if ($Reported) { ?>
                        <a href="#" onclick="show_reported('<?=$TorrentID?>');return false;">(View Report Info)</a>
<?php  } ?>
                    </div>
                    <div id="peers_<?=$TorrentID?>" class="hidden"></div>
                    <div id="downloads_<?=$TorrentID?>" class="hidden"></div>
                    <div id="snatches_<?=$TorrentID?>" class="hidden"></div>
                    <div id="files_<?=$TorrentID?>" class="hidden"><?=$FileList?></div>
<?php   if ($Reported) { ?>
                    <div id="reported_<?=$TorrentID?>"><?=$ReportInfo?></div>
<?php  } ?>
                </td>
            </tr>
        </table>
<?php
$Requests = get_group_requests($GroupID);
if (count($Requests) > 0) {
    $i = 0;
?>
        <div class="head">
            <span style="font-weight: bold;">Requests (<?=count($Requests)?>)</span>
            <span style="float:right;"><a href="#" onClick="$('#requests').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(Show)':'(Hide)'); return false;">(Show)</a></span>
        </div>
        <div class="box">
            <table id="requests" class="hidden">
                <tr class="head">
                    <td>Request name</td>
                    <td>Votes</td>
                    <td>Bounty</td>
                </tr>
<?php 	foreach ($Requests as $Request) {
        $RequestVotes = get_votes_array($Request['ID']);
?>
                <tr class="requestrows <?=(++$i%2?'rowa':'rowb')?>">
                    <td><a href="requests.php?action=view&id=<?=$Request['ID']?>"><?=$Request['Title']?></a></td>
                    <td>
                        <form id="form_<?=$Request['ID']?>">
                            <span id="vote_count_<?=$Request['ID']?>"><?=count($RequestVotes['Voters'])?></span>
                            <input type="hidden" id="requestid_<?=$Request['ID']?>" name="requestid" value="<?=$Request['ID']?>" />
                            <input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                            &nbsp;&nbsp; <a href="javascript:Vote(0, <?=$Request['ID']?>)"><strong>(+)</strong></a>
                        </form>
                    </td>
                    <td><?=get_size($RequestVotes['TotalBounty'])?></td>
                </tr>
<?php 	} ?>
            </table>
        </div>
<?php
}
$Collages = $Cache->get_value('torrent_collages_'.$GroupID);
if (!is_array($Collages)) {
    $DB->query("SELECT c.Name, c.NumTorrents, c.ID FROM collages AS c JOIN collages_torrents AS ct ON ct.CollageID=c.ID WHERE ct.GroupID='$GroupID' AND Deleted='0' AND CategoryID!='0'");
    $Collages = $DB->to_array();
    $Cache->cache_value('torrent_collages_'.$GroupID, $Collages, 3600*6);
}
if (count($Collages)>0) {
?>
            <div class="head">Collages</div>
        <table id="collages" style='margin-bottom:1px'>
            <tr class="colhead">
                <td width="85%">Collage name</td>
                <td># torrents</td>
            </tr>
<?php 	foreach ($Collages as $Collage) {
        list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
            <tr>
                <td>
                <!-- Display toggle unit -->
                 <a href='#' onclick='javascript:toggleDisplay(document.getElementById("collagelist")); return false;' title='View'><?=$CollageName?> 
                 &raquo;</a></td>
                <!-- End of Display toggle unit -->
                <td><?=$CollageTorrents?></td>
            </tr>
<?php 	} ?>
        </table>
<?php
}

// Build the data for the collage and the torrent list
    $DB->query("SELECT 
            ct.GroupID,
            t.FilePath,
            tg.Image,
            tg.NewCategoryID,
            um.ID,
            um.Username
            FROM collages_torrents AS ct
            INNER JOIN torrents AS t ON t.ID=ct.GroupID
            JOIN torrents_group AS tg ON tg.ID=ct.GroupID
            LEFT JOIN users_main AS um ON um.ID=ct.UserID
            WHERE ct.CollageID='$CollageID'
            ORDER BY ct.Sort");

    $GroupIDs = $DB->collect('GroupID');
    $CollageDataList=$DB->to_array('GroupID', MYSQLI_ASSOC);

    if (count($GroupIDs)>0) {
        $TorrentList = get_groups($GroupIDs);
        $TorrentList = $TorrentList['matches'];
   } else {
    $TorrentList = array();
}

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = 0;
$NumGroupsByUser = 0;
$Tags = array();
$Users = array();
$Number = 0;

$Bookmarks = all_bookmarks('torrent');

foreach ($TorrentList as $Group_ID=>$Group) {
    list($GroupID1, $GroupName, $TagList, $Torrents) = array_values($Group);
    list($GroupID2, $FilePath, $Image, $NewCategoryID, $UserID, $Username) = array_values($CollageDataList[$Group_ID]);

    $Review = get_last_review($GroupID1);
        // Handle stats and stuff
    $Number++;
    $NumGroups++;
    if ($UserID == $LoggedUser['ID']) {
        $NumGroupsByUser++;
    }

    if ($Username) {
        if (!isset($Users[$UserID])) {
            $Users[$UserID] = array('name'=>$Username, 'count'=>1);
        } else {
            $Users[$UserID]['count']++;
        }
    }

    $TagList = explode(' ',str_replace('_','.',$TagList));

    $TorrentTags = array();
    $numtags=0;
    foreach ($TagList as $Tag) {
        if ($numtags++>=$LoggedUser['MaxTags'])  break;
        if (!isset($Tags[$Tag])) {
            $Tags[$Tag] = array('name'=>$Tag, 'count'=>1);
        } else {
            $Tags[$Tag]['count']++;
        }
        $TorrentTags[]='<a href="torrents.php?taglist='.$Tag.'">'.$Tag.'</a>';
    }
    $PrimaryTag = $TagList[0];
    $TorrentTags = implode(', ', $TorrentTags);

    // Start an output buffer, so we can store this output in $TorrentTable
    ob_start();

        list($TorrentID, $Torrent) = each($Torrents);

        $DisplayName = $GroupName;

        if ($Torrent['ReportCount'] > 0) {
            $Title = "This torrent has ".$Torrent['ReportCount']." active ".($Torrent['ReportCount'] > 1 ?'reports' : 'report');
            $DisplayName .= ' /<span class="reported" title="'.$Title.'"> Reported</span>';
        }
        $Icons = torrent_icons($Torrent, $TorrentID, $Review, in_array($GroupID1, $Bookmarks));

        $row = $row == 'a' ? 'b' : 'a';
        $IsMarkedForDeletion = $Review['Status'] == 'Warned' || $Review['Status'] == 'Pending';
?>
<tr class="torrent <?=($IsMarkedForDeletion?'redbar':"row$row")?>" id="group_<?=$GroupID1?>">
        <td style="position:relative; width:80%;">
                <?php
                if ($LoggedUser['HideFloat']) {?>
                    <span style="bottom:0; right:0; position:absolute; margin:0px; padding:0px;"><?=$Icons?></span><a href="torrents.php?id=<?=$GroupID1?>"><?=$DisplayName?></a>
<?php              } else {
                    $Overlay = get_overlay_html($GroupName, anon_username($Torrent['Username'], $Torrent['Anonymous']), $Image, $Torrent['Seeders'], $Torrent['Leechers'], 
                    $Torrent['Size'], $Torrent['Snatched'], $FilePath);
                    ?>
                    <script>
                        var overlay<?=$GroupID1?> = <?=json_encode($Overlay)?>
                    </script>
                    <a href="torrents.php?id=<?=$GroupID1?>" onmouseover="return overlib(overlay<?=$GroupID1?>, FULLHTML);" onmouseout="return nd();"><?=$DisplayName?></a>
<?php              }  ?>
                <?php if ($LoggedUser['HideTagsInLists'] !== 1) {?>
                <div class="tags">
                  <div style="margin:0px; padding:0px; max-width:80%;"><?=$TorrentTags?></div>
                </div>  
                <?php } ?>
                 <div style="bottom:0; right:0; position:absolute; margin:0px; padding:2px;"><?=$Icons?></div>                 
        </td>
        <td class="nobr" style="text-align:center; vertical-align:middle;"><?=get_size($Torrent['Size'])?></td>
        <td style="text-align:center; vertical-align:middle;"><?=number_format($Torrent['Snatched'])?></td>
        <td style="text-align:center; vertical-align:middle;"<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
        <td style="text-align:center; vertical-align:middle;"><?=number_format($Torrent['Leechers'])?></td>
</tr>
<?php
    $TorrentTable.=ob_get_clean();

    ob_start();

    $DisplayName = $GroupName;

?>
        <li class="image_group_<?=$GroupID1?>">
            <a href="torrents.php?id=<?=$GroupID1?>">
<?php	if ($Image) {
        if (check_perms('site_proxy_images')) {
            $Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
        }
?>
                <img src="<?=$Image?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?>"  />
<?php	} else { ?>
                <div class="noimagepad"><div class="box noimage" title="<?=$DisplayName?>" ><?=$DisplayName?></div></div>
<?php	} ?>
            </a>
        </li>
<?php
    $Collage[]=ob_get_clean();
}
?>
  <!-- Display toggle div -->
   <div id='collagelist' name='collagelist' style='display:none;'>
        <table class="torrent_table" id="discog_table">
            <tr class="colhead">
                <td><strong>Name</strong></td>
                <td style="text-align:center; vertical-align:middle;">Size</td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/snatched.svg" alt="↺" title="Snatches" /></td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/seeders.svg" alt="∧" title="Seeders" /></td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/leechers.svg" alt="∨" title="Leechers" /></td>
            </tr>
        <?=$TorrentTable?>
        </table>
   </div>        
<?php
//---------------------------------------------------------

$PersonalCollages = $Cache->get_value('torrent_collages_personal_'.$GroupID);
if (!is_array($PersonalCollages)) {
    $DB->query("SELECT c.Name, c.NumTorrents, c.ID FROM collages AS c JOIN collages_torrents AS ct ON ct.CollageID=c.ID WHERE ct.GroupID='$GroupID' AND Deleted='0' AND CategoryID='0'");
    $PersonalCollages = $DB->to_array(false, MYSQL_NUM);
    $Cache->cache_value('torrent_collages_personal_'.$GroupID, $PersonalCollages, 3600*6);
}

if (count($PersonalCollages)>0) {
    if (count($PersonalCollages) > MAX_PERS_COLLAGES) {
        // Pick 5 at random
        $Range = range(0,count($PersonalCollages) - 1);
        shuffle($Range);
        $Indices = array_slice($Range, 0, MAX_PERS_COLLAGES);
        $SeeAll = ' <a href="#" onClick="$(\'.personal_rows\').toggle(); return false;">(See all)</a>';
    } else {
        $Indices = range(0, count($PersonalCollages)-1);
        $SeeAll = '';
    }
?>
            <div class="head">Personal Collages</div>
        <table id="personal_collages">
            <tr class="colhead">
                <td width="85%">This torrent is in <?=count($PersonalCollages)?> personal collage<?=((count($PersonalCollages)>1)?'s':'')?><?=$SeeAll?></td>
                <td># torrents</td>
            </tr>
<?php 	foreach ($Indices as $i) {
        list($CollageName, $CollageTorrents, $CollageID) = $PersonalCollages[$i];
        unset($PersonalCollages[$i]);
?>
            <tr>
                <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
                <td><?=$CollageTorrents?></td>
            </tr>
<?php 	}
    foreach ($PersonalCollages as $Collage) {
        list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
            <tr class="personal_rows hidden">
                <td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
                <td><?=$CollageTorrents?></td>
            </tr>
<?php 	} ?>
        </table>
<?php
}

?>

        </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>

    <div class="main_column">
<?php if($Mediainfo) { ?>
      <div class="head">
          <strong>Media Info</strong>
          <span style="float:right;"><a href="#" id="mediainfotoggle" onclick="MediaInfo_Toggle();  return false;">(Hide)</a></span>
      </div>
      <div class="box">
          <div id="mediainfobox" class="body">
           <div class="showinfobox">
            <?=$Text->full_format($Mediainfo)?>
           </div> 
          </div>
      </div>
<?php } ?>
    </div>

<div class="main_column">

<?php if(($Synopsis && $Season && $Episode) || ($Synopsis && !$Season && !$Episode)) { // episode or talk show

      if(!$PosterURL) $PosterURL = '/static/common/images/no-img-episode.png'; // no Poster, use default
?>
        <div class="head">
                <strong>Episode Info</strong>
                <span style="float:right;"><a href="#" id="episodeinfotoggle" onclick="EpisodeInfo_Toggle(); return false;">(Hide)</a></span>
        </div>
        <div class="box">
            <div id="episodeinfobox" class="body">
            <table class="showinfo"> 
             <tr>
              <td style="width:10%;">
                <img src="<?=$PosterURL?>" alt="" class="screencap" onclick="lightbox.init(this,330);">
              </td>
              <td style="padding:10px;">
             <?php if($EpisodeTitle) { ?> 
                <b><?=$EpisodeTitle?></b><br /><br />
             <?php } ?>   
                <?=$Synopsis?><br /><br />
             <?php if($AirDate != '0000-00-00 00:00:00') { ?>
             <?php   if($TMDb && !$Season && !$Episode) { ?>
                <b>Release date:</b> <?=date("F j, Y",strtotime($AirDate))?><br />
             <?php   }else { ?>
                <b>Air date:</b> <?=date("F j, Y",strtotime($AirDate))?><br />
             <?php   } ?>   
             <?php } ?>   
                <?php if($Season) {?><b>Number:</b> Season <?=$Season?><?php } if($Episode) {?>, Episode <?=$Episode?> <?php }?><br />
              </td>
             </tr>
            </table>
            </div>
        </div>
<?php } else ?>
<?php if($Synopsis && $Season && !$Episode) {  // season  

      if(!$PosterURL) $PosterURL = "/static/common/images/no-img-poster.png"; // no Poster, use default
?>
      <div class="head">
          <strong>Show Info</strong>
          <span style="float:right;"><a href="#" id="seasoninfotoggle" onclick="ShowInfo_Toggle(); return false;">(Hide)</a></span>
      </div>
      <div class="box">
          <div id="seasoninfobox" class="body">
            <table class="showinfo">
             <tr>
              <td style="width:200px; height:295px; text-align: center;">
               <?php if($CollageID) { // on click go to collage ?>
               <a href="/collages.php?id=<?=$CollageID?>">
                <img src="<?=$PosterURL?>" alt="" class="poster" style="cursor: pointer" title="Go to Collage" />
               </a> 
               <?php }else{ ?>
                <img src="<?=$PosterURL?>" alt="" class="poster" />
               <?php }?>
              </td>
              <td style="padding:10px;">
                <?=$Text->full_format($Synopsis)?>
              </td>
             </tr>
            </table>
          </div>
      </div>
<?php } ?>

<?php if($EpisodeGuide && $Season && !$Episode) { ?>
      <div class="head">
          <strong>Episode List</strong>
          <span style="float:right;"><a href="#" id="episodeinfotoggle" onclick="EpisodeInfo_Toggle(); return false;">(Hide)</a></span>
      </div>
      <div class="box">
          <div id="episodeinfobox" class="body">
            <div class="showinfobox">
               <?=$Text->full_format($EpisodeGuide)?>
            </div>          
          </div>
      </div>
<?php } ?>
   
    <div class="main_column">
<?php if($Screens) { ?>
      <div class="head">
          <strong>Screens</strong>
          <span style="float:right;"><a href="#" id="screenstoggle" onclick="Screens_Toggle(); return false;">(Hide)</a></span>
      </div>
      <div class="box">
          <div id="screensbox" class="body">
           <div class="showinfo">
            <?=$Text->full_format($Screens)?>
           </div> 
          </div>
      </div>
<?php } ?>
    </div>

<?php /*    <div class="main_column">
<?php if($Trailer) { ?>
      <div class="head">
          <strong>Trailer</strong>
          <span style="float:right;"><a href="#" id="trailertoggle" onclick="Trailer_Toggle(); return false;">(Hide)</a></span>
      </div>
      <div class="box">
          <div id="trailerbox" class="body">
           <div class="showinfo">
            <?=$Text->full_format($Trailer)?>
           </div>
          </div>
      </div>
<?php } ?>
    </div>*/ ?>

    <div class="main_column">
<?php if ($Body){ ?>          
        <div class="head">
                <strong>Description</strong>
                <span style="float:right;"><a href="#" id="desctoggle" onclick="Desc_Toggle(); return false;">(Hide)</a></span>
        </div>
        <div class="box">
            <div id="descbox" class="body">
<?php
                        $PermissionsInfo = get_permissions_for_user($UserID);
                        if ($Body!='') {
                            $Body = $Text->full_format($Body, isset($PermissionsInfo['site_advanced_tags']) &&  $PermissionsInfo['site_advanced_tags'] );
                            echo $Body;
                        }// else
                         //   echo "There is no information on this torrent.";
  }                       
?>
<?php
        if (!$IsAnon) {

            $UserInfo = user_info($UserID);
            $TorrentSig = $UserInfo['TorrentSignature'];
            if ($TorrentSig!='') {
?>
                <div id="torrentsigbox" style="max-height: <?=TORRENT_SIG_MAX_HEIGHT?>px">
<?php
                            $TorrentSig = $Text->full_format($TorrentSig, isset($PermissionsInfo['site_advanced_tags']) &&  $PermissionsInfo['site_advanced_tags'] );
                            echo $TorrentSig;
?>
                </div>
<?php
            }
        }
?>
            </div>
        </div>

        <div class="head">Thanks</div>
        <div class="box pad center">
<?php

    $Thanks = $Cache->get_value('torrent_thanks_'.$GroupID);
    if ($Thanks === false) {
          $Thanks = [];
          $DB->query("SELECT Thanks FROM torrents WHERE GroupID = '$GroupID'");
          list($Thanks['names']) = $DB->next_record();
          $Thanks['count'] = str_word_count($Thanks['names']);
          $Cache->cache_value('torrent_thanks_'.$GroupID, $Thanks);
    }
    if (!$IsUploader && (!$Thanks || strpos($Thanks['names'], $LoggedUser['Username'])===false )) {
?>
                <form action="torrents.php" method="post" id="thanksform">
                    <input type="hidden" name="action" value="thank" />
                    <input type="hidden" name="groupid" value="<?=$GroupID?>" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input id="thanksbutton" type="button" onclick="Say_Thanks()" value="Thank the uploader!" class=" center" style="font-weight:bold;font-size:larger;" />
               </form>
<?php   }   ?>
                <div  id="thanksdiv" class="pad<?php if(!$Thanks['names'])echo' hidden';?>" style="text-align:left">
                    <p><strong id="thanksdigest">The following <?=$Thanks['count']?> people said thanks!</strong> &nbsp;<span id="thankstext"><?=$Thanks['names']?></span></p>
                </div>
        </div>
<?php

$Results = get_num_comments($GroupID);

if (isset($_GET['postid']) && is_number($_GET['postid']) && $Results > TORRENT_COMMENTS_PER_PAGE) {
    $DB->query("SELECT COUNT(ID) FROM torrents_comments WHERE GroupID = $GroupID AND ID <= $_GET[postid]");
    list($PostNum) = $DB->next_record();
    list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$PostNum);
} else {
    list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);
}

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID);
if ($Catalogue === false) {
    $DB->query("SELECT
            c.ID,
            c.AuthorID,
            c.AddedTime,
            c.Body,
            c.EditedUserID,
            c.EditedTime,
            u.Username
            FROM torrents_comments as c
            LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
                  LEFT JOIN users_main AS a ON a.ID = c.AuthorID
            WHERE c.GroupID = '$GroupID'
            ORDER BY c.ID
            LIMIT $CatalogueLimit");
    $Catalogue = $DB->to_array(false,MYSQLI_ASSOC, array('Badges'));
    $Cache->cache_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)%THREAD_CATALOGUE),TORRENT_COMMENTS_PER_PAGE,true);
?>
    <div class="linkbox"><a name="comments"></a>
<?php
$Pages=get_pages($Page,$Results,TORRENT_COMMENTS_PER_PAGE,9,'#comments');
echo $Pages;
?>
    </div>
<?php

//---------- Begin printing
foreach ($Thread as $Key => $Post) {
    list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
    list($AuthorID, $Username, $PermissionID, $Paranoia, $Donor, $Warned, $Avatar, $Enabled, $UserTitle,,,$Signature,,$GroupPermissionID) = array_values(user_info($AuthorID));
      $AuthorPermissions = get_permissions($PermissionID);
      list($ClassLevel,$PermissionValues,$MaxSigLength,$MaxAvatarWidth,$MaxAvatarHeight)=array_values($AuthorPermissions);
?>
<table class="forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>" id="post<?=$PostID?>">
    <tr class="smallhead">
        <td colspan="2">
            <span style="float:left;"><a class="post_id" href='torrents.php?id=<?=$GroupID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>'>#<?=$PostID?></a>
                <?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled, $PermissionID, $UserTitle, true, $GroupPermissionID, true)?> <?=time_diff($AddedTime)?>
                - <a href="#quickpost" onclick="Quote('comments','<?=$PostID?>','t<?=$GroupID?>','<?=$Username?>');">[Quote]</a>
<?php if (can_edit_comment($AuthorID, $EditedUserID, $AddedTime, $EditedTime)) { ?>
                        - <a href="#post<?=$PostID?>" onclick="Edit_Form('comments','<?=$PostID?>','<?=$Key?>');">[Edit]</a><?php }
    if (check_perms('site_admin_forums')) { ?>
                        - <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a> <?php } ?>
            </span>
            <span id="bar<?=$PostID?>" style="float:right;">

                <a href="reports.php?action=report&amp;type=torrents_comment&amp;id=<?=$PostID?>">[Report]</a>
                &nbsp;
                <a href="#">&uarr;</a>
            </span>
        </td>
    </tr>
    <tr>
<?php  if (empty($HeavyInfo['DisableAvatars'])) {?>
        <td class="avatar" valign="top" rowspan="2">
    <?php  if ($Avatar) { ?>
            <img src="<?=$Avatar?>" class="avatar" style="<?=get_avatar_css($MaxAvatarWidth, $MaxAvatarHeight)?>" alt="<?=$Username ?>'s avatar" />
    <?php  } else { ?>
            <img src="<?=STATIC_SERVER?>common/avatars/default.svg" class="avatar" style="<?=get_avatar_css()?>" alt="Default avatar" />
    <?php
         }
        $UserBadges = get_user_badges($AuthorID);
        if ( !empty($UserBadges) ) {  ?>
               <div class="badges">
<?php                   print_badges_array($UserBadges, $AuthorID); ?>
               </div>
<?php       }      ?>
        </td>
<?php
}
$AllowTags= get_permissions_advtags($AuthorID, false, $AuthorPermissions);
?>
        <td class="postbody" valign="top">
            <div id="content<?=$PostID?>" class="post_container">
                      <div class="post_content"><?=$Text->full_format($Body, $AllowTags) ?> </div>
<?php  if ($EditedUserID) { ?>
                        <div class="post_footer">
<?php 	if (check_perms('site_moderate_forums')) { ?>
                <a href="#content<?=$PostID?>" onclick="LoadEdit('torrents', <?=$PostID?>, 1); return false;">&laquo;</a>
<?php  	} ?>
                        <span class="editedby">Last edited by
                <?=format_username($EditedUserID, $EditedUsername) ?> <?=time_diff($EditedTime,2,true,true)?>
                        </span>
                        </div>
        <?php  }   ?>
            </div>
        </td>
    </tr>
<?php
      if ( empty($HeavyInfo['DisableSignatures']) && ($MaxSigLength > 0) && !empty($Signature) ) { //post_footer

            echo '
      <tr>
            <td class="sig"><div id="sig" style="max-height: '.SIG_MAX_HEIGHT. 'px"><div>' . $Text->full_format($Signature, $AllowTags) . '</div></div></td>
      </tr>';
           }
?>
</table>
<?php 	} ?>
        <div class="linkbox">
        <?=$Pages?>
        </div>
<?php
if (!$LoggedUser['DisablePosting']) { ?>
            <br />
            <div class="messagecontainer" id="container"><div id="message" class="hidden center messagebar"></div></div>
                <table id="quickreplypreview" class="forum_post box vertical_margin hidden" style="text-align:left;">
                    <tr class="smallhead">
                        <td colspan="2">
                            <span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
                                by <strong><?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'], $LoggedUser['PermissionID'], false, true)?></strong>
                            Just now
                            </span>
                            <span id="barpreview" style="float:right;">
                                <a href="#quickreplypreview">[Report]</a>
                                <a href="#">&uarr;</a>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="avatar" valign="top">
                              <?php  if (!empty($LoggedUser['Avatar'])) {  ?>
                                            <img src="<?=$LoggedUser['Avatar']?>" class="avatar" style="<?=get_avatar_css($LoggedUser['MaxAvatarWidth'], $LoggedUser['MaxAvatarHeight'])?>" alt="<?=$LoggedUser['Username']?>'s avatar" />
                               <?php  } else { ?>
                                          <img src="<?=STATIC_SERVER?>common/avatars/default.svg" class="avatar" style="<?=get_avatar_css()?>" alt="Default avatar" />
                              <?php  } ?>
                        </td>
                        <td class="body" valign="top">
                            <div id="contentpreview" style="text-align:left;"></div>
                        </td>
                    </tr>
                </table>
                  <div class="head">Post Comment</div>
            <div class="box pad shadow">
                <form id="quickpostform" action="" method="post" onsubmit="return Validate_Form('message','quickpost')" style="display: block; text-align: center;">
                    <div id="quickreplytext">
                        <input type="hidden" name="action" value="reply" />
                        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                        <input type="hidden" name="groupid" value="<?=$GroupID?>" />
                            <?php  $Text->display_bbcode_assistant("quickpost", get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions'])); ?>
                        <textarea id="quickpost" name="body" class="long"  rows="8"></textarea> <br />
                    </div>
                    <input id="post_preview" type="button" value="Preview" onclick="if (this.preview) {Quick_Edit();} else {Quick_Preview();}" />
                    <input type="submit" value="Post Comment" />
                </form>
            </div>
<?php  } ?>
    </div>
</div>
<?php

show_footer();
