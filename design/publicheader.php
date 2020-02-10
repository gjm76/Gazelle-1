<?php
global $LoggedUser, $Languages, $SSL;
define('FOOTER_FILE',SERVER_ROOT.'/design/publicfooter.php');
header('Content-Security-Policy: "default-src \'none\'; img-src \'self\'; script-src \'self\'; object-src \'self\';"');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?=display_str($PageTitle)?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
<?php if ($Mobile) { ?>
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0, user-scalable=no;"/>
<?php } ?>
    <link href="<?=STATIC_SERVER ?>styles/themes/public/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/public/style.css')?>" rel="stylesheet" type="text/css" />
    <script src="<?=STATIC_SERVER?>functions/sizzle.js" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/script_start.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/script_start.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/class_ajax.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_ajax.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/class_cookie.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_cookie.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/class_storage.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/class_storage.js')?>" type="text/javascript"></script>
    <script src="<?=STATIC_SERVER?>functions/global.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/global.js')?>" type="text/javascript"></script>
<?php if ($Mobile) { ?>
    <script src="<?=STATIC_SERVER?>styles/mobile/style.js?v=<?=filemtime(SERVER_ROOT.'/static/mobile/style.js')?>" type="text/javascript"></script>
<?php }

?>
</head>
<body>
<?php /*<div id="head">
<?=($SSL)?'<span>SSL</span>':''?>
</div>*/?>
<table id="maincontent">
    <tr>
        <td align="center" valign="middle">
            <div id="logo">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="https://eu.jotform.com/build/91986150186364">Application</a></li>
<?php if (OPEN_REGISTRATION) { ?>
                    <li><a href="register.php">Register</a></li>
<?php } ?>
                </ul>
            </div>
