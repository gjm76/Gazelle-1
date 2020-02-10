<?php
if (!check_perms('site_view_flow')) { error(403); }
show_header('HnR Pool');
?>
<div class="thin">
    <h2>HnR Watch</h2>
<?php
define('USERS_PER_PAGE', 50);
list($Page,$Limit) = page_limit(USERS_PER_PAGE);

$RS = $DB->query("SELECT
    SQL_CALC_FOUND_ROWS
    m.ID,
    m.Username,
    m.Uploaded,
    m.Downloaded,
    m.PermissionID,
    m.Enabled,
    m.can_leech,
    i.Donor,
    i.Warned,
    i.JoinDate,
    i.HnRWatchEnds,
    m.HnR,
    i.HnRWatchTimes
    FROM users_main AS m
    LEFT JOIN users_info AS i ON i.UserID=m.ID
    WHERE i.HnRWatchEnds != '0000-00-00 00:00:00'
    AND m.Enabled = '1'
    ORDER BY i.HnRWatchEnds ASC LIMIT $Limit");
    
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();

$DB->query("SELECT COUNT(UserID) FROM users_info LEFT JOIN users_main AS m ON m.ID=UserID
            WHERE HnRWatchEnds != '0000-00-00 00:00:00'
            AND m.can_leech = '0'
            AND m.Enabled = '1'");
list($TotalDisabled) = $DB->next_record();

$DB->set_query_id($RS);

if ($DB->record_count()) {
?>
    <div class="box pad">
        <p>There are currently <?=number_format($Results)?> users queued by the system and <?=number_format($TotalDisabled)?> already leech disabled.</p>
    </div>
    <div class="linkbox">
<?php
    $Pages=get_pages($Page,$Results,USERS_PER_PAGE,11) ;
    echo $Pages;
?>
    </div>
    <table width="100%">
        <tr class="colhead">
            <td>User</td>
            <td>Up</td>
            <td>Down</td>
            <td>Ratio</td>
            <td>HnRs</td>
            <td title="Over Quota">Q</td>
            <td title="HnR Watch Times">C</td>
            <td>Leech</td>
            <td>Registered</td>
            <td>Remaining</td>
        </tr>
<?php
    while (list($UserID, $Username, $Uploaded, $Downloaded, $PermissionID, $Enabled, $Leech, $Donor, $Warned, $Joined, $HnRWatchEnds, $HnR, $HnRWatchTimes)=$DB->next_record()) {
    $Row = ($Row == 'b') ? 'a' : 'b';

?>
        <tr class="row<?=$Row?>">
            <td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
            <td><?=get_size($Uploaded)?></td>
            <td><?=get_size($Downloaded)?></td>
            <td><?=ratio($Uploaded, $Downloaded)?></td>
            <td><?=$HnR?></td>
            <td><?php  if ($HnR >= $SiteOptions['HnRThreshold']) { echo ($HnR - $SiteOptions['HnRThreshold']);}?></td>
            <td><?=$HnRWatchTimes?></td>
            <td><?php if($Leech){ echo "Enabled"; }else{ echo "Disabled"; }?></td>
            <td><?=time_diff($Joined,2)?></td>
            <td><?=time_diff($HnRWatchEnds)?></td>
        </tr>
<?php 	} ?>
    </table>
    <div class="linkbox">
<?php  echo $Pages; ?>
    </div>
<?php  } else { ?>
    <h2 align="center">There are currently no users on HnR watch.</h2>
<?php  }

$RS = $DB->query("SELECT
    SQL_CALC_FOUND_ROWS
    m.ID,
    m.Username,
    m.Uploaded,
    m.Downloaded,
    m.PermissionID,
    m.Enabled,
    m.can_leech,
    i.Donor,
    i.Warned,
    i.JoinDate,
    m.HnR,
    i.HnRWatchTimes
    FROM users_main AS m
    LEFT JOIN users_info AS i ON i.UserID=m.ID
    WHERE i.HnRWatchTimes AND m.Enabled = '1'
    ORDER BY i.HnRWatchTimes DESC LIMIT $Limit");

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();    

$DB->query("SELECT COUNT(UserID) FROM users_info LEFT JOIN users_main AS m ON m.ID=UserID
            WHERE HnRWatchTimes != '0'
            AND m.Enabled = '1'");
list($Total) = $DB->next_record();

$DB->set_query_id($RS);
    
if ($DB->record_count()) {
?>
    <h2>HnR Watch Times History</h2>
    <div class="box pad">
        <p>There are currently <?=$Total?> users in history.</p>
    </div>
    <div class="linkbox">
<?php
    $Pages=get_pages($Page,$Results,USERS_PER_PAGE,11) ;
    echo $Pages;
?>
    </div>
    <table width="100%">
        <tr class="colhead">
            <td>User</td>
            <td>Up</td>
            <td>Down</td>
            <td>Ratio</td>
            <td>HnRs</td>
            <td>Leech</td>
            <td>Registered</td>
            <td title="HnR Watch Times">Times</td>
        </tr>
<?php
    while (list($UserID, $Username, $Uploaded, $Downloaded, $PermissionID, $Enabled, $Leech, $Donor, $Warned, $Joined, $HnR, $HnRWatchTimes)=$DB->next_record()) {
    $Row = ($Row == 'b') ? 'a' : 'b';

?>
        <tr class="row<?=$Row?>">
            <td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
            <td><?=get_size($Uploaded)?></td>
            <td><?=get_size($Downloaded)?></td>
            <td><?=ratio($Uploaded, $Downloaded)?></td>
            <td><?=$HnR?></td>
            <td><?php if($Leech){ echo "Enabled"; }else{ echo "Disabled"; }?></td>
            <td><?=time_diff($Joined,2)?></td>
            <td><?=$HnRWatchTimes?></td>
        </tr>
<?php 	} ?>
    </table>
    <div class="linkbox">
<?php  echo $Pages; ?>
    </div>
<?php  } else { ?>
    <h2 align="center">There are currently no users on HnR Watch Times History.</h2>
<?php  } ?>
<?php
show_footer();
