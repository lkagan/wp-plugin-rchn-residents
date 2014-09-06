<script>
jQuery(document).ready(function($) {
    $('#winner-button').click(function() {
        $.post('/wp-admin/admin-ajax.php', {action: 'winner'}, function(data) {
            $('#winner').html(data);
        });
    });
});
</script>
<link rel="stylesheet" href="<?= plugins_url() ?>/rchn-residents/style.css">
<div class="wrap rchn-admin">
    <h2 class="dashicons dashicons-groups">RCHN Registered Citizens</h2>
    <a style="float: right; display: inline-block;" href="/wp-content/plugins/rchn-residents/excel-export.php">Excel Export</a>
    <?= $resident_table->display(); ?>
    <small>Pick a random winner. First 6 citizens aren't included</small><br />
    <button id="winner-button">Winner Winner Chicken Dinner!</button>
    <div id="winner"></div>    
</div>