<?php
show_header('Disabled');

?>
<p class="warning">
    Your account has been disabled. This is either due to inactivity or rule violation.<br />
</p>

<p class="strong">To discuss this come to our IRC at: <?=BOT_SERVER?> and join <?=BOT_DISABLED_CHAN?><br />
    Be truthful - at this point, lying will get you nowhere.<br />
</p>

<p class="strong">
    You may also use the WebIRC interface provided below.<br /><br />
    Please use your <?=SITE_NAME?> username.<br />
</p>


<?php

if ((empty($_POST['submit']) || empty($_POST['username'])) && !isset($Username)) {
    ?>
    <form action="" method="post">
          <input type="text" name="username" width="20" />
          <input type="submit" name="submit" value="Join WebIRC" />
    </form>
    <?php
} else {
    if (isset($Username)) {
        $nick = $Username;
    } else {
        $nick = $_POST['username'];
    }
    $nick = preg_replace('/[^a-zA-Z0-9\[\]\\`\^\{\}\|_]/', '', $nick);
    if (strlen($nick) == 0) {
        $nick = "NBLGuest?";
    }

    $nick = "disabled_$nick";

    ?>
    <div class="thin">
    <h3 id="general">Disabled IRC</h3>
    <div class="">
    <div class="box pad center">
	<iframe src="https://kiwiirc.com/client/irc.nebulance.cc/?nick=nebula|?&theme=cli#nbl-disabled" style="border:0; width:98%; height:450px;"></iframe>
    </div>
    </div>
    </div>
    <?php
}
show_footer();
