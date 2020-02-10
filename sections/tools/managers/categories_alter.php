<?php

if (!check_perms('admin_manage_categories')) { error(403); }

authorize();

if ($_POST['submit'] == 'Delete') {
    if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
    $DB->query('DELETE FROM categories WHERE ID='.$_POST['id']);
} else {
    $Val->SetFields('name', '1','string','The name must be set, and has a max length of 30 characters', array('maxlength'=>30, 'minlength'=>1));
    $Val->SetFields('tag', '1','string','The tag must be set, and has a max length of 255 characters', array('maxlength'=>255, 'minlength'=>1));
    $Val->SetFields('image', '1','string','The image must be set.', array('minlength'=>1));
    $Val->SetFields('min_upload_screenshots', '1','number','The Min Upload Screenshots must be set.', array('maxlength' =>255, 'minlength'=>0));
    $Err=$Val->ValidateForm($_POST); // Validate the form
    if ($Err) { error($Err); }

    $P=array();
    $P=db_array($_POST); // Sanitize the form

    $P['autofreeleech'] = (isset($_POST['autofreeleech']))? '1':'0';
    $P['autoreap']      = (isset($_POST['autoreap']))? '1':'0';

    if ($_POST['submit'] == 'Edit') { //Edit
        if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
        $DB->query("UPDATE categories SET
            name='$P[name]',
            image='$P[image]',
            tag='$P[tag]',
            autofreeleech='$P[autofreeleech]',
            autoreap='$P[autoreap]',
            min_upload_screenshots='$P[min_upload_screenshots]'
            WHERE id='$P[id]'");
    } else { //Create
        $DB->query("INSERT INTO categories
            (name, image, tag, autofreeleech, autoreap, min_upload_screenshots) VALUES
            ('$P[name]','$P[image]', '$P[tag]', '$P[autofreeleech]', '$P[autoreap]', '$P[min_upload_screenshots]')");
    }

}

$Cache->delete('new_categories');

// Go back
header('Location: tools.php?action=categories');
