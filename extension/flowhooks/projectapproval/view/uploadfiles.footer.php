<?php $html = $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false')); ?>
<script>
$(".table.table-form").css('table-layout', 'auto');
$(document).ready(function() {
    setTimeout(function() {
        $('#files').prepend(<?php echo json_encode($html);?>);
    }, 500);
});
</script>