<?php
/*************************************************************************\
//--------------Take restore credit -------------------------------------//

\*************************************************************************/

// Are they being tricky blighters?
if (!$_POST['userid'] || !is_number($_POST['userid'])) {
    error(404);
} elseif (!(check_perms('users_mod') || $_POST['userid'] === $LoggedUser['ID'])) {
    error(403);
}
authorize();
// End checking for moronity

$UserID = $_POST['userid'];

$DB->query("SELECT m.Credits, i.BonusLog
  FROM users_main AS m
  JOIN users_info AS i ON i.UserID = m.ID
  WHERE m.ID = '".$UserID."'");

if ($DB->record_count() == 0) { // If user doesn't exist (also moronic)
  header("Location: log.php?search=User+".$UserID);
}

list($BonusCredits, $BonusLog) = $DB->next_record(MYSQLI_NUM);

if (!$BonusLog) error("Could not load Bonus History for user $UserID");

$RealBonus = 0;
// Calculate what BonusPoints should be from the log (code copy pasta from user.php)
$BonusArr = explode(PHP_EOL, $BonusLog);
function addCredit($Total, $BonusRow) {
    if (preg_match('/^[^\|]+\|\s((\+|\-)\d+\.?\d*)\s/', $BonusRow, $Match)) {
        $Total += $Match[1];
    }
    return $Total;
};
$RealBonus = array_reduce($BonusArr, "addCredit");

// Load Slot Machine Data (copy pasta from user.php)
$UserResults = $Cache->get_value('sm_sum_history_'.$UserID);
if ($UserResults === false) {
    $DB->query("SELECT Count(ID), SUM(Spins), SUM(Won),SUM(Bet*Spins),(SUM(Won)/SUM(Bet*Spins))
              FROM sm_results WHERE UserID = $UserID");
    $UserResults = $DB->next_record();
    $Cache->cache_value('sm_sum_history_'.$UserID, $UserResults, 86400);
}
if (is_array($UserResults) && $UserResults[0] > 0) {
    list($Num, $NumSpins, $TotalWon, $TotalBet, $TotalReturn) = $UserResults;
    $RealBonus += $TotalWon - $TotalBet;
}

if (($BonusCredits + 1) > $RealBonus) {
  error('Logged cubits ('.number_format($RealBonus,2).') are lower than current cubits ('.number_format($BonusCredits,2).'). Saving you from yourself by not restoring to the log.');
}

$DB->query("UPDATE users_main
  SET Credits=$RealBonus
  WHERE ID=$UserID");

// Note - explicitly not updating bonuslog or calling update_bonus_log because this is just restoring to the sum of the log

write_user_log($UserID, $LoggedUser['Username']. " restored cubits to $RealBonus from the bonus log.");

// redirect to user page
header("location: user.php?id=$UserID");