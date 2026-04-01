<?php if($this->app->tab == 'chteam'):?>
<?php
$projectName = $this->loadModel('project')->getById($task->project)->name;
?>
<script>
var peojectElement = $(".infos:last").clone();
$(peojectElement).find('span>span:first').text('<?php echo $lang->task->project;?>')
$(peojectElement).find('span>span:last').text('<?php echo $projectName;?>')
peojectElement.insertAfter(".infos:last");
</script>
<?php endif?>