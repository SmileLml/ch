<?php
$html  = '';
$html .= html::hidden('isDraft', '1');
$html .= html::submitButton($lang->flow->launch, "onClick='setDraftStatus()'", 'btn btn-primary btn-wide');

js::set('poolID', $poolID);
?>
<script>
$('#businessUnit').prev('div').removeClass('required');

var html = <?php echo json_encode($html);?>;
$('#submit').before(html);

$(document).ready(function()
{
    $('button:contains("保存")').click(function()
    {
        $('#isDraft').val('1');
    });
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

function setDraftStatus()
{
    $('#isDraft').val('0');

    setTimeout(function()
    {
        $('.close').off();
        $('.close').on('click', function()
        {
            var link = createLink('demand', 'browse', 'poolID=' + poolID);
            window.location = link;
        });
    }, 500);
}

function approvalSubmit(option)
{
    var link = createLink('business', 'approvalsubmit1', 'dataID=' + option.dataID, '', true);

    new $.zui.ModalTrigger(
    {
        type: 'iframe',
        url: link,
        width: 800
    }).show();
}
</script>
