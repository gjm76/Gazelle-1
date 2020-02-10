<?php
enforce_login();
show_header('Badges and Awards');

$DB->query("SELECT b.Title, b.Type, b.Description, b.Cost, b.Image,
                (CASE WHEN Type='Shop' THEN 2
                      WHEN ba.ID IS NOT NULL THEN 0
                      ELSE 1
                 END) AS Sorter
              FROM badges AS b
              LEFT JOIN badges_auto AS ba ON b.ID=ba.BadgeID
              WHERE ba.ID IS NULL OR ba.Active = 1
              ORDER BY Sorter, b.Sort"); // b.Type,

$Awards = $DB->to_array(false, MYSQLI_BOTH);

?>

<div class="thin">
    <h2>Badges and Awards</h2>
    <table>
<?php
    $Row = 'a';
      $LastType='';
    foreach ($Awards as $Award) {
        list($Name, $Type, $Desc, $Cost, $Image, $Sorter) = $Award;

            if ($LastType != $Sorter) {     // && $Type != 'Unique') {
?>
            <tr class="head pad">
                <td colspan="3">
                <?php
                switch ($Sorter) {
                    case 2:
                        echo "Badges available for purchase in the Black Market";
                        break;
                    case 0:
                        echo "Badges automatically awarded by the system";
                        break;
                    case 1:
                        echo "Badges awarded by the staff";
                        break;
                }
                ?>
                </td>
            </tr>
<?php
                $Row = 'a';
                $LastType=$Sorter;
            }

        $Row = ($Row == 'a') ? 'b' : 'a';
?>
        <tr class="row<?=$Row?>">
                <td><?=display_str($Name)?>
                </td>
                <td>
                    <img style="text-align:center" src="<?=STATIC_SERVER.'common/badges/'.$Image?>" title="<?=$Desc?>" alt="<?=$Name?>" />
                </td>
                <td>
<?php           if ($Type=='Shop') echo '<strong style="float:right;margin-top:2px;">Cost: '.number_format($Cost).'</strong>'; ?>
                    <?=$Desc?>
                </td>
        </tr>
<?php	}  ?>
        </table>
    <div class="clear"></div>
</div>

<?php
show_footer();
