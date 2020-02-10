<?php
enforce_login();


// Save updated order / hidden settings
if ($_POST['submit']) {
  authorize();

  // Get again (actually first; so we can verify what stamps the user has)
  $UserStamps = Stamps::getUserStamps($LoggedUser['ID'], true);

  $Values = [];
  foreach($_POST['stamp'] as $Key => $Settings) {
    if (!$UserStamps[$Key]) {
      error('You just tried to add a stamp you don\'t own. Naughty naughty.');
    }
    $Order = is_numeric($Settings['order']) ? $Settings['order'] : 0;
    $Hidden = $Settings['hidden'] ? 1 : 0;
    $Values[] = "($LoggedUser[ID], '$Key', $Order, $Hidden)";
  }

  $DB->query("INSERT INTO users_stamps (UserID, StampID, `Order`, IsHidden) 
    VALUES ".implode(',',$Values)."
    ON DUPLICATE KEY UPDATE `Order`=VALUES(`Order`), IsHidden=Values(IsHidden)");
}

$UserStamps = Stamps::getUserStamps($LoggedUser['ID'], true);

if (count($UserStamps) == 0) {
  error('You have no stamps to manage!');
}


show_header('Manage Stamps');
?>

<div class="thin">
  <h2>Manage Stamps</h2>
  <form method="post" action="">
    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
    <table class="stampTable">
      <tr>
        <th>Name</th>
        <th>Stamp</th>
        <th>Order</th>
        <th>Hidden</th>
      </tr>
      <?php 
      foreach($UserStamps as $Key => $Stamp) { ?>
        <tr>
          <td><?=$Stamp['Name']?></td>
          <td><img src="<?=$Stamp['Src']?>"></td>
          <td><input size="3" type="text" name="stamp[<?=$Key?>][order]" value="<?=$Stamp['Order']?>" data-lpignore="true" /></td>
          <td><input type="checkbox" name="stamp[<?=$Key?>][hidden]" <?= selected('IsHidden', 1, 'checked', $Stamp) ?>/></td>
        </tr>
      <?php  } ?>
      <tr>
        <td colspan="2">&nbsp</td>
        <td colspan="2"><input type="submit" name="submit" value="Save Settings" style="margin: 3px"/></td>
      </tr>
    </table>
  </form>
</div>
<?php
show_footer();