<?php
/* AJAX Previews, simple stuff. */

$Text = new TEXT;
$Validate = new VALIDATE;

/* -------  Retrieve Min Upload Screenshots  ------- */
$DB->query("SELECT min_upload_screenshots FROM categories WHERE id = $_POST[category]");
list($minUploadScreenshots) = $DB->next_record();

//******************************************************************************//
//--------------- Validate data in upload form ---------------------------------//
//** note: if the same field is set to be validated more than once then each time it is set it overwrites the previous test
//** ie.. one test per field max, last one set for a specific field is what is used
//$Validate->SetFields('title', '1', 'string', 'Title must be between 2 and 200 characters.', array('maxlength' => 200, 'minlength' => 2));
//$Validate->SetFields('tags', '1', 'string', 'You must enter at least one tag. Maximum length is 10000 characters.', array('maxlength' => 10000, 'minlength' => 2));
$whitelist_regex = get_whitelist_regex();
$Validate->SetFields('image', '0', 'image', 'The image URL you entered was not valid.', array('regex' => $whitelist_regex, 'maxlength' => 255, 'minlength' => 12));
$Validate->SetFields('screens', '1', 'screens', 'Screens', array('regex' => $whitelist_regex, 'minimages'=>2, 'maxlength' => 512, 'minlength' => 12));
//$Validate->SetFields('desc', '1', 'desc', 'Description', array('regex' => $whitelist_regex, 'minimages' => $minUploadScreenshots, 'maxlength' => 1000000, 'minlength' => 20));
//$Validate->SetFields('category', '1', 'inarray', 'Please select a category.', array('inarray' => array_keys($NewCategories)));
//$Validate->SetFields('rules', '1', 'require', 'Your torrent must abide by the rules.');

$Err = $Validate->ValidateForm($_POST, $Text); // Validate the form

$Response = array();

$Response[0] = $Err;

$Content = $_REQUEST['mediaclean']; // Don't use URL decode.
$Content .= $_REQUEST['screensclean']; 
$Content .= $_REQUEST['trailerclean']; 
$Content .= "[br][br]$_REQUEST[templatefooter]";
$Imageurl = $_REQUEST['image']; // Don't use URL decode.

if (!empty($Imageurl)) {
    if ($Text->valid_url($Imageurl)) {
        $Imageurl = '<img style="max-width: 100%;" src="' . $Imageurl . '" onclick="lightbox.init(this,220);" />';
        //$Imageurl = $Text->full_format('[align=center][img]'.$Imageurl.'[/img][/align]',false,true);
    } else {
        $Imageurl = "<div style=\"text-align: center;\"><strong class=\"important_text\">Not a valid url</strong></div>";
    }
} else {
    $Imageurl = "<div style=\"text-align: center;\"><strong class=\"important_text\">No Banner Image</strong></div>";
}

$Response[1] = '<br/>
          <div class="cover_image">
                <div class="head">Banner Image</div>
                <div class="box box_albumart">' . $Imageurl . '</div>
          </div>
          <div class="head">Description</div>
          <div class="box pad">
          <div class="body">
               ' . $Text->full_format($Content, get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions']), true) . '
          </div>
          </div><br/>';

echo json_encode($Response);
