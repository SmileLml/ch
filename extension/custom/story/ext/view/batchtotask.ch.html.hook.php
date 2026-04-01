<?php
$chprojectInputs = '';
$chprojectInputs .= html::hidden('execution', $executionID);
$chprojectInputs .= html::hidden('chproject', $chproject);
?>
<script>
$(document).ready(function()
{
    $('#batchToTaskForm').append(<?php echo json_encode($chprojectInputs);?>);
});
</script>
