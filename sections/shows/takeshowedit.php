<?php
authorize();

include(SERVER_ROOT . '/sections/torrents/functions.php');

$Text = new TEXT;
$Validate = new VALIDATE;

if (!$_REQUEST['showid'] || !is_number($_REQUEST['showid'])) {
    error(404);
}

$ShowID = (int) $_REQUEST['showid'];

if (!check_perms('torrents_delete')) { error(403); }; // staff only

$Title = $_POST['title'];

$Synopsis = $_POST['synopsis'];
$_POST['synopsis'] = preg_replace('/\r/','',$_POST['synopsis']); // remove new lines for the check

$ShowInfo = $_POST['showinfo'];
$Genres =  $_POST['genres'];
$Rating = $_POST['rating'];
$Premiered = $_POST['premiered'];
$Weight = $_POST['weight'];
$Network = $_POST['network'];
$WebChannel = $_POST['webchannel'];
$NetworkUrl = $_POST['networkurl'];
$PosterURL = $_POST['posterurl'];
$Trailer = $_POST['trailer'];
$FanArtURL = $_POST['fanarturl'];

$Text->validate_bbcode($_POST['synopsis'],  get_permissions_advtags($LoggedUser['ID']));
$Text->validate_bbcode($_POST['trailer'],  get_permissions_advtags($LoggedUser['ID']));

$Err = $Validate->ValidateForm($_POST, $Text); // Validate the form

if ($Err) { 
    $_GET['showid'] = $ShowID;
    include(SERVER_ROOT . '/sections/torrents/editshow.php');
    die();
}

//save for log checks
$DB->query("SELECT ShowTitle, Synopsis, ShowInfo, Genres, NetworkName, WebChannel, NetworkUrl, PosterUrl, Rating, Weight, Premiered, Trailer, FanArtUrl FROM shows 
           WHERE ID='$ShowID'");
list($OrigShowTitle, $OrigSynopsis, $OrigShowInfo, $OrigGenres, $OrigNetwork, $OrigWebChannel, $OrigNetworkUrl, $OrigPoster, $OrigRating, $OrigWeight,
     $OrigPremiered, $OrigTrailer, $OrigFanArtUrl) = $DB->next_record();

$Title = db_string($Title);
$Synopsis = db_string($Synopsis);
$ShowInfo = db_string($ShowInfo);
$Genres =  db_string($Genres);
$Rating = db_string($Rating);
$Premiered = db_string($Premiered);
$Weight = db_string($Weight);
$Network = db_string($Network);
$WebChannel = db_string($WebChannel);
$NetworkUrl = db_string($NetworkUrl);
$PosterURL = db_string($PosterURL);
$Trailer = db_string($Trailer);
$FanArtURL = db_string($FanArtURL);
$SqlTime = $Updated = db_string( sqltime() );

// Update show table
$DB->query("UPDATE shows SET
    ShowTitle='$Title', 
    Synopsis='$Synopsis', 
    ShowInfo='$ShowInfo', 
    Genres='$Genres', 
    NetworkName='$Network', 
    WebChannel='$WebChannel', 
    NetworkUrl='$NetworkUrl', 
    PosterUrl='$PosterURL', 
    Rating='$Rating', 
    Weight='$Weight', 
    Premiered='$Premiered',
    FanArtUrl='$FanArtURL',
    Trailer='$Trailer',
    Updated='$SqlTime'
    WHERE ID='$ShowID'");

$Cache->delete_value('show_static_'.$ShowID);
 
// fix string back for the check
$OrigShowTitle = preg_replace('/&#39;/','\'',$OrigShowTitle);
$OrigSynopsis = preg_replace('/&#39;/','\'',$OrigSynopsis);
$OrigShowInfo = preg_replace('/&#39;/','\'',$OrigShowInfo);
$OrigGenres = preg_replace('/&#39;/','\'',$OrigGenres);
$OrigRating = preg_replace('/&#39;/','\'',$OrigRating);
$OrigPremiered = preg_replace('/&#39;/','\'',$OrigPremiered);
$OrigWeight = preg_replace('/&#39;/','\'',$OrigWeight);
$OrigNetwork = preg_replace('/&#39;/','\'',$OrigNetwork);
$OrigWebChannel = preg_replace('/&#39;/','\'',$OrigWebChannel);
$OrigNetworkUrl = preg_replace('/&#39;/','\'',$OrigNetworkUrl);
$OrigPoster = preg_replace('/&#39;/','\'',$OrigPoster);
$OrigTrailer = preg_replace('/&#39;/','\'',$OrigTrailer);
$OrigFanArt = preg_replace('/&#39;/','\'',$OrigFanArt);

$OrigShowTitle = htmlspecialchars_decode($OrigShowTitle );
$OrigSynopsis = htmlspecialchars_decode($OrigSynopsis);
$OrigShowInfo = htmlspecialchars_decode($OrigShowInfo);
$OrigGenres = htmlspecialchars_decode($OrigGenres);
$OrigRating = htmlspecialchars_decode($OrigRating);
$OrigPremiered = htmlspecialchars_decode($OrigPremiered);
$OrigWeight = htmlspecialchars_decode($OrigWeight);
$OrigNetwork = htmlspecialchars_decode($OrigNetwork);
$OrigWebChannel = htmlspecialchars_decode($OrigWebChannel);
$OrigNetworkUrl = htmlspecialchars_decode($OrigNetworkUrl);
$OrigPoster = htmlspecialchars_decode($OrigPoster);
$OrigTrailer = htmlspecialchars_decode($OrigTrailer);
$OrigFanArt = preg_replace('/&#39;/','\'',$OrigFanArt);

$OrigPremiered = substr($OrigPremiered, 0, 10);
$OrigSynopsis = preg_replace('/\r/','',$OrigSynopsis); // remove new lines for the check

if ($_POST['title'] != $OrigShowTitle) {
    $LogDetails = "Title";
    $Concat = ', ';
}
if ($_POST['synopsis'] != $OrigSynopsis) {
    $LogDetails .= "{$Concat}Synopsis";
    $Concat = ', ';
}

if($_POST['showinfo']!= $OrigShowInfo) { $LogDetails .= "{$Concat}Show Info"; $Concat = ', '; }
if($_POST['genres']!= $OrigGenres) { $LogDetails .= "{$Concat}Genres"; $Concat = ', '; }
if($_POST['rating']!= $OrigRating) { $LogDetails .= "{$Concat}Rating"; $Concat = ', '; }
if($_POST['weight']!= $OrigWeight) { $LogDetails .= "{$Concat}Weight"; $Concat = ', '; }
if($_POST['premiered']!= $OrigPremiered) { $LogDetails .= "{$Concat}Premiered"; $Concat = ', '; }
if($_POST['network']!= $OrigNetwork) { $LogDetails .= "{$Concat}Network"; $Concat = ', '; }
if($_POST['webchannel']!= $OrigWebChannel) { $LogDetails .= "{$Concat}Web Channel"; $Concat = ', '; }
if($_POST['posterurl']!= $OrigPoster) { $LogDetails .= "{$Concat}Poster"; $Concat = ', '; }
if($_POST['networkurl']!= $OrigNetworkUrl) { $LogDetails .= "{$Concat}Network Logo"; $Concat = ', '; }
if($_POST['trailer']!= $OrigTrailer) { $LogDetails .= "{$Concat}Trailer"; $Concat = ', '; }
if($_POST['fanarturl']!= $OrigFanArt) { $LogDetails .= "{$Concat}fanart"; $Concat = ', '; }

write_log("Show $ShowID ($Title) was edited by ".$LoggedUser['Username']." ($LogDetails)");

header("Location: torrents.php?action=show&showid=".$ShowID."&did=3");
