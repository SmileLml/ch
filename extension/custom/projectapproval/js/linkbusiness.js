$(function()
{
    $('.picker-select').picker();
    $(document).on('change', 'select.businessList', function(event, param)
    {
        var id    = $(this).val();
        var _this = this;
        if(id === '')
        {
            $(this).closest('tr').find('td:eq(1) input').val('');
            $(this).closest('tr').find('td:eq(2) input').val('');
            $(this).closest('tr').find('td:eq(3) input').val('');
            return;
        }

        $.get(createLink('project', 'ajaxGetBusiness', "id=" + id), function(data)
        {
            $(_this).closest('tr').find('td:eq(1) input').val(data.business.developmentBudget)
            $(_this).closest('tr').find('td:eq(2) input').val(data.business.headBusinessUser)
            $(_this).closest('tr').find('td:eq(3) input').val(data.business.outsourcingBudget)
        }, 'json');
    })
})
function addNewLine(obj)
{
    var businessHtml = $('.businessTemp').html();
    var newLine      = $(obj).closest('tr').clone();
    newLine.find('a').eq(1).css('visibility', 'unset');
    newLine.find('.picker').remove();
    newLine.find('select').picker();
    newLine.find('input.form-control').val('');
    $(obj).closest('tr').after(newLine);
}

function removeLine(obj)
{
    $(obj).closest('tr').remove();
}
