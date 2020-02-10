<?php
if (!check_perms('admin_manage_site_options')) {
    error(403);
}

show_header('Manage Site Options');

/*
 * Below is a template for creating option sections:

                <div class="site_option">
                    <div class="input-label"></div>
                    <input type="checkbox" name="" <?=selected('', 'true', 'checked', $SiteOptions)?>/>
                    <input type="text" title="" name="" size="5" value="<?=$SiteOptions['']?>" />
                </div>
                <div class="clear"></div>

 */
?>


<div class="thin">
    <div class="head">
        Site Configuration
    </div>
    <div class="box">
        <div style="margin: 20px;">
            <form action="tools.php" method="post">
                <input type="hidden" name="action" value="take_site_options" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />

                <div class="site_option">
                    <div class="input-label">Page Request Logging:</div>
                    <select name="FullLogging">
                        <option value="0" <?=selected('FullLogging', 0, 'selected', $SiteOptions)?>>Off</option>
                        <option value="1" <?=selected('FullLogging', 1, 'selected', $SiteOptions)?>>Only user.php</option>
                        <option value="2" <?=selected('FullLogging', 2, 'selected', $SiteOptions)?>>Nearly All (excludes some common known ajax calls)</option>
                        <option value="3" <?=selected('FullLogging', 3, 'selected', $SiteOptions)?>>Absolutely All</option>
                    </select>
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label"> <?php  if (!$SiteOptions['SitewideFreeleechMode'] == "timed") echo "Set ";?>Sitewide Freeleech Until (Y-M-D H:M:S)</div>
                    <?php  if ($SiteOptions['SitewideFreeleechMode'] == 'timed') {
			$SWFL_Until = strtotime($SiteOptions['SitewideFreeleechTime']);
                        $SWFL_Until = ($SWFL_Until >= time()) ? $SWFL_Until : time();
                        echo date('Y-m-d H:i:s', strtotime($SiteOptions['SitewideFreeleechTime']) - (int) $LoggedUser['TimeOffset']);
                        echo "  (". time_diff($SiteOptions['SitewideFreeleechTime']) ." left.)";
                    } else {
                        $SWFL_Until = time();
                    } ?>
                    <input type="text" title="enter the time the sitewide freeleech should expire" name="SitewideFreeleechTime" size="18" value="<?=date('Y-m-d H:i:s', $SWFL_Until - (int) $LoggedUser['TimeOffset'])?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Freeleech Mode</div>
                    <select name="SitewideFreeleechMode">
                        <option value="on"    <?=selected('SitewideFreeleechMode', 'on', 'selected', $SiteOptions)?>>On</option>
                        <option value="timed" <?=selected('SitewideFreeleechMode', 'timed', 'selected', $SiteOptions)?>>Timed</option>
                        <option value="off"   <?=selected('SitewideFreeleechMode', 'off', 'selected', $SiteOptions)?>>Off</option>
                    </select>
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label"> <?php  if (!$SiteOptions['SitewideDoubleseedMode'] == "timed") echo "Set ";?>Sitewide Doubleseed Until (Y-M-D H:M:S)</div>
                    <?php  if ($SiteOptions['SitewideDoubleseedMode'] == 'timed') {
			$SWDS_Until = strtotime($SiteOptions['SitewideDoubleseedTime']);
                        $SWDS_Until = ($SWDS_Until >= time()) ? $SWDS_Until : time();
                        echo date('Y-m-d H:i:s', strtotime($SiteOptions['SitewideDoubleseedTime']) - (int) $LoggedUser['TimeOffset']);
                        echo "  (". time_diff($SiteOptions['SitewideDoubleseedTime']) ." left.)";
                    } else {
                        $SWDS_Until = time();
                    } ?>
                    <input type="text" title="enter the time the sitewide soubleseed should expire" name="SitewideDoubleseedTime" size="18" value="<?=date('Y-m-d H:i:s', $SWDS_Until - (int) $LoggedUser['TimeOffset'])?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Doubleseed Mode</div>
                    <select name="SitewideDoubleseedMode">
                        <option value="on"    <?=selected('SitewideDoubleseedMode', 'on', 'selected', $SiteOptions)?>>On</option>
                        <option value="timed" <?=selected('SitewideDoubleseedMode', 'timed', 'selected', $SiteOptions)?>>Timed</option>
                        <option value="off"   <?=selected('SitewideDoubleseedMode', 'off', 'selected', $SiteOptions)?>>Off</option>
                    </select>
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">MFD Fix Time</div>
                    <input type="text" title="Number of hours users are given to fix thier torrent" name="ReviewHours" size="5" value="<?=$SiteOptions['ReviewHours']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Auto Delete MFD Torrents</div>
                    <input type="checkbox" name="AutoDelete" <?=selected('AutoDelete', 'true', 'checked', $SiteOptions)?>/>
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">Speedrecord keep time</div>
                    <input type="text" title="Keep all speed records for this length of time (mins)" name="DeleteRecordsMins" size="5" value="<?=$SiteOptions['DeleteRecordsMins']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Speedcheat threshold</div>
                    <input type="text" title="Do not automatically delete speedrecords faster than this (bytes/s)" name="KeepSpeed" size="5" value="<?=$SiteOptions['KeepSpeed']?>" />
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">Default Avatar Width</div>
                    <input type="text" title="enter the width for the site default avatar" name="AvatarWidth" size="5" value="<?=$SiteOptions['AvatarWidth']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Default Avatar Height</div>
                    <input type="text" title="enter the height for the site default avatar" name="AvatarHeight" size="5" value="<?=$SiteOptions['AvatarHeight']?>" />
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">Initial Upload Credit (MB)</div>
                    <input type="text" title="enter the initial upload credit in MB" name="UsersStartingUpload" size="5" value="<?=$SiteOptions['UsersStartingUpload']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Initial Invites</div>
                    <input type="text" title="enter the number of initial invites" name="UsersStartingInvites" size="5" value="<?=$SiteOptions['UsersStartingInvites']?>" />
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">Initial Personal Freeleech (days)</div>
                    <input type="text" title="enter the number of days of initial personal freeleech" name="UsersStartingPFLDays" size="5" value="<?=$SiteOptions['UsersStartingPFLDays']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Initial Freeleech/Doubleseed Tokens</div>
                    <input type="text" title="enter the number of initial FL Tokens" name="UsersStartingFLTokens" size="5" value="<?=$SiteOptions['UsersStartingFLTokens']?>" />
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">Chevereto Imagehost URL</div>
                    <input type="text" title="enter the URL of your chevereto imagehost" name="ImagehostURL" size="30" value="<?=$SiteOptions['ImagehostURL']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Chevereto Imagehost API Key</div>
                    <input type="text" title="enter the API key of your chevereto imagehost" name="ImagehostKey" size="34" value="<?=$SiteOptions['ImagehostKey']?>" />
                </div>
                <div class="clear"></div>

                <div class="site_option">
                    <div class="input-label">Minimum length of tags</div>
                    <input type="text" title="Minimum length of tags" name="MinTagLength" size="5" value="<?=$SiteOptions['MinTagLength']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">Maximum length of tags</div>
                    <input type="text" title="Maximum length of tags" name="MaxTagLength" size="5" value="<?=$SiteOptions['MaxTagLength']?>" />
                </div>
                <div class="clear"></div>
                <div class="site_option">
                    <div class="input-label">HnR value in hours for an Episode</div>
                    <input type="text" title="HnR value in hours" name="HnR" size="5" value="<?=$SiteOptions['HnR']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">HnR value in hours for a Season</div>
                    <input type="text" title="HnR value in hours" name="HnRSeason" size="5" value="<?=$SiteOptions['HnRSeason']?>" />
                </div>
                <div class="site_option">
                    <div class="input-label">HnR Threshold for leech enable/disable</div>
                    <input type="text" title="HnR number" name="HnRThreshold" size="5" value="<?=$SiteOptions['HnRThreshold']?>" />
                </div>                                 
                <div class="site_option">
                    <div class="input-label">Premiered Shows Weight</div>
                    <input type="text" title="Premiered Shows Weight" name="TVMazeWeight" size="5" value="<?=$SiteOptions['TVMazeWeight']?>" />
                </div>                
                <div class="site_option">
                    <div class="input-label">Reaper Threshold</div>
                    <input type="text" title="Reap after # of days" name="ReaperThreshold" size="5" value="<?=$SiteOptions['ReaperThreshold']?>" />
                </div>                                 
                <div class="site_option">
                    <div class="input-label">Invite Buy Threshold</div>
                    <select title="Set minimum class which can buy an invite in black market" name="InviteBuyThreshold">
<?php
        foreach ($ClassLevels as $CurClass) {
            if ($SiteOptions['InviteBuyThreshold']==$CurClass['Level']) { $Selected='selected="selected"'; } else { $Selected=""; }
?>
                        <option value="<?=$CurClass['Level']?>" <?=$Selected?>><?=$CurClass['Name'].' ('.$CurClass['Level'].')'?></option>
<?php 		} ?>
                    </select>
                </div> 
                              
                <div class="clear"></div>
                <div>
                    <input type="submit" value="Save Changes" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php
show_footer();
