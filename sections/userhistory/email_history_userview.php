<?php
$UserID = $_GET['userid'];
if (!is_number($UserID)) {
  error(404);
}

$Self = ($UserID == $LoggedUser['ID']);

if (!check_perms('users_mod') && !$Self) {
  error(403);
}

$DB->query("
  SELECT DISTINCT Email
  FROM users_history_emails
  WHERE UserID = '$UserID'");

$EncEmails = $DB->collect("Email");
$Emails = [];

foreach ($EncEmails as $Enc) {
  if (!isset($Emails[$Enc])) {
    $Emails[$Enc] = [];
  }
  $Emails[$Enc][] = $Enc;
}

$DB->query("
  SELECT Email
  FROM users_main
  WHERE ID = '$UserID'");

list($Curr) = $DB->next_record();
//$Curr = Crypt::decrypt($Curr);

if (!$Self) {
  $DB->query("SELECT Username FROM users_main WHERE ID = '$UserID'");
  list($Username) = $DB->next_record();

  show_header("Email history for $Username");
} else {
  show_header("Your email history");
}

?>

<div class="header">
<?php if ($Self) { ?>
  <h2>Your email history</h2>
<?php } else { ?>
  <h2>Email history for <a href="user.php?id=<?=$UserID ?>"><?=$Username ?></a></h2>
<?php } ?>
</div>
<table width="100%">
  <tr class="colhead">
    <td>Email</td>
    <td>Expunge</td>
  </tr>
<?php foreach ($Emails as $Email => $Encs) { ?>
  <tr class="row">
    <td><?=display_str($Email)?></td>
    <td>
    <?php if ($Email != $Curr) { ?>
      <form action="delete.php" method="post">
        <input type="hidden" name="action" value="email">
        <?php foreach ($Encs as $Enc) { ?>
        <input type="hidden" name="emails[]" value="<?=$Enc?>">
        <?php } ?>
        <input type="submit" value="X">
      </form>
    <?php } ?>
    </td>
  </tr>
<?php } ?>
</table>
<?php show_footer(); ?>
