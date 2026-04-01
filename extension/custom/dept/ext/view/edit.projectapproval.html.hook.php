<?php
$html = '';
$html .= '<tr><';
$html .= '<th>' . $lang->dept->leaders . '</th>';
$html .= '<td>';
$html .= html::select('leaders[]', $leadersUsers, $dept->leaders, "class='form-control chosen' multiple");
$html .= '</td>';
$html .= '/tr>';
?>

<script>
var html = <?php echo json_encode($html);?>;
$('#manager').closest('tr').after(html);

var $accounts = $('#leaders');
var pickerremote = $accounts.attr('data-pickerremote');
$accounts.addClass('picker-select').picker({chosenMode: true, remote: pickerremote});

$('#parent').parent().css({'opacity': '0.9', 'pointer-events': 'none'});
$('#parent_chosen > a').css({'background-color': '#f5f5f5'});
$('#name').prop('readonly', true);
</script>
