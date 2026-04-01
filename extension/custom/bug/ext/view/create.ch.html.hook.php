<?php if($app->tab == 'chteam'):?>
<script>
<?php if($caseID):?>
var gobackText = '<?php echo $lang->goback;?>';
var goback     = '<?php echo $this->createLink('chproject', 'bug', "&chprojectID=$chprojectID");?>' + '#app=chteam';
$('tfoot').find('a:contains("' + gobackText + '")').attr('href', goback);
<?php endif?>
$('#customField').addClass('hidden');
$('#buildBox > div.input-group-btn').addClass('hidden');

$('#project').on('change', function()
{
    var productID   = $('#product').val();
    var branch      = $('#branch').val() > 0 ? $('#branch').val() : 0;
    var projectID   = $(this).val();
    var bugID       = '<?php echo $bugID?>';
    var chprojectID = '<?php echo $chprojectID?>';

    link = createLink('bug', 'create', 'productID=' + productID + '&branch=' + branch + '&extras=projectID=' + projectID + '&chprojectID=' + chprojectID + '&copyBugID=' + bugID);
    location.href = link;
});

function loadAll(productID)
{
    $('#taskIdBox').innerHTML = '<select id="task"></select>';  // Reset the task.
    $('#task').chosen();

    $('#branch').remove();
    $('#branch_chosen').remove();
    $('#branch').next('.picker').remove();

    var executionID  = $('#execution').val();
    var branchStatus = 'active';
    var oldBranch    = 0;
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
        loadExecutionBuilds(executionID);
    })
}
</script>
<?php endif;?>
