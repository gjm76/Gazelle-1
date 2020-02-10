<?php
if (!check_perms('admin_imagehosts')) { error(403); }

authorize();

include(SERVER_ROOT . '/sections/upload/functions.php');

function update_banners($TVMazeID) {
    global $DB;

    $DB->query("SELECT ID FROM torrents_group WHERE TVMAZE={$TVMazeID}");
    $GroupIDs = $DB->collect('ID');

    $DB->query("UPDATE torrents_group AS tg
                  JOIN torrents_banners AS tb ON tg.TVMAZE=tb.TVMazeID
                   SET tg.Image=tb.BannerLink
                 WHERE tg.TVMAZE={$TVMazeID}"
              );

    foreach($GroupIDs as $GroupID) {
    	  update_show($GroupID);
        update_hash($GroupID);
    }
}

switch($_POST['submit']) {
    case 'Delete':
        if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
        $DB->query("DELETE FROM torrents_banners WHERE ID='$_POST[id]'");
        break;

    case 'Edit':
    case 'Create':
        if($_POST['tvdb_banner'])
              $_POST['link'] = upload_to_imagehost($_POST['tvdb_banner']);

//            We don't need to compess the files apparently
//            ob_start();
//            imagejpeg(imagecreatefromjpeg($_POST['tvdb_banner']), NULL, 90);            
//            $banner = base64_encode(ob_get_clean());

        $Val->SetFields('tvmaze', '1','number','The TVMaze ID must be set');
        $Val->SetFields('comment', '0','string','The description has a max length of 255 characters', array('maxlength'=>255));
        $Val->SetFields('link', '0','link','The goto link is not a valid url.', array('maxlength'=>255, 'minlength'=>1));
        $_POST['link'] = trim($_POST['link']); // stop whitespace errors on validating link input
        $_POST['comment'] = trim($_POST['comment']); // stop db from storing empty comments
        $Err=$Val->ValidateForm($_POST); // Validate the form
        if ($Err) { error($Err); }

        $P=array();
        $P=db_array($_POST); // Sanitize the form
        if ($_POST['submit'] == 'Edit') { //Edit
            if (!is_number($_POST['id']) || $_POST['id'] == '') { error(0); }
            $DB->query("UPDATE torrents_banners SET
                                TVMazeID='$P[tvmaze]',
                                BannerLink='$P[link]',
                                Comment='$P[comment]',
                                UserID='$LoggedUser[ID]'
                         WHERE ID='$P[id]'");
            update_banners($P['tvmaze']);
        } else { //Create
            $DB->query("INSERT INTO torrents_banners
                (TVMazeID, BannerLink, Comment, UserID, Time) VALUES
                ('$P[tvmaze]','$P[link]','$P[comment]','$LoggedUser[ID]','".sqltime()."')");
            update_banners($P['tvmaze']);
        }
}

// Go back
header('Location: tools.php?action=automatic_banners');
