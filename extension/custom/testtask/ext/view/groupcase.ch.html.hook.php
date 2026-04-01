<?php if($this->app->tab == 'chteam'):?>
<?php js::set('chprojectID', $chprojectID);?>
<?php js::set('taskID', $taskID);?>
<script>
var allTabLink          = createLink('testtask', 'cases', "taskID=" + taskID +"&browseType=all&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project=" + chprojectID);
var backLink            = createLink('chproject', 'testtask', "project=" + chprojectID);
var assignedtomeTabLink = createLink('testtask', 'cases', "taskID=" + taskID +"&browseType=assignedtome&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project=" + chprojectID);

$('#allTab').attr('href', allTabLink);
$('#back').attr('href', backLink);
$('#assignedtomeTab').attr('href', assignedtomeTabLink);
</script>
<?php endif;?>
