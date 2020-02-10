<?php

if (!isset($_REQUEST['action'])) {
    include(SERVER_ROOT . '/sections/people/people.php');
} else {
    switch ($_REQUEST['action']) {
    	
        case 'autocomplete':
            enforce_login();
            include(SERVER_ROOT . '/sections/people/autocomplete.php');
            break;

        default:
            include(SERVER_ROOT . '/sections/people/people.php');
            break;
    }
}