<?php $html = $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false')); ?>
<?php
js::set('itPlanInfoActualExpend', $itPlanInfoActualExpend);
js::set('itCostActualExpend', $itCostActualExpend);
?>
<script>
$('#submit').text('<?php echo $lang->flow->submit?>');
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

    $('[name*="costType"]').each(function()
    {
        if($(this).val() == 'itPlanInto' || $(this).val() == 'itCost')
        {
            $(this).closest('td').next('td').find('[name*="actualExpend"]').prop('readonly', true);
        }
    })
    $('#sub_projectcost').on('change', 'select[name*="costType"]', function(){
        if($(this).val() == 'itPlanInto' || $(this).val() == 'itCost')
        {
            var actualExpend = $(this).val() == 'itPlanInto' ? itPlanInfoActualExpend : itCostActualExpend;
            $(this).closest('td').next('td').find('[name*="actualExpend"]').val(actualExpend);
            $(this).closest('td').next('td').find('[name*="actualExpend"]').prop('readonly', true);
        }
        else
        {
            $(this).closest('td').next('td').find('[name*="actualExpend"]').val('');
            $(this).closest('td').next('td').find('[name*="actualExpend"]').prop('readonly', false);
        }
    })
    $('#sub_projectcost tbody tr').each(function(index, element){
        $(element).find('td:last a').remove();
        $(element).find('td:first>div').css({'opacity': '0.6', 'pointer-events': 'none'});
    })
    $('#sub_projectvalue tbody tr').each(function(index, element){
        $(element).find('td:last a').remove();
        // $(element).find('td:first>div').css({'opacity': '0.6', 'pointer-events': 'none'});
        // $(element).find('td:eq(1)>input').prop('readonly', true);
        // $(element).find('td:eq(1)>textarea').prop('readonly', true);
        // $(element).find('td:eq(1)>div').css({'opacity': '0.6', 'pointer-events': 'none'});
    })
});

</script>
