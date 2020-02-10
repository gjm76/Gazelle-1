<?php
/* AJAX Previews, simple stuff. */

$Text = new TEXT;

if (!empty($_POST['message'])) {
    echo $Text->full_format($_POST['message'], true, true);
}
