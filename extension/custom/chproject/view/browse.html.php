<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php js::set('edit', $lang->edit);?>
<?php js::set('selectAll', $lang->selectAll);?>
<?php js::set('checkedExecutions', $lang->execution->checkedExecutions);?>
<?php js::set('cilentLang', $this->app->getClientLang());?>
<?php js::set('defaultTaskTip', $lang->programplan->stageCustom->task);?>
<?php js::set('disabledTaskTip', sprintf($lang->project->disabledInputTip, $lang->edit . $lang->executionCommon));?>
<?php js::set('defaultExecutionTip', $lang->edit . $lang->executionCommon);?>
<?php js::set('disabledExecutionTip', sprintf($lang->project->disabledInputTip, $lang->programplan->stageCustom->task));?>
<?php js::set('checkedSummary', $lang->execution->checkedExecSummary);?>
<?php js::set('pageSummary', $lang->execution->pageExecSummary);?>
<?php js::set('executionSummary', $lang->execution->executionSummary);?>
<div id='mainMenu' class='clearfix'>
  <div class='btn-toolbar pull-left'>
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjects, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
          $class = '';
          if($intanceProjectID == 0) $class = 'class="active"';
          echo "<li $class>" . html::a($this->createLink('chproject', 'browse', "teamID=$teamID&intanceProjectID=0&status=$status&orderBy=$orderBy"), $lang->chproject->allProject) . "</li>";
          foreach($intanceProjects as $key => $intanceProjectName)
          {
              $class = $intanceProjectID == $key ? 'class="active"' : '';
              echo "<li $class>" . html::a($this->createLink('chproject', 'browse', "teamID=$teamID&intanceProjectID=$key&status=$status&orderBy=$orderBy"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
          }
        ?>
      </ul>
    </div>
    <?php common::sortFeatureMenu();?>
    <?php foreach($lang->project->featureBar['execution'] as $key => $label):?>
    <?php $label = "<span class='text'>$label</span>";?>
    <?php if($status == $key) $label .= " <span class='label label-light label-badge'>{$pager->recTotal}</span>";?>
    <?php echo html::a($this->createLink('chproject', 'browse', "teamID=$teamID&intanceProjectID=$intanceProjectID&status=$key&orderBy=$orderBy"), $label, '', "class='btn btn-link' id='{$key}Tab'");?>
    <?php endforeach;?>
    <?php if(common::hasPriv('execution', 'batchEdit') and !empty($executionStats)) //echo html::checkbox('editExecution', array('1' => $lang->edit . $lang->executionCommon), '', $this->cookie->editExecution ? 'checked=checked' : '');?>
  </div>
  <div class='btn-toolbar pull-right'>
    <?php //common::printLink('execution', 'export', "status=$status&productID=$productID&orderBy=$orderBy&from=project", "<i class='icon-export muted'> </i> " . $lang->export, '', "class='btn btn-link export'")?>
    <?php if(common::hasPriv('chproject', 'create') && $teamID) echo html::a($this->createLink('chproject', 'create', "teamID=$teamID"), "<i class='icon icon-sm icon-plus'></i> " . $lang->chproject->create, '', "class='btn btn-primary create-chproject-btn' data-app='chteam' onclick='$(this).removeAttr(\"data-toggle\")'");?>
  </div>
</div>
<div id='mainContent' class="main-row fade">
  <?php if(empty($executionStats)):?>
  <div class="table-empty-tip">
    <p>
      <span class="text-muted"><?php echo $lang->chproject->noChproject;?></span>
      <?php if(common::hasPriv('chproject', 'create') && $teamID):?>
      <?php echo html::a($this->createLink('chproject', 'create', "teamID=$teamID"), "<i class='icon icon-plus'></i> " . $lang->chproject->create, '', "class='btn btn-info' data-app='chteam'");?>
      <?php endif;?>
    </p>
  </div>
  <?php else:?>
  <?php $canBatchEdit = common::hasPriv('execution', 'batchEdit'); ?>
  <form class='main-table' id='executionForm' method='post' data-nested='true' data-expand-nest-child='false' data-enable-empty-nested-row='true' data-replace-id='executionTableList' data-preserve-nested='true'>
    <table class="table table-from table-fixed table-nested" id="executionList">
      <?php $vars = "status=$status&orderBy=%s";?>
      <thead>
        <tr>
          <th class='c-title'><?php echo $lang->nameAB;?></th>
          <th class='text-left c-title'><?php echo $lang->chproject->project;?></th>
          <th class='text-left c-title'><?php echo $lang->chproject->product;?></th>
          <th class='c-status text-center'><?php echo $lang->chproject->status;?></th>
          <th class='w-70px'><?php echo $lang->chproject->owner;?></th>
          <th class='c-date'><?php echo $lang->chproject->begin;?></th>
          <th class='c-enddate'><?php echo $lang->chproject->end;?></th>
          <th class='w-50px text-right'><?php echo $lang->chproject->estimateAB;?></th>
          <th class='w-50px text-right'><?php echo $lang->chproject->consumedAB;?></th>
          <th class='w-50px text-right'><?php echo $lang->chproject->leftAB;?> </th>
          <th class='w-50px'><?php echo $lang->chproject->progress;?></th>
          <th class='text-center c-actions-4'><?php echo $lang->actions;?></th>
        </tr>
      </thead>
      <tbody id="executionTableList">
        <?php foreach($executionStats as $chproject):?>
        <?php $this->chproject->printNestedList($chproject, $users, $intanceProjects);?>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class='table-footer'>
      <div class="table-statistic"></div>
      <?php $pager->show('right', 'pagerjs');?>
    </div>
  </form>
  <?php endif;?>
</div>
<?php js::set('status', $status)?>
<?php js::set('orderBy', $orderBy)?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
