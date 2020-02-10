<?php
if (!check_perms('site_torrents_notify')) { error(403); }
authorize();

$ShowList = '';
$PeopleList = '';
$TagList = '';
$NotTagList = '';
$CategoryList = '';
$HasFilter = false;

if ($_POST['tags']) {
    $TagList = '|';
    $Tags = explode(',', strtolower($_POST['tags']));
    foreach ($Tags as $Tag) {
        $TagList.=db_string(trim($Tag)).'|';
    }
    $HasFilter = true;
}

if ($_POST['shows']) {
    $ShowList = '|';
    $Shows = explode(',', $_POST['shows']);
    foreach ($Shows as $Show) {
        $ShowList.=db_string(trim($Show)).'|';
    }
    $HasFilter = true;
}

if ($_POST['people']) {
    $PeopleList = '|';
    $People = explode(',', $_POST['people']);
    foreach ($People as $Person) {
        $PeopleList.=db_string(trim($Person)).'|';
    }
    $HasFilter = true;
}

if ($_POST['nottags']) {
    $NotTagList = '|';
    $Tags = explode(',', strtolower($_POST['nottags']));
    foreach ($Tags as $Tag) {
        $NotTagList.=db_string(trim($Tag)).'|';
    }
    $HasFilter = true;
}

if ($_POST['categories']) {
    $CategoryList = '|';
    foreach ($_POST['categories'] as $Category) {
        $CategoryList.=db_string(trim($Category)).'|';
    }
    $HasFilter = true;
}

if (!$HasFilter) {
    $Err = 'You must add at least one criterion to filter by';
} elseif (!$_POST['label'] && !$_POST['id']) {
    $Err = 'You must add a label for the filter set';
}

if ($Err) {
    error($Err);
    header('Location: user.php?action=notify');
    die();
}

$ShowList = str_replace('||','|',$ShowList);
$ShowList = str_replace('.','',$ShowList); // fix .

$PeopleList = str_replace('||','|',$PeopleList);
$PeopleList = str_replace('.','',$PeopleList); // fix .
//$PeopleList = str_replace('\'','',$PeopleList); // fix '

$TagList = str_replace('||','|',$TagList);
$NotTagList = str_replace('||','|',$NotTagList);

if ($_POST['id'] && is_number($_POST['id'])) {
    $DB->query("UPDATE users_notify_filters SET
        Shows='$ShowList',
        People='$PeopleList',
        Tags='$TagList',
        NotTags='$NotTagList',
        Categories='$CategoryList'
        WHERE ID='".db_string($_POST['id'])."' AND UserID='$LoggedUser[ID]'");
} else {
    $DB->query("INSERT INTO users_notify_filters
        (UserID, Label, Shows, People, Tags, NotTags, Categories)
        VALUES
        ('$LoggedUser[ID]','".db_string($_POST['label'])."', '$ShowList', '$PeopleList', '$TagList', '$NotTagList', '$CategoryList')");
}

$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);

header('Location: user.php?action=notify');
