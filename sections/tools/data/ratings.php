<?php
if (!check_perms('users_view_ips') || !check_perms('users_view_email')) { error(403); }
include(SERVER_ROOT . '/common/functions.php');

$Orders = array( 'Rated', 'Rating', 'Name', 'Username', 'Votes');
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

    if($OrderBy == 'Votes' ) $GroupBy = ' GROUP BY sr.ShowID';
    
} else {
    $OrderBy = 'Rated';
}

if (!empty($_GET['order_way']) && array_key_exists($_GET['order_way'], $Ways)) {
    $OrderWay = $_GET['order_way'];
} else {
    $OrderWay = 'DESC';
}

$User = null;

if (!empty($_GET['user'])) {
    $User = trim($_GET['user']);
    $User = "um.Username LIKE '%".db_string($User)."%'";
}

if (!empty($_GET['search']) && trim($_GET['search']) != '') {
    $Words = array_unique(explode(' ', db_string($_GET['search'])));
}

$SQL = "SELECT sr.UserID, sr.ShowID, sr.Rating, sr.Time AS Rated, b.BannerLink, s.ShowTitle AS Name, um.Username AS UserName, sr2.Votes
        FROM shows_ratings AS sr
        LEFT JOIN torrents_banners AS b ON sr.ShowID = b.TVMazeID
        LEFT JOIN shows AS s ON s.ID = sr.ShowID
        LEFT JOIN users_main AS um ON sr.UserID = um.ID
        JOIN ( SELECT ShowID, COUNT(UserID) AS Votes FROM shows_ratings GROUP BY ShowID) as sr2 ON sr.ShowID = sr2.ShowID
       "; 

if (!empty($Words)) {
    $SQL .= "
    WHERE s.ShowTitle LIKE '%".implode("%' AND s.ShowTitle LIKE '%", $Words)."%'";
    if(!empty($User)) $SQL .= " AND $User";    
}elseif($User) {
	 $SQL .= "WHERE $User";
}

if($GroupBy) $SQL .= $GroupBy;

$SQL .= " ORDER BY $OrderBy $OrderWay LIMIT $Limit";

$DB->query($SQL);
$Shows = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($ShowsFound) = $DB->next_record();

if($GroupBy) 
   $DB->query("SELECT COUNT(DISTINCT ShowID) FROM shows_ratings");
else
   $DB->query("SELECT COUNT(UserID) FROM shows_ratings");    
list($Total) = $DB->next_record();
$TotalDisplay = $Total;

$DB->query("SELECT AVG(Rating) FROM shows_ratings");
list($AverageRating) = $DB->next_record();
if(!$AverageRating) $AverageRating=0;

if($ShowsFound < $TorrentsPerPage) $Total = 0;

$Pages = get_pages($Page, $Total, $TorrentsPerPage, 11, '#shows');

show_header('Recent Rating Votes', '','show,showstool');
?>

<div class="thin">
   <h2>Recent Rating Votes</h2>
    <div class="head">Total Average rating: <?php if($AverageRating && intval($AverageRating)<10) { 
   echo number_format($AverageRating,1); }
else { echo intval($AverageRating); }?> / 10. 
<?php if($GroupBy) { 
         echo 'Shows: '.$TotalDisplay;
      }else{
      	echo 'Votes: '.$TotalDisplay.' (click on Votes header below / order by Votes to get the totals)';
      } ?>.</div>

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
                    <td class="label"><strong>User:</strong></td>
                    <td>
                        <input type="text" name="user" style="width:99%" value="<?php form('user')?>" />
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
       <td style="width:200px"><a href="<?=header_link('Name', 'desc', '#ratings')?>">Name</a></td>
       <td style="width:220px"><a href="<?=header_link('Rating', 'desc', '#ratings')?>">Rating</a></td>
       <td style="width:50px"><a href="<?=header_link('Votes', 'desc', '#ratings')?>">Votes</a></td>
       <td style="width:100px"><a href="<?=header_link('Username', 'desc', '#ratings')?>">Username</a></td>
       <td><a href="<?=header_link('Rated', 'desc', '#ratings')?>">Rated</a></td>
     </tr>  
<?php $y=0;   
      foreach ($Shows as $Show) {
         $y++;
         list($ID, $ShowID, $Rating, $Rated, $Banner, $ShowTitle , $Username, $Votes) = $Show;
         if(!$Banner) $Banner = '/static/common/noartwork/noimage.png';
         if(!$ShowTitle) $ShowTitle = 'Deleted';
?>
         <tr>
            <td>
               <a href='/torrents.php?action=show&showid=<?=$ShowID?>'>
                <img src='<?=$Banner?>' class="banner_col">
               </a>
            </td>
            <td><a href='/torrents.php?action=show&showid=<?=$ShowID?>'><?=$ShowTitle?></a></td>         
            <td>
      <span class="starRating" style="width:88%">
<?php for($i=10;$i;$i--) {   ?>      
        <input id="rating<?=$i?>_<?=$y?>" type="radio" name="ratingS_<?=$y?>" disabled="disabled" value="<?=$i?>" <?php echo $i==$Rating?'checked':''?> />
        <label for="rating<?=$i?>_<?=$y?>"><?=$i?></label>
<?php } ?>        
      </span>               
               <span style="margin-left:5px; font-weight:bold;"><?=$Rating?></span></td>
            <td style="text-align:center; font-weight:bold;">
                <?=$Votes?> 
            </td>            
            <td>
               <a href='user.php?id=<?=$ID?>'>
                <?=$Username?> 
               </a>
            </td>
               <td><?=time_diff($Rated)?></td>
            </tr>
<?php } ?> 
   </table>
<div class="linkbox"><?=$Pages?></div>
 
</div>
<?php
show_footer();
