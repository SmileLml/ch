<script>
$(document).ready(function()
{
    $('#version').closest('tr').hide();
    $('#projectApproval').parent().parent().parent().hide();
    $('#projectType').parent().parent().parent().hide();
});

function showMilestone(projectapprovalID)
{
    if(projectapprovalID == '')
    {
        $('#goLiveDate').closest('tr').hide();
        $('#acceptanceDate').closest('tr').hide();
        $('#PRDdate').closest('tr').hide();
    }
    else
    {
        $('#goLiveDate').closest('tr').show();
        $('#acceptanceDate').closest('tr').show();
        $('#PRDdate').closest('tr').show();
    }
}
</script>
