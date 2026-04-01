$(function()
{
    var projectType = $('input[name="projectType"]:checked').val();
    if(projectType === "1")
    {
        $('#project').closest('tr').hide();
    }
    else
    {
        $('#projectApproval').closest('tr').hide();
    }

    $('input[name="projectType"]').change(function()
    {
        var selectedValue = $('input[name="projectType"]:checked').val();
        if(selectedValue === "1")
        {
            $('#projectApproval').closest('tr').show();
            $('#project').closest('tr').hide();
        }
        else
        {
            $('#projectApproval').closest('tr').hide();
            $('#project').closest('tr').show();
        }
    });
});

function addItem(obj)
{
    var $inputRow = $(obj).closest('.row-module');
    var $newRow = $('#insertItemBox').children('.row-module').clone().insertAfter($inputRow).addClass('highlight');
    $newRow.find("input[type!='hidden']").val('');
    setTimeout(function()
    {
        $newRow.removeClass('highlight');
    }, 1600);
}

function insertItem(obj)
{
    var $inputgroup = $(obj).closest('.row-table');
    var insertHtml  = $('#insertItemBox').children('.row-table').clone();
    $inputgroup.after(insertHtml).next('.row-table').find('input').val('');
}

function deleteItem(obj)
{
    var $inputRow = $(obj).closest('.row-module');
    if ($inputRow.siblings('.row-module.row-module-new').find('.btn-delete').length > 0)
    {
        $inputRow.addClass('highlight').fadeOut(500, function()
        {
            $inputRow.remove();
        });
    }
}
