<?php
show_header('Recover Password','validate');
if (empty($_POST['submit']) || empty($_POST['username'])) {

        echo $Validate->GenerateJS('recoverform');
        ?>
        <form name="recoverform" id="recoverform" method="post" action="" onsubmit="return formVal();">
              <div>
                    <font class="titletext">Reset your password - Step 1</font><br /><br />
        <?php
        if (empty($Sent) || (!empty($Sent) && $Sent!=1)) {
              if (!empty($Err)) {
        ?>
                    <font color="red"><strong><?=$Err ?></strong></font><br /><br />
        <?php 	} ?>
              An email will be sent to your email address with information on how to reset your password<br />
              (check your spam folder if you haven't received it within a few minutes)<br /><br />
              <label for="email">Email&nbsp;</label>
              <br />
              <span>
                  <input type="text" name="email" id="email" class="inputtext" />
                  <input type="submit" name="reset" value="Reset!" class="submit" />
              </span>

        <?php  } else { ?>
              An email has been sent to you, please follow the directions in that email to reset your password.<br />
              (check your spam folder if you haven't received it within a few minutes)<br />
        <?php  } ?>
              </div>
        </form>
        <br/><br/><br/>
        <p class="strong">
            If you need help you can come to our IRC at: <?=BOT_SERVER?><br />
            And join <?=BOT_DISABLED_CHAN?><br /><br />
            If you do not have access to an IRC client you can use the WebIRC interface provided below.<br />
            Please use your <?=SITE_NAME?> username.
        </p>
        <br />
        <form action="" method="post">
              <input type="hidden" name="act" value="recover" />
              <input type="text" name="username" width="20" />
              <input type="submit" name="submit" value="Join WebIRC" />
        </form>
<?php
} else {

        $nick = $_POST['username'];
        $nick = preg_replace('/[^a-zA-Z0-9\[\]\\`\^\{\}\|_]/', '', $nick);
        if (strlen($nick) == 0) {
        $nick = "TtNGuest?";
        }
        $nick = "nologin_$nick";

        ?>
    <div class="thin">
        <div class="thin">
              <h3 id="general">IRC Help</h3>
            <div class="">
                  <div class="head">IRC</div>
                  <div class="box pad center">
                            <iframe src="<?=HELP_URL?>nick=<?=$nick?><?=BOT_DISABLED_CHAN?>" width="98%" height="600"></iframe>
                  </div>
            </div>
        </div>
    </div>
<?php
}

show_footer();
