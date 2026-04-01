<?php if($this->app->tab == 'chteam') js::set('objectID', $executionID);?>
<?php if($this->app->tab == 'chteam'):?>
<script>
var chprojects = "<?php echo str_replace(array("\r", "\n", "\r\n"), '', html::select('execution', $executions, $executionID, "class='form-control chosen'"));?>";
$('#mainContent #dataform tbody tr:first').after('<tr><th><?php echo $lang->testcase->project;?></th><td colspan="2"><div class="input-group">' + chprojects + '</div></td></tr>')
$('#execution').on('change', function()
{
    var productID   = $('#product').val();
    var branch      = $('#branch').val() === 'undefined' ? 0 : $('#branch').val();
    var executionID = $(this).val();
    var chprojectID = '<?php echo $chprojectID?>';

    link = createLink('testcase', 'create', 'productID=' + productID + '&branch=' + branch + '&moduleID=0&from=&param=&storyID=0&extras=executionID=' + executionID + '&chprojectID=' + chprojectID);
    location.href = link;
});
</script>
<?php endif;?>