<table class="hidden" id='executionBox'>
  <tbody>
    <tr class='required'>
      <th class='w-80px'><?php echo $lang->task->execution;?></th>
      <td class='w-300px'><?php echo html::select('execution', $executions, $executionID, "class='form-control chosen' onchange='switchExecution(this.value)'")?></td>
      <td></td>
    </tr>
  </tbody>
</table>
<script>
var fromChTeam = '<?php echo $app->tab == 'chteam' ? true : false;?>';
if(fromChTeam)
{
    $('#executionBox').removeClass('hidden');
    $('#batchCreateForm').prepend($('#executionBox'));
}
</script>
