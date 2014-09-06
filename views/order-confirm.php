<div class="rchn confirm">
    <fieldset>
        <legend>Please confirm your information below</legend>
        <label for="firstname" class="required">First name:</label> <?= htmlentities($resident->firstname); ?><br />
        <label for="lastname" class="required">Last name:</label> <?= htmlentities($resident->lastname); ?><br />
        <label for="email" class="required">Email address:</label> <?= htmlentities($resident->email); ?><br />
        <? if(!is_user_logged_in() && !empty($resident->username)): ?>
            <label for="username">RCHN Username: </label> <?= htmlentities($resident->username); ?><br />
        <? endif; ?>
        <div class="buttons">
            <a href="<?= $this->url ?>?rchn_resident_action=edit">[change information]</a>
            <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
                <input type="hidden" name="return" value="http://<?= $_SERVER['HTTP_HOST'] ?>/rchn-citizen-registration-thanks/">
                <input type="hidden" name="custom" value="<?= $resident->id ?>">
                <input type="hidden" name="notify_url" value="<?= $this->url ?>">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="Z7FDPNEZ6RA9W">
                <input type="image" src="https://www.sandbox.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                <img alt="" border="0" src="https://www.sandbox.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>
            <!--
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                <input type="hidden" name="return" value="http://<?= $_SERVER['HTTP_HOST'] ?>/rchn-citizen-registration-thanks/">
                <input type="hidden" name="custom" value="<?= $resident->id ?>">
                <input type="hidden" name="notify_url" value="<?= $this->url ?>">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="6KPU82V6LLGS2" />
                <input type="image" alt="PayPal - The safer, easier way to pay online!" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" />
                <img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" border="0" />
            </form>
            -->
        </div>
    </fieldset>
</div>