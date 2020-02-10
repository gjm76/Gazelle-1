<?php
include(SERVER_ROOT . '/common/toolbox.php');
show_header('Staff Tools');
?>
<div class="permissions">
    <div class="permission_container">
            <div class="head">Managers</div>
        <table>
<?php  foreach($Toolbox as $Tool) {
            list($ToolName, $ToolAction, $ToolPermission) = $Tool;
            if (check_perms($ToolPermission)) { ?>
            <tr><td><a href="tools.php?action=<?=$ToolAction?>"><?=$ToolName?></a></td></tr>
<?php       } ?>
<?php  } if (check_perms('users_groups')) { ?>
            <tr><td><a href="groups.php">User Groups</a></td></tr>
<?php  } ?>
        </table>
    </div>
    <div class="permission_container">
            <div class="head">Data</div>
        <table>


<?php if (check_perms('admin_donor_addresses')) { ?>
            <tr><td><a href="tools.php?action=btc_address_input" title="Input freshly generated bitcoin addresses for users to donate to">Bitcoin addresses</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_data_viewer')) { ?>
            <tr><td><a href="tools.php?action=data_viewer">Data Viewer</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_donor_drives')) { ?>
            <tr><td><a href="tools.php?action=donation_drives" title="Manage Donation Drives">Donation Drives</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_donor_log')) { ?>
            <tr><td><a href="tools.php?action=donation_log" title="View bitcoin donation log">Donation Log</a></td></tr>
<?php } ?>

<?php if (check_perms('site_view_flow')) { ?>            
            <tr><td><a href="tools.php?action=economic_stats">Economic Stats</a></td></tr>
<?php } ?>

<?php if (check_perms('site_view_flow')) { ?>
            <tr><td><a href="tools.php?action=hnr_pool">HnR Pool</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_invites')) { ?>
            <tr><td><a href="tools.php?action=invite_pool">Invite Pool</a></td></tr>
<?php } ?>

<?php if (check_perms('site_debug')) { ?>
            <tr><td><a href="tools.php?action=opcode_stats">Opcode Stats</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_ips') && check_perms('users_view_email')) { ?>
            <tr><td><a href="tools.php?action=ratings">Recent Rating Votes</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_ips') && check_perms('users_view_email')) { ?>
            <tr><td><a href="tools.php?action=registration_log">Registration Log</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_manage_site_options')) { ?>
            <tr><td><a href="tools.php?action=page_log">Page Logs</a></td></tr>
<?php } ?>

<?php if (check_perms('site_debug')) { ?>
            <tr><td><a href="tools.php?action=service_stats">Service Stats</a></td></tr>
<?php } ?>

<?php  if (check_perms('admin_manage_permissions')) { ?>
            <tr><td><a href="tools.php?action=special_users">Special Users</a></td></tr>
<?php } ?>

<?php if (check_perms('site_view_flow')) { ?>
            <tr><td><a href="tools.php?action=torrent_stats">Torrent Stats</a></td></tr>
<?php } ?>

<?php if (check_perms('site_view_flow')) { ?>
            <tr><td><a href="tools.php?action=upscale_pool">Upscale Pool</a></td></tr>
<?php } ?>

<?php if (check_perms('site_view_flow')) { ?>
            <tr><td><a href="tools.php?action=user_flow">User Flow</a></td></tr>
<?php } ?>

        </table>
    </div>
    <div class="permission_container">
            <div class="head">Misc</div>
        <table>

<?php if (check_perms('admin_clear_cache')) { ?>
            <tr><td><a href="tools.php?action=clear_cache">Clear/view a cache key</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_create_users')) { ?>
            <tr><td><a href="tools.php?action=create_user">Create User</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_ips')) { ?>
            <tr><td><a href="tools.php?action=dupe_ips">Duplicate IPs</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_ips')) { ?>
            <tr><td><a href="tools.php?action=flush_ip_history">Flush IP History</a></td></tr>
<?php } ?>

<?php if (check_perms('users_mod')) { ?>
            <tr><td><a href="tools.php?action=manipulate_tree">Manipulate Tree</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_ips')) { ?>
            <tr><td><a href="tools.php?action=dupe_ips_old">Old Duplicate IPs</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_update_geoip')) { ?>
            <tr><td><a href="tools.php?action=repair_geodist">Repair GeoIP Distribution</a></td></tr>
<?php } ?>

<?php if (check_perms('users_view_ips')) { ?>
            <tr><td><a href="tools.php?action=banned_ip_users">Returning Dupe IPs</a></td></tr>
<?php } ?>      

<?php if (check_perms('site_debug')) { ?>
            <tr><td><a href="schedule.php?auth=<?=$LoggedUser['AuthKey']?>" onClick="return confirm('Are you sure you want to run the site schedule (may take minutes to complete)?');">Schedule</a></td></tr>
<?php } ?>

<?php if (check_perms('admin_update_geoip')) { ?>
            <tr><td><a href="tools.php?action=update_geoip">Update GeoIP </a></td></tr>
<?php } ?>            
        </table>
    </div>
    <div class="clear"></div>
</div>
<?php
show_footer();
