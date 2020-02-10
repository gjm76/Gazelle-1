<?php
/************************************************************************
||------------|| Edit torrent page ||------------------------------||
************************************************************************/

$GroupID = $_GET['groupid'];
if (!is_number($GroupID) || !$GroupID) { error(0); }

$Review = get_last_review($GroupID);

    // may as well use prefilled vars if coming from takegroupedit
if ($HasDescriptionData !== TRUE) {
    $DB->query("SELECT
          tg.NewCategoryID,
          tg.Name,
          tg.Image,
          tg.Mediainfo,
          tg.Screens,
          tg.Trailer,
          tg.Synopsis,
          tg.EpisodeGuide,
          tg.Body,
          tg.PosterURL,
          t.UserID,
          t.FreeTorrent,
          t.DoubleTorrent,
          t.Season,
          t.Episode,
          tg.Time,
          tg.TMDb,
          tg.TVMAZE,
          t.Anonymous,
          t.AirDate
          FROM torrents_group AS tg
          JOIN torrents AS t ON t.GroupID = tg.ID
          WHERE tg.ID='$GroupID'");
    if ($DB->record_count() == 0) { error(404); }
    list($CategoryID, $Name, $Image, $Mediainfo, $Screens, $Trailer, $Synopsis, $EpisodeGuide, $Body, $PosterURL, $AuthorID, $Free, $Doubleseed, $Season, 
         $Episode, $AddedTime, $TMDb, $TVMaze, $IsAnon, $AirDate) = $DB->next_record();

    $CanEdit = check_perms('torrents_edit');
    if (!$CanEdit) {

        if ($LoggedUser['ID'] == $AuthorID) {
            if (check_perms ('site_edit_override_timelock') || time_ago($AddedTime)< TORRENT_EDIT_TIME || $Review['Status'] != 'Okay') {
                $CanEdit = true;
            } else {
                error("Sorry - you only have ". date('i \m\i\n\s', TORRENT_EDIT_TIME). "  to edit your torrent before it is automatically locked.");
            }
        }

    }
}

// Pull category tag
$DB->query("SELECT
tag
FROM categories
WHERE id = $CategoryID");
list($Category_tag) = $DB->next_record();

$Synopsis = str_replace('\\','',$Synopsis); // remove extra '\', had to be done here to cover existing ones
if(substr($Synopsis,0,5) == "&#39;") // cover old way
 $Synopsis = substr($Synopsis,5,-5); // remove extra ' at start and end of the string

if($Season && intval($Season)<10) $Season = "0".$Season;
if($Episode && intval($Episode)<10) $Episode = "0".$Episode;
if($AirDate && preg_match('/\d\d\d\d-\d\d-\d\d/', $AirDate, $matches,PREG_OFFSET_CAPTURE)) $AirDate = $matches[0][0];

if (!$CanEdit) { error(403); }

if (!isset($Text)) {
    $Text = new TEXT;
}

show_header('Edit torrent','bbcode,edittorrent','editgroup');

// Start printing form
?>
<div class="thin">
<?php
    if ($Err) { ?>
            <div id="messagebar" class="messagebar alert"><?=$Err?></div>
<?php 	} ?>

    <h2>Rename Title</h2>
    <div class="box pad">
        <form action="torrents.php" method="post" id="rename">
            <div>
                 <input type="hidden" name="action" value="rename" />
                 <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                 <input type="hidden" name="groupid" value="<?=$GroupID?>" />
                 <input type="hidden" name="oldcategoryid" value="<?=$CategoryID?>" />
                 <table class="rename">
                  <tr>
                   <td colspan="3"><input type="text" name="name" class="title" value="<?=$Name?>" /></td>
                   <td><input type="submit" class="submit" value="Rename" /></td>
                <?php if(check_perms ('torrents_scrape')){ // staff only ?>
                  </tr>
                  <tr>   
                   <td style="text-align:left">Set correct TVMaze ID: <input type="text" name="tvmaze" class="tvmazeid" value="<?=$TVMaze?>" /></td>
                   <td>Set correct Season (in 01 format): <input type="text" name="season" id="season" class="number" value="<?=$Season?>" /></td>
                   <td>Set correct Episode (in 01 format): <input type="text" name="episode" id="episode" class="number" value="<?=$Episode?>" /></td>
                  </tr>
                  <tr> 
                   <td colspan="3" style="text-align:left">Set correct Air Date (in yyyy-mm-dd format): 
                   <input type="text" name="airdate" id="airdate" class="number"  style="width:150px" value="<?=$AirDate?>" />
                    Will override Season + Episode if they're empty and vise versa</td>
                   <td><input type="submit" class="submit" value="Scrape!" onclick="document.getElementById('rename').elements[0].value='scrape';" /></td>
                 </tr>
                 <tr>  
                     <td style="text-align:left">
                        TMDb TV ID: 
                        <input type="text" name="tmdbtv" id="tmdbtv" size="15" value="" onclick='document.getElementById("tmdb").value="0";' />
                     </td>
                     <td>   
                        <b>Prev. TMDb ID: <?=$TMDb?></b>, will override TVMaze
                     </td>
                     <td>   
                      <div style="float:right;">
                        TMDb Movie ID: 
                        <input type="text" name="tmdb" id="tmdb" size="15" value="" onclick='document.getElementById("tmdbtv").value="0";
                        document.getElementById("airdate").value="0";
                        document.getElementById("season").value="0";
                        document.getElementById("episode").value="0";' />
                      </div>
                     </td>
                 </tr>    
                <?php } ?>                    
                </table>
            </div>
        </form>
    </div>

    <h2>Edit <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></h2>
    <div class="box pad">
        <form id="edit_torrent" action="torrents.php" method="post">
            <div>
                <input type="hidden" name="action" value="takegroupedit" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="groupid" value="<?=$GroupID?>" />
                <input type="hidden" name="authorid" value="<?=$AuthorID?>" />
                <input type="hidden" name="name" value="<?=$Name?>" />
                                <input type="hidden" name="oldcategoryid" value="<?=$CategoryID?>" />
                                <h3>Category</h3>
                                <select name="categoryid">
                                <?php  foreach ($NewCategories as $category) { ?>
                                <option <?=$CategoryID==$category['id'] ? 'selected="selected"' : ''?> value="<?=$category['id']?>"><?=$category['name']?></option>
                                <?php  } ?>
                                </select>
                <?php if(check_perms ('torrents_scrape')){ // staff only ?>                         
                                <div style="text-align:left; display:inline; margin:2px;">TVMaze ID: <input type="text" name="tvmazeM" class="tvmazeid" value="<?=$TVMaze?>" /></div>
                                <div style="text-align:left; display:inline; margin:2px;">Season (01 format): <input type="text" name="seasonM" class="number" value="<?=$Season?>" /></div>
                                <div style="text-align:left; display:inline; margin:2px;">Episode (01 format): <input type="text" name="episodeM" class="number" value="<?=$Episode?>" /></div>                        
                                <div style="text-align:left; display:inline; margin:2px;">Air Date (yyyy-mm-dd): <input type="text" name="airdateM" class="number"  style="width:100px" value="<?=$AirDate?>" /></div>
                <?php }else { ?>                 
                                <input type="hidden" name="tvmazeM" class="tvmazeid" value="<?=$TVMaze?>" />
                                <input type="hidden" name="seasonM" class="number" value="<?=$Season?>" />
                                <input type="hidden" name="episodeM" class="number" value="<?=$Episode?>" />                     
                                <input type="hidden" name="airdateM" class="number"  style="width:100px" value="<?=$AirDate?>" />
                <?php } ?>
                        <br /><br />                      
                        <div id="preview" class="hidden"  style="text-align:left;">
                        </div>
                        <div id="editor">
                       
                       <?php if(check_perms ('torrents_edit')){ // staff only ?>
                                               
                                <h3 style="display:inline">Banner Image</h3>
                                 &nbsp;&nbsp; (Enter the full url for the banner).</strong><br/>
                                <input type="text" id="image" name="image" class="long" value="<?=$Image?>" /><br /><br />
                       
                       <?php }else{                             // staff only ?>                                 

                                <input type="hidden" id="image" name="image" value="<?=$Image?>" />

                       <?php } ?>

                                <h3>Media Info</h3>
                                    <?php  $Text->display_bbcode_assistant("mediainfo", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="mediainfo" name="mediainfo" class="long" rows="20"><?=$Mediainfo?></textarea><br /><br />

                      <?php if($Category_tag == 'season'){ // season template ?>
 
                        <?php if(check_perms ('torrents_edit')){ // staff only ?>

                                <h3 style="display:inline">Poster Image</h3>
                                 &nbsp;&nbsp; (Enter the full url for the poster).</strong><br/>
                                <input type="text" id="poster" name="poster" class="long" value="<?=$PosterURL?>" /><br /><br />

                                <h3>Show Info</h3>
                                    <?php  $Text->display_bbcode_assistant("showinfo", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="showinfo" name="showinfo" class="long" rows="10"><?=$Synopsis?></textarea><br /><br />

                                <h3>Episode List</h3>
                                    <?php  $Text->display_bbcode_assistant("episodeguide", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="episodeguide" name="episodeguide" class="long" rows="20"><?=$EpisodeGuide?></textarea><br /><br />

                        <?php }else{                             // users ?>

                                <div style="display:none;">
                                 <input type="hidden" id="poster" name="poster" value="<?=$PosterURL?>" />
                                 <textarea id="showinfo" name="showinfo" class="hidden" rows="1"><?=$Synopsis?></textarea>
                                 <textarea id="episodeguide" name="episodeguide" class="hidden" rows="1"><?=$EpisodeGuide?></textarea>
                                </div>

                        <?php }                                 // staff only ?>                                

                              <?php /*  <h3>Screens</h3>
                                    <?php  $Text->display_bbcode_assistant("screens", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="screens" name="screens" class="long" rows="6"><?=$Screens?></textarea><br /><br />

                                <h3>Trailer</h3>
                                    <?php  $Text->display_bbcode_assistant("trailer", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="trailer" name="trailer" class="long" rows="3"><?=$Trailer?></textarea><br /><br />*/ ?>

                      <?php }else{ // episode template ?>                                

                        <?php if(check_perms ('torrents_edit')){ // staff only ?>

                                <h3 style="display:inline">Poster Image</h3>
                                 &nbsp;&nbsp; (Enter the full url for the poster)</strong><br/>
                                <input type="text" id="poster" name="poster" class="long" value="<?=$PosterURL?>" /><br /><br />

                                <h3>Show Info</h3>
                                    <?php  $Text->display_bbcode_assistant("showinfo", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="showinfo" name="showinfo" class="long" rows="10"><?=$Synopsis?></textarea><br /><br />
 
                        <?php }else{ ?>

                                <div style="display:none;">
                                 <input type="hidden" id="poster" name="poster" value="<?=$PosterURL?>" />
                                 <textarea id="showinfo" name="showinfo" class="hidden" rows="1"><?=$Synopsis?></textarea>
                                </div>

                        <?php }?>  
                      <?php } ?>
                                             
 								<?php if($Body){?>                                 
                                <h3>Description</h3>
                                    <?php  $Text->display_bbcode_assistant("body", get_permissions_advtags($AuthorID)); ?>
                                <textarea id="body" name="body" class="long" rows="20"><?=$Body?></textarea><br /><br />
                        <?php }?>
                                                      
                        </div>
                        <h3>Edit summary</h3>
                <input type="text" name="summary" class="long" value="<?=$EditSummary?>" /><br />
                <div style="text-align: center;">
                                <input id="preview_button" type="button" value="Preview" onclick="Preview_Toggle();" />
                                <input type="submit" value="Submit" />
                        </div>
            </div>
        </form>
    </div>

<?php  if (check_perms('torrents_freeleech') || check_perms('torrents_doubleseed')) { ?>
    <h2>
<?php  if (check_perms('torrents_freeleech')) { ?>Freeleech/<?php }?>    
    Doubleseed</h2>
    <div class="box pad">
        <form action="torrents.php" method="post">
            <input type="hidden" name="action" value="nonwikiedit" />
            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
            <input type="hidden" name="groupid" value="<?=$GroupID?>" />
            <table cellpadding="3" cellspacing="1" border="0" class="border" width="100%">
<?php  if (check_perms('torrents_freeleech')) { ?>
                <tr>
                    <td class="label">Freeleech</td>
                    <td>
            <input name="freeleech" value="0" type="radio"<?php  if($Free!=1) echo ' checked="checked"';?>/> None&nbsp;&nbsp;
            <input name="freeleech" value="1" type="radio"<?php  if($Free==1) echo ' checked="checked"';?>/> Freeleech&nbsp;&nbsp;
                    </td>
                </tr>
<?php  }
       if (check_perms('torrents_doubleseed')) { ?>
                <tr>
                    <td class="label">Doubleseed</td>
                    <td>
            <input name="doubleseed" value="0" type="radio"<?php  if($Doubleseed!=1) echo ' checked="checked"';?>/> None&nbsp;&nbsp;
            <input name="doubleseed" value="1" type="radio"<?php  if($Doubleseed==1) echo ' checked="checked"';?>/> Doubleseed&nbsp;&nbsp;
                    </td>
                </tr>
<?php  } ?>
            </table>
            <input type="submit" value="Edit" />
        </form>
    </div>
<?php  } ?>
</div>
<?php
show_footer();
