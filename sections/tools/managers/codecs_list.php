<?php
if (!check_perms('admin_manage_parser')) { error(403); }

show_header('Manage automatic Codecs');
$DB->query("SELECT
    ID,
    Codec,
    Sort
    FROM torrents_codecs
    ORDER BY Sort ASC");
$TorrentCodecs = $DB->to_array(false, MYSQLI_NUM);
?>
<div class="thin">
<h2>Automatic Codecs</h2>
<table>
    <tr class="head">
        <td colspan="6">Add Automatic Codec</td>
    </tr>
    <tr class="colhead">
        <td width="25%"><span title="Torrent codec">
                    Codec</span></td>
        <td width="10%"><span>
                    Sort</span></td>
        <td width="10%"><span>
                    Submit</span></td>
    </tr>
    <tr class="rowa">
    <form action="tools.php" method="post">
        <input type="hidden" name="action" value="cx_alter" />
        <input type="hidden" name="modify" value="codec" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <td>
            <input class="long"  type="text" name="codec" />
        </td>
        <td>
            <input class="long"  type="text" name="sort" />
        </td>
        <td>
            <input type="submit" value="Create" />
        </td>
    </form>
    </tr>
    <tr><td colspan="2">Sort order: 1 - Source; 2 - Codec; 100 - Resolution; 200 - Release Groups; 300 - Proper Tags</td></tr>
</table>
<br/><br/>
<table>
    <tr class="head">
        <td colspan="6">Manage Automatic Codecs</td>
    </tr>
</table>
<?php  $Row = 'b';
foreach($TorrentCodecs as $TorrentCodec) {
    list($CodecID, $Codec, $Sort) = $TorrentCodec;
    $DB->query("SELECT ID, AltCodec FROM torrents_codecs_alt WHERE CodecID=$CodecID");
    $TorrentCodecsAlt = $DB->to_array(false, MYSQLI_NUM);
?>
<a id="codec_<?=$CodecID?>" class="anchor"></a>
<table>
    <tr class="colhead">
        <td width="25%"><span title="Torrent codec">
                    Codec</span></td>
        <td width="10%">
                    Sort</span></td>
        <td width="10%"><span>
                    Submit</span></td>
    </tr>
    <tr class="colhead">
        <form action="tools.php" method="post">
            <td>
                <input type="hidden" name="action" value="cx_alter" />
                <input type="hidden" name="modify" value="codec" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <input type="hidden" name="id" value="<?=$CodecID?>" />
                <input class="long" type="text" name="codec" value="<?=display_str($Codec)?>" />
            </td>
            <td>
                <input class="long"  type="text" name="sort" value="<?=display_str($Sort)?>" />
            </td>
            <td>
                <input type="submit" name="submit" value="Edit" />
                <input type="submit" name="submit" value="Delete" />
            </td>
        </form>
    </tr>
    <tr>
        <td colspan=3><table style="border:none;">
            <tr class="colhead">
                <td style="border:none;"></td>
                <td><span title="Torrent codec">
                            Alternative Codec</span></td>
                <td><span>
                            Submit</span></td>
            </tr>
<?php
    foreach($TorrentCodecsAlt as $TorrentCodecAlt) {
        list($AltCodecID, $AltCodec) = $TorrentCodecAlt;
        $Row = ($Row === 'a' ? 'b' : 'a');
?>
            <tr class="row<?=$Row?>">
                <form action="tools.php" method="post">
                <td style="border:none;"></td>
                <td>
                    <input type="hidden" name="action" value="cx_alter" />
                    <input type="hidden" name="modify" value="codec_alt" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input type="hidden" name="id" value="<?=$AltCodecID?>" />
                    <input class="long" type="text" name="codec" value="<?=display_str($AltCodec)?>" />
                </td>
                <td>
                    <input type="submit" name="submit" value="Edit" />
                    <input type="submit" name="submit" value="Delete" />
                </td>
                </form>
            </tr>
<?php
    } ?>
        <tr class="colhead">
                <form action="tools.php" method="post">
                <td style="border:none;"></td>
                <td>
                    <input type="hidden" name="action" value="cx_alter" />
                    <input type="hidden" name="modify" value="codec_alt" />
                    <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                    <input type="hidden" name="id" value="<?=$CodecID?>" />
                    <input class="long" type="text" name="codec" value="" />
                </td>
                <td>
                    <input type="submit" name="submit" value="Add" />
                </td>
                </form>
            </tr>
        </table></td>
    </tr>
</table>
</br></br>
<?php  } ?>
</div>
<?php
show_footer();
