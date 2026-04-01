<?php if($this->app->tab == 'chteam') js::set('objectID', $case->execution);?>
<?php if($this->app->tab == 'chteam'):?>
<script>
var chprojects = "<?php echo str_replace(array("\r", "\n", "\r\n"), '', html::select('execution', $executions, $executionID, "class='form-control chosen'"));?>";
$('.detail').eq(8).find('tbody tr:first').after('<tr><th><?php echo $lang->testcase->project;?></th><td><div class="input-group">' + chprojects + '</div></td></tr>')
$('#execution').on('change', function()
{
    var executionID = $(this).val();

    link = createLink('testcase', 'edit', 'caseID=' + '<?php echo $case->id;?>' + '&comment=false&executionID=' + executionID + '&chprojectID=' + '<?php echo $chprojectID?>');
    location.href = link;
});
$('.detail').eq(8).find('tbody tr:not(.hide):last').remove();
$('.detail').eq(8).find('tbody tr:not(.hide):last').remove();
//setStories();
</script>
<?php endif;?>
