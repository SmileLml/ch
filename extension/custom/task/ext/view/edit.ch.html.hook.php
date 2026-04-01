<?php if($app->tab == 'chteam'):?>
<?php js::set('designID', 0)?>
<script>
$('#showAllModuleBox').hide();

/**
 * Load module of the execution.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadModuleMenu(executionID)
{
    var link = createLink('tree', 'ajaxGetOptionMenu', 'rootID=' + executionID + '&viewtype=task&branch=0&rootModuleID=0&returnType=html&fieldID=&needManage=0&extra=' + 'allModule');
    $('#moduleIdBox').load(link, function(){$('#module').chosen();});
}

/**
 * Load stories of the execution.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadExecutionStories(executionID, moduleID)
{
    if(typeof(moduleID) == 'undefined') moduleID = 0;
    var link = createLink('story', 'ajaxGetExecutionStories', 'executionID=' + executionID + '&productID=0&branch=0&moduleID=' + moduleID + '&storyID=' + oldStoryID + '&number=&type=&status=active');
    $('#storyIdBox').load(link, function(){$('#story').chosen();});
}
</script>
<?php endif;?>
