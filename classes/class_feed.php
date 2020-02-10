<?php
class feed
{
    public $UseSSL = true; // If we're using SSL for blog and news links

    public function open_feed()
    {
        header("Content-type: application/xml; charset=UTF-8");
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n","<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n\t<channel>\n";
    }
    public function close_feed()
    {
        echo "\t</channel>\n</rss>";
    }
    public function channel($Title, $Description, $Section='')
    {
        $Site = $this->UseSSL ? 'https://'.SSL_SITE_URL : 'http://'.NONSSL_SITE_URL;
        echo "\t\t<title>$Title :: ". SITE_NAME. "</title>\n";
        echo "\t\t<link>$Site/$Section</link>\n";
        echo "\t\t<description>$Description</description>\n";
        echo "\t\t<language>en-us</language>\n";
        echo "\t\t<lastBuildDate>". date('r'). "</lastBuildDate>\n";
        echo "\t\t<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n";
        echo "\t\t<generator>Gazelle Feed Class</generator>\n\n";
    }
    public function item($Title, $Description, $Page, $Creator, $Comments='', $Category='', $Date='') { //Escape with CDATA, otherwise the feed breaks.
        if ($Date == '') {
            $Date = date("r");
        } else {
            $Date = date("r",strtotime($Date));
        }
        $Site = $this->UseSSL ? 'https://'.SSL_SITE_URL : 'http://'.NONSSL_SITE_URL;
        $Item = "\t\t<item>\n";
        $Item .= "\t\t\t<title><![CDATA[$Title]]></title>\n";
        $Item .= "\t\t\t<description><![CDATA[$Description]]></description>\n";
        $Item .= "\t\t\t<pubDate>$Date</pubDate>\n";
        $Item .= "\t\t\t<link>$Site/$Page</link>\n";
        $Item .= "\t\t\t<guid>$Site/$Page</guid>\n";
        if ($Comments != '') {
            $Item .= "\t\t\t<comments>$Site/$Comments</comments>\n";
        }
        if ($Category != '') {
            $Item .= "\t\t\t<category><![CDATA[$Category]]></category>\n";
        }
        $Item .= "\t\t\t<dc:creator>$Creator</dc:creator>\n\t\t</item>\n";

        return $Item;
    }
    //specialised creator function for torrent items
    public function torrent($Title, $Description, $Page, $DownLink, $InfoHash, $TorrentName, $TorrentSize, $ContentSize, $ContentSizeHR, $Creator, $Domain, $Category='', $Tags='', $Date)
    {
        if(empty($Date)) $Date=date("r");
        $Site = $this->UseSSL ? 'https://'.SSL_SITE_URL : 'http://'.NONSSL_SITE_URL;
        $Item = "\t\t<item>\n";

        $Item .= "\t\t\t<title><![CDATA[$Title]]></title>\n";
        $Item .= "\t\t\t<link>$Site/$Page</link>\n";
        $Item .= "\t\t\t<category domain=\"$Site/$Domain\"><![CDATA[$Category]]></category>\n";
        $Item .= "\t\t\t<pubDate>$Date</pubDate>\n";
        $Item .= "\t\t\t<description><![CDATA[$Description]]></description>\n";
        $Item .= "\t\t\t<tags><![CDATA[$Tags]]></tags>\n";
        $Item .= "\t\t\t<dc:creator>$Creator</dc:creator>\n";
        $Item .= "\t\t\t<enclosure url=\"$Site/$DownLink\" length=\"$TorrentSize\" type=\"application/x-bittorrent\" />\n";
        $Item .= "\t\t\t<comments>$Site/$Page</comments>\n";
        $Item .= "\t\t\t<guid>$Site/$Page</guid>\n";

        $Item .= "\t\t\t<torrent xmlns=\"http://xmlns.ezrss.it/0.1/\">\n";
        $Item .= "\t\t\t\t<fileName><![CDATA[$TorrentName]]></fileName>\n";
        $Item .= "\t\t\t\t<infoHash><![CDATA[$InfoHash]]></infoHash>\n";
        $Item .= "\t\t\t\t<contentLength>$ContentSize</contentLength>\n";
        $Item .= "\t\t\t\t<contentLengthHR>$ContentSizeHR</contentLengthHR>\n";
        $Item .= "\t\t\t</torrent>\n";

        $Item .= "\t\t</item>\n";

        return $Item;
    }

    public function retrieve($CacheKey,$AuthKey,$PassKey)
    {
        global $Cache;
        $Entries = $Cache->get_value($CacheKey);

        // Reload and retry
        if (($CacheKey == 'torrents_all') && (!$Entries)) {
            $this->repopulate();
            $Entries = $Cache->get_value($CacheKey);
        }

        if (!$Entries) {
            $Entries = array();
        } else {
            foreach ($Entries as $Item) {
                echo str_replace(array('[[PASSKEY]]','[[AUTHKEY]]'),array(display_str($PassKey),display_str($AuthKey)),$Item);
            }
        }
    }

    function repopulate()
    {
        global $Cache;

        $Text = NEW TEXT;
        $DB   = NEW DB_MYSQL;

        $DB->query("SELECT * FROM (
                    SELECT tg.Name AS Title,
                           tg.Body,
                           tg.ID AS GroupID,
                            t.ID AS TorrentID,
                            t.info_hash AS InfoHash,
                            t.FilePath AS FileName,
              OCTET_LENGTH(tf.File) AS TorrentSize,
                            t.Size AS TotalSize,
                            t.Anonymous,
                           um.Username,
                           tg.NewCategoryID,
             UNIX_TIMESTAMP(t.Time) AS Date
                      FROM torrents_group AS tg
                 LEFT JOIN torrents AS t ON tg.ID=t.GroupID
                 LEFT JOIN torrents_files AS tf ON t.ID=tf.TorrentID
                 LEFT JOIN users_main AS um ON t.UserID=um.ID
                  ORDER BY tg.ID DESC
                     LIMIT 50) sub
                  ORDER BY GroupID ASC");
        $Torrents = $DB->to_array();

        $NewCategories = $Cache->get_value('new_categories');
        if (!$NewCategories) {
            $DB->query('SELECT id, name, image, tag FROM categories ORDER BY name ASC');
            $NewCategories = $DB->to_array('id');
            $Cache->cache_value('new_categories', $NewCategories);
        }

        foreach ($Torrents AS $Torrent) {
            $DB->query("SELECT Name FROm tags LEFT JOIN torrents_tags AS tt ON tt.TagID=tags.ID WHERE tt.GroupID=$Torrent[GroupID]");
            $Tags = $DB->collect('Name');
            $Item = $this->torrent($Torrent['Title'],
                                   $Text->strip_bbcode($Torrent['Body']),
                                  'torrents.php?id=' . $Torrent['GroupID'],
                                  'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id=' . $Torrent['TorrentID'],
                                  rawurlencode($Torrent['InfoHash']),
                                  $Torrent['FileName'],
                                  $Torrent['TorrentSize'],
                                  $Torrent['TotalSize'],
                                  get_size($Torrent['TotalSize']),
                                  ($Torrent['Anonymous']=='1'?'anon':$Torrent['Username']),
                                  "torrents.php?filter_cat[".$Torrent['NewCategoryID']."]=1",
                                  $NewCategories[(int) $Torrent['NewCategoryID']]['name'],
                                  implode($Tags, ' '),
                                  date("r", $Torrent['Date']));
            $this->populate('torrents_all', $Item);
        }

    }

    public function populate($CacheKey,$Item)
    {
        global $Cache;
        $Entries = $Cache->get_value($CacheKey,true);
        if (!$Entries) {
            $Entries = array();
        } else {
            if (count($Entries)>=50) {
                array_pop($Entries);
            }
        }
        array_unshift($Entries, $Item);
        $Cache->cache_value($CacheKey, $Entries, 0); //inf cache
    }
}
