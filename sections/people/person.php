<?php

include(SERVER_ROOT . '/sections/people/functions.php');
include(SERVER_ROOT . '/sections/shows/functions.php');
include(SERVER_ROOT . '/sections/torrents/functions.php');

if (!empty($_GET['personid']) && is_number($_GET['personid'])) {
   $PersonID = round($_GET['personid']);
} else {
   $PersonID = 1;
}

setlocale(LC_ALL, "en_US.utf8"); // for special chars name correction
function sanitizeNames($Name) {
   return iconv('UTF-8','ASCII//TRANSLIT',$Name);
}	

function get_poster($PosterURL, &$DebugL) {
   if(!empty($PosterURL)) {
      $PosterURL = upload_to_people_imagehost($PosterURL);
      $DebugL[] = "Imagehost Poster url is: $PosterURL";
   }    
   return $PosterURL;	
}	

function get_person_info($PersonID, &$DebugL) {
   $Person = json_decode(file_get_contents("http://api.tvmaze.com/people/$PersonID"));
   $DebugL[] = 'Collected person info from TVMaze';
   return $Person; 
}

function get_person_shows($PersonID, &$Shows, &$Missing, &$DebugL) {

   global $DB;

   if($PersonID) {
      $DB->query("SELECT PersonID, ShowID, CharacterID, CharacterName, Time FROM persons_shows WHERE PersonID='$PersonID'");
      if ($DB->record_count() > 0) {
   	   $Shows = $DB->to_array();
         $DebugL[] = 'Collected found shows from DB';
      }
      else {
         $Shows = json_decode(file_get_contents("http://api.tvmaze.com/people/$PersonID/castcredits?embed[]=show&embed[]=character"));
         
         $Clean = array();
         $Missing = array();
         $i = 0;
         foreach ($Shows as $Show) {   
             $Clean[$i][PersonID] = $PersonID;
             $Clean[$i][ShowID] = $Show->_embedded->show->id;
             $Clean[$i][CharacterID] = $Show->_embedded->character->id;
             $Clean[$i][CharacterName] = $Show->_embedded->character->name;
             $Missing[$Show->_embedded->show->id][] = $Show->_embedded->show->id;    
    		    $Missing[$Show->_embedded->show->id][] = sanitizeNames($Show->_embedded->show->name);    
             $i++;
         }
         $DebugL[] = 'Collected '.$i.' shows from TVMaze';
         $Shows = $Clean;
      }   
   }

   foreach ($Shows as $Show) {   
      $ShowID = $Show[ShowID];
      $CharacterID = $Show[CharacterID];
      $CharacterName = $Show[CharacterName];
      if($ShowID && $PersonID && $CharacterID) {
         $DB->query("SELECT ShowID FROM persons_shows WHERE PersonID='$PersonID' AND ShowID='$ShowID' AND CharacterID='$CharacterID'");
         if ($DB->record_count() == 0) {
            $DB->query("INSERT INTO persons_shows
            (PersonID, ShowID, CharacterID, CharacterName, Time)
            VALUES
            ('$PersonID', '$ShowID', '$CharacterID', '".db_string($CharacterName)."', '".sqltime()."')");
            $DebugL[] = 'Saved show info to DB';
         }
      }
   }
}

function get_person_missing_shows($PersonID, &$Missing, &$DebugL) {

   global $DB;

   if($PersonID) {

      $ShowsInfo = json_decode(file_get_contents("http://api.tvmaze.com/people/$PersonID/castcredits?embed[]=show"));   	
   	if($ShowsInfo) {
   	   $Info = array();
         foreach ($ShowsInfo as $Show) {
            $Info[$Show->_embedded->show->id] = sanitizeNames($Show->_embedded->show->name);   	
         }	    		
         $DebugL[] = 'Collected missing shows from TVMaze';
   		
         $DB->query("SELECT ps.ShowID FROM persons_shows AS ps
                 LEFT JOIN shows AS s ON ps.ShowID = s.ID
                 WHERE PersonID='$PersonID' AND s.ID IS NULL");
         if ($DB->record_count() > 0) {
   	      $MissingShows = $DB->to_array();
            $DebugL[] = 'Collected missing shows from DB';
            $Missing = array();
            foreach ($MissingShows as $Show) { 
                $Missing[$Show[ShowID]][] = $Show[ShowID];    
                $Missing[$Show[ShowID]][] = $Info[$Show[ShowID]];    
            }
            $DebugL[] = 'Sorted missing shows';            
         }
      }     
   }
}

function update_missing_shows_db($PersonID, &$Missing, &$DebugL) {

   global $DB;
   
   $DB->query("SELECT Missing FROM persons WHERE ID='$PersonID'");
   list($CurrentMissing) = $DB->next_record();

   $CurrentMissing = htmlspecialchars_decode($CurrentMissing, ENT_QUOTES); // fix ' and /
   $MissingCheck = serialize($Missing);

   if ($DB->record_count() > 0 && $MissingCheck != $CurrentMissing) {

      $Updated = db_string( sqltime() );
      $MissingS = db_string( serialize($Missing) );
      
      $DB->query("UPDATE persons SET
          Missing = '$MissingS',
          Updated = '$Updated'
          WHERE ID='$PersonID'");
      $DebugL[] = 'Updated missing shows info to DB';
   }
}

$Data = $Cache->get_value('person_'.$PersonID);

if ($Data) {
    $Data = unserialize($Data);
    list($K, list($PersonName, $PosterURL, $Shows, $Found, $Unique, $Missing, $ShowsTable, $Updated, $Gender, $Birthday, $Country, $CountryCode, 
                  $Age, $Deathday)) = each($Data);
    $Missing = str_replace('\\', '', $Missing);
    if(!is_array($Missing)) $Missing = unserialize($Missing);
    $DebugL[] = 'Pulled person info from cache';
}else {
    $DB->query("SELECT PersonName, PosterUrl, Missing, Updated, Gender, Birthday, Country, CountryCode, Deathday FROM persons WHERE ID='$PersonID'");
    if ($DB->record_count() > 0) {
        list($PersonName, $PosterURL, $Missing, $Updated, $Gender, $Birthday, $Country, $CountryCode, $Deathday) = $DB->next_record();
        $Missing = unserialize(htmlspecialchars_decode($Missing, ENT_QUOTES));        
        $Shows='';
        $DebugL[] = 'Pulled person info from DB';
    }
}

if(!$PersonName) {
   $Person = get_person_info($PersonID, $DebugL);
   $PersonName = $Person->name;

   $PosterURL = get_poster(secure_link($Person->image->medium), $DebugL);
   if(empty($PosterURL)) $PosterURL = '/static/common/images/no-img-poster.png';

   $Gender = $Person->gender;
   $Birthday = $Person->birthday;
   $Deathday = $Person->deathday;
   $Country = $Person->country->name;
   $CountryCode = $Person->country->code;
}   

if(!$Age && $Birthday) {
   $from = new DateTime($Birthday);
   $to   = new DateTime('today');
   $Age = $from->diff($to)->y;
   if($Deathday) {
   	$death = new DateTime($Deathday);
      $Age = $death->diff($from)->y;
   }   
   $DebugL[] = 'Calculated person age'; 
}
   
if(!$Shows) get_person_shows($PersonID, $Shows, $Missing, $DebugL);

if( !isset($Missing) ) get_person_missing_shows($PersonID, $Missing, $DebugL);   
    
$StaffTools = check_perms('torrents_delete');

show_header($PersonName, 'person','show,editgroup,person');

if(empty($Updated)) $Updated = sqltime();

if($PersonID) { 
 
?>

<?php
if(!$Found) {

   $Found = array();
   $Unique = array();
   foreach ($Shows as $Show) {

      $ShowID = $Show[ShowID];
       
      $DB->query("SELECT s.ShowTitle, b.BannerLink FROM shows AS s
                 LEFT JOIN torrents_banners AS b ON s.ID = b.TVMazeID  
                 WHERE s.ID='$ShowID'");

      if ($DB->record_count() > 0) {
         list($ShowTitle, $BannerLink) = $DB->next_record();

         if(!$BannerLink) $BannerLink = 'static/common/noartwork/noimage.png';
   
         $Found[$Show[CharacterID]]['ShowID'] = $ShowID;
         $Found[$Show[CharacterID]]['ShowTitle'] = $ShowTitle;
         $Found[$Show[CharacterID]]['BannerLink'] = $BannerLink;
         $Found[$Show[CharacterID]]['CharacterName'] = $Show[CharacterName];
         $Unique[] = $ShowID;

         if(in_array($ShowID, $Missing[$ShowID])) unset($Missing[$ShowID]);
      }
   }
   
   $DebugL[] = 'Pulled show data from DB';

   // update db entry
   if($Missing) update_missing_shows_db($PersonID, $Missing, $DebugL); 
}

sort($Found);

if(!$ShowsTable) {

   $i = 0;

   foreach ($Found as $Show) {

      // Start an output buffer, so we can store this output in $ShowsTable
      ob_start();

      if( $i % 3 == 0){
         if($i > 0) echo "</tr><tr>"; // first row
      }
?>
      <td class="shows">
         <div class="character"><?=$Show[CharacterName]?></div>
         <a href='torrents.php?action=show&showid=<?=$Show[ShowID]?>'><img class="banner_col" src='<?=$Show[BannerLink]?>' title='Go to <?=$Show[ShowTitle]?>'></a>
      </td>
<?php

      if($i == (count($Found)-1)) echo "</tr>"; //last row
   
      $ShowsTable.=ob_get_clean();   
   
      $i++;
   }
}

if(!$ShowsTable) { 
   $ShowsTable = "<tr><td>Not found</td></tr>";
   get_person_missing_shows($PersonID, $Missing, $DebugL);
   if($Missing) update_missing_shows_db($PersonID, $Missing, $DebugL);
}   

$Debug->set_flag('start users_notify_filters pull');
$PersonNameClean = str_replace('&#39;','\'',$PersonName); // fix ' back
$PersonName = str_replace('\'','&#39;',$PersonName); // fix '

if(check_perms('site_torrents_notify')) {

   $IsNotified = in_array_r($PersonName, $LoggedUser['Notify']);

   if($IsNotified) {
      $DB->query("SELECT ID FROM users_notify_filters WHERE UserID='$LoggedUser[ID]' AND Label='".db_string($PersonNameClean)."'");
      list($N) = $DB->next_record();
   }
}
$Debug->set_flag('end users_notify_filters pull');  

?>
   <div class="main_column main_table">

<?php  $AlertClass = ' hidden';
       if (isset($_GET['did']) && is_number($_GET['did'])) {
          if ($_GET['did'] == 1) {
              $ResultMessage ='Successfully reconstructed';
              $AlertClass = '';
          }
          if ($_GET['did'] == 2) {
              $ResultMessage ='Successfully refreshed';
              $AlertClass = '';
          }
       }
?>
   <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>
<div class="thin">   
 <div class="">
   <div class="sidebar side_bar">
    <div class="main_pad">    
     <div class="head"><?=$PersonName?></div>
         <div class="box" style="width: 214px; height:295px; margin: 0; padding:1px; text-align: center;">
            <img class="poster poster_r" title="<?=$PersonName?>" src="<?=$PosterURL?>">
         </div>
<?php if($Gender) { ?>         
         <div class="head">Person Info</div>
         <div class="box" style="height:auto; margin: 0px; padding: 4px; line-height: 1.5;">
<?php if($Gender) { ?>          
           <div><b>Gender:</b> <?=$Gender?></div>
<?php } ?>
<?php if($Age) { ?>            
           <div><b>Age:</b> <?=$Age?></div>
<?php } ?>
<?php if($Birthday) { ?>            
           <div><b>Birthday:</b> <?=date('F d, Y',strtotime($Birthday))?></div>
<?php }else{ ?>
           <div><b>Birthday:</b> <i>unknown</i></div>
<?php } ?>
<?php if($Deathday) { ?>            
           <div><b>Died:</b> <?=date('F d, Y',strtotime($Deathday))?></div>
<?php } ?>
<?php if($Country) { ?>               
           <div><b>Born in:</b> <?=$Country?> 
              <img src="static/common/flags/iso16/<?=strtolower($CountryCode)?>.png" alt="<?=$Country?>" title="<?=$Country?>" style="vertical-align:middle" />
           </div>
<?php }else{ ?>
           <div><b>Born in:</b> <i>unknown</i></div>
<?php } ?>
         </div>
<?php } ?>              
    </div>
   </div>
   <div class="main_pad pad_person">

<?php if($StaffTools) { ?>
  <form action="" method="post" id="refresh_person">
<?php } ?>
       
    <div class="head">Known For

<?php if(check_perms('site_torrents_notify')) { ?>    
    <div style="float:right; display:inline; margin-top:6px;">
<?php if(!$IsNotified) { ?>
        <a href="#" class="__notify-person" data-label="<?=$PersonName?>" data-person="<?=$PersonName?>" data-personid="<?=$PersonID?>" title="Notify of new uploads">
           <span class="icon icon_notify"></span></a>
<?php }else { ?>
        <a href="#" class="__notify-person" data-label="<?=$PersonName?>" data-person="<?=$PersonName?>" data-personid="<?=$PersonID?>" data-id="<?=$N?>" 
         title="Do not notify"><span class="icon icon_notify notified"></span></a>
<?php } ?>            
    </div>
<?php } ?>    
    
<?php if($StaffTools) { ?>
       <input type="hidden" name="action" id="action" value="refresh_person" />
       <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
       <input type="hidden" name="personid" value="<?=$PersonID?>" />
       <div class="staff_tools">
         Updated: <?=time_diff($Updated)?>
         <input type="submit" class="submit2" value="Reconstruct!" id="submitForm" />         
         <input type="submit" class="submit1" value="Refresh!" id="submitFormCache" />
       </div>
<?php } ?>    
    
    </div>    

<?php if($StaffTools) { ?>    
  </form>
<?php } ?>

   <div class="center">
      <div class="box">
      <table class="shows_table">
         <?=$ShowsTable?>
      </table>
      </div>       
   </div>
  </div>

   <div class="main_pad pad_person">
   <div class="head">Statistics</div>    
     <div class="box">
      <table>
       <tr>       
        <td>Total roles: <?=count($Found)?></td>
        <td>Total shows: <?=count(array_unique($Unique))?></td>
        <td>Total shows missing: <?php echo $Missing? count($Missing):'0'; ?></td>
       </tr>
<?php if($Missing) { ?>       
       <tr>
        <td colspan="3" style="text-align: justify;"><b>Missing:</b> 
        <?php foreach ($Missing as $Show) { echo $Show[1]; if($Show != end($Missing)) echo ' , '; } ?></td>       
       </tr>
<?php } ?>       
      </table>   
     </div>   
  </div>

<?php }else{ ?>
   <h2>Not found</h2>
<?php } ?>
 </div>
</div> 
<?php
show_footer(array('disclaimer' => false));

if($Shows) {
   // save to db
   $DB->query("SELECT ID FROM persons WHERE ID='$PersonID'");
   if ($DB->record_count() == 0) {

      $Updated = db_string( sqltime() );
      $PersonName = db_string( $PersonName );
      $Missing = db_string( serialize($Missing) );

      $DB->query("INSERT IGNORE INTO persons
          (ID, PersonName, PosterUrl, Missing, Updated, Gender, Birthday, Country, CountryCode, Deathday)
          VALUES
          ('$PersonID', '$PersonName', '$PosterURL', '$Missing', '$Updated', '$Gender', '$Birthday', '$Country', '$CountryCode', '$Deathday')");
      $DebugL[] = 'Saved person info to DB';
      write_log("Person $PersonID ($PersonName) was scraped by ".$LoggedUser['Username']);          
   }
}

$Cache->cache_value('person_'.$PersonID, serialize(array(array($PersonName, $PosterURL, $Shows, $Found, $Unique, $Missing, $ShowsTable,
                    $Updated, $Gender, $Birthday, $Country, $CountryCode, $Age, $Deathday))), 3600*3); // cache for 3h

//if($StaffTools) var_dump($DebugL);
