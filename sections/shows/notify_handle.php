<?php

// for show page only

authorize();

if (!check_perms('site_torrents_notify')) { error(403); }

$ShowList = '';
$PeopleList = '';
$TagList = '';
$NotTagList = '';
$CategoryList = '';
$HasFilter = false;

if ($_GET['tags']) {
    $TagList = '|';
    $Tags = explode(',', strtolower($_GET['tags']));
    foreach ($Tags as $Tag) {
        $TagList.=db_string(trim($Tag)).'|';
    }
    $HasFilter = true;
}

if ($_GET['shows']) {
    $ShowList = '|';
    $Shows = explode(',', $_GET['shows']);
    foreach ($Shows as $Show) {
        $ShowList.=db_string(trim($Show)).'|';
    }
    $HasFilter = true;
}

if ($_GET['people']) {
    $PeopleList = '|';
    $People = explode(',', $_GET['people']);
    foreach ($People as $Person) {
        $PeopleList.=db_string(trim($Person)).'|';
    }
    $HasFilter = true;
}

if ($_GET['nottags']) {
    $NotTagList = '|';
    $Tags = explode(',', strtolower($_GET['nottags']));
    foreach ($Tags as $Tag) {
        $NotTagList.=db_string(trim($Tag)).'|';
    }
    $HasFilter = true;
}

if ($_GET['categories']) {
    $CategoryList = '|';
    foreach ($_GET['categories'] as $Category) {
        $CategoryList.=db_string(trim($Category)).'|';
    }
    $HasFilter = true;
}

if (!$HasFilter) {
    $Err = 'You must add at least one criterion to filter by';
} elseif (!$_GET['label'] && !$_GET['id']) {
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

$TagList = str_replace('||','|',$TagList);
$NotTagList = str_replace('||','|',$NotTagList);

if ($_GET['id'] && is_number($_GET['id'])) {
    $DB->query("UPDATE users_notify_filters SET
        Shows='$ShowList',
        People='$PeopleList',        
        Tags='$TagList',
        NotTags='$NotTagList',
        Categories='$CategoryList'
        WHERE ID='".db_string($_GET['id'])."' AND UserID='$LoggedUser[ID]'");
} else {
    $DB->query("INSERT INTO users_notify_filters
        (UserID, Label, Shows, People, Tags, NotTags, Categories)
        VALUES
        ('$LoggedUser[ID]','".db_string($_GET['label'])."', '$ShowList', '$PeopleList', '$TagList', '$NotTagList', '$CategoryList')");
}

$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
