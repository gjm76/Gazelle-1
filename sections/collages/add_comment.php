<?php
authorize();

if (empty($_POST['collageid']) || !is_number($_POST['collageid'])) { error(0); }

if (empty($_POST['body']) && $_POST['body'] !== '0') {
    error('You cannot post a comment with no content.');
}
$CollageID = $_POST['collageid'];

if ($LoggedUser['DisablePosting']) {
    error('Your posting rights have been removed'); // Should this be logged?
}

$Text = new TEXT;
$Text->validate_bbcode($_POST['body'],  get_permissions_advtags($LoggedUser['ID']));

flood_check('collages_comments');

$DB->query("INSERT INTO collages_comments
    (CollageID, Body, UserID, Time)
    VALUES
    ('$CollageID', '".db_string($_POST['body'])."', '$LoggedUser[ID]', '".sqltime()."')");

$CommentID = $DB->inserted_id();

$Cache->delete_value('collage_'.$CollageID.'_catalogue_0');
$Cache->delete_value('collage_'.$CollageID);
header('Location: collages.php?id='.$CollageID."#post$CommentID");
