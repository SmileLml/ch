$(function()
{
    var table = $('#taskList');
    table.css('height', table.height() - 10 + 'px');

    $('.group-expand-all').click(function()
    {
        $('#taskList').find('tbody tr').removeClass('hidden');
        $('#taskList').find('tbody tr.group-summary').addClass('hidden');
        $('.group-collapse-all').show();
        $(this).hide();
    });

    $('.group-collapse-all').click(function()
    {
        $('#taskList').find('tbody tr').addClass('hidden');
        $('#taskList').find('tbody tr.group-summary').removeClass('hidden');
        $('.group-expand-all').show();
        $(this).hide();
    });

    $('td.group-toggle').click(function()
    {
        var dataid = $(this).closest('tr').attr('data-id');
        var $tbody = $(this).closest('tbody');
        $tbody.find('tr[data-id=' + dataid + ']').addClass('hidden');
        $tbody.find('tr.group-summary[data-id=' + dataid + ']').removeClass('hidden');

        $('.group-collapse-all').show();
        $('.group-expand-all').hide();
        if($tbody.find('tr:not(.group-summary):not(.hidden)').length == 0)
        {
            $('.group-collapse-all').hide();
            $('.group-expand-all').show();
        }
    });

    $('tr.group-summary.group-toggle').click(function()
    {
        dataid = $(this).attr('data-id');
        $(this).closest('tbody').find('tr[data-id=' + dataid + ']').removeClass('hidden');
        $(this).addClass('hidden');

        $('.group-collapse-all').show();
        $('.group-expand-all').hide();
    });
});
