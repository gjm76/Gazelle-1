<?php
//~~~~~~~~~~~ Main collage page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y)
{
    return($Y['count'] - $X['count']);
}

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
$Text = new TEXT;

$CollageID = $_GET['id'];
if (!is_number($CollageID)) { error(0); }

$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
if (empty($TokenTorrents)) {
    $DB->query("SELECT TorrentID, FreeLeech, DoubleSeed FROM users_slots WHERE UserID=$UserID");
    $TokenTorrents = $DB->to_array('TorrentID');
    $Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
}

$Data = $Cache->get_value('collage_'.$CollageID);

if ($Data) {
    $Data = unserialize($Data);
    list($K, list($Name, $Description, $CollageDataList, $TorrentList, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $CreatorName, $CollagePermissions)) = each($Data);
} else {
    $DB->query("SELECT c.Name, Description, UserID, Username, c.Deleted, CategoryID, Locked, MaxGroups, MaxGroupsPerUser, c.Permissions FROM collages AS c LEFT JOIN users_main As u ON c.UserID=u.ID WHERE c.ID='$CollageID'");
    if ($DB->record_count() > 0) {
        list($Name, $Description, $CreatorID, $CreatorName, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser, $CollagePermissions) = $DB->next_record();
        $TorrentList='';
        $CollageList='';
    } else {
        $Deleted = '1';
    }
}

if ($Deleted == '1') {
    header('Location: log.php?search=Collage+'.$CollageID);
    die();
}

$CollagePermissions=(int) $CollagePermissions;
if ($CreatorID == $LoggedUser['ID']) {
      $CanEdit = true;
} elseif ($CollagePermissions>0) {
      $CanEdit = $LoggedUser['Class'] >= $CollagePermissions;
} else {
      $CanEdit=false; // can be overridden by permissions
}

//Handle subscriptions
if (($CollageSubscriptions = $Cache->get_value('collage_subs_user_'.$LoggedUser['ID'])) === FALSE) {
    $DB->query("SELECT CollageID FROM users_collage_subs WHERE UserID = '$LoggedUser[ID]'");
    $CollageSubscriptions = $DB->collect(0);
    $Cache->cache_value('collage_subs_user_'.$LoggedUser['ID'],$CollageSubscriptions,0);
}

if (empty($CollageSubscriptions)) {
    $CollageSubscriptions = array();
}

if (in_array($CollageID, $CollageSubscriptions)) {
    $Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);
}
$DB->query("UPDATE users_collage_subs SET LastVisit=NOW() WHERE UserID = ".$LoggedUser['ID']." AND CollageID=$CollageID");

// Build the data for the collage and the torrent list
if (!is_array($TorrentList)) {
    $DB->query("SELECT ct.GroupID,
            tg.Image,
            tg.NewCategoryID,
            um.ID,
            um.Username,
            tg.TVMAZE
            FROM collages_torrents AS ct
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

foreach ($TorrentList as $GroupID=>$Group) {
    list($GroupID, $GroupName, $TagList, $Torrents) = array_values($Group);
    list($GroupID2, $Image, $NewCategoryID, $UserID, $Username, $TVMAZE) = array_values($CollageDataList[$GroupID]);

    $Review = get_last_review($GroupID);
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
    sort($TagList);
    
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
    $TorrentTags='<br /><div class="tags">'.$TorrentTags.'</div>';

    // Start an output buffer, so we can store this output in $TorrentTable
    ob_start();

        list($TorrentID, $Torrent) = each($Torrents);

        $DisplayName = $GroupName;

        if ($Torrent['ReportCount'] > 0) {
            $Title = "This torrent has ".$Torrent['ReportCount']." active ".($Torrent['ReportCount'] > 1 ?'reports' : 'report');
            $DisplayName .= ' /<span class="reported" title="'.$Title.'"> Reported</span>';
        }
        $Icons = torrent_icons($Torrent, $TorrentID, $Review, in_array($GroupID, $Bookmarks));

        $row = $row == 'a' ? 'b' : 'a';
        $IsMarkedForDeletion = $Review['Status'] == 'Warned' || $Review['Status'] == 'Pending';
?>
<tr class="torrent <?=($IsMarkedForDeletion?'redbar':"row$row")?>" id="group_<?=$GroupID?>">
        <td style="position:relative;">
                <?php
                if ($LoggedUser['HideFloat']) {?>
                    <span style="bottom:0; right:0; position:absolute; margin:0px; padding:0px;"><?=$Icons?></span> <a href="torrents.php?id=<?=$GroupID?>"><?=$DisplayName?></a>
<?php              } else {
                    $Overlay = get_overlay_html($GroupName, anon_username($Torrent['Username'], $Torrent['Anonymous']), $Image, $Torrent['Seeders'], $Torrent['Leechers'], $Torrent['Size'], $Torrent['Snatched'], $Torrent['FilePath']);
                    ?>
                    <script>
                        var overlay<?=$GroupID?> = <?=json_encode($Overlay)?>
                    </script>
                    <div style="bottom:0; right:0; position:absolute; margin:0px; padding:2px;"><?=$Icons?></div>
                    <a href="torrents.php?id=<?=$GroupID?>" onmouseover="return overlib(overlay<?=$GroupID?>, FULLHTML);" onmouseout="return nd();"><?=$DisplayName?></a>
<?php              }  ?>
                <?php if ($LoggedUser['HideTagsInLists'] !== 1) { ?>
                <div class="tags">
                    <div style="padding:0px; margin:0px; max-width:80%;"><?=$TorrentTags?></div>
                </div>    
                <?php } ?>
        </td>
        <td class="nobr"><?=get_size($Torrent['Size'])?></td>
        <td><?=number_format($Torrent['Snatched'])?></td>
        <td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
        <td><?=number_format($Torrent['Leechers'])?></td>
</tr>
<?php
    $TorrentTable.=ob_get_clean();

    ob_start();

    $DisplayName = $GroupName;

    $ShowTitle = explode(" - " , $GroupName); // extract title
    $ShowTitle = $ShowTitle[0]; // get the title
?>
        <li class="image_group_<?=$GroupID?>">
            <a href="torrents.php?action=show&showid=<?=$TVMAZE?>" title="Go to <?=$ShowTitle?>">
<?php	if ($Image) {
        if (check_perms('site_proxy_images')) {
            $Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
        }
?>
                <img src="<?=$Image?>" />
<?php	} else { ?>
                <div class="noimagepad"><div class="box noimage"><?=$DisplayName?></div></div>
<?php	} ?>
            </a>
        </li>
<?php
    $Collage[]=ob_get_clean();
}

$NumUsers = count($Users);

if (($MaxGroups>0 && $NumGroups>=$MaxGroups)  || ($MaxGroupsPerUser>0 && $NumGroupsByUser>=$MaxGroupsPerUser)) {
    $Locked = true;
}

// Silly hack for people who are on the old setting
$CollageCovers = isset($LoggedUser['CollageCovers'])?$LoggedUser['CollageCovers']:25*(abs($LoggedUser['HideCollage'] - 1));
$CollagePages = array();

// Pad it out
if ($NumGroups > $CollageCovers) {
    for ($i = $NumGroups + 1; $i <= ceil($NumGroups/$CollageCovers)*$CollageCovers; $i++) {
        $Collage[] = '<li></li>';
    }
}

for ($i=0; $i < $NumGroups/$CollageCovers; $i++) {
    $Groups = array_slice($Collage, $i*$CollageCovers, $CollageCovers);
    $CollagePage = '';
    foreach ($Groups as $Group) {
        $CollagePage .= $Group;
    }
    $CollagePages[] = $CollagePage;
}

show_header($Name,'overlib,browse,collage,comments,bbcode');
?>
<div class="thin">
    <h2>
<?php if (has_bookmarked('collage', $CollageID)) { ?>
    <img class="icon" src="static/styles/themes/<?=$LoggedUser['StyleName']?>/images/bookmarked.svg" alt="bookmarked" title="You have this collage bookmarked" />&nbsp;
<?php } ?>         
    <?=$Name?></h2>
    <div class="linkbox">
        <a href="collages.php">[List of collages]</a>
<?php if (check_perms('site_collages_create')) { ?>
        <a href="collages.php?action=new">[New collage]</a>
<?php } ?>
        <br /><br />
<?php if (check_perms('site_collages_subscribe')) { ?>
        <a href="#" onclick="CollageSubscribe(<?=$CollageID?>);return false;" id="subscribelink<?=$CollageID?>">[<?=(in_array($CollageID, $CollageSubscriptions) ? 'Unsubscribe' : 'Subscribe')?>]</a>
<?php }
   if (check_perms('site_collages_manage') || ($CreatorID == $LoggedUser['ID'] && !$Locked) ) { ?>
        <a href="collages.php?action=edit&amp;collageid=<?=$CollageID?>">[Edit description]</a>
<?php }
    if (has_bookmarked('collage', $CollageID)) {
?>
        <a href="#" class="__bookmark-collage" data-collageid="<?=$CollageID?>" data-bookmarked>[Remove bookmark]</a>
<?php	} else { ?>
        <a href="#" class="__bookmark-collage" data-collageid="<?=$CollageID?>">[Bookmark]</a>
<?php	}

if (check_perms('site_collages_manage') || ($CreatorID == $LoggedUser['ID'] && !$Locked)) { ?>
        <a href="collages.php?action=manage&amp;collageid=<?=$CollageID?>">[Manage torrents]</a>
<?php } ?>
    <a href="reports.php?action=report&amp;type=collage&amp;id=<?=$CollageID?>">[Report Collage]</a>
<?php if (check_perms('site_collages_delete') && ( $CollageCategoryID ==0 || $NumUsers == 0 || ($NumUsers ==1 && isset($Users[$CreatorID]) )) ) { ?>
        <a href="collages.php?action=delete&amp;collageid=<?=$CollageID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to delete this collage?.');">[Delete]</a>
<?php } ?>
    </div>
    <div class="sidebar">
        <div class="head"><strong>Category</strong></div>
        <div class="box pad">
            <table class="center"><tr>
                <td class="center"><h3><?=$CollageCats[(int) $CollageCategoryID]?></h3></td>
                <td class="right"><a href="collages.php?action=search&amp;cats[<?=(int) $CollageCategoryID?>]=1"><img src="static/common/collageicons/<?=$CollageIcons[(int) $CollageCategoryID]?>" alt="<?=$CollageCats[(int) $CollageCategoryID]?>" title="<?=$CollageCats[(int) $CollageCategoryID]?>" /></a></td>
            </tr></table>
        </div>
<?php
if (check_perms('zip_downloader')) {
?>
        <div class="head"><strong>Collector</strong></div>
        <div class="box">
            <div class="pad">
                <form action="collages.php" method="post">
                <input type="hidden" name="action" value="download" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="collageid" value="<?=$CollageID?>" />
                <select name="preference" style="width:210px">
                    <option value="0">Download All</option>
                    <option value="1">At least 1 seeder</option>
                    <option value="2">5 or more seeders</option>
                </select>
                <input type="submit" style="width:210px" value="Download" />
                </form>
            </div>
        </div>
<?php } ?>
        <div class="head"><strong>Stats</strong></div>
        <div class="box">
            <ul class="stats nobullet">
                <li>Torrents: <?=$NumGroups?></li>
                <li>Built by <?=$NumUsers?> user<?=($NumUsers>1) ? 's' : ''?></li>
            </ul>
        </div>

        <div class="head"><strong>Created by <?=$CreatorName?></strong></div>
        <div class="box pad">

<?php	if (check_perms('site_collages_manage')) { ?>

                <form action="collages.php" method="post">
                    <input type="hidden" name="action" value="change_level" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input type="hidden" name="collageid" value="<?=$CollageID?>" />
                    The collage creator can set the permission level for who can add/delete torrents<br/>
                    <em>the collage creator can edit the description</em><br/>
                    <select name="permission">
<?php
        foreach ($ClassLevels as $CurClass) {
                    if ($CurClass['Level']>=STAFF_LEVEL) break;
                    if ($CollagePermissions==$CurClass['Level']) { $Selected='selected="selected"'; } else { $Selected=""; }
?>
                    <option value="<?=$CurClass['Level']?>" <?=$Selected?>><?=$CurClass['Name'];?></option>
<?php		} ?>

                    <option value="0" <?php if($CollagePermissions==0)echo'selected="selected"';?>>Only Creator</option>

                    </select>

                    <input type="submit" value="Change" title="Change Permissions" />
                </form>
<?php	} else { //  ?>

                Can be contributed by: <?php
                    if ($CollagePermissions==0)
                        echo '<span style="font-weight:bold;">'.$CreatorName.'</span>';
                    else
                        echo make_class_string($ClassLevels[$CollagePermissions]['ID'], true).'+';
                    ?> <br/><br/>
                You <span style="font-weight:bold;"><?=($CanEdit?'can':'cannot')?></span> contribute to this collage.
<?php	} ?>
        </div>
        <div class="head"><strong>Top tags</strong></div>
        <div class="box">
            <div class="pad">
                <ol style="padding-left:5px;">
<?php
uasort($Tags, 'compare');
$i = 0;
foreach ($Tags as $TagName => $Tag) {
    $i++;
    if ($i>5) { break; }
?>
                    <li><a href="collages.php?action=search&amp;tags=<?=$TagName?>"><?=$TagName?></a> (<?=$Tag['count']?>)</li>
<?php
}
?>
                </ol>
            </div>
        </div>
        <div class="head"><strong>Top contributors</strong></div>
        <div class="box">
            <div class="pad">
                <ol style="padding-left:5px;">
<?php
uasort($Users, 'compare');
$i = 0;
foreach ($Users as $ID => $User) {
    $i++;
    if ($i>5) { break; }
?>
                    <li><?=format_username($ID, $User['name'])?> (<?=$User['count']?>)</li>
<?php
}
?>
                </ol>

            </div>
        </div>
<?php if (check_perms('site_collages_manage') || ($CanEdit && !$Locked)) { ?>
        <div class="head"><strong>Add torrent</strong><span style="float: right"><a href="#" onClick="$('#addtorrent').toggle(); $('#batchadd').toggle(); this.innerHTML = (this.innerHTML == '[Batch Add]'?'[Individual Add]':'[Batch Add]'); return false;">[Batch Add]</a></span></div>
        <div class="box">
            <div class="pad" id="addtorrent">
                <form action="collages.php" method="post">
                    <input type="hidden" name="action" value="add_torrent" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input type="hidden" name="collageid" value="<?=$CollageID?>" />
                    <input type="text" size="20" name="url" />
                    <input type="submit" value="+" />
                    <br />
                    <i>Enter the URL of a torrent on the site.</i>
                </form>
            </div>
            <div class="pad hidden" id="batchadd">
                <form action="collages.php" method="post">
                    <input type="hidden" name="action" value="add_torrent_batch" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input type="hidden" name="collageid" value="<?=$CollageID?>" />
                    <textarea name="urls" rows="5" cols="25" wrap="off"></textarea><br />
                    <input type="submit" value="Add" />
                    <br />
                    <i>Enter the URLs of torrents on the site, one to a line.</i>
                </form>
            </div>
        </div>
<?php } ?>
    </div>
    <div class="main_column">
<?php
if ($CollageCovers != 0) { ?>
        <div class="head" id="coverhead"><strong>Banner</strong></div>
        <div id="coverart" class="box">
            <ul class="collage_images" id="collage_page0">
<?php
    $Page1 = array_slice($Collage, 0, $CollageCovers); 
    foreach ($Page1 as $Group) {
        echo $Group;
}?>
            </ul>
        </div>
<?php	if ($NumGroups > $CollageCovers) {  ?>
        <div class="linkbox pager" style="clear: left;" id="pageslinksdiv">
            <span id="firstpage" class="invisible"><a href="#" class="pageslink" onClick="collageShow.page(0, this); return false;">&lt;&lt; First</a> | </span>
            <span id="prevpage" class="invisible"><a href="#" id="prevpage"  class="pageslink" onClick="collageShow.prevPage(); return false;">&lt; Prev</a> | </span>
<?php		for ($i=0; $i < $NumGroups/$CollageCovers; $i++) { ?>
            <span id="pagelink<?=$i?>" class="<?=(($i>4)?'hidden':'')?><?=(($i==0)?' selected':'')?>"><a href="#" class="pageslink" onClick="collageShow.page(<?=$i?>, this); return false;"><?=$CollageCovers*$i+1?>-<?=min($NumGroups,$CollageCovers*($i+1))?></a><?=($i != ceil($NumGroups/$CollageCovers)-1)?' | ':''?></span>
<?php		} ?>
            <span id="nextbar" class="<?=($NumGroups/$CollageCovers > 5)?'hidden':''?>"> | </span>
            <span id="nextpage"><a href="#" class="pageslink" onClick="collageShow.nextPage(); return false;">Next &gt;</a></span>
            <span id="lastpage" class="<?=ceil($NumGroups/$CollageCovers)==2?'invisible':''?>"> | <a href="#" id="lastpage" class="pageslink" onClick="collageShow.page(<?=ceil($NumGroups/$CollageCovers)-1?>, this); return false;">Last &gt;&gt;</a></span>
        </div>
        <script type="text/javascript">
            collageShow.init(<?=json_encode($CollagePages)?>);
        </script>
<?php	}
} ?>
            <div class="head"><strong>Description</strong></div>
        <div class="box">
                  <div class="pad"><?=$Text->full_format($Description, get_permissions_advtags($UserID))?></div>
        </div>

        <div class="head"><strong>Torrents</strong></div>
        <table class="torrent_table" id="discog_table">
            <tr class="colhead">
                <td width="70%"><strong>Name</strong></td>
                <td>Size</td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/snatched.svg" alt="↺" title="Snatches" /></td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/seeders.svg" alt="∧" title="Seeders" /></td>
                <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/leechers.svg" alt="∨" title="Leechers" /></td>
            </tr>
<?=$TorrentTable?>
        </table>

    </div>
        
            <br style="clear:both;" />
            <div class="box pad shadow">
                <h3 style="float:left">Most recent Comments</h3> <a style="float:right" href="collages.php?action=comments&amp;collageid=<?=$CollageID?>">All comments</a>
            <br style="clear:both;" /></div>
<?php
if (empty($CommentList)) {
    $DB->query("SELECT
        cc.ID,
        cc.Body,
        cc.UserID,
        um.Username,
        cc.Time
        FROM collages_comments AS cc
        LEFT JOIN users_main AS um ON um.ID=cc.UserID
        WHERE CollageID='$CollageID'
        ORDER BY ID DESC LIMIT 15");
    $CommentList = $DB->to_array();
}
foreach ($CommentList as $Comment) {
    list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment;
?>
                <div class="head"><a href='#post<?=$CommentID?>'>#<?=$CommentID?></a> By <?=format_username($UserID, $Username) ?> <?=time_diff($CommentTime) ?> <a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$CommentID?>">[Report Comment]</a></div>
        <div id="post<?=$CommentID?>" class="box">
                  <div class="pad"><?=$Text->full_format($Body, get_permissions_advtags($UserID))?></div>
        </div>
<?php
}

if (!$LoggedUser['DisablePosting']) {
?>

            <div class="messagecontainer" id="container"><div id="message" class="hidden center messagebar"></div></div>
                <table id="quickreplypreview" class="forum_post box vertical_margin hidden" style="text-align:left;">
                    <tr class="smallhead">
                        <td>
                            <span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
                                            By <?=format_username($LoggedUser['ID'], $LoggedUser['Username'])?>
                            Just now
                            </span>
                            <span id="barpreview" style="float:right;">
                                <a href="#quickreplypreview">[Report]</a>
                                <a href="#">&uarr;</a>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="body pad" valign="top">
                            <div id="contentpreview" style="text-align:left;"></div>
                        </td>
                    </tr>
                </table>
                  <div class="head">Post reply</div>
            <div class="box pad shadow">
                <form id="quickpostform" action="" method="post" onsubmit="return Validate_Form('message', 'quickpost')" style="display: block; text-align: center;">
                    <div id="quickreplytext">
                        <input type="hidden" name="action" value="add_comment" />
                        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                                    <input type="hidden" name="collageid" value="<?=$CollageID?>" />
                            <?php $Text->display_bbcode_assistant("quickpost", get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions'])); ?>
                        <textarea id="quickpost" name="body" class="long"  rows="5"></textarea> <br />
                    </div>
                    <input id="post_preview" type="button" value="Preview" onclick="if (this.preview) {Quick_Edit();} else {Quick_Preview();}" />
                    <input type="submit" value="Post reply" />
                </form>
            </div>
<?php
}
?>
    </div>
</div>
<?php
show_footer();

$Cache->cache_value('collage_'.$CollageID, serialize(array(array($Name, $Description, $CollageDataList, $TorrentList, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $CreatorName, $CollagePermissions, $Locked, $MaxGroups, $MaxGroupsPerUser))), 3600);
