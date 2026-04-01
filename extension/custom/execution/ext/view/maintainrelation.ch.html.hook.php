<?php if($this->app->tab == 'chteam'):?>
<script>
var gobackText           = '<?php echo $lang->goback;?>';
var maintainRelationText = '<?php echo $lang->execution->gantt->editRelationOfTasks?>';
var taskText             = '<?php echo $lang->task->create?>';
var goback               = '<?php echo $this->createLink('chproject', 'gantt', "&chprojectID=$chprojectID");?>' + '#app=chteam';
var maintainRelation     = '<?php echo $this->createLink('execution', 'maintainRelation', "executionID=$executionID&chprojectID=$chprojectID");?>' + '#app=chteam';
var task                 = '<?php echo $this->createLink('task', 'create', "execution=$executionID&storyID=0&moduleID=0&taskID=0&todoID=0&extra=&bugID=0&chprojectID=$chprojectID&formrelation=1", '', true);?>' + '#app=chteam';
$('.pull-left').find('a:contains("' + gobackText + '")').attr('href', goback);
$('.pull-right').find('a:contains("' + maintainRelationText + '")').attr('href', maintainRelation);
$('.pull-right').find('a:contains("' + taskText + '")').attr('href', task);
</script>
<?php endif?>