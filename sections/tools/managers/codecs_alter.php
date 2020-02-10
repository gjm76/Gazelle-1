<?php
if (!check_perms('admin_manage_parser')) { error(403); }

authorize();

if ($_POST['submit'] == 'Delete') { //Delete
    if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
    if (isset($_POST['modify']) && $_POST['modify'] == 'codec') {
        $DB->query("DELETE FROM torrents_codecs WHERE ID='$_POST[id]'");
        $DB->query("DELETE FROM torrents_codecs_alt WHERE CodecID='$_POST[id]'");
        // Go back
        header('Location: tools.php?action=automatic_codecs');
    }
    if (isset($_POST['modify']) && $_POST['modify'] == 'codec_alt') {
        $DB->query("SELECT CodecID FROM torrents_codecs_alt WHERE ID='$_POST[id]'");
        list($CodecID) = $DB->next_record();
        $DB->query("DELETE FROM torrents_codecs_alt WHERE ID='$_POST[id]'");
        // Go back
        header('Location: tools.php?action=automatic_codecs#codec_'.$CodecID);
    }

} else if ($_POST['modify'] == 'codec') { //Edit & Create, Shared Validation
    $Val->SetFields('codec', true, 'string', 'The codec must be set, and has a max length of 255 characters', array('maxlength'=>255, 'minlength'=>1));
    $Val->SetFields('sort',  true, 'number', 'You did not enter a valid number for sort.');
    //$_POST['codec'] = trim($_POST['codec']); // stop db from storing empty comments
    //$_POST['sort']  = trim($_POST['sort']); // stop db from storing empty comments
    $Err=$Val->ValidateForm($_POST); // Validate the form
    if ($Err) { error($Err); }

    $P=array();
    $P=db_array($_POST); // Sanitize the form
    if ($_POST['submit'] == 'Edit') { //Edit
        if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
        $DB->query("UPDATE torrents_codecs SET
                            Codec='$P[codec]',
                            Sort='$P[sort]'
                     WHERE ID='$P[id]'");
    } else { //Create
        $DB->query("INSERT INTO torrents_codecs
            (Codec, Sort) VALUES
            ('$P[codec]','$P[sort]')");

        $CodecID = $DB->inserted_id();

        $DB->query("INSERT INTO torrents_codecs_alt
            (CodecID, AltCodec) VALUES
            ($CodecID, '$P[codec]')");
    }

    // Go back
    header('Location: tools.php?action=automatic_codecs');

} else if ($_POST['modify'] == 'codec_alt') { //Edit & Create, Shared Validation
    $Val->SetFields('codec', true, 'string', 'The codec must be set, and has a max length of 255 characters', array('maxlength'=>255, 'minlength'=>1));
    //$_POST['codec'] = trim($_POST['codec']); // stop db from storing empty comments
    $Err=$Val->ValidateForm($_POST); // Validate the form
    if ($Err) { error($Err); }

    $P=array();
    $P=db_array($_POST); // Sanitize the form
    if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
    if ($_POST['submit'] == 'Edit') { //Edit
        $DB->query("UPDATE torrents_codecs_alt SET
                            AltCodec='$P[codec]'
                     WHERE ID='$P[id]'");
    } else { //Create
        $DB->query("INSERT INTO torrents_codecs_alt
            (CodecID, AltCodec) VALUES
            ($P[id],'$P[codec]')");
    }

    // Go back
    header('Location: tools.php?action=automatic_codecs#codec_'.$P[id]);

}

