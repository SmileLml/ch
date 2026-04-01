<?php if($this->app->tab == 'chteam'):?>
<script>
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
    var link        = createLink('bug', 'create', params + ',stepIdList=' + stepIdList + '&chprojectID=' + chprojectID) + '#app=chteam';
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