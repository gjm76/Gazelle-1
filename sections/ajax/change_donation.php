<?php
if (!check_perms('admin_donor_log')) error(403,true);

include(SERVER_ROOT.'/sections/donate/functions.php');

$address = db_string($_REQUEST['address']);
$state = $_REQUEST['state'];
if (!in_array($state, array('unused','submitted','cleared'))) error(0, true);

$DB->query("UPDATE bitcoin_donations SET state='$state' WHERE public='$address'");
$result = $DB->affected_rows();

echo $result;
