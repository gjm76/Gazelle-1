<?php
/**********************************************************************
 *>>>>>>>>>>>>>>>>>>>>>>>>>>> User search <<<<<<<<<<<<<<<<<<<<<<<<<<<<*
 * Best viewed with a wide screen monitor							 *
 **********************************************************************/
$_GET['search'] = trim($_GET['search']);

if (!empty($_GET['search'])) {
    if (preg_match("/^".IP_REGEX."$/", $_GET['search'])) {
        $_GET['ip'] = $_GET['search'];
    } elseif (preg_match("/^".EMAIL_REGEX."$/i", $_GET['search'])) {
        $_GET['email'] = $_GET['search'];
    } elseif (preg_match('/^[a-z0-9_?\-\.]{1,20}$/iD',$_GET['search'])) {
        $DB->query("SELECT ID FROM users_main WHERE Username='".db_string($_GET['search'])."'");
        if (list($ID) = $DB->next_record()) {
            header('Location: user.php?id='.$ID);
            die();
        }
        $_GET['username'] = $_GET['search'];
    } else {
        $_GET['comment'] = $_GET['search'];
    }
}

define('USERS_PER_PAGE', 30);

function wrap($String, $ForceMatch = '', $IPSearch = false)
{
    if (!$ForceMatch) {
        global $Match;
    } else {
        $Match = $ForceMatch;
    }
    if ($Match == ' REGEXP ') {
        if (strpos($String, '\'') || preg_match('/^.*\\\\$/i', $String)) {
            error('Regex contains illegal characters.');
        }
    } else {
        $String = db_string($String);
    }
    if ($Match == ' LIKE ') {
        // Fuzzy search
        // Stick in wildcards at beginning and end of string unless string starts or ends with |
        if (($String[0] != '|') && !$IPSearch) {
            $String = '%'.$String;
        } elseif ($String[0] == '|') {
            $String = substr($String, 1, strlen($String));
        }

        if (substr($String, -1, 1) != '|') {
            $String = $String.'%';
        } else {
            $String = substr($String, 0, -1);
        }

    }
    $String="'$String'";

    return $String;
}

function date_compare($Field, $Operand, $Date1, $Date2 = '')
{
    $Date1 = db_string($Date1);
    $Date2 = db_string($Date2);
    $Return = array();

    switch ($Operand) {
        case 'on':
            $Return []= " $Field>='$Date1 00:00:00' ";
            $Return []= " $Field<='$Date1 23:59:59' ";
            break;
        case 'before':
            $Return []= " $Field<'$Date1 00:00:00' ";
            break;
        case 'after':
            $Return []= " $Field>'$Date1 23:59:59' ";
            break;
        case 'between':
            $Return []= " $Field>='$Date1 00:00:00' ";
            $Return []= " $Field<='$Date2 00:00:00' ";
            break;
    }

    return $Return;
}

function num_compare($Field, $Operand, $Num1, $Num2 = '')
{
    if ($Num1!=0) {
        $Num1 = db_string($Num1);
    }
    if ($Num2!=0) {
        $Num2 = db_string($Num2);
    }

    $Return = array();

    switch ($Operand) {
        case 'equal':
            $Return []= " $Field='$Num1' ";
            break;
        case 'above':
            $Return []= " $Field>'$Num1' ";
            break;
        case 'below':
            $Return []= " $Field<'$Num1' ";
            break;
        case 'between':
            $Return []= " $Field>'$Num1' ";
            $Return []= " $Field<'$Num2' ";
            break;
        default:
            print_r($Return);
            die();
    }

    return $Return;
}

// Arrays, regexes, and all that fun stuff we can use for validation, form generation, etc

$DateChoices = array('inarray'=>array('on', 'before', 'after', 'between'));
$SingleDateChoices = array('inarray'=>array('on', 'before', 'after'));
$NumberChoices = array('inarray'=>array('equal', 'above', 'below', 'between', 'buffer'));
$YesNo = array('inarray'=>array('any', 'yes', 'no'));
$OrderVals = array('inarray'=>array('Username', 'Ratio', 'IP', 'Email', 'Joined', 'Last Seen', 'Uploaded', 'Downloaded', 'Invites', 'Snatches', 
'Seeding Size', 'HnRs'));
$WayVals = array('inarray'=>array('Ascending', 'Descending'));

if (count($_GET)) {
    $DateRegex = array('regex'=>'/\d{4}-\d{2}-\d{2}/');

    $ClassIDs = array();
    foreach ($Classes as $ClassID => $Value) {
        $ClassIDs[]=$ClassID;
    }

    $Val->SetFields('comment','0','string','Comment is too long.', array('maxlength'=>512));
    $Val->SetFields('disabled_invites', '0', 'inarray', 'Invalid disabled_invites field', $YesNo);

    $Val->SetFields('joined', '0', 'inarray', 'Invalid joined field', $DateChoices);
    $Val->SetFields('join1', '0', 'regex', 'Invalid join1 field', $DateRegex);
    $Val->SetFields('join2', '0', 'regex', 'Invalid join2 field', $DateRegex);

    $Val->SetFields('lastactive', '0', 'inarray', 'Invalid lastactive field', $DateChoices);
    $Val->SetFields('lastactive1', '0', 'regex', 'Invalid lastactive1 field', $DateRegex);
    $Val->SetFields('lastactive2', '0', 'regex', 'Invalid lastactive2 field', $DateRegex);

    $Val->SetFields('ratio', '0', 'inarray', 'Invalid ratio field', $NumberChoices);
    $Val->SetFields('uploaded', '0', 'inarray', 'Invalid uploaded field', $NumberChoices);
    $Val->SetFields('downloaded', '0', 'inarray', 'Invalid downloaded field', $NumberChoices);
    //$Val->SetFields('snatched', '0', 'inarray', 'Invalid snatched field', $NumberChoices);

    $Val->SetFields('matchtype', '0', 'inarray', 'Invalid matchtype field', array('inarray'=>array('strict', 'fuzzy', 'regex')));

    $Val->SetFields('enabled', '0', 'inarray', 'Invalid enabled field', array('inarray'=>array('', 0, 1, 2)));
    $Val->SetFields('class', '0', 'inarray', 'Invalid class', array('inarray'=>$ClassIDs));
    $Val->SetFields('donor', '0', 'inarray', 'Invalid donor field', $YesNo);
    $Val->SetFields('warned', '0', 'inarray', 'Invalid warned field', $YesNo);
    $Val->SetFields('disabled_uploads', '0', 'inarray', 'Invalid disabled_uploads field', $YesNo);

    $Val->SetFields('order', '0', 'inarray', 'Invalid ordering', $OrderVals);
    $Val->SetFields('way', '0', 'inarray', 'Invalid way', $WayVals);

    $Val->SetFields('passkey', '0', 'string', 'Invalid passkey', array('maxlength'=>32));
    $Val->SetFields('avatar', '0', 'string', 'Avatar URL too long', array('maxlength'=>512));
    $Val->SetFields('stylesheet', '0', 'inarray', 'Invalid stylesheet', array_unique(array_keys($Stylesheets)));
    $Val->SetFields('cc', '0', 'inarray', 'Invalid Country Code', array('maxlength'=>2));
    
    $Val->SetFields('seedsize_opt', '0', 'inarray', 'Invalid Seeding Size', $NumberChoices);
    $Val->SetFields('hnr_opt', '0', 'inarray', 'Invalid HnR Value', $NumberChoices);

    $Err = $Val->ValidateForm($_GET);

    if (!$Err) {
        // Passed validation. Let's rock.
        $RunQuery = false; // if we should run the search

        if (isset($_GET['matchtype']) && $_GET['matchtype'] == 'strict') {
            $Match = ' = ';
        } elseif (isset($_GET['matchtype']) && $_GET['matchtype'] == 'regex') {
            $Match = ' REGEXP ';
        } else {
            $Match = ' LIKE ';
        }

        $OrderTable = array('Username'=>'um1.Username', 'Joined'=>'ui1.JoinDate', 'Email'=>'um1.Email', 'IP'=>'um1.IP', 'Last Seen'=>'um1.LastAccess', 
        'Uploaded'=>'um1.Uploaded', 'Downloaded'=>'um1.Downloaded', 'Ratio'=>'(um1.Uploaded/um1.Downloaded)', 'Invites'=>'um1.Invites', 
        'Snatches'=>'Snatches', 'Seeding Size'=>'SeedSize' , 'HnRs'=>'HnR');

        $WayTable = array('Ascending'=>'ASC', 'Descending'=>'DESC');

        $Where = array();
        $Having = array();
        $Join = array();
        $Group = array();
        $Distinct = '';
        $Order = '';

        $SQL = 'SQL_CALC_FOUND_ROWS
            um1.ID,
            um1.Username,
            um1.Uploaded,
            um1.Downloaded,';
        if ($_GET['snatched'] == "off") {
            $SQL .= "'X' AS Snatches,";
        } else {
            $SQL .= "(SELECT COUNT(uid) FROM xbt_snatched AS xs WHERE xs.uid=um1.ID) AS Snatches,";
        }
        $SQL .= 'um1.PermissionID,
            um1.Email,
            um1.Enabled,
            um1.IP,';
        if (empty($_GET['tracker_ip'])) {
            $SQL .= "'' AS TrackerIP1, ";
        } else {
            $SQL .= "xfu.ip AS TrackerIP1, ";
        }
        $SQL .= 'um1.Invites,
            ui1.DisableInvites,
            ui1.Warned,
            ui1.Donor,
            ui1.JoinDate,
            um1.LastAccess,
            um1.SeedSize, 
            um1.HnR 
            FROM users_main AS um1 JOIN users_info AS ui1 ON ui1.UserID=um1.ID ';


        if (!empty($_GET['username'])) {
            $Where[]='um1.Username'.$Match.wrap($_GET['username']);
        }

        if (!empty($_GET['email'])) {
            if (isset($_GET['email_history'])) {
                $Distinct = 'DISTINCT ';
                $Join['he']=' JOIN users_history_emails AS he ON he.UserID=um1.ID ';
                $Where[]= ' he.Email '.$Match.wrap($_GET['email']);
            } else {
                $Where[]='um1.Email'.$Match.wrap($_GET['email']);
            }
        }

        if (!empty($_GET['email_cnt'])) {
            $Query = "SELECT UserID FROM users_history_emails GROUP BY UserID HAVING COUNT(DISTINCT Email) ";
            if ($_GET['emails_opt'] === 'equal') {
                $operator = '=';
            }
            if ($_GET['emails_opt'] === 'above') {
                $operator = '>';
            }
            if ($_GET['emails_opt'] === 'below') {
                $operator = '<';
            }
            $Query .= $operator." ".$_GET['email_cnt'];
            $DB->query($Query);
            $Users = implode(',', $DB->collect('UserID'));
            if (!empty($Users)) {
                $Where[] = "um1.ID IN (".$Users.")";
            }
        }

        if (strlen($_GET['seedsize_cnt'])) {
        	   if(strlen($_GET['seedsize_cnt']))$_GET['seedsize_cnt'] = $_GET['seedsize_cnt']*1024*1024; // convert to bytes
            $Query = "SELECT ID FROM users_main WHERE SeedSize ";
            if ($_GET['seedsize_opt'] === 'equal') {
                $operator = '=';
            }
            if ($_GET['seedsize_opt'] === 'above') {
                $operator = '>';
            }
            if ($_GET['seedsize_opt'] === 'below') {
                $operator = '<';
            }
            $Query .= $operator." ".$_GET['seedsize_cnt'];
            $DB->query($Query);
            $Users = implode(',', $DB->collect('ID'));
            if (!empty($Users)) {
                $Where[] = "um1.ID IN (".$Users.")";
            }
        	   if(strlen($_GET['seedsize_cnt']))$_GET['seedsize_cnt'] = $_GET['seedsize_cnt']/1024/1024; // convert back to MB
        }


        if (strlen($_GET['hnr_cnt'])) {
            $Query = "SELECT ID FROM users_main WHERE HnR ";
            if ($_GET['hnr_opt'] === 'equal') {
                $operator = '=';
            }
            if ($_GET['hnr_opt'] === 'above') {
                $operator = '>';
            }
            if ($_GET['hnr_opt'] === 'below') {
                $operator = '<';
            }            
            $Query .= $operator." ".round($_GET['hnr_cnt']);
            $DB->query($Query);
            $Users = implode(',', $DB->collect('ID'));
            if (!empty($Users)) {
                $Where[] = "um1.ID IN (".$Users.")";
            }
        }
        
        if (!empty($_GET['ip'])) {
            if (isset($_GET['ip_history'])) {
                $Distinct = 'DISTINCT ';
                $Join['hi']=' JOIN users_history_ips AS hi ON hi.UserID=um1.ID ';
                $Where[]= ' hi.IP '.$Match.wrap($_GET['ip'], '', true);
            } else {
                $Where[]='um1.IP'.$Match.wrap($_GET['ip'], '', true);
            }
        }

        if (!empty($_GET['cc'])) {
            if ($_GET['cc_op'] == "equal") {
                $Where[]="um1.ipcc = '".$_GET['cc']."'";
            } else {
                $Where[]="um1.ipcc != '".$_GET['cc']."'";
            }
        }

        if (!empty($_GET['tracker_ip'])) {
                $Distinct = 'DISTINCT ';
                $Join['xfu']=' JOIN xbt_files_users AS xfu ON um1.ID=xfu.uid ';
                $Where[]= ' xfu.ip '.$Match.wrap($_GET['tracker_ip'], '', true);
        }

        if (!empty($_GET['comment'])) {
            $Where[]='ui1.AdminComment'.$Match.wrap($_GET['comment']);
        }


        if (strlen($_GET['invites1'])) {
            $Invites1 = round($_GET['invites1']);
            $Invites2 = round($_GET['invites2']);
            $Where[]=implode(' AND ', num_compare('Invites', $_GET['invites'], $Invites1, $Invites2));
        }

        if ($_GET['disabled_invites'] == 'yes') {
            $Where[]='ui1.DisableInvites=\'1\'';
        } elseif ($_GET['disabled_invites'] == 'no') {
            $Where[]='ui1.DisableInvites=\'0\'';
        }

        if ($_GET['disabled_uploads'] == 'yes') {
            $Where[]='ui1.DisableUpload=\'1\'';
        } elseif ($_GET['disabled_uploads'] == 'no') {
            $Where[]='ui1.DisableUpload=\'0\'';
        }

        if ($_GET['join1']) {
            $Where[]=implode(' AND ', date_compare('ui1.JoinDate', $_GET['joined'], $_GET['join1'], $_GET['join2']));
        }

        if ($_GET['lastactive1']) {
            $Where[]=implode(' AND ', date_compare('um1.LastAccess', $_GET['lastactive'], $_GET['lastactive1'], $_GET['lastactive2']));
        }

        if ($_GET['ratio1']) {
            $Decimals = strlen(array_pop(explode('.', $_GET['ratio1'])));
            if (!$Decimals) { $Decimals = 0; }

            $Where[]=implode(' AND ', num_compare("ROUND(Uploaded/Downloaded,$Decimals)", $_GET['ratio'], $_GET['ratio1'], $_GET['ratio2']));
        }

        if (strlen($_GET['uploaded1'])) {
            $Upload1 = round($_GET['uploaded1']);
            $Upload2 = round($_GET['uploaded2']);
            if ($_GET['uploaded']!='buffer') {
                $Where[]=implode(' AND ', num_compare('ROUND(Uploaded/1024/1024/1024)', $_GET['uploaded'], $Upload1, $Upload2));
            } else {
                $Where[]=implode(' AND ', num_compare('ROUND((Uploaded/1024/1024/1024)-(Downloaded/1024/1024/1023))', 'between', $Upload1*0.9, $Upload1*1.1));
            }
        }

        if (strlen($_GET['downloaded1'])) {
            $Download1 = round($_GET['downloaded1']);
            $Download2 = round($_GET['downloaded2']);
            $Where[]=implode(' AND ', num_compare('ROUND(Downloaded/1024/1024/1024)', $_GET['downloaded'], $Download1, $Download2));
        }

        if (strlen($_GET['snatched1'])) {
            $Snatched1 = round($_GET['snatched1']);
            $Snatched2 = round($_GET['snatched2']);
            $Having[]=implode(' AND ', num_compare('Snatches', $_GET['snatched'], $Snatched1, $Snatched2));
        }

        if ($_GET['enabled']!='') {
            $Where[]='um1.Enabled='.wrap($_GET['enabled'], '=');
        }

        if ($_GET['class']!='') {
            $Where[]='um1.PermissionID='.wrap($_GET['class'], '=');
        }

        if ($_GET['donor'] == 'yes') {
            $Where[]='ui1.Donor=\'1\'';
        } elseif ($_GET['donor'] == 'no') {
            $Where[]='ui1.Donor=\'0\'';
        }

        if ($_GET['warned'] == 'yes') {
            $Where[]='ui1.Warned!=\'0000-00-00 00:00:00\'';
        } elseif ($_GET['warned'] == 'no') {
            $Where[]='ui1.Warned=\'0000-00-00 00:00:00\'';
        }

        if ($_GET['disabled_ip']) {
            $Distinct = 'DISTINCT ';
            if ($_GET['ip_history']) {
                if (!isset($Join['hi'])) {
                    $Join['hi']=' JOIN users_history_ips AS hi ON hi.UserID=um1.ID ';
                }
                $Join['hi2']=' JOIN users_history_ips AS hi2 ON hi2.IP=hi.IP ';
                $Join['um2']=' JOIN users_main AS um2 ON um2.ID=hi2.UserID AND um2.Enabled=\'2\' ';
            } else {
                $Join['um2']=' JOIN users_main AS um2 ON um2.IP=um1.IP AND um2.Enabled=\'2\' ';
            }
        }

        if (!empty($_GET['passkey'])) {
            $Where[]='um1.torrent_pass'.$Match.wrap($_GET['passkey']);
        }

        if (!empty($_GET['avatar'])) {
            $Where[]='ui1.Avatar'.$Match.wrap($_GET['avatar']);
        }

        if ($_GET['stylesheet']!='') {
            $Where[]='ui1.StyleID='.wrap($_GET['stylesheet'], '=');
        }

        if ($OrderTable[$_GET['order']] && $WayTable[$_GET['way']]) {
            $Order = ' ORDER BY '.$OrderTable[$_GET['order']].' '.$WayTable[$_GET['way']].' ';
        }

        //---------- Finish generating the search string

        $SQL = 'SELECT '.$Distinct.$SQL;
        $SQL .= implode(' ', $Join);

        if (count($Where)) {
            $SQL .= ' WHERE '.implode(' AND ', $Where);
        }

        if (count($Having)) {
            $SQL .= ' HAVING '.implode(' AND ', $Having);
        }
        $SQL .= $Order;

        if (count($Where)>0 || count($Join)>0 || count($Having)>0) {
            $RunQuery = true;
        }

        list($Page,$Limit) = page_limit(USERS_PER_PAGE);
        $SQL.=" LIMIT $Limit";
    } else { error($Err); }

}
show_header('User search');
?>
<div class="thin">
    <form action="user.php" method="get">
        <input type="hidden" name="action" value="search" />
        <table style="table-layout: auto;">
            <tr>
                <td class="label" style="width:3%">Username:</td>
                <td width="1%">
                    <input type="text" name="username" size="10" value="<?=display_str($_GET['username'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Joined:</td>
                <td width="25%">
                    <select name="joined">
                        <option value="on"<?php  if ($_GET['joined']==='on') {echo ' selected="selected"';}?>>On</option>
                        <option value="before"<?php  if ($_GET['joined']==='before') {echo ' selected="selected"';}?>>Before</option>
                        <option value="after"<?php  if ($_GET['joined']==='after') {echo ' selected="selected"';}?>>After</option>
                        <option value="between"<?php  if ($_GET['joined']==='between') {echo ' selected="selected"';}?>>Between</option>
                    </select>
                    <input type="text" name="join1" size="3" value="<?=display_str($_GET['join1'])?>" />
                    <input type="text" name="join2" size="3" value="<?=display_str($_GET['join2'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Enabled:</td>
                <td>
                    <select name="enabled">
                        <option value="" <?php  if ($_GET['enabled']==='') {echo ' selected="selected"';}?>>Any</option>
                        <option value="0"<?php  if ($_GET['enabled']==='0') {echo ' selected="selected"';}?>>Unconfirmed</option>
                        <option value="1"<?php  if ($_GET['enabled']==='1') {echo ' selected="selected"';}?>>Enabled</option>
                        <option value="2"<?php  if ($_GET['enabled']==='2') {echo ' selected="selected"';}?>>Disabled</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label" style="width:3%">Email:</td>
                <td>
                    <input type="text" name="email" size="10" value="<?=display_str($_GET['email'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Last active:</td>
                <td>
                    <select name="lastactive">
                        <option value="on"<?php  if ($_GET['lastactive']==='on') {echo ' selected="selected"';}?>>On</option>
                        <option value="before"<?php  if ($_GET['lastactive']==='before') {echo ' selected="selected"';}?>>Before</option>
                        <option value="after"<?php  if ($_GET['lastactive']==='after') {echo ' selected="selected"';}?>>After</option>
                        <option value="between"<?php  if ($_GET['lastactive']==='between') {echo ' selected="selected"';}?>>Between</option>
                    </select>
                    <input type="text" name="lastactive1" size="3" value="<?=display_str($_GET['lastactive1'])?>" />
                    <input type="text" name="lastactive2" size="3" value="<?=display_str($_GET['lastactive2'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Class:</td>
                <td>
                    <select name="class">
                        <option value="" <?php  if ($_GET['class']==='') {echo ' selected="selected"';}?>>Any</option>
<?php 	foreach ($ClassLevels as $Class) { ?>
                    <option value="<?=$Class['ID'] ?>" <?php  if ($_GET['class']===$Class['ID']) {echo ' selected="selected"';}?>><?=cut_string($Class['Name'], 10, 1, 1).' ('.$Class['Level'].')'?></option>
<?php 	} ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label" style="width:3%">IP:</td>
                <td>
                    <input type="text" name="ip" size="10" value="<?=display_str($_GET['ip'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Ratio:</td>
                <td>
                    <select name="ratio">
                        <option value="equal"<?php  if ($_GET['ratio']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['ratio']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['ratio']==='below') {echo ' selected="selected"';}?>>Below</option>
                        <option value="between"<?php  if ($_GET['ratio']==='between') {echo ' selected="selected"';}?>>Between</option>
                    </select>
                    <input type="text" name="ratio1" size="3" value="<?=display_str($_GET['ratio1'])?>" />
                    <input type="text" name="ratio2" size="3" value="<?=display_str($_GET['ratio2'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Donor:</td>
                <td>
                    <select name="donor">
                        <option value="" <?php  if ($_GET['donor']==='') {echo ' selected="selected"';}?>>Any</option>
                        <option value="yes" <?php  if ($_GET['donor']==='yes') {echo ' selected="selected"';}?>>Yes</option>
                        <option value="no" <?php  if ($_GET['donor']==='no') {echo ' selected="selected"';}?>>No</option>
                    </select>
                </td>

            </tr>
            <tr>
                <td class="label" style="width:3%">Comment:</td>
                <td>
                    <input type="text" name="comment" size="10" value="<?=display_str($_GET['comment'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Uploaded:</td>
                <td>
                    <select name="uploaded">
                        <option value="equal"<?php  if ($_GET['uploaded']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['uploaded']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['uploaded']==='below') {echo ' selected="selected"';}?>>Below</option>
                        <option value="between"<?php  if ($_GET['uploaded']==='between') {echo ' selected="selected"';}?>>Between</option>
                        <option value="buffer"<?php  if ($_GET['uploaded']==='buffer') {echo ' selected="selected"';}?>>Buffer</option>
                    </select>
                    <input type="text" name="uploaded1" size="3" value="<?=display_str($_GET['uploaded1'])?>" />
                    <input type="text" name="uploaded2" size="3" value="<?=display_str($_GET['uploaded2'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Warned:</td>
                <td>
                    <select name="warned">
                        <option value="" <?php  if ($_GET['warned']==='') {echo ' selected="selected"';}?>>Any</option>
                        <option value="yes" <?php  if ($_GET['warned']==='yes') {echo ' selected="selected"';}?>>Yes</option>
                        <option value="no" <?php  if ($_GET['warned']==='no') {echo ' selected="selected"';}?>>No</option>
                    </select>
                </td>

            </tr>
            <tr>
                <td class="label" style="width:3%">Invites:</td>
                <td>
                    <select name="invites">
                        <option value="equal"<?php  if ($_GET['invites']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['invites']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['invites']==='below') {echo ' selected="selected"';}?>>Below</option>
                        <option value="between"<?php  if ($_GET['invites']==='between') {echo ' selected="selected"';}?>>Between</option>
                    </select>
                    <br />
                    <input type="text" name="invites1" size="3" value="<?=display_str($_GET['invites1'])?>" />
                    <input type="text" name="invites2" size="3" value="<?=display_str($_GET['invites2'])?>" />

                </td>
                <td class="label nobr" style="width:3%">Downloaded:</td>
                <td>
                    <select name="downloaded">
                        <option value="equal"<?php  if ($_GET['downloaded']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['downloaded']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['downloaded']==='below') {echo ' selected="selected"';}?>>Below</option>
                        <option value="between"<?php  if ($_GET['downloaded']==='between') {echo ' selected="selected"';}?>>Between</option>
              table-layout: fixed;      </select>
                    <input type="text" name="downloaded1" size="3" value="<?=display_str($_GET['downloaded1'])?>" />
                    <input type="text" name="downloaded2" size="3" value="<?=display_str($_GET['downloaded2'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Disabled IP:</td>
                <td>
                    <input type="checkbox" name="disabled_ip" <?php  if ($_GET['disabled_ip']) { echo ' checked="checked"'; }?> />
                </td>

            </tr>
            <tr>
                <td class="label" style="width:3%">Disabled<br />invites</td>
                <td>
                    <select name="disabled_invites">
                        <option value="" <?php  if ($_GET['disabled_invites']==='') {echo ' selected="selected"';}?>>Any</option>
                        <option value="yes" <?php  if ($_GET['disabled_invites']==='yes') {echo ' selected="selected"';}?>>Yes</option>
                        <option value="no" <?php  if ($_GET['disabled_invites']==='no') {echo ' selected="selected"';}?>>No</option>
                    </select>
                </td>
                <td class="label nobr" style="width:3%">Snatched:</td>
                <td>
                    <select name="snatched">
                        <option value="equal"<?php  if (isset($_GET['snatched']) && $_GET['snatched']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if (isset($_GET['snatched']) && $_GET['snatched']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if (isset($_GET['snatched']) && $_GET['snatched']==='below') {echo ' selected="selected"';}?>>Below</option>
                        <option value="between"<?php  if (isset($_GET['snatched']) && $_GET['snatched']==='between') {echo ' selected="selected"';}?>>Between</option>
                        <option value="off"<?php  if (isset($_GET['snatched']) && $_GET['snatched']==='off') {echo ' selected="selected"';}?>>Off</option>
                    </select>
                    <input type="text" name="snatched1" size="3" value="<?=display_str($_GET['snatched1'])?>" />
                    <input type="text" name="snatched2" size="3" value="<?=display_str($_GET['snatched2'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Disabled<br />uploads</td>
                <td>
                    <select name="disabled_uploads">
                        <option value="" <?php  if (isset($_GET['disabled_uploads']) && $_GET['disabled_uploads']==='') {echo ' selected="selected"';}?>>Any</option>
                        <option value="yes" <?php  if (isset($_GET['disabled_uploads']) && $_GET['disabled_uploads']==='yes') {echo ' selected="selected"';}?>>Yes</option>
                        <option value="no" <?php  if (isset($_GET['disabled_uploads']) && $_GET['disabled_uploads']==='no') {echo ' selected="selected"';}?>>No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label" style="width:3%">Passkey:</td>
                <td>
                    <input type="text" name="passkey" size="10" value="<?=display_str($_GET['passkey'])?>" />
                </td>
                <td class="label">Tracker IP:</td>
                <td>
                    <input type="text" name="tracker_ip" size="10" value="<?=display_str($_GET['tracker_ip'])?>" />
                </td>
                <td class="label nobr" style="width:3%">Extra</td>
                <td>
                    <input type="checkbox" name="ip_history" id="ip_history"<?php  if ($_GET['ip_history']) { echo ' checked="checked"'; }?> />
                    <label for="ip_history">IP History</label>

                    <input type="checkbox" name="email_history" id="email_history"<?php  if ($_GET['email_history']) { echo ' checked="checked"'; }?> />
                    <label for="email_history">Email History</label>
                </td>
            </tr>
            <tr>
                <td class="label" style="width:3%">Avatar:</td>
                <td>
                    <input type="text" name="avatar" size="10" value="<?=display_str($_GET['avatar'])?>" />
                </td>
                <td class="label">Stylesheet:</td>
                <td>
                    <select name="stylesheet" id="stylesheet">
                        <option value="">Any</option>
<?php  foreach ($Stylesheets as $Style) { ?>
                        <option value="<?=$Style['ID']?>"<?php selected('stylesheet',$Style['ID'])?>><?=$Style['ProperName']?></option>
<?php  } ?>
                    </select>
                </td>
                <td class="label nobr" style="width:3%">Country Code:</td>
                <td width="5%">
                    <select name="cc_op">
                        <option value="equal"<?php  if ($_GET['cc_op']==='equal') { echo ' selected="selected"';}?>>Equals</option>
                        <option value="not_equal"<?php  if ($_GET['cc_op']==='not_equal') { echo ' selected="selected"';}?>>Not Equal</option>
                    </select>
                    <input type="text" name="cc" size="2" value="<?=display_str($_GET['cc'])?>" />
                </td>
            </tr>
            <tr>
                <td class="label" style="width:3%">Seeding<br />Size:</td>
                <td class="nobr">
                    <select name="seedsize_opt">
                        <option value="equal"<?php  if ($_GET['seedsize_opt']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['seedsize_opt']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['seedsize_opt']==='below') {echo ' selected="selected"';}?>>Below</option>
                    </select>
                    <input type="text" name="seedsize_cnt" size="4" value="<?=display_str($_GET['seedsize_cnt'])?>" /> MB
                </td>
                <td class="label">HnRs:</td>
                <td class="nobr">
                    <select name="hnr_opt">
                        <option value="equal"<?php  if ($_GET['hnr_opt']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['hnr_opt']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['hnr_opt']==='below') {echo ' selected="selected"';}?>>Below</option>                        
                    </select>
                    <input type="text" name="hnr_cnt" size="4" value="<?=display_str($_GET['hnr_cnt'])?>" />
                </td>                
                <td class="label nobr" style="width:3%"># Of Emails:</td>
                <td>
                    <select name="emails_opt">
                        <option value="equal"<?php  if ($_GET['emails_opt']==='equal') {echo ' selected="selected"';}?>>Equal</option>
                        <option value="above"<?php  if ($_GET['emails_opt']==='above') {echo ' selected="selected"';}?>>Above</option>
                        <option value="below"<?php  if ($_GET['emails_opt']==='below') {echo ' selected="selected"';}?>>Below</option>
                    </select>
                    <input type="text" name="email_cnt" size="3" value="<?=display_str($_GET['email_cnt'])?>" />
                </td>
            </tr> 
            <tr>
                <td class="label" style="width:3%">Type</td>
                <td>
                    Strict <input type="radio" name="matchtype" value="strict"<?php  if ($_GET['matchtype'] == 'strict' || !$_GET['matchtype']) { echo ' checked="checked"'; } ?> /> |
                    Fuzzy <input type="radio" name="matchtype" value="fuzzy"<?php  if ($_GET['matchtype'] == 'fuzzy' || !$_GET['matchtype']) { echo ' checked="checked"'; } ?> /> |
                    Regex <input type="radio" name="matchtype" value="regex"<?php  if ($_GET['matchtype'] == 'regex') { echo ' checked="checked"'; } ?> />
                </td>
                <td class="label">Order by:</td>
                <td colspan="2">
                    <select name="order">
                    <?php
                        foreach (array_shift($OrderVals) as $Cur) { ?>
                        <option value="<?=$Cur?>"<?php  if (isset($_GET['order']) && $_GET['order'] == $Cur || (!isset($_GET['order']) && $Cur == 'Joined')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
                    <?php 	}?>
                    </select>
                    <select name="way">
                    <?php 	foreach (array_shift($WayVals) as $Cur) { ?>
                        <option value="<?=$Cur?>"<?php  if (isset($_GET['way']) && $_GET['way'] == $Cur || (!isset($_GET['way']) && $Cur == 'Descending')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
                    <?php 	}?>
                    </select>
                </td>
                <td class="center" colspan="2">
                    <input type="submit" value="Search users" />
                </td>                                  
            </tr>
        </table>
    </form>
</div>
<?php
if ($RunQuery) {
    $Results = $DB->query($SQL);
    $DB->query('SELECT FOUND_ROWS();');
    list($NumResults) = $DB->next_record();
$DB->set_query_id($Results);
}
?>
<div class="linkbox">
<?php
$Pages=get_pages($Page,$NumResults,USERS_PER_PAGE,11);
echo $Pages;
?>
</div>
<div class="box pad center">
    <table width="100%">
        <tr class="colhead">
            <td>Username</td>
            <td>Ratio</td>
            <td>IP</td>
            <td>Email</td>
            <td>Joined</td>
            <td>Last Seen</td>
            <td>Upload</td>
            <td>Download</td>
            <td>Seeding Size</td>
            <td>HnRs</td>
            <td title="downloads (number of torrent files downloaded)">Dlds</td>
            <td title="snatched (number of torrents completed)">Sn'd</td>
            <td title="invites">Inv's</td>
        </tr>
<?php
while(list($UserID, $Username, $Uploaded, $Downloaded, $Snatched, $Class, $Email, $Enabled, $IP, $trackerIP1,
                                        $Invites, $DisableInvites, $Warned, $Donor, $JoinDate, $LastAccess, $SeedSize, $HnRs) = $DB->next_record()){ ?>
        <tr>
            <td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $Class)?></td>
            <td><?=ratio($Uploaded, $Downloaded)?></td>
            <td><?="<span title=\"account ip\">".display_ip($IP)."</span>";
                  if($trackerIP1) echo "<br/><span title=\"current tracker ip\">".display_ip($trackerIP1)."</span>";
                  //if($trackerIP2) echo "<br/><span title=\"tracker ip history\">".display_ip($trackerIP2)."</span>";
            ?>
            </td>
            <td style="word-break:break-all;" title="<?=display_str($Email)?>"><?=display_str($Email)?></td>
            <td><?=time_diff($JoinDate)?></td>
            <td><?=time_diff($LastAccess)?></td>
            <td><?=get_size($Uploaded)?></td>
            <td><?=get_size($Downloaded)?></td>
            <td <?=(!$SeedSize)?' class="r00"':''?>><?=get_size($SeedSize)?></td>
            <td <?=($HnRs)?' class="r00"':''?>><?=$HnRs?></td>
<?php $DB->query("SELECT COUNT(ud.UserID) FROM users_downloads AS ud JOIN torrents AS t ON t.ID = ud.TorrentID WHERE ud.UserID = ".$UserID);
            list($Downloads) = $DB->next_record();
            $DB->set_query_id($Results);
?>
            <td><?=(int) $Downloads?></td>
            <td><?=is_numeric($Snatched) ? number_format($Snatched) : display_str($Snatched)?></td>
            <td><?php  if ($DisableInvites) { echo 'X'; } else { echo $Invites; } ?></td>
        </tr>
<?php
}
?>
    </table>
</div>
<div class="linkbox">
<?=$Pages?>
</div>
<?php
show_footer();
