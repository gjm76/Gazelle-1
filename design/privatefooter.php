</div>
<div id="footer">
<script type="text/javascript">

<?php if ($LoggedUser['UseTooltipster'] == 0 || $LoggedUser['UseTooltipster'] === null) { ?>
    var useTooltipster = true;
<?php } else { ?>
    var useTooltipster = false;
<?php } ?>
</script>
<script src="<?=STATIC_SERVER?>functions/ttn.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/ttn.js')?>" type="text/javascript"></script>
    <p>
        Site and design &copy; <?=date("Y")?> <?=SITE_NAME?>
    </p>
    <?php if (!empty($LastActive)) { ?><p><a href="user.php?action=sessions" title="Manage Sessions">Last activity <?=time_diff($LastActive['LastUpdate'])?> from <?=$LastActive['IP']?>.</a></p><?php } ?>

    <?php
if (check_perms('users_mod')) {
        $Load = sys_getloadavg(); ?>
        <p>
                <strong>Time:</strong> <?=number_format(((microtime(true)-$ScriptStartTime)*1000),5)?> ms
                <strong>Used:</strong> <?=get_size(memory_get_usage(true))?>
                <strong>Load:</strong> <?=number_format($Load[0],2).' '.number_format($Load[1],2).' '.number_format($Load[2],2)?>
                <strong>Date:</strong> <?=time_diff(time(),2,false,false,1)  //date('M d Y, H:i')?>

        </p>
<?php } ?>

    <p> 
        <a style="margin-left:16px;vertical-align: top" href="feeds.php?feed=torrents_all&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : All Torrents" ><img src="<?=STATIC_SERVER?>/common/symbols/rss.svg" alt="RSS feed" /></a>
        <a style="margin-left:3px;" href="feeds.php?feed=torrents_all&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : All Torrents" >Torrents</a>

        <a style="margin-left:16px;vertical-align: top" href="feeds.php?feed=feed_news&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : News" ><img src="<?=STATIC_SERVER?>/common/symbols/rss.svg" alt="RSS feed" /></a>
        <a style="margin-left:3px;" href="feeds.php?feed=feed_news&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : News" >News</a>

        <a style="margin-left:16px;vertical-align: top" href="feeds.php?feed=torrents_bookmarks_t_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode(SITE_NAME.': Bookmarked Torrents')?>" title="<?=SITE_NAME?> : Bookmarked Torrents" ><img src="<?=STATIC_SERVER?>/common/symbols/rss.svg" alt="RSS feed" /></a>
        <a style="margin-left:3px;" href="feeds.php?feed=torrents_bookmarks_t_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode(SITE_NAME.': Bookmarked Torrents')?>" title="<?=SITE_NAME?> : Bookmarked Torrents" >Bookmarks</a>
<?php /* Hiding blog feed since we hide blogs
        <a style="margin-left:16px;vertical-align: top" href="feeds.php?feed=feed_blog&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : Blog" ><img src="<?=STATIC_SERVER?>/common/symbols/rss.svg" alt="RSS feed" /></a>
        <a style="margin-left:3px;" href="feeds.php?feed=feed_blog&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : Blog" >blog</a>
*/ ?>
        <a style="margin-left:16px;vertical-align: top" href="feeds.php?feed=torrents_notify_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : Torrent Notifications" ><img src="<?=STATIC_SERVER?>/common/symbols/rss.svg" alt="RSS feed" /></a>
        <a style="margin-left:3px;" href="feeds.php?feed=torrents_notify_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" title="<?=SITE_NAME?> : Torrent Notifications" >Notifications</a>

        <a style="margin-left:16px;vertical-align: top" href="articles.php?topic=rsshelp" title="<?=SITE_NAME?> : RSS Help" ><img src="<?=STATIC_SERVER?>/common/symbols/rss.svg" alt="RSS feed" /></a>
        <a style="margin-left:3px;" href="articles.php?topic=rsshelp" title="<?=SITE_NAME?> : RSS Help" >Help</a>

    </p>
    <p><a href="log.php">Site Logs</a></p>
</div>
<div id="footer_bottom">
</div>

<?php if (DEBUG_MODE || check_perms('site_debug')) {
    /*
     * Prevent var_dump from being clipped in debug info
    */
    ini_set('xdebug.var_display_max_depth',    -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data',     -1);
?>
    <!-- Begin Debugging -->
    <div id="site_debug">
<?php
$Debug->git_commit();
$Debug->flag_table();
$Debug->error_table();
$Debug->sphinx_table();
$Debug->query_table();
$Debug->cache_table();
$Debug->vars_table();
?>
    </div>
    <!-- End Debugging -->
<?php } ?>

</div>
<div id="lightbox" class="lightbox hidden"></div>
<div id="curtain" class="curtain hidden"></div>

<!-- Extra divs, for stylesheet developers to add imagery -->
<div id="extra1"><span></span></div>
<div id="extra2"><span></span></div>
<div id="extra3"><span></span></div>
<div id="extra4"><span></span></div>
<div id="extra5"><span></span></div>
<div id="extra6"><span></span></div>
</body>
</html>
