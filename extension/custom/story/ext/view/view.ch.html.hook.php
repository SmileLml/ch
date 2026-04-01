<script>
$(document).ready(function() {
    $('li.legendStories a.removeButton').off('click').on('click', function(e) {
        e.preventDefault();
        var href        = $(this).attr('href');
        var isConfirmed = confirm('<?php echo $lang->story->confirmUnlink;?>');

        if (isConfirmed) {
            window.open(href, 'hiddenwin');
            $(this).closest('li').remove();
        }
    });
});
</script>
<?php if($this->app->tab == 'chteam'):?>
<script>
var link = '<?php echo $this->session->teamStoryList;?>';

$('a.btn-secondary').attr('href', link);
$('.btn-toolbar .icon-back').parent().attr('href', link);

$('#mainContent > div.side-col.col-4 > div:nth-child(2) > div > ul > li.active').hide();
$('#mainContent > div.side-col.col-4 > div:nth-child(2) > div > ul > li:nth-child(2)').addClass('active');
$('#legendStories').hide();
$('#legendProjectAndTask').addClass('active');
$("#legendProjectAndTask a").removeAttr("href");
$("#legendProjectAndTask a").removeClass("iframe");
$('#mainMenu > div.btn-toolbar.pull-right').hide();
</script>
<?php endif;?>

<?php
$story = $this->story->appendChproject(array($story), $this->session->chproject)[0];

$html  = '';
$html .= '<tr>';
$html .= '<th>' . $lang->story->project . '</th>';
$html .= '<td>' . $story->projectName . '</td>';
$html .= '</tr>';

$businessHtml = '';
if($story->type == 'requirement')
{
    $businessName = $this->dao->select('name')->from('zt_flow_business')->where('id')->eq($story->business)->fetch('name');
    $businessName = $businessName ? html::a(helper::createLink('business', 'view', 'dataID='.$story->business), $businessName, '', "title='$businessName' style='color: $story->color' data-app='project'") : '';
    $businessHtml .= '<tr>';
    $businessHtml .= '<th>' . $lang->story->business . '</th>';
    $businessHtml .= '<td>' . $businessName . '</td>';
    $businessHtml .= '</tr>';
}

$residueEstimateHtml = '';
if($story->type == 'requirement')
{
    $requirementEstimate = (float)$story->estimate;
    $haveEstimateID      = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($story->id)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');
    $haveEstimate        = $this->dao->select('estimate')->from(TABLE_STORY)->where('id')->in($haveEstimateID)->andWhere('deleted')->eq(0)->fetchAll();
    $haveEstimate        = array_reduce($haveEstimate, function($carry, $item){return bcadd($carry, $item->estimate, 2);}, '0.0');

    $residueEstimate     = bcsub($requirementEstimate, $haveEstimate, 2) . $this->lang->story->day;
    $businessHtml .= '<tr>';
    $businessHtml .= '<th>' . $lang->story->residueEstimate . '</th>';
    $businessHtml .= '<td>' . $residueEstimate . '</td>';
    $businessHtml .= '</tr>';
}

js::set('residueEstimateHtml', $residueEstimateHtml);
js::set('businessHtml', $businessHtml);
js::set('storyType', $story->type);
?>
<script>
$('#legendBasicInfo > table > tbody').prepend('<?php echo json_encode($html);?>');
$('#legendBasicInfo > table > tbody').prepend(businessHtml);
$('#legendBasicInfo > table > tbody').prepend(residueEstimateHtml);

var estimateHtml = $('th:contains("<?php echo $this->lang->story->estimate;?>")').next();
var estimate     = estimateHtml.text();

estimate = estimate.replace('<?php echo $this->config->hourUnit;?>', '<?php echo $this->lang->story->day;?>');
estimateHtml.text(estimate);
estimateHtml.attr('title', estimate);

</script>
<?php
$projectID     = $this->dao->select('project')->from('zt_projectstory')->where('story')->eq($story->id)->orderBy('project_desc')->fetch('project');
$project       = $this->loadModel('project')->getById($projectID);
?>
<?php if($project->instance  && $story->status != 'draft')
:?>
<script>
$('#linkButton').css({
    'pointer-events': 'none',
    'color': 'gray'
});
</script>
<?php endif?>
