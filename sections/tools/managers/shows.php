<?php
if (!check_perms('admin_manage_networks')) {
    error(403);
}

include(SERVER_ROOT . '/common/functions.php');

$Orders = array( 'Updated', 'ShowTitle', 'ID');
$Ways = array('desc'=>'Descending', 'asc'=>'Ascending');

if (isset($LoggedUser['TorrentsPerPage'])) {
    $TorrentsPerPage = $LoggedUser['TorrentsPerPage'];
} else {
    $TorrentsPerPage = TORRENTS_PER_PAGE;
}

if (!empty($_GET['page']) && is_number($_GET['page'])) {
    $Page = $_GET['page'];
    $Limit = ($Page-1)*$TorrentsPerPage.', '.$TorrentsPerPage;
} else {
    $Page = 1;
    $Limit = $TorrentsPerPage;
}

if (!empty($_GET['order_by']) && in_array($_GET['order_by'], $Orders)) {
    $OrderBy = $_GET['order_by'];
} else {
    $OrderBy = 'Updated';
}

if (!empty($_GET['order_way']) && array_key_exists($_GET['order_way'], $Ways)) {
    $OrderWay = $_GET['order_way'];
} else {
    $OrderWay = 'DESC';
}

if (!empty($_GET['search']) && trim($_GET['search']) != '') {
    $Words = array_unique(explode(' ', db_string($_GET['search'])));
}

$SQL = "SELECT ID, PosterURL, ShowTitle, Updated FROM shows"; 

if (!empty($Words)) {
    $SQL .= "
    WHERE ShowTitle LIKE '%".implode("%' AND ShowTitle LIKE '%", $Words)."%'";
}

$SQL .= " ORDER BY $OrderBy $OrderWay LIMIT $Limit";

if (!empty($_GET['dead']) && $_GET['dead'] == 1)
   $SQL = "SELECT s.ID, s.PosterURL, s.ShowTitle, s.Updated FROM shows AS s LEFT JOIN torrents_group AS tg ON s.ID = tg.TVMAZE WHERE tg.TVMAZE IS NULL";

$DB->query($SQL);
$Shows = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($ShowsFound) = $DB->next_record();

$DB->query("SELECT count(ID) from shows");
list($Total) = $DB->next_record();
$TotalDisplay = $Total;

$DB->query("SELECT count( DISTINCT TVMAZE) FROM torrents_group WHERE TVMAZE");
list($TotalShows) = $DB->next_record();

if($ShowsFound < $TorrentsPerPage) $Total = 0;

$Pages = get_pages($Page, $Total, $TorrentsPerPage, 11, '#shows');

$DB->query("SELECT count(s.ID) FROM shows AS s LEFT JOIN torrents_group AS tg ON s.ID = tg.TVMAZE WHERE tg.TVMAZE IS NULL");
list($DeletedShows) = $DB->next_record();

show_header('Manage Shows', 'showstool','editgroup,showstool');
?>

<div class="thin">
   <h2>Shows</h2>

<?php  $AlertClass = ' hidden';
       if (isset($_GET['did']) && is_number($_GET['did'])) {
          if ($_GET['did'] == 1) {
              $ResultMessage ='Successfully cleared';
              $AlertClass = '';
          }
       }
?>
   <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>
   
   <form action="" method="post" id="clear" onsubmit="return confirm('This will clear absent shows from DB.\nAre you sure?');">
    <input type="hidden" name="action" id="action" value="clear_shows" />
    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
    <div class="head">Total Shows in DB: <?=$TotalDisplay?> of <?=$TotalShows?> available
<?php if(!$_GET['dead'] && $DeletedShows) { ?>
    , incl. <?=$DeletedShows?> dead.
      <input type="button" class="submit absent" value="Show absent" onclick="ShowDead(this);" />
<?php }elseif($DeletedShows) { ?>
    . (custom shows excluded)
    <div class="buttonswrap" >   
      <input type="button" class="submit" value="Show All" onclick="ShowAll(this);" />
      <input type="submit" class="submit" value="Clear absent!" onclick="Clear(this);" />
    </div>  
<?php }else { ?>
    .
<?php } ?>       
    </div>
   </form> 
      <form action="" method="get" id="search">
            <table>
                <tr>
                    <td class="label"><strong>Search for:</strong></td>
                    <td colspan="2">
                        <input type="hidden" name="action" id="action" value="shows" />
                        <input type="text" name="search" style="width:99%" value="<?php form('search')?>" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><strong>Order by</strong></td>
                    <td>
                        <select name="order_by">
<?php  foreach ($Orders as $OrderText) { ?>
                            <option value="<?=$OrderText?>" <?php selected('order_by', $OrderText)?>><?=$OrderText?></option>
<?php  }?>
                        </select>&nbsp;
                        <select name="order_way">
<?php  foreach ($Ways as $WayKey=>$WayText) { ?>
                            <option value="<?=$WayKey?>" <?php selected('order_way', $WayKey)?>><?=$WayText?></option>
<?php  }?>
                        </select>
                        <div class="search">
                          <input type="submit" value="Search shows" />
                        </div>
                    </td>
                </tr>
            </table>
      </form>    
<div class="linkbox"><?=$Pages?></div>
   <table>
     <tr>
       <td><a href="<?=header_link('ID', 'desc', '#shows')?>">ID</a></td>
       <td>Poster</td>
       <td><a href="<?=header_link('ShowTitle', 'desc', '#shows')?>">Name</a></td>
       <td><a href="<?=header_link('Updated', 'desc', '#shows')?>">Updated</a></td>
     </tr>  
<?php   foreach ($Shows as $Show) {
            list($ID, $PosterURL, $ShowTitle, $Updated) = $Show; ?>
            <tr>
               <td><?=$ID?></td>
               <td>
                  <a href='/torrents.php?action=show&showid=<?=$ID?>'>
                  <img src='<?=$PosterURL?>' class="show_poster">
                  </a>
               </td>
               <td><?=$ShowTitle?></td>
               <td><?=time_diff($Updated)?></td>
            </tr>
<?php   } ?> 
   </table>
<div class="linkbox"><?=$Pages?></div>
 
</div>
<?php
show_footer();
