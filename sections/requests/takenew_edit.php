<?php

//******************************************************************************//
//----------------- Take request -----------------------------------------------//

authorize();

if(!check_perms('site_submit_requests') || $LoggedUser['HnR']) error(403);

if ($_POST['action'] != "takenew" &&  $_POST['action'] != "takeedit") {
    error(0);
}

include(SERVER_ROOT . '/sections/torrents/functions.php');
$Text = new TEXT;

$NewRequest = ($_POST['action'] == "takenew");

if (!$NewRequest) {
    $ReturnEdit = true;
}

if ($NewRequest) {
    if (!check_perms('site_submit_requests') || $LoggedUser['TotalCredits'] < 500) {
        error(403);
    }
} else {
    $RequestID = $_POST['requestid'];
    if (!is_number($RequestID)) {
        error(0);
    }

    $Request = get_requests(array($RequestID));
    $Request = $Request['matches'][$RequestID];
    if (empty($Request)) {
        error(404);
    }

    list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Image, $Description,
         $FillerID, $FillerName, $TorrentID, $TimeFilled, $GroupID) = $Request;
    $VoteArray = get_votes_array($RequestID);
    $VoteCount = count($VoteArray['Voters']);

    $IsFilled = !empty($TorrentID);

    $ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0)));
    $CanEdit = ((!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));

    if (!$CanEdit) {
        error(403);
    }
}

// Validate
if (empty($_POST['category'])) {
    error("You forgot to enter a category!");
}

$CategoryID = $_POST['category'];

$TVMazeID = $_POST['tvmaze'];
if(!$TVMazeID) $TVMazeID= 0;

$Season = $_POST['season'];
if(!$Season) $Season= 0;

$Episode = $_POST['episode'];
if(!$Episode) $Episode= 0;

$Resolution = $_POST['resolution'];
if(!$Resolution) $Resolution = 0;

$Source = $_POST['source'];
if(!$Source) $Source = 0;

$Codec = $_POST['codec'];
if(!$Codec) $Codec = 0;

$Container = $_POST['container'];
if(!$Container) $Container = 0;

$ReleaseGroup = $_POST['release'];
if(!$ReleaseGroup) $ReleaseGroup = 0;

if (empty($CategoryID)) {
    error(0);
}

if (empty($_POST['title'])) {
    $Err = "You forgot to enter the title!";
} else {
    $Title = trim($_POST['title']);
}

if (empty($_POST['tags'])) {
    $Err = "You forgot to enter any tags!";
} else {
    $Tags = trim($_POST['tags']);
}

if ($NewRequest) {
    if (empty($_POST['amount'])) {
        $Err = "You forgot to enter any bounty!";
    } else {
        $Bounty = trim($_POST['amount']);
        if (!is_number($Bounty)) {
            $Err = "Your entered bounty is not a number";
        } elseif ($Bounty < 500) {
            $Err = "Minumum bounty is 500 cubits";
        }
        $Bytes = $Bounty; //From MB to B
    }
}

if (empty($_POST['description']) && $_POST['body'] !== '0') {
    $Err = "You forgot to enter a description!";
} else {
    $Description = trim($_POST['description']);
}

if (empty($_POST['image'])) {
    $Image = "";
} else {
      $Result = validate_imageurl($_POST['image'], 12, 255, get_whitelist_regex());
      if($Result!==TRUE) $Err = $Result;
      else $Image = trim($_POST['image']);
}

/* -------  Retrieve banner  ------- */
if($TVMazeID) {
  $DB->query("SELECT
  BannerLink
  FROM torrents_banners
  WHERE TVMazeID = $TVMazeID");

  list($Image) = $DB->next_record();
}

$Text->validate_bbcode($_POST['description'],  get_permissions_advtags($LoggedUser['ID']));

if (!empty($Err)) {
    error($Err);
    $Bounty= $_POST['unit'];
    include(SERVER_ROOT.'/sections/requests/new_edit.php');
    die();
}

if ($NewRequest) {
        $DB->query("INSERT INTO requests (
                            UserID, TimeAdded, LastVote, CategoryID, Title, Image, Description, Visible, TVMazeID, Season, Episode, Resolution, Source, 
                            Codec, Container, ReleaseGroup)
                    VALUES
                            (".$LoggedUser['ID'].", '".sqltime()."', '".sqltime()."', ".$CategoryID.", '".db_string($Title)."', '"
                            .db_string($Image)."', '".db_string($Description)."', '1' , ".$TVMazeID." , ".$Season.", ".$Episode.", '"
                            .db_string($Resolution)."', '".db_string($Source)."', '".db_string($Codec)."', '".db_string($Container)."', '"
                            .db_string($ReleaseGroup)."' )");

        $RequestID = $DB->inserted_id();
} else {
        $DB->query("UPDATE requests
        SET CategoryID = ".$CategoryID.",
            Title = '".db_string($Title)."',
            Image = '".db_string($Image)."',
            Description = '".db_string($Description)."',
            TVMazeID = ".$TVMazeID.",
            Season = ".$Season.",
            Episode = ".$Episode.",
            Resolution = '".db_string($Resolution)."',
            Source = '".db_string($Source)."',
            Codec = '".db_string($Codec)."',
            Container = '".db_string($Container)."',
            ReleaseGroup = '".db_string($ReleaseGroup)."'
        WHERE ID = ".$RequestID);
}

//Tags
if (!$NewRequest) {
    $DB->query("DELETE FROM requests_tags WHERE RequestID = ".$RequestID);
}

$Tags = explode(', ', strtolower($NewCategories[$CategoryID]['tag'].", ".$Tags));

$TagsAdded=array();
foreach ($Tags as $Tag) {
        $Tag = strtolower(trim($Tag,'.')); // trim dots from the beginning and end
        if (!is_valid_tag($Tag) || !check_tag_input($Tag)) continue;
        $Tag = get_tag_synonym($Tag);
        if (!empty($Tag)) {
            if (!in_array($Tag, $TagsAdded)) { // and to create new tags as Uses=1 which seems more correct
                $TagsAdded[] = $Tag;
                $DB->query("INSERT INTO tags
                            (Name, UserID, Uses) VALUES
                            ('$Tag', $LoggedUser[ID], 1)
                            ON DUPLICATE KEY UPDATE Uses=Uses+1;");
                $TagID = $DB->inserted_id();

                $DB->query("INSERT IGNORE INTO requests_tags
                    (TagID, RequestID) VALUES
                    ($TagID, $RequestID)");
            }
        }
}
// replace the original tag array with corrected tags
$Tags = $TagsAdded;

if ($NewRequest) {
    //Remove the bounty and create the vote
    $DB->query("INSERT INTO requests_votes
                    (RequestID, UserID, Bounty)
                VALUES
                    (".$RequestID.", ".$LoggedUser['ID'].", ".$Bytes.")");

    $DB->query("UPDATE users_main SET Credits = (Credits - ".$Bytes.") WHERE ID = ".$LoggedUser['ID']);
    $Cache->delete_value('user_stats_'.$LoggedUser['ID']);

    $Title = db_string($Title);
    write_user_log($LoggedUser['ID'], "Removed -". $Bytes. " Cubits for new request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url]");

    update_bonus_log($LoggedUser['ID'], sqltime()." | -".$Bytes." credits | ".ucfirst("removed -". $Bytes. " for new request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url]"));

    $Announce = "'".$Title."' - http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID." - ".implode(" ", $Tags);

    send_irc('PRIVMSG #'.NONSSL_SITE_URL.'-requests :'.$Announce);

    write_log("Request $RequestID ($Title) created with " . $Bytes. " Cubits bounty by ".$LoggedUser['Username']);

} else {
    $Cache->delete_value('request_'.$RequestID);
}

update_sphinx_requests($RequestID);

header('Location: requests.php?action=view&id='.$RequestID);
