<?php

enforce_login();

include(SERVER_ROOT . '/common/functions.php');

if (isset($_GET['userid'])) {
        if (!is_number($_GET['userid'])) { error(404); }
        $UserID = $_GET['userid'];
} else {
        $UserID = $LoggedUser['ID'];
}

if ($UserID != $LoggedUser['ID'] && !check_perms('admin_manage_networks')) {
    error(403);
}

$_GET['order_by'] = $_COOKIE["FollowsOrderPanelState"]; // load order by cookie
$_GET['order_way'] = $_COOKIE["FollowsOrderWayPanelState"]; // load order way cookie

$Orders = array('inarray'=>array('Added', 'Name', 'Index'));
$OrderTable = array('Added'=>'fs.Time', 'Name'=>'s.ShowTitle', 'Index'=>'s.ID');
$Ways = array('desc'=>'Descending', 'asc'=>'Ascending');

$ShowsPerPage = 24;

if (!empty($_GET['page']) && is_number($_GET['page'])) {
    $Page = $_GET['page'];
    $Limit = ($Page-1)*$ShowsPerPage.', '.$ShowsPerPage;
} else {
    $Page = 1;
    $Limit = $ShowsPerPage;
}

if (!empty($_GET['order_by']) && in_array($_GET['order_by'], $Orders[inarray])) {
    $OrderBy = $OrderTable[$_GET['order_by']];
} else {
    $OrderBy = 'fs.Time';
}

if (!empty($_GET['order_way']) && array_key_exists($_GET['order_way'], $Ways)) {
    $OrderWay = $_GET['order_way'];
} else {
    $OrderWay = 'DESC';
}

if (!empty($_GET['search']) && trim($_GET['search']) != '') {
    $Words = array_unique(explode(' ', db_string($_GET['search'])));
}

$SQL = "SELECT s.ID, s.ShowTitle, tb.BannerLink, fs.Time FROM shows AS s 
        LEFT JOIN follows_shows AS fs ON s.ID=fs.ShowID
        LEFT JOIN torrents_banners AS tb ON tb.TVMazeID=s.ID
        WHERE fs.UserID=$UserID";

if (!empty($Words)) {
    $SQL .= "
    AND s.ShowTitle LIKE '%".implode("%' AND s.ShowTitle LIKE '%", $Words)."%'";
}

$SQL .= " ORDER BY $OrderBy $OrderWay LIMIT $Limit";

$DB->query($SQL);
$SmallMaze = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($Found) = $DB->next_record();

$DB->query("SELECT count(ShowID) FROM follows_shows AS fs JOIN shows AS s ON s.ID=fs.ShowID WHERE UserID=$UserID");
list($Total) = $DB->next_record();
$Totals = $Total;

$DB->query("SELECT count(ShowID) FROM follows_shows WHERE UserID=$UserID");
list($Missing) = $DB->next_record();

if($Found < $ShowsPerPage) $Total = 0;

$Pages = get_pages($Page, $Total, $ShowsPerPage, 11, '#shows');

$StaffTools = check_perms('torrents_delete');

$DB->query("SELECT ShowID FROM follows_shows WHERE UserID='$UserID'");
$Follow = $DB->collect("ShowID");

$User = user_info($UserID);

echo ($UserID != $LoggedUser['ID'])? show_header($User['Username'].'\'s Follows', 'show,follows,jquery.cookie', 'follows') : show_header('Follows', 'show,follows,jquery.cookie', 'follows'); ?>

 <div class="thin">
   <h2>
<?php echo ($UserID != $LoggedUser['ID'])? $User['Username'].'\'s Follows':'Follows'; ?>   
   </h2>
 
    <div class="head">Search</div>

     <div class="box">    

      <div class="pad">

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
                    <select name="order_by" id="order_by">
                    <?php
                        foreach (array_shift($Orders) as $Cur) { ?>
                        <option value="<?=$Cur?>"<?php  if (isset($_GET['order_by']) && $_GET['order_by'] == $Cur || (!isset($_GET['order_by']) && $Cur == 'Added')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
                    <?php 	}?>
                    </select>&nbsp;
                        <select name="order_way" id="order_way">
<?php  foreach ($Ways as $WayKey=>$WayText) { ?>
                            <option value="<?=$WayKey?>" <?php selected('order_way', $WayKey)?>><?=$WayText?></option>
<?php  }?>
                        </select>
                        <div class="search">
                          <input class="search_follows" type="submit" value="Search follows" />
                        </div>
                    </td>
                </tr>
            </table>
      </form>    

<br>
<div class='linkbox'><?=$Pages?></div>

<table class="main">
   <tr>
<?php
    // Data rows
    $i=0;
    foreach($SmallMaze as $key=>$Show){
    	  if($Show['ID']) {
    	  	  $Url = "/torrents.php?action=show&showid=".$Show['ID'];
    	  }

        $IsFollow = in_array($Show['ID'], $Follow);

    	  if(!$Show['BannerLink']) {
    	  	  $Show['BannerLink'] = "/static/common/noartwork/noimage.png";
    	  }

        if($i%3==0){ ?>
             </tr><tr>
<?php   } ?>
             <td>
              <div class="showmaze">
                <div class="title">
                 <span><a href='<?=$Url?>'><?=$Show['ShowTitle']?></span></a>
                </div>
               <a href='<?=$Url?>'>
                <img class="banner_col" src="<?=$Show['BannerLink']?>">
               </a>
               <div class="heart">
<?php if(!$IsFollow) { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$Show['ID']?>" title="Follow"><span class="icon icon_follow"></span></a>
<?php }else { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$Show['ID']?>" title="Following"><span class="icon icon_follow followed"></span></a>
<?php } ?>               
               </div>               
              </div>
             </td>
<?php
        $i++;
    }
    
  if(!$SmallMaze) {   
?>
  <tr><td><h2>Not found.<br />Time to add some shows to follow.</h2></td></tr>
<?php } ?>
  
</table>
</div>

<div class='linkbox'><?=$Pages?></div>
   </div>
  </div> 

<div class="stat_pad" style="margin-left:20px; margin-right:20px;">
   <div class="head">Statistics</div>    
     <div class="box">
      <table>
       <tr>       
        <td>Total shows present: <?=$Totals?></td>
        <td>Total shows missing: <?=$Missing-$Totals?></td>
        <td>Total shows following: <?=$Missing?></td>
       </tr>
      </table>   
     </div>   
</div>

<?php
show_footer();

//if($StaffTools) { sort($Follow); var_dump($Follow); }
