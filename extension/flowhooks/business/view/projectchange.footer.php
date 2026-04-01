<script>
$(function()
{
    $('#reasonType').parent().css({'opacity': '0.6', 'pointer-events': 'none'});
    $('#createdDept').parent().css({'opacity': '0.6', 'pointer-events': 'none'});

    var businessTotal = 0;
    parent.$('input[id*="childrensub_projectbusinessdevelopmentBudget"]').each(function() 
    {
        var budget = parseFloat($(this).val());
        if(!isNaN(budget)) businessTotal += budget;
    });

    var otherBusinessBudget = businessTotal - $('#developmentBudget').val(); 
    var hiddenBusiness = $('<input>').attr(
    {
        type:  'hidden',
        id:    'businessTotal',
        name:  'businessTotal',
        value: otherBusinessBudget
    });

    $('#operateForm table').append(hiddenBusiness);
});

$('#submit').text('<?php echo $lang->flow->draft?>');

$(document).ready(function()
{
    var deptID      = $('#dept').val();
    var createdDept = $('#createdDept').val();

    setTimeout(function()
    {
        $('select[id*=approval_reviewer]').each(function()
        {
            var myPicker = $(this).data('zui.picker');
            myPicker.destroy();

            var field = this.id;
            var link = createLink('user', 'ajaxGetUserByBusinessDept', 'createDeptID=' + createdDept + '&deptID=' + deptID + '&field=' + field);
            $.get(link, function(data)
            {
                $('#' + field).replaceWith(data);
                $('#' + field).picker();
            });
        });
    }, 300);
});

$('input[name="developmentBudget"]').on('input', function()
{
    var value = $(this).val().replace(/[^0-9.]/g, '');
    var parts = value.split('.');

    if (parts.length > 2)
    {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    if (parts[1] && parts[1].length > 1) {
        value = `${parts[0]}.${parts[1].slice(0, 1)}`;
    }

    $(this).val(value)
})

$('#dept').change(function()
{
    var deptID      = $('#dept').val();
    var createdDept = $('#createdDept').val();
    $('select[id*=approval_reviewer]').each(function()
    {
        var myPicker = $(this).data('zui.picker');
        myPicker.destroy();

        var field = this.id;
        var link = createLink('user', 'ajaxGetUserByBusinessDept', 'createDeptID=' + createdDept + '&deptID=' + deptID + '&field=' + field);
        $.get(link, function(data)
        {
            $('#' + field).replaceWith(data);
            $('#' + field).picker();
        });
    });
});

$(document).ajaxSuccess(function(event, responseData, settings) 
{
    if(settings.type === 'POST') 
    {
        var response = JSON.parse(responseData.responseText);

        if(response.result == 'success' && response.type == 'projectchange')
        {
            var businessID    = parent.$('input[id*="childrenbusinessID"][value="' + response.business + '"]').attr('id');
            var businessIndex = businessID.replace('childrenbusinessID', '');

            parent.$('#childrensub_projectbusinessdevelopmentBudget' + businessIndex).val(response.budget);
        }
    }
});
</script>
<style>
#triggerModal .modal-dialog {top: 50px !important;}
#triggerModal .modal-body  {max-height: 800px !important;}
</style>