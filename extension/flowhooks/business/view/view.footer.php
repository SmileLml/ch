<?php
if($data->version > 1)
{
    $html  = '';
    $html .= "<small id='viewVersion' class='dropdown' style='margin-left: 10px'>";
    $html .= "<a href='#' data-toggle='dropdown' class='text-muted'>";
    $html .= '#' . $version;
    $html .= "<span class='caret'></span></a>";
    $html .= "<ul class='dropdown-menu'>";

    if($config->requestType == 'GET')       $link = $this->createLink('business', 'view', 't=' . $data->name . '&dataID=' . $data->id);
    if($config->requestType == 'PATH_INFO') $link = $this->createLink('business', 'view', 't=' . $data->id);

    for($i = $data->version; $i >= 1; $i --)
    {
        $class = $i == $version ? " class='active'" : '';
        $html .= "<li" . $class . ">" . html::a($link, '#' . $i, '', 'onclick="setVersion(' . $i . ')"') . "</li>";
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
            $link = helper::createLink('diff', 'index', 'objectType=business&objectID=' . $data->id . '&firstVersion=' . $version . '&secondVersion=' . $i, '', true);
            $diffMenu .= "<li>" . html::a($link, '#' . $i, '', 'class="iframe" data-toggle="modal" data-width="80%" data-height="90%"') . "</li>";
        }
        $diffMenu .= "</ul>";
        $diffMenu .= "</small>";
        $html .= $diffMenu;
    }
}
?>
<?php js::set('businessName', $data->name);?>
<?php js::set('stakeholdersHtml', $stakeholdersHtml);?>
<?php js::set('backUrl', $backUrl);?>
<?php js::set('disableAfterClickList', $lang->business->disableAfterClickList);?>
<?php
$data = $this->loadModel('project')->processBusinessData($data, 'businessview');
$requirementHtml  = '';
$requirementHtml .= '<tr>';
$requirementHtml .= "<th class='w-80px'>{$this->lang->project->requirement}</th>";
$requirementHtml .= "<td>{$data->requirement}</td>";
$requirementHtml .= '</tr>';
$requirementHtml .= '<tr>';
$requirementHtml .= "<th class='w-80px'>{$this->lang->project->estimate}</th>";
$requirementHtml .= "<td>{$data->estimate}</td>";
$requirementHtml .= '</tr>';

js::set('requirementHtml', $requirementHtml);
?>
<script>
$('#mainTitle .text').text(businessName);
$('#mainTitle .text').prop('title', businessName);
$('.main-col .panel').after(stakeholdersHtml);
setTimeout(function()
{
    var html = <?php echo json_encode($html) ?>;
    $('#mainTitle .text').after(html);
}, 100);

function setVersion(version)
{
    $.cookie('businessVersion', version, {expires: 0.000394, path:config.webRoot})
    window.location.reload();
}
$('.side-col.col-3 table').find('tr').last().after(requirementHtml);
$('.btn-toolbar.pull-left').find('a:first').attr('href', backUrl);
$('.btn-toolbar').last('div').find('a:first').attr('href', backUrl);
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
