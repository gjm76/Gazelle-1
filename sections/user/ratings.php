<?php

if (!check_force_anon($_GET['userid'])) {
     error(403);
}

include(SERVER_ROOT . '/common/functions.php');

$Orders = array( 'Rated', 'Rating', 'Name');
$Ways = array('desc'=>'Descending', 'asc'=>'Ascending');

$UserID = $_GET['userid'];

if (!is_number($UserID)) { error(0); }

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
    $OrderBy = 'Rated';
}

if (!empty($_GET['order_way']) && array_key_exists($_GET['order_way'], $Ways)) {
    $OrderWay = $_GET['order_way'];
} else {
    $OrderWay = 'DESC';
}

if (!empty($_GET['search']) && trim($_GET['search']) != '') {
    $Words = array_unique(explode(' ', db_string($_GET['search'])));
}

$SQL = "SELECT s.ID, b.BannerLink, s.ShowTitle AS Name, r.Time AS Rated, r.Rating FROM shows AS s
        JOIN shows_ratings AS r ON s.ID = r.ShowID AND r.UserID = $UserID AND r.Rating
        LEFT JOIN torrents_banners AS b ON s.ID = b.TVMazeID"; 

if (!empty($Words)) {
    $SQL .= "
    WHERE s.ShowTitle LIKE '%".implode("%' AND s.ShowTitle LIKE '%", $Words)."%'";
}

$SQL .= " ORDER BY $OrderBy $OrderWay LIMIT $Limit";

$DB->query($SQL);
$Shows = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($ShowsFound) = $DB->next_record();

$DB->query("SELECT count(ShowID) FROM shows_ratings WHERE UserID = $UserID");
list($Total) = $DB->next_record();
$TotalDisplay = $Total;

$DB->query("SELECT count(r.ShowID) FROM shows_ratings AS r 
            LEFT JOIN shows AS s ON r.ShowID = s.ID
            LEFT JOIN torrents_group AS tg ON s.ID = tg.TVMAZE WHERE tg.TVMAZE IS NULL AND r.UserID = $UserID");
list($DeletedShows) = $DB->next_record();

$DB->query("SELECT AVG(Rating) FROM shows_ratings WHERE UserID = $UserID");
list($AverageRating) = $DB->next_record();
if(!$AverageRating) $AverageRating=0;

$Total -= $DeletedShows;

if($ShowsFound < $TorrentsPerPage) $Total = 0;

$Pages = get_pages($Page, $Total, $TorrentsPerPage, 11, '#shows');

$User = user_info($UserID);

show_header('Ratings', '','show,showstool');
?>

<div class="thin">
<?php if($LoggedUser['ID'] == $UserID) { // user ?>
   <h2>Rated Shows</h2>
<?php }else {  //staff ?>
   <h2><a href="user.php?id=<?=$UserID?>"><?=$User['Username']?></a><?='\'s Rated Shows'?></h2>
<?php }?>   

    <div class="head">You have rated <?=$TotalDisplay?> shows<?php echo $DeletedShows?' ('.$DeletedShows.' deleted)':'';?>, 
    with an average rating of <?php if($AverageRating && intval($AverageRating)<10) { 
   echo number_format($AverageRating,1); }
else { echo intval($AverageRating); }?> / 10.</div>

      <form action="" method="get" id="search">
            <table>
                <tr>
                    <td class="label"><strong>Search for:</strong></td>
                    <td colspan="2">
                        <input type="hidden" name="action" id="action" value="ratings" />
                        <input type="hidden" name="userid" value="<?=$UserID?>" />
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
       <td style="width:225px">Banner</td>
       <td style="width:270px"><a href="<?=header_link('Name', 'desc', '#ratings')?>">Name</a></td>
       <td style="width:220px"><a href="<?=header_link('Rating', 'desc', '#ratings')?>">Rating</a></td>
       <td><a href="<?=header_link('Rated', 'desc', '#ratings')?>">Rated</a></td>
     </tr>  
<?php $y=0;   
      foreach ($Shows as $Show) {
         $y++;
         list($ID, $Banner, $ShowTitle, $Rated, $Rating) = $Show;
         if(!$Banner) $Banner = '/static/common/noartwork/noimage.png'; 
?>
         <tr>
            <td>
               <a href='/torrents.php?action=show&showid=<?=$ID?>'>
                <img src='<?=$Banner?>' class="banner_col">
               </a>
            </td>
            <td><a href='/torrents.php?action=show&showid=<?=$ID?>'><?=$ShowTitle?></a></td>
            <td>
      <span class="starRating" style="width:88%">
<?php for($i=10;$i;$i--) {   ?>      
        <input id="rating<?=$i?>_<?=$y?>" type="radio" name="ratingS_<?=$y?>" disabled="disabled" value="<?=$i?>" <?php echo $i==$Rating?'checked':''?> />
        <label for="rating<?=$i?>_<?=$y?>"><?=$i?></label>
<?php } ?>        
      </span>               
               <span style="margin-left:5px; font-weight:bold;"><?=$Rating?></span></td>
               <td><?=time_diff($Rated)?></td>
            </tr>
<?php } ?> 
   </table>
<div class="linkbox"><?=$Pages?></div>
 
</div>
<?php
show_footer();
