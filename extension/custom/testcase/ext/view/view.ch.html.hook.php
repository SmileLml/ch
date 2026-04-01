<?php if($isLibCase):?>
<?php
$libHtml  = '';
$libHtml = "<tr>";
$libHtml .= "<th class='thWidth'>{$lang->testcase->callCase}</th>";
$libHtml .= "<td>";

if(isset($case->callCaseTitles))
{
    foreach($case->callCaseTitles as $callCaseID => $callCaseTitle)
    {
        $libHtml .= html::a($this->createLink('testcase', 'view', "caseID=$callCaseID", '', true), "#$callCaseID $callCaseTitle", '', "class='iframe' data-width='80%'") . '<br />';
    }
}

$libHtml .= "<td></tr>";
?>
<script>
var libRow = <?php echo json_encode($libHtml); ?>;
$('#mainContent > div.side-col.col-4 > div:nth-child(1) > details > div > table > tbody').append(libRow);
</script>
<?php endif;?>
<?php if($app->tab == 'chteam'):?>
<?php
js::set('from', $from);
if($from == 'testtask')
{

    $chProjectID = $this->dao->select('ch')->from(TABLE_CHPROJECTINTANCES)->where('zentao')->eq($case->execution)->fetch('ch');
    $backUrl     = helper::createLink('testtask', 'cases', "taskID=$taskID&browseType=all&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project=$chProjectID");
    js::set('backUrl', $backUrl);
}
$moduleName = '';
if(empty($modulePath)) $moduleName = '/';

if($caseModule->branch and isset($branches[$caseModule->branch])) $moduleName .= $branches[$caseModule->branch] . $lang->arrow;

foreach($modulePath as $key => $module)
{
    $moduleName .= $module->name;
    if(isset($modulePath[$key + 1])) $moduleName .= $lang->arrow;
}
?>
<script>
var newLink         = '<?php echo $this->session->teamTestcaseList;?>';
var legendBasicInfo = '<?php echo $lang->testcase->legendBasicInfo;?>';
$('summary:contains("' + legendBasicInfo + '")').next().find('tbody tr:first').before('<tr id="projectBox"><th class="w-90px"><?php echo $lang->testcase->project;?></th><td><?php echo zget($projects, $execution->project, $execution->project);?></td></tr>');
if(from == 'testtask')
{
    $('.icon-back').parent().attr('href', backUrl);
}
else
{
    $('.icon-back').parent().attr('href', newLink);
}
$('summary:contains("' + legendBasicInfo + '")').next().find('tbody tr:eq(2) td').html('<?php echo $moduleName;?>');
</script>
<?php endif;?>
<?php
ob_start();
common::printPreAndNext($preAndNext, $this->createLink('testcase', 'view', "testcaseID=%s&version=0&from=$from&taskID=$taskID"));
$preAndNextHtml = ob_get_clean();
$preAndNextHtml = str_replace('"', "'", $preAndNextHtml);
$preAndNextHtml = str_replace(array("\r", "\n", "\r\n"), '', $preAndNextHtml);
?>
<script>
var preAndNext = "<?php echo $preAndNextHtml;?>";
$('#mainActions').html(preAndNext);
$('#mainMenu > div.btn-toolbar.pull-right > a').hide();
</script>
