<?php
/********************************************************************************
 ************ Torrent form class *************** upload.php                  ****
 ********************************************************************************
 ** This class is used to create the upload form                               **
 ** It is broken down into several functions - head(), foot(),                 **
 ** and	                                                                      **
 ** simple_form() [everything else].                                           **
 ********************************************************************************/

class TORRENT_FORM
{
    var $NewCategories = array();
    
    public $Media = array();
    public $NewTorrent = false;
    public $Torrent = array();
    public $Error = false;
    public $TorrentID = false;
    public $Disabled = 'disabled';
    public $Readonly = 'readonly';
        
    public function TORRENT_FORM($Torrent = false, $Error = false, $NewTorrent = true)
    {
        $this->NewTorrent = $NewTorrent;
        $this->Torrent = $Torrent;
        $this->Error = $Error;

        global $NewCategories, $Media, $TorrentID;

        $this->NewCategories = $NewCategories;
        $this->Media = $Media;
        $this->TorrentID = $TorrentID;
    }

    public function head()
    {
        global $Text,$LoggedUser,$DupeResults,$DB;
        $DB->query("SELECT min_upload_screenshots FROM categories WHERE tag = 'season'");
        list($minUploadScreenshots) = $DB->next_record();
        $DB->query("SELECT id FROM categories WHERE tag = 'season'");
        list($Season) = $DB->next_record();
        $DB->query("SELECT id FROM categories WHERE tag = 'episode'");
        list($Episode) = $DB->next_record();
?>
<a id="uploadform"></a>

<?php	if ($this->NewTorrent) {
            $ANNOUNCE_URL = ANNOUNCE_URL;
            if ($LoggedUser['SSLTracker'])
                $ANNOUNCE_URL = SSL_ANNOUNCE_URL;
?>
    <p style="text-align: center;">
        Your personal announce url is:<br />
        <input type="text" value="<?= $ANNOUNCE_URL.'/'.$LoggedUser['torrent_pass'].'/announce'?>" size="71" onfocus="this.select()" />
    </p>
<?php		}

            //for testing form vars set action="http://www.tipjar.com/cgi-bin/test"
?>
      <div id="messagebar" class="messagebar alert<?php if(!$this->Error) echo ' hidden'?>"><?php if($this->Error) echo ($this->Error) ; ?></div><br />
      <div id="uploadpreviewbody" class="hidden">
            <div id="contentpreview" style="text-align:left;"></div>
    </div>
    <form action="" enctype="multipart/form-data" method="post" id="upload_table" onsubmit="$('#post').raw().disabled = 'disabled'">
<?php
    if (is_array($DupeResults)) { // && $DupeResults['DupeResults']) {

        if ($DupeResults['DupeResults']) {
            $INLINE = true;
            $DupeResults['Title'] = $this->Torrent['Title'];
            $DupeResults['SearchTags'] = $this->Torrent['TagList'];
            include(SERVER_ROOT . '/sections/upload/display_dupes.php');
?>
            <div class="box pad shadow center rowa">
                If you have checked and are certain these are not dupes check this box to ignore the dupe check
                <div style="margin-top:6px"><strong style="font-size:1.2em;">Skip Dupe Check:</strong> <input type="checkbox" name="ignoredupes" value="1"
                <?php                           if (isset($this->Torrent['IgnoreDupes']) && $this->Torrent['IgnoreDupes']==1) {
                                                    echo ' checked="checked"'; }   ?> /> </div>
            </div>
<?php
        } else {
?>
            <div class="box pad shadow center rowa">
                Checked <?=$DupeResults['NumChecked']?> file<?=($DupeResults['NumChecked']>1?'s':'')?>.<br/>There are no exact size dupes in this torrent file <?=$Text->full_format("<3", true)?>
            </div>
<?php
        }
?>
        <br/>
<?php
    }
?>
        <div>
            <input type="hidden" name="submit" value="true" />
            <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
<?php	if (!$this->NewTorrent) { ?>
            <input type="hidden" name="action" value="takeedit" />
            <input type="hidden" name="torrentid" value="<?=display_str($this->TorrentID)?>" />
<?php	} else {
            if ($this->Torrent && $this->Torrent['GroupID']) { ?>
            <input type="hidden" name="groupid" value="<?=display_str($this->Torrent['GroupID'])?>" />
<?php		}
            if ($this->Torrent && $this->Torrent['RequestID']) { ?>
            <input type="hidden" name="requestid" value="<?=display_str($this->Torrent['RequestID'])?>" />
<?php			}
            if ($this->Torrent && $this->Torrent['TemplateID']) { ?>
            <input type="hidden" name="templateid" value="<?=display_str($this->Torrent['TemplateID'])?>" />
<?php			}
            if ($this->Torrent && $this->Torrent['TemplateFooter']) { ?>
            <input type="hidden" name="templatefooter" value="<?=display_str($this->Torrent['TemplateFooter'])?>" />
<?php			}
        } ?>
        </div>
          <div class="head">Upload Torrent</div>
        <table cellpadding="3" cellspacing="1" border="0" class="border" width="100%">
<?php	if ($this->NewTorrent) { ?>
                <tr class="uploadbody">
                <td class="label">
                    Torrent file
                </td>
                <td>
<?php            if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0)  { ?>
                  <input type="hidden" name="tempfileid" value="<?=display_str($this->Torrent['tempfileid'])?>" />
                  <input type="hidden" name="tempfilename" value="<?=display_str($this->Torrent['tempfilename'])?>" />
                  <input id="file" type="text" size="70" disabled="disabled" value="<?=display_str($this->Torrent['tempfilename'])?>" />
                  <br/>[already loaded file]
<?php            } else {    ?>
                  <input type="hidden" name="MAX_FILE_SIZE" value="<?=MAX_FILE_SIZE_BYTES?>" />
                  <input id="file" type="file" name="file_input" size="70" onchange="$('#messagebar').hide(); ShowNext('categorywrap');" />
                  <br/>[max .torrent filesize: <?=strtolower(get_size(MAX_FILE_SIZE_BYTES))?>; set private flag and source to NBL to avoid re-downloading the torrent]
<?php            } ?>
                </td>
                </tr>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>
                <tr id="categorywrap" class="uploadbody" style="display:table-row;">
<?php } else { ?>            
                <tr id="categorywrap" class="uploadbody" style="display:none;">
<?php } ?>      <td class="label">
                 Category
                </td>
                <td>
                 <select id="category" name="category" onchange="SwitchCat(<?=$Episode?>,<?=$Season?>); change_tagtext();">
                  <option value="0">---</option>
                  <?php foreach ($this->NewCategories as $category) { ?>
                    <option value="<?=$category['id']?>"<?php
                    if (isset($this->Torrent['Category']) && $this->Torrent['Category']==$category['id']) {
                        echo ' selected="selected"';
                    }   ?>><?=$category['name']?></option>
                  <?php } ?>
                 </select>
              </td>
            </tr>

<?php		}//if ?>

<?php	} // function head

    public function simple_form()
    {
        global $Text, $DB, $Cache, $LoggedUser;
        $Torrent = $this->Torrent;
        $DB->query("SELECT min_upload_screenshots FROM categories WHERE tag = 'season'");
        list($minUploadScreenshots) = $DB->next_record();
        $DB->query("SELECT id FROM categories WHERE tag = 'season'");
        list($Season) = $DB->next_record();
        $DB->query("SELECT id FROM categories WHERE tag = 'episode'");
        list($Episode) = $DB->next_record();
?>
<?php	if ($this->NewTorrent) {  ?>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>
            <tr id="name" class="uploadbody" style="display:table-row;">
<?php } else { ?>            
            <tr id="name" class="uploadbody" style="display:none;">
<?php } ?>            
                <td class="label">Title</td>
                <td>
                    <input type="text" id="title" name="title" class="long" value="<?=display_str($Torrent['Title']) ?>" />
                </td>
            </tr>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>
            <tr id="tvmaze" class="uploadbody" style="display:table-row;">
<?php } else { ?>
            <tr id="tvmaze" class="uploadbody" style="display:none;">
<?php } ?>             
                <td class="label">TVMaze ID: (Optional)</td>
                <td> http://www.tvmaze.com/shows/ <input type="text" data-lpignore="true" style="text-align:center;" id="tvmazeid" name="tvmazeid" size="5" value="<?=display_str($Torrent['TVMAZE']) ?>" /> / (Just enter TVMaze ID)<br/>e.g in http://www.tvmaze.com/shows/3728/the-mist (<b>"3728"</b> is ID)</td>
            </tr>
<?php
                $taginfo = get_article('tagrulesinline');
                if ($taginfo) {
?>
                <tr class="uploadbody">
                    <td class="label"></td>
                    <td><?=$Text->full_format($taginfo, true)?></td>
                </tr>
<?php
                }
?>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>
            <tr id="tags" class="uploadbody" style="table-row;">
<?php } else { ?>
            <tr id="tags" class="uploadbody" style="display:none;">
<?php } ?>              
                <td class="label">Tags</td>
                <td>
                    <div>
                        search tags:
                        <div class="autoresults">
                            <input type="text" id="torrentssearch" value="search tags"
                                            onfocus="if (this.value == 'search tags') this.value='';"
                                            onblur="if (this.value == '') this.value='search tags';"
                                            onkeyup="return autocomp.keyup(event);"
                                            onkeydown="return autocomp.keydown(event);"
                                            autocomplete="off"
                                            title="enter text to search for tags, click (or enter) to select a tag from the drop-down (BETA)" />
                            <ul id="torrentscomplete"></ul>
                         </div>
<?php
                $GenreTags = $Cache->get_value('genre_tags_upload');
                if (!$GenreTags) {
                    $DB->query("(SELECT Name, Uses FROM tags WHERE TagType='genre' ORDER BY Uses DESC LIMIT 80) ORDER BY Name");
                    $GenreTags = $DB->to_array();
                    $Cache->cache_value('genre_tags_upload', $GenreTags, 3600 * 24);
                }

                if ($GenreTags) { ?>
                        &nbsp; popular tags:
                        <select id="genre_tags" name="genre_tags" title="select popular tags from the drop down"
                                onchange="add_tag();return false;" >
                            <option>---</option>
<?php                       foreach ($GenreTags as $Tag) { ?>
                                <option value="<?=$Tag['Name']?>"><?="$Tag[Name] ($Tag[Uses])"?></option>
<?php                       }   ?>
                        </select>
<?php           }  ?>
                        &nbsp;&nbsp;
                        <div style="display:inline-block" id="tagtext"></div>
                    </div>
                    <textarea id="tags" name="tags" class="medium" rows="3"><?=display_str($Torrent['TagList']) ?></textarea>

                    <br />
                </td>
            </tr>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>
            <tr id="banner" class="uploadbody" style="display:table-row;">
<?php } else { ?>
            <tr id="banner" class="uploadbody" style="display:none;">
<?php } ?>             
             <td class="label">Banner Image</td>
             <td>
              <input type="text" id="image" class="long" name="image" value="<?=display_str($Torrent['Image'])?>" <?=$this->Readonly?> />
             </td>
            </tr>

<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>            
            <tr id="mediainfowrap" class="uploadbody"  style="display:table-row;">
<?php } else { ?>           
            <tr id="mediainfowrap" class="uploadbody"  style="display:none;">
<?php } ?> 
                <td class="label">Media Info</td>
                <td>
                   <textarea name="media" id="media" class="long" rows="15" ><?=display_str($Torrent['MediaInfoRaw']);?></textarea><br />
                   <textarea style="display:none;" name="mediaclean" id="mediaclean" class="long" rows="15" ><?=display_str($Torrent['MediaInfo']);?></textarea><br />
                   Paste Raw Media Info here (Required)
                </td>
            </tr>

<?php /*<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0 && $this->Torrent['Category'] == $Season) { ?>            
            <tr id="screenswrap" class="uploadbody"  style="display:table-row;">
<?php } else { ?>           
            <tr id="screenswrap" class="uploadbody"  style="display:none;">
<?php } ?> 
                <td class="label">Screens</td>
                <td>
                   <textarea name="screens" id="screens" class="long" rows="2" onchange="SecureMedia(this.value,this.id);" <?=$this->Readonly?> /><br />
                   <textarea style="display:none;" name="screensclean" id="screensclean" class="long" rows="2"
                   Paste <?php if($minUploadScreenshots) echo $minUploadScreenshots?> Screens here in following format [img] image url [/img] (Required)
                </td>
            </tr>

<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0 && $this->Torrent['Category'] == $Season) { ?>            
            <tr id="trailerwrap" class="uploadbody"  style="display:table-row;">
<?php } else { ?>           
            <tr id="trailerwrap" class="uploadbody"  style="display:none;">
<?php } ?> 
                <td class="label">Trailer (Optional)</td>
                <td>
                   <input type="text" id="trailer" name="trailer" class="long" onchange="SecureMedia(this.value,this.id);" <?=$this->Readonly?> /><br />
                   <input style="display:none;" type="text" id="trailerclean" name="trailerclean" class="long" <?=$this->Readonly?> /><br />
                   <font color="red">This field is disabled</font>                
                </td>
            </tr>*/ ?>
                        
            <tr id="checkbutton" class="uploadbody" style="display:none;">
            <td></td>
             <td>
              <span style="float:left">
                <input type="button" id="checkonly" name="checkonly" title="Click this button to check for Dupes and to autofill title, tags and banner" 
                 value="Check Upload/Autofill" onclick="$('#messagebar').hide(); this.value='Loading...'; FillDescription(<?=$minUploadScreenshots?>,<?=$Episode?>);" />
               </span>
             </td>                       
            </tr>
            
            <tr id="description" class="uploadbody"  style="display:none;">
                <td class="label">Description</td>
                <td>
                 <?php $Text->display_bbcode_assistant("desc", get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions'])); ?>
                 <textarea name="desc" id="desc" class="long" rows="36"><?=display_str($Torrent['GroupDescription']); ?></textarea>
                </td>
            </tr>
<?php	} ?>

<?php	}//function simple_form

    public function foot()
    {
        global $LoggedUser, $DB;
        $DB->query("SELECT min_upload_screenshots FROM categories WHERE tag = 'season'");
        list($minUploadScreenshots) = $DB->next_record();
        $DB->query("SELECT id FROM categories WHERE tag = 'episode'");
        list($Episode) = $DB->next_record();
        $Torrent = $this->Torrent;
        if ($this->NewTorrent && in_array('uploadanon', $LoggedUser['Paranoia'])) {
            $Torrent['Anonymous'] = 1;
        }

        if (check_perms('site_upload_anon')) {
?>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>            
                    <tr id="anon" style="display:table-row;">
<?php } else { ?> 
                    <tr id="anon" style="display:none;">
<?php } ?>                    
                        <td class="label">Upload Anonymously</td>
                        <td>

                            <input onclick="$('#warnbox').hide();"  name="anonymous" value="0" type="radio"<?php if($Torrent['Anonymous']!=1) echo ' checked="checked"';?>/> Show uploader name&nbsp;&nbsp;
                            <input onclick="$('#warnbox').show();" name="anonymous" value="1" type="radio"<?php if($Torrent['Anonymous']==1) echo ' checked="checked"';?>/> Hide uploader name (Upload Anonymously)&nbsp;&nbsp;
<?php
                            if (check_paranoia('tags', $LoggedUser['Paranoia'], 10000 )) {  ?>
                                <div id="warnbox" class="warnbox hidden" >
                                    NOTE: Your paranoia settings for tags is <strong>Tags: Show List</strong>. This will allow other users to see tags you have added to your own uploads in a list from which
                                    it might be possible to deduce your uploaded torrents. [<strong><a target="_blank" href="userhistory.php?action=tag_history&type=added&way=DESC&order=AddedBy&include=own&userid=<?=$LoggedUser['ID']?>" title="list of tags you have added">view your tag list</a></strong>]
                                    <br/>To be absolutely anonymous you should change your paranoia settings on your user page to Tags: Hide List (you can show the count without giving anything away).
                                    [<strong><a target="_blank" href="user.php?action=edit&userid=<?=$LoggedUser['ID']?>#paranoia" title="open user page on your paranoia settings">open paranoia settings</a></strong>]
                                </div>
<?php                          }       ?>
                        </td>
                    </tr>
<?php
        }

        if (check_perms('torrents_freeleech')) {
?>
<?php	if (isset($this->Torrent['tempfileid']) && is_number($this->Torrent['tempfileid']) && $this->Torrent['tempfileid']>0) { ?>            
            <tr id="freetorrent" class="uploadbody" style="display:table-row;">
<?php } else { ?>             
            <tr id="freetorrent" class="uploadbody" style="display:none;">
<?php } ?>            
                <td class="label">Freeleech</td>
                <td>
                    <select name="freeleech">
<?php       $FL = array("Normal", "Free");
            foreach ($FL as $Key => $Name) {     ?>
                        <option value="<?=$Key?>" <?=($Key == $Torrent['FreeLeech'] ? ' selected="selected"' : '')?>><?=$Name?></option>
<?php          }       ?>
                    </select>
                </td>
            </tr>
<?php
        }
?>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <p>Be sure that your torrent is approved by the <a href="articles.php?topic=upload">rules</a>. Not doing this will result in a <strong>warning</strong> or <strong>worse</strong>.</p>
<?php	if ($this->NewTorrent) { ?>
                    <p>After uploading the torrent, you will have a one hour grace period during which no one other than you can fill requests with this torrent. Make use of this time wisely, and search the requests. </p>

                              <input id="post_preview" type="button" value="Preview" onclick="if (this.preview || !FillDescription(<?=$minUploadScreenshots?>,<?=$Episode?>)) {Upload_Quick_Edit();} else {Upload_Quick_Preview();}" />
    <?php	} ?>
                    <input id="post" type="button" onclick="document.getElementById('title').value=''; FillDescription(<?=$minUploadScreenshots?>,<?=$Episode?>);"<?php if ($this->NewTorrent) { echo "value=\"Upload Torrent\""; } else { echo "value=\"Edit torrent\"";} ?> />
                </td>
            </tr>
        </table>
    </form>

<?php	} //function foot

}//class
