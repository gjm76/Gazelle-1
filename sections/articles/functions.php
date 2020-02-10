<?php

/**
 * Much saner and simpler view than a ginormous table
 * (Still annoying to use the list() function; will fix ... someday)
 */
function list_articles($Articles, $StaffClass=0) {
    global $ArticleCats, $ArticleSubCats, $ClassLevels;

    $LastSubCat='';
    $OpenUL = false;
    foreach ($Articles as $Article) {
        list($TopicID, $ATitle, $Description, $SubCat, $MinClass) = $Article;

        if($MinClass>$StaffClass) continue;
        if($SubCat==$SkipSubArticle) continue;
        
        if ($LastSubCat != $SubCat) {
            if ($OpenUL) echo '</ul>';
            $LastSubCat = $SubCat;
            echo '<h4>' . $ArticleSubCats[$SubCat] . '</h4><ul>';
            $OpenUL = true;
        }

        echo '<li><a href="articles.php?topic='.$TopicID.'" title="'.display_str($Description).'">'.display_str($ATitle).'</a></li>';
    }
    if ($OpenUL) { echo '</ul>'; }
}

function replace_special_tags($Body)
{
    global $DB, $Cache, $LoggedUser, $Text;

    // Deal with special article tags.
    if (preg_match("/\[clientlist\]/i", $Body)) {
        if (!$BlacklistedClients = $Cache->get_value('blacklisted_clients')) {
            $DB->query('SELECT vstring FROM xbt_client_blacklist WHERE vstring NOT LIKE \'//%\' ORDER BY vstring ASC');
            $BlacklistedClients = $DB->to_array(false,MYSQLI_NUM,false);
            $Cache->cache_value('blacklisted_clients',$BlacklistedClients,604800);
        }

        $list = '<table cellpadding="5" cellspacing="1" border="0" class="border" width="100%">
                    <tr class="colhead">
                      <td style="width:150px;"><strong>Banned Clients</strong></td>
                </tr>';

        $Row = 'a';
        foreach ($BlacklistedClients as $Client) {
            //list($ClientName,$Notes) = $Client;
            list($ClientName) = $Client;
            $Row = ($Row == 'a') ? 'b' : 'a';
            $list .= "<tr class=row$Row>
                            <td>$ClientName</td>
                      </tr>";
        }
        $list .= "</table>";
        $Body = preg_replace("/\[clientlist\]/i", $list, $Body);
    }

    // imagehost whitelist
    if (preg_match("/\[whitelist\]/i", $Body)) {

        $ImageWhitelist = $Cache->get_value('imagehost_whitelist');
        if ($ImageWhitelist === FALSE) {
                $DB->query("SELECT
                    Imagehost,
                    Link,
                    Comment,
                    Time,
                    Hidden
                    FROM imagehost_whitelist
                    WHERE Hidden='0'
                    ORDER BY Time DESC");
                $ImageWhitelist = $DB->to_array();
                $Cache->cache_value('imagehost_whitelist', $ImageWhitelist);
        }
        $list = '<table id="whitelist">
                    <tr class="colhead">
                      <td style="width:50%;"><strong>Imagehost</strong></td>
                      <td><strong>Comment</strong></td>
                </tr>';

        $Row = 'a';
        foreach ($ImageWhitelist as $ImageHost) {

            list($Host, $Link, $Comment, $Updated) = $ImageHost;
            $Row = ($Row == 'a') ? 'b' : 'a';
            $list .= "<tr class=row$Row>
                            <td>".$Text->full_format($Host);
             if ( !empty($Link) && $Text->valid_url($Link)) {
                     $list .=   "<a href=\"$Link\"  target=\"_blank\"><img src=\"". STATIC_SERVER .'common/symbols/offsite.svg" width="16" height="16" alt="Goto '.$Host."\" /></a>\n";
             }

             $list .=   "</td>
                            <td>".$Text->full_format($Comment)."</td>
                      </tr>";
        }
        $list .= "</table>";

        $Body = preg_replace("/\[whitelist\]/i", $list, $Body);
    }

    // DNU list
    if (preg_match("/\[dnulist\]/i", $Body)) {

        $DNUlist = $Cache->get_value('do_not_upload_list');
        if ($DNUlist === FALSE) {
                $DB->query("SELECT  Name, Comment, Time FROM do_not_upload ORDER BY Time");
                $DNUlist = $DB->to_array();
                $Cache->cache_value('do_not_upload_list', $DNUlist);
        }
        $list = '<table id="dnulist">
                    <tr class="colhead">
                      <td style="width:50%;"><strong>Name</strong></td>
                      <td><strong>Comment</strong></td>
                    </tr>';

        $Row = 'a';
        foreach ($DNUlist as $BadUpload) {

            list($Name, $Comment, $Updated) = $BadUpload;
            $Row = ($Row == 'a') ? 'b' : 'a';
            $list .= "<tr class=row$Row>
                            <td>".$Text->full_format($Name)."</td>
                            <td>".$Text->full_format($Comment)."</td>
                      </tr>";
        }
        $list .= "</table>";

        $Body = preg_replace("/\[dnulist\]/i", $list, $Body);
    }

    return $Body;
}
