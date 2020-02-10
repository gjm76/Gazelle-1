<?php
function get_featured_show() {

    global $DB;
    
    $DB->query("SELECT
                   fs.TVMAZE, 
                   fs.Title,
                   fs.Synopsis,
                   fs.Rating,
                   fs.PosterURL,
                   fs.Time,
                   fs.UniqueTag
                   FROM featured_show as fs
               ");
    $P=$DB->next_record();
    list($TVMazeID,$TVMazeTitle,$Synopsis,$TVMazeRating,$TVMazePoster,$SetTime,$UniqueTag) = $P;
    if($TVMazeTitle){
     $TVMazeTitle = str_replace('&#39;', "`", $TVMazeTitle); // fix '
     
     if($TVMazeID) {
        $Url = "/torrents.php?action=show&showid=".$TVMazeID;
     }   
     else {
        $Url = $Url = torrents_link($TVMazeTitle);
        if($UniqueTag) // add unique tag
          $Url .= '&taglist=' . $UniqueTag;
     }
    }       
    $P[6] = $Url; // save url
    return $P;
}

function set_featured_show($TVMazeID,$TVMazeTitle,$TVMazePoster,$TVMazeRating,$Synopsis,$UniqueTag) {

    global $DB;
    
    $Time = sqltime();
    
    // Force ID to 1 so that it always INSERTS/UPDATES the same column
    $DB->query("INSERT INTO featured_show (ID, TVMAZE, Title, Synopsis, Rating, PosterURL, Time, UniqueTag)
                VALUES(1, $TVMazeID, '$TVMazeTitle', '$Synopsis', $TVMazeRating, '$TVMazePoster', '$Time' ,'$UniqueTag')
                ON DUPLICATE KEY UPDATE 
                   TVMAZE=$TVMazeID,
                   Title='$TVMazeTitle',
                   Synopsis='$Synopsis',
                   Rating=$TVMazeRating,
                   PosterURL='$TVMazePoster',
                   Time='$Time',
                   UniqueTag='$UniqueTag'");
}
