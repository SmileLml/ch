<?php if($this->app->tab == 'chteam'):?>
<script>
var chprojectID         = <?php echo $chprojectID;?>;
var taskID              = <?php echo $taskID;?>;
var productID           = <?php echo $productID;?>;
var link                = createLink('testtask', 'linkcase', "taskID=" + taskID + "&type=all&param=0&recTotal=0&recPerPage=20&pageID=1&project=" + chprojectID);
var viewLink            = createLink('testtask', 'view', "taskID=" + taskID + "&project=" + chprojectID);
var reportLink          = createLink('testtask', 'report', "productID=" + productID +"&taskID=" + taskID + "&browseType=all&branchID=0&moduleID=0&chartType=pie&chprojectID=" + chprojectID);
var allTabLink          = createLink('testtask', 'cases', "taskID=" + taskID +"&browseType=all&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project=" + chprojectID);
var backLink            = createLink('chproject', 'testtask', "project=" + chprojectID);
var assignedtomeTabLink = createLink('testtask', 'cases', "taskID=" + taskID +"&browseType=assignedtome&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project=" + chprojectID);
var groupTabLink        = createLink('testtask', 'groupCase', "taskID=" + taskID + "&groupBy=story&chprojectID=" + chprojectID);

$('#allTab').attr('href', allTabLink);
$('.icon-link').parent('a').attr('href', link);
$('.icon-testtask-view').parent('a').attr('href', viewLink);
$('.icon-common-report').parent('a').attr('href', reportLink);
$('#back').attr('href', backLink);
$('#assignedtomeTab').attr('href', assignedtomeTabLink);
$('#groupTab a').attr('href', groupTabLink);

$('.btn-limit').parent().remove();
</script>
<?php endif;?>
