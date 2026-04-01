<?php $html = $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false')); ?>
<script>
$('#submit').text('<?php echo $lang->flow->submit?>');
$(document).ready(function()
{
    setTimeout(function() {
        $('#files').prepend(<?php echo json_encode($html);?>);
        $('.files-list').find('a[title="删除"]').each(function(index, element){
            var str = $(element).attr('onclick');
            var regex = /\((.*?)\)/;
            var matches = str.match(regex);
            if (matches) {
                var params = matches[1].split(',').map(function(item) {
                    return item.trim(); // 去掉多余的空格
                });

                var str = $(element).attr('onclick', '');
                $(element).off('click').on('click', function() {
                    var isConfirmed = confirm("<?php echo $lang->file->confirmDelete?>");

                    if (isConfirmed) {
                        $(this).closest('li').next('li').remove()
                        $(this).closest('li').remove();
                        $.get(createLink('file', 'delete', 'fileID=' + params[0] + '&confirm=yes'))
                    }
                });
            }

        })
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
    $('[data-businessStatus="closed"]').each(function()
    {
        $(this).prev('tr').prev('tr').find('td').css({'opacity': '0.8', 'pointer-events': 'none'});
        $(this).prev('tr').prev('tr').find('td').find('input').css({'opacity': '0.8', 'background-color': '#f5f5f5'});
        $(this).find('[name*="children[sub_projectbusiness][PRDdate]"]').css({'opacity': '0.8', 'pointer-events': 'none', 'background-color': '#f5f5f5'});
        $(this).find('[name*="children[sub_projectbusiness][goLiveDate]"]').css({'opacity': '0.8', 'pointer-events': 'none', 'background-color': '#f5f5f5'});
        $(this).find('[name*="children[sub_projectbusiness][acceptanceDate]"]').css({'opacity': '0.8', 'pointer-events': 'none', 'background-color': '#f5f5f5'});
    })
    $('[data-businessStatus="cancelled"]').each(function()
    {
        $(this).prev('tr').prev('tr').find('td').css({'opacity': '0.8', 'pointer-events': 'none'});
        $(this).prev('tr').prev('tr').find('td').find('input').css({'opacity': '0.8', 'background-color': '#f5f5f5'});
        $(this).find('[name*="children[sub_projectbusiness][PRDdate]"]').css({'opacity': '0.8', 'pointer-events': 'none', 'background-color': '#f5f5f5'});
        $(this).find('[name*="children[sub_projectbusiness][goLiveDate]"]').css({'opacity': '0.8', 'pointer-events': 'none', 'background-color': '#f5f5f5'});
        $(this).find('[name*="children[sub_projectbusiness][acceptanceDate]"]').css({'opacity': '0.8', 'pointer-events': 'none', 'background-color': '#f5f5f5'});
    })
    $('[data-businessStatus]').each(function()
    {
        $(this).prev('tr').prev('tr').find('td').css({'opacity': '0.8', 'pointer-events': 'none'});
        $(this).prev('tr').prev('tr').find('td').find('div[class*="picker-selections"]').css({'background-color': '#f5f5f5'});
    })
});

$('#projectApprovalDate').val('<?php echo $data->projectApprovalDate?>').css({'opacity': '0.8', 'pointer-events': 'none'});
</script>
