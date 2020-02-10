<?php
if (!check_perms('admin_manage_parser')) { error(403); }

authorize();

switch ($_POST['submit']) {
    case 'Delete':
        if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
        $DB->query("SELECT Sort FROM torrents_parser WHERE ID='$_POST[id]'");
        list($Sort) = $DB->next_record();
        $DB->query("DELETE FROM torrents_parser WHERE ID='$_POST[id]'");
        $DB->query("UPDATE torrents_parser SET Sort=(Sort-1) WHERE Sort >= $Sort");
        break;

    case 'Edit':
        $Val->SetFields('subject',   true, 'inarray', 'Subject must be set.',   ['title', 'filelist']);

        $Rules = json_decode($_POST['rules']);
	foreach($Rules as $Sort => $JSONRule) {

            $Rule = ['pattern'   => '', 
                     'replace'   => '', 
                     'tvmazeid'  => '', 
                     'overwrite' => '',
                     'tag'       => '',
                     'append'    => '',
                     'break'     => '',
                     'comment'   => '',
                     'sort'      => $Sort];

            foreach($Rule as $Key => $Value) {
                $Rule[$Key] = $JSONRule->$Key;
            }

            $Val->SetFields('pattern',   true,  'string',  'Pattern must be set.',   ['maxlength'=>255, 'minlength'=>1]);
            $Val->SetFields('replace',   false, 'string',  'Replace has a max length of 255 characters.',   ['maxlength'=>255]);
            $Val->SetFields('tvmazeid',  false, 'number',  'TVMaze ID is not valid');
            $Val->SetFields('overwrite', true,  'inarray', 'Overwrite must be set.', ['on']);
            $Val->SetFields('tag',       true,  'inarray', 'Tag must be set.',       ['on']);
            $Val->SetFields('append',    true, ' inarray', 'Append must be set.',    ['on']);
            $Val->SetFields('break',     true, ' inarray', 'Break must be set.',     ['on']);
            $Val->SetFields('comment',   false, 'string',  'Comment has a max length of 255 characters', ['maxlength'=>255]);
            $Err=$Val->ValidateForm($Rule); // Validate the rule
            if ($Err) { 
                echo("Rule $Sort: ".$Err);
                die();
            }

            // Save the rule array back
            $Rules[$Sort] = $Rule;
        }

	$ID=(int)$_POST[id];
	$Subject=db_string($_POST['subject']);
        $Rules=base64_encode(json_encode($Rules)); // Sanitize the form

        if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
        $DB->query("INSERT INTO torrents_parser (ID, Rules, Subject, UserID, TVMazeID, Time)
                    VALUES(
                            '$ID',
                            '$Rules',
                            '$Subject',
                            '$LoggedUser[ID]',
                            '$TVMazeID',
                            '".sqltime()."'
                           )
                    ON DUPLICATE KEY UPDATE
                            Rules='$Rules',
                            Subject='$Subject',
                            UserID='$LoggedUser[ID]',
                            TVMazeID='$TVMazeID',
                            Time='".sqltime()."'");

        break;
}
