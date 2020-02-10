<?php
enforce_login();
if (!check_perms('site_upload')) { error(403); }
if ($LoggedUser['DisableUpload']) {
    error('Your upload privileges have been revoked.');
}

include(SERVER_ROOT . '/sections/upload/functions.php');

if (!empty($_POST['submit'])) {
    include(SERVER_ROOT.'/sections/upload/upload_handle.php');

} else {

    switch ($_GET['action']) {
          case 'add_template': // ajax call
                include(SERVER_ROOT.'/sections/upload/add_template.php');
                break;
          case 'delete_template': // ajax call
                include(SERVER_ROOT.'/sections/upload/delete_template.php');
                break;
          case 'test_tvmaze_show':
                print_r(get_tvmaze_show_info($_GET['title']));
                break;
          case 'test_tvmaze_episode':
                print_r(get_tvmaze_episode_info($_GET['showid'], $_GET['season'], $_GET['episode']));
                break;

        default:
                include(SERVER_ROOT.'/sections/upload/upload.php');
    }
}
