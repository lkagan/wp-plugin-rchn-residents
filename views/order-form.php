<script>
    jQuery(document).ready(function($) {
        $('.tipable').tooltip();
    });
</script>
<div class="resident-form rchn">
    <form  method="post" id="resident-form" action="<?= $this->url ?>">
    <input type="hidden" name="rchn_resident_action" value="submit">
        <fieldset>
            <legend>Please enter your information below</legend>
            <? if(is_user_logged_in()): ?>
                <small>all fields are required</small>
            <? else: ?>
                <small>bold fields are required</small>
            <? endif; ?>
            <div class="error">
            <? if(!empty($this->errors)): ?>
                <? foreach($this->errors as $error): ?>
                    <?= $error ?><br>
                <? endforeach; ?>
            <? endif; ?>
            </div>
            <label for="firstname" class="required">First name:</label><input type="text" name="firstname" required value="<?= htmlentities($resident->firstname, ENT_QUOTES); ?>" /><br />
            <label for="lastname" class="required">Last name:</label><input type="text" name="lastname" required  value="<?= htmlentities($resident->lastname, ENT_QUOTES); ?>" /><br />
            <label for="email" class="required">Email address:</label><input type="email" name="email" required  value="<?= htmlentities($resident->email, ENT_QUOTES); ?>" /><br />
            <? if(is_user_logged_in()): ?>
                <input type="hidden" name="username" value="<?= htmlentities($username, ENT_QUOTES); ?>">
            <? else: ?>
                <label for="username">RCHN Username <a  class="tipable help-icon" title="If you registered for the forums or any other portion of this site, please enter your username.">[?]</a>:</label><input type="text" name="username"  value="<?= htmlentities($resident->username); ?>" /><br />
            <? endif; ?>
            <input type="submit" value="Submit">
        </fieldset>
    </form>
</div>