<?php
if (!check_perms('admin_manage_parser')) { error(403); }

include(SERVER_ROOT . '/sections/upload/functions.php');

$Title=$_REQUEST['test_title'];
$Rules=$_REQUEST['rules'];
$TitleDebug=array();
if(!empty($Title)) {
    reparse_title($Title, $Lints, $Append, $FileList, $TVMazeID, $TitleDebug, $Rules);
}

$Row = 'b';
$Text = new TEXT;
if(empty($TitleDebug)) $TitleDebug[] = "No rules were matched";
// Print a table row with column headings for clarity ?>
<tr class="rowa">
    <td>Info</td>
    <td>Title Before</td>
    <td>Title After</td>
    <td>Tags</td>
</tr>
<?php
foreach($TitleDebug as $Result) {
    $Row = ($Row === 'a' ? 'b' : 'a');
    echo "<tr class='row$Row'>";
    $i=0;
    foreach($Result as $ResultColumn) {
        $i++;
        echo "<td>".$Text->full_format($ResultColumn)."</td>";
    }
    if($i != 4) echo '<td colspan="'.(4-$i).'"></td>';
    echo "</tr>";
}

if (!empty($TVMazeID)) {
    $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/shows/$TVMazeID"));
    $TVMazeTitle  = $RawTVMazeInfo->name;
    $TVMazePoster = $RawTVMazeInfo->image->medium;
    $TVMazeSynop  = $RawTVMazeInfo->summary;
}elseif (!empty($Title)) {
    $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/singlesearch/shows?q=".urlencode($Title)));
    $TVMazeTitle  = $RawTVMazeInfo->name;
    $TVMazePoster = $RawTVMazeInfo->image->medium;
    $TVMazeSynop  = $RawTVMazeInfo->summary;
}

if (!empty($TVMazeTitle)){
?>
    <tr class="rowb"><td colspan="4"></td></tr>
    <tr class=rowa><td colspan="4"><h1><?=$TVMazeTitle?></h1></td></tr>
    <tr class="rowa">
    <td><img src="<?=$TVMazePoster?>" /></td>
    <td colspan="2"><?=$TVMazeSynop?></td>
    <td></td>
    </tr>
<?php } ?>
