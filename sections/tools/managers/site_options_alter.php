<?php


enforce_login();
authorize();

if (!check_perms('admin_manage_site_options')) error(403);

$Validate = new VALIDATE;

// Load the site options table
$DB->query("SELECT * FROM site_options;");
$SiteOptions = $DB->to_array('Name', MYSQLI_ASSOC);

// Populate the new values into the SiteOptions array
// and prepare the validation class
foreach($SiteOptions AS $SiteOption) {
    if ($SiteOption['Typeset'] == 'bool') {
        $SiteOptionsUpdate[$SiteOption['Name']] = ($_POST[$SiteOption['Name']] ? 'true' : 'false');
    } else {
        $SiteOptionsUpdate[$SiteOption['Name']] = $_POST[$SiteOption['Name']];
    }
    $Validate->SetFields($SiteOption['Name'], '1', $SiteOption['Typeset'], "Input error: $SiteOption[Name] contains invalid input");
}

// Validate
$Err = $Validate->ValidateForm($_POST, $Text);

if ($Err) {
    error($Err);
}

// Update the enrite table (it's not that big)
foreach($SiteOptions AS $SiteOption) {
    if($SiteOptionsUpdate[$SiteOption['Name']] !== $SiteOptions[$SiteOption['Name']])
        $DB->query("UPDATE site_options SET Value='".$SiteOptionsUpdate[$SiteOption['Name']]."' WHERE Name='".$SiteOption['Name']."'");
}

// Update the tracker
$DB->query("SELECT Name, Value FROM site_options WHERE Name IN ('SitewideFreeleechTime', 'SitewideFreeleechMode', 'SitewideDoubleseedTime', 'SitewideDoubleseedMode')");
$TrackerUpdate = array_column($DB->to_array(), 'Value', 'Name');
update_tracker('site_option', array('set' => 'freeleech', 'time' => strtotime($TrackerUpdate['SitewideFreeleechTime']),  'mode' => $TrackerUpdate['SitewideFreeleechMode']));
update_tracker('site_option', array('set' => 'doubleseed','time' => strtotime($TrackerUpdate['SitewideDoubleseedTime']), 'mode' => $TrackerUpdate['SitewideDoubleseedMode']));

$Cache->delete_value('global_site_options');

header('Location: tools.php?action=site_options');
