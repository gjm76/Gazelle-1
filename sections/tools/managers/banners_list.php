<?php
if (!check_perms('admin_imagehosts')) { error(403); }

include(SERVER_ROOT . '/sections/shows/functions.php');

show_header('Manage automatic banners');

// Pages
if (!empty($_GET['page']) && is_number($_GET['page'])) {
    $Page = $_GET['page'];
} else {
    $Page = 1;
}

$WHERE='';
$BannerSearch=$_REQUEST['search'];
if (!empty($BannerSearch)) {
    $WHERE = "WHERE Comment LIKE '%".db_string($BannerSearch)."%'";
}

$TVMazeTitle=$_REQUEST['tvmaze_search'];
if (!empty($TVMazeTitle)) {
    $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/singlesearch/shows?q=".urlencode($TVMazeTitle)));
    $TVMazeID     = $RawTVMazeInfo->id;
    $TVMazeTitle  = $RawTVMazeInfo->name;
    $TVMazePoster = secure_link($RawTVMazeInfo->image->medium);
    $TVMazeSynop  = $RawTVMazeInfo->summary;
}

list($Page,$Limit) = page_limit(25);

$DB->query("SELECT SQL_CALC_FOUND_ROWS
    b.ID,
    b.TVMazeID,
    b.BannerLink,
    b.Comment,
    b.UserID,
    um.Username,
    b.Time
    FROM torrents_banners as b
    LEFT JOIN users_main AS um ON um.ID=b.UserID
    $WHERE
    ORDER BY b.Time DESC
    LIMIT $Limit");

$Banners = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();

$Pages=get_pages($Page,$NumResults,25,9);


?>
<div class="thin">
<h2>Automatic Banners</h2>
<table>
    <tr class="head">
        <td colspan="6">Search Automatic Banner</td>
    </tr>
    <tr class="colhead">
        <td width="25%"><span title="Search Banners">
                    Search Banners</span></td>
        <td width="10%"><span>
                    Submit</span></td>
        <td width="25%"><span title="Search TVMaze for expisode info">
                    Search TVMaze</span></td>
        <td width="10%"><span>
                    Submit</span></td>
    </tr>
    <tr class="rowa">
    <form action="tools.php?action=automatic_banners" method="post">
        <input type="hidden" name="action" value="automatic_banners" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <td>
            <input class="medium"  type="text" name="search" value="<?=$BannerSearch?>" />
        </td>
        <td>
            <input type="submit" name="submit" value="Search" />
        </td>
    </form>
    <form action="tools.php?action=automatic_banners" method="post">
        <input type="hidden" name="action" value="automatic_banners" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <td>
            <input class="medium"  type="text" name="tvmaze_search" value="<?=$TVMazeTitle?>" />
        </td>
        <td>
            <input type="submit" name="submit" value="Search" />
        </td>
    </form>
</tr>
<?php if(!empty($TVMazeTitle)) { ?>
<tr class="rowb"><td colspan="4"></td></tr>
<tr class=rowa><td colspan="4"><h1><?=$TVMazeTitle?></h1></td></tr>
<tr class="rowa">
<td><img src="<?=$TVMazePoster?>" /></td>
<td colspan="3"><?=$TVMazeSynop?></td>
</tr>
<tr class="rowb"><td colspan="4"></td></tr>
<?php

    if(is_numeric($TVDBID)) {          
       $ch = curl_init();
       $timeout = 5;
       curl_setopt($ch, CURLOPT_URL, "http://thetvdb.com/api/2FB450F80319F388/series/$TVDBID/banners.xml");
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
       $tvdb_banners = curl_exec($ch);
       $tvdb_banners = new SimpleXMLElement($tvdb_banners);
       curl_close($ch);
    }

    $banner_count=0;
    foreach($tvdb_banners as $tvdb_banner) {
        if($tvdb_banner->BannerType=="series") {
            if(($banner_count++ % 4) == 0)echo '<tr class="rowa">'; 
            $TVDB_BANNER='http://thetvdb.com/banners/'.$tvdb_banner->BannerPath; // no support for https ?>
            <td class="banner_button"><form action="tools.php" method="post">
                <input type="hidden" name="action" value="ba_alter" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="tvmaze" value="<?=$TVMazeID?>"/>
                <input type="hidden" name="comment" value="<?=$TVMazeTitle?>"/>
                <input type="hidden" name="tvdb_banner" value="<?=$TVDB_BANNER?>"/>
                <button type="submit" name="submit" value="Create" style="border: 0; background: transparent;" title="Create">
                    <img style="width: 230px" src="<?=$TVDB_BANNER?>" />
                </button>
            </form></td>
<?php       if(($banner_count % 4) == 0)echo '<tr>';
            if($banner_count >= 12) break;
        }
    }

    $banner_gap=$banner_count %4;
    if($banner_gap != 0) echo '<td colspan="'.(4-$banner_gap).'"></td></tr>';
} ?>
</table>
<br/><br/>
<table>
    <tr class="head">
        <td colspan="6">Add Automatic Banner</td>
    </tr>
    <tr class="colhead">
        <td width="25%"><span title="TVMaze show ID to match with">
                    TVMaze ID</span></td>
        <td width="20%"><span title="Link to the banner image">
                    Banner Link</span></td>
        <td width="30%" colspan="2"><span>
                    Comment</span></td>
        <td width="10%"><span>
                    Submit</span></td>
    </tr>
    <tr class="rowa">
    <form action="tools.php" method="post">
        <input type="hidden" name="action" value="ba_alter" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <td>
            <input class="medium"  type="text" name="tvmaze" value="<?=$TVMazeID?>"/>
        </td>
        <td>
            <input class="long"  type="text" name="link" />
        </td>
        <td colspan="2">
            <input class="long"  type="text" name="comment" value="<?=$TVMazeTitle?>"/>
        </td>
        <td>
            <input type="submit" name="submit" value="Create" />
        </td>
    </form>
</tr>
</table>

<br/><br/>
<div class='linkbox'><?=$Pages?></div>

<table>
    <tr class="head">
        <td colspan="6">Manage Automatic Banners</td>
    </tr>
    <tr class="colhead">
        <td width="25%"><span title="TVMaze show ID to match with">
                    TVMaze ID</span></td>
        <td width="20%"><span title="Link to the banner image">
                    Banner Link</span></td>
        <td width="30%"><span>
                    Comment</span></td>
        <td width="10%"><span title="Date added">
                    Added</span></td>
        <td width="10%"><span>
                    Submit</span></td>
    </tr>
<?php  $Row = 'b';
foreach($Banners as $Banner) {
    list($ID, $TVMazeID, $Link, $Comment, $UserID, $Username, $BATime) = $Banner;
    $Row = ($Row === 'a' ? 'b' : 'a');
?>
    <tr class="row<?=$Row?>">
        <form action="tools.php" method="post">
            <td>
                <input type="hidden" name="action" value="ba_alter" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="id" value="<?=$ID?>" />
                <input class="medium" type="text" name="tvmaze" value="<?=display_str($TVMazeID)?>" />
            </td>
            <td>
                <input class="long"  type="text" name="link" value="<?=display_str($Link)?>" />
            </td>
            <td>
                <input class="long"  type="text" name="comment" value="<?=display_str($Comment)?>" />
            </td>
            <td>
                <?=format_username($UserID, $Username)?><br />
                <?=time_diff($BATime, 1)?>
                  </td>
            <td>
                <input type="submit" name="submit" value="Edit" />
                <input type="submit" name="submit" value="Delete" />
            </td>
        </form>
    </tr>
<?php  } ?>
</table>
</div>
<?php
show_footer();
