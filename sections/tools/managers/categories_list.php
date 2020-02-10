<?php
if (!check_perms('admin_manage_categories')) {
    error(403);
}

$images = scandir(SERVER_ROOT . '/static/common/caticons', 0);
$images = array_diff($images, array('.', '..'));

show_header('Manage Categories');
?>

<script type="text/javascript">//<![CDATA[
    public function change_image(display_image, cat_image)
    {
        jQuery(display_image).html('<img src="/static/common/caticons/'+jQuery(cat_image).val()+'"/>');
    }
    //]]></script>

<div class="thin">
    <h2>Categories</h2>
    <p><strong>Observe!</strong> You must upload new images to the <?= SERVER_ROOT ?>/static/common/caticons/ folder before you can use it here.</p><br />

    <table>
        <tr class="head">
            <td colspan="7">Add a new category</td>
        </tr>
        <tr class="colhead">
            <td width="28%">Image</td>
            <td width="20%">Name</td>
            <td width="29%">Tag</td>
            <td width="7%">AutoFL</td>
            <td width="7%">AutoReap</td>
            <td width="7%">Min Upload Screenshots</td>
            <td width="22%">Submit</td>
        </tr>
        <tr>
        <form action="tools.php" method="post">
            <td>
                <input type="hidden" name="action" value="categories_alter" />
                <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                <span id="display_image0">
                    <img src="/static/common/caticons/<?= $images[2] ?>   " />
                </span>
                <span style="float:right"> <select id="cat_image0" name="image" onchange="change_image('#display_image0', '#cat_image0');">
                        <?php  foreach ($images as $key => $value) { ?>
                            <option value="<?= display_str($value) ?>"><?= $value ?></option>
                        <?php  } ?>
                    </select> </span>
            </td>
            <td>
                <input class="medium" type="text" name="name" />
            </td>
            <td>
                <input class="long"  type="text" name="tag" />
            </td>
            <td>
                <input type="checkbox" name="autofreeleech" />
            </td>
            <td>
                <input type="checkbox" name="autoreap" />
            </td>
            <td>
                <input type="text" class="medium" name="min_upload_screenshots" value='0' />
            </td>            
            <td>
                <input type="submit" value="Create" />
            </td>
        </form>
        </tr>
    </table>
    <br />
    <table>
        <tr class="colhead">
            <td width="28%">Image</td>
            <td width="20%">Name</td>
            <td width="29%">Tag</td>
            <td width="7%">AutoFL</td>
            <td width="7%">AutoReap</td>
            <td width="7%">Min Upload Screenshots</td>            
            <td width="22%">Submit</td>
        </tr>
        <?php
        $DB->query("SELECT
        id,
        name,
        image,
        tag,
	     autofreeleech,
	     autoreap,
	     min_upload_screenshots
        FROM categories");

        while (list($id, $name, $image, $tag, $autofreeleech, $autoreap, $minUploadScreenshots) = $DB->next_record()) {
            ?>
            <tr>
            <form action="tools.php" method="post">
                <td>
                    <input type="hidden" name="action" value="categories_alter" />
                    <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                    <input type="hidden" name="id" value="<?= $id ?>" />
                    <span id="display_image<?= $id ?>">
                        <img src="/static/common/caticons/<?= $image ?>" />
                    </span>
                    <span style="float:right">
                        <select id="cat_image<?= $id ?>" name="image" onchange="change_image('#display_image<?= $id ?>', '#cat_image<?= $id ?>');">
                            <?php  foreach ($images as $key => $value) { ?>
                                <option value="<?= display_str($value) ?>"<?= ($image == $value) ? 'selected="selected"' : ''; ?>><?= $value ?></option>
    <?php  } ?>
                        </select>
                    </span>
                </td>
                <td>
                    <input type="text" class="medium"  name="name" value="<?= display_str($name) ?>" />
                </td>
                <td>
                    <input type="text" class="long"  name="tag" value="<?= display_str($tag) ?>" />
                </td>
                <td>
                    <input type="checkbox" name="autofreeleech" <?=selected('autofreeleech', 1, 'checked', $autofreeleech) ?>/>
                </td>
                <td>
                    <input type="checkbox" name="autoreap" <?=selected('autoreap', 1, 'checked', $autoreap) ?>/>
                </td>
                <td>
                    <input type="text" class="medium" name="min_upload_screenshots" value='<?=$minUploadScreenshots?>' />
                </td>                
                <td>
                    <input type="submit" name="submit" value="Edit" />
                    <input type="submit" name="submit" value="Delete" />
                </td>
            </form>
            </tr>
<?php  } ?>
    </table>
</div>

<?php
show_footer();
