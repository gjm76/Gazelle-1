<?php
enforce_login();
authorize();

include(SERVER_ROOT . '/sections/torrents/functions.php');

// Quick SQL injection check
if (!$_REQUEST['groupid'] || !is_number($_REQUEST['groupid'])) {
    error(404);
}

if(!$GroupID) $GroupID=ceil($_POST['groupid']);
if(!$TVMaze) $TVMaze=ceil($_POST['tvmaze']);
if(!$TMDb) $TMDb=$_POST['tmdb'];
if(!$TMDbTV) $TMDbTV=$_POST['tmdbtv'];
if(!$Season) $Season=$_POST['season'];
if(!$Episode) $Episode=$_POST['episode'];
if(!$AirDate) $AirDate=$_POST['airdate'];

//check user has permission to scrape
$CanEdit = check_perms('torrents_scrape');

if (!$CanEdit) {
    $DB->query("SELECT UserID, Time FROM torrents WHERE GroupID='$GroupID'");
    list($AuthorID, $AddedTime) = $DB->next_record();
    if ($LoggedUser['ID'] == $AuthorID) {
        if (check_perms ('torrents_scrape')) {
            $CanEdit = true;
        } else {
            error(403);
        }
    }
}

//check user has permission to edit
if (!$CanEdit) { error(403); }

if($TMDb || $TMDbTV) {
   
   require_once(SERVER_ROOT . '/sections/upload/functions.php');	
	
	$TMDbKey = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
	
	if($TMDb) $TMDbInfo = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/$TMDb?api_key=$TMDbKey"), TRUE);
	elseif($TMDbTV) {
       
      if($Season && $Episode) $TMDbInfoEpisode = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/$TMDbTV/season/$Season/episode/$Episode?api_key=$TMDbKey"), TRUE); 		

      elseif($Season && !$Episode) $TMDbInfoSeason = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/$TMDbTV/season/$Season?api_key=$TMDbKey"), TRUE); 		

		$TMDbInfo = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/$TMDbTV?api_key=$TMDbKey"), TRUE);
   }

   if (!$TMDbInfo) { error('TMDb ID Not found'); }   // error -> exit

   // get tags
   $Tags = array();
   foreach ($TMDbInfo['genres'] as $Tag){
       $Tags[] = $Tag['name'];   
   }

   $Genres = $Tags; 

   $Tags = strtolower(implode(' ', $Tags));
   $Tags = str_replace('-', ".", $Tags);

   // load old tags
   $DB->query("SELECT Name
               FROM torrents_tags AS tt
               JOIN tags AS t ON t.ID = tt.TagID
               WHERE GroupID = '$GroupID'");
   $OldTags = $DB->collect('Name');
   $OldTags = implode(' ', $OldTags);
   $Tags = $Tags.' '.$OldTags;
   $Tags = explode(' ', $Tags);        

   // Decrease the tag count, if it's not in use any longer and not an official tag, delete it from the list.
   $DB->query("SELECT tt.TagID, t.Uses, t.TagType
               FROM torrents_tags AS tt
               JOIN tags AS t ON t.ID = tt.TagID
               WHERE GroupID ='$GroupID'");
   $Tags2 = $DB->to_array();
        foreach ($Tags2 as $Tag) {
            $Uses = $Tag['Uses'] > 0 ?  $Tag['Uses'] - 1 : 0;
            if ($Tag['TagType'] == 'genre' || $Uses > 0) {
                $DB->query("UPDATE tags SET Uses=$Uses WHERE ID=".$Tag['TagID']);   //$TagID);
            } else {
                $DB->query("DELETE FROM tags WHERE ID=".$Tag['TagID']." AND TagType='other'");
            }
        }
   $DB->query("DELETE FROM torrents_tags WHERE GroupID='$GroupID'");
   $DB->query("DELETE FROM torrents_tags_votes WHERE GroupID='$GroupID'");
   
   // add new ones
   $TagsAdded=array();
   foreach ($Tags as $Tag) {
    if (empty($Tag)) continue;
    $Tag = strtolower(trim(trim($Tag,'.'))); // trim dots from the beginning and end
    if (!is_valid_tag($Tag) || !check_tag_input($Tag)) continue;
    $Tag = get_tag_synonym($Tag);

    if (empty($Tag)) continue;
    if (in_array($Tag, $TagsAdded)) continue;

    $TagsAdded[] = $Tag;
    $DB->query("INSERT INTO tags
                            (Name, UserID, Uses) VALUES
                            ('" . $Tag . "', $LoggedUser[ID], 1)
                            ON DUPLICATE KEY UPDATE Uses=Uses+1;");
    $TagID = $DB->inserted_id();

    if (empty($LoggedUser['NotVoteUpTags'])) {

        $UserVote = check_perms('site_vote_tag_enhanced') ? ENHANCED_VOTE_POWER : 1;
        $VoteValue = $UserVote + 8;

        $DB->query("INSERT INTO torrents_tags
                            (TagID, GroupID, UserID, PositiveVotes) VALUES
                            ($TagID, $GroupID, $LoggedUser[ID], $VoteValue)
                            ON DUPLICATE KEY UPDATE PositiveVotes=PositiveVotes+$UserVote;");

        $DB->query("INSERT IGNORE INTO torrents_tags_votes (TagID, GroupID, UserID, Way) VALUES
                                ($TagID, $GroupID, $LoggedUser[ID], 'up');");
    } else {
        $DB->query("INSERT IGNORE INTO torrents_tags
                            (TagID, GroupID, UserID, PositiveVotes) VALUES
                            ($TagID, $GroupID, $LoggedUser[ID], 8);");
    }

   }

   if($TMDbInfo['title']) $Title = $TMDbInfo['title']; // movie
   elseif($Season && !$Episode) $Title = $TMDbInfo['name'] . ' - S' . $Season; 
   elseif($Season && $Episode && $TMDbInfoEpisode['name']) $Title = $TMDbInfo['name'] . ' - S'. $Season.'E'.$Episode . ' - ' . $TMDbInfoEpisode['name']; 
   elseif($Season && $Episode) $Title = $TMDbInfo['name'] . ' - S'. $Season.'E'.$Episode; 
   else $Title = $TMDbInfo['name'];

   // get release date and year for title
   if($TMDbInfoEpisode['air_date']) { // tv episode
     $AirDate = substr($TMDbInfoEpisode['air_date'], 0, 11);
     $AirDate = date('Y-m-d', strtotime($AirDate));
     //$Year = date('Y', strtotime($AirDate));
   }elseif($TMDbInfoSeason['air_date']) { // tv season
     $AirDate = substr($TMDbInfoSeason['air_date'], 0, 11);
     $AirDate = date('Y-m-d', strtotime($AirDate));
     //$Year = date('Y', strtotime($AirDate));   	     	  
   }elseif($TMDbInfo['release_date']) {     // movie
     $AirDate = substr($TMDbInfo['release_date'], 0, 11);
     $AirDate = date('Y-m-d', strtotime($AirDate));
     $Year = date('Y', strtotime($AirDate));
   }elseif($TMDbInfo['first_air_date']) { // tv movie
     $AirDate = substr($TMDbInfo['first_air_date'], 0, 11);
     $AirDate = date('Y-m-d', strtotime($AirDate));
     $Year = date('Y', strtotime($AirDate));
   }else {
     $AirDate='';	  
     $Year='';	  
   }

   if($Year && !$Season && !$Episode) $Title .= ' - ' . $Year;

   if($TMDbInfoEpisode['still_path']) $Poster = upload_to_imagehost('https://image.tmdb.org/t/p/w400'.$TMDbInfoEpisode['still_path']);
   elseif($TMDbInfoSeason['poster_path']) $Poster = upload_to_imagehost('https://image.tmdb.org/t/p/w400'.$TMDbInfoSeason['poster_path']);
   elseif($TMDbInfo['poster_path']) $Poster = upload_to_imagehost('https://image.tmdb.org/t/p/w400'.$TMDbInfo['poster_path']);
   
   if($TMDbInfoEpisode) $Plot = $TMDbInfoEpisode['overview'];
   elseif($TMDbInfoSeason) {
   	
     // Start of cluster 1
     $Synopsis = "[url=https://www.themoviedb.org/tv/".$TMDbTV."][size=4]".$TMDbInfo['name']."[/size][/url]\n\n";

     $Synopsis .= "[b]Airs on: [/b][url=/shows.php?page=1&sort=weight&taglist=all&genre=&network=";

     if($TMDbInfo['networks'][0]['name'])		
      $Synopsis .= str_replace(' ', '+', $TMDbInfo['networks'][0]['name']);
	  $Synopsis .= "]";
 	  if($TMDbInfo['networks'][0]['name'])		
	   $Synopsis .= $TMDbInfo['networks'][0]['name'];
     $Synopsis .= "[/url]\n";        

     $Synopsis .= "[b]Show Type: [/b]".$TMDbInfo['type']."\n";
     if($Genres) $Synopsis .= "[b]Genres: [/b]".implode(', ', $Genres)."\n";

     if($TMDbInfo['created_by']){ 
      $Synopsis .= "[br][b]Created by: [/b]";
     foreach($TMDbInfo['created_by'] as $Search){
      if($Search != reset($TMDbInfo['created_by'])) $Synopsis .= " | ";   
        $Synopsis .= $Search['name'];
      }
     }
        
     if($TMDbInfo['homepage']) {
      $Synopsis .= "[br][b]Official site: [/b][url=" . $TMDbInfo['homepage'] . "]" . preg_replace('#^https?://#', '', parse_url($TMDbInfo['homepage'], PHP_URL_HOST)) . "[/url]";
     }

     $Synopsis .= "\n\n";
     $Synopsis .= $TMDbInfo['overview'];
     // End of current cluster 1

     // Start of new cluster 2
     $Episodes = ""; 
     foreach($TMDbInfoSeason['episodes'] as $episode) {
          $Episodes .= "[color=#2c539e][b]".$episode['name']."[/b][/color]\n";
          $Episodes .= "[b]Episode:[/b] ".str_pad($episode['season_number'], 2, '0', STR_PAD_LEFT);
          $Episodes .= "x".str_pad($episode['episode_number'], 2, '0', STR_PAD_LEFT);
          $Episodes .= " | [b]Aired:[/b] " . $episode['air_date'] . "\n";
          if(!empty($episode['overview'])) {
             $Episodes .= $episode['overview'];
          }
          $Episodes .= "\n\n";
     }
     // End of second cluster 2   	
   	
     $Plot = $Synopsis;
   }	
   else $Plot = $TMDbInfo['overview'];
   
   $TagsAdded = implode(' ', $TagsAdded);
   
   $IMDb = '';
   if($TMDbInfo['imdb_id']) $IMDb = $TMDbInfo['imdb_id'];
   
   if(!$TMDb) $TMDb = $TMDbTV; 
   
   /* -------  Update torrent group  ------- */
   $DB->query("UPDATE torrents_group SET
    Name='" . db_string($Title) . "',
    Synopsis='" . db_string($Plot) . "',
    EpisodeGuide='" . db_string($Episodes) . "',
    TagList='" . db_string($TagsAdded) . "',
    PosterURL = '" . db_string($Poster) . "',
    TMDb = '" . $TMDb . "',
    IMDB = '" . $IMDb . "',
    TVMAZE = '0'
    WHERE ID='$GroupID'");

   /* -------  Update torrent table  ------- */
   $DB->query("UPDATE torrents SET
    Season='$Season',
    Episode='$Episode',
    AirDate='" . db_string($AirDate) . "'
    WHERE ID='$GroupID'");

   /* -------  Refresh cache  ------- */
   $Cache->delete_value('torrents_details_'.$GroupID);
   $DB->query("SELECT CollageID FROM collages_torrents WHERE GroupID='$GroupID'");
   if ($DB->record_count()>0) {
    while (list($CollageID) = $DB->next_record()) {
        $Cache->delete_value('collage_'.$CollageID);
    }
   }
   update_hash($GroupID);

   write_log("Torrent $GroupID (" . $Title . ") was edited by " . $LoggedUser['Username'] . " (Scraped)");
   write_group_log($GroupID, $GroupID, $LoggedUser['ID'], "Torrent edited: Scraped with ShowID: " . $TMDb , 0);
   
   $Cache->delete_value('show_'.$TVMaze);
   header("Location: torrents.php?id=".$GroupID."&did=7");
      
} else {
   scrape($GroupID, $TVMaze, $Season, $Episode, $AirDate);
   $Cache->delete_value('show_'.$TVMaze);
   header("Location: torrents.php?id=".$GroupID."&did=6");
}
