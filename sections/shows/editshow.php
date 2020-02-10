<?php
/************************************************************************
||------------|| Edit show page ||------------------------------||
************************************************************************/

if (!check_perms('torrents_delete')) { error(403); }; // staff only

$ShowID = $_GET['showid'];
if (!is_number($ShowID) || !$ShowID) { error(0); }

$DB->query("SELECT ShowTitle, Synopsis, ShowInfo, Genres, NetworkName, WebChannel, NetworkUrl, PosterUrl, Rating, Weight, Premiered, Trailer, FanArtUrl FROM shows 
           WHERE ID='$ShowID'");
if ($DB->record_count() == 0) { error(404); }
list($ShowTitle, $Synopsis, $ShowInfo, $Genres, $NetworkName, $WebChannel, $NetworkUrl, $PosterUrl, $Rating, $Weight, $Premiered, $Trailer, $FanArtUrl) = $DB->next_record();

if (!isset($Text)) {
    $Text = new TEXT;
}

$Genres = str_replace('|', ', ', $Genres);
$Premiered = substr($Premiered, 0, 10);
 
show_header('Edit show','bbcode,edittorrent','editgroup,editshow');

// Start printing form
?>
<div class="thin">
<?php
    if ($Err) { ?>
            <div id="messagebar" class="messagebar alert"><?=$Err?></div>
<?php 	} ?>

    <h2>Edit <a href="torrents.php?action=show&showid=<?=$ShowID?>"><?=$ShowTitle?></a></h2>
    <div class="box pad">
        <form id="edit_show" action="shows.php" method="post">
            <div>
                <input type="hidden" name="action" value="takeshowedit" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="showid" value="<?=$ShowID?>" />

                <h3>Title: <input type="text" name="title" style="display:inline; width:90%;" value="<?=$ShowTitle?>" /></h3><br />

                <h3>Synopsis:</h3>
                 <?php  $Text->display_bbcode_assistant("synopsis"); ?>
                 <textarea id="synopsis" name="synopsis" class="long" rows="15"><?=$Synopsis?></textarea><br /><br />

                <h3>Show Info: (used for hover tooltip)</h3>
                 <textarea id="showinfo" name="showinfo" class="long" rows="4"><?=$ShowInfo?></textarea><br /><br />
                 
                <table>
                 <tr>
                  <th>Genres:</th><td><input type="text" id="genres" name="genres" class="long" value="<?=$Genres?>" /></td>                
                  <th>Rating:</th><td><input type="text" id="rating" name="rating" class="medium" value="<?=$Rating?>" /></td>                
                 </tr>
                 <tr>
                  <th>Premiered:</th><td><input type="text" id="premiered" name="premiered" class="short" value="<?=$Premiered?>" /> (yyyy-mm-dd)</td>                
                  <th>Weight:</th><td><input type="text" id="weight" name="weight" class="medium" value="<?=$Weight?>" /></td>                
                 </tr>
                 <tr>
                  <th>Network:</th><td><input type="text" id="network" name="network" class="long" value="<?=$NetworkName?>" /></td>                
                  <th>Web Channel:</th><td><input type="text" id="webchannel" name="webchannel" class="medium" value="<?=$WebChannel?>" /></td>                
                 </tr>
                 <tr>
                   <th>Network Url:</th><td colspan="4"><input type="text" id="networkurl" name="networkurl" class="long" value="<?=$NetworkUrl?>" /></td>            
                 </tr>
                 <tr>
                   <th>Poster Url:</th><td colspan="4"><input type="text" id="posterurl" name="posterurl" class="long" value="<?=$PosterUrl?>" /></td>            
                 </tr>
                 	<tr>
                   <th>FanArt Url:</th><td colspan="4"><input type="text" id="fanarturl" name="fanarturl" class="long" value="<?=$FanArtUrl?>" /></td>            
                 </tr>
			  <?php /*<tr>
                   <th>Trailer:</th><td colspan="4"><input type="text" id="trailer" name="trailer" class="long" value="<?=$Trailer?>" /></td>            
                 </tr>*/ ?>
                </table>
               
                <div style="text-align: center;">
                     <input type="submit" value="Submit" />
                </div>
            </div>
        </form>
    </div>
</div>
<?php
show_footer();
