<?php

enforce_login();

$Orders = array('inarray'=>array('Updated', 'Name', 'Index'));
$OrderTable = array('Updated'=>'Updated', 'Name'=>'PersonName', 'Index'=>'ID');
$Ways = array('desc'=>'Descending', 'asc'=>'Ascending');

$PeoplePerPage = 12;

if (!empty($_GET['page']) && is_number($_GET['page'])) {
    $Page = $_GET['page'];
    $Limit = ($Page-1)*$PeoplePerPage.', '.$PeoplePerPage;
} else {
    $Page = 1;
    $Limit = $PeoplePerPage;
}

if (!empty($_GET['order_by']) && in_array($_GET['order_by'], $Orders[inarray])) {
    $OrderBy = $OrderTable[$_GET['order_by']];
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

$SQL = "SELECT ID, PersonName, PosterUrl, Updated FROM persons";

if (!empty($Words)) {
    $SQL .= "
    WHERE PersonName LIKE '%".implode("%' AND PersonName LIKE '%", $Words)."%'";
}

$SQL .= " ORDER BY $OrderBy $OrderWay LIMIT $Limit";

$DB->query($SQL);
$SmallMaze = $DB->to_array();

$DB->query("SELECT FOUND_ROWS()");
list($Found) = $DB->next_record();

$DB->query("SELECT count(ID) FROM persons");
list($Total) = $DB->next_record();
$Totals = $Total;

if($Found < $PeoplePerPage) $Total = 0;

$Pages = get_pages($Page, $Total, $PeoplePerPage, 11, '#people');

$StaffTools = check_perms('torrents_delete');

show_header('People', '', 'shows,editgroup,people');
?>

 <div class="thin">
   <h2>People</h2>
    <div class="head">Search <div style="display:inline; float:right;">Total: <?=$Totals?></div></div>
    <div class="box pad">    
      <form action="" method="get" id="search">
            <table>
                <tr>
                    <td class="label"><strong>Search for:</strong></td>
                    <td colspan="2">
                        <input type="hidden" name="action" id="action" value="people" />
                        <input type="text" name="search" style="width:99%" value="<?php form('search')?>" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><strong>Order by</strong></td>
                    <td>
                    <select name="order_by">
                    <?php
                        foreach (array_shift($Orders) as $Cur) { ?>
                        <option value="<?=$Cur?>"<?php  if (isset($_GET['order_by']) && $_GET['order_by'] == $Cur || (!isset($_GET['order_by']) && $Cur == 'Updated')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
                    <?php 	}?>
                    </select>&nbsp;
                        <select name="order_way">
<?php  foreach ($Ways as $WayKey=>$WayText) { ?>
                            <option value="<?=$WayKey?>" <?php selected('order_way', $WayKey)?>><?=$WayText?></option>
<?php  }?>
                        </select>
                        <div class="search">
                          <input class="search_people" type="submit" value="Search people" />
                        </div>
                    </td>
                </tr>
            </table>
      </form>    

<br>
<div class='linkbox'><?=$Pages?></div>

<table>
   <tr>
<?php
    // Data rows
    $i=0;
    foreach($SmallMaze as $key=>$Person){
    	  if($Person['ID']) {
    	  	  $Url = "/torrents.php?action=person&personid=".$Person['ID'];
    	  }

        if($i%4==0){ ?>
             </tr><tr>
<?php   } ?>
             <td class="showmaze">
              <div class="showswrapper">
               <a href='<?=$Url?>'>
                <img class="showmaze" src="<?=$Person['PosterUrl']?>" title="<?=$Person['PersonName']?>">
               </a>
               <div class="showsfoot">
                <a href='<?=$Url?>'><?=$Person['PersonName']?></a>
               </div>
              </div>
             </td>
<?php
        $i++;
    }
?>
</table>

<br>
<div class='linkbox'><?=$Pages?></div>
   </div>
  </div> 
<?php
show_footer();
