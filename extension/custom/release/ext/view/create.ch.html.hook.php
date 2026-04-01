<?php
if(!$app->user->admin):?>
<script>
setTimeout(function() {
    $('#date').val('');
}, 100)
$('#date').closest('tr').hide();
</script>
<?php endif;?>
<script>
document.getElementById('dataform').innerHTML += '<input type="hidden" name="status" value="draft">';
</script>
