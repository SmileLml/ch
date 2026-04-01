<?php
/**
 * The batch create view of testcase module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Yangyang Shi <shiyangyang@cnezsoft.com>
 * @package     testcase
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php js::set('testcaseBatchCreateNum', $config->testcase->batchCreate);?>
<?php js::set('productID', $productID);?>
<?php js::set('branch', $branch);?>
<?php js::set('requiredFields', $config->testcase->create->requiredFields)?>
<?php js::set('showFields', $showFields);?>
<?php js::set('fromCases', $fromCases);?>
<div id="mainContent" class="main-content fade">
  <div class="main-header">
    <h2>
      <?php echo $lang->testcase->batchClone;?>
    </h2>
  </div>
  <?php
  $visibleFields  = array();
  $requiredFields = array();
  foreach(explode(',', $showFields) as $field)
  {
      if($field) $visibleFields[$field] = '';
  }
  foreach(explode(',', $config->testcase->create->requiredFields) as $field)
  {
      if($field)
      {
          $requiredFields[$field] = '';
          if(strpos(",{$config->testcase->customBatchCreateFields},", ",{$field},") !== false) $visibleFields[$field] = '';
      }
  }
  $colspan     = count($visibleFields) + 3;
  $hiddenStory = (isonlybody() and $story) ? ' hidden' : '';
  if($hiddenStory and isset($visibleFields['story'])) $colspan -= 1;
  $storyPairs['ditto'] = $lang->testcase->ditto;
  ?>
  <form method='post' class='load-indicator main-form' enctype='multipart/form-data' target='hiddenwin' id="batchCreateForm">
    <div class="table-responsive">
      <table class="table table-form" id="tableBody">
        <thead>
          <tr class='text-center'>
            <th class='c-id'><?php echo $lang->idAB;?></th>
            <th class='c-id'><?php echo $lang->testcase->cloneCaseID;?></th>
            <th class='c-product productBox' style = 'width:270px;'><?php echo $lang->testcase->product ;?></th>
            <th class='c-module moduleBox'><?php echo $lang->testcase->module;?></th>
            <th class='c-project'><?php echo $lang->testcase->project ;?></th>
            <th class='c-execution executionBox' style = 'width:180px;'><?php echo $lang->testcase->execution ;?></th>
            <th class='c-scene sceneBox' style = 'width:180px;'><?php echo $lang->testcase->scene;?></th>
            <th class='c-story storyBox' style = 'width:270px;'> <?php echo $lang->testcase->story;?></th>
            <th class='text-left required has-btn c-title w-150px'><?php echo $lang->testcase->title;?></th>
            <th class='c-type text-left required w-150px'><?php echo $lang->testcase->type;?></th>
            <th class='c-pri priBox w-150px'><?php echo $lang->testcase->pri;?></th>
            <th class='c-precondition preconditionBox w-150px'><?php echo $lang->testcase->precondition;?></th>
            <th class='c-keywords keywordsBox w-150px'><?php echo $lang->testcase->keywords;?></th>
            <th class='c-stage stageBox w-150px'><?php echo $lang->testcase->stage;?></th>
            <th class='c-review reviewBox w-150px'><?php echo $lang->testcase->review;?></th>
          </tr>
        </thead>
        <tbody>
          <?php unset($lang->testcase->typeList['']);?>
          <?php foreach($fromCases as $i => $value):?>
          <?php
          $i += 1;
          ?>
          <tr>
            <td class="text-center"><?php echo $i;?></td>
            <td class="text-center">
            <?php echo $value->id;?>
            <?php echo html::hidden("caseIDList[$i]", $value->id)?>
            </td>
            <td class="text-left productBox">
              <div class='input-group'>
                <?php echo html::select("product[$i]", $products, $value->product, "class='form-control chosen' onchange='loadProductBranches(this.value, $i)'");?>
              </div>            
            </td>
            <td class='text-left moduleBox' style='overflow:visible'><?php echo html::select("modules[$i]", array(), '', "class='form-control chosen' onchange='loadStories($productID, this.value, $i)' data-drop_direction='down'");?></td>
            <td class="text-left projectBox"><?php echo html::select("project[$i]", array(), '', "class='form-control' onchange='loadExecutions($productID, this.value, $i)'");?></td>
            <td class="text-left executionBox"><?php echo html::select("execution[$i]", array(), '', "class='form-control' onchange='loadStories($productID, this.value, $i)'");?></td>
            <td class='text-left' style='overflow:visible;'><?php echo html::select("scene[$i]", $sceneOptionMenu, $currentSceneID, "class='form-control chosen' data-drop_direction='down'");?></td>
            <td class='text-left storyBox' style='overflow:visible'> <?php echo html::select("story[$i]", $storyPairs, $story ? $story->id : 'ditto', 'class="form-control picker-select"');?></td>
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
            <td class=' priBox'><?php echo html::select("pri[$i]", $lang->testcase->priList, $value->pri, "class='form-control chosen'");?></td>
            <td class='preconditionBox'><?php echo html::textarea("precondition[$i]", $value->precondition, "rows='1' class='form-control autosize'")?></td>
            <td class='keywordsBox'><?php echo html::input("keywords[$i]", $value->keywords, "class='form-control'");?></td>
            <td class='text-left stageBox' style='overflow:visible'><?php echo html::select("stage[$i][]", $lang->testcase->stageList, $value->stage, "class='form-control chosen' multiple");?></td>
            <td class=' reviewBox'><?php echo html::select("needReview[$i]", $lang->testcase->reviewList, $needReview, "class='form-control chosen'");?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan='15' class='text-center form-actions'>
              <?php echo html::submitButton('', '', 'form-stash-clear btn btn-wide btn-primary');?>
              <?php echo html::backButton();?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </form>
</div>

<?php include $app->getModuleRoot() . 'common/view/pastetext.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
