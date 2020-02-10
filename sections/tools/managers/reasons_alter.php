<?php

if (!check_perms('admin_manage_reasons')) { error(403); }

authorize();

if ($_POST['submit'] == 'Delete') {
    if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
    $DB->query('DELETE FROM review_reasons WHERE ID='.$_POST['id']);
} else {
    $Val->SetFields('sort', '1','number','Sort must be set.', array('maxlength' =>255, 'minlength'=>0));
    $Val->SetFields('name', '1','string','Name must be set, and has a max length of 255 characters', array('maxlength'=>255, 'minlength'=>1));
    $Val->SetFields('description', '1','string','Description must be set, and has a max length of 255 characters', array('maxlength'=>255, 'minlength'=>1));
    $Err=$Val->ValidateForm($_POST); // Validate the form
    if ($Err) { error($Err); }

    $P=array();
    $P=db_array($_POST); // Sanitize the form

    if ($_POST['submit'] == 'Edit') { //Edit
        if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
        $DB->query("UPDATE review_reasons SET
            ID='$P[id]',
            Sort='$P[sort]',
            Name='$P[name]',
            Description='$P[description]'
            WHERE id='$P[id]'");
    } else { // Create
        // cannot have same sort
        $DB->query("SELECT ID from review_reasons WHERE Sort = $_POST[sort]");
        list($Sort) = $DB->next_record();
        if($Sort) { error('Duplicate entry for the Sort value'); }

        $DB->query("INSERT INTO review_reasons
            (id, sort, name, description) VALUES
            ('$P[id]','$P[sort]', '$P[name]', '$P[description]')");
    }

}

//$Cache->delete('new_categories');

// Go back
header('Location: tools.php?action=reasons');
