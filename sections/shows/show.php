<?php

include(SERVER_ROOT . '/common/functions.php');
include(SERVER_ROOT . '/sections/torrents/functions.php');
include(SERVER_ROOT . '/sections/upload/functions.php');
include(SERVER_ROOT . '/sections/bookmarks/functions.php');
include(SERVER_ROOT . '/sections/shows/functions.php');


if (!empty($_GET['showid']) && is_number($_GET['showid'])) {
   $TVMAZE = round($_GET['showid']);
} else {
   $TVMAZE = 1;
}

function get_poster($PosterURL, &$Debug) {
   if(!empty($PosterURL)) {
   	$Debug->set_flag('Start Poster pull');
      $PosterURL = upload_to_imagehost($PosterURL);
      $Debug->set_flag('Imagehost Poster url is: '.$PosterURL);
   }
   return $PosterURL;
}

function get_episodes_info($TVMAZE, &$Debug) {
   $Episodes = json_decode(file_get_contents("http://api.tvmaze.com/shows/$TVMAZE?embed[]=previousepisode&embed[]=nextepisode"));
   $Debug->set_flag('Collected Latest Episode from TVMaze');
   return $Episodes;
}

function get_tvmaze_info($ID, $Season, &$Debug) {

    if(!empty($Season)) {
        $Debug->set_flag("Performing search by season");
        $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/shows/$ID?&embed[]=crew"));
        $TVMazeInfo['Poster'] = $RawTVMazeInfo->image->medium;
        $TVMazeInfo['Network'] = $RawTVMazeInfo->network->name;
        $TVMazeInfo['WebChannel'] = $RawTVMazeInfo->webChannel->name;
        $TVMazeInfo['Weight'] = $RawTVMazeInfo->weight;
        $TVMazeInfo['Premiered'] = $RawTVMazeInfo->premiered;

        //getting crew
        $Crew =  $RawTVMazeInfo->_embedded->crew;

        // looking for creators
        foreach($Crew as $Search){
   	   if($Search->type != 'Creator') continue;
           $Creators[] = $Search;
        }

        $TVMazeInfo['Synopsis'] = "[url=".$RawTVMazeInfo->url."][size=4]".$RawTVMazeInfo->name."[/size][/url]\n\n";

        $TVMazeInfo['Synopsis'] .= "[b]Airs on: [/b][url=/shows.php?page=1&sort=weight&taglist=all&genre=&network=";

 	     if($RawTVMazeInfo->network->name)
		     $TVMazeInfo['Synopsis'] .= str_replace(' ', '+', $RawTVMazeInfo->network->name);
	     elseif($RawTVMazeInfo->webChannel->name)
		     $TVMazeInfo['Synopsis'] .= str_replace(' ', '+', $RawTVMazeInfo->webChannel->name);
	     $TVMazeInfo['Synopsis'] .= "]";
 	     if($RawTVMazeInfo->network->name)
		     $TVMazeInfo['Synopsis'] .= $RawTVMazeInfo->network->name;
	     elseif($RawTVMazeInfo->webChannel->name)
		     $TVMazeInfo['Synopsis'] .= $RawTVMazeInfo->webChannel->name;
        $TVMazeInfo['Synopsis'] .= "[/url]\n";

        $TVMazeInfo['Synopsis'] .= "[b]Status: [/b]".$RawTVMazeInfo->status."\n";

	   $TVMazeInfo['Synopsis'] .= "[b]Show Type: [/b]".$RawTVMazeInfo->type."\n";
        if($RawTVMazeInfo->genres) {
        	$TVMazeInfo['Synopsis'] .= "[b]Genres: [/b]".implode(', ', $RawTVMazeInfo->genres)."\n";
        	$TVMazeInfo['Genres'] = implode(', ', $RawTVMazeInfo->genres);
        }

        if($Creators){
          $TVMazeInfo['Synopsis'] .= "[br][b]Created by: [/b]";
        foreach($Creators as $Search){
         if($Search != reset($Creators)) $TVMazeInfo['Synopsis'] .= " | ";
   	     $TVMazeInfo['Synopsis'] .= "[url=";
   	   if($Search->person->url) $TVMazeInfo['Synopsis'] .= $Search->person->url;
   	     $TVMazeInfo['Synopsis'] .= "]";
         $TVMazeInfo['Synopsis'] .= $Search->person->name."[/url] ";
         }
        }

        if($RawTVMazeInfo->officialSite) {
         $TVMazeInfo['Synopsis'] .= "[br][b]Official site: [/b][url=" . $RawTVMazeInfo->officialSite . "]" . preg_replace('#^https?://#', '', parse_url($RawTVMazeInfo->officialSite, PHP_URL_HOST)) . "[/url]";
        }

        $TVMazeInfo['Synopsis'] .= "\n\n";
        $TVMazeInfo['Synopsis'] .= preg_replace('#<[^>]+>#', '', $RawTVMazeInfo->summary);
        $TVMazeInfo['ShowInfo']  = cutAfter(preg_replace('#<[^>]+>#', '', $RawTVMazeInfo->summary),444,' ...');

    }

    return $TVMazeInfo;
}

function get_year($TVMAZE, $Season, &$Debug) {
	global $DB;
   $Year =  json_decode(file_get_contents("http://api.tvmaze.com/shows/".$TVMAZE."/episodebynumber?season=".$Season."&number=1"));
   if($Year->airdate) {
     $Years[$Season] =  date('Y', strtotime($Year->airdate)); // get a year
     $DB->query("DELETE FROM shows WHERE ID='$TVMAZE'"); // refresh db
     $Debug->set_flag('Collected Season Year from TVMaze');
     return $Years[$Season];
   }
}

$UserID = $LoggedUser['ID'];

$Text = new TEXT;

$StaffTools = check_perms('torrents_delete');

$DataStatic = $Cache->get_value('show_static_'.$TVMAZE);

if ($DataStatic) {
    $DataStatic = unserialize($DataStatic);
    list($K, list($PosterURL, $Synopsis, $ShowTitle, $Rating, $Cast, $Years, $NetworkName, $NetworkUrl, $Trailer, $FanArtURL,
    $Updated, $LatestEpisode, $NextEpisode, $Image)) = each($DataStatic);
    $DebugL[] = 'Pulled static data from cache';
}else {
    $DB->query("SELECT PosterURL, Synopsis, ShowTitle, Rating, Cast, Years, NetworkName, WebChannel, NetworkUrl, Trailer, FanArtURL, Updated FROM shows WHERE ID='$TVMAZE'");
    if ($DB->record_count() > 0) {
        list($PosterURL, $Synopsis, $ShowTitle, $Rating, $Cast, $Years, $NetworkName, $WebChannel, $NetworkUrl, $Trailer, $FanArtURL, $Updated) = $DB->next_record();
        $Cast = htmlspecialchars_decode($Cast, ENT_QUOTES);
        $TorrentList='';
        $DataList='';
        if(!is_array($Years)) $Years = unserialize(htmlspecialchars_decode($Years));
        $DebugL[] = 'Pulled all from DB';
    }
}

$Data = $Cache->get_value('show_'.$TVMAZE);

if ($Data) {
    $Data = unserialize($Data);
    list($K, list($TorrentList, $DataList)) = each($Data);
    $DebugL[] = 'Pulled torrent data from cache';
}

if (!is_array($TorrentList)) {
   $DB->query("SELECT tg.ID,
            tg.Image,
            tg.NewCategoryID
            FROM torrents_group AS tg
            JOIN torrents AS t ON tg.ID = t.GroupID
            WHERE tg.TVMAZE='$TVMAZE'
            ORDER BY
            COALESCE(Season, 0) DESC ,
            COALESCE(Episode,0) DESC ,
            AirDate DESC ,
            Size ASC");

   $GroupIDs = $DB->collect('ID');
   $DataList=$DB->to_array('ID', MYSQLI_ASSOC);

   if (count($GroupIDs)>0) {
    $TorrentList = get_groups($GroupIDs);
    $TorrentList = $TorrentList['matches'];
   } else {
    $TorrentList = array();
   }
}

if(!empty($TorrentList)) {

   $ResolutionClean = $Cache->get_value('resolutions');
   if (!$ResolutionClean) {
      $DB->query("SELECT Codec
            FROM torrents_codecs AS tc
            JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
            WHERE Sort >= 100 AND Sort < 200
            ORDER BY tc.Sort");
      $ResolutionClean  = $DB->collect('Codec');
      $Cache->cache_value('resolutions', $ResolutionClean, 3600*12); // cache for 12h
   }

   $CodecClean = $Cache->get_value('codecs');
   if (!$CodecClean) {
      $DB->query("SELECT Codec
            FROM torrents_codecs AS tc
            JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
            WHERE Sort < 100
            ORDER BY tc.Sort");
      $CodecClean  = $DB->collect('Codec');
      $Cache->cache_value('codecs', $CodecClean, 3600*12); // cache for 12h
   }

   $ReleaseGroupClean = $Cache->get_value('release_groups');
   if (!$ReleaseGroupClean) {
      $DB->query("SELECT Codec
            FROM torrents_codecs AS tc
            JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
            WHERE Sort >= 200 AND Sort < 300
            ORDER BY tc.Sort");
      $ReleaseGroupClean  = $DB->collect('Codec');
      $ReleaseGroupClean = array_merge($ReleaseGroupClean, $ReleaseGroups);
	   $Cache->cache_value('release_groups', $ReleaseGroupClean, 3600*12); // cache for 12h
	}
}

if(!is_array($Years)  && !empty($TorrentList)) {
	$TorrentList_Inverted = array_reverse($TorrentList);
   foreach ($TorrentList_Inverted as $GroupID=>$Group) { // fetch season years
       list($GroupID) = array_values($Group);

       $TorrentCache = get_group_info($GroupID, true);
       list( , , , , , , , , , , , , , , , , , , , , , $Season, $Episode, $AirDate) = $TorrentCache[1][0];

       if ($Season && ($Episode=='0'||!$Episode) && ($AirDate=='0000-00-00 00:00:00'||!$AirDate) && !$Years[$Season]) {
          $Years[$Season] = get_year($TVMAZE, $Season, $Debug);
       }
       elseif($AirDate != '0000-00-00 00:00:00' && $Season && !$Years[$Season]) {
          $Years[$Season] =  date('Y', strtotime($AirDate));
       }
   }
}

$TorrentTable = '';
$NumOfSeasons = 0;
$NumOfSnatches = 0;
$TotalSize = 0;

$Bookmarks = all_bookmarks('torrent');

$Debug->set_flag('start build_TorrentList');
foreach ($TorrentList as $GroupID=>$Group) {
    list($GroupID, $GroupName, $TagList, $Torrents) = array_values($Group);
    list( , $Image) = array_values($DataList[$GroupID]);

    $TorrentCache = get_group_info($GroupID, true);
    list( , , $Size, , , $Snatched, , , , , , , , , , , , , , , , $Season, $Episode, $AirDate) = $TorrentCache[1][0];

    $NumOfSnatches += $Snatched;
    $TotalSize += $Size;

    if(empty($PosterURL) && $Season && ($Episode=='0'||!$Episode)) { // season
          list( , , , $Trailer, $Synopsis, , , , , , , , $PosterURL) = array_shift($TorrentCache[0]); // get all from db

       if(empty($NetworkName) && empty($WebChannel)) {
         $NetworkName = get_tvmaze_info($TVMAZE, $Season, $Debug);
         if(!$Synopsis) $Synopsis =  $NetworkName[Synopsis];
         if(!$PosterURL) $PosterURL =  get_poster(secure_link($NetworkName[Poster]), $Debug);
         if(!$WebChannel) $WebChannel =  $NetworkName[WebChannel];
         if(!$Genres) $Genres =  $NetworkName[Genres];
         if(!$ShowInfo) $ShowInfo =  $NetworkName[ShowInfo];
         if(!$Weight) $Weight =  $NetworkName[Weight];
         if(!$Premiered) $Premiered =  $NetworkName[Premiered];
    	   $NetworkName = $NetworkName[Network];
         $Debug->set_flag('Collected NetworkName from TVMaze');
       }

       if(empty($WebChannel) && empty($NetworkName)) {
    	   $WebChannel = get_tvmaze_info($TVMAZE, $Season, $Debug);
         if(!$Synopsis) $Synopsis =  $WebChannel[Synopsis];
         if(!$PosterURL) $PosterURL =  get_poster(secure_link($WebChannel[Poster]), $Debug);
         if(!$NetworkName) $NetworkName =  $WebChannel[Network];
         if(!$Genres) $Genres =  $WebChannel[Genres];
         if(!$ShowInfo) $ShowInfo =  $WebChannel[ShowInfo];
         if(!$Weight) $Weight =  $WebChannel[Weight];
         if(!$Premiered) $Premiered =  $WebChannel[Premiered];
    	   $WebChannel = $WebChannel[WebChannel];
    	   $Debug->set_flag('Collected WebChannel from TVMaze');
       }
          $Debug->set_flag('Collected Poster from DB season found');
    }

    if(empty($PosterURL) && $AirDate && ($Season=='0'||!$Season) && ($Episode=='0'||!$Episode)) {  // daily show
          $PosterURL = get_tvmaze_info($TVMAZE, date('Y', strtotime($AirDate)), $Debug);
          if(!$Synopsis) $Synopsis =  $PosterURL[Synopsis];
          if(!$NetworkName) $NetworkName =  $PosterURL[Network];
          if(!$WebChannel) $WebChannel =  $PosterURL[WebChannel];
          if(!$Genres) $Genres =  $PosterURL[Genres];
          if(!$ShowInfo) $ShowInfo =  $PosterURL[ShowInfo];
          if(!$Weight) $Weight =  $PosterURL[Weight];
          if(!$Premiered) $Premiered =  $PosterURL[Premiered];
          $Debug->set_flag('get_tvmaze_info daily show');
          $PosterURL = get_poster(secure_link($PosterURL[Poster]), $Debug);
          $Debug->set_flag('Collected Poster from TVMaze daily show');
    }

    if(empty($PosterURL) && $Season && $Episode) {  // not a season
          $PosterURL = get_tvmaze_info($TVMAZE, $Season, $Debug);
          if(!$Synopsis) $Synopsis =  $PosterURL[Synopsis];
          if(!$NetworkName) $NetworkName =  $PosterURL[Network];
          if(!$WebChannel) $WebChannel =  $PosterURL[WebChannel];
          if(!$Genres) $Genres =  $PosterURL[Genres];
          if(!$ShowInfo) $ShowInfo =  $PosterURL[ShowInfo];
          if(!$Weight) $Weight =  $PosterURL[Weight];
          if(!$Premiered) $Premiered =  $PosterURL[Premiered];
          $Debug->set_flag('get_tvmaze_info episode');
          $PosterURL = get_poster(secure_link($PosterURL[Poster]), $Debug);
          $Debug->set_flag('Collected Poster from TVMaze not a season');
    }

    if(empty($PosterURL)) $PosterURL = '/static/common/images/no-img-poster.png';

    if(!$Season) $Season = date('Y', strtotime($AirDate)); // get year
    if($Season == '-0001' ) $Season = 1;

    if ($Season && ($Episode=='0'||!$Episode) && ($AirDate=='0000-00-00 00:00:00'||!$AirDate) && !$Years[$Season]) {
       $Years[$Season] = get_year($TVMAZE, $Season, $Debug);
    }

    if(!$Years[$Season]) $Years[$Season] = $Season;

    if(strlen($Years[$Season]) != 4) { // collect year for new season added
      $Years[$Season] = get_year($TVMAZE, $Season, $Debug);
    }

    if($Synopsis) {
       $Synopsis = str_replace('\\','',$Synopsis); // remove extra '\'
       if(substr($Synopsis,0,5) == "&#39;") // cover old way
         $Synopsis = substr($Synopsis,5,-5); // remove extra ' at start and end of the string
    }
 	 if(!$Synopsis) { // not found
       $Synopsis = get_tvmaze_info($TVMAZE, $Season, $Debug);
       if(!$Genres) $Genres =  $Synopsis[Genres];
       if(!$ShowInfo) $ShowInfo =  $Synopsis[ShowInfo];
       if(!$Weight) $Weight =  $Synopsis[Weight];
       if(!$Premiered) $Premiered =  $Synopsis[Premiered];
       $Synopsis = $Synopsis[Synopsis];
       $Debug->set_flag('Collected Synopsis from TVMaze');
    }
    preg_match('/\[url=\]/', $Synopsis, $Empty,PREG_OFFSET_CAPTURE); // foolproof
    if($Empty) $Synopsis = null;

    if($Synopsis) $Synopsis = preg_replace('/network\=A\&E/', 'network=AETV', $Synopsis); // fix A&E

    if(!$Trailer && $Season && ($Episode=='0'||!$Episode) && ($AirDate=='0000-00-00 00:00:00'||!$AirDate)) {
       list( , , , $Trailer) = array_shift($TorrentCache[0]);
       $Debug->set_flag('Collected Trailer');
    }

    if(!$ShowTitle) {
      $ShowTitle = explode(" - " , $GroupName);
      $ShowTitle = $ShowTitle[0];
      $Debug->set_flag('Collected Show Title');
    }

    if(!empty($NetworkName) && $NetworkName != '') {
    	$NetworkUrl =  "static/common/shows/networks/".strtolower(str_replace(' ', '+', $NetworkName)).".png";
    }
    elseif(!empty($WebChannel) && $WebChannel != '') {
    	$NetworkName = $WebChannel;
    	$NetworkUrl =  "static/common/shows/networks/".strtolower(str_replace(' ', '+', $NetworkName)).".png";
    }

    if(strcmp($NetworkName,'A&E') == 0) $NetworkLogoLink = "torrents.php?action=basic&taglist=aetv"; // fix A&E
    elseif(strcmp($NetworkName,'YAHOO! View') == 0) $NetworkLogoLink = "torrents.php?action=basic&taglist=yahoo.view"; // YAHOO! View
    else $NetworkLogoLink = "torrents.php?action=basic&taglist=".strtolower(str_replace(' ', '.', $NetworkName));

    if(!$Rating) {
       $Rating = json_decode(file_get_contents("http://api.tvmaze.com/shows/".$TVMAZE));
       $Rating = $Rating->rating->average;
       $Debug->set_flag('Collected Rating from TVMaze');
    }
    if(!$Rating) $Rating = '-';

    if(!$LatestEpisode) $temp = get_episodes_info($TVMAZE, $Debug);
    if(!$NextEpisode) $NextEpisode = $temp->_embedded->nextepisode;
    if(!$NextEpisode) $NextEpisode = '-'; // not found
    if(!$LatestEpisode) $LatestEpisode = $temp->_embedded->previousepisode;
    if(!$LatestEpisode) $LatestEpisode = '-'; // not found
    unset($temp);

    if(!$Cast) {
    	$Cast = json_decode(file_get_contents("http://api.tvmaze.com/shows/".$TVMAZE."/cast"));
     if($Cast) {
      $Cast2 = "<table><tr>";
      // Data rows
      $i=0;
      foreach($Cast as $key=>$Person){
        if( $i % 1 == 0){
          if($i > 0) $Cast2 .= "</tr><tr>"; // first row
        }
        $Cast2 .= '<td><center><a href=torrents.php?action=person&personid='.$Person->person->id.'><img class="show_page_cast" title="Discover '.
        $Person->person->name.'" src=';
        $CastPosterURL = '';
        if($Person->character->image->medium) { // check if file exists
        	 $CastPosterURL = get_poster(secure_link($Person->character->image->medium), $Debug);
        	 if(!$CastPosterURL) $CastPosterURL = "/static/common/images/noimg.png";
          $Cast2 .= $CastPosterURL;
        }
        elseif($Person->person->image->medium) {
          $CastPosterURL = get_poster(secure_link($Person->person->image->medium), $Debug);
          if(!$CastPosterURL) $CastPosterURL = "/static/common/images/noimg.png";
          $Cast2 .= $CastPosterURL;
        }
        else
         $Cast2 .= "/static/common/images/noimg.png";

        $Cast2 .= "></a><br><b><a href=torrents.php?action=person&personid=".$Person->person->id.">";
        $Cast2 .= $Person->person->name;
        $Cast2 .= "</a></b><br>as<br><a target=_new href=".ANONYMIZER_URL."".$Person->character->url.">";
        $Cast2 .= $Person->character->name;
        $Cast2 .= "</a></center></td>";
        if($i == (count($Cast2)-1))$Cast2 .= "</tr>"; //last row
        $i++;
        if($i == 8){ // cut long cast
     	    $Cast2 .= "</tr>"; //last row
     	    break;
        }
      }
      if(count($Cast) > 8) $Cast2 .= "<tr><td><center><a target=_new href=".ANONYMIZER_URL."https://www.tvmaze.com/shows/".$TVMAZE."/".str_replace(' ', '-', $ShowTitle)."/cast>View full cast list</a></center></td></tr>";
      $Cast2 .= "</table>";
     }
     else { // no cast found
     	$Cast2 = "-";
     }
     $Cast = $Cast2;

     $Debug->set_flag('Collected Cast from TVMaze');
    }

    if(!$Image) $Image = '/static/common/noartwork/noimage.png';

    $Review = get_last_review($GroupID);
        // Handle stats and stuff
    $Number++;
    $NumGroups++;
    if ($UserID == $LoggedUser['ID']) {
        $NumGroupsByUser++;
    }

    $ExtFound = '';
    $ExtSearch = array_map('strtoupper', $Video_FileTypes);
    foreach($ExtSearch as $Search) {
        if(preg_match('/(\b'.$Search.'\b)/i', $TagList, $Matches)) {
             $ExtFound = $Matches[0];
        }
    }
    if(!$ExtFound) $ExtFound = "---";

    $CodecFound = array();
    foreach($CodecClean as $Search){
        if(preg_match('/(\b'.$Search.'\b)/i', $TagList, $Matches)) {
           $CodecFound[] = $Search;
        }
    }
    $CodecFound = array_values(array_unique($CodecFound));
    $Source = $CodecFound[0];
    if(!$Source) $Source = "---";
    $Codec = $CodecFound[1];
    if(!$Codec) $Codec = "---";

    $Resolution = '';
    foreach($ResolutionClean as $Search) {
        if(preg_match('/'.$Search.'/i', $TagList, $Matches)) {
            $Resolution = $Matches[0];
        }
    }
    if(!$Resolution) $Resolution = "---";

    $ReleaseGroup = '';
    foreach($ReleaseGroupClean as $Search) {
        if(preg_match('/('.$Search.').release/i', $TagList, $Matches)) {
            $ReleaseGroup = $Search;
        }
    }
    if(!$ReleaseGroup) $ReleaseGroup = "---";

    // Start an output buffer, so we can store this output in $TorrentTable
    ob_start();

        list($TorrentID, $Torrent) = each($Torrents);

        $DisplayName = $GroupName;
        $DisplayName = explode(" - " , $GroupName);
        $DisplayNameFull = $DisplayName[1];
        $SE = $DisplayName[1];
        if($DisplayName[2]) {
        	  $DisplayNameFull .= " - ".$DisplayName[2]; // get the episode title
        }

        $Icons = torrent_icons($Torrent, $TorrentID, $Review, in_array($GroupID, $Bookmarks), $ShowTitle);

        $row = $row == 'a' ? 'b' : 'a';
        $IsMarkedForDeletion = $Review['Status'] == 'Warned' || $Review['Status'] == 'Pending';

        $DB->query("SELECT r.ID
            FROM reportsv2 AS r
            WHERE TorrentID = $TorrentID
                AND Type != 'edited'
                AND Status != 'Resolved'");
        $IsReported = $DB->record_count();

        // get status from icons
        $Status = '';
        if(!$Status) {
           preg_match('/icon_disk_grabbed/', $Icons, $Status,PREG_OFFSET_CAPTURE);
           if($Status) $Status = 'Downloaded';
        }
        if(!$Status) {
           preg_match('/icon_disk_seed/', $Icons, $Status,PREG_OFFSET_CAPTURE);
           if($Status) $Status = 'Seeding';
        }
        if(!$Status) {
           preg_match('/icon_disk_leech/', $Icons, $Status,PREG_OFFSET_CAPTURE);
           if($Status) $Status = 'Leeching';
        }
        if(!$Status) {
           preg_match('/icon_disk_snatched/', $Icons, $Status,PREG_OFFSET_CAPTURE);
           if($Status) $Status = 'Snatched';
        }
        if(!$Status) $Status='';
?>
<?php if($Prev != $Season) { ?>
  <tr class="colhead">
	    <?php if ($Season != $Years[$Season])
           echo "<td class='season_head'><div class='season'>Season $Season&nbsp;&nbsp;<a style='font-weight: normal;' class=\"seasontoggle\">(Hide)</a></div><div class=\"seasonbox\">$Years[$Season]</span></td>";
       else   // daily shows
           echo "<td class='season_head'><div class='season'>Special</div><span style='font-weight: normal;'> $Years[$Season]</span></td>";
       $Prev = $Season;
       $NumOfSeasons++;
?>
          <td>Size</td>
          <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/snatched.svg" alt="↺" title="Snatches" /></td>
          <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/seeders.svg" alt="∧" title="Seeders" /></td>
          <td class="sign"><img src="static/styles/themes/<?=$LoggedUser['StyleName'] ?>/images/leechers.svg" alt="∨" title="Leechers" /></td>
<?php  } ?>
</tr>
<tr class="torrent <?php if($IsMarkedForDeletion) {
                            echo 'redbar'; }
                         else {
                            if ($IsReported) {
                               echo 'redbar'; }
                            else { echo 'row$row'; }
                         }?>" id="group_<?=$GroupID?>">
        <td style="position:relative; padding:6px;">

<?php   if($PrevSE != $SE) { ?>
<?php   if ($IsReported) { ?>
           <div class="showname" title="Reported"> &#187; <?=$DisplayNameFull?></div>
<?php   } else { ?>
           <div class="showname" title="<?=$DisplayNameFull?>"> &#187; <?=$DisplayNameFull?></div>
<?php   } ?>
<?php      $PrevSE = $SE;
        } ?>
        <div class="tagssh">
<?php   if ($LoggedUser['HideFloat']) { ?>
           <span class="icons"><?=$Icons?></span>
           <a class="codecs <?=$Status?>" href="torrents.php?id=<?=$GroupID?>" title="<?=$Status?>">
<?php         echo $Resolution." / ".$Source." / ".$Codec." / ".$ExtFound." / ".$ReleaseGroup; ?>
           </a>
<?php      } else {
           if ($Status) $GroupNameStatus = '<a class="'.$Status.'Hover">[ '.$Status.' ]</a> ';
           else $GroupNameStatus = '';
           $Overlay = get_overlay_html($GroupNameStatus.$GroupName, anon_username($Torrent['Username'], $Torrent['Anonymous']), $Image, $Torrent['Seeders'], $Torrent['Leechers'], $Torrent['Size'], $Torrent['Snatched'], $Torrent['FilePath']);
?>
           <script>var overlay<?=$GroupID?> = <?=json_encode($Overlay)?></script>
           <span class="icons"><?=$Icons?></span>
           <a class="codecs <?=$Status?>" href="torrents.php?id=<?=$GroupID?>" onmouseover="return overlib(overlay<?=$GroupID?>, FULLHTML);" onmouseout="return nd();">
<?php         echo $Resolution." / ".$Source." / ".$Codec." / ".$ExtFound." / ".$ReleaseGroup; ?>
           </a>
<?php      }  ?>
        </div>
        </td>
        <td style="width:20px;" class="nobr"><?=get_size($Torrent['Size'])?></td>
        <td style="width:10px;"><?=number_format($Torrent['Snatched'])?></td>
        <td style="width:10px;" <?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
        <td style="width:10px;"><?=number_format($Torrent['Leechers'])?></td>
</tr>
<?php
    $TorrentTable.=ob_get_clean();

    ob_start();

    $DisplayName = $GroupName;

?>
        <li class="image_group_<?=$GroupID?>">
            <a href="torrents.php?id=<?=$GroupID?>">
<?php	if ($Image) {
        if (check_perms('site_proxy_images')) {
            $Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
        }
?>
                <img src="<?=$Image?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?>"  />
<?php	} else { ?>
                <div class="noimagepad"><div class="box noimage" title="<?=$DisplayName?>" ><?=$DisplayName?></div></div>
<?php	} ?>
            </a>
        </li>
<?php
    ob_get_clean();

}
$Debug->set_flag('end build_TorrentList');

show_header($ShowTitle, 'overlib,browse,status,jquery.cookie,show','details,show,editgroup');

if(empty($Updated)) $Updated = sqltime();

if($TorrentTable) {

$ShowTitleS = str_replace('\'','&#39;',$ShowTitle); // fix '
$ShowTitle = htmlspecialchars_decode($ShowTitle, ENT_QUOTES); // fix '

if(check_perms('site_torrents_notify')) {

   $IsNotified = in_array_r($ShowTitleS, $LoggedUser['Notify']);

   if($IsNotified) {
      $DB->query("SELECT ID FROM users_notify_filters WHERE UserID='$LoggedUser[ID]' AND Label='".db_string($ShowTitle)."'");
      list($N) = $DB->next_record();
   }
}

if(!$IsFollow) {
   $DB->query("SELECT ShowID FROM follows_shows WHERE UserID='$LoggedUser[ID]' AND ShowID='$TVMAZE'");
   $IsFollow = $DB->record_count();
}

$DB->query("SELECT count(UserID) FROM follows_shows WHERE ShowID='$TVMAZE'");
list($Followed) = $DB->next_record();

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
          if ($_GET['did'] == 3) {
              $ResultMessage ='Successfully edited';
              $AlertClass = '';
          }
       }
?>
   <div id="messagebarA" class="messagebar<?=$AlertClass?>" title="<?=$ResultMessage?>"><?=$ResultMessage?></div>

   <div class="thin show_title_wrap">
    <div class="head"><?=$ShowTitle?>

      <div style="float:right; display:inline; margin-top:6px;">

<?php if(check_perms('site_torrents_notify')) { ?>
<?php   if(!$IsNotified) { ?>
        <a href="#" class="__notify-show" data-label="<?=$ShowTitle?>" data-shows="<?=$ShowTitle?>" data-tvmazeid="<?=$TVMAZE?>" title="Notify of new uploads">
           <span class="icon icon_notify"></span></a>
<?php   }else { ?>
        <a href="#" class="__notify-show" data-label="<?=$ShowTitle?>" data-shows="<?=$ShowTitle?>" data-tvmazeid="<?=$TVMAZE?>" data-id="<?=$N?>"
         title="Do not notify"><span class="icon icon_notify notified"></span></a>
<?php   } ?>
<?php } ?>
      </div>

      <div style="float:right; display:inline; margin-top:5px;">
<?php if(!$IsFollow) { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$TVMAZE?>" title="Follow"><span class="icon icon_follow"></span></a>
<?php }else { ?>
        <a href="#" class="__fav-show" data-favtvmazeid="<?=$TVMAZE?>" title="Following"><span class="icon icon_follow followed"></span></a>
<?php } ?>
      </div>

    </div>
   <div class="main_pad">
    <div class="image_wrap">
      <div class="box" style="height:140px;"><img src="<?=$Image?>" class="banner"></div>
    </div>
   </div>
   <div class="sidebar side_bar">
   <div class="main_pad">
      <div class="head">Cover
      </div>
         <div class="box poster_box">
            <img class="poster" src="<?=$PosterURL?>">
         </div>
   </div>

<?php

$PersonalRating = get_personal_rating($TVMAZE);
if(!$PersonalRating) $PersonalRating = 0;
$AverageRating = get_average_rating($TVMAZE);
if(!$AverageRating) $AverageRating = 0;
$Votes = get_votes($TVMAZE);

?>

   <div class="main_pad">
      <div class="head">Rating</div>
      <div class="box">

  <form action="" method="get" id="ratings">
      <span class="starRating">
<?php for($i=10;$i;$i--) {   ?>
        <input id="rating<?=$i?>" type="radio" name="ratingS" value="<?=$i?>" <?php echo $i==round($AverageRating)?'checked':''?> />
        <label for="rating<?=$i?>" onclick="rate(<?=$TVMAZE?>,<?=$i?>);" onmouseover="set_mouseover('<?=$i?>');" onmouseout="clear_hover();"><?=$i?></label>
<?php } ?>
      </span>
        <br />
     <div class="ratingWrap">
      <table class="ratingTable">
       <tr>
        <td>Average:</td><td><input id="average_rating" readonly="readonly" class="personalRating" style="text-align:left"
        value="<?php if($AverageRating && intval($AverageRating)<10) {
   echo number_format($AverageRating,1); }
else { echo intval($AverageRating); }?>"></td>
        <td>/ 10
         (<input id="votes" class="votes" style="width:50%" value="<?=$Votes?> votes" readonly="readonly" onclick="show_ratings(<?=$TVMAZE?>);" >)
        </td>
       </tr>
       <tr>
        <td>Personal:</td>
        <td>
         <input id="user_rating" value="<?=$PersonalRating?>" class="personalRating" readonly="readonly"  style="text-align:left" />
         <input id="prev_user_rating" value="<?=$PersonalRating?>" class="hidden" />
        </td>
        <td>/ 10
         <input type="button" id="removePersonalRating" data-showid="<?=$TVMAZE?>" value="remove" class='removeB'
         <?php echo empty($PersonalRating)?'style="display:none;"':'';?> title="Remove Personal Rating">
        </td>
       </tr>
       <tr>
        <td>TVMaze:</td>
        <td>
          <input id="tvmaze_rating" value="<?php echo $Rating!='-'? $Rating:'0'; ?>" class="personalRating" readonly="readonly"  style="text-align:left" />
        </td>
        <td>/ 10
<?php   if(strtotime($Updated) < strtotime('today - 1 day')) { // once a day only ?>
         <input type="button" id="refrefhTVMazeRating" data-showid="<?=$TVMAZE?>" value="refresh" class='refreshB' title="Refresh TVMaze Rating">
<?php   } ?>
        </td>
       </tr>
      </table>
     </div>
   </form>
      </div>
   </div>

<?php if($LatestEpisode && $LatestEpisode!='-') { ?>
   <div class="main_pad">
      <div class="head">Latest Episode</div>
        <div class="box episodes">
            <span title="<?=$LatestEpisode->name?>">&#9777; &nbsp;<?=$LatestEpisode->name?></span><br />
            <span  title="Air Date">&larr; &nbsp;<?=date("F j, Y",strtotime($LatestEpisode->airdate))?></span><br />
            # &nbsp;Season <?=$LatestEpisode->season?>,
            Episode <?=$LatestEpisode->number?>
        </div>
   </div>
<?php } ?>

<?php if($NextEpisode && $NextEpisode!='-') { ?>
   <div class="main_pad">
      <div class="head">Next Episode</div>
        <div class="box episodes">
            <span title="<?=$NextEpisode->name?>">&#9777; &nbsp;<?=$NextEpisode->name?></span><br />
            <span  title="Air Date">&rarr; &nbsp;<?=date("F j, Y",strtotime($NextEpisode->airdate))?></span><br />
            # &nbsp;Season <?=$NextEpisode->season?>,
            Episode <?=$NextEpisode->number?>
        </div>
   </div>
<?php } ?>

<?php if($Cast && $Cast!='-') { ?>
   <div class="main_pad">
      <div class="head">Cast
         <span style="float:right;"><a href="#" id="casttoggle" onclick="Cast_Toggle(); return false;">(Hide)</a></span>
      </div>
        <div class="box">
          <div id="castbox">
            <?=html_entity_decode(($Cast))?>
          </div>
        </div>
   </div>
<?php } ?>
   </div>
   <div class="main synopsis">
<?php if($Synopsis || $TVMAZE >= 959500) { // custom show included ?>
   <div class="main_pad">
<?php if($StaffTools) { ?>
  <form action="" method="post" id="refresh">
<?php } ?>
    <div class="head">Show Info
<?php if($StaffTools) { ?>
       <span style="margin-left:2px;"><a href="#" id="showinfotoggle" onclick="ShowInfo_Toggle(); return false;">(Hide)</a></span>
       <input type="hidden" name="action" id="action" value="refresh" />
       <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
       <input type="hidden" name="showid" value="<?=$TVMAZE?>" />
       <div style="float:right;">
         Updated: <?=time_diff($Updated)?>
<?php if($TVMAZE < 959500) { // custom show excluded ?>
         <input type="submit" class="submit2" value="Reconstruct!" id="submitForm" />
<?php } ?>
         <input type="submit" class="edit" id="edit_button" value="Edit">
         <input type="submit" class="submit1" value="Refresh!" id="submitFormCache" />
       </div>
<?php } else { ?>
       <span style="float:right;"><a href="#" id="showinfotoggle" onclick="ShowInfo_Toggle(); return false;">(Hide)</a></span>
<?php } ?>
    </div>
<?php if($StaffTools) { ?>
  </form>
<?php } ?>
  <div id="showinfobox">
    <div class="networklogowrapper">
<?php if(file_exists(SERVER_ROOT . "/".$NetworkUrl)){?>
         <a href="<?=$NetworkLogoLink?>"><img class="showmazenetworklogo" src="<?=$NetworkUrl?>" title="More shows from this network"></a>
<?php } ?>
    </div>
     <div class="box" style="padding:20px;"><?=$Text->full_format($Synopsis)?></div>
  </div>
 </div>
<?php } ?>
<?php if($FanArtURL) { ?>
  <div class="main_pad">
   <div class="head">Show FanArt
     <span style="float:right;"><a href="#" id="fanarttoggle" onclick="FanArt_Toggle(); return false;">(Hide)</a></span>
   </div>
     <div class="box">
	<img id="fanartbox" src="<?=$FanArtURL?>" alt="This is cool. Like really cool!" style="width:100%;max-width:360px">

<!-- The Modal -->
<div id="myModal" class="modal">

  <!-- The Close Button -->
  <span class="close">&times;</span>

  <!-- Modal Content (The Image) -->
  <img class="modal-content" id="img01">

  <!-- Modal Caption (Image Text) -->
  <div id="caption"></div>
</div>

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the image and insert it inside the modal - use its "alt" text as a caption
var img = document.getElementById("fanartbox");
var modalImg = document.getElementById("img01");
var captionText = document.getElementById("caption");
img.onclick = function(){
  modal.style.display = "block";
  modalImg.src = this.src;
  captionText.innerHTML = this.alt;
}

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}
</script>
     </div>
  </div>
<?php } ?>
<?php /* <?php if($Trailer) { ?>
  <div class="main_pad">
   <div class="head">Trailer
     <span style="float:right;"><a href="#" id="trailertoggle" onclick="Trailer_Toggle(); return false;">(Hide)</a></span>
   </div>
     <div class="box">
        <div id="trailerbox">
           <?=$Text->full_format($Trailer)?>
        </div>
     </div>
  </div>
<?php } ?>*/ ?>
  <div class="main_pad">
   <div class="head">Media</div>
       <table class="torrent_table" id="discog_table">
         <?=$TorrentTable?>
       </table>
   </div>

   <div class="head">Statistics</div>
     <div class="box">
      <table>
       <tr>
        <td>Total seasons: <?=$NumOfSeasons?></td>
        <td>Total torrents: <?=count($TorrentList)?></td>
        <td>Total snatches: <?=$NumOfSnatches?></td>
        <td>Total group size: <?=get_size($TotalSize)?></td>
       </tr>
       <tr>
        <td colspan="4">Users following: <?=$Followed?></td>
       </tr>
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

if($TorrentTable) {
   // save to db
   $DB->query("SELECT ID FROM shows WHERE ID='$TVMAZE'");
   if ($DB->record_count() == 0) {

      $sqltime = $Updated = db_string( sqltime() );
      $YearsL = serialize($Years);

      $DB->query("INSERT IGNORE INTO shows
          (ID, ShowTitle, Synopsis, ShowInfo, Genres, NetworkName, WebChannel, NetworkUrl, PosterUrl, Rating, Weight, Cast, Years, Trailer, Premiered, Updated)
          VALUES
          ('$TVMAZE', '" . db_string($ShowTitle) . "', '" . db_string($Synopsis) . "', '" . db_string($ShowInfo) . "', '" . db_string($Genres) . "', '"
          . db_string($NetworkName) . "', '" . db_string($WebChannel) . "', '$NetworkUrl', '$PosterURL', '$Rating', '$Weight', '" . db_string($Cast)
          . "','$YearsL','$Trailer', '$Premiered','$sqltime')");

      write_log("Show $TVMAZE ($ShowTitle) was scraped by ".$LoggedUser['Username']);
   }

   $Cache->cache_value('show_static_'.$TVMAZE, serialize(array(array($PosterURL, $Synopsis, $ShowTitle, $Rating, $Cast, $Years,
   $NetworkName, $NetworkUrl, $Trailer, $FanArtURL, $Updated, $LatestEpisode, $NextEpisode, $Image))), 3600*24); // cache for 24h
}

if($TorrentTable) {
   $Cache->cache_value('show_'.$TVMAZE, serialize(array(array($TorrentList, $DataList))), 3600*3); // cache for 3h
}

//if($StaffTools) var_dump($DebugL);
