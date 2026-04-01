<?php
/**
 * The story view file of chproject module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     chproject
 * @version     $Id: story.html.php 5117 2013-07-12 07:03:14Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php $canOrder = common::hasPriv('execution', 'storySort');?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datatable.fix.html.php';?>
<?php if($canOrder) include $app->getModuleRoot() . 'common/view/sortable.html.php';?>
<?php js::set('moduleID', $this->cookie->storyModuleParam);?>
<?php js::set('productID', $this->cookie->storyProductParam);?>
<?php js::set('branchID', str_replace(',', '_', $this->cookie->storyBranchParam));?>
<?php js::set('executionID', $execution->id);?>
<?php js::set('projectID', $execution->project);?>
<?php js::set('storyType', $storyType);?>
<?php js::set('confirmUnlinkStory', $lang->execution->confirmUnlinkStory)?>
<?php js::set('typeError', sprintf($this->lang->error->notempty, $this->lang->task->type))?>
<?php js::set('typeNotEmpty', sprintf($this->lang->error->notempty, $this->lang->task->type));?>
<?php js::set('hourPointNotEmpty', sprintf($this->lang->error->notempty, $this->lang->story->convertRelations));?>
<?php js::set('hourPointNotError', sprintf($this->lang->story->float, $this->lang->story->convertRelations));?>
<?php js::set('workingHourError', sprintf($this->lang->error->notempty, $this->lang->workingHour))?>
<?php js::set('linkedTaskStories', $linkedTaskStories);?>
<?php js::set('confirmStoryToTask', $lang->execution->confirmStoryToTask);?>
<style>
.btn-group a.btn-secondary, .btn-group a.btn-primary {border-right: 1px solid rgba(255,255,255,0.2);}
.btn-group button.dropdown-toggle.btn-secondary, .btn-group button.dropdown-toggle.btn-primary {padding:6px;}
.export {margin-left: 0px !important;}
</style>
<?php $isAllModules = (!empty($module->name) or !empty($product->name) or !empty($branch)) ? false : true;?>
<?php $sidebarName  = $lang->tree->all;?>
<?php $removeBtn    = '';?>
<div id="mainMenu" class="clearfix">
  <div id="sidebarHeader">
    <?php
    if(!$isAllModules)
    {
        $sidebarName = isset($product) ? $product->name : (isset($branch) ? $branch : $module->name);
        $removeType  = isset($product) ? 'byproduct' : (isset($branch) ? 'bybranch' : 'bymodule');
        $removeLink  = inlink('story', "projectID=$projectID&intanceProjectID=$intanceProjectID&storyType=$storyType&orderBy=$orderBy&type=$removeType&param=0&recTotal=0&recPerPage={$pager->recPerPage}");
        $removeBtn   = html::a($removeLink, "<i class='icon icon-sm icon-close'></i>", '', "class='text-muted'");
    }
    ?>
    <div class="title" title='<?php echo $sidebarName;?>'>
      <?php echo $sidebarName;?>
      <?php echo $removeBtn;?>
    </div>
  </div>
  <div class="btn-toolbar pull-left">
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjects, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
          $class = '';
          if($intanceProjectID == 0) $class = 'class="active"';
          echo "<li $class>" . html::a($this->createLink('chproject', 'story', "projectID=$projectID&intanceProjectID=0&storyType=$storyType&orderBy=$orderBy&type=$type&param=$param"), $lang->chproject->allProject) . "</li>";
          foreach($intanceProjects as $key => $intanceProjectName)
          {
              $class = $intanceProjectID == $key ? 'class="active"' : '';
              echo "<li $class>" . html::a($this->createLink('chproject', 'story', "projectID=$projectID&intanceProjectID=$key&storyType=$storyType&orderBy=$orderBy&type=$type&param=$param"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
          }
        ?>
      </ul>
    </div>
    <?php foreach($lang->execution->featureBar['story'] as $featureType => $label):?>
    <?php $active = $type == $featureType ? 'btn-active-text' : '';?>
    <?php $label  = "<span class='text'>$label</span>";?>
    <?php if($type == $featureType) $label .= " <span class='label label-light label-badge'>{$pager->recTotal}</span>";?>
    <?php echo html::a(inlink('story', "projectID=$projectID&intanceProjectID=$intanceProjectID&storyType=$storyType&orderBy=order_desc&type=$featureType"), $label, '', "class='btn btn-link $active'");?>
    <?php endforeach;?>
    <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->product->searchStory;?></a>
  </div>
  <div class="btn-toolbar pull-right">
    <?php
    if(common::canModify('chproject', $project))
    {
        $this->lang->story->create = $this->lang->execution->createStory;

        if($productID)
        {
            $storyModuleID   = (int)$this->cookie->storyModuleParam;
            $createStoryLink = $this->createLink('story', 'create', "productID=$productID&branch=0&moduleID={$storyModuleID}&story=0&execution=0&bugID=0&planID=0&todoID=0&extra=&storyType=$storyType&chproject=$projectID");
            $batchCreateLink = $this->createLink('story', 'batchCreate', "productID=$productID&branch=0&moduleID={$storyModuleID}&story=0&execution=$execution->id&plan=0&storyType=$storyType");

            $buttonLink  = '';
            $buttonTitle = '';
            if(common::hasPriv('story', 'batchCreate'))
            {
                $buttonLink  = $batchCreateLink;
                $buttonTitle = $lang->story->batchCreate;
            }
            if(common::hasPriv('story', 'create'))
            {
                $buttonLink  = $createStoryLink;
                $buttonTitle = $lang->story->create;
            }

            $hidden = empty($buttonLink) ? 'hidden' : '';
            echo "<div class='btn-group dropdown' title='{$buttonTitle}'>";
            echo html::a($buttonLink, "<i class='icon icon-plus'></i> $buttonTitle", '', "class='btn btn-secondary $hidden' data-app='$app->tab'");

            if($common::hasPriv('story', 'create') and common::hasPriv('story', 'batchCreate'))
            {
                if(!($isAllProduct and count($products) > 1)) echo "<button type='button' class='btn btn-secondary dropdown-toggle' data-toggle='dropdown'><span class='caret'></span></button>";
                echo "<ul class='dropdown-menu pull-right'>";
                echo '<li>' . html::a($createStoryLink, $lang->story->create, '', "data-app=$app->tab") . '</li>';
                //echo '<li>' . html::a($batchCreateLink, $lang->story->batchCreate, '', "data-app=$app->tab") . '</li>';
                echo '</ul>';
            }

            echo '</div>';
        }

        // The none-product and single execution cann't link stories.  此条件一定为true，因为选择范围是融合敏捷项目，融合敏捷默认开启迭代.
        echo "<div class='btn-group dropdown'>";
        echo html::a('###', "<i class='icon-link'></i> {$lang->execution->linkStory}", '', "class='btn btn-primary'");

        if(common::hasPriv('execution', 'linkStory'))
        {
            echo "<button type='button' class='btn btn-primary dropdown-toggle' data-toggle='dropdown'><span class='caret'></span></button>";
            echo "<ul class='dropdown-menu pull-right'>";
            foreach($executionProjectPairs as $tmpExecutionID => $projectName)
            {
                $buttonLink = $this->createLink('execution', 'linkStory', "execution=$tmpExecutionID&browseType&param=0&recTotal=0&recPerPage=50&pageID=1&extra&storyType=$storyType&chProject=$projectID");
                echo "<li>" . html::a($buttonLink, $projectName, '', "title='{$projectName}' class='text-ellipsis' data-app='chteam'") . "</li>";
            }
            echo '</ul>';
        }

        echo '</div>';
    }
    ?>
  </div>
</div>
<div id="mainContent" class="main-row fade">
  <div class="side-col" id="sidebar">
    <div class="sidebar-toggle"><i class="icon icon-angle-left"></i></div>
    <div class="cell">
      <?php if(!$moduleTree):?>
      <hr class="space">
      <div class="text-muted" style='text-align:center'>
        <?php echo $lang->product->noModule;?>
      </div>
      <hr class="space">
      <?php endif;?>
      <?php echo $moduleTree;?>
    </div>
  </div>
  <div class="main-col">
    <div id='queryBox' data-module='chprojectStory' class='cell <?php if($type =='bysearch') echo 'show';?>'></div>
    <?php if(empty($stories)):?>
    <div class="table-empty-tip">
      <p><span class="text-muted"><?php echo $lang->story->noStory;?></span></p>
    </div>
    <?php else:?>
    <div class="table-header fixed-right">
      <nav class="btn-toolbar pull-right setting"></nav>
    </div>
    <form class='main-table table-story skip-iframe-modal' method='post' id='chprojectStoryForm'>
      <?php
      $datatableId  = $this->moduleName . ucfirst($this->methodName);
      $useDatatable = (isset($config->datatable->$datatableId->mode) and $config->datatable->$datatableId->mode == 'datatable');
      $vars = "projectID={$projectID}&intanceProjectID=$intanceProjectID&storyType=$storyType&orderBy=%s&type=$type&param=$param&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}";

      if($useDatatable) include $app->getModuleRoot() . 'common/view/datatable.html.php';

      $setting = $this->datatable->getSetting('chproject');
      $widths  = $this->datatable->setFixedFieldWidth($setting);
      $columns = 0;

      $checkObject = new stdclass();
      $checkObject->execution = $execution->id;

      $totalEstimate       = 0;
      $canBatchEdit        = common::hasPriv('story', 'batchEdit');
      $canBatchClose       = (common::hasPriv('story', 'batchClose') and $storyType != 'requirement');
      $canBatchChangeStage = (common::hasPriv('story', 'batchChangeStage') and $storyType != 'requirement');
      $canBatchUnlink      = common::hasPriv('execution', 'batchUnlinkStory');
      $canBatchToTask      = (common::hasPriv('story', 'batchToTask', $checkObject) and $storyType != 'requirement');
      $canBatchAssignTo    = common::hasPriv($storyType, 'batchAssignTo');

      $canBatchAction      = ($canBeChanged and ($canBatchEdit or $canBatchClose or $canBatchChangeStage or $canBatchUnlink or $canBatchToTask or $canBatchAssignTo));
      ?>
      <?php if(!$useDatatable) echo '<div class="table-responsive">';?>
      <table class='table tablesorter has-sort-head<?php if($useDatatable) echo ' datatable';?>' id='storyList' data-fixed-left-width='<?php echo $widths['leftWidth']?>' data-fixed-right-width='<?php echo $widths['rightWidth']?>'>
        <thead>
          <tr>
          <?php
          foreach($setting as $key => $value)
          {
              if($value->show)
              {
                  $this->datatable->printHead($value, $orderBy, $vars, $canBatchAction);
                  $columns ++;
              }
          }
          ?>
          </tr>
        </thead>
        <tbody id='storyTableList' class='sortable'>
          <?php foreach($stories as $key => $story):?>
          <?php
          $totalEstimate += $story->estimate;
          $story->from    = $this->app->tab;
          ?>
          <tr id="story<?php echo $story->id;?>" data-id='<?php echo $story->id;?>' data-order='<?php echo $story->order ?>' data-estimate='<?php echo $story->estimate?>' data-cases='<?php echo zget($storyCases, $story->id, 0)?>'>
          <?php
          foreach($setting as $key => $value)
          {
              if($value->id == 'project')
              {
                  echo "<td class='c-project' title='$story->projectName'>" . $story->projectName . "</td>";
              }
              else
              {
                  $this->story->printCell($value, $story, $users, $branchOption, $storyStages, $modulePairs, $storyTasks, $storyBugs, $storyCases, $useDatatable ? 'datatable' : 'table', $storyType, $execution, $showBranch);
              }
          }
          ?>
          </tr>
          <?php if(!empty($story->children)):?>
          <?php $i = 0;?>
          <?php foreach($story->children as $key => $child):?>
          <?php $child->from = $this->app->tab;?>
          <?php $class  = $i == 0 ? ' table-child-top' : '';?>
          <?php $class .= ($i + 1 == count($story->children)) ? ' table-child-bottom' : '';?>
          <tr class='table-children<?php echo $class;?> parent-<?php echo $story->id;?>' data-id='<?php echo $child->id?>' data-status='<?php echo $child->status?>' data-estimate='<?php echo $child->estimate?>' data-cases='<?php echo zget($storyCases, $story->id, 0);?>'>
          <?php
          foreach($setting as $key => $value)
          {
              if($value->id == 'project')
              {
                  echo "<td class='c-project' title='$story->projectName'>" . $story->projectName . "</td>";
              }
              else
              {
                  $this->story->printCell($value, $child, $users, $branchOption, $storyStages, $modulePairs, $storyTasks, $storyBugs, $storyCases, $useDatatable ? 'datatable' : 'table', $storyType, $execution);
              }
          }
          ?>
          </tr>
          <?php $i ++;?>
          <?php endforeach;?>
          <?php endif;?>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php if(!$useDatatable) echo '</div>';?>
      <div class='table-footer'>
        <?php if($canBatchAction):?>
        <div class="checkbox-primary check-all"><label><?php echo $lang->selectAll?></label></div>
        <?php endif;?>
        <div class='table-actions btn-toolbar'>
          <div class='btn-group dropup'>
            <?php
            $disabled   = $canBatchEdit ? '' : "disabled='disabled'";
            $actionLink = $this->createLink('story', 'batchEdit', "productID=0&executionID=$execution->id&branch=0&storyType=$storyType");
            echo html::commonButton($lang->edit, "data-form-action='$actionLink' $disabled");
            ?>
            <?php if($canBatchToTask):?>
            <button type='button' class='btn dropdown-toggle' data-toggle='dropdown'><span class='caret'></span></button>
            <ul class='dropdown-menu'>
              <?php
              echo "<li>" . html::a('#batchToTask', $lang->story->batchToTask, '', "data-toggle='modal' id='batchToTaskButton'") . "</li>";
              ?>
            </ul>
            <?php endif;?>
          </div>

          <?php if($canBatchAssignTo):?>
          <div class="btn-group dropup">
            <button data-toggle="dropdown" type="button" class="btn assignedTo"><?php echo $lang->story->assignedTo;?> <span class="caret"></span></button>
            <?php
            $withSearch = count($users) > 10;
            $actionLink = $this->createLink('story', 'batchAssignTo', "storyType=$storyType");
            echo html::select('assignedTo', $users, '', 'class="hidden"');
            ?>
            <div class="dropdown-menu search-list<?php if($withSearch) echo ' search-box-sink';?>" data-ride="searchList">
              <?php if($withSearch):?>
              <?php $usersPinYin = common::convert2Pinyin($users);?>
              <div class="input-control search-box has-icon-left has-icon-right search-example">
                <input id="userSearchBox" type="search" autocomplete="off" class="form-control search-input">
                <label for="userSearchBox" class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label>
                <a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a>
              </div>
              <?php endif;?>
              <div class="list-group">
              <?php foreach ($users as $key => $value):?>
              <?php
              if(empty($key) or $key == 'closed') continue;
              $searchKey = $withSearch ? ('data-key="' . zget($usersPinYin, $value, '') . " @$key\"") : "data-key='@$key'";
              echo html::a("javascript:$(\"#assignedTo\").val(\"$key\");setFormAction(\"$actionLink\", \"hiddenwin\", \"#storyList\")", $value, '', $searchKey);
              ?>
              <?php endforeach;?>
              </div>
            </div>
          </div>
          <?php endif;?>

          <?php
          if($canBatchClose)
          {
              $actionLink = $this->createLink('story', 'batchClose', "productID=0&executionID=$execution->id");
              echo html::commonButton($lang->close, "data-form-action='$actionLink'");
          }
          ?>
          <?php if($canBatchChangeStage):?>
          <div class="btn-group dropup">
            <button data-toggle="dropdown" type="button" class="btn"><?php echo $lang->story->stageAB;?> <span class="caret"></span></button>
            <ul class='dropdown-menu <?php echo count($stories) == 1 ? 'stageBox' : '';?>'>
            <?php
            $lang->story->stageList[''] = $lang->null;
            foreach($lang->story->stageList as $key => $stage)
            {
                if(empty($key)) continue;
                if(strpos('wait|planned|projected', $key) !== false) continue;
                $actionLink = $this->createLink('story', 'batchChangeStage', "stage=$key");
                echo "<li>" . html::a('#', $stage, '', "onclick=\"setFormAction('$actionLink', 'hiddenwin', '#storyList')\"") . "</li>";
            }
            ?>
            </ul>
          </div>
          <?php endif;?>
        </div>
        <div class="table-statistic"><?php echo $summary;?></div>
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>

<div class="modal fade" id="linkStoryByPlan">
  <div class="modal-dialog mw-500px">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="icon icon-close"></i></button>
        <?php
        $linkStoryByPlanTips = $multiBranch ? sprintf($lang->execution->linkBranchStoryByPlanTips, $lang->project->branch) : $lang->execution->linkNormalStoryByPlanTips;
        $linkStoryByPlanTips = $execution->multiple ? $linkStoryByPlanTips : str_replace($lang->execution->common, $lang->projectCommon, $linkStoryByPlanTips);
        ?>
        <h4 class="modal-title"><?php echo $lang->execution->linkStoryByPlan;?></h4><?php echo '(' . $linkStoryByPlanTips . ')';?>
      </div>
      <div class="modal-body">
        <div class='input-group'>
          <?php echo html::select('plan', $allPlans, '', "class='form-control chosen' id='plan'");?>
          <?php $allPlans = array_filter($allPlans);?>
          <?php $disabled = empty($allPlans) ? 'disabled' : ''?>
          <span class='input-group-btn'><?php echo html::commonButton($lang->execution->linkStory, "id='toTaskButton'", 'btn btn-primary ' . $disabled);?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="batchToTask">
  <div class="modal-dialog mw-600px">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="icon icon-close"></i></button>
        <h4 class="modal-title"><?php echo $lang->story->batchToTask;?></h4>
      </div>
      <div class="modal-body">
        <form method='post' class='not-watch' action='<?php echo $this->createLink('story', 'batchToTask', "executionID=$execution->id&projectID=$execution->project");?>'>
          <table class='table table-form'>
            <tr>
              <th><?php echo $lang->task->project?></th>
              <td><?php echo html::select('execution', $executionProjectPairs, '', "class='form-control chosen' required");?></td>
              <td></td>
            </tr>
            <tr>
              <th class="<?php echo strpos($this->app->getClientLang(), 'zh') === false ? 'w-140px' : 'w-80px';?>"><?php echo $lang->task->type?></th>
              <td><?php echo html::select('type', $lang->task->typeList, '', "class='form-control chosen' required");?></td>
              <td></td>
            </tr>
            <?php if($lang->hourCommon !== $lang->workingHour):?>
            <tr>
              <th><?php echo $lang->story->one . $lang->hourCommon?></th>
              <td><div class='input-group'><span class='input-group-addon'><?php echo "≈";?></span><?php echo html::input('hourPointValue', '', "class='form-control' required");?> <span class='input-group-addon'><?php echo $lang->workingHour;?></span></div></td>
              <td></td>
            </tr>
            <?php endif;?>
            <tr>
              <th><?php echo $lang->story->field;?></th>
              <td colspan='2'><?php echo html::checkbox('fields', $lang->story->convertToTask->fieldList, '', 'checked');?></td>
            </tr>
            <tr>
              <td colspan='3'><div class='alert alert-info no-margin'><?php echo $lang->story->batchToTaskTips?></div></td>
            </tr>
            <tr>
              <td colspan='3' class='text-center'>
                <?php echo html::hidden('chproject', $projectID);?>
                <?php echo html::hidden('storyIdList', '');?>
                <?php echo html::submitButton($lang->execution->next, '', 'btn btn-primary');?>
              </td>
            </tr>
          </table>
        </form>
      </div>
    </div>
  </div>
</div>
<?php js::set('checkedSummary', $lang->product->checkedSummary);?>
<?php js::set('executionID', $execution->id);?>
<?php js::set('orderBy', $orderBy)?>
<script>
$(function()
{
    /* Remove datatable setting. */
    $('chprojectStoryForm .table-header .btn-toolbar.pull-right').remove();

    /* Update table summary text. */
    <?php $storyCommon = $lang->SRCommon;?>
    var checkedSummary = '<?php echo str_replace('%storyCommon%', $storyCommon, $lang->product->checkedSummary)?>';
    $('#chprojectStoryForm').table(
    {
        statisticCreator: function(table)
        {
            var $checkedRows = table.getTable().find(table.isDataTable ? '.datatable-row-left.checked' : 'tbody>tr.checked');
            var $originTable = table.isDataTable ? table.$.find('.datatable-origin') : null;
            var checkedTotal = $checkedRows.length;
            if(!checkedTotal) return;

            var checkedEstimate = 0;
            var checkedCase     = 0;
            $checkedRows.each(function()
            {
                var $row = $(this);
                if ($originTable)
                {
                    $row = $originTable.find('tbody>tr[data-id="' + $row.data('id') + '"]');
                }
                var data = $row.data();
                checkedEstimate += data.estimate;
                if(data.cases > 0) checkedCase += 1;
            });
            var rate = Math.round(checkedCase / checkedTotal * 10000 / 100) + '' + '%';
            return checkedSummary.replace('%total%', checkedTotal)
                  .replace('%estimate%', checkedEstimate.toFixed(1))
                  .replace('%rate%', rate);
        }
    });
});
<?php if(!empty($useDatatable)):?>
$(function(){$('chprojectStoryForm').table();})
<?php endif;?>
</script>
<?php if(commonModel::isTutorialMode()): ?>
<style>
#storyList .c-count, #storyList .c-category {display: none!important;}
</style>
<?php endif; ?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
