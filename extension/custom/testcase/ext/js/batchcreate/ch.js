/**
 * Load stories of a product and a module.
 *
 * @param  int    productID
 * @param  int    moduleID
 * @param  int    num
 * @access public
 * @return void
 */
function loadStories(productID, moduleID, num)
{
    var branchIDName = (config.currentMethod == 'batchcreate' || config.currentMethod == 'showimport') ? '#branch' : '#branches';
    var branchID     = $(branchIDName + num).val();
    if(!branchID) branchID = 0;

    var storyLink = createLink('story', 'ajaxGetProductStories', 'productID=' + productID + '&branch=' + branchID + '&moduleID=' + moduleID + '&storyID=0&onlyOption=false&status=noclosed&limit=0&type=full&hasParent=1&objectID=0&number=' + num + '&source=testcase');
    $.get(storyLink, function(stories)
    {
        if(!stories) stories = '<select id="story' + num + '" name="story[' + num + ']" class="form-control"></select>';
        for(var i = num; i <= rowIndex ; i ++)
        {
            if(i != num && $('#module' + i).val() != 'ditto') break;

            var nowStories = stories.replaceAll('story' + num, 'story' + i);

            $('#story' + i).replaceWith(nowStories);
            $('#story' + i + "_chosen").remove();
            $('#story' + i).next('.picker').remove();
            $('#story' + i).attr('name', 'story[' + i + ']');
            $('#story' + i).picker();

            if(i == 1)
            {
                var myPicker = $('#story' + i).data('zui.picker');
                myPicker.setValue(0);
            }
        }
    });
}
