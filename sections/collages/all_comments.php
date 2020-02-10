<?php
/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
    ThreadID: ID of the forum curently being browsed
    page:	The page the user's on.
    page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content


$Text = new TEXT;

// Check for lame SQL injection attempts
$CollageID = $_GET['collageid'];
if (!is_number($CollageID)) {
    error(0);
}

list($Page,$Limit) = page_limit(POSTS_PER_PAGE);

//Get the cache catalogue
$CatalogueID = floor((POSTS_PER_PAGE*$Page-POSTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
if (!list($Catalogue,$Posts) = $Cache->get_value('collage_'.$CollageID.'_catalogue_'.$CatalogueID)) {
    $DB->query("SELECT SQL_CALC_FOUND_ROWS
        cc.ID,
        cc.UserID,
        cc.Time,
        cc.Body
        FROM collages_comments AS cc
            LEFT JOIN users_main AS a ON a.ID = UserID
        WHERE CollageID = '$CollageID'
        LIMIT $CatalogueLimit");
    $Catalogue = $DB->to_array();
    $DB->query("SELECT FOUND_ROWS()");
    list($Posts) = $DB->next_record();
    $Cache->cache_value('collage_'.$CollageID.'_catalogue_'.$CatalogueID, array($Catalogue,$Posts), 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((POSTS_PER_PAGE*$Page-POSTS_PER_PAGE)%THREAD_CATALOGUE),POSTS_PER_PAGE,true);

$DB->query("SELECT Name FROM collages WHERE ID='$CollageID'");
list($Name) = $DB->next_record();

// Start printing
show_header('Comments for collage '.$Name, 'comments,bbcode,jquery');
?>
<div class="thin">
    <h2>
        <a href="collages.php">Collages</a> &gt;
        <a href="collages.php?id=<?=$CollageID?>"><?=$Name?></a>
    </h2>
    <div class="linkbox">
<?php
$Pages=get_pages($Page,$Posts,POSTS_PER_PAGE,9);
echo $Pages;
?>
    </div>
<?php

//---------- Begin printing
foreach ($Thread as $Post) {
    list($PostID, $AuthorID, $AddedTime, $Body) = $Post;
    list($AuthorID, $Username, $PermissionID, $Paranoia, $Donor, $Warned, $Avatar, $Enabled, $UserTitle,,,$Signature,,$GroupPermissionID) = array_values(user_info($AuthorID));
      $AuthorPermissions = get_permissions($PermissionID);
      list($ClassLevel,$PermissionValues,$MaxSigLength,$MaxAvatarWidth,$MaxAvatarHeight)=array_values($AuthorPermissions);

      ?>
<table class="forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>" id="post<?=$PostID?>">
    <tr class="smallhead">
        <td colspan="2">
            <span style="float:left;"><a href='#post<?=$PostID?>'>#<?=$PostID?></a>
                <?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled, $PermissionID, $UserTitle, true, $GroupPermissionID, true)?> <?=time_diff($AddedTime)?>
<?php if (!$ThreadInfo['IsLocked']) { ?>				- <a href="#quickpost" onclick="Quote('collages','<?=$PostID?>','c<?=$CollageID?>','<?=$Username?>');">[Quote]</a><?php }
if (can_edit_comment($AuthorID, null, $AddedTime, $AddedTime)) { ?>				- <a href="#post<?=$PostID?>" onclick="Edit_Form('collages','<?=$PostID?>');">[Edit]</a><?php }
if (check_perms('site_moderate_forums')) { ?>				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a> <?php } ?>
            </span>
            <span id="bar<?=$PostID?>" style="float:right;">
                 <a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$PostID?>">[Report]</a>
                 &nbsp;
                <a href="#">&uarr;</a>
            </span>
        </td>
    </tr>
    <tr>
<?php if (empty($HeavyInfo['DisableAvatars'])) { ?>
        <td class="avatar" valign="top" rowspan="2">
<?php      if ($Avatar) { ?>
            <img src="<?=$Avatar?>" class="avatar" style="<?=get_avatar_css($MaxAvatarWidth, $MaxAvatarHeight)?>" alt="<?=$Username ?>'s avatar" />
<?php      } else { ?>
            <img src="<?=STATIC_SERVER?>common/avatars/default.svg" class="avatar" style="<?=get_avatar_css()?>" alt="Default avatar" />
<?php      }
        $UserBadges = get_user_badges($AuthorID);
        if ( !empty($UserBadges) ) {  ?>
               <div class="badges">
<?php                  print_badges_array($UserBadges, $AuthorID); ?>
               </div>
<?php      }      ?>
        </td>
<?php }
$AllowTags= get_permissions_advtags($AuthorID, false, $AuthorPermissions);
?>
        <td class="body" valign="top">
            <div id="content<?=$PostID?>">
                      <div class="post_content"><?=$Text->full_format($Body, $AllowTags) ?> </div>
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
<?php	}


if (!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
    if ($ThreadInfo['MinClassWrite'] <= $LoggedUser['Class'] && !$LoggedUser['DisablePosting']) {

?>
            <div class="messagecontainer" id="container"><div id="message" class="hidden center messagebar"></div></div>
                <table id="quickreplypreview" class="forum_post box vertical_margin hidden" style="text-align:left;">
                    <tr class="smallhead">
                        <td colspan="2">
                            <span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
                                <?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'], $LoggedUser['PermissionID'], $LoggedUser['Title'], true, $LoggedUser['GroupPermissionID'], true)?>
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
                              <?php if (!empty($LoggedUser['Avatar'])) {  ?>
                                            <img src="<?=$LoggedUser['Avatar']?>" class="avatar" style="<?=get_avatar_css($LoggedUser['MaxAvatarWidth'], $LoggedUser['MaxAvatarHeight'])?>" alt="<?=$LoggedUser['Username']?>'s avatar" />
                               <?php } else { ?>
                                          <img src="<?=STATIC_SERVER?>common/avatars/default.svg" class="avatar" style="<?=get_avatar_css()?>" alt="Default avatar" />
                              <?php } ?>
                        </td>
                        <td class="body" valign="top">
                            <div id="contentpreview" style="text-align:left;"></div>
                        </td>
                    </tr>
                </table>
                  <div class="head">Post comment</div>
            <div class="box pad shadow">
                <form id="quickpostform" action="" method="post" onsubmit="return Validate_Form('message', 'quickpost')" style="display: block; text-align: center;">
                    <div id="quickreplytext">
                        <input type="hidden" name="action" value="add_comment" />
                        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="collageid" value="<?=$CollageID?>" />
                            <?php $Text->display_bbcode_assistant("quickpost", get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions'])); ?>
                        <textarea id="quickpost" name="body" class="long"  rows="8"></textarea> <br />
                    </div>
                    <input id="post_preview" type="button" value="Preview" onclick="if (this.preview) {Quick_Edit();} else {Quick_Preview();}" />
                    <input type="submit" value="Post comment" />
                </form>
            </div>
<?php
      }
}
?>
    <div class="linkbox">
        <?=$Pages?>
    </div>
</div>
<?php
show_footer();
