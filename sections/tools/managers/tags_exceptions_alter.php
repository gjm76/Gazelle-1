<?php
enforce_login();
authorize();

if (!check_perms('site_manage_tags')) {
    error(403);
}
include(SERVER_ROOT . '/sections/torrents/functions.php');

$Message = '';
if (isset($_POST['doit'])) {

    if (isset($_POST['oldtags'])) {
        $OldTagIDs = $_POST['oldtags'];

        foreach ($OldTagIDs AS $OldTagID) {
            if (!is_number($OldTagID)) {
                error(403);
            }
            $DB->query("DELETE FROM tags_exceptions WHERE ID=$OldTagID");
        }
    }


    if ($_POST['newtag']) {
        $Tag = trim($Tag,'.'); // trim dots from the beginning and end
        $Tag = sanitize_tag($_POST['newtag']);
        $TagName = get_tag_synonym($Tag);
        $ExceptionType = $_POST['exceptiontype'];
        if(!in_array($ExceptionType, ['good', 'bad']))
            $Message .= "Malformed request";

        if ($Tag != $TagName) // this was a synonym replacement
            $Message .= "$Tag = $TagName. ";

        $DB->query("SELECT ID FROM tags_exceptions WHERE Name LIKE '" . $TagName . "'");
        list($TagID) = $DB->next_record();

        if ($TagID) {
            $DB->query("UPDATE tags_exceptions SET ExceptionType = '$ExceptionType' WHERE ID = $TagID");
        } else { // Tag doesn't exist yet - create tag
            $DB->query("INSERT INTO tags_exceptions (Name, UserID, ExceptionType)
                VALUES ('" . $TagName . "', " . $LoggedUser['ID'] . ", '$ExceptionType')");
            $TagID = $DB->inserted_id();
            $Message .= "Created $TagName. ";
        }
        $Message .= "Added $TagName to exceptions list.";
        $Result = 1;
    }

    $Cache->delete_value('good_tags');
    $Cache->delete_value('bad_tags');
}

if ($Message != '') {
    header("Location: tools.php?action=tags_exceptions&rst=$Result&msg=" . htmlentities($Message) .$anchor);
} else {
    header('Location: tools.php?action=tags_exceptions'.$anchor);
}
