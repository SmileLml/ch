<?php $html = $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false')); ?>
<script>
$('#submit').text('<?php echo $lang->flow->submit?>');
$(document).ready(function() {
    setTimeout(function() {
        $('#files').prepend(<?php echo json_encode($html);?>);
    }, 500);

    $('#sub_projectbusiness').on('click','.delItem',function ()
    {
        $("#sub_projectbusinessLabel").remove();
    })

    $(document).on('change','[id^="childrensub_projectbusinessbusiness"]',function ()
    {
        $("#sub_projectbusinessLabel").remove();
    })

    $(document).on('change', '#end', function ()
    {
        var regex            = /^childrensub_projectbusinessacceptanceDate\d+Label$/;
        var elementsToRemove = $('#sub_projectbusiness').find('*').filter(function()
        {
            return regex.test($(this).attr('id'));
        });
        elementsToRemove.remove();
    })

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
