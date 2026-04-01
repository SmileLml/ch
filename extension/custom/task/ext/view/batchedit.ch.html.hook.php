<script>
var chProject = '<?php echo $chProjectID;?>';
if(chProject != 0)
{
    $('.c-id').after('<th class="c-name"><?php echo $lang->task->project?></th>');

    var tasks = <?php echo json_encode($tasks);?>;
    $('table tbody tr').each(function()
    {
        var taskID      = $(this).find('td:first input').val();
        var projectName = tasks[taskID]['projectName'];

        $(this).find('td:first').after('<td title="' + projectName + '">' + projectName + '</td>');
    });
}
</script>
