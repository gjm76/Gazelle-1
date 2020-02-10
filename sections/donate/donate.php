<?php
enforce_login();

$eur_rate = get_current_btc_rate();

show_header('Donate','bitcoin');
?>
<!-- Donate -->
<div class="thin">
    <h2>Donate</h2>

    <div class="head">Thank you for visiting the donate page.</div>
    <div class="box pad">
        <?php
        $Body = get_article('donateinline');
        if ($Body) {
            $Text = new TEXT;
            echo $Text->full_format($Body , get_permissions_advtags($LoggedUser['ID']));
        }
        ?>
        <br/>
        <p style="font-size: 1.1em" title="rate is Mt.Gox weighted average: <?=$eur_rate?>">The current bitcoin exchange rate is 1 bitcoin = &euro;<?=number_format($eur_rate,2);?></p>

        <div style="text-align: center">
            <a style="font-weight: bold;font-size: 1.4em;" href="donate.php?action=my_donations&new=1"><span style="color:red;"> >> </span>Click here to get a personal donation address<span style="color:red;"> << </span></a>
        </div>
    </div>

    <div class="head">Donate for <img src="<?= STATIC_SERVER ?>common/symbols/donor.svg" alt="love" /></div>
    <div class="box pad">
        <p><span style="font-size:1.1em;font-weight: bold;">What you will receive for donating:</span> </p>
        <ul>
            <?php if ($LoggedUser['Donor']) { ?>
                <li>Even more love! (You will not get multiple hearts.)</li>
                <li>A warmer fuzzier feeling than before!</li>
            <?php } else { ?>
                <li>Our eternal love, as represented by the <img src="<?= STATIC_SERVER ?>common/symbols/donor.svg" alt="Donor" /> you get next to your name.</li>
				<li>A Donor Badge.
                <li>A warm fuzzy feeling.</li>
            <?php } ?>
					<br/>
					<p> <img src="<?= STATIC_SERVER ?>common/badges/donate10.png" alt="Donor" />&nbsp;&nbsp;&nbsp;&nbsp;You will receive this badge for a minimum <span style="font-size: 1.0em;font-weight: bolder">10.00&nbsp;&euro;</span> Tier 5 donation (<?=number_format(10.0/$eur_rate,3)?> bitcoins)  </p>
					<p> <img src="<?= STATIC_SERVER ?>common/badges/donate15.png" alt="Donor" />&nbsp;&nbsp;&nbsp;&nbsp;You will receive this badge for a minimum <span style="font-size: 1.0em;font-weight: bolder">15.00&nbsp;&euro;</span> Tier 4 donation (<?=number_format(15.0/$eur_rate,3)?> bitcoins)  </p>
	            <p> <img src="<?= STATIC_SERVER ?>common/badges/donate20.png" alt="Donor" />&nbsp;&nbsp;&nbsp;&nbsp;You will receive this badge for a minimum <span style="font-size: 1.0em;font-weight: bolder">20.00&nbsp;&euro;</span> Tier 3 donation (<?=number_format(20.0/$eur_rate,3)?> bitcoins)  </p>
					<p> <img src="<?= STATIC_SERVER ?>common/badges/donate25.png" alt="Donor" />&nbsp;&nbsp;&nbsp;&nbsp;You will receive this badge for a minimum <span style="font-size: 1.0em;font-weight: bolder">25.00&nbsp;&euro;</span> Tier 2 donation (<?=number_format(25.0/$eur_rate,3)?> bitcoins)  </p>
					<p> <img src="<?= STATIC_SERVER ?>common/badges/donate50.png" alt="Donor" />&nbsp;&nbsp;&nbsp;&nbsp;You will receive this badge for a minimum <span style="font-size: 1.0em;font-weight: bolder">50.00&nbsp;&euro;</span> Tier 1 donation (<?=number_format(50.0/$eur_rate,3)?> bitcoins)  </p>
					<br/>
        </ul>
    </div>

    <div class="head">Donate for <strong>GB</strong></div>
    <div class="box pad">
        <p><span style="font-size:1.1em;font-weight: bold;">What you will receive for your donation:</span></p>
        <ul>
            <?php

            foreach ($DonateLevels as $level=>$rate) {
                ?>
                    <li>If you donate &euro;<?=$level?> you will get <?=number_format($level * $rate)?> GB added to your <u>upload</u>   <strong>(rate: <?=$rate?>gb per &euro;) &nbsp; ( <?=number_format($level/$eur_rate,6)?> bitcoins)</strong></li>

                <?php
            }

            ?><br/>
            <li><span style="font-size: 1.2em;">If you want to donate for GB
                    <a style="font-weight: bold;" href="donate.php?action=my_donations&new=1"><span style="color:red;"> >> </span>Click here to get a personal donation address<span style="color:red;"> << </span></a></span></li>
        </ul>

    </div>

    <div class="head">What you will <strong>not</strong> receive</div>
    <div class="box pad">
        <ul>
            <li>Immunity from the rules.</li>
        </ul>
        <p>Please be aware that by making a donation you are not purchasing donor status or invites. You are helping us pay the bills and cover the costs of running the site. We are doing our best to give our love back to donors but sometimes it might take more than 48 hours. Feel free to contact us by sending us a <a href="staffpm.php?action=user_inbox">Staff Message</a> regarding any matter. We will answer as quickly as possible.</p>
    </div>
</div>
<!-- END Donate -->
<?php
show_footer();
