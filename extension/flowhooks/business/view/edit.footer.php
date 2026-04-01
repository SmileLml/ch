<script>
$(function()
{
    $('#reasonType').parent().css({'opacity': '0.6', 'pointer-events': 'none'});
    $('#createdDept').parent().css({'opacity': '0.6', 'pointer-events': 'none'});
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
</script>
