<script>
$(document).ready(function()
{
    $('table').find('input[name^="estimate"]').on('input', function()
    {
        var value = $(this).val().replace(/[^0-9.]/g, '');
        var parts = value.split('.');

        if (parts.length > 2)
        {
            value = parts[0] + '.' + parts.slice(1).join('');
        }

        if (parts[1] && parts[1].length > 2)
        {
            value = `${parts[0]}.${parts[1].slice(0, 2)}`;
        }

        $(this).val(value)
    })
})
</script>