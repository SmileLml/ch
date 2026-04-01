<?php
if($data->version > 1)
{
    $objectVersions = $this->dao->select('version,action,actionCount')->from('zt_objectversion')->where('objectType')->eq('projectapproval')->andWhere('objectID')->eq($data->id)->fetchAll('version');
    $html  = '';
    $html .= "<small id='viewVersion' class='dropdown' style='margin-left: 10px'>";
    $html .= "<a href='#' data-toggle='dropdown' class='text-muted'>";
    $html .= '#' . $version;
    $html .= "<span class='caret'></span></a>";
    $html .= "<ul class='dropdown-menu'>";

    if($config->requestType == 'GET')       $link = $this->createLink('projectapproval', 'view', 't=' . $data->name . '&dataID=' . $data->id);
    if($config->requestType == 'PATH_INFO') $link = $this->createLink('projectapproval', 'view', 't=' . $data->id);

    for($i = $data->version; $i >= 1; $i --)
    {
        $class         = $i == $version ? " class='active'" : '';
        $currentAction = $objectVersions[$i]->action;
        $actionName    = '';
        if($currentAction)
        {
            $actionName = ($currentAction == 'approvalsubmit3') ? sprintf($lang->projectapproval->objectVersion->$currentAction, $objectVersions[$i]->actionCount) : $lang->projectapproval->objectVersion->$currentAction;
        }
        $html .= "<li" . $class . ">" . html::a($link, '#' . $i . ' ' . $actionName, '', 'onclick="setVersion(' . $i . ')"') . "</li>";
    }
    $html .= "</ul>";
    $html .= "</small>";

    if(common::hasPriv('diff', 'index'))
    {
        $diffMenu = '';
        $diffMenu .= "<small id='compareVersion' class='dropdown' style='margin-left: 10px'>";
        $diffMenu .= html::a('javascript:void(0);', '版本对比 <span class="caret"></span>', '', 'class="btn btn-link" data-toggle="dropdown"');
        $diffMenu .= "<ul class='dropdown-menu'>";
        for($i = $data->version; $i >= 1; $i --)
        {
            if($i == $version) continue;

            $currentAction = $objectVersions[$i]->action;
            $actionName    = '';
            if($currentAction)
            {
                $actionName = ($currentAction == 'approvalsubmit3') ? sprintf($lang->projectapproval->objectVersion->$currentAction, $objectVersions[$i]->actionCount) : $lang->projectapproval->objectVersion->$currentAction;
            }

            $link = helper::createLink('diff', 'index', 'objectType=projectapproval&objectID=' . $data->id . '&firstVersion=' . $version . '&secondVersion=' . $i, '', true);
            $diffMenu .= "<li>" . html::a($link, '#' . $i . ' ' . $actionName, '', 'class="iframe" data-toggle="modal" data-width="80%" data-height="90%"') . "</li>";
        }
        $diffMenu .= "</ul>";
        $diffMenu .= "</small>";
        $html .= $diffMenu;
    }
}
?>

<?php
$exportVersion = (isset($_COOKIE['projectapprovalVersion']) && !empty($_COOKIE['projectapprovalVersion'])) ? $_COOKIE['projectapprovalVersion'] : '';

$projectExportHtml  = '<div class="btn-toolbar pull-right btn-group dropdown-hover">';
if(common::hasPriv('projectapproval', 'exportReportWord'))
{
    $projectExportHtml .= '<button class="btn btn-link" data-toggle="dropdown"><i class="icon icon-export muted"></i> <span class="text">' . $lang->export . $lang->projectapproval->approvalReport . '</span> <span class="caret"></span></button>';
    $projectExportHtml .= '<ul class="dropdown-menu pull-right" id="exportActionMenu">';
    $projectExportHtml .= "<li >" . html::a($this->createLink('projectapproval', 'exportReportWord', 'dataID=' . $data->id . '&version=' . $exportVersion . '&type=word'), 'word', '', "class='export'") . "</li>";
    $projectExportHtml .= "<li >" . html::a($this->createLink('projectapproval', 'exportReportWord', 'dataID=' . $data->id . '&version=' . $exportVersion. '&type=pdf'), 'pdf', '', "class='export'") . "</li>";
    $projectExportHtml .= '</ul>';

}
// $projectExportHtml .= common::hasPriv('projectapproval', 'exportReportWord') ? html::a($this->createLink('projectapproval', 'exportReportWord', 'dataID=' . $data->id . '&version=' . $exportVersion), 'word', '', "class='export'") : '';
$projectExportHtml .= '</div>';

$backUrl = $this->session->projectapprovalViewBackUrl ? $this->session->projectapprovalViewBackUrl : '';
?>

<?php js::set('projectapprovalName', $data->name);?>
<?php js::set('isShowChangeDetail', $isShowChangeDetail);?>
<?php js::set('isShowCancelDetail', $isShowCancelDetail);?>
<?php js::set('extraHistoryDetailHtml', $extraHistoryDetailHtml);?>
<?php js::set('extraHistoryDetailHtml', $extraHistoryDetailHtml);?>
<?php js::set('isFinished', $isFinished);?>
<?php js::set('actualCost', $actualCost);?>
<?php js::set('actualCostText', $lang->projectapproval->actualCost);?>
<?php js::set('businessLineText', $lang->projectapproval->businessLine);?>
<?php js::set('requestType', $config->requestType);?>
<?php js::set('backUrl', $backUrl);?>
<?php js::set('disableAfterClickList', $lang->projectapproval->disableAfterClickList);?>
<script>
let projectbusinessLink = '';
if(requestType == 'GET')       projectbusinessLink = createLink('projectapproval', 'business', 'dataID=' + dataID);
if(requestType == 'PATH_INFO') projectbusinessLink = createLink('projectapproval', 'business', 't=' + dataID);
$('nav>ul>li').eq(1).find('a').attr('href', projectbusinessLink);

if(requestType == 'GET')       finishReportLink = createLink('projectapproval', 'finishReport', 'dataID=' + dataID);
if(requestType == 'PATH_INFO') finishReportLink = createLink('projectapproval', 'finishReport', 't=' + dataID);
$('nav>ul>li').eq(2).find('a').attr('href', finishReportLink);


$('#mainTitle .text').text(projectapprovalName);
$('#mainTitle .text').prop('title', projectapprovalName);

$('.side-col').find('th:contains("' + businessLineText + '")').closest('tr').after('<tr><th class="w-80px">' + actualCostText + '</th><td>' + actualCost + '</td></tr>');

setTimeout(function()
{
    var html = <?php echo json_encode($html);?>;
    $('#mainTitle .text').after(html);

    var projectExportHtml = <?php echo json_encode($projectExportHtml);?>;
    $('#mainTitle .pull-left').after(projectExportHtml);
}, 100);

function setVersion(version)
{
    $.cookie('projectapprovalVersion', version, {expires: 0.000394, path:config.webRoot});
    window.location.reload();
}

if(isShowChangeDetail || isShowCancelDetail)
{
    var changeHistoryHtml = $('.panel').eq(1).prop('outerHTML');
    $('.panel').eq(0).after(changeHistoryHtml);
    $('.panel').eq(1).find('.panel-heading strong').text('<?php echo $isShowChangeDetail ? $this->lang->projectapproval->changeHistoryDetail : $this->lang->projectapproval->cancelHistoryDetail;?>');
    $('.panel').eq(1).find('.panel-body').html(extraHistoryDetailHtml);
}

if(backUrl != '')
{
    $('.btn-toolbar.pull-left').find('a:first').attr('href', backUrl);
    $('.btn-toolbar').last('div').find('a:first').attr('href', backUrl);
}

$(document).ready(function() {
    $.each(disableAfterClickList, function(key,value){
        $('.main-actions a:contains("' + value + '")').click(function(event) {
            var link = $(this);
            event.preventDefault();

            link.off('click');
            link.css({
            'color': 'gray',
            'pointer-events': 'none'
            });
        });
    })
});
</script>
