<?php if($this->app->tab == 'chteam'):?>
<script>
var preHref = '<?php echo inlink('runCase', "runID={$preCase['runID']}&caseID={$preCase['caseID']}&version={$preCase['version']}&confirm=&chprojectID={$chprojectID}");?>';
var nextHref = '<?php echo inlink('runCase', "runID={$nextCase['runID']}&caseID={$nextCase['caseID']}&version={$nextCase['version']}&confirm=&chprojectID={$chprojectID}");?>';
$('#pre').attr('href', preHref);
$('#next').attr('href', nextHref);

function loadResult()
{
    $('#resultsContainer').load("<?php echo $this->createLink('testtask', 'results', "runID={$runID}&caseID=$caseID&version=$version&status=all&chprojectID={$chprojectID}");?> #casesResults", function()
    {
        $('.result-item').click(function()
        {
            var $this = $(this);
            if($this.data('status') == 'running')
            {
                return;
            }
            $this.toggleClass('show-detail');
            var show = $this.hasClass('show-detail');
            $this.next('.result-detail').toggleClass('hide', !show);
            $this.find('.collapse-handle').toggleClass('icon-chevron-down', !show).toggleClass('icon-chevron-up', show);;
        });

        $('#casesResults table caption .result-tip').html($('#resultTip').html());

        if($('.result-item:first').data('status') == 'running')
        {
            var times = 0;
            var id    = $('.result-item:first').data('id')
            var link  = createLink('testtask', 'ajaxGetResult', 'resultID=' + id);

            var resultInterval = setInterval(() => {
                times++;
                if(times > 600)
                {
                    clearInterval(resultInterval);
                }

                $.get(link, function(task)
                {
                    task = JSON.parse(task);
                    task = task.data;
                    if(task.ZTFResult != '')
                    {
                        clearInterval(resultInterval);
                        loadResult();
                    }
                });
            }, 1000);
        }
    });
}

/**
 * Create bug from fail case.
 *
 * @param  object $obj
 * @access public
 * @return void
 */
function createBug(obj)
{
    var chprojectID = '<?php echo $chprojectID;?>';
    var projectID   = '<?php echo $projectID;?>';
    var $form       = $(obj).closest('form');
    var params      = $form.data('params') + ',projectID=' + projectID;
    var stepIdList  = '';
    $form.find('.step .step-id :checkbox').each(function()
    {
        if($(this).prop('checked')) stepIdList += $(this).val() + '_';
    });

    var onlybody    = config.onlybody;
    config.onlybody = 'no';

    var link = createLink('bug', 'create', params + ',stepIdList=' + stepIdList + '&chprojectID=' + chprojectID) + '#app=chteam';
    if(tab == 'my')
    {
        window.parent.$.apps.open(link, 'qa');
    }
    else
    {
        window.open(link, '_blank');
    }

    config.onlybody = onlybody;
}
</script>
<?php endif?>