<script>
    jQuery(document).ready(function($) {
        $('.tipable').tooltip();
    });
</script>
<a name="rchn-citizen-form"></a>
<div class="resident-form rchn">
    <form  method="post" id="resident-form" action="<?= $this->url ?>#rchn-citizen-form">
    <input type="hidden" name="rchn_resident_action" value="submit">
        <fieldset>
            <legend>Please enter your information below</legend>
            <?php if(!empty($GLOBALS['user_ID'])): ?>
                <small>all fields are required</small>
            <?php else: ?>
                <small>bold fields are required</small>
            <?php endif; ?>
            <div class="error">
            <?php if(!empty($this->errors)): ?>
                <?php foreach($this->errors as $error): ?>
                    <?= $error ?><br>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
            <label for="firstname" class="required">First name:</label><input type="text" name="firstname" required value="<?= htmlentities($resident->firstname, ENT_QUOTES); ?>" /><br />
            <label for="lastname" class="required">Last name:</label><input type="text" name="lastname" required  value="<?= htmlentities($resident->lastname, ENT_QUOTES); ?>" /><br />
            <label for="email" class="required">Email address:</label><input type="email" name="email" required  value="<?= htmlentities($resident->email, ENT_QUOTES); ?>" /><br />
            <?php if(!empty($GLOBALS['user_ID'])): ?>
                <input type="hidden" name="username" value="<?= htmlentities($username, ENT_QUOTES); ?>">
            <?php else: ?>
                <label for="username">RCHN Username <a  class="tipable help-icon" title="If you registered for this site, please enter the username you chose.">[?]</a>:</label><input type="text" name="username"  value="<?= htmlentities($resident->username); ?>" /><br />
            <?php endif; ?>
            <input type="submit" value="Submit">
        </fieldset>
    </form>
</div>