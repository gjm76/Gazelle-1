<?php

include(SERVER_ROOT . '/common/functions.php');

$Orders = array( 'Rated', 'Rating', 'Name');
$Ways = array('desc'=>'Descending', 'asc'=>'Ascending');

$ShowID = $_GET['showid'];

if (!is_number($ShowID)) { error(0); }

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

$SQL = "SELECT sr.UserID, sr.Time AS Rated, sr.Rating, um.Username AS Name FROM shows_ratings AS sr 
        LEFT JOIN users_main AS um ON sr.UserID = um.ID        
        WHERE ShowID = $ShowID"; 

$SQL .= " ORDER BY $OrderBy $OrderWay LIMIT $Limit";

$DB->query($SQL);
$Shows = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($ShowsFound) = $DB->next_record();

$DB->query("SELECT count(UserID) FROM shows_ratings WHERE ShowID = $ShowID");
list($Total) = $DB->next_record();
$TotalDisplay = $Total;

$DB->query("SELECT AVG(Rating) FROM shows_ratings WHERE ShowID = $ShowID");
list($AverageRating) = $DB->next_record();
if(!$AverageRating) $AverageRating=0;

if($ShowsFound < $TorrentsPerPage) $Total = 0;

$Pages = get_pages($Page, $Total, $TorrentsPerPage, 11, '#shows');

$DB->query("SELECT ShowTitle FROM shows WHERE ID = $ShowID");
list($ShowName) = $DB->next_record();

show_header($ShowName.' > Ratings', '','show,showstool');
?>

<div class="thin">
   <h2><a href='torrents.php?action=show&showid=<?=$ShowID?>'><?=$ShowName?></a> > Ratings</h2>
    <div class="head">Average rating: <?php if($AverageRating && intval($AverageRating)<10) { 
   echo number_format($AverageRating,1); }
else { echo intval($AverageRating); }?> / 10. Votes: <?=$TotalDisplay?>.</div>

<div class="linkbox"><?=$Pages?></div>
   <table>
     <tr>
       <td style="width:300px"><a href="<?=header_link('Name', 'desc', '#ratings')?>">Name</a></td>
       <td style="width:220px"><a href="<?=header_link('Rating', 'desc', '#ratings')?>">Rating</a></td>
       <td><a href="<?=header_link('Rated', 'desc', '#ratings')?>">Rated</a></td>
     </tr>  
<?php $y=0;   
      foreach ($Shows as $Show) {
         $y++;
         list($ID, $Rated, $Rating, $Username) = $Show;
?>
         <tr>
            <td>
               <a href='user.php?id=<?=$ID?>'>
                <?=$Username?> 
               </a>
            </td>
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
