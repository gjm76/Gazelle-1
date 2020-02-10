<?php
enforce_login();

$UserID = $LoggedUser['ID'];
$SaveResult = '';
$AllStamps = Stamps::getAllStamps($UserID);

// Actually do the stamp saving!
if ($_POST['submit']) {
  authorize();
  // Get again (actually first; so we can verify what stamps the user has)
  $UserStamps = Stamps::getUserStamps($UserID, true);
  $Order = array_reduce($UserStamps, function($Max, $Stamp) { return max($Max, $Stamp['Order']); }, 0);

  $Values = [];
  $Cost = 0;
  foreach (explode(',',$_POST['stamps']) as $StampID) {
    if (!Stamps::isValidStamp($StampID)) {
      error('Invalid Stamp!');
    }
    if (array_key_exists($StampID, $UserStamps)) {
      $SaveResult .= '<div>You already have '.$UserStamps[$StampID]['Name'] . '!</div>';
    } else {
      $Order++;
      $Values[] = "($UserID, '$StampID', $Order, 0)";
      $Cost += $AllStamps[$StampID]['Cost'];
    }
  }

  $NumStamps = count($Values);
  $StampOrS = count($Values)>1 ? 'stamps' : 'stamp';

  if ($Cost > $LoggedUser['TotalCredits']) {
    $SaveResult .= "<div class='error'>You do not have enough cubits to purchase $NumStamps $StampOrS!</div>";
  } else if (count($Values) > 0) {
    $DB->query("INSERT INTO users_stamps (UserID, StampID, `Order`, IsHidden) 
      VALUES ".implode(',',$Values)."
      ON DUPLICATE KEY UPDATE `Order`=VALUES(`Order`), IsHidden=Values(IsHidden)");
  
    $Summary = sqltime().' - '."Collected $NumStamps $StampOrS. Cost: $Cost cubits";
    $UpdateSet[]="i.AdminComment=CONCAT_WS( '\n', '$Summary', i.AdminComment)";
    $Summary = sqltime()." | -$Cost cubits | "."Collected $NumStamps $StampOrS";
    $UpdateSet[]="i.BonusLog=CONCAT_WS( '\n', '$Summary', i.BonusLog)";
    $UpdateSet[]="m.Credits=(m.Credits-'$Cost')";
    $SET = implode(', ', $UpdateSet);
    $DB->query("UPDATE users_main AS m JOIN users_info AS i ON m.ID=i.UserID SET $SET WHERE m.ID='$UserID'");
    $Cache->delete_value('user_stats_'.$UserID);  // Needed for cubits
    $Cache->delete_value('user_info_heavy_'.$UserID); // I'm not sure we really need to reset these two, but... whatever
    $Cache->delete_value('user_info_' . $UserID);
  
    $SaveResult .= "<h4> $NumStamps $StampOrS purchased for " . number_format($Cost) . '!</h4>';    
  }
} // End actual purchase-stamp block

$UserStamps = Stamps::getUserStamps($UserID, true);

show_header('Stamps', 'stamps');
?>
<div class="thin">
  <?=$SaveResult?>
  <div id="bonusCredits" style="display:none"><?=$LoggedUser['TotalCredits']?></div>

<?php if (count($UserStamps)) {
  echo '<div id="current"><a style="float:right" href="bonus.php?action=manage_stamps">Reorder / Hide</a><h2>Currently Owned Stamps</h2>';
  foreach($UserStamps as $Stamp) echo Stamps::getStampImg($Stamp);
  echo '</div>';
} ?>
  <div id="cart" style="float:right; width:150px;"><h2>Shopping Cart</h2>
    <div id="cartResults">Empty! Pick some stamps!</div>
    <div id="cartCheckout" style="display:none">
      <form method="post" action="">
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <input type="hidden" name="stamps" id="stamps" />
        <input type="submit" name="submit" id="checkout" value="Checkout!"/>
      </form>
    </div>
    <div id="cartStamps"></div>
  </div>

  <h2>Stamp Market!</h2>
  <?php 

  $StampNav = '';
  $StampHtml = '';
  $LastLetter = '';
  foreach($AllStamps as $Stamp) {
    // Omit currently-owned stamps
    if (array_key_exists($Stamp['StampID'],$UserStamps)) continue;

    if ($Stamp['SortName']{0} !== $LastLetter && !(is_numeric($LastLetter) && is_numeric($Stamp['SortName']{0}))) {
      $LastLetter = $Stamp['SortName']{0};
      $StampNav .= '<a class="TOC" href="javascript:void(0);" data-target="#group'.$LastLetter.'">['.$LastLetter.']</a> ';
      $StampHtml .= '<h3 id="group'.$LastLetter.'">'.$LastLetter.'</h3>';
    }

    $StampHtml .= Stamps::getStampImg($Stamp, 'buyStamp', true, true, true);

  }
  ?>
  <input type="text" id="stampFilter" data-lpignore="true" placeholder="Filter Stamps"/>
  <?=$StampNav?>
  <div id="marketScroll" style="width: 755px; height: 400px; resize:vertical; overflow:auto;">
    <?=$StampHtml?>
  </div> <?php // Market div ?>
</div> <?php // thin wrapper ?>

<?php

show_footer();