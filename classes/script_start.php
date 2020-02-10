<?php
/* -- Script Start Class -------------------------------- */
/* ------------------------------------------------------ */
/* This isnt really a class but a way to tie other	  */
/* classes and functions used all over the site to the    */
/* page currently being displayed.                        */
/* ------------------------------------------------------ */
/* The code that includes the main php files and          */
/* generates the page are at the bottom.                  */
/* ------------------------------------------------------ */
/* ****************************************************** */

require 'config.php'; //The config contains all site wide configuration information

//Deal with dumbasses
if (isset($_REQUEST['info_hash']) && isset($_REQUEST['peer_id'])) {
    die('d14:failure reason40:Invalid .torrent, try downloading again.e');
}

require(SERVER_ROOT . '/classes/class_proxies.php');
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && proxyCheck($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

$SSL = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

if (!isset($argv) && !empty($_SERVER['HTTP_HOST'])) { //Skip this block if running from cli or if the browser is old and shitty
    if (!$SSL && $_SERVER['HTTP_HOST'] == 'www.' . NONSSL_SITE_URL) {
        header('Location: http://' . NONSSL_SITE_URL . $_SERVER['REQUEST_URI']);
        die();
    }
    if ($SSL && $_SERVER['HTTP_HOST'] == 'www.' . NONSSL_SITE_URL) {
        header('Location: https://' . SSL_SITE_URL . $_SERVER['REQUEST_URI']);
        die();
    }
    if (SSL_SITE_URL != NONSSL_SITE_URL) {
        if (!$SSL && $_SERVER['HTTP_HOST'] == SSL_SITE_URL) {
            header('Location: https://' . SSL_SITE_URL . $_SERVER['REQUEST_URI']);
            die();
        }
        if ($SSL && $_SERVER['HTTP_HOST'] == NONSSL_SITE_URL) {
            header('Location: https://' . SSL_SITE_URL . $_SERVER['REQUEST_URI']);
            die();
        }
    }
    if ($_SERVER['HTTP_HOST'] == 'www.m.' . NONSSL_SITE_URL) {
        header('Location: http://m.' . NONSSL_SITE_URL . $_SERVER['REQUEST_URI']);
        die();
    }
}

$ScriptStartTime = microtime(true); //To track how long a page takes to create

ob_start(); //Start a buffer, mainly in case there is a mysql error

require(SERVER_ROOT . '/classes/class_time.php');      //Require the time class
require(SERVER_ROOT . '/classes/class_paranoia.php');  //Require the paranoia check_paranoia function
require(SERVER_ROOT . '/classes/regex.php');

$Debug = new DEBUG;
$Debug->handle_errors();
$Debug->set_flag('Debug constructed');

$DB = new DB_MYSQL;
$Cache = new CACHE;
$Enc = new CRYPT;
$UA = new USER_AGENT;
$SS = new SPHINX_SEARCH;
$Tracker = new TRACKER;

//Begin browser identification

$Browser = $UA->browser($_SERVER['HTTP_USER_AGENT']);
$OperatingSystem = $UA->operating_system($_SERVER['HTTP_USER_AGENT']);
//$Mobile = $UA->mobile($_SERVER['HTTP_USER_AGENT']);
$Mobile = in_array($_SERVER['HTTP_HOST'], array('m.' . NONSSL_SITE_URL, 'm.' . NONSSL_SITE_URL));

$Debug->set_flag('start user handling');

// Get permissions
list($Classes, $ClassLevels, $ClassNames) = $Cache->get_value('classes');
if (!$Classes || !$ClassLevels) {
    $DB->query("SELECT ID, Name, Description, Level, Color, LOWER(REPLACE(Name,' ','')) AS ShortName, IsUserClass FROM permissions ORDER BY IsUserClass, Level"); //WHERE IsUserClass='1'
    $Classes = $DB->to_array('ID');
    $ClassLevels = $DB->to_array('Level');
    $ClassNames = $DB->to_array('ShortName');
    $Cache->cache_value('classes', array($Classes, $ClassLevels, $ClassNames), 0);
}
$Debug->set_flag('Loaded permissions');
$NewCategories = $Cache->get_value('new_categories');
if (!$NewCategories) {
    $DB->query('SELECT id, name, image, tag, min_upload_screenshots FROM categories ORDER BY name ASC');
    $NewCategories = $DB->to_array('id');
    $Cache->cache_value('new_categories', $NewCategories);
}
$Debug->set_flag('Loaded new categories');

//-----------------------------------------------------------------------------------
/////////////////////////////////////////////////////////////////////////////////////
//-- Load user information ----------------------------------------------------------
// User info is broken up into many sections
// Heavy - Things that the site never has to look at if the user isn't logged in (as opposed to things like the class, donor status, etc)
// Light - Things that appear in format_user
// Stats - Uploaded and downloaded - can be updated by a script if you want super speed
// Session data - Information about the specific session
// Enabled - if the user's enabled or not
// Permissions

if (isset($_COOKIE['session'])) {
    $LoginCookie = $Enc->decrypt($_COOKIE['session']);
}
if (isset($LoginCookie)) {
    list($SessionID, $LoggedUser['ID']) = explode("|~|", $Enc->decrypt($LoginCookie));
    $LoggedUser['ID'] = (int) $LoggedUser['ID'];

    $UserID = $LoggedUser['ID']; //TODO: UserID should not be LoggedUser

    if (!$LoggedUser['ID'] || !$SessionID) {
        logout();
    }

    $UserSessions = $Cache->get_value('users_sessions_' . $UserID);
    if (!is_array($UserSessions)) {
        $DB->query("SELECT
            SessionID,
            Browser,
            OperatingSystem,
            IP,
            LastUpdate
            FROM users_sessions
            WHERE UserID='$UserID'
            AND Active = 1
            ORDER BY LastUpdate DESC");
        $UserSessions = $DB->to_array('SessionID', MYSQLI_ASSOC);
        $Cache->cache_value('users_sessions_' . $UserID, $UserSessions, 0);
    }

    if (!array_key_exists($SessionID, $UserSessions)) {
        logout();
    }

    // Check if user is enabled
    $Enabled = $Cache->get_value('enabled_' . $LoggedUser['ID']);
    if ($Enabled === false) {
        $DB->query("SELECT Enabled FROM users_main WHERE ID='$LoggedUser[ID]'");
        list($Enabled) = $DB->next_record();
        $Cache->cache_value('enabled_' . $LoggedUser['ID'], $Enabled, 0);
    }
    if ($Enabled == 2) {

        logout();
    }

    // Up/Down stats
    $UserStats = $Cache->get_value('user_stats_' . $LoggedUser['ID']);
    if (!is_array($UserStats)) {
        $DB->query("SELECT Uploaded AS BytesUploaded, Downloaded AS BytesDownloaded, RequiredRatio, Credits as TotalCredits FROM users_main WHERE ID='$LoggedUser[ID]'");
        $UserStats = $DB->next_record(MYSQLI_ASSOC);
        $Cache->cache_value('user_stats_' . $LoggedUser['ID'], $UserStats, 3600);
    }

    // Get info such as username
    $LightInfo = user_info($LoggedUser['ID']);
    $HeavyInfo = user_heavy_info($LoggedUser['ID']);

    // Set referrer if user has chosen it
    define('ANONYMIZER_URL', isset($HeavyInfo['UseAnonymizer']) && $HeavyInfo['UseAnonymizer'] ? ANONYMIZER_URL_PREFIX : '');

    // Get user permissions
    $Permissions = get_permissions($LightInfo['PermissionID']);
    // Create LoggedUser array
    $LoggedUser = array_merge($HeavyInfo, $LightInfo, $Permissions, $UserStats);

    $LoggedUser['RSS_Auth'] = md5($LoggedUser['ID'] . RSS_HASH . $LoggedUser['torrent_pass']);

    //$LoggedUser['RatioWatch'] as a bool to disable things for users on Ratio Watch
    $LoggedUser['RatioWatch'] = (
            $LoggedUser['RatioWatchEnds'] != '0000-00-00 00:00:00' &&
           // time() < strtotime($LoggedUser['RatioWatchEnds']) &&
            ($LoggedUser['BytesDownloaded'] * $LoggedUser['RequiredRatio']) > $LoggedUser['BytesUploaded']
            );
    if (!isset($LoggedUser['ID'])) {
        $Debug->log_var($LightInfo, 'LightInfo');
        $Debug->log_var($HeavyInfo, 'HeavyInfo');
        $Debug->log_var($Permissions, 'Permissions');
        $Debug->log_var($UserStats, 'UserStats');
    }

    //Load in the permissions
    $LoggedUser['Permissions'] = get_permissions_for_user($LoggedUser['ID'], $LoggedUser['CustomPermissions']);

    //Change necessary triggers in external components
    $Cache->CanClear = check_perms('admin_clear_cache');

    $RealIP = $_SERVER['REMOTE_ADDR'];
    // Because we <3 our staff
    if (check_perms('site_disable_ip_history')) {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    // Update LastUpdate every 10 minutes
    if (strtotime($UserSessions[$SessionID]['LastUpdate']) + 600 < time()) {
        $DB->query("UPDATE users_main SET LastAccess='" . sqltime() . "' WHERE ID='$LoggedUser[ID]'");
        $DB->query("UPDATE users_sessions SET IP='" . $_SERVER['REMOTE_ADDR'] . "', Browser='" . $Browser . "', OperatingSystem='" . $OperatingSystem . "', LastUpdate='" . sqltime() . "' WHERE UserID='$LoggedUser[ID]' AND SessionID='" . db_string($SessionID) . "'");
        $Cache->begin_transaction('users_sessions_' . $UserID);
        $Cache->delete_row($SessionID);
        $Cache->insert_front($SessionID, array(
            'SessionID' => $SessionID,
            'Browser' => $Browser,
            'OperatingSystem' => $OperatingSystem,
            'IP' => $_SERVER['REMOTE_ADDR'],
            'LastUpdate' => sqltime()
        ));
        $Cache->commit_transaction(0);
    }

    // Notifications
    if (isset($LoggedUser['Permissions']['site_torrents_notify'])) {
        $LoggedUser['Notify'] = $Cache->get_value('notify_filters_' . $LoggedUser['ID']);
        if (!is_array($LoggedUser['Notify'])) {
            $DB->query("SELECT ID, Label FROM users_notify_filters WHERE UserID='$LoggedUser[ID]'");
            $LoggedUser['Notify'] = $DB->to_array('ID');
            $Cache->cache_value('notify_filters_' . $LoggedUser['ID'], $LoggedUser['Notify'], 2592000);
        }
    }

    // IP changed
    if ($LoggedUser['IP'] != $_SERVER['REMOTE_ADDR'] && !check_perms('site_disable_ip_history')) {
        if (site_ban_ip($_SERVER['REMOTE_ADDR'])) {
            error('Your IP has been banned.');
        }

        $CurIP = db_string($LoggedUser['IP']);
        $NewIP = db_string($_SERVER['REMOTE_ADDR']);
        $DB->query("UPDATE users_history_ips SET
                EndTime='" . sqltime() . "'
                WHERE EndTime IS NULL
                AND UserID='$LoggedUser[ID]'
                AND IP='$CurIP'");
        $DB->query("INSERT IGNORE INTO users_history_ips
                (UserID, IP, StartTime) VALUES
                ('$LoggedUser[ID]', '$NewIP', '" . sqltime() . "')");

        $ipcc = geoip($NewIP);
        $DB->query("UPDATE users_main SET IP='$NewIP', ipcc='$ipcc' WHERE ID='$LoggedUser[ID]'");
        $Cache->begin_transaction('user_info_heavy_' . $LoggedUser['ID']);
        $Cache->update_row(false, array('IP' => $_SERVER['REMOTE_ADDR']));
        $Cache->commit_transaction(0);
    }

    // Get stylesheets
    $Stylesheets = $Cache->get_value('stylesheets');
    if (!is_array($Stylesheets)) {
        $DB->query('SELECT ID, LOWER(REPLACE(Name," ","_")) AS Name, Name AS ProperName FROM stylesheets');
        $Stylesheets = $DB->to_array('ID', MYSQLI_BOTH);
        $Cache->cache_value('stylesheets', $Stylesheets, 600);
    }

    //A9 TODO: Clean up this messy solution
    $LoggedUser['StyleName'] = $Stylesheets[$LoggedUser['StyleID']]['Name'];

    if (empty($LoggedUser['Username'])) {
        logout(); // Ghost
    }
}

$Debug->set_flag('end user handling');

$SiteOptions=array();
load_site_options();

// full logging for analysing bots!
if ($FullLogging!='0') {
    $uri = $_SERVER['REQUEST_URI'];
    if($FullLogging=='3' ||
       ($FullLogging=='2' && !in_array($uri, array( "/torrents.php?action=resort_tags", "/torrents.php?action=update_status"))) ||
       ($FullLogging=='1' && substr($uri, 0, 9)=='/user.php') ) {
        $vars = implode("|", $_REQUEST);
        if ($vars) {
            $keys = implode("|", array_keys($_REQUEST));
            $vars = "~$keys~$vars";
        }
        $DB->query("INSERT INTO full_log (userID, time, ip, ipnum, request, variables)
                         VALUES ( '$LoggedUser[ID]' , '".db_string(sqltime())."', '".db_string($RealIP)."', '" . ip2unsigned($RealIP) . "',
                                  '".db_string($uri)."', '".db_string($_SERVER['REQUEST_METHOD']. $vars )."' )");
    }
}
unset($RealIP);
unset($keys);
unset($vars);

$TorrentUserStatus = $Cache->get_value('torrent_user_status_'.$LoggedUser['ID']);
if ($TorrentUserStatus === false) {
    $DB->query("
        SELECT fid as TorrentID,
            IF(xbt.remaining >  '0', 'L', 'S') AS PeerStatus
        FROM xbt_files_users AS xbt
            WHERE active='1' AND uid =  '".$LoggedUser['ID']."'");
    $TorrentUserStatus = $DB->to_array('TorrentID');
    $Cache->cache_value('torrent_user_status_'.$LoggedUser['ID'], $TorrentUserStatus, 600);
}

$Debug->set_flag('start function definitions');

// Refresh Show Page
function update_show($GroupID) {

    global $DB, $Cache;
    
    $DB->query("SELECT TVMAZE FROM torrents_group WHERE ID='$GroupID'");
    list($TVMaze) = $DB->next_record();
    if ($TVMaze) {
       $Cache->delete_value('show_'.$TVMaze);
    }
}

// Get cached user info, is used for the user loading the page and usernames all over the site
// AND for looking up advanced tags permissions
function user_info($UserID)
{
    global $DB, $Cache;
    $UserInfo = $Cache->get_value('user_info_' . $UserID);
    // the !isset($UserInfo['Paranoia']) can be removed after a transition period
    if (empty($UserInfo) || empty($UserInfo['ID']) || !isset($UserInfo['Paranoia'])) {
        $DB->query("SELECT
            m.ID,
            m.Username,
            m.PermissionID,
            m.Paranoia,
            i.Donor,
            i.Warned,
            i.Avatar,
            m.Enabled,
            m.Title,
            i.CatchupTime,
            m.Visible,
            m.Signature,
            i.TorrentSignature,
            m.GroupPermissionID,
            m.ipcc
            FROM users_main AS m
            INNER JOIN users_info AS i ON i.UserID=m.ID
            WHERE m.ID='$UserID'");

        if ($DB->record_count() == 0) { // Deleted user, maybe?
            $UserInfo = array('ID'=>'','Username'=>'','PermissionID'=>0,'Paranoia'=>array(),'Donor'=>false,'Warned'=>'0000-00-00 00:00:00',
                    'Avatar'=>'','Enabled'=>0,'Title'=>'', 'CatchupTime'=>0, 'Visible'=>'1','Signature'=>'','TorrentSignature'=>'',
                    'GroupPermissionID'=>0,'ipcc'=>'??');

        } else {
            $UserInfo = $DB->next_record(MYSQLI_ASSOC, array('Paranoia', 'Title'));
            $UserInfo['CatchupTime'] = strtotime($UserInfo['CatchupTime']);
            $UserInfo['Paranoia'] = unserialize($UserInfo['Paranoia']);
            if ($UserInfo['Paranoia'] === false) {
                $UserInfo['Paranoia'] = array();
            }
        }
        $Cache->cache_value('user_info_'.$UserID, $UserInfo, 2592000);
    }
    if (strtotime($UserInfo['Warned']) < time()) {
        $UserInfo['Warned'] = '0000-00-00 00:00:00';
        $Cache->cache_value('user_info_'.$UserID, $UserInfo, 2592000);
    }

    // Image proxy
    if (check_perms('site_proxy_images') && !empty($UserInfo['Avatar'])) {
        $UserInfo['Avatar'] = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&amp;avatar='.$UserID.'&amp;i='.urlencode($UserInfo['Avatar']);
    }

    return $UserInfo;
}

// Only used for current user
function user_heavy_info($UserID)
{
    global $DB, $Cache;
    $HeavyInfo = $Cache->get_value('user_info_heavy_' . $UserID);

    if (empty($HeavyInfo)) {

        $DB->query("SELECT
            m.Invites,
            m.torrent_pass,
            m.IP,
            m.CustomPermissions,
            m.can_leech AS CanLeech,
            i.AuthKey,
            i.RatioWatchEnds,
            i.RatioWatchDownload,
            i.StyleID,
            i.StyleURL,
            i.DisableInvites,
            i.DisablePosting,
            i.DisableUpload,
            i.DisableAvatar,
            i.DisablePM,
            i.DisableRequests,
            i.SiteOptions,
            i.DownloadAlt,
            i.SSLTracker,
            i.LastReadNews,
            i.RestrictedForums,
            i.PermittedForums,
            m.FLTokens,
            m.personal_freeleech,
            m.Credits,
            i.SupportFor,
            i.BlockPMs,
            i.CommentsNotify,
            i.TimeZone,
            i.SuppressConnPrompt,
            i.DisableForums,
            i.DisableTagging,
            i.DisableSignature,
            i.DisableTorrentSig,
            i.DisableIRC,
            m.HnR
            FROM users_main AS m
            INNER JOIN users_info AS i ON i.UserID=m.ID
            WHERE m.ID='$UserID'");
        $HeavyInfo = $DB->next_record(MYSQLI_ASSOC, array('CustomPermissions', 'SiteOptions'));

        if (!empty($HeavyInfo['CustomPermissions'])) {
            $HeavyInfo['CustomPermissions'] = unserialize($HeavyInfo['CustomPermissions']);
        } else {
            $HeavyInfo['CustomPermissions'] = array();
        }

        if (!empty($HeavyInfo['RestrictedForums'])) {
            $RestrictedForums = explode(',', $HeavyInfo['RestrictedForums']);
        } else {
            $RestrictedForums = array();
        }
        unset($HeavyInfo['RestrictedForums']);

        if (!empty($HeavyInfo['PermittedForums'])) {
            $PermittedForums = explode(',', $HeavyInfo['PermittedForums']);
        } else {
            $PermittedForums = array();
        }
        unset($HeavyInfo['PermittedForums']);

        if (!empty($PermittedForums) || !empty($RestrictedForums)) {
            $HeavyInfo['CustomForums'] = array();
            foreach ($RestrictedForums as $ForumID) {
                $HeavyInfo['CustomForums'][$ForumID] = 0;
            }
            foreach ($PermittedForums as $ForumID) {
                $HeavyInfo['CustomForums'][$ForumID] = 1;
            }
        } else {
            $HeavyInfo['CustomForums'] = null;
        }

        $HeavyInfo['SiteOptions'] = unserialize($HeavyInfo['SiteOptions']);
        if (!empty($HeavyInfo['SiteOptions'])) {
            $HeavyInfo = array_merge($HeavyInfo, $HeavyInfo['SiteOptions']);
        }
        unset($HeavyInfo['SiteOptions']);

        //if (!isset($HeavyInfo['MaxTags'])) $HeavyInfo['MaxTags'] = 16;

        if (!empty($HeavyInfo['Badges'])) {
            $HeavyInfo['Badges'] = unserialize($HeavyInfo['Badges']);
            //$HeavyInfo = array_merge($HeavyInfo, $HeavyInfo['Badges']);
        } else {
            $HeavyInfo['Badges'] = array();
        }

        if (empty($HeavyInfo['TimeZone']) || $HeavyInfo['TimeZone'] == '')
            $HeavyInfo['TimeOffset'] = 0;
        else {
            $HeavyInfo['TimeOffset'] = get_timezone_offset($HeavyInfo['TimeZone']);
        }

        $Cache->cache_value('user_info_heavy_' . $UserID, $HeavyInfo, 0);
    }
    // add this here for implementation to live server (so logged in users dont get blank tags)
    // but can move up a few lines to inside if statements at some point in the future.
    if (!isset($HeavyInfo['MaxTags'])) $HeavyInfo['MaxTags'] = 100;
    return $HeavyInfo;
}

/**
 * get the users seed leech info (caches for 15 mins)
 *
 * @param int $UserID
 * @return array Returns array('Seeding'=>$Seeding, 'Leeching'=>$Leeching)
 */
function user_peers($UserID)
{
    global $DB, $Cache;
    $PeerInfo = $Cache->get_value('user_peers_' . $UserID);
    if ($PeerInfo===false) {
        $DB->query("SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(x.uid)
                      FROM xbt_files_users AS x
                      JOIN torrents AS t ON t.ID=x.fid
                     WHERE x.uid='$UserID' AND x.active=1
                  GROUP BY Type");
        $PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
        $Seeding = isset($PeerCount['Seeding'][1]) ? $PeerCount['Seeding'][1] : 0;
        $Leeching = isset($PeerCount['Leeching'][1]) ? $PeerCount['Leeching'][1] : 0;
        $PeerInfo = array('Seeding'=>$Seeding, 'Leeching'=>$Leeching);
        $Cache->cache_value('user_peers_' . $UserID, $PeerInfo, 900);
    }

    return $PeerInfo;
}

function get_userid($Username)
{
    global $DB;
    if(!$Username) return 0;
    $DB->query("SELECT ID FROM users_main WHERE Username = '" . db_string($Username). "'");
    if ($DB->record_count() > 0) {
        list($UserID) = $DB->next_record();

        return $UserID;
   }

   return 0;
}

/**
 * update a users site_options field with a new value
 *
 * @param int $UserID
 * @param int $NewOptions options to overwrite in format array('OptionName' => $Value, 'OptionName' => $Value)
 */
function update_site_options($UserID, $NewOptions)
{
    if (!is_number($UserID)) {
        error(0);
    }
    if (empty($NewOptions) || !is_array($NewOptions)) {
        return false;
    }
    global $DB, $Cache, $LoggedUser;

    // Get SiteOptions
    $DB->query("SELECT SiteOptions FROM users_info WHERE UserID = $UserID");
    list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
    $SiteOptions = unserialize($SiteOptions);

    // Get HeavyInfo
    $HeavyInfo = user_heavy_info($UserID);

    // Insert new/replace old options
    $SiteOptions = array_merge($SiteOptions, $NewOptions);
    $HeavyInfo = array_merge($HeavyInfo, $NewOptions);

    // Update DB
    $DB->query("UPDATE users_info SET SiteOptions = '" . db_string(serialize($SiteOptions)) . "' WHERE UserID = $UserID");

    // Update cache
    $Cache->cache_value('user_info_heavy_' . $UserID, $HeavyInfo, 0);

    // Update $LoggedUser if the options are changed for the current
    if ($LoggedUser['ID'] == $UserID) {
        $LoggedUser = array_merge($LoggedUser, $NewOptions);
        $LoggedUser['ID'] = $UserID; // We don't want to allow userid switching
    }
}

function get_next_bonus_update($LastBonusTime)
{
    return strftime("%e %b %Y  %r", strtotime("+1 week", $LastBonusTime));
}

function load_site_options()
{
    global $SiteOptions, $DB, $Cache;
    require('default_site_options.php');

    // Load the site options array
    if(!$SiteOptions = $Cache->get_value('global_site_options')) {
        $DB->query("INSERT IGNORE INTO site_options VALUES $DefaultSiteOptions");
        $DB->query("SELECT Name, Value FROM site_options");
        $SiteOptions = array_column($DB->to_array(), 'Value', 'Name');
	$DB->query("SELECT Name, Value FROM site_options WHERE Typeset='bool'");
	$BoolOptions = array_column($DB->to_array(), 'Value', 'Name');
	foreach($BoolOptions as $Name => $BoolOption) {
		$SiteOptions[$Name] = ($BoolOption === 'true');
	}
        $Cache->cache_value('global_site_options', $SiteOptions);
    }

    // Cancel timed sitewide freeleech event
    if(($SiteOptions['SitewideFreeleechTime'] != '0000-00-00 00:00:00') &&
       ($SiteOptions['SitewideFreeleechTime'] <= sqltime()) &&
       ($SiteOptions['SitewideFreeleechMode'] == 'timed')) {
        $DB->query("UPDATE site_options SET Value='off' WHERE Name='SitewideFreeleechMode'");
        $Cache->delete_value('global_site_options');
    }
}

function get_avatar_css()
{
    global $DB, $Cache, $SiteOptions;

    $css .= " max-width: $SiteOptions[AvatarWidth]px;";
    $css .= " max-height: $SiteOptions[AvatarHeight]px;";
    return $css;
}

function get_permissions($PermissionID)
{
    global $DB, $Cache;
    $Permission = $Cache->get_value('perm_' . $PermissionID);
    if (empty($Permission)) {
        $DB->query("SELECT p.Level AS Class,
            p.Values as Permissions,
            p.MaxSigLength,
            p.MaxAvatarWidth,
            p.MaxAvatarHeight,
            p.DisplayStaff
            FROM permissions AS p WHERE ID='$PermissionID'");

        if ($DB->record_count()>0) {
            $Permission = $DB->next_record(MYSQLI_ASSOC, array('Permissions'));
            $Permission['Permissions'] = unserialize($Permission['Permissions']);
            $Cache->cache_value('perm_' . $PermissionID, $Permission, 2592000);
        } else {
            $Permission = array('Permissions' => array());
        }
    }

    return $Permission;
}

function get_permissions_for_user($UserID, $CustomPermissions = false, $UserPermission = false)
{
    global $DB, $Cache;

    $UserInfo = user_info($UserID);

    if ($CustomPermissions === false) {
            // if this value is in the cache get it from there
            $HeavyInfo = $Cache->get_value('user_info_heavy_' . $UserID);
            if ($HeavyInfo!==false && isset($HeavyInfo['CustomPermissions'])) {
                $CustomPermissions = $HeavyInfo['CustomPermissions'];
            } elseif ($UserID>0) { // if not just grab it
                $DB->query("SELECT um.CustomPermissions FROM users_main AS um WHERE um.ID = '$UserID'");
                list($CustomPermissions) = $DB->next_record(MYSQLI_NUM, false);
            }
    }

    if (!empty($CustomPermissions) && !is_array($CustomPermissions)) {
        $CustomPermissions = unserialize($CustomPermissions);
    }

    if ($UserPermission === false) {
            $Permissions = get_permissions($UserInfo['PermissionID']);
      } else {
            $Permissions = $UserPermission;
      }

    if ($UserInfo['GroupPermissionID'] > 0) {
        $GroupPerms = get_permissions($UserInfo['GroupPermissionID']);
    } else {
        $GroupPerms = array('Permissions' => array());
    }

    if (!empty($CustomPermissions)) {
        $CustomPerms = $CustomPermissions;
    } else {
        $CustomPerms = array();
    }

    $MaxCollages = $Permissions['Permissions']['MaxCollages'] + $GroupPerms['Permissions']['MaxCollages'] + $CustomPerms['MaxCollages'];

    //Combine the permissions
    return array_merge($Permissions['Permissions'], $GroupPerms['Permissions'], $CustomPerms, array('MaxCollages' => $MaxCollages));

}

// Get whether this user can use adv tags (pass optional params to reduce lookups)
function get_permissions_advtags($UserID, $CustomPermissions = false, $UserPermission = false)
{
    $PermissionsValues = get_permissions_for_user($UserID, $CustomPermissions, $UserPermission);

      return isset($PermissionsValues['site_advanced_tags']) &&  $PermissionsValues['site_advanced_tags'];
}

function get_user_badges($UserID)
{
    global $DB, $Cache;
    $UserID = (int) $UserID;
    // Killing the limit, since NBL is set up differently and users cannot control what badges display
    $UserBadges = $Cache->get_value('user_badges_'.$UserID.$extra);
    if (!is_array($UserBadges)) {
        $DB->query("
                    (SELECT
                        ub.ID, ub.BadgeID,  ub.Description,  b.Title, b.Image,
                        IF(ba.ID IS NULL,FALSE,TRUE) AS Auto, b.Type, b.Display, b.Sort
                    FROM users_badges AS ub
                    JOIN badges AS b ON b.ID = ub.BadgeID
                    LEFT JOIN badges_auto AS ba ON b.ID=ba.BadgeID
                    WHERE ub.UserID = $UserID
                    ORDER BY b.Sort)
                ORDER BY Display, Sort
                ");
        $UserBadges = $DB->to_array();
        $Cache->cache_value('user_badges_'.$UserID.$extra, $UserBadges);
    }

    return $UserBadges;
}

function get_user_shop_badges_ids($UserID)
{
    global $DB, $Cache;
    $UserID = (int) $UserID;
    $UserBadges = $Cache->get_value('user_badges_ids_'.$UserID);
    if (!is_array($UserBadges)) {
        $DB->query("SELECT BadgeID
                    FROM users_badges AS ub
                    LEFT JOIN badges AS b ON b.ID = ub.BadgeID
                    WHERE b.Type='Shop' AND UserID = $UserID");
        $UserBadges = $DB->collect('BadgeID');
        $Cache->cache_value('user_badges_ids_'.$UserID, $UserBadges);
    }

    return $UserBadges;
}

function print_badges_array($UserBadges, $UserLinkID = false)
{
    $LastRow='';
    $html='';
    foreach ($UserBadges as $Badge) {
        list($ID,$BadgeID, $Tooltip, $Name, $Image, $Auto, $Type, $Row ) = $Badge;
        if($LastRow=='') $LastRow = $Row;
        if ($LastRow!=$Row && $html != '') {
            $html .= "<br/>";
            $LastRow=$Row;
        }
        if($UserLinkID && is_number($UserLinkID))
            $html .= '<div class="badge"><a href="user.php?id='.$UserLinkID.'#userbadges"><img src="'.STATIC_SERVER.'common/badges/'.$Image.'" title="The '.$Name.'. '.$Tooltip.'" alt="'.$Name.'" /></a></div>';
        else
            $html .= '<div class="badge"><img src="'.STATIC_SERVER.'common/badges/'.$Image.'" title="The '.$Name.'. '.$Tooltip.'" alt="'.$Name.'" /></div>';
    }
    echo $html;
}

function get_latest_forum_topics($PermissionID, $ExcludeGames = true)
{
    global $Classes, $DB, $Cache, $ExcludeForums;
    if ($ExcludeGames && is_array($ExcludeForums)) { // check array from config exists
        $ANDWHERE = " AND ft.ForumID NOT IN (" . implode(",", $ExcludeForums) .") ";
        $cachekey = "nogames_$PermissionID";
    } else {
        $cachekey = "$PermissionID";
    }
    $LatestTopics = $Cache->get_value('latest_topics_'.$cachekey);
    if ($LatestTopics === false) {
        $Level = $Classes[$PermissionID]['Level'];

        $DB->query("SELECT ft.ID AS ThreadID, ft.LastPostID as LastPostID, fp.ID AS PostID, ft.Title, um.Username, fp.AddedTime
                      FROM forums_topics AS ft
                      JOIN forums AS f ON f.ID=ft.ForumID
                      JOIN forums_posts AS fp ON fp.ID=ft.LastPostID
                      JOIN users_main AS um ON um.ID=fp.AuthorID
                     WHERE f.MinClassRead<='$Level' $ANDWHERE
                  GROUP BY ThreadID
                  ORDER BY AddedTime DESC
                     LIMIT 6");

        $LatestTopics = $DB->to_array();
        $Cache->cache_value('latest_topics_'.$cachekey, $LatestTopics);
    }

    return $LatestTopics;
}

function print_latest_forum_topics()
{
    global $LoggedUser;
    if (empty($LoggedUser['DisableLatestTopics'])) {
        $LatestTopics = get_latest_forum_topics($LoggedUser['PermissionID'], !$LoggedUser['ShowGames'] );

        echo '<div class="head latest_topics">Latest forum topics</div>';
        echo '<div class="box pad latest_topics">';
        foreach ($LatestTopics as $Key=>$Value) {
            echo '<a href="forums.php?action=viewthread&threadid='.$Value['ThreadID']."&postid=".$Value['PostID']."#post".$Value['PostID'].'"><strong>'.$Value['Title']."</strong></a> by ".$Value['Username']." (".time_diff($Value['AddedTime'], 1,true,false,0).")&nbsp;&nbsp;";
        }
        echo "</div>";
    }
}

function get_premiers($force_reload)
{
    global $Cache, $DB, $SiteOptions;
    if (($Premiers = $Cache->get_value('premiers_'.date('d.m.y'))) === false || $force_reload) {
        $Premiers = array();

        $DB->query("SELECT s.id, s.ShowTitle as name, s.ShowInfo as summary, s.premiered, s.weight, tb.BannerLink FROM shows AS s
                    LEFT JOIN torrents_banners AS tb ON tb.TVMazeID = s.ID
                    WHERE s.premiered > NOW() - INTERVAL 1 MONTH AND s.premiered <= NOW()
                    GROUP BY s.ID
                    ORDER BY s.premiered DESC");
        $maze = $DB->to_array();

        foreach($maze as $Show) {
            if(intval($Show['weight']) < $SiteOptions['TVMazeWeight']) continue;

            $Banner = $Show["BannerLink"]; 

            if($Banner) { // no banner no display
                $Synopsis = str_replace('"', '', $Show["summary"]); // Striping " to correct Synopsis
                $Synopsis = strip_tags($Synopsis); // Striping tags
                $Synopsis = cutAfter($Synopsis,222,' ...'); // Cut synopsis
                $Premiers[] = ['TVMazeID' => $Show["id"],
                               'Name'     => $Show["name"],
                               'Banner'   => $Banner,
                               'Synopsis' => $Synopsis];
            }
        }
        $Cache->cache_value('premiers_'.date('d.m.y'), $Premiers, 3600); // cache for 1 hour
    }
    return $Premiers;
}

function torrents_link($Title)
{
   $Title = str_replace("'", "`", $Title); // fix '
	if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $Title)) {
      return	"/torrents.php?title=". urlencode(htmlspecialchars_decode($Title))."&action=advanced";      
	}
	elseif(preg_match("/\\d|\./", $Title))
	   return	"/torrents.php?searchtext=". urlencode(htmlspecialchars_decode($Title));
	else
	   return	"/torrents.php?title=". urlencode(htmlspecialchars_decode($Title))."&action=advanced";
}

function print_premiers($force_reload)
{
    global $LoggedUser;
    if (empty($LoggedUser['DisableLatestShows'])) {
        $Premiers = get_premiers($force_reload);
        $i = 0;
        echo '<div class="head" title="'.date('d.m.y',strtotime('today - 30 days')).' - '.date('d.m.y').'">New Shows Premiered in the Past 30 Days</div>';
        echo '<div class="box pad center">';
        echo '<table style="width:90%; border:0; margin-left: auto; margin-right: auto;">';
        foreach ($Premiers as $Key=>$Value) {
        	   if($i%4==0) echo '</tr><tr>';
        	   echo '<td style="padding:2px; border:0;">';
        	   if($Value['TVMazeID']) 
               echo '<a href=/torrents.php?action=show&showid='.$Value['TVMazeID'].'>';
            else
               echo '<a href='.torrents_link($Value['Name']).'>'; 
            echo '<img data-tooltip-width="400" class="banner_col" src="'.$Value['Banner'].'" title="'.$Value['Synopsis'].'" />';
            echo '</a>';
            echo '</td>';
            $i++;
        }
        echo '</table>';
        echo '</div>';
    }
}


/* --------------------------------
* Returns a regex string in the form '/email.com|otheremail.com|email2.com/i'
  for fast email blacklist checking
  ----------------------------------- */
function get_emailblacklist_regex()
{
    global $DB, $Cache;
    $pattern = $Cache->get_value('emailblacklist_regex');
    if ($pattern===false) {
        $DB->query("SELECT Email FROM email_blacklist");
        if ($DB->record_count()>0) {
            $pattern = '@';
            $div = '';
            while (list($host)=$DB->next_record()) {
                $pattern .= $div . preg_quote($host, '@');
                $div = '|';
            }
            $pattern .= '@i';
            $Cache->cache_value('emailblacklist_regex', $pattern);
        } else {
            $pattern = '@nohost.non@i';
        }
    }

    return $pattern;
}
/* --------------------------------
* Returns a regex string in the form '/imagehost.com|otherhost.com|imgbox.com/i'
  for fast whitelist checking
  ----------------------------------- */
function get_whitelist_regex()
{
    global $DB, $Cache;
    $pattern = $Cache->get_value('imagehost_regex');
    if ($pattern===false) {
        $DB->query("SELECT Imagehost FROM imagehost_whitelist");
        if ($DB->record_count()>0) {
            $pattern = '@';
            $div = '';
            while (list($host)=$DB->next_record()) {
                $pattern .= $div . preg_quote($host, '@');
                $div = '|';
            }
            $pattern .= '@i';
            $Cache->cache_value('imagehost_regex', $pattern);
        } else {
            $pattern = '@nohost.com@i';
        }
    }

    return $pattern;
}

// TODO copied in sections/feeds/entry.php
function getValidUrlRegex($Extension = '', $Inline = false)
{
    $Regex = '/^';
    $Regex .= '(https?|ftps?|irc):\/\/'; // protocol
    $Regex .= '(\w+(:\w+)?@)?'; // user:pass@
    $Regex .= '(';
    $Regex .= '(([0-9]{1,3}\.){3}[0-9]{1,3})|'; // IP or...
    $Regex .= '(([a-z0-9\-\_]+\.)+\w{2,6})'; // sub.sub.sub.host.com
    $Regex .= ')';
    $Regex .= '(:[0-9]{1,5})?'; // port
    $Regex .= '\/?'; // slash?
    $Regex .= '(\/?[0-9a-z\-_.,&=@~%\/:;()+|!#]+)*'; // /file
    if (!empty($Extension)) {
        $Regex.=$Extension;
    }

    // query string
    if ($Inline) {
        $Regex .= '(\?([0-9a-z\-_.,%\/\@~&=:;()+*\^$!#|]|\[\d*\])*)?';
    } else {
        $Regex .= '(\?[0-9a-z\-_.,%\/\@[\]~&=:;()+*\^$!#|]*)?';
    }

    $Regex .= '(#[a-z0-9\-_.,%\/\@[\]~&=:;()+*\^$!]*)?'; // #anchor
    $Regex .= '$/i';

    return $Regex;
}

/**
 * Validates the passed imageurl with the passed parameters, and against an image validating regex:
 * '/^(https?):\/\/([a-z0-9\-\_]+\.)+([a-z]{1,5}[^\.])(\/[^<>]+)*$/i'
 *
 * @param string $Imageurl The url to validate
 * @param int $MinLength The min string length
 * @param int $MaxLength The max string length
 * @param string $WhitelistRegex a regex containing valid imagehosts
 * @param boolean $ProhibitGIF a flag as to whether GIF type images should be prohibited
 * @return mixed Returns TRUE if it validates and a user readable error message if it fails
 */
function validate_imageurl($Imageurl, $MinLength, $MaxLength, $WhitelistRegex, $ProhibitGIF)
{
       $ErrorMessage = "$Imageurl is not a valid url.";

       $URLInfo = parse_url($Imageurl);
       if (!$URLInfo) {
           return "$ErrorMessage (Bad URL)";
       }
       $ImageHost = $URLInfo['scheme'].'://'.$URLInfo['host'];

       if (strlen($Imageurl)>$MaxLength) {
           return "$ErrorMessage (must be < $MaxLength characters)";
       } elseif (strlen($Imageurl)<$MinLength) {
           return "$ErrorMessage (must be > $MinLength characters)";
       } elseif (!preg_match('/^' . ($ProhibitGIF ? IMAGE_REGEX_NO_GIF : IMAGE_REGEX) . '/is', $Imageurl)) {
           return "$ErrorMessage (Not an allowed image format)";
       } elseif (!preg_match($WhitelistRegex, $ImageHost.'/')) {
           return "$Imageurl is not on an approved imagehost ($ImageHost).";
       } else { // hooray it validated

           return TRUE;
       }
}

function validate_email($email)
{
       if (preg_match(get_emailblacklist_regex(), $email)) {
           return "$email is on a blacklisted email host.";
       } else { // hooray it validated

           return TRUE;
       }
}

// This is used to determine whether the '[Edit]' link should be shown
function can_edit_comment($AuthorID, $EditedUserID, $AddedTime, $EditedTime)
{
    global $LoggedUser;

    if (check_perms('site_moderate_forums')) {
        return true; // moderators can edit anything
    }

    if ($AuthorID != $LoggedUser['ID'] || ($EditedUserID && $EditedUserID != $LoggedUser['ID'])) {
        return false;
    }

    return ( time_ago($AddedTime)<USER_EDIT_POST_TIME || time_ago($EditedTime)<USER_EDIT_POST_TIME ) || check_perms ('site_edit_own_posts');
}

// This function is used to check if the user can submit changes to a comment.
// Prints error if not permitted.
function validate_edit_comment($AuthorID, $EditedUserID, $AddedTime, $EditedTime)
{
    global $LoggedUser;

    if (check_perms('site_moderate_forums')) {
        return; // moderators can edit anything
    }

    if ($AuthorID != $LoggedUser['ID']) {
        error(403, true);
    }

    if ($EditedUserID && $EditedUserID != $LoggedUser['ID']) {
        error("You are not allowed to edit a post that has been edited by moderators.", true);
    }

    if (!check_perms ('site_edit_own_posts')
            && time_ago($AddedTime)>(USER_EDIT_POST_TIME+600)  && time_ago($EditedTime)>(USER_EDIT_POST_TIME+300)) { // give them an extra 15 mins in the backend
        error("Sorry - you only have ". date('i\m s\s', USER_EDIT_POST_TIME). "  to edit your post before it is automatically locked." ,true);
    }
}

// for getting an article to display on some other page
function get_article($TopicID)
{
    global $DB;
    $TopicID = db_string($TopicID);
    $DB->query("SELECT Body FROM articles WHERE TopicID='$TopicID'");
    list($Body) = $DB->next_record();

    return $Body;
}

function flood_check($Table = 'forums_posts')
{
    global $DB, $LoggedUser;
    if (check_perms('site_ignore_floodcheck')) return true;
    if ( !in_array($Table, array('forums_posts','requests_comments','torrents_comments','collages_comments','sm_results'))) error(0);
    if ($Table=='collages_comments' || $Table=='sm_results') {
        $DB->query( "SELECT ( (UNIX_TIMESTAMP( Time)+'".USER_FLOOD_POST_TIME."')-UNIX_TIMESTAMP(  UTC_TIMESTAMP()) )  FROM $Table
                  WHERE UserID = $LoggedUser[ID]
                    AND UNIX_TIMESTAMP( Time)>= ( UNIX_TIMESTAMP(  UTC_TIMESTAMP())-'".USER_FLOOD_POST_TIME."')");
    } else {
        $DB->query( "SELECT ( (UNIX_TIMESTAMP( AddedTime)+'".USER_FLOOD_POST_TIME."')-UNIX_TIMESTAMP(  UTC_TIMESTAMP()) )  FROM $Table
                  WHERE AuthorID = $LoggedUser[ID]
                    AND UNIX_TIMESTAMP( AddedTime)>= ( UNIX_TIMESTAMP(  UTC_TIMESTAMP())-'".USER_FLOOD_POST_TIME."')");
    }
    if ($DB->record_count()==0) return true;
    else {
        list($Secs) = $DB->next_record();
        error("<h3>Flood Control</h3>You must wait <strong>$Secs</strong> seconds before posting again.");
    }
}

function flood_check_slots() {  // gives ajax error
    global $DB, $LoggedUser;
    if (check_perms('site_ignore_floodcheck')) return true;

    $DB->query( "SELECT ( (UNIX_TIMESTAMP( Time)+'5')-UNIX_TIMESTAMP(  UTC_TIMESTAMP()) )  FROM sm_results
                  WHERE UserID = $LoggedUser[ID]
                    AND UNIX_TIMESTAMP( Time)>= ( UNIX_TIMESTAMP(  UTC_TIMESTAMP())-'5')");

    if ($DB->record_count()==0) return true;
    else {
        list($Secs) = $DB->next_record();

        return (int) $Secs;
    }
}

// This function is slow. Don't call it unless somebody's logging in.
function site_ban_ip($IP)
{
    global $DB, $Cache;
    $IPNum = ip2unsigned($IP);
    $IPBans = $Cache->get_value('ip_bans');
    if (!is_array($IPBans)) {
        $DB->query("SELECT ID, FromIP, ToIP FROM ip_bans");
        $IPBans = $DB->to_array(0, MYSQLI_NUM);
        $Cache->cache_value('ip_bans', $IPBans, 0);
    }
    foreach ($IPBans as $Index => $IPBan) {
        list($ID, $FromIP, $ToIP) = $IPBan;
        if ($IPNum >= $FromIP && $IPNum <= $ToIP) {
            return true;
        }
    }

    return false;
}

function ip2unsigned($IP)
{
    return sprintf("%u", ip2long($IP));
}

// Geolocate an IP address. Two functions - a database one, and a dns one.
function geoip($IP)
{
    static $IPs = array();
    if (isset($IPs[$IP])) {
        return $IPs[$IP];
    }
    $Long = ip2unsigned($IP);
    if (!$Long || $Long == 2130706433) { // No need to check cc for 127.0.0.1
        return '??';
    }
    global $DB;
    $DB->query("SELECT EndIP,Code FROM geoip_country WHERE $Long >= StartIP ORDER BY StartIP DESC LIMIT 1");
    if ((!list($EndIP, $Country) = $DB->next_record()) || $EndIP < $Long) {
        $Country = '??';
    }
    $IPs[$IP] = $Country;

    return $Country;
}

function old_geoip($IP)
{
    static $Countries = array();
    if (empty($Countries[$IP])) {
        $Country = 0;
        // Reverse IP, so 127.0.0.1 becomes 1.0.0.127
        $ReverseIP = implode('.', array_reverse(explode('.', $IP)));
        $TestHost = $ReverseIP . '.country.netop.org';
        $Return = dns_get_record($TestHost, DNS_TXT);
        if (!empty($Return)) {
            $Country = $Return[0]['txt'];
        }
        if (!$Country) {
            $Return = gethostbyaddr($IP);
            $Return = explode('.', $Return);
            $Return = array_pop($Return);
            if (strlen($Return) == 2 && !is_number($Return)) {
                $Country = strtoupper($Return);
            } else {
                $Country = '??';
            }
        }
        if ($Country == 'UK') {
            $Country = 'GB';
        }
        $Countries[$IP] = $Country;
    }

    return $Countries[$IP];
}

function gethostbyip($ip)
{
    $testar = explode('.', $ip);
    if (count($testar) != 4) {
        return $ip;
    }
    for ($i = 0; $i < 4; ++$i) {
        if (!is_numeric($testar[$i])) {
            return $ip;
        }
    }

    $host = `host -W 1 $ip`;

    return (($host ? end(explode(' ', $host)) : $ip));
}

function get_host($IP)
{
    static $ID = 0;
    ++$ID;

    return '<span id="host_' . $ID . '">Resolving host ' . $IP . '...<script type="text/javascript">ajax.get(\'tools.php?action=get_host&ip=' . $IP . '\',function (host) {$(\'#host_' . $ID . '\').raw().innerHTML=host;});</script></span>';
}

function lookup_ip($IP)
{
    //TODO: use the $Cache
    global $Cache;
    if (!$IP) return false;

    $LookUp = $Cache->get_value('gethost_'.$IP);
    if ($LookUp===false) {
        $Output = explode(' ', shell_exec('host -W 1 ' . escapeshellarg($IP)));
        if (count($Output) == 1 && empty($Output[0])) {
            //No output at all implies the command failed
           $LookUp = ''; // pass back empty string for error reporting in ajax call
        }
        if (count($Output) != 5) {
            $LookUp = false;
        } else {
            $LookUp = $Output[4];
            $Cache->cache_value('gethost_'.$IP, $LookUp, 0);
        }
    }

    return $LookUp;
}

function display_ip($IP, $cc = '?', $gethost = false, $baniplink=false)
{
    global $DB, $Cache;
    if($gethost) $Line = get_host($IP);
    else $Line = display_str($IP);
    if ($cc=='?' || $cc=='') {
        $cc=='?';
        $country = 'unknown';
    } else {
        $country = $Cache->get_value('country_'.$cc);
        if ($country===false) {
            $DB->query("SELECT country FROM countries WHERE cc='$cc'");
            list($country) = $DB->next_record();
            $Cache->cache_value('country_'.$cc, $country, 0);
        }
        $Line .= ' <span title="'.$country.'">('.$cc.')</span> ' . '<img style="margin-bottom:-3px;" title="'.$country.'" src="static/common/flags/iso16/'. strtolower($cc).'.png" alt="" />';
    }
    $Line .= ' [<a href="user.php?action=search&amp;ip_history=on&amp;ip=' . display_str($IP) . '&amp;matchtype=fuzzy" title="Search IP History">S</a>]';
    $Line .= ' [<a href="user.php?action=search&amp;tracker_ip=' . display_str($IP) . '&amp;matchtype=fuzzy" title="Search Tracker IP\'s">S</a>]';
    if ($baniplink && check_perms('admin_manage_ipbans')) {
        $Line .= ' [<a href="tools.php?action=ip_ban&uip='.display_str($IP).'" title="Ban this users current IP ('.display_str($IP).')">B</a>]';
    }

    return $Line;
}

function logout()
{
    global $SessionID, $LoggedUser, $DB, $Cache;
    setcookie('session', '', time() - 60 * 60 * 24 * 365, '/', '', false);
    setcookie('keeplogged', '', time() - 60 * 60 * 24 * 365, '/', '', false);
    setcookie('session', '', time() - 60 * 60 * 24 * 365, '/', '', false);
    if ($SessionID) {
        $DB->query("DELETE FROM users_sessions WHERE UserID='$LoggedUser[ID]' AND SessionID='" . db_string($SessionID) . "'");

        $Cache->begin_transaction('users_sessions_' . $LoggedUser['ID']);
        $Cache->delete_row($SessionID);
        $Cache->commit_transaction(0);
    }
    $Cache->delete_value('user_info_' . $LoggedUser['ID']);
    $Cache->delete_value('user_stats_' . $LoggedUser['ID']);
    $Cache->delete_value('user_info_heavy_' . $LoggedUser['ID']);

    header('Location: login.php');

    die();
}

function enforce_login()
{
    global $SessionID, $LoggedUser;
    if (!$SessionID || !$LoggedUser) {
        setcookie('redirect', $_SERVER['REQUEST_URI'], time() + 60 * 30, '/', '', false);
        logout();
    }
}

// Make sure $_GET['auth'] is the same as the user's authorization key
// Should be used for any user action that relies solely on GET.
function authorize($Ajax = false)
{
    global $LoggedUser;
    if (empty($_REQUEST['auth']) || $_REQUEST['auth'] != $LoggedUser['AuthKey']) {
        send_irc("PRIVMSG " . LAB_CHAN . " :" . $LoggedUser['Username'] . " just failed authorize on " . $_SERVER['REQUEST_URI'] . " coming from " . $_SERVER['HTTP_REFERER']);
        error('Invalid authorization key. Go back, refresh, and try again.', $Ajax);

        return false;
    }

    return true;
}

// This function is to include the header file on a page.
// $JSIncludes is a comma separated list of js files to be inclides on
// the page, ONLY PUT THE RELATIVE LOCATION WITHOUT .js
// ex: 'somefile,somdire/somefile'
function show_header($PageTitle='', $JSIncludes='', $CSSIncludes='')
{
    global $Document, $Cache, $DB, $LoggedUser, $Mobile, $Classes, $SiteOptions;

    if ($PageTitle != '') {
        $PageTitle.=' :: ';
    }
    $PageTitle .= SITE_NAME;

    if (!is_array($LoggedUser)) {
        require(SERVER_ROOT . '/design/publicheader.php');
    } else {
        require(SERVER_ROOT . '/design/privateheader.php');
    }
}

/* -- show_footer function ------------------------------------------------ */
/* ------------------------------------------------------------------------ */
/* This function is to include the footer file on a page.				 */
/* $Options is an optional array that you can pass information to the	 */
/*  header through as well as setup certain limitations				   */
/*  Here is a list of parameters that work in the $Options array:		 */
/*  ['disclaimer']	= [boolean]		Displays the disclaimer in the footer */
/* 								  Default is false					  */
/* * *********************************************************************** */

function show_footer($Options=array())
{
    global $ScriptStartTime, $LoggedUser, $Cache, $DB, $SessionID, $UserSessions, $Debug, $Time;
    if (!is_array($LoggedUser)) {
        require(SERVER_ROOT . '/design/publicfooter.php');
    } else {
        require(SERVER_ROOT . '/design/privatefooter.php');
    }
}

function cut_string($Str, $Length, $Hard=0, $ShowDots=1)
{
    if (mb_strlen($Str, "UTF-8") > $Length) {
        if ($Hard == 0) {
            // Not hard, cut at closest word
            $CutDesc = mb_substr($Str, 0, $Length, "UTF-8");
            $DescArr = explode(' ', $CutDesc);
            $DescArr = array_slice($DescArr, 0, count($DescArr) - 1);
            $CutDesc = implode($DescArr, ' ');
            if ($ShowDots == 1) {
                $CutDesc.='...';
            }
        } else {
            $CutDesc = mb_substr($Str, 0, $Length, "UTF-8");
            if ($ShowDots == 1) {
                $CutDesc.='...';
            }
        }

        return $CutDesc;
    } else {
        return $Str;
    }
}

/**
 * Highlight all instances of string 'term' in string 'text'
 *
 * @param $hlterm string The string to highlight; the actual html used is: '<span atyle="color: $color">$hlterm</span>'
 *
 * @param $text string, which result's page we want if no page is specified
 *
 * @param $color string Optional, which color to use to highlight the term (can be any valid css color)
 * If this parameter is not specified, defaults to red
 *
 * @return string The text with 'term' highlighted
 */
function highlight_text_color($hlterm, $text, $color = 'red')
{
    return str_replace($hlterm, "<span style=\"color: $color;\">$hlterm</span>", $text);
}

/**
 * Highlight all instances of string 'term' in string 'text'
 *
 * @param $hlterm string The string to highlight; the actual html used is: '<span class="$css">$hlterm</span>'
 *
 * @param $text string, which result's page we want if no page is specified
 *
 * @param $css string Optional, which css class to use to highlight the term
 * If this parameter is not specified, defaults to search_highlight
 *
 * @return string The text with 'term' highlighted
 */
function highlight_text_css($hlterm, $text, $css = 'search_highlight')
{
    return str_replace($hlterm, "<span class=\"$css\">$hlterm</span>", $text);
}

function get_ratio_color($Ratio)
{
    if ($Ratio < 0.1) {
        return 'r00';
    }
    if ($Ratio < 0.2) {
        return 'r01';
    }
    if ($Ratio < 0.3) {
        return 'r02';
    }
    if ($Ratio < 0.4) {
        return 'r03';
    }
    if ($Ratio < 0.5) {
        return 'r04';
    }
    if ($Ratio < 0.6) {
        return 'r05';
    }
    if ($Ratio < 0.7) {
        return 'r06';
    }
    if ($Ratio < 0.8) {
        return 'r07';
    }
    if ($Ratio < 0.9) {
        return 'r08';
    }
    if ($Ratio < 1) {
        return 'r09';
    }
    if ($Ratio < 2) {
        return 'r10';
    }
    if ($Ratio < 5) {
        return 'r20';
    }

    return 'r50';
}

function ratio($Dividend, $Divisor, $Color = true)
{
    if ($Divisor == 0 && $Dividend == 0) {
        return '<span>--</span>';
    } elseif ($Divisor == 0) {
        return '<span class="r99 infinity">∞</span>';
    }
    $Ratio = number_format(max($Dividend / $Divisor - 0.005, 0), 2); //Subtract .005 to floor to 2 decimals
    if ($Color) {
        $Class = get_ratio_color($Ratio);
        if ($Class) {
            $Ratio = '<span class="' . $Class . '">' . $Ratio . '</span>';
        }
    }

    return $Ratio;
}

function get_url($Exclude = false)
{
    if ($Exclude !== false) {
        $QueryItems = array();
        parse_str($_SERVER['QUERY_STRING'], $QueryItems);

        foreach ($QueryItems AS $Key => $Val) {
            if (!in_array(strtolower($Key), $Exclude)) {
                $Query[$Key] = $Val;
            }
        }

        if (empty($Query)) {
            return;
        }

        return display_str(http_build_query($Query));
    } else {
        return display_str($_SERVER['QUERY_STRING']);
    }
}

/**
 * Finds what page we're on and gives it to us, as well as the LIMIT clause for SQL
 * Takes in $_GET['page'] as an additional input
 *
 * @param $PerPage Results to show per page
 *
 * @param $DefaultResult Optional, which result's page we want if no page is specified
 * If this parameter is not specified, we will default to page 1
 *
 * @return array(int,string) What page we are on, and what to use in the LIMIT section of a query
 * i.e. "SELECT [...] LIMIT $Limit;"
 */
function page_limit($PerPage, $DefaultResult = 1, $PageGetVar = 'page')
{
    if (!isset($_GET[$PageGetVar])) {
        $Page = ceil($DefaultResult / $PerPage);
        if ($Page == 0)
            $Page = 1;
        $Limit = $PerPage;
    } else {
        if (!is_number($_GET[$PageGetVar])) {
            error(0);
        }
        $Page = $_GET[$PageGetVar];
        if ($Page == 0) {
            $Page = 1;
        }
        $Limit = $PerPage * $_GET[$PageGetVar] - $PerPage . ', ' . $PerPage;
    }

    return array($Page, $Limit);
}

// For data stored in memcached catalogues (giant arrays), eg. forum threads
function catalogue_limit($Page, $PerPage, $CatalogueSize=500)
{
    $CatalogueID = floor(($PerPage * $Page - $PerPage) / $CatalogueSize);
    ;
    $CatalogueLimit = ($CatalogueID * $CatalogueSize) . ', ' . $CatalogueSize;

    return array($CatalogueID, $CatalogueLimit);
}

function catalogue_select($Catalogue, $Page, $PerPage, $CatalogueSize=500)
{
    return array_slice($Catalogue, (($PerPage * $Page - $PerPage) % $CatalogueSize), $PerPage, true);
}

function get_pages($StartPage, $TotalRecords, $ItemsPerPage, $ShowPages=11, $Anchor='')
{
    global $Document, $Method, $Mobile;
    $Location = $Document . '.php';
    /* -- Get pages ---------------------------------------------------------------//
      This function returns a page list, given certain information about the pages.

      Explanation of arguments:
     * $StartPage: The current record the page you're on starts with.
      eg. if you're on page 2 of a forum thread with 25 posts per page, $StartPage is 25.
      If you're on page 1, $StartPage is 0.
     * $TotalRecords: The total number of records in the result set.
      eg. if you're on a forum thread with 152 posts, $TotalRecords is 152.
     * $ItemsPerPage: Self-explanatory. The number of records shown on each page
      eg. if there are 25 posts per forum page, $ItemsPerPage is 25.
      $ShowPages: The number of page links that are shown.
      eg. If there are 20 pages that exist, but $ShowPages is only 11, only 11 links will be shown.
      //---------------------------------------------------------------------------- */
    $StartPage = ceil($StartPage);
    if ($StartPage == 0) {
        $StartPage = 1;
    }
    $TotalPages = 0;
    if ($TotalRecords > 0) {
        if ($StartPage > ceil($TotalRecords / $ItemsPerPage)) {
            $StartPage = ceil($TotalRecords / $ItemsPerPage);
        }

        $ShowPages--;
        $TotalPages = ceil($TotalRecords / $ItemsPerPage);

        if ($TotalPages > $ShowPages) {
            $StartPosition = $StartPage - round($ShowPages / 2);

            if ($StartPosition <= 0) {
                $StartPosition = 1;
            } else {
                if ($StartPosition >= ($TotalPages - $ShowPages)) {
                    $StartPosition = $TotalPages - $ShowPages;
                }
            }

            $StopPage = $ShowPages + $StartPosition;
        } else {
            $StopPage = $TotalPages;
            $StartPosition = 1;
        }

        if ($StartPosition < 1) {
            $StartPosition = 1;
        }

        $QueryString = get_url(array('page', 'post'));
        if ($QueryString != '') {
            $QueryString = '&amp;' . $QueryString;
        }

        $Pages = '';

        if ($StartPage > 1) {
            $Pages.='<a href="' . $Location . '?page=1' . $QueryString . $Anchor . '" class="pager pager_first">&lt;&lt; First</a> ';
            if ($StartPage > 2)
                $Pages.='<a href="' . $Location . '?page=' . ($StartPage - 1) . $QueryString . $Anchor . '" class="pager pager_prev">&lt; Prev</a>';
            $Pages.= ' | ';
        }
        //End change

        if (!$Mobile) {
            for ($i = $StartPosition; $i <= $StopPage; $i++) {
                if ($i != $StartPage) {
                    $Pages.='<a href="' . $Location . '?page=' . $i . $QueryString . $Anchor . '" class="pager pager_page">';
                } else {
                    $Pages.='<span class="pager pager_on">';
                }
                //$Pages.="<strong>"; fuck using strong... added css classes so can be done the right way
                if ($i * $ItemsPerPage > $TotalRecords) {
                    $Pages.=((($i - 1) * $ItemsPerPage) + 1) . '-' . ($TotalRecords);
                } else {
                    $Pages.=((($i - 1) * $ItemsPerPage) + 1) . '-' . ($i * $ItemsPerPage);
                }

                //$Pages.="</strong>";
                if ($i != $StartPage) {
                    $Pages.='</a>';
                } else {
                    $Pages.='</span>';
                }
                if ($i < $StopPage) {
                    $Pages.=" | ";
                }
            }
        } else {
            $Pages .= $StartPage;
        }

        if ($StartPage < $TotalPages) $Pages.=' | ';
        if ($StartPage < $TotalPages-1) {
            $Pages.='<a href="' . $Location . '?page=' . ($StartPage + 1) . $QueryString . $Anchor . '" class="pager pager_next">Next &gt;</a> ';
        }
        if ($StartPage < $TotalPages) {
                $Pages.='<a href="' . $Location . '?page=' . $TotalPages . $QueryString . $Anchor . '" class="pager pager_last"> Last &gt;&gt;</a>';
        }
    }

    if ($TotalPages > 1) {
        return $Pages;
    }
}

function send_email($To, $Subject, $Body, $From='noreply', $ContentType='text/plain')
{
    $Headers = 'MIME-Version: 1.0' . PHP_EOL;
    $Headers.='Content-type: ' . $ContentType . '; charset=iso-8859-1' . PHP_EOL;
    $Headers.='From: ' . SITE_NAME . ' <' . $From . '@' . NONSSL_SITE_URL . '>' . PHP_EOL;
    $Headers.='Reply-To: ' . $From . '@' . NONSSL_SITE_URL . PHP_EOL;
    $Headers.='X-Mailer: Project Gazelle' . PHP_EOL;
    $Headers.='Message-Id: <' . make_secret() . '@' . NONSSL_SITE_URL . '>' . PHP_EOL;
    $Headers.='X-Priority: 3' . PHP_EOL;
    mail($To, $Subject, $Body, $Headers, "-f " . $From . "@" . NONSSL_SITE_URL);
}

function get_size($Size, $Levels = 2)
{
    $Units = array(' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
    $Size = (double) $Size;
    for ($Steps = 0; abs($Size) >= 1024; $Size /= 1024, $Steps++) {

    }
    if (func_num_args() == 1 && $Steps >= 4) {
        $Levels++;
    }

    return number_format($Size, $Levels) . $Units[$Steps];
}

function get_bytes($Size)
{
    list($Value, $Unit) = sscanf($Size, "%f%s");
    $Unit = ltrim($Unit);
    if (empty($Unit)) {
        return $Value ? round($Value) : 0;
    }
    switch (strtolower($Unit[0])) {
        case 'k': return round($Value * 1024);
        case 'm': return round($Value * 1048576);
        case 'g': return round($Value * 1073741824);
        case 't': return round($Value * 1099511627776);
        default: return 0;
    }
}

function human_format($Number)
{
    $Steps = 0;
    while ($Number >= 1000) {
        $Steps++;
        $Number = $Number / 1000;
    }
    switch ($Steps) {
        case 0: return round($Number);
            break;
        case 1: return round($Number, 2) . 'k';
            break;
        case 2: return round($Number, 2) . 'M';
            break;
        case 3: return round($Number, 2) . 'G';
            break;
        case 4: return round($Number, 2) . 'T';
            break;
        case 5: return round($Number, 2) . 'P';
            break;
        default:
            return round($Number, 2) . 'E + ' . $Steps * 3;
    }
}

function is_number($Str)
{
    $Return = true;
    if ($Str < 0) {
        $Return = false;
    }
    // We're converting input to a int, then string and comparing to original
    $Return = ($Str == strval(intval($Str)) ? true : false);

    return $Return;
}

function file_string($EscapeStr)
{
    return str_replace(array('"', '*', '/', ':', '<', '>', '?', '\\', '|'), '', $EscapeStr);
}

// This is preferable to htmlspecialchars because it doesn't screw up upon a double escape
function display_str($Str)
{
    if ($Str === NULL || $Str === FALSE || is_array($Str)) {
        return '';
    }
    if ($Str != '' && !is_number($Str)) {
        $Str = make_utf8($Str);
        $Str = mb_convert_encoding($Str, "HTML-ENTITIES", "UTF-8");
        $Str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m", "&amp;", $Str);

        $Replace = array(
            "'", '"', "<", ">",
            '&#128;', '&#130;', '&#131;', '&#132;', '&#133;', '&#134;', '&#135;', '&#136;', '&#137;', '&#138;', '&#139;', '&#140;', '&#142;', '&#145;', '&#146;', '&#147;', '&#148;', '&#149;', '&#150;', '&#151;', '&#152;', '&#153;', '&#154;', '&#155;', '&#156;', '&#158;', '&#159;'
        );

        $With = array(
            '&#39;', '&quot;', '&lt;', '&gt;',
            '&#8364;', '&#8218;', '&#402;', '&#8222;', '&#8230;', '&#8224;', '&#8225;', '&#710;', '&#8240;', '&#352;', '&#8249;', '&#338;', '&#381;', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8226;', '&#8211;', '&#8212;', '&#732;', '&#8482;', '&#353;', '&#8250;', '&#339;', '&#382;', '&#376;'
        );

        $Str = str_replace($Replace, $With, $Str);
    }

    return $Str;
}

// Use sparingly
function undisplay_str($Str)
{
    return mb_convert_encoding($Str, 'UTF-8', 'HTML-ENTITIES');
}

function make_utf8($Str)
{
    if ($Str != "") {
        if (is_utf8($Str)) {
            $Encoding = "UTF-8";
        }
        if (empty($Encoding)) {
            $Encoding = mb_detect_encoding($Str, 'UTF-8, ISO-8859-1');
        }
        if (empty($Encoding)) {
            $Encoding = "ISO-8859-1";
        }
        if ($Encoding == "UTF-8") {
            return $Str;
        } else {
            return @mb_convert_encoding($Str, "UTF-8", $Encoding);
        }
    }
}

function is_utf8($Str)
{
    return preg_match('%^(?:
        [\x09\x0A\x0D\x20-\x7E]			 // ASCII
        | [\xC2-\xDF][\x80-\xBF]			// non-overlong 2-byte
        | \xE0[\xA0-\xBF][\x80-\xBF]		// excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
        | \xED[\x80-\x9F][\x80-\xBF]		// excluding surrogates
        | \xF0[\x90-\xBF][\x80-\xBF]{2}	 // planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}		 // planes 4-15
        | \xF4[\x80-\x8F][\x80-\xBF]{2}	 // plane 16
        )*$%xs', $Str
    );
}

function str_plural($Str, $Num)
{
    if ($Num==1) return "$Num $Str";
    else return "$Num {$Str}s";
}

// Escape an entire array for output
// $Escape is either true, false, or a list of array keys to not escape
function display_array($Array, $Escape = array())
{
    foreach ($Array as $Key => $Val) {
        if ((!is_array($Escape) && $Escape == true) || !in_array($Key, $Escape)) {
            $Array[$Key] = display_str($Val);
        }
    }

    return $Array;
}

// Removes any inconsistencies in the list of tags before they are split into an array.
function cleanup_tags($s)
{
    return preg_replace(array('/[^A-Za-z0-9.\-\&é]/i', '/^\s*/s', '/\s*$/s', '/\s+/s'), array(" ", "", "", " ", ""), $s);
}

// Gets a tag ready for database input and display
function sanitize_tag($str)
{
    $str = strtolower(urldecode(trim($str)));
    $str = str_replace('É', 'e', $str);
    $str = preg_replace('/[^a-z0-9.\-&é]/', '', $str);
    //$str = htmlspecialchars($str);
    $str = db_string($str);

    return $str;
}

function check_tag_input($str)
{
    return preg_match('/[^a-z0-9.\-\&éÉ]/', $str)==0;
}

function get_tag_synonym($Tag, $Sanitise = true)
{
        global $Cache, $DB;

        if ($Sanitise) $Tag = sanitize_tag($Tag);
        $DB->query("SELECT t.Name
                    FROM tag_synomyns AS ts JOIN tags as t ON t.ID = ts.TagID
                    WHERE Synomyn LIKE '".db_string($Tag)."'");
        if ($DB->record_count() > 0) { // should only ever be one but...
            list($TagName) = $DB->next_record();

            return $TagName;
        } else {
            return $Tag;
        }
}


/**
 * Return whether $Tag is a valid tag - more than 2** char long and not a stupid word
 * (** unless is 'hd','dp','bj','ts','sd','69','mf','3d','hj','bi')
 *
 * @param string $Tag The prospective tag to be evaluated
 * @return Boolean representing whether the tag is valid format (not banned)
 */
function is_valid_tag($Tag)
{
    global $SiteOptions, $DB, $Cache;

    static $Good2charTags;
    $len = strlen($Tag);
    if ( $len < $SiteOptions['MinTagLength'] || $len > $SiteOptions['MaxTagLength']){
        if(!$GoodTags = $Cache->get_value('good_tags')) {
            $DB->query("SELECT Name FROM tags_exceptions WHERE ExceptionType='good'");
            $GoodTags = array_column($DB->to_array(FALSE, MYSQLI_ASSOC), 'Name');
            $Cache->cache_value('good_tags', $GoodTags);
        }
        if ( !in_array($Tag, $GoodTags) ) return false;
    }

    if(!$BadTags = $Cache->get_value('bad_tags')) {
        $DB->query("SELECT Name FROM tags_exceptions WHERE ExceptionType='bad'");
        $BadTags = array_column($DB->to_array(FALSE, MYSQLI_ASSOC), 'Name');
        $Cache->cache_value('bad_tags', $BadTags);
    }
    if ( !in_array($Tag, $BadTags) ) return true;
}




// Generate a random string
function make_secret($Length = 32)
{
    $NumBytes = (int) round($Length / 2);
    $Secret = bin2hex(openssl_random_pseudo_bytes($NumBytes));
    return substr($Secret, 0, $Length);
}

// Password hashes, feel free to make your own algorithm here
function make_hash($Str, $Secret)
{
    // We will be using tbdevs way for passwords instead of gazelles
    // we are also using the salt field from tbdev that contains a shorter
    // salt than what gazelle generates, but new accounts will use gazelles
    // generated salt.
    return md5(md5($Secret) . md5($Str));
}

// To allow users to upgrade from XBTIT (gazelle)
function make_old_hash($Str)
{
    return substr(sha1(md5($Str)), 30, 10).substr(sha1(md5($Str)), 0, 10);
}

function is_anon($IsAnon)
{
    if(check_perms('site_force_anon_uploaders')) return true;
    else if(check_perms('users_view_anon_uploaders')) return false;
    else return $IsAnon;
}

function anon_username_ifmatch($Username, $UsernameCheck, $IsAnon = false)
{
    return anon_username($Username, $IsAnon && $Username===$UsernameCheck);
}

function anon_username($Username, $IsAnon = false, $Override = true)
{
    // if not anon then just return username
    if (!$IsAnon && !check_perms('site_force_anon_uploaders')) return $Username;
    // if anon ...
    if (check_perms('users_view_anon_uploaders') && $Override) {
        return "anon [$Username]";
    } else {
        return 'anon ';
    }
}

function torrent_username($UserID, $Username, $IsAnon = false)
{
    // if not anon then just return username
    if (!$IsAnon && !check_perms('site_force_anon_uploaders')) return format_username($UserID, $Username);
    // if anon ...
    if (check_perms('users_view_anon_uploaders')) {
        return '<span class="anon_name" style="float: none;"><a href="user.php?id=' . $UserID . '" title="' . $Username . '">anon</a></span>';
    } elseif (!$IsAnon) {
        return '<span class="anon_name" style="float: none;" title="anonymous upload: your userclass is too low to see uploader info">anon</span>';
    } else {
        return '<span class="anon_name" style="float: none;" title="anonymous upload: this uploader has chosen to hide their username">anon</span>';
    }
}

/*
  Returns a username string for display
  $Class and $Title can be omitted for an abbreviated version
  $IsDonor, $IsWarned and $IsEnabled can be omitted for a *very* abbreviated version
 */
function format_username($UserID, $Username, $IsDonor = false, $IsWarned = '0000-00-00 00:00:00',
                            $Enabled = 1, $Class = false, $Title = false, $DrawInBox = false, $GroupPerm = false, $DropDown=false, $Colorname=false) {
    global $DB, $Cache, $LoggedUser, $Classes;
    if ($UserID == 0) {
        return 'System';
    } elseif ($Username == '') {
        $Username = getUserName($UserID);
    }
    if ($Username == '') {
        return "Unknown [$UserID]";
    }
    if ($Colorname) $str = '<a href="user.php?id=' . $UserID . '">' . '<span style="color:#'. $Classes[$Class]['Color'] . '">' .$Username . '</span></a>';
    else $str = '<a href="user.php?id=' . $UserID . '">' . $Username . '</a>';
    if ($DropDown && $LoggedUser['ID']!==$UserID) {
        $ddlist = '<li><a href="user.php?id='.$UserID.'" title="View '.$Username.'\'s profile">View profile</a></li>';
        if (check_perms('users_mod')) {
            $ddlist .= '<li><a href="staffpm.php?action=compose&amp;toid='.$UserID.'" title="Start a Staff Conversation with '.$Username.'">Staff Message</a></li>';
        }
        $ddlist .= '<li><a href="inbox.php?action=compose&amp;to='.$UserID.'" title="Send a Private Message to '.$Username.'">Send PM</a></li>';

        $Friends = $Cache->get_value('user_friends_'.$LoggedUser['ID']);
        if ($Friends===false) {
                $DB->query("SELECT FriendID, Type FROM friends WHERE UserID='$LoggedUser[ID]'");
                $Friends = $DB->to_array('FriendID');
                $Cache->cache_value('user_friends_'.$LoggedUser['ID'], $Friends);
        }
        $FType = isset($Friends[$UserID]) ? $Friends[$UserID]['Type'] : false;
        if (!$FType || $FType != 'friends') {
                $ddlist .= '<li><a href="friends.php?action=add&amp;friendid='.$UserID.'&amp;auth='.$LoggedUser['AuthKey'].'" title="Add this user to your friends list">Add to friends</a></li>';
        } elseif ($FType == 'friends') {
                $ddlist .= '<li><a href="friends.php?action=Defriend&amp;friendid='.$UserID.'&amp;auth='.$LoggedUser['AuthKey'].'" title="Remove this user from your friends list">Remove friend</a></li>';
        }
        if (!$FType || $FType != 'blocked') {
                $ddlist .= '<li><a href="friends.php?action=add&amp;friendid='.$UserID.'&amp;type=blocked&amp;auth='.$LoggedUser['AuthKey'].'" title="Add this user to your blocked list (blocks from sending PMs to you)">Block User</a></li>';
        } elseif ($FType == 'blocked') {
                $ddlist .= '<li><a href="friends.php?action=Unblock&amp;friendid='.$UserID.'&amp;type=blocked&amp;auth='.$LoggedUser['AuthKey'].'" title="Remove this user from your blocked list">Remove block</a></li>';
        }

        $str = "<div id=\"user_dropdown\">$str<ul>$ddlist</ul></div>";
    }
    $str.=($IsDonor) ? '<a href="donate.php"><img src="' . STATIC_SERVER . 'common/symbols/donor.svg" alt="Donor" title="Donor" /></a>' : '';

    $str.=($IsWarned != '0000-00-00 00:00:00' && $IsWarned !== false) ? '<img src="' . STATIC_SERVER . 'common/symbols/warned.svg" alt="Warned" title="Warned" />' : '';

    if ($Enabled != '1' || $Enabled != true) {
        if ($Enabled == '0')
            $str.= '<img src="' . STATIC_SERVER . 'common/symbols/unconfirmed.svg" alt="Unconfirmed" title="This user has not confirmed their membership" />' ;
        else
            $str.= '<img src="' . STATIC_SERVER . 'common/symbols/disabled.svg" alt="Banned" title="Be good, and you won\'t end up like this user" />' ;
    }

    if($GroupPerm) $str.= make_groupperm_string($GroupPerm, TRUE) ;  // ' (' . make_groupperm_string($GroupPerm, TRUE) . ')' ;
    if($Class && !$Colorname) $str.= ' (' . make_class_string($Class, TRUE) . ')' ;
    if ($Title) {
        if(($Class && !$Colorname) || $GroupPerm) $str.= '&nbsp;<span class="user_title">' . display_str($Title) . '</span>' ;
        else $str.= '&nbsp;(<span class="user_title">' . display_str($Title) . '</span>)' ;
    }
    if ($DrawInBox)
        ( $str = '<span class="user_name">' . $str . '</span>' );

    return $str;
}

function make_groupperm_string($GroupPermID, $Usespan = false)
{
    global $Classes;
    if ($Usespan === false) {
        return $Classes[$GroupPermID]['Name'];
    } else {
        return '<span alt="' . $GroupPermID . '" class="groupperm" style="color:#' . $Classes[$GroupPermID]['Color'] . '" title="' . $Classes[$GroupPermID]['Description'] . '">' . $Classes[$GroupPermID]['Name'] . '</span>';
    }
}

function make_class_string($ClassID, $Usespan = false)
{
    global $Classes;
    if ($Usespan === false) {
        return $Classes[$ClassID]['Name'];
    } else {
        return '<span alt="' . $ClassID . '" class="rank" style="color:#'. $Classes[$ClassID]['Color'] . '">' . $Classes[$ClassID]['Name'] . '</span>';
    }
}

//Write to the group log
function write_group_log($GroupID, $TorrentID, $UserID, $Message, $Hidden)
{
    global $DB, $Time;
    $DB->query("INSERT INTO group_log (GroupID, TorrentID, UserID, Info, Time, Hidden) VALUES (" . (int) $GroupID . ", " . (int) $TorrentID . ", " . (int) $UserID . ", '" . db_string($Message) . "', '" . sqltime() . "', " . (int) $Hidden . ")");
}

// Write a message to the system log
function write_log($Message)
{
    global $DB, $Time;
    $DB->query('INSERT INTO log (Message, Time) VALUES (\'' . db_string($Message) . '\', \'' . sqltime() . '\')');
}

// write to user admincomment
function write_user_log($UserID, $Comment)
{
    global $DB;
    $AdminComment = db_string( date("Y-m-d H:i:s") . " - $Comment\n" );
    $DB->query("UPDATE users_info SET AdminComment=CONCAT('$AdminComment',AdminComment) WHERE UserID='$UserID'");
}

// Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
function send_irc($Raw)
{
    $IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
    $Raw = str_replace(array("\n", "\r"), '', $Raw);
    fwrite($IRCSocket, $Raw);
    fclose($IRCSocket);
}

function delete_torrent($ID, $GroupID=0, $UserID = 0)
{
    global $DB, $Cache, $LoggedUser;
    if (!$GroupID) {
        $DB->query("SELECT GroupID, UserID FROM torrents WHERE ID='$ID'");
        list($GroupID, $UploaderID) = $DB->next_record();

    }
    if (!$UserID) {
        $DB->query("SELECT UserID FROM torrents WHERE ID='$ID'");
        list($UserID) = $DB->next_record();
    }

    $RecentUploads = $Cache->get_value('recent_uploads_'.$UserID);
    if (is_array($RecentUploads)) {
        foreach ($RecentUploads as $Key => $Recent) {
            if ($Recent['ID'] == $GroupID) {
                $Cache->delete_value('recent_uploads_'.$UserID);
            }
        }
    }

    $DB->query("SELECT info_hash FROM torrents WHERE ID = ".$ID);
    list($InfoHash) = $DB->next_record(MYSQLI_BOTH, false);
    $DB->query("DELETE FROM torrents WHERE ID = ".$ID);
    update_tracker('delete_torrent', array('info_hash' => rawurlencode($InfoHash), 'id' => $ID));

    $Cache->decrement('stats_torrent_count');

    $DB->query("SELECT COUNT(ID) FROM torrents WHERE GroupID='$GroupID' AND flags <> 1");
    list($Count) = $DB->next_record();

    if ($Count == 0) {
        delete_group($GroupID);
    } else {
        update_hash($GroupID);
    }

    // Torrent notifications
    $DB->query("SELECT UserID FROM users_notify_torrents WHERE TorrentID='$ID'");
    while (list($UserID) = $DB->next_record()) {
        $Cache->delete_value('notifications_new_'.$UserID);
    }
    $DB->query("DELETE FROM users_notify_torrents WHERE TorrentID='$ID'");

      $DB->query("DELETE FROM torrents_reviews WHERE GroupID='$GroupID'");

    $DB->query("UPDATE reportsv2 SET
            Status='Resolved',
            LastChangeTime='" . sqltime() . "',
            ModComment='Report already dealt with (Torrent deleted)'
        WHERE TorrentID=" . $ID . "
            AND Status != 'Resolved'");
    $Reports = $DB->affected_rows();
    if ($Reports) {
        $Cache->decrement('num_torrent_reportsv2', $Reports);
    }

    $DB->query("DELETE FROM torrents_files WHERE TorrentID='$ID'");
    $DB->query("DELETE FROM torrents_bad_tags WHERE TorrentID = " . $ID);
    $DB->query("DELETE FROM torrents_bad_folders WHERE TorrentID = " . $ID);
    $DB->query("DELETE FROM torrents_bad_files WHERE TorrentID = " . $ID);
    $Cache->delete_value('torrent_download_' . $ID);
    $Cache->delete_value('torrent_group_' . $GroupID);
    $Cache->delete_value('torrents_details_' . $GroupID);
}

function delete_group($GroupID)
{
    global $DB, $Cache;
    $Cache->decrement('stats_group_count');

    // Collages
    $DB->query("SELECT CollageID FROM collages_torrents WHERE GroupID='$GroupID'");
    if ($DB->record_count()>0) {
        $CollageIDs = $DB->collect('CollageID');
        $DB->query("UPDATE collages SET NumTorrents=NumTorrents-1 WHERE ID IN (".implode(', ',$CollageIDs).")");
        $DB->query("DELETE FROM collages_torrents WHERE GroupID='$GroupID'");

        foreach ($CollageIDs as $CollageID) {
            $Cache->delete_value('collage_'.$CollageID);
        }
        $Cache->delete_value('torrent_collages_'.$GroupID);
    }

    // Requests
    $DB->query("SELECT ID FROM requests WHERE GroupID='$GroupID'");
    $Requests = $DB->collect('ID');
    $DB->query("UPDATE requests SET GroupID = NULL WHERE GroupID = '$GroupID'");
    foreach ($Requests as $RequestID) {
        $Cache->delete_value('request_'.$RequestID);
    }

        // Decrease the tag count, if it's not in use any longer and not an official tag, delete it from the list.
        $DB->query("SELECT tt.TagID, t.Uses, t.TagType
                    FROM torrents_tags AS tt
                        JOIN tags AS t ON t.ID = tt.TagID
                    WHERE GroupID ='$GroupID'");
        $Tags = $DB->to_array();
        foreach ($Tags as $Tag) {
            $Uses = $Tag['Uses'] > 0 ?  $Tag['Uses'] - 1 : 0;
            if ($Tag['TagType'] == 'genre' || $Uses > 0) {
                $DB->query("UPDATE tags SET Uses=$Uses WHERE ID=".$Tag['TagID']);   //$TagID);
            } else {
                $DB->query("DELETE FROM tags WHERE ID=".$Tag['TagID']." AND TagType='other'");
            }
        }

    update_show($GroupID);

    $DB->query("DELETE FROM group_log WHERE GroupID='$GroupID'");
    $DB->query("DELETE FROM torrents_group WHERE ID='$GroupID'");
    $DB->query("DELETE FROM torrents_tags WHERE GroupID='$GroupID'");
    $DB->query("DELETE FROM torrents_tags_votes WHERE GroupID='$GroupID'");
    $DB->query("DELETE FROM torrents_comments WHERE GroupID='$GroupID'");
    $DB->query("DELETE FROM bookmarks_torrents WHERE GroupID='$GroupID'");
    $DB->query("REPLACE INTO sphinx_delta (ID,Time) VALUES ('$GroupID',UNIX_TIMESTAMP())"); // Tells Sphinx that the group is removed

    $Cache->delete_value('torrents_details_'.$GroupID);
    $Cache->delete_value('torrent_group_'.$GroupID);
}

function warn_user($UserID, $Duration, $Reason)
{
    global $LoggedUser, $DB, $Cache, $Time;

    $DB->query("SELECT Warned FROM users_info WHERE UserID=" . $UserID . " AND Warned <> '0000-00-00 00:00:00'");
    if ($DB->record_count() > 0) {
        //User was already warned, appending new warning to old.
        list($OldDate) = $DB->next_record();
        $NewExpDate = date('Y-m-d H:i:s', strtotime($OldDate) + $Duration);

        send_pm($UserID, 0, db_string("You have received multiple warnings."), db_string("When you received your latest warning (Set to expire on " . date("Y-m-d", (time() + $Duration)) . "), you already had a different warning (Set to expire on " . date("Y-m-d", strtotime($OldDate)) . ").\n\n Due to this collision, your warning status will now expire at " . $NewExpDate . "."));

        $AdminComment = date("Y-m-d H:i:s") . ' - Warning (Clash) extended to expire at ' . $NewExpDate . ' by ' . $LoggedUser['Username'] . "\nReason: $Reason\n";

        $DB->query('UPDATE users_info SET
            Warned=\'' . db_string($NewExpDate) . '\',
            WarnedTimes=WarnedTimes+1,
            AdminComment=CONCAT(\'' . db_string($AdminComment) . '\',AdminComment)
            WHERE UserID=\'' . db_string($UserID) . '\'');
    } else {
        //Not changing, user was not already warned
        $WarnTime = time_plus($Duration);

        $Cache->begin_transaction('user_info_' . $UserID);
        $Cache->update_row(false, array('Warned' => $WarnTime));
        $Cache->commit_transaction(0);

        $AdminComment = date("Y-m-d H:i:s") . ' - Warned until ' . $WarnTime . ' by ' . $LoggedUser['Username'] . "\nReason: $Reason\n";

        $DB->query('UPDATE users_info SET
            Warned=\'' . db_string($WarnTime) . '\',
            WarnedTimes=WarnedTimes+1,
            AdminComment=CONCAT(\'' . db_string($AdminComment) . '\',AdminComment)
            WHERE UserID=\'' . db_string($UserID) . '\'');
    }
}

/* -- update_hash function ------------------------------------------------ */
/* ------------------------------------------------------------------------ */
/* This function is to update the cache and sphinx delta index to keep    */
/* everything up to date                                                  */
/* -- TODO ---------------------------------------------------------------- */
/* Add in tag sorting based on positive negative votes algo   - done mifune -            */
/* * *********************************************************************** */

function update_hash($GroupID)
{
    global $DB, $SpecialChars, $Cache;
    $DB->query("UPDATE torrents_group SET TagList=(SELECT REPLACE(GROUP_CONCAT(tags.Name ORDER BY  (t.PositiveVotes-t.NegativeVotes) DESC SEPARATOR ' '),'.','_')
        FROM torrents_tags AS t
        INNER JOIN tags ON tags.ID=t.TagID
        WHERE t.GroupID='$GroupID'
        GROUP BY t.GroupID)
        WHERE ID='$GroupID'");

    $DB->query("REPLACE INTO sphinx_delta (ID, GroupName, TagList, NewCategoryID, Image, Time, Size, Snatched, Seeders, Leechers, FreeTorrent, FileList, FileName, FileSize, SearchText)
        SELECT
        g.ID AS ID,
        g.Name AS GroupName,
        g.TagList,
        g.NewCategoryID,
        g.Image,
        UNIX_TIMESTAMP(g.Time) AS Time,
        MAX(CEIL(t.Size/1024)) AS Size,
        SUM(t.Snatched) AS Snatched,
        SUM(t.Seeders) AS Seeders,
        SUM(t.Leechers) AS Leechers,
        BIT_OR(t.FreeTorrent-1) AS FreeTorrent,
        GROUP_CONCAT(REPLACE(REPLACE(FileList, '|||', '\n '), '_', ' ') SEPARATOR '\n ') AS FileList,
        t.FileName,
        t.Size AS FileSize,
        g.SearchText
        FROM torrents AS t
        JOIN torrents_group AS g ON g.ID=t.GroupID
        WHERE g.ID=$GroupID
        GROUP BY g.ID");

    $Cache->delete_value('torrents_details_'.$GroupID);
    $Cache->delete_value('torrent_group_'.$GroupID);
}

//  OPTIMISED a bit more for mass sending (only put in an array of numbers if fromID==system (0)
// this function sends a PM to the userid $ToID and from the userid $FromID, sets date to now
// this function no longer uses db_string() so you will need to escape strings before using this function!
// set userid to 0 for a PM from 'system'
// if $ConvID is not set, it auto increments it, ie. starting a new conversation
function send_pm($ToID, $FromID, $Subject, $Body, $ConvID='')
{
    global $DB, $Cache ;

    if (!is_array($ToID)) $ToID = array($ToID);

    // Clear the caches of the inbox and sentbox
    foreach ($ToID as $key=>$ID) {
        if (!is_number($ID)) return false;
        // Don't allow users to send messages to the system
        if ($ID == 0) unset($ToID[$key]);
        if ($ID == $FromID) unset($ToID[$key]); // or themselves
    }
    if (count($ToID)==0) return false;
    if (count($ToID)>1 && $FromID!==0) return false; // masspms not from the system with the same convID dont work
    $sqltime = sqltime();

    if ($ConvID == '') { // new pm

        $DB->query("INSERT INTO pm_conversations (Subject) VALUES ('" . $Subject . "')");
        $ConvID = $DB->inserted_id();

        if ($FromID != 0) {
            $Values = "('$FromID', '$ConvID', '0','1','$sqltime', '$sqltime', '0'),";
        }
        $Values .= "('".implode("', '$ConvID', '1','0', '$sqltime', '$sqltime', '1'), ('", $ToID)."', '$ConvID', '1','0', '$sqltime', '$sqltime', '1')";

        $DB->query("INSERT INTO pm_conversations_users
                                        (UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead) VALUES
                                        $Values");

    } else { // responding to exisiting

        $DB->query("UPDATE pm_conversations_users SET
                InInbox='1',
                UnRead='1',
                ReceivedDate='$sqltime'
                WHERE UserID IN (" . implode(',', $ToID) . ")
                AND ConvID='$ConvID'");

        $DB->query("UPDATE pm_conversations_users SET
                InSentbox='1',
                SentDate='$sqltime'
                WHERE UserID='$FromID'
                AND ConvID='$ConvID'");
    }

    $DB->query("INSERT INTO pm_messages
            (SenderID, ConvID, SentDate, Body) VALUES
            ('$FromID', '$ConvID', '$sqltime', '" . $Body . "')");

    // Clear the caches of the inbox and sentbox
    foreach ($ToID as $ID) {
        $Cache->delete_value('inbox_new_' . $ID);
    }
    if ($FromID != 0) $Cache->delete_value('inbox_new_' . $FromID);
    // DEBUG only:
    //write_log("Sent MassPM to ".count($ToID)." users. ConvID: $ConvID  Subject: $Subject");
    return $ConvID;
}

//Create thread function, things should already be escaped when sent here.
//Almost all the code is stolen straight from the forums and tailored for new posts only
function create_thread($ForumID, $AuthorID, $Title, $PostBody)
{
    global $DB, $Cache, $Time;
    if (!$ForumID || !$AuthorID || !is_number($AuthorID) || !$Title || !$PostBody) {
        return -1;
    }

    $DB->query("SELECT Username FROM users_main WHERE ID=" . $AuthorID);
    if ($DB->record_count() < 1) {
        return -2;
    }
    list($AuthorName) = $DB->next_record();

    $ThreadInfo = array();
    $ThreadInfo['IsLocked'] = 0;
    $ThreadInfo['IsSticky'] = 0;

    $DB->query("INSERT INTO forums_topics
        (Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID)
        Values
        ('" . $Title . "', '" . $AuthorID . "', '$ForumID', '" . sqltime() . "', '" . $AuthorID . "')");
    $TopicID = $DB->inserted_id();
    $Posts = 1;

    $DB->query("INSERT INTO forums_posts
            (TopicID, AuthorID, AddedTime, Body)
            VALUES
            ('$TopicID', '" . $AuthorID . "', '" . sqltime() . "', '" . $PostBody . "')");
    $PostID = $DB->inserted_id();

    $DB->query("UPDATE forums SET
                NumPosts		  = NumPosts+1,
                NumTopics		 = NumTopics+1,
                LastPostID		= '$PostID',
                LastPostAuthorID  = '" . $AuthorID . "',
                LastPostTopicID   = '$TopicID',
                LastPostTime	  = '" . sqltime() . "'
                WHERE ID = '$ForumID'");

    $DB->query("UPDATE forums_topics SET
            NumPosts		  = NumPosts+1,
            LastPostID		= '$PostID',
            LastPostAuthorID  = '" . $AuthorID . "',
            LastPostTime	  = '" . sqltime() . "'
            WHERE ID = '$TopicID'");

    // Bump this topic to head of the cache
    list($Forum,,, $Stickies) = $Cache->get_value('forums_' . $ForumID);
    if (!empty($Forum)) {
        if (count($Forum) == TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
            array_pop($Forum);
        }
        $DB->query("SELECT f.IsLocked, f.IsSticky, f.NumPosts FROM forums_topics AS f WHERE f.ID ='$TopicID'");
        list($IsLocked, $IsSticky, $NumPosts) = $DB->next_record();
        $Part1 = array_slice($Forum, 0, $Stickies, true); //Stickys
        $Part2 = array(
            $TopicID => array(
                'ID' => $TopicID,
                'Title' => $Title,
                'AuthorID' => $AuthorID,
                'AuthorUsername' => $AuthorName,
                'IsLocked' => $IsLocked,
                'IsSticky' => $IsSticky,
                'NumPosts' => $NumPosts,
                'LastPostID' => $PostID,
                'LastPostTime' => sqltime(),
                'LastPostAuthorID' => $AuthorID,
                'LastPostUsername' => $AuthorName
            )
        ); //Bumped thread
        $Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE, true); //Rest of page
        if ($Stickies > 0) {
            $Part1 = array_slice($Forum, 0, $Stickies, true); //Stickies
            $Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); //Rest of page
        } else {
            $Part1 = array();
            $Part3 = $Forum;
        }
        if (is_null($Part1)) {
            $Part1 = array();
        }
        if (is_null($Part3)) {
            $Part3 = array();
        }
        $Forum = $Part1 + $Part2 + $Part3;
        $Cache->cache_value('forums_' . $ForumID, array($Forum, '', 0, $Stickies), 0);
    }

    //Update the forum root
    $Cache->begin_transaction('forums_list');
    $UpdateArray = array(
        'NumPosts' => '+1',
        'LastPostID' => $PostID,
        'LastPostAuthorID' => $AuthorID,
        'Username' => $AuthorName,
        'LastPostTopicID' => $TopicID,
        'LastPostTime' => sqltime(),
        'Title' => $Title,
        'IsLocked' => $ThreadInfo['IsLocked'],
        'IsSticky' => $ThreadInfo['IsSticky']
    );

    $UpdateArray['NumTopics'] = '+1';

    $Cache->update_row($ForumID, $UpdateArray);
    $Cache->commit_transaction(0);

    $CatalogueID = floor((POSTS_PER_PAGE * ceil($Posts / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);
    $Cache->begin_transaction('thread_' . $TopicID . '_catalogue_' . $CatalogueID);
    $Post = array(
        'ID' => $PostID,
        'AuthorID' => $LoggedUser['ID'],
        'AddedTime' => sqltime(),
        'Body' => $PostBody,
        'EditedUserID' => 0,
        'EditedTime' => '0000-00-00 00:00:00',
        'Username' => ''
    );
    $Cache->insert('', $Post);
    $Cache->commit_transaction(0);

    $Cache->begin_transaction('thread_' . $TopicID . '_info');
    $Cache->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $AuthorID));
    $Cache->commit_transaction(0);

    return $TopicID;
}

// Check to see if a user has the permission to perform an action
function check_perms($PermissionName, $MinClass = 0)
{
    global $LoggedUser;

    return (isset($LoggedUser['Permissions'][$PermissionName]) && $LoggedUser['Permissions'][$PermissionName] && $LoggedUser['Class'] >= $MinClass) ? true : false;
}

function check_force_anon($UserID)
{
    global $LoggedUser;

    return $UserID == $LoggedUser['ID'] || !check_perms('site_force_anon_uploaders')  ;
}

// Function to get data and torrents for an array of GroupIDs.
// In places where the output from this is merged with sphinx filters, it will be in a different order.
function get_groups($GroupIDs, $Return = true, $Torrents = true)
{
    global $DB, $Cache, $LoggedUser;

    $Found = array_flip($GroupIDs);
    $NotFound = array_flip($GroupIDs);
    $Key = $Torrents ? 'torrent_group_' : 'torrent_group_light_';

    foreach ($GroupIDs as $GroupID) {
        $Data = $Cache->get_value($Key.$GroupID);
        if (!empty($Data) && (@$Data['ver'] >= 5)) {
            unset($NotFound[$GroupID]);
            $Found[$GroupID] = $Data['d'];
            if ($Torrents) {
                foreach ($Found[$GroupID]['Torrents'] as $TID=>&$TData) {
                    $TorrentPeerInfo = get_peers($TID);
                    $TData[3]=$TorrentPeerInfo['Seeders'];
                    $TData[4]=$TorrentPeerInfo['Leechers'];
                    $TData[5]=$TorrentPeerInfo['Snatched'];
                    $TData['Seeders']=$TorrentPeerInfo['Seeders'];
                    $TData['Leechers']=$TorrentPeerInfo['Leechers'];
                    $TData['Snatched']=$TorrentPeerInfo['Snatched'];
                }
            }
        }
    }

    $IDs = implode(',',array_flip($NotFound));

    /*
    Changing any of these attributes returned will cause very large, very dramatic site-wide chaos.
    Do not change what is returned or the order thereof without updating:
        torrents, collages, bookmarks, better, the front page,
    and anywhere else the get_groups function is used.
    */

    if (count($NotFound)>0) {
        $DB->query("SELECT g.ID, g.Name, g.TagList FROM torrents_group AS g WHERE g.ID IN ($IDs)");

        while ($Group = $DB->next_record(MYSQLI_ASSOC, true)) {
            unset($NotFound[$Group['ID']]);
            $Found[$Group['ID']] = $Group;
            $Found[$Group['ID']]['Torrents'] = array();
        }

        if ($Torrents) {

            $DB->query("SELECT t.ID, t.UserID, um.Username, t.GroupID, FileCount, FreeTorrent, DoubleTorrent,
                                        Size, Leechers, Seeders, Snatched, FilePath, Season, t.Time, t.ID AS HasFile, r.ReportCount, t.Anonymous
                          FROM torrents AS t
                     LEFT JOIN users_main AS um ON t.UserID=um.ID
                     LEFT JOIN (SELECT TorrentID, count(*) as ReportCount FROM reportsv2
                                 WHERE Type != 'edited' AND Status != 'Resolved' GROUP BY TorrentID) AS r ON r.TorrentID=t.ID
                         WHERE t.GroupID IN($IDs)
                      ORDER BY GroupID DESC, t.ID");

            while ($Torrent = $DB->next_record(MYSQLI_ASSOC, true)) {
                $Found[$Torrent['GroupID']]['Torrents'][$Torrent['ID']] = $Torrent;

                $CacheTime = $Torrent['Seeders']==0 ? 120 : 900;
                $TorrentPeerInfo = array('Seeders'=>$Torrent['Seeders'],'Leechers'=>$Torrent['Leechers'],'Snatched'=>$Torrent['Snatched']);
                $Cache->cache_value('torrent_peers_'.$Torrent['ID'], $TorrentPeerInfo, $CacheTime);

                $Cache->cache_value('torrent_group_'.$Torrent['GroupID'], array('ver'=>5, 'd'=>$Found[$Torrent['GroupID']]), 0);
                $Cache->cache_value('torrent_group_light_'.$Torrent['GroupID'], array('ver'=>5, 'd'=>$Found[$Torrent['GroupID']]), 0);
            }
        } else {
            foreach ($Found as $Group) {
                $Cache->cache_value('torrent_group_light_'.$Group['ID'], array('ver'=>5, 'd'=>$Found[$Group['ID']]), 0);
            }
        }
    }

    if ($Return) { // If we're interested in the data, and not just caching it
        $Matches = array('matches'=>$Found, 'notfound'=>array_flip($NotFound));

        return $Matches;
    }
}

function get_peers($TorrentID)
{
    global $DB, $Cache, $LoggedUser;

    $TorrentPeerInfo = $Cache->get_value('torrent_peers_'.$TorrentID);
    if ($TorrentPeerInfo===false) {
            // testing with 'dye'
        $DB->query("SELECT Seeders, Leechers, Snatched FROM torrents WHERE ID ='$TorrentID'");
        $TorrentPeerInfo = $DB->next_record(MYSQLI_ASSOC) ;
        $CacheTime = $TorrentPeerInfo['Seeders']==0 ? 120 : 900;
        $Cache->cache_value('torrent_peers_'.$TorrentID, $TorrentPeerInfo, $CacheTime);
    }

    return $TorrentPeerInfo;
}

function get_last_review($GroupID)
{
    global $DB, $Cache;
    $LastReview = $Cache->get_value('torrent_review_'.$GroupID);
    if ($LastReview===false || $LastReview['ver']<2) {
        $DB->query("SELECT tr.ID,
                           tr.Status,
                           tr.Time,
                           tr.KillTime,
                           IF(tr.ReasonID = 0, tr.Reason, rr.Description) AS StatusDescription,
                           tr.ConvID,
                           tr.UserID AS UserID,
                           u.Username AS Username
                      FROM torrents_reviews AS tr
                 LEFT JOIN review_reasons AS rr ON rr.ID = tr.ReasonID
                 LEFT JOIN users_main AS u ON u.ID=tr.UserID
                     WHERE tr.GroupID=$GroupID
                  ORDER BY tr.Time DESC
                     LIMIT 1 " );
        $LastReviewRow = $DB->next_record(MYSQLI_ASSOC);
        if ($LastReviewRow['Status']!='Pending') { // if last review log is not from a user
            $LastReviewRow['StaffID']=$LastReviewRow['UserID'];
            $LastReviewRow['Staffname']=$LastReviewRow['Username'];
        } else {
            $DB->query("SELECT tr.UserID AS StaffID, u.Username AS Staffname
                          FROM torrents_reviews AS tr
                     LEFT JOIN users_main AS u ON u.ID=tr.UserID
                     WHERE tr.GroupID=$GroupID AND tr.Status!='Pending'
                  ORDER BY tr.Time DESC
                     LIMIT 1 ");
            $LastStaffReview = $DB->next_record(MYSQLI_ASSOC);
            $LastReviewRow['StaffID']=$LastStaffReview['StaffID'];
            $LastReviewRow['Staffname']=$LastStaffReview['Staffname'];
        }
        $LastReview = array('ver'=>2, 'd'=>$LastReviewRow) ;
        $Cache->cache_value('torrent_review_'.$GroupID, $LastReview, 0);
    }

    return $LastReview['d'];
}

// moved this here from requests/functions.php as get_requests() is dependent
function get_request_tags($RequestID)
{
    global $DB;
    $DB->query("SELECT rt.TagID,
                    t.Name
                FROM requests_tags AS rt
                    JOIN tags AS t ON rt.TagID=t.ID
                WHERE rt.RequestID = ".$RequestID."
                ORDER BY rt.TagID ASC");
    $Tags = $DB->to_array();
    $Results = array();
    foreach ($Tags as $TagsRow) {
        list($TagID, $TagName) = $TagsRow;
        $Results[$TagID]= $TagName;
    }

    return $Results;
}

//Function to get data from an array of $RequestIDs.
//In places where the output from this is merged with sphinx filters, it will be in a different order.
function get_requests($RequestIDs, $Return = true)
{
    global $DB, $Cache;

    $Found = array_flip($RequestIDs);
    $NotFound = array_flip($RequestIDs);

    foreach ($RequestIDs as $RequestID) {
        $Data = $Cache->get_value('request_' . $RequestID);
        if (!empty($Data)) {
            unset($NotFound[$RequestID]);
            $Found[$RequestID] = $Data;
        }
    }

    $IDs = implode(',', array_flip($NotFound));

    /*
      Don't change without ensuring you change everything else that uses get_requests()
     */

    if (count($NotFound) > 0) {
        $DB->query("SELECT
                    r.ID AS ID,
                    r.UserID,
                    u.Username,
                    r.TimeAdded,
                    r.LastVote,
                    r.CategoryID,
                    r.Title,
                    r.Image,
                    r.Description,
                    r.FillerID,
                    filler.Username,
                    r.TorrentID,
                    r.TimeFilled,
                    r.GroupID,
                    r.UploaderID,
                    uploader.Username,
                    r.TVMazeID,
                    r.Season,
                    r.Episode,
                    r.Resolution,
                    r.Source,
                    r.Codec,
                    r.Container,
                    r.ReleaseGroup
                FROM requests AS r
                    LEFT JOIN users_main AS u ON u.ID=r.UserID
                    LEFT JOIN users_main AS filler ON filler.ID=FillerID AND FillerID!=0
                    LEFT JOIN users_main AS uploader ON uploader.ID=r.UploaderID AND r.UploaderID!=0
                WHERE r.ID IN (" . $IDs . ")
                ORDER BY ID");

        $Requests = $DB->to_array();
        foreach ($Requests as $Request) {
            unset($NotFound[$Request['ID']]);
            $Request['Tags'] = get_request_tags($Request['ID']);
            $Found[$Request['ID']] = $Request;
            $Cache->cache_value('request_' . $Request['ID'], $Request, 0);
        }
    }

    if ($Return) { // If we're interested in the data, and not just caching it
        $Matches = array('matches' => $Found, 'notfound' => array_flip($NotFound));

        return $Matches;
    }
}

function update_sphinx_requests($RequestID)
{
    global $DB, $Cache;

    $DB->query("REPLACE INTO sphinx_requests_delta (
                ID, UserID, TimeAdded, LastVote, CategoryID,
                                Title, FillerID, TorrentID,
                TimeFilled, Visible, Votes, Bounty)
            SELECT
                ID, r.UserID, UNIX_TIMESTAMP(TimeAdded) AS TimeAdded,
                UNIX_TIMESTAMP(LastVote) AS LastVote, CategoryID,
                Title, FillerID, TorrentID,
                UNIX_TIMESTAMP(TimeFilled) AS TimeFilled, Visible,
                COUNT(rv.UserID) AS Votes, CEIL(SUM(rv.Bounty)/1024) AS Bounty
            FROM requests AS r LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
                wHERE ID = " . $RequestID . "
                GROUP BY r.ID");

    $Cache->delete_value('request_'.$RequestID);
}

function get_tags($TagNames)
{
    global $Cache, $DB;
    $TagIDs = array();
    foreach ($TagNames as $Index => $TagName) {
        $Tag = $Cache->get_value('tag_id_' . $TagName);
        if (is_array($Tag)) {
            unset($TagNames[$Index]);
            $TagIDs[$Tag['ID']] = $Tag['Name'];
        }
    }
    if (count($TagNames) > 0) {
        $DB->query("SELECT ID, Name FROM tags WHERE Name IN ('" . implode("', '", $TagNames) . "')");
        $SQLTagIDs = $DB->to_array();
        foreach ($SQLTagIDs as $Tag) {
            $TagIDs[$Tag['ID']] = $Tag['Name'];
            $Cache->cache_value('tag_id_' . $Tag['Name'], $Tag, 0);
        }
    }

    return($TagIDs);
}

function get_overlay_html($GroupName, $Username, $Image, $Seeders, $Leechers, $Size, $Snatched, $FilePath)
{
    $OverImage = $Image != '' ? $Image : '/static/common/noartwork/noimage.png';

    # Temporary solution for image load on fapping - TODO proper permanent solution, this is ugly
    $matches = array();
    if (preg_match('#^(http://fapping\.empornium\.sx/images/.*)\.(gif|jpg|png)$#', $OverImage, $matches)) {
        if (substr($matches[1], -3) != '.th') {
            $OverImage = $matches[1].'.th.'.$matches[2];
        }
    }

    $OverName = mb_strlen($GroupName) <= 80 ? $GroupName : mb_substr($GroupName, 0, 76) . '...';
    $OverFile = mb_strlen($FilePath)  <= 80 ? $FilePath  : mb_substr($FilePath,  0, 76) . '...';
    $SL = ($Seeders == 0 ? "<span class=r00>" . number_format($Seeders) . "</span>" : number_format($Seeders)) . " / " . number_format($Leechers);

    $Overlay  = '';
    $Overlay .= "<table class=overlay>";
    $Overlay .= "<tr><td class=overlay colspan=2><strong>" . $OverName . "</strong></td></tr>";
//    $Overlay .= "<tr><td class=overlay colspan=2><strong>" . $OverFile . "</strong></td></tr>";
    $Overlay .= "<tr><td class=leftOverlay><img style='max-width: 400px;' src=" . $OverImage . "></td>";
    $Overlay .= "<td class=rightOverlay><strong>Uploader:</strong> $Username<br />";
    $Overlay .= "<strong>Size:</strong> " . get_size($Size) . "<br />";
    $Overlay .= "<strong>Snatched:</strong> " . number_format($Snatched) . "<br />";
    $Overlay .= "<strong>Seeders/Leechers:</strong> " . $SL . "<br />";
    $Overlay .= "</td></tr>";
    $Overlay .= "<tr><td class=overlay colspan=2>" . $OverFile . "</td></tr>";
    $Overlay .= "</table>";

    return $Overlay;
}

function torrent_icons($Data, $TorrentID, $Review, $IsBookmarked, $SearchTitle) {  //  $UserID,
    global $DB, $Cache, $LoggedUser, $TorrentUserStatus, $Sitewide_Freeleech_On, $Sitewide_Freeleech;
        $SeedTooltip='';
        $FreeTooltip='';
        if ($Data['FreeTorrent'] == '1') {
            $FreeTooltip = "Unlimited Freeleech";
        } elseif ($Data['FreeTorrent'] == '2') {
            $FreeTooltip = "Neutral Freeleech";
        } elseif ($Sitewide_Freeleech_On) {
            $FreeTooltip = "Sitewide Freeleech for ".time_diff($Sitewide_Freeleech, 2,false,false,0);
        }

        if ($Data['DoubleTorrent'] == '1') {
            $SeedTooltip = "Unlimited Doubleseed";
        }

        $UserID = $LoggedUser['ID'];
        $TokenTorrents = $Cache->get_value('users_tokens_' .$UserID );
        if ($TokenTorrents===false) {
            $DB->query("SELECT TorrentID, FreeLeech, DoubleSeed FROM users_slots WHERE UserID=$UserID");
            $TokenTorrents = $DB->to_array('TorrentID');
            $Cache->cache_value('users_tokens_' . $UserID, $TokenTorrents);
        }

        if (!empty($TokenTorrents[$TorrentID]) && $TokenTorrents[$TorrentID]['FreeLeech'] > sqltime()) {
            $FreeTooltip = "Personal Freeleech for ".time_diff($TokenTorrents[$TorrentID]['FreeLeech'], 2,false,false,0);
        }

        if (!empty($TokenTorrents[$TorrentID]) && $TokenTorrents[$TorrentID]['DoubleSeed'] > sqltime()) {
            $SeedTooltip = "Personal Doubleseed for ".time_diff($TokenTorrents[$TorrentID]['DoubleSeed'], 2,false,false,0);
        }

        $Icons = '';
        if ($SeedTooltip)
            $Icons .= '<span class="icon icon_doubleseed" title="'.$SeedTooltip.'"></span>';
//      if ($FreeTooltip)
//          $Icons .= '&nbsp;<img src="static/common/symbols/freedownload.svg" alt="Freeleech" title="'.$FreeTooltip.'" />';

        $SnatchedTorrents = $Cache->get_value('users_torrents_snatched_' .$UserID );
        if ($SnatchedTorrents===false) {
            $DB->query("SELECT DISTINCT x.fid as TorrentID
                          FROM xbt_snatched AS x JOIN torrents AS t ON t.ID=x.fid
                         WHERE x.uid='$UserID' AND x.upload = '0'");

            $SnatchedTorrents = $DB->to_array('TorrentID');
            $Cache->cache_value('users_torrents_snatched_' . $UserID, $SnatchedTorrents, 21600);
        }

        $GrabbedTorrents = $Cache->get_value('users_torrents_grabbed_' .$UserID );
        if ($GrabbedTorrents===false) {
            $DB->query("SELECT DISTINCT ud.TorrentID
                                  FROM users_downloads AS ud JOIN torrents AS t ON t.ID=ud.TorrentID
                                 WHERE ud.UserID='$UserID' ");

            $GrabbedTorrents = $DB->to_array('TorrentID');
            $Cache->cache_value('users_torrents_grabbed_' . $UserID, $GrabbedTorrents);
        }

        if ($Review) {
            if (check_perms('torrents_review')) {
                $Icons .= get_status_icon_staff($Review['Status'], $Review['Staffname'], $Review['StatusDescription']);
            } else {
                $Icons .= get_status_icon($Review['Status']) ;
            }
        }

        if ($IsBookmarked) {
            $Icons .= '<a class="__bookmark-torrent" title="Remove Bookmark" data-torrentid="'.$TorrentID.'">';
            $Icons .= '<span class="icon icon_bookmark bookmarked"></span>';
            $Icons .= '</a>';
        } else {
            $Icons .= '<a class="__bookmark-torrent" title="Add Bookmark" data-torrentid="'.$TorrentID.'">';
            $Icons .= '<span class="icon icon_bookmark"></span>';
            $Icons .= '</a>';
        }

        //icon_disk_grabbed icon_disk_snatched
        if ( !$Review || !$Review['Status'] ||  $Review['Status'] == 'Okay' || check_perms('torrents_download_override')) {

            if ($TorrentUserStatus[$TorrentID]['PeerStatus'] == 'S') {
                $Icons .= '<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$LoggedUser['AuthKey'].'&amp;torrent_pass='.$LoggedUser['torrent_pass'].'" title="Currently Seeding Torrent">';
                $Icons .= '<span class="icon icon_disk_seed"></span>';
                $Icons .= '</a>';
            } elseif ($TorrentUserStatus[$TorrentID]['PeerStatus'] == 'L') {
                $Icons .= '<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$LoggedUser['AuthKey'].'&amp;torrent_pass='.$LoggedUser['torrent_pass'].'"  title="Currently Leeching Torrent">';
                $Icons .= '<span class="icon icon_disk_leech"></span>';
                $Icons .= '</a>';
            } elseif (isset($SnatchedTorrents[$TorrentID])) {
                $Icons .= '<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$LoggedUser['AuthKey'].'&amp;torrent_pass='.$LoggedUser['torrent_pass'].'" title="Previously Snatched Torrent">';
                $Icons .= '<span class="icon icon_disk_snatched"></span>';
                $Icons .= '</a>';
            } elseif (isset($GrabbedTorrents[$TorrentID] )) {
                $Icons .= '<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$LoggedUser['AuthKey'].'&amp;torrent_pass='.$LoggedUser['torrent_pass'].'"  title="Previously Grabbed Torrent File">';
                $Icons .= '<span class="icon icon_disk_grabbed"></span>';
                $Icons .= '</a>';

            } elseif (empty($TorrentUserStatus[$TorrentID])) {
                $Icons .= '<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$LoggedUser['AuthKey'].'&amp;torrent_pass='.$LoggedUser['torrent_pass'].'" title="Download Torrent">';
                $Icons .= '<span class="icon icon_disk_none"></span>';
                $Icons .= '</a>';
            }           
        } else {

            if ($TorrentUserStatus[$TorrentID]['PeerStatus'] == 'S') {
                $Icons .= '<span class="icon icon_disk_seed" title="Warning: You are seeding a torrent that is marked for deletion"></span> ';
            } elseif ($TorrentUserStatus[$TorrentID]['PeerStatus'] == 'L') {
                $Icons .= '<span class="icon icon_disk_leech" title="Warning: You are leeching a torrent that is marked for deletion"></span> ';
            } elseif (isset($SnatchedTorrents[$TorrentID])) {
                $Icons .= '<span class="icon icon_disk_snatched" title="Previously Snatched Torrent"></span>';
            } elseif (isset($GrabbedTorrents[$TorrentID] )) {
                $Icons .= '<span class="icon icon_disk_grabbed" title="Previously Grabbed Torrent File"></span>';

            }
        }

        if (check_perms('torrents_delete') && $SearchTitle) {
            $Icons .= '<a href="'.get_search($SearchTitle).'">';
            $Icons .= '<span class="icon icon_search" title="Search"></span>';
            $Icons .= '</a>';
        } 

        return $Icons;

}

function get_status_icon_staff($Status, $Staffname, $Reason)
{
    if ($Status == 'Warned' || $Status == 'Pending')
        return "<a title=\"$Status: [$Reason] by $Staffname\" class=\"icon icon_warning\"></a>";
    elseif ($Status == 'Okay')
        return '<a title="This torrent has been checked by staff ('.$Staffname.') and is okay" class="icon icon_okay"></a>';
    else return '';
}

function get_status_icon($Status)
{
    if ($Status == 'Warned' || $Status == 'Pending') return '<span title="This torrent will be automatically deleted unless the uploader fixes it" class="icon icon_warning"></span>';
    elseif ($Status == 'Okay') return '<span title="This torrent has been checked by staff and is okay" class="icon icon_okay"></span>';
    else return '';
}

function get_num_comments($GroupID)
{
    global $DB, $Cache;

    $Results = $Cache->get_value('torrent_comments_'.$GroupID);
    if ($Results === false) {
          $DB->query("SELECT
                      COUNT(c.ID)
                      FROM torrents_comments as c
                      WHERE c.GroupID = '$GroupID'");
          list($Results) = $DB->next_record();
          $Cache->cache_value('torrent_comments_'.$GroupID, $Results, 0);
    }

    return $Results;
}

// Echo data sent in a form, typically a text area
function form($Index, $Return = false)
{
    if (!empty($_GET[$Index])) {
        if ($Return) {
            return display_str($_GET[$Index]);
        } else {
            echo display_str($_GET[$Index]);
        }
    }
}

// Check/select tickboxes and <select>s
function selected($Name, $Value, $Attribute='selected', $Array = array())
{
    if (!empty($Array)) {
	if(is_array($Array)){
            if (isset($Array[$Name]) && $Array[$Name] !== '') {
                if ($Array[$Name] == $Value) {
                    echo ' ' . $Attribute . '="' . $Attribute . '"';
	        }
            }
        } else {
            if ($Array == $Value) {
                echo ' ' . $Attribute . '="' . $Attribute . '"';
            }
        }
    } else {
        if (isset($_GET[$Name]) && $_GET[$Name] !== '') {
            if ($_GET[$Name] == $Value) {
                echo ' ' . $Attribute . '="' . $Attribute . '"';
            }
        }
    }
}

function error($Error, $Ajax=false)
{
    global $Debug;
    require(SERVER_ROOT . '/sections/error/index.php');
    $Debug->profile();
    die();
}

/**
 * @param BanReason 0 - Unknown, 1 - Manual, 2 - Ratio, 3 - Inactive, 4 - Cheating.
 */
function disable_users($UserIDs, $AdminComment, $BanReason = 1)
{
    global $Cache, $DB;
    if (!is_array($UserIDs)) {
        $UserIDs = array($UserIDs);
    }
    $DB->query("UPDATE users_info AS i JOIN users_main AS m ON m.ID=i.UserID
        SET m.Enabled='2',
        m.can_leech='0',
        i.AdminComment = CONCAT('" . sqltime() . " - " . ($AdminComment ? $AdminComment : 'Disabled by system') . "\n', i.AdminComment),
        i.BanDate='" . sqltime() . "',
        i.BanReason='" . $BanReason . "',
        i.RatioWatchDownload=" . ($BanReason == 2 ? 'm.Downloaded' : "'0'") . "
        WHERE m.ID IN(" . implode(',', $UserIDs) . ") ");
    $Cache->decrement('stats_user_count', $DB->affected_rows() / 2); # Two tables are affected, meaning affected_rows() is actually twice the number of disabled users.
    foreach ($UserIDs as $UserID) {
        $Cache->delete_value('enabled_' . $UserID);
        $Cache->delete_value('user_info_' . $UserID);
        $Cache->delete_value('user_info_heavy_' . $UserID);
        $Cache->delete_value('user_stats_' . $UserID);

        $DB->query("SELECT SessionID FROM users_sessions WHERE UserID='$UserID' AND Active = 1");
        while (list($SessionID) = $DB->next_record()) {
            $Cache->delete_value('session_' . $UserID . '_' . $SessionID);
        }
        $Cache->delete_value('users_sessions_' . $UserID);

        $DB->query("DELETE FROM users_sessions WHERE UserID='$UserID'");
    }
    $DB->query("SELECT torrent_pass FROM users_main WHERE ID in (" . implode(", ", $UserIDs) . ")");
    $PassKeys = $DB->collect('torrent_pass');
    $Concat = "";
    foreach ($PassKeys as $PassKey) {
        if (strlen($Concat) > 4000) {
            update_tracker('remove_users', array('passkeys' => $Concat));
            $Concat = $PassKey;
        } else {
            $Concat .= $PassKey;
        }
    }
    update_tracker('remove_users', array('passkeys' => $Concat));
}

/**
 * Send a GET request over a socket directly to the tracker
 * For example, update_tracker('change_passkey', array('oldpasskey' => OLD_PASSKEY, 'newpasskey' => NEW_PASSKEY)) will send the request:
 * GET /tracker_32_char_secret_code/update?action=change_passkey&oldpasskey=OLD_PASSKEY&newpasskey=NEW_PASSKEY HTTP/1.1
 * @param $Action The action to send
 * @param $Updates An associative array of key->value pairs to send to the tracker
 */
function update_tracker($Action, $Updates, $ToIRC = false) {
    global $Tracker;
    return $Tracker->update_tracker($Action, $Updates);
}

/*
 * Send a message to the IRC bot to perform an announcement.
 */
function update_ircbot($chan=BOT_ANNOUNCE_CHAN, $msg='Announcement triggered but no message was supplied') {
    if(empty($chan) || !defined('ANNOUNCE_LISTEN_ADDRESS') || !defined('ANNOUNCE_LISTEN_PORT')) return;
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_connect($sock, ANNOUNCE_LISTEN_ADDRESS, ANNOUNCE_LISTEN_PORT);
    socket_write($sock, "PRIVMSG $chan $msg");  // rewrite for new irc-bot
    socket_close($sock);
}

/** This ends_with is slightly slower when the string is found, but a lot faster when it isn't.
 */
function ends_with($Haystack, $Needle)
{
    return substr($Haystack, strlen($Needle) * -1) == $Needle;
}

function starts_with($Haystack, $Needle)
{
    return strpos($Haystack, $Needle) === 0;
}

// amazingly fmod() does not return remanider when var2<var1... this one does
function modulos($var1, $var2)
{
  $tmp = $var1/$var2;

  return (float) ( $var1 - ( ( (int) ($tmp) ) * $var2 ) );
}

/**
 * Variant of in_array() with trailing wildcard support
 * @param string $Needle, array $Haystack
 * @return true if (substring of) $Needle exists in $Haystack
 */
function in_array_partial($Needle, $Haystack)
{
    static $Searches = array();
    if (array_key_exists($Needle, $Searches)) {
        return $Searches[$Needle];
    }
    foreach ($Haystack as $String) {
        if (substr($String, -1) == '*') {
            if (!strncmp($Needle, $String, strlen($String) - 1)) {
                $Searches[$Needle] = true;

                return true;
            }
        } elseif (!strcmp($Needle, $String)) {
            $Searches[$Needle] = true;

            return true;
        }
    }
    $Searches[$Needle] = false;

    return false;
}

/**
 * Will freeleech / neutralleech / doubleseed / normalise a set of torrents
 * @param array $TorrentIDs An array of torrents IDs to iterate over
 * @param int $FreeNeutral 0 = normal, 1 = fl, 2 = nl
 * @param int $DoubleSeed 0 = normal, 1 = ds
 */
function freedouble_torrents($TorrentIDs, $FreeNeutral = false, $DoubleSeed = false, $FromShop = false)
{
    global $DB, $Cache, $LoggedUser;

    if (!is_array($TorrentIDs)) {
        $TorrentIDs = array($TorrentIDs);
    }

    $FreeDouble = array();
    if ($FreeNeutral !== false) $FreeDouble[] = "FreeTorrent = '" . $FreeNeutral . "'";
    if ($DoubleSeed !== false)  $FreeDouble[] = "DoubleTorrent = '" . $DoubleSeed . "'";
    $FreeDouble = implode(',', $FreeDouble);

    $DB->query("UPDATE torrents SET $FreeDouble WHERE ID IN (" . implode(", ", $TorrentIDs) . ")");
    $DB->query("SELECT ID, GroupID, info_hash FROM torrents WHERE ID IN (" . implode(", ", $TorrentIDs) . ") ORDER BY GroupID ASC");
    $Torrents = $DB->to_array(false, MYSQLI_NUM, false);
    $GroupIDs = $DB->collect('GroupID');

    foreach ($Torrents as $Torrent) {
        list($TorrentID, $GroupID, $InfoHash) = $Torrent;
        update_tracker('update_torrent', array('info_hash' => rawurlencode($InfoHash), 'freetorrent' => $FreeNeutral, 'doubletorrent' => $DoubleSeed));
        $Cache->delete_value('torrent_download_' . $TorrentID);
        if ($FromShop) {
            if ($FreeNeutral != 0) {
                write_log($LoggedUser['Username'] . " bought universal freeleech for torrent " . $TorrentID);
                write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "bought universal freeleech.", 0);
            }
            if ($DoubleSeed != 0) {
                write_log($LoggedUser['Username'] . " bought universal doubleseed for torrent " . $TorrentID);
                write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "bought doubleseed.", 0);
            }
        } else {
            if ($FreeNeutral != 0) {
                write_log($LoggedUser['Username'] . " marked torrent " . $TorrentID . " as freeleech.");
                write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "marked as freeleech.", 0);
            }
            if ($DoubleSeed != 0) {
                write_log($LoggedUser['Username'] . " marked torrent " . $TorrentID . " as doubleseed.");
                write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "marked as doubleseed.", 0);
            }
        }
    }

    foreach ($GroupIDs as $GroupID) {
    	  update_show($GroupID); 
        update_hash($GroupID);
    }
}

/**
 * Convenience function to allow for passing groups to freeleech_torrents()
 */
function freedouble_groups($GroupIDs, $FreeNeutral = false, $DoubleSeed = false, $FromShop = false)
{
    global $DB;

    if (!is_array($GroupIDs)) {
        $GroupIDs = array($GroupIDs);
    }

    $DB->query("SELECT ID from torrents WHERE GroupID IN (" . implode(", ", $GroupIDs) . ")");
    if ($DB->record_count()) {
        $TorrentIDs = $DB->collect('ID');
        freedouble_torrents($TorrentIDs, $FreeNeutral, $DoubleSeed, $FromShop);
    }
}

/* Just a way to get a image url from the symbols folder */

function get_symbol_url($image)
{
    return STATIC_SERVER . 'common/symbols/' . $image;
}

// Moved from mysql class
//Handles escaping
function db_string($String,$DisableWildcards=false)
{
    global $DB;
    //Escape
    $String = $DB->escape_str($String);
    //Remove user input wildcards
    if ($DisableWildcards) {
        $String = str_replace(array('%','_'), array('\%','\_'), $String);
    }

    return $String;
}

function db_array($Array, $DontEscape = array(), $Quote = false)
{
    foreach ($Array as $Key => $Val) {
        if (!in_array($Key, $DontEscape)) {
            if ($Quote) {
                $Array[$Key] = '\''.db_string(trim($Val)).'\'';
            } else {
                $Array[$Key] = db_string(trim($Val));
            }
        }
    }

    return $Array;
}

function getCollage($collageID)
{
    global $DB, $Cache;
    $collage = $Cache->get_value('collage_'.$collageID);
    if ($collage===false) {
        $DB->query("SELECT c.Name, c.Description, c.UserID, u.Username, c.Deleted, CategoryID, Locked, MaxGroups, MaxGroupsPerUser, c.Permissions
                                             FROM collages AS c LEFT JOIN users_main As u ON c.UserID=u.ID
                                            WHERE c.ID = ".$collageID);
        $collage = $DB->to_array(FALSE, MYSQLI_ASSOC)[0];
        $Cache->cache_value('collage_'.$collageID, $collage);
    }
    return $collage;
}

function getCollageName($collageID)
{
    global $DB, $Cache;
    $name = '';
    $data = $Cache->get_value('collage_'.$collageID);
    if (is_array($data) && $data['Name']) {
        $name = $data['Name'];
    }
    if (!$name) {
        $name = $Cache->get_value('collage_name_'.$collageID);
        if (!$name) {
            $DB->query("SELECT Name FROM collages WHERE ID=".$collageID);
            list($name) = $DB->next_record();
            $Cache->cache_value('collage_name_'.$collageID, $name, 0);
        }
    }
    return $name;
}

function getArticleTitle($topicID)
{
    global $DB, $Cache, $LoggedUser;
    $title = '';
    $data = $Cache->get_value('article_'.$topicID);
    if (is_array($data) && $data['Title']) {
        $title = $data['Title'];
    }
    if (!$title) {
        $title = $Cache->get_value('article_'.$topicID);
        if (!$title) {
            $DB->query("SELECT Title FROM articles WHERE TopicID='".$topicID."'AND MinClass<=".$LoggedUser['Class']);
            list($title) = $DB->next_record();
            $Cache->cache_value('article_'.$topicID, $title, 0);
        }
    }
    return $title;
}

function getThreadName($threadID)
{
    // Might not be loaded already depending on where we want to show the latest topics box
    require_once(SERVER_ROOT.'/sections/forums/functions.php');
    $threadInfo = get_thread_info($threadID);
    $permitted = check_forumperm($threadInfo['ForumID']);
    if ($permitted) {
        return $threadInfo['Title'];
    } else {
        return '';
    }
}

function getForumName($forumID)
{
    // Might not be loaded already depending on where we want to show the latest topics box
    require_once(SERVER_ROOT.'/sections/forums/functions.php');
    $permitted = check_forumperm($forumID);
    $forums = get_forums_info();

    if ($permitted) {
        return $forums[$forumID]['Name'];
    } else {
        return '';
    }
}

function getUserName($userID)
{
    $data = user_info($userID);
    return $data['Username'];
}

$Debug->set_flag('ending function definitions');
//Include /sections/*/index.php
$Document = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH), '.php');
if ($Document == 'details') $Document = 'torrents';
if (!preg_match('/^[a-z0-9]+$/i', $Document)
    || !file_exists(SERVER_ROOT . '/sections/' . $Document . '/index.php')) {
    error(404);
}

require(SERVER_ROOT . '/sections/' . $Document . '/index.php');
$Debug->set_flag('completed module execution');

/* Required in the absence of session_start() for providing that pages will change
  upon hit rather than being browser cache'd for changing content. */
header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');

//Flush to user
ob_end_flush();

$Debug->set_flag('set headers and send to user');

//Attribute profiling
$Debug->profile();
