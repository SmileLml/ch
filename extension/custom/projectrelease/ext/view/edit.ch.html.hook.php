<?php
if(!$app->user->admin):?>
<script>
$('#date').closest('tr').hide();
$('#status').closest('tr').hide();
</script>
<?php endif;?>
