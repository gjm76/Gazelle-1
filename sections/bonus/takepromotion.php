<?php
enforce_login();
authorize();

if (isset($_POST['userid'])) {
        if (!is_number($_POST['userid'])) { error(404); }
        $UserID = $_POST['userid'];
} else {
        $UserID = $LoggedUser['ID'];
}

if($UserID != $LoggedUser['ID'])  error(403);

show_header('Promotion purchase');
?>
    <h2>Promotion purchase result</h2>
    <div class="thin">
        <div class="head">Result</div>
        <div class="box pad ">
                    <h3 class="center body" style="white-space:pre">
<?php

// Retrieve the from/to pairs of user classes starting.  Should return a number of rows equivalent to the number of AutoPromote classes minus 1
$sql = "SELECT a.id \"From\", b.id \"To\", b.reqUploaded MinUpload, b.reqCredits MinCredits, IFNULL(b.reqTorrents,0) MinUploads,  IFNULL(b.reqSnatches,0) 
        MinSnatches, IFNULL(b.reqForumPosts,0) MinPosts, b.reqWeeks
        FROM (SELECT @curRow := @curRow + 1 AS rn, id FROM permissions JOIN (SELECT @curRow := -1) r WHERE isAutoPromote='1') a 
        JOIN (SELECT @curRow2 := @curRow2 + 1 AS rn, id, level, name, reqWeeks, reqUploaded, reqTorrents, reqForumPosts, reqCredits, reqSnatches
        FROM permissions JOIN (SELECT @curRow2 := -1) r WHERE isAutoPromote='1') b ON a.rn = b.rn-1";

$result = $DB->query($sql);

// Does the DB contain any AutoPromote classes?  If not, skip all this code
if (($result) && ($result->num_rows > 0))
{
    $Criteria = array();
    //convert query result into an associative array

    while ($row = $result->fetch_assoc())
    {
     $row[TotalUploaded] = 0;  // Deprecated, make sure it is set to zero, non-zero untested
     $row[MaxTime] = time_minus(3600*24*7*$row[reqWeeks]);
     $Criteria[] = $row;
    }

     $result->free();

     // Go through each of the from-to pairs
     foreach ($Criteria as $L) { // $L = Level
                $Query = "SELECT ID, Credits
                            FROM users_main JOIN users_info ON users_main.ID = users_info.UserID
                           WHERE PermissionID=".$L['From']."
                             AND Warned = '0000-00-00 00:00:00'
                             AND Uploaded >= $L[MinUpload]
                             AND JoinDate <= '$L[MaxTime]'
                             AND Credits >= '$L[MinCredits]'
                             AND ($L[MinUploads] = 0 OR (SELECT COUNT(*) FROM torrents WHERE userid = users_main.id) >= $L[MinUploads]) -- Short circuit, skip torrents unless needed
                             AND ($L[MinSnatches] = 0 OR (SELECT COUNT(*) FROM xbt_snatched WHERE uid = users_main.id) >= $L[MinSnatches]) -- Short circuit, skip torrents unless needed
                             AND ($L[MinPosts] = 0 OR (SELECT COUNT(*) FROM forums_posts WHERE authorid = users_main.id) >= $L[MinPosts]) -- Short circuit, skip posts unless needed 
                             AND Enabled='1'
                             AND HnR='0'
                             AND ID='$UserID'";

            if (!empty($L['Extra'])) {
                $Query .= ' AND '.$L['Extra'];
            }

            $DB->query($Query);

            list($UserID2, $Credits) = $DB->next_record();

            if ($UserID2 > 0) {
                $Cache->delete_value('user_info_'.$UserID2);
                $Cache->delete_value('user_info_heavy_'.$UserID2);
                $Cache->delete_value('user_stats_'.$UserID2);
                $Cache->delete_value('enabled_'.$UserID2);
   
                $Credits = $Credits - $L['MinCredits']; // cut the price               
                
                $DB->query("UPDATE users_info SET AdminComment = CONCAT('".sqltime()." - User bought promotion to [b][color=".str_replace(" ", "", $Classes[$L['To']]['Name'])."]".make_class_string($L['To'])."[/color][/b]\n', AdminComment) WHERE UserID = $UserID2");
                $DB->query("UPDATE users_main SET PermissionID=".$L['To']." WHERE ID = $UserID2");
                $DB->query("UPDATE users_main SET Credits=".$Credits." WHERE ID = $UserID2");
                echo "Successfully promoted to <b><span style='color:#".str_replace(" ", "", $Classes[$L['To']]['Color'])."'>".make_class_string($L['To'])."</span></b>.\n";
                
                $Summary = sqltime()." | -".$L['MinCredits']." credits | Promotion to ".make_class_string($L['To']);
                $UpdateSet="i.BonusLog=CONCAT_WS( '\n', '$Summary', i.BonusLog)";
                $sql = "UPDATE users_main AS m JOIN users_info AS i ON m.ID=i.UserID SET $UpdateSet WHERE m.ID='$UserID2'";
                $DB->query($sql);
                
                write_log("Promotion by " .$LoggedUser['Username'].' was bought to '.make_class_string($L['To']));
                break;
            } 
        }
}
if(!$UserID2) echo 'Requirements are not met.';
?>
</h3>
        </div>

        <div class="head">Return</div>
        <div class="box pad ">
         <form action="user.php" method="post" id="bonus">        
            <input type="hidden" name="action" value="next_class" />
            <a href="#" onclick="document.getElementById('bonus').submit();">Return to stat page</a>
         </form>        
        </div>
    </div>
<?php
show_footer();
