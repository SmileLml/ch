<script>
$(function()
{
    $('#reasonType').parent().css({'opacity': '0.6', 'pointer-events': 'none'});
    $('#createdDept').parent().css({'opacity': '0.6', 'pointer-events': 'none'});
    $('#goLiveDate').closest('td').addClass('required');
    $('#acceptanceDate').closest('td').addClass('required');
    $('#PRDdate').closest('td').addClass('required');

    $('#project').change(function(){
        showMilestone($(this).val());
    })

    showMilestone($('#project').val());
});

$('#submit').text('<?php echo $lang->flow->submit?>');

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
</script>
