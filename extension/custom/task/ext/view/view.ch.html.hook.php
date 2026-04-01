<?php if($from == 'chteam'):?>
<script>
var newLink = '<?php echo $this->session->teamTaskList;?>';
$('#legendBasic tbody tr:first').before('<tr id="projectBox"><th class="w-90px"><?php echo $lang->task->project;?></th><td><?php echo zget($projects, $task->project, $task->project);?></td></tr>');
$('.icon-back').parent().attr('href', newLink);
$('#mainMenu > div.btn-toolbar.pull-right').hide();
</script>
<?php endif;?>
