<?php
if (!check_perms('admin_manage_networks')) {
    error(403);
}

include(SERVER_ROOT . '/sections/tools/managers/functions.php');
include(SERVER_ROOT.'/sections/shows/functions.php');

enforce_login();

show_header('Manage Premiers');

?>
<div class="head" title="<?=date('d.m.y',strtotime('today - 30 days'))?> - <?=date('d.m.y')?>">New Shows Premiered in the Past 30 Days Manager</div>
    <div class="box">
    <?php
        $force_reload = isset($_GET['reload']) && check_perms('site_reload_shows');

        $DB->query("SELECT s.id, s.ShowTitle as name, s.ShowInfo as summary, s.premiered, s.weight, tb.BannerLink FROM shows AS s
                    LEFT JOIN torrents_banners AS tb ON tb.TVMazeID = s.ID
                    WHERE s.premiered > NOW() - INTERVAL 1 MONTH AND s.premiered <= NOW()
                    GROUP BY s.ID
                    ORDER BY s.premiered DESC");
        $maze = $DB->to_array();

        $PremiersAdmin = "<table><tr>";

        $i = 0;
        foreach($maze as $Show) {

            if(intval($Show['weight']) < $SiteOptions['TVMazeWeight']) continue;
   
            $Banner = $Show["BannerLink"]; 

            if($i%4==0) $PremiersAdmin .= "</tr><tr>";
            $PremiersAdmin .= "<td>";

            if($Banner) { // no banner no display
        
                $Synopsis = str_replace('"', '', $Show["summary"]); // Striping " to correct Synopsis
                $Synopsis = strip_tags($Synopsis); // Striping tags
                $Synopsis = cutAfter($Synopsis,222,' ...'); // Cut synopsis
   
                if($Show["id"]) // Search prep
                  $Url = "/torrents.php?action=show&showid=".$Show["id"];
                else
                  $Url = torrents_link($Show["name"]); // Search prep
       
                $PremiersAdmin .= "Date: " . date('d.m.Y',strtotime($Show["premiered"])) . "<br />";
                $PremiersAdmin .= "<a href='$Url'><img data-tooltip-width='400' class='banner_col' src='$Banner' title='$Synopsis' style='margin:1px; padding:1px'/></a><br>";
                $PremiersAdmin .= "Show: " . $Show["name"] . "<br />Weight: " . $Show['weight'] . " , Rating: " . $Show['rating']['average'];
            } else {
                $PremiersAdmin .= "Date: " . date('d.m.Y',strtotime($Show["premiered"])) . "<br />";
                $PremiersAdmin .= "<span style='font-weight:bold'><a href=/tools.php?action=automatic_banners&tvmaze_search=" .urlencode($Show["name"]) ."><br />Set banner for: " . $Show["name"] . "</a></span><br /><br />";
                $PremiersAdmin .= "Show: " . $Show["name"] . "<br />Weight: " . $Show['weight'] . " , Rating: " . $Show['rating']['average'];
            }
         
            $PremiersAdmin .= "</td>";
            $i++; 
        }

echo $PremiersAdmin . "</tr></table>"; 
?>
    </div>
    </div>
<?php
show_footer();
