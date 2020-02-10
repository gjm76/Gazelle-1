<?php

function notify_active_users($TorrentID, $Name, $Size, $Reason, $Extra) {
	global $DB, $LoggedUser;
   $DB->query("SELECT DISTINCT uid FROM xbt_files_users WHERE fid='$TorrentID' AND active");
   if ($DB->record_count()>0) {
       $ActiveUsers = $DB->to_array();        
       foreach ($ActiveUsers as $UserID) {
          send_pm($UserID[0], 0, db_string('Torrent deleted: '.$Name.' ('.get_size($Size).')'), 
          db_string("Torrent ".$TorrentID." (".$Name.") (".get_size($Size).") that you're leeching/seeding has been deleted by ".$LoggedUser['Username'].' (Reason: ' .$Reason.') '.$Extra));
       }
   } 	
}	

function get_personal_rating($TVMAZE) {
   global $DB, $LoggedUser;
   $DB->query("SELECT Rating FROM shows_ratings WHERE UserID='$LoggedUser[ID]' AND ShowID='$TVMAZE'");
   list($PersonalRating) = $DB->next_record();
   return $PersonalRating;
}

function get_average_rating($TVMAZE) {
   global $DB;
   $DB->query("SELECT AVG(Rating) FROM shows_ratings WHERE ShowID='$TVMAZE'");
   list($AverageRating) = $DB->next_record();
   return $AverageRating;
}

function get_votes($TVMAZE) {
   global $DB;
   $DB->query("SELECT count(UserID) FROM shows_ratings WHERE ShowID='$TVMAZE'");
   list($Votes) = $DB->next_record();
   return $Votes;
}

function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

function get_search($Title) {
   $Title = str_replace('&#39;', "`", $Title); // fix '
   $Title = str_replace('&eacute;', "e", $Title); // fix é
	if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $Title)) {
      return	"/torrents.php?title=". urlencode(htmlspecialchars_decode($Title))."&action=advanced";      
	}
	elseif(preg_match("/\\d|\./", $Title))
	   return	"/torrents.php?searchtext=". urlencode(htmlspecialchars_decode($Title));
	else
	   return	"/torrents.php?title=". urlencode(htmlspecialchars_decode($Title))."&action=advanced"; ;
}	

function scrape($GroupID, $TVMaze, $Season, $Episode, $AirDate) {
   
   require_once(SERVER_ROOT . '/sections/upload/functions.php');

   global $Cache, $DB, $LoggedUser, $NewCategories;

   /* -------  Retrieve files  ------- */
   $TorrentCache = get_group_info($GroupID, true);
   $TorrentList = $TorrentCache[1];
   list( , , , , , , , , , $FileList, $FilePath) = $TorrentList[0];
   $NewCategoryID = $TorrentCache[0][0][9];

   /* -------  Parse TVMaze  ------- */
   $Properties = array();
   
   $Properties['Title'] = $FilePath;
   $Properties['FileName'] = $FilePath;

   $FileList = explode("|||", $FileList);
   foreach ($FileList as $File) {
      $FileListClean[][1] = preg_replace('/\{\{\{.+\}\}\}/', '', $File);
   }

   parse_title($Properties, $FileListClean, $TVMaze, $Season, $Episode, $Debug, $AirDate);

   /* -------  Retrieve banner  ------- */
   if($Properties['TVMAZE']) {
     $ID = $Properties['TVMAZE'];
     $DB->query("SELECT
     BannerLink
     FROM torrents_banners
     WHERE TVMazeID = $ID");

     list($Properties['Image']) = $DB->next_record();
  
     if($Properties['Image']=='') $Properties['Image'] = "/static/common/noartwork/noimage.png";  
   }

   /* -------  Save tags  ------------- */
   // in case of comma delineators.
   $Tags = str_replace(',', ' ', $Properties['TagList']);
   // insert the category tag here.
   $Tags = explode(' ', strtolower($NewCategories[(int) $NewCategoryID]['tag']." ".$Tags));

   // check for autofill tag
   $DB->query("SELECT Name
               FROM torrents_tags AS tt
               JOIN tags AS t ON t.ID = tt.TagID
               WHERE Name = 'autofill' AND GroupID = '$GroupID'");
    if ($DB->record_count()>0) $Tags[] = 'autofill';           

   // check for subtitles tag
   $DB->query("SELECT Name
               FROM torrents_tags AS tt
               JOIN tags AS t ON t.ID = tt.TagID
               WHERE Name = 'subtitles' AND GroupID = '$GroupID'");
    if ($DB->record_count()>0) $Tags[] = 'subtitles'; 

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
   // replace the original tag array with corrected tags
   $Properties['TagList'] = $TagsAdded;
   
   /* -------  Update torrent group  ------- plus reset IMDb, TMDb */
   $DB->query("UPDATE torrents_group SET
    Name='" . db_string($Properties['Title']) . "',
    PosterURL='" . db_string($Properties['PosterURL']) . "',
    Synopsis='" . db_string($Properties['Synopsis']) . "',
    EpisodeGuide='" . db_string($Properties['EpisodeGuide']) . "',
    TagList='" . db_string($Properties['TagList']) . "',
    Image = '" . db_string($Properties['Image']) . "',
    TVMAZE = '" . $Properties['TVMAZE'] . "',
    IMDB = '" . $Properties['IMDb'] . "',
    TMDb = '" . $Properties['TMDb'] . "'
    WHERE ID='$GroupID'");

   /* -------  Update torrent table  ------- */
   $DB->query("UPDATE torrents SET
    Season='" . db_string($Properties['Season']) . "',
    Episode='" . db_string($Properties['Episode']) . "',
    AirDate='" . db_string($Properties['AirDate']) . "',
    Network='" . db_string($Properties['Network']) . "'
    WHERE ID='$GroupID'");

   /* -------  Refresh cache  ------- */
   $Cache->delete_value('torrents_details_'.$GroupID);
   //$Cache->delete_value('show_'.$TVMaze);
   $DB->query("SELECT CollageID FROM collages_torrents WHERE GroupID='$GroupID'");
   if ($DB->record_count()>0) {
    while (list($CollageID) = $DB->next_record()) {
        $Cache->delete_value('collage_'.$CollageID);
    }
   }
   update_hash($GroupID);

   write_log("Torrent $GroupID (" . $Properties['Title'] . ") was edited by " . $LoggedUser['Username'] . " (Scraped)");
   write_group_log($GroupID, $GroupID, $LoggedUser['ID'], "Torrent edited: Scraped with ShowID: " . $Properties['TVMAZE'] . " Season: ".
                   $Properties['Season'] . " Episode: " .$Properties['Episode'] , 0);
}

function get_group_info($GroupID, $Return = true, $ShowLog = true)
{
    global $Cache, $DB;

    $GroupID=(int) $GroupID;

    $TorrentCache=$Cache->get_value('torrents_details_'.$GroupID);

    //TODO: Remove LogInDB at a much later date.
    if (!is_array($TorrentCache) || !isset($TorrentCache[1][0]['LogInDB'])) {
        // Fetch the group details

        $SQL = "SELECT
                g.Body,
                g.Mediainfo,
                g.Screens,
                g.Trailer,
                g.Synopsis,
                g.EpisodeGuide,
                g.Image,
                g.ID,
                g.Name,
                g.NewCategoryID,
                g.Time,
                g.IMDB,
                g.PosterURL,
                g.TVMAZE,
                g.TMDb                
            FROM torrents_group AS g
            WHERE g.ID='$GroupID' ";

        $DB->query($SQL);
        $TorrentDetails=$DB->to_array();

        $DB->query("
            SELECT
                tags.Name,
                tt.TagID,
                tt.UserID,
                um1.Username,
                tags.Uses,
                tt.PositiveVotes,
                tt.NegativeVotes,
                GROUP_CONCAT(ttv.UserID SEPARATOR '|'),
                GROUP_CONCAT(um2.Username SEPARATOR '|'),
                GROUP_CONCAT(ttv.Way SEPARATOR '|')
            FROM torrents_tags AS tt
            LEFT JOIN tags ON tags.ID=tt.TagID
            LEFT JOIN torrents_tags_votes AS ttv ON ttv.GroupID=tt.GroupID AND ttv.TagID=tt.TagID
            LEFT JOIN users_main AS um1 ON um1.ID=tt.UserID
            LEFT JOIN users_main AS um2 ON um2.ID=ttv.UserID
            WHERE tt.GroupID='$GroupID'
            GROUP BY tt.TagID    ");
        $TagDetails=$DB->to_array(false, MYSQLI_NUM);

        // Fetch the individual torrents

        $DB->query("
            SELECT
                t.ID,
                t.FileCount,
                t.Size,
                t.Seeders,
                t.Leechers,
                t.Snatched,
                t.FreeTorrent,
                t.DoubleTorrent,
                t.Time,
                t.FileList,
                t.FilePath,
                t.UserID,
                um.Username,
                t.last_action,
                tbt.TorrentID,
                tbf.TorrentID,
                tfi.TorrentID,
                t.LastReseedRequest,
                tln.TorrentID AS LogInDB,
                t.ID AS HasFile ,
                t.Anonymous,
                t.Season,
                t.Episode,
                t.AirDate

            FROM torrents AS t
            LEFT JOIN users_main AS um ON um.ID=t.UserID
            LEFT JOIN torrents_bad_tags AS tbt ON tbt.TorrentID=t.ID
            LEFT JOIN torrents_bad_folders AS tbf on tbf.TorrentID=t.ID
            LEFT JOIN torrents_bad_files AS tfi on tfi.TorrentID=t.ID
            LEFT JOIN torrents_logs_new AS tln ON tln.TorrentID=t.ID
            WHERE t.GroupID='".db_string($GroupID)."'
            AND flags != 1
            ORDER BY t.ID");

        $TorrentList = $DB->to_array();
        if (count($TorrentList) == 0 && $ShowLog) {
            if (isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
                header("Location: log.php?search=Torrent+".$_GET['torrentid']);
            } else {
                header("Location: log.php?search=Torrent+".$GroupID);
            }
            die();
        }

        foreach ($TorrentList as &$Torrent) {
            $CacheTime = $Torrent['Seeders']==0 ? 120 : 900;
            $TorrentPeerInfo = array('Seeders'=>$Torrent['Seeders'],'Leechers'=>$Torrent['Leechers'],'Snatched'=>$Torrent['Snatched']);
            $Cache->cache_value('torrent_peers_'.$Torrent['ID'], $TorrentPeerInfo, $CacheTime);
        }

        // Store it all in cache
        $Cache->cache_value('torrents_details_'.$GroupID,array($TorrentDetails,$TorrentList,$TagDetails), 3600);

    } else { // If we're reading from cache
        $TorrentDetails=$TorrentCache[0];
        $TorrentList=$TorrentCache[1];
        $TagDetails=$TorrentCache[2];
        foreach ($TorrentList as &$Torrent) {
            $TorrentPeerInfo = get_peers($Torrent['ID']);
            $Torrent[3]=$TorrentPeerInfo['Seeders'];
            $Torrent[4]=$TorrentPeerInfo['Leechers'];
            $Torrent[5]=$TorrentPeerInfo['Snatched'];
            $Torrent['Seeders']=$TorrentPeerInfo['Seeders'];
            $Torrent['Leechers']=$TorrentPeerInfo['Leechers'];
            $Torrent['Snatched']=$TorrentPeerInfo['Snatched'];
        }
    }

    if ($Return) {
        return array($TorrentDetails,$TorrentList,$TagDetails);
    }
}

//Check if a givin string can be validated as a torrenthash
function is_valid_torrenthash($Str)
{
    //6C19FF4C 6C1DD265 3B25832C 0F6228B2 52D743D5
    $Str = str_replace(' ', '', $Str);
    if(preg_match('/^[0-9a-fA-F]{40}$/', $Str))

        return $Str;
    return false;
}

function get_group_requests($GroupID)
{
    global $DB, $Cache;

    $Requests = $Cache->get_value('requests_group_'.$GroupID);
    if ($Requests === FALSE) {
        $DB->query("SELECT ID FROM requests WHERE GroupID = $GroupID AND TimeFilled = '0000-00-00 00:00:00'");
        $Requests = $DB->collect('ID');
        $Cache->cache_value('requests_group_'.$GroupID, $Requests, 0);
    }
    $Requests = get_requests($Requests);

    return $Requests['matches'];
}

function get_group_requests_filled($TorrentID)
{
    global $DB, $Cache;

    $Requests = $Cache->get_value('requests_torrent_'.$TorrentID);
    if ($Requests === FALSE) {
        $DB->query("SELECT ID FROM requests WHERE TorrentID = $TorrentID");
        $Requests = $DB->collect('ID');
        $Cache->cache_value('requests_torrent_'.$TorrentID, $Requests, 0);
    }
    $Requests = get_requests($Requests);

    return $Requests['matches'];
}

// tag sorting functions
function sort_uses_desc($X, $Y)
{
    return($Y['uses'] - $X['uses']);
}
function sort_added_desc($X, $Y)
{
    return($X['id'] - $Y['id']);
}
function sort_az_desc($X, $Y)
{
    return( strcmp($X['name'], $Y['name']) );
}

function sort_uses_asc($X, $Y)
{
    return($X['uses'] - $Y['uses']);
}
function sort_added_asc($X, $Y)
{
    return($Y['id'] - $X['id']);
}
function sort_az_asc($X, $Y)
{
    return( strcmp($Y['name'], $X['name']) );
}

/**
 * Returns the inner list elements of the tag table for a torrent
 * (this function calls/rebuilds the group_info cache for the torrent - in theory just a call to memcache as all calls come through the torrent details page)
 * @param int $GroupID The group id of the torrent
 * @return the html for the taglist
 */
function get_taglist_html($GroupID, $tagsort, $order = 'desc')
{
    global $LoggedUser;

    $TorrentCache = get_group_info($GroupID, true);
    $TorrentDetails = $TorrentCache[0];
    $TorrentList = $TorrentCache[1];
    $TorrentTags = $TorrentCache[2];

    list(, , , , , , , , , , , $UserID, $Username, , , , , , , ,$IsAnon) = $TorrentList[0];

    if(!$tagsort || !in_array($tagsort, array('uses','az','added'))) $tagsort = 'uses';

    $Tags = array();
    if ($TorrentTags != '') {
        foreach ($TorrentTags as $TagKey => $TagDetails) {
            list($TagName, $TagID, $TagUserID, $TagUsername, $TagUses, $TagPositiveVotes, $TagNegativeVotes,
                    $TagVoteUserIDs, $TagVoteUsernames, $TagVoteWays) = $TagDetails;

			$Tags[$TagKey]['name'] = $TagName;
            $Tags[$TagKey]['id']= $TagID;
            $Tags[$TagKey]['userid']= $TagUserID;

            $Tags[$TagKey]['username']= anon_username_ifmatch($TagUsername, $Username, $IsAnon) ;
            $Tags[$TagKey]['uses']= $TagUses;

            $TagVoteUsernames = explode('|',$TagVoteUsernames);
            $TagVoteWays = explode('|',$TagVoteWays);
            $VoteMsgs=array();
            $VoteMsgs[]= "$TagName (" . str_plural('use' , $TagUses).')';
            $VoteMsgs[]= "added by ".anon_username_ifmatch($TagUsername, $Username, $IsAnon);
            foreach ($TagVoteUsernames as $TagVoteKey => $TagVoteUsername) {
                if (!$TagVoteUsername) continue;
                $VoteMsgs[] = $TagVoteWays[$TagVoteKey] . " (". anon_username_ifmatch($TagVoteUsername, $Username, $IsAnon).")";
            }
            $Tags[$TagKey]['votes'] = implode("\n", $VoteMsgs) ;
        }
        if($order!='desc') $order = 'asc';
        uasort($Tags, "sort_{$tagsort}_$order");
    }

    $IsUploader =  $UserID == $LoggedUser['ID'];

    ob_start();
?>
                <ul class="stats nobullet">

<?php
            foreach ($Tags as $TagKey=>$Tag) {
?>
                                <li id="tlist<?=$Tag['id']?>">
                                      <a href="torrents.php?taglist=<?=$Tag['name']?>" style="float:left; display:block;" title="<?=$Tag['votes']?>"><?=display_str($Tag['name'])?></a>
                                      <div style="float:right; display:block; letter-spacing: -1px;">
        <?php //		if (check_perms('site_vote_tag') || ($IsUploader && $LoggedUser['ID']==$Tag['userid'])) {  ?>
        <?php 		if (check_perms('site_vote_tag')) {  ?>
                                      <a title="Vote down tag '<?=$Tag['name']?>'" href="#tags" onclick="return Vote_Tag(<?="'{$Tag['name']}',{$Tag['id']},$GroupID,'down'"?>)" style="font-family: monospace;" >[-]</a>
                                      <a title="Vote up tag '<?=$Tag['name']?>'" href="#tags" onclick="return Vote_Tag(<?="'{$Tag['name']}',{$Tag['id']},$GroupID,'up'"?>)" style="font-family: monospace;">[+]</a>

        <?php
                  } else {  // cannot vote on tags ?>
                                      <span style="width:10px;text-align:center;display:inline-block;" title="You do not have permission to vote on tags"><?=$Tag['score']?></span>
                                      <span style="font-family: monospace;" >&nbsp;&nbsp;&nbsp;</span>

        <?php 		} ?>
        <?php 		if (check_perms('users_warn')) { ?>
                                      <a title="Tag '<?=$Tag['name']?>' added by <?=$Tag['username']?>" href="user.php?id=<?=$Tag['userid']?>" >[U]</a>
        <?php 		} ?>
        <?php 		if (check_perms('site_delete_tag') ) { ?>
                                   <a title="Delete tag '<?=$Tag['name']?>'" href="#tags" onclick="return Del_Tag(<?="'{$Tag['id']}',$GroupID,'$tagsort'"?>)"   style="font-family: monospace;">[X]</a>
        <?php 		} else { ?>
                                      <span style="font-family: monospace;">&nbsp;&nbsp;&nbsp;</span>
        <?php 		} ?>
                                      </div>
                                      <br style="clear:both" />
                                </li>
<?php
            }
?>
                </ul>
<?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

function update_staff_checking($location="cyberspace",$dontactivate=false) { // logs the staff in as 'checking'
    global $Cache, $DB, $LoggedUser;

    if ($dontactivate) {
        // if not already active dont activate
        $DB->query("SELECT UserID FROM staff_checking
                     WHERE UserID='$LoggedUser[ID]' AND TimeOut > '".time()."' AND IsChecking='1'" );
        if($DB->record_count()==0) return;
    }

    $sqltimeout = time() + 480;
    $DB->query("INSERT INTO staff_checking (UserID, TimeOut, TimeStarted, Location, IsChecking)
                                    VALUES ('$LoggedUser[ID]','$sqltimeout','".sqltime()."','$location','1')
                           ON DUPLICATE KEY UPDATE TimeOut='$sqltimeout', Location='$location', IsChecking='1'");

    $Cache->delete_value('staff_checking');
    $Cache->delete_value('staff_lastchecked');
}

function print_staff_status()
{
    global $Cache, $DB, $LoggedUser;

    $Checking = $Cache->get_value('staff_checking');
    if ($Checking===false) {
        // delete old ones every 4 minutes
        $DB->query("UPDATE staff_checking SET IsChecking='0' WHERE TimeOut <= '".time()."' " );
        $DB->query("SELECT s.UserID, u.Username, s.TimeStarted , s.TimeOut , s.Location
                      FROM staff_checking AS s
                      JOIN users_main AS u ON u.ID=s.UserID
                     WHERE s.IsChecking='1'
                  ORDER BY s.TimeStarted ASC " );
        $Checking = $DB->to_array();
        $Cache->cache_value('staff_checking',$Checking,240);
    }

    ob_start();
    $UserOn = false;
    $active=0;
    if (count($Checking)>0) {
        foreach ($Checking as $Status) {
            list( $UserID, $Username, $TimeStart, $TimeOut ,$Location ) =  $Status;
            $Own = $UserID==$LoggedUser['ID'];
            if ($Own) $UserOn = true;

            $TimeLeft = $TimeOut - time();
            if ($TimeLeft<0) {
                $Cache->delete_value('staff_checking');
                continue;
            }
            $active++;
?>
            <span class="staffstatus status_checking<?php if($Own)echo' statusown';?>"
               title="<?=($Own?'Status: checking torrents ':"$Username is currently");
                        echo " $Location&nbsp;";
                        echo " (".time_diff($TimeOut-480, 1, false, false, 0).") ";
                        if ($Own && $TimeLeft<240) echo "(".time_diff($TimeOut, 1, false, false, 0)." till time out)"; ?> ">
                <?php
                    if ($TimeLeft<60) echo "<blink>";
                    if($Own) echo "<a onclick=\"change_status('".($TimeLeft<60?"1":"0")."')\">";
                    echo $Username;
                    if($Own) echo "</a>";
                    if ($TimeLeft<60) echo "</blink>";
                   ?>
            </span>
<?php
        }
    }

    if ($active==0) { // if no staff are checking now
            $LastChecked = $Cache->get_value('staff_lastchecked');
            if ($LastChecked===false) {
                $DB->query("SELECT s.UserID, u.Username, s.TimeOut , s.Location
                              FROM staff_checking AS s
                              JOIN users_main AS u ON u.ID=s.UserID
                              JOIN (
                                        SELECT Max(TimeOut) as LastTimeOut
                                        FROM staff_checking
                                    ) AS x
                              ON x.LastTimeOut= s.TimeOut  " );
                if ($DB->record_count()>0) {
                    $LastChecked = $DB->next_record(MYSQLI_ASSOC);
                    $Cache->cache_value('staff_lastchecked',$LastChecked);
                }
            }
            if ($LastChecked) $Str = time_diff($LastChecked['TimeOut']-480, 2, false)." ($LastChecked[Username])";
            else $Str = "never";
?>
            <span class="nostaff_checking" title="last check: <?=$Str?>">
                No staff are currently checking torrents
            </span>
<?php
    }

    if (!$UserOn) {
?>
        <span class="staffstatus status_notchecking statusown"  title="Status: not checking">
            <a onclick="change_status('1')"> <?=$LoggedUser['Username']?> </a>
        </span>
<?php
    }

    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}
