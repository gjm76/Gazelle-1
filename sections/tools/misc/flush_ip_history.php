<?php
if (!check_perms('users_view_ips')) {
    error(403);
}

show_header('Flush IP History');

$UserID = $_GET['userid'];
$ResultMessage = '';
$AlertClass = ' hidden';

if (!empty($UserID) && is_numeric($UserID)) {
   $DB->query("SELECT ID FROM users_main WHERE ID=$UserID");
	list($Found) = $DB->next_record();

	if($Found) {
      $DB->query("DELETE FROM users_history_ips WHERE UserID=$UserID");
      $ResultMessage = "Successfully flushed IP history for user with UserID: ".$UserID;
      $AlertClass = '';
      $DB->query("UPDATE users_info SET AdminComment = CONCAT('".sqltime()." - IP history flushed by ".$LoggedUser['Username']."\n', AdminComment) WHERE UserID = $UserID");
   }else {
      $ResultMessage = "User with UserID: ".$UserID." not found.";
      $AlertClass = ' alert';
   }   
}elseif(isset($UserID)) {
   $ResultMessage = 'Enter valid UserID';
   $AlertClass = ' alert';
}

?>
    <div class="thin">
    <h2>Flush IP History</h2>

    <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>

    <form method="get" action="" name="flush_ip_history">
        <input type="hidden" name="action" value="flush_ip_history" />
        <table cellpadding="2" cellspacing="1" border="0" align="center">
            <tr>
                <td align="right">UserID</td>
                <td align="left">
                    <input type="text" name="userid" id="userid" class="inputtext" value="<?=$_GET['userid']?>" />
                    <input type="submit" value="Flush" class="submit" />
                </td>
            </tr>
        </table>
    </form>
    </div>
<?php

show_footer();
