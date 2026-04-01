<?php $html = $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false')); ?>
<script>
$('#submit').text('<?php echo $lang->flow->submit?>');
$(document).ready(function() {
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
