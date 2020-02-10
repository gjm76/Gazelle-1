<?php
enforce_login();

include(SERVER_ROOT.'/sections/shows/functions.php');
include(SERVER_ROOT.'/sections/torrents/functions.php');
include(SERVER_ROOT . '/sections/upload/functions.php');

function get_schedule($CountryCode, $Date) {
    global $Cache;
    if (($Schedule = $Cache->get_value($Date.'-'.$CountryCode.'_tvsched')) === false) {

        $Schedule = file_get_contents("http://api.tvmaze.com/schedule?country=".$CountryCode."&date=".$Date);

        $Schedule = json_decode($Schedule);

        $Schedule = json_encode($Schedule);

        $Cache->cache_value($Date.'-'.$CountryCode.'_tvsched', $Schedule, 3600*6); // cache for 6h
    }

	// set country local time
    if($CountryCode =='GB')
      date_default_timezone_set('Europe/London');
    else 
      date_default_timezone_set('America/Belize');
      	
    return $Schedule;
}

show_header('Schedule', 'show', 'tvschedule');

// Fail Safe for Country
if(!empty($_GET['country']) && in_array(strtoupper($_GET['country']), ['GB', 'US'])){
    $CountryCode = strtoupper($_GET['country']);
}
else {
    $CountryCode = 'US';
}

// All / Follow switch
$All = $_GET['all'];

if(!$All || $All=='following')
  $All = 'Following';
  
if($All=='all')
  $All='All';

// Fail safe for Date
$Off = $_GET['date'];
if($Off == '' || $Off < -2 || $Off > 7) $Off = 0;
$Date = date('Y-m-d',strtotime('today '.$Off.' day'));

$Schedule = json_decode(get_schedule($CountryCode, $Date));

$DB->query("SELECT ShowID FROM follows_shows WHERE UserID='$UserID'");
$Follow = $DB->collect("ShowID");

?>
<div class="thin">
    <h2>Schedule</h2>
    <?php if($CountryCode =='GB'){?>
     <div class="head"><span><?=date('l, jS \of F Y',strtotime($Date))?><?php if(!$Off) echo ' '.date('H:m:s', time());?></span></div>
    <?php }else{?>
     <div class="head"><span><?=date('l, jS \of F Y',strtotime($Date))?><?php if(!$Off) echo ' '.date('h:m:s A', time());?></span></div>
    <?php }?>
    <div class="box pad">

     <table class="nobr">
      <tr>
       <td class="nobr flags" style="width:45px;">    
        <a href="/tvschedule.php?country=us&all=<?=strtolower($All)?>&date=<?=$Off?>"><img src="/static/common/flags/iso16/us.png"></a>&nbsp
        <a href="/tvschedule.php?country=gb&all=<?=strtolower($All)?>&date=<?=$Off?>"><img src="/static/common/flags/iso16/gb.png"></a>&nbsp
       </td>
       <td class="nobr" style="width:130px;">
        <?php if($All=='Following') {?> 
         <input class="showshead" type="button" value="Show All" onclick="document.location.href='/tvschedule.php?country=<?=strtolower($CountryCode)?>&all=all&date=<?=$Off?>'" >
        <?php }
        if($All=='All') {?> 
         <input class="showshead" type="button" value="Show Following Only" onclick="document.location.href='/tvschedule.php?country=<?=strtolower($CountryCode)?>&all=following&date=<?=$Off?>'" >
        <?php }?>
       </td>
       <td class="nobr">
<?php    if($Off=='-2') { ?>       
         -2 |
<?php    }else{ ?>                  
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=-2">-2</a> |         
<?php    } ?>
<?php    if($Off=='-1') { ?>       
         -1 |
<?php    }else{ ?> 
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=-1">-1</a> |
<?php    } ?>
<?php    if($Off=='0') { ?>       
         Today |
<?php    }else{ ?>                   
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=">Today</a> |
<?php    } ?>                  
<?php    if($Off=='1') { ?>       
         +1 |
<?php    }else{ ?>                   
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=1">+1</a> |
<?php    } ?>
<?php    if($Off=='2') { ?>       
         +2 |
<?php    }else{ ?>                   
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=2">+2</a> |
<?php    } ?>
<?php    if($Off=='3') { ?>       
         +3 |
<?php    }else{ ?>                 
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=3">+3</a> |
<?php    } ?>
<?php    if($Off=='4') { ?>       
         +4 |
<?php    }else{ ?>                
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=4">+4</a> |
<?php    } ?>
<?php    if($Off=='5') { ?>       
         +5 |
<?php    }else{ ?>                 
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=5">+5</a> |
<?php    } ?>
<?php    if($Off=='6') { ?>       
         +6 |
<?php    }else{ ?> 
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=6">+6</a> |      
<?php    } ?>
<?php    if($Off=='7') { ?>       
         +7
<?php    }else{ ?> 
         <a href="/tvschedule.php?country=<?=$CountryCode?>&all=<?=strtolower($All)?>&date=7">+7</a>        
<?php    } ?>
       </td>
     </tr>
    </table> 

    <table class="tvschedule">
    <tr class="head">
        <td colspan="8"><?=$CountryCode?> <?php if($All=='Following') echo Following?> Shows</td>
    </tr>
<?php
    $Counter = 0;
    foreach($Schedule as $Show) {
    	  
    	 // Network Logo
       if($Show->show->network) {
          $NetworkName = $Show->show->network->name;
          $NetworkName = str_ireplace(' ', '+', $NetworkName);
          $NetworkLogo =  "static/common/shows/networks/".strtolower($NetworkName).".png";
       }
       elseif($Show->show->webChannel) {
          $NetworkName = $Show->show->webChannel->name;
          $NetworkName = str_ireplace(' ', '+', $NetworkName);
          $NetworkLogo =  "static/common/shows/networks/".strtolower($NetworkName).".png";
       }
       
       $ID = $Show->show->id;
       $DB->query("SELECT BannerLink FROM torrents_banners WHERE TVMazeID = $ID LIMIT 1");

       list($Banner) = $DB->next_record();

       $Synopsis = str_replace('"', '', $Show->show->summary); // Striping " to correct Synopsis
       $Synopsis = strip_tags($Synopsis); // Striping tags
       $Synopsis = cutAfter($Synopsis,444,' ...'); // Cut synopsis
       
       if($Show->show->id) // Search prep
          $Url = "/torrents.php?action=show&showid=".$Show->show->id;
       else
          $Url = torrents_link($Show->show->name); 
       
       $Tag = strtolower(str_replace('+', '.', $NetworkName)); // Tag prep
       if(strcmp($Tag,'a&e') == 0) $UrlNetwork = "/torrents.php?searchtext=&taglist=aetv"; // fix A&E
       else $UrlNetwork = "/torrents.php?searchtext=&taglist=".$Tag;
       
       // make tags
       $Genres = '';
       $Type = strtolower(trim(trim($Show->show->type,'.'))); // trim dots from the beginning and end
       $Type = str_replace('+', '.', $Type);
       $Type = get_tag_synonym($Type);
       if (!empty($Type) && is_valid_tag($Type) && check_tag_input($Type)){
          $Genres = "<a href='/torrents.php?searchtext=&taglist=" . $Type . "'>" . $Type . "</a>";
       }
       
       foreach ($Show->show->genres as $Genre) {
        $Genre = strtolower(trim(trim($Genre,'.'))); // trim dots from the beginning and end
        $Genre = str_replace('+', '.',$Genre);
        $Genre = get_tag_synonym($Genre);
        if (!empty($Genre) && is_valid_tag($Genre) && check_tag_input($Genre)){
           $Genres .= ', ' . "<a href='/torrents.php?searchtext=&taglist=" . $Genre . "'>" . $Genre . "</a>";
        }
       }
       
       $Genres = (implode(', ',array_filter(array_unique(explode(', ', $Genres))))); // remove doubles and empties
       
       if($All=='All' && !$Banner) {
       	$Banner = '/static/common/noartwork/noimage.png';
       	}
       $IsFollow = in_array($Show->show->id, $Follow);
       if($IsFollow || $All=='All') {
       	$Counter++;
?>
    <tr>
        <td style="width:35px">
        <?php if(file_exists(SERVER_ROOT . "/" . $NetworkLogo)){?>        
        <a href='<?=$UrlNetwork?>'><img class="tvschedulenetworklogo" src="<?=$NetworkLogo?>" title="Search for <?=$Show->show->network->name?>">
        <?php }?>        
        </td>
        <td><?=$Show->airtime?></td>
        <td style="width:227px"><a href='<?=$Url?>'><img  data-tooltip-width="400" class="banner_col" src="<?=$Banner?>" title="<?=$Synopsis?>" /></a></td>
        <td><span style="float:left"><b><a class="tvschedule" href='<?=$Url?>'><?=$Show->show->name?></a></b></span><br />
        <span style="float:left"><span class="tv-tags"><?=$Genres?></span></span>
        </td>
        <td>Season <?=str_pad($Show->season, 2, '0', STR_PAD_LEFT)?><br>Episode <?=str_pad($Show->number, 2, '0', STR_PAD_LEFT)?></td>
        <td><?php if($Show->runtime) {?><?=$Show->runtime?> mins<?php }?></td>
        <td>
        
<?php if(!$IsFollow) { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$Show->show->id?>" title="Follow"><span class="icon icon_follow"></span></a>
<?php }else { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$Show->show->id?>" title="Following"><span class="icon icon_follow followed"></span></a>
<?php } ?> 
        
        </td>
    </tr>
<?php
      }	
    }

if (!$Counter) { ?>
    <tr><td><h2>Not found.<br />Time to add some shows to follow.</h2></td></tr> 	
<?php } ?>
    </table>
    </div>
   </div> 
<?php
show_footer();
