<?php

/*
 * Yeah, that's right, edit and new are the same place again.
 * It makes the page uglier to read but ultimately better as the alternative means
 * maintaining 2 copies of almost identical files.
 */

$Text = new TEXT;

if(!check_perms('site_submit_requests') || $LoggedUser['HnR']) error(403);

$NewRequest = ($_GET['action'] == "new" ? true : false);

if (!$NewRequest) {
    $RequestID = $_GET['id'];
    if (!is_number($RequestID)) {
        error(404);
    }
}

if ($NewRequest && ($LoggedUser['TotalCredits'] < 250 || !check_perms('site_submit_requests'))) {
    error('You do not have enough cubits to make a request.');
}

if (!$NewRequest) {
    if (empty($ReturnEdit)) {

        $Request = get_requests(array($RequestID));
        $Request = $Request['matches'][$RequestID];
        if (empty($Request)) {
            error(404);
        }

        list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Image, $Description,
             $FillerID, $FillerName, $TorrentID, $TimeFilled, $GroupID, , , $TVMazeID, $Season, $Episode, $ResolutionL, $SourceL, 
             $CodecL, $ContainerL, $ReleaseL) = $Request;
        $VoteArray = get_votes_array($RequestID);
        $VoteCount = count($VoteArray['Voters']);

        $IsFilled = !empty($TorrentID);
        $CategoryName = $NewCategories[$CategoryID]['name'];
        $ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0)));
        $CanEdit = ((!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));

        if (!$CanEdit) {
            error(403);
        }
        sort($Request['Tags']);
        $Tags = implode(", ", $Request['Tags']);
    }
}

if ($NewRequest && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
    $DB->query("SELECT
                            tg.Name,
                            tg.Image,
                            GROUP_CONCAT(t.Name SEPARATOR ', '),
                    FROM torrents_group AS tg
                            JOIN torrents_tags AS tt ON tt.GroupID=tg.ID
                            JOIN tags AS t ON t.ID=tt.TagID
                    WHERE tg.ID = ".$_GET['groupid']);
    if (list($Title, $Image, $Tags) = $DB->next_record()) {
        $GroupID = trim($_REQUEST['groupid']);
    }
}

show_header(($NewRequest ? "Create a request" : "Edit a request"), 'requests,bbcode,jquery.cookie');
?>
<script type="text/javascript">//<![CDATA[
    public function change_tagtext()
    {
        var tags = new Array();
<?php
foreach ($NewCategories as $cat) {
    echo 'tags[' . $cat['id'] . ']="' . $cat['tag'] . '"' . ";\n";
}
?>
        if ($('#category').raw().value == 0) {
            $('#tagtext').html("");
        } else {
            $('#tagtext').html("<strong>The tag "+tags[$('#category').raw().value]+" will be added automatically.</strong>");
        }
    }
<?php
if (!empty($Properties))
    echo "addDOMLoadEvent(SynchInterface);";
?>
//]]></script>

<div class="thin">
    <h2><?=($NewRequest ? "Create a request" : "Edit a request")?></h2>

    <div class="linkbox">
           [ <a href="requests.php">Search requests</a>
           | <a href="requests.php?type=created">My requests</a>
<?php 	 if (check_perms('site_vote')) {?>
           | <a href="requests.php?type=voted">Requests I've voted on</a> ]
<?php 		}  ?>

    </div>
      <div class="head"><?=($NewRequest ? "Create New Request" : "Edit Request")?></div>
    <div class="box pad">
        <form action="" method="post" id="request_form" onsubmit="return flow()" >
<?php  if (!$NewRequest) { ?>
                <input type="hidden" name="requestid" value="<?=$RequestID?>" />
<?php  } ?>
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="action" value="<?=$NewRequest ? 'takenew' : 'takeedit'?>" />
      <div id="messagebar" name="messagebar" class="messagebar alert hidden"></div>
            <table>
                <tr>
                    <td colspan="2" class="center">Please make sure your request follows <a href="articles.php?topic=requests">the request rules!</a></td>
                </tr>
<?php 	if ($NewRequest || $CanEdit) { ?>
                <tr class="pad">
                    <td colspan="2" style="text-align:justify;">
                        NOTE: Requests automatically expire after 90 days. At this time if the bounty has not been filled
 all outstanding bounties are returned to those who placed them.
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        Category
                    </td>
                    <td>
                        <select id="category" name="category" onchange="change_tagtext();">
                                        <option value="0">---</option>
                                    <?php  foreach ($NewCategories as $category) { ?>
                                        <option value="<?=$category['id']?>"<?php
                                            if (isset($CategoryID) && $CategoryID==$category['id']) {
                                                echo ' selected="selected"';
                                            }   ?>><?=$category['name']?></option>
                                    <?php  } ?>
                        </select>
                    </td>
                </tr>
<?php           if ($NewRequest) { ?>                
                <tr id="tvmazeid" style="display:none;">
<?php           } else { ?>                
                <tr id="tvmazeid">
<?php           } ?>                
                    <td class="label">TVMaze ID<br />of the show</td>
                    <td>
                        <input type="text" id="tvmaze" name="tvmaze" style="width:150px;" value="<?=(!empty($TVMazeID) ? display_str($TVMazeID) : '')?>" />
                        <input type="button" id="tvmazeload" value="Load" />
                    </td>
                </tr>
<?php           if (check_perms('site_moderate_requests')) { // staff ?>                 
                <tr id="title_wrap">
<?php           } else { ?>                
                <tr id="title_wrap" style="display:none;">
<?php           } ?>                
                    <td class="label">Title</td>
                    <td>
                        <input type="text" id="title" name="title" style="width:300px;" value="<?=(!empty($Title) ? display_str($Title) : '')?>" />
                        <div id="title_set_wrap" style="display:none"><input type="button" id="titleset" value="Set" /></div>
                    </td>
                </tr>
<?php           if (check_perms('site_moderate_requests')) { // staff ?>                
                <tr id="tags_wrapper">
<?php           } else { ?>                
                <tr id="tags_wrapper" style="display:none;">
<?php           } ?>                
                   <td class="label">Tags</td>
                   <td>
<?php           if (check_perms('site_moderate_requests')) { // staff ?>                
                        <div id="season_wrap" style="display:inline">
<?php           } else { ?>                        
                        <div id="season_wrap" style="display:none">
<?php           } ?>                
                        Season: <input type="text" id="season" name="season" style="width:30px;" value="<?=$Season?>" /></div>
<?php           if ($NewCategories[$CategoryID]['tag'] === 'season') { ?>                        

<?php              if (check_perms('site_moderate_requests') && $NewCategories[$CategoryID]['tag'] === 'episode') { // staff ?>                
                        <div id="episode_wrap" style="display:inline">
<?php              } else { ?>                        
                        <div id="episode_wrap" style="display:none">
<?php              } ?>                
                        
<?php           } else { ?>
                        <div id="episode_wrap" style="display:inline">
<?php           } ?>                                                
                        Episode: <input type="text" id="episode" name="episode" style="width:30px;" value="<?=$Episode?>" /></div>

<?php           if (check_perms('site_moderate_requests')) { // staff ?> 
                        <div id="resolution_wrap" style="display:inline">
<?php           } else { ?>
                        <div id="resolution_wrap" style="display:none">
<?php           } ?>
                        Resolution: 
                        <select id="resolution" name="resolution">
                           <option value="0">Any</option>
<?php 
                           $DB->query("SELECT Codec
                                       FROM torrents_codecs AS tc
                                       JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                                       WHERE Sort >= 100 AND Sort < 200
                                       ORDER BY Codec");
                           $Resolutions = $DB->collect('Codec');
                           $Resolutions = array_unique($Resolutions);
                           foreach ($Resolutions as $Resolution) { ?>
                           <option value="<?=$Resolution?>"<?php
                                            if (isset($ResolutionL) && $ResolutionL==$Resolution) {
                                                echo ' selected="selected"';
                                            }   ?>><?=$Resolution?></option>
<?php  } ?>
                        </select>
                        </div>
                        
<?php           if (check_perms('site_moderate_requests')) { // staff ?> 
                        <div id="source_wrap" style="display:inline">
<?php           } else { ?>                        
                        <div id="source_wrap" style="display:none">
<?php           } ?>                        
                        Source: 
                        <select id="source" name="source">
                           <option value="0">Any</option>
<?php 
                           $DB->query("SELECT Codec
                                       FROM torrents_codecs AS tc
                                       JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                                       WHERE Sort = 1
                                       ORDER BY Codec");
                           $Sources = $DB->collect('Codec');
                           $Sources = array_unique($Sources);
                           foreach ($Sources as $Source) { ?>
                           <option value="<?=$Source?>"<?php
                                            if (isset($SourceL) && $SourceL==$Source) {
                                                echo ' selected="selected"';
                                            }   ?>><?=$Source?></option>
<?php  } ?>
                        </select>

                        Subtitles: <input id="subs" name="subs" type="checkbox" title="Check to request subtitles as well">                           
                                                
                        </div>
                        <br />
<?php           if (check_perms('site_moderate_requests')) { // staff ?> 
                        <div id="codec_wrap" style="display:inline">
<?php           } else { ?>                        
                        <div id="codec_wrap" style="display:none">
<?php           } ?>                        
                        Codec: 
                        <select id="codec" name="codec">
                           <option value="0">Any</option>
<?php 
                           $DB->query("SELECT Codec
                                       FROM torrents_codecs AS tc
                                       JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                                       WHERE Sort = 2
                                       ORDER BY Codec");
                           $Codecs = $DB->collect('Codec');
                           $Codecs = array_unique($Codecs);
                           foreach ($Codecs as $Codec) { ?>
                           <option value="<?=$Codec?>"<?php
                                            if (isset($CodecL) && $CodecL==$Codec) {
                                                echo ' selected="selected"';
                                            }   ?>><?=$Codec?></option>
<?php  } ?>
                        </select>                        
                        </div>

<?php           if (check_perms('site_moderate_requests')) { // staff ?> 
                        <div id="container_wrap" style="display:inline">
<?php           } else { ?>                        
                        <div id="container_wrap" style="display:none">
<?php           } ?>                        
                        Container: 
                        <select id="container" name="container">
                           <option value="0">Any</option>
<?php                      $Containers = $Video_FileTypes;
                           foreach ($Containers as $Container) { ?>
                           <option value="<?=$Container?>"<?php
                                            if (isset($ContainerL) && $ContainerL==$Container) {
                                                echo ' selected="selected"';
                                            }   ?>><?=$Container?></option>
<?php  } ?>
                        </select>                        
                        </div>

<?php           if (check_perms('site_moderate_requests')) { // staff ?> 
                        <div id="release_wrap" style="display:inline">
<?php           } else { ?>                        
                        <div id="release_wrap" style="display:none">
<?php           } ?>                        
                        Release Group: 
                        <select id="release" name="release">
                           <option value="0">Any</option>
<?php 
                           $DB->query("SELECT Codec
                                       FROM torrents_codecs AS tc
                                       JOIN torrents_codecs_alt AS tca ON tc.ID=tca.CodecID
                                       WHERE Sort >= 200 AND Sort < 300
                                       ORDER BY Codec");
                           $Releases = $DB->collect('Codec');
                           $Releases = array_unique($Releases);
                           foreach ($Releases as $Release) { ?>
                           <option value="<?=$Release?>"<?php
                                            if (isset($ReleaseL) && $ReleaseL==$Release) {
                                                echo ' selected="selected"';
                                            }   ?>><?=$Release?></option>
<?php  } ?>
                        </select>                        
                        </div>

<?php           if (check_perms('site_moderate_requests')) { // staff ?>
                        <div id="set_wrap" style="display:inline">
<?php           } else { ?>                        
                        <div id="set_wrap" style="display:none">
<?php           } ?>                        
                        <input type="button" id="seasonepisodeset" value="Set" /></div>
                     </td>   
                </tr>
<?php           if (check_perms('site_moderate_requests')) { // staff ?>                 
                <tr id="image_tr">
                            <td class="label">Banner Image</td>
                            <td>
                                 <input type="text" id="image" class="long" name="image" value="<?=(!empty($Image) ? $Image : '')?>" />
                            </td>
                </tr>
<?php 	} ?>
<?php 	       }  // staff ?>
<?php           if (check_perms('site_moderate_requests')) { // staff ?>                 
                <tr id="tags_wrap">
                    <td class="label">Tags</td>
                    <td>
                    <div id="tagtext"></div>
<?php
    $GenreTags = $Cache->get_value('genre_tags');
    if (!$GenreTags) {
        $DB->query('SELECT Name FROM tags WHERE TagType=\'genre\' ORDER BY Name');
        $GenreTags =  $DB->collect('Name');
        $Cache->cache_value('genre_tags', $GenreTags, 3600*6);
    }
?>
                        <select id="genre_tags" name="genre_tags" onchange="add_tag();return false;" >
                            <option>---</option>
<?php 	foreach (display_array($GenreTags) as $Genre) { ?>
                            <option value="<?=$Genre ?>"><?=$Genre ?></option>
<?php 	} ?>
                        </select>
                    <textarea id="tags" name="tags" class="medium" style="height:1.4em;" ><?=(!empty($Tags) ? display_str($Tags) : '')?></textarea>

                        <br />
                    <?php
                                      $taginfo = get_article('tagrulesinline');
                                      if($taginfo) echo $Text->full_format($taginfo, true);
                              ?>
                    </td>
                </tr>
                <tr id="desc_wrap">
                    <td class="label">Description</td>
                    <td>  <div id="preview" class="box pad hidden"></div>
                                    <div  id="editor">
                                         <?php  $Text->display_bbcode_assistant("quickcomment", get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions'])); ?>
                                        <textarea  id="quickcomment" name="description" class="long" rows="7"><?=(!empty($Description) ? $Description : '')?></textarea>
                                    </div>
                                    <input type="button" id="previewbtn" value="Preview" style="margin-right: 40px;" onclick="Preview_Request();" />
                              </td>
                </tr>                
<?php 	       } else {  ?>
                <tr id="tags_wrap" style="display:none;">
                    <td class="label">Tags</td>
                    <td>
                    <div id="tagtext"></div>
                    <textarea id="tags" name="tags" class="medium" style="height:1.4em;" readonly="readonly"></textarea>
                    </td>
                </tr>
                <tr id="desc_wrap" style="display:none;">
                    <td class="label">Description</td>
                    <td>  <div id="preview" class="box pad hidden"></div>
                                    <div  id="editor">
                                        <textarea  id="quickcomment" name="description" readonly="readonly" class="long" rows="1"><?=(!empty($Description) ? $Description : '')?></textarea>
                                    </div>
                              </td>
                </tr>
<?php 	       }  // staff ?>
<?php 	if ($NewRequest) { ?>
<?php           if (check_perms('site_moderate_requests')) { // staff ?> 
                <tr id="voting_wrap">
<?php 	       } else {  ?>                
                <tr id="voting_wrap" name="voting_wrap" style="display:none;">
<?php 	       }  // staff ?>                
                    <td class="label" id="bounty">Bounty</td>
                    <td>
                        <input type="text" id="amount_box" size="8" value="<?=(!empty($Bounty) ? $Bounty : '500')?>" onchange="Calculate();" /> Cubits
                        <input type="button" value="Preview" onclick="Calculate();"/>
                        <strong id="inform">500 Cubits will be immediately removed from your Cubits total.</strong>
                    </td>
                </tr>
<?php           if (check_perms('site_moderate_requests')) { // staff ?>                
                <tr id="bounty_info" >
<?php 	       } else {  ?>                 
                <tr id="bounty_info" name="bounty_info" style="display:none;">
<?php 	       }  // staff ?>                  
                    <td class="label">Post request information</td>
                    <td>
                        <input type="hidden" id="amount" name="amount" value="<?=(!empty($Bounty) ? $Bounty : '500')?>" />
                        <input type="hidden" id="current_credits" value="<?=intval($LoggedUser['TotalCredits'])?>" />
                        If you add the entered <strong><span id="new_bounty">500 Cubits</span></strong> of bounty, your new stats will be: <br/>
                        Cubits: <span id="new_points"><?=intval($LoggedUser['TotalCredits'])?></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" id="button_vote" value="Create request" onclick="flow();" />
                    </td>
                </tr>
<?php 	} else { ?>
                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" id="button_vote" value="Edit request" />
                    </td>
                </tr>
<?php 	} ?>
            </table>
        </form>
    </div>
</div>
<?php
show_footer();
