<?php
if (!check_perms('admin_manage_networks')) { error(403); }

enforce_login();

include(SERVER_ROOT . '/sections/tools/managers/functions.php');
include(SERVER_ROOT . '/sections/shows/functions.php');
include(SERVER_ROOT . '/sections/upload/functions.php');

$TVMazeTitle = $_REQUEST['tvmaze_search'];
$UniqueTag = $_REQUEST['unique_tag'];

if (!empty($TVMazeTitle)) {
    $RawTVMazeInfo = json_decode(file_get_contents("http://api.tvmaze.com/singlesearch/shows?q=".urlencode($TVMazeTitle)));

    $TVMazeID     = $RawTVMazeInfo->id;
    if(!$TVMazeID){                    // not found
        $Message  = "Show `".$TVMazeTitle."` not found.";
        error(0);
    }

    $TVMazeTitle  = $RawTVMazeInfo->name;
    $TVMazePoster = $RawTVMazeInfo->image->medium;
    $TVMazePoster = upload_to_imagehost(str_replace('http', 'https', $TVMazePoster)); // Secure link + upload to imagehost
    $TVMazeRating  = $RawTVMazeInfo->rating->average;
    $TVMazeTags = strtolower(implode(' ', (array)$RawTVMazeInfo->genres)); // get tags
    $TVMazeTags .= " " . strtolower(implode(' ', (array)str_replace(" ", ".", $RawTVMazeInfo->network->name))); // correct tag
    $TVMazeTags .= " " . strtolower(implode(' ', (array)str_replace(" ", ".", $RawTVMazeInfo->webChannel->name))); // correct tag
    if(!$TVMazeRating)$TVMazeRating='0'; // no rating
    $TVMazeTitle = str_replace('&#39;', "`", $TVMazeTitle); // fix '

    if($TVMazeID) // Search prep
       $Url = "/torrents.php?action=show&showid=".$TVMazeID;
    else {
       $Url = torrents_link($TVMazeTitle);
       if($UniqueTag) { // add unique tag
       $Url .= '&taglist=' . $UniqueTag;
       } 
    }
    
    $Synopsis = $RawTVMazeInfo->summary;
    $Synopsis = str_replace('"', '', $Synopsis); // Striping " to correct Synopsis
    $Synopsis = strip_tags($Synopsis); // Striping tags
    $Synopsis = cutAfter($Synopsis,444,' ...'); // cut long one
    $Message  = "Show `".$TVMazeTitle."` ready to be set";
}
else {
    $F = get_featured_show();
    list($TVMazeID,$TVMazeTitle,$Synopsis,$TVMazeRating,$TVMazePoster,$SetTime,$Url) = $F;
}

show_header('Featured Show', '', 'featured');

?>
<div class="thin">
<h2>Featured Show</h2>
<table>
    <tr class="head">
        <td colspan="6">Set Featured Show</td>
    </tr>
    <tr class="colhead">
        <td colspan="3"><span title="Search TVMaze for info">
                    Search TVMaze by Show Name</span></td>
    </tr>
    <form action="tools.php?action=featured_show" method="post">
    <tr class="rowa">
        <input type="hidden" name="action" value="featured_show" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <td style="width:400px;">
            <input class="medium"  type="text" style="width:400px;" name="tvmaze_search" value="<?=$TVMazeTitle?>" />
        </td>
        <td style="width:100px;">
            <input type="submit" name="submit" style="width:100px;" value="Search" />
        </td>
        <td>
         <?php if($SetTime) {?>Was set on:<?php }?> <?=$SetTime?>
        </td>
    </tr>
    <tr>
     <td style="width:400px;">
      Shows Tags: <input class="medium"  type="text" style="width:189px;" name="tags" value="<?=$TVMazeTags?>"/>
      Unique Tag: <input class="medium"  type="text" style="width:50px;" name="unique_tag" value="<?=$UniqueTag?>"/>
     </td>
     <td colspan="2">Set Unique Tag and click on Search again</td>
    </tr>
    </form>
</table>

<table>
    <form action="tools.php" method="post">
    <tr class="rowa">
        <input type="hidden" name="action" value="featured_show_alter" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <input type="hidden" name="synopsis" value="<?=$Synopsis?>" />
        <input type="hidden" name="poster" value="<?=$TVMazePoster?>" />
        <input type="hidden" name="rating" value="<?=$TVMazeRating?>" />
        <input type="hidden" name="tvmazetitle" value="<?=$TVMazeTitle?>" />
        <input type="hidden" name="unique_tag" value="<?=$UniqueTag?>" />
        <td style="width:400px;">
            <input class="medium"  type="text" readonly style="width:400px;" name="tvmaze" value="<?=$TVMazeID?>"/>
        </td>
        <td style="width:100px;">
        <?php if($TVMazeID) {?>
            <input type="submit" name="submit" style="width:100px;" value="Set" />
        <?php }?>
        </td>
        <td>
        <?=$Message?>
        </td>
        <td>
        <?php if($TVMazeID) {?>
           <input type="submit" name="submit" style="width:100px;" value="Remove" onclick="return confirm('Are you sure you want to remove?'); document.getElementById('action').value = 'Remove';" />
        <?php }?>
        </td>
    </tr>
    </form>
</table>
<?php if($TVMazeID) {?>
<a href='<?=$Url?>' style="text-decoration: none;">
    <table style="width:200px;">
     <tr>
      <td colspan="2" style="border:1px; text-align:center;">
      <img class="featured"  data-tooltip-width="500" src="<?=$TVMazePoster?>" title="<?=$Synopsis?>">
      </td>
     </tr>
      <tr>
       <td style="border:0px; position:relative;">
        <span style="font-weight:bold; font-size:14px;"><?=$TVMazeTitle?></span>
        <div style="font-weight:bold; font-size:14px; float: right;"><?=$TVMazeRating?></div>
        <div class="rating-star"></div>
       </td>
      </tr>
    </table>
</a>
<?php }?>
    <!--a href="<?=ANONYMIZER_URL?>http://www.tvmaze.com/" target="_blank"><div style="padding: 5px;"-->
    <span style="float:right;"><img src="https://tvmazecdn.com/images/favico/apple-touch-icon-60x60.png"></span></div>
    <span style="float:right;"><br/>Powered by<br/><span style="font-size:1.5em;">TVMaze</span></span>
    <div class="clear"></div><!--/a-->
    </div>
<?php
show_footer();
