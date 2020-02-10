<?php
if (!check_perms('admin_manage_parser')) { error(403); }

show_header('Manage upload parser rules', parser);

$DB->query("SELECT
    p.ID,
    p.Rules,
    p.Subject,
    p.UserID,
    p.TVMazeID,
    um.Username,
    p.Time
    FROM torrents_parser as p
    LEFT JOIN users_main AS um ON um.ID=p.UserID
    WHERE p.ID=1");

list($ID, $Rules, $Subject, $UserID, $TVMazeID, $UserName, $Time) = $DB->next_record();
$ID=1;
?>
<div class="thin">
<h2>Upload Parser Rules</h2>
<table id="testtable">
    <tr class="head">
        <td colspan="4">Test Upload Parser Rules</td>
    </tr>
    <tr class="colhead">
        <td colspan="3">
                    Title</span></td>
        <td width="10%"><span>
                    Submit</span></td>
    </tr>
    <tr class="rowa">
        <td colspan="3">
            <input id="test_title" class="long" type="text" name="test_title" value=""/>
        </td>
        <td>
            <button style="parser_test_rule" onclick="test_rules()">Test</button>
        </td>
    </tr>
</table>
<br/><br/>

<div id="loadrules" class="hidden"><?=base64_decode($Rules)?></div>
<table class="hidden">
    <tbody id="loadtemplate">
        <tr id="rule___INDEX__" class="__ROWCLASS__">
            <td>__INDEX__</td>
            <td>
                <input class="long"  type="text" name="pattern" value=""/>
            </td>
            <td>
                <input class="long"  type="text" name="replace" value="" />
            </td>
            <td>
                <input class="long"  type="text" name="tvmazeid" value="" />
            </td>
            <td style="text-align: center">
                <input type="checkbox" name="overwrite"/>
            </td>
            <td style="text-align: center">
                <input type="checkbox" name="tag"/>
            </td>
            <td style="text-align: center">
                <input type="checkbox" name="append"/>
            </td>
            <td style="text-align: center">
                <input type="checkbox" name="break"/>
            </td>
            <td>
                <input class="long"  type="text" name="comment" value=""/>
            </td>
            <td>
                <button style="parser_move_rule" onclick="move_rule_up(__INDEX__)">&uarr;</button>
                <button style="parser_move_rule" onclick="move_rule_down(__INDEX__)">&darr;</button>
            </td>
            <td>
                <button style="parser_del_rule" onclick="del_rule(__INDEX__)">del</button>
            </td>
        </tr>
    </tbody>
</table>

<div id="response"></div>
<br/>
<table id="rulestable">
    <tr class="head">
        <td colspan="3">Edit Upload Parser Rules</td>
        <td colspan="4">Operating on <?=$Subject?></td>
        <td colspan="4">Saved by 
<?php   if($UserID != 0) { ?>
        <?=format_username($UserID, $LoggedUser['Username'])?>&nbsp;<?=time_diff($Time, 1)?>
<?php   } ?>
    </td>
    </tr>
    <tr class="colhead">
        <td width="2%"><span>
                    Index</span></td>
        <td width="25%"><span title="Search PCRE pattern">
                    Pattern</span></td>
        <td width="20%"><span title="Replacement">
                    Replace</span></td>
        <td width="5%"><span>
                    TVMaze ID</span></td>
        <td width="2%"><span>
                    Overwrite</span></td>
        <td width="2%"><span>
                    Tag</span></td>
        <td width="2%"><span>
                    Append</span></td>
        <td width="2%"><span>
                    Break</span></td>
        <td width="10%"><span>
                    Comment</span></td>
        <td width="10%"><span>
                    Sort</span></td>
        <td width="2%"><span>
                    Delete</span></td>
    </tr>
</table>
<br/>
<table>
    <tr class="rowa">
        <td style="text-align: left">
            <div class="btn">
                <span>Export</span>
                <a id="export_button" class="magic_button" onclick="export_rules(this)">Export</a>
            </div>
            &nbsp;&nbsp;
            <div class="btn">
                <span>Import</span>
                <input type=file id="import_button" class="magic_button" onchange="import_rules()">
            </div>
         </td>
        </td>
        <td style="text-align: right">
            <button class="parser_reset_rules" onclick="reset_rules()">Reset</button>
            &nbsp;&nbsp;
            <button class="parser_save_rules" onclick="save_rules()">Save</button>
         </td>
    </tr>
</table>
<form id="rulesform" action="tools.php" method="post">
    <input type="hidden" name="action" value="po_alter" />
    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
    <input type="hidden" name="id" value="<?=$ID?>" />
</form>
</div>
<?php
show_footer();
