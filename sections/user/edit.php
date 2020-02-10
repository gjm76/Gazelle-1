<?php
$UserID = $_REQUEST['userid'];
if (!is_number($UserID)) {
    error(404);
}

$DB->query("SELECT
            m.Username,
            m.Email,
            m.IRCKey,
            m.Paranoia,
            m.Signature,
            m.PermissionID,
            m.CustomPermissions,
            i.Info,
            i.Avatar,
            i.Country,
            i.StyleID,
            i.StyleURL,
            i.SiteOptions,
            i.UnseededAlerts,
            i.TimeZone,
            m.Flag,
            i.DownloadAlt,
            i.SSLTracker,
            i.TorrentSignature,
            m.TwoFactor
            FROM users_main AS m
            JOIN users_info AS i ON i.UserID = m.ID
            LEFT JOIN permissions AS p ON p.ID=m.PermissionID
            WHERE m.ID = '".db_string($UserID)."'");

// Watch out - the next_record call is saying DON'T escape keys 3, 6, and 12, so don't add any fields before them without changing that parameter.
list($Username,$Email,$IRCKey,$Paranoia,$Signature,$PermissionID,$CustomPermissions,$Info,$Avatar,$Country,
        $StyleID,$StyleURL,$SiteOptions,$UnseededAlerts,$TimeZone,$flag,$DownloadAlt,$SSLTracker,$TorrentSignature,$TwoFactor)=$DB->next_record(MYSQLI_NUM, array(3,6,12));
$TwoFA = new TwoFactorAuth(SITE_NAME);
$Permissions = get_permissions($PermissionID);
list($Class,$PermissionValues,$MaxSigLength,$MaxAvatarWidth,$MaxAvatarHeight)=array_values($Permissions);

if ($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Class)) {
    error(403);
}

$Paranoia = unserialize($Paranoia);
if(!is_array($Paranoia)) $Paranoia = array();

function paranoia_level($Setting)
{
       global $Paranoia;
       // 0: very paranoid; 1: stats allowed, list disallowed; 2: not paranoid
       return (in_array($Setting . '+', $Paranoia)) ? 0 : (in_array($Setting, $Paranoia) ? 1 : 2);
}

function display_paranoia($FieldName)
{
       $Level = paranoia_level($FieldName);
       print '<label><input type="checkbox" name="p_'.$FieldName.'_c" '.checked($Level >= 1).' onChange="AlterParanoia()" /> Show count</label>'."&nbsp;&nbsp;\n";
       print '<label><input type="checkbox" name="p_'.$FieldName.'_l" '.checked($Level >= 2).' onChange="AlterParanoia()" /> Show list</label>';
}

function checked($Checked)
{
    return $Checked ? 'checked="checked"' : '';
}

function sorttz($a, $b)
{
    if ($a[1] == $b[1]) {
        if ($a[0] == $b[0]) {
            return 0;
        } else {
            return ($a[0] < $b[0]) ? -1 : 1;
        }
    } else {
        return ($a[1] < $b[1]) ? -1 : 1;
    }
}

function get_timezones_list()
{
    global $Cache;
    $zones = $Cache->get_value('timezones');
    if ($zones !== false) return $zones;
    $rawzones = timezone_identifiers_list();
    $Continents = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic','Australia','Europe','Indian','Pacific');
    $i = 0;
    foreach ($rawzones AS $szone) {
        $z = explode('/',$szone);
        if ( in_array($z[0], $Continents )) {
            $zones[$i][0] = $szone;
            $zones[$i][1] = -get_timezone_offset($szone);
            $i++;
        }
    }
    usort($zones, "sorttz");
    foreach ($zones AS &$zone) {
        $zone[1] = format_offset($zone[1]);
    }
    $Cache->cache_value('timezones', $zones);

    return $zones;
}

function format_offset($offset)
{
        $hours = $offset / 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs(($offset % 3600) / 60); // for stupid half hour timezones
        if ($hour == 0 && $minutes == 0) $sign = '&nbsp;';
        return "GMT $sign" . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');
}

$flags = scandir(SERVER_ROOT.'/static/common/flags/64', 0);
$flags= array_diff($flags, array('.','..'));

$DB->query("SELECT COUNT(x.uid) FROM xbt_snatched AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID'");
list($Snatched) = $DB->next_record();

if ($SiteOptions) {
    $SiteOptions = unserialize($SiteOptions);
} else {
    $SiteOptions = array();
}
if (!isset($SiteOptions['MaxTags'])) $SiteOptions['MaxTags'] = 100;

$Text = new TEXT;

show_header($Username.' > Settings','user,validate,bbcode,jquery.cookie');
echo $Val->GenerateJS('userform');
?>

<div class="thin">
    <h2>User Settings</h2>
    <div class="head"><?=format_username($UserID,$Username)?> &gt; Settings</div>
    <form id="userform" name="userform" action="" method="post" onsubmit="return formVal();" autocomplete="off">
        <div>
            <input type="hidden" name="action" value="takeedit" />
            <input type="hidden" name="userid" value="<?=$UserID?>" />
            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        </div>
        <table cellpadding='6' cellspacing='1' border='0' width='100%' class='border'>
            <tr class="colhead">
                <td colspan="2">
                    <strong>Site preferences</strong>
                </td>
            </tr>
                        <tr>
                <td colspan="2" class="right">
                    <input type="submit" value="Save Profile" title="Save all changes" />
                </td>
            </tr>
			<tr>
                <td class="label"><strong>Stylesheet</strong></td>
                <td>
                    <select name="stylesheet" id="stylesheet">
<?php  foreach ($Stylesheets as $Style) { ?>
                        <option value="<?=$Style['ID']?>"<?php  if ($Style['ID'] == $StyleID) { ?>selected="selected"<?php  } ?>><?=$Style['ProperName']?></option>
<?php  } ?>
                    </select>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Or -&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    External CSS: <input type="text" size="40" name="styleurl" id="styleurl" value="<?=display_str($StyleURL)?>" />
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Styled tooltips</strong></td>
                <td>
                    <input type="checkbox" name="usetooltipster" id="usetooltipster" <?php  if (empty($SiteOptions['UseTooltipster'])) { ?>checked="checked"<?php  } ?> />
                    <label for="usetooltipster">Enable styled tooltips</label>
                </td>
            </tr>
            <tr>
                <td class="label" title="The site uses a no-referrer policy on all links which makes this service unnecessary. But if you have an older browser, don't trust the policy, or really like delays, feel free to enable this service."><strong>Anonymizer</strong></td>
                <td title="The site uses a no-referrer policy on all links which makes this service unnecessary.">
                <input type="checkbox" id="use_anonymizer" name="use_anonymizer"<?php if ($LoggedUser['UseAnonymizer']) { echo 'checked="checked"'; } ?> />
                <label for="use_anonymizer">Use anonymizer service. I have an old browser, don't trust meta referrer tags, or I like delays.</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Time Zone</strong></td>
                <td>
                            <select name="timezone" id="timezone">
<?php
                                    $zones = get_timezones_list();
                                    foreach ($zones as $tzone) {
                                        list($zone,$offset)=$tzone;
                                        //$offset = format_offset($offset);
?>                              <option value="<?=$zone?>"<?php  if ($zone == $TimeZone) { ?>selected="selected"<?php  } ?>><?="$offset &nbsp;&nbsp;".str_replace(array('_','/'),array(' ',' / '),$zone)?></option>
<?php                                   } ?>
                            </select>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Time style</strong></td>
                <td>
                    <input type="radio" name="timestyle" value="0" <?php  if (empty($LoggedUser['TimeStyle'])||$LoggedUser['TimeStyle']==0) { ?>checked="checked"<?php  } ?> />
                    <label>Display times as time since (date and time is displayed as tooltip)</label><br/>
                    <input type="radio" name="timestyle" value="1" <?php  if ($LoggedUser['TimeStyle']==1) { ?>checked="checked"<?php  } ?> />
                    <label>Display times as date and time (time since is displayed as tooltip)</label>
                </td>
            </tr>
<?php  if (check_perms('site_advanced_search')) { ?>
            <tr>
                <td class="label"><strong>Default Search Type</strong></td>
                <td>
                    <select name="searchtype" id="searchtype">
                        <option value="0"<?php  if ($SiteOptions['SearchType'] == 0) { ?>selected="selected"<?php  } ?>>Simple</option>
                        <option value="1"<?php  if ($SiteOptions['SearchType'] == 1) { ?>selected="selected"<?php  } ?>>Advanced</option>
                    </select>
                </td>
            </tr>
<?php  } ?>
            <tr>
                <td class="label"><strong>Torrents per page</strong></td>
                <td>
                    <select name="torrentsperpage" id="torrentsperpage">
                        <option value="25"<?php  if ($SiteOptions['TorrentsPerPage'] == 25) { ?>selected="selected"<?php  } ?>>25 (Default)</option>
                        <option value="50"<?php  if ($SiteOptions['TorrentsPerPage'] == 50) { ?>selected="selected"<?php  } ?>>50</option>
                        <option value="100"<?php  if ($SiteOptions['TorrentsPerPage'] == 100) { ?>selected="selected"<?php  } ?>>100</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Posts per page (Forum)</strong></td>
                <td>
                    <select name="postsperpage" id="postsperpage">
                        <option value="25"<?php  if ($SiteOptions['PostsPerPage'] == 25) { ?>selected="selected"<?php  } ?>>25 (Default)</option>
                        <option value="50"<?php  if ($SiteOptions['PostsPerPage'] == 50) { ?>selected="selected"<?php  } ?>>50</option>
                        <option value="100"<?php  if ($SiteOptions['PostsPerPage'] == 100) { ?>selected="selected"<?php  } ?>>100</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Collage torrent covers to show per page</strong></td>
                <td>
                    <select name="collagecovers" id="collagecovers">
                        <option value="10"<?php  if ($SiteOptions['CollageCovers'] == 10) { ?>selected="selected"<?php  } ?>>10</option>
                        <option value="25"<?php  if (($SiteOptions['CollageCovers'] == 25) || !isset($SiteOptions['CollageCovers'])) { ?>selected="selected"<?php  } ?>>25 (default)</option>
                        <option value="50"<?php  if ($SiteOptions['CollageCovers'] == 50) { ?>selected="selected"<?php  } ?>>50</option>
                        <option value="100"<?php  if ($SiteOptions['CollageCovers'] == 100) { ?>selected="selected"<?php  } ?>>100</option>
                        <option value="1000000"<?php  if ($SiteOptions['CollageCovers'] == 1000000) { ?>selected="selected"<?php  } ?>>All</option>
                        <option value="0"<?php  if (($SiteOptions['CollageCovers'] === 0) || (!isset($SiteOptions['CollageCovers']) && $SiteOptions['HideCollage'])) { ?>selected="selected"<?php  } ?>>None</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Split Torrents by Days</strong></td>
                <td>
                    <input type="checkbox" name="splitbydays" id="splitbydays" <?php  if (!empty($SiteOptions['SplitByDays'])) { ?>checked="checked"<?php  } ?> />
                    <label for="splitbydays">display new day header in browse torrents list</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Category list in torrent search</strong></td>
                <td>
                    <select name="hidecats" id="hidecats">
                        <option value="0"<?php  if ($SiteOptions['HideCats'] == 0) { ?>selected="selected"<?php  } ?>>Open by default.</option>
                        <option value="1"<?php  if ($SiteOptions['HideCats'] == 1) { ?>selected="selected"<?php  } ?>>Closed by default.</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Tag list in torrent search</strong></td>
                <td>
                    <select name="showtags" id="showtags">
                        <option value="1"<?php  if ($SiteOptions['ShowTags'] == 1) { ?>selected="selected"<?php  } ?>>Open by default.</option>
                        <option value="0"<?php  if ($SiteOptions['ShowTags'] == 0) { ?>selected="selected"<?php  } ?>>Closed by default.</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Tags in lists</strong></td>
                <td>
                    <input type="checkbox" name="hidetagsinlists" id="hidetagsinlists" <?php  if (!empty($SiteOptions['HideTagsInLists'])) { ?>checked="checked"<?php  } ?> />
                    <label for="hidetagsinlists">Hide tags in lists (show filename instead)</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Max Tags in lists</strong></td>
                <td> <?php  //if (!isset($SiteOptions['MaxTags'])) $SiteOptions['MaxTags']=20; ?>
                    <input type="text" name="maxtags" id="maxtags" size="8" value="<?=((int) $SiteOptions['MaxTags'])?>" />
                    <label for="maxtags">The maximum number of tags to show in a torrent list</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Hover info window</strong></td>
                <td>
                    <input type="checkbox" name="hidefloatinfo" id="hidefloatinfo" <?php  if (!empty($SiteOptions['HideFloat'])) { ?>checked="checked"<?php  } ?> />
                    <label for="hidetagsinlists">Hide floating info window on browse torrents page</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>External link behaviour</strong></td>
                <td>
                    <input type="checkbox" name="forcelinks" id="forcelinks" <?php  if (empty($SiteOptions['NotForceLinks'])) { ?>checked="checked"<?php  } ?> />
                    <label for="forcelinks">Force external links to open in a new page</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Accept PM's</strong></td>
                <td>
                    <input type="radio" name="blockPMs" id="blockPMs" value="0" <?php  if (empty($LoggedUser['BlockPMs'])||$LoggedUser['BlockPMs']==0) { ?>checked="checked"<?php  } ?> />
                    <label>All (except blocks)</label><br/>
                    <input type="radio" name="blockPMs" id="blockPMs" value="1" <?php  if ($LoggedUser['BlockPMs']==1) { ?>checked="checked"<?php  } ?> />
                    <label>Friends only</label><br/>
                    <input type="radio" name="blockPMs" id="blockPMs" value="2"  <?php  if ($LoggedUser['BlockPMs']==2) { ?>checked="checked"<?php  } ?> />
                    <label>Staff only</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Comments PM</strong></td>
                <td>
                    <input type="checkbox" name="commentsnotify" id="commentsnotify" <?php  if (!empty($LoggedUser['CommentsNotify'])) { ?>checked="checked"<?php  } ?> />
                    <label for="commentsnotify">Notify me by PM when I receive a comment on one of my torrents</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Subscription</strong></td>
                <td>
                    <input type="checkbox" name="autosubscribe" id="autosubscribe" <?php  if (!empty($SiteOptions['AutoSubscribe'])) { ?>checked="checked"<?php  } ?> />
                    <label for="autosubscribe">Subscribe to topics when posting</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Page Titles</strong></td>
                <td>
                    <input type="checkbox" name="shortpagetitles" id="shortpagetitles" <?php  if (!empty($SiteOptions['ShortTitles'])) { ?>checked="checked"<?php  } ?> />
                    <label for="shortpagetitles">Use short page titles (ie. instead of Forums > Forum-name > Thread-title use just Thread-Title)</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Forum topics</strong></td>
                <td>
                    <input type="checkbox" name="disablelatesttopics" id="disablelatesttopics" onclick="SetLatestTopicsInterface();"
                                <?php  if (!empty($SiteOptions['DisableLatestTopics'])) { ?>checked="checked"<?php  } ?> />
                    <label for="disablelatesttopics">Disable latest forum topics</label>
                    <?php   // okay lets get the actual names of the excluded forums...
                        if ( is_array($ExcludeForums)) {
                            $ExcludedForumNames = $Cache->get_value('excluded_forum_names');
                            if ($ExcludedForumNames===false) {
                                $DB->query("SELECT Name FROM forums WHERE ID IN (". implode(",", $ExcludeForums) .")");
                                $ExclForumNames = $DB->collect('Name');
                                $ExcludedForumNames = count($ExclForumNames)>1?'forums ':'forum ';
                                $LastFName = array_pop($ExclForumNames);
                                if (count($ExclForumNames)>0) {
                                    $ExcludedForumNames .= implode(", ", $ExclForumNames) . " and $LastFName";
                                } else {
                                    $ExcludedForumNames .= $LastFName;
                                }
                                $Cache->cache_value('excluded_forum_names', $ExcludedForumNames, 3600*24);
                            }
                    ?>
                            <br/>
                            <input type="checkbox" name="showgames" id="showgames" <?php
                                if (!empty($SiteOptions['ShowGames'])) { ?>checked="checked"<?php  };
                                if (!empty($SiteOptions['DisableLatestTopics'])) { ?>disabled="disabled"<?php  }    ?> />
                            <label for="showgames">show <?=$ExcludedForumNames?> in latest forum topics</label>
                    <?php
                        }
                    ?>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Latest Shows</strong></td>
                <td>
                    <input type="checkbox" name="disablelatestshows" id="disablelatestshows" onclick="SetLatestShowsInterface();"
                                <?php  if (!empty($SiteOptions['DisableLatestShows'])) { ?>checked="checked"<?php  } ?> />
                    <label for="disablelatestshows">Disable latest shows</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>User Torrents</strong></td>
                <td>
                    <input type="checkbox" name="showusertorrents" id="showusertorrents" <?php  if (empty($SiteOptions['HideUserTorrents']) || $SiteOptions['HideUserTorrents']==0) { ?>checked="checked"<?php  } ?> />
                    <label for="showusertorrents">Show users uploaded torrents on user page (if allowed by that users paranoia settings)</label>
                </td>
            </tr>
                  <tr>
                <td class="label"><strong>Smileys</strong></td>
                <td>
                    <input type="checkbox" name="disablesmileys" id="disablesmileys" <?php  if (!empty($SiteOptions['DisableSmileys'])) { ?>checked="checked"<?php  } ?> />
                    <label for="disablesmileys">Disable smileys</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Avatars</strong></td>
                <td>
                    <input type="checkbox" name="disableavatars" id="disableavatars" <?php  if (!empty($SiteOptions['DisableAvatars'])) { ?>checked="checked"<?php  } ?> />
                    <label for="disableavatars">Disable avatars (disabling avatars also hides user badges)</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Signatures</strong></td>
                <td>
                    <input type="checkbox" name="disablesignatures" id="disablesignatures" <?php  if (!empty($SiteOptions['DisableSignatures'])) { ?>checked="checked"<?php  } ?> />
                    <label for="disablesignatures">Disable Signatures</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Download torrents as text files</strong></td>
                <td>
                    <input type="checkbox" name="downloadalt" id="downloadalt" <?php  if ($DownloadAlt) { ?>checked="checked"<?php  } ?> />
                    <label for="downloadalt">For users whose ISP block the downloading of torrent files</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Use the SSL tracker</strong></td>
                <td>
                    <input type="checkbox" name="ssltracker" id="ssltracker" <?php  if ($SSLTracker) { ?>checked="checked"<?php  } ?> />
                    <label for="ssltracker">Use the SSL tracker when downloading torrent files</label>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Unseeded torrent alerts</strong></td>
                <td>
                    <input type="checkbox" name="unseededalerts" id="unseededalerts" <?=checked($UnseededAlerts)?> />
                    <label for="unseededalerts">Receive a PM alert before your uploads are deleted for being unseeded</label>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="right">
                    <input type="submit" value="Save Profile" title="Save all changes" />
                </td>
            </tr>
            <tr class="colhead">
                <td colspan="2">
                    <strong>User info</strong>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Avatar URL</strong></td>
                <td>
                    <input class="long" type="text" name="avatar" id="avatar" value="<?=display_str($Avatar)?>" onchange="SecureMedia(this.value,this.id);" />
                    <p class="min_padding">Maximum Size: <?=$MaxAvatarWidth?>x<?=$MaxAvatarHeight?> pixels and 1 MB</p>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Flag</strong></td>
                <td style="">
                    <span id="flag_image" >
                        <?php  if ($flag && $flag != '??') { ?>
                            <img src="/static/common/flags/64/<?=$flag?>.png" />
                        <?php  } ?>
                    </span>
                    <div style="display:inline-block;vertical-align: top;">
                        <select id="flag" name="flag" onchange="change_flag();" style="margin-top: 25px">
                            <option value="" <?=($flag == '' || $flag == '??') ? 'selected="selected"' : '';?>>none</option>
                   <?php        foreach ($flags as $value) {
                                $value = substr($value, 0, strlen($value)-4  ); // remove .png extension
                    ?>
                            <option value="<?=display_str($value)?>" <?=($flag == $value) ? 'selected="selected"' : '';?>><?=$value?></option>
                    <?php       }  ?>
                        </select>
                    </div>
                </td>
            </tr>

<?php       if ( check_perms('site_set_language') ) {  ?>

            <tr>
                <td class="label"><strong>Language(s)</strong></td>
                <td>
<?php

                $Userlangs = $Cache->get_value('user_langs_' .$UserID);
                if ($Userlangs===false) {
                    $DB->query("SELECT ul.LangID, l.code, l.flag_cc AS cc, l.language
                              FROM users_languages AS ul
                              JOIN languages AS l ON l.ID=ul.LangID
                             WHERE UserID=$UserID");
                    $Userlangs = $DB->to_array('LangID', MYSQL_ASSOC);
                    $Cache->cache_value('user_langs_'.$UserID, $Userlangs);
                }
                if ($Userlangs) {
?>
                    select language to remove it:<br/>
<?php
                    foreach ($Userlangs as $langresult) {
?>
                    <input type="checkbox" name="del_lang[]" value="<?=$langresult['LangID']?>" />
                        <img style="vertical-align: bottom" title="<?=$langresult['language']?>" alt="[<?=$langresult['code']?>]" src="http://<?=SITE_URL?>/static/common/flags/iso16/<?=$langresult['cc']?>.png" />
<?php
                    }
?>
                     <br/>
<?php
                }

                $SiteLanguages = $Cache->get_value('site_languages');
                if ($SiteLanguages===false) {
                    $DB->query("SELECT ID, language FROM languages WHERE active='1' ORDER BY language");
                    $SiteLanguages = $DB->to_array('ID', MYSQL_ASSOC);
                    $Cache->cache_value('site_languages', $SiteLanguages);
                }
?>
                    <div style="display:inline-block;vertical-align: top;">
                        add language:
                        <span id="lang_image">
                        </span>
                        <select id="new_lang" name="new_lang" onchange="change_lang_flag();" style="margin-top: 25px">
                            <option value="" selected="selected" >none</option>
                   <?php        foreach ($SiteLanguages as $key=>$value) {
                                if (!array_key_exists($key, $Userlangs)  ) { ?>
                                    <option value="<?=$key?>"><?=$value['language']?></option>
                   <?php            }
                            }  ?>
                        </select>
                    </div>
                     <br/>(only staff can see your selected language) <br/>
                </td>
            </tr>
<?php
        }
?>
            <tr>
                <td class="label"><strong>Email</strong></td>
                <td><input class="long" type="text" name="email" id="email" value="<?=display_str($Email)?>" />
                    <p class="min_padding">If changing this field you must enter your current password in the "Current password" field before saving your changes.</p>
                </td>
            </tr>

            <tr id="acc_2fa_tr">
                <td class="label" title="This will let you enable 2-Factor Auth for your <?=SITE_NAME?> account. The use of your 2FA client will be required whenever you login after enabling it."><strong>2-Factor Auth</strong></td>
                <td>
                <?php $TwoFASecret = empty($TwoFactor) ? $TwoFA->createSecret() : $TwoFactor; ?>
                <div class="field_div">
                    <?php if (!empty($TwoFactor)) { ?>
                    <p>2FA is enabled for this account with the following secret:</p>
                    <?php } ?>
                    <input type="text" size="80" name="twofasecret" id="twofasecret" value="<?=$TwoFASecret?>" readonly><br>
                    <img src="<?=$TwoFA->getQRCodeImageAsDataUri(SITE_NAME, $TwoFASecret)?>"><br/>
		    <?php if (!empty($TwoFactor)) { ?>
			<input type="text" size="80" maxlength="6" name="twofa" id="twofa" placeholder="Type 'REMOVE' here in all-caps to remove your 2FA configuration." />
                    <?php } else { ?>
	                    <input type="text" size="20" maxlength="6" name="twofa" id="twofa" placeholder="Verification Code">
        	            <p class="min_padding">To enable 2FA, scan the above QR code (or add the secret below it) to your 2FA client of choice, and enter a verification code it generates. Note that the verification code must not have expired when you save your profile.</p>
                    <?php } ?>
                	    <p class="min_padding">When setting up 2FA, you must enter your current password in the "Current password" field before saving your changes.</p>
                </div>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>IRC Key</strong></td>
                <td><input class="long" type="text" name="irckey" id="irckey" value="<?=display_str($IRCKey)?>" />
                    <p class="min_padding">This key will be used when authenticating with Muffit on the site's <a href="articles.php?topic=irc_nicks">IRC network.</a>
                        <br/>
                        <ul>
                            <li>This value is stored in plaintext and should not be your password.</li>
                            <li>IRC keys must be between 6 and 32 characters.</li>
                        </ul>
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="right">
                    <input type="submit" value="Save Profile" title="Save all changes" />
                </td>
            </tr>
            <tr class="colhead">
                <td colspan="2">
                    <strong>Change Password</strong>
                </td>
            </tr>
            <tr>
                <td class="label"><strong>Current Password</strong></td>
                <td><input class="long" type="password" name="cur_pass" id="cur_pass" value="" /></td>
            </tr>
            <tr>
                <td class="label"><strong>New Password</strong></td>
                <td><input class="long" type="password" name="new_pass_1" id="new_pass_1" value="" /></td>
            </tr>
            <tr>
                <td class="label"><strong>Re-type New Password</strong></td>
                <td><input class="long" type="password" name="new_pass_2" id="new_pass_2" value="" /></td>
            </tr>
            <tr>
                <td class="label"><strong>Reset Passkey</strong></td>
                <td>
                    <input type="checkbox" name="resetpasskey" />
                    <label for="ResetPasskey">Any active torrents must be downloaded again to continue leeching/seeding.</label>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="right">
                    <input type="submit" value="Save Profile" title="Save all changes"/>
                </td>
            </tr>
            <tr class="colhead">
                <td colspan="2">
                    <strong>User Profile</strong>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="box pad hidden" id="preview_info" style="text-align:left;"></div>
                    <div  class="" id="editor_info" >
                        <?php  $Text->display_bbcode_assistant("preview_message_info", get_permissions_advtags($UserID, unserialize($CustomPermissions),$Permissions )); ?>
                        <textarea id="preview_message_info" name="info" class="long" rows="8"><?=display_str($Info)?></textarea>
                    </div>
                    <input type="button" value="Toggle Preview" onclick="Preview_Toggle('info');" />
                </td>
            </tr>

            <tr>
                <td colspan="2"> &nbsp; </td>
            </tr>
            <tr class="colhead">
                <td colspan="2">
                    <strong>Signature </strong> &nbsp;(max <?=$MaxSigLength?> chars) <span style="text-decoration: underline">max total size</span> (all images and text combined) 1MB and <?=SIG_MAX_WIDTH?> px * <?=SIG_MAX_HEIGHT?> px
                </td>
            </tr>
            <tr>
                <?php
                $AdvancedTags = get_permissions_advtags($UserID, unserialize($CustomPermissions),$Permissions );
                ?>
                <td colspan="2">
<?php
        if ($MaxSigLength>0) {
 ?>
                    <div class="box pad hidden" id="preview_sig" style="text-align:left;"></div>
                    <div id="editor_sig" >
                        <?php  $Text->display_bbcode_assistant("preview_message_sig", $AdvancedTags); ?>
                        <textarea  id="preview_message_sig" name="signature" class="long"  rows="8"><?=display_str($Signature);?></textarea>
                    </div>
                    <input type="button" value="Toggle Preview" onclick="Preview_Toggle('sig');" />
 <?php
        } else {
 ?>
                    <div style="text-align:left;">
                        <?=$Text->full_format('You need to get promoted before you can have a signature. see the [url=/articles.php?topic=ranks][b]User Classes[/b][/url] article.', $AdvancedTags ) ?>
                    </div>
 <?php
        }
 ?>
                </td>
            </tr>

            <tr>
                <td colspan="2"> &nbsp; </td>
            </tr>
            <tr class="colhead">
                <td colspan="2">
                    <strong>Torrent Footer</strong> &nbsp; <span style="text-decoration: underline">max height</span> <?=TORRENT_SIG_MAX_HEIGHT?> px
                </td>
            </tr>
            <tr>
                <td colspan="2">
<?php
        if (check_perms('site_torrent_signature')) {
 ?>
                    <div class="box pad hidden" id="preview_torrentsig" style="text-align:left;"></div>
                    <div  class="" id="editor_torrentsig" >
                        <?php  $Text->display_bbcode_assistant("preview_message_torrentsig", get_permissions_advtags($UserID, unserialize($CustomPermissions),$Permissions )); ?>
                        <textarea  id="preview_message_torrentsig" name="torrentsignature" class="long"  rows="8"><?=display_str($TorrentSignature);?></textarea>
                    </div>
                    <input type="button" value="Toggle Preview" onclick="Preview_Toggle('torrentsig');" />
 <?php
        } else {
 ?>
                    <div style="text-align:left;">
                        <?=$Text->full_format('You need to get promoted before you can have a torrent footer. see the [url=/articles.php?topic=ranks][b]User Classes[/b][/url] article.', $AdvancedTags ) ?>
                    </div>
 <?php
        }
 ?>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="right">
                    <input type="submit" value="Save Profile" title="Save all changes" />
                </td>
            </tr>
            <tr id="paranoia" class="colhead">
                <td colspan="2">
                    <strong>Paranoia Settings</strong>
                </td>
            </tr>
            <tr>
                <td class="label">&nbsp;</td>
                <td>
                    <p><span class="warning">Note: Paranoia has nothing to do with your security on this site, the only thing affected by this setting is other users ability to see your site activity and taste.</span></p>
                    <p>Select the elements <strong>you want to show</strong> on your profile. For example, if you tick "Show count" for "Snatched", users will be able to see that you have snatched <?=number_format($Snatched)?> torrents. If you tick "Show list", they will be able to see the full list of torrents you've snatched.</p>
                    <p><span class="warning">Some information will still be available in the site log.</span></p>
                </td>
            </tr>
            <tr>
                <td class="label">Recent activity</td>
                <td>
                    <label><input type="checkbox" name="p_lastseen" <?=checked(!in_array('lastseen', $Paranoia))?>> Last seen</label>
                </td>
            </tr>
            <tr>
                <td class="label" title="Automatically check the 'Upload Anonymously' box when uploading new torrents">Uploading</td>
                <td>
                <label><input type="checkbox" name="p_uploadanon"<?=checked(in_array('uploadanon', $Paranoia))?> /> Upload anonymously by default</label>
                </td>
            </tr>            
            <tr>
                <td class="label">Preset</td>
                <td>
                    <button type="button" onClick="ParanoiaResetOff()">Show everything</button>
                    <button type="button" onClick="ParanoiaResetStats2()">Show all but snatches</button>
                    <button type="button" onClick="ParanoiaResetStats()">Show stats only</button>
                </td>
            </tr>
            <tr>
                <td class="label">Stats</td>
                <td>
<?php
$UploadChecked = checked(!in_array('uploaded', $Paranoia));
$DownloadChecked = checked(!in_array('downloaded', $Paranoia));
$RatioChecked = checked(!in_array('ratio', $Paranoia));
$SeedsizeChecked = checked(!in_array('seedsize', $Paranoia));
?>
                    <label><input type="checkbox" name="p_uploaded" onChange="AlterParanoia()"<?=$UploadChecked?> /> Uploaded</label>&nbsp;&nbsp;
                    <?php if (check_perms('site_downloaded') ) { ?>
                    <label><input type="checkbox" name="p_downloaded" onChange="AlterParanoia()"<?=$DownloadChecked?> /> Downloaded</label>&nbsp;&nbsp;
                    <?php } ?>
                    <?php if (check_perms('site_ratio') ) { ?>
                    <label><input type="checkbox" name="p_ratio" onChange="AlterParanoia()"<?=$RatioChecked?> /> Ratio</label>
                    <?php } ?>
                    <label><input type="checkbox" name="p_seedsize" onChange="AlterParanoia()"<?=$SeedsizeChecked?> /> Seeding Size</label>
                </td>
            </tr>
            <tr>
                <td class="label">Torrent comments</td>
                <td>
<?php  display_paranoia('torrentcomments'); ?>
                </td>
            </tr>
            <tr>
                <td class="label">Collages started</td>
                <td>
<?php  display_paranoia('collages'); ?>
                </td>
            </tr>
            <tr>
                <td class="label">Collages contributed to</td>
                <td>
<?php  display_paranoia('collagecontribs'); ?>
                </td>
            </tr>
                <td class="label">Requests filled</td>
                <td>
<?php
$RequestsFilledCountChecked = checked(!in_array('requestsfilled_count', $Paranoia));
$RequestsFilledBountyChecked = checked(!in_array('requestsfilled_bounty', $Paranoia));
$RequestsFilledListChecked = checked(!in_array('requestsfilled_list', $Paranoia));
?>
                    <label><input type="checkbox" name="p_requestsfilled_count" onChange="AlterParanoia()" <?=$RequestsFilledCountChecked?> /> Show count</label>&nbsp;&nbsp;
                    <label><input type="checkbox" name="p_requestsfilled_bounty" onChange="AlterParanoia()" <?=$RequestsFilledBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
                    <label><input type="checkbox" name="p_requestsfilled_list" onChange="AlterParanoia()" <?=$RequestsFilledListChecked?> /> Show list</label>
                </td>
            </tr>
                <td class="label">Requests voted</td>
                <td>
<?php
$RequestsVotedCountChecked = checked(!in_array('requestsvoted_count', $Paranoia));
$RequestsVotedBountyChecked = checked(!in_array('requestsvoted_bounty', $Paranoia));
$RequestsVotedListChecked = checked(!in_array('requestsvoted_list', $Paranoia));
?>
                    <label><input type="checkbox" name="p_requestsvoted_count" onChange="AlterParanoia()" <?=$RequestsVotedCountChecked?> /> Show count</label>&nbsp;&nbsp;
                    <label><input type="checkbox" name="p_requestsvoted_bounty" onChange="AlterParanoia()" <?=$RequestsVotedBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
                    <label><input type="checkbox" name="p_requestsvoted_list" onChange="AlterParanoia()" <?=$RequestsVotedListChecked?> /> Show list</label>
                </td>
            </tr>
            <tr>
                <td class="label" title="tags you have added/voted on">Tags</td>
                <td>
<?php  display_paranoia('tags'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="uploaded torrents">Uploaded</td>
                <td>
<?php  display_paranoia('uploads'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="torrents you are currently seeding">Seeding</td>
                <td>
<?php  display_paranoia('seeding'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="torrents you are currently leeching">Leeching</td>
                <td>
<?php  display_paranoia('leeching'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="torrents you have downloaded 100% of">Snatched</td>
                <td>
<?php  display_paranoia('snatched'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="torrent files you have downloaded">Grabbed</td>
                <td>
<?php  display_paranoia('grabbed'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="shows you have rated">Rated</td>
                <td>
<?php  display_paranoia('rated'); ?>
                </td>
            </tr>
            <tr>
                <td class="label" title="shows you have follow">Following</td>
                <td>
<?php  display_paranoia('follow'); ?>
                </td>
            </tr>                        
            <tr>
                <td class="label">Miscellaneous</td>
                <td>
                <?php if (check_perms('site_ratio') ) { ?>
                    <label><input type="checkbox" name="p_requiredratio" <?=checked(!in_array('requiredratio', $Paranoia))?>> Required ratio</label><br />
                <?php } ?>                    
<?php
$DB->query("SELECT COUNT(UserID) FROM users_info WHERE Inviter='$UserID'");
list($Invited) = $DB->next_record();
?>
                   <label><input type="checkbox" name="p_invitedcount" <?=checked(!in_array('invitedcount', $Paranoia))?>> Number of users invited</label>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="right">
                    <input type="submit" value="Save Profile" title="Save all changes" />
                </td>
            </tr>
        </table>
    </form>
</div>
<?php
show_footer();
