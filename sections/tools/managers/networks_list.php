<?php
if (!check_perms('admin_manage_networks')) {
    error(403);
}

$images = scandir(SERVER_ROOT . '/static/common/shows/networks', 0);
$images = array_diff($images, array('.', '..'));

include(SERVER_ROOT.'/sections/shows/functions.php');

show_header('Manage Networks');
?>

<script type="text/javascript">//<![CDATA[
    public function change_image(display_image, cat_image)
    {
        jQuery(display_image).html('<img src="/static/common/shows/networks/'+jQuery(cat_image).val()+'"/>');
    }
    //]]></script>

<link href="<?=STATIC_SERVER?>styles/sections/tvschedule.css" rel="stylesheet" type="text/css" />

<div class="thin">
    <h2>Networks</h2>
    <p><strong>Observe!</strong> You must upload new images to the <?= SERVER_ROOT ?>/static/common/shows/networks/ folder before you can use it here.</p>
    List of all logos:
    <table>
      <tr><td>#</td><td>Logo</td><td>Specs</td><td>Filename</td></tr>
      <?php  $i=0; foreach ($images as $key => $value) { ;
       $filename =   SERVER_ROOT . "/static/common/shows/networks/";
       $filename .=   display_str($value);
       list($width, $height) = getimagesize($filename);
       $imgsize = filesize(htmlspecialchars_decode($filename));
       $i++;   
       ?> 
      <tr>
       <td><?= $i ?></td>
       <td><img width="64px" class="tvschedulenetworklogo" src="/static/common/shows/networks/<?= display_str($value) ?>"></td>
       <td><?php 
       echo "width: " . $width . "<br />";
       echo "height: " .  $height. "<br />";
       echo "size: " .  round($imgsize/1024) ." kb";
       ?></td>
       <td> <?= $value ?></td>
      </tr>    
      <?php } ?>
    </table>                        
    <hr><br>
    Current Shows module networks list, represents  <span style='color:red;'>missing</span> logos needed to be added:<br><br>
    <?php
     $BigMaze = get_shows($force_reload);
     $Networks = get_networks($BigMaze);
     $i = 0;
    ?>    
    <table>
    <tr><td>#</td><td>Logo</td><td>Network</td></tr>
     <?php    
    // Shows current network list
    foreach($Networks as $key => $Show){
          echo "<tr>";
          $i++;
          $Show = str_ireplace(' ', '+', $Show);
          $Network = "/static/common/shows/networks/".strtolower($Show).".png";
          if(file_exists(SERVER_ROOT . $Network)) {
            echo "<td>".$i."</td>"."<td><img width='64px' class='tvschedulenetworklogo' src='".$Network."'>"."</td>"."<td>".$Show."</td>";          	
          	}
          else {
            echo "<td style='color:red;'>".$i."</td>"."<td style='color:red;'>Missing!"."</td>"."<td style='color:red;'>".$Show."</td>";            
            }
          echo "</tr>";
     }   
    ?>
    </table>
</div>
<?php
show_footer();
