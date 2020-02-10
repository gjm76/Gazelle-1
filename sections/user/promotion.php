<?php

if (isset($_GET['userid'])) {
        if (!is_number($_GET['userid'])) { error(404); }
        $UserID = $_GET['userid'];
} else {
        $UserID = $LoggedUser['ID'];
}

if ($UserID != $LoggedUser['ID'] && !check_perms('admin_manage_networks')) {
    error(403);
}

// current class
$DB->query("SELECT
   m.Username,
   i.JoinDate,
   i.Warned,
   p.Level AS Class,
   m.Uploaded,
   m.PermissionID AS ClassID,
   m.GroupPermissionID,
   m.Credits,
   m.HnR,
   COUNT(posts.id) AS ForumPosts
   FROM users_main AS m
   JOIN users_info AS i ON i.UserID = m.ID
   LEFT JOIN permissions AS p ON p.ID=m.PermissionID
   LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
   WHERE m.ID = $UserID");

if ($DB->record_count() == 0) { // If user doesn't exist
   header("Location: log.php?search=User+".$UserID);
}

list($Username, $JoinDate, $Warned, $Class, $Uploaded, $ClassID, $GroupPermID, $BonusCredits, $HnRs, $ForumPosts) = $DB->next_record();

// get users uploads
$DB->query("SELECT COUNT(ID) FROM torrents WHERE UserID='$UserID'");
list($Uploads) = $DB->next_record();
if(!$Uploads) $Uploads = 0;

// get users snatches
$DB->query("SELECT COUNT(x.uid) FROM xbt_snatched AS x WHERE x.uid='$UserID'");
list($Snatches) = $DB->next_record();

// get users class ids
$DB->query("SELECT Level FROM permissions WHERE isAutoPromote='1' ORDER BY Level");
$ClassIDs = $DB->to_array('Level');

$ClassIDs2 = array(); // clenaup
foreach ($ClassIDs as $ID) {
   $ClassIDs2[] = $ID['Level'];
}

$i = 0;
foreach ($ClassIDs2 as $ID) { // find current index
	$i++;
   if ($Class == $ID) break;   	
}

if ($i <= (count($ClassIDs2)-1)) $NextClassID = $ClassIDs2[$i]; // can promote
else {
	$NextClassID = $LoggedUser['Class']; // last class already or staff
	$LastClass = true;
}

// next class
$DB->query("SELECT p.ID, p.Name, p.Level, p.Color, p.reqWeeks, p.reqUploaded, p.reqTorrents, p.reqForumPosts, p.reqCredits, p.reqSnatches
                   FROM permissions AS p LEFT JOIN users_main AS u ON u.PermissionID=p.ID
                   WHERE p.Level=$NextClassID");
if ($DB->record_count()) {
   list($ID, $Name, $Level, $Color, $reqWeeks, $reqUploaded, $reqTorrents, $reqForumPosts, $reqCredits, $reqSnatches)=$DB->next_record();

$Weeks = round((time()-strtotime($JoinDate))/604800,2);

show_header('Next User Class');
?>
<div class="thin">
<h3>Required stats to progress to <span style="font-weight:bold;color: #<?=display_str($Color)?>"><?=$ClassLevels[$NextClassID]['Name']?></span>.</h3>
   <div class="box pad">
<table>
 <tr class="colhead">
   <td >Stat</td>
   <td >Required</td>
   <td >You Have</td>
   <td > </td>
 </tr>
 <tr>
   <td >Upload</td>
   <td >Need <?=$reqUploaded / pow(1024,3) . 'GB'?></td>
   <td >You Have <?=round($Uploaded / pow(1024,3)) . 'GB'?></td>
   <td style="text-align:center"><?php if($reqUploaded <= $Uploaded) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0">
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>
 <?php /*<tr>
   <td >Uploads</td>
   <td >Need <?=$reqTorrents?></td>
   <td >You Have <?=$Uploads?></td>
   <td style="text-align:center"><?php if($reqTorrents <= $Uploads) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0"> 
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>*/ ?>
 <tr>
   <td >Snatches</td>
   <td >Need <?=$reqSnatches?></td>
   <td >You Have  <?=$Snatches?></td>
   <td style="text-align:center"><?php if($reqSnatches <= $Snatches) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0"> 
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>   
 <tr>
   <td >Cubits</td>
   <td >Need <?=intval($reqCredits)?></td>
   <td >You Have <?=round($BonusCredits)?></td>
   <td style="text-align:center"><?php if($reqCredits <= $BonusCredits) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0"> 
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>   
 <tr>
   <?php /*<td >Forum Posts</td>
   <td >Need <?=$reqForumPosts?></td>
   <td >You Have <?=$ForumPosts?></td>
   <td style="text-align:center"><?php if($reqForumPosts <= $ForumPosts) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0"> 
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>*/ ?>
 <tr>
   <td >HnRs</td>
   <td >Need 0</td>
   <td >You Have <?=$HnRs?></td>
   <td style="text-align:center"><?php if($HnRs == 0) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0"> 
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>                 
 <tr>
   <td >Minimum time</td>
   <td >Need to have been a member for <?=$reqWeeks?> weeks</td>
   <td >You have been a member for <?=$Weeks?> <?php if($Weeks>1){?>weeks<?php }else{?>week<?php }?></td>
   <td style="text-align:center"><?php if($reqWeeks <= $Weeks) { ?> <img src="/static/common/images/ok.svg" width="15" height="15" border="0"> 
   <?php }else{?> <img src="/static/common/images/not.svg" width="15" height="15" border="0"> <?php }?></td>
 </tr>
</table>
<?php  } ?>
<form action="user.php" method="post" id="bonus">
   <input type="hidden" name="action" value="buy_promotion" />
   <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
   <br />
<?php if($LastClass) { ?>   
   Note: You are already at your last available class.
<?php } elseif($Warned != '0000-00-00 00:00:00') { ?>
   Note: You cannot purchase a promotion while you have an active warning.
<?php } elseif($reqUploaded <= $Uploaded && $reqTorrents <= $Uploads && $reqSnatches <= $Snatches && $reqCredits <= $BonusCredits &&
               $reqForumPosts <= $ForumPosts && $HnRs == 0 && $reqWeeks <= $Weeks) { ?>
   <a href="#" onclick="document.getElementById('bonus').submit();">Click Here</a> to purchase a promotion!
<?php  }else { ?>
   Note: Requirements are not met.
<?php  } ?>   
</form>                
  </div>
</div>

<?php
show_footer();
