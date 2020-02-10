<?php
$data_viewer_queries = array(
    'torrents_monthly' => array(
        'title' => 'Torrents: Monthly stats',
        'description' => 'This query shows various torrent-related statistics grouped by month.<br /><i>Average current seeders</i> shows the number of active seeders right now for torrents uploaded during that period.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    YEAR(t.Time) AS Year,
    MONTH(t.Time) AS Month,
    COUNT(DISTINCT t.ID) AS Torrents,
    MIN(t.ID) AS Lowest_ID,
    COUNT(DISTINCT t.UserID) AS Distinct_uploaders,
    ROUND(SUM(t.Size) / 1073741824) AS Total_size_in_GB,
    ROUND(AVG(t.Size) / 1048576) AS Average_size_in_MB,
    ROUND(AVG(t.Seeders), 2) AS Average_current_seeders
FROM
    torrents AS t
GROUP BY
    year,
    month
ORDER BY
    year,
    month"
    ),
    'forums_monthly' => array(
        'title' => 'Forums: Monthly stats',
        'description' => 'This query shows various forum-related statistics grouped by month.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    YEAR(AddedTime) AS Year,
    MONTH(AddedTime) AS Month,
    COUNT(DISTINCT fp.AuthorID) AS Unique_forum_posters,
    COUNT(DISTINCT fp.TopicID) AS Active_forum_topics,
    COUNT(fp.ID) AS Forum_posts
FROM
    forums_posts AS fp
GROUP BY
    year,
    month
ORDER BY
    year,
    month"
    ),
    'users_ipcount' => array(
        'title' => 'Users: Active IPs',
        'description' => 'This query shows active IP count per user. Particularly high numbers are suspicious.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<a href=\"/user.php?id=', xfu.uid, '\">', um.username, '</a>') AS User,
    COUNT(DISTINCT xfu.ip) AS IP_count,
    COUNT(DISTINCT xfu.useragent) AS Useragent_count,
    xfu.useragent AS Sample_useragent
FROM
    xbt_files_users AS xfu,
    users_main AS um
WHERE
    um.ID = xfu.uid
GROUP BY
    xfu.uid
ORDER BY
    IP_count DESC"
    ),
    'users_useragentcount' => array(
        'title' => 'Users: Active Useragents',
        'description' => 'This query shows active IP count per user. Particularly high numbers are suspicious.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<a href=\"/user.php?id=', xfu.uid, '\">', um.username, '</a>') AS User,
    COUNT(DISTINCT xfu.ip) AS IP_count,
    COUNT(DISTINCT xfu.useragent) AS Useragent_count,
    xfu.useragent AS Sample_useragent
FROM
    xbt_files_users AS xfu,
    users_main AS um
WHERE
    um.ID = xfu.uid
GROUP BY
    xfu.uid
ORDER BY
    Useragent_count DESC"
    ),
    'users_classes' => array(
        'title' => 'Users: Class stats',
        'description' => "This query shows various total and average stats per user class.",
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<strong><span style=\"color: #', p.Color, ';\">', p.Name, '</span></strong>') AS Class,
    COUNT(um.ID) AS Number, ROUND(AVG(um.Uploaded)/1073741824) as Uploaded_average_in_GB,
    ROUND(AVG(um.Downloaded)/1073741824) as Downloaded_average_in_GB,
    IFNULL(ROUND(AVG(um.Uploaded)/AVG(um.Downloaded), 2), 'NaN') AS Ratio
FROM
    permissions AS p,
    users_main AS um,
    users_info AS ui
WHERE
    um.PermissionID = p.ID
    AND um.Enabled = '1'
    AND ui.UserID = um.ID
    AND p.IsUserClass = '1'
GROUP BY
    p.ID
ORDER BY
    p.Level"
    ),
        'users_dupe_email' => array(
                'title' => 'Users: Possible duplicate emails (SLOW)',
                'description' => '<strong>This query is slow, please use it sparingly.</strong><br />Tries to detect e-mail addresses that are effectively duplicates by processing it in various ways.<br />Results where every single account is already disabled are excluded.',
                'sql' => "
SELECT SQL_CALC_FOUND_ROWS
        CONCAT(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(um.Email, '@', 1), '+', 1), '.', ''), '@', REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(um.Email, '@', -1), '.', 1), 'googlemail', 'gmail'), 'live', 'hotmail'), 'outlook', 'hotmail')) AS Cleaned_up_address,
        COUNT(*) AS Number,
        GROUP_CONCAT(IF(um.Enabled='1','','<del>'),'<a href=\"/user.php?id=', um.ID, '\">', um.username, '</a>', IF(um.Enabled='1','','</del>'), ' ', um.Email, ' [', um.IP, '] ' ORDER BY um.ID SEPARATOR '<br />') AS Accounts
FROM
        users_main AS um
GROUP BY
        Cleaned_up_address
HAVING
        Number >= 2
        AND MAX(IF(um.Enabled='1',1,0)) = 1
ORDER BY
        Number DESC
"
        ),
    'users_monthly' => array(
        'title' => 'Users: Monthly stats',
        'description' => "This query shows  the number of users joined as well as disabled each month.",
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    joined.year AS Year,
    joined.month AS Month,
    joined.count AS Joined,
    IFNULL(banned.count, 0) AS Disabled,
    joined.count - IFNULL(banned.count, 0) AS Growth
FROM
    (
        SELECT
            YEAR(ui.JoinDate) AS year,
            MONTH(ui.JoinDate) AS month,
            COUNT(DISTINCT ui.UserID) AS count
        FROM
            users_info AS ui
        GROUP BY
            year,
            month
    ) AS joined
LEFT OUTER JOIN
    (
        SELECT
            YEAR(ui.BanDate) AS year,
            MONTH(ui.BanDate) AS month,
            COUNT(DISTINCT ui.UserID) AS count
        FROM
            users_info AS ui
        GROUP BY
            year,
            month
    ) AS banned
ON
    joined.year = banned.year
    AND joined.month = banned.month"
    ),
    'users_special_gifts' => array(
        'title' => 'Users: Special Gifts - Donors & Recipients',
        'description' => 'This query shows Special Gifts given by users.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<a href=\"/user.php?id=', usg.UserID, '\">', (SELECT username FROM users_main WHERE ID = usg.UserID), '</a>') AS User,
    SUM(CreditsSpent) AS CreditsSpent,
    SUM(CreditsGiven) AS CreditsGiven,
    SUM(GBsGiven) AS GBsGiven,
    CONCAT('<a href=\"/user.php?id=', usg.Recipient, '\">', (SELECT username FROM users_main WHERE ID = usg.Recipient), '</a>') AS Recipient
FROM
    users_special_gifts AS usg
GROUP BY
    usg.UserID,
    usg.Recipient
ORDER BY
    CreditsGiven DESC"
    ),
'users_special_gifts_donors' => array(
        'title' => 'Users: Special Gifts - Donors',
        'description' => 'This query shows Special Gifts given by users.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<a href=\"/user.php?id=', usg.UserID, '\">', (SELECT username FROM users_main WHERE ID = usg.UserID), '</a>') AS User,
    SUM(CreditsSpent) AS CreditsSpent,
    SUM(CreditsGiven) AS CreditsGiven,
    SUM(GBsGiven) AS GBsGiven
FROM
    users_special_gifts AS usg
GROUP BY
    usg.UserID
ORDER BY
    CreditsSpent DESC"
    ),
'users_special_gifts_recipients' => array(
        'title' => 'Users: Special Gifts - Recipients',
        'description' => 'This query shows Special Gifts received by users.',
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<a href=\"/user.php?id=', usg.Recipient, '\">', (SELECT username FROM users_main WHERE ID = usg.Recipient), '</a>') AS Recipient,
    SUM(CreditsGiven) AS CreditsReceived,
    SUM(GBsGiven) AS GBsReceived
FROM
    users_special_gifts AS usg
GROUP BY
    usg.Recipient
ORDER BY
    CreditsReceived DESC, GBsReceived DESC"
    ),
'users_suspicious' => array(
        'title' => 'Users: Suspicious Torrent Activity',
        'description' => 'This query shows users who have grabbed unbalanced torrents, high counts could indicate cheating.',
        'byte_columns' => ['Downloaded', 'Uploaded', 'TorrentBalance', 'GrabSize'],
        'sql' => "
SELECT SQL_CALC_FOUND_ROWS
    CONCAT('<a href=\"/user.php?id=', ud.UserID, '\">', um.Username, '</a>') AS User,
    (SELECT COUNT(fid) FROM xbt_snatched WHERE uid=um.ID) AS Snatches,
    (SELECT COUNT(TorrentID) FROM users_downloads WHERE UserID=um.ID) AS Grabs,
    um.Downloaded,
    um.Uploaded,
    COUNT(tor.ID) AS TorrentCount,
    SUM(tor.balance) AS TorrentBalance,
    CONCAT('<a href=\"/tools.php?action=ban_speed_cheat&banuser=1&userid=', um.ID, '\"><img src=\"/static/common/symbols/ban.svg\" alt=\"ban\"/></a>') AS Ban
FROM
    users_downloads AS ud
INNER JOIN
    (SELECT ID, balance
     FROM
         torrents
     WHERE
         balance != 0
         AND FreeTorrent = '0')
AS
    tor
ON
    tor.ID=ud.TorrentID
JOIN
    users_main AS um ON ud.UserID=um.ID
WHERE
    um.Enabled='1'
GROUP BY
    ud.UserID
HAVING
    TorrentCount BETWEEN (Grabs*.95) AND (Grabs*1.05)
ORDER BY
    TorrentBalance DESC"
    ),
);
