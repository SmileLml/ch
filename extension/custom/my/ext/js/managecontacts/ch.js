$(function()
{
    /* Regenerate the user selection to enable remote search when necessary. */
    $contractUserSelect = $('#users');
    if($contractUserSelect)
    {
        if($contractUserSelect.attr('data-pickerremote') !== undefined)
        {
            $contractUserSelect.data('zui.picker').destroy();
            var pickerRemote = $contractUserSelect.attr('data-pickerremote');
            $contractUserSelect.picker({remote: pickerRemote});
        }
    }
});
