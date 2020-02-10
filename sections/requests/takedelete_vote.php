<?php
authorize();
if (!check_perms("site_moderate_requests")) {
    error(404);
}

$RequestID = $_POST['id'];
$VoterID = $_POST['voterid'];

if (is_number($RequestID) && is_number($VoterID)) {
    $DB->query("SELECT Bounty FROM requests_votes WHERE UserID = ".$VoterID." AND RequestID = ".$RequestID);
    if ($DB->record_count() < 1) {
        error(404);
    }
    list($Bounty)=$DB->next_record();

    $DB->query("UPDATE users_main SET Credits = (Credits + $Bounty) WHERE ID = ".$VoterID);
    $DB->query("SELECT Title FROM requests WHERE ID = ".$RequestID);
    list($Title)=$DB->next_record();
    write_user_log($VoterID, "Added +". $Bounty. " Cubits for deleted vote on request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url] by $LoggedUser[Username]\nReason: ".$_POST['reason']);

    update_bonus_log($VoterID, sqltime()." | +".$Bounty." credits | ".ucfirst("added +". $Bounty. " for deleted vote on request [url=/requests.php?action=view&id={$RequestID}]{$Title}[/url]"));

    $DB->query("DELETE FROM requests_votes WHERE UserID = ".$VoterID." AND RequestID = ".$RequestID);

    $DB->query("SELECT RequestID FROM requests_votes WHERE RequestID = ".$RequestID);
    if ($DB->record_count() < 1) {
        $DB->query("DELETE FROM requests WHERE ID = ".$RequestID);
        $DB->query("DELETE FROM requests_comments WHERE RequestID = ".$RequestID);
        $DB->query("DELETE FROM requests_tags WHERE RequestID = ".$RequestID);
        write_log("Request $RequestID ($Title) was deleted by ".$LoggedUser['Username']." for the reason: ".$_POST['reason']);
    }
    $Cache->delete_value('user_stats_'.$LoggedUser['ID']);
    $Cache->delete_value('request_'.$RequestID);
    $Cache->delete_value('request_votes_'.$RequestID);
    update_sphinx_requests($RequestID);

    $DB->query("SELECT RequestID FROM requests_votes WHERE RequestID = ".$RequestID);
    if ($DB->record_count() < 1) {
        header("Location: requests.php");
    } else {
        header("Location: requests.php?action=view&id=".$RequestID);
    }

} else {
    error(404);
}
?>
