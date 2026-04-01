<?php if($app->tab == 'chteam'):?>
<script>
var newLink = '<?php echo $this->session->teamBugList;?>';
$('#legendBasic tbody tr:first').before('<tr id="projectBox"><th class="w-90px"><?php echo $lang->task->project;?></th><td><?php echo zget($projects, $task->project, $task->project);?></td></tr>');
$('.icon-back').parent().attr('href', newLink);

var storyLink = '<?php echo $this->createLink('story', 'create', "product={$bug->product}&branch={$bug->branch}&module=0&story=0&execution={$bug->execution}&bugID={$bug->id}&planID=0&todoID=0&extra=&storyType=story&chprojectID=$chprojectID");?>';
$('#tostory').attr('href', storyLink);

var caseLink = '<?php echo $this->createLink('testcase', 'create', "productID={$bug->product}&branch={$bug->branch}&moduleID=0&from=bug&bugID={$bug->id}&storyID=0&extras=&chprojectID=$chprojectID");?>';
$('.icon-testcase-create').parent().attr('href', caseLink);
$('.icon-check').parent().addClass('hidden');
$('#mainMenu > div.btn-toolbar.pull-right').hide();
</script>
<?php endif;?>
