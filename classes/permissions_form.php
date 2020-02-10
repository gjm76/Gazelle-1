<?php
/********************************************************************************
 ************ Permissions form ********************** user.php and tools.php ****
 ********************************************************************************
 ** This function is used to create both the class permissions form, and the   **
 ** user custom permissions form.					       **
 ********************************************************************************/

 $PermissionsArray = array(
    'site_force_anon_uploaders' =>'Hide all uploader info from this user',

    'site_leech' => 'Can leech (Does this work?).',
    'site_upload' => 'Upload torrent access.',
    'site_ratio' => 'Can view ratio.',
    'site_downloaded' => 'Can view downloaded.',
    'site_upload_anon' => 'Can upload anonymously',
    'site_edit_override_timelock' => 'Can edit own torrents after edit timelock',

    'use_templates' => 'Can use templates.',
    'make_private_templates' => 'Can make/delete private upload templates.',
    'make_public_templates' => 'Can make public upload templates.',
    'delete_any_template' => 'Can delete any upload templates.',

    'site_view_stats' => 'View the site stats page.',
    'site_stats_advanced' => 'View selected site stats.',

    'site_vote' => 'Can vote on requests.',
    'site_submit_requests' => 'Request create access.',
    'site_see_old_requests' => 'Can see old requests.',

    'site_staff_page' => 'Can see the Staff page.',

    'site_advanced_search' => 'Advanced search access.',
    'site_top10' => 'Top 10 access.',
    'site_advanced_top10' => 'Advanced Top 10 access.',
    'site_torrents_notify' => 'Notifications access.',

    'site_collages_create' => 'Can create collages.',
    'site_collages_delete' => 'Can delete collages.',
    'site_collages_subscribe' => 'Collage subscription access.',
    'site_collages_personal' => 'Can have a personal collage.',
    'site_collages_renamepersonal' => 'Can rename own personal collages.',

    'site_make_bookmarks' => 'Bookmarks access.',
    'site_can_invite_always' => 'Can invite past user limit.',
    'site_send_unlimited_invites' => 'Unlimited invites.',
    'site_advanced_tags' => 'Advanced bbcode tags.',
    'site_edit_own_posts' => 'Can edit own posts in forum after edit time limit.',

    'site_ignore_floodcheck' => 'Can post more often than floodcheck allows',
    'site_moderate_requests' => 'Request moderation access.',
    'site_moderate_forums' => 'Forum moderation access.',
    'site_admin_forums' => 'Forum administrator access.',
    'site_forums_double_post' => 'Can double post in the forums.',
    'site_view_flow' => 'Can view stats and data pools.',
    'site_view_full_log' => 'Can view old log entries.',
    'site_view_torrent_snatchlist' => 'Can view torrent snatchlists.',

    'site_view_torrent_peerlist' => 'Can view torrent peerlists.',

    'site_vote_tag' => 'Can vote on tags.',
    'site_add_tag' => 'Can add tags.',
    'site_add_multiple_tags' => 'Can add multiple tags at once.',
    'site_delete_tag' => 'Can delete tags.',
    'site_vote_tag_enhanced' => 'Has extra tag voting power (&plusmn;'. ENHANCED_VOTE_POWER . ')',
    'site_manage_tags' => 'Can manage official tag list and synonyms.',
    'site_convert_tags' => 'Can convert tags to synonyms.',

    'site_manage_shop' => 'Can manage shop.',
    'site_manage_badges' => 'Can manage badges.',
    'site_manage_awards' => 'Can manage awards schedule.',
    'site_reload_shows' => 'Can forcefully reload show cache.',

    'site_disable_ip_history' => 'Disable IP history.',
    'zip_downloader' => 'Download multiple torrents at once.',
    'site_debug' => 'Developer access.',
    'site_proxy_images' => 'Image proxy & Anti-Canary.',
    'site_search_many' => 'Can go past low limit of search results.',
    'site_give_specialgift' => 'Can give a special gift.',
    'site_play_slots' => 'Can play the slot machine.',
    'site_set_language' => 'Can set own user language(s) in settings',

    'site_torrent_signature' => 'Can set and use a torrent signature',

    'users_edit_usernames' => 'Can edit usernames.',
    'users_edit_ratio' => 'Can edit other\'s upload/download amounts.',
    'users_edit_own_ratio' => 'Can edit own upload/download amounts.',

    'users_edit_tokens' => 'Can edit other\'s FLTokens (Slots?)',
    'users_edit_own_tokens' => 'Can edit own FLTokens (Slots?)',
    'users_edit_pfl' => 'Can edit other\'s personal freeleech',
    'users_edit_own_pfl' => 'Can edit own personal freeleech',
    'users_edit_credits' => 'Can edit other\'s Bonus Credits',
    'users_edit_own_credits' => 'Can edit own Bonus Credits',

    'users_edit_titles' => 'Can edit titles.',
    'users_edit_avatars' => 'Can edit avatars.',
    'users_edit_badges' => 'Can edit other\s badges.',
    'users_edit_own_badges' => 'Can edit own badges.',

    'users_edit_invites' => 'Can edit invite numbers and cancel sent invites.',
    'users_edit_watch_hours' => 'Can edit contrib watch hours.',
    'users_edit_reset_keys' => 'Can reset passkey/authkey.',
    'users_edit_profiles' => 'Can edit anyone\'s profile.',
    'users_view_friends' => 'Can view anyone\'s friends.',
    'users_reset_own_keys' => 'Can reset own passkey/authkey.',
    'users_edit_password' => 'Can change passwords.',
    'users_edit_email' => 'Can change user email address.',

    'users_promote_below' => 'Can promote users to below current level.',
    'users_promote_to' => 'Can promote users up to current level.',
    'user_group_permissions'=> 'Can manage group permissions.',
    'users_view_donor' => 'Can view users my donations page.',
    'users_give_donor' => 'Can manually give donor status.',
    'users_warn' => 'Can warn users.',
    'users_disable_users' => 'Can disable users.',
    'users_disable_posts' => 'Can disable users\' posting rights.',
    'users_disable_any' => 'Can disable any users\' rights.',
    'users_delete_users' => 'Can delete users.',
    'users_view_invites' => 'Can view who user has invited.',
    'users_view_seedleech' => 'Can view what a user is seeding or leeching.',
    'users_view_bonuslog' => 'Can view bonus logs.',
    'users_view_uploaded' => 'Can view a user\'s uploads, regardless of privacy level.',
    'users_view_keys' => 'Can view passkeys.',
    'users_view_ips' => 'Can view IP addresses.',
    'users_view_email' => 'Can view email addresses.',
    'users_override_paranoia' => 'Can override paranoia.',
    'users_logout' => 'Can log users out (old?).',
    'users_make_invisible' => 'Can make users invisible.',
    'users_mod' => 'Basic moderator tools.',
    'users_groups' => 'Can use Group tools.',
    'users_manage_cheats' => 'Can manage watchlist.',
    'users_set_suppressconncheck' => 'Can set Suppress ConnCheck prompt for users.',
    'users_view_language' => 'Can view user language(s) on user profile',
    'users_view_anon_uploaders' => 'Can view anonymous uploaders names.',

     //-------------------------

    'torrents_edit_override_timelock' => 'Can edit own torrents after edit timelock.',
    'torrents_edit' => 'Can edit any torrent.',
    'torrents_scrape' => 'Can scrape info from TVMaze.',
    'torrents_review' => 'Can mark torrents for deletion.',
    'torrents_review_override' => 'Can overide ongoing marked for deletion process.',
    'torrents_review_manage' => 'Can set site options for marked for deletion list.',
    'torrents_download_override' => 'Can download torrents that are marked for deletion.',
    'torrents_delete' => 'Can delete torrents.',
    'torrents_delete_fast' => 'Can delete more than 3 torrents at a time.',
    'torrents_freeleech' => 'Can make torrents freeleech.',
    'torrents_doubleseed' => 'Can make torrents doubleseed.',
    'torrents_search_fast' => 'Rapid search (for scripts).',
    'torrents_hide_dnu' => 'Hide the Do Not Upload list by default.',
    'torrents_hide_imagehosts' => 'Hide the Imagehost Whitelist list by default.',

    'admin_manage_site_options' => 'Can manage site options',
    'admin_manage_parser' => 'Can manage parser',
    'admin_manage_networks' => 'Can manage networks',
    'admin_manage_no_showid' => 'Can view torrents with no ShowID',
    'admin_manage_reasons' => 'Can manage reasons',
    'admin_manage_languages' => 'Can manage the official site languages',
    'admin_email_blacklist' => 'Can manage the email blacklist',
    'admin_manage_cheats' => 'Can admin watchlist.',
    'admin_manage_categories' => 'Can manage categories.',
    'admin_manage_news' => 'Can manage news.',
    'admin_manage_articles' => 'Can manage articles',
    'admin_manage_blog' => 'Can manage blog.',
    'admin_manage_polls' => 'Can manage polls.',
    'admin_manage_forums' => 'Can manage forums (add/edit/delete).',
    'admin_manage_fls' => 'Can manage FLS.',
    'admin_reports' => 'Can access reports system.',
    'admin_advanced_user_search' => 'Can access advanced user search.',
    'admin_create_users' => 'Can create users through an administrative form.',
    'admin_donor_drives' => 'Can view and manage donation drives.',
    'admin_donor_log' => 'Can view and manage the donor log.',
    'admin_donor_addresses' => 'Can manage and enter new bitcoin addresses.',
    'admin_manage_ipbans' => 'Can manage IP bans.',
    'admin_dnu' => 'Can manage do not upload list.',
    'admin_imagehosts' => 'Can manage Imagehost Whitelist.',
    'admin_clear_cache' => 'Can clear cached.',
    'admin_whitelist' => 'Can manage the list of allowed clients.',
    'admin_manage_permissions' => 'Can edit permission classes/user permissions.',
    'admin_schedule' => 'Can run the site schedule.',
    'admin_login_watch' => 'Can manage login watch.',
    'admin_manage_wiki' => 'Can manage wiki access.',
    'admin_update_geoip' => 'Can update geoip data.',
    'admin_data_viewer' => 'Can access data viewer.',
    'admin_stealth_resolve' => 'Can stealth resolve.',
    'site_collages_manage' => 'Can manage any collage.',
    'site_collages_recover' => 'Can recover \'deleted\' collages.',
    'edit_unknowns' => 'Can edit unknown release information.',
    'forums_polls_create' => 'Can create polls in the forums.',
    'forums_polls_moderate' => 'Can feature and close polls.',
    'project_team' => 'Is part of the project team.'

 );

function permissions_form() { ?>
<div class="permissions">
    <div class="permission_container">
        <table>
            <tr>
                <td class="colhead">Site</td>
            </tr>
            <tr>
                <td>
                    <?php display_perm('site_force_anon_uploaders','Hide all uploader info from this user','Hide all uploader info from this user (forces uploader anonymity for untrusted users).'); ?>

                    <?php display_perm('site_leech','Can leech'); ?>
                    <?php display_perm('site_upload','Can upload'); ?>
                    <?php display_perm('site_ratio','Can view ratio'); ?>
                    <?php display_perm('site_downloaded','Can view downloaded'); ?>
                    <?php display_perm('site_upload_anon', 'Can upload anonymously'); ?>
                    <?php display_perm('site_edit_override_timelock', 'Can edit own torrents after edit timelock'); ?>

                    <?php display_perm('use_templates','Can use templates'); ?>
                    <?php display_perm('make_private_templates','Can make/delete private upload templates'); ?>
                    <?php display_perm('make_public_templates','Can make public upload templates'); ?>
                    <?php display_perm('delete_any_template','Can delete any upload templates'); ?>

                    <?php display_perm('site_view_stats' , 'View the site stats page'); ?>
                    <?php display_perm('site_stats_advanced', 'View selected site stats'); ?>

                    <?php display_perm('site_vote','Can vote on requests'); ?>
                    <?php display_perm('site_submit_requests','Can submit requests'); ?>
                    <?php display_perm('site_see_old_requests','Can see old requests'); ?>

                    <?php display_perm('site_staff_page','Can see the Staff page'); ?>

                    <?php display_perm('site_advanced_search','Can use advanced search'); ?>
                    <?php display_perm('site_top10','Can access top 10'); ?>
                    <?php display_perm('site_torrents_notify','Can access torrents notifications system'); ?>
                    <?php display_perm('site_collages_create','Can create collages'); ?>
                    <?php display_perm('site_collages_delete','Can delete collages'); ?>
                    <?php display_perm('site_collages_subscribe','Can access collage subscriptions'); ?>
                    <?php display_perm('site_collages_personal','Can have a personal collage'); ?>
                    <?php display_perm('site_collages_renamepersonal','Can rename own personal collages'); ?>
                    <?php display_perm('site_advanced_top10','Can access advanced top 10'); ?>
                    <?php display_perm('site_make_bookmarks','Can make bookmarks'); ?>
                    <?php display_perm('site_can_invite_always', 'Can invite users even when invites are closed'); ?>
                    <?php display_perm('site_send_unlimited_invites', 'Can send unlimited invites'); ?>
                    <?php display_perm('site_advanced_tags', 'Can use advanced bbcode tags'); ?>
                    <?php display_perm('site_edit_own_posts', 'Can edit own posts in forum after edit lock time limit'); ?>
                    <?php display_perm('site_ignore_floodcheck', 'Can post more often than floodcheck allows', 'Allows multiple posting immediately - no complaints if you double post!') ; ?>
                    <?php display_perm('site_moderate_requests', 'Can moderate any request'); ?>
                    <?php display_perm('forums_polls_create','Can create polls in the forums') ?>
                    <?php display_perm('forums_polls_moderate','Can feature and close polls') ?>
                    <?php display_perm('site_moderate_forums', 'Can moderate the forums', 'Can moderate the forums (lock/sticky/rename/move threads).'); ?>
                    <?php display_perm('site_admin_forums', 'Can administrate the forums','Can administrate the forums (merge/delete threads).'); ?>
                    <?php display_perm('site_view_flow', 'Can view site stats and data pools'); ?>
                    <?php display_perm('site_view_full_log', 'Can view the full site log'); ?>
                    <?php display_perm('site_view_torrent_snatchlist', 'Can view torrent snatchlists'); ?>
                    <?php display_perm('site_view_torrent_peerlist', 'Can view torrent peerlists'); ?>
                    <?php display_perm('site_vote_tag', 'Can vote on tags'); ?>
                    <?php display_perm('site_add_tag', 'Can add tags'); ?>
                    <?php display_perm('site_add_multiple_tags','Can add multiple tags at once'); ?>
                    <?php display_perm('site_delete_tag', 'Can delete tags'); ?>
                    <?php display_perm('site_vote_tag_enhanced', 'Has extra tag voting power (&plusmn;'. ENHANCED_VOTE_POWER . ')','extra tag voting power is defined in config'); ?>
                    <?php display_perm('site_disable_ip_history', 'Disable IP history'); ?>
                    <?php display_perm('zip_downloader', 'Download multiple torrents at once'); ?>
                    <?php display_perm('site_debug', 'View site debug tables'); ?>
                    <?php display_perm('site_proxy_images', 'Proxy images through the server'); ?>
                    <?php display_perm('site_search_many', 'Can go past low limit of search results'); ?>
                    <?php display_perm('site_collages_manage','Can manage/edit any collage'); ?>
                    <?php display_perm('site_collages_recover', 'Can recover \'deleted\' collages'); ?>
                    <?php display_perm('site_forums_double_post', 'Can double post in the forums'); ?>
                    <?php display_perm('project_team', 'Part of the project team'); ?>
                    <?php display_perm('site_give_specialgift', 'Can give a special gift.'); ?>
                    <?php display_perm('site_play_slots', 'Can play the slot machine'); ?>
                    <?php display_perm('site_set_language', 'Can set own user language(s)', 'Can set own user language(s) on settings page.'); ?>
                    <?php display_perm('site_torrent_signature', 'Can set and use a torrent signature'); ?>

                </td>
            </tr>
        </table>
    </div>
    <div class="permission_container">
        <table>
            <tr>
                <td class="colhead">Users</td>
            </tr>
            <tr>
                <td>
                    <?php display_perm('users_edit_usernames', 'Can edit usernames'); ?>
                    <?php display_perm('users_edit_ratio', 'Can edit other\'s upload/download amounts'); ?>
                    <?php display_perm('users_edit_own_ratio', 'Can edit own upload/download amounts'); ?>
                    <?php display_perm('users_edit_tokens', 'Can edit other\'s FLTokens (Slots?)'); ?>
                    <?php display_perm('users_edit_own_tokens', 'Can edit own FLTokens (Slots?)'); ?>
                    <?php display_perm('users_edit_pfl', 'Can edit other\'s personal freeleech'); ?>
                    <?php display_perm('users_edit_own_pfl', 'Can edit own personal freeleech'); ?>
                    <?php display_perm('users_edit_credits', 'Can edit other\'s Bonus Credits'); ?>
                    <?php display_perm('users_edit_own_credits', 'Can edit own Bonus Credits'); ?>
                    <?php display_perm('users_edit_titles', 'Can edit titles'); ?>
                    <?php display_perm('users_edit_avatars', 'Can edit avatars.'); ?>
                    <?php display_perm('users_edit_badges', 'Can edit other\'s badges'); ?>
                    <?php display_perm('users_edit_own_badges', 'Can edit own badges'); ?>
                    <?php display_perm('users_edit_invites', 'Can edit invite numbers and cancel sent invites'); ?>
                    <?php display_perm('users_edit_watch_hours', 'Can edit contrib watch hours'); ?>
                    <?php display_perm('users_edit_reset_keys', 'Can reset any passkey/authkey'); ?>
                    <?php display_perm('users_edit_profiles', 'Can edit anyone\'s profile'); ?>
                    <?php display_perm('users_view_friends', 'Can view anyone\'s friends'); ?>
                    <?php display_perm('users_reset_own_keys', 'Can reset own passkey/authkey'); ?>
                    <?php display_perm('users_edit_password', 'Can change password.'); ?>
                    <?php display_perm('users_edit_email', 'Can change user email address'); ?>
                    <?php display_perm('users_promote_below', 'Can promote users to below current level'); ?>
                    <?php display_perm('users_promote_to', 'Can promote users up to current level'); ?>
                    <?php display_perm('user_group_permissions', 'Can manage group permissions', 'Can change a users group permission setting.'); ?>
                    <?php display_perm('users_view_donor', 'Can view users my donations page','Can view detailed donation information for each user'); ?>
                    <?php display_perm('users_give_donor', 'Can give donor status','Can manually give donor status'); ?>
                    <?php display_perm('users_warn', 'Can warn users.'); ?>
                    <?php display_perm('users_disable_users', 'Can disable users'); ?>
                    <?php display_perm('users_disable_posts', 'Can disable users\' posting rights'); ?>
                    <?php display_perm('users_disable_any', 'Can disable any users\' rights'); ?>
                    <?php display_perm('users_delete_users', 'Can delete anyone\'s account'); ?>
                    <?php display_perm('users_view_invites', 'Can view who user has invited'); ?>
                    <?php display_perm('users_view_seedleech', 'Can view what a user is seeding or leeching'); ?>
                    <?php display_perm('users_view_bonuslog', 'Can view a users bonus logs'); ?>
                    <?php display_perm('users_view_uploaded', 'Can view a user\'s uploads, regardless of privacy level'); ?>
                    <?php display_perm('users_view_keys', 'Can view passkeys'); ?>
                    <?php display_perm('users_view_ips', 'Can view IP addresses'); ?>
                    <?php display_perm('users_view_email', 'Can view email addresses'); ?>
                    <?php display_perm('users_override_paranoia', 'Can override paranoia'); ?>
                    <?php display_perm('users_make_invisible', 'Can make users invisible'); ?>
                    <?php display_perm('users_logout', 'Can log users out'); ?>
                    <?php display_perm('users_mod', 'Can access basic moderator tools','Allows access to the user moderation panels'); ?>
                    <?php display_perm('users_admin_notes', 'Can edit Admin comment','To be used sparingly - staff can add notes via the submit panel'); ?>
                    <?php display_perm('users_groups', 'Can use Group tools'); ?>
                    <?php display_perm('users_manage_cheats', 'Can manage watchlist', 'Can add and remove users from watchlist, and view speed reports page'); ?>
                    <?php display_perm('users_set_suppressconncheck', 'Can set Suppress ConnCheck prompt for users', 'Suppress ConnCheck if set for a user stops any prompts in the header bar re: connectable status'); ?>
                    <?php display_perm('users_view_language', 'Can view user language(s) on user profile', 'Can view user language(s) on user profile - to other users they can only be seen on the staff page'); ?>
                    <?php display_perm('users_view_anon_uploaders', 'Can view anonymous uploaders names', 'Can view anonymous uploaders names - viewable in tooltip, and anon links to actual user'); ?>

                    <br/>*Everything is only applicable to users with the same or lower class level
                </td>
            </tr>
        </table>
    </div>
    <div class="permission_container">
        <table>
            <tr>
                <td class="colhead">Torrents</td>
            </tr>
            <tr>
                <td>
                    <?php display_perm('torrents_edit', 'Can edit any torrent'); ?>
                    <?php display_perm('torrents_scrape', 'Can scrape info from TVMaze'); ?>
                    <?php display_perm('torrents_review', 'Can mark torrents for deletion'); ?>
                    <?php display_perm('torrents_review_override', 'Can overide ongoing marked for deletion process'); ?>
                    <?php display_perm('torrents_review_manage', 'Can set site options for marked for deletion list'); ?>
                    <?php display_perm('torrents_download_override', 'Can download torrents that are marked for deletion'); ?>
                    <?php display_perm('torrents_delete', 'Can delete torrents'); ?>
                    <?php display_perm('torrents_delete_fast', 'Can delete more than 3 torrents at a time'); ?>
                    <?php display_perm('torrents_freeleech', 'Can make torrents freeleech'); ?>
                    <?php display_perm('torrents_doubleseed', 'Can make torrents doubleseed'); ?>
                    <?php display_perm('torrents_search_fast', 'Unlimit search frequency (for scripts)'); ?>
                    <?php display_perm('edit_unknowns', 'Can edit unknown release information'); ?>
                    <?php display_perm('site_add_logs', 'Can add logs to torrents after upload'); ?>
                    <?php display_perm('torrents_hide_dnu', 'Hide the do not upload list by default'); ?>
                    <?php display_perm('torrents_hide_imagehosts', 'Hide the imagehost whitelist by default'); ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="permission_container">
        <table>
            <tr>
                <td class="colhead">Administrative</td>
            </tr>
            <tr>
                <td>
                    <?php display_perm('admin_manage_site_options', 'Can manage site options'); ?>
                    <?php display_perm('admin_manage_parser', 'Can manage parser'); ?>
                    <?php display_perm('admin_manage_networks', 'Can manage networks'); ?>
                    <?php display_perm('admin_manage_no_showid', 'Can view torrents with no ShowID'); ?>
                    <?php display_perm('admin_manage_reasons', 'Can manage reasons'); ?>
                    <?php display_perm('admin_manage_languages', 'Can manage the official site languages'); ?>
                    <?php display_perm('admin_email_blacklist', 'Can manage the email blacklist'); ?>
                    <?php display_perm('admin_manage_cheats', 'Can admin watchlist.', 'Can change site options for watchlist'); ?>
                    <?php display_perm('admin_manage_categories', 'Can manage categories.'); ?>
                    <?php display_perm('admin_manage_news', 'Can manage news'); ?>
                    <?php display_perm('admin_manage_articles', 'Can manage articles'); ?>
                    <?php display_perm('admin_manage_blog', 'Can manage blog'); ?>
                    <?php display_perm('admin_manage_polls', 'Can manage polls'); ?>
                    <?php display_perm('admin_manage_forums', 'Can manage forums (add/edit/delete)'); ?>
                    <?php display_perm('admin_manage_fls', 'Can manage FLS'); ?>

                    <?php display_perm('site_manage_tags', 'Can manage official tag list and synonyms.'); ?>
                    <?php display_perm('site_convert_tags', 'Can convert tags to synonyms.'); ?>
                    <?php display_perm('site_manage_badges', 'Can manage badges.'); ?>
                    <?php display_perm('site_manage_awards', 'Can manage awards schedule.'); ?>
                    <?php display_perm('site_manage_shop', 'Can manage bonus shop items.'); ?>
                    <?php display_perm('site_reload_shows', 'Can forcefully reload show cache.'); ?>

                    <?php display_perm('admin_reports', 'Can access reports system'); ?>
                    <?php display_perm('admin_advanced_user_search', 'Can access advanced user search'); ?>
                    <?php display_perm('admin_create_users', 'Can create users through an administrative form'); ?>
                    <?php display_perm('admin_donor_drives', 'Can view and manage donation drives'); ?>
                    <?php display_perm('admin_donor_log', 'Can view and manage the donor log'); ?>
                    <?php display_perm('admin_donor_addresses', 'Can manage and enter new bitcoin addresses.'); ?>
                    <?php display_perm('admin_manage_ipbans', 'Can manage IP bans'); ?>
                    <?php display_perm('admin_dnu', 'Can manage do not upload list'); ?>
                    <?php display_perm('admin_imagehosts', 'Can manage imagehosts whitelist'); ?>
                    <?php display_perm('admin_clear_cache', 'Can clear cached pages'); ?>
                    <?php display_perm('admin_whitelist', 'Can manage the list of allowed clients.'); ?>
                    <?php display_perm('admin_manage_permissions', 'Can edit permission classes/user permissions.', 'Can edit all permissions and templates; user classes / group permissions / individual user permissions.'); ?>
                    <?php display_perm('admin_schedule', 'Can run the site schedule.'); ?>
                    <?php display_perm('admin_login_watch', 'Can manage login watch.'); ?>
                    <?php display_perm('admin_manage_wiki', 'Can manage wiki access.'); ?>
                    <?php display_perm('admin_update_geoip', 'Can update geoip data.'); ?>
                    <?php display_perm('admin_data_viewer', 'Can access data viewer.'); ?>
                    <?php display_perm('admin_stealth_resolve', 'Can stealth resolve.'); ?>

                </td>
            </tr>
        </table>
    </div>
    <div class="submit_container"><input type="submit" name="submit" value="Save Permission Class" /></div>
</div>
<?php }
