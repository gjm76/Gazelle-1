<?php
// Tool for fixing broken cubits after bug, calculates cubits from bonus log + slot machine

if (!check_perms('admin_manage_networks')) {
    error(403);
}
/*
function fixcubits($bonus) {
 
   preg_match_all('/\|\s.+.\s\|/', $bonus, $matches); // extract cubits from log

   foreach ($matches[0] as $i) {
      $i = trim($i, ' | , credits');
      $i = str_replace(',', '', $i);                  // remove , from gifts
	   if(floatval($i) > 25500){                       // skip wrong ones above 25500
        continue;
	   }	
      $x += $i;
   }
   $x = $x + 5000.00;                                    // extra credit
   return $x;	
}
*/
show_header('Misc Fixers');
?>
<div class="thin">

<?php
echo "The Fix cubits tool is disabled.<br />";
/*
echo "List of Users: <br/><br/>";

$DB->query("SELECT Username, UserID, BonusLog FROM users_info AS ui JOIN users_main AS um ON um.ID = ui.UserID WHERE um.Enabled = '1' AND 
ui.Bonuslog ORDER BY um.Username");
$Bonuslogs = $DB->to_array("UserID", MYSQLI_ASSOC);

foreach ($Bonuslogs as $B) {
	$Username = $B[Username];
	$UserID = $B[UserID];
	$BonusLog = $B[BonusLog];
	$Cred = fixcubits($BonusLog);
   
   // include slot machine results	
   $DB->query("SELECT Count(ID), SUM(Spins), SUM(Won),SUM(Bet*Spins),(SUM(Won)/SUM(Bet*Spins))
               FROM sm_results WHERE UserID = $UserID");
   $UserResults = $DB->next_record();

   if (is_array($UserResults) && $UserResults[0] > 0) {
       list($Num, $NumSpins, $TotalWon, $TotalBet, $TotalReturn) = $UserResults;
   $Cred += ($TotalWon-$TotalBet);
   }
	
   $DB->query("UPDATE users_main SET Credits = $Cred WHERE ID = $UserID");
   echo "<a href='user.php?id=" . $UserID . "'>" . $Username . "</a> set with Cubits: " . $Cred . "<br />";
}
?>
    <h3>
<?php
echo count($Bonuslogs) . " rows updated successfully.";
?>
   </h3>
<?php

$DB->query("SELECT Username, UserID, Credits FROM users_info AS ui JOIN users_main AS um ON um.ID = ui.UserID WHERE um.Enabled = '1' AND 
um.Credits < 0 ORDER BY um.Username");
$Bonuslogs = $DB->to_array("UserID", MYSQLI_ASSOC);

if($Bonuslogs) {
	echo "<br/><hr><br/>Users with negative cubits: <br/><br/>";

   foreach ($Bonuslogs as $B) {
	   $Username = $B[Username];
	   $UserID = $B[UserID];
	   $Credits = $B[Credits];
      echo "<a href='user.php?id=" . $UserID . "'>" . $Username . "</a> Cubits: " . $Credits . "<br />";
   }
   echo "<h3>" . count($Bonuslogs) . " rows found.</h3>";
}
*/
echo "<br><hr><br>The Fix re-seeds tool is disabled.<br><br>";
/*
// ------- Add cross seeds to snatches for seed times to count
$DB->query("SELECT xf.uid,xf.fid,xf.ip from xbt_files_users as xf 
            LEFT JOIN xbt_snatched AS xs ON (xf.uid = xs.uid and xf.fid = xs.fid)
            WHERE xs.uid is null and xs.fid is null and xf.active = 1 and xf.remaining = 0");

$CrossSeeds = $DB->to_array();

echo "Found ".count($CrossSeeds)." records\n<br>";

$i = 0;
foreach ($CrossSeeds as $Search){
   $DB->query("INSERT INTO xbt_snatched (uid, tstamp, fid, IP, seedtime, upload) VALUES
              ($Search[uid], '" . time (). "', $Search[fid], '$Search[ip]', 0, '1')");
   echo "User <a href='user.php?id=" . $Search[uid] . "'>" . $Search[uid] . "</a> updated file id <a href='/torrents.php?id=" . $Search[fid] . "'>". $Search[fid] ."</a><br />";
   $i++;
   if($i == 1000) break;              
}
*/

echo "<br><hr><br>Fix Screens, Trailers and Media Info tool is disabled.<br><br>"; 
/*
$DB->query("SELECT count(tg.ID) FROM torrents_group AS tg 
            LEFT JOIN torrents AS t ON t.GroupID = tg.ID   
            WHERE tg.Body IS NOT NULL AND tg.Body != ''
            AND t.GroupID IS NOT NULL
            ");
list($Total) = $DB->next_record();

echo $Total." entries found<br /><br />";

$DB->query("SELECT tg.ID, tg.Body FROM torrents_group AS tg 
            LEFT JOIN torrents AS t ON t.GroupID = tg.ID   
            WHERE tg.Body IS NOT NULL AND tg.Body != ''
            AND t.GroupID IS NOT NULL
            LIMIT 700");

$Torrents = $DB->to_array();
$Matches = array();
$Screens = array();
$i = 0;
foreach ($Torrents as $Body){
   
   preg_match('/\[img\].+\n.+/i', $Body[1], $Matches); // collect screens
   if($Matches[0]) {
   	$Screens[$i]['ID'] = $Body[0];
   	$Matches[0] = str_replace('[/center]', '', $Matches[0]);
   	$Screens[$i]['Screens'] = db_string("[center]".$Matches[0]."[/center]");
   }
   preg_match('/\[video\=.+/i', $Body[1], $Matches); // collect trailers
   if($Matches[0]) {
   	$Screens[$i]['ID'] = $Body[0];
   	$Screens[$i]['Trailer'] = db_string("[align=center]".$Matches[0]);
   }	

   $Start = strpos($Body[1], '[mediainfo]'); // collect media info
   $End = strpos($Body[1], '[/mediainfo]');
   if($Start) {
   	$Screens[$i]['ID'] = $Body[0];
   	$Screens[$i]['Mediainfo'] = db_string(substr($Body[1], $Start, $End-$Start+12));
   }
 	$i++;
   
}

// update db
foreach ($Screens as $Screen) {
   $DB->query("UPDATE torrents_group SET Screens = '$Screen[Screens]', Trailer = '$Screen[Trailer]', Mediainfo = '$Screen[Mediainfo]' 
              WHERE ID = '$Screen[ID]'");
}

// cleanup
$i = 1;
foreach ($Screens as $Screen) {
   $DB->query("UPDATE torrents_group SET Body = '' WHERE ID = '$Screen[ID]'");
   echo $i.". <a href='/torrents.php?id=".$Screen[ID]."'>".$Screen[ID]."</a><br />";
   $i++;
}

if(count($Screens) || count($Trailers) || count($MediaInfos))  echo "<br />".count($Screens)." DB entries updated<br />";
*/
echo "<br><hr><br>Auto mark okay old torrents tool is disabled.<br><br>";
/*
$IDs = array();
$DB->query("SELECT t.GroupID
            FROM torrents AS t
            JOIN torrents_group AS tg ON tg.ID = t.GroupID
            LEFT JOIN users_main AS um ON um.ID=t.UserID
            LEFT JOIN torrents_reviews AS tr ON tr.GroupID=t.GroupID
            WHERE tr.GroupID IS NULL
            ");

$IDs = $DB->to_array();
foreach ($IDs as $Torrent) {
   $DB->query("INSERT INTO torrents_reviews (GroupID, ReasonID, UserID, ConvID, Time, Status, Reason, KillTime)
               VALUES ('".$Torrent[GroupID]."', '-1', '535', 'NULL', '0000-00-00 00:00:00', 'Okay', 'NULL', '0000-00-00 00:00:00')");
}

if(count($IDs)) echo "Found and okayed: ".count($IDs)."<br /><br />";
*/
echo "<br><hr><br>Seasons with no Show Info (scrape needed): <br><br>";

$DB->query("SELECT tg.ID, tg.Name FROM torrents_group AS tg 
            LEFT JOIN torrents AS t ON t.GroupID = tg.ID   
            WHERE (tg.Synopsis IS NULL OR tg.Synopsis = '')
            AND t.ID IS NOT NULL
            AND (t.AirDate IS NULL OR t.AirDate = '')  
            ");

$Torrents = $DB->to_array();

echo "Found: ".count($Torrents)."<br /><br />";

$i = 0;
foreach ($Torrents as $Torrent) {
 $i++;
 echo "$i. <a href='/torrents.php?id=".$Torrent['ID']."'>".$Torrent['Name']."</a><br />";
}

show_footer();
?>
