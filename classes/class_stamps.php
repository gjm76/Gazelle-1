<?php

class Stamps {
  /**
   * Load hash-to-stamp map
   */
  private static function stampHashes() {
    global $Cache;
    if (!($Hashes = $Cache->get_value('stamp_hashes'))) {
      $Hashes = [];

      $Directories=['0-9'];
      for ($char = 65; $char <= 90; $char++) $Directories[] = chr($char);
      
      foreach($Directories as $Dir) {
        $stamps = array_diff(scandir(SERVER_ROOT . '/static/common/shows/stamps/'.$Dir), array('.', '..'));
        foreach($stamps as $stamp) {
          $Hash = hash('crc32', $Dir.'/'.$stamp);
          $Hashes[$Hash] = $Dir.'/'.$stamp;
        }
      }
      $Cache->cache_value('stamp_hashes', $Hashes);
    }
    return $Hashes;
  }

  public static function isValidStamp($StampID) {
    $Hashes = self::stampHashes();
    return array_key_exists($StampID, $Hashes);
  }

  /**
   * Remove all non-word characters (including the &#xx; characters that display_str throws in)
   * Also removes (modifiers)
   */
  private static function sanitize($Title) {
    return strtolower(preg_replace(['/\&\#\d+\;/', '/\(.*\)/', '/[^A-Za-z0-9]/'], '', $Title));
  }

  private static function getUserUploads($UserID) {
    if (!is_numeric($UserID)) return [];
    global $DB;
    $DB->query('SELECT DISTINCT LOWER(s.ShowTitle) AS ShowTitle FROM torrents t
      LEFT JOIN torrents_group tg ON t.GroupID = tg.ID
      LEFT JOIN shows s ON s.ID = tg.TVMAZE
      WHERE (t.Episode = 0 or t.Episode IS NULL) AND t.UserID = ' . $UserID);
    if (!$DB->has_results()) return [];
    $UserShows = $DB->collect('ShowTitle', false);
    return array_map(function ($Title) { return self::sanitize(db_string($Title));}, $UserShows);
  }
  /**
   * Load all stamps
   */
  public static function getAllStamps($UserID) {
    global $Cache;
    $Stamps = [];
    $Hashes = self::stampHashes();

    $FeaturedShow = $Cache->get_value('featured_show');
    if ($FeaturedShow) $FeaturedShow = self::sanitize($FeaturedShow['Title']);
    $UserUploads = self::getUserUploads($UserID);

    foreach ($Hashes as $StampID => $Path) {
      $Stamps[$StampID] = [
        'StampID' => $StampID,
        'IsHidden' => 0,
        'Order' => 0,
        'Src' => STATIC_SERVER.'common/shows/stamps/'.$Path,
        'Cost' => 15000
      ];
      if (preg_match('/\/(.+)\.png$/', $Path, $Matches)) {
        $Stamps[$StampID]['Name'] = $Matches[1];
        $Stamps[$StampID]['SortName'] = preg_replace('/^((An?|The)\s|\')/', '', $Matches[1]);
      } else {
        // remove stamp?
      }

      $SaneName = self::sanitize($Stamps[$StampID]['Name']);
      if (in_array($SaneName, $UserUploads)) { 
        $Stamps[$StampID]['Cost'] = 1000;
      } else if ($FeaturedShow == $SaneName) {
        $Stamps[$StampID]['Cost'] = 10000;
      }

    }
    uasort($Stamps, function($a, $b) {
      return strcmp($a['SortName'], $b['SortName']);
    });
    return $Stamps;
  }

  /**
   * Loads only the stamps for one user
   */
  public static function getUserStamps($UserID, $InclHidden = false) {
    global $DB, $Cache;
    $Hashes = self::stampHashes();

    if (!$InclHidden) { $AndHidden = " AND IsHidden = false"; }
    $DB->query("SELECT StampID, `Order`, IsHidden FROM users_stamps WHERE UserID = '$UserID' $AndHidden ORDER BY `Order`");
    if (!$DB->has_results()) return [];
    $Stamps = $DB->to_array('StampID', MYSQLI_ASSOC);
    foreach($Stamps as $Key => $Stamp) {
      $Path = $Hashes[$Key];
      if (!$Path) continue;
      $Stamps[$Key]['Src'] = STATIC_SERVER.'common/shows/stamps/'.$Path;
      if (preg_match('/\/(.+)\.png$/', $Path, $Matches)) {
        $Stamps[$Key]['Name'] = $Matches[1];
      }
    }
    return $Stamps;
  }

  /**
   * Return the image tag for a stamp, with appropriate attributes set
   */
  public static function getStampImg($Stamp, $Class='', $AddID=false, $LazyLoad=false, $InclCost=false) {
    // TODO space in class
    $Id = $AddID ? "id='stamp_$Stamp[StampID]'" : '';
    $SrcTag = $LazyLoad ? 'data-src' : 'src';
    $Title = display_str($Stamp['Name']);
    if ($InclCost) $Title .= ' (' . number_format($Stamp['Cost']) . ' cubits)';
    return "<img $Id class='stamp $Class' $SrcTag=\"$Stamp[Src]\" title=\"$Title\" data-id='$Stamp[StampID]' data-title=\"$Stamp[Name]\" data-cost='$Stamp[Cost]' />";
  }

}