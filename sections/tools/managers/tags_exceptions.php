<?php
if (!check_perms('site_manage_tags')) {
    error(403);
}

$UseMultiInterface= true;

show_header('Tags Exceptions Manager');
?>
<div class="thin">
    <h2>Tags Manager</h2>
    <div class="linkbox">
        <a href="tools.php?action=official_tags">[Tags Manager]</a>
        <a href="tools.php?action=official_synonyms">[Synonyms Manager]</a>
        <a style="font-weight: bold" href="tools.php?action=tags_exceptions">[Exceptions Manager]</a>
    </div>
<?php
    if (isset($_GET['rst']) && is_number($_GET['rst'])) {
        $Result = (int) $_GET['rst'];
        $ResultMessage = display_str($_GET['msg']);
        if ($Result !== 1)
            $AlertClass = ' alert';

        if ($ResultMessage) {
?>
            <div class="messagebar<?= $AlertClass ?>"><?= $ResultMessage ?></div>
<?php
        }
    }
?>
    <h2>Tag Exceptions</h2>
    <div class="tagtable center">
        <div>
            <form method="post">
                <input type="hidden" name="action" value="tags_exceptions_alter" />
                <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                <input type="hidden" name="doit" value="1" />
                <table class="tagtable shadow">
                    <tr class="colhead">
                        <td style="font-weight: bold" style="text-align: center">Remove</td>
                        <td style="font-weight: bold">Tag</td>
                        <td style="font-weight: bold">Type</td>
                        <td style="font-weight: bold">Uses</td>
                        <td>&nbsp;&nbsp;&nbsp;</td>
                        <td style="font-weight: bold" style="text-align: center">Remove</td>
                        <td style="font-weight: bold">Tag</td>
                        <td style="font-weight: bold">Type</td>
                        <td style="font-weight: bold">Uses</td>
                        <td>&nbsp;&nbsp;&nbsp;</td>
                        <td style="font-weight: bold" style="text-align: center">Remove</td>
                        <td style="font-weight: bold">Tag</td>
                        <td style="font-weight: bold">Type</td>
                        <td style="font-weight: bold">Uses</td>
                    </tr>
                    <?php
                    $i = 0;
                    $DB->query("SELECT te.ID, te.Name, te.ExceptionType, tags.Uses FROM tags_exceptions AS te LEFT JOIN tags ON te.Name=tags.Name ORDER BY tags.Name ASC");
                    $TagCount = $DB->record_count();
                    $Tags = $DB->to_array();
                    for ($i = 0; $i < $TagCount / 3; $i++) {
                        list($TagID1, $TagName1, $TagType1, $TagUses1) = $Tags[$i];
                        list($TagID2, $TagName2, $TagType2, $TagUses2) = $Tags[ceil($TagCount / 3) + $i];
                        list($TagID3, $TagName3, $TagType3, $TagUses3) = $Tags[2 * ceil($TagCount / 3) + $i];
                        ?>
                        <tr class="<?= (($i % 2) ? 'rowa' : 'rowb') ?>">
                            <td><input type="checkbox" name="oldtags[]" value="<?= $TagID1 ?>" /></td>
                            <td><a href="torrents.php?taglist=<?= $TagName1 ?>" ><?= $TagName1 ?></a></td>
                            <td><?= $TagType1 ?></td>
                            <td><?= $TagUses1 ?></td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>
    <?php  if ($TagID2) { ?>
                                    <input type="checkbox" name="oldtags[]" value="<?= $TagID2 ?>" />
                                <?php  } ?>
                            </td>
                            <td><a href="torrents.php?taglist=<?= $TagName2 ?>" ><?= $TagName2 ?></a></td>
                            <td><?= $TagType2 ?></td>
                            <td><?= $TagUses2 ?></td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>
    <?php  if ($TagID3) { ?>
                                    <input type="checkbox" name="oldtags[]" value="<?= $TagID3 ?>" />
                        <?php  } ?>
                            </td>
                            <td><a href="torrents.php?taglist=<?= $TagName3 ?>" ><?= $TagName3 ?></a></td>
                            <td><?= $TagType3 ?></td>
                            <td><?= $TagUses3 ?></td>
                        </tr>
    <?php
}
?>
                    <tr class="<?= (($i % 2) ? 'rowa' : 'rowb') ?>">
                        <td colspan="14"><label for="newtag">New tag exception: </label>
                            <input type="text" name="newtag" />
                            <select name="exceptiontype">
                                <option value='good' selected=selected>good</option>
                                <option value='bad'>bad</option>
                            </select>
                        </td>
                    </tr>
                    <tr style="border-top: thin solid #98AAB1">
                        <td colspan="11" style="text-align: center"><input type="submit" value="Submit Changes" /></td>
                    </tr>

                </table>
            </form>
        </div>
    </div>

</div>
<?php
show_footer();
