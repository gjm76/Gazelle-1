<?php

function checkRemoteFile($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if(curl_exec($ch)!==FALSE)
    {
        return true;
    }
    else
    {
        return false;
    }
}

function URL_exists($url){
   $headers=get_headers($url);
   return stripos($headers[0],"200 OK")?true:false;
}

function get_trusted_group($GroupName){

   if (empty($GroupName)) return false; // empty

   global $DB;
   $Limit = 50; // limit results
   
   // get group id
   $DB->query("SELECT ID
            FROM groups
            WHERE Name='$GroupName'");
            
   if ($DB->record_count() == 0) return false; // not found
   
   list($GroupID) = $DB->next_record();


   // main query
   $DB->query("SELECT
      SQL_CALC_FOUND_ROWS
      u.UserID
      FROM users_groups AS u
      JOIN users_main AS m ON u.UserID=m.ID
      JOIN users_info AS i ON u.UserID=i.UserID
      WHERE u.GroupID=$GroupID
      ORDER BY m.Username ASC LIMIT $Limit");

   $Users = $DB->collect('UserID'); // collect users

   $DB->query('SELECT FOUND_ROWS()');
   if ($DB->record_count() == 0) return false; // empty list

   return $Users;
}

function set_review_okay($GroupID, $UserID, $LoggedUser) {
	
   global $DB, $Cache;
   	
	$Reason     = "Trusted Uploader";
   $Status     = "Okay";
   $ReasonID   = -1;
   $KillTime   ='0000-00-00 00:00:00';
   $LogDetails = "Marked as Okay";

   $DB->query("INSERT INTO torrents_reviews (GroupID, ReasonID, UserID, ConvID, Time, Status, Reason, KillTime)
        VALUES ($GroupID, $ReasonID, ".db_string($LoggedUser['ID']).", ".($ConvID?$ConvID:"null").", '$Time', '$Status', '".db_string($Reason)."', '".sqltime($KillTime)."')");

//   $Cache->delete_value('torrent_review_' . $GroupID);
//   $Cache->delete_value('staff_pm_new_' . $UserID);
   
}

function check_size_dupes($TorrentFilelist, $ExcludeID=0)
{
    global $SS, $ExcludeBytesDupeCheck, $Image_FileTypes;

    $SS->limit(0, 10, 10);
    $SS->SetSortMode(SPH_SORT_ATTR_DESC, 'time');
    $SS->set_index(SPHINX_INDEX . ' delta');

    $AllResults=array();
    $UniqueResults = 0;

    foreach ($TorrentFilelist as $File) {
        list($Size, $Name) = $File;

        // skip matching files < 1mb in size
        if ($Size < 1024*1024*2) continue;

        // skip image files
        preg_match('/\.([^\.]+)$/i', $Name, $ext);
        if (in_array($ext[1], $Image_FileTypes)) continue;

        if (isset($ExcludeBytesDupeCheck[$Size])) {
            $FakeEntry = array( array( 'excluded'=> $ExcludeBytesDupeCheck[$Size],
                                       'dupedfileexact'=>$Name,
                                       'dupedfile'=>"$Name (".  get_size($Size).")" ) );
            $AllResults = array_merge($AllResults, $FakeEntry);
            continue;
        }

        $Query = '@filelist "' . $SS->EscapeString($Size) .'"';  // . '"~20';

        $Results = $SS->search($Query, '', 0, array(), '', '');
        $Num = $SS->TotalResults;
        if ($Num>0) {
            // These ones were not found in the cache, run SQL
            if (!empty($Results['notfound'])) {

                $SQLResults = get_groups($Results['notfound']);

                if (is_array($SQLResults['notfound'])) { // Something wasn't found in the db, remove it from results
                    reset($SQLResults['notfound']);
                    foreach ($SQLResults['notfound'] as $ID) {
                        unset($SQLResults['matches'][$ID]);
                        unset($Results['matches'][$ID]);
                    }
                }
                // Merge SQL results with sphinx/memcached results
                foreach ($SQLResults['matches'] as $ID => $SQLResult) {
                    $Results['matches'][$ID] = array_merge($Results['matches'][$ID], $SQLResult);
                    ksort($Results['matches'][$ID]);
                }
            }
            foreach ($Results['matches'] as $ID => $tdata) {
                if ($tdata['ID']==$ExcludeID) {
                    unset($Results['matches'][$ID]);
                } elseif ( (time_ago($tdata['Torrents'][$ID]['Time']) > 24*3600*EXCLUDE_DUPES_AFTER_DAYS) &&
                            ($tdata['Torrents'][$ID]['Seeders']< EXCLUDE_DUPES_SEEDS) ) {
                    unset($Results['matches'][$ID]);
                } else {
                    $Results['matches'][$ID]['dupedfile'] = "$Name (".  get_size($Size).")";
                }
            }
            if (count($Results['matches'])>0) {
                $UniqueResults++;
                $AllResults = array_merge($AllResults, $Results['matches']);

                if (count($AllResults)>=500) break;
            }
        }
    }
    $NumFiles = count($TorrentFilelist);
    if(count($AllResults)<1) return array('UniqueMatches'=>0, 'NumChecked'=>$NumFiles, 'DupeResults'=>false);

    return array('UniqueMatches'=>$UniqueResults, 'NumChecked'=>$NumFiles, 'DupeResults'=>$AllResults) ;
}

function get_templates_private($UserID)
{
    global $DB, $Cache;

    $UserTemplates = $Cache->get_value('templates_ids_' . $UserID);
    if ($UserTemplates === FALSE) {
                        $DB->query("SELECT
                                    t.ID,
                                    t.Name,
                                    t.Public,
                                    u.Username
                               FROM upload_templates as t
                          LEFT JOIN users_main AS u ON u.ID=t.UserID
                              WHERE t.UserID='$UserID'
                                AND Public='0'
                           ORDER BY Name");
                        $UserTemplates = $DB->to_array();
                        $Cache->cache_value('templates_ids_' . $UserID, $UserTemplates, 96400);
    }

    return $UserTemplates;
}

function get_templates_public()
{
    global $DB, $Cache;
    $PublicTemplates = $Cache->get_value('templates_public');
    if ($PublicTemplates === FALSE) {
                        $DB->query("SELECT
                                    t.ID,
                                    t.Name,
                                    t.Public,
                                    u.Username
                               FROM upload_templates as t
                          LEFT JOIN users_main AS u ON u.ID=t.UserID
                              WHERE Public='1'
                           ORDER BY Name");
                        $PublicTemplates = $DB->to_array();
                        $Cache->cache_value('templates_public', $PublicTemplates, 96400);
    }

    return $PublicTemplates;
}

function reparse_title(&$Title, &$Tags, &$Append, $FileList, &$TVMazeID, &$Debug, $Rules=NULL){
    global $DB;
    if(is_null($Rules)){
        $DB->query("SELECT Rules FROM torrents_parser WHERE ID=1");
        list($Rules) = $DB->next_record();
        $Rules = base64_decode($Rules);
    }

    $Rules = json_decode($Rules);

    foreach($Rules as $Sort => $Rule) {
        $Matches=array();

        if(!preg_match("/.*($Rule->pattern).*/i", $Title, $Matches))
            continue;

        // Apply reparser actions
        if($Rule->overwrite == 'on') $Title = preg_replace("/($Rule->pattern)/i", $Rule->replace, $Title);       
        if($Rule->tag       == 'on') $Tags[] = $Rule->replace;
        if($Rule->append    == 'on') $Append[$Sort] = $Matches[1];
        if($Rule->tvmazeid         ) $TVMazeID = $Rule->tvmazeid;

        $Debug[] = ["Matched parser rule: $Sort",
        preg_replace("/$Rule->pattern/i", '[color=#F00]'.$Matches[1].'[/color]', $Matches[0]),
        $Title, implode('|', $Tags)];

        // Do break after debug
        if($Rule->break     == 'on') break;
    }
    $Debug[]=['Reparsed title', '[u][b][size=2]'.$Title.'[/size][/b][/u]'];
    $Debug[]=['Reparsed tags',  '[b][size=2]'.implode('|', $Tags).'[/size][/b]'];
    $Debug[]=['Append data',   '[b][size=2]'.implode(' ', $Append).'[/size][/b]'];
    $Debug[]=['TVMaze ID',   '[b][size=2]'. $TVMazeID.'[/size][/b]'];
}

function get_tvmaze_show_info($Title, $TVMazeID, &$Debug) {
    reparse_title($Title, $Tags, $Append, $FileList, $TVMazeID, $Debug);
    
    if($TVMazeID) $TVMAZE_URL = "http://api.tvmaze.com/shows/$TVMazeID";
    else $TVMAZE_URL = "http://api.tvmaze.com/singlesearch/shows?q=".urlencode($Title);
    $Debug[] = "Querying TVMaze with: $TVMAZE_URL";

    $RawTVMazeInfo = json_decode(file_get_contents($TVMAZE_URL));
    $TVMazeInfo['Title']   = $RawTVMazeInfo->name;
    $TVMazeInfo['Append']  = implode(' ', $Append);
    $TVMazeInfo['ID']      = $RawTVMazeInfo->id;
    $TVMazeInfo['IMDb']    = $RawTVMazeInfo->externals->imdb;
    $TVMazeInfo['TMDb']    = '';

    // Process TVMaze Network name
    $TVMazeInfo['Network'][] = $RawTVMazeInfo->network->name;
    $TVMazeInfo['Network'][] = $RawTVMazeInfo->webChannel->name;
    $TVMazeInfo['Network']   = implode('|', $TVMazeInfo['Network']);
    
    // Collect the TVMaze tag list
    $TVMazeInfo['Tags']   = array_merge((array)$Tags, (array)$RawTVMazeInfo->genres);
    if($RawTVMazeInfo->language && $RawTVMazeInfo->language != 'English') $TVMazeInfo['Tags'][] = $RawTVMazeInfo->language;
    $TVMazeInfo['Tags'][] = $RawTVMazeInfo->type;
    $TVMazeInfo['Tags'][] = $TVMazeInfo['Network'];
    
    // Process the tag list
    $TVMazeInfo['Tags']   = implode('|', $TVMazeInfo['Tags']);
    $TVMazeInfo['Tags']   = str_replace(' ', '.', $TVMazeInfo['Tags']);
    $TVMazeInfo['Tags']   = str_replace('-', '.', $TVMazeInfo['Tags']);
    $TVMazeInfo['Tags']   = str_replace('|', ' ', $TVMazeInfo['Tags']);
    return $TVMazeInfo;
}

function get_tvmaze_episode_info($ID, $Season, $Episode, $Date, &$Debug) {

    // Episode by date (talk shows)
    if(!empty($Date)) {
        $Debug[] = "Performing date search";
        $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/shows/$ID/episodesbydate?date=$Date"))[0];
        $Debug[] = "Got season: ".$RawTVMazeInfo->season;
        $Debug[] = "Got episode: ".$RawTVMazeInfo->number;
        $Season = $RawTVMazeInfo->season;
        $Episode = $RawTVMazeInfo->number;

        $TVMazeInfo['AirDate']  = $RawTVMazeInfo->airdate;
        $TVMazeInfo['Title']    = $RawTVMazeInfo->name;
        $TVMazeInfo['Synopsis'] = preg_replace('#<[^>]+>#', '', $RawTVMazeInfo->summary); // TVMaze has horrible HTML in synopsis
        $TVMazeInfo['Poster']   = $RawTVMazeInfo->image->medium;
    } else

    // Episode by number
    if(!empty($Season) && !empty($Episode)) {
        $Debug[] = "Performing TVMaze lookup";
        $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/shows/$ID/episodebynumber?season=$Season&number=$Episode"));

        $TVMazeInfo['AirDate']  = $RawTVMazeInfo->airdate;
        $TVMazeInfo['Title']    = $RawTVMazeInfo->name;
        $TVMazeInfo['Synopsis'] = preg_replace('#<[^>]+>#', '', $RawTVMazeInfo->summary); // TVMaze has horrible HTML in synopsis
        $TVMazeInfo['Poster']   = $RawTVMazeInfo->image->medium;
    } else

    // Season by number
    if(!empty($Season) && empty($Episode)) {
        $Debug[] = "Performing TVMaze lookup";
        $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/shows/$ID?&embed[]=episodes&embed[]=seasons&embed[]=crew"));
        
        /* This doesn't always succeed; v1 of the API is way past its sunset date, and we usually get episode summaries from tvmaze anyway
        $tvdb = $RawTVMazeInfo->externals->thetvdb;
        if(is_numeric($tvdb)) {          
           $ch = curl_init();
           $timeout = 5;
           curl_setopt($ch, CURLOPT_URL, "http://thetvdb.com/api/2FB450F80319F388/series/$tvdb/all/en.xml");
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
           curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
           $RawTVDBInfo = curl_exec($ch);
           $RawTVDBInfo = new SimpleXMLElement($RawTVDBInfo);
           curl_close($ch);
        }*/

        // Grab show poster first, overwrite with season poster if it's available
        $TVMazeInfo['Poster'] = $RawTVMazeInfo->image->medium;
        
        //grab network name
        $TVMazeInfo['Network'] = $RawTVMazeInfo->network->name;
        $TVMazeInfo['WebChannel'] = $RawTVMazeInfo->webChannel->name;

        //getting crew 
        $Crew =  $RawTVMazeInfo->_embedded->crew;

        // looking for creators
        foreach($Crew as $Search){
   	   if($Search->type != 'Creator') continue;
           $Creators[] = $Search;
        }
   
        // Start of cluster 1
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

        $TVMazeInfo['Synopsis'] .= "[b]Show Type: [/b]".$RawTVMazeInfo->type."\n";
        if($RawTVMazeInfo->genres) $TVMazeInfo['Synopsis'] .= "[b]Genres: [/b]".implode(', ', $RawTVMazeInfo->genres)."\n";
        
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
        // End of current cluster 1

        $RawTVMazeInfo = $RawTVMazeInfo->_embedded;
        foreach($RawTVMazeInfo->seasons as $RawSeason){
            if (intval($RawSeason->number) == intval($Season) && !empty($RawSeason->image->medium)) {
                $TVMazeInfo['Poster'] = $RawSeason->image->medium;
            }
        }

        // Start of new cluster 2
        $TVMazeInfo['EpisodeGuide'] = "";
        foreach($RawTVMazeInfo->episodes as $episode) {
            if($episode->season == $Season) {
                $TVMazeInfo['EpisodeGuide'] .= "[b][url=".$episode->url."]".$episode->name."[/url][/b]\n";
                $TVMazeInfo['EpisodeGuide'] .= "[b]Episode:[/b] ".str_pad($episode->season, 2, '0', STR_PAD_LEFT);
                $TVMazeInfo['EpisodeGuide'] .= "x".str_pad($episode->number, 2, '0', STR_PAD_LEFT);
                $TVMazeInfo['EpisodeGuide'] .= " | [b]Aired:[/b] " . $episode->airdate . "\n";
                if(!empty($episode->summary)) {
                  $TVMazeInfo['EpisodeGuide'] .= preg_replace('#<[^>]+>#', '', $episode->summary);
                } /*else {
                  foreach($RawTVDBInfo->Episode as $TVDBEpisode) {
                    if($TVDBEpisode->SeasonNumber == $Season && $TVDBEpisode->EpisodeNumber == $episode->number)
                      $TVMazeInfo['EpisodeGuide'] .= $TVDBEpisode->Overview;
                  }
                }*/
                $TVMazeInfo['EpisodeGuide'] .= "\n\n";
            }
        }
        // End of second cluster 2
    }

    return $TVMazeInfo;
}

function upload_to_imagehost($ImageURL, $Debug=false) {

   global $SiteOptions;

   $parts = parse_url($ImageURL); // get image filename
   $filename = basename($parts['path']);
   //$filename = str_replace('@', '', $filename); // imdb fix

                                  // create imagehost filename
   $filename = $SiteOptions['ImagehostURL'] . "/images/" . $filename; 

   //if(URL_exists($filename))      // check if exists
   if(checkRemoteFile($filename))      // check if exists
      return $filename;
	
   if(!empty($SiteOptions['ImagehostURL']) && !empty($SiteOptions['ImagehostKey'])){
        $url = $SiteOptions['ImagehostURL'].'/api/1/upload';
        $post_data = array('key'    => $SiteOptions['ImagehostKey'],
                           'source' => $ImageURL,
                           'format' => 'json');

        $post_fields='';
        foreach($post_data as $key=>$value) { $post_fields .= $key.'='.$value.'&'; }
        rtrim($post_fields, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, count($post_data));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $post_fields);
   curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_AUTOREFERER, true);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
   curl_setopt($ch, CURLOPT_TIMEOUT, 10);
 
	//execute post
	$chev_image = json_decode(curl_exec($ch));

	//close connection
	curl_close($ch);

        if($Debug) {
            header('Content-Type: application/json');
            echo json_encode($chev_image);
            die();
        }

        return $chev_image->image->url;
   }
   return;
}

function parse_title(&$Properties, &$FileList, &$TVMazeID, &$SeasonID, &$EpisodeID, &$Debug, &$AirDate) 
{
    global $DB, $Video_FileTypes, $ReleaseGroups, $Sub_FileTypes;

    if(empty($Properties)) return;

    // Process the title string "intelligently"
    // Get all codec/source info
    $DB->query("SELECT AltCodec, Codec
                  FROM torrents_codecs AS tc
                  JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                 WHERE Sort < 100
              ORDER BY tc.Sort");

    $CodecSearch = $DB->collect('AltCodec');
    $CodecClean  = $DB->collect('Codec');

    $DB->query("SELECT AltCodec, Codec
                  FROM torrents_codecs AS tc
                  JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                 WHERE Sort >= 100 AND Sort < 200
              ORDER BY tc.Sort");

    $ResolutionSearch = $DB->collect('AltCodec');
    $ResolutionClean  = $DB->collect('Codec');

    $DB->query("SELECT AltCodec, Codec
                  FROM torrents_codecs AS tc
                  JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                 WHERE Sort >= 200 AND Sort < 300
              ORDER BY tc.Sort");

    $ReleaseGroupSearch = $DB->collect('AltCodec');
    $ReleaseGroupClean  = $DB->collect('Codec');

    $DB->query("SELECT AltCodec, Codec
                  FROM torrents_codecs AS tc
                  JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                 WHERE Sort >= 300
              ORDER BY tc.Sort");

    $ProperTagSearch = $DB->collect('AltCodec');
    $ProperTagClean  = $DB->collect('Codec');

    $CodecFound   = array();
    $Debug[] = "Title: $Properties[Title]";

    foreach($CodecSearch as $Search){
        if(preg_match('/(\b'.$Search.'\b)/i', $Properties['Title'], $Matches)) {
            $CodecFound[] = $Matches[0];
            $Debug[] = "Found codec: $Matches[0]";
        }        
    }

    // correcting WEB to WebDL
    $StrTemp = strtoupper($CodecFound[0]);
    if((strcmp($StrTemp,'WEB.'))==0 || (strcmp($StrTemp,'WEB '))==0) {
        $CodecFound[0] = 'WebDL';
        $Debug[] = "Corrected WEB to WebDL";
    }

    $Debug[] = "Found codecs: ".implode(' ', $CodecFound);

    // Process the FileName first, title can override.
    foreach($ResolutionSearch as $Search) {
        if(preg_match('/'.$Search.'/i', $Properties['FileName'], $Matches)) {
            $Resolution = $Matches[0];
            $Debug[] = "Found resolution: $Matches[0]";
        }
    }
    foreach($ResolutionSearch as $Search) {
        if(preg_match('/'.$Search.'/i', $Properties['Title'], $Matches)) {
            $Resolution = $Matches[0];
            $Debug[] = "Found resolution: $Matches[0]";
        }
    }
    
    // Get the Release Group old way
    foreach($ReleaseGroups as $Search) {
        if(preg_match('/-('.$Search.')\b/i', $Properties['FileName'], $Matches)) {
            $ReleaseGroupOld[] = $Matches[1];
            $Debug[] = "Found Release Group Old: $Matches[1]";
        }
    }

    // Get the Release Group
    foreach($ReleaseGroupSearch as $Search) {
        if(preg_match('/-('.$Search.')\b/i', $Properties['FileName'], $Matches)) {
            $ReleaseGroup[] = $Matches[1];
            $Debug[] = "Found Release Group: $Matches[1]";
        }
    }    

    // Get the Repack / Proper tag
    foreach($ProperTagSearch as $Search) {
        if(preg_match('/('.$Search.')\b/i', $Properties['FileName'], $Matches)) {
            $ProperTag[] = $Matches[1];
            $Debug[] = "Found Proper Tag: $Matches[1]";
        }
    }

    $ExtSearch = array_map('strtoupper', $Video_FileTypes);
    foreach($FileList as $File) {
        list($Size, $Name) = $File;

        foreach($ExtSearch as $Search) {
            if(preg_match('/.(\b'.$Search.'\b)/i', $Name, $Matches)) {
                $CodecFound[] = $Matches[1];
                $Debug[] = "Found extension: $Matches[1]";
            }
        }

        // look for subtitles to tag them    
       foreach($Sub_FileTypes as $Search){
    	     if (preg_match('/\.'.$Search.'/i', $Name)) {
                $Properties['TagList'] .= " subtitles ";
                $Debug[] = "Found subtitles in files";
    	     }
       }	
    }

    // look for subtitles to tag in description
    if (preg_match('/Text\s[\#\d,\sID]/', $Properties['MediaInfo'])) {
         $Properties['TagList'] .= " subtitles ";
         $Debug[] = "Found subtitles";
    }

    // Strip the codec info
    $TitleEndSearch = implode('|', array_merge($CodecSearch, $ExtSearch, $ResolutionSearch));
    if(preg_match('/'.$TitleEndSearch.'/', $Properties['Title'], $Matches, PREG_OFFSET_CAPTURE))
        $Properties['Title'] = substr($Properties['Title'], 0 ,$Matches[0][1]);

    // Get the Episode info
    $EpisodeSearch = array("[\d]E([\d]+)", "Episode([\d]+)", "[\d]+x([\d]+)");
    foreach($EpisodeSearch as $Search) {
        if(preg_match('/'.$Search.'/i', $Properties['Title'], $Matches)) {
            $Properties['Episode'] = $Matches[1];
            $Debug[] = "Found episode: $Matches[1]";
        }
    }

    if(!empty($EpisodeID)) $Properties['Episode'] = $EpisodeID; // correct if requested

    // Get the Season info
    $SeasonSearch = array("S([\d]+)", "Season([\d]+)", "([\d]+)x[\d]+");
    foreach($SeasonSearch as $Search) {
        if(preg_match('/'.$Search.'/i', $Properties['Title'], $Matches)) {
            $Properties['Season'] = $Matches[1];
            $Debug[] = "Found season: $Matches[1]";
        }
    }

    if(!empty($SeasonID)) $Properties['Season'] = $SeasonID; // correct if requested

    // Get Date info for Daily shows
    $DateSearch = array("(\d{4}\.\d{2}\.\d{2})", "(\d{4}-\d{2}-\d{2})", "(\d{4}\s\d{2}\s\d{2})");
    foreach($DateSearch as $Search) {
        if(preg_match('/'.$Search.'/i', $Properties['Title'], $Matches)) {
            $Properties['Date'] = $Matches[1];
            $Debug[] = "Found date: $Matches[1]";
        }
    }

    // Strip Season & Episode info
    $TitleEndSearch = implode('|', array_merge($SeasonSearch, $EpisodeSearch, $DateSearch));
    if(preg_match('/'.$TitleEndSearch.'/i', $Properties['Title'], $Matches, PREG_OFFSET_CAPTURE))
        $Properties['Title'] = substr($Properties['Title'], 0 ,$Matches[0][1]);

    // Convert dots to spaces
    $Properties['Title'] = str_replace('.', ' ', $Properties['Title']);
    $Properties['Title'] = str_replace('_', ' ', $Properties['Title']);

    // Clean date a bit
    $Properties['Date'] = str_replace('.', '-', $Properties['Date']);
    $Properties['Date'] = str_replace('_', '-', $Properties['Date']);
    $Properties['Date'] = str_replace(' ', '-', $Properties['Date']);

    // Strip site tags
    $Properties['Title'] = preg_replace('/\[[^\]]*\]/', '', $Properties['Title']);
    $Properties['Title'] = trim($Properties['Title']);

    $Debug[] = "Cleaned title to: $Properties[Title]";

    if(!empty($TVMazeID)) $TVMaze['ID'] = $TVMazeID; // correct if requested

    // TVMaze show stuff now (performs title parsing)
    $TVMaze = get_tvmaze_show_info($Properties['Title'], $TVMaze['ID'], $Debug);
    if(!empty($TVMaze['Title']))    $Properties['Title']   = $TVMaze['Title'];
    if(!empty($TVMaze['Network']))  $Properties['Network'] = $TVMaze['Network'];
    if(!empty($TVMaze['Tags']))     $Properties['TagList'] = implode(' ', [$TVMaze['Tags'], $Properties['TagList']]);
    if(!empty($TVMaze['Append']))   $Properties['Append']  = $TVMaze['Append'];

    // Do fancy Stuff
    if(!empty($TVMaze['ID'])) {
        $Properties['TVMAZE'] = $TVMaze['ID'];
        $Properties['IMDb'] = $TVMaze['IMDb'];
        $Properties['TMDb'] = $TVMaze['TMDb'];

        $Debug[] = "TVMaze id is: $TVMaze[ID]";

        if($AirDate && $AirDate!='0000-00-00' && !$SeasonID && !$EpisodeID) { // correct by AirDate if requested, can override
    	    $Properties['Date'] = $AirDate;
    	    $Properties['Season'] = '';
    	    $Properties['Episode'] = '';
        }	

        if((!$AirDate || $AirDate=='0000-00-00') && $SeasonID && $EpisodeID) { // correct Season+Episode if requested, can override AirDate
    	    $Properties['Date'] = '';
    	    $Properties['Season'] = $SeasonID;
    	    $Properties['Episode'] = $EpisodeID;
        }

        if((!empty($Properties['Season']) || !empty($Properties['Episode'])) || !empty($Properties['Date'])) {
            $TVMazeEpisode = get_tvmaze_episode_info($TVMaze['ID'], $Properties['Season'], $Properties['Episode'], $Properties['Date'], $Debug);
            
            $S = intval($Properties['Season']);
            $E = intval($Properties['Episode']);
            $T = $TVMaze['ID'];
            
            $Debug[] = "TVMaze Poster url is: $TVMazeEpisode[Poster]";

            if($S && !$E) { // for season use the saved poster            

             $DB->query("SELECT tg.PosterURL
                        FROM torrents_group AS tg
                        JOIN torrents AS t on t.GroupID = tg.ID
                        WHERE tg.TVMAZE = $T
                        AND t.Season = $S
                        AND t.Episode IS NULL
                        AND tg.PosterURL != ''
                        AND tg.PosterURL IS NOT NULL
                        ORDER BY tg.Time ASC
                        LIMIT 1");
            }
            else {
            	   
              $DB->query("SELECT tg.PosterURL
                        FROM torrents_group AS tg
                        JOIN torrents AS t on t.GroupID = tg.ID
                        WHERE tg.TVMAZE = $T
                        AND t.Episode = $E
                        AND t.Season = $S
                        AND tg.PosterURL != ''
                        AND tg.PosterURL IS NOT NULL
                        ORDER BY tg.Time ASC
                        LIMIT 1");
            }
                                               
            list($PosterURL) = $DB->next_record();
            $Debug[] = "DB Poster url is: $PosterURL";

            /*
             * Okay, we don't have a poster, so we need to upload it to
             * to our imagehost and stash the URL. But before we check if its already on imagehost
             */
            if(empty($PosterURL)) {
                $PosterURL = upload_to_imagehost($TVMazeEpisode['Poster']);
                $Debug[] = "Saved on imagehost Poster url is: $PosterURL";
            }
        }
    }

    $Properties['Synopsis']  = $TVMazeEpisode['Synopsis'];
    $Properties['EpisodeGuide']  = $TVMazeEpisode['EpisodeGuide'];
    $Properties['AirDate']   = $TVMazeEpisode['AirDate'];
    if(empty($Properties['AirDate']) && !empty($Properties['Date'])) $Properties['AirDate'] = $Properties['Date']; // save date for show grouping
    $Properties['SubTitle']  = $TVMazeEpisode['Title'];
    $Properties['PosterURL'] = $PosterURL;

    if($Properties['Season'] == "01" && $Properties['Episode'] == "01"){
    	 $Properties['FreeLeech'] = '1';
    	 $Properties['TagList'] .= " pilot "; // add a pilot tag
    }
    
    // Rebuild the title
    if(!empty($Properties['Season']))   $Properties['Title'] .= " - S$Properties[Season]";
    if(!empty($Properties['Episode']))  $Properties['Title'] .= "E$Properties[Episode]";
    if(!empty($Properties['Date']))     $Properties['Title'] .= " - $Properties[Date]";
    if(!empty($Properties['Append']))   $Properties['Title'] .= " - " . ucwords(trim($Properties['Append']));

    // Stash IRC title here
    $Properties['IRCTitle'] = $Properties['Title'];
    if(!empty($Properties['SubTitle'])) $Properties['Title'] .= " - $Properties[SubTitle]";

    // Tag the resolution info onto the end of the array
    if (empty($Resolution)) $Resolution = '480p';
    $CodecFound[] = $Resolution;
    if(intval($Resolution) >= 720 && intval($Resolution) < 2160) {
        $CodecFound[] = 'HD';
    } elseif(intval($Resolution) >= 2160) {
        $CodecFound[] = '4K';
    } else {
        $CodecFound[] = 'SD';
    }

    $CodecSearch = array_merge($CodecSearch, $ExtSearch, $ResolutionSearch, $ProperTagSearch, $ReleaseGroupSearch);
    $CodecClean  = array_merge($CodecClean,  $ExtSearch, $ResolutionClean, $ProperTagClean, $ReleaseGroupClean);

    $ProperTag    = str_ireplace($ProperTagSearch, $ProperTagClean, $ProperTag);
    $CodecClean   = str_ireplace($CodecSearch, $CodecClean, $CodecFound);
    $ReleaseGroup = str_ireplace($ReleaseGroupSearch, $ReleaseGroupClean, $ReleaseGroup);
    $ReleaseGroupOld = str_ireplace($ReleaseGroups, $ReleaseGroups, $ReleaseGroupOld);
        
    if(empty($ReleaseGroup)) $ReleaseGroup = $ReleaseGroupOld;
    
    // Include title lints and release group in the taglist
    $Properties['TagList'] = array_merge(explode(' ', $Properties['TagList']), (array)$CodecClean, array_filter((array)$ProperTag) ,preg_filter('/$/', '.release', array_filter((array)$ReleaseGroup)));
    $Properties['TagList'] = array_unique($Properties['TagList']);
    $Properties['TagList'] = trim(implode(' ', $Properties['TagList']));

    // Clean the Codec list and append it to the title.
    $Properties['IRCTitle'] .= ' ['.implode(' / ', array_filter(array_unique(array_merge((array)$CodecClean, (array)$ProperTag, (array)$ReleaseGroup))));
    $Properties['IRCTitle'] .= ' / ' . $Properties['FileName'].']';

    $Debug[] = "Final title: $Properties[Title]";
    $Debug[] = "Final taglist: $Properties[Taglist]";

    /* Uncomment to check debugs */
    //var_dump($Debug);
    //die();
}


/**
 * Returns the inner list elements of the tag table for a torrent
 * (this function calls/rebuilds the group_info cache for the torrent - in theory just a call to memcache as all calls come through the torrent details page)
 * @param int $GroupID The group id of the torrent
 * @return the html for the taglist
 */
function get_templatelist_html($UserID, $SelectedTemplateID =0)
{
    global $DB, $Cache;

    ob_start();

    $TemplatesPrivate = get_templates_private($UserID);
    $TemplatesPublic = get_templates_public();
    ?>

        <select id="template" name="template" onchange="SelectTemplate(<?=(check_perms('delete_any_template')?'1':'0')?>);" title="Select a template (*=public)">
            <option class="indent" value="0" <?php  if($SelectedTemplateID==0) echo ' selected="selected"' ?>>---</option>
    <?php
        if (count($TemplatesPrivate)>0) {
    ?>
            <optgroup label="private templates">
    <?php
            foreach ($TemplatesPrivate as $template) {
                list($tID, $tName,$tPublic,$tAuthorname) = $template;
    ?>
                <option class="indent" value="<?=$tID?>"<?php  if($SelectedTemplateID==$tID) echo ' selected="selected"' ?>><?=$tName?></option>
    <?php           }         ?>
            </optgroup>
    <?php
        }
        if (count($TemplatesPublic)>0) {
    ?>
            <optgroup label="public templates">
    <?php
            foreach ($TemplatesPublic as $template) {
                list($tID, $tName,$tPublic,$tAuthorname) = $template;
                if ($tPublic==1) $tName .= " (by $tAuthorname)*"
    ?>
                <option class="indent" value="<?=$tID?>"<?php  if($SelectedTemplateID==$tID) echo ' selected="selected"' ?>><?=$tName?></option>
    <?php           }           ?>
            </optgroup>
    <?php       }         ?>
        </select>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}
