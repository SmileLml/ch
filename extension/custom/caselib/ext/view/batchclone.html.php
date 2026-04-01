<?php
/**
 * The batch create case view of caselib module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Yidong Wang <yidong@cnezsoft.com>
 * @package     caselib
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php js::set('testcaseBatchCreateNum', $config->testcase->batchCreate);?>
<?php js::set('libID', $libID);?>
<?php js::set('fromCases', $fromCases);?>
<div id="mainContent" class="main-content fade">
  <div class="main-header">
    <h2><?php echo $lang->testcase->batchCreate;?></h2>
  </div>
  <form method='post' class='load-indicator main-form' enctype='multipart/form-data' target='hiddenwin' id="batchCreateForm">
    <table align='center' class='table table-form' id="tableBody">
      <thead>
        <tr class='text-center'>
          <th class='c-id'><?php echo $lang->idAB;?></th>
          <th class='c-id'><?php echo $lang->testcase->cloneCaseID;?></th>
          <th class='c-id w-150px'><?php echo $lang->caselib->common;?></th>
          <th class='c-module<?php echo strpos($config->testcase->create->requiredFields, 'module') !== false ? ' required' : '';?>'><?php echo $lang->testcase->module;?></th>
          <th class='required'><?php echo $lang->testcase->title;?></th>
          <th class='c-status required'><?php echo $lang->testcase->type;?></th>
          <th class='c-status<?php  echo strpos($config->testcase->create->requiredFields, 'pri') !== false ? ' required' : '';?>'><?php echo $lang->testcase->pri;?></th>
          <th class='c-text<?php  echo strpos($config->testcase->create->requiredFields, 'precondition') !== false ? ' required' : '';?>'><?php echo $lang->testcase->precondition;?></th>
          <th class='c-text<?php  echo strpos($config->testcase->create->requiredFields, 'keywords') !== false ? ' required' : '';?>'><?php echo $lang->testcase->keywords;?></th>
          <th class='c-text<?php  echo strpos($config->testcase->create->requiredFields, 'stage') !== false ? ' required' : '';?>'><?php echo $lang->testcase->stage;?></th>
        </tr>
      </thead>
      <tbody>
      <?php unset($lang->testcase->typeList['']);?>
      <?php foreach($fromCases as $i => $value):?>
      <?php
      $i += 1;  
      ?>
      <tr>
        <td class="text-center"><?php echo $i+1;?></td>
        <td class="text-center">
        <?php echo $value->id;?>
        <?php echo html::hidden("caseIDList[$i]", $value->id)?>
        </td>
        <td class="text-center">
        <?php echo html::select("lib[$i]", $libs, $value->lib, "class='form-control chosen' onchange='loadLibModules(this.value);'");?>
        </td>
        <td class='text-left' style='overflow:visible'><?php echo html::select("modules[$i]", array(), $value->module, "class='form-control chosen'");?></td>
        <td style='overflow:visible'>
          <div class="input-control has-icon-right">
            <?php echo html::input("title[$i]", $value->title, "class='form-control title-import'");?>
            <div class="colorpicker">
              <button type="button" class="btn btn-link dropdown-toggle" data-toggle="dropdown"><span class="cp-title"></span><span class="color-bar"></span><i class="ic"></i></button>
              <ul class="dropdown-menu clearfix">
                <li class="heading"><?php echo $lang->testcase->colorTag;?><i class="icon icon-close"></i></li>
              </ul>
              <?php echo html::hidden("color[$i]", $value->color, "data-provide='colorpicker' data-icon='color' data-wrapper='input-control-icon-right'  data-update-color='#title\\[$i\\]'");?>
            </div>
          </div>
        </td>
        <td><?php echo html::select("type[$i]", $lang->testcase->typeList, $value->type, "class='form-control chosen'");?></td>
        <td><?php echo html::select("pri[$i]", $lang->testcase->priList, $value->pri, "class='form-control chosen'");?></td>
        <td><?php echo html::textarea("precondition[$i]", $value->precondition, "rows='1' class='form-control autosize'")?></td>
        <td><?php echo html::input("keywords[$i]", $value->keywords, "class='form-control'");?></td>
        <td class='text-left' style='overflow:visible'><?php echo html::select("stage[$i][]", $lang->testcase->stageList, $value->stage, "class='form-control chosen' multiple");?></td>
      </tr>
      <?php endforeach;?>
      </tbody>
      <tfoot>
        <tr><td colspan='9' class='text-center form-actions'><?php echo html::submitButton()?> <?php echo  html::backButton();?></td></tr>
      </tfoot>
    </table>
  </form>
</div>
<script>
$(function()
{
    fromCases.forEach(function(value, index){
        var currentIndex = index + 1;
        loadLibModules($('#lib' + currentIndex).val(), currentIndex, true);
    })
})
function loadLibModules($lib, index, isCheckOld = false)
{
    var fromIndex  = index -1;
    var moduleID   = isCheckOld ? fromCases[fromIndex].module : 0;
    link = createLink('tree', 'ajaxGetOptionMenu', 'libID=' + libID + '&viewtype=caselib&branch=0&rootModuleID=0&returnType=html&fieldID=' + index + '&needManage=false&extra=nodeleted&currentModuleID=' + moduleID);
    $('#modules' + index).parent('td').load(link, function()
    {
        $("#modules" + index).chosen()
    });
}
</script>
<?php include $app->getModuleRoot() . 'common/view/pastetext.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
