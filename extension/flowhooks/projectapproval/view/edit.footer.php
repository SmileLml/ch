<?php $html = $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false')); ?>
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('#files').prepend(<?php echo json_encode($html);?>);
    }, 500);

    $('[name*="costUnit"]').each(function()
    {
        let costUnit  = $(this);
        costUnit.addClass('form-control');
        costUnit.attr('type', 'text');
        costUnit.prop('readonly', true);

        html = $(this).clone();
        html.addClass('form-control').attr('type', 'text').prop('readonly', true);
        $(this).closest('td').html(html);

        let costUnitKey = costUnit.attr('id').replace('childrensub_projectcostcostUnit', '');

        let costDesc = $('#childrensub_projectcostcostDesc' + costUnitKey);

        costDesc.addClass('form-control');
        costDesc.attr('type', 'text');
        costDesc.prop('readonly', true);

        costDescHtml = costDesc.clone();
        costDescHtml.addClass('form-control').attr('type', 'text').prop('readonly', true);
        costDesc.closest('td').html(costDescHtml);
    });
});
</script>
