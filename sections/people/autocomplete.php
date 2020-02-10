<?php
if (empty($_GET['query'])) {
  error(0);
}
header('Content-Type: application/json; charset=utf-8');

$FullName = rawurldecode($_GET['query']);

$MaxKeySize = 4;
if (strtolower(substr($FullName,0,4)) == 'the ') {
  $MaxKeySize += 4;
}
$KeySize = min($MaxKeySize,max(1,strlen($FullName)));

$Letters = strtolower(substr($FullName,0,$KeySize));
$AutoSuggest = $Cache->get('autocomplete_artist_'.$KeySize.'_'.$Letters);

if (!$AutoSuggest) {
  $Limit = (($KeySize === $MaxKeySize) ? 250 : 10);
  $DB->query("
    SELECT
      p.ID,
      p.PersonName
    FROM persons AS p
      INNER JOIN persons_shows AS ps ON ps.PersonID=p.ID
      INNER JOIN shows AS s ON s.ID=ps.ShowID
    WHERE p.PersonName LIKE '".db_string(str_replace('\\','\\\\',$Letters),true)."%'
    GROUP BY ps.PersonID
    ORDER BY s.Updated DESC
    LIMIT $Limit");
  $AutoSuggest = $DB->to_array(false,MYSQLI_NUM,false);
  $Cache->cache_value('autocomplete_artist_'.$KeySize.'_'.$Letters,$AutoSuggest,1800 + 7200 * ($MaxKeySize - $KeySize)); // Can't cache things for too long in case names are edited
}

$Matched = 0;
$ArtistIDs = [];
$Response = array(
  'query' => $FullName,
  'suggestions' => []
);
foreach ($AutoSuggest as $Suggestion) {
  list($ID, $Name) = $Suggestion;
  if (stripos($Name, $FullName) === 0) {
    $Response['suggestions'][] = array('value' => $Name, 'data' => $ID);
    if (++$Matched > 9) {
      break;
    }
  }
}
echo json_encode($Response);
