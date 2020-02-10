<?php

if (!check_force_anon($_GET['userid'])) {
    // then you dont get to see any torrents for any uploader!
     error(403);
}

include(SERVER_ROOT . '/common/functions.php');
include(SERVER_ROOT . '/sections/bookmarks/functions.php');

$Orders = array('Time', 'Name', 'Seeders', 'Leechers', 'Snatched', 'Size', 'SeedTime');
$Ways = array('asc'=>'Ascending', 'desc'=>'Descending');

$Type = $_GET['type'];
$UserID = $_GET['userid'];

$StaffTools = check_perms('torrents_delete'); // HnR clear permission

if (!is_number($UserID)) { error(0); }

if (isset($LoggedUser['TorrentsPerPage'])) {
    $TorrentsPerPage = $LoggedUser['TorrentsPerPage'];
} else {
    $TorrentsPerPage = TORRENTS_PER_PAGE;
}

if (!empty($_GET['page']) && is_number($_GET['page'])) {
    $Page = $_GET['page'];
    $Limit = ($Page-1)*$TorrentsPerPage.', '.$TorrentsPerPage;
} else {
    $Page = 1;
    $Limit = $TorrentsPerPage;
}

if (!empty($_GET['order_by']) && in_array($_GET['order_by'], $Orders)) {
    $OrderBy = $_GET['order_by'];
} else {
    $OrderBy = 'Time';
}

if (!empty($_GET['order_way']) && array_key_exists($_GET['order_way'], $Ways)) {
    $OrderWay = $_GET['order_way'];
} else {
    $OrderWay = 'desc';
}

$SearchWhere = array();

if (!empty($_GET['categories'])) {
    $Cats = array();
    foreach (array_keys($_GET['categories']) as $Cat) {
        if (!is_number($Cat)) {
            error(0);
        }
        $Cats[]="tg.NewCategoryID='".db_string($Cat)."'";
    }
    $SearchWhere[]='('.implode(' OR ', $Cats).')';
}

if (!empty($_GET['tags'])) {
    $Tags = explode(' ',$_GET['tags']);
    $TagList = array();
    foreach ($Tags as $Tag) {
        $Tag = trim(str_replace('.','_',$Tag));
        if (empty($Tag)) { continue; }
        $TagList[]="tg.TagList LIKE '%".db_string($Tag)."%'";
    }
    if (!empty($TagList)) {
        $SearchWhere[]='('.implode(' AND ', $TagList).')';
    }
}

$SearchWhere = implode(' AND ', $SearchWhere);
if (!empty($SearchWhere)) {
    $SearchWhere = ' AND '.$SearchWhere;
}

$User = user_info($UserID);
$Perms = get_permissions($User['PermissionID']);
$UserClass = $Perms['Class'];

switch ($_GET['type']) {
    case 'snatched':
        if (!check_paranoia('snatched', $User['Paranoia'], $UserClass, $UserID)) { error(PARANOIA_MSG); }
        $Time = 'xs.tstamp';
        $Seedtime = 'xs.seedtime';
        $UserField = 'xs.uid';
        $ExtraWhere = "AND xs.upload = '0'";
        $From = "xbt_snatched AS xs JOIN torrents AS t ON t.ID=xs.fid";
        break;
    case 'hitandrun':
        $Hit = true;
        $Time = 'xs.tstamp';
        $Seedtime = 'st';
        $UserField = 'xs.uid';
        $GroupBy = 'GroupID';
        $ExtraWhere = "AND ( 
          (st < '" . $SiteOptions['HnR'] . "'
           AND t.ID IS NOT NULL
           AND c.tag = 'episode')
          OR
          (st < '" . $SiteOptions['HnRSeason'] . "'
           AND t.ID IS NOT NULL
           AND c.tag = 'season')     
        )";
        $From = "(SELECT uid, fid, tstamp, seedtime AS st FROM xbt_snatched WHERE uid='$UserID' AND seedtime < '" . $SiteOptions['HnRSeason'] . "') AS xs
        LEFT JOIN torrents AS t ON xs.fid = t.ID";
        break;
    case 'seeding':
        if (!check_paranoia('seeding', $User['Paranoia'], $UserClass, $UserID)) { error(PARANOIA_MSG); }
        $Time = '(unix_timestamp(now()) - xfu.timespent)';
        $Seedtime = 'xs.seedtime';
        $UserField = 'xfu.uid';
        $ExtraWhere = 'AND xfu.active=1 AND xfu.Remaining=0';
        $From = "xbt_files_users AS xfu JOIN torrents AS t ON t.ID=xfu.fid
        LEFT JOIN xbt_snatched as xs ON xs.uid = xfu.uid AND xs.fid = xfu.fid";
        break;
    case 'leeching':
        if (!check_paranoia('leeching', $User['Paranoia'], $UserClass, $UserID)) { error(PARANOIA_MSG); }
        $Time = '(unix_timestamp(now()) - xfu.timespent)';
        $Seedtime = 'xs.seedtime';
        $UserField = 'xfu.uid';
        $ExtraWhere = 'AND xfu.active=1 AND xfu.Remaining>0';
        $From = "xbt_files_users AS xfu JOIN torrents AS t ON t.ID=xfu.fid
        LEFT JOIN xbt_snatched as xs ON xs.uid = xfu.uid AND xs.fid = xfu.fid";
        break;
    case 'uploaded':
        if ((empty($_GET['filter']) || $_GET['filter'] != 'perfectflac') && !check_paranoia('uploads', $User['Paranoia'], $UserClass, $UserID)) { error(PARANOIA_MSG); }
        $Time = 'unix_timestamp(t.Time)';
        $Seedtime = 'xs.seedtime';
        $UserField = 't.UserID';
        $ExtraWhere = 'AND flags!=1';
        $From = "torrents AS t
        LEFT JOIN xbt_snatched as xs ON xs.uid = t.UserID AND xs.fid = t.id";
        break;
    case 'downloaded':
        if (!check_paranoia('grabbed', $User['Paranoia'], $UserClass, $UserID)) { error(PARANOIA_MSG); }
        $Time = 'unix_timestamp(ud.Time)';
        $Seedtime = 'xs.seedtime';
        $UserField = 'ud.UserID';
        $ExtraWhere = '';
        $From = "users_downloads AS ud JOIN torrents AS t ON t.ID=ud.TorrentID
        LEFT JOIN xbt_snatched as xs ON xs.uid = ud.UserID AND xs.fid = ud.TorrentID";
        break;
    default:
        error(404);
}

if (!empty($_GET['filter'])) {
    if ($_GET['filter'] == "uniquegroup") {
        $GroupBy = "tg.ID";
    }
}

if (empty($GroupBy)) {
    $GroupBy = "t.ID";
}

// if anon ...
if ($UserID!=$LoggedUser['ID'] && !check_perms('users_view_anon_uploaders')) {
    $ExtraWhere .= " AND t.Anonymous='0'";
}

if ((empty($_GET['search']) || trim($_GET['search']) == '') && $OrderBy!='Name') {
    $SQL = "SELECT SQL_CALC_FOUND_ROWS t.GroupID, t.ID AS TorrentID, $Time AS Time, tg.NewCategoryID, tg.Image, $Seedtime AS SeedTime, TVMAZE
        FROM $From
        JOIN torrents_group AS tg ON tg.ID=t.GroupID
        LEFT JOIN categories AS c ON tg.NewCategoryID = c.id
        WHERE $UserField='$UserID' $ExtraWhere $SearchWhere
        GROUP BY $GroupBy        
        ORDER BY $OrderBy $OrderWay LIMIT $Limit";
} else {
    $DB->query("CREATE TEMPORARY TABLE temp_sections_torrents_user (
        GroupID int(10) unsigned not null,
        TorrentID int(10) unsigned not null,
        Time int(12) unsigned not null,
        NewCategoryID int(11) unsigned,
        Image varchar(255),
        Seeders int(6) unsigned,
        Leechers int(6) unsigned,
        Snatched int(10) unsigned,
        Name mediumtext,
        Size bigint(12) unsigned,
        seedtime int(11) NOT NULL DEFAULT '0',
        TVMAZE int(10) DEFAULT NULL,
        PRIMARY KEY (TorrentID)) CHARSET=utf8");
    $DB->query("INSERT IGNORE INTO temp_sections_torrents_user SELECT
        t.GroupID,
        t.ID AS TorrentID,
        $Time AS Time,
        tg.NewCategoryID,
        tg.Image,
        t.Seeders,
        t.Leechers,
        t.Snatched,
        tg.Name,
        t.Size,
        $Seedtime as SeedTime,
        TVMAZE
        FROM $From
        JOIN torrents_group AS tg ON tg.ID=t.GroupID
        LEFT JOIN categories AS c ON tg.NewCategoryID = c.id
        WHERE $UserField='$UserID' $ExtraWhere $SearchWhere
        GROUP BY TorrentID, Time");

    if (!empty($_GET['search']) && trim($_GET['search']) != '') {
        $Words = array_unique(explode(' ', db_string($_GET['search'])));
    }

    $SQL = "SELECT SQL_CALC_FOUND_ROWS
        GroupID, TorrentID, Time, NewCategoryID, Image, SeedTime, TVMAZE
        FROM temp_sections_torrents_user";
    if (!empty($Words)) {
        $SQL .= "
        WHERE Name LIKE '%".implode("%' AND Name LIKE '%", $Words)."%'";
    }
    $SQL .= "
        ORDER BY $OrderBy $OrderWay LIMIT $Limit";
}

$DB->query($SQL);
$GroupIDs = $DB->collect('GroupID');
$TorrentsInfo = $DB->to_array('TorrentID', MYSQLI_ASSOC);

$DB->query("SELECT FOUND_ROWS()");
list($TorrentCount) = $DB->next_record();

$Results = get_groups($GroupIDs);

$Action = display_str($_GET['type']);
$User = user_info($UserID);

if(!$INLINE) {
	if($Action == 'hitandrun')  show_header($User['Username'].'\'s Recent Activity Log', 'overlib,browse');
	else show_header($User['Username'].'\'s '.$Action.' torrents', 'overlib,browse');
}

$Pages=get_pages($Page,$TorrentCount,$TorrentsPerPage,8,'#torrents');

?>
<?php  if (!$INLINE) {  ?>

<div class="thin">
<?php if($Action == 'hitandrun') { ?>
    <h2><a href="user.php?id=<?=$UserID?>"><?=$User['Username']?></a><?='\'s Recent Activity Log'?></h2>
<?php }else{ ?>    
    <h2><a href="user.php?id=<?=$UserID?>"><?=$User['Username']?></a><?='\'s '.$Action.' torrents'?></h2>
<?php } ?>
    <div class="head">Search</div>
        <form action="" method="get">
                 <table>
                <tr>
                    <td class="label"><strong>Search for:</strong></td>
                    <td>
                        <input type="hidden" name="type" value="<?=$_GET['type']?>" />
                        <input type="hidden" name="userid" value="<?=$UserID?>" />
                        <input type="text" name="search" size="60" value="<?php form('search')?>" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><strong>Tags:</strong></td>
                    <td>
                        <input type="text" name="tags" size="60" value="<?php form('tags')?>" />
                    </td>
                </tr>

                <tr>
                    <td class="label"><strong>Order by</strong></td>
                    <td>
                        <select name="order_by">
<?php  foreach ($Orders as $OrderText) { ?>
                            <option value="<?=$OrderText?>" <?php selected('order_by', $OrderText)?>><?=$OrderText?></option>
<?php  }?>
                        </select>&nbsp;
                        <select name="order_way">
<?php  foreach ($Ways as $WayKey=>$WayText) { ?>
                            <option value="<?=$WayKey?>" <?php selected('order_way', $WayKey)?>><?=$WayText?></option>
<?php  }?>
                        </select>
                    </td>
                </tr>
            </table>

            <table class="cat_list">
<?php
$x=0;
$row = 'a';
reset($NewCategories);
foreach ($NewCategories as $Cat) {
    if ($x%7==0) {
        if ($x > 0) {
?>
                </tr>
<?php 		} ?>
                <tr class="row<?=$row?>">
<?php
            $row = ($row == 'a') ? 'b' : 'a';
    }
    $x++;
?>
                    <td>
                        <input type="checkbox" name="categories[<?=($Cat['id'])?>]" id="cat_<?=($Cat['id'])?>" value="1" <?php  if (isset($_GET['categories'][$Cat['id']])) { ?>checked="checked"<?php  } ?>/>
                        <label for="cat_<?=($Cat['id'])?>">
                        <a href="torrents.php?categories[<?=$Cat['id']?>]=1&userid=<?=$_GET['userid']?>&type=<?=$_GET['type']?>&order_way=<?=$_GET['order_way']?>&order_by=<?=$_GET['order_by']?>&page=<?=$_GET['page']?>&search=<?=$_GET['search']?>&tags=<?=$_GET['tags']?>">
                        <?= $Cat['name'] ?></a></label>
                    </td>
<?php
}
?>
                                    <td colspan="<?=7-($x%7)?>"></td>
                </tr>
            </table>
            <div class="submit">
                <input type="submit" value="Search torrents" />
            </div>
        </form>

<?php if ($StaffTools && $Hit) { ?>
<form action="" method="post" id="hnrform" name="hnrform">
  <input type="hidden" name="action" id="action" value="" />
  <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
  <input type="hidden" name="userid" value="<?=$UserID?>" />
<?php } ?>

<?php
} // end if !$INLINE
?>

<?php
    if (count($GroupIDs) == 0) { ?>
        <br/>
    <div class="head">Torrents</div>
    <div class="box pad center">
          <h2>No torrents found</h2>
    </div>
<?php 	} else { ?>
    <div class="linkbox"><?=$Pages?></div>
    <div class="head"><?=str_plural('Torrent',$TorrentCount)?></div>
    <table class="torrent_table" id="torrent_table">
        <tr class="colhead">
            <td></td>
            <td><a href="<?=header_link('Name', 'asc', '#torrents')?>">Torrent</a></td>
                  <?php /* <td class="center"><span title="Number of Files">F</span></td>
                  <td class="center"><span title="Number of Comments">C</span></td> */ ?>
            <td><a href="<?=header_link('Time', 'desc', '#torrents')?>">Uploaded</a></td>
            <td style="width:8%;"><a href="<?=header_link('SeedTime', 'desc', '#torrents')?>" title="">Seed Time</a></td>
<?php if($Hit) { ?>            
            <td>Remaining</td>
<?php } ?>
            <td><a href="<?=header_link('Size', 'desc', '#torrents')?>">Size</a></td>
            <td class="sign">
                <a href="<?=header_link('Snatched', 'desc', '#torrents')?>"><img src="static/styles/themes/<?=$LoggedUser['StyleName']?>/images/snatched.svg" alt="↺" title="Snatches" /></a>
            </td>
            <td class="sign">
                <a href="<?=header_link('Seeders', 'desc', '#torrents')?>"><img src="static/styles/themes/<?=$LoggedUser['StyleName']?>/images/seeders.svg" alt="∧" title="Seeders" /></a>
            </td>
            <td class="sign">
                <a href="<?=header_link('Leechers', 'desc', '#torrents')?>"><img src="static/styles/themes/<?=$LoggedUser['StyleName']?>/images/leechers.svg" alt="∨" title="Leechers" /></a>
            </td>
<?php if($Hit) { ?>             
            <td class="center">
                <img src='static/styles/themes/<?=$LoggedUser['StyleName']?>/images/flag.svg' width="15" height="15" border="0" title="HnR">
            </td>
<?php } ?>
        <?php if ($StaffTools && $Hit) { // for staff only ?>
         <td title="Clear HnR">Clear</td>
        <?php }  // for staff only ?>
        </tr>
<?php
    $Results = $Results['matches'];
      $row = 'a';
    $Bookmarks = all_bookmarks('torrent');
    foreach ($TorrentsInfo as $TorrentID=>$Info) {

        list($GroupID, , $Time, $NewCategoryID, $Image, $HnR, $TVMAZE) = array_values($Info);

        list($GroupID, $GroupName, $TagList, $Torrents) = array_values($Results[$GroupID]);
        $Torrent = $Torrents[$TorrentID];

        $Review = get_last_review($GroupID);

        $TagList = explode(' ',str_replace('_','.',$TagList));
        sort($TagList);
        
        $TorrentTags = array();
        $numtags=0;
        foreach ($TagList as $Tag) {
            if ($numtags++>=$LoggedUser['MaxTags'])  break;
            $TorrentTags[]='<a href="torrents.php?type='.$Action.'&amp;userid='.$UserID.'&amp;tags='.$Tag.'">'.$Tag.'</a>';
        }
        $TorrentTags = implode(', ', $TorrentTags);
        $DisplayName = $GroupName;

        if ($Torrent['ReportCount'] > 0) {
            $Title = "This torrent has ".$Torrent['ReportCount']." active ".($Torrent['ReportCount'] > 1 ?'reports' : 'report');
            $DisplayName .= ' /<span class="reported" title="'.$Title.'"> Reported</span>';
        }

        $Icons = torrent_icons($Torrent, $TorrentID, $Review, in_array($GroupID, $Bookmarks));

        $NumComments = get_num_comments($GroupID);

        $row = $row==='b'?'a':'b';
        $IsMarkedForDeletion = $Review['Status'] == 'Warned' || $Review['Status'] == 'Pending';
        
        if($UserID!=$LoggedUser['ID'] && $StaffTools) { // show status for staff
           $DB->query("SELECT fid as TorrentID, IF(xbt.remaining > '0', 'L', 'S') AS PeerStatus FROM xbt_files_users AS xbt
                       WHERE active='1' AND uid = '$UserID' AND fid ='$GroupID'");
           list(,$Status) = $DB->next_record();
        }   
?>
        <tr class="torrent <?=($IsMarkedForDeletion?'redbar':"row$row")?>">
            <td class="center cats_col">
                <div title="<?php echo empty($TVMAZE) ? $NewCategories[$NewCategoryID]['tag'] : 'Go to show'; ?>">
<?php            if($TVMAZE) { ?>
                  <a href="torrents.php?action=show&showid=<?=$TVMAZE?>">
<?php            } ?>                  
                   <img src="<?='static/common/caticons/'.$NewCategories[$NewCategoryID]['image']?>" />
<?php            if($TVMAZE) { ?>
                  </a> 
<?php            } ?>
                </div>
            </td>
            <td>
                <?php
                if ($LoggedUser['HideFloat']) {?>
                    <?=$Icons?> <a href="torrents.php?id=<?=$GroupID?>"><?=$DisplayName?></a>
<?php               } else {
                    $Overlay = get_overlay_html($GroupName, anon_username($Torrent['Username'], $Torrent['Anonymous']), $Image, $Torrent['Seeders'], $Torrent['Leechers'], $Torrent['Size'], $Torrent['Snatched'], $Torrent['FilePath']);
                    ?>
                    <script>
                        var overlay<?=$GroupID?> = <?=json_encode($Overlay)?>
                    </script>
                    <?=$Icons?>
                    <a href="torrents.php?id=<?=$GroupID?>" onmouseover="return overlib(overlay<?=$GroupID?>, FULLHTML);" onmouseout="return nd();"><?=$DisplayName?></a>
<?php               }  ?>
                <br />
          <?php  if ($LoggedUser['HideTagsInLists'] !== 1) { ?>
                <div class="tags">
                    <?=$TorrentTags?>
                </div>
          <?php  } ?>
            </td>
          <?php /*  <td class="center"><?=number_format($Torrent['FileCount'])?></td>
            <td class="center"><?=number_format($NumComments)?></td> */ ?>
            <td class="nobr"><?=time_diff($Torrent['Time'],1)?></td>
          <?php if($HnR) { ?>
            <td style="text-align:center"<?=(($NewCategories[$NewCategoryID]['tag'] == 'season') && ($SiteOptions['HnRSeason'] > $HnR)  && !$Hit ||
            ($NewCategories[$NewCategoryID]['tag'] == 'episode') && ($SiteOptions['HnR'] > $HnR  && !$Hit))?' class="r00"':''?>>
            <?php if($HnR) echo time_span($HnR*3600); else echo "0";?></td>          
          <?php }else {?>
            <td style="text-align:center">---</td> 
          <?php }?>            
          <?php if($Hit) { ?>            
            <td class="nobr" style="text-align:center">
          <?php if ($NewCategories[$NewCategoryID]['tag'] == 'season'){
                echo time_span(($SiteOptions['HnRSeason'] - $HnR)*3600);
                }
                else echo time_span(($SiteOptions['HnR'] - $HnR)*3600);
            ?></td>
          <?php  } ?>
            <td class="nobr"><?=get_size($Torrent['Size'])?></td>
            <td><?=number_format($Torrent['Snatched'])?></td>
            <td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
            <td><?=number_format($Torrent['Leechers'])?></td>
          <?php if($Hit) { ?>            
            <td><?php if($TorrentUserStatus[$TorrentID][PeerStatus] == "S" || $TorrentUserStatus[$TorrentID][PeerStatus] == "L" || $Status) { ?>
            <img src="/static/common/images/ok.svg" width="15" height="15" border="0" title="Not a HnR">
              <?php } else { ?>
            <img src="/static/common/images/not.svg" width="15" height="15" border="0" title="HnR"></td>
              <?php } ?>
          <?php }?>
        <?php if ($StaffTools && $Hit) {  // for staff only ?>       
         <td>
          <input class="checkbox" type="checkbox" id="hnr_<?=$TorrentID?>" name="hnr_select[]" value="<?=$TorrentID?>" onclick="show_delete();">
         </td>
        <?php }  // for staff only ?>                       
        </tr>
<?php  }

    }
?>
    </table>

<?php if ($StaffTools && count($GroupIDs) > 0 && $Hit) {  // for staff only ?>
    <div style="text-align: center;">
        <input type="checkbox" id="hnr_all" onClick="toggle_hnr_all()" /> Select All (use with caution) | 
        <input type="button" value="Clear" onclick="do_mass_hnr()">
<?php }  // for staff only ?>
    
    <div class="linkbox"><?=$Pages?></div>
<?php
if (!$INLINE) {
?>
</div>
<?php if ($StaffTools && $Hit) { // for staff only ?>
</form>
<?php }  // for staff only
    show_footer();
}
