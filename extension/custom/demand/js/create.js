$(function()
{
    $('#group').change(function()
    {
        var group = $(this).val();
        var link  = createLink('opinion', 'ajaxGetOwner', 'group=' + group);

        $.post(link, function(data)
        {
            $('#owner').val(data);
            $('#owner').trigger('chosen:updated');
        })

    })

    $('#pri').on('change', function()
    {
        var $select = $(this);
        var $selector = $select.closest('.pri-selector');
        var value = $select.val();
        $selector.find('.pri-text').html('<span class="label-pri label-pri-' + value + '" title="' + value + '">' + value + '</span>');
    });
})
