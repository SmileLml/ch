<?php if($this->app->tab == 'chteam'):?>
<script>
$('#dataform > div.main-row > div.side-col.col-4 > div > div:nth-child(3)').hide();
</script>
<?php endif;?>
<?php
$businessHtml = '';
if(($this->app->tab == 'project' or $this->app->tab == 'product') && $story->type == 'requirement')
{
    $busienssStyle = ($story->business && $story->status != 'draft') ? 'style="pointer-events: none; opacity: 0.6;"' : '';
    $businessHtml .= '<tr>';
    $businessHtml .= "<th>{$this->lang->story->business}</th>";
    $businessHtml .= "<td $busienssStyle>";
    $businessHtml .= html::select('business', $business, $story->business, "class='form-control chosen'");
    $businessHtml .= '</td>';
    $businessHtml .= '</th>';
}
js::set('businessHtml', $businessHtml);
?>
<script>
$('.side-col.col-4 table:eq(0) tr').last().after(businessHtml);
$('input[name="estimate"]').on('input', function()
{
    var value = $(this).val().replace(/[^0-9.]/g, '');
    var parts = value.split('.');

    if (parts.length > 2)
    {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    if (parts[1] && parts[1].length > 2)
    {
        value = `${parts[0]}.${parts[1].slice(0, 2)}`;
    }

    $(this).val(value)
})

$('#reviewer').closest('div.detail').hide();
$('#saveButton').hide();
$('th:contains("<?php echo $this->lang->story->estimate;?>")').text('<?php echo $this->lang->story->estimateAB;?>')
</script>
<?php
$isProjectapproval = false;
$tempProjectID     = $this->dao->select('project')->from('zt_projectstory')->where('story')->eq($story->id)->fetch('project');
if($tempProjectID)
{
    $tempProject = $this->loadModel('project')->getByID($tempProjectID);
    if($tempProject->instance) $isProjectapproval = true;
}
?>
<?php if($story->status != 'draft' && $isProjectapproval):?>
<script>
$('input[name="estimate"]').prop('readonly', true);
</script>
<?php endif?>
