<?php
if (!check_perms('users_mod')) { error(403); }

if (isset($_REQUEST['addinvites'])) {
    authorize();
    $Invites = $_REQUEST['numinvites'];

    if (!is_number($Invites) || ($Invites < 0)) {	error("Please enter a valid number of invites."); }
    $sql = "UPDATE users_main SET Invites = Invites + $Invites WHERE Enabled = '1'";
    if (!isset($_REQUEST['leechdisabled'])) {
        $sql .= " AND can_leech = 1";
    }
    $DB->query($sql);
    $sql = "SELECT ID FROM users_main WHERE Enabled = '1'";
    if (!isset($_REQUEST['leechdisabled'])) {
        $sql .= " AND can_leech = 1";
    }
    $DB->query($sql);
    while (list($UserID) = $DB->next_record()) {
        $Cache->delete_value('user_info_heavy_'.$UserID);
    }
    $message = "<strong>$Invites invites added to all enabled users" . (!isset($_REQUEST['leechdisabled'])?' with enabled leeching privs':'') . '.</strong><br /><br />';
} elseif (isset($_REQUEST['clearinvites'])) {
    authorize();
    $Invites = $_REQUEST['numinvites'];

    if (!is_number($Invites) || ($Invites < 0)) {	error("Please enter a valid number of invites."); }

    if (isset($_REQUEST['onlydrop'])) {
        $Where = "WHERE Invites > $Invites";
    } elseif (!isset($_REQUEST['leechdisabled'])) {
        $Where = "WHERE (Enabled = '1' AND can_leech = 1) OR Invites > $Invites";
    } else {
        $Where = "WHERE Enabled = '1' OR Invites > $Invites";
    }
    $DB->query("SELECT ID FROM users_main $Where");
    $Users = $DB->to_array();
    $DB->query("UPDATE users_main SET Invites = $Invites $Where");

    foreach ($Users as $UserID) {
        list($UserID) = $UserID;
        $Cache->delete_value('user_info_heavy_'.$UserID);
    }
    $message = "<strong>$Invites invites set to all enabled users" . (!isset($_REQUEST['leechdisabled'])?' with enabled leeching privs':'') . '.</strong><br /><br />';
    $where = "";
}

show_header('Add invites sitewide');

?>
<div class="thin">
<h2>Add invites to all enabled users</h2>

<?php  $AlertClass = ' hidden';
       if (isset($message)) {
           $ResultMessage = $message;
           $AlertClass = '';
       }
?>
   <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>

<div class="box pad" style="margin-left: auto; margin-right: auto; text-align:center; max-width: 40%">
    <form action="" method="post">
        <input type="hidden" name="action" value="invites" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        Invites to add: <input type="text" name="numinvites" size="5" style="text-align: right" value="0"><br /><br />
        <label for="leechdisabled">Grant invites to leech disabled users: </label><input type="checkbox" id="leechdisabled" name="leechdisabled" value="1"><br /><br />
        <input type="submit" name="addinvites" value="Add invites" onclick="jQuery('#messagebarA').addClass('hidden');">
    </form>
</div>
<br />
<div class="box pad" style="margin-left: auto; margin-right: auto; text-align:center; max-width: 40%">
    <form action="" method="post">
        <input type="hidden" name="action" value="invites" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        Invites to set: <input type="text" name="numinvites" size="5" style="text-align: right" value="0"><br /><br />
        <span id="dropinvites" class=""><label for="onlydrop">Only affect users with at least this many invites: </label><input type="checkbox" id="onlydrop" name="onlydrop" value="1" onChange="$('#disabled').toggle();return true;"></span><br />
        <span id="disabled" class=""><label for="leechdisabled">Also add invites (as needed) to leech disabled users: </label><input type="checkbox" id="leechdisabled" name="leechdisabled" value="1" onChange="$('#dropinvites').toggle();return true;"></span><br /><br />
        <input type="submit" name="clearinvites" value="Set invites total" onclick="jQuery('#messagebarA').addClass('hidden');">
    </form>
</div>
</div>
<?php
show_footer();
