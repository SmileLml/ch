<?php if($app->tab == 'chteam'):?>
<?php if($formRelation):?>
<style>
#execution_chosen {pointer-events: none;}
#execution_chosen > a {background-color: #f5f5f5;}
</style>
<?php endif;?>
<script>
$('#selectTestStoryBox').parent('td').hide();
$('#type').change(function()
{
    if(lifetime != 'ops' && attribute != 'request' && attribute != 'review')
    {
        toggleSelectTestStory();
    }
});

$('#showAllModule').parent('div').parent('td').hide();

/**
 * Load module, stories and members.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadAll(executionID)
{
    lifetime      = lifetimeList[executionID];
    attribute     = attributeList[executionID];
    var fieldList = showFields + ',';
    if(lifetime == 'ops' || attribute == 'request' || attribute == 'review')
    {
        $('.storyBox,#selectTestStoryBox,#testStoryBox').addClass('hidden');
    }
    else if(fieldList.indexOf('story') >= 0)
    {
        $('.storyBox,#selectTestStoryBox').removeClass('hidden');
        if($('#selectTestStory').prop('checked')) $('#testStoryBox').removeClass('hidden');
    }

    link = createLink('execution', 'linkStory', 'executionID=' + executionID);
    $('#storyBox > a:nth-child(1)').attr('href', link);

    loadModuleMenu(executionID);
    loadExecutionStories(executionID);
    loadExecutionMembers(executionID);
}

</script>
<?php endif;?>
