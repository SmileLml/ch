<script>
/* Get select of stories.*/
function setStories(moduleID, executionID)
{
    link = createLink('story', 'ajaxGetExecutionStories', 'executionID=' + executionID + '&productID=0&branch=all&moduleID=' + moduleID + '&storyID=0&number=&type=full&status=all');
    $.get(link, function(stories)
    {
        var storyID = $('#story').val();
        if(!stories) stories = '<select id="story" name="story" class="form-control"></select>';
        $('#story').replaceWith(stories);
        if($('#story').length == 0 && $('#storyBox').length != 0) $('#storyBox').html(stories);

        $('#story').val(storyID);
        setPreview();
        $('#story_chosen').remove();
        $('#story').next('.picker').remove();
        $("#story").addClass('filled').chosen();

        /* If there is no story option, select will be hidden and text will be displayed; otherwise, the opposite is true */
        if($('#story option').length > 1 || parseInt(hasProduct) == 0)
        {
            $('#story').parent().removeClass('hidden');
            $('#storyBox').addClass('hidden');
        }
        else
        {
            $('#storyBox').removeClass('hidden');
            $('#story').parent().addClass('hidden');
        }
    });
}

/**
 * Load stories of the execution.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadExecutionStories(executionID)
{
    $.get(createLink('story', 'ajaxGetExecutionStories', 'executionID=' + executionID + '&productID=0&branch=0&moduleID=0&storyID=' + $('#story').val() + '&number=&type=full&status=all'), function(data)
    {
        $('#story_chosen').remove();
        $('#story').next('.picker').remove();
        $('#story').replaceWith(data);
        $('#story').addClass('filled').chosen();

        /* If there is no story option, select will be hidden and text will be displayed; otherwise, the opposite is true */
        if($('#story option').length > 1)
        {
            $('#story').parent().removeClass('hidden');
            $('#storyBox').addClass('hidden');
        }
        else
        {
            $('#storyBox').removeClass('hidden');
            $('#storyBox > a:first').attr('href', createLink('execution', 'linkStory', 'executionID=' + executionID));
            $('#storyBox > a:nth-child(2)').attr('href', "javascript:loadStories(" + executionID + ")");
            $('#story').parent().addClass('hidden');
        }

        if($('#testStoryBox table tbody tr').length == 0)
        {
            var trHtml  = $('#testStoryTemplate tr').prop("outerHTML");
            $('#testStoryBox table tbody').append(trHtml);

            $td = $('#testStoryBox table tbody tr:first').find('#testStory').closest('td');
            $td.html(data);
            $td.find('#story').val($td.find('#story option').eq(i).val()).attr('id', 'testStory').attr('name', 'testStory[]').addClass('filled').chosen();

            $td = $('#testStoryBox table tbody tr:first').find('#testPri_chosen').closest('td');
            $td.find('#testPri_chosen').remove();
            $td.find('#testPri').chosen();
        }
        else
        {
            $('#testStoryBox table tbody tr').each(function(i)
            {
                $td = $(this).find('#testStory').closest('td');
                $td.html(data);
                $td.find('#story').val($td.find('#story option').eq(i).val()).attr('id', 'testStory').attr('name', 'testStory[]').addClass('filled').chosen();
            });
        }
    });
}
</script>

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
