<?php

if (!isset($_REQUEST['action'])) {
    include(SERVER_ROOT . '/sections/shows/shows.php');
} else {
    switch ($_REQUEST['action']) {
    	
        case 'autocomplete':
            enforce_login();
            include(SERVER_ROOT.'/sections/shows/autocomplete.php');
            break;

        case 'refresh':
            enforce_login();
            include(SERVER_ROOT.'/sections/shows/takeshowsrefresh.php');
            break;

        case 'takeshowedit':
            enforce_login();
            include(SERVER_ROOT.'/sections/shows/takeshowedit.php');
            break;

        default:
            include(SERVER_ROOT . '/sections/shows/shows.php');
            break;
    }
}