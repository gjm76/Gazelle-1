<?php
include(SERVER_ROOT.'/sections/shows/functions.php');
include(SERVER_ROOT.'/sections/torrents/functions.php');

enforce_login();

// Pages
if (!empty($_GET['page']) && is_number($_GET['page'])) {
    $Page = $_GET['page'];
} else {
    $Page = 1;
}

$StaffTools = check_perms('torrents_delete');

show_header('Shows', 'show,shows,jquery.cookie', 'shows,editgroup');

$_GET['sort'] = $_COOKIE["sortPanelState"]; // load sort cookie

// Fail Safe for Sort
if(empty($_GET['sort']) || !in_array($_GET['sort'], ['id', 'name', 'rating', 'premiered', 'updated', 'weight'])){
    $_GET['sort'] = 'updated';
    $Sort = 'updated';
} else {
    $Sort = $_GET['sort'];
}
/*
// Fail Safe for Taglist
if(!empty($_GET['taglist']) && in_array($_GET['taglist'], ['', '2160p', '1080p', '720p', 'sd', 'seasons'])){
    $Filter = $_GET['taglist'];
    if($_GET['taglist']=='seasons')
        $Filter = ucfirst($Filter);
    if($_GET['taglist']=='sd')
        $Filter = strtoupper($Filter);
}
*/
// Genres unit -----------------------------
$Genres = array('Action', 'Adult', 'Adventure', 'Anime', 'Children', 'Comedy', 'Crime',
                'DIY', 'Drama', 'Espionage', 'Family', 'Fantasy', 'Food', 'History', 'Horror', 'Legal', 'Medical', 'Music',
                'Mystery', 'Nature', 'Romance', 'Science-Fiction', 'Sports', 'Supernatural', 'Thriller', 'Travel', 'War', 'Western');

sort($Genres); // sort in alphabetical order

// Fail Safe for Genres
if(!empty($_GET['genre']) && in_array($_GET['genre'], $Genres)){
    $Genre = $_GET['genre'];
}
else {
     $Genre = false;
}

$force_reload = isset($_GET['reload']) && check_perms('site_reload_shows');

$Debug->set_flag('start get_shows');
$BigMaze = get_shows($force_reload);
$Debug->set_flag('end get_shows');

$Networks = get_networks($BigMaze);

// Fail Safe for Networks
if(!empty($_GET['network']) && in_array($_GET['network'], $Networks)){
    $Network = $_GET['network'];
}
else {
    $Network = false;
}

$DB->query("SELECT ShowID FROM follows_shows WHERE UserID='$UserID'");
$Follow = $DB->collect("ShowID");

?>
<?php if($StaffTools) { 
       $AlertClass = ' hidden';
       if (isset($_GET['did']) && is_number($_GET['did'])) {
          if ($_GET['did'] == 1) {
              $ResultMessage ='Successfully refreshed';
              $AlertClass = '';
          }
       }
?>
   <form action="" method="post" id="refresh" onsubmit="return confirm('This will refresh Shows cache.\nAre you sure you want to refresh it?');">
   <div class="head">Staff tools 
       <input type="hidden" name="action" value="refresh" />
       <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
       <input type="hidden" name="userid" value="<?=$LoggedUser['ID']?>" />
       <div style="float:right; margin-top:4px;">
        <input type="submit" class="submit" value="Refresh!" id="submitFormShows" />
       </div>
   </div>
  </form>

  <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>
<?php } ?>

<form action="" method="get" id="form1">
 <div class="thin">
   <h2>Shows</h2>
   <div class="head">
    <input type="hidden" value="<?=$Page?>" name="page">
           Sort by
    <div class="select-box-sort">
     <select name="sort" id="sort_box">
          <option value="id"        <?=selected('sort', 'id',     'selected')?>>Index</option>
          <option value="weight"    <?=selected('sort', 'weight', 'selected')?>>Most Popular</option>
          <option value="name"      <?=selected('sort', 'name',   'selected')?>>Name</option>
          <option value="rating"    <?=selected('sort', 'rating', 'selected')?>>Rating</option>
          <option value="premiered" <?=selected('sort', 'premiered', 'selected')?>>Recently Added</option>
          <option value="updated"   <?=selected('sort', 'updated','selected')?>>Updated</option>
      </select>
    </div>
<?php /*    
             | Search in
    <div class="select-box-format">
     <select name="taglist">
          <option value="all"     <?=selected('taglist', '',     'selected')?>>All formats</option>
          <option value="2160p"   <?=selected('taglist', '2160p',   'selected')?>>2160p</option>
          <option value="1080p"   <?=selected('taglist', '1080p',   'selected')?>>1080p</option>
          <option value="720p"    <?=selected('taglist', '720p',    'selected')?>>720p</option>
          <option value="sd"      <?=selected('taglist', 'sd',      'selected')?>>SD</option>
          <option value="seasons" <?=selected('taglist', 'seasons', 'selected')?>>Seasons</option>
    </select>
    </div>
      */ ?>    
             | Genre
    <div class="select-box-genre">
    <select name="genre" onchange="document.forms['form1']['page'].value=1;">
          <option value="" <?=selected('genre', '','selected')?>></option>
          <?php foreach($Genres as $Gen){?>
          <option value="<?=$Gen?>" <?=selected('genre', $Gen, 'selected')?>><?=$Gen?></option>
          <?php }?>
        </select>
    </div>
             | Network
    <div class="select-box-network">
    <select name="network" onchange="document.forms['form1']['page'].value=1;">
          <option value="" <?=selected('network', '', 'selected')?>></option>
          <?php foreach($Networks as $Net) { ?>
          <option value="<?=$Net?>" <?=selected('network', $Net, 'selected')?>><?=$Net?></option>
          <?php }?>
    </select>
    </div>
    <div class="showsheadinput">
     <input class="showshead" type="submit" value="Filter" style="margin-right:0; margin-top:15%;">
    </div>
  </div>
  <div class="box pad">
  
 
</form>

<?php

$Grid = 12; // How many shows to show

// Sort Module ------------------------------------------------------

if(in_array($_GET['sort'], ['updated','rating','weight','premiered'])) {
    $order = SORT_DESC;
    }
else {
    $order = SORT_ASC;
}

// Sort by
array_sort_by_column($BigMaze, $Sort, $order);

// Sort by Genre
if($Genre) {
    $BigMaze = get_genre($BigMaze, $Genre);
}

// Sort by Network
if($Network) {
    $BigMaze = get_network($BigMaze, $Network);
}

$Debug->set_flag('end sort_shows');
// ------ End of Sort Module ---------------------------------------

$Pages = get_pages($Page, count($BigMaze), $Grid, 10);

// The cut per page
$SmallMaze = array_slice($BigMaze,($Page-1)*$Grid,$Grid);
?>
<br>
<div class='linkbox'><?=$Pages?></div>

<table>
   <tr>
<?php
    // Data rows
    $i=0;
    foreach($SmallMaze as $key=>$Show){
    	  if($Show['id']) {
    	  	  $Url = "/torrents.php?action=show&showid=".$Show['id'];
    	  }
    	  else {	
           $Url = torrents_link($Show['name']);
           if(!empty($Filter)) $Url .=  "&taglist=".urlencode($Filter);
        }   

        $Synopsis = str_replace('"', '', $Show['summary']); // Striping " to correct Synopsis
        $Synopsis = strip_tags($Synopsis); // Striping tags

        $SUrl = $Show['image']['medium'];
        
        if(!$SUrl) { // No poster
          $SUrl = "static/common/shows/no-img.jpg";
        }	
        
        // Network Logo
        if($Show['network']['name']) {
          $NetworkName =  $Show['network']['name'];
          $NetworkLogo =  htmlspecialchars_decode($Show['network']['url']);
        }
        elseif($Show['webChannel']['name']) {
          $NetworkName =  $Show['webChannel']['name'];
          $NetworkLogo =  htmlspecialchars_decode($Show['network']['url']);
        }

        //$SearchIcon = '<a href="'.get_search($Show['name']).'"><span class="icon icon_search" title="Search"></span</a>';
        
        $IsFollow = in_array($Show['id'], $Follow);        
        
        if($i%4==0){ ?>
             </tr><tr>
<?php   } ?>
             <td class="showmaze"><div class="showswrapper"><a href='<?=$Url?>'>
             <img class="showmaze" src="<?=$SUrl?>" title="<?=$Synopsis?>">
             <div class="showsrating">
                 <span class="rating"><?=print_rating($Show['rating']['average'], $i)?></span>
                 <?=$Show['rating']['average']?>
             </div>
             <br/><span></a>
             <div class="showsfoot">
             <?php if(file_exists(SERVER_ROOT . "/".$NetworkLogo)){?>
             <span class="showslogo"><img class="showmazenetworklogo" src="<?=$NetworkLogo?>" title="Search for <?=$NetworkName?> Shows"
             onclick="document.forms['form1']['network'].value = '<?=$NetworkName?>'; document.forms['form1']['page'].value=1;
             document.forms['form1'].submit();"></span>
             <?php }?>
             <span><a href='<?=$Url?>' title="Search for <?=$Show['name']?><?php if(!empty($Filter)) echo ' in '.$Filter; ?>"><?=$Show['name']?></span></a></span>
             <span class="showslogo">

<?php if(!$IsFollow) { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$Show['id']?>" title="Follow"><span class="icon icon_follow"></span></a>
<?php }else { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$Show['id']?>" title="Following"><span class="icon icon_follow followed"></span></a>
<?php } ?>              
             
             </span></div></div></td>
<?php
        $i++;
    }
?>
</table>

<?php  $Debug->set_flag('end build_table'); ?>

    <br>
    <div class='linkbox'><?=$Pages?></div>
  </div>
<?php
show_footer();
