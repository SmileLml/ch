<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<style>
body {margin-bottom: 10px;}
#subHeader #dropMenu .col-left .list-group {margin-bottom: 0px; padding-top: 10px;}
#subHeader #dropMenu .col-left {padding-bottom: 0px;}
#currentBranch + #dropMenu .col-left {padding-bottom: 30px;}
</style>
<div id="mainMenu" class="clearfix main-row fade in">
  <div id="sidebarHeader">
    <div class="title" title="<?php echo $moduleName;?>">
     <?php
     echo $moduleName;
     if(!empty($moduleID))
     {
         $removeLink = $this->createLink('chproject', 'testcase', "projectID=$projectID&intanceProjectID=$intanceProjectID&productID=$productID&branchID=$branchID&type=$type&moduleID=0&orderBy=$orderBy");
         echo html::a($removeLink, "<i class='icon icon-sm icon-close'></i>", '', "class='text-muted' data-app='{$this->app->tab}'");
     }
     ?>
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
          echo "<li $class>" . html::a($this->createLink('chproject', 'testcase', "projectID=$projectID&intanceProjectID=0&productID=$productID&branchID=$branchID&type=$type&moduleID=$moduleID&orderBy=$orderBy"), $lang->chproject->allProject) . "</li>";
          foreach($intanceProjects as $key => $intanceProjectName)
          {
              $class = $intanceProjectID == $key ? 'class="active"' : '';
              echo "<li $class>" . html::a($this->createLink('chproject', 'testcase', "projectID=$projectID&intanceProjectID=$key&productID=$productID&branchID=$branchID&type=$type&moduleID=$moduleID&orderBy=$orderBy"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
          }
        ?>
      </ul>
    </div>
    <?php
     foreach($this->lang->execution->featureBar['testcase'] as $label)
     {
         echo html::a($this->createLink('chproject', 'testcase', "projectID=$projectID"), "<span class='text'>{$label}</span> <span class='label label-light label-badge'>{$pager->recTotal}</span>", '', "class='btn btn-link btn-active-text'");
     }
    ?>
  </div>
  <div class="btn-toolbar pull-right">
    <?php if(common::canModify('execution', $project)) echo html::a(helper::createLink('testcase', 'create', "productID=$defaultProduct&branch=0&moduleID=0&from=&param=&storyID=0&extras=&chprojectID=$projectID", '', '', '', true), "<i class='icon icon-plus'></i> " . $lang->testcase->create, '', "class='btn btn-primary' data-app='{$this->app->tab}'");?>
  </div>
</div>
<div id="mainContent" class='main-row split-row fade'>
  <div class="side-col" id='sidebar' data-min-width='235'>
    <div class="sidebar-toggle"><i class="icon icon-angle-left"></i></div>
    <div class='cell'>
      <?php if(empty($moduleTree)):?>
      <hr class="space">
      <div class="text-center text-muted"><?php echo $lang->testcase->noModule;?></div>
      <hr class="space">
      <?php else:?>
      <?php echo $moduleTree;?>
      <?php endif;?>
    </div>
  </div>
  <?php
  $canBatchClone = common::hasPriv('testcase', 'batchClone');
  ?>
  <div class='main-col' data-min-width='400'>
    <?php if(empty($cases)):?>
    <div class="table-empty-tip">
      <p>
        <span class="text-muted"><?php echo $lang->testcase->noCase;?></span>
        <?php if(common::canModify('execution', $project) and common::hasPriv('testcase', 'create')):?>
        <?php echo html::a(helper::createLink('testcase', 'create', "productID=$defaultProduct&branch=0&moduleID=0&from=&param=&storyID=0&extras=&chprojectID=$projectID", '', '', '', true), "<i class='icon icon-plus'></i> " . $lang->testcase->create, '', "class='btn btn-info' data-app='{$this->app->tab}'");?>
        <?php endif;?>
      </p>
    </div>
    <?php else:?>
    <form class='main-table' method='post' id='executionBugForm' data-ride="table">
      <div style="overflow: auto;">
        <table class='table has-sort-head' id='bugList'>
        <?php $vars = "projectID=$projectID&intanceProjectID=$intanceProjectID&productID=$productID&branchID=$branchID&type=$type&moduleID=$moduleID&orderBy=%s&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}&pageID={$pager->pageID}";?>
          <thead>
            <tr>
              <th class='w-id'>
                <?php
                if($canBatchClone) echo "<div class='checkbox-primary check-all' title='{$this->lang->selectAll}'><label></label></div>";
                common::printOrderLink('id', $orderBy, $vars, $lang->idAB);?>
              </th>
              <th class="c-project"> <?php common::printOrderLink('title',         $orderBy, $vars, $lang->testcase->title);?></th>
              <th class='c-pri'>     <?php common::printOrderLink('pri',           $orderBy, $vars, $lang->priAB);?></th>
              <th class='c-project'> <?php common::printOrderLink('projectID',     $orderBy, $vars, $lang->testcase->project);?></th>
              <th class='c-product'> <?php common::printOrderLink('productID',     $orderBy, $vars, $lang->testcase->product);?></th>
              <th class='c-type'>    <?php common::printOrderLink('type',          $orderBy, $vars, $lang->testcase->type);?></th>
              <th class='c-status'>  <?php common::printOrderLink('status',        $orderBy, $vars, $lang->statusAB);?></th>
              <th class='c-user'>    <?php common::printOrderLink('openedBy',      $orderBy, $vars, $lang->testcase->openedByAB);?></th>
              <th class='c-user'>    <?php common::printOrderLink('lastRunner',    $orderBy, $vars, $lang->testtask->lastRunAccount);?></th>
              <th class='c-date'>    <?php common::printOrderLink('lastRunDate',   $orderBy, $vars, $lang->testtask->lastRunTime);?></th>
              <th class='c-result'>  <?php common::printOrderLink('lastRunResult', $orderBy, $vars, $lang->testtask->lastRunResult);?></th>
              <th class='c-actions-6 text-center'><?php echo $lang->actions;?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cases as $case):?>
            <?php
            $caseID = $type == 'assigntome' ? $case->case : $case->id;
            $runID  = $type == 'assigntome' ? $case->id   : 0;
            ?>
            <tr>
              <td class="c-id">
                <?php
                if($canBatchClone) echo html::checkbox('caseIDList', array($case->id => ''));
                echo sprintf('%03d', $case->id);
                ?>
              </td>
              <?php $params = "testcaseID=$caseID&version=$case->version";?>
              <?php $params = $type == 'assigntome' ? "testcaseID=$caseID&version=$case->version&from=testtask&taskID=$case->task" : "testcaseID=$caseID&version=$case->version&from=testcase&taskID=0&project=$projectID";?>
              <?php $icon = $case->auto == 'auto' ? "<i class='icon icon-ztf'></i> " : "<i class='icon icon-test'></i> ";?>
              <td class='c-title text-left' title="<?php echo $case->title?>"><?php echo $icon . html::a($this->createLink('testcase', 'view', $params, '', '', $case->project), $case->title, null, "style='color: $case->color' data-app='{$this->app->tab}'");?></td>
              <td><span class='label-pri <?php echo 'label-pri-' . $case->pri?>' title='<?php echo zget($lang->testcase->priList, $case->pri, $case->pri);?>'><?php echo zget($lang->testcase->priList, $case->pri, $case->pri)?></span></td>
              <td><?php echo $case->linkProjectName;?></td>
              <td><?php echo zget($products, $case->productID, '');?></td>
              <td><?php echo zget($lang->testcase->typeList, $case->type);?></td>
              <td>
              <?php
              if($case->needconfirm)
              {
                  echo "<span class='status-story status-changed' title='{$this->lang->story->changed}'>{$this->lang->story->changed}</span>";
              }
              elseif(isset($case->fromCaseVersion) and $case->fromCaseVersion > $case->version and !$case->needconfirm)
              {
                  echo "<span class='status-story status-changed' title='{$this->lang->testcase->changed}'>{$this->lang->testcase->changed}</span>";
              }
              else
              {
                  echo "<span class='status-testcase status-{$case->status}'>" . $this->processStatus('testcase', $case) . "</span>";
              }
              ?>
              </td>
              <td><?php echo zget($users, $case->openedBy);?></td>
              <td><?php echo zget($users, $case->lastRunner);?></td>
              <td><?php if(!helper::isZeroDate($case->lastRunDate)) echo substr($case->lastRunDate, 5, 11);?></td>
              <td class='result-testcase <?php echo $case->lastRunResult;?>'><?php echo $case->lastRunResult ? $lang->testcase->resultList[$case->lastRunResult] : $lang->testcase->unexecuted;?></td>
              <td class='c-actions'>
                <?php
                $case->browseType = $type;
                echo $this->testcase->buildOperateMenu($case, 'browse');
                ?>
              </td>
            </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <?php if($canBatchClone):?>
        <div class="checkbox-primary check-all"><label><?php echo $lang->selectAll?></label></div>
        <?php endif;?>
        <div class='table-actions btn-toolbar'>
          <div class='btn-group dropup'>
            <?php
            $actionLink = $this->createLink('testcase', 'batchClone');
            $misc       = $canBatchClone ? "onclick=\"setFormAction('$actionLink', '', '#executionBugForm')\"" : "disabled='disabled'";
             echo html::commonButton($lang->testcase->clone, $misc);
            ?>
          </div>
        </div>
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<?php js::set('moduleID', $moduleID);?>
<script>
$(function()
{
    $('#module' + moduleID).closest('li').addClass('active');
});
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
