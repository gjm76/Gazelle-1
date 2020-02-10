<?php

if (!isset($_REQUEST['action'])) {
    include(SERVER_ROOT . '/sections/tvschedule/tvschedule.php');
} else {
    switch ($_REQUEST['action']) {

        default:
            include(SERVER_ROOT . '/sections/tvschedule/tvschedule.php');
            break;
    }
}
