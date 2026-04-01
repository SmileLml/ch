<?php
/**
 * The browse view file of testtask module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     testtask
 * @version     $Id: browse.html.php 1914 2011-06-24 10:11:25Z yidong@cnezsoft.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php $hideProduct = (isset($project->hasProduct) and $project->hasProduct == '0');?>
<?php if(!$hideProduct):?>
<style>
.table-footer {margin-left: 205px}
.table-group-btns {width:200px;}
</style>
<?php endif;?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php if(!empty($tasks) and !$hideProduct):?>
    <div class="pull-left table-group-btns">
      <button type="button" class="btn btn-link group-collapse-all"><?php echo $lang->testtask->collapseAll;?> <i class="icon-fold-all muted"></i></button>
      <button type="button" class="btn btn-link group-expand-all"><?php echo $lang->testtask->expandAll;?> <i class="icon-unfold-all muted"></i></button>
    </div>
    <?php endif;?>
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjectPairs, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
            $class = '';
            if($intanceProjectID == 0) $class = 'class="active"';
            echo "<li $class>" . html::a($this->createLink('chproject', 'testtask', "projectID=$projectID&intanceProjectID=0&orderBy=$orderBy&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID"), $lang->chproject->allProject) . "</li>";
            foreach($intanceProjectPairs as $key => $intanceProjectName)
            {
                $class = $intanceProjectID == $key ? 'class="active"' : '';
                echo "<li $class>" . html::a($this->createLink('chproject', 'testtask', "projectID=$projectID&intanceProjectID=$key&orderBy=$orderBy&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
            }
        ?>
      </ul>
    </div>

    <?php $total = 0;?>
    <?php foreach($tasks as $productTasks) $total += count($productTasks);?>
    <a href='' class='btn btn-link btn-active-text'>
      <span class='text'><?php echo $lang->testtask->browse;?></span>
      <span class="label label-light label-badge"><?php echo $total;?></span>
    </a>
  </div>
  <div class="btn-toolbar pull-right">
    <?php if(common::canModify('execution', $execution)):?>
    <?php common::printLink('testtask', 'create', "product=0&executionID=$executionID&build=0&projectID=$execution->project&chprojectID=$projectID#app=chteam", "<i class='icon icon-plus'></i> " . $lang->testtask->create, '', "class='btn btn-primary'");?>
    <?php endif;?>
  </div>
</div>
<?php
$waitCount    = 0;
$testingCount = 0;
$blockedCount = 0;
$doneCount    = 0;
?>
<div id="mainContent">
  <?php if(empty($tasks)):?>
  <div class="table-empty-tip">
    <p>
      <span class="text-muted"><?php echo $lang->testtask->noTesttask;?></span>
      <?php if(common::canModify('execution', $execution) and common::hasPriv('testtask', 'create')):?>
      <?php echo html::a($this->createLink('testtask', 'create', "product=0&executionID=$executionID&build=0&projectID=$execution->project&chprojectID={$projectID}#app=chteam"), "<i class='icon icon-plus'></i> " . $lang->testtask->create, '', "class='btn btn-info' data-app='chteam'");?>
      <?php endif;?>
    </p>
  </div>
  <?php else:?>
  <form class="main-table table-testtask" data-group="true" method="post" target='hiddenwin' id='testtaskForm'>
    <table class="table table-grouped has-sort-head" id='taskList'>
      <thead>
        <?php $vars = "projectID={$projectID}&executionID=$executionID&orderBy=%s&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}&pageID={$pager->pageID}#app=chteam";?>
        <?php $canTestReport = ($canBeChanged and common::hasPriv('chproject', 'testreport'));?>
        <tr class='<?php if($total and !$hideProduct) echo 'divider'; ?>'>
          <th class='c-side text-center <?php if($hideProduct) echo 'hide';?>'><?php common::printOrderLink('product', $orderBy, $vars, $lang->testtask->product);?></th>
          <th class="c-id">
            <?php common::printOrderLink('id', $orderBy, $vars, $lang->idAB);?>
          </th>
          <th><?php common::printOrderLink('name', $orderBy, $vars, $lang->testtask->name);?></th>
          <th><?php common::printOrderLink('product', $orderBy, $vars, $lang->testtask->product);?></th>
          <th><?php common::printOrderLink('project', $orderBy, $vars, $lang->testtask->project);?></th>
          <th><?php common::printOrderLink('build', $orderBy, $vars, $lang->testtask->build);?></th>
          <th class='c-status'><?php common::printOrderLink('status', $orderBy, $vars, $lang->statusAB);?></th>
          <th class='c-user'><?php common::printOrderLink('owner', $orderBy, $vars, $lang->testtask->owner);?></th>
          <th class='c-date'><?php common::printOrderLink('begin', $orderBy, $vars, $lang->testtask->begin);?></th>
          <th class='c-date'><?php common::printOrderLink('end', $orderBy, $vars, $lang->testtask->end);?></th>
          <th class='c-actions-5 text-center'><?php echo $lang->actions;?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($tasks as $product => $productTasks):?>
        <?php $productName = zget($products, $product, '');?>
        <?php foreach($productTasks as $task):?>
        <?php $taskProject = $this->loadModel('project')->getById($task->project);?>
        <?php $taskProductName = ($taskProject->hasProduct == 0) ? '' : $productName;?>
        <?php if($task->status == 'wait')    $waitCount ++;?>
        <?php if($task->status == 'doing')   $testingCount ++;?>
        <?php if($task->status == 'blocked') $blockedCount ++;?>
        <?php if($task->status == 'done')    $doneCount ++;?>
        <?php $projectName = zget($intanceProjectPairs, $task->project, '');?>
        <tr data-id='<?php echo $product;?>' <?php if($task == reset($productTasks)) echo "class='divider-top'";?> data-status='<?php echo $task->status;?>'>
          <?php if($task == reset($productTasks)):?>
          <td rowspan='<?php echo count($productTasks);?>' class='c-side text-left group-toggle <?php if($hideProduct) echo 'hide';?>'>
            <a class='text-primary' title='<?php echo $productName;?>'><i class='icon icon-caret-down'></i> <?php echo $productName;?></a>
            <div class='small'><span class='text-muted'><?php echo $lang->testtask->allTasks;?></span> <?php echo count($productTasks);?></div>
          </td>
          <?php endif;?>
          <td class="c-id">
            <?php printf('%03d', $task->id);?>
          </td>
          <td class='text-left' title="<?php echo $task->name?>"><?php echo html::a($this->createLink('testtask', 'cases', "taskID=$task->id&browseType=all&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project={$projectID}"), $task->name, '', "data-app='chteam'");?></td>
          <td class='text-left' title="<?php echo $taskProductName?>"><?php echo $taskProductName;?></td>
          <td class='text-left' title="<?php echo $projectName?>"><?php echo $projectName;?></td>
          <td title="<?php echo $task->buildName?>"><?php echo ($task->build == 'trunk' || empty($task->buildName)) ? $lang->trunk : $task->buildName;?></td>
          <?php $status = $this->processStatus('testtask', $task);?>
          <td title='<?php echo $status;?>'>
            <span class='status-testtask status-<?php echo $task->status?>'><?php echo $status;?></span>
          </td>
          <td><?php echo zget($users, $task->owner);?></td>
          <td><?php echo $task->begin?></td>
          <td><?php echo $task->end?></td>
          <td class='c-actions'>
            <?php
            if($canBeChanged)
            {
                common::printIcon('testtask', 'cases',    "taskID=$task->id&browseType=all&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&project={$projectID}", $task, 'list', 'sitemap');
                common::printIcon('testtask', 'linkCase', "taskID=$task->id&type=all&param=0&recTotal=0&recPerPage=20&pageID=1&project={$projectID}", $task, 'list', 'link');
                common::printIcon('chproject', 'testreport', "project=$projectID&intanceProjectID={$task->project}&extra={$task->id}", '', 'list', 'summary', '', '', false, "data-app='chteam'", $this->lang->testreport->common);
                common::printIcon('testtask', 'edit',   "taskID=$task->id&project={$projectID}", $task, 'list', '', '', '', false, "data-app='chteam'");
                common::printIcon('testtask', 'delete', "taskID=$task->id&confirm=no&project={$projectID}", $task, 'list', 'trash', 'hiddenwin');
            }
            ?>
          </td>
        </tr>
        <?php endforeach;?>
        <tr data-id='<?php echo $product;?>' class='group-toggle group-summary divider hidden'>
          <td class='c-side text-left'>
            <a title='<?php echo $productName;?>'><i class='icon-caret-right text-muted'></i> <?php echo $productName;?></a>
          </td>
          <td colspan='8' class='text-left'>
            <div class='small with-padding'>
              <span class='text-muted'><?php echo $lang->testtask->allTasks;?></span> <?php echo count($productTasks);?>
            </div>
          </td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class="table-footer">
      <div class="table-statistic"><?php echo sprintf($lang->testtask->allSummary, $total, $waitCount, $testingCount, $blockedCount, $doneCount);?></div>
      <?php $pager->show('right', 'pagerjs');?>
    </div>
  </form>
  <?php endif;?>
</div>
<?php js::set('pageSummary', sprintf($lang->testtask->allSummary, $total, $waitCount, $testingCount, $blockedCount, $doneCount));?>
<?php js::set('checkedAllSummary', $lang->testtask->checkedAllSummary);?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
