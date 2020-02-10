<?php
// Limiter
function cutAfter($string, $len, $append) {
    return (strlen($string) > $len) ?
    substr($string, 0, $len - strlen($append)) . $append :
    $string;
}

function secure_link($link) {
	   return str_replace('http', 'https', $link);
}

function small_portrait($link) {
	   return str_replace('medium', 'small', $link);
}

function get_shows($force_refresh=FALSE) {

    global $Cache, $DB;
    global $SiteOptions;
    
    if ((!$Clean = $Cache->get_value('shows')) || $force_refresh) {

        // ensure we really are clean
        unset($Clean);

        $async_curl = curl_multi_init();

        // Prepare all page requests
        for($i=0; $i < 250; $i++){
            $ch = curl_init(); // init curl, and then setup your options
            curl_setopt($ch, CURLOPT_URL, "http://api.tvmaze.com/shows?page=".$i);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            $aCurlHandles[] = $ch;
            curl_multi_add_handle($async_curl,$ch);
        }

        // Perform asynchronous request fetching
        $active = null;
        do {
            $mrc = curl_multi_exec($async_curl, $active);
        }
        while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($async_curl, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        // Collect the results
        foreach ($aCurlHandles as $ch) {
            $Pages[] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($async_curl, $ch);
        }
        curl_multi_close($async_curl);

        // Decode pages + pull from db + save missing to db
        foreach($Pages as $Page) {
            $Shows = json_decode($Page, TRUE);
            foreach($Shows as $Show) {
                if(intval($Show['weight']) < intval($SiteOptions['TVMazeWeight'])) continue;
                    
                $TVMAZE = $Show['id'];
                if(is_numeric($TVMAZE)) {
                   $DB->query("SELECT ID, ShowTitle, ShowInfo, Genres, NetworkName, WebChannel, NetworkUrl, PosterUrl, Rating, Weight, Premiered, Updated
                               FROM shows WHERE ID='$TVMAZE'");
                   if ($DB->record_count() > 0) {
                      list($ID, $ShowTitle, $ShowInfo, $Genres, $NetworkName, $WebChannel, $NetworkUrl, $PosterUrl, $Rating, $Weight, $Premiered, $Updated) = $DB->next_record();

                      $Clean[$ID]['id'] = $ID;
                      $Clean[$ID]['name'] = $ShowTitle;

                      if(!$ShowInfo) {
                         $Clean[$ID]['summary'] = preg_replace('#<[^>]+>#', '', $Show['summary']);
                         $Clean[$ID]['summary'] = cutAfter($Clean[$ID]['summary'],444,' ...');
                         $DB->query("UPDATE shows SET  ShowInfo = '" . db_string($Clean[$ID]['summary']) . "' WHERE ID=$ID");
                         $DebugL[] = 'Saved ShowInfo to DB';  
                      }   
                      else
                         $Clean[$ID]['summary'] = $ShowInfo;

                      if(!$Genres && $Show['genres']) {
                         $Clean[$ID]['genres'] = $Show['genres'];
                         $DB->query("UPDATE shows SET  Genres = '" . db_string(implode('|', $Clean[$ID]['genres'])) . "' WHERE ID=$ID");
                         $DebugL[] = 'Saved Genres to DB';  
                      }
                      else
                         $Clean[$ID]['genres'] = explode('|', $Genres);

                      if(!$NetworkName && $Show['network']['name']) {
                         $Clean[$ID]['network']['name'] = $Show['network']['name'];
                         $DB->query("UPDATE shows SET  NetworkName = '" . db_string($Clean[$ID]['network']['name']) . "' WHERE ID=$ID");
                         $DebugL[] = 'Saved NetworkName to DB';                          
                      }   
                      else
                         $Clean[$ID]['network']['name'] = $NetworkName;   
                      
                      if(!$WebChannel && $Show['webChannel']['name']) {
                         $Clean[$ID]['webChannel']['name'] = $Show['webChannel']['name'];
                         $DB->query("UPDATE shows SET  WebChannel = '" . db_string($Clean[$ID]['webChannel']['name']) . "' WHERE ID=$ID");
                         $DebugL[] = 'Saved WebChannel to DB';                          
                      }   
                      else
                         $Clean[$ID]['webChannel']['name'] = $WebChannel;  

                      if(strcmp($Clean[$ID]['network']['name'],'A&amp;E') == 0) $Clean[$ID]['network']['name'] = 'AETV'; // fix A&E

                      $Clean[$ID]['network']['url'] = $NetworkUrl;
                      $Clean[$ID]['image']['medium'] = $PosterUrl;
                      $Clean[$ID]['rating']['average'] = $Rating;

                      if(!$Weight) {
                         $Clean[$ID]['weight'] = $Show['weight'];
                         $DB->query("UPDATE shows SET  Weight = '" . intval($Clean[$ID]['weight']) . "' WHERE ID=$ID");
                         $DebugL[] = 'Saved Weight to DB';
                      }                             
                      else
                         $Clean[$ID]['weight'] = $Weight;

                      if($Premiered == '0000-00-00 00:00:00' && $Show['premiered']) {
                         $Clean[$ID]['premiered'] = $Show['premiered'];
                         $DB->query("UPDATE shows SET  Premiered = '" . $Clean[$ID]['premiered'] . "' WHERE ID=$ID");
                         $DebugL[] = 'Saved Premiered '. $Clean[$ID]['premiered'] .' to DB';  
                      }                           
                      else
                         $Clean[$ID]['premiered'] = $Premiered;
                         
                      $Clean[$ID]['updated'] = $Updated;     
                   }
                   
                }
                unset($TVMAZE);   
            }
        }
        $Cache->cache_value('shows', $Clean, 3600*6); // once in 6 hours
    }
    //var_dump($DebugL);
    return $Clean;
}

function get_genre($Shows,$Genre) {

   // Sort
   foreach($Shows as $Show) {
       if($Show['genres']) {
           foreach($Show['genres'] as $Search){
               if($Search == $Genre) {                
                   $Clean[] = ($Show);
                   break;
               }        
           }
       }
    }
    return $Clean;
}

function get_network($Shows,$Network) {

   // Sort
    for($i=0; $i < count($Shows) ;$i++) {
        if($Shows[$i]['network']['name']) {
               if($Shows[$i]['network']['name'] == $Network) {                
                    $Clean[] = $Shows[$i];
                    }        
                }
        elseif($Shows[$i]['webChannel']['name']) {
               if($Shows[$i]['webChannel']['name'] == $Network) {                
                    $Clean[] = $Shows[$i];
                    }        
                }                
    }             
    return $Clean;
}

function array_sort_by_column(&$arr, $col, $dir) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}

function get_networks($BigMaze) {
	foreach($BigMaze as $Search){
		if($Search['network']['name']) {
			$Networks[] = $Search['network']['name'];
		}
		elseif($Search['webChannel']['name']) {
			$Networks[] = $Search['webChannel']['name'];
		}		
	}
	$Networks = array_unique($Networks);
	sort($Networks); // sort in alphabetical order
	return $Networks;
}

function print_rating($rating, $index) {
    $r = round($rating); // getting rating + round it
    // Rating stars
    for($j=10; $j>0; $j--) {
?>
        <input type="radio" class="rating-input" disabled <?= $j>=$r ? ' checked ' : ' '?> id="rating-input-<?=$index?>-<?=$j?>" name="rating-input-<?=$index?>"/>
        <label for="rating-input-1-<?=$j?>" class="rating-star"></label>
<?php
    }
}
