<?php js::set('businessID', $businessID);?>
<script>
if(businessID)
{
    var businessUrl = '<?php echo $this->createLink('project', 'business', 'projectID=' . $executionID);?>'
    $('.table-responsive table').find('tr').last().find('a').last().attr('href', businessUrl);
}

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

$('th:contains("<?php echo $this->lang->story->estimate;?>")').text('<?php echo $this->lang->story->estimateAB;?>')
$('.reviewBox').hide();
$('#saveButton').hide();
</script>
