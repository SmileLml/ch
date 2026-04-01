<?php if($app->tab == 'chteam'):?>
<?php js::set('projectID' , $bug->project);?>
<?php js::set('chprojectID', $chprojectID);?>
<?php js::set('noProductExecutions', $noProductExecutions);?>
<?php js::set('productList', $productList);?>
<style>
#resolvedBuildBox {width: 100%;}
</style>
<script>
$('#openedBuildBox > span').addClass('hidden');
$('#resolvedBuildBox > span').addClass('hidden');
$('#dataform > div.main-row > div.side-col.col-4 > div > div:nth-child(4)').addClass('hidden');

/**
 * Load executions of product.
 *
 * @param  int    $productID
 * @param  int    $projectID
 * @access public
 * @return void
 */
function loadProductExecutions(productID, projectID = 0)
{
    required = $('#execution_chosen').hasClass('required');
    branch   = $('#branch').val();
    if(typeof(branch) == 'undefined') branch = 0;

    if(projectID != 0 && projectExecutionPairs[projectID] !== undefined)
    {
        $('#executionIdBox').parents('.executionBox').hide();
        var execution = projectExecutionPairs[projectID];
    }
    else
    {
        $('#executionIdBox').parents('.executionBox').show();
        var execution = $('#execution').val();
    }

    link = createLink('product', 'ajaxGetExecutions', 'productID=' + productID + '&projectID=' + projectID + '&branch=' + branch + '&number=&executionID=' + execution + '&from=&mode=stagefilter&chprojectID=' + chprojectID);
    $('#executionIdBox').load(link, function()
    {
        $(this).find('select').chosen();
        if(typeof(bugExecution) == 'string') $('#executionIdBox').prepend("<span class='input-group-addon' id='executionBox' style='border-left-width: 0px;'>" + bugExecution + "</span>");
        if(required) $(this).find('#execution_chosen').addClass('required');
        changeAssignedTo(projectID);

        var execution = $('#execution').val();
        loadExecutionRelated(execution);

        if(noProductExecutions[execution] !== undefined)
        {
            $('#product').parents('tr').removeClass('hide');
        }
        else
        {
            $('#product').parents('tr').addClass('hide');
        }
    });
}

function loadAll(productID)
{
    if(typeof(changeProductConfirmed) != 'undefined' && !changeProductConfirmed)
    {
        firstChoice = confirm(confirmChangeProduct);
        changeProductConfirmed = true;    // Only notice the user one time.

        if(!firstChoice)
        {
            $('#product').val(oldProductID);//Revert old product id if confirm is no.
            $('#product').trigger("chosen:updated");
            $('#product').chosen();
            return true;
        }

        loadAll(productID);
    }
    else
    {
        $('#taskIdBox').innerHTML = '<select id="task"></select>';  // Reset the task.
        $('#task').chosen();

        $('#branch').remove();
        $('#branch_chosen').remove();
        $('#branch').next('.picker').remove();

        var executionID  = $('#execution').val();
        var branchStatus = 'all';
        var oldBranch    = bugBranch;
        var param        = "productID=" + productID + "&oldBranch=" + oldBranch + "&param=" + branchStatus;
        param += "&projectID=" + '<?php echo $execution->id?>';
        $.get(createLink('branch', 'ajaxGetBranches', param), function(data)
        {
            if(data)
            {
                $('#product').closest('.input-group').append(data);
                $('#branch').css('width', page == 'create' ? '120px' : '65px');
                $('#branch').chosen();
            }

            loadProductModules(productID);
            loadProductplans(productID);
            loadProductStories(productID);
            loadAllBuildsForChteam(executionID, productID);
        })
    }
}

function loadAllBuildsForChteam(executionID, productID)
{
    link = createLink('build', 'ajaxGetExecutionBuilds', 'executionID=' + executionID + '&productID=' + productID + '&varName=openedBuild&build=' + oldOpenedBuild + '&branch=' + branch + '&index=0&needCreate=false&type=normal');
    $('#openedBuildBox').load(link, function(){$(this).find('select').val(oldOpenedBuild).picker({optionRender: markReleasedBuilds})});

    oldResolvedBuild = $('#resolvedBuild').val() ? $('#resolvedBuild').val() : 0;
    link = createLink('build', 'ajaxGetExecutionBuilds', 'executionID=' + executionID + '&productID=' + productID + '&varName=resolvedBuild&build=' + oldResolvedBuild + '&branch=' + branch);
    $('#resolvedBuildBox').load(link, function(){$(this).find('select').val(oldResolvedBuild).picker({optionRender: markReleasedBuilds, dropWidth: 'auto'})});
}

/**
 * Load execution related bugs and tasks.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadExecutionRelated(executionID)
{
    executionID      = parseInt(executionID);
    currentProjectID = $('#project').val() == 'undefined' ? 0 : $('#project').val();

    if(executionID)
    {
        if(currentProjectID == 0) loadProjectByExecutionID(executionID);
        loadExecutionStories(executionID);
        loadExecutionBuilds(executionID);
        loadAssignedTo(executionID, $('#assignedTo').val());
        loadTestTasks($('#product').val(), executionID);
    }
    else
    {
        var currentProductID = $('#product').val();

        $('#taskIdBox').innerHTML = '<select id="task"></select>';  // Reset the task.
        loadProductStories(currentProductID);
        loadTestTasks(currentProductID);
        if(currentProjectID == 0)
        {
            loadProductMembers(currentProductID);
        }
        else
        {
            loadProjectTeamMembers(currentProjectID);
        }

        currentProjectID != 0 ? loadProjectBuilds(currentProjectID) : loadProductBuilds(currentProductID);
    }

    loadExecutionTasks(executionID);
}
/**
 * Load builds of a execution.
 *
 * @param  int      executionID
 * @param  int      num
 * @access public
 * @return void
 */
function loadExecutionBuilds(executionID, num)
{
    if(typeof(num) == 'undefined') num = '';
    var branch         = $('#branch' + num).val();
    var oldOpenedBuild = $('#openedBuild' + num).val() ? $('#openedBuild' + num).val() : 0;
    var productID      = $('#product' + num).val();

    if(typeof(branch) == 'undefined') var branch = 0;
    if(typeof(productID) == 'undefined') var productID = 0;

    $.get(createLink('bug', 'ajaxGetReleasedBuilds', 'productID=' + productID), function(data){releasedBuilds = data;}, 'json');

    productID = noProductExecutions[executionID] !== undefined ? productID : productList[executionID];
    loadAllBuildsForChteam(executionID, productID);
    loadProductModules(productID);
}
</script>
<?php endif;?>
