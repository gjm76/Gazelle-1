<?php

enforce_login();
authorize();

if (!check_perms('torrents_delete')){
    error(403);
}

$DB->query("DELETE s 
            FROM shows AS s 
            LEFT JOIN torrents_group AS tg ON s.ID = tg.TVMAZE 
            WHERE tg.TVMAZE IS NULL AND s.ID < 959500 OR s.ID > 959595"); // custom shows excluded

header("Location: tools.php?type=&action=shows&&did=1");

