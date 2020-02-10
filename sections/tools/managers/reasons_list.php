<?php
if (!check_perms('admin_manage_reasons')) {
    error(403);
}

show_header('Manage Reasons for Delete Tool');
?>

<div class="thin">
    <h2>Reasons for Delete Tool</h2>

    <table>
        <tr class="head">
            <td colspan="5">Add a new reason</td>
        </tr>
        <tr class="colhead">
            <td></td>
            <td width="5%">Sort</td>
            <td width="40%">Name</td>
            <td width="40%">Description</td>
            <td width="15%">Submit</td>
        </tr>
        <tr>
        <form action="tools.php" method="post">
            <td>
                <input type="hidden" name="action" value="reasons_alter" />
                <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
            </td>
            <td>
                <input class="medium" type="text" name="sort" />
            </td>            
            <td>
                <input class="long" type="text" name="name" />
            </td>
            <td>
                <input class="long"  type="text" name="description" />
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
            <td></td>
            <td width="5%">Sort</td>
            <td width="40%">Name</td>
            <td width="40%">Description</td>
            <td width="15%">Submit</td>
        </tr>
        <?php
        $DB->query("SELECT * FROM review_reasons ORDER by Sort");

        while (list($id, $sort, $name, $description) = $DB->next_record()) {
            ?>
            <tr>
            <form action="tools.php" method="post">
                <td>
                    <input type="hidden" name="action" value="reasons_alter" />
                    <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                    <input type="hidden" name="id" value="<?= $id ?>" />
                </td>
                <td>
                    <input type="text" class="medium"  name="sort" value="<?= display_str($sort) ?>" />
                </td>
                <td>
                    <input type="text" class="long"  name="name" value="<?= display_str($name) ?>" />
                </td>
                <td>
                    <input type="text" class="long"  name="description" value="<?= display_str($description) ?>" />
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
