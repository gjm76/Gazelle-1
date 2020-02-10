<?php
enforce_login();

// Number of users per page
define('FAVORITES_PER_PAGE', '20');

if (empty($_REQUEST['action'])) { $_REQUEST['action'] = 'view'; }
switch ($_REQUEST['action']) {
        
    case 'shows':
        require(SERVER_ROOT.'/sections/follows/shows.php');
        break;        

    default:
       include(SERVER_ROOT . '/sections/follows/shows.php');
       break;
}
