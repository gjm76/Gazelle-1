<?php

require 'classes/config.php';

if ($_REQUEST['key'] === SCHEDULE_KEY) {
    $hostname = gethostname();
    if ($hostname === "piraticis.eu") {
        echo shell_exec("/usr/bin/git fetch && /usr/bin/git reset --hard origin/develop 2>&1");
    } else {
        echo shell_exec("/usr/bin/git fetch && /usr/bin/git reset --hard origin/master 2>&1");
    }
} else {
    echo "An error has occured";
}